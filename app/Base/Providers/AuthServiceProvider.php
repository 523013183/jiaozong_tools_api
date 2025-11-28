<?php

namespace App\Base\Providers;

use App\Admin\Facades\AdminUserFacade;
use App\Api\Facades\ApiFacade;
use App\Base\Facades\ApiSysUserFacade;
use Illuminate\Support\ServiceProvider;
use App\Base\Exceptions\ApiException;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.
        //处理swoole框架中带来的认证重复问题
        $auth = app('auth');
        $reflClass = new \ReflectionClass($auth);
        $reflProp = $reflClass->getProperty('guards');
        $reflProp->setAccessible(true);
        $reflProp->setValue($auth, []);

        $reflProp = $reflClass->getProperty('customCreators');
        $reflProp->setAccessible(true);
        $reflProp->setValue($auth, []);

        $this->app['auth']->viaRequest('api', function ($request) {
//            checkRedisPing();
            $token = $request->header('token');
            if (empty($token)) {
                $token = $request->input('token');
            }
            $apiToken = $request->header('api_token');
            if(empty($apiToken)){
                $apiToken = $request->input('api_token');
            }
            if ($apiToken) {
                $data = ApiFacade::findUserInfoByApiToken($apiToken);
                if (!empty($data)) {
                    //1天做一次缓存更新
                    $time=time();
                    $heartBeatTime= config('cache.heart_beat_time');//心跳时间
                    if (!empty($data['heart_beat_time'])&&($data['heart_beat_time']+$heartBeatTime) <$time) {
                        $res= ApiSysUserFacade::heartBeat($data['sys_api_token']);
                        if(!$res){
                            ApiFacade::forgotToken($apiToken);
                            throw new ApiException('common.auth_fail', '认证失败');
                        }
                        $data['heart_beat_time']=$time;
                    }
                    $data =   ApiFacade::updateUserInfoCache($apiToken, $data);
                    return $data;
                }
            }
            return null;
        });
    }
}
