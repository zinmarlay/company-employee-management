Implement a safe DEVELOPMENT-ONLY sample data seeder for the current Pure PHP employee management project.

IMPORTANT:

- Work only on the current branch: chore/development-seeder
- Do NOT commit, merge, push, switch branches, reset, restore, stash, or discard existing work.
- Do NOT modify existing production migrations just to insert sample data.
- Do NOT put sample data inside migration files.
- Preserve the existing architecture and coding conventions.
- Inspect the existing project before implementing.
- Do not introduce a framework or ORM.

==================================================

1. # PURPOSE

We need realistic development sample data so the completed Phase 05 Employee Management and Phase 06 Dispatch Management can be manually tested in the browser.

The normal local development environment currently uses:

APP_ENV=local
DB_DATABASE=company_employee_management

The isolated automated test database is:

company_employee_management_test

The seeder MUST NEVER seed the automated test database or a production/non-local environment.

# ================================================== 2. INSPECT FIRST

Before writing code, inspect:

- bin/migrate
- src/Bootstrap/EnvironmentLoader.php
- src/Bootstrap/Configuration.php
- src/Database/ConnectionFactory.php
- src/Database/DatabaseConfiguration.php
- all existing database migrations
- current Employee repository/service conventions
- current Dispatch repositories/services
- composer.json
- tests and project coding style

Reuse existing infrastructure where appropriate.

# ================================================== 3. CLI

Add a development seed command that can be run as:

php bin/seed

Use the same general bootstrap style as bin/migrate:

vendor/autoload.php
→ EnvironmentLoader
→ Configuration
→ ConnectionFactory
→ development seeder

The command should print a clear success summary.

On failure:

- write a useful message to STDERR
- return a non-zero exit code

# ================================================== 4. HARD SAFETY RULES

The seed command MUST refuse to run unless:

APP_ENV === 'local'

Do not silently fall back to another environment.

Also explicitly refuse if the configured database name:

- is empty
- ends with "\_test"
- appears to be a test database

Do not use DB*TEST*\* variables.

Do not implement destructive DROP TABLE behavior.

Do not truncate the database.

Do not delete existing user-created application data.

The seeder is for safe local development only.

# ================================================== 5. IMPLEMENTATION STRUCTURE

Prefer a small reusable seeding component rather than placing all SQL directly inside bin/seed.

For example, following the project's existing namespace/style:

src/Database/Seed/DevelopmentSeeder.php

The exact structure may be adjusted if the existing architecture suggests a better location.

Responsibilities:

bin/seed

- environment/bootstrap
- safety checks
- connection creation
- invoke seeder
- output result

DevelopmentSeeder

- transaction boundary
- prepared SQL
- seed orchestration
- deterministic sample data
- idempotent behavior

Do not add SQL to controllers/views.

Use PDO prepared statements for values.

Use a transaction so partial sample data is not left behind if seeding fails.

# ================================================== 6. IDEMPOTENCY

Running:

php bin/seed

multiple times MUST NOT create duplicate sample records.

Do not solve this by deleting all existing data.

Use stable sample identifiers such as:

company code
branch code
department code
employee_code
employee email
dispatch company code

Existing matching seed records may be reused or safely updated only when appropriate.

Do not overwrite unrelated user-created records.

If a stable seed identifier conflicts with incompatible existing data, fail clearly rather than corrupting data.

# ================================================== 7. SAMPLE ORGANIZATION DATA

Create or reuse one internal company:

Company:
code: SAMPLE-COMPANY
name: サンプル株式会社

Branches:

1.  code: TOKYO
    name: 東京支店
    city: 東京都
    address: 東京都千代田区丸の内1-1-1
    phone: 03-1234-5678
    status: active

2.  code: OSAKA
    name: 大阪支店
    city: 大阪府
    address: 大阪府大阪市北区梅田1-1-1
    phone: 06-1234-5678
    status: active

Departments:

Tokyo branch:

- DEV / 開発部
- SALES / 営業部

Osaka branch:

- DEV / 開発部

All sample departments should be active.

Respect all existing branch/department FK and uniqueness constraints.

# ================================================== 8. SAMPLE EMPLOYEES

Create realistic sample employees including BOTH permanent and dispatched employees.

At minimum:

EMP001
山田 太郎
ヤマダ タロウ
taro.yamada@example.test
Tokyo / 開発部
position: シニアエンジニア
type: permanent
status: active
hire date: 2022-04-01

EMP002
佐藤 花子
サトウ ハナコ
hanako.sato@example.test
Tokyo / 開発部
position: Webエンジニア
type: dispatched
status: active
hire date: 2025-04-01

EMP003
鈴木 一郎
スズキ イチロウ
ichiro.suzuki@example.test
Tokyo / 営業部
position: 営業担当
type: permanent
status: active
hire date: 2023-10-01

EMP004
高橋 美咲
タカハシ ミサキ
misaki.takahashi@example.test
Osaka / 開発部
position: PHPエンジニア
type: dispatched
status: active
hire date: 2025-07-01

Use realistic sample phone values if the schema supports them.

Respect the existing employee schema exactly.

Do not invent columns that do not exist.

# ================================================== 9. SAMPLE DISPATCH COMPANIES

Create at least:

Dispatch Company 1:
code: TECH-PARTNERS
name: テックパートナーズ株式会社
phone: 03-9876-1000
email: contact@tech-partners.example.test
address: 東京都新宿区西新宿1-1-1
status: active

Dispatch Company 2:
code: NEXT-STAFF
name: ネクストスタッフ株式会社
phone: 06-9876-2000
email: contact@next-staff.example.test
address: 大阪府大阪市北区梅田2-2-2
status: active

# ================================================== 10. SAMPLE DISPATCH CONTRACTS

Only dispatched employees may receive dispatch contracts.

Create useful sample contracts for:

EMP002 佐藤 花子
→ テックパートナーズ株式会社

EMP004 高橋 美咲
→ ネクストスタッフ株式会社

Use non-overlapping contract periods.

Also create enough contract history to manually verify:

- contract detail
- contract history
- renewal-style history
- expiration classification

IMPORTANT:
The application's expiration classifier uses the current Clock and these categories:

expired:
E < reference date

expiring_7:
reference date <= E <= reference date + 7 days

expiring_30:
reference date + 7 days < E <= reference date + 30 days

normal:
E > reference date + 30 days

Because fixed dates will eventually become stale, design the DEVELOPMENT sample contract dates sensibly.

Prefer generating relevant contract dates relative to the current date at seed time where appropriate, while still keeping them deterministic enough for understandable manual testing.

Do NOT create overlapping contracts for the same employee.

At minimum, make it possible to see meaningful dispatch contract history in the browser.

# ================================================== 11. DO NOT BYPASS BUSINESS/DATABASE RULES

Respect:

- employee_type permanent/dispatched
- active/inactive statuses
- branch/department relationships
- foreign keys
- unique employee code
- unique employee email
- unique dispatch company code
- contract start_date <= end_date
- no overlapping sample contracts for the same employee

Do not disable foreign key checks merely to make seeding easier.

# ================================================== 12. TESTS

Add focused automated tests for the development seeder.

At minimum verify:

Safety:

- refuses non-local APP_ENV
- refuses test database
- does not target DB_TEST_DATABASE

Behavior using the isolated test infrastructure where appropriate:

- creates expected organization records
- creates permanent employees
- creates dispatched employees
- creates dispatch companies
- creates dispatch contracts
- FK relationships are correct
- repeated seed execution is idempotent
- repeated execution does not duplicate employees/contracts
- unrelated existing records are not deleted

IMPORTANT:
Automated testing must remain safe.

Do NOT weaken the existing test DB protections.

If testing DevelopmentSeeder directly against the isolated `_test` DB is necessary, keep the CLI production/local safety guard separate from the seeder component so the test can exercise seeding logic safely without making `php bin/seed` accept `_test` databases.

The real CLI command itself must still refuse `_test`.

# ================================================== 13. REGRESSION

Run:

./vendor/bin/phpunit

Then run the complete suite against the explicitly configured isolated test database using the project's existing DB*TEST*\* convention.

Do NOT run tests against:

company_employee_management

Automated integration tests must only use:

company_employee_management_test

Do not alter or delete normal development data during automated tests.

# ================================================== 14. DOCUMENTATION

Add a concise development seeding document, for example:

docs/development-seeding.md

Document:

php bin/seed

Explain:

- development-only
- APP_ENV=local requirement
- idempotent behavior
- no truncation/deletion of unrelated data
- sample records created
- how it differs from migrations
- never use for production data

Keep it concise.

# ================================================== 15. FINAL REVIEW

Before finishing:

- review all changed files
- verify no production migration was modified for sample data
- verify no secrets were added
- verify no destructive SQL was introduced
- verify no unrelated files were changed
- verify existing Phase 05/06 behavior still passes

Do NOT commit.

At the end report:

1. files added/modified
2. safety design
3. sample records created
4. idempotency approach
5. normal PHPUnit result
6. isolated real-DB PHPUnit result
7. skipped tests and reasons
8. exact manual command to seed local development DB
9. any remaining concerns
10. git status --short
