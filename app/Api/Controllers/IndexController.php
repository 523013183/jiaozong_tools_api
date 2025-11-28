<?php

namespace App\Api\Controllers;

use App\Api\Services\ApiService;
use App\Base\Controllers\ApiBaseController;
use Illuminate\Http\Request;

class IndexController extends ApiBaseController
{
    public function __construct()
    {
    }

    /**
     * 首页
     */
    public function index(Request $request)
    {
        return 'hello, world!';
    }
}
