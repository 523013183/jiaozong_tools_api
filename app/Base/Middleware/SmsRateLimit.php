<?php

namespace App\Base\Middleware;

use App\Base\Exceptions\ApiException;
use Closure;
use Illuminate\Support\Facades\Cache;

// 短信验证码限流
class SmsRateLimit
{
    // 限制每分钟最多 1 次请求
    protected $maxAttempts = 1;
    protected $decayMinutes = 1;
    protected $cacheKey = 'sms_rate_limit:';

    public function handle($request, Closure $next)
    {
        $ip = $request->ip();
        $key = $this->cacheKey . $ip;
        \Log::info($key);
        $attempts = Cache::get($key, 0);
        if ($attempts >= $this->maxAttempts) {
            throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
        }
        Cache::put($key, $attempts + 1, $this->decayMinutes * 60);
        return $next($request);
    }
}
