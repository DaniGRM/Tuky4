<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

abstract class BaseController
{
    protected function render(string $template, array $data = []): void
    {
        View::render($template, $data);
    }
}
