<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$branch = is_array($data['branch'] ?? null) ? $data['branch'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($branch['id'] ?? 0);
?>
<section class="page-section">
    <?php $data['pageEyebrowKey'] = 'branches.directory'; $data['pageDescriptionKey'] = 'branches.deactivate_description'; $data['pageActions'] = [['href' => '/branches/' . $id, 'labelKey' => 'actions.back_to_branch', 'variant' => 'text']]; include __DIR__ . '/../partials/page-header.php'; ?>
    <div class="card confirmation-card"><div class="confirmation-icon confirmation-icon--danger" aria-hidden="true">!</div><div><h2><?= $escape($t('branches.deactivate_confirm', ['name' => (string) ($branch['name'] ?? '')])) ?></h2><p class="muted-text"><?= $escape($t('branches.deactivate_retained')) ?></p><p class="confirmation-meta"><span class="code-text"><?= $escape(($branch['company_code'] ?? '') . ' ' . ($branch['code'] ?? '')) ?></span> · <?= $escape($t('branches.department_count', ['count' => (string) ($branch['department_count'] ?? 0)])) ?> · <?= $escape($t('branches.employee_count', ['count' => (string) ($branch['employee_count'] ?? 0)])) ?></p></div></div>
    <?php if (($branch['status'] ?? null) === 'inactive'): ?><div class="alert alert--info" role="status"><?= $escape($t('branches.already_inactive')) ?></div><div class="form-actions"><a class="button button--secondary" href="/branches/<?= $id ?>"><?= $escape($t('actions.return_to_branch')) ?></a></div><?php else: ?><form class="card form-card confirmation-form" method="post" action="/branches/<?= $id ?>/deactivate"><div class="form-actions"><a class="button button--secondary" href="/branches/<?= $id ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--danger" type="submit"><?= $escape($t('actions.confirm_deactivation')) ?></button></div></form><?php endif; ?>
</section>
