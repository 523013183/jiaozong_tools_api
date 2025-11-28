<?php

namespace App\User\Services;
use App\Base\Exceptions\ApiException;
use App\Base\Library\AES;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class WechatService
{
    protected $cache = true;

    protected $cacheBucket = 'wechat:';

    protected $client = null;

    protected $appId = null;
    protected $appSecret = null;
    public function __construct($config)
    {
        $client = new Client();
        $this->client = $client;
        $this->appId = $config['app_id'];
        $this->appSecret = $config['secret'];
    }

    public function getAccessToken()
    {
        $accessTokenKey = 'wechat:access:key';
        $cache = Cache::get($accessTokenKey);
        if($cache) {
            return $cache;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/stable_token';
        $formParams = [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'grant_type' => 'client_credential'
        ];
        try {
            $options = [
                'json' => $formParams,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ];
            $response = $this->client->request('POST', $url, $options);
            // 获取响应体并解码为数组
            $body = json_decode($response->getBody()->getContents(), true);
            if (!empty($body['errcode'])) {
                $this->throwApiError($options, $url.json_encode($body['errcode']).json_encode($body['errmsg']));
            } else {
                Cache::put($accessTokenKey, $body['access_token'], 240);
                return $body['access_token'];
            }
        } catch (RequestException $e) {
            $this->throwApiError($options, $e->getMessage(), $e->getCode());
        }
    }

    public function getSessionInfo($code)
    {
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        // 准备请求参数
        $formParams = [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        try {
            $options = [
                'query' => $formParams
            ];
            $response = $this->client->request('GET', $url, $options);
            // 获取响应体并解码为数组
            $body = json_decode($response->getBody()->getContents(), true);
            if (!empty($body['errcode'])) {
                $this->throwApiError($options, $url.json_encode($body['errcode']).json_encode($body['errmsg']));
            } else {
                // $openid = $data['openid'];
                // $sessionKey = $data['session_key'];
                return $body;
            }
        } catch (RequestException $e) {
            $this->throwApiError($options, $e->getMessage(), $e->getCode());
        }
    }

    public function getUserPhoneNumber($code)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber';
        // 准备请求参数
        try {
            $options = [
                'query' => ['access_token' => $this->getAccessToken()],
                'json' => ['code' => $code],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ];
            $response = $this->client->request('POST', $url, $options);
            // 获取响应体并解码为数组
            $body = json_decode($response->getBody()->getContents(), true);
            if (!empty($body['errcode'])) {
                $this->throwApiError($options, $url.json_encode($body['errcode']).json_encode($body['errmsg']));
            } else {
                /*
                 * {
                    "errcode":0,
                    "errmsg":"ok",
                    "phone_info": {
                        "phoneNumber":"xxxxxx",
                        "purePhoneNumber": "xxxxxx",
                        "countryCode": 86,
                        "watermark": {
                            "timestamp": 1637744274,
                            "appid": "xxxx"
                        }
                    }
                   }
                 */
                return $body;
            }
        } catch (RequestException $e) {
            $this->throwApiError($options, $e->getMessage(), $e->getCode());
        }
    }

    public function getWxACodeUnLimit($scene, $extra = [])
    {
        //$page, $envVersion = 'release', $checkPath = false, $width = 430, $autoColor = false, $lineColor = '{"r":0,"g":0,"b":0}', $isHyaline = false
        //$envVersion 要打开的小程序版本。正式版为 "release"，体验版为 "trial"，开发版为 "develop"。默认是正式版。
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit';
        // 准备请求参数
        try {
            $options = [
                'query' => ['access_token' => $this->getAccessToken()],
                'json' => ['scene' => $scene ?? uniqid()],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ];
            if (isset($extra['page'])) {
                $options['json']['page'] = $extra['page'];
            }
            if (isset($extra['check_path'])) {
                $options['json']['check_path'] = $extra['check_path'];
            }
            if (isset($extra['env_version'])) {
                $options['json']['env_version'] = $extra['env_version'];
            }
            if (isset($extra['width'])) {
                $options['json']['width'] = $extra['width'];
            }
            if (isset($extra['auto_color'])) {
                $options['json']['auto_color'] = $extra['auto_color'];
            }
            if (isset($extra['line_color'])) {
                $options['json']['line_color'] = $extra['line_color'];
            }
            if (isset($extra['is_hyaline'])) {
                $options['json']['is_hyaline'] = $extra['is_hyaline'];
            }
            $response = $this->client->request('POST', $url, $options);
            // 获取图片流
            $imageStream = $response->getBody()->getContents();;
            $jsonStream = json_decode($imageStream, true);
            if (!empty($jsonStream['errcode'])) {
                $this->throwApiError($options, $url.json_encode($jsonStream['errcode']).json_encode($jsonStream['errmsg']));
            }
            return $imageStream;
        } catch (RequestException $e) {
            $this->throwApiError($options, $e->getMessage(), $e->getCode());
        }
    }

    public function decryptData(string $sessionKey, string $iv, string $encrypted)
    {
        $decrypted = AES::decrypt(
            base64_decode($encrypted, false), base64_decode($sessionKey, false), base64_decode($iv, false)
        );

        $decrypted = json_decode($this->pkcs7Unpad($decrypted), true);

        if (!$decrypted) {
            $this->throwApiError([$sessionKey, $iv, $encrypted], '解密失败', 500);
        }

        return $decrypted;
    }

    public function pkcs7Unpad(string $text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }

        return substr($text, 0, (strlen($text) - $pad));
    }

    private function throwApiError($options, $error, $code = 200)
    {
        $key = createGuid();
        Log::info('Wechat第三方接口出错key：'.$key);
        Log::info('Wechat第三方接口出错params：'.json_encode($options));
        Log::info('Wechat第三方接口出错：'.$code.'，'.$error);
        throw new ApiException('wechat.error', 'Wechat第三方接口出错：{error}', [
            'error' => $key
        ]);
    }
}
