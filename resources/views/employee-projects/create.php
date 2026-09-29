<?php
$id = (int) $data['employee']['id'];
$data['formAction'] = '/employees/' . $id . '/projects';
$data['cancelHref'] = '/employees/' . $id . '/projects';
$data['formTitleKey'] = 'employees.project_create_title';
$data['formSubmitLabelKey'] = 'actions.add_project';
$data['pageDescriptionKey'] = 'employees.project_create_description';
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>

