<?php
namespace App\Base\Facades;


use Illuminate\Support\Facades\Facade;

class MailFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }

}
