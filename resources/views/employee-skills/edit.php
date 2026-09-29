<?php
$id = (int) $data['employee']['id'];
$assignmentId = (int) ($data['assignment']['id'] ?? 0);
$data['formAction'] = '/employees/' . $id . '/skills/' . $assignmentId;
$data['cancelHref'] = '/employees/' . $id . '/skills';
$data['formTitleKey'] = 'employees.skills_edit_title';
$data['formSubmitLabelKey'] = 'actions.save_changes';
$data['pageDescriptionKey'] = 'employees.skills_edit_description';
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>

