<?php

declare(strict_types=1);

return [
    'version' => '202608110001_create_money_settings',
    'description' => 'Crea tabla de configuracion de money',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS money_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            monthly_salary DECIMAL(10,2) NOT NULL DEFAULT 0,
            current_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            savings_goal DECIMAL(10,2) NOT NULL DEFAULT 0,
            monthly_budget DECIMAL(10,2) NOT NULL DEFAULT 0,
            payday_day TINYINT UNSIGNED NOT NULL DEFAULT 1,
            emergency_buffer DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        "DROP TABLE IF EXISTS money_settings",
    ],
];
