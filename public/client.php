<?php

include_once '../src/Kernel.php';

(new Snidget\Kernel())
    ->overrideRequest('post', ['login' => '114', 'password' => 'qwert'])
    ->run();
