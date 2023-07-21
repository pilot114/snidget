<?php

namespace App\Module\Core\Domain;

use Snidget\Kernel\AttributeLoader;
use Snidget\Kernel\Reflection;
use Snidget\Kernel\SnidgetException;

class Duck
{
    protected static array $cache = [];

    public function __construct(
        protected array $schemaPaths
    ) {
    }

    // TODO: handle nested schema
    // TODO: duck use for correct build DTO and save to container
    protected function quack(array $data): string
    {
        $hash = serialize(array_keys($data));
        foreach (psrIterator($this->schemaPaths) as $className) {
            $props = (new Reflection($className))->getPublicProperties();
            $typeHash = serialize(array_map(fn($x) => $x->getName(), $props));
            if (isset(self::$cache[$typeHash])) {
                throw new SnidgetException("Совпадающие схемы: $className - " . self::$cache[$typeHash]);
            }
            self::$cache[$typeHash] = $className;
            if ($typeHash === $hash) {
                return $className;
            }
        }
        throw new SnidgetException("Не найдено схемы, соответствующее хешу $hash");
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
