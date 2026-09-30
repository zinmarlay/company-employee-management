<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$user = is_array($data['systemUser'] ?? null) ? $data['systemUser'] : [];
$id = (int) ($user['id'] ?? 0);
$isActive = ($user['status'] ?? '') === 'active';
$isAdmin = (bool) ($data['isAdmin'] ?? false);
$notice = is_string($data['notice'] ?? null) ? $data['notice'] : null;
$noticeMap = [
    'already-inactive' => 'already_inactive',
    'already-active' => 'already_active',
    'inactive-readonly' => 'inactive_readonly',
];
$noticeKey = $notice !== null
    ? ($noticeMap[$notice] ?? $notice)
    : null;
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'system_users.title';
    $data['pageDescriptionKey'] = 'system_users.detail_description';
    $data['pageActions'] = [['href' => '/system-users', 'labelKey' => 'actions.back_to_system_users', 'variant' => 'text']];
    if ($isAdmin) {
        if ($isActive) {
            $data['pageActions'][] = ['href' => '/system-users/' . $id . '/edit', 'labelKey' => 'actions.edit', 'variant' => 'secondary'];
            $data['pageActions'][] = ['href' => '/system-users/' . $id . '/deactivate', 'labelKey' => 'actions.deactivate', 'variant' => 'danger'];
        } else {
            $data['pageActions'][] = ['href' => '/system-users/' . $id . '/activate', 'labelKey' => 'actions.activate', 'variant' => 'primary'];
        }
    }
    include __DIR__ . '/../partials/page-header.php';
    ?>
    <?php if (is_string($noticeKey) && $noticeKey !== ''): ?><div class="alert <?= in_array($notice, ['activated', 'deactivated'], true) ? 'alert--success' : 'alert--info' ?>" role="status"><?= $escape($t('system_users.' . $noticeKey)) ?></div><?php endif; ?>
    <div class="profile-summary card">
        <div class="profile-avatar" aria-hidden="true"><?= $escape(strtoupper(substr((string) ($user['name'] ?? 'S'), 0, 1))) ?></div>
        <div class="profile-summary__identity">
            <h2><?= $escape($user['name'] ?? '') ?></h2>
            <p class="muted-text"><?= $escape($user['email'] ?? '') ?></p>
        </div>
        <div class="profile-summary__chips">
            <?php $chipLabelKey = ($user['role'] ?? '') === 'ADMIN' ? 'system_users.admin' : 'system_users.user'; $chipTone = ($user['role'] ?? '') === 'ADMIN' ? 'primary' : 'info'; include __DIR__ . '/../partials/status-chip.php'; ?>
            <?php $chipLabelKey = $isActive ? 'system_users.active' : 'system_users.inactive'; $chipTone = $isActive ? 'success' : 'neutral'; include __DIR__ . '/../partials/status-chip.php'; ?>
        </div>
    </div>
    <article class="card detail-card system-user-detail-card">
        <div class="card-header"><div><p class="eyebrow"><?= $escape($t('system_users.title')) ?></p><h2><?= $escape($t('system_users.account_information')) ?></h2></div></div>
        <dl class="detail-list">
            <div><dt><?= $escape($t('system_users.name')) ?></dt><dd><?= $escape($user['name'] ?? '') ?></dd></div>
            <div><dt><?= $escape($t('system_users.email')) ?></dt><dd><a class="inline-link" href="mailto:<?= $escape($user['email'] ?? '') ?>"><?= $escape($user['email'] ?? '') ?></a></dd></div>
            <div><dt><?= $escape($t('system_users.role')) ?></dt><dd><?php $chipLabelKey = ($user['role'] ?? '') === 'ADMIN' ? 'system_users.admin' : 'system_users.user'; $chipTone = ($user['role'] ?? '') === 'ADMIN' ? 'primary' : 'info'; include __DIR__ . '/../partials/status-chip.php'; ?></dd></div>
            <div><dt><?= $escape($t('system_users.status')) ?></dt><dd><?php $chipLabelKey = $isActive ? 'system_users.active' : 'system_users.inactive'; $chipTone = $isActive ? 'success' : 'neutral'; include __DIR__ . '/../partials/status-chip.php'; ?></dd></div>
            <div><dt><?= $escape($t('system_users.last_login')) ?></dt><dd><?= $escape($user['last_login_at'] ?? $t('system_users.never_logged_in')) ?></dd></div>
            <div><dt><?= $escape($t('system_users.created_at')) ?></dt><dd><?= $escape($user['created_at'] ?? '') ?></dd></div>
            <div><dt><?= $escape($t('system_users.updated_at')) ?></dt><dd><?= $escape($user['updated_at'] ?? '') ?></dd></div>
        </dl>
    </article>
    <?php if (($user['status'] ?? '') === 'inactive'): ?><div class="alert alert--info" role="status"><?= $escape($t('system_users.inactive_readonly')) ?></div><?php endif; ?>
</section>
