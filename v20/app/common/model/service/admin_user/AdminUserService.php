<?php
/**
 * 系统后台用户登录权限服务
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\common\model\service\admin_user;
use app\common\model\db\Admin as UserModel;
// use app\common\model\service\SystemMenuService;
use app\common\model\service\AreaService;
use app\common\model\service\AreaStreetService;
use app\traits\CommonLogTraits;
use token\Token;
class AdminUserService {

	use CommonLogTraits;

    public $userObj = null;
    public function __construct()
    {
        $this->userObj = new UserModel();
    }

    public function login($data) {
	    $login_type = '';
        switch($data['ltype']){
            case '1':
                // 用户名密码登录
                // 用户信息
	            $login_type = '账号密码登录';
                $user = $this->getNormalUserByAccount($data['account']);
                if(!$user) {
	                $logData['account']         = $data['account'];
	                $logData['realname']        = '';
	                $logData['login_type']      = $login_type;
	                $logData['login_status']    = 0;
	                $logData['reson']           = '用户不存在';
	                $this->laterAdminLoginLogQueue($logData);
                    throw new \think\Exception("账号或密码错误");
                }

                if($user['pwd'] != md5($data['pwd'])) {
	                $logData['account']         = $data['account'];
	                $logData['realname']        = '';
	                $logData['login_type']      = $login_type;
	                $logData['login_status']    = 0;
	                $logData['reson']           = '输入的密码错误';
	                $this->laterAdminLoginLogQueue($logData);
                    throw new \think\Exception("账号或密码错误");
                }
                break;
            case '2':
                // 扫码登录
                // 用户信息
	            $login_type = '扫码登录';
                $user = $this->getNormalUserByid($data['id']);
                if(!$user) {
	                $logData['account']         = '';
	                $logData['realname']        = '';
	                $logData['login_type']      = $login_type;
	                $logData['login_status']    = 0;
	                $logData['reson']           = '用户不存在';
	                $this->laterAdminLoginLogQueue($logData);
                    throw new \think\Exception("账号或密码错误");
                }

                break;
                case '3':
                    // 管理员伪登录
                    $login_type = '楼宇伪登录';
                    $user = $this->getNormalUserByid($data['id']);
                    if(!$user) {
                        $logData['account']         = '';
                        $logData['realname']        = '';
                        $logData['login_type']      = $login_type;
                        $logData['login_status']    = 0;
                        $logData['reson']           = '用户不存在';
                        $this->laterAdminLoginLogQueue($logData);
                        throw new \think\Exception("账号或密码错误");
                    }
    
                    break;
        }  

        // 用户ID
        $userId = $user['id'];

        // 账号名
        $account = $user['account'];

        // 更新数据
        $updateData['id'] = $user['id'];
        $updateData['last_ip'] = request()->ip();
        $updateData['last_time'] = time();
        $updateData['login_count'] = $user['login_count']+1;

		$logData['account']     = $account;
		$logData['realname']    = $user['realname'];
		$logData['login_type']  = $login_type;
		$this->laterAdminLoginLogQueue($logData);

        if($this->update($userId,$updateData)){

        }else{
	        $logData['account']         = $account;
	        $logData['realname']        = $user['realname'];
	        $logData['login_type']      = $login_type;
	        $logData['login_status']    = 0;
	        $logData['reson']           = '更新用户信息失败';
	        $this->laterAdminLoginLogQueue($logData);
            throw new \think\Exception("更新用户信息失败");
        }

        // 生成ticket
        $ticket = Token::createToken($userId);
        return $ticket ? ["ticket" => $ticket] : false;
    }

    /**
     * 返回正常用户数据
     * @param $account
     * @return array
     */
    public function getNormalUserByAccount($account) {
        $user = $this->userObj->getUserByAccount($account);
        if(!$user || $user->status != 1) {
            return [];
        }
        return $user->toArray();
    }

    /**
     * 获得账号的权限
     * @param $id
     * @return array
     */
    public function getUserRole($id) {
        $user = $this->getNormalUserById($id);
        if(!$user) {
            return [];
        }
        $area=array();
        $user['admin_area_type']=0;
        if ($user['area_id']>0) {
            $areaServiceObj = new AreaService();
            $area = $areaServiceObj->getAreaByAreaId($user['area_id']);
            if($area && isset($area['area_type'])){
                $user['admin_area_type']=$area['area_type'];
            }
        }
        // 展示的管理员名
        $showAccount = '超级管理员' . ($user['realname'] ? '(' . $user['realname'] . ')' : "");
        if ($user['level'] == 1) {
            if ($user['street_id']) {
                $street = (new AreaStreetService())->getStreetById($user['street_id'], 'area_name');
                $showAccount = $street['area_name'] . '管理员' . ($user['realname'] ? '(' . $user['realname'] . ')' : "");
                $user['admin_area_type'] = 8;
            } else if ($user['area_id']) {
                $areaServiceObj = new AreaService();
                $area = $areaServiceObj->getAreaByAreaId($user['area_id']);
                $showAccount = $area['area_name'] . '管理员' . ($user['realname'] ? '(' . $user['realname'] . ')' : "");
            }
        } else if ($user['level'] == 2) {
            $showAccount = '超级管理员' . ($user['realname'] ? '(' . $user['realname'] . ')' : "");
            $user['admin_area_type'] = 9;
        } else {
            $showAccount = '普通管理员' . ($user['realname'] ? '(' . $user['realname'] . ')' : "");
        }
        $user['show_account'] = $showAccount;

        // import('ORG.Net.IpLocation');
        // $IpLocation = new IpLocation();
        // $last_location = $IpLocation->getlocation($user['last_ip']);
        // $user['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
        // $user['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');

        if((($user['menus'] && !strpos($user['menus'],'7')) || !$user['menus']) && $user['level']!=2) $user['menus'].=',7'; //强制给用户修改信息的权限

        if( $user['area_id']!=0) $user['menus'].=',9999'; //强制给用户修改信息的权限

       
        /*if($user['level']<2){
            $user['menus'] = str_replace('231,','',$user['menus']);
        }*/

        if ($user['menus']) { 
            // 获得用户有权限的菜单
            $systemMenuService = new SystemMenuService();
            $where = [];
            $menuIds = array_filter(explode(',', $user['menus']));
            $menusGalias = $systemMenuService->getNormalMenuList($where);
            foreach ($menusGalias as $key => $mg) {
                if (!in_array($mg['id'], $menuIds)) {
                    unset($menusGalias[$key]);
                    continue;
                }
                $user['menus_galias'][] = $mg['galias'];
            }
            $user['menus'] = $menuIds;
        }else{
            $user['menus'] = [];
        }

        // 排序
        $sort_menus = [];
        if ($user['sort_menus']){
            $sort_menus =   explode(";", $user['sort_menus']);
            foreach($sort_menus as $v){
                $exp    =   explode(',',$v);
                $sort_menus[$exp[0]] =  $exp[1];
            }
            $user['sort_menus'] = $sort_menus;
        }
        return $user;
    }

    /**
     * 组装前端数据
     * @param $id
     * @return array
     */
    public function formatUserData($systemUser) {
        if (!$systemUser) {
            return [];
        }

        $returnArr = [];
        $returnArr['name'] = $systemUser['show_account'];
        $returnArr['role'] = [];
        $returnArr['role']['id'] = 'admin';
        $returnArr['role']['name'] = '管理员';
        $returnArr['role']['permissions'][] = [
            'permissionId' => 'system', // 菜单的权限标识
            // 'actions' => null, // 该菜单下的所有按钮集合,可不传
            'actionEntitySet' => [ // 本用户能看见和操作的按钮
                [
                     'action' => 'query',
                     'describe' => '查询',
                     'defaultCheck' => true
                ],
                [
                     'action' => 'add',
                     'describe' => '新增',
                     'defaultCheck' => false
                ],
                [
                     'action' => 'delete',
                     'describe' => '删除',
                     'defaultCheck' => false
                ],
                [
                     'action' => 'edit',
                     'describe' => '修改',
                     'defaultCheck' => false
                ],
                [
                     'action' => 'enable',
                     'describe' => '是否禁用',
                     'defaultCheck' => false
                ],
            ]
        ];
        
        return $returnArr;
    }

    /**
     * 通过管理员父id查找所有子管理员
     * @param $fid 管理员id
     * @return array
     */
    public function getAdminByFid($fid){
        if(empty($fid)){
            return [$fid];
        }

        $where = [
            'fid' => $fid
        ];
        $adminList = $this->getList($where);
        if(empty($adminList)){
            return [$fid];
        }

        $returnArr = [];
        $returnArr = $adminIdArr = array_column($adminList,'id');
        while($adminList){
            $where = [
                ['fid' , 'in', implode(',', $adminIdArr)]
            ];
            $adminList = $this->getList($where);
            $adminIdArr = array_column($adminList,'id');
            if($adminIdArr){
                $returnArr = array_merge($returnArr, $adminIdArr);
            }
        }
        $returnArr[] = $fid;
        return $returnArr;
    }
    /**
     * 返回正常用户数据
     * @param $id
     * @return array
     */
    public function getNormalUserById($id) {
        $user = $this->userObj->getUserById($id);
        if(!$user || $user->status != 1) {
            return [];
        }
        return $user->toArray();
    }


    /**
     * 更新用户数据
     * @param $id
     * @param $data
     * @return array
     */
    public function update($id, $data) {
        $user = $this->getNormalUserById($id);
        if(!$user) {
            throw new \think\Exception("不存在该用户");
        }
       
        return $this->userObj->updateById($id, $data);
    }    

    /**
     * 根据条件获取一条数据
     * @param $where array 
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
           return [];
        }

        $admin = $this->userObj->getOne($where);
        if(!$admin) {
            return [];
        }
        
        return $admin->toArray(); 
    }

    /**
     * 根据条件返回数据列表
     * @param $where array 
     * @return array
     */
    public function getList($where) {
        if(empty($where)){
           return [];
        }

        $admin = $this->userObj->getList($where);
        if(!$admin) {
            return [];
        }
        
        return $admin->toArray(); 
    }
}