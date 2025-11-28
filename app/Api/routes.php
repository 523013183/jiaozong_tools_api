<?php
$app = app()->router;

/**
 * pc 无需auth路由
 * */
$app->group([
    'namespace' => 'App\Api\Controllers',
    'prefix' => 'api',
], function () use ($app) {
    //获取邮箱注册验证码
    $app->post('/user/get-reg-email-code', ['uses' => 'ApiController@getRegisterEmailCode']);
    //获取手机验证码
    $app->group(['middleware' => 'sms_rate_limit'], function ($app) {
        $app->post('/user/get-phone-code', ['uses' => 'ApiController@getPhoneCode']);
    });
    // 小程序登录
    $app->post('/user/wechat-login', ['uses' => 'ApiController@miniProgLogin']);
    $app->post('/user/wechat-phone-quick-login', ['uses' => 'ApiController@quickPhoneLogin']);
    // pc登录
    $app->post('/user/login', ['uses' => 'ApiController@login']);

    $app->get('/blog/info', ['uses' => 'BlogController@getInfo']);
    $app->get('/blog/front-list', ['uses' => 'BlogController@getFrontList']);

    // 返回考试日历列表
    $app->get('/exams-list', ['uses' => 'BlogController@getFrontList']);
    $app->get('/exam-info', ['uses' => 'BlogController@getFrontList']);
});

/**
 * pc 无需auth路由
 * */
$app->group([
    'namespace' => 'App\Api\Controllers',
    'prefix' => 'api',
    'middleware' => ['auth'],
], function () use ($app) {
    // 用户操作
    $app->post('/user/update-base-user-info', ['uses' => 'UserController@updateBaseUserInfo']);
    //获取用户详细信息
    $app->get('/user/base-account', ['uses' => 'ApiController@baseAccount']);
    //登出
    $app->post('/user/logout', ['uses' => 'ApiController@logout']);

    //附件上传
    $app->post('/attachment/upload', 'ApiController@upload');
    //上传base64Tupac
    $app->post('/attachment/upload-image', 'ApiController@uploadImg');

    // 资讯、新闻
    $app->get('/blog/list', ['uses' => 'BlogController@getList']);
    $app->get('/blog/cate-list', ['uses' => 'BlogController@getCateList']);
    $app->post('/blog/info', ['uses' => 'BlogController@saveInfo']);
    $app->delete('/blog/info', ['uses' => 'BlogController@deleteInfo']);
    $app->post('/blog/status', ['uses' => 'BlogController@toggleBlogStatus']);
});

$app->group([
    'namespace' => 'App\Api\Controllers',
    'prefix' => ''
], function () use ($app) {
    $app->get('/','IndexController@index');
});
