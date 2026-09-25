<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$branches = is_array($data['branches'] ?? null) ? $data['branches'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
?>
<section class="page-section">
    <?php $data['pageEyebrowKey'] = 'branches.directory'; $data['pageDescriptionKey'] = 'branches.description'; $data['pageActions'] = [['href' => '/branches/create', 'labelKey' => 'actions.create_branch', 'variant' => 'primary']]; include __DIR__ . '/../partials/page-header.php'; ?>
    <?php if (($data['notice'] ?? null) === 'deactivated'): ?><div class="alert alert--success" role="status"><?= $escape($t('branches.deactivated_success')) ?></div><?php elseif (($data['notice'] ?? null) === 'already-inactive'): ?><div class="alert alert--info" role="status"><?= $escape($t('branches.already_inactive_notice')) ?></div><?php endif; ?>
    <?php if ($branches === []): ?>
        <?php $data['emptyTitleKey'] = 'branches.no_branches'; $data['emptyMessageKey'] = 'branches.empty_description'; $data['emptyActionHref'] = '/branches/create'; $data['emptyActionLabelKey'] = 'actions.create_branch'; include __DIR__ . '/../partials/empty-state.php'; ?>
    <?php else: ?>
        <div class="card table-card"><div class="table-card__header"><div><h2><?= $escape($t('branches.list_heading')) ?></h2><p class="muted-text"><?= $escape($t('branches.list_description')) ?></p></div><span class="record-count"><?= $escape($t('branches.record_count', ['count' => (string) count($branches)])) ?></span></div><div class="table-scroll"><table class="data-table"><thead><tr><th><?= $escape($t('form.company')) ?></th><th><?= $escape($t('form.branch_code')) ?></th><th><?= $escape($t('form.branch_name')) ?></th><th><?= $escape($t('form.city')) ?></th><th><?= $escape($t('table.departments')) ?></th><th><?= $escape($t('table.employees')) ?></th><th><?= $escape($t('table.status')) ?></th><th><span class="visually-hidden"><?= $escape($t('table.actions')) ?></span></th></tr></thead><tbody>
        <?php foreach ($branches as $branch): $id = (int) $branch['id']; $active = ($branch['status'] ?? '') === 'active'; ?>
            <tr><td data-label="<?= $escape($t('form.company')) ?>"><?= $escape(($branch['company_code'] ?? '') . ' ' . ($branch['company_name'] ?? '')) ?></td><td data-label="<?= $escape($t('form.branch_code')) ?>"><span class="code-text"><?= $escape($branch['code']) ?></span></td><td data-label="<?= $escape($t('form.branch_name')) ?>"><a class="table-primary-link" href="/branches/<?= $id ?>"><?= $escape($branch['name']) ?></a></td><td data-label="<?= $escape($t('form.city')) ?>"><?= $escape($branch['city']) ?></td><td data-label="<?= $escape($t('table.departments')) ?>"><?= $escape($branch['department_count'] ?? 0) ?></td><td data-label="<?= $escape($t('table.employees')) ?>"><?= $escape($branch['employee_count'] ?? 0) ?></td><td data-label="<?= $escape($t('table.status')) ?>"><?php $chipLabelKey = $active ? 'status.active' : 'status.inactive'; $chipTone = $active ? 'success' : 'neutral'; include __DIR__ . '/../partials/status-chip.php'; ?></td><td><div class="table-actions"><a class="button button--text button--small" href="/branches/<?= $id ?>"><?= $escape($t('actions.view')) ?></a><a class="button button--text button--small" href="/branches/<?= $id ?>/edit"><?= $escape($t('actions.edit')) ?></a><?php if ($active): ?><a class="button button--text button--small button--danger-text" href="/branches/<?= $id ?>/deactivate"><?= $escape($t('actions.deactivate')) ?></a><?php endif; ?></div></td></tr>
        <?php endforeach; ?></tbody></table></div></div>
    <?php endif; ?>
</section>
