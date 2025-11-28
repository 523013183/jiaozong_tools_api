<?php

namespace App\Base\Middleware;

use App\Api\Facades\ApiFacade;
use App\Base\Exceptions\ApiException;
use Closure;

class ApiPermission
{
    public function handle($request, Closure $next, $guard = null)
    {
        $user = $request->user();
        if (!isset($user['id']) || !$user['id']) {
            throw new ApiException('common.auth_fail', '认证失败');
        }
        if (is_object($user)) {
            $user = $user->toArray();
        }
        $userId = $user['id'];
        $tk = $request->header('api_token');
        if(empty($tk)){
            $tk = $request->input('api_token');
        }
        $login_singleton=config('app.login_singleton');
        //如果不是最近登录的token
        if ($login_singleton&&!empty($tk)&& !ApiFacade::isLastToken($userId, $tk)) {
            ApiFacade::logout($tk);
            throw new ApiException('common.user_other_login', '您的账号已在其他地方登录！');
        }
        return $next($request);
    }
}
