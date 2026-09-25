# Phase 05a — Material UI Foundation

This task establishes the shared Material Design-inspired presentation layer
for the existing Pure PHP Company Employee Management System after Phase 05
and before Phase 06.

Preserve the existing SSR request flow, controllers, services, repositories,
validation, database behavior, and lazy PDO connection. Use semantic HTML,
reusable PHP view partials, CSS custom properties, and minimal vanilla
JavaScript only when necessary. Do not introduce React, MUI, Vue, Tailwind,
Bootstrap, a Node build pipeline, authentication, or Phase 06 business logic.

Upgrade the shared layout and existing employee pages with:

- a reusable administration shell with sidebar, top bar, and main content;
- navigation that links only to implemented routes and marks future areas as
  disabled;
- a restrained Material-inspired token and component system;
- accessible buttons, forms, validation feedback, cards, status chips, and
  empty states;
- responsive desktop, tablet, and mobile layouts;
- an employee list with an intentional empty state;
- structured employee create/edit forms;
- card-based employee detail and deactivation pages;
- a professional root landing page without fake metrics or records.

All existing employee field names, submitted values, validation responses,
redirects, 404/405 behavior, escaping, and business rules must remain intact.
Add or update focused view tests, run the normal suite, run the full suite
with the explicit `_test` database, and verify `/`, `/employees`, and
`/employees/create` in the browser. Do not commit or merge the changes.
