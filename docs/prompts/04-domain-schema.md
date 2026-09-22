# Phase 04 — Domain Schema

## Specification Prompt

You are acting as a senior PHP backend engineer and database architect.

We are building a production-minded **Company Employee Management System** using **Pure PHP 8.3+**, MySQL/MariaDB, PDO, Composer PSR-4 autoloading, and PHPUnit.

The project intentionally does NOT use Laravel, Symfony, an ORM, Active Record, or a database framework.

Phase 01 established the project foundation.

Phase 02 established the HTTP and presentation architecture.

Phase 03 established the database infrastructure:

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
MySQL / MariaDB

It also introduced:

- safe PDO configuration
- prepared-statement policy
- database exceptions
- migration discovery
- migration execution
- schema_migrations tracking
- migration CLI
- unit tests
- isolated real-database integration tests

We are now starting:

# Phase 04 — Domain Schema

The purpose of this phase is to design and implement the **first real business database schema** for the Company Employee Management System.

Do NOT implement employee CRUD, controllers, repositories, services, authentication, authorization, search, pagination, dashboards, or UI in this phase.

The goal is database/domain structure only.

---

# 1. Main Goal

Design a normalized, maintainable relational schema for the organizational and employee domain.

At minimum, evaluate whether the system requires these concepts:

- Company
- Branch
- Department
- Employee

Do not blindly create tables just because they are listed.

For every table and relationship, explain why it belongs in the domain.

The expected conceptual organization is approximately:

Company
↓
Branch
↓
Department
↓
Employee

However, do not force this hierarchy if a more flexible relational model is justified.

Explicitly determine questions such as:

- Can a company have multiple branches?
- Can a branch have multiple departments?
- Can the same department concept exist in different branches?
- Does every employee belong to a branch?
- Does every employee belong to a department?
- Should department assignment be nullable?
- Can employees exist before department assignment?
- Should employees reference both branch and department?
- How do we prevent inconsistent branch/department relationships?
- Should employee managers be modeled now or deferred?
- Should job titles/positions be normalized now or deferred?

Prefer the simplest design that supports realistic requirements without speculative abstractions.

---

# 2. Database Design Principles

The specification must explicitly discuss:

- relational modeling
- normalization
- primary keys
- foreign keys
- unique constraints
- NOT NULL constraints
- nullable columns
- indexes
- referential integrity
- deletion behavior
- update behavior
- timestamps
- naming conventions
- data types
- string lengths
- monetary values if any
- date vs datetime usage
- boolean representation
- status representation

Avoid unnecessary denormalization.

Avoid premature generic frameworks such as:

- entity attribute value tables
- generic metadata tables
- generic relation tables
- generic lookup engines
- polymorphic relationships

unless there is a strong concrete requirement.

---

# 3. Naming Convention

Define a consistent database naming convention.

Prefer clear snake_case names.

Example:

companies
branches
departments
employees

Columns may follow conventions such as:

id
company_id
branch_id
department_id
employee_code
first_name
last_name
email
phone
hire_date
status
created_at
updated_at

But the specification must decide the final schema rather than blindly copying this list.

Explain the reason for important naming decisions.

---

# 4. Primary Key Strategy

Decide and document the primary key strategy.

Evaluate:

- BIGINT UNSIGNED AUTO_INCREMENT
- INT UNSIGNED AUTO_INCREMENT
- UUID

For this learning project, prefer a straightforward relational key strategy unless UUID provides a concrete benefit.

Explain why the selected strategy is appropriate.

Do not introduce UUID complexity without a real requirement.

---

# 5. Company Table

Evaluate the required company attributes.

Potential examples:

- id
- name
- code
- email
- phone
- address
- created_at
- updated_at

Decide:

- which fields are required
- which fields are optional
- which fields must be unique
- suitable lengths
- indexes
- constraints

Do not add fields without explaining their purpose.

---

# 6. Branch Table

Design the relationship:

Company
1
↓
N
Branches

A branch must not exist without a valid company unless there is a strong reason.

Evaluate attributes such as:

- id
- company_id
- name
- code
- email
- phone
- address
- created_at
- updated_at

Determine whether:

branch code is globally unique

or

(company_id, code) is unique.

Prefer domain-appropriate scoped uniqueness.

Define foreign-key deletion behavior explicitly.

---

# 7. Department Table

Design the department relationship carefully.

Possible model:

Branch
1
↓
N
Departments

Evaluate:

- id
- branch_id
- name
- code
- created_at
- updated_at

Determine whether department names/codes should be unique:

globally

or

within a branch.

Consider realistic examples such as:

Tokyo Branch
Engineering
HR

Osaka Branch
Engineering
HR

The schema should allow legitimate repeated department names across branches if appropriate.

---

# 8. Employee Table

Design the employee table carefully because it will become the core business entity in later phases.

Evaluate attributes such as:

- id
- branch_id
- department_id
- employee_code
- first_name
- last_name
- email
- phone
- hire_date
- status
- created_at
- updated_at

Determine which fields are required and which are nullable.

Discuss whether an employee may temporarily exist without a department.

Example:

Employee joins company
↓
Assigned to branch
↓
Department assignment may occur later

If this is a realistic workflow, department_id may be nullable.

Do not add authentication credentials to employees in this phase.

Authentication belongs to a later phase.

---

# 9. Employee Code

Define employee code uniqueness.

Evaluate:

- globally unique employee_code
- company-scoped employee_code
- branch-scoped employee_code

Choose the simplest realistic strategy and explain it.

Consider future search and display requirements.

---

# 10. Email

Decide whether employee email is:

- required or optional
- globally unique
- company-scoped unique
- non-unique

Explain the business assumption.

Do not confuse database identity with future authentication identity.

Authentication has not been designed yet.

---

# 11. Employee Status

Design employee status without over-engineering.

Potential states:

- active
- inactive

Possibly:

- on_leave
- terminated

Decide what Phase 04 actually needs.

Evaluate whether to use:

VARCHAR
ENUM
lookup table

Prefer a design that is maintainable and portable.

Avoid creating a status lookup framework unless justified.

---

# 12. Referential Integrity

Explicitly specify foreign keys.

For example:

branches.company_id
→ companies.id

departments.branch_id
→ branches.id

employees.branch_id
→ branches.id

employees.department_id
→ departments.id

But carefully analyze the employee branch + department relationship.

A naive schema could allow:

employee.branch_id = Tokyo

employee.department_id = Engineering department belonging to Osaka

This is inconsistent.

The specification MUST address this integrity problem.

Compare possible solutions such as:

### Option A

Employee references department only and branch is derived through department.

### Option B

Employee references both branch and department, with application-level validation.

### Option C

Use a database-level composite relationship/constraint where appropriate.

Discuss trade-offs and choose an approach appropriate for this project.

Do not silently allow inconsistent relationships.

---

# 13. Foreign Key Delete Behavior

For every foreign key, define deletion behavior.

Evaluate:

- RESTRICT
- CASCADE
- SET NULL

Do not automatically use CASCADE everywhere.

Examples requiring explicit decisions:

Can a company be deleted while branches exist?

Can a branch be deleted while departments/employees exist?

Can a department be deleted while employees belong to it?

For business data, prefer safety against accidental mass deletion.

Explain each important decision.

---

# 14. Index Strategy

Design indexes based on expected future access patterns.

Future application features may include:

- find employee by ID
- find employee by employee code
- find employee by email
- list employees by branch
- list employees by department
- search employees
- filter employees by status
- order employees by name/hire date
- pagination

Do NOT build those application features now.

Only create indexes that are clearly justified by expected database access and constraints.

Explain that foreign-key indexes and unique constraints may already provide useful indexes depending on the database.

Avoid adding indexes blindly.

---

# 15. Date and Time Strategy

Define appropriate types.

Examples:

hire_date
→ DATE

created_at
→ DATETIME or TIMESTAMP

updated_at
→ DATETIME or TIMESTAMP

Explain why.

Decide whether timestamps are:

- application managed
- database managed
- mixed

Prefer one clear strategy.

---

# 16. Migration Strategy

Use the migration infrastructure built in Phase 03.

Create production migration files under:

database/migrations/

Do not manually create business tables outside migrations.

Migrations must:

- follow deterministic version naming
- implement the existing migration contract
- create tables in dependency order
- create appropriate constraints
- create appropriate indexes
- provide safe down() behavior where reasonably possible

Expected dependency order may be:

companies
↓
branches
↓
departments
↓
employees

Rollback must occur in reverse dependency order.

Respect MySQL/MariaDB DDL limitations.

Do not claim DDL rollback is universally transactional.

---

# 17. Migration Granularity

Decide whether Phase 04 should use:

- one migration per table

or

- one migration for the initial domain schema

Prefer an approach that provides clear schema history and maintainability.

Explain the choice.

Do not create excessive micro-migrations without benefit.

---

# 18. Database Engine and Charset

Use database choices compatible with the Phase 03 foundation.

Expected:

- MySQL/MariaDB
- InnoDB
- utf8mb4

Ensure foreign keys are supported.

Do not silently introduce database-specific features that unnecessarily reduce compatibility between MySQL and the XAMPP MariaDB test environment.

---

# 19. Seed Data

Do NOT build a full seeding framework in Phase 04.

If test data is needed, create data directly inside integration tests or use narrowly scoped test fixtures.

Production seed data is outside the scope unless a concrete schema requirement makes it necessary.

---

# 20. Domain Models

Do NOT create PHP domain entities/models merely because tables now exist.

Phase 04 is schema-focused.

Avoid prematurely creating:

- Company.php
- Branch.php
- Department.php
- Employee.php
- BaseModel.php
- ActiveRecord.php

Those should be introduced only when the application layer actually needs them.

---

# 21. Repository Layer

Do NOT implement repositories yet unless the specification identifies a concrete Phase 04 requirement that cannot reasonably be tested without one.

Expected future architecture:

Controller
↓
Application Service
↓
Repository
↓
PDO
↓
MySQL

Phase 04 remains focused on:

Migration
↓
Database Schema
↓
Constraints
↓
Integration Tests

---

# 22. Authentication Separation

Do not add:

- passwords
- password hashes
- login identifiers
- remember tokens
- API tokens
- sessions
- roles
- permissions

to the employee schema solely for future authentication.

Authentication and authorization will be designed in later phases.

Employee business identity and application login identity must not be assumed to be the same thing without an explicit future design decision.

---

# 23. Testing Requirements

Add automated tests appropriate for Phase 04.

## Unit Tests

Only add unit tests if there is actual PHP logic to test.

Do not create meaningless tests simply to increase test count.

## Database Integration Tests

Use the isolated database configuration established in Phase 03.

Integration tests should verify important schema behavior such as:

- migrations successfully create the domain schema
- expected tables exist
- important columns exist
- primary keys work
- required foreign keys are enforced
- unique constraints are enforced
- nullable relationships behave correctly
- invalid parent references are rejected
- important deletion restrictions work
- valid company → branch → department → employee data can be inserted
- inconsistent relationships are rejected according to the chosen schema design
- migrations remain idempotent
- rollback removes domain tables safely in reverse dependency order if rollback is supported

Do not test every SQL detail redundantly.

Focus on business-critical integrity rules.

---

# 24. Test Database Safety

Retain all Phase 03 safety rules.

Integration tests must require:

APP_ENV=test

and explicit:

DB_TEST_HOST
DB_TEST_PORT
DB_TEST_DATABASE
DB_TEST_USERNAME
DB_TEST_PASSWORD
DB_TEST_CHARSET

The test database must remain clearly isolated.

Example:

company_employee_management_test

Never allow schema tests to fall back to production credentials.

Never perform destructive tests against a non-test database.

---

# 25. Security Requirements

Phase 04 must preserve:

- no credentials in Git
- no sensitive DSN output
- no password output
- no web-accessible migration execution
- prepared statements for dynamic test values
- referential integrity
- safe test DB isolation
- utf8mb4
- least-privilege awareness

Database constraints should protect critical integrity even when application code has bugs.

---

# 26. Explicitly Out of Scope

Do NOT implement:

- Employee CRUD
- Company CRUD
- Branch CRUD
- Department CRUD
- controllers
- application services
- repositories
- HTTP routes
- forms
- validation requests
- authentication
- login/logout
- sessions
- users
- roles
- permissions
- authorization
- CSRF changes
- employee search
- filters
- sorting
- pagination
- uploads
- employee photos
- dashboards
- REST API
- React
- SPA
- Material UI
- ORM
- Active Record
- query builder
- generic repository framework
- generic domain framework

These belong to later phases.

---

# 27. Architecture Boundary

Phase 04 should preserve the architecture already established.

HTTP:

Browser
↓
public/index.php
↓
ApplicationBootstrap
↓
HttpKernel
↓
Router
↓
Middleware
↓
Controller
↓
Response

Database infrastructure:

Environment
↓
Configuration
↓
DatabaseConfiguration
↓
ConnectionFactory
↓
PDO

Phase 04 adds:

Migration
↓
Relational Domain Schema

Do not couple Request, Response, Router, Middleware, or ViewRenderer directly to PDO.

---

# 28. Specification Deliverable

Create:

docs/specs/04-domain-schema.md

The specification must include:

1. Phase purpose
2. Scope
3. Non-goals
4. Domain assumptions
5. Entity/table overview
6. Relationship diagram
7. Complete table definitions
8. Column names
9. SQL data types
10. nullability
11. defaults
12. primary keys
13. foreign keys
14. unique constraints
15. indexes
16. delete/update behavior
17. employee branch/department integrity strategy
18. migration strategy
19. migration dependency order
20. rollback strategy
21. integration test strategy
22. test DB safety
23. security considerations
24. future repository/application-layer implications
25. acceptance criteria

Include a concise relational diagram similar to:

Company
1
↓
N
Branch
1
↓
N
Department
1
↓
N
Employee

but adjust it to match the actual selected schema.

Also include example SQL/table definitions where they clarify important constraints.

---

# 29. Design Quality Requirements

Before finalizing the specification, verify:

- every table has a concrete domain reason
- every column has a concrete reason
- relationships are normalized
- duplicate data is minimized
- employee organizational integrity cannot silently become inconsistent
- foreign-key behavior is deliberate
- uniqueness rules match business assumptions
- nullable fields reflect realistic workflows
- indexes have a reason
- authentication concerns have not leaked into the employee domain
- repositories have not been prematurely implemented
- schema remains understandable to another engineer
- schema is compatible with MySQL/MariaDB
- migrations fit the Phase 03 migration infrastructure
- test strategy proves critical database constraints

Prefer simple, explicit, maintainable relational design over clever abstractions.

---

# 30. Expected Phase Completion

At the end of Phase 04 we should have:

Environment
↓
Database Infrastructure
↓
Migration System
↓
Company Schema
↓
Branch Schema
↓
Department Schema
↓
Employee Schema
↓
Database Constraints
↓
Database Integration Tests

But still NO employee application functionality.

The next phase should be able to build Employee Management functionality on top of this schema without redesigning the database foundation.

Generate the specification only.

Do not implement Phase 04 yet.

# Implementation Prompt

# Implementation Prompt

You are acting as a senior PHP backend engineer implementing Phase 04 of the Company Employee Management System.

The approved specification is:

docs/specs/04-domain-schema.md

Read and follow that specification as the authoritative source.

Do not redesign the schema unless implementation reveals a genuine incompatibility or contradiction. If that occurs, explain the issue before introducing a materially different design.

The current branch is:

feature/domain-schema

Phase 01 established the project foundation.

Phase 02 established the HTTP and presentation architecture.

Phase 03 established:

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
MySQL / MariaDB

and:

MigrationInterface
↓
MigrationDiscovery
↓
MigrationRunner
↓
schema_migrations
↓
bin/migrate

Phase 04 must build on those existing components rather than replacing them.

---

# 1. Main Goal

Implement the approved Phase 04 relational domain schema:

Company
↓
Branch
↓
Department

and:

Branch
↓
Employee
↓
Optional Department

The implementation must create:

- companies
- branches
- departments
- employees

using the existing Phase 03 migration infrastructure.

The database must enforce the critical business relationships.

Do not implement application CRUD functionality.

---

# 2. Before Implementation

Inspect the existing project before changing files.

Confirm:

- Phase 03 migration interface
- migration naming convention
- migration discovery behavior
- migration runner behavior
- rollback behavior
- database integration-test setup
- DB test safety checks
- Composer test configuration
- existing test fixture conventions

Reuse the existing architecture.

Do not create duplicate migration infrastructure.

Do not create a second database abstraction.

Do not introduce a framework.

---

# 3. Production Migrations

Create one production migration per domain table under:

database/migrations/

Create them in deterministic dependency order:

1. companies
2. branches
3. departments
4. employees

Use the naming convention already required by Phase 03 migration discovery.

Use distinct monotonically increasing migration versions.

Conceptually:

VersionXXXXXXXXXXXXXXCreateCompanies.php
VersionXXXXXXXXXXXXXXCreateBranches.php
VersionXXXXXXXXXXXXXXCreateDepartments.php
VersionXXXXXXXXXXXXXXCreateEmployees.php

Use actual valid deterministic versions compatible with the existing migration discovery rules.

Do not reuse versions from test fixtures.

Each migration must implement the existing MigrationInterface.

---

# 4. Companies Migration

Implement the `companies` table exactly according to the approved specification.

Required columns:

- id
- code
- name
- email
- phone
- address
- created_at
- updated_at

Important requirements:

- BIGINT UNSIGNED AUTO_INCREMENT primary key
- code VARCHAR(30) NOT NULL
- name VARCHAR(160) NOT NULL
- email VARCHAR(254) NULL
- phone VARCHAR(32) NULL
- address VARCHAR(500) NULL
- created_at DATETIME NOT NULL
- updated_at DATETIME NOT NULL
- UNIQUE(code)
- InnoDB
- utf8mb4
- compatible utf8mb4 collation

Do not add speculative fields.

---

# 5. Branches Migration

Implement the `branches` table.

Required columns:

- id
- company_id
- code
- name
- city
- address
- phone
- status
- created_at
- updated_at

Important constraints:

PRIMARY KEY(id)

UNIQUE(company_id, code)

FOREIGN KEY(company_id)
REFERENCES companies(id)
ON UPDATE RESTRICT
ON DELETE RESTRICT

CHECK(status IN ('active', 'inactive'))

Use:

status VARCHAR(20) NOT NULL DEFAULT 'active'

Do not add a redundant single-column company_id index if the scoped unique index already satisfies the supported engine's foreign-key/index requirement.

---

# 6. Departments Migration

Implement the `departments` table.

Required columns:

- id
- branch_id
- code
- name
- description
- status
- created_at
- updated_at

Important constraints:

PRIMARY KEY(id)

UNIQUE(branch_id, code)

UNIQUE(branch_id, id)

FOREIGN KEY(branch_id)
REFERENCES branches(id)
ON UPDATE RESTRICT
ON DELETE RESTRICT

CHECK(status IN ('active', 'inactive'))

The composite unique key:

(branch_id, id)

is intentional.

It exists so the employee table can enforce:

employee.branch_id

- employee.department_id

against:

department.branch_id

- department.id

Do not remove this constraint merely because `id` is already globally unique.

It serves the composite foreign-key relationship.

---

# 7. Employees Migration

Implement the `employees` table.

Required columns:

- id
- branch_id
- department_id
- employee_code
- first_name
- last_name
- first_name_kana
- last_name_kana
- email
- phone
- position_title
- employee_type
- hire_date
- status
- created_at
- updated_at

Types/nullability must match the specification.

Important constraints:

PRIMARY KEY(id)

UNIQUE(employee_code)

UNIQUE(email)

KEY(branch_id, status)

KEY(branch_id, department_id)

KEY(last_name, first_name)

FOREIGN KEY(branch_id)
REFERENCES branches(id)
ON UPDATE RESTRICT
ON DELETE RESTRICT

FOREIGN KEY(branch_id, department_id)
REFERENCES departments(branch_id, id)
ON UPDATE RESTRICT
ON DELETE RESTRICT

CHECK(employee_type IN ('permanent', 'dispatched'))

CHECK(status IN ('active', 'inactive'))

department_id must be nullable.

branch_id must not be nullable.

---

# 8. Critical Organizational Integrity Rule

The database itself must prevent this state:

# Employee.branch_id

Tokyo Branch

while:

# Employee.department_id

Department owned by Osaka Branch

Do not rely only on PHP validation.

The database-level composite foreign key is required.

Valid:

Tokyo Branch
↓
Tokyo Engineering
↓
Employee

Valid:

Tokyo Branch
↓
No department yet
↓
Employee

Invalid:

Tokyo Branch
↓
Osaka Engineering
↓
Employee

The invalid case must fail at the database level.

---

# 9. Foreign-Key Delete Behavior

Use:

ON DELETE RESTRICT
ON UPDATE RESTRICT

for all Phase 04 domain relationships.

Do not use CASCADE.

Do not automatically delete employees when organizational records are deleted.

Do not use SET NULL for employee department deletion.

If a department is referenced by an employee, deleting that department must fail.

Business records can use status = inactive rather than destructive deletion.

---

# 10. CHECK Constraint Compatibility

The development/test environment includes MySQL/MariaDB through the Phase 03 PDO infrastructure.

Before relying on CHECK constraints, verify that the actual integration-test database engine enforces them.

The implementation must include an integration test proving invalid values are rejected.

At minimum verify:

branches.status invalid value → rejected

departments.status invalid value → rejected

employees.employee_type invalid value → rejected

employees.status invalid value → rejected

Do not rely only on SHOW CREATE TABLE or metadata.

Attempt invalid inserts and verify that the database rejects them.

If the current supported database engine does not enforce CHECK constraints, do not silently pass the phase.

Document the incompatibility and propose the smallest portable alternative consistent with the specification.

---

# 11. Timestamp Ownership

The approved design uses application-managed timestamps.

Do not add:

DEFAULT CURRENT_TIMESTAMP

or:

ON UPDATE CURRENT_TIMESTAMP

unless the specification is deliberately amended.

All domain tables use:

created_at DATETIME NOT NULL
updated_at DATETIME NOT NULL

Integration-test inserts must provide explicit timestamps.

---

# 12. Charset and Engine

Every domain table must explicitly use:

ENGINE=InnoDB

DEFAULT CHARSET=utf8mb4

and the compatible collation selected by the specification/project environment.

Do not introduce mixed character sets between tables.

---

# 13. Migration down()

Each migration must implement safe table removal for its own table.

Conceptually:

CreateEmployees.down()
→ DROP TABLE employees

CreateDepartments.down()
→ DROP TABLE departments

CreateBranches.down()
→ DROP TABLE branches

CreateCompanies.down()
→ DROP TABLE companies

The migration runner must perform rollback according to its existing Phase 03 behavior.

Do not disable foreign-key checks to force incorrect rollback order.

Do not hide dependency errors.

Do not claim DDL operations are universally transactional.

---

# 14. Integration Tests

Add focused Phase 04 real-database integration tests.

Prefer a clear test class such as:

tests/Integration/Database/DomainSchemaIntegrationTest.php

or another location consistent with the current test structure.

Reuse the Phase 03 test database safety mechanism.

Do not create another database test configuration system.

---

# 15. Required Integration Test Coverage

The tests must prove the important schema behavior.

## Migration

Verify:

- all four production migrations are discovered
- migrations execute successfully
- repeated migrate does not reapply them
- tables exist after migration

Do not depend on unrelated test fixture migrations when validating production domain migrations.

---

## Valid hierarchy

Insert:

Company
↓
Branch
↓
Department
↓
Employee

and verify it succeeds.

Use prepared statements for dynamic values.

---

## Nullable department

Insert an employee with:

valid branch_id
department_id = NULL

Verify it succeeds.

---

## Cross-branch department protection

Create:

Company
├── Tokyo Branch
│ └── Tokyo Engineering
│
└── Osaka Branch
└── Osaka Engineering

Attempt:

Employee.branch_id = Tokyo Branch
Employee.department_id = Osaka Engineering

The database must reject the insert.

This is one of the most important Phase 04 acceptance tests.

---

# 16. Unique Constraint Tests

Verify representative uniqueness rules.

At minimum:

duplicate company code
→ rejected

duplicate branch code inside same company
→ rejected

same branch code in another company
→ allowed if test setup includes another company

duplicate department code inside same branch
→ rejected

same department code in another branch
→ allowed

duplicate employee_code
→ rejected

duplicate employee email
→ rejected

Avoid excessive repetitive tests when one focused test can clearly demonstrate a rule.

---

# 17. Foreign-Key Tests

Verify representative invalid relationships.

Examples:

branch with nonexistent company
→ rejected

department with nonexistent branch
→ rejected

employee with nonexistent branch
→ rejected

employee with department from another branch
→ rejected

Use database exceptions as evidence of database-level enforcement.

Do not reproduce the constraint logic only in PHP and call that an integration test.

---

# 18. Delete Restriction Tests

Verify representative delete protection.

At minimum prove that:

company with branch
→ cannot be deleted

branch with employee or department
→ cannot be deleted

department assigned to employee
→ cannot be deleted

Do not delete dependent records automatically.

---

# 19. CHECK Constraint Tests

Attempt invalid values.

Examples:

branch status:

archived

employee type:

contractor

employee status:

deleted

These must fail under the supported engine baseline.

Also verify valid values succeed.

---

# 20. Schema Metadata Verification

Do not make the entire integration suite a brittle metadata snapshot.

However, verify enough schema metadata to catch major migration mistakes.

At minimum verify:

- expected tables exist
- employees.department_id is nullable
- critical foreign keys exist
- critical unique/index relationships exist where practical

Behavioral constraint tests are more important than asserting every metadata field.

---

# 21. Rollback Verification

If the existing Phase 03 MigrationRunner supports rollback, test Phase 04 rollback using the real database.

Verify rollback follows dependency-safe behavior.

After all Phase 04 migrations are rolled back:

employees
departments
branches
companies

must no longer exist.

The Phase 03 infrastructure table:

schema_migrations

may remain.

Do not expect migration files to disappear.

After rollback, migration status may show those migrations as pending.

---

# 22. Test Database Safety

All destructive Phase 04 integration tests must preserve the Phase 03 safety rules.

Require:

APP_ENV=test

and explicit:

DB_TEST_HOST
DB_TEST_PORT
DB_TEST_DATABASE
DB_TEST_USERNAME
DB_TEST_PASSWORD
DB_TEST_CHARSET

The database name must clearly identify itself as a test database, following the Phase 03 rules such as:

company_employee_management_test

Never fall back to:

DB_DATABASE

for destructive integration testing.

Never use production credentials.

Never silently run destructive schema tests against an unverified database.

---

# 23. Test Cleanup

Tests must leave the dedicated test database in a deterministic state.

Use the existing migration runner where practical.

Respect foreign-key dependency order.

Do not use:

SET FOREIGN_KEY_CHECKS=0

as a shortcut for incorrect cleanup design unless there is an exceptional documented reason.

Prefer:

employees
↓
departments
↓
branches
↓
companies

for destructive cleanup.

The schema_migrations infrastructure must remain valid.

---

# 24. Unit Tests

Do not add unit tests merely to increase the test count.

Phase 04 is mostly declarative schema/migration work.

Add unit tests only if new non-trivial PHP logic is introduced.

Migration behavior requiring a real database belongs in integration tests.

---

# 25. Existing Test Regression

After implementation, run the existing test suite.

Phase 01–03 behavior must remain valid.

In particular:

- HTTP tests must continue working without database credentials.
- Database infrastructure unit tests must continue passing.
- Phase 03 database integration tests must continue passing under the explicit test environment.
- Normal application bootstrap must not connect to the database merely because production migrations now exist.

---

# 26. HTTP Independence

Do not modify ApplicationBootstrap so it eagerly creates PDO.

Do not execute migrations from:

public/index.php

HttpKernel

Router

Middleware

Controller

ViewRenderer

The existence of production migrations must not make HTTP startup database-dependent.

---

# 27. Do Not Add Application Layers Yet

Do not create:

CompanyRepository
BranchRepository
DepartmentRepository
EmployeeRepository

Do not create:

CompanyService
EmployeeService

Do not create:

Company
Branch
Department
Employee

PHP domain objects solely because tables exist.

Do not create CRUD controllers.

Do not create routes.

Do not create forms.

Those belong to future phases.

---

# 28. Authentication Boundary

Do not add:

password
password_hash
login_email
remember_token
api_token
role
permission

to employees.

Employee email remains business contact information.

Future authentication must be designed separately.

---

# 29. Scope Guard

Before finalizing implementation, inspect the changed files.

Every changed file must have a direct Phase 04 reason.

Expected changes should primarily be:

database/migrations/_
tests/Integration/Database/_
docs/specs/04-domain-schema.md
docs/prompts/04-domain-schema.md

Existing infrastructure files should only change if Phase 04 exposes a genuine defect or compatibility issue.

If you change:

src/Http/_
src/View/_
routes/_
public/_
authentication-related files

stop and justify why.

Normally those files should remain untouched.

---

# 30. Documentation

Update README only if a small Phase 04 addition materially improves developer setup, such as documenting how to apply production migrations.

Do not rewrite unrelated documentation.

Do not add credentials.

Keep integration-test commands consistent with Phase 03.

---

# 31. Verification Commands

After implementation, provide the exact commands needed to verify the phase.

At minimum include:

PHP syntax validation where useful

normal test suite

real database integration tests

migration status

migration execution if manual verification is appropriate

git diff --stat

git status

Do not automatically commit or merge unless explicitly instructed.

---

# 32. Expected Implementation Shape

The final Phase 04 implementation should look conceptually like:

database/
└── migrations/
├── Version...CreateCompanies.php
├── Version...CreateBranches.php
├── Version...CreateDepartments.php
└── Version...CreateEmployees.php

tests/
└── Integration/
└── Database/
└── DomainSchemaIntegrationTest.php

No repository/application/HTTP layer should be required.

---

# 33. Acceptance Checklist

Before declaring Phase 04 implementation complete, verify:

[ ] companies migration exists

[ ] branches migration exists

[ ] departments migration exists

[ ] employees migration exists

[ ] deterministic migration order is correct

[ ] InnoDB used

[ ] utf8mb4 used

[ ] BIGINT UNSIGNED PK/FK types match

[ ] scoped branch code uniqueness enforced

[ ] scoped department code uniqueness enforced

[ ] employee_code uniqueness enforced

[ ] employee email uniqueness enforced

[ ] employee branch required

[ ] employee department nullable

[ ] cross-branch department assignment rejected by DB

[ ] status CHECK constraints enforced

[ ] employee_type CHECK constraint enforced

[ ] parent deletion restrictions enforced

[ ] migrations idempotently tracked

[ ] rollback behavior verified

[ ] isolated test DB used

[ ] existing tests still pass

[ ] HTTP bootstrap still works without DB

[ ] no CRUD added

[ ] no repository added

[ ] no service added

[ ] no auth added

[ ] no UI added

[ ] no ORM/framework added

[ ] working tree contains only Phase 04 changes

---

# 34. Final Output

After implementation, report:

1. Files created
2. Files modified
3. Final migration order
4. Important schema decisions implemented
5. Integration tests added
6. CHECK constraint compatibility result
7. Test commands run
8. Test results
9. Any deviations from the specification
10. Remaining Phase 04 verification steps

Do not commit.

Do not merge.

Stop after implementation and verification reporting so the changes can be reviewed before Git operations.
