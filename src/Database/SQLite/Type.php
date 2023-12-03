<?php

namespace Snidget\Database\SQLite;

enum Type
{
    case NULL;
    case INTEGER;
    case REAL;
    case TEXT;
    case BLOB;
}
