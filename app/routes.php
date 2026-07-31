<?php
declare(strict_types=1);

use App\Controllers\Api\GymApiController;
use App\Controllers\GymController;
use App\Controllers\HomeController;
use App\Core\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/index.php', [HomeController::class, 'index']);

$router->get('/gym', [GymController::class, 'index']);

$router->post('/api/gym/exercises/list', [GymApiController::class, 'listExercises']);
$router->post('/api/gym/exercises/add', [GymApiController::class, 'addExercise']);
$router->post('/api/gym/exercises/delete', [GymApiController::class, 'deleteExercise']);

$router->post('/api/gym/records/add', [GymApiController::class, 'addRecord']);
$router->post('/api/gym/records/history', [GymApiController::class, 'getHistory']);
$router->post('/api/gym/records/delete', [GymApiController::class, 'deleteRecord']);

$router->post('/api/gym/activities/list', [GymApiController::class, 'listActivities']);
$router->post('/api/gym/activities/add', [GymApiController::class, 'addActivity']);
$router->post('/api/gym/activities/delete', [GymApiController::class, 'deleteActivity']);

$router->post('/api/gym/activities-records/add', [GymApiController::class, 'addActivityRecord']);
$router->post('/api/gym/activities-records/history', [GymApiController::class, 'getActivityHistory']);
$router->post('/api/gym/activities-records/delete', [GymApiController::class, 'deleteActivityRecord']);

return $router;
