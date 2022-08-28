<?php

include_once '../src/Kernel.php';

$isAsync = php_sapi_name() === 'cli';
(new Snidget\Kernel())->run($isAsync);
