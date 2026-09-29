<?php
$id = (int) $data['employee']['id'];
$data['formAction'] = '/employees/' . $id . '/certifications';
$data['cancelHref'] = '/employees/' . $id . '/certifications';
$data['formTitleKey'] = 'employees.certification_create_title';
$data['formSubmitLabelKey'] = 'actions.add_certification';
$data['pageDescriptionKey'] = 'employees.certification_create_description';
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>

