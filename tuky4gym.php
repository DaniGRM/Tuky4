<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
	header('Location: /gym', true, 301);
	exit;
}

App\Core\JsonResponse::error('Endpoint legacy retirado. Usa /api/gym/...', 410);
