<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\SystemUser\SystemUserService;
use App\Domain\SystemUser\SystemUserDuplicateException;
use App\Domain\SystemUser\SystemUserAlreadyActiveException;
use App\Domain\SystemUser\SystemUserInactiveException;
use App\Domain\SystemUser\SystemUserLastAdminException;
use App\Domain\SystemUser\SystemUserNotFoundException;
use App\Domain\SystemUser\SystemUserSelfProtectionException;
use App\Http\Request;
use App\Http\Response;
use App\Http\SecurityErrorResponder;
use App\Http\Routing\NotFoundException;
use App\Http\View\ViewRenderer;

final class SystemUserController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly SystemUserService $users,
        private readonly SecurityErrorResponder $securityErrors,
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->renderPage('system-users/index', [
            'pageTitleKey' => 'system_users.title',
            'currentPath' => $request->path(),
            'activeNav' => 'system-users',
            'users' => $this->users->list(),
            'notice' => $this->notice($request),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->renderPage('system-users/create', [
            'pageTitleKey' => 'system_users.create_title',
            'currentPath' => $request->path(),
            'activeNav' => 'system-users',
            'values' => ['name' => '', 'email' => '', 'role' => 'USER'],
            'errors' => [],
        ]);
    }

    public function store(Request $request): Response
    {
        $result = $this->users->validateCreate($request->bodyParameters());

        if (!$result->isValid()) {
            return $this->renderPage('system-users/create', [
                'pageTitleKey' => 'system_users.create_title',
                'currentPath' => $request->path(),
                'activeNav' => 'system-users',
                'values' => $result->values,
                'errors' => $result->errors,
            ], 422);
        }

        try {
            $id = $this->users->create($result->input);
        } catch (SystemUserDuplicateException) {
            return $this->renderPage('system-users/create', [
                'pageTitleKey' => 'system_users.create_title',
                'currentPath' => $request->path(),
                'activeNav' => 'system-users',
                'values' => $result->values,
                'errors' => ['email' => 'A system user with this email already exists.'],
            ], 422);
        }

        return Response::redirect('/system-users/' . $id . '?notice=created');
    }

    public function show(Request $request): Response
    {
        $user = $this->find($request);

        return $this->renderPage('system-users/show', [
            'pageTitleKey' => 'system_users.detail_title',
            'currentPath' => $request->path(),
            'activeNav' => 'system-users',
            'systemUser' => $user,
            'notice' => $this->notice($request),
        ]);
    }

    public function edit(Request $request): Response
    {
        $user = $this->find($request);

        if ($user['status'] === 'inactive') {
            return Response::redirect('/system-users/' . $user['id'] . '?notice=inactive-readonly');
        }

        return $this->renderEdit($request, $user);
    }

    public function update(Request $request): Response
    {
        $id = $this->id($request);
        $user = $this->find($request);

        if ($user['status'] === 'inactive') {
            return Response::redirect('/system-users/' . $id . '?notice=inactive-readonly');
        }

        $result = $this->users->validateUpdate($request->bodyParameters(), $id);

        if (!$result->isValid()) {
            return $this->renderEdit($request, $user, $result->values, $result->errors, 422);
        }

        try {
            $this->users->update($id, $result->input, $request->authenticatedUser()?->id ?? 0);
        } catch (SystemUserDuplicateException) {
            return $this->renderEdit($request, $user, $result->values, ['email' => 'A system user with this email already exists.'], 422);
        } catch (SystemUserSelfProtectionException|SystemUserLastAdminException) {
            return $this->securityErrors->authorizationDenied();
        } catch (SystemUserNotFoundException) {
            throw new NotFoundException($request->path());
        }

        return Response::redirect('/system-users/' . $id . '?notice=updated');
    }

    public function deactivateConfirmation(Request $request): Response
    {
        $user = $this->find($request);

        if ($user['status'] === 'inactive') {
            return Response::redirect('/system-users/' . $user['id'] . '?notice=already-inactive');
        }

        return $this->renderPage('system-users/deactivate', [
            'pageTitleKey' => 'system_users.deactivate_title',
            'currentPath' => $request->path(),
            'activeNav' => 'system-users',
            'systemUser' => $user,
        ]);
    }

    public function deactivate(Request $request): Response
    {
        $id = $this->id($request);

        try {
            $this->users->deactivate($id, $request->authenticatedUser()?->id ?? 0);
        } catch (SystemUserSelfProtectionException|SystemUserLastAdminException) {
            return $this->securityErrors->authorizationDenied();
        } catch (SystemUserNotFoundException|SystemUserInactiveException) {
            throw new NotFoundException($request->path());
        }

        return Response::redirect('/system-users/' . $id . '?notice=deactivated');
    }

    public function activateConfirmation(Request $request): Response
    {
        $user = $this->find($request);

        if ($user['status'] === 'active') {
            return Response::redirect('/system-users/' . $user['id'] . '?notice=already-active');
        }

        return $this->renderPage('system-users/activate', [
            'pageTitleKey' => 'system_users.activate_title',
            'currentPath' => $request->path(),
            'activeNav' => 'system-users',
            'systemUser' => $user,
        ]);
    }

    public function activate(Request $request): Response
    {
        $id = $this->id($request);

        try {
            $this->users->activate($id);
        } catch (SystemUserAlreadyActiveException) {
            return Response::redirect('/system-users/' . $id . '?notice=already-active');
        } catch (SystemUserNotFoundException) {
            throw new NotFoundException($request->path());
        }

        return Response::redirect('/system-users/' . $id . '?notice=activated');
    }

    /** @return array<string, mixed> */
    private function find(Request $request): array
    {
        $id = $this->id($request);
        $user = $this->users->find($id);

        if ($user === null) {
            throw new NotFoundException($request->path());
        }

        return $user;
    }

    private function id(Request $request): int
    {
        $raw = $request->routeParameter('id');

        if (!is_string($raw) || preg_match('/^[1-9][0-9]*$/', $raw) !== 1) {
            throw new NotFoundException($request->path());
        }

        return (int) $raw;
    }

    /** @param array<string, mixed> $user @param array<string, mixed> $values @param array<string, string> $errors */
    private function renderEdit(Request $request, array $user, array $values = [], array $errors = [], int $status = 200): Response
    {
        $values = $values !== [] ? $values : ['name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];

        return $this->renderPage('system-users/edit', [
            'pageTitleKey' => 'system_users.edit_title',
            'currentPath' => $request->path(),
            'activeNav' => 'system-users',
            'systemUser' => $user,
            'values' => $values,
            'errors' => $errors,
        ], $status);
    }

    /** @param array<string, mixed> $data */
    private function renderPage(string $view, array $data, int $status = 200): Response
    {
        return Response::html($this->views->renderPage($view, $data), $status);
    }

    private function notice(Request $request): ?string
    {
        $notice = $request->query('notice');

        return is_string($notice) && in_array($notice, ['created', 'updated', 'deactivated', 'already-inactive', 'inactive-readonly', 'activated', 'already-active'], true)
            ? $notice
            : null;
    }
}
