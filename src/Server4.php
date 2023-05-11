<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\Message;
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
        $connection->wait(
            $this->session->getResponseTimeout(),
            0,
            PDU::ID_BIND_RECEIVER,
            PDU::ID_BIND_TRANSMITTER,
            PDU::ID_BIND_TRANSCEIVER
        );
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
        $deferred = $connection->delExpects($pdu->getSeqNum(), $pdu->getID()) ?: new Deferred(0, 0);

        // Check errored response
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            $this->detachConnection(
                $connection,
                ': error [' . (PDU::getStatuses()[$pdu->getStatus()] ?? $pdu->getStatus()) . ']'
            );
            $deferred->failure($pdu);
            return;
        }

        if (array_key_exists($pdu->getID(), ConnectionInterface::BOUND_MAP)) {
            $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
            $connection->setStatus(ConnectionInterface::BOUND_MAP[$pdu->getID()]);
            $connection->setSession(
                new Session($pdu->get(PDU::KEY_SYSTEM_ID), $pdu->get(PDU::KEY_PASSWORD), $pdu->get('address'))
            );
            $deferred->success($pdu);
            return;
        }

        $deferred->success($pdu);//TODO are need here???

        switch ($pdu->getID()) {
            case PDU::ID_ENQUIRE_LINK:
            case PDU::ID_UNBIND:
                $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
                return;
            case PDU::ID_ALERT_NOTIFICATION:
                break;
            case PDU::ID_SUBMIT_SM:
                try {
                    $message = new Message(
                        $pdu->get(PDU::KEY_SRC_ADDRESS),
                        $pdu->get(PDU::KEY_DST_ADDRESS),
                        $pdu->get(PDU::KEY_SHORT_MESSAGE)
                    );
                    $message->setMessageID($this->factory->generateID());
                    $message->setScheduledAt($pdu->get(PDU::KEY_SCHEDULE_DELIVERY_TIME));
                    $message->setParams([
                        PDU::KEY_SERVICE_TYPE      => $pdu->get(PDU::KEY_SERVICE_TYPE),
                        PDU::KEY_DATA_CODING       => $pdu->get(PDU::KEY_DATA_CODING),
                        PDU::KEY_VALIDITY_PERIOD   => $pdu->get(PDU::KEY_VALIDITY_PERIOD),
                        PDU::KEY_REG_DELIVERY      => $pdu->get(PDU::KEY_REG_DELIVERY),
                        PDU::KEY_SM_DEFAULT_MSG_ID => $pdu->get(PDU::KEY_SM_DEFAULT_MSG_ID),
                        PDU::KEY_SM_LENGTH         => $pdu->get(PDU::KEY_SM_LENGTH),
                    ]);
                    $this->storage->insert($message);
                    $connection->send(new PDU(PDU::ID_SUBMIT_SM_RESP, 0, $pdu->getSeqNum(), [
                        PDU::KEY_MESSAGE_ID => $message->getMessageID()
                    ]));
                } catch (\Throwable $exception) {
                    $connection->send(new PDU(PDU::ID_SUBMIT_SM_RESP, PDU::STATUS_SUBMIT_SM_FAILED, $pdu->getSeqNum()));
                }
                break;
            case PDU::ID_DATA_SM:
                try {
                    $message = new Message(
                        $pdu->get(PDU::KEY_SRC_ADDRESS),
                        $pdu->get(PDU::KEY_DST_ADDRESS),
                        $pdu->get(PDU::KEY_SHORT_MESSAGE)
                    );
                    $message->setMessageID($this->factory->generateID());
                    $message->setParams([
                        PDU::KEY_SERVICE_TYPE      => $pdu->get(PDU::KEY_SERVICE_TYPE),
                        PDU::KEY_DATA_CODING       => $pdu->get(PDU::KEY_DATA_CODING),
                        PDU::KEY_VALIDITY_PERIOD   => $pdu->get(PDU::KEY_VALIDITY_PERIOD),
                        PDU::KEY_REG_DELIVERY      => $pdu->get(PDU::KEY_REG_DELIVERY),
                        PDU::KEY_SM_DEFAULT_MSG_ID => $pdu->get(PDU::KEY_SM_DEFAULT_MSG_ID),
                        PDU::KEY_SM_LENGTH         => $pdu->get(PDU::KEY_SM_LENGTH),
                    ]);
                    $this->storage->insert($message);
                    $connection->send(new PDU(PDU::ID_DATA_SM_RESP, 0, $pdu->getSeqNum(), [
                        PDU::KEY_MESSAGE_ID => $message->getMessageID()
                    ]));
                } catch (\Throwable $exception) {
                    $connection->send(new PDU(PDU::ID_DATA_SM_RESP, PDU::STATUS_DELIVERY_FAILURE, $pdu->getSeqNum()));
                }
                break;
            case PDU::ID_QUERY_SM:
                $search = (new Search())
                    ->setMessageID($pdu->get(PDU::KEY_MESSAGE_ID))
                    ->setSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $message = $this->storage->search($search);
                if ($message) {
                    $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum(), [
                        PDU::KEY_MESSAGE_ID    => $message->getMessageID(),
                        PDU::KEY_MESSAGE_STATE => $message->getStatus(),
                        PDU::KEY_FINAL_DATE    => $message->getDeliveredAt(),
                        PDU::KEY_ERROR_CODE    => $message->getErrorCode(),
                    ]));
                } else {
                    $connection->send(new PDU(PDU::ID_QUERY_SM_RESP, PDU::STATUS_QUERY_SM_FAILED, $pdu->getSeqNum()));
                }
                break;
            case PDU::ID_CANCEL_SM:
                $search = (new Search())
                    ->setMessageID($pdu->get(PDU::KEY_MESSAGE_ID))
                    ->setStatus(Message::STATUS_CREATED)
                    ->setSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $message = $this->storage->search($search);
                if ($message) {
                    $message->setStatus(Message::STATUS_DELETED);
                    $connection->send(new PDU(PDU::ID_CANCEL_SM_RESP, PDU::STATUS_NO_ERROR, $pdu->getSeqNum()));
                } else {
                    $connection->send(new PDU(PDU::ID_CANCEL_SM_RESP, PDU::STATUS_CANCEL_SM_FAILED, $pdu->getSeqNum()));
                }
                break;
            case PDU::ID_REPLACE_SM:
                $search = (new Search())
                    ->setMessageID($pdu->get(PDU::KEY_MESSAGE_ID))
                    ->setStatus(Message::STATUS_CREATED)
                    ->setSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $message = $this->storage->search($search);
                if ($message) {
                    $message->setMessage($pdu->get(PDU::KEY_SHORT_MESSAGE));
                    $message->setScheduledAt($pdu->get(PDU::KEY_SCHEDULE_DELIVERY_TIME));
                    $message->setParams([
                        PDU::KEY_VALIDITY_PERIOD   => $pdu->get(PDU::KEY_VALIDITY_PERIOD),
                        PDU::KEY_REG_DELIVERY      => $pdu->get(PDU::KEY_REG_DELIVERY),
                        PDU::KEY_SM_DEFAULT_MSG_ID => $pdu->get(PDU::KEY_SM_DEFAULT_MSG_ID),
                        PDU::KEY_SM_LENGTH         => $pdu->get(PDU::KEY_SM_LENGTH),
                    ]);
                    $this->storage->update($message);
                    $connection->send(new PDU(PDU::ID_REPLACE_SM_RESP, PDU::STATUS_NO_ERROR, $pdu->getSeqNum()));
                } else {
                    $connection->send(new PDU(
                        PDU::ID_REPLACE_SM_RESP,
                        PDU::STATUS_REPLACE_SM_FAILED,
                        $pdu->getSeqNum()
                    ));
                }
                break;
            default:
                return;
        }

        $this->emitter->dispatch(new Event(PDU::getIdentifiers()[$pdu->getID()], $connection, $pdu));
    }

    private function processTimeout(Connection4 $connection): void
    {
        $deferredList = $connection->getExpects();
        foreach ($deferredList as $deferred) {
            if ($deferred->getExpiredAt() < time()) {
                $deferred->failure(null);
                $this->detachConnection($connection, ': timed out');
            }
        }
    }

    private function processEnquire(Connection4 $connection): void
    {
        $overdue = time() - $connection->getLastMessageTime() > $this->session->getInactiveTimeout();
        if ($overdue) {
            $sequenceNum = $this->session->newSequenceNum();

            $connection->send(new PDU(PDU::ID_ENQUIRE_LINK, 0, $sequenceNum));
            $connection->wait($this->session->getResponseTimeout(), $sequenceNum, PDU::ID_ENQUIRE_LINK_RESP);
        }
    }

    private function processPending(Connection4 $connection): void
    {
        if (!array_key_exists($connection->getStatus(), ConnectionInterface::BOUND_MAP)) {
            return;
        }

        $search = (new Search())
            ->setStatus(Message::STATUS_CREATED)
            ->setTargetAddress($connection->getSession()->getAddress())
            ->setCheckSchedule();

        $message = $this->storage->search($search);
        if ($message) {
            $sequenceNum = $this->session->newSequenceNum();
            $connection->send(new PDU(PDU::ID_DELIVER_SM, 0, $sequenceNum, [
                PDU::KEY_SHORT_MESSAGE          => $message->getMessage(),
                PDU::KEY_DST_ADDRESS            => $message->getTargetAddress(),
                PDU::KEY_SRC_ADDRESS            => $message->getSourceAddress(),
                PDU::KEY_SCHEDULE_DELIVERY_TIME => $message->getScheduledAt(),
            ] + $message->getParams()));
            $connection
                ->wait($this->session->getResponseTimeout(), $sequenceNum, PDU::ID_DELIVER_SM_RESP)
                ->then(fn() => $message->setStatus(Message::STATUS_DELIVERED))
                ->else(fn() => $message->setStatus(Message::STATUS_REJECTED));
            $message->setStatus(Message::STATUS_ENROUTE);
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
