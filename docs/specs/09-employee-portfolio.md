# Phase 09: Employee Portfolio Specification

## 1. Purpose

This document specifies Phase 09 of the Company Employee Management System:
server-rendered management of an employee's professional portfolio.

The portfolio contains:

- skills and employee-specific proficiency information;
- projects and career/work history;
- certifications and professional qualifications.

This is an implementation specification only. It does not create PHP classes,
migrations, routes, views, tests, or database data. Implementation must use the
assigned feature/employee-portfolio branch.

The existing request flow remains:

~~~text
Browser
  -> front controller -> middleware -> router -> controller
  -> application service -> repository interface -> PDO -> MySQL/MariaDB
  -> SSR view or redirect
~~~

The phase remains Pure PHP, PDO, custom-router, server-side rendered HTML. It
does not introduce Laravel, Symfony, an ORM, React, Vue, a SPA, or a REST API.

## 2. Current-state audit

### 2.1 Architecture discovered

The application uses PHP 8.x, Composer PSR-4 autoloading, PDO, MySQL/MariaDB,
a front controller at public/index.php, a custom router, HttpKernel, locale
middleware, controllers, application services, repository interfaces, PDO
repositories, ViewRenderer, PHP views, and explicit bootstrap wiring.

The Employee implementation is split across:

- src/Application/DTO/EmployeeInput.php and EmployeeSearchCriteria.php;
- src/Application/Validation/EmployeeInputValidator.php;
- src/Application/Employee/EmployeeService.php;
- src/Domain/Employee/EmployeeRepositoryInterface.php;
- src/Infrastructure/Persistence/PdoEmployeeRepository.php;
- src/Http/Controllers/EmployeeController.php;
- routes/web.php;
- resources/views/employees/;
- resources/lang/en.php and resources/lang/ja.php;
- src/Bootstrap/ApplicationBootstrap.php.

Employee create/update uses a validator, business checks, a UTC Clock,
prepared PDO statements, and POST -> 303 -> GET. Validation failures preserve
submitted values and render HTTP 422. Missing resources use the existing 404
exception path.

### 2.2 Existing Employee schema and lifecycle

The employees table contains:

- id;
- branch_id and nullable department_id;
- unique employee_code and unique email;
- first/last names and kana names;
- nullable phone and position_title;
- employee_type: permanent or dispatched;
- required hire_date;
- status: active or inactive;
- created_at and updated_at.

The current lifecycle is:

| Employee state | View | Edit | Deactivate | Delete/reactivate |
| --- | --- | --- | --- | --- |
| Active | Allowed | Allowed | Allowed | No physical delete / no reactivation |
| Inactive | Allowed | Blocked | Blocked/idempotent notice | No physical delete / no reactivation |

PdoEmployeeRepository::update() and deactivate() include the active-status
condition, so a direct request cannot bypass the service rule.

The Employee detail page shows basic information, organization, employment
data, and dispatched-contract history. It has no portfolio section.

### 2.3 Existing routes and UI conventions

Employee routes currently include:

~~~text
GET  /employees
GET  /employees/create
POST /employees
GET  /employees/{id}
GET  /employees/{id}/edit
POST /employees/{id}
GET  /employees/{id}/deactivate
POST /employees/{id}/deactivate
~~~

Views use the existing Material Design-inspired layout, page headers, cards,
tables, empty states, status chips, escaped dynamic values, and SSR forms.
Mutation confirmation uses a normal page rather than a modal-only workflow.

### 2.4 Existing database and migration conventions

The repository has six migrations:

1. companies;
2. branches;
3. departments;
4. employees;
5. dispatch companies and contracts;
6. employee-code sequence.

The tables use BIGINT UNSIGNED primary keys, InnoDB, utf8mb4,
utf8mb4_unicode_ci, UTC DATETIME timestamps, explicit indexes, foreign keys
with ON UPDATE RESTRICT and ON DELETE RESTRICT, and checks for controlled
values or date relationships where appropriate.

### 2.5 Portfolio schema finding

No portfolio schema exists. There are no skills, employee_skills,
employee_projects, or employee_certifications tables; no portfolio DTOs,
validators, services, repositories, controllers, routes, views, translation
keys, or tests exist.

Phase 09 therefore requires one new forward migration unless a target database
is later found to contain an already approved equivalent schema.

### 2.6 Security and authorization finding

The current application has locale middleware but no implemented authentication,
roles, authorization middleware, or CSRF infrastructure. Phase 09 must not pull
Phase 10 authentication/roles into this work. It must still enforce the
Employee active/inactive mutation rule in application and persistence paths,
use prepared statements, bounded inputs, safe route IDs, and escaped output.

## 3. Goals

Phase 09 must:

1. provide professional SSR portfolio pages from Employee detail;
2. allow portfolio reads for active and inactive Employees;
3. allow portfolio mutation only while the owning Employee is active;
4. preserve portfolio history without physical child-record deletion;
5. normalize reusable skills without coupling project technologies to the
   current skill catalog;
6. validate every writable field at the application boundary;
7. enforce foreign keys, ownership, duplicates, and dates at the correct
   database/application boundaries;
8. support English and Japanese labels, errors, actions, and enum values;
9. keep controllers thin, views SQL-free, and repository SQL prepared;
10. provide unit, repository-integration, and HTTP feature coverage.

## 4. Non-goals

This phase does not include:

- Phase 10 authentication, login, sessions, ADMIN/USER roles, or authorization;
- CSRF infrastructure when it is not already present;
- employee reactivation;
- branch or department redesign;
- portfolio PDF/resume export;
- file uploads or document storage;
- external certification verification APIs;
- skill recommendations, analytics, AI, or dashboards;
- a global skill-management administration screen;
- REST/JSON, SPA, React, Vue, Laravel, Symfony, or ORM architecture;
- project staffing, billing, payroll, attendance, or time tracking;
- automatic translation of user-entered portfolio data.

## 5. Portfolio UX and navigation

### 5.1 Navigation decision

Use a combination of an Employee-detail summary and separate resource pages.
Do not put all portfolio forms and records into the Employee detail
controller/view.

The Employee detail page receives a compact Professional portfolio card with
counts and links:

~~~text
Employee detail
  -> Professional portfolio summary
       -> Skills
       -> Projects
       -> Certifications
~~~

There is no separate /employees/{id}/portfolio landing route in this phase.
The three resource list pages are the entry points. Each page keeps Employee
identity, employee code, status, and a link back to Employee detail. This is
the smallest coherent SSR route set and avoids a one-size-fits-all controller.

### 5.2 Active and inactive presentation

The summary and all resource lists are readable for inactive Employees. An
inactive badge and localized read-only notice are shown. Create, edit, and
archive controls are omitted for inactive Employees, but the server also
rejects direct mutation requests.

Archived records remain visible in a clearly separated historical section
rather than being silently lost.

### 5.3 Data display

User-entered skill names, project content, certification data, credentials, and
notes are displayed as stored except for HTML escaping. They are not
translated. Controlled labels such as Advanced, Archived, and Ongoing use the
translator.

## 6. Employee lifecycle interaction

The portfolio follows the Employee lifecycle without copying Employee status
columns onto every record:

| Owning Employee | Portfolio read | Portfolio create/update/archive |
| --- | --- | --- |
| Active | Allowed | Allowed when validation succeeds |
| Inactive | Allowed, including archived history | Rejected; no database write |
| Missing | 404 | 404 |

The service loads the current Employee before every use case. Each portfolio
repository mutation also checks the Employee's active status, or performs the
write through an equivalent active-Employee condition, so a status change
between service validation and persistence cannot create a write gap.

For consistency with existing Employee routes, a direct mutation request for an
inactive Employee redirects with HTTP 303 to that Employee's resource list and
an allowlisted inactive-edit notice. It must not report a successful mutation or
alter a portfolio row. A future security phase may replace this presentation
without changing the domain rule.

Deactivating an Employee does not archive, alter, or hide portfolio records.

## 7. Skill domain design

### 7.1 Modeling decision

Use a normalized global skills master plus an employee_skills association. Do not
store a repeated free-text skill name on every Employee row.

Normalization has real value because PHP, MySQL, AWS, and similar skills are
reused across employees, duplicate labels should be controlled, and a future
catalog can be managed without rewriting employee history.

There is no global skill-admin screen in Phase 09. The employee skill form
accepts a trimmed skill_name; the service finds the existing master by its
unique name or creates the master in the same transaction as the assignment.
An SSR datalist or select may offer existing skill names, but JavaScript is not
required.

### 7.2 Employee-specific fields

The association contains only fields useful to an internal employee directory:

- skill name, represented by skill_id and entered as skill_name at the form
  boundary;
- controlled proficiency;
- optional years_experience with one decimal place;
- optional free-text notes.

Do not add arbitrary percentage scores, months and years simultaneously,
last-used dates, endorsements, ratings, or localized skill labels.

### 7.3 Proficiency vocabulary

The allowed stored values are:

~~~text
beginner
intermediate
advanced
expert
~~~

The database check and validator use this allowlist. The UI uses localized
labels; values are stable machine values.

### 7.4 Duplicate policy

One Employee has at most one association for a given global skill across the
entire association history. The database unique key is
(employee_id, skill_id), covering active and archived rows.

Adding an already-active skill is a validation conflict. Adding a skill whose
association is archived reuses that row, updates the submitted metadata,
clears archived_at, and restores it to active. It does not insert a duplicate.
There is no separate restore screen in this phase.

### 7.5 Skill lifecycle

Removing a skill archives the employee association. The global skill master is
never deleted by this feature. Archived assignments remain available in the
history section and can be restored through a new add submission for the same
skill while the Employee is active.

## 8. Project domain design

### 8.1 Fields

Each project contains:

- project_name, required;
- role, required employee role on the project;
- start_date, required;
- end_date, optional for ongoing work;
- description, optional plain text summary;
- responsibilities, optional plain text detail;
- technologies, optional plain text snapshot of technologies used;
- lifecycle status and timestamps.

Project technologies remain project-specific text. They are not foreign keys to
the current Skills catalog because usage is historical, may include versions or
tools not in the catalog, and should remain readable if a skill changes.

### 8.2 Date and overlap rules

- start_date is a strict YYYY-MM-DD date and is required;
- blank end_date is stored as NULL and means ongoing;
- end_date, when present, is on or after start_date;
- overlapping projects are allowed because an Employee may work on several
  projects at once;
- ongoing is derived from end_date IS NULL; no redundant is_current column is
  stored.

### 8.3 Project lifecycle

Projects are never physically deleted. An active project can be archived from
an SSR confirmation page. Archived projects remain readable in history and are
not included in the default active-project count.

Editing an archived project is not provided in Phase 09. A later restore or
correction workflow can be added without losing the row.

There is no project-name uniqueness rule. The same Employee may have repeated
work for the same client/project name across periods or roles.

## 9. Certification domain design

### 9.1 Fields

Each certification contains:

- certification_name, required;
- issuing_organization, required;
- obtained_date, required;
- expiration_date, optional;
- credential_identifier, optional reference/credential number;
- notes, optional plain text;
- lifecycle status and timestamps.

### 9.2 Date and duplicate rules

- obtained_date is a strict YYYY-MM-DD date and is required;
- blank expiration_date is stored as NULL;
- expiration_date, when present, is not before obtained_date;
- the database unique boundary is
  (employee_id, certification_name, issuing_organization, obtained_date);
- names and organizations are trimmed before persistence and use the existing
  case-insensitive project collation;
- same certification name is allowed when obtained date or issuer differs,
  supporting renewals and re-certification;
- an archived exact duplicate is restored/updated rather than inserted;
- credential identifiers are not globally unique.

### 9.3 Certification lifecycle

Certifications are never physically deleted. Archive is the removal/correction
semantics. Archived certifications remain visible in history and do not count
as active qualifications. Phase 09 does not expose a separate restore route.

## 10. Historical-data policy

| Resource | Active state | Removal semantics | Physical delete |
| --- | --- | --- | --- |
| Global skill master | Reusable catalog name | Retained while and after associations exist | Never by Phase 09 |
| Employee skill association | Current skill profile | Archive association | Never |
| Project | Current portfolio history | Archive project | Never |
| Certification | Current qualification history | Archive certification | Never |

This is intentionally not a copy of Employee active/inactive. Portfolio records
use active/archived because they represent individual career-history entries,
while Employee status determines whether a person can currently be changed.

## 11. Proposed database schema

All tables use BIGINT UNSIGNED auto-increment primary keys, InnoDB, utf8mb4,
utf8mb4_unicode_ci, and UTC DATETIME values. Application validation remains
required even when a database check exists.

### 11.1 skills

| Column | Type | Null/default | Rule |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | PK, auto increment | Internal key |
| name | VARCHAR(120) | NOT NULL | Unique canonical display name |
| created_at | DATETIME | NOT NULL | UTC creation time |
| updated_at | DATETIME | NOT NULL | UTC last update |

Constraints:

- PRIMARY KEY (id);
- UNIQUE KEY uq_skills_name (name);
- no status column and no speculative search index;
- skill names remain user data, not translation keys.

### 11.2 employee_skills

| Column | Type | Null/default | Rule |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | PK, auto increment | Association key |
| employee_id | BIGINT UNSIGNED | NOT NULL | Owning Employee |
| skill_id | BIGINT UNSIGNED | NOT NULL | Global skill |
| proficiency | VARCHAR(20) | NOT NULL | Four-value allowlist |
| years_experience | DECIMAL(4,1) | NULL | 0.0 through 99.9 |
| notes | TEXT | NULL | Optional plain text |
| status | VARCHAR(20) | active | active or archived |
| archived_at | DATETIME | NULL | Required for archived rows |
| created_at | DATETIME | NOT NULL | UTC creation time |
| updated_at | DATETIME | NOT NULL | UTC last update |

Constraints and indexes:

- UNIQUE KEY uq_employee_skills_employee_skill (employee_id, skill_id);
- KEY idx_employee_skills_employee_status (employee_id, status);
- KEY idx_employee_skills_skill (skill_id);
- foreign keys to employees(id) and skills(id), update/delete restrict;
- check for the proficiency allowlist;
- check for years_experience NULL or between 0 and 99.9;
- check that active rows have archived_at NULL and archived rows have a
  non-null archived_at, subject to supported engine behavior.

### 11.3 employee_projects

| Column | Type | Null/default | Rule |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | PK, auto increment | Project key |
| employee_id | BIGINT UNSIGNED | NOT NULL | Owning Employee |
| project_name | VARCHAR(160) | NOT NULL | Project/client label |
| role | VARCHAR(120) | NOT NULL | Employee role |
| start_date | DATE | NOT NULL | First project day |
| end_date | DATE | NULL | Last day; NULL means ongoing |
| description | TEXT | NULL | Optional summary |
| responsibilities | TEXT | NULL | Optional detail |
| technologies | TEXT | NULL | Historical text snapshot |
| status | VARCHAR(20) | active | active or archived |
| archived_at | DATETIME | NULL | Required for archived rows |
| created_at | DATETIME | NOT NULL | UTC creation time |
| updated_at | DATETIME | NOT NULL | UTC last update |

Constraints and indexes:

- KEY idx_employee_projects_employee_status (employee_id, status);
- KEY idx_employee_projects_employee_period (employee_id, start_date, end_date);
- foreign key to employees(id), update/delete restrict;
- check start_date <= end_date when end_date is not null;
- check for lifecycle values and archived timestamp pairing.

No project uniqueness key and no overlap constraint are required.

### 11.4 employee_certifications

| Column | Type | Null/default | Rule |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED | PK, auto increment | Certification key |
| employee_id | BIGINT UNSIGNED | NOT NULL | Owning Employee |
| certification_name | VARCHAR(160) | NOT NULL | Qualification name |
| issuing_organization | VARCHAR(120) | NOT NULL | Issuer |
| obtained_date | DATE | NOT NULL | Date obtained |
| expiration_date | DATE | NULL | Optional expiry |
| credential_identifier | VARCHAR(120) | NULL | Optional reference |
| notes | TEXT | NULL | Optional plain text |
| status | VARCHAR(20) | active | active or archived |
| archived_at | DATETIME | NULL | Required for archived rows |
| created_at | DATETIME | NOT NULL | UTC creation time |
| updated_at | DATETIME | NOT NULL | UTC last update |

Constraints and indexes:

- UNIQUE KEY uq_employee_certification_identity
  (employee_id, certification_name, issuing_organization, obtained_date);
- KEY idx_employee_certifications_employee_status (employee_id, status);
- KEY idx_employee_certifications_expiration (employee_id, expiration_date);
- foreign key to employees(id), update/delete restrict;
- check expiration_date NULL or expiration_date >= obtained_date;
- check for lifecycle values and archived timestamp pairing.

The identity key excludes expiration and credential identifier. Renewals with a
new obtained date remain separate rows.

## 12. Constraints, indexes, and referential behavior

Foreign keys use ON UPDATE RESTRICT and ON DELETE RESTRICT. The application has
no physical Employee deletion workflow, but restrict behavior prevents a future
destructive operation from silently discarding the portfolio.

No child uses ON DELETE CASCADE. No database trigger is required. The
application owns archive timestamps and localized notices.

Database unique constraints are the final duplicate boundary. Service-level
checks improve form feedback but are not concurrency protection. Repositories
translate known unique violations into portfolio duplicate results rather than
exposing raw PDO exceptions.

## 13. Transaction and concurrency design

### 13.1 Skill create/restore

Adding a skill is one transaction covering:

1. lock or look up the Employee and verify it is active;
2. find the global skill by normalized name or insert it;
3. find the employee/skill association;
4. reject an active duplicate, or update an archived row to active;
5. insert a new association when none exists;
6. commit master and association changes together.

The skills.name and employee_skills(employee_id, skill_id) unique keys resolve
concurrent requests. If a concurrent insert wins, the repository re-reads the
row and returns the appropriate duplicate or restore outcome.

### 13.2 Project and certification writes

Each create/update/archive operation rechecks the owning Employee's active
status and the child ownership boundary. The implementation may use a short
transaction or an atomic SQL condition, but not an earlier service read alone.

Certification exact-duplicate races are resolved by the database unique key. The
service catches the repository duplicate result and returns localized field
errors with submitted values.

### 13.3 Scope

No transaction spans unrelated skills, projects, and certifications. One form
submission changes one resource at a time. Timestamps come from the existing
UTC Clock.

## 14. Validation rules

All validators trim strings, reject non-string structured values, preserve
submitted values on failure, and use the existing multibyte length convention.
Blank optional strings become NULL. HTML constraints never replace validation.

### 14.1 Skills

| Field | Rule |
| --- | --- |
| skill_name | Required, trim/collapse whitespace, 1–120 Unicode characters |
| proficiency | Required, one of beginner/intermediate/advanced/expert |
| years_experience | Optional decimal, 0–99.9, at most one decimal place |
| notes | Optional plain text, maximum 5,000 Unicode characters |

### 14.2 Projects

| Field | Rule |
| --- | --- |
| project_name | Required, 1–160 characters |
| role | Required, 1–120 characters |
| start_date | Required, strict YYYY-MM-DD |
| end_date | Optional strict YYYY-MM-DD; blank becomes NULL |
| description | Optional plain text, maximum 5,000 characters |
| responsibilities | Optional plain text, maximum 5,000 characters |
| technologies | Optional plain text, maximum 2,000 characters |

end_date before start_date is an error on both date fields. Overlap is not an
error.

### 14.3 Certifications

| Field | Rule |
| --- | --- |
| certification_name | Required, 1–160 characters |
| issuing_organization | Required, 1–120 characters |
| obtained_date | Required, strict YYYY-MM-DD |
| expiration_date | Optional strict YYYY-MM-DD; blank becomes NULL |
| credential_identifier | Optional, maximum 120 characters |
| notes | Optional plain text, maximum 5,000 characters |

expiration_date before obtained_date is an error on both date fields. Exact
identity duplicates are conflicts; a different obtained date is not.

## 15. DTOs and repository interfaces

Add explicit input DTOs:

- EmployeeSkillInput;
- EmployeeProjectInput;
- EmployeeCertificationInput.

DTOs contain normalized domain values, not raw requests, HTTP objects, or
localized labels.

Prefer focused repository interfaces:

### SkillRepositoryInterface

~~~text
listForEmployee(employeeId, includeArchived): array
findAssignmentForEmployee(employeeId, assignmentId): ?array
findSkillByName(name): ?array
insertSkill(name, createdAt, updatedAt): int
insertAssignment(employeeId, skillId, input, createdAt, updatedAt): int
updateAssignment(employeeId, assignmentId, input, updatedAt): bool
archiveAssignment(employeeId, assignmentId, archivedAt): bool
~~~

insertAssignment owns skill lookup/create and restore transaction or delegates
to a private repository transaction helper. No method accepts an unscoped
assignment ID for mutation.

### EmployeeProjectRepositoryInterface

~~~text
listForEmployee(employeeId, includeArchived): array
findByIdForEmployee(employeeId, projectId): ?array
insert(employeeId, input, createdAt, updatedAt): int
update(employeeId, projectId, input, updatedAt): bool
archive(employeeId, projectId, archivedAt): bool
~~~

### EmployeeCertificationRepositoryInterface

~~~text
listForEmployee(employeeId, includeArchived): array
findByIdForEmployee(employeeId, certificationId): ?array
insert(employeeId, input, createdAt, updatedAt): int
update(employeeId, certificationId, input, updatedAt): bool
archive(employeeId, certificationId, archivedAt): bool
~~~

Every findByIdForEmployee and mutation method includes both IDs in SQL.
Child-only lookup is not acceptable.

The existing EmployeeRepositoryInterface remains the Employee context reader;
it is not widened into a generic portfolio repository.

## 16. Application services

Use separate services:

- EmployeeSkillService — list, form, create/restore, update, archive;
- EmployeeProjectService — list, detail, form, create, update, archive;
- EmployeeCertificationService — list, detail, form, create, update, archive;
- EmployeePortfolioSummaryService — read-only counts and compact summary data
  for Employee detail.

Each service receives repository interfaces, an Employee reader, its validator,
and Clock. Services verify Employee existence/status, enforce business rules,
produce form values/errors and safe read models, translate persistence outcomes,
and never construct SQL or render HTML.

The summary service may issue three count queries or focused list queries; it
must not load all portfolio text merely to render Employee detail.

## 17. Controllers and dependency wiring

Add thin controllers:

- EmployeeSkillController;
- EmployeeProjectController;
- EmployeeCertificationController.

Controllers validate positive route IDs at the HTTP boundary, pass structured
body data to services, map outcomes to 200/303/422/404, and render views. They
do not perform ownership checks by themselves; services and scoped repositories
do that too.

ApplicationBootstrap explicitly constructs validators, repositories, services,
summary service, and controllers. No service locator or global database access
is introduced.

EmployeeController::show() receives portfolio summary data through the summary
service or equivalent collaborator. It does not query portfolio tables.

## 18. Routes

Register explicit routes in routes/web.php. Resource routes must be registered
before less-specific Employee routes where matching order requires it.

### Skills

~~~text
GET  /employees/{employeeId}/skills
GET  /employees/{employeeId}/skills/create
POST /employees/{employeeId}/skills
GET  /employees/{employeeId}/skills/{skillId}/edit
POST /employees/{employeeId}/skills/{skillId}
GET  /employees/{employeeId}/skills/{skillId}/archive
POST /employees/{employeeId}/skills/{skillId}/archive
~~~

### Projects

~~~text
GET  /employees/{employeeId}/projects
GET  /employees/{employeeId}/projects/create
POST /employees/{employeeId}/projects
GET  /employees/{employeeId}/projects/{projectId}
GET  /employees/{employeeId}/projects/{projectId}/edit
POST /employees/{employeeId}/projects/{projectId}
GET  /employees/{employeeId}/projects/{projectId}/archive
POST /employees/{employeeId}/projects/{projectId}/archive
~~~

### Certifications

~~~text
GET  /employees/{employeeId}/certifications
GET  /employees/{employeeId}/certifications/create
POST /employees/{employeeId}/certifications
GET  /employees/{employeeId}/certifications/{certificationId}
GET  /employees/{employeeId}/certifications/{certificationId}/edit
POST /employees/{employeeId}/certifications/{certificationId}
GET  /employees/{employeeId}/certifications/{certificationId}/archive
POST /employees/{employeeId}/certifications/{certificationId}/archive
~~~

A project detail page is justified by multiline historical fields. A
certification detail page is justified by credential and notes data. Skills
remain a compact list.

## 19. SSR views and HTTP behavior

### 19.1 Expected views

Add:

~~~text
resources/views/employee-skills/index.php
resources/views/employee-skills/_form.php
resources/views/employee-skills/create.php
resources/views/employee-skills/edit.php
resources/views/employee-skills/archive.php

resources/views/employee-projects/index.php
resources/views/employee-projects/_form.php
resources/views/employee-projects/create.php
resources/views/employee-projects/edit.php
resources/views/employee-projects/show.php
resources/views/employee-projects/archive.php

resources/views/employee-certifications/index.php
resources/views/employee-certifications/_form.php
resources/views/employee-certifications/create.php
resources/views/employee-certifications/edit.php
resources/views/employee-certifications/show.php
resources/views/employee-certifications/archive.php
~~~

Update resources/views/employees/show.php with the summary card only. Views
receive prepared data, contain no SQL, and escape every dynamic value.

### 19.2 Responses

| Request | Result |
| --- | --- |
| Valid portfolio GET | 200 SSR page |
| Successful create/update/archive POST | 303 to resource list or detail |
| Validation/duplicate failure | 422 with submitted values and localized errors |
| Missing Employee | Existing 404 response |
| Child missing or not owned by URL Employee | 404, with no disclosure |
| Inactive Employee mutation | 303 to resource list with inactive notice; no write |
| Invalid route identifier | Existing 404 response |

Archive confirmation pages are 200 for active, existing, owned records. An
inactive or already archived record is not archived again; the service returns
a safe informational outcome and the controller redirects to the list.

## 20. EN/JA localization

Add Phase 09 keys to both resources/lang/en.php and resources/lang/ja.php:

- portfolio headings, descriptions, breadcrumbs, and navigation labels;
- skills/projects/certifications field labels;
- active/archived/ongoing labels;
- proficiency labels;
- create/edit/archive actions and confirmations;
- empty states and history headings;
- inactive read-only and archived notices;
- required, length, date, enum, range, and duplicate errors;
- success and invalid-state messages;
- accessible table labels where needed.

Use machine keys for controlled values. User-entered portfolio content goes
through the existing escaper and never through translation lookup.

## 21. Security and data handling

- Use PDO prepared statements for every value.
- Never interpolate request values into SQL identifiers, sort fragments, or
  predicates.
- Validate all IDs as positive integers.
- Bound all text lengths, including TEXT fields.
- Escape names, notes, technologies, credentials, errors, and query-derived
  values in views.
- Keep SQL in repositories and business rules in services.
- Enforce active Employee status in the service and again at the repository
  write boundary.
- Do not add authentication, roles, or CSRF claims to Phase 09. Record the
  absence of CSRF infrastructure for Phase 10.

## 22. Error handling and ownership enforcement

Portfolio child IDs are scoped to the Employee in the URL. Every detail, edit,
update, and archive operation uses a method such as
findByIdForEmployee(employeeId, childId), with SQL predicates containing both
IDs.

If a child belongs to Employee A but the request uses Employee B's URL, behave
as if the child does not exist and return 404. Do not render it, redirect to
Employee A, or reveal the mismatch.

The same scope is required for mutation SQL:

~~~sql
WHERE child.id = :child_id
  AND child.employee_id = :employee_id
~~~

Known duplicate-key and inactive-owner outcomes become stable service results.
Raw SQL, table names, connection details, and traces are never shown in SSR.

## 23. Test plan

### 23.1 Unit tests

Add tests for:

- skill required/name length/trim behavior;
- skill proficiency allowlist;
- years-experience decimal/range rules;
- project required fields and text bounds;
- project strict dates and start_date <= end_date;
- project overlap acceptance;
- certification required fields and text bounds;
- certification expiration ordering;
- certification renewal versus exact duplicate behavior;
- active Employee mutation success;
- inactive Employee mutation rejection for create/update/archive;
- archived skill restore behavior;
- repository duplicate/status results mapped to field errors.

### 23.2 Repository integration tests

Use the existing test-database guard and migration runner. Prove:

- all four tables migrate and roll back in dependency order;
- skills and associations persist and read with explicit columns;
- foreign keys reject invalid Employee and skill IDs;
- Employee-skill uniqueness rejects a second association;
- certification identity uniqueness rejects exact duplicates but allows renewals;
- overlapping projects persist and read;
- NULL ongoing project end dates round-trip;
- certification expiration dates round-trip;
- archive preserves rows and sets status/timestamp;
- no portfolio child is physically deleted;
- restricted foreign keys prevent destructive parent deletion;
- concurrent/duplicate skill creation resolves through constraints and
  transaction handling;
- inactive Employee writes do not modify children.

Existing tests asserting migration count must change from six to seven when this
migration is implemented.

### 23.3 HTTP feature tests

Cover:

- Employee detail portfolio summary links;
- skills/projects/certifications list, create, edit, detail, and archive pages;
- POST -> 303 -> GET;
- invalid submissions return 422 and preserve/escape submitted values;
- English and Japanese labels and controlled values;
- empty states and archived-history display;
- escaping of portfolio names, notes, technologies, and credentials;
- inactive Employee read-only UI;
- direct inactive mutation rejection for every resource;
- missing Employee and missing child 404 behavior;
- Employee A child cannot be viewed or edited through Employee B's URL;
- invalid route IDs use existing 404 behavior;
- duplicate skill and certification messages are localized.

## 24. Expected implementation files

### New migration

~~~text
database/migrations/Version20260929000700CreateEmployeePortfolio.php
~~~

The exact version prefix must remain the next valid migration version if the
repository changes before implementation. The migration creates skills first,
then employee_skills, employee_projects, and employee_certifications, and drops
them in reverse order.

### New application/domain/infrastructure files

~~~text
src/Application/DTO/EmployeeSkillInput.php
src/Application/DTO/EmployeeProjectInput.php
src/Application/DTO/EmployeeCertificationInput.php
src/Application/Validation/EmployeeSkillInputValidator.php
src/Application/Validation/EmployeeProjectInputValidator.php
src/Application/Validation/EmployeeCertificationInputValidator.php
src/Domain/Employee/SkillRepositoryInterface.php
src/Domain/Employee/EmployeeProjectRepositoryInterface.php
src/Domain/Employee/EmployeeCertificationRepositoryInterface.php
src/Infrastructure/Persistence/PdoSkillRepository.php
src/Infrastructure/Persistence/PdoEmployeeProjectRepository.php
src/Infrastructure/Persistence/PdoEmployeeCertificationRepository.php
src/Application/Employee/EmployeeSkillService.php
src/Application/Employee/EmployeeProjectService.php
src/Application/Employee/EmployeeCertificationService.php
src/Application/Employee/EmployeePortfolioSummaryService.php
src/Http/Controllers/EmployeeSkillController.php
src/Http/Controllers/EmployeeProjectController.php
src/Http/Controllers/EmployeeCertificationController.php
~~~

Validation-result and duplicate exception classes may be added beside existing
conventions when needed. No generic CRUD base class is required.

### Existing files expected to change

~~~text
src/Bootstrap/ApplicationBootstrap.php
routes/web.php
src/Http/Controllers/EmployeeController.php
resources/views/employees/show.php
resources/lang/en.php
resources/lang/ja.php
~~~

### Tests expected to add/update

~~~text
tests/Unit/Application/Employee/EmployeeSkillServiceTest.php
tests/Unit/Application/Employee/EmployeeProjectServiceTest.php
tests/Unit/Application/Employee/EmployeeCertificationServiceTest.php
tests/Unit/Application/Validation/EmployeeSkillInputValidatorTest.php
tests/Unit/Application/Validation/EmployeeProjectInputValidatorTest.php
tests/Unit/Application/Validation/EmployeeCertificationInputValidatorTest.php
tests/Integration/Database/EmployeePortfolioRepositoryIntegrationTest.php
tests/Feature/Http/EmployeePortfolioHttpTest.php
~~~

Existing migration-discovery/count tests and Employee HTTP composition tests
change only where the new routes or migration count require it.

## 25. Migration plan

1. Confirm that no approved portfolio tables exist in the target schema.
2. Add one forward migration using the discovery naming and namespace
   convention.
3. Create skills first.
4. Create employee_skills, employee_projects, and
   employee_certifications with restricted foreign keys.
5. Add the unique keys, indexes, and checks from section 11.
6. Implement down() in reverse dependency order.
7. Do not alter existing Employee or dispatch migrations.
8. Do not add portfolio seed rows unless explicitly requested during
   implementation review.
9. Update integration fixtures and migration-count assertions for migration
   seven.

No existing production portfolio data requires migration because the current
repository contains no portfolio schema.

## 26. Acceptance criteria

Phase 09 is accepted when:

1. Employee detail provides SSR links for skills, projects, and certifications.
2. Active Employees can create, edit, and archive permitted portfolio rows.
3. Inactive Employees can view all portfolio history but cannot mutate it,
   including through direct POST requests.
4. Skills use a normalized global master and unique Employee association.
5. Proficiency values are controlled and never arbitrary percentages.
6. Project dates, ongoing work, overlap, and project-specific technologies
   follow this specification.
7. Certification date and renewal/duplicate rules follow this specification.
8. Portfolio rows are archived, not physically deleted.
9. Every child lookup and mutation enforces Employee ownership in the query.
10. Forms preserve values and return localized 422 errors when invalid.
11. Dynamic output is escaped and SQL values are prepared.
12. EN and JA views include labels, actions, notices, enum values, and errors.
13. Missing Employees and ownership mismatches return existing 404 behavior.
14. Successful writes use POST -> 303 -> GET.
15. Migration and unit, integration, and HTTP tests pass.

## 27. Risks and tradeoffs

### Global skill creation without a master screen

The recommended form is convenient and keeps Phase 09 small, but portfolio
editors can create new global names. A later admin phase may add catalog
curation, merge, or retirement workflows. The unique name key and trimmed input
prevent common duplicates now.

### Archive instead of delete

Archive preserves history and prevents accidental loss, but adds status and
archive-time fields and requires a future restore/correction workflow. That is
appropriate for career information.

### Skill uniqueness across history

The single employee/skill key makes duplicate behavior deterministic and
supports restoration, but does not record multiple historical proficiency
versions for one skill. A later audit/versioning phase can add that.

### Project technologies remain text

Text preserves historical snapshots and avoids over-modeling, but does not
support normalized technology analytics. Analytics is outside this phase.

### Database check portability

Current migrations already use checks. Implementation must verify supported
MySQL/MariaDB enforcement and retain equivalent service validation when an older
engine parses but does not enforce checks.

## 28. Decisions requiring review

The implementation should not begin until these choices are accepted:

1. Proficiency vocabulary: beginner, intermediate, advanced, expert.
2. Skill fields: proficiency, optional one-decimal years_experience, and
   optional notes; last-used date is omitted.
3. Global skill creation during employee assignment rather than a master screen.
4. Archive for all employee portfolio associations; no physical delete route.
5. Resource pages nested under /employees/{employeeId}; no portfolio hub.
6. Archived rows shown in a separate history section by default.
7. Same certification name/issuer/obtained date is the duplicate boundary;
   renewals with a different obtained date remain separate.
8. Use the next valid migration version if another migration appears before
   implementation; the filename above assumes 007 is next.

## 29. Final implementation report

1. Current Employee architecture: Pure PHP SSR with custom router, controllers,
   services, repository interfaces, PDO repositories, explicit bootstrap
   wiring, escaped PHP views, and EN/JA translations.
2. Existing portfolio schema: none; no tables, classes, routes, views, or tests.
3. Recommended Skill model: normalized global skills plus employee_skills.
4. Recommended Skill fields: name, controlled proficiency, optional one-decimal
   years of experience, optional notes, and archive metadata.
5. Recommended Project fields: name, role, start/end dates, description,
   responsibilities, project-specific technologies, and archive metadata.
6. Recommended Certification fields: name, issuing organization, obtained date,
   optional expiration date, optional credential identifier, notes, and archive
   metadata.
7. Historical/delete policy: retain global skills; archive employee skills,
   projects, and certifications; never physically delete portfolio rows.
8. Proposed routes: nested resource list/create/edit/detail/archive routes for
   skills, projects, and certifications.
9. Inactive Employee behavior: reads remain available; mutations are rejected
   server-side with no write and an existing-convention redirect notice.
10. Ownership enforcement: every child read and write uses Employee ID and child
    ID; mismatch returns 404.
11. Proposed tables/constraints: skills, employee_skills, employee_projects, and
    employee_certifications with restricted FKs, employee/status indexes,
    uniqueness, and date/enum checks.
12. Migrations required: one new forward migration, likely
    Version20260929000700CreateEmployeePortfolio.php.
13. Expected files to change: DTOs, validators, repository contracts and
    implementations, three resource services, summary service, controllers,
    bootstrap, routes, Employee detail summary, translations, views, and tests.
14. Decisions requiring review: proficiency vocabulary, skill-master creation,
    archive visibility/restore behavior, route shape, certification duplicate
    boundary, and the next migration version.

