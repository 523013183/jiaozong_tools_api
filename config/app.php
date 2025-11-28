<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY', 'SomeRandomString!!!'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => env('APP_LOCALE', 'zh-cn'),

    'log' => 'daily',
    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    /**
     * 默认分页行数
     */
    'app_rows' => env('APP_ROWS', 10),
    /**
     * token缓存时间
     */
    'token_time' => env('APP_TOKEN_TIME', 20 * 60),

    // 运行模式, 0-开发模式，1-正式模式
    'app_mode' => env('APP_MODE', 1),

    'attachment_host' => env('ATTACHMENT_HOST', ''),

    //开启单点登录
    'login_singleton' => env('SINGLE_LOGIN', false),

    'js_version' => env('JS_VERSION', '0.01'),
    // 通知开关
    'notice_switch'=>env('NOTICE_SWITCH', true),
    'bucketLimitSize' => env('BUCKET_LIMIT_SIZE', 10737418240), //单用户存储上限10g 
    //ffmpeg相关设置
    'USE_FFMPEG' => env('USE_FFMPEG', false),
    'ffmpeg'    => [
        'ffmpeg_binaries'  => env('FFMPEG_BINARIES',''), //ffmpeg 程序位置
        'ffprobe_binaries'  => env('FFPROBE_BINARIES',''), //ffprobe 程序位置
        'timeout'  => env('FFMPEG_TIMEOUT',3600),//超时
        'ffmpeg_threads' => env('FFMPEG_THREADS',10),//队列所占最大线程数
    ],
    'aes_key' => env('AES_KEY', 'aCkAmHSDUNZujks4'),
    'base_url' => env('BASE_URL', 'http://lh-api.prereg.com')
];
