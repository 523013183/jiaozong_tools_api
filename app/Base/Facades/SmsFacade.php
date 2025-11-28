<?php
namespace App\Base\Facades;


use Illuminate\Support\Facades\Facade;

class SmsFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }

}
