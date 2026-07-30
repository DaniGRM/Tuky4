<?php
declare(strict_types=1);

use App\Controllers\ApiController;
use App\Controllers\GymController;
use App\Controllers\HomeController;
use App\Core\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/index.php', [HomeController::class, 'index']);

$router->get('/gym', [GymController::class, 'index']);
$router->get('/tuky4gym.php', [GymController::class, 'index']);

$router->post('/gym', [ApiController::class, 'handle']);
$router->post('/tuky4gym.php', [ApiController::class, 'handle']);

$router->post('/api', [ApiController::class, 'handle']);
$router->post('/api.php', [ApiController::class, 'handle']);

return $router;
