<?php


namespace App\Crontab\Services;

class TaskInfoService
{

    /**
     * TaskInfoService constructor.
     */
    public function __construct()
    {

    }
    /**
     * 执行任务
     * todo 请维护README.md文件！！！
     * @param $params //module：执行代码所在的模块，service：执行代码所在的业务文件，method：执行代码所在的业务方法，params：执行代码的业务方法所携带的参数
     * @return int|void
     */
    public function execute($params)
    {
        try {
            $module = $params['module'] ?? '';
            $service = $params['service'] ?? '';
            $method = $params['method'] ?? '';
            $params = $params['params'] ?? [];

            if($module && $method){
                $module = ucfirst(convertUnderline($module));
                $method = convertUnderline($method);
                $service = ucfirst(convertUnderline($service));
                $reflection = new \ReflectionClass('App\\'.$module.'\\Services\\'.$service.'Service');
                $service = $reflection->newInstanceWithoutConstructor();
                if($reflection->hasMethod($method)){
                    if(!empty($params)){
                        return $service->$method(...$params);
                    } else {
                        return $service->$method();
                    }
                }
                return 0;
            }
        } catch (\Exception $e){
            //print_r($e->getMessage());
        }

    }
}
