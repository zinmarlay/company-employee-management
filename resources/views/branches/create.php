<?php

declare(strict_types=1);

$data['pageEyebrowKey'] = 'branches.directory';
$data['pageDescriptionKey'] = 'branches.create_description';
$data['pageActions'] = [['href' => '/branches', 'labelKey' => 'actions.back_to_branches', 'variant' => 'text']];
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>
