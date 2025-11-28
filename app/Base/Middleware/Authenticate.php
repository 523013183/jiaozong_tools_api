<?php

namespace App\Base\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Base\Exceptions\ApiException;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     * @param  \Illuminate\Contracts\Auth\Factory $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     * @return mixed
     * @throws ApiException
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $user = $request->user();
        if (!isset($user['id']) || !$user['id']) {
            throw new ApiException('common.auth_fail', '认证失败');
        }
        if (is_object($user)) {
            $user = $user->toArray();
        }
//        if(!checkApiPermission($user['permissions'],$request->path(), ($request->method() == 'GET' ? $request->all() : []))){
//            throw new ApiException('common.no_permission', '没有权限');
//        }
        return $next($request);
    }
}
