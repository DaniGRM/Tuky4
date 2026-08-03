<?php

declare(strict_types=1);

return [
    'version' => '202608030001_create_eat_portions',
    'description' => 'Crea tabla eat_portions para CRUD de raciones',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS eat_portions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            amount DECIMAL(8,2) NOT NULL,
            unit VARCHAR(32) NOT NULL,
            calories INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_eat_portions_name (name),
            KEY idx_eat_portions_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS eat_portions',
    ],
];
