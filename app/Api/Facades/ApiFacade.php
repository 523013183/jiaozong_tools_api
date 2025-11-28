<?php

namespace App\Api\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Api\Services\ApiService getClientUserInfoById($userId) 根据id获取客户端用户详细数据
 * Class ApiFacade
 * @package App\Api\Facades
 */
class ApiFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
