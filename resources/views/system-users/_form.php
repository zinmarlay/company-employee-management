<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;
use App\Localization\Translator;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$translator = $data['translator'] ?? null;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];
$editing = is_array($data['systemUser'] ?? null);
$id = $editing ? (int) $data['systemUser']['id'] : 0;
$action = $editing ? '/system-users/' . $id : '/system-users';
$error = static function (string $field) use ($errors, $escape, $translator): string {
    if (!isset($errors[$field])) return '';
    $message = (string) $errors[$field];
    if ($translator instanceof Translator) $message = $translator->validationMessage($message);
    return '<p class="field-error" role="alert">' . $escape($message) . '</p>';
};
$value = static fn (string $field): mixed => $values[$field] ?? '';
?>
<form class="card form-card" method="post" action="<?= $escape($action) ?>">
    <?php include __DIR__ . '/../partials/csrf-field.php'; ?>
    <?php if ($errors !== []): ?><div class="alert alert--danger" role="alert"><strong><?= $escape($t('form.correct_fields')) ?></strong> <span><?= $escape($t('form.submitted_values_kept')) ?></span></div><?php endif; ?>
    <div class="form-card__header">
        <div>
            <p class="eyebrow"><?= $escape($t('system_users.title')) ?></p>
            <h2><?= $escape($t('system_users.account_information')) ?></h2>
        </div>
        <p class="required-note"><span aria-hidden="true">*</span> <?= $escape($t('form.required_fields')) ?></p>
    </div>
    <div class="form-grid">
        <div class="form-field form-field--wide"><label for="system_user_name"><?= $escape($t('system_users.name')) ?></label><input id="system_user_name" name="name" maxlength="120" value="<?= $escape($value('name')) ?>" required><?= $error('name') ?></div>
        <div class="form-field form-field--wide"><label for="system_user_email"><?= $escape($t('system_users.email')) ?></label><input id="system_user_email" type="email" name="email" maxlength="254" autocomplete="username" value="<?= $escape($value('email')) ?>" required><?= $error('email') ?></div>
        <div class="form-field"><label for="system_user_role"><?= $escape($t('system_users.role')) ?></label><select id="system_user_role" name="role" required><option value="USER"<?= $value('role') === 'USER' ? ' selected' : '' ?>><?= $escape($t('system_users.user')) ?></option><option value="ADMIN"<?= $value('role') === 'ADMIN' ? ' selected' : '' ?>><?= $escape($t('system_users.admin')) ?></option></select><?= $error('role') ?></div>
        <div class="form-field form-field--wide"><p class="form-section-label"><?= $escape($t('system_users.password_information')) ?></p><p class="field-help system-user-password-help"><?= $escape($t('system_users.password_help')) ?></p></div>
        <div class="form-field"><label for="system_user_password"><?= $escape($t('system_users.password')) ?></label><input id="system_user_password" type="password" name="password" autocomplete="new-password"<?= $editing ? '' : ' required' ?>><?= $error('password') ?></div>
        <div class="form-field"><label for="system_user_password_confirmation"><?= $escape($t('system_users.password_confirmation')) ?></label><input id="system_user_password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"<?= $editing ? '' : ' required' ?>><?= $error('password_confirmation') ?></div>
    </div>
    <div class="form-actions"><a class="button button--secondary" href="<?= $escape($editing ? '/system-users/' . $id : '/system-users') ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--primary" type="submit"><?= $escape($t($editing ? 'actions.save_changes' : 'system_users.create_title')) ?></button></div>
</form>
