<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DatabaseTools\Tests\Unit\Console;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\DatabaseTools\Tests\TestCase;

final class DatabaseToolsCommandTest extends TestCase
{
    public function test_unknown_action_fails(): void
    {
        $this->artisan('laranail::database-tools.db', ['action' => 'nope'])
            ->assertExitCode(1);
    }

    public function test_missing_path_fails(): void
    {
        $this->artisan('laranail::database-tools.db', ['action' => 'export'])
            ->assertExitCode(1);
    }

    public function test_dry_run_export_succeeds_without_touching_db(): void
    {
        $this->artisan('laranail::database-tools.db', [
            'action' => 'export',
            '--path' => '/tmp/does-not-matter.sql',
            '--dry-run' => true,
        ])->assertExitCode(0);
    }

    public function test_clean_rejects_unknown_tables(): void
    {
        $this->artisan('laranail::database-tools.db', [
            'action' => 'clean',
            '--tables' => 'ghost_table',
            '--force' => true,
        ])->assertExitCode(1);
    }

    public function test_clean_truncates_named_tables(): void
    {
        Schema::create('clean_widgets', function ($t): void {
            $t->id();
            $t->string('name');
        });
        DB::table('clean_widgets')->insert([['name' => 'a'], ['name' => 'b']]);

        self::assertSame(2, DB::table('clean_widgets')->count());

        $this->artisan('laranail::database-tools.db', [
            'action' => 'clean',
            '--tables' => 'clean_widgets',
            '--force' => true,
        ])->assertExitCode(0);

        self::assertSame(0, DB::table('clean_widgets')->count());
    }
}
