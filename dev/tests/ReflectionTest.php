<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\Reflection;

class ReflectionTest extends TestCase
{
    public function testUnknownClassThrows(): void
    {
        $this->expectException(ReflectionException::class);
        new Reflection('NoSuchClass');
    }

    public function testUnknownAttributeTargetThrows(): void
    {
        $this->expectException(UnhandledMatchError::class);
        iterator_to_array(new Reflection(self::class)->getAttributes(0, Attribute::class));
    }
}
