# Phase 07: Organization Management Specification

## 1. Purpose

This document specifies Phase 07 of the Company Employee Management System:
server-rendered management of internal branches and departments. It is an
implementation specification only. It does not create controllers, services,
repositories, DTOs, validators, views, routes, migrations, or database data.

Phase 07 builds on the existing Pure PHP, SSR, PDO, custom-router, migration,
employee-management, and dispatch-management boundaries. It must extend those
boundaries rather than introduce a framework, ORM, SPA, REST API, or parallel
application architecture.

The implementation branch named by the prompt is `feature/organization-management`.
Implementation must use that assigned branch and must not invent a different
branch name in this specification.

## 2. Phase objective

Make the existing organization master data manageable through the application
UI while preserving employee relationships and historical read behavior:

- branch list, detail, create, edit, and deactivation;
- department list, detail, create, edit, and deactivation;
- active/inactive lifecycle visibility without physical deletion;
- branch and department validation based on the existing schema;
- safe parent-child relationship checks;
- continued compatibility with EmployeeService's active organization choices;
- English and Japanese SSR UI integrated into the current navigation.

The primary request flow remains:

```text
HTTP request
  -> controller
  -> application service
  -> repository interface
  -> PDO repository
  -> MySQL/MariaDB
  -> prepared view / redirect
```

## 3. Current project state

### 3.1 Inspected project areas

The specification was based on the current repository, including:

- `docs/specs/00-project-overview.md` through
  `docs/specs/06-dispatch-contract-management.md`;
- the Phase 05 employee implementation and tests;
- the Phase 06 dispatch-company and dispatch-contract implementation and
  tests;
- `BranchReadRepositoryInterface` and `DepartmentReadRepositoryInterface`;
- `PdoBranchReadRepository` and `PdoDepartmentReadRepository`;
- `EmployeeRepositoryInterface`, `PdoEmployeeRepository`, `EmployeeService`,
  `EmployeeInput`, and `EmployeeInputValidator`;
- `DispatchCompanyRepositoryInterface`, `PdoDispatchCompanyRepository`,
  `DispatchCompanyService`, `DispatchCompanyInput`, and
  `DispatchCompanyInputValidator`;
- the existing controllers, routes, bootstrap/composition root, layouts,
  partials, views, translations, and tests;
- the migrations for companies, branches, departments, employees, dispatch
  companies, and dispatch contracts;
- the development seeder and its existing sample organization data.

### 3.2 Existing architecture to preserve

The application is Pure PHP with PHP 8.x, Composer PSR-4 autoloading, PDO,
MySQL/MariaDB, a custom GET/POST router, SSR PHP views, and English/Japanese
translation files. The existing dependency direction is:

```text
Browser
  -> public/index.php
  -> ApplicationBootstrap
  -> middleware
  -> Router
  -> Controller
  -> Application Service
  -> Domain Repository Interface
  -> PDO Repository
  -> Database
  -> ViewRenderer / redirect
```

Controllers parse HTTP input and select responses. Services coordinate use
cases and business rules. Repositories own SQL. Views receive prepared arrays
and scalars only. All variable SQL values use PDO prepared statements.

## 4. Scope and non-goals

### 4.1 Included

- Branch management pages and lifecycle behavior.
- Department management pages and lifecycle behavior.
- Explicit branch and department input DTOs.
- Centralized structural validators and validation results.
- Focused management repository contracts and PDO implementations.
- Company and branch lookup data required by forms.
- Related department and employee counts where they can be loaded without
  N+1 queries.
- Existing employee-form compatibility for inactive current assignments.
- English/Japanese labels, errors, notices, and navigation.
- Unit, repository-integration, and HTTP/feature tests.
- Manual browser verification for both management areas and employee
  regression behavior.

### 4.2 Explicitly excluded

Phase 07 does not add or imply:

- company CRUD or company lifecycle management;
- authentication, login, sessions, authorization, or user management;
- CSRF infrastructure when it is not already present;
- physical deletion of branches, departments, employees, or companies;
- branch or department reactivation UI;
- branch reparenting between companies;
- department reparenting between branches;
- automatic status changes to departments or employees when a parent is
  deactivated;
- employee search, filtering, sorting controls, or pagination;
- portfolios, skills, projects, certifications, payroll, attendance, or
  billing;
- a REST/JSON API, SPA, ORM, generic CRUD framework, or query builder;
- schema redesign unrelated to the existing organization tables.

## 5. Domain and data relationships

The existing relationship remains:

```text
Company
  -> Branch
      -> Department
          -> Employee
```

### 5.1 Existing tables consumed by Phase 07

Phase 07 consumes the existing schema and does not require a migration.

#### `companies`

Relevant fields:

| Field | Type | Phase 07 behavior |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` primary key | Parent identifier |
| `code` | `VARCHAR(30)` unique, required | Display/read-only parent code |
| `name` | `VARCHAR(160)` required | Selection and detail label |
| `created_at`, `updated_at` | `DATETIME` required | Existing persistence metadata |

The current table has no status column. Phase 07 therefore treats an existing
company as selectable and does not invent company active/inactive behavior.

#### `branches`

| Field | Type | Rule |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` primary key | Internal identifier |
| `company_id` | `BIGINT UNSIGNED` not null | Existing company parent |
| `code` | `VARCHAR(30)` not null | Unique within `company_id` |
| `name` | `VARCHAR(160)` not null | Branch display name |
| `city` | `VARCHAR(120)` not null | Branch city/area |
| `address` | `VARCHAR(500)` not null | Branch address |
| `phone` | `VARCHAR(32)` not null | Branch phone |
| `status` | `VARCHAR(20)` not null | `active` or `inactive` |
| `created_at`, `updated_at` | `DATETIME` not null | UTC persistence timestamps |

The database unique key is `(company_id, code)`. The company foreign key uses
restrict behavior.

#### `departments`

| Field | Type | Rule |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` primary key | Internal identifier |
| `branch_id` | `BIGINT UNSIGNED` not null | Existing branch parent |
| `code` | `VARCHAR(30)` not null | Unique within `branch_id` |
| `name` | `VARCHAR(120)` not null | Department display name |
| `description` | `TEXT` nullable | Optional existing master-data note |
| `status` | `VARCHAR(20)` not null | `active` or `inactive` |
| `created_at`, `updated_at` | `DATETIME` not null | UTC persistence timestamps |

The database unique key is `(branch_id, code)`. The branch foreign key uses
restrict behavior, and employees use the department/branch relationship as a
composite foreign-key boundary.

### 5.2 Department description decision

`departments.description` exists in the schema even though the current
Phase 05 read contract does not expose it. Phase 07 should support it as an
optional department form/detail field so management does not silently discard
an existing schema field. It is plain text, not rich HTML, and has no invented
application maximum beyond the database `TEXT` type. Empty input is stored as
`NULL`.

## 6. Lifecycle and deactivation decisions

### 6.1 General lifecycle rules

1. New branches and departments are created with `active` status.
2. Status is not an ordinary form field.
3. Deactivation is a conditional status update, not a `DELETE`.
4. Deactivation is idempotent: an already inactive row remains unchanged and
   the user receives an allowlisted informational notice.
5. Phase 07 does not provide reactivation. A later lifecycle specification
   must define authorization, validation, and audit behavior before adding it.
6. No status change cascades automatically to children or employees.
7. Existing relationships remain readable after deactivation.

This preserves the distinction between historical/read behavior, selection
behavior, and create/update behavior.

### 6.2 Branch deactivation

Deactivating a branch is allowed even when the branch has departments or
employees. The confirmation page must show a warning that child records and
employee history are retained. The operation changes only the branch's
status and `updated_at`.

Departments under the branch remain at their current statuses. Employees
remain at their current statuses and continue to reference the branch. No
department or employee is silently changed.

After deactivation:

- the inactive branch remains in management lists and its detail page;
- employee detail pages continue to show the branch and its inactive status;
- the branch is excluded from new employee branch choices;
- an employee edit may preserve the employee's existing inactive branch as a
  marked current value, following the Phase 05 behavior, but may not assign a
  different employee to an inactive branch;
- new departments cannot be created under the inactive branch;
- existing departments under the branch may be viewed and edited for
  metadata correction, but cannot be reparented to or from another branch;
- the branch may not be reactivated in Phase 07.

### 6.3 Department deactivation

Deactivating a department is allowed even when employees still reference it.
The operation changes only the department's status and `updated_at`.

After deactivation:

- the inactive department remains in department management lists and its
  detail page;
- employee detail pages continue to show the department and its inactive
  status;
- the department is excluded from new employee department choices;
- an employee edit may preserve the employee's existing inactive department
  as a marked current value, following Phase 05, but may not assign a
  different employee to it;
- existing employees are not automatically deactivated or moved;
- the department may be edited for metadata correction, but may not be
  reactivated in Phase 07;
- a new department cannot be created under an inactive branch, regardless of
  the new department's requested status.

### 6.4 Parent assignment and reparenting

Branch `company_id` and department `branch_id` are required on create. On
edit, the parent is displayed as read-only and is not changed. Reparenting is
deferred because it changes the meaning of historical organization
relationships and can change the scope in which a code is unique.

The service must still validate the parent identifier on create and confirm
the parent exists. The database foreign key remains the final boundary.

## 7. Branch management

### 7.1 Branch list

`GET /branches` displays active and inactive branches as organization records.
Each row contains:

- company name and code;
- branch code and name;
- city;
- status;
- department count;
- employee count;
- links to detail and edit;
- a deactivation action for active rows.

Rows use deterministic ordering: company name, branch name, branch code, and
branch id as the final tie-breaker. The service owns a fixed safety limit if
the implementation needs one; Phase 07 does not expose pagination.

Counts should be loaded in the management list query using grouped or
correlated aggregate queries, not one query per branch. If a count cannot be
provided without an unnecessary query abstraction, it may be omitted, but the
implementation must document that choice.

### 7.2 Branch detail

`GET /branches/{id}` displays:

- company name and code;
- branch code, name, city, address, phone, and status;
- created and updated timestamps in the configured display timezone;
- department count and employee count;
- a list of related departments with code, name, status, and employee count;
- edit and deactivation controls when appropriate.

Inactive children remain visible. A missing or invalid id uses the existing
404 behavior and exposes no SQL or PDO details.

### 7.3 Branch create

`GET /branches/create` renders a blank form. The company selector lists
existing companies in deterministic code/name order. There is no company CRUD
in this phase, so a user cannot create a company from this form.

`POST /branches` accepts only the documented branch input fields. On success,
the controller responds with `303 See Other` to `/branches/{id}`. New rows
are active regardless of any submitted `status` field.

### 7.4 Branch edit

`GET /branches/{id}/edit` renders the current branch values. The company is
shown as read-only. Editable values are code, name, city, address, and phone.
An inactive branch may be edited for metadata correction; editing does not
reactivate it.

`POST /branches/{id}` preserves the row id, status, created timestamp, and
company parent. It updates only the approved metadata and `updated_at`. A
successful update redirects with 303 to the branch detail page. Validation
failures render the submitted values and field errors with HTTP 422.

### 7.5 Branch deactivation

`GET /branches/{id}/deactivate` renders a confirmation page. It has no side
effect. The page identifies the branch, parent company, status, and related
department/employee counts and explains that child and employee history will
be retained.

`POST /branches/{id}/deactivate` performs the conditional status update and
redirects with 303 to the detail page. Missing ids return 404. Repeated
deactivation is a safe no-op with an allowlisted notice.

## 8. Department management

### 8.1 Department list

`GET /departments` displays active and inactive departments. Each row contains:

- parent company and branch;
- department code and name;
- optional description preview where useful;
- status;
- employee count;
- links to detail and edit;
- a deactivation action for active rows.

Rows use deterministic ordering: company name, branch name, department name,
department code, and department id. Counts must not introduce an N+1 query
pattern.

### 8.2 Department detail

`GET /departments/{id}` displays:

- parent company and branch, including parent status;
- department code, name, optional description, and status;
- created and updated timestamps in the configured display timezone;
- employee count and a basic list of employees currently related to it;
- edit and deactivation controls when appropriate.

Employees remain visible when the department is inactive. A missing or
invalid id uses the existing 404 behavior.

### 8.3 Department create

`GET /departments/create` renders a blank form. The branch selector lists
active branches only and includes the company context. An inactive branch
cannot be selected.

`POST /departments` accepts the explicit department input. The service
requires an existing active branch, creates the row with active status, and
redirects with 303 to `/departments/{id}` on success. A submitted `status`
field is ignored or rejected as an unexpected field; it cannot set the row
inactive during creation.

### 8.4 Department edit

`GET /departments/{id}/edit` renders the current department values. The parent
branch is read-only. An inactive department may be edited for metadata
correction. An inactive parent branch does not prevent viewing or editing an
existing department because the operation does not create a new relationship;
the parent status is clearly shown.

`POST /departments/{id}` preserves id, branch_id, status, and created_at. It
updates code, name, description, and `updated_at`. It cannot move the
department to another branch or reactivate it.

### 8.5 Department deactivation

`GET /departments/{id}/deactivate` renders a confirmation page showing the
parent branch, department, status, and employee count. The GET has no side
effect.

`POST /departments/{id}/deactivate` sets status to inactive and redirects with
303 to detail. It does not update employee status or department_id. Repeated
deactivation is a safe no-op with an allowlisted notice.

## 9. HTTP routes and semantics

Register static and action routes before dynamic `/{id}` routes, following the
current router behavior:

### 9.1 Branch routes

| Method | Path | Responsibility | Success |
| --- | --- | --- | --- |
| GET | `/branches` | Management list | 200 HTML |
| GET | `/branches/create` | Create form | 200 HTML |
| POST | `/branches` | Create branch | 303 to detail |
| GET | `/branches/{id}/deactivate` | Confirmation | 200 HTML |
| POST | `/branches/{id}/deactivate` | Deactivate | 303 to detail |
| GET | `/branches/{id}/edit` | Edit form | 200 HTML |
| POST | `/branches/{id}` | Update branch | 303 to detail |
| GET | `/branches/{id}` | Detail | 200 HTML |

### 9.2 Department routes

| Method | Path | Responsibility | Success |
| --- | --- | --- | --- |
| GET | `/departments` | Management list | 200 HTML |
| GET | `/departments/create` | Create form | 200 HTML |
| POST | `/departments` | Create department | 303 to detail |
| GET | `/departments/{id}/deactivate` | Confirmation | 200 HTML |
| POST | `/departments/{id}/deactivate` | Deactivate | 303 to detail |
| GET | `/departments/{id}/edit` | Edit form | 200 HTML |
| POST | `/departments/{id}` | Update department | 303 to detail |
| GET | `/departments/{id}` | Detail | 200 HTML |

Invalid, non-positive, or non-integer route ids return the existing 404
response. Missing records also return 404. Unsupported methods return the
existing 405 response. Every successful write uses POST -> 303 -> GET.

## 10. Request and validation specification

### 10.1 Branch input

The branch DTO contains:

- `companyId: int`;
- `code: string`;
- `name: string`;
- `city: string`;
- `address: string`;
- `phone: string`.

`status`, `id`, `created_at`, and `updated_at` are not accepted as ordinary
form input.

| Field | Structural validation | Business validation |
| --- | --- | --- |
| `company_id` | Required positive decimal integer | Company must exist |
| `code` | Required scalar string, trimmed, 1–30 characters | Unique within company; update excludes current branch |
| `name` | Required scalar string, trimmed, 1–160 characters | None beyond required value |
| `city` | Required scalar string, trimmed, 1–120 characters | None beyond required value |
| `address` | Required scalar string, trimmed, 1–500 characters | None beyond required value |
| `phone` | Required scalar string, trimmed, 1–32 characters | None beyond required value |

On update, `company_id` must match the current parent. A request attempting
to change it is rejected as a business validation error rather than silently
moving the branch.

### 10.2 Department input

The department DTO contains:

- `branchId: int`;
- `code: string`;
- `name: string`;
- `description: ?string`.

| Field | Structural validation | Business validation |
| --- | --- | --- |
| `branch_id` | Required positive decimal integer | Branch must exist and be active on create |
| `code` | Required scalar string, trimmed, 1–30 characters | Unique within branch; update excludes current department |
| `name` | Required scalar string, trimmed, 1–120 characters | None beyond required value |
| `description` | Optional scalar text; blank becomes `NULL` | None; preserve plain text only |

On update, `branch_id` must match the current parent. An inactive parent is
allowed for metadata-only updates to an existing department, but not for
creating a new department or moving a department.

### 10.3 Common validation rules

- Reject arrays and objects for scalar fields.
- Trim strings before validation and redisplay normalized submitted values.
- Use multibyte-safe length checks for bounded text fields.
- Do not invent stricter phone or code character regular expressions than the
  existing schema requires.
- Do not accept status, timestamps, or ids as mass-assignment fields.
- Convert valid numeric ids to positive integers.
- Translate duplicate-key races into field-level errors for `code`.
- Never expose raw PDO, SQL, DSN, username, or password details.

Database foreign keys, unique keys, and status checks remain the final
integrity boundary after service validation.

## 11. Repository design

### 11.1 Preserve existing read contracts

The current `BranchReadRepositoryInterface` and
`DepartmentReadRepositoryInterface` provide:

```php
listActive(): array
findById(int $id): ?array
```

These contracts are already used by EmployeeService and must remain narrow and
compatible. Employee forms must continue to receive active choices, while
`findById()` must continue to support display of an inactive current
relationship.

Phase 07 should not turn either read interface into a generic write contract.
Use separate management interfaces, optionally implemented by the same
concrete PDO class where that avoids duplicate SQL:

```text
BranchReadRepositoryInterface
  -> existing EmployeeService dependency

BranchRepositoryInterface
  -> branch management service dependency

DepartmentReadRepositoryInterface
  -> existing EmployeeService dependency

DepartmentRepositoryInterface
  -> department management service dependency
```

This keeps Phase 05 dependencies stable while giving Phase 07 explicit write
operations. Do not add a BaseRepository or generic CRUD interface.

### 11.2 Branch management repository operations

The branch management contract should provide explicit operations equivalent
to:

- `listManagement(int limit): array` with company and aggregate counts;
- `findById(int id): ?array` with company context and counts/children as
  appropriate;
- `listCompanies(): array` or a small dedicated company-read contract for
  create-form choices;
- `codeExists(int companyId, string code, ?int $exceptId = null): bool`;
- `insert(BranchInput $input, string $createdAt, string $updatedAt): int`;
- `update(int $id, BranchInput $input, string $updatedAt): void`;
- `deactivate(int $id, string $updatedAt): bool`.

The implementation must verify the current company parent during update and
must not silently change it.

### 11.3 Department management repository operations

The department management contract should provide explicit operations
equivalent to:

- `listManagement(int $limit): array` with branch/company context and employee
  counts;
- `findById(int $id): ?array` with branch/company context and employees as
  appropriate;
- `codeExists(int $branchId, string $code, ?int $exceptId = null): bool`;
- `insert(DepartmentInput $input, string $createdAt, string $updatedAt): int`;
- `update(int $id, DepartmentInput $input, string $updatedAt): void`;
- `deactivate(int $id, string $updatedAt): bool`.

The implementation must verify the current branch parent during update and
must use a prepared query for every request value.

### 11.4 PDO behavior

PDO repositories own SQL and joins, select explicit columns, use deterministic
ordering, and map duplicate constraint failures to small known conflict
exceptions where that matches the existing Employee and DispatchCompany
patterns. Unknown persistence failures must be rethrown to the centralized
HTTP error boundary.

The repository must not render HTML, parse requests, decide HTTP status codes,
or perform status cascades.

## 12. Application layer design

### 12.1 DTOs and validators

Add small explicit value objects equivalent to:

- `BranchInput`;
- `DepartmentInput`.

Add focused validators and validation results equivalent to:

- `BranchInputValidator` / `BranchValidationResult`;
- `DepartmentInputValidator` / `DepartmentValidationResult`.

Validators perform structural normalization only. Services perform parent
existence/status, parent immutability, duplicate, and lifecycle checks.

### 12.2 BranchService

BranchService should expose use-case-oriented operations equivalent to:

- `listBranches()`;
- `getBranch(int $id)`;
- `createForm()`;
- `createBranch(array $rawInput)`;
- `editForm(int $id)`;
- `updateBranch(int $id, array $rawInput)`;
- `deactivationForm(int $id)`;
- `deactivateBranch(int $id)`.

It is responsible for:

- calling the validator;
- loading and validating company existence;
- enforcing active-company/parent rules as applicable to the schema;
- enforcing company immutability on update;
- checking scoped code uniqueness;
- setting new status to active;
- generating UTC timestamps through the existing `Clock`;
- translating known duplicate conflicts into field errors;
- returning prepared list/detail/form data.

It must not execute SQL, render HTML, read superglobals, or know redirect
URLs/status codes.

### 12.3 DepartmentService

DepartmentService should expose use-case-oriented operations equivalent to:

- `listDepartments()`;
- `getDepartment(int $id)`;
- `createForm()`;
- `createDepartment(array $rawInput)`;
- `editForm(int $id)`;
- `updateDepartment(int $id, array $rawInput)`;
- `deactivationForm(int $id)`;
- `deactivateDepartment(int $id)`.

It is responsible for:

- calling the validator;
- validating branch existence and active status on create;
- preserving branch immutability on update;
- checking scoped code uniqueness;
- setting new status to active;
- generating UTC timestamps through `Clock`;
- translating known duplicate conflicts into field errors;
- returning prepared list/detail/form data.

An inactive parent is allowed only for reading and metadata updates to an
existing department. It is not a valid parent for new department creation.

## 13. Controllers and views

### 13.1 Controllers

Add thin `BranchController` and `DepartmentController` actions matching the
routes. Each action should:

1. validate the route id at the HTTP boundary;
2. pass body parameters to the corresponding service;
3. render a view or issue a 303 redirect;
4. map missing resources to the existing `NotFoundException`;
5. render validation failures with HTTP 422 and submitted values.

Controllers must not instantiate PDO, build SQL, perform parent checks, or
change status directly.

### 13.2 Branch views

Add SSR views under `resources/views/branches/` equivalent to:

- `index.php`;
- `show.php`;
- `create.php`;
- `edit.php`;
- `deactivate.php`;
- `_form.php`.

The list uses a shared page header, status chip, deterministic table, empty
state, and safe action links. The detail page shows company context, branch
fields, counts, related departments, and lifecycle actions. The form shows
company as selectable on create and read-only on edit.

### 13.3 Department views

Add SSR views under `resources/views/departments/` equivalent to:

- `index.php`;
- `show.php`;
- `create.php`;
- `edit.php`;
- `deactivate.php`;
- `_form.php`.

The form uses an active branch selector on create, displays the parent as
read-only on edit, and includes optional plain-text description. Detail and
list pages retain inactive parent/status context.

Every dynamic value, including errors, descriptions, names, codes, notices,
ids in URLs, and selected values, is escaped with the existing
`HtmlEscaper::escape` convention. Views contain no SQL, repositories, services,
PDO, request globals, or hidden lifecycle decisions.

## 14. Localization

Use the existing `Translator` and `resources/lang/en.php` / `ja.php` files.
Do not add new user-facing strings directly in controllers or views.

The conceptual translation areas include:

```text
navigation.branches
navigation.departments
branches.title
branches.create_title
branches.edit_title
branches.detail_title
branches.deactivate_title
branches.directory
branches.description
branches.related_departments
branches.department_count
branches.employee_count
branches.deactivate_confirm
branches.deactivate_retained
branches.already_inactive
branches.deactivated_success
departments.title
departments.create_title
departments.edit_title
departments.detail_title
departments.deactivate_title
departments.directory
departments.description
departments.parent_branch
departments.employee_count
departments.deactivate_confirm
departments.deactivate_retained
departments.already_inactive
departments.deactivated_success
form.branch_code
form.department_code
form.city
form.description
validation.existing_company
validation.existing_branch
validation.active_branch
validation.branch_parent_immutable
validation.duplicate_branch_code
validation.duplicate_department_code
```

Terminology must remain consistent: Branch / 支店, Department / 部署,
Branch Code / 支店コード, and Department Code / 部署コード.

## 15. Navigation

Update only the existing sidebar entries that currently display as unavailable:

- Branches becomes an active link to `/branches`.
- Departments becomes an active link to `/departments`.

The active navigation key must be `branches` or `departments` on the
corresponding pages. Existing links for Employees, Dispatch Companies,
Dispatch Contracts, Dashboard, and locale switching remain unchanged. No
sidebar redesign is part of Phase 07.

## 16. Security and data integrity

The implementation must:

- use prepared PDO statements for all variable SQL values;
- allowlist request fields and reject mass assignment of status, ids, and
  timestamps;
- validate positive route identifiers and return 404 for invalid values;
- escape all SSR output using the existing helper;
- preserve the current POST-only mutation pattern;
- keep foreign-key and unique constraints enabled;
- avoid physical deletion and status cascades;
- preserve inactive historical relationships;
- keep raw SQL/DSN/PDO errors out of user-facing responses;
- use existing CSRF conventions if they are added before implementation;
- leave route/controller boundaries ready for future authentication,
  authorization, and CSRF middleware.

Authentication, authorization, sessions, and CSRF are not currently present
in the inspected application. Phase 07 must not invent a competing security
subsystem, and it must not describe these routes as production-secure until a
later security phase supplies those controls.

## 17. Error handling

| Situation | Behavior |
| --- | --- |
| Invalid/non-positive route id | Existing 404 response |
| Missing branch/department | Existing 404 response |
| Missing company or parent branch on submitted form | HTTP 422 field error |
| Inactive branch selected for new department | HTTP 422 on `branch_id` |
| Parent change attempted on update | HTTP 422 parent field error |
| Missing required field or over-length value | HTTP 422 with submitted values |
| Duplicate scoped code | HTTP 422 on `code` |
| Duplicate race from database | HTTP 422 on `code` when recognized |
| Already inactive deactivation | 303 detail redirect with safe notice |
| Unsupported method | Existing 405 response |
| Unexpected PDO/database failure | Existing centralized 500 behavior |

No raw database error should be displayed. A failed write must not partially
change status or metadata. Each current use case is a single write after
validation; if a later use case adds multiple writes, it must introduce an
explicit transaction boundary.

## 18. Test strategy

### 18.1 Unit/application tests

Add focused validator and service tests covering:

- required fields, scalar rejection, trimming, and exact schema lengths;
- valid and invalid company/branch identifiers;
- optional department description becoming `NULL` when blank;
- scoped duplicate branch and department code checks;
- update checks excluding the current id;
- missing parent and inactive parent behavior;
- parent immutability on update;
- new rows defaulting to active;
- status input not changing lifecycle state;
- branch deactivation with existing children and employees;
- department deactivation with existing employees;
- no cascade to child or employee statuses;
- repeated deactivation as an idempotent no-op;
- UTC timestamps through a fixed clock;
- validation values preserved after failure.

Use repository test doubles for service tests. Do not use PDO mocks to claim
database constraint coverage.

### 18.2 Database integration tests

Run only against the isolated database configured by `APP_ENV=test` and
`DB_TEST_*`. Never use `company_employee_management` or development
credentials.

Apply the existing migrations and create deterministic fixtures. Cover:

- branch insert/read/update/deactivation;
- department insert/read/update/deactivation;
- company and branch foreign keys;
- composite employee department/branch relationship;
- duplicate branch code within one company;
- same branch code allowed under a different company;
- duplicate department code within one branch;
- same department code allowed under a different branch;
- invalid parent foreign keys;
- status check constraints where supported;
- prepared statements with multibyte and HTML-sensitive text;
- counts and deterministic management-list ordering;
- preservation of employees and unrelated records after deactivation.

If MySQL is unavailable, follow the existing skip convention and report that
the integration dependency was unavailable. Do not substitute the development
database.

### 18.3 HTTP/feature tests

Using the existing Request, Router, HttpKernel, and response-testing style,
verify:

Branch:

- list, create, detail, edit, and deactivation pages return 200;
- valid create/update/deactivate operations return 303;
- invalid ids and missing rows return 404;
- validation failures return 422 and preserve submitted values;
- inactive branch detail remains readable;
- branch action routes do not mutate on GET;
- escaped branch/company values render as text.

Department:

- list, create, detail, edit, and deactivation pages return 200;
- valid create/update/deactivate operations return 303;
- missing/inactive branch selection is rejected with 422;
- inactive parent detail/edit behavior is preserved;
- invalid ids and missing rows return 404;
- validation failures return 422 and preserve submitted values;
- escaped department/description values render as text.

Regression:

- EmployeeService still lists active branch/department choices;
- an employee with an inactive current branch or department remains readable;
- employee forms preserve current inactive relationships without offering
  inactive rows as new choices;
- existing Employees, Dispatch Companies, Dispatch Contracts, locale, and
  setup routes continue to work.

## 19. Existing development seeder impact

The current seeder already creates the Phase 07 sample organization:

```text
SAMPLE-COMPANY / サンプル株式会社
TOKYO / 東京支店
OSAKA / 大阪支店
TOKYO / DEV / 開発部
TOKYO / SALES / 営業部
OSAKA / DEV / 開発部
```

Phase 07 requires no seeder change. The implementation must not modify
`php bin/seed`, add sample data to migrations, or run the seeder as part of
automated tests. Existing seeded records should appear in branch and
department management pages, and the seeder's stable identifiers must remain
compatible with the new repository operations.

## 20. Expected implementation shape

The following is the minimum expected shape, adjusted only if an existing
convention makes an equivalent location clearer:

```text
src/Application/DTO/BranchInput.php
src/Application/DTO/DepartmentInput.php
src/Application/Validation/BranchInputValidator.php
src/Application/Validation/BranchValidationResult.php
src/Application/Validation/DepartmentInputValidator.php
src/Application/Validation/DepartmentValidationResult.php
src/Application/Organization/BranchService.php
src/Application/Organization/DepartmentService.php
src/Domain/Organization/BranchRepositoryInterface.php
src/Domain/Organization/DepartmentRepositoryInterface.php
src/Domain/Organization/CompanyReadRepositoryInterface.php
src/Infrastructure/Persistence/PdoBranchRepository.php
src/Infrastructure/Persistence/PdoDepartmentRepository.php
src/Infrastructure/Persistence/PdoCompanyReadRepository.php
src/Http/Controllers/BranchController.php
src/Http/Controllers/DepartmentController.php
resources/views/branches/index.php
resources/views/branches/show.php
resources/views/branches/create.php
resources/views/branches/edit.php
resources/views/branches/deactivate.php
resources/views/branches/_form.php
resources/views/departments/index.php
resources/views/departments/show.php
resources/views/departments/create.php
resources/views/departments/edit.php
resources/views/departments/deactivate.php
resources/views/departments/_form.php
```

Also update only the required bootstrap wiring, route registration, sidebar
availability, translations, and focused tests. Do not add generic managers,
handlers, factories, abstract repositories, or a full domain entity model
without documenting a concrete need.

## 21. Implementation order

1. Confirm the existing schema and isolated test-database safeguards.
2. Preserve the two existing active-read contracts used by EmployeeService.
3. Add explicit management repository contracts/read shapes and company-choice
   lookup behavior.
4. Add DTOs, validators, and validation result behavior.
5. Add repository integration tests and prepared PDO implementations.
6. Add BranchService and DepartmentService with unit tests.
7. Wire lazy PDO dependencies through ApplicationBootstrap.
8. Add thin controllers and route registration in static-before-dynamic order.
9. Add SSR views, navigation links, translations, and escaping coverage.
10. Run full normal and explicitly isolated test suites.
11. Complete browser checks for both management areas and employee regressions.

No migration is expected. If implementation discovers a genuine schema defect,
stop and document it as a separately reviewed change rather than modifying an
accepted migration silently.

## 22. Acceptance criteria

Phase 07 is complete only when:

1. Branch and department list, detail, create, edit, and deactivation routes
   are available with the specified SSR behavior.
2. Static and action routes are registered before dynamic `/{id}` routes.
3. Successful writes use POST -> 303 -> GET.
4. Invalid ids and missing records use the existing 404 behavior.
5. Validation is centralized, schema-derived, and preserves submitted values.
6. Scoped branch and department uniqueness is checked in the service and
   protected by existing database constraints.
7. New branches/departments are active and status cannot be mass-assigned.
8. Branch company and department branch parents cannot be changed in an edit.
9. Inactive branches/departments remain readable and editable for metadata
   correction without reactivation.
10. New departments cannot be created under inactive branches.
11. Deactivation never physically deletes or silently cascades to children or
    employees.
12. Existing employees retain and display inactive organization relationships.
13. Employee new-assignment choices remain active-only and Phase 05 behavior
    is not broken.
14. SQL remains in prepared PDO repositories; views contain no database access.
15. English and Japanese navigation, labels, errors, and notices are provided.
16. Branch and department pages use the existing Material Design-inspired
    layout, shared partials, status chips, and escaping helper.
17. Unit, integration, and HTTP tests cover success, validation, duplicate,
    lifecycle, 404, escaped output, and regression behavior.
18. Integration tests use only the isolated `_test` database.
19. Existing seed data appears correctly without seeder or migration changes.
20. No Phase 07 implementation introduces authentication, authorization,
    CSRF, company CRUD, physical deletion, or unrelated schema redesign.

## 23. Design decisions and remaining ambiguities

The following conservative decisions are the source of truth for
implementation:

- Company management is not part of Phase 07; existing companies are parent
  choices only.
- Parent reassignment is deferred to avoid changing historical relationship
  meaning and scoped-code behavior.
- Reactivation is deferred; inactive records remain readable and metadata
  editable but cannot be made active in this phase.
- Deactivation does not cascade to departments or employees.
- Existing inactive parent relationships are preserved on employee pages and
  forms, while new assignments remain active-only.
- Department `description` is included because it exists in the schema; it is
  optional plain text and is not interpreted as markup.
- Counts are expected on management lists/details only when they can be
  obtained by explicit aggregate SQL without N+1 queries.

Future specifications may revisit reactivation, parent reassignment, audit
history, authorization, CSRF, and company lifecycle management. They must not
silently change the Phase 07 historical-preservation rules.
