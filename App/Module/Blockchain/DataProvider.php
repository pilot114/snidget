<?php

namespace App\Module\Blockchain;

/**
 * TODO:
 * - Consensus Algorithm
 * - Peer-to-Peer Network
 * - Block Validation
 * - Consensus Mechanism
 * - Block Propagation
 * - Peer Discovery
 * - Fault Tolerance
 * - Security
 */
class DataProvider
{
    public static function example(): void
    {
        $blockchain = new Chain(difficulty: 2);

        $block1 = new Block(1, time(), ['Transaction 1']);
        $blockchain->addBlock($block1);

        $block2 = new Block(2, time(), ['Transaction 2']);
        $blockchain->addBlock($block2);

        if ($blockchain->isChainValid()) {
            echo json_encode($blockchain->getInfo(), JSON_PRETTY_PRINT) . "\n";
            return;
        }
        echo "blockchain invalid\n";
    }
}