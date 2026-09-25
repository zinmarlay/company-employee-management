<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$companies = is_array($data['companies'] ?? null) ? $data['companies'] : [];
$notice = $data['notice'] ?? null;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'dispatch_companies.directory';
    $data['pageDescriptionKey'] = 'dispatch_companies.description';
    $data['pageActions'] = [
        ['href' => '/dispatch-companies/create', 'labelKey' => 'actions.create_company', 'variant' => 'primary'],
    ];
    include __DIR__ . '/../partials/page-header.php';
    ?>
    <?php if ($notice === 'deactivated'): ?>
        <div class="alert alert--success" role="status"><?= $escape($t('dispatch_companies.deactivated_success')) ?></div>
    <?php elseif ($notice === 'already-inactive'): ?>
        <div class="alert alert--info" role="status"><?= $escape($t('dispatch_companies.already_inactive_notice')) ?></div>
    <?php endif; ?>
    <?php if ($companies === []): ?>
        <?php
        $data['emptyTitleKey'] = 'dispatch_companies.no_companies';
        $data['emptyMessageKey'] = 'dispatch_companies.empty_description';
        $data['emptyActionHref'] = '/dispatch-companies/create';
        $data['emptyActionLabelKey'] = 'actions.create_company';
        include __DIR__ . '/../partials/empty-state.php';
        ?>
    <?php else: ?>
        <div class="card table-card">
            <div class="table-card__header">
                <div>
                    <h2><?= $escape($t('dispatch_companies.list_heading')) ?></h2>
                    <p class="muted-text"><?= $escape($t('dispatch_companies.list_description')) ?></p>
                </div>
                <span class="record-count"><?= $escape($t('dispatch_companies.record_count', ['count' => (string) count($companies)])) ?></span>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr>
                        <th scope="col"><?= $escape($t('table.code')) ?></th>
                        <th scope="col"><?= $escape($t('table.name')) ?></th>
                        <th scope="col"><?= $escape($t('table.email')) ?></th>
                        <th scope="col"><?= $escape($t('table.contracts')) ?></th>
                        <th scope="col"><?= $escape($t('table.status')) ?></th>
                        <th scope="col"><span class="visually-hidden"><?= $escape($t('table.actions')) ?></span></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($companies as $company): ?>
                        <?php $id = (int) $company['id']; $active = $company['status'] === 'active'; ?>
                        <tr>
                            <td data-label="<?= $escape($t('table.code')) ?>"><span class="code-text"><?= $escape($company['code']) ?></span></td>
                            <td data-label="<?= $escape($t('table.name')) ?>"><a class="table-primary-link" href="/dispatch-companies/<?= $id ?>"><?= $escape($company['name']) ?></a></td>
                            <td data-label="<?= $escape($t('table.email')) ?>"><?= $escape($company['email'] ?? $t('status.not_provided')) ?></td>
                            <td data-label="<?= $escape($t('table.contracts')) ?>"><?= $escape($company['contract_count'] ?? 0) ?></td>
                            <td data-label="<?= $escape($t('table.status')) ?>"><?php $chipLabelKey = $active ? 'status.active' : 'status.inactive'; $chipTone = $active ? 'success' : 'neutral'; include __DIR__ . '/../partials/status-chip.php'; ?></td>
                            <td data-label="<?= $escape($t('table.actions')) ?>"><div class="table-actions">
                                <a class="button button--text button--small" href="/dispatch-companies/<?= $id ?>"><?= $escape($t('actions.view')) ?></a>
                                <a class="button button--text button--small" href="/dispatch-companies/<?= $id ?>/edit"><?= $escape($t('actions.edit')) ?></a>
                                <?php if ($active): ?><a class="button button--text button--small button--danger-text" href="/dispatch-companies/<?= $id ?>/deactivate"><?= $escape($t('actions.deactivate')) ?></a><?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
