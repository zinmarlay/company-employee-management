<?php

declare(strict_types=1);

$data['formAction'] = '/employees';
$data['formSubmitLabelKey'] = 'actions.create_employee';
$data['pageEyebrowKey'] = 'employees.directory';
$data['pageDescriptionKey'] = 'employees.create_description';
$data['pageActions'] = [
    ['href' => '/employees', 'labelKey' => 'actions.back_to_employees', 'variant' => 'text'],
];
?>
<section class="page-section">
    <?php include __DIR__ . '/../partials/page-header.php'; ?>
    <?php include __DIR__ . '/_form.php'; ?>
</section>
