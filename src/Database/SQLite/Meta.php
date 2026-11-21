<?php

namespace Snidget\Database\SQLite;

class Meta
{
    public function __construct(
        protected Driver $driver,
    ) {
    }

    public function getInfo(): void
    {
        $sql = "SELECT name FROM sqlite_schema WHERE type = 'table' AND name NOT LIKE 'sqlite_%'";
        foreach (array_column($this->driver->query($sql), 'name') as $table) {
            dump($table);
            $columns = $this->driver->query(
                "SELECT char(9) || c.name || coalesce(' => ' || f.\"table\" || '.' || f.\"to\", '') AS line
                FROM pragma_table_info(:t) c LEFT JOIN pragma_foreign_key_list(:t) f ON f.\"from\" = c.name",
                ['t' => $table]
            );
            dump(...array_column($columns, 'line'));
        }
    }
}
