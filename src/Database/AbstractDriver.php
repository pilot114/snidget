<?php

namespace Snidget\Database;

use PDO;
use PDOStatement;
use Snidget\Kernel\SnidgetException;

abstract class AbstractDriver
{
    protected PDO $connection;

    public function __construct(ConnectConfig $config)
    {
        $this->connection = new PDO(
            $config->dsn,
            $config->user,
            $config->password,
            [
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    /**
     * @throws SnidgetException
     */
    public function execute(string $sql, array $params = []): bool
    {
        return $this->prepare($sql)->execute($params);
    }

    /**
     * @throws SnidgetException
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->prepare($sql);
        return $stmt->execute($params) ? $stmt->fetchAll() : [];
    }

    /**
     * @throws SnidgetException
     */
    public function count(string $sql, array $params = []): int
    {
        $stmt = $this->prepare($sql);
        return $stmt->execute($params) ? (int)$stmt->fetchColumn() : 0;
    }

    /**
     * @throws SnidgetException
     */
    protected function prepare(string $sql): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new SnidgetException("Не удалось подготовить sql запрос: $sql");
        }
        return $stmt;
    }
}
