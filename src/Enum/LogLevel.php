<?php

namespace Snidget\Enum;

enum LogLevel
{
    case EMERGENCY;
    case ALERT;
    case CRITICAL;
    case ERROR;
    case WARNING;
    case NOTICE;
    case INFO;
    case DEBUG;
}
