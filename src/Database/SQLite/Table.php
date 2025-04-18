<?php

namespace Snidget\Database\SQLite;

use Snidget\Kernel\AttributeLoader;

class Table
{
    public function __construct(
        protected Driver $db,
        protected string $name,
        protected Type   $type
    ) {
    }

    public function exist(): bool
    {
        $sql = "select name from sqlite_master where type='table' and name = '{$this->name}'";
        return (bool)$this->db->query($sql);
    }

    public function create(?string $from = null): bool
    {
        if ($from === null) {
            $definition = AttributeLoader::getDbTypeDefinition($this->type::class);
            $sql = "create table {$this->name} ($definition)";
        } else {
            $sql = "create table {$this->name} select * from $from";
        }
        return $this->db->execute($sql);
    }

    public function insert(Type $data): bool
    {
        $definitionInsert = AttributeLoader::getDbTypeInsertDefinition($data::class, $data);
        $sql = "insert into {$this->name} $definitionInsert";
        return $this->db->execute($sql);
    }

    public function find(): array
    {
        return $this->db->query("select * from {$this->name}");
    }

    public function like(string $q, string $field): array
    {
        return $this->db->query(
            "select * from {$this->name} where $field like lower(:q)",
            ['q' => '%' . mb_strtolower($q) . '%']
        );
    }

    public function count(): int
    {
        return $this->db->count("select count(1) count from {$this->name}");
    }

    public function read(int $id, string $field = 'id'): array
    {
        return $this->db->query(
            "select * from {$this->name} where $field = :id limit 1",
            ['id' => $id]
        )[0] ?? [];
    }

    public function getType(): Type
    {
        return $this->type;
    }
}
