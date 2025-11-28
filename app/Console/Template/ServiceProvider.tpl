<?php


namespace App\{module}\Providers;


use App\Base\Providers\AppServiceProvider;
use App\{module}\Services\{action}Service;
use App\{module}\Models\{action}Model;
use App\{module}\Facades\{action}Facade;

class {action}ServiceProvider extends AppServiceProvider
{
    public function register()
    {
        $this->app->bind({action}Service::class,function(){
            return new {action}Service(new {action}Model);
        });
        $this->app->bind({action}Facade::class,function(){
            return app()->make({action}Service::class);
        });
    }
}