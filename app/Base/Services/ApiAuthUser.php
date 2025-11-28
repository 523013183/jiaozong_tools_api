<?php


namespace App\Base\Services;


use App\Api\Facades\ApiFacade;
use App\User\Facades\UserFacade;
use Illuminate\Support\Facades\Auth;



trait ApiAuthUser
{
    /** @var null 用户系统后台任务动态授权 */
    private $sysAuthUser = null;

    public function setSysAuthUser($sysAuthUser)
    {
        $this->sysAuthUser = $sysAuthUser;
    }

    /**
     * 获取登录用户ID
     * @return int
     */
    public function getAuthUserId()
    {
        $user = $this->getAuthUser();
        return $user['id'] ?? 0;
    }

    /**
     * 获取登录用户当前主页身份
     * @return int
     */
    public function getAuthCurrEnterpriseId()
    {
        $user = $this->getAuthUser();
        return $user['curr_enterprise_id'] ?? 0;
    }

    /**
     * 获取登录用户个人主页
     * @return int
     */
    public function getAuthPersonalEnterpriseId()
    {
        $user = $this->getAuthUser();
        return $user['enterprise_id'] ?? 0;
    }

    /**
     * 获取token
     * @return int
     */
    public function getAuthApiToken()
    {
        $user = $this->getAuthUser();
        return $user['api_token']??'';
    }

    /**
     * 获取token
     * @return int
     */
    public function getAuthSysApiToken()
    {
        $user = $this->getAuthUser();
        return $user['sys_api_token']??'';
    }

    /**
     * 获取手机
     * @return string
     */
    public function getAuthPhone()
    {
        $user = $this->getAuthUser();
        return $user['phone']??'';
    }
    /**
     * 获取国家编码
     * @return string
     */
    public function getAuthCountryCode()
    {
        $user = $this->getAuthUser();
        return $user['country_code']??'86';
    }

    /**
     * 获取邮箱
     * @return string
     */
    public function getAuthEmail()
    {
        $user = $this->getAuthUser();
        return $user['email']??'';
    }

    /**
     * 获取登录用户真实姓名
     * @return int
     */
    public function getAuthRealName()
    {
        $user = $this->getAuthUser();
        return $user['real_name'] ?? '';
    }

    /**
     * 获取登录用实名认证id
     * @return int
     */
    public function getAuthPersonalCertifiedId()
    {
        $user = $this->getAuthUser();
        return $user['personal_certified_id'] ?? 0;
    }

    /**
     * 获取登录用户企业认证id
     * @return int
     */
    public function getAuthEnterpriseCertifiedId()
    {
        $user = $this->getAuthUser();
        return $user['enterprise_certified_id'] ?? 0;
    }

    /**
     * 获取子账号id列表
     * @return string
     */
    public function getAuthEnterpriseIds()
    {
        $user = $this->getAuthUser();
        return $user['enterprise_ids']??[];
    }


    /**
     * 获取登录用户
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getAuthUser()
    {
        // todo 用redis缓存来获取
        $user = Auth::user();
        if ($user == null) {
            $user = $this->sysAuthUser ?: null;
        }
        return $user;
    }

    /**
     * 获取登录设备语言
     * */
    public function getAuthDeviceLanguage(){
        $user = $this->getAuthUser();
        return empty($user['device_language'])?'zh-cn':$user['device_language'];
    }


    /**
     * 获取是否长登录
     * */
    public function getAuthlongLogin(){
        $user = $this->getAuthUser();
        return empty($user['long_login'])?0:$user['long_login'];
    }


    /**
     * 获取房间id
     * */
    public function getAuthRoomId(){
        $user = $this->getAuthUser();
        return empty($user['room_id'])?'':$user['room_id'];
    }

    /**
     * 更新用户的
     * */
    public function updateAuthUserData($isDetail=0){
        $user=Auth::user();
        if(!empty($user)){
            if($isDetail){
                $newUser = UserFacade::getDetailUserInfoById($user['id']);
            }else{
                $newUser = UserFacade::getBaseUserInfoById($user['id']);
            }
            $newUser['device_language']=empty($user['device_language'])?'zh-cn':$user['device_language'];
            $newUser['long_login']=empty($user['long_login'])?'0':$user['long_login'];
            $newUser['sys_api_token']=empty($user['sys_api_token'])?'':$user['sys_api_token'];
            $user =   ApiFacade::updateUserInfoCache($user['api_token'], $newUser);
        }
        return $user;
    }

    /**
     * 获取token
     * @return int
     */
    public function getAuthIsUpdateUser()
    {
        $user = $this->getAuthUser();
        return empty($user['is_update_user'])?false:$user['is_update_user'];
    }
}
