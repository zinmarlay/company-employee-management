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
<?php if ($errors !== []): ?><div class="alert alert--danger" role="alert"><strong><?= $escape($t('form.correct_fields')) ?></strong><span><?= $escape($t('form.submitted_values_kept')) ?></span></div><?php endif; ?>
<div class="form-card__header"><div><p class="eyebrow"><?= $escape($t('employees.portfolio')) ?></p><h2><?= $escape($t($data['formTitleKey'] ?? 'employees.project_create_title')) ?></h2></div><p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p></div>
<div class="form-grid">
<div class="form-field form-field--wide"><label for="project_name"><?= $escape($t('portfolio.project_name')) ?> *</label><input id="project_name" name="project_name" maxlength="160" value="<?= $escape($value('project_name')) ?>" required<?= $attributes('project_name') ?>><?= $error('project_name') ?></div>
<div class="form-field"><label for="role"><?= $escape($t('portfolio.role')) ?> *</label><input id="role" name="role" maxlength="120" value="<?= $escape($value('role')) ?>" required<?= $attributes('role') ?>><?= $error('role') ?></div>
<div class="form-field"><label for="start_date"><?= $escape($t('portfolio.start_date')) ?> *</label><input id="start_date" type="date" name="start_date" value="<?= $escape($value('start_date')) ?>" required<?= $attributes('start_date') ?>><?= $error('start_date') ?></div>
<div class="form-field"><label for="end_date"><?= $escape($t('portfolio.end_date')) ?></label><input id="end_date" type="date" name="end_date" value="<?= $escape($value('end_date')) ?>"<?= $attributes('end_date') ?>><?= $error('end_date') ?></div>
<div class="form-field form-field--wide"><label for="description"><?= $escape($t('portfolio.description')) ?></label><textarea id="description" name="description" maxlength="5000" rows="4"<?= $attributes('description') ?>><?= $escape($value('description')) ?></textarea><?= $error('description') ?></div>
<div class="form-field form-field--wide"><label for="responsibilities"><?= $escape($t('portfolio.responsibilities')) ?></label><textarea id="responsibilities" name="responsibilities" maxlength="5000" rows="5"<?= $attributes('responsibilities') ?>><?= $escape($value('responsibilities')) ?></textarea><?= $error('responsibilities') ?></div>
<div class="form-field form-field--wide"><label for="technologies"><?= $escape($t('portfolio.technologies')) ?></label><textarea id="technologies" name="technologies" maxlength="2000" rows="3"<?= $attributes('technologies') ?>><?= $escape($value('technologies')) ?></textarea><?= $error('technologies') ?></div>
</div><div class="form-actions"><a class="button button--secondary" href="<?= $escape((string) $data['cancelHref']) ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--primary" type="submit"><?= $escape($t($data['formSubmitLabelKey'] ?? 'actions.save_changes')) ?></button></div>
</form>

