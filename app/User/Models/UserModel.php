<?php

namespace App\User\Models;

use App\Base\Models\BaseModel;
use App\Base\Models\ApiSoftDeletes;
use Illuminate\Support\Facades\DB;

class UserModel extends BaseModel
{
    use ApiSoftDeletes;
    protected $table = 'user_info';

    const ROLE_USER = 1;

    const ROLE_ENTERPRISE = 2;

    const STATUS_BLACKLIST = 3;

    /**
     * 是否自己平台用户：是
     */
    const PLATFORM_USER = 1;

    /**
     * 是否自己平台用户：否
     */
    const NOT_PLATFORM_USER = 0;

    public function saveUserInfoWeChatInfoById($userId, $data)
    {
        return $this->updateData($data, ['id' => $userId]);
    }

    /**
     * 用手机号获取用户
     * @param $phone
     * @return array
     */
    public function getUserByPhone($phone)
    {
        $where = [];
        $where['phone'] = $phone;
        $where['status'] = 0;
        $data = $this->db('a')
            ->where($where)->selectRaw('a.*')->first();
        return (array)$data;
    }

    /**
     * 密码登录查询
     * */
    public function pwdLoginSearch($userName){
        $info=$this->newInstance()
            ->where('status','<',2)
            ->where(function ($query)use ($userName){
                $query ->where('user_name','=',$userName)
                    ->orWhere('email','=',$userName)
                    ->orWhere('phone','=',$userName);
            })
            ->selectRaw('id,password,salt,status')->first();;
        if(!empty($info)){
            $info=$info->toArray();
        }else{
            $info=[];
        }
        return $info;
    }

    /**
     * 获取用户基本详细
     * */
    public function getUserBaseInfo($params,$fields="*"){
        $where=[];
        if(!empty($params['user_name'])){
            $where[] =['user_name','=',$params['user_name']];
        }
        if(isset($params['status'])){
            $where[] =['status','=',$params['status']];
        }
        if(isset($params['id'])){
            $where[] =['id','=',$params['id']];
        }
        $info= $this->newInstance()->where($where)->selectRaw($fields)->first();
        if(!empty($info)){
            $info=$info->toArray();
        }else{
            $info=[];
        }
        return $info;
    }
}
