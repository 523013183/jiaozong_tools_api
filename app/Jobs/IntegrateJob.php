<?php

namespace App\Jobs;

class IntegrateJob extends Job
{
    protected $module = null;
    protected $service = null;
    protected $method = null;
    protected $params = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->module = $params['module'] ?? '';
        $this->service = $params['service'] ?? '';
        $this->method = $params['method'] ?? '';
        $this->params = $params['params'] ?? [];

    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            if($this->module && $this->method){
                $this->module = ucfirst(convertUnderline($this->module));
                $this->method = convertUnderline($this->method);
                $this->service = ucfirst(convertUnderline($this->service));
                $reflection = new \ReflectionClass('App\\'.$this->module.'\\Services\\'.$this->service);
                $service = $reflection->newInstanceWithoutConstructor();
                if($reflection->hasMethod($this->method)){
                    $method = $this->method;
                    if(!empty($this->params)){
                        return $service->$method(...$this->params);
                    } else {
                        return $service->$method();
                    }
                }
            }
        } catch (\Exception $e){
            return $e->getMessage();
        }
    }
}
