<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Localization\Locale;
use App\Localization\Translator;

final class LocaleMiddleware implements MiddlewareInterface
{
    private const COOKIE_NAME = 'app_locale';
    private const COOKIE_MAX_AGE = 31536000;

    public function __construct(private readonly Translator $translator)
    {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $queryLocale = $request->query('lang');
        $hasLocaleQuery = $queryLocale !== null;
        $requestedLocale = Locale::normalize($queryLocale);
        $cookieLocale = Locale::normalize($request->cookie(self::COOKIE_NAME));
        $locale = $hasLocaleQuery
            ? ($requestedLocale ?? Locale::ENGLISH)
            : ($cookieLocale ?? Locale::ENGLISH);
        $this->translator->setLocale($locale);

        $response = $next->handle($request);

        if ($requestedLocale !== null) {
            $response = $response->withHeader('Set-Cookie', $this->cookieHeader($requestedLocale));
        }

        return $response;
    }

    private function cookieHeader(string $locale): string
    {
        return sprintf(
            '%s=%s; Max-Age=%d; Path=/; SameSite=Lax',
            self::COOKIE_NAME,
            rawurlencode($locale),
            self::COOKIE_MAX_AGE,
        );
    }
}
