<?php


namespace App\Doc\Controllers;

use App\Base\Controllers\Controller;
use App\Doc\Services\DocService;
use Illuminate\Http\Request;

class DocController extends Controller
{
    /**
     * @var DocService
     */
    private $service;

    /**
     * DocController constructor.
     * @param DocService $service
     */
    public function __construct(DocService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取文档分组
     * @param Request $request
     * @return string
     */
    public function group(Request $request)
    {
        return $this->service->group();
    }

    /**
     * 文档详情
     * @param Request $request
     * @return mixed
     */
    public function detail(Request $request)
    {
        return $this->service->detail($request->all());
    }
}
