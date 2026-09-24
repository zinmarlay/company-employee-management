# Phase 05: Employee Management Specification

## 1. Purpose

This document specifies the first application-level business feature for the
Company Employee Management System: server-rendered employee management.

It is an implementation specification. It does not implement controllers,
services, repositories, views, routes, migrations, authentication, or
authorization.

Phase 05 builds on the HTTP and database foundations from Phases 01–04. It
must use the existing custom router, request/response objects, view renderer,
configuration boundary, PDO connection factory, migration schema, and
integration-test safety rules. It must not introduce a PHP framework, ORM,
generic CRUD framework, API layer, or parallel application architecture.

The implementation branch for this phase is not assigned by the prompt.
Implementation work must use the explicitly assigned project branch and must
not invent a branch name in this specification.

## 2. Phase objective

Add the first complete business workflow:

HTTP request
  -> controller
  -> employee application service
  -> employee repository contract
  -> PDO repository
  -> MySQL/MariaDB

The feature must support:

- a basic employee list;
- employee detail;
- employee creation;
- employee editing;
- employee deactivation while preserving the employee row.

The result must demonstrate clear separation between HTTP concerns,
application rules, persistence, and server-rendered presentation.

## 3. Scope

### 3.1 Included

- Employee list, detail, create, edit, and deactivation pages
- Explicit employee input mapping and validation
- Employee application service
- Employee-oriented repository contracts and PDO implementations
- Read-only branch and department lookups needed by employee forms
- Employee routes using the existing GET/POST router
- Server-rendered employee views using the existing layout and escaping helper
- Application-level uniqueness checks backed by database constraints
- Safe translation of expected duplicate-key races
- Unit, repository integration, and HTTP/feature tests
- A small manual browser-verification checklist

### 3.2 Existing schema to consume

Phase 04 already provides these tables and relationships:

Company
  -> Branch
      -> Department
      -> Employee
          -> optional Department in the same Branch

The Phase 05 implementation must use the existing employees table. It must
not redesign the Phase 04 schema or add a second employee table.

The employee columns are:

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

## 4. Non-goals and deferred work

Phase 05 must not implement:

- authentication, login, logout, or sessions;
- role authorization or Admin/User permissions;
- CSRF infrastructure;
- Company CRUD;
- Branch CRUD;
- Department CRUD;
- advanced keyword search;
- branch, department, type, or status filters;
- sorting controls;
- pagination UI or a pagination abstraction;
- employee photos or uploads;
- dispatch-company or contract management;
- employee portfolios, skills, projects, or certifications;
- dashboards;
- REST/JSON endpoints;
- React, Vue, or another frontend framework;
- hard employee deletion;
- employee reactivation;
- manager hierarchies, position catalogs, or employment history.

The list may use a fixed safety cap, but it must not expose that cap as a
pretend pagination feature. Full search, filtering, sorting, and pagination
belong to Phase 09.

## 5. Existing architecture assumptions

### 5.1 HTTP and presentation

The current project has:

- public/index.php as the front controller;
- ApplicationBootstrap as the composition root;
- Request, Response, HttpKernel, and MiddlewarePipeline;
- a custom Router with exact and named-segment routes;
- ViewRenderer::renderPage() for layout-backed PHP views;
- HtmlEscaper for HTML output escaping;
- centralized ExceptionResponder handling 404, 405, and unexpected 500
  responses.

The current router only exposes get() and post() helpers. It does not
provide named routes, route constraints, HTTP method override, or automatic
redirect helpers. Phase 05 must work with those capabilities. A small
Response::redirect() convenience method may be added if useful, but it must
produce an ordinary 303 response with a validated Location header and must
not become a routing abstraction.

The router checks routes in registration order. Static routes must therefore
be registered before the dynamic /employees/{id} route.

### 5.2 Database

The current database foundation provides:

- Configuration::database();
- DatabaseConfiguration;
- ConnectionFactory;
- PDO exception mode, associative fetches, and native prepared statements;
- migration discovery and tracking;
- isolated integration-test configuration through DB_TEST_*.

The normal setup route must remain usable without database credentials or a
running database. Employee dependencies must therefore use a lazy connection
provider, or an equivalent composition-root mechanism, so that a PDO
connection is created only when an employee operation is invoked. Controllers
must never create PDO connections.

The employee feature does not add a migration. It consumes the Phase 04
schema already created by the existing migration runner.

### 5.3 Dependency direction

Dependencies must flow in this direction:

HTTP controller
  -> employee application service
      -> repository interfaces and read-only lookup interfaces
          -> concrete PDO repositories
              -> PDO

Views depend only on prepared view data and HtmlEscaper. They must not
receive PDO, repositories, services, or request globals.

## 6. Domain rules

The service and repositories must preserve these Phase 04 rules:

1. An employee must reference an existing branch.
2. A department is optional.
3. If supplied, a department must belong to the selected branch.
4. employee_code is globally unique.
5. email is globally unique.
6. employee_type is either permanent or dispatched.
7. status is either active or inactive.
8. Employee email is business contact data, not an authentication identity.
9. Foreign keys and unique constraints remain the final integrity boundary.
10. Branches and departments are not managed in this phase.

### 6.1 Active organization choices

Employee forms must offer active branches and active departments only. A
branch or department that is inactive must not be selected for a new
assignment.

If an existing employee still references an organization row that has become
inactive, the edit form may include that current row as a clearly marked
current value so editing unrelated employee data does not silently change
the employee's organization. The service must still reject changing to an
inactive branch or department. The detail page must display the current
relationship regardless of its status.

This behavior does not add organization-management UI; it only prevents an
employee edit from losing an existing relationship.

## 7. Use cases

### 7.1 Employee list

GET /employees displays a basic list containing:

- employee code;
- employee name;
- branch;
- department;
- position title;
- employee type;
- status;
- links for detail and edit;
- a deactivation action for active employees.

The service calls an explicit repository operation such as
listBasic(int limit). The implementation must use a fixed application limit
of 200 rows. There is no user-controlled limit and no pagination UI in this
phase.

The query must have deterministic ordering:

1. last_name ascending;
2. first_name ascending;
3. employee_code ascending;
4. id ascending as the final tie-breaker.

Both active and inactive employees are included so the list remains a
business record list rather than an active-only roster. Phase 09 may add
filters and pagination later.

The service must not return a raw PDOStatement. Each row is mapped to a
documented employee list read shape.

### 7.2 Employee detail

GET /employees/{id} displays:

- employee code;
- full name and kana name;
- email;
- phone;
- position title;
- employee type;
- hire date;
- status;
- branch;
- department, or an explicit "not assigned" value;
- created and updated timestamps in the application's display timezone;
- edit link;
- deactivation controls when the employee is active.

The repository must load branch and department display names through joins or
an equivalent explicit read query. The view must not perform lookups.

An invalid, non-positive, non-integer route identifier and a valid identifier
that has no employee both produce the existing application 404 behavior.
Neither case exposes SQL or PDO details.

### 7.3 Employee creation

The workflow is:

1. GET /employees/create loads active branch and department choices and
   renders an empty form.
2. The user submits POST /employees.
3. The controller passes the body to the service.
4. The service explicitly maps allowed fields, validates input, checks
   business relationships, and calls the repository.
5. On success, the service returns the new employee identifier.
6. The controller responds with 303 See Other to /employees/{id}.
7. The detail page is loaded by a new GET request.

The create operation ignores unexpected request fields. It does not accept
id, created_at, updated_at, or client-selected status. New employees always
start with active status.

### 7.4 Employee update

The workflow is:

1. GET /employees/{id}/edit loads the employee and form choices.
2. The current employee values are shown.
3. The user submits POST /employees/{id}.
4. The service validates and checks the submitted values.
5. On success, the service updates editable fields and preserves status,
   created_at, and id.
6. The controller redirects with 303 See Other to /employees/{id}.

If the employee is missing, the edit page and update both return 404.
Validation failures render the edit form with status 422, field errors, and
the submitted values. They must not redirect, because the submitted form
state would otherwise be lost.

An update uniqueness check must exclude the employee being edited. The
database unique constraints remain authoritative if two updates race.

### 7.5 Employee deactivation

"Remove employee" means deactivation in Phase 05. It must not issue
DELETE FROM employees.

The workflow is:

1. GET /employees/{id}/deactivate renders a confirmation page.
2. The page identifies the employee and explains that the row and its
   historical data will be retained while its status becomes inactive.
3. The user submits POST /employees/{id}/deactivate.
4. The service validates the identifier, loads the employee, and updates only
   status and updated_at.
5. The controller redirects with 303 to the detail page.

The confirmation page is a normal GET and has no side effect. Any optional
JavaScript confirmation is only progressive enhancement; the server relies on
the explicit POST confirmation form, not on JavaScript.

If the employee is already inactive, deactivation is an idempotent no-op and
redirects to the detail page with a safe, allowlisted notice such as
already-inactive. It is not an error and must not change updated_at unless
the implementation deliberately treats the repeated command as a new
lifecycle event. The recommended behavior is not to change the timestamp for
the no-op.

Reactivation is deferred. The edit form must not expose a status selector,
and Phase 05 must not add a POST route that changes inactive to active.

## 8. Routes

Register these routes in routes/web.php, before the dynamic /employees/{id}
route where applicable:

| Method | Path | Responsibility | Success response |
| --- | --- | --- | --- |
| GET | /employees | Basic list | 200 HTML |
| GET | /employees/create | Empty create form | 200 HTML |
| POST | /employees | Create employee | 303 to detail |
| GET | /employees/{id}/deactivate | Deactivation confirmation | 200 HTML |
| POST | /employees/{id}/deactivate | Deactivate employee | 303 to detail |
| GET | /employees/{id}/edit | Edit form | 200 HTML |
| POST | /employees/{id} | Update employee | 303 to detail |
| GET | /employees/{id} | Detail | 200 HTML |

The static /employees/create route and the two-segment action routes must
be registered before /employees/{id}. The current router will otherwise
allow the dynamic route to claim a static segment.

The router does not constrain id to digits. The controller or service must
validate it as a positive decimal integer and return 404 for invalid values.
No HTTP method override is permitted.

All successful writes use POST -> 303 redirect -> GET. This prevents browser
refresh from resubmitting a create, update, or deactivation command.

## 9. Request and input model

### 9.1 Allowed employee fields

The only request fields accepted by create and update are:

- employee_code
- first_name
- last_name
- first_name_kana
- last_name_kana
- email
- phone
- position_title
- branch_id
- department_id
- employee_type
- hire_date

The service must map these names explicitly from the request body. It must
not pass the complete request array to a repository.

status, id, created_at, and updated_at are not accepted as ordinary form
input. Status changes only through the deactivation use case.

### 9.2 Input normalization

The input validator must reject arrays and objects for scalar fields. It must:

- trim all string fields;
- convert empty optional phone, position_title, and department_id values to
  null;
- preserve Unicode names and submitted display values;
- preserve the employee's email spelling after trimming;
- convert valid numeric identifiers to positive integers;
- preserve hire_date as the business calendar date in Y-m-d form.

It must not silently accept arbitrary nested request data or perform a
mass-assignment update.

## 10. Validation rules

### 10.1 Structural input validation

| Field | Rules |
| --- | --- |
| employee_code | Required scalar string, trimmed, 1–40 characters |
| first_name | Required scalar string, trimmed, 1–100 characters |
| last_name | Required scalar string, trimmed, 1–100 characters |
| first_name_kana | Required scalar string, trimmed, 1–100 characters |
| last_name_kana | Required scalar string, trimmed, 1–100 characters |
| email | Required scalar string, trimmed, valid email, 1–254 characters |
| phone | Optional scalar string, trimmed, at most 32 characters |
| position_title | Optional scalar string, trimmed, at most 120 characters |
| branch_id | Required positive decimal integer |
| department_id | Optional positive decimal integer |
| employee_type | Required string equal to permanent or dispatched |
| hire_date | Required string in exact Y-m-d format and a real calendar date |

Use multibyte-safe length checks for user-facing text. The project should
use mb_strlen when available and document mbstring as a runtime requirement
for this feature. It must not impose a strict Japanese kana regular
expression. Kana fields are structurally required text fields; names may
contain legitimate spaces, punctuation, or characters not covered by a
single narrow regex.

For hire_date, use strict parsing with DateTimeImmutable::createFromFormat
and reject parse warnings/errors. Do not convert the date through a timezone;
it represents a calendar date rather than an instant.

For email, use PHP's email validation after the length check. The database
collation and unique index decide case-insensitive duplicate behavior where
the configured engine/collation does so.

### 10.2 Business validation

After structural validation:

- branch_id must identify an existing branch;
- a new assignment must use an active branch;
- if department_id is null, the employee has no department assignment;
- if supplied, department_id must identify an existing department;
- the department's branch_id must equal the submitted branch_id;
- a new department assignment must use an active department;
- employee_code must not already belong to another employee;
- email must not already belong to another employee;
- update uniqueness checks must exclude the current employee id.

The service performs these checks through read-only repositories before the
write. It must still handle a duplicate-key result from the write because
application checks cannot close a concurrent-request race.

### 10.3 Database validation

The database remains the final boundary:

- foreign keys reject unknown branches and departments;
- the composite employee foreign key rejects cross-branch department
  assignments;
- unique keys reject duplicate employee codes and emails;
- check constraints reject unsupported employee types and statuses.

The service must not treat a database exception as the normal user-facing
validation mechanism. It should prevent expected errors before the write and
translate only known constraint races after the write.

## 11. Validation result design

Do not scatter validation rules through controller actions.

Use one small employee input validator and one explicit result shape:

- values: normalized submitted values suitable for redisplay;
- input: an EmployeeInput value object when structural validation succeeds,
  otherwise null;
- errors: an associative map from field name to one safe user-facing
  message.

The result must retain submitted values even when there are errors. The
controller passes those values to the form view, along with fresh branch and
department choices.

Business validation errors are added to the same field-keyed error map:

- branch existence/status errors use branch_id;
- department existence/status/ownership errors use department_id;
- duplicate code uses employee_code;
- duplicate email uses email.

A small immutable EmployeeInput object is appropriate after validation.
Read models may remain arrays with documented shapes; a full domain entity or
validation framework is not justified by this phase.

## 12. Application service design

Create an employee-focused application service. Its public operations should
correspond to use cases rather than generic CRUD methods:

- listEmployees();
- getEmployee(int id);
- createForm();
- createEmployee(array rawInput);
- editForm(int id);
- updateEmployee(int id, array rawInput);
- deactivationForm(int id);
- deactivateEmployee(int id).

The exact PHP return classes may follow existing project conventions, but the
operations must expose the outcomes described below.

The service is responsible for:

- coordinating the validator;
- applying branch/department ownership rules;
- checking active organization choices;
- checking application-level uniqueness;
- deciding create default status;
- generating application-managed UTC timestamps;
- invoking repositories;
- translating known persistence conflicts into field errors;
- returning prepared read/form data.

The service must not:

- read $_GET, $_POST, or other globals;
- render HTML;
- know response status codes or redirect URLs;
- execute SQL or receive a PDOStatement;
- decide authentication or authorization;
- implement organization CRUD.

### 12.1 Service outcomes

The implementation must distinguish:

- found vs missing employee;
- valid vs invalid form submission;
- successful create/update/deactivation;
- already-inactive no-op;
- expected duplicate conflict;
- unexpected infrastructure failure.

Missing-resource outcomes should be translated to the existing HTTP 404
boundary by the controller. Validation and known conflicts return form data
and errors for a 422 response. Unexpected exceptions are allowed to reach the
existing centralized 500 responder.

## 13. Repository contracts

Do not introduce BaseRepository, GenericRepository<T>, a query builder, or a
repository manager.

### 13.1 Employee repository

The employee persistence contract must provide explicit operations equivalent
to:

- listBasic(int limit): array
- findById(int id): ?array
- employeeCodeExists(string code, ?int exceptId = null): bool
- emailExists(string email, ?int exceptId = null): bool
- insert(EmployeeInput input, string createdAt, string updatedAt): int
- update(int id, EmployeeInput input, string updatedAt): void
- deactivate(int id, string updatedAt): bool

deactivate returns whether a state change occurred. It must update only
active rows, making an already-inactive command a no-op.

The concrete PDO repository owns:

- SQL text;
- prepared statements and parameter binding;
- joins used to produce employee list/detail read shapes;
- PDO row-to-array mapping;
- translation of known duplicate employee-code/email constraints.

It must not own HTML, request parsing, redirects, or business policy about
what a 404 page looks like.

### 13.2 Branch read contract

Use a minimal read-only contract equivalent to:

- listActive(): array
- findById(int id): ?array

The returned branch shape contains at least id, code, name, and status. The
implementation may include city for a useful form/detail label.

### 13.3 Department read contract

Use a minimal read-only contract equivalent to:

- listActive(): array
- findById(int id): ?array

The returned department shape contains at least id, branch_id, code, name,
and status.

The service compares the returned branch_id to the submitted branch_id. The
database composite foreign key independently enforces the same invariant at
write time.

### 13.4 Query safety

All runtime values use PDO prepared statements. Identifiers, ordering, table
names, and SQL fragments are fixed reviewed strings in repository code.
Request values must never be concatenated into SQL.

The basic list query uses a fixed ordering and a service-owned integer limit.
It must not accept a request-provided column name or sort direction.

## 14. Branch and department read strategy

Phase 05 does not create branch or department management pages.

For a maintainable SSR implementation without a new dynamic API:

1. Load active branches.
2. Load active departments in one read operation.
3. Group department options by branch_id in the service or controller view
   data.
4. Render all options with the branch relationship visible, or render
   branch-grouped optgroup elements.
5. Use server-side validation as the authority when the selected branch and
   department do not match.

The form may use JavaScript to hide department groups for the selected
branch, but the feature must remain usable without JavaScript. Do not add an
AJAX endpoint solely to populate departments.

On edit, the service includes the employee's current branch or department
row when it is inactive so that the current value can be displayed and
preserved. It must mark that option as unavailable for a new assignment.

## 15. Data transfer strategy

Use small, explicit shapes rather than raw database objects.

### 15.1 Employee list row

Each list row contains:

- id
- employee_code
- first_name
- last_name
- branch_name
- nullable department_name
- nullable position_title
- employee_type
- status

### 15.2 Employee detail row

The detail shape contains all employee columns needed by the detail view plus:

- branch_code
- branch_name
- department_code
- department_name
- department_status

The repository maps database column names to this documented shape. No
PDOStatement, PDO, or lazy database object escapes the persistence layer.

### 15.3 Form page data

Form data contains:

- normalized/current values;
- field errors;
- branches;
- departments grouped by branch;
- page title and mode;
- employee id when editing;
- a status notice only from an allowlisted service outcome.

The view receives this data as ordinary arrays/scalars and remains free of
business decisions.

## 16. Controller design

Create a thin employee controller with actions corresponding to the routes.
Each action should:

1. read route parameters or body parameters from Request;
2. pass them to the service;
3. select a view or redirect;
4. translate known service outcomes into HTTP status and view data.

The controller must not:

- instantiate PDO, repositories, or services;
- build SQL;
- validate every field itself;
- compare branch and department ownership itself;
- update status directly;
- manage transactions;
- render escaped values manually throughout the action.

The controller may perform the narrow HTTP mapping of an invalid route id to
the existing 404 exception and may pass the request path or safe notice to a
view.

## 17. View structure

Add these server-rendered views under resources/views/employees/:

- index.php
- show.php
- create.php
- edit.php
- deactivate.php
- _form.php

Use the existing layout.php through ViewRenderer::renderPage().

### 17.1 View rules

Every dynamic value must be escaped with HtmlEscaper::escape, including:

- names and kana;
- employee code;
- email and phone;
- position title;
- branch and department names;
- status/type labels;
- validation errors;
- selected form values;
- notice text.

The views must contain:

- no SQL;
- no repository or service calls;
- no PDO;
- no request globals;
- no authorization decisions;
- no substantial business validation.

The shared form partial renders the explicit allowed fields, values, errors,
and branch/department choices. It must preserve submitted values after a 422
response. It must not render a status selector.

The list and detail pages include links with escaped identifiers generated
from trusted integer ids. Deactivation is represented as a POST form, never
as a GET mutation.

## 18. Error handling

Define concrete behavior for each class of failure:

| Situation | Behavior |
| --- | --- |
| Non-positive or non-integer employee id | Existing 404 response |
| Missing employee | Existing 404 response |
| Missing branch/department choice | 422 with field error |
| Inactive new branch/department choice | 422 with field error |
| Cross-branch department | 422 on department_id |
| Invalid scalar/date/type input | 422 with field errors and submitted values |
| Duplicate code/email found before write | 422 on the duplicate field |
| Duplicate code/email raised by a race | 422 on the known field |
| Already inactive deactivation | 303 detail redirect with idempotent notice |
| Unexpected PDO/database failure | Existing centralized 500 response |
| Unsupported HTTP method | Existing 405 response and Allow header |

The application must not expose SQL, DSNs, usernames, passwords, or raw PDO
messages in a user-facing response. The existing ExceptionResponder remains
the final HTTP error boundary.

### 18.1 Duplicate-key race handling

The concrete employee repository may catch a PDOException only around an
employee insert/update statement. If the SQLSTATE and driver error identify
one of the known employee unique constraints (employee_code or email), it
maps the result to a small application conflict outcome. It rethrows other
constraint violations and all unrelated database errors.

This prevents the normal race from becoming a generic 500 while avoiding the
unsafe practice of swallowing every integrity exception.

## 19. Employee lifecycle and timestamps

### 19.1 Deactivation decision

Use status deactivation, not physical deletion, because:

- Phase 04 intentionally restricts deletes from organizational records;
- employee history should remain countable and inspectable;
- deleting an employee would destroy business contact and assignment history;
- inactive status already exists as the domain lifecycle state;
- a future retention policy can define hard deletion separately.

The UI and service must use the term "Deactivate employee," not "Delete
employee," for the actual operation.

### 19.2 Timestamps

The service creates created_at and updated_at values in UTC. Views may format
timestamps for display using the configured application timezone, but they
must not create persistence timestamps.

hire_date is passed to the repository as the validated Y-m-d business date
without timezone conversion.

A small injected clock abstraction is justified for deterministic service
tests:

- application code asks for the current UTC DateTimeImmutable;
- production wiring uses a system clock;
- tests can provide a fixed clock.

The clock must not be a global singleton and must not be accessed by views or
controllers.

## 20. Transaction design

Do not wrap every SELECT in a transaction.

For the current use cases:

- list, detail, and form choice reads require no explicit transaction;
- create is one employee INSERT, with application checks before it and
  database constraints on the write;
- update is one employee UPDATE, with the same constraint protection;
- deactivation is one conditional UPDATE.

These single-statement writes do not require a service-level transaction in
Phase 05. A transaction becomes required if a later change makes a use case
perform multiple writes that must succeed together, such as creating an
employee and a related history row.

The repository must keep each write atomic and must not issue unrelated
writes as a side effect.

## 21. Security boundaries

### Implemented in Phase 05

- prepared statements for all variable SQL values;
- explicit allowlisted input fields;
- positive identifier validation;
- server-side branch/department consistency checks;
- database foreign keys and unique constraints;
- escaped HTML output;
- POST-only state changes;
- no raw database errors in responses;
- no credentials or secrets in view data or diagnostics.

### Deferred intentionally

Authentication, role authorization, session handling, and CSRF protection
are not currently present in the existing project and belong to Phases 07–08.
Phase 05 must not build a competing security subsystem.

Until those phases are complete, employee routes are a development feature
without access control and must not be described as production-secure. The
implementation should leave route/controller boundaries suitable for later
authentication, authorization, and CSRF middleware. When CSRF infrastructure
exists, all create/update/deactivation forms must use it.

## 22. Dependency injection and composition root

Extend ApplicationBootstrap and routes/web.php without moving dependency
construction into controller methods.

The composition root should wire:

1. the existing Configuration;
2. a lazy PDO connection provider backed by ConnectionFactory;
3. concrete employee, branch-read, and department-read PDO repositories;
4. the employee validator and UTC clock;
5. the employee application service;
6. the employee controller;
7. route registrations.

The lazy provider is important because GET / must continue to render the setup
page without requiring DB_DATABASE, DB_USERNAME, or a running MySQL server.
An employee route may fail through the existing centralized error boundary if
database configuration or connectivity is unavailable.

No service locator or static global database connection is allowed.

## 23. Testing strategy

Preserve all existing tests and add only tests that prove meaningful Phase 05
behavior.

### 23.1 Validator and service unit tests

Use focused test doubles for repository contracts and the clock where that
simplifies application tests. Cover:

- required and maximum-length rules;
- scalar-vs-array input rejection;
- valid and invalid email;
- invalid and impossible dates;
- optional empty values becoming null;
- allowed employee types;
- valid branch lookup;
- missing/inactive branch;
- missing/inactive department;
- cross-branch department rejection;
- create defaulting status to active;
- status not being accepted from request input;
- duplicate code/email pre-checks;
- update uniqueness excluding the current id;
- submitted values surviving validation failure;
- successful create/update/deactivation orchestration;
- already-inactive deactivation as a no-op;
- UTC timestamp generation.

### 23.2 Repository integration tests

Run against the real isolated MySQL/MariaDB database using the existing
APP_ENV=test and DB_TEST_* safeguards. Never use normal development
credentials and never guess a database name.

Apply the existing migrations and create deterministic company, branch,
department, and employee fixtures. Cover:

- basic list with branch and department names;
- deterministic list ordering and safety limit;
- finding an existing employee;
- missing employee returning null;
- inserting with a department;
- inserting with a null department;
- updating employee fields;
- deactivating an active employee;
- repeated deactivation being a no-op;
- duplicate employee code rejection;
- duplicate email rejection;
- update uniqueness excluding its own row;
- invalid branch rejection;
- cross-branch department rejection;
- multibyte names and apostrophes through prepared statements;
- stored UTC timestamps and date values.

Do not mock PDO when proving SQL behavior. Use test doubles only at the
service boundary.

### 23.3 HTTP and feature tests

Using the existing Request, HttpKernel, router, and response testing style,
verify:

- GET /employees returns 200 HTML;
- GET /employees/create returns 200 HTML;
- GET /employees/{id} returns detail HTML;
- GET /employees/{missing} returns 404;
- GET /employees/{id}/edit returns 200 HTML;
- GET /employees/{id}/deactivate returns confirmation HTML;
- invalid POST /employees returns 422 and preserves submitted values;
- successful POST /employees returns 303 and a detail Location;
- invalid POST /employees/{id} returns 422;
- successful update returns 303;
- successful deactivation returns 303 and changes status;
- unsupported methods return 405;
- list/detail/form output escapes HTML-sensitive employee data;
- the existing setup route remains usable without a database connection.

If the existing feature-test infrastructure cannot connect an HTTP kernel to
the isolated database, add a narrowly scoped composition helper for tests;
do not bypass the service/repository boundaries or replace integration tests
with SQL mocks.

## 24. Test data and isolation

Test data must be created by the test itself with unique deterministic
suffixes or isolated setup. Tests must not depend on manually existing
development rows.

Before any destructive schema cleanup, preserve the Phase 03 checks:

- APP_ENV=test;
- all DB_TEST_* variables explicitly present;
- test database name ends in _test;
- test database differs from DB_DATABASE.

If the configured test database is unavailable, follow the existing
integration-test skip behavior and report the missing dependency clearly.

## 25. Manual verification

After automated tests, verify in a browser:

1. Open the employee list.
2. Open an employee detail page.
3. Open the create form.
4. Submit invalid data and confirm field errors and submitted values remain.
5. Create a valid employee and confirm the 303/GET result.
6. Edit the employee and confirm changed values.
7. Try a department from another branch and confirm server-side rejection.
8. Open the deactivation confirmation.
9. Deactivate the employee and confirm the record remains visible as inactive.
10. Repeat deactivation and confirm the idempotent result.
11. Verify names, email, phone, and errors containing HTML-sensitive
    characters render as text rather than markup.
12. Refresh after each successful POST and confirm the browser does not
    resubmit the form.
13. Open GET / without database credentials and confirm the existing setup
    page still works.

Manual checks complement, but do not replace, automated tests.

## 26. Expected implementation files

The following is the minimum expected shape, adjusted only if an existing
convention makes an equivalent location clearer:

- src/Application/Services/EmployeeService.php
- src/Application/Validation/EmployeeInputValidator.php
- src/Application/DTO/EmployeeInput.php or an equally small value object
- src/Application/Support/Clock.php
- src/Http/Controllers/EmployeeController.php
- src/Domain/Repository/EmployeeRepositoryInterface.php
- src/Domain/Repository/BranchReadRepositoryInterface.php
- src/Domain/Repository/DepartmentReadRepositoryInterface.php
- src/Infrastructure/Persistence/PdoEmployeeRepository.php
- src/Infrastructure/Persistence/PdoBranchReadRepository.php
- src/Infrastructure/Persistence/PdoDepartmentReadRepository.php
- src/Infrastructure/Time/SystemClock.php
- a small lazy PDO provider in the existing database/infrastructure boundary
- resources/views/employees/index.php
- resources/views/employees/show.php
- resources/views/employees/create.php
- resources/views/employees/edit.php
- resources/views/employees/deactivate.php
- resources/views/employees/_form.php
- updates to routes/web.php
- updates to src/Bootstrap/ApplicationBootstrap.php
- focused unit, integration, and HTTP tests under the existing tests/
  conventions

Do not add managers, handlers, command buses, factories, abstract
repositories, generic service bases, or a full domain entity model unless an
actual implementation need appears and is documented.

## 27. Implementation order

Implement in this order:

1. Confirm the existing Phase 04 schema and test-database configuration.
2. Add explicit read-model shapes, employee input, validator, and validation
   result behavior.
3. Add repository interfaces and PDO implementations with prepared SQL.
4. Add the lazy connection provider and wire it through the composition root.
5. Add the employee service and its unit tests.
6. Add the controller and view data mapping.
7. Add employee views and escaping coverage.
8. Register routes in static-before-dynamic order.
9. Add repository integration tests against the isolated database.
10. Add HTTP/feature regression tests.
11. Run the full existing test suite.
12. Complete the manual browser checklist.

No migration is expected for Phase 05. If implementation discovers a genuine
Phase 04 schema defect, stop and document it as a separately reviewed schema
change rather than silently modifying this feature.

## 28. Acceptance criteria

Phase 05 is complete only when:

1. The implementation follows the explicitly assigned branch for the phase.
2. The employee list, detail, create, edit, and deactivation use cases are
   available at the specified SSR routes.
3. Static routes are registered before /employees/{id}.
4. Successful writes use POST -> 303 -> GET.
5. Invalid identifiers and missing employees produce the existing 404
   behavior.
6. Validation is centralized in a small explicit validator and preserves
   submitted values and field errors.
7. Only the documented employee fields can be written from a request.
8. New employees default to active; ordinary edit cannot change status.
9. Branch and department choices are validated in the service, and a
   cross-branch department cannot be assigned.
10. Employee code and email uniqueness checks exclude the current employee
    during update.
11. Known duplicate-key races become useful field errors, while unrelated
    database exceptions reach centralized error handling.
12. SQL exists only in PDO repositories and uses prepared statements for
    variable values.
13. Views contain no SQL or repository calls and escape all dynamic output.
14. Deactivation updates status and preserves the employee row; no employee
    DELETE operation is exposed.
15. Repeated deactivation is an idempotent no-op, and reactivation is
    deferred.
16. created_at and updated_at are generated by application code in UTC;
    hire_date remains a calendar date.
17. The normal setup route does not require a database connection merely to
    boot the application.
18. Authentication, authorization, CSRF, and other deferred security work is
    documented honestly and not falsely implied to exist.
19. Repository integration tests exercise real database constraints and
    prepared statements using only the isolated test database.
20. HTTP/service tests cover success, validation failure, not-found, escaped
    output, redirects, and regression behavior.
21. The manual verification checklist passes.

## 29. Deferred work

Later specifications may add:

- authentication and secure sessions;
- CSRF middleware;
- Admin/User authorization;
- organization CRUD;
- employee search, filtering, sorting, and pagination;
- reactivation or a richer employment lifecycle;
- employment history and dispatch contracts;
- photo uploads;
- portfolios, skills, projects, and certifications;
- dashboards and production hardening.

Those phases must preserve the Phase 05 boundaries: controllers remain thin,
services own use-case coordination, repositories own SQL, views remain
presentation-only, and database constraints remain the final integrity
boundary.
