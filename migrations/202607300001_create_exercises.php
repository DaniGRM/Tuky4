<?php

return [
    'version' => '202607300001_create_exercises',
    'description' => 'Crea tabla exercises',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS exercises (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            routine ENUM('PECHO','ESPALDA','PIERNAS') NOT NULL DEFAULT 'PECHO',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_exercises_name (name),
            KEY idx_exercises_routine (routine)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS exercises',
    ],
];
