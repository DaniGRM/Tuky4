<?php
declare(strict_types=1);

namespace App\Controllers\Api;

final class GymApiController extends BaseApiController
{
    public function getUserInfo(): void
    {
        $this->run('getUserInfoAction');
    }

    public function saveUserInfo(): void
    {
        $this->run('saveUserInfoAction');
    }

    public function getSteps(): void
    {
        $this->run('getStepsAction');
    }

    public function saveSteps(): void
    {
        $this->run('saveStepsAction');
    }

    public function getTrainingDays(): void
    {
        $this->run('getTrainingDaysAction');
    }

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

    public function listSports(): void
    {
        $this->run('listSportsAction');
    }

    public function addSport(): void
    {
        $this->run('addSportAction');
    }

    public function addSportRegister(): void
    {
        $this->run('addSportRegisterAction');
    }

    public function getSportHistory(): void
    {
        $this->run('getSportHistoryAction');
    }

    public function deleteSport(): void
    {
        $this->run('deleteSportAction');
    }

    public function deleteSportRegister(): void
    {
        $this->run('deleteSportRegisterAction');
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
    protected function getUserInfoAction(): array
    {
        $db = $this->medoo();

        $row = $db->get('user_info', [
            'id',
            'daily_steps_goal',
            'calorie_deficit_goal',
            'sex',
            'height_cm',
            'age',
        ], [
            'ORDER' => ['id' => 'ASC'],
        ]);

        return [
            'ok' => true,
            'data' => [
                'daily_steps_goal' => $row ? (int) ($row['daily_steps_goal'] ?? 0) : 0,
                'calorie_deficit_goal' => $row ? (int) ($row['calorie_deficit_goal'] ?? 0) : 0,
                'sex' => $row ? (string) ($row['sex'] ?? 'male') : 'male',
                'height_cm' => $row ? (float) ($row['height_cm'] ?? 170) : 170,
                'age' => $row ? (int) ($row['age'] ?? 30) : 30,
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function saveUserInfoAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $dailyStepsGoal = max(0, (int) ($in['daily_steps_goal'] ?? 0));
        $calorieDeficitGoal = (int) ($in['calorie_deficit_goal'] ?? 0);
        $sex = strtolower(trim((string) ($in['sex'] ?? 'male')));
        $heightCm = (float) ($in['height_cm'] ?? 0);
        $age = (int) ($in['age'] ?? 0);

        if (!in_array($sex, ['male', 'female'], true)) {
            return ['ok' => false, 'msg' => 'Sexo invalido'];
        }
        if ($heightCm <= 0 || $heightCm > 260) {
            return ['ok' => false, 'msg' => 'Altura invalida'];
        }
        if ($age <= 0 || $age > 120) {
            return ['ok' => false, 'msg' => 'Edad invalida'];
        }

        $payload = [
            'daily_steps_goal' => $dailyStepsGoal,
            'calorie_deficit_goal' => $calorieDeficitGoal,
            'sex' => $sex,
            'height_cm' => round($heightCm, 2),
            'age' => $age,
        ];

        $id = $db->get('user_info', 'id', ['ORDER' => ['id' => 'ASC']]);
        if ($id) {
            $db->update('user_info', $payload, ['id' => (int) $id]);
        } else {
            $db->insert('user_info', $payload);
        }

        return ['ok' => true, 'data' => $payload];
    }

    /** @return array<string, mixed> */
    protected function getStepsAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'msg' => 'Fecha invalida'];
        }

        $row = $db->get('user_info_register', ['id', 'steps', 'weight'], ['date' => $date]);
        $steps = $row ? (int) ($row['steps'] ?? 0) : 0;
        $weight = $row && $row['weight'] !== null ? (float) $row['weight'] : 0.0;

        if ($weight <= 0) {
            $lastWeightRow = $db->get('user_info_register', ['weight'], [
                'weight[!]' => null,
                'weight[>]' => 0,
                'date[<=]' => $date,
                'ORDER' => ['date' => 'DESC', 'id' => 'DESC'],
            ]);

            if ($lastWeightRow && isset($lastWeightRow['weight'])) {
                $weight = (float) $lastWeightRow['weight'];
            }
        }

        return [
            'ok' => true,
            'data' => [
                'date' => $date,
                'steps' => $steps,
                'weight' => max(0.0, $weight),
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function saveStepsAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        $steps = (int) ($in['steps'] ?? 0);
        $hasWeight = array_key_exists('weight', $in);
        $weight = $hasWeight ? (float) ($in['weight'] ?? 0) : null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'msg' => 'Fecha invalida'];
        }
        if ($steps < 0) {
            return ['ok' => false, 'msg' => 'Pasos invalidos'];
        }
        if ($hasWeight && $weight !== null && $weight < 0) {
            return ['ok' => false, 'msg' => 'Peso invalido'];
        }

        $existingId = $db->get('user_info_register', 'id', ['date' => $date]);
        $saveData = ['steps' => $steps];
        if ($hasWeight) {
            $saveData['weight'] = $weight;
        }

        if ($existingId) {
            $db->update('user_info_register', $saveData, ['id' => (int) $existingId]);
        } else {
            $insertData = [
                'date' => $date,
                'steps' => $steps,
                'kcal_consumed' => 0,
                'kcal_burned' => 0,
            ];
            if ($hasWeight) {
                $insertData['weight'] = $weight;
            }
            $db->insert('user_info_register', $insertData);
        }

        $saved = $db->get('user_info_register', ['steps', 'weight'], ['date' => $date]);

        return [
            'ok' => true,
            'data' => [
                'date' => $date,
                'steps' => (int) ($saved['steps'] ?? $steps),
                'weight' => isset($saved['weight']) && $saved['weight'] !== null ? (float) $saved['weight'] : 0,
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function getTrainingDaysAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $endDate = trim((string) ($in['end_date'] ?? date('Y-m-d')));
        $startDate = trim((string) ($in['start_date'] ?? date('Y-m-d', strtotime($endDate . ' -6 days'))));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            return ['ok' => false, 'msg' => 'Rango de fechas invalido'];
        }
        if ($startDate > $endDate) {
            return ['ok' => false, 'msg' => 'El inicio debe ser anterior o igual al fin'];
        }

        $rows = [];
        $cursor = strtotime($startDate);
        $end = strtotime($endDate);
        while ($cursor !== false && $cursor <= $end) {
            $date = date('Y-m-d', $cursor);
            $rows[$date] = [
                'date' => $date,
                'gym' => false,
                'activity' => false,
            ];
            $cursor = strtotime('+1 day', $cursor);
        }

        $strengthDates = $db->select('records', ['date'], [
            'date[>=]' => $startDate,
            'date[<=]' => $endDate,
            'GROUP' => 'date',
        ]);
        foreach ($strengthDates as $row) {
            $date = (string) ($row['date'] ?? '');
            if (isset($rows[$date])) {
                $rows[$date]['gym'] = true;
            }
        }

        $activityDates = $db->select('activities_records', ['date'], [
            'date[>=]' => $startDate,
            'date[<=]' => $endDate,
            'GROUP' => 'date',
        ]);
        foreach ($activityDates as $row) {
            $date = (string) ($row['date'] ?? '');
            if (isset($rows[$date])) {
                $rows[$date]['activity'] = true;
            }
        }

        $sportDates = $db->select('sports_register', ['date'], [
            'date[>=]' => $startDate,
            'date[<=]' => $endDate,
            'GROUP' => 'date',
        ]);
        foreach ($sportDates as $row) {
            $date = (string) ($row['date'] ?? '');
            if (isset($rows[$date])) {
                $rows[$date]['activity'] = true;
            }
        }

        return [
            'ok' => true,
            'data' => array_values($rows),
        ];
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
    protected function listSportsAction(): array
    {
        $db = $this->medoo();

        $rows = $db->select('sports', ['id', 'name', 'icon', 'created_at'], [
            'ORDER' => ['created_at' => 'DESC', 'id' => 'DESC'],
        ]);

        return ['ok' => true, 'data' => $rows ?: []];
    }

    /** @return array<string, mixed> */
    protected function addSportAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $name = trim((string) ($in['name'] ?? ''));
        $icon = trim((string) ($in['icon'] ?? 'fa-solid fa-futbol'));

        if ($name === '') {
            return ['ok' => false, 'msg' => 'Nombre vacio'];
        }
        if ($icon === '') {
            $icon = 'fa-solid fa-futbol';
        }

        $db->insert('sports', [
            'name' => $name,
            'icon' => $icon,
        ]);

        return [
            'ok' => true,
            'id' => $this->lastInsertId(),
            'name' => $name,
            'icon' => $icon,
        ];
    }

    /** @return array<string, mixed> */
    protected function addSportRegisterAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $sportId = (int) ($in['sport_id'] ?? 0);
        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        $duration = (int) ($in['duration'] ?? 0);

        if ($sportId <= 0) {
            return ['ok' => false, 'msg' => 'Deporte invalido'];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'msg' => 'Fecha invalida'];
        }
        if ($duration <= 0) {
            return ['ok' => false, 'msg' => 'Duracion invalida'];
        }

        $exists = $db->has('sports', ['id' => $sportId]);
        if (!$exists) {
            return ['ok' => false, 'msg' => 'Deporte no encontrado'];
        }

        $db->insert('sports_register', [
            'sport_id' => $sportId,
            'date' => $date,
            'duration' => $duration,
        ]);

        return ['ok' => true, 'id' => $this->lastInsertId()];
    }

    /** @return array<string, mixed> */
    protected function getSportHistoryAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $sportId = (int) ($in['sport_id'] ?? 0);
        if ($sportId <= 0) {
            return ['ok' => false, 'msg' => 'Deporte invalido'];
        }

        $rows = $db->select('sports_register', ['id', 'sport_id', 'date', 'duration'], [
            'sport_id' => $sportId,
            'ORDER' => ['date' => 'DESC', 'id' => 'DESC'],
            'LIMIT' => 50,
        ]);

        return ['ok' => true, 'data' => $rows ?: []];
    }

    /** @return array<string, mixed> */
    protected function deleteSportAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $sportId = (int) ($in['sport_id'] ?? 0);
        if ($sportId <= 0) {
            return ['ok' => false, 'msg' => 'Deporte invalido'];
        }

        $db->delete('sports', ['id' => $sportId]);
        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    protected function deleteSportRegisterAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $recordId = (int) ($in['record_id'] ?? 0);
        if ($recordId <= 0) {
            return ['ok' => false, 'msg' => 'Registro invalido'];
        }

        $db->delete('sports_register', ['id' => $recordId]);
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
