<?php

declare(strict_types=1);

$data['formAction'] = '/dispatch-contracts';
$data['formSubmitLabelKey'] = 'actions.create_contract';
$data['cancelHref'] = '/employees';
$data['pageEyebrowKey'] = 'dispatch_contracts.directory';
$data['pageDescriptionKey'] = 'dispatch_contracts.create_description';
$data['pageActions'] = [['href' => '/employees', 'labelKey' => 'actions.back_to_employees', 'variant' => 'text']];
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>
