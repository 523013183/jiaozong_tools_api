<?php

namespace App\Attachment\Controllers;

use App\Attachment\Facades\AttachmentFacade;
use App\Attachment\Services\AttachmentService;
use App\Base\Controllers\Controller;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    private $service;

    public function __construct(AttachmentService $service)
    {
        $this->service = $service;
    }

    /**
     * @param file file 上传文件
     * @successExample
    {
    "ret": 0,
    "msg": "success.",
    "data":[{
    "files": ["\/22222.jpg"],
    }]}
     * @return \App\Base\Models\BaseModel
     * @api post /admin/attachment/upload 文件上传
     * @group 公共模块 附件
     * @header string token 用户授权token
     */
    public function upload(Request $request)
    {
        $this->validate($request, [
            'file' => 'required'
        ], [
            'file.required' => transL('common.file_empty', '上传文件不能为空'),
        ]);
        set_time_limit(60 * 5);
        $adminUserId=$this->service->getAuthAdminId();
        return $this->service->upload($request,$adminUserId);
    }

    /**
     * @param file file 上传文件
     * @param string fileExt 文件后缀
     * @param string fileName 文件名
     * @successExample
    {
    "ret": 0,
    "msg": "success.",
    "data":[{
    "files": ["\/22222.jpg"],
    }]}
     * @return \App\Base\Models\BaseModel
     * @api post /api/attachment/upload-image base64图片上传
     * @group 客户端 公共接口
     * @header string api-token 用户授权token
     */
    public function uploadImg(Request $request)
    {
        $data = $request->input('file', '');
        $fileExt = $request->input('fileExt', '');
        $selectFileName = $request->input('fileName', '');
        $this->validate($request, [
            'file' => 'required'
        ], [
            'file.required' => 'file不能为空'
        ]);
        $adminUserId=$this->service->getAuthAdminId();
        $ret= $this->service->uploadImg($data, $fileExt, $selectFileName,$adminUserId);
        return $ret;
    }

    //附件保存
    public function saveAttachment(Request $request){
        $params = $request->all();
        $this->validate($request, [
            'id' => 'required'
        ], [
            'id.required' => 'id不能为空'
        ]);
        $ret= $this->service->saveAttachment($params);
        return $ret;
    }


    //删除附件
    public function delAttachment(Request $request)
    {
        $id = $request->input('id', 0);
        $this->validate($request, [
            'id' => 'required'
        ], [
            'id.required' => 'id不能为空'
        ]);
        $params=[];
        $params['id']=$id;
        $resultData = $this->service->delAttachment($params);
        return $resultData;
    }

    //批量删除附件
    public function batchDelAttachment(Request $request)
    {
        $id = $request->input('ids', []);
        $this->validate($request, [
            'ids' => 'required'
        ], [
            'ids.required' => 'id不能为空'
        ]);
        $params=[];
        $params['id']=$id;
        $resultData =$this->service->delAttachment($params);
        return $resultData;
    }

    //获取附件列表
    public function attachmentList(Request $request)
    {
        $pageNo = $request->input('page_no', 1);
        $pageSize = $request->input('page_size', 20);
        $keyword = $request->input('keyword', '');
        $type = $request->input('type', 0);
        $beginEndTime = $request->input('time', []);
        $month = $request->input('month', '');
        $params = [];
        $params['page_no'] = $pageNo;
        $params['page_size'] = $pageSize;
        $params['keyword'] = $keyword;
        $params['type'] = $type;
        $params['begin_end_time'] = $beginEndTime;
        $params['month'] = $month;
        $adminUserId=$this->service->getAuthAdminId();
        $params['admin_user_id']=$adminUserId;
        $resultData = $this->service->getAttachmentList($params);
        return $resultData;

    }


}
