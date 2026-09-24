<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$employee = is_array($data['employee'] ?? null) ? $data['employee'] : [];
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($employee['id'] ?? 0);
$name = ($employee['last_name'] ?? '') . ' ' . ($employee['first_name'] ?? '');
?>
<section>
    <p><a href="/employees/<?= $id ?>">Back to employee</a></p>
    <h1>Deactivate employee</h1>
    <p>
        Deactivate <?= $escape($name) ?> (<?= $escape($employee['employee_code'] ?? '') ?>)?
        The employee record and historical data will be retained.
    </p>
    <?php if (($employee['status'] ?? null) === 'inactive'): ?>
        <p>This employee is already inactive.</p>
        <p><a href="/employees/<?= $id ?>">Return to employee</a></p>
    <?php else: ?>
        <form method="post" action="/employees/<?= $id ?>/deactivate">
            <button type="submit">Confirm deactivation</button>
            <a href="/employees/<?= $id ?>">Cancel</a>
        </form>
    <?php endif; ?>
</section>
