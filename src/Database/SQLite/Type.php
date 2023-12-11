<?php

namespace Snidget\Database\SQLite;

enum Type: string
{
    case NULL = 'NULL';
    case INTEGER = 'INTEGER';
    case REAL = 'REAL';
    case TEXT = 'TEXT';
    case BLOB = 'BLOB';
}
