Material Design UI Foundation

We need to implement the shared UI foundation for the existing Company Employee Management System before starting Phase 06.

The application is already functional through Phase 05, but the browser UI currently renders mostly as unstyled/default HTML.

For example, /employees/create currently shows plain browser-default labels, inputs, selects, links, and buttons.

The goal of this task is to establish a professional, reusable Material Design-inspired administration UI for the existing Pure PHP SSR application.

Do not start Phase 06 business functionality.

⸻

1. Existing architecture must be preserved

This is an existing Pure PHP application.

Current constraints:

- PHP 8.3+
- Pure PHP
- Server-Side Rendering
- PDO
- Composer / PSR-4
- Front Controller
- Router
- Middleware pipeline
- Controller → Service → Repository architecture
- PHP views
- HTML5
- CSS
- minimal vanilla JavaScript where necessary
- PHPUnit
- no PHP framework
- no ORM

Do NOT convert the project to Laravel, Symfony, React, Vue, Next.js, or another frontend/backend framework.

Do NOT rewrite working Phase 01–05 architecture.

⸻

2. Important clarification: Material Design, not React MUI

The desired UI is Material Design-inspired.

Do NOT install or introduce React Material UI (@mui/material) because this application does not use React.

Implement the visual system using the existing SSR PHP architecture with:

- semantic HTML
- reusable PHP view partials/components
- CSS
- minimal vanilla JavaScript where useful

A lightweight icon solution may be used if appropriate, but avoid introducing a large frontend framework just for styling.

Do not introduce a Node/npm build pipeline unless the existing project already requires one.

⸻

3. First inspect the existing project

Before modifying anything, inspect:

- resources/views/
- existing layouts
- existing partials
- employee views
- public assets
- public/index.php
- routing
- controllers
- response/view rendering
- current tests
- README
- project documentation

Identify the current view composition strategy before designing the shared layout.

Do not blindly replace existing views.

⸻

4. Create a reusable application shell

Build a shared administration layout.

Target structure:

Application Shell
│
├── Sidebar
│ ├── Application identity
│ ├── Dashboard
│ ├── Employees
│ ├── Branches
│ ├── Departments
│ ├── Dispatch Companies
│ ├── Dispatch Contracts
│ └── System Users / Settings placeholder if appropriate
│
├── Top App Bar
│ ├── Current page/context
│ └── user/account area placeholder if authentication is not implemented yet
│
└── Main Content
├── Breadcrumb / back navigation
├── Page header
├── Main content/card
└── Page actions

The shell must be reusable by future Phase 06+ pages.

Do not duplicate sidebar/header markup in every page.

⸻

5. Navigation must reflect actual implementation status

Do not create fake working functionality.

If Dashboard, Dispatch Companies, Contracts, System Users, etc. are not implemented yet:

- they may appear as disabled/future navigation items where appropriate,
- or omit them until implemented.

Do not create links that lead to nonexistent routes just to make the sidebar look complete.

Existing working routes must remain functional.

⸻

6. Material Design-inspired visual system

Create a coherent design system rather than isolated page-specific CSS.

Define reusable design tokens/custom properties for concepts such as:

- primary color
- surface/background
- text colors
- muted text
- border/divider
- success
- warning
- danger/error
- spacing scale
- border radius
- shadows
- typography
- sidebar dimensions
- content width

Prefer CSS custom properties.

Example conceptual structure:

:root {
--color-primary: ...;
--color-surface: ...;
--color-background: ...;
--color-text: ...;
--color-text-muted: ...;
--spacing-1: ...;
--spacing-2: ...;
--radius-small: ...;
--radius-medium: ...;
--shadow-card: ...;
}

Choose a professional restrained admin-system appearance.

Avoid excessive gradients, animations, oversized elements, or flashy portfolio styling.

⸻

7. Typography

Create a clear typography hierarchy for:

- application title
- page title
- section title
- labels
- body text
- helper text
- table text
- validation/error messages

Use a sensible system font stack unless an existing project decision specifies otherwise.

Do not require remote fonts merely for the page to render correctly.

⸻

8. Buttons

Create reusable button styles:

- Primary
- Secondary / outlined
- Text
- Danger
- Disabled

Buttons should have:

- consistent height
- spacing
- border radius
- hover state
- focus state
- disabled state

Examples:

Create employee → primary
Cancel → secondary/text
Edit → secondary
Deactivate → danger

Do not rely on browser-default button styling.

⸻

9. Form system

The existing Employee Create/Edit forms must be upgraded.

Create reusable styling for:

- text input
- email input
- telephone input
- date input
- select
- textarea if used
- labels
- required indicators
- helper text
- validation errors
- disabled/read-only state
- field groups

Use Material-inspired outlined/form-field styling where practical with semantic HTML/CSS.

Do not sacrifice accessibility for visual imitation.

Forms must remain usable without JavaScript.

⸻

10. Employee form layout

Improve the current /employees/create and /employees/{id}/edit layout.

Do not render every field as a narrow browser-default input stacked without structure.

Use a responsive form layout.

For example:

Employee Information
────────────────────────────────────
Employee Code Hire Date
Last Name First Name
Last Name Kana First Name Kana
Email Phone
Branch Department
Position Title Employee Type
[Cancel] [Create Employee]

Exact layout may be adjusted based on the existing fields.

On narrow screens, fields should collapse into one column.

Preserve all existing field names, submitted values, validation behavior, and controller contracts.

⸻

11. Employee list

Upgrade /employees.

Create a professional management table/card interface.

Include existing available functionality such as:

- page title
- Create Employee action
- employee data
- status
- employee type
- branch
- department
- available actions

If existing search/filter controls are implemented, style them consistently.

Do not invent backend filters that do not exist.

Table requirements:

- readable spacing
- clear header
- row hover
- responsive behavior
- accessible links/actions
- empty state

The existing:

No employees have been registered.

must become a deliberate empty-state component instead of plain text.

Example concept:

Employees
Manage company employees and employment information.
[ + Create Employee ]
┌────────────────────────────────────────────┐
│ No employees yet │
│ Add the first employee to get started. │
│ │
│ [ Create Employee ] │
└────────────────────────────────────────────┘

Do not insert fake employees.

⸻

12. Employee detail page

Upgrade the Employee Detail view using cards/sections.

Possible structure:

Employee Detail
[Active] [Permanent]
Basic Information
─────────────────
Employee code
Name
Kana
Email
Phone
Organization
────────────
Branch
Department
Position
Employment
──────────
Employee type
Hire date
Status
[Back] [Edit] [Deactivate]

Use existing data only.

Do not invent fields.

⸻

13. Status chips / badges

Create reusable chips/badges for values such as:

- Active
- Inactive
- Permanent
- Dispatched

Prepare styles that can later support Phase 06 contract statuses such as:

- Normal
- Expiring within 30 days
- Expiring within 7 days
- Expired

Do not implement Phase 06 contract logic now.

Only make the visual component reusable.

⸻

14. Cards

Create reusable card styles for:

- forms
- detail sections
- empty states
- future dashboard widgets

Cards should have consistent:

- padding
- border/radius
- background
- subtle shadow/border
- section spacing

⸻

15. Feedback and validation

Existing validation behavior must remain intact.

Style:

- field validation messages
- general errors
- success messages if currently supported
- warning/danger states

Validation errors should be visually obvious but professional.

Do not remove server-side validation.

Do not replace server-side validation with JavaScript validation.

⸻

16. Accessibility

Maintain good accessibility.

At minimum:

- labels associated with form controls
- visible keyboard focus
- adequate contrast
- semantic buttons/links
- correct heading hierarchy
- meaningful navigation landmarks
- usable keyboard navigation
- appropriate aria-\* only where needed

Do not create clickable <div> elements where buttons or links are appropriate.

⸻

17. Responsive design

The administration interface should work reasonably on:

- desktop
- tablet
- mobile

Desktop:

Sidebar | Main content

Smaller screens may use:

- collapsed sidebar
- drawer/navigation toggle
- stacked form fields

If JavaScript is used for mobile navigation, keep it small and framework-free.

Core content must remain accessible if JavaScript is unavailable.

⸻

18. CSS organization

Avoid a giant collection of random inline styles.

Prefer a maintainable asset structure appropriate to the existing project, for example:

public/
└── assets/
├── css/
│ └── app.css
└── js/
└── app.js

or a similarly clean structure consistent with the repository.

Do not introduce unnecessary build tooling.

Avoid inline CSS except where there is a strong reason.

⸻

19. Reusable PHP view components / partials

Where appropriate, introduce reusable view partials for concepts such as:

layout
sidebar
topbar
page header
form errors
status chip
empty state

Use the project’s existing ViewRenderer conventions.

Do not build a new templating engine.

Do not add Blade/Twig unless already present.

⸻

20. Existing Employee functionality must not regress

All Phase 05 functionality must continue working:

- employee list
- employee detail
- create
- edit
- deactivate
- validation
- duplicate handling
- search/filter behavior if present
- pagination behavior if present
- 303 redirects
- 404
- 405
- 422

Do not change business rules merely for UI convenience.

⸻

21. Database architecture must not change

Do not alter:

- PDO repository architecture
- LazyPdoConnection
- development/test DB separation
- migration behavior
- .env loading behavior

The recently added environment-loading fix must remain intact.

⸻

22. Tests

Run all existing tests after the UI work.

Normal suite:

composer test

Then run the full suite with the dedicated test database:

APP_ENV=test \
DB_TEST_HOST=127.0.0.1 \
DB_TEST_PORT=3306 \
DB_TEST_DATABASE=company_employee_management_test \
DB_TEST_USERNAME=root \
DB_TEST_PASSWORD='' \
DB_TEST_CHARSET=utf8mb4 \
composer test

Never run destructive integration tests against:

company_employee_management

If view/HTTP tests make brittle assertions against exact HTML that must change for the new shared layout, update those tests carefully while preserving their behavioral intent.

Add focused tests for important reusable rendering behavior if appropriate.

⸻

23. Browser verification

Use the local server:

php -S localhost:8000 -t public

Verify at minimum:

/
/employees
/employees/create

Also verify existing employee detail/edit routes if development data is available.

Check:

- CSS loads correctly
- no browser-default form appearance dominates the page
- sidebar/layout renders correctly
- forms are readable
- navigation works
- responsive behavior is reasonable
- no PHP warnings/errors
- no broken asset URLs

Do not add fake production/development records just for screenshots.

⸻

24. Root page

The / route currently reflects earlier architecture-phase messaging.

Inspect it.

If it still says things such as:

Phase 02 HTTP and presentation architecture is active.

replace the obsolete development-placeholder presentation with an appropriate application landing/dashboard shell without implementing fake dashboard business metrics.

A simple professional welcome/dashboard placeholder inside the shared admin layout is acceptable.

Do not claim unimplemented functionality exists.

⸻

25. Documentation

Create/update documentation for this UI foundation.

Add:

docs/prompts/05a-material-ui-foundation.md
docs/specs/05a-material-ui-foundation.md

The specification should document:

- purpose
- shared layout architecture
- CSS organization
- reusable UI components
- responsive behavior
- accessibility decisions
- employee page integration
- constraints
- testing

Update README only where useful.

⸻

26. Scope boundaries

Do NOT implement:

- Phase 06 Dispatch Company business logic
- Dispatch Contract business logic
- authentication
- authorization
- CSRF unless already part of current scope
- dashboard statistics requiring new queries
- React
- Vue
- frontend SPA routing
- Tailwind unless already explicitly selected for this project
- Bootstrap unless already explicitly selected
- a Node/npm frontend build system merely for this task

This task is the shared Material Design-inspired UI foundation for the existing SSR application.

⸻

27. Git safety

Current branch:

feature/material-ui-foundation

Work only on the current branch.

Do NOT:

- commit
- push
- merge
- switch branches
- delete branches
- manipulate Phase 06 stash/work

Leave all modifications in the working tree for review.

⸻

28. Final report

When finished, report:

1. What was wrong with the previous UI
2. UI architecture introduced
3. Files created
4. Files modified
5. Shared PHP partials/components introduced
6. CSS/JS assets introduced
7. Employee list changes
8. Employee create/edit form changes
9. Employee detail changes
10. Root page changes
11. Responsive behavior
12. Accessibility improvements
13. Test changes
14. Normal test result
15. Real test DB result
16. Browser verification performed
17. Remaining limitations
18. Confirmation that Phase 06 business logic was NOT implemented
19. Confirmation that no commit/push/merge was performed
