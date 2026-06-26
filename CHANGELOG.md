# Changelog

All notable changes to `laranail/database-tools` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-06-25

### Added

Database-oriented features relocated here from `laranail/toolkit` (which now
defers all DB/UUID concerns to this package):

- **`Console\DatabaseToolsCommand`** — the `laranail::database-tools.db` Artisan
  command: `import` / `export` / `restore` / `clean`. Destructive actions confirm
  first (and require `--force` in non-interactive runs); `clean` truncates through
  the query grammar after verifying tables exist; dumps/restores delegate to the
  per-driver `BackupManager`. Adds a local `Console\Concerns\SupportsNamespacedNames`
  trait so the `::` command name works without a `laranail/console` dependency.
- **`Concerns\HasArchiver`** + **`Schema\Scopes\ArchiveScope`** — soft-archive on
  an `archived_at` column (archive / unArchive / `onlyArchived()` / `withArchived()`),
  coexisting with Laravel's native soft deletes.
- **`Models\DatabaseSession`** — read model over the `sessions` table
  (`SESSION_DRIVER=database`) with safe payload decoding and a configurable user
  relation.
- **`Pagination\Pagination`** — offset (page-number) `LengthAwarePaginator`
  helper for arrays and query builders, complementing the cursor `CursorPage`.
- **`Schema\FieldGroupMacros`** — reusable `Blueprint` column-group macros
  (`addCommonFields`, `addUserFields`, `addSlugField`, `addPublishingFields`,
  `addMetaFields`/`addSeoFields`, `addLocationFields`, `addImageFields`,
  `addPriceFields`, `addActivationFields`, `addExpiryFields`, `addStatusField`,
  `addSortingField`, `addUuidPrimaryKey`, `addNullableMorphs`, and the conditional
  `dropForeignIfExists` / `dropColumnIfExists`).

## [0.1.0] - 2026-06-19

Initial release — independent Laravel database utilities.

### Added

**Model traits & casts**

- `Concerns\HasUuid` (RFC 4122, time-ordered), `HasNanoid` (21-char), `HasUlid`
  (sortable) — auto-set a unique key on creating.
- `Concerns\HasUuidsOrIntegerIds` — switch the key type (BIGINT/UUID/ULID) from
  `config('database-tools.*')`; `HasUuidOptions::generateUuidUsing()` registers a
  host-supplied generator (e.g. deterministic UUIDs in tests).
- `Concerns\HasJsonColumnAccessors`, `HasScopes`, `HasImmutability`
  (+ `Exceptions\ImmutableDataException`), `HasThreadedParentChildrenRecords`,
  `HasSlug`, `HasQuietSaving`, `ManagesForeignKeyChecks` (per-connection
  nesting), `ManagesTransactions`, `ValidatesFilePaths` (realpath containment).
- `Concerns\LoadsAggregatesIfMissing` — opt-in `loadIfMissing()`,
  `loadCountIfMissing()`, `loadAggregateIfMissing()` over Eloquent's native
  lazy-eager-loaders.
- `Casts\CastMoney` — backed by [`brick/money`](https://github.com/brick/money):
  stores integer minor units and reads a `Brick\Money\Money` value object
  (currency from a cast argument or `config('database-tools.money.default_currency')`;
  `toArray()`/JSON emit `{ amount, currency }`). `Casts\CastDatetime` — UTC
  storage, timezone-aware presentation.
- Optional `Models\BaseModel` — UUID-or-integer keys, lifecycle hook stubs,
  time-based scopes, metadata helpers.

**Schema macros** (registered by `DatabaseToolsServiceProvider`)

- `Blueprint::auditColumns()` — `created_by` / `updated_by` / `deleted_by`.
- `Blueprint::softDeletesWithUndo()` — soft-delete plus a `restored_at` column.
- `Blueprint::configuredMorphs()` / `configuredNullableMorphs()` — id-type-aware
  polymorphic columns (int/uuid/ulid from `database-tools.id_type`).
- `Blueprint::softDeleteHistory()` — the companion table for restore history.

**Audit & soft-delete history**

- `Observers\AuditObserver` — stamps `created_by`/`updated_by`/`deleted_by` from
  the authenticated user, using the column names from
  `config('database-tools.audit.*')`. Null-safe: leaves the nullable columns
  untouched for guest/console/queue writes, and on a soft-delete persists only
  `deleted_by` with a targeted keyed update. `Concerns\HasAuditObserver` attaches
  it via `whenBooted` where the native `#[ObservedBy(AuditObserver::class)]`
  attribute is awkward.
- `Concerns\HasSoftDeletesWithUndo` — records delete/restore events into the
  history table, stamps `restored_at`, with a nullable actor; helpers
  `softDeleteHistory()` and `restoreWithHistory()`.

**Backup & restore**

- `Backup\BackupManager` with pluggable MySQL / PostgreSQL / SQLite drivers.
  Restore is driver-aware: PostgreSQL custom-format dumps restore via
  `pg_restore`, MySQL replays through the `mysql` client, SQLite uses
  `SqlFileRestorer` for `.sql` (or a file copy). All shelling uses `Process` with
  array arguments; credentials pass via environment variables.
- Backups honour `config('database-tools.backup')`: `gzip` compression,
  `exclude` tables, and optional `binaries.*` paths.
- `Files\DatabaseFileService::handleImport(string $path, ?string $connection = null)`
  — validated dump import (`.sql` / `.dump`) that delegates to the restore;
  refuses live `.sqlite` / `.db` files.

**Inspection, services & pagination**

- `Schema\DatabaseConnectionTester`, `DatabaseSchemaInspector`,
  `DatabaseTableVerifier` — bound to interfaces and reachable via the
  `DatabaseTools` facade.
- `Services\DatabaseService` — query/model helpers (`isJoined`,
  `modifyTimestamps`, `handleViewCount`, `setMorphClassNames`,
  `generateRelationshipSyncData`).
- `Services\MaintenanceService` — filesystem housekeeping (`clearCache`,
  `clearLogFiles`, `deleteStorageSymlink`), kept separate from the database
  service.
- `Pagination\CursorPage` — a `Responsable` / `Arrayable` / `JsonSerializable`
  DTO over Laravel's native `cursorPaginate()`, built via
  `CursorPage::fromPaginator(...)`.

**Configuration & docs**

- Publishable `config/database-tools.php` (tag `database-tools-config`):
  `id_type` (+ `using_uuids_for_id` / `using_ulids_for_id`), `audit.*`,
  `money.default_currency`, `backup.{gzip,exclude,binaries}`,
  `soft_delete_history.table`. Soft-delete history migration publishable via tag
  `database-tools-migrations`.
- Full documentation suite under `docs/` (installation, configuration,
  architecture, and per-feature pages).

### Dependencies

- PHP `^8.3 || ^8.4 || ^8.5`, Laravel `^13.0`.
- `brick/money: ^0.10`, `ramsey/uuid: ^4.7`, `symfony/uid: ^7.0`,
  `spatie/laravel-sluggable: ^3.6`.

### Independence

This package has **no dependency** on `laranail/package-tools` or any other
Laranail package — only `illuminate/*` plus the small utility libraries above.
Seeding lives in `package-tools` and the seed console formatter in
`laranail/console`; neither belongs here.
