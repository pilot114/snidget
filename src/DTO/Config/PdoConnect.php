<?php

namespace Snidget\DTO\Config;

class PdoConnect
{
    public string $dsn = 'sqlite:../data/snidget';
//    public string $dsn = 'sqlite::memory:';
    public string $user = '';
    public string $password = '';
}