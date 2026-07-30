<?php
declare(strict_types=1);

namespace App\Core;

use Medoo\Medoo;
use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;
    private static ?Medoo $medoo = null;

    public static function pdo(): PDO
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

    public static function connection(): PDO
    {
        return self::pdo();
    }

    public static function medoo(): Medoo
    {
        if (self::$medoo instanceof Medoo) {
            return self::$medoo;
        }

        $pdo = self::pdo();
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver !== 'mysql' && $driver !== 'sqlite') {
            throw new RuntimeException("Driver PDO no soportado por Medoo: {$driver}");
        }

        self::$medoo = new Medoo([
            'type' => $driver,
            'pdo' => $pdo,
            'error' => PDO::ERRMODE_EXCEPTION,
        ]);

        return self::$medoo;
    }
}
