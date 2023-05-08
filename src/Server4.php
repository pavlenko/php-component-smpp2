<?php

namespace PE\Component\SMPP;

use PE\Component\Event\Emitter;
use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\Loop\Loop;
use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\Serializer;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use PE\Component\Socket\Factory as SocketFactory;
use PE\Component\Socket\Select;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server4
{
    private \SplObjectStorage $sessions;
    private EmitterInterface $emitter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        EmitterInterface $emitter,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->sessions   = new \SplObjectStorage();
        $this->emitter    = $emitter;
        $this->serializer = $serializer;
        $this->logger     = $logger ?: new NullLogger();
    }

    public function bind(string $address): void
    {
        $loop    = new Loop();
        $select  = new Select();
        $factory = new SocketFactory($select);

        $server = $factory->createServer($address);

        //TODO maybe split onInput into two, or first try to accept connection
        //TODO onConnect($stream)
        //TODO onReceive($stream)

        $server->setInputHandler(function (SocketClientInterface $input) {
            $input->setErrorHandler(fn($error) => $this->logger->log(LogLevel::ERROR, '< E: ' . $error));

            //TODO detach session
            $input->setCloseHandler(fn($error) => $this->logger->log(LogLevel::DEBUG, '< C: ' . ($error ?: 'Closed')));

            $client = new Connection4($input, $this->emitter, $this->serializer, $this->logger);

            $this->logger->log(LogLevel::DEBUG, '> New connection from' . $input->getRemoteAddress());
            $this->sessions->attach($client);
        });

        $server->setErrorHandler(function ($error) {
            $this->logger->log(LogLevel::ERROR, 'E: ' . $error);
        });

        $server->setCloseHandler(function ($error = null) {
            $this->logger->log(LogLevel::DEBUG, 'C: ' . ($error ?: 'Closed'));
        });

        $this->logger->log(LogLevel::DEBUG, 'Listen to ' . $server->getAddress());

        $loop->addPeriodicTimer(0.001, fn() => $select->dispatch());
        $loop->run();
    }

    public function dispatch(): void
    {
        $this->select->dispatch();

        foreach ($this->sessions as $session) {
            $this->processTimeout($this->sessions[$session]);
        }

        foreach ($this->sessions as $session) {
            // Request storage for pending PDUs for this connection
            //$this->processPending($this->sessions[$session]);
        }
    }

    private function processReceive(Connection4 $connection, PDU $pdu): void
    {
        // Remove expects PDU if any (prevents close client connection on timeout)
        $connection->delExpects($pdu->getSeqNum(), $pdu->getID());

        // Check errored response
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            $connection->close('Error [' . $pdu->getStatus() . ']');
            $this->sessions->detach($connection);
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
        } elseif (PDU::ID_UNBIND === $pdu->getID()) {
            // Handle unbind request
            $connection->send(new PDU(PDU::ID_GENERIC_NACK | $pdu->getID(), 0, $pdu->getSeqNum()));
            $connection->close('Error [' . $pdu->getStatus() . ']');
            $this->sessions->detach($connection);
        } else {
            // Handle other requests redirected to user code
            $this->emitter->dispatch(new Event('server.receive', $pdu));
        }
    }

    private function processTimeout(Connection4 $connection): void
    {
        $requests = $connection->getExpects();
        foreach ($requests as $request) {
            if ($request->getExpiredAt() < time()) {
                $connection->close('Timed out');
                $this->sessions->detach($connection);
            }
        }
    }

    public function stop(): void
    {
    }
}
