<?php

namespace Snidget\Exception;

use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInterface;

class InvalidCacheKeyException extends \InvalidArgumentException implements SimpleCacheInterface
{
}