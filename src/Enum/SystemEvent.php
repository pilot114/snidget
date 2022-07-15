<?php

namespace Snidget\Enum;

enum SystemEvent
{
    case START;
    case HANDLE_REQUEST;
    case MATCH_ROUTE;
    case CALL_ACTION;
    case SEND_RESPONSE;
    case FINISH;
}