<?php

namespace Snidget\Kernel;

// все системные события должно отправлять Kernel
enum SystemEvent
{
    // вызывается при старте фреймворка
    case START;
    // вызывается после получения запроса
    case REQUEST;
    // вызывается перед отправкой ответа
    case RESPONSE;
    // вызывается после отправки ответа
    case SEND;
    // вызывается так поздно, насколько это возможно (shutdown)
    case FINISH;
    // вызывается при необработанном исключении
    case EXCEPTION;
}
