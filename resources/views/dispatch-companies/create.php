<?php

declare(strict_types=1);

$data['formAction'] = '/dispatch-companies';
$data['formSubmitLabelKey'] = 'actions.create_company';
$data['cancelHref'] = '/dispatch-companies';
$data['pageEyebrowKey'] = 'dispatch_companies.directory';
$data['pageDescriptionKey'] = 'dispatch_companies.create_description';
$data['pageActions'] = [
    ['href' => '/dispatch-companies', 'labelKey' => 'actions.back_to_companies', 'variant' => 'text'],
];
?>
<section class="page-section">
    <?php include __DIR__ . '/../partials/page-header.php'; ?>
    <?php include __DIR__ . '/_form.php'; ?>
</section>
