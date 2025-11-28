<?php

namespace App\{module}\Controllers;

use App\{module}\Services\Api{action}Service;
use App\Base\Controllers\ApiBaseController;
use Illuminate\Http\Request;

class Api{action}Controller extends ApiBaseController
{
    private $service;

    /**
     * Api{action}Controller constructor.
     * @param Api{action}Service $service
     */
    public function __construct(Api{action}Service $service)
    {
        $this->service = $service;
    }
}
