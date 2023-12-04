<?php

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Snidget\Kernel\PSR\Cache\InvalidCacheKeyException;
use Snidget\Kernel\PSR\Cache\MemoryCache;

class InMemoryCacheTest extends TestCase
{
    protected CacheInterface $cache;

    public function setUp(): void
    {
        $this->cache = new MemoryCache();
    }

    protected function values(): Generator
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

    protected function expiredValues(): Generator
    {
        yield 'interval' => ['value', 'key', new DateInterval('PT1S')];
        yield 'seconds'  => ['value', 'key', 1];
    }

    protected function invalidKeys(): Generator
    {
        yield 'empty'    => [''];
        yield 'symbol'   => ['-'];
        yield 'noLatin'  => ['кириллица'];
        yield 'overflow' => [str_repeat('x', 256)];
    }

    protected function methodsWithInvalidKeys(): Generator
    {
        $keys = array_column(iterator_to_array($this->invalidKeys()), 0);

        foreach ($keys as $key) {
            yield ['get', $key];
            yield ['set', $key, 0];
            yield ['delete', $key];
            yield ['has', $key];
        }
        yield ['getMultiple', $keys];
        yield ['setMultiple', array_combine($keys, $keys)];
        yield ['deleteMultiple', $keys];
    }

    /**
     * @dataProvider values
     */
    public function testBase(mixed $value): void
    {
        $key = 'testKeyValue';
        $this->assertFalse($this->cache->has($key),          'Не должно быть значения в кеше');
        $this->assertTrue($this->cache->set($key, $value),   'Запись в кэш должна быть успешна');
        $this->assertTrue($this->cache->has($key),           'Должно быть значение в кеше');
        $this->assertFalse($this->cache->set($key, $value),  'Запись в кэш должна быть неуспешна');
        $this->assertEquals($value, $this->cache->get($key), 'Полученое значение должно быть равно записанному');
        $this->assertTrue($this->cache->delete($key),        'Должно быть упешное удаление');
        $this->assertFalse($this->cache->delete($key),       'Должно быть неупешное удаление');
        $this->assertFalse($this->cache->has($key),          'Не должно быть значения в кеше');
    }

    public function testMultiple(): void
    {
        $values = iterator_to_array($this->values());
        $values = array_map(fn($x): mixed => $x[0], $values);
        $keys = array_keys($values);

        array_map(fn($k) => $this->assertFalse($this->cache->has($k)), $keys);
        $this->assertTrue($this->cache->setMultiple($values),    'Запись в кэш должна быть успешна');
        array_map(fn($k) => $this->assertTrue($this->cache->has($k)), $keys);
        $this->assertFalse($this->cache->setMultiple($values),  'Запись в кэш должна быть неуспешна');
        $this->assertEquals(
            $values,
            $this->cache->getMultiple($keys),
            'Полученое значение должно быть равно записанному'
        );
        $this->assertTrue($this->cache->deleteMultiple($keys),  'Должно быть упешное удаление');
        array_map(fn($k) => $this->assertFalse($this->cache->has($k)), $keys);
        $this->assertFalse($this->cache->deleteMultiple($keys), 'Должно быть неупешное удаление');

        // удаление через clear
        $this->assertTrue($this->cache->setMultiple($values),  'Запись в кэш должна быть успешна');
        array_map(fn($k) => $this->assertTrue($this->cache->has($k)), $keys);
        $this->assertTrue($this->cache->clear(),               'Очистка кэша должна быть успешна');
        array_map(fn($k) => $this->assertFalse($this->cache->has($k)), $keys);
    }

    /**
     * @dataProvider expiredValues
     */
    public function testExpiredValues(mixed $value, string $key, int|DateInterval $ttl): void
    {
        $this->assertFalse($this->cache->has($key), 'Не должно быть значения в кеше');
        $this->cache->set($key, $value, $ttl);
        $this->assertTrue($this->cache->has($key), 'Должно быть значения в кеше');
        sleep($ttl instanceof DateInterval ? $ttl->s : $ttl);
        $this->assertFalse($this->cache->has($key), 'Не должно быть значения в кеше');
    }

    /**
     * @dataProvider methodsWithInvalidKeys
     */
    public function testInvalidCacheKey(string $method, mixed ...$args): void
    {
        $this->expectException(InvalidCacheKeyException::class);
        $this->cache->$method(...$args);
    }
}
