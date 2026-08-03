<?php
declare(strict_types=1);

namespace App\Controllers\Api;

final class EatApiController extends BaseApiController
{
    private const MEAL_KEYS = ['breakfast', 'lunch', 'dinner', 'snacks'];

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

        return [
            'ok' => true,
            'data' => [
                'date' => $date,
                'meals' => $meals,
                'totals' => $totals,
            ],
        ];
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
        foreach ($rows as $row) {
            $uid = (int) $row['unit_id'];
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

        if ($name === '') {
            return ['ok' => false, 'msg' => 'Nombre vacio'];
        }
        if ($amount <= 0 || $unitId <= 0 || $calories <= 0) {
            return ['ok' => false, 'msg' => 'Cantidad, unidad y calorias deben ser validas'];
        }

        if (!$db->has('eat_units', ['id' => $unitId])) {
            return ['ok' => false, 'msg' => 'Unidad invalida'];
        }

        $db->insert('eat_portions', [
            'name' => $name,
            'amount' => $amount,
            'unit_id' => $unitId,
            'calories' => $calories,
        ]);

        return [
            'ok' => true,
            'id' => $this->lastInsertId(),
            'name' => $name,
            'amount' => $amount,
            'unit_id' => $unitId,
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

        $db->update('eat_portions', [
            'name' => $name,
            'amount' => $amount,
            'unit_id' => $unitId,
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
}
