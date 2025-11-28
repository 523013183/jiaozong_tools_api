<?php

namespace App\Web\Controllers;

use App\Api\Facades\BlogFacade;
use App\Api\Facades\ExpoFacade;
use App\Base\Controllers\Controller;
use App\Base\Exceptions\ApiException;
use App\Web\Services\IndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class IndexController extends Controller
{
    private $service;

    /**
     * IndexController constructor.
     * @param IndexService $service
     */
    public function __construct(IndexService $service)
    {
        $this->service = $service;
    }

    /**
     * @param string blade 组件名称
     * @param string view_file 视图文件
     * @return mixed
     * @throws \App\Base\Exceptions\ApiException
     * @throws \Illuminate\Validation\ValidationException
     * @api get /blade 获取渲染后的组件html内容
     * @group 客户端 公共接口
     * @successExample
     * {"ret":0,"msg":"success","data":'html'}
     */
    public function blade(Request $request)
    {
        $this->validate($request, [
            'blade' => 'required',
            'view_file' => 'required',
            'page' => 'integer',
            'page_size' => 'integer'
        ], [
            'blade.required' => transL('common.param_require', '', ["param" => 'blade']),
            'view_file.required' => transL('common.param_require', '', ["param" => 'view_file']),
            'page.integer' =>  transL('common.param_format_error'),
            'page_size.integer' => transL('common.param_format_error')
        ]);
        $blade = $request->input("blade");
        $viewFile = $request->input("view_file");
        $components = Blade::getClassComponentAliases();
        $class = $components[$blade] ?? '';
        if (empty($class)) {
            throw new ApiException('common.name_none_exists', '', ['name' => $blade], 1005);
        }
        $params = $request->all();
        $c = new $class($viewFile, $params);
        return $c->render();
    }


    /**
     * 路由公共入口
     */
    public function index(Request $request)
    {
        $plink = $request->route("urla", "index"); //详情页面分配的地址
        if (empty($plink)) {
            $view = 'index.index';
            $viewData = view($view);
            return $viewData;    
        }
        $plink = urldecode($plink);
        $info = $this->service->getIdByPgkey($plink);
        if (empty($info) && !empty($plink)) {
            abort(404);
        }
        $module = $info['module'] ?? '';
        $data = [
            "pageId" => $info['page_id'] ?? 0,
            "infoId" => $info['info_id'] ?? 0,
            "seoTitle" => $info['seo_title'] ?? '',
            "seoKeywords" => $info['seo_keywords'] ?? '',
            "seoDescription" => $info['seo_description'] ?? '',
            "preview" => 0
        ];
        if ($module == 'blog' && !empty($data['infoId'])) {
            $data['info'] = BlogFacade::getBlogInfo($data['infoId']);
            if ($data['infoId'] === 7) {
                $expoInfo = ExpoFacade::getMyExpoInfo($data['info']['expo_id']);
            }
            if ($data['infoId'] === 15) {
                // 好礼， 提取图片
                $imageUrls = extractImageUrls($data['info']['content'] ?? '');
                if (!empty($imageUrls)) {
                    $data['info']['imageUrls'] = $imageUrls;
                }
            }
            $data['info']['expo_lng'] = $expoInfo['lng'] ?? 0;
            $data['info']['expo_lat'] = $expoInfo['lat'] ?? 0;
        }
        if ($plink == 'index') {
            $blogList  = BlogFacade::getFrontList(['page' => 1, 'page_size' => 100]);
            $data['blogList'] = $blogList['data'] ?? [];
        }
        //组合额外查询的参数: ?page=1&page_size=2
        $data = array_merge($data, $request->all());
        $view = "{$module}.detail";
        $viewData = view($view, $data);
        return $viewData;
    }

    /**
     * 报名
     */
    public function apply()
    {
        $data = [];
        $viewData = view("apply.index", $data);
        return $viewData;
    }

    /** 
     * 报名详情表单页面
     */
    public function applyForm()
    {
        $data = [];
        $viewData = view("apply.detail", $data);
        return $viewData;
    }

    /** 
     * 签到
     */
    public function signIn()
    {
        $data = [];
        $viewData = view("signin.index", $data);
        return $viewData;
    }

    /** 
     * 关于我们
     */
    public function about()
    {
        $data = [];
        $blogList  = BlogFacade::getFrontList(['page' => 1, 'page_size' => 13]);
        $data['blogList'] = $blogList['data'] ?? [];
        $viewData = view("about.index", $data);
        return $viewData;
    }
}
