<?php


namespace App\Base\Services;

use App\Api\Facades\ApiFacade;
use App\Base\Exceptions\ApiException;
use App\User\Facades\UserFacade;
use Illuminate\Support\Facades\Cache;

class ApiSysUserService
{
    /**
     * @var
     */
    protected $tokenBucket = 'ExpoRegApiToken:';

    /**
     * ApiService constructor.
     */
    public function __construct()
    {
    }

    /**
     * http请求
     * @param $method 请求方式 GET|POST|PUT|DELETE
     * @param $url 请求地址
     * @param array $params 请求参数
     * @param array $headers 请求带的header头
     * @return string 返回数据
     * @throws ApiException
     */
    public function request($method, $url, $params = [], $headers = [])
    {
        $headers['X-Requested-With'] = 'XMLHttpRequest';
        return httpClient($method, $url, $params, $headers);
    }

    /**
     * 是否返回成功
     * @param $res
     * @return bool
     */
    private function isSuccess($res)
    {
        if (!empty($res) && is_array($res) && $res['code'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 登录
     * @param $username
     * @param $password
     * @param string $redirect
     * @param string $checkCode
     * @return array
     * @throws ApiException
     */
    public function pwdLogin($userName, $password)
    {
        $user = UserFacade::getLoginInfoByUserNamge($userName);
        if (!empty($user)) {
            $prefixed = $user['salt'];
            $passwordStr = $prefixed . $password;
            $tempPassword = md5($passwordStr);
            if ($user['status'] != 0) {
                throw new ApiException('user.user_disabled_tip', '您的帐号已被禁用');
            }
            if ($tempPassword == $user['password']) {
                //生成token 缓存基本信息
                $randomStr = getRandomStr('CLIENT_TOKEN');
                $apiToken = md5($userName . $randomStr);
                $userInfo = UserFacade::getUserBaseInfoById($user['id']);
                $rep['api_token'] = $apiToken;
                $rep['user_id'] = $userInfo['id'];
                return $rep;
            } else {
                throw new ApiException('user.user_name_password_tip', '对不起，您输入的帐号或密码不正确');
            }
        } else {
            throw new ApiException('user.user_not_exists', '对不起，用户不存在');
        }
    }

    /**
     * 心跳接口
     * @param $apiToken
     * @return mixed|string
     * @throws ApiException
     */
    public function heartBeat($apiToken, $throwError = false)
    {
        $user = $this->findClientUserByToken($apiToken);
        $this->updateUserInfoCache($apiToken, $user);
        return true;
    }

    /**
     * 更新用户api_token缓存
     * */
    public function updateUserInfoCache($token='',$user=[])
    {
        $CacheTokenTime=config('cache.token_expire_time');
        $nowTime=time();
        $expiration_time= $nowTime+$CacheTokenTime;
        $user['expiration_time']=$expiration_time;
        ApiFacade::setToken($token, $user);
        return $user;
    }

    /**
     * 根据token获取用户
     * @param $token
     * @return array | mixed
     */
    public function findClientUserByToken($token)
    {
        return Cache::get($this->getTokenKey($token));
    }

    /**
     * 获取token缓存key
     * @param $token
     * @return string
     */
    private function getTokenKey($token)
    {
        return $this->tokenBucket . $token;
    }
}
