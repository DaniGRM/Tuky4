<?php

declare(strict_types=1);

return [
    'version' => '202608030006_add_profile_fields_to_user_info',
    'description' => 'Agrega sexo, altura y edad a user_info para calcular BMR (Mifflin-St Jeor)',
    'transactional' => false,
    'up' => [
        "ALTER TABLE user_info
            ADD COLUMN sex ENUM('male','female') NOT NULL DEFAULT 'male' AFTER calorie_deficit_goal",
        "ALTER TABLE user_info
            ADD COLUMN height_cm DECIMAL(5,2) NOT NULL DEFAULT 170.00 AFTER sex",
        "ALTER TABLE user_info
            ADD COLUMN age INT UNSIGNED NOT NULL DEFAULT 30 AFTER height_cm",
    ],
    'down' => [
        "ALTER TABLE user_info DROP COLUMN age",
        "ALTER TABLE user_info DROP COLUMN height_cm",
        "ALTER TABLE user_info DROP COLUMN sex",
    ],
];
