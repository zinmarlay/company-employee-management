We are starting Phase 10 – System Users + Security for the existing
Pure PHP Employee Management System.

IMPORTANT:
This step is SPECIFICATION AND CURRENT-STATE AUDIT ONLY.

Do NOT implement Phase 10 yet.
Do NOT create migrations yet.
Do NOT modify application behavior.
Do NOT commit, merge, or push.

First inspect the existing project carefully, including:

- architecture and bootstrap/composition root
- Request / Response / Router / Middleware pipeline
- existing session usage, if any
- existing cookies
- current routes
- controllers
- services
- repositories
- PDO/database conventions
- migrations
- validation conventions
- flash-message / redirect conventions
- localization EN/JA
- views/layout/sidebar/topbar
- existing employee/organization lifecycle rules
- test architecture
- environment/configuration
- current security-related code
- docs/specs from previous phases

Then create:

docs/specs/10-system-users-security.md

The spec must be detailed enough to implement Phase 10 without making
important security decisions during coding.

==================================================
PHASE 10 GOAL
==================================================

Add production-style application authentication and authorization for the
internal employee management system.

Phase 10 must include:

1. System users
2. Login
3. Logout
4. Session-based authentication
5. ADMIN / USER roles
6. Server-side authorization
7. CSRF protection
8. Password hashing
9. Session security
10. ADMIN-only system-user management
11. Protection of all existing application routes
12. EN / JA UI
13. Automated tests

Do NOT add:

- registration
- forgot-password flow
- email verification
- OAuth/social login
- API token authentication
- MFA
  unless an existing project requirement explicitly requires it.

==================================================
SYSTEM USER MODEL
==================================================

Design a dedicated system user/account model.

Do NOT reuse employees as authentication accounts.

The spec should evaluate and lock fields such as:

- id
- name
- email
- password_hash
- role
- status
- last_login_at
- created_at
- updated_at

Roles:

ADMIN
USER

Use stable persisted values appropriate for the existing project conventions.

Status should support at minimum:

active
inactive

Email must be unique.

Passwords must NEVER be stored as plaintext.

Specify reasonable field lengths and indexes.

Use the project's existing:

- BIGINT UNSIGNED conventions
- InnoDB
- utf8mb4_unicode_ci
- UTC DATETIME policy

No physical deletion of system users unless there is a very strong existing
project reason.

Prefer deactivation/history preservation.

==================================================
AUTHENTICATION
==================================================

Design:

GET /login
POST /login
POST /logout

Unauthenticated users must not access protected application pages.

Protected request:
unauthenticated
→ redirect to /login

Successful login:
→ regenerate session ID
→ store minimal authenticated identity in the session
→ redirect to a safe application page such as dashboard

Failed login:
→ generic error message
→ do not reveal whether email or password was wrong
→ preserve appropriate non-sensitive form input
→ never preserve password

Inactive users:
→ cannot authenticate

Logout:
→ POST only
→ CSRF protected
→ clear authentication/session state safely
→ redirect to login

Avoid open redirects.

If implementing a post-login intended destination, it must be restricted to a
safe local application path. Otherwise keep the design simple and redirect to
dashboard.

==================================================
PASSWORD SECURITY
==================================================

Use PHP's password API:

password_hash()
password_verify()

Prefer PASSWORD_DEFAULT unless the project has a justified stronger explicit
policy.

The spec must define:

- minimum password requirements
- maximum accepted password length to bound input
- password confirmation where appropriate
- hashing only in application/service layer or a dedicated password component
- never log passwords
- never return password hashes to views

Discuss password_needs_rehash() and whether Phase 10 should support transparent
rehashing on successful login.

Do not invent custom cryptography.

==================================================
SESSION SECURITY
==================================================

Inspect current session handling first.

Specify a centralized session/authentication design.

At minimum consider:

- HttpOnly session cookie
- SameSite=Lax
- Secure cookie when HTTPS is used
- session.use_strict_mode
- session ID regeneration after successful login
- session invalidation on logout
- protection against session fixation
- minimal data stored in session

Do not scatter raw $\_SESSION access throughout controllers/views if the existing
architecture allows a dedicated abstraction/service.

Define clearly which component owns:

- session startup
- authenticated user identity
- login state
- logout
- flash messages if applicable

==================================================
AUTHORIZATION
==================================================

Roles:

ADMIN:

- full read/write access to application business features
- manage system users

USER:

- read-only access to business data

This must be SERVER-SIDE authorization.

Hiding buttons is NOT sufficient.

Existing mutating operations must be protected, including current features such
as:

- Branch create/edit/update/deactivate
- Department create/edit/update/deactivate
- Employee create/edit/update/deactivate
- Dispatch company mutations
- Dispatch contract create/renew or other mutations
- Employee Skill create/edit/archive
- Employee Project create/edit/archive
- Employee Certification create/edit/archive
- System User management

USER must be able to access permitted read-only pages but must not be able to
perform mutation by manually entering URLs or submitting crafted POST requests.

The spec must define the response behavior for authenticated but unauthorized
requests.

Prefer consistency with the existing HTTP architecture.

Clearly distinguish:

Authentication:
"Who are you?"

Authorization:
"What are you allowed to do?"

==================================================
AUTHORIZATION ARCHITECTURE
==================================================

Inspect the existing Middleware pipeline.

Prefer centralized middleware/policy-style enforcement rather than repeating
role checks throughout every controller.

The spec should determine the cleanest design compatible with the existing
architecture, for example:

Request
→ Session/Auth Middleware
→ Authentication Middleware
→ Authorization Middleware
→ Controller

Do not blindly introduce framework-style abstractions that do not fit the
project.

Document:

- public routes
- authenticated routes
- ADMIN-only/mutation routes
- read-only USER routes

The route authorization strategy must be explicit and auditable.

==================================================
CSRF PROTECTION
==================================================

All state-changing HTML form requests must be CSRF protected.

This includes:

- login if appropriate for the chosen threat model
- logout
- create
- update
- activate
- deactivate
- archive
- renewal
- system-user mutations
- every existing POST mutation route

Design a centralized CSRF component.

Requirements:

- cryptographically secure token generation
- token stored server-side in session
- constant-time comparison with hash_equals()
- hidden form input
- reject missing/invalid token
- token must not be accepted from arbitrary GET parameters
- no state changes through GET

Decide and document:

- token lifecycle
- whether one session token is sufficient for this project
- response/status behavior for invalid token
- how views receive/render the token
- how all existing forms will be migrated

GET confirmation pages may display forms, but the actual mutation remains POST
and CSRF protected.

==================================================
SYSTEM USER MANAGEMENT
==================================================

ADMIN-only.

Design routes such as:

GET /system-users
GET /system-users/create
POST /system-users
GET /system-users/{id}
GET /system-users/{id}/edit
POST /system-users/{id}
GET /system-users/{id}/deactivate
POST /system-users/{id}/deactivate
GET /system-users/{id}/activate
POST /system-users/{id}/activate

Use naming consistent with the existing application.

Required behavior:

- list users
- view user
- create user
- edit appropriate profile fields
- change role
- deactivate user
- reactivate inactive user through an ADMIN-only confirmation and POST+CSRF flow
- no physical deletion
- inactive user remains readable
- inactive user cannot log in
- inactive user cannot be normally edited or deactivated again
- activation preserves the password hash, role, identity, and historical
  timestamps except updated_at

Important administrative safety rules must be specified.

At minimum evaluate and lock rules for:

- ADMIN deactivating own currently authenticated account
- removing own ADMIN role
- deactivating the last active ADMIN
- demoting the last active ADMIN

The system must not accidentally reach a state with no active ADMIN.

Prefer explicit business-rule protection.

==================================================
INITIAL ADMIN / DEVELOPMENT ACCESS
==================================================

The spec must solve initial access cleanly.

There must be a safe way to obtain the first ADMIN account in development/test
without:

- committing plaintext production credentials
- hard-coding a production password in source
- requiring public registration

Inspect the existing DevelopmentSeeder and environment conventions.

Design a development/test bootstrap strategy consistent with the project.

If a development admin is seeded, document:

- development-only behavior
- how password/hash is supplied/generated
- how tests control credentials
- how production creates the initial administrator safely

Do not put real secrets in the repository.

==================================================
SYSTEM USER VALIDATION
==================================================

Define validation rules for:

Name:

- required
- trimmed
- bounded length

Email:

- required
- normalized appropriately
- valid format
- bounded length
- unique

Role:

- ADMIN / USER only

Status:

- active / inactive only as permitted by lifecycle operations

Password:

- required on create
- confirmation required
- bounded minimum/maximum
- optional on ordinary edit unless changing password

Duplicate email:
→ validation/conflict response consistent with existing conventions.

==================================================
HTTP BEHAVIOR
==================================================

Specify expected behavior such as:

GET success
→ 200

Successful POST
→ 303 redirect

Validation failure
→ 422 with form input/errors

Unauthenticated protected request
→ redirect to login

Authenticated USER attempting ADMIN/mutation action
→ define consistent 403 behavior

Missing resource
→ 404

Invalid CSRF
→ define explicit 403 behavior

Inactive authenticated account discovered during request
→ define safe logout/re-authentication behavior

Do not expose sensitive internal information.

==================================================
VIEW / UI
==================================================

Maintain existing Material Design-inspired SSR UI.

Add:

- login page
- authenticated user display in shared layout/topbar if appropriate
- logout POST form
- System Users navigation visible to ADMIN only
- mutation buttons visible to ADMIN only
- USER sees read-only UI

However:
UI visibility must mirror server-side authorization, not replace it.

Inactive records should remain clearly identifiable.

Do not expose:

- password hashes
- CSRF token except where required in form hidden inputs
- sensitive session information

==================================================
LOCALIZATION
==================================================

All system-controlled UI must support existing EN/JA localization.

Add Translator keys for:

- login/logout
- email/password
- authentication errors
- authorization errors
- CSRF errors where user-facing
- system users
- roles
- account statuses
- create/edit/activate/deactivate messages
- validation messages

Do not translate user-entered names/emails.

==================================================
SECURITY BOUNDARIES
==================================================

The spec must explicitly address:

- SQL injection → prepared PDO statements
- XSS → existing output escaping policy
- CSRF
- session fixation
- password storage
- authentication enumeration
- authorization bypass
- open redirect avoidance
- inactive-account access
- mass assignment / field allowlisting
- session cookie settings
- secrets/environment configuration

Do not claim Phase 10 solves unrelated security concerns that are not actually
implemented.

==================================================
MIGRATION
==================================================

Plan one new forward-only migration for system users if appropriate.

Do not edit old migrations.

The migration should follow current project conventions.

Document:

- table
- columns
- indexes
- unique constraints
- CHECK constraints where consistent with current DB strategy
- timestamps
- charset/collation
- foreign-key decisions if any

==================================================
TEST STRATEGY
==================================================

The spec must include comprehensive automated tests.

Unit tests should cover appropriate components such as:

- user validation
- password policy
- CSRF token validation
- authorization decisions
- authentication-related pure logic

Database integration tests:

- system-user persistence
- unique email
- status/role constraints
- admin safety rules where persistence matters
- migration behavior

HTTP/integration tests:

- login page
- successful login
- failed login
- inactive user login rejection
- session regeneration behavior where testable
- logout
- unauthenticated redirect
- authenticated read access
- USER mutation denial
- ADMIN mutation access
- CSRF missing/invalid/valid
- all important existing POST routes protected
- system-user CRUD lifecycle, including activation and deactivation
- self/last-admin protections
- EN/JA rendering
- escaped output

Regression:
Existing Phase 01–09 behavior must continue to work for authorized users.

Tests must not rely on real credentials.

==================================================
ARCHITECTURE SECTION
==================================================

The spec must include a concrete request flow.

For example, after inspection and adaptation to the actual project:

Browser
↓
public/index.php
↓
ApplicationBootstrap
↓
Request
↓
Session initialization
↓
Authentication middleware
↓
Authorization middleware
↓
CSRF middleware for mutation requests
↓
Router / Controller
↓
Service
↓
Repository
↓
Response
↓
ResponseEmitter

Do not copy this blindly if the existing HttpKernel/Middleware order requires a
different accurate flow.

Clearly state the actual proposed order and why.

==================================================
DELIVERABLE
==================================================

Create only:

docs/specs/10-system-users-security.md

Do not implement Phase 10.

At the end report:

1. current security/auth state found;
2. proposed architecture;
3. database design;
4. authentication/session design;
5. authorization design;
6. CSRF design;
7. system-user lifecycle;
8. last-admin/self-protection rules;
9. initial-admin strategy;
10. routes;
11. tests planned;
12. open questions or decisions that require review before implementation.

Do not commit.
Do not merge.
Do not push.

# implement promt

Implement Phase 10 – System Users and Security.

The approved and LOCKED specification is:

docs/specs/10-system-users-security.md

Read that entire specification before changing code.

IMPORTANT:

- The specification is the source of truth.
- Do not weaken or reinterpret its security decisions.
- Preserve the existing Pure PHP / PDO / SSR architecture.
- Do not introduce a framework, ORM, SPA, REST API, JWT, OAuth, MFA,
  registration, password-reset flow, or unrelated refactoring.
- Do not edit old migrations.
- Do not commit, merge, or push.
- Do not run migrations against the development database automatically.
- Do not create or seed a default administrator.
- Do not put plaintext/default credentials in the repository.

==================================================

1. # INSPECT BEFORE EDITING

Before implementation, inspect the actual current code and confirm:

- migration directory and next unused migration version;
- Router / Route / RouteMatch APIs;
- HttpKernel middleware construction/order;
- Request immutable-context capabilities;
- Middleware interfaces;
- ApplicationBootstrap composition;
- Response / ExceptionResponder;
- ViewRenderer and shared layout;
- Translator and EN/JA files;
- existing notice/redirect conventions;
- every registered GET/POST route in routes/web.php;
- every existing POST form;
- repository/service/validator conventions;
- database integration-test setup;
- DevelopmentSeeder boundaries;
- CLI migration/seeder bootstrapping conventions.

Adapt implementation names to the real codebase while preserving the locked
design.

================================================== 2. SYSTEM USERS DATABASE
==================================================

Create the next forward-only migration for:

system_users

Implement the approved schema:

- id BIGINT UNSIGNED AUTO_INCREMENT
- name VARCHAR(120)
- email VARCHAR(254) UNIQUE
- password_hash VARCHAR(255)
- role VARCHAR(20), ADMIN / USER
- status VARCHAR(20), active / inactive
- last_login_at DATETIME NULL
- created_at DATETIME
- updated_at DATETIME

Use:

- InnoDB
- utf8mb4
- utf8mb4_unicode_ci
- appropriate CHECK constraints
- index for active ADMIN lookup
- project migration conventions

Do not modify prior migrations.

Do not automatically run the migration against the development DB.

================================================== 3. SYSTEM USER DOMAIN / APPLICATION LAYER
==================================================

Implement focused components consistent with existing architecture:

- safe system-user DTO/view model
- authentication-only record containing password hash
- SystemUserRepositoryInterface
- PdoSystemUserRepository
- SystemUserService
- system-user input DTO(s)
- validator/result types as appropriate

Validation:

Name:

- required
- trimmed
- max 120 Unicode chars

Email:

- required
- trim
- lowercase
- max 254
- FILTER_VALIDATE_EMAIL
- unique

Role:

- ADMIN / USER only

Password:

- create: required
- minimum 12 characters
- maximum 128 bytes
- confirmation required
- edit: optional; blank keeps current password
- replacement requires confirmation

Never expose password_hash to ordinary controllers/views.

Use password_hash(PASSWORD_DEFAULT).
Use password_verify().
Use password_needs_rehash() after successful authentication.

================================================== 4. SESSION ARCHITECTURE
==================================================

Implement the centralized session abstraction specified in the document.

No controller/view may directly access $\_SESSION.

Session configuration must include:

- dedicated session name
- session.use_strict_mode=1
- session.use_only_cookies=1
- session.use_trans_sid=0
- HttpOnly
- SameSite=Lax
- Secure=true only from trusted/direct HTTPS detection
- local HTTP development may use Secure=false

Do NOT blindly trust X-Forwarded-Proto or other arbitrary proxy headers.

Session should store only minimal security state such as:

auth.user_id
csrf.token

Implement:

- startup
- authenticated user id read/write
- ID regeneration after login
- auth clearing
- safe destruction/logout
- CSRF state

Keep global PHP session interaction centralized and testable where practical.

================================================== 5. AUTHENTICATION
==================================================

Implement:

GET /login
POST /login
POST /logout

Rules must exactly follow the spec.

Unauthenticated GET /login:
→ 200

Authenticated GET /login:
→ 303 /

Authenticated POST /login:
→ CSRF must be valid first
→ 303 /
→ existing identity unchanged

Successful login:

- normalize email
- verify password
- require active account
- regenerate session ID with old session deletion
- store only user id
- rotate CSRF token
- update last_login_at in UTC
- transparently rehash password when needed
- 303 /

Unknown email / wrong password / inactive account:

- same generic localized error
- HTTP 422
- preserve email only
- never preserve password

Use a dummy password verification path for unknown email to reduce account
enumeration differences.

Logout:

- POST only
- authenticated
- CSRF protected
- destroy authentication/session safely
- expire cookie
- 303 to login with allowlisted notice

No arbitrary return URL.

================================================== 6. REQUEST AUTH CONTEXT
==================================================

Extend the existing immutable request/route context minimally so downstream
middleware/controllers/views can receive:

- route access metadata
- safe authenticated identity

Do not put password hash, session id, or mutable raw session state into Request.

Use a safe AuthenticatedUser representation containing only appropriate fields
such as:

- id
- name
- email
- role

================================================== 7. ROUTE ACCESS METADATA
==================================================

Extend the existing route system with explicit trusted access metadata:

- public
- authenticated
- authenticated_read
- admin

Do this at route registration, not with a second duplicated URL-policy table.

Apply the exact route policy from the specification.

Important:

- only login routes are public
- logout is authenticated
- business read routes are authenticated_read
- all create/edit/confirmation/activation/mutation routes are admin
- all system-user routes are admin

GET mutation forms and archive/activate/deactivate confirmation pages are ADMIN-only.

Audit every existing route in routes/web.php so no Phase 01–09 mutation route
is accidentally left writable by USER.

================================================== 8. SECURITY MIDDLEWARE
==================================================

Implement the approved middleware order compatible with the current
HttpKernel:

Session
→ Locale
→ Authentication
→ Authorization
→ CSRF for POST
→ Controller

AuthenticationMiddleware must evaluate an existing session even on public
routes so authenticated /login requests can redirect safely.

Inactive/missing/malformed session account:

- clear session
- redirect to login safely
- no protected content

Role/status must be loaded from persistent storage on protected requests.
Do not trust a stale role stored in session.

================================================== 9. CENTRALIZED SECURITY ERROR RESPONDER
==================================================

Implement one centralized safe response component for security denials.

At minimum:

Authorization failure
→ localized generic HTTP 403

Missing/invalid CSRF
→ localized generic HTTP 403

AuthorizationMiddleware and CsrfMiddleware must delegate to it.

Controllers must not duplicate these security error pages.

Do not expose:

- SQL
- account existence
- token values
- session ids
- password details
- internal exception details

================================================== 10. CSRF
==================================================

Implement a centralized synchronizer-token design.

Token:
bin2hex(random_bytes(32))

Store server-side in session.

Validate with:
hash_equals()

Every POST route must require CSRF, including:

- login
- logout
- employees
- branches
- departments
- dispatch companies
- dispatch contracts
- skills
- projects
- certifications
- system users

Never accept CSRF from:

- GET query
- URL
- cookie fallback

Rotate after successful login.
Clear on logout.

Ensure every existing POST form receives:

<input type="hidden" name="csrf_token" ...>

Prefer a centralized view-data/helper/partial boundary rather than controllers
generating tokens.

Do not mutate state through GET.

================================================== 11. AUTHORIZATION
==================================================

ADMIN:

- business read/write
- system-user management

USER:

- business read-only

Server-side enforcement is mandatory.

USER crafted POST:
→ 403
→ no controller mutation
→ no DB write

USER must also be unable to access create/edit/activate/deactivate/archive/
renewal confirmation/form GET routes.

UI hiding mirrors authorization but is never the security boundary.

Existing employee/organization/portfolio lifecycle rules remain independently
enforced.

================================================== 12. LAST ACTIVE ADMIN SAFETY
==================================================

Implement the locked concurrency rule exactly.

Self rules:

- current ADMIN cannot deactivate own account
- current ADMIN cannot demote own account

Last active ADMIN:

- cannot deactivate
- cannot demote

CRITICAL:

Do NOT implement:

service count admins
→ decide
→ separate update

For active ADMIN demotion/deactivation, PdoSystemUserRepository must own the
atomic transaction and locking protocol.

Inside one transaction:

- lock relevant ADMIN state in a consistent order
- reload/recheck target
- recheck active ADMIN state/count
- enforce invariant
- perform mutation only while protected
- commit atomically

Concurrent requests must not both observe a safe state and leave zero active
ADMIN accounts.

Service owns orchestration/domain outcomes.
Repository owns SQL, locking and transaction mechanics.

Do not physically delete accounts.

================================================== 13. SYSTEM USER MANAGEMENT
==================================================

Implement ADMIN-only:

GET /system-users
GET /system-users/create
POST /system-users
GET /system-users/{id}
GET /system-users/{id}/edit
POST /system-users/{id}
GET /system-users/{id}/deactivate
POST /system-users/{id}/deactivate
GET /system-users/{id}/activate
POST /system-users/{id}/activate

Pages:

- list
- detail
- create
- edit
- activate confirmation
- deactivate confirmation

Inactive system user:

- remains readable
- cannot login
- cannot edit
- cannot deactivate again
- may be reactivated by an ADMIN only

No delete route.

Never display password hashes.

================================================== 14. INITIAL ADMIN CLI
==================================================

Implement the dedicated interactive CLI bootstrap path described in the spec,
for example:

php bin/system-user create-admin

Follow existing environment/bootstrap conventions.

Requirements:

- interactive name
- interactive email
- password input
- password confirmation
- do not accept plaintext password through normal command-line arguments
- hash immediately
- use normal validation/service/repository path
- no hard-coded credential
- no default password
- no automatic HTTP bootstrap
- no automatic DevelopmentSeeder admin

If secure hidden password input is practical with the project's supported CLI
environment, use it. Otherwise keep the implementation explicit and document
the terminal-visibility limitation rather than introducing insecure storage.

Do not execute the command to create a real account during this Codex task.

================================================== 15. UI
==================================================

Preserve the current Material Design-inspired SSR UI.

Add:

- login page
- authenticated name/role in topbar
- POST logout form with CSRF
- System Users sidebar item for ADMIN only
- ADMIN mutation buttons
- USER read-only presentation
- system-user pages
- inactive status presentation

Remove/replace any placeholder administrator identity currently in the shell.

All dynamic output must be escaped.

Password inputs:

- never prepopulate
- never preserve submitted password
- appropriate autocomplete attributes

================================================== 16. EN / JA
==================================================

Add all required Translator keys for:

- login/logout
- email/password/password confirmation
- generic login failure
- session notices
- forbidden
- CSRF failure
- System Users
- roles
- statuses
- create/edit/activate/deactivate
- validation
- duplicate email
- self protection
- last-admin protection
- last login
- never logged in

Do not translate user-entered names/emails.

================================================== 17. TESTS
==================================================

Add/update comprehensive tests according to the approved spec.

Include:

Unit:

- validation
- email normalization
- password policy
- password hashing/verification/rehash logic where isolated
- CSRF
- authorization/access metadata
- security domain outcomes

Database integration:

- migration
- system-user persistence
- unique email
- role/status constraints
- last_login_at
- password hash persistence
- active/inactive
- last-ADMIN transaction protection
- no physical delete behavior

HTTP/integration:

- EN/JA login
- login CSRF
- successful login
- failed login
- unknown/wrong/inactive generic behavior
- authenticated GET /login redirect
- authenticated POST /login behavior
- logout
- unauthenticated protected routes
- USER reads
- USER mutation denial
- ADMIN mutation access
- CSRF on representative existing mutations
- system-user lifecycle
- self-deactivation rejection
- self-demotion rejection
- last-admin rejection
- inactive-session invalidation
- navigation/topbar behavior
- escaped output

Regression:

- existing Phase 01–09 tests must remain valid for authorized behavior
- update tests that instantiate changed constructors or HttpKernel directly
- do not weaken tests simply to make the suite pass

Run the normal test suite.

If the configured test database is available, also run the project's existing
DB-backed test command.

Do NOT run migrations against the development database.

================================================== 18. IMPLEMENTATION QUALITY
==================================================

Maintain:

- declare(strict_types=1)
- existing namespace conventions
- PSR-4
- constructor injection
- explicit composition root
- thin controllers
- no SQL in controllers/services
- prepared PDO
- output escaping
- immutable request/context style where applicable
- focused classes
- existing exception/HTTP conventions
- no unrelated refactor

Do not create unnecessary framework-like abstractions.

================================================== 19. DOCUMENTATION
==================================================

Create/update:

docs/prompts/10-system-users-security.md

Record the implementation prompt/intent consistent with previous phases.

Do not replace the approved spec.

If setup documentation is necessary for the CLI first-admin command, add the
smallest appropriate documentation consistent with the current repository.

================================================== 20. FINAL VERIFICATION / REPORT
==================================================

Before finishing:

1. inspect git diff;
2. ensure no secret/plaintext credential is present;
3. ensure every POST route is CSRF protected;
4. ensure every route has explicit access policy;
5. ensure USER cannot access mutation GET/POST routes;
6. ensure no password hash reaches views;
7. ensure last-admin mutation is transaction-safe;
8. run relevant tests;
9. do not commit.

Report:

- files added/changed;
- migration version selected;
- architecture implemented;
- route-access policy summary;
- CSRF coverage;
- system-user lifecycle behavior;
- last-admin locking strategy;
- first-admin CLI behavior;
- tests run and exact results;
- anything not completed or any test/environment limitation.

Do not commit.
Do not merge.
Do not push.
