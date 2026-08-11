<?php

declare(strict_types=1);

return [
    'version' => '202608110004_refine_money_schema',
    'description' => 'Elimina columnas no usadas en money_settings y notes en money_expenses',
    'transactional' => false,
    'up' => [
        'ALTER TABLE money_settings DROP COLUMN monthly_budget',
        'ALTER TABLE money_settings DROP COLUMN payday_day',
        'ALTER TABLE money_settings DROP COLUMN emergency_buffer',
        'ALTER TABLE money_expenses DROP COLUMN notes',
    ],
    'down' => [
        'ALTER TABLE money_settings ADD COLUMN monthly_budget DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER savings_goal',
        'ALTER TABLE money_settings ADD COLUMN payday_day TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER monthly_budget',
        'ALTER TABLE money_settings ADD COLUMN emergency_buffer DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER payday_day',
        'ALTER TABLE money_expenses ADD COLUMN notes VARCHAR(255) NULL AFTER tag_id',
    ],
];
