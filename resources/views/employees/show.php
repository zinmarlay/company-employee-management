<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$employee = is_array($data['employee'] ?? null) ? $data['employee'] : [];
$notice = $data['notice'] ?? null;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($employee['id'] ?? 0);
$fullName = ($employee['last_name'] ?? '') . ' ' . ($employee['first_name'] ?? '');
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'employees.directory';
    $data['pageDescriptionKey'] = 'employees.profile_description';
    $data['pageActions'] = [
        ['href' => '/employees', 'labelKey' => 'actions.back_to_employees', 'variant' => 'text'],
        ['href' => '/employees/' . $id . '/edit', 'labelKey' => 'actions.edit_employee', 'variant' => 'secondary'],
    ];
    include __DIR__ . '/../partials/page-header.php';
    ?>

    <?php if ($notice === 'deactivated'): ?>
        <div class="alert alert--success" role="status"><?= HtmlEscaper::escape($t('employees.deactivated_success')) ?></div>
    <?php elseif ($notice === 'already-inactive'): ?>
        <div class="alert alert--info" role="status"><?= HtmlEscaper::escape($t('employees.already_inactive_notice')) ?></div>
    <?php endif; ?>

    <div class="profile-summary card">
        <div class="profile-avatar" aria-hidden="true"><?= $escape(strtoupper(substr((string) ($employee['first_name'] ?? 'E'), 0, 1))) ?></div>
        <div class="profile-summary__identity">
            <h2><?= $escape($fullName) ?></h2>
            <p class="muted-text"><?= $escape($employee['position_title'] ?? $t('navigation.employees')) ?></p>
        </div>
        <div class="profile-summary__chips">
            <?php $chipLabelKey = ($employee['status'] ?? '') === 'active' ? 'status.active' : 'status.inactive'; $chipTone = ($employee['status'] ?? '') === 'active' ? 'success' : 'neutral'; include __DIR__ . '/../partials/status-chip.php'; ?>
            <?php $chipLabelKey = ($employee['employee_type'] ?? '') === 'dispatched' ? 'status.dispatched' : 'status.permanent'; $chipTone = ($employee['employee_type'] ?? '') === 'dispatched' ? 'primary' : 'info'; include __DIR__ . '/../partials/status-chip.php'; ?>
        </div>
    </div>

    <div class="detail-grid">
        <article class="card detail-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow"><?= $escape($t('employees.profile')) ?></p>
                    <h2><?= $escape($t('employees.basic_information')) ?></h2>
                </div>
            </div>
            <dl class="detail-list">
                <div><dt><?= $escape($t('form.employee_code')) ?></dt><dd class="code-text"><?= $escape($employee['employee_code'] ?? '') ?></dd></div>
                <div><dt><?= $escape($t('table.name')) ?></dt><dd><?= $escape($fullName) ?></dd></div>
                <div><dt><?= $escape($t('employees.name_kana')) ?></dt><dd><?= $escape(($employee['last_name_kana'] ?? '') . ' ' . ($employee['first_name_kana'] ?? '')) ?></dd></div>
                <div><dt><?= $escape($t('form.email')) ?></dt><dd><a class="inline-link" href="mailto:<?= $escape($employee['email'] ?? '') ?>"><?= $escape($employee['email'] ?? '') ?></a></dd></div>
                <div><dt><?= $escape($t('form.phone')) ?></dt><dd><?= $escape($employee['phone'] ?? $t('status.not_provided')) ?></dd></div>
            </dl>
        </article>

        <article class="card detail-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow"><?= $escape($t('employees.organization')) ?></p>
                    <h2><?= $escape($t('employees.current_assignment')) ?></h2>
                </div>
            </div>
            <dl class="detail-list">
                <div><dt><?= $escape($t('form.branch')) ?></dt><dd><?= $escape(($employee['branch_code'] ?? '') . ' ' . ($employee['branch_name'] ?? '')) ?></dd></div>
                <div><dt><?= $escape($t('form.department')) ?></dt><dd><?= $escape($employee['department_name'] === null ? $t('form.not_assigned') : (($employee['department_code'] ?? '') . ' ' . $employee['department_name'])) ?></dd></div>
                <div><dt><?= $escape($t('form.position_title')) ?></dt><dd><?= $escape($employee['position_title'] ?? $t('status.not_provided')) ?></dd></div>
            </dl>
        </article>

        <article class="card detail-card">
            <div class="card-header">
                <div>
                    <p class="eyebrow"><?= $escape($t('employees.employment')) ?></p>
                    <h2><?= $escape($t('employees.employment_details')) ?></h2>
                </div>
            </div>
            <dl class="detail-list">
                <div><dt><?= $escape($t('form.employee_type')) ?></dt><dd><?= $escape($t(($employee['employee_type'] ?? '') === 'dispatched' ? 'status.dispatched' : 'status.permanent')) ?></dd></div>
                <div><dt><?= $escape($t('form.hire_date')) ?></dt><dd><?= $escape($employee['hire_date'] ?? '') ?></dd></div>
                <div><dt><?= $escape($t('table.status')) ?></dt><dd><?= $escape($t(($employee['status'] ?? '') === 'active' ? 'status.active' : 'status.inactive')) ?></dd></div>
                <div><dt><?= $escape($t('employees.created')) ?></dt><dd><?= $escape($employee['created_at'] ?? '') ?></dd></div>
                <div><dt><?= $escape($t('employees.updated')) ?></dt><dd><?= $escape($employee['updated_at'] ?? '') ?></dd></div>
            </dl>
        </article>
    </div>

    <div class="detail-footer-actions">
        <?php if (($employee['status'] ?? null) === 'active'): ?>
            <a class="button button--danger" href="/employees/<?= $id ?>/deactivate"><?= $escape($t('actions.deactivate_employee')) ?></a>
        <?php endif; ?>
    </div>
</section>
