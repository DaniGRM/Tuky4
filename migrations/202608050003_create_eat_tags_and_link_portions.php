<?php

declare(strict_types=1);

return [
    'version' => '202608050003_create_eat_tags_and_link_portions',
    'description' => 'Crea eat_tags y vincula eat_portions con tag_id opcional',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS eat_tags (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_eat_tags_name (name),
            KEY idx_eat_tags_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "ALTER TABLE eat_portions ADD COLUMN tag_id BIGINT UNSIGNED NULL AFTER unit_id",
        "ALTER TABLE eat_portions ADD KEY idx_eat_portions_tag_id (tag_id)",
        "ALTER TABLE eat_portions ADD CONSTRAINT fk_eat_portions_tag_id FOREIGN KEY (tag_id) REFERENCES eat_tags(id) ON DELETE SET NULL",
    ],
    'down' => [
        "ALTER TABLE eat_portions DROP FOREIGN KEY fk_eat_portions_tag_id",
        "ALTER TABLE eat_portions DROP KEY idx_eat_portions_tag_id",
        "ALTER TABLE eat_portions DROP COLUMN tag_id",
        'DROP TABLE IF EXISTS eat_tags',
    ],
];
