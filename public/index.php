<?php

include_once '../src/Kernel.php';

run(isAsync: php_sapi_name() === 'cli');
