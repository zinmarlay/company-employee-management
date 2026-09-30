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
