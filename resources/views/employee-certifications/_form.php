<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$translator = $data['translator'] ?? null;
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$value = static fn (string $field): mixed => $values[$field] ?? '';
$error = static function (string $field) use ($errors, $escape, $translator): string { if (!isset($errors[$field])) return ''; $message = (string) $errors[$field]; if ($translator instanceof \App\Localization\Translator) $message = $translator->validationMessage($message); return '<p id="' . $escape($field . '-error') . '" class="field-error" role="alert">' . $escape($message) . '</p>'; };
$attributes = static fn (string $field): string => isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $escape($field . '-error') . '"' : '';
?>
<form class="card form-card" method="post" action="<?= $escape((string) $data['formAction']) ?>">
<?php include __DIR__ . '/../partials/csrf-field.php'; ?>
<?php if ($errors !== []): ?><div class="alert alert--danger" role="alert"><strong><?= $escape($t('form.correct_fields')) ?></strong><span><?= $escape($t('form.submitted_values_kept')) ?></span></div><?php endif; ?>
<div class="form-card__header"><div><p class="eyebrow"><?= $escape($t('employees.portfolio')) ?></p><h2><?= $escape($t($data['formTitleKey'] ?? 'employees.certification_create_title')) ?></h2></div><p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p></div>
<div class="form-grid">
<div class="form-field form-field--wide"><label for="certification_name"><?= $escape($t('portfolio.certification_name')) ?> *</label><input id="certification_name" name="certification_name" maxlength="160" value="<?= $escape($value('certification_name')) ?>" required<?= $attributes('certification_name') ?>><?= $error('certification_name') ?></div>
<div class="form-field"><label for="issuing_organization"><?= $escape($t('portfolio.issuing_organization')) ?> *</label><input id="issuing_organization" name="issuing_organization" maxlength="120" value="<?= $escape($value('issuing_organization')) ?>" required<?= $attributes('issuing_organization') ?>><?= $error('issuing_organization') ?></div>
<div class="form-field"><label for="obtained_date"><?= $escape($t('portfolio.obtained_date')) ?> *</label><input id="obtained_date" type="date" name="obtained_date" value="<?= $escape($value('obtained_date')) ?>" required<?= $attributes('obtained_date') ?>><?= $error('obtained_date') ?></div>
<div class="form-field"><label for="expiration_date"><?= $escape($t('portfolio.expiration_date')) ?></label><input id="expiration_date" type="date" name="expiration_date" value="<?= $escape($value('expiration_date')) ?>"<?= $attributes('expiration_date') ?>><?= $error('expiration_date') ?></div>
<div class="form-field"><label for="credential_identifier"><?= $escape($t('portfolio.credential_identifier')) ?></label><input id="credential_identifier" name="credential_identifier" maxlength="120" value="<?= $escape($value('credential_identifier')) ?>"<?= $attributes('credential_identifier') ?>><?= $error('credential_identifier') ?></div>
<div class="form-field form-field--wide"><label for="notes"><?= $escape($t('portfolio.notes')) ?></label><textarea id="notes" name="notes" maxlength="5000" rows="5"<?= $attributes('notes') ?>><?= $escape($value('notes')) ?></textarea><?= $error('notes') ?></div>
</div><div class="form-actions"><a class="button button--secondary" href="<?= $escape((string) $data['cancelHref']) ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--primary" type="submit"><?= $escape($t($data['formSubmitLabelKey'] ?? 'actions.save_changes')) ?></button></div>
</form>
