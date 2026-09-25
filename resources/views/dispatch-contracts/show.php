<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$contract = is_array($data['contract'] ?? null) ? $data['contract'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($contract['id'] ?? 0);
$classification = (string) ($contract['expiration_classification'] ?? 'normal');
$tone = $classification === 'expired' ? 'danger' : ($classification === 'normal' ? 'success' : 'warning');
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'dispatch_contracts.directory';
    $data['pageDescriptionKey'] = 'dispatch_contracts.detail_description';
    $data['pageActions'] = [
        ['href' => '/employees/' . (int) $contract['employee_id'], 'labelKey' => 'actions.back_to_employee', 'variant' => 'text'],
        ['href' => '/dispatch-contracts/' . $id . '/edit', 'labelKey' => 'actions.edit_contract', 'variant' => 'secondary'],
        ['href' => '/dispatch-contracts/' . $id . '/renew', 'labelKey' => 'actions.renew_contract', 'variant' => 'primary'],
    ];
    include __DIR__ . '/../partials/page-header.php';
    ?>
    <div class="detail-grid">
        <article class="card detail-card"><div class="card-header"><div><p class="eyebrow"><?= $escape($t('dispatch_contracts.contract_information')) ?></p><h2>#<?= $id ?></h2></div><?php $chipLabelKey = 'status.' . $classification; $chipTone = $tone; include __DIR__ . '/../partials/status-chip.php'; ?></div><dl class="detail-list">
            <div><dt><?= $escape($t('dispatch_contracts.employee')) ?></dt><dd><a class="inline-link" href="/employees/<?= (int) $contract['employee_id'] ?>"><?= $escape($contract['employee_code'] . ' — ' . $contract['employee_name']) ?></a></dd></div>
            <div><dt><?= $escape($t('dispatch_contracts.dispatch_company')) ?></dt><dd><a class="inline-link" href="/dispatch-companies/<?= (int) $contract['dispatch_company_id'] ?>"><?= $escape($contract['dispatch_company_code'] . ' — ' . $contract['dispatch_company_name']) ?></a></dd></div>
            <div><dt><?= $escape($t('dispatch_contracts.period')) ?></dt><dd><?= $escape($contract['start_date']) ?> → <?= $escape($contract['end_date']) ?></dd></div>
            <div><dt><?= $escape($t('dispatch_contracts.expiration')) ?></dt><dd><?= $escape($t('status.' . $classification)) ?></dd></div>
            <div><dt><?= $escape($t('dispatch_contracts.created')) ?></dt><dd><?= $escape($contract['created_at']) ?></dd></div>
            <div><dt><?= $escape($t('dispatch_contracts.updated')) ?></dt><dd><?= $escape($contract['updated_at']) ?></dd></div>
        </dl></article>
    </div>
</section>
