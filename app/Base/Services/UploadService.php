<?php

namespace App\Base\Services;


use App\Attachment\Facades\AttachmentFacade;
use App\Base\Exceptions\ApiException;
use App\Base\Facades\ObsFacade;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;

class UploadService
{
    /**
     * 文件mineType类型
     * @var array
     */
    private $mineTypeList = [];

    /**
     * 文件扩展名
     * @var array
     */
    private $fileExt = ['png', 'jpg', 'jpeg', 'gif','webp', 'ico', '3gpp', 'ac3',
        'asf', 'au', 'csv', 'doc', 'dot', 'dtd', 'dwg', 'dxf', 'jp2', 'jpe','avif',
        'mp2', 'mp3', 'mp4', 'mpeg', 'mpg', 'mpp', 'ogg', 'pdf', 'pot', 'pps', 'ppt', 'pptx', 'rtf', 'svf',
        'wav', 'wma', 'flac', 'midi', 'ra', 'ape', 'aac', 'cda', 'mov',
        'avi', 'wmv', 'm4v', 'flv', 'f4v', 'rmvb', 'rm', '3gp',
        'tif','pjp','xbm','jxl','svgz','jfif','bmp','pjpeg', 'tiff', 'txt', 'wdb', 'wps', 'xhtml', 'xlc', 'xlm', 'xls', 'xlt', 'xlw', 'xml',
        'zip', '7z', 'xlsx','docx','svg','mkv'];

    /**
     * 文件扩展名
     * @var string
     */
    private $nowFileExt = '';

    /**
     * 文件限制大小,默认20M
     * @var int
     */
    private $limitSize = 1024 * 1024 * 200;

    /**
     * 保存的路径
     * @var string
     */
    private $basePath = './';
    /**
     * 设置文件路径
     * @var string
     */
    private $filePath = '/images';
    /**
     * 上传文件字段
     * @var string
     */
    private $fileField = 'file';

    /**
     * 获取本地路径
     * @param $file
     * @return string
     */
    public function getLocalPath($file = '')
    {
        return base_path('public') . $file;
    }


    /**
     * 上传图片文件
     * @param $request
     * @param string $fileField
     * @param array|string $fileExt
     * @param bool $isUploadOss
     * @return array
     */
    public function upload($request,$userId=0,$fileField = 'file', $fileExt = [], $isUploadOss = true,$isReName=false,$successAfterRemove=true,$isImg=false)
    {
        $this->fileField = $fileField;
        if (!empty($fileExt)) {
            $this->setFileExt($fileExt);
        }
        $this->validate($request, $userId);
        $result = $this->save($request);
        $privateBucket = $request->input("private", 0); //是否上传到私有桶
        $localPath=$this->getLocalPath().$result['path'];//本地保存文件地址
        if (strpos($result['mimeType'], 'image') !== false) {
            list($width, $height) = getimagesize($localPath);
            $result['resolution'] = $width . '*' . $height;
            $isImg = true;
        }
        $result['url'] = $request->root() .'/public'. $result['path'];
        $path = $this->getLocalPath() . $this->filePath;
        $useFfmpeg= config('app.USE_FFMPEG');
        if ($useFfmpeg&&!empty($result['mimeType'])&&strpos($result['mimeType'], 'video') !== false) {
            $ffmpeg=FFMpeg::create(array(
                'ffmpeg.binaries'  => config('app.ffmpeg.ffmpeg_binaries'),
                'ffprobe.binaries' => config('app.ffmpeg.ffprobe_binaries'),
                'timeout'          =>config('app.ffmpeg.timeout'), // The timeout for the underlying process
                'ffmpeg.threads'   => config('app.ffmpeg.ffmpeg_threads'),   // The number of threads that FFMpeg should use
            ));
            $picFilename = $this->getFilename($path,'jpg');
            $video = $ffmpeg->open($localPath);

            $fileSavePath=$path.'/'.$picFilename;
            $video->frame(TimeCode::fromSeconds(1))->save($fileSavePath);
            if(file_exists($fileSavePath)){
                $uploadParams=[];
                $uploadParams['file_path']=$fileSavePath;
                $uploadParams['file_name']=$picFilename;
                $uploadParams['file_size']=filesize($fileSavePath);
                $uploadParams['etag']=md5_file($fileSavePath);
                $uploadParams['user_id']=$userId;
                $uploadParams['type']='common';
                $uploadParams['is_re_name']=$isReName;
                $uploadParams['success_after_remove']=$successAfterRemove;
                $uploadParams['private_bucket'] = 0;
                $coverOssUrl = ObsFacade::uploadToObs($uploadParams);
                $result['pic']=$coverOssUrl;//封面图
            }
        }
        if ($isUploadOss) {
            $filename = $result['original_filename'];
            $checkFileNameStr=str_replace('.'.$result['ext'],'',$filename);
            if (preg_match('/^.*[,\.#%\'\+\*\:;^`\{\}\(\)\[\]\s]/', $checkFileNameStr)) {
                $filename=  preg_replace('/[,\.#%\'\+\*\:;^`\{\}\(\)\[\]\s]/','-', $checkFileNameStr).'.'.$result['ext'];
            }
            $uploadParams=[];
            $uploadParams['file_path']=$localPath;
            $uploadParams['file_name']=$filename;
            $uploadParams['file_size']=$result['size'];
            $uploadParams['etag']=md5_file($localPath);
            $uploadParams['user_id']=$userId;
            $uploadParams['type']='common';
            $uploadParams['is_re_name']=$isReName;
            $uploadParams['success_after_remove']=$successAfterRemove;
            $uploadParams['private_bucket'] = $privateBucket;
            $ossUrl = ObsFacade::uploadToObs($uploadParams);
            $result['url'] = $ossUrl;
            $result['full_path'] =  config('oss.ossDomain').$ossUrl;
        }
        if(!empty($result['ext'])&&$result['ext']==='mp3'){
            $result['mimeType']='audio/mpeg';
        }
        return $result;
    }

    /**
     * 上传Base64图片
     * @param $request
     * @param string $fileField
     * @param array|string $fileExt
     * @param bool $isUploadOss
     * @return array
     */
    public function uploadImgBase64($data, $adminUserId=0,$userId=0,$fileExt = 'jpg', $isUploadOss = false, $private = 0)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data, $result)) {
            $type = $result[2];
            if (in_array($type, array('pjpeg', 'jpeg', 'jpg', 'gif', 'bmp', 'png', 'webp'))) {
                $this->nowFileExt = strtolower($type);
                $path = $this->getLocalPath() . $this->filePath;
                $filename = $this->getFilename($path);
                $nowDate = date('Ymd');
                $filepath = $path . '/' . $nowDate . '/' . $filename;
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                if (!file_exists($path . '/' . $nowDate)) {
                    mkdir(($path . '/' . $nowDate), 0777, true);
                }
                if (file_put_contents($filepath, base64_decode(str_replace($result[1], '', $data)))) {
                    $size = filesize($filepath);
                    $s = getimagesize($filepath);
                    $width = empty($s[0]) ? '' : $s[0];
                    $height = empty($s[1]) ? '' : $s[1];
                    $result = [
                        'path' => $filepath,
                        'size' => $size,
                        'filename' => $filename,
                        'original_filename' => $filename,
                        'ext' => $this->nowFileExt,
                        'resolution' => $width . '*' . $height,
                        'url' => config('app.base_url') . '/images/' . $nowDate . '/' . $filename,
                        'mimeType' => 'image/' . $this->nowFileExt
                    ];
                    if ($isUploadOss) {
                        //$ossUrl = OssFacade::uploadToOss($filepath, $filename, md5_file($filepath),$adminUserId,$userId,'common',true);
                        $uploadParams=[];
                        $uploadParams['file_path']=$filepath;
                        $uploadParams['file_name']=$filename;
                        $uploadParams['file_size']=$result['size'];
                        $uploadParams['etag']=md5_file($filepath);
                        $uploadParams['admin_user_id']=$adminUserId;
                        $uploadParams['user_id']=$userId;
                        $uploadParams['type']='common';
                        $uploadParams['is_re_name']=true;
                        $uploadParams['success_after_remove']=true;
                        $uploadParams['private_bucket'] = $private;
                        $ossUrl = ObsFacade::uploadToObs($uploadParams);
                        $result['url'] = $ossUrl;
                        $result['full_path'] =  config('oss.ossDomain').$ossUrl;
                    }
                    return $result;
//                    echo '图片上传成功</br><img src="' .$img_path. '">';
                } else {
                    throw new ApiException('common.file_ext_error','上传文件类型不正确');

                }
            } else {
                //文件类型错误
                throw new ApiException('common.file_ext_error','上传文件类型不正确');
            }

        } else {
            //文件错误
            throw new ApiException('common.file_ext_error','上传文件类型不正确');
        }
        return false;
    }

    /**
     * 上传文本转为文件
     * @param $request
     * @param string $fileField
     * @param array|string $fileExt
     * @param bool $isUploadOss
     * @return array
     */
    public function uploadTextToFile($data,$adminUserId=0,$userId=0, $fileExt = '', $isUploadOss = true)
    {
        if (!empty($fileExt)) {
            $this->nowFileExt = strtolower($fileExt);
            $path = $this->getLocalPath() . $this->filePath;
            $filename = $this->getFilename($path);
            $filepath = $path . '/' . $filename;
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            if (file_put_contents($filepath, $data)) {
                $size = filesize($filepath);
                $s = getimagesize($filepath);
                $result = [
                    'path' => $filepath,
                    'size' => $size,
                    'filename' => $filename,
                    'original_filename' => $filename,
                    'ext' => $this->nowFileExt,
                    'url' => ''
                ];
                if ($isUploadOss) {
                    //$ossUrl = OssFacade::uploadToOss($filepath, $filename, md5_file($filepath),$adminUserId,$userId,'common',true);
                    $uploadParams=[];
                    $uploadParams['file_path']=$filepath;
                    $uploadParams['file_name']=$filename;
                    $uploadParams['file_size']=$result['size'];
                    $uploadParams['etag']=md5_file($filepath);
                    $uploadParams['admin_user_id']=$adminUserId;
                    $uploadParams['user_id']=$userId;
                    $uploadParams['type']='common';
                    $uploadParams['is_re_name']=true;
                    $uploadParams['success_after_remove']=true;
                    $ossUrl = ObsFacade::uploadToObs($uploadParams);
                    $result['url'] = $ossUrl;
                    $result['full_path'] =  config('oss.ossDomain').$ossUrl;
                }
                return $result;
            }
        }
        return false;
    }

    /**
     * 设置扩展名
     * @param array $fileExt
     * @return $this
     */
    public function setFileExt($fileExt)
    {
        if (empty($fileExt)) {
            return;
        }
        if (!is_array($fileExt)) {
            $fileExt = explode(',', $fileExt);
        }
        $this->fileExt = $fileExt;
        return $this;
    }

    /**
     * 上传验证
     * @param $request
     * @return bool
     * @throws ApiException
     */
    private function validate($request, $userId = 0)
    {

        $fileInfo = $request->file($this->fileField);
//        if (!$request->hasFile($this->fileField) || !$fileInfo->isValid()) {
//            throw new ApiException(30002);
//        }
        if(empty($fileInfo)){
            throw new ApiException('common.file_empty','上传文件不能为空');
        }
        if ($fileInfo&&!$this->checkFileExt($fileInfo)) {
            throw new ApiException('common.file_ext_error','上传文件类型不正确');
        }
        if (!$this->checkLimitSize($fileInfo)) {
            $limit = floor($this->limitSize / (1024 * 1024)) ;
            throw new ApiException('common.file_size_error','', ['size' => $limit],1024);
        }
        //判断是否超出单用户限制
        if ($userId) {
            $bucketLimitSize = config("app.bucketLimitSize");
            $bucketSize = AttachmentFacade::getUserBucketSize($userId);
            if (($bucketSize + $fileInfo->getSize()) > $bucketLimitSize) {
                throw new ApiException('common.file_bucket_size_error', '空间不足');
            }
        }
        $this->checkDirectory($this->filePath);
        return true;
    }

    /**
     * 检查文件扩展名
     * @param $fileInfo
     * @return bool
     */
    private function checkFileExt($fileInfo)
    {
        $fileType = strtolower($fileInfo->getClientOriginalExtension());
        if (!$fileType || !in_array($fileType, $this->fileExt)) {
            return false;
        }
        $this->nowFileExt = strtolower($fileType);
        return true;
    }

    /**
     * 验证文件大小
     * @param $fileInfo
     * @return bool
     */
    private function checkLimitSize($fileInfo)
    {
        $clientSize = $fileInfo->getSize();
        if ($clientSize > $this->limitSize) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 检验文件夹，不存在则创建
     * @param $path
     */
    private function checkDirectory($path)
    {
        $folders = explode('/', $path);
        $basePath = $this->getLocalPath();//$this->basePath;
        foreach ($folders as $item) {
            if (empty($item)) {
                continue;
            }
            if (strrpos($basePath, '/') !== strlen($basePath) - 1) {
                $basePath .= '/';
            }
            $basePath .= $item;
            $this->makeDir($basePath);
        }
    }

    /**
     * 新增文件目录
     * @param $path
     * @return mixed
     */
    private function makeDir($path)
    {
        if (is_dir($path)) {
            return $path;
        } else {
            mkdir($path, 0777, true);
            return $path;
        }
    }

    /**
     * 上传保存到本地
     * @param $request
     * @return array
     */
    private function save($request)
    {
        $fileInfo = $request->file($this->fileField);
        $path = $this->getLocalPath() . $this->filePath;//$this->basePath.$this->filePath;
        $OldFilename = $fileInfo->getClientOriginalName();
        $filename = $this->getFilename($path);
        $fileSize = $fileInfo->getSize();
        $mimeType = $fileInfo->getMimeType();
        $fileInfo->move($path, $filename);
//        Log::info($this->filePath.'/'.$filename);
        return [
            'path' => $this->filePath . '/' . $filename,
            'size' => $fileSize,
            'filename' => $filename,
            'original_filename' => $OldFilename,
            'ext' => $this->nowFileExt,
            'mimeType' => $mimeType
        ];
    }

    /**
     * 获取文件名
     * @param $path
     * @return string
     */
    private function getFilename($path,$ext='')
    {
        $suffix=empty($ext)? $this->nowFileExt:$ext;
        $fileName = date('YmdHis') . mt_rand(100, 999) . '.' . $suffix;
        if (file_exists($path .'/'. $fileName)) {
            return $this->getFileName($path,$ext);
        }
        return $fileName;
    }

    /**
     * 设置文件路径
     * @param string $path
     * @return $this
     */
    public function setFilePath($path)
    {
        $this->filePath = $path;
        return $this;
    }

    /**
     * 设置文件大小
     * @param string $maxSize
     * @return $this
     */
    public function setMaxSize($maxSize = '')
    {
        if ($maxSize && is_numeric($maxSize)) {
            $this->limitSize = $maxSize;
        }
        return $this;
    }

    /**
     * 设置文件mineType
     * @param array $mineType
     * @return $this
     */
    public function setMineType($mineType = [])
    {
        if ($mineType) {
            $this->mineTypeList = $mineType;
        }
        return $this;
    }

    /**
     * 上传远程文件
     * */
    public function uploadRemoteImg($remoteUrl){
        $ossUrl='';
        if (!empty($remoteUrl) && (strpos($remoteUrl, "http://") !== false || strpos($remoteUrl, "https://") !== false)) {
            // logo上传
            $saveToUrl=  $this->getLocalPath() . $this->filePath.'/'. basename($remoteUrl);
            $remoteUrl = str_replace(basename($remoteUrl), urlencode(basename($remoteUrl)), $remoteUrl); // 存在中文的处理
            downloadRemoteFile($remoteUrl, $saveToUrl, $fileContent);
            $img = base64EncodeImage($saveToUrl);
            $mime_type = mime_content_type($saveToUrl);
            unlink($saveToUrl);
            $img = "data:{$mime_type};base64," . $img;
            try {
                $ret = $this->uploadImgBase64($img);
                $ossUrl= $ret['url'] ?? '';
            } catch (\Exception $e) {
            }
        }
        return $ossUrl;
    }
}
