<?php

namespace App\Base\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * @method static \App\Basic\Services\DictLocationService locationById($id, $fields = '*') 通过id获取地址
 * Class DictLocationFacade
 * @package App\Basic\Facades
 */
class DictLocationFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
