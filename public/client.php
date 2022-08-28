<?php

include_once '../src/Kernel.php';

(new Snidget\Kernel())
    ->overrideRequest('post', 'POST', ['login' => '114', 'password' => 'qwert'])
    ->run();
