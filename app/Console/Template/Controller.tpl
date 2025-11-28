<?php

namespace App\{module}\Controllers;

use App\{module}\Services\{action}Service;
use App\Base\Controllers\Controller;
use Illuminate\Http\Request;

class {action}Controller extends Controller
{
    private $service;

    /**
     * {action}Controller constructor.
     * @param {action}Service $service
     */
    public function __construct({action}Service $service)
    {
        $this->service = $service;
    }

    /**
     * @param string name 主页名称
     * @param int page_size 每页显示数量
     * @param int page 当前页
     * @return array
     * @throws \App\Base\Exceptions\ApiException
     * @throws
     * @api get （接口路径） （接口名称）
     * @group （接口分组）
     * @successExample
     */
    public function lists(Request $request)
    {
        return $this->service->lists($request);
    }


    /**
     * @param (参数类型) (参数字段) （描述）
     * @return mixed
     * @throws \App\Base\Exceptions\ApiException
     * @api post （接口路径） （接口名称）
     * @group （接口分组）
     * @successExample
     * {"ret":0,"msg":"success","data":1}
     */
    public function saveInfo(Request $request){
        return $this->service->saveInfo($request);
    }

    /**
     * @param (参数类型) (参数字段) （描述）
     * @return bool
     * @throws \App\Base\Exceptions\ApiException
     * @api post （接口路径） （接口名称）
     * @group （接口分组）
     * @successExample
     * {"ret":0,"msg":"success","data":1}
     */
    public function changeStatus(Request $request){
        return $this->service->changeStatus($request);
    }

    /**
     * @param int id required
     * @return \App\Base\Models\BaseModel|array|void
     * @api post （接口路径） （接口名称）
     * @group （接口分组）
     * @successExample
     */
    public function detail(Request $request){
        parent::detail($request);
        return $this->service->detail($request);
    }

    /**
     * @param array ids ids required
     * @return int
     * @api delete （接口路径） （接口名称）
     * @group （接口分组）
     * @successExample
     * {"ret":0,"msg":"success.","data":1}
     */
    public function deleteInfo(Request $request){
        parent::deleteInfo($request);
        return $this->service->deleteInfo($request);
    }
}
