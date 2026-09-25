<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$department = is_array($data['department'] ?? null) ? $data['department'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($department['id'] ?? 0);
?>
<section class="page-section">
    <?php $data['pageEyebrowKey'] = 'departments.directory'; $data['pageDescriptionKey'] = 'departments.deactivate_description'; $data['pageActions'] = [['href' => '/departments/' . $id, 'labelKey' => 'actions.back_to_department', 'variant' => 'text']]; include __DIR__ . '/../partials/page-header.php'; ?>
    <div class="card confirmation-card"><div class="confirmation-icon confirmation-icon--danger" aria-hidden="true">!</div><div><h2><?= $escape($t('departments.deactivate_confirm', ['name' => (string) ($department['name'] ?? '')])) ?></h2><p class="muted-text"><?= $escape($t('departments.deactivate_retained')) ?></p><p class="confirmation-meta"><span class="code-text"><?= $escape(($department['branch_code'] ?? '') . ' ' . ($department['code'] ?? '')) ?></span> · <?= $escape($t('departments.employee_count', ['count' => (string) ($department['employee_count'] ?? 0)])) ?></p></div></div>
    <?php if (($department['status'] ?? null) === 'inactive'): ?><div class="alert alert--info" role="status"><?= $escape($t('departments.already_inactive')) ?></div><div class="form-actions"><a class="button button--secondary" href="/departments/<?= $id ?>"><?= $escape($t('actions.return_to_department')) ?></a></div><?php else: ?><form class="card form-card confirmation-form" method="post" action="/departments/<?= $id ?>/deactivate"><div class="form-actions"><a class="button button--secondary" href="/departments/<?= $id ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--danger" type="submit"><?= $escape($t('actions.confirm_deactivation')) ?></button></div></form><?php endif; ?>
</section>
