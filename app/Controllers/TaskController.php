<?php
declare(strict_types=1);

namespace App\Controllers;

final class TaskController extends BaseController
{
    public function index(): void
    {
        $this->render('task/index');
    }
}
