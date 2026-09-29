<?php
$id = (int) $data['employee']['id'];
$certificationId = (int) $data['certification']['id'];
$data['formAction'] = '/employees/' . $id . '/certifications/' . $certificationId;
$data['cancelHref'] = '/employees/' . $id . '/certifications/' . $certificationId;
$data['formTitleKey'] = 'employees.certification_edit_title';
$data['formSubmitLabelKey'] = 'actions.save_changes';
$data['pageDescriptionKey'] = 'employees.certification_edit_description';
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>

