<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Http\SecurityErrorResponder;

final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly SecurityErrorResponder $errors)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $access = $request->routeAccess();
        $user = $request->authenticatedUser();

        if ($access === 'public') {
            return $next->handle($request);
        }

        if ($user === null) {
            return Response::redirect('/login');
        }

        if ($access === 'admin' && !$user->isAdmin()) {
            return $this->errors->authorizationDenied();
        }

        return $next->handle($request);
    }
}
