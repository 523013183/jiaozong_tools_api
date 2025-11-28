<?php

namespace App\Api\Controllers;

use App\Base\Controllers\ApiBaseController;
use App\User\Facades\UserFacade;
use App\User\Services\UserService;
use Illuminate\Http\Request;

class UserController extends ApiBaseController
{
    private $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * @api post /api/user/update-base-user-info 更改用户基本信息
     * @group 客户端 用户接口
     */
    public function updateBaseUserInfo(Request $request)
    {
        $params = $request->only(['avatar', 'nick_name', 'real_name']);
        $params['id'] = $this->getAuthUserId();
        $ret = UserFacade::saveInfo($params);
        return $ret;
    }
}
