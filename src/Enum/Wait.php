<?php

namespace Snidget\Enum;

enum Wait
{
    case ASAP;  // empty payload
    case WRITE; // payload - socket
    case READ;  // payload - socket
    // TODO: реализация таймеров
//    case DELAY; // payload - seconds (float)
}