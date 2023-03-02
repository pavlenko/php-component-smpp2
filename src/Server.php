<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\EventsInterface;
use PE\Component\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server implements ServerInterface
{
    public const EVENT_RECEIVE = 'server.receive';

    private string $address;
    private FactoryInterface $factory;
    private SessionInterface $session;
    private EventsInterface $events;
    private LoggerInterface $logger;
    private ConnectionInterface $connection;
    private \SplObjectStorage $sessions;

    public function __construct(string $address, FactoryInterface $factory, SessionInterface $session, EventsInterface $events, LoggerInterface $logger = null)
    {
        $this->address = $address;
        $this->factory = $factory;
        $this->session = $session;
        $this->events  = $events;
        $this->logger  = $logger ?: new NullLogger();

        $this->sessions = new \SplObjectStorage();
    }

    public function bind(): void
    {
        $this->connection = $this->factory->createServerConnection($this->address);
    }

    public function tick(): void
    {
        $master = $this->connection->getStream();

        $r = array_merge([$master], iterator_to_array($this->sessions));
        $n = [];
        Stream::select($r, $n, $n, 0.01);//<-- here always need timeout, for prevent blocking process

        if (in_array($master, $r)) {
            unset($r[array_search($master, $r)]);
            $this->attachConnection($this->factory->createStreamConnection($master->accept(), $this->logger));
        }

        foreach ($r as $stream) {
            $this->processReceive($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            //$this->handleTimeout($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            //$this->handleEnquire($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            //$this->handlePending($stream);
        }
    }

    private function attachConnection(ConnectionInterface $connection): void
    {
        $this->logger->log(LogLevel::INFO, 'Accept connection');
        $this->sessions->attach($connection->getStream(), $connection);

        $pdu = $connection->waitPDU();
        if (array_key_exists($pdu->getID(), ConnectionInterface::BOUND_MAP)) {
            $connection->sendPDU(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
        }
    }

    private function detachConnection(ConnectionInterface $connection, bool $unbind = true): void
    {
        $this->logger->log(LogLevel::INFO, 'Detach connection');

        if ($unbind) {
            $sequenceNum = $this->session->newSequenceNum();

            $this->connection->sendPDU(new PDU(PDU::ID_UNBIND, PDU::STATUS_NO_ERROR, $sequenceNum));
            $this->connection->waitPDU($sequenceNum);
        }

        $connection->exit();
        $this->sessions->detach($connection->getStream());
    }

    private function processReceive(ConnectionInterface $connection): void
    {
        $pdu = $connection->readPDU();
        if (null === $pdu) {
            $this->detachConnection($connection, false);
            return;
        }
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            throw new \UnexpectedValueException('Error', $pdu->getStatus());
        }

        switch ($pdu->getID()) {
            case PDU::ID_UNBIND:
                $connection->sendPDU(new PDU(PDU::ID_UNBIND_RESP, 0, $pdu->getSeqNum()));
                $this->detachConnection($connection, false);
                break;
            default:
                $this->events->trigger(self::EVENT_RECEIVE, $connection, $pdu);
        }
    }

    public function exit(): void
    {
        foreach ($this->sessions as $stream) {
            $this->detachConnection($this->sessions[$stream]);
        }
        $this->connection->exit();
    }
}
