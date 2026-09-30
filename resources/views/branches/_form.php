<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$translator = $data['translator'] ?? null;
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$companies = is_array($data['companies'] ?? null) ? $data['companies'] : [];
$prefectures = is_array($data['prefectures'] ?? null) ? $data['prefectures'] : [];
$branch = is_array($data['branch'] ?? null) ? $data['branch'] : null;
$editing = $branch !== null;
$id = (int) ($data['branchId'] ?? 0);
$action = $editing ? '/branches/' . $id : '/branches';
$submitKey = $editing ? 'actions.save_changes' : 'actions.create_branch';
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
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $escape($field . '-error') . '"' : '';
};
$currentPrefectureLabel = $branch['prefecture_display_label'] ?? ($branch['prefecture_label_en'] ?? ($branch['prefecture_label_ja'] ?? $value('prefecture_code')));
?>
<form class="card form-card" method="post" action="<?= $escape($action) ?>">
    <?php include __DIR__ . '/../partials/csrf-field.php'; ?>
    <?php if ($errors !== []): ?>
        <div class="alert alert--danger" role="alert"><strong><?= $escape($t('form.correct_fields')) ?></strong> <span><?= $escape($t('form.submitted_values_kept')) ?></span></div>
    <?php endif; ?>
    <div class="form-card__header"><div><p class="eyebrow"><?= $escape($t('branches.branch_information')) ?></p><h2><?= $escape($t($submitKey)) ?></h2></div><p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p></div>
    <div class="form-grid">
        <div class="form-field form-field--wide">
            <label for="company_id"><?= $escape($t('form.company')) ?> <span class="required-mark" aria-hidden="true">*</span></label>
            <?php if ($editing): ?>
                <?php $companyLabel = ''; foreach ($companies as $company) { if ((int) $company['id'] === (int) $value('company_id')) { $companyLabel = $company['code'] . ' ' . $company['name']; break; } } ?>
                <p class="readonly-field"><?= $escape($companyLabel !== '' ? $companyLabel : $value('company_id')) ?></p>
            <?php else: ?>
                <select id="company_id" name="company_id" required<?= $attributes('company_id') ?>><option value=""><?= $escape($t('form.select_company')) ?></option><?php foreach ($companies as $company): ?><option value="<?= (int) $company['id'] ?>"<?= (string) $value('company_id') === (string) $company['id'] ? ' selected' : '' ?>><?= $escape($company['code'] . ' ' . $company['name']) ?></option><?php endforeach; ?></select>
            <?php endif; ?>
            <?= $error('company_id') ?>
        </div>
        <?php if ($editing): ?>
            <div class="form-field"><label><?= $escape($t('form.prefecture')) ?></label><p class="readonly-field"><?= $escape($currentPrefectureLabel) ?></p></div>
            <div class="form-field"><label><?= $escape($t('form.branch_code')) ?></label><p class="readonly-field code-text"><?= $escape($value('code')) ?></p></div>
            <div class="form-field"><label><?= $escape($t('form.branch_name')) ?></label><p class="readonly-field"><?= $escape($branch['display_name'] ?? $value('name')) ?></p></div>
        <?php else: ?>
            <div class="form-field form-field--wide"><label for="prefecture_code"><?= $escape($t('form.prefecture')) ?> <span class="required-mark" aria-hidden="true">*</span></label><select id="prefecture_code" name="prefecture_code" required<?= $attributes('prefecture_code') ?>><option value=""><?= $escape($t('form.select_prefecture')) ?></option><?php foreach ($prefectures as $prefecture): ?><option value="<?= $escape($prefecture['code']) ?>"<?= (string) $value('prefecture_code') === (string) $prefecture['code'] ? ' selected' : '' ?>><?= $escape($prefecture['display_label'] ?? $prefecture['name']) ?></option><?php endforeach; ?></select><?= $error('prefecture_code') ?></div>
        <?php endif; ?>
        <div class="form-field"><label for="city"><?= $escape($t('form.city')) ?> <span class="required-mark" aria-hidden="true">*</span></label><input id="city" name="city" value="<?= $escape($value('city')) ?>" maxlength="120" required<?= $attributes('city') ?>><?= $error('city') ?></div>
        <div class="form-field"><label for="phone"><?= $escape($t('form.phone')) ?> <span class="required-mark" aria-hidden="true">*</span></label><input id="phone" name="phone" value="<?= $escape($value('phone')) ?>" maxlength="32" required<?= $attributes('phone') ?>><?= $error('phone') ?></div>
        <div class="form-field form-field--wide"><label for="address"><?= $escape($t('form.address')) ?> <span class="required-mark" aria-hidden="true">*</span></label><textarea id="address" name="address" maxlength="500" rows="3" required<?= $attributes('address') ?>><?= $escape($value('address')) ?></textarea><?= $error('address') ?></div>
    </div>
    <div class="form-actions"><a class="button button--secondary" href="<?= $escape($editing ? '/branches/' . $id : '/branches') ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--primary" type="submit"><?= $escape($t($submitKey)) ?></button></div>
</form>
