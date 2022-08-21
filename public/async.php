<?php

include_once '../src/Kernel.php';

use Snidget\Async\Scheduler;
use Snidget\Async\Debug;
use Snidget\Async\Http;
use Snidget\Kernel;

$app = new Kernel();

$scheduler = new Scheduler([
    Http::server(...),
], new Debug());
$scheduler->run();