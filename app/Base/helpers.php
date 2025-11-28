<?php

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

/**
 * 强转UTF-8
 * @param $str
 * @return mixed|string
 */
function mbEncodingUtf8($str)
{
    if (isUTF8($str))
        return $str;
    else {
        return mb_convert_encoding($str, 'UTF-8', 'ascii,GB2312,UTF-8,gbk');
    }
}

/**
 * http请求
 * @param $method
 * @param $url
 * @param array $params
 * @param array $headers
 * @param int $timeout
 * @param int $error 是否处理错误
 * @return string
 * @throws \App\Base\Exceptions\ApiException
 */
function httpClient($method, $url, $params = [], $headers = [], $timeout = 0, $error = 1)
{
    $curl = new \Curl\Curl();
    $curl->setOpt(CURLOPT_FOLLOWLOCATION, 5);
    $curl->setOpt(CURLOPT_ENCODING, "gzip");
    $curl->setUserAgent('Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36');
    //https 不验证ssl证书
    $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
    if ($timeout > 0) {
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 60);
        $curl->setOpt(CURLOPT_TIMEOUT, $timeout);
    }
    if (is_array($headers) && !empty($headers)) {
        foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
    }
    $method = strtolower($method);
    $curl->$method($url, $params);
    $code = $curl->http_status_code;
    $curl->close();
    if ($error && $code != 200) {
        \Illuminate\Support\Facades\Log::alert('code：' . $curl->error_code . ', error: ' . $curl->error, ['method' => $method, 'url' => $url, 'params' => $params]);
        throw new \App\Base\Exceptions\ApiException($curl->error_code);
    }
    return $curl->response;
}


/**
 * 对象重组
 * @param array $map
 * @param $key
 * @return array
 */
function mapByKey(array $map, $key)
{
    $data = [];
    foreach ($map as $item) {
        $data[$item[$key]] = $item;
    }
    return $data;
}

/**
 * 当前时间
 * @param bool|int $ts
 * @return string
 */
function nowTime($ts = false)
{
    return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
}

/*
 * 日期间隔天数
 * */
function dayDiff($date1, $date2)
{
    $datetime1 = new \DateTime($date1);
    $datetime2 = new \DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->format('%R%a');
}

/**
 * 验证email格式
 * @param $email
 * @return bool
 */
function validEmail($email)
{
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    $pattern = "/^((\w[-\w.+]*[-\w])|[-\w])@([A-Za-z0-9][-_A-Za-z0-9]*\.)+[A-Za-z]{2,20}$/";
    if (preg_match($pattern, $email)) {
        return true;
    }

    return false;
}

/**
 * 加密解密函数
 * @param $string
 * @param string $operation
 * @param string $key
 * @param int $expiry
 * @return bool|string
 */
function authCode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{

    $ckey_length = 4;

    $key = md5($key ? $key : 'matchexpo');
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

/**
 * html转为text
 * @param $str
 * @param int $formatText
 * @return mixed
 */
function html2text($str, $formatText = 1)
{
    $str = preg_replace("/<style .*?<\\/style>/is", "", $str);
    $str = preg_replace("/<script .*?<\\/script>/is", "", $str);
    $str = preg_replace("/<br \\s*\\/>/i", $formatText ? ">>>>" : "", $str);
    $str = preg_replace("/<\\/?p>/i", $formatText ? ">>>>" : "", $str);
    $str = preg_replace("/<\\/?td>/i", "", $str);
    $str = preg_replace("/<\\/?div>/i", $formatText ? ">>>>" : "", $str);
    $str = preg_replace("/<\\/?blockquote>/i", "", $str);
    $str = preg_replace("/<\\/?li>/i", $formatText ? ">>>>" : "", $str);
    $str = preg_replace("/ /i", " ", $str);
    $str = preg_replace("/ /i", " ", $str);
    $str = preg_replace("/&/i", "&", $str);
    $str = preg_replace("/&/i", "&", $str);
    $str = preg_replace("/</i", "<", $str);
    $str = preg_replace("/</i", "<", $str);
    $str = preg_replace("/“/i", '"', $str);
    $str = preg_replace("/&ldquo/i", '"', $str);
    $str = preg_replace("/‘/i", "'", $str);
    $str = preg_replace("/&lsquo/i", "'", $str);
    $str = preg_replace("/’/i", "'", $str);
    $str = preg_replace("/&rsquo/i", "'", $str);
    $str = preg_replace("/>/i", ">", $str);
    $str = preg_replace("/>/i", ">", $str);
    $str = preg_replace("/”/i", '"', $str);
    $str = preg_replace("/&rdquo/i", '"', $str);
    $str = strip_tags($str);
    $str = html_entity_decode($str, ENT_QUOTES, "utf-8");
    $str = preg_replace("/&#.*?;/i", "", $str);
    return $str;
}

/**
 * 去掉内容中的html代码
 */
function strip($str)
{
    $str = str_replace("<br>", "", $str);
    return strip_tags($str);
}

/**
 * 创建多级文件目录
 * @param $dir
 * @return bool
 */
function mkDirs($dir)
{
    if (!is_dir($dir)) {
        if (!mkdirs(dirname($dir))) {
            return false;
        }
        if (!mkdir($dir, 0777, true)) {
            return false;
        }
    }
    return true;
}


/**
 * 下载远程文件
 * @param $fileUrl
 * @param $saveTo
 * @param $fileContent
 */
function downloadRemoteFile($fileUrl, $saveTo, &$fileContent)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_URL, $fileUrl);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 2);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11");
    $fileContent = curl_exec($ch);
    curl_close($ch);
    $downloaded_file = fopen($saveTo, 'w');
    fwrite($downloaded_file, $fileContent);
    fclose($downloaded_file);
}

/**
 * 获取html的meta标签
 * @param $link
 * @return mixed
 */
function getMetaOfLink($link)
{
    if (!stristr($link, 'http')) {
        $link = 'http://' . $link;
    }
    $content = httpClient('get', $link);
    if (!$content) {
        $content = file_get_contents($link);
    }
    $encode = mb_detect_encoding($content, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
    $content = iconv($encode, 'utf-8', $content);

    $reg = "/<title>(.*?)<\/title>/s";
    preg_match($reg, $content, $arr);
    if ($arr[1]) $meta['title'] = $arr[1];
    preg_match_all("/<meta[^>]+name=\"([^\"]*)\"[^>]" . "+content=\"([^\"]*)\"[^>]*>/i", $content, $r, PREG_PATTERN_ORDER);
    for ($i = 0; $i < count($r[1]); $i++) {
        if (strtolower($r[1][$i]) == "keywords") $meta['keywords'] = $r[2][$i];
        if (strtolower($r[1][$i]) == "description") $meta['description'] = $r[2][$i];
    }
    return $meta;
}

/**
 * 是否为utf8编码
 * @param $str
 * @return bool
 */
function isUTF8($str)
{
    $encoding = mb_detect_encoding($str, 'GB2312,UTF-8');
    if ($encoding == 'UTF-8') {
        return true;
    } else {
        return false;
    }
}

//格式化url
function completeUrl($url)
{
    if ($url) {
        $url = trim($url);
        if ($url[strlen($url) - 1] == '/') {
            $url = substr($url, 0, strlen($url) - 1);
        }
    }
    return $url;
}

//去掉http、https以及www
function parseUrl($url)
{
    if ($url) {
        $url = trim($url);
        $reg = '/^((https|http)?:\/\/)/';
        $strs = preg_split($reg, $url);
        $url = $strs[count($strs) - 1];
        if (strpos($url, 'www.') === 0)
            $url = substr($url, 4);
    }
    return $url;
}

/**
 * 从Request中获取客户端IP
 * @param \Illuminate\Http\Request $request
 * @return mixed|string|string[]|null
 */
function getClientIpFromRequest(\Illuminate\Http\Request $request)
{
    $remoteIp = $request->server->get('REMOTE_ADDR');
    $ip = '';
    if ($request->headers->has('X_FORWARDED_FOR')) {
        foreach (explode(',', $request->headers->get('X_FORWARDED_FOR')) as $v) {
            if ($v == 'unknown') {
                continue;
            }
            $clientValues[] = trim($v);
        }
        $ip = isset($clientValues[0]) ? $clientValues[0] : '';
    }
    if (!$ip && $request->server->has('HTTP_CLIENT_IP')) {
        $ip = $request->server->get('HTTP_CLIENT_IP');
    }
    if (!$ip && $request->headers->has('X-Real-IP')) {
        $ip = $request->headers->get('X-Real-IP');
    }
    $ip = $ip ? $ip : $remoteIp;
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? $ip : ($remoteIp ?: '0.0.0.0');
    return $ip;
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function getClientIp($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) return $ip[$type];
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 其他时区时间转为北京时间 $timezone_time 时区时间，$timezone时区
 * @param $timezoneTime
 * @param $timezone
 * @param int $isDefault
 * @return false|string
 */
function toTimezone($timezoneTime, $timezone, $isDefault = 0)
{
    $beiJin = 8;
    if (!$isDefault) {
        $diffS = ($beiJin - $timezone) * 3600;//相差秒数
    } else {
        $diffS = ($timezone - $beiJin) * 3600;//相差秒数
    }
    $zoneTime = strtotime($timezoneTime);
    $time = date('Y-m-d H:i', $zoneTime + $diffS);
    return $time;
}

/**
 * 时间显示格式
 * @param $theTime
 * @param $isShort true-短格式
 * @return false|string
 */
function timeTran($theTime, $isShort = false)
{
    $nowTime = time();
    $showTime = $theTime;
    $dur = $nowTime - $showTime;
    //$monthLastTime = strtotime(date('Y-m-01', $theTime). ' 1 month -1 day'); //当月最后一天
    $transKeySuffix = '';
    if (!$isShort ) {
        $transKeySuffix = '_long';
    }
    if ($dur <= 60) {
        return transL("common.now", "刚刚");
    } elseif ($dur <= 3600) {
        $number = floor($dur / 60);
        return $number . ' '. transL("common.minute_ago".$transKeySuffix, "分钟前", ['s'=>($number ? 's' : '')]);
    } elseif ($dur <= 86400) {
        $number = floor($dur / 3600);
        return $number . ' '. transL("common.hour_ago".$transKeySuffix, "小时前", ['s'=>($number ? 's' : '')]);
    } elseif ($dur <= 86400 * 30) {
        $number = floor($dur / 86400);
        return $number . ' '. transL("common.day_ago" . $transKeySuffix, "天前", ['s'=>($number ? 's' : '')]);
    } elseif ($dur <= 86400 * 30 * 12) {
        $number = floor($dur / (86400 * 30));
        return $number . ' '. transL("common.month_ago".$transKeySuffix, "月前", ['s'=>($number ? 's' : '')]);
    } else {
        $number = floor($dur / (86400 * 30 * 12));
        return $number . ' '. transL("common.year_ago".$transKeySuffix, "年前", ['s'=>($number ? 's' : '')]);
    }
}

/**
 * 数字转化
 */
function formatNumber($number) {
    $length = strlen($number);  //数字长度
    $lang = app('translator')->getLocale();
    if ($lang == 'en-us') { //英文的单位不一样
        if ($length > 9) { //十亿单位
            $str = round($number / 1000000000, 1).transL("common.billion", "十亿");
        } elseif ($length > 6) { //百万单位
            $str = round($number / 1000000, 1).transL("common.million", "百万");
        } elseif ($length > 3) { //千单位
            $str = round($number / 1000, 1).transL("common.thousand", "千");
        } else {
            return $number;
        }
    } else {
        if ($length > 8) { //亿单位
            $str = round($number / 100000000, 1).transL("common.hundred_million", "亿");
        } elseif ($length > 4) { //万单位
            $str = round($number / 10000, 1).transL("common.ten_thousand", "万");
        } elseif ($length > 3) { //千单位
            $str = round($number / 1000, 1).transL("common.thousand", "千");
        } else {
            return $number;
        }
    }
    return $str;
}

/**
 * 格式化金额
 */
function formatAmount($amount = "")
{
    $lang = app('translator')->getLocale();
    if ($lang == 'en-us') {
        return '$' . $amount;
    } else {
        return '￥' . $amount;
    }
}


/**
 * 根据数组某个字段排序
 * @param $array 待排序数组
 * @param $field 排序字段
 * @param bool $desc 是否降序
 */
function sortArrByField(&$array, $field, $desc = false)
{
    $fieldArr = array();
    foreach ($array as $k => $v) {
        $fieldArr[$k] = strtolower($v[$field]);
    }
    $sort = $desc == false ? SORT_ASC : SORT_DESC;
    array_multisort($fieldArr, $sort, $array);
}

//将查询字符中的特殊查询字符处理掉
function formatQueryKey($key)
{
    $key = str_replace('\\', '\\\\\\\\', $key);
    $key = str_replace('%', '\\%', $key);
    $key = str_replace('_', '\\_', $key);
    $key = str_replace("'", "\\'", $key);
    $key = str_replace('"', '\\"', $key);
    if (strstr($key, '[!')) {
        $key = str_replace('[!', '\\[\\!', $key);
    } elseif (strstr($key, '[^')) {
        $key = str_replace('[!', '\\[\\^', $key);
    } elseif (strstr($key, '[')) {
        $key = str_replace('[!', '\\[', $key);
    }
    return $key;
}

//图片转base64
function base64EncodeImage($image_file)
{
    $base64_image = '';
    $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
    $base64_image = chunk_split(base64_encode($image_data));
    return $base64_image;
}

/*
 * 把数组转换成树
 */
function listToTree($list, $root = 0, $pk = 'id', $pid = 'pid', $child = '_child')
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = 0;
            if (isset($data[$pid])) {
                $parentId = $data[$pid];
            }
            if ((string)$root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                } else {
                    //如果找不到父项那么自己就是父项
                    $tree[] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}


/**
 * 过滤并返回整形数组
 * @param array $arr
 */
function filterIntArray($arr)
{
    if ($arr) {
        foreach ($arr as $k => $v) {
            $arr[$k] = (int)$v;
            if (!$arr[$k]) {
                unset($arr[$k]);
            }
        }
    }
    return $arr;
}


//增加redis的连接判断，默认如果连接错误会抛出异常，这边捕获异常，下次会重新连接
function checkRedisPing($config = null)
{
    if ($config) {
        $redis = app('redis')->connection($config);
    } else {
        $redis = app('redis.connection');
    }
    try {
        $redis->ping();
    } catch (\Exception $e) {
    }
}

/**
 * 调试sql语句
 */
function sqlDump()
{
    \DB::listen(function ($query) {
        $i = 0;
        $rawSql = preg_replace_callback('/\?/', function ($matches) use ($query, &$i) {
            $item = isset($query->bindings[$i]) ? $query->bindings[$i] : $matches[0];
            $i++;
            return gettype($item) == 'string' ? "'$item'" : $item;
        }, $query->sql);
//        \Log::info($rawSql);
        echo $rawSql, "\n\n";
    });
}

function transL($id = null, $msg = '', $replace = [], $locale = null)
{
    $data = trans($id, $replace, $locale);
    if (is_array($data)) {
        $data = $data[1];
    }
    if ($replace) {
        foreach ($replace as $key => $item) {
            $data = str_replace('{' . $key . '}', $item, $data);
        }
    }
    return $data ?: $msg;
}

/**
 * 获取分页数组
 * @param array $data
 * @param int $total
 * @param null $perPage
 * @param null $page
 * @param string $pageName
 * @return mixed
 */
function getPaginateArray($data = [], $total = 0, $perPage = null, $page = null, $pageName = 'page')
{
    $page = $page ?: \Illuminate\Pagination\Paginator::resolveCurrentPage($pageName);
    $perPage = $perPage === null ? config('app.app_rows', 15) : $perPage;
    return (new \Illuminate\Pagination\LengthAwarePaginator($data, $total, $perPage, $page, [
        'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
        'pageName' => 'page'
    ]))->toArray();
}

/**
 * 转换秒数为 3h39'25" 的格式
 * @param $seconds
 * @return string
 */
function convertDuration($seconds)
{
    if ($seconds > 0) {
        $str = '';
        $unit = ['"', '\'', 'h'];
        foreach ($unit as $i => $u) {
            $v = $seconds % 60;
            $seconds = floor($seconds / 60);
            if ($v > 0) {
                $str = $v . $u . $str;
            }
            if (!$seconds) {
                break;
            }
        }
        return $str;
    }
    return '0"';
}

//转换大小
function conversionByte($size)
{

    $kb = 1024; // 1KB（Kibibyte，千字节）=1024B，
    $mb = 1024 * $kb; //1MB（Mebibyte，兆字节，简称“兆”）=1024KB，
    $gb = 1024 * $mb; // 1GB（Gigabyte，吉字节，又称“千兆”）=1024MB，
    $tb = 1024 * $gb; // 1TB（Terabyte，万亿字节，太字节）=1024GB，
    $pb = 1024 * $tb; //1PB（Petabyte，千万亿字节，拍字节）=1024TB，
    $fb = 1024 * $pb; //1EB（Exabyte，百亿亿字节，艾字节）=1024PB，
    $zb = 1024 * $fb; //1ZB（Zettabyte，十万亿亿字节，泽字节）= 1024EB，
    $yb = 1024 * $zb; //1YB（Yottabyte，一亿亿亿字节，尧字节）= 1024ZB，
    $bb = 1024 * $yb; //1BB（Brontobyte，一千亿亿亿字节）= 1024YB

    if ($size < $kb) {
        return $size . " B";
    } else if ($size < $mb) {
        return round($size / $kb, 2) . " KB";
    } else if ($size < $gb) {
        return round($size / $mb, 2) . " MB";
    } else if ($size < $tb) {
        return round($size / $gb, 2) . " GB";
    } else if ($size < $pb) {
        return round($size / $tb, 2) . " TB";
    } else if ($size < $fb) {
        return round($size / $pb, 2) . " PB";
    } else if ($size < $zb) {
        return round($size / $fb, 2) . " EB";
    } else if ($size < $yb) {
        return round($size / $zb, 2) . " ZB";
    } else {
        return round($size / $bb, 2) . " YB";
    }
}

//隐藏手机号
function hidePhone($phone)
{
    return substr_replace($phone, '****', 3, 4);
}

//隐藏字符串
function hideString($str){
   $len= mb_strlen($str);
   $retStr='';
   if($len>0){
       if($len>7){
           $retStr=mb_substr($str,0,3).'****'.mb_substr($str,-3);
       }else if($len>3){
           $retStr=mb_substr($str,0,1).'****'.mb_substr($str,-1);
       }else{
           $retStr='****'.mb_substr($str,-1);
       }
   }
   return $retStr;
}

function emailMatch($email)
{
    return validEmail($email);
}

function changeCharset($content, $fromCharset, $toCharset)
{
    if (empty($fromCharset)) {
        $content = mb_convert_encoding($content, $toCharset, 'ascii,UTF-8,GB18030');
    } else {
        if ($fromCharset != $toCharset) {
            $content = iconv($fromCharset, $toCharset, $content);
        }
    }
    return $content;
}

/*
 * 调用Http Get，或Post方法
 */
function getHttpContent($url, $data = null, $timeout = 20, $headers = '', $method = null)
{
    $ch = curl_init();
//    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    if ($data) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
//    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    if (stristr($url, 'https')) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 2);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    if ($method) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $content = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode == 404) {
        $content = null;
    }
//    print_r(curl_error($ch));
    curl_close($ch);
    return $content;
}

function treeToOptionArr(&$tree, $level = 0, $name = 'title', $child = 'sub', $prefix = ' ')
{
    $optionArr = [];
    if (is_array($tree)) {
        foreach ($tree as $t) {
            $p = $t;
            $p[$name] = str_repeat($prefix, $level) . $t[$name];
            if (isset($t[$child])) {
                unset($p[$child]);
                $optionArr[] = $p;
                $optionArr = array_merge($optionArr, treeToOptionArr($t[$child], $level + 1, $name, $child, $prefix));
            } else {
                $optionArr[] = $p;
            }
        }
    }
    return $optionArr;
}

function getOs()
{
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $OS = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/win/i', $OS)) {
            $OS = 'Windows';
        } elseif (preg_match('/mac/i', $OS)) {
            $OS = 'MAC';
        } elseif (preg_match('/linux/i', $OS)) {
            $OS = 'Linux';
        } elseif (preg_match('/unix/i', $OS)) {
            $OS = 'Unix';
        } elseif (preg_match('/bsd/i', $OS)) {
            $OS = 'BSD';
        } else {
            $OS = 'Other';
        }
        return $OS;
    } else {
        return "unknow";
    }
}


//计算时区
function calcTimezone($time = null, $timezone = 8, $isDefault = 0)
{
    if (!$time) {
        return '';
    }
    $default = 8;
    $minute = 0;
    $diff = $default - $timezone;
    if ($isDefault) {
        $pre = '+';
    } else {
        $pre = '-';
    }
    if ($timezone < 8) {
        $diff = $default - $timezone;
        if ($isDefault) {
            $pre = '-';
        } else {
            $pre = '+';
        }
    } else {
        $diff = $timezone - $default;
    }
//    echo $diff.'='.$pre;exit;
    if (ceil($diff) != $diff) {
        $tmp = explode(".", floatval($diff) . "");
        $minute = 60 * floatval("0." . $tmp[1]);
        $diff = intval($diff);
    }
    $time = strtotime($pre . $diff . ' hours', strtotime($time));
    if ($minute) {
        $time = strtotime($pre . $minute . ' minutes', $time);
    }
    return date('Y-m-d H:i', $time);
}

/**
 * 将字符串插入到另一个字符串的指定位置
 * @param $str 原字符串
 * @param $i 位置
 * @param $substr 插入的字符串
 * @return string
 */
function strInsert($str, $i, $substr)
{
    $startstr = '';
    $laststr = '';
    for ($j = 0; $j < $i; $j++) {
        $startstr .= $str[$j];
    }
    for ($j = $i; $j < strlen($str); $j++) {
        $laststr .= $str[$j];
    }
    $str = ($startstr . $substr . $laststr);
    return $str;
}

// 生成GUID
function createGuid()
{
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45);// "-"
    $uuid = substr($charid, 0, 8) . $hyphen
        . substr($charid, 8, 4) . $hyphen
        . substr($charid, 12, 4) . $hyphen
        . substr($charid, 16, 4) . $hyphen
        . substr($charid, 20, 12);
    return strtolower($uuid);
}

function getEmailName($email)
{
    $tmp = explode("@", $email);
    return $tmp[0];
}

if (!function_exists('ftok')) {
    function ftok($filePath, $projectId)
    {
        $fileStats = stat($filePath);
        if (!$fileStats) {
            return -1;
        }
        return sprintf('%u',
            ($fileStats['ino'] & 0xffff) | (($fileStats['dev'] & 0xff) << 16) | ((ord($projectId) & 0xff) << 24)
        );
    }
}

function unicode2utf8($str)
{
    if (!$str) return $str;
    $decode = json_decode($str);
    if ($decode) return $decode;
    $str = '["' . $str . '"]';
    $decode = json_decode($str);
    if (count($decode) == 1) {
        return $decode[0];
    }
    return $str;
}


/**
 * 电话过滤 空格、+、- 字符
 * @param $phone
 * @return null|string|string[]
 */
function filterPhone($phone)
{
    return "'" . preg_replace('/(\s+)|(\-+)|(\++)/', '', $phone) . "'";
}

/*
 * 邮箱过滤 “.”“，”“ ”（空格）“-”
 * @param $email
 * @return null|string|string[]
 * */
function filterEmail($email)
{
    $email = trim($email);
    $email = trim($email, ".");
    $email = trim($email, ";");
    $email = trim($email, "-");
    $email = trim($email, ",");
    return $email;
}

/**
 * url解析
 * @param $input
 * @return bool|string
 */
function base64_url_decode($input)
{
    return base64_decode(strtr($input, '-_', '+/'));
}

/*
 * 判断手机号是否合法
 * */
function validPhone($phone)
{
    $phone = trim($phone);
    $pattern = "/^1[345789]\d{9}$/";
    if (preg_match($pattern, $phone)) {
        return true;
    } else {
        return false;
    }
}


/**
 * 输出导出excel文件到浏览器
 * @param $formatData
 * @param $fileName
 * @param $header
 * @param null $expire
 */
function outputExcelDumpFile(&$formatData, $fileName, $header, $expire = null, $setTimeLimit = 600)
{
    $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use (&$formatData, &$header, $setTimeLimit) {
        set_time_limit($setTimeLimit);
        $phpExcel = new \PHPExcel();
        $phpExcel->getProperties()->setCreator("matchexpo");
        $phpExcel->setActiveSheetIndex(0);
        $phpExcel->getActiveSheet()->setTitle('Sheet1');
        $startCell = 'A1';
        if (!empty($header)) {
            $phpExcel->getActiveSheet()->fromArray($header, null, $startCell);
            $startCell = 'A2';
        }
        $phpExcel->getActiveSheet()->fromArray($formatData, null, $startCell);
        $objWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5');
        $objWriter->save("php://output");
        ini_restore('max_execution_time');
    });
    return setDumpFileHead($response, $fileName, null, 'application/vnd.ms-excel');
}

/**
 * 设置导出文件header
 * @param $response
 * @param $fileName
 * @param integer $expire
 * @param string $contentType
 * @return mixed
 */
function setDumpFileHead($response, $fileName, $expire = null, $contentType = null)
{
    if (!$expire) {
        $expire = 180;
    }
    if (!$contentType) {
        $contentType = 'application/octet-stream';
    }
    if ((string)$fileName !== '') {
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'max-age=' . $expire);
        $response->headers->set('Cache-Control', 'post-check=0, pre-check=0', false);
        $response->headers->set('Expires', gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
        $response->headers->set('Last-Modified', gmdate("D, d M Y H:i:s", time()) . "GMT");
        $response->headers->set('Content-Disposition', 'attachment; filename="' . rawurlencode($fileName) . '"; filename*=utf-8\'\'' . rawurlencode($fileName));
        $response->headers->set('X-Accel-Buffering', 'no');
    }
    $response->headers->set('Content-type', $contentType);
    return $response;
}

/**
 * 输出导出csv文件到浏览器
 * @param $formatData 数据数组
 * @param $fileName 导出文件名
 * @param callable $rowHandler 处理数据的回调函数,接收两个参数:数据数组和文件句柄(&$formatData, $fp)
 * @param $header 表头数组
 * @param null $expire 过期秒数
 * @return mixed
 * @throws \Exception
 */
function outputCsvDumpFile(&$formatData, $fileName, callable $rowHandler, $header, $expire = null, $setTimeLimit = 600)
{
    //节省内存
    $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use (&$formatData, $rowHandler, &$header, $setTimeLimit) {
        try {
            set_time_limit($setTimeLimit);
            $fp = fopen('php://output', 'w');
            //add BOM to fix UTF-8 in Excel
            fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            //写表头
            if ($header) {
                fputcsv($fp, $header);
            }
            //写内容
            $rowHandler($formatData, $fp);
            fclose($fp);
            ini_restore('max_execution_time');
        } catch (\Exception $e) {
            ini_restore('max_execution_time');
            if (isset($fp) && is_resource($fp)) {
                @fclose($fp);
            }
            throw $e;
        }
    });
    return setDumpFileHead($response, $fileName, $expire);
}

/**
 * 通过callback导出文件
 * @param callable $callback
 * @param $fileName
 * @param null $expire
 * @return mixed
 */
function outputDumpFileDynamic(callable $callback, $fileName, $expire = null, $contentType = null)
{
    $response = new \Symfony\Component\HttpFoundation\StreamedResponse($callback);
    return setDumpFileHead($response, $fileName, $expire, $contentType);
}

/**
 * 根据数组字段检验数组唯一性
 * @param $keys 检验数组键值
 * @param $arrs 检验数组
 * @param $flitNull 是否检验空值
 * @return array
 */
function checkArrayUnique($keys, $arrs, $flitNull = true)
{
    $unique_arr = array();
    $repeat_arr = array();
    foreach ($keys as $key) {
        foreach ($arrs as $k => $v) {
            if (!isset($v[$key]) && $flitNull) break;
            $str = "";
            foreach ([$key] as $a => $b) {
                $str .= "{$v[$b]},";
            }
            if (!in_array($str, $unique_arr)) {
                $unique_arr[] = $str;
            } else {
                if ($flitNull) $repeat_arr[$key] = $v;
                if (!$flitNull && !empty($v[$b])) $repeat_arr[$key] = $v;
            }
        }
    }
    return $repeat_arr;
}

/**
 * 过滤掉emoji表情
 * @param $str
 * @return null|string|string[]
 */
function stripEmoji($str)
{
    $str = preg_replace_callback(
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);

    return $str;
}

/**
 * 过滤4字节以上的utf8字符
 * @param $string
 * @return string|string[]|null
 */
function removeUtf8Mb4Char($string)
{
    $regx = '/[^\x{00}-\x{7F}\x{80}-\x{07FF}\x{0800}-\x{D7FF}\x{E000}-\x{FFFF}]/u';
    $result = preg_replace($regx, '', $string);
    if ($result !== null) {
        $string = $result;
    }
    return $string;
}

/**
 * 格式化秒为时分秒
 * @param $seconds
 * @return string
 */
function formatSeconds($seconds)
{
    $h = floor($seconds / 3600);
    $str = gmstrftime("%M'%S\"", $seconds);
    if ($h > 0) {
        return $h . 'h' . $str;
    }

    return $str;
}


/**
 * 过滤Emoji表情
 * @param $str
 * @return string|string[]|null
 */
function filterEmoji($str)
{
    $out = preg_replace_callback(
        '/./u', function (array $match) {
        return strlen($match[0]) >= 4 ? '' : $match[0];
    }, $str);

    return $out;
}

function getRequestId()
{
    $ip = request()->header('request_id');
    $ip = !empty($ip) ? (is_array($ip) ? current($ip) : $ip) : '';
    return $ip;
}


if (!function_exists('starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        return \Illuminate\Support\Str::startsWith($haystack, $needles);
    }
}

if (!function_exists('snake_case')) {


    function snake_case($value, $delimiter = '_')
    {
        return \Illuminate\Support\Str::snake($value, $delimiter);
    }
}
/**
 * 对图片前面的网址进行处理
 * @param $img
 * @param string $root
 * @return string
 */
function checkRootImg($img, $root = '')
{
    $root = $root ?: config('app.attachment_host');
    if ($img && !stristr($img, $root)) {
        $img = $root . $img;
    }
    return $img;
}

/**
 * 处理转化密码加密
 * @param $password
 * @return string
 */
function converPassword($password)
{
    return md5($password);
}

/**
 * 生成随机数
 */
function random($length, $numeric = 0)
{
    mt_srand((double)microtime() * 1000000);
    if ($numeric) {
        $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
    } else {
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
    }
    return $hash;
}

/**
 * 获取随机字符串
 * @param $prefix
 * @return string
 */
function getRandomStr($prefix = 'WX')
{
    $addHead = '';
    $addTail = '';
    $i = 0;
    while ($i < 3) {
        $addHead .= mt_rand(0, 9);
        $addTail .= mt_rand(0, 9);
        $i = $i + 1;
    }
    return $prefix . $addHead . time() . $addTail;
}

/**
 * 构造返回页面数据
 * @param $data //数据
 * @param $formNb //开始数量
 * @param $page //页数
 * @param $perPageSize //每页数量
 * @param $total //总数量
 * @return mixed
 */
function buildPage($data = [], $formNb = 0, $page = 1, $perPageSize = 10, $total = 0)
{
    $lastPage = ceil($total / $perPageSize);
    $toNb = $page * $perPageSize;
    if (empty($formNb)) {
        $formNb = $toNb - $perPageSize;
    }
    if ($toNb > $total) {
        $toNb = $total;
    }
    $resultDdata = array(
        'current_page' => $page,
        'data' => $data,
        'from' => $formNb,
        'last_page' => $lastPage,
        'per_page' => $perPageSize,
        'to' => $toNb,
        'total' => $total,
    );
    return $resultDdata;
}

/**
 * 获取岁数
 * @param DateTime $date 日期
 * @param integer $type 类型 1虚岁 2周岁
 * */
function getAgeByBirth($date, $type = 1)
{
    $nowYear = date("Y", time());
    $nowMonth = date("m", time());
    $nowDay = date("d", time());
    $birthYear = date("Y", $date);
    $birthMonth = date("m", $date);
    $birthDay = date("d", $date);
    if ($type == 1) {
        $age = $nowYear - ($birthYear - 1);
    } else {
        if ($nowMonth < $birthMonth) {
            $age = $nowYear - $birthYear - 1;
        } elseif ($nowMonth == $birthMonth) {
            if ($nowDay < $birthDay) {
                $age = $nowYear - $birthYear - 1;
            } else {
                $age = $nowYear - $birthYear;
            }
        } else {
            $age = $nowYear - $birthYear;
        }
    }
    return $age;
}

if (!function_exists('config_path')) {
    /* Get the configuration path. @param string $path @return string */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

/**
 * 按key对二维数组去重
 */
if (!function_exists('assoc_unique')) {
    function assoc_unique($arr, $key)
    {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }
}

/**
 * 内部的一维数组不能完全相同，而删除重复项
 */
if (!function_exists('array_unique_fb')) {
    function array_unique_fb($array)
    {
        $serializeArrs = array_map('serialize', $array);
        $uniqueArrs = array_unique($serializeArrs);
        $unserializeArrs = array_map('unserialize', $uniqueArrs);
        return $unserializeArrs;
    }
}

/**
 * 汉语 - 给用户自动生成昵称
 * @param int $type 1生成昵称，2生成姓名
 * @return mixed|string
 */
if (!function_exists('randNickName')) {
    function randNickName($type = 1)
    {
        /**
         * 随机昵称 形容词
         */
        $nick_name_tou = ['迷你的', '鲜艳的', '飞快的', '真实的', '清新的', '幸福的', '可耐的', '快乐的', '冷静的', '醉熏的', '潇洒的', '糊涂的', '积极的', '冷酷的', '深情的', '粗暴的',
            '温柔的', '可爱的', '愉快的', '义气的', '认真的', '威武的', '帅气的', '传统的', '潇洒的', '漂亮的', '自然的', '专一的', '听话的', '昏睡的', '狂野的', '等待的', '搞怪的',
            '幽默的', '魁梧的', '活泼的', '开心的', '高兴的', '超帅的', '留胡子的', '坦率的', '直率的', '轻松的', '痴情的', '完美的', '精明的', '无聊的', '有魅力的', '丰富的', '繁荣的',
            '饱满的', '炙热的', '暴躁的', '碧蓝的', '俊逸的', '英勇的', '健忘的', '故意的', '无心的', '土豪的', '朴实的', '兴奋的', '幸福的', '淡定的', '不安的', '阔达的', '孤独的',
            '独特的', '疯狂的', '时尚的', '落后的', '风趣的', '忧伤的', '大胆的', '爱笑的', '矮小的', '健康的', '合适的', '玩命的', '沉默的', '斯文的', '香蕉', '苹果', '鲤鱼', '鳗鱼',
            '任性的', '细心的', '粗心的', '大意的', '甜甜的', '酷酷的', '健壮的', '英俊的', '霸气的', '阳光的', '默默的', '大力的', '孝顺的', '忧虑的', '着急的', '紧张的', '善良的',
            '凶狠的', '害怕的', '重要的', '危机的', '欢喜的', '欣慰的', '满意的', '跳跃的', '诚心的', '称心的', '如意的', '怡然的', '娇气的', '无奈的', '无语的', '激动的', '愤怒的',
            '美好的', '感动的', '激情的', '激昂的', '震动的', '虚拟的', '超级的', '寒冷的', '精明的', '明理的', '犹豫的', '忧郁的', '寂寞的', '奋斗的', '勤奋的', '现代的', '过时的',
            '稳重的', '热情的', '含蓄的', '开放的', '无辜的', '多情的', '纯真的', '拉长的', '热心的', '从容的', '体贴的', '风中的', '曾经的', '追寻的', '儒雅的', '优雅的', '开朗的',
            '外向的', '内向的', '清爽的', '文艺的', '长情的', '平常的', '单身的', '伶俐的', '高大的', '懦弱的', '柔弱的', '爱笑的', '乐观的', '耍酷的', '酷炫的', '神勇的', '年轻的',
            '唠叨的', '瘦瘦的', '无情的', '包容的', '顺心的', '畅快的', '舒适的', '靓丽的', '负责的', '背后的', '简单的', '谦让的', '彩色的', '缥缈的', '欢呼的', '生动的', '复杂的',
            '慈祥的', '仁爱的', '魔幻的', '虚幻的', '淡然的', '受伤的', '雪白的', '高高的', '糟糕的', '顺利的', '闪闪的', '羞涩的', '缓慢的', '迅速的', '优秀的', '聪明的', '含糊的',
            '俏皮的', '淡淡的', '坚强的', '平淡的', '欣喜的', '能干的', '灵巧的', '友好的', '机智的', '机灵的', '正直的', '谨慎的', '俭朴的', '殷勤的', '虚心的', '辛勤的', '自觉的',
            '无私的', '无限的', '踏实的', '老实的', '现实的', '可靠的', '务实的', '拼搏的', '个性的', '粗犷的', '活力的', '成就的', '勤劳的', '单纯的', '落寞的', '朴素的', '悲凉的',
            '忧心的', '洁净的', '清秀的', '自由的', '小巧的', '单薄的', '贪玩的', '刻苦的', '干净的', '壮观的', '和谐的', '文静的', '调皮的', '害羞的', '安详的', '自信的', '端庄的',
            '坚定的', '美满的', '舒心的', '温暖的', '专注的', '勤恳的', '美丽的', '腼腆的', '优美的', '甜美的', '甜蜜的', '整齐的', '动人的', '典雅的', '尊敬的', '舒服的', '妩媚的',
            '秀丽的', '喜悦的', '甜美的', '彪壮的', '强健的', '大方的', '俊秀的', '聪慧的', '迷人的', '陶醉的', '悦耳的', '动听的', '明亮的', '结实的', '魁梧的', '标致的', '清脆的',
            '敏感的', '光亮的', '大气的', '老迟到的', '知性的', '冷傲的', '呆萌的', '野性的', '隐形的', '笑点低的', '微笑的', '笨笨的', '难过的', '沉静的', '火星上的', '失眠的',
            '安静的', '纯情的', '要减肥的', '迷路的', '烂漫的', '哭泣的', '贤惠的', '苗条的', '温婉的', '发嗲的', '会撒娇的', '贪玩的', '执着的', '眯眯眼的', '花痴的', '想人陪的',
            '眼睛大的', '高贵的', '傲娇的', '心灵美的', '爱撒娇的', '细腻的', '天真的', '怕黑的', '感性的', '飘逸的', '怕孤独的', '忐忑的', '高挑的', '傻傻的', '冷艳的', '爱听歌的',
            '还单身的', '怕孤单的', '懵懂的'];
        $nick_name_wei = ['嚓茶', '皮皮虾', '皮卡丘', '马里奥', '小霸王', '凉面', '便当', '毛豆', '花生', '可乐', '灯泡', '哈密瓜', '野狼', '背包', '眼神', '缘分', '雪碧', '人生', '牛排',
            '蚂蚁', '飞鸟', '灰狼', '斑马', '汉堡', '悟空', '巨人', '绿茶', '自行车', '保温杯', '大碗', '墨镜', '魔镜', '煎饼', '月饼', '月亮', '星星', '芝麻', '啤酒', '玫瑰',
            '大叔', '小伙', '哈密瓜，数据线', '太阳', '树叶', '芹菜', '黄蜂', '蜜粉', '蜜蜂', '信封', '西装', '外套', '裙子', '大象', '猫咪', '母鸡', '路灯', '蓝天', '白云',
            '星月', '彩虹', '微笑', '摩托', '板栗', '高山', '大地', '大树', '电灯胆', '砖头', '楼房', '水池', '鸡翅', '蜻蜓', '红牛', '咖啡', '机器猫', '枕头', '大船', '诺言',
            '钢笔', '刺猬', '天空', '飞机', '大炮', '冬天', '洋葱', '春天', '夏天', '秋天', '冬日', '航空', '毛衣', '豌豆', '黑米', '玉米', '眼睛', '老鼠', '白羊', '帅哥', '美女',
            '季节', '鲜花', '服饰', '裙子', '白开水', '秀发', '大山', '火车', '汽车', '歌曲', '舞蹈', '老师', '导师', '方盒', '大米', '麦片', '水杯', '水壶', '手套', '鞋子', '自行车',
            '鼠标', '手机', '电脑', '书本', '奇迹', '身影', '香烟', '夕阳', '台灯', '宝贝', '未来', '皮带', '钥匙', '心锁', '故事', '花瓣', '滑板', '画笔', '画板', '学姐', '店员',
            '电源', '饼干', '宝马', '过客', '大白', '时光', '石头', '钻石', '河马', '犀牛', '西牛', '绿草', '抽屉', '柜子', '往事', '寒风', '路人', '橘子', '耳机', '鸵鸟', '朋友',
            '苗条', '铅笔', '钢笔', '硬币', '热狗', '大侠', '御姐', '萝莉', '毛巾', '期待', '盼望', '白昼', '黑夜', '大门', '黑裤', '钢铁侠', '哑铃', '板凳', '枫叶', '荷花', '乌龟',
            '仙人掌', '衬衫', '大神', '草丛', '早晨', '心情', '茉莉', '流沙', '蜗牛', '战斗机', '冥王星', '猎豹', '棒球', '篮球', '乐曲', '电话', '网络', '世界', '中心', '鱼', '鸡', '狗',
            '老虎', '鸭子', '雨', '羽毛', '翅膀', '外套', '火', '丝袜', '书包', '钢笔', '冷风', '八宝粥', '烤鸡', '大雁', '音响', '招牌', '胡萝卜', '冰棍', '帽子', '菠萝', '蛋挞', '香水',
            '泥猴桃', '吐司', '溪流', '黄豆', '樱桃', '小鸽子', '小蝴蝶', '爆米花', '花卷', '小鸭子', '小海豚', '日记本', '小熊猫', '小懒猪', '小懒虫', '荔枝', '镜子', '曲奇', '金针菇',
            '小松鼠', '小虾米', '酒窝', '紫菜', '金鱼', '柚子', '果汁', '百褶裙', '项链', '帆布鞋', '火龙果', '奇异果', '煎蛋', '唇彩', '小土豆', '高跟鞋', '戒指', '雪糕', '睫毛', '铃铛',
            '手链', '香氛', '红酒', '月光', '酸奶', '银耳汤', '咖啡豆', '小蜜蜂', '小蚂蚁', '蜡烛', '棉花糖', '向日葵', '水蜜桃', '小蝴蝶', '小刺猬', '小丸子', '指甲油', '康乃馨', '糖豆',
            '薯片', '口红', '超短裙', '乌冬面', '冰淇淋', '棒棒糖', '长颈鹿', '豆芽', '发箍', '发卡', '发夹', '发带', '铃铛', '小馒头', '小笼包', '小甜瓜', '冬瓜', '香菇', '小兔子',
            '含羞草', '短靴', '睫毛膏', '小蘑菇', '跳跳糖', '小白菜', '草莓', '柠檬', '月饼', '百合', '纸鹤', '小天鹅', '云朵', '芒果', '面包', '海燕', '小猫咪', '龙猫', '唇膏', '鞋垫',
            '羊', '黑猫', '白猫', '万宝路', '金毛', '山水', '音响', '纸飞机', '烧鹅'];
        /**
         * 百家姓
         */
        $arrXing = ['赵', '钱', '孙', '李', '周', '吴', '郑', '王', '冯', '陈', '褚', '卫', '蒋', '沈', '韩', '杨', '朱', '秦', '尤', '许', '何', '吕', '施', '张', '孔', '曹', '严', '华', '金', '魏', '陶', '姜', '戚', '谢', '邹',
            '喻', '柏', '水', '窦', '章', '云', '苏', '潘', '葛', '奚', '范', '彭', '郎', '鲁', '韦', '昌', '马', '苗', '凤', '花', '方', '任', '袁', '柳', '鲍', '史', '唐', '费', '薛', '雷', '贺', '倪', '汤', '滕', '殷', '罗',
            '毕', '郝', '安', '常', '傅', '卞', '齐', '元', '顾', '孟', '平', '黄', '穆', '萧', '尹', '姚', '邵', '湛', '汪', '祁', '毛', '狄', '米', '伏', '成', '戴', '谈', '宋', '茅', '庞', '熊', '纪', '舒', '屈', '项', '祝',
            '董', '梁', '杜', '阮', '蓝', '闵', '季', '贾', '路', '娄', '江', '童', '颜', '郭', '梅', '盛', '林', '钟', '徐', '邱', '骆', '高', '夏', '蔡', '田', '樊', '胡', '凌', '霍', '虞', '万', '支', '柯', '管', '卢', '莫',
            '柯', '房', '裘', '缪', '解', '应', '宗', '丁', '宣', '邓', '单', '杭', '洪', '包', '诸', '左', '石', '崔', '吉', '龚', '程', '嵇', '邢', '裴', '陆', '荣', '翁', '荀', '于', '惠', '甄', '曲', '封', '储', '仲', '伊',
            '宁', '仇', '甘', '武', '符', '刘', '景', '詹', '龙', '叶', '幸', '司', '黎', '溥', '印', '怀', '蒲', '邰', '从', '索', '赖', '卓', '屠', '池', '乔', '胥', '闻', '莘', '党', '翟', '谭', '贡', '劳', '逄', '姬', '申',
            '扶', '堵', '冉', '宰', '雍', '桑', '寿', '通', '燕', '浦', '尚', '农', '温', '别', '庄', '晏', '柴', '瞿', '阎', '连', '习', '容', '向', '古', '易', '廖', '庾', '终', '步', '都', '耿', '满', '弘', '匡', '国', '文',
            '寇', '广', '禄', '阙', '东', '欧', '利', '师', '巩', '聂', '关', '荆', '司马', '上官', '欧阳', '夏侯', '诸葛', '闻人', '东方', '赫连', '皇甫', '尉迟', '公羊', '澹台', '公冶', '宗政', '濮阳', '淳于', '单于', '太叔',
            '申屠', '公孙', '仲孙', '轩辕', '令狐', '徐离', '宇文', '长孙', '慕容', '司徒', '司空', '皮'];
        /**
         * 名
         */
        $arrMing = ['伟', '刚', '勇', '毅', '俊', '峰', '强', '军', '平', '保', '东', '文', '辉', '力', '明', '永', '健', '世', '广', '志', '义', '兴', '良', '海', '山', '仁', '波', '宁', '贵', '福', '生', '龙', '元', '全'
            , '国', '胜', '学', '祥', '才', '发', '武', '新', '利', '清', '飞', '彬', '富', '顺', '信', '子', '杰', '涛', '昌', '成', '康', '星', '光', '天', '达', '安', '岩', '中', '茂', '进', '林', '有', '坚', '和', '彪', '博', '诚'
            , '先', '敬', '震', '振', '壮', '会', '思', '群', '豪', '心', '邦', '承', '乐', '绍', '功', '松', '善', '厚', '庆', '磊', '民', '友', '裕', '河', '哲', '江', '超', '浩', '亮', '政', '谦', '亨', '奇', '固', '之', '轮', '翰'
            , '朗', '伯', '宏', '言', '若', '鸣', '朋', '斌', '梁', '栋', '维', '启', '克', '伦', '翔', '旭', '鹏', '泽', '晨', '辰', '士', '以', '建', '家', '致', '树', '炎', '德', '行', '时', '泰', '盛', '雄', '琛', '钧', '冠', '策'
            , '腾', '楠', '榕', '风', '航', '弘', '秀', '娟', '英', '华', '慧', '巧', '美', '娜', '静', '淑', '惠', '珠', '翠', '雅', '芝', '玉', '萍', '红', '娥', '玲', '芬', '芳', '燕', '彩', '春', '菊', '兰', '凤', '洁', '梅', '琳'
            , '素', '云', '莲', '真', '环', '雪', '荣', '爱', '妹', '霞', '香', '月', '莺', '媛', '艳', '瑞', '凡', '佳', '嘉', '琼', '勤', '珍', '贞', '莉', '桂', '娣', '叶', '璧', '璐', '娅', '琦', '晶', '妍', '茜', '秋', '珊', '莎'
            , '锦', '黛', '青', '倩', '婷', '姣', '婉', '娴', '瑾', '颖', '露', '瑶', '怡', '婵', '雁', '蓓', '纨', '仪', '荷', '丹', '蓉', '眉', '君', '琴', '蕊', '薇', '菁', '梦', '岚', '苑', '婕', '馨', '瑗', '琰', '韵', '融', '园'
            , '艺', '咏', '卿', '聪', '澜', '纯', '毓', '悦', '昭', '冰', '爽', '琬', '茗', '羽', '希', '欣', '飘', '育', '滢', '馥', '筠', '柔', '竹', '霭', '凝', '晓', '欢', '霄', '枫', '芸', '菲', '寒', '伊', '亚', '宜', '可', '姬'
            , '舒', '影', '荔', '枝', '丽', '阳', '妮', '宝', '贝', '初', '程', '梵', '罡', '恒', '鸿', '桦', '骅', '剑', '娇', '纪', '宽', '苛', '灵', '玛', '媚', '琪', '晴', '容', '睿', '烁', '堂', '唯', '威', '韦', '雯', '苇', '萱'
            , '阅', '彦', '宇', '雨', '洋', '忠', '宗', '曼', '紫', '逸', '贤', '蝶', '菡', '绿', '蓝', '儿', '翠', '烟'];
        $nick_name = '';
        switch ($type) {
            case 1:
                $tou_num = rand(0, count($nick_name_tou) - 1);
                $wei_num = rand(0, count($nick_name_wei) - 1);
                $nick_name = $nick_name_tou[$tou_num] . $nick_name_wei[$wei_num];
                break;
            case 2:
                $nick_name = $arrXing[mt_rand(0, count($arrXing) - 1)];
                for ($i = 1; $i <= 3; $i++) {
                    $nick_name .= (mt_rand(0, 1) ? $arrMing[mt_rand(0, count($arrMing) - 1)] : $arrMing[mt_rand(0, count($arrMing) - 1)]);
                }
                break;
        }
        return $nick_name;
    }
}
if (!function_exists('getMillisecond')) {
    function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}

/**
 * 下划线转驼峰
 * @param $str
 * @return array|string|string[]|null
 */
function convertUnderline($str)
{
    $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function($matches){
        return strtoupper($matches[2]);
    }, $str);
    return $str;
}

/**
 * 返回完整的文件地址
 */
if (!function_exists('formatFileUrl')) {
    function formatFileUrl($url)
    {
        if (empty($url)) {
            return $url;
        }
        if (strpos($url, "https://") !== false || strpos($url, "http://") !== false) {
            return $url;
        }
        $url = trim($url, "/");
        return config("obs.obsDomain") . "/" . $url;
    }
}
/**
 * 是否海外版本
 * @return bool
 */
function isOverseasEdition()
{
    return boolval(config('app.overseas_edition'));
}

/**
 * 格式化时间
 * @param $dateTime
 * @return string
 */
function formatLocaleTime($dateTime)
{
    $time = strtotime($dateTime);
    if (!isOverseasEdition()) {
        $weekArray = ["日", "一", "二", "三", "四", "五", "六"];
        return date('Y年m月d日 H时i分', $time).
            ' 星期'.$weekArray[date('w', $time)];
    } else {
        $month = ["January","February", "March", "April", "May", "June", "July",
            "August", "September", "October", "November", "December"][intval(date("m", $time))];
        return date('H:i - l, d ', $time).$month.' '.
            date('Y', $time).'(China, Singapore, Perth)';
    }
}

//获取当前语言，包含是否国际版标识
function getLocale()
{
    $isOverseas = boolval(config('app.overseas_edition'));
    $siteName = 'inside';
    if ($isOverseas) {
        $siteName = 'overseas';
    }
    $translator = app('translator');
    $curLang = $translator->getLocale();
    return [
        'site' => $siteName,
        'language' => $curLang
    ];
}

/**
 * 获取站点语言
 * @return string
 */
function getSiteLocale()
{
    return isOverseasEdition() ? 'en-us' : 'zh-cn';
}

/**
 * 登录用户信息
 */
function getAccessToken()
{
    $tokenKey = 'inside';
    if (config('app.overseas_edition')) {
        $tokenKey = 'overseas';
    }
    $tokenKey .= '_' . config('app.global_access_token_key');
    return $_COOKIE[$tokenKey] ?? '';
}

/**
 * 根据id生成邀请码
 */
function createInviteCode($userId, $length = 4)
{
    $key = 'CUTW4JGSHKVY58QNMEP62XRZ7AFB31D9'; // key顺序不能变，用于邀请码解码用
    $octal = strlen($key);

    $code = '';
    // 转进制
    while ($userId > 0) {
        $mod = $userId % $octal; // 求模
        $userId = ($userId - $mod) / $octal;
        $code = $key[$mod] . $code;
    }
    // 最小4位
    return str_pad($code, $length, '0', STR_PAD_LEFT); // 不足用0补充;
}

/**
 * 邀请码解码
 */
function inviteCodeDecode($code)
{
    $key = 'CUTW4JGSHKVY58QNMEP62XRZ7AFB31D9';
    $octal = strlen($key);

    if (strrpos($code, '0') !== false) {
        $code = substr($code, strrpos($code, '0') + 1);
    }

    $code = strrev($code);
    $len = strlen($code);
    $userId = 0;
    for ($i = 0; $i < $len; $i++) {
        $userId += strpos($key, $code[$i]) * ($octal ** $i);
    }
    return $userId;
}

function aesEncrypt($str, $key = null)
{
    $aesKey = $key ?? config('app.aes_key');
    $privateKey = str_pad($aesKey, 16, "\0");
    $iv = str_pad($aesKey, 16, "\0");
    $str = str_pad($str, 16, "\0");

    $content = openssl_encrypt($str, 'aes-128-cbc', $privateKey, OPENSSL_PKCS1_PADDING, $iv);
    $content = base64_encode($content);
    return $content;
}
 // 提取图片
function extractImageUrls($html) {
    // 创建一个 DOMDocument 对象
    $dom = new DOMDocument;

    // Suppress warnings due to malformed HTML (e.g. missing doctype)
    @$dom->loadHTML($html);

    // 获取所有的 <img> 标签
    $images = $dom->getElementsByTagName('img');

    $imageUrls = [];

    // 遍历每一个 <img> 标签，获取其 src 属性
    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        // 如果 src 属性不为空，添加到数组中
        if (!empty($src)) {
            $imageUrls[] = $src;
        }
    }

    return $imageUrls;
}

