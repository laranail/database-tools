<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DatabaseTools\Tests\Unit;

use Simtabi\Laranail\DatabaseTools\DatabaseTools;
use Simtabi\Laranail\DatabaseTools\Tests\TestCase;

final class DatabaseToolsFacadeTest extends TestCase
{
    public function test_without_foreign_key_checks_returns_callback_result(): void
    {
        $result = DatabaseTools::withoutForeignKeyChecks(fn (): string => 'ok');

        self::assertSame('ok', $result);
    }
}
