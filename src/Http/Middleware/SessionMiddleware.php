<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Security\SessionManager;

final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly SessionManager $session)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $this->session->start($request);

        return $next->handle($request);
    }
}
