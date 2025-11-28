<?php

namespace App\Api\Controllers;

use App\Api\Services\BlogService;
use App\Base\Controllers\ApiBaseController;
use Illuminate\Http\Request;

class BlogController extends ApiBaseController
{
    private $service;

    public function __construct(BlogService $service)
    {
        $this->service = $service;
    }

    /** 
     * @api get /api/blog/list 获取列表
     */
    public function getList(Request $request)
    {
        $params = $request->only(['expo_id', 'cate_id', 'page', 'page_size']);
        $params['page'] = $params['page'] ?? 1;
        $ret = $this->service->getList($params);
        return $ret;
    }

    public function getFrontList(Request $request)
    {
        $params = $request->only(['expo_id', 'cate_id', 'page', 'page_size']);
        $params['page'] = $params['page'] ?? 1;
        $params['page_size'] = 11;
        $ret = $this->service->getFrontList($params);
        return $ret;
    }

    /** 
     * @api get /api/blog/cate-list 获取分类列表
     */
    public function getCateList(Request $request)
    {
        return $this->service->getCateList();
    }

    /** 
     * @api get /api/blog/my-info 获取我的展会详情
     */
    public function getMyInfo(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);
        $ret = $this->service->getMyInfo($request->input('id'), $this->getAuthUserId());
        return $ret;
    }

    /** 
     * @api get /api/blog/info 获取资讯详情
     */
    public function getInfo(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);
        $ret = $this->service->getBlogInfo($request->input('id'));
        return $ret;
    }

    /** 
     * @api post /api/blog/info 保存详情
     */
    public function saveInfo(Request $request)
    {
        $params = $request->only(['id', 'name', 'expo_id', 'cate_id', 'content', 'pic', 'urla', 'page_id']);
        $params['user_id'] = $this->getAuthUserId();
        $ret = $this->service->saveInfo($params);
        return $ret;
    }

    /** 
     * @api delete /api/blog/info 删除详情
     */
    public function deleteInfo(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);
        $ret = $this->service->deleteInfo($request->input('id'), $this->getAuthUserId());
        return $ret;
    }

    /**
     * @api post /api/blog/status 启用/禁用
     */
    public function toggleBlogStatus(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'status' => 'required|in:0,1',
        ]);
        $ret = $this->service->toggleBlogStatus($request->input('id'), $request->input('status'), $this->getAuthUserId());
        return $ret;
    }

}
