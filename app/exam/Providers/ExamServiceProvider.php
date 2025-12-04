<?php
namespace App\Exam\Providers;

use App\Base\Providers\AppServiceProvider;
use App\Exam\Facades\ExamFacade;
use App\Exam\Services\ExamService;

class ExamServiceProvider extends AppServiceProvider
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

        $this->app->bind(ExamFacade::class, function () {
            return app()->make(ExamService::class);
        });
    }
}
