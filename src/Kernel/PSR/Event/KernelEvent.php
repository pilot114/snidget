<?php

namespace Snidget\Kernel\PSR\Event;

// only Kernel emits these
enum KernelEvent
{
    case START;
    // on shutdown, as late as possible
    case FINISH;
    case ERROR;
    case REQUEST;
    case RESPONSE;
}
