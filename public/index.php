<?php

// ✅ LOG TEMPORAL: detecta qué URI dispara el error UTF-8
register_shutdown_function(function () {
    $error = error_get_last();

    if ($error && str_contains($error['message'] ?? '', 'Malformed UTF-8')) {
        $logDir = __DIR__ . '/../storage/logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        file_put_contents(
            $logDir . '/utf8_routes.log',
            date('Y-m-d H:i:s')
                . ' | URI: ' . ($_SERVER['REQUEST_URI'] ?? '?')
                . ' | METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? '?')
                . ' | FILE: ' . ($error['file'] ?? '?')
                . ':' . ($error['line'] ?? '?')
                . PHP_EOL,
            FILE_APPEND
        );
    }
});

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());