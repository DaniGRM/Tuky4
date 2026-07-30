<?php
// ─── API: maneja todas las peticiones POST y termina la ejecución ─────────────
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return; // Dejar que index.php continúe renderizando el HTML
}

header('Content-Type: application/json');
$in     = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $in['action'] ?? '';

try {
    switch ($action) {

        case 'add_exercise': {
            $name    = trim($in['name'] ?? '');
            $routine = trim($in['routine'] ?? 'PECHO');
            $allowed = ['PECHO', 'ESPALDA', 'PIERNAS'];
            if (!in_array($routine, $allowed)) $routine = 'PECHO';
            if ($name === '') {
                echo json_encode(['ok' => false, 'msg' => 'Nombre vacío']);
                exit;
            }
            $s = $pdo->prepare("INSERT INTO exercises (name, routine) VALUES (?, ?)");
            $s->execute([$name, $routine]);
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId(), 'name' => $name, 'routine' => $routine]);
            break;
        }

        case 'get_exercises': {
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
            break;
        }

        case 'add_record': {
            $eid     = intval($in['exercise_id'] ?? 0);
            $weight  = floatval($in['weight'] ?? 0);
            $series  = intval($in['series'] ?? 0);
            $pattern = trim($in['rep_pattern'] ?? '');
            $date    = trim($in['date'] ?? date('Y-m-d'));
            if (!$eid || !$series || $pattern === '') {
                echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
                exit;
            }
            $s = $pdo->prepare("INSERT INTO records (exercise_id, weight, series, rep_pattern, date) VALUES (?, ?, ?, ?, ?)");
            $s->execute([$eid, $weight, $series, $pattern, $date]);
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
            break;
        }

        case 'get_history': {
            $eid  = intval($in['exercise_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT r.id, r.weight, r.series, r.rep_pattern, r.date
                FROM records r
                WHERE r.exercise_id = ?
                ORDER BY r.date DESC, r.id DESC
                LIMIT 20
            ");
            $stmt->execute([$eid]);
            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        }

        case 'delete_exercise': {
            $eid = intval($in['exercise_id'] ?? 0);
            $pdo->prepare("DELETE FROM records WHERE exercise_id = ?")->execute([$eid]);
            $pdo->prepare("DELETE FROM exercises WHERE id = ?")->execute([$eid]);
            echo json_encode(['ok' => true]);
            break;
        }

        case 'delete_record': {
            $rid = intval($in['record_id'] ?? 0);
            $pdo->prepare("DELETE FROM records WHERE id = ?")->execute([$rid]);
            echo json_encode(['ok' => true]);
            break;
        }

        default:
            echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
exit;
