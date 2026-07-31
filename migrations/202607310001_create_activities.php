<?php

return [
    'version' => '202607310001_create_activities',
    'description' => 'Crea tablas activities y activities_records',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS activities (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            type ENUM('bike', 'running') NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_activities_type (type),
            KEY idx_activities_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS activities_records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            activity_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            distance DECIMAL(8,2) NOT NULL,
            avg_speed DECIMAL(6,2) NOT NULL,
            elevation INT NOT NULL,
            duration INT UNSIGNED NOT NULL,
            CONSTRAINT fk_activities_records_activity
                FOREIGN KEY (activity_id) REFERENCES activities(id)
                ON DELETE CASCADE,
            KEY idx_activities_records_activity_date (activity_id, date),
            KEY idx_activities_records_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS activities_records',
        'DROP TABLE IF EXISTS activities',
    ],
];
