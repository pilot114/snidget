<?php

interface Animal
{
    public function reproduction(Animal $animal, Environment $env): Animal;
    public function getGenome(): int;
}

abstract class Reptile implements Animal
{
    final public function __construct(
        protected int $genome,
        protected Environment $env,
    ){
//        dump('__construct ' . static::class . "($genome)");
    }

    public function reproduction(Animal $animal, Environment $env): self
    {
        $genome = $this->getGenome() + $animal->getGenome();
        return new static($genome, $env);
    }

    public function getGenome(): int
    {
        return $this->genome;
    }

    static public function getName(): string
    {
        return static::class;
    }
}

abstract class Lizard extends Reptile
{
    protected bool $hasTail = true;

    public function throwTail(): void
    {
        $this->hasTail = false;
    }

    public function growTail(): void
    {
        $this->hasTail = true;
    }

    public function withTail(): bool
    {
        return $this->hasTail;
    }
}

class Chameleon extends Lizard {}
class Iguana extends Lizard {}

class Environment {}