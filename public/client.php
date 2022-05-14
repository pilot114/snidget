<?php

use Snidget\{Container, Request};

include_once './boot.php';

$container = new Container();
$request = $container->get(Request::class);

$request->uri = 'post';
$request->data = ['login' => '114', 'password' => 'qwert'];

include './index.php';

