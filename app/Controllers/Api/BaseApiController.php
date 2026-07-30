<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JsonResponse;
use Medoo\Medoo;
use PDO;
use RuntimeException;
use Throwable;

abstract class BaseApiController
{
    private ?array $inputCache = null;
    private ?PDO $pdoCache = null;
    private ?Medoo $medooCache = null;

    protected function run(string $actionMethod): void
    {
        try {
            if (!method_exists($this, $actionMethod)) {
                throw new RuntimeException("Accion API no encontrada: {$actionMethod}");
            }

            $payload = $this->{$actionMethod}();
            if (!is_array($payload)) {
                throw new RuntimeException("La accion {$actionMethod} debe retornar un array");
            }

            JsonResponse::send($payload);
        } catch (Throwable $e) {
            JsonResponse::error($e->getMessage(), 500);
        }

        exit;
    }

    protected function input(): array
    {
        if (is_array($this->inputCache)) {
            return $this->inputCache;
        }

        $decoded = json_decode(file_get_contents('php://input'), true);
        $this->inputCache = is_array($decoded) ? $decoded : [];
        return $this->inputCache;
    }

    protected function pdo(): PDO
    {
        if ($this->pdoCache instanceof PDO) {
            return $this->pdoCache;
        }

        $this->pdoCache = Database::pdo();
        return $this->pdoCache;
    }

    protected function medoo(): Medoo
    {
        if ($this->medooCache instanceof Medoo) {
            return $this->medooCache;
        }

        $this->medooCache = Database::medoo();
        return $this->medooCache;
    }

    protected function lastInsertId(): string
    {
        return (string) $this->medoo()->id();
    }
}
