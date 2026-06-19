<?php

declare(strict_types=1);

namespace Simtabi\Laranail\DatabaseTools\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\DatabaseTools\DatabaseTools;

class DatabaseToolsFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DatabaseTools::class;
    }
}
