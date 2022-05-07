<?php

namespace Wshell\Snidget;

use Wshell\Snidget\Module\PDO;

class Table
{
    public function __construct(
        protected PDO $db,
        protected string $name
    ){}

    public function copy(string $from): bool
    {
        $sql = "create table {$this->name} select * from $from";
        return $this->db->execute($sql);
    }

    public function create(): bool
    {
        $sql = "create table {$this->name}";
        dump($sql);
        die();
        return $this->db->execute($sql);
    }


    public function findAll(): array
    {
        return $this->db->query("select * from {$this->name}");
    }
}