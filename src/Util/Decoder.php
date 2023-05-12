<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Exception\InvalidPDUException;
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
            case PDU::ID_BIND_RECEIVER:
            case PDU::ID_BIND_TRANSMITTER:
            case PDU::ID_BIND_TRANSCEIVER:
                $params = [
                    PDU::KEY_SYSTEM_ID         => $this->decodeString($buffer, $pos, true, 16),
                    PDU::KEY_PASSWORD          => $this->decodeString($buffer, $pos, false, 9),
                    PDU::KEY_SYSTEM_TYPE       => $this->decodeString($buffer, $pos, false, 13),
                    PDU::KEY_INTERFACE_VERSION => $this->decodeUint08($buffer, $pos, true),
                    PDU::KEY_ADDRESS           => $this->decodeAddress($buffer, $pos, false, 41),
                ];
                break;
            case PDU::ID_SUBMIT_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE           => $this->decodeString($buffer, $pos, false, 6),
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
                    PDU::KEY_SHORT_MESSAGE          => $this->decodeString($buffer, $pos, true, 254),
                ];
                break;
            case PDU::ID_SUBMIT_SM_RESP:
                $params = [PDU::KEY_MESSAGE_ID => $this->decodeString($buffer, $pos, true, 65)];
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

        if ($required && 0 === $value[1]) {
            $error = sprintf('Required UINT08 value at position %d in "%s"', $pos, $this->toPrintable($buffer));
            throw new InvalidPDUException($error);
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

        if ($required && 0 === $value[1]) {
            $error = sprintf('Required UINT16 value at position %d in "%s"', $pos, $this->toPrintable($buffer));
            throw new InvalidPDUException($error);
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

        if ($required && 0 === $value[1]) {
            $error = sprintf('Required UINT32 value at position %d in "%s"', $pos, $this->toPrintable($buffer));
            throw new InvalidPDUException($error);
        }

        $pos += 4;
        return $value[1];
    }

    private function decodeString(string $buffer, int &$pos, bool $required, int $max = null): ?string
    {
        $error = sprintf('Required STRING value at position %d in "%s"', $pos, $this->toPrintable($buffer));
        $value = '';

        while (strlen($buffer) > $pos && $buffer[$pos] !== "\0" && strlen($value) < $max) {
            $value .= $buffer[$pos++];
        }
        $pos++;//<-- skip null char

        if ($required && '' === $value) {
            throw new InvalidPDUException($error);
        }

        return $value ?: null;
    }

    private function decodeAddress(string $buffer, int &$pos, bool $required, int $max): ?Address
    {
        $error = sprintf('Required ADDRESS value at position %d in "%s"', $pos, $this->toPrintable($buffer));
        $ton   = $this->decodeUint08($buffer, $pos, false);
        $npi   = $this->decodeUint08($buffer, $pos, false);
        $value = $this->decodeString($buffer, $pos, false, $max);

        if ($required && null === $value) {
            throw new InvalidPDUException($error);
        }

        return null !== $value ? new Address((int) $ton, (int) $npi, $value) : null;
    }

    private function decodeDateTime(string $buffer, int &$pos, bool $required): ?\DateTimeInterface
    {
        $error = sprintf('Malformed DATETIME _PARAM_ at position %d in "%s"', $pos, $this->toPrintable($buffer));
        $value = $this->decodeString($buffer, $pos, false, 17);

        if (null !== $value) {
            if (strlen($value) < 17) {
                throw new MalformedPDUException(str_replace('_PARAM_', 'invalid length', $error));
            }

            $value = substr($value, 0, 12);
            $value = \DateTimeImmutable::createFromFormat('ymdHis', $value);

            if (false === $value) {
                throw new MalformedPDUException(str_replace('_PARAM_', 'invalid format', $error));
            }
        }

        if ($required && null === $value) {
            $error = sprintf('Required DATETIME value at position %d in "%s"', $pos, $this->toPrintable($buffer));
            throw new InvalidPDUException($error);
        }

        return $value;
    }

    private function toPrintable(string $value): string
    {
        return preg_replace_callback('/[\x00-\x1F\x7F]+/', function ($c) {
            $map = [
                "\t" => '\t',
                "\n" => '\n',
                "\v" => '\v',
                "\f" => '\f',
                "\r" => '\r',
            ];

            $c = $c[$i = 0];
            $s = '';
            do {
                $s .= $map[$c[$i]] ?? sprintf('\x%02X', \ord($c[$i]));
            } while (isset($c[++$i]));

            return $s;
        }, $value);
    }
}
