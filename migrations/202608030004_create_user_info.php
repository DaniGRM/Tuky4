<?php

declare(strict_types=1);

return [
    'version' => '202608030004_create_user_info',
    'description' => 'Crea tabla user_info para metas globales de usuario',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS user_info (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            daily_steps_goal INT UNSIGNED NOT NULL DEFAULT 0,
            calorie_deficit_goal INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS user_info',
    ],
];
