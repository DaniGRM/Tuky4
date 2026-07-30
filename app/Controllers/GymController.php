<?php
declare(strict_types=1);

namespace App\Controllers;

final class GymController extends BaseController
{
    public function index(): void
    {
        $this->render('gym/index');
    }
}
