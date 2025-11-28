<?php
namespace App\Base\Models;


class ComOssETagModel extends BaseModel
{
    protected $table = 'com_oss_etag';

    /**
     * 插入时间字段
     */
    const CREATED_AT = 'create_time';

    /**
     * 更新时间字段
     */
    const UPDATED_AT = null;

    /**
     * 状态字段
     */
    const DELETED_AT = null;

}
