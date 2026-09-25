We are starting Phase 07 of the Pure PHP Company Employee Management project.

Current branch:
feature/organization-management

Phase 07:
Organization Management — Branch and Department Management

For this step, DO NOT implement Phase 07.

Your task is ONLY to inspect the existing project and create the Phase 07 specification document.

Create:

docs/specs/07-organization-management.md

Do not create application code.
Do not modify application code.
Do not create migrations.
Do not modify the database.
Do not run php bin/seed.
Do not commit or push.

==================================================

1. # FIRST INSPECT THE EXISTING PROJECT

Before writing the specification, inspect the existing architecture and conventions.

At minimum inspect:

- docs/specs from previous phases
- Phase 05 Employee Management implementation
- Phase 06 Dispatch Management implementation

Organization-related existing code:

src/Domain/Organization/BranchReadRepositoryInterface.php
src/Domain/Organization/DepartmentReadRepositoryInterface.php

src/Infrastructure/Persistence/PdoBranchReadRepository.php
src/Infrastructure/Persistence/PdoDepartmentReadRepository.php

Also inspect:

- EmployeeRepositoryInterface
- PdoEmployeeRepository
- EmployeeService
- Employee DTO / Validator / ValidationResult

- DispatchCompanyRepositoryInterface
- PdoDispatchCompanyRepository
- DispatchCompanyService
- Dispatch DTO / Validator / ValidationResult

- existing controllers
- route registration
- Bootstrap / Composition Root
- shared views / partials
- sidebar/navigation
- localization
- tests
- database migrations for:
  - companies
  - branches
  - departments
  - employees

Do not assume paths for controllers or other components.
Discover the actual project structure first.

# ================================================== 2. PROJECT ARCHITECTURE

The project is Pure PHP.

Technology:

- PHP 8.x
- MySQL / MariaDB
- PDO
- Composer
- PSR-4
- SSR PHP views
- HTML/CSS/JavaScript
- existing Material Design-inspired UI
- English/Japanese localization

Forbidden:

- Laravel
- Symfony
- CodeIgniter
- React
- Vue
- ORM
- direct DB access from views
- non-parameterized SQL

Existing architecture should remain:

Browser
↓
Front Controller
↓
Bootstrap / Composition Root
↓
Middleware
↓
Router
↓
Controller
↓
Application Service
↓
Domain Repository Interface
↓
PDO Repository
↓
Database
↓
View / Redirect

# ================================================== 3. PHASE 07 PURPOSE

Phase 07 should make the existing organization master data manageable through the application UI.

Main targets:

A. Branch Management（支店管理）
B. Department Management（部署管理）

Current database relationship:

Company
↓
Branch
↓
Department
↓
Employee

Existing Employee Management already uses active branches and departments for employee forms.

Phase 07 must not break that behavior.

# ================================================== 4. BRANCH MANAGEMENT SCOPE

Specify the behavior for:

- Branch list
- Branch detail
- Branch create
- Branch edit
- Branch deactivate confirmation
- Branch deactivation

Expected branch fields must be derived from the existing schema.

Likely fields include:

- company
- code
- name
- city
- address
- phone
- status

Do not invent fields that do not exist.

Define proposed routes consistent with the project's existing routing conventions.

Likely shape:

GET /branches
GET /branches/create
POST /branches
GET /branches/{id}
GET /branches/{id}/edit
POST /branches/{id}
GET /branches/{id}/deactivate
POST /branches/{id}/deactivate

But verify against existing project conventions before finalizing the spec.

# ================================================== 5. DEPARTMENT MANAGEMENT SCOPE

Specify:

- Department list
- Department detail
- Department create
- Department edit
- Department deactivate confirmation
- Department deactivation

Fields must come from the actual schema.

Likely:

- branch
- code
- name
- status

Define proposed routes consistent with existing conventions.

Likely shape:

GET /departments
GET /departments/create
POST /departments
GET /departments/{id}
GET /departments/{id}/edit
POST /departments/{id}
GET /departments/{id}/deactivate
POST /departments/{id}/deactivate

# ================================================== 6. BUSINESS RULES TO DEFINE

The specification must explicitly define and justify the behavior for these cases.

Branch:

- Branch belongs to a company.
- Branch code uniqueness scope.
- Required fields.
- Create rules.
- Edit rules.
- Deactivation rules.
- No physical delete.
- What happens when a branch still has departments.
- What happens when a branch still has employees.
- Whether inactive branches can be edited.
- Whether inactive branches can be reactivated in Phase 07.
- Whether new departments can be created under inactive branches.
- Whether employees are automatically changed when a branch is deactivated.

Department:

- Department belongs to a branch.
- Department code uniqueness scope.
- Required fields.
- Create rules.
- Edit rules.
- Deactivation rules.
- No physical delete.
- What happens when employees still belong to the department.
- Whether inactive departments can be edited.
- Whether reactivation belongs to Phase 07.
- Behavior when the parent branch is inactive.
- Whether employee records are automatically changed when a department is deactivated.

Important principle:

Do not silently cascade business status changes unless the existing project requirements explicitly require it.

Historical relationships must be preserved.

# ================================================== 7. IMPORTANT DEACTIVATION DESIGN

Analyze the implications carefully.

Example:

東京支店
├── 開発部
│ ├── EMP001
│ └── EMP002
└── 営業部
└── EMP003

If 東京支店 is deactivated:

Should departments remain unchanged?

Should employees remain unchanged?

Should the branch disappear from future create/edit selection lists?

Should existing employee detail pages still display the inactive branch?

Define these behaviors clearly.

Do the same analysis for department deactivation.

The specification should distinguish:

- historical/read behavior
- selection behavior
- create/update behavior

# ================================================== 8. VALIDATION SPECIFICATION

Derive validation rules from actual migration definitions.

Specify validation for Branch:

- company_id
- code
- name
- city
- address
- phone

Specify validation for Department:

- branch_id
- code
- name

Include:

- required/optional
- data type
- maximum length where applicable
- parent existence
- parent status
- duplicate handling

Do not invent arbitrary limits.

Database constraints remain the final integrity protection.

Raw PDO/MySQL errors must not be exposed to the user.

# ================================================== 9. REPOSITORY DESIGN

Inspect the existing:

BranchReadRepositoryInterface
DepartmentReadRepositoryInterface

They currently provide:

listActive()
findById()

The specification must decide how Phase 07 should evolve repository abstractions.

Consider operations such as:

Branch:

- management list
- listActive
- findById
- create
- update
- deactivate
- duplicate lookup/check

Department:

- management list
- listActive
- findById
- create
- update
- deactivate
- duplicate lookup/check

Compare with EmployeeRepositoryInterface and DispatchCompanyRepositoryInterface.

Avoid unnecessary duplicate abstractions.

Do not implement them yet.

# ================================================== 10. APPLICATION LAYER DESIGN

Specify likely application components without creating them.

For example:

- BranchInput DTO
- DepartmentInput DTO
- BranchInputValidator
- DepartmentInputValidator
- validation result types
- BranchService
- DepartmentService

Explain responsibilities.

Controllers should remain thin.

Services should coordinate business rules.

Repositories should focus on persistence.

# ================================================== 11. UI / UX SPECIFICATION

Follow the existing SSR Material Design-inspired UI.

Reuse existing components where appropriate:

- page header
- status chip
- empty state
- shared layout
- sidebar
- forms
- validation display patterns

Specify the information shown on:

Branch list
Branch detail
Branch create/edit
Branch deactivate confirmation

Department list
Department detail
Department create/edit
Department deactivate confirmation

Consider useful related counts if they can be retrieved without N+1 queries.

Examples:

Branch:

- department count
- employee count

Department:

- employee count

Do not require these counts if they create unnecessary complexity; make a reasoned decision in the spec.

# ================================================== 12. LOCALIZATION

Phase 07 must support:

English
Japanese

Specify new translation areas/keys conceptually.

Use terminology consistent with the existing application.

Examples:

Branch / 支店
Branches / 支店
Department / 部署
Departments / 部署
Branch Code / 支店コード
Department Code / 部署コード

Do not hard-code new user-facing strings in controllers/views where the project already uses Translator.

# ================================================== 13. NAVIGATION

Specify how Branch and Department Management should appear in the existing sidebar/navigation.

Preserve existing navigation for:

- Employees
- Dispatch Companies
- Dispatch Contracts
- locale switching

Do not redesign the whole sidebar.

# ================================================== 14. SECURITY / DATA INTEGRITY

Specify:

- prepared statements
- SSR escaping / XSS protection
- existing CSRF conventions
- server-side validation
- 404 behavior
- duplicate handling
- FK integrity
- no physical deletion
- no direct database access from views

# ================================================== 15. TEST STRATEGY

Specify tests that Phase 07 implementation should later add.

Unit/Application tests:

- Branch validation
- Department validation
- Branch service rules
- Department service rules
- inactive parent behavior
- duplicate handling

Database integration tests:

- branch persistence
- department persistence
- parent relationships
- deactivation
- duplicate constraints
- unrelated data preservation where relevant

HTTP/Feature tests:

Branch:

- list
- detail
- create
- edit
- deactivate
- validation failure
- 404

Department:

- list
- detail
- create
- edit
- deactivate
- validation failure
- inactive parent rejection
- 404

Tests must use the existing isolated test database conventions.

Never use the development database for integration tests.

# ================================================== 16. EXISTING DEVELOPMENT SEEDER

The project already has:

php bin/seed

Existing sample organization data:

SAMPLE-COMPANY
サンプル株式会社

Branches:

- TOKYO / 東京支店
- OSAKA / 大阪支店

Departments:

- TOKYO / DEV / 開発部
- TOKYO / SALES / 営業部
- OSAKA / DEV / 開発部

The specification should state whether Phase 07 requires any seeder changes.

Prefer no seeder change unless necessary.

Do not modify the seeder in this step.

# ================================================== 17. OUT OF SCOPE

Explicitly keep these outside Phase 07:

- Employee advanced search/filter/pagination
- Portfolio management
- Skills
- Projects
- Certifications
- System user management
- authentication/authorization expansion
- unrelated schema redesign

# ================================================== 18. SPEC DOCUMENT STRUCTURE

Write:

docs/specs/07-organization-management.md

Use a clear professional structure such as:

1. Purpose
2. Current State
3. Scope
4. Architecture
5. Domain/Data Relationships
6. Branch Management
7. Department Management
8. Business Rules
9. Validation
10. Repository Design
11. Application Service Design
12. HTTP Routes
13. UI/UX
14. Localization
15. Navigation
16. Security and Data Integrity
17. Error Handling
18. Test Strategy
19. Development Seeder Impact
20. Out of Scope
21. Acceptance Criteria
22. Implementation Notes / Decisions

Include tables where they improve clarity.

The spec must be detailed enough that a later Codex implementation prompt can reference it as the source of truth.

# ================================================== 19. IMPORTANT

For this task:

DO:

- inspect
- analyze
- write the spec

DO NOT:

- implement Phase 07
- create controllers
- create services
- create repositories
- create DTOs
- create validators
- create views
- change routes
- change localization
- change migrations
- change the seeder
- commit
- push

If an important business rule is ambiguous, make a conservative recommendation in the spec and clearly mark it as a design decision.

# ================================================== 20. FINAL REPORT

After creating the specification, report only:

1. File created
2. Existing project areas inspected
3. Major design decisions documented
4. Any ambiguities/risks discovered
5. git status --short

Do not implement anything else.
Do not commit.

# implementaion

We are now implementing Phase 07 of the Pure PHP Company Employee Management project.

Current branch:

feature/organization-management

The Phase 07 specification has already been written:

docs/specs/07-organization-management.md

IMPORTANT:
Read that specification completely before changing any application code.

The specification is the source of truth for Phase 07.

Do not rewrite or reinterpret the approved business rules unless implementation reveals a genuine conflict with the existing schema or architecture.

If a conflict is discovered, stop that affected part and report it clearly rather than silently changing the specification.

==================================================

1. # TASK

Implement Phase 07:

Organization Management

A. Branch Management
B. Department Management

Implement the approved specification in:

docs/specs/07-organization-management.md

This includes:

Branch:

- list
- detail
- create
- edit
- deactivate confirmation
- deactivate

Department:

- list
- detail
- create
- edit
- deactivate confirmation
- deactivate

Also implement the required:

- DTOs
- validators
- validation results
- application services
- repository interfaces
- PDO repositories
- company read support required by forms
- controllers
- routes
- bootstrap / dependency wiring
- SSR views
- sidebar/navigation integration
- EN/JA localization
- tests

Do not commit.
Do not push.
Do not merge.

================================================== 2. BEFORE IMPLEMENTING
==================================================

First inspect:

docs/specs/07-organization-management.md

Then inspect the actual existing implementation patterns from:

Phase 05 Employee Management

and

Phase 06 Dispatch Management.

At minimum inspect:

- EmployeeService
- EmployeeInput
- EmployeeInputValidator
- EmployeeValidationResult
- EmployeeRepositoryInterface
- PdoEmployeeRepository

- DispatchCompanyService
- DispatchCompanyInput
- DispatchCompanyInputValidator
- DispatchCompanyValidationResult
- DispatchCompanyRepositoryInterface
- PdoDispatchCompanyRepository

- existing controller locations and conventions
- Router registration
- ApplicationBootstrap / Composition Root
- Request handling
- Response / redirects
- ViewRenderer
- HtmlEscaper
- Translator
- shared layout
- sidebar
- status chip
- page header
- empty state
- test conventions
- database integration test safeguards

Also inspect:

src/Domain/Organization/BranchReadRepositoryInterface.php
src/Domain/Organization/DepartmentReadRepositoryInterface.php

src/Infrastructure/Persistence/PdoBranchReadRepository.php
src/Infrastructure/Persistence/PdoDepartmentReadRepository.php

Search all usages before modifying organization read abstractions.

================================================== 3. PRESERVE EXISTING ARCHITECTURE
==================================================

Continue using:

Browser
↓
Front Controller
↓
ApplicationBootstrap
↓
Middleware
↓
Router
↓
Controller
↓
Application Service
↓
Domain Repository Interface
↓
PDO Repository
↓
MySQL/MariaDB
↓
SSR View / Redirect

Do not introduce:

- Laravel
- Symfony
- CodeIgniter
- ORM
- React
- Vue
- REST API
- generic CRUD framework
- generic BaseRepository
- unnecessary abstract factories

Controllers must remain thin.

Business rules belong in services.

SQL belongs in PDO repositories.

Views must not access the database.

================================================== 4. IMPORTANT EXISTING READ CONTRACTS
==================================================

The existing:

BranchReadRepositoryInterface

and

DepartmentReadRepositoryInterface

are already used by Employee Management.

Preserve their compatibility.

Do not casually expand them into large management/write interfaces.

Follow the approved specification and introduce focused management repository
interfaces where appropriate.

The expected conceptual separation is:

BranchReadRepositoryInterface
→ existing Employee Management dependency

BranchRepositoryInterface
→ Phase 07 Branch Management

DepartmentReadRepositoryInterface
→ existing Employee Management dependency

DepartmentRepositoryInterface
→ Phase 07 Department Management

A concrete PDO implementation may implement multiple compatible interfaces
only if doing so remains clean and consistent with the project.

Avoid duplicate SQL unnecessarily.

================================================== 5. VERIFY PHASE 05 INACTIVE-RELATIONSHIP BEHAVIOR
==================================================

Before changing Employee-related code, verify the actual current behavior of
EmployeeService and employee forms regarding inactive current branches and
departments.

The Phase 07 specification requires:

- inactive organization records are not available as new assignments;
- existing employee relationships remain readable;
- an employee should not lose an existing inactive current relationship merely
  because the parent was deactivated.

If Phase 05 already supports this behavior, preserve it.

If Phase 05 does not fully support it, make only the smallest regression-safe
change necessary to satisfy the Phase 07 specification.

Do not redesign Employee Management.

Document any Employee-related modification in the final report.

================================================== 6. DATABASE
==================================================

Use the existing schema.

Expected existing hierarchy:

companies
↓
branches
↓
departments
↓
employees

Do NOT create a migration unless implementation discovers a genuine schema
defect that makes the approved specification impossible.

A migration is not expected.

Do not alter an accepted historical migration merely for convenience.

Do not physically delete organization data.

================================================== 7. BRANCH MANAGEMENT
==================================================

Implement the Branch behavior exactly as approved in:

docs/specs/07-organization-management.md

Important rules include:

- branch belongs to an existing company;
- branch code is unique within company;
- company parent cannot be changed during edit;
- new branch status is active;
- status cannot be mass-assigned;
- inactive branch remains readable;
- inactive branch may receive metadata corrections;
- no reactivation in Phase 07;
- deactivation changes only the branch status;
- no department cascade;
- no employee cascade;
- historical relationships remain readable.

Expected routes:

GET /branches
GET /branches/create
POST /branches
GET /branches/{id}/deactivate
POST /branches/{id}/deactivate
GET /branches/{id}/edit
POST /branches/{id}
GET /branches/{id}

Respect the project's actual route-registration style.

Register static/action routes before dynamic /{id} routes.

================================================== 8. DEPARTMENT MANAGEMENT
==================================================

Implement Department behavior exactly as approved.

Important rules include:

- department belongs to an existing branch;
- department code is unique within branch;
- branch parent cannot be changed during edit;
- new department status is active;
- status cannot be mass-assigned;
- new department requires an active branch;
- inactive department remains readable;
- inactive department may receive metadata corrections;
- existing department under inactive branch remains readable/editable;
- no reactivation in Phase 07;
- deactivation changes only department status;
- no employee cascade;
- description is optional plain text;
- blank description becomes NULL.

Expected routes:

GET /departments
GET /departments/create
POST /departments
GET /departments/{id}/deactivate
POST /departments/{id}/deactivate
GET /departments/{id}/edit
POST /departments/{id}
GET /departments/{id}

Again, static/action routes must precede dynamic routes.

================================================== 9. VALIDATION
==================================================

Follow the exact schema-derived validation rules in the specification.

Branch:

company_id
code
name
city
address
phone

Department:

branch_id
code
name
description

Use the existing DTO + Validator + ValidationResult pattern.

Requirements:

- reject arrays/objects for scalar fields;
- trim strings;
- use multibyte-safe length validation;
- positive integer parent ids;
- validate parent existence;
- active branch required for department create;
- enforce parent immutability on update;
- enforce scoped code uniqueness;
- preserve submitted values after validation failure;
- do not accept status/id/timestamps as lifecycle changes;
- translate known duplicate database conflicts into friendly field errors.

Do not expose raw PDO/MySQL errors.

================================================== 10. REPOSITORIES
==================================================

Implement repository design according to the approved spec.

Repository responsibilities:

- prepared SQL
- explicit selected columns
- deterministic ordering
- joins
- aggregate counts
- insert/update/deactivate persistence
- duplicate existence checks
- duplicate exception translation where appropriate

Avoid N+1 queries.

Repositories must not:

- render HTML
- parse HTTP requests
- choose HTTP status codes
- perform business status cascades

Use prepared statements for all variable values.

================================================== 11. APPLICATION SERVICES
==================================================

Implement:

BranchService

DepartmentService

following existing project conventions.

Services coordinate:

- validation
- parent existence
- parent status
- parent immutability
- scoped duplicate checking
- lifecycle rules
- Clock timestamps
- repository operations
- prepared form/list/detail data

Do not put SQL in services.

Do not put redirects or HTML rendering in services.

================================================== 12. CONTROLLERS
==================================================

Use the actual controller namespace/location already established by the
project.

Implement thin Branch and Department controllers/actions.

Responsibilities:

- route id validation
- request input extraction
- service calls
- ViewRenderer usage
- 303 redirects
- 404 mapping
- HTTP 422 validation responses

Do not place business logic or SQL in controllers.

================================================== 13. SSR VIEWS
==================================================

Implement the approved branch and department SSR pages.

Branch:

resources/views/branches/

- index.php
- show.php
- create.php
- edit.php
- deactivate.php
- \_form.php

Department:

resources/views/departments/

- index.php
- show.php
- create.php
- edit.php
- deactivate.php
- \_form.php

Follow the existing Material Design-inspired layout.

Reuse existing:

- page header
- status chip
- empty state
- shared layout
- form styles
- validation patterns

Do not introduce a new CSS framework.

Escape every dynamic user/database value with the existing HtmlEscaper
convention.

Description remains plain text.

================================================== 14. COUNTS / RELATED DATA
==================================================

Where required by the approved specification, provide:

Branch:

- department count
- employee count
- related department information

Department:

- employee count
- related employee information

Avoid N+1 queries.

Prefer explicit joins / aggregate queries consistent with the repository
architecture.

Do not introduce unnecessary query abstractions.

================================================== 15. LOCALIZATION
==================================================

Update:

resources/lang/en.php
resources/lang/ja.php

Add all Phase 07 user-facing strings.

Use the existing Translator.

Do not hard-code new user-facing English/Japanese strings in controllers or
views when they belong in translation files.

Maintain terminology consistency:

Branch / 支店
Department / 部署
Branch Code / 支店コード
Department Code / 部署コード

================================================== 16. NAVIGATION
==================================================

Activate the existing unavailable sidebar entries:

Branches
→ /branches

Departments
→ /departments

Use the appropriate active navigation keys.

Do not redesign the sidebar.

Do not break:

- Employees
- Dispatch Companies
- Dispatch Contracts
- Dashboard
- locale switching

================================================== 17. SECURITY / INTEGRITY
==================================================

Preserve:

- PDO prepared statements
- SSR escaping
- server-side validation
- database foreign keys
- database unique constraints
- status constraints
- POST-only mutations
- centralized unexpected-error handling

Do not introduce authentication/authorization/CSRF infrastructure in this
phase if it does not already exist.

Do not claim these routes are production-secure without those later controls.

================================================== 18. DEVELOPMENT SEEDER
==================================================

The existing development seeder already contains sufficient Phase 07 data.

Do not change it unless implementation genuinely requires a change.

Do not run:

php bin/seed

as part of automated tests.

Do not modify development data during test execution.

================================================== 19. TESTS
==================================================

Implement the test strategy defined in:

docs/specs/07-organization-management.md

Add appropriate:

- validator tests
- service/application tests
- repository integration tests
- HTTP/feature tests
- Employee Management regression tests

Important cases include:

Branch:

- list
- detail
- create
- edit
- deactivate
- validation
- duplicate code
- parent immutability
- inactive read/edit
- idempotent deactivation
- 404

Department:

- list
- detail
- create
- edit
- deactivate
- validation
- duplicate code
- parent immutability
- inactive parent create rejection
- inactive read/edit
- description handling
- idempotent deactivation
- 404

Regression:

- Employee active organization choices still work
- existing inactive relationships remain readable
- existing Employee routes work
- Dispatch routes work
- locale behavior works

================================================== 20. TEST DATABASE SAFETY
==================================================

Database integration tests MUST use the existing isolated test DB conventions.

Never use:

company_employee_management

for automated tests.

Use only the configured:

APP*ENV=test
DB_TEST*\*

environment.

Before running database integration tests, verify the project's existing safety
mechanism.

If the test DB is unavailable, skip/report according to existing conventions.

Never substitute the development database.

================================================== 21. TEST EXECUTION
==================================================

After implementation:

1. Run relevant syntax/static checks used by the project.

2. Run focused Phase 07 unit/application tests.

3. Run the normal PHPUnit suite.

4. Run database integration tests only with explicit isolated test DB
   configuration.

5. Report exact results.

Do not hide failures.

If a test fails:

- investigate;
- fix implementation/test issues that are within Phase 07 scope;
- rerun affected tests;
- then rerun the complete suite.

Do not weaken valid assertions merely to make tests pass.

================================================== 22. DO NOT
==================================================

Do not:

- commit
- push
- merge
- change branches
- delete unrelated files
- reset existing work
- create a new framework
- introduce an ORM
- redesign Employee Management
- redesign Dispatch Management
- add company CRUD
- add reactivation
- add employee advanced search
- add authentication/user management
- add unrelated migrations
- modify the development database from tests

================================================== 23. GIT SAFETY
==================================================

Remain on:

feature/organization-management

At completion run:

git branch --show-current
git status --short

Do not stage files.
Do not commit files.
Do not push.

================================================== 24. FINAL REPORT
==================================================

After implementation and testing, provide a concise but complete report with:

1. Current branch
2. Architecture implemented
3. Files created
4. Files modified
5. Routes added
6. Branch functionality implemented
7. Department functionality implemented
8. Validation/business rules implemented
9. Repository design used
10. Any Employee Management compatibility changes
11. Localization/navigation changes
12. Tests added
13. Focused test results
14. Full PHPUnit result
15. Integration-test result
16. Any skipped tests and why
17. Migration changes
18. Seeder changes
19. Remaining concerns / technical debt
20. git status --short

Explicitly state:

- whether any migration was added/changed;
- whether the development seeder was changed;
- whether Employee Management required modification;
- whether any test touched the development database.

Do not commit or push.
