<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use Medoo\Medoo;
use PDO;

final class TaskApiController extends BaseApiController
{
    private const OPEN_STATUSES = ['inbox', 'next', 'waiting', 'someday'];
    private const UPCOMING_DAYS = 7;
    private const DONE_LIMIT = 100;

    public function dashboard(): void
    {
        $this->run('dashboardAction');
    }

    public function listTasks(): void
    {
        $this->run('listTasksAction');
    }

    public function addTask(): void
    {
        $this->run('addTaskAction');
    }

    public function updateTask(): void
    {
        $this->run('updateTaskAction');
    }

    public function completeTask(): void
    {
        $this->run('completeTaskAction');
    }

    public function deleteTask(): void
    {
        $this->run('deleteTaskAction');
    }

    /** @return array<string,mixed> */
    protected function dashboardAction(): array
    {
        $in = $this->input();
        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        if (!$this->isValidDate($date)) {
            return ['ok' => false, 'msg' => 'Fecha invalida'];
        }

        $open = $this->fetchTasks("t.status <> 'done'");
        $active = array_values(array_filter($open, static fn (array $t): bool => !$t['blocked']));
        $limit = date('Y-m-d', strtotime($date . ' +' . self::UPCOMING_DAYS . ' days') ?: time());

        $today = array_values(array_filter(
            $active,
            static fn (array $t): bool => $t['scheduled_date'] !== null && $t['scheduled_date'] <= $date
        ));
        $upcoming = array_values(array_filter(
            $active,
            static fn (array $t): bool => $t['scheduled_date'] !== null && $t['scheduled_date'] > $date && $t['scheduled_date'] <= $limit
        ));
        $next = array_values(array_filter(
            $active,
            static fn (array $t): bool => $t['status'] === 'next' && $t['scheduled_date'] === null
        ));

        $countStatus = static fn (string $status): int => count(array_filter($open, static fn (array $t): bool => $t['status'] === $status));

        $doneToday = (int) $this->medoo()->count('task_items', [
            'status' => 'done',
            'completed_at[>=]' => $date . ' 00:00:00',
            'completed_at[<=]' => $date . ' 23:59:59',
        ]);

        return [
            'ok' => true,
            'data' => [
                'date' => $date,
                'today' => $this->sortTasks($today),
                'upcoming' => $this->sortTasks($upcoming),
                'next' => $this->sortTasks($next),
                'kpis' => [
                    'today' => count($today),
                    'overdue' => count(array_filter($today, static fn (array $t): bool => $t['scheduled_date'] < $date)),
                    'inbox' => $countStatus('inbox'),
                    'next' => count($next),
                    'waiting' => $countStatus('waiting'),
                    'someday' => $countStatus('someday'),
                    'blocked' => count($open) - count($active),
                    'done_today' => $doneToday,
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    protected function listTasksAction(): array
    {
        $filter = trim((string) ($this->input()['filter'] ?? 'open'));

        $where = match ($filter) {
            'inbox', 'next', 'waiting', 'someday' => "t.status = '{$filter}'",
            'scheduled' => "t.status <> 'done' AND t.scheduled_date IS NOT NULL",
            'done' => "t.status = 'done'",
            'open' => "t.status <> 'done'",
            default => null,
        };
        if ($where === null) {
            return ['ok' => false, 'msg' => 'Filtro invalido'];
        }

        if ($filter === 'done') {
            $rows = $this->fetchTasks($where, [], 't.completed_at DESC, t.id DESC LIMIT ' . self::DONE_LIMIT);
            return ['ok' => true, 'data' => $rows];
        }

        return ['ok' => true, 'data' => $this->sortTasks($this->fetchTasks($where))];
    }

    /** @return array<string,mixed> */
    protected function addTaskAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $fields = $this->parseFields($in, null);
        if (isset($fields['error'])) {
            return ['ok' => false, 'msg' => $fields['error']];
        }

        $payload = $fields['values'];
        if ($payload['parent_id'] !== null && !$db->has('task_items', ['id' => $payload['parent_id']])) {
            return ['ok' => false, 'msg' => 'La tarea origen no existe'];
        }

        $db->insert('task_items', $payload);

        return ['ok' => true, 'data' => ['id' => (int) $this->lastInsertId()]];
    }

    /** @return array<string,mixed> */
    protected function updateTaskAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        $current = $id > 0 ? $db->get('task_items', '*', ['id' => $id]) : null;
        if (!is_array($current)) {
            return ['ok' => false, 'msg' => 'Tarea no encontrada'];
        }

        $fields = $this->parseFields($in, $current);
        if (isset($fields['error'])) {
            return ['ok' => false, 'msg' => $fields['error']];
        }

        $payload = $fields['values'];
        $parentId = $payload['parent_id'];
        if ($parentId !== null) {
            if (!$db->has('task_items', ['id' => $parentId])) {
                return ['ok' => false, 'msg' => 'La tarea origen no existe'];
            }
            if ($this->createsCycle($db, $id, $parentId)) {
                return ['ok' => false, 'msg' => 'Una tarea no puede derivar de si misma o de sus derivadas'];
            }
        }

        $db->update('task_items', $payload, ['id' => $id]);

        return ['ok' => true, 'data' => ['id' => $id]];
    }

    /** @return array<string,mixed> */
    protected function completeTaskAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        if ($id <= 0 || !$db->has('task_items', ['id' => $id])) {
            return ['ok' => false, 'msg' => 'Tarea no encontrada'];
        }

        $done = (bool) ($in['done'] ?? true);
        $db->update('task_items', [
            'status' => $done ? 'done' : 'next',
            'completed_at' => $done ? date('Y-m-d H:i:s') : null,
        ], ['id' => $id]);

        $unlocked = $done
            ? (int) $db->count('task_items', ['parent_id' => $id, 'status[!]' => 'done'])
            : 0;

        return ['ok' => true, 'data' => ['id' => $id, 'unlocked' => $unlocked]];
    }

    /** @return array<string,mixed> */
    protected function deleteTaskAction(): array
    {
        $id = (int) ($this->input()['id'] ?? 0);
        $db = $this->medoo();

        if ($id <= 0 || !$db->has('task_items', ['id' => $id])) {
            return ['ok' => false, 'msg' => 'Tarea no encontrada'];
        }

        // SQLite no aplica la FK ON DELETE SET NULL.
        $db->update('task_items', ['parent_id' => null], ['parent_id' => $id]);
        $db->delete('task_items', ['id' => $id]);

        return ['ok' => true, 'data' => ['id' => $id]];
    }

    /**
     * Valida y normaliza los campos de una tarea. Con $current se fusionan sobre la tarea existente.
     *
     * @param array<string,mixed>|null $current
     * @return array{values:array<string,mixed>}|array{error:string}
     */
    private function parseFields(array $in, ?array $current): array
    {
        $has = static fn (string $key): bool => array_key_exists($key, $in);

        $title = $has('title') ? trim((string) $in['title']) : (string) ($current['title'] ?? '');
        if ($title === '') {
            return ['error' => 'El titulo es obligatorio'];
        }
        if (mb_strlen($title) > 200) {
            return ['error' => 'El titulo es demasiado largo (max. 200)'];
        }

        $notes = $has('notes') ? trim((string) $in['notes']) : (string) ($current['notes'] ?? '');
        if (mb_strlen($notes) > 500) {
            return ['error' => 'Las notas son demasiado largas (max. 500)'];
        }

        $context = $has('context') ? trim((string) $in['context']) : (string) ($current['context'] ?? '');
        $context = ltrim($context, '@ ');
        if (mb_strlen($context) > 60) {
            return ['error' => 'El contexto es demasiado largo (max. 60)'];
        }

        $date = $has('scheduled_date') ? trim((string) ($in['scheduled_date'] ?? '')) : (string) ($current['scheduled_date'] ?? '');
        if ($date !== '' && !$this->isValidDate($date)) {
            return ['error' => 'Fecha invalida'];
        }

        $parentRaw = $has('parent_id') ? $in['parent_id'] : ($current['parent_id'] ?? null);
        $parentId = (int) $parentRaw > 0 ? (int) $parentRaw : null;

        $status = $has('status') ? trim((string) $in['status']) : (string) ($current['status'] ?? 'inbox');
        $isDone = ($current['status'] ?? '') === 'done' && !$has('status');
        if (!$isDone) {
            if (!in_array($status, self::OPEN_STATUSES, true)) {
                return ['error' => 'Estado invalido'];
            }
            // Una tarea fechada o derivada ya esta clarificada, no puede quedarse en inbox/algun dia.
            if (in_array($status, ['inbox', 'someday'], true) && ($date !== '' || $parentId !== null)) {
                $status = 'next';
            }
        }

        return ['values' => [
            'title' => $title,
            'notes' => $notes !== '' ? $notes : null,
            'status' => $isDone ? 'done' : $status,
            'scheduled_date' => $date !== '' ? $date : null,
            'context' => $context !== '' ? $context : null,
            'parent_id' => $parentId,
        ]];
    }

    private function createsCycle(Medoo $db, int $id, int $parentId): bool
    {
        $seen = [];
        $cursor = $parentId;
        while ($cursor > 0 && !isset($seen[$cursor])) {
            if ($cursor === $id) {
                return true;
            }
            $seen[$cursor] = true;
            $cursor = (int) ($db->get('task_items', 'parent_id', ['id' => $cursor]) ?? 0);
        }

        return false;
    }

    /**
     * @param array<int,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    private function fetchTasks(string $where, array $params = [], string $order = 't.id DESC'): array
    {
        $sql = "SELECT t.id, t.title, t.notes, t.status, t.scheduled_date, t.context, t.parent_id, t.completed_at, t.created_at,
                    p.title AS parent_title, p.status AS parent_status,
                    (SELECT COUNT(*) FROM task_items c WHERE c.parent_id = t.id) AS children_count,
                    (SELECT COUNT(*) FROM task_items c WHERE c.parent_id = t.id AND c.status <> 'done') AS children_open
                FROM task_items t
                LEFT JOIN task_items p ON p.id = t.parent_id
                WHERE {$where}
                ORDER BY {$order}";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $row): array {
            $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            return [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'notes' => $row['notes'] !== null ? (string) $row['notes'] : '',
                'status' => (string) $row['status'],
                'scheduled_date' => $row['scheduled_date'] !== null ? (string) $row['scheduled_date'] : null,
                'context' => $row['context'] !== null ? (string) $row['context'] : '',
                'parent_id' => $parentId,
                'parent_title' => $row['parent_title'] !== null ? (string) $row['parent_title'] : null,
                'children_count' => (int) $row['children_count'],
                'children_open' => (int) $row['children_open'],
                'blocked' => $parentId !== null && $row['status'] !== 'done' && $row['parent_status'] !== 'done',
                'completed_at' => $row['completed_at'],
                'created_at' => $row['created_at'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Con fecha primero (la mas antigua arriba), despues sin fecha por mas recientes.
     *
     * @param array<int,array<string,mixed>> $tasks
     * @return array<int,array<string,mixed>>
     */
    private function sortTasks(array $tasks): array
    {
        usort($tasks, static function (array $a, array $b): int {
            $da = $a['scheduled_date'];
            $db = $b['scheduled_date'];
            if ($da !== $db) {
                if ($da === null) {
                    return 1;
                }
                if ($db === null) {
                    return -1;
                }
                return strcmp($da, $db);
            }
            return $b['id'] <=> $a['id'];
        });

        return $tasks;
    }

    private function isValidDate(string $date): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }
}
