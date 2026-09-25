<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$appName = (string) ($data['appName'] ?? 'Company Employee Management System');
$displayAppName = isset($data['appNameKey']) ? $t((string) $data['appNameKey']) : $appName;
$requestPath = HtmlEscaper::escape($data['requestPath'] ?? '/');
?>
<section class="page-section">
    <div class="page-header">
        <div>
            <p class="eyebrow"><?= HtmlEscaper::escape($t('shell.overview')) ?></p>
            <h1><?= HtmlEscaper::escape($t('shell.welcome', ['app' => $displayAppName])) ?></h1>
            <p class="page-description"><?= HtmlEscaper::escape($t('shell.welcome_description')) ?></p>
        </div>
        <div class="page-actions">
            <a class="button button--primary" href="/employees"><?= HtmlEscaper::escape($t('actions.open_employees')) ?></a>
        </div>
    </div>

    <div class="dashboard-grid">
        <article class="card welcome-card">
            <div class="card-icon" aria-hidden="true">✦</div>
            <div>
                <p class="eyebrow"><?= HtmlEscaper::escape($t('shell.getting_started')) ?></p>
                <h2><?= HtmlEscaper::escape($t('shell.organize_records')) ?></h2>
                <p class="muted-text"><?= HtmlEscaper::escape($t('shell.organize_description')) ?></p>
                <a class="inline-link" href="/employees"><?= HtmlEscaper::escape($t('shell.go_to_employee_management')) ?> <span aria-hidden="true">→</span></a>
            </div>
        </article>

        <article class="card status-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow"><?= HtmlEscaper::escape($t('shell.interface')) ?></p>
                    <h2><?= HtmlEscaper::escape($t('shell.application_ready')) ?></h2>
                </div>
                <?php $chipLabelKey = 'shell.ready'; $chipTone = 'success'; include __DIR__ . '/partials/status-chip.php'; ?>
            </div>
            <p class="muted-text"><?= HtmlEscaper::escape($t('shell.additional_workspaces')) ?></p>
            <dl class="compact-list">
                <div>
                    <dt><?= HtmlEscaper::escape($t('shell.current_path')) ?></dt>
                    <dd><code><?= $requestPath ?></code></dd>
                </div>
                <div>
                    <dt><?= HtmlEscaper::escape($t('shell.interface')) ?></dt>
                    <dd><?= HtmlEscaper::escape($t('shell.server_rendered')) ?></dd>
                </div>
            </dl>
        </article>
    </div>
</section>
