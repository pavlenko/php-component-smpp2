<?php

namespace PE\SMPP\Util;

final class StreamException extends \RuntimeException
{
    public static function try(\Closure $closure)
    {
        set_error_handler(function ($code, $message) use (&$error) {
            throw new StreamException($message, $code);
        });
        $result = $closure();
        restore_error_handler();
        return $result;
    }
}
