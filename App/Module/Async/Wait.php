<?php

namespace App\Module\Async;

enum Wait
{
    case ASAP;  // empty payload
    case WRITE; // payload - socket
    case READ;  // payload - socket
    case DELAY; // payload - seconds (float)
}
