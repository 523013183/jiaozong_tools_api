<?php

namespace App\Exam\Models;

use App\Base\Models\BaseModel;
use App\Base\Models\ApiSoftDeletes;

class ExamMainModel extends BaseModel
{
    use ApiSoftDeletes;
    protected $table = 'exam_main';

    /**
     * 列表
     */
    public function getLists($query, $pageSize)
    {
        $db = $this->db('a');
        $where = [
        ];
        $fields = "a.*";
        $model = $db
            ->where($where)
            ->where("a.status", "<>", self::STATUS_DELETED)
            ->selectRaw($fields);
        $pageData = $model->orderByRaw('a.id asc')
            ->paginate($pageSize)
            ->toArray();
        return $pageData;
    }
}
