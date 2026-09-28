# Branch Redesign Specification

## 1. Purpose

This specification redesigns branch creation around a fixed catalog of all 47
Japanese prefectures.

Users select a prefecture. The application derives the canonical branch code
and branch name. Users may still maintain branch metadata such as city,
address, and phone, but they may not invent or change the branch identity.

This is a specification-only document. It does not implement production code,
modify tests, add migrations, or commit changes.

## 2. Current-state findings

The inspection covered:

- docs/specs/07-organization-management.md;
- database/migrations/Version20260922000200CreateBranches.php;
- BranchInput, BranchInputValidator, and BranchValidationResult;
- BranchRepositoryInterface and PdoBranchRepository;
- BranchService and BranchController;
- branch routes, views, layouts, navigation, and translation resources;
- DevelopmentSeeder and its integration test;
- organization HTTP, service, validator, and repository integration tests;
- EmployeeService's branch read dependency and employee form behavior.

The current architecture is Pure PHP with the existing front controller,
middleware pipeline, custom GET/POST router, SSR PHP views, application
services, explicit repository interfaces, PDO repositories, and a composition
root. It must be preserved.

### 2.1 Current schema

The existing branches table contains:

- id;
- company_id;
- code;
- name;
- city;
- address;
- phone;
- status;
- created_at;
- updated_at.

It has:

- a foreign key from company_id to companies.id;
- UNIQUE KEY uq_branches_company_code (company_id, code);
- a status check allowing active and inactive.

There is no separate prefecture column.

### 2.2 Current implementation gaps

The current implementation:

1. accepts arbitrary code and name values in BranchInputValidator;
2. renders editable code and name inputs in resources/views/branches/_form.php;
3. passes code/name from the request through BranchService;
4. updates code and name in PdoBranchRepository::update();
5. checks duplicate code, but has no prefecture catalog or prefecture
   validation;
6. hard-codes the Tokyo and Osaka code/name mapping in DevelopmentSeeder;
7. tests scoped code uniqueness, but do not test all prefectures, derived
   identity, or immutable branch identity.

Phase 07 already makes company_id immutable during branch updates. The
redesign extends the same historical-integrity principle to prefecture, code,
and derived name.

### 2.3 Existing valid seed data

The current development seeder creates:

| Code | Name | City |
| --- | --- | --- |
| TOKYO | 東京支店 | 東京都 |
| OSAKA | 大阪支店 | 大阪府 |

These values are valid catalog entries and must remain compatible.

## 3. Goals

The redesign must:

1. provide a central, complete 47-prefecture catalog;
2. let users select a prefecture rather than type branch identity fields;
3. derive branch code from the selected prefecture;
4. derive branch name from the selected prefecture;
5. prevent arbitrary or unsupported prefectures at the service boundary;
6. preserve one branch per company and prefecture, including inactive history;
7. make company, prefecture, code, and derived name immutable after creation;
8. keep city, address, and phone editable as metadata;
9. preserve readable historical branch records and existing employee
   relationships;
10. retain English/Japanese SSR behavior and existing HTTP conventions;
11. protect duplicate creation with a database uniqueness boundary.

## 4. Non-goals

This redesign does not include:

- Department Redesign;
- Employee Advanced Search;
- branch reactivation;
- company CRUD or company lifecycle management;
- authentication or authorization redesign;
- physical branch deletion;
- status history or audit logging;
- employee code redesign;
- branch reparenting between companies;
- changing employee or department relationship semantics;
- a REST API, SPA, ORM, generic CRUD framework, or query builder.

## 5. Canonical prefecture catalog

### 5.1 Single source of truth

Add one domain/application catalog, recommended at:

src/Domain/Organization/PrefectureCatalog.php

The catalog owns the complete mapping and exposes read-only operations such as:

- all();
- find(string $code);
- contains(string $code);
- branchName(string $code);
- label(string $code, string $locale).

The exact PHP API may use immutable value objects instead of arrays, but there
must be one mapping source. Controllers, services, views, validators, and the
seeder must not duplicate the 47-entry mapping.

The persisted canonical code is the catalog key. It is uppercase ASCII,
stable, and suitable for the existing branches.code VARCHAR(30) column.

The canonical stored branch name is Japanese and ends in 支店. English UI
labels may use the catalog's English prefecture label, but locale selection
must not change persisted branch identity.

The catalog does not own or provide a city default. Prefecture and city are
different concepts: the selected prefecture determines branch identity, while
city is user-entered, editable metadata.

### 5.2 Complete mapping

The catalog must contain exactly these 47 entries:

| # | Prefecture | English label | Canonical code | Derived branch name |
| ---: | --- | --- | --- | --- |
| 1 | 北海道 | Hokkaido | HOKKAIDO | 北海道支店 |
| 2 | 青森県 | Aomori | AOMORI | 青森支店 |
| 3 | 岩手県 | Iwate | IWATE | 岩手支店 |
| 4 | 宮城県 | Miyagi | MIYAGI | 宮城支店 |
| 5 | 秋田県 | Akita | AKITA | 秋田支店 |
| 6 | 山形県 | Yamagata | YAMAGATA | 山形支店 |
| 7 | 福島県 | Fukushima | FUKUSHIMA | 福島支店 |
| 8 | 茨城県 | Ibaraki | IBARAKI | 茨城支店 |
| 9 | 栃木県 | Tochigi | TOCHIGI | 栃木支店 |
| 10 | 群馬県 | Gunma | GUNMA | 群馬支店 |
| 11 | 埼玉県 | Saitama | SAITAMA | 埼玉支店 |
| 12 | 千葉県 | Chiba | CHIBA | 千葉支店 |
| 13 | 東京都 | Tokyo | TOKYO | 東京支店 |
| 14 | 神奈川県 | Kanagawa | KANAGAWA | 神奈川支店 |
| 15 | 新潟県 | Niigata | NIIGATA | 新潟支店 |
| 16 | 富山県 | Toyama | TOYAMA | 富山支店 |
| 17 | 石川県 | Ishikawa | ISHIKAWA | 石川支店 |
| 18 | 福井県 | Fukui | FUKUI | 福井支店 |
| 19 | 山梨県 | Yamanashi | YAMANASHI | 山梨支店 |
| 20 | 長野県 | Nagano | NAGANO | 長野支店 |
| 21 | 岐阜県 | Gifu | GIFU | 岐阜支店 |
| 22 | 静岡県 | Shizuoka | SHIZUOKA | 静岡支店 |
| 23 | 愛知県 | Aichi | AICHI | 愛知支店 |
| 24 | 三重県 | Mie | MIE | 三重支店 |
| 25 | 滋賀県 | Shiga | SHIGA | 滋賀支店 |
| 26 | 京都府 | Kyoto | KYOTO | 京都支店 |
| 27 | 大阪府 | Osaka | OSAKA | 大阪支店 |
| 28 | 兵庫県 | Hyogo | HYOGO | 兵庫支店 |
| 29 | 奈良県 | Nara | NARA | 奈良支店 |
| 30 | 和歌山県 | Wakayama | WAKAYAMA | 和歌山支店 |
| 31 | 鳥取県 | Tottori | TOTTORI | 鳥取支店 |
| 32 | 島根県 | Shimane | SHIMANE | 島根支店 |
| 33 | 岡山県 | Okayama | OKAYAMA | 岡山支店 |
| 34 | 広島県 | Hiroshima | HIROSHIMA | 広島支店 |
| 35 | 山口県 | Yamaguchi | YAMAGUCHI | 山口支店 |
| 36 | 徳島県 | Tokushima | TOKUSHIMA | 徳島支店 |
| 37 | 香川県 | Kagawa | KAGAWA | 香川支店 |
| 38 | 愛媛県 | Ehime | EHIME | 愛媛支店 |
| 39 | 高知県 | Kochi | KOCHI | 高知支店 |
| 40 | 福岡県 | Fukuoka | FUKUOKA | 福岡支店 |
| 41 | 佐賀県 | Saga | SAGA | 佐賀支店 |
| 42 | 長崎県 | Nagasaki | NAGASAKI | 長崎支店 |
| 43 | 熊本県 | Kumamoto | KUMAMOTO | 熊本支店 |
| 44 | 大分県 | Oita | OITA | 大分支店 |
| 45 | 宮崎県 | Miyazaki | MIYAZAKI | 宮崎支店 |
| 46 | 鹿児島県 | Kagoshima | KAGOSHIMA | 鹿児島支店 |
| 47 | 沖縄県 | Okinawa | OKINAWA | 沖縄支店 |

## 6. Data model and migration strategy

### 6.1 Recommended canonical design: no new prefecture column

No database migration is required for the current schema.

The existing branches.code column becomes the persisted canonical
prefecture_code. The existing unique key:

    UNIQUE (company_id, code)

is therefore the equivalent of:

    UNIQUE (company_id, prefecture_code)

This is the smallest design that preserves the existing schema, seeded
identifiers, employee read models, and branch foreign-key relationships. It
also avoids storing the same identity twice in code and prefecture_code,
which could allow the two values to drift.

The application contract changes semantically:

- code is no longer arbitrary branch text;
- code must be a key in PrefectureCatalog;
- name must equal the catalog-derived branch name for that code.

The database cannot express the complete code-to-name mapping with the
existing check constraint. Therefore the application catalog is authoritative
for writes, while the existing database unique constraint is authoritative for
concurrency.

### 6.2 Existing data compatibility

Before enabling the redesigned create/update workflow, existing branch rows
must be checked against the catalog:

- current TOKYO / 東京支店 and OSAKA / 大阪支店 rows are valid;
- existing valid canonical rows remain readable and editable for metadata;
- an existing row with an unknown legacy code remains readable and must not be
  physically deleted;
- unknown legacy rows require a reviewed data-normalization decision before
  they can be treated as selectable prefecture branches.

The current repository and seed fixtures contain valid Tokyo and Osaka entries,
so the accepted project data does not require a backfill migration. If another
environment contains arbitrary historical codes, that environment needs a
separate reviewed data migration or legacy compatibility decision; this
specification must not silently overwrite those rows.

### 6.3 Alternative rejected design

Adding a nullable prefecture_code column while retaining code would duplicate
the same identity and require backfilling existing rows. Adding a unique
(company_id, prefecture_code) key without removing or reconciling code would
create two competing identity constraints. The canonical existing code design
is preferred unless a future requirement needs a distinct, non-code prefecture
identifier.

## 7. Business rules

### 7.1 Creation

Creating a branch requires:

- an existing company;
- one supported catalog prefecture;
- a user-entered city;
- address;
- phone.

The selected prefecture must not initialize or derive the city. For example,
東京都 may be selected while the city is entered as 新宿区.

The new branch is always active. Submitted status, id, created_at, and
updated_at values cannot control persistence.

The service derives:

    prefecture code -> branches.code
    prefecture code -> branches.name

For example:

    東京都 -> TOKYO -> 東京支店
    大阪府 -> OSAKA -> 大阪支店
    神奈川県 -> KANAGAWA -> 神奈川支店

If the company already has an active or inactive branch with that canonical
code, creation is rejected. An inactive branch does not free the prefecture.

The same prefecture is allowed for a different company because uniqueness is
scoped by company_id.

### 7.2 Editing

After creation, these fields are immutable:

- company_id;
- selected prefecture;
- code;
- derived name.

These metadata fields remain editable:

- city;
- address;
- phone.

An edit request must not be able to change identity by submitting company_id,
prefecture, prefecture_code, code, or name. The controller may render company,
prefecture, code, and name as read-only display values, but backend protection
must not depend on HTML readonly or hidden fields.

An inactive branch may continue to receive metadata corrections under the
existing Phase 07 behavior, but an edit must never reactivate it or change its
identity.

### 7.3 Deactivation and history

Deactivation remains a conditional status update:

    UPDATE branches
    SET status = 'inactive', updated_at = :updated_at
    WHERE id = :id AND status = 'active'

It does not delete the row, departments, or employees. Repeated deactivation
is an idempotent no-op. Reactivation is not added.

Inactive branches remain readable in lists, details, employee details, and
historical relationships. They are not offered as new employee or department
choices where the existing Phase 07 rules require active choices.

## 8. Domain/application design

### 8.1 Catalog dependency

BranchService must receive the catalog through explicit dependency injection,
or use an immutable catalog value object with no runtime state. The composition
root remains responsible for wiring it.

The catalog, not a controller or view, determines:

- whether a submitted prefecture is supported;
- its canonical code;
- its derived Japanese branch name;
- its localized display labels.

The catalog does not determine city, address, or phone values.

### 8.2 Input models

The current BranchInput is a canonical persistence write model containing
arbitrary code and name. It must be replaced or narrowed so raw request data
cannot create those fields.

Recommended explicit models:

- BranchCreateInput: companyId, prefectureCode, city, address, phone;
- BranchMetadataInput: city, address, phone;
- an internal canonical persistence shape containing companyId, derived code,
  derived name, and metadata.

If preserving the existing BranchInput name is materially smaller, it may be
used only as the canonical write shape created by BranchService after catalog
resolution. It must not be constructed directly from request code or name.

### 8.3 Validator

BranchInputValidator remains responsible for structural normalization:

- positive company identifier;
- supported scalar prefecture code format;
- required bounded metadata strings;
- trimming and preservation of submitted values for 422 responses.

The validator must not accept arbitrary branch code/name as identity fields.
The service must resolve the catalog entry after validation and before any
repository write.

## 9. Repository changes

BranchRepositoryInterface remains a focused management contract. The preferred
operations are:

- listManagement(int limit): array;
- listActive(): array;
- listCompanies(): array;
- findCompanyById(int id): ?array;
- findById(int id): ?array;
- codeExists(int companyId, string canonicalCode, ?int exceptId = null): bool;
- insert(canonicalBranchInput, string createdAt, string updatedAt): int;
- updateMetadata(int id, metadataInput, string updatedAt): void;
- deactivate(int id, string updatedAt): bool.

Keeping the existing codeExists name is acceptable because code now means the
canonical prefecture code. A prefectureExists alias is not required if it
would only duplicate the same SQL.

The repository must:

- keep company_id, code, and name out of metadata update SQL;
- select explicit branch fields and preserve existing read shapes;
- retain company-scoped duplicate detection;
- catch the existing unique-key conflict and translate it to a known duplicate
  outcome;
- use prepared statements for all request values;
- leave status unchanged during metadata updates.

The database unique key remains the final race/concurrency boundary. A
pre-check in BranchService is useful for a friendly error, but it is not
sufficient by itself.

## 10. Service rules and duplicate handling

BranchService owns the use-case rules:

1. validate raw input;
2. resolve the company;
3. resolve the submitted prefecture through PrefectureCatalog;
4. derive canonical code and branch name;
5. reject an existing same-company canonical code, including inactive rows;
6. construct canonical persistence data;
7. call the repository;
8. translate a database duplicate race to a 422 code/prefecture error.

For update:

1. load the current branch;
2. preserve its company, canonical code, and derived name;
3. validate only metadata fields;
4. ignore or reject attempted identity fields without using them;
5. update only city, address, phone, and updated_at.

Unexpected database errors continue to reach the centralized 500 responder.
Known duplicate errors must not expose SQL or raw PDO messages.

## 11. HTTP routes and controller behavior

The existing branch routes remain:

| Method | Path | Behavior |
| --- | --- | --- |
| GET | /branches | List active and inactive branches |
| GET | /branches/create | Create form |
| POST | /branches | Create branch; 303 to detail on success |
| GET | /branches/{id}/deactivate | Non-mutating confirmation |
| POST | /branches/{id}/deactivate | Conditional deactivation |
| GET | /branches/{id}/edit | Edit form |
| POST | /branches/{id} | Metadata update |
| GET | /branches/{id} | Detail/history read |

Static and action routes remain before /branches/{id}. Invalid identifiers and
missing records use the existing 404 behavior. Successful writes continue to
use POST -> 303 -> GET.

BranchController must remain thin. It parses the request, passes body data to
the service, selects views or redirects, and maps known outcomes to 200, 303,
422, or 404. It must not derive code/name, validate the 47-entry mapping, or
trust read-only HTML controls.

## 12. Create UI behavior

The branch create form must:

- keep the existing company selector;
- replace free-text code and name inputs with one required prefecture select;
- render all 47 prefectures;
- use catalog values as option values and catalog labels as option text;
- show Japanese prefecture names in Japanese;
- show a locale-appropriate English label, optionally alongside Japanese, in
  English;
- keep city, address, and phone as normal user-entered inputs;
- do not initialize or derive city from the selected prefecture;
- not render editable code or name inputs;
- preserve submitted prefecture and metadata values after 422;
- use labels, errors, and instructions from Translator.

The server must derive and persist code/name even if a malicious request adds
code or name fields. Such fields must never override catalog output.

No JavaScript is required for correctness. A display-only preview of the
derived code/name is optional, but it must use prepared catalog data and must
not become a second mapping source.

## 13. Edit UI behavior

The edit form must:

- display company, prefecture, code, and derived name as read-only values;
- provide editable city, address, and phone controls;
- not expose identity values as trusted editable fields;
- preserve inactive status and show it clearly;
- keep the current company and prefecture readable even when metadata is
  corrected;
- render validation errors and submitted metadata safely.

If hidden inputs are used for navigation or display, the service must ignore
identity changes from them and reconstruct immutable values from the current
database row.

## 14. Read and historical behavior

GET /branches/{id} continues to return inactive branches. Detail/list read
models should expose:

- company code/name;
- canonical branch code;
- derived branch name;
- prefecture label resolved from the catalog when supported;
- city, address, phone, status, and timestamps;
- department and employee counts;
- related departments.

The read path must not physically delete or hide historical branches. Unknown
legacy codes, if found during a compatibility audit, remain readable as raw
historical data and are reported as a data-compatibility issue rather than
silently rewritten.

## 15. Localization

Use resources/lang/en.php, resources/lang/ja.php, and Translator.

Add or update keys for:

- prefecture selection label and help text;
- derived identity/read-only labels;
- unsupported prefecture;
- duplicate prefecture/branch;
- immutable prefecture/code/name errors;
- branch metadata validation;
- existing branch history/deactivation notices.

Suggested concepts:

| Concept | English | Japanese |
| --- | --- | --- |
| Prefecture | Prefecture | 都道府県 |
| Derived code | Branch code is generated from the prefecture. | 支店コードは都道府県から自動生成されます。 |
| Derived name | Branch name is generated from the prefecture. | 支店名は都道府県から自動生成されます。 |
| Duplicate | This company already has a branch for this prefecture. | この会社には選択した都道府県の支店がすでにあります。 |
| Immutable identity | The company, prefecture, branch code, and branch name cannot be changed. | 会社、都道府県、支店コード、支店名は変更できません。 |

The 47 data labels should come from PrefectureCatalog rather than being
duplicated in language files. Translation resources provide UI concepts and
validation messages; the catalog provides canonical data labels.

## 16. Seeder compatibility

DevelopmentSeeder::upsertBranches() must use the central catalog for Tokyo and
Osaka instead of duplicating code/name mapping literals.

The seed must continue to:

- create exactly the existing two sample branches;
- use TOKYO / 東京支店 and OSAKA / 大阪支店;
- preserve the existing company and department keys;
- remain idempotent;
- preserve the existing integration-test counts;
- keep seeded sample branches active according to the current development
  fixture contract.

Seeder lookup should remain by company and canonical code. It must not create
a second branch for a prefecture when an inactive historical row already
exists. The development seeder's normalization of its own sample fixture is
not an application reactivation feature.

## 17. Concurrency and database integrity

The service should first perform a canonical duplicate pre-check for a useful
422 response. The database must still enforce:

    UNIQUE (company_id, code)

because code is the canonical persisted prefecture identity.

Two concurrent creates for the same company and prefecture may both pass the
service pre-check. Only one database insert may succeed. The repository must
translate the unique-key failure into the same safe duplicate outcome as the
pre-check.

The uniqueness check must not filter by status; inactive history consumes the
prefecture slot. No application transaction is required around the pre-check
and insert because the unique index is the atomic boundary.

## 18. Test plan

### 18.1 Catalog and validator unit tests

Add focused tests for:

- exactly 47 catalog entries;
- every listed prefecture resolving to a unique canonical code;
- every canonical code resolving to the expected derived branch name;
- Tokyo, Osaka, Kanagawa, Saitama, and Chiba examples;
- unsupported/arbitrary prefecture values being rejected;
- scalar/array input handling and metadata length validation;
- English/Japanese catalog labels where the catalog exposes both.

### 18.2 Branch service unit tests

Cover:

- valid creation for all 47 prefectures;
- code and name are derived from the selected prefecture;
- city remains the submitted user-entered metadata and is not derived from the
  selected prefecture;
- submitted arbitrary code cannot override generated code;
- submitted arbitrary name cannot override generated name;
- same prefecture is rejected within one company;
- an inactive existing branch still blocks creation;
- same prefecture succeeds for a different company;
- missing company is rejected;
- status/id/timestamp input cannot control persistence;
- company/prefecture/code/name remain immutable on update;
- city/address/phone update succeeds;
- inactive branch metadata remains editable without reactivation;
- known duplicate repository race becomes a safe validation failure;
- UTC timestamps are supplied through the existing clock.

### 18.3 HTTP/feature tests

Extend the organization HTTP coverage to verify:

- create form renders all 47 options;
- create form does not render editable code/name inputs;
- active and inactive branch history remains readable;
- valid create redirects with 303 and stores derived identity;
- arbitrary submitted code/name do not appear as persisted identity;
- duplicate prefecture returns 422;
- same prefecture under another company remains valid;
- edit form shows immutable identity as read-only;
- update requests cannot change company, prefecture, code, or name;
- metadata update returns 303;
- EN/JA labels and messages are correct;
- branch action routes retain existing 404/405 behavior;
- employee and department routes continue to work with canonical branch data.

### 18.4 Repository integration tests

Against the isolated MySQL/MariaDB test database, verify:

- all 47 canonical codes fit the existing column and can be inserted;
- (company_id, code) rejects a duplicate in the same company;
- the duplicate remains rejected after the first branch is inactive;
- the same code is accepted for another company;
- branch update changes only metadata;
- company/code/name remain unchanged after update;
- foreign-key relationships to employees/departments remain valid;
- known duplicate-key exceptions are translated;
- concurrent or sequential duplicate attempts cannot create two rows;
- existing Tokyo/Osaka seed-shaped data reads correctly;
- no migration is added for this design.

Use real database constraints for persistence assertions. Do not use PDO mocks
to claim race protection.

### 18.5 Seeder regression tests

Update the development seeder integration expectations only as needed to prove:

- existing two-branch counts remain unchanged;
- seeding twice is idempotent;
- Tokyo and Osaka still resolve through the catalog;
- departments and employees still reference the same branch identities;
- unrelated records remain untouched.

## 19. Expected files to change

Expected production changes:

- src/Domain/Organization/PrefectureCatalog.php or an equivalent central
  catalog/value-object file;
- src/Application/DTO/BranchInput.php or new explicit create/metadata DTOs;
- src/Application/Validation/BranchInputValidator.php;
- src/Application/Validation/BranchValidationResult.php if its shape changes;
- src/Application/Organization/BranchService.php;
- src/Domain/Organization/BranchRepositoryInterface.php;
- src/Domain/Organization/BranchDuplicateException.php if duplicate wording
  changes;
- src/Infrastructure/Persistence/PdoBranchRepository.php;
- src/Http/Controllers/BranchController.php only for new service outcomes;
- resources/views/branches/_form.php;
- resources/views/branches/show.php if prefecture/read-only identity is added;
- resources/views/branches/index.php if prefecture display is added;
- resources/lang/en.php;
- resources/lang/ja.php;
- src/Database/Seed/DevelopmentSeeder.php to consume the catalog;
- tests/Unit/Application/Organization/OrganizationInputValidatorTest.php;
- tests/Unit/Application/Organization/OrganizationServiceTest.php;
- tests/Feature/Http/OrganizationHttpTest.php;
- tests/Integration/Database/OrganizationRepositoryIntegrationTest.php;
- tests/Integration/Database/DevelopmentSeederIntegrationTest.php;
- a new catalog unit-test file if appropriate.

routes/web.php and ApplicationBootstrap.php should only change if the catalog
or new DTO dependency requires explicit composition-root wiring.

No migration file is expected for the recommended canonical existing-code
design. No Department, Employee, or Dispatch schema file should change.

## 20. Acceptance criteria

The redesign is complete only when:

1. A central catalog contains exactly all 47 Japanese prefectures.
2. Every catalog entry has one stable canonical code and derived branch name.
3. Controllers, views, services, and seeders do not duplicate the mapping.
4. Branch creation uses a prefecture selector rather than free-text code/name.
5. Unsupported or arbitrary prefectures are rejected server-side.
6. Branch code is generated from the selected prefecture.
7. Branch name is generated from the selected prefecture.
8. Malicious submitted code/name values cannot override generated values.
9. One company cannot create the same prefecture twice.
10. An inactive branch still prevents creation of the same company/prefecture.
11. The same prefecture may be used by another company.
12. The database unique key remains the concurrency boundary.
13. Company, prefecture, code, and derived name cannot be changed by updates.
14. City, address, and phone remain editable metadata.
15. Inactive branch history remains readable and is never physically deleted.
16. No branch reactivation is introduced.
17. City is user-entered metadata and is not automatically initialized or
    derived from the selected prefecture.
18. Tokyo and Osaka development seed data remains valid and idempotent.
19. Employee and department relationships remain compatible.
20. English and Japanese UI, errors, and notices remain supported.
21. Unit, HTTP, repository integration, and seeder tests cover the required
    behavior.
22. No database migration is required for the recommended design.
23. Department Redesign, Employee Advanced Search, authentication redesign,
    physical deletion, and employee-code redesign remain excluded.

## 21. Risks and decisions requiring review

### 21.1 Canonical code as persisted prefecture identity

This specification deliberately uses the existing branches.code as the
prefecture key. It avoids redundant schema state and preserves current seed
and employee read behavior, but it requires all future branch codes to remain
catalog keys.

If the business later needs a separate branch code independent of prefecture,
the data model should be revisited with a reviewed migration rather than
quietly adding a second identity field.

### 21.2 Legacy arbitrary branches

The pre-redesign implementation allowed arbitrary code/name values. The
current fixtures are compatible, but another environment may contain legacy
rows that are not in the catalog. Those rows must remain readable and must not
be physically removed. A separate data-normalization decision is required
before such rows can be edited as canonical prefecture branches.

### 21.3 City and prefecture are separate concepts

The existing schema uses city, and historical/seed data may store the full
prefecture label there. This specification preserves those values without
rewriting them. New creation treats city as normal user-entered metadata; the
selected prefecture neither initializes nor derives it. The UI and backend
must keep later city edits separate from the immutable prefecture.

### 21.4 Stored branch names are Japanese

Derived branch names are stored as canonical Japanese business data
(東京支店, 大阪支店, and so on). English localization applies to labels,
instructions, and errors; it must not generate a different persisted name per
request locale.

### 21.5 Existing Phase 07 inactive-edit rule

Phase 07 permits inactive branch metadata correction. This redesign preserves
that behavior while making branch identity immutable. A future requirement to
freeze inactive records entirely would need a separate specification.

## 22. Explicit exclusions confirmation

Department Redesign is excluded. Employee Advanced Search is excluded.
Reactivation is excluded. Authentication redesign is excluded. Physical
deletion is excluded. Employee code redesign is excluded. No unrelated UI or
schema redesign is included.
