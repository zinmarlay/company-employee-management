<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Routing\MethodNotAllowedException;
use App\Http\Routing\NotFoundException;
use App\Http\View\HtmlEscaper;
use App\Localization\Translator;
use Throwable;

final class ExceptionResponder
{
    public function __construct(private readonly bool $debug, private readonly ?Translator $translator = null)
    {
    }

    public function respond(Throwable $exception): Response
    {
        if ($exception instanceof NotFoundException) {
            return Response::html($this->page($this->text('errors.not_found_title', 'Not Found'), $this->text('errors.not_found_message', 'The requested page could not be found.')), 404);
        }

        if ($exception instanceof MethodNotAllowedException) {
            return Response::html(
                $this->page($this->text('errors.method_not_allowed_title', 'Method Not Allowed'), $this->text('errors.method_not_allowed_message', 'The requested method is not supported for this page.')),
                405,
                ['Allow' => implode(', ', $exception->allowedMethods())],
            );
        }

        $message = 'An unexpected application error occurred.';

        if ($this->debug) {
            $diagnostic = substr($exception::class . ': ' . $exception->getMessage(), 0, 500);
            $message .= ' ' . HtmlEscaper::escape($diagnostic);
        }

        return Response::html($this->page($this->text('errors.server_error_title', 'Server Error'), $message), 500);
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

    private function text(string $key, string $fallback): string
    {
        $value = $this->translator?->get($key) ?? $fallback;
        return $value === $key ? $fallback : $value;
    }
}
