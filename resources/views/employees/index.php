<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$employees = is_array($data['employees'] ?? null) ? $data['employees'] : [];
$notice = $data['notice'] ?? null;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'employees.directory';
    $data['pageDescriptionKey'] = 'employees.description';
    $data['pageActions'] = [
        ['href' => '/employees/create', 'labelKey' => 'actions.create_employee', 'variant' => 'primary'],
    ];
    include __DIR__ . '/../partials/page-header.php';
    ?>

    <?php if ($notice === 'deactivated'): ?>
        <div class="alert alert--success" role="status"><?= HtmlEscaper::escape($t('employees.deactivated_success')) ?></div>
    <?php elseif ($notice === 'already-inactive'): ?>
        <div class="alert alert--info" role="status"><?= HtmlEscaper::escape($t('employees.already_inactive_notice')) ?></div>
    <?php endif; ?>

    <?php if ($employees === []): ?>
        <?php
        $data['emptyTitleKey'] = 'employees.no_employees';
        $data['emptyMessageKey'] = 'employees.empty_description';
        $data['emptyActionHref'] = '/employees/create';
        $data['emptyActionLabelKey'] = 'actions.create_employee';
        include __DIR__ . '/../partials/empty-state.php';
        ?>
    <?php else: ?>
        <div class="card table-card">
            <div class="table-card__header">
                <div>
                    <h2><?= HtmlEscaper::escape($t('employees.directory_heading')) ?></h2>
                    <p class="muted-text"><?= HtmlEscaper::escape($t('employees.directory_description')) ?></p>
                </div>
                <span class="record-count"><?= HtmlEscaper::escape($t('employees.record_count', ['count' => (string) count($employees)])) ?></span>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th scope="col"><?= HtmlEscaper::escape($t('table.code')) ?></th>
                        <th scope="col"><?= HtmlEscaper::escape($t('table.name')) ?></th>
                        <th scope="col"><?= HtmlEscaper::escape($t('table.branch')) ?></th>
                        <th scope="col"><?= HtmlEscaper::escape($t('table.department')) ?></th>
                        <th scope="col"><?= HtmlEscaper::escape($t('table.position')) ?></th>
                        <th scope="col"><?= HtmlEscaper::escape($t('table.type')) ?></th>
                        <th scope="col"><?= HtmlEscaper::escape($t('table.status')) ?></th>
                        <th scope="col"><span class="visually-hidden"><?= HtmlEscaper::escape($t('table.actions')) ?></span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <?php
                        $id = (int) $employee['id'];
                        $typeLabelKey = $employee['employee_type'] === 'dispatched' ? 'status.dispatched' : 'status.permanent';
                        $typeTone = $employee['employee_type'] === 'dispatched' ? 'primary' : 'info';
                        $statusLabelKey = $employee['status'] === 'active' ? 'status.active' : 'status.inactive';
                        $statusTone = $employee['status'] === 'active' ? 'success' : 'neutral';
                        ?>
                        <tr>
                            <td data-label="<?= $escape($t('table.code')) ?>"><span class="code-text"><?= $escape($employee['employee_code']) ?></span></td>
                            <td data-label="<?= $escape($t('table.name')) ?>"><a class="table-primary-link" href="/employees/<?= $id ?>"><?= $escape($employee['last_name'] . ' ' . $employee['first_name']) ?></a></td>
                            <td data-label="<?= $escape($t('table.branch')) ?>"><?= $escape($employee['branch_name']) ?></td>
                            <td data-label="<?= $escape($t('table.department')) ?>"><?= $escape($employee['department_name'] ?? $t('form.not_assigned')) ?></td>
                            <td data-label="<?= $escape($t('table.position')) ?>"><?= $escape($employee['position_title'] ?? '—') ?></td>
                            <td data-label="<?= $escape($t('table.type')) ?>"><?php $chipLabelKey = $typeLabelKey; $chipTone = $typeTone; include __DIR__ . '/../partials/status-chip.php'; ?></td>
                            <td data-label="<?= $escape($t('table.status')) ?>"><?php $chipLabelKey = $statusLabelKey; $chipTone = $statusTone; include __DIR__ . '/../partials/status-chip.php'; ?></td>
                            <td data-label="<?= $escape($t('table.actions')) ?>">
                                <div class="table-actions">
                                    <a class="button button--text button--small" href="/employees/<?= $id ?>"><?= $escape($t('actions.view')) ?></a>
                                    <a class="button button--text button--small" href="/employees/<?= $id ?>/edit"><?= $escape($t('actions.edit')) ?></a>
                                    <?php if ($employee['status'] === 'active'): ?>
                                        <a class="button button--text button--small button--danger-text" href="/employees/<?= $id ?>/deactivate"><?= $escape($t('actions.deactivate')) ?></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
