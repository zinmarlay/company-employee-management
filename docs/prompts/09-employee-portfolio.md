We are starting Phase 09: Employee Portfolio.

Current branch:
feature/employee-portfolio

IMPORTANT:
Do NOT implement anything yet.
Do NOT modify production code.
Do NOT modify tests yet.
Do NOT create migrations yet.
Do NOT commit, merge, or push.

First inspect the existing project architecture, database schema, migrations,
Employee domain/application code, repository patterns, routes, views, tests,
and existing documentation.

Then create ONLY:

docs/specs/09-employee-portfolio.md

==================================================
PHASE 09 GOAL
==================================================

Add professional employee career/portfolio management to the existing
Pure PHP employee management system.

The portfolio area must cover:

1. Skills
2. Projects
3. Certifications

These records belong to an Employee and must remain readable as historical
career information.

The design must fit the existing architecture:

Browser
→ Front Controller
→ Middleware
→ Router
→ Controller
→ Application Service
→ Repository
→ PDO
→ MySQL

SSR PHP views only.

No Laravel.
No Symfony.
No ORM.
No React/Vue.
No SPA.

==================================================
CURRENT EMPLOYEE RULES
==================================================

Preserve existing Employee lifecycle rules.

Active Employee:

- view allowed
- edit allowed
- deactivate allowed

Inactive Employee:

- view allowed
- edit blocked
- deactivate blocked
- no physical deletion
- no reactivation currently

Determine how these rules should apply to portfolio mutation.

Preferred business rule for review:

Active Employee:

- portfolio records readable
- skills can be added/edited/removed according to approved lifecycle design
- projects can be added/edited
- certifications can be added/edited

Inactive Employee:

- entire portfolio remains readable
- no portfolio mutation

Do not implement this assumption until the specification analyzes and states
the exact rule.

==================================================
PORTFOLIO ENTRY POINT
==================================================

Inspect the existing Employee detail page.

Design the cleanest SSR navigation for portfolio information.

Consider:

Employee Detail
├── Basic Information
├── Skills
├── Projects
└── Certifications

Determine whether Phase 09 should use:

A. sections on Employee detail;
B. separate portfolio page;
C. separate resource pages linked from Employee detail;
D. a combination.

Prefer simple professional SSR UX and avoid one huge controller/view.

Document the chosen route structure.

==================================================
SKILLS
==================================================

Design skill management carefully.

Examples:

PHP
Laravel
JavaScript
React
MySQL
PostgreSQL
Git
Docker
AWS

Analyze whether Skills should use:

A. free-text skill per employee;
B. global skill master + employee_skill relation;
C. another normalized model.

Prefer normalization where it provides real value.

Possible employee-specific fields to evaluate:

- skill
- proficiency level
- years/months of experience
- notes
- last used date/year

Do NOT add every possible field automatically.

Choose fields useful for an internal employee management system.

Proficiency must use a controlled vocabulary if included.

Do not use arbitrary numeric "percentage skill levels".

Define duplicate prevention.

Example question:

Can the same Employee have PHP twice?

Normally no.

Specify the uniqueness boundary.

==================================================
PROJECTS
==================================================

Projects represent Employee work/career history.

Evaluate fields such as:

- project name
- role
- start date
- end date
- description
- responsibilities
- technologies
- status/current project

Keep the model practical.

Define date rules.

At minimum consider:

start_date <= end_date

and:

end_date may be NULL for ongoing work

Decide whether overlapping projects are allowed.

Do NOT assume overlap is invalid:
employees may participate in multiple projects simultaneously.

Determine whether technologies should reuse Skills or remain project-specific
text/relations.

Avoid unnecessary complexity for this phase.

==================================================
CERTIFICATIONS
==================================================

Certifications represent professional qualifications.

Examples:

AWS Certified Solutions Architect
Oracle Java certification
PHP certification
JLPT

Evaluate fields such as:

- certification name
- issuing organization
- obtained date
- expiration date
- credential/reference identifier
- notes

Define date rules:

expiration_date must not precede obtained_date when both exist.

Determine duplicate rules carefully.

Do not assume two certifications with the same name are always duplicates;
renewals/re-certification may exist.

Design a practical uniqueness policy.

==================================================
HISTORICAL DATA
==================================================

Portfolio data is career history.

Avoid physical deletion where historical preservation is important.

Analyze appropriate lifecycle semantics independently for:

- employee skills;
- projects;
- certifications.

Possible options include:

- physical delete;
- deactivate/archive;
- end-date/history;
- immutable historical record plus correction.

Choose the simplest professional policy appropriate for each resource.

Do not blindly copy Employee active/inactive status to every table.

==================================================
DATABASE DESIGN
==================================================

Inspect existing migration conventions and schema.

Propose normalized tables and foreign keys.

At minimum evaluate:

skills
employee_skills
employee_projects
employee_certifications

But do not assume all four are required.

For every proposed table specify:

- columns;
- data types;
- nullable/not-null;
- primary key;
- foreign keys;
- unique constraints;
- indexes;
- check constraints if appropriate;
- timestamps;
- deletion/referential behavior.

Preserve historical Employee data.

Do not add derived fields unless justified.

Do not store localized UI labels in database columns.

==================================================
TRANSACTIONS / CONCURRENCY
==================================================

Identify which writes require transactions.

Consider duplicate Skill assignment and concurrent requests.

Do not use application-only duplicate checks when a database unique constraint
is the correct final boundary.

Explain the DB constraint + application validation relationship.

==================================================
DOMAIN / APPLICATION DESIGN
==================================================

Inspect the current project's conventions.

Design:

- DTOs / input objects where appropriate;
- Services;
- Repository interfaces;
- PDO repository implementations;
- Controllers;
- route ownership;
- view models.

Keep controllers thin.

No database access in views.

No SQL in controllers/services.

No locale-specific SQL.

Use explicit Dependency Injection through ApplicationBootstrap.

==================================================
AUTHORIZATION / LIFECYCLE
==================================================

Phase 10 authentication/roles are not implemented yet.

Do NOT implement ADMIN/USER authorization in Phase 09.

However, portfolio mutation must respect Employee status.

Server-side enforcement is mandatory.

Hiding a button in the UI is not sufficient.

Direct GET/POST mutation attempts against an inactive Employee must be
rejected according to the project's existing HTTP/error conventions.

==================================================
VALIDATION
==================================================

Specify server-side validation for every writable field.

Include:

- required/optional;
- maximum lengths;
- date parsing;
- allowed enum values;
- numeric ranges where applicable;
- trimming/normalization;
- duplicate behavior.

Validation failures must preserve user input and provide localized errors
according to existing project conventions.

==================================================
SECURITY
==================================================

Preserve existing security rules:

- PDO prepared statements;
- output escaping;
- no direct DB access in views;
- bounded inputs;
- safe integer IDs;
- server-side lifecycle enforcement.

Inspect current CSRF state but do NOT pull Phase 10 security work into
Phase 09 unless CSRF infrastructure already exists and is already mandatory.

==================================================
LOCALIZATION
==================================================

The application supports EN and JA.

All Phase 09 UI labels, validation messages, empty states, actions, and
controlled enum labels must support both EN and JA.

Use the existing:

- Locale
- Translator
- resources/lang/en.php
- resources/lang/ja.php

Do not translate user-entered portfolio data automatically.

Examples:

"PHP"
"AWS migration project"
"AWS Certified Solutions Architect"

remain exactly as entered.

==================================================
ORGANIZATION DISPLAY
==================================================

Phase 08 + Organization Display Localization are complete.

Do not redesign:

- PrefectureCatalog;
- DepartmentCatalog;
- OrganizationDisplayNameResolver;
- Branch/Department localization.

Portfolio screens may show Employee organization context, but must reuse the
existing display architecture.

==================================================
SSR UI
==================================================

Use the existing Material Design-inspired SSR UI.

Specify screens/forms for:

Skills:

- list/section
- add
- edit
- lifecycle/removal action if approved

Projects:

- list
- detail if justified
- create
- edit
- lifecycle action if approved

Certifications:

- list
- create
- edit
- lifecycle action if approved

Include:

- empty states;
- validation feedback;
- confirmation screens for destructive/archive actions;
- inactive Employee read-only behavior;
- EN/JA labels.

Avoid modal-only workflows if they weaken server-side SSR behavior.

==================================================
ROUTES
==================================================

Propose explicit REST-like SSR routes consistent with the current Router.

For example only:

GET /employees/{employeeId}/skills
GET /employees/{employeeId}/skills/create
POST /employees/{employeeId}/skills
...

Do not copy this blindly.

Inspect current route conventions first and propose the smallest coherent
route set.

Specify expected:

- 200
- redirect after successful POST
- 404
- validation behavior
- inactive Employee mutation behavior

==================================================
TESTING
==================================================

Design comprehensive tests.

Unit tests:

- validation;
- lifecycle rules;
- date rules;
- duplicate rules;
- controlled enum rules.

Repository integration tests:

- create/read/update;
- FK behavior;
- uniqueness;
- historical preservation;
- project date persistence;
- certification dates;
- transaction behavior where relevant.

HTTP feature tests:

- Employee portfolio navigation;
- create/edit;
- validation;
- EN/JA;
- escaping;
- inactive Employee read-only UI;
- direct inactive mutation rejection;
- missing Employee;
- missing portfolio record;
- ownership mismatch.

IMPORTANT:
A portfolio child ID belonging to Employee A must never be editable through
Employee B's URL.

Specify how ownership is enforced.

==================================================
OUT OF SCOPE
==================================================

Do NOT include:

- Phase 10 authentication;
- ADMIN/USER roles;
- login;
- React/Vue;
- REST API;
- file uploads;
- portfolio PDF export;
- employee reactivation;
- Branch/Department redesign;
- machine translation;
- dashboards/analytics unless strictly required;
- external certification verification APIs;
- skill recommendations/AI;
- employee resume generation.

==================================================
SPEC CONTENT
==================================================

docs/specs/09-employee-portfolio.md must include:

1. Purpose
2. Current-state audit
3. Goals
4. Non-goals
5. Portfolio UX/navigation
6. Employee lifecycle interaction
7. Skill domain design
8. Project domain design
9. Certification domain design
10. Historical-data policy
11. Proposed database schema
12. Constraints and indexes
13. Foreign-key/delete behavior
14. Transaction/concurrency design
15. Validation rules
16. Repository interfaces
17. Application services
18. Controllers
19. Routes
20. SSR views
21. EN/JA localization
22. Security
23. Error/HTTP behavior
24. Ownership enforcement
25. Test plan
26. Expected implementation files
27. Migration plan
28. Acceptance criteria
29. Risks/tradeoffs
30. Decisions requiring review

==================================================
FINAL REPORT
==================================================

After creating the specification, report:

1. current Employee architecture discovered;
2. whether any portfolio schema already exists;
3. recommended Skill model;
4. recommended Skill fields;
5. recommended Project fields;
6. recommended Certification fields;
7. historical/delete policy for each resource;
8. proposed routes;
9. inactive Employee behavior;
10. ownership enforcement design;
11. proposed tables and constraints;
12. migrations required;
13. expected files to change;
14. decisions requiring review.

Do NOT implement anything.

Stop after the specification and report.

# implement prompt

Implement Phase 09 Employee Portfolio according to:

docs/specs/09-employee-portfolio.md

Current branch:
feature/employee-portfolio

Read the complete specification before changing code.

Do not commit, merge, push, or delete branches.

==================================================
APPROVED DESIGN DECISIONS
==================================================

The Phase 09 specification is approved with these decisions locked.

SKILLS

Use:

skills

- employee_skills

The global Skill master is normalized.

Employee skill fields:

- skill_name at form boundary
- proficiency
- optional years_experience
- optional notes

Allowed proficiency values:

beginner
intermediate
advanced
expert

Do not add percentage proficiency.

Do not add last-used date.

One Employee may have only one association for one Skill.

Database final boundary:

UNIQUE(employee_id, skill_id)

If an active association already exists:

→ duplicate validation/conflict outcome

If an archived association already exists:

→ restore that existing association
→ update submitted proficiency/years/notes
→ clear archived_at
→ status active

Do not insert another association.

==================================================
SKILL TRANSACTION RESPONSIBILITY
==================================================

Clarify the repository design from the specification.

EmployeeSkillService owns business orchestration and validation.

The PDO Skill repository owns the transaction-safe persistence operation for
skill assignment.

The transaction must atomically handle:

1. recheck owning Employee is active;
2. find existing global Skill by normalized name;
3. create Skill if missing;
4. resolve concurrent unique skill-name insertion safely;
5. find employee_skills association;
6. active association -> duplicate outcome;
7. archived association -> restore/update existing row;
8. missing association -> insert;
9. commit.

Do not spread one logical skill assignment transaction across unrelated
controller code.

Do not put SQL in EmployeeSkillService.

Expose a focused repository operation/result if needed rather than creating an
ambiguous insertAssignment API.

Keep repository interfaces explicit and testable.

==================================================
EMPLOYEE STATUS RACE PROTECTION
==================================================

Service-level Employee active checks are required but are NOT the final
concurrency boundary.

Every portfolio mutation must also protect against:

Service checks Employee active
→ concurrent Employee deactivation
→ portfolio write

The persistence operation must recheck Employee active within the write
transaction or use an equivalent atomic database condition.

This applies to:

- skill create/restore
- skill update
- skill archive
- project create
- project update
- project archive
- certification create
- certification update
- certification archive

An inactive Employee must never receive a portfolio mutation.

Do not rely only on hidden UI controls.

==================================================
PROJECTS
==================================================

Implement the specification as approved.

Fields:

- project_name
- role
- start_date
- nullable end_date
- description
- responsibilities
- technologies

NULL end_date means ongoing.

start_date <= end_date when end_date exists.

Overlapping projects are allowed.

Technologies remain historical plain text.

No project-name uniqueness constraint.

Archive instead of physical delete.

Archived projects are read-only in Phase 09.

==================================================
CERTIFICATIONS
==================================================

Fields:

- certification_name
- issuing_organization
- obtained_date
- nullable expiration_date
- nullable credential_identifier
- notes

expiration_date >= obtained_date when expiration exists.

Duplicate boundary:

(employee_id,
certification_name,
issuing_organization,
obtained_date)

Different obtained dates represent renewal/re-certification and are allowed.

An archived exact duplicate may be restored/updated according to the approved
specification.

Do not physically delete certifications.

==================================================
ARCHIVE POLICY
==================================================

Use:

active
archived

for employee portfolio record lifecycle.

Do NOT copy Employee active/inactive terminology onto portfolio records.

Archive:

- employee skill assignment
- project
- certification

Never physically delete these records.

Global skills are retained.

Archived records remain visible in a separate history section.

==================================================
EMPLOYEE LIFECYCLE
==================================================

Active Employee:

- read portfolio
- create
- edit
- archive

Inactive Employee:

- read active + archived portfolio history
- no create
- no edit
- no archive

Direct GET/POST mutation attempts must follow the approved existing HTTP
behavior and must not write data.

Employee deactivation must not automatically modify portfolio rows.

No employee reactivation feature.

==================================================
OWNERSHIP
==================================================

Every child operation must be scoped by BOTH:

employee_id

- child_id

Example:

WHERE id = :child_id
AND employee_id = :employee_id

Employee A's project/skill/certification must never be readable or writable
through Employee B's URL.

Ownership mismatch:

→ 404

Do not disclose the actual owner.

==================================================
DATABASE
==================================================

Create one new migration using the next valid migration version.

Expected tables:

skills
employee_skills
employee_projects
employee_certifications

Use existing project migration conventions:

- BIGINT UNSIGNED IDs
- InnoDB
- utf8mb4
- utf8mb4_unicode_ci
- UTC DATETIME timestamps
- explicit FK names
- ON UPDATE RESTRICT
- ON DELETE RESTRICT
- explicit indexes
- checks where supported

No ON DELETE CASCADE.

Do not modify old migrations.

Do not add portfolio seed data.

Implement down() in reverse dependency order.

==================================================
VALIDATION
==================================================

Implement the exact field limits and date rules from the approved
specification.

Validation must be server-side.

Invalid POST:

→ HTTP 422
→ preserve submitted values
→ localized validation errors

Optional blank strings normalize to NULL.

Use strict YYYY-MM-DD date validation.

Do not accept arbitrary proficiency values.

==================================================
APPLICATION ARCHITECTURE
==================================================

Use:

DTO
→ Validator
→ Application Service
→ Repository Interface
→ PDO Repository

Thin controllers.

No SQL in:

- controllers
- services
- views

No direct DB access in views.

Explicit dependency injection through ApplicationBootstrap.

Do not introduce generic CRUD base classes.

==================================================
ROUTES / SSR
==================================================

Implement the approved nested routes.

Skills:

GET /employees/{employeeId}/skills
GET /employees/{employeeId}/skills/create
POST /employees/{employeeId}/skills
GET /employees/{employeeId}/skills/{skillId}/edit
POST /employees/{employeeId}/skills/{skillId}
GET /employees/{employeeId}/skills/{skillId}/archive
POST /employees/{employeeId}/skills/{skillId}/archive

Projects:

GET /employees/{employeeId}/projects
GET /employees/{employeeId}/projects/create
POST /employees/{employeeId}/projects
GET /employees/{employeeId}/projects/{projectId}
GET /employees/{employeeId}/projects/{projectId}/edit
POST /employees/{employeeId}/projects/{projectId}
GET /employees/{employeeId}/projects/{projectId}/archive
POST /employees/{employeeId}/projects/{projectId}/archive

Certifications:

GET /employees/{employeeId}/certifications
GET /employees/{employeeId}/certifications/create
POST /employees/{employeeId}/certifications
GET /employees/{employeeId}/certifications/{certificationId}
GET /employees/{employeeId}/certifications/{certificationId}/edit
POST /employees/{employeeId}/certifications/{certificationId}
GET /employees/{employeeId}/certifications/{certificationId}/archive
POST /employees/{employeeId}/certifications/{certificationId}/archive

Register routes safely relative to generic Employee parameter routes.

Use existing POST -> 303 -> GET conventions.

==================================================
EMPLOYEE DETAIL
==================================================

Add a compact Professional Portfolio summary to Employee detail.

Show:

- Skill count
- Project count
- Certification count
- links to each resource

Do not turn Employee detail into a large portfolio management page.

Use focused summary/count queries.

==================================================
LOCALIZATION
==================================================

Support EN and JA using the existing Locale/Translator architecture.

Translate:

- headings
- labels
- buttons
- validation messages
- proficiency labels
- lifecycle labels
- empty states
- confirmation text
- inactive read-only notices

Do NOT translate user-entered data:

PHP
Laravel
AWS migration project
certification names
project descriptions
notes
technologies
credential IDs

Reuse existing organization localization if Employee organization context is
shown.

==================================================
SECURITY
==================================================

Use:

- PDO prepared statements
- positive integer route IDs
- bounded inputs
- escaped SSR output
- scoped ownership queries
- server-side Employee lifecycle enforcement

Do not implement Phase 10:

- authentication
- ADMIN/USER
- login
- authorization middleware
- new CSRF architecture

==================================================
TESTS
==================================================

Implement comprehensive tests from the specification.

Include:

UNIT

- all validators
- proficiency allowlist
- years_experience boundaries
- strict dates
- project date ordering
- project overlap allowed
- certification date ordering
- lifecycle behavior
- duplicate mapping
- archived Skill restore

INTEGRATION

- migration up/down
- all four tables
- FKs
- unique employee skill
- certification duplicate boundary
- certification renewal
- overlapping projects
- NULL ongoing end date
- archive preservation
- no physical child deletion
- inactive Employee write protection
- ownership scoping
- Skill transaction behavior

HTTP

- Employee portfolio summary
- lists
- create
- edit
- project/certification detail
- archive confirmations
- POST -> 303
- validation 422
- EN
- JA
- escaping
- inactive Employee read-only UI
- direct inactive mutation rejection
- missing Employee 404
- missing child 404
- Employee A child through Employee B URL -> 404
- duplicate messages

Do not weaken an existing test just to make the suite pass.

If an existing test conflicts with an approved current business rule, report
the conflict before changing the expectation.

==================================================
TEST EXECUTION
==================================================

Run normal:

composer test

Then run DB-backed tests with the project's established test environment.

Report separately:

- test count
- assertion count
- failures
- errors
- skipped
- PHPUnit deprecations

==================================================
DOCUMENTATION
==================================================

Create:

docs/prompts/09-employee-portfolio.md

Preserve:

docs/specs/09-employee-portfolio.md

Do not rewrite the approved spec except for a necessary factual correction.
If a factual correction is necessary, report it.

==================================================
SCOPE CONTROL
==================================================

Do NOT add:

- authentication
- roles
- React/Vue
- REST API
- file uploads
- PDF/resume export
- portfolio analytics
- AI
- project staffing
- global skill administration UI
- employee reactivation
- organization redesign
- portfolio seed data

==================================================
FINAL REPORT
==================================================

After implementation report:

1. migration created;
2. final tables and constraints;
3. DTOs and validators;
4. repository interfaces;
5. PDO repository transaction/concurrency behavior;
6. services;
7. controllers/routes;
8. views;
9. Employee detail portfolio summary;
10. EN/JA localization;
11. archive behavior;
12. inactive Employee enforcement;
13. ownership enforcement;
14. normal test result;
15. DB-backed test result;
16. skipped tests;
17. PHPUnit deprecations;
18. files changed;
19. deviations from the approved specification;
20. anything requiring manual browser verification.

Do NOT commit.
Do NOT merge.
Do NOT push.
Do NOT delete the feature branch.

Stop after implementation and test reporting.
