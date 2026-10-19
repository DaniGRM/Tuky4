<?php

declare(strict_types=1);

return [
    'version' => '202610190001_create_task_items',
    'description' => 'Crea tabla de tareas GTD (inbox, siguientes, fechadas y derivadas)',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS task_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            notes VARCHAR(500) NULL,
            status ENUM('inbox','next','waiting','someday','done') NOT NULL DEFAULT 'inbox',
            scheduled_date DATE NULL,
            context VARCHAR(60) NULL,
            parent_id BIGINT UNSIGNED NULL,
            completed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_task_items_status (status),
            KEY idx_task_items_scheduled_date (scheduled_date),
            KEY idx_task_items_parent_id (parent_id),
            CONSTRAINT fk_task_items_parent FOREIGN KEY (parent_id)
                REFERENCES task_items(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        "DROP TABLE IF EXISTS task_items",
    ],
];
