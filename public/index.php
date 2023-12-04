<?php

use App\Module\Async\AsyncKernel;
use Snidget\Kernel\Kernel;

include_once '/app/vendor/autoload.php';

// TODO: create Env class and select mode by env variable
// TODO: english comments (find cyrillic by regex)
// TODO: logs
// and other TODOs

// TODO: index file override
if (isset($_ENV['ASYNC'])) {
    (new AsyncKernel())->run();
}
(new Kernel())->run();
