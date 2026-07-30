<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\JsonResponse;
use Exception;
use PDO;

abstract class BaseApiController
{
    /**
     * @param callable(array, PDO): array<string, mixed> $callback
     */
    protected function execute(callable $callback): void
    {
        try {
            $payload = $callback($this->readInput(), Database::connection());
            JsonResponse::send($payload);
        } catch (Exception $e) {
            JsonResponse::error($e->getMessage(), 500);
        }

        exit;
    }

    private function readInput(): array
    {
        $decoded = json_decode(file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }
}
