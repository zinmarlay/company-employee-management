<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Http\SecurityErrorResponder;
use App\Security\CsrfTokenManager;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CsrfTokenManager $tokens,
        private readonly SecurityErrorResponder $errors,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if ($request->method() !== 'POST' || !$this->tokens->isValid($request->body('csrf_token'))) {
            if ($request->method() !== 'POST') {
                return $next->handle($request);
            }

            $this->errors->logCsrfDenied($request);
            return $this->errors->csrfDenied();
        }

        return $next->handle($request);
    }
}
