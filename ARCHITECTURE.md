# Arquitectura MVC ligera

## Flujo de entrada

- `index.php` delega en el router central.
- `tuky4gym.php` es solo un redirect legacy hacia `/gym`.
- `api.php` responde 410 para forzar migracion a endpoints modulares.
- `app/routes.php` define rutas y handlers.
- `app/Core/Router.php` resuelve metodo + path y ejecuta controlador.
- `router.php` permite front controller para `php -S`.

## Capas

- `app/bootstrap.php`: autoload simple para clases en `App\\...`.
- `app/Core/Router.php`: router HTTP (GET/POST/ANY) con dispatch centralizado.
- `app/Controllers/*`: controladores de vistas por modulo.
- `app/Controllers/Api/*`: controladores API por modulo.
- `app/Core/View.php`: renderer de vistas (`.tpl.php` o `.php`).
- `app/Core/Database.php`: proveedor central de conexion PDO.
- `app/Core/JsonResponse.php`: respuesta JSON centralizada.
- `app/routes.php`: registro de rutas de la aplicacion.
- `views/*`: plantillas HTML separadas por modulo.

## Convencion para nuevas pantallas

1. Crear controlador en `app/Controllers`.
2. Crear plantilla en `views/<modulo>/<pantalla>.tpl.php`.
3. Registrar una ruta en `app/routes.php` hacia el controlador.

## Convencion para API

- Evitar controladores monoliticos por `action`.
- Crear un `ApiController` por modulo (ejemplo: `GymApiController`).
- Exponer endpoints explicitos por caso de uso.
- Mantener respuestas JSON consistentes: `{ ok: bool, ... }`.

## Rutas actuales

- `GET /` y `GET /index.php` -> `HomeController::index()`.
- `GET /gym` -> `GymController::index()`.
- `POST /api/gym/exercises/list` -> `GymApiController::listExercises()`.
- `POST /api/gym/exercises/add` -> `GymApiController::addExercise()`.
- `POST /api/gym/exercises/delete` -> `GymApiController::deleteExercise()`.
- `POST /api/gym/records/add` -> `GymApiController::addRecord()`.
- `POST /api/gym/records/history` -> `GymApiController::getHistory()`.
- `POST /api/gym/records/delete` -> `GymApiController::deleteRecord()`.

## Endpoints retirados

- `/api.php` -> HTTP 410 (deprecated).
- `/tuky4gym.php` -> redirect 301 a `/gym` en GET; API por ese path no soportada.

## Politica de eliminacion legacy

- Cuando un modulo migra a endpoints explicitos, se elimina el endpoint antiguo en la misma iteracion.
- No mantener rutas alias legacy de forma indefinida.

## Base de datos

- La conexion vive en `db.php`.
- El motor se elige por `DB_CONNECTION` (`sqlite` o `mysql`).
- MySQL usa migraciones versionadas (`migrate.php` y `migrations/*`).
