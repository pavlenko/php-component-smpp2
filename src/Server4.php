<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server4
{
    private SessionInterface $session;
    private StorageInterface $storage;
    private EmitterInterface $emitter;
    private FactoryInterface $factory;
    private LoggerInterface $logger;
    private LoopInterface $loop;
    private \SplObjectStorage $connections;

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
            foreach ($this->connections as $connection) {
                $this->processTimeout($connection);
            }
            foreach ($this->connections as $connection) {
                $this->processEnquire($connection);
            }
            foreach ($this->connections as $connection) {
                $this->processPending($connection);
            }
        });

        $this->connections = new \SplObjectStorage();
    }

    public function bind(string $address): void
    {
        $server = $this->factory->createSocketServer($address);
        $server->setInputHandler(function (SocketClientInterface $client) {
            $connection = $this->factory->createConnection($client);
            $connection->setInputHandler(fn(PDU $pdu) => $this->processReceive($connection, $pdu));
            $connection->setErrorHandler(fn($error) => $this->logger->log(LogLevel::ERROR, '< E: ' . $error));
            $connection->setCloseHandler(fn() => $this->detachConnection($connection));

            $this->attachConnection($connection);
        });

        $server->setErrorHandler(fn($e) => $this->logger->log(LogLevel::ERROR, 'E: ' . $e));
        $server->setCloseHandler(fn($e = null) => $this->logger->log(LogLevel::DEBUG, 'C: ' . ($e ?: 'Closed')));

        $this->logger->log(LogLevel::DEBUG, 'Listen to ' . $server->getAddress());

        $this->loop->run();
    }

    private function attachConnection(Connection4 $connection): void
    {
        $this->logger->log(LogLevel::DEBUG, '< New connection from ' . $connection->getRemoteAddress());
        $this->connections->attach($connection);
        $connection->wait(5, 0, PDU::ID_BIND_RECEIVER, PDU::ID_BIND_TRANSMITTER, PDU::ID_BIND_TRANSCEIVER);
    }

    private function detachConnection(Connection4 $connection, string $message = null): void
    {
        $this->logger->log(LogLevel::DEBUG, '< Close connection from ' . $connection->getRemoteAddress() . $message);
        $this->connections->detach($connection);
        $connection->setCloseHandler(fn() => null);
        $connection->close();
    }

    private function processReceive(Connection4 $connection, PDU $pdu): void
    {
        // Remove expects PDU if any (prevents close client connection on timeout)
        $deferred = $connection->delExpects($pdu->getSeqNum(), $pdu->getID());

        // Check errored response
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            if ($deferred) {
                $deferred->failure($pdu);
            }
            $this->detachConnection(
                $connection,
                ': error [' . (PDU::getStatuses()[$pdu->getStatus()] ?? $pdu->getStatus()) . ']'
            );
            return;
        } elseif ($deferred) {
            $deferred->success($pdu);
        }

        if (array_key_exists($pdu->getID(), ConnectionInterface::BOUND_MAP)) {
            $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
            $connection->setStatus(ConnectionInterface::BOUND_MAP[$pdu->getID()]);
            $connection->setSession(new Session(
                $pdu->get('system_id'),
                $pdu->get('password'),
                $pdu->get('address')
            ));
            return;
        }

        switch ($pdu->getID()) {
            case PDU::ID_ENQUIRE_LINK:
            case PDU::ID_UNBIND:
                $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
                return;
            case PDU::ID_ALERT_NOTIFICATION:
                break;
            case PDU::ID_SUBMIT_SM:
                $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
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
                break;
            case PDU::ID_DATA_SM:
                $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
                break;
            case PDU::ID_QUERY_SM:
                //TODO check message status in storage
                $message = $this->storage->search($pdu->get('message_id'), $pdu->get('source_addr'));
                if ($message) {
                    $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum(), [
                        'message_id'    => $pdu->get('message_id'),
                        'final_date'    => new \DateTime(),//or null if no delivered
                        'message_state' => 1,//
                        'error_code'    => 0,//or some code if errored delivery
                    ]));
                }
                break;
            case PDU::ID_CANCEL_SM:
                //TODO remove message from storage
            case PDU::ID_REPLACE_SM:
                //TODO replace message in storage
                $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
                break;
            default:
                return;
        }

        $this->emitter->dispatch(new Event(PDU::getIdentifiers()[$pdu->getID()], $connection, $pdu));
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

        $pdu = $this->storage->select($connection->getSession()->getAddress());
        if ($pdu) {
            $connection->send($pdu);
            $connection->wait(5, $pdu->getSeqNum(), PDU::ID_GENERIC_NACK | $pdu->getID());
            $this->storage->delete($pdu);
        }
    }

    public function stop(): void
    {
        foreach ($this->connections as $connection) {
            $this->detachConnection($connection);
        }
        $this->loop->stop();
    }
}
