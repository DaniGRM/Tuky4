<?php

declare(strict_types=1);

return [
    'version' => '202608030005_create_user_info_register',
    'description' => 'Crea tabla user_info_register para registro diario de metricas',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS user_info_register (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            weight DECIMAL(6,2) NULL,
            steps INT UNSIGNED NOT NULL DEFAULT 0,
            kcal_consumed INT UNSIGNED NOT NULL DEFAULT 0,
            kcal_burned INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_info_register_date (date),
            KEY idx_user_info_register_steps (steps)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS user_info_register',
    ],
];
