<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Loop\Loop;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\SMS;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\Select;
use Psr\Log\LoggerInterface;

final class Sender4
{
    private SessionInterface $session;
    private EmitterInterface $emitter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private ?Connection4 $connection;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function bind(string $address): void
    {
        $select  = new Select();
        $factory = new \PE\Component\Socket\Factory($select);
        $socket  = $factory->createClient($address);

        $this->connection = new Connection4($socket, $this->emitter, $this->serializer, $this->logger);
    }

    public function send(SMS $message): void
    {
        $sequenceNum = $this->session->newSequenceNum();

        $this->connection->send(new PDU(PDU::ID_SUBMIT_SM, PDU::STATUS_NO_ERROR, $sequenceNum, [
            'short_message'          => $message->getMessage(),
            'dest_address'           => $message->getRecipient(),
            'source_address'         => $message->getSender() ?: $this->session->getAddress(),
            'data_coding'            => $message->getDataCoding(),
            'schedule_delivery_time' => $message->getScheduleAt(),
            'registered_delivery'    => $message->hasRegisteredDelivery(),
        ]));
        $this->connection->wait(5, $sequenceNum, PDU::ID_SUBMIT_SM_RESP);

        $loop = new Loop(1, fn() => null);
        $loop->run();
    }

    public function exit(): void
    {
        //TODO stop loop & close connection
    }
}
