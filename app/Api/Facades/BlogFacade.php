<?php

namespace App\Api\Facades;

use Illuminate\Support\Facades\Facade;

class BlogFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
