<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\JsonResponse;
use Exception;
use PDO;

final class ApiController
{
    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        try {
            $pdo = Database::connection();
            $in = $this->readInput();
            $action = $in['action'] ?? '';

            $response = match ($action) {
                'add_exercise' => $this->addExercise($pdo, $in),
                'get_exercises' => $this->getExercises($pdo),
                'add_record' => $this->addRecord($pdo, $in),
                'get_history' => $this->getHistory($pdo, $in),
                'delete_exercise' => $this->deleteExercise($pdo, $in),
                'delete_record' => $this->deleteRecord($pdo, $in),
                default => ['ok' => false, 'msg' => 'Accion desconocida'],
            };

            JsonResponse::send($response);
        } catch (Exception $e) {
            JsonResponse::error($e->getMessage(), 500);
        }

        exit;
    }

    private function readInput(): array
    {
        $decoded = json_decode(file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, mixed> */
    private function addExercise(PDO $pdo, array $in): array
    {
        $name = trim((string) ($in['name'] ?? ''));
        $routine = trim((string) ($in['routine'] ?? 'PECHO'));
        $allowed = ['PECHO', 'ESPALDA', 'PIERNAS'];

        if (!in_array($routine, $allowed, true)) {
            $routine = 'PECHO';
        }

        if ($name === '') {
            return ['ok' => false, 'msg' => 'Nombre vacio'];
        }

        $s = $pdo->prepare('INSERT INTO exercises (name, routine) VALUES (?, ?)');
        $s->execute([$name, $routine]);

        return [
            'ok' => true,
            'id' => $pdo->lastInsertId(),
            'name' => $name,
            'routine' => $routine,
        ];
    }

    /** @return array<string, mixed> */
    private function getExercises(PDO $pdo): array
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

        return ['ok' => true, 'data' => $rows];
    }

    /** @return array<string, mixed> */
    private function addRecord(PDO $pdo, array $in): array
    {
        $eid = (int) ($in['exercise_id'] ?? 0);
        $weight = (float) ($in['weight'] ?? 0);
        $series = (int) ($in['series'] ?? 0);
        $pattern = trim((string) ($in['rep_pattern'] ?? ''));
        $date = trim((string) ($in['date'] ?? date('Y-m-d')));

        if (!$eid || !$series || $pattern === '') {
            return ['ok' => false, 'msg' => 'Datos incompletos'];
        }

        $s = $pdo->prepare('INSERT INTO records (exercise_id, weight, series, rep_pattern, date) VALUES (?, ?, ?, ?, ?)');
        $s->execute([$eid, $weight, $series, $pattern, $date]);
        return ['ok' => true, 'id' => $pdo->lastInsertId()];
    }

    /** @return array<string, mixed> */
    private function getHistory(PDO $pdo, array $in): array
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
        return ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /** @return array<string, mixed> */
    private function deleteExercise(PDO $pdo, array $in): array
    {
        $eid = (int) ($in['exercise_id'] ?? 0);
        $pdo->prepare('DELETE FROM records WHERE exercise_id = ?')->execute([$eid]);
        $pdo->prepare('DELETE FROM exercises WHERE id = ?')->execute([$eid]);
        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    private function deleteRecord(PDO $pdo, array $in): array
    {
        $rid = (int) ($in['record_id'] ?? 0);
        $pdo->prepare('DELETE FROM records WHERE id = ?')->execute([$rid]);
        return ['ok' => true];
    }
}
