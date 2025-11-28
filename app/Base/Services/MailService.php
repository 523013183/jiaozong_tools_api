<?php
namespace App\Base\Services;

use AlibabaCloud\SDK\Dm\V20151123\Dm;
use AlibabaCloud\SDK\Dm\V20151123\Models\SingleSendMailRequest;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use Darabonba\OpenApi\Models\Config;
use Illuminate\Support\Facades\Log;

class MailService
{
    public static function createClient() {
        $config = new Config([
            "accessKeyId" => config('aliyunmail.access_key'),
            "accessKeySecret" => config('aliyunmail.access_secret')
        ]);
        // 访问的域名
        $config->endpoint = "dm.aliyuncs.com";
        return new Dm($config);
    }

    /**
     * 发送邮件
     */
    public function sendMailInfo($subject, $htmlBody = '', $toAddress)
    {
        $client = self::createClient();
        $singleSendMailRequest = new SingleSendMailRequest([
            "addressType" => 1,
            "toAddress" => $toAddress,
            "subject" => $subject,
            "htmlBody" => $htmlBody,
            "replyToAddress" => 'false',
            "fromAlias" => '展会预登记',
            "accountName" => config("aliyunmail.account_name")
        ]);
        $runtime = new RuntimeOptions([]);
        try {
            $client->singleSendMailWithOptions($singleSendMailRequest, $runtime);
        }
        catch (\Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            // 打印 error
            Log::info('method:sendSMSInfo:' . $error->message);
            return $error->message;
        }
        return true;
    }


}
