<?php

namespace Snidget\Kernel\PSR\Log;

enum LogLevel: string
{
    case EMERGENCY = 'EMERGENCY';
    case ALERT = 'ALERT';
    case CRITICAL = 'CRITICAL';
    case ERROR = 'ERROR';
    case WARNING = 'WARNING';
    case NOTICE = 'NOTICE';
    case INFO = 'INFO';
    case DEBUG = 'DEBUG';
}
