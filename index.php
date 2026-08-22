<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
define('LARAVEL_ROOT', __DIR__.'/laravel-app');

if (file_exists($maintenance = LARAVEL_ROOT.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require LARAVEL_ROOT.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once LARAVEL_ROOT.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
