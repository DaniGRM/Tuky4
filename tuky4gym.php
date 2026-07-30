<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
$router = require __DIR__ . '/app/routes.php';
$router->dispatch();
