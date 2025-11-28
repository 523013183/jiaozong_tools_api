<?php


namespace App\Doc\Providers;

use App\Base\Providers\AppServiceProvider;
use App\Doc\Services\DocService;

class DocServiceProvider extends AppServiceProvider
{
    public function register()
    {
        $this->app->bind(DocService::class, function () {
            return new DocService();
        });
    }
}
