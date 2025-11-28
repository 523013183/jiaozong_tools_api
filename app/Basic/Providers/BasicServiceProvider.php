<?php

namespace App\Basic\Providers;


use App\Base\Providers\AppServiceProvider;


class BasicServiceProvider extends AppServiceProvider
{
    public function boot()
    {
        //sql打印 不提交
        /*\DB::listen(function ($query) {
            $sql = array_reduce($query->bindings, function($sql, $binding) {
                return preg_replace('/\?/', is_numeric($binding) ? $binding : sprintf("'%s'", $binding), $sql, 1);
            }, $query->sql);

            \Log::info($sql);
        });*/
        parent::boot();
    }

    /**
     * 注册绑定门面
     */
    public function register()
    {
    }
}
