<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Exception\MalformedPDUException;

final class Decoder
{
    public function decode(string $buffer): PDU
    {
        $pos    = 0;
        $id     = (int) $this->decodeUint32($buffer, $pos, true);
        $status = (int) $this->decodeUint32($buffer, $pos, false);
        $seqNum = (int) $this->decodeUint32($buffer, $pos, true);

        switch ($id) {
            case PDU::ID_SUBMIT_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE           => $this->decodeString($buffer, $pos, false, null, 6),
                    PDU::KEY_SRC_ADDRESS            => $this->decodeAddress($buffer, $pos, false, 21),
                    PDU::KEY_DST_ADDRESS            => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_ESM_CLASS              => $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_PROTOCOL_ID            => $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_PRIORITY_FLAG          => $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SCHEDULE_DELIVERY_TIME => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_VALIDITY_PERIOD        => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_REG_DELIVERY           => $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_REPLACE_IF_PRESENT     => $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_DATA_CODING            => $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_DEFAULT_MSG_ID      => $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_LENGTH              => $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SHORT_MESSAGE          => $this->decodeString($buffer, $pos, true, null, 254),
                ];
                break;
            case PDU::ID_SUBMIT_SM_RESP:
                $params = [PDU::KEY_MESSAGE_ID => $this->decodeString($buffer, $pos, true, null, 65)];
                break;
        }

        return new PDU($id, $status, $seqNum, $params ?? []);
    }

    private function decodeUint08(string $buffer, int &$pos, bool $required): ?int
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            $error = $message;
            if (false !== ($pos = strpos($error, '): '))) {
                $error = substr($error, $pos + 3);
            }
        });
        $value = unpack('C', $buffer, $pos);
        restore_error_handler();

        if (false === $value) {
            throw new MalformedPDUException($error);
        }

        $pos += 1;
        return $value[1];
    }

    private function decodeUint16(string $buffer, int &$pos, bool $required): ?int
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            $error = $message;
            if (false !== ($pos = strpos($error, '): '))) {
                $error = substr($error, $pos + 3);
            }
        });
        $value = unpack('n', $buffer, $pos);
        restore_error_handler();

        if (false === $value) {
            throw new MalformedPDUException($error);
        }

        $pos += 2;
        return $value[1];
    }

    private function decodeUint32(string $buffer, int &$pos, bool $required): ?int
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            $error = $message;
            if (false !== ($pos = strpos($error, '): '))) {
                $error = substr($error, $pos + 3);
            }
        });
        $value = unpack('N', $buffer, $pos);
        restore_error_handler();

        if (false === $value) {
            throw new MalformedPDUException($error);
        }

        $pos += 4;
        return $value[1];
    }

    //TODO check
    private function decodeString(string $buffer, int &$pos, bool $required, int $min = null, int $max = null): ?string
    {
        $value = '';
        while (strlen($buffer) > $pos && $buffer[$pos] !== "\0" && strlen($value) < $max) {
            $value .= $buffer[$pos++];
        }
        $pos++;//<-- skip null char

        if ($required && empty($value) || null !== $min && strlen($value) < $min) {
            //throw new MalformedPDUException('Malformed PDU');
        }

        return $value ?: null;
    }

    //TODO check
    private function decodeAddress(string $buffer, int &$pos, bool $required, int $max): ?Address
    {
        $ton   = $this->decodeUint08($buffer, $pos, false);
        $npi   = $this->decodeUint08($buffer, $pos, false);
        $value = $this->decodeString($buffer, $pos, false, null, $max);

        if ($required && empty($value)) {
            //throw new \UnexpectedValueException('Malformed PDU');
        }

        return null !== $value ? new Address((int) $ton, (int) $npi, $value) : null;
    }

    private function decodeDateTime(string $buffer, int &$pos, bool $required): ?\DateTimeInterface
    {
        //TODO check if need to parse date when no required
        $value = $this->decodeString($buffer, $pos, $required, null, 17);//use $min = null for check later
        $value = substr($value, 0, 12);
        $value = \DateTimeImmutable::createFromFormat('ymdHis', $value);

        if ($required && false === $value) {
            throw new MalformedPDUException('Malformed PDU');
        }

        return $value ?: null;
    }
}
