<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$employee = is_array($data['employee'] ?? null) ? $data['employee'] : [];
$active = is_array($data['activeRecords'] ?? null) ? $data['activeRecords'] : [];
$archived = is_array($data['archivedRecords'] ?? null) ? $data['archivedRecords'] : [];
$employeeId = (int) ($employee['id'] ?? 0);
$employeeName = trim((string) ($employee['last_name'] ?? '') . ' ' . (string) ($employee['first_name'] ?? ''));
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'employees.portfolio';
    $data['pageDescriptionKey'] = 'employees.skills_description';
    $data['pageActions'] = [['href' => '/employees/' . $employeeId, 'labelKey' => 'actions.back_to_employee', 'variant' => 'text']];
    include __DIR__ . '/../partials/page-header.php';
    ?>
    <div class="card detail-card">
        <div class="card-header"><div><p class="eyebrow"><?= $escape($employeeName) ?> · <?= $escape((string) ($employee['employee_code'] ?? '')) ?></p><h2><?= $escape($t('employees.skills_heading')) ?></h2></div><?php if (($employee['status'] ?? '') === 'active'): ?><a class="button button--primary" href="/employees/<?= $employeeId ?>/skills/create"><?= $escape($t('actions.add_skill')) ?></a><?php endif; ?></div>
        <?php if (($data['notice'] ?? null) === 'created'): ?><div class="alert alert--success" role="status"><?= $escape($t('employees.portfolio_created')) ?></div><?php elseif (($data['notice'] ?? null) === 'restored'): ?><div class="alert alert--success" role="status"><?= $escape($t('employees.portfolio_restored')) ?></div><?php elseif (($data['notice'] ?? null) === 'updated'): ?><div class="alert alert--success" role="status"><?= $escape($t('employees.portfolio_updated')) ?></div><?php elseif (($data['notice'] ?? null) === 'archived'): ?><div class="alert alert--success" role="status"><?= $escape($t('employees.portfolio_archived')) ?></div><?php elseif (($data['notice'] ?? null) === 'already-archived'): ?><div class="alert alert--info" role="status"><?= $escape($t('employees.portfolio_already_archived')) ?></div><?php elseif (($data['notice'] ?? null) === 'inactive-edit'): ?><div class="alert alert--info" role="status"><?= $escape($t('employees.portfolio_inactive_notice')) ?></div><?php endif; ?>
        <?php if (($employee['status'] ?? '') !== 'active'): ?><div class="alert alert--info" role="status"><?= $escape($t('employees.portfolio_read_only')) ?></div><?php endif; ?>
        <?php if ($active === []): ?>
            <p class="muted-text"><?= $escape($t('employees.no_skills')) ?></p>
        <?php else: ?>
            <div class="table-scroll"><table class="data-table"><thead><tr><th><?= $escape($t('portfolio.skill')) ?></th><th><?= $escape($t('portfolio.proficiency')) ?></th><th><?= $escape($t('portfolio.years_experience')) ?></th><th><span class="visually-hidden"><?= $escape($t('table.actions')) ?></span></th></tr></thead><tbody>
            <?php foreach ($active as $record): ?><tr><td data-label="<?= $escape($t('portfolio.skill')) ?>"><?= $escape($record['skill_name'] ?? '') ?></td><td data-label="<?= $escape($t('portfolio.proficiency')) ?>"><?= $escape($t('portfolio.proficiency_' . ($record['proficiency'] ?? '')) ) ?></td><td data-label="<?= $escape($t('portfolio.years_experience')) ?>"><?= $record['years_experience'] === null ? $escape($t('status.not_provided')) : $escape((string) $record['years_experience']) ?></td><td><div class="table-actions"><?php if (($employee['status'] ?? '') === 'active'): ?><a class="button button--text button--small" href="/employees/<?= $employeeId ?>/skills/<?= (int) $record['id'] ?>/edit"><?= $escape($t('actions.edit')) ?></a><a class="button button--text button--small button--danger-text" href="/employees/<?= $employeeId ?>/skills/<?= (int) $record['id'] ?>/archive"><?= $escape($t('actions.archive')) ?></a><?php endif; ?></div></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
    <div class="card detail-card">
        <div class="card-header"><div><p class="eyebrow"><?= $escape($t('employees.portfolio_history')) ?></p><h2><?= $escape($t('employees.archived_skills')) ?></h2></div></div>
        <?php if ($archived === []): ?><p class="muted-text"><?= $escape($t('employees.no_archived_skills')) ?></p><?php else: ?><div class="table-scroll"><table class="data-table"><thead><tr><th><?= $escape($t('portfolio.skill')) ?></th><th><?= $escape($t('portfolio.proficiency')) ?></th><th><?= $escape($t('portfolio.archived_at')) ?></th></tr></thead><tbody><?php foreach ($archived as $record): ?><tr><td><?= $escape($record['skill_name'] ?? '') ?></td><td><?= $escape($t('portfolio.proficiency_' . ($record['proficiency'] ?? '')) ) ?></td><td><?= $escape((string) ($record['archived_at'] ?? '')) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>
</section>

