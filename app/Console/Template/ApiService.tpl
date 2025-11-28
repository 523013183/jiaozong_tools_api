<?php


namespace App\{module}\Services;
use App\Base\Services\ApiBaseService;
use App\Base\Models\BaseModel;

class Api{action}Service extends ApiBaseService
{

    /**
     * Api{action}Service constructor.
     * @param BaseModel $model
     */
    public function __construct(BaseModel $model)
    {
        $this->model = $model;
    }


}
