<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DatabaseTools\Tests\Unit\Schema;

use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\DatabaseTools\Tests\TestCase;

final class SchemaMacrosTest extends TestCase
{
    public function test_audit_columns_macro_adds_three_columns(): void
    {
        Schema::create('audit_test', function ($t): void {
            $t->id();
            $t->auditColumns();
        });

        self::assertTrue(Schema::hasColumn('audit_test', 'created_by'));
        self::assertTrue(Schema::hasColumn('audit_test', 'updated_by'));
        self::assertTrue(Schema::hasColumn('audit_test', 'deleted_by'));
    }

    public function test_audit_columns_can_skip_deleted_by(): void
    {
        Schema::create('audit_test_no_delete', function ($t): void {
            $t->id();
            $t->auditColumns(includeDeletedBy: false);
        });

        self::assertTrue(Schema::hasColumn('audit_test_no_delete', 'created_by'));
        self::assertTrue(Schema::hasColumn('audit_test_no_delete', 'updated_by'));
        self::assertFalse(Schema::hasColumn('audit_test_no_delete', 'deleted_by'));
    }

    public function test_soft_deletes_with_undo_adds_two_columns(): void
    {
        Schema::create('sdwu_test', function ($t): void {
            $t->id();
            $t->softDeletesWithUndo();
        });

        self::assertTrue(Schema::hasColumn('sdwu_test', 'deleted_at'));
        self::assertTrue(Schema::hasColumn('sdwu_test', 'restored_at'));
    }

    public function test_soft_deletes_with_undo_supports_custom_column_names(): void
    {
        Schema::create('sdwu_custom', function ($t): void {
            $t->id();
            $t->softDeletesWithUndo(deletedColumn: 'archived_at', restoredColumn: 'unarchived_at');
        });

        self::assertTrue(Schema::hasColumn('sdwu_custom', 'archived_at'));
        self::assertTrue(Schema::hasColumn('sdwu_custom', 'unarchived_at'));
    }
}
