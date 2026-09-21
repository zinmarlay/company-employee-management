<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Routing\MethodNotAllowedException;
use App\Http\Routing\NotFoundException;
use App\Http\View\HtmlEscaper;
use Throwable;

final class ExceptionResponder
{
    public function __construct(private readonly bool $debug)
    {
    }

    public function respond(Throwable $exception): Response
    {
        if ($exception instanceof NotFoundException) {
            return Response::html($this->page('Not Found', 'The requested page could not be found.'), 404);
        }

        if ($exception instanceof MethodNotAllowedException) {
            return Response::html(
                $this->page('Method Not Allowed', 'The requested method is not supported for this page.'),
                405,
                ['Allow' => implode(', ', $exception->allowedMethods())],
            );
        }

        $message = 'An unexpected application error occurred.';

        if ($this->debug) {
            $diagnostic = substr($exception::class . ': ' . $exception->getMessage(), 0, 500);
            $message .= ' ' . HtmlEscaper::escape($diagnostic);
        }

        return Response::html($this->page('Server Error', $message), 500);
    }

    private function page(string $title, string $message): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>'
            . HtmlEscaper::escape($title)
            . '</title></head><body><main><h1>'
            . HtmlEscaper::escape($title)
            . '</h1><p>'
            . $message
            . '</p></main></body></html>';
    }
}
