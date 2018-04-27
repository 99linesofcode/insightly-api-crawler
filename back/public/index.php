<?php

require_once '/var/www/html/app/vendor/autoload.php';

use Acme\Http\Api\Insightly;
use \GuzzleHttp\Client;
use Acme\Commands;

$commands = new Commands();
$commands->test();
