<?php
declare(strict_types=1);
use App\Http\View\HtmlEscaper;
$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$employeeId = (int) $data['employee']['id'];
$project = $data['project'];
$projectId = (int) $project['id'];
?>
<section class="page-section"><?php $data['pageEyebrowKey'] = 'employees.portfolio'; $data['pageDescriptionKey'] = 'employees.project_detail_description'; $data['pageActions'] = [['href' => '/employees/' . $employeeId . '/projects', 'labelKey' => 'actions.back_to_projects', 'variant' => 'text']]; include __DIR__ . '/../partials/page-header.php'; ?>
<article class="card detail-card"><div class="card-header"><div><p class="eyebrow"><?= $escape(($data['employee']['last_name'] ?? '') . ' ' . ($data['employee']['first_name'] ?? '')) ?></p><h2><?= $escape($project['project_name'] ?? '') ?></h2></div><?php $chipLabelKey = ($project['status'] ?? '') === 'active' ? 'portfolio.status_active' : 'portfolio.status_archived'; $chipTone = ($project['status'] ?? '') === 'active' ? 'success' : 'neutral'; include __DIR__ . '/../partials/status-chip.php'; ?></div><dl class="detail-list"><div><dt><?= $escape($t('portfolio.role')) ?></dt><dd><?= $escape($project['role'] ?? '') ?></dd></div><div><dt><?= $escape($t('portfolio.period')) ?></dt><dd><?= $escape($project['start_date'] ?? '') ?> → <?= $escape($project['end_date'] ?? $t('portfolio.ongoing')) ?></dd></div><div><dt><?= $escape($t('portfolio.description')) ?></dt><dd><?= nl2br($escape($project['description'] ?? $t('status.not_provided'))) ?></dd></div><div><dt><?= $escape($t('portfolio.responsibilities')) ?></dt><dd><?= nl2br($escape($project['responsibilities'] ?? $t('status.not_provided'))) ?></dd></div><div><dt><?= $escape($t('portfolio.technologies')) ?></dt><dd><?= nl2br($escape($project['technologies'] ?? $t('status.not_provided'))) ?></dd></div></dl><div class="detail-footer-actions"><?php if (($data['employee']['status'] ?? '') === 'active' && ($project['status'] ?? '') === 'active'): ?><a class="button button--secondary" href="/employees/<?= $employeeId ?>/projects/<?= $projectId ?>/edit"><?= $escape($t('actions.edit')) ?></a><a class="button button--danger" href="/employees/<?= $employeeId ?>/projects/<?= $projectId ?>/archive"><?= $escape($t('actions.archive')) ?></a><?php endif; ?></div></article></section>

