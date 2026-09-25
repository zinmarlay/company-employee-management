<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$translator = $data['translator'] ?? null;
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$branches = is_array($data['branches'] ?? null) ? $data['branches'] : [];
$department = is_array($data['department'] ?? null) ? $data['department'] : null;
$editing = $department !== null;
$id = (int) ($data['departmentId'] ?? 0);
$action = $editing ? '/departments/' . $id : '/departments';
$submitKey = $editing ? 'actions.save_changes' : 'actions.create_department';
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$value = static fn (string $field): mixed => $values[$field] ?? '';
$error = static function (string $field) use ($errors, $escape, $translator): string {
    if (!isset($errors[$field])) return '';
    $message = (string) $errors[$field];
    if ($translator instanceof \App\Localization\Translator) $message = $translator->validationMessage($message);
    return '<p id="' . $escape($field . '-error') . '" class="field-error" role="alert">' . $escape($message) . '</p>';
};
$attributes = static fn (string $field): string => isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $escape($field . '-error') . '"' : '';
?>
<form class="card form-card" method="post" action="<?= $escape($action) ?>">
    <?php if ($errors !== []): ?><div class="alert alert--danger" role="alert"><strong><?= $escape($t('form.correct_fields')) ?></strong> <span><?= $escape($t('form.submitted_values_kept')) ?></span></div><?php endif; ?>
    <div class="form-card__header"><div><p class="eyebrow"><?= $escape($t('departments.department_information')) ?></p><h2><?= $escape($t($submitKey)) ?></h2></div><p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p></div>
    <div class="form-grid">
        <div class="form-field form-field--wide"><label for="branch_id"><?= $escape($t('form.branch')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <?php if ($editing): ?><?php $branchLabel = ''; foreach ($branches as $branchRow) { if ((int) $branchRow['id'] === (int) $value('branch_id')) { $branchLabel = ($branchRow['code'] ?? '') . ' ' . ($branchRow['name'] ?? ''); break; } } ?><input type="hidden" name="branch_id" value="<?= $escape($value('branch_id')) ?>"><p class="readonly-field"><?= $escape($branchLabel !== '' ? $branchLabel : $value('branch_id')) ?></p>
            <?php else: ?><select id="branch_id" name="branch_id" required<?= $attributes('branch_id') ?>><option value=""><?= $escape($t('form.select_branch')) ?></option><?php foreach ($branches as $branchRow): ?><option value="<?= (int) $branchRow['id'] ?>"<?= (string) $value('branch_id') === (string) $branchRow['id'] ? ' selected' : '' ?>><?= $escape(($branchRow['code'] ?? '') . ' ' . ($branchRow['name'] ?? '')) ?></option><?php endforeach; ?></select><?php endif; ?><?= $error('branch_id') ?>
        </div>
        <div class="form-field"><label for="code"><?= $escape($t('form.department_code')) ?> <span class="required-mark" aria-hidden="true">*</span></label><input id="code" name="code" value="<?= $escape($value('code')) ?>" maxlength="30" required<?= $attributes('code') ?>><?= $error('code') ?></div>
        <div class="form-field"><label for="name"><?= $escape($t('form.department_name')) ?> <span class="required-mark" aria-hidden="true">*</span></label><input id="name" name="name" value="<?= $escape($value('name')) ?>" maxlength="120" required<?= $attributes('name') ?>><?= $error('name') ?></div>
        <div class="form-field form-field--wide"><label for="description"><?= $escape($t('form.description')) ?></label><textarea id="description" name="description" rows="4"<?= $attributes('description') ?>><?= $escape($value('description')) ?></textarea><?= $error('description') ?></div>
    </div>
    <div class="form-actions"><a class="button button--secondary" href="<?= $escape($editing ? '/departments/' . $id : '/departments') ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--primary" type="submit"><?= $escape($t($submitKey)) ?></button></div>
</form>
