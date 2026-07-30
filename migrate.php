<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se puede ejecutar desde CLI.\n");
    exit(1);
}

require_once __DIR__ . '/db.php';

$driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver !== 'mysql') {
    fwrite(STDERR, "Las migraciones versionadas estan habilitadas para MySQL. Configura DB_CONNECTION=mysql en .env.\n");
    exit(1);
}

$argv = $_SERVER['argv'] ?? [];
$command = $argv[1] ?? 'up';
$options = parseOptions(array_slice($argv, 2));

$runner = new MigrationRunner(
    $pdo,
    __DIR__ . '/migrations',
    envValue('MIGRATIONS_TABLE', 'schema_migrations') ?? 'schema_migrations',
    envValue('MIGRATIONS_LOCK_NAME', 'tuky4_migrations_lock') ?? 'tuky4_migrations_lock',
    (int) (envValue('MIGRATIONS_LOCK_TIMEOUT', '10') ?? '10')
);

try {
    switch ($command) {
        case 'up':
            $runner->migrateUp();
            break;

        case 'status':
            $runner->status();
            break;

        case 'down':
            $steps = max(1, (int) ($options['step'] ?? 1));
            $runner->rollback($steps);
            break;

        default:
            fwrite(STDOUT, usage());
            exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, '[ERROR] ' . $e->getMessage() . "\n");
    exit(1);
}

function parseOptions(array $rawArgs): array
{
    $parsed = [];
    for ($i = 0; $i < count($rawArgs); $i++) {
        $arg = (string) $rawArgs[$i];
        if (str_starts_with($arg, '--')) {
            $arg = substr($arg, 2);
            if (str_contains($arg, '=')) {
                [$key, $value] = explode('=', $arg, 2);
                $parsed[$key] = $value;
                continue;
            }

            $next = $rawArgs[$i + 1] ?? true;
            if (is_string($next) && !str_starts_with($next, '--')) {
                $parsed[$arg] = $next;
                $i++;
            } else {
                $parsed[$arg] = true;
            }
        }
    }

    return $parsed;
}

function usage(): string
{
    return implode("\n", [
        'Uso:',
        '  php migrate.php up',
        '  php migrate.php status',
        '  php migrate.php down --step=1',
        '',
    ]);
}

final class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsPath;
    private string $table;
    private string $lockName;
    private int $lockTimeout;

    public function __construct(PDO $pdo, string $migrationsPath, string $table, string $lockName, int $lockTimeout)
    {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath;
        $this->table = $this->validateIdentifier($table, 'MIGRATIONS_TABLE');
        $this->lockName = trim($lockName) !== '' ? $lockName : 'tuky4_migrations_lock';
        $this->lockTimeout = max(1, $lockTimeout);
    }

    public function migrateUp(): void
    {
        $this->ensureMetadataTable();
        $this->acquireLock();

        try {
            $migrations = $this->loadMigrations();
            $applied = $this->getAppliedMap();
            $batch = $this->nextBatchNumber();
            $appliedNow = 0;

            foreach ($migrations as $migration) {
                $version = $migration['version'];
                $checksum = $migration['checksum'];

                if (isset($applied[$version])) {
                    if ($applied[$version]['checksum'] !== $checksum) {
                        throw new RuntimeException("Checksum cambiado para migracion aplicada: {$version}");
                    }
                    continue;
                }

                $this->applyMigration($migration, 'up');
                $this->markApplied($version, $checksum, $batch);
                $appliedNow++;
                fwrite(STDOUT, "[UP] {$version} - {$migration['description']}\n");
            }

            if ($appliedNow === 0) {
                fwrite(STDOUT, "No hay migraciones pendientes.\n");
            } else {
                fwrite(STDOUT, "Migraciones aplicadas: {$appliedNow}.\n");
            }
        } finally {
            $this->releaseLock();
        }
    }

    public function rollback(int $steps): void
    {
        $this->ensureMetadataTable();
        $this->acquireLock();

        try {
            $migrations = $this->loadMigrationsByVersion();

            for ($i = 0; $i < $steps; $i++) {
                $batch = $this->latestBatchNumber();
                if ($batch === null) {
                    fwrite(STDOUT, "No hay batches para revertir.\n");
                    return;
                }

                $versions = $this->getVersionsByBatchDesc($batch);
                foreach ($versions as $version) {
                    if (!isset($migrations[$version])) {
                        throw new RuntimeException("Falta el archivo de migracion para revertir: {$version}");
                    }

                    $migration = $migrations[$version];
                    $this->applyMigration($migration, 'down');
                    $this->unmarkApplied($version);
                    fwrite(STDOUT, "[DOWN] {$version} - {$migration['description']}\n");
                }
            }
        } finally {
            $this->releaseLock();
        }
    }

    public function status(): void
    {
        $this->ensureMetadataTable();
        $migrations = $this->loadMigrations();
        $applied = $this->getAppliedMap();

        if (!$migrations) {
            fwrite(STDOUT, "No hay archivos de migracion en {$this->migrationsPath}.\n");
            return;
        }

        foreach ($migrations as $migration) {
            $version = $migration['version'];
            $state = isset($applied[$version]) ? 'APPLIED' : 'PENDING';
            $batch = $applied[$version]['batch'] ?? '-';
            fwrite(STDOUT, sprintf("[%s] %s (batch: %s) - %s\n", $state, $version, (string) $batch, $migration['description']));
        }
    }

    private function ensureMetadataTable(): void
    {
        $table = $this->quoteIdentifier($this->table);
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(191) NOT NULL UNIQUE,
            checksum CHAR(64) NOT NULL,
            batch INT UNSIGNED NOT NULL,
            executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }

    private function acquireLock(): void
    {
        $stmt = $this->pdo->prepare('SELECT GET_LOCK(:name, :timeout)');
        $stmt->bindValue(':name', $this->lockName, PDO::PARAM_STR);
        $stmt->bindValue(':timeout', $this->lockTimeout, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchColumn();
        if ((int) $result !== 1) {
            throw new RuntimeException('No se pudo adquirir lock de migraciones.');
        }
    }

    private function releaseLock(): void
    {
        $stmt = $this->pdo->prepare('SELECT RELEASE_LOCK(:name)');
        $stmt->bindValue(':name', $this->lockName, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * @return array<int, array{version:string,description:string,up:array<int,string>,down:array<int,string>,transactional:bool,checksum:string,path:string}>
     */
    private function loadMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files, SORT_STRING);

        $migrations = [];
        $seen = [];

        foreach ($files as $file) {
            $definition = require $file;
            if (!is_array($definition)) {
                throw new RuntimeException("Migracion invalida: {$file}");
            }

            $version = (string) ($definition['version'] ?? pathinfo($file, PATHINFO_FILENAME));
            $description = trim((string) ($definition['description'] ?? $version));
            $up = $this->normalizeStatements($definition['up'] ?? null, 'up', $file);
            $down = $this->normalizeStatements($definition['down'] ?? [], 'down', $file);
            $transactional = (bool) ($definition['transactional'] ?? false);

            if (isset($seen[$version])) {
                throw new RuntimeException("Version duplicada de migracion: {$version}");
            }
            $seen[$version] = true;

            $migrations[] = [
                'version' => $version,
                'description' => $description,
                'up' => $up,
                'down' => $down,
                'transactional' => $transactional,
                'checksum' => hash_file('sha256', $file) ?: '',
                'path' => $file,
            ];
        }

        return $migrations;
    }

    /**
     * @return array<string, array{version:string,description:string,up:array<int,string>,down:array<int,string>,transactional:bool,checksum:string,path:string}>
     */
    private function loadMigrationsByVersion(): array
    {
        $list = $this->loadMigrations();
        $mapped = [];
        foreach ($list as $migration) {
            $mapped[$migration['version']] = $migration;
        }
        return $mapped;
    }

    /** @return array<string, array{checksum:string,batch:int}> */
    private function getAppliedMap(): array
    {
        $table = $this->quoteIdentifier($this->table);
        $sql = "SELECT version, checksum, batch FROM {$table} ORDER BY version ASC";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $applied = [];
        foreach ($rows as $row) {
            $applied[(string) $row['version']] = [
                'checksum' => (string) $row['checksum'],
                'batch' => (int) $row['batch'],
            ];
        }
        return $applied;
    }

    private function nextBatchNumber(): int
    {
        $table = $this->quoteIdentifier($this->table);
        $value = $this->pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM {$table}")->fetchColumn();
        return max(1, (int) $value);
    }

    private function latestBatchNumber(): ?int
    {
        $table = $this->quoteIdentifier($this->table);
        $value = $this->pdo->query("SELECT MAX(batch) FROM {$table}")->fetchColumn();
        if ($value === false || $value === null) {
            return null;
        }
        $batch = (int) $value;
        return $batch > 0 ? $batch : null;
    }

    /** @return array<int, string> */
    private function getVersionsByBatchDesc(int $batch): array
    {
        $table = $this->quoteIdentifier($this->table);
        $stmt = $this->pdo->prepare("SELECT version FROM {$table} WHERE batch = :batch ORDER BY version DESC");
        $stmt->bindValue(':batch', $batch, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function markApplied(string $version, string $checksum, int $batch): void
    {
        $table = $this->quoteIdentifier($this->table);
        $stmt = $this->pdo->prepare("INSERT INTO {$table} (version, checksum, batch) VALUES (:version, :checksum, :batch)");
        $stmt->bindValue(':version', $version, PDO::PARAM_STR);
        $stmt->bindValue(':checksum', $checksum, PDO::PARAM_STR);
        $stmt->bindValue(':batch', $batch, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function unmarkApplied(string $version): void
    {
        $table = $this->quoteIdentifier($this->table);
        $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE version = :version");
        $stmt->bindValue(':version', $version, PDO::PARAM_STR);
        $stmt->execute();
    }

    private function applyMigration(array $migration, string $direction): void
    {
        $statements = $migration[$direction] ?? [];
        if (!$statements) {
            return;
        }

        $transactional = (bool) ($migration['transactional'] ?? false);

        if ($transactional) {
            $this->pdo->beginTransaction();
        }

        try {
            foreach ($statements as $sql) {
                $trimmed = trim($sql);
                if ($trimmed === '') {
                    continue;
                }
                $this->pdo->exec($trimmed);
            }

            if ($transactional && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($transactional && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new RuntimeException(
                "Error en {$direction} para {$migration['version']}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /** @return array<int, string> */
    private function normalizeStatements(mixed $value, string $direction, string $file): array
    {
        if ($value === null) {
            if ($direction === 'down') {
                return [];
            }
            throw new RuntimeException("Falta bloque '{$direction}' en migracion {$file}");
        }

        if (is_string($value)) {
            return [trim($value)];
        }

        if (!is_array($value)) {
            throw new RuntimeException("Bloque '{$direction}' invalido en migracion {$file}");
        }

        $result = [];
        foreach ($value as $statement) {
            if (!is_string($statement)) {
                throw new RuntimeException("SQL invalido en bloque '{$direction}' de {$file}");
            }
            $result[] = trim($statement);
        }

        return $result;
    }

    private function validateIdentifier(string $value, string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $value)) {
            throw new RuntimeException("{$name} contiene caracteres invalidos.");
        }
        return $value;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
