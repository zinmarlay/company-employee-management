<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$translator = $data['translator'] ?? null;
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$branches = is_array($data['branches'] ?? null) ? $data['branches'] : [];
$departmentGroups = is_array($data['departmentGroups'] ?? null) ? $data['departmentGroups'] : [];
$action = (string) ($data['formAction'] ?? '');
$submitLabel = isset($data['formSubmitLabelKey']) ? $t((string) $data['formSubmitLabelKey']) : (string) ($data['formSubmitLabel'] ?? $t('actions.save_changes'));
$submitLabelKey = (string) ($data['formSubmitLabelKey'] ?? 'actions.save_changes');
$cancelHref = (string) ($data['cancelHref'] ?? '/employees');
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);

$value = static function (string $field) use ($values): mixed {
    return $values[$field] ?? '';
};

$error = static function (string $field) use ($errors, $escape, $translator): string {
    if (!isset($errors[$field])) {
        return '';
    }

    $message = (string) $errors[$field];
    if ($translator instanceof \App\Localization\Translator) {
        $message = $translator->validationMessage($message);
    }

    return '<p id="' . $escape($field . '-error') . '" class="field-error" role="alert">'
        . $escape($message)
        . '</p>';
};

$fieldAttributes = static function (string $field) use ($errors, $escape): string {
    if (!isset($errors[$field])) {
        return '';
    }

    return ' aria-invalid="true" aria-describedby="' . $escape($field . '-error') . '"';
};

$selectedBranch = (string) ($values['branch_id'] ?? '');
$selectedDepartment = (string) ($values['department_id'] ?? '');
$employeeCode = $data['employeeCode'] ?? null;
?>
<form class="card form-card" method="post" action="<?= $escape($action) ?>">
    <?php if ($errors !== []): ?>
        <div class="alert alert--danger" role="alert">
            <strong><?= $escape($t('form.correct_fields')) ?></strong>
            <span><?= $escape($t('form.submitted_values_kept')) ?></span>
            <?php if (isset($errors['form'])): ?>
                <span><?= $escape($translator instanceof \App\Localization\Translator ? $translator->validationMessage((string) $errors['form']) : (string) $errors['form']) ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-card__header">
        <div>
            <p class="eyebrow"><?= $escape($t('form.employee_information')) ?></p>
            <h2><?= $escape($t($submitLabelKey === 'actions.create_employee' ? 'form.add_details' : 'form.update_details')) ?></h2>
        </div>
        <p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p>
    </div>

    <div class="form-grid">
        <div class="form-field">
            <span class="form-label"><?= $escape($t('form.employee_code')) ?></span>
            <?php if (is_string($employeeCode) && $employeeCode !== ''): ?>
                <p class="code-text"><?= $escape($employeeCode) ?></p>
            <?php else: ?>
                <p class="field-help"><?= $escape($t('form.employee_code_auto_assigned')) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="hire_date"><?= $escape($t('form.hire_date')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="hire_date" type="date" name="hire_date" value="<?= $escape($value('hire_date')) ?>" required<?= $fieldAttributes('hire_date') ?>>
            <?= $error('hire_date') ?>
        </div>

        <div class="form-field">
            <label for="last_name"><?= $escape($t('form.last_name')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="last_name" name="last_name" value="<?= $escape($value('last_name')) ?>" maxlength="100" required<?= $fieldAttributes('last_name') ?>>
            <?= $error('last_name') ?>
        </div>

        <div class="form-field">
            <label for="first_name"><?= $escape($t('form.first_name')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="first_name" name="first_name" value="<?= $escape($value('first_name')) ?>" maxlength="100" required<?= $fieldAttributes('first_name') ?>>
            <?= $error('first_name') ?>
        </div>

        <div class="form-field">
            <label for="last_name_kana"><?= $escape($t('form.last_name_kana')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="last_name_kana" name="last_name_kana" value="<?= $escape($value('last_name_kana')) ?>" maxlength="100" required<?= $fieldAttributes('last_name_kana') ?>>
            <?= $error('last_name_kana') ?>
        </div>

        <div class="form-field">
            <label for="first_name_kana"><?= $escape($t('form.first_name_kana')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="first_name_kana" name="first_name_kana" value="<?= $escape($value('first_name_kana')) ?>" maxlength="100" required<?= $fieldAttributes('first_name_kana') ?>>
            <?= $error('first_name_kana') ?>
        </div>

        <div class="form-field form-field--wide">
            <label for="email"><?= $escape($t('form.email')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="email" type="email" name="email" value="<?= $escape($value('email')) ?>" maxlength="254" required<?= $fieldAttributes('email') ?>>
            <?= $error('email') ?>
        </div>

        <div class="form-field">
            <label for="phone"><?= $escape($t('form.phone')) ?></label>
            <input id="phone" type="tel" name="phone" value="<?= $escape($value('phone')) ?>" maxlength="32"<?= $fieldAttributes('phone') ?>>
            <?= $error('phone') ?>
        </div>

        <div class="form-field">
            <label for="branch_id"><?= $escape($t('form.branch')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <select id="branch_id" name="branch_id" required<?= $fieldAttributes('branch_id') ?>>
                <option value=""><?= $escape($t('form.select_branch')) ?></option>
                <?php foreach ($branches as $branch): ?>
                    <?php
                    $branchId = (string) $branch['id'];
                    $isSelected = $selectedBranch === $branchId;
                    $isInactiveCurrent = $branch['status'] !== 'active' && !$isSelected;
                    ?>
                    <option value="<?= $escape($branchId) ?>" <?= $isSelected ? 'selected' : '' ?> <?= $isInactiveCurrent ? 'disabled' : '' ?>>
                        <?= $escape($branch['name']) ?><?= $branch['status'] !== 'active' ? $escape($t('form.inactive_suffix')) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $error('branch_id') ?>
        </div>

        <div class="form-field">
            <label for="department_id"><?= $escape($t('form.department')) ?></label>
            <select id="department_id" name="department_id"<?= $fieldAttributes('department_id') ?>>
                <option value=""><?= $escape($t('form.not_assigned')) ?></option>
                <?php foreach ($departmentGroups as $group): ?>
                    <optgroup label="<?= $escape($group['branch']['name']) ?>">
                        <?php foreach ($group['departments'] as $department): ?>
                            <?php
                            $departmentId = (string) $department['id'];
                            $isSelected = $selectedDepartment === $departmentId;
                            $isInactiveCurrent = $department['status'] !== 'active' && !$isSelected;
                            ?>
                            <option value="<?= $escape($departmentId) ?>" <?= $isSelected ? 'selected' : '' ?> <?= $isInactiveCurrent ? 'disabled' : '' ?>>
                                <?= $escape($department['name']) ?><?= $department['status'] !== 'active' ? $escape($t('form.inactive_suffix')) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <?= $error('department_id') ?>
        </div>

        <div class="form-field">
            <label for="position_title"><?= $escape($t('form.position_title')) ?></label>
            <input id="position_title" name="position_title" value="<?= $escape($value('position_title')) ?>" maxlength="120"<?= $fieldAttributes('position_title') ?>>
            <?= $error('position_title') ?>
        </div>

        <div class="form-field">
            <label for="employee_type"><?= $escape($t('form.employee_type')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <select id="employee_type" name="employee_type" required<?= $fieldAttributes('employee_type') ?>>
                <?php foreach (['permanent' => 'status.permanent', 'dispatched' => 'status.dispatched'] as $type => $labelKey): ?>
                    <option value="<?= $escape($type) ?>" <?= $value('employee_type') === $type ? 'selected' : '' ?>>
                        <?= $escape($t($labelKey)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $error('employee_type') ?>
        </div>
    </div>

    <div class="form-actions">
        <a class="button button--secondary" href="<?= $escape($cancelHref) ?>"><?= $escape($t('actions.cancel')) ?></a>
        <button class="button button--primary" type="submit"><?= $escape($submitLabel) ?></button>
    </div>
</form>
