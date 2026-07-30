<?php
declare(strict_types=1);

namespace App\Core;

final class JsonResponse
{
    public static function send(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public static function ok(array $data = []): void
    {
        self::send(array_merge(['ok' => true], $data));
    }

    public static function error(string $message, int $statusCode = 400): void
    {
        self::send(['ok' => false, 'msg' => $message], $statusCode);
    }
}
