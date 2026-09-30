# Phase 10: System Users and Security Specification

Status: reviewed design specification

This document specifies Phase 10 of the Company Employee Management System:
production-style authentication, session security, authorization, CSRF
protection, and administration of system users.

It does not implement PHP classes, migrations, routes, views, tests, or
credentials. Implementation must preserve the existing Pure PHP, PDO,
server-rendered architecture and must not introduce a framework, ORM, SPA,
REST API, OAuth, MFA, registration, email verification, or password-reset
flow.

## 1. Purpose and security boundary

Phase 10 answers two separate questions:

- Authentication: who is making the request?
- Authorization: what may that authenticated user do?

The application is an internal employee-management system. After Phase 10,
all application pages are protected by default. The only public application
page is the login flow. Business data remains server-rendered HTML, and every
state-changing form remains a POST followed by a 303 redirect on success.

The phase includes:

- dedicated system-user accounts, separate from employees;
- login and POST-only logout;
- PHP session authentication;
- `ADMIN` and `USER` roles;
- centralized server-side route authorization;
- CSRF protection for every HTML form mutation, including login and logout;
- password hashing and verification through PHP's password API;
- secure session-cookie and session-fixation handling;
- ADMIN-only system-user management;
- English/Japanese security UI;
- unit, HTTP, database, and regression tests.

The phase does not claim to solve unrelated infrastructure concerns such as
network TLS termination, host hardening, rate limiting, account lockout,
MFA, malware scanning, or audit-log compliance. HTTPS is an operational
requirement for production. The application may use direct request HTTPS
detection for the Secure session-cookie flag, but must not trust arbitrary
`X-Forwarded-Proto` or similar client-supplied headers. If production is
behind a reverse proxy, trusted-proxy HTTPS detection must be explicitly
configured at the deployment/application boundary before proxy headers are
trusted. The application must not silently weaken cookie or session security.

## 2. Current-state audit

### 2.1 Actual request flow

The current application flow is:

```text
Browser
  -> public/index.php
  -> EnvironmentLoader
  -> ApplicationBootstrap
  -> HttpKernel::handle(Request)
  -> Router::match(Request)
  -> MiddlewarePipeline
  -> Controller
  -> Application Service
  -> Repository Interface
  -> PDO repository / MySQL
  -> ViewRenderer or redirect Response
  -> ResponseEmitter
```

`public/index.php` loads Composer autoloading and the optional `.env`, creates
the application kernel, converts PHP globals into `Request`, and emits the
resulting `Response`. Setup failures are converted to a generic text 500.

`HttpKernel` currently matches the route before constructing the middleware
pipeline. The pipeline currently contains only `LocaleMiddleware`. The router
supports exact and named-parameter GET/POST routes, and returns 404 or 405
with an `Allow` header through the existing exception responder.

`Request` exposes method, path, query parameters, body parameters, headers,
cookies, and route parameters. It has no session, user, route metadata, or
CSRF state. `Response` owns status and headers and validates header injection,
while `ResponseEmitter` sends them through PHP's native `header()` function.

### 2.2 Existing session, cookie, and flash state

There is no `session_start()`, `$_SESSION` access, authentication cookie,
flash-message service, or authentication state in the current codebase.

`LocaleMiddleware` is the only cookie-aware component. It reads and writes
the non-sensitive `app_locale` preference cookie with `Max-Age`, `Path=/`, and
`SameSite=Lax`. It does not provide an authentication session and is not a
security boundary.

There is no current flash-message abstraction. Existing success and notice
flows use allowlisted query-string notice values after a 303 redirect, while
validation failures render the form directly with HTTP 422 and preserved
non-sensitive values.

Phase 10 must introduce one centralized session abstraction. It must not
scatter raw `$_SESSION` access through controllers or views. Existing locale
cookie behavior may remain separate; the authentication session cookie must
have stricter settings.

### 2.3 Existing routes and features

The current route set is:

```text
GET  /

GET  /employees
GET  /employees/create
POST /employees
GET  /employees/{id}
GET  /employees/{id}/edit
POST /employees/{id}
GET  /employees/{id}/deactivate
POST /employees/{id}/deactivate

GET  /employees/{employeeId}/skills
GET  /employees/{employeeId}/skills/create
POST /employees/{employeeId}/skills
GET  /employees/{employeeId}/skills/{skillId}/edit
POST /employees/{employeeId}/skills/{skillId}
GET  /employees/{employeeId}/skills/{skillId}/archive
POST /employees/{employeeId}/skills/{skillId}/archive

GET  /employees/{employeeId}/projects
GET  /employees/{employeeId}/projects/create
POST /employees/{employeeId}/projects
GET  /employees/{employeeId}/projects/{projectId}
GET  /employees/{employeeId}/projects/{projectId}/edit
POST /employees/{employeeId}/projects/{projectId}
GET  /employees/{employeeId}/projects/{projectId}/archive
POST /employees/{employeeId}/projects/{projectId}/archive

GET  /employees/{employeeId}/certifications
GET  /employees/{employeeId}/certifications/create
POST /employees/{employeeId}/certifications
GET  /employees/{employeeId}/certifications/{certificationId}
GET  /employees/{employeeId}/certifications/{certificationId}/edit
POST /employees/{employeeId}/certifications/{certificationId}
GET  /employees/{employeeId}/certifications/{certificationId}/archive
POST /employees/{employeeId}/certifications/{certificationId}/archive

GET/POST /branches and /branches/{id} lifecycle routes
GET/POST /departments and /departments/{id} lifecycle routes
GET/POST /dispatch-companies and /dispatch-companies/{id} lifecycle routes
GET/POST /dispatch-contracts and /dispatch-contracts/{id} lifecycle routes
```

The exact route patterns remain the source of truth in `routes/web.php`.
Phase 10 adds `/login`, `/logout`, and `/system-users` routes and updates the
existing route registration with explicit access metadata.

### 2.4 Existing architecture and conventions

Application composition is explicit in `ApplicationBootstrap`. Controllers
receive services and `ViewRenderer`; services receive repository interfaces,
validators, and the UTC `Clock`; PDO repositories own prepared SQL and
database transactions. Views receive prepared arrays and use
`HtmlEscaper::escape()` for dynamic output.

The existing conventions to preserve are:

- GET renders pages with HTTP 200;
- successful POST mutations redirect with HTTP 303;
- invalid forms render HTTP 422 with submitted non-sensitive values and
  localized field errors;
- missing resources use the existing 404 exception path;
- duplicate and domain conflicts are mapped to localized validation or notice
  responses;
- timestamps are generated through the existing UTC `Clock`;
- values are bound through PDO prepared statements;
- system-controlled labels come from `Translator` in EN and JA;
- user-entered names, emails, notes, and descriptions are escaped but never
  translated.

### 2.5 Current lifecycle and database findings

The current database has seven migrations, including Phase 09 portfolio
tables. Tables use `BIGINT UNSIGNED` keys, InnoDB, `utf8mb4`,
`utf8mb4_unicode_ci`, UTC `DATETIME` timestamps, explicit indexes, prepared
PDO statements, and restrictive foreign keys where relationships exist.

Employees, branches, departments, and dispatch companies use `active` and
`inactive` lifecycle values. Employees and portfolio records are not
physically deleted. Inactive employees remain readable but are blocked from
mutation by both service and persistence rules. Phase 10 authorization is an
additional access boundary; it must not weaken these existing lifecycle rules.

The development database boundary is explicit: `bin/seed` may run only when
`APP_ENV=local` and refuses test-like database names. Integration tests use an
explicit `_test` database through `DB_TEST_*` environment variables. No
authentication account table or initial administrator currently exists.

## 3. Goals and non-goals

### 3.1 Goals

Phase 10 must:

1. add dedicated system-user accounts rather than reusing employees;
2. authenticate active users with email and password;
3. store only password hashes, never plaintext passwords;
4. regenerate and protect the session after login;
5. enforce ADMIN/USER authorization on the server for every route;
6. make USER access read-only for business data;
7. make all business mutations ADMIN-only;
8. protect every state-changing HTML POST with a session CSRF token;
9. prevent session fixation, open redirects, authentication enumeration, and
   authorization bypasses within the application boundary;
10. prevent the application from reaching a state with no active ADMIN;
11. provide ADMIN-only system-user CRUD, deactivation, and reactivation without physical delete;
12. support EN/JA login, errors, roles, statuses, and management UI;
13. preserve authorized Phase 01–09 behavior and test coverage.

### 3.2 Non-goals

This phase does not add:

- public registration;
- forgot-password or reset links;
- email verification;
- OAuth, social login, API tokens, JWT, or bearer authentication;
- MFA;
- login throttling or account lockout;
- physical deletion of system users;
- employee-as-account behavior;
- a public API or SPA;
- an audit-log product beyond the existing timestamps and last-login field.

## 4. System-user data model

### 4.1 Dedicated `system_users` table

Add one forward-only migration after the current migrations. Do not edit an
existing migration. The implementation should use the next unused project
migration version, for example `Version20260929000800CreateSystemUsers`,
after verifying the migration directory before coding.

The table is:

| Column | Type / rule | Purpose |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | Stable account identifier |
| `name` | `VARCHAR(120) NOT NULL` | Display name |
| `email` | `VARCHAR(254) NOT NULL` | Normalized login identifier |
| `password_hash` | `VARCHAR(255) NOT NULL` | Output of `password_hash()` only |
| `role` | `VARCHAR(20) NOT NULL DEFAULT 'USER'` | Persisted `ADMIN` or `USER` |
| `status` | `VARCHAR(20) NOT NULL DEFAULT 'active'` | Persisted `active` or `inactive` |
| `last_login_at` | `DATETIME NULL` | Successful-login timestamp in UTC |
| `created_at` | `DATETIME NOT NULL` | UTC creation timestamp |
| `updated_at` | `DATETIME NOT NULL` | UTC update timestamp |

Constraints and indexes:

- primary key on `id`;
- unique key on `email`;
- index on `(status, role)` for active-ADMIN checks and authorization reads;
- index on `last_login_at` only if query/report requirements justify it;
- CHECK `role IN ('ADMIN', 'USER')`;
- CHECK `status IN ('active', 'inactive')`;
- InnoDB, `utf8mb4`, and `utf8mb4_unicode_ci`;
- no foreign keys: a system user is an account root, not an employee or
  organization child;
- no physical delete route or repository operation.

The unique email index relies on the existing case-insensitive collation.
Application validation also lowercases and trims email before every lookup or
write so the stored representation is deterministic.

The migration down path drops `system_users` only as normal migration rollback
behavior. The application has no user-data deletion behavior.

### 4.2 Persisted values

Persist roles as uppercase `ADMIN` and `USER`, because roles are security
identifiers and must not vary by locale. Persist statuses as lowercase
`active` and `inactive`, matching existing lifecycle conventions.

Translator keys provide the EN/JA display labels. User-entered names and
emails are never translated.

### 4.3 Repository contract

Add a focused `SystemUserRepositoryInterface` and PDO implementation. The
repository must expose only needed fields and must never return
`password_hash` to controllers or views. Suggested operations are:

- list users with id, name, email, role, status, last_login_at, timestamps;
- find by id with safe display fields;
- find authentication record by normalized email, including the hash only to
  the authentication service;
- insert a hashed-password account;
- update allowed profile fields and, separately, an optional new hash;
- count active ADMIN accounts with a transaction-safe lock path;
- deactivate an account with a conditional status update;
- activate an inactive account with a conditional status update;
- update `last_login_at` and optionally a rehashed password.

Authentication-specific repository methods may return a private DTO containing
the hash, but that DTO must not cross into a view or generic user-management
response.

## 5. Validation and password policy

### 5.1 Name

- required string;
- trim surrounding whitespace;
- maximum 120 Unicode characters using the project’s multibyte length helper;
- preserve internal user-entered characters after trimming;
- never accept arbitrary array/object values.

### 5.2 Email

- required string;
- trim and lowercase for storage and lookup;
- maximum 254 characters;
- validate with `FILTER_VALIDATE_EMAIL` after normalization;
- check uniqueness in the service and rely on the database unique key for the
  final race-safe boundary;
- on conflict, show a generic localized duplicate-email validation error;
- preserve the normalized email on a 422 form, but never preserve a password.

### 5.3 Role and lifecycle status

- role input is allowlisted to `ADMIN` or `USER`;
- status is not an ordinary editable form field;
- create always starts `active`;
- deactivation is a separate confirmed action;
- reactivation is a separate ADMIN-only confirmed action;
- inactive accounts remain readable to ADMIN users but are read-only except for reactivation;
- activation is allowed only for an inactive target and never changes the password hash,
  role, identity, or historical timestamps other than `updated_at`.

### 5.4 Password

Use only `password_hash()` and `password_verify()` with `PASSWORD_DEFAULT`.
Do not invent cryptography, salt handling, or custom password storage.

The Phase 10 application policy is:

- minimum 12 characters;
- maximum 128 bytes accepted by the form/service boundary;
- no artificial composition rule beyond length, to avoid treating a weak
  complexity checklist as a security guarantee;
- required on account creation;
- required confirmation on account creation;
- optional on ordinary edit; blank means keep the existing hash;
- if a new password is supplied during edit, confirmation is required and the
  same min/max policy applies;
- password fields are never redisplayed after a validation failure;
- passwords and hashes are never logged, translated, placed in query strings,
  returned to views, or included in exception messages.

After a successful password verification, call `password_needs_rehash()`.
If it returns true, transparently replace the stored hash in the same
successful-login flow. This keeps existing accounts compatible if PHP changes
the `PASSWORD_DEFAULT` algorithm without requiring a separate password-change
screen.

For an email that does not exist, use the same generic failure path and a
dummy password-hash verification path so the response does not reveal whether
the email exists. The error must be the same for unknown email, wrong
password, and inactive account.

## 6. Authentication and session design

### 6.1 Proposed components

Introduce these focused responsibilities within the existing architecture:

- `SessionManager`: starts/configures the native PHP session, reads/writes
  allowlisted session values, regenerates IDs, clears authentication, and
  destroys the session safely;
- `AuthenticationService`: validates login credentials, verifies hashes,
  performs transparent rehashing, updates `last_login_at`, and establishes
  the authenticated identity;
- `AuthenticatedUser` or equivalent safe identity DTO: id, name, email, and
  role only;
- `AuthenticationMiddleware`: loads the current account by session user id,
  rejects missing/inactive accounts, and exposes the safe identity to the
  request context;
- `AuthorizationMiddleware`: enforces route access metadata and role rules;
- `CsrfTokenManager` and `CsrfMiddleware`: own token generation and mutation
  validation;
- `SystemUserService`: owns account management and last-ADMIN rules.

No controller or view may call `session_start()` or access `$_SESSION`
directly.

### 6.2 Session initialization

`SessionMiddleware`/`SessionManager` runs once for every HTTP request before
locale, authentication, authorization, or controller execution. It must:

1. set session cookie parameters before `session_start()`;
2. enable `session.use_strict_mode=1`;
3. use cookies only (`session.use_only_cookies=1`);
4. disable URL-based session IDs (`session.use_trans_sid=0`);
5. set `HttpOnly=true`;
6. set `SameSite=Lax`;
7. set `Secure=true` when direct HTTPS is detected, otherwise use
   `Secure=false` only for local HTTP development;
8. use a dedicated session name such as `company_employee_session`, not the
   default PHP session name;
9. start the session before any response is emitted.

The auth session stores only:

```text
auth.user_id       positive integer
csrf.token         cryptographically random token
optional flash data / locale-independent notices
```

The session is not the source of truth for role or status. Middleware loads
the current system-user row on each protected request, so an account
deactivated or demoted elsewhere loses access on the next request.

Direct HTTPS detection must not be inferred from arbitrary client-supplied
`X-Forwarded-Proto` or equivalent headers. A production reverse-proxy setup
must explicitly configure which proxy boundary is trusted and how HTTPS is
communicated before the application accepts proxy-derived HTTPS metadata.
Until that boundary is configured, the application must preserve the secure
deployment requirement rather than silently treating an untrusted header as
proof of HTTPS.

### 6.3 Login

Routes:

```text
GET  /login
POST /login
```

GET `/login` ensures a CSRF token exists and renders the localized login form
only when the request is unauthenticated. Authentication identity loading is
not blindly skipped for a public route: the authentication middleware may
resolve a valid session user before public-route authorization is evaluated.
Therefore an already-authenticated GET `/login` returns HTTP 303 to `/` and
does not render a second login form.

POST `/login`:

1. CSRF middleware validates the hidden token;
2. if the request already has a valid authenticated identity, it does not
   establish a second login state and redirects safely with HTTP 303 to `/`;
3. otherwise the service normalizes the email and validates bounded input;
4. the repository loads an authentication-only account record;
5. the service calls `password_verify()` against the account hash or a dummy
   hash when no account exists;
6. only an active account with a valid password may continue;
7. `session_regenerate_id(true)` runs after successful verification;
8. the session stores only the user id and a freshly rotated CSRF token;
9. `last_login_at` is updated in UTC, with transparent rehash if required;
10. the response redirects with HTTP 303 to `/`.

The already-authenticated POST behavior is evaluated only after the required
CSRF policy has passed. It must not create a redirect loop or replace the
current session identity.

There is no `return`, `next`, or arbitrary redirect query parameter. This
avoids open redirects and keeps login behavior deterministic.

Failed login renders the login page with HTTP 422 and one generic localized
message such as “The email or password is incorrect.” It may preserve the
normalized email only. It must not reveal unknown email, wrong password, or
inactive-account status.

Inactive users cannot authenticate. They receive the same generic failure
message and no session identity is established.

### 6.4 Logout

Route:

```text
POST /logout
```

Logout is authenticated and CSRF protected. It must:

1. clear the auth identity;
2. remove all session data;
3. expire the session cookie;
4. call `session_destroy()` safely;
5. redirect with HTTP 303 to `/login?notice=logged-out`.

The notice value is allowlisted and contains no user-controlled data. GET
`/logout` is not registered and therefore returns 405/404 according to the
router path behavior; it never changes state.

### 6.5 Account state discovered during a request

If a session user id is missing, malformed, deleted, or inactive, the
authentication middleware clears the session and redirects to `/login` with a
localized allowlisted notice. It must not render protected content and must
not expose whether the account was deleted or deactivated.

If the account remains active but the role has changed, the middleware uses
the current persisted role immediately. No stale role in session may grant
access.

## 7. Authorization architecture

### 7.1 Route access metadata

The current `Router` has no route access metadata, so Phase 10 should make a
small compatible extension rather than repeat role checks in every controller.
Each route definition receives one explicit access class:

```text
public              no authenticated identity required
authenticated_read active authenticated user required
admin               active ADMIN required
authenticated       active authenticated user required, any role
```

`Route`/`RouteMatch` should carry this trusted metadata into the immutable
request context. `HttpKernel` already matches a route before building the
middleware pipeline, so it can attach the matched access class without
changing controller signatures. This keeps the policy auditable at the route
registration point and avoids a second path-pattern policy table.

### 7.2 Actual proposed middleware order

The Phase 10 request flow is:

```text
public/index.php
  -> EnvironmentLoader
  -> ApplicationBootstrap
  -> Request
  -> Router::match (route parameters + access metadata)
  -> SessionMiddleware
  -> LocaleMiddleware
  -> AuthenticationMiddleware
  -> AuthorizationMiddleware
  -> CsrfMiddleware for POST
  -> Controller
  -> Service
  -> Repository / PDO
  -> Response
  -> ResponseEmitter
```

The order is deliberate:

- session must exist before auth and CSRF;
- locale must be selected before auth/authorization errors are rendered or
  redirected;
- authentication must resolve the current account before authorization;
- authorization must reject USER mutation routes before controller execution;
- CSRF must reject state-changing requests before a controller or service can
  write data;
- login is public but still passes through CSRF because it is a POST form;
- logout is authenticated and passes through both authorization and CSRF.

`AuthenticationMiddleware` evaluates the session on every request, including
`public` routes. A public route permits an anonymous request, but it does not
prevent the middleware from resolving a valid authenticated identity. This is
required for the login route to redirect an already-authenticated user to `/`.
`AuthorizationMiddleware` turns `authenticated_read` and
`authenticated` into an active-user requirement and `admin` into an active
ADMIN requirement.

### 7.3 Centralized security error responses

Authorization and CSRF middleware must not each construct localized HTML
responses. Add one small security-error response boundary compatible with the
existing architecture, such as `SecurityErrorResponder` (or an equivalent
focused component). It owns safe localized responses for at least:

- HTTP 403 authorization failure;
- HTTP 403 missing or invalid CSRF.

The responder may use the existing `Translator` and `ViewRenderer`, or a
focused safe response view, but it must not expose account, session, token,
SQL, or other security internals. `AuthorizationMiddleware` and
`CsrfMiddleware` delegate to this boundary, and controllers never implement
these middleware security errors individually. The existing exception
response boundary may delegate its generic 403 path to the same component so
all security denials use one safe presentation policy.

### 7.4 Access policy for current routes

Only login is public:

```text
GET  /login   public
POST /login   public + CSRF
```

Logout is authenticated for both roles:

```text
POST /logout  authenticated + CSRF
```

USER and ADMIN may read business data:

```text
GET /
GET /employees
GET /employees/{id}
GET /branches
GET /branches/{id}
GET /departments
GET /departments/{id}
GET /dispatch-companies
GET /dispatch-companies/{id}
GET /dispatch-contracts/{id}
GET /employees/{employeeId}/skills
GET /employees/{employeeId}/projects
GET /employees/{employeeId}/projects/{projectId}
GET /employees/{employeeId}/certifications
GET /employees/{employeeId}/certifications/{certificationId}
```

These routes are `authenticated_read`.

All create, edit-form, confirmation, update, activate, deactivate, archive, contract
renewal, and portfolio mutation routes are `admin`. This includes:

- employee create/update/deactivate;
- branch create/update/deactivate;
- department create/update/deactivate;
- dispatch-company create/update/deactivate;
- dispatch-contract create/update/renew;
- skill create/update/archive;
- project create/update/archive;
- certification create/update/archive.

GET forms are still `admin`: displaying a mutation form must not become a
USER capability merely because it does not write immediately.

All `/system-users` routes are `admin`, except no public system-user route is
provided.

Authenticated USER requests to an ADMIN or mutation route receive a generic
HTTP 403 response. Hiding a button is only a UI convenience; the middleware
and repository/service boundaries must reject crafted requests.

## 8. CSRF protection

### 8.1 Token design

Use one per-session synchronizer token:

- generate with `bin2hex(random_bytes(32))`;
- store only in the server-side session;
- expose it to a form only as a hidden `csrf_token` input;
- compare submitted and stored values with `hash_equals()`;
- reject missing, empty, malformed, or mismatched values;
- never accept a token from a GET query parameter, URL path, cookie, or
  custom user-controlled fallback;
- rotate the token after successful login and clear it on logout.

One token per session is sufficient for this same-origin SSR application. A
single token avoids token storage complexity while remaining resistant to
cross-site form submission when the attacker cannot read the session token.

### 8.2 Middleware behavior

`CsrfMiddleware` applies to every POST route, including login, logout, all
existing business mutations, and system-user mutations. There are no state
changes through GET. Missing or invalid CSRF returns a localized generic HTTP
403 response and the controller is never called.

A CSRF failure does not redirect to login and does not retry the write. It is a
security rejection, not a validation failure.

### 8.3 Form migration

Every existing HTML POST form must include the same hidden field, including:

- employee create/update/deactivate;
- branch and department create/update/deactivate;
- dispatch-company create/update/deactivate;
- dispatch-contract create/update/renew;
- employee skill/project/certification create/update/archive;
- login and logout;
- system-user create/update/activate/deactivate.

The existing view renderer/bootstrap should provide the token through a
centralized view-data boundary or shared CSRF field partial. Controllers must
not generate tokens independently. Error re-renders preserve safe form
values but use the current session token.

## 9. System-user management

### 9.1 Routes

Add these ADMIN-only routes:

```text
GET  /system-users
GET  /system-users/create
POST /system-users
GET  /system-users/{id}
GET  /system-users/{id}/edit
POST /system-users/{id}
GET  /system-users/{id}/deactivate
POST /system-users/{id}/deactivate
GET  /system-users/{id}/activate
POST /system-users/{id}/activate
```

There is no delete route. Activation and deactivation are separate confirmed
GET/POST lifecycle operations and are ADMIN-only.

### 9.2 Use cases

The list shows name, email, role, status, and last login. Detail shows the
same safe fields plus timestamps. No password hash or password-derived data is
rendered.

Create accepts name, email, role, password, and password confirmation. The
new account is active. The service hashes the password before the repository
write.

Edit accepts name, normalized email, role, and an optional replacement
password plus confirmation. Status is not changed from the ordinary edit
form. Inactive users remain readable but are read-only except for the
separate ADMIN-only activation operation.

Deactivate uses a confirmation page and a separate POST. It is idempotent only
for a target already inactive if the target is not the current account and
last-ADMIN protections still hold. No row is physically deleted.

Activate uses a confirmation page and a separate CSRF-protected POST. It is
valid only for an inactive target. An active target is not activated a second
time; the controller returns an allowlisted localized already-active notice
without changing the row. Successful activation changes only status and
`updated_at`, preserving the password hash, role, identity, and historical
timestamps. A reactivated account can authenticate and access the application
again on its next request.

### 9.3 Last-ADMIN and self-protection rules

The following rules are mandatory:

1. An ADMIN cannot deactivate their own currently authenticated account.
2. An ADMIN cannot demote their own currently authenticated account from
   ADMIN to USER.
3. The last active ADMIN cannot be deactivated.
4. The last active ADMIN cannot be demoted.
5. If a target is an active ADMIN, the persistence layer must own one atomic
   transaction that locks the relevant ADMIN state using a consistent lock
   strategy and order, rechecks the target account, rechecks the number and
   state of active ADMIN accounts, rejects self/last-ADMIN violations as
   appropriate, performs the mutation only while the invariant is protected,
   and commits atomically.
6. The last-active-ADMIN invariant must not be implemented as a service count
   query followed by a service decision and a separate repository update. The
   service owns business orchestration and maps repository/domain outcomes,
   while SQL, row locking, transaction boundaries, and the invariant's
   recheck remain in the PDO repository.
7. Concurrent ADMIN changes must use the same lock order and transaction
   protocol so two requests cannot both observe a safe state and leave zero
   active ADMIN accounts.
8. Creating an ADMIN is allowed for an existing ADMIN and never reduces the
   active-ADMIN count.
9. An inactive ADMIN does not count toward the active-ADMIN minimum.
10. If an external/manual database change creates an invalid no-active-ADMIN
   state, the application must fail closed for authorization and report a
   safe administrative configuration error; normal UI operations must never
   create that state.

Reactivation is ADMIN-only and does not remove any self-protection or
last-active-ADMIN protection. The repository owns the activation status
transition and transaction boundary; an active target produces an explicit
already-active outcome and no mutation.

The service must return explicit domain outcomes for self-target, last-ADMIN,
inactive-target, missing-target, duplicate-email, and validation conflicts.
Controllers map them to localized 403, 422, 404, or allowlisted 303 notices
consistent with existing conventions.

## 10. Initial administrator strategy

No default account or plaintext password may be committed. The existing
`DevelopmentSeeder` remains development-only sample business data and must not
silently create a universal credential.

Add a separate CLI bootstrap path during implementation, such as:

```text
php bin/system-user create-admin
```

The command must:

- load the same environment/configuration boundary as `bin/migrate` and
  `bin/seed`;
- require an explicit interactive name, email, and password confirmation;
- avoid placing the password in command-line arguments or repository files;
- hash the password immediately with `password_hash()`;
- use the same service/repository validation and last-ADMIN rules;
- be usable for local setup and controlled production deployment;
- refuse ambiguous or unsafe non-interactive invocation unless an explicit
  deployment policy is later approved.

For automated tests, fixtures may insert a known test-only hash using
`password_hash()` in the test process. Tests must never depend on a real local
credential. If an environment-driven bootstrap is later desired for CI, it
must be explicit, test-only, and must not provide a default password.

## 11. HTTP behavior

| Situation | Response |
| --- | --- |
| Unauthenticated GET login | 200 |
| Authenticated GET login | 303 to `/` |
| Valid login | 303 to `/` |
| Authenticated POST login after valid CSRF | 303 to `/`; existing identity unchanged |
| Invalid login | 422 with generic error; email only preserved |
| Inactive login | same generic 422 error |
| Logout POST with valid CSRF | 303 to login |
| GET logout | 404/405; no state change |
| Unauthenticated protected request | 303 to `/login` |
| Authenticated USER on read route | 200 if resource exists |
| Authenticated USER on ADMIN route | generic 403 |
| Authenticated ADMIN on valid mutation form | 200 |
| Successful mutation | 303 to canonical resource/list |
| Invalid mutation input | 422 with safe values/errors |
| Missing resource | existing 404 path |
| Missing/invalid CSRF | localized generic 403; no controller write |
| Inactive session account | clear session and 303 to login |
| Duplicate system-user email | 422 localized conflict |
| Self/last-ADMIN violation | 403 or 422 by domain outcome; no write |
| Inactive system-user activation | GET confirmation; valid POST+CSRF changes only status/updated_at and redirects with a localized notice |
| Already-active system-user activation | allowlisted localized already-active notice; no write |

The centralized security-error response boundary should provide the safe 403
path without exposing SQL, password, session, token, or account-existence
details. Security messages should be localized through the existing
`Translator`; diagnostics remain debug-only and bounded.

## 12. UI and localization

### 12.1 Shared shell

The existing topbar currently contains a placeholder administrator display.
After authentication it should display the safe authenticated name and role,
with a POST logout form containing a CSRF field. It must not display an email
unless the UI requirement explicitly calls for it, and it must never display a
hash or session identifier.

The sidebar should show a System Users link only for ADMIN users. The link
visibility mirrors authorization but does not replace it. USER sees the
business-data navigation and no mutation controls.

### 12.2 Login and system-user pages

Add Material Design-inspired SSR pages for login, system-user list/detail,
create, edit, activate confirmation, and deactivate confirmation. Use existing cards, page headers,
forms, tables, alerts, status chips, empty states, and button conventions.

All dynamic output must use `HtmlEscaper::escape()`. Password inputs have no
value attribute on initial or error rendering. Forms use semantic labels,
`autocomplete` values appropriate to email/current/new password, and visible
localized error text.

### 12.3 Required EN/JA keys

Add translator keys for:

- login, logout, session/account notices;
- email, password, password confirmation;
- generic authentication failure;
- forbidden and CSRF failure;
- system users and management descriptions;
- ADMIN/USER labels;
- active/inactive account labels;
- create/edit/activate/deactivate actions and success notices;
- duplicate email and password validation;
- self-account and last-ADMIN protections;
- last-login display and no-login state.

Never translate user-entered names or email addresses.

## 13. Security boundaries

### SQL injection

All user-controlled values use prepared PDO parameters. Route access metadata,
sort-like security policy values, and table/column identifiers are trusted
code, not concatenated user input. Email and user IDs are validated before
repository calls.

### XSS

All names, emails, notices, and validation values are escaped at output. Error
messages are selected from allowlisted translator keys. Passwords, hashes,
tokens, and session IDs are never rendered.

### CSRF

Every POST is checked by the centralized synchronizer-token middleware. GET
never mutates state.

### Session fixation

The session ID is regenerated with deletion of the old session after successful
login. Strict mode, cookie-only sessions, HttpOnly, SameSite=Lax, and Secure
on directly detected HTTPS are enabled centrally. Arbitrary proxy headers are
not trusted; trusted reverse-proxy HTTPS detection must be configured at the
deployment/application boundary before it is used.

### Password storage

Only `password_hash(PASSWORD_DEFAULT)` output is persisted. Verification uses
`password_verify()`, and successful logins may transparently rehash. Passwords
are not logged, queried, returned, or stored in session.

### Authentication enumeration

Unknown email, incorrect password, and inactive account use one generic
failure message and equivalent verification flow.

### Authorization bypass

Route metadata and middleware enforce access before controller execution.
Services and repositories remain responsible for existing domain lifecycle
rules. User-management writes additionally enforce last-ADMIN/self rules in a
transaction. Direct crafted POST requests receive 403/422 and cannot write.

### Open redirects

Login always redirects to `/`; no arbitrary return URL is accepted. Any future
intended-destination feature must accept only a parsed local path beginning
with `/` and reject scheme-relative, absolute, encoded, or external targets.

### Inactive accounts

Inactive users cannot log in. Existing sessions are invalidated when the
current account is discovered inactive. Inactive system-user records remain
readable to ADMIN, cannot be normally edited, and may be reactivated only
through the separate ADMIN-only confirmation and POST+CSRF flow.

### Mass assignment

DTOs and validators explicitly allowlist name, normalized email, role, and
optional password fields. Status, IDs, timestamps, and password hashes are not
accepted as arbitrary request fields. Deactivation has its own use case.

### Secrets and configuration

No credentials or password defaults are committed. Database and application
environment values continue to come from process configuration/ignored `.env`.
Development/test bootstrap uses explicit test-only values or interactive CLI
input. Production deployment must use HTTPS and a protected operational path
for the first administrator.

## 14. Migration and composition plan

Implementation must add only the new system-user migration and leave prior
migrations unchanged. Migration tests must update expected migration/table
counts in the existing integration fixtures without inserting seed users into
the migration.

Application composition should add, through `ApplicationBootstrap`:

- `SystemUserRepositoryInterface` -> PDO implementation;
- authentication and system-user services;
- `SessionManager`, `CsrfTokenManager`, and security middleware;
- login and system-user controllers;
- route access metadata and the new route registrations;
- translator and view-renderer security dependencies.

The HTTP application must not run migrations or seed accounts automatically.

## 15. Test strategy

### 15.1 Unit tests

Add focused tests for:

- system-user name/email/role/password validation;
- email normalization and maximum length;
- password minimum/maximum and confirmation behavior;
- password hashing/verification and rehash decision logic;
- session manager identity clearing and regeneration orchestration where
  testable without global PHP session state;
- CSRF token generation, `hash_equals()` validation, missing/invalid token,
  and refusal of query-string tokens;
- route access policy for public, authenticated, and ADMIN routes;
- last-ADMIN/self-protection decisions;
- safe local redirect policy if introduced.

### 15.2 Database integration tests

Using the existing explicit `_test` database convention, cover:

- migration creates/drops `system_users` with expected columns and indexes;
- repeated migration is idempotent;
- unique normalized email;
- role/status CHECK constraints where supported by the DB engine;
- password hashes persist and are not plaintext fixture values;
- active/inactive and last-login persistence;
- system-user repository ownership of safe/authentication records;
- deactivation and concurrent last-ADMIN protection paths;
- no physical delete path;
- rollback order after portfolio and system-user migrations.

Tests must clean their schema and never use the configured development
database.

### 15.3 HTTP and feature tests

Cover:

- GET login renders EN and JA labels and CSRF field;
- successful login regenerates session identity and redirects to `/`;
- failed login returns generic 422 and preserves only email;
- unknown, wrong-password, and inactive-account failures are indistinguishable
  at the message/status boundary;
- logout is POST-only, CSRF protected, and clears access;
- unauthenticated requests to every protected route redirect to login;
- authenticated USER can read permitted pages;
- authenticated USER receives 403 for every mutation/admin route, including
  crafted POST requests;
- ADMIN can access authorized mutation pages;
- missing, invalid, and valid CSRF behavior on login, logout, and representative
  existing POST routes;
- system-user list/detail/create/edit/activate/deactivate lifecycle;
- self-deactivation and self-demotion rejection;
- last-active-ADMIN deactivation/demotion rejection;
- inactive-account session invalidation;
- topbar identity/logout rendering and ADMIN-only System Users navigation;
- escaped system-user names/emails;
- EN/JA authentication, authorization, CSRF, role, and lifecycle messages;
- regression of authorized Phase 01–09 reads and writes.

Existing tests that instantiate `EmployeeController` or `HttpKernel` directly
must receive explicit authenticated test context or use a test middleware
fixture. Tests must not bypass security accidentally by asserting old routes
without an account.

## 16. Implementation acceptance criteria

Phase 10 is complete only when:

- no protected route can be reached without an active authenticated account;
- no USER can mutate business data through UI or crafted requests;
- every POST mutation rejects missing/invalid CSRF before controller writes;
- passwords are hashed, verified, and never exposed;
- login regenerates the session ID and logout invalidates the session;
- inactive users cannot authenticate or retain protected access;
- an active ADMIN always remains available through normal application actions;
- system-user management is ADMIN-only and preserves inactive history;
- EN/JA UI is complete for security-controlled text;
- migration, unit, database, HTTP, and regression tests pass in their
  configured environments;
- no production credential, default password, or secret is committed.

## 17. Locked decisions before implementation

The following decisions are final for Phase 10:

1. The authenticated landing page remains the existing `/` route.
2. Successful login always redirects with HTTP 303 to `/`.
3. An unauthenticated GET `/login` returns 200; an authenticated GET `/login`
   redirects with HTTP 303 to `/`.
4. An authenticated POST `/login` passes the required CSRF policy and then
   redirects with HTTP 303 to `/` without establishing a second login state or
   replacing the current identity.
5. The initial administrator is created only through the dedicated interactive
   CLI bootstrap path.
6. No plaintext or default administrator credential is committed.
7. No environment default password is introduced.
8. Production requires HTTPS. Direct HTTPS detection may enable the Secure
   session-cookie flag; reverse-proxy HTTPS detection requires an explicitly
   configured trusted boundary, and arbitrary client-supplied proxy headers are
   never trusted.
9. The security-error response boundary is centralized and used by
   authorization and CSRF middleware for safe localized 403 responses.
10. Last-active-ADMIN protection is enforced atomically by the PDO repository's
    transaction and locking protocol, not by a service count followed by a
    separate update.
11. Migration implementation must inspect the migration directory and use the
    next unused version immediately before coding; existing migrations remain
    unchanged.

No unresolved implementation-blocking design question remains. These
decisions must not be weakened during implementation.
