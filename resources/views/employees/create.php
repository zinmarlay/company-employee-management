<?php

declare(strict_types=1);

$data['formAction'] = '/employees';
$data['formSubmitLabel'] = 'Create employee';
?>
<section>
    <p><a href="/employees">Back to employees</a></p>
    <h1>Create employee</h1>
    <?php include __DIR__ . '/_form.php'; ?>
</section>
