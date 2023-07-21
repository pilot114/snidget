<?php

namespace Snidget\CLI;

enum CLIStyle: string
{
    case RESET = 'reset';
    case BOLD  = 'bold';
    case DIM   = 'dim';
    case UNDER = 'under';
    case BLINK = 'blink';
    case REV   = 'rev';
    case HIDE  = 'hide';
}
