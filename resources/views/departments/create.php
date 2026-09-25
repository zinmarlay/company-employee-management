<?php

declare(strict_types=1);

$data['pageEyebrowKey'] = 'departments.directory';
$data['pageDescriptionKey'] = 'departments.create_description';
$data['pageActions'] = [['href' => '/departments', 'labelKey' => 'actions.back_to_departments', 'variant' => 'text']];
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>
