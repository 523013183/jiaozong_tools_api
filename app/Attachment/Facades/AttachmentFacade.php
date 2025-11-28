<?php

namespace App\Attachment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Attachment\Services\AttachmentService getFileByIds($ids,$field='') 获取附件数据
 * Class AttachmentFacade
 * @package App\Attachment\Facades
 */
class AttachmentFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
