<?php
// ─── Conexión y esquema de base de datos ──────────────────────────────────────
$db_file = __DIR__ . '/gym_track.db';
$pdo = new PDO('sqlite:' . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS exercises (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS records (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    exercise_id INTEGER NOT NULL,
    weight      REAL    NOT NULL,
    series      INTEGER NOT NULL,
    rep_pattern TEXT    NOT NULL,
    date        DATE    DEFAULT (date('now')),
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
)");

// ─── Migraciones ──────────────────────────────────────────────────────────────
try { $pdo->exec("ALTER TABLE exercises ADD COLUMN routine TEXT NOT NULL DEFAULT 'PECHO'"); } catch (Exception $e) {}
