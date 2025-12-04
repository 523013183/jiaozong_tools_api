<?php
namespace App\Exam\Services;

use App\Base\Exceptions\ApiException;
use App\Base\Services\ApiBaseService;
use App\Exam\Models\ExamMainModel;

class ExamService extends ApiBaseService
{
    private $blogCateModel;

    public function __construct(ExamMainModel $model)
    {
        $this->model = $model;
    }

    /** 
     * 获取展会列表
     */
    public function getList($criteria) 
    {
        $criteria['user_id'] = $this->getAuthUserId();
        $pageSize = $this->getPageSize($criteria);
        $list = $this->model->getLists($criteria, $pageSize);
        return $list;
    }

    /** 
     * 前台地址
     */
    public function getFrontList($criteria)
    {
        $pageSize = $this->getPageSize($criteria);
        $list = $this->model->getLists($criteria, $pageSize);
        $data = [];
        foreach ($list['data'] as $val) {
            $item = [];
            $item['icon'] = $val['icon'];
            $item['pic'] = $val['pic'];
            $item['name'] = $val['name'];
            $item['urla'] = $val['urla'];
            $data[$val['cate_key']][] = $item;
        }
        $list['data'] = $data;
        return $list;
    }

    /** 
     * 获取分类列表
     */
    public function getCateList()
    {
       return $this->blogCateModel->findBy(['status' => 0]); 
    }

    /** 
     * 获取我的展会详情
     */
    public function getMyInfo($id, $userId = 0)
    {
        $where = [
            'id' => $id,
        ];
        if (!empty($userId)) {
            $where['user_id'] = $userId;
        }
        $info = $this->findOneBy($where);
        if (empty($info)) {
            throw new ApiException('common.no_records', '展会不存在');
        } 
        if (empty($info['page_id'])) {
            $info['urla'] = '';
            return $info;
        }
        $pageInfo = PageInfoFacade::findOneById($info['page_id'], 'id,urla');
        $info['urla'] = $pageInfo['urla'] ?? '';
        return $info;
    }

    /** 
     * 获取展会详情
     */
    public function getExamInfo($id)
    {
        $where = [
            'id' => $id,
        ];
        $info = $this->model->findOneBy($where);
        return $info;
    }

    /** 
     * 保存详情
     */
    public function saveInfo($params)
    {
        $data = [
            'province' => $params['province'] ?? '',
            'city' => $params['city'] ?? '',
            'exam_name' => $params['exam_name'] ?? '',
            'exam_type' => $params['exam_type'] ?? '',
            'announcement_url' => $params['announcement_url'] ?? '',
            'year' => $params['year'],
            'extra_info' => $params['extra_info'] ?? '',
        ];
        if (empty($params['id'])) {
            $ret = $this->model->insertData($data);
        } else {
            $ret = $this->updateBy([
                'id' => $params['id']
            ], $data);
        }
        return $ret;
    }

    /** 
     * 删除展会详情
     */
    public function deleteInfo($id, $userId)
    {
        $where = [
            'id' => $id,
            'user_id' => $userId,
        ];
        $info = $this->findOneBy($where);
        if (empty($info)) {
            throw new ApiException('common.no_records', '不存在');
        }
        $ret = $this->deleteBy($where);
        return $ret;
    }

    /**
     * 启用/禁用展会
     */
    public function toggleBlogStatus($id, $status, $userId)
    {
        $where = [
            'id' => $id,
            'user_id' => $userId,
        ];
        $info = $this->findOneBy($where);
        if (empty($info)) {
            throw new ApiException('common.no_records', '展会不存在');
        }
        $ret = $this->updateBy($where, [
            'status' => $status,
        ]);
        return $ret;
    }
}
