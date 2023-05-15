<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Exception\DecoderException;
use PE\Component\SMPP\Exception\ExceptionInterface;
use PE\Component\SMPP\Exception\InvalidArgumentException;
use PE\Component\SMPP\Exception\UnknownPDUException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Client implements ClientInterface
{
    private SessionInterface $session;
    private StorageInterface $storage;
    private EmitterInterface $emitter;
    private FactoryInterface $factory;
    private LoggerInterface $logger;
    private LoopInterface $loop;

    private ?ConnectionInterface $connection = null;

    public function __construct(
        SessionInterface $session,
        StorageInterface $storage,
        EmitterInterface $emitter,
        FactoryInterface $factory,
        LoggerInterface $logger = null
    ) {
        $this->session = $session;
        $this->storage = $storage;
        $this->emitter = $emitter;
        $this->factory = $factory;
        $this->logger  = $logger ?: new NullLogger();

        $this->loop = $factory->createDispatcher(function () {
            $this->processTimeout($this->connection);
            $this->processEnquire($this->connection);
            $this->processPending($this->connection);
        });
    }

    public function bind(string $address, int $mode): Deferred
    {
        if (!array_key_exists($mode, ConnectionInterface::BIND_MAP)) {
            throw new InvalidArgumentException('Invalid bind mode, allowed only one of PDU::ID_BIND_*');
        }

        $this->logger->log(LogLevel::DEBUG, "Connecting to $address ...");

        $this->connection = $this->factory->createConnection($this->factory->createSocketClient($address));
        $this->connection->setInputHandler(fn(PDU $pdu) => $this->processReceive($this->connection, $pdu));
        $this->connection->setErrorHandler(function (ExceptionInterface $exception) {
            $this->processErrored($this->connection, $exception);
        });
        $this->connection->setCloseHandler(function (string $message = null) {
            $this->logger->log(
                LogLevel::DEBUG,
                "Connection to {$this->connection->getRemoteAddress()} closed" . ($message ? ': ' . $message : '')
            );
            $this->loop->stop();
            $this->connection->setStatus(ConnectionInterface::STATUS_CLOSED);
        });

        return $this->send($mode, [
            'system_id'         => $this->session->getSystemID(),
            'password'          => $this->session->getPassword(),
            'system_type'       => '',
            'interface_version' => ConnectionInterface::INTERFACE_VER,
            'address'           => $this->session->getAddress(),
        ]);
    }

    public function send(int $id, array $params = []): Deferred
    {
        $sequenceNum = $this->session->newSequenceNum();
        $this->connection->send(new PDU($id, 0, $sequenceNum, $params));
        return $this->connection->wait($this->session->getResponseTimeout(), $sequenceNum, PDU::ID_GENERIC_NACK | $id);
    }

    public function wait(): void
    {
        $this->loop->run();
    }

    public function exit(): void
    {
        if (null === $this->connection) {
            throw new \RuntimeException('You must call bind() before any other operation');
        }

        if (ConnectionInterface::STATUS_CLOSED === $this->connection->getStatus()) {
            return;
        }

        $this->send(PDU::ID_UNBIND)
            ->then(fn() => $this->connection->close())
            ->else(fn() => $this->connection->close());
    }

    private function processErrored(ConnectionInterface $connection, ExceptionInterface $exception)
    {
        if ($exception instanceof UnknownPDUException) {
            $connection->error('Error [' . PDU::getStatuses()[PDU::STATUS_INVALID_COMMAND_ID] . ']');
            $connection->send(new PDU(PDU::ID_GENERIC_NACK, PDU::STATUS_INVALID_COMMAND_ID, 0), true);
        }
        if ($exception instanceof DecoderException) {
            $connection->error('Error [' . PDU::getStatuses()[PDU::STATUS_INVALID_COMMAND_LENGTH] . ']');
            $connection->send(new PDU(PDU::ID_GENERIC_NACK, PDU::STATUS_INVALID_COMMAND_LENGTH, 0), true);
        }
    }

    private function processReceive(ConnectionInterface $connection, PDU $pdu): void
    {
        // Remove expects PDU if any (prevents close client connection on timeout)
        $deferred = $connection->dequeuePacket($pdu->getSeqNum(), $pdu->getID()) ?: new Deferred(0, 0);

        // Check errored response
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            //TODO try recover error instead of close
            $connection->close('Error [' . (PDU::getStatuses()[$pdu->getStatus()] ?? $pdu->getStatus()) . ']');
            $deferred->failure($pdu);
            return;
        }

        if (array_key_exists($pdu->getID(), ConnectionInterface::BOUND_MAP)) {
            $this->logger->log(LogLevel::DEBUG, "Connecting to {$connection->getRemoteAddress()} OK");
            $this->connection->setStatus(ConnectionInterface::BOUND_MAP[$pdu->getID()]);
            $deferred->success($pdu);
            return;
        }

        if (array_key_exists($pdu->getID(), ConnectionInterface::ALLOWED_ID_BY_BOUND)
            && !in_array($connection->getStatus(), ConnectionInterface::ALLOWED_ID_BY_BOUND[$pdu->getID()])) {
            $connection->send(
                new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), PDU::STATUS_INVALID_BIND_STATUS, $pdu->getSeqNum())
            );
            $deferred->failure($pdu);
            return;
        }

        $deferred->success($pdu);

        switch ($pdu->getID()) {
            case PDU::ID_ENQUIRE_LINK:
                $connection->send(new PDU(PDU::ID_ENQUIRE_LINK_RESP, 0, $pdu->getSeqNum()));
                return;
            case PDU::ID_ALERT_NOTIFICATION:
                break;
            case PDU::ID_DELIVER_SM:
            case PDU::ID_DATA_SM:
                $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
                break;
            default:
                return;
        }

        $this->emitter->dispatch(new Event(PDU::getIdentifiers()[$pdu->getID()], $connection, $pdu));
    }

    private function processTimeout(ConnectionInterface $connection): void
    {
        $deferredList = $connection->getWaitQueue();
        foreach ($deferredList as $deferred) {
            if ($deferred->getExpiredAt() < time()) {
                $deferred->failure(null);
                $connection->close('Timed out');
            }
        }
    }

    private function processEnquire(ConnectionInterface $connection): void
    {
        $overdue = time() - $connection->getLastMessageTime() > $this->session->getInactiveTimeout();
        if ($overdue) {
            $sequenceNum = $this->session->newSequenceNum();

            $connection->send(new PDU(PDU::ID_ENQUIRE_LINK, 0, $sequenceNum));
            $connection->wait($this->session->getResponseTimeout(), $sequenceNum, PDU::ID_ENQUIRE_LINK_RESP);
        }
    }

    private function processPending(ConnectionInterface $connection): void
    {
        if (!in_array($connection->getStatus(), ConnectionInterface::BOUND_MAP)) {
            return;
        }

        if ($message = $this->storage->select()) {
            //TODO callbacks or remove storage usage entirely
            $this->send(PDU::ID_SUBMIT_SM, [
                PDU::KEY_SHORT_MESSAGE => $message->getBody(),
                PDU::KEY_DST_ADDRESS   => $message->getTargetAddress(),
                PDU::KEY_SRC_ADDRESS   => $message->getSourceAddress(),
                PDU::KEY_SCHEDULED_AT  => $message->getScheduledAt(),
            ] + $message->getParams());

            $this->storage->delete($message);
        }
    }
}
