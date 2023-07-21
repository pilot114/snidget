<?php

namespace Snidget\Database;

enum SQLiteType
{
    case NULL;
    case INTEGER;
    case REAL;
    case TEXT;
    case BLOB;
}
