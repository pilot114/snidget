<?php

namespace Snidget\Kernel\PSR;

use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInterface;

class InvalidCacheKeyException extends \InvalidArgumentException implements SimpleCacheInterface
{
}
