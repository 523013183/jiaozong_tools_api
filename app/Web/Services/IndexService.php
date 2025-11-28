<?php


namespace App\Web\Services;

use App\Api\Models\PageInfoModel;
use App\Base\Services\BaseService;

class IndexService extends BaseService
{
    protected $pageInfoModel;

    public function __construct(PageInfoModel $pageInfoModel)
    {
        $this->pageInfoModel = $pageInfoModel;
    }

    /**
     * 获取对应的id
     */
    public function getIdByPgkey($plink)
    {
        return $this->pageInfoModel->getInfo($plink);
    }
}
