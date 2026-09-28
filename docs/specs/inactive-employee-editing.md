# Inactive Employee Editing Specification

## 1. Purpose

This specification defines the lifecycle rules for active and inactive
employees. Inactive employees remain available as historical business records,
but they must not be edited, updated, or deactivated again.

This is a specification-only change. It does not implement production code,
modify tests, add a migration, or change the database schema.

## 2. Current behavior and inspected architecture

The inspection covered the Phase 05 employee specification and the current
employee implementation, including:

- `src/Http/Controllers/EmployeeController.php`;
- `src/Application/Employee/EmployeeService.php`;
- `src/Domain/Employee/EmployeeRepositoryInterface.php`;
- `src/Infrastructure/Persistence/PdoEmployeeRepository.php`;
- employee views, routes, translation resources, and the composition root;
- employee unit, HTTP/feature, and repository integration tests;
- the existing employee status migration and employee-code allocator.

The current gaps are:

1. `resources/views/employees/index.php` always renders an Edit link. It only
   hides Deactivate for inactive rows.
2. `resources/views/employees/show.php` always renders an Edit page action.
3. `EmployeeService::editForm()` loads an inactive employee into the edit
   form.
4. `EmployeeService::updateEmployee()` checks that the employee exists but does
   not check that its status is active.
5. `PdoEmployeeRepository::update()` uses `WHERE id = :id` without a status
   predicate, so a stale form can update an employee after deactivation.
6. `PdoEmployeeRepository::deactivate()` already uses
   `WHERE id = :id AND status = 'active'` and returns whether a row changed,
   but `EmployeeService::deactivateEmployee()` currently ignores that boolean
   after its initial read.

The existing HTTP conventions are custom GET/POST routes, thin controllers,
service-owned use-case rules, PDO repositories, SSR views, 303 redirects for
successful commands, `NotFoundException` for missing resources, and
allowlisted query-string notices rendered through `Translator`. There is no
existing forbidden-response or inactive-employee exception boundary, so this
specification does not introduce one.

The existing employees table already has `status VARCHAR(20)` with a database
check constraint for `active` and `inactive`. It is the authoritative status
field.

## 3. Final status rules

| Employee status | Detail/view | Edit form | Update | Deactivate |
| --- | --- | --- | --- | --- |
| `active` | Allowed | Allowed | Allowed | Allowed; changes to `inactive` |
| `inactive` | Allowed | Rejected | Rejected | No state change; idempotent no-op |

Inactive does not mean deleted. The employee row, employee code, organization
relationships, employment data, dispatch history, and timestamps remain
readable. Reactivation is not part of this task.

## 4. List UI behavior

The employee list continues to include both active and inactive employees.

For an active row, render:

- Detail;
- Edit;
- Deactivate.

For an inactive row, render only:

- Detail.

The same conditional behavior applies to English and Japanese output. The
view may inspect the prepared `status` value to hide actions, but this is only
a usability rule. Direct requests must still be rejected by the service.

The inactive row must retain its status chip and all read-only identifying
information.

## 5. Detail and historical read behavior

`GET /employees/{id}` remains available for both statuses. It must not return
404 merely because the employee is inactive.

The detail page continues to display the employee code, name, kana, contact
data, branch, department, employment type, hire date, status, timestamps, and
any existing dispatch history.

For an inactive employee:

- do not render the Edit page action;
- do not render the Deactivate action;
- keep all historical information readable.

For an active employee, preserve the existing Edit and Deactivate actions.

## 6. Direct edit URL

### 6.1 Active employee

`GET /employees/{id}/edit` continues to return `200 HTML` with the current
editable values and form choices.

### 6.2 Inactive employee

The status check belongs in `EmployeeService::editForm()`, immediately after
the employee is loaded and before form choices are prepared. The service must
return an explicit `inactive` outcome rather than constructing an edit form.

The controller maps that expected outcome to:

```text
303 See Other
Location: /employees/{id}?notice=inactive-edit
```

The detail page then renders a safe localized notice. This is consistent with
the project's existing post/redirect/get notice pattern and avoids treating a
known employee as missing. It also avoids adding a new global exception or
403 responder solely for this workflow.

The service must continue to distinguish `missing` from `inactive`:

- invalid or missing employee identifiers use the existing 404 behavior;
- an existing inactive employee receives the inactive redirect above.

No inactive employee data or internal status details are included in the
response beyond the already-readable detail page.

## 7. Direct update request

`POST /employees/{id}` must reject an inactive employee even when the request
contains valid values or is manually constructed.

The required flow is:

1. The controller validates the route identifier using its existing positive
   integer rule.
2. `EmployeeService::updateEmployee()` loads the current employee before
   validating or persisting submitted fields.
3. If the employee is missing, return the existing not-found outcome.
4. If the current status is not `active`, return an explicit `inactive`
   outcome without calling the validator, business relationship checks, or
   repository update.
5. The controller maps the inactive outcome to a 303 redirect to the detail
   page with `notice=inactive-edit`.
6. Only an active employee proceeds through input validation, business checks,
   and the conditional repository update.

An inactive update must not return a form with 422 validation errors. There is
no editable operation to continue, and the detail redirect gives the user the
localized explanation without exposing implementation details.

The service must continue to ignore `employee_code`, `id`, `status`, and
timestamps from submitted input. Employee code remains immutable.

## 8. Deactivation behavior

### 8.1 Active employee

An active employee may be deactivated through the existing confirmation page
and POST route. The operation changes only `status` and `updated_at`, and it
must not delete the row.

### 8.2 Already inactive employee

The current repository SQL already safely makes repeated deactivation a
conditional no-op. The service must also use the repository boolean result:

- `true` means the request changed an active row and returns `deactivated`;
- `false` means no active row was changed and returns `already-inactive` when
  the employee still exists.

This matters when two deactivation requests race after both have read an
active employee. The losing request must not be reported as another successful
deactivation and must not change `updated_at`.

The existing direct POST behavior may remain a `303` redirect to the detail
page with `notice=already-inactive`; it is a non-mutating no-op, not a second
successful lifecycle transition. A directly opened confirmation page for an
inactive employee must not render a confirmation submit form.

No reactivation route, form control, or service operation is added.

## 9. Application service responsibility

`EmployeeService` owns the business rule that an inactive employee cannot be
modified. It must:

- allow reads for either status;
- reject inactive `editForm()` requests;
- reject inactive updates before validation and persistence;
- preserve the existing active employee workflow;
- map conditional update failure to an inactive outcome;
- use the repository result for accurate deactivation outcomes;
- keep status out of ordinary employee input;
- preserve UTC timestamp and employee-code behavior.

The service must not know HTTP status codes, redirect URLs, or HTML. Use the
existing array/status outcome style unless a small equivalent result type is
needed to distinguish `missing`, `inactive`, and `editable`.

Recommended service outcomes are:

- edit form: `missing`, `inactive`, or `editable` with prepared form data;
- update: existing success/validation result plus `missing` or `inactive`;
- deactivation: existing `missing`, `deactivated`, and
  `already-inactive` statuses.

## 10. Repository responsibility

`EmployeeRepositoryInterface` remains focused on persistence and read shapes.
No generic repository or new persistence architecture is needed.

The existing `update()` contract should return `bool` (or an equivalently
explicit `updateActive()` operation) so the service can detect a conditional
write that did not occur. The PDO SQL must be equivalent to:

```sql
UPDATE employees
SET branch_id = :branch_id,
    department_id = :department_id,
    first_name = :first_name,
    last_name = :last_name,
    first_name_kana = :first_name_kana,
    last_name_kana = :last_name_kana,
    email = :email,
    phone = :phone,
    position_title = :position_title,
    employee_type = :employee_type,
    hire_date = :hire_date,
    updated_at = :updated_at
WHERE id = :id
  AND status = 'active'
```

The repository returns whether the statement changed a row. A false result
must not update any field or timestamp. SQL variable values remain bound
through prepared statements.

The existing `deactivate()` conditional SQL and boolean return should be
preserved. The repository must not decide how inactive outcomes are rendered
or which HTTP response is returned.

## 11. Controller responsibility

`EmployeeController` remains responsible for:

- parsing route and request input;
- mapping missing employee results to the existing 404 exception;
- mapping inactive edit/update outcomes to the allowlisted detail redirect;
- preserving existing 303 redirects for successful writes;
- passing safe notice values to the detail view.

The controller must not implement the status business rule by itself and must
not call the repository directly. It may perform the narrow HTTP mapping of a
service outcome.

The existing routes in `routes/web.php` are sufficient and should remain in
their current static-before-dynamic registration order. No route or method
override is required.

## 12. View responsibility

Views receive prepared arrays and scalars only. They must:

- conditionally render Edit and Deactivate based on employee status;
- continue escaping all dynamic values with `HtmlEscaper`;
- render the inactive status and historical data;
- render the localized inactive-edit notice only for an allowlisted notice
  value;
- never enforce security through hidden links alone;
- never call services, repositories, PDO, or request globals.

`employees/edit.php` should remain a normal active-only form. The service and
controller prevent inactive access before this view is rendered.

## 13. Race-condition and defense-in-depth design

Service-only checking is not sufficient. The following race must be safe:

1. An active employee opens an edit page.
2. Another request deactivates the employee.
3. The stale edit form is submitted.

The service check provides the normal business boundary, while
`WHERE id = :id AND status = 'active'` provides the atomic persistence boundary.
If the conditional update returns false, the service returns the inactive
outcome and the controller redirects to the readable detail page. The stale
payload must not modify any column, including `updated_at`.

This is the smallest robust design consistent with the existing repository
contract and with the already-conditional deactivation SQL. A transaction
around the service's initial read and update is not required; the conditional
write is the required race defense.

## 14. HTTP and error behavior

| Situation | Response |
| --- | --- |
| Invalid route identifier | Existing 404 response |
| Missing employee on detail/edit/update | Existing 404 response |
| Active edit GET | 200 HTML edit form |
| Inactive edit GET | 303 to `/employees/{id}?notice=inactive-edit` |
| Active valid update | 303 to `/employees/{id}` |
| Inactive update, including valid manual input | 303 to `/employees/{id}?notice=inactive-edit` |
| Stale update after deactivation | Same inactive 303 redirect; no data change |
| Active deactivation | 303 with `notice=deactivated` |
| Repeated/racing deactivation | 303 with `notice=already-inactive`; no data change |
| Unexpected persistence failure | Existing centralized 500 response |
| Unsupported method | Existing 405 response and `Allow` header |

The application must not expose SQL, DSNs, credentials, raw PDO messages, or
internal exception details. Notice query values must be allowlisted by the
controller before being passed to views.

## 15. Localization

If the inactive edit/update redirect displays a message, add translation keys
to both existing resources:

| Key | English | Japanese |
| --- | --- | --- |
| `employees.inactive_edit_notice` | `Inactive employees cannot be edited.` | `無効な社員は編集できません。` |

The detail view should render this message through the existing `Translator`
and `t()` view data. Controllers and services must not hard-code localized
sentences. Existing `deactivated` and `already-inactive` notices remain
unchanged.

## 16. Test strategy

### 16.1 Service unit tests

Extend `tests/Unit/Application/Employee/EmployeeServiceTest.php` to cover:

- active employee edit form remains available;
- inactive employee edit form returns the inactive outcome and no form;
- active employee update succeeds and preserves employee code/status;
- inactive update is rejected before repository update;
- inactive update is rejected even when submitted fields are valid;
- rejected inactive update leaves all persisted values unchanged;
- a conditional update returning false is mapped to the inactive outcome;
- active deactivation returns `deactivated`;
- sequential repeated deactivation returns `already-inactive`;
- a losing/racing deactivation result is not reported as successful again.

Use focused repository doubles and the existing fixed clock. Assert repository
write calls and timestamps, not only returned status strings.

### 16.2 HTTP/feature tests

Extend `tests/Feature/Http/EmployeeHttpTest.php` to cover:

- active detail, edit, update, and deactivation remain successful;
- inactive detail returns 200 and includes historical employee data;
- an inactive list row contains Detail but not its Edit or Deactivate links;
- an inactive detail page contains neither Edit nor Deactivate actions;
- `GET /employees/{id}/edit` for inactive status returns the expected 303
  detail redirect;
- `POST /employees/{id}` for inactive status returns the same rejection even
  with a valid manually constructed payload;
- the persisted employee data is unchanged after the rejected POST;
- the redirected detail page renders the English inactive-edit notice;
- the Japanese locale renders the Japanese inactive-edit notice;
- repeated deactivation keeps the original `updated_at` value;
- active employee code generation and immutable employee-code behavior remain
  unchanged;
- existing dispatch and organization read relationships remain visible.

Tests must assert row-specific action markup rather than only searching the
whole page, because active rows legitimately continue to contain Edit and
Deactivate links.

### 16.3 Repository integration tests

Extend `tests/Integration/Database/EmployeeRepositoryIntegrationTest.php` to
cover real MySQL/MariaDB behavior:

- active conditional update returns true and updates editable fields;
- inactive conditional update returns false;
- inactive conditional update leaves every field and `updated_at` unchanged;
- employee code is not included in the update set;
- active deactivation changes status and timestamp;
- repeated deactivation returns false and preserves the timestamp;
- the status check constraint still permits only `active` and `inactive`.

Use the existing isolated `DB_TEST_*` setup. Do not add a migration or use the
development database.

### 16.4 Race/defense regression

At minimum, add a deterministic service/repository-double test that changes
the employee to inactive between the service's initial read and the
conditional update and verifies that no update succeeds. If the integration
test harness supports two database connections safely, also verify the
conditional SQL against a real concurrent transition; otherwise the repository
integration test plus the deterministic service test is sufficient evidence of
the intended defense.

## 17. Security and data integrity

- UI links are not a security boundary.
- Status is read from the database and is never trusted from request input.
- The service enforces the inactive modification rule.
- The repository atomically requires `status = 'active'` for update.
- The database status check constraint remains authoritative for allowed
  status values.
- No physical employee deletion is introduced.
- All variable SQL values remain prepared parameters.
- Authentication, authorization, CSRF, and session policy remain outside this
  task and are not implied by this specification.

## 18. No migration

No database migration is required or permitted for this task. The existing
`employees.status` column and constraints remain unchanged. Do not redesign
status storage, add status history, or add audit-log tables.

## 19. Out of scope

This task does not include:

- employee reactivation;
- Phase 08 Employee Advanced Search;
- active/inactive filtering;
- branch redesign;
- department redesign;
- employee-code redesign;
- authentication, authorization, or CSRF redesign;
- physical deletion;
- status history or audit logging;
- unrelated UI redesign;
- dispatch-contract lifecycle changes.

## 20. Acceptance criteria

The implementation is complete only when:

1. Active employees can still be viewed, edited, updated, and deactivated.
2. Inactive employees remain viewable with their historical data.
3. Inactive list rows expose only the Detail action.
4. Inactive detail pages expose neither Edit nor Deactivate.
5. Direct inactive edit GET requests do not render an edit form.
6. Direct inactive update POST requests are rejected server-side.
7. Rejected inactive updates do not change employee data or timestamps.
8. A stale active edit form cannot update an employee after deactivation.
9. Repeated or racing deactivation does not modify an already-inactive row or
   report another successful transition.
10. No physical employee deletion occurs.
11. English and Japanese inactive-edit messaging is localized through the
    existing translation boundary.
12. Employee code remains immutable and code generation behavior does not
    regress.
13. Existing dispatch and organization relationships remain readable.
14. No migration is required.
15. Reactivation, branch redesign, department redesign, and Phase 08 remain
    excluded.

## 21. Expected implementation files

Expected production changes are limited to the existing boundaries:

- `src/Application/Employee/EmployeeService.php`;
- `src/Domain/Employee/EmployeeRepositoryInterface.php`;
- `src/Infrastructure/Persistence/PdoEmployeeRepository.php`;
- `src/Http/Controllers/EmployeeController.php`;
- `resources/views/employees/index.php`;
- `resources/views/employees/show.php`;
- `resources/views/employees/deactivate.php` only if needed to preserve the
  inactive no-submit behavior;
- `resources/lang/en.php`;
- `resources/lang/ja.php`.

Expected test changes are:

- `tests/Unit/Application/Employee/EmployeeServiceTest.php`;
- `tests/Feature/Http/EmployeeHttpTest.php`;
- `tests/Integration/Database/EmployeeRepositoryIntegrationTest.php`.

`routes/web.php`, `ApplicationBootstrap`, the migration directory, and
employee-code allocator files should not require changes. If implementation
finds a genuine need to change one of them, document that separately before
expanding scope.

## Appendix A. Recommended implementation architecture

```text
View
  -> hides Edit/Deactivate for inactive rows
Controller
  -> maps service outcomes to 200/303/404 and safe notices
EmployeeService
  -> owns inactive modification rule and use-case outcomes
EmployeeRepositoryInterface
  -> exposes reads and conditional active-only writes
PdoEmployeeRepository
  -> executes prepared SQL and returns row-change booleans
Database
  -> preserves status check constraints and the employee row
```

Do not add a generic authorization layer, a new exception hierarchy, or a
second employee status model for this focused rule.

## Appendix B. Exact inactive-update protection flow

```text
POST /employees/{id}
  -> controller validates positive integer id
  -> service loads employee
     -> missing: existing 404 outcome
     -> inactive: inactive outcome; no validation/write
     -> active: validate and check business rules
  -> repository UPDATE ... WHERE id = :id AND status = 'active'
     -> row changed: success, 303 to detail
     -> zero rows: inactive/stale outcome, 303 to detail with notice
```

The repository condition is required even though the service checks status,
because the employee can change between the service read and the write.

## Appendix C. Repository-level conditional update recommendation

Repository-level conditional update is recommended and required. The service
check is the readable business rule for ordinary requests; the SQL predicate is
the smallest atomic defense against stale edit forms and concurrent
deactivation. Returning `bool` allows the service to distinguish a successful
write from a rejected stale write without exposing SQL details to the user.

## Appendix D. Risks and open questions from inspection

- The existing unit and HTTP repository doubles implement `update()` as
  `void`; they must be updated if the contract changes to `bool`.
- The current service uses hard-coded validation sentences that the form
  partial maps through `Translator`. The new inactive-edit notice should use a
  translation key directly and should not expand this task into a general
  validation-message refactor.
- Query-string notices are not server-side flash storage. They are acceptable
  here because values are allowlisted and the existing project already uses
  this pattern; do not place employee data in the query string.
- There is no authentication or authorization in the current application. This
  specification protects employee status integrity, not user permissions.
- Dispatch-contract pages can still be reachable from employee detail data;
  this task must not silently redesign dispatch authorization or lifecycle
  rules.

## Appendix E. Explicit exclusions confirmation

Reactivation is excluded. Branch redesign is excluded. Department redesign is
excluded. Phase 08 Employee Advanced Search is excluded. No migration,
physical deletion, status history, authentication redesign, or unrelated UI
redesign is part of this specification.
