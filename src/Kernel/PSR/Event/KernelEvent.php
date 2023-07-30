<?php

namespace Snidget\Kernel\PSR\Event;

// все системные события должно отправлять Kernel
enum KernelEvent
{
    // вызывается при старте фреймворка
    case START;
    // вызывается так поздно, насколько это возможно (shutdown)
    case FINISH;
    // вызывается при возникновении ошибок
    case ERROR;

    // TODO: to HTTP

    // вызывается после получения запроса
    case REQUEST;
    // вызывается перед отправкой ответа
    case RESPONSE;
    // вызывается после отправки ответа
    case SEND;
}
