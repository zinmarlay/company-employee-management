<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$employees = is_array($data['employees'] ?? null) ? $data['employees'] : [];
$notice = $data['notice'] ?? null;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
?>
<section>
    <header>
        <h1>Employees</h1>
        <p><a href="/employees/create">Create employee</a></p>
    </header>

    <?php if ($notice === 'deactivated'): ?>
        <p role="status">Employee deactivated.</p>
    <?php elseif ($notice === 'already-inactive'): ?>
        <p role="status">Employee was already inactive.</p>
    <?php endif; ?>

    <?php if ($employees === []): ?>
        <p>No employees have been registered.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th scope="col">Code</th>
                <th scope="col">Name</th>
                <th scope="col">Branch</th>
                <th scope="col">Department</th>
                <th scope="col">Position</th>
                <th scope="col">Type</th>
                <th scope="col">Status</th>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $employee): ?>
                <?php $id = (int) $employee['id']; ?>
                <tr>
                    <td><?= $escape($employee['employee_code']) ?></td>
                    <td><?= $escape($employee['last_name'] . ' ' . $employee['first_name']) ?></td>
                    <td><?= $escape($employee['branch_name']) ?></td>
                    <td><?= $escape($employee['department_name'] ?? 'Not assigned') ?></td>
                    <td><?= $escape($employee['position_title'] ?? '') ?></td>
                    <td><?= $escape($employee['employee_type']) ?></td>
                    <td><?= $escape($employee['status']) ?></td>
                    <td>
                        <a href="/employees/<?= $id ?>">View</a>
                        <a href="/employees/<?= $id ?>/edit">Edit</a>
                        <?php if ($employee['status'] === 'active'): ?>
                            <a href="/employees/<?= $id ?>/deactivate">Deactivate</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
