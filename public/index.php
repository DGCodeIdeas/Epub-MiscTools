<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();
$kernel = new Kernel();
$response = $kernel->handle($request);
$response->send();
