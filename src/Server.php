<?php

namespace PE\SMPP;

use PE\SMPP\PDU\BindTransmitter;
use PE\SMPP\PDU\BindTransmitterResp;
use PE\SMPP\PDU\SubmitSm;
use PE\SMPP\PDU\SubmitSmResp;
use PE\SMPP\Util\Buffer;
use PE\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server
{
    private LoggerInterface $logger;
    private ?Stream $master = null;

    /**
     * @var \SplObjectStorage|SessionV2[]
     */
    private \SplObjectStorage $sessions;

    /**
     * @var Stream[]
     */
    private array $clients = [];

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger   = $logger ?: new NullLogger();
        $this->sessions = new \SplObjectStorage();
    }

    public function init(string $address): void
    {
        $this->master = Stream::createServer($address);
        $this->master->setBlocking(false);

        if (0 === strpos($address, 'tls')) {
            $this->master->setCrypto(true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        }
    }

    public function tick(): void
    {
        $r = array_merge([$this->master], iterator_to_array($this->sessions));
        $n = null;

        // Check streams first
        if (0 === Stream::select($r, $n, $n)) {
            return;
        }

        // Handle new connections
        if (in_array($this->master, $r)) {
            unset($r[array_search($this->master, $r)]);
            $this->handleConnect($this->master->accept());
        }

        // Handle incoming data
        foreach ($r as $client) {
            $this->handleReceive($client);
            $head = $client->readData(16);

            if ('' === $head) {
                $client->close();
                unset($this->clients[array_search($client, $this->clients)]);
                continue;
            }

            $buffer = new Buffer($head);
            if ($buffer->bytesLeft() < 16) {
                throw new \RuntimeException('Malformed PDU header');
            }

            $length        = $buffer->shiftInt32();
            $commandID     = $buffer->shiftInt32();
            $commandStatus = $buffer->shiftInt32();
            $sequenceNum   = $buffer->shiftInt32();

            $body = (string) $client->readData($length);
            if (strlen($body) < $length - 16) {
                throw new \RuntimeException('Malformed PDU body');
            }

            new Command($commandID, $commandStatus, $sequenceNum, $body);
        }

        // Handle rest clients connected check
        foreach ($this->clients as $key => $client) {
            if (!in_array($client, $r)) {
                continue;
            }

            $num = $client->sendData('ENQUIRE_LINK');
            if (0 === $num) {
                $client->close();
                unset($this->clients[$key]);
            }
        }
    }

    private function handleConnect(Stream $stream)
    {
        $this->logger->log(LogLevel::DEBUG, 'Accepted new connection');
        $this->sessions->attach($stream, new SessionV2($stream));
    }

    private function handleReceive(Stream $stream)
    {
        $pdu = $this->sessions[$stream]->readPDU();
        if ($pdu instanceof BindTransmitter) {//receiver, transceiver
            $this->logger->log(LogLevel::DEBUG, 'BIND_TRANSMITTER');

            $res = new BindTransmitterResp();
            $res->setCommandStatus(CommandStatus::NO_ERROR);
            $res->setSequenceNumber($pdu->getSequenceNumber());

            $this->sessions[$stream]->sendPDU($res);
        }
        if ($pdu instanceof SubmitSm) {//sm_multi
            $res = new SubmitSmResp();
            $res->setCommandStatus(CommandStatus::NO_ERROR);
            $res->setSequenceNumber($pdu->getSequenceNumber());
            $res->setMessageID(uniqid('', true));
            $this->sessions[$stream]->sendPDU($res);
        }
        if (PDU::CANCEL_SM === $pdu->getCommandID()) {//TRY to simplify PDU object (fields, address, tlv)
            $this->sessions[$stream]->sendPDU(new PDU(PDU::CANCEL_SM_RESP, PDU::NO_ERROR, $pdu->getSequenceNumber()));
        }
        // query_sm, cancel_sm, replace_sm, unbind
        // respond with generic nack if pdu not handled by this server
    }

    //TODO Process sent PDU responses timed out
    private function handleTimeout(){}

    //TODO rename method???
    //TODO send deliver_sm if processed & check it response
    //TODO Send enquire link PDU for check client alive
    private function handleEnquire(){}

    private function handleStalled(): void
    {
        foreach ($this->sessions as $session) {
            $interval = time() - $session->enquiredAt();
            // Check enquire interval
            if ($interval > 'ENQUIRE_INTERVAL') {
                $session->sendPDU('ENQUIRE_LINK', 'ENQUIRE_TIMEOUT');//TODO check if sent not failed
                $session->setEnquireAt(time());
            }
            // Check pdu timeout
            if ($interval > 'ENQUIRE_TIMEOUT') {
                $this->sessions->detach($session->close());
            }
        }
    }

    public function stop(): void
    {
        $this->logger->log(LogLevel::INFO, 'Stopping server ...');
        foreach ($this->clients as $key => $client) {
            $client->close();
            unset($this->clients[$key]);
        }

        if ($this->master) {
            $this->master->close();
            $this->master = null;
        }
        $this->logger->log(LogLevel::INFO, 'Stopping server OK');
    }
}
