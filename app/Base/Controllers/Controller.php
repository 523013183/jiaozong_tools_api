<?php

namespace App\Base\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function getPageSize(Request $request)
    {
        return $request->input('page_size', config('app.app_rows'));
    }

}
