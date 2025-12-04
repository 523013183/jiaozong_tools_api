<?php
/** 
 * 考试信息
 */
namespace App\Api\Controllers;

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
     * @api get /api/exam/list 获取考试信息列表
     */
    public function getList(Request $request)
    {
        $params = $request->only(['page', 'page_size']);
        $params['page'] = $params['page'] ?? 1;
        $ret = $this->service->getList($params);
        return $ret;
    }

    /** 
     * @api get /api/exam/info 获取考试信息详情
     */
    public function getInfo(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);
        $ret = $this->service->getExamInfo($request->input('id'));
        return $ret;
    }
}
