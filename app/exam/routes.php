<?php
$app = app()->router;

/**
 * pc 无需auth路由
 * */
$app->group([
    'namespace' => 'App\Api\Controllers',
    'prefix' => 'api',
    'middleware' => ['auth'],
], function () use ($app) {
    // 考试事件
    $app->post('/exams/info', ['uses' => 'BlogController@saveInfo']);
    $app->get('/exams/info', ['uses' => 'BlogController@saveInfo']);
    $app->delete('/exams/info', ['uses' => 'BlogController@deleteInfo']);
});
