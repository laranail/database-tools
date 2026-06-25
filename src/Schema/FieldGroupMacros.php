<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DatabaseTools\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable column-group macros on the schema Blueprint — convenience helpers for
 * the column patterns that recur across migrations (timestamps + soft deletes,
 * audit user ids, publishing/SEO/location/price/activation/expiry fields, slugs,
 * UUID primary keys, nullable morphs) plus safe conditional drops.
 *
 * Registered in {@see DatabaseToolsServiceProvider::boot()}.
 *
 * ```php
 * Schema::create('posts', function (Blueprint $t) {
 *     $t->id();
 *     $t->addSlugField();
 *     $t->addPublishingFields();
 *     $t->addMetaFields();
 *     $t->addCommonFields();   // timestamps() + softDeletes()
 * });
 * ```
 */
final class FieldGroupMacros
{
    public static function register(): void
    {
        Blueprint::macro('addCommonFields', function (): void {
            /** @var Blueprint $this */
            $this->timestamps();
            $this->softDeletes();
        });

        Blueprint::macro('addUserFields', function (): void {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('created_by')->nullable();
            $this->unsignedBigInteger('updated_by')->nullable();
            $this->unsignedBigInteger('deleted_by')->nullable();
        });

        Blueprint::macro('addPublishingFields', function (): void {
            /** @var Blueprint $this */
            $this->boolean('is_published')->default(false);
            $this->timestamp('published_at')->nullable();
        });

        Blueprint::macro('addStatusField', function (string $default = 'active'): void {
            /** @var Blueprint $this */
            $this->string('status')->default($default)->index();
        });

        Blueprint::macro('addSortingField', function (int $default = 0): void {
            /** @var Blueprint $this */
            $this->integer('sort_order')->default($default)->index();
        });

        Blueprint::macro('addSlugField', function (bool $nullable = false) {
            /** @var Blueprint $this */
            $column = $this->string('slug')->unique();

            if ($nullable) {
                $column->nullable();
            }

            return $column;
        });

        Blueprint::macro('dropForeignIfExists', function (string $index): void {
            /** @var Blueprint $this */
            if (Schema::hasColumn($this->getTable(), $index)) {
                $this->dropForeign([$index]);
            }
        });

        Blueprint::macro('dropColumnIfExists', function (string|array $columns): void {
            /** @var Blueprint $this */
            $columns = is_array($columns) ? $columns : [$columns];
            $table = $this->getTable();

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $this->dropColumn($column);
                }
            }
        });

        Blueprint::macro('addMetaFields', function (): void {
            /** @var Blueprint $this */
            $this->string('meta_title')->nullable();
            $this->text('meta_description')->nullable();
            $this->text('meta_keywords')->nullable();
        });

        Blueprint::macro('addSeoFields', function (): void {
            /** @var Blueprint $this */
            $this->addMetaFields();
        });

        Blueprint::macro('addLocationFields', function (): void {
            /** @var Blueprint $this */
            $this->decimal('latitude', 10, 8)->nullable();
            $this->decimal('longitude', 11, 8)->nullable();
        });

        Blueprint::macro('addImageFields', function (string $prefix = ''): void {
            /** @var Blueprint $this */
            $prefix = $prefix !== '' ? $prefix.'_' : '';

            $this->string("{$prefix}image")->nullable();
            $this->string("{$prefix}image_alt")->nullable();
            $this->string("{$prefix}image_title")->nullable();
        });

        Blueprint::macro('addPriceFields', function (): void {
            /** @var Blueprint $this */
            $this->decimal('price', 10, 2)->default(0);
            $this->decimal('sale_price', 10, 2)->nullable();
            $this->string('currency', 3)->default('USD');
        });

        Blueprint::macro('addActivationFields', function (): void {
            /** @var Blueprint $this */
            $this->boolean('is_active')->default(true)->index();
            $this->timestamp('activated_at')->nullable();
            $this->timestamp('deactivated_at')->nullable();
        });

        Blueprint::macro('addExpiryFields', function (): void {
            /** @var Blueprint $this */
            $this->timestamp('starts_at')->nullable();
            $this->timestamp('expires_at')->nullable();
        });

        Blueprint::macro('addUuidPrimaryKey', function (string $column = 'id'): void {
            /** @var Blueprint $this */
            $this->uuid($column)->primary();
        });

        Blueprint::macro('addNullableMorphs', function (string $name, ?string $indexName = null): void {
            /** @var Blueprint $this */
            $this->string("{$name}_type")->nullable();
            $this->unsignedBigInteger("{$name}_id")->nullable();
            $this->index(["{$name}_type", "{$name}_id"], $indexName);
        });
    }
}
