# Phase 01: Project Setup Specification

## 1. Document purpose

This document defines Phase 01 of the Company Employee Management System. It translates the master project overview into a concrete project-bootstrap specification for a professional Pure PHP application.

This phase establishes the repository, runtime, Composer, PSR-4, configuration, public entry point, and development conventions required by later phases. It does not implement business features.

The implementation branch for this phase is the official branch `feature/project-setup`. Future specifications must use the branch name explicitly assigned to them; they must not invent or infer a separate Git branch naming convention.

## 2. Phase objective

At the end of Phase 01, the project must have a repeatable and understandable foundation on which later phases can add routing, views, database access, authentication, domain logic, and feature modules.

The setup must demonstrate:

- A deliberate Pure PHP project structure
- A PHP >= 8.3 runtime baseline
- Composer dependency management
- PSR-4 autoloading
- A single public web entry point
- Environment-aware configuration boundaries
- Separation between public web files, application source, views, configuration, tests, and runtime data
- Git-safe handling of secrets and generated files

The setup should be small enough to understand completely. No framework-shaped scaffolding should be introduced merely for appearance.

## 3. Relationship to the master specification

Phase 01 must follow these master requirements:

- Use PHP >= 8.3, Pure PHP, Composer, and PSR-4. The master specification's PHP 8.x direction is made concrete for this project by the minimum defined in Section 5.
- Keep the backend and frontend in one server-side rendered PHP application.
- Do not use Laravel, Symfony, CodeIgniter, React, Vue, or a PHP ORM.
- Preserve the intended dependency direction and avoid hidden global state.
- Keep configuration and secrets outside committed application code.
- Use a public front controller as the future request entry point.
- Develop incrementally, one feature branch at a time.

The architecture introduced here is a foundation, not a commitment to create every possible abstraction immediately. Later features must add an abstraction only when its responsibility and benefit are clear.

## 4. Phase scope

### 4.1 Included

Phase 01 includes:

1. Repository and directory conventions
2. PHP runtime baseline and local environment documentation
3. Composer project metadata
4. PSR-4 namespace mapping
5. Public web root and front-controller boundary
6. Application bootstrap boundary
7. Environment-aware configuration contract
8. Development and production configuration expectations
9. Git ignore and secret-handling rules
10. Initial verification commands and setup acceptance criteria
11. Documentation of the setup workflow for a new developer

### 4.2 Explicitly excluded

The following belong to later phases and must not be implemented as part of Phase 01:

- A router, route matching, or route definitions
- Controllers or controller actions
- MVC feature modules
- PHP view templates or reusable view components
- Database connections, database schema, migrations, seed data, PDO connection creation, or PDO repositories
- Authentication, sessions, users, roles, or authorization
- CSRF protection and login workflows
- Business services or domain services
- Branch, department, employee, contract, portfolio, skill, project, certification, or dispatch-company features
- Domain rules, DTOs, or repositories that have no setup purpose
- File-upload handling
- Dashboard pages or Material Design UI pages
- API endpoints or SPA behavior
- Production deployment automation
- Frameworks, ORM libraries, or frontend frameworks

Phase 01 may define extension points for these concerns, but it must not pretend that an extension point is an implemented feature.

## 5. Runtime baseline

### 5.1 PHP

- The minimum supported PHP version is **PHP >= 8.3**.
- This provides a modern, stable PHP baseline while allowing the project to study modern PHP language features.
- Composer will later express this minimum requirement in the project's platform requirements.
- The implementation must still verify the actual installed PHP version during setup checks; a Composer requirement alone is not sufficient.
- The project must document the exact PHP minor version used for development and CI.
- The installed version used for local verification should satisfy PHP >= 8.3 and should match the documented development version where possible.
- PHP extensions required by the project must be documented rather than assumed.

At minimum, the setup documentation must identify the extensions needed for the planned application, including PDO and the MySQL PDO driver. Feature specifications may add extension requirements when they introduce functionality that needs them.

### 5.2 Database environment

Phase 01 does not connect to MySQL or define database tables. It must, however, document that later phases require:

- MySQL
- PDO MySQL support
- A project-specific database and account for local development
- Credentials supplied through environment-specific configuration

Database setup belongs to a later database phase and must not be hidden inside the initial bootstrap.

### 5.3 Web server

The application must be served with a document root pointing to the `public/` directory, not the repository root. This prevents source files, configuration, documentation, and runtime data from being directly served by the web server.

The setup documentation must explain the expected local configuration for the selected development server, including the fact that all application requests will eventually enter through `public/index.php`.

## 6. Project structure

Phase 01 must distinguish between the architecture that the project is intended to grow into and the small set of files and directories that the setup phase actually needs. The project must not create a large collection of empty directories merely to imitate a framework architecture.

### 6.1 Future conceptual architecture

The following is a conceptual map for later phases. It documents responsibilities; it is not a Phase 01 creation checklist.

```text
src/
├── Application/
│   ├── Services/
│   └── DTOs/                  # Only where a DTO provides real value
├── Domain/
│   ├── Models/
│   └── Rules/                 # Only where domain rules require them
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Routing/
├── Infrastructure/
│   ├── Repositories/
│   └── Persistence/
└── Support/

resources/views/               # Server-rendered PHP views
database/
├── migrations/
└── seeders/
```

The conceptual names describe likely future boundaries:

- `Application/Services/` will coordinate use cases when feature work begins.
- `Domain/Models/` and domain rules will represent business concepts only when that improves clarity or protects an invariant.
- `Http/Controllers/`, `Http/Middleware/`, and `Http/Routing/` will be introduced with the HTTP and presentation phase, not scaffolded in advance.
- `Infrastructure/Repositories/` and persistence code will be introduced when database work begins.
- `resources/views/` will be introduced when server-rendered pages are implemented.
- `database/migrations/` and `database/seeders/` will be introduced by the database phase.
- `Support/` may be introduced only when a genuine cross-cutting setup utility belongs there; it must not become a miscellaneous dumping ground.

The exact class placement may be refined by later specifications, but later changes must preserve the public/private boundary and the dependency direction.

### 6.2 Phase 01 initial physical structure

The initial physical structure must remain minimal and must contain only directories with a current Phase 01 responsibility or files that are required for setup verification and documentation.

```text
company-employee-management/
├── public/
│   └── index.php
├── src/
├── config/
├── storage/
│   └── logs/
├── tests/
├── docs/
│   ├── prompts/
│   └── specs/
├── .env.example
├── .gitignore
├── composer.json
├── composer.lock
└── README.md
```

Physical-structure decisions for this phase:

- `public/` is required because the front-controller boundary is part of Phase 01. `public/index.php` is the only intended web entry point.
- `src/` is required because Composer's PSR-4 mapping and the setup bootstrap/autoload verification need a namespaced source location. It must contain only setup-owned files at this stage; feature subdirectories are deferred until needed.
- `config/` is required because Phase 01 establishes an explicit configuration boundary. It must contain only non-secret setup configuration or configuration-loading files.
- `storage/logs/` is created only if the Phase 01 bootstrap or setup verification writes local runtime logs. If logging is not yet implemented, the directory should be deferred rather than created empty; the later logging phase will create it when it has a real responsibility.
- `tests/` is created only when Phase 01 includes an actual setup verification test. Test-purpose subdirectories such as `Unit/`, `Integration/`, and `Feature/` are deferred until a test of that type exists.
- `docs/prompts/` and `docs/specs/` are retained because project documentation and phase specifications are already part of the repository's current responsibility.
- `.env.example`, `.gitignore`, `composer.json`, `composer.lock`, and `README.md` are setup artifacts and belong in the initial structure. The lock file is tracked after dependencies are resolved; Composer must not be run as part of writing this specification.
- `resources/`, `database/`, `database/migrations/`, `database/seeders/`, `public/assets/`, `storage/uploads/`, and future `src/` subdirectories are deferred until a later phase has a real file or responsibility for them.

### 6.3 Structure rules

- `public/` is the only web-server document root.
- `public/index.php` is the future front controller and must remain thin.
- `src/` contains namespaced application code and is loaded through Composer.
- `config/` contains non-secret configuration definitions or configuration-loading code, not credentials.
- Runtime data must not be publicly served or committed when it contains local data.
- Documentation contains project documentation and specifications, not runtime code.
- A directory is created when the current phase has a real responsibility or file that belongs there. Later feature branches introduce their own directories when needed.

Phase 01 must not create empty `Application`, `Domain`, `Http`, `Infrastructure`, `Controllers`, `Services`, `Repositories`, `Middleware`, `Views`, `database/migrations`, or `database/seeders` directories solely to make the tree look complete.

## 7. Composer specification

### 7.1 Composer responsibilities

Composer is the project's dependency manager and PSR-4 autoloader. It must not be used to install a full-stack framework, ORM, frontend framework, or a package that duplicates a small concept the project is intended to learn directly.

The project must commit the dependency lock file so that development and CI resolve the same dependency versions.

### 7.2 Composer metadata requirements

The Composer project definition must specify:

- A stable project package name suitable for a private internal application
- A short project description
- The application PHP requirement
- PSR-4 mapping from the project namespace to `src/`
- Appropriate stability settings only when genuinely required
- Production and development dependencies separated correctly
- Composer scripts for the setup checks that are actually available

The project namespace should be consistent and vendor-neutral, for example `App\\`. The final namespace must be selected once and used consistently in class names, file paths, tests, and documentation.

### 7.3 Dependency policy

Initial dependencies should be minimal. A package may be added only when it provides a clear benefit that justifies its maintenance and learning cost.

At Phase 01:

- Composer itself and its generated autoloader are required.
- No framework or ORM may be added.
- No database abstraction package may be added in place of PDO.
- Test, static-analysis, or coding-standard packages may be introduced only if this phase also documents and verifies their commands.
- A dotenv package is optional and requires an explicit decision; the project must not add one by default without deciding whether its behavior is needed and understood.

## 8. PSR-4 and namespace rules

The application namespace must map one-to-one to the `src/` directory through Composer PSR-4 configuration.

Rules:

- Namespace segments use the same case as their directory names.
- Class names use StudlyCaps.
- One primary class, interface, trait, or enum belongs in one appropriately named file.
- A class's file path must be predictable from its fully qualified class name.
- Tests should use a deliberate test namespace or documented convention consistent with the chosen test runner.
- Global functions and procedural helpers must not become a substitute for application classes.

The first verification of PSR-4 must use a small namespaced class or equivalent autoload check. The check should prove that Composer can load project code without manually requiring individual source files.

## 9. Front-controller boundary

### 9.1 Required responsibility

`public/index.php` is the single public entry point for application requests. Its Phase 01 responsibility is limited to bootstrapping the application boundary.

It may:

- Define the minimal safe runtime context needed before application boot
- Require Composer's generated autoloader
- Load the application bootstrap
- Pass the request into a future application entry point

It must not:

- Contain SQL
- Read or write employee data
- Perform authentication or authorization decisions
- Contain route tables
- Render feature-specific HTML
- Instantiate unrelated dependencies throughout the file
- Become a second application container or service locator

### 9.2 Phase 01 behavior

Because routing and feature handling are out of scope, the initial front controller may terminate with a deliberately controlled setup response or delegate to a minimal bootstrap placeholder. That temporary behavior must be clearly marked as incomplete and must be replaced by the routing phase.

The important Phase 01 outcome is the boundary: direct web requests enter one known file, and source/configuration directories are not web-facing.

## 10. Bootstrap and composition boundary

Phase 01 must define where application startup is coordinated without implementing the full application container.

The bootstrap boundary should be responsible for:

- Loading Composer's autoloader
- Loading environment-aware configuration
- Establishing the application environment and timezone configuration
- Preparing a small composition root for future dependency construction
- Returning or invoking a single application entry point

The bootstrap must not create database connections, authenticate users, query repositories, or instantiate every future service eagerly.

Dependency construction should remain explicit. Avoid static global registries, facades, hidden singleton state, and calls to `new` scattered across controllers or domain objects.

## 11. Configuration specification

### 11.1 Configuration goals

Configuration must be separate from business logic and must support different local, test, and production environments. Configuration values must be available to the application through an explicit configuration boundary rather than through arbitrary reads of superglobals throughout the codebase.

### 11.2 Required configuration categories

Phase 01 must establish names or documented placeholders for:

- Application name
- Application environment, such as local, test, or production
- Debug mode
- Base URL or trusted application URL settings
- Application timezone
- Logging destination and minimum level
- Database settings reserved for a later phase
- Session and security settings reserved for later phases
- Upload/storage settings reserved for later phases

The configuration shape may be represented by arrays, immutable value objects, or another small explicit design. The choice must be justified by the project's learning goals and must not become a framework imitation.

### 11.3 Environment and secrets

- `.env.example` documents required variable names and safe placeholder values.
- Real `.env` files, local secrets, credentials, and runtime logs must be ignored by Git.
- Secrets must not appear in `config/`, source files, documentation examples, or committed test fixtures.
- Production configuration must be supplied by the deployment environment or a protected secret mechanism.
- Configuration loading must fail clearly for required values that are absent or invalid.
- Debug output and stack traces must be restricted to development/test environments.

The project must document whether values are read directly from process environment variables or through a dotenv loader. No implicit precedence rules may be left unexplained.

### 11.4 Timezone

The application timezone must be configured in one place. The contract-alert feature will later rely on this setting for consistent date classification, so feature code must not choose its own timezone.

Phase 01 does not finalize the business definition of “today” for contract alerts; it only establishes the configuration boundary needed to make that later decision explicit.

## 12. Error and runtime behavior for setup

Phase 01 must establish a safe distinction between development and production behavior, even if the complete exception handler is deferred.

Expected direction:

- Development may show actionable diagnostic information to the developer.
- Production must not show stack traces, filesystem paths, SQL statements, credentials, or internal configuration.
- Bootstrap failures must be logged or surfaced through a controlled failure path appropriate to the environment.
- Error reporting settings must be centralized and documented.

The full exception hierarchy, HTTP error responses, logging implementation, and user-facing error pages belong to the later HTTP/error-handling phase.

## 13. Git and generated-file rules

The setup must include a `.gitignore` that protects local and generated data without hiding source files accidentally.

It should ignore, as applicable:

- Environment files containing real secrets
- Composer's `vendor/` directory
- Runtime logs
- Runtime uploads and generated storage data
- Local IDE metadata
- Operating-system metadata
- Test or coverage output
- Local temporary files

The following should remain tracked when present:

- `composer.json`
- `composer.lock`
- `.env.example`
- Source code
- Documentation and specifications
- Database migrations and seed definitions once introduced
- Deliberately reviewed public assets

The implementation must not use destructive Git commands to create the setup. Existing user changes must be preserved.

## 14. Documentation requirements

The project README or a setup document must explain:

1. Supported PHP version and required extensions
2. Required local tools, including Composer and a MySQL-capable development environment for later phases
3. How to obtain dependencies
4. How to create local configuration from the example
5. How to configure the web server document root to `public/`
6. How to run the Phase 01 verification commands
7. Which behavior is intentionally deferred to later phases
8. Which files and directories must never contain secrets

The documentation should be written for a new developer who understands basic PHP but needs to learn why the structure exists.

## 15. Quality and verification requirements

The Phase 01 implementation must be verifiable without requiring application data.

### 15.1 Required checks

The implementation must define and document checks equivalent to:

- PHP version check
- Composer dependency installation check
- Composer autoload generation check
- PSR-4 class loading check
- Configuration loading check for a safe local/test environment
- Front-controller request check through the configured web root
- Git cleanliness check for secrets and accidental generated files

The exact commands may vary by operating system, but a new developer must be able to run the same logical checks consistently.

### 15.2 Failure expectations

Verification must fail clearly when:

- The PHP version is unsupported
- Required Composer metadata is invalid
- Autoload generation fails
- A namespace-to-path mapping is incorrect
- Required non-secret configuration is missing
- The web server points at the repository root instead of `public/`

No check should require a real production secret, production database, or external service during Phase 01.

## 16. Acceptance criteria

Phase 01 is complete only when all of the following are true:

1. The repository has the minimal Phase 01 physical structure, or any deviation is explicitly justified by a current responsibility.
2. The application has a Composer project definition and a committed lock file after dependencies are resolved.
3. The project declares PHP >= 8.3 as its Composer platform requirement when Composer metadata is implemented.
4. The actual installed PHP version is verified and satisfies PHP >= 8.3.
5. The chosen project namespace maps correctly to `src/` through PSR-4.
6. A namespaced project class can be loaded through Composer without manual source-file includes.
7. The only intended web entry point is `public/index.php`.
8. The documented local web-server configuration uses `public/` as its document root.
9. Configuration is accessed through an explicit boundary and does not scatter environment reads throughout application code.
10. `.env.example` documents required setup variables without containing real secrets.
11. Real environment files, vendor dependencies, and runtime output are protected by `.gitignore`.
12. The setup works without implementing a router, controllers, database access, authentication, authorization, sessions, CSRF protection, business services, or feature modules.
13. Development and production diagnostic behavior are distinguished at the bootstrap boundary.
14. Setup and verification steps are documented for a new developer.
15. No prohibited framework, ORM, SPA library, or unnecessary abstraction has been introduced.
16. No large collection of empty future-architecture directories has been created merely for scaffolding.
17. The implementation changes only the files needed for Phase 01 and does not alter the master specification without an explicit architectural reason.

## 17. Later-phase handoff

Phase 01 should leave clear seams for the next phases:

### Phase 02: HTTP and presentation foundation

Expected additions include custom routing, request/response handling, controllers, middleware structure, exception handling, PHP views, reusable view components, and shared layout.

### Phase 03: Database foundation

Expected additions include MySQL configuration activation, PDO construction, migrations, transaction handling, and persistence conventions.

### Later feature phases

Authentication, authorization, domain models, services, repositories, validation, and employee-management features must build on the boundaries established here rather than bypassing the front controller or configuration layer.

Phase 01 must not preempt these later decisions. It should make them easier to implement and explain.

## 18. Non-negotiable rules for Phase 01

1. Do not implement application features in the setup phase.
2. Do not expose the repository root as the web document root.
3. Do not place secrets in tracked files.
4. Do not use a framework, ORM, SPA library, or hidden global service locator.
5. Do not put business logic, SQL, or feature HTML in the front controller.
6. Do not create abstractions without a clear setup responsibility.
7. Do not make later features depend on direct environment reads or hard-coded paths.
8. Do not treat a successful Composer install as proof that the architecture is correct; verify the public boundary, namespace mapping, and configuration behavior as well.
