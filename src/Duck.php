<?php

namespace Snidget;

use Snidget\Exception\SnidgetException;
use Snidget\Module\Reflection;

class Duck
{
    public function __construct(
        protected string $classPath
    ){}

    protected function quack(array $data): string
    {
        $hash = serialize(array_keys($data));
        foreach (Kernel::psrIterator($this->classPath) as $className) {
            $props = (new Reflection($className))->getProperties();
            $typeHash = serialize(array_map(fn($x) => $x->getName(),$props));
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