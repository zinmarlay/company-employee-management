# Phase 05a: Material UI Foundation Specification

## 1. Purpose

Phase 05a replaces the browser-default presentation of the existing employee
management application with a reusable, accessible, Material Design-inspired
SSR interface. It is a presentation-only phase between Phase 05 and Phase 06.

The implementation must preserve Pure PHP, server-side rendering, the custom
router, controllers, services, repositories, PDO, lazy database behavior,
validation, HTTP status semantics, and existing employee business rules.

## 2. Scope and non-goals

Included:

- a shared administration shell;
- responsive sidebar navigation and top app bar;
- CSS design tokens and reusable component styles;
- page headers, cards, buttons, badges, alerts, form fields, empty states,
  tables, and detail sections;
- upgraded root, employee list, employee create/edit, employee detail, and
  deactivation views;
- focused rendering/feature-test updates and browser verification.

Not included:

- dispatch companies or dispatch contracts;
- authentication, authorization, or CSRF;
- fake dashboard statistics or seed data;
- search, filtering, sorting, or pagination not already implemented;
- React/MUI, Vue, Tailwind, Bootstrap, or an npm build pipeline;
- changes to the database schema, repositories, or application business rules.

## 3. Shared layout architecture

`resources/views/layout.php` is the single application shell. It provides:

```text
app-shell
├── app-sidebar
│   ├── application identity
│   ├── implemented Dashboard and Employees links
│   └── disabled future navigation items
├── app-main
│   ├── topbar with current page context
│   └── main-content
└── shared stylesheet link
```

Controllers pass `activeNav` so the current workspace is visibly selected.
Only `/` and `/employees` are links because those are the currently
implemented business routes. Branches, departments, dispatch areas, and
system users are displayed as disabled “Soon” items without fake routes.

The layout keeps account text as a non-functional placeholder because
authentication is not implemented. The root page is a welcome/overview page;
it does not claim to provide dashboard metrics.

Reusable PHP partials are kept within the existing `ViewRenderer` convention:

- `resources/views/partials/page-header.php` — eyebrow, title,
  description, and safe action links;
- `resources/views/partials/status-chip.php` — allowlisted visual tones for
  active/inactive and permanent/dispatched values;
- `resources/views/partials/empty-state.php` — deliberate no-record state with
  an optional action.

Views receive prepared `$data` only. They do not call services, repositories,
or global request state.

## 4. CSS organization and visual system

The visual system lives in:

```text
public/assets/css/app.css
```

It uses CSS custom properties for:

- primary and semantic colors;
- page background and surfaces;
- text, muted text, and borders;
- spacing scale;
- radii and card shadow;
- sidebar/content dimensions;
- system typography stacks.

The design is restrained and administrative: light surfaces, strong readable
type, indigo primary actions, semantic status colors, subtle borders/shadows,
and no dependency on remote fonts. Buttons have primary, secondary, text,
danger, small, hover, focus, and disabled-ready styling. Inputs and selects
use outlined form-field styling and visible invalid states.

No JavaScript is required for core navigation or form submission. The
responsive layout remains usable when JavaScript is unavailable.

## 5. Employee page integration

### 5.1 List

`/employees` keeps the existing list data and actions while adding:

- page title, description, and Create Employee action;
- responsive table card with readable spacing and row hover;
- status/type chips;
- accessible row action links;
- record count;
- deliberate empty state with a Create Employee action.

No employees are inserted merely to populate the screen.

### 5.2 Create/edit forms

`/employees/create` and `/employees/{id}/edit` keep every existing field name,
value, option, maxlength, validation error, and POST action. The form is
organized into a responsive two-column grid that collapses to one column on
small screens. Labels remain associated with controls, required fields are
identified, validation errors use `aria-invalid`/`aria-describedby`, and
submitted values remain escaped and visible after a 422 response.

The form retains server-side validation as the authority. It does not add
client-side business validation that could diverge from the service.

### 5.3 Detail and deactivation

The detail view preserves all existing employee values and timestamp output,
but presents them as a profile summary plus Basic Information, Organization,
and Employment cards. Active/inactive and permanent/dispatched values use
reusable chips. The deactivation confirmation remains GET-only for display and
POST-only for mutation, with the existing confirmation text and redirect
behavior preserved.

### 5.4 Root page

The root page replaces the obsolete Phase 02 placeholder with a welcome page,
an employee-management call to action, and a simple application-ready status
card. It introduces no new database query or fake metric.

## 6. Accessibility and responsive behavior

The UI must maintain:

- semantic `aside`, `nav`, `header`, `main`, `section`, `article`, `form`,
  `table`, `dl`, and button/link elements;
- visible focus rings for keyboard users;
- labels associated with every form control;
- appropriate heading hierarchy;
- `aria-current` for active navigation;
- `role="alert"` for validation failures and `role="status"` for notices;
- sufficient contrast for text, controls, and semantic colors;
- no clickable non-interactive elements.

Desktop uses a sidebar and content column. At tablet widths the content and
dashboard cards adapt. At mobile widths the sidebar becomes a full-width
navigation block, form/detail grids become one column, and actions become
full-width stacks. Tables remain readable through horizontal scrolling and
data labels.

## 7. Files

Expected additions:

- `public/assets/css/app.css`;
- `resources/views/partials/page-header.php`;
- `resources/views/partials/status-chip.php`;
- `resources/views/partials/empty-state.php`;
- `resources/lang/en.php`;
- `resources/lang/ja.php`;
- `src/Localization/Locale.php`;
- `src/Localization/Translator.php`;
- `src/Http/Middleware/LocaleMiddleware.php`;
- `docs/prompts/05a-material-ui-foundation.md`;
- `docs/specs/05a-material-ui-foundation.md`.

Expected modifications:

- `resources/views/layout.php`;
- `resources/views/setup.php`;
- `resources/views/employees/index.php`;
- `resources/views/employees/create.php`;
- `resources/views/employees/edit.php`;
- `resources/views/employees/_form.php`;
- `resources/views/employees/show.php`;
- `resources/views/employees/deactivate.php`;
- `src/Http/Controllers/SetupController.php`;
- `src/Http/Controllers/EmployeeController.php`;
- `src/Http/Request.php` and `src/Http/View/ViewRenderer.php` for locale
  input and translation context;
- focused HTTP/view tests and README only where useful.

No migration, repository, service, or Phase 06 file is required.

## 8. Testing and verification

Run:

```sh
composer test
```

Then run the full suite with only the isolated database configuration:

```sh
APP_ENV=test \
DB_TEST_HOST=127.0.0.1 \
DB_TEST_PORT=3306 \
DB_TEST_DATABASE=company_employee_management_test \
DB_TEST_USERNAME=root \
DB_TEST_PASSWORD='' \
DB_TEST_CHARSET=utf8mb4 \
composer test
```

The test database must never be replaced with
`company_employee_management`. Existing behavior tests must continue to cover
303 redirects, 404, 405, 422, output escaping, validation preservation, and
lazy database boot.

Browser verification uses `php -S localhost:8000 -t public` and checks:

- `/` — shared shell and welcome page;
- `/employees` — table or deliberate empty state;
- `/employees/create` — responsive structured form;
- employee detail/edit/deactivation when development data is available;
- stylesheet loading, navigation, focus states, escaped output, and absence
  of PHP warnings or broken asset URLs.

## 9. Acceptance criteria

Phase 05a is complete when:

1. The shared shell is rendered by the existing layout for root and employee
   pages.
2. Existing routes and controller/service/repository behavior are unchanged.
3. Only implemented routes are clickable in navigation.
4. The root page no longer shows obsolete Phase 02 placeholder messaging.
5. Employee list, empty state, detail, forms, and deactivation pages use the
   shared visual system.
6. Submitted values, validation messages, escaping, redirects, and status
   codes continue to work.
7. Forms and actions are keyboard-accessible and responsive.
8. No fake records or dashboard metrics are added.
9. Normal and isolated-test suites pass.
10. Browser verification confirms CSS, layout, navigation, and forms render
    without warnings or broken assets.

## 10. Typography and presentation localization

The shared stylesheet uses a readable system stack that includes macOS and
Japanese UI fonts (`Hiragino Sans`, `Hiragino Kaku Gothic ProN`, `Yu Gothic`,
and `Meiryo`) after the existing Latin system fonts. Body text is 16px with a
1.6 line height; headings, metadata, controls, table cells, and validation
feedback use distinct, intentionally sized levels rather than one uniform
scale. No remote font request or JavaScript runtime is required.

Presentation strings are kept in PHP resource arrays:

```text
resources/lang/en.php
resources/lang/ja.php
```

`App\Localization\Translator` provides dot-delimited lookups, placeholder
replacement, English fallback for missing keys, and a small mapping for the
existing English validation messages. Shared layout, navigation, employee
pages, status chips, empty states, and form feedback consume translation keys;
employee data, routes, service rules, and repository boundaries remain
unchanged.

`LocaleMiddleware` resolves the locale in this order:

1. an allowlisted `lang` query value (`en` or `ja`) for the current request;
2. the allowlisted `app_locale` cookie when no query value is present;
3. English (`en`).

An unsupported query value explicitly falls back to English. A valid query
selection is persisted with a one-year, path-wide, `SameSite=Lax` cookie. The
layout emits the resolved locale on the `<html lang>` attribute, and its EN / 日本語
links preserve the current page path.

Future translations should add stable keys to both resource arrays and update
views to use the translator; business-layer messages and persisted data should
not be localized in this presentation phase.
11. Phase 06 business logic is not implemented.
12. No commit, push, merge, branch switch, or branch deletion is performed.
