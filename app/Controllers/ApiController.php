<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use PDO;

final class ApiController
{
    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        require __DIR__ . '/../../db.php';

        header('Content-Type: application/json');
        $in = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $in['action'] ?? '';

        try {
            switch ($action) {
                case 'add_exercise':
                    $this->addExercise($pdo, $in);
                    break;

                case 'get_exercises':
                    $this->getExercises($pdo);
                    break;

                case 'add_record':
                    $this->addRecord($pdo, $in);
                    break;

                case 'get_history':
                    $this->getHistory($pdo, $in);
                    break;

                case 'delete_exercise':
                    $this->deleteExercise($pdo, $in);
                    break;

                case 'delete_record':
                    $this->deleteRecord($pdo, $in);
                    break;

                default:
                    echo json_encode(['ok' => false, 'msg' => 'Accion desconocida']);
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }

        exit;
    }

    private function addExercise(PDO $pdo, array $in): void
    {
        $name = trim((string) ($in['name'] ?? ''));
        $routine = trim((string) ($in['routine'] ?? 'PECHO'));
        $allowed = ['PECHO', 'ESPALDA', 'PIERNAS'];

        if (!in_array($routine, $allowed, true)) {
            $routine = 'PECHO';
        }

        if ($name === '') {
            echo json_encode(['ok' => false, 'msg' => 'Nombre vacio']);
            return;
        }

        $s = $pdo->prepare('INSERT INTO exercises (name, routine) VALUES (?, ?)');
        $s->execute([$name, $routine]);

        echo json_encode([
            'ok' => true,
            'id' => $pdo->lastInsertId(),
            'name' => $name,
            'routine' => $routine,
        ]);
    }

    private function getExercises(PDO $pdo): void
    {
        $rows = $pdo->query("
            SELECT e.id, e.name, e.routine,
                   r.weight, r.series, r.rep_pattern, r.date
            FROM exercises e
            LEFT JOIN records r ON r.id = (
                SELECT id FROM records
                WHERE exercise_id = e.id
                ORDER BY date DESC, id DESC LIMIT 1
            )
            ORDER BY e.routine, e.name
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'data' => $rows]);
    }

    private function addRecord(PDO $pdo, array $in): void
    {
        $eid = (int) ($in['exercise_id'] ?? 0);
        $weight = (float) ($in['weight'] ?? 0);
        $series = (int) ($in['series'] ?? 0);
        $pattern = trim((string) ($in['rep_pattern'] ?? ''));
        $date = trim((string) ($in['date'] ?? date('Y-m-d')));

        if (!$eid || !$series || $pattern === '') {
            echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
            return;
        }

        $s = $pdo->prepare('INSERT INTO records (exercise_id, weight, series, rep_pattern, date) VALUES (?, ?, ?, ?, ?)');
        $s->execute([$eid, $weight, $series, $pattern, $date]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    private function getHistory(PDO $pdo, array $in): void
    {
        $eid = (int) ($in['exercise_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT r.id, r.weight, r.series, r.rep_pattern, r.date
            FROM records r
            WHERE r.exercise_id = ?
            ORDER BY r.date DESC, r.id DESC
            LIMIT 20
        ");

        $stmt->execute([$eid]);
        echo json_encode(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    private function deleteExercise(PDO $pdo, array $in): void
    {
        $eid = (int) ($in['exercise_id'] ?? 0);
        $pdo->prepare('DELETE FROM records WHERE exercise_id = ?')->execute([$eid]);
        $pdo->prepare('DELETE FROM exercises WHERE id = ?')->execute([$eid]);
        echo json_encode(['ok' => true]);
    }

    private function deleteRecord(PDO $pdo, array $in): void
    {
        $rid = (int) ($in['record_id'] ?? 0);
        $pdo->prepare('DELETE FROM records WHERE id = ?')->execute([$rid]);
        echo json_encode(['ok' => true]);
    }
}
