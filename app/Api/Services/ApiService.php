<?php


namespace App\Api\Services;

use App\Attachment\Facades\AttachmentFacade;
use App\Base\Exceptions\ApiException;
use App\Base\Facades\ApiSysUserFacade;
use App\Base\Services\ApiBaseService;
use App\User\Facades\UserFacade;
use App\User\Models\UserModel;
use App\User\Models\UserWechatModel;
use App\User\Services\WechatService;
use Illuminate\Support\Facades\Cache;

class ApiService extends ApiBaseService
{

    protected $cache = true;

    protected $cacheBucket = 'ExpoRegApi:';

    protected $tokenBucket = 'ExpoRegApiToken:';
    private $userWechatModel;

    public function __construct(UserModel $userModel, UserWechatModel $userWechatModel)
    {
        $this->model = $userModel;
        $this->userWechatModel = $userWechatModel;
    }

    /**
     * login_type 登录类型 0 帐号密码登录 1 短信登录 2 微信登录 3 QQ登录
     * login_portal 登录入口 0 web 1 app
     * */
    public function login($params)
    {
        $loginType = empty($params['login_type']) ? 0 : $params['login_type'];
        $loginPortal = empty($params['login_portal']) ? 0 : $params['login_portal'];
        $longLogin = empty($params['long_login']) ? 0 : $params['long_login'];
        $httpAcceptLanguage = empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? 'zh-cn' : strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $deviceLanguage = empty($params['device_language']) ? $httpAcceptLanguage : strtolower($params['device_language']);
        $inviteCode = empty($params['invite_code']) ? '' : $params['invite_code'];
        $referrer = empty($params['referrer']) ? '' : $params['referrer'];

        $ret = [];
        switch ($loginType) {
            case 0:
                $userName = empty($params['username']) ? 0 : $params['username'];
                $password = empty($params['password']) ? 0 : $params['password'];
                if (empty($userName)) {
                    throw new ApiException('user.user_empty', '帐号不能为空');
                }
                if (empty($password)) {
                    throw new ApiException('user.pwd_empty', '密码不能为空');
                }
                $ret = $this->passwordLogin($userName, $password, $loginPortal, $deviceLanguage,$longLogin, $inviteCode, $referrer);
                break;
            case 1:
                $phone = empty($params['phone']) ? '' : $params['phone'];
                $validCode = empty($params['valid_code']) ? '' : $params['valid_code'];
                $countryCode = empty($params['country_code']) ? 86 : $params['country_code'];
                if (empty($phone)) {
                    throw new ApiException('user.user_phone_empty', '手机号不能为空');
                }
                if (empty($validCode)) {
                    throw new ApiException('user.user_valid_code_empty', '验证码不能为空');
                }
                $ret = $this->phoneLogin($phone, $validCode, $countryCode,$loginPortal, $deviceLanguage,$longLogin, $inviteCode, $referrer);
                break;
        }
        return $ret;
    }

    /**
     * @param string $userName 用户名
     * @param string $password 密码
     * @param integer $loginPortal 登录入口 0 web 1 app
     * @param array $referrer 来源地址
     * @return mixed
     * */
    private function passwordLogin($userName, $password, $loginPortal, $deviceLanguage = 'zh-cn',$longLogin=0)
    {
        //请求登录
        $loginUser = ApiSysUserFacade::pwdLogin($userName, $password);
        $sysApiToken = $loginUser['api_token'];
        $where = [];
        $where['a.id'] = $loginUser['user_id'];
        $userModel = UserFacade::getModel();
        $user = $userModel->newInstance()->alias('a')
            ->buildQuery($where)
            ->where('a.status','<',2)
            ->first();
        $isUpdateUser = false;
        if ($user['status'] == 1) {
            throw new ApiException('common.user_disabled', '用户被禁用');
        }
        $id = $loginUser['user_id'];
        $randomStr = getRandomStr('API_TOKEN');
        $apiToken = md5($id. $randomStr);
        $ret = [];
        $ret['api_token'] = $apiToken;
        $userInfo = UserFacade::getDetailUserInfoById($id);
        $userInfo['device_language'] = $deviceLanguage;
        $userInfo['long_login'] = $longLogin;
        $userInfo['sys_api_token'] = $sysApiToken;
        $userInfo['is_update_user']=$isUpdateUser;
        if (config('app.login_singleton')) {
            if ($loginPortal == 1) {
                //如果是app登录 在用户已经登录的情况下  拿到上次登录的token 重新更新token数据
                $key = $userInfo['id'] . 'jwt_api_token';
                $insideApiToken = Cache::get($key);
                if ($insideApiToken) {
                    $apiToken = $insideApiToken;
                    $ret['api_token'] = $apiToken;
                }
            }
        }

        //登录的时候只记录简单的基础信息
        $this->updateUserInfoCache($apiToken,$userInfo);
        return $ret;
    }

    /**
     * @param string $userName 用户名
     * @param string $password 密码
     * @param integer $loginPortal 登录入口 0 web 1 app
     * @return mixed
     * */
    private function phoneLogin($phone, $validCode,$countryCode=86, $loginPortal, $deviceLanguage = 'zh-cn',$longLogin=0, $inviteCode = '', $referrer = [])
    {
        //判断账号是否存在
        $checkRet= ApiSysUserFacade::checkSysUserPhoneUnique($phone,false);
        if(!empty($checkRet['code'])&&$checkRet['code']===10012) {
            //手机号已被注册
            //请求登录
            $loginUser = ApiSysUserFacade::phoneLogin($phone, $validCode,$countryCode);
        } else {
            //不存在则注册并登录
            $loginUser = ApiSysUserFacade::registerSysUser([
                "phone" => $phone,
                "valid_code" => $validCode,
                "auto_login" => 1,
                "country_code" => $countryCode,
                "nick_name" => transL('user.customer', '用户') . rand(10000, 999999)
            ]);
        }
        $sysApiToken = $loginUser['api_token'];
        if(!empty($loginUser['p_user_id'])){
            throw new ApiException('common.app_disabled_sub_account', '请使用主帐号登录！');
        }
        if(!empty($loginUser['code'])&&$loginUser['code']==10035){
            ApiSysUserFacade::appBind($sysApiToken);
        }
        $where = [];
        $where['a.id'] = $loginUser['user_id'];
        $userModel = UserFacade::getModel();
        $user = $userModel->newInstance()->alias('a')
            ->buildQuery($where)
            ->where('a.status','<',2)
            ->first();
        $isUpdateUser=false;
        //上级
        $pid = 0;
        if (!empty($inviteCode)) {
            $pid = inviteCodeDecode($inviteCode);
        }
        if(empty($user)){
            //新增用户
            $sysUserInfo= ApiSysUserFacade::getSysUserInfo($sysApiToken);
            $userData=[];
            $userData['id'] =  $sysUserInfo['id'];
            $userData['user_name'] = $sysUserInfo['user_name'];
            $userData['nick_name'] = $sysUserInfo['nick_name'];
            $userData['real_name'] = $sysUserInfo['real_name'];
            $userData['phone'] = $sysUserInfo['phone'];
            $userData['email'] = $sysUserInfo['email'];
            $userData['pid'] = $pid;
            $userData['country_code'] = empty($sysUserInfo['country_code']) ? '86' : $sysUserInfo['country_code'];
            $userData['referrer'] = $referrer;
            $id = UserFacade::sysAddUser($userData);
            if(empty($id)){
                throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
            }
            $isUpdateUser=true;
        }else{
            if ($user['status'] == 1) {
                throw new ApiException('common.user_disabled', '用户被禁用');
            }
            $id =$user['id'];
            if (empty($user['pid']) && !empty($pid)) {
                // 绑定上级
                UserFacade::update($id, [
                    "pid" => $pid
                ]);
            }
        }
        $randomStr = getRandomStr('API_TOKEN');
        $apiToken = md5($id . $randomStr);
        $ret = [];
        $ret['api_token'] = $apiToken;
        $userInfo = UserFacade::getDetailUserInfoById($id);
        $userInfo['device_language'] = $deviceLanguage;
        $userInfo['long_login'] = $longLogin;
        $userInfo['sys_api_token'] = $sysApiToken;
        $userInfo['is_update_user']=$isUpdateUser;
        if (config('app.login_singleton')) {
            if ($loginPortal == 1) {
                //如果是app登录 在用户已经登录的情况下  拿到上次登录的token 重新更新token数据
                $key = $userInfo['id'] . 'jwt_api_token';
                $insideApiToken = Cache::get($key);
                if ($insideApiToken) {
                    $apiToken = $insideApiToken;
                    $ret['api_token'] = $apiToken;
                }
            }
        }
        //登录的时候只记录简单的基础信息
        $this->updateUserInfoCache($apiToken,$userInfo);
        return $ret;
    }

    /**
     * 更新用户缓存
     * */
    public function updateUserInfoCache($apiToken = '', $user = [])
    {
        if (empty($user)) {
            $userId = $this->getAuthUserId();
            $newUser = UserFacade::getDetailUserInfoById($userId);
            $newUser['device_language']=$this->getAuthDeviceLanguage();
            $newUser['long_login']=$this->getAuthlongLogin();
            $newUser['sys_api_token']=$this->getAuthSysApiToken();
            $newUser['is_update_user']=$this->getAuthIsUpdateUser();
        } else {
            $newUser = $user;
            $userId = $user['id'];
            if(empty($user['is_detail'])){
                $newUser['industry_id'] =!empty($newUser['industry_id'])?$newUser['industry_id']:[];
                $newUser['permission'] = !empty($newUser['permission'])?$newUser['permission']:[];
                $newUser['is_detail']=0;
            }
            $longLogin= isset($newUser['long_login'])? $newUser['long_login']:0;
            $device_language=isset($newUser['device_language'])?$newUser['device_language']: 'zh-cn';
            $sysApiToken=isset($newUser['sys_api_token'])?$newUser['sys_api_token']: '';
            $newUser['device_language'] =$device_language;
            $newUser['long_login'] =$longLogin;
            $newUser['sys_api_token']=$sysApiToken;
            $newUser['is_update_user']=empty($newUser['is_update_user'])?false:$newUser['is_update_user'];
        }
        if (empty($apiToken)) {
            if (config('app.login_singleton')) {
                $key = $userId . 'jwt_api_token';
                $apiToken = Cache::get($key);
            } else {
                $apiToken = $this->getAuthApiToken();
            }
        }
        if (empty($apiToken) || empty($newUser)) {
            return [];
        }
        $apiTokenExpireTime=config('cache.api_token_expire_time');
        $longLogin = 1; //写死长登录
        if($longLogin){
            $apiTokenExpireTime=config('cache.api_token_long_expire_time');
        }
        if(empty($newUser['sys_api_token'])){
            $this->forgotToken($apiToken);
            throw new ApiException('common.auth_fail', '认证失败');
        }
        $nowTime = time();
        $newUser['api_token'] = $apiToken;
        $newUser['heart_beat_time']=empty($newUser['heart_beat_time'])?$nowTime:$newUser['heart_beat_time'];
        $this->setToken($apiToken, $newUser,$apiTokenExpireTime);
        $this->setCacheToken($newUser['id'], $apiToken,$apiTokenExpireTime);
        return $newUser;
    }

    /**
     * 忘记token
     * */
    public function forgotToken($token)
    {
        return Cache::pull($this->getTokenKey($token));
    }

    /**
     * 获取token缓存key
     * @param $apiToken
     * @return string
     */
    private function getTokenKey($apiToken)
    {
        return $this->tokenBucket . $apiToken;
    }

    /**
     * 设置当前用户token
     * @param $userId
     * @param $apiToken
     */
    public function setCacheToken($userId, $apiToken,$expireTime=0)
    {
        if(empty($expireTime)){
            $expireTime=config('cache.api_token_expire_time');
        }
        $key = $userId . 'jwt_api_token';
        Cache::put($key, $apiToken, $expireTime);
    }

    /**
     * 设置api_token缓存时间
     * @param $apiToken
     * @param $user
     */
    public function setToken($apiToken, $user,$expireTime=0)
    {
        if(empty($expireTime)){
            $expireTime=config('cache.api_token_expire_time');
        }
        Cache::put($this->getTokenKey($apiToken), $user,$expireTime );
        //设置用户在线
        // Cache::put('user.active.' . $user['id'], $user['id'], $expireTime);
    }

    /**
     * 小程序登录
     * @param $params
     * @return array
     */
    public function wechatOauth($config, $code)
    {
        $wechatService = new WechatService($config);
        $session = $wechatService->getSessionInfo($code);
        if (!empty($session['openid'])) {
            Cache::put($this->getWechatSessionKey($session['openid']), $session['session_key'], config('cache.token'));
            return [
                'openid'=>$session['openid'],
                'unionid'=>$session['unionid']??''
            ];
        }
        return '';
    }

    public function getWechatSessionKey($openId)
    {
        return 'wechat:code:sessionkey:'.$openId;
    }

    /**
     * 根据token获取用户
     * @param $token
     * @return UserModel | mixed
     */
    public function findUserInfoByApiToken($apiToken)
    {
        return Cache::get($this->getTokenKey($apiToken));
    }

    /**
     * 手机号快速登录
     * @param $config
     * @param $params
     * @return mixed
     * @throws ApiException
     */
    public function wechatQuickPhoneLogin($config, $params)
    {
        $code = $params['code'];
        $encryptedData = $params['encrypted_data'];
        $iv = $params['iv'];
        $openId = $params['open_id'];
        $unionId = $params['union_id']??'';
        $inviteCode = $params['invite_code'] ?? ''; // 邀请码
        $sessionKey = Cache::get($this->getWechatSessionKey($unionId ?: $openId));
        $userInfo = null;
        $phoneInfo = [];
        $phone = null;
        $countryCode = null;
        if (!empty($sessionKey)) {
            $wechatService = new WechatService($config);
            $decryptData = $wechatService->decryptData($sessionKey, $iv, $encryptedData);
            if (isset($decryptData['purePhoneNumber'])) {
                if (empty($decryptData['purePhoneNumber'])) {
                    $phoneInfo = $wechatService->getUserPhoneNumber($code);
                    if (!empty($phoneInfo['phone_info'])) {
                        $phone = $phoneInfo['phone_info']['purePhoneNumber'];
                        $countryCode = $phoneInfo['phone_info']['countryCode'];
                    }
                } else {
                    $phone = $decryptData['purePhoneNumber'];
                    $countryCode = $decryptData['countryCode'];
                }
                if ($phone) {
                    $authInfo = [];
                    $authInfo['encrypt_data'] = $encryptedData;
                    $authInfo['decrypt_data'] = $decryptData;
                    $authInfo['phone_info'] = $phoneInfo;
                    $userInfo = $this->saveWechatUser($phone, $config['open_id_key'], $openId, $unionId, $authInfo, $inviteCode);
                }
            }
        }
        if ($userInfo) {
            $loginUser = ApiSysUserFacade::phoneLogin($phone, '123456', $countryCode, true);
            $where['a.id'] = $loginUser['user_id'];
            $userModel = UserFacade::getModel();
            $user = $userModel->newInstance()->alias('a')
                ->buildQuery($where)
                ->where('a.status','<',2)
                ->first();
            if ($user) {
                if ($user['status'] == 1) {
                    throw new ApiException('common.user_disabled', '用户被禁用');
                }
                $id =$user['id'];
                $sysApiToken = $loginUser['api_token'];
                $randomStr = getRandomStr('API_TOKEN');
                $apiToken = md5($id . $randomStr);
                $ret = [];
                $ret['api_token'] = $apiToken;
                $ret['sys_api_token'] = $sysApiToken;
                $userInfo = UserFacade::getDetailUserInfoById($id);
                $userInfo['device_language'] = 'zh-cn';
                $userInfo['long_login'] = 1;
                $userInfo['sys_api_token'] = $sysApiToken;
                $userInfo['is_update_user'] = false;
                if (config('app.login_singleton')) {
                    //如果是app登录 在用户已经登录的情况下  拿到上次登录的token 重新更新token数据
                    $key = $userInfo['id'] . 'jwt_api_token';
                    $insideApiToken = Cache::get($key);
                    if ($insideApiToken) {
                        $apiToken = $insideApiToken;
                        $ret['api_token'] = $apiToken;
                    }
                }
                //登录的时候只记录简单的基础信息
                $this->updateUserInfoCache($apiToken,$userInfo);
                return $ret;
            }
        }
        return '';
    }

    /**
     * 微信注册接口
     * @param $phone
     * @param $openId
     * @param $authInfo
     * @return array|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object
     */
    public function saveWechatUser($phone, $openIdKey, $openId, $unionId, $authInfo = null, $inviteCode = '')
    {
        $phoneUserInfo = $this->model->getUserByPhone($phone);
        $openUserInfo = $this->model->getUserByOpenId($openIdKey, $openId);
        if(!empty($openUserInfo) && $openUserInfo['phone'] != $phone) {
            $this->userWechatModel->updateData(
                [$openIdKey => '', 'union_id' => ''],
                ['user_id'=> $openUserInfo['id']]
            );
        }
        $userData = [];
        $userData[$openIdKey] = $openId;
        if ($unionId) {
            $userData['union_id'] = $unionId;
        }
//        if(isset($authInfo)) {
//            $userData['auth_info'] = json_encode($authInfo, JSON_UNESCAPED_UNICODE);
//        }
        $userId = null;
        if (!empty($phoneUserInfo)) {
            $this->userWechatModel->updateData($userData, [
                'id'=>$phoneUserInfo['id']
            ]);
            $userId = $phoneUserInfo['id'];
        } else {
            // 注册用户
            $user = UserFacade::createUser([
                'phone'=>$phone,
                'email'=>'',
                'name'=>$phone,
                'invite_code'=>$inviteCode
            ]);
            $userId = $user['user_id']??'';
        }
        return $this->model->findOneById($userId);
    }

    /**
     * 获取邮箱注册用户的验证码
     * */
    public function getRegisterEmailCode($email,$throwError = true)
    {
        $ret=['data'=>'','code'=>0];
        $checkRet= ApiSysUserFacade::checkSysUserEmailUnique($email,false);
        if(!empty($checkRet['code'])&&$checkRet['code']===10014){
            //邮箱已被注册
            if($throwError){
                throw new ApiException('common.app_email_allow_register', '邮箱已被注册!');
            }else{
                $ret['code']=1080;
                return $ret;
            }

        }else{
            $language='zh-cn';
            $footer='开始你的商业之旅。';
            if (isOverseasEdition()) {
                $language='en-us';
                $footer='Start your business journey.';
            }
            $sendRet = ApiSysUserFacade::getEmailCode($email,$language,$footer);
            $ret['data']=true;
            return $ret;
        }
    }

     /**
     * 获取手机的验证码
     * */
    public function getPhoneCode($phone, $countryCode)
    {
        $sendRet = ApiSysUserFacade::getSmsCode($phone, $countryCode);
        $ret['data'] = true;
        return $ret;
    }

    /**
     * 客户端文件上传
     * */
    public function customerUpload($request)
    {
        $userId = $this->getAuthUserId();
        $ret = AttachmentFacade::upload($request, $userId);
        return $ret;
    }
    
    /**
     * 获取用户基本数据
     * @$noCache 不使用缓存
     * */
    public function accountInfo()
    {
        $resultData = [];
        $user = $this->getAuthUser();
        if(!empty($user)){
            // 获取用户状态
            $userStatus = UserFacade::getFieldById('status', $user['id']);
            if ($userStatus == 1) {
                $this->logout($user['api_token']);
                throw new ApiException('common.auth_fail', '认证失败');
            }
            //获取
            $baseField = array('id', 'user_name', 'nick_name', 'create_time',
                    'phone', 'email', 'avatar');
            foreach ($baseField as $value) {
                $resultData[$value] = empty($user[$value])?'':$user[$value];
            }
         }

         return $resultData;
     }

     /**
     * 登出
     * */
    public function logout($token)
    {
        if (empty($token)) {
            return;
        }
        $this->forgotToken($token);
        return;
    }
}
