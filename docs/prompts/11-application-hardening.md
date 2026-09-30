We are starting Phase 11 of the Company Employee Management System.

Phase 11:
Application Hardening & Production Readiness

IMPORTANT:
This task is AUDIT + SPECIFICATION ONLY.

Do NOT implement Phase 11 yet.
Do NOT modify production application code.
Do NOT create migrations unless the audit proves one is required and the
specification explicitly proposes it.
Do NOT commit, merge, or push.

Current branch:
feature/application-hardening

==================================================
PROJECT CONTEXT
==================================================

This is a Pure PHP 8.x SSR internal business application.

Stack:

- Pure PHP 8.x
- PDO
- MySQL/MariaDB
- Composer / PSR-4
- Server-side rendered PHP views
- HTML/CSS/minimal JavaScript
- No framework
- No ORM
- No SPA

Architecture direction:

Browser
-> public/index.php
-> Bootstrap / Composition Root
-> Request
-> HttpKernel
-> Router
-> Middleware Pipeline
-> Controller
-> Application Service
-> Repository Interface
-> PDO Repository
-> MySQL
-> ViewRenderer / Response
-> ResponseEmitter
-> Browser

Existing major phases include:

01 Project Foundation
02 HTTP Architecture
03 Database Foundation
04 Domain Schema
05 Employee Management
06 Dispatch Management
07 Organization Management
08 Employee Advanced Search
09 Employee Portfolio
10 System Users + Security

Phase 10 added:

- system users
- login/logout
- PHP session authentication
- ADMIN / USER roles
- centralized authorization
- CSRF protection
- password hashing
- secure session handling
- System User management
- deactivate/reactivate lifecycle
- initial ADMIN CLI
- EN/JA security UI
- security-related tests

==================================================
PHASE 11 GOAL
==================================================

Audit the entire current application and design a professional hardening phase
that makes the application safer and closer to production/deployment readiness.

This phase should focus on strengthening existing architecture rather than
adding unrelated business features.

Audit the REAL repository first. Do not design from assumptions.

==================================================

1. # ERROR HANDLING AUDIT

Inspect:

- HttpKernel
- ExceptionResponder or equivalent
- SecurityErrorResponder
- Response / ResponseEmitter
- controllers
- repositories
- bootstrap
- error views
- validation handling

Determine current behavior for:

- 400 if applicable
- 403
- 404
- 405
- 422
- 500

Audit whether production responses can expose:

- exception messages
- stack traces
- SQL
- filesystem paths
- configuration values
- credentials
- session identifiers
- CSRF tokens
- internal implementation details

Define a consistent production-safe error policy.

Consider:

Development/local:

- useful diagnostic information where appropriate

Production:

- generic safe user-facing error
- no stack trace
- no SQL/internal paths
- detailed information only in server-side logs

Preserve EN/JA behavior where user-facing messages exist.

# ================================================== 2. LOGGING AUDIT

Determine whether a real application logging abstraction currently exists.

Inspect for:

- error_log()
- direct file logging
- exception logging
- security logging
- database errors
- authentication events
- application events

Design a small framework-independent logging architecture if needed.

Potential concepts:

- LoggerInterface
- FileLogger or equivalent
- centralized exception logging
- structured/context logging where useful

Define what SHOULD be logged, for example:

- unexpected application exceptions
- production 500 errors
- authentication failures where appropriate
- authorization failures where appropriate
- security-sensitive lifecycle events if justified
- migration/CLI failures where useful

Define what MUST NEVER be logged:

- plaintext passwords
- password hashes unless absolutely necessary (prefer never)
- CSRF tokens
- session IDs
- cookies
- Authorization secrets
- database passwords
- full sensitive request bodies
- secrets from environment/configuration

Include log rotation/deployment considerations without overengineering the app.

# ================================================== 3. ENVIRONMENT / CONFIGURATION HARDENING

Audit:

- EnvironmentLoader
- config files
- .env.example
- .gitignore
- ApplicationBootstrap
- DB configuration
- session configuration
- production detection
- HTTPS detection
- trusted proxy handling if any

Define supported environments clearly, such as:

- local
- test
- production

Determine what configuration must fail fast.

Examples:

- missing DB settings
- unsafe production session settings
- invalid APP_ENV
- production HTTPS requirements
- invalid secret/config values

Do not introduce unnecessary configuration complexity.

# ================================================== 4. SECURITY AUDIT

Perform a repository-wide security review.

Audit at minimum:

SQL Injection

- all SQL uses prepared PDO statements
- dynamic ORDER BY / identifiers use allowlists
- no unsafe interpolation

XSS

- dynamic HTML output escaped
- trusted rendered HTML boundaries explicit
- no unsafe user content rendering

CSRF

- every state-changing HTML POST is protected
- login/logout included
- lifecycle actions included
- no state mutation via GET

Authentication

- password_hash / password_verify
- generic login failures
- inactive user handling
- session regeneration
- logout invalidation
- reactivated account behavior

Authorization

- route access metadata coverage
- USER read-only policy
- ADMIN mutation policy
- direct URL protection
- server-side enforcement independent of UI

Session security

- strict mode
- cookies only
- HttpOnly
- SameSite
- Secure production behavior
- session fixation protection

Redirect security

- no arbitrary open redirects
- safe fixed destinations

Input validation

- bounded strings
- positive integer IDs
- enum/allowlist validation
- pagination bounds
- date validation

Mass assignment / DTO boundaries

- explicit accepted fields
- lifecycle/status fields not blindly user-controlled

Ownership / IDOR

- employee portfolio child ownership
- nested resources
- system-user access

Secrets

- .env ignored
- no credentials/default passwords committed
- no sensitive data in documentation/tests

File handling

- if upload/file functionality exists, audit it
- if none exists, explicitly state not applicable

# ================================================== 5. DATABASE HARDENING AUDIT

Inspect all migrations and repositories.

Audit:

- foreign keys
- ON DELETE behavior
- unique constraints
- CHECK constraints
- indexes
- transaction boundaries
- concurrency-sensitive operations
- employee code generation
- dispatch contract history
- portfolio race handling
- system-user last-active-ADMIN protection
- system-user activation/deactivation
- UTC timestamp policy

Look for:

- missing indexes on real query paths
- N+1 patterns where relevant
- unbounded queries
- unsafe transaction boundaries
- SELECT then UPDATE race conditions
- MAX()+1 patterns
- inconsistent lifecycle persistence
- physical deletion where history should be retained

Do not propose speculative indexes without tying them to actual queries.

# ================================================== 6. HTTP HARDENING

Audit HTTP response headers and determine whether the application should add
a small centralized security-header policy.

Evaluate applicability of:

- Content-Type
- X-Content-Type-Options: nosniff
- Referrer-Policy
- Content-Security-Policy
- frame protection via CSP frame-ancestors or X-Frame-Options
- cache behavior for authenticated/private pages

Do not blindly add headers.

For CSP specifically:
inspect the actual HTML/CSS/JS first for:

- inline scripts
- inline styles
- external resources

Design a policy compatible with the real application.

Also audit:

- CRLF/header injection protection
- method handling
- 404 vs 405
- Allow header
- response emission boundaries

# ================================================== 7. PRODUCTION ERROR/DISPLAY SETTINGS

Audit current PHP runtime/error configuration assumptions.

Specify safe expectations for production:

- display_errors
- log_errors
- error_reporting
- PHP configuration vs application responsibility

Do not rely solely on application try/catch for fatal/runtime configuration
security.

# ================================================== 8. TEST HARDENING

Audit the existing test suite.

Current categories include:

- Unit
- Feature/HTTP
- Integration/Database

Determine missing high-value regression coverage.

Include tests for:

- production-safe 500 responses
- no sensitive exception leakage
- authorization boundaries
- CSRF coverage
- authentication lifecycle
- inactive/reactivated users
- session behavior
- security headers if introduced
- configuration validation
- logger redaction/sensitive-data rules if logging is introduced
- database transaction invariants
- last-active-ADMIN protection
- representative XSS escaping
- representative SQL-injection resistance where useful

Do not create meaningless tests just to increase test count.

Existing test behavior must not be weakened to make tests pass.

# ================================================== 9. PRODUCTION / DEPLOYMENT READINESS

Audit what another developer/operator needs to deploy the application.

Design documentation/checklists for:

- required PHP version
- required PHP extensions
- Composer install
- production Composer flags
- environment configuration
- DB creation
- migration execution
- initial ADMIN creation
- writable directories
- logging directory if introduced
- web server document root must be /public
- HTTPS requirement
- session configuration
- production error settings
- migration procedure
- backup recommendation
- rollback considerations
- health/smoke checks after deployment

Do not introduce Docker, CI/CD, Redis, queues, cloud infrastructure, or other
large infrastructure unless the existing repository clearly requires it.

# ================================================== 10. DOCUMENTATION AUDIT

Inspect existing README/docs.

Determine what should be added or improved for professional maintainability:

- project overview
- architecture
- directory structure
- installation
- environment setup
- database setup
- migrations
- development seeding
- initial ADMIN CLI
- running locally
- testing
- DB-backed testing
- authentication/authorization model
- security architecture
- localization
- deployment checklist

Phase 11 does not need to rewrite every document if Phase 12 is a better place
for final presentation documentation.

Clearly separate:

- Phase 11 hardening documentation
  from
- Phase 12 final README/release documentation.

# ================================================== 11. SCOPE CONTROL

Explicitly identify:

A. MUST implement in Phase 11
B. SHOULD implement if justified
C. DEFER to Phase 12
D. OUT OF SCOPE

Avoid overengineering.

This is an internal SSR employee-management application, not a distributed
internet-scale platform.

# ================================================== 12. REQUIRED SPECIFICATION

Create:

docs/specs/11-application-hardening.md

The specification must be based on the actual repository audit.

It should contain at minimum:

1. Purpose
2. Current-state audit
3. Problems/risk findings
4. Error-handling policy
5. Logging design
6. Environment/configuration hardening
7. Security hardening
8. Database hardening
9. HTTP/security-header policy
10. Production PHP/runtime expectations
11. Testing strategy
12. Deployment-readiness requirements
13. Documentation requirements
14. Architecture/component changes
15. Exact files/components expected to change
16. Acceptance criteria
17. Explicit non-goals
18. Open questions, if any

For each meaningful finding, distinguish:

- existing behavior that is already correct
- actual defect/risk
- recommended hardening
- optional improvement

Do not rewrite working architecture merely for stylistic preference.

# ================================================== 13. FINAL RESPONSE

After the audit/spec work, report:

- repository areas inspected
- important findings
- actual security defects found
- existing protections already correct
- proposed Phase 11 scope
- files expected to change during implementation
- whether a migration is needed
- open questions requiring a decision
- path to the completed specification

Again:

AUDIT + SPEC ONLY.
DO NOT IMPLEMENT PHASE 11.
DO NOT COMMIT.
DO NOT MERGE.
DO NOT PUSH.

# implement prompt

Implement Phase 11: Application Hardening and Production Readiness.

Current branch:
feature/application-hardening

Authoritative specification:
docs/specs/11-application-hardening.md

IMPORTANT:
Read the complete Phase 11 specification before changing code.

The specification is LOCKED.

Do not redesign the approved architecture.
Do not broaden the scope.
Do not add unrelated business features.
Do not create a database migration unless implementation discovers a genuine
blocking invariant that contradicts the approved audit; if that happens, STOP
and report it instead of creating the migration.

Do NOT commit.
Do NOT merge.
Do NOT push.

==================================================
IMPLEMENTATION GOAL
==================================================

Implement the approved Phase 11 specification against the real repository.

Preserve all existing business behavior unless the specification explicitly
requires a hardening change.

The implementation must remain:

- Pure PHP 8.x
- PDO
- MySQL/MariaDB
- Composer / PSR-4
- server-side rendered PHP
- framework-free
- ORM-free
- SPA-free

Keep the existing architectural responsibilities and dependency-injection
style.

==================================================

1. # PRODUCTION CONFIGURATION HARDENING

Implement the configuration rules from the specification.

Supported environments:

- local
- test
- production

Production must fail closed.

At minimum reject production startup when:

- APP_DEBUG=true
- APP_URL is absent or not HTTPS
- required production DB configuration is not explicitly supplied
- APP_ENV is invalid
- required session/security configuration cannot be applied

Do not allow production to silently inherit local database defaults.

Configuration errors may identify the setting name but must never expose the
secret value.

Preserve convenient local development behavior and existing explicit test
configuration behavior.

================================================== 2. DIRECT HTTPS POLICY
==================================================

Implement the locked Phase 11 trust model:

- direct trusted HTTPS only
- no X-Forwarded-Proto trust
- no Forwarded trust
- no trusted-proxy implementation

Production must fail closed when direct trusted HTTPS is not established.

The production session cookie must be Secure only under the approved trusted
HTTPS boundary.

Do not let client-controlled proxy headers influence this decision.

================================================== 3. LOGGING
==================================================

Implement the small logging boundary specified in Phase 11.

Expected concepts include:

- LoggerInterface
- ErrorLogLogger
- allowlist-first safe logging context
- defense-in-depth redaction where useful

Use PHP's configured error_log pipeline only.

Do NOT create:

- custom application log files
- log rotation
- monitoring infrastructure

Logging MUST be fail-safe:
a logger failure must never expose sensitive data or replace the safe HTTP
response.

Never log:

- plaintext passwords
- password hashes
- submitted login email/identifier
- CSRF tokens
- session IDs
- cookies
- Authorization values
- DB passwords
- DSNs containing credentials
- environment dumps
- complete request bodies
- arbitrary $\_SERVER / $\_ENV / session arrays

Call sites must construct small known-safe contexts.

================================================== 4. REQUEST / CORRELATION ID
==================================================

Generate one non-secret correlation ID per request.

Requirements:

- not derived from session ID
- not derived from CSRF token
- not derived from user ID
- not derived from email or other identity data
- safe for server-side logs

Use the same request ID consistently for unexpected exception and security
operational events during that request.

Browser display is optional.

Do not introduce a distributed tracing system.

================================================== 5. ERROR HANDLING
==================================================

Harden the existing centralized error boundary.

Production unexpected errors must:

- return generic safe HTML
- not reveal exception class/message
- not reveal SQL
- not reveal filesystem paths
- not reveal configuration values
- not reveal secrets
- not reveal request/session/CSRF data

Unexpected exceptions must be logged using safe allowlisted context.

Bootstrap failures must also be safely logged before the full application
container is available.

Logging failure must not prevent the generic safe response.

Preserve:

- 403
- 404
- 405
- 422

semantics.

Preserve the 405 Allow header.

Add 400 only if a real malformed-request boundary exists. Do not manufacture
a generic 400 architecture merely to satisfy the table in the specification.

Use the existing EN/JA localization boundary for user-facing error pages where
appropriate.

================================================== 6. RESPONSE / COOKIE COMPOSITION
==================================================

Fix the response abstraction so multiple Set-Cookie headers can coexist.

The design must correctly support cases such as:

- PHP/session-related cookie behavior
- locale cookie
- future additional response cookies

Do not collapse repeated Set-Cookie values into one invalid header.

Preserve CR/LF header-injection protection.

Update ResponseEmitter accordingly and add tests that exercise actual repeated
header behavior as far as the test environment allows.

================================================== 7. HTTP SECURITY POLICY
==================================================

Implement the approved centralized response security policy.

For applicable HTML responses:

Content-Type: text/html; charset=utf-8
X-Content-Type-Options: nosniff
Referrer-Policy: no-referrer
Content-Security-Policy:
default-src 'self';
base-uri 'self';
object-src 'none';
frame-ancestors 'none';
form-action 'self';
script-src 'self';
style-src 'self'
X-Frame-Options: DENY

Authenticated and login HTML responses must also use:

Cache-Control: no-store
Pragma: no-cache

Do not blindly attach HTML-only headers to unrelated response types.

Do not overwrite an explicit security-critical response value incorrectly.

Preserve existing Content-Type behavior.

================================================== 8. CSP / EMPLOYEE SEARCH JAVASCRIPT
==================================================

Remove the existing employee-search inline JavaScript from the PHP view.

Move it to exactly:

public/assets/js/employee-search.js

Load it as a same-origin external asset.

Production CSP must use:

script-src 'self'

Do NOT use:

- unsafe-inline
- CSP nonces
- external CDN JavaScript

Preserve current employee-search behavior.

Preserve the safe JSON encoding boundary used to transfer server-rendered
catalog/filter data to JavaScript.

================================================== 9. INPUT BOUNDS
==================================================

Add explicit server-side bounds for the audited unbounded inputs, including:

- employee search keyword
- department description

Inspect the real database/view/domain limits before choosing values.

Do not silently truncate.

Invalid values must use the existing validation/error architecture and
localized feedback where applicable.

Do not introduce arbitrary restrictive limits that conflict with existing
database semantics.

================================================== 10. DISPATCH CONTRACT CONCURRENCY FIX
==================================================

This is a MUST requirement.

The current overlap check followed by a separate write has a race condition.

Make the repository transaction authoritative.

For create/update:

BEGIN transaction

Acquire deterministic employee-row lock using SELECT ... FOR UPDATE

Verify employee state/eligibility as required

Check contract overlap while the employee lock is held

For update:
exclude the current contract from its own overlap check

Perform insert/update only if the locked check succeeds

COMMIT

On any failure:
ROLLBACK

Two concurrent overlapping writes for the same employee must not both commit.

The service may retain an early friendly validation check, but it must not be
the authoritative concurrency guarantee.

Preserve existing domain error behavior where practical.

Do not solve this with MAX()+1, table-wide locking, or an unrelated schema
change.

No migration is expected.

================================================== 11. SESSION HARDENING
==================================================

Preserve and verify:

- session.use_strict_mode
- session.use_only_cookies
- session.use_trans_sid disabled
- HttpOnly
- SameSite=Lax
- session ID regeneration on authentication
- session destruction/invalidation behavior

Production Secure cookies require direct trusted HTTPS.

Locale cookie behavior must not replace/drop another Set-Cookie value.

Do not trust proxy headers.

================================================== 12. SECURITY REGRESSION COVERAGE
==================================================

Preserve all Phase 10 security behavior.

Add focused regression coverage for:

- SQL prepared-value boundaries
- dynamic sort allowlisting
- representative hostile search/form input
- HTML escaping
- employee-search JSON context
- all registered POST routes and CSRF
- login/logout CSRF
- direct USER access to ADMIN routes
- inactive system user behavior
- reactivation behavior
- session invalidation
- fixed redirects / no open redirect
- portfolio child ownership
- last-active-ADMIN protection
- password hash preservation

Do not duplicate tests unnecessarily if strong equivalent coverage already
exists.

================================================== 13. DATABASE / TRANSACTION TESTING
==================================================

Extend DB-backed tests for the real invariants identified in the spec.

Especially verify:

- dispatch overlap cannot bypass the authoritative transaction
- employee code transaction invariants remain intact
- portfolio ownership/transaction behavior
- last-active-ADMIN protection
- system-user lifecycle/password preservation

Database tests must remain explicitly gated by:

APP*ENV=test
DB_TEST*\*

Never guess or fall back to a development database.

================================================== 14. PRODUCTION RUNTIME DOCUMENTATION
==================================================

Document the approved production expectations.

At minimum include:

- PHP >= 8.3
- PDO
- pdo_mysql
- mbstring
- Composer dependencies
- display_errors=Off
- display_startup_errors=Off
- log_errors=On
- PHP error_log configured operationally
- session security expectations
- HTTPS requirement
- /public as web-server document root

Do not introduce upload/fileinfo requirements because the application has no
upload feature.

================================================== 15. DEPLOYMENT CHECKLIST
==================================================

Create:

docs/deployment-checklist.md

Cover the items required by the locked specification, including:

- runtime prerequisites
- Composer production installation
- production environment configuration
- dedicated/least-privilege DB account
- /public document root
- HTTPS
- migrations
- initial ADMIN CLI
- PHP error logging
- permissions
- backup/restore considerations
- migration rollback caution
- smoke checks

Do not introduce Docker, CI/CD, Redis, queues, cloud infrastructure, or other
unapproved deployment architecture.

================================================== 16. README / ENV EXAMPLE
==================================================

Make only the minimal Phase 11 documentation changes approved by the spec.

Update .env.example as needed using placeholders only.

Never add real credentials or default passwords.

Do not perform the final polished README redesign; that belongs to Phase 12.

================================================== 17. TEST EXECUTION
==================================================

Run the normal test suite.

Then, if the configured explicit test database is available, run the existing
DB-backed suite using the project's established APP*ENV=test / DB_TEST*\*
mechanism.

Also run any relevant syntax/static checks already used by the repository.

Do not weaken or delete existing assertions merely to make the suite pass.

Fix regressions caused by Phase 11.

If a pre-existing unrelated failure is discovered, clearly separate it from
Phase 11 rather than hiding it.

================================================== 18. SCOPE RESTRICTIONS
==================================================

Do NOT add:

- framework
- ORM
- API
- SPA
- MFA
- registration
- password reset
- OAuth
- JWT
- API tokens
- rate limiting
- CAPTCHA
- upload subsystem
- Docker
- CI/CD
- Redis
- queues
- cloud infrastructure
- service mesh
- audit-log product UI
- speculative indexes
- unrelated refactors

Do not modify historical migrations.

Do not create a Phase 11 migration unless a genuine blocking contradiction is
found. If one is found, STOP and report it before changing schema.

================================================== 19. COMPLETION REVIEW
==================================================

Before reporting completion:

1. Re-read docs/specs/11-application-hardening.md.
2. Compare every MUST requirement and acceptance criterion with the actual
   implementation.
3. Inspect git diff for accidental unrelated changes.
4. Check for credentials/secrets.
5. Confirm no migration was added unexpectedly.
6. Confirm existing architectural boundaries were preserved.
7. Run the required tests.

Then report:

- implementation summary
- important security changes
- concurrency fix
- files added
- important files modified
- test commands and exact results
- DB-backed test result separately
- skipped tests and why
- any remaining deprecations/warnings
- any acceptance criterion not fully satisfied
- confirmation that no migration was added
- confirmation that no commit/merge/push was performed

Do NOT commit.
Do NOT merge.
Do NOT push.
