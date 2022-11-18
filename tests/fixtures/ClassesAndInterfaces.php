<?php

interface Animal
{
    public function reproduction(Animal $animal): Animal;
    public function getGenome(): int;
}

abstract class Reptile implements Animal
{
    final public function __construct(
        protected int $genome,
    ){}

    public function reproduction(Animal $animal): self
    {
        $genome = $this->getGenome() + $animal->getGenome();
        return new static($genome);
    }

    public function getGenome(): int
    {
        return $this->genome;
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
class Iguania extends Lizard {}