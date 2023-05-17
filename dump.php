<?php
// phpcs:ignoreFile

require_once __DIR__ . '/vendor/autoload.php';

//TODO create new type classes and refactor existing code to use it
interface Type
{
    //public static function decode(string $buffer, int &$pos = null): self;

    public function __toString(): string;
}

class Uint08T implements Type
{
    public int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

class StringT implements Type
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

$val = new Uint08T(3);
dump($val, (int)(string) $val/*this only one way to cast to int*/, (string) $val, (string) $val > 2);
