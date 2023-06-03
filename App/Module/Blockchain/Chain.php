<?php

namespace App\Module\Blockchain;

class Chain
{
    protected array $chain;

    public function __construct(
        protected int $difficulty = 3
    ) {
        $this->chain = [$this->createGenesisBlock()];
    }

    public function addBlock(Block $newBlock): void
    {
        $newBlock->previousHash = $this->getLatestBlock()->hash;
        $newBlock->mineBlock($this->difficulty);
        $this->chain[] = $newBlock;
    }

    public function isChainValid(): bool
    {
        for ($i = 1; $i < count($this->chain); $i++) {
            $currentBlock = $this->chain[$i];
            $previousBlock = $this->chain[$i - 1];

            if ($currentBlock->hash !== $currentBlock->calculateHash()) {
                return false;
            }

            if ($currentBlock->previousHash !== $previousBlock->hash) {
                return false;
            }
        }

        return true;
    }

    public function getInfo(): array
    {
        return [
            'chain' => $this->chain,
            'difficulty' => $this->difficulty,
        ];
    }

    protected function createGenesisBlock(): Block
    {
        return new Block(0, time(), 'Genesis Block', '0');
    }

    protected function getLatestBlock(): Block
    {
        return $this->chain[count($this->chain) - 1];
    }
}