# Development seeding

Run the local sample-data seeder with:

```sh
php bin/seed
```

The command is development-only. It requires `APP_ENV=local` and refuses empty,
test-like, or `_test` database names. It uses the normal `DB_DATABASE` setting;
it never reads or targets `DB_TEST_*` settings.

The seeder is transactional and idempotent. It creates or reuses the sample
company, branches, departments, four employees, two dispatch companies, and
canonical dispatch-contract history for the two dispatched employees. Contract
periods intentionally use a stable sample schedule rather than the execution
date, so rerunning the command months later does not shift or duplicate them.
Re-running it does not duplicate sample rows, truncate tables, or delete unrelated data.
Stable identifiers that conflict with incompatible existing records cause the
transaction to fail instead of overwriting those records.

This command is separate from migrations: migrations create or change schema;
the seeder inserts local demonstration data. Never run it against production or
use it as a production data-loading tool.
