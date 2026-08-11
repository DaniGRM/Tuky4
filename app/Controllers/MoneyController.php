<?php
declare(strict_types=1);

namespace App\Controllers;

final class MoneyController extends BaseController
{
    public function index(): void
    {
        $this->render('money/index');
    }
}
