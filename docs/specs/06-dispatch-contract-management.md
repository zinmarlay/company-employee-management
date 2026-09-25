# Phase 06: Dispatch Company and Contract Management Specification

## 1. Document purpose

This document specifies Phase 06 of the Company Employee Management System:
server-rendered management of dispatch companies and dispatched-employee
contracts. It is an implementation specification. It does not itself create
PHP classes, migrations, routes, views, or tests.

Phase 06 builds on the Pure PHP, SSR, PDO, custom-router, migration, and
employee-management boundaries established in Phases 01–05. It must extend
those boundaries rather than introduce a framework, ORM, SPA, REST API, or a
parallel application architecture.

The implementation branch is not assigned by this document. Implementation
must use the explicitly assigned project branch and must not invent a branch
name here.

## 2. Phase objective

Add the dispatch-management workflows needed for employees whose
`employee_type` is `dispatched`:

- dispatch-company list, detail, create, edit, and deactivation;
- dispatch-contract create, detail, edit, and explicit renewal;
- preserved contract history;
- overlap prevention for one employee's contract periods;
- strict contract-date validation;
- reusable expiration classification;
- dispatch information on dispatched employee detail pages.

The primary workflow remains:

```text
HTTP request
  -> controller
  -> application service
  -> repository interface
  -> PDO repository
  -> MySQL/MariaDB
```

Successful writes use POST -> 303 See Other -> GET. Validation and business
rule failures render the submitted form with HTTP 422. Missing resources use
the existing 404 behavior.

## 3. Scope

### 3.1 Included

- Dispatch-company master records with active/inactive lifecycle.
- Dispatch-company list, detail, create, edit, and deactivation pages.
- Dispatch-contract records joining one employee to one dispatch company.
- Contract create, detail, edit, and renewal pages.
- Employee-type, dispatch-company, date, and overlap business rules.
- Explicit input DTOs and centralized validators.
- Focused repository interfaces and prepared PDO implementations.
- Employee detail integration with one contract-history read path.
- Unit, repository-integration, and HTTP/feature tests.
- A forward migration only when the inspected database genuinely lacks the
  tables specified here.
- Manual browser verification for the new workflows and regressions.

### 3.2 Existing schema and migration finding

The current repository's Phase 04 migrations create only:

```text
companies
branches
departments
employees
```

They do not create dispatch-company or dispatch-contract tables. Therefore,
unless a target database contains an already-approved equivalent schema that
is not represented in the repository migrations, implementation is expected
to add a forward migration for the two Phase 06 tables.

Before implementation:

1. Inspect every existing migration and the actual test-database schema.
2. Compare existing tables, columns, indexes, foreign keys, and nullability
   with this specification.
3. Reuse an approved equivalent schema when it exists.
4. Do not create duplicate tables or silently alter an accepted migration.
5. If a genuine schema change is required, add a new migration using the
   exact `MigrationDiscovery` filename and namespace convention.

The schema in this document is the Phase 06 baseline for a repository that
does not already have an approved dispatch schema. An existing approved
schema remains the source of truth; implementation must adapt field names and
nullable rules to it rather than redesign it for convenience.

## 4. Explicit non-goals

Phase 06 does not add or imply:

- authentication, login, passwords, sessions, or user identities;
- authorization or Admin/User permissions;
- CSRF protection unless already present in the application;
- Laravel, Symfony, an ORM, Active Record, or a query builder;
- React, Vue, another SPA framework, or an API architecture;
- payroll, attendance, billing, invoicing, or rate calculation;
- email notifications, scheduled jobs, or background workers;
- dashboard redesign or a notification center;
- contract documents, uploads, signatures, or file storage;
- employee reactivation or dispatch-company reactivation UI;
- hard deletion of dispatch companies or contracts;
- employee search, filtering, sorting controls, or pagination;
- automated status transitions beyond derived expiration classification;
- overlapping-contract repair or historical-data migration;
- a general-purpose repository, service base class, entity framework, or
  command bus.

Expiration classification is included now. A later phase may use it in a
dashboard or notification system.

## 5. Architecture and dependency rules

Preserve the existing request flow:

```text
Browser
  -> public/index.php
  -> ApplicationBootstrap
  -> Request
  -> HttpKernel
  -> Router
  -> MiddlewarePipeline
  -> Controller
  -> Application Service
  -> Repository Interface
  -> PDO Repository
  -> Database
  -> ViewRenderer
  -> Response
  -> ResponseEmitter
  -> Browser
```

Controllers:

- parse route, query, and body input;
- validate route identifiers at the HTTP boundary;
- pass allowlisted request data to services;
- select view data and HTTP status;
- translate successful writes to redirects;
- do not contain SQL or substantial business rules.

Services:

- coordinate application use cases;
- enforce employee type, company lifecycle, date, and overlap rules;
- call repository interfaces;
- generate UTC persistence timestamps through the existing `Clock`;
- return explicit read shapes and form results suitable for SSR.

Repository interfaces:

- define focused persistence contracts for the use cases;
- expose read shapes rather than PDO statements;
- do not become generic CRUD abstractions.

PDO repositories:

- own SQL and joins;
- use prepared statements for values;
- select explicit columns where practical;
- provide deterministic ordering;
- translate meaningful duplicate or persistence failures at the repository
  boundary where that matches existing conventions.

Views:

- receive prepared arrays/scalars only;
- contain no SQL, repository, service, or global request access;
- escape every dynamic value using the existing `HtmlEscaper` convention.

## 6. Database schema

### 6.1 Modeling decisions

1. A dispatch company is an external staffing company. It is separate from
   the internal `companies` table and must not be represented by a branch or
   employee field.
2. A dispatch contract is an immutable-in-history assignment period linking
   one employee and one dispatch company.
3. Contract history is represented by multiple rows. Renewal inserts a new
   row; it never transforms the previous row into the new period.
4. Contract expiration is derived from `end_date` and a reference date. No
   stored contract status is required in the baseline schema, avoiding stale
   `active` or `expired` values. If an already-approved schema has a status
   column, preserve and validate it according to that schema.
5. A contract has a required end date. Open-ended contracts are out of scope
   until a later schema decision explicitly supports them.
6. Foreign keys use `ON UPDATE RESTRICT` and `ON DELETE RESTRICT`. The
   application deactivates dispatch companies instead of deleting them.
7. Application rules are mandatory even where the database has a supporting
   constraint. The database remains the final integrity boundary.

### 6.2 `dispatch_companies`

The baseline table is:

| Column       | SQL type          | Null/default                | Purpose                      |
| ------------ | ----------------- | --------------------------- | ---------------------------- |
| `id`         | `BIGINT UNSIGNED` | PK, auto increment          | Internal key                 |
| `code`       | `VARCHAR(30)`     | `NOT NULL`                  | Stable external-company code |
| `name`       | `VARCHAR(160)`    | `NOT NULL`                  | Company display name         |
| `phone`      | `VARCHAR(32)`     | `NULL`                      | Optional contact phone       |
| `email`      | `VARCHAR(254)`    | `NULL`                      | Optional contact email       |
| `address`    | `VARCHAR(500)`    | `NULL`                      | Optional contact address     |
| `status`     | `VARCHAR(20)`     | `NOT NULL DEFAULT 'active'` | `active` or `inactive`       |
| `created_at` | `DATETIME`        | `NOT NULL`                  | UTC creation time            |
| `updated_at` | `DATETIME`        | `NOT NULL`                  | UTC last-update time         |

Constraints and indexes:

- `PRIMARY KEY (id)`;
- `UNIQUE KEY uq_dispatch_companies_code (code)`;
- `CHECK (status IN ('active', 'inactive'))` when the supported engine
  enforces checks, following the Phase 04 engine baseline;
- `utf8mb4`, `utf8mb4_unicode_ci`, and `ENGINE=InnoDB`;
- no speculative name, email, or phone indexes.

If the inspected approved schema does not have a company code, the
implementation must omit the code field from DTOs, forms, queries, and
validation rather than adding it merely for symmetry.

### 6.3 `dispatch_contracts`

The baseline table is:

| Column                | SQL type          | Null/default       | Purpose                   |
| --------------------- | ----------------- | ------------------ | ------------------------- |
| `id`                  | `BIGINT UNSIGNED` | PK, auto increment | Internal key              |
| `employee_id`         | `BIGINT UNSIGNED` | `NOT NULL`         | Dispatched employee       |
| `dispatch_company_id` | `BIGINT UNSIGNED` | `NOT NULL`         | External staffing company |
| `start_date`          | `DATE`            | `NOT NULL`         | First contract day        |
| `end_date`            | `DATE`            | `NOT NULL`         | Last contract day         |
| `created_at`          | `DATETIME`        | `NOT NULL`         | UTC creation time         |
| `updated_at`          | `DATETIME`        | `NOT NULL`         | UTC last-update time      |

Constraints and indexes:

- `PRIMARY KEY (id)`;
- `KEY idx_dispatch_contracts_employee_period
(employee_id, start_date, end_date)`;
- `KEY idx_dispatch_contracts_company (dispatch_company_id)`;
- `FOREIGN KEY (employee_id) REFERENCES employees(id)` with update/delete
  restrict;
- `FOREIGN KEY (dispatch_company_id) REFERENCES dispatch_companies(id)` with
  update/delete restrict;
- `CHECK (start_date <= end_date)` when enforced by the supported engine;
- `ENGINE=InnoDB`, `utf8mb4`, and the existing compatible collation.

The database cannot portably enforce the no-overlap rule with the current
schema. The application service must enforce it through a repository query,
and repository integration tests must prove the query behavior. The date
check is a supporting database boundary, not a replacement for validator and
service validation.

### 6.4 Referential and lifecycle behavior

- Employees and dispatch companies referenced by a contract cannot be hard
  deleted through this phase.
- Deactivating a dispatch company does not change or delete its contracts.
- Historical contracts remain readable after company deactivation.
- A new or renewed contract normally requires an active dispatch company.
- A new or edited contract requires an employee whose current
  `employee_type` is `dispatched`.
- Changing an employee from `dispatched` to `permanent` is outside Phase 06;
  if later implemented, existing contract history must remain readable and
  the transition must define what happens to future periods.

### 6.5 Migration strategy

If no approved dispatch schema exists, add one forward migration after the
employee migration, for example using the next project-approved numeric
version and a name such as `CreateDispatchCompaniesAndContracts`. The exact
filename must be discovered from `MigrationDiscovery` and existing migration
tests before implementation; this document does not authorize inventing a
version that collides with another migration.

The migration may create both tables in dependency order, or use one
migration per table if that is the established convention. `down()` must drop
`dispatch_contracts` before `dispatch_companies`. Do not edit the accepted
Phase 04 migration files. MySQL/MariaDB DDL may implicitly commit, so neither
migration nor test documentation may claim universal transactional rollback.

## 7. Domain values and read shapes

### 7.1 Allowed values

- Dispatch-company status: `active`, `inactive`.
- Employee type consumed by this phase: `dispatched`; `permanent` is
  explicitly rejected for contracts.
- Contract expiration classification: `expired`, `expiring_7`,
  `expiring_30`, `normal`.

Expiration classification is derived and is not a replacement for a stored
contract status in an already-approved schema.

### 7.2 Company list/detail read shapes

The service should return documented arrays with at least:

```text
id, code (when present), name, phone, email, address, status,
created_at, updated_at
```

Company detail may include a single joined or separately batched
`contracts` collection. It must not issue one query per rendered contract or
one query per list row. If the existing read path uses a separate explicit
history query, that query must be bounded and deterministic.

### 7.3 Contract read shapes

A contract detail/history row should include:

```text
id, employee_id, employee_code, employee_name,
dispatch_company_id, dispatch_company_code (when present),
dispatch_company_name, start_date, end_date,
expiration_classification, created_at, updated_at
```

The exact keys may follow the existing project naming style, but views must
not need to perform any joins or date calculations.

Employee detail dispatch data should contain, for dispatched employees:

```text
dispatch_company (nullable),
current_contract (nullable),
contract_history (ordered list)
```

Permanent employees must receive an explicit empty/non-dispatch shape and
must not display a misleading dispatch section.

## 8. Routes and HTTP semantics

Register static routes before dynamic `/{id}` routes, matching the current
router's registration-order behavior.

### 8.1 Dispatch-company routes

| Method | Path                                  | Use case                  | Success       |
| ------ | ------------------------------------- | ------------------------- | ------------- |
| GET    | `/dispatch-companies`                 | List companies            | 200 HTML      |
| GET    | `/dispatch-companies/create`          | Create form               | 200 HTML      |
| POST   | `/dispatch-companies`                 | Create company            | 303 to detail |
| GET    | `/dispatch-companies/{id}/deactivate` | Deactivation confirmation | 200 HTML      |
| POST   | `/dispatch-companies/{id}/deactivate` | Deactivate company        | 303 to detail |
| GET    | `/dispatch-companies/{id}/edit`       | Edit form                 | 200 HTML      |
| POST   | `/dispatch-companies/{id}`            | Update company            | 303 to detail |
| GET    | `/dispatch-companies/{id}`            | Detail                    | 200 HTML      |

The static create, deactivate, and edit paths must be registered before the
final detail route. GET deactivation only renders confirmation; only POST
mutates status. Repeated deactivation is an idempotent no-op where practical.

### 8.2 Dispatch-contract routes

| Method | Path                             | Use case                | Success                         |
| ------ | -------------------------------- | ----------------------- | ------------------------------- |
| GET    | `/dispatch-contracts/create`     | Create form             | 200 HTML                        |
| POST   | `/dispatch-contracts`            | Create contract         | 303 to contract/employee detail |
| GET    | `/dispatch-contracts/{id}/renew` | Renewal form            | 200 HTML                        |
| POST   | `/dispatch-contracts/{id}/renew` | Insert renewed contract | 303                             |
| GET    | `/dispatch-contracts/{id}/edit`  | Edit existing period    | 200 HTML                        |
| POST   | `/dispatch-contracts/{id}`       | Update existing period  | 303                             |
| GET    | `/dispatch-contracts/{id}`       | Contract detail         | 200 HTML                        |

The create form may accept an `employee_id` query parameter so an employee
detail page can link to `/dispatch-contracts/create?employee_id={id}`. The
service must still validate the employee from submitted input; query
parameters are form context, not authorization or persistence input.

The renewal routes are intentionally separate from edit routes. A renewal
POST always inserts a new row and never updates the source contract.

### 8.3 Employee integration link

For a dispatched employee, the detail page may link to the contract create
form and to each existing contract's detail, edit, and renew actions. A
permanent employee has no dispatch-contract create action and no dispatch
contract section.

### 8.4 Status mapping

- 200 for successful GET and rendered forms;
- 303 for every successful state-changing POST;
- 404 for invalid/non-positive identifiers and missing company, employee, or
  contract resources;
- 405 from the existing router for unsupported methods;
- 422 for malformed input, validation errors, or expected business-rule
  failures such as permanent employees, inactive companies, and overlaps;
- 500 only for unexpected failures handled by the existing centralized
  exception responder.

## 9. Dispatch-company use cases

### 9.1 List

`GET /dispatch-companies` displays active and inactive dispatch companies as
business records. Use a fixed application safety limit consistent with the
Phase 05 list convention; do not expose that cap as pagination. The ordering
must be deterministic, for example:

1. `name` ascending;
2. `code` ascending when present;
3. `id` ascending as the final tie-breaker.

Each row may show contract count if the query can provide it without N+1
queries. It must include detail/edit links and a deactivation link only when
the company is active.

### 9.2 Detail

`GET /dispatch-companies/{id}` displays company fields, status, timestamps,
and related contract information. Related contracts must be loaded using an
explicit joined/batched repository operation with deterministic ordering. An
inactive company remains readable and its historical contracts remain
visible.

### 9.3 Create

`GET /dispatch-companies/create` renders an empty form. `POST
/dispatch-companies` must:

1. allowlist fields;
2. map the body to a `DispatchCompanyInput` (or project-equivalent DTO);
3. validate required fields, lengths, email format, and normalized values;
4. default status to `active` rather than accepting status from ordinary
   create input;
5. persist application-generated UTC timestamps;
6. return 303 to `/dispatch-companies/{id}` on success.

### 9.4 Edit

`GET /dispatch-companies/{id}/edit` loads the company or returns 404. `POST
/dispatch-companies/{id}` validates and updates editable contact/master
fields. Ordinary edit does not accept a status field; deactivation has its
own confirmation and POST route. The successful response is a 303 to detail.

### 9.5 Deactivation

`GET /dispatch-companies/{id}/deactivate` renders confirmation only. `POST
/dispatch-companies/{id}/deactivate` changes `active` to `inactive`, updates
`updated_at`, and redirects to detail. If already inactive, the operation
returns the same successful redirect and does not fail. No DELETE operation is
exposed.

## 10. Dispatch-contract use cases

### 10.1 Create

`GET /dispatch-contracts/create` loads the employee and active dispatch-
company choices needed by the form. If an `employee_id` query value is
provided, it preselects that employee only after validating the identifier.

`POST /dispatch-contracts` must:

1. allowlist `employee_id`, `dispatch_company_id`, `start_date`, and
   `end_date` (plus only fields required by an approved existing schema);
2. validate scalar types, positive IDs, strict dates, and field lengths;
3. load the employee through a read repository and require
   `employee_type = dispatched`;
4. load the dispatch company and require it to exist and be active;
5. reject `start_date > end_date`;
6. reject an overlapping period for the same employee;
7. insert a new contract with UTC `created_at` and `updated_at`;
8. redirect with 303 to the new contract detail or the employee detail page.

### 10.2 Detail

`GET /dispatch-contracts/{id}` displays the contract, employee identity,
dispatch company identity, date range, derived expiration classification,
timestamps, and links to edit and renew. It must remain readable if the
company is inactive. A missing contract is 404.

### 10.3 Edit

Edit corrects information belonging to the existing period. It may change
the employee, company, start date, or end date only if the approved schema and
business design allow those fields; the baseline permits those contract
attributes but always re-applies employee type, company eligibility, date, and
overlap validation. The overlap query excludes the contract's own ID.

The update changes `updated_at` but does not create a history row. If a user
needs a new period, they must use renewal.

### 10.4 Renew

`GET /dispatch-contracts/{id}/renew` loads the source contract and uses its
employee/company values as form defaults. It may suggest a next period based
on the existing end date, but must not silently invent a date that the user
did not submit.

`POST /dispatch-contracts/{id}/renew` validates the submitted next period and
creates a new contract row. It must:

- preserve the source contract unchanged;
- require a dispatched employee;
- require an active dispatch company for the new record;
- enforce strict dates and no overlap against all other rows;
- redirect with 303 after insertion.

Renewal is not an update disguised as an insert and must have separate tests
proving both row preservation and new-row creation.

### 10.5 Employee contract history

Employee detail for a dispatched employee displays contract history ordered
deterministically, normally by `start_date DESC`, `end_date DESC`, and `id
DESC` so the latest period is first. The service identifies the current/latest
relevant contract without putting date comparisons in the view. The complete
history remains available, including expired contracts and contracts whose
company is now inactive.

For a permanent employee, the detail page omits dispatch-company and
contract-history content rather than showing an empty or misleading dispatch
assignment.

## 11. Business rules

### 11.1 Employee type

Only an employee whose current `employee_type` is exactly `dispatched` may be
used for contract create, edit, or renewal. A permanent employee must be
rejected in the application/service layer even if a malicious request bypasses
the form.

### 11.2 Company eligibility

A contract create or renewal must reference an existing active dispatch
company. An edit that retains a company which has since become inactive must
not make historical data unreadable; the implementation must define one of
the following based on the approved project policy:

- permit correction of dates/employee while retaining the existing inactive
  company, while rejecting a new selection of an inactive company; or
- reject any edit that would persist an inactive company and direct the user
  to a new active-company period.

The baseline recommendation is the first option for historical safety: an
existing inactive company may remain attached to its existing contract, but a
new company selection and all renewal/create operations require active status.
The service must make this distinction explicit and test it.

### 11.3 Strict dates

Contract dates use exact `Y-m-d` validation, matching Phase 05 date handling:

- reject non-string/scalar misuse according to existing validator behavior;
- reject impossible dates such as `2026-02-30`;
- reject alternate formats such as `2026-1-1`;
- require `start_date <= end_date`;
- preserve submitted values and field-level errors on failure.

The inclusive range means a contract covers both start and end dates.

### 11.4 Overlap

For the same employee, a candidate period overlaps an existing period when:

```text
candidate_start <= existing_end
AND candidate_end >= existing_start
```

This rejects any shared calendar day. Adjacent periods are valid when the
next start is the day after the previous end, for example:

```text
2026-01-01 .. 2026-03-31
2026-04-01 .. 2026-06-30
```

The repository should expose a focused `hasOverlap()` query that accepts the
employee ID, candidate dates, and optional excluded contract ID. The service
must run it for create, renewal, and edit. Edit excludes its own ID; create
and renewal do not.

### 11.5 Expiration classification

Given a reference date `R` and inclusive contract `end_date` `E`:

| Classification | Rule                            |
| -------------- | ------------------------------- |
| `expired`      | `E < R`                         |
| `expiring_7`   | `R <= E <= R + 7 days`          |
| `expiring_30`  | `R + 7 days < E <= R + 30 days` |
| `normal`       | `E > R + 30 days`               |

Boundary examples for reference date `2026-10-01`:

- `2026-09-30` -> `expired`;
- `2026-10-01` and `2026-10-08` -> `expiring_7`;
- `2026-10-09` and `2026-10-31` -> `expiring_30`;
- `2026-11-01` -> `normal`.

Put this logic in a small unit-testable application/domain value or service.
Use the existing injected `Clock` for the default reference date. Do not call
`new DateTimeImmutable('now')` throughout services or views.

## 12. DTOs and validation

Follow the Phase 05 explicit-input approach. Raw `$_POST`/body arrays stop at
the controller boundary and are not passed through every application layer.

### 12.1 Suggested DTOs

Names may follow established namespace conventions, but the implementation
should provide small immutable structures equivalent to:

- `DispatchCompanyInput`: code when present, name, phone, email, address;
- `DispatchContractInput`: employee ID, dispatch-company ID, start date, end
  date.

Status is not accepted from ordinary company create/edit or contract forms
unless an approved existing schema explicitly requires a user-managed status.

### 12.2 Company validator

The validator must:

- allow only approved fields;
- reject arrays where scalar values are required;
- trim string values;
- enforce schema maximums: code 30 when present, name 160, phone 32,
  email 254, address 500;
- validate email only when a non-empty email is supplied;
- normalize empty optional contact fields to `null` in the DTO;
- preserve form-display values for invalid input;
- return structured field errors.

### 12.3 Contract validator

The validator must:

- allow only employee/company IDs and date fields defined by the schema;
- require positive integer IDs using the existing strict scalar convention;
- validate exact `Y-m-d` dates;
- reject impossible dates;
- return `start_date`/`end_date` errors for ordering failure;
- preserve submitted values when business or validation errors render 422.

Employee type, company existence/status, and overlap are service rules, not
view-only restrictions and not validator-only assumptions.

Unexpected fields must not be persisted. SQL must name writable columns
explicitly.

## 13. Repository contracts

Use focused interfaces under the existing domain namespace conventions.

### 13.1 Dispatch-company repository

The concrete interface should provide operations equivalent to:

```text
listBasic(limit): list<company-read>
findById(id): company-read|null
insert(input, createdAt, updatedAt): int
update(id, input, updatedAt): void
deactivate(id, updatedAt): bool
listContracts(companyId): list<contract-read>
```

`listContracts()` may instead live on the contract repository if that is the
existing ownership convention. It must be an explicit, bounded read rather
than a view-level lookup.

### 13.2 Dispatch-contract repository

The concrete interface should provide operations equivalent to:

```text
findById(id): contract-read|null
findHistoryByEmployeeId(employeeId): list<contract-read>
findByCompanyId(companyId): list<contract-read>
hasOverlap(employeeId, startDate, endDate, exceptId): bool
insert(input, createdAt, updatedAt): int
update(id, input, updatedAt): void
```

The contract repository may also expose a dedicated employee-detail read
shape that joins the employee and dispatch-company names. Avoid duplicating
the same join in controllers or views.

The existing employee repository/read path must expose enough employee data
to validate `employee_type` and render employee identity. Extend the current
repository or add a narrow read interface; do not introduce a generic
repository base.

### 13.3 PDO behavior

`PdoDispatchCompanyRepository` and `PdoDispatchContractRepository` must:

- use the existing lazy PDO provider;
- use native prepared statements for values;
- keep SQL out of services/controllers/views;
- select explicit columns and aliases for read shapes;
- use deterministic ordering in every list/history query;
- avoid N+1 reads for company detail and employee detail;
- translate known unique/foreign-key races only where the application can
  present a useful error;
- let unrelated exceptions reach centralized error handling.

## 14. Application services

### 14.1 DispatchCompanyService

Responsibilities:

- list and map company rows;
- load company detail and related contract read data;
- prepare create/edit forms and choice data;
- validate DTOs and persistence/business errors;
- default new companies to active;
- update editable company fields;
- perform idempotent deactivation;
- generate UTC timestamps through `Clock`.

It must not build SQL or accept arbitrary request arrays after the controller
mapping boundary.

### 14.2 DispatchContractService

Responsibilities:

- prepare create, edit, and renewal forms;
- load contract detail and history;
- validate employee type and company eligibility;
- enforce date ordering and overlap rules;
- coordinate create, update, and renewal;
- ensure renewal inserts rather than updates;
- calculate expiration classification using the injected clock;
- supply employee-detail dispatch read data.

The service must distinguish these outcomes so controllers can map them to
404 or 422 correctly:

- missing target resource;
- invalid submitted input;
- permanent employee;
- missing/ineligible company;
- overlap;
- successful insert/update.

## 15. Controllers and views

### 15.1 Controllers

Add focused controllers equivalent to:

- `DispatchCompanyController`;
- `DispatchContractController`.

Controllers should mirror `EmployeeController` conventions:

- parse and validate positive numeric route IDs;
- call the corresponding service;
- render `ViewRenderer::renderPage()` output;
- set 422 on validation/business form failures;
- throw the existing `NotFoundException` for missing resources;
- use `Response::redirect()` for 303 success responses;
- convert display timestamps in the same application timezone convention.

### 15.2 Company views

Expected view shape, adjusted only for existing conventions:

```text
resources/views/dispatch-companies/index.php
resources/views/dispatch-companies/show.php
resources/views/dispatch-companies/create.php
resources/views/dispatch-companies/edit.php
resources/views/dispatch-companies/deactivate.php
resources/views/dispatch-companies/_form.php
```

### 15.3 Contract views

Expected view shape:

```text
resources/views/dispatch-contracts/create.php
resources/views/dispatch-contracts/show.php
resources/views/dispatch-contracts/edit.php
resources/views/dispatch-contracts/renew.php
resources/views/dispatch-contracts/_form.php
```

The exact form partial structure may follow employee views. All submitted
values and errors must be rendered after escaping. Dates remain calendar
values; UTC timestamps are displayed using the existing configured display
timezone.

## 16. Bootstrap, routes, and lazy PDO

Update `ApplicationBootstrap` by constructor-injecting the new repositories,
validators, services, and controllers using the existing composition-root
style. Update `routes/web.php` with static-before-dynamic ordering.

The root/setup route must continue to render without database credentials or
a running database when that behavior is currently guaranteed. Constructing
the dispatch feature must not eagerly call `PDO` or run a query. Use the
existing `LazyPdoConnection` boundary and preserve all Phase 05 lazy-boot
tests.

## 17. Testing strategy

Preserve all Phase 01–05 tests and add tests that prove Phase 06 behavior.

### 17.1 Unit tests

Use fake repositories and a deterministic clock where appropriate. Cover:

- company required fields and maximum lengths;
- optional contact values becoming null;
- invalid company email;
- scalar-vs-array input rejection;
- unexpected fields not entering DTOs;
- contract positive-ID validation;
- impossible dates and non-`Y-m-d` values;
- start date after end date;
- permanent employee rejected;
- missing employee rejected;
- missing/inactive company rejected for new contract;
- existing inactive company handling on approved edit policy;
- overlapping periods rejected;
- adjacent periods accepted;
- edit overlap excluding its own ID;
- renewal preserving source contract and calling insert for a new row;
- create defaulting company status to active;
- ordinary company edit not changing status;
- idempotent company deactivation;
- expired, expiring-7, expiring-30, and normal classifications;
- exact expiration boundary dates;
- UTC timestamps generated through the fake clock;
- submitted values preserved with errors.

### 17.2 Repository integration tests

Use only the isolated test database configuration:

```text
APP_ENV=test
DB_TEST_HOST
DB_TEST_PORT
DB_TEST_DATABASE
DB_TEST_USERNAME
DB_TEST_PASSWORD
DB_TEST_CHARSET
```

Before destructive setup/cleanup, assert the existing safety conditions:

- `APP_ENV` is `test`;
- all `DB_TEST_*` values are explicitly present;
- the test database name ends in `_test`;
- the test database differs from `DB_DATABASE`.

Apply migrations through the existing runner and create deterministic,
isolated company, branch, department, employee, dispatch-company, and
contract fixtures. Cover:

- company insert, read, update, and deactivation;
- repeated company deactivation;
- company list deterministic ordering;
- contract insert and read with joined employee/company names;
- contract history ordering;
- contract update;
- renewal insert preserving the old row;
- overlap query for overlap, adjacency, and excluded own ID;
- inactive company remaining readable in historical joins;
- foreign-key protection;
- stored UTC timestamps and `DATE` values;
- multibyte names, apostrophes, and HTML-sensitive text through prepared
  statements.

Do not mock PDO when proving SQL, joins, indexes, or foreign keys. If the
configured test database is unavailable, follow the established integration
test skip/report behavior and say so clearly.

### 17.3 HTTP/feature tests

Using the existing `Request`, `HttpKernel`, router, and response-test style,
verify at minimum:

Dispatch companies:

- list and detail return 200 HTML;
- create form returns 200;
- valid create returns 303 to detail;
- invalid create returns 422 and preserves values/errors;
- edit form and valid update work;
- deactivate confirmation is GET-only and returns 200;
- POST deactivation returns 303 and changes lifecycle;
- repeated deactivation remains successful;
- missing and invalid IDs return 404;
- unsupported methods return 405.

Dispatch contracts:

- create form returns 200;
- valid create returns 303;
- permanent employee submission returns 422;
- inactive/missing company returns 422;
- invalid/impossible/reversed dates return 422;
- overlap returns 422;
- adjacent period succeeds;
- detail and edit form return 200;
- edit returns 303 and retains history semantics;
- renewal form returns 200;
- renewal returns 303 and leaves the source row unchanged;
- missing and invalid IDs return 404;
- unsupported methods return 405.

Regression coverage must confirm:

- root/setup remains usable without opening PDO;
- existing employee list/detail/create/edit/deactivation still work;
- employee detail shows dispatch data only for dispatched employees;
- permanent employee detail does not show misleading dispatch content;
- HTML-sensitive names, company names, addresses, and errors are escaped;
- all successful mutations use 303 and do not resubmit on refresh.

## 18. Manual browser verification

After automated tests:

1. Open the dispatch-company list and confirm deterministic rows/statuses.
2. Create a dispatch company and verify POST -> 303 -> detail.
3. Edit contact data and verify the detail page.
4. Open deactivation confirmation; confirm GET did not mutate state.
5. Deactivate the company with POST and verify it remains readable as
   inactive.
6. Create or identify a dispatched employee and open employee detail.
7. Create a valid contract and verify its detail and employee-history entry.
8. Try to create a contract for a permanent employee and confirm 422.
9. Try impossible, reversed, overlapping, and adjacent dates.
10. Renew a contract and verify two history records remain visible.
11. Edit an existing contract and verify it changes only that period.
12. Confirm expired and approaching end dates show the expected labels.
13. Confirm an inactive company remains visible on historical contract pages.
14. Verify HTML-sensitive input renders as text.
15. Refresh after every successful POST and confirm no form resubmission.
16. Re-run the existing employee workflows and root/setup route.

Manual checks complement, but do not replace, automated tests.

## 19. Expected implementation files

The following is the minimum expected shape, adjusted only where an existing
convention makes an equivalent location clearer:

```text
database/migrations/Version<approved>...Dispatch...

src/Application/DTO/DispatchCompanyInput.php
src/Application/DTO/DispatchContractInput.php
src/Application/Validation/DispatchCompanyInputValidator.php
src/Application/Validation/DispatchContractInputValidator.php
src/Application/DispatchCompany/DispatchCompanyService.php
src/Application/DispatchContract/DispatchContractService.php
src/Application/Support/<expiration-classification logic>

src/Domain/DispatchCompany/DispatchCompanyRepositoryInterface.php
src/Domain/DispatchContract/DispatchContractRepositoryInterface.php
src/Infrastructure/Persistence/PdoDispatchCompanyRepository.php
src/Infrastructure/Persistence/PdoDispatchContractRepository.php

src/Http/Controllers/DispatchCompanyController.php
src/Http/Controllers/DispatchContractController.php
resources/views/dispatch-companies/*
resources/views/dispatch-contracts/*

routes/web.php
src/Bootstrap/ApplicationBootstrap.php
tests/Unit/.../Dispatch...
tests/Integration/.../Dispatch...
tests/Feature/Http/Dispatch...
```

The implementation may extend `EmployeeService`/`EmployeeRepositoryInterface`
or add a narrow employee-detail read collaborator to supply dispatch history.
Do not add manager/handler/factory abstractions without an actual need.

## 20. Implementation order

Implement in this order:

1. Reconfirm the existing migrations, actual test schema, router behavior,
   lazy PDO provider, employee service, and test conventions.
2. Decide whether the existing database already has an approved equivalent
   dispatch schema; document the finding.
3. Add the forward migration only if required, with schema and migration
   tests.
4. Add read-shape definitions, DTOs, validators, and expiration logic.
5. Add repository interfaces and prepared PDO implementations.
6. Add repository unit/integration coverage for ordering, joins, overlap, and
   history.
7. Add dispatch-company and dispatch-contract services with fake-clock/fake-
   repository unit tests.
8. Integrate employee detail with one dispatch-history read path.
9. Add controllers, forms, detail pages, and escaping coverage.
10. Register static-before-dynamic routes and wire the composition root.
11. Run focused unit and feature tests.
12. Run real database integration tests using only the isolated test config.
13. Run the complete regression suite.
14. Complete the browser checklist.

Do not rewrite working Phase 01–05 architecture. Do not commit or merge
automatically; stop after implementation and testing for review.

## 21. Acceptance criteria

Phase 06 is complete only when:

1. The implementation follows the explicitly assigned branch.
2. The migration decision is documented and no duplicate dispatch tables are
   created.
3. Dispatch-company list, detail, create, edit, and deactivation workflows
   are available at the specified SSR routes.
4. Contract create, detail, edit, and explicit renewal workflows are
   available at the specified SSR routes.
5. Static routes are registered before dynamic ID routes.
6. Successful writes use POST -> 303 -> GET.
7. Invalid identifiers and missing resources use the existing 404 behavior.
8. Validation is centralized and preserves submitted values and errors.
9. Only allowlisted fields can be written from requests.
10. New dispatch companies default to active, and ordinary edit cannot
    change lifecycle status.
11. Only dispatched employees can receive, edit, or renew contracts.
12. New and renewed contracts use eligible active dispatch companies.
13. Historical contracts remain readable after company deactivation.
14. Dates use strict `Y-m-d` validation and enforce start <= end.
15. Overlapping periods for one employee are rejected; adjacent periods are
    accepted; edit excludes its own contract ID.
16. Renewal inserts a new row and preserves the previous contract unchanged.
17. Expiration classification is reusable, clock-driven, unit tested, and
    correct at expired/7-day/30-day boundaries.
18. Employee detail shows dispatch information only for dispatched employees
    and avoids N+1 query behavior.
19. SQL exists only in PDO repositories and uses prepared statements for
    variable values.
20. Views contain no SQL/repository/service calls and escape dynamic output.
21. Lazy PDO boot behavior and the root/setup route are preserved.
22. Unit, repository integration, and HTTP/feature tests cover success,
    validation failure, 404, 405, escaped output, redirects, history,
    overlap, renewal, and expiration behavior.
23. The complete Phase 01–06 regression suite passes, or unavailable external
    database prerequisites are reported explicitly.
24. No destructive integration test can target the normal application
    database.

## 22. Deferred work and implementation report

Later specifications may add authentication, authorization, CSRF, richer
employment lifecycle transitions, contract documents, notifications,
payroll, attendance, billing, dashboards, search, filtering, pagination, or
multi-company tenancy. Such work must preserve the Phase 06 boundaries:
controllers remain thin, services own use-case coordination, repositories own
SQL, views remain presentation-only, and database constraints remain the final
integrity boundary.

After implementation, report:

1. files added;
2. files modified;
3. whether a migration was added and why;
4. routes added;
5. business rules implemented;
6. tests added;
7. focused-test results;
8. real-database integration-test results;
9. full-regression result;
10. remaining limitations or unavailable infrastructure.

## 23. Implementation record

The repository migrations were inspected before implementation. They contained
only `companies`, `branches`, `departments`, and `employees`, so the approved
Phase 06 baseline is implemented by the forward migration
`Version20260922000500CreateDispatchCompaniesAndContracts.php`. It creates
`dispatch_companies` before `dispatch_contracts` and reverses that order in
`down()`.

The implementation uses `DispatchCompanyService` and
`DispatchContractService`, focused dispatch repository interfaces and PDO
repositories, explicit company/contract DTOs and validators, and
`ContractExpirationClassifier` driven by the existing `Clock`. Dispatch
company and contract pages use the existing localized SSR shell. Contract
renewal inserts a new row; edit updates the existing period; overlap checks
exclude the edited row only; and employee detail receives one ordered contract
history read for dispatched employees.
