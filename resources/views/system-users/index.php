<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$users = is_array($data['users'] ?? null) ? $data['users'] : [];
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'system_users.title';
    $data['pageDescriptionKey'] = 'system_users.description';
    $data['pageActions'] = [['href' => '/system-users/create', 'labelKey' => 'system_users.create_title', 'variant' => 'primary']];
    include __DIR__ . '/../partials/page-header.php';
    ?>
    <?php if (($data['notice'] ?? null) !== null): ?><div class="alert alert--info" role="status"><?= $escape($t('system_users.' . $data['notice'])) ?></div><?php endif; ?>
    <?php if ($users === []): ?>
        <?php
        $data['emptyTitleKey'] = 'system_users.no_users';
        $data['emptyMessageKey'] = 'system_users.empty_description';
        include __DIR__ . '/../partials/empty-state.php';
        ?>
    <?php else: ?>
        <div class="card table-card">
            <div class="table-card__header">
                <div>
                    <h2><?= $escape($t('system_users.list_heading')) ?></h2>
                    <p class="muted-text"><?= $escape($t('system_users.list_description')) ?></p>
                </div>
                <span class="record-count" role="status"><?= $escape($t('system_users.record_count', ['count' => (string) count($users)])) ?></span>
            </div>
            <div class="table-scroll">
                <table class="data-table system-users-table">
                    <colgroup>
                        <col class="system-users-table__col--name">
                        <col class="system-users-table__col--email">
                        <col class="system-users-table__col--role">
                        <col class="system-users-table__col--status">
                        <col class="system-users-table__col--last-login">
                        <col class="system-users-table__col--actions">
                    </colgroup>
                    <thead><tr><th><?= $escape($t('system_users.name')) ?></th><th><?= $escape($t('system_users.email')) ?></th><th><?= $escape($t('system_users.role')) ?></th><th><?= $escape($t('system_users.status')) ?></th><th><?= $escape($t('system_users.last_login')) ?></th><th><span class="visually-hidden"><?= $escape($t('table.actions')) ?></span></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $id = (int) $user['id'];
                        $isActive = ($user['status'] ?? '') === 'active';
                        $roleLabelKey = ($user['role'] ?? '') === 'ADMIN' ? 'system_users.admin' : 'system_users.user';
                        $roleTone = ($user['role'] ?? '') === 'ADMIN' ? 'primary' : 'info';
                        $statusLabelKey = $isActive ? 'system_users.active' : 'system_users.inactive';
                        $statusTone = $isActive ? 'success' : 'neutral';
                        ?>
                        <tr>
                            <td class="system-users-table__cell--name" data-label="<?= $escape($t('system_users.name')) ?>"><a class="table-primary-link" href="/system-users/<?= $id ?>"><?= $escape($user['name']) ?></a></td>
                            <td class="system-users-table__cell--email" data-label="<?= $escape($t('system_users.email')) ?>"><?= $escape($user['email']) ?></td>
                            <td class="system-users-table__cell--role" data-label="<?= $escape($t('system_users.role')) ?>"><?php $chipLabelKey = $roleLabelKey; $chipTone = $roleTone; include __DIR__ . '/../partials/status-chip.php'; ?></td>
                            <td class="system-users-table__cell--status" data-label="<?= $escape($t('system_users.status')) ?>"><?php $chipLabelKey = $statusLabelKey; $chipTone = $statusTone; include __DIR__ . '/../partials/status-chip.php'; ?></td>
                            <td class="system-users-table__cell--last-login" data-label="<?= $escape($t('system_users.last_login')) ?>"><?= $escape($user['last_login_at'] ?? $t('system_users.never_logged_in')) ?></td>
                            <td class="system-users-table__cell--actions"><div class="table-actions"><a class="button button--text button--small" href="/system-users/<?= $id ?>"><?= $escape($t('actions.view')) ?></a></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
