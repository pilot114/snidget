<?php

namespace Wshell\Snidget\Module;

use Wshell\Snidget\Config\PdoConnect;
use PDO as NativePDO;

class PDO
{
    protected NativePDO $pdo;

    public function __construct(PdoConnect $config)
    {
        $this->pdo = new NativePDO(
            $config->dsn,
            $config->user,
            $config->password,
        );
    }

    public function execute(string $sql): bool
    {
//        dump('sql execute: ' . $sql);
        return $this->pdo->prepare($sql)->execute();
    }

    public function query(string $sql): array
    {
//        dump('sql query: ' . $sql);
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
    }
}