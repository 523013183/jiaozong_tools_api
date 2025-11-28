<?php

namespace App\Api\Models;

use App\Base\Models\BaseModel;
use App\Base\Models\ApiSoftDeletes;

class BlogCateModel extends BaseModel
{
    use ApiSoftDeletes;
    protected $table = 'blog_cate';

    /**
     * 列表
     */
    public function getLists($query, $pageSize)
    {
        $db = $this->db('a');
        $where = [
        ];
        if (!empty($query['user_id'])) {
            $where['a.user_id'] = $query['user_id'];
        }
        if (!empty($query['expo_id'])) {
            $where['a.expo_id'] = $query['expo_id'];
        }
        $fields = "a.*";
        $model = $db
            ->where($where)
            ->where("a.status", "<>", self::STATUS_DELETED)
            ->selectRaw($fields);
        $pageData = $model->orderByRaw('a.id desc')
            ->paginate($pageSize)
            ->toArray();
        return $pageData;
    }
}
