<?php


namespace App\Base\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 数据返回中间件
 * Class Response
 * @package App\Base\Middleware\Middleware
 */
class Response
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $curTime = gettimeofday();
        $beginTime = $curTime['sec'] * 1000000 + $curTime['usec'];
        if (strtolower($request->getMethod()) == 'options') {
            $response = new \Illuminate\Http\Response();
            $response->withHeaders([
                'Content-Type' => $request->ajax() ? 'application/json; charset:UTF-8' : 'text/html; charset=UTF-8',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods' => 'PUT, GET, POST, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type,token,api-token,X-Requested-With,Language-Set',
                'Access-Control-Expose-Headers' => '*'
            ]);
            return $response;
        }
        //去除请求参数左右两边空格
        $params = $request->all();
        foreach ($params as $key => $value) {
            if (!is_array($value)) {
                $params[$key] = trim($value);
            }
        }
        $request->replace($params);
        if ($this->mTrans($request)) {
            DB::beginTransaction();
        }


        //增加redis的连接判断，默认如果连接错误会抛出异常，这边捕获异常，下次会重新连接
//        checkRedisPing();

//        DB::enableQueryLog();
        //在有auth中间件认证时，包括中间件里也有调用request->user(),但其实是只有调用一次的
        $request->attributes->set('_is_check_auth', 1);
        $user = $request->user();
        $request->attributes->set('_is_check_auth', 0);

        $response = $next($request);
        if ($response->getStatusCode() == 200 && (!isset($response->exception) || $response->exception == null)) {
            if ($this->mTrans($request)) {
                DB::commit();
            }

            if ($request->ajax() && !($response instanceof StreamedResponse)) {
                $content = $response->getContent();
                $content = json_encode([
                    'ret' => 0,
                    'msg' => 'success.',
                    'data' => ($this->isJson($content) ? json_decode($content, true) : $content)
                ], JSON_UNESCAPED_UNICODE);
                $response->setContent($content);
            }
        } elseif ($response->getStatusCode() == 404 && !$request->ajax()) {
            //不是ajax请求 跳转到404页面
            return response(view("errors.404"), 404);
        } else {
            if ($this->mTrans($request) || DB::transactionLevel()) {
                DB::rollBack();
            }
            if (!$request->ajax() && !($response instanceof StreamedResponse)) {
                $content = json_decode($response->getContent(), true);
                $response->setContent($content['msg']??'');
            }
        }
        if (method_exists($response,'withHeaders')) {
            $curTime = gettimeofday();
            $costTime = ($curTime['sec'] * 1000000 + $curTime['usec']) - $beginTime;
            $costTime = $costTime / 1000000.0;
//            $this->saveAccessLog($request->method(), $request->path(), $params, $response, $request, $costTime);
            $response->withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods' => 'PUT, GET, POST, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type,token,api_token,X-Requested-With,Language-Set',
                'Access-Control-Expose-Headers' => '*'
            ]);
            if ($request->ajax()) {
                $response->withHeaders(['Content-Type' => 'application/json; charset:UTF-8']);
            } elseif (!$response->headers->get('content-type')) {
                $response->withHeaders(['Content-Type' => 'text/html; charset=UTF-8']);
            }
            $response->withHeaders(['Language' => app('translator')->getLocale()]);
        }
        DB::disconnect();
        return $response;
    }

    /**
     * 是否为json格式的字符串
     * @param $string
     * @return bool
     */
    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 是否开启事务
     * @param Request $request
     * @return bool
     */
    private function mTrans($request)
    {
        if (/*strtolower($request->method())!='get' && */
        config('database.transaction')
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加访问日志
     * @param $response
     */
    private function saveAccessLog($method, $route, $params, $response, $request, $costTime = 0)
    {
        try {
            $user = Auth::user();
            if (!isset($user['id']) || !$user['id']) {
                return;
            }

            // 过滤掉常规路由，主要是定时请求
            $route = trim($route, '/');
            if (in_array($route, ['notice/news', 'checkLogin'])) {
                return;
            }

            $type = 0;
            $serverIp = env('SERVER_IP', ''); // 增加服务器IP,便于快速定位日志
            $data = [
                'method' => strtolower($method),
                'route' => '/' . $route, // 屏蔽掉$request->root()
                'params' => $params,
                'status_code' => $response->getStatusCode(),
                'response' => json_decode($response->getContent(), true),
                'error_code' => $response->exception ? $response->exception->getCode() : 0,
                'error_message' => $response->exception ? $response->exception->getMessage() : '',
                'company_id' => $user->company_id ?? '',
                'user_id' => $user->id ?? '',
                'is_marketing' => 0,
                'type' => $type,  //值：0为cdp,1为marketing，2为dmp,3为小程序
                'ip' => $request->getClientIp(),
                'date' => date('Y-m-d'),
                'create_time' => date('Y-m-d H:i:s'),
                'cost_time' => $costTime,
                'server_ip' => $serverIp,
            ];

//            checkRedisPing('es_log');
//            $redis = app('redis')->connection('es_log');
//            $redis->rpush('queue:api_log', json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            Log::info('es_log:' . print_r($e->getMessage(), true));
        }
    }
}
