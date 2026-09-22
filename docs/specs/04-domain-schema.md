# Phase 04: Domain Schema Specification

## 1. Document purpose

This document defines the first business schema for the Company Employee Management System. It is a specification only. It does not implement PHP classes, create migrations, connect to a database, or modify the Phase 01–03 implementation.

Phase 04 adds the relational structure for the organization and its employees. The implementation must use the migration infrastructure defined in [Phase 03](03-database-foundation.md).

## 2. Phase purpose and objective

Design a small, normalized MySQL/MariaDB schema that supports one company operating multiple branches, departments within branches, and employees assigned to branches and optionally to departments. Database constraints must protect the important organizational relationships even when application code is incorrect.

The database remains behind the Phase 03 configuration, connection, and migration boundaries. The normal HTTP bootstrap must not connect to the database or execute migrations.

## 3. Scope and non-goals

### 3.1 Included

- Tables for companies, branches, departments, and employees
- Primary keys, foreign keys, scoped uniqueness, nullability, checks, and indexes
- One initial migration per domain table, applied in dependency order
- MySQL integration tests using the isolated Phase 03 test database configuration
- Documentation of relationship integrity, deletion behavior, and rollback limits

### 3.2 Explicitly excluded

- CRUD pages, routes, controllers, services, repositories, domain entities, or DTOs
- Authentication, users, passwords, roles, permissions, or sessions
- Employee search, filters, sorting, pagination, dashboards, or UI
- Dispatch companies, contracts, portfolios, skills, projects, certifications, or uploads
- Manager hierarchy, position/grade catalogs, generic metadata, or generic lookup systems
- Production seed data or a general-purpose seeding framework
- ORM, Active Record, query builder, or framework database layer

## 4. Domain assumptions and decisions

1. A deployment manages one company. The `companies` table stores that organization's identity and provides the parent record for its branches. The table is not a tenant boundary and does not authorize users to access multiple unrelated companies. Cross-company tenancy is out of scope.
2. A company may have multiple branches; each branch belongs to exactly one company.
3. A branch may have multiple departments. Departments are branch-specific organizational units. The same department name or code may be used in another branch.
4. Each employee must belong to a branch. An employee may temporarily have no department while assignment is pending. If assigned, the department must belong to that employee's branch.
5. Employee numbers are globally unique. This is a simple, indexable rule and avoids storing a redundant `company_id` on employees solely to enforce company-wide uniqueness. Reusing the same employee number in separate companies would require a future multi-company design decision.
6. Employee email is required by the master requirements. It is not a login identifier. It is globally unique to avoid ambiguous employee lookup and accidental duplicate contact records; future authentication must define its own identity rules.
7. Employee manager relationships are deferred. No concrete reporting or hierarchy behavior is required yet.
8. Positions are stored as a nullable `position_title` string on the employee. A controlled position catalog is deferred until lifecycle, reuse, or reporting requirements justify it.
9. Employee types are `permanent` and `dispatched`, as specified by the master requirements. Employee status and branch/department status initially use `active` and `inactive`. Controlled values use `VARCHAR` plus `CHECK`, rather than vendor-specific `ENUM` or lookup tables.
10. Branch city, address, phone, department description, employee name kana, and employee phone are represented because the master project requirements call for them. Photo storage, employee contract details, and dispatch-company links are deferred to later domain phases.

## 5. Modeling and naming conventions

- Use plural snake_case table names and singular snake_case column names.
- Use `id` as each table's surrogate primary key.
- Use `BIGINT UNSIGNED AUTO_INCREMENT` for primary keys and matching foreign keys. It is straightforward for PDO and avoids introducing UUID handling without a concrete distributed-ID requirement.
- Use `VARCHAR` for bounded human-readable values, `TEXT` for unbounded address/description content where appropriate, `DATE` for calendar dates, and `DATETIME` for application timestamps.
- Store money nowhere in this phase; no monetary columns are justified.
- Use `TINYINT(1)` only for actual boolean facts. Domain lifecycle states are named strings with checks, not booleans.
- Use `utf8mb4` and InnoDB for every domain table. Use the Phase 03 compatible `utf8mb4_unicode_ci` collation unless its supported engine baseline specifies a compatible alternative.
- Keep all timestamps in UTC. PHP application code manages `created_at` and `updated_at` consistently; do not mix implicit database timestamp behavior with application-managed values. `hire_date` is a `DATE`, not a timestamp, because hiring is a calendar date.
- Every table has required `created_at` and `updated_at` values. They are `DATETIME NOT NULL` without implicit database defaults. Application persistence explicitly sets and updates them in UTC, keeping one clear timestamp owner.

### 5.1 Engine compatibility

Status and employee-type `CHECK` constraints must be enforced by the supported engine version. The implementation must verify its MySQL/MariaDB baseline supports enforced `CHECK` constraints; it must not claim these constraints protect data on older MySQL versions that parse but ignore them. If the established Phase 03 environment cannot meet that baseline, the phase implementation must record the engine limitation and use a reviewed portable enforcement strategy before declaring acceptance.

## 6. Entity overview and relationships

```text
Company (1)
  └── Branch (many)
        ├── Department (many)
        └── Employee (many)
              └── Department (zero or one, same branch)
```

Companies are the organizational root. Branches represent physical company locations. Departments belong to one branch, allowing the same department concept to exist in multiple locations. Employees always reference a branch and may reference a department. A composite foreign key ensures that an employee cannot point at a department in a different branch.

## 7. Table definitions

All columns below use InnoDB and `utf8mb4`. Unless specified otherwise, identifiers and text values are required only where explicitly marked `NOT NULL`. All primary keys are `BIGINT UNSIGNED AUTO_INCREMENT`.

### 7.1 `companies`

The company row represents the single organization managed by this installation and gives branches a stable parent identity.

| Column | SQL type | Null/default | Purpose |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK, auto increment | Internal relational key |
| `code` | `VARCHAR(30)` | `NOT NULL` | Stable human-facing organization code |
| `name` | `VARCHAR(160)` | `NOT NULL` | Organization name |
| `email` | `VARCHAR(254)` | `NULL` | Optional organization contact email |
| `phone` | `VARCHAR(32)` | `NULL` | Optional organization contact phone |
| `address` | `VARCHAR(500)` | `NULL` | Optional registered/contact address |
| `created_at` | `DATETIME` | `NOT NULL` | UTC creation time |
| `updated_at` | `DATETIME` | `NOT NULL` | UTC last update time |

Constraints and indexes:

- `PRIMARY KEY (id)`
- `UNIQUE (code)`
- No separate index on `name`, email, or phone is required in this phase.
- Company codes are compared under the selected collation. If case-sensitive identifiers become a requirement, specify an explicit compatible collation in a later migration.

### 7.2 `branches`

Each branch is a company location. Codes are unique within their company, not globally.

| Column | SQL type | Null/default | Purpose |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK, auto increment | Internal relational key |
| `company_id` | `BIGINT UNSIGNED` | `NOT NULL` | Owning company |
| `code` | `VARCHAR(30)` | `NOT NULL` | Company-scoped branch code |
| `name` | `VARCHAR(160)` | `NOT NULL` | Branch display name |
| `city` | `VARCHAR(120)` | `NOT NULL` | City for location display and search |
| `address` | `VARCHAR(500)` | `NOT NULL` | Branch address |
| `phone` | `VARCHAR(32)` | `NOT NULL` | Branch contact phone |
| `status` | `VARCHAR(20)` | `NOT NULL DEFAULT 'active'` | `active` or `inactive` |
| `created_at` | `DATETIME` | `NOT NULL` | UTC creation time |
| `updated_at` | `DATETIME` | `NOT NULL` | UTC last update time |

Constraints and indexes:

- `PRIMARY KEY (id)`
- `UNIQUE (company_id, code)`
- The unique index begins with `company_id` and also supports the company foreign key; do not add a duplicate single-column index.
- `FOREIGN KEY (company_id) REFERENCES companies(id)`
- `CHECK (status IN ('active', 'inactive'))`

### 7.3 `departments`

Departments are scoped to a branch. Repeated names and codes across branches are allowed.

| Column | SQL type | Null/default | Purpose |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK, auto increment | Internal relational key |
| `branch_id` | `BIGINT UNSIGNED` | `NOT NULL` | Branch that owns this department |
| `code` | `VARCHAR(30)` | `NOT NULL` | Branch-scoped department code |
| `name` | `VARCHAR(120)` | `NOT NULL` | Department name |
| `description` | `TEXT` | `NULL` | Optional explanatory text |
| `status` | `VARCHAR(20)` | `NOT NULL DEFAULT 'active'` | `active` or `inactive` |
| `created_at` | `DATETIME` | `NOT NULL` | UTC creation time |
| `updated_at` | `DATETIME` | `NOT NULL` | UTC last update time |

Constraints and indexes:

- `PRIMARY KEY (id)`
- `UNIQUE (branch_id, code)`
- `UNIQUE (branch_id, id)` to provide the referenced composite key for employee branch/department integrity
- `FOREIGN KEY (branch_id) REFERENCES branches(id)`
- `CHECK (status IN ('active', 'inactive'))`
- No name index is added until a concrete query pattern requires it. The scoped unique code index also supports branch-filtered code lookups.

### 7.4 `employees`

Employees are the core business records. Employee business identity is separate from application login identity.

| Column | SQL type | Null/default | Purpose |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK, auto increment | Internal relational key |
| `branch_id` | `BIGINT UNSIGNED` | `NOT NULL` | Required organizational location |
| `department_id` | `BIGINT UNSIGNED` | `NULL` | Optional current department assignment |
| `employee_code` | `VARCHAR(40)` | `NOT NULL` | Globally unique human-facing employee identifier |
| `first_name` | `VARCHAR(100)` | `NOT NULL` | Given name |
| `last_name` | `VARCHAR(100)` | `NOT NULL` | Family name |
| `first_name_kana` | `VARCHAR(100)` | `NOT NULL` | Given name in kana for Japanese display/search |
| `last_name_kana` | `VARCHAR(100)` | `NOT NULL` | Family name in kana for Japanese display/search |
| `email` | `VARCHAR(254)` | `NOT NULL` | Required business contact address; not an authentication credential |
| `phone` | `VARCHAR(32)` | `NULL` | Optional employee contact number |
| `position_title` | `VARCHAR(120)` | `NULL` | Optional current position label without a speculative catalog |
| `employee_type` | `VARCHAR(20)` | `NOT NULL` | `permanent` or `dispatched` |
| `hire_date` | `DATE` | `NOT NULL` | Calendar date of employment |
| `status` | `VARCHAR(20)` | `NOT NULL DEFAULT 'active'` | `active` or `inactive` |
| `created_at` | `DATETIME` | `NOT NULL` | UTC creation time |
| `updated_at` | `DATETIME` | `NOT NULL` | UTC last update time |

Constraints and indexes:

- `PRIMARY KEY (id)`
- `UNIQUE (employee_code)`
- `UNIQUE (email)`
- `KEY (branch_id, status)` for branch employee lists and status filtering
- `KEY (branch_id, department_id)` for department membership and the composite foreign key
- `KEY (last_name, first_name)` for common name ordering; this is not a complete substring-search solution
- `FOREIGN KEY (branch_id) REFERENCES branches(id)`
- `FOREIGN KEY (branch_id, department_id) REFERENCES departments(branch_id, id)`
- `CHECK (employee_type IN ('permanent', 'dispatched'))`
- `CHECK (status IN ('active', 'inactive'))`

The branch foreign key is separate from the composite department foreign key. This requires a valid branch for every employee while allowing `department_id` to be null. MySQL's composite-FK null semantics mean the department relationship is not checked when `department_id` is `NULL`, which is the intended pending-assignment state.

The organization-wide employee code uniqueness rule is implemented globally because `company_id` is intentionally not duplicated on employees. If a future requirement demands that the same employee code may be reused by separate companies in a multi-company deployment, the schema and tenancy scope must be explicitly redesigned together.

## 8. Referential integrity and delete/update behavior

All foreign keys use `ON UPDATE RESTRICT` and `ON DELETE RESTRICT`:

| Relationship | Delete behavior | Reason |
| --- | --- | --- |
| `branches.company_id → companies.id` | `RESTRICT` | A company with branches cannot be deleted accidentally |
| `departments.branch_id → branches.id` | `RESTRICT` | A branch with departments cannot be deleted accidentally |
| `employees.branch_id → branches.id` | `RESTRICT` | Employees preserve their branch history and countability |
| `(employees.branch_id, employees.department_id) → (departments.branch_id, departments.id)` | `RESTRICT` | A department in use cannot be deleted; employee and department branch must match |

`SET NULL` is not used for branch or department deletion because it would lose or obscure business assignment history. `CASCADE` is not used because deleting an organizational row must never implicitly delete employees. Rows become unavailable for new assignment through their `inactive` status; hard deletion can be addressed by a later explicit retention policy.

## 9. Normalization and index strategy

Each entity's attributes live on its own table. Department attributes are not copied onto employees, company identity is not copied onto branches beyond the foreign key, and branch identity is not copied onto departments beyond its foreign key. The employee's `branch_id` is intentionally repeated alongside `department_id` to represent a required branch assignment and to make cross-branch inconsistency enforceable through a composite foreign key.

Unique constraints create the indexes needed to enforce business identifiers and scoped codes. Explicit indexes are limited to foreign-key and clearly expected list/filter/order paths. No speculative full-text, prefix, or generic search indexes are included. Indexes should be revisited when actual repository queries and data volumes are known.

## 10. Migration strategy and dependency order

Use the Phase 03 migration contract and deterministic numeric version prefix. Create one migration per domain table so each schema evolution has a clear identity and can be reviewed or rolled back in dependency order. Do not edit Phase 03's `schema_migrations` infrastructure table.

Migration order:

1. `companies`
2. `branches`
3. `departments`
4. `employees`

Each migration creates only its table and its keys, checks, and indexes. Table names and all identifiers are static migration SQL; any runtime values in integration tests use prepared statements. DDL must specify `ENGINE=InnoDB`, `DEFAULT CHARSET=utf8mb4`, and the chosen compatible collation.

Rollback proceeds in reverse order: employees, departments, branches, companies. `down()` may drop the table introduced by that migration after its dependent tables have already been removed. MySQL/MariaDB DDL may implicitly commit; neither migrations nor tests may claim universal transactional rollback. A failed DDL migration may leave partial state requiring operator inspection before retry.

## 11. SQL examples for critical constraints

The implementation must encode the employee-to-department integrity rule at the database level. Representative SQL (constraint names may follow a consistent migration naming convention):

```sql
CREATE TABLE departments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    branch_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departments_branch_code (branch_id, code),
    UNIQUE KEY uq_departments_branch_id (branch_id, id),
    CONSTRAINT fk_departments_branch
        FOREIGN KEY (branch_id) REFERENCES branches (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_departments_status
        CHECK (status IN ('active', 'inactive'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CONSTRAINT fk_employees_branch
    FOREIGN KEY (branch_id) REFERENCES branches (id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_employees_branch_department
    FOREIGN KEY (branch_id, department_id)
    REFERENCES departments (branch_id, id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
```

The full table migration must include all employee columns, unique constraints, and indexes listed in Section 7.4. The snippet highlights the composite relationship and is not a substitute for the table specification.

## 12. Test strategy and test database safety

### 12.1 Integration tests

Use the real MySQL/MariaDB integration-test configuration from Phase 03. Tests should verify the domain behavior that matters:

- Applying all migrations creates the four domain tables in dependency order.
- The schema has the specified primary keys, critical columns, foreign keys, and indexes.
- Valid company → branch → department → employee rows can be inserted.
- Duplicate company codes, company-scoped branch codes, branch-scoped department codes, employee codes, and emails are rejected.
- Missing required values and invalid status/type values are rejected by constraints where supported by the declared engine baseline.
- Invalid company/branch/department references are rejected.
- An employee can be inserted with a null department.
- An employee cannot be assigned a department belonging to a different branch.
- Deleting a company, branch, or department that has dependents is rejected.
- A repeated migration command does not reapply completed migrations.
- Rollback, if supported by Phase 03, removes tables in reverse dependency order.

Test only representative constraint behavior; avoid duplicating every SQL declaration as a separate test. Unit tests are needed only if Phase 04 adds PHP logic beyond declarative migration definitions.

### 12.2 Isolation requirements

Preserve all Phase 03 safeguards. Integration tests require `APP_ENV=test` and explicit `DB_TEST_HOST`, `DB_TEST_PORT`, `DB_TEST_DATABASE`, `DB_TEST_USERNAME`, `DB_TEST_PASSWORD`, and `DB_TEST_CHARSET`. The database name must be clearly test-only (for example, ending `_test`) and must be verified as distinct from development and production database names before any destructive setup or cleanup.

Never fall back to normal application credentials. Never run destructive schema tests against a database that has not passed the explicit test-environment and test-database checks. If the dedicated test database is unavailable, report that clearly or skip only according to the documented Phase 03 integration-test command.

## 13. Security and operational considerations

- Do not commit credentials or output passwords, raw DSNs, or sensitive SQL in errors.
- Migration execution remains CLI-only and is never triggered from an HTTP request.
- Use prepared statements for dynamic integration-test values.
- Keep `utf8mb4` from connection through schema to preserve multilingual names.
- Use a least-privilege migration/database account in deployed environments; application runtime credentials should not receive unnecessary schema privileges.
- Foreign keys and unique constraints are the final integrity boundary for the business relationships defined here.
- No password, token, login identifier, or role column belongs on `employees`.

## 14. Future application and repository implications

Later employee-management features may add focused repositories that use PDO and prepared statements, then application services for validation and use-case transaction boundaries. Those layers are not created in Phase 04. The department's branch ownership and employee's optional department relationship must remain visible in repository queries; repository code must not treat a department ID as valid without preserving the database-enforced branch pairing.

Future work may add position catalogs, manager relationships, employment history, dispatch-company associations, contracts, photo references, and richer lifecycle states when their requirements are defined. Those additions should use new ordered migrations and preserve existing employee and organizational history.

## 15. Acceptance criteria

Phase 04's implementation is complete only when:

1. The work follows the explicitly assigned implementation branch for the phase; this specification does not invent a branch name.
2. The four tables and columns match the decisions in this document.
3. All tables use InnoDB, `utf8mb4`, consistent timestamps, and `BIGINT UNSIGNED` keys.
4. Company, branch, department, and employee relationships are normalized and enforced by foreign keys.
5. Department codes/names may repeat across branches while codes remain unique within their owning scope.
6. Employees require a branch, may have a null department, and cannot reference a department in another branch.
7. Employee code and email uniqueness follow the documented global rules.
8. Status and employee type values are constrained to their documented initial sets.
9. Deletes are restricted where dependent organizational or employee records exist.
10. Migrations are deterministic, ordered by dependency, idempotently tracked by Phase 03, and reversible in reverse order where `down()` is supported.
11. Integration tests prove critical valid and invalid relationship behavior using only the explicitly isolated test database.
12. No CRUD, HTTP, repository, service, model, authentication, authorization, seed framework, or UI functionality is introduced.
13. The normal HTTP bootstrap remains independent of database availability.

The schema is not complete merely because all four tables can be created. The branch/department consistency rule, scoped uniqueness, deletion protection, and test isolation must also be demonstrated.
