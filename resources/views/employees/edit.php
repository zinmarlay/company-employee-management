<?php

declare(strict_types=1);

$id = (int) ($data['employeeId'] ?? 0);
$data['formAction'] = '/employees/' . $id;
$data['formSubmitLabel'] = 'Save changes';
?>
<section>
    <p><a href="/employees/<?= $id ?>">Back to employee</a></p>
    <h1>Edit employee</h1>
    <?php include __DIR__ . '/_form.php'; ?>
</section>
