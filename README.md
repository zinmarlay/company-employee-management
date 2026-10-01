# Company Employee Management System

English | [日本語](README.ja.md)

## Overview

Company Employee Management System is a server-rendered internal application for managing employees, organization data, dispatch contracts, employee portfolios, and system users. It is a deliberately small Pure PHP application: the request flow, security boundaries, validation, and persistence rules remain explicit and easy to inspect.

The project is suitable for local development, technical discussion, and controlled production deployment. It is not a public registration service, API platform, or SPA.

## Project purpose

The application provides one place to maintain employee and organizational records while preserving history through status and archive transitions. It also demonstrates framework-free object-oriented design with constructor injection, DTOs, application services, repository interfaces, and PDO persistence.

## Key features

- Employee creation, editing, deactivation, detail views, and historical records.
- Employee search by keyword, branch, department, employment type, and status, with allowlisted sorting and pagination.
- Company, branch, and department management with lifecycle/status handling.
- Dispatch-company management and non-overlapping dispatch-contract history.
- Contract expiration classification: normal, expiring within 30 days, expiring within 7 days, and expired.
- Employee skills, projects, and certifications with archive workflows.
- ADMIN and read-only USER accounts, authentication, reactivation, and lifecycle protection.
- English/Japanese presentation localization.

## Technology stack

- PHP 8.3 or later
- MySQL or MariaDB through PDO and `pdo_mysql`
- Composer and PSR-4 autoloading
- Server-side rendered PHP views with HTML5/CSS and minimal same-origin JavaScript
- PHPUnit
- `mbstring` (required)

The application has no full-stack framework, ORM, React/Vue SPA, queue, Redis, or upload subsystem. `fileinfo` is not currently required because there is no file-upload feature.

## Architecture

The application uses explicit layers and constructor injection:

```text
Browser
  -> public/index.php              Front controller
  -> ApplicationBootstrap           Composition root / dependency graph
  -> Request
  -> HttpKernel
  -> MiddlewarePipeline             Session, locale, authentication, authorization, CSRF
  -> Router
  -> Controller
  -> Application Service
  -> Repository
  -> PDO / MySQL or MariaDB
```

The response path is:

```text
Controller -> ViewRenderer -> Response -> ResponseEmitter -> Browser
```

- `public/index.php` loads the environment, boots the application, creates the request, and emits a safe bootstrap-failure response.
- `ApplicationBootstrap` constructs configuration, repositories, services, controllers, middleware, and the kernel.
- `Request` and `Response` are explicit HTTP boundaries. Each request receives a non-secret correlation ID.
- `Router` maps method/path combinations and records route access metadata.
- Middleware establishes the session, locale, authenticated user, authorization boundary, and POST CSRF requirement.
- Controllers translate HTTP input into service calls and choose views or redirects.
- Application services orchestrate validation and business workflows; DTOs carry validated values.
- Repositories isolate PDO queries and transaction behavior.
- `ViewRenderer` composes escaped server-rendered pages; `ResponseEmitter` writes status and headers, including repeated `Set-Cookie` values.

## Directory structure

```text
config/                 Application defaults
database/migrations/    Versioned MySQL/MariaDB schema migrations
docs/                   Deployment and development operations notes
public/                 Web document root and public assets
resources/lang/         English and Japanese translations
resources/views/        Server-rendered PHP templates
routes/                 Route registration and access metadata
src/Bootstrap/           Configuration and composition root
src/Domain/              Repository contracts and domain exceptions
src/Application/         DTOs, validators, services, and workflows
src/Infrastructure/      PDO repository implementations
src/Http/                HTTP boundaries, routing, middleware, views, controllers
src/Security/            Sessions, authentication context, authentication, CSRF
src/Logging/             Structured error_log logger and safe context handling
tests/                   Unit, feature/HTTP, and gated DB integration tests
bin/                     Migration, seed, and system-user CLI commands
```

## Database and domain overview

```text
Company 1---* Branch 1---* Department
                         |
                         *---* Employee

Employee *---* Skill       (employee_skills)
Employee 1---* Project     (employee_projects)
Employee 1---* Certification (employee_certifications)
Employee *---* Dispatch Contract *---1 Dispatch Company
System User
Employee Code Sequence
```

The schema uses foreign keys, unique constraints, status checks, date checks, and UTF-8 `utf8mb4` configuration. Employee, branch, department, dispatch-company, portfolio, and system-user records are retained through status or archive transitions rather than casually deleted. Employee codes and important organization identities remain stable; dispatch contracts preserve history and cannot overlap for the same employee.

## Authentication and authorization

The login flow normalizes the email for lookup, verifies a password hash, uses a dummy hash for missing/inactive accounts, regenerates the session ID after successful authentication, rotates the CSRF token, and records the successful login. Inactive accounts cannot authenticate, and an existing session is rechecked against the database on each request.

Routes carry access metadata:

- `public`: login pages and login submission.
- `authenticated`: logout.
- `authenticated_read`: signed-in read-only access.
- `admin`: ADMIN-only create, update, deactivate, archive, activate, and system-user operations.

Authorization is enforced in middleware and supported by service/repository rules. A USER cannot reach ADMIN routes by guessing URLs.

## Security measures implemented by the application

- PDO prepared statements and allowlisted SQL sort/direction expressions.
- Context-aware HTML escaping and JSON hex escaping for employee-search metadata.
- CSRF validation on every registered POST route before controller writes.
- Password hashing, password verification, dummy-hash timing behavior, and session ID regeneration.
- Strict, cookie-only, `HttpOnly`, `SameSite=Lax` sessions; production cookies require direct trusted HTTPS.
- Production configuration fails closed for debug mode, non-HTTPS `APP_URL`, missing explicit database configuration, and example database credentials.
- Direct trusted HTTPS is supported in production. `X-Forwarded-Proto`, `Forwarded`, and similar client headers are not trusted; reverse-proxy trust is deferred.
- HTML responses include CSP with `script-src 'self'` and `frame-ancestors 'none'`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, and a referrer policy.
- Private HTML responses use `Cache-Control: no-store, private` and `Pragma: no-cache` where applicable.
- Unexpected errors and bootstrap failures return generic browser responses and write safe metadata to PHP's configured `error_log` pipeline.
- Logging is allowlist-first and includes a non-secret request/correlation ID without logging passwords, identifiers, bodies, cookies, sessions, CSRF tokens, authorization values, or secrets.
- Search keywords and department descriptions have explicit server-side bounds.
- Dispatch writes recheck overlap inside one repository transaction while holding a deterministic employee-row lock.

These are implemented controls, not a claim of absolute security. TLS, least-privilege database access, PHP runtime settings, monitoring, backups, and operational procedures remain deployment responsibilities.

## Feature areas

### Employees and search

Employees can be created, edited, viewed, and deactivated. Deactivation preserves historical references. The directory supports keyword search, branch and department filters, employment type and status filters, allowlisted sort fields/directions, and bounded pagination. Department filtering has a same-origin enhancement at [`public/assets/js/employee-search.js`](public/assets/js/employee-search.js); the server remains authoritative.

### Branches and departments

Branches belong to companies and departments belong to branches. Inactive organization records remain readable for historical employee and contract references. Parent relationships and immutable identity fields are validated by application and database constraints.

### Dispatch companies and contracts

Dispatch companies have active/inactive lifecycle state. Contracts link dispatched employees to dispatch companies with validated dates. Renewal creates history, expiration is classified against UTC reference dates, and the repository transaction prevents overlapping contracts for one employee.

### Employee portfolio

Skills use a catalog and employee-specific proficiency/experience records. Projects support optional end dates and archive status. Certifications preserve issuing organization and obtained-date identity, optional expiration, and archive status. Portfolio child records are ownership-checked against their employee parent.

### System users

System users have `ADMIN` or `USER` roles and active/inactive status. ADMIN-only screens manage accounts, while the service protects the acting administrator and prevents removal of the last active ADMIN. Existing password hashes are preserved when no replacement password is submitted during an edit.

### Localization

The application supports English and Japanese translation resources. Locale selection is available through the UI and is persisted in an `app_locale` cookie. Stored identifiers remain stable while organization display labels are localized.

## Installation

Requirements: PHP 8.3+, Composer, MySQL/MariaDB, PDO with `pdo_mysql`, and `mbstring`.

```sh
git clone <repository-url>
cd company-employee-management
composer install
cp .env.example .env
```

Edit `.env` with local database values. Do not commit `.env` or put real credentials in documentation.

## Environment configuration

The application reads `config/app.php` defaults and optional `.env` values through `vlucas/phpdotenv`. Existing process environment variables take precedence.

### Local development

Use `APP_ENV=local`, `APP_DEBUG=true`, a local HTTP `APP_URL`, and local `DB_*` values. Development seeding is allowed only for `local` and refuses test-like database names.

### Test environment

Use `APP_ENV=test` with a separate database described by `DB_TEST_*`. Integration tests never guess a database and skip unless the explicit test environment is configured.

### Production

Set `APP_ENV=production`, `APP_DEBUG=false`, and an `https://` `APP_URL`. Production database host, port, database, username, password, and charset must be explicitly supplied; local defaults and example `change-me` credentials are rejected. Production supports direct trusted HTTPS only and does not trust forwarded protocol headers. Configure PHP with `display_errors=Off`, `display_startup_errors=Off`, `log_errors=On`, an actionable `error_reporting` level, and a host-managed `error_log` destination.

See [`docs/deployment-checklist.md`](docs/deployment-checklist.md). The committed template is [`.env.example`](.env.example).

## Database setup and migrations

Create a dedicated database and least-privilege credentials, then configure `.env`:

```sh
php bin/migrate status
php bin/migrate migrate
```

Migrations run from the CLI, not web requests. The migration set covers companies, branches, departments, employees, dispatch companies/contracts, employee-code allocation, portfolios, and system users. Review migration status and backup/recovery procedures before production changes.

## Development seeding

After migrations in a local database:

```sh
php bin/seed
```

The seeder is transactional and idempotent. It creates or reuses deterministic sample organization, employee, dispatch-company, and dispatch-contract data, and never targets `DB_TEST_*`. See [`docs/development-seeding.md`](docs/development-seeding.md).

## Running locally

```sh
php -S localhost:8000 -t public
```

Open <http://localhost:8000/>. Create an administrator through the protected CLI command when needed:

```sh
php bin/system-user create-admin
```

The command prompts for the administrator name, email, and password; it has no default password.

## Testing

Normal suite:

```sh
composer test
```

Database-backed example using a separate test database:

```sh
APP_ENV=test \
DB_TEST_HOST=127.0.0.1 \
DB_TEST_PORT=3306 \
DB_TEST_DATABASE=company_employee_management_test \
DB_TEST_USERNAME=root \
DB_TEST_PASSWORD= \
DB_TEST_CHARSET=utf8mb4 \
composer test
```

Without all `DB_TEST_*` values and an available test database, integration tests skip safely. Never point them at development or production.

## Production considerations

Use `/public` as the web-server document root, keep `.env` and source files outside the web root, install production Composer dependencies, apply migrations deliberately, configure direct HTTPS and secure sessions, and verify security headers, login/logout, CSRF rejection, authorization boundaries, localization, database access, and safe error responses after deployment. PHP log rotation, retention, permissions, monitoring, backups, and restore procedures belong to the host/operator.

## Design principles

- Keep infrastructure details behind explicit interfaces and repositories.
- Prefer constructor injection and small DTOs over hidden global dependencies.
- Keep validation and authorization at the server boundary.
- Preserve historical data through lifecycle/archive states.
- Let database constraints reinforce application invariants.
- Make security decisions explicit and testable.
- Favor readable, framework-free code over accidental abstraction.
- Document operational assumptions without claiming more than the code implements.
