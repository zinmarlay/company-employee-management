<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$employee = is_array($data['employee'] ?? null) ? $data['employee'] : [];
$notice = $data['notice'] ?? null;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$id = (int) ($employee['id'] ?? 0);
?>
<section>
    <p><a href="/employees">Back to employees</a></p>
    <?php if ($notice === 'deactivated'): ?>
        <p role="status">Employee deactivated.</p>
    <?php elseif ($notice === 'already-inactive'): ?>
        <p role="status">Employee was already inactive.</p>
    <?php endif; ?>

    <h1><?= $escape(($employee['last_name'] ?? '') . ' ' . ($employee['first_name'] ?? '')) ?></h1>
    <p>Status: <strong><?= $escape($employee['status'] ?? '') ?></strong></p>

    <dl>
        <dt>Employee code</dt>
        <dd><?= $escape($employee['employee_code'] ?? '') ?></dd>
        <dt>Name</dt>
        <dd><?= $escape(($employee['last_name'] ?? '') . ' ' . ($employee['first_name'] ?? '')) ?></dd>
        <dt>Name in kana</dt>
        <dd><?= $escape(($employee['last_name_kana'] ?? '') . ' ' . ($employee['first_name_kana'] ?? '')) ?></dd>
        <dt>Email</dt>
        <dd><?= $escape($employee['email'] ?? '') ?></dd>
        <dt>Phone</dt>
        <dd><?= $escape($employee['phone'] ?? '') ?></dd>
        <dt>Position</dt>
        <dd><?= $escape($employee['position_title'] ?? '') ?></dd>
        <dt>Employee type</dt>
        <dd><?= $escape($employee['employee_type'] ?? '') ?></dd>
        <dt>Hire date</dt>
        <dd><?= $escape($employee['hire_date'] ?? '') ?></dd>
        <dt>Branch</dt>
        <dd><?= $escape(($employee['branch_code'] ?? '') . ' ' . ($employee['branch_name'] ?? '')) ?></dd>
        <dt>Department</dt>
        <dd><?= $escape(
            $employee['department_name'] === null
                ? 'Not assigned'
                : (($employee['department_code'] ?? '') . ' ' . $employee['department_name']),
        ) ?></dd>
        <dt>Created</dt>
        <dd><?= $escape($employee['created_at'] ?? '') ?></dd>
        <dt>Updated</dt>
        <dd><?= $escape($employee['updated_at'] ?? '') ?></dd>
    </dl>

    <p>
        <a href="/employees/<?= $id ?>/edit">Edit employee</a>
        <?php if (($employee['status'] ?? null) === 'active'): ?>
            <a href="/employees/<?= $id ?>/deactivate">Deactivate employee</a>
        <?php endif; ?>
    </p>
</section>
