<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Employee\EmployeeSkillService;
use App\Http\Request;
use App\Http\Response;
use App\Http\Routing\NotFoundException;
use App\Http\View\ViewRenderer;

final class EmployeeSkillController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly EmployeeSkillService $skills,
    ) {
    }

    public function index(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $data = $this->skills->list($employeeId);
        if ($data['status'] === 'missing') throw new NotFoundException($request->path());

        return $this->page($request, 'employee-skills/index', $data, 'employees.skills_title');
    }

    public function create(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $form = $this->skills->createForm($employeeId);
        return $this->formResponse($request, $employeeId, $form, 'employee-skills/create', 200);
    }

    public function store(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $result = $this->skills->create($employeeId, $request->bodyParameters());

        if ($result['status'] === 'missing') throw new NotFoundException($request->path());
        if ($result['status'] === 'inactive') return Response::redirect($this->listPath($employeeId) . '?notice=inactive-edit');
        if (in_array($result['status'], ['created', 'restored'], true)) {
            return Response::redirect($this->listPath($employeeId) . '?notice=' . $result['status']);
        }

        $form = $this->skills->createForm($employeeId);
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->formResponse($request, $employeeId, $form, 'employee-skills/create', 422);
    }

    public function edit(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $assignmentId = $this->id($request, 'skillId');
        $form = $this->skills->editForm($employeeId, $assignmentId);
        return $this->formResponse($request, $employeeId, $form, 'employee-skills/edit', 200);
    }

    public function update(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $assignmentId = $this->id($request, 'skillId');
        $result = $this->skills->update($employeeId, $assignmentId, $request->bodyParameters());

        if ($result['status'] === 'missing') throw new NotFoundException($request->path());
        if (in_array($result['status'], ['inactive', 'archived'], true)) {
            return Response::redirect($this->listPath($employeeId) . '?notice=' . ($result['status'] === 'inactive' ? 'inactive-edit' : 'archived'));
        }
        if (in_array($result['status'], ['updated', 'restored'], true)) {
            return Response::redirect($this->listPath($employeeId) . '?notice=updated');
        }

        $form = $this->skills->editForm($employeeId, $assignmentId);
        if ($form['status'] === 'missing') throw new NotFoundException($request->path());
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->formResponse($request, $employeeId, $form, 'employee-skills/edit', 422);
    }

    public function archiveConfirmation(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $assignmentId = $this->id($request, 'skillId');
        $form = $this->skills->editForm($employeeId, $assignmentId);
        if ($form['status'] === 'missing') throw new NotFoundException($request->path());
        if (in_array($form['status'], ['inactive', 'archived'], true)) {
            return Response::redirect($this->listPath($employeeId) . '?notice=' . ($form['status'] === 'inactive' ? 'inactive-edit' : 'already-archived'));
        }

        return $this->page($request, 'employee-skills/archive', $form, 'employees.skills_archive_title');
    }

    public function archive(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $assignmentId = $this->id($request, 'skillId');
        $result = $this->skills->archive($employeeId, $assignmentId);

        if ($result['status'] === 'missing') throw new NotFoundException($request->path());
        if ($result['status'] === 'inactive') return Response::redirect($this->listPath($employeeId) . '?notice=inactive-edit');
        if ($result['status'] === 'archived' || $result['status'] === 'already-archived') {
            return Response::redirect($this->listPath($employeeId) . '?notice=already-archived');
        }

        return Response::redirect($this->listPath($employeeId) . '?notice=archived');
    }

    /** @param array<string,mixed> $data */
    private function page(Request $request, string $view, array $data, string $titleKey, int $status = 200): Response
    {
        $employee = is_array($data['employee'] ?? null) ? $data['employee'] : [];
        return Response::html($this->views->renderPage($view, [
            ...$data,
            'pageTitleKey' => $titleKey,
            'currentPath' => $request->path(),
            'activeNav' => 'employees',
            'notice' => $this->notice($request),
        ]), $status);
    }

    /** @param array<string,mixed> $form */
    private function formResponse(Request $request, int $employeeId, array $form, string $view, int $status): Response
    {
        if ($form['status'] === 'missing') throw new NotFoundException($request->path());
        if ($form['status'] === 'inactive') return Response::redirect($this->listPath($employeeId) . '?notice=inactive-edit');
        if ($form['status'] === 'archived') return Response::redirect($this->listPath($employeeId) . '?notice=already-archived');

        return $this->page($request, $view, $form, $view === 'employee-skills/create' ? 'employees.skills_create_title' : 'employees.skills_edit_title', $status);
    }

    private function listPath(int $employeeId): string
    {
        return '/employees/' . $employeeId . '/skills';
    }

    private function id(Request $request, string $parameter): int
    {
        $raw = $request->routeParameter($parameter);
        if ($raw === null || preg_match('/^[1-9][0-9]*$/', $raw) !== 1) throw new NotFoundException($request->path());
        $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!is_int($id)) throw new NotFoundException($request->path());
        return $id;
    }

    private function notice(Request $request): ?string
    {
        $notice = $request->query('notice');
        return is_string($notice) && in_array($notice, ['created', 'restored', 'updated', 'archived', 'already-archived', 'inactive-edit'], true) ? $notice : null;
    }
}
