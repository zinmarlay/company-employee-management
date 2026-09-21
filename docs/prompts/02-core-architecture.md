You are preparing the specification for Phase 02 of the Company Employee Management System.

This task is SPECIFICATION ONLY.

Do not implement PHP application code.

Context

This is an advanced Pure PHP learning project intended to develop professional and senior-level PHP engineering skills.

Before writing the Phase 02 specification, read these files completely:

- docs/specs/00-project-overview.md
- docs/specs/01-project-setup.md
- README.md
- composer.json

Also inspect the existing Phase 01 implementation, especially:

- public/index.php
- src/Bootstrap/ApplicationBootstrap.php
- src/Bootstrap/Configuration.php
- config/app.php

The Phase 02 design must build naturally on the existing Phase 01 architecture rather than replacing it unnecessarily.

Output

Create only:

docs/specs/02-core-architecture.md

Do not modify any existing application files.

Do not implement the specification.

Do not create directories or PHP classes.

Do not run Composer commands.

Do not create, switch, merge, or delete Git branches.

Do not commit or push anything.

The official future implementation branch for this phase will be:

feature/core-architecture

Phase 02 purpose

Phase 02 establishes the HTTP and presentation architecture that future business features will use.

The intended high-level request flow is:

Browser
↓
public/index.php
↓
Application Bootstrap
↓
Request
↓
Router
↓
Middleware Pipeline
↓
Controller
↓
Response
↓
Browser

The architecture must remain Pure PHP and must teach the concepts normally hidden by frameworks.

The specification should prioritize:

- explicit responsibilities
- dependency direction
- testability
- type safety
- small focused classes
- understandable request flow
- minimal abstraction
- framework-independent PHP knowledge

Do not imitate Laravel or Symfony merely for familiarity.

Every abstraction must have a clear responsibility and benefit.

Required specification topics

1. Phase scope

Clearly define what Phase 02 implements and what it intentionally defers.

Phase 02 may establish:

- HTTP Request abstraction
- HTTP Response abstraction
- Router
- Route definitions
- HTTP method matching
- path matching
- route parameters if justified
- controller/action boundary
- middleware foundation
- middleware execution pipeline
- 404 handling
- 405 Method Not Allowed handling if appropriate
- exception/error handling boundary
- PHP view rendering foundation
- shared layout foundation if appropriate
- output escaping conventions
- dependency/composition updates required to connect these pieces
- tests for the HTTP architecture

Do not include business features.

2. Request abstraction

Specify the responsibility of the Request object.

Discuss which HTTP information it should expose, such as:

- method
- path
- query parameters
- form/body input
- headers where needed
- server/request metadata where justified
- route parameters after routing

Avoid turning Request into a global service container.

The specification must define how PHP superglobals such as:

- $\_GET
- $\_POST
- $\_SERVER

are isolated at the HTTP boundary instead of being accessed throughout controllers and application code.

Discuss input access versus validation.

Request must not become the validation framework.

3. Response abstraction

Define a Response object with clear ownership of:

- response body
- HTTP status code
- response headers

Explain how the response is eventually sent to the browser.

Avoid scattering direct:

header(...)
http_response_code(...)
echo ...

through controllers.

The specification should define where response emission belongs.

4. Router

Specify a small custom Router.

Define responsibilities such as:

- route registration
- HTTP method matching
- path matching
- route parameter extraction if included
- returning a matched route/handler
- distinguishing no route from wrong HTTP method where practical

The Router must not:

- contain business logic
- query databases
- instantiate feature services
- perform authorization
- render feature views directly

Define expected route declaration style conceptually, but do not lock the project into unnecessary framework-like syntax.

5. Route definitions

Define where application route definitions should live.

Route definitions must remain separate from:

- Router implementation
- controller implementation
- business logic

Future routes such as employees or authentication must be able to use the same routing foundation.

Do not define full business routes in Phase 02.

Only minimal demonstration/setup routes may be used for verification.

6. Controller boundary

Define what a Controller is responsible for.

A Controller should generally:

1. receive HTTP-facing input
2. call application/service behavior
3. translate the result into a Response

Controllers must not become the location for:

- SQL
- repository implementation
- large business rules
- transaction orchestration
- reusable domain logic

Since business services do not exist yet, Phase 02 should use only minimal demonstration behavior needed to prove the architecture.

7. Middleware

Specify a minimal middleware abstraction and pipeline.

Explain middleware responsibilities and execution order.

The foundation should support future concerns such as:

- authentication
- authorization
- CSRF
- request logging
- security headers

Do not implement those future concerns unless a tiny architecture-verification middleware is justified.

Middleware must support continuing to the next handler and short-circuiting with a Response.

Avoid designing an unnecessarily complex PSR-15 clone unless adopting a standard is explicitly justified.

8. Error and exception boundary

Define where uncaught exceptions from request processing should be converted into safe HTTP responses.

Development behavior may expose useful diagnostic information.

Production behavior must not expose:

- stack traces
- filesystem paths
- secrets
- internal implementation details

Distinguish expected HTTP failures such as:

- 404 Not Found
- 405 Method Not Allowed

from unexpected server failures such as:

- 500 Internal Server Error

Do not build a huge exception hierarchy without demonstrated need.

9. View rendering

Define a minimal server-rendered PHP view foundation.

The system is not an SPA.

Views should eventually receive prepared data and render HTML.

Views must not:

- execute SQL
- access repositories
- contain business decisions
- perform authorization decisions
- access arbitrary global application services

Define a safe escaping convention for HTML output.

A helper or rendering boundary may be introduced only if it has a clear responsibility.

10. Layout foundation

If Phase 02 introduces a shared layout, define only the structural foundation.

Do not implement the full Material Design interface yet.

The later UI/layout phase will own:

- navigation
- sidebar
- detailed Material styling
- responsive dashboard design
- feature-specific pages

Phase 02 may establish only what is required to prove that PHP views and shared layouts work correctly.

11. Output escaping and XSS foundation

Document the difference between:

- input validation
- output escaping

Define the default HTML escaping strategy.

Use context-appropriate escaping principles.

For normal HTML text/attribute output, the specification should consider PHP’s htmlspecialchars() with explicit flags and UTF-8.

Do not claim that one escaping function automatically handles JavaScript, CSS, URL, and every output context.

12. Dependency composition

Explain how Phase 01 ApplicationBootstrap should evolve to compose the Phase 02 HTTP components.

Keep dependency construction explicit.

Do not introduce:

- global service locator
- static dependency registry
- hidden singleton container
- full dependency-injection framework

If ApplicationBootstrap would become too responsible, specify a small refactoring based on actual responsibilities rather than speculative abstractions.

13. Proposed physical structure

Propose only directories/classes that Phase 02 genuinely needs.

Possible conceptual areas include:

src/
├── Bootstrap/
└── Http/
├── Request
├── Response
├── Routing
├── Middleware
└── Controllers
resources/
└── views/
routes/

However, do not mechanically copy this structure.

The specification must state the actual proposed structure and justify important boundaries.

Avoid empty directories.

14. Testing strategy

Phase 02 must establish meaningful automated tests for the HTTP foundation.

Specify tests for important behavior such as:

- request creation
- method/path normalization
- route matching
- route parameters if supported
- 404 behavior
- 405 behavior if supported
- middleware order
- middleware short-circuit behavior
- response status/body/headers
- view rendering and escaping where practical
- safe error behavior

Tests should focus on behavior rather than implementation details.

If PHPUnit is proposed, document why it is appropriate and how it will be added as a development dependency.

Do not add test tooling during this specification task.

15. Security boundaries

Document security expectations relevant to Phase 02.

Include at least:

- public web root remains public/
- superglobals isolated at boundary
- safe production errors
- output escaping
- no arbitrary file inclusion from user input
- no client-controlled view/template path
- response headers handled intentionally

Authentication, authorization, CSRF, database security, and uploads remain later concerns.

16. HTTP behavior

Define expected behavior for at least:

- successful request
- unknown route
- supported path with unsupported method
- unexpected application exception

Use correct HTTP status semantics.

Avoid redirects or JSON APIs unless Phase 02 genuinely requires them.

The primary application remains server-rendered HTML.

17. Phase 02 verification

Define manual and automated verification steps.

The specification should make it possible to verify the complete request flow from:

HTTP request
→ front controller
→ bootstrap/composition
→ router
→ middleware
→ controller
→ view/response
→ browser

without introducing database or authentication features.

18. Learning goals

Explicitly list the PHP/backend concepts this phase is intended to teach.

Include concepts such as:

- HTTP request/response lifecycle
- front controller
- routing
- dependency injection
- middleware
- separation of concerns
- controller responsibility
- server-side rendering
- output escaping
- HTTP status codes
- exception boundaries
- testable architecture

19. Definition of Done

Provide concrete acceptance criteria.

Phase 02 should not be considered complete merely because a page appears in the browser.

Definition of Done should require:

- architecture boundaries are implemented
- HTTP behavior is correct
- automated tests pass
- manual request flow works
- safe error behavior is verified
- no future business features are implemented
- documentation is updated
- Git-visible changes remain within scope

Explicitly deferred to later phases

Phase 02 must NOT implement:

- MySQL connection
- PDO connection factory
- migrations
- seeders
- repositories
- database transactions
- authentication
- login/logout
- sessions for authentication
- users or roles
- authorization
- CSRF
- Branch CRUD
- Department CRUD
- Employee CRUD
- Dispatch company CRUD
- Contracts
- Employee portfolio
- search/filter/sort/pagination
- dashboard business data
- file uploads
- complete Material Design UI
- API/SPA architecture

Database infrastructure belongs to Phase 03.

Important design principle

Do not overengineer.

For every proposed class or abstraction, the specification should make its responsibility understandable.

Prefer a small architecture that can evolve over a large speculative architecture designed for features that do not exist yet.

The project is intended to teach how professional PHP applications work internally, not to recreate an entire framework.

Final requirement

After creating:

docs/specs/02-core-architecture.md

stop.

Report only:

- the file created
- a short summary of major design decisions
- important decisions intentionally deferred

Do not implement Phase 02.
Do not modify application code.
Do not perform Git operations.
