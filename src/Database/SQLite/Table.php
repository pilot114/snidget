<?php

namespace Snidget\Database\SQLite;

use Snidget\Kernel\AttributeLoader;
use Snidget\Kernel\Schema\Type as SchemaType;
use Snidget\Kernel\SnidgetException;

class Table
{
    public function __construct(
        protected Driver $db,
        protected string $name,
        protected SchemaType $type
    ) {
        self::validateIdentifier($name);
    }

    public function exist(): bool
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name = :name";
        return (bool) $this->db->query($sql, ['name' => $this->name]);
    }

    public function create(?string $from = null): bool
    {
        if ($from === null) {
            $definition = AttributeLoader::getDbTypeDefinition($this->type::class);
            $sql = "CREATE TABLE {$this->name} ($definition)";
        } else {
            self::validateIdentifier($from);
            $sql = "CREATE TABLE {$this->name} AS SELECT * FROM $from";
        }
        return $this->db->execute($sql);
    }

    public function insert(SchemaType $data): bool
    {
        $insertData = AttributeLoader::getDbTypeInsertDefinition($data::class, $data);
        $columns = implode(', ', $insertData['columns']);
        $placeholders = implode(', ', array_map(fn(string $col): string => ':' . $col, $insertData['columns']));
        $sql = "INSERT INTO {$this->name} ($columns) VALUES ($placeholders)";
        $params = array_combine($insertData['columns'], $insertData['values']);
        return $this->db->execute($sql, $params);
    }

    public function find(): array
    {
        return $this->db->query("SELECT * FROM {$this->name}");
    }

    public function like(string $q, string $field): array
    {
        self::validateIdentifier($field);
        return $this->db->query(
            "SELECT * FROM {$this->name} WHERE $field LIKE lower(:q)",
            ['q' => '%' . mb_strtolower($q) . '%']
        );
    }

    public function count(): int
    {
        return $this->db->count("SELECT count(1) count FROM {$this->name}");
    }

    public function read(int $id, string $field = 'id'): array
    {
        self::validateIdentifier($field);
        return $this->db->query(
            "SELECT * FROM {$this->name} WHERE $field = :id LIMIT 1",
            ['id' => $id]
        )[0] ?? [];
    }

    public function getType(): SchemaType
    {
        return $this->type;
    }

    /**
     * @throws SnidgetException
     */
    public static function validateIdentifier(string $identifier): void
    {
        if (!preg_match('/^[a-zA-Z_]\w*$/', $identifier)) {
            throw new SnidgetException("Недопустимый идентификатор: $identifier");
        }
    }
}
