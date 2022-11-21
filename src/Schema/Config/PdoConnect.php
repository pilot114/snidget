<?php

namespace Snidget\Schema\Config;

class PdoConnect
{
    public string $dsn = 'sqlite:../data/snidget'; // sqlite::memory:
    public string $user = '';
    public string $password = '';
}
