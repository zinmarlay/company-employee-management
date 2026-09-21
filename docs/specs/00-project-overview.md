# Company Employee Management System

## 1. Document purpose

This document is the master requirements and architecture direction for the Company Employee Management System. It establishes the project scope, domain vocabulary, technical constraints, security expectations, and engineering principles that all later feature specifications must follow.

Detailed feature specifications will be added independently, one Git branch at a time. A feature specification may refine this document, but it must not silently change the technology direction, authorization model, or core domain rules defined here.

This is a planning document. It intentionally contains no application implementation code.

## 2. Project summary

### Project name

Company Employee Management System

### Purpose

Build an internal, server-side rendered system for managing employees and related company information across multiple branches in different cities.

The system will manage:

- Branches
- Departments
- Employees
- Permanent employees (正社員)
- Dispatched employees (派遣社員)
- Dispatch companies
- Employee contracts
- Employee portfolios
- Skills
- Projects
- Certifications
- System users

The project is also an advanced Pure PHP learning project. It must demonstrate professional PHP engineering practices suitable for real-world systems, rather than beginner-style scripts or framework-specific conventions copied without understanding.

## 3. Goals and success criteria

The project should:

1. Provide a secure internal employee-management workflow for administrators and read-only users.
2. Model the company's branches, departments, employees, dispatched workforce, contracts, and professional history clearly.
3. Demonstrate maintainable object-oriented PHP design with explicit dependency flow.
4. Keep business rules testable and independent from HTTP and database details where practical.
5. Make authorization a server-side responsibility, not only a user-interface concern.
6. Preserve historical business data, especially contract history, rather than overwriting it.
7. Provide a foundation on which later feature branches can be implemented incrementally.

The system is successful when a developer can identify where a responsibility belongs, explain why each layer exists, and extend a feature without placing unrelated logic in a controller, view, or SQL statement.

## 4. Scope

### In scope

- Authentication and session-based access to the internal system
- Two application roles: `ADMIN` and `USER`
- Branch, department, employee, dispatch company, contract, portfolio, skill, project, certification, and system-user management
- Employee search, filtering, sorting, pagination, and result counts
- Contract expiration classification and dashboard visibility
- Server-side validation, authorization, exception handling, logging, and configuration management
- Responsive HTML5 views with CSS and JavaScript enhancements
- Database persistence in MySQL through PDO

### Out of scope unless a later specification explicitly adds it

- Public self-registration
- Payroll, attendance, leave, benefits, or expense management
- Recruitment and applicant tracking
- Customer or sales relationship management
- Native mobile applications
- SPA frontend architecture
- Background job infrastructure beyond what is required for a later, explicitly specified feature
- Multi-tenant behavior across unrelated companies

## 5. Technology constraints

### Required technologies

- PHP 8.x
- Pure PHP; no full-stack PHP framework
- MySQL
- PDO for all database access
- Composer
- PSR-4 autoloading
- Server-side rendered PHP views
- HTML5
- CSS
- JavaScript for progressive enhancement and focused UI behavior
- Material Design inspired visual language
- Git and GitHub

### Prohibited technologies and patterns

- Laravel
- Symfony
- CodeIgniter
- React
- Vue
- PHP ORM libraries
- Direct database access from views
- Unparameterized SQL built from request input
- Authorization implemented only by hiding buttons or links
- A single procedural script containing routing, validation, SQL, business rules, and HTML output

All database interaction must use PDO with prepared statements. Composer is used for dependency management and PSR-4 autoloading, not as a reason to introduce unnecessary packages.

## 6. Architecture direction

The application is one server-side rendered PHP application with a clear request lifecycle:

1. The web server forwards the request to a front controller.
2. Bootstrap code loads configuration, the Composer autoloader, and application dependencies.
3. Middleware performs cross-cutting checks such as sessions, authentication, and authorization.
4. A custom router resolves the HTTP method and path to a controller action.
5. The controller translates the request into an application-level operation.
6. A service coordinates validation, business rules, transactions, and repository calls.
7. Repositories perform persistence through PDO.
8. The controller prepares a response and renders a PHP view or redirects after a successful command.
9. Exceptions are handled centrally and logged appropriately.

The design should use MVC principles, but the project must explain the responsibility of each part rather than treating “MVC” as a reason to create empty layers.

### Suggested boundaries

The exact directory structure will be defined by a later implementation specification. The conceptual boundaries are:

- **Front controller:** One public entry point for web requests.
- **Router:** Matches HTTP method and path and dispatches a known route.
- **Middleware:** Handles concerns that apply around request execution, such as session loading, authentication, authorization, CSRF protection, and request context.
- **Controller:** Handles HTTP concerns, extracts input, invokes an application service, chooses a view or redirect, and exposes safe presentation data. It should not contain SQL or substantial business rules.
- **Service/application layer:** Coordinates a use case, applies business rules, manages transaction boundaries where needed, and calls repositories or other services.
- **Repository:** Encapsulates persistence queries and mapping between database records and domain/application data. It should not decide HTTP behavior or render HTML.
- **Domain objects/models:** Represent meaningful business concepts and invariants where that provides clear value. Simple data may remain simpler rather than being wrapped in ceremonial abstractions.
- **DTOs:** Carry structured input or output across a boundary when doing so improves clarity, validation, or testability. DTOs should not be created merely to duplicate arrays.
- **Views:** Render escaped presentation data and reusable view components. Views must not query the database or enforce authorization.
- **Infrastructure:** Contains PDO setup, concrete repositories, logging, configuration loading, password hashing, and other technical adapters.

### Dependency direction

Dependencies should flow inward toward business and application rules where practical:

- HTTP and views depend on application services.
- Application services depend on repository contracts or focused collaborators.
- Infrastructure provides concrete implementations of those contracts.
- Domain rules must not require a controller, PHP superglobal, template, or PDO connection.

Dependency injection should be explicit. A small composition root should construct and wire application services and infrastructure dependencies. Avoid service locators, hidden global state, and static database access.

### Avoiding over-engineering

Every abstraction must have a clear reason to exist, such as isolating a business rule, avoiding repeated persistence logic, enabling testing, or protecting a boundary. Do not introduce a repository, interface, factory, event system, command bus, or domain aggregate solely because it is common in larger frameworks.

## 7. Roles and authorization

The system has exactly two roles for the initial scope:

### `ADMIN`

Administrators have full CRUD permissions for the managed system data, including:

- Employees
- Branches
- Departments
- Dispatch companies
- Employee contracts
- Employee portfolios
- System users

Administrators may also view and manage skills, projects, certifications, and their relationships as defined by later feature specifications.

### `USER`

Users have read-only access to permitted system data. They may view list and detail pages that the application allows, but they cannot create, update, or delete records.

### Authorization rules

- Authorization must be enforced on the server for every protected use case.
- A UI control being hidden is not authorization.
- Every mutating route must verify both authentication and the required role before executing its service.
- Unauthorized and unauthenticated requests must receive a consistent application response without leaking sensitive details.
- Authorization checks should be expressed close to the use case boundary, with middleware or policies used where they improve consistency.
- Read access to sensitive fields must be considered separately from general record visibility when later requirements introduce such fields.

## 8. Authentication and session requirements

- System users authenticate with a secure credential flow.
- Passwords must be stored only as one-way password hashes using PHP's password APIs.
- Successful login must regenerate the session identifier.
- Sessions must be configured securely, including appropriate cookie flags for the deployment environment.
- Logout must invalidate the authenticated session.
- State-changing forms must use CSRF protection.
- Login failures must not reveal whether a user account exists.
- Authentication and authorization failures must be logged at an appropriate level without logging passwords or other secrets.

The detailed login, logout, session timeout, password reset, and account-status behavior belongs in a later authentication specification.

## 9. Core domain requirements

### 9.1 Branches

A branch represents a company location in a city.

Required information:

- Branch code
- Branch name
- City
- Address
- Phone
- Status

Required capabilities:

- List
- Detail
- Create
- Update
- Delete
- Search

The system must be able to show employee counts by branch. A branch referenced by employees should not be hard-deleted without an explicit, later-defined data policy; the preferred default is to use status or a protected deletion rule so historical relationships remain valid.

### 9.2 Departments

A department represents an organizational unit to which employees belong.

Required information:

- Department code
- Department name
- Description
- Status

Required capabilities:

- List
- Create
- Update
- Delete
- Search

Employees belong to departments. The relationship and deletion behavior must preserve referential integrity.

### 9.3 Employees

The system supports two employee types:

- `permanent` — 正社員
- `dispatched` — 派遣社員

Required employee information:

- Employee number
- Name
- Name Kana
- Email
- Phone
- Photo reference or managed photo value
- Branch
- Department
- Position
- Employee type
- Hire date
- Status

Required capabilities:

- List
- Detail
- Create
- Update
- Delete
- Search
- Filter
- Sort
- Pagination

Employee number must be uniquely identifiable. Employee type must be represented as controlled data, not as arbitrary free text. The employee record must reference a branch and department through their identifiers.

### 9.4 Employee search

Users must be able to search employees by:

- Name
- Employee number
- Email

Filters must include:

- Branch
- Department
- Employee type
- Status

Multiple conditions must be combinable using `AND` semantics. For example:

> Department = Development AND Employee Type = dispatched AND Name contains Tanaka

Search results must display:

- The result count for the applied query
- Paginated results

Search, filter, sort, and pagination state must persist when navigating between result pages. Query construction must remain parameterized and must define an explicit, stable default sort so records do not move unpredictably between pages.

The later employee feature specification must define the allowed sort fields, default page size, maximum page size, case-sensitivity expectations, and behavior for empty or invalid filters.

### 9.5 Dispatch companies

Dispatch companies are managed as a separate entity. A dispatch company name must not be copied directly into the employee record as the relationship's source of truth.

Required information:

- Company name
- Contact person
- Email
- Phone
- Address
- Status

Dispatch employees may be associated with dispatch companies through contracts. This allows company details to be maintained once and historical contracts to retain their company relationship.

### 9.6 Employee contracts

Dispatched employees require employment or dispatch contract information.

Required information:

- Employee
- Dispatch company
- Contract start date
- Contract end date
- Status

The data model and architecture must support:

- Contract renewal
- Contract history
- Multiple historical contracts for one employee

Renewing a contract must create a new historical contract record or otherwise preserve the old contract as an immutable historical record. Renewal must not simply overwrite the previous contract dates or company relationship.

Business rules to be made explicit in the contract feature specification include date validity, overlapping contracts, permitted status transitions, whether only dispatched employees may have dispatch contracts, and whether a contract may change dispatch company on renewal.

### 9.7 Contract expiration alerts

The system must classify contracts as:

- `expired`
- `expiring_soon_7`
- `expiring_soon_30`
- `normal`

The Dashboard must display at least:

- Employee
- Dispatch company
- Contract end date

The classification must use one consistent application timezone and a clearly defined “today” boundary. The categories must be mutually understandable and must not produce contradictory labels. The detailed specification must define precedence, especially for expired contracts and contracts ending exactly today or exactly at the 7-day and 30-day boundaries.

### 9.8 Employee portfolios

An employee portfolio represents professional information associated with an employee. It may include a summary, work history, achievements, responsibilities, and other approved professional content.

The portfolio must be associated with the employee and must be manageable by administrators. Users may view permitted portfolio information. The later portfolio specification must define the fields, visibility rules, ordering, and whether portfolio entries are versioned.

### 9.9 Skills

Skills are reusable, controlled records that can be associated with employees and, where useful, projects or portfolio entries. The design should avoid duplicating the same skill as inconsistent free text across many employees.

The later skill specification must define skill names, optional categories or levels, status, search behavior, and relationship management.

### 9.10 Projects

Projects represent company work in which employees may participate or which may be referenced in an employee portfolio.

The later project specification must define project identity, status, dates, description, employee relationships, and whether project records are managed as a standalone module or primarily as portfolio history.

### 9.11 Certifications

Certifications represent professional qualifications associated with employees.

The design should distinguish the reusable certification definition from an employee's obtained certification when the requirements need issuer, issue date, expiration date, credential identifier, or document evidence.

The later certification specification must define those fields, status and expiration rules, search behavior, and relationship management.

### 9.12 System users

System users are authenticated accounts for people who access the application. A system user has:

- A unique login identifier, such as email or username
- A display name
- A securely stored password hash
- One of the supported roles
- An account status
- Created and updated timestamps

The later user-management specification must define whether a system user is linked to an employee, account lifecycle rules, password changes, and administrative safeguards. Administrators can manage users; ordinary users cannot manage users or elevate their own role.

## 10. Data and persistence direction

The database schema must use normalized relationships for core entities and foreign keys for required associations. Entity names, keys, timestamps, status values, and deletion behavior will be finalized in the individual feature specifications and migrations.

General persistence expectations:

- Use MySQL with an explicitly selected character set and collation suitable for Japanese and English data.
- Use PDO prepared statements for all values originating from application input.
- Keep SQL in repositories or dedicated persistence classes, never in controllers or templates.
- Use transactions for multi-step commands that must succeed or fail together.
- Preserve historical contracts and other records where deletion would destroy business history.
- Enforce important uniqueness and referential-integrity rules in the database as well as in application validation.
- Store timestamps consistently and convert them for display at the presentation boundary.
- Avoid storing derived values such as employee counts when they can be safely calculated, unless a later performance requirement justifies a maintained projection.
- Define upload storage and validation rules before implementing photo handling; never trust an uploaded filename or MIME type supplied by the client.

The application must distinguish validation errors, not-found conditions, authorization failures, conflict errors, and unexpected infrastructure failures so each can be handled appropriately.

## 11. Validation and error handling

Validation must occur on the server for every create and update operation. Client-side JavaScript may improve usability but cannot replace server-side validation.

Validation should cover:

- Required fields
- Field lengths and formats
- Email and date formats
- Controlled enum-like values such as status, role, and employee type
- Relationship existence
- Cross-field rules such as contract date ordering
- Uniqueness and conflict conditions

Expected validation errors should return the user to the form with safe, field-specific messages and previously entered non-secret values where appropriate.

Unexpected exceptions must be handled centrally. Users should receive a safe error response, while logs should contain enough context for diagnosis without exposing credentials, session secrets, or unnecessary personal data.

## 12. Logging and configuration

Configuration must be externalized from application code and must support environment-specific values for at least:

- Database connection settings
- Application environment and debug mode
- Application base URL
- Session and security settings
- Logging destination and level
- Upload/storage settings where applicable

Secrets must not be committed to Git. A documented example configuration may contain placeholders only.

Logging should support operational diagnosis and security review, including relevant authentication failures, authorization failures, validation or conflict events where useful, and unexpected exceptions. Logs must avoid passwords, raw session identifiers, and unnecessary sensitive employee information.

## 13. UI and presentation direction

The UI should be a professional, responsive, Material Design inspired administrative interface.

Common screens are expected to include:

- Login
- Dashboard
- Resource list pages
- Resource detail pages
- Create and edit forms
- Confirmation flows for destructive actions
- Search and filter controls
- Pagination controls that preserve query state
- Validation and system-error feedback

Views should use reusable partials or components for repeated elements such as navigation, alerts, form fields, tables, pagination, and confirmation dialogs. Templates must escape untrusted output by default.

JavaScript should enhance the server-rendered experience rather than turn the application into a client-side SPA. Core workflows must remain understandable and usable without depending on JavaScript for authorization or data integrity.

## 14. Security baseline

The implementation must address, at minimum:

- Prepared statements for SQL injection prevention
- Output escaping for XSS prevention
- CSRF protection on state-changing requests
- Password hashing and secure session handling
- Server-side authentication and authorization
- Safe file-upload handling for employee photos
- Input validation and bounded pagination parameters
- Safe redirects and error responses
- Protection of configuration secrets
- Audit-friendly logging without secret leakage

Security-sensitive behavior must be tested at the use-case or HTTP boundary, not only by inspecting rendered controls.

## 15. Testing and quality direction

Later feature branches should add tests proportionate to the risk and complexity of the feature. The project should progressively demonstrate:

- Unit tests for meaningful domain rules and services
- Repository or integration tests for important persistence behavior
- HTTP-level tests for routing, authentication, authorization, validation, and redirects
- Regression tests for contract history and expiration classification
- Manual browser verification for important server-rendered workflows and responsive layout

Code should favor small, named methods, explicit inputs, predictable side effects, and meaningful exception types. Formatting, static analysis, and test commands should be documented in the project setup specification when those tools are introduced.

## 16. Git and feature-branch workflow

The project is developed incrementally. Each feature specification and implementation should be isolated in its own Git branch, reviewed, and merged only after its requirements and tests are clear.

The master overview should remain stable. If a feature reveals a necessary architectural change, update this document deliberately and explain the reason in the relevant change history or pull request.

Suggested progression, subject to later planning:

1. Project bootstrap, configuration, Composer, PSR-4 autoloading, and front controller
2. Routing, views, error handling, and shared layout
3. Database connection and migration approach
4. Authentication, sessions, roles, and authorization
5. Branches and departments
6. Employees and employee search
7. Dispatch companies and contract history
8. Contract expiration dashboard
9. Portfolios, skills, projects, and certifications
10. System-user administration, hardening, testing, and operational polish

The progression is a learning aid, not permission to implement all features in one branch.

## 17. Decisions reserved for later specifications

The following details are intentionally left to feature-level specifications:

- Exact database table and column names
- Migration and seed strategy
- Exact directory structure and namespace names
- Route names and URL conventions
- HTML form field naming
- Pagination defaults and maximums
- Status catalogs and allowed transitions
- Soft-delete versus protected-delete policy for each entity
- File-storage provider and image-processing rules
- Dashboard layout and additional metrics
- Audit history requirements
- Password reset and account recovery workflow
- Exact date/timezone policy, including contract boundary calculations

Later specifications must resolve these decisions explicitly before implementation and must remain consistent with the requirements in this document.

## 18. Non-negotiable principles

1. No framework replacement for understanding the application architecture.
2. No ORM; persistence uses PDO and deliberate SQL.
3. No business logic hidden in templates or scattered across controllers.
4. No client-only authorization.
5. No overwriting historical contracts during renewal.
6. No direct copying of dispatch company names into employee records as the relationship source of truth.
7. No abstraction without a clear responsibility and benefit.
8. No secret or personal data leakage through logs, errors, or committed configuration.
9. No feature is complete until its validation, authorization, failure behavior, and relevant tests are considered.

