<?php


namespace App\Base\Services;


use App\Base\Exceptions\ApiException;
use Illuminate\Support\Facades\Redis;

class BaseService extends AbstractBaseService
{
    use AuthUser;

    /**
     * 获取并发锁
     * @param $lockKey
     * @param $expire
     * @return bool
     */
    function getLock($lockKey, $expire = 3)
    {
        $redis = Redis::connection()->client();
        if (!$redis->setnx($lockKey, 1)) {
            return false;
        }
        $redis->expire($lockKey, $expire);
        return true;
    }

    /**
     * 释放锁
     * @param $lockKey
     * @return mixed
     */
    function releaseLock($lockKey)
    {
        $redis = Redis::connection()->client();
        return $redis->del($lockKey);
    }

    /**
     * 没有操作权限
     * @throws ApiException
     */
    function throwNoPermission()
    {
        throw new ApiException('common.no_operator_permission', '您没有权限操作此功能');
    }

}
