<?php
$data['formAction'] = '/employees/' . (int) $data['employee']['id'] . '/skills';
$data['cancelHref'] = '/employees/' . (int) $data['employee']['id'] . '/skills';
$data['formTitleKey'] = 'employees.skills_create_title';
$data['formSubmitLabelKey'] = 'actions.add_skill';
$data['pageDescriptionKey'] = 'employees.skills_create_description';
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>

