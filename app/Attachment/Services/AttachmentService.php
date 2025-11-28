<?php
namespace App\Attachment\Services;

use App\Attachment\Facades\AttachmentFacade;
use App\Attachment\Models\AttachmentListModel;
use App\Base\Exceptions\ApiException;
use App\Base\Facades\ObsFacade;
use App\Base\Facades\PinYinFacade;
use App\Base\Facades\UploadFacade;
use App\Base\Models\BaseModel;
use App\Base\Services\BaseService;
use App\Base\Services\ObsService;
use App\User\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttachmentService extends BaseService
{
//    protected $cache = true;
//    protected $cacheBucket = 'attachment:';

    //文件限制大小,默认10M
    protected $limitSize = 1024 * 1024 * 200;
    private $obsService;

    /**
     * AttachmentListService constructor.
     * @param AttachmentListModel $model
     */
    public function __construct(AttachmentListModel $model,
        ObsService $obsService)
    {
        $this->model = $model;
        $this->obsService = $obsService;
    }

    /**
     * 文件上传
     * @param Request $request
     * @return BaseModel
     */
    public function upload(Request $request, $userId=0)
    {
        //验证上传文件类型
        try {
            $isReName= $request->input('is_re_name', true);
            if (empty($request->input('url'))) {
                $upload = UploadFacade::upload($request, $userId, $request->input('field', 'file'), [], false, $isReName);
            } else {
                $upload = [
                    'mimeType' => $request->input('mime_type', ''),
                    'url' => $request->input('url'),
                    'filename' => $request->input('filename', ''),
                    'original_filename' => $request->input('filename', ''),
                    'size' => $request->input('size', ''),
                    'ext' => $request->input('ext', ''),
                    'pic' => $request->input('pic', ''),
                    'resolution' => $request->input('resolution', ''),
                ];
                $etag = $request->input('etag', '');
                if (!empty($etag)) {
                    $etag = strtolower(trim($etag));
                    $obsInfo = ObsFacade::findOneBy([
                        'etag' => $etag
                    ], 'etag');
                    if (empty($obsInfo['etag'])) {
                        ObsFacade::save([
                            'etag' => $etag,
                            'path' => $upload['url'],
                            'full_path' => formatFileUrl($upload['url']),
                            'user_id' => $userId,
                            'file_size' => $upload['size'],
                            'upload_id' => 0
                        ]);
                    }
                }
            }
            $mimeType = empty($upload['mimeType']) ? '' : $upload['mimeType'];
            $privateBucket = $request->input("private", 0); //是否上传到私有桶
            $data = [
                'user_id' =>$userId,
                'file' => str_replace('/public', '', $upload['url']),//文件地址
                'name' => $upload['filename'] ?? '',//文件名
                'old_name' => $upload['original_filename'] ?? '',//文件名
                'alt' => empty($request->input("alt", '')) ? ($upload['original_filename'] ?? '') : $request->input("alt", ''),//文件名
                'size' => $upload['size'],
                'ext' => $upload['ext'],
                'pic'=>empty($upload['pic'])?'':$upload['pic'],
                'resolution' => empty($upload['resolution']) ? '' : $upload['resolution'],
                'type' => $this->getFileType($mimeType),
                'bucket_name' => $privateBucket == 1 ? config("obs.private.bucketName") : config("obs.bucketName"),
                'title' => $request->input("title", '')

            ];
            $data['name'] = str_replace('.' . strtolower($data['ext']), '', $data['name']);
            if (empty($data['name'])) {
                die;
            } else {
                $data['first_letter'] = PinYinFacade::getOnePY($data['old_name']);
            }
            //判断附件是否已经上传到公共桶(第一个上传的文件判断)
            $info = $this->model->db()->selectRaw('id,bucket_name')
                ->where([
                    'file' => $data['file']
                ])->orderBy('id', 'asc')->first();
            if (!empty($info['id'])) {
                $data['bucket_name'] = $info['bucket_name'];
            }
            $saveModel = $this->save($data);
            // $saveModel['file'] = $this->formatByFiles([$saveModel['file']])[0] ?? '';
            return $saveModel;
        } catch (\Exception $ex) {
            Log::info('method:upload:' . $ex->getMessage());
            Log::info('method:upload:' . $ex->getTraceAsString());
            throw new ApiException('common.msg','', ['msg' =>  $ex->getMessage()],1004);
        }
    }

    /**
     * 保存
     */
    public function save($data)
    {
        $saveModel = parent::save($data);
        return $saveModel;
    }


    /**
     * 获取文件类型
     * */
    private function getFileType($mimeType)
    {
        $type = 0;
        if (strpos($mimeType, 'image') !== false) {
            $type = 1;
        } else if (strpos($mimeType, 'video') !== false) {
            $type = 2;
        } else if (strpos($mimeType, 'audio') !== false) {
            $type = 3;
        }
        return $type;
    }


    /**
     * Base64图片上传
     * @param Request $request
     * @return BaseModel
     */
    public function uploadImg($data, $fileExt, $selectFileName = '', $adminUserId = 0, $userId = 0, $folderId = 0, $private = 0, $isTemporary = 0)
    {
        $upload = UploadFacade::uploadImgBase64($data,$adminUserId,$userId,$fileExt, false, $private);
        //如果该url已存在于该公司
        $data = [
            'user_id' => $userId,
            'file' => $upload['url'],//文件地址
            'name' => $upload['filename'] ?? '',//文件名
            'old_name' => $selectFileName ?? '',//文件名
            'alt' => $selectFileName ?? '',//文件名
            'size' => $upload['size'],
            'ext' => $upload['ext'],
            'resolution' => empty($upload['resolution']) ? '' : $upload['resolution'],
            'type' => 1,
            'bucket_name' => $private == 1 ? config("obs.private.bucketName") : config("obs.bucketName"),
        ];
        $data['name'] = str_replace('.' . strtolower($data['ext']), '', $data['name']);
        if (empty($data['name'])) {
            die;
        } else {
            $data['first_letter'] = PinYinFacade::getOnePY($data['old_name']);
        }
        //判断附件是否已经上传到公共桶
        $info = $this->findOneBy([
            'file' => $data['file'],
            'bucket_name' => config("obs.bucketName")
        ], "id");
        if (!empty($info['id'])) {
            $data['bucket_name'] = config("obs.bucketName");
        }
        if (!empty($isTemporary)) {
            $data['status'] = 1;
        }
        $saveModel = $this->save($data);
        return $saveModel;
    }

    public function getAttachmentList($requestParams)
    {
        $pageSize = $requestParams['page_size'] ? $requestParams['page_size'] : 10; //页面大小，不传默认一页10条记录
        $pageNo = $requestParams['page_no'] ? $requestParams['page_no'] : 1; //页码，不传默认第1页
        $skip = ($pageNo - 1) * $pageSize; //页面记录的开始位置，即偏移量
        $where = [];
        $where['a.status'] = 0;
        if(!empty($requestParams['user_id'])&&empty($requestParams['official'])){
            $where['a.user_id'] = $requestParams['user_id'];
        }
        if(!empty($requestParams['admin_user_id'])){
            $where['a.admin_user_id'] = $requestParams['admin_user_id'];
        }

        if (isset($requestParams['type']) && $requestParams['type'] !== '' && $requestParams['type'] != -1) {
            $where['a.type'] = $requestParams['type'];
        }
        //过滤私密图片
        if (empty($requestParams['private'])) {
            $where['a.bucket_name'] = config("obs.bucketName");
        }
        if (!empty($requestParams['ids'])) {
            $where['a.id'] = ["in", $requestParams['ids']];
        }
        //查询的月份处理
        if (!empty($requestParams['month'])) {
            $begin = $requestParams['month'].'-01 00:00:00';
            $end = date('Y-m-d 23:59:59',strtotime('+1 month', strtotime($begin)) - 1);
            $requestParams['begin_end_time'] = [$begin, $end];
        }


        $resultData = [];
        $query = AttachmentFacade::getModel()->newInstance()->alias('a')->buildQuery($where);
        if (!empty($requestParams['begin_end_time'])) {
            $begin = empty($requestParams['begin_end_time'][0]) ? '' : $requestParams['begin_end_time'][0];
            $end = empty($requestParams['begin_end_time'][1]) ? '' : $requestParams['begin_end_time'][1];
            if (!empty($begin) && !empty($end)) {
                $query->whereBetween('a.create_time', [$begin, $end]);
            }
        }
        if (!empty($requestParams['keyword'])) {
            $keyword = $requestParams['keyword'];
            $query->where(function ($queryStr) use ($keyword) {
                $queryStr->where('a.des', 'like', "%" . $keyword . "%")
                    ->orWhere('a.alt', 'like', "%" . $keyword . "%")
                    ->orWhere('a.old_name', 'like', "%" . $keyword . "%")
                    ->orWhere('a.title', 'like', "%{$keyword}%");
            });
        }
        $totalCount = $query->count();
        $data = $query
            ->selectRaw('a.*')
            ->skip($skip)
            ->limit($pageSize)
            ->orderByDesc('a.update_time')
            ->get();
        if (!empty($data)) {
            $resultData = $data->toArray();
            foreach ($resultData as &$v) {
                $v['base_file'] = $v['file'];
                if ($v['bucket_name'] == config("obs.bucketName")) {
                    continue;
                }
                $v['file'] = $this->obsService->createSignedUrl($v['file']);
            }
        }
        $result = buildPage($resultData, $skip, $pageNo, $pageSize, $totalCount);

        return $result;
    }

    //保存附件信息
    public function saveAttachment($params)
    {
        $data=[];
        if(!empty($params['id'])){
            $where=[];
            $where['id']=$params['id'];
            if(!empty($params['user_id'])){
                $where['user_id']=$params['user_id'];
            }
            $data = $this->model->where($where)->first();
            if(!empty($data)){
                $data->update_time = nowTime();
                if ($data->type == 1) {
                    $data->alt = empty($params['alt']) ? '' : $params['alt'];
                    $data->title = empty($params['title']) ? '' : $params['title'];
                    $data->remark = empty($params['remark']) ? '' : $params['remark'];
                    $data->des = empty($params['des']) ? '' : $params['des'];
                }
                $data->save();
            }
        }
        return $data;
    }

    //删除附件信息
    public function delAttachment($params)
    {
        $id = $params['id'];
        if (!is_array($id)) {
            $ids = [$id];
        } else {
            $ids = $id;
        }
        $where = [];
        $where['id'] = [['in', $ids]];
        if(!empty($params['user_id'])){
            $where['user_id'] = $params['user_id'];
        }
        $ret = $this->model->buildQuery($where)->update(['update_time' => nowTime(), 'status' => 2]);
        return $ret;
    }


    /**
     * 根据附件名称查找附件详情
     * @param $file
     */
    public function getInfoByFile($file)
    {
        $map = ['file' => $file];
        $info = $this->findOneBy($map);
        if ($info) {
            $info = $info->toArray();
        }
        return $info;
    }

    /**
     * 根据id获取附件信息
     * @param $ids
     * @return array
     */
    public function getFileByIds($ids,$field='')
    {
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        if(empty($field)){
            $field= 'file,pic,name,old_name,ext,resolution,title,alt,des,remark,size,type,id';
        }
        $map = ['id' => [['in', $ids]]];
        $list = $this->model->newInstance()->buildQuery($map)
            ->selectRaw($field)
            ->get()->toArray();
        return $list;
    }

    /**
     * 私有桶的文件，格式化文件地址
     */
    public function formatByFiles($files)
    {
        $retFiles = $files;
        $files = array_filter($files);
        if (empty($files)) {
            return $retFiles;
        }
        $list = $this->findBy([
            "file" => ["in", $files]
        ], "file,bucket_name");
        if (empty($list)) {
            return $retFiles;
        }

        $data = [];
        foreach ($list as $val) {
            if ($val['bucket_name'] == config("obs.bucketName")) {
                $data[$val['file']] = $val['file'];
                continue;
            }
            $file = $this->obsService->createSignedUrl($val['file']);
            $data[$val['file']] = $file;
        }
        //按请求时的数组进行排序输出
        $ret = [];
        foreach ($retFiles as $v) {
            $ret[] = $data[$v] ?? $v;
        }
        return $ret;
    }

    /**
     * 获取用户已使用的存储大小
     */
    public function getUserBucketSize(int $userId)
    {
        return $this->model->getUserBucketSize($userId);
    }

}
