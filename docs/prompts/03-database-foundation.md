You are preparing the specification for Phase 03 of the Company Employee Management System.

This task is SPECIFICATION ONLY.

Do not implement PHP application code.

# Context

This is an advanced Pure PHP learning project intended to develop professional and senior-level PHP engineering skills.

Phase 01 established the project/bootstrap foundation.

Phase 02 established the HTTP and server-rendered presentation architecture, including:

- Request abstraction
- Response abstraction
- Router and route matching
- Middleware pipeline
- Controller boundary
- View rendering
- HTML escaping
- HTTP exception/error boundary
- Response emission
- PHPUnit testing foundation

Phase 03 will establish the DATABASE FOUNDATION.

Before writing the Phase 03 specification, read these files completely:

- docs/specs/00-project-overview.md
- docs/specs/01-project-setup.md
- docs/specs/02-core-architecture.md
- README.md
- composer.json
- config/app.php

Also inspect the existing implementation, especially:

- public/index.php
- src/Bootstrap/ApplicationBootstrap.php
- src/Bootstrap/Configuration.php
- src/Http/
- routes/web.php
- tests/

The Phase 03 design must build naturally on the existing Phase 01 and Phase 02 architecture rather than replacing it unnecessarily.

# Output

Create only:

docs/specs/03-database-foundation.md

Do not modify existing application files.

Do not implement the specification.

Do not create database classes or directories.

Do not run Composer commands.

Do not execute migrations.

Do not connect to MySQL.

Do not create, switch, merge, or delete Git branches.

Do not commit or push anything.

The official future implementation branch for this phase is:

feature/database-foundation

# Phase 03 Purpose

Phase 03 establishes the database infrastructure that future business features will use.

The intended high-level dependency flow should be approximately:

Environment
↓
Configuration
↓
Database Configuration
↓
PDO Connection Factory
↓
PDO
↓
Database Infrastructure
↓
Future Repositories
↓
Future Application Services

Phase 03 must teach how professional PHP applications interact with relational databases without hiding the fundamentals behind an ORM or framework.

The architecture must remain Pure PHP.

# Design Priorities

Prioritize:

- PDO fundamentals
- explicit database configuration
- environment-backed secrets
- dependency injection
- prepared statements
- safe database defaults
- transaction boundaries
- migration strategy
- database exception handling
- testability
- clear dependency direction
- minimal abstraction
- framework-independent PHP knowledge

Do not imitate Laravel Eloquent, Doctrine, or another ORM.

Every class or abstraction must have a demonstrated responsibility.

# Required Specification Topics

## 1. Phase Scope

Clearly define what Phase 03 implements and what it intentionally defers.

Phase 03 may establish:

- database configuration
- environment variables required for MySQL
- PDO connection creation
- connection factory
- safe PDO options
- connection failure behavior
- database exception boundary
- transaction foundation
- migration infrastructure
- schema version tracking
- migration execution strategy
- migration rollback strategy if justified
- database integration-test infrastructure
- database-related documentation

Phase 03 must not implement business CRUD.

## 2. Database Technology

The project should use:

- MySQL
- PHP PDO
- PDO MySQL driver

Explain why PDO is appropriate for this Pure PHP learning project.

The specification should teach the difference between:

- PDO
- PDOStatement
- prepared statements
- transactions
- SQL
- repositories

Do not introduce an ORM.

## 3. Database Configuration

Define the database configuration values required by the application.

Consider values such as:

DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
DB_CHARSET

Determine which values should have safe defaults and which should require explicit configuration.

Database credentials must not be committed to Git.

Explain how Phase 01 Configuration should evolve to expose database configuration without turning Configuration into a database service.

Configuration should describe settings.

It should not open connections.

## 4. Connection Factory

Specify a small PDO connection factory.

Its responsibility should be limited to constructing correctly configured PDO connections from trusted configuration.

Discuss appropriate PDO options, including:

PDO::ATTR_ERRMODE
PDO::ATTR_DEFAULT_FETCH_MODE
PDO::ATTR_EMULATE_PREPARES

Prefer exception-based error handling.

The specification should explain why native prepared statements should normally be preferred when supported.

Do not place SQL queries inside the connection factory.

Do not turn the factory into a repository.

Do not use static global database connections.

## 5. Dependency Direction

Define the intended dependency direction.

Future code should conceptually follow:

Controller
↓
Application Service
↓
Repository
↓
PDO

HTTP code should not directly create PDO connections.

Repositories should eventually receive PDO or an appropriate database dependency through constructor injection.

Phase 03 should establish the foundation without implementing future business repositories.

## 6. Prepared Statements

Document the project's SQL safety rule.

Dynamic values must not normally be concatenated directly into SQL.

Future queries should use:

prepare()
execute()

with bound/input values.

Explain the difference between:

SQL structure

and

SQL values.

Values should be parameterized.

Do not imply that table names, column names, ORDER BY expressions, or arbitrary SQL fragments can safely be supplied through normal value placeholders.

Dynamic SQL structure must later be selected from trusted application-controlled allowlists.

## 7. Character Encoding

Define UTF-8 database expectations.

Use utf8mb4 where appropriate.

Explain where charset belongs in the connection configuration.

Avoid relying on ad-hoc SET NAMES queries if the DSN can configure the charset correctly.

## 8. Database Error Handling

Define how database connection and infrastructure errors should behave.

Low-level PDO exceptions must not expose:

- database passwords
- connection strings containing secrets
- raw SQL containing sensitive values
- stack traces in production
- internal filesystem paths

Phase 02 already established the HTTP exception boundary.

Explain how database failures should eventually flow into that existing boundary without duplicating HTTP error handling inside database infrastructure.

Do not create a huge database exception hierarchy.

Only introduce custom exceptions where they provide a concrete architectural benefit.

## 9. Transactions

Define a minimal transaction foundation.

Explain:

beginTransaction()
commit()
rollBack()

Explain when transactions are required.

Transactions should normally be controlled around a complete application use case rather than randomly inside unrelated SQL statements.

Phase 03 may introduce a small transaction helper only if its responsibility is clearly justified.

Do not build a Unit of Work framework.

Do not implement business transactions yet.

The specification should make transaction ownership explicit for future phases.

## 10. Migration Strategy

Define how database schema changes will be versioned and applied.

The project should learn migration concepts without introducing a full framework.

A migration should have a clear responsibility for one schema evolution.

Discuss:

- migration naming/versioning
- migration ordering
- schema migration tracking table
- up migrations
- down/rollback behavior if supported
- transactional limitations of MySQL DDL
- failure behavior
- idempotency expectations

Avoid designing a complete Laravel-style migration framework.

A small migration runner is acceptable if justified.

## 11. Migration Tracking

Specify how applied migrations are recorded.

A table conceptually similar to:

schema_migrations

may be used.

It should record enough information to determine which migrations have already been applied.

Do not put business data in the migration tracking table.

## 12. Initial Schema Scope

Be strict about schema scope.

Phase 03 is database infrastructure.

Do not create the complete employee-management schema merely because migrations now exist.

If a minimal database object is necessary to prove migration infrastructure, justify it carefully.

Prefer proving migration execution using migration infrastructure itself rather than prematurely creating:

- employees
- branches
- departments
- users
- roles
- contracts
- portfolios
- projects
- certifications

Those belong to later business phases.

## 13. Database Command / Migration Entry Point

If migrations require a CLI entry point, specify a small command-line boundary.

For example, a project-controlled script may conceptually support migration execution.

Do not introduce a full console framework.

The CLI boundary must:

- use Composer autoloading
- use the existing configuration boundary
- construct database infrastructure explicitly
- return meaningful process exit codes
- avoid exposing secrets

Web requests must not automatically execute migrations.

## 14. ApplicationBootstrap

Explain whether the normal HTTP ApplicationBootstrap should construct a database connection during Phase 03.

Avoid connecting to MySQL unnecessarily on every request if the Phase 03 setup route does not require database access.

Prefer dependencies to be created when a real application responsibility requires them.

Do not inject PDO into:

- Request
- Response
- Router
- ViewRenderer
- MiddlewarePipeline

unless a future concrete responsibility genuinely requires it.

The HTTP architecture from Phase 02 must remain independent from database implementation details.

## 15. Proposed Physical Structure

Propose only files and directories Phase 03 genuinely needs.

Possible conceptual structure:

src/
├── Bootstrap/
├── Database/
│ ├── ConnectionFactory.php
│ └── Migration/
│ └── ...

database/
└── migrations/

bin/
└── ...

tests/
├── Unit/
└── Integration/

Do not mechanically copy this structure.

Choose the smallest structure that satisfies the actual Phase 03 responsibilities.

Explain why each important boundary exists.

Do not create:

Repositories/
Models/
Entities/
Services/

unless Phase 03 has a real requirement for them.

Avoid empty future directories.

## 16. Testing Strategy

Define meaningful automated tests.

Possible unit tests include:

- database configuration behavior
- DSN construction
- PDO option configuration
- migration discovery/order logic
- migration tracking behavior where it can be isolated
- transaction helper behavior if one exists

Integration tests should verify behavior that genuinely requires MySQL, such as:

- successful PDO connection
- utf8mb4 connection behavior
- prepared statement execution
- migration execution
- migration tracking
- repeated migration runs do not reapply completed migrations
- failure behavior
- rollback behavior where supported

Clearly distinguish:

Unit Tests

from:

Database Integration Tests

Do not fake PDO merely to claim database behavior has been tested.

If tests require a dedicated test database, specify how it must be isolated from development/production databases.

Tests must never destroy a non-test database.

## 17. Test Database Safety

Define strong safety requirements for database integration tests.

The test database should use separate configuration.

Destructive test operations must verify that they are operating against an explicitly designated test database/environment.

Production database credentials must never be used automatically by the test suite.

Document how test setup and cleanup should work.

## 18. Security Boundaries

Include at least:

- credentials stored outside Git
- PDO prepared statements for values
- no SQL built from arbitrary user-controlled identifiers
- safe production database errors
- utf8mb4
- least-privilege database users where practical
- no database credentials in browser responses
- no migration execution from normal web requests
- no production database used by automated integration tests

Authentication, authorization, CSRF, and upload security remain later concerns.

## 19. Manual Verification

Define manual verification steps for Phase 03.

Verification should prove:

PHP
↓
Configuration
↓
Connection Factory
↓
PDO
↓
MySQL

and separately:

Migration CLI
↓
Migration discovery
↓
PDO
↓
schema_migrations
↓
migration execution

Verification should include checking the migration state and failure behavior.

Do not require Employee CRUD or other business features.

## 20. Relationship to Phase 02

Explicitly document that Phase 03 extends infrastructure without breaking the HTTP architecture.

The Phase 02 flow remains:

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

Database infrastructure sits behind future application/repository responsibilities.

Request, Router, Response, ViewRenderer, and ResponseEmitter must not become database-aware.

## 21. Learning Goals

Explicitly list concepts Phase 03 is intended to teach.

Include:

- relational database fundamentals
- PDO
- DSN construction
- database configuration
- environment variables
- PDO exceptions
- prepared statements
- SQL injection prevention
- utf8mb4
- transactions
- migration concepts
- schema versioning
- database integration testing
- dependency injection
- dependency direction
- infrastructure boundaries
- database error handling

## 22. Definition of Done

Provide concrete acceptance criteria.

Phase 03 should not be considered complete merely because PHP can connect to MySQL.

Definition of Done should require appropriate items such as:

- database configuration boundary implemented
- PDO connection creation works
- safe PDO options configured
- database credentials remain outside Git
- prepared-statement policy documented and verified
- migration infrastructure works
- migration tracking works
- repeated migration execution behaves safely
- database integration tests pass
- test database isolation is verified
- transaction behavior is understood/tested where implemented
- production-safe database error behavior is verified
- Phase 02 HTTP architecture remains intact
- no business CRUD has been implemented
- documentation is updated
- Git-visible changes remain within Phase 03 scope

## 23. Explicitly Deferred

Phase 03 must NOT implement:

- authentication
- login/logout
- authenticated sessions
- users
- roles
- authorization
- CSRF
- Branch CRUD
- Department CRUD
- Employee CRUD
- Dispatch Company CRUD
- Contracts
- Employee Portfolio
- Skills
- Projects
- Certifications
- employee search/filter/sort/pagination
- dashboard business data
- file uploads
- API endpoints
- SPA architecture
- complete Material Design UI
- ORM
- Active Record
- generic repository framework

Future business phases will use the database foundation established here.

# Important Design Principle

Do not overengineer.

For every proposed class or abstraction, explain its responsibility.

Prefer:

small explicit infrastructure
↓
understandable PDO usage
↓
testable database behavior

over:

large generic database framework
↓
hidden behavior
↓
speculative abstractions

The goal is to understand how professional PHP database infrastructure works internally, not to recreate Laravel's database layer.

# Final Requirement

After creating:

docs/specs/03-database-foundation.md

stop.

Report only:

- the file created
- a short summary of major design decisions
- important decisions intentionally deferred

Do not implement Phase 03.

Do not modify application code.

Do not run Composer.

Do not connect to MySQL.

Do not execute migrations.

Do not perform Git operations.

# Implementation Prompt

Implement Phase 03 — Database Foundation.

Before making any changes, read completely:

- docs/specs/00-project-overview.md
- docs/specs/01-project-setup.md
- docs/specs/02-core-architecture.md
- docs/specs/03-database-foundation.md
- README.md
- composer.json
- config/app.php
- src/Bootstrap/Configuration.php
- src/Bootstrap/ApplicationBootstrap.php
- public/index.php
- existing tests

The authoritative requirements for this task are in:

docs/specs/03-database-foundation.md

Implement that specification.

# Git

The current implementation branch must be:

feature/database-foundation

Do not create another branch.

Do not merge to main.

Do not commit or push.

# Scope

Implement only Phase 03 database infrastructure.

Do not implement any business feature.

Do not create:

- employees
- branches
- departments
- users
- roles
- contracts
- portfolios
- skills
- projects
- certifications

Do not implement business CRUD.

Do not introduce:

- ORM
- Active Record
- query builder
- generic repository framework
- Unit of Work framework

# Database foundation

Implement the smallest architecture necessary for:

Environment
↓
Configuration
↓
DatabaseConfiguration
↓
ConnectionFactory
↓
PDO

Use:

- MySQL
- PDO
- pdo_mysql
- utf8mb4
- exception-based PDO errors
- associative fetch mode
- native prepared statements

Use safe PDO configuration equivalent to:

PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
PDO::ATTR_EMULATE_PREPARES => false

Database configuration must remain separate from connection creation.

Do not create a global/static PDO connection.

# HTTP architecture

Do not make the existing Phase 02 HTTP application depend on MySQL.

The existing setup route must continue working even when MySQL is unavailable.

Do not inject PDO into:

- Request
- Response
- Router
- ViewRenderer
- MiddlewarePipeline

Do not execute migrations from web requests.

# Migration infrastructure

Implement the minimal migration infrastructure required by the specification.

Prefer:

- deterministic migration discovery
- migration contract
- schema_migrations tracking
- migrate command
- status command

Implement rollback only if it remains small, clear, and safe.

Do not build a Laravel-style migration framework.

Do not create business tables merely to demonstrate migrations.

The schema_migrations infrastructure table is allowed.

# CLI

Create the small CLI migration boundary described by the specification.

It must:

- use Composer autoloading
- use the existing configuration system
- construct database dependencies explicitly
- return meaningful exit codes
- avoid printing secrets
- never be reachable through public/

# Security

Enforce the Phase 03 security requirements.

In particular:

- credentials outside Git
- no credentials in errors/output
- prepared statements for dynamic SQL values
- no arbitrary user-controlled SQL identifiers
- utf8mb4
- safe PDO exceptions
- no production database fallback during tests
- no migrations through HTTP

# Testing

Add meaningful automated tests.

Unit tests must run without MySQL.

Add database integration tests where appropriate.

Integration tests must have strong safety guards and must never automatically use a development or production database.

Do not fake PDO and claim real database behavior was tested.

If an explicitly configured MySQL test database is unavailable, integration tests may be skipped clearly according to the documented test workflow, while unit tests must remain runnable.

Test important behavior including, where applicable:

- database configuration validation
- DSN construction
- PDO configuration
- deterministic migration discovery
- duplicate/invalid migration rejection
- schema_migrations behavior
- repeated migrate behavior
- prepared statement behavior
- utf8mb4
- safe failure behavior
- test database isolation

# Documentation

Update README or appropriate documentation only where Phase 03 requires it.

Document:

- required DB environment variables
- MySQL/PDO requirements
- migration commands
- test database configuration
- integration-test safety
- manual verification

Do not rewrite unrelated documentation.

# Quality

Use:

declare(strict_types=1);

where appropriate.

Keep classes small and focused.

Prefer explicit dependencies and constructor injection.

Do not add abstractions without a concrete responsibility.

Preserve the architecture established in Phase 01 and Phase 02.

# Verification

After implementation:

1. Run Composer validation if appropriate.
2. Run the complete unit test suite.
3. Run static PHP syntax checks where useful.
4. Run database integration tests only if a safely configured test database is available.
5. Verify the existing Phase 02 HTTP tests still pass.
6. Do not connect to an unknown or non-test database merely to make integration tests pass.

If MySQL integration tests cannot safely run because test database configuration is unavailable, report that clearly instead of substituting another database.

# Final report

When finished, report:

1. Files created
2. Files modified
3. Architecture implemented
4. Tests added
5. Commands/tests executed
6. Test results
7. Integration tests run or skipped, and why
8. Any important implementation decisions
9. Anything intentionally deferred

Do not commit.

Do not push.

Do not merge.

Stop after implementation and verification.
