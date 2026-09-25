<?php

declare(strict_types=1);

$id = (int) ($data['contractId'] ?? 0);
$data['formAction'] = '/dispatch-contracts/' . $id;
$data['formSubmitLabelKey'] = 'actions.save_changes';
$data['cancelHref'] = '/dispatch-contracts/' . $id;
$data['pageEyebrowKey'] = 'dispatch_contracts.directory';
$data['pageDescriptionKey'] = 'dispatch_contracts.edit_description';
$data['pageActions'] = [['href' => '/dispatch-contracts/' . $id, 'labelKey' => 'actions.back_to_contract', 'variant' => 'text']];
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>
