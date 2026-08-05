<?php

declare(strict_types=1);

return [
    'version' => '202608050001_create_sports',
    'description' => 'Crea tabla sports para catalogo de deportes',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS sports (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            icon VARCHAR(120) NOT NULL DEFAULT 'fa-solid fa-futbol',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sports_name (name),
            KEY idx_sports_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS sports',
    ],
];
