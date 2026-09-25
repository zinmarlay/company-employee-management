<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Organization\DepartmentService;
use App\Http\Request;
use App\Http\Response;
use App\Http\Routing\NotFoundException;
use App\Http\View\ViewRenderer;

final class DepartmentController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly DepartmentService $departments,
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->views->renderPage('departments/index', [
            'pageTitleKey' => 'departments.title',
            'currentPath' => $request->path(),
            'activeNav' => 'departments',
            'departments' => $this->departments->listDepartments(),
            'notice' => $this->notice($request),
        ]));
    }

    public function show(Request $request): Response
    {
        $id = $this->id($request);
        $department = $this->departments->getDepartment($id);
        if ($department === null) {
            throw new NotFoundException($request->path());
        }

        return Response::html($this->views->renderPage('departments/show', [
            'pageTitle' => (string) $department['name'],
            'currentPath' => $request->path(),
            'activeNav' => 'departments',
            'department' => $department,
            'notice' => $this->notice($request),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->renderForm($request, $this->departments->createForm(), 200, 'departments/create');
    }

    public function store(Request $request): Response
    {
        $result = $this->departments->createDepartment($request->bodyParameters());
        if ($result['success']) {
            return Response::redirect('/departments/' . $result['id']);
        }

        $form = $this->departments->createForm();
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'departments/create');
    }

    public function edit(Request $request): Response
    {
        $id = $this->id($request);
        $form = $this->departments->editForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }

        $form['departmentId'] = $id;
        return $this->renderForm($request, $form, 200, 'departments/edit');
    }

    public function update(Request $request): Response
    {
        $id = $this->id($request);
        $result = $this->departments->updateDepartment($id, $request->bodyParameters());
        if ($result['id'] === null && $result['errors'] === []) {
            throw new NotFoundException($request->path());
        }
        if ($result['success']) {
            return Response::redirect('/departments/' . $id);
        }

        $form = $this->departments->editForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }
        $form['departmentId'] = $id;
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'departments/edit');
    }

    public function deactivateConfirmation(Request $request): Response
    {
        $id = $this->id($request);
        $department = $this->departments->deactivationForm($id);
        if ($department === null) {
            throw new NotFoundException($request->path());
        }

        return Response::html($this->views->renderPage('departments/deactivate', [
            'pageTitleKey' => 'departments.deactivate_title',
            'currentPath' => $request->path(),
            'activeNav' => 'departments',
            'department' => $department,
        ]));
    }

    public function deactivate(Request $request): Response
    {
        $id = $this->id($request);
        $result = $this->departments->deactivateDepartment($id);
        if ($result['status'] === 'missing') {
            throw new NotFoundException($request->path());
        }

        return Response::redirect('/departments/' . $id . '?notice=' . $result['status']);
    }

    /** @param array<string, mixed> $form */
    private function renderForm(Request $request, array $form, int $status, string $view): Response
    {
        $pageTitleKey = $view === 'departments/edit' ? 'departments.edit_title' : 'departments.create_title';
        return Response::html($this->views->renderPage($view, [
            'pageTitleKey' => $pageTitleKey,
            'currentPath' => $request->path(),
            'activeNav' => 'departments',
            ...$form,
        ]), $status);
    }

    private function id(Request $request): int
    {
        $raw = $request->routeParameter('id');
        if ($raw === null || preg_match('/^[1-9][0-9]*$/', $raw) !== 1) {
            throw new NotFoundException($request->path());
        }
        $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!is_int($id)) {
            throw new NotFoundException($request->path());
        }
        return $id;
    }

    private function notice(Request $request): ?string
    {
        $notice = $request->query('notice');
        return is_string($notice) && in_array($notice, ['deactivated', 'already-inactive'], true) ? $notice : null;
    }
}
