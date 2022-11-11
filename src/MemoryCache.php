<?php

namespace Snidget;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class MemoryCache implements CacheInterface
{
    protected array $values = [];
    protected array $timers = [];
    protected bool $useSharedMemory = false;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->deleteIfExpired($key);
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        if (key_exists($key, $this->values)) {
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
        if (!key_exists($key, $this->values)) {
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
        $keys = array_flip((array)$keys);
        foreach ($keys as $key => $v) {
            $keys[$key] = $this->get($key, $default);
        }
        return $keys;
    }

    /**
     * @param iterable<string, mixed> $values
     * @throws InvalidArgumentException
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $results = [];
        foreach ($values as $key => $value) {
            $results[] = $this->set($key, $value, $ttl);
        }
        return in_array(true, $results, true);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $results = [];
        foreach ($keys as $key) {
            $results[] = $this->delete($key);
        }
        return in_array(true, $results, true);
    }

    public function has(string $key): bool
    {
        $this->deleteIfExpired($key);
        return key_exists($key, $this->values);
    }

    protected function deleteIfExpired(string $key): void
    {
        if (!isset($this->timers[$key])) {
            return;
        }
        [$startTs, $duration] = explode(':', $this->timers[$key]);
        $duration = (int)$duration;
        $isExpired = $duration && (time() - (int)$startTs) >= $duration;
        $isExpired && $this->delete($key);
    }
}