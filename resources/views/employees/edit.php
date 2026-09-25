<?php

declare(strict_types=1);

$id = (int) ($data['employeeId'] ?? 0);
$data['formAction'] = '/employees/' . $id;
$data['formSubmitLabelKey'] = 'actions.save_changes';
$data['cancelHref'] = '/employees/' . $id;
$data['pageEyebrowKey'] = 'employees.directory';
$data['pageDescriptionKey'] = 'employees.edit_description';
$data['pageActions'] = [
    ['href' => '/employees/' . $id, 'labelKey' => 'actions.back_to_employee', 'variant' => 'text'],
];
?>
<section class="page-section">
    <?php include __DIR__ . '/../partials/page-header.php'; ?>
    <?php include __DIR__ . '/_form.php'; ?>
</section>
