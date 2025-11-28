<?php


namespace App\User\Services;

use App\Base\Exceptions\ApiException;
use App\Base\Facades\ApiSysUserFacade;
use App\Base\Services\BaseService;
use App\User\Facades\UserFacade;
use App\User\Models\UserModel;
use Illuminate\Support\Facades\Log;

class UserService extends BaseService
{

    protected $cache = true;

    protected $cacheBucket = 'User:';

    /**
     * UserService constructor.
     * @param UserModel $model
     */
    public function __construct(UserModel $model)
    {
        $this->model = $model;
    }

    /**
     * 添加用户
     * @param $command 系统执行新增
     * */
    public function sysAddUser($userData, $command = 0){
        $insertData=[];
        $insertData['id'] =  $userData['id'];
        $insertData['user_name'] = $userData['user_name'];
        $insertData['nick_name'] = $userData['nick_name'];
        $insertData['real_name'] = $userData['real_name'];
        $insertData['phone'] = $userData['phone'];
        $insertData['email'] = $userData['email'];
        $insertData['avatar'] = $userData['avatar'];
        $insertData['pid'] = $userData['pid'] ?? 0;
        $insertData['invite_code'] = createInviteCode($userData['id']);
        $insertData['create_time'] =nowTime();
        $insertData['update_time'] =nowTime();
        if(isset($userData['is_platform'])){
            $insertData['is_platform'] = $userData['is_platform']?1:0;
        }
        $insertRet=$this->model->insert($insertData);
        $id = 0;
        if ($insertRet) {
            $insertData['country_code'] = empty($userData['country_code']) ? '86' : $userData['country_code'];
            $insertData['company'] = $userData['company'] ?? '';
            $insertData['industry_id'] = $userData['industry_id'] ?? [];
            $insertData['address'] = $userData['address'] ?? [];
            $insertData['avatar'] = $userData['avatar'] ?? '';
            $insertData['job_id'] = $userData['job_id'] ?? 0;
            $insertData['referrer'] = $userData['referrer'] ?? '';
            $id = $this->saveInfo($insertData);
        }
        return $id;
    }

    public function saveInfo($params)
    {
        try {
            $saveData = $this->buildUserSaveData($params);
            if (!empty($saveData['id'])) {
                $this->model->where(['id' => $saveData['id']])->update($saveData);
                $id = $saveData['id'];
            } else {
                $id = $this->model->insertGetId($saveData);
            }
            return $id;
        } catch (\Exception $ex) {
            Log::info('method:upload:' . $ex->getMessage());
            Log::info('method:upload:' . $ex->getTraceAsString());
            throw new ApiException('common.server_busy', '服务器忙，请稍候重试~');
        }

    }

    /**
     * 构造用户保存数据 用户注册用户和保存普通数据
     * */
    private function buildUserSaveData($params)
    {
        $nowTime = nowTime();
        $field = ['id', 'user_name', 'nick_name', 'real_name', 'is_platform', 'phone', 'email', 'status', 'avatar', 'create_id', 'update_id'];
        $numberField = ['id', 'is_platform', 'status', 'create_id', 'update_id'];
        $trimField = ['user_name', 'phone', 'email', 'real_name'];
        $data = [];
        foreach ($field as $value) {
            if (isset($params[$value])) {
                if (in_array($value, $numberField)) {
                    $data[$value] = empty($params[$value]) ? 0 : $params[$value];
                } else if (in_array($value, $trimField)) {
                    $data[$value] = empty($params[$value]) ? '' : trim($params[$value]);
                } else {
                    $data[$value] = empty($params[$value]) ? '' : $params[$value];
                }
            }
        }
        $data['update_time'] = $nowTime;
        if (empty($params['id'])) {
            $data['status'] = 1; //注册用户默认禁用状态，设置完密码才为真正创建成功
            $data['create_time'] = $nowTime;
        }
        return $data;
    }

    /**
     * 获取用户的详细信息
     * */
    public function getDetailUserInfoById($id)
    {
        $field = 'id,user_name,nick_name,real_name,invite_code,phone,email';
        $userInfo = $this->findOneById($id, $field);
        return $userInfo;
    }

    /**
     * 创建用户
     */
    public function createUser($info)
    {
        if (empty($info['phone']) && empty($info['email'])) {
            return '';
        }
        $phone = '';
        $email = '';
        $info['phone'] = str_replace(';', ',', $info['phone']);
        $info['phone'] = str_replace('，', ',', $info['phone']);
        $info['phone'] = explode(',', $info['phone']);
        if (!empty($info['phone'][0]) && validPhone($info['phone'][0])) {
            $phone = $info['phone'][0];
        }
        $info['email'] = str_replace(';', ',', $info['email']);
        $info['email'] = str_replace('，', ',', $info['email']);
        $info['email'] = explode(',', $info['email']);
        if (!empty($info['email'][0]) && validEmail($info['email'][0])) {
            $email = $info['email'][0];
        }
        if (empty($phone) && empty($email)) {
            return '';
        }
        //上级
        $pid = 0;
        if (!empty($info['invite_code'])) {
            $pid = inviteCodeDecode($info['invite_code']);
        }
        $userData = [
            'phone' => $phone,
            'email' => $email,
            'country_code' => 86,
            'type' => 1,
            'nick_name' => $info['name'],
            'password' => $info['password'] ?? ''
        ];

        try {
            $ret = ApiSysUserFacade::autoRegisterLogin($userData);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            return ['error' => $errorMsg];
        }

        $user = UserFacade::findOneBy([
            'id' => $ret['user_id'],
            'status' => ['<', 2]
        ]);
        if (empty($user)) {
            $userData = [];
            $userData['id'] = $ret['user_id'];
            $userData['user_name'] = $info['user_name'] ?? '';
            $userData['nick_name'] = $info['name'] ?? '';
            $userData['real_name'] = $info['real_name'] ?? '';
            $userData['phone'] = $phone;
            $userData['email'] = $email;
            $userData['is_platform'] = 1;
            $userData['pid'] = ($ret['user_id'] != $pid) ? $pid : 0;
            $userData['avatar'] = empty($info['logo']) ? UserFacade::randAvatar() : $info['logo'];
            $id = UserFacade::sysAddUser($userData, 1);
            if (empty($id)) {
                $errorMsg = "用户添加失败";
                return ['error' => $errorMsg];
            }
        } else {
            $id = $ret['user_id'];
        }
        return ['user_id' => $id];
    }

    /**
     * 根据用户名获取登陆用户
     * */
    public function getLoginInfoByUserNamge($userName)
    {
        $info = $this->model->pwdLoginSearch($userName);
        return $info;
    }

    public function getUserBaseInfoById($id)
    {
        $where = [];
        $where['id'] = $id;
        $info = $this->model->getUserBaseInfo($where);
        return $info;
    }
}
