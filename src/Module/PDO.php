<?php

namespace Snidget\Module;

use Snidget\DTO\Config\PdoConnect;
use PDO as NativePDO;
use PDOStatement;

class PDO
{
    protected NativePDO $connection;
    protected array $queries = [];

    public function __construct(PdoConnect $config)
    {
        if (str_starts_with($config->dsn, 'sqlite:') && !file_exists($config->dsn)) {
            touch(str_replace('sqlite:', '', $config->dsn));
        }
        $this->connection = new NativePDO(
            $config->dsn,
            $config->user,
            $config->password,
            [
                NativePDO::ATTR_EMULATE_PREPARES => false,
                NativePDO::ATTR_ERRMODE => NativePDO::ERRMODE_EXCEPTION,
                NativePDO::ATTR_DEFAULT_FETCH_MODE => NativePDO::FETCH_ASSOC,
            ]
        );
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->prepare(__METHOD__, $sql, $params);
        return $stmt && $stmt->execute($params);
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->prepare(__METHOD__, $sql, $params);
        return ($stmt && $stmt->execute($params)) ? $stmt->fetchAll() : [];
    }

    public function count(string $sql, array $params = []): int
    {
        $stmt = $this->prepare(__METHOD__, $sql, $params);
        return ($stmt && $stmt->execute($params)) ? (int)$stmt->fetchColumn() : 0;
    }

    public function getLog(): array
    {
        return $this->queries;
    }

    protected function prepare(string $method, string $sql, array $params): PDOStatement | false
    {
        $this->queries[] = [$method, $sql, $params];
        return $this->connection->prepare($sql);
    }
}
