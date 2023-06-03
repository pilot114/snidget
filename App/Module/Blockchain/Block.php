<?php

namespace App\Module\Blockchain;

class Block
{
    public string $hash;
    public string $nonce;

    public function __construct(
        public int $index,
        public int $timestamp,
        public mixed $data,
        public string $previousHash = '',
    ) {
        $this->nonce = 0;
        $this->hash = $this->calculateHash();
    }

    public function calculateHash(): string
    {
        return hash('sha256', $this->index . $this->timestamp . json_encode($this->data) . $this->previousHash . $this->nonce);
    }

    public function mineBlock(int $difficulty): string
    {
        while (substr($this->hash, 0, $difficulty) !== str_repeat('0', $difficulty)) {
            $this->nonce++;
            $this->hash = $this->calculateHash();
        }
        return $this->hash;
    }
}