<?php

namespace App\Api\Controllers;

use App\Api\Services\ApiService;
use App\Base\Controllers\ApiBaseController;
use App\Base\Exceptions\ApiException;
use App\Base\Facades\DictJobTitleFacade;
use App\Base\Facades\DictLocationFacade;
use Illuminate\Http\Request;

class ApiController extends ApiBaseController
{
    private $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * @param string username 用户名(手机或者邮箱) required
     * @param string password 密码 required
     * @param string phone 手机号（手机登录时必传）
     * @param string valid_code 验证码（手机登录时必传）
     * @param string login_type 登录类型(0-帐号密码登录,1-短信登录,2-微信登录,3-QQ登录) required
     * @param string login_portal 登录入口(0-web,1-app) required
     * @param string long_login 持久化登录
     * @param string invite_code 邀请码
     * @param array referrer 来源
     * @return array
     * @throws
     * @successExample
     * {"ret":0,"msg":"success.","data":{"api_token":"c3ab2a90d4a80853b0b2a4469258f3ac","expire":604800,"nick_name":"","user_name":""}}
     * @api post /api/user/login 客户端用户登录
     * @group 客户端 用户接口
     */
    public function login(Request $request)
    {
        $params = $request->only(['username', 'password', 'phone', 'valid_code', 'login_type', 'login_portal', 'long_login', 'invite_code', 'country_code', 'referrer']);
        $ret = $this->apiService->login($params);
        return $ret;
    }

    /**
     * @api post /api/user/wechat-login
     * @param Request $request
     * @return array|int|null
     * @throws \Illuminate\Validation\ValidationException
     */
    public function miniProgLogin(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);
        $code = $request->input('code');
        $config = config('wechat.exporeg');
        return $this->apiService->wechatOauth($config, $code);
    }

    /** 
     * 小程序登录（手机号快速登录）
     * @api post /api/user/wechat-phone-quick-login
     */
    public function quickPhoneLogin(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
            'encrypted_data' => 'required',
            'iv' => 'required',
            'open_id' => 'required',
        ]);
        $miniProgram = $request->input('mini_program');
        $config = config('wechat.exporeg');
        $params = $request->only('code', 'encrypted_data', 'iv', 'open_id', 'union_id', 'invite_code');
        return $this->apiService->wechatQuickPhoneLogin($config, $params);
    }

    /**
     * 获取手机验证码
     * @api post /api/user/get-phone-code
     */
    public function getPhoneCode(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required',
            'country_code' => 'required',
        ], [
            'phone.required' => trans('user.user_phone_empty'),
            'country_code.required' => trans('user.user_country_code_empty'),
        ]);
        $phone = $request->input('phone', '');
        $countryCode = $request->input('country_code', '86');
        $ret = $this->apiService->getPhoneCode($phone, $countryCode);
        return $ret;
    }

    /**
     * @param file files 上传文件
     * @param int private 是否私有(1=是，0=否)
     * @param string title 标题
     * @param string alt 描述
     * @param string url 路径
     * @param string filename 文件名
     * @param string size 大小
     * @param string ext 后缀
     * @param string pic 首图
     * @param string resolution 分辨率
     * @param string mime_type 文件类型
     * @param string etag 文件的etag
     * @successExample
     * {
     * "ret": 0,
     * "msg": "success.",
     * "data":[{
     * "files": ["\/22222.jpg"],
     * }]}
     * @return \App\Base\Models\BaseModel
     * @api post /api/attachment/upload 文件上传
     * @group 客户端 公共接口
     * @header string api-token 用户授权token
     */
    public function upload(Request $request)
    {
        // 直接上传文件 或者 前端上传obs后提交url
        if (empty($request->input('file')) && empty($request->input('url'))) {
            throw new ApiException('common.file_empty', '上传文件不能为空');
        }
        $ret = $this->apiService->customerUpload($request);
        return $ret;
    }

    /** 
     * 获取用户基本信息
     * @api post /api/user/base-account
     */
    public function baseAccount(Request $request)
    {
        $ret = $this->apiService->accountInfo();
        return $ret;
    }

    /**
     * @return array
     * @throws
     * @successExample
     * {"ret":0,"msg":"success.","data":{}}
     * @api post /api/user/logout 客户端用户登出
     * @group 客户端 用户接口
     */
    public function logout(Request $request)
    {
        $token = $request->header('api-token');
        $ret = $this->apiService->logout($token);
        return $ret;
    }
}
