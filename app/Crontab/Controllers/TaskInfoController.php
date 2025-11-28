<?php

namespace App\Crontab\Controllers;

use App\Crontab\Services\TaskInfoService;
use App\Base\Controllers\Controller;
use Illuminate\Http\Request;

class TaskInfoController extends Controller
{
    private $service;

    /**
     * TaskInfoController constructor.
     * @param TaskInfoService $service
     */
    public function __construct(TaskInfoService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return int
     */
    public function task(Request $request)
    {
        return $this->service->execute($request->all());
    }

}
