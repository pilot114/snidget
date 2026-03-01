<?php

namespace Snidget\Tests\Fixtures;

class CircularA
{
    public function __construct(public CircularB $b) {}
}

class CircularB
{
    public function __construct(public CircularA $a) {}
}

class DeepA
{
    public function __construct(public DeepB $b) {}
}

class DeepB
{
    public function __construct(public DeepC $c) {}
}

class DeepC
{
    public function __construct(public DeepA $a) {}
}

class NonCircularA
{
    public function __construct(public NonCircularB $b) {}
}

class NonCircularB
{
    public function __construct(public NonCircularC $c) {}
}

class NonCircularC
{
    public string $value = 'leaf';
}
