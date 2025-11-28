<?php

namespace App\Base\Controllers;

use App\Base\Services\ApiAuthUser;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class ApiBaseController extends Controller
{
    use ApiAuthUser;

    public function getPageSize(Request $request)
    {
        return $request->input('page_size', config('app.app_rows'));
    }
}
