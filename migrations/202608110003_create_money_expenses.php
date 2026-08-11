<?php

declare(strict_types=1);

return [
    'version' => '202608110003_create_money_expenses',
    'description' => 'Crea tabla de gastos de money (fijos y variables)',
    'transactional' => false,
    'up' => [
        "CREATE TABLE IF NOT EXISTS money_expenses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            expense_type ENUM('fixed','variable') NOT NULL,
            expense_date DATE NULL,
            billing_day TINYINT UNSIGNED NULL,
            tag_id BIGINT UNSIGNED NULL,
            notes VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_money_expenses_type (expense_type),
            KEY idx_money_expenses_date (expense_date),
            KEY idx_money_expenses_billing_day (billing_day),
            KEY idx_money_expenses_tag_id (tag_id),
            CONSTRAINT fk_money_expenses_tag FOREIGN KEY (tag_id)
                REFERENCES money_tags(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        "DROP TABLE IF EXISTS money_expenses",
    ],
];
