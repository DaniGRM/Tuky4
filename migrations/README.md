# Migraciones Tuky4

Este proyecto usa migraciones versionadas en PHP para MySQL.

## Comandos

- `php migrate.php status` -> ver estado
- `php migrate.php up` -> aplicar pendientes
- `php migrate.php down --step=1` -> revertir ultimos batches

## Propiedades de una migracion

Cada archivo en `migrations/*.php` debe retornar un array:

```php
return [
    'version' => '202607300010_add_example_table',
    'description' => 'Describe el cambio',
    'transactional' => false,
    'up' => [
        "CREATE TABLE ...",
    ],
    'down' => [
        "DROP TABLE ...",
    ],
];
```

## Convenciones

- No editar una migracion ya aplicada.
- Para cambios nuevos, crear archivo nuevo con version mayor.
- El runner valida checksum para detectar cambios en migraciones aplicadas.
