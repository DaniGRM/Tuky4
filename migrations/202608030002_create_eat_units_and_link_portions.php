<?php

declare(strict_types=1);

return [
    'version' => '202608030002_create_eat_units_and_link_portions',
    'description' => 'Crea eat_units y vincula eat_portions mediante unit_id',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS eat_units (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(40) NOT NULL,
            icon VARCHAR(80) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 100,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_eat_units_name (name),
            KEY idx_eat_units_sort_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "INSERT INTO eat_units (name, icon, sort_order) VALUES
            ('unidad', 'fa-solid fa-cube', 10),
            ('un cuarto', 'fa-regular fa-square', 20),
            ('una mitad', 'fa-solid fa-divide', 30),
            ('taza', 'fa-solid fa-mug-hot', 40),
            ('gramos', 'fa-solid fa-weight-scale', 50),
            ('cucharada', 'fa-solid fa-spoon', 60),
            ('cucharadita', 'fa-solid fa-spoon', 70),
            ('mililitros', 'fa-solid fa-vial', 80),
            ('rebanada', 'fa-solid fa-bread-slice', 90),
            ('porcion', 'fa-solid fa-bowl-food', 100)
         ON DUPLICATE KEY UPDATE icon = VALUES(icon), sort_order = VALUES(sort_order)",

        "ALTER TABLE eat_portions ADD COLUMN unit_id BIGINT UNSIGNED NULL AFTER amount",

        "UPDATE eat_portions p
         INNER JOIN eat_units u ON LOWER(TRIM(p.unit)) = LOWER(TRIM(u.name))
         SET p.unit_id = u.id
         WHERE p.unit_id IS NULL",

        "UPDATE eat_portions p
         INNER JOIN eat_units u ON u.name = 'unidad'
         SET p.unit_id = u.id
         WHERE p.unit_id IS NULL",

        "ALTER TABLE eat_portions MODIFY unit_id BIGINT UNSIGNED NOT NULL",
        "ALTER TABLE eat_portions ADD KEY idx_eat_portions_unit_id (unit_id)",
        "ALTER TABLE eat_portions ADD CONSTRAINT fk_eat_portions_unit_id FOREIGN KEY (unit_id) REFERENCES eat_units(id) ON DELETE RESTRICT",
        "ALTER TABLE eat_portions DROP COLUMN unit",
    ],
    'down' => [
        "ALTER TABLE eat_portions ADD COLUMN unit VARCHAR(32) NOT NULL DEFAULT 'unidad' AFTER amount",
        "UPDATE eat_portions p
         INNER JOIN eat_units u ON p.unit_id = u.id
         SET p.unit = u.name",
        "ALTER TABLE eat_portions DROP FOREIGN KEY fk_eat_portions_unit_id",
        "ALTER TABLE eat_portions DROP KEY idx_eat_portions_unit_id",
        "ALTER TABLE eat_portions DROP COLUMN unit_id",
        "DROP TABLE IF EXISTS eat_units",
    ],
];
