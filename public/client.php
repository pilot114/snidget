<?php

include_once '../vendor/autoload.php';

(new Snidget\Kernel())
    ->overrideRequest('post', 'POST', ['login' => '114', 'password' => 'qwert'])
    ->run();
