<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$company = is_array($data['company'] ?? null) ? $data['company'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($company['id'] ?? 0);
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'dispatch_companies.directory';
    $data['pageDescriptionKey'] = 'dispatch_companies.deactivate_description';
    $data['pageActions'] = [['href' => '/dispatch-companies/' . $id, 'labelKey' => 'actions.back_to_company', 'variant' => 'text']];
    include __DIR__ . '/../partials/page-header.php';
    ?>
    <div class="card confirmation-card"><div class="confirmation-icon confirmation-icon--danger" aria-hidden="true">!</div><div><h2><?= $escape($t('dispatch_companies.deactivate_confirm', ['name' => (string) ($company['name'] ?? '')])) ?></h2><p class="muted-text"><?= $escape($t('dispatch_companies.deactivate_retained')) ?></p><p class="confirmation-meta"><span class="code-text"><?= $escape($company['code'] ?? '') ?></span></p></div></div>
    <?php if (($company['status'] ?? null) === 'inactive'): ?>
        <div class="alert alert--info" role="status"><?= $escape($t('dispatch_companies.already_inactive')) ?></div><div class="form-actions"><a class="button button--secondary" href="/dispatch-companies/<?= $id ?>"><?= $escape($t('actions.return_to_company')) ?></a></div>
    <?php else: ?>
        <form class="card form-card confirmation-form" method="post" action="/dispatch-companies/<?= $id ?>/deactivate"><div class="form-actions"><a class="button button--secondary" href="/dispatch-companies/<?= $id ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--danger" type="submit"><?= $escape($t('actions.confirm_deactivation')) ?></button></div></form>
    <?php endif; ?>
</section>
