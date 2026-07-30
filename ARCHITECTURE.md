# Arquitectura MVC ligera

## Flujo de entrada

- `index.php`, `tuky4gym.php` y `api.php` delegan en el router central.
- `app/routes.php` define rutas y handlers.
- `app/Core/Router.php` resuelve metodo + path y ejecuta controlador.
- `router.php` permite front controller para `php -S`.

## Capas

- `app/bootstrap.php`: autoload simple para clases en `App\\...`.
- `app/Core/Router.php`: router HTTP (GET/POST/ANY) con dispatch centralizado.
- `app/Controllers/*`: logica de aplicacion y orquestacion.
- `app/Core/View.php`: renderer de vistas (`.tpl.php` o `.php`).
- `app/routes.php`: registro de rutas de la aplicacion.
- `views/*`: plantillas HTML separadas por modulo.

## Convencion para nuevas pantallas

1. Crear controlador en `app/Controllers`.
2. Crear plantilla en `views/<modulo>/<pantalla>.tpl.php`.
3. Registrar una ruta en `app/routes.php` hacia el controlador.

## Convencion para API

- Agregar nueva accion en `ApiController::handle()`.
- Implementar metodo privado por accion (`addX`, `getX`, `deleteX`).
- Mantener respuestas JSON consistentes: `{ ok: bool, ... }`.

## Rutas actuales

- `GET /` y `GET /index.php` -> `HomeController::index()`.
- `GET /gym` y `GET /tuky4gym.php` -> `GymController::index()`.
- `POST /gym`, `POST /tuky4gym.php`, `POST /api`, `POST /api.php` -> `ApiController::handle()`.

## Base de datos

- La conexion vive en `db.php`.
- El motor se elige por `DB_CONNECTION` (`sqlite` o `mysql`).
- MySQL usa migraciones versionadas (`migrate.php` y `migrations/*`).
