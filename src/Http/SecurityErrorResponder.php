<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\View\ViewRenderer;

final class SecurityErrorResponder
{
    public function __construct(private readonly ViewRenderer $views)
    {
    }

    public function authorizationDenied(): Response
    {
        return $this->render('security.forbidden_title', 'security.forbidden_message');
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
