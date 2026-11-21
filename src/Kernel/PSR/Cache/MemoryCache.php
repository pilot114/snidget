<?php

namespace Snidget\Kernel\PSR\Cache;

use Psr\SimpleCache\CacheInterface;

class MemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    protected array $values = [];

    /** @var array<string, int> key => expiry unix time */
    protected array $timers = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $this->checkCacheKey($key);
        $this->deleteIfExpired($key);
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->checkCacheKey($key);
        if ($ttl instanceof \DateInterval) {
            $now = new \DateTimeImmutable();
            $ttl = $now->add($ttl)->getTimestamp() - time();
        }

        $this->values[$key] = $value;
        unset($this->timers[$key]);
        if ($ttl !== null) {
            $this->timers[$key] = time() + $ttl;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        $this->checkCacheKey($key);
        if (!array_key_exists($key, $this->values)) {
            return false;
        }

        unset($this->values[$key]);
        unset($this->timers[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->timers = [];
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($this->validKeys($keys) as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<mixed, mixed> $values
     * @throws InvalidCacheKeyException
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        // check all keys first, so a bad key writes nothing
        $pairs = [];
        foreach ($values as $key => $value) {
            $pairs[] = [$this->validKey($key), $value];
        }

        $results = array_map(fn(array $pair): bool => $this->set($pair[0], $pair[1], $ttl), $pairs);
        return in_array(true, $results, true);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $results = array_map($this->delete(...), $this->validKeys($keys));
        return in_array(true, $results, true);
    }

    public function has(string $key): bool
    {
        $this->deleteIfExpired($key);
        return array_key_exists($key, $this->values);
    }

    protected function deleteIfExpired(string $key): void
    {
        $this->checkCacheKey($key);
        if (isset($this->timers[$key]) && time() >= $this->timers[$key]) {
            $this->delete($key);
        }
    }

    /**
     * @param iterable<mixed> $keys
     * @return list<string>
     * @throws InvalidCacheKeyException
     */
    protected function validKeys(iterable $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $this->validKey($key);
        }

        return $result;
    }

    /**
     * PHP casts numeric string keys to int, cast them back
     *
     * @throws InvalidCacheKeyException
     */
    protected function validKey(mixed $key): string
    {
        if (is_int($key)) {
            $key = (string)$key;
        }

        if (!is_string($key)) {
            throw new InvalidCacheKeyException('Ключ кэша должен быть строкой, получен ' . get_debug_type($key));
        }

        $this->checkCacheKey($key);
        return $key;
    }

    protected function checkCacheKey(string $key): void
    {
        if (preg_match('#^[0-9a-zA-Z_:]+$#', $key) === 0 || strlen($key) > 255) {
            throw new InvalidCacheKeyException("Невалидный ключ: $key");
        }
    }
}
