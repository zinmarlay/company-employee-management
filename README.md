# Company Employee Management System

Internal, server-side rendered employee management system for a company with branches in multiple cities. The project is also an advanced Pure PHP learning project focused on professional object-oriented design, explicit dependencies, and clear application boundaries.

## Phase 01 status

Phase 01 establishes the project bootstrap, Composer metadata, PSR-4 autoloading, central configuration boundary, and public web root. It intentionally does not implement application features.

## Phase 02 status

Phase 02 establishes the Pure PHP HTTP and server-rendered presentation foundation. The request flow now has explicit Request, Router, Middleware Pipeline, Controller, ViewRenderer, Response, ResponseEmitter, and HTTP error boundaries.

The only application route is the setup route at `GET /`. Business routes and features remain deferred.

## Requirements

- PHP >= 8.3
- Composer
- Git
- A MySQL-capable local development environment for later phases

The current local development environment uses PHP 8.5.x. Verify the active PHP runtime with:

```sh
php -v
```

The database foundation requires PDO and `pdo_mysql` when database commands or database integration tests are used. `mbstring` remains planned for reliable Japanese and multibyte text handling, and `fileinfo` remains planned for safe employee-photo upload handling.

## Installation

From the project root:

```sh
composer validate
composer install
composer dump-autoload
composer test
```

Composer installs the minimal project dependencies and generates the PSR-4 autoloader. The project namespace `App\\` maps to `src/`.

PHPUnit is a development dependency used to verify the HTTP architecture. Tests focus on request/response behavior, routing, middleware, view escaping, error handling, and the complete setup request flow.

## Configuration

Application configuration is defined in `config/app.php` and may be
overridden by the process environment. At the web and migration entry points,
the project loads an optional `.env` file from the project root using
`vlucas/phpdotenv`. Existing environment variables supplied by the shell or
runtime take precedence over values in `.env`.

Create the local file from the safe template and set local database values:

```sh
cp .env.example .env
```

For the local XAMPP/MySQL setup, `.env` should contain values equivalent to:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Tokyo

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=company_employee_management
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

`.env` is ignored by Git. Do not add local credentials to the repository;
`.env.example` is the committed template.

## Local development server

Run the PHP built-in development server from the project root:

```sh
php -S localhost:8000 -t public
```

The built-in server is for local development only. The `public/` directory is the document root so that only the intended web entry point is exposed; source code, configuration, documentation, and runtime data remain outside the web-facing directory.

The Phase 01 temporary entry point can be opened at <http://localhost:8000/>. Stop the server with `Ctrl+C` after verification.

Phase 02 verification should also confirm that an unknown path returns `404`, a known path with an unsupported method returns `405` with an `Allow` header, and unexpected request-processing failures return a safe `500` response.

## Phase 01 intentionally does not implement

The following are deferred to later branches:

- Router, route definitions, controllers, middleware, and views
- Material Design UI pages
- MySQL or PDO database connections, repositories, migrations, and seeders
- Authentication, authorization, sessions, and CSRF protection
- Validation framework, business services, domain features, and DTOs
- Branch, department, employee, dispatch-company, contract, portfolio, skill, project, certification, user, search, pagination, and dashboard features

Future phases must preserve the front-controller and configuration boundaries established here and must introduce directories only when they have a real responsibility.

Phase 02 does not implement MySQL, PDO, repositories, authentication, authorization, sessions, CSRF, business services, employee features, dashboard data, file uploads, a complete Material Design interface, API endpoints, or SPA architecture.

## Phase 03 status

Phase 03 establishes the database foundation without coupling the existing HTTP application to MySQL. Database configuration is resolved through the bootstrap configuration boundary, `ConnectionFactory` creates PDO connections with explicit safe defaults, and versioned migrations are managed through the CLI boundary.

Phase 03 does not create business tables or implement repositories, services, authentication, authorization, or employee features. The web entry point remains usable without database credentials or a running MySQL server.

### Database configuration

The minimum runtime requirement is PHP >= 8.3. Phase 03 also requires the PDO extension and the `pdo_mysql` driver when a database connection is used. Verify the active runtime and available driver with:

```sh
php -v
php -m | grep -E 'PDO|pdo_mysql'
```

Database values are read at the configuration boundary. `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` must be explicitly configured before a connection is attempted; `DB_PASSWORD` may intentionally be an empty string. `DB_HOST`, `DB_PORT`, and `DB_CHARSET` default to `127.0.0.1`, `3306`, and `utf8mb4` respectively. Do not commit real credentials.

### Migration commands

Run migration commands from the project root. They use the configured environment and do not run automatically during web requests:

```sh
php bin/migrate status
php bin/migrate migrate
php bin/migrate rollback
```

The normal local setup flow is:

```sh
cp .env.example .env
# edit .env with local database credentials
php bin/migrate status
php bin/migrate migrate
php -S localhost:8000 -t public
```

Migration classes are deterministic `VersionYYYYMMDDHHMMSSName` classes implementing `MigrationInterface`, and applied migrations are tracked in `schema_migrations`. The current domain migrations create companies, branches, departments, and employees in dependency order. They are applied only when `php bin/migrate migrate` is run; the HTTP application does not run them automatically.

### Database tests

Unit tests do not require MySQL. Integration tests run only when `APP_ENV=test`
and all `DB_TEST_*` variables are explicitly supplied. Use a separate test
database, for example:

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

The test database name must end in `_test` and must be different from the
normal application database. Test configuration is passed explicitly and
never falls back to `DB_DATABASE`. If the required values are not present,
integration tests are skipped or fail safely rather than guessing a database
target.

The Phase 04 integration coverage checks the company/branch/department/employee schema, scoped uniqueness, required relationships, optional department assignment, cross-branch assignment rejection, deletion restrictions, check constraints, migration idempotency, and reverse-order rollback. The configured MySQL or MariaDB version must enforce `CHECK` constraints; use the integration suite to verify status and employee-type values are rejected by the actual test engine.

The Phase 04 schema decisions are documented in [docs/specs/04-domain-schema.md](docs/specs/04-domain-schema.md). They do not add application CRUD, repositories, authentication, or UI behavior.

## Phase 05a status

Phase 05a adds the shared Material Design-inspired administration shell for
the existing server-rendered application. The layout, responsive CSS, shared
view partials, employee list, employee forms, employee detail, deactivation,
and root welcome page use the same presentation system without changing
employee business rules or database behavior.

The sidebar links only to implemented routes. Future organization and
dispatch areas are shown as disabled placeholders until their respective
phases are implemented. See
[docs/specs/05a-material-ui-foundation.md](docs/specs/05a-material-ui-foundation.md)
for the UI architecture and accessibility decisions.
