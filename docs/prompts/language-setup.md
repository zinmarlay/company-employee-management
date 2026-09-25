Continue working on the current branch:

feature/material-ui-foundation

Do not commit, push, merge, or switch branches.

The Material Design-inspired UI foundation is working, but after browser review we need two refinements:

1. Improve typography/readability.
2. Add a proper English/Japanese localization foundation.

Do not start Phase 06 business functionality.

1. Typography needs refinement

The current layout is structurally good, but many text elements are visually too small, especially on a desktop display.

Review the complete UI and improve the typography scale.

Pay particular attention to:

- sidebar navigation
- sidebar application name
- top app bar
- eyebrow text such as PEOPLE DIRECTORY
- page descriptions
- form labels
- form helper text
- required-field text
- table text
- status chips
- buttons

The application should remain clean and professional, but readability is more important than fitting excessive content onto the screen.

Do not simply increase every font by the same amount.

Create a coherent typography hierarchy.

As a general direction, consider approximately:

body:
16px
sidebar navigation:
0.95rem – 1rem
page title:
around 2rem – 2.25rem
page description:
around 1rem
form labels:
around 0.875rem – 0.95rem
form controls:
around 0.95rem – 1rem
helper/error text:
around 0.8rem – 0.85rem
buttons:
around 0.875rem – 0.95rem
table body:
around 0.875rem – 0.95rem

These are guidelines, not mandatory exact values.

Keep the visual hierarchy balanced.

Avoid oversized typography.

2. Japanese typography support

The application must render both English and Japanese cleanly.

Update the font stack to include suitable Japanese system fonts.

Prefer system fonts; do not add a remote Google Font dependency.

A reasonable direction is:

font-family:
Inter,
-apple-system,
BlinkMacSystemFont,
"Segoe UI",
"Hiragino Sans",
"Hiragino Kaku Gothic ProN",
"Yu Gothic",
"Meiryo",
sans-serif;

Adjust if necessary for good macOS/Windows Japanese rendering.

3. Add localization architecture

Add a lightweight localization/i18n mechanism appropriate for this Pure PHP application.

Do NOT introduce a framework or third-party translation framework unless genuinely necessary.

Support initially:

English: en
Japanese: ja

Design it so another language can be added later without rewriting views.

Possible structure:

resources/
└── lang/
├── en.php
└── ja.php

or another clean structure consistent with the existing architecture.

Translation files should return structured arrays.

For example:

return [
'navigation' => [
'dashboard' => 'Dashboard',
'employees' => 'Employees',
],
'employees' => [
'title' => 'Employees',
'create' => 'Create employee',
],
];

Japanese equivalent:

return [
'navigation' => [
'dashboard' => 'ダッシュボード',
'employees' => '社員',
],
'employees' => [
'title' => '社員',
'create' => '社員を登録',
],
];

Do not hard-code two-language ternary expressions throughout PHP views.

Bad:

$locale === 'ja' ? '社員' : 'Employees';

Views should use a reusable translator/localization abstraction.

4. Locale selection

Add an EN / 日本語 language selector to the shared application shell, preferably in the top app bar.

For example:

EN | 日本語

or an accessible select/menu.

It should be simple and professional.

The selected locale must persist between requests.

Use an approach appropriate for the current application architecture, such as a cookie.

Do not introduce authentication/session functionality merely for localization.

Use a safe default:

en

Supported locales must be explicitly allowlisted:

en
ja

Do not trust arbitrary locale values from request/query/cookie input.

5. URL / switching behavior

Implement a simple language-switch mechanism that preserves the current page where practical.

For example, when viewing:

/employees/create

switching to Japanese should keep the user on the same functional page rather than unnecessarily sending them somewhere unrelated.

Choose the implementation that best fits the existing router/request architecture.

Avoid unnecessary route duplication such as:

/en/employees
/ja/employees

unless the current architecture clearly benefits from that design.

For this internal administration system, a persisted locale preference is sufficient.

6. Translate shared application UI

Move shared hard-coded UI text into translation resources.

Translate at minimum:

Navigation

English:

- Dashboard
- Employees
- Branches
- Departments
- Dispatch companies
- Dispatch contracts
- Soon

Japanese:

- ダッシュボード
- 社員
- 支店
- 部署
- 派遣会社
- 派遣契約
- 準備中

Application shell

Translate appropriate text such as:

- Administration
- Workspace
- Company workspace
- Local workspace

Do not translate the product/company application name if it is treated as a proper application name.

7. Translate employee UI

Translate the existing Employee pages.

This includes:

- Employees
- Create employee
- Edit employee
- Employee detail
- Deactivate employee
- Back to employees
- Manage company employees and employment information.
- No employees yet
- Add the first employee to get started.
- Employee information
- Add employee details
- Required fields
- Employee code
- Hire date
- Last name
- First name
- Last name kana
- First name kana
- Email
- Phone
- Branch
- Department
- Position
- Employee type
- Status
- Create
- Update/Save
- Cancel
- Edit
- Deactivate

Also translate existing validation/feedback messages where they are part of the UI and doing so does not require changing domain/business semantics.

Use natural Japanese suitable for an internal Japanese company administration system.

Avoid awkward literal machine translations.

For example, terminology can use forms such as:

Employees 社員
Create employee 社員登録
Employee code 社員番号
Hire date 入社日
Last name 姓
First name 名
Last name kana 姓（カナ）
First name kana 名（カナ）
Email メールアドレス
Phone 電話番号
Branch 支店
Department 部署
Position 役職
Employee type 雇用区分
Status ステータス
Active 在籍
Inactive 退職・無効
Edit 編集
Cancel キャンセル

Check the actual domain meaning before choosing translations such as 退職 versus 無効.

Do not change stored database values merely for display translation.

For example, if database value is:

active

keep it as active internally and translate only its presentation.

8. Translate status chips

The status-chip component should support localized display labels while preserving existing internal values.

Examples:

active → Active / 在籍
inactive → Inactive / 無効
permanent → Permanent / 正社員
dispatched → Dispatched / 派遣社員

Do not alter database enum/value contracts.

9. HTML language attribute

The shared layout currently has:

<html lang="en">

Make this dynamic.

English:

<html lang="en">

Japanese:

<html lang="ja">

Ensure the locale value is allowlisted before output.

10. Keep escaping/security intact

Translation output and dynamic content must continue to respect the project’s escaping rules.

Do not weaken HtmlEscaper.

Do not introduce raw unescaped user data.

11. Controllers and architecture

Do not spread locale detection logic across every controller.

Centralize localization as much as reasonably possible.

If changes to bootstrap/request/view infrastructure are necessary, keep them small and architecturally clear.

EmployeeController should not become responsible for translation implementation details.

12. Existing behavior must remain unchanged

Do not change Employee business rules.

Preserve:

- list
- detail
- create
- edit
- deactivate
- validation
- duplicate handling
- status codes
- redirects
- database behavior
- repository/service boundaries

Localization is a presentation concern.

13. Tests

Add focused tests for localization behavior.

At minimum verify:

- default locale is English
- Japanese can be selected
- unsupported locale is rejected/falls back safely
- locale persists according to the chosen mechanism
- Japanese Employee UI renders expected Japanese labels
- existing HTTP behavior still works

Avoid brittle tests asserting huge complete HTML documents.

Run the normal suite and the explicit test-database suite.

14. Documentation

Update:

docs/specs/05a-material-ui-foundation.md

to document:

- typography changes
- supported locales
- translation resource structure
- locale resolution
- persistence mechanism
- how future translations are added

Do not create another redundant UI prompt/spec file unless needed.

Also inspect:

docs/prompts/material-ui.md

because it may duplicate:

docs/prompts/05a-material-ui-foundation.md

If it is genuinely redundant and contains no unique required information, remove the redundant file.

15. Cleanup

Remove accidental macOS metadata such as:

public/assets/.DS_Store

Ensure .DS_Store is ignored appropriately by Git if the repository does not already ignore it.

Do not delete legitimate project files.

16. Final verification

After implementation, verify both languages in the browser:

English:

/employees

/employees/create

Japanese:

switch the UI to Japanese and verify the same pages.

Confirm:

- typography is more readable
- Japanese characters render naturally
- no layout overflow
- form labels remain aligned
- language switching works
- current page is preserved where practical
- no untranslated obvious UI strings remain on the reviewed pages
- no PHP warnings/errors

Do not commit, push, or merge.

At the end, provide a concise report of:

1. typography changes
2. localization architecture
3. language-switch implementation
4. translation files
5. views/controllers/infrastructure modified
6. tests added/updated
7. normal test result
8. test DB result
9. browser verification
10. cleanup performed
11. any untranslated or intentionally untranslated text
12. confirmation that Phase 06 was not implemented
13. confirmation that no commit/push/merge was performed
