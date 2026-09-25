<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$company = is_array($data['company'] ?? null) ? $data['company'] : [];
$contracts = is_array($company['contracts'] ?? null) ? $company['contracts'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($company['id'] ?? 0);
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'dispatch_companies.directory';
    $data['pageDescriptionKey'] = 'dispatch_companies.description';
    $data['pageActions'] = [
        ['href' => '/dispatch-companies', 'labelKey' => 'actions.back_to_companies', 'variant' => 'text'],
        ['href' => '/dispatch-companies/' . $id . '/edit', 'labelKey' => 'actions.edit_company', 'variant' => 'secondary'],
    ];
    include __DIR__ . '/../partials/page-header.php';
    ?>
    <?php if (($data['notice'] ?? null) === 'deactivated'): ?><div class="alert alert--success" role="status"><?= $escape($t('dispatch_companies.deactivated_success')) ?></div><?php endif; ?>
    <?php if (($data['notice'] ?? null) === 'already-inactive'): ?><div class="alert alert--info" role="status"><?= $escape($t('dispatch_companies.already_inactive_notice')) ?></div><?php endif; ?>
    <div class="detail-grid">
        <article class="card detail-card">
            <div class="card-header"><div><p class="eyebrow"><?= $escape($t('dispatch_companies.company_information')) ?></p><h2><?= $escape($company['name'] ?? '') ?></h2></div><?php $chipLabelKey = ($company['status'] ?? '') === 'active' ? 'status.active' : 'status.inactive'; $chipTone = ($company['status'] ?? '') === 'active' ? 'success' : 'neutral'; include __DIR__ . '/../partials/status-chip.php'; ?></div>
            <dl class="detail-list">
                <div><dt><?= $escape($t('form.company_code')) ?></dt><dd class="code-text"><?= $escape($company['code'] ?? '') ?></dd></div>
                <div><dt><?= $escape($t('form.email')) ?></dt><dd><?= $escape($company['email'] ?? $t('status.not_provided')) ?></dd></div>
                <div><dt><?= $escape($t('form.phone')) ?></dt><dd><?= $escape($company['phone'] ?? $t('status.not_provided')) ?></dd></div>
                <div><dt><?= $escape($t('form.address')) ?></dt><dd><?= $escape($company['address'] ?? $t('status.not_provided')) ?></dd></div>
                <div><dt><?= $escape($t('dispatch_contracts.created')) ?></dt><dd><?= $escape($company['created_at'] ?? '') ?></dd></div>
                <div><dt><?= $escape($t('dispatch_contracts.updated')) ?></dt><dd><?= $escape($company['updated_at'] ?? '') ?></dd></div>
            </dl>
        </article>
        <article class="card detail-card">
            <div class="card-header"><div><p class="eyebrow"><?= $escape($t('dispatch_companies.related_contracts')) ?></p><h2><?= $escape($t('dispatch_companies.contract_count', ['count' => (string) count($contracts)])) ?></h2></div></div>
            <?php if ($contracts === []): ?><p class="muted-text"><?= $escape($t('dispatch_companies.no_contracts')) ?></p><?php else: ?>
                <div class="table-scroll"><table class="data-table"><thead><tr><th><?= $escape($t('dispatch_contracts.employee')) ?></th><th><?= $escape($t('table.start_date')) ?></th><th><?= $escape($t('table.end_date')) ?></th><th><?= $escape($t('table.expiration')) ?></th></tr></thead><tbody>
                <?php foreach ($contracts as $contract): ?><tr><td><a class="table-primary-link" href="/dispatch-contracts/<?= (int) $contract['id'] ?>"><?= $escape($contract['employee_name']) ?></a></td><td><?= $escape($contract['start_date']) ?></td><td><?= $escape($contract['end_date']) ?></td><td><?php $chipLabelKey = 'status.' . $contract['expiration_classification']; $chipTone = $contract['expiration_classification'] === 'expired' ? 'danger' : ($contract['expiration_classification'] === 'normal' ? 'success' : 'warning'); include __DIR__ . '/../partials/status-chip.php'; ?></td></tr><?php endforeach; ?></tbody></table></div>
            <?php endif; ?>
        </article>
    </div>
    <?php if (($company['status'] ?? null) === 'active'): ?><div class="detail-footer-actions"><a class="button button--danger" href="/dispatch-companies/<?= $id ?>/deactivate"><?= $escape($t('actions.deactivate_company')) ?></a></div><?php endif; ?>
</section>
