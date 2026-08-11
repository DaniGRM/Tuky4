<?php

declare(strict_types=1);

return [
    'version' => '202608110005_add_money_csv_import_rules_and_incomes',
    'description' => 'Agrega soporte CSV idempotente, reglas por concepto e ingresos en money',
    'transactional' => false,
    'up' => [
        "ALTER TABLE money_expenses
            ADD COLUMN source_item VARCHAR(255) NULL AFTER name,
            ADD COLUMN import_ref CHAR(64) NULL AFTER source_item",
        'ALTER TABLE money_expenses ADD UNIQUE KEY uq_money_expenses_import_ref (import_ref)',
        "CREATE TABLE IF NOT EXISTS money_incomes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            source_item VARCHAR(255) NULL,
            amount DECIMAL(10,2) NOT NULL,
            income_date DATE NOT NULL,
            tag_id BIGINT UNSIGNED NULL,
            import_ref CHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_money_incomes_import_ref (import_ref),
            KEY idx_money_incomes_date (income_date),
            KEY idx_money_incomes_tag_id (tag_id),
            CONSTRAINT fk_money_incomes_tag FOREIGN KEY (tag_id)
                REFERENCES money_tags(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS money_concept_rules (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            bank_item VARCHAR(255) NOT NULL,
            pretty_name VARCHAR(160) NULL,
            apply_to ENUM('expense','income','both') NOT NULL DEFAULT 'expense',
            expense_type ENUM('fixed','variable') NULL,
            tag_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_money_concept_rules_bank_item (bank_item),
            KEY idx_money_concept_rules_tag_id (tag_id),
            CONSTRAINT fk_money_concept_rules_tag FOREIGN KEY (tag_id)
                REFERENCES money_tags(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS money_concept_rules',
        'DROP TABLE IF EXISTS money_incomes',
        'ALTER TABLE money_expenses DROP INDEX uq_money_expenses_import_ref',
        'ALTER TABLE money_expenses DROP COLUMN import_ref',
        'ALTER TABLE money_expenses DROP COLUMN source_item',
    ],
];
