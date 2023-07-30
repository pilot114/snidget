<?php

namespace Snidget\Database;

class SqlitePDO extends PDO
{
    public function __construct(PdoConnect $config)
    {
        if (!file_exists($config->dsn)) {
            touch(str_replace('sqlite:', '', $config->dsn));
        }
        parent::__construct($config);
    }
}