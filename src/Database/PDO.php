<?php

namespace Snidget\Database;

use PDO as NativePDO;
use PDOStatement;
use Snidget\Kernel\SnidgetException;

abstract class PDO
{
    protected NativePDO $connection;

    public function __construct(PdoConnect $config)
    {
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
