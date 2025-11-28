<?php
$app = app()->router;
$app->group([
    'namespace' => 'App\Crontab\Controllers',
    'prefix' => 'crontab',
    'middleware' => [],
], function () use ($app) {
       //   定时任务请求地址
       //  /crontab/task/run?module=module&service=service&method=method&params=params
    $app->get('/task/run', 'TaskInfoController@task');
});