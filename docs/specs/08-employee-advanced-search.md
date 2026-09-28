# Phase 08: Employee Advanced Search Specification

Status: reviewed design specification

This specification covers server-side search, filtering, sorting, pagination,
and result counts for the existing Employee list. It does not implement the
feature.

## 1. Purpose

Add a professional, server-rendered Employee directory search using PHP, PDO,
prepared SQL, GET query parameters, the existing service/controller boundaries,
and the existing EN/JA localization system.

The feature remains a normal SSR page at GET /employees. It is not a SPA,
client-side data grid, REST API, or JavaScript-dependent search experience.

## 2. Current-state findings

### 2.1 Employee domain and persistence

The employees table currently stores:

- id;
- branch_id;
- nullable department_id;
- employee_code;
- first_name and last_name;
- first_name_kana and last_name_kana;
- email;
- phone;
- nullable position_title;
- employee_type;
- hire_date;
- status;
- created_at and updated_at.

The existing canonical employee types are permanent and dispatched. The
existing lifecycle statuses are active and inactive. No new type or status
values are introduced.

The employee repository currently exposes listBasic(limit), findById,
duplicate checks, insert, update, and deactivate. It does not expose filtered
search, total counts, or pagination metadata.

### 2.2 Existing list behavior

EmployeeService::listEmployees requests at most 200 rows from
PdoEmployeeRepository::listBasic. The current SQL:

- joins branches with an inner join;
- left joins departments while enforcing the employee branch/department
  relationship;
- returns display fields for employee code, name, branch, department,
  position, type, and status;
- includes active and inactive employees because it has no employee-status
  predicate;
- orders by last_name, first_name, employee_code, and id ascending;
- limits the result without a total-count query.

The current Employee list view renders the table and action links but has no
search form, filter controls, sort controls, pagination, filtered count, or
reset behavior.

### 2.3 Existing organization reads

Employee create and edit forms use BranchReadRepositoryInterface::listActive
and DepartmentReadRepositoryInterface::listActive. These active-only methods
are correct for new assignments and must remain so.

Search needs a separate organization-choice read path that includes inactive
branches and departments. Reusing the active-only assignment methods for
search would make historical employee relationships impossible to filter.

### 2.4 Existing HTTP and localization

GET /employees is already the list route. EmployeeController currently passes
only the unfiltered employee rows to the view. The existing view renderer,
translator, locale middleware, and EN/JA language files are available for the
new form, count, empty state, sort, pagination, and inactive-organization
labels.

Employee inactive-record protections already make inactive employees
detail-only: list/detail edit and deactivate links are hidden, direct edit and
update attempts are blocked, and repeated deactivation is safe. Advanced
search must not weaken those rules.

## 3. Existing schema/index findings

The employee migration currently defines:

- unique employee_code;
- unique email;
- index employees(branch_id, status);
- index employees(branch_id, department_id);
- index employees(last_name, first_name);
- foreign key branch_id to branches.id;
- composite foreign key (branch_id, department_id) to departments(branch_id,
  id);
- database checks for employee_type and status.

Branches have a primary-key lookup and a unique company/code key. Departments
have a primary-key lookup, a unique branch/code key, and a unique
branch/id key. Neither organization table currently has a dedicated status
index.

The composite employee foreign key means a stored department relationship
cannot belong to a different branch. The application must still validate
manually supplied branch_id and department_id search filters explicitly so the
search contract remains correct even with an invalid query.

## 4. Problems being solved

The current list cannot:

- find employees by code, name, or email;
- narrow results by branch, department, employee type, or status;
- sort using a user-selected approved field and direction;
- show more than the first 200 rows;
- report the total number of filtered matches;
- navigate result pages while retaining query state;
- search historical employees through inactive organization records.

## 5. Goals

The feature must:

1. show active and inactive employees by default;
2. search employee code, stored name columns, and email with one keyword;
3. combine keyword and selected filters with AND semantics;
4. support branch, department, employee type, and status filters;
5. support a small explicit sort whitelist;
6. use stable server-side pagination with 20 rows per page;
7. display the total count for the filtered result set;
8. preserve normalized query state in the form and links;
9. keep inactive organization choices available for historical searching;
10. preserve existing assignment, detail, create, edit, and inactive-record
    behavior;
11. support EN and JA SSR output and accessible controls;
12. use prepared SQL with no user-controlled identifiers or SQL fragments.

## 6. Non-goals

This phase does not include:

- Employee Portfolio;
- skills, projects, or certifications;
- System User management;
- authentication redesign;
- Branch Redesign;
- Department Redesign;
- employee code redesign;
- employee status redesign;
- full-text search infrastructure;
- autocomplete;
- saved searches;
- CSV export;
- client-side data grids;
- SPA or API redesign.

## 7. Search criteria model

Add an application DTO named EmployeeSearchCriteria. It contains normalized
search state:

- keyword: nullable string;
- branchId: nullable positive integer;
- departmentId: nullable positive integer;
- employeeType: nullable string;
- status: nullable string;
- sort: one approved public sort key;
- direction: asc or desc;
- page: positive integer;
- perPage: fixed integer 20.

The DTO is created by a dedicated query parser/validator at the application
boundary. Controllers do not pass raw query arrays to the repository.

The repository returns an EmployeeSearchResult containing:

- current-page employee rows;
- total matching count;
- current page;
- total pages;
- per-page size;
- normalized criteria or equivalent link state.

The result object keeps pagination construction out of the controller and
prevents the view from inferring count or page state from row arrays.

## 8. Keyword semantics

One normalized keyword searches the following employee columns:

- employee_code;
- first_name;
- last_name;
- first_name_kana;
- last_name_kana;
- email.

The stored first/last name columns are used because they are the canonical
employee name fields. Kana columns are included so Japanese and kana searches
are useful. Phone, position_title, branch name, and department name are not
part of the keyword group in this phase.

The SQL predicate is logically:

    e.employee_code LIKE :keyword
    OR e.first_name LIKE :keyword
    OR e.last_name LIKE :keyword
    OR e.first_name_kana LIKE :keyword
    OR e.last_name_kana LIKE :keyword
    OR e.email LIKE :keyword

The complete keyword group is parenthesized before other filters are added:

    (keyword column 1 OR keyword column 2 OR ...)
    AND branch filter
    AND department filter
    AND employee type filter
    AND status filter

Blank or whitespace-only keywords become null and add no predicate.

LIKE values are bound parameters. To make the search literal and predictable,
the parser/repository escapes backslash, percent, and underscore in the user
keyword and appends percent wildcards around the escaped value. The SQL uses
an explicit backslash escape clause. User input therefore cannot inject SQL
or unexpectedly turn percent/underscore into wildcard operators.

## 9. Filter semantics

Each selected filter is optional. Selected filters combine with AND:

- branch_id filters e.branch_id;
- department_id filters e.department_id;
- employee_type filters e.employee_type;
- status filters e.status.

The employee type whitelist is permanent and dispatched. The status whitelist
is active and inactive. Empty values mean no filter.

No filter is inferred from an inactive organization. If the user selects an
inactive branch or department, it is a valid historical search choice.

## 10. Branch/Department relationship behavior

A department belongs to one branch. When both IDs are supplied, both
predicates are applied. A mismatched pair returns zero rows; it must never
drop one predicate or broaden the query to all employees in either
organization.

The parser accepts positive integer IDs syntactically. The service may
pre-check that both records exist and that department.branch_id equals
branchId, but the repository query remains authoritative. The approved
controlled behavior for a mismatched or missing organization pair is an empty
result with HTTP 200 and the normalized filters preserved. No exception or
silent filter removal is used for ordinary query input.

The search filter form uses all historical search choices. When a branch is
selected, the rendered department options are restricted to departments
belonging to that branch, including inactive departments. If a manually
submitted department belongs to another branch, the result is still zero and
the selected values are preserved so the invalid combination is explainable.
Server-side correctness does not depend on JavaScript.

## 11. Historical organization filter behavior

Search organization choices are different from assignment choices:

| Use case | Branch choices | Department choices |
| --- | --- | --- |
| Create/edit employee assignment | active only | active only |
| Employee historical search | active and inactive | active and inactive |

Add explicit read methods such as listForSearch to the branch and department
read repository interfaces. These methods return id, code, name, and status,
with deterministic display ordering. Existing listActive methods remain
unchanged for employee create/edit forms.

Employee rows remain searchable when:

- the employee is inactive;
- its department is inactive;
- its branch is inactive.

Search joins must not add b.status = active or d.status = active. Status
filters apply to the employee row only.

## 12. Sorting design

The public sort whitelist is:

- employee_code;
- name;
- branch;
- department;
- employee_type;
- status.

The repository maps these keys to fixed SQL expressions:

| Public key | SQL expression |
| --- | --- |
| employee_code | e.employee_code |
| name | CONCAT(e.last_name, ' ', e.first_name) |
| branch | b.name |
| department | COALESCE(d.name, '') |
| employee_type | e.employee_type |
| status | e.status |

Only asc and desc are accepted directions. Unknown sort keys fall back to
employee_code. Unknown directions fall back to asc.

The direction applies to the selected primary expression. Every query adds
the deterministic secondary ordering e.employee_code ASC, e.id ASC. Name
sorting therefore remains stable when names match, and employee_code sorting
uses id as a technical tie-breaker even though employee_code is unique in the
current schema.

No request value is interpolated as an SQL identifier. The whitelist map is
defined in repository code.

The default is employee_code ASC on page 1 with 20 rows.

## 13. Pagination design

Pagination is server-side SQL pagination with a fixed page size of 20.

For a normalized page p:

    LIMIT 20
    OFFSET (p - 1) * 20

The repository executes a COUNT query using the same joins and WHERE
semantics as the data query. Total pages are the ceiling of total count
divided by 20.

Page normalization rules:

- missing page becomes 1;
- a positive integer is accepted;
- zero, negative, non-integer, overflowing, or otherwise malformed values
  become 1;
- if the requested page exceeds the final page, it is clamped to the final
  page;
- if there are zero matches, current page and total pages are both 1.

The endpoint returns HTTP 200 for an empty result or an out-of-range page.
The view receives the normalized current page rather than an invalid offset.

The search form does not submit page. Submitting new criteria therefore starts
at page 1. Sort controls also reset page to 1. Pagination links change only
page and retain all other normalized query parameters.

## 14. Result count

The view displays total matching rows, not the current page row count.

Suggested localized labels:

- EN: 0 employees, 1 employee, or N employees;
- JA: 0件の社員, 1件の社員, or N件の社員.

Pluralization can remain a simple localized count string if that matches the
existing translator conventions. The count is rendered in an accessible
status or summary region near the table heading.

## 15. Query parameter contract

The stable GET contract is:

    /employees?keyword=山田
    &branch_id=1
    &department_id=2
    &employee_type=permanent
    &status=active
    &sort=employee_code
    &direction=asc
    &page=2

The parameter names match existing id/value naming conventions. perPage is
not user-configurable and is not accepted from the query string.

Empty values behave as no filter. Normalized state is used when generating
links. Search and sort form submissions omit page so criteria changes start
at page 1.

Unknown or malformed values are handled safely:

- invalid IDs become null filters, except a syntactically valid mismatched
  branch/department pair, which produces zero results;
- invalid employee type or status becomes an unselected filter;
- invalid sort/direction uses the approved default;
- invalid page becomes page 1.

No raw query array is echoed into the view or SQL.

## 16. Input normalization and validation

The criteria parser:

1. reads scalar query values only;
2. trims keyword;
3. converts blank keyword to null;
4. accepts only positive integer branch and department IDs;
5. checks employee type against permanent/dispatched;
6. checks status against active/inactive;
7. checks sort against the six-key whitelist;
8. checks direction against asc/desc;
9. normalizes page to an integer at least 1;
10. always sets perPage to 20.

Arrays, objects, booleans, and other non-scalar query values are treated as
malformed and normalized to the safe empty/default value. The application
does not return a validation error page for ordinary malformed search input.

## 17. Repository design

Extend EmployeeRepositoryInterface with one search operation:

    search(EmployeeSearchCriteria $criteria): EmployeeSearchResult

The repository owns SQL execution, fixed column mappings, parameter binding,
COUNT/data query construction, and deterministic ordering. The service owns
criteria normalization and view-level pagination/filter data.

Use one private repository query-builder path that returns the equivalent
FROM/JOIN/WHERE fragments and bound values for both COUNT and data queries.
The data query adds only the fixed ORDER BY, LIMIT, and OFFSET clauses. This
avoids count/data filter drift.

Do not retain listBasic as the Employee list path after implementation unless
it is needed by another caller. Removing or deprecating it is a follow-up
cleanup decision; the approved search path must be authoritative for GET
/employees.

The organization read interfaces gain search-specific list methods:

    listForSearch(): array

The returned rows contain id, branch_id where applicable, code, name, and
status. These methods are not used by assignment forms.

## 18. SQL/query design

The data and COUNT queries use:

    FROM employees e
    INNER JOIN branches b ON b.id = e.branch_id
    LEFT JOIN departments d
      ON d.id = e.department_id
     AND d.branch_id = e.branch_id

The selected fields include the current list display values plus the ids and
status values needed for result rendering and accessible labels.

Keyword and filter values are bound parameters. LIMIT and OFFSET are
normalized positive integers and bound as PDO integer values where supported.
The fixed ORDER BY expression comes only from the repository whitelist.

The employee status condition is applied only when the status filter is
selected. Organization status is never used to hide historical employee
rows. Department filtering uses e.department_id and branch filtering uses
e.branch_id, so a mismatched pair cannot broaden the result.

The COUNT query uses COUNT(*) over the same filtered joined relation. Because
each employee has at most one matching branch and department row under the
foreign-key design, no DISTINCT is needed. If future joins can multiply
employee rows, the implementation must use COUNT(DISTINCT e.id) and ensure
the data query has equivalent row semantics.

## 19. SQL safety

The implementation must explicitly preserve these guarantees:

- all keyword, ID, type, and status values use prepared parameters;
- LIKE wildcards are escaped and the keyword is bound;
- user input never supplies an SQL column name or ORDER BY fragment;
- LIMIT and OFFSET come only from normalized integers;
- sort and direction are mapped through hard-coded whitelists;
- COUNT and data queries share equivalent filtering;
- no raw GET value is concatenated into SQL;
- empty and malformed input cannot cause an exception or an unbounded query.

## 20. Service design

EmployeeService owns the application workflow for list search:

1. parse and normalize the request query through the criteria parser;
2. obtain active/inactive organization choices for search;
3. obtain the EmployeeSearchResult from the employee repository;
4. construct normalized form values and pagination link state;
5. provide the view with rows, count, page metadata, criteria, and choices.

The controller remains thin and does not know SQL, joins, sort expressions,
or pagination calculations. The view renders the prepared data and does not
filter arrays or compute result counts.

Existing create, edit, detail, and deactivate methods remain separate. Search
does not alter assignment validation or inactive employee protections.

## 21. Controller behavior

GET /employees remains the only list endpoint. The controller passes
Request::query values to a service method such as listEmployees($query), or
passes a parser-created EmployeeSearchCriteria if that better matches the
existing request boundary.

Normal matches, zero matches, malformed query input, and an out-of-range page
all return HTTP 200. The controller renders the existing employee index view
with the new search model.

The controller must not:

- concatenate query values into SQL;
- decide which organization records are historical;
- calculate total pages;
- paginate PHP arrays;
- silently remove a selected filter to make a mismatched pair match.

## 22. SSR UI design

Place a compact accessible search/filter card above the employee table:

- Keyword: search by code, name, or email;
- Branch: all active and inactive branches;
- Department: all departments or departments belonging to the selected branch;
- Employee Type: all, permanent, dispatched;
- Status: all, active, inactive;
- Sort: one approved field;
- Direction: ascending or descending;
- Search button;
- Reset link to /employees.

The current selected values are rendered from normalized criteria. Inactive
organization options receive the existing inactive suffix convention and
remain selectable for historical searches.

The form uses method GET, explicit labels, associated controls, keyboard
operation, and existing responsive classes. It does not require JavaScript.
If progressive enhancement later updates the department select when a branch
changes, the server-rendered options and server-side mismatch behavior remain
authoritative.

The table continues to use escaped values, status chips, and action rules.
Inactive employee rows continue to show only Detail.

## 23. Pagination UI

The page displays previous/next controls and a bounded page-number window.
The window should include:

- the first page;
- the last page;
- the current page;
- up to two nearby pages on either side;
- ellipsis where a gap exists.

For a small total page count, render every page. Do not render hundreds of
links.

Every pagination URL preserves keyword, branch_id, department_id,
employee_type, status, sort, and direction. Only page changes. Previous and
next are disabled or omitted at the boundaries, with accessible labels in
both languages.

## 24. Localization

Add EN/JA keys for:

- employee search label and keyword help;
- branch, department, type, and status filter labels;
- all-options labels;
- sort and direction labels;
- search and reset actions;
- active/inactive organization suffixes where needed;
- result count;
- no matching employees;
- previous, next, and page navigation labels;
- current-page and selected-sort accessibility text.

Suggested text:

| Concept | EN | JA |
| --- | --- | --- |
| Keyword help | Search by code, name, or email | 社員コード、氏名、メールアドレスで検索 |
| Result count | :count employees | :count件の社員 |
| Empty result | No employees match the current search. | 条件に一致する社員はいません。 |
| Reset | Reset filters | 条件をリセット |
| Inactive organization | (inactive) | （無効） |

Use the existing Translator and escaping conventions. No search label or
validation text is hard-coded only in a view.

## 25. Empty and reset behavior

Zero matches are a successful HTTP 200 search result, not an application
error. The search form and all selected normalized values remain visible.

The page displays a localized empty-state message and a Reset filters link to
/employees. The reset URL clears keyword, filters, sort, direction, and page,
returning to employee_code ASC, page 1, and 20 rows.

The existing create-employee action may remain available as a separate
primary action, but it must not replace the reset action for a zero-result
search.

## 26. Performance and index analysis

Existing indexes provide useful support for:

- exact employee_code and email lookups through unique indexes;
- branch/status filtering through employees(branch_id, status);
- branch/department filtering through employees(branch_id, department_id);
- name ordering/prefix behavior through employees(last_name, first_name).

Leading-wildcard searches over code, names, kana, and email will generally
not use ordinary B-tree indexes efficiently. This is accepted for the initial
server-side feature and is documented rather than addressed with a premature
full-text engine.

Sorting by joined branch or department name and counting broad filtered
results may require temporary sorting as data grows. Correctness and stable
pagination take priority for this phase.

No migration is required for the approved design. Do not add indexes yet.
After implementation, query plans and realistic row counts should be reviewed.
If measurements justify it, a separate migration review may consider indexes
such as employee_type/status or department_id/status. Any such migration is
outside this phase and must not change the search contract.

## 27. Test plan

### 27.1 Criteria/parser unit tests

Cover:

- blank and trimmed keyword;
- scalar and non-scalar keyword input;
- positive, zero, negative, malformed, and oversized IDs;
- permanent and dispatched employee types;
- active and inactive statuses;
- invalid type/status fallback;
- all sort keys;
- invalid sort and direction fallback;
- page normalization;
- fixed perPage 20;
- literal percent, underscore, and backslash keyword handling.

### 27.2 Repository integration tests

Cover:

- default employee_code ASC ordering;
- code, first name, last name, kana, and email matches;
- no-match keyword;
- branch, department, type, and status filters;
- all filters combined with AND;
- keyword plus filters;
- inactive employees;
- inactive departments;
- inactive branches;
- valid branch/department pair;
- mismatched pair returning zero rows;
- missing organization IDs;
- every approved sort field in both directions;
- deterministic tie-breaking;
- COUNT and row query using identical filters;
- LIMIT 20;
- OFFSET for middle and final pages;
- 0, 20, and 21 matching records;
- page beyond the final page;
- prepared values containing quote and wildcard characters.

### 27.3 HTTP/UI tests

Cover:

- GET /employees default response and default ordering;
- search form and all controls;
- selected query values retained;
- inactive organization options available for search;
- filtered result count;
- zero-result empty state and reset link;
- pagination links preserve every non-page query parameter;
- sort links/controls preserve filters;
- invalid query input returns 200 safely;
- mismatched branch/department returns zero results;
- EN and JA labels;
- existing detail/create/edit/deactivate routes;
- inactive employee action hiding and direct protection.

### 27.4 Regression

Run the full existing test suite. Confirm Branch and Department redesign
behavior, dispatch relationships, employee assignment validation, and
inactive employee protections remain unchanged.

## 28. Expected files to change

Expected implementation changes, subject to final code structure:

- new EmployeeSearchCriteria DTO;
- new EmployeeSearchResult DTO/value object;
- new search criteria parser or validator;
- EmployeeRepositoryInterface;
- PdoEmployeeRepository;
- EmployeeService;
- EmployeeController;
- employee index view;
- branch and department read repository interfaces;
- PdoBranchReadRepository and PdoDepartmentReadRepository;
- EN/JA localization files;
- Employee HTTP, unit, and repository integration tests.

Routes/web.php should not need a new route because GET /employees already
exists. Bootstrap changes are expected only if constructor dependencies are
expanded.

## 29. Migration decision

No migration is approved or required for the initial feature. The existing
schema has the required columns, foreign keys, lifecycle values, and useful
indexes.

Index improvements remain a measured follow-up decision. No schema, status,
employee type, or organization relationship changes are part of this phase.

## 30. Acceptance criteria

The feature is complete only when:

1. GET /employees shows active and inactive employees by default.
2. The default sort is employee_code ASC with stable tie-breaking.
3. The default page size is 20 and page 1 is used.
4. Keyword search covers employee_code, first_name, last_name,
   first_name_kana, last_name_kana, and email.
5. Keyword predicates are ORed within one parenthesized group.
6. Keyword and all selected filters combine with AND.
7. Branch, department, employee type, and status filters use canonical values.
8. Inactive branches and departments are available as search choices.
9. Inactive organization relationships do not hide historical employees.
10. Mismatched branch/department filters return zero results without
    broadening.
11. Sort keys and directions are explicitly whitelisted.
12. Arbitrary query values cannot become SQL identifiers or SQL fragments.
13. COUNT and data queries use equivalent filtering semantics.
14. LIMIT/OFFSET pagination is performed in SQL, not PHP arrays.
15. Out-of-range pages are safely clamped and zero results remain HTTP 200.
16. Total count represents all filtered matches, not only the current page.
17. Pagination links preserve all criteria except page.
18. Search criteria remain visible after submission.
19. Reset returns to /employees with default criteria.
20. EN/JA labels, counts, empty states, and pagination controls are present.
21. Existing employee detail/create/edit/deactivate behavior remains intact.
22. Existing inactive employee protections remain intact.
23. No migration, full-text engine, SPA, autocomplete, saved search, or export
    feature is added.

## 31. Risks and decisions requiring review

### 31.1 Leading-wildcard search cost

Contains matching is useful for names, kana, code, and email but can scan
large portions of the table. The initial feature accepts this tradeoff.
Production query plans and row counts should be reviewed before adding
specialized search infrastructure.

### 31.2 Organization-choice volume

Including inactive organization rows is required for historical searching. If
the organization catalog grows substantially, the filter UI may need bounded
choice loading or a separate reviewed search experience. It must not silently
return to active-only choices.

### 31.3 Mismatched filter feedback

The approved behavior is zero results with HTTP 200 and preserved values. A
future UX review may add a localized explanatory message, but it must not
remove either filter or broaden the SQL result.

### 31.4 Page clamping

Clamping an out-of-range page is user-friendly and keeps the endpoint at
HTTP 200. If analytics or canonical URLs later require a redirect to the
last page, that would be a separate HTTP behavior decision.

### 31.5 Count cost

A COUNT query is required for the approved result count and pagination. On
large data sets it may become a measurable cost; caching or approximate
counts are out of scope and require review.

### 31.6 Search semantics and collation

LIKE matching follows the configured database collation. The specification
defines the participating columns and literal wildcard behavior, but exact
case/accent behavior remains a database-collation concern and should be
covered by the integration environment.

### 31.7 Existing listBasic callers

The current listBasic API may be used by tests or other internal callers.
The implementation should introduce the search API without breaking unrelated
read paths, then remove or deprecate the old list method only after callers
are confirmed.
