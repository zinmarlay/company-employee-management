Phase 06 — Dispatch Company & Contract Management

You are continuing an existing Pure PHP Company Employee Management System.

Do NOT rebuild the project from scratch.

Before implementing anything, inspect the existing codebase, architecture, database schema, migrations, tests, and conventions established in Phases 01–05.

Preserve the existing architecture and coding style unless a change is clearly necessary.

⸻

1. Goal

Implement Phase 06:

Dispatch Company & Dispatch Contract Management.

The system already supports employee management.

This phase adds the domain required to manage dispatched employees:

- Dispatch companies
- Dispatch contract history
- Contract creation
- Contract editing
- Contract renewal
- Contract status / expiration awareness
- Employee contract history display

Do not implement authentication, authorization, or CSRF protection in this phase unless they already exist in the project.

Do not introduce a PHP framework, ORM, SPA framework, or API architecture.

Continue using Pure PHP + SSR + PDO.

⸻

2. Existing Architecture

Preserve the existing request flow:

Browser
→ public/index.php
→ ApplicationBootstrap
→ Request
→ HttpKernel
→ Router
→ Middleware Pipeline
→ Controller
→ Application Service
→ Repository Interface
→ PDO Repository
→ Database
→ ViewRenderer
→ Response
→ ResponseEmitter
→ Browser

Maintain separation of concerns.

Controller:

- HTTP concerns only
- Parse request
- Call service
- Return response / render view

Service:

- Application use cases
- Business rules
- Workflow coordination

Repository Interface:

- Persistence contract

PDO Repository:

- SQL and database persistence

View:

- Presentation only
- No SQL
- No repository calls
- No service calls
- No direct access to global request state

⸻

3. First Inspect the Existing Schema

Before writing a new migration, inspect all existing migrations from Phase 04 and later.

Determine whether tables for dispatch companies and dispatch contracts already exist.

Do NOT blindly create duplicate tables.

If the approved Phase 04 schema already contains the required tables and constraints, reuse them.

Only create a migration if the current database schema genuinely lacks something required by this specification.

If a migration is necessary, follow the existing MigrationDiscovery filename convention exactly.

⸻

4. Dispatch Company Domain

A dispatch company represents an external staffing company that supplies dispatched employees.

Required conceptual fields:

- id
- name
- code if the existing schema supports it
- phone
- email
- address
- status
- created_at
- updated_at

Use the existing database schema as the source of truth for exact column names and nullable rules.

Do not modify an already-approved schema merely to make implementation easier.

Status should follow existing project conventions.

Prefer:

- active
- inactive

if that matches the existing schema.

⸻

5. Dispatch Company Features

Implement:

List

GET /dispatch-companies

Display dispatch companies with deterministic ordering.

Support the existing project conventions for status display.

Detail

GET /dispatch-companies/{id}

Display company information.

Where practical, show related dispatch contract information without introducing inefficient per-row queries.

Create

GET /dispatch-companies/create

POST /dispatch-companies

Validate input before persistence.

After successful creation:

POST
→ 303 See Other
→ GET detail page

Edit

GET /dispatch-companies/{id}/edit

POST /dispatch-companies/{id}

Validate input.

After successful update:

POST
→ 303 See Other
→ GET detail page

Deactivation

Do not hard-delete a dispatch company if the existing domain model uses active/inactive lifecycle management.

Use a confirmation page:

GET /dispatch-companies/{id}/deactivate

Perform mutation only through:

POST /dispatch-companies/{id}/deactivate

Repeated deactivation should be safe/idempotent where practical.

Do not use GET to mutate state.

⸻

6. Dispatch Contract Domain

A dispatch contract represents the contractual assignment/history of a dispatched employee.

The exact database columns must follow the existing approved schema.

Conceptually, a contract should connect:

Employee
↕
Dispatch Contract
↕
Dispatch Company

Contract information should include the existing equivalents of:

- employee
- dispatch company
- contract start date
- contract end date
- status if present in the schema
- created_at
- updated_at

Do not invent duplicate columns if equivalent fields already exist.

⸻

7. Employee Type Rule

Only employees whose employee type is:

dispatched

may receive a dispatch contract.

A permanent employee must not be assigned a dispatch contract.

This must be enforced in the application/service layer.

Do not rely only on UI restrictions.

⸻

8. Dispatch Company Rule

A new dispatch contract must reference a valid dispatch company.

For a new contract, the company must be eligible according to the current schema/business lifecycle.

If the project uses active/inactive status, new contracts should normally require an active dispatch company.

Historical contracts must remain readable even if the company later becomes inactive.

⸻

9. Contract Date Validation

Contract dates must be strictly validated.

Required rule:

start_date <= end_date

Reject impossible calendar dates.

Examples:

2026-02-30
→ invalid

Start:
2026-10-01

End:
2026-09-30
→ invalid

Use strict Y-m-d validation consistent with Phase 05 conventions.

⸻

10. Contract History

The system must preserve dispatch contract history.

Do NOT overwrite an old contract simply because an employee receives a renewed contract.

Example:

Employee A

Contract 1
2026-01-01
→ 2026-03-31

Contract 2
2026-04-01
→ 2026-06-30

Both records must remain available as history.

Contract renewal must preserve the previous contract.

⸻

11. Contract Renewal

Implement an explicit renewal workflow.

Suggested routes:

GET /dispatch-contracts/{id}/renew
POST /dispatch-contracts/{id}/renew

Renewal should use the previous contract as the basis for the form where appropriate.

The renewal operation must create a new contract record.

It must NOT mutate the historical contract into the new period.

After successful renewal:

POST
→ 303 See Other
→ appropriate contract/employee detail page

⸻

12. Contract Editing

Existing contract records may be editable if required by the approved project design.

Suggested routes:

GET /dispatch-contracts/{id}/edit
POST /dispatch-contracts/{id}

Editing a contract and renewing a contract are different operations.

Edit:
Correct information belonging to an existing contract.

Renew:
Create the next contract period while preserving history.

Keep these concepts separate.

⸻

13. Contract Overlap

Prevent invalid overlapping contract periods for the same employee.

Example:

Existing:

2026-01-01
→ 2026-03-31

New:

2026-03-01
→ 2026-05-31

This overlaps and should be rejected.

Adjacent periods are acceptable if they do not overlap.

Example:

Contract 1:
2026-01-01
→ 2026-03-31

Contract 2:
2026-04-01
→ 2026-06-30

Valid.

The overlap check must be implemented in the application/service workflow and supported by appropriate repository queries.

When editing an existing contract, exclude that contract’s own ID from the overlap check.

⸻

14. Contract Expiration Classification

Provide reusable application logic for contract expiration awareness.

Given an appropriate reference date, classify contracts conceptually as:

expired
expiring_7
expiring_30
normal

Rules:

expired:
end date is before the reference date

expiring_7:
end date is from reference date through 7 days ahead

expiring_30:
end date is more than 7 days ahead and within 30 days

normal:
more than 30 days remaining

Be precise about boundary dates.

Do not scatter this date logic across views.

Put it in an appropriate application/domain service/value logic that can be unit tested.

Use the existing Clock abstraction instead of directly depending on the machine’s current time.

⸻

15. Employee Detail Integration

Update Employee Detail so a dispatched employee can display dispatch-related information.

Where applicable show:

- Dispatch company
- Current/latest relevant contract
- Contract start date
- Contract end date
- Expiration classification
- Contract history

Permanent employees should not display misleading dispatch-contract information.

Avoid N+1 query patterns.

⸻

16. Input DTOs

Continue the Phase 05 approach of explicit structured input.

Create appropriate DTOs such as:

DispatchCompanyInput

DispatchContractInput

Exact naming may follow existing conventions.

Do not pass raw $\_POST arrays throughout the application.

Use allowlisted input fields.

Unexpected fields must not be persisted.

⸻

17. Validation

Create centralized validators consistent with Phase 05.

Examples:

DispatchCompanyInputValidator

DispatchContractInputValidator

Validate:

- required fields
- maximum lengths
- email format where applicable
- positive integer IDs
- strict dates
- enum/status values where applicable

Return structured validation errors suitable for SSR forms.

Preserve submitted values when validation fails.

Validation failure should return:

422 Unprocessable Content

unless the existing HTTP abstraction uses an equivalent established convention.

⸻

18. Repository Interfaces

Introduce focused repository interfaces.

Possible interfaces:

DispatchCompanyRepositoryInterface

DispatchContractRepositoryInterface

Use the actual domain requirements to determine methods.

Avoid generic CRUD repositories.

Possible company operations:

listBasic()
findById()
insert()
update()
deactivate()
exists…

Possible contract operations:

findById()
findByEmployeeId()
findHistoryByEmployeeId()
insert()
update()
hasOverlap(…)

Method names should follow existing project conventions where possible.

⸻

19. PDO Repositories

Implement concrete PDO repositories.

Examples:

PdoDispatchCompanyRepository

PdoDispatchContractRepository

Rules:

- Use prepared statements
- No string-concatenated untrusted SQL
- Deterministic ordering
- Explicit selected columns where practical
- Correct joins
- No SQL inside controllers/services/views
- Translate persistence failures when they have meaningful domain/application semantics

⸻

20. Application Services

Introduce application services appropriate to the use cases.

Possible design:

DispatchCompanyService

DispatchContractService

Responsibilities include:

DispatchCompanyService:

- list
- detail
- create
- edit/update
- deactivate

DispatchContractService:

- detail
- create
- edit
- renew
- employee contract history
- overlap validation
- dispatched-employee validation
- expiration classification coordination

Do not turn services into SQL containers.

Repositories own persistence logic.

⸻

21. Controllers

Create focused controllers.

Possible design:

DispatchCompanyController

DispatchContractController

Controllers must remain thin.

Controller responsibilities:

Request
→ Allowlisted Input
→ DTO
→ Service
→ Response

Do not place SQL in controllers.

Do not place large business rules in controllers.

⸻

22. Views

Create SSR views following the existing project structure.

Suggested dispatch company views:

resources/views/dispatch-companies/index.php
resources/views/dispatch-companies/show.php
resources/views/dispatch-companies/create.php
resources/views/dispatch-companies/edit.php
resources/views/dispatch-companies/deactivate.php
resources/views/dispatch-companies/\_form.php

Suggested dispatch contract views:

resources/views/dispatch-contracts/create.php
resources/views/dispatch-contracts/edit.php
resources/views/dispatch-contracts/renew.php
resources/views/dispatch-contracts/show.php
resources/views/dispatch-contracts/\_form.php

Exact view structure may be adjusted to fit existing conventions.

All dynamic output must be escaped using the project’s existing escaping mechanism.

No SQL or repository/service calls from views.

⸻

23. HTTP Behavior

Use established HTTP semantics.

200
→ successful GET/render

303
→ successful POST followed by redirect

404
→ employee/company/contract does not exist

405
→ unsupported HTTP method according to existing router behavior

422
→ validation/business input failure

Do not return 500 for normal user validation failures.

⸻

24. Lazy Database Connection

Preserve Phase 05 lazy database behavior.

Application bootstrap must not unnecessarily open the database connection for routes that do not require it.

The root route should continue to work without database credentials if that is already guaranteed by the current architecture.

Do not regress this behavior.

⸻

25. Clock / Time

Reuse the existing:

Clock
SystemClock

abstraction.

Do not scatter:

new DateTimeImmutable(‘now’)

through application services.

Contract expiration behavior must be deterministic and testable using an injected/fake clock.

Use the project’s established UTC convention for application timestamps.

⸻

26. Database Safety

If database schema changes are necessary:

- inspect existing migrations first
- preserve foreign keys
- preserve unique constraints
- use the existing migration runner
- follow the exact migration filename convention
- update schema tests

Do not modify old migration files that have already been accepted/applied merely to simplify the new feature.

Prefer a new forward migration if a genuine schema change is required.

⸻

27. Testing — Unit

Add unit tests for important business rules.

At minimum test:

- Dispatch company input validation
- Dispatch contract input validation
- Permanent employee cannot receive dispatch contract
- Invalid company rejected
- Invalid dates rejected
- start_date > end_date rejected
- Overlapping contract rejected
- Non-overlapping adjacent contract accepted
- Renewal preserves old contract and creates new contract behavior
- Expired classification
- Expiring within 7 days classification
- Expiring within 30 days classification
- Normal classification
- Boundary dates

Use fake repositories/fakes/stubs consistent with existing tests.

Use a deterministic clock.

⸻

28. Testing — Repository Integration

Add real database integration tests.

Use only the test database configuration:

APP_ENV=test
DB_TEST_HOST
DB_TEST_PORT
DB_TEST_DATABASE
DB_TEST_USERNAME
DB_TEST_PASSWORD
DB_TEST_CHARSET

Never allow destructive integration tests to run against a normal development/production database.

Test repository behaviors such as:

- Insert dispatch company
- Read company
- Update company
- Deactivate company
- Insert contract
- Read contract
- Contract history ordering
- Contract update
- Overlap query behavior
- Relevant joins

Clean up fixtures safely according to existing test conventions.

⸻

29. Testing — HTTP / Feature

Add HTTP/feature tests for:

Dispatch Companies:

- list
- detail
- create form
- create success
- create validation failure
- edit form
- update success
- deactivate confirmation
- deactivate success
- 404 behavior

Dispatch Contracts:

- create form
- create success
- invalid employee type
- invalid date
- overlap rejection
- edit
- renewal form
- renewal success
- contract detail/history where applicable
- 404 behavior

Verify:

303 redirects
422 validation responses
escaped dynamic output
unsupported method behavior where appropriate

Also ensure existing Phase 01–05 tests continue to pass.

⸻

30. Regression Requirements

Do not break:

- Root route
- Existing HTTP architecture
- Employee list
- Employee detail
- Employee create
- Employee edit
- Employee deactivate
- Existing migrations
- Lazy PDO behavior
- Existing unit tests
- Existing integration tests
- Existing HTTP tests

Run the complete test suite after implementation.

⸻

31. Security

Continue using:

- PDO prepared statements
- Output escaping
- Input allowlists
- Centralized validation
- Database constraints
- POST for state-changing operations

Do not implement fake UI-only authorization.

Authentication / Authorization / CSRF are outside this phase unless already implemented by the existing application.

⸻

32. Code Quality

Use:

declare(strict_types=1);

where consistent with the existing project.

Follow existing:

- namespaces
- PSR-4 structure
- constructor injection
- final/readonly conventions
- type declarations
- response abstractions
- exception handling
- testing conventions

Avoid:

- God classes
- Generic BaseRepository
- Generic CRUD Service
- Service Locator
- Global PDO access
- SQL in Controller
- SQL in View
- Business rules in View
- Raw $\_POST throughout application layers

⸻

33. Documentation

Create/update:

docs/prompts/06-dispatch-contract-management.md

docs/specs/06-dispatch-contract-management.md

The specification should document:

- Scope
- Routes
- Business rules
- Contract lifecycle
- Renewal behavior
- Overlap behavior
- Expiration classification
- Validation
- Repository responsibilities
- Service responsibilities
- HTTP behavior
- Test strategy
- Explicit non-goals

⸻

34. Non-Goals

Do NOT implement unless already part of the current architecture:

- Authentication
- Authorization
- CSRF
- Laravel
- Symfony
- ORM
- React
- Vue
- REST API
- Payroll
- Attendance
- Billing
- Email notifications
- Scheduled jobs
- Dashboard redesign

Contract expiration classification should be implemented now, but a larger dashboard/notification system may remain for a later phase.

⸻

35. Implementation Process

Before coding:

1. Inspect existing project files.
2. Inspect Phase 04 schema.
3. Inspect Phase 05 Employee implementation.
4. Inspect existing tests.
5. Identify whether schema changes are actually required.
6. Write/update Phase 06 specification.
7. Implement incrementally.
8. Add tests alongside the implementation.
9. Run focused tests.
10. Run real DB integration tests using only the test database.
11. Run the complete regression suite.

Do not rewrite working Phase 01–05 architecture without a concrete reason.

⸻

36. Completion Criteria

Phase 06 is complete only when:

- Dispatch company workflow works
- Dispatch contract workflow works
- Contract history is preserved
- Renewal creates a new historical record
- Permanent employees cannot receive dispatch contracts
- Company validity is enforced
- Contract dates are strictly validated
- Contract overlap is prevented
- Expiration classification works with correct boundaries
- Employee detail integrates dispatch information correctly
- SSR output is escaped
- POST mutations use 303 redirects
- Validation failures use 422
- Missing resources use 404
- Lazy PDO behavior is preserved
- Unit tests pass
- Real DB integration tests pass
- HTTP/feature tests pass
- Existing Phase 01–05 tests still pass
- Full test suite passes
- No destructive test can accidentally target the normal application database

At the end, report:

1. Files added
2. Files modified
3. Any migration added and why
4. Routes added
5. Business rules implemented
6. Tests added
7. Focused test results
8. Real DB integration test results
9. Full regression test result
10. Any remaining limitations

Do not commit or merge automatically.
Stop after implementation and testing so the changes can be reviewed before Git commit.

# implement prompt

Implement Phase 06 — Dispatch Company and Dispatch Contract Management for the existing Pure PHP Company Employee Management System.

IMPORTANT:
Read and follow these existing specification files first:

- docs/prompts/06-dispatch-contract-management.md
- docs/specs/06-dispatch-contract-management.md

Also inspect the current implementation before changing anything. Do not replace the existing architecture with a new architecture.

CURRENT BRANCH
Work only on the currently checked-out branch:

feature/dispatch-contract-management

Do not create, switch, merge, delete, or commit Git branches.
Do not commit automatically.

==================================================

1. # EXISTING PROJECT ARCHITECTURE

This is a Pure PHP SSR application.

Preserve the existing request flow:

Browser
→ public/index.php
→ ApplicationBootstrap
→ Request
→ HttpKernel
→ Router
→ Middleware Pipeline
→ Controller
→ Application Service
→ Repository Interface
→ PDO Repository
→ MySQL/MariaDB
→ ViewRenderer
→ Response
→ ResponseEmitter
→ Browser

Do NOT introduce:

- Laravel
- Symfony
- ORM
- Active Record
- query builder
- React
- Vue
- SPA architecture
- REST API architecture
- generic repository base classes
- unnecessary factories/managers/handlers

Controllers must remain thin.

Business/use-case rules belong in application services.

SQL belongs only in PDO repositories.

Views must never access repositories, services, PDO, or the database.

Use explicit dependency injection through the existing ApplicationBootstrap composition root.

================================================== 2. CURRENT DATABASE FINDING
==================================================

Existing repository migrations are:

Version20260922000100CreateCompanies.php
Version20260922000200CreateBranches.php
Version20260922000300CreateDepartments.php
Version20260922000400CreateEmployees.php

The isolated test database was inspected and currently contains no tables.

There is no existing approved dispatch-company or dispatch-contract schema.

Therefore Phase 06 REQUIRES a forward migration.

Use:

database/migrations/
Version20260922000500CreateDispatchCompaniesAndContracts.php

The migration must follow the exact existing MigrationInterface style.

Migration up():

1. create dispatch_companies
2. create dispatch_contracts

Migration down():

1. drop dispatch_contracts
2. drop dispatch_companies

Do NOT edit accepted Phase 04 migrations.

Use:

- BIGINT UNSIGNED IDs
- InnoDB
- utf8mb4
- utf8mb4_unicode_ci
- named indexes
- named foreign keys
- named CHECK constraints where consistent with existing migrations
- ON UPDATE RESTRICT
- ON DELETE RESTRICT

dispatch_companies baseline:

id BIGINT UNSIGNED PK AUTO_INCREMENT
code VARCHAR(30) NOT NULL UNIQUE
name VARCHAR(160) NOT NULL
phone VARCHAR(32) NULL
email VARCHAR(254) NULL
address VARCHAR(500) NULL
status VARCHAR(20) NOT NULL DEFAULT 'active'
created_at DATETIME NOT NULL
updated_at DATETIME NOT NULL

status:
active | inactive

dispatch_contracts baseline:

id BIGINT UNSIGNED PK AUTO_INCREMENT
employee_id BIGINT UNSIGNED NOT NULL
dispatch_company_id BIGINT UNSIGNED NOT NULL
start_date DATE NOT NULL
end_date DATE NOT NULL
created_at DATETIME NOT NULL
updated_at DATETIME NOT NULL

Add:

INDEX(employee_id, start_date, end_date)
INDEX(dispatch_company_id)

FK employee_id → employees(id)
FK dispatch_company_id → dispatch_companies(id)

CHECK start_date <= end_date.

================================================== 3. PHASE 05 PATTERNS TO REUSE
==================================================

Inspect and follow the conventions in:

src/Application/DTO/EmployeeInput.php
src/Application/Validation/EmployeeInputValidator.php
src/Application/Validation/EmployeeValidationResult.php
src/Application/Employee/EmployeeService.php

src/Domain/Employee/EmployeeRepositoryInterface.php
src/Domain/Organization/BranchReadRepositoryInterface.php
src/Domain/Organization/DepartmentReadRepositoryInterface.php

src/Infrastructure/Persistence/PdoEmployeeRepository.php
src/Infrastructure/Persistence/PdoBranchReadRepository.php
src/Infrastructure/Persistence/PdoDepartmentReadRepository.php

Also inspect:

src/Http/Controllers/EmployeeController.php
routes/web.php
src/Bootstrap/ApplicationBootstrap.php

Do not blindly duplicate EmployeeService.
Reuse its architectural conventions while keeping dispatch responsibilities focused.

================================================== 4. DTO AND VALIDATION
==================================================

Create explicit immutable input structures equivalent to:

DispatchCompanyInput
DispatchContractInput

Company fields:

code
name
phone
email
address

Do not accept status from normal create/edit requests.

New companies always start as active.

Company validation:

- scalar input only
- trim strings
- code required, max 30
- name required, max 160
- phone optional, max 32
- email optional, max 254 and valid email
- address optional, max 500
- normalize empty optional values to null
- preserve submitted form values
- structured field errors
- ignore/reject unexpected writable fields according to existing Phase 05 convention

Contract fields:

employee_id
dispatch_company_id
start_date
end_date

Validation:

- IDs must be strict positive integers
- reject arrays/non-scalar misuse
- dates must be exact Y-m-d
- impossible dates must fail
- 2026-1-1 must fail
- require start_date <= end_date
- preserve submitted values and field errors

Business rules such as employee type, company lifecycle, and overlap belong in services, not only validators.

================================================== 5. DOMAIN AND REPOSITORIES
==================================================

Add focused interfaces under the existing domain conventions:

DispatchCompanyRepositoryInterface
DispatchContractRepositoryInterface

Add PDO implementations:

PdoDispatchCompanyRepository
PdoDispatchContractRepository

Use the existing LazyPdoConnection mechanism.

Never eagerly connect to PDO during application bootstrap.

Root/setup must continue working without DB credentials/running database where currently guaranteed.

All SQL must use prepared statements for variable values.

Use deterministic ordering.

Avoid SELECT \* where practical.

Avoid N+1 queries.

Company repository/use cases need operations equivalent to:

listBasic(limit)
findById(id)
insert(...)
update(...)
deactivate(...)
find contracts for company if ownership fits here

Contract repository/use cases need operations equivalent to:

findById(id)
findHistoryByEmployeeId(employeeId)
findByCompanyId(companyId)
hasOverlap(employeeId, startDate, endDate, exceptId)
insert(...)
update(...)

The overlap condition is inclusive:

candidate_start <= existing_end
AND candidate_end >= existing_start

Edit must exclude its own ID.

Adjacent periods are valid:

2026-01-01 → 2026-03-31
2026-04-01 → 2026-06-30

================================================== 6. APPLICATION SERVICES
==================================================

Add focused services equivalent to:

DispatchCompanyService
DispatchContractService

Use the existing Clock abstraction.

Do not call "now" directly throughout services/views.

Dispatch company behavior:

- list
- detail
- create
- edit
- deactivate
- deactivation is idempotent
- create always defaults status to active
- ordinary edit must not change status
- inactive company remains readable

Dispatch contract behavior:

Only:

employee_type === 'dispatched'

may receive/create/edit/renew a dispatch contract.

Create:

- employee must exist
- employee must be dispatched
- dispatch company must exist
- dispatch company must be active
- valid date range
- no overlap
- insert contract

Edit:

- revalidate employee
- revalidate company
- revalidate dates
- check overlap excluding own ID

Use the historical-safety policy from the specification:

If an existing contract already references a company that has since become inactive, allow date/employee corrections while retaining that same inactive company.

Do NOT allow changing the contract to another inactive company.

Create and renewal always require an active company.

Renewal:

Renewal MUST INSERT a new row.

It MUST NOT update or delete the source contract.

Source contract must remain unchanged.

================================================== 7. EXPIRATION CLASSIFICATION
==================================================

Implement small reusable, unit-testable expiration classification logic.

Use the injected Clock.

Classification:

end_date < reference date
→ expired

reference date <= end_date <= reference date + 7 days
→ expiring_7

reference date + 7 days < end_date <= reference date + 30 days
→ expiring_30

end_date > reference date + 30 days
→ normal

Test exact boundaries.

Do not store this derived classification in the database.

================================================== 8. HTTP ROUTES
==================================================

Add SSR routes.

Dispatch companies:

GET /dispatch-companies
GET /dispatch-companies/create
POST /dispatch-companies
GET /dispatch-companies/{id}/deactivate
POST /dispatch-companies/{id}/deactivate
GET /dispatch-companies/{id}/edit
POST /dispatch-companies/{id}
GET /dispatch-companies/{id}

Dispatch contracts:

GET /dispatch-contracts/create
POST /dispatch-contracts
GET /dispatch-contracts/{id}/renew
POST /dispatch-contracts/{id}/renew
GET /dispatch-contracts/{id}/edit
POST /dispatch-contracts/{id}
GET /dispatch-contracts/{id}

IMPORTANT:

Register static/specific routes before dynamic /{id} routes according to the current router behavior.

Successful state-changing POST:
303 See Other.

Validation/business rule failure: 422.

Missing/invalid resource:
404 using existing NotFoundException behavior.

Unsupported methods:
existing 405 behavior.

================================================== 9. EMPLOYEE DETAIL INTEGRATION
==================================================

Extend employee detail carefully.

For employee_type=dispatched show:

- dispatch company/current assignment when available
- current/latest relevant contract
- contract history
- create contract action
- contract detail/edit/renew actions where appropriate

For permanent employees:

Do NOT show a misleading dispatch section.
Do NOT show a create-dispatch-contract action.

Do not perform SQL or date calculations in the view.

Do not create N+1 queries.

================================================== 10. UI FOUNDATION — MUST REUSE CURRENT UI
==================================================

The project now has a Material Design-inspired SSR UI foundation.

Reuse it.

Inspect:

public/assets/css/app.css

resources/views/layouts/\*
resources/views/partials/page-header.php
resources/views/partials/status-chip.php
resources/views/partials/empty-state.php

and the current employee views.

Do NOT create a separate design system.
Do NOT introduce Material UI React.
Do NOT use inline page-specific styling unless truly necessary.

Create views under:

resources/views/dispatch-companies/
resources/views/dispatch-contracts/

Expected company views:

index.php
show.php
create.php
edit.php
deactivate.php
\_form.php

Expected contract views:

show.php
create.php
edit.php
renew.php
\_form.php

Match the existing:

- typography
- cards
- buttons
- tables
- forms
- page headers
- status chips
- empty states
- responsive behavior
- sidebar/topbar layout

All dynamic output must use the existing HtmlEscaper convention.

================================================== 11. ENGLISH + JAPANESE LOCALIZATION
==================================================

The project now supports English and Japanese.

Inspect and reuse:

src/Localization/Locale.php
src/Localization/Translator.php
src/Http/Middleware/LocaleMiddleware.php

resources/lang/en.php
resources/lang/ja.php

Do NOT hard-code new user-facing Phase 06 labels/messages into views when they belong in translation files.

Add English and natural Japanese translations for Phase 06.

Examples:

Dispatch Companies
→ 派遣会社

Dispatch Contracts
→ 派遣契約

Contract History
→ 契約履歴

Start Date
→ 契約開始日

End Date
→ 契約終了日

Renew Contract
→ 契約更新

Use natural Japanese suitable for an internal employee-management system.

For status/lifecycle semantics:

active → 在籍/有効 as contextually appropriate
inactive dispatch company → 無効

Do not translate inactive company as 退職.

Expiration labels should be natural Japanese, for example:

expired
→ 契約終了

expiring_7
→ 7日以内に終了

expiring_30
→ 30日以内に終了

normal
→ 通常

Follow the existing translation key structure rather than inventing a parallel localization system.

Validation and expected business-rule messages shown to users must also support EN/JA using the current translation approach.

================================================== 12. TEST DATABASE SAFETY
==================================================

Integration tests may operate ONLY on the isolated test database.

Preserve/enforce:

APP_ENV=test

required:
DB_TEST_HOST
DB_TEST_PORT
DB_TEST_DATABASE
DB_TEST_USERNAME
DB_TEST_PASSWORD
DB_TEST_CHARSET

DB_TEST_DATABASE must end in:

\_test

It must differ from DB_DATABASE.

Never run destructive test setup against the normal application database.

IMPORTANT:

Existing database integration teardown/reset logic currently knows only:

employees
departments
branches
companies
...

After adding Phase 06 foreign keys, update relevant test cleanup/reset helpers so dependency order is:

dispatch_contracts
dispatch_companies
employees
departments
branches
companies

before schema_migrations where applicable.

Do not weaken existing database safety checks.

================================================== 13. TESTING
==================================================

Add comprehensive tests following existing conventions.

Unit tests:

- company validation
- contract validation
- scalar/array misuse
- maximum lengths
- optional null normalization
- invalid email
- strict date validation
- impossible dates
- reversed dates
- permanent employee rejection
- missing employee
- inactive/missing company
- overlap
- adjacency
- edit excluding own ID
- renewal inserts new row
- renewal preserves source
- company create defaults active
- ordinary edit does not alter status
- idempotent deactivation
- expiration classifications
- exact 7/30-day boundaries
- fake Clock / UTC timestamps
- form values/errors preserved

Repository integration tests:

- migration/schema
- company insert/read/update/deactivate
- deterministic ordering
- contract insert/read/update
- joins
- employee history ordering
- company contract history
- overlap
- adjacency
- excluded own ID
- inactive historical company readable
- foreign key enforcement
- UTF-8/multibyte
- apostrophes
- HTML-sensitive text
- DATE values
- UTC timestamps

HTTP/feature tests:

- company list/detail/create/edit/deactivate
- contract create/detail/edit/renew
- 200
- 303
- 404
- 405
- 422
- value/error preservation
- escaped output
- permanent employee behavior
- inactive company behavior
- overlap behavior
- adjacency behavior
- renewal source preservation
- employee detail integration
- permanent employee detail hiding dispatch section
- Japanese rendering/localization where appropriate

Preserve existing Phase 01–05 tests.

================================================== 14. REGRESSION AND VERIFICATION
==================================================

Run focused tests first.

Then run the full test suite.

Then run real database integration tests only with the explicitly configured isolated test environment.

Confirm:

- root/setup still works without eagerly opening PDO
- employee workflows still work
- English UI works
- Japanese UI works
- language switching still works
- new dispatch UI uses current design system
- no N+1 behavior was introduced
- no SQL exists in controllers/views/services
- successful POSTs use 303

Do not claim integration tests passed if the test DB was unavailable.
Report skipped tests accurately.

================================================== 15. DOCUMENTATION
==================================================

Update the existing Phase 06 specification/prompt only when implementation findings require clarification.

Do not overwrite the user's documentation unnecessarily.

Document the migration finding:

- repository migrations previously ended at employees
- isolated test DB contained no approved dispatch tables
- therefore the forward Phase 06 migration was required

================================================== 16. STOP CONDITION
==================================================

After implementation:

DO NOT commit.
DO NOT merge.
DO NOT push.

Stop and give me a review report containing:

1. files added
2. files modified
3. migration added and why
4. routes added
5. architecture decisions
6. business rules implemented
7. UI/localization changes
8. tests added
9. focused test result
10. isolated real-DB integration result
11. full regression result
12. skipped tests, if any
13. remaining limitations or concerns

Before finishing, also show:

git status --short

I will review the implementation and perform Git operations myself.
