<?php

declare(strict_types=1);

$id = (int) ($data['branchId'] ?? 0);
$data['pageEyebrowKey'] = 'branches.directory';
$data['pageDescriptionKey'] = 'branches.edit_description';
$data['pageActions'] = [['href' => '/branches/' . $id, 'labelKey' => 'actions.back_to_branch', 'variant' => 'text']];
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>
