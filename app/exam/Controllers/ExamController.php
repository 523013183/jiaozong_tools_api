<?php
/** 
 * 考试信息管理
 */
namespace App\Exam\Controllers;

use App\Base\Controllers\ApiBaseController;
use App\Exam\Services\ExamService;
use Illuminate\Http\Request;

class ExamController extends ApiBaseController
{
    private $service;

    public function __construct(ExamService $service)
    {
        $this->service = $service;
    }

    /** 
     * @api get /admin/exam/list 获取考试信息列表
     */
    public function getList(Request $request)
    {
        $params = $request->only(['page', 'page_size']);
        $params['page'] = $params['page'] ?? 1;
        $ret = $this->service->getList($params);
        return $ret;
    }

    /** 
     * @api get /admin/exam/info 获取考试信息详情
     */
    public function getInfo(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);
        $ret = $this->service->getExamInfo($request->input('id'));
        return $ret;
    }

    /** 
     * @api post /admin/exam/info 保存考试信息详情
     */
    public function saveInfo(Request $request)
    {
        $this->validate($request, [
            'id' => 'integer',
            'exam_name' => 'required',
            'province' => 'required',
            'city' => 'required',
            'exam_type' => 'required',
            'announcement_url' => 'required',
            'year' => 'required',
        ]);
        $params = $request->all();
        $params['user_id'] = $this->getAuthUserId();
        $ret = $this->service->saveInfo($params);
        return $ret;
    }

    /** 
     * @api delete /admin/exam/info 删除考试信息详情
     */
    public function deleteInfo(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);
        $ret = $this->service->deleteInfo($request->input('id'), $this->getAuthUserId());
        return $ret;
    }

}
