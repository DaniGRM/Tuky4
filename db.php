<?php
// Carga variables desde .env (si existe) usando phpdotenv.
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

if (class_exists('Dotenv\\Dotenv')) {
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
} else {
    loadEnvFallback(__DIR__ . '/.env');
}

/**
 * Fallback simple para cargar .env cuando no esta disponible phpdotenv.
 */
function loadEnvFallback(string $envPath): void {
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '') {
            continue;
        }

        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Respeta variables ya definidas en el entorno del proceso.
        $alreadyDefined = array_key_exists($key, $_ENV)
            || array_key_exists($key, $_SERVER)
            || getenv($key) !== false;

        if ($alreadyDefined) {
            continue;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}

function envValue(string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

// ─── Conexión de base de datos (sqlite por defecto, mysql opcional) ─────────
$dbConnection = strtolower((string) (envValue('DB_CONNECTION', 'sqlite')));

if ($dbConnection === 'mysql') {
    $dbHost    = (string) envValue('DB_HOST', '127.0.0.1');
    $dbPort    = (string) envValue('DB_PORT', '3306');
    $dbName    = (string) envValue('DB_NAME', 'tuky4_dev');
    $dbUser    = (string) envValue('DB_USER', 'root');
    $dbPass    = (string) envValue('DB_PASS', '');
    $dbCharset = (string) envValue('DB_CHARSET', 'utf8mb4');

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $dbHost,
        $dbPort,
        $dbName,
        $dbCharset
    );

    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Importante: las tablas en MySQL se gestionarán con migraciones.
    return;
}

// ─── SQLite (comportamiento actual, sin cambios funcionales) ────────────────
$db_file = __DIR__ . '/gym_track.db';
$pdo = new PDO('sqlite:' . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec("CREATE TABLE IF NOT EXISTS exercises (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS records (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    exercise_id INTEGER NOT NULL,
    weight      REAL    NOT NULL,
    series      INTEGER NOT NULL,
    rep_pattern TEXT    NOT NULL,
    date        DATE    DEFAULT (date('now')),
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
)");

// ─── Migraciones (sqlite legado) ────────────────────────────────────────────
try { $pdo->exec("ALTER TABLE exercises ADD COLUMN routine TEXT NOT NULL DEFAULT 'PECHO'"); } catch (Exception $e) {}
