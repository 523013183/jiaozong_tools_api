<?php

namespace App\User\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class UserService
 * @package App\User\Facades
 */
class UserFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
