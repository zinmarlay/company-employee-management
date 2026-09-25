<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$translator = $data['translator'] ?? null;
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$employees = is_array($data['employees'] ?? null) ? $data['employees'] : [];
$companies = is_array($data['dispatchCompanies'] ?? null) ? $data['dispatchCompanies'] : [];
$source = is_array($data['sourceContract'] ?? null) ? $data['sourceContract'] : null;
$action = (string) ($data['formAction'] ?? '/dispatch-contracts');
$submitKey = (string) ($data['formSubmitLabelKey'] ?? 'actions.save_changes');
$cancelHref = (string) ($data['cancelHref'] ?? '/dispatch-contracts/create');
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$value = static fn (string $field): mixed => $values[$field] ?? '';
$error = static function (string $field) use ($errors, $escape, $translator): string {
    if (!isset($errors[$field])) return '';
    $message = (string) $errors[$field];
    if ($translator instanceof \App\Localization\Translator) $message = $translator->validationMessage($message);
    return '<p id="' . $escape($field . '-error') . '" class="field-error" role="alert">' . $escape($message) . '</p>';
};
$attributes = static function (string $field) use ($errors, $escape): string {
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $escape($field . '-error') . '"' : '';
};
?>
<form class="card form-card" method="post" action="<?= $escape($action) ?>">
    <?php if ($errors !== []): ?><div class="alert alert--danger" role="alert"><strong><?= $escape($t('form.correct_fields')) ?></strong><span><?= $escape($t('form.submitted_values_kept')) ?></span></div><?php endif; ?>
    <?php if ($source !== null): ?><div class="alert alert--info" role="status"><?= $escape($t('dispatch_contracts.source_contract')) ?>: <span class="code-text">#<?= (int) $source['id'] ?></span> <?= $escape($source['start_date']) ?> → <?= $escape($source['end_date']) ?></div><?php endif; ?>
    <div class="form-card__header"><div><p class="eyebrow"><?= $escape($t('dispatch_contracts.contract_information')) ?></p><h2><?= $escape($t($submitKey === 'actions.create_contract' ? 'dispatch_contracts.create_title' : ($submitKey === 'actions.renew_contract' ? 'dispatch_contracts.renew_title' : 'dispatch_contracts.edit_title'))) ?></h2></div><p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p></div>
    <div class="form-grid">
        <div class="form-field form-field--wide">
            <label for="employee_id"><?= $escape($t('dispatch_contracts.employee')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <select id="employee_id" name="employee_id" required<?= $attributes('employee_id') ?>><option value=""><?= $escape($t('dispatch_contracts.select_employee')) ?></option>
                <?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['id'] ?>" <?= (string) $value('employee_id') === (string) $employee['id'] ? 'selected' : '' ?>><?= $escape($employee['employee_code'] . ' — ' . $employee['last_name'] . ' ' . $employee['first_name']) ?></option><?php endforeach; ?>
            </select><?= $error('employee_id') ?>
        </div>
        <div class="form-field form-field--wide">
            <label for="dispatch_company_id"><?= $escape($t('dispatch_contracts.dispatch_company')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <select id="dispatch_company_id" name="dispatch_company_id" required<?= $attributes('dispatch_company_id') ?>><option value=""><?= $escape($t('dispatch_contracts.select_company')) ?></option>
                <?php foreach ($companies as $company): ?><option value="<?= (int) $company['id'] ?>" <?= (string) $value('dispatch_company_id') === (string) $company['id'] ? 'selected' : '' ?>><?= $escape($company['code'] . ' — ' . $company['name']) ?><?= ($company['status'] ?? 'active') !== 'active' ? $escape($t('form.inactive_suffix')) : '' ?></option><?php endforeach; ?>
            </select><?= $error('dispatch_company_id') ?>
        </div>
        <div class="form-field"><label for="start_date"><?= $escape($t('dispatch_contracts.start_date')) ?> <span class="required-mark" aria-hidden="true">*</span></label><input id="start_date" type="date" name="start_date" value="<?= $escape($value('start_date')) ?>" required<?= $attributes('start_date') ?>><?= $error('start_date') ?></div>
        <div class="form-field"><label for="end_date"><?= $escape($t('dispatch_contracts.end_date')) ?> <span class="required-mark" aria-hidden="true">*</span></label><input id="end_date" type="date" name="end_date" value="<?= $escape($value('end_date')) ?>" required<?= $attributes('end_date') ?>><?= $error('end_date') ?></div>
    </div>
    <div class="form-actions"><a class="button button--secondary" href="<?= $escape($cancelHref) ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--primary" type="submit"><?= $escape($t($submitKey)) ?></button></div>
</form>
