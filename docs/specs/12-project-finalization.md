# Phase 12 — Project Finalization & Release Documentation

You are working on the existing project:

Company Employee Management System

Technology:

- Pure PHP 8.x
- MySQL / MariaDB
- PDO
- Composer / PSR-4
- Server-side rendered PHP views
- HTML5 / CSS / minimal JavaScript
- PHPUnit
- No full-stack PHP framework
- No ORM
- No React/Vue SPA

This is the FINAL project phase.

The application through Phase 11 is already implemented and tested.
Do not redesign the application or add new business features.

==================================================

1. # OBJECTIVE

Finalize the project so that it is:

- professionally documented
- reproducible from a fresh clone
- understandable by another developer
- suitable as a GitHub portfolio project
- suitable for technical interviews in Japan
- ready for a controlled production deployment
- internally consistent with the actual implementation

This phase is primarily:

Documentation
Final audit
Cleanup
Verification
Release preparation

It is NOT a new feature-development phase.

================================================== 2. NON-NEGOTIABLE RULES
==================================================

Preserve all existing architecture and business rules.

Do NOT:

- introduce Laravel, Symfony, CodeIgniter, or another framework
- introduce an ORM
- introduce React/Vue or a SPA architecture
- redesign the application architecture
- change established authorization rules
- change employee lifecycle rules
- change organization lifecycle rules
- change dispatch contract business rules
- weaken Phase 10/11 security
- weaken tests
- remove security headers
- remove CSRF protection
- expose secrets
- commit .env
- add speculative infrastructure
- add unrelated features

Small defects discovered during the final audit may be fixed only when
the correction is clearly within existing requirements.

For every code defect fixed:

- add or update an appropriate regression test
- run the relevant test suite

If a discovered problem requires:

- architectural redesign
- schema redesign
- major business-rule changes
- large new functionality

DO NOT implement it silently.

Document it in the final report as a deferred issue.

================================================== 3. FINAL AUDIT
==================================================

Audit the repository before finalizing documentation.

Review at minimum:

- application architecture
- Composer configuration
- PSR-4 structure
- configuration handling
- environment variables
- database configuration
- migrations
- seeders
- authentication
- authorization
- session security
- CSRF
- SQL parameterization
- output escaping
- security headers
- CSP
- production error handling
- operational logging
- request/correlation ID
- employee management
- organization management
- dispatch contracts
- employee portfolios
- system users
- localization
- automated tests
- deployment documentation

Search for suspicious leftovers such as:

TODO
FIXME
var_dump
print_r
dd(
die(
exit(
debug code
temporary credentials
hard-coded passwords
hard-coded secrets
accidentally tracked .env files

Do not remove intentional exit/termination behavior merely because the
word "exit" exists. Review context before changing anything.

Also check that documentation does not claim functionality that the
application does not actually implement.

================================================== 4. README STRUCTURE — MANDATORY
==================================================

Create/finish TWO professional README documents.

README.md
English

README.ja.md
Japanese

Both files must link to each other near the top.

Example concept:

English | 日本語

The Japanese README must NOT be an awkward literal translation.

Use natural technical Japanese appropriate for:

- Japanese software engineers
- hiring managers
- technical interviewers
- GitHub portfolio review

Both README files must describe the same project accurately.

================================================== 5. README CONTENT
==================================================

Both README versions should contain appropriate sections covering:

1. Project title
2. Overview
3. Project purpose
4. Key features
5. Technology stack
6. Architecture
7. Request lifecycle
8. Directory structure
9. Database/domain overview
10. Authentication
11. Authorization
12. Security measures
13. Employee management
14. Employee search/filter/sort/pagination
15. Branch and department management
16. Dispatch company / contract management
17. Contract expiration handling
18. Employee portfolio
19. Skills
20. Projects
21. Certifications
22. System user management
23. Localization
24. Installation
25. Environment configuration
26. Database setup
27. Migrations
28. Development seeding
29. Running locally
30. Testing
31. Production considerations
32. Deployment documentation
33. Project design principles

Use concise professional documentation rather than excessive marketing
language.

Do not describe the project as using technologies it does not use.

================================================== 6. ARCHITECTURE DOCUMENTATION
==================================================

Document the actual request flow accurately.

Conceptually it should reflect the existing architecture such as:

Browser
↓
public/index.php
↓
ApplicationBootstrap
↓
Request
↓
HttpKernel
↓
Middleware Pipeline
↓
Router
↓
Controller
↓
Application Service
↓
Repository
↓
PDO / MySQL

and:

Controller
↓
ViewRenderer
↓
Response
↓
ResponseEmitter
↓
Browser

Do not force this exact diagram if the source code proves a different
detail.

Inspect the actual implementation and document reality.

Explain responsibilities such as:

- Front Controller
- Composition Root
- Dependency Injection
- Router
- Middleware
- Controller
- Service
- Repository
- DTO
- ViewRenderer
- Response
- ResponseEmitter

================================================== 7. SECURITY DOCUMENTATION
==================================================

Document implemented security controls accurately.

Cover relevant existing controls including:

- PDO prepared statements
- output escaping
- CSRF protection
- password hashing
- password verification
- secure sessions
- session ID regeneration
- role-based authorization
- backend authorization enforcement
- secure POST state changes
- safe redirects
- bounded input
- CSP
- security response headers
- production error handling
- operational logging
- request/correlation IDs
- production HTTPS requirement
- configuration secret protection
- dispatch concurrency protection

Do not claim absolute security.

Use wording such as:

"Security measures implemented by the application"

rather than:

"The application is completely secure."

================================================== 8. DATABASE / DOMAIN DOCUMENTATION
==================================================

Document the major domain areas based on the actual schema.

Include relationships at a useful high level.

Examples:

Company
Branch
Department
Employee
Dispatch Company
Dispatch Contract
Portfolio
Skill
Employee Skill
Project
Certification
System User

Document important lifecycle/history decisions where applicable.

Examples:

- employee deactivation instead of physical deletion
- immutable employee code
- dispatch contract history
- inactive organization records retained for historical references
- system-user activation/deactivation lifecycle

Do not invent columns or relationships.

Inspect migrations/source code when necessary.

================================================== 9. INSTALLATION DOCUMENTATION
==================================================

Ensure a new developer can understand the setup process.

Document commands based on the actual project.

Cover concepts such as:

git clone
composer install
.env creation
database creation
configuration
migration execution
development seeding
application startup
test execution

Known project commands should be verified rather than guessed.

Examples currently expected include:

php bin/migrate status
php bin/migrate migrate
php bin/seed
php -S localhost:8000 -t public
composer test

Confirm them against the repository.

Do not put real credentials into documentation.

================================================== 10. ENVIRONMENT DOCUMENTATION
==================================================

Review .env.example.

Ensure required variables are represented without secrets.

Clearly distinguish:

local development

test environment

production environment

Document Phase 11 production requirements accurately, including:

- production configuration must fail closed
- production DB configuration must be explicit
- HTTPS is required
- only direct trusted HTTPS is currently supported
- proxy HTTPS headers are NOT trusted
- trusted proxy support is deferred
- PHP error logging is expected to use the host-managed error_log
- display_errors should be disabled in production
- display_startup_errors should be disabled in production
- log_errors should be enabled

Do not introduce actual production credentials.

================================================== 11. TEST DOCUMENTATION
==================================================

Document both test modes.

Normal:

composer test

Database-backed example:

APP_ENV=test \
DB_TEST_HOST=127.0.0.1 \
DB_TEST_PORT=3306 \
DB_TEST_DATABASE=company_employee_management_test \
DB_TEST_USERNAME=root \
DB_TEST_PASSWORD= \
DB_TEST_CHARSET=utf8mb4 \
composer test

Explain why some DB-dependent tests may be skipped in the normal test
environment.

Do not hard-code historical test counts in the README unless there is a
strong reason, because counts will become stale.

================================================== 12. DEPLOYMENT DOCUMENTATION
==================================================

Review and finalize:

docs/deployment-checklist.md

Ensure it accurately covers:

- PHP/runtime requirements
- required PHP extensions
- Composer dependencies
- production environment variables
- DB preparation
- migrations
- document root
- HTTPS
- PHP error configuration
- session configuration
- filesystem permissions where relevant
- application smoke tests
- security-header verification
- rollback considerations
- backup considerations

Do not add infrastructure-specific instructions unless the repository
actually supports that infrastructure.

================================================== 13. JAPANESE DOCUMENTATION QUALITY
==================================================

README.ja.md must use natural Japanese terminology.

Prefer professional terms such as:

社員管理システム
概要
主な機能
使用技術
システム構成
ディレクトリ構成
データベース設計
認証
認可
セキュリティ対策
社員管理
組織管理
派遣契約管理
ポートフォリオ管理
システムユーザー管理
多言語対応
環境構築
マイグレーション
開発用データ
テスト
本番環境
デプロイ
設計方針

Avoid machine-translation-like Japanese.

The Japanese README should be suitable for discussing the project during
a Japanese technical interview.

================================================== 14. CODE CLEANUP
==================================================

Only perform conservative cleanup.

Allowed examples:

- remove confirmed dead debug statements
- correct misleading comments
- correct documentation inconsistencies
- fix small obvious defects
- add missing regression tests for those defects

Do NOT perform broad refactoring merely for style.

Do NOT rename major architecture components without necessity.

Do NOT reformat the entire repository.

================================================== 15. RELEASE READINESS CHECK
==================================================

Verify:

- no tracked .env containing secrets
- no credentials/passwords/tokens in documentation
- no accidental debug output
- no obvious temporary development files
- Composer configuration is valid
- autoload works
- migrations remain valid
- seed command remains valid
- application boots
- login still works
- localization still works
- major navigation still works
- authorization boundaries remain intact
- security headers remain intact
- employee search JavaScript still works
- tests pass

================================================== 16. REQUIRED TEST EXECUTION
==================================================

Run the normal suite:

composer test

Then run the DB-backed suite:

APP_ENV=test \
DB_TEST_HOST=127.0.0.1 \
DB_TEST_PORT=3306 \
DB_TEST_DATABASE=company_employee_management_test \
DB_TEST_USERNAME=root \
DB_TEST_PASSWORD= \
DB_TEST_CHARSET=utf8mb4 \
composer test

Also run:

git diff --check

Do not weaken, delete, or skip failing tests merely to obtain a green
suite.

If tests fail, diagnose the cause and fix only issues within Phase 12
scope.

================================================== 17. FINAL DELIVERABLES
==================================================

At minimum Phase 12 should leave:

README.md
README.ja.md
docs/deployment-checklist.md

plus any small documentation or regression-test changes genuinely
required by the audit.

Do not create unnecessary documentation files merely to increase the
number of deliverables.

================================================== 18. FINAL REPORT
==================================================

When finished, report:

1. Files created
2. Files modified
3. Audit findings
4. Small defects fixed, if any
5. Deferred issues, if any
6. Documentation completed
7. English README status
8. Japanese README status
9. Normal PHPUnit result
10. DB-backed PHPUnit result
11. git diff --check result
12. Manual verification still recommended
13. Whether any database migration was added
14. Any production/deployment caveats

Do NOT commit.
Do NOT merge.
Do NOT push.

Leave Git finalization to the developer.

================================================== 19. DEFINITION OF DONE
==================================================

Phase 12 is complete only when:

- English README accurately describes the project
- Japanese README accurately describes the project
- both README files cross-link correctly
- setup instructions are reproducible
- architecture documentation matches implementation
- security documentation matches implementation
- deployment checklist is accurate
- no secrets are exposed
- final audit is complete
- normal tests pass
- DB-backed tests pass
- git diff --check passes
- no unnecessary redesign or feature expansion occurred

Proceed with the Phase 12 audit and implementation now.
