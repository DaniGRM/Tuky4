<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $base = dirname(__DIR__, 2) . '/views/';
        $path = self::resolveTemplatePath($base, $template);

        if (!is_file($path)) {
            throw new RuntimeException("Vista no encontrada: {$template}");
        }

        extract($data, EXTR_SKIP);
        require $path;
    }

    private static function resolveTemplatePath(string $base, string $template): string
    {
        $normalized = trim(str_replace('..', '', $template), '/');
        $candidates = [
            $base . $normalized . '.tpl.php',
            $base . $normalized . '.php',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }
}
