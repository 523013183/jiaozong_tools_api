<?php
$app = app()->router;

/**
 * pc 无需auth路由
 * */
$app->group([
    'namespace' => 'App\Api\Controllers',
    'prefix' => 'admin',
    'middleware' => ['auth'],
], function () use ($app) {
    // 考试事件
    $app->post('/exams/info', ['uses' => 'ExamController@saveInfo']);
    $app->get('/exams/info', ['uses' => 'ExamController@getInfo']);
    $app->get('/exams/list', ['uses' => 'ExamController@getList']);
    $app->delete('/exams/info', ['uses' => 'ExamController@deleteInfo']);
});
