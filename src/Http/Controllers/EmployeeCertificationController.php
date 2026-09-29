<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Employee\EmployeeCertificationService;
use App\Http\Request;
use App\Http\Response;
use App\Http\Routing\NotFoundException;
use App\Http\View\ViewRenderer;

final class EmployeeCertificationController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly EmployeeCertificationService $certifications,
    ) {
    }

    public function index(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $data = $this->certifications->list($employeeId);
        if ($data['status'] === 'missing') throw new NotFoundException($request->path());
        return $this->page($request, 'employee-certifications/index', $data, 'employees.certifications_title');
    }

    public function create(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        return $this->formResponse($request, $employeeId, $this->certifications->createForm($employeeId), 'employee-certifications/create', 200);
    }

    public function store(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $result = $this->certifications->create($employeeId, $request->bodyParameters());
        if ($result['status'] === 'missing') throw new NotFoundException($request->path());
        if ($result['status'] === 'inactive') return Response::redirect($this->listPath($employeeId) . '?notice=inactive-edit');
        if (in_array($result['status'], ['created', 'restored'], true)) return Response::redirect($this->listPath($employeeId) . '?notice=' . $result['status']);

        $form = $this->certifications->createForm($employeeId);
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->formResponse($request, $employeeId, $form, 'employee-certifications/create', 422);
    }

    public function show(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $certificationId = $this->id($request, 'certificationId');
        $data = $this->certifications->detail($employeeId, $certificationId);
        if ($data['status'] === 'missing') throw new NotFoundException($request->path());
        return $this->page($request, 'employee-certifications/show', $data, 'employees.certification_detail_title');
    }

    public function edit(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $certificationId = $this->id($request, 'certificationId');
        return $this->formResponse($request, $employeeId, $this->certifications->editForm($employeeId, $certificationId), 'employee-certifications/edit', 200);
    }

    public function update(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $certificationId = $this->id($request, 'certificationId');
        $result = $this->certifications->update($employeeId, $certificationId, $request->bodyParameters());
        if ($result['status'] === 'missing') throw new NotFoundException($request->path());
        if (in_array($result['status'], ['inactive', 'archived'], true)) return Response::redirect($this->listPath($employeeId) . '?notice=' . ($result['status'] === 'inactive' ? 'inactive-edit' : 'archived'));
        if ($result['status'] === 'updated') return Response::redirect('/employees/' . $employeeId . '/certifications/' . $certificationId . '?notice=updated');

        $form = $this->certifications->editForm($employeeId, $certificationId);
        if ($form['status'] === 'missing') throw new NotFoundException($request->path());
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->formResponse($request, $employeeId, $form, 'employee-certifications/edit', 422);
    }

    public function archiveConfirmation(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $certificationId = $this->id($request, 'certificationId');
        $data = $this->certifications->detail($employeeId, $certificationId);
        if ($data['status'] === 'missing') throw new NotFoundException($request->path());
        if (($data['employee']['status'] ?? '') !== 'active') return Response::redirect($this->listPath($employeeId) . '?notice=inactive-edit');
        if (($data['certification']['status'] ?? '') !== 'active') return Response::redirect($this->listPath($employeeId) . '?notice=already-archived');
        return $this->page($request, 'employee-certifications/archive', $data, 'employees.certification_archive_title');
    }

    public function archive(Request $request): Response
    {
        $employeeId = $this->id($request, 'employeeId');
        $certificationId = $this->id($request, 'certificationId');
        $result = $this->certifications->archive($employeeId, $certificationId);
        if ($result['status'] === 'missing') throw new NotFoundException($request->path());
        if ($result['status'] === 'inactive') return Response::redirect($this->listPath($employeeId) . '?notice=inactive-edit');
        return Response::redirect($this->listPath($employeeId) . '?notice=' . ($result['status'] === 'already-archived' ? 'already-archived' : 'archived'));
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
        return $this->page($request, $view, $form, $view === 'employee-certifications/create' ? 'employees.certification_create_title' : 'employees.certification_edit_title', $status);
    }

    private function listPath(int $employeeId): string { return '/employees/' . $employeeId . '/certifications'; }

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
