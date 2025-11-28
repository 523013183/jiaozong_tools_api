<?php
$app = app()->router;
$app->group([
    'namespace' => 'App\Doc\Controllers',
], function ($router) {
    //文档分组菜单
    $router->get('/doc/group', 'DocController@group');
    //文档接口详情
    $router->get('/doc/detail', 'DocController@detail');
    //文档多语言统计
    $router->get('/count/file', 'CountController@doc');
});
