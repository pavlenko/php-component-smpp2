<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;

final class Decoder
{
    public function decode(string $buffer): PDU
    {
        $pos    = 0;
        $id     = $this->decodeUint32($buffer, $pos, true);
        $status = $this->decodeUint32($buffer, $pos, true);
        $seqNum = $this->decodeUint32($buffer, $pos, true);

        $pdu = new PDU($id, $status, $seqNum);

        switch ($id) {
            case PDU::ID_SUBMIT_SM:
                break;
            case PDU::ID_SUBMIT_SM_RESP:
                $pdu->set(PDU::KEY_MESSAGE_ID, $this->decodeString($buffer, $pos, true, null, 65));
                break;
        }

        return $pdu;
    }

    private function decodeUint08(string $buffer, int &$pos, bool $required): int
    {
        $value = @unpack('C', $buffer, $pos++)[1] ?: null;

        if ($required && empty($value)) {
            throw new \UnexpectedValueException('Malformed PDU');
        }

        return $value;
    }

    private function decodeUint16(string $buffer, int &$pos, bool $required): ?int
    {
        $value = @unpack('n', $buffer, $pos += 2)[1] ?: null;

        if ($required && empty($value)) {
            throw new \UnexpectedValueException('Malformed PDU');
        }

        return $value;
    }

    private function decodeUint32(string $buffer, int &$pos, bool $required): ?int
    {
        $value = @unpack('N', $buffer, $pos += 4)[1] ?: null;

        if ($required && empty($value)) {
            throw new \UnexpectedValueException('Malformed PDU');
        }

        return $value;
    }

    private function decodeString(string $buffer, int &$pos, bool $required, int $min = null, int $max = null): ?string
    {
        $value = '';
        while (strlen($buffer) > $pos && $buffer[$pos] !== "\0" && strlen($value) < $max) {
            $value .= $buffer[$pos++];
        }
        $pos++;//<-- skip null char
        if ($required && empty($value) || null !== $min && strlen($value) < $min) {
            throw new \UnexpectedValueException('Malformed PDU');
        }
        return $value;
    }
}
