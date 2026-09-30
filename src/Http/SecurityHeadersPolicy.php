<?php

declare(strict_types=1);

namespace App\Http;

final class SecurityHeadersPolicy
{
    public function apply(Response $response, Request $request): Response
    {
        $contentType = $response->header('Content-Type') ?? '';
        if (!str_starts_with(strtolower($contentType), 'text/html')) {
            return $response;
        }

        $response = $response
            ->withHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'")
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->path() === '/login' || $request->routeAccess() !== 'public' || $request->authenticatedUser() !== null) {
            $response = $response
                ->withHeader('Cache-Control', 'no-store, private')
                ->withHeader('Pragma', 'no-cache');
        }

        return $response;
    }
}
