<?php


namespace App\{module}\Services;

use App\Base\Exceptions\ApiException;
use App\Base\Services\BaseService;
use App\{module}\Models\{action}Model;
use App\Log\Facades\LogAdminOperationFacade;

class {action}Service extends BaseService
{

    /**
     * {action}Service constructor.
     * @param {action}Model $model
     */
    public function __construct({action}Model $model)
    {
        $this->model = $model;
    }

    public function lists($request){
        $params = $request->all();
        $limit = $this->getPageSize($params);
        $page = $params['page'] ?? 1;

        $map = [];
        $OrWhere = [];
        $status = $params['status'] ?? '';
        if($status == '') {
            $map['a.status'] = [['in', [0, 1]]];
        }else{
            $map['a.status'] = $status;
        }

        $model = $this->model->newInstance()->alias('a')
            ->where(function($q)use($OrWhere){
                foreach ($OrWhere as $or){
                    $q->OrWhere($or[0],$or[1],$or[2]);
                }
            })
            ->buildQuery($map);
        $counts = $model->count();
        $list = $model->forPage($page, $limit)
        ->orderBy('id', 'desc')
        ->get()->toArray();

        $data = $this->paginator($list, $counts);
        return $data;
    }

    public function saveInfo($request){
        $params = $request->all();
        $id = $params['id'];
        //无ID则为新增
        $params['update_id'] = $this->getAuthAdminId();

        $log = [];
        $log['admin_id'] = $params['update_id'];
        $log['ip'] = getClientIp();
        $log['url'] = $request->getRequestUri();
        if($id){
            $this->updateBy(['id' => $id],$params);
            $log['operation'] = '更新数据：' . json_encode($params, JSON_UNESCAPED_UNICODE);
        }else{
            $params['create_id'] = $params['update_id'];
            $id = $this->save($params)->id;
            $log['operation'] = '新增数据：id => '.$id.' =>'.json_encode($params, JSON_UNESCAPED_UNICODE);
        }
        LogAdminOperationFacade::addOperationLog($log);
        return $id;
    }

    public function changeStatus($request){
        $params = $request->all();
        $model = $this->findOneById($params['id']);
        if(!$model){
            throw new ApiException('common.no_records', '没有找到相关的记录');
        }
        $params['update_id'] = $this->getAuthAdminId();
        $ret = $this->updateBy(['id' => $params['id']],['status' => $params['status'],'update_id' => $params['update_id']]);
        $log = [];
        $log['admin_id'] = $params['update_id'];
        $log['ip'] = getClientIp();
        $log['url'] = $request->getRequestUri();
        $log['operation'] = '更新状态：' . json_encode($params, JSON_UNESCAPED_UNICODE);
        LogAdminOperationFacade::addOperationLog($log);
        return $ret;
    }

    public function deleteInfo($request){
        $ids = $request->input('ids', 0);
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $map = ['id' => [['in', $ids]]];
        $this->deleteBy($map, ['update_id' => $this->getAuthAdminId()]);
        $log = [];
        $log['admin_id'] = $this->getAuthAdminId();
        $log['ip'] = getClientIp();
        $log['url'] = $request->getRequestUri();
        $log['operation'] = '删除数据：ids => ' . implode(',',$ids);
        LogAdminOperationFacade::addOperationLog($log);
        return 1;
    }

    public function detail($request)
    {
        $id = $request->input('id', 0);
        $info = $this->findOneById($id);
        if ($info) {
            $info = $info->toArray();
        }
        $log = [];
        $log['admin_id'] = $this->getAuthAdminId();
        $log['ip'] = getClientIp();
        $log['url'] = $request->getRequestUri();
        $log['operation'] = '查看详情：id => ' . $id;
        LogAdminOperationFacade::addOperationLog($log);
        return $info;
    }
}
