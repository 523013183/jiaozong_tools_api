<?php


namespace App\Base\Services;


use Illuminate\Support\Facades\Auth;
use App\User\Facades\CompanyDepartmentRelationFacade;
use App\Base\Facades\ApiFacade;


trait AuthUser
{

    /**
     * 获取登录用户ID
     * @return int
     */
    public function getAuthAdminId()
    {
        $user = $this->getAuthUser();
        return $user['id'] ?? 0;
    }


    /**
     * 获取登录用户
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getAuthUser()
    {
        return Auth::user();
    }

    /**
     * 获取头部token
     * @return mixed
     */
    public function getAuthToken()
    {
        $user = $this->getAuthUser();
        return $user['token'];
    }


    /**
     * 获取角色ID
     * @return int
     */
    public function getAuthRoleId()
    {
        $user = $this->getAuthUser();
        return $user['role_id'];
    }

    /**
     * 是否为管理员
     * @param $typeId
     * @return bool
     */
    protected function isAdmin($roleId = 1)
    {
        return $roleId == $this->getAuthRoleId();
    }
}
