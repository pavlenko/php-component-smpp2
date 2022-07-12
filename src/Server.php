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
use Psr\Log\LogLevel;

final class Server
{
    private string $address;
    private LoggerInterface $logger;
    private ?Stream $master = null;

    /**
     * @var \SplObjectStorage|Session[]
     */
    private \SplObjectStorage $sessions;

    private array $pendings = [];

    public function __construct(string $address, LoggerInterface $logger = null)
    {
        $this->address  = $address;
        $this->logger   = $logger ?: new LoggerSTDOUT();
        $this->sessions = new \SplObjectStorage();
    }

    public function init(): void
    {
        $this->logger->log($this, LogLevel::DEBUG, 'Listen to ' . $this->address);
        $this->master = Stream::createServer($this->address);
        $this->master->setBlocking(false);

        if (0 === strpos($this->address, 'tls')) {
            $this->master->setCrypto(true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        }
    }

    public function tick(): void
    {
        $this->logger->log($this, LogLevel::DEBUG, 'tick');

        $r = array_merge([$this->master], iterator_to_array($this->sessions));
        $n = [];
        Stream::select($r, $n, $n, 0.05);//<-- here always need timeout, for prevent blocking process

        if (in_array($this->master, $r)) {
            unset($r[array_search($this->master, $r)]);
            $this->acceptSession(new Session($this->master->accept(), $this->logger));
        }

        foreach ($r as $client) {
            $this->handleReceive($client);
        }

        foreach ($this->sessions as $stream) {
            //$this->handleTimeout($stream);
        }

        foreach ($this->sessions as $stream) {
            //$this->handleEnquire($stream);
        }

        foreach ($this->sessions as $stream) {
            //$this->handlePending($stream);
        }
    }

    private function acceptSession(Session $session): void
    {
        $this->logger->log($this, LogLevel::DEBUG, 'Accepted new connection from ' . $session->getPeerName());
        $this->sessions->attach($session->getStream(), $session);
    }

    private function handleReceive(Stream $stream): void
    {
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
        $this->logger->log($this, LogLevel::DEBUG, 'Process timeouts from ' . $this->sessions[$stream]->getPeerName());
        $sent = $this->sessions[$stream]->getSentPDUs();
        foreach ($sent as $packet) {
            if (time() > $packet->getExpectedTime()) {
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
            $this->sessions[$stream]->setEnquiredAt();
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
        $this->logger->log($this, LogLevel::INFO, 'Stopping server ...');
        foreach ($this->sessions as $stream) {
            $this->sessions[$stream]->close();
            $this->sessions->detach($stream);
        }

        if ($this->master) {
            $this->master->close();
            $this->master = null;
        }
        $this->logger->log($this, LogLevel::INFO, 'Stopping server OK');
    }
}
