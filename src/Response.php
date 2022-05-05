<?php

namespace Wshell\Snidget;

class Response
{
    public function __construct(
        protected string $data
    ){}

    public function send(): void
    {
        echo $this->data;
    }
}