<?php

return [
    'version' => '202607300002_create_records',
    'description' => 'Crea tabla records',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            exercise_id BIGINT UNSIGNED NOT NULL,
            weight DECIMAL(6,2) NOT NULL,
            series INT UNSIGNED NOT NULL,
            rep_pattern VARCHAR(64) NOT NULL,
            date DATE NOT NULL DEFAULT (CURRENT_DATE),
            CONSTRAINT fk_records_exercise
                FOREIGN KEY (exercise_id) REFERENCES exercises(id)
                ON DELETE CASCADE,
            KEY idx_records_exercise_date (exercise_id, date),
            KEY idx_records_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS records',
    ],
];
