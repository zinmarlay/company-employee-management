<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$employee = is_array($data['employee'] ?? null) ? $data['employee'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($employee['id'] ?? 0);
$name = ($employee['last_name'] ?? '') . ' ' . ($employee['first_name'] ?? '');
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'employees.directory';
    $data['pageDescriptionKey'] = 'employees.deactivate_description';
    $data['pageActions'] = [
        ['href' => '/employees/' . $id, 'labelKey' => 'actions.back_to_employee', 'variant' => 'text'],
    ];
    include __DIR__ . '/../partials/page-header.php';
    ?>
    <div class="card confirmation-card">
        <div class="confirmation-icon confirmation-icon--danger" aria-hidden="true">!</div>
        <div>
            <h2><?= $escape($t('employees.deactivate_confirm', ['name' => $name])) ?></h2>
            <p class="muted-text"><?= $escape($t('employees.deactivate_retained')) ?></p>
            <p class="confirmation-meta"><span class="code-text"><?= $escape($employee['employee_code'] ?? '') ?></span></p>
        </div>
    </div>
    <?php if (($employee['status'] ?? null) === 'inactive'): ?>
        <div class="alert alert--info" role="status"><?= $escape($t('employees.already_inactive')) ?></div>
        <div class="form-actions">
            <a class="button button--secondary" href="/employees/<?= $id ?>"><?= $escape($t('actions.return_to_employee')) ?></a>
        </div>
    <?php else: ?>
        <form class="card form-card confirmation-form" method="post" action="/employees/<?= $id ?>/deactivate">
            <div class="form-actions">
                <a class="button button--secondary" href="/employees/<?= $id ?>"><?= $escape($t('actions.cancel')) ?></a>
                <button class="button button--danger" type="submit"><?= $escape($t('actions.confirm_deactivation')) ?></button>
            </div>
        </form>
    <?php endif; ?>
</section>
