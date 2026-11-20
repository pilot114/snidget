<?php

namespace Snidget\Database\SQLite;

use Snidget\Kernel\SnidgetException;

class Meta
{
    public function __construct(
        protected Driver $driver,
    ) {
    }

    public function getInfo(): void
    {
        $tablesInfo = $this->driver->query("select tbl_name, sql from sqlite_schema where type = 'table'");

        foreach ($tablesInfo as ['tbl_name' => $tableName, 'sql' => $sql]) {
            if (!is_string($tableName) || !is_string($sql)) {
                continue;
            }

            dump($tableName);
            foreach ($this->parseCreateTableSql($sql) as $field) {
                if ($field->ref === null) {
                    dump("\t$field->name");
                } else {
                    dump("\t$field->name => $field->ref");
                }
            }
        }
    }

    /**
     * https://sqlite.org/lang_createtable.html
     *
     * @return array<string, Column>
     */
    private function parseCreateTableSql(string $sql): array
    {
        $pattern = '#CREATE TABLE (\[?\w+\]?)\s+\((.+)\)#s';
        $parts = preg_match($pattern, $sql, $matches) === 1 ? preg_split("#,\n#", trim($matches[2])) : false;
        if ($parts === false) {
            throw new SnidgetException("Не удалось разобрать CREATE TABLE: $sql");
        }

        $columns = [];
        foreach ($parts as $part) {
            $part = preg_replace("#\s+#", ' ', $part) ?? $part;
            $words = array_values(array_filter(explode(' ', trim($part)), fn(string $w): bool => $w !== ''));
            $first = $words[0] ?? '';
            if ($first === 'CONSTRAINT') {
                continue;
            }

            if ($first === 'FOREIGN') {
                array_shift($words);
                array_shift($words);
                $columnName = $this->shiftName($words);
                array_shift($words); // REFERENCES
                $refTable = $this->shiftName($words);
                $refColumnName = $this->shiftName($words);
                if (implode(' ', $words) !== 'ON DELETE NO ACTION ON UPDATE NO ACTION') {
                    throw new SnidgetException('Неподдерживаемый FOREIGN KEY: ' . implode(' ', $words));
                }

                $column = $columns[$columnName]
                    ?? throw new SnidgetException("FOREIGN KEY на неизвестную колонку $columnName");
                $column->ref = "$refTable.$refColumnName";
                continue;
            }

            $name = $this->shiftName($words);
            $type = mb_strtoupper(array_shift($words) ?? '');
            $type = match (true) {
                str_starts_with($type, 'NVARCHAR') => 'TEXT',
                str_starts_with($type, 'NUMERIC')  => 'REAL',
                str_starts_with($type, 'DATETIME') => 'INTEGER',
                str_starts_with($type, 'INT')      => 'INTEGER',
                str_starts_with($type, 'VARCHAR')  => 'TEXT',
                default => $type,
            };
            $isNull = $words !== ['NOT', 'NULL'];
            $columns[$name] = new Column(
                name: $name,
                type: Type::tryFrom($type) ?? Type::TEXT,
                isNull: $isNull,
            );
        }

        return $columns;
    }

    /**
     * @param list<string> $words
     */
    private function shiftName(array &$words): string
    {
        return trim(array_shift($words) ?? '', '()[]');
    }
}
