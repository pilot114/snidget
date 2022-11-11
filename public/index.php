<?php

include_once '../vendor/autoload.php';

run(isAsync: php_sapi_name() === 'cli');
