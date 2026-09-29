<?php
declare(strict_types=1);
use App\Http\View\HtmlEscaper;
$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$employeeId = (int) $data['employee']['id'];
$assignmentId = (int) $data['assignment']['id'];
?>
<section class="page-section"><?php $data['pageEyebrowKey'] = 'employees.portfolio'; $data['pageDescriptionKey'] = 'employees.skills_archive_description'; $data['pageActions'] = [['href' => '/employees/' . $employeeId . '/skills', 'labelKey' => 'actions.back_to_skills', 'variant' => 'text']]; include __DIR__ . '/../partials/page-header.php'; ?>
<div class="card confirmation-card"><div class="confirmation-icon confirmation-icon--danger" aria-hidden="true">!</div><div><h2><?= $escape($t('employees.skills_archive_confirm', ['name' => (string) ($data['assignment']['skill_name'] ?? '')])) ?></h2><p class="muted-text"><?= $escape($t('employees.portfolio_archive_retained')) ?></p></div></div>
<form class="card form-card confirmation-form" method="post" action="/employees/<?= $employeeId ?>/skills/<?= $assignmentId ?>/archive"><div class="form-actions"><a class="button button--secondary" href="/employees/<?= $employeeId ?>/skills"><?= $escape($t('actions.cancel')) ?></a><button class="button button--danger" type="submit"><?= $escape($t('actions.confirm_archive')) ?></button></div></form></section>

