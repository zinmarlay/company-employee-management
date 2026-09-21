# Company Employee Management System

Internal, server-side rendered employee management system for a company with branches in multiple cities. The project is also an advanced Pure PHP learning project focused on professional object-oriented design, explicit dependencies, and clear application boundaries.

## Phase 01 status

Phase 01 establishes the project bootstrap, Composer metadata, PSR-4 autoloading, central configuration boundary, and public web root. It intentionally does not implement application features.

## Requirements

- PHP >= 8.3
- Composer
- Git
- A MySQL-capable local development environment for later phases

The current local development environment uses PHP 8.5.x. Verify the active PHP runtime with:

```sh
php -v
```

Planned PHP extensions include PDO and `pdo_mysql` for the later database phase, `mbstring` for reliable Japanese and multibyte text handling, and `fileinfo` for safe employee-photo upload handling. Phase 01 does not connect to MySQL.

## Installation

From the project root:

```sh
composer validate
composer install
composer dump-autoload
```

Composer installs the minimal project dependencies and generates the PSR-4 autoloader. The project namespace `App\\` maps to `src/`.

## Configuration

Phase 01 centralizes application configuration in `config/app.php`. Environment variables may override the safe local defaults through the bootstrap configuration boundary.

No dotenv package is installed in Phase 01. `.env.example` documents the expected variable names and safe local values, but PHP does not load `.env` automatically. For local development, either export variables in the shell before starting PHP or configure them in the web server environment. For example:

```sh
export APP_ENV=local
export APP_DEBUG=true
export APP_URL=http://localhost:8000
export APP_TIMEZONE=Asia/Tokyo
```

Do not add real credentials to the repository. Database variables are documented for a later phase only.

## Local development server

Run the PHP built-in development server from the project root:

```sh
php -S localhost:8000 -t public
```

The built-in server is for local development only. The `public/` directory is the document root so that only the intended web entry point is exposed; source code, configuration, documentation, and runtime data remain outside the web-facing directory.

The Phase 01 temporary entry point can be opened at <http://localhost:8000/>. Stop the server with `Ctrl+C` after verification.

## Phase 01 intentionally does not implement

The following are deferred to later branches:

- Router, route definitions, controllers, middleware, and views
- Material Design UI pages
- MySQL or PDO database connections, repositories, migrations, and seeders
- Authentication, authorization, sessions, and CSRF protection
- Validation framework, business services, domain features, and DTOs
- Branch, department, employee, dispatch-company, contract, portfolio, skill, project, certification, user, search, pagination, and dashboard features

Future phases must preserve the front-controller and configuration boundaries established here and must introduce directories only when they have a real responsibility.
