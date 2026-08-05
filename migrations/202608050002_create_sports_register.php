<?php

declare(strict_types=1);

return [
    'version' => '202608050002_create_sports_register',
    'description' => 'Crea tabla sports_register para registro de sesiones deportivas',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS sports_register (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            sport_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            duration INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_sports_register_sport
                FOREIGN KEY (sport_id) REFERENCES sports(id)
                ON DELETE CASCADE,
            KEY idx_sports_register_sport_date (sport_id, date),
            KEY idx_sports_register_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS sports_register',
    ],
];
