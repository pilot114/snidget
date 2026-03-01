<?php

include_once __DIR__ . '/../vendor/autoload.php';

request(
    url: 'post',
    method: 'POST',
    data: ['login' => '114', 'password' => 'qwert']
);
