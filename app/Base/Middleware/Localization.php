<?php

namespace App\Base\Middleware;

use Closure;
use App\Base\Exceptions\ApiException;

class Localization
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     * @return mixed
     * @throws ApiException
     */
    public function handle($request, Closure $next, $guard = null)
    {
        //设置多语言
        $isOverseas = boolval(config('app.overseas_edition'));
        $siteName = 'inside';
        if ($isOverseas) {
            $siteName = 'overseas';
        }
        $lang = $request->input('lang');
        if(empty($lang)) {
            //?lang=en-us
            // 使用客户端的语言设置
            $lang = $request->header("Language-Set");
            if (empty($lang)) {
                // 使用cookie语言设置
                $globalLocaleCookieKey = $siteName.'_'.config('app.global_locale_cookie_key');
                $lang = $request->cookie($globalLocaleCookieKey);
            }
        }
        $translator = app('translator');
        $curLang = $translator->getLocale();
        $allowSet = false;
        if (!empty(config('app.overseas_edition'))) {
            // 海外版支持的语言
            if (in_array($lang, ['en-us', 'zh-tw'])) {
                $allowSet = true;
            }
        } else {
            if ($lang == 'zh-cn') {
                $allowSet = true;
            }
        }
        if ($allowSet && $lang && $lang != $curLang) {
            $translator->setLocale($lang);
        }
        $response = $next($request);
        return $response;
    }
}
