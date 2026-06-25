# laranail/database-tools

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/database-tools.svg)](https://packagist.org/packages/laranail/database-tools)
[![Tests](https://github.com/laranail/database-tools/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/database-tools/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/database-tools/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/database-tools/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Independent, framework-agnostic database utilities for Laravel.

Model traits (UUID/NanoID/ULID keys, JSON accessors, slugs, immutability,
threaded records, quiet saving), money & datetime casts, schema macros
(`auditColumns()`, `softDeletesWithUndo()`, `configuredMorphs()`), an audit
observer, soft-delete restore history, backup/restore, connection & schema
inspection, a cursor-pagination DTO, and database/maintenance services —
designed to be useful in any Laravel app. This package is genuinely
**independent**: it depends only on `illuminate/*` plus a few small
utility libraries (`ramsey/uuid`, `symfony/uid`,
`spatie/laravel-sluggable`, and `brick/money` for the `CastMoney`
cast), and has **no dependency** on
[`laranail/package-tools`](https://github.com/laranail/package-tools)
or any other Laranail package. Seeding lives in `package-tools` and the
seed console formatter in `laranail/console` — neither belongs here.

## Targets

- PHP `^8.3 || ^8.4 || ^8.5`
- Laravel `^13.0` — depends only on `illuminate/database` + `illuminate/support`
- Pest `^3.0`, Testbench `^11.0`
- CI matrix: in-memory SQLite + optional MySQL/Postgres legs

## Install

```bash
composer require laranail/database-tools
```

`DatabaseToolsServiceProvider` is auto-discovered and registers the schema macros at boot.

## Quick examples

### UUID / NanoID / ULID model identifiers

```php
use Illuminate\Database\Eloquent\Model;
use Simtabi\Laranail\DatabaseTools\Concerns\HasUuid;

class Order extends Model
{
    use HasUuid;
    // Auto-sets a v4 UUID on the `uuid` column at creating-time.
    // Override uuidColumn() to use a different column.
}
```

`HasUlid` and `HasNanoid` follow the same pattern. ULIDs are
lexicographically sortable by creation time; NanoIDs are 21-char
URL-safe by default.

### Audit columns + observer

```php
// Migration:
Schema::create('orders', function (Blueprint $t) {
    $t->id();
    $t->string('name');
    $t->auditColumns();           // adds created_by, updated_by, deleted_by
    $t->softDeletesWithUndo();    // adds deleted_at + restored_at
    $t->timestamps();
});

// Model:
use Simtabi\Laranail\DatabaseTools\Observers\AuditObserver;

class Order extends Model
{
    protected static function booted(): void
    {
        static::observe(AuditObserver::class);
    }
}
```

The observer stamps the authenticated user's ID into the audit columns
on create/update/delete; override `userIdentifier()` if your FK isn't
the user's primary key.

### JSON column accessors

```php
use Simtabi\Laranail\DatabaseTools\Concerns\HasJsonColumnAccessors;

class Order extends Model
{
    use HasJsonColumnAccessors;

    protected array $jsonColumns = ['metadata', 'snapshot'];
}

$order->metadata = ['shipped_via' => 'fedex'];   // auto-encoded on save
$order->save();
$order->metadata['shipped_via'];                 // 'fedex' — auto-decoded on read
```

Skips columns already in `$casts` to avoid double-cast.

## Local development

```bash
bash .scripts/init.sh
composer test                 # vendor/bin/pest
composer lint                 # pint + phpstan + rector --dry-run
composer audit                # composer audit (security advisories)
```

## Documentation

Hosted at [`opensource.simtabi.com/database-tools/docs/`](https://opensource.simtabi.com/database-tools/docs/)
(product page: [`opensource.simtabi.com/database-tools/`](https://opensource.simtabi.com/database-tools/)).
The same pages live under [`docs/`](docs/):

**Guides**

- [Installation](docs/installation.md) — Composer install, auto-discovery, the `DatabaseTools` facade, targets
- [Configuration](docs/configuration.md) — publishing `config/database-tools.php` + the history migration; every config key
- [Architecture](docs/architecture.md) — how the facade, schema services, and backup drivers fit together; the independence invariant

**Tools & features**

- [Facade](docs/tools/facade.md) — every `DatabaseTools` static method, with examples
- [Connection testing](docs/tools/connection-testing.md) — `DatabaseConnectionTester`: test, driver, version, database name
- [Schema inspection](docs/tools/schema-inspection.md) — `DatabaseSchemaInspector`: tables, columns, counts
- [Table verification](docs/tools/table-verification.md) — `DatabaseTableVerifier`: verify, detailed report, Laravel tables
- [Backup & restore](docs/tools/backup-restore.md) — `BackupManager`, per-driver backups, driver-aware restore, dump import
- [Database CLI](docs/tools/database-cli.md) — `laranail::database-tools.db` command: import / export / restore / clean
- [Traits](docs/tools/traits.md) — model identifier & behavior traits under `Concerns/`
- [Soft-archive](docs/tools/archiving.md) — `HasArchiver`: `archived_at` archive/restore (coexists with soft deletes)
- [Casts](docs/tools/casts.md) — `CastMoney` (brick/money) and `CastDatetime`
- [Schema macros](docs/tools/macros.md) — `auditColumns()`, `softDeletesWithUndo()`, `configuredMorphs()`, `softDeleteHistory()`, `BlueprintMacros`, field-group macros
- [Audit observer](docs/tools/observers.md) — `AuditObserver` + `HasAuditObserver`: stamping created/updated/deleted by
- [Soft-delete restore history](docs/tools/soft-deletes.md) — `HasSoftDeletesWithUndo` trait + history table
- [Eager-load helpers](docs/tools/eager-loading.md) — `LoadsAggregatesIfMissing`: load-if-missing for counts & aggregates
- [Pagination](docs/tools/pagination.md) — `CursorPage` DTO + offset `Pagination` helper
- [Events](docs/tools/events.md) — `DatabaseEvents` + `BaseEvent`
- [BaseModel](docs/tools/base-model.md) — the optional base Eloquent model
- [Database session](docs/tools/database-session.md) — `DatabaseSession` read model over the `sessions` table
- [Services](docs/tools/services.md) — `DatabaseService` query/model helpers + `MaintenanceService` filesystem housekeeping

**Examples**

- [Runnable examples](docs/examples/) — `Order.php` + `OrderMigration.php` demonstrating the identifier traits, audit columns, soft-deletes-with-undo, and JSON accessors together

- Changelog: [CHANGELOG.md](CHANGELOG.md)

## Sister packages

- [`laranail/console`](https://github.com/laranail/console) — console output, prompts and Artisan command toolkit.
- [`laranail/package-tools`](https://github.com/laranail/package-tools) — runtime base library for building Laravel packages.
- [`laranail/package-scaffolder`](https://github.com/laranail/package-scaffolder) — generator that scaffolds new packages.
- [`laranail/laranail`](https://github.com/laranail/laranail) — Simtabi's Laravel utility toolbox.

## Contributing & security

- [CONTRIBUTING.md](CONTRIBUTING.md) — development guidelines and PR expectations.
- [SECURITY.md](SECURITY.md) — how to report a vulnerability (opensource@simtabi.com).
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) — community expectations.

## License

MIT. See [LICENSE](LICENSE).
