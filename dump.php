<?php
// phpcs:ignoreFile

require_once __DIR__ . '/vendor/autoload.php';

//TODO create new type classes and refactor existing code to use it
interface Type
{
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

class Params
{
    public Uint08T $a;
    public StringT $b;

    public function __construct()
    {
        $this->a = new Uint08T(2);
        $this->b = new StringT('s');
    }
}

$params = new Params();

dump($params);
