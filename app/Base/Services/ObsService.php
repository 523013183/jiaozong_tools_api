<?php
namespace App\Base\Services;

use App\Base\Exceptions\ApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Obs\ObsClient;
use Obs\ObsException;

class ObsService extends AbstractBaseService
{
    function base64ToUploadedFile(string $base64, string $filename = null): UploadedFile
    {
        // 提取 mime 和内容
        if (preg_match('/^data:(.*?);base64,(.*)$/', $base64, $matches)) {
            $mime = $matches[1];
            $data = base64_decode($matches[2]);
        } else {
            throw new \Exception('无效的 base64 图片');
        }

        // 生成临时文件名
        $extension = explode('/', $mime)[1] ?? 'png';
        $filename = $filename ?? Str::random(10) . '.' . $extension;
        $tmpPath = sys_get_temp_dir() . '/' . $filename;

        // 写入临时文件
        file_put_contents($tmpPath, $data);

        // 构造 UploadedFile
        return new UploadedFile(
            $tmpPath,
            $filename,
            $mime,
            null,
            true // 标记为测试上传，跳过 is_uploaded_file 检查
        );
    }

    /**
     * 上传到华为云存储
     * @param array $params file_path 文件路径 file_name 文件名称 file_size 文件大小 etag admin_user_id user_id type is_re_name 是否重命名 success_after_remove 上传成功是否删除
     * @param boolean $isPartUpload 是否分段上传
     * @return string
     * @throws ApiException
     */
    public function uploadToObs($params,$isPartUpload=false)
    {
        $filePath = $params['file_path'];
        $fileName = $params['file_name'];
        $fileSize = $params['file_size'];
        $etag = $params['etag'];
        $adminUserId = empty($params['admin_user_id']) ? 0 : $params['admin_user_id'];
        $userId = empty($params['user_id']) ? 0 : $params['user_id'];
        $type = 'common';
        if (isset($params['type'])) {
            $type = $params['type'];
        }
        $isReName = false;
        if (isset($params['is_re_name'])) {
            $isReName = $params['is_re_name'];
        }
        $successAfterRemove = true;
        if (isset($params['success_after_remove'])) {
            $successAfterRemove = $params['success_after_remove'];
        }
        $privateBucket = $params['private_bucket'] ?? 0; //是否私有库
        $uploadId = 0;
        $isGoOn = false; //是否断点续传
        if ($etag) {
            $obsData = $this->model->where([
                'etag' => strtolower($etag)
            ])->first();
            if ($obsData) {
                if (empty($obsData['path'])) {
                    //上传失败进行续传
                    $uploadId = $obsData['upload_id'];
                    $isGoOn = true;
                } else {
                    //上传成功直接返回结果
                    if ($successAfterRemove) {
                        @unlink($filePath);
                    }
                    return $obsData['path'];
                }
            }
        }
        $url='';
        if(empty($isPartUpload)){
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $bucketConfig = ''; //私有桶配置
            $ak = config("obs.{$bucketConfig}accessKeyId");
            $sk = config("obs.{$bucketConfig}accessKeySecret");
            $endpoint = config("obs.{$bucketConfig}endpoint");
            $bucketName = config("obs.{$bucketConfig}bucketName");
            $obsClient = ObsClient::factory([
                'key' => $ak,
                'secret' => $sk,
                'endpoint' => $endpoint
            ]);
            if ($isReName) {
                $objectKey = "$type/" . date('Y') . "/" . date('md') . "/" . uniqid() . ($ext ? "." . $ext : "");
            } else {
                $objectKey = "$type/" . date('Y') . "/" . date('md') . "/" . uniqid() . ($ext ? "/" . $fileName : mt_rand(1000, 9999));
            }
            $resp = $obsClient->putObject([
                'Bucket' => $bucketName,
                'Key' => $objectKey,
                'SourceFile' => $filePath  // localfile为待上传的本地文件路径，需要指定到具体的文件名
            ]);
            if(!empty($resp)){
              /*  $resp=  $resp->toArray();
                $url= $resp['ObjectURL'];*/
                $fullPath = config('obs.obsDomain') . '/' . $objectKey;
                $url ='/'.$objectKey;
                try {
                    $this->save([
                        'etag' => trim($etag, '"') ?? '',
                        'path' => $url,
                        'full_path' => $fullPath,
                        'admin_user_id' => $adminUserId,
                        'user_id' => $userId,
                        'file_size' => filesize($filePath),
                        'upload_id' => $uploadId
                    ]);
                } catch (\Exception $e) {
                    throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
                }
            }else{
                $obsClient->close();
                throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
            }
        }else{
            if (!$isGoOn) {
                $url=$this->defPartUpload($filePath, $fileName, $fileSize, $etag, $type, $adminUserId, $userId, $isReName);
            } else {
                $url = $this->partUploadGoOn($uploadId, $filePath, $fileName, $fileSize, $etag, $type, $adminUserId, $userId, $isReName);
            }
        }
        if($successAfterRemove&&!empty($url)){
            @unlink($filePath);
        }
        return $url;
    }

    /**
     * 续传
     * */
    private function partUploadGoOn($uploadId, $filePath, $fileName, $fileSize, $etag, $type, $adminUserId, $userId, $isReName)
    {
        $url = '';
        try {
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $ak = config('obs.accessKeyId');
            $sk = config('obs.accessKeySecret');
            $endpoint = config('obs.endpoint');
            $bucketName = config('obs.bucketName');
            $obsClient = ObsClient::factory([
                'key' => $ak,
                'secret' => $sk,
                'endpoint' => $endpoint,
                'socket_timeout' => 30,
                'connect_timeout' => 10
            ]);
            $partSize = config('obs.obsPartSize');
            if ($isReName) {
                $objectKey = "$type/" . date('Y') . "/" . date('md') . "/" . uniqid() . ($ext ? "." . $ext : "");
            } else {
                $objectKey = "$type/" . date('Y') . "/" . date('md') . "/" . uniqid() . ($ext ? "/" . $fileName : mt_rand(1000, 9999));
            }
            $partCount = $fileSize % $partSize === 0 ? intval($fileSize / $partSize) : intval($fileSize / $partSize) + 1;
            if ($partCount > 10000) {
                //todo 报错
                throw new ApiException('common.file_upload_part_max_error', '权限结束时间不能为空');
            }
            $parts = [];
            $promise = null;
            //列举已上传的所有部分
            $resp = $obsClient->listParts(['Bucket' => $bucketName, 'Key' => $objectKey, 'UploadId' => $uploadId]);
            $alreadyPart = [];
            foreach ($resp['Parts'] as $part) {
                $parts[] = ['PartNumber' => $part['PartNumber'], 'ETag' => $part['ETag']];
                $alreadyPart[] = $part['PartNumber'];
            }
            for ($i = 0; $i < $partCount; $i++) {
                $offset = $i * $partSize;
                $currPartSize = ($i + 1 === $partCount) ? $fileSize - $offset : $partSize;
                $partNumber = $i + 1;
                if (!in_array($partNumber, $alreadyPart)) {
                    $p = $obsClient->uploadPartAsync([
                        'Bucket' => $bucketName,
                        'Key' => $objectKey,
                        'UploadId' => $uploadId,
                        'PartNumber' => $partNumber,
                        'SourceFile' => $filePath,
                        'Offset' => $offset,
                        'PartSize' => $currPartSize
                    ], function ($exception, $resp) use (&$parts, $partNumber) {
                        $parts[] = ['PartNumber' => $partNumber, 'ETag' => $resp['ETag']];
                    });

                    if ($promise === null) {
                        $promise = $p;
                    }
                }
            }
            //等待上传完毕
            if ($promise) {
                $promise->wait();
            }

            usort($parts, function ($a, $b) {
                if ($a['PartNumber'] === $b['PartNumber']) {
                    return 0;
                }
                return $a['PartNumber'] > $b['PartNumber'] ? 1 : -1;
            });

            //验证是否所有部分已上传
            if (count($parts) !== $partCount) {
                //todo 报错
                throw new ApiException('common.file_upload_part_discord', '权限结束时间不能为空');
            }
            //合并上传
            $resp = $obsClient->completeMultipartUpload([
                'Bucket' => $bucketName,
                'Key' => $objectKey,
                'UploadId' => $uploadId,
                'Parts' => $parts
            ]);
            if(!empty($resp)){
                $resp=  $resp->toArray();
                $fullPath = config('obs.obsDomain') . '/' . $resp['Key'];
                $url = '/' . $resp['Key'];
                $this->save([
                    'etag' => trim($etag, '"') ?? '',
                    'path' => $url,
                    'full_path' => $fullPath,
                    'admin_user_id' => $adminUserId,
                    'user_id' => $userId,
                    'file_size' => filesize($filePath),
                    'upload_id' => $uploadId
                ]);
            }else{
                $obsClient->close();
                throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
            }
        } catch (ObsException $e) {
            $data = [];
            $data['path'] = $url;
            $data['full_path'] = $fullPath;
            $data['admin_user_id'] = $adminUserId;
            $data['user_id'] = $userId;
            $data['file_size'] =$fileSize;
            $data['upload_id'] = $uploadId;
            $this->updateBy(['etag' => $etag], $data);
            Log::info('method:uploadGoOn:Response Code:' . $e->getStatusCode());
            Log::info('method:uploadGoOn:Error Message:' . $e->getExceptionMessage());
            Log::info('method:uploadGoOn:Error Code:' . $e->getExceptionCode());
            Log::info('method:uploadGoOn:Request ID:' . $e->getRequestId());
            Log::info('method:uploadGoOn:Exception Type:' . $e->getExceptionType());
            throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
        } finally {
            $obsClient->close();
        }
        return $url;
    }

    /**
     * 默认分段上传
     * */
    private function defPartUpload($filePath, $fileName, $fileSize, $etag, $type, $adminUserId, $userId, $isReName)
    {
        $url = '';
        try {
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $ak = config('obs.accessKeyId');
            $sk = config('obs.accessKeySecret');
            $endpoint = config('obs.endpoint');
            $bucketName = config('obs.bucketName');
            $obsClient = ObsClient::factory([
                'key' => $ak,
                'secret' => $sk,
                'endpoint' => $endpoint,
                'socket_timeout' => 30,
                'connect_timeout' => 10
            ]);
            $partSize = config('obs.obsPartSize');
            if ($isReName) {
                $objectKey = "$type/" . date('Y') . "/" . date('md') . "/" . uniqid() . ($ext ? "." . $ext : "");
            } else {
                $objectKey = "$type/" . date('Y') . "/" . date('md') . "/" . uniqid() . ($ext ? "/" . $fileName : mt_rand(1000, 9999));
            }
            $partCount = $fileSize % $partSize === 0 ? intval($fileSize / $partSize) : intval($fileSize / $partSize) + 1;
            if ($partCount > 10000) {
                //todo 报错
                throw new ApiException('common.file_upload_part_max_error', '权限结束时间不能为空');
            }
            $parts = [];
            $promise = null;
            $resp = $obsClient->initiateMultipartUpload(['Bucket' => $bucketName, 'Key' => $objectKey]);
            $uploadId = $resp['UploadId'];
            for ($i = 0; $i < $partCount; $i++) {
                $offset = $i * $partSize;
                $currPartSize = ($i + 1 === $partCount) ? $fileSize - $offset : $partSize;
                $partNumber = $i + 1;
                $p = $obsClient->uploadPartAsync([
                    'Bucket' => $bucketName,
                    'Key' => $objectKey,
                    'UploadId' => $uploadId,
                    'PartNumber' => $partNumber,
                    'SourceFile' => $filePath,
                    'Offset' => $offset,
                    'PartSize' => $currPartSize
                ], function ($exception, $resp) use (&$parts, $partNumber) {
                    $parts[] = ['PartNumber' => $partNumber, 'ETag' => $resp['ETag']];
                });
                if ($promise === null) {
                    $promise = $p;
                }
            }
            //等待上传完毕
            if ($promise) {
                $promise->wait();
            }

            usort($parts, function ($a, $b) {
                if ($a['PartNumber'] === $b['PartNumber']) {
                    return 0;
                }
                return $a['PartNumber'] > $b['PartNumber'] ? 1 : -1;
            });

            //验证是否所有部分已上传
            if (count($parts) !== $partCount) {
                //todo 报错
                throw new ApiException('common.file_upload_part_discord', '权限结束时间不能为空');
            }
            //合并上传
            $resp = $obsClient->completeMultipartUpload([
                'Bucket' => $bucketName,
                'Key' => $objectKey,
                'UploadId' => $uploadId,
                'Parts' => $parts
            ]);
            if(!empty($resp)){
                $resp=  $resp->toArray();
                $fullPath = config('obs.obsDomain') . '/' . $resp['Key'];
                $url = '/' . $resp['Key'];
                $this->save([
                    'etag' => trim($etag, '"') ?? '',
                    'path' => $url,
                    'full_path' => $fullPath,
                    'admin_user_id' => $adminUserId,
                    'user_id' => $userId,
                    'file_size' => filesize($filePath),
                    'upload_id' => $uploadId
                ]);
            }else{
                $obsClient->close();
                throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
            }
        } catch (ObsException $e) {
            $uploadId=  $e->getRequestId();
            $data = [];
            $data['admin_user_id'] = $adminUserId;
            $data['user_id'] = $userId;
            $data['file_size'] = $fileSize;
            $data['upload_id'] = $uploadId;
            $this->updateBy(['etag' => $etag], $data);
            Log::info('method:uploadGoOn:Response Code:' . $e->getStatusCode());
            Log::info('method:uploadGoOn:Error Message:' . $e->getExceptionMessage());
            Log::info('method:uploadGoOn:Error Code:' . $e->getExceptionCode());
            Log::info('method:uploadGoOn:Request ID:' . $e->getRequestId());
            Log::info('method:uploadGoOn:Exception Type:' . $e->getExceptionType());
            throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
        } finally {
            $obsClient->close();
        }
        return $url;
    }

    /**
     * 获取私有库的授权url
     */
    public function createSignedUrl($url)
    {
        $url = trim($url, "/");
        $ak = config("obs.private.accessKeyId");
        $sk = config("obs.private.accessKeySecret");
        $endpoint = config("obs.private.endpoint");
        $bucketName = config("obs.private.bucketName");
        $obsClient = ObsClient::factory([
            'key' => $ak,
            'secret' => $sk,
            'endpoint' => $endpoint
        ]);
        $resp = $obsClient->createSignedUrl([
            'Method' => 'GET',
            'Bucket' => $bucketName,
            'Key' => $url
        ]);
        return $resp['SignedUrl'] ?? $url;
    }
}
