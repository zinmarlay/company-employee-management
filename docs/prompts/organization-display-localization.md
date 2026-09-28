We are starting a focused Organization Display Localization cleanup.

Current branch:
feature/organization-display-localization

IMPORTANT:
Do NOT implement anything yet.
Do NOT modify production code.
Do NOT modify tests yet.
Do NOT add a migration.
Do NOT commit, merge, or push.

First inspect the current project and create ONLY:

docs/specs/organization-display-localization.md

==================================================
BACKGROUND
==================================================

The application supports EN and JA.

Branch and Department identities are catalog-backed:

Branch examples:
TOKYO
OSAKA
CHIBA

Department examples:
DEV
SALES
HR
GA
FIN
IT
LEGAL
PR
PL
CS

Phase 08 Employee Advanced Search already introduced some locale-aware
organization display behavior.

However, other screens still show persisted Japanese organization names in
English mode.

Observed examples:

English Branch list:

- 千葉支店
- 大阪支店
- 東京支店

English Department list:

- OSAKA 大阪支店
- TOKYO 東京支店
- 人事部
- 総務部
- 開発部
- 営業部

This creates inconsistent mixed-language UI.

==================================================
CORE DISPLAY RULE
==================================================

Known catalog-backed organization identity:

→ display according to current locale.

Examples:

English:
TOKYO → Tokyo Branch
OSAKA → Osaka Branch
CHIBA → Chiba Branch

DEV → Development
SALES → Sales
HR → Human Resources
GA → General Affairs

Japanese:
TOKYO → 東京支店
OSAKA → 大阪支店
CHIBA → 千葉支店

DEV → 開発部
SALES → 営業部
HR → 人事部
GA → 総務部

==================================================
PERSISTED DATA
==================================================

Do NOT change persisted Branch/Department names.

Do NOT migrate Japanese names into English.

Persisted organization data remains canonical historical/domain data.

Localization is presentation behavior.

==================================================
LEGACY / CUSTOM RECORDS
==================================================

Unknown catalog codes must remain readable.

Example:

a historical/custom Branch whose code is not in PrefectureCatalog
and persisted name is:

横浜支店

English mode:

→ 横浜支店

Do NOT guess "Yokohama Branch" unless an authoritative catalog mapping exists.

Same rule for unknown Department codes.

Fallback:

known catalog code
→ localized catalog label

unknown catalog code
→ persisted name

==================================================
USER-ENTERED BUSINESS DATA
==================================================

Do NOT automatically translate ordinary business data.

Examples:

Company:
サンプル株式会社

City:
板橋区

Address:
東京都...

Employee:
山田 太郎

Position:
シニアエンジニア

These remain as stored unless the domain explicitly has multilingual fields.

The cleanup applies to catalog-defined organization display identity, not
arbitrary business text.

==================================================
AUDIT SCOPE
==================================================

Inspect every place Branch and Department names are displayed or used as
choices.

At minimum inspect:

Employee:

- list/search
- detail
- create
- edit

Branch:

- list
- detail
- create/edit where relevant
- deactivate confirmation where relevant

Department:

- list
- detail
- create/edit
- deactivate confirmation where relevant

Dispatch:

- dispatch company screens if organization names appear
- dispatch contract create/detail/edit/renew/history
- employee/organization labels used in dispatch workflows

Dashboard or other screens:

- any Branch/Department display

Shared partials/helpers:

- inspect whether localization should be centralized.

Do not assume every screen needs modification.
Document exactly which screens currently expose persisted organization names.

==================================================
CATALOGS
==================================================

Inspect:

- PrefectureCatalog
- DepartmentCatalog
- Phase 08 localization additions

Determine whether the catalogs already provide enough information for:

EN display
JA display

Prefer extending/reusing the existing catalog APIs rather than duplicating
mappings.

There must be ONE authoritative mapping for each catalog identity.

Do NOT put duplicate TOKYO/DEV translation maps into:

- views
- controllers
- services
- JavaScript
- translation files

unless the architecture genuinely requires translation keys and the catalog
remains the authoritative identity mapping.

==================================================
ARCHITECTURE
==================================================

Design a clean reusable organization-display mechanism.

Consider whether localization should happen through:

- catalog methods;
- a dedicated organization display-name resolver;
- application view-model preparation;
- another small reusable presentation service.

Choose the approach that best fits the current Pure PHP architecture.

Avoid views repeatedly implementing logic like:

if code == TOKYO ...

Controllers should remain thin.

Repositories should return canonical data, not locale-specific SQL values.

SQL must not contain EN/JA presentation logic.

==================================================
SORTING / SEARCHING
==================================================

Do NOT silently change search or sorting semantics.

Phase 08 Employee Advanced Search currently has approved search/filter/sort
behavior.

Localized display labels must not change:

- IDs;
- filter semantics;
- branch/department relationships;
- repository predicates;
- employee result membership.

If Branch/Department sorting currently uses persisted DB names, document that
behavior.

Do not redesign localized sorting unless necessary and explicitly reviewed.

==================================================
ASSIGNMENT CHOICES
==================================================

Preserve existing lifecycle rules.

Employee create/edit assignment:
→ active Branches/Departments only.

Employee historical search:
→ active + inactive Branches/Departments.

Localization must affect display labels only.

It must not change which records are selectable.

==================================================
INACTIVE DISPLAY
==================================================

Preserve inactive indicators.

English example:
Tokyo Branch (inactive)

Japanese example:
東京支店（無効）

Apply the current project's localization conventions consistently.

Do not confuse localized display name with lifecycle status.

==================================================
JAVASCRIPT
==================================================

Phase 08 may contain vanilla JavaScript for dependent Branch → Department
search choices.

If organization labels are passed to JavaScript:

→ server should prepare localized labels.

Do not create another catalog translation map in JavaScript.

Server-side behavior remains authoritative.

==================================================
CREATE / EDIT FORMS
==================================================

Be careful with forms.

If a field represents catalog identity:

→ localized option label is appropriate.

If a field displays persisted historical data or user-entered metadata:

→ do not translate it automatically.

Do not accidentally make immutable Branch/Department identity editable.

Preserve:

Branch identity immutability.
Department identity immutability.
Inactive-record read-only protections.

==================================================
DETAIL / LIST SCREENS
==================================================

Known catalog-backed Branch/Department names should display localized names
according to current locale.

Example English Branch list:

CHIBA | Chiba Branch
OSAKA | Osaka Branch
TOKYO | Tokyo Branch

Example Japanese Branch list:

CHIBA | 千葉支店
OSAKA | 大阪支店
TOKYO | 東京支店

Department example English:

DEV | Development
SALES | Sales

Japanese:

DEV | 開発部
SALES | 営業部

If Branch code is shown next to the name, avoid redundant/awkward formatting.

Recommend a consistent presentation.

==================================================
TEST PLAN
==================================================

The specification must include tests for at least:

Catalog-backed Branch:

- EN localized label
- JA localized label

Catalog-backed Department:

- EN localized label
- JA localized label

Legacy/custom:

- unknown Branch code → persisted name fallback
- unknown Department code → persisted name fallback

Inactive:

- EN localized name + inactive indicator
- JA localized name + inactive indicator

Screens:

- Branch list/detail
- Department list/detail
- Employee list/search
- Employee detail
- Employee create/edit choices where relevant
- Dispatch screens if organization labels appear

Regression:

- Branch identity unchanged
- Department identity unchanged
- DB persisted names unchanged
- employee search results unchanged
- search filters unchanged
- Branch → Department dependent filter unchanged
- active-only assignment rules unchanged
- historical search choices unchanged
- inactive-record protections unchanged

==================================================
DATABASE
==================================================

No migration is expected.

Do not add English-name columns.

Do not rewrite persisted names.

If inspection reveals a genuine data-model blocker, report it for review
instead of changing the schema.

==================================================
OUT OF SCOPE
==================================================

Do not include:

- translating Company names;
- translating City/address;
- translating Employee names;
- translating Position titles;
- Phase 09 portfolio features;
- Phase 10 authentication/users;
- new Branch/Department business rules;
- organization CRUD redesign;
- database localization redesign;
- automatic machine translation.

==================================================
SPEC CONTENT
==================================================

docs/specs/organization-display-localization.md must include:

1. Purpose
2. Current-state findings
3. Screens audited
4. Current inconsistencies
5. Localization boundary
6. Catalog-backed display rules
7. Legacy/custom fallback rules
8. User-entered data rules
9. Proposed architecture
10. PrefectureCatalog changes, if any
11. DepartmentCatalog changes, if any
12. Employee screen behavior
13. Branch screen behavior
14. Department screen behavior
15. Dispatch screen behavior
16. Inactive-record display
17. Assignment/search choice behavior
18. JavaScript interaction
19. Sorting/search impact
20. Persistence guarantees
21. Localization keys
22. Test plan
23. Expected files to change
24. Migration decision
25. Acceptance criteria
26. Risks / decisions requiring review

Do NOT implement.

After writing the specification, report:

1. every screen inspected;
2. every screen currently showing inconsistent organization names;
3. which screens already behave correctly;
4. proposed reusable localization architecture;
5. PrefectureCatalog changes;
6. DepartmentCatalog changes;
7. legacy fallback behavior;
8. whether Dispatch requires changes;
9. whether Employee detail/create/edit require changes;
10. confirmation that persisted data remains unchanged;
11. confirmation that no migration is required;
12. decisions requiring review.

# implement prompt

Implement the approved Organization Display Localization specification.

Current branch:
feature/organization-display-localization

Specification:
docs/specs/organization-display-localization.md

Read and follow the specification completely before changing code.

==================================================
APPROVED REVIEW DECISIONS
==================================================

The specification is approved with these decisions locked:

1. Detail screens ARE in scope.

Known catalog-backed Branch and Department identities must be localized
consistently on:

- Employee list/search
- Employee detail
- Employee create/edit choices
- Branch list
- Branch detail
- Branch edit/read-only identity
- Branch deactivate confirmation
- Department list
- Department detail
- Department create/edit choices and read-only identity
- Department deactivate confirmation

2. Unknown/custom codes MUST use persisted-name fallback.

Example:

unknown Branch code + persisted name 横浜支店

EN:
横浜支店

JA:
横浜支店

Do not guess or machine-translate unknown organization names.

3. Sorting semantics MUST NOT change.

Do not introduce locale-aware sorting.

Preserve Phase 08 Employee search sorting, filtering, pagination,
query values, and result membership.

4. Create:

src/Application/Organization/OrganizationDisplayNameResolver.php

Use this as the shared reusable organization identity display resolver.

Do not create duplicate mapping logic elsewhere.

5. Dispatch production screens are OUT OF SCOPE for code changes because
   the audit found no direct Branch/Department identity display.

Do not modify Dispatch merely for consistency.

Only update Dispatch tests if required because a shared dependency or test
fixture must adapt.

6. PrefectureCatalog and DepartmentCatalog remain the authoritative
   code-to-label sources.

Do not duplicate catalog mappings in:

- views
- controllers
- services
- JavaScript
- resources/lang

7. OrganizationDisplayNameResolver resolves IDENTITY LABELS ONLY.

It must NOT append:

- (inactive)
- （無効）
- active/inactive status text

Lifecycle/status presentation remains separate and must use the existing
Translator conventions in application/view-model presentation.

==================================================
ARCHITECTURE REQUIREMENTS
==================================================

Implement a reusable OrganizationDisplayNameResolver in:

src/Application/Organization/OrganizationDisplayNameResolver.php

It should use:

- PrefectureCatalog
- DepartmentCatalog
- active locale

It must support at minimum:

- Branch display name:
  code + persisted name -> localized known label or persisted fallback

- Department display name:
  code + persisted name -> localized known label or persisted fallback

Keep the API small.

Only add combined Branch/Department formatting if the current application
actually needs it.

Do not turn the resolver into a general translation service.

==================================================
CATALOG RULES
==================================================

Known Branch:

EN:
TOKYO -> Tokyo Branch
OSAKA -> Osaka Branch
CHIBA -> Chiba Branch
and all other known PrefectureCatalog entries according to the catalog.

JA:
TOKYO -> 東京支店
OSAKA -> 大阪支店
CHIBA -> 千葉支店
and all other known entries.

Known Department:

DEV:
EN Development
JA 開発部

SALES:
EN Sales
JA 営業部

HR:
EN Human Resources
JA 人事部

GA:
EN General Affairs
JA 総務部

FIN:
EN Accounting
JA 経理部

IT:
EN Information Systems
JA 情報システム部

LEGAL:
EN Legal
JA 法務部

PR:
EN Public Relations
JA 広報部

PL:
EN Planning
JA 企画部

CS:
EN Customer Support
JA カスタマーサポート部

Unknown code:
return persisted name unchanged.

Do not rewrite database records.

==================================================
EMPLOYEE
==================================================

Refactor the Phase 08 organization-display mapping to use the shared
resolver.

Do not leave separate Employee-specific catalog mapping logic if the new
resolver can own it.

Employee list/search:

- preserve all Phase 08 search behavior;
- preserve filter IDs;
- preserve Branch -> Department dependent filtering;
- preserve active + inactive historical search choices;
- preserve unknown-code fallback;
- preserve query state;
- preserve sorting and pagination.

Employee detail:

localize known Branch and Department display names.

Do not translate:

- employee name
- email
- position
- other employee business data.

Employee create/edit:

localize Branch and Department option labels.

Preserve:

- active-only assignment choices;
- submitted IDs;
- grouping relationships;
- current inactive-assignment compatibility if already supported;
- all server-side validation.

JavaScript must consume server-prepared localized labels.

Do not add catalog mappings to JavaScript.

==================================================
BRANCH
==================================================

Branch list:

show localized known Branch identity.

Example EN:

TOKYO | Tokyo Branch

JA:

TOKYO | 東京支店

Preserve company/city/address/phone/etc exactly as persisted.

Branch detail:

localize Branch identity and any related known Department identity.

Branch edit:

read-only identity display must be localized.

Do not make Branch identity editable.

Branch deactivate confirmation:

use localized Branch identity.

Preserve all inactive-record protections.

Branch create:

existing localized PrefectureCatalog choices should continue working.

Do not redesign creation.

==================================================
DEPARTMENT
==================================================

Department list:

localize both:

- Department identity
- parent Branch identity

Example EN:

TOKYO | Tokyo Branch
DEV | Development

JA:

TOKYO | 東京支店
DEV | 開発部

Department detail:

localize Department identity and parent Branch identity.

Department create:

Department catalog options remain localized.
Branch choices must use localized Branch identity.

Department edit:

read-only Department and Branch identity must be localized.

Do not make identity editable.

Department deactivate confirmation:

use localized Department/Branch identity where displayed.

Preserve inactive Department read-only rules and inactive Branch historical
behavior.

==================================================
STATUS / INACTIVE
==================================================

Do NOT make OrganizationDisplayNameResolver append status.

Example identity resolver output:

Tokyo Branch

NOT:

Tokyo Branch (inactive)

The existing application presentation layer should combine:

localized identity

- Translator-based inactive indicator

when required.

EN example:

Tokyo Branch (inactive)

JA example:

東京支店（無効）

Preserve current conventions if punctuation is already centralized.

==================================================
USER-ENTERED DATA
==================================================

Never automatically translate:

- Company names
- City
- Address
- Phone
- Employee names
- Position
- Department description
- Dispatch company name
- arbitrary historical text

==================================================
PERSISTENCE
==================================================

No migration.

Do not change:

- Branch persisted name
- Department persisted name
- codes
- IDs
- foreign keys
- status
- employee assignments

Repositories must not introduce locale-specific SQL.

If a read projection lacks a required canonical code, make the smallest
read-model change necessary, but do not change search predicates or write
behavior.

==================================================
TESTS
==================================================

Add/update focused tests.

At minimum cover:

Resolver:

- known Branch EN
- known Branch JA
- unknown Branch fallback
- known Department EN
- known Department JA
- unknown Department fallback

Employee:

- EN search labels/results
- JA search labels/results
- detail localization
- create/edit assignment localization
- inactive historical search choices
- unknown fallback
- dependent Branch -> Department behavior regression
- mismatched filter zero-result regression

Branch:

- list EN/JA
- detail EN/JA
- edit read-only localized identity
- deactivate confirmation localized identity
- unknown fallback
- inactive protection regression

Department:

- list EN/JA
- detail EN/JA
- parent Branch localization
- create/edit choices
- deactivate confirmation
- unknown fallback
- inactive protection regression

Persistence/regression:

- persisted names unchanged
- canonical codes unchanged
- IDs unchanged
- Employee search membership unchanged
- no lifecycle rule changed

Run:

composer test

Then also run the DB-backed suite using the project's existing test
environment configuration.

Do not weaken existing tests merely to make them pass.

If an existing test conflicts with the approved current business rules,
explain the conflict before changing its expectation.

==================================================
SCOPE CONTROL
==================================================

Do not implement:

- locale-aware sorting
- database localization
- English-name columns
- machine translation
- Company translation
- address/city translation
- Dispatch organization redesign
- Phase 09
- Phase 10
- authentication
- unrelated refactors

==================================================
DOCUMENTATION
==================================================

After implementation, create:

docs/prompts/organization-display-localization.md

Document the implementation prompt/intent consistently with the project's
existing docs/prompts convention.

Do not overwrite the approved specification except for a tiny factual
correction if implementation proves the audited current state inaccurate.
If such a correction is necessary, report it explicitly.

==================================================
FINAL REPORT
==================================================

After implementation and tests, report:

1. files changed;
2. resolver API implemented;
3. PrefectureCatalog changes;
4. DepartmentCatalog changes;
5. Employee screens changed;
6. Branch screens changed;
7. Department screens changed;
8. Dispatch changes, if any and why;
9. unknown-code fallback behavior;
10. inactive display behavior;
11. test results;
12. DB-backed test results;
13. any skipped tests;
14. PHPUnit deprecations;
15. confirmation that no migration was added;
16. confirmation that persisted organization data was not changed;
17. confirmation that Phase 08 search semantics were not changed;
18. anything requiring manual browser verification.

Do NOT commit, merge, push, or delete branches.
Stop after implementation and test reporting.
