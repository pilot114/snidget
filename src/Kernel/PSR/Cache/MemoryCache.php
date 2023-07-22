<?php

namespace Snidget\Kernel\PSR\Cache;

use Psr\SimpleCache\CacheInterface;

class MemoryCache implements CacheInterface
{
    protected array $values = [];
    protected array $timers = [];
    protected bool $useSharedMemory = false;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->checkCacheKey($key);
        $this->deleteIfExpired($key);
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->checkCacheKey($key);
        if (array_key_exists($key, $this->values)) {
            return false;
        }
        if ($ttl instanceof \DateInterval) {
            $now = new \DateTimeImmutable();
            $ttl = $now->add($ttl)->getTimestamp() - time();
        }
        $this->values[$key] = $value;
        if ($ttl) {
            $this->timers[$key] = time() . ':' . $ttl;
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

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        array_map(fn($key) => $this->checkCacheKey($key), (array)$keys);
        $keys = array_flip((array)$keys);
        foreach (array_keys($keys) as $key) {
            $keys[$key] = $this->get($key, $default);
        }
        return $keys;
    }

    /**
     * @param iterable<string, mixed> $values
     * @throws InvalidCacheKeyException
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        array_map(fn($key) => $this->checkCacheKey($key), array_keys((array)$values));
        $results = [];
        foreach ($values as $key => $value) {
            $results[] = $this->set($key, $value, $ttl);
        }
        return in_array(true, $results, true);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        array_map(fn($key) => $this->checkCacheKey($key), (array)$keys);
        $results = [];
        foreach ($keys as $key) {
            $results[] = $this->delete($key);
        }
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
        if (!isset($this->timers[$key])) {
            return;
        }
        [$startTs, $duration] = explode(':', $this->timers[$key]);
        $duration = (int)$duration;
        $isExpired = $duration && (time() - (int)$startTs) >= $duration;
        if ($isExpired) {
            $this->delete($key);
        }
    }

    protected function checkCacheKey(string $key): void
    {
        if (preg_match('#^[0-9a-zA-Z_:]+$#', $key) === 0 || strlen($key) > 255) {
            throw new InvalidCacheKeyException("Невалидный ключ: $key");
        }
    }
}
