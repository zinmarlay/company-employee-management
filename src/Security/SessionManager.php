<?php

declare(strict_types=1);

namespace App\Security;

use App\Http\Request;
use RuntimeException;

final class SessionManager
{
    public const SESSION_NAME = 'company_employee_session';
    private const USER_ID_KEY = 'auth.user_id';
    private const CSRF_KEY = 'csrf.token';

    public function __construct(private readonly bool $requireHttps = false)
    {
    }

    public function start(Request $request): void
    {
        if ($this->requireHttps && !$request->isHttps()) {
            throw new RuntimeException('HTTPS is required in production.');
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (session_status() !== PHP_SESSION_NONE) {
            throw new RuntimeException('The PHP session is unavailable.');
        }

        session_name(self::SESSION_NAME);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        if (ini_get('session.use_strict_mode') !== '1'
            || ini_get('session.use_only_cookies') !== '1'
            || ini_get('session.use_trans_sid') !== '0') {
            throw new RuntimeException('Required session security settings could not be applied.');
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $request->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Request::fromGlobals() already mirrors PHP's cookie superglobal. This
        // explicit bridge also keeps the abstraction deterministic in focused
        // HTTP tests that construct Request values directly.
        $cookie = $request->cookie(self::SESSION_NAME);
        if ($cookie !== null && !isset($_COOKIE[self::SESSION_NAME])) {
            $_COOKIE[self::SESSION_NAME] = $cookie;
        }

        session_start();
    }

    public function userId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $value = $_SESSION[self::USER_ID_KEY] ?? null;

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1) {
            return (int) $value;
        }

        return $value === null ? null : 0;
    }

    public function setUserId(int $id): void
    {
        $this->requireActive();
        $_SESSION[self::USER_ID_KEY] = $id;
    }

    public function regenerate(): void
    {
        $this->requireActive();

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('The session could not be regenerated.');
        }
    }

    public function clearAuthentication(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[self::USER_ID_KEY]);
        }
    }

    public function destroy(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $_SESSION = [];
        $params = session_get_cookie_params();
        $header = sprintf(
            '%s=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; Path=%s; %s%s',
            rawurlencode(self::SESSION_NAME),
            $params['path'] !== '' ? $params['path'] : '/',
            $params['secure'] ? 'Secure; ' : '',
            $params['httponly'] ? 'HttpOnly; SameSite=' . ($params['samesite'] ?? 'Lax') : 'SameSite=' . ($params['samesite'] ?? 'Lax'),
        );
        session_destroy();

        return $header;
    }

    public function csrfToken(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $token = $_SESSION[self::CSRF_KEY] ?? null;

        return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1 ? $token : null;
    }

    public function setCsrfToken(string $token): void
    {
        $this->requireActive();
        $_SESSION[self::CSRF_KEY] = $token;
    }

    public function clearCsrfToken(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[self::CSRF_KEY]);
        }
    }

    private function requireActive(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('The PHP session has not been started.');
        }
    }
}
