<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Dispatch\DispatchContractService;
use App\Http\Request;
use App\Http\Response;
use App\Http\Routing\NotFoundException;
use App\Http\View\ViewRenderer;

final class DispatchContractController
{
    public function __construct(private readonly ViewRenderer $views, private readonly DispatchContractService $contracts)
    {
    }

    public function create(Request $request): Response
    {
        $employeeId = $this->queryId($request, 'employee_id');
        return $this->renderForm($request, $this->contracts->createForm($employeeId), 200, 'dispatch-contracts/create');
    }

    public function store(Request $request): Response
    {
        $result = $this->contracts->createContract($request->bodyParameters());
        if ($result['success']) {
            return Response::redirect('/dispatch-contracts/' . $result['id']);
        }

        $form = $this->contracts->createForm($this->positiveId($result['values']['employee_id'] ?? null));
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'dispatch-contracts/create');
    }

    public function show(Request $request): Response
    {
        $id = $this->id($request);
        $contract = $this->contracts->getContract($id);
        if ($contract === null) {
            throw new NotFoundException($request->path());
        }

        return Response::html($this->views->renderPage('dispatch-contracts/show', [
            'pageTitleKey' => 'dispatch_contracts.detail_title',
            'currentPath' => $request->path(),
            'activeNav' => 'dispatch-contracts',
            'contract' => $contract,
        ]));
    }

    public function edit(Request $request): Response
    {
        $id = $this->id($request);
        $form = $this->contracts->editForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }
        $form['contractId'] = $id;
        return $this->renderForm($request, $form, 200, 'dispatch-contracts/edit');
    }

    public function update(Request $request): Response
    {
        $id = $this->id($request);
        $result = $this->contracts->updateContract($id, $request->bodyParameters());
        if ($result['id'] === null && $result['errors'] === []) {
            throw new NotFoundException($request->path());
        }
        if ($result['success']) {
            return Response::redirect('/dispatch-contracts/' . $id);
        }

        $form = $this->contracts->editForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }
        $form['contractId'] = $id;
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'dispatch-contracts/edit');
    }

    public function renew(Request $request): Response
    {
        $id = $this->id($request);
        $form = $this->contracts->renewForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }
        $form['contractId'] = $id;
        return $this->renderForm($request, $form, 200, 'dispatch-contracts/renew');
    }

    public function renewStore(Request $request): Response
    {
        $id = $this->id($request);
        $result = $this->contracts->renewContract($id, $request->bodyParameters());
        if ($result['id'] === null && $result['errors'] === []) {
            throw new NotFoundException($request->path());
        }
        if ($result['success']) {
            return Response::redirect('/dispatch-contracts/' . $result['id']);
        }

        $form = $this->contracts->renewForm($id);
        if ($form === null) {
            throw new NotFoundException($request->path());
        }
        $form['contractId'] = $id;
        $form['values'] = $result['values'];
        $form['errors'] = $result['errors'];
        return $this->renderForm($request, $form, 422, 'dispatch-contracts/renew');
    }

    /** @param array<string, mixed> $form */
    private function renderForm(Request $request, array $form, int $status, string $view): Response
    {
        $pageTitleKey = match ($view) {
            'dispatch-contracts/edit' => 'dispatch_contracts.edit_title',
            'dispatch-contracts/renew' => 'dispatch_contracts.renew_title',
            default => 'dispatch_contracts.create_title',
        };
        return Response::html($this->views->renderPage($view, [
            'pageTitleKey' => $pageTitleKey,
            'currentPath' => $request->path(),
            'activeNav' => 'dispatch-contracts',
            ...$form,
        ]), $status);
    }

    private function id(Request $request): int
    {
        $id = $this->positiveId($request->routeParameter('id'));
        if ($id === null) {
            throw new NotFoundException($request->path());
        }
        return $id;
    }

    private function queryId(Request $request, string $key): ?int
    {
        $value = $request->query($key);
        return $value === null ? null : $this->positiveId($value);
    }

    private function positiveId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $value = (string) $value;
        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return is_int($id) ? $id : null;
    }
}
