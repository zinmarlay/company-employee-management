<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$branches = is_array($data['branches'] ?? null) ? $data['branches'] : [];
$departmentGroups = is_array($data['departmentGroups'] ?? null) ? $data['departmentGroups'] : [];
$action = (string) ($data['formAction'] ?? '');
$submitLabel = (string) ($data['formSubmitLabel'] ?? 'Save');
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);

$value = static function (string $field) use ($values): mixed {
    return $values[$field] ?? '';
};

$error = static function (string $field) use ($errors, $escape): string {
    return isset($errors[$field]) ? '<p class="field-error">' . $escape($errors[$field]) . '</p>' : '';
};

$selectedBranch = (string) ($values['branch_id'] ?? '');
$selectedDepartment = (string) ($values['department_id'] ?? '');
?>
<?php if ($errors !== []): ?>
    <p role="alert">Please correct the highlighted fields.</p>
<?php endif; ?>

<form method="post" action="<?= $escape($action) ?>">
    <div>
        <label for="employee_code">Employee code</label>
        <input id="employee_code" name="employee_code" value="<?= $escape($value('employee_code')) ?>" maxlength="40" required>
        <?= $error('employee_code') ?>
    </div>
    <div>
        <label for="last_name">Last name</label>
        <input id="last_name" name="last_name" value="<?= $escape($value('last_name')) ?>" maxlength="100" required>
        <?= $error('last_name') ?>
    </div>
    <div>
        <label for="first_name">First name</label>
        <input id="first_name" name="first_name" value="<?= $escape($value('first_name')) ?>" maxlength="100" required>
        <?= $error('first_name') ?>
    </div>
    <div>
        <label for="last_name_kana">Last name kana</label>
        <input id="last_name_kana" name="last_name_kana" value="<?= $escape($value('last_name_kana')) ?>" maxlength="100" required>
        <?= $error('last_name_kana') ?>
    </div>
    <div>
        <label for="first_name_kana">First name kana</label>
        <input id="first_name_kana" name="first_name_kana" value="<?= $escape($value('first_name_kana')) ?>" maxlength="100" required>
        <?= $error('first_name_kana') ?>
    </div>
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= $escape($value('email')) ?>" maxlength="254" required>
        <?= $error('email') ?>
    </div>
    <div>
        <label for="phone">Phone</label>
        <input id="phone" name="phone" value="<?= $escape($value('phone')) ?>" maxlength="32">
        <?= $error('phone') ?>
    </div>
    <div>
        <label for="position_title">Position title</label>
        <input id="position_title" name="position_title" value="<?= $escape($value('position_title')) ?>" maxlength="120">
        <?= $error('position_title') ?>
    </div>
    <div>
        <label for="branch_id">Branch</label>
        <select id="branch_id" name="branch_id" required>
            <option value="">Select a branch</option>
            <?php foreach ($branches as $branch): ?>
                <?php
                $branchId = (string) $branch['id'];
                $isSelected = $selectedBranch === $branchId;
                $isInactiveCurrent = $branch['status'] !== 'active' && !$isSelected;
                ?>
                <option value="<?= $escape($branchId) ?>" <?= $isSelected ? 'selected' : '' ?> <?= $isInactiveCurrent ? 'disabled' : '' ?>>
                    <?= $escape($branch['name']) ?><?= $branch['status'] !== 'active' ? ' (inactive)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= $error('branch_id') ?>
    </div>
    <div>
        <label for="department_id">Department</label>
        <select id="department_id" name="department_id">
            <option value="">Not assigned</option>
            <?php foreach ($departmentGroups as $group): ?>
                <?php $groupBranchId = (string) $group['branch']['id']; ?>
                <optgroup label="<?= $escape($group['branch']['name']) ?>">
                    <?php foreach ($group['departments'] as $department): ?>
                        <?php
                        $departmentId = (string) $department['id'];
                        $isSelected = $selectedDepartment === $departmentId;
                        $isInactiveCurrent = $department['status'] !== 'active' && !$isSelected;
                        ?>
                        <option value="<?= $escape($departmentId) ?>" <?= $isSelected ? 'selected' : '' ?> <?= $isInactiveCurrent ? 'disabled' : '' ?>>
                            <?= $escape($department['name']) ?><?= $department['status'] !== 'active' ? ' (inactive)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <?= $error('department_id') ?>
    </div>
    <div>
        <label for="employee_type">Employee type</label>
        <select id="employee_type" name="employee_type" required>
            <?php foreach (['permanent' => 'Permanent', 'dispatched' => 'Dispatched'] as $type => $label): ?>
                <option value="<?= $escape($type) ?>" <?= $value('employee_type') === $type ? 'selected' : '' ?>>
                    <?= $escape($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= $error('employee_type') ?>
    </div>
    <div>
        <label for="hire_date">Hire date</label>
        <input id="hire_date" type="date" name="hire_date" value="<?= $escape($value('hire_date')) ?>" required>
        <?= $error('hire_date') ?>
    </div>
    <button type="submit"><?= $escape($submitLabel) ?></button>
</form>
