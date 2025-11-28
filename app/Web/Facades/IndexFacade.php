<?php

namespace App\Web\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Web\Services\IndexService createRoutes(string $module, array $infoIds) 创建内容路由
 * Class IndexFacade
 * @package App\Web\Facades
 */
class IndexFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
