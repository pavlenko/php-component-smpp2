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
use PE\SMPP\PDU\QuerySm;
use PE\SMPP\PDU\QuerySmResp;
use PE\SMPP\PDU\ReplaceSm;
use PE\SMPP\PDU\ReplaceSmResp;
use PE\SMPP\PDU\SubmitSm;
use PE\SMPP\PDU\SubmitSmResp;
use PE\SMPP\PDU\Unbind;
use PE\SMPP\PDU\UnbindResp;
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
        //TODO maybe add special processors per pdu request type
        $sess = $this->sessions[$stream];
        $pdu  = $sess->readPDU();

        if (null === $pdu) {
            $this->sessions->detach($sess->close());
        }

        switch (true) {
            case ($pdu instanceof BindReceiver):
                $response = new BindReceiverResp();
                break;
            case ($pdu instanceof BindTransmitter):
                $response = new BindTransmitterResp();
                break;
            case ($pdu instanceof BindTransceiver):
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
                // SUBMIT_MULTI, DATA_SM
                $response = new GenericNack();
        }

        $response->setCommandStatus(CommandStatus::NO_ERROR);
        $response->setSequenceNumber($pdu->getSequenceNumber());

        $sess->sendPDU($response);
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
