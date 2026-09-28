# Organization Display Localization Specification

Status: design specification only. This document does not implement the
cleanup.

## 1. Purpose

Provide consistent EN/JA presentation for catalog-backed Branch and
Department identities across the application.

Persisted organization records remain the source of historical/domain data.
Localization is a presentation concern: known catalog codes receive the
current locale's catalog label, while unknown legacy/custom codes retain
their persisted names.

The cleanup includes organization labels shown as values, headings, table
cells, read-only fields, confirmation text, and employee organization
information. It does not translate ordinary business data.

## 2. Current-state findings

### 2.1 Catalogs

PrefectureCatalog is the authoritative Branch identity catalog. It maps a
canonical Branch/prefecture code such as TOKYO, OSAKA, or CHIBA to Japanese
and English identity information and the canonical Japanese Branch name.

DepartmentCatalog is the authoritative Department identity catalog. It maps
codes such as DEV, SALES, HR, and GA to Japanese and English labels.

The catalogs do not read the database and must remain the only code-to-label
mapping for known catalog identities.

### 2.2 Translation and locale

LocaleMiddleware selects EN or JA on each request and updates the shared
Translator. ViewRenderer exposes the active locale and translator to views.
Generic UI strings and lifecycle indicators continue to use Translator;
catalog identity labels come from the catalogs through application display
mapping.

### 2.3 Phase 08 Employee Advanced Search

Phase 08 already provides:

- active and inactive Branch and Department search choices;
- Branch to Department dependent filtering;
- server-prepared localized filter labels;
- vanilla JavaScript that consumes server-prepared Department labels;
- Employee search result rows with presentation-only localized Branch and
  Department display fields;
- persisted-name fallback for unknown search-result organization codes.

Its search predicates, IDs, AND semantics, sorting, pagination, and repository
data must not change in this cleanup.

### 2.4 Repository data

Employee search rows already contain the canonical identity and persisted
name fields needed for presentation:

- branch_code, branch_name, branch_status;
- department_code, department_name, department_status.

Branch and Department read choices expose their own code, persisted name, ID,
and status, with Department choices also carrying the parent Branch code/name.
Repositories return canonical/persisted data only; they do not produce
locale-specific SQL values.

## 3. Screens audited

| Area | Screens/layers audited | Current status |
|---|---|---|
| Employee | list/search, detail, create, edit, deactivate confirmation | Search labels/results are localized by Phase 08; detail and assignment choices still need the shared mapping |
| Branch | list, detail, create, edit, deactivate confirmation | Catalog prefecture choices are localized; persisted Branch display names and related Departments are not consistent |
| Department | list, detail, create, edit, deactivate confirmation | Department type choices are localized; Branch choices and persisted Department/Branch names are not consistent |
| Dispatch | dispatch company list/detail/forms; contract create/edit/renew/detail/history | These screens display dispatch companies, employees, and contract data, but no Branch/Department names directly |
| Shared UI | layout, page headers, status chips, empty states, form conventions | Generic labels and status text use Translator; no shared organization display resolver exists |
| Persistence/application | read repositories, organization services, EmployeeService, catalogs | Repositories are canonical-data sources; organization display mapping is split and incomplete |

## 4. Current inconsistencies

The following displays can expose persisted Japanese organization names in
English:

- Employee detail Branch and Department fields;
- Employee create/edit Branch options;
- Employee create/edit Department option groups and labels;
- Branch list Branch name column;
- Branch detail title and related Department names;
- Branch edit read-only Branch name;
- Branch deactivation confirmation;
- Department list Branch and Department columns;
- Department detail title, parent Branch, and Department information;
- Department create Branch choices;
- Department edit read-only parent Branch and Department name;
- Department deactivation confirmation.

Already correct or partially correct:

- Phase 08 Employee filter options and search result rows use catalog-backed
  display labels;
- Phase 08 Department JavaScript receives server-prepared labels;
- Branch create prefecture options use catalog locale labels;
- Department create Department type options use catalog locale labels;
- generic EN/JA UI labels and inactive indicators use Translator;
- dispatch company and contract screens do not currently expose Branch or
  Department names directly;
- dashboard and shared navigation do not currently expose organization names.

## 5. Localization boundary

Localization applies only to catalog-defined organization identity:

- Branch identity is resolved by Branch/prefecture code;
- Department identity is resolved by Department code;
- a Department parent is resolved by its Branch code;
- inactive status is appended separately using the existing localized status
  convention.

Localization does not apply to Company names or codes, City, Address, Phone,
Employee names, Position titles, Department descriptions, arbitrary
user-entered metadata, or persisted text without an authoritative catalog
mapping.

## 6. Catalog-backed display rules

Known Branch labels:

| Code | EN | JA |
|---|---|---|
| TOKYO | Tokyo Branch | 東京支店 |
| OSAKA | Osaka Branch | 大阪支店 |
| CHIBA | Chiba Branch | 千葉支店 |

Known Department labels:

| Code | EN | JA |
|---|---|---|
| DEV | Development | 開発部 |
| SALES | Sales | 営業部 |
| HR | Human Resources | 人事部 |
| GA | General Affairs | 総務部 |
| FIN | Accounting | 経理部 |
| IT | Information Systems | 情報システム部 |
| LEGAL | Legal | 法務部 |
| PR | Public Relations | 広報部 |
| PL | Planning | 企画部 |
| CS | Customer Support | カスタマーサポート部 |

Department choices and tables use:

- EN: Tokyo Branch · Development;
- JA: 東京支店・開発部.

The separator is presentation formatting, not persisted data or a search
value.

## 7. Legacy/custom fallback rules

The display resolver must:

1. normalize and look up the canonical code in the relevant catalog;
2. use the catalog label for the active locale when found;
3. otherwise return the persisted name unchanged.

An unknown Branch code with persisted name 横浜支店 remains 横浜支店 in
English. The application must not infer Yokohama Branch from Japanese text.
The same rule applies to unknown Department codes and unknown parent Branch
codes in Department rows.

Fallback must preserve HTML escaping and must not make historical records
unreadable.

## 8. User-entered data rules

User-entered and historical business data remains unchanged and
untranslated:

- サンプル株式会社;
- 板橋区;
- 東京都...;
- 山田 太郎;
- シニアエンジニア;
- Department descriptions.

The cleanup applies to catalog-defined organization display identity, not
arbitrary business text.

## 9. Proposed architecture

Introduce one reusable application/presentation mapper,
OrganizationDisplayNameResolver, backed by PrefectureCatalog,
DepartmentCatalog, and the active locale.

The resolver should provide methods equivalent to:

- resolve Branch display name from code and persisted name;
- resolve Department display name from code and persisted name;
- resolve a Department parent Branch display name;
- resolve a combined Branch/Department display label;
- preserve unknown-code fallbacks.

The resolver returns display values separately from canonical fields. It must
not overwrite code, id, or persisted name in repository data.

Application services prepare view-model display fields before rendering:

- branch_display_name;
- department_display_name;
- display_label for Department choices;
- equivalent fields for Branch/Department management view models.

Controllers remain thin. Views render prepared display fields and do not
contain code-to-label conditionals. JavaScript receives the same prepared
labels as JSON/HTML metadata and contains no catalog mappings.

The existing Phase 08 Employee mapping should be delegated to this resolver
so Employee search, Employee detail/forms, Branch screens, and Department
screens share one behavior.

## 10. PrefectureCatalog changes

Retain PrefectureCatalog as the single Branch identity source.

The catalog API must support:

- canonical code lookup;
- Japanese Branch display label;
- English Branch display label;
- existing prefecture labels used by Branch creation/edit forms.

Reuse the existing branch label method where available. Do not add view-local
TOKYO/OSAKA maps or translation-file mappings for Branch identity.

Unknown codes return no catalog label so the resolver can apply the
persisted-name fallback.

## 11. DepartmentCatalog changes

Retain DepartmentCatalog as the single Department identity source.

The catalog API must support:

- canonical code lookup;
- Japanese Department display label;
- English Department display label.

Reuse the existing locale-aware Department label method where available.
Unknown codes return no catalog label so the resolver can preserve the
persisted name.

No Department database data or catalog identity rules change.

## 12. Employee screen behavior

### 12.1 List/search

Employee filter options, result-row Branch cells, and result-row Department
cells all use the resolver:

- TOKYO displays Tokyo Branch in EN and 東京支店 in JA;
- DEV displays Development in EN and 開発部 in JA;
- unknown codes display persisted names.

Inactive organization choices remain available for historical search and keep
the localized inactive suffix. Search predicates and result membership remain
unchanged.

### 12.2 Detail

Employee detail displays localized Branch and Department identity using their
codes, with persisted-name fallback. Employee names, position, and other
business data remain unchanged.

The detail page remains read-only for organization identity and preserves
inactive Employee protections.

### 12.3 Create/edit

Employee assignment choices use localized display labels but preserve:

- active Branches only;
- active Departments only;
- unchanged submitted IDs;
- Department grouping under the correct Branch;
- immutable organization identity.

Existing inactive-current assignment compatibility and disabled-option
behavior remain intact.

## 13. Branch screen behavior

### 13.1 List

Show the canonical Branch code and localized Branch display name:

TOKYO | Tokyo Branch in EN and TOKYO | 東京支店 in JA.

Company, city, address, phone, counts, and other metadata remain persisted
values.

### 13.2 Detail

Localize the Branch title/name and related Department identity shown on the
Branch detail page. Keep Branch code, company, city, address, phone, status,
and timestamps unchanged.

### 13.3 Create/edit

Branch creation prefecture choices remain catalog-backed and localized.
Immutable Branch code/name fields remain immutable after creation. Read-only
identity displays use the resolver; city, address, phone, and company keep
their existing behavior.

### 13.4 Deactivate confirmation

Use the localized Branch display name in confirmation text while preserving
the existing active-only action and inactive-record protections.

## 14. Department screen behavior

### 14.1 List

Localize Department and parent Branch display columns while retaining
canonical code columns:

DEV | Development and TOKYO | Tokyo Branch in EN;
DEV | 開発部 and TOKYO | 東京支店 in JA.

Descriptions, employee counts, status, and timestamps remain unchanged.

### 14.2 Detail

Localize the Department title, Department identity, and parent Branch
identity. Preserve code, description, employees, status, and lifecycle
actions.

### 14.3 Create/edit

Department type choices remain catalog-backed and localized. Parent Branch
choices and read-only Branch/Department identity fields use the resolver.
Branch, Department code, Department name, and status immutability remain
unchanged.

### 14.4 Deactivate confirmation

Use localized Department and parent Branch display names where shown. Keep
the existing active-only deactivation behavior and fully read-only inactive
Department behavior.

## 15. Dispatch screen behavior

The audit found no direct Branch or Department display in dispatch company
screens or dispatch contract create/edit/renew/detail/history screens.
Dispatch choices and history display dispatch companies, employee codes, and
employee names.

No Dispatch production change is required unless a future view introduces
Branch or Department identity. Such a view must consume the shared resolver.
Dispatch company names remain user-entered business data and are out of
scope.

## 16. Inactive-record display

Localized identity and lifecycle status are separate concerns:

- EN: Tokyo Branch (inactive);
- JA: 東京支店（無効）;
- EN: Tokyo Branch · Development (inactive);
- JA: 東京支店・開発部（無効）.

The existing localized inactive suffix from Translator remains the source for
the indicator. The resolver supplies only the identity label.

Inactive records remain readable. This cleanup adds no edit, update,
deactivate, or delete capabilities.

## 17. Assignment/search choice behavior

Employee create/edit assignment choices remain active-only.

Employee historical search choices include active and inactive Branches and
Departments. Selecting a Branch continues to filter Department choices to
that Branch, including historical records. A mismatched manually submitted
Branch/Department query remains an AND query and returns zero employees.

Localization changes labels only. IDs, codes, relationship checks, status
eligibility, and submitted query values remain unchanged.

## 18. JavaScript interaction

The Phase 08 dependent Department dropdown remains a progressive enhancement:

- the server renders authoritative localized Department metadata;
- the script filters by Branch ID;
- the script reuses server-provided display_label;
- changing Branch resets an incompatible Department selection;
- valid Department selections remain selected;
- no AJAX, API, framework, or client-side catalog map is introduced.

With JavaScript disabled, server-rendered filtering and form submission remain
fully correct.

## 19. Sorting/search impact

This cleanup does not change search or sorting semantics:

- Employee predicates continue to use IDs, codes, and persisted columns;
- organization relationships continue to use IDs and canonical data;
- the approved Phase 08 sort whitelist remains unchanged;
- Branch/Department sort behavior may continue to use persisted database
  names.

Localized display sorting is out of scope. Introducing it later requires a
reviewed product decision because it can change ordering between locales.

## 20. Persistence guarantees

No repository write behavior changes.

The implementation must not:

- update Branch or Department names;
- add English-name columns;
- rewrite historical Japanese names;
- change Branch/Department codes or IDs;
- alter status transitions;
- change employee assignments;
- change search predicates.

Repository projections continue to return canonical identity and persisted
names. Application mapping adds presentation fields only.

## 21. Localization keys

Continue using Translator keys for:

- EN/JA UI labels;
- active/inactive status;
- inactive suffix;
- form and confirmation text;
- empty states and accessibility labels.

Catalog identity labels remain in PrefectureCatalog and DepartmentCatalog.
Do not duplicate identity mappings in resources/lang, controllers, views, or
JavaScript.

If a separator or display-format string becomes a user-visible localized
convention, add a generic translation key rather than embedding catalog
mappings. The approved default is middle dot in EN and Japanese middle dot
in JA.

## 22. Test plan

### 22.1 Catalog tests

Test known Branch EN/JA labels, known Department EN/JA labels, and unknown
codes returning no catalog label for fallback.

### 22.2 Resolver/application tests

Test:

- known labels are placed in presentation fields;
- unknown Branch/Department codes return persisted names;
- canonical IDs/codes and persisted names remain present and unchanged;
- inactive suffix is added by presentation mapping, not by catalogs.

### 22.3 Employee HTTP tests

In EN, verify:

- Employee result Branch displays Tokyo Branch;
- Employee result Department displays Development;
- filter options use the same labels;
- unknown/custom result and filter choices retain persisted names;
- known inactive choices use localized identity plus inactive suffix.

In JA, verify:

- Employee result Branch displays 東京支店;
- Employee result Department displays 開発部;
- filter options and inactive indicators are Japanese.

Regression tests verify dependent filtering, mismatched zero-result behavior,
stable IDs/query values, unchanged result membership, and active/inactive
historical choices.

### 22.4 Branch HTTP tests

Test Branch list, detail, edit, and deactivation confirmation for known EN/JA
labels, related Department labels, unknown-code fallback, inactive indicators,
unchanged identity/metadata, and action protections.

Test Branch create/edit choices remain catalog-backed and immutable identity
rules remain enforced.

### 22.5 Department HTTP tests

Test Department list, detail, edit, and deactivation confirmation for known
EN/JA labels, parent Branch labels, unknown-code fallback, inactive
indicators, unchanged identity/description, and lifecycle protections.

Test Department create type choices and parent Branch choices are localized
without changing active-only creation rules.

### 22.6 Dispatch and shared-screen tests

Confirm dispatch company/contract screens remain unchanged because they do not
display Branch or Department identities. If a future fixture includes such
identities, assert that the shared resolver is used.

## 23. Expected files to change

Expected implementation files:

- src/Application/Organization/OrganizationDisplayNameResolver.php (new);
- src/Domain/Organization/PrefectureCatalog.php;
- src/Domain/Organization/DepartmentCatalog.php;
- src/Application/Employee/EmployeeService.php;
- src/Application/Organization/BranchService.php;
- src/Application/Organization/DepartmentService.php;
- relevant view-model mapping code;
- Employee, Branch, and Department views;
- existing localization and Employee/Branch/Department HTTP tests.

Repositories should change only if an audited screen lacks canonical code
fields. SQL predicates and sort expressions must not change.

Expected non-changes:

- dispatch views unless a direct organization display is found;
- persistence writes;
- migrations;
- schema.

The final implementation file list must be confirmed after the audit is
converted into view-model changes.

## 24. Migration decision

No migration is required or permitted.

Do not add English-name columns, rewrite persisted names, or backfill
historical records. Existing data remains compatible because unknown catalog
codes fall back to persisted names.

## 25. Acceptance criteria

The implementation is accepted when:

1. known Branch identities display the correct EN/JA catalog label on every
   audited Branch, Department, and Employee screen;
2. known Department identities display the correct EN/JA catalog label on
   every audited Department and Employee screen;
3. unknown Branch and Department codes remain readable using persisted names;
4. Companies, cities, addresses, employee names, positions, descriptions, and
   other user-entered data are not automatically translated;
5. inactive indicators remain localized and separate from identity labels;
6. Employee create/edit choices remain active-only;
7. Employee historical search choices include active and inactive records;
8. Branch to Department filtering and no-JavaScript server behavior remain
   correct;
9. Employee search predicates, sort semantics, result membership, IDs, and
   query values remain unchanged;
10. immutable Branch/Department identity and inactive-record protections
    remain enforced;
11. no catalog mapping is duplicated in views, controllers, JavaScript, or
    unrelated translation resources;
12. no persisted organization names or database schema objects change;
13. the full test suite passes with documented skipped tests and
    deprecations understood.

## 26. Risks / decisions requiring review

### 26.1 Detail-screen scope

This specification recommends localizing known organization identity on
Employee detail and Branch/Department detail screens, not only lists and
search filters. Reviewers should confirm this consistent user-facing scope.

### 26.2 Persisted-name fallback

Fallback intentionally leaves unknown Japanese/custom names untranslated in
English. This preserves historical truth and avoids unsafe guessed
translations. An authoritative mapping for a custom code must be added to the
appropriate catalog through a separate reviewed change.

### 26.3 Sorting

Existing persisted-name sorting remains approved for this phase. Locale-aware
sorting is a separate design decision.

### 26.4 Resolver placement

The proposed resolver centralizes mapping in the application layer. Reviewers
should confirm whether it belongs under src/Application/Organization or a
small shared presentation namespace, while preserving thin controllers and
repository neutrality.

### 26.5 Dispatch scope

The audit found no direct Branch/Department labels in dispatch screens.
Reviewers should confirm that employee names alone are sufficient for dispatch
workflows and that no hidden organization label is required.

### 26.6 Catalog evolution

Adding or changing a known identity label affects every locale-aware screen.
Catalog updates must remain explicit, tested, and separate from arbitrary
historical data cleanup.
