<?php

ini_set('date.timezone', 'Asia/Shanghai');
require_once __DIR__ . '/../vendor/autoload.php';

$envFile = '.env';
(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__),
    $envFile
))->bootstrap();
//try {
//    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
//} catch (Dotenv\Exception\InvalidPathException $e) {
//    //
//}

/*
  |--------------------------------------------------------------------------
  | Create The Application
  |--------------------------------------------------------------------------
  |
  | Here we will load the environment and create the application instance
  | that serves as the central piece of this framework. We'll use this
  | application as an "IoC" container and router for this framework.
  |
 */

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);
$app->withFacades();
//注册mongodb  必须在withEloquent之前
//$app->register(Jenssegers\Mongodb\MongodbServiceProvider::class);
$app->withEloquent();

/**
 * 缓存配置
 */
$app->configure('app');
$app->configure('auth');
$app->configure('cache');
$app->configure('aliyunmail');
$app->configure('aliyunsms');
$app->configure('obs');

/*
  |--------------------------------------------------------------------------
  | Register Container Bindings
  |--------------------------------------------------------------------------
  |
  | Now we will register a few bindings in the service container. We will
  | register the exception handler and the console kernel. You may add
  | your own bindings here if you like or you can make another file.
  |
 */

$app->singleton(
        Illuminate\Contracts\Debug\ExceptionHandler::class, App\Base\Exceptions\Handler::class
);

$app->singleton(
        Illuminate\Contracts\Console\Kernel::class, App\Console\Kernel::class
);

/*
  |--------------------------------------------------------------------------
  | Register Middleware
  |--------------------------------------------------------------------------
  |
  | Next, we will register the middleware with the application. These can
  | be global middleware that run before and after each request into a
  | route or middleware that'll be assigned to some specific routes.
  |
 */

$app->middleware([
    //多语言
    App\Base\Middleware\Localization::class,
    //全局添加返回值格式
    App\Base\Middleware\Response::class
]);

$app->routeMiddleware([
    'auth' => App\Base\Middleware\Authenticate::class,
    'permission' => App\Base\Middleware\Permission::class,
    'api_permission' => App\Base\Middleware\ApiPermission::class,
    'sms_rate_limit' => App\Base\Middleware\SmsRateLimit::class,
]);


/*
  |--------------------------------------------------------------------------
  | Register Service Providers
  |--------------------------------------------------------------------------
  |
  | Here we will register all of the application's service providers which
  | are used to bind services into the container. Service providers are
  | totally optional, so you are not required to uncomment this line.
  |
 */
$getMillisecond = function () {
    list($t1, $t2) = explode(' ', microtime());
    return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
};
$app->register(App\Base\Providers\AppServiceProvider::class);
$app->register(App\Base\Providers\EventServiceProvider::class);
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);

/*
 * 配置日志文件为每日
 */
//
//$app->configureMonologUsing(function(Monolog\Logger $monoLog) use ($app) {
//    return $monoLog->pushHandler(
//        new \Monolog\Handler\RotatingFileHandler($app->storagePath() . '/logs/lumen' . php_sapi_name() . '.log', 5)
//    );
//});

/*
  |--------------------------------------------------------------------------
  | Load The Application Routes
  |--------------------------------------------------------------------------
  |
  | Next we will include the routes file so that they can all be added to
  | the application. This will provide all of the URLs the application
  | can respond to, as well as the controllers that may handle them.
  |
 */
return $app;
