<?php

include_once '../vendor/autoload.php';

override(
    url: 'post',
    method: 'POST',
    data: ['login' => '114', 'password' => 'qwert']
);
