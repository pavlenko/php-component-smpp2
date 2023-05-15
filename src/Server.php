<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\Message;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Exception\DecoderException;
use PE\Component\SMPP\Exception\ExceptionInterface;
use PE\Component\SMPP\Exception\UnknownPDUException;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server implements ServerInterface
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
            $connection->setInputHandler(function (PDU $pdu) use ($connection) {
                $this->processReceive($connection, $pdu);
            });
            $connection->setErrorHandler(function (ExceptionInterface $error) use ($connection) {
                $this->processErrored($connection, $error);
            });
            $connection->setCloseHandler(function (string $message = null) use ($connection) {
                $this->detachConnection($connection, $message);
            });

            $this->attachConnection($connection);
        });

        $server->setErrorHandler(fn($e) => $this->logger->log(LogLevel::ERROR, 'E: ' . $e));
        $server->setCloseHandler(fn($e) => $this->logger->log(LogLevel::DEBUG, 'C: ' . ($e ?: 'Closed')));

        $this->logger->log(LogLevel::DEBUG, 'Listen to ' . $server->getAddress());

        $this->loop->run();
    }

    private function attachConnection(ConnectionInterface $connection): void
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

    private function detachConnection(ConnectionInterface $connection, string $message = null): void
    {
        $this->logger->log(
            LogLevel::DEBUG,
            '< Close connection from ' . $connection->getRemoteAddress() . ' ' . $message
        );
        $this->connections->detach($connection);
        $connection->setCloseHandler(fn() => null);
        $connection->close();
    }

    private function processErrored(ConnectionInterface $connection, ExceptionInterface $exception): void
    {
        if ($exception instanceof UnknownPDUException) {
            $connection->send(new PDU(PDU::ID_GENERIC_NACK, PDU::STATUS_INVALID_COMMAND_ID, 0), true);
        }
        if ($exception instanceof DecoderException) {
            $connection->send(new PDU(PDU::ID_GENERIC_NACK, PDU::STATUS_INVALID_COMMAND_LENGTH, 0), true);
        }
        $connection->close();
    }

    private function processReceive(ConnectionInterface $connection, PDU $pdu): void
    {
        // Remove expects PDU if any (prevents close client connection on timeout)
        $deferred = $connection->dequeuePacket($pdu->getSeqNum(), $pdu->getID()) ?: new Deferred(0, 0);

        // Check errored response
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            $this->detachConnection(
                $connection,
                ': error [' . (PDU::getStatuses()[$pdu->getStatus()] ?? $pdu->getStatus()) . ']'
            );
            $deferred->failure($pdu);
            return;
        }

        if (array_key_exists($pdu->getID(), ConnectionInterface::BIND_MAP)) {
            if (in_array($connection->getStatus(), ConnectionInterface::BIND_MAP)) {
                $connection->send(
                    new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), PDU::STATUS_ALREADY_BOUND, $pdu->getSeqNum()),
                    true
                );
                $deferred->failure($pdu);
                return;
            }

            try {
                $deferred->success($pdu);
            } catch (\Throwable $exception) {
                $connection->send(
                    new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), PDU::STATUS_BIND_FAILED, $pdu->getSeqNum()),
                    true
                );
                $deferred->failure($pdu);
                return;
            }

            $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), PDU::STATUS_NO_ERROR, $pdu->getSeqNum(), [
                PDU::KEY_SYSTEM_ID => $this->session->getSystemID(),
            ]));
            $connection->setStatus(ConnectionInterface::BIND_MAP[$pdu->getID()]);
            $connection->setSession(
                new Session($pdu->get(PDU::KEY_SYSTEM_ID), $pdu->get(PDU::KEY_PASSWORD), $pdu->get(PDU::KEY_ADDRESS))
            );
            $deferred->success($pdu);
            return;
        }

        if (array_key_exists($pdu->getID(), ConnectionInterface::ALLOWED_ID_BY_BOUND)
            && !in_array($connection->getStatus(), ConnectionInterface::ALLOWED_ID_BY_BOUND)) {
            $connection->send(
                new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), PDU::STATUS_INVALID_BIND_STATUS, $pdu->getSeqNum()),
                true
            );
            $deferred->failure($pdu);
            return;
        }

        $deferred->success($pdu);

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
                        $pdu->get(PDU::KEY_SHORT_MESSAGE),
                        $pdu->get(PDU::KEY_DST_ADDRESS),
                        $pdu->get(PDU::KEY_SRC_ADDRESS),
                    );
                    $message->setID($this->factory->generateID());
                    $message->setDataCoding($pdu->get(PDU::KEY_DATA_CODING));
                    $message->setScheduledAt($pdu->get(PDU::KEY_SCHEDULED_AT));
                    $message->setExpiredAt($pdu->get(PDU::KEY_VALIDITY_PERIOD));
                    $message->setParams([
                        PDU::KEY_SERVICE_TYPE      => $pdu->get(PDU::KEY_SERVICE_TYPE),
                        PDU::KEY_REG_DELIVERY      => $pdu->get(PDU::KEY_REG_DELIVERY),
                        PDU::KEY_SM_DEFAULT_MSG_ID => $pdu->get(PDU::KEY_SM_DEFAULT_MSG_ID),
                        PDU::KEY_SM_LENGTH         => $pdu->get(PDU::KEY_SM_LENGTH),
                    ]);

                    $this->storage->insert($message);

                    $connection->send(new PDU(PDU::ID_SUBMIT_SM_RESP, 0, $pdu->getSeqNum(), [
                        PDU::KEY_MESSAGE_ID => $message->getID()
                    ]));
                } catch (\Throwable $exception) {
                    $connection->send(new PDU(PDU::ID_SUBMIT_SM_RESP, PDU::STATUS_SUBMIT_SM_FAILED, $pdu->getSeqNum()));
                }
                break;
            case PDU::ID_DATA_SM:
                try {
                    $message = new Message(
                        $pdu->get(PDU::KEY_SHORT_MESSAGE),
                        $pdu->get(PDU::KEY_DST_ADDRESS),
                        $pdu->get(PDU::KEY_SRC_ADDRESS),
                    );
                    $message->setID($this->factory->generateID());
                    $message->setDataCoding($pdu->get(PDU::KEY_DATA_CODING));
                    $message->setParams([
                        PDU::KEY_SERVICE_TYPE => $pdu->get(PDU::KEY_SERVICE_TYPE),
                        PDU::KEY_REG_DELIVERY => $pdu->get(PDU::KEY_REG_DELIVERY),
                    ]);

                    $this->storage->insert($message);

                    $connection->send(new PDU(PDU::ID_DATA_SM_RESP, 0, $pdu->getSeqNum(), [
                        PDU::KEY_MESSAGE_ID => $message->getID()
                    ]));
                } catch (\Throwable $exception) {
                    $connection->send(new PDU(PDU::ID_DATA_SM_RESP, PDU::STATUS_DELIVERY_FAILURE, $pdu->getSeqNum()));
                }
                break;
            case PDU::ID_QUERY_SM:
                $search = (new Criteria())
                    ->setMessageID($pdu->get(PDU::KEY_MESSAGE_ID))
                    ->setSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS));

                if ($message = $this->storage->search($search)) {
                    $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum(), [
                        PDU::KEY_MESSAGE_ID    => $message->getID(),
                        PDU::KEY_MESSAGE_STATE => $message->getStatus(),
                        PDU::KEY_FINAL_DATE    => $message->getDeliveredAt(),
                        PDU::KEY_ERROR_CODE    => $message->getErrorCode(),
                    ]));
                } else {
                    $connection->send(new PDU(PDU::ID_QUERY_SM_RESP, PDU::STATUS_QUERY_SM_FAILED, $pdu->getSeqNum()));
                }
                break;
            case PDU::ID_CANCEL_SM:
                $search = (new Criteria())
                    ->setMessageID($pdu->get(PDU::KEY_MESSAGE_ID))
                    ->setStatus(Message::STATUS_PENDING)
                    ->setSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS));

                if ($message = $this->storage->search($search)) {
                    $message->setStatus(Message::STATUS_DELETED);
                    $connection->send(new PDU(PDU::ID_CANCEL_SM_RESP, PDU::STATUS_NO_ERROR, $pdu->getSeqNum()));
                } else {
                    $connection->send(new PDU(PDU::ID_CANCEL_SM_RESP, PDU::STATUS_CANCEL_SM_FAILED, $pdu->getSeqNum()));
                }
                break;
            case PDU::ID_REPLACE_SM:
                $search = (new Criteria())
                    ->setMessageID($pdu->get(PDU::KEY_MESSAGE_ID))
                    ->setStatus(Message::STATUS_PENDING)
                    ->setSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS));

                if ($message = $this->storage->search($search)) {
                    $message->setBody($pdu->get(PDU::KEY_SHORT_MESSAGE));
                    $message->setScheduledAt($pdu->get(PDU::KEY_SCHEDULED_AT));
                    $message->setExpiredAt($pdu->get(PDU::KEY_VALIDITY_PERIOD));
                    $message->setParams([
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

    private function processTimeout(ConnectionInterface $connection): void
    {
        $deferredList = $connection->getWaitQueue();
        foreach ($deferredList as $deferred) {
            if ($deferred->getExpiredAt() < time()) {
                $deferred->failure(null);
                $this->detachConnection($connection, ': timed out');
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
        if (!array_key_exists($connection->getStatus(), ConnectionInterface::BOUND_MAP)) {
            return;
        }

        $search = (new Criteria())
            ->setStatus(Message::STATUS_PENDING)
            ->setTargetAddress($connection->getSession()->getAddress())
            ->setCheckSchedule();

        if ($message = $this->storage->search($search)) {
            $sequenceNum = $this->session->newSequenceNum();

            $connection->send(new PDU(PDU::ID_DELIVER_SM, 0, $sequenceNum, [
                PDU::KEY_SHORT_MESSAGE => $message->getBody(),
                PDU::KEY_DST_ADDRESS   => $message->getTargetAddress(),
                PDU::KEY_SRC_ADDRESS   => $message->getSourceAddress(),
                PDU::KEY_SCHEDULED_AT  => $message->getScheduledAt(),
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
