<?php

namespace Snidget;

use Psr\SimpleCache\CacheInterface;

class InMemoryCache implements CacheInterface
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
        if ($ttl instanceof \DateInterval) {
            $now = new \DateTimeImmutable();
            $ttl = $now->add($ttl)->getTimestamp() - $now->getTimestamp();
        }
        $this->values[$key] = $value;
        $this->timers[$key] = time() . ':' . $ttl;
        return true;
    }

    public function delete(string $key): bool
    {
        if (!isset($this->values[$key])) {
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
        return array_map(fn($key) => $this->get($key, $default), (array)$keys);
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        $this->deleteIfExpired($key);
        return isset($this->values[$key]);
    }

    protected function deleteIfExpired(string $key): bool
    {
        if (!isset($this->timers[$key])) {
            return true;
        }
        [$startTs, $duration] = explode(':', $this->timers[$key]);
        $isExpired = (time() - (int)$startTs) > (int)$duration;
        return $isExpired && $this->delete($key);
    }
}