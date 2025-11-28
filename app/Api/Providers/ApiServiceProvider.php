<?php
namespace App\Api\Providers;

use App\Api\Facades\ApiFacade;
use App\Api\Facades\BlogFacade;
use App\Api\Services\ApiService;
use App\Api\Services\BlogService;
use App\Base\Facades\PinYinFacade;
use App\Base\Providers\AppServiceProvider;
use App\Base\Services\PinYinService;

class ApiServiceProvider extends AppServiceProvider
{
    public function boot()
    {
        parent::boot();
    }

    /**
     * 注册绑定门面
     */
    public function register()
    {
        //注册Api
        $this->registerApi();
    }

    public function registerApi(){

        $this->app->bind(ApiFacade::class, function () {
            return app()->make(ApiService::class);
        });
        $this->app->bind(PinYinFacade::class, function () {
            return app()->make(PinYinService::class);
        });
        $this->app->bind(BlogFacade::class, function () {
            return app()->make(BlogService::class);
        });
    }
}
