<?php

namespace Wshell\Snidget;

use Wshell\Snidget\Module\PDO;
use Wshell\Snidget\Typing\Type;

class Table
{
    public function __construct(
        protected PDO $db,
        protected string $name,
        protected Type $type
    ){}

    public function copy(string $from): bool
    {
        $sql = "create table {$this->name} select * from $from";
        return $this->db->execute($sql);
    }

    public function create(): bool
    {
        $definition = AttributeLoader::getDbTypeDefinition($this->type::class);
        $sql = sprintf('create table %s (%s)', $this->name, $definition);
        return $this->db->execute($sql);
    }

    public function findAll(): array
    {
        return $this->db->query("select * from {$this->name}");
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function insert(Type $data): bool
    {
        $definitionInsert = AttributeLoader::getDbTypeInsertDefinition($data::class, $data);
        $sql = sprintf('insert into %s %s', $this->name, $definitionInsert);
        return $this->db->execute($sql);
    }
}