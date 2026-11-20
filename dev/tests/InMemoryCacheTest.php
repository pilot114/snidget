<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Snidget\Kernel\PSR\Cache\InvalidCacheKeyException;
use Snidget\Kernel\PSR\Cache\MemoryCache;

class InMemoryCacheTest extends TestCase
{
    protected CacheInterface $cache;

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
    }

    /** @return Generator<string, array{mixed}> */
    public static function values(): Generator
    {
        yield 'null'   => [null];
        yield 'true'   => [true];
        yield 'false'  => [false];
        yield 'zero'   => [0];
        yield '_int'   => [-1];
        yield 'int'    => [1];
        yield '_float' => [-1.1];
        yield 'float'  => [1.1];
        yield 'empty'  => [''];
        yield 'string' => ['Lorem Ipsum'];
        yield 'array1' => [[]];
        yield 'array2' => [[1,2,3]];
        yield 'array3' => [[[1,2,3], [1,2,3], [1,2,3]]];
        yield 'object' => [new stdClass()];
    }

    /** @return Generator<string, array{string, string, DateInterval|int}> */
    public static function expiredValues(): Generator
    {
        yield 'interval' => ['value', 'key', new DateInterval('PT1S')];
        yield 'seconds'  => ['value', 'key', 1];
    }

    /** @return Generator<string, array{string}> */
    public static function invalidKeys(): Generator
    {
        yield 'empty'    => [''];
        yield 'symbol'   => ['-'];
        yield 'noLatin'  => ['кириллица'];
        yield 'overflow' => [str_repeat('x', 256)];
    }

    /** @return Generator<array{Closure(CacheInterface): mixed}> */
    public static function methodsWithInvalidKeys(): Generator
    {
        $keys = array_column(iterator_to_array(self::invalidKeys()), 0);

        foreach ($keys as $key) {
            yield [fn(CacheInterface $c): mixed => $c->get($key)];
            yield [fn(CacheInterface $c): bool => $c->set($key, 0)];
            yield [fn(CacheInterface $c): bool => $c->delete($key)];
            yield [fn(CacheInterface $c): bool => $c->has($key)];
        }

        yield [fn(CacheInterface $c): iterable => $c->getMultiple($keys)];
        yield [fn(CacheInterface $c): bool => $c->setMultiple(array_combine($keys, $keys))];
        yield [fn(CacheInterface $c): bool => $c->deleteMultiple($keys)];
    }

    #[DataProvider('values')]
    public function testBase(mixed $value): void
    {
        $key = 'testKeyValue';
        self::assertFalse($this->cache->has($key), 'Не должно быть значения в кеше');
        self::assertTrue($this->cache->set($key, $value), 'Запись в кэш должна быть успешна');
        self::assertTrue($this->cache->has($key), 'Должно быть значение в кеше');
        self::assertTrue($this->cache->set($key, 'new'), 'Повторная запись должна перезаписать значение');
        self::assertEquals('new', $this->cache->get($key));
        $this->cache->set($key, $value);
        self::assertEquals($value, $this->cache->get($key), 'Полученое значение должно быть равно записанному');
        self::assertTrue($this->cache->delete($key), 'Должно быть упешное удаление');
        self::assertFalse($this->cache->delete($key), 'Должно быть неупешное удаление');
        self::assertFalse($this->cache->has($key), 'Не должно быть значения в кеше');
    }

    public function testMultiple(): void
    {
        $values = iterator_to_array(self::values());
        $values = array_map(fn(array $x): mixed => $x[0], $values);

        $keys = array_keys($values);

        foreach ($keys as $k) {
            self::assertFalse($this->cache->has($k));
        }

        self::assertTrue($this->cache->setMultiple($values), 'Запись в кэш должна быть успешна');
        foreach ($keys as $k) {
            self::assertTrue($this->cache->has($k));
        }

        self::assertTrue($this->cache->setMultiple($values), 'Повторная запись должна перезаписать значения');
        self::assertEquals(
            $values,
            $this->cache->getMultiple($keys),
            'Полученое значение должно быть равно записанному'
        );
        self::assertTrue($this->cache->deleteMultiple($keys), 'Должно быть упешное удаление');
        foreach ($keys as $k) {
            self::assertFalse($this->cache->has($k));
        }

        self::assertFalse($this->cache->deleteMultiple($keys), 'Должно быть неупешное удаление');

        // удаление через clear
        self::assertTrue($this->cache->setMultiple($values), 'Запись в кэш должна быть успешна');
        foreach ($keys as $k) {
            self::assertTrue($this->cache->has($k));
        }

        self::assertTrue($this->cache->clear(), 'Очистка кэша должна быть успешна');
        foreach ($keys as $k) {
            self::assertFalse($this->cache->has($k));
        }
    }

    #[DataProvider('expiredValues')]
    public function testExpiredValues(mixed $value, string $key, int|DateInterval $ttl): void
    {
        self::assertFalse($this->cache->has($key), 'Не должно быть значения в кеше');
        $this->cache->set($key, $value, $ttl);
        self::assertTrue($this->cache->has($key), 'Должно быть значения в кеше');
        sleep($ttl instanceof DateInterval ? $ttl->s : $ttl);
        self::assertFalse($this->cache->has($key), 'Не должно быть значения в кеше');
    }

    /**
     * @param Closure(CacheInterface): mixed $call
     */
    #[DataProvider('methodsWithInvalidKeys')]
    public function testInvalidCacheKey(Closure $call): void
    {
        $this->expectException(InvalidCacheKeyException::class);
        $call($this->cache);
    }
}
