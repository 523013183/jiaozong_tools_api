<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'table'  => env('CACHE_DATABASE_TABLE', 'cache'),
            'connection' => env('CACHE_DATABASE_CONNECTION', null),
        ],

        'file' => [
            'driver' => 'file',
            'path'   => storage_path('framework/cache'),
        ],

        'memcached' => [
            'driver'  => 'memcached',
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'), 'port' => env('MEMCACHED_PORT', 11211), 'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('CACHE_REDIS_CONNECTION', 'default'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', 'prereg:'),
    /**
     * 字段缓存时间
     */
    'columns' => env('CACHE_COLUMNS',3600),
    /**
     * 数据缓存时间
     */
    'time'  => env('CACHE_TIME',3600),
    /**
     * 较短的缓存时间
     */
    'short_time' => env('SHORT_CACHE_TIME', 300),
    /**
     * token缓存时间, 单位秒
     */
    'token' => env('TOKEN_TIME',3600),
    'api_token' => env('TOKEN_TIME',3600),
    'sms_time'=>env('SMS_TIME',1800), //30分钟
    'email_time'=>env('EMAIL_TIME',3600), //一小时
    'def_time'=>env('DEF_TIME',60*60*24), //一天
    'api_token_expire_time'=>env('TOKEN_EXPIRE_TIME',60*60*24), //2*60*60小时（秒）token缓存时间
    'api_token_long_expire_time'=>env('TOKEN_EXPIRE_TIME',60*60*24*30), //30*24小时（秒）token缓存时间
    //表单缓存时间
    'form_cache_time' => env('FORM_CACHE_TIME', 7*24*60),
    //订单缓存时间
    'order_expire_time' => env('ORDER_EXPIRE_TIME', 30),
    'heart_beat_time' => env('TOKEN_EXPIRE_TIME', 60 * 30), //7*24小时（秒）token缓存时间
];
