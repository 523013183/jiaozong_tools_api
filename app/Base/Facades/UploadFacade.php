<?php
namespace App\Base\Facades;


use Illuminate\Support\Facades\Facade;
class UploadFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }

}
