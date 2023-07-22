<?php

namespace Snidget\Kernel\PSR\Cache;

use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInterface;

class InvalidCacheKeyException extends \InvalidArgumentException implements SimpleCacheInterface
{
}
