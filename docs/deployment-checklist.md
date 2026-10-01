# Production deployment checklist

Use this checklist before enabling the application in production.

## Application and transport

- [ ] Set `APP_ENV=production`.
- [ ] Set `APP_DEBUG=false`.
- [ ] Set `APP_URL` to the public direct `https://` URL.
- [ ] Confirm the deployed PHP runtime includes `pdo_mysql` and `mbstring`.
- [ ] Install production dependencies with `composer install --no-dev --classmap-authoritative` or the deployment-equivalent verified by the operator.
- [ ] Confirm the application receives the trusted server HTTPS signal (`HTTPS=on` or `HTTPS=1`).
- [ ] Do not rely on `X-Forwarded-Proto`, `Forwarded`, or other client-controlled forwarding headers. Trusted reverse-proxy support is not part of Phase 11.
- [ ] Serve only `public/` as the web document root.
- [ ] Keep source files, `.env`, migrations, runtime directories, and operational scripts outside the web-accessible document root.
- [ ] Configure web/PHP request-size and execution-time limits appropriate to this server-rendered application.
- [ ] Confirm HTTP requests cannot reach the application as an established production HTTPS request.

## Database

- [ ] Supply `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_CHARSET` explicitly in the production process environment or deployment secret store.
- [ ] Do not inherit local `.env` credentials or the example `change-me` values.
- [ ] Confirm missing or invalid production database configuration fails during startup without exposing credential values.
- [ ] Confirm the existing migration set is applied. Phase 11 does not require a database migration.
- [ ] Confirm the database account has only the permissions required by the application and migration process.
- [ ] Review migration status and run forward migrations through `php bin/migrate migrate`; migrations must not run from web requests.
- [ ] Create the initial administrator through `php bin/system-user create-admin` over a protected operational channel, with no default password.

## Session, authentication, and CSRF

- [ ] Confirm sessions use strict mode, cookies only, `HttpOnly`, `SameSite=Lax`, and `Secure` cookies over HTTPS.
- [ ] Confirm passwords are stored and verified with the existing password-hashing flow.
- [ ] Confirm login failures remain generic and contain no email/login identifier in logs.
- [ ] Confirm all POST mutations require a valid CSRF token.
- [ ] Confirm inactive accounts cannot authenticate and session authentication is revalidated against the database.

## Headers and caching

- [ ] Confirm HTML responses emit `Content-Security-Policy` with `script-src 'self'` and `frame-ancestors 'none'`.
- [ ] Confirm HTML responses emit `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, and the configured referrer policy.
- [ ] Confirm authenticated, login, and non-public HTML responses emit `Cache-Control: no-store, private` and `Pragma: no-cache`.
- [ ] Confirm employee-search JavaScript is served from `/assets/js/employee-search.js`; no employee-search executable JavaScript remains inline.

## Logging and operations

- [ ] Configure PHP `error_log` to the host/operator-approved destination.
- [ ] Configure host-level rotation, retention, permissions, alerting, and monitoring for that destination.
- [ ] Confirm logs contain request/correlation IDs for unexpected exceptions and security operational events.
- [ ] Confirm log call sites pass allowlisted operational metadata only; never pass request bodies, `$_SERVER`, `$_ENV`, cookies, sessions, exception dumps, passwords, CSRF tokens, authorization values, or secrets.
- [ ] Confirm the host/operator can correlate an incident using the request ID without exposing sensitive values to the browser.
- [ ] Set `display_errors=Off`, `display_startup_errors=Off`, `log_errors=On`, and an actionable production `error_reporting` level.

## Verification

- [ ] Run `composer test` and record the result.
- [ ] Run PHP syntax checks for `src/`, `public/`, and `tests/`.
- [ ] Run the DB-backed integration suite with explicitly designated test credentials and record skipped tests separately.
- [ ] Exercise representative 403, 404, 405, and 500 paths and confirm status codes and safe response bodies.
- [ ] Exercise overlapping dispatch-contract create/update attempts and confirm the repository transaction preserves the no-overlap invariant.
- [ ] Confirm backups and restores are covered by an operator-approved recovery procedure; treat migration rollback as a controlled operational action.
