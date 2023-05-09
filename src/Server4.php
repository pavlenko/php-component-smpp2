<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\Loop\Loop;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use PE\Component\Socket\Factory as SocketFactory;
use PE\Component\Socket\Select;
use PE\Component\Socket\SelectInterface as SocketSelectInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server4
{
    private SessionInterface $session;
    private StorageInterface $storage;

    /**
     * @var \SplObjectStorage|SessionInterface[]
     */
    private \SplObjectStorage $sessions;
    private EmitterInterface $emitter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private SocketSelectInterface $select;

    public function __construct(
        SessionInterface $session,
        EmitterInterface $emitter,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->session    = $session;
        $this->storage    = new Storage4();//TODO pass to constructor
        $this->sessions   = new \SplObjectStorage();
        $this->emitter    = $emitter;
        $this->serializer = $serializer;
        $this->logger     = $logger ?: new NullLogger();
        $this->select     = new Select();//TODO pass to constructor
    }

    public function bind(string $address): void
    {
        $factory = new SocketFactory($this->select);//TODO pass to constructor

        $server = $factory->createServer($address);
        $server->setInputHandler(function (SocketClientInterface $client) {
            $connection = new Connection4($client, $this->serializer, $this->logger);
            $connection->setInputHandler(fn(PDU $pdu) => $this->processReceive($connection, $pdu));
            $connection->setErrorHandler(fn($error) => $this->logger->log(LogLevel::ERROR, '< E: ' . $error));
            $connection->setCloseHandler(fn() => $this->detachConnection($connection));

            $this->attachConnection($connection);
        });

        $server->setErrorHandler(function ($error) {
            $this->logger->log(LogLevel::ERROR, 'E: ' . $error);
        });

        $server->setCloseHandler(function ($error = null) {
            $this->logger->log(LogLevel::DEBUG, 'C: ' . ($error ?: 'Closed'));
        });

        $this->logger->log(LogLevel::DEBUG, 'Listen to ' . $server->getAddress());

        $loop = new Loop(1, fn() => $this->dispatch());//TODO pass to constructor
        $loop->run();
    }

    public function dispatch(): void
    {
        $this->select->dispatch();

        foreach ($this->sessions as $session) {
            $this->processTimeout($session);
        }

        foreach ($this->sessions as $session) {
            $this->processEnquire($session);
        }

        foreach ($this->sessions as $session) {
            $this->processPending($session);
        }
    }

    private function attachConnection(Connection4 $connection): void
    {
        $this->logger->log(LogLevel::DEBUG, '< New connection from ' . $connection->getRemoteAddress());
        $this->sessions->attach($connection);
        $connection->wait(5, 0, PDU::ID_BIND_RECEIVER, PDU::ID_BIND_TRANSMITTER, PDU::ID_BIND_TRANSCEIVER);
    }

    private function detachConnection(Connection4 $connection, string $message = null): void
    {
        $this->logger->log(LogLevel::DEBUG, '< Close connection from ' . $connection->getRemoteAddress() . $message);
        $this->sessions->detach($connection);
        $connection->setCloseHandler(fn() => null);
        $connection->close();
    }

    private function processReceive(Connection4 $connection, PDU $pdu): void
    {
        // Remove expects PDU if any (prevents close client connection on timeout)
        $connection->delExpects($pdu->getSeqNum(), $pdu->getID());

        // Check errored response
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            $this->detachConnection($connection, ': error [' . $pdu->getStatus() . ']');
            return;
        }

        if (array_key_exists($pdu->getID(), ConnectionInterface::BOUND_MAP)) {
            // Handle bind request
            $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
            // Store registration data
            $this->sessions[$connection] = new Session(
                $pdu->get('system_id'),
                $pdu->get('password'),
                $pdu->get('address')
            );
            $connection->setStatus(ConnectionInterface::BOUND_MAP[$pdu->getID()]);
        } elseif (PDU::ID_ENQUIRE_LINK === $pdu->getID()) {
            $connection->send(new PDU(PDU::ID_ENQUIRE_LINK_RESP, 0, $pdu->getSeqNum()));
        } elseif (PDU::ID_UNBIND === $pdu->getID()) {
            // Handle unbind request
            $connection->send(new PDU(PDU::ID_UNBIND_RESP, 0, $pdu->getSeqNum()));
        } elseif (PDU::ID_SUBMIT_SM === $pdu->getID()) {
            $connection->send(new PDU(PDU::ID_SUBMIT_SM_RESP, 0, $pdu->getSeqNum()));
            $this->storage->insert(new PDU(
                PDU::ID_DELIVER_SM,
                PDU::STATUS_NO_ERROR,
                $this->session->newSequenceNum(),
                [
                    'short_message'          => $pdu->get('short_message'),
                    'dest_address'           => $pdu->get('dest_address'),
                    'source_address'         => $pdu->get('source_address'),
                    'data_coding'            => $pdu->get('data_coding'),
                    'schedule_delivery_time' => $pdu->get('schedule_delivery_time'),
                    'registered_delivery'    => $pdu->get('registered_delivery'),
                ]
            ));
        } else {
            // Handle other requests redirected to user code
            $this->emitter->dispatch(new Event('server.receive', $pdu));
        }
    }

    private function processTimeout(Connection4 $connection): void
    {
        $expects = $connection->getExpects();
        foreach ($expects as $expect) {
            if ($expect->getExpiredAt() < time()) {
                $this->detachConnection($connection, ': timed out');
            }
        }
    }

    private function processEnquire(Connection4 $connection): void
    {
        $overdue = time() - $connection->getLastMessageTime() > 15;
        if ($overdue) {
            $sequenceNum = $this->session->newSequenceNum();

            $connection->send(new PDU(PDU::ID_ENQUIRE_LINK, 0, $sequenceNum));
            $connection->wait(5, $sequenceNum, PDU::ID_ENQUIRE_LINK_RESP);
        }
    }

    private function processPending(Connection4 $connection): void
    {
        if (!array_key_exists($connection->getStatus(), ConnectionInterface::BOUND_MAP)) {
            return;
        }

        $pdu = $this->storage->select($this->sessions[$connection]->getAddress());
        if ($pdu) {
            $connection->send($pdu);
            $connection->wait(5, $pdu->getSeqNum(), PDU::ID_GENERIC_NACK | $pdu->getID());
            $this->storage->delete($pdu);
        }
    }

    public function stop(): void
    {
    }
}
