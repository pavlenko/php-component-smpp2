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

    public function __construct($value = null)
    {
        $this->value = (int) $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

class StringT implements Type
{
    public string $value;

    public function __construct($value = null)
    {
        $this->value = (string) $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

class Params74
{
    public Uint08T $a;
    public StringT $b;

    public function __construct()
    {
        $this->a = new Uint08T(2);
        $this->b = new StringT('s');
    }
}

//TODO maybe change min version to php 8.0
class Params80
{
    public function __construct(
        public Uint08T $a = new Uint08T(),
        public StringT $b = new StringT(),
    ) {

    }

    /**
     * @template T
     *
     * @param string $key
     * @param T|class-string $type
     * @return T
     */
    public function get(string $key, string $type)
    {
        return new $type($this->{$key} ?? null);
    }

    /**
     * @template T
     *
     * @param string $key
     * @param T|class-string $type
     * @param $value
     */
    public function set(string $key, string $type, $value)
    {}
}

$params = new Params80();
$params->set('b', StringT::class, 'b');
dump($params->get('a', Uint08T::class)->value, $params->get('b', Uint08T::class)->value);
