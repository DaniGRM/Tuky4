<?php
declare(strict_types=1);

namespace App\Controllers;

final class EatController extends BaseController
{
    public function index(): void
    {
        $this->render('eat/index');
    }
}
