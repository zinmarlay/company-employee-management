# Phase 03: Database Foundation Specification

## 1. Document purpose

This document defines Phase 03 of the Company Employee Management System. It specifies the database infrastructure that later business features will use.

This is a specification only. It does not implement PHP application code, create database classes or directories, execute migrations, connect to MySQL, or modify the existing Phase 01 or Phase 02 implementation.

The official implementation branch for this phase is:

`feature/database-foundation`

Future specifications must use an explicitly assigned branch name and must not invent a separate Git branch naming convention.

## 2. Phase objective

Phase 03 must establish a small, explicit, testable database boundary for a Pure PHP application:

```text
Environment
    ↓
Configuration
    ↓
DatabaseConfiguration
    ↓
ConnectionFactory
    ↓
PDO
    ↓
Database infrastructure
    ↓
Future repositories
    ↓
Future application services
```

The phase should teach how PHP interacts with a relational database without hiding SQL, PDO, prepared statements, transactions, or schema evolution behind an ORM or framework.

## 3. Relationship to previous phases

Phase 03 builds on the existing project foundation:

- PHP remains `>= 8.3` with strict typing where appropriate.
- Composer and PSR-4 remain the dependency and autoloading mechanisms.
- `App\\` continues to map to `src/`.
- `public/` remains the only web-server document root.
- Phase 02's HTTP flow remains independent from database implementation details.
- `ApplicationBootstrap` remains an explicit composition root.
- `Configuration` remains responsible for describing configuration, not opening connections.
- `Request`, `Response`, `Router`, `MiddlewarePipeline`, `ViewRenderer`, and `ResponseEmitter` must not become database-aware.

The Phase 02 setup route does not require database data. Therefore the normal HTTP bootstrap must not open a MySQL connection merely because database infrastructure exists.

## 4. Phase scope

### 4.1 Included

Phase 03 may establish:

- Database configuration values and environment-variable rules
- An immutable database-configuration value object
- A small PDO connection factory
- Safe PDO attributes and native prepared-statement defaults
- Database connection failure behavior
- A minimal transaction boundary convention
- Migration contracts and discovery
- Migration ordering and schema-version tracking
- A small migration CLI entry point
- Rollback behavior if it can be implemented honestly within MySQL's DDL limitations
- Unit tests for configuration, DSN construction, and migration discovery
- Isolated MySQL integration-test infrastructure
- Database setup, migration, testing, and safety documentation

### 4.2 Explicitly excluded

Phase 03 must not implement:

- Authentication, login, logout, authenticated sessions, users, or roles
- Authorization, policies, or CSRF protection
- Branch, department, employee, dispatch-company, contract, portfolio, skill, project, certification, or dashboard features
- Business CRUD, search, filtering, sorting, or pagination
- Repositories, models, entities, application services, or generic repository frameworks
- Database transactions for business use cases that do not yet exist
- File uploads
- API endpoints or SPA architecture
- A complete Material Design interface
- An ORM, Active Record layer, query builder, or framework database abstraction

The only schema object justified in this phase is infrastructure needed to prove migration tracking, such as `schema_migrations`. No employee-management tables should be created.

## 5. Database technology

### 5.1 MySQL and PDO

The project uses MySQL through PHP's PDO extension and the PDO MySQL driver.

PDO is appropriate for this learning project because it provides:

- A standard PHP database API
- Prepared statements and bound values
- Exception-based error handling
- Transaction primitives
- A clear boundary between PHP code and SQL
- Testable dependency injection through a `PDO` object

The project must use the `pdo_mysql` driver explicitly. It must not use an ORM or database abstraction library that hides SQL and PDO fundamentals.

### 5.2 Concepts this phase must make clear

- **PDO** is the connection object and database API used by PHP.
- **PDOStatement** represents a prepared SQL statement and its execution/result behavior.
- **Prepared statements** separate SQL structure from dynamic values and reduce SQL-injection risk when used correctly.
- **Transactions** group related database operations into an all-or-nothing unit where the storage engine supports that guarantee.
- **SQL** remains an explicit language owned by the repository or migration that needs it.
- **Repositories** will later coordinate persistence queries for a business concept; they are not part of this infrastructure-only phase.

## 6. Database configuration

### 6.1 Required settings

The database configuration boundary should support these values:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_CHARSET`

The configuration may also define a fixed driver value of `mysql`; the driver must not be selected from arbitrary request input.

### 6.2 Defaults and required values

Safe local defaults:

- `DB_HOST`: `127.0.0.1` may be used as a local-only default.
- `DB_PORT`: `3306` may be used as the default MySQL port.
- `DB_CHARSET`: `utf8mb4` is the required project default.
- Driver: fixed to `mysql`.

Values required before a connection is created:

- `DB_DATABASE` must be explicitly provided and non-empty.
- `DB_USERNAME` must be explicitly provided and non-empty.
- `DB_PASSWORD` must be explicitly defined. An intentionally empty local password may be supported, but it must be represented by an explicit `DB_PASSWORD=` setting rather than an absent variable.

The configuration boundary must reject invalid ports, unsupported charsets, missing required values, and empty identifiers before the connection factory is called. It must not silently fall back to production credentials or a shared database.

### 6.3 Configuration evolution

Phase 01's `Configuration` should evolve to expose a `DatabaseConfiguration` value object or an equivalently small database-specific configuration boundary.

The preferred shape is:

- `Configuration` loads and validates application-level environment values.
- `Configuration::database()` lazily constructs or returns a `DatabaseConfiguration` from the database settings.
- `DatabaseConfiguration` owns validated host, port, database, username, password, charset, and driver values.
- `Configuration` and `DatabaseConfiguration` describe settings only; neither opens a connection or executes SQL.

Database configuration should be lazy so the Phase 02 HTTP bootstrap can continue serving the setup route without requiring MySQL. A missing database credential may therefore be reported when the CLI or another explicit database responsibility requests `database()`, not during every web request.

The exact implementation may instead use a dedicated configuration loader, but it must preserve this separation:

```text
configuration values → DatabaseConfiguration → ConnectionFactory → PDO
```

### 6.4 Environment and secrets

- Real database credentials must remain outside Git.
- `.env.example` may document variable names with safe placeholders only.
- A local `.env` is not automatically loaded unless a later dependency is deliberately introduced; the existing environment-loading convention must remain explicit.
- Database passwords must not appear in logs, exceptions sent to browsers, tests committed to Git, or command output.
- Production credentials must come from the deployment environment or a protected secret mechanism.
- A test database must use distinct variables or an explicitly isolated environment.

## 7. Connection factory

### 7.1 Responsibility

`ConnectionFactory` is a small infrastructure class responsible only for constructing a correctly configured `PDO` connection from trusted `DatabaseConfiguration`.

It must:

1. Build a MySQL DSN from validated configuration.
2. Construct `PDO` with the configured username and password.
3. Apply safe PDO attributes.
4. Return the connection or throw a safe database-connection exception.

It must not:

- Contain application SQL queries
- Know about employees, branches, or any business entity
- Implement repositories
- Run migrations automatically
- Store a static global connection
- Read PHP superglobals
- Decide HTTP response behavior

### 7.2 PDO options

The factory must configure at least:

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
- `PDO::ATTR_EMULATE_PREPARES => false`

Exception mode makes connection and statement failures explicit. Associative fetches provide predictable repository input. Disabling emulated prepares prefers native server-side prepared statements for MySQL where supported.

Native prepares do not make arbitrary SQL structure safe. Dynamic identifiers, sort expressions, table names, and SQL fragments still require trusted application-controlled allowlists and must not be accepted as ordinary values.

The factory must not issue ad-hoc `SET NAMES` statements when the DSN can configure the charset.

### 7.3 DSN and charset

The DSN should use the MySQL driver, host, port, database name, and `charset=utf8mb4`. Database configuration values are trusted configuration, not request parameters, but they must still be validated before interpolation into the DSN.

The database, tables, and connection should use `utf8mb4` consistently. A project-wide compatible collation should be chosen in the database specification or migrations; `utf8mb4_unicode_ci` is an acceptable compatibility baseline unless the supported MySQL version justifies a more specific choice.

The implementation must not rely on an unreviewed `SET NAMES` query to establish character encoding.

## 8. Dependency direction

The intended future dependency direction is:

```text
Controller
    ↓
Application service
    ↓
Repository
    ↓
PDO
```

Phase 03 establishes only the bottom part of this flow:

```text
Configuration → ConnectionFactory → PDO
```

Future repositories should receive `PDO` or a deliberately focused database dependency through constructor injection. Controllers must not create PDO connections directly. `Request`, `Response`, `Router`, `ViewRenderer`, and `MiddlewarePipeline` must not receive PDO merely because it is available.

No business repository or service should be created solely to prove that PDO can be injected.

## 9. Prepared-statement policy

All future SQL involving dynamic values must use `prepare()` and `execute()` or an equivalent explicit bound-parameter flow.

The project must distinguish:

- **SQL structure:** keywords, table names, column names, operators, ordering expressions, and clauses.
- **SQL values:** names, email addresses, dates, identifiers, statuses, and other data supplied at runtime.

Values belong in prepared-statement parameters. Placeholders cannot safely represent arbitrary SQL structure. Later feature specifications must select dynamic structures from trusted application-controlled allowlists and must never pass user-provided identifiers directly into SQL syntax.

Migrations contain controlled SQL structure and may interpolate only fixed, reviewed identifiers. They are not a reason to accept SQL from request input.

## 10. Database error handling

### 10.1 Connection and infrastructure failures

The connection factory and migration infrastructure must treat `PDOException` as an infrastructure failure. A small `DatabaseConnectionException` or `DatabaseException` is justified if it provides a stable safe boundary for callers without exposing PDO internals.

Custom exceptions must:

- Have a concrete purpose
- Avoid embedding credentials or full DSNs
- Preserve an internal previous exception for logging where appropriate
- Provide a safe public message

Do not create a large hierarchy for query, constraint, deadlock, timeout, and domain errors before a real use case requires those distinctions.

### 10.2 Flow into the HTTP boundary

Phase 02 already owns conversion of unexpected application failures into HTTP Responses. Database infrastructure must not render HTML, set HTTP status codes, or duplicate `ExceptionResponder` behavior.

When a later application service or repository raises a safe database exception during a web request, it should propagate to the existing HTTP exception boundary. A later error/logging specification may map it to an operational log entry and a safe `500` or appropriate application response.

Phase 03's migration CLI handles database failures as command failures with a non-zero process exit code and safe terminal output. It must not print passwords, raw DSNs, or raw SQL containing sensitive values.

## 11. Transactions

### 11.1 Ownership

Transactions belong around a complete application use case, not randomly around individual repository statements. The future application service that knows the business operation should normally own the boundary:

1. Begin transaction.
2. Perform the related repository operations.
3. Commit after all operations succeed.
4. Roll back when an exception prevents completion.

Phase 03 does not implement business transactions because no business use cases or repositories exist yet.

### 11.2 PDO primitives

The project must explicitly understand and document:

- `beginTransaction()` starts a transaction.
- `commit()` makes the transaction's changes durable.
- `rollBack()` reverts changes that remain transactional.
- `PDO::inTransaction()` can guard rollback behavior.

The implementation may use a small transaction helper only if it removes repeated, demonstrably unsafe rollback code. It must not become a Unit of Work, identity map, repository manager, or hidden global connection. If no real reuse exists, direct PDO transaction calls in the migration runner and future services are preferred.

### 11.3 MySQL DDL limitation

MySQL DDL may implicitly commit or otherwise fail to provide the same atomicity as ordinary InnoDB data changes. Migration execution must document that a transaction wrapper does not guarantee rollback of every schema change.

Migration failures must be reported clearly, and operators must understand that a partially applied DDL migration may require manual remediation before retrying. The runner must not claim universal atomic rollback.

## 12. Migration strategy

### 12.1 Migration responsibility

Each migration represents one deliberate schema evolution. It must have an ordered identity and explicit `up` behavior. A `down` behavior may be required for supported rollback, but it must not be generated automatically or assumed to be safe for every schema change.

The project should use small namespaced migration classes implementing a focused contract such as:

- `up(PDO $pdo): void`
- `down(PDO $pdo): void` when rollback is supported

The contract must not expose HTTP, configuration loading, repositories, or business services.

### 12.2 Naming and ordering

Migration identity must be deterministic and sortable, for example:

```text
Version202609210001CreateSchemaMigrations
Version202609210002AddSomeFutureTable
```

The numeric version prefix is the ordering key and the full migration class name or version string is the stored identity. Each identity must be unique. Discovery must reject duplicate versions, invalid class names, files outside the configured migration directory, or classes that do not implement the migration contract.

Migrations must be applied in ascending version order. Discovery must not depend on filesystem enumeration order.

### 12.3 Idempotency and failure behavior

- A migration that is recorded as applied must not be run again by a normal `migrate` command.
- A migration must not be recorded until its `up` operation completes successfully.
- If `up` fails, the runner exits non-zero and leaves the migration unrecorded; any MySQL DDL partial state must be investigated before retrying.
- Migrations should be written to fail clearly when their expected preconditions are absent or inconsistent.
- `IF NOT EXISTS` may be used deliberately for infrastructure bootstrapping, but it must not hide an incompatible schema.
- Migration files are reviewed source code, not user input.

## 13. Migration tracking

### 13.1 Tracking table

The runner may use an infrastructure table named `schema_migrations`. It must contain no employee or other business data.

The minimum conceptual columns are:

- `migration` — unique ordered migration identity
- `batch` — integer grouping one migration command's successful applications
- `applied_at` — timestamp recorded after successful application

The table should use InnoDB and `utf8mb4` where applicable. The migration identity must be unique.

### 13.2 Bootstrap policy

Because the runner needs the tracking table before it can query applied migrations, it may ensure this one infrastructure table exists with a fixed, reviewed DDL statement before discovery. This bootstrap DDL is part of migration infrastructure, not a business migration and not a general schema-loader feature.

The runner must not create employee, branch, department, user, role, contract, portfolio, project, or certification tables in this phase.

### 13.3 Commands

The small CLI boundary should support at least:

- `migrate` — apply pending migrations in order
- `status` — show discovered, applied, and pending migration identities without modifying business schema
- `rollback` — optionally revert the latest successful batch when every migration in that batch has a supported `down` implementation

If rollback is not implemented in the first iteration, the specification and CLI must say so explicitly rather than exposing a command that cannot be safe. The tracking table must never be edited manually by normal application code.

## 14. Initial schema scope

Phase 03 is database infrastructure, not employee-management schema design.

No business tables should be included for:

- Employees
- Branches
- Departments
- Dispatch companies
- Contracts
- Users or roles
- Portfolios
- Skills
- Projects
- Certifications

The only initial database object justified is the infrastructure tracking table needed to verify migration state. Migration tests may use temporary or isolated test-only tables, but those objects must not become production business schema.

## 15. Database command boundary

If migrations require a CLI entry point, use a small project-controlled script such as:

```text
bin/migrate migrate
bin/migrate status
bin/migrate rollback
```

The exact filename and command syntax may be finalized during implementation, but the boundary must:

- Load Composer's autoloader.
- Use the existing configuration boundary.
- Construct `DatabaseConfiguration`, `ConnectionFactory`, and migration infrastructure explicitly.
- Return process exit code `0` for success and non-zero for usage, configuration, connection, or migration failure.
- Avoid displaying credentials, raw DSNs, or sensitive SQL.
- Never execute migrations automatically from a normal web request.

The CLI must validate its command and arguments before opening a connection where possible.

## 16. ApplicationBootstrap and HTTP composition

The normal Phase 02 `ApplicationBootstrap` must not construct a database connection merely because Phase 03 adds database infrastructure.

Preferred behavior:

- HTTP bootstrap constructs the HTTP kernel, router, middleware, controllers, and views exactly as before.
- A database connection is created only by an explicit CLI command or a future application composition path that has a real database-dependent responsibility.
- Future repository/application-service composition may obtain a `PDO` from `ConnectionFactory` when that feature is introduced.
- `PDO` must not be injected into `Request`, `Response`, `Router`, `ViewRenderer`, or `MiddlewarePipeline` without a concrete reason.

This keeps the setup route available when MySQL is unavailable and keeps HTTP architecture independent from database infrastructure.

## 17. Proposed physical structure

This section describes files and directories that the Phase 03 implementation may create. It is not permission to create them during this specification task.

```text
company-employee-management/
├── src/
│   ├── Bootstrap/
│   │   ├── ApplicationBootstrap.php
│   │   └── Configuration.php
│   └── Database/
│       ├── DatabaseConfiguration.php
│       ├── ConnectionFactory.php
│       ├── DatabaseConnectionException.php
│       └── Migration/
│           ├── MigrationInterface.php
│           ├── MigrationDiscovery.php
│           └── MigrationRunner.php
├── database/
│   └── migrations/
├── bin/
│   └── migrate
└── tests/
    ├── Unit/Database/
    └── Integration/Database/
```

### 17.1 Structure decisions

- `src/Database/` is justified because Phase 03 has real infrastructure responsibilities.
- `DatabaseConfiguration.php` separates validated settings from connection creation.
- `ConnectionFactory.php` owns PDO construction only.
- `DatabaseConnectionException.php` is justified only as a safe boundary around low-level connection failure.
- `Migration/` contains only migration contracts, discovery, and execution; it must not become a generic schema framework.
- `database/migrations/` is for reviewed schema-evolution files and must not contain business tables in this phase.
- `bin/migrate` is a CLI boundary and is never served through `public/`.
- Integration tests are created only when a separate, explicitly designated MySQL test database is available.
- No `Repositories/`, `Models/`, `Entities/`, `Services/`, or business feature directories are created in Phase 03.

The final implementation may use fewer files if the responsibilities remain clear. It must not create empty directories or speculative abstractions.

## 18. Testing strategy

### 18.1 Unit tests

Unit tests must not require MySQL and should cover:

- Database configuration defaults and required-value validation
- Port and charset validation
- DSN construction from trusted configuration
- PDO option selection, preferably by testing the factory's observable setup or a narrowly exposed option builder
- Migration filename/class discovery and deterministic ordering
- Duplicate or invalid migration detection
- Migration tracking decisions that can be isolated without pretending to test PDO behavior
- CLI argument validation where it can be separated from connection execution

Do not replace real PDO integration behavior with a fake PDO and claim that database behavior has been tested.

### 18.2 Database integration tests

Integration tests must use a real MySQL test database and should cover:

- Successful PDO connection
- `utf8mb4` connection behavior
- Prepared statement execution with values containing quotes and Japanese text
- Migration tracking-table creation
- Migration discovery and execution
- Repeated migration runs not reapplying completed migrations
- Migration failure behavior
- Rollback behavior if rollback is implemented
- Transaction behavior where a real transaction boundary exists

Integration tests must be clearly separated from unit tests and must not run against a default development or production database.

## 19. Test database safety

The test database configuration must be explicit and defensive.

Required safeguards:

- Integration tests run only when the application environment is explicitly `test`.
- Test credentials use separate `DB_TEST_*` variables or an equivalently isolated configuration source.
- The test database name must be clearly designated as a test database, such as a name ending in `_test`.
- The test database must not equal the configured development or production database name.
- Destructive setup/cleanup must verify the environment and database identity before executing.
- Production credentials must never be loaded automatically by the test suite.
- Tests must not drop or truncate a database outside the explicitly designated test database.
- Test setup and cleanup behavior must be documented, including whether migrations run fresh, inside a transaction, or through an isolated temporary schema.

If the test database is unavailable, unit tests should remain runnable and the integration suite should fail clearly or be intentionally skipped according to the documented command. It must not silently fall back to another database.

## 20. Security boundaries

Phase 03 must establish at least these security boundaries:

- Credentials are supplied outside Git and outside browser responses.
- Values in SQL are passed through prepared statements.
- User-controlled identifiers must never become arbitrary SQL identifiers or fragments.
- Production database failures expose only safe messages.
- MySQL and tables use `utf8mb4` consistently.
- Database users should have least-privilege permissions appropriate to the environment where practical.
- Migrations are not executable through normal web requests.
- Automated integration tests cannot use production credentials automatically.
- Raw PDO exceptions, DSNs, passwords, and sensitive SQL are not printed to users or normal command output.

Authentication, authorization, CSRF, session security, and upload security remain later concerns.

## 21. Manual verification

The implementation should document manual checks for both paths below:

```text
PHP
  ↓
Configuration
  ↓
DatabaseConfiguration
  ↓
ConnectionFactory
  ↓
PDO
  ↓
MySQL
```

```text
Migration CLI
  ↓
Migration discovery
  ↓
PDO
  ↓
schema_migrations
  ↓
Migration execution
```

Verification should include:

- Confirming the active PHP version and required PDO MySQL driver.
- Supplying test-only database variables explicitly.
- Establishing a connection without displaying credentials.
- Checking the configured character set.
- Running migration status before and after `migrate`.
- Running `migrate` again and confirming no completed migration is reapplied.
- Exercising a controlled migration failure and confirming a non-zero exit code.
- Exercising rollback only if the implementation supports it.
- Confirming that the Phase 02 setup route still works when database infrastructure is present but no database connection is requested.

No employee CRUD or business data is required for verification.

## 22. Relationship to Phase 02

The Phase 02 HTTP flow remains:

```text
Browser
  ↓
public/index.php
  ↓
ApplicationBootstrap
  ↓
Request
  ↓
Router
  ↓
Middleware
  ↓
Controller
  ↓
Response
  ↓
ResponseEmitter
```

Database infrastructure sits behind future application-service and repository responsibilities. It must not be pushed upward into the Request, Router, Response, ViewRenderer, or ResponseEmitter merely to demonstrate availability.

## 23. Learning goals

Phase 03 is intended to teach:

- Relational database fundamentals
- PDO and PDOStatement
- DSN construction
- Environment-backed database configuration
- PDO exception behavior
- Prepared statements and SQL-injection prevention
- SQL structure versus SQL values
- `utf8mb4` and connection character sets
- Transactions and transaction ownership
- MySQL DDL limitations
- Migration concepts and schema versioning
- Migration tracking and repeatable execution
- CLI boundaries and process exit codes
- Database integration testing
- Test database isolation
- Dependency injection and dependency direction
- Infrastructure boundaries
- Safe database error handling

## 24. Definition of Done

Phase 03 is complete only when:

1. The implementation is made on the official `feature/database-foundation` branch.
2. Database settings are represented by an explicit configuration boundary and credentials remain outside Git.
3. A database configuration object validates required values, safe defaults, port, charset, and environment separation.
4. The PDO connection factory creates a MySQL connection with the documented safe PDO options.
5. Connection failures are converted into a safe infrastructure error without exposing secrets.
6. The prepared-statement policy is documented and verified with real integration behavior where available.
7. Migration discovery is deterministic and rejects invalid or duplicate migrations.
8. The `schema_migrations` tracking behavior records successful migrations and prevents normal reapplication.
9. Migration failure and rollback behavior are documented honestly, including MySQL DDL limitations.
10. The CLI migration boundary returns meaningful exit codes and is not reachable through web requests.
11. Unit tests pass without requiring MySQL.
12. Database integration tests, when enabled, use an explicitly isolated test database and pass without touching non-test data.
13. The Phase 02 HTTP architecture remains intact and does not require a database connection for the setup route.
14. Production-safe database error behavior is verified.
15. No business tables or CRUD features have been implemented.
16. Documentation covers configuration, migration commands, test database safety, and manual verification.
17. Git-visible changes remain within Phase 03 database-foundation scope.

Phase 03 is not complete merely because PHP can connect to MySQL. The configuration, failure behavior, migration state, test isolation, and dependency boundaries must also be demonstrated.

## 25. Non-negotiable rules

1. Do not introduce an ORM, Active Record, query builder, or generic repository framework.
2. Do not create business tables or implement business CRUD in this phase.
3. Do not open a database connection from the normal Phase 02 HTTP bootstrap without a real database-dependent responsibility.
4. Do not put SQL queries in controllers, views, Request, Response, Router, or the connection factory.
5. Do not concatenate untrusted values into SQL.
6. Do not treat table names, column names, or SQL fragments as safely parameterizable values.
7. Do not expose credentials, raw DSNs, raw sensitive SQL, or PDO stack traces in production.
8. Do not execute migrations from a web request.
9. Do not allow integration tests to fall back to a non-test database.
10. Do not build a Unit of Work or speculative database framework before a real use case requires one.
11. Do not create empty future business directories or classes.
12. Do not modify the master, Phase 01, or Phase 02 specifications as part of implementing this phase unless a separately reviewed architectural correction is required.

