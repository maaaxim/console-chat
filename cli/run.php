<?php

error_reporting(0);

use Symfony\Component\Console\Application;
use Maaaxim\ConsoleChat\Command\InitUserCommand;

require("./vendor/autoload.php");

$app = new Application('Welcome', "v1.0.0");
$app->add(new InitUserCommand());
$app->run();