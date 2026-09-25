<?php

declare(strict_types=1);

$id = (int) ($data['companyId'] ?? 0);
$data['formAction'] = '/dispatch-companies/' . $id;
$data['formSubmitLabelKey'] = 'actions.save_changes';
$data['cancelHref'] = '/dispatch-companies/' . $id;
$data['pageEyebrowKey'] = 'dispatch_companies.directory';
$data['pageDescriptionKey'] = 'dispatch_companies.edit_description';
$data['pageActions'] = [
    ['href' => '/dispatch-companies/' . $id, 'labelKey' => 'actions.back_to_company', 'variant' => 'text'],
];
?>
<section class="page-section">
    <?php include __DIR__ . '/../partials/page-header.php'; ?>
    <?php include __DIR__ . '/_form.php'; ?>
</section>
