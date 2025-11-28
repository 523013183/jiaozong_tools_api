<?php

namespace App\Base\Services;


use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use App\Base\Exceptions\ApiException;

class SmsService
{

    /**
     * 通用短信发送方法
     * @param string $phone 手机号
     * @param string $countryCode 手机国家编码
     * @param string $templateParam 模板参数
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @param string $signName
     * @param string $templateCode
     * @param string $type
     * @return bool|mixed
     * @throws \App\Base\Exceptions\ApiException;
     * @throws ClientException
     */
    public function sendSMSInfo($phone, $countryCode = '', $accessKeyId = '', $accessKeySecret = '', $signName = '', $templateCode = '', $templateParam = array())
    {
        $ret = ['code' => 0, 'data' => [], 'message' => ''];
        try {
            //发送短信
            if (empty($accessKeyId)) {
                $accessKeyId = config('aliyunsms.access_key'); //阿里云短信公钥
            }
            if (empty($accessKeySecret)) {
                $accessKeySecret = config('aliyunsms.access_secret'); //阿里云短信私钥
            }
            $template_type = 'inside';
            if (!empty($countryCode) && $countryCode != 86) {
                $template_type = 'foreign';
                $phone = $countryCode . $phone;
            }
            if (empty($signName)) {
                if ($template_type == 'inside') {
                    $signName = config('aliyunsms.inside.sign_name'); //阿里云短信 签名名称
                } else {
                    $signName = config('aliyunsms.foreign.sign_name'); //阿里云短信 签名名称
                }

            }
            if (empty($templateCode)) {
                if ($template_type == 'inside') {
                    $templateCode = config('aliyunsms.inside.template_code.verification_code'); //阿里云短信 短信模板ID
                } else {
                    $templateCode = config('aliyunsms.foreign.template_code.verification_code'); //阿里云短信 短信模板ID
                }
            }
            if (empty($templateParam)) {
                $code = mt_rand(100000, 999999);
                $templateParam = array('code' => $code);
            }
            AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                ->regionId('cn-hangzhou')// replace regionId as you need
                ->asDefaultClient();
            $response = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'SignName' => $signName, //阿里云短信 签名名称
                        'TemplateCode' => $templateCode, //阿里云短信 短信模板ID
                        'PhoneNumbers' => $phone, //手机号
                        'TemplateParam' => json_encode($templateParam),//验证码参数  json格式
                    ],
                ])
                ->request();
            $responseData = $response->toArray();
            if (isset($responseData['Code']) && $responseData['Code'] == 'OK') {
                $ret['message']='OK';
            } else {
                $errorMessage = empty($response->Message) ? '' : $response->Message;
                $errorCode = empty($response->Code) ? '' : $response->Code;
                $errorRequestId = empty($response->RequestId) ? '' : $response->RequestId;
                $ret['code']=1032;
                $ret['message'] = 'ErrorRequestId' . $errorRequestId . ';ErrorCode:' . $errorCode . ';Message:' . $errorMessage;
                Log::info('method:error:' . $response);
            }
            return $ret;
        } catch (\Exception $ex) {
            Log::info('method:sendSMSInfo:' . $ex->getMessage());
            Log::info('method:sendSMSInfo:' . $ex->getTraceAsString());
            throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
        }
    }


    /**
     * 发送推广短信
     * */
    public function sendSMSPromotion($phone,$signName='',$templateCode=''){
        $ret = ['code' => 0, 'data' => [], 'message' => ''];
        try {
            $accessKeyId = config('aliyunsms.access_key'); //阿里云短信公钥
            $accessKeySecret = config('aliyunsms.access_secret'); //阿里云短信私钥
            if (empty($signName)) {
                $signName = config('aliyunsms.inside.sign_name'); //阿里云短信 签名名称
            }
            if (empty($templateCode)) {
                $templateCode = 'SMS_255200174'; //阿里云短信 短信模板ID
            }
            if(!is_array($phone)){
                $phone=[$phone];
            }
            $phoneStr=implode(',',$phone);
            AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                ->regionId('cn-hangzhou')// replace regionId as you need
                ->asDefaultClient();
            $response = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->withPhoneNumbers($phoneStr)
                ->options([
                    'query' => [
                        'TemplateCode' => $templateCode, //阿里云短信 短信模板ID
                        'SignName' => $signName, //阿里云短信 签名名称
                    ],
                ])
                ->request();
            $responseData = $response->toArray();
            if (isset($responseData['Code']) && $responseData['Code'] == 'OK') {
                $ret['message']='OK';
            } else {
                $errorMessage = empty($response->Message) ? '' : $response->Message;
                $errorCode = empty($response->Code) ? '' : $response->Code;
                $errorRequestId = empty($response->RequestId) ? '' : $response->RequestId;
                $ret['code']=1032;
                $ret['message'] = 'ErrorRequestId' . $errorRequestId . ';ErrorCode:' . $errorCode . ';Message:' . $errorMessage;
                Log::info('method:error:' . $response);
            }
            return $ret;
        } catch (\Exception $ex) {
            Log::info('method:sendSMSPromotion:' . $ex->getMessage());
            Log::info('method:sendSMSPromotion:' . $ex->getTraceAsString());
            throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
        }
    }


}
