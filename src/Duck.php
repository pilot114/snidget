<?php

namespace Snidget;

use Snidget\Exception\SnidgetException;
use Snidget\Module\Reflection;

class Duck
{
    static protected array $cache = [];

    public function __construct(
        protected string $classPath
    ){}

    // TODO: handle nested DTO
    protected function quack(array $data): string
    {
        $hash = serialize(array_keys($data));
        foreach (Kernel::psrIterator($this->classPath) as $className) {
            $props = (new Reflection($className))->getProperties();
            $typeHash = serialize(array_map(fn($x) => $x->getName(), $props));
            if (isset(self::$cache[$typeHash])) {
                throw new SnidgetException("Совпадающие DTO: $className - " . self::$cache[$typeHash]);
            }
            self::$cache[$typeHash] = $className;
            if ($typeHash === $hash) {
                return $className;
            }
        }
        throw new SnidgetException("Не найдено DTO, соответствующее хешу $hash");
    }

    /** @return iterable<string, array> */
    public function layAnEgg(array $data): iterable
    {
        $type = $this->quack($data);
        foreach (AttributeLoader::getAssertions($type) as $name => $validator) {
            $field = explode('::', $name)[1];
            yield $name => $validator->check($data[$field]);
        }
    }
}