# Phase 11: Application Hardening and Production Readiness Specification

Status: audit-backed design specification. Phase 11 is not implemented by
this document.

This specification is based on the repository state on branch
`feature/application-hardening`. The audit covered the HTTP kernel and
middleware pipeline, routes and controllers, views, bootstrap and
configuration, session/authentication/CSRF code, repositories and migrations,
CLI entry points, documentation, and the Unit/Feature/Integration test suite.

## 1. Purpose

Phase 11 strengthens the existing Pure PHP, PDO, server-rendered application
for controlled production deployment. It adds hardening at the existing
boundaries; it does not introduce a framework, API, SPA, distributed runtime,
or unrelated business functionality.

The phase must provide:

- production-safe exception handling with server-side diagnostics;
- a small redacting application logger;
- fail-fast environment and production configuration validation;
- explicit direct-HTTPS and session-cookie policy;
- centralized security headers and private-page cache policy;
- closure of the identified input and concurrency gaps;
- high-value security and deployment regression coverage; and
- an operator-facing deployment checklist.

## 2. Audit scope and baseline

### 2.1 Request path

The actual composition root is `public/index.php` -> `EnvironmentLoader` ->
`ApplicationBootstrap` -> `HttpKernel`. The kernel matches routes, attaches
route access metadata to `Request`, then runs:

```text
SessionMiddleware
  -> LocaleMiddleware
  -> AuthenticationMiddleware
  -> AuthorizationMiddleware
  -> CsrfMiddleware
  -> controller
```

`Response` owns status/body/headers and validates CR/LF in header names and
values. `ResponseEmitter` sends headers through PHP's `header()` function.

### 2.2 Existing protections that are correct

The following behavior is already present and must be preserved:

- PDO uses `ERRMODE_EXCEPTION`, native prepares, and associative fetches.
- Repository values are bound as parameters. Employee search sorting maps a
  fixed allowlist to SQL expressions; direction is allowlisted.
- Route access is declared centrally in `routes/web.php`; business GET routes
  are authenticated, business writes are ADMIN-only, and logout is POST-only.
- Every POST, including login and logout, passes through CSRF middleware.
- Login uses `password_verify()`, a dummy hash for unknown/inactive accounts,
  generic failure text, session ID regeneration, CSRF rotation, and optional
  password rehashing.
- Session state is centralized in `SessionManager`; strict mode, cookie-only
  sessions, disabled URL session IDs, HttpOnly, and SameSite=Lax are configured.
- Authentication reloads the current user from the database on every request;
  inactive users lose the session and protected access.
- Portfolio repository writes lock the parent employee and child row in a
  transaction. Employee-code allocation locks its sequence row in a
  transaction. System-user role/status changes lock active ADMIN rows and
  protect self-deactivation and the last active ADMIN.
- User input is converted to explicit DTOs before persistence. Lifecycle and
  role/status fields are not accepted as general mutation input.
- Views consistently use `HtmlEscaper::escape()` for HTML/attribute output.
  The employee-search JSON uses `JSON_HEX_TAG`, `JSON_HEX_AMP`,
  `JSON_HEX_APOS`, and `JSON_HEX_QUOT` before being consumed as text by the
  browser.
- State-changing actions are POSTs; GET lifecycle routes render confirmation
  pages. Successful writes use 303 redirects and invalid forms use 422.
- Foreign keys, restrictive deletes, uniqueness constraints, status/date
  checks, UTC timestamps, and non-destructive lifecycle states are already
  used throughout the schema.
- The baseline suite passes: 131 tests, 533 assertions, and 16 skipped
  database-dependent tests in the current environment.

### 2.3 Findings and risk classification

| Finding | Classification | Phase 11 treatment |
| --- | --- | --- |
| `ExceptionResponder` includes exception class/message whenever `APP_DEBUG` is true | Actual leakage risk if production is misconfigured | Make production policy fail closed and centralize logging/response selection |
| `public/index.php` converts bootstrap failures to a generic 500 without logging | Operational defect | Log sanitized setup failures and retain a generic response |
| No application logger or centralized exception/security event logging exists | Missing control | Add a small logger abstraction and redaction boundary |
| `Configuration` defaults to `local` and debug true and does not validate production cross-field rules | Deployment safety defect | Require explicit safe production settings |
| HTTPS is detected only from direct `HTTPS`; production does not currently fail closed when HTTPS is absent | Session security gap | Require direct trusted HTTPS in production; do not add proxy-header trust |
| `Response` stores one value per header name while locale response composition also uses `Set-Cookie` | Cookie composition risk | Support repeated response headers or a cookie collection and test native emission |
| No centralized security headers or private-cache policy exists | Missing defense-in-depth | Add a response policy compatible with the actual HTML |
| Employee page contains an inline script | CSP constraint, not currently an XSS defect | Move it to the required same-origin asset `public/assets/js/employee-search.js` |
| Employee search keyword and department description lack explicit server-side bounds | Resource/input hardening gap | Add bounded validation and regression tests |
| Dispatch overlap is checked before `insert`/`update`, outside a transaction | Concurrency defect | Recheck and write while holding a deterministic employee-row lock |
| `mbstring` is used unconditionally by validators/layout but is described as merely planned | Deployment documentation/requirement defect | Require the extension or add a consistent fallback before deployment |
| Test coverage does not exercise the full authenticated HTTP lifecycle, emitted headers, logging redaction, or production configuration | Regression gap | Add focused Unit/Feature/Integration tests |

No evidence of SQL injection, stored-XSS output bypass, GET state mutation,
CSRF omission on registered POST routes, open redirect handling, plaintext
password storage, or missing child-resource ownership predicates was found in
the audited code. The implementation must retain tests for these conclusions.

## 3. Error-handling policy

### 3.1 Status mapping

The application keeps one exception/response boundary in `HttpKernel` and
`ExceptionResponder`:

| Status | Meaning and policy |
| --- | --- |
| 400 | Malformed request syntax or an unsupported request representation, only where the request boundary can identify it. Do not use 400 for ordinary form validation. |
| 403 | Authorization denial and CSRF denial through `SecurityErrorResponder`; no account, token, or implementation detail is exposed. |
| 404 | Unknown route or missing resource. Preserve the current not-found path. |
| 405 | Known path with an unsupported method; preserve a validated `Allow` header. |
| 422 | Well-formed form/query input that fails validation or a known domain conflict. Preserve non-sensitive values only. |
| 500 | Unexpected application, database, rendering, bootstrap, or runtime failure. |

There is currently no generic 400 response path. Phase 11 may add one only at
an actual request/body parsing boundary; controllers must not turn malformed
IDs or missing records into information-rich errors.

### 3.2 Production and development behavior

In `production`:

- 500 responses use a generic localized-safe message and contain no exception
  class, message, SQL, filesystem path, configuration value, credential,
  cookie, session identifier, CSRF token, or request body;
- 404/405/403 responses remain safe and do not reveal route internals;
- all unexpected throwables are logged through the logger with a correlation
  identifier and a sanitized context;
- bootstrap failures are logged before `ApplicationBootstrap` is available,
  then emitted as the existing generic setup-error response; and
- the response is still emitted if logging itself fails. Logging must never
  replace or expose the safe response.

In `local` and `test`, diagnostics may include a bounded escaped exception
class and message when explicitly enabled by `APP_DEBUG`. `APP_DEBUG=true`
must be rejected in production rather than silently treated as acceptable.

### 3.3 Localization

User-facing error titles and instructions must use the existing EN/JA
translator. Diagnostics are server-side data and are never translated or
rendered. The current hardcoded English 404/405/500 HTML should be moved to a
safe error-view/translation boundary without changing the status semantics.

## 4. Logging design

### 4.1 Components

Add a small framework-independent boundary:

- `App\Logging\LoggerInterface` with level methods sufficient for `debug`,
  `info`, `warning`, and `error`;
- `App\Logging\ErrorLogLogger` writing one structured line to PHP's configured
  `error_log` pipeline; and
- an allowlist-first context policy. This is a MUST: every call site constructs
  a small known-safe context. A redaction helper may provide defense in depth,
  but must not receive arbitrary request/server/environment/session data and
  attempt to make it safe afterward.

The logger must accept event names and scalar context. It must tolerate
encoding failures and must not throw during exception handling. Tests should
use an in-memory logger double.

### 4.2 Events to log

At minimum log:

- unexpected HTTP exceptions and production 500 responses;
- bootstrap/configuration failures;
- authentication failures as a countable event with only safe operational
  metadata; never log the submitted email/login identifier or password;
- authorization and CSRF denials with route, method, and outcome, without
  session or token values;
- successful security-sensitive lifecycle events such as system-user
  activation/deactivation and last-ADMIN protection failures where useful;
- migration, seed, and initial-admin CLI failures through the CLI boundary,
  without duplicating secrets in the message.

Each event should include only allowlisted context such as event name,
environment, method, normalized path, outcome/status, and a generated request
ID. Client IP is not required for Phase 11. Call sites MUST NOT pass arbitrary
request bodies, `$_SERVER`, `$_ENV`, cookies, sessions, exception dumps, or
large generic arrays to the logger.

Phase 11 MUST generate one non-secret, request-scoped correlation ID and use it
consistently in unexpected-exception and security operational logs. It must
not be derived from a session ID, CSRF token, user ID, or another secret.
Browser display is optional; server-side correlation is required.

### 4.3 Data that must never be logged

The logger and all call sites must never record plaintext passwords, password
hashes, CSRF tokens, PHP session IDs, cookies, Authorization headers, database
passwords, environment dumps, full sensitive request bodies, or raw exception
messages when they contain credentials or connection strings. SQL statements
may be identified by operation name, but bound values and DSNs are excluded.

### 4.4 Rotation and permissions

The application uses only PHP's configured `error_log` pipeline. It must not
create a custom application log file or rotation subsystem. The host/operator
owns log rotation, retention, permissions, access control, and monitoring.

## 5. Environment and configuration hardening

### 5.1 Supported environments

Only `local`, `test`, and `production` are valid. `APP_ENV` must be normalized
and validated at the configuration boundary.

- `local`: may default to local development values and may use debug output.
- `test`: must use explicitly supplied test configuration; database tests must
  continue to require `DB_TEST_*` and a test-designated database.
- `production`: must be explicitly selected by deployment and must fail fast
  when any production invariant is missing.

The current local defaults remain convenient for development, but production
must not be reachable through those defaults.

### 5.2 Production invariants

When `APP_ENV=production`, configuration must reject:

- `APP_DEBUG=true`;
- a missing or non-HTTPS `APP_URL`;
- missing explicitly supplied `DB_DATABASE`, `DB_USERNAME`, or `DB_PASSWORD`
  configuration; production must not inherit local development credential
  defaults;
- unsupported timezone, charset, or environment values;
- disabled/failed session security settings;
- an invalid session name; and
- any other setting required by the direct-HTTPS production policy.

An empty database password remains valid only when explicitly configured;
whether it is acceptable in production is an operator policy, not a default.
Configuration errors must identify the setting name, never its value.

### 5.3 HTTPS policy

The default trust boundary is direct server HTTPS. `Request` must not trust
arbitrary `X-Forwarded-Proto`, `Forwarded`, or similar client headers.

Phase 11 supports direct trusted HTTPS only. `Request` must use the trusted
server HTTPS signal and must never trust `X-Forwarded-Proto`, `Forwarded`, or
similar client-supplied headers. Production must fail closed when direct
trusted HTTPS has not been established. Trusted reverse-proxy support is
deferred until a real deployment requires it and is not implemented in Phase
11.

### 5.4 Environment files and secrets

`.env` remains ignored and `.env.example` remains placeholder-only. The
example must document production-safe values without containing credentials or
default passwords. Configuration errors and CLI failures must not print raw
DSNs or database passwords.

## 6. Security hardening

### 6.1 SQL injection and identifiers

Retain prepared statements for all dynamic values and native PDO prepares.
Retain the employee sort allowlist and audit any future dynamic identifier
against a fixed map. The private `exists()` column arguments are currently
internal constants; keep them private and allowlisted if their design changes.

Add representative injection regression tests for search filters, sort values,
IDs, and form values. Do not add speculative SQL rewrites where the audit
found correct prepared usage.

### 6.2 XSS and browser execution contexts

Retain HTML escaping at every dynamic text and attribute boundary, including
validation values, query strings, translated replacements, email links, and
user names/notes. Keep JSON data encoded for a script-data context rather than
reusing ordinary HTML escaping.

The current employee-search JSON encoding is correct and must be covered by a
regression test using hostile label characters. The static inline employee
search script MUST be moved to `public/assets/js/employee-search.js`. CSP
nonces are not part of Phase 11, and `unsafe-inline` is not acceptable.

### 6.3 CSRF, authentication, and authorization

Retain the current synchronizer-token model, POST-only mutations, generic
login failure, inactive-account invalidation, session regeneration, and
server-side route access metadata. Phase 11 must add coverage that enumerates
all registered POST routes and verifies CSRF rejection occurs before the
controller/service write.

The UI may hide ADMIN links from USER accounts, but only middleware and service
boundaries decide access. Direct URLs, forged form fields, role changes, and
lifecycle changes must remain protected. Login/logout redirects remain fixed
destinations; no return URL is accepted.

### 6.4 Session security

The existing strict-mode, cookie-only, HttpOnly, SameSite=Lax, and login
regeneration settings remain centralized in `SessionManager`. In production:

- the session cookie must be Secure and the application must fail closed if
  direct trusted HTTPS is not established;
- session settings must be applied before `session_start()` and failures to
  apply required settings must be observable and safe;
- locale preference cookies may remain non-sensitive but should receive Secure
  in HTTPS deployments; and
- all session destruction and authentication invalidation behavior must be
  tested using a real emitted-cookie boundary where possible.

The response abstraction must support both the PHP session cookie and the
locale cookie. A single `Set-Cookie` map entry is insufficient for multiple
cookies; implement repeated header values or an explicit cookie collection.

### 6.5 Input validation and DTO boundaries

Keep explicit DTO construction and lifecycle fields out of general form input.
Add bounded server-side validation for:

- employee search keywords;
- department descriptions, which currently have no explicit bound;
- any query/body value whose database or rendering cost is currently
  unbounded; and
- route IDs using the existing positive-integer policy.

Bounds must be documented and localized where they produce form errors. Do not
silently truncate user data.

### 6.6 Ownership and file handling

Nested portfolio repository methods already include both child ID and parent
employee ID, preventing the audited IDOR class. Preserve this predicate and
add a representative cross-employee test for each portfolio type.

No upload/file-handling feature or upload route exists in the repository.
Upload hardening is therefore not applicable to Phase 11; if uploads are
introduced later, they require a separate threat model and storage policy.

## 7. Database hardening

### 7.1 Existing schema protections

The audit confirms InnoDB, utf8mb4, restrictive foreign keys, unique keys,
status/date checks, child ownership keys, UTC application timestamps, and
non-destructive lifecycle persistence. No destructive schema correction is
justified by this audit.

### 7.2 Dispatch-contract concurrency fix (MUST)

The current service calls `hasOverlap()` and then performs `insert()` or
`update()` in separate operations. Two concurrent requests can both observe
no overlap and insert overlapping contracts.

Phase 11 MUST move the authoritative overlap check into the same repository
transaction as the write. The transaction must acquire a deterministic lock on the
employee row (`SELECT ... FOR UPDATE`) before checking overlap, then:

1. verify the employee still exists and is eligible;
2. check the requested period excluding the current contract for updates;
3. insert/update only after the locked check succeeds; and
4. commit or roll back as one unit.

The service may retain an early validation check for friendly errors, but the
repository transaction is authoritative. A concurrency integration test must
prove that overlapping writes cannot both succeed.

### 7.3 Transactions and query bounds

Retain existing transaction boundaries for employee-code allocation, portfolio
writes, seed operations, and system-user ADMIN protection. Do not introduce
indexes without tying them to an observed query plan or real query path.

Review the unbounded system-user/catalog/history reads. Phase 11 may add
small safe limits where a page is operationally expected, but it must not
change user-visible behavior merely for style. The current 200-item selector
limits and paginated employee search are acceptable starting points.

Migration DDL remains forward-only and operator-driven. MySQL/MariaDB DDL may
implicitly commit; documentation must not promise universal transactional DDL
rollback.

### 7.4 Migration decision

No Phase 11 database migration is currently required. The audit found no
missing database constraint that can be safely added without changing existing
data semantics. The dispatch overlap fix is an application
transaction/concurrency change.

## 8. HTTP and security-header policy

### 8.1 Central policy

Add one small response policy applied after route handling and before emission.
It must add headers only when appropriate and must not overwrite explicit
security-critical values supplied by a response.

For HTML responses, the default production policy is:

```text
Content-Type: text/html; charset=utf-8
X-Content-Type-Options: nosniff
Referrer-Policy: no-referrer
Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; script-src 'self'; style-src 'self'
X-Frame-Options: DENY
```

Both `X-Frame-Options: DENY` and CSP `frame-ancestors 'none'` are mandatory;
the application has no iframe embedding requirement. The exact policy must be
verified against the actual HTML: the application has one same-origin
stylesheet and, after the required externalization, one same-origin employee
search script, with no external image, iframe, object, or third-party resource.

Authenticated and login HTML responses must be private and non-cacheable:

```text
Cache-Control: no-store
Pragma: no-cache
```

Public static assets may use ordinary web-server cache rules. Redirects and
plain-text setup/error responses must receive only applicable headers.

### 8.2 Existing header protections to preserve

Keep `Response` CR/LF validation, method matching, 404 versus 405 behavior,
and the `Allow` header. Add tests for header injection through any new
configuration or request-derived value and for repeated `Set-Cookie` output.

## 9. Production PHP/runtime expectations

The deployment must configure PHP, not rely only on application try/catch:

- `display_errors=Off`;
- `display_startup_errors=Off`;
- `log_errors=On`;
- PHP's configured `error_log` destination with host/operator-managed
  rotation and restricted access;
- an appropriate production `error_reporting` level that still records
  actionable errors; and
- production `session.use_strict_mode=1`, `session.use_only_cookies=1`, and
  `session.use_trans_sid=0`.

The application may set security-critical session values before startup, but
deployment remains responsible for PHP runtime defaults, TLS, web-server
configuration, process permissions, and fatal-error visibility. A shutdown
handler may be added only to log otherwise-unhandled fatal errors and must
still emit a generic safe response when headers/body have not already begun.

The deployed PHP runtime must include PHP >= 8.3, PDO, `pdo_mysql`, `mbstring`,
and Composer's installed dependencies. `fileinfo` is not required by the
current application because no file upload exists.

## 10. Testing strategy

### 10.1 Unit tests

Add focused tests for:

- production configuration invariants and invalid environment combinations;
- direct HTTPS and Secure-cookie requirements;
- logger levels, structured context, redaction, and logger-failure tolerance;
- response security headers and repeated response-cookie values;
- bounded search/description validation;
- error response localization and absence of sensitive diagnostics;
- hostile HTML and JSON-context escaping; and
- existing router/header validation behavior.

### 10.2 Feature/HTTP tests

Add composed-kernel coverage for:

- production 500 responses with secret-like exception text absent;
- generic 400/403/404/405/422/500 status policy where each path exists;
- unauthenticated redirects and USER-versus-ADMIN boundaries on direct URLs;
- missing/invalid CSRF on login, logout, lifecycle, and representative CRUD;
- inactive and reactivated accounts, including existing-session invalidation;
- POST-only mutation behavior and safe fixed redirects;
- HTML security headers, private caching, CSP compatibility, and `nosniff`;
- locale behavior without dropping the authentication session cookie; and
- representative XSS input in HTML and employee-search JSON.

### 10.3 Integration/database tests

Extend the existing integration suite for:

- concurrent dispatch-contract overlap protection;
- employee-code sequence rollback/uniqueness under failed writes;
- portfolio parent/child ownership and transaction invariants;
- last-active-ADMIN protection under concurrent lifecycle operations;
- system-user activation/deactivation and password-hash preservation; and
- migration status/idempotency/rollback expectations.

Integration tests must continue to require explicit `APP_ENV=test` and
`DB_TEST_*` values and must skip/fail safely without guessing a database.

The suite must not weaken existing assertions or bypass middleware in tests
that claim to test composed HTTP behavior.

## 11. Deployment-readiness requirements

Add a concise deployment checklist, separate from the final Phase 12 README
rewrite, covering:

1. PHP version/extensions and Composer platform check.
2. `composer install --no-dev --classmap-authoritative` (or the verified
   production equivalent).
3. Creation of a dedicated production database and least-privilege database
   credentials.
4. Explicit `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, timezone,
   and production database values/credentials; production must not inherit
   local database defaults. Direct trusted HTTPS is required.
5. Web-server document root set to `/public`; source, `.env`, and runtime
   directories must not be web-accessible.
6. Migration status review and forward migration execution using
   `php bin/migrate migrate`; migrations must not run from web requests.
7. Initial ADMIN creation through `php bin/system-user create-admin` over a
   protected operational channel, with no default password.
8. Host/operator configuration of PHP's `error_log` pipeline, including log
   rotation, retention, permissions, and monitoring.
9. PHP production display/log settings, HTTPS, session-cookie behavior, and
   web-server request-size/time limits.
10. Backup and restore recommendation, migration rollback caution, and an
    operator-approved recovery procedure.
11. Post-deployment smoke checks for login, logout, CSRF rejection, protected
    reads, ADMIN-only writes, 404/405, safe 500, locale, and database access.

No Docker, CI/CD system, Redis, queue, cloud service, or distributed health
platform is required by this phase.

## 12. Documentation requirements

Phase 11 documentation should add or update only hardening and operations
material:

- `docs/deployment-checklist.md` (or an equivalent operations document);
- a short logging/error/session/security-header section in the README or an
  architecture/security document;
- explicit `mbstring` and `pdo_mysql` requirements;
- production environment examples with placeholders only; and
- the distinction between local/test/production configuration.

The final project overview, polished directory tour, and release-oriented
README restructuring are Phase 12 concerns. Phase 11 must not duplicate or
rewrite all historical phase specifications.

## 13. Architecture and component changes

The implementation is expected to add or adjust these boundaries:

- `Configuration`/`config/app.php` for cross-field environment validation;
- `Request` or a dedicated direct-HTTPS policy for the server trust boundary;
- `LoggerInterface`, `ErrorLogLogger`, and a redaction/context helper;
- request-scoped correlation-ID generation and propagation;
- `HttpKernel`, `ExceptionResponder`, `SecurityErrorResponder`, and
  `public/index.php` for centralized safe error/log behavior;
- `Response`/`ResponseEmitter` and `LocaleMiddleware` for response policies
  and multiple `Set-Cookie` values;
- `SessionManager` for fail-closed production Secure-cookie behavior;
- a small HTTP security-header/cache policy;
- `DispatchContractService`/`PdoDispatchContractRepository` for the locked
  overlap transaction;
- `EmployeeSearchCriteriaParser` and `DepartmentInputValidator` for explicit
  bounds;
- the employee-search view plus the required same-origin JS asset for CSP
  compatibility; and
- the existing Unit/Feature/Integration test files plus focused new tests.

## 14. Exact files/components expected to change during implementation

This is an implementation inventory, not a request to modify these files in
the audit/specification phase.

Likely existing files:

```text
.env.example
.gitignore
README.md                         (minimal hardening/deployment references)
config/app.php
public/index.php
routes/web.php                    (only if route metadata audit finds a gap)
src/Bootstrap/Configuration.php
src/Bootstrap/ApplicationBootstrap.php
src/Database/ConnectionFactory.php (only if sanitized failure boundary needs it)
src/Http/HttpKernel.php
src/Http/ExceptionResponder.php
src/Http/SecurityErrorResponder.php
src/Http/Request.php
src/Http/Response.php
src/Http/ResponseEmitter.php
src/Http/Middleware/LocaleMiddleware.php
src/Security/SessionManager.php
src/Application/Dispatch/DispatchContractService.php
src/Infrastructure/Persistence/PdoDispatchContractRepository.php
src/Application/Employee/EmployeeSearchCriteriaParser.php
src/Application/Validation/DepartmentInputValidator.php
resources/views/layout.php
resources/views/errors/security.php
resources/views/employees/index.php
```

Likely new files:

```text
src/Logging/LoggerInterface.php
src/Logging/ErrorLogLogger.php
src/Logging/LogContextRedactor.php
src/Http/SecurityHeadersPolicy.php   (or equivalent)
public/assets/js/employee-search.js
docs/deployment-checklist.md
```

Likely test additions/changes include `tests/Unit/Bootstrap`,
`tests/Unit/Http`, `tests/Unit/Security`, `tests/Unit/Logging`,
`tests/Feature/Http`, and the relevant database integration test files. The
exact test split may follow the existing naming conventions.

No `database/migrations/` file is expected for Phase 11.

## 15. Scope control

### A. Must implement in Phase 11

- fail-closed production configuration and HTTPS/session policy;
- safe centralized error responses and sanitized exception logging;
- response security headers, private cache behavior, and cookie composition;
- bounded search/description input;
- transactionally protected dispatch-contract overlap writes with the
  authoritative repository check and employee-row lock;
- externalization of the existing inline script to
  `public/assets/js/employee-search.js`;
- a mandatory non-secret request/correlation ID for operational logs;
- regression coverage for the above and existing Phase 10 boundaries; and
- deployment checklist and runtime dependency documentation.

### B. Should implement if justified by the final code change

- a shutdown handler for otherwise-unhandled fatal errors;
- explicit limits on currently small catalog/system-user reads;
- localized generic 400/500 error views if the new response boundary benefits
  from them.

### C. Defer to Phase 12

- final README/release-documentation redesign;
- product-level audit log browsing/reporting;
- broader observability dashboards and alerting;
- performance profiling and query-plan optimization not tied to a Phase 11
  finding; and
- deployment automation or infrastructure packaging.

### D. Out of scope

- MFA, password reset, email verification, registration, OAuth, JWT, API
  tokens, or bearer authentication;
- rate limiting, account lockout, CAPTCHA, malware scanning, or WAF policy;
- uploads and file storage, because no upload feature exists;
- Docker, CI/CD, Redis, queues, cloud infrastructure, or service meshes;
- changing business roles or adding business features; and
- editing existing migrations, creating speculative indexes, or physically
  deleting historical records.

## 16. Acceptance criteria

Phase 11 is complete only when:

1. Production configuration cannot boot with debug output, non-HTTPS URL, or
   unknown environment values.
2. Production 500/setup failures are generic to the browser and detailed only
   in sanitized server-side logs.
3. Logger tests prove passwords, hashes, tokens, sessions, cookies, secrets,
   and sensitive bodies are excluded.
4. 404/405/403/422 semantics remain correct, 405 retains `Allow`, and any
   400 path is explicit and safe.
5. All registered POST mutations, including login/logout, reject missing or
   invalid CSRF before application writes.
6. Session cookies are strict, cookie-only, HttpOnly, SameSite=Lax, Secure in
   production only after direct trusted HTTPS is established, and are not lost
   when locale and session cookies coexist.
7. Security headers and private cache policy are emitted consistently, and
   CSP works with the actual stylesheet/script resources, including both
   `frame-ancestors 'none'` and `X-Frame-Options: DENY`.
8. Hostile text remains escaped in HTML and JSON script-data contexts.
9. Search and free-text inputs have documented server-side bounds.
10. Concurrent overlapping dispatch contracts cannot both be committed.
11. A non-secret request/correlation ID is generated per request and is used
    in unexpected-exception and security operational logs without exposing
    secrets or user identifiers.
12. Authentication failure logs contain no submitted email/login identifier,
    password, request body, cookie, session ID, CSRF token, Authorization
    value, or environment secret.
13. Last-active-ADMIN, inactive-account, reactivation, portfolio ownership,
    employee-code, and transaction invariants remain covered.
14. The baseline suite remains green with database tests still safely gated.
15. Deployment documentation covers runtime dependencies, `/public`, direct HTTPS,
    configuration, migrations, initial ADMIN creation, logs, backups, and
    smoke checks.
16. No Phase 11 production code is implemented, committed, merged, or pushed
    as part of the audit/specification task itself.

## 17. Locked decisions

The following decisions are final for Phase 11:

- direct trusted HTTPS only; no proxy-header trust and no trusted-proxy
  support;
- PHP's configured `error_log` pipeline only; no custom application log file
  or rotation subsystem;
- both CSP `frame-ancestors 'none'` and `X-Frame-Options: DENY`;
- external `public/assets/js/employee-search.js` with `script-src 'self'`, no
  CSP nonces, and no `unsafe-inline`;
- authentication failure logs contain safe request metadata only and never the
  submitted email/login identifier;
- allowlist-first logging context is mandatory, with redaction only as defense
  in depth;
- a non-secret request/correlation ID is mandatory and server-side only;
- production database configuration and credentials must be explicit and must
  not inherit local defaults;
- dispatch overlap checking and insert/update must be one repository
  transaction with a deterministic employee-row lock; and
- no Phase 11 database migration is currently required.

There are no remaining open questions for the approved Phase 11 scope.

## 18. Final audit conclusion

The application already has a strong Phase 10 security foundation and should
not be redesigned. Phase 11 should concentrate on production boundaries:
configuration fail-closed behavior, safe error/log handling, HTTP headers and
cookie composition, bounded inputs, and one real concurrency defect in
dispatch contracts. No database migration is currently justified.
