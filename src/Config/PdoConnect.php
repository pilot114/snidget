<?php

namespace Wshell\Snidget\Config;

class PdoConnect
{
    public string $dsn = 'sqlite::memory:';
    public string $user = 'root';
    public string $password = '';
}