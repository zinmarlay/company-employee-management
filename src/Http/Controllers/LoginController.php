<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\View\ViewRenderer;
use App\Security\AuthenticationService;
use App\Security\SessionManager;

final class LoginController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly AuthenticationService $authentication,
        private readonly SessionManager $session,
    ) {
    }

    public function show(Request $request): Response
    {
        if ($request->authenticatedUser() !== null) {
            return Response::redirect('/');
        }

        return $this->render($request, 200, (string) ($request->query('notice') ?? ''));
    }

    public function authenticate(Request $request): Response
    {
        if ($request->authenticatedUser() !== null) {
            return Response::redirect('/');
        }

        $email = $request->body('email');
        $password = $request->body('password');

        if (!is_string($email) || !is_string($password)) {
            return $this->render($request, 422, '', 'auth.invalid_credentials', is_string($email) ? strtolower(trim($email)) : '');
        }

        if ($this->authentication->authenticate($email, $password) === null) {
            return $this->render($request, 422, '', 'auth.invalid_credentials', strtolower(trim($email)));
        }

        return Response::redirect('/');
    }

    public function logout(Request $request): Response
    {
        $cookie = $this->session->destroy();
        $response = Response::redirect('/login?notice=logged-out');

        return $cookie === '' ? $response : $response->withHeader('Set-Cookie', $cookie);
    }

    /** @param string $email */
    private function render(Request $request, int $status, string $notice = '', ?string $errorKey = null, string $email = ''): Response
    {
        $page = $this->views->renderPage('auth/login', [
            'pageTitleKey' => 'auth.login_title',
            'currentPath' => '/login',
            'values' => ['email' => $email],
            'errorKey' => $errorKey,
            'notice' => in_array($notice, ['logged-out'], true) ? $notice : null,
        ]);

        return Response::html($page, $status);
    }
}
