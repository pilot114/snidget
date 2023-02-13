#!/usr/local/bin/php
<?php

include __DIR__ . "/vendor/autoload.php";

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class GenerateAdminCommand extends Command
{

}

$application = new Application();
//$application->add(new GenerateAdminCommand());
$application->run();