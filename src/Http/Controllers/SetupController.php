<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Bootstrap\Configuration;
use App\Http\Request;
use App\Http\Response;
use App\Http\View\ViewRenderer;

final class SetupController
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly Configuration $configuration,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $page = $this->views->renderPage('setup', [
            'pageTitle' => 'Project Setup',
            'appName' => $this->configuration->name(),
            'requestPath' => $request->path(),
        ]);

        return Response::html($page);
    }
}
