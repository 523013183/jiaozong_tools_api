<?php

namespace App\User\Providers;


use App\Base\Providers\AppServiceProvider;
use App\User\Facades\UserFacade;
use App\User\Services\UserService;

class UserServiceProvider extends AppServiceProvider
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
        $this->registerUser();
    }

    protected function registerUser()
    {
        $this->app->bind(UserFacade::class, function () {
            return app()->make(UserService::class);
        });
    }

}
