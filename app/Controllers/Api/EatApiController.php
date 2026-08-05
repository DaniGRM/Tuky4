<?php
declare(strict_types=1);

namespace App\Controllers\Api;

final class EatApiController extends BaseApiController
{
    private const MEAL_KEYS = ['breakfast', 'lunch', 'dinner', 'snacks'];
    private const DEFAULT_SEX = 'male';
    private const DEFAULT_HEIGHT_CM = 170.0;
    private const DEFAULT_AGE = 30;
    private const DEFAULT_WEIGHT_KG = 70.0;

    public function listDashboard(): void
    {
        $this->run('listDashboardAction');
    }

    public function addDashboardRation(): void
    {
        $this->run('addDashboardRationAction');
    }

    public function deleteDashboardRation(): void
    {
        $this->run('deleteDashboardRationAction');
    }

    public function listUnits(): void
    {
        $this->run('listUnitsAction');
    }

    public function listPortions(): void
    {
        $this->run('listPortionsAction');
    }

    public function listTags(): void
    {
        $this->run('listTagsAction');
    }

    public function updateTagColor(): void
    {
        $this->run('updateTagColorAction');
    }

    public function addPortion(): void
    {
        $this->run('addPortionAction');
    }

    public function updatePortion(): void
    {
        $this->run('updatePortionAction');
    }

    public function deletePortion(): void
    {
        $this->run('deletePortionAction');
    }

    /** @return array<string, mixed> */
    protected function listDashboardAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'msg' => 'Fecha invalida'];
        }

        $records = $db->select('eat_records', ['id', 'date', 'key'], [
            'date' => $date,
            'ORDER' => ['id' => 'ASC'],
        ]);

        $recordsById = [];
        $recordIds = [];
        foreach ($records as $record) {
            $rid = (int) $record['id'];
            $recordsById[$rid] = [
                'id' => $rid,
                'key' => (string) $record['key'],
            ];
            $recordIds[] = $rid;
        }

        $pivotRows = [];
        if ($recordIds) {
            $pivotRows = $db->select('eat_records_rations', ['id', 'id_eat_record', 'id_eat_ration'], [
                'id_eat_record' => $recordIds,
                'ORDER' => ['id' => 'ASC'],
            ]);
        }

        $rationIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['id_eat_ration'],
            $pivotRows
        )));

        $rations = [];
        if ($rationIds) {
            $portionRows = $db->select('eat_portions', ['id', 'name', 'amount', 'unit_id', 'calories'], [
                'id' => $rationIds,
            ]);
            foreach ($portionRows as $row) {
                $rations[(int) $row['id']] = $row;
            }
        }

        $unitIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) ($row['unit_id'] ?? 0),
            array_values($rations)
        )));

        $units = [];
        if ($unitIds) {
            $unitRows = $db->select('eat_units', ['id', 'name', 'icon'], ['id' => $unitIds]);
            foreach ($unitRows as $u) {
                $units[(int) $u['id']] = [
                    'name' => (string) $u['name'],
                    'icon' => (string) $u['icon'],
                ];
            }
        }

        $meals = [];
        $totals = [];
        foreach (self::MEAL_KEYS as $key) {
            $meals[$key] = [];
            $totals[$key] = 0;
        }

        foreach ($pivotRows as $pivot) {
            $recordId = (int) $pivot['id_eat_record'];
            $rationId = (int) $pivot['id_eat_ration'];
            if (!isset($recordsById[$recordId]) || !isset($rations[$rationId])) {
                continue;
            }

            $mealKey = $recordsById[$recordId]['key'];
            if (!in_array($mealKey, self::MEAL_KEYS, true)) {
                continue;
            }

            $ration = $rations[$rationId];
            $uid = (int) $ration['unit_id'];
            $item = [
                'id' => (int) $pivot['id'],
                'record_id' => $recordId,
                'ration_id' => $rationId,
                'name' => (string) $ration['name'],
                'amount' => $ration['amount'],
                'unit_id' => $uid,
                'unit_name' => $units[$uid]['name'] ?? '',
                'unit_icon' => $units[$uid]['icon'] ?? '',
                'calories' => (int) $ration['calories'],
            ];

            $meals[$mealKey][] = $item;
            $totals[$mealKey] += (int) $ration['calories'];
        }

        $totals['day_total'] = array_sum(array_map(
            static fn (string $k): int => (int) $totals[$k],
            self::MEAL_KEYS
        ));

        $dailyContext = $this->resolveDailyContext($db, $date);
        $consumedKcal = (int) $totals['day_total'];
        $bmrKcal = (int) round($this->computeBmrMifflinStJeor(
            $dailyContext['weight_kg'],
            $dailyContext['height_cm'],
            $dailyContext['age'],
            $dailyContext['sex']
        ));
        $stepsKcal = (int) round($this->computeStepsCalories(
            $dailyContext['steps'],
            $dailyContext['weight_kg']
        ));
        $workoutsKcal = (int) round($this->computeWorkoutCalories($db, $date, $dailyContext['weight_kg']));
        $tefKcal = (int) round($consumedKcal * 0.10);
        $burnedTotalKcal = $bmrKcal + $stepsKcal + $workoutsKcal + $tefKcal;
        $netKcal = $consumedKcal - $burnedTotalKcal;

        return [
            'ok' => true,
            'data' => [
                'date' => $date,
                'meals' => $meals,
                'totals' => $totals,
                'energy' => [
                    'weight_kg' => $dailyContext['weight_kg'],
                    'steps' => $dailyContext['steps'],
                    'profile' => [
                        'sex' => $dailyContext['sex'],
                        'height_cm' => $dailyContext['height_cm'],
                        'age' => $dailyContext['age'],
                    ],
                    'consumed_kcal' => $consumedKcal,
                    'tef_kcal' => $tefKcal,
                    'bmr_kcal' => $bmrKcal,
                    'steps_kcal' => $stepsKcal,
                    'workouts_kcal' => $workoutsKcal,
                    'burned_total_kcal' => $burnedTotalKcal,
                    'net_kcal' => $netKcal,
                ],
            ],
        ];
    }

    /**
     * @return array{weight_kg: float, steps: int, sex: string, height_cm: float, age: int}
     */
    private function resolveDailyContext($db, string $date): array
    {
        $steps = 0;
        $weight = 0.0;

        $dayRow = $db->get('user_info_register', ['steps', 'weight'], ['date' => $date]);
        if ($dayRow) {
            $steps = (int) ($dayRow['steps'] ?? 0);
            $weight = (float) ($dayRow['weight'] ?? 0);
        }

        if ($weight <= 0) {
            $weightRows = $db->select('user_info_register', ['weight'], [
                'weight[!]' => null,
                'date[<=]' => $date,
                'ORDER' => ['date' => 'DESC', 'id' => 'DESC'],
                'LIMIT' => 1,
            ]);
            if ($weightRows && isset($weightRows[0]['weight'])) {
                $weight = (float) $weightRows[0]['weight'];
            }
        }

        if ($weight <= 0) {
            $weight = self::DEFAULT_WEIGHT_KG;
        }

        $sex = self::DEFAULT_SEX;
        $heightCm = self::DEFAULT_HEIGHT_CM;
        $age = self::DEFAULT_AGE;

        $profile = $db->get('user_info', ['sex', 'height_cm', 'age'], [
            'ORDER' => ['id' => 'ASC'],
        ]);
        if ($profile) {
            $profileSex = strtolower(trim((string) ($profile['sex'] ?? self::DEFAULT_SEX)));
            if (in_array($profileSex, ['male', 'female'], true)) {
                $sex = $profileSex;
            }

            $heightValue = (float) ($profile['height_cm'] ?? self::DEFAULT_HEIGHT_CM);
            if ($heightValue > 0) {
                $heightCm = $heightValue;
            }

            $ageValue = (int) ($profile['age'] ?? self::DEFAULT_AGE);
            if ($ageValue > 0) {
                $age = $ageValue;
            }
        }

        return [
            'weight_kg' => $weight,
            'steps' => max(0, $steps),
            'sex' => $sex,
            'height_cm' => $heightCm,
            'age' => $age,
        ];
    }

    private function computeBmrMifflinStJeor(float $weightKg, float $heightCm, int $age, string $sex): float
    {
        $base = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age);
        return $sex === 'female' ? $base - 161 : $base + 5;
    }

    private function computeStepsCalories(int $steps, float $weightKg): float
    {
        // Aproximacion: 0.0005 kcal por paso y kg corporal.
        return max(0, $steps) * max(0.0, $weightKg) * 0.0005;
    }

    private function computeWorkoutCalories($db, string $date, float $weightKg): float
    {
        $weight = max(1.0, $weightKg);
        $total = 0.0;

        $strengthRows = $db->select('records', ['series', 'rep_pattern'], [
            'date' => $date,
        ]);
        foreach ($strengthRows as $row) {
            $series = max(1, (int) ($row['series'] ?? 1));
            $reps = $this->sumReps((string) ($row['rep_pattern'] ?? ''));
            $estimatedMinutes = max(8, (int) round(($series * 2.5) + ($reps * 0.08)));
            $total += $this->caloriesFromMet(6.0, $weight, $estimatedMinutes);
        }

        $activityRows = $db->select('activities_records', ['activity_id', 'avg_speed', 'duration'], [
            'date' => $date,
        ]);

        if ($activityRows) {
            $activityIds = array_values(array_unique(array_map(
                static fn (array $row): int => (int) ($row['activity_id'] ?? 0),
                $activityRows
            )));

            $typesByActivityId = [];
            if ($activityIds) {
                $activityTypes = $db->select('activities', ['id', 'type'], [
                    'id' => $activityIds,
                ]);
                foreach ($activityTypes as $activityType) {
                    $typesByActivityId[(int) $activityType['id']] = strtolower((string) $activityType['type']);
                }
            }

            foreach ($activityRows as $row) {
                $activityId = (int) ($row['activity_id'] ?? 0);
                $type = $typesByActivityId[$activityId] ?? '';
                $duration = max(1, (int) ($row['duration'] ?? 0));
                $speed = max(0.0, (float) ($row['avg_speed'] ?? 0));

                $met = $this->resolveMetForCardio($type, $speed);
                if ($met <= 0) {
                    continue;
                }
                $total += $this->caloriesFromMet($met, $weight, $duration);
            }
        }

        return $total;
    }

    private function caloriesFromMet(float $met, float $weightKg, int $minutes): float
    {
        return ($met * 3.5 * $weightKg / 200) * max(1, $minutes);
    }

    private function resolveMetForCardio(string $type, float $speedKmh): float
    {
        $speed = max(0.0, $speedKmh);

        if ($type === 'bike') {
            if ($speed < 16.0) return 4.0;
            if ($speed < 19.0) return 6.8;
            if ($speed < 22.5) return 8.0;
            if ($speed < 25.7) return 10.0;
            return 12.0;
        }

        if ($type === 'running') {
            if ($speed < 8.0) return 8.3;
            if ($speed < 9.7) return 9.8;
            if ($speed < 11.3) return 11.0;
            if ($speed < 12.9) return 11.8;
            return 12.8;
        }

        return 0.0;
    }

    private function sumReps(string $pattern): int
    {
        $parts = array_filter(array_map('trim', explode('-', $pattern)), static fn (string $p): bool => $p !== '');
        $sum = 0;
        foreach ($parts as $part) {
            $sum += max(0, (int) $part);
        }
        return $sum;
    }

    /** @return array<string, mixed> */
    protected function addDashboardRationAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        $key = strtolower(trim((string) ($in['key'] ?? '')));
        $rationId = (int) ($in['ration_id'] ?? 0);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['ok' => false, 'msg' => 'Fecha invalida'];
        }
        if (!in_array($key, self::MEAL_KEYS, true)) {
            return ['ok' => false, 'msg' => 'Comida invalida'];
        }
        if ($rationId <= 0) {
            return ['ok' => false, 'msg' => 'Racion invalida'];
        }
        if (!$db->has('eat_portions', ['id' => $rationId])) {
            return ['ok' => false, 'msg' => 'Racion no encontrada'];
        }

        $recordId = $db->get('eat_records', 'id', [
            'date' => $date,
            'key' => $key,
        ]);

        if (!$recordId) {
            $db->insert('eat_records', [
                'date' => $date,
                'key' => $key,
            ]);
            $recordId = (int) $this->lastInsertId();
        }

        $db->insert('eat_records_rations', [
            'id_eat_record' => (int) $recordId,
            'id_eat_ration' => $rationId,
        ]);

        return ['ok' => true, 'id' => $this->lastInsertId()];
    }

    /** @return array<string, mixed> */
    protected function deleteDashboardRationAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'Registro de racion invalido'];
        }

        $db->delete('eat_records_rations', ['id' => $id]);
        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    protected function listUnitsAction(): array
    {
        $db = $this->medoo();
        $rows = $db->select('eat_units', ['id', 'name', 'icon', 'sort_order'], [
            'ORDER' => ['sort_order' => 'ASC', 'id' => 'ASC'],
        ]);

        return ['ok' => true, 'data' => $rows ?: []];
    }

    /** @return array<string, mixed> */
    protected function listTagsAction(): array
    {
        $db = $this->medoo();
        $rows = $db->select('eat_tags', ['id', 'name', 'color'], [
            'ORDER' => ['name' => 'ASC', 'id' => 'ASC'],
        ]);

        return ['ok' => true, 'data' => $rows ?: []];
    }

    /** @return array<string, mixed> */
    protected function updateTagColorAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        $color = $this->normalizeHexColor((string) ($in['color'] ?? ''));

        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'Tag invalida'];
        }

        $exists = $db->has('eat_tags', ['id' => $id]);
        if (!$exists) {
            return ['ok' => false, 'msg' => 'Tag no encontrada'];
        }

        $db->update('eat_tags', [
            'color' => $color !== '' ? $color : null,
        ], [
            'id' => $id,
        ]);

        return [
            'ok' => true,
            'data' => [
                'id' => $id,
                'color' => $color !== '' ? $color : null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function listPortionsAction(): array
    {
        $db = $this->medoo();

        $rows = $db->select('eat_portions', [
            'id',
            'name',
            'amount',
            'calories',
            'created_at',
            'updated_at',
            'unit_id',
            'tag_id',
        ], [
            'ORDER' => ['created_at' => 'DESC', 'id' => 'DESC'],
        ]);

        if (!$rows) {
            return ['ok' => true, 'data' => []];
        }

        $unitIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['unit_id'],
            $rows
        )));

        $units = [];
        if ($unitIds) {
            $unitRows = $db->select('eat_units', ['id', 'name', 'icon'], ['id' => $unitIds]);
            foreach ($unitRows as $u) {
                $units[(int) $u['id']] = [
                    'name' => (string) $u['name'],
                    'icon' => (string) $u['icon'],
                ];
            }
        }

        $mapped = [];
        $tagIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['tag_id'] ?? 0),
            $rows
        ))));
        $tagsById = [];
        if ($tagIds) {
            $tagRows = $db->select('eat_tags', ['id', 'name', 'color'], ['id' => $tagIds]);
            foreach ($tagRows as $tagRow) {
                $tagsById[(int) $tagRow['id']] = [
                    'name' => (string) $tagRow['name'],
                    'color' => $this->normalizeHexColor((string) ($tagRow['color'] ?? '')),
                ];
            }
        }

        foreach ($rows as $row) {
            $uid = (int) $row['unit_id'];
            $tagId = $row['tag_id'] !== null ? (int) $row['tag_id'] : null;
            $mapped[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'amount' => $row['amount'],
                'calories' => (int) $row['calories'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'unit_id' => $uid,
                'unit_name' => $units[$uid]['name'] ?? '',
                'unit_icon' => $units[$uid]['icon'] ?? '',
                'tag_id' => $tagId,
                'tag_name' => $tagId !== null ? (($tagsById[$tagId]['name'] ?? '')) : '',
                'tag_color' => $tagId !== null ? (($tagsById[$tagId]['color'] ?? '')) : '',
            ];
        }

        return ['ok' => true, 'data' => $mapped];
    }

    /** @return array<string, mixed> */
    protected function addPortionAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $name = trim((string) ($in['name'] ?? ''));
        $amount = (float) ($in['amount'] ?? 0);
        $unitId = (int) ($in['unit_id'] ?? 0);
        $calories = (int) ($in['calories'] ?? 0);
        $tagName = trim((string) ($in['tag_name'] ?? ''));

        if ($name === '') {
            return ['ok' => false, 'msg' => 'Nombre vacio'];
        }
        if ($amount <= 0 || $unitId <= 0 || $calories <= 0) {
            return ['ok' => false, 'msg' => 'Cantidad, unidad y calorias deben ser validas'];
        }

        if (!$db->has('eat_units', ['id' => $unitId])) {
            return ['ok' => false, 'msg' => 'Unidad invalida'];
        }

        $tagId = $this->resolveTagId($db, $tagName);

        $db->insert('eat_portions', [
            'name' => $name,
            'amount' => $amount,
            'unit_id' => $unitId,
            'tag_id' => $tagId,
            'calories' => $calories,
        ]);

        return [
            'ok' => true,
            'id' => $this->lastInsertId(),
            'name' => $name,
            'amount' => $amount,
            'unit_id' => $unitId,
            'tag_id' => $tagId,
            'tag_name' => $tagName,
            'calories' => $calories,
        ];
    }

    /** @return array<string, mixed> */
    protected function updatePortionAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        $name = trim((string) ($in['name'] ?? ''));
        $amount = (float) ($in['amount'] ?? 0);
        $unitId = (int) ($in['unit_id'] ?? 0);
        $calories = (int) ($in['calories'] ?? 0);
        $tagName = trim((string) ($in['tag_name'] ?? ''));

        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'Racion invalida'];
        }
        if ($name === '' || $amount <= 0 || $unitId <= 0 || $calories <= 0) {
            return ['ok' => false, 'msg' => 'Datos invalidos'];
        }

        $exists = $db->has('eat_portions', ['id' => $id]);
        if (!$exists) {
            return ['ok' => false, 'msg' => 'Racion no encontrada'];
        }

        if (!$db->has('eat_units', ['id' => $unitId])) {
            return ['ok' => false, 'msg' => 'Unidad invalida'];
        }

        $tagId = $this->resolveTagId($db, $tagName);

        $db->update('eat_portions', [
            'name' => $name,
            'amount' => $amount,
            'unit_id' => $unitId,
            'tag_id' => $tagId,
            'calories' => $calories,
        ], [
            'id' => $id,
        ]);

        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    protected function deletePortionAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'Racion invalida'];
        }

        $db->delete('eat_portions', ['id' => $id]);
        return ['ok' => true];
    }

    private function resolveTagId($db, string $tagName): ?int
    {
        $name = trim($tagName);
        if ($name === '') {
            return null;
        }

        $existingId = $db->get('eat_tags', 'id', ['name' => $name]);
        if ($existingId) {
            return (int) $existingId;
        }

        $db->insert('eat_tags', ['name' => $name]);
        return (int) $this->lastInsertId();
    }

    private function normalizeHexColor(string $raw): string
    {
        $color = strtoupper(trim($raw));
        if ($color === '') {
            return '';
        }

        if (preg_match('/^#[0-9A-F]{6}$/', $color) === 1) {
            return $color;
        }

        if (preg_match('/^#[0-9A-F]{3}$/', $color) === 1) {
            return '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        return '';
    }
}
