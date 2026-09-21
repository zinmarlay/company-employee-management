You are a senior PHP software architect.

Read the master project specification first:

docs/specs/00-project-overview.md

Based on that master specification, create the detailed specification for Phase 01: Project Setup.

Create ONLY:

docs/specs/01-project-setup.md

Do NOT implement the application yet.

The purpose of this specification is to define the technical foundation that will later be implemented in the Git branch:

feature/project-setup

Project Context

This is a professional Pure PHP Company Employee Management System.

Technology direction:

- PHP 8.x
- Pure PHP
- MySQL
- PDO
- Composer
- PSR-4
- Server-side rendered PHP
- HTML5
- CSS
- JavaScript
- Material Design inspired UI
- Git / GitHub

Do not use:

- Laravel
- Symfony
- CodeIgniter
- React
- Vue
- ORM

Define the Project Setup

The specification should decide and document the initial project foundation.

1. PHP Version

Choose and document an appropriate minimum PHP version for this project.

Explain why that version is selected.

Document important PHP language features that may be used later, such as:

- strict types
- typed properties
- union types where useful
- enums where useful
- readonly where useful
- constructor property promotion
- attributes only where they provide clear value
- modern exception handling

Do not use modern features merely because they exist.

2. Composer

Define how Composer will be used.

Include:

- composer.json purpose
- PSR-4 autoloading
- application namespace
- development dependencies
- Composer scripts if useful

Propose an application namespace such as:

App\

Explain how:

composer install

and:

composer dump-autoload

fit into the development workflow.

Do NOT actually run Composer in this task.

3. Initial Directory Structure

Design the initial project directory structure.

Consider directories such as:

app/
Core/
Controllers/
Services/
Repositories/
Models/
DTO/
Middleware/
Validation/
Exceptions/

config/

database/

public/
index.php
assets/
css/
js/
images/

routes/

storage/
logs/
uploads/

tests/

views/
layouts/
components/

docs/
prompts/
specs/

Do not blindly copy Laravel.

For every important directory, explain:

- its responsibility
- what belongs there
- what should NOT belong there

Do not create unnecessary empty architecture directories if they are not needed during Project Setup.

Distinguish between:

1. directories created immediately
2. directories introduced later when their feature requires them

3. Public Web Root

Define:

public/

as the intended web-accessible directory.

Explain why application source code, configuration, logs, and other internal files should not be directly web-accessible.

Define the future role of:

public/index.php

as the Front Controller.

Do not implement routing yet unless minimal bootstrap behavior is required by the future implementation phase.

5. Front Controller Foundation

Define what the initial:

public/index.php

should be responsible for during project setup.

Keep it minimal.

Its future responsibilities may include:

- loading Composer autoload
- loading/bootstrap configuration
- starting the application request lifecycle

Do NOT put:

- SQL
- business logic
- HTML page implementations
- employee logic
- authentication logic

inside index.php.

6. Configuration Strategy

Define a safe configuration approach.

The project will eventually need:

- Application environment
- Debug mode
- Application URL
- Database host
- Database port
- Database name
- Database username
- Database password
- Session configuration
- Logging configuration

Secrets must not be committed to Git.

Define the purpose of something similar to:

.env
.env.example

But do not assume a specific environment library unless justified.

Explain what should and should not be committed.

7. Git Ignore

Define what the initial .gitignore should cover.

Consider:

- vendor/
- .env
- runtime logs
- uploaded files where appropriate
- IDE/editor files
- OS-generated files
- temporary files
- test/cache artifacts

Do not ignore files that should actually be version controlled.

8. Coding Standards

Define the coding conventions.

Use:

- PSR-12
- namespaces
- type declarations
- return types
- visibility
- declare(strict_types=1) where appropriate

Explain expectations for:

- class naming
- method naming
- constants
- files
- namespaces

9. Error Environment

Define the difference between:

Development

and:

Production

error behavior.

Development may expose useful debugging information.

Production must not expose:

- stack traces
- SQL errors
- credentials
- internal paths

Detailed centralized exception handling will be implemented in a later phase.

10. Logging Foundation

Define where application logs should eventually live.

Example:

storage/logs/

Explain why logs should not be publicly accessible.

Do not build the complete logging system in Project Setup.

11. Testing Foundation

Define the intended testing approach.

Consider PHPUnit as the test framework.

Explain:

- tests/ directory
- unit tests
- integration tests

Do not write feature tests for functionality that does not exist yet.

Specify whether the Project Setup implementation should install/configure PHPUnit now or defer it, and explain the decision.

12. Development Server

Document how developers should run the project locally.

For example, PHP’s built-in development server may use:

php -S localhost:8000 -t public

Clarify that the built-in server is for local development, not production deployment.

13. Database

MySQL + PDO are project requirements.

However, decide whether actual database connection implementation belongs in:

01-project-setup

or should be deferred to:

03-database-foundation

Avoid mixing future database architecture into this branch unnecessarily.

14. Security Foundation

Even though full security implementation happens later, Project Setup must establish safe defaults.

Consider:

- public web root
- secret management
- environment configuration
- output/error behavior
- Git exclusions

Do not implement authentication, authorization, or CSRF in this phase.

15. Project Setup Scope

Clearly define what feature/project-setup WILL implement.

Keep the branch small and reviewable.

Also define what it WILL NOT implement.

It must not accidentally implement future phases such as:

- Router
- Authentication
- Authorization
- Employee CRUD
- Branch CRUD
- Department CRUD
- Database repositories
- Contracts
- Dashboard

16. Expected Files

Provide a proposed list of files that the later implementation branch is expected to create or modify.

For example:

composer.json
.gitignore
.env.example
public/index.php
config/…
README.md

Only include files genuinely required for Project Setup.

17. Verification

Define how we will verify the future Project Setup implementation.

Examples:

- Composer autoload works
- PHP application entry point loads
- development server starts
- public/index.php can respond without fatal error
- secrets are ignored by Git
- PSR-4 class autoloading works
- coding standard expectations are satisfied

Provide specific commands where appropriate, but do not execute them.

18. Learning Goals

This is especially important.

Document what I should understand after completing this phase.

Include concepts such as:

- What Composer does
- What PSR-4 means
- What autoloading means
- Why namespaces are useful
- Why public/ should be the web root
- What a Front Controller is
- What strict_types does
- Why environment configuration is separated from source code
- Why vendor/ should not be committed
- Development vs production error handling

For each major concept, include a short explanation or review question.

19. Definition of Done

Define clear completion criteria for feature/project-setup.

The branch should not be considered complete simply because files exist.

Definition of Done should include:

- structure is correct
- Composer configuration is valid
- PSR-4 works
- public entry point works
- local development instructions work
- sensitive configuration is not tracked
- no future feature is accidentally implemented
- documentation is updated
- review checks pass

IMPORTANT

This task is SPECIFICATION ONLY.

Do NOT:

- create PHP application implementation
- create composer.json
- create .env
- create .gitignore
- install Composer packages
- create application directories
- create public/index.php
- create database code
- create routes
- create authentication
- create Git branches
- commit anything

ONLY create/update:

docs/specs/01-project-setup.md

Do not modify:

docs/specs/00-project-overview.md

When finished, report:

1. The file created
2. Key technical decisions proposed
3. What is included in Project Setup
4. What is intentionally deferred
5. Any decisions that need human review before implementation

Then stop.
