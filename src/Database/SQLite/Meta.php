<?php

namespace Snidget\Database\SQLite;

class Meta
{
    public function __construct(
        protected Driver $driver,
    ) {}

    public function getInfo()
    {
        $tablesInfo = $this->driver->query("select tbl_name, sql from sqlite_schema where type = 'table'");

        foreach ($tablesInfo as $tableInfo) {
            $tableName = $tableInfo['tbl_name'];
            $fields = $this->parseCreateTableSql($tableInfo['sql']);

            dump($tableName);
            /** @var Column $field */
            foreach ($fields as $field) {
                if ($field->ref === null) {
                    dump("\t$field->name");
                } else {
                    dump("\t$field->name => $field->ref");
                }
            }
        }
    }

    // https://sqlite.org/lang_createtable.html
    private function parseCreateTableSql(string $sql): array
    {
        $pattern = '#CREATE TABLE (\[?\w+\]?)\s+\((.+)\)#s';
        preg_match($pattern, $sql, $matches);

        /** @var Column[] $columns */
        $columns = [];
        foreach (preg_split("#,\n#", trim($matches[2])) as $part) {
            $part = preg_replace("#\s+#", ' ', $part);
            $words = array_filter(explode(' ', trim($part)));
            if ($words[0] === 'CONSTRAINT') {
                continue;
            }
            if ($words[0] === 'FOREIGN') {
                array_shift($words);
                array_shift($words);
                $columnName = trim(array_shift($words), '()[]');
                array_shift($words); // REFERENCES
                $refTable = trim(array_shift($words), '()[]');
                $refColumnName = trim(array_shift($words), '()[]');
                if (implode(' ', $words) !== 'ON DELETE NO ACTION ON UPDATE NO ACTION') {
                    dump($words);
                    die();
                }
                $columns[$columnName]->ref = "$refTable.$refColumnName";
                continue;
            }
            $name = trim(array_shift($words), "[]");
            $type = mb_strtoupper(array_shift($words));
            $type = match(true) {
                str_starts_with($type, 'NVARCHAR') => 'TEXT',
                str_starts_with($type, 'NUMERIC')  => 'REAL',
                str_starts_with($type, 'DATETIME') => 'INTEGER',
                str_starts_with($type, 'INT')      => 'INTEGER',
                str_starts_with($type, 'VARCHAR')  => 'TEXT',
                default => $type,
            };
            $isNull = !(count($words) === 2 && $words[0] === 'NOT' && $words[1] === 'NULL');
            $columns[$name] = new Column(
                name: $name,
                type: Type::from($type),
                isNull: $isNull,
            );
        }
        return $columns;
    }
}
