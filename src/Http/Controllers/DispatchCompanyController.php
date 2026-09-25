<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Dispatch\DispatchCompanyService;
use App\Http\Request;
use App\Http\Response;
use App\Http\Routing\NotFoundException;
use App\Http\View\ViewRenderer;

final class DispatchCompanyController
{
    public function __construct(private readonly ViewRenderer $views, private readonly DispatchCompanyService $companies)
    {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->views->renderPage('dispatch-companies/index', [
            'pageTitleKey' => 'dispatch_companies.title',
            'currentPath' => $request->path(),
            'activeNav' => 'dispatch-companies',
            'companies' => $this->companies->listCompanies(),
            'notice' => $this->notice($request),
        ]));
    }

    public function show(Request $request): Response
    {
        $id = $this->id($request);
        $company = $this->companies->getCompany($id);
        if ($company === null) {
            throw new NotFoundException($request->path());
        }

        return Response::html($this->views->renderPage('dispatch-companies/show', [
            'pageTitle' => (string) $company['name'],
            'currentPath' => $request->path(),
            'activeNav' => 'dispatch-companies',
            'company' => $company,
            'notice' => $this->notice($request),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->renderForm($request, $this->companies->createForm(), 200, 'dispatch-companies/create');
    }

    public function store(Request $request): Response
    {
        $result = $this->companies->createCompany($request->bodyParameters());
        if ($result['success']) {
            return Response::redirect('/dispatch-companies/' . $result['id']);
        }

        $form = $this->companies->createForm();
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'dispatch-companies/create');
    }

    public function edit(Request $request): Response
    {
        $id = $this->id($request);
        $form = $this->companies->editForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }

        $form['companyId'] = $id;
        return $this->renderForm($request, $form, 200, 'dispatch-companies/edit');
    }

    public function update(Request $request): Response
    {
        $id = $this->id($request);
        $result = $this->companies->updateCompany($id, $request->bodyParameters());
        if ($result['id'] === null && $result['errors'] === []) {
            throw new NotFoundException($request->path());
        }
        if ($result['success']) {
            return Response::redirect('/dispatch-companies/' . $id);
        }

        $form = $this->companies->editForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }
        $form['companyId'] = $id;
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'dispatch-companies/edit');
    }

    public function deactivateConfirmation(Request $request): Response
    {
        $id = $this->id($request);
        $company = $this->companies->deactivationForm($id);
        if ($company === null) {
            throw new NotFoundException($request->path());
        }

        return Response::html($this->views->renderPage('dispatch-companies/deactivate', [
            'pageTitleKey' => 'dispatch_companies.deactivate_title',
            'currentPath' => $request->path(),
            'activeNav' => 'dispatch-companies',
            'company' => $company,
        ]));
    }

    public function deactivate(Request $request): Response
    {
        $id = $this->id($request);
        $result = $this->companies->deactivateCompany($id);
        if ($result['status'] === 'missing') {
            throw new NotFoundException($request->path());
        }

        return Response::redirect('/dispatch-companies/' . $id . '?notice=' . $result['status']);
    }

    /** @param array<string, mixed> $form */
    private function renderForm(Request $request, array $form, int $status, string $view): Response
    {
        $pageTitleKey = $view === 'dispatch-companies/edit'
            ? 'dispatch_companies.edit_title'
            : 'dispatch_companies.create_title';
        return Response::html($this->views->renderPage($view, [
            'pageTitleKey' => $pageTitleKey,
            'currentPath' => $request->path(),
            'activeNav' => 'dispatch-companies',
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
