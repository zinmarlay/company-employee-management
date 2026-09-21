<?php

declare(strict_types=1);

namespace App\Http\View;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ViewRenderer
{
    public function __construct(private readonly string $viewsRoot)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $templatePath = $this->resolve($view);

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
        $content = $this->render($view, $data);
        $layoutData = [...$data, 'content' => $content];

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
