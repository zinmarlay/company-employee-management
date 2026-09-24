Specification Prompt

You are working on an existing learning-oriented Pure PHP project named Company Employee Management System.

Your task is to design the specification for:

Phase 05 – Employee Management

Do not implement the feature yet.

First inspect the existing project structure, architecture, Phase 01–04 specifications, migrations, tests, routing, presentation layer, database infrastructure, and established conventions. Reuse the existing architecture rather than inventing a parallel architecture.

The goal of this phase is to introduce the first real business CRUD feature while preserving clear architectural boundaries.

⸻

1. Project Context

The project is a server-rendered Pure PHP application.

Technology constraints:

- PHP 8.x
- Pure PHP
- MySQL / MariaDB
- PDO
- Composer
- PSR-4 autoloading
- Server-side rendered PHP views
- HTML5 / CSS / JavaScript
- PHPUnit
- Git / GitHub

Do not introduce:

- Laravel
- Symfony
- CodeIgniter
- ORM libraries
- React
- Vue
- API-first architecture
- Active Record
- direct database access from views
- SQL inside controllers
- large business logic inside controllers

The intended architecture is:

HTTP Request
↓
Front Controller
↓
Bootstrap
↓
Middleware
↓
Router
↓
Controller
↓
Application Service
↓
Repository
↓
PDO
↓
MySQL / MariaDB

Presentation:

Controller
↓
View
↓
HTML Response

⸻

2. Existing Foundation

Phases 01–04 are already complete.

The existing project already provides concepts such as:

- application bootstrap
- front controller
- routing
- request / response handling
- error handling
- layouts / views
- environment configuration
- database configuration
- PDO connection factory
- migration infrastructure
- test database isolation
- integration testing infrastructure

Do not duplicate these mechanisms.

Inspect and reuse them.

⸻

3. Existing Domain Schema

Phase 04 introduced:

companies
branches
departments
employees

Relationship:

Company
└── Branch
├── Department
└── Employee
└── Department (optional)

Important employee schema concepts already established:

id
branch_id
department_id
employee_code
first_name
last_name
first_name_kana
last_name_kana
email
phone
position_title
employee_type
hire_date
status
created_at
updated_at

Existing domain rules include:

- Employee must belong to a valid branch.
- Department is optional.
- If a department is selected, it must belong to the employee’s branch.
- Employee code is globally unique.
- Employee email is globally unique.
- Employee type is currently:
  - permanent
  - dispatched
- Employee status is currently:
  - active
  - inactive
- Database constraints already protect critical integrity.
- Employee email is business data, not authentication identity.

Do not redesign the Phase 04 schema unless a genuine defect is discovered.

⸻

4. Phase 05 Goal

Implement the architectural design for managing employees through the server-rendered application.

Phase 05 should cover the core employee management use cases:

Employee List
Employee Detail
Employee Create
Employee Edit / Update
Employee Delete / Deactivation decision

The specification must decide the correct lifecycle behavior for removal based on the existing Phase 04 decision to preserve business records and use employee status.

Prefer preserving historical employee data rather than destructive deletion unless the existing requirements clearly justify physical deletion.

Explain the decision explicitly.

⸻

5. Scope Boundary

Phase 05 should focus on Employee Management.

Do not pull future phases into this phase.

Specifically defer:

Phase 06

Organization management:

Company CRUD
Branch CRUD
Department CRUD

Phase 05 may read branches/departments when required for employee forms, but must not implement their management CRUD.

Phase 07

Authentication.

Do not add login/logout/session authentication here.

Phase 08

Authorization/security roles.

Do not implement Admin/User permissions here unless an already-existing generic mechanism requires minimal integration.

Phase 09

Advanced employee search.

Do not implement the full planned search/filter/sort/pagination feature here.

A basic employee list is enough.

Do not introduce advanced:

- keyword search
- branch filter
- department filter
- employee type filter
- status filter
- sorting UI
- pagination

Those belong to Phase 09.

Phase 10

Employee portfolio / skills / projects / certifications.

Defer them.

Phase 11

Dashboard UI.

Defer dashboard work.

Phase 12

Production hardening.

Do not prematurely implement production-specific infrastructure.

⸻

6. Required Use Cases

The specification should define these use cases clearly.

6.1 Employee List

Define a page that displays employees.

At minimum consider:

- employee code
- employee name
- branch
- department
- position title
- employee type
- status
- actions

Do not turn this into Phase 09 advanced search.

Specify deterministic ordering for the basic list.

Avoid an unbounded production-style query if a simple safety limit is appropriate, but do not prematurely build full pagination.

Explain the choice.

⸻

6.2 Employee Detail

Define an employee detail page.

It should show the important employee information and organizational relationships in a readable form.

Define behavior for:

existing employee
missing employee

Do not leak raw database errors.

⸻

7. Employee Creation

Define the employee creation workflow.

Expected flow:

GET create form
↓
User enters data
↓
POST create
↓
Input validation
↓
Business validation
↓
Repository
↓
Database
↓
Redirect

The specification must define the fields displayed on the form.

Use the existing employee schema as the source of truth.

The application must not accept arbitrary database columns from the request.

Explicitly map allowed input fields.

⸻

8. Employee Update

Define the edit/update workflow.

Expected flow:

GET edit form
↓
Load employee
↓
Display current values
↓
POST update
↓
Validation
↓
Application Service
↓
Repository
↓
Database
↓
Redirect

Specify:

- missing employee behavior
- validation failure behavior
- uniqueness behavior
- branch/department consistency
- preservation of submitted form values after validation errors

⸻

9. Employee Removal / Lifecycle

Phase 04 deliberately introduced:

status = active
status = inactive

and used restrictive foreign keys to avoid accidental destructive deletion.

The Phase 05 specification must explicitly evaluate whether the UI should:

DELETE employee physically

or:

Deactivate employee
status = inactive

Prefer lifecycle deactivation if consistent with the existing domain model.

If deactivation is selected, define:

- route
- service operation
- confirmation page or confirmation mechanism
- behavior when employee is already inactive
- whether reactivation belongs in Phase 05

Do not silently call a status update “delete” if the operation is actually deactivation.

Use terminology that reflects the domain behavior.

⸻

10. Validation Design

Define validation rules for all employee input.

At minimum address:

employee_code

- required
- string
- trimmed
- maximum length 40
- globally unique

first_name

- required
- string
- trimmed
- maximum length 100

last_name

- required
- string
- trimmed
- maximum length 100

first_name_kana

- required
- string
- trimmed
- maximum length 100

last_name_kana

- required
- string
- trimmed
- maximum length 100

Decide whether Phase 05 should enforce strict Japanese kana characters or only structural validation.

Avoid overly restrictive validation unless justified.

email

- required
- valid email
- maximum length 254
- globally unique

phone

- optional
- maximum length 32

Avoid overly strict international phone validation.

position_title

- optional
- maximum length 120

branch_id

- required
- valid positive identifier
- referenced branch must exist

department_id

- optional
- valid identifier when supplied
- department must exist
- department must belong to selected branch

employee_type

Allowed values:

permanent
dispatched

hire_date

- required
- valid calendar date
- define accepted request format explicitly

status

Allowed values:

active
inactive

Decide whether status should be user-selectable during creation or default to active.

Explain the decision.

⸻

11. Validation Layers

The specification must distinguish:

Input Validation
Business Validation
Database Constraints

Example:

Input:
branch_id must be a valid integer
Business:
department belongs to selected branch
Database:
composite foreign key guarantees integrity

Do not rely on database exceptions as normal form validation.

Database constraints remain the final integrity boundary.

⸻

12. Validation Result Design

Do not scatter validation logic across controllers.

Specify a reusable validation approach appropriate for this Pure PHP project.

The design should support:

validated values
field-specific errors
submitted values

Example conceptual result:

Validated employee input
Errors:
email => ...
employee_code => ...

Do not introduce a large validation framework.

Keep the design small and explicit.

⸻

13. Application Service

Define an Employee application service responsible for employee use cases.

Possible responsibilities:

list employees
get employee detail
prepare create form data
create employee
prepare edit form data
update employee
deactivate employee

The service should coordinate repositories and business rules.

It must not:

- render HTML
- read global request variables directly
- execute SQL directly
- know HTTP response details

Clarify transaction boundaries where appropriate.

⸻

14. Repository Design

Define repository interfaces/contracts based on actual Phase 05 use cases.

Avoid generic repository abstractions such as:

BaseRepository
GenericRepository<T>

unless the existing codebase genuinely requires them.

Prefer explicit employee-oriented operations.

Possible operations may include:

findById
listBasic
employeeCodeExists
emailExists
insert
update
deactivate

For update uniqueness checks, account for excluding the employee currently being edited.

Repository should contain persistence concerns, not HTTP or presentation concerns.

⸻

15. Read Models / Data Transfer

Decide how data should move between:

Repository
Service
Controller
View

Do not return raw PDOStatement objects outside the repository.

Avoid exposing PDO details to higher layers.

Choose a simple approach appropriate for a learning project, such as:

- explicit arrays with documented shape
- small DTOs
- domain objects where genuinely useful

Explain the choice.

Do not over-engineer a full enterprise domain model in Phase 05.

⸻

16. Branch and Department Read Access

Employee forms need branch and department choices.

Phase 05 must not implement Branch/Department CRUD.

Define minimal read-only persistence contracts required for employee forms.

Examples:

list active branches
list active departments
find department by id

Consider whether department options should be:

- loaded for all active departments and grouped by branch, or
- loaded based on selected branch

Since this is SSR Pure PHP and Phase 05 should remain focused, choose the simplest maintainable solution.

Do not introduce an API solely for dynamic department loading unless clearly necessary.

⸻

17. Controller Design

Define an Employee controller that remains thin.

Controller responsibilities should be limited to concerns such as:

read request data
call application service
select view
redirect
translate application result into HTTP response

Controller must not:

- contain SQL
- instantiate PDO
- perform large business validation
- enforce branch/department consistency itself
- directly manipulate database transactions

⸻

18. Routes

Define clear SSR routes for the employee feature.

Prefer explicit REST-like web routes compatible with the existing router.

For example, evaluate routes conceptually similar to:

GET /employees
GET /employees/create
POST /employees
GET /employees/{id}
GET /employees/{id}/edit
POST /employees/{id}
POST /employees/{id}/deactivate

Do not assume the existing router supports arbitrary HTTP method override.

Inspect its capabilities.

If the project already has conventions for route naming or route registration, follow them.

Specify route ordering if static routes such as /employees/create could conflict with dynamic routes such as /employees/{id}.

⸻

19. POST / Redirect / GET

Successful write operations should use the:

POST
↓
Redirect
↓
GET

pattern.

Explain why.

The specification should prevent accidental form resubmission after browser refresh.

⸻

20. Error Handling

Define expected behavior for:

employee not found
invalid employee id
validation failure
duplicate employee code
duplicate email
invalid branch
invalid department
department/branch mismatch
unexpected database failure

Expected principles:

- user mistakes → useful form errors
- missing resources → appropriate 404 behavior
- unexpected infrastructure errors → existing centralized error handling
- no raw SQL / PDO errors exposed to users

⸻

21. Concurrency and Database Constraint Races

Application-level uniqueness checks improve user experience but cannot completely prevent concurrent duplicate requests.

Example:

Request A checks email → available
Request B checks email → available
A inserts
B inserts

The database UNIQUE constraint remains authoritative.

The specification should define a small, practical strategy for translating expected duplicate constraint violations into useful application errors without swallowing unrelated database failures.

Do not build an elaborate database exception framework.

⸻

22. Views

Specify SSR views for Phase 05.

Expected pages:

employees/index.php
employees/show.php
employees/create.php
employees/edit.php

Consider a reusable form partial:

employees/\_form.php

if compatible with the existing view system.

Views must:

- escape dynamic output
- contain no SQL
- contain no repository calls
- avoid business logic
- display validation errors
- preserve submitted values

Use existing layout/presentation conventions.

⸻

23. Output Escaping

All untrusted dynamic values rendered into HTML must be escaped.

Use the project’s existing escaping helper if one exists.

Otherwise the specification should identify the appropriate existing presentation mechanism rather than inventing duplicate helpers unnecessarily.

Pay particular attention to:

employee names
position title
email
phone
branch name
department name
validation errors

⸻

24. CSRF Boundary

Authentication and full authorization belong to later phases, but employee create/update/deactivation are state-changing HTTP operations.

Inspect whether CSRF protection already exists.

If the project already provides CSRF infrastructure, Phase 05 should use it.

If it does not exist yet and the project roadmap assigns CSRF to Phase 08, do not build a competing security subsystem in Phase 05.

Instead clearly document the temporary boundary and ensure state-changing operations use POST rather than GET.

Do not pretend the feature is production-secure before the planned security phase.

⸻

25. Dependency Injection / Composition Root

New dependencies should be wired through the existing Composition Root / bootstrap approach.

Conceptually:

PDO
↓
EmployeeRepository
↓
EmployeeService
↓
EmployeeController

and potentially:

BranchRepository
DepartmentRepository
↓
EmployeeService

Do not instantiate repositories or PDO connections inside controller action methods.

⸻

26. Transaction Design

Evaluate which Phase 05 operations require explicit transactions.

Do not add transactions mechanically to every SELECT.

If a use case currently performs one atomic INSERT/UPDATE only, explain whether the individual SQL statement plus DB constraints is sufficient.

If multiple writes must succeed together, use a transaction.

The specification should explain the boundary rather than simply saying “use transactions everywhere.”

⸻

27. SQL Safety

All variable input in SQL must use PDO prepared statements.

Never build SQL such as:

$sql = "SELECT \* FROM employees WHERE id = " . $\_GET['id'];

Use parameter binding.

The repository must remain the persistence boundary.

⸻

28. Mass Assignment

This is Pure PHP, so there is no framework mass-assignment protection automatically.

Do not pass the complete request body directly into persistence.

Define explicit allowed fields.

Conceptually:

HTTP input
↓
Explicit mapping
↓
Validation
↓
Known employee data
↓
Repository

Unexpected request fields must not become database updates.

⸻

29. Dates and Timestamps

Continue the Phase 04 timestamp decision.

Database stores application-managed UTC:

created_at
updated_at

Define where timestamps are generated.

Avoid making views/controllers responsible for persistence timestamps.

For hire_date, preserve the business calendar date without timezone conversion.

⸻

30. Tests

The specification must define an appropriate testing strategy.

Do not rely only on manual browser testing.

Include tests for the layers that provide meaningful value.

At minimum consider:

Repository integration tests

Using the isolated real test database:

- list employees
- find existing employee
- missing employee
- insert
- update
- deactivate
- unique employee code behavior
- unique email behavior
- valid nullable department
- invalid department/branch relationship
- prepared statements / special characters

Application service tests

Where useful, test:

- validation
- create workflow
- update workflow
- uniqueness rules
- branch existence
- department relationship
- deactivation behavior

Use test doubles only where they simplify application-level tests.

Do not mock the database when testing SQL correctness.

HTTP / Controller tests

Verify important routes and response behavior according to the capabilities of the existing test architecture.

Examples:

GET /employees
GET /employees/{id}
GET /employees/create
GET /employees/{id}/edit
POST validation failure
successful POST redirect
404 behavior

Preserve all existing regression tests.

⸻

31. Test Data

Tests should create deterministic data.

Do not depend on manually existing development records.

Respect the Phase 03 test database safety mechanisms.

Tests must never accidentally run destructive database setup against the development database.

⸻

32. Manual Verification

Define a small manual browser verification checklist.

For example:

Open employee list
Open employee detail
Open create form
Submit invalid form
Create valid employee
Edit employee
Verify department/branch behavior
Deactivate employee
Verify escaped output
Verify refresh after POST does not resubmit

Keep manual verification complementary to automated tests.

⸻

33. Expected File / Component Plan

The specification should propose the minimum necessary files/components based on the actual existing project structure.

Possible concepts:

EmployeeController
EmployeeService
EmployeeRepositoryInterface
PdoEmployeeRepository
Branch read repository
Department read repository
Employee input validator / request data object
Employee views
Routes
Tests

These names are examples, not mandatory.

Inspect the existing namespaces and conventions before deciding exact names.

Do not create unnecessary:

Managers
Handlers
Commands
Buses
Factories
Abstract repositories
Generic service bases

unless existing architecture genuinely requires them.

⸻

34. Architecture Dependency Direction

Maintain dependency direction.

Preferred conceptual structure:

Presentation / HTTP
↓
Application
↓
Persistence abstraction
↓
Infrastructure

Business/application code should not depend on:

HTML
PDOStatement
$_POST
$\_GET

directly.

⸻

35. Security Considerations

Even though later phases add full authentication/authorization/security hardening, Phase 05 should still avoid introducing obvious insecure patterns.

Address:

SQL injection
XSS
Mass assignment
Unsafe GET mutations
Raw DB errors
Unvalidated identifiers
Trusting client-supplied branch/department relationships

Clearly distinguish:

Implemented now
vs
Deferred to security phases

⸻

36. Non-Goals

Explicitly document Phase 05 non-goals.

At minimum:

Authentication
Role authorization
Admin/User access rules
Advanced search
Filtering
Sorting UI
Pagination UI
Employee photo upload
Dispatch contract management
Portfolio
Skills
Projects
Certifications
Company CRUD
Branch CRUD
Department CRUD
Dashboard
REST API
React frontend

Do not accidentally implement these features.

⸻

37. Specification Deliverable

Produce:

docs/specs/05-employee-management.md

The specification should be detailed enough that a separate implementation step can follow it without making major architectural decisions again.

Use clear sections including:

1. Purpose
2. Scope
3. Non-goals
4. Existing architecture assumptions
5. Employee use cases
6. Domain rules
7. Routes
8. Request/input model
9. Validation rules
10. Application service design
11. Repository contracts
12. Branch/department read strategy
13. Data transfer strategy
14. Controller responsibilities
15. View structure
16. Error handling
17. Employee lifecycle/deactivation
18. Transactions
19. Security boundaries
20. Testing strategy
21. Manual verification
22. Expected implementation files
23. Implementation order
24. Acceptance criteria
25. Deferred work

⸻

38. Acceptance Criteria for the Specification

The specification is complete only if another developer can clearly answer:

What employee operations are included?
What is intentionally deferred?
What routes will exist?
Where does validation happen?
Where does SQL live?
Where do business rules live?
How are branches/departments read?
How is cross-branch department assignment prevented?
How are employee code/email duplicates handled?
How does create work?
How does update work?
What does “remove employee” actually mean?
How are timestamps handled?
How are errors presented?
How are dependencies wired?
What tests are required?
What security protections exist now?
What protections are intentionally deferred?

Avoid vague statements such as:

"Handle errors appropriately."
"Validate input."
"Use repository pattern."

Define concrete behavior.

⸻

39. Design Principles

Follow these principles:

Simple over clever
Explicit over magical
Business rules over CRUD convenience
Database constraints plus application validation
Thin controllers
SQL only in repositories
Test real database behavior
Reuse existing infrastructure
No premature abstraction
No future-phase scope creep

The purpose of Phase 05 is not merely to make an employee form work.

The purpose is to learn how a professional PHP application moves a real business use case through:

HTTP
↓
Controller
↓
Application Service
↓
Repository
↓
PDO
↓
Database

while keeping responsibilities separated and the business data valid.

# Implementation Prompt

Implementation Prompt

Implement Phase 05 – Employee Management for the existing Pure PHP Company Employee Management System.

The authoritative specification is:

docs/specs/05-employee-management.md

Read that specification completely before modifying code.

Also inspect the existing implementation from Phases 01–04 before deciding exact namespaces, constructors, file locations, test helpers, or infrastructure changes.

Do not redesign the application.

Do not implement future phases.

⸻

1. Current Branch

Implementation must be performed on the already-created branch:

feature/employee-management

Before making changes, verify:

git branch --show-current
git status

Do not create another branch.

Do not switch to main.

Do not commit, merge, push, or delete branches automatically.

Leave Git commit/merge/push decisions to the developer after review and testing.

⸻

2. Source of Truth

Follow:

docs/specs/05-employee-management.md

as the authoritative Phase 05 design.

If this implementation prompt and the specification appear to conflict, prefer the specification unless doing so would break an established Phase 01–04 project contract.

If an existing project contract conflicts with the Phase 05 specification:

1. inspect the existing implementation;
2. preserve the existing architecture where reasonable;
3. make the smallest compatible adjustment;
4. document the discrepancy in the final implementation summary.

Do not silently replace working Phase 01–04 infrastructure.

⸻

3. Phase 05 Goal

Implement:

Employee List
Employee Detail
Employee Create
Employee Edit / Update
Employee Deactivation

using:

HTTP Request
↓
EmployeeController
↓
EmployeeService
↓
Repository Interfaces
↓
PDO Repositories
↓
MySQL / MariaDB

Views remain server-rendered PHP.

⸻

4. Preserve Existing Architecture

Reuse existing:

public/index.php
ApplicationBootstrap
Request
Response
HttpKernel
MiddlewarePipeline
Router
ViewRenderer
HtmlEscaper
ExceptionResponder
Configuration
DatabaseConfiguration
ConnectionFactory
Migration infrastructure
Integration-test DB safety

Do not create competing versions of these components.

Before implementing a new helper, inspect whether equivalent behavior already exists.

⸻

5. No Phase 05 Migration

Phase 05 consumes the Phase 04 schema.

Do not add a migration unless implementation proves that the approved Phase 04 schema contains a genuine defect.

If a schema defect is discovered:

STOP

Do not silently change the database schema.

Report:

- the defect;
- the affected migration/schema rule;
- why Phase 05 cannot safely continue;
- the smallest proposed schema correction.

⸻

6. Implement in Small Architectural Layers

Implement in this approximate order:

1. Inspect existing project
2. Input / validation objects
3. Repository contracts
4. PDO repositories
5. Lazy DB connection mechanism
6. Clock
7. EmployeeService
8. EmployeeController
9. Views
10. Routes
11. Composition-root wiring
12. Unit tests
13. DB integration tests
14. HTTP / feature tests
15. Full regression tests

Adjust exact order only where existing project dependencies require it.

⸻

7. Employee Input

Create a small explicit employee input representation.

It should represent only:

employee_code
first_name
last_name
first_name_kana
last_name_kana
email
phone
position_title
branch_id
department_id
employee_type
hire_date

Do not include:

id
status
created_at
updated_at

Do not pass arbitrary request arrays into SQL.

⸻

8. Employee Input Validator

Implement centralized employee structural validation.

Validate according to:

docs/specs/05-employee-management.md

Required:

employee_code
first_name
last_name
first_name_kana
last_name_kana
email
branch_id
employee_type
hire_date

Optional:

phone
position_title
department_id

Rules must include:

scalar input protection
trim strings
maximum lengths
email validation
positive integer identifiers
allowed employee_type values
strict Y-m-d calendar date validation
optional empty values → null

Do not add a strict Japanese Kana regex.

Do not accept arrays where scalar form values are expected.

Use multibyte-safe length validation consistent with the actual project/runtime requirements.

Do not build a general-purpose validation framework.

⸻

9. Validation Result

Validation must preserve:

normalized values
validated EmployeeInput when valid
field errors

A validation failure must be usable to render:

HTTP 422

without losing the user’s submitted values.

Keep validation messages safe for HTML rendering.

Views must still escape them.

⸻

10. Repository Interfaces

Create explicit contracts for:

EmployeeRepository
BranchReadRepository
DepartmentReadRepository

Use existing namespace conventions where possible.

Do not create:

BaseRepository
GenericRepository
RepositoryManager
QueryBuilder
ActiveRecord
ORM abstraction

⸻

11. Employee Repository

Implement operations equivalent to:

listBasic(int limit)
findById(int id)
employeeCodeExists(
string code,
?int exceptId = null
)
emailExists(
string email,
?int exceptId = null
)
insert(...)
update(...)
deactivate(...)

Use PDO prepared statements for runtime values.

Do not expose:

PDO
PDOStatement

outside the infrastructure repository.

⸻

12. Employee List Query

Implement the Phase 05 basic list.

Maximum:

200 employees

No request-controlled limit.

No pagination.

No search/filter UI.

Ordering:

last_name ASC
first_name ASC
employee_code ASC
id ASC

Include:

branch name
department name

without causing N+1 queries.

Prefer an explicit JOIN query.

Both:

active
inactive

employees must be listed.

⸻

13. Employee Detail Query

Load employee detail together with organization display information.

The view must not query Branch or Department separately.

Return an explicit read shape containing the employee data required by the specification plus organization names/codes/status where needed.

Missing employee:

null

from repository.

The service/controller translates this to the existing 404 mechanism.

⸻

14. Branch Read Repository

Implement only the read operations needed by Employee Management.

Equivalent operations:

listActive()
findById(int id)

Do not implement Branch CRUD.

⸻

15. Department Read Repository

Implement only:

listActive()
findById(int id)

or the smallest equivalent contract required by the approved specification.

Do not implement Department CRUD.

Return enough information to verify:

department.branch_id
department.status

⸻

16. Active Organization Rules

For new employee assignment:

Branch must exist
Branch must be active

If department is selected:

Department must exist
Department must be active
Department.branch_id must equal Employee.branch_id

For an existing employee referencing an inactive organization row:

- allow the current relationship to remain visible;
- allow unrelated employee fields to be edited without silently changing that relationship;
- do not allow changing to another inactive organization choice.

Implement the simplest clear solution consistent with the specification.

⸻

17. Database Constraints Remain Authoritative

Application validation improves user experience.

It does not replace:

Foreign Keys
Composite Foreign Key
UNIQUE constraints
CHECK constraints

Do not remove or bypass Phase 04 database protections.

⸻

18. Duplicate Employee Code / Email

Before writes, perform application-level checks for:

employee_code
email

For update:

exclude current employee id

Example concept:

emailExists(email, employeeId)

must not report the employee’s own unchanged email as duplicate.

⸻

19. Duplicate Race Handling

Pre-checks do not eliminate concurrency races.

Handle only known duplicate-key failures from employee INSERT/UPDATE.

Translate known:

employee_code duplicate
email duplicate

into safe field errors.

Do not catch every PDOException and convert it into validation.

Unknown DB failures must continue to the existing centralized error handling.

Do not expose raw SQL/PDO messages.

⸻

20. Employee Service

Implement use-case-oriented operations consistent with the specification:

listEmployees()
getEmployee()
createForm()
createEmployee()
editForm()
updateEmployee()
deactivationForm()
deactivateEmployee()

The service owns:

validation coordination
organization business validation
uniqueness pre-checks
timestamp generation
repository orchestration
deactivation behavior
form/read data preparation

The service must not:

read $\_POST
read $\_GET
render HTML
return Response
execute SQL
access PDO directly

⸻

21. Employee Creation

Create flow:

POST /employees
↓
Explicit input mapping
↓
Structural validation
↓
Business validation
↓
Uniqueness checks
↓
INSERT
↓
new employee id
↓
303 /employees/{id}

New employee status:

active

The client cannot choose status.

Generate:

created_at
updated_at

in application code using UTC.

⸻

22. Employee Update

Update flow:

POST /employees/{id}
↓
Employee exists?
↓
Validation
↓
Business validation
↓
Uniqueness excluding self
↓
UPDATE
↓
303 /employees/{id}

Ordinary update must not modify:

id
status
created_at

It updates:

updated_at

⸻

23. Employee Deactivation

Do not physically delete employees.

Never implement:

DELETE FROM employees

for the Phase 05 employee lifecycle.

Use:

status = inactive

Route:

GET /employees/{id}/deactivate
POST /employees/{id}/deactivate

GET:

confirmation only
no state change

POST:

active → inactive

Already inactive:

no-op

Do not update updated_at for the no-op unless the existing implementation contract makes this unavoidable and the reason is documented.

Do not implement reactivation.

⸻

24. Clock

If a clock abstraction is introduced, keep it minimal.

Example responsibility:

Clock
↓
nowUtc(): DateTimeImmutable

Production:

SystemClock

Tests:

Fixed/Test Clock

Do not build:

time service framework
timezone registry
event scheduler
global clock singleton

The purpose is only deterministic application timestamps.

⸻

25. Timestamp Formatting

Persistence:

UTC

for:

created_at
updated_at

hire_date remains:

Y-m-d

without timezone conversion.

Use the existing application/configuration conventions for display timezone if one already exists.

Do not invent a large timezone subsystem for Phase 05.

⸻

26. Lazy Database Connection

Preserve the existing ability to boot/render the setup route without requiring a working database.

Do not eagerly call:

ConnectionFactory → PDO

during application bootstrap if that would make:

GET /

depend on DB credentials.

Use the smallest lazy connection mechanism compatible with the existing architecture.

Concept:

ApplicationBootstrap
↓
Lazy connection provider
↓
Repository
↓
PDO created only when repository operation occurs

Do not use:

global PDO
static PDO
service locator

⸻

27. Controller

Implement a thin EmployeeController.

Controller responsibilities:

Read Request
Read route parameter
Call EmployeeService
Render View
Return redirect
Map missing resource → existing 404
Map validation result → 422

Controller must not contain:

SQL
PDO
repository construction
large validation logic
branch/department business rules
database transactions

⸻

28. Redirects

Successful writes must return:

303 See Other

with:

Location: /employees/{id}

If existing Response has no suitable redirect factory, add the smallest safe convenience method.

Do not build a redirect/router framework.

Validate or construct redirect destinations only from trusted application paths and validated integer IDs.

⸻

29. Routes

Register:

GET /employees
GET /employees/create
POST /employees
GET /employees/{id}/deactivate
POST /employees/{id}/deactivate
GET /employees/{id}/edit
POST /employees/{id}
GET /employees/{id}

Respect the existing router’s registration-order matching.

Register:

/employees/create

and action routes before:

/employees/{id}

Do not add PUT/PATCH/DELETE method override.

⸻

30. Identifier Validation

Route {id} must represent a positive decimal integer.

Examples:

1 valid
42 valid
0 invalid
-1 invalid
abc invalid
1.5 invalid

Invalid identifiers use the existing 404 behavior.

Do not send invalid identifiers to SQL.

⸻

31. Views

Implement:

resources/views/employees/index.php
resources/views/employees/show.php
resources/views/employees/create.php
resources/views/employees/edit.php
resources/views/employees/deactivate.php
resources/views/employees/\_form.php

Use existing:

ViewRenderer
layout.php
HtmlEscaper

Do not create a second template engine.

⸻

32. Shared Employee Form

Use \_form.php to avoid duplicating create/edit fields where appropriate.

Render:

employee_code
first_name
last_name
first_name_kana
last_name_kana
email
phone
position_title
branch
department
employee_type
hire_date

Do not render:

status
created_at
updated_at
id

as editable employee fields.

⸻

33. Branch / Department Form Options

Load organization options without AJAX.

Use active branches/departments and group departments by branch.

The form must remain usable without JavaScript.

Small progressive-enhancement JavaScript is permitted only if:

server-side validation remains authoritative

Do not create an API endpoint for department loading.

⸻

34. Escaping

Escape every untrusted dynamic HTML value with the existing HtmlEscaper.

This includes:

employee names
kana
employee code
email
phone
position title
branch
department
validation errors
notice messages
form values

Do not rely on “this value normally comes from the database” as a reason not to escape.

Stored data can still contain hostile HTML.

⸻

35. Form Error Behavior

Validation failure:

HTTP 422

Render the same create/edit form.

Preserve:

submitted values
field-specific errors
organization choices

Do not redirect on validation failure.

Do not discard the user’s input.

⸻

36. Error Boundaries

Use:

404

for invalid/missing employee resources.

Use:

422

for employee form validation/business errors.

Use:

303

after successful writes.

Unexpected infrastructure/database exceptions:

existing centralized 500 handling

Do not expose:

SQL
DSN
DB username
DB password
PDO stack details

to browser output.

⸻

37. POST / Redirect / GET

Successful:

Create
Update
Deactivate

must follow:

POST
↓
303
↓
GET

This is mandatory for Phase 05.

⸻

38. CSRF

Do not invent CSRF infrastructure in Phase 05 if none currently exists.

All state changes must nevertheless use POST.

Document clearly that CSRF protection is deferred to the planned security phase.

Do not describe Phase 05 as production-secure before authentication, authorization, and CSRF are implemented.

⸻

39. Transactions

Do not mechanically add transactions around every operation.

Current Phase 05 writes are single SQL statements:

INSERT employee
UPDATE employee
UPDATE status

No service-level transaction is required unless implementation changes a use case into multiple dependent writes.

If that occurs, use a transaction only around the atomic multi-write unit and document why.

⸻

40. Tests – General Rule

Do not finish implementation merely because pages render.

Add automated tests.

Preserve all existing Phase 01–04 tests.

Do not weaken existing assertions to make new implementation pass.

Do not remove DB test safety checks.

⸻

41. Validator Tests

Test at minimum:

required fields
max lengths
array/non-scalar rejection
email
employee type
strict date
optional → null
positive IDs
Unicode/multibyte values
submitted-value preservation

⸻

42. Service Tests

Test meaningful business behavior:

missing branch
inactive branch
missing department
inactive department
cross-branch department
duplicate employee code
duplicate email
update uniqueness excluding self
create → active
status input ignored
successful update
successful deactivation
already inactive no-op
timestamp behavior

Do not make tests depend on current wall-clock time if a clock abstraction exists.

⸻

43. Repository Integration Tests

Use the real isolated test database.

Respect:

APP*ENV=test
DB_TEST*\*
database name ending \_test
development DB != test DB

Test:

list
ordering
limit
detail
insert
insert null department
update
deactivate
deactivate no-op
unique code
unique email
invalid FK
cross-branch department
multibyte values
apostrophes
timestamps

Do not mock PDO for SQL tests.

⸻

44. HTTP / Feature Tests

Use the existing project testing style.

Cover important behavior including:

GET /employees
GET /employees/create
GET employee detail
GET missing employee
GET edit
GET deactivate confirmation
POST create invalid → 422
POST create valid → 303
POST update invalid → 422
POST update valid → 303
POST deactivate → 303
unsupported method → existing 405
HTML escaping

Also preserve the important regression:

GET /

must still be bootable without requiring a DB connection.

⸻

45. Manual Verification

After automated tests, provide a manual browser checklist based on the specification.

Do not claim the browser checklist passed unless it was actually executed.

Distinguish:

Automated verification completed

from:

Manual verification recommended

⸻

46. Do Not Expand Scope

Do not implement:

Authentication
Authorization
CSRF subsystem
Company CRUD
Branch CRUD
Department CRUD
Search
Filters
Pagination
Sorting UI
Photo upload
Dispatch contract
Portfolio
Skills
Projects
Certifications
Dashboard
REST API
React
Employee reactivation
Physical employee deletion

⸻

47. Avoid Over-Engineering

Do not add:

Command Bus
Event Bus
CQRS
ORM
Generic repository
Base service
Base controller
Service locator
Container framework
Domain event framework
Result monad framework
Validation framework

Phase 05 should remain explicit and teachable.

⸻

48. Code Quality

Use:

declare(strict_types=1);

consistent with existing project conventions.

Prefer:

final classes
constructor injection
typed parameters
typed return values
small methods
explicit names

where appropriate.

Avoid unnecessary static state.

Keep responsibilities narrow.

⸻

49. Documentation

Update relevant project documentation only where Phase 05 changes actual usage or architecture.

Do not rewrite unrelated Phase 01–04 documentation.

Document:

Employee Management feature
required runtime extension if genuinely needed
test commands
manual verification steps
security work still deferred

If mbstring is already available/required, follow the current environment.

If it is not currently required, inspect the project/runtime before adding a new Composer/platform requirement. Do not introduce a dependency solely because the specification mentioned mb_strlen when available.

⸻

50. Verification Commands

After implementation, run the project’s existing test commands first.

At minimum:

composer test

Then run the real database integration tests using the existing safe Phase 03 pattern and the configured isolated test database.

Do not guess credentials.

Do not connect to or reset the development database.

If the environment required for DB integration testing is unavailable, report that clearly instead of claiming the tests passed.

⸻

51. Final Implementation Report

When implementation is complete, do not commit automatically.

Provide a concise report containing:

1. Files created
2. Files modified
3. Architecture implemented
4. Employee routes added
5. Validation rules implemented
6. Repository operations implemented
7. Deactivation behavior
8. Lazy DB connection behavior
9. Automated tests added
10. Test commands executed
11. Exact test results
12. Skipped tests and reasons
13. Manual verification still required
14. Any deviation from the specification
15. git status

Also provide:

git diff --stat

and identify anything unexpected in the diff.

⸻

52. Stop Conditions

Stop implementation and report instead of guessing if any of these occur:

Phase 04 schema cannot support the approved feature
Existing migration contract would need destructive modification
Test DB safety cannot be established
Implementing the feature would require changing an approved
Phase 01–04 architectural contract in a major way
A required project file is missing or inconsistent with the specification
in a way that cannot be safely resolved from existing conventions

Minor implementation differences that preserve the approved architecture do not require stopping.

⸻

53. Definition of Done

Phase 05 implementation is ready for developer review when:

Employee List works
Employee Detail works
Employee Create works
Employee Update works
Employee Deactivation works
No employee physical delete exists
Validation is centralized
Business validation is in service layer
SQL is in repositories
PDO prepared statements are used
Views escape dynamic output
Controllers remain thin
POST → 303 → GET works
Invalid/missing employee → 404
Invalid form → 422
Unexpected infrastructure error → centralized 500
Cross-branch department assignment is prevented
Duplicate code/email are handled
Database constraints remain authoritative
Setup route remains DB-independent
Automated tests pass in the available environments
Existing regression tests remain intact
No future-phase scope creep exists

Do not commit, merge, push, or delete the feature branch.

Stop after implementation, testing, and the final implementation report so the developer can review the changes.
