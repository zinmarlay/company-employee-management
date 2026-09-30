<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$values = is_array($data['values'] ?? null) ? $data['values'] : [];
$errorKey = is_string($data['errorKey'] ?? null) ? $data['errorKey'] : null;
?>
<section class="page-section page-section--narrow">
    <div class="card form-card">
        <div class="form-card__header">
            <div><p class="eyebrow"><?= $escape($t('shell.application_name')) ?></p><h1><?= $escape($t('auth.login_title')) ?></h1></div>
        </div>
        <p class="muted-text"><?= $escape($t('auth.login_description')) ?></p>
        <?php if (($data['notice'] ?? null) === 'logged-out'): ?><div class="alert alert--info" role="status"><?= $escape($t('auth.logged_out')) ?></div><?php endif; ?>
        <?php if ($errorKey !== null): ?><div class="alert alert--danger" role="alert"><?= $escape($t($errorKey)) ?></div><?php endif; ?>
        <form method="post" action="/login">
            <?php include __DIR__ . '/../partials/csrf-field.php'; ?>
            <div class="form-grid">
                <div class="form-field form-field--wide"><label for="email"><?= $escape($t('auth.email')) ?></label><input id="email" type="email" name="email" autocomplete="username" value="<?= $escape($values['email'] ?? '') ?>" required></div>
                <div class="form-field form-field--wide"><label for="password"><?= $escape($t('auth.password')) ?></label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
            </div>
            <div class="form-actions"><button class="button button--primary" type="submit"><?= $escape($t('auth.sign_in')) ?></button></div>
        </form>
    </div>
</section>
