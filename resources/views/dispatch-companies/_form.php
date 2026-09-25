<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$translator = $data['translator'] ?? null;
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$action = (string) ($data['formAction'] ?? '/dispatch-companies');
$submitKey = (string) ($data['formSubmitLabelKey'] ?? 'actions.save_changes');
$cancelHref = (string) ($data['cancelHref'] ?? '/dispatch-companies');
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$value = static fn (string $field): mixed => $values[$field] ?? '';
$error = static function (string $field) use ($errors, $escape, $translator): string {
    if (!isset($errors[$field])) {
        return '';
    }
    $message = (string) $errors[$field];
    if ($translator instanceof \App\Localization\Translator) {
        $message = $translator->validationMessage($message);
    }
    return '<p id="' . $escape($field . '-error') . '" class="field-error" role="alert">' . $escape($message) . '</p>';
};
$attributes = static function (string $field) use ($errors, $escape): string {
    return isset($errors[$field])
        ? ' aria-invalid="true" aria-describedby="' . $escape($field . '-error') . '"'
        : '';
};
?>
<form class="card form-card" method="post" action="<?= $escape($action) ?>">
    <?php if ($errors !== []): ?>
        <div class="alert alert--danger" role="alert">
            <strong><?= $escape($t('form.correct_fields')) ?></strong>
            <span><?= $escape($t('form.submitted_values_kept')) ?></span>
        </div>
    <?php endif; ?>
    <div class="form-card__header">
        <div>
            <p class="eyebrow"><?= $escape($t('dispatch_companies.company_information')) ?></p>
            <h2><?= $escape($t($submitKey === 'actions.create_company' ? 'dispatch_companies.create_title' : 'dispatch_companies.edit_title')) ?></h2>
        </div>
        <p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p>
    </div>
    <div class="form-grid">
        <div class="form-field">
            <label for="code"><?= $escape($t('form.company_code')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="code" name="code" value="<?= $escape($value('code')) ?>" maxlength="30" required<?= $attributes('code') ?>>
            <?= $error('code') ?>
        </div>
        <div class="form-field">
            <label for="name"><?= $escape($t('form.company_name')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="name" name="name" value="<?= $escape($value('name')) ?>" maxlength="160" required<?= $attributes('name') ?>>
            <?= $error('name') ?>
        </div>
        <div class="form-field">
            <label for="phone"><?= $escape($t('form.phone')) ?></label>
            <input id="phone" name="phone" value="<?= $escape($value('phone')) ?>" maxlength="32"<?= $attributes('phone') ?>>
            <?= $error('phone') ?>
        </div>
        <div class="form-field">
            <label for="email"><?= $escape($t('form.email')) ?></label>
            <input id="email" type="email" name="email" value="<?= $escape($value('email')) ?>" maxlength="254"<?= $attributes('email') ?>>
            <?= $error('email') ?>
        </div>
        <div class="form-field form-field--wide">
            <label for="address"><?= $escape($t('form.address')) ?></label>
            <textarea id="address" name="address" maxlength="500" rows="3"<?= $attributes('address') ?>><?= $escape($value('address')) ?></textarea>
            <?= $error('address') ?>
        </div>
    </div>
    <div class="form-actions">
        <a class="button button--secondary" href="<?= $escape($cancelHref) ?>"><?= $escape($t('actions.cancel')) ?></a>
        <button class="button button--primary" type="submit"><?= $escape($t($submitKey)) ?></button>
    </div>
</form>
