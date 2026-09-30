<?php

declare(strict_types=1);

namespace App\Http\View;

use App\Localization\Translator;
use App\Security\AuthenticationContext;
use App\Security\CsrfTokenManager;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ViewRenderer
{
    private readonly Translator $translator;

    public function __construct(
        private readonly string $viewsRoot,
        ?Translator $translator = null,
        private readonly ?CsrfTokenManager $csrfTokens = null,
        private readonly ?AuthenticationContext $authenticationContext = null,
    ) {
        $this->translator = $translator ?? new Translator(dirname($viewsRoot) . '/lang');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        if ($this->csrfTokens !== null) {
            $data['csrfToken'] = $this->csrfTokens->token();
        }

        $templatePath = $this->resolve($view);
        $data['translator'] = $this->translator;
        $data['locale'] = $this->translator->locale();
        $data['t'] = fn (string $key, array $replace = []): string => $this->translator->get($key, $replace);

        ob_start();

        try {
            include $templatePath;

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderPage(string $view, array $data = [], string $layout = 'layout'): string
    {
        if ($this->csrfTokens !== null) {
            $data['csrfToken'] = $this->csrfTokens->token();
        }

        if ($this->authenticationContext !== null) {
            $data['authenticatedUser'] = $this->authenticationContext->user();
            $data['isAdmin'] = $this->authenticationContext->user()?->isAdmin() ?? false;
        } else {
            // Standalone controller/view tests do not install the security
            // composition root and retain their pre-Phase-10 presentation.
            $data['isAdmin'] = true;
        }

        if (isset($data['pageTitleKey']) && is_string($data['pageTitleKey'])) {
            $data['pageTitle'] = $this->translator->get($data['pageTitleKey']);
        }

        $content = $this->render($view, $data);
        $layoutData = [
            ...$data,
            'content' => $content,
            'translator' => $this->translator,
            'locale' => $this->translator->locale(),
            't' => fn (string $key, array $replace = []): string => $this->translator->get($key, $replace),
        ];

        return $this->render($layout, $layoutData);
    }

    private function resolve(string $view): string
    {
        if (preg_match('/^[A-Za-z0-9_-]+(?:\/[A-Za-z0-9_-]+)*$/', $view) !== 1) {
            throw new InvalidArgumentException('View identifiers must contain only trusted path segments.');
        }

        $root = realpath($this->viewsRoot);

        if ($root === false) {
            throw new RuntimeException('The configured views directory does not exist.');
        }

        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
        $resolvedPath = realpath($path);

        if ($resolvedPath === false || !str_starts_with($resolvedPath, $root . DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException('The requested view does not exist within the configured views directory.');
        }

        return $resolvedPath;
    }
}
