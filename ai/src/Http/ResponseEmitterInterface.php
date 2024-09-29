<?php

namespace SnidgetAI\Http;

use Psr\Http\Message\ResponseInterface;

interface ResponseEmitterInterface
{
    public function emit(ResponseInterface $response): void;
}