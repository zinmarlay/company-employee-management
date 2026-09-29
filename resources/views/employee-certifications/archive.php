<?php
declare(strict_types=1);
use App\Http\View\HtmlEscaper;
$t = $data['t'];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$employeeId = (int) $data['employee']['id'];
$certificationId = (int) $data['certification']['id'];
?>
<section class="page-section"><?php $data['pageEyebrowKey'] = 'employees.portfolio'; $data['pageDescriptionKey'] = 'employees.certification_archive_description'; $data['pageActions'] = [['href' => '/employees/' . $employeeId . '/certifications/' . $certificationId, 'labelKey' => 'actions.back_to_certification', 'variant' => 'text']]; include __DIR__ . '/../partials/page-header.php'; ?><div class="card confirmation-card"><div class="confirmation-icon confirmation-icon--danger" aria-hidden="true">!</div><div><h2><?= $escape($t('employees.certification_archive_confirm', ['name' => (string) ($data['certification']['certification_name'] ?? '')])) ?></h2><p class="muted-text"><?= $escape($t('employees.portfolio_archive_retained')) ?></p></div></div><form class="card form-card confirmation-form" method="post" action="/employees/<?= $employeeId ?>/certifications/<?= $certificationId ?>/archive"><div class="form-actions"><a class="button button--secondary" href="/employees/<?= $employeeId ?>/certifications/<?= $certificationId ?>"><?= $escape($t('actions.cancel')) ?></a><button class="button button--danger" type="submit"><?= $escape($t('actions.confirm_archive')) ?></button></div></form></section>

