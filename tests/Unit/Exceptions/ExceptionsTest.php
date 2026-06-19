<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DatabaseTools\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Simtabi\Laranail\DatabaseTools\Exceptions\DatabaseToolsException;
use Simtabi\Laranail\DatabaseTools\Exceptions\MissingUuidColumnException;
use Simtabi\Laranail\DatabaseTools\Exceptions\UuidException;

final class ExceptionsTest extends TestCase
{
    public function test_all_exceptions_extend_the_package_base(): void
    {
        self::assertInstanceOf(DatabaseToolsException::class, UuidException::missingValue('id'));
        self::assertInstanceOf(DatabaseToolsException::class, new MissingUuidColumnException('missing'));
    }

    public function test_uuid_exception_factories_carry_context(): void
    {
        $e = UuidException::invalidFormat('not-a-uuid');

        self::assertSame(1002, $e->getCode());
        self::assertSame('not-a-uuid', $e->getContext()['value']);
    }

    public function test_uuid_exception_missing_value_carries_column(): void
    {
        $e = UuidException::missingValue('uuid');

        self::assertSame(1001, $e->getCode());
        self::assertSame('uuid', $e->getContext()['column']);
    }
}
