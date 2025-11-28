<?php

namespace App\Api\Models;

use App\Base\Models\BaseModel;
use App\Base\Models\ApiSoftDeletes;

class BlogInfoModel extends BaseModel
{
    use ApiSoftDeletes;
    protected $table = 'blog_info';

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
        if (!empty($query['cate_id'])) {
            $where['a.cate_id'] = $query['cate_id'];
        }
        $fields = "a.*,c.name as cate_name,p.urla,c.key as cate_key";
        $model = $db
            ->join('blog_cate as c', 'c.id', '=', 'a.cate_id')
            ->leftJoin('page_info as p', 'p.id', '=', 'a.page_id')
            ->where($where)
            ->where("a.status", "<>", self::STATUS_DELETED)
            ->selectRaw($fields);
        $pageData = $model->orderByRaw('a.sort asc,a.id asc')
            ->paginate($pageSize)
            ->toArray();
        return $pageData;
    }
}
