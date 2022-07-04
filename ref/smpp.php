<?php

/*

File		:	smppclass.php
Implements	:	SMPPClass()
Description	:	This class can send messages via the SMPP protocol. Also supports unicode and multi-part messages.
License		:	GNU Lesser Genercal Public License: http://www.gnu.org/licenses/lgpl.html
Commercial advertisement: Contact info@chimit.nl for SMS connectivity and more elaborate SMPP libraries in PHP and other languages.

*/

/*

The following are the SMPP PDU types that we are using in this class.
Apart from the following 5 PDU types, there are a lot of SMPP directives
that are not implemented in this version.

*/

define('CM_BIND_RECEIVER', 0x00000001);
define('CM_BIND_TRANSMITTER', 0x00000002);
define('CM_BIND_TRANSCEIVER', 0x00000009);

define('CM_SUBMIT_SM', 0x00000004);
define('CM_SUBMIT_MULTI', 0x00000021);

define('CM_ENQUIRELINK', 0x00000015);
define('CM_QUERY_SM',0x00000003);

define('CM_DELIVER_SM',0x00000005);

define('CM_UNBIND', 0x00000006);

define('CT_TRANSMITTER', 2);
define('CT_TRANSCEIVER', 9);
define('CT_RECEIVER', 1);

class SMPPClass {


	private  $_sended_sms = array();

	private $_delivered_sms = array();
// public members:
	/*
	Constructor.
	Parameters:
		none.
	Example:
		$smpp = new SMPPClass();
	*/
	function __construct()
	{
		/* seed random generator */
		list($usec, $sec) = explode(' ', microtime());
		$seed = (float) $sec + ((float) $usec * 100000);
		srand($seed);

		/* initialize member variables */
		$this->_debug = false; /* set this to false if you want to suppress debug output. */
		$this->first_output = true;
		$this->_socket = NULL;
		$this->_command_status = 0;
		$this->_sequence_number = 1;
		$this->_source_address = "";
		$this->_message_sequence = rand(1,255);

		$this -> _sended_sms = array();

	}

	function getSendedSms() {

		return $this -> _sended_sms;

	}

	function getStatusesSms() {

		return $this -> _delivered_sms;

	}

	/*
	For SMS gateways that support sender-ID branding, the method
	can be used to set the originating address.
	Parameters:
		$from	:	Originating address
	Example:
		$smpp->SetSender("31495595392");
	*/
	function SetSender($from)
	{
		if (strlen($from) > 20) {
			$this->debug("Error: sender id too long.<br/>");
			return;
		}
		$this->_source_address = $from;
	}

	/*
	This method initiates an SMPP session.
	It is to be called BEFORE using the Send() method.
	Parameters:
		$host		: SMPP ip to connect to.
		$port		: port # to connect to.
		$username	: SMPP system ID
		$password	: SMPP passord.
		$system_type	: SMPP System type
	Returns:
		true if successful, otherwise false
	Example:
		$smpp->Start("smpp.chimit.nl", 2345, "chimit", "my_password", "client01");
	*/
	function Start($connection_type, $host, $port, $username, $password, $system_type)
	{

		$this -> _sended_sms = array();

		$this->_socket = fsockopen($host, $port, $errno, $errstr, 20);
		// todo: sanity check on input parameters
		if (!$this->_socket) {
			$this->debug("Error opening SMPP session.<br/>");
			$this->debug("Error was: $errstr.<br/>");
			return;
		}
		socket_set_timeout($this->_socket, 0, 1000000);

		$status = $this->SendBind($connection_type, $username, $password, $system_type);

		if ($status != 0) {
			$this->debug("Error binding to SMPP server. Invalid credentials?<br/>");
		}

		return ($status == 0);

	}


	/*
	This method sends out one SMS message.
	Parameters:
		$to	: destination address.
		$text	: text of message to send.
		$data_coding: int
	Returns:
		true if messages sent successfull, otherwise false.
	Example:
		$smpp->Send("31649072766", "This is an SMPP Test message.");
		$smpp->Send("31648072766", "&#1589;&#1576;&#1575;&#1581;&#1575;&#1604;&#1582;&#1610;&#1585;", true);
	*/
	function Send($id, $to, $text, $data_coding = 244)
	{
		if (strlen($to) > 20) {
			$this->debug("to-address too long.<br/>");
			return;
		}
		if (!$this->_socket) {
			$this->debug("Not connected, while trying to send SUBMIT_SM.<br/>");
			// return;
		}
		$service_type = "";
		//default source TON and NPI for international sender
		$source_addr_ton = 1;
		$source_addr_npi = 1;
		$source_addr = $this->_source_address;
		if (preg_match('/\D/', $source_addr)) //alphanumeric sender
		{
			$source_addr_ton = 5;
			$source_addr_npi = 0;
		}
		elseif (strlen($source_addr) < 11) //national or shortcode sender
		{
			$source_addr_ton = 2;
			$source_addr_npi = 1;
		}
		$dest_addr_ton = 1;
		$dest_addr_npi = 1;
		$destination_addr = $to;
		$esm_class = 3;
		$protocol_id = 0;
		$priority_flag = 0;
		$schedule_delivery_time = "";
		$validity_period = "";
		$registered_delivery_flag = 0;
		$replace_if_present_flag = 0;
		$sm_default_msg_id = 0;
		if ($data_coding == 8) {
			$text = mb_convert_encoding($text, 'UCS-2BE', 'UTF-8');
			$multi = $this->split_message_unicode($text);
		}
		else {
			$multi = $this->split_message($text);
		}
		$multiple = (count($multi) > 1);
		if ($multiple) {
			$esm_class += 0x00000040;
		}
		$result = true;
		reset($multi);
		while (list(, $part) = each($multi)) {
			$short_message = $part;
			$sm_length = strlen($short_message);

			$this -> _sended_sms[$this->_sequence_number]['id'] = $id;
			$this -> _sended_sms[$this->_sequence_number]['status'] = 'error';
			$this -> _sended_sms[$this->_sequence_number]['message_id'] = '';

			$status = $this->SendSubmitSM(
                $service_type,
                $source_addr_ton,
                $source_addr_npi,
                $source_addr,
                $dest_addr_ton,
                $dest_addr_npi,
                $destination_addr,
                $esm_class,
                $protocol_id,
                $priority_flag,
                $schedule_delivery_time,
                $validity_period,
                $registered_delivery_flag,
                $replace_if_present_flag,
                $data_coding,
                $sm_default_msg_id,
                $sm_length,
                $short_message
            );
			if ($status != 0) {
				$this->debug("SMPP server returned error $status.<br/>");
				$result = false;
			}
		}

		return $result;
	}

	/*
	This method sends out one SMS in Binary mode.
	Parameters:
		$to	: destination address.
		$binary	: array()
		$udh: array()
		$data_coding: int
	Returns:
		true if messages sent successfull, otherwise false.
	Example:
		$smpp->Send("31649072766", "This is an SMPP Test message.");
	*/

	function SendBinary($to, $binary, $udh, $data_coding=4)
	{
		if (strlen($to) > 20) {
			$this->debug("to-address too long.<br/>");
			return;
		}
		if (!$this->_socket) {
			$this->debug("Not connected, while trying to send SUBMIT_SM.<br/>");
			return;
		}

		if(!is_array($binary)) {
			$this->debug("binary must be array<br/>");
			return;
		}

		if(!is_array($udh)) {
			$this->debug("udh must be array<br/>");
			return;
		}

		$udh_packed = '';
		$binary_packed = '';

		foreach($udh as $u) {

			if(!is_numeric($u)) {
				$this->debug("udh must be array of integers<br/>");
				return;
			}

			$udh_packed .= pack("c", $u);
		}

		foreach($binary as $b) {

			if(!is_numeric($b)) {
				$this->debug("binary must be array of integers<br/>");
				return;
			}

			$binary_packed .= pack("c", $b);
		}

		$this->debug("Split: UDH = ");
		for ($i = 0; $i < strlen($udh_packed); $i++) {
				$this->debug(ord($udh_packed[$i]) . " ");
		}
		$this->debug("<br/>");

		$this->debug("Split: Binary = ");
		for ($i = 0; $i < strlen($binary_packed); $i++) {
				$this->debug(ord($binary_packed[$i]) . " ");
		}
		$this->debug("<br/>");


		$service_type = "";
		//default source TON and NPI for international sender
		$source_addr_ton = 1;
		$source_addr_npi = 1;
		$source_addr = $this->_source_address;

		if (preg_match('/\D/', $source_addr)) //alphanumeric sender
		{
			$source_addr_ton = 5;
			$source_addr_npi = 0;
		}
		elseif (strlen($source_addr) < 11) //national or shortcode sender
		{
			$source_addr_ton = 2;
			$source_addr_npi = 1;
		}

		$dest_addr_ton = 1;
		$dest_addr_npi = 1;
		$destination_addr = $to;
		$esm_class = 0x00000043;
		$protocol_id = 0;
		$priority_flag = 0;
		$schedule_delivery_time = "";
		$validity_period = "";
		$registered_delivery_flag = 0;
		$replace_if_present_flag = 0;
		//$data_coding = 4;
		$sm_default_msg_id = 0;

		$result = true;


		$short_message = $udh_packed . $binary_packed;
		$sm_length = strlen($short_message);
		$status = $this->SendSubmitSM(
            $service_type,
            $source_addr_ton,
            $source_addr_npi,
            $source_addr,
            $dest_addr_ton,
            $dest_addr_npi,
            $destination_addr,
            $esm_class,
            $protocol_id,
            $priority_flag,
            $schedule_delivery_time,
            $validity_period,
            $registered_delivery_flag,
            $replace_if_present_flag,
            $data_coding,
            $sm_default_msg_id,
            $sm_length,
            $short_message
        );
		if ($status != 0) {
			$this->debug("SMPP server returned error $status.<br/>");
			$result = false;
		}

		return $result;
	}


	/*
	This method ends a SMPP session.
	Parameters:
		none
	Returns:
		true if successful, otherwise false
	Example: $smpp->End();
	*/
	function End()
	{
		if (!$this->_socket) {
			// not connected
			return;
		}
		$status = $this->SendUnbind();
		if ($status != 0) {
			$this->debug("SMPP Server returned error $status.<br/>");
		}
		fclose($this->_socket);
		$this->_socket = NULL;

		$this->debug('end');
		return ($status == 0);
	}

	/*
	This method sends an enquire_link PDU to the server and waits for a response.
	Parameters:
		none
	Returns:
		true if successfull, otherwise false.
	Example: $smpp->TestLink()
	*/
	function TestLink()
	{
		$pdu = "";
		$status = $this->SendPDU(CM_ENQUIRELINK, $pdu);
		return ($status == 0);
	}

	/*
	This method sends a single message to a comma separated list of phone numbers.
	There is no limit to the number of messages to send.
	Parameters:
		$tolist		: comma seperated list of phone numbers
		$text		: text of message to send
		$data_coding: int
	Returns:
		true if messages received by smpp server, otherwise false.
	Example:
		$smpp->SendMulti("31777110204,31649072766,...,...", "This is an SMPP Test message.");
	*/
	function SendMulti($tolist, $text, $data_coding = 244)
	{
		if (!$this->_socket) {
			$this->debug("Not connected, while trying to send SUBMIT_MULTI.<br/>");
			// return;
		}
		$service_type = "";
		$source_addr = $this->_source_address;
		//default source TON and NPI for international sender
		$source_addr_ton = 1;
		$source_addr_npi = 1;
		$source_addr = $this->_source_address;
		if (preg_match('/\D/', $source_addr)) //alphanumeric sender
		{
			$source_addr_ton = 5;
			$source_addr_npi = 0;
		}
		elseif (strlen($source_addr) < 11) //national or shortcode sender
		{
			$source_addr_ton = 2;
			$source_addr_npi = 1;
		}
		$dest_addr_ton = 1;
		$dest_addr_npi = 1;
		$destination_arr = explode(",", $tolist);
		$esm_class = 3;
		$protocol_id = 0;
		$priority_flag = 0;
		$schedule_delivery_time = "";
		$validity_period = "";
		$registered_delivery_flag = 0;
		$replace_if_present_flag = 0;
		$sm_default_msg_id = 0;
		if ($data_coding == 8) {
			$text = mb_convert_encoding($text, "UCS-2BE", "HTML-ENTITIES");
			$multi = $this->split_message_unicode($text);
		} else {
			$multi = $this->split_message($text);
		}
		$multiple = (count($multi) > 1);
		if ($multiple) {
			$esm_class += 0x00000040;
		}
		$result = true;
		reset($multi);
		while (list(, $part) = each($multi)) {
			$short_message = $part;
			$sm_length = strlen($short_message);
			$status = $this->SendSubmitMulti(
                $service_type,
                $source_addr_ton,
                $source_addr_npi,
                $source_addr,
                $dest_addr_ton,
                $dest_addr_npi,
                $destination_arr,
                $esm_class,
                $protocol_id,
                $priority_flag,
                $schedule_delivery_time,
                $validity_period,
                $registered_delivery_flag,
                $replace_if_present_flag,
                $data_coding,
                $sm_default_msg_id,
                $sm_length,
                $short_message
            );
			if ($status != 0) {
				$this->debug("SMPP server returned error $status.<br/>");
				$result = false;
			}
		}
		return $result;
	}

// private members (not documented):

	function ExpectPDU($our_sequence_number = NULL)
	{
		if( !empty($our_sequence_number) )
		{
			do {
				$this->debug("Trying to read PDU.<br/>");
				if (feof($this->_socket)) {
					$this->debug("Socket was closed.!!<br/>");
				}
				$elength = fread($this->_socket, 4);
				if (empty($elength)) {
					$this->debug("Connection lost.<br/>");
					return;
				}
				extract(unpack("Nlength", $elength));
				$this->debug("Reading PDU     : $length bytes.<br/>");
				$stream = fread($this->_socket, $length - 4);
				$this->debug("Stream len      : " . strlen($stream) . "<br/>");
				extract(unpack("Ncommand_id/Ncommand_status/Nsequence_number", $stream));
				$command_id &= 0x0fffffff;
				$this->debug("Command id      : $command_id.<br/>");
				$command_status = $command_status > 0? "<span style='color:red'>$command_status</span>":$command_status;
				$this->debug("Command status  : $command_status.<br/>");
				$this->debug("sequence_number : $sequence_number.<br/>");
				$pdu = substr($stream, 12);
				switch ($command_id) {
				case CM_BIND_TRANSMITTER:
					$this->debug("Got CM_BIND_TRANSMITTER_RESP.<br/>");
					$spec = "asystem_id";
					extract($this->unpack2($spec, $pdu));
					$this->debug("system id       : $system_id.<br/>");
					break;
				case CM_BIND_TRANSCEIVER:
					$this->debug("Got CM_BIND_TRANSCEIVER_RESP.<br/>");
					$spec = "asystem_id";
					extract($this->unpack2($spec, $pdu));
					$this->debug("system id       : $system_id.<br/>");
					break;
				case CM_BIND_RECEIVER:
					$this->debug("Got CM_BIND_RECEIVER_RESP.<br/>");
					$spec = "asystem_id";
					extract($this->unpack2($spec, $pdu));
					$this->debug("system id       : $system_id.<br/>");

						return $this ->ExpectPDU();

					break;
				case CM_UNBIND:
					$this->debug("Got CM_UNBIND_RESP.<br/>");
					break;
				case CM_SUBMIT_SM:
					$this->debug("Got CM_SUBMIT_SM_RESP.<br/>");
					if ($command_status == 0) {
						$spec = "amessage_id";
						extract($this->unpack2($spec, $pdu));

						$this -> _sended_sms[$sequence_number]['message_id'] = $message_id;
						$this -> _sended_sms[$sequence_number]['status'] = 'sended';

						$this->debug("message id      : $message_id.<br/>");
					}
					break;
				case CM_SUBMIT_MULTI:
					$this->debug("Got CM_SUBMIT_MULTI_RESP.<br/>");
					$spec = "amessage_id/cno_unsuccess/";
					extract($this->unpack2($spec, $pdu));
					$this->debug("message id      : $message_id.<br/>");
					$this->debug("no_unsuccess    : $no_unsuccess.<br/>");
					break;
				case CM_DELIVER_SM:

						$spec = "atemp/ctemp/ctemp/atemp/ctemp/ctemp/atemp/ctemp" .
								"/ctemp/ctemp/atemp/atemp/ctemp/ctemp/ctemp/" .
								"ctemp/ctemp/amessage";

						extract($this->unpack2($spec,$pdu));

						$this -> _delivered_sms[] = $message;

						$this -> debug($message . '<br/>');

						return $this ->ExpectPDU();

					break;
				case CM_ENQUIRELINK:
					$this->debug("GOT CM_ENQUIRELINK_RESP.<br/>");
					break;
				case CM_QUERY_SM:
					$this->debug("GOT CM_QUERY_SM_RESP.<br/>");
					break;
				default:
					$this->debug("Got unknown SMPP pdu: <span style='color:red'>$command_id</span> <br/>");
					break;
				}
				$this->debug("Received PDU: ");
				for ($i = 0; $i < strlen($stream); $i++) {
					if (ord($stream[$i]) < 32) $this->debug("(" . ord($stream[$i]) . ")"); else $this->debug($stream[$i]);
				}
				$this->debug("<br/><br/>");
			} while ($sequence_number != $our_sequence_number);
		} else {

			$command_status = 0;

			for($i=0;$i<5;$i++) {

				$this->debug("Trying to read PDU.<br/>");
				if (feof($this->_socket)) {
					$this->debug("Socket was closed.!!<br/>");
				}
				$elength = fread($this->_socket, 4);
				if (empty($elength)) {
					$this->debug("Connection lost.<br/>");
					continue;
				}
				extract(unpack("Nlength", $elength));
				$this->debug("Reading PDU     : $length bytes.<br/>");
				$stream = fread($this->_socket, $length - 4);
				$this->debug("Stream len      : " . strlen($stream) . "<br/>");
				extract(unpack("Ncommand_id/Ncommand_status/Nsequence_number", $stream));
				$command_id &= 0x0fffffff;
				$this->debug("Command id      : $command_id.<br/>");
				$command_status = $command_status > 0? "<span style='color:red'>$command_status</span>":$command_status;
				$this->debug("Command status  : $command_status.<br/>");
				$this->debug("sequence_number : $sequence_number.<br/>");
				$pdu = substr($stream, 12);
				switch ($command_id) {
				case CM_BIND_TRANSMITTER:
					$this->debug("Got CM_BIND_TRANSMITTER_RESP.<br/>");
					$spec = "asystem_id";
					extract($this->unpack2($spec, $pdu));
					$this->debug("system id       : $system_id.<br/>");
					break;
				case CM_BIND_TRANSCEIVER:
					$this->debug("Got CM_BIND_TRANSCEIVER_RESP.<br/>");
					$spec = "asystem_id";
					extract($this->unpack2($spec, $pdu));
					$this->debug("system id       : $system_id.<br/>");
					break;
				case CM_BIND_RECEIVER:
					$this->debug("Got CM_BIND_RECEIVER_RESP.<br/>");
					$spec = "asystem_id";
					extract($this->unpack2($spec, $pdu));
					$this->debug("system id       : $system_id.<br/>");
					break;
				case CM_UNBIND:
					$this->debug("Got CM_UNBIND_RESP.<br/>");
					break;
				case CM_SUBMIT_SM:
					$this->debug("Got CM_SUBMIT_SM_RESP.<br/>");
					if ($command_status == 0) {
						$spec = "amessage_id";
						extract($this->unpack2($spec, $pdu));
						$this->debug("message id      : $message_id.<br/>");
					}
					break;
				case CM_SUBMIT_MULTI:
					$this->debug("Got CM_SUBMIT_MULTI_RESP.<br/>");
					$spec = "amessage_id/cno_unsuccess/";
					extract($this->unpack2($spec, $pdu));
					$this->debug("message id      : $message_id.<br/>");
					$this->debug("no_unsuccess    : $no_unsuccess.<br/>");
					break;
				case CM_DELIVER_SM:

					$spec = "atemp/ctemp/ctemp/atemp/ctemp/ctemp/atemp/ctemp" .
							"/ctemp/ctemp/atemp/atemp/ctemp/ctemp/ctemp/" .
							"ctemp/ctemp/amessage";

					extract($this->unpack2($spec,$pdu));

					$this -> _delivered_sms[] = $message;

					$this -> debug($message . '<br/>');

					return $this -> ExpectPDU();

					break;
				case CM_ENQUIRELINK:
					$this->debug("GOT CM_ENQUIRELINK_RESP.<br/>");
					break;
				case CM_QUERY_SM:
					$this->debug("GOT CM_QUERY_SM_RESP.<br/>");
					break;
				default:
					$this->debug("Got unknown SMPP pdu: <span style='color:red'>$command_id</span> <br/>");
					break;
				}
				$this->debug("Received PDU: ");
				for ($i = 0; $i < strlen($stream); $i++) {
					if (ord($stream[$i]) < 32) $this->debug("(" . ord($stream[$i]) . ")"); else $this->debug($stream[$i]);
				}
				$this->debug("<br/><br/>");

			}

		}
		return $command_status;
	}

	function SendPDU($command_id, $pdu)
	{
		$length = strlen($pdu) + 16;
		$header = pack("NNNN", $length, $command_id, $this->_command_status, $this->_sequence_number);
		$this->debug("Sending PDU: " . $pdu);
		$this->debug("Sending PDU, len == $length<br/>");
		$this->debug("Sending PDU, header-len == " . strlen($header) .  "<br/>");
		$this->debug("Sending PDU, command_id == " . $command_id  .  "<br/>");
		fwrite($this->_socket, $header . $pdu, $length);
		$status = $this->ExpectPDU($this->_sequence_number);
		$this->_sequence_number = $this->_sequence_number + 1;
		return $status;
	}

	function SendBind($connection_type, $system_id, $smpppassword, $system_type)
	{
		$system_id = $system_id . chr(0);
		$system_id_len = strlen($system_id);
		$smpppassword = $smpppassword . chr(0);
		$smpppassword_len = strlen($smpppassword);
		$system_type = $system_type . chr(0);
		$system_type_len = strlen($system_type);
		$pdu = pack("a{$system_id_len}a{$smpppassword_len}aCCCa1", $system_id, $smpppassword, chr(0), 0x34, 0, 0, chr(0));
		$this->debug("Bind Transmitter PDU: ");
		for ($i = 0; $i < strlen($pdu); $i++) {
			$this->debug(ord($pdu[$i]) . " ");
		}
		$this->debug("<br/>");
		$status = $this->SendPDU($connection_type, $pdu);
		return $status;
	}

	function SendUnbind()
	{
		$pdu = "";
		$status = $this->SendPDU(CM_UNBIND, $pdu);
		return $status;
	}

	function SendSubmitSM(
        $service_type,
        $source_addr_ton,
        $source_addr_npi,
        $source_addr,
        $dest_addr_ton,
        $dest_addr_npi,
        $destination_addr,
        $esm_class,
        $protocol_id,
        $priority_flag,
        $schedule_delivery_time,
        $validity_period,
        $registered_delivery_flag,
        $replace_if_present_flag,
        $data_coding,
        $sm_default_msg_id,
        $sm_length,
        $short_message
    ) {

		// Если $data_coding = 1111xxxx, то это GSM 03.38
		if((240 & $data_coding) == 240)
		{
			$short_message = $this->convert_to_7bit($short_message);
		}



		$service_type = $service_type . chr(0);
		$service_type_len = strlen($service_type);
		$source_addr = $source_addr . chr(0);
		$source_addr_len = strlen($source_addr);
		$destination_addr = $destination_addr . chr(0);
		$destination_addr_len = strlen($destination_addr);
		$schedule_delivery_time = $schedule_delivery_time . chr(0);
		$schedule_delivery_time_len = strlen($schedule_delivery_time);
		$validity_period = $validity_period . chr(0);
		$validity_period_len = strlen($validity_period);
		// $short_message = $short_message . chr(0);
		$message_len = $sm_length;
		$spec = "a{$service_type_len}cca{$source_addr_len}cca{$destination_addr_len}ccca{$schedule_delivery_time_len}a{$validity_period_len}ccccca{$message_len}";
		$this->debug("Message text: $short_message.<br/>");
		$this->debug("PDU spec: $spec.<br/>");

		// 8 - код кодировки UCS2 согласно спецификации SMPP 3.4
		if ($data_coding != 8) {
			$short_message = str_replace('?',chr(12),$short_message);
		}

		$pdu = pack($spec,
			$service_type,
			$source_addr_ton,
			$source_addr_npi,
			$source_addr,
			$dest_addr_ton,
			$dest_addr_npi,
			$destination_addr,
			$esm_class,
			$protocol_id,
			$priority_flag,
			$schedule_delivery_time,
			$validity_period,
			$registered_delivery_flag,
			$replace_if_present_flag,
			$data_coding,
			$sm_default_msg_id,
			$sm_length,
			$short_message);
		$status = $this->SendPDU(CM_SUBMIT_SM, $pdu);
		return $status;
	}

	function SendQuerySM($message_id,  $source_addr_ton, $source_addr_npi, $source_addr) {

		$message_id .= chr(0);
		$message_id_len = strlen($message_id);

		$source_addr .= chr(0);
		$source_addr_len = strlen($source_addr);

		$spec = "a{$message_id_len}cca{$source_addr_len}";

		$pdu = pack($spec,$message_id,$source_addr_ton,$source_addr_npi,$source_addr);

		$status = $this->SendPDU(CM_QUERY_SM, $pdu);

		return $status;

	}

	function SendSubmitMulti(
        $service_type,
        $source_addr_ton,
        $source_addr_npi,
        $source_addr,
        $dest_addr_ton,
        $dest_addr_npi,
        $destination_arr,
        $esm_class,
        $protocol_id,
        $priority_flag,
        $schedule_delivery_time,
        $validity_period,
        $registered_delivery_flag,
        $replace_if_present_flag,
        $data_coding,
        $sm_default_msg_id,
        $sm_length,
        $short_message
    ) {
		// Если $data_coding = 1111xxxx, то это GSM 03.38
		if((240 & $data_coding) == 240)
		{
			$short_message = $this->convert_to_7bit($short_message);
		}


		$service_type = $service_type . chr(0);
		$service_type_len = strlen($service_type);
		$source_addr = $source_addr . chr(0);
		$source_addr_len = strlen($source_addr);
		$number_destinations = count($destination_arr);
		$dest_flag = 1;
		$spec = "a{$service_type_len}cca{$source_addr_len}c";
		$pdu = pack($spec,
			$service_type,
			$source_addr_ton,
			$source_addr_npi,
			$source_addr,
			$number_destinations
		);

		$dest_flag = 1;
		reset($destination_arr);
		while (list(, $destination_addr) = each($destination_arr)) {
			$destination_addr .= chr(0);
			$dest_len = strlen($destination_addr);
			$spec = "ccca{$dest_len}";
			$pdu .= pack($spec, $dest_flag, $dest_addr_ton, $dest_addr_npi, $destination_addr);
		}
		$schedule_delivery_time = $schedule_delivery_time . chr(0);
		$schedule_delivery_time_len = strlen($schedule_delivery_time);
		$validity_period = $validity_period . chr(0);
		$validity_period_len = strlen($validity_period);
		$message_len = $sm_length;
		$spec = "ccca{$schedule_delivery_time_len}a{$validity_period_len}ccccca{$message_len}";

		$pdu .= pack($spec,
			$esm_class,
			$protocol_id,
			$priority_flag,
			$schedule_delivery_time,
			$validity_period,
			$registered_delivery_flag,
			$replace_if_present_flag,
			$data_coding,
			$sm_default_msg_id,
			$sm_length,
			$short_message);

		$this->debug("<br/>Multi PDU: ");
		for ($i = 0; $i < strlen($pdu); $i++) {
			if (ord($pdu[$i]) < 32) $this->debug("."); else $this->debug($pdu[$i]);
		}
		$this->debug("<br/>");

		$status = $this->SendPDU(CM_SUBMIT_MULTI, $pdu);
		return $status;
	}

	function split_message($text)
	{
		$this->debug("In split_message.<br/>");
		$max_len = 153;
		$res = array();
		if (strlen($text) <= 160) {
			$this->debug("One message: " . strlen($text) . "<br/>");
			$res[] = $text;
			return $res;
		}
		$pos = 0;
		$msg_sequence = $this->_message_sequence++;
		$num_messages = ceil(strlen($text) / $max_len);
		$part_no = 1;
		while ($pos < strlen($text)) {
			$ttext = substr($text, $pos, $max_len);
			$pos += strlen($ttext);
			$udh = pack("cccccc", 5, 0, 3, $msg_sequence, $num_messages, $part_no);
			$part_no++;
			$res[] = $udh . $ttext;
			$this->debug("Split: UDH = ");
			for ($i = 0; $i < strlen($udh); $i++) {
				$this->debug(ord($udh[$i]) . " ");
			}
			$this->debug("<br/>");
			$this->debug("Split: $ttext.<br/>");
		}
		return $res;
	}

	function split_message_unicode($text)
	{
		$this->debug("In split_message.<br/>");
		$max_len = 134;
		$res = array();
		if (mb_strlen($text) <= 140) {
			$this->debug("One message: " . mb_strlen($text) . "<br/>");
			$res[] = $text;
			return $res;
		}
		$pos = 0;
		$msg_sequence = $this->_message_sequence++;
		$num_messages = ceil(mb_strlen($text) / $max_len);
		$part_no = 1;
		while ($pos < mb_strlen($text)) {
			$ttext = mb_substr($text, $pos, $max_len);
			$pos += mb_strlen($ttext);
			$udh = pack("cccccc", 5, 0, 3, $msg_sequence, $num_messages, $part_no);
			$part_no++;
			$res[] = $udh . $ttext;
			$this->debug("Split: UDH = ");
			for ($i = 0; $i < strlen($udh); $i++) {
				$this->debug(ord($udh[$i]) . " ");
			}
			$this->debug("<br/>");
			$this->debug("Split: $ttext.<br/>");
		}
		return $res;
	}

	function unpack2($spec, $data)
	{
		$res = array();
		$specs = explode("/", $spec);
		$pos = 0;
		reset($specs);
		while (list(, $sp) = each($specs)) {
			$subject = substr($data, $pos);
			$type = substr($sp, 0, 1);
			$var = substr($sp, 1);
			switch ($type) {
			case "N":
				$temp = unpack("Ntemp2", $subject);
				$res[$var] = $temp["temp2"];
				$pos += 4;
				break;
			case "c":
				$temp = unpack("ctemp2", $subject);
				$res[$var] = $temp["temp2"];
				$pos += 1;
				break;
			case "a":
				$pos2 = strpos($subject, chr(0)) === false? strlen($subject): strpos($subject, chr(0)) + 1;
				$temp = unpack("a{$pos2}temp2", $subject);
				$res[$var] = $temp["temp2"];
				$pos += $pos2;
				break;
			}
		}
		return $res;
	}

	function convert_to_7bit($string)
	{
		$replace_chr = array(
            '@','?','$','?','?','?','?','?','?','?',"\n",'?','?',"\r",'?','?',null,'_',null,null,null,null,null,null,
            null,null,null,'?','?','?','?','!','"','#','?','%','&',"'",'(',')','*','+',',','-','.','/','?','?','?','?',
            '?','?','?','?','?','?','?'
        );

        $replace_by = array(
            chr(0), chr(1), chr(2), chr(3), chr(4), chr(5),
            chr(6), chr(7), chr(8), chr(9), chr(10), chr(11),
            chr(12), chr(13), chr(14), chr(15), chr(16), chr(17),
            chr(18), chr(19), chr(20), chr(21), chr(22), chr(23),
            chr(24), chr(25), chr(26), chr(28), chr(29), chr(30),
            chr(31), chr(33), chr(34), chr(35), chr(36), chr(37),
            chr(38), chr(39), chr(40), chr(41), chr(42), chr(43),
            chr(44), chr(45), chr(46), chr(47), chr(91), chr(92),
            chr(93), chr(94), chr(95), chr(96), chr(123), chr(124),
            chr(125), chr(126), chr(127)
        );

		return str_replace($replace_chr,$replace_by,$string);
	}

	function debug($str)
	{

		if ($this->_debug) {

			if( $this->first_output ) {

				$this->first_output = false;
				$this->startHtml();

			}

			echo $str;

			if( $str == 'end' ) {

				$this -> stopHtml();

			}

		}

	}

	function startHtml()
	{
		$str = "<html><head>Debug messages</head><body>";
		echo $str;

	}

	function stopHtml()
	{

		echo '</body></html>';

	}



};

?>
