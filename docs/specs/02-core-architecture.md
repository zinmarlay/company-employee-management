# Phase 02: Core HTTP and Presentation Architecture Specification

## 1. Document purpose

This document defines Phase 02 of the Company Employee Management System. It specifies the HTTP and server-rendered presentation foundation that later business features will use.

This is a specification only. It does not implement PHP application code, create directories, add Composer packages, or modify the existing Phase 01 implementation.

The official implementation branch for this phase is:

`feature/core-architecture`

Future specifications must use an explicitly assigned branch name and must not invent a separate branch naming convention.

## 2. Phase objective

Phase 02 must make the complete request lifecycle understandable and testable:

```text
Browser
  ↓
public/index.php
  ↓
ApplicationBootstrap
  ↓
Request
  ↓
Router
  ↓
Middleware pipeline
  ↓
Controller/action
  ↓
Response
  ↓
Browser
```

The phase should teach the concepts that a PHP framework normally hides while retaining a small, framework-independent design. Each class must have one clear responsibility, dependencies must be explicit, and behavior must be verifiable without a database or authenticated user.

## 3. Relationship to previous phases

Phase 02 builds on the Phase 01 implementation rather than replacing it:

- `public/index.php` remains the only public web entry point.
- Composer remains responsible for autoloading the `App\\` namespace from `src/`.
- `ApplicationBootstrap` remains the composition root and evolves to construct the HTTP application.
- `Configuration` remains the single boundary for environment-backed configuration.
- `config/app.php` remains non-secret configuration with safe defaults.
- The repository root must remain outside the web-server document root.

The current Phase 01 entry point emits a temporary plain-text setup response. Phase 02 replaces that temporary behavior with a request-to-response flow, but it must not add business functionality.

## 4. Phase scope

### 4.1 Included

Phase 02 may establish:

- An immutable HTTP request abstraction
- An HTTP response abstraction
- A single response-emission boundary for the PHP SAPI
- A small custom router
- Route registration and HTTP method matching
- Safe path matching and route parameters where justified
- A controller/action boundary
- A small middleware contract and execution pipeline
- Expected 404 and 405 handling
- A centralized request-processing exception boundary
- Minimal server-rendered PHP view rendering
- A minimal shared layout foundation
- Output escaping conventions for HTML
- The composition changes needed to connect these pieces to Phase 01
- Automated tests for the HTTP and presentation foundation

### 4.2 Explicitly deferred

Phase 02 must not implement:

- MySQL or PDO connections
- Database configuration activation, migrations, seeders, repositories, or transactions
- Authentication, login, logout, authenticated sessions, users, or roles
- Authorization or policy decisions
- CSRF protection
- Branch, department, employee, dispatch-company, contract, portfolio, skill, project, certification, or dashboard features
- Business services, domain rules, or feature DTOs
- Employee search, filtering, sorting, or pagination
- File uploads
- A complete Material Design interface
- Navigation, sidebar, dashboard styling, or feature-specific pages
- API endpoints, JSON responses, or SPA architecture
- A full dependency-injection framework, service container, event system, or command bus

Only minimal setup behavior may be exposed through production route definitions. Test-only routes or synthetic handlers may be used to verify the HTTP architecture.

## 5. Design principles

The implementation must follow these principles:

1. Keep the request flow visible in the code.
2. Isolate PHP superglobals at the HTTP boundary.
3. Keep controllers thin and free from SQL and substantial business rules.
4. Keep HTTP objects independent from database and authentication concerns.
5. Keep response emission in one SAPI-facing boundary.
6. Keep views responsible for presentation, not data access or authorization.
7. Prefer concrete, small classes over speculative interfaces.
8. Use dependency injection where it makes construction and testing explicit.
9. Do not imitate Laravel, Symfony, PSR-15, or another framework without a specific learning or design reason.
10. Make invalid or unsupported HTTP behavior visible through correct status codes and tests.

## 6. Request abstraction

### 6.1 Responsibility

`Request` represents one immutable HTTP request as a safe application value. It is created once at the HTTP boundary and passed explicitly to the router, middleware, and controller.

The request object may expose:

- HTTP method
- Normalized path
- Query parameters
- Form/body input needed by the server-rendered application
- Selected request headers
- Minimal server metadata where a later concern genuinely needs it
- Route parameters added after a successful route match

It must not become a service locator, configuration object, database gateway, session manager, or validation framework.

### 6.2 Superglobal isolation

Only the request factory at the HTTP boundary may read PHP superglobals such as `$_GET`, `$_POST`, and `$_SERVER`. Controllers, services, views, and domain objects must not read them directly.

The implementation should provide a production factory such as `Request::fromGlobals()` and a test-friendly constructor or factory that accepts arrays. The test-friendly path must allow request behavior to be tested without mutating process-wide superglobals.

The request factory must snapshot input rather than retaining references to mutable global arrays. It must not expose arbitrary server variables by default; every exposed value needs a clear HTTP responsibility.

### 6.3 Normalization rules

The Phase 02 request contract should define the following behavior:

- HTTP methods are normalized to uppercase.
- The path is extracted from the request target without its query string.
- The path always begins with `/`.
- The root path is `/`; a trailing slash on a non-root path is normalized consistently before matching.
- Query parameters and form input remain structured arrays and are not silently cast into business types.
- Header lookup is case-insensitive from the caller's perspective.
- Missing query, body, or optional header values return an explicit empty or nullable result rather than generating notices.
- Route parameters are absent until routing succeeds and are then attached through an immutable operation such as `withRouteParameters()`.

URL decoding and path-segment rules must be implemented consistently with the router. A route parameter represents one path segment and must not silently consume `/` characters.

### 6.4 Input versus validation

`Request` only retrieves and normalizes transport input. It does not determine whether a name, email, date, employee number, or other business value is valid.

Validation belongs to a later application or domain boundary. Phase 02 may verify that request values are retrievable, but it must not introduce a general validation framework or business validation rules.

## 7. Response abstraction

### 7.1 Responsibility

`Response` represents the result of request processing and owns:

- Response body
- HTTP status code
- Response headers

It should be a simple, inspectable value that can be created and tested without an active web server.

The response contract must validate status-code ranges and preserve a predictable header representation. Header names may be normalized for comparison, but emission must retain a valid and unambiguous form.

### 7.2 Response creation

The implementation should provide clear constructors or named factories for the response types Phase 02 actually needs, such as:

- HTML response
- Plain-text error response where appropriate
- Empty response if a later HTTP behavior needs it

Redirect helpers and JSON helpers are not required in this phase. They should not be added speculatively.

### 7.3 Response emission

Controllers and middleware must return `Response` objects. They must not scatter calls to `header()`, `http_response_code()`, or `echo` throughout request-processing code.

A small SAPI-facing response emitter must be the only component responsible for:

1. Sending the status code
2. Sending response headers
3. Writing the response body

`public/index.php` may invoke that emitter, but it must not duplicate response-emission logic. Unit tests should inspect `Response` values directly; emitter behavior should be verified separately where practical.

The emitter must reject or safely handle header values containing carriage returns or line feeds to avoid response-header injection.

## 8. Router

### 8.1 Responsibility

The custom `Router` is responsible for:

- Registering routes
- Matching an HTTP method
- Matching a normalized path
- Extracting supported route parameters
- Returning a matched route and handler
- Distinguishing an unknown path from a known path with an unsupported method

The router must not:

- Query a database
- Instantiate feature services
- Perform authentication or authorization
- Apply business rules
- Render views directly
- Emit HTTP responses

### 8.2 Route matching

Phase 02 should support exact paths and simple named parameters because later employee and contract detail pages will need a stable foundation. A conceptual pattern such as `/employees/{employeeNumber}` may match one non-empty path segment and provide the value under the declared parameter name.

The initial matcher should not introduce a general regular-expression DSL, optional segments, host-based routing, route groups, subdomain routing, or automatic model binding. Those features are not justified before business routes exist.

Matching rules must include:

- Methods compared case-insensitively through normalization to uppercase
- Paths compared after the Request normalization rules are applied
- Route parameters extracted only from complete path segments
- Duplicate method-and-pattern registration rejected as a configuration error
- Registration order documented and deterministic when patterns could overlap

### 8.3 Match result

A successful match should provide:

- The registered handler
- The matched route pattern or route identity
- Extracted route parameters
- The allowed method information needed by later diagnostics if applicable

The match result must not contain a database record or business object. The application may create a new Request containing route parameters before invoking the handler.

### 8.4 404 and 405

- If no registered route matches the path, request processing produces `404 Not Found`.
- If a path exists but the method is unsupported, request processing produces `405 Method Not Allowed` and an `Allow` header listing the supported methods.
- The router or a small HTTP exception boundary may represent these expected failures; they must not be confused with unexpected server errors.

## 9. Route definitions

Route declarations must be separate from the Router implementation and from controller/business code.

Phase 02 should introduce a single route-definition file such as `routes/web.php` only if it contains a real route-registration responsibility. It may register a minimal setup route, for example `GET /`, using a controller supplied by the composition root.

The route file must not:

- Read request superglobals
- Open database connections
- Construct repositories or business services
- Contain authorization rules
- Render HTML
- Define full employee or authentication routes

The route declaration style should be readable and explicit. A small registration callback or route collection is sufficient; a framework-like annotation system or route compiler is not required.

Production Phase 02 routes should be limited to setup/architecture verification. Tests may register synthetic routes to exercise parameters, 404 behavior, and 405 behavior without adding those routes to the application.

## 10. Controller boundary

### 10.1 Responsibility

A controller is an HTTP adapter. It should:

1. Receive a `Request`.
2. Read the small set of HTTP-facing values required by the action.
3. Call application behavior when such behavior exists.
4. Translate the result into a `Response`.

Phase 02 has no business services, so its demonstration controller may only prepare setup-page data and delegate rendering to a view renderer. It must be concrete and focused; no abstract base controller is required.

### 10.2 Prohibited controller responsibilities

Controllers must not contain:

- SQL or repository implementation
- Database connection creation
- Large business rules
- Transaction orchestration
- Reusable domain logic
- Direct authentication or authorization logic
- Direct superglobal access
- Direct response emission
- Client-controlled template paths

Future feature controllers may depend on application services through constructor injection, but Phase 02 must not create empty service classes merely to demonstrate the pattern.

## 11. Middleware foundation

### 11.1 Contract

Phase 02 should define a small middleware contract with two focused responsibilities:

- A middleware receives a `Request` and a next request handler.
- It either returns a `Response` immediately or delegates to the next handler and may inspect or transform the returned response.

A small `RequestHandler` contract with one `handle(Request): Response` operation is acceptable. This gives the pipeline a typed terminal handler without adopting the complete PSR-15 package ecosystem.

The project should not add a PSR-15 dependency solely to obtain this two-method concept.

### 11.2 Pipeline behavior

The middleware pipeline must:

- Preserve the declared middleware order
- Execute middleware in a predictable nesting order
- Allow a middleware to short-circuit without invoking the next handler
- Pass the same logical Request onward unless an explicit immutable replacement is needed
- Return exactly one Response to the caller
- Remain independently unit-testable with fake handlers and responses

For middleware `[A, B]`, the observable flow should be `A before → B before → controller → B after → A after` when both middleware delegate and perform post-processing.

### 11.3 Phase 02 middleware scope

Phase 02 need not implement authentication, authorization, CSRF, sessions, request logging, or security-header policy. The pipeline itself and test-only or architecture-verification middleware are sufficient.

If a concrete middleware is added to prove the pipeline, it must have a small non-business purpose, such as adding a deterministic response header. It must not become a hidden place for future security policy.

## 12. Error and exception boundary

### 12.1 Ownership

Uncaught exceptions from routing, middleware, controllers, or view rendering must be converted to a `Response` at one centralized request-processing boundary, preferably the HTTP kernel or a small exception responder used by it.

The front controller should only coordinate bootstrap, request creation, kernel invocation, and response emission. It must not contain a growing exception taxonomy or repeated error-rendering branches.

### 12.2 Expected HTTP failures

The boundary must distinguish expected HTTP failures from unexpected failures:

- Unknown route: `404 Not Found`
- Known path with unsupported method: `405 Method Not Allowed`, including `Allow`
- Unexpected exception: `500 Internal Server Error`

Only a small number of exception types are justified at this stage. Do not create a large hierarchy for validation, authorization, database, or domain errors that do not yet exist.

### 12.3 Development and production behavior

- In local/development mode, the response may include a useful exception message and a clearly bounded diagnostic representation for debugging.
- In production mode, the response must not expose stack traces, filesystem paths, secrets, SQL, configuration values, or internal implementation details.
- Error responses must have an appropriate content type and must not accidentally append partial output from a failed view.
- Detailed exception information belongs in logging when logging is available; it must not be sent to the browser in production.

The full application-wide exception and logging system remains a later concern. Phase 02 only establishes the HTTP conversion boundary.

## 13. View rendering foundation

### 13.1 Responsibility

The view renderer is responsible for locating an approved server-rendered PHP template, supplying prepared presentation data, capturing its output, and returning an HTML string to the controller or response factory.

Views must not:

- Execute SQL
- Access repositories
- Read PHP superglobals
- Construct application services
- Make authorization decisions
- Perform substantial business decisions
- Select a template based on untrusted request input

The renderer must receive a trusted view identifier selected by application code. It must resolve that identifier beneath the configured views directory and reject path traversal or invalid template names.

### 13.2 Data and escaping

The renderer may expose a documented data convention to templates, such as named presentation variables or a controlled data array. It must avoid making the full application container or configuration object available to every view.

All dynamic text and attribute values must be escaped by default. The project should provide one small, documented HTML escaping helper or view boundary using:

`htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`

The helper must define how non-string scalar values are handled and must not silently treat arbitrary objects as safe HTML.

Escaping is context-specific. HTML text/attribute escaping does not automatically make a value safe for JavaScript, CSS, URL, SQL, or shell contexts. Those contexts are outside the Phase 02 view contract unless a later feature explicitly introduces them.

### 13.3 Output buffering and failures

If output buffering is used to capture a view, the renderer must clean up the buffer on success and failure. A failed view must not leave partial HTML in the response.

The renderer should produce strings rather than writing directly to the SAPI. This keeps controllers and tests independent from the web server.

## 14. Layout foundation

Phase 02 may introduce a minimal shared layout to prove that view composition works. The layout should provide only structural concerns such as:

- HTML5 document structure
- Character encoding declaration
- Page title slot
- Main content slot
- A minimal semantic status or navigation placeholder only if needed for the demonstration page

It must not implement the complete Material Design interface. Navigation, sidebar, responsive dashboard design, design tokens, detailed styling, and feature-specific pages belong to a later UI/layout phase.

The renderer may render a page template first and pass its resulting content into a trusted layout template. Layout selection must be application-controlled and must not come from a query parameter or form field.

## 15. Dependency composition

### 15.1 ApplicationBootstrap evolution

`ApplicationBootstrap` remains the composition root. It should evolve from returning only `Configuration` to constructing the Phase 02 HTTP application while continuing to use the existing configuration boundary.

The composition flow should be explicit:

1. Load `Configuration` using the existing `config/app.php` and environment rules.
2. Construct the view renderer with a configured, non-public views root.
3. Construct the router.
4. Construct the demonstration controller with its renderer dependency.
5. Register the minimal route definitions.
6. Construct the middleware pipeline or middleware factory.
7. Construct the exception responder/error boundary.
8. Construct a small HTTP kernel that coordinates matching, middleware, controller invocation, and response creation.
9. Return the kernel to `public/index.php`.

The exact method name may change from `boot()` if needed, but the responsibility must remain explicit and testable.

### 15.2 Front controller after Phase 02

The intended `public/index.php` flow is:

1. Require Composer's generated autoloader.
2. Ask `ApplicationBootstrap` for the configured HTTP application.
3. Create `Request::fromGlobals()`.
4. Pass the request to the HTTP kernel.
5. Pass the returned Response to the response emitter.

It must not contain route declarations, controller construction, view rendering, SQL, authentication, or business logic.

### 15.3 No container

Do not introduce a service container, static dependency registry, global service locator, hidden singleton, or framework-like application kernel. A small `HttpKernel` that coordinates a request is acceptable; it is not a general-purpose container.

If composition becomes too large, refactor based on a demonstrated responsibility, such as a route registrar or response emitter factory. Do not split the composition root into speculative factories merely to reduce its line count.

## 16. Proposed physical structure

This section describes the files and directories that the Phase 02 implementation may create. It is not permission to create them during this specification task.

```text
company-employee-management/
├── public/
│   └── index.php                         # Existing entry point, evolved
├── src/
│   ├── Bootstrap/
│   │   ├── ApplicationBootstrap.php      # Existing composition root, evolved
│   │   └── Configuration.php             # Existing configuration boundary
│   └── Http/
│       ├── Controllers/
│       │   └── SetupController.php
│       ├── Middleware/
│       │   ├── MiddlewareInterface.php
│       │   ├── RequestHandlerInterface.php
│       │   └── MiddlewarePipeline.php
│       ├── Routing/
│       │   ├── Router.php
│       │   ├── Route.php
│       │   └── RouteMatch.php
│       ├── View/
│       │   ├── ViewRenderer.php
│       │   └── HtmlEscaper.php
│       ├── Request.php
│       ├── Response.php
│       ├── ResponseEmitter.php
│       ├── HttpKernel.php
│       └── ExceptionResponder.php
├── resources/
│   └── views/
│       ├── layout.php
│       └── setup.php
├── routes/
│   └── web.php
└── tests/
    ├── Unit/Http/
    └── Feature/Http/
```

### 16.1 Structure decisions

- `src/Http/` is justified because Phase 02 now has real HTTP responsibilities.
- `Routing/`, `Middleware/`, `Controllers/`, and `View/` are created only if the corresponding Phase 02 files are implemented; they must not contain placeholders.
- `Request`, `Response`, `ResponseEmitter`, and `HttpKernel` belong directly under `Http/` because they coordinate the HTTP boundary rather than a single sub-concern.
- `resources/views/` is justified only when the setup route renders a real PHP view and layout.
- `routes/web.php` is justified only when route declarations are separated from Router implementation.
- `tests/` and its subdirectories are created only together with meaningful automated tests. PHPUnit test fixtures must not be used as an excuse to create empty architecture directories.
- No database, domain, application-service, repository, authentication, session, or business-feature directories are created in Phase 02.

The proposed class list is intentionally small. A separate base controller, generic service interface, generic repository interface, dependency container, or application-wide event abstraction is not part of this phase.

## 17. Testing strategy

### 17.1 Test tooling

PHPUnit is appropriate because Phase 02 needs repeatable unit and HTTP-level behavior tests, assertions for response values, and test doubles for handlers and middleware. It should be added only as a development dependency on the implementation branch, compatible with PHP >= 8.3, and committed through the normal Composer lock-file workflow.

This specification task must not add PHPUnit or run Composer commands.

### 17.2 Required behavior coverage

Tests should focus on observable behavior rather than private implementation details.

Request tests:

- Method normalization
- Path extraction and normalization
- Query and form input access
- Case-insensitive header lookup
- Isolation from caller mutation of input arrays
- Route-parameter attachment
- Absence of direct superglobal access outside the boundary factory

Response tests:

- Body, status code, and headers are preserved
- Invalid status codes are rejected
- Header values cannot contain unsafe line breaks
- HTML and plain-text response content types are explicit

Router tests:

- Exact path and method matching
- Method normalization
- Named route-parameter extraction
- Non-matching path produces 404 behavior
- Known path with unsupported method produces 405 behavior
- `Allow` contains the supported methods
- Duplicate route registration is rejected
- Query strings do not change path matching

Middleware tests:

- Declared execution order
- Before/after nesting order
- Delegation to the next handler
- Short-circuit behavior
- The pipeline returns the terminal or short-circuit Response unchanged unless a middleware intentionally transforms it

View and layout tests:

- A trusted view renders through the renderer
- Layout composition inserts page content correctly
- Dynamic HTML text and attributes are escaped
- A traversal or untrusted template identifier is rejected
- A rendering exception does not leak partial output

HTTP-flow tests:

- Setup route returns a successful HTML Response
- Unknown route returns 404
- Unsupported method returns 405 and `Allow`
- Unexpected controller or view exception returns 500
- Production error responses do not expose exception details
- Development error responses provide bounded diagnostic information

### 17.3 Manual verification

Manual verification should use the built-in PHP server with `public/` as the document root and should confirm:

- `GET /` enters the front controller and returns the setup page
- A browser displays the server-rendered HTML page
- An unknown path returns 404
- A known setup path requested with an unsupported method returns 405 where a safe client allows that request
- A controlled test failure produces a safe 500 response
- No repository-root, source, configuration, or view file is directly served

The server must be stopped after verification. No database or authentication setup is required.

## 18. Security boundaries

Phase 02 must establish these boundaries:

- `public/` remains the only public document root.
- PHP superglobals are read only by the Request factory.
- All normal HTML text and attribute output is escaped with an explicit UTF-8 HTML strategy.
- Production errors do not disclose stack traces, paths, secrets, SQL, or internal implementation details.
- View identifiers are selected by trusted application code; user input cannot choose an included file.
- No arbitrary file inclusion or path traversal is permitted in view rendering.
- Response headers are emitted centrally and header values are validated against injection.
- Controllers and views do not access arbitrary global services.
- No security decision is delegated to hidden UI controls or client-side JavaScript.

Authentication, authorization, CSRF, session cookie policy, database security, and upload security are later concerns and must not be simulated by Phase 02 placeholders.

## 19. HTTP behavior contract

Phase 02 must define and test at least these outcomes:

| Situation | Status | Response behavior |
| --- | ---: | --- |
| Registered setup route with supported method | 200 | Server-rendered HTML with an explicit content type |
| Unknown path | 404 | Safe not-found response |
| Known path with unsupported method | 405 | Safe response with an `Allow` header |
| Unexpected exception during processing | 500 | Safe server-error response; diagnostics only in development |

The primary application response is server-rendered HTML. JSON APIs, redirects, content negotiation, and streaming are deferred unless a later requirement explicitly introduces them.

## 20. Verification of the complete flow

The implementation should make the following path observable in tests and manual execution:

```text
HTTP request
  → public/index.php
  → ApplicationBootstrap
  → Request::fromGlobals()
  → Router match
  → MiddlewarePipeline
  → SetupController
  → ViewRenderer / layout
  → Response
  → ResponseEmitter
  → browser
```

No step in this flow may require a database, authentication, authorization, or business feature. The route and controller used for this demonstration exist only to prove the architecture and should be easy to remove or replace when the first real feature specification is implemented.

## 21. Learning goals

Phase 02 is intended to teach:

- The HTTP request/response lifecycle
- The front-controller pattern
- Isolation of PHP superglobals
- Custom routing and method semantics
- Route-parameter extraction
- Dependency injection and explicit composition
- Middleware and short-circuit control flow
- Controller responsibility
- Response ownership and HTTP status codes
- Server-side rendering with PHP views
- Shared layout composition
- Context-appropriate output escaping
- Exception boundaries and safe production errors
- Testable architecture without a framework
- Separation of concerns and dependency direction

## 22. Definition of Done

Phase 02 is complete only when:

1. The implementation is made on the official `feature/core-architecture` branch.
2. The Phase 01 front-controller and configuration boundaries are preserved and evolved deliberately.
3. Request and Response abstractions are typed, testable, and free from database or authentication concerns.
4. The Router supports the documented method/path behavior and separates 404 from 405.
5. Route declarations are separate from Router implementation and business logic.
6. The middleware pipeline demonstrates order, delegation, and short-circuit behavior.
7. Controllers return Responses and do not emit output directly or contain business logic.
8. A minimal server-rendered setup page and shared layout work through the view renderer.
9. HTML output escaping is documented and verified.
10. The response emitter is the only SAPI-facing response output boundary.
11. Expected HTTP failures and unexpected exceptions produce the documented status codes.
12. Production error behavior is safe and development diagnostics are bounded and useful.
13. PHPUnit tests cover the important HTTP, routing, middleware, response, rendering, and error behaviors.
14. Manual verification confirms the complete request flow through the browser or local HTTP client.
15. No database, authentication, authorization, CSRF, business feature, API, SPA, or complete UI code is present.
16. Documentation describes the architecture, verification steps, and intentionally deferred concerns.
17. Git-visible changes remain limited to Phase 02 specification-approved files and tests/tooling needed for the HTTP foundation.

The phase is not complete merely because a page appears in a browser; the boundaries and failure behavior must also be demonstrated by automated tests.

## 23. Non-negotiable rules

1. Do not add business routes or feature behavior in Phase 02.
2. Do not access `$_GET`, `$_POST`, or `$_SERVER` throughout application code.
3. Do not emit headers or body output from controllers, middleware, or views directly.
4. Do not include a template selected by client-controlled input.
5. Do not expose production stack traces or internal diagnostics.
6. Do not add a database, authentication, authorization, session, CSRF, or upload implementation.
7. Do not introduce a full framework, ORM, service container, or unnecessary PSR package.
8. Do not create empty future architecture directories or speculative abstractions.
9. Do not modify the master or Phase 01 specification as part of implementing Phase 02 unless a separately reviewed architectural correction is required.

