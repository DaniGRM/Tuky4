<?php

declare(strict_types=1);

return [
    'version' => '202608050004_add_color_to_eat_tags',
    'description' => 'Agrega color opcional a eat_tags',
    'transactional' => false,
    'up' => [
        "ALTER TABLE eat_tags ADD COLUMN color VARCHAR(7) NULL AFTER name",
    ],
    'down' => [
        "ALTER TABLE eat_tags DROP COLUMN color",
    ],
];
