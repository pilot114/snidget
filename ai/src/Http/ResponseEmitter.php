<?php

namespace SnidgetAI\Http;

use Psr\Http\Message\ResponseInterface;

class ResponseEmitter implements ResponseEmitterInterface
{
    public function emit(ResponseInterface $response): void
    {
        // Статус и заголовки
        header(sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true, $response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        // Тело ответа
        echo $response->getBody();
    }
}