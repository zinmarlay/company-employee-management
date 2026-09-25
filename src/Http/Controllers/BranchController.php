<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Organization\BranchService;
use App\Http\Request;
use App\Http\Response;
use App\Http\Routing\NotFoundException;
use App\Http\View\ViewRenderer;

final class BranchController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly BranchService $branches,
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->views->renderPage('branches/index', [
            'pageTitleKey' => 'branches.title',
            'currentPath' => $request->path(),
            'activeNav' => 'branches',
            'branches' => $this->branches->listBranches(),
            'notice' => $this->notice($request),
        ]));
    }

    public function show(Request $request): Response
    {
        $id = $this->id($request);
        $branch = $this->branches->getBranch($id);
        if ($branch === null) {
            throw new NotFoundException($request->path());
        }

        return Response::html($this->views->renderPage('branches/show', [
            'pageTitle' => (string) $branch['name'],
            'currentPath' => $request->path(),
            'activeNav' => 'branches',
            'branch' => $branch,
            'notice' => $this->notice($request),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->renderForm($request, $this->branches->createForm(), 200, 'branches/create');
    }

    public function store(Request $request): Response
    {
        $result = $this->branches->createBranch($request->bodyParameters());
        if ($result['success']) {
            return Response::redirect('/branches/' . $result['id']);
        }

        $form = $this->branches->createForm();
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'branches/create');
    }

    public function edit(Request $request): Response
    {
        $id = $this->id($request);
        $form = $this->branches->editForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }

        $form['branchId'] = $id;
        return $this->renderForm($request, $form, 200, 'branches/edit');
    }

    public function update(Request $request): Response
    {
        $id = $this->id($request);
        $result = $this->branches->updateBranch($id, $request->bodyParameters());
        if ($result['id'] === null && $result['errors'] === []) {
            throw new NotFoundException($request->path());
        }
        if ($result['success']) {
            return Response::redirect('/branches/' . $id);
        }

        $form = $this->branches->editForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }
        $form['branchId'] = $id;
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'branches/edit');
    }

    public function deactivateConfirmation(Request $request): Response
    {
        $id = $this->id($request);
        $branch = $this->branches->deactivationForm($id);
        if ($branch === null) {
            throw new NotFoundException($request->path());
        }

        return Response::html($this->views->renderPage('branches/deactivate', [
            'pageTitleKey' => 'branches.deactivate_title',
            'currentPath' => $request->path(),
            'activeNav' => 'branches',
            'branch' => $branch,
        ]));
    }

    public function deactivate(Request $request): Response
    {
        $id = $this->id($request);
        $result = $this->branches->deactivateBranch($id);
        if ($result['status'] === 'missing') {
            throw new NotFoundException($request->path());
        }

        return Response::redirect('/branches/' . $id . '?notice=' . $result['status']);
    }

    /** @param array<string, mixed> $form */
    private function renderForm(Request $request, array $form, int $status, string $view): Response
    {
        $pageTitleKey = $view === 'branches/edit' ? 'branches.edit_title' : 'branches.create_title';
        return Response::html($this->views->renderPage($view, [
            'pageTitleKey' => $pageTitleKey,
            'currentPath' => $request->path(),
            'activeNav' => 'branches',
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
