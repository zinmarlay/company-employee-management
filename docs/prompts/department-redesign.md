We are starting the Department Redesign for the Pure PHP Employee Management System.

Current branch:
feature/department-redesign

IMPORTANT:
Do NOT implement anything yet.
Do NOT modify production code.
Do NOT modify tests yet.
Do NOT create migrations yet.
Do NOT commit, merge, or push.

First inspect the current Department implementation, database schema,
repositories, services, controllers, routes, views, localization, seeder,
tests, and the completed Branch Redesign.

Then create ONLY:

docs/specs/department-redesign.md

==================================================
GOAL
==================================================

Redesign Department creation so users cannot freely type arbitrary department
codes and department names.

A Department belongs to a Branch.

The user selects a department type/code from a centralized Department Catalog.

The application derives the canonical Japanese department name.

Example desired behavior:

東京支店
├── DEV -> 開発部 allowed
├── SALES -> 営業部 allowed
└── DEV -> 開発部 duplicate, rejected

大阪支店
└── DEV -> 開発部 allowed

Therefore department uniqueness is scoped by Branch.

==================================================

1. # DEPARTMENT CATALOG

Design a centralized DepartmentCatalog similar in principle to the completed
PrefectureCatalog, but appropriate for department business data.

Initial proposed department types include concepts such as:

DEV
-> 開発部
-> Development

SALES
-> 営業部
-> Sales

HR
-> 人事部
-> Human Resources

GA
-> 総務部
-> General Affairs

FIN
-> 経理部
-> Accounting

IT
-> 情報システム部
-> Information Systems

However, DO NOT blindly use only this proposed list.

Inspect the existing application, seed data, tests, and organization model.

Propose a practical canonical department catalog for this employee-management
project.

The specification must clearly list the complete initial catalog with:

- canonical code;
- Japanese department name;
- English display label.

Keep the catalog reasonably sized and appropriate for a general Japanese
company employee-management system.

The catalog must be the single source of truth.

Do not duplicate mappings across:

- controller;
- service;
- views;
- translations;
- seeder.

# ================================================== 2. CREATION RULE

Department creation should conceptually accept:

- branch_id
- department_code/type
- description

The application derives the canonical department name from DepartmentCatalog.

Users must NOT freely type department name.

Determine from the current schema whether the existing department code column
can serve directly as the canonical DepartmentCatalog key, as was done with
branches.code in Branch Redesign.

Prefer the smallest compatible design.

# ================================================== 3. UNIQUENESS RULE

For one Branch, only one Department of a given canonical department code may
exist.

Valid:

Tokyo Branch -> DEV
Tokyo Branch -> SALES
Osaka Branch -> DEV

Invalid:

Tokyo Branch -> DEV
Tokyo Branch -> DEV

This uniqueness rule includes inactive historical departments.

If Tokyo Branch already has:

DEV -> 開発部 -> inactive

another DEV department must NOT be created under Tokyo Branch.

The same DEV department is allowed under another branch.

The specification must inspect whether the current database already has an
equivalent constraint such as:

UNIQUE(branch_id, code)

If it exists and is appropriate, prefer using it as the database concurrency
boundary.

If a migration is required, explain why, but do not implement it yet.

# ================================================== 4. PARENT BRANCH RULES

Preserve existing Phase 07 organization rules unless this redesign explicitly
changes them.

Inspect and document:

- whether new departments may be created under inactive branches;
- how active branch choices are currently loaded;
- historical behavior when a parent branch later becomes inactive.

Preferred direction:

- create Department only under an active Branch;
- existing Department remains readable if its Branch later becomes inactive;
- no cascade deactivation;
- no physical deletion.

# ================================================== 5. IMMUTABLE IDENTITY

After Department creation, preferred immutable identity is:

- branch_id
- department code/type
- derived department name

Editable metadata:

- description

Inspect the existing schema and implementation and confirm whether this is
compatible.

A malicious update request must not be able to change:

- branch_id
- department code
- department name
- status

Backend enforcement is mandatory.

Do not rely on disabled/readonly HTML controls.

Preserve the existing Phase 07 behavior regarding whether inactive Department
metadata may still be corrected.

Do not introduce reactivation.

# ================================================== 6. UI

Desired create form direction:

Branch
[ active branch select ]

Department
[ department catalog select ]

Description
[ user-entered metadata ]

Do not show editable free-text Department Code and Department Name inputs.

The selected Department entry determines:

department code
department Japanese canonical name

Edit form should display immutable identity as read-only and allow only
approved metadata editing.

EN/JA localization must remain supported.

# ================================================== 7. SECURITY / MALICIOUS INPUT

The spec must explicitly address malicious request values.

Examples:

department_code = DEV
name = HACKED

must never persist HACKED.

Likewise update requests attempting to change:

branch_id
code
name
status

must not change immutable identity or lifecycle state.

# ================================================== 8. DUPLICATE / CONCURRENCY HANDLING

Application-level duplicate validation alone is not enough.

The specification must define:

- friendly service-level duplicate pre-check;
- database unique constraint as final race boundary;
- safe translation of duplicate-key errors;
- no raw PDO/SQL error exposure.

Duplicate checks must include inactive historical records.

# ================================================== 9. EXISTING DATA / SEEDER

Inspect current seeded Departments.

Existing seed data must remain compatible where possible.

The current project previously used sample departments such as:

開発部
営業部

Do not silently rewrite or delete historical records.

Determine how DevelopmentSeeder should consume DepartmentCatalog instead of
duplicating code/name mappings.

Seeder must remain idempotent and preserve current relationships and fixture
counts unless an approved redesign requires otherwise.

# ================================================== 10. READ / HISTORY

Preserve historical readability.

Inactive departments remain readable.

Departments whose parent branch becomes inactive remain readable.

Existing employee relationships must remain valid.

No physical deletion.

No cascade deactivation.

No reactivation.

# ================================================== 11. ARCHITECTURE

Preserve:

Front Controller
-> Middleware
-> Router
-> Controller
-> Service
-> Repository (PDO)
-> View

Keep:

- Pure PHP;
- PDO;
- explicit dependency injection;
- Composition Root;
- SSR PHP views;
- existing localization infrastructure.

Do not introduce a framework, ORM, SPA, or unrelated abstraction.

# ================================================== 12. TEST PLAN

The specification must include tests for at least:

DepartmentCatalog:

- complete initial catalog size;
- unique canonical codes;
- expected Japanese names;
- expected English labels.

Creation:

- valid catalog departments;
- derived department name;
- arbitrary submitted name cannot override derived name;
- unsupported department code rejected;
- malformed/non-scalar code rejected;
- active branch accepted;
- inactive branch rejected;
- missing branch rejected.

Duplicate rules:

- same department code in same branch rejected;
- inactive department still blocks duplicate;
- same department code in another branch allowed;
- database unique constraint/race protection.

Update:

- branch immutable;
- department code immutable;
- department name immutable;
- status cannot be changed through metadata update;
- description remains editable;
- inactive Department behavior follows existing approved Phase 07 rule.

HTTP/UI:

- create form uses catalog select;
- no editable free-text code/name creation fields;
- validation preserves submitted values;
- successful create uses existing POST -> 303 -> GET flow;
- edit form displays immutable identity;
- EN/JA behavior remains correct.

Repository integration:

- real database uniqueness;
- metadata-only update;
- identity preservation;
- historical readability;
- employee/branch relationships remain valid.

Seeder:

- canonical department mapping;
- idempotency;
- current sample relationships remain intact.

# ================================================== 13. OUT OF SCOPE

Explicitly exclude:

- Branch Redesign (already completed);
- Employee Advanced Search;
- employee code redesign;
- Employee Status Rules;
- authentication redesign;
- reactivation;
- physical deletion;
- audit logging;
- unrelated organization redesign.

# ================================================== 14. SPEC CONTENT

docs/specs/department-redesign.md must include:

1. Purpose
2. Current-state findings
3. Current schema
4. Problems being solved
5. Goals
6. Non-goals
7. Complete proposed DepartmentCatalog
8. Data model strategy
9. Migration decision
10. Business rules
11. Creation behavior
12. Update / immutability behavior
13. Parent Branch rules
14. Repository design
15. Service design
16. Controller behavior
17. Create UI
18. Edit UI
19. Localization
20. Duplicate handling
21. Concurrency/race protection
22. Existing data compatibility
23. Seeder compatibility
24. Historical/read behavior
25. Test plan
26. Expected files to change
27. Acceptance criteria
28. Risks / decisions requiring review

Do NOT implement.

After writing the specification, report:

1. what you inspected;
2. important current-state findings;
3. the proposed complete DepartmentCatalog;
4. whether a migration is required;
5. how uniqueness will be enforced;
6. how existing seed data maps to the catalog;
7. any design decisions or risks that require review.

# implement design

Implement the approved Department Redesign specification:

docs/specs/department-redesign.md

Current branch:
feature/department-redesign

IMPORTANT:

- Implement only the approved specification.
- Do not redesign unrelated modules.
- Do not add a database migration unless an unexpected schema incompatibility
  is discovered. If one is discovered, STOP and report it instead of creating
  a migration.
- Do not commit, merge, or push.
- Preserve the existing Pure PHP architecture.
- Preserve existing Phase 07 historical behavior.
- Run the relevant tests after implementation.

==================================================
APPROVED BUSINESS DESIGN
==================================================

Department identity is catalog-controlled.

Approved initial DepartmentCatalog:

DEV

- Japanese: 開発部
- English: Development

SALES

- Japanese: 営業部
- English: Sales

HR

- Japanese: 人事部
- English: Human Resources

GA

- Japanese: 総務部
- English: General Affairs

FIN

- Japanese: 経理部
- English: Accounting

IT

- Japanese: 情報システム部
- English: Information Systems

LEGAL

- Japanese: 法務部
- English: Legal

PR

- Japanese: 広報部
- English: Public Relations

PL

- Japanese: 企画部
- English: Planning

CS

- Japanese: カスタマーサポート部
- English: Customer Support

DepartmentCatalog must be the single source of truth for this mapping.

Do not duplicate this mapping in:

- controllers;
- services;
- validators;
- views;
- translation files;
- seeder.

==================================================
CREATE BEHAVIOR
==================================================

The create form accepts conceptually:

- branch_id
- department_code
- description

The user does NOT freely enter department name.

The selected department code must be resolved through DepartmentCatalog.

The application must derive both canonical persisted values:

departments.code
departments.name

Example:

department_code = DEV

must persist:

code = DEV
name = 開発部

A malicious request such as:

department_code = DEV
name = HACKED

must still persist:

code = DEV
name = 開発部

The request-provided name must never control persistence.

Unsupported catalog codes must return a friendly validation error.

Malformed/non-scalar department codes must be rejected safely.

New departments require an existing ACTIVE branch.

==================================================
UNIQUENESS
==================================================

Uniqueness is scoped by Branch.

Allowed:

Tokyo -> DEV
Tokyo -> SALES
Osaka -> DEV

Rejected:

Tokyo -> DEV
Tokyo -> DEV

Inactive historical rows also consume the slot.

If:

Tokyo -> DEV -> inactive

exists, another Tokyo -> DEV must be rejected.

Use the existing:

UNIQUE(branch_id, code)

as the final database race/concurrency boundary.

Also keep a friendly service-level duplicate pre-check.

Translate known duplicate-key races to the same friendly validation outcome.

Never expose raw SQL/PDO/index details.

==================================================
UPDATE / IMMUTABILITY
==================================================

After creation, these are immutable:

- branch_id
- department code
- department name
- status

Only description is editable through normal Department update.

The repository update operation must therefore update only:

- description
- updated_at

It must NOT update:

- branch_id
- code
- name
- status

Do not depend on readonly/disabled HTML controls for security.

A malicious POST containing:

branch_id
code
department_code
name
status

must not change those values.

Preserve Phase 07 historical correction behavior:

- inactive Department description may still be corrected;
- Department under inactive Branch may still have description corrected;
- identity remains unchanged;
- status remains unchanged;
- no reactivation.

==================================================
UI
==================================================

Create form:

Branch
[ active branch select ]

Department Type
[ DepartmentCatalog select ]

Description
[ editable metadata ]

Remove editable free-text Department Code and Department Name inputs.

For Japanese locale:
show Japanese catalog names.

For English locale:
show English catalog labels.
It is acceptable to include Japanese name alongside the English label if it
fits the existing UI cleanly.

Edit form:

Display as read-only information:

- Branch
- Department Code
- Department Name
- Status

Editable:

- Description only

Do not use trusted hidden fields for immutable identity.

Preserve submitted branch, department code, and description after create 422.

Preserve submitted description after update 422.

==================================================
LEGACY DATA
==================================================

Do not rewrite or delete unknown historical Department rows.

If an existing historical row has a code that is not currently in
DepartmentCatalog:

- it remains readable;
- its employee relationships remain readable;
- description may still be corrected according to existing rules;
- its identity must not be silently converted;
- it must not be deleted.

==================================================
SEEDER
==================================================

Refactor DevelopmentSeeder so Department identity comes from
DepartmentCatalog.

Existing fixtures must remain:

TOKYO + DEV
-> DEV / 開発部

TOKYO + SALES
-> SALES / 営業部

OSAKA + DEV
-> DEV / 開発部

Preserve:

- existing fixture counts;
- employee relationships;
- branch relationships;
- idempotency.

Do not create duplicate mappings inside the seeder.

==================================================
ARCHITECTURE
==================================================

Preserve:

Front Controller
-> Middleware
-> Router
-> Controller
-> Service
-> Repository(PDO)
-> View

Use explicit dependency injection and the Composition Root.

Prefer clear DTO separation:

DepartmentCreateInput

- branchId
- departmentCode
- description

DepartmentMetadataInput

- description

The exact internal canonical persistence DTO may follow the smallest clean
design compatible with the existing code.

DepartmentService must remain the owner of business rules.

DepartmentCatalog must not access the database.

Controller must remain thin.

==================================================
TESTS
==================================================

Implement comprehensive tests from the approved specification.

At minimum test:

DepartmentCatalog:

- exactly 10 entries;
- unique codes;
- expected Japanese names;
- expected English labels;
- unsupported code behavior.

Create:

- all valid catalog entries;
- derived canonical Japanese name;
- arbitrary submitted name cannot override;
- unsupported code rejected;
- malformed/non-scalar code rejected;
- active branch accepted;
- inactive branch rejected;
- missing branch rejected.

Duplicate:

- same code + same branch rejected;
- inactive existing department still blocks duplicate;
- same code + different branch allowed;
- repository duplicate race safely translated.

Update:

- description editable;
- branch immutable;
- code immutable;
- name immutable;
- status immutable;
- inactive department description correction works;
- inactive parent history remains compatible.

HTTP/UI:

- catalog selector rendered;
- no editable code/name create fields;
- active branch choices;
- validation values preserved;
- POST -> 303 -> GET success;
- edit identity read-only;
- malicious identity update cannot persist;
- EN/JA behavior.

Repository integration:

- UNIQUE(branch_id, code);
- inactive duplicate protection;
- same code different branch;
- metadata-only update;
- identity/status preservation;
- historical readability.

Seeder:

- three existing sample departments preserved;
- catalog-derived identities;
- idempotency;
- existing relationships/counts preserved.

==================================================
DOCUMENTATION
==================================================

Create:

docs/prompts/department-redesign.md

Record the implementation prompt/design intent there consistently with the
project's existing prompt documentation style.

Do not replace or weaken:

docs/specs/department-redesign.md

==================================================
FINAL VERIFICATION
==================================================

After implementation:

1. Run composer test.
2. If DB-backed tests require the explicit test environment and are not
   available in the default run, report that clearly rather than treating
   skips as full DB verification.
3. Report:
   - files added;
   - files modified;
   - whether any migration was added;
   - final DepartmentCatalog;
   - create flow;
   - update/immutability flow;
   - duplicate/race protection;
   - legacy compatibility;
   - seeder changes;
   - test counts/assertions/skips/failures/errors;
   - any PHPUnit deprecations;
   - any deviations from the approved specification.

Do NOT commit.
Do NOT merge.
Do NOT push.
