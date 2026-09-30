<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$translator = $data['translator'] ?? null;
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$action = (string) ($data['formAction'] ?? '');
$cancelHref = (string) ($data['cancelHref'] ?? '/employees');
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
    <?php include __DIR__ . '/../partials/csrf-field.php'; ?>
    <?php if ($errors !== []): ?><div class="alert alert--danger" role="alert"><strong><?= $escape($t('form.correct_fields')) ?></strong><span><?= $escape($t('form.submitted_values_kept')) ?></span></div><?php endif; ?>
    <div class="form-card__header"><div><p class="eyebrow"><?= $escape($t('employees.portfolio')) ?></p><h2><?= $escape($t($data['formTitleKey'] ?? 'employees.skills_create_title')) ?></h2></div><p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p></div>
    <div class="form-grid">
        <div class="form-field form-field--wide"><label for="skill_name"><?= $escape($t('portfolio.skill')) ?> <span class="required-mark" aria-hidden="true">*</span></label><input id="skill_name" name="skill_name" value="<?= $escape($value('skill_name')) ?>" maxlength="120" required<?= $attributes('skill_name') ?>><?= $error('skill_name') ?></div>
        <div class="form-field"><label for="proficiency"><?= $escape($t('portfolio.proficiency')) ?> <span class="required-mark" aria-hidden="true">*</span></label><select id="proficiency" name="proficiency" required<?= $attributes('proficiency') ?>><?php foreach (['beginner','intermediate','advanced','expert'] as $level): ?><option value="<?= $escape($level) ?>" <?= (string) $value('proficiency') === $level ? 'selected' : '' ?>><?= $escape($t('portfolio.proficiency_' . $level)) ?></option><?php endforeach; ?></select><?= $error('proficiency') ?></div>
        <div class="form-field"><label for="years_experience"><?= $escape($t('portfolio.years_experience')) ?></label><input id="years_experience" name="years_experience" inputmode="decimal" value="<?= $escape($value('years_experience')) ?>" maxlength="4"<?= $attributes('years_experience') ?>><?= $error('years_experience') ?></div>
        <div class="form-field form-field--wide"><label for="notes"><?= $escape($t('portfolio.notes')) ?></label><textarea id="notes" name="notes" maxlength="5000" rows="5"<?= $attributes('notes') ?>><?= $escape($value('notes')) ?></textarea><?= $error('notes') ?></div>
    </div>
    <div class="form-actions"><a class="button button--secondary" href="<?= $escape($cancelHref) ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--primary" type="submit"><?= $escape($t($data['formSubmitLabelKey'] ?? 'actions.save_changes')) ?></button></div>
</form>
