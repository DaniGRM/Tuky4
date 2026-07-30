<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        require __DIR__ . '/../../db.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('No se pudo inicializar la conexion a base de datos.');
        }

        self::$connection = $pdo;
        return self::$connection;
    }
}
