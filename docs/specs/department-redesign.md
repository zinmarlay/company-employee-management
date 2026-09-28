# Department Redesign Specification

## 1. Purpose

This specification redesigns department creation around a fixed, centralized
catalog of supported department types. Users select a department type for a
branch; the application derives the canonical department code and Japanese
department name. Users may maintain the existing optional description, but may
not invent department identity values.

This document is a design specification only. It does not implement
production code, modify tests, add migrations, or commit changes.

## 2. Current-state findings

The inspection covered:

- `docs/specs/07-organization-management.md`;
- the completed Branch Redesign specification and its `PrefectureCatalog`;
- `database/migrations/Version20260922000300CreateDepartments.php`;
- `DepartmentInput`, `DepartmentInputValidator`, and
  `DepartmentValidationResult`;
- `DepartmentRepositoryInterface`, `PdoDepartmentRepository`, and
  `PdoDepartmentReadRepository`;
- `DepartmentService` and `DepartmentController`;
- department routes, SSR views, layout/navigation partials, and EN/JA
  translation resources;
- `DevelopmentSeeder` and its integration test;
- organization validator, service, HTTP, and repository integration tests;
- `EmployeeService` and department read dependencies.

The application remains Pure PHP with the existing front controller,
middleware, custom GET/POST router, SSR PHP views, application services,
explicit repository interfaces, PDO repositories, and composition root. The
redesign must preserve those boundaries.

### 2.1 Current department implementation

The current implementation:

1. accepts arbitrary `code` and `name` values in
   `DepartmentInputValidator`;
2. renders editable code and name text inputs in
   `resources/views/departments/_form.php`;
3. passes both request values through `DepartmentService`;
4. inserts and updates both identity fields in `PdoDepartmentRepository`;
5. checks duplicate codes by branch, but has no department catalog;
6. hard-codes `DEV -> 開発部` and `SALES -> 営業部` in
   `DevelopmentSeeder`;
7. already prevents creation under an inactive branch;
8. currently allows existing department metadata updates even when the
   department or its parent branch is inactive; this is superseded by the
   reviewed inactive-record rule in this specification;
9. preserves department and employee history through conditional
   deactivation rather than deletion.

### 2.2 Existing parent and read behavior

`DepartmentService::createForm()` loads active branches for new department
choices. `editForm()` starts with active branches and adds the current branch
when it is inactive so an existing department remains understandable in
history/detail views; inactive departments are not editable.

On creation, `DepartmentService` requires the selected branch to exist and be
active. On update, it requires the original branch to remain the parent but
does not require that branch to remain active. This distinction must remain:
new departments require active parents; existing department history remains
readable, while editing requires both an active department and an active
parent branch.

`PdoDepartmentRepository` joins departments to branches and companies for
management/detail reads, and joins employees for employee counts and detail
history. `PdoDepartmentReadRepository` exposes only active departments to
new employee choices. Existing employee records continue to resolve their
historical department through the employee/department relationship.

### 2.3 Existing seed data

The development seeder currently creates these department identities:

| Branch key | Code | Japanese name | Employees/relationships |
| --- | --- | --- | --- |
| `TOKYO` | `DEV` | `開発部` | Two sample employees |
| `TOKYO` | `SALES` | `営業部` | One sample employee |
| `OSAKA` | `DEV` | `開発部` | One sample employee |

These are valid entries in the proposed catalog. Their codes, names, branch
keys, employee relationships, and fixture counts must remain unchanged.

## 3. Problems being solved

The current form and service allow a request such as:

```text
code = DEV
name = HACKED
```

to persist a mismatched identity. The same problem exists on update, where a
request can attempt to change `branch_id`, `code`, `name`, or status through
fields that the repository currently writes.

The redesign must establish one source of truth for department identity,
prevent unsupported department types, retain branch-scoped uniqueness, and
keep historical records readable without changing the existing organization
schema.

## 4. Goals

The redesign must:

1. add one central catalog for the complete initial department set;
2. derive the Japanese department name from the selected catalog code;
3. prevent arbitrary or unsupported department codes at the service boundary;
4. prevent submitted names from overriding catalog-derived names;
5. preserve one department of a given code per branch, including inactive
   history;
6. keep department identity immutable after creation;
7. keep description editable only for an active department under an active
   branch;
8. allow creation only under an active existing branch;
9. preserve inactive department and inactive-parent branch history;
10. preserve employee relationships and active department choices;
11. retain EN/JA server-rendered behavior and existing HTTP conventions;
12. use the existing database unique key as the final concurrency boundary.

## 5. Non-goals

This redesign does not include:

- Branch Redesign, which is already completed;
- Employee Advanced Search;
- employee code redesign;
- Employee Status Rules;
- authentication or authorization redesign;
- branch or department reactivation;
- physical deletion;
- audit logging or status history;
- department reparenting between branches;
- employee or department relationship redesign;
- company CRUD or company lifecycle management;
- a REST/JSON API, SPA, ORM, generic CRUD framework, or query builder;
- unrelated organization refactors.

## 6. Proposed DepartmentCatalog

### 6.1 Single source of truth

Add one central catalog, recommended at:

```text
src/Domain/Organization/DepartmentCatalog.php
```

The catalog owns the complete mapping and exposes read-only operations such
as:

- `all()`;
- `find(string $code)`;
- `contains(string $code)`;
- `departmentName(string $code)`;
- `label(string $code, string $locale)`.

The exact PHP API may use immutable value objects, but controllers, services,
validators, views, and seeders must not duplicate the mapping. Translation
resources must provide UI concepts and validation messages; catalog data must
provide the department labels.

The canonical code is an uppercase ASCII key and fits the existing
`departments.code VARCHAR(30)` column. The canonical stored name is Japanese
business data. Locale selection changes display labels only; it does not
change persisted department identity.

### 6.2 Complete initial catalog

The initial catalog is intentionally small enough for a general company
employee-management system while covering common corporate functions and
preserving every existing seed identity:

| # | Canonical code | Japanese department name | English display label |
| ---: | --- | --- | --- |
| 1 | `DEV` | 開発部 | Development |
| 2 | `SALES` | 営業部 | Sales |
| 3 | `HR` | 人事部 | Human Resources |
| 4 | `GA` | 総務部 | General Affairs |
| 5 | `FIN` | 経理部 | Accounting |
| 6 | `IT` | 情報システム部 | Information Systems |
| 7 | `LEGAL` | 法務部 | Legal |
| 8 | `PR` | 広報部 | Public Relations |
| 9 | `PL` | 企画部 | Planning |
| 10 | `CS` | カスタマーサポート部 | Customer Support |

This list is the complete initial catalog, not an open-ended free-text
validation list. Adding a new department type requires a reviewed catalog
change and corresponding test/fixture review.

The catalog must not own branch-specific data, descriptions, status, or
employee assignments. It owns only department identity and display labels.

### 6.3 Code normalization and lookup

Catalog lookup may trim and normalize ASCII code case before resolution, but
the persisted value must always be the canonical uppercase catalog key. A
value that does not resolve to one of the ten catalog entries is unsupported,
even if it fits the database column.

## 7. Data model strategy and migration decision

### 7.1 Reuse existing department identity columns

No database migration is required for the approved design.

The existing columns already represent the required canonical model:

- `departments.code` becomes the persisted DepartmentCatalog key;
- `departments.name` becomes the catalog-derived Japanese name;
- `departments.description` remains nullable editable metadata;
- `departments.status` remains an application-controlled lifecycle field;
- `departments.branch_id` remains the immutable branch parent.

The existing database constraint:

```sql
UNIQUE (branch_id, code)
```

is exactly the required branch-scoped department uniqueness boundary. It
includes inactive rows because the constraint has no status predicate.

The existing foreign keys and composite relationship protections remain
unchanged. The redesign must not add a redundant `department_type` or
`department_code` column alongside `code`.

### 7.2 Existing data compatibility

The current accepted project data contains `DEV`/`開発部` and
`SALES`/`営業部`, so it is compatible without a backfill. Existing rows with
unknown legacy codes or names must remain readable and must not be silently
deleted or rewritten. A separate reviewed data-normalization decision would
be required before treating an unknown row as a selectable catalog
department.

The redesigned write path must preserve unknown historical rows for reads and
employee relationships. If an unknown row is opened for edit, only its
description may be corrected; the service must not infer or overwrite its
identity.

### 7.3 Rejected schema alternatives

Adding a separate `department_type` column while retaining `code` would
duplicate identity and require backfilling and reconciliation. Replacing the
existing code would break employee read models, seed keys, and the existing
unique index. Reusing `code` is the smallest compatible design.

## 8. Business rules

### 8.1 Creation

Creating a department requires:

- an existing branch;
- an active parent branch;
- one supported `DepartmentCatalog` code;
- an optional description.

The service resolves the selected catalog entry and derives:

```text
department code -> departments.code
department code -> departments.name
```

For example:

```text
DEV   -> DEV   -> 開発部
SALES -> SALES -> 営業部
HR    -> HR    -> 人事部
```

New departments are always created with `active` status. Request fields for
`id`, `name`, `status`, `created_at`, or `updated_at` cannot control
persistence.

If the branch already has an active or inactive row with the canonical code,
creation is rejected. The same canonical code is valid on another branch.

### 8.2 Parent Branch rules

The existing Phase 07 rules remain:

- a new department requires a branch that exists and is active;
- inactive branches are excluded from the create-form branch selector;
- a department whose parent later becomes inactive remains readable;
- a department under an inactive branch is not reparented automatically;
- no cascade deactivation occurs;
- no new department may be created under an inactive branch;
- branch identity and department identity are not changed by this redesign.
- an inactive department is detail/history-only;
- an active department under an inactive branch is detail/history-only;
- inactive departments and departments under inactive branches cannot be
  edited, updated, or deactivated.

The service must perform the active-parent check even if a malicious request
submits an inactive branch ID not present in the form.

### 8.3 Editing and immutability

After creation, these fields are immutable:

- `branch_id`;
- department code/type;
- derived department name;
- `status`.

Only `description` is editable through the department metadata update use
case. Update SQL must not include `branch_id`, `code`, `name`, or `status`.

Only an active department whose parent branch is also active may receive a
description update. Inactive departments and departments under inactive
branches remain readable, but direct edit/update/deactivate attempts are
blocked and cannot change metadata, status, or identity.

Identity values submitted in a malicious update request must be ignored or
rejected at the service boundary. The recommended behavior is to ignore
identity fields and reconstruct identity from the current database row, while
validating only the description field for the metadata update.

### 8.4 Deactivation and history

Deactivation remains a conditional status update:

```sql
UPDATE departments
SET status = 'inactive', updated_at = :updated_at
WHERE id = :id AND status = 'active'
```

It does not delete departments or employees. Repeated deactivation is an
idempotent no-op. Reactivation is not added.

An inactive department continues to be readable in management lists, detail
pages, employee details, and historical relationships. It remains excluded
from new employee department choices.

## 9. Domain and application design

### 9.1 Catalog dependency

`DepartmentService` receives `DepartmentCatalog` through explicit dependency
injection. The composition root wires the same catalog used by the form,
service, and seeder. The catalog has no runtime state and no database access.

The catalog, not the controller or view, determines whether a code is
supported and what Japanese name it derives.

### 9.2 Input models

The existing `DepartmentInput` currently contains branch ID, arbitrary code,
arbitrary name, and description. It should be split or narrowed so raw request
data cannot construct canonical identity directly.

Recommended models:

- `DepartmentCreateInput`: `branchId`, `departmentCode`, `description`;
- `DepartmentMetadataInput`: `description`;
- an internal canonical persistence shape containing `branchId`, derived
  `code`, derived `name`, and description.

If retaining the current `DepartmentInput` name is materially smaller, it may
remain the canonical repository write shape, but it must be constructed only
by `DepartmentService` after catalog resolution. It must not be constructed
directly from request `name` or arbitrary request `code`.

### 9.3 Validator

The create validator performs structural validation for:

- positive scalar `branch_id`;
- scalar bounded `department_code`;
- optional scalar description, trimmed and normalized to `NULL` when blank.

The service performs catalog support validation after structural validation.
The validator must not accept arbitrary department name as an identity input.

The update validator validates only description. It must preserve submitted
description for 422 responses and must not require or trust hidden identity
fields.

Non-scalar code values such as arrays are rejected. A syntactically valid but
unsupported scalar such as `HACKED` is rejected by catalog resolution.

## 10. Repository design

The repository contract remains focused on department management. The
preferred operations are:

- `listManagement(int $limit): array`;
- `listActive(): array`;
- `listByBranchId(int $branchId): array`;
- `listEmployeesByDepartment(int $departmentId): array`;
- `findById(int $id): ?array`;
- `codeExists(int $branchId, string $canonicalCode, ?int $exceptId = null): bool`;
- `insert(canonicalDepartmentInput, string $createdAt, string $updatedAt): int`;
- `updateMetadata(int $id, DepartmentMetadataInput $input, string $updatedAt): bool`;
- `deactivate(int $id, string $updatedAt): bool`.

Keeping `codeExists` is acceptable because `code` is now the canonical catalog
key. A `departmentTypeExists` alias is unnecessary if it would duplicate the
same SQL.

The PDO repository must:

- keep `branch_id`, `code`, `name`, and `status` out of metadata update SQL;
- preserve current explicit read columns and joins;
- use prepared statements for all request values;
- retain the branch-scoped duplicate check including inactive rows;
- catch the existing unique-key conflict and translate it to
  `DepartmentDuplicateException`;
- leave status unchanged during description updates;
- preserve employee counts and branch/company context in read models.

## 11. Service design

`DepartmentService` owns the use-case rules:

### 11.1 Create flow

1. Structurally validate branch ID, department code, and description.
2. Resolve the branch by ID.
3. Reject a missing branch.
4. Reject an inactive branch.
5. Resolve the department code through `DepartmentCatalog`.
6. Reject unsupported codes.
7. Derive the canonical Japanese name.
8. Pre-check `(branch_id, canonical_code)` including inactive rows.
9. Construct canonical persistence input and force active status through the
   repository insert contract.
10. Translate a unique-key race to the same friendly duplicate validation
    outcome.

### 11.2 Update flow

1. Load the current department by ID.
2. Return the existing 404 outcome when it does not exist.
3. Validate only description metadata.
4. Reconstruct immutable branch/code/name/status from the current row.
5. Ignore or reject submitted `branch_id`, `code`, `name`, and `status`.
6. Update only description and `updated_at`.
7. Preserve inactive status and inactive-parent history.

Unexpected PDO errors continue to reach the centralized 500 responder. Known
duplicate errors must not expose SQL, index names, or raw PDO messages.

## 12. Controller behavior

Existing routes and HTTP conventions remain:

| Method | Path | Behavior |
| --- | --- | --- |
| GET | `/departments` | List active and inactive departments |
| GET | `/departments/create` | Create form |
| POST | `/departments` | Create; 303 to detail on success |
| GET | `/departments/{id}/edit` | Edit form |
| POST | `/departments/{id}` | Description update |
| GET | `/departments/{id}` | Detail/history read |
| GET | `/departments/{id}/deactivate` | Non-mutating confirmation |
| POST | `/departments/{id}/deactivate` | Conditional deactivation |

Static and action routes remain before `/departments/{id}`. Invalid IDs and
missing records retain the current 404 behavior. Successful writes retain
POST -> 303 -> GET.

`DepartmentController` remains thin. It parses the request body, passes it to
the service, selects a view or redirect, and maps known outcomes to 200, 303,
422, or 404. It must not derive department names, duplicate catalog entries,
or trust readonly/hidden identity fields.

## 13. Create UI

The create form must:

- show an active branch selector sourced from `DepartmentService`;
- show one required department catalog selector;
- render exactly the catalog entries from `DepartmentCatalog`;
- show Japanese department names in JA;
- show English display labels in EN, optionally alongside Japanese names;
- keep description as the only user-entered department metadata field;
- not render editable department code or department name inputs;
- preserve submitted branch, catalog code, and description after 422;
- use translated labels, instructions, and errors.

The server must derive and persist code/name even if a malicious request adds
`code` or `name` fields. Those fields must never override catalog output. No
JavaScript is required for correctness; an optional preview must consume
prepared catalog data and must not duplicate the mapping.

## 14. Edit UI

The edit form must:

- display branch, department code/type, and derived Japanese name as
  read-only values;
- provide only the description control for editing;
- avoid trusted hidden inputs for branch/code/name/status;
- show inactive department status clearly;
- show an inactive parent branch clearly when applicable;
- preserve submitted description and render validation errors safely.

The service remains authoritative if a request adds or alters hidden identity
fields. Inactive department records and departments under inactive branches do
not render an edit form; their detail/history views remain understandable
without offering inactive branches as new create choices.

## 15. Localization

Use the existing `resources/lang/en.php`, `resources/lang/ja.php`, and
`Translator` infrastructure.

Add or update UI/message keys for:

- department type/catalog selection;
- derived department code/name read-only labels;
- unsupported department type;
- duplicate department type within a branch;
- immutable department identity;
- inactive branch create rejection;
- description validation and retained history notices.

Suggested concepts:

| Concept | English | Japanese |
| --- | --- | --- |
| Department type | Department type | 部署種別 |
| Derived name | Department name is generated from the department type. | 部署名は部署種別から自動生成されます。 |
| Duplicate | This branch already has a department of this type. | この支店には選択した部署種別の部署がすでにあります。 |
| Active parent | Select an active branch. | 有効な支店を選択してください。 |
| Immutable identity | The branch, department code, department name, and status cannot be changed here. | 支店、部署コード、部署名、ステータスはここでは変更できません。 |

The ten catalog data labels must come from `DepartmentCatalog`, not be
duplicated in language files.

## 16. Duplicate handling and concurrency

The service performs a friendly duplicate pre-check using:

```text
codeExists(branch_id, canonical_code, except_id)
```

The check must not filter by department status, so inactive history consumes
the code slot.

The database unique key remains the final race boundary:

```sql
UNIQUE (branch_id, code)
```

Two concurrent creates for the same branch and type may both pass the
pre-check, but only one insert may succeed. `PdoDepartmentRepository` catches
the known duplicate-key failure and translates it to
`DepartmentDuplicateException`. The service maps both pre-check and race
outcomes to the same field-level 422 message without exposing SQL or PDO
details.

No application transaction is required around the pre-check and insert; the
existing unique index provides the atomic boundary.

## 17. Existing data and seeder compatibility

`DevelopmentSeeder::upsertDepartments()` must consume `DepartmentCatalog` for
the three existing identities:

```text
TOKYO:DEV   -> catalog DEV   -> 開発部
TOKYO:SALES -> catalog SALES -> 営業部
OSAKA:DEV   -> catalog DEV   -> 開発部
```

The seeder may retain branch-specific fixture keys and descriptions, but must
not duplicate department code/name literals as an independent mapping source.
It should resolve each code through the catalog, derive the name, and continue
to look up rows by `branch_id` and canonical code.

Seeder behavior must remain:

- exactly three sample departments;
- idempotent across repeated runs;
- active sample departments according to the current fixture contract;
- unchanged branch and employee relationships;
- unchanged employee and department counts;
- no creation of a second row when an inactive historical row already uses
  the same branch/code.

Seeder normalization of its own fixture is not an application reactivation
feature. Unknown historical rows outside the sample fixture remain untouched
and readable.

## 18. Historical and read behavior

Department list/detail reads continue to expose inactive departments. Detail
views should continue to show:

- company code/name;
- branch code/name and branch status;
- canonical department code;
- derived or stored department name;
- department status;
- description;
- timestamps;
- employee count and related employees.

If the department code is known to the catalog, the read path may resolve its
catalog labels. Unknown legacy rows remain readable using their stored code
and name and must be reported as a compatibility concern rather than
silently rewritten.

Departments whose parent branch later becomes inactive remain readable. No
cascade deactivation, physical deletion, employee reassignment, or
reactivation is introduced.

`PdoDepartmentReadRepository::listActive()` continues to provide only active
departments for new employee assignment choices. Existing employee detail and
history reads continue to resolve inactive departments.

## 19. Test plan

### 19.1 DepartmentCatalog unit tests

Add focused tests for:

- exactly ten initial entries;
- every code being unique and uppercase;
- every code resolving to the expected Japanese name;
- every code resolving to the expected English label;
- `DEV`, `SALES`, `HR`, `GA`, `FIN`, and `IT` examples;
- unsupported code rejection;
- scalar normalization and non-scalar request rejection at the validator
  boundary.

### 19.2 Validator and service tests

Cover:

- valid creation for every catalog entry;
- canonical Japanese name derivation;
- submitted arbitrary name cannot override the derived name;
- unsupported and malformed codes are rejected;
- active branch creation succeeds;
- inactive or missing branch creation fails;
- duplicate code in the same branch fails;
- inactive existing department still blocks a duplicate;
- same code on another branch succeeds;
- `branch_id`, code, name, and status cannot change on update;
- description updates succeed;
- inactive department update is rejected without changing description or
  status;
- update under an inactive parent is rejected without changing description,
  status, or parent identity;
- duplicate repository race becomes a safe validation failure;
- UTC timestamps continue to use the existing clock.

### 19.3 HTTP and UI tests

Extend organization HTTP coverage to verify:

- create form renders the complete catalog selector;
- create form uses active branch choices;
- create form has no editable code/name inputs;
- malicious submitted name/code do not persist;
- unsupported type returns 422 and preserves submitted values;
- valid creation returns 303 and stores derived identity;
- duplicate and inactive-parent errors return 422;
- edit form renders branch/code/name/status as read-only;
- update requests cannot change branch, code, name, or status;
- description update returns 303;
- inactive department and inactive-parent history remain readable;
- EN/JA labels and validation messages are correct;
- existing route 404/405 behavior remains unchanged.

### 19.4 Repository integration tests

Against the isolated MySQL/MariaDB test database, verify:

- all catalog codes fit the existing column;
- `(branch_id, code)` rejects duplicates in the same branch;
- duplicates remain rejected after the original department is inactive;
- the same code succeeds under another branch;
- metadata update changes description only;
- branch/code/name/status remain unchanged after metadata update;
- active/inactive parent foreign keys remain valid;
- employee relationships and counts remain valid;
- known duplicate-key exceptions are translated;
- sequential/concurrent duplicate attempts cannot create two rows;
- unknown legacy-shaped rows remain readable;
- no migration is added for this design.

### 19.5 Seeder regression tests

Verify that:

- the existing three department rows remain present;
- `DEV`/`開発部` and `SALES`/`営業部` resolve through the catalog;
- running the seeder twice is idempotent;
- branch and employee relationships remain unchanged;
- current fixture counts remain unchanged;
- unrelated records remain untouched.

## 20. Expected files to change

Expected production changes:

- `src/Domain/Organization/DepartmentCatalog.php`;
- `src/Application/DTO/DepartmentInput.php` or new create/metadata DTOs;
- `src/Application/Validation/DepartmentInputValidator.php`;
- `src/Application/Validation/DepartmentValidationResult.php` and a metadata
  validation result if needed;
- `src/Application/Organization/DepartmentService.php`;
- `src/Domain/Organization/DepartmentRepositoryInterface.php`;
- `src/Domain/Organization/DepartmentDuplicateException.php` if wording is
  updated;
- `src/Infrastructure/Persistence/PdoDepartmentRepository.php`;
- `src/Http/Controllers/DepartmentController.php` only if service outcomes
  require mapping changes;
- `resources/views/departments/_form.php`;
- `resources/views/departments/show.php` or `index.php` only if catalog/read
  display is added;
- `resources/lang/en.php`;
- `resources/lang/ja.php`;
- `src/Database/Seed/DevelopmentSeeder.php`;
- organization unit, HTTP, repository integration, and seeder tests;
- a new DepartmentCatalog unit-test file.

`routes/web.php` and `src/Bootstrap/ApplicationBootstrap.php` should change
only if explicit catalog dependency wiring requires it.

No migration file is expected. No Branch, Employee, or Dispatch schema file
should change.

## 21. Acceptance criteria

The redesign is complete only when:

1. A central catalog contains exactly the ten approved initial department
   types.
2. Every catalog entry has one stable code, Japanese name, and English label.
3. Controllers, views, services, validators, and seeders do not duplicate the
   catalog mapping.
4. Department creation uses catalog selection rather than free-text identity
   fields.
5. Unsupported or non-scalar department codes are rejected server-side.
6. Department name is always derived from the selected catalog code.
7. Malicious submitted code/name values cannot override canonical identity.
8. A department may be created only under an existing active branch.
9. One branch cannot create the same catalog department twice.
10. An inactive department still prevents a duplicate in its branch.
11. The same catalog department may be created under another branch.
12. The existing `(branch_id, code)` unique key remains the concurrency
    boundary.
13. Branch, department code, department name, and status cannot be changed by
    metadata updates.
14. Description remains editable only for an active department under an
    active branch; inactive records remain detail/history-only.
15. Inactive departments and departments under inactive branches remain
    readable.
16. No cascade deactivation, reactivation, or physical deletion is added.
17. Existing `TOKYO:DEV`, `TOKYO:SALES`, and `OSAKA:DEV` seed identities remain
    valid and idempotent.
18. Existing employee relationships and fixture counts remain compatible.
19. EN/JA UI, errors, and notices remain supported.
20. Unit, HTTP, repository integration, and seeder tests cover the required
    behavior.
21. No database migration is required for the approved design.
22. Branch Redesign, Employee Advanced Search, employee code redesign,
    Employee Status Rules, authentication redesign, reactivation, audit
    logging, and unrelated organization redesign remain excluded.

## 22. Risks and decisions requiring review

### 22.1 Catalog scope and future additions

The ten-entry catalog is a reviewed initial business set, not a claim that
every organization uses the same departments. Adding entries is a catalog and
fixture change. Removing or renaming an entry after data exists requires a
separate compatibility decision so historical rows remain readable.

### 22.2 Canonical code as department type identity

This design deliberately uses the existing `departments.code` value as the
catalog key. It avoids redundant schema state and preserves the current
unique index, but future requirements for an independent free-form department
code would require a new reviewed data model rather than silently adding a
second identity column.

### 22.3 Legacy arbitrary departments

The prior implementation permitted arbitrary code/name pairs. Unknown legacy
rows must remain readable and must not be silently normalized or deleted. A
separate migration or compatibility policy is required before those rows can
be treated as canonical catalog departments.

### 22.4 Inactive metadata correction

The reviewed decision is that inactive departments, and active departments
whose parent branch is inactive, are detail/history-only. Description updates
are allowed only while both the department and parent branch are active.
This prevents historical records from being modified while preserving all
existing reads and relationships.

### 22.5 Seeder normalization versus reactivation

The development seeder currently expects its sample departments to be active.
It may normalize its own fixture in an idempotent seed run, but that behavior
must not become a production reactivation endpoint or alter unrelated
historical rows.

### 22.6 Parent branch availability

The create form intentionally shows active branches only, and edit forms are
available only for active departments under active branches. Detail/history
views continue to resolve inactive parent relationships without exposing
mutation actions.

## 23. Explicit exclusions confirmation

This specification does not redesign branches, employees, employee codes,
employee status rules, authentication, deletion, reactivation, audit
logging, or unrelated organization behavior.
