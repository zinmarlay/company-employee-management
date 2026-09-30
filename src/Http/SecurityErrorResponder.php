<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\View\ViewRenderer;
use App\Logging\LoggerInterface;
use App\Logging\NullLogger;

final class SecurityErrorResponder
{
    public function __construct(private readonly ViewRenderer $views, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    private readonly LoggerInterface $logger;

    public function authorizationDenied(): Response
    {
        return $this->render('security.forbidden_title', 'security.forbidden_message');
    }

    public function logAuthorizationDenied(Request $request): void
    {
        $this->logger->warning('authorization_denied', \App\Logging\LogContext::request($request, ['outcome' => 'denied', 'status' => 403]));
    }

    public function logCsrfDenied(Request $request): void
    {
        $this->logger->warning('csrf_denied', \App\Logging\LogContext::request($request, ['outcome' => 'denied', 'status' => 403]));
    }

    public function csrfDenied(): Response
    {
        return $this->render('security.csrf_title', 'security.csrf_message');
    }

    private function render(string $titleKey, string $messageKey): Response
    {
        $page = $this->views->renderPage('errors/security', [
            'pageTitleKey' => $titleKey,
            'messageKey' => $messageKey,
            'currentPath' => '/',
        ]);

        return Response::html($page, 403);
    }
}
