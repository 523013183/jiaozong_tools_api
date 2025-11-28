<?php

namespace App\Base\Providers;

use App\Api\Providers\ApiServiceProvider;
use App\Attachment\Providers\AttachmentServiceProvider;
use App\Base\Facades\ApiSysUserFacade;
use App\Base\Facades\DictLocationFacade;
use App\Base\Facades\MailFacade;
use App\Base\Facades\ObsFacade;
use App\Base\Facades\SmsFacade;
use App\Base\Services\ApiSysUserService;
use App\Base\Services\MailService;
use App\Base\Services\SmsService;
use App\Basic\Providers\BasicServiceProvider;
use App\Crontab\Providers\TaskServiceProvider;
use App\Doc\Providers\DocServiceProvider;
use App\Base\Facades\UploadFacade;
use App\Base\Models\ComOssETagModel;
use App\Base\Services\DictLocationService;
use App\Base\Services\ObsService;
use App\Base\Services\UploadService;
use App\User\Providers\UserServiceProvider;
use App\Web\Providers\WebServiceProvider;
use Illuminate\Redis\RedisServiceProvider;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    //路由文件名
    protected $routes = 'routes.php';

    public function boot()
    {
        //sql打印 不提交
       /* \DB::listen(function ($query) {
            $sql = array_reduce($query->bindings, function($sql, $binding) {
                return preg_replace('/\?/', is_numeric($binding) ? $binding : sprintf("'%s'", $binding), $sql, 1);
            }, $query->sql);

            \Log::info($sql);
        });*/
        //自动载入路由
        $func = new \ReflectionClass(get_class($this));
        $path = str_replace($func->getShortName() . '.php', '', $func->getFileName());
        $routesFile = $path . '../' . $this->routes;
        if (file_exists($routesFile)) {
            require $routesFile;
        }

        if (! isset($this->app['blade.compiler'])) {
            $this->app['view'];
        }
        parent::boot();
    }


    /**
     * 注册
     */
    public function register()
    {
        //基础服务
        $this->registerBaseService();
        //文档模块
        $this->app->register(DocServiceProvider::class);

        // 接口模块
        $this->app->register(ApiServiceProvider::class);
        //注册定时任务模块
        $this->app->register(TaskServiceProvider::class);
        $this->app->register(BasicServiceProvider::class);
        $this->app->register(UserServiceProvider::class);
        $this->app->register(AttachmentServiceProvider::class);
        $this->app->register(WebServiceProvider::class);

        $this->app->bind(ApiSysUserFacade::class, function () {
            return app()->make(ApiSysUserService::class);
        });
        $this->app->bind(DictLocationFacade::class, function () {
            return app()->make(DictLocationService::class);
        });
        $this->app->bind(ObsFacade::class, function () {
            return app()->make(ObsService::class);
        });
        $this->app->bind(ObsService::class, function () {
            return new ObsService(new ComOssETagModel());
        });
        $this->app->bind(UploadFacade::class, function () {
            return app()->make(UploadService::class);
        });
        //注册公共短信服务
        $this->registerSms();
    }

    // 注册基础服务
    public function registerBaseService()
    {
        // redis服务
        $this->app->register(RedisServiceProvider::class);
        // 短信服务
//        $this->app->register(AliyunsmsServiceProvider::class);
        // 授权验证
        $this->app->register(AuthServiceProvider::class);
    }

    /**
     * 注册公共短信发送服务
     * */
    protected function registerSms()
    {
        $this->app->bind(SmsFacade::class, function () {
            return app()->make(SmsService::class);
        });
        $this->app->bind(MailFacade::class, function () {
            return app()->make(MailService::class);
        });
    }
}
