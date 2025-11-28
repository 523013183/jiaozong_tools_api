<?php
$app = app()->router;

$app->group([
    'namespace' => 'App\Web\Controllers',
    'prefix' => ''
], function () use ($app) {
    $app->get('/blade','IndexController@blade'); //获取渲染后的组件html内容
    $app->get('/','IndexController@index');
    $app->get('/apply','IndexController@apply'); // 报名
    $app->get('/apply-form','IndexController@applyForm'); // 报名表单页面
    $app->get('/sign-in','IndexController@signIn'); // 签到
    $app->get('/about','IndexController@about'); // 介绍
    //模块路由
    $app->get('/{urla:[A-Za-z0-9-]+}[.html]',[
        'as' => 'moduleIndex', 'uses' => 'IndexController@index'
    ]);
});


