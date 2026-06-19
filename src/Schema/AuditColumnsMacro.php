<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DatabaseTools\Schema;

use Illuminate\Database\Schema\Blueprint;

/**
 * Schema macro: $table->auditColumns(...).
 *
 * Adds `created_by`, `updated_by`, `deleted_by` foreign-id-style columns
 * (nullable, indexed). Pairs with AuditObserver.
 *
 * The macro is registered in DatabaseToolsServiceProvider::boot().
 *
 * Example:
 * ```
 * Schema::create('orders', function (Blueprint $t) {
 *     $t->id();
 *     $t->string('name');
 *     $t->auditColumns();
 *     $t->timestamps();
 *     $t->softDeletes();
 * });
 * ```
 *
 * Optional named-arguments:
 *   - foreignKey   — type of FK column. Default 'foreignId' (BIGINT).
 *                    Pass 'foreignUuid' or 'foreignUlid' for non-int PKs.
 *   - includeDeletedBy — set false to skip the deleted_by column when
 *                        the table doesn't use soft-deletes.
 *   - userTable    — referenced table name. Default 'users'. (No actual
 *                    FK constraint added by default; pass true via
 *                    constrained=true if you want one.)
 */
final class AuditColumnsMacro
{
    public static function register(): void
    {
        if (Blueprint::hasMacro('auditColumns')) {
            return;
        }

        Blueprint::macro('auditColumns', function (
            string $foreignKey = 'foreignId',
            bool $includeDeletedBy = true,
            string $userTable = 'users',
            bool $constrained = false,
        ): Blueprint {
            /** @var Blueprint $this */
            $columns = ['created_by', 'updated_by'];
            if ($includeDeletedBy) {
                $columns[] = 'deleted_by';
            }

            foreach ($columns as $col) {
                $column = $this->{$foreignKey}($col)->nullable()->index();
                if ($constrained) {
                    $column->constrained($userTable)->nullOnDelete();
                }
            }

            return $this;
        });
    }
}
