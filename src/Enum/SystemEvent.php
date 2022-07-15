<?php

namespace Snidget\Enum;

enum SystemEvent
{
    // вызывается так рано, насколько это возможно
    case START;
    // вызывается перед отправкой ответа
    case SEND;
    // вызывается так поздно, насколько это возможно
    case FINISH;
    // вызывается при необработанном исключении
    case EXCEPTION;
}