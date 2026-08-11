<?php
declare(strict_types=1);

namespace App\Controllers\Api;

final class MoneyApiController extends BaseApiController
{
    public function listDashboard(): void
    {
        $this->run('listDashboardAction');
    }

    public function importCsv(): void
    {
        $this->run('importCsvAction');
    }

    public function getSettings(): void
    {
        $this->run('getSettingsAction');
    }

    public function saveSettings(): void
    {
        $this->run('saveSettingsAction');
    }

    public function listExpenses(): void
    {
        $this->run('listExpensesAction');
    }

    public function updateMovement(): void
    {
        $this->run('updateMovementAction');
    }

    public function addExpense(): void
    {
        $this->run('addExpenseAction');
    }

    public function updateExpense(): void
    {
        $this->run('updateExpenseAction');
    }

    public function deleteExpense(): void
    {
        $this->run('deleteExpenseAction');
    }

    public function listTags(): void
    {
        $this->run('listTagsAction');
    }

    public function saveTag(): void
    {
        $this->run('saveTagAction');
    }

    public function deleteTag(): void
    {
        $this->run('deleteTagAction');
    }

    public function updateTagColor(): void
    {
        $this->run('updateTagColorAction');
    }

    public function listRules(): void
    {
        $this->run('listRulesAction');
    }

    public function saveRule(): void
    {
        $this->run('saveRuleAction');
    }

    /** @return array<string,mixed> */
    protected function listDashboardAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        if (!$this->isValidDate($date)) {
            return ['ok' => false, 'msg' => 'Fecha invalida'];
        }
        if ($this->isFutureDate($date)) {
            return ['ok' => false, 'msg' => 'No se puede usar una fecha futura'];
        }

        $settings = $this->loadSettings($db);
        $monthStart = date('Y-m-01', strtotime($date) ?: time());
        $monthEnd = date('Y-m-t', strtotime($date) ?: time());

        $tagsById = $this->getMoneyTagsById($db);

        $expenseRows = $db->select('money_expenses', [
            'id', 'name', 'source_item', 'amount', 'expense_type', 'expense_date', 'tag_id', 'created_at', 'updated_at',
        ], [
            'expense_date[>=]' => $monthStart,
            'expense_date[<=]' => $monthEnd,
            'ORDER' => ['expense_date' => 'DESC', 'id' => 'DESC'],
        ]) ?: [];

        $incomeRows = $db->select('money_incomes', [
            'id', 'name', 'source_item', 'amount', 'income_date', 'tag_id', 'created_at', 'updated_at',
        ], [
            'income_date[>=]' => $monthStart,
            'income_date[<=]' => $monthEnd,
            'ORDER' => ['income_date' => 'DESC', 'id' => 'DESC'],
        ]) ?: [];

        $expenseToday = array_values(array_filter($expenseRows, static fn (array $e): bool => (string) ($e['expense_date'] ?? '') === $date));
        $incomeToday = array_values(array_filter($incomeRows, static fn (array $e): bool => (string) ($e['income_date'] ?? '') === $date));

        $expenseMonthToDate = array_values(array_filter($expenseRows, static fn (array $e): bool => (string) ($e['expense_date'] ?? '') <= $date));
        $incomeMonthToDate = array_values(array_filter($incomeRows, static fn (array $e): bool => (string) ($e['income_date'] ?? '') <= $date));

        $spentToday = $this->sumAmounts($expenseToday);
        $spentMonthToDate = $this->sumAmounts($expenseMonthToDate);
        $projectedMonthSpent = $this->sumAmounts($expenseRows);

        $incomeTodayAmount = $this->sumAmounts($incomeToday);
        $incomeMonthToDateAmount = $this->sumAmounts($incomeMonthToDate);

        $monthlyAvailable = max(0.0, (float) $settings['monthly_salary'] - (float) $settings['savings_goal']);
        $monthBalance = $monthlyAvailable + $incomeMonthToDateAmount - $projectedMonthSpent;
        $remainingAmount = (float) $settings['current_amount'];

        $todayItems = array_merge(
            $this->mapExpenses($expenseToday, $tagsById),
            $this->mapIncomes($incomeToday, $tagsById)
        );

        usort($todayItems, static function (array $a, array $b): int {
            $kindA = (string) ($a['movement_kind'] ?? 'expense');
            $kindB = (string) ($b['movement_kind'] ?? 'expense');
            if ($kindA === $kindB) {
                return ((float) ($b['amount'] ?? 0)) <=> ((float) ($a['amount'] ?? 0));
            }
            return $kindA === 'income' ? -1 : 1;
        });

        $tagBreakdown = $this->buildTagBreakdown($expenseRows, $tagsById);

        return [
            'ok' => true,
            'data' => [
                'date' => $date,
                'settings' => $settings,
                'today_items' => $todayItems,
                'kpis' => [
                    'spent_today' => round($spentToday, 2),
                    'spent_month_to_date' => round($spentMonthToDate, 2),
                    'projected_month_spent' => round($projectedMonthSpent, 2),
                    'income_today' => round($incomeTodayAmount, 2),
                    'income_month_to_date' => round($incomeMonthToDateAmount, 2),
                    'monthly_available' => round($monthlyAvailable, 2),
                    'month_balance' => round($monthBalance, 2),
                    'remaining_amount' => round($remainingAmount, 2),
                    'savings_goal' => round((float) $settings['savings_goal'], 2),
                ],
                'tag_breakdown' => $tagBreakdown,
            ],
        ];
    }

    /** @return array<string,mixed> */
    protected function importCsvAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $csvText = (string) ($in['csv_text'] ?? '');
        if (trim($csvText) === '') {
            return ['ok' => false, 'msg' => 'CSV vacio'];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($csvText)) ?: [];
        if (count($lines) < 2) {
            return ['ok' => false, 'msg' => 'CSV sin datos'];
        }

        $rulesByItem = $this->getRulesByItem($db);

        $createdExpenses = 0;
        $createdIncomes = 0;
        $duplicates = 0;
        $invalid = 0;

        $latestBalance = null;
        $latestBalanceDate = '';

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim((string) $lines[$i]);
            if ($line === '') {
                continue;
            }

            $cols = str_getcsv($line, ';');
            if (count($cols) < 4) {
                $invalid++;
                continue;
            }

            $itemRaw = trim((string) ($cols[0] ?? ''));
            $dateRaw = trim((string) ($cols[1] ?? ''));
            $amountRaw = trim((string) ($cols[2] ?? ''));
            $balanceRaw = trim((string) ($cols[3] ?? ''));

            $date = $this->parseBankDate($dateRaw);
            $amount = $this->parseBankAmount($amountRaw);
            $balance = $this->parseBankAmount($balanceRaw);
            $item = $this->normalizeBankItem($itemRaw);

            if ($item === '' || $date === '' || $amount === null || $amount == 0.0) {
                $invalid++;
                continue;
            }

            if ($balance !== null && ($latestBalanceDate === '' || $date >= $latestBalanceDate)) {
                $latestBalanceDate = $date;
                $latestBalance = round($balance, 2);
            }

            $rule = $rulesByItem[$item] ?? null;
            $ruleApplyTo = strtolower(trim((string) ($rule['apply_to'] ?? 'expense')));
            $appliesToExpense = in_array($ruleApplyTo, ['expense', 'both'], true);
            $appliesToIncome = in_array($ruleApplyTo, ['income', 'both'], true);
            $amountAbs = round(abs($amount), 2);

            if ($amount < 0) {
                $pretty = $appliesToExpense ? trim((string) ($rule['pretty_name'] ?? '')) : '';
                $name = $pretty !== '' ? $pretty : $itemRaw;

                $expenseType = $appliesToExpense ? (string) ($rule['expense_type'] ?? 'variable') : 'variable';
                if (!in_array($expenseType, ['fixed', 'variable'], true)) {
                    $expenseType = 'variable';
                }

                $tagId = $appliesToExpense ? $this->validateTagId($db, (int) ($rule['tag_id'] ?? 0)) : null;
                $importRef = hash('sha256', 'expense|' . $item . '|' . $date . '|' . number_format($amountAbs, 2, '.', ''));

                if ($db->has('money_expenses', ['import_ref' => $importRef])) {
                    $duplicates++;
                    continue;
                }

                $db->insert('money_expenses', [
                    'name' => $name,
                    'source_item' => $item,
                    'amount' => $amountAbs,
                    'expense_type' => $expenseType,
                    'expense_date' => $date,
                    'billing_day' => null,
                    'tag_id' => $tagId,
                    'import_ref' => $importRef,
                ]);
                $createdExpenses++;
                continue;
            }

            $pretty = $appliesToIncome ? trim((string) ($rule['pretty_name'] ?? '')) : '';
            $name = $pretty !== '' ? $pretty : $itemRaw;
            $tagId = $appliesToIncome ? $this->validateTagId($db, (int) ($rule['tag_id'] ?? 0)) : null;
            $importRef = hash('sha256', 'income|' . $item . '|' . $date . '|' . number_format($amountAbs, 2, '.', ''));
            if ($db->has('money_incomes', ['import_ref' => $importRef])) {
                $duplicates++;
                continue;
            }

            $db->insert('money_incomes', [
                'name' => $name,
                'source_item' => $item,
                'amount' => $amountAbs,
                'income_date' => $date,
                'tag_id' => $tagId,
                'import_ref' => $importRef,
            ]);
            $createdIncomes++;
        }

        if ($latestBalance !== null) {
            $this->upsertCurrentAmount($db, (float) $latestBalance);
        }

        return [
            'ok' => true,
            'data' => [
                'created_expenses' => $createdExpenses,
                'created_incomes' => $createdIncomes,
                'duplicates' => $duplicates,
                'invalid_rows' => $invalid,
                'current_amount' => $latestBalance,
            ],
        ];
    }

    /** @return array<string,mixed> */
    protected function getSettingsAction(): array
    {
        return ['ok' => true, 'data' => $this->loadSettings($this->medoo())];
    }

    /** @return array<string,mixed> */
    protected function saveSettingsAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $payload = [
            'monthly_salary' => round(max(0.0, (float) ($in['monthly_salary'] ?? 0)), 2),
            'current_amount' => round((float) ($in['current_amount'] ?? 0), 2),
            'savings_goal' => round(max(0.0, (float) ($in['savings_goal'] ?? 0)), 2),
        ];

        $id = (int) ($db->get('money_settings', 'id', ['ORDER' => ['id' => 'ASC']]) ?? 0);
        if ($id > 0) {
            $db->update('money_settings', $payload, ['id' => $id]);
        } else {
            $db->insert('money_settings', $payload);
        }

        return ['ok' => true, 'data' => $this->loadSettings($db)];
    }

    /** @return array<string,mixed> */
    protected function listExpensesAction(): array
    {
        $db = $this->medoo();
        $tagsById = $this->getMoneyTagsById($db);
        $in = $this->input();
        $date = trim((string) ($in['date'] ?? date('Y-m-d')));
        if (!$this->isValidDate($date)) {
            return ['ok' => false, 'msg' => 'Fecha invalida'];
        }
        if ($this->isFutureDate($date)) {
            return ['ok' => false, 'msg' => 'No se puede usar una fecha futura'];
        }

        $expenseRows = $db->select('money_expenses', [
            'id', 'name', 'source_item', 'amount', 'expense_type', 'expense_date', 'tag_id', 'created_at', 'updated_at',
        ], [
            'expense_date[<=]' => $date,
            'ORDER' => ['expense_date' => 'DESC', 'id' => 'DESC'],
        ]) ?: [];

        $incomeRows = $db->select('money_incomes', [
            'id', 'name', 'source_item', 'amount', 'income_date', 'tag_id', 'created_at', 'updated_at',
        ], [
            'income_date[<=]' => $date,
            'ORDER' => ['income_date' => 'DESC', 'id' => 'DESC'],
        ]) ?: [];

        $all = array_merge(
            $this->mapExpenses($expenseRows, $tagsById),
            $this->mapIncomes($incomeRows, $tagsById)
        );

        usort($all, static function (array $a, array $b): int {
            $dateA = (string) ($a['movement_date'] ?? '0000-00-00');
            $dateB = (string) ($b['movement_date'] ?? '0000-00-00');
            if ($dateA === $dateB) {
                return ((float) ($b['amount'] ?? 0)) <=> ((float) ($a['amount'] ?? 0));
            }
            return strcmp($dateB, $dateA);
        });

        return [
            'ok' => true,
            'data' => $all,
        ];
    }

    /** @return array<string,mixed> */
    protected function updateMovementAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $kind = strtolower(trim((string) ($in['movement_kind'] ?? 'expense')));
        $id = (int) ($in['id'] ?? 0);
        $name = trim((string) ($in['name'] ?? ''));
        $tagIdInput = (int) ($in['tag_id'] ?? 0);
        $tagName = trim((string) ($in['tag_name'] ?? ''));
        $saveRule = (bool) ($in['save_rule'] ?? false);
        $applyTo = strtolower(trim((string) ($in['rule_apply_to'] ?? 'expense')));
        $ruleType = strtolower(trim((string) ($in['expense_type'] ?? 'variable')));

        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'Movimiento invalido'];
        }
        if ($name === '') {
            return ['ok' => false, 'msg' => 'Nombre obligatorio'];
        }

        if ($kind === 'income') {
            if (!$db->has('money_incomes', ['id' => $id])) {
                return ['ok' => false, 'msg' => 'Ingreso no encontrado'];
            }

            $tagId = $this->resolveMoneyTagId($db, $tagIdInput, $tagName);
            $db->update('money_incomes', [
                'name' => $name,
                'tag_id' => $tagId,
            ], ['id' => $id]);

            if ($saveRule) {
                $sourceItem = (string) ($db->get('money_incomes', 'source_item', ['id' => $id]) ?? '');
                if ($sourceItem !== '') {
                    $this->upsertRule($db, $sourceItem, $name, $applyTo, null, $tagId);
                }
            }

            return ['ok' => true];
        }

        if ($kind !== 'expense') {
            return ['ok' => false, 'msg' => 'Tipo de movimiento invalido'];
        }

        if (!$db->has('money_expenses', ['id' => $id])) {
            return ['ok' => false, 'msg' => 'Gasto no encontrado'];
        }

        if (!in_array($ruleType, ['fixed', 'variable'], true)) {
            $ruleType = 'variable';
        }

        $tagId = $this->resolveMoneyTagId($db, $tagIdInput, $tagName);
        $db->update('money_expenses', [
            'name' => $name,
            'expense_type' => $ruleType,
            'tag_id' => $tagId,
            'billing_day' => null,
        ], ['id' => $id]);

        if ($saveRule) {
            $sourceItem = (string) ($db->get('money_expenses', 'source_item', ['id' => $id]) ?? '');
            if ($sourceItem !== '') {
                $this->upsertRule($db, $sourceItem, $name, $applyTo, $ruleType, $tagId);
            }
        }

        return ['ok' => true];
    }

    /** @return array<string,mixed> */
    protected function addExpenseAction(): array
    {
        return ['ok' => false, 'msg' => 'Alta manual desactivada: usa importacion CSV'];
    }

    /** @return array<string,mixed> */
    protected function updateExpenseAction(): array
    {
        return ['ok' => false, 'msg' => 'Actualiza movimientos desde la nueva lista'];
    }

    /** @return array<string,mixed> */
    protected function deleteExpenseAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $kind = strtolower(trim((string) ($in['movement_kind'] ?? 'expense')));
        $id = (int) ($in['id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'Movimiento invalido'];
        }

        if ($kind === 'income') {
            $db->delete('money_incomes', ['id' => $id]);
            return ['ok' => true];
        }

        $db->delete('money_expenses', ['id' => $id]);
        return ['ok' => true];
    }

    /** @return array<string,mixed> */
    protected function listRulesAction(): array
    {
        $db = $this->medoo();
        $tagsById = $this->getMoneyTagsById($db);

        $rows = $db->select('money_concept_rules', [
            'id', 'bank_item', 'pretty_name', 'apply_to', 'expense_type', 'tag_id',
        ], [
            'ORDER' => ['bank_item' => 'ASC', 'id' => 'ASC'],
        ]) ?: [];

        $data = [];
        foreach ($rows as $row) {
            $tagId = $row['tag_id'] !== null ? (int) $row['tag_id'] : 0;
            $data[] = [
                'id' => (int) ($row['id'] ?? 0),
                'bank_item' => (string) ($row['bank_item'] ?? ''),
                'pretty_name' => (string) ($row['pretty_name'] ?? ''),
                'apply_to' => (string) ($row['apply_to'] == 'expense' ? 'Gasto' : ($row['apply_to'] == 'income' ? 'Ingreso' : 'Ambos')),
                'expense_type' => (string) ($row['expense_type'] ?? 'variable'),
                'tag_id' => $tagId > 0 ? $tagId : null,
                'tag_name' => $tagId > 0 ? (string) ($tagsById[$tagId]['name'] ?? '') : '',
            ];
        }

        return ['ok' => true, 'data' => $data];
    }

    /** @return array<string,mixed> */
    protected function saveRuleAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $bankItem = $this->normalizeBankItem((string) ($in['bank_item'] ?? ''));
        $prettyName = trim((string) ($in['pretty_name'] ?? ''));
        $applyTo = strtolower(trim((string) ($in['apply_to'] ?? 'expense')));
        $expenseType = strtolower(trim((string) ($in['expense_type'] ?? 'variable')));
        $tagIdInput = (int) ($in['tag_id'] ?? 0);
        $tagName = trim((string) ($in['tag_name'] ?? ''));

        if ($bankItem === '') {
            return ['ok' => false, 'msg' => 'Concepto de banco obligatorio'];
        }
        if (!in_array($applyTo, ['expense', 'income', 'both'], true)) {
            $applyTo = 'expense';
        }
        if (!in_array($expenseType, ['fixed', 'variable'], true)) {
            $expenseType = 'variable';
        }

        $tagId = $this->resolveMoneyTagId($db, $tagIdInput, $tagName);
        $this->upsertRule($db, $bankItem, $prettyName, $applyTo, $expenseType, $tagId);

        return ['ok' => true];
    }

    /** @return array<string,mixed> */
    protected function listTagsAction(): array
    {
        $db = $this->medoo();
        $rows = $db->select('money_tags', ['id', 'name', 'color'], [
            'ORDER' => ['name' => 'ASC', 'id' => 'ASC'],
        ]) ?: [];

        return ['ok' => true, 'data' => array_map(function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['name'] = (string) ($row['name'] ?? '');
            $row['color'] = $this->normalizeHexColor((string) ($row['color'] ?? ''));
            return $row;
        }, $rows)];
    }

    /** @return array<string,mixed> */
    protected function saveTagAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        $name = trim((string) ($in['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'msg' => 'Nombre de tag invalido'];
        }

        if ($id > 0) {
            if (!$db->has('money_tags', ['id' => $id])) {
                return ['ok' => false, 'msg' => 'Tag no encontrada'];
            }
            $db->update('money_tags', ['name' => $name], ['id' => $id]);
            return ['ok' => true, 'id' => $id];
        }

        $existingId = (int) ($db->get('money_tags', 'id', ['name' => $name]) ?? 0);
        if ($existingId > 0) {
            return ['ok' => true, 'id' => $existingId];
        }

        $db->insert('money_tags', ['name' => $name]);
        return ['ok' => true, 'id' => (int) $this->lastInsertId()];
    }

    /** @return array<string,mixed> */
    protected function deleteTagAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'Tag invalida'];
        }

        $db->delete('money_tags', ['id' => $id]);
        return ['ok' => true];
    }

    /** @return array<string,mixed> */
    protected function updateTagColorAction(): array
    {
        $in = $this->input();
        $db = $this->medoo();

        $id = (int) ($in['id'] ?? 0);
        $color = $this->normalizeHexColor((string) ($in['color'] ?? ''));
        if ($id <= 0) {
            return ['ok' => false, 'msg' => 'Tag invalida'];
        }
        if (!$db->has('money_tags', ['id' => $id])) {
            return ['ok' => false, 'msg' => 'Tag no encontrada'];
        }

        $db->update('money_tags', [
            'color' => $color !== '' ? $color : null,
        ], [
            'id' => $id,
        ]);

        return ['ok' => true];
    }

    /** @return array<string,mixed> */
    private function loadSettings($db): array
    {
        $row = $db->get('money_settings', [
            'id', 'monthly_salary', 'current_amount', 'savings_goal',
        ], [
            'ORDER' => ['id' => 'ASC'],
        ]);

        return [
            'monthly_salary' => $row ? (float) ($row['monthly_salary'] ?? 0) : 0.0,
            'current_amount' => $row ? (float) ($row['current_amount'] ?? 0) : 0.0,
            'savings_goal' => $row ? (float) ($row['savings_goal'] ?? 0) : 0.0,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function mapExpenses(array $rows, array $tagsById): array
    {
        $mapped = [];
        foreach ($rows as $row) {
            $tagId = $row['tag_id'] !== null ? (int) $row['tag_id'] : 0;
            $mapped[] = [
                'id' => (int) ($row['id'] ?? 0),
                'movement_kind' => 'expense',
                'name' => (string) ($row['name'] ?? ''),
                'source_item' => (string) ($row['source_item'] ?? ''),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
                'expense_type' => (string) ($row['expense_type'] ?? 'variable'),
                'movement_date' => $row['expense_date'] !== null ? (string) $row['expense_date'] : null,
                'tag_id' => $tagId > 0 ? $tagId : null,
                'tag_name' => $tagId > 0 ? ($tagsById[$tagId]['name'] ?? '') : '',
                'tag_color' => $tagId > 0 ? ($tagsById[$tagId]['color'] ?? '') : '',
            ];
        }
        return $mapped;
    }

    /** @return array<int,array<string,mixed>> */
    private function mapIncomes(array $rows, array $tagsById): array
    {
        $mapped = [];
        foreach ($rows as $row) {
            $tagId = $row['tag_id'] !== null ? (int) $row['tag_id'] : 0;
            $mapped[] = [
                'id' => (int) ($row['id'] ?? 0),
                'movement_kind' => 'income',
                'name' => (string) ($row['name'] ?? ''),
                'source_item' => (string) ($row['source_item'] ?? ''),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
                'expense_type' => 'variable',
                'movement_date' => $row['income_date'] !== null ? (string) $row['income_date'] : null,
                'tag_id' => $tagId > 0 ? $tagId : null,
                'tag_name' => $tagId > 0 ? ($tagsById[$tagId]['name'] ?? '') : '',
                'tag_color' => $tagId > 0 ? ($tagsById[$tagId]['color'] ?? '') : '',
            ];
        }
        return $mapped;
    }

    private function sumAmounts(array $rows): float
    {
        $sum = 0.0;
        foreach ($rows as $row) {
            $sum += (float) ($row['amount'] ?? 0);
        }
        return round($sum, 2);
    }

    /** @return array<int,array{name:string,color:string}> */
    private function getMoneyTagsById($db): array
    {
        $rows = $db->select('money_tags', ['id', 'name', 'color']) ?: [];
        $mapped = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $mapped[$id] = [
                'name' => (string) ($row['name'] ?? ''),
                'color' => $this->normalizeHexColor((string) ($row['color'] ?? '')),
            ];
        }
        return $mapped;
    }

    /** @return array<int,array{name:string,total:float,color:string}> */
    private function buildTagBreakdown(array $expenseRows, array $tagsById): array
    {
        $totals = [];
        foreach ($expenseRows as $row) {
            $tagId = $row['tag_id'] !== null ? (int) $row['tag_id'] : 0;
            $amount = round((float) ($row['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $key = $tagId > 0 ? 'tag-' . $tagId : 'tag-none';
            $name = $tagId > 0 ? ($tagsById[$tagId]['name'] ?? 'Sin tag') : 'Sin tag';
            $color = $tagId > 0 ? ($tagsById[$tagId]['color'] ?? '') : '';

            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'name' => $name,
                    'total' => 0.0,
                    'color' => $color,
                ];
            }
            $totals[$key]['total'] += $amount;
        }

        uasort($totals, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        $result = [];
        foreach ($totals as $item) {
            $result[] = [
                'name' => (string) ($item['name'] ?? 'Sin tag'),
                'total' => round((float) ($item['total'] ?? 0), 2),
                'color' => (string) ($item['color'] ?? ''),
            ];
        }

        return $result;
    }

    private function resolveMoneyTagId($db, int $tagIdInput, string $tagName): ?int
    {
        if ($tagIdInput > 0 && $db->has('money_tags', ['id' => $tagIdInput])) {
            return $tagIdInput;
        }

        $name = trim($tagName);
        if ($name === '') {
            return null;
        }

        $existingId = (int) ($db->get('money_tags', 'id', ['name' => $name]) ?? 0);
        if ($existingId > 0) {
            return $existingId;
        }

        $db->insert('money_tags', ['name' => $name]);
        return (int) $this->lastInsertId();
    }

    /** @return array<string,array<string,mixed>> */
    private function getRulesByItem($db): array
    {
        $rows = $db->select('money_concept_rules', [
            'bank_item', 'pretty_name', 'apply_to', 'expense_type', 'tag_id',
        ]) ?: [];

        $mapped = [];
        foreach ($rows as $row) {
            $key = $this->normalizeBankItem((string) ($row['bank_item'] ?? ''));
            if ($key === '') {
                continue;
            }
            $mapped[$key] = [
                'pretty_name' => (string) ($row['pretty_name'] ?? ''),
                'apply_to' => (string) ($row['apply_to'] ?? 'expense'),
                'expense_type' => (string) ($row['expense_type'] ?? 'variable'),
                'tag_id' => $row['tag_id'] !== null ? (int) $row['tag_id'] : null,
            ];
        }

        return $mapped;
    }

    private function upsertRule($db, string $bankItem, string $prettyName, string $applyTo, ?string $expenseType, ?int $tagId): void
    {
        $payload = [
            'pretty_name' => trim($prettyName) !== '' ? trim($prettyName) : null,
            'apply_to' => $applyTo,
            'expense_type' => $expenseType,
            'tag_id' => $tagId,
        ];

        $id = (int) ($db->get('money_concept_rules', 'id', ['bank_item' => $bankItem]) ?? 0);
        if ($id > 0) {
            $db->update('money_concept_rules', $payload, ['id' => $id]);
            return;
        }

        $payload['bank_item'] = $bankItem;
        $db->insert('money_concept_rules', $payload);
    }

    private function validateTagId($db, int $tagId): ?int
    {
        if ($tagId <= 0) {
            return null;
        }
        return $db->has('money_tags', ['id' => $tagId]) ? $tagId : null;
    }

    private function upsertCurrentAmount($db, float $currentAmount): void
    {
        $id = (int) ($db->get('money_settings', 'id', ['ORDER' => ['id' => 'ASC']]) ?? 0);
        if ($id > 0) {
            $db->update('money_settings', ['current_amount' => round($currentAmount, 2)], ['id' => $id]);
            return;
        }

        $db->insert('money_settings', [
            'monthly_salary' => 0,
            'current_amount' => round($currentAmount, 2),
            'savings_goal' => 0,
        ]);
    }

    private function parseBankDate(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m) !== 1) {
            return '';
        }

        $day = (int) $m[1];
        $month = (int) $m[2];
        $year = (int) $m[3];
        if (!checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function parseBankAmount(string $raw): ?float
    {
        $value = strtoupper(trim($raw));
        if ($value === '') {
            return null;
        }

        $value = str_replace(['EUR', ' '], '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        if (!preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) {
            return null;
        }

        return (float) $value;
    }

    private function normalizeBankItem(string $item): string
    {
        $value = trim($item);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return strtoupper($value);
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

    private function isValidDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
    }

    private function isFutureDate(string $date): bool
    {
        return $date > date('Y-m-d');
    }
}
