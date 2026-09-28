We are starting the Branch Redesign for the Pure PHP Employee Management System.

Current branch:
feature/branch-redesign

IMPORTANT:
Do NOT implement anything yet.
Do NOT modify production code.
Do NOT create migrations yet.
Do NOT commit, merge, or push.

First inspect the current Branch implementation, database schema, repositories,
services, controllers, routes, views, localization, seeder, and tests.

Then create ONLY:

docs/specs/branch-redesign.md

## Goal

Redesign Branch creation so users cannot freely type arbitrary branch codes
and branch names.

## Desired direction

1. A branch belongs to a Company.

2. The user selects a Japanese prefecture from all 47 prefectures.

Example:
東京都
大阪府
神奈川県
埼玉県
千葉県
etc.

3. Branch code must be derived from the selected prefecture.

Examples:
東京都 -> TOKYO
大阪府 -> OSAKA
神奈川県 -> KANAGAWA
埼玉県 -> SAITAMA
千葉県 -> CHIBA

The exact canonical code mapping for all 47 prefectures must be defined
centrally and must not be duplicated across controllers/views/services.

4. Branch name must also be derived automatically.

Examples:
東京都 -> 東京支店
大阪府 -> 大阪支店
神奈川県 -> 神奈川支店

The user must NOT freely type branch_code or branch_name during creation.

5. Current business rule:

For one Company, only ONE Branch may exist for a given prefecture.

Valid:
Company A -> 東京都
Company A -> 大阪府
Company B -> 東京都

Invalid:
Company A -> 東京都
Company A -> 東京都

This uniqueness rule includes inactive historical branches.

If Company A already has an inactive Tokyo branch,
a new Tokyo branch must NOT be created.

Reactivation is NOT part of this redesign.

6. Historical data must remain readable.

No physical deletion.

7. Existing organization-management behavior should remain compatible
   unless this redesign explicitly changes it.

8. Branch edit behavior must be carefully designed.

Inspect the existing Phase 07 rule that makes the parent Company immutable.

Decide and document whether prefecture / branch code / branch name should
also be immutable after creation.

Preferred direction:

- Company: immutable
- Prefecture: immutable
- Branch Code: immutable
- Branch Name: derived and immutable
- City / Phone / Address: editable metadata

Explain the reasoning in the spec.

9. Backend validation is mandatory.

Do NOT rely only on HTML select/readonly controls.

A malicious request must not be able to submit an arbitrary prefecture,
branch code, or branch name.

10. Race/concurrency safety must be considered.

Application-level duplicate validation alone is not sufficient.

Inspect the current database schema and determine what database constraint
should enforce:

(company_id, prefecture) UNIQUE

or an equivalent canonical design.

If a migration is required, describe it in the spec but do not implement it yet.

11. Seeder compatibility must be analyzed.

Existing sample branches such as Tokyo and Osaka must remain valid after
the redesign.

12. Localization:
    UI must continue supporting EN / JA.

13. Tests must cover at least:

- all valid prefectures
- derived branch code
- derived branch name
- arbitrary/unsupported prefecture rejected
- duplicate prefecture within same company rejected
- same prefecture allowed for different companies
- inactive existing branch still prevents duplicate creation
- malicious branch_code submission cannot override generated value
- malicious branch_name submission cannot override generated value
- immutable fields cannot be changed through update requests
- editable metadata can still be updated
- database uniqueness/race protection
- existing branch history remains readable

14. Out of scope:

- Department Redesign
- Employee Advanced Search
- reactivation
- authentication redesign
- physical deletion
- employee code redesign

## Architecture

Keep the existing architecture:

Front Controller
-> Middleware
-> Router
-> Controller
-> Service
-> Repository (PDO)
-> View

Keep explicit dependency injection and the Composition Root.

Prefer a centralized domain/application representation for the 47-prefecture
catalog rather than embedding mappings in the view.

## Spec requirements

The spec should include:

- Current-state findings
- Problems being solved
- Goals
- Non-goals
- Business rules
- Complete 47-prefecture canonical mapping strategy
- Data model changes
- Migration strategy if required
- Domain/application design
- Repository changes
- Service rules
- Controller behavior
- Create UI behavior
- Edit UI behavior
- Validation rules
- Duplicate handling
- Concurrency/race-condition protection
- Seeder compatibility
- EN/JA localization changes
- Test plan
- Expected files to change
- Acceptance criteria

Do not implement.

After writing docs/specs/branch-redesign.md,
report:

1. what you inspected,
2. important current-state findings,
3. proposed design,
4. whether a migration is required,
5. any risks or decisions that need review.

# implement prompt

Implement the approved Branch Redesign specification.

Current branch:

feature/branch-redesign

Authoritative specification:

docs/specs/branch-redesign.md

IMPORTANT

---

Read the complete specification before changing code.

Implement ONLY the approved Branch Redesign scope.

Do NOT:

- implement Department Redesign;

- implement Employee Advanced Search;

- add branch reactivation;

- redesign authentication/authorization;

- physically delete branches;

- redesign employee codes;

- add unrelated refactors;

- commit;

- merge;

- push.

No database migration is expected for the approved design.

Architecture must remain:

Front Controller

-> Middleware

-> Router

-> Controller

-> Service

-> Repository (PDO)

-> View

Keep explicit dependency injection and the Composition Root.

==================================================

1. PREFECTURE CATALOG

==================================================

Create a single central PrefectureCatalog in the organization domain/application

area as specified.

It must contain exactly all 47 Japanese prefectures from the approved spec.

Each entry must provide:

- canonical uppercase ASCII code;

- Japanese prefecture label;

- English prefecture label;

- canonical Japanese derived branch name.

Examples:

TOKYO

Japanese label: 東京都

English label: Tokyo

Branch name: 東京支店

OSAKA

Japanese label: 大阪府

English label: Osaka

Branch name: 大阪支店

KANAGAWA

Japanese label: 神奈川県

English label: Kanagawa

Branch name: 神奈川支店

The catalog must be the single source of truth.

Do not duplicate the 47-entry mapping in:

- controllers;

- services;

- views;

- translation files;

- seeders;

- tests except explicit expected assertions where necessary.

The catalog must NOT derive or default city.

==================================================

2. BRANCH CREATION INPUT

==================================================

Redesign creation input so the request supplies:

- company_id

- prefecture_code

- city

- address

- phone

Do NOT trust request-supplied:

- code

- name

- status

- id

- timestamps

The user does not create branch identity manually.

BranchService must resolve prefecture_code through PrefectureCatalog and derive:

prefecture_code -> branches.code

prefecture_code -> branches.name

Example:

prefecture_code = TOKYO

persist:

code = TOKYO

name = 東京支店

If malicious request data contains:

code = HACKED

name = Fake Branch

it must have no effect on persisted branch identity.

==================================================

3. VALIDATION

==================================================

Validate server-side.

Creation must reject:

- missing/invalid company;

- missing prefecture;

- unsupported prefecture code;

- array/non-scalar prefecture input;

- invalid metadata according to existing field limits.

Do not rely on the HTML select.

City remains normal user-entered metadata.

DO NOT:

東京都 -> city = 東京都

automatically.

A valid example is:

Prefecture: 東京都

Code: TOKYO

Name: 東京支店

City: 新宿区

==================================================

4. DUPLICATE BUSINESS RULE

==================================================

One Company may have only one Branch per prefecture.

Valid:

Company A -> TOKYO

Company A -> OSAKA

Company B -> TOKYO

Invalid:

Company A -> TOKYO

Company A -> TOKYO

Inactive history also consumes the prefecture.

Therefore:

Company A -> TOKYO (inactive)

must still prevent creation of another TOKYO branch for Company A.

Perform a service-level pre-check for a friendly validation error.

But preserve the existing database constraint:

UNIQUE(company_id, code)

as the final concurrency/race-condition boundary.

Translate known duplicate-key races into a safe validation outcome.

Do not expose PDO/SQL errors.

==================================================

5. IMMUTABLE BRANCH IDENTITY

==================================================

After creation these are immutable:

- company_id

- prefecture

- code

- name

Only these metadata fields remain editable:

- city

- address

- phone

Repository update SQL must NOT update:

- company_id

- code

- name

- status

Update must only modify approved metadata plus updated_at.

A malicious update request containing:

company_id

prefecture_code

code

name

status

must not change persisted identity or lifecycle state.

Preserve the existing Phase 07 rule:

inactive branches may still receive metadata corrections.

Do not add reactivation.

==================================================

6. CREATE UI

==================================================

Update the branch create form.

Keep:

Company [select]

Replace free-text Code and Name fields with:

Prefecture [select]

The select must contain all 47 prefectures from PrefectureCatalog.

Japanese locale:

show Japanese prefecture labels.

English locale:

show an appropriate English label, optionally with Japanese.

Do not create a second hard-coded mapping in the view.

Keep normal editable inputs:

City

Address

Phone

Do not automatically fill City from Prefecture.

After a 422 validation response:

- preserve selected company;

- preserve selected prefecture;

- preserve city;

- preserve address;

- preserve phone.

Code and name remain server-derived.

==================================================

7. EDIT UI

==================================================

Edit page must clearly display immutable identity:

- Company

- Prefecture

- Branch Code

- Branch Name

These are read-only display values.

Editable:

- City

- Address

- Phone

Backend enforcement must not depend on readonly/disabled/hidden HTML controls.

==================================================

8. READ / HISTORICAL COMPATIBILITY

==================================================

Preserve readable historical branch data.

Inactive branches must remain readable.

Do not physically delete anything.

Existing canonical branches such as:

TOKYO / 東京支店

OSAKA / 大阪支店

must remain compatible.

If an unknown legacy code exists, do not silently rewrite or delete it.

Keep the record readable.

==================================================

9. REPOSITORY

==================================================

Adapt BranchRepositoryInterface and PdoBranchRepository as needed.

Preserve:

UNIQUE(company_id, code)

as the DB uniqueness boundary.

Prefer a metadata-only update operation such as:

updateMetadata(...)

or equivalent.

Use prepared statements.

Duplicate detection must include inactive branches.

Do not add unnecessary repository abstractions.

==================================================

10. SEEDER

==================================================

Update DevelopmentSeeder so Tokyo and Osaka branch identity comes from

PrefectureCatalog instead of duplicating mapping literals.

Preserve:

- existing two sample branches;

- TOKYO / 東京支店;

- OSAKA / 大阪支店;

- current relationships;

- idempotency;

- existing expected fixture counts.

Do not convert this into a general reactivation feature.

==================================================

11. LOCALIZATION

==================================================

Maintain EN / JA support.

Add/update translation keys for:

- Prefecture

- prefecture selection help

- generated branch code/name explanation

- unsupported prefecture

- duplicate prefecture

- immutable identity explanation

- related validation errors

Do not duplicate the 47-prefecture catalog in language files.

==================================================

12. TESTS

==================================================

Implement comprehensive tests required by the approved specification.

Catalog tests:

- exactly 47 entries;

- canonical codes unique;

- expected Japanese labels;

- expected English labels;

- expected branch names;

- TOKYO;

- OSAKA;

- KANAGAWA;

- SAITAMA;

- CHIBA.

Creation/service tests:

- valid prefectures resolve correctly;

- preferably data-provider coverage for all 47;

- derived code;

- derived name;

- submitted city remains unchanged;

- arbitrary request code cannot override;

- arbitrary request name cannot override;

- unsupported prefecture rejected;

- malformed/non-scalar prefecture rejected;

- duplicate same company rejected;

- inactive duplicate rejected;

- same prefecture under another company allowed;

- missing company rejected;

- request status/id/timestamps cannot control persistence.

Update tests:

- company immutable;

- prefecture immutable;

- code immutable;

- name immutable;

- status cannot be changed by metadata update;

- city editable;

- address editable;

- phone editable;

- inactive branch metadata remains editable.

HTTP tests:

- create page renders 47 prefecture options;

- free-text code/name creation inputs are absent;

- 422 preserves submitted values;

- successful creation uses 303;

- derived identity displayed correctly;

- duplicate returns safe validation response;

- edit page shows immutable identity;

- malicious update cannot change identity;

- EN/JA rendering remains correct.

Repository integration:

- same company/code unique constraint;

- inactive branch still blocks duplicate;

- same code allowed for another company;

- metadata-only update preserves identity;

- known duplicate DB error translated safely;

- existing FK relationships remain valid.

Seeder:

- Tokyo/Osaka remain canonical;

- running seeder twice remains idempotent;

- fixture counts and relationships remain correct.

Do not weaken or remove existing tests just to make the suite pass.

If existing tests must change because their expectations represent the old

free-text branch behavior, update them to the approved new behavior.

==================================================

13. VERIFICATION

==================================================

After implementation run:

1. Relevant targeted unit/feature tests.

2. Normal suite:

composer test

3. Full DB-backed suite:

APP_ENV=test \

DB_TEST_HOST=127.0.0.1 \

DB_TEST_PORT=3306 \

DB_TEST_DATABASE=company_employee_management_test \

DB_TEST_USERNAME=root \

DB_TEST_PASSWORD= \

DB_TEST_CHARSET=utf8mb4 \

composer test

If any test fails:

- investigate;

- fix only issues related to this redesign or genuine regressions caused by it;

- rerun the affected tests;

- rerun the full suite.

Do not hide skipped/failing tests.

==================================================

14. DOCUMENTATION

==================================================

Create:

docs/prompts/branch-redesign.md

containing the implementation prompt / implementation record consistent with

the project's existing docs/prompts convention.

Do not overwrite:

docs/specs/branch-redesign.md

except for a small clarification if implementation discovers a genuine

specification contradiction. If that happens, report it explicitly.

==================================================

15. FINAL REPORT

==================================================

Do NOT commit, merge, or push.

When complete report:

1. files added;

2. files modified;

3. final PrefectureCatalog design;

4. create flow;

5. immutable update behavior;

6. duplicate/concurrency handling;

7. seeder changes;

8. localization changes;

9. tests added/updated;

10. targeted test results;

11. normal composer test result;

12. DB-backed full test result;

13. any skipped tests;

14. PHPUnit deprecations;

15. whether any migration was added;

16. git status;

17. any remaining risks or manual browser checks recommended.
