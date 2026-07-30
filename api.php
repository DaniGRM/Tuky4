<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

App\Core\JsonResponse::error('Endpoint legacy retirado. Usa /api/gym/...', 410);
