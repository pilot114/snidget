<?php

include_once '/app/vendor/autoload.php';

request(
    url: 'post',
    method: 'POST',
    data: ['login' => '114', 'password' => 'qwert']
);
