<?php

namespace App\Module\Core\Domain;

use Snidget\Kernel\AttributeLoader;
use Snidget\Kernel\Reflection;
use Snidget\Kernel\SnidgetException;

class Duck
{
    public function __construct(
        protected array $schemaPaths
    ) {
    }

    // TODO: handle nested schema
    // TODO: duck use for correct build DTO and save to container
    protected function quack(array $data): ?string
    {
        $hash = $this->hash(array_keys($data));
        $schemas = [];
        foreach (psrIterator($this->schemaPaths) as $className) {
            $props = new Reflection($className)->getPublicProperties();
            $typeHash = $this->hash(array_map(fn($x) => $x->getName(), $props));
            if (isset($schemas[$typeHash])) {
                throw new SnidgetException("Совпадающие схемы: $className - " . $schemas[$typeHash]);
            }

            $schemas[$typeHash] = $className;
        }

        return $schemas[$hash] ?? null;
    }

    /** @return iterable<string, string[]> только поля с ошибками */
    public function layAnEgg(array $data): iterable
    {
        $type = $this->quack($data);
        if ($type === null) {
            yield 'payload' => ['не найдено схемы с полями ' . implode(', ', array_keys($data))];
            return;
        }

        foreach (AttributeLoader::getAssertions($type) as $name => $validator) {
            $field = explode('::', $name)[1];
            $errors = $validator->check($data[$field]);
            if ($errors !== []) {
                yield $name => $errors;
            }
        }
    }

    // порядок ключей в JSON не важен
    protected function hash(array $keys): string
    {
        sort($keys);
        return serialize($keys);
    }
}
