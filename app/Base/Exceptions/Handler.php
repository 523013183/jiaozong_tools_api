<?php

namespace App\Base\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(\Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, \Throwable $e)
    {
        $content = [
            'ret' => $e->getCode() == 0 ? '500' : $e->getCode(),
            'msg' => $e->getMessage()
        ];
        $status = 200;
        if ($e instanceof \HttpResponseException) {
            $content['data'] = $e->getResponse();
        } elseif ($e instanceof ModelNotFoundException || ($e instanceof NotFoundHttpException && $request->ajax())) {
            $content['ret'] = 404;
            $content['msg'] = '您访问的页面地址不存在';
        } elseif ($e instanceof NotFoundHttpException) {
            $content['ret'] = 404;
            $content['msg'] = '您访问的页面地址不存在';
            $status = 404;
        } elseif ($e instanceof AuthorizationException) {
            $content['ret'] = 403;
        } elseif ($e instanceof ValidationException && $e->getResponse()) {
            $content['ret'] = 422;
            $content['data'] = json_decode($e->getResponse()->getContent(), true);
            //处理多语言中的[11000,'xxx']格式
            $msg = [];
            $ret = [];
            foreach ($content['data'] as &$item) {
                if (is_array($item)) {
                    foreach ($item as &$item2) {
                        if (is_array($item2) && count($item2) == 2) {
                            $msg = array_merge($msg, [$item2[1]]);
                            $ret = array_merge($ret, [$item2[0]]);
                            $item2 = $item2[1];
                        } else {
                            $msg = array_merge($msg, [$item2]);
                        }
                    }
                } else {
                    $msg[] = $item;
                }
            }
            $content['msg'] = reset($msg);
            $content['ret'] = reset($ret) ?: 422;
            if (is_array($content['msg'])) {
                if (count($content['msg']) == 2) {
                    $content['ret'] = $content['msg'][0];
                    $content['msg'] = $content['msg'][1];
                }
            }
        } elseif ($e instanceof ApiException) {
            $content['data'] = $e->getData();
        }

        $trace = $e->getTrace();
        if (isset($content['msg']) && $content['msg']) {
            $track = json_encode($trace, JSON_UNESCAPED_UNICODE);
            if (stristr($content['msg'], 'connection')
                || stristr($content['msg'], 'SQLSTATE')
                || stristr($track, 'PDOException: SQLSTATE')
            ) {
                $content['ret'] = -100;
            }
        }

        if (env('APP_DEBUG')) { // 开发模式增加track输出
            $content['track'] = $trace[0];
        }

        $response = new Response(json_encode($content, JSON_UNESCAPED_UNICODE), $status);
        $response->header('Content-Type', 'application/json;charset:UTF-8');
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Allow-Methods', 'PUT, GET, POST, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type,token,api-token,x-requested-with,Language-Set');
        $response->header('Access-Control-Expose-Headers', '*');
        $response->exception = $e;

        return $response;
    }
}
