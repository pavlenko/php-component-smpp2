<?php

namespace PE\SMPP;

use PE\SMPP\PDU\BindReceiver;
use PE\SMPP\PDU\BindReceiverResp;
use PE\SMPP\PDU\BindTransceiver;
use PE\SMPP\PDU\BindTransceiverResp;
use PE\SMPP\PDU\BindTransmitter;
use PE\SMPP\PDU\BindTransmitterResp;
use PE\SMPP\PDU\CancelSm;
use PE\SMPP\PDU\CancelSmResp;
use PE\SMPP\PDU\DeliverSm;
use PE\SMPP\PDU\DeliverSmResp;
use PE\SMPP\PDU\EnquireLink;
use PE\SMPP\PDU\EnquireLinkResp;
use PE\SMPP\PDU\GenericNack;
use PE\SMPP\PDU\PDU;
use PE\SMPP\PDU\QuerySm;
use PE\SMPP\PDU\QuerySmResp;
use PE\SMPP\PDU\ReplaceSm;
use PE\SMPP\PDU\ReplaceSmResp;
use PE\SMPP\PDU\SubmitSm;
use PE\SMPP\PDU\SubmitSmResp;
use PE\SMPP\PDU\Unbind;
use PE\SMPP\PDU\UnbindResp;
use PE\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server
{
    //TODO server events for allow communicate with other apps

    private LoggerInterface $logger;
    private ?Stream $master = null;

    /**
     * @var \SplObjectStorage|Session[]
     */
    private \SplObjectStorage $sessions;

    private array $pendings = [];

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

        if (0 === Stream::select($r, $n, $n)) {
            return;
        }

        if (in_array($this->master, $r)) {
            unset($r[array_search($this->master, $r)]);
            $this->handleConnect($this->master->accept());
        }

        foreach ($r as $client) {
            $this->handleReceive($client);
        }

        foreach ($this->sessions as $stream) {
            $this->handleTimeout($stream);
        }

        foreach ($this->sessions as $stream) {
            $this->handleEnquire($stream);
        }

        foreach ($this->sessions as $stream) {
            $this->handlePending($stream);
        }
    }

    private function handleConnect(Stream $stream): void
    {
        $this->logger->log(LogLevel::DEBUG, 'Accepted new connection');
        $this->sessions->attach($stream, new Session($stream));
    }

    private function handleReceive(Stream $stream): void
    {
        //TODO maybe add special processors per pdu request type
        $sess = $this->sessions[$stream];
        $pdu  = $sess->readPDU();

        if (null === $pdu) {
            $this->sessions[$stream]->close();
            $this->sessions->detach($stream);
            return;
        }

        switch (true) {
            case ($pdu instanceof BindReceiver):
                $this->sessions[$stream]->setSystemID($pdu->getSystemID());
                $this->sessions[$stream]->setPassword($pdu->getPassword());
                $response = new BindReceiverResp();
                break;
            case ($pdu instanceof BindTransmitter):
                $this->sessions[$stream]->setSystemID($pdu->getSystemID());
                $this->sessions[$stream]->setPassword($pdu->getPassword());
                $response = new BindTransmitterResp();
                break;
            case ($pdu instanceof BindTransceiver):
                $this->sessions[$stream]->setSystemID($pdu->getSystemID());
                $this->sessions[$stream]->setPassword($pdu->getPassword());
                $response = new BindTransceiverResp();
                break;
            case ($pdu instanceof Unbind):
                $response = new UnbindResp();//TODO <-- disconnect client
                break;
            case ($pdu instanceof SubmitSm):
                $response = new SubmitSmResp();
                $response->setMessageID(uniqid('', true));
                break;
            case ($pdu instanceof DeliverSm)://TODO <-- this probably need processing on client side
                $response = new DeliverSmResp();
                $response->setMessageID(uniqid('', true));
                break;
            case ($pdu instanceof QuerySm):
                $response = new QuerySmResp();
                break;
            case ($pdu instanceof CancelSm):
                $response = new CancelSmResp();
                break;
            case ($pdu instanceof ReplaceSm)://TODO <-- add fields by spec
                $response = new ReplaceSmResp();
                break;
            case ($pdu instanceof EnquireLink):
                $response = new EnquireLinkResp();
                break;
            default:
                $response = new GenericNack();
        }

        $response->setCommandStatus(CommandStatus::NO_ERROR);
        $response->setSequenceNum($pdu->getSequenceNum());

        $sess->sendPDU($response, null, Session::TIMEOUT_RESPONSE);
    }

    private function handleTimeout(Stream $stream): void
    {
        $sent = $this->sessions[$stream]->getSentPDUs();
        foreach ($sent as $packet) {
            if (time() > $packet->getExpectedTill()) {
                $this->sessions[$stream]->close();
                $this->sessions->detach($stream);
                return;
            }
        }
    }

    private function handleEnquire(Stream $stream): void
    {
        if (time() - Session::TIMEOUT_ENQUIRE > $this->sessions[$stream]->getEnquiredAt()) {
            $this->sessions[$stream]->sendPDU(new EnquireLink(), PDU::ENQUIRE_LINK_RESP, Session::TIMEOUT_RESPONSE);
        }
    }

    private function handlePending(Stream $stream): void
    {
        $toDelete = [];
        foreach ($this->pendings as $key => [$systemID, $pdu, $expectedResp, $timeout]) {
            if ($this->sessions[$stream]->getSystemID() !== $systemID) {
                continue;
            }
            $this->sessions[$stream]->sendPDU($pdu, $expectedResp, $timeout);
            $toDelete[] = $key;
        }
        foreach ($toDelete as $key) {
            unset($this->pendings[$key]);
        }
    }

    public function stop(): void
    {
        $this->logger->log(LogLevel::INFO, 'Stopping server ...');
        foreach ($this->sessions as $stream) {
            $this->sessions[$stream]->close();
            $this->sessions->detach($stream);
        }

        if ($this->master) {
            $this->master->close();
            $this->master = null;
        }
        $this->logger->log(LogLevel::INFO, 'Stopping server OK');
    }
}
