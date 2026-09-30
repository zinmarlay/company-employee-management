<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$user = is_array($data['systemUser'] ?? null) ? $data['systemUser'] : [];
$id = (int) ($user['id'] ?? 0);
$data['pageEyebrowKey'] = 'system_users.title';
$data['pageDescriptionKey'] = 'system_users.activate_description';
$data['pageActions'] = [['href' => '/system-users/' . $id, 'labelKey' => 'actions.back_to_system_user', 'variant' => 'text']];
?>
<section class="page-section">
    <?php include __DIR__ . '/../partials/page-header.php'; ?>
    <div class="card confirmation-card">
        <div class="confirmation-icon confirmation-icon--success" aria-hidden="true">✓</div>
        <div>
            <h2><?= $escape($t('system_users.activate_confirm', ['name' => $user['name'] ?? ''])) ?></h2>
            <p class="muted-text"><?= $escape($t('system_users.activate_retained')) ?></p>
            <p class="confirmation-meta"><span class="code-text"><?= $escape($user['email'] ?? '') ?></span></p>
        </div>
    </div>
    <form class="card form-card confirmation-form" method="post" action="/system-users/<?= $id ?>/activate">
        <?php include __DIR__ . '/../partials/csrf-field.php'; ?>
        <div class="form-actions"><a class="button button--secondary" href="/system-users/<?= $id ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--primary" type="submit"><?= $escape($t('actions.activate')) ?></button></div>
    </form>
</section>
