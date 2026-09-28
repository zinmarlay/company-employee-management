We are starting Phase 08: Employee Advanced Search.

Current branch:
feature/employee-advanced-search

IMPORTANT:
Do NOT implement anything yet.
Do NOT modify production code.
Do NOT modify tests yet.
Do NOT create migrations.
Do NOT commit, merge, or push.

First inspect the current Employee implementation, database schema,
repositories, services, controllers, routes, SSR views, localization,
organization read repositories, tests, and the recently completed Branch and
Department redesigns.

Then create ONLY:

docs/specs/08-employee-advanced-search.md

==================================================
GOAL
==================================================

Add professional server-side Employee search, filtering, sorting, pagination,
and result-count behavior to the existing employee list.

This is NOT a SPA and NOT a client-side filtering feature.

The feature must use:

- Pure PHP
- PDO
- existing SSR architecture
- GET query parameters
- prepared SQL
- existing localization
- existing repository/service/controller boundaries

==================================================

1. # DEFAULT LIST BEHAVIOR

Approved default behavior:

- show both active and inactive employees;
- default sort: employee_code ASC;
- default page size: 20 employees;
- default page: 1.

Example:

EMP000001
EMP000002
EMP000003
...

The list must have deterministic/stable ordering.

If employee_code alone is not sufficient as a technical tie-breaker,
recommend an appropriate secondary ordering such as id ASC.

================================================== 2. KEYWORD SEARCH
==================================================

One keyword input searches across:

- employee code;
- employee name;
- email.

The search is OR within the keyword group.

Conceptually:

(
employee_code LIKE keyword
OR name LIKE keyword
OR email LIKE keyword
)

If other filters are present, the keyword group combines with them using AND.

Example:

keyword = "山田"
branch = Tokyo
status = active

means:

(
employee_code matches "山田"
OR name matches "山田"
OR email matches "山田"
)
AND branch = Tokyo
AND status = active

Use prepared statements.

Do not concatenate raw request values into SQL.

Inspect current employee name storage and document exactly which name columns
must participate in the keyword search.

================================================== 3. FILTERS
==================================================

Approved filters:

- Branch
- Department
- Employee Type
- Status

Employee Type values should follow the existing canonical application values,
for example:

- regular
- dispatched

Status values should follow the existing employee lifecycle values:

- active
- inactive

Do not invent new database status/type values.

All selected filters combine using AND.

Example:

Tokyo Branch

- Development Department
- Regular Employee
- Active

returns only employees satisfying ALL four filters.

================================================== 4. BRANCH / DEPARTMENT RELATIONSHIP
==================================================

Inspect how Branch and Department choices are currently loaded.

The specification must define correct behavior when both are selected.

A Department belongs to a Branch.

A malicious or manually constructed query must not produce logically invalid
filter semantics.

Example:

branch_id = Tokyo
department_id = a department belonging to Osaka

must not accidentally broaden the search.

Prefer a safe outcome such as zero results or explicit filter validation.

Document the chosen behavior.

For the SSR UI, propose how Department choices should behave when Branch is
selected.

Do NOT introduce a frontend framework.

JavaScript may be used only as progressive enhancement if truly useful.
Server-side correctness must not depend on JavaScript.

================================================== 5. ACTIVE / INACTIVE ORGANIZATION FILTER OPTIONS
==================================================

The employee list includes historical inactive employees.

Therefore inspect whether Branch and Department filter selectors should include
inactive organization records when they are needed to search historical
employee relationships.

Do not blindly reuse active-only assignment selectors if that would make
historical employees impossible to filter.

The specification must distinguish:

- organization choices for assigning a new employee;
- organization choices for searching historical employees.

Historical readability must be preserved.

================================================== 6. QUERY PARAMETERS
==================================================

Use GET query parameters.

Propose a stable query contract such as:

/employees?keyword=山田
&branch_id=1
&department_id=2
&employee_type=regular
&status=active
&sort=employee_code
&direction=asc
&page=2

The exact parameter names should match existing project conventions where
possible.

All search/filter/sort parameters must survive pagination links.

Changing search/filter criteria should conceptually return to page 1.

Empty parameters should behave as "no filter".

Unknown or malformed values must be handled safely.

================================================== 7. SORTING
==================================================

Support a small explicit whitelist of sortable fields.

Inspect the current employee list and recommend the useful initial fields.

At minimum consider:

- employee_code
- name
- branch
- department
- employee_type
- status

Do NOT allow arbitrary request values to become SQL column names.

Map public sort keys to hard-coded SQL expressions.

Direction must be limited to:

- asc
- desc

Unknown sort keys/directions must safely fall back to approved defaults or
produce a controlled validation outcome.

Approved default:

employee_code ASC

Always use deterministic ordering with an appropriate tie-breaker.

================================================== 8. PAGINATION
==================================================

Approved page size:

20 employees per page.

Use server-side SQL pagination.

The specification must define:

- LIMIT;
- OFFSET;
- page normalization;
- total matching count;
- total pages;
- current page;
- behavior when page exceeds the final page;
- preservation of all query parameters.

Do not load all employees into PHP and paginate arrays.

Use a COUNT query for the filtered result set.

COUNT and data queries must use equivalent filtering semantics.

Protect against unreasonable/negative page values.

================================================== 9. RESULT COUNT
==================================================

Display total matching employee count.

Examples:

4 employees
27 employees
0 employees

The count must represent the filtered result set, not only the current page.

EN/JA localization is required.

The specification should propose natural labels in both languages.

================================================== 10. EMPTY RESULTS
==================================================

When no employee matches:

- keep the current search/filter form visible;
- show a localized empty-state message;
- do not treat zero results as an application error;
- allow filters to be cleared/reset.

Recommend a clear "Reset" / "Clear filters" action.

================================================== 11. INPUT NORMALIZATION / VALIDATION
==================================================

Design a dedicated search criteria/query DTO rather than passing raw `$_GET`
through layers.

Consider something like:

EmployeeSearchCriteria

containing normalized values for:

- keyword;
- branchId;
- departmentId;
- employeeType;
- status;
- sort;
- direction;
- page;
- perPage.

The exact design should fit the current architecture.

Rules should include:

- trim keyword;
- blank keyword -> null;
- positive integer IDs only;
- whitelist employee types;
- whitelist statuses;
- whitelist sort keys;
- whitelist direction;
- page >= 1;
- fixed perPage = 20.

Do not expose raw GET arrays to repository SQL construction.

================================================== 12. REPOSITORY DESIGN
==================================================

Inspect the existing EmployeeRepository interfaces.

Propose the smallest clean repository API for advanced search.

Prefer a result shape that provides:

- current-page employee rows;
- total matching count.

Possible approaches:

search(EmployeeSearchCriteria $criteria): EmployeeSearchResult

or separate:

search(...)
countSearch(...)

Choose based on the current architecture.

Avoid:

- N+1 queries;
- duplicated filter-building logic;
- raw SQL fragments supplied from controller/service;
- arbitrary ORDER BY input.

Use explicit JOINs for branch/department data needed by filters and sorting.

================================================== 13. SQL SAFETY
==================================================

The specification must explicitly address:

- prepared statements for keyword/filter values;
- safe LIKE parameter binding;
- escaping or documenting `%` / `_` wildcard semantics;
- integer LIMIT/OFFSET handling;
- ORDER BY whitelist mapping;
- no raw user-controlled SQL identifiers;
- equivalent WHERE clauses between COUNT and data queries.

Inspect the project's current PDO conventions and follow them.

================================================== 14. SERVICE RESPONSIBILITY
==================================================

EmployeeService should own application-level search behavior.

It should:

- normalize/validate criteria through the appropriate validator/parser;
- obtain filter choices;
- call the repository;
- construct pagination/view data;
- preserve normalized query state;
- keep controller thin.

Repository owns SQL/query execution.

View owns presentation.

Do not put SQL/search business logic in the controller or view.

================================================== 15. CONTROLLER / HTTP BEHAVIOR
==================================================

Employee list remains:

GET /employees

No new REST API is required.

Search/filter/sort/pagination all use GET.

The controller should pass query data to the service and render the existing
employee index view.

Normal search results, including zero matches, return HTTP 200.

Malformed query input must have a documented controlled behavior.

================================================== 16. UI
==================================================

Design a compact SSR search/filter interface above the employee table.

Suggested structure:

Keyword
[ Search by code, name, or email ]

Branch
[ All branches ▼ ]

Department
[ All departments ▼ ]

Employee Type
[ All types ▼ ]

Status
[ All statuses ▼ ]

[ Search ] [ Reset ]

Sorting may be:

- clickable table headers;
  or
- explicit sort controls.

Choose the option most consistent with the current Material Design-inspired
UI.

The UI must remain usable in both EN and JA.

Do not introduce React/Vue or a CSS framework.

================================================== 17. PAGINATION UI
==================================================

Pagination should provide practical navigation such as:

Previous
1
2
3
Next

Avoid rendering hundreds of page-number links for large result sets.

Specify a bounded page-window strategy if needed.

Every pagination URL must preserve:

- keyword
- branch
- department
- employee type
- status
- sort
- direction

except `page`, which changes.

================================================== 18. QUERY STATE
==================================================

The form must preserve selected/current values after search.

Examples:

- keyword remains visible;
- selected Branch remains selected;
- selected Department remains selected;
- selected Employee Type remains selected;
- selected Status remains selected;
- current sort state can be understood by the user.

Reset should return to:

/employees

which means:

- no keyword;
- no filters;
- employee_code ASC;
- page 1;
- 20 per page.

================================================== 19. HISTORICAL DATA
==================================================

Search must work for:

- active employees;
- inactive employees;
- employees associated with inactive departments;
- employees associated with inactive branches.

Do not hide historical employees merely because their organization is now
inactive.

Default list includes both active and inactive employees.

Status filter determines whether the user explicitly narrows lifecycle state.

================================================== 20. PERFORMANCE
==================================================

Inspect relevant indexes from existing migrations.

The specification must discuss whether existing indexes are sufficient for:

- employee_code;
- email;
- branch_id;
- department_id;
- employee_type;
- status;
- branch/code relationships.

Do not add indexes or migrations yet.

If an index migration would materially improve the approved design, report it
as a review decision rather than implementing it.

Remember that leading-wildcard LIKE searches may not use ordinary indexes
efficiently. Document the tradeoff without prematurely introducing full-text
search.

================================================== 21. TEST PLAN
==================================================

The specification must include comprehensive tests.

At minimum:

Keyword:

- employee code match;
- name match;
- email match;
- no match;
- blank keyword;
- special LIKE characters.

Filters:

- branch only;
- department only;
- employee type only;
- status only;
- all filters combined with AND;
- keyword + filters;
- inactive employee filter;
- historical inactive organization relationships.

Branch/Department:

- valid matching pair;
- mismatched branch/department pair;
- missing IDs;
- malformed IDs.

Sorting:

- default employee_code ASC;
- each approved sort field;
- ASC;
- DESC;
- invalid sort key;
- invalid direction;
- deterministic tie-breaker.

Pagination:

- first page;
- middle page;
- final page;
- page < 1;
- page beyond final page;
- exactly 20;
- 21 records;
- total count;
- query state preserved.

Repository integration:

- COUNT and rows use identical filters;
- LIMIT/OFFSET;
- JOIN-based branch/department filters;
- inactive history;
- stable sorting;
- prepared-value behavior.

HTTP/UI:

- GET /employees default;
- search form;
- selected values preserved;
- pagination links preserve filters;
- sorting preserves filters;
- reset URL;
- zero-result empty state;
- EN/JA labels.

Regression:

- employee detail;
- create;
- edit;
- inactive employee rules;
- branch/department redesign behavior;
- dispatch relationships where relevant.

================================================== 22. OUT OF SCOPE
==================================================

Explicitly exclude:

- Employee Portfolio;
- Skills;
- Projects;
- Certifications;
- System User management;
- authentication redesign;
- Branch Redesign;
- Department Redesign;
- employee code redesign;
- employee status redesign;
- full-text search engine;
- autocomplete;
- saved searches;
- CSV export;
- client-side data grid;
- SPA/API redesign.

================================================== 23. SPEC CONTENT
==================================================

docs/specs/08-employee-advanced-search.md must include:

1. Purpose
2. Current-state findings
3. Existing employee list/query behavior
4. Existing schema/index findings
5. Problems being solved
6. Goals
7. Non-goals
8. Search criteria model
9. Keyword semantics
10. Filter semantics
11. Branch/Department relationship behavior
12. Historical organization filter behavior
13. Sorting design
14. Pagination design
15. Result count
16. Query parameter contract
17. Input normalization/validation
18. Repository design
19. SQL/query design
20. Service design
21. Controller behavior
22. SSR UI design
23. Pagination UI
24. Localization
25. Empty/reset behavior
26. Performance/index analysis
27. Test plan
28. Expected files to change
29. Migration decision
30. Acceptance criteria
31. Risks / decisions requiring review

Do NOT implement.

After writing the specification, report:

1. what you inspected;
2. current employee list limitations;
3. proposed EmployeeSearchCriteria;
4. exact keyword matching semantics;
5. exact filter AND semantics;
6. branch/department mismatch behavior;
7. historical inactive organization handling;
8. approved sortable fields;
9. pagination behavior;
10. repository/query design;
11. whether any migration/index change is recommended;
12. decisions or risks requiring review.

# implement prompt

Implement the approved Phase 08 Employee Advanced Search specification:

docs/specs/08-employee-advanced-search.md

Current branch:
feature/employee-advanced-search

IMPORTANT:

- Implement only the approved specification.
- Do not commit, merge, or push.
- Do not add a migration.
- Do not redesign unrelated modules.
- Preserve the current Pure PHP SSR architecture.
- Preserve all existing Employee inactive-record protections.
- Preserve Branch and Department redesign behavior.

==================================================
APPROVED DEFAULT BEHAVIOR
==================================================

GET /employees

Default:

- active + inactive employees;
- page = 1;
- perPage = 20;
- sort = employee_code;
- direction = asc;
- deterministic tie-breaker using id where appropriate.

==================================================
SEARCH
==================================================

One keyword searches:

- employee_code
- first_name
- last_name
- first_name_kana
- last_name_kana
- email

Keyword columns use OR inside one parenthesized group.

Keyword group combines with filters using AND.

Example:

(keyword matches code/name/kana/email)
AND branch_id = ...
AND department_id = ...
AND employee_type = ...
AND status = ...

Trim keyword.

Blank keyword means no keyword filter.

Escape LIKE wildcard characters %, \_, and backslash so keyword matching is
literal contains matching.

Use prepared parameters.

==================================================
FILTERS
==================================================

Support:

- branch_id
- department_id
- employee_type
- status

Canonical employee types MUST come from the existing application/schema.
The inspected specification found:

- permanent
- dispatched

Do NOT invent "regular" if the actual canonical value is "permanent".

Statuses:

- active
- inactive

All selected filters combine using AND.

==================================================
BRANCH / DEPARTMENT
==================================================

When both branch_id and department_id are supplied:

- both predicates must remain active;
- never silently remove either filter;
- never broaden the result.

If Department belongs to another Branch:

→ HTTP 200
→ zero results
→ preserve the selected criteria.

The same zero-result behavior applies to syntactically valid positive
organization IDs that do not match stored employee relationships.

==================================================
HISTORICAL SEARCH
==================================================

Employee assignment choices remain:

- active Branches only;
- active Departments only.

Employee SEARCH choices must include:

- active Branches;
- inactive Branches;
- active Departments;
- inactive Departments.

Introduce separate search read methods such as listForSearch() if that is the
cleanest fit.

Do not change listActive() assignment semantics.

Employees must remain searchable when:

- employee is inactive;
- Branch is inactive;
- Department is inactive.

==================================================
SEARCH CRITERIA
==================================================

Introduce a dedicated normalized EmployeeSearchCriteria DTO.

It should represent:

- keyword
- branchId
- departmentId
- employeeType
- status
- sort
- direction
- page
- perPage

perPage is fixed at 20.

Do not pass raw $\_GET arrays into repository SQL construction.

Use a dedicated parser/validator consistent with the existing application
architecture.

Malformed ordinary search values should normalize safely according to the
approved specification rather than producing application exceptions.

==================================================
SEARCH RESULT
==================================================

Introduce a clean result object/value object such as EmployeeSearchResult.

It should provide enough data for:

- current-page rows;
- total matching count;
- current page;
- total pages;
- per-page value;
- normalized query state if appropriate.

Do not make the view infer total counts from the current row array.

==================================================
SORTING
==================================================

Approved public sort keys:

employee_code
name
branch
department
employee_type
status

Map them internally to fixed SQL expressions.

Never interpolate arbitrary user-controlled SQL identifiers.

Directions:

asc
desc

Invalid sort:
→ employee_code

Invalid direction:
→ asc

Default:
employee_code ASC

Use deterministic secondary ordering.

==================================================
PAGINATION
==================================================

Server-side pagination only.

Do NOT load all employees and paginate in PHP.

Fixed page size:
20

Use:
LIMIT
OFFSET

Run a filtered COUNT query.

COUNT and data queries must have equivalent filtering semantics.

Behavior:

missing page
→ 1

page <= 0 / malformed
→ 1

page beyond final page
→ clamp to final page

zero results
→ HTTP 200
→ current page 1
→ total pages 1

Pagination links must preserve:

- keyword
- branch_id
- department_id
- employee_type
- status
- sort
- direction

and change only page.

==================================================
REPOSITORY
==================================================

Add the smallest clean Employee repository search API.

Preferred:

search(EmployeeSearchCriteria $criteria): EmployeeSearchResult

Avoid duplicated WHERE construction between:

COUNT query
and
data query.

Use a shared private filter builder or equivalent clean mechanism.

Use the existing joins:

employees
INNER JOIN branches
LEFT JOIN departments

Do not add organization active-status predicates to employee search.

Use prepared statements.

LIMIT/OFFSET must come only from normalized integers.

==================================================
SERVICE / CONTROLLER
==================================================

EmployeeService owns the search workflow:

- normalize criteria;
- load historical search choices;
- call repository;
- prepare view/pagination/query state.

EmployeeController remains thin.

GET /employees remains the route.

No new REST endpoint.

No API.

No SPA.

==================================================
UI
==================================================

Add a compact search/filter area above the Employee table.

Controls:

Keyword
Branch
Department
Employee Type
Status
Sort
Direction

Actions:

Search
Reset filters

Reset URL:

/employees

Preserve selected values after search.

Inactive Branch/Department options must be identifiable using the existing
inactive display convention.

When Branch is selected, server-render Department options for that Branch,
including inactive departments.

Do not depend on JavaScript for correctness.

Keep existing Material Design-inspired styling.

==================================================
RESULT COUNT / EMPTY STATE
==================================================

Display total filtered employee count.

EN and JA required.

Zero results:

- HTTP 200;
- keep search form visible;
- keep selected criteria;
- show localized empty state;
- show Reset filters action.

==================================================
INACTIVE EMPLOYEE ACTION RULE
==================================================

Do NOT regress the existing rule.

Active employee:

- Detail
- Edit
- Deactivate

Inactive employee:

- Detail only

Direct edit/update protection for inactive employees must remain intact.

==================================================
LOCALIZATION
==================================================

Add appropriate EN/JA translation keys for:

- keyword search
- filter labels
- all options
- sorting
- direction
- result count
- empty result
- reset
- pagination
- inactive organization labels/accessibility text

Do not unnecessarily hard-code user-facing strings in views.

==================================================
TESTS
==================================================

Implement the complete test plan from the approved specification.

At minimum verify:

Keyword:

- employee_code
- first_name
- last_name
- first_name_kana
- last_name_kana
- email
- blank
- no match
- literal %, \_, backslash behavior

Filters:

- branch
- department
- employee_type
- status
- all filters combined
- keyword + filters

History:

- inactive employee
- inactive Branch
- inactive Department

Branch/Department:

- matching pair
- mismatched pair returns zero
- no filter broadening

Sorting:

- all approved sort fields
- asc/desc
- invalid fallback
- stable ordering

Pagination:

- 0 results
- first page
- middle page
- final page
- 20 results
- 21 results
- out-of-range page
- total count
- query preservation

HTTP/UI:

- default GET /employees
- search controls
- selected values
- historical organization options
- count
- empty state
- reset
- pagination
- EN/JA

Regression:

- Employee detail
- create
- edit
- deactivate
- inactive Employee protections
- Branch redesign
- Department redesign
- Dispatch relationships

==================================================
DATABASE
==================================================

Do NOT add a migration.

Do NOT add speculative indexes.

Existing indexes are sufficient for this phase.

If implementation reveals an actual schema blocker, STOP and report it
instead of creating a migration.

==================================================
DOCUMENTATION
==================================================

Create:

docs/prompts/08-employee-advanced-search.md

Keep:

docs/specs/08-employee-advanced-search.md

consistent with the final implementation.

Do not weaken the approved business rules.

==================================================
FINAL VERIFICATION
==================================================

After implementation run:

composer test

Do not commit.

Report:

1. files added;
2. files modified;
3. EmployeeSearchCriteria design;
4. EmployeeSearchResult design;
5. keyword SQL behavior;
6. filter AND behavior;
7. historical Branch/Department search behavior;
8. sorting whitelist implementation;
9. pagination/count implementation;
10. query-state preservation;
11. UI changes;
12. test count;
13. assertion count;
14. skipped tests;
15. failures/errors;
16. PHPUnit deprecations;
17. confirmation that no migration was added;
18. any deviation from the approved specification.

Do NOT commit.
Do NOT merge.
Do NOT push.
