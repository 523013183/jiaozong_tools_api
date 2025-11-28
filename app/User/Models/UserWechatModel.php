<?php

namespace App\User\Models;

use App\Base\Models\BaseModel;
use App\Base\Models\ApiSoftDeletes;

class UserWechatModel extends BaseModel
{
    use ApiSoftDeletes;
    protected $table = 'user_wechat';
}
