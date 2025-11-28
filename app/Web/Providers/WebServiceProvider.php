<?php
/**
 * Created by PhpStorm.
 * User: fangx
 * Date: 2021/7/7
 * Time: 10:32
 */

namespace App\Web\Providers;

use App\Base\Providers\AppServiceProvider;
use App\Web\Facades\IndexFacade;
use App\Web\Services\IndexService;
use Illuminate\Support\Facades\Blade;

class WebServiceProvider extends AppServiceProvider
{
    public function boot()
    {
        $this->app->bind(IndexFacade::class,function(){
            return app()->make(IndexService::class);
        });
        $this->registerComponent();
        parent::boot();
        /*\DB::listen(function ($query) {
            $sql = array_reduce($query->bindings, function($sql, $binding) {
                return preg_replace('/\?/', is_numeric($binding) ? $binding : sprintf("'%s'", $binding), $sql, 1);
            }, $query->sql);

            \Log::info($sql);
        });*/
    }

    /**
     * 注册组件
     */
    private function registerComponent()
    {
    }
}
