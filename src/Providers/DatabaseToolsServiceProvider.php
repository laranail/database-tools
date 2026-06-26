<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DatabaseTools\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Override;
use Psr\Log\LoggerInterface;
use Simtabi\Laranail\DatabaseTools\Backup\BackupManager;
use Simtabi\Laranail\DatabaseTools\Backup\Contracts\BackupManagerInterface;
use Simtabi\Laranail\DatabaseTools\Console\DatabaseToolsCommand;
use Simtabi\Laranail\DatabaseTools\DatabaseTools;
use Simtabi\Laranail\DatabaseTools\Files\Contracts\DatabaseFileServiceInterface;
use Simtabi\Laranail\DatabaseTools\Files\DatabaseFileService;
use Simtabi\Laranail\DatabaseTools\Schema\AuditColumnsMacro;
use Simtabi\Laranail\DatabaseTools\Schema\BlueprintMacros;
use Simtabi\Laranail\DatabaseTools\Schema\ConfiguredMorphsMacro;
use Simtabi\Laranail\DatabaseTools\Schema\Contracts\DatabaseConnectionTesterInterface;
use Simtabi\Laranail\DatabaseTools\Schema\Contracts\DatabaseSchemaInspectorInterface;
use Simtabi\Laranail\DatabaseTools\Schema\Contracts\DatabaseTableVerifierInterface;
use Simtabi\Laranail\DatabaseTools\Schema\DatabaseConnectionTester;
use Simtabi\Laranail\DatabaseTools\Schema\DatabaseSchemaInspector;
use Simtabi\Laranail\DatabaseTools\Schema\DatabaseTableVerifier;
use Simtabi\Laranail\DatabaseTools\Schema\FieldGroupMacros;
use Simtabi\Laranail\DatabaseTools\Schema\SoftDeleteHistoryMacro;
use Simtabi\Laranail\DatabaseTools\Schema\SoftDeletesWithUndoMacro;
use Simtabi\Laranail\DatabaseTools\Services\Contracts\DatabaseServiceInterface;
use Simtabi\Laranail\DatabaseTools\Services\Contracts\MaintenanceServiceInterface;
use Simtabi\Laranail\DatabaseTools\Services\DatabaseService;
use Simtabi\Laranail\DatabaseTools\Services\MaintenanceService;

final class DatabaseToolsServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/database-tools.php', 'database-tools');

        // Backup
        $this->app->singleton(BackupManagerInterface::class, BackupManager::class);

        // Files
        $this->app->singleton(DatabaseFileServiceInterface::class, DatabaseFileService::class);

        // Schema / Inspection
        $this->app->singleton(DatabaseSchemaInspectorInterface::class, DatabaseSchemaInspector::class);
        $this->app->singleton(DatabaseTableVerifierInterface::class, DatabaseTableVerifier::class);
        $this->app->singleton(DatabaseConnectionTesterInterface::class, DatabaseConnectionTester::class);

        // General DB service
        $this->app->singleton(DatabaseServiceInterface::class, DatabaseService::class);

        // Filesystem maintenance (caches, logs, storage symlink)
        $this->app->singleton(MaintenanceServiceInterface::class, fn ($app): MaintenanceService => new MaintenanceService(
            $app->make(LoggerInterface::class),
            $app->basePath(),
        ));

        if (class_exists('DatabaseTools')) {
            AliasLoader::getInstance()->alias('DatabaseTools', DatabaseTools::class);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DatabaseToolsCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../../config/database-tools.php' => config_path('database-tools.php'),
            ], 'database-tools-config');

            $this->publishes([
                __DIR__.'/../../database/migrations/0001_01_01_000000_create_soft_delete_history_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_soft_delete_history_table.php'),
            ], 'database-tools-migrations');
        }

        // Keep the custom BlueprintMacros builder aligned with the configured
        // key type so its id()/foreignId()/morphs() overrides match the macros.
        BlueprintMacros::setIdTypeResolver(static fn (): string => ConfiguredMorphsMacro::idType());

        AuditColumnsMacro::register();
        SoftDeletesWithUndoMacro::register();
        ConfiguredMorphsMacro::register();
        SoftDeleteHistoryMacro::register();
        FieldGroupMacros::register();
    }
}
