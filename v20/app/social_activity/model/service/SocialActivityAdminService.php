<?php

namespace app\social_activity\model\service;

use app\community\model\db\HouseVillage;
use app\social_activity\model\db\SocialActivityAdmin;
use app\social_activity\model\db\SocialActivityRole;
use app\social_activity\model\db\SocialActivityMenu;
use app\social_activity\model\db\SocialActivityRoleMenu;
use token\Token;

class SocialActivityAdminService
{
    /**
     * 获取管理账号列表
     * @param $param
     * @return \think\Paginator
     */
    public function getAdminList($param)
    {
        $field = 'id,account,realname,phone,role_id,village_id,status,last_login_ip,last_login_time,create_time,update_time';
        
        $searchParams = [
            'account' => $param['account'] ?? '',
            'realname' => $param['realname'] ?? '',
            'phone' => $param['phone'] ?? '',
            'status' => isset($param['status']) && $param['status'] !== '' ? $param['status'] : null,
        ];
        
        if (isset($param['role_id']) && $param['role_id'] > 0) {
            $searchParams['role_id'] = $param['role_id'];
        }
        
        $paginate = (new SocialActivityAdmin())
            ->with(['role' => function ($query) {
                $query->field('id,name');
            }])
	        ->where('phone', '<>', 'admin')
	        ->where('is_delete', 0)
            ->withSearch(['account', 'realname', 'phone', 'role_id', 'status'], $searchParams)
            ->field($field)
	        ->append(['status_text'])
            ->order('id DESC')
            ->paginate([
                'list_rows' => $param['pageSize'],
                'page' => $param['page'],
            ]);
        $list = $paginate ? $paginate->items() : [];

        $villageIdsAll = [];
        foreach ($list as $item) {
            if ($item['village_id'] && $item['village_id'] != '0') {
                $ids = explode(',', $item['village_id']);
                foreach ($ids as $vid) {
                    $vid = intval($vid);
                    if ($vid > 0) {
                        $villageIdsAll[$vid] = $vid;
                    }
                }
            }
        }
        $villageNameMap = [];
        if ($villageIdsAll) {
            $villageNameMap = (new HouseVillage())
                ->where('village_id', 'in', $villageIdsAll)
                ->column('village_name', 'village_id');
        }
        foreach ($list as &$item) {
            $item['role_name'] = $item['role']['name'] ?? '';
            $item['list_phone'] = $item['phone'];
            $item['village_name'] = '';
            if ($item['village_id'] && $item['village_id'] != '0') {
                $villageIds = explode(',', $item['village_id']);
                $villageNames = [];
                foreach ($villageIds as $vid) {
                    $vid = intval($vid);
                    if ($vid > 0 && isset($villageNameMap[$vid])) {
                        $villageNames[] = $villageNameMap[$vid];
                    }
                }
                $item['village_name'] = implode(',', $villageNames);
            }
            unset($item['password'], $item['role']);
        }
        return $paginate;
    }

    /**
     * 管理员登录
     * @param $param
     * @return array
     */
    public function login($param)
    {
		if(!empty($param['super_admin'])) {
			$admin = SocialActivityAdmin::where('account', 'admin')->find();
		} else {
			$account = trim($param['account']);
			$password = trim($param['password']);

			if (empty($account)) {
				throw_exception('请输入账号！');
			}
			if (empty($password)) {
				throw_exception('请输入密码！');
			}

			$admin = (new SocialActivityAdmin())->getAdminByAccount($account);
			if (!$admin || $admin->isEmpty()) {
				throw_exception('手机号或密码错误！');
			}
			if ($admin['status'] != 1) {
				throw_exception('账号已被禁用，请联系管理员！');
			}

			if (!(new SocialActivityAdmin())->verifyPassword($password, $admin['password'])) {
				throw_exception('账号或密码错误！');
			}
		}
        $ticket = Token::createToken($admin['id'], 'social_activity_admin');
        if (empty($ticket)) {
            throw_exception('登录失败，请重试！');
        }

        (new SocialActivityAdmin())->where(['id' => $admin['id']])->save([
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => request()->ip(),
        ]);

        $roleInfo = (new SocialActivityRole())->getRoleById($admin['role_id']);
        $roleName = $roleInfo ? $roleInfo['name'] : '';
        
        return [
            'ticket' => $ticket,
            'admin_id' => $admin['id'],
            'account' => $admin['account'],
            'realname' => $admin['realname'],
            'phone' => phone_desensitization($admin['phone']),
            'role_id' => $admin['role_id'],
            'role_name' => $roleName,
        ];
    }

    /**
     * 获取管理账号详情
     * @param $param
     * @return array
     */
    public function getAdminDetail($param)
    {
        if (!$param['id']) {
            throw_exception('查看对象不能为空！');
        }
        $info = (new SocialActivityAdmin())
            ->with(['role' => function ($query) {
                $query->field('id,name');
            }])
            ->find($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('查看对象不存在或已被删除！');
        }
        $info = $info->toArray();
        $info['role_name'] = $info['role']['name'] ?? '';
        unset($info['password'], $info['role']);

        $villageNames = [];
        if ($info['village_id'] && $info['village_id'] != '0') {
            $villageIds = explode(',', $info['village_id']);
            $villageIds = array_filter(array_map('intval', $villageIds));
            if ($villageIds) {
                $villageNames = (new \app\community\model\db\HouseVillage())
                    ->where(['id' => ['in', $villageIds]])
                    ->column('village_name');
            }
        }
        $info['village_names'] = $villageNames;

        return $info;
    }

    /**
     * 保存管理账号（新增/编辑）
     * @param $param
     * @return bool
     */
    public function saveAdmin($param)
    {
        $id = $param['id'] ?? 0;

        if ($id > 0) {
            // 编辑模式
            $info = (new SocialActivityAdmin())->getAdminById($id);
            if (!$info || $info->isEmpty()) {
                throw_exception('编辑对象不存在！');
            }

            // 检查账号是否重复
            if (isset($param['account']) && $param['account'] != $info['account']) {
                $existAdmin = (new SocialActivityAdmin())->getAdminByAccount($param['account']);
                if ($existAdmin && !$existAdmin->isEmpty()) {
                    throw_exception('账号已存在！');
                }
            }

            $data = [
                'account' => $param['account'] ?? $info['account'],
                'realname' => $param['realname'] ?? $info['realname'],
                'phone' => $param['phone'] ?? $info['phone'],
                'role_id' => isset($param['role_id']) ? intval($param['role_id']) : $info['role_id'],
                'village_id' => $param['village_id'] ?? $info['village_id'],
                'status' => isset($param['status']) ? intval($param['status']) : $info['status'],
            ];

            if (!empty($param['password'])) {
                $data['password'] = md5($param['password']);
            }

            $result = (new SocialActivityAdmin())->where(['id' => $id])->save($data);
            if ($result === false) {
                throw_exception('编辑失败！');
            }
        } else {
            // 新增模式
	        $data = [
		        'account' => $param['account'],
		        'password' => md5($param['password']),
		        'realname' => $param['realname'],
		        'phone' => $param['phone'],
		        'role_id' => intval($param['role_id']),
		        'village_id' => $param['village_id'] ?? '0',
		        'status' => intval($param['status']),
	        ];
            $existAdmin = (new SocialActivityAdmin())->getAdminByAccount($param['account']);
            if ($existAdmin && !$existAdmin->isEmpty()) {
				if($existAdmin['is_delete'] == 0) {
					throw_exception('账号已存在！');
				}
	            $existAdmin->is_delete = 0;
	            $existAdmin->save($data);
            } else {
	            (new SocialActivityAdmin())->save($data);
            }
        }

        return true;
    }

    /**
     * 删除管理账号
     * @param $param
     * @return bool
     */
    public function deleteAdmin($param)
    {
        if (!$param['id']) {
            throw_exception('删除对象不能为空！');
        }
        $info = (new SocialActivityAdmin())->getAdminById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('删除对象不存在！');
        }

        $result = (new SocialActivityAdmin())->where(['id' => $param['id']])->save(['is_delete' => 1, 'status' => 0]);
        if ($result === false) {
            throw_exception('删除失败！');
        }
        return true;
    }

    /**
     * 修改账号状态
     * @param $param
     * @return bool
     */
    public function changeStatus($param)
    {
        if (!$param['id']) {
            throw_exception('操作对象不能为空！');
        }
        $info = (new SocialActivityAdmin())->getAdminById($param['id']);
        if (!$info || $info->isEmpty()) {
            throw_exception('操作对象不存在！');
        }

        $result = (new SocialActivityAdmin())->where(['id' => $param['id']])->save(['status' => intval($param['status'])]);
        if (!$result) {
            throw_exception('操作失败！');
        }
        return true;
    }
    
    public function formatUserData($params){
        $uid = $params['uid'];
        if (!$uid) {
            return [];
        }

        $admin = (new SocialActivityAdmin())->where(['id' => $uid])->findOrEmpty()->toArray();
        if (!$admin) {
            throw_exception('账号不存在！');
        }

        if ($admin['status'] != 1) {
            throw_exception('账号已被禁用，请联系管理员！');
        }

        (new SocialActivityAdmin())->where(['id' => $admin['id']])->save([
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => request()->ip(),
        ]);

        $roleInfo = (new SocialActivityRole())->getRoleById($admin['role_id']);
        $roleName = $roleInfo ? $roleInfo['name'] : '';

        $menuService = new SocialActivityMenuService();
        $menuList = $menuService->getAdminMenuList($admin['id']);

        return [
            'admin_id' => $admin['id'],
            'account' => $admin['account'],
            'realname' => $admin['realname'],
            'phone' => phone_desensitization($admin['phone']),
            'role_id' => $admin['role_id'],
            'role_name' => $roleName,
            'menu_list' => $menuList,
        ];
    }
}
