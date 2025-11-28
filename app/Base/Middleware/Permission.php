<?php

namespace App\Base\Middleware;

use App\Base\Exceptions\ApiException;
use Closure;

class Permission
{
    public function handle($request, Closure $next, $guard = null)
    {
//        $user = $request->user();
//        if ($user['role_id'] != 1) {
//            $path = $request->path();
//            if (strpos($path, '/') !== 0) {
//                $path = '/' . $path;
//            }
//
//            if (!isset($user['rules'][$path]) && !isset($user['rules_params'][$path])) {
//                throw new ApiException('common.no_permission', '您没有权限');
//            }
//
//            //如果有字段参数需要判断
//            if (!empty($user['rules_params'][$path])) {
//                $params = $request->all();
//                $check = 0;
//                $check2 = 1;
//                foreach ($user['rules_params'][$path] as $key => $rp) {
//                    if (!empty($rp['params'])) {
//                        foreach ($rp['params'] as $k => $p) {
//                            if (isset($params[$k])) {
//                                if ($params[$k] == $p || empty($p)) {
//                                    $check = 1;
//                                    $check2 = 1;
//                                    break;
//                                }
//                                $check2 = 0;
//                            }
//                        }
//                    }
//                    if ($check) {
//                        break;
//                    }
//                }
//                //如果参数没有对上，有可能是不需要参数
//                if ($check2 && !$check) {
//                    if (isset($user['rules_params'][$path][-1])) {
//                        $check = 1;
//                    }
//                }
//                if (!$check) {
////                    Log::info('user222'.json_encode($user['rules_params'][$path]));
//                    throw new ApiException('common.no_permission', '您没有权限');
//                }
//            }
//        }
        return $next($request);
    }
}