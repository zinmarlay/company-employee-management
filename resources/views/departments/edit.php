<?php

declare(strict_types=1);

$id = (int) ($data['departmentId'] ?? 0);
$data['pageEyebrowKey'] = 'departments.directory';
$data['pageDescriptionKey'] = 'departments.edit_description';
$data['pageActions'] = [['href' => '/departments/' . $id, 'labelKey' => 'actions.back_to_department', 'variant' => 'text']];
?>
<section class="page-section"><?php include __DIR__ . '/../partials/page-header.php'; ?><?php include __DIR__ . '/_form.php'; ?></section>
