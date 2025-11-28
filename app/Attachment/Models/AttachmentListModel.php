<?php

namespace App\Attachment\Models;

use App\Base\Models\BaseModel;
use App\Base\Models\ApiSoftDeletes;

class AttachmentListModel extends BaseModel
{
    use ApiSoftDeletes;
    protected $table = 'attachment_list';

    /**
     * 插入时间字段
     */
    const CREATED_AT = 'create_time';

    /**
     * 文件类型-其他
     */
    const TYPE_OTHER = 0;

    /**
     * 文件类型-图片
     */
    const TYPE_PIC = 1;

    /**
     * 文件类型-视频
     */
    const TYPE_VIDEO = 2;

    /**
     * 文件类型-音乐
     */
    const TYPE_MUSIC = 3;

    /**
     * 获取用户已使用的存储空间大小
     */
    public function getUserBucketSize(int $userId)
    {
        $size = $this->db("a")->where("user_id", "=", $userId)
            ->where("status", "=", self::STATUS_ENABLED)
            ->sum("size");
        return $size;
    }
}
