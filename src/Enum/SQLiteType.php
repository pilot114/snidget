<?php

namespace Snidget\Enum;

enum SQLiteType
{
    case NULL;
    case INTEGER;
    case REAL;
    case TEXT;
    case BLOB;
}