<?php
$id = (int) $data['employee']['id'];
$projectId = (int) $data['project']['id'];
$data['formAction'] = '/employees/' . $id . '/projects/' . $projectId;
$data['cancelHref'] = '/employees/' . $id . '/projects/' . $projectId;
$data['formTitleKey'] = 'employees.project_edit_title';
$data['formSubmitLabelKey'] = 'actions.save_changes';
$data['pageDescriptionKey'] = 'employees.project_edit_description';
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>

