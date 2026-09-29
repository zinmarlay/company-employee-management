<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$employee = is_array($data['employee'] ?? null) ? $data['employee'] : [];
$active = is_array($data['activeRecords'] ?? null) ? $data['activeRecords'] : [];
$archived = is_array($data['archivedRecords'] ?? null) ? $data['archivedRecords'] : [];
$id = (int) ($employee['id'] ?? 0);
?>
<section class="page-section"><?php $data['pageEyebrowKey'] = 'employees.portfolio'; $data['pageDescriptionKey'] = 'employees.projects_description'; $data['pageActions'] = [['href' => '/employees/' . $id, 'labelKey' => 'actions.back_to_employee', 'variant' => 'text']]; include __DIR__ . '/../partials/page-header.php'; ?>
<div class="card detail-card"><div class="card-header"><div><p class="eyebrow"><?= $escape(($employee['last_name'] ?? '') . ' ' . ($employee['first_name'] ?? '')) ?></p><h2><?= $escape($t('employees.projects_heading')) ?></h2></div><?php if (($employee['status'] ?? '') === 'active'): ?><a class="button button--primary" href="/employees/<?= $id ?>/projects/create"><?= $escape($t('actions.add_project')) ?></a><?php endif; ?></div>
<?php if (($data['notice'] ?? null) === 'created'): ?><div class="alert alert--success" role="status"><?= $escape($t('employees.portfolio_created')) ?></div><?php elseif (($data['notice'] ?? null) === 'updated'): ?><div class="alert alert--success" role="status"><?= $escape($t('employees.portfolio_updated')) ?></div><?php elseif (($data['notice'] ?? null) === 'archived'): ?><div class="alert alert--success" role="status"><?= $escape($t('employees.portfolio_archived')) ?></div><?php elseif (($data['notice'] ?? null) === 'already-archived'): ?><div class="alert alert--info" role="status"><?= $escape($t('employees.portfolio_already_archived')) ?></div><?php elseif (($data['notice'] ?? null) === 'inactive-edit'): ?><div class="alert alert--info" role="status"><?= $escape($t('employees.portfolio_inactive_notice')) ?></div><?php endif; ?>
<?php if (($employee['status'] ?? '') !== 'active'): ?><div class="alert alert--info" role="status"><?= $escape($t('employees.portfolio_read_only')) ?></div><?php endif; ?>
<?php if ($active === []): ?><p class="muted-text"><?= $escape($t('employees.no_projects')) ?></p><?php else: ?><div class="table-scroll"><table class="data-table"><thead><tr><th><?= $escape($t('portfolio.project_name')) ?></th><th><?= $escape($t('portfolio.role')) ?></th><th><?= $escape($t('portfolio.period')) ?></th><th><?= $escape($t('table.status')) ?></th><th><span class="visually-hidden"><?= $escape($t('table.actions')) ?></span></th></tr></thead><tbody>
<?php foreach ($active as $project): ?><tr><td><a class="table-primary-link" href="/employees/<?= $id ?>/projects/<?= (int) $project['id'] ?>"><?= $escape($project['project_name']) ?></a></td><td><?= $escape($project['role']) ?></td><td><?= $escape($project['start_date']) ?> → <?= $escape($project['end_date'] ?? $t('portfolio.ongoing')) ?></td><td><?= $escape($t('portfolio.status_active')) ?></td><td><div class="table-actions"><?php if (($employee['status'] ?? '') === 'active'): ?><a class="button button--text button--small" href="/employees/<?= $id ?>/projects/<?= (int) $project['id'] ?>/edit"><?= $escape($t('actions.edit')) ?></a><a class="button button--text button--small button--danger-text" href="/employees/<?= $id ?>/projects/<?= (int) $project['id'] ?>/archive"><?= $escape($t('actions.archive')) ?></a><?php endif; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div>
<div class="card detail-card"><div class="card-header"><div><p class="eyebrow"><?= $escape($t('employees.portfolio_history')) ?></p><h2><?= $escape($t('employees.archived_projects')) ?></h2></div></div><?php if ($archived === []): ?><p class="muted-text"><?= $escape($t('employees.no_archived_projects')) ?></p><?php else: ?><div class="table-scroll"><table class="data-table"><thead><tr><th><?= $escape($t('portfolio.project_name')) ?></th><th><?= $escape($t('portfolio.period')) ?></th><th><?= $escape($t('portfolio.archived_at')) ?></th></tr></thead><tbody><?php foreach ($archived as $project): ?><tr><td><a class="table-primary-link" href="/employees/<?= $id ?>/projects/<?= (int) $project['id'] ?>"><?= $escape($project['project_name']) ?></a></td><td><?= $escape($project['start_date']) ?> → <?= $escape($project['end_date'] ?? $t('portfolio.ongoing')) ?></td><td><?= $escape($project['archived_at'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
</section>

