<?php

declare(strict_types=1);

return [
    'version' => '202608030003_create_eat_records',
    'description' => 'Crea eat_records y eat_records_rations para dashboard diario de comidas',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS eat_records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            `key` ENUM('breakfast','lunch','dinner','snacks') NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_eat_records_date_key (date, `key`),
            KEY idx_eat_records_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS eat_records_rations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            id_eat_record BIGINT UNSIGNED NOT NULL,
            id_eat_ration BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_eat_records_rations_record (id_eat_record),
            KEY idx_eat_records_rations_ration (id_eat_ration),
            CONSTRAINT fk_eat_records_rations_record
                FOREIGN KEY (id_eat_record) REFERENCES eat_records(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_eat_records_rations_ration
                FOREIGN KEY (id_eat_ration) REFERENCES eat_portions(id)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS eat_records_rations',
        'DROP TABLE IF EXISTS eat_records',
    ],
];
