<?php
declare(strict_types=1);

namespace App\Controllers\Api;

final class GymApiController extends BaseApiController
{
    public function listActivities(): void
    {
        $this->run('listActivitiesAction');
    }

    public function addActivity(): void
    {
        $this->run('addActivityAction');
    }

    public function addActivityRecord(): void
    {
        $this->run('addActivityRecordAction');
    }

    public function getActivityHistory(): void
    {
        $this->run('getActivityHistoryAction');
    }

    public function deleteActivity(): void
    {
        $this->run('deleteActivityAction');
    }

    public function deleteActivityRecord(): void
    {
        $this->run('deleteActivityRecordAction');
    }

    public function listExercises(): void
    {
        $this->run('listExercisesAction');
    }

    public function addExercise(): void
    {
        $this->run('addExerciseAction');
    }

    public function addRecord(): void
    {
        $this->run('addRecordAction');
    }

    public function getHistory(): void
    {
        $this->run('getHistoryAction');
    }

    public function deleteExercise(): void
    {
        $this->run('deleteExerciseAction');
    }

    public function deleteRecord(): void
    {
        $this->run('deleteRecordAction');
    }

    /** @return array<string, mixed> */
    protected function listActivitiesAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $type = strtolower(trim((string) ($in['type'] ?? '')));
        $allowedTypes = ['bike', 'running'];

        $where = [
            'ORDER' => ['created_at' => 'DESC', 'id' => 'DESC'],
        ];

        if ($type !== '') {
            if (!in_array($type, $allowedTypes, true)) {
                return ['ok' => false, 'msg' => 'Tipo invalido'];
            }
            $where['type'] = $type;
        }

        $rows = $db->select('activities', ['id', 'name', 'type', 'created_at'], $where);
        return ['ok' => true, 'data' => $rows ?: []];
    }

    /** @return array<string, mixed> */
    protected function addActivityAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $name = trim((string) ($in['name'] ?? ''));
        $type = strtolower(trim((string) ($in['type'] ?? '')));
        $allowedTypes = ['bike', 'running'];

        if ($name === '') {
            return ['ok' => false, 'msg' => 'Nombre vacio'];
        }
        if (!in_array($type, $allowedTypes, true)) {
            return ['ok' => false, 'msg' => 'Tipo invalido'];
        }

        $db->insert('activities', [
            'name' => $name,
            'type' => $type,
        ]);

        return [
            'ok' => true,
            'id' => $this->lastInsertId(),
            'name' => $name,
            'type' => $type,
        ];
    }

    /** @return array<string, mixed> */
    protected function addActivityRecordAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $activityId = (int) ($in['activity_id'] ?? 0);
        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        $distance = (float) ($in['distance'] ?? 0);
        $avgSpeed = (float) ($in['avg_speed'] ?? 0);
        $elevation = (int) ($in['elevation'] ?? 0);
        $duration = (int) ($in['duration'] ?? 0);

        if ($activityId <= 0) {
            return ['ok' => false, 'msg' => 'Actividad invalida'];
        }
        if ($distance <= 0 || $avgSpeed <= 0 || $duration <= 0) {
            return ['ok' => false, 'msg' => 'Distancia, velocidad y duracion deben ser mayores que 0'];
        }

        $exists = $db->has('activities', ['id' => $activityId]);
        if (!$exists) {
            return ['ok' => false, 'msg' => 'Actividad no encontrada'];
        }

        $db->insert('activities_records', [
            'activity_id' => $activityId,
            'date' => $date,
            'distance' => $distance,
            'avg_speed' => $avgSpeed,
            'elevation' => $elevation,
            'duration' => $duration,
        ]);

        return ['ok' => true, 'id' => $this->lastInsertId()];
    }

    /** @return array<string, mixed> */
    protected function getActivityHistoryAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();
        $activityId = (int) ($in['activity_id'] ?? 0);

        if ($activityId <= 0) {
            return ['ok' => false, 'msg' => 'Actividad invalida'];
        }

        $rows = $db->select('activities_records', [
            'id',
            'activity_id',
            'date',
            'distance',
            'avg_speed',
            'elevation',
            'duration',
        ], [
            'activity_id' => $activityId,
            'ORDER' => ['date' => 'DESC', 'id' => 'DESC'],
            'LIMIT' => 50,
        ]);

        return ['ok' => true, 'data' => $rows ?: []];
    }

    /** @return array<string, mixed> */
    protected function deleteActivityAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $activityId = (int) ($in['activity_id'] ?? 0);
        if ($activityId <= 0) {
            return ['ok' => false, 'msg' => 'Actividad invalida'];
        }

        $db->delete('activities', ['id' => $activityId]);
        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    protected function deleteActivityRecordAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $recordId = (int) ($in['record_id'] ?? 0);
        if ($recordId <= 0) {
            return ['ok' => false, 'msg' => 'Registro invalido'];
        }

        $db->delete('activities_records', ['id' => $recordId]);
        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    protected function listExercisesAction(): array
    {
        $db = $this->medoo();

        $exercises = $db->select('exercises', ['id', 'name', 'routine'], [
            'ORDER' => ['routine' => 'ASC', 'name' => 'ASC'],
        ]);

        if (!$exercises) {
            return ['ok' => true, 'data' => []];
        }

        $exerciseIds = array_map(static fn (array $e): int => (int) $e['id'], $exercises);
        $records = $db->select('records', ['id', 'exercise_id', 'weight', 'series', 'rep_pattern', 'date'], [
            'exercise_id' => $exerciseIds,
            'ORDER' => ['date' => 'DESC', 'id' => 'DESC'],
        ]);

        $latestByExercise = [];
        foreach ($records as $record) {
            $eid = (int) $record['exercise_id'];
            if (!isset($latestByExercise[$eid])) {
                $latestByExercise[$eid] = $record;
            }
        }

        $rows = [];
        foreach ($exercises as $exercise) {
            $eid = (int) $exercise['id'];
            $record = $latestByExercise[$eid] ?? null;

            $rows[] = [
                'id' => $eid,
                'name' => $exercise['name'],
                'routine' => $exercise['routine'],
                'weight' => $record['weight'] ?? null,
                'series' => $record['series'] ?? null,
                'rep_pattern' => $record['rep_pattern'] ?? null,
                'date' => $record['date'] ?? null,
            ];
        }

        return ['ok' => true, 'data' => $rows];
    }

    /** @return array<string, mixed> */
    protected function addExerciseAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $name = trim((string) ($in['name'] ?? ''));
        $routine = trim((string) ($in['routine'] ?? 'PECHO'));
        $allowed = ['PECHO', 'ESPALDA', 'PIERNAS'];

        if (!in_array($routine, $allowed, true)) {
            $routine = 'PECHO';
        }

        if ($name === '') {
            return ['ok' => false, 'msg' => 'Nombre vacio'];
        }

        $db->insert('exercises', [
            'name' => $name,
            'routine' => $routine,
        ]);

        return [
            'ok' => true,
            'id' => $this->lastInsertId(),
            'name' => $name,
            'routine' => $routine,
        ];
    }

    /** @return array<string, mixed> */
    protected function addRecordAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $eid = (int) ($in['exercise_id'] ?? 0);
        $weight = (float) ($in['weight'] ?? 0);
        $series = (int) ($in['series'] ?? 0);
        $pattern = trim((string) ($in['rep_pattern'] ?? ''));
        $date = trim((string) ($in['date'] ?? date('Y-m-d')));

        if (!$eid || !$series || $pattern === '') {
            return ['ok' => false, 'msg' => 'Datos incompletos'];
        }

        $db->insert('records', [
            'exercise_id' => $eid,
            'weight' => $weight,
            'series' => $series,
            'rep_pattern' => $pattern,
            'date' => $date,
        ]);

        return ['ok' => true, 'id' => $this->lastInsertId()];
    }

    /** @return array<string, mixed> */
    protected function getHistoryAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();
        $eid = (int) ($in['exercise_id'] ?? 0);

        $rows = $db->select('records', ['id', 'weight', 'series', 'rep_pattern', 'date'], [
            'exercise_id' => $eid,
            'ORDER' => ['date' => 'DESC', 'id' => 'DESC'],
            'LIMIT' => 20,
        ]);

        return ['ok' => true, 'data' => $rows ?: []];
    }

    /** @return array<string, mixed> */
    protected function deleteExerciseAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $eid = (int) ($in['exercise_id'] ?? 0);
        $db->delete('records', ['exercise_id' => $eid]);
        $db->delete('exercises', ['id' => $eid]);
        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    protected function deleteRecordAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $rid = (int) ($in['record_id'] ?? 0);
        $db->delete('records', ['id' => $rid]);
        return ['ok' => true];
    }
}
