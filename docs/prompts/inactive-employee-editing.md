We are working on the Pure PHP project:

company-employee-management

Current branch:

fix/inactive-employee-editing

This task is SPECIFICATION ONLY.

Do NOT implement production code.
Do NOT modify existing PHP source files.
Do NOT modify tests yet.
Do NOT create migrations.
Do NOT stage, commit, merge, or push anything.

First inspect the current Employee Management implementation carefully,
especially:

- EmployeeController
- EmployeeService
- EmployeeRepositoryInterface
- PdoEmployeeRepository
- Employee DTO/read models
- employee list/detail/edit/deactivate views
- employee routes
- employee feature/HTTP tests
- employee service unit tests
- employee repository integration tests
- localization resources
- existing Phase 05 Employee Management specification
- current status/deactivation behavior

Then create:

docs/specs/inactive-employee-editing.md

==================================================

1. # PURPOSE

Define and enforce employee status rules for active and inactive employees.

Current problem:

An employee with:

status = inactive

is still shown with an Edit action and may potentially access the edit/update
flow.

Inactive employees must remain readable for historical purposes but must not
be editable.

# ================================================== 2. FINAL BUSINESS RULE

ACTIVE employee:

- Detail/view: allowed
- Edit form: allowed
- Update: allowed
- Deactivate: allowed

INACTIVE employee:

- Detail/view: allowed
- Edit form: NOT allowed
- Update: NOT allowed
- Deactivate again: NOT allowed
- Historical data remains readable
- Employee record must NOT be physically deleted

Reactivation is NOT part of this task.

# ================================================== 3. LIST UI

Employee list behavior:

For active employee:

詳細
編集
無効化

For inactive employee:

詳細

Do not show:

編集
無効化

for inactive employees.

English UI must follow the same behavior.

UI hiding is only a usability rule.

It must NOT be the security/business-rule boundary.

# ================================================== 4. DIRECT EDIT URL

A user must not be able to bypass the list UI by manually opening:

GET /employees/{id}/edit

for an inactive employee.

Inspect the current HTTP architecture and determine the cleanest behavior.

Specify:

- which layer checks the employee status;
- what HTTP response/redirect should occur;
- whether the application should use an existing domain/application exception;
- how the user receives a safe localized message if appropriate.

Prefer consistency with existing project conventions.

Do not invent a new architecture unnecessarily.

# ================================================== 5. DIRECT UPDATE REQUEST

A user must not be able to bypass the UI by sending:

POST /employees/{id}

for an inactive employee.

The backend must reject the update even if:

- all submitted fields are valid;
- the user manually constructs the request;
- the edit form itself is hidden.

This rule must be enforced server-side.

Specify exactly where the business rule belongs.

Do not rely only on Controller UI logic.

# ================================================== 6. DEACTIVATION

Active employee:

active -> inactive

is allowed.

Inactive employee:

inactive -> inactive

must not be treated as another successful deactivation.

Inspect the existing repository/service behavior.

Determine whether the current implementation already safely prevents repeated
deactivation.

If it does, document the existing behavior and required regression tests.

If application-layer improvement is needed, specify it.

Do not implement reactivation.

# ================================================== 7. DETAIL / HISTORICAL READ

Inactive employees remain available through:

GET /employees/{id}

Their employee code, name, branch, department, employment type, status, and
other historical information remain readable.

Do NOT return 404 merely because an employee is inactive.

Inactive is not deleted.

# ================================================== 8. RESPONSIBILITY

Clearly define responsibilities for:

EmployeeController
EmployeeService
EmployeeRepository
Views
Database

Preferred principle:

View
-> controls which actions are displayed

Controller
-> HTTP mapping/response

Service
-> business rule: inactive employee cannot be modified

Repository
-> persistence/read operations and safe conditional updates where useful

Database
-> integrity constraints

Inspect the current architecture before deciding the exact implementation.

Avoid duplicating the same business rule unnecessarily across layers.

# ================================================== 9. RACE-CONDITION / DEFENSE-IN-DEPTH

Consider this case:

1. Edit page is opened while employee is active.
2. Another request deactivates the employee.
3. The original edit form is submitted.

The update must NOT modify the now-inactive employee.

The specification must determine whether service-only status checking is
sufficient or whether repository update SQL should also defensively require:

WHERE id = :id
AND status = 'active'

or an equivalent atomic persistence rule.

Recommend the smallest robust design consistent with the existing project.

# ================================================== 10. ERROR / HTTP BEHAVIOR

Specify behavior for attempts to edit/update an inactive employee.

The behavior must:

- not expose internal details;
- be deterministic;
- use existing HTTP/application conventions where possible;
- support EN/JA localization if user-facing text is needed.

Possible user-facing concept:

English:
Inactive employees cannot be edited.

Japanese:
無効な社員は編集できません。

But inspect existing error/redirect conventions before choosing the final
behavior.

# ================================================== 11. LOCALIZATION

If new user-facing messages are required, use:

resources/lang/en.php
resources/lang/ja.php
Translator

Do not hard-code localized messages in controllers or services.

# ================================================== 12. TESTS

Specify Unit, Feature/HTTP, and Integration tests.

At minimum cover:

ACTIVE:

- active employee detail works
- active employee edit page works
- active employee update works
- active employee can be deactivated

INACTIVE:

- inactive employee detail still works
- inactive employee list row does not show Edit
- inactive employee list row does not show Deactivate
- GET /employees/{id}/edit is rejected
- POST /employees/{id} is rejected
- persisted employee data remains unchanged after rejected update
- repeated deactivation does not modify data again

Race/defense behavior:

- update cannot succeed if employee becomes inactive before persistence

Regression:

- employee code remains immutable
- existing Employee CRUD behavior for active employees still works
- dispatch/organization relationships are not broken
- EN/JA behavior remains correct

# ================================================== 13. NO DATABASE MIGRATION

This task should not require a database migration.

The existing employees.status field remains authoritative.

Do not redesign the employee status schema.

# ================================================== 14. OUT OF SCOPE

Do NOT implement:

- employee reactivation
- Phase 08 Employee Advanced Search
- active/inactive status filter
- Branch redesign
- Department redesign
- employee-code redesign
- authentication/authorization redesign
- physical deletion
- status history/audit log
- unrelated UI redesign

# ================================================== 15. ACCEPTANCE CRITERIA

The specification must define acceptance criteria including:

1. Active employee can still be viewed, edited, updated, and deactivated.

2. Inactive employee remains viewable.

3. Inactive employee list row exposes only appropriate read actions and no
   Edit/Deactivate action.

4. Direct GET edit access for inactive employee is blocked.

5. Direct POST update for inactive employee is blocked.

6. Rejected update does not change employee data.

7. Repeated deactivation does not change the already-inactive employee.

8. A stale edit form cannot update an employee after that employee has become
   inactive.

9. No physical employee deletion occurs.

10. No migration is required.

11. Employee Code Generation behavior does not regress.

12. Reactivation and Phase 08 remain excluded.

# ================================================== 16. REQUIRED SPEC STRUCTURE

The document should contain at least:

1. Purpose
2. Current behavior/problem
3. Final status rules
4. Active employee behavior
5. Inactive employee behavior
6. List UI behavior
7. Detail behavior
8. Edit GET behavior
9. Update POST behavior
10. Deactivation behavior
11. Service responsibility
12. Repository responsibility
13. Controller responsibility
14. View responsibility
15. Race-condition protection
16. HTTP/error behavior
17. Localization
18. Test strategy
19. Security/data integrity
20. Out of scope
21. Acceptance criteria
22. Expected implementation files

At the end provide:

A. Recommended implementation architecture

B. Exact inactive-update protection flow

C. Whether repository-level conditional update is recommended and why

D. Expected files to change

E. Risks/open questions found during project inspection

F. Confirmation that Reactivation, Branch Redesign, Department Redesign,
and Phase 08 are excluded

Again:

SPECIFICATION ONLY.

Do not implement anything.

# Implementation Prompt

Implement the approved Inactive Employee Editing specification for the
company-employee-management Pure PHP project.

Current branch:

fix/inactive-employee-editing

Authoritative specification:

docs/specs/inactive-employee-editing.md

Read the complete specification and inspect the existing implementation before
changing code.

Do NOT implement anything outside this specification.

==================================================
GOAL
==================================================

Enforce the employee lifecycle rule:

ACTIVE employee:

- Detail allowed
- Edit allowed
- Update allowed
- Deactivate allowed

INACTIVE employee:

- Detail allowed
- Edit NOT allowed
- Update NOT allowed
- Deactivate again NOT allowed as another successful transition
- Historical data remains readable

Reactivation is NOT part of this task.

==================================================

1. # LIST UI

Update the employee list.

Active employee row:

詳細
編集
無効化

Inactive employee row:

詳細

Do not render Edit or Deactivate actions for inactive employees.

Apply the same behavior in English and Japanese.

Do not rely on UI hiding as the security boundary.

================================================== 2. DETAIL UI
==================================================

Active employee detail:

- Edit action remains available
- Deactivate action remains available

Inactive employee detail:

- historical/read-only data remains visible
- status remains visible
- Edit action is not rendered
- Deactivate action is not rendered

Do not return 404 merely because the employee is inactive.

================================================== 3. EDIT GET PROTECTION
==================================================

Protect:

GET /employees/{id}/edit

EmployeeService::editForm() must distinguish:

- missing
- inactive
- editable

For inactive employees:

Do not construct/render an edit form.

Controller behavior:

303 redirect to:

/employees/{id}?notice=inactive-edit

Missing employees continue using the existing 404 behavior.

================================================== 4. UPDATE POST PROTECTION
==================================================

Protect:

POST /employees/{id}

EmployeeService::updateEmployee() must:

1. load the existing employee;
2. return missing if it does not exist;
3. check status before editable-field validation;
4. return inactive immediately when status is not active;
5. perform normal validation/business checks only for active employees;
6. perform the repository conditional update;
7. treat a zero-row conditional update as inactive/stale.

A malicious manually constructed request must not update an inactive employee.

Do not trust submitted:

- status
- employee_code
- id
- timestamps

================================================== 5. RACE-CONDITION PROTECTION
==================================================

Service-only status checking is NOT sufficient.

Change the employee update persistence operation so the final SQL write is
conditional on active status.

Equivalent SQL requirement:

UPDATE employees
SET ...
WHERE id = :id
AND status = 'active'

The repository update operation must return whether the row was actually
updated.

If the employee becomes inactive between the service read and repository
write:

- no editable field changes;
- updated_at does not change;
- service returns inactive/stale;
- controller redirects to detail with inactive-edit notice.

Do not add unnecessary transactions solely for this rule.

================================================== 6. REPOSITORY CONTRACT
==================================================

Update EmployeeRepositoryInterface cleanly so the application can distinguish
a successful active update from a rejected conditional update.

Prefer the smallest change consistent with the approved specification.

Update all repository doubles/test implementations affected by the signature
change.

Keep employee_code out of UPDATE SQL.

Preserve prepared statements.

================================================== 7. DEACTIVATION
==================================================

Preserve the existing conditional repository behavior:

WHERE id = :id
AND status = 'active'

Use its boolean result in EmployeeService.

Expected outcomes:

active transition:
deactivated

already inactive / losing concurrent request:
already-inactive

Repeated deactivation must:

- not change status again;
- not change updated_at;
- not report another successful transition.

An inactive employee must not receive a usable deactivate confirmation form.

Do NOT add reactivation.

================================================== 8. LOCALIZED NOTICE
==================================================

Add/use:

employees.inactive_edit_notice

English:

Inactive employees cannot be edited.

Japanese:

無効な社員は編集できません。

Use the existing Translator architecture.

Do not hard-code localized sentences in controller/service code.

Only allow known notice values through the existing notice mechanism.

================================================== 9. TESTS
==================================================

Update/add tests required by the approved specification.

Service unit tests:

- active edit form works
- inactive edit form rejected
- active update works
- inactive update rejected before repository update
- malicious valid inactive update rejected
- conditional update false maps to inactive
- active deactivation returns deactivated
- repeated/racing deactivation returns already-inactive

HTTP/Feature tests:

- active row shows Detail/Edit/Deactivate
- inactive row shows Detail only
- inactive detail remains 200
- inactive detail has no Edit action
- inactive detail has no Deactivate action
- inactive edit GET returns 303
- redirect points to detail with inactive-edit notice
- inactive POST update returns 303
- inactive POST does not modify persisted data
- English notice works
- Japanese notice works
- repeated deactivation does not change updated_at
- active CRUD still works
- employee-code generation/immutability does not regress

Repository integration tests:

- active conditional update succeeds
- inactive conditional update returns false
- inactive data remains unchanged
- inactive updated_at remains unchanged
- employee_code remains unchanged
- first deactivation succeeds
- repeated deactivation returns false
- repeated deactivation preserves timestamp

Add deterministic stale-edit/race regression coverage.

Use real two-connection concurrency only if it can be deterministic and
non-flaky in the existing test architecture.

================================================== 10. NO MIGRATION
==================================================

Do NOT add or modify database migrations.

The existing employees.status field remains authoritative.

================================================== 11. SCOPE PROTECTION
==================================================

Do NOT implement:

- Reactivation
- Phase 08 Employee Advanced Search
- active/inactive filtering
- Branch redesign
- Department redesign
- Employee Code redesign
- authentication/authorization redesign
- physical employee deletion
- audit/status history
- unrelated UI changes

================================================== 12. VERIFICATION
==================================================

After implementation:

1. Run relevant targeted tests.
2. Run the normal complete test suite.
3. Run the complete database-backed test suite using:

APP_ENV=test \
DB_TEST_HOST=127.0.0.1 \
DB_TEST_PORT=3306 \
DB_TEST_DATABASE=company_employee_management_test \
DB_TEST_USERNAME=root \
DB_TEST_PASSWORD= \
DB_TEST_CHARSET=utf8mb4 \
composer test

4. Report:

- files changed
- repository contract changes
- exact active-only UPDATE protection
- service inactive protection
- UI changes
- localization changes
- targeted test result
- normal full test result
- database-backed full test result
- skipped tests
- failures/errors
- PHPUnit deprecations
- any remaining limitations

IMPORTANT:

Do not commit.
Do not merge.
Do not push.

Leave all implementation changes in the working tree for review.
