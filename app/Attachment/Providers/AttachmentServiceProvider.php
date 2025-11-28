<?php

namespace App\Attachment\Providers;

use App\Attachment\Facades\AttachmentFacade;
use App\Attachment\Services\AttachmentService;
use App\Base\Providers\AppServiceProvider;

class AttachmentServiceProvider extends AppServiceProvider
{
    /**
     * 注册绑定门面
     */
    public function register()
    {
        //附件列表
        $this->app->bind(AttachmentFacade::class, function () {
            return app()->make(AttachmentService::class);
        });
    }
}
