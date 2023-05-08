<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Loop\Loop;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\Factory as SocketFactory;
use PE\Component\Socket\Select;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Client4
{
    private SessionInterface $session;
    private EmitterInterface $emitter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private Select $select;
    private Connection4 $connection;

    public function __construct(
        SessionInterface $session,
        EmitterInterface $emitter,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->session = $session;
        $this->emitter = $emitter;
        $this->serializer = $serializer;
        $this->logger = $logger ?: new NullLogger();
    }


    public function bind(string $address): void
    {
        $this->select = new Select();
        $factory = new SocketFactory($this->select);

        $client = $factory->createClient($address);

        $sequenceNum = $this->session->newSequenceNum();

        // bind start
        //$this->logger->log(LogLevel::DEBUG, "SMPP Client of ($address) connecting ...");
        $this->logger->log(LogLevel::DEBUG, "Connecting to {$address} ...");

        $this->connection = new Connection4($client, $this->emitter, $this->serializer, $this->logger);
        $this->connection->send(new PDU(PDU::ID_BIND_TRANSMITTER, PDU::STATUS_NO_ERROR, $sequenceNum, [
            'system_id'         => $this->session->getSystemID(),
            'password'          => $this->session->getPassword(),
            'system_type'       => '',
            'interface_version' => ConnectionInterface::INTERFACE_VER,
            'address'           => $this->session->getAddress(),
        ]));
        $this->connection->wait(5, $sequenceNum);
        // bind end

        $this->emitter->attach(Connection4::EVT_INPUT, \Closure::fromCallable([$this, 'processReceive']));

        $loop = new Loop(1, fn() => $this->dispatch());

        $client->setErrorHandler(function ($error) {
            $this->logger->log(LogLevel::ERROR, 'E: ' . $error);
        });

        $client->setCloseHandler(function ($error = null) use ($loop) {
            $loop->stop();
            $this->logger->log(LogLevel::DEBUG, 'C: ' . ($error ?: 'Closed'));
        });

        $loop->run();
    }

    public function dispatch()
    {
        $this->select->dispatch();
        $this->processTimeout();
    }

    public function processReceive(Connection4 $connection, PDU $pdu): void
    {
        // Remove expects PDU if any (prevents close client connection on timeout)
        $connection->delExpects($pdu->getSeqNum(), $pdu->getID());

        // Check errored response
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            $connection->close('Error [' . $pdu->getStatus() . ']');
            return;
        }

        if (PDU::ID_BIND_TRANSMITTER_RESP === $pdu->getID()) {
            $this->logger->log(LogLevel::DEBUG, "Connecting to {$connection->getClient()->getRemoteAddress()} OK");
        }

        if (PDU::ID_ENQUIRE_LINK === $pdu->getID()) {
            $connection->send(new PDU(PDU::ID_ENQUIRE_LINK_RESP, 0, $pdu->getSeqNum()));
        }
    }

    private function processTimeout()
    {
        $expects = $this->connection->getExpects();
        foreach ($expects as $expect) {
            if ($expect->getExpiredAt() < time()) {
                $this->connection->close('Timed out');
            }
        }
    }
}
