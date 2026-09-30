<?php

declare(strict_types=1);

use App\Http\View\HtmlEscaper;

$t = $data['t'] ?? static fn (string $key, array $replace = []): string => $key;
$employees = is_array($data['employees'] ?? null) ? $data['employees'] : [];
$branches = is_array($data['searchBranches'] ?? null) ? $data['searchBranches'] : [];
$departments = is_array($data['searchDepartments'] ?? null) ? $data['searchDepartments'] : [];
$departmentCatalog = is_array($data['searchDepartmentCatalog'] ?? null) ? $data['searchDepartmentCatalog'] : $departments;
$criteria = $data['criteria'] ?? null;
$queryState = is_array($data['queryState'] ?? null) ? $data['queryState'] : [];
$total = (int) ($data['total'] ?? count($employees));
$currentPage = max(1, (int) ($data['currentPage'] ?? 1));
$totalPages = max(1, (int) ($data['totalPages'] ?? 1));
$notice = $data['notice'] ?? null;
$escape = static fn (mixed $value): string => HtmlEscaper::escape($value);
$queryString = static function (array $overrides = []) use ($queryState): string {
    $parameters = array_replace($queryState, $overrides);
    foreach ($parameters as $key => $value) {
        if ($value === null || $value === '' || ($key === 'page' && (int) $value <= 1)) {
            unset($parameters[$key]);
        }
    }

    $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    return $query === '' ? '/employees' : '/employees?' . $query;
};
$criteriaValue = static function (string $property) use ($criteria): string {
    if (!is_object($criteria) || !property_exists($criteria, $property)) {
        return '';
    }

    $value = $criteria->{$property};
    return $value === null ? '' : (string) $value;
};
$sortHref = static function (string $sort) use ($criteriaValue, $queryString): string {
    $direction = $criteriaValue('sort') === $sort && $criteriaValue('direction') === 'asc' ? 'desc' : 'asc';
    return $queryString(['sort' => $sort, 'direction' => $direction, 'page' => null]);
};
$departmentMetadata = array_map(
    static function (array $department) use ($t): array {
        $label = (string) ($department['display_label'] ?? (($department['branch_name'] ?? '') . ' · ' . ($department['name'] ?? '')));
        if (($department['status'] ?? '') !== 'active') {
            $label .= $t('form.inactive_suffix');
        }

        return [
            'id' => (int) ($department['id'] ?? 0),
            'branch_id' => (int) ($department['branch_id'] ?? 0),
            'label' => $label,
        ];
    },
    $departmentCatalog,
);
$departmentMetadataJson = json_encode(
    $departmentMetadata,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE,
);
if (!is_string($departmentMetadataJson)) {
    $departmentMetadataJson = '[]';
}
?>
<section class="page-section">
    <?php
    $data['pageEyebrowKey'] = 'employees.directory';
    $data['pageDescriptionKey'] = 'employees.description';
    $data['pageActions'] = [
        ['href' => '/employees/create', 'labelKey' => 'actions.create_employee', 'variant' => 'primary'],
    ];
    include __DIR__ . '/../partials/page-header.php';
    ?>

    <?php if ($notice === 'deactivated'): ?>
        <div class="alert alert--success" role="status"><?= $escape($t('employees.deactivated_success')) ?></div>
    <?php elseif ($notice === 'already-inactive'): ?>
        <div class="alert alert--info" role="status"><?= $escape($t('employees.already_inactive_notice')) ?></div>
    <?php endif; ?>

    <form class="card form-card employee-search-form" method="get" action="/employees">
        <div class="form-card__header">
            <div>
                <p class="eyebrow"><?= $escape($t('employees.search_title')) ?></p>
                <h2><?= $escape($t('employees.search_heading')) ?></h2>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-field form-field--wide">
                <label for="employee_keyword"><?= $escape($t('employees.keyword')) ?></label>
                <input id="employee_keyword" name="keyword" value="<?= $escape($criteriaValue('keyword')) ?>" placeholder="<?= $escape($t('employees.keyword_placeholder')) ?>">
                <p class="field-help"><?= $escape($t('employees.keyword_help')) ?></p>
            </div>
            <div class="form-field">
                <label for="employee_branch_id"><?= $escape($t('form.branch')) ?></label>
                <select id="employee_branch_id" name="branch_id">
                    <option value=""><?= $escape($t('employees.all_branches')) ?></option>
                    <?php foreach ($branches as $branch): ?>
                        <?php $branchId = (string) ($branch['id'] ?? ''); ?>
                        <option value="<?= $escape($branchId) ?>" <?= $criteriaValue('branchId') === $branchId ? 'selected' : '' ?>>
                            <?= $escape($branch['display_name'] ?? ($branch['name'] ?? '')) ?><?= ($branch['status'] ?? '') !== 'active' ? $escape($t('form.inactive_suffix')) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="employee_department_id"><?= $escape($t('form.department')) ?></label>
                <select id="employee_department_id" name="department_id"
                        data-all-label="<?= $escape($t('employees.all_departments')) ?>"
                        data-inactive-suffix="<?= $escape($t('form.inactive_suffix')) ?>">
                    <option value=""><?= $escape($t('employees.all_departments')) ?></option>
                    <?php foreach ($departments as $department): ?>
                        <?php $departmentId = (string) ($department['id'] ?? ''); ?>
                        <option value="<?= $escape($departmentId) ?>" <?= $criteriaValue('departmentId') === $departmentId ? 'selected' : '' ?>>
                            <?= $escape($department['display_label'] ?? (($department['branch_name'] ?? '') . ' · ' . ($department['name'] ?? ''))) ?><?= ($department['status'] ?? '') !== 'active' ? $escape($t('form.inactive_suffix')) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="employee_type"><?= $escape($t('form.employee_type')) ?></label>
                <select id="employee_type" name="employee_type">
                    <option value=""><?= $escape($t('employees.all_employee_types')) ?></option>
                    <option value="permanent" <?= $criteriaValue('employeeType') === 'permanent' ? 'selected' : '' ?>><?= $escape($t('status.permanent')) ?></option>
                    <option value="dispatched" <?= $criteriaValue('employeeType') === 'dispatched' ? 'selected' : '' ?>><?= $escape($t('status.dispatched')) ?></option>
                </select>
            </div>
            <div class="form-field">
                <label for="employee_status"><?= $escape($t('table.status')) ?></label>
                <select id="employee_status" name="status">
                    <option value=""><?= $escape($t('employees.all_statuses')) ?></option>
                    <option value="active" <?= $criteriaValue('status') === 'active' ? 'selected' : '' ?>><?= $escape($t('status.active')) ?></option>
                    <option value="inactive" <?= $criteriaValue('status') === 'inactive' ? 'selected' : '' ?>><?= $escape($t('status.inactive')) ?></option>
                </select>
            </div>
            <div class="form-field">
                <label for="employee_sort"><?= $escape($t('employees.sort_by')) ?></label>
                <select id="employee_sort" name="sort">
                    <?php foreach (['employee_code' => 'employees.sort_employee_code', 'name' => 'employees.sort_name', 'branch' => 'employees.sort_branch', 'department' => 'employees.sort_department', 'employee_type' => 'employees.sort_employee_type', 'status' => 'employees.sort_status'] as $sortKey => $sortLabel): ?>
                        <option value="<?= $escape($sortKey) ?>" <?= $criteriaValue('sort') === $sortKey ? 'selected' : '' ?>><?= $escape($t($sortLabel)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="employee_direction"><?= $escape($t('employees.sort_direction')) ?></label>
                <select id="employee_direction" name="direction">
                    <option value="asc" <?= $criteriaValue('direction') === 'asc' ? 'selected' : '' ?>><?= $escape($t('employees.ascending')) ?></option>
                    <option value="desc" <?= $criteriaValue('direction') === 'desc' ? 'selected' : '' ?>><?= $escape($t('employees.descending')) ?></option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <a class="button button--secondary" href="/employees"><?= $escape($t('actions.reset_filters')) ?></a>
            <button class="button button--primary" type="submit"><?= $escape($t('actions.search')) ?></button>
        </div>
    </form>

    <?php if ($employees === []): ?>
        <p class="record-count" role="status"><?= $escape($t('employees.record_count', ['count' => (string) $total])) ?></p>
    <?php endif; ?>

    <?php if ($employees === []): ?>
        <?php
        $data['emptyTitleKey'] = 'employees.no_search_results';
        $data['emptyMessageKey'] = 'employees.no_search_results_description';
        $data['emptyActionHref'] = '/employees';
        $data['emptyActionLabelKey'] = 'actions.reset_filters';
        include __DIR__ . '/../partials/empty-state.php';
        ?>
    <?php else: ?>
        <div class="card table-card">
            <div class="table-card__header">
                <div>
                    <h2><?= $escape($t('employees.directory_heading')) ?></h2>
                    <p class="muted-text"><?= $escape($t('employees.directory_description')) ?></p>
                </div>
                <span class="record-count" role="status"><?= $escape($t('employees.record_count', ['count' => (string) $total])) ?></span>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th scope="col"><a class="table-primary-link" href="<?= $escape($sortHref('employee_code')) ?>"><?= $escape($t('table.code')) ?></a></th>
                        <th scope="col"><a class="table-primary-link" href="<?= $escape($sortHref('name')) ?>"><?= $escape($t('table.name')) ?></a></th>
                        <th scope="col"><a class="table-primary-link" href="<?= $escape($sortHref('branch')) ?>"><?= $escape($t('table.branch')) ?></a></th>
                        <th scope="col"><a class="table-primary-link" href="<?= $escape($sortHref('department')) ?>"><?= $escape($t('table.department')) ?></a></th>
                        <th scope="col"><?= $escape($t('table.position')) ?></th>
                        <th scope="col"><a class="table-primary-link" href="<?= $escape($sortHref('employee_type')) ?>"><?= $escape($t('table.type')) ?></a></th>
                        <th scope="col"><a class="table-primary-link" href="<?= $escape($sortHref('status')) ?>"><?= $escape($t('table.status')) ?></a></th>
                        <th scope="col"><span class="visually-hidden"><?= $escape($t('table.actions')) ?></span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <?php
                        $id = (int) $employee['id'];
                        $typeLabelKey = $employee['employee_type'] === 'dispatched' ? 'status.dispatched' : 'status.permanent';
                        $typeTone = $employee['employee_type'] === 'dispatched' ? 'primary' : 'info';
                        $statusLabelKey = $employee['status'] === 'active' ? 'status.active' : 'status.inactive';
                        $statusTone = $employee['status'] === 'active' ? 'success' : 'neutral';
                        ?>
                        <tr>
                            <td data-label="<?= $escape($t('table.code')) ?>"><span class="code-text"><?= $escape($employee['employee_code']) ?></span></td>
                            <td data-label="<?= $escape($t('table.name')) ?>"><a class="table-primary-link" href="/employees/<?= $id ?>"><?= $escape($employee['last_name'] . ' ' . $employee['first_name']) ?></a></td>
                            <td data-label="<?= $escape($t('table.branch')) ?>"><?= $escape($employee['branch_display_name'] ?? $employee['branch_name']) ?></td>
                            <td data-label="<?= $escape($t('table.department')) ?>"><?= $escape($employee['department_display_name'] ?? ($employee['department_name'] ?? $t('form.not_assigned'))) ?></td>
                            <td data-label="<?= $escape($t('table.position')) ?>"><?= $escape($employee['position_title'] ?? '—') ?></td>
                            <td data-label="<?= $escape($t('table.type')) ?>"><?php $chipLabelKey = $typeLabelKey; $chipTone = $typeTone; include __DIR__ . '/../partials/status-chip.php'; ?></td>
                            <td data-label="<?= $escape($t('table.status')) ?>"><?php $chipLabelKey = $statusLabelKey; $chipTone = $statusTone; include __DIR__ . '/../partials/status-chip.php'; ?></td>
                            <td data-label="<?= $escape($t('table.actions')) ?>">
                                <div class="table-actions">
                                    <a class="button button--text button--small" href="/employees/<?= $id ?>"><?= $escape($t('actions.view')) ?></a>
                                    <?php if ($employee['status'] === 'active' && ($data['isAdmin'] ?? false)): ?>
                                        <a class="button button--text button--small" href="/employees/<?= $id ?>/edit"><?= $escape($t('actions.edit')) ?></a>
                                        <a class="button button--text button--small button--danger-text" href="/employees/<?= $id ?>/deactivate"><?= $escape($t('actions.deactivate')) ?></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
            <?php
            $pageWindow = range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2));
            if ($currentPage > 3) {
                $pageWindow[] = 1;
            }
            if ($currentPage < $totalPages - 2) {
                $pageWindow[] = $totalPages;
            }
            $pageWindow = array_values(array_unique($pageWindow));
            sort($pageWindow);
            $lastPageLink = 0;
            ?>
            <nav class="pagination" aria-label="<?= $escape($t('employees.pagination')) ?>">
                <?php if ($currentPage > 1): ?>
                    <a class="button button--secondary button--small" href="<?= $escape($queryString(['page' => $currentPage - 1])) ?>"><?= $escape($t('employees.previous')) ?></a>
                <?php endif; ?>
                <?php foreach ($pageWindow as $pageNumber): ?>
                    <?php if ($lastPageLink > 0 && $pageNumber > $lastPageLink + 1): ?><span aria-hidden="true">…</span><?php endif; ?>
                    <?php if ($pageNumber === $currentPage): ?>
                        <span class="button button--primary button--small" aria-current="page"><?= $escape((string) $pageNumber) ?></span>
                    <?php else: ?>
                        <a class="button button--secondary button--small" href="<?= $escape($queryString(['page' => $pageNumber])) ?>"><?= $escape((string) $pageNumber) ?></a>
                    <?php endif; ?>
                    <?php $lastPageLink = $pageNumber; ?>
                <?php endforeach; ?>
                <span class="pagination__summary"><?= $escape($t('employees.page_summary', ['page' => (string) $currentPage, 'total' => (string) $totalPages])) ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <a class="button button--secondary button--small" href="<?= $escape($queryString(['page' => $currentPage + 1])) ?>"><?= $escape($t('employees.next')) ?></a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
<script id="employee-search-department-data" type="application/json"><?= $departmentMetadataJson ?></script>
<script>
(() => {
    const branchSelect = document.getElementById('employee_branch_id');
    const departmentSelect = document.getElementById('employee_department_id');
    const metadataElement = document.getElementById('employee-search-department-data');

    if (!branchSelect || !departmentSelect || !metadataElement) {
        return;
    }

    let departmentCatalog;
    try {
        departmentCatalog = JSON.parse(metadataElement.textContent || '[]');
    } catch (error) {
        return;
    }

    if (!Array.isArray(departmentCatalog)) {
        return;
    }

    const renderDepartments = (branchId, selectedDepartmentId) => {
        const departments = branchId === ''
            ? departmentCatalog
            : departmentCatalog.filter((department) => String(department.branch_id) === branchId);
        const selectedId = departments.some((department) => String(department.id) === selectedDepartmentId)
            ? selectedDepartmentId
            : '';

        departmentSelect.replaceChildren();
        const allOption = document.createElement('option');
        allOption.value = '';
        allOption.textContent = departmentSelect.dataset.allLabel || '';
        departmentSelect.append(allOption);

        departments.forEach((department) => {
            const option = document.createElement('option');
            option.value = String(department.id);
            option.textContent = department.label;
            option.selected = String(department.id) === selectedId;
            departmentSelect.append(option);
        });

        departmentSelect.value = selectedId;
    };

    renderDepartments(branchSelect.value, departmentSelect.value);
    branchSelect.addEventListener('change', () => {
        renderDepartments(branchSelect.value, departmentSelect.value);
    });
})();
</script>
