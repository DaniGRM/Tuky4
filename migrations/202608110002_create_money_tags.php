<?php

declare(strict_types=1);

return [
    'version' => '202608110002_create_money_tags',
    'description' => 'Crea tabla de tags para money',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS money_tags (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            color VARCHAR(7) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_money_tags_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        "DROP TABLE IF EXISTS money_tags",
    ],
];
