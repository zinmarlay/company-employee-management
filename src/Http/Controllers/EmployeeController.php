<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Employee\EmployeeService;
use App\Bootstrap\Configuration;
use App\Http\Request;
use App\Http\Response;
use App\Http\Routing\NotFoundException;
use App\Http\View\ViewRenderer;
use DateTimeImmutable;
use DateTimeZone;

final class EmployeeController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly EmployeeService $employees,
        private readonly Configuration $configuration,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = $this->views->renderPage('employees/index', [
            'pageTitleKey' => 'employees.title',
            'currentPath' => $request->path(),
            'activeNav' => 'employees',
            'employees' => $this->employees->listEmployees(),
            'notice' => $this->notice($request),
        ]);

        return Response::html($page);
    }

    public function create(Request $request): Response
    {
        return $this->renderForm($request, $this->employees->createForm(), 200, 'employees/create');
    }

    public function store(Request $request): Response
    {
        $result = $this->employees->createEmployee($request->bodyParameters());

        if ($result['success']) {
            return Response::redirect('/employees/' . $result['id']);
        }

        $form = $this->employees->createForm();
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];

        return $this->renderForm($request, $form, 422, 'employees/create');
    }

    public function show(Request $request): Response
    {
        $id = $this->employeeId($request);
        $employee = $this->employees->getEmployee($id);

        if ($employee === null) {
            throw new NotFoundException($request->path());
        }

        $employee['created_at'] = $this->displayDateTime($employee['created_at']);
        $employee['updated_at'] = $this->displayDateTime($employee['updated_at']);

        $page = $this->views->renderPage('employees/show', [
            'pageTitle' => $employee['first_name'] . ' ' . $employee['last_name'],
            'currentPath' => $request->path(),
            'activeNav' => 'employees',
            'employee' => $employee,
            'notice' => $this->notice($request),
        ]);

        return Response::html($page);
    }

    public function edit(Request $request): Response
    {
        $id = $this->employeeId($request);
        $form = $this->employees->editForm($id);

        if ($form === null) {
            throw new NotFoundException($request->path());
        }

        return $this->renderForm($request, $form, 200, 'employees/edit');
    }

    public function update(Request $request): Response
    {
        $id = $this->employeeId($request);
        $result = $this->employees->updateEmployee($id, $request->bodyParameters());

        if ($result['id'] === null && $result['errors'] === []) {
            throw new NotFoundException($request->path());
        }

        if ($result['success']) {
            return Response::redirect('/employees/' . $id);
        }

        $form = $this->employees->editForm($id);

        if ($form === null) {
            throw new NotFoundException($request->path());
        }

        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];

        return $this->renderForm($request, $form, 422, 'employees/edit');
    }

    public function deactivateConfirmation(Request $request): Response
    {
        $id = $this->employeeId($request);
        $employee = $this->employees->deactivationForm($id);

        if ($employee === null) {
            throw new NotFoundException($request->path());
        }

        $page = $this->views->renderPage('employees/deactivate', [
            'pageTitleKey' => 'employees.deactivate_title',
            'currentPath' => $request->path(),
            'activeNav' => 'employees',
            'employee' => $employee,
        ]);

        return Response::html($page);
    }

    public function deactivate(Request $request): Response
    {
        $id = $this->employeeId($request);
        $result = $this->employees->deactivateEmployee($id);

        if ($result['status'] === 'missing') {
            throw new NotFoundException($request->path());
        }

        return Response::redirect('/employees/' . $id . '?notice=' . $result['status']);
    }

    /**
     * @param array<string, mixed> $form
     */
    private function renderForm(Request $request, array $form, int $status, string $view): Response
    {
        $pageTitleKey = $view === 'employees/edit'
            ? 'employees.edit_title'
            : 'employees.create_title';
        $page = $this->views->renderPage($view, [
            'pageTitleKey' => $pageTitleKey,
            'currentPath' => $request->path(),
            'activeNav' => 'employees',
            ...$form,
        ]);

        return Response::html($page, $status);
    }

    private function employeeId(Request $request): int
    {
        $rawId = $request->routeParameter('id');

        if ($rawId === null || preg_match('/^[1-9][0-9]*$/', $rawId) !== 1) {
            throw new NotFoundException($request->path());
        }

        $id = filter_var($rawId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if (!is_int($id)) {
            throw new NotFoundException($request->path());
        }

        return $id;
    }

    private function displayDateTime(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $value,
            new DateTimeZone('UTC'),
        );

        if ($date === false) {
            return $value;
        }

        return $date
            ->setTimezone(new DateTimeZone($this->configuration->timezone()))
            ->format('Y-m-d H:i:s');
    }

    private function notice(Request $request): ?string
    {
        $notice = $request->query('notice');

        return is_string($notice) && in_array($notice, ['deactivated', 'already-inactive'], true)
            ? $notice
            : null;
    }
}
