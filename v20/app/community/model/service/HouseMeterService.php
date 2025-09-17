<?php
/**
 * Created by PhpStorm.
 * User: zhubaodi
 * Date Time: 2021/4/8 13:33
 */

namespace app\community\model\service;

use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\UploadFileService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\Express;
use app\community\model\db\HouseMeterAdminElectric;
use app\community\model\db\HouseMeterAdminVillage;
use app\community\model\db\HouseMeterAdminUser;
use app\community\model\db\HouseMeterUserPayorder;
use app\community\model\db\HouseMeterElectricGroup;
use app\community\model\db\HouseMeterElectricPrice;
use app\community\model\db\HouseMeterElectricRealtime;
use app\community\model\db\HouseMeterReadingSys;
use app\community\model\db\Country;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\Area;
use app\community\model\db\AreaStreet;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;
use token\Token;
use think\facade\Request;
use app\community\model\db\HouseMeterWarn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use app\community\model\db\HouseVillagePublicArea;
class HouseMeterService
{
    public $base_url = '';
    public $street_url = '';

    public function __construct()
    {
        // if(C('config.local_dev')){
        // $this->base_url = '/packapp/plat_dev/';
        // }else{
        if (cfg('system_type') == 'village') {
            $this->base_url = '/packapp/village/';
        } else {
            $this->base_url = '/packapp/plat/';
        }
        $this->street_url = '/packapp/street/';
        // }
    }


    //缴费类型 0:预交费 1:余额扣费
    public $payment_type_arr = [
        0 => '预交金', 1 => '抄表扣费'
    ];

    //缴费项目 0:电费 1:水费 2:燃气费
    public $payment_num_arr = [
        0 => '电费', 1 => '水费', 2 => '燃气费'
    ];
    //支付类型 0:支付宝 1:微信 2:银联 3:余额抵扣
    public $pay_type_arr = [
        'alipay' => '支付宝', 'wechat' => '微信', 'unionpay' => '银联', 'balance' => '余额抵扣', 'meterPay' => '预交金抵扣'
    ];
    // 电表状态
    public $electric_status_arr = [
        1 => '正常', 2 => '异常'
    ];
    // 电表分组状态
    public $group_status_arr = [
        1 => '正常', 2 => '关闭'
    ];
    // 电表分组状态
    public $house_type_arr = [
        1 => '住宅', 2 => '商铺', 3 => '办公'
    ];

    /**
     * 登录接口
     * @param array $data
     * @return array
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time: 2021/4/8 13:32
     */
    public function login($data)
    {
        $service_admin_user = new HouseMeterAdminUser();
        $house_admin_user = $service_admin_user->getInfo(['username' => $data['username'], 'status' => 0]);
        if (!$house_admin_user) {
            throw new \think\Exception("账号或密码错误");
        }
        if ($house_admin_user) {
            /* if ($house_admin_user['status']==1){
                 throw new \think\Exception("账号不存在！");
             }*/
            $pwd = md5($data['password']);
            if ($pwd != $house_admin_user['password']) {
                throw new \think\Exception("账号或密码错误！");
            }
            $log['id'] = $house_admin_user['id'];
            $log['last_login_time'] = $_SERVER['REQUEST_TIME'];
            $ip = Request::ip();
            $log['ip'] = $ip;
            $service_admin_user->addLoginLog($log);
            // 生成ticket
            $ticket = Token::createToken($house_admin_user['id'], $house_admin_user['px']);
            $data['ticket'] = $ticket;
            unset($data['password']);
            return $data;
        } else {
            throw new \think\Exception("账号不存在！请联系工作人员添加！");
        }

    }

    /**
     * 组装数据
     * @param integer $admin_id
     * @return array
     * @author: zhubaodi
     * @date_time: 2021/4/8 15:45
     */
    public function formatUserData($admin_id)
    {
        if (!$admin_id) {
            return [];
        }
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);

        $returnArr = [];
        $returnArr['name'] = $admin_user_info['name'];
        $returnArr['qx'] = $admin_user_info['qx'];
        $returnArr['role']['id'] = $admin_user_info['id'];
        $returnArr['role']['username'] = $admin_user_info['username'];
        $returnArr['role']['name'] = $admin_user_info['name'];
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
     * 修改密码
     * @param integer $admin_id 总管理员id
     * @param array $data 对修改信息数组
     * @return string
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time: 2021/4/8 15:45
     */
    public function editPassword($admin_id, $data)
    {
        if ($data['new_password'] != $data['confirm_password']) {
            throw new \think\Exception("确认密码时与新密码不一致，请重新输入！");
        }
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if (!$admin_user_info) {
            throw new \think\Exception("管理员不存在");
        }
        if ($admin_user_info) {
            $pwd = md5($data['old_password']);
            if ($pwd != $admin_user_info['password']) {
                throw new \think\Exception("原密码错误！");
            }
        }
        $data['new_password'] = md5($data['new_password']);
        $edit_info = $service_admin_user->saveOne(['id' => $admin_id], ['password' => $data['new_password']]);
        return $edit_info;
    }


    /**
     * 获取管理员列表
     * @param int $admin_id 管理员id
     * @param $page
     * @param $limit
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/8 16:57
     */
    public function getAdminUserList($admin_id, $page, $limit)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if ($admin_user_info['qx'] != 1) {
            throw new \think\Exception("该用户没有获取管理员信息的权限！");
        }
        $where = ['status' => 0];
        $count = $service_admin_user->getCount($where);
        $list = $service_admin_user->getList($where, true, $page, $limit, 'id ASC');

        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $k => $value) {
                $list[$k] = $value;
                if (empty($value['remarks']) || $value['remarks'] = '') {
                    $list[$k]['remarks'] = '--';
                }

            }
        }
        $data = [];
        $data['count'] = $count;
        $data['list'] = $list;

        return $data;
    }


    /**
     * 获取管理员信息详情
     * @param integer $admin_id 总管理员id
     * @param integer $id 管理员id
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/8 16:57
     */
    public function getAdminUserInfo($admin_id, $id)
    {


        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if ($admin_user_info['qx'] != 1) {
            throw new \think\Exception("该用户没有获取管理员信息的权限！");
        }
        $user_info = $service_admin_user->getInfo(['id' => $id]);
        if ($user_info) {
            unset($user_info['password']);
        }
        return $user_info;
    }

    /**
     * 获取电表分组信息详情
     * @param integer $admin_id 总管理员id
     * @param integer $id 管理员id
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/8 16:57
     */
    public function getElectricGroupInfo($admin_id, $id)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if (!$admin_user_info) {
            throw new \think\Exception("管理员不存在！");
        }
        $where[] = ['id', '=', $id];
        // 初始化 数据层
        $service_group_user = new HouseMeterElectricGroup();
        $user_info = $service_group_user->getInfo($where);
        return $user_info;

    }

    /**
     * 获取电表信息详情
     * @param integer $admin_id 总管理员id
     * @param integer $id 管理员id
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/8 16:57
     */
    public function getElectricInfo($admin_id, $id)
    {

        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if (!$admin_user_info) {
            throw new \think\Exception("管理员不存在！");
        }
        $where[] = ['id', '=', $id];
        // 初始化 数据层
        $service_electric_info = new HouseMeterAdminElectric();
        $electric_info = $service_electric_info->getInfo($where);
        return $electric_info;

    }

    /**
     * 添加管理员
     * @param integer $id 总管理员id
     * @param array $data 添加数据数组
     * @param array $village_id 待绑定的小区id
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/8 18:50
     */
    public function addAdminUser($id, $data, $village_id)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $id]);
        if ($admin_user_info['qx'] != 1) {
            throw new \think\Exception("该用户没有添加管理员信息的权限！");
        }
        if ($data['password'] != $data['confirm_password']) {
            throw new \think\Exception("确认密码时与新密码不一致，请重新输入");
        }

        $info = $service_admin_user->getInfo(['username' => $data['username'], 'status' => 0]);
        if ($info) {
            throw new \think\Exception("登录账号已使用，请重新输入");
        }
        $user_data = [
            'username' => $data['username'],
            'password' => md5($data['password']),
            'name' => $data['name'],
            'phone' => $data['phone'],
            'qx' => $data['qx'],
            'status' => 0,
            'add_time' => time(),
            'remarks' => $data['remark'],

        ];
        $uid = $service_admin_user->insertOne($user_data);
        if (!empty($village_id)) {
            foreach ($village_id as $value) {
                $village = $this->addMeterVillage($id, $uid, $value);
            }
        }
        return $uid;
    }


    /**
     * 编辑管理员信息
     * @param integer $id 总管理员id
     * @param array $data 添加数据数组
     * @return string
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/8 18:50
     */
    public function editAdminUser($id, $data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $id]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在");
        }
        if ($admin_user_info['qx'] != 1) {
            throw new \think\Exception("该用户没有编辑管理员信息的权限！");
        }
        if (strlen($data['password']) < 6) {
            throw new \think\Exception("密码长度不能低于六位！");
        }
        if ($data['password'] != $data['confirm_password']) {
            throw new \think\Exception("确认密码时与密码不一致，请重新输入！");
        }
        $user_info = $service_admin_user->getInfo(['id' => $data['id']]);
        if (!$user_info) {
            throw new \think\Exception("管理员不存在！");
        }
        $user_data = [
            'password' => md5($data['password']),
            'name' => $data['name'],
            'phone' => $data['phone'],
            'qx' => $data['qx'],
            'status' => 0,
            'add_time' => time(),
            'remarks' => $data['remark'],

        ];
        $uid = $service_admin_user->saveOne(['id' => $data['id']], $user_data);
        return $uid;
    }


    /**
     * 删除管理员信息
     * @param integer $id 管理员id
     * @param integer $admin_id 总管理员id
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/8 18:50
     */
    public function deleteAdminUser($admin_id, $id)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if (!$admin_user_info) {
            throw new \think\Exception("该用户不存在");
        }
        if ($admin_user_info['qx'] != 1) {
            throw new \think\Exception("该用户没有删除管理员信息的权限！");
        }

        $user_info = $service_admin_user->getInfo(['id' => $id]);
        if (!$user_info) {
            throw new \think\Exception("管理员不存在！");
        }
        $uid = $service_admin_user->saveOne(['id' => $id], ['status' => 1]);
        return $uid;
    }


    /**
     * 获取已绑定小区列表
     * @param array $data 查询条件字段
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/9 13:27
     */
    public function getVillageBindList($data, $page, $limit)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);

        if ($admin_user_info['qx'] != 1) {
            throw new \think\Exception("该用户没有获取已绑定小区信息的权限！");
        }

        $where = [];
        if (isset($data['province_id']) && !empty($data['province_id'])) {
            $where[] = ['a.province_id', '=', $data['province_id']];
        }
        if (isset($data['city_id']) && !empty($data['city_id'])) {
            $where[] = ['a.city_id', '=', $data['city_id']];
        }
        if (isset($data['area_id']) && !empty($data['area_id'])) {
            $where[] = ['a.area_id', '=', $data['area_id']];
        }
        if (isset($data['street_id']) && !empty($data['street_id'])) {
            $where[] = ['a.street_id', '=', $data['street_id']];
        }
        if (isset($data['community_id']) && !empty($data['community_id'])) {
            $where[] = ['a.community_id', '=', $data['community_id']];
        }
        if (isset($data['village_id']) && !empty($data['village_id'])) {
            $where[] = ['a.village_id', '=', $data['village_id']];
        }
        if (isset($data['village_name']) && !empty($data['village_name'])) {
            $where[] = ['a.village_name', '=', $data['village_name']];
        }
        $data1 = [];
        if ($data['type'] != 1) {
            if ($data['uid']) {
                $where[] = ['b.admin_uid', '=', $data['uid']];
            } else {
                throw new \think\Exception("请上传管理员id！");
            }
            $service_admin_village = new HouseMeterAdminVillage();
            $list = $service_admin_village->getList($where, 'distinct  a.village_id,b.id,a.village_name,a.village_address');
            $data1['list'] = $list;
            $data1['count'] = 0;
        } else {
            $service_village = new HouseVillage();
            $count = $service_village->getNum1($where, 'a.village_id');
            $list = $service_village->getMeterList($where, 'distinct  a.village_id,a.village_name,a.village_address', $page, $limit);
            $data1['count'] = $count;
            $data1['list'] = $list;
        }
        return $data1;
    }


    /**
     * 绑定小区
     * @param integer $admin_id 总管理员id
     * @param integer $uid 管理员id
     * @param integer $village_id 小区id
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/8 18:50
     */
    public function addMeterVillage($admin_id, $uid, $village_id)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if ($admin_user_info['qx'] != 1) {
            throw new \think\Exception("该用户没有绑定小区信息的权限！");
        }
        $user_info = $service_admin_user->getInfo(['id' => $uid]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $service_house_village = new HouseVillage();
        $village_info = $service_house_village->getOne($village_id);
        if (!$village_info) {
            throw new \think\Exception("该小区不存在！");
        }
        $house_meter_village = new HouseMeterAdminVillage();
        /*$village_meter_info = $house_meter_village->getOne($village_id);
        if ($village_meter_info) {
            throw new \think\Exception($village_meter_info['village_name']."已被绑定！");
        }*/

        $village_data = [
            'province_id' => $village_info['province_id'],
            'city_id' => $village_info['city_id'],
            'area_id' => $village_info['area_id'],
            'street_id' => $village_info['street_id'],
            'community_id' => $village_info['community_id'],
            'village_id' => $village_info['village_id'],
            'add_time' => time(),
            'admin_uid' => $uid,

        ];
        $bind_id = $house_meter_village->insertOne($village_data);
        return $bind_id;
    }


    /**
     * 批量绑定小区
     * @param integer $admin_id 总管理员id
     * @param integer $uid 管理员id
     * @param integer $village_id 小区id
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/8 18:50
     */
    public function addAllMeterVillage($admin_id, $uid, $village_id)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if ($admin_user_info['qx'] != 1) {
            return "该用户没有绑定小区信息的权限！";
        }
        $user_info = $service_admin_user->getInfo(['id' => $uid]);
        if (!$user_info) {
            return "该用户不存在！";
        }
        $service_house_village = new HouseVillage();
        $village_info = $service_house_village->getOne($village_id);
        if (!$village_info) {
            return "该小区不存在！";
        }
        $house_meter_village = new HouseMeterAdminVillage();
        /* $village_meter_info = $house_meter_village->getOne($village_id);
       if ($village_meter_info) {
           return $village_info['village_name']."已被绑定！";
       }*/

        $village_data = [
            'province_id' => $village_info['province_id'],
            'city_id' => $village_info['city_id'],
            'area_id' => $village_info['area_id'],
            'street_id' => $village_info['street_id'],
            'community_id' => $village_info['community_id'],
            'village_id' => $village_info['village_id'],
            'add_time' => time(),
            'admin_uid' => $uid,

        ];
        $bind_id = $house_meter_village->insertOne($village_data);
        return $bind_id;
    }

    /**
     * 移除小区绑定信息
     * @param integer $village_id 小区id
     * @param integer $uid 管理员id
     * @param integer $admin_id 总管理员id
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/8 18:50
     */
    public function deleteMeterVillage($admin_id, $uid, $village_id)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if ($admin_user_info['qx'] != 1) {
            throw new \think\Exception("该用户没有移除小区绑定信息的权限！");
        }
        $user_info = $service_admin_user->getInfo(['id' => $uid]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $house_meter_village = new HouseMeterAdminVillage();
        $village_meter_info = $house_meter_village->getInfo(['id' => $village_id, 'admin_uid' => $uid]);
        if (!$village_meter_info) {
            throw new \think\Exception("该小区没有绑定信息！");
        }
        $bind_id = $house_meter_village->deleteInfo(['id' => $village_id, 'admin_uid' => $uid]);
        return $bind_id;
    }


    /**
     * 获取电表列表
     * @param array $data 查询条件字段
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/9 16:27
     */
    public function getMeterElectricList($data)
    {

        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $where = [];
        if ($user_info['qx'] != 1) {
            $service_admin_village = new HouseMeterAdminVillage();
            $village_info = $service_admin_village->getLists(['admin_uid' => $data['admin_id']]);
            if (!$village_info) {
                throw new \think\Exception("管理员未绑定小区！");
            }
            $village_info = $village_info->toArray();

            $vv = [];
            foreach ($village_info as $value) {
                $vv[] = $value['village_id'];
            }
            $vv_arr = implode(',', $vv);

            if (empty($data['village_id'])) {
                $where[] = ['a.village_id', 'in', $vv_arr];
            }
        }
        if ($data['province_id']) {
            $where[] = ['a.province_id', '=', $data['province_id']];
        }
        if ($data['city_id']) {
            $where[] = ['a.city_id', '=', $data['city_id']];
        }
        if ($data['area_id']) {
            $where[] = ['a.area_id', '=', $data['area_id']];
        }
        if ($data['street_id']) {
            $where[] = ['a.street_id', '=', $data['street_id']];
        }
        if ($data['community_id']) {
            $where[] = ['a.community_id', '=', $data['community_id']];
        }
        if ($data['village_id']) {
            $where[] = ['a.village_id', '=', $data['village_id']];
        }
        if ($data['single_id']) {
            $where[] = ['a.single_id', '=', $data['single_id']];
        }
        if ($data['floor_id']) {
            $where[] = ['a.floor_id', '=', $data['floor_id']];
        }
        if ($data['layer_id']) {
            $where[] = ['a.layer_id', '=', $data['layer_id']];
        }
        if ($data['vacancy_id']) {
            $where[] = ['a.vacancy_id', '=', $data['vacancy_id']];
        }
        if ($data['group_name']) {
            $where[] = ['a.group_id', '=', $data['group_name']];
        }
        if ($data['electric_name']) {
            $where[] = ['a.electric_name', 'like', '%' . $data['electric_name'] . '%'];
        }

        //获取电表的当前指数
        $service_realtime = new HouseMeterElectricRealtime();
        $service_admin_electric = new HouseMeterAdminElectric();
        $list = $service_admin_electric->getList($where, 'a.electric_name,a.id,a.swicth,a.disabled,a.group_id,a.status,a.village_address,b.group_name,a.remaining_capacity', $data['page'], $data['limit']);

        $count = $service_admin_electric->getCount($where);
        if (!$list || $list->isEmpty()) {
            $user_list = [];
        } else {
            $user_list = [];
            foreach ($list as $k => $value) {

                $realtime_list = $service_realtime->getList(['electric_id' => $value['id'], 'status' => 0], 'id,electric_id,end_num', 1, 1, 'id DESC');
                $arr = [];
                if (!empty($realtime_list)) {
                    $realtime_list = $realtime_list->toArray();
                    $realtime_info = reset($realtime_list);
                    if (isset($realtime_info['end_num']) && $realtime_info['end_num']) {
                        $value['begin_num'] = '当前电表数： ' . $realtime_info['end_num'];
                    } else {
                        $value['begin_num'] = '当前电表数： 0';
                    }
                } else {
                    $value['begin_num'] = '当前电表数：0';
                }

                $user_list[$k] = $value;
                $user_list[$k]['status'] = $this->electric_status_arr[$value['status'] + 1];
            }
        }
        $data = [];
        $data['count'] = $count;
        $data['list'] = $user_list;

        return $data;
    }


    /**
     * 添加电表
     * @param array $data
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/9 17:50
     */
    public function addMeterElectric($data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $service_electric_group = new HouseMeterElectricGroup();
        $group_info = $service_electric_group->getOne($data['group_id']);
        if (!$group_info) {
            throw new \think\Exception("该分组不存在！");
        }
        $house_meter_electric = new HouseMeterAdminElectric();
        $vacancy_info = $house_meter_electric->getInfo(['vacancy_id' => $data['vacancy_id']]);
        if ($vacancy_info) {
            throw new \think\Exception("该房间已添加电表！");
        }
        $house_meter_village = new HouseMeterAdminVillage();
        $village_info = $house_meter_village->getInfo(['village_id' => $data['village_id'], 'admin_uid' => $data['admin_id']]);
        if (!$village_info && $user_info['qx'] != 1) {
            throw new \think\Exception("当前用户无权限添加电表到该房间！");
        }
        $service_area = new Area();// 查询市
        $service_street = new AreaStreet();// 查询市
        $house_village = new HouseVillage();
        $village_single = new HouseVillageSingle();
        $village_floor = new HouseVillageFloor();
        $village_layer = new HouseVillageLayer();
        $vacancy = new HouseVillageUserVacancy();

        $province_info = $service_area->getOne(['area_id' => $data['province_id'], 'area_type' => 1, 'is_open' => 1]);
        if (empty($province_info)) {
            $value['failReason'] = '所属省不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }

        $city_info = $service_area->getOne(['area_type' => 2, 'is_open' => 1, 'area_id' => $data['city_id'], 'area_pid' => $province_info['area_id']]);
        if (empty($city_info)) {
            $value['failReason'] = '所属市不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $area_info = $service_area->getOne(['area_id' => $data['area_id'], 'area_type' => 3, 'is_open' => 1, 'area_pid' => $city_info['area_id']]);
        if (empty($area_info)) {
            $value['failReason'] = '所属区/县不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $street_info = $service_street->getOne(['area_type' => 0, 'is_open' => 1, 'area_pid' => $area_info['area_id'], 'area_id' => $data['street_id']]);
        if (empty($street_info)) {
            $value['failReason'] = '所属街道不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }

        $community_info = $service_street->getOne(['area_type' => 1, 'is_open' => 1, 'area_pid' => $street_info['area_id'], 'area_id' => $data['community_id']]);
        if (empty($community_info)) {
            $value['failReason'] = '所属社区不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $village_info = $house_village->getInfo(['village_id' => $data['village_id'], 'status' => 1, 'community_id' => $community_info['area_id']]);
        if (empty($village_info)) {
            $value['failReason'] = '所属小区不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $single_info = $village_single->getOne(['id' => $data['single_id'], 'status' => 1, 'village_id' => $village_info['village_id']]);
        if (empty($single_info)) {
            $value['failReason'] = '所属楼栋不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $floor_info = $village_floor->getOne(['floor_id' => $data['floor_id'], 'status' => 1, 'single_id' => $single_info['id']]);
        if (empty($floor_info)) {
            $value['failReason'] = '所属单元不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $layer_info = $village_layer->getOne(['id' => $data['layer_id'], 'status' => 1, 'floor_id' => $floor_info['floor_id']]);
        if (empty($layer_info)) {
            $value['failReason'] = '所属楼层不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $vacancy_info = $vacancy->getOne(['pigcms_id' => $data['vacancy_id'], 'status' => [1, 2, 3], 'layer_id' => $layer_info['id']]);
        if (empty($vacancy_info)) {
            $value['failReason'] = '所属房间号不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $village_address = $province_info['area_name'] . $city_info['area_name'] . $area_info['area_name'] . $street_info['area_name'] . $community_info['area_name'] . $village_info['village_name'] . $single_info['single_name'] . $floor_info['floor_name'] . $layer_info['layer_name'] . $vacancy_info['room'];
        /* $villageTreeList = $this->getVillageTreeList();
       $area_address = $villageTreeList['area'][$data['province_id']]['name'] . $villageTreeList['area'][$data['province_id']][$data['city_id']]['name'] . $villageTreeList['area'][$data['province_id']][$data['city_id']][$data['area_id']]['name'];
       $area_street_address = $villageTreeList['village'][$data['street_id']]['name'] . $villageTreeList['village'][$data['street_id']]['list'][$data['community_id']]['name'] . $villageTreeList['village'][$data['street_id']]['list'][$data['community_id']]['list'][$data['village_id']]['name'];
       $single_address = $villageTreeList['single'][$data['single_id']]['name'] . $villageTreeList['single'][$data['single_id']]['list'][$data['floor_id']]['name'];
       $vacancy_address = $villageTreeList['vacancy'][$data['layer_id']]['name'] . $villageTreeList['vacancy'][$data['layer_id']]['list'][$data['vacancy_id']]['name'];
       $village_address = $area_address . $area_street_address . $single_address . $vacancy_address;*/

        $electric_data = [
            'province_id' => $data['province_id'],
            'city_id' => $data['city_id'],
            'area_id' => $data['area_id'],
            'street_id' => $data['street_id'],
            'community_id' => $data['community_id'],
            'village_id' => $data['village_id'],
            'single_id' => $data['single_id'],
            'floor_id' => $data['floor_id'],
            'layer_id' => $data['layer_id'],
            'vacancy_id' => $data['vacancy_id'],
            'group_id' => $data['group_id'],
            'measure_id' => $data['measure_id'],
            'electric_name' => $data['electric_name'],
            'admin_uid' => $data['admin_id'],
            'electric_type' => $data['electric_type'],
            'electric_address' => $data['electric_address'],
            'electric_price_id' => $data['electric_price_id'],
            'unit_price' => $data['unit_price'],
            'rate' => $data['rate'],
            'update_time' => time(),
            'add_time' => time(),
            'village_address' => $village_address,


        ];
        // print_r($electric_data);exit;
        $electric_id = $house_meter_electric->insertOne($electric_data);
        return $electric_id;
    }


    /**
     * 获取省市至房间的树形结构数据
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author: zhubaodi
     * @date_time:2021/4/9 13:27
     */
    public function getVillageTreeList()
    {

        // 查询省
        $area = new Area();
        $province_list = $area->getList(['is_open' => 1, 'area_type' => 1], 'area_id,area_id as id,area_name as name,area_name,area_pid');
        // 查询市
        $city_list = $area->getList(['is_open' => 1, 'area_type' => 2], 'area_id as id,area_name as name,area_id,area_name,area_pid');
        // 查询区
        $area_list = $area->getList(['is_open' => 1, 'area_type' => 3], 'area_id as id,area_name as name,area_id,area_name,area_pid');

        $areaArr = [];
        if ($province_list) {
            foreach ($province_list as $vp) {
                $areaArr[$vp['area_id']]['name'] = $vp['area_name'];
                $areaArr[$vp['area_id']]['id'] = $vp['area_id'];
                $areaArr[$vp['area_id']]['pid'] = $vp['area_pid'];

                if ($city_list) {
                    foreach ($city_list as $kc => $vc) {

                        if ($vp['area_id'] == $vc['area_pid']) {
                            $areaArr[$vp['area_id']][$vc['area_id']]['name'] = $vc['area_name'];
                            $areaArr[$vp['area_id']][$vc['area_id']]['id'] = $vc['area_id'];
                            $areaArr[$vp['area_id']][$vc['area_id']]['pid'] = $vc['area_pid'];
                            unset($city_list[$kc]);
                        }

                        if ($area_list) {
                            foreach ($area_list as $ka => $va) {
                                if ($vc['area_id'] == $va['area_pid']) {
                                    $areaArr[$vp['area_id']][$vc['area_id']][$va['area_id']]['name'] = $va['area_name'];
                                    $areaArr[$vp['area_id']][$vc['area_id']][$va['area_id']]['id'] = $va['area_id'];
                                    $areaArr[$vp['area_id']][$vc['area_id']][$va['area_id']]['pid'] = $va['area_pid'];
                                    unset($area_list[$ka]);
                                }
                            }

                        }
                    }

                }
            }
        }

        // 查询街道
        $area_street = new AreaStreet();
        $street_list = $area_street->getLists(['is_open' => 1, 'area_type' => 0], 'area_id as id,area_name as name,area_id,area_name,area_pid');
        // 查询社区
        $community_list = $area_street->getLists(['is_open' => 1, 'area_type' => 1], 'area_id as id,area_name as name,area_id,area_name,area_pid');
        // 查询小区
        $house_village = new HouseVillage();
        $village_list = $house_village->getList(['status' => 1], 'village_id as id,village_name as name,village_id,village_name,community_id');

        $villageArr = [];
        if ($street_list) {
            foreach ($street_list as $vs) {
                $villageArr[$vs['area_id']]['name'] = $vs['area_name'];
                $villageArr[$vs['area_id']]['id'] = $vs['area_id'];
                $villageArr[$vs['area_id']]['pid'] = $vs['area_pid'];
                if ($community_list) {
                    foreach ($community_list as $kkc => $vvc) {
                        if ($vs['area_id'] == $vvc['area_pid']) {
                            $villageArr[$vs['area_id']]['list'][$vvc['area_id']]['name'] = $vvc['area_name'];
                            $villageArr[$vs['area_id']]['list'][$vvc['area_id']]['id'] = $vvc['area_id'];
                            $villageArr[$vs['area_id']]['list'][$vvc['area_id']]['pid'] = $vvc['area_pid'];
                            unset($community_list[$kkc]);
                        }
                        if ($village_list) {
                            foreach ($village_list as $kk => $vv) {
                                if ($vv['community_id'] == $vvc['area_id']) {
                                    $villageArr[$vs['area_id']]['list'][$vvc['area_id']]['list'][$vv['village_id']]['name'] = $vv['village_name'];
                                    $villageArr[$vs['area_id']]['list'][$vvc['area_id']]['list'][$vv['village_id']]['id'] = $vv['village_id'];
                                    $villageArr[$vs['area_id']]['list'][$vvc['area_id']]['list'][$vv['village_id']]['pid'] = $vv['community_id'];
                                    unset($village_list[$kk]);
                                }

                            }

                        }
                    }

                }
            }
        }


        // 查询楼栋
        $village_single = new HouseVillageSingle();
        $single_list = $village_single->getList(['status' => 1], 'single_name as name,id,single_name,village_id');
        // 查询单元
        $village_floor = new HouseVillageFloor();
        $floor_list = $village_floor->getList(['status' => 1], 'floor_name as name,floor_id as id ,floor_id,floor_name,single_id');
        // 查询楼层
        $village_layer = new HouseVillageLayer();
        $layer_list = $village_layer->getList(['status' => 1], 'layer_name as name,id,layer_name,floor_id');
        // 查询房间号
        $vacancy = new HouseVillageUserVacancy();
        $vacancy_list = $vacancy->getList([['status', 'in', '1,2,3']], 'pigcms_id as id ,room as name,pigcms_id,room,layer_id');

        $singleArr = [];
        if ($single_list) {
            foreach ($single_list as $vas) {
                $singleArr[$vas['id']]['name'] = $vas['single_name'];
                $singleArr[$vas['id']]['id'] = $vas['id'];
                $singleArr[$vas['id']]['pid'] = $vas['village_id'];
                if ($floor_list) {
                    foreach ($floor_list as $kaf => $vaf) {
                        if ($vaf['single_id'] == $vas['id']) {
                            $singleArr[$vas['id']]['list'][$vaf['floor_id']]['name'] = $vaf['floor_name'];
                            $singleArr[$vas['id']]['list'][$vaf['floor_id']]['id'] = $vaf['floor_id'];
                            $singleArr[$vas['id']]['list'][$vaf['floor_id']]['pid'] = $vaf['single_id'];
                            unset($floor_list[$kaf]);
                        }


                    }

                }
            }
        }

        $vacancyArr = [];
        if ($layer_list) {
            foreach ($layer_list as $val) {
                $vacancyArr[$val['id']]['name'] = $val['layer_name'];
                $vacancyArr[$val['id']]['id'] = $val['id'];
                $vacancyArr[$val['id']]['pid'] = $val['floor_id'];


                if ($vacancy_list) {
                    foreach ($vacancy_list as $kav => $vav) {
                        if ($vav['layer_id'] == $val['id']) {
                            $vacancyArr[$val['id']]['list'][$vav['pigcms_id']]['name'] = $vav['room'];
                            $vacancyArr[$val['id']]['list'][$vav['pigcms_id']]['id'] = $vav['pigcms_id'];
                            $vacancyArr[$val['id']]['list'][$vav['pigcms_id']]['pid'] = $vav['layer_id'];
                            unset($vacancy_list[$kav]);
                        }


                    }

                }
            }

        }
        $list['area'] = $areaArr;
        $list['village'] = $villageArr;
        $list['single'] = $singleArr;
        $list['vacancy'] = $vacancyArr;

        $list['province_list'] = $province_list;
        $list['city_list'] = $city_list;
        $list['area_list'] = $area_list;
        $list['street_list'] = $street_list;
        $list['community_list'] = $community_list;
        $list['village_list'] = $village_list;
        $list['single_list'] = $single_list;
        $list['floor_list'] = $floor_list;
        if (!empty($layer_list)) {
            $layer_list = $layer_list->toArray();
        }
        $list['layer_list'] = $layer_list;
        if (!empty($vacancy_list)) {
            $vacancy_list = $vacancy_list->toArray();
        }
        $list['vacancy_list'] = $vacancy_list;
        return $list;
    }


    /**
     * 编辑电表
     * @param array $data
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/9 17:50
     */
    public function editMeterElectric($data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $service_electric_group = new HouseMeterElectricGroup();
        $group_info = $service_electric_group->getOne($data['group_id']);
        if (!$group_info) {
            throw new \think\Exception("该分组不存在！");

        }

        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_info = $house_meter_electric->getInfo(['id' => $data['electric_id']]);
        if (!$electric_info) {
            throw new \think\Exception("该电表不存在！");
        }
        $vacancy_info = $house_meter_electric->getInfo(['vacancy_id' => $data['vacancy_id']]);
        if ($vacancy_info && $vacancy_info['id'] != $data['electric_id']) {
            throw new \think\Exception("该房间已添加电表！");
        }

        $house_meter_village = new HouseMeterAdminVillage();
        $village_info = $house_meter_village->getInfo(['village_id' => $data['village_id'], 'id' => $data['admin_id']]);
        if (!$village_info && $user_info['qx'] != 1) {
            throw new \think\Exception("当前用户无权限添加电表到该房间！");
        }

        $service_area = new Area();// 查询市
        $service_street = new AreaStreet();// 查询市
        $house_village = new HouseVillage();
        $village_single = new HouseVillageSingle();
        $village_floor = new HouseVillageFloor();
        $village_layer = new HouseVillageLayer();
        $vacancy = new HouseVillageUserVacancy();

        $province_info = $service_area->getOne(['area_id' => $data['province_id'], 'area_type' => 1, 'is_open' => 1]);
        if (empty($province_info)) {
            $value['failReason'] = '所属省不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }

        $city_info = $service_area->getOne(['area_type' => 2, 'is_open' => 1, 'area_id' => $data['city_id'], 'area_pid' => $province_info['area_id']]);
        if (empty($city_info)) {
            $value['failReason'] = '所属市不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $area_info = $service_area->getOne(['area_id' => $data['area_id'], 'area_type' => 3, 'is_open' => 1, 'area_pid' => $city_info['area_id']]);
        if (empty($area_info)) {
            $value['failReason'] = '所属区/县不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $street_info = $service_street->getOne(['area_type' => 0, 'is_open' => 1, 'area_pid' => $area_info['area_id'], 'area_id' => $data['street_id']]);
        if (empty($street_info)) {
            $value['failReason'] = '所属街道不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }

        $community_info = $service_street->getOne(['area_type' => 1, 'is_open' => 1, 'area_pid' => $street_info['area_id'], 'area_id' => $data['community_id']]);
        if (empty($community_info)) {
            $value['failReason'] = '所属社区不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $village_info = $house_village->getInfo(['village_id' => $data['village_id'], 'status' => 1, 'community_id' => $community_info['area_id']]);
        if (empty($village_info)) {
            $value['failReason'] = '所属小区不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $single_info = $village_single->getOne(['id' => $data['single_id'], 'status' => 1, 'village_id' => $village_info['village_id']]);
        if (empty($single_info)) {
            $value['failReason'] = '所属楼栋不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $floor_info = $village_floor->getOne(['floor_id' => $data['floor_id'], 'status' => 1, 'single_id' => $single_info['id']]);
        if (empty($floor_info)) {
            $value['failReason'] = '所属单元不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $layer_info = $village_layer->getOne(['id' => $data['layer_id'], 'status' => 1, 'floor_id' => $floor_info['floor_id']]);
        if (empty($layer_info)) {
            $value['failReason'] = '所属楼层不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $vacancy_info = $vacancy->getOne(['pigcms_id' => $data['vacancy_id'], 'status' => [1, 2, 3], 'layer_id' => $layer_info['id']]);
        if (empty($vacancy_info)) {
            $value['failReason'] = '所属房间号不存在，请正确填写参数';
            throw new \think\Exception($value['failReason']);
        }
        $village_address = $province_info['area_name'] . $city_info['area_name'] . $area_info['area_name'] . $street_info['area_name'] . $community_info['area_name'] . $village_info['village_name'] . $single_info['single_name'] . $floor_info['floor_name'] . $layer_info['layer_name'] . $vacancy_info['room'];


        if (!empty($data['electric_price_id'])) {
            $data['unit_price'] = 0;
            $data['rate'] = 0;
        }
        if (!empty($data['unit_price'])) {
            $data['electric_price_id'] = 0;
        }

        $electric_data = [
            'province_id' => $data['province_id'],
            'city_id' => $data['city_id'],
            'area_id' => $data['area_id'],
            'street_id' => $data['street_id'],
            'community_id' => $data['community_id'],
            'village_id' => $data['village_id'],
            'single_id' => $data['single_id'],
            'floor_id' => $data['floor_id'],
            'layer_id' => $data['layer_id'],
            'vacancy_id' => $data['vacancy_id'],
            'group_id' => $data['group_id'],
            'measure_id' => $data['measure_id'],
            'electric_name' => $data['electric_name'],
            'admin_uid' => $data['admin_id'],
            'electric_type' => $data['electric_type'],
            'electric_address' => $data['electric_address'],
            'electric_price_id' => $data['electric_price_id'],
            'unit_price' => $data['unit_price'],
            'rate' => $data['rate'],
            'village_address' => $village_address,


        ];
        $electric_info_arr = [
            'province_id' => $electric_info['province_id'],
            'city_id' => $electric_info['city_id'],
            'area_id' => $electric_info['area_id'],
            'street_id' => $electric_info['street_id'],
            'community_id' => $electric_info['community_id'],
            'village_id' => $electric_info['village_id'],
            'single_id' => $electric_info['single_id'],
            'floor_id' => $electric_info['floor_id'],
            'layer_id' => $electric_info['layer_id'],
            'vacancy_id' => $electric_info['vacancy_id'],
            'group_id' => $electric_info['group_id'],
            'measure_id' => $electric_info['measure_id'],
            'electric_name' => $electric_info['electric_name'],
            'admin_uid' => $electric_info['admin_uid'],
            'electric_type' => $electric_info['electric_type'],
            'electric_address' => $electric_info['electric_address'],
            'electric_price_id' => $electric_info['electric_price_id'],
            'unit_price' => $electric_info['unit_price'],
            'rate' => $electric_info['rate'],
            'village_address' => $electric_info['village_address'],
        ];

        if (empty(array_diff($electric_info_arr, $electric_data))) {
            return 1;
        }
        $electric_id = $house_meter_electric->saveOne(['id' => $data['electric_id']], $electric_data);
        if ($electric_id) {
            if ($electric_info['group_id'] != $data['group_id'] || $electric_info['measure_id'] != $data['measure_id'] || $electric_info['electric_address'] != $data['electric_address']) {
                $serviceRedis = new HouseMeterRedisService();
                $bang = $serviceRedis->download_bang($data['electric_id']);
                if (is_numeric($bang)) {
                    $this->editElectricStatus($data['electric_id'], 0);
                }
            }

        }
        return $electric_id;
    }


    /**
     * 修改电表状态
     * @param integer $electric_id 电表id
     * @param integer $status 电表状态
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/9 17:50
     */
    public function editElectricStatus($electric_id, $status)
    {

        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_info = $house_meter_electric->getInfo(['id' => $electric_id]);
        if (!$electric_info) {
            throw new \think\Exception("该电表不存在！");
        }

        $electric_id = $house_meter_electric->saveOne(['id' => $electric_id], ['status' => $status]);
        return $electric_id;
    }


    /**
     * 删除电表
     * @param integer $electric_id 电表id
     * @param integer $admin_id 管理员id
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/8 18:50
     */
    public function deleteMeterElectric($admin_id, $electric_id)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_info = $house_meter_electric->getInfo(['id' => $electric_id]);
        if (!$electric_info) {
            throw new \think\Exception("该电表不存在！");
        }
        $electric_id = $house_meter_electric->deleteInfo(['id' => $electric_id]);
        return $electric_id;
    }


    /**
     * 查询电表分组列表
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/10 10:00
     */
    public function getMeterElectricGroupList($admin_id, $page, $limit)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $where = [];
        /*if ($user_info['qx'] != 1) {
            $where[] = ['admin_uid', '=', $admin_id];
        }*/

        $service_admin_electric = new HouseMeterElectricGroup();
        $count = $service_admin_electric->getCount($where);
        $list = $service_admin_electric->getList($where, 'id,group_name,status');
        if (!$list || $list->isEmpty()) {
            $group_list = [];
        } else {
            $group_list = [];
            foreach ($list as $k => $value) {

                $group_list[$k] = $value;
                $group_list[$k]['status'] = $this->group_status_arr[$value['status']];
            }
        }


        $data = [];
        $data['count'] = $count;
        $data['list'] = $group_list;
        return $data;

    }


    /**
     * 添加分组
     * @param array $data
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/10 10:32
     */
    public function addMeterElectricGroup($data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        /* if (ctype_digit($data['group_address']) == true) {
             $data['tcp_address'] = dechex($data['group_address']);
         } else {
             $data['tcp_address'] = $data['group_address'];
         }*/
        $data['tcp_address'] = $data['group_address'];
        $group_data = [
            'group_name' => $data['group_name'],
            'group_address' => $data['group_address'],
            'tcp_address' => $data['tcp_address'],
            'status' => $data['status'],
            'admin_uid' => $data['admin_id'],
            'add_time' => time(),
        ];
        $service_electric_group = new HouseMeterElectricGroup();
        $group_id = $service_electric_group->insertOne($group_data);
        return $group_id;
    }

    /**
     * 编辑分组
     * @param array $data
     * @return integer
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/10 10:32
     */
    public function editMeterElectricGroup($data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $service_electric_group = new HouseMeterElectricGroup();
        $group_info = $service_electric_group->getInfo(['id' => $data['group_id']]);
        if (!$group_info) {
            throw new \think\Exception("该集中器信息不存在！");
        }
        /*if (ctype_digit($data['group_address']) == true) {
            $data['tcp_address'] = dechex($data['group_address']);
        } else {
            $data['tcp_address'] = $data['group_address'];
        }*/

        $data['tcp_address'] = $data['group_address'];
        $group_data = [
            'group_name' => $data['group_name'],
            'group_address' => $data['group_address'],
            'tcp_address' => $data['tcp_address'],
            'status' => $data['status'],
            'admin_uid' => $data['admin_id'],
        ];

        $group_id = $service_electric_group->saveOne(['id' => $data['group_id']], $group_data);
        return $group_id;
    }

    /**
     * 查询电表设置信息
     * @return array
     * @author:zhubaodi
     * @date_time: 2021/4/10 11:30
     */
    public function getMeterElectricSetInfo($admin_uid)
    {
        $service_electric_group = new HouseMeterReadingSys();
        $set_info = $service_electric_group->getInfo(['id' => 1]);
        if (!empty($set_info)) {
            $now_time = strtotime(date('Y-m-d 00:00:00'));
            //  $meter_reading_date = intval($now_time) + $set_info['meter_reading_date'];
            $meter_reading_date = $set_info['meter_reading_date'];
            $set_info['meter_reading_date'] = date('H:i:s', $meter_reading_date);
            $set_info['meter_reading_date1'] = date('Y-m-d H:i');
            if ($set_info['meter_reading_type'] == 1) {
                $date_1 = explode(' ', date('Y-m-d H:i:s', $meter_reading_date));
                $date_2 = explode('-', $date_1[0]);
                $set_info['dateMouth'] = $date_2[2];
            }

        }
        return $set_info;
    }

    /**
     * 编辑电表设置信息
     * @param array $data
     * @return integer
     * @author:zhubaodi
     * @date_time: 2021/4/10 11:42
     */
    public function editMeterElectricSet($data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
//        if ($data['meter_reading_date']){
//            $date_arr=explode(' ',$data['meter_reading_date']);
//            $date_arr1=explode(':',$date_arr[1]);
//         }
//        $time1 = mktime($date_arr1[0],$date_arr1[1],0,date('m'),$date_arr[0],date('Y'));
//        $date = time()-$time1;
        if (isset($data['meter_reading_type']) && $data['meter_reading_type'] == 1) {
            // 按月来
            /*  $date_arr = trim($data['meter_reading_date']);
              $date_1 = explode(' ',$date_arr);
              $date_arr1 = explode(':',$date_1[1]);
              $time1 = mktime($date_arr1[0],$date_arr1[1],0,date('m'),$date_1[0],date('Y'));
              $now_time = mktime(0,0,0,date('m'),1,date('Y'));*/
            //  $date = $time1 - $now_time;


            $date_arr = trim($data['meter_reading_date']);
            $dateMouth = trim($data['dateMouth']);
            $date_arr1 = explode(':', $date_arr);
            $time1 = mktime($date_arr1[0], $date_arr1[1], 0, date('m'), $dateMouth, date('Y'));
            $date = $time1;

        } else {
            $date_arr = trim($data['meter_reading_date']);
            $time1 = date('Y-m-d') . ' ' . $date_arr;
            //  $date = strtotime($time1) - strtotime(date('Y-m-d 00:00:00'));
            $date = strtotime($time1);
        }
        $service_electric_sys = new HouseMeterReadingSys();
        $set_info = $service_electric_sys->getInfo(['id' => 1]);
        //  print_r($set_info);exit;
        $set_data = [
            'electric_set' => $data['electric_set'],
            'price_electric_set' => $data['price_electric_set'],
            'meter_reading_type' => $data['meter_reading_type'],
            'meter_reading_date' => $date,
            'admin_uid' => $data['admin_id'],
        ];

        if (!empty($set_info)) {
            if ($set_info['electric_set'] == $set_data['electric_set'] && $set_info['price_electric_set'] == $set_data['price_electric_set'] && $set_info['meter_reading_type'] == $set_data['meter_reading_type'] && $set_info['admin_uid'] == $set_data['admin_uid'] && $set_info['meter_reading_date'] == $set_data['meter_reading_date']) {
                throw new \think\Exception("请先修改数据再上传！");
            }
            $set_id = $service_electric_sys->saveOne(['id' => $set_info['id']], $set_data);
        } else {
            $set_data['add_time'] = time();
            //  print_r($set_data);exit;
            $set_id = $service_electric_sys->insertOne($set_data);
        }


        return $set_id;

    }


    /**
     * 查询城市列表
     * @author:zhubaodi
     * @date_time: 2021/4/10 13:27
     */

    public function getAreaList($page, $limit)
    {
        $service_area = new Area();// 查询市
        ;
        $count = $service_area->getCount(['is_open' => 1, 'area_type' => 2]);
        $list = $service_area->getLists(['is_open' => 1, 'area_type' => 2], 'area_id,area_name', $page, $limit);

        $data = [];
        $data['count'] = $count;
        $data['list'] = $list;


        return $data;
    }


    /**
     * 查询省市区列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getAreasList($type, $pid)
    {
        $service_area = new Area();// 查询市
        if (!empty($pid) && isset($pid)) {
            $where[] = ['area_pid', '=', $pid];
        }
        $where[] = ['area_type', '=', $type];
        $where[] = ['is_open', '=', 1];
        $list = $service_area->getList($where, 'area_id,area_id as id,area_name as name,area_name,area_pid');

        return $list;
    }

    /**
     * 查询街道、社区列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getCommunityList($type, $pid)
    {
        $service_area = new AreaStreet();// 查询市
        $where = [];
        if (!empty($pid) && isset($pid)) {
            $where[] = ['area_pid', '=', $pid];
        }
        $where[] = ['area_type', '=', $type];
        $where[] = ['is_open', '=', 1];
        $list = $service_area->getLists($where, 'area_id,area_id as id,area_name as name,area_name,area_pid', 0);

        return $list;
    }


    /**
     * 查询小区列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getVillageList($pid)
    {
        $house_village = new HouseVillage();
        if (!empty($pid) && isset($pid)) {
            $where[] = ['community_id', '=', $pid];
        }
        $where[] = ['status', '=', 1];
        $list = $house_village->getList($where, 'village_id as id,village_name as name,village_id,village_name,community_id');

        return $list;
    }


    /**
     * 查询楼栋列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     * @param integer $pid
     * @param array $param
     * $single_type unitRental 公租楼栋 normal正常楼栋 不传是所有楼栋
     * @return array
     */
    public function getSingleList($pid,$param=[],$single_type='')
    {
        $where = [];
        if (isset($param['charge_type'])&&$param['charge_type']&&in_array($param['charge_type'],['water','electric','gas','new_nuanqifei'])) {
            $service_house_new_charge_rule = new HouseNewChargeRuleService();
            if ($pid) {
                $param['village_id'] = $pid;
            }
            $param['is_del']=1;
            $idArr = $service_house_new_charge_rule->getStandardIds($param,'single_id');
            if (empty($idArr)) {
                $idArr = [];
            }
            $where[] = ['id', 'in', $idArr];
        }
        if (!empty($pid) && isset($pid)) {
            $where[] = ['village_id', '=', $pid];
        }
        $where[] = ['status', '=', 1];
        if(isset($param['is_public_rental']) && $param['is_public_rental']){
            $where[] = ['is_public_rental', '=', 1];
        }else{
            if($single_type=='unitRental'){
                $where[] = ['is_public_rental', '=', 1];
            }else if($single_type=='normal'){
                $where[] = ['is_public_rental', '=', 0];
            }
        }

        // 查询楼栋
        $village_single = new HouseVillageSingle();
        $list = $village_single->getList($where, 'single_name as name,id,single_name,village_id','sort DESC,id desc');
        if (!empty($list) && isset($list[0])) {
            $list = $list->toArray();
            foreach ($list as &$item) {
                $item['id']=intval($item['id']);
                if(preg_match('/[\x7f-\xff]/', $item['single_name'])) {
                    $item['name'] = $item['single_name'];
                }else{
                    $item['name'] = $item['single_name'].'('.((new HouseVillageService())->getHouseAlias('single_name',$item['village_id'])?:'栋').')';
                }
            }
        }
        return $list;
    }

    /**
     * 查询单元列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getFloorList($pid,$param=[])
    {
        $where = [];
        if (isset($param['charge_type'])&&$param['charge_type']&&in_array($param['charge_type'],['water','electric','gas','new_nuanqifei'])) {
            $service_house_new_charge_rule = new HouseNewChargeRuleService();
            if ($pid) {
                $param['single_id'] = $pid;
            }
            $idArr = $service_house_new_charge_rule->getStandardIds($param,'floor_id');
            if (empty($idArr)) {
                $idArr = [];
            }
            $where[] = ['floor_id', 'in', $idArr];
        }
        if (!empty($pid) && isset($pid)) {
            $where[] = ['single_id', '=', $pid];
        }
        $where[] = ['status', '=', 1];
        if(isset($param['is_public_rental']) && $param['is_public_rental']){
            $where[] = ['is_public_rental', '=', 1];
        }
        // 查询单元
        $village_floor = new HouseVillageFloor();
        $list = $village_floor->getList($where, 'floor_name as name,floor_id as id ,floor_id,floor_name,single_id,village_id','sort DESC,floor_id desc');
        if (!empty($list) && isset($list[0])) {
            $list = $list->toArray();
            foreach ($list as &$item) {
                if(preg_match('/[\x7f-\xff]/', $item['floor_name'])) {
                    $item['name'] = $item['floor_name'];
                }else{
                    $item['name'] = $item['floor_name'].'('.((new HouseVillageService())->getHouseAlias('floor_name',$item['village_id'])?:'单元').')';
                }
            }
        }
        return $list;
    }

    /**
     * 查询楼层列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getLayerList($pid,$param=[])
    {
        $where = [];
        if (isset($param['charge_type'])&&$param['charge_type']&&in_array($param['charge_type'],['water','electric','gas','new_nuanqifei'])) {
            $service_house_new_charge_rule = new HouseNewChargeRuleService();
            if ($pid) {
                $param['floor_id'] = $pid;
            }
            $idArr = $service_house_new_charge_rule->getStandardIds($param,'layer_id');
            if (empty($idArr)) {
                $idArr = [];
            }
            $where[] = ['id', 'in', $idArr];
        }
        if (!empty($pid) && isset($pid)) {
            $where[] = ['floor_id', '=', $pid];
        }
        $where[] = ['status', '=', 1];
        if(isset($param['is_public_rental']) && $param['is_public_rental']){
            $where[] = ['is_public_rental', '=', 1];
        }
        // 查询楼层
        $village_layer = new HouseVillageLayer();
        $list = $village_layer->getList($where, 'layer_name as name,id,layer_name,floor_id','sort DESC,id desc');
        if (!empty($list) && isset($list[0])) {
            $list = $list->toArray();
            foreach ($list as &$item) {
                if(preg_match('/[\x7f-\xff]/', $item['layer_name'])) {
                    $item['name'] = $item['layer_name'];
                }else{
                    $item['name'] = $item['layer_name'].'(层)';
                }
            }
        }
        return $list;
    }

    /**
     * 查询楼层列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getLayerSingleList($pid,$floor_id=0)
    {
        if (!empty($pid) && isset($pid)) {
            $where[] = ['single_id', '=', $pid];
        }
        if($floor_id>0){
            $where[] = ['floor_id', '=', $floor_id];
        }
        $where[] = ['status', '=', 1];
        // 查询楼层
        $village_layer = new HouseVillageLayer();
        $village_floor = new HouseVillageFloor();
        $list = $village_layer->getList($where, 'layer_name as name,id,layer_name,floor_id');
        $floorInfo=array();
        foreach ($list as &$value) {
            $where_floor = [];
            $floor_name = '';
            if (isset($floorInfo[$value['floor_id']]) && !empty($floorInfo[$value['floor_id']])) {
                $floor_name = $floorInfo[$value['floor_id']]['floor_name'];
            } else {
                $where_floor[] = ['floor_id', '=', $value['floor_id']];
                // 查询单元名称
                $floorObj = $village_floor->getOne($where_floor, 'floor_name');
                if ($floorObj && !$floorObj->isEmpty()) {
                    $floor = $floorObj->toArray();
                    $floorInfo[$value['floor_id']] = $floor;
                    $floor_name = $floor['floor_name'];
                }
            }
            $value['floor_name']=$floor_name;
        }

        return $list;
    }

    /**
     * 查询房间列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getVacancyList($pid, $param=[])
    {
        $where = [];
        if (isset($param['charge_type'])&&$param['charge_type']&&in_array($param['charge_type'],['water','electric','gas','new_nuanqifei'])) {
            $service_house_new_charge_rule = new HouseNewChargeRuleService();
            if ($pid) {
                $param['layer_id'] = $pid;
            }
            $idArr = $service_house_new_charge_rule->getStandardIds($param,'vacancy_id');
            if (empty($idArr)) {
                $idArr = [];
            }
            $where[] = ['pigcms_id', 'in', $idArr];
        }
        if (!empty($pid) && isset($pid)) {
            $where[] = ['layer_id', '=', $pid];
        }
        $where[] = ['status', 'in', '1,2,3'];
        if(isset($param['is_public_rental']) && $param['is_public_rental']){
            $where[] = ['is_public_rental', '=', 1];
        }
        // 查询房间号
        $vacancy = new HouseVillageUserVacancy();
        $list = $vacancy->getList($where, 'pigcms_id as id ,room as name,pigcms_id,room,layer_id','sort desc, pigcms_id asc',0);
        return $list;
    }


    /**
     * 添加收费标准
     * @param array $data
     * @return array
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/10 13:53
     */
    public function addMeterElectricPrice($data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $service_electric_group = new HouseMeterElectricPrice();
        foreach ($data['price'] as $key => $value) {
            $price_data = [
                'city_id' => $data['city_id'],
                'house_type' => $key,
                'charge_num' => 1,
                'unit_price' => $value['unit_price'],
                'rate' => $value['rate'],
                'add_time' => time(),
            ];
            $priceinfo = $service_electric_group->getInfo(['city_id' => $data['city_id'], 'house_type' => $key]);
            if ($priceinfo) {
                $price_id[] = $service_electric_group->saveOne(['id' => $priceinfo['id']], $price_data);
            } else {
                $price_id[] = $service_electric_group->insertOne($price_data);
            }

        }


        return $price_id;
    }


    /**
     * 编辑收费标准
     * @param array $data
     * @return array
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/10 13:53
     */
    public function editMeterElectricPrice($data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        foreach ($data['type'] as $key => $value) {
            $price_data = [
                'city_id' => $data['city_id'],
                'house_type' => $key,
                'charge_num' => 1,
                'unit_price' => $value['unit_price'],
                'rate' => $value['rate'],
            ];
            $service_electric_group = new HouseMeterElectricPrice();
            $price_id[] = $service_electric_group->saveOne(['id' => $value['id']], $price_data);
        }
        return $price_id;
    }

    /**
     * 查询收费标准
     * @param integer $city_id
     * @date_time: 2021/4/10 14:32
     * @throws \think\Exception
     * @author:zhubaodi
     */
    public function getAreaPriceList($city_id)
    {
        ini_set('memory_limit', '1024M');
        $service_price = new HouseMeterElectricPrice();
        $price_list = $service_price->getList(['city_id' => $city_id], 'city_id,id,house_type,unit_price,rate');
        if (!empty($price_list)) {
            $price_list = $price_list->toArray();
            if (!empty($price_list)) {
                foreach ($price_list as $k => $value) {
                    $price_list[$k] = $value;
                    $price_list[$k]['info'] = $this->house_type_arr[$value['house_type']] . '(单价' . $value['unit_price'] . ';倍率' . $value['rate'];
                }
            } else {
                $price_list = ['暂无数据'];
            }

        } else {
            $price_list = ['暂无数据'];
        }
        return $price_list;
    }


    /**
     * 获取账单列表
     * @param array $data
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:59
     */
    public function getPayorderList($data)
    {

        // 初始化 数据层
        $service_electric = new HouseMeterAdminElectric();
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $where = [];
        if (isset($data['phone']) && !empty($data['phone'])) {
            $where[] = ['a.phone', '=', $data['phone']];
        }
        if (isset($data['electric_name']) && !empty($data['electric_name'])) {
            $data_where = [['electric_name', 'like', '%' . $data['electric_name'] . '%']];
            $electric_list = $service_electric->getListAll($data_where, 'id,electric_name');
            if (!empty($electric_list)) {
                $electric_list = $electric_list->toArray();
                if (!empty($electric_list)) {
                    $electric_id = array_column($electric_list, 'id');
                    $where[] = ['a.electric_id', 'in', $electric_id];
                }
            }
        }
        if (isset($data['payment_num']) && !empty($data['payment_num'])) {
            $where[] = ['a.payment_num', '=', $data['payment_num']];
        }
        if ($data['payment_type']) {
            $where[] = ['a.payment_type', '=', $data['payment_type'] - 1];
        }
        if ($data['pay_type']) {
            $where[] = ['a.pay_type', '=', $data['pay_type']];
        }
        if (isset($data['begin_time']) && !empty($data['begin_time'])) {
            $where[] = ['a.pay_time', '>=', strtotime($data['begin_time'] . " 00:00:00")];

        }
        if (isset($data['end_time']) && !empty($data['end_time'])) {
            $where[] = ['a.pay_time', '<=', strtotime($data['end_time'] . " 23:59:59")];
        }

        if (isset($data['nickname']) && !empty($data['nickname'])) {
            /* $service_admin_electric = new User();
             $uid = $service_admin_electric->getOne(['nickname' => $data['nickname']], 'uid');*/
            $where[] = ['c.nickname', 'like', '%' . $data['nickname'] . '%'];
        }

        if (isset($data['province_id']) && !empty($data['province_id'])) {
            $where[] = ['a.province_id', '=', $data['province_id']];
        }
        if (isset($data['city_id']) && !empty($data['city_id'])) {
            $where[] = ['a.city_id', '=', $data['city_id']];
        }
        if (isset($data['area_id']) && !empty($data['area_id'])) {
            $where[] = ['a.area_id', '=', $data['area_id']];
        }
        if (isset($data['street_id']) && !empty($data['street_id'])) {
            $where[] = ['a.street_id', '=', $data['street_id']];
        }
        if (isset($data['community_id']) && !empty($data['community_id'])) {
            $where[] = ['a.community_id', '=', $data['community_id']];
        }
        if (isset($data['village_id']) && !empty($data['village_id'])) {
            $where[] = ['a.village_id', '=', $data['village_id']];
        }
        if (isset($data['single_id']) && !empty($data['single_id'])) {
            $where[] = ['a.single_id', '=', $data['single_id']];
        }
        if (isset($data['floor_id']) && !empty($data['floor_id'])) {
            $where[] = ['a.floor_id', '=', $data['floor_id']];
        }
        if (isset($data['layer_id']) && !empty($data['layer_id'])) {
            $where[] = ['a.layer_id', '=', $data['layer_id']];
        }
        if (isset($data['vacancy_id']) && !empty($data['vacancy_id'])) {
            $where[] = ['a.vacancy_id', '=', $data['vacancy_id']];
        }

        if ($user_info['qx'] != 1) {
            $where[] = ['b.admin_uid', '=', $data['admin_id']];
        }
        $where[] = ['a.status', '=', 2];
        $service_admin_electric = new HouseMeterUserPayorder();
        $count = $service_admin_electric->getCount($where);
        $sum_list = $service_admin_electric->getList($where, 'DISTINCT a.id,a.charge_price');
        $sum = 0.00;
        if (!empty($sum_list)) {
            $sum_list = $sum_list->toArray();
            if (!empty($sum_list)) {
                $sum_arr = array_column($sum_list, 'charge_price');
                $sum = array_sum($sum_arr);
                if (empty(strstr($sum, '.'))) {
                    $sum = $sum . '.00';
                } else {
                    $sum = getFormatNumber($sum);
                }
            }
        }

        $list = $service_admin_electric->getList($where, 'DISTINCT a.id,a.order_no,a.payment_num,a.payment_type,a.current_system_balance,a.charge_price,a.pay_type,a.pay_time,a.uid,c.nickname,a.phone,a.charge_num,a.paid_orderNo,a.electric_id', $data['page'], $data['limit'], 'id DESC', 1);


        if (!$list || $list->isEmpty()) {
            $order_list = [];
        } else {
            $order_list = [];

            foreach ($list as $k => $value) {
                $order_list[$k] = $value;
                $electricinfo = $service_electric->getOne($value['electric_id'], 'id,electric_name,village_address');
                $order_list[$k]['electric_name'] = $electricinfo['electric_name'];
                $order_list[$k]['village_address'] = $electricinfo['village_address'];
                $order_list[$k]['payment_num'] = $this->payment_num_arr[$value['payment_num']];
                $order_list[$k]['payment_type'] = $this->payment_type_arr[$value['payment_type']];
                if (!empty($value['pay_type'])) {
                    $order_list[$k]['pay_type'] = $this->pay_type_arr[$value['pay_type']];
                }
                $order_list[$k]['pay_time'] = date('Y-m-d H:i:s', $value['pay_time']);
            }
        }
        $data = [];
        $data['count'] = $count;
        $data['list'] = $order_list;
        $data['sum'] = $sum;

        return $data;

    }

    /**
     *查询小区详情
     * @param int $admin_id 总管理员id
     * @param int $id 小区id
     * @author:zhubaodi
     * @date_time: 2021/4/14 16:00
     */
    public function getVillageInfo($admin_id, $id)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $service_admin_electric = new HouseVillage();
        $village_info = $service_admin_electric->getOne($id);
        return $village_info;
    }


    /**
     *查询小区详情
     * @param int $admin_id 总管理员id
     * @param int $group_id 分组id
     * @author:zhubaodi
     * @date_time: 2021/4/14 16:00
     */
    public function getMeasureList($admin_id, $group_id, $page = 1)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $admin_id]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }
        $service_admin_electric = new HouseMeterAdminElectric();
        $measure_list = $service_admin_electric->getList(['group_id' => $group_id], 'measure_id');
        $measure_id = [];

        for ($i = 1, $j = 1; $i < 2049; $i++, $j++) {
            $measure_id[$j] = $i;
        }
        $measure_arr = [];
        $result = $measure_id;
        if ($measure_list) {
            $measure_list = $measure_list->toArray();
            if (!empty($measure_list)) {
                foreach ($measure_list as $k => $value) {
                    $measure[$k] = $value['measure_id'];
                }
                $result = array_diff($measure_id, $measure);
            }
            if ($result) {
                foreach ($result as $k => $value) {
                    $measure1['key'] = $k;
                    $measure1['value'] = $value;
                    $measure_arr[] = $measure1;
                }
            }

        }
        /* for ($i = 1, $j = 0; $i < 2049; $i++, $j++) {
             $measure_id[$j]['key'] = $i;
             $measure_id[$j]['value'] = $i;
         }
         if ($measure_list) {
             foreach ($measure_id as $k => $vv) {
                 foreach ($measure_list as $value) {
                     if ($vv['key'] == $value['measure_id']) {
                         unset($measure_id[$k]);
                     }
                 }
             }

         }
         print_r($measure_id);exit;*/
        return $measure_arr;
    }


    /**
     * 获取实时电量列表
     * @param integer $uid
     * @param integer $electric_id 电表id
     * @param string $time
     * @author:zhubaodi
     * @date_time: 2021/4/10 18:09
     */
    public function getMeterReadingList($time, $electric_id, $page, $limit)
    {

        if (!empty($time)) {
            $time[0] = date('Y-m-d 00:00:00', strtotime($time[0]));
            $time[1] = date('Y-m-d 23:59:59', strtotime($time[1]));
            $where[] = ['add_time', '>=', strtotime($time[0])];
            $where[] = ['add_time', '<=', strtotime($time[1])];
        }


        $where[] = ['electric_id', '=', $electric_id];
        $group = "FROM_UNIXTIME(add_time, '%Y-%m-%d')";

        $service_admin_electric = new HouseMeterElectricRealtime();
        $count = $service_admin_electric->getCount($where, $group);
        $list = $service_admin_electric->getLists($where, 'FROM_UNIXTIME(add_time, "%Y-%m-%d") as time,FROM_UNIXTIME(add_time, "%m-%d") as datetime,end_num-begin_num as num,end_num,begin_num', $group, 'add_time DESC', $page, $limit);
        $arr = [];

        if (!$list || $list->isEmpty()) {
            $order_list = [];
        } else {
            $list = $list->toArray();
            $order_arr = [];
            $order_list = [];
            $arr['electric_count'] = 0;
            foreach ($list as $k => $value) {

                $order_list = [
                    'time' => $value['time'],
                    'num' => $value['num'],
                    'begin_num' => $value['begin_num'],
                    'end_num' => $value['end_num'],
                    'id' => $k,
                ];

                $arr['electric_count'] += $value['num'];
                $arr['electric_count'] = round($arr['electric_count'], 2);
                $order_arr[] = $order_list;
            }

            $len = count($order_arr) - 1;
            $arr['begin_num'] = $order_arr[0]['end_num'];
            $arr['list'] = array_reverse($order_arr);
            $arr['count'] = $count;
            $arr['total_limit'] = $limit;

        }
        return $arr;
    }

    /**
     * 获取抄表数据
     * @author:zhubaodi
     * @date_time: 2021/4/21 13:50
     */
    public function meter_reading()
    {
        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_sys = new HouseMeterReadingSys();
        $electric_realtime = new HouseMeterElectricRealtime();
        $service_vacancy = new HouseVillageUserVacancy();
        $service_price = new HouseMeterElectricPrice();
        $service_order = new HouseMeterUserPayorder();
        $redis = new HouseMeterRedisService();
        $templateNewsService = new TemplateNewsService();

        $time = strtotime(date('Y-m-d 00:00:00'));
        $where = [];
        $where[] = ['status', '=', '0'];
        $where[] = ['reading_time', '<', $time];


        $electric_list = $house_meter_electric->getListAll($where, true, 0, 100);
        if (!empty($electric_list)) {
            $electric_list = $electric_list->toArray();
            $sys = $electric_sys->getInfo(['id' => 1]);
            foreach ($electric_list as $value) {
                $uid = $service_vacancy->getOne(['pigcms_id' => $value['vacancy_id']]);
                if ($sys) {
                    if ($sys) {
                        if (empty($value['electric_price_id'])) {
                            $electric_price = $service_price->getInfo(['city_id' => $value['city_id'], 'house_type' => $uid['house_type']]);
                        } else {
                            $electric_price = $service_price->getInfo(['id' => $value['electric_price_id']]);
                        }
                        $time1 = mktime(0, 0, 0, date('m'), 1, date('Y'));
                        $dateMonth = time() - $time1;
                        if ($sys['meter_reading_type'] == 1 && $dateMonth >= $sys['meter_reading_date']) {
                            $begin_time = date('Y-m-01', time());
                            $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                            $end_time = date('Y-m-d', strtotime("$begin_time +1 month -1 day"));
                            $where1[] = ['reading_time', '<=', strtotime($end_time)];
                            $where1[] = ['electric_id', '=', $value['id']];
                            $reading_list = $electric_realtime->getList($where1);
                            $reading_list = $reading_list->toArray();
                            $len = count($reading_list);
                            if (!empty($reading_list) && $len > 0) {
                                $reading_num = $reading_list[0]['end_num'] - $reading_list[$len - 1]['begin_num'];
                                $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                                $order_arr = [
                                    'uid' => $uid['uid'],
                                    'phone' => $uid['phone'],
                                    'province_id' => $value['province_id'],
                                    'city_id' => $value['city_id'],
                                    'area_id' => $value['area_id'],
                                    'street_id' => $value['street_id'],
                                    'community_id' => $value['community_id'],
                                    'village_id' => $value['village_id'],
                                    'single_id' => $value['single_id'],
                                    'floor_id' => $value['floor_id'],
                                    'layer_id' => $value['layer_id'],
                                    'vacancy_id' => $value['vacancy_id'],
                                    'meter_reading_type' => $sys['meter_reading_type'],
                                    'payment_num' => 0,
                                    'payment_type' => 1,
                                    'begin_num' => $reading_list[$len - 1]['begin_num'],
                                    'end_num' => $reading_list[0]['end_num'],
                                    'unit_price' => $electric_price['unit_price'],
                                    'rate' => $electric_price['rate'],
                                    'add_time' => time(),
                                    'pay_time' => time(),
                                    'electric_id' => $value['id'],
                                    'order_no' => build_real_orderid($uid['uid']),
                                    'charge_num' => $reading_num,
                                    'pay_type' => 'balance',
                                    'charge_price' => $charge_price,
                                    'status' => 2,
                                ];
                                $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                                $service_order->insertOne($order_arr);
                                $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => time()]);
                                $user = new User();
                                $openid = $user->getOne(['uid' => $uid['uid']]);
                                if (!empty($openid)) {
                                    $href1 = get_base_url('pages/houseMeter/index/billList?electric_id=' . $value['id']);
                                    $datamsg1 = [
                                        'tempKey' => 'TM01008',
                                        'dataArr' => [
                                            'href' => $href1,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '电表抄表扣费成功',
                                            'keyword1' => '电费',
                                            'keyword2' => $value['village_address'],
                                            'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $charge_price,

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $templateNewsService->sendTempMsg($datamsg1['tempKey'], $datamsg1['dataArr']);
                                }

                                if ($remaining_capacity < $sys['electric_set'] || $remaining_capacity < $sys['price_electric_set']) {
                                    if ($value['disabled'] != true) {
                                        $this->download_switch($value['id'], 'close');
                                    }
                                }
                                //剩余电量小于断闸可手动开闸且大于断闸需交费
                                if ($remaining_capacity < $sys['electric_set'] && $remaining_capacity > $sys['price_electric_set']) {
                                    if (!empty($openid)) {
                                        $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                        $templateNewsService = new TemplateNewsService();
                                        $datamsg = [
                                            'tempKey' => 'OPENTM400166399',
                                            'dataArr' => [
                                                'href' => $href,
                                                'wecha_id' => $openid['openid'],
                                                'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['electric_set'] . '，电表已断闸，可重新开闸，请及时缴费！',
                                                'keyword1' => '电表状态提醒',
                                                'keyword2' => '已发送',
                                                'keyword3' => date('H:i'),
                                                'remark' => '请点击查看详细信息！',

                                            ]
                                        ];
                                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                        $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                        fdump_api($restem, 'uploadtemp');
                                    }
                                    $sms_data = array('type' => 'meter');
                                    $sms_data['uid'] = $uid['uid'];
                                    $sms_data['mobile'] = $openid['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，可重新开闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['electric_set']));
                                    (new SmsService())->sendSms($sms_data);

                                } //剩余电量小于断闸需交费
                                if ($remaining_capacity <= $sys['price_electric_set']) {
                                    if (!empty($openid)) {
                                        $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                        $templateNewsService = new TemplateNewsService();
                                        $datamsg = [
                                            'tempKey' => 'OPENTM400166399',
                                            'dataArr' => [
                                                'href' => $href,
                                                'wecha_id' => $openid['openid'],
                                                'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['price_electric_set'] . '，电表已断闸，请及时缴费！',
                                                'keyword1' => '电表状态提醒',
                                                'keyword2' => '已发送',
                                                'keyword3' => date('H:i'),
                                                'remark' => '请点击查看详细信息！',

                                            ]
                                        ];
                                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);

                                        $sms_data = array('type' => 'meter');
                                        $sms_data['uid'] = $uid['uid'];
                                        $sms_data['mobile'] = $openid['phone'];
                                        $sms_data['sendto'] = 'user';
                                        $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['price_electric_set']));
                                        (new SmsService())->sendSms($sms_data);
                                    }

                                }
                            }
                        }
                        $time2 = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                        $dateDay = time() - $time2;
                        if ($sys['meter_reading_type'] == 2 && $dateDay >= $sys['meter_reading_date']) {
                            $realtime_last_info = $electric_realtime->where(['electric_id' => $value['id']])->order('id DESC')->find();
                            if (empty($realtime_last_info)) {
                                $realtime_last_info['end_num'] = 0;
                            }
                            if (!empty($realtime_last_info)) {
                                $reading_num = $realtime_last_info['begin_num'] - $realtime_last_info['end_num'];
                                $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                                $order_arr = [
                                    'uid' => $uid['uid'],
                                    'phone' => $uid['phone'],
                                    'province_id' => $value['province_id'],
                                    'city_id' => $value['city_id'],
                                    'area_id' => $value['area_id'],
                                    'street_id' => $value['street_id'],
                                    'community_id' => $value['village_id'],
                                    'single_id' => $value['single_id'],
                                    'floor_id' => $value['floor_id'],
                                    'layer_id' => $value['layer_id'],
                                    'vacancy_id' => $value['vacancy_id'],
                                    'meter_reading_type' => $sys['meter_reading_type'],
                                    'payment_num' => 0,
                                    'payment_type' => 1,
                                    'begin_num' => $realtime_last_info['begin_num'],
                                    'end_num' => $realtime_last_info['end_num'],
                                    'unit_price' => $electric_price['unit_price'],
                                    'rate' => $electric_price['rate'],
                                    'add_time' => time(),
                                    'pay_time' => time(),
                                    'electric_id' => $value['id'],
                                    'order_no' => rand(1000, 9999) . time(),
                                    'charge_num' => $reading_num,
                                    'pay_type' => 'balance',
                                    'charge_price' => $charge_price,
                                    'status' => 2,
                                ];
                                $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                                $service_order->insertOne($order_arr);
                                $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => time()]);
                                $user = new User();
                                $openid = $user->getOne(['uid' => $uid['uid']]);
                                if (!empty($openid)) {
                                    $href1 = get_base_url('pages/houseMeter/index/billList?electric_id=' . $value['id']);
                                    $datamsg1 = [
                                        'tempKey' => 'TM01008',
                                        'dataArr' => [
                                            'href' => $href1,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '电表抄表扣费成功',
                                            'keyword1' => '电费',
                                            'keyword2' => $value['village_address'],
                                            'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $charge_price,

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $resqq = $templateNewsService->sendTempMsg($datamsg1['tempKey'], $datamsg1['dataArr']);
                                    fdump_api($resqq, 'uploadtempjf');
                                }

                                if ($remaining_capacity < $sys['electric_set'] || $remaining_capacity < $sys['price_electric_set']) {
                                    if ($value['disabled'] != true) {
                                        $this->download_switch($value['id'], 'close');
                                    }

                                }

                                if ($remaining_capacity < $sys['electric_set'] && $remaining_capacity > $sys['price_electric_set']) {
                                    if (!empty($openid)) {
                                        $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                        $templateNewsService = new TemplateNewsService();
                                        $datamsg = [
                                            'tempKey' => 'OPENTM400166399',
                                            'dataArr' => [
                                                'href' => $href,
                                                'wecha_id' => $openid['openid'],
                                                'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['electric_set'] . '，电表已断闸，可重新开闸，请及时缴费！',
                                                'keyword1' => '电表状态提醒',
                                                'keyword2' => '已发送',
                                                'keyword3' => date('H:i'),
                                                'remark' => '请点击查看详细信息！',

                                            ]
                                        ];
                                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                        $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                        fdump_api($restem, 'uploadtempday');

                                        $sms_data = array('type' => 'meter');
                                        $sms_data['uid'] = $uid['uid'];
                                        $sms_data['mobile'] = $openid['phone'];
                                        $sms_data['sendto'] = 'user';
                                        $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，可重新开闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['electric_set']));
                                        (new SmsService())->sendSms($sms_data);

                                    }

                                }
                                if ($remaining_capacity <= $sys['price_electric_set']) {
                                    if (!empty($openid)) {
                                        $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                        $templateNewsService = new TemplateNewsService();
                                        $datamsg = [
                                            'tempKey' => 'OPENTM400166399',
                                            'dataArr' => [
                                                'href' => $href,
                                                'wecha_id' => $openid['openid'],
                                                'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['price_electric_set'] . '，电表已断闸，请及时缴费！',
                                                'keyword1' => '电表状态提醒',
                                                'keyword2' => '已发送',
                                                'keyword3' => date('H:i'),
                                                'remark' => '请点击查看详细信息！',

                                            ]
                                        ];
                                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                        $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                        fdump_api($restem, 'uploadtempday1');
                                        $uid['phone'] = '15055152821';
                                        $sms_data = array('type' => 'meter');
                                        $sms_data['uid'] = $uid['uid'];
                                        $sms_data['mobile'] = $openid['phone'];
                                        $sms_data['sendto'] = 'user';
                                        $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['price_electric_set']));
                                        (new SmsService())->sendSms($sms_data);
                                    }


                                }

                            }
                        }
                    }
                    /* $house_type = $service_vacancy->getOne(['pigcms_id' => $value['vacancy_id'], 'status' => 1]);

                     if (empty($value['electric_price_id'])) {
                         $electric_price = $service_price->getInfo(['city_id' => $value['city_id'], 'house_type' => $house_type['house_type']]);
                     } else {
                         $electric_price = $service_price->getInfo(['id' => $value['electric_price_id']]);
                     }
                     if ($sys['meter_reading_type'] == 1) {
                         $begin_time = date('Y-m-01', time());
                         $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                         $end_time = date('Y-m-d', strtotime("$begin_time +1 month -1 day"));
                         $where1[] = ['reading_time', '<=', strtotime($end_time)];
                         $where1[] = ['electric_id', '=', $value['id']];
                         $reading_list = $electric_realtime->getList($where1);
                         $reading_list = $reading_list->toArray();
                         $len = count($reading_list);
                         if (!empty($reading_list) && $len > 0) {
                             $reading_num = $reading_list[0]['end_num'] - $reading_list[$len - 1]['begin_num'];
                             $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                             $order_arr = [
                                 'uid' => $house_type['uid'],
                                 'phone' => $house_type['phone'],
                                 'province_id' => $value['province_id'],
                                 'city_id' => $value['city_id'],
                                 'area_id' => $value['area_id'],
                                 'street_id' => $value['street_id'],
                                 'community_id' => $value['community_id'],
                                 'village_id' => $value['village_id'],
                                 'single_id' => $value['single_id'],
                                 'floor_id' => $value['floor_id'],
                                 'layer_id' => $value['layer_id'],
                                 'vacancy_id' => $value['vacancy_id'],
                                 'meter_reading_type' => $sys['meter_reading_type'],
                                 'payment_num' => 0,
                                 'payment_type' => 1,
                                 'begin_num' => $reading_list[$len - 1]['begin_num'],
                                 'end_num' => $reading_list[0]['end_num'],
                                 'unit_price' => $electric_price['unit_price'],
                                 'rate' => $electric_price['rate'],
                                 'add_time' => time(),
                                 'electric_id' => $value['id'],
                                 'order_no' => rand(1000, 9999) . time(),
                                 'charge_num' => $reading_num,
                                 'pay_type' => 'balance',
                                 'charge_price' => $charge_price,
                             ];
                             $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                             $order_id = $service_order->insertOne($order_arr);

                             /* if ($remaining_capacity<$sys['electric_set']||$remaining_capacity<$sys['price_electric_set']){
                                  $redis->download_switch($value['id'],'close');
                              }*/
                    /*if ($remaining_capacity < $sys['price_electric_set']) {
                        $electric_charge_num = $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'disabled' => 'false']);
                    } else {
                        $electric_charge_num = $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity]);
                    }
                }
            } else {
                $data['where'] = ['electric_id' => $value['id']];
                $data['order'] = 'id DESC';
                $reading_list = $electric_realtime->find($data);
                if (!empty($reading_list)) {
                    $reading_num = $reading_list['end_num'] - $reading_list['begin_num'];
                    $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                    $order_arr = [
                        'uid' => $house_type['uid'],
                        'phone' => $house_type['phone'],
                        'province_id' => $value['province_id'],
                        'city_id' => $value['city_id'],
                        'area_id' => $value['area_id'],
                        'street_id' => $value['street_id'],
                        'community_id' => $value['community_id'],
                        'village_id' => $value['village_id'],
                        'single_id' => $value['single_id'],
                        'floor_id' => $value['floor_id'],
                        'layer_id' => $value['layer_id'],
                        'vacancy_id' => $value['vacancy_id'],
                        'meter_reading_type' => $sys['meter_reading_type'],
                        'payment_num' => 0,
                        'payment_type' => 1,
                        'begin_num' => $reading_list['begin_num'],
                        'end_num' => $reading_list['end_num'],
                        'unit_price' => $electric_price['unit_price'],
                        'rate' => $electric_price['rate'],
                        'add_time' => time(),
                        'electric_id' => $value['id'],
                        'order_no' => rand(1000, 9999) . time(),
                        'charge_num' => $reading_num,
                        'pay_type' => 'balance',
                        'charge_price' => $charge_price,
                    ];
                    $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                    $order_id = $service_order->insertOne($order_arr);
                    /* if ($remaining_capacity<$sys['electric_set']||$remaining_capacity<$sys['price_electric_set']){
                         $redis->download_switch($value['id'],'close');
                     }*/
                    /*if ($remaining_capacity < $sys['price_electric_set']) {
                        $electric_charge_num = $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'update_time' => time(), 'disabled' => 'false']);
                    } else {
                        $electric_charge_num = $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'update_time' => time()]);
                    }

                }*/
                    /* }*/
                }
            }
        }


    }

    /**
     * 根据分组获取数量
     * @param array $where
     * @param string $group
     * @return mixed
     * @author lijie
     * @date_time 2021/05/20
     */
    public function getCountByField($where = [], $group = 'city_id')
    {
        $db_house_meter_admin_electric = new HouseMeterAdminElectric();
        $count = $db_house_meter_admin_electric->getCountByField($where, $group);
        return $count;
    }

    /**
     * 获取电表数量
     * @param array $where
     * @return int
     * @author lijie
     * @date_time 2021/05/20
     */
    public function getEleCount($where = [])
    {
        $db_house_meter_admin_electric = new HouseMeterAdminElectric();
        $count = $db_house_meter_admin_electric->getEleCount($where);
        return $count;
    }

    /**
     * 获取电表告警数量
     * @param array $where
     * @return int
     * @author lijie
     * @date_time 2021/05/20
     */
    public function getWarnCount($where = [])
    {
        $db_house_meter_warn = new HouseMeterWarn();
        $count = $db_house_meter_warn->getCount($where);
        return $count;
    }

    /**
     * 设备告警列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWarnList($where = [], $field = true, $page = 1, $limit = 15, $order = 'id DESC')
    {
        $db_house_meter_warn = new HouseMeterWarn();
        $data = $db_house_meter_warn->getList($where, $field, $page, $limit, $order);
        $list_arr = [];
        if ($data) {
            $arr = [
                ['title' => '告警详情'],
                ['title' => '设备名称'],
                ['title' => '所属小区'],
                ['title' => '时间']
            ];
            foreach ($data as $key => $val) {
                $list_arr[0] = $arr;
                $list_arr[$key + 1] = [
                    ['title' => $val['warn_reason']],
                    ['title' => $val['electric_name']],
                    ['title' => $val['village_name']],
                    ['title' => date('Y-m-d H:i:s', $val['warn_time'])],
                ];
            }
        }
        return $list_arr;
    }

    /**
     *获取所有用电量
     * @param string $sum
     * @param string $group
     * @param string $order
     * @return float|string
     * @author lijie
     * @date_time 2021/05/20
     */
    public function getSumPower($sum = 'end_num - begin_num', $group = '', $order = '')
    {
        $service_house_meter_electric_real_time = new HouseMeterElectricRealtime();
        $sum = $service_house_meter_electric_real_time->getSum($sum, $group, $order);
        return $sum;
    }

    /**
     * 查询各个小区的耗电量
     * @param string $sum
     * @param string $group
     * @param string $order
     * @param int $sumPower
     * @return string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/05/20
     */
    public function getSumPowerGroupByVillage($sum = 'r.end_num - r.begin_num', $group = 'r.village_id', $order = 'sum DESC', $sumPower = 0)
    {
        $service_house_meter_electric_real_time = new HouseMeterElectricRealtime();
        $data = $service_house_meter_electric_real_time->getSum($sum, $group, $order);
        $list_arr = [];
        if ($data) {
            $arr = [
                ['title' => '排名'],
                ['title' => '小区'],
                ['title' => '耗电量（度）'],
                ['title' => '占比']
            ];
            $top_rate = 0;
            $all_sum = 0;
            $top_sum = 0;
            foreach ($data as $key => $val) {
                $all_sum+=$val['sum'];
                if($key<=4){
                    $top_sum+=$val['sum'];
                    $top_rate+=sprintf("%.2f", $val['sum'] / $sumPower * 100);
                }
                $data[$key]['rate'] = sprintf("%.2f", $val['sum'] / $sumPower * 100) . '%';
                $list_arr[0] = $arr;
                $list_arr[$key + 1] = [
                    ['title' => $key + 1],
                    ['title' => $val['village_name']],
                    ['title' => $val['sum']],
                    ['title' => $data[$key]['rate']],
                ];
            }
            if(count($data)>5){
                $data = array_slice($data,0,5);
                $data[5]['village_name'] = '其他小区';
                $data[5]['rate'] = getFormatNumber(100-$top_rate).'%';
                $data[5]['sum'] = getFormatNumber($all_sum-$top_sum);
            }
        }
        $info['list_arr'] = $list_arr;
        $info['data'] = $data;
        return $info;
    }

    /**
     * 根据集中器状态获取对应电表的数量
     * @param array $where
     * @param string $group
     * @return mixed
     * @author lijie
     * @date_time 2021/05/21
     */
    public function getEleStatus($where = [], $group = '')
    {
        $db_admin_electric = new HouseMeterAdminElectric();
        $count = $db_admin_electric->getEleStatus($where, $group);
        return $count;
    }

    /**
     * 获取总耗电费用
     * @param array $where
     * @param bool $field
     * @param string $group
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return string
     * @author lijie
     * @date_time 2021/05/21
     */
    public function getRealtime($where = [], $field = true, $group = '', $order = 'id DESC', $page = 0, $limit = 20)
    {
        $service_house_meter_electric_real_time = new HouseMeterElectricRealtime();
        $data = $service_house_meter_electric_real_time->getLists($where, $field, $group, $order, $page, $limit)->toArray();
        $all_fee = 0;
        $village_list = [];
        $list_arr = [];
        if ($data) {
            foreach ($data as $v) {
                $service_electric = new HouseMeterAdminElectric();
                $electric_info = $service_electric->getOne($v['electric_id']);
                if (empty($electric_info['electric_price_id']) && empty($electric_info['unit_price'])) {
                    $service_vacancy = new HouseVillageUserVacancy();
                    $house_type = $service_vacancy->getOne([['pigcms_id', '=', $electric_info['vacancy_id']], ['status', 'in', '1,2,3']]);
                    $service_price = new HouseMeterElectricPrice();
                    $electric_price = $service_price->getInfo(['city_id' => $electric_info['city_id'], 'house_type' => $house_type['house_type']]);
                } elseif (!empty($electric_info['electric_price_id'])) {
                    $service_price = new HouseMeterElectricPrice();
                    $electric_price = $service_price->getInfo(['id' => $electric_info['electric_price_id']]);
                } else {
                    $electric_price['unit_price'] = $electric_info['unit_price'];
                    $electric_price['rate'] = $electric_info['rate'];
                }
                $all_fee += $v['sum_num'] * $electric_price['unit_price'] * $electric_price['rate'];
                if (empty($village_list)) {
                    $village_list[0]['village_id'] = $v['village_id'];
                    $village_list[0]['village_fee'] = $v['sum_num'] * $electric_price['unit_price'] * $electric_price['rate'];
                } else {
                    foreach ($village_list as $key => $value) {
                        if ($value['village_id'] == $v['village_id']) {
                            $village_list[$key]['village_fee'] = $value['village_fee'] + $v['sum_num'] * $electric_price['unit_price'] * $electric_price['rate'];
                            break;
                        } elseif (count($village_list) == $key + 1) {
                            $village_list[count($village_list)]['village_id'] = $v['village_id'];
                            $village_list[count($village_list)]['village_fee'] = $v['sum_num'] * $electric_price['unit_price'] * $electric_price['rate'];
                        }
                    }
                }
            }
            $db_house_village = new HouseVillage();
            $arr = [
                ['title' => '小区 '],
                ['title' => '用电费用'],
                ['title' => '占比'],
            ];
            $top_rate = 0;
            $top_fee = 0;
            foreach ($village_list as $k => $v) {
                if($k<=4){
                    $top_fee+=$v['village_fee'];
                    $top_rate+=sprintf("%.2f", $v['village_fee'] / $all_fee * 100);
                }
                $village_info = $db_house_village->getOne($v['village_id'], 'village_name');
                $village_list[$k]['village_name'] = $village_info['village_name'];
                $village_list[$k]['village_fee'] = sprintf("%.2f", $v['village_fee']);
                $village_list[$k]['village_fee_rate'] = sprintf("%.2f", $v['village_fee'] / $all_fee * 100) . '%';
                $list_arr[0] = $arr;
                $list_arr[$k + 1] = [
                    ['title' => $village_info['village_name']],
                    ['title' => sprintf("%.2f", $v['village_fee'])],
                    ['title' => sprintf("%.2f", $v['village_fee'] / $all_fee * 100) . '%'],
                ];
            }
            if(count($village_list)>5){
                $village_list = array_slice($village_list,0,5);
                $village_list[5]['village_name'] = '其他小区';
                $village_list[5]['village_id'] = 0;
                $village_list[5]['village_fee_rate'] = getFormatNumber(100-$top_rate).'%';
                $village_list[5]['village_fee'] = getFormatNumber($all_fee-$top_fee);
            }
        }
        $return['all_fee'] = sprintf("%.2f", $all_fee);
        $return['village_list'] = $village_list;
        $return['list_arr'] = $list_arr;
        return $return;
    }


    /**
     * 导入电表
     * @author:zhubaodi
     * @date_time: 2021/5/21 11:55
     */
    public function upload($file, $upload_dir)
    {
        $service = new UploadFileService();
        $savepath = $service->uploadFile($file, $upload_dir);
        $filed = [
            'A' => 'electric_name',
            'B' => 'electric_address',
            'C' => 'group_name',
            'D' => 'measure_id',
            'E' => 'province',
            'F' => 'city',
            'G' => 'area',
            'H' => 'street',
            'I' => 'community',
            'J' => 'village',
            'K' => 'single',
            'L' => 'floor',
            'M' => 'layer',
            'N' => 'vacancy',
            'O' => 'unit_price',
            'P' => 'rate',
            'Q' => 'electric_type',
        ];
        $data = $this->readFile($_SERVER['DOCUMENT_ROOT'] . $savepath, $filed, 'Xlsx');
        $data_print = [];
        $service_area = new Area();// 查询市
        $service_street = new AreaStreet();// 查询市
        $house_village = new HouseVillage();
        $village_single = new HouseVillageSingle();
        $village_floor = new HouseVillageFloor();
        $village_layer = new HouseVillageLayer();
        $house_meter_electric = new HouseMeterAdminElectric();
        $service_electric_group = new HouseMeterElectricGroup();
        $vacancy = new HouseVillageUserVacancy();
        $res = '';
        if (!empty($data)) {
            $data_print = [];
            foreach ($data as $value) {
                fdump_api($value, 'electric_data', 1);
                if (empty($value['electric_name']) && empty($value['electric_address']) && empty($value['group_name']) && empty($value['measure_id']) && empty($value['province']) && empty($value['city']) && empty($value['area']) && empty($value['street']) && empty($value['community']) && empty($value['village']) && empty($value['single']) && empty($value['floor']) && empty($value['layer']) && empty($value['vacancy']) && empty($value['unit_price']) && empty($value['rate'])) {
                    continue;
                }
                if (empty($value['electric_name']) || empty($value['electric_address']) || empty($value['group_name']) || empty($value['measure_id']) || empty($value['province']) || empty($value['city']) || empty($value['area']) || empty($value['street']) || empty($value['community']) || empty($value['village']) || empty($value['single']) || empty($value['floor']) || empty($value['layer']) || empty($value['vacancy'])) {
                    $value['failReason'] = '参数不全，请按照模板完整填写参数';
                    $data_print[] = $value;
                    continue;
                }
                if (positive_integer($value['measure_id']) != true || $value['measure_id'] > 2048) {
                    $value['failReason'] = '测量点错误，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }

                $province_info = $service_area->getOne(['area_name' => $value['province'], 'area_type' => 1, 'is_open' => 1]);
                if (empty($province_info)) {
                    $value['failReason'] = '所属省不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }

                $city_info = $service_area->getOne(['area_type' => 2, 'is_open' => 1, 'area_name' => $value['city'], 'area_pid' => $province_info['area_id']]);
                if (empty($city_info)) {
                    $value['failReason'] = '所属市不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $area_info = $service_area->getOne(['area_name' => $value['area'], 'area_type' => 3, 'is_open' => 1, 'area_pid' => $city_info['area_id']]);
                if (empty($area_info)) {
                    $value['failReason'] = '所属区/县不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $street_info = $service_street->getOne(['area_type' => 0, 'is_open' => 1, 'area_pid' => $area_info['area_id'], 'area_name' => $value['street']]);
                if (empty($street_info)) {
                    $value['failReason'] = '所属街道不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }

                $community_info = $service_street->getOne(['area_type' => 1, 'is_open' => 1, 'area_pid' => $street_info['area_id'], 'area_name' => $value['community']]);
                if (empty($community_info)) {
                    $value['failReason'] = '所属社区不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $village_info = $house_village->getInfo(['village_name' => $value['village'], 'status' => 1, 'community_id' => $community_info['area_id']]);
                if (empty($village_info)) {
                    $value['failReason'] = '所属小区不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $single_info = $village_single->getOne(['single_name' => $value['single'], 'status' => 1, 'village_id' => $village_info['village_id']]);
                if (empty($single_info)) {
                    $value['failReason'] = '所属楼栋不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $floor_info = $village_floor->getOne(['floor_name' => $value['floor'], 'status' => 1, 'single_id' => $single_info['id']]);
                if (empty($floor_info)) {
                    $value['failReason'] = '所属单元不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $layer_info = $village_layer->getOne(['layer_name' => $value['layer'], 'status' => 1, 'floor_id' => $floor_info['floor_id']]);
                if (empty($layer_info)) {
                    $value['failReason'] = '所属楼层不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $vacancy_info = $vacancy->getOne(['room' => $value['vacancy'], 'status' => [1, 2, 3], 'layer_id' => $layer_info['id']]);
                if (empty($vacancy_info)) {
                    $value['failReason'] = '所属房间号不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $group_info = $service_electric_group->getInfo(['group_name' => $value['group_name']]);
                if (empty($group_info)) {
                    $value['failReason'] = '所属集中器名称不存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $measure_id = $house_meter_electric->getInfo(['group_id' => $group_info['id'], 'measure_id' => $value['measure_id']]);

                if (!empty($measure_id)) {
                    $value['failReason'] = '测量点已存在，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }
                $electric_info = $house_meter_electric->getInfo(['electric_address' => $value['electric_address']]);
                if ($electric_info) {
                    if ($electric_info['electric_name'] == $value['electric_name'] && $electric_info['group_id'] == $group_info['id'] && $electric_info['measure_id'] == $value['measure_id'] && $electric_info['vacancy_id'] == $vacancy_info['pigcms_id'] && $electric_info['single_id'] == $single_info['id'] && $electric_info['layer_id'] == $layer_info['id'] && $electric_info['floor_id'] == $floor_info['floor_id'] && $electric_info['village_id'] == $village_info['village_id'] && $electric_info['community_id'] == $community_info['area_id'] && $electric_info['street_id'] == $street_info['area_id'] && $electric_info['area_id'] == $area_info['area_id'] && $electric_info['city_id'] == $city_info['area_id'] && $electric_info['province_id'] == $province_info['area_id']) {
                        continue;
                    } else {
                        $value['failReason'] = '电表地址已存在，请正确填写参数';
                        $data_print[] = $value;
                        continue;
                    }
                }
                $vacancy_info1 = $house_meter_electric->getInfo(['vacancy_id' => $vacancy_info['pigcms_id']]);
                fdump_api($vacancy_info, 'electric_data', 1);
                if ($vacancy_info1 && $vacancy_info1['id'] != $data['electric_id']) {
                    $value['failReason'] = '该房间已添加电表，请正确填写参数';
                    $data_print[] = $value;
                    continue;
                }

                if (!empty($value['unit_price']) && is_numeric($value['unit_price']) == '') {
                    $value['failReason'] = '请正确上传单价';
                    $data_print[] = $value;
                    continue;
                }
                if (!empty($value['rate']) && is_numeric($value['rate']) == '') {
                    $value['failReason'] = '请正确上传倍率';
                    $data_print[] = $value;
                    continue;
                }
                $electric_type = 1;
                if (!empty($value['electric_type'])) {
                    if ($value['electric_type'] == '单相表') {
                        $electric_type = 2;
                    } elseif ($value['electric_type'] == '三相表') {
                        $electric_type = 1;
                    } else {
                        $value['failReason'] = '电表类型不正确';
                        $data_print[] = $value;
                        continue;
                    }

                }
                $electric_data = [
                    'electric_name' => $value['electric_name'],
                    'electric_address' => $value['electric_address'],
                    'group_id' => $group_info['id'],
                    'measure_id' => $value['measure_id'],
                    'province_id' => $province_info['area_id'],
                    'city_id' => $city_info['area_id'],
                    'area_id' => $area_info['area_id'],
                    'street_id' => $street_info['area_id'],
                    'community_id' => $community_info['area_id'],
                    'village_id' => $village_info['village_id'],
                    'single_id' => $single_info['id'],
                    'floor_id' => $floor_info['floor_id'],
                    'layer_id' => $layer_info['id'],
                    'vacancy_id' => $vacancy_info['pigcms_id'],
                    'electric_price_id' => 0,
                    'unit_price' => $value['unit_price'],
                    'rate' => $value['rate'],
                    'electric_type' => $electric_type,
                    'village_address' => $province_info['area_name'] . $city_info['area_name'] . $area_info['area_name'] . $street_info['area_name'] . $community_info['area_name'] . $village_info['village_name'] . $single_info['single_name'] . $floor_info['floor_name'] . $layer_info['layer_name'] . $vacancy_info['room'],
                    'add_time' => time(),
                    'disabled' => 'false',
                    'admin_uid' => 1,

                ];
                fdump_api($electric_data, 'electric_data', 1);
                $electric_id = $house_meter_electric->insertOne($electric_data);
                if ($electric_id) {
                    $serviceRedis = new HouseMeterRedisService();
                    $bang = $serviceRedis->download_bang($electric_id);
                    if (!is_numeric($bang)) {
                        $this->editElectricStatus($electric_id, 1);
                    }
                }
            }
            if (!empty($data_print)) {
                $title = ['电表名称', '电表号', '所属集中器名称', '测量点', '所属省', '所属市', '所属区/县', '所属街道', '所属社区', '所属小区', '所属楼栋', '所属单元', '所属楼层', '所属房间号', '单价', '倍率', '电表类型', '失败原因'];
                $res = $this->saveExcel($title, $data_print, '批量导入电表失败数据列表' . time());
            }

        }
        return $res;

    }


    /**
     * 读取文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 13:21
     */
    public function readFile($file, $filed, $readerType)
    {
        $uploadfile = $file;
        $reader = IOFactory::createReader($readerType); //设置以Excel5格式(Excel97-2003工作簿)
        $PHPExcel = $reader->load($uploadfile); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= $highestColumm; $column++) //列数是以A列开始
            {
                $data[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
            }
        }
        return $data;
    }


    /**
     * 导出文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 14:44
     */
    public function saveExcel($title, $data, $fileName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(20);
        //  $sheet->getStyle('A')->getNumberFormat()->setFormatCode('@');
        //设置单元格内容
        $titCol = 'A';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '1', $value);
            $titCol++;
        }
        $row = 2;
        foreach ($data as $item) {
            $dataCol = 'A';
            foreach ($item as $value) {
                //单元格内容写入
                $sheet->setCellValue($dataCol . $row, $value);
                $dataCol++;
            }
            $row++;
        }
        //保存

        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = $fileName . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }

    /**
     * 下载表格
     */
    public function downloadExportFile($param)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $param)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }
        $filename = $param;

        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }

        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $param;
        $returnArr['error'] = 0;

        return $returnArr;
    }


    /**
     * 导出账单
     * @author:zhubaodi
     * @date_time: 2021/5/27 20:17
     */
    public function getPayorderPrint($data)
    {
        // 初始化 数据层
        $service_admin_user = new HouseMeterAdminUser();
        $user_info = $service_admin_user->getInfo(['id' => $data['admin_id']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $where = [];
        if (isset($data['phone']) && !empty($data['phone'])) {
            $where[] = ['a.phone', '=', $data['phone']];
        }
        if (isset($data['payment_num']) && !empty($data['payment_num'])) {
            $where[] = ['a.payment_num', '=', $data['payment_num']];
        }
        if ($data['payment_type']) {
            $where[] = ['a.payment_type', '=', $data['payment_type'] - 1];
        }
        if ($data['pay_type']) {
            $where[] = ['a.pay_type', '=', $data['pay_type']];
        }
        if (isset($data['begin_time']) && !empty($data['begin_time'])) {
            $where[] = ['a.pay_time', '>=', strtotime($data['begin_time'] . " 00:00:00")];

        }
        if (isset($data['end_time']) && !empty($data['end_time'])) {
            $where[] = ['a.pay_time', '<=', strtotime($data['end_time'] . " 23:59:59")];
        }

        if (isset($data['nickname']) && !empty($data['nickname'])) {
            /* $service_admin_electric = new User();
             $uid = $service_admin_electric->getOne(['nickname' => $data['nickname']], 'uid');*/
            $where[] = ['c.nickname', 'like', '%' . $data['nickname'] . '%'];
        }

        if (isset($data['province_id']) && !empty($data['province_id'])) {
            $where[] = ['a.province_id', '=', $data['province_id']];
        }
        if (isset($data['city_id']) && !empty($data['city_id'])) {
            $where[] = ['a.city_id', '=', $data['city_id']];
        }
        if (isset($data['area_id']) && !empty($data['area_id'])) {
            $where[] = ['a.area_id', '=', $data['area_id']];
        }
        if (isset($data['street_id']) && !empty($data['street_id'])) {
            $where[] = ['a.street_id', '=', $data['street_id']];
        }
        if (isset($data['community_id']) && !empty($data['community_id'])) {
            $where[] = ['a.community_id', '=', $data['community_id']];
        }
        if (isset($data['village_id']) && !empty($data['village_id'])) {
            $where[] = ['a.village_id', '=', $data['village_id']];
        }
        if (isset($data['single_id']) && !empty($data['single_id'])) {
            $where[] = ['a.single_id', '=', $data['single_id']];
        }
        if (isset($data['floor_id']) && !empty($data['floor_id'])) {
            $where[] = ['a.floor_id', '=', $data['floor_id']];
        }
        if (isset($data['layer_id']) && !empty($data['layer_id'])) {
            $where[] = ['a.layer_id', '=', $data['layer_id']];
        }
        if (isset($data['vacancy_id']) && !empty($data['vacancy_id'])) {
            $where[] = ['a.vacancy_id', '=', $data['vacancy_id']];
        }

        if ($user_info['qx'] != 1) {
            $where[] = ['b.admin_uid', '=', $data['admin_id']];
        }
        $where[] = ['a.status', '=', 2];
        $service_admin_electric = new HouseMeterUserPayorder();
        $list = $service_admin_electric->getList($where, 'DISTINCT a.id,a.order_no,a.payment_num,a.current_system_balance,a.payment_type,a.charge_price,a.pay_type,a.pay_time,a.uid,c.nickname,a.phone,a.charge_num,a.paid_orderNo,a.electric_id');

        if (!$list || $list->isEmpty()) {
            $order_list = [];
        } else {
            $list = $list->toArray();
            $order_list = [];
            $service_electric = new HouseMeterAdminElectric();
            foreach ($list as $k => $value) {
                $order_list[$k] = $value;
                $electricinfo = $service_electric->getOne($value['electric_id'], 'id,electric_name,village_address');
                $order_list[$k]['electric_name'] = $electricinfo['electric_name'];
                $order_list[$k]['village_address'] = $electricinfo['village_address'];
                $order_list[$k]['payment_num'] = $this->payment_num_arr[$value['payment_num']];
                $order_list[$k]['payment_type'] = $this->payment_type_arr[$value['payment_type']];
                if (!empty($value['pay_type'])) {
                    $order_list[$k]['pay_type'] = $this->pay_type_arr[$value['pay_type']];
                }
                $order_list[$k]['pay_time'] = date('Y-m-d H:i:s', $value['pay_time']);
            }
        }
        $res = '';
        if (!empty($order_list)) {
            $data_order_arr = [];
            if ($data['payment_type'] == 1) {
                foreach ($order_list as $v) {
                    $data_order = [
                        'order_no' => $v['order_no'] . ' ',
                        'paid_orderNo' => $v['paid_orderNo'],
                        'electric_name' => $v['electric_name'],
                        'payment_num' => $v['payment_num'],
                        'charge_num' => $v['charge_num'],
                        'charge_price' => $v['charge_price'],
                        'pay_type' => $v['pay_type'],
                        'pay_time' => $v['pay_time'],
                        'current_system_balance' => $v['current_system_balance'],
                        'nickname' => $v['nickname'],
                        'phone' => $v['phone'],
                        'village_address' => $v['village_address'],
                    ];
                    $data_order_arr[] = $data_order;
                }

                $title = ['订单编号', '支付单号', '电表名称', '缴费项', '充值电量', '缴费金额', '支付方式', '支付时间','余额支付金额', '用户名', '联系电话', '所属房间'];
                $res = $this->saveExcel($title, $data_order_arr, '预交金订单列表' . time());
            } else {
                foreach ($order_list as $v) {
                    $data_order = [
                        'order_no' => $v['order_no'] . ' ',
                        'electric_name' => $v['electric_name'],
                        'payment_num' => $v['payment_num'],
                        'charge_num' => $v['charge_num'],
                        'charge_price' => $v['charge_price'],
                        'pay_type' => $v['pay_type'],
                        'pay_time' => $v['pay_time'],
                        'nickname' => $v['nickname'],
                        'phone' => $v['phone'],
                        'village_address' => $v['village_address'],
                    ];
                    $data_order_arr[] = $data_order;
                }
                $title = ['订单编号', '电表名称', '缴费项', '使用电量', '扣费金额', '扣费方式', '扣费时间', '用户名', '联系电话', '所属房间'];
                $res = $this->saveExcel($title, $data_order_arr, '抄表扣费订单列表' . time());
            }

        }
        return $res;
    }

    public function getCity($where, $field = true)
    {
        $service_area = new Area();
        $data = $service_area->getOne($where, $field);
        return $data;
    }


    /**
     * 手动扣费
     * @author:zhubaodi
     * @date_time: 2021/4/21 13:50
     */
    public function meter_reading_order($readingtime)
    {
        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_sys = new HouseMeterReadingSys();
        $electric_realtime = new HouseMeterElectricRealtime();
        $service_vacancy = new HouseVillageUserVacancy();
        $service_price = new HouseMeterElectricPrice();
        $service_order = new HouseMeterUserPayorder();
        $templateNewsService = new TemplateNewsService();

        $time = strtotime($readingtime);
        $where = [];
        $where[] = ['status', '=', '0'];
        $where[] = ['reading_time', '<', $time];
        $electric_list = $house_meter_electric->getListAll($where);
        if (!empty($electric_list)) {
            $electric_list = $electric_list->toArray();
            $sys = $electric_sys->getInfo(['id' => 1]);
            foreach ($electric_list as $value) {
                $uid = $service_vacancy->getOne(['pigcms_id' => $value['vacancy_id']]);
                if (empty($value['electric_price_id']) && empty($value['unit_price'])) {
                    $electric_price = $service_price->getInfo(['city_id' => $value['city_id'], 'house_type' => $uid['house_type']]);
                } elseif (!empty($value['electric_price_id'])) {
                    $electric_price = $service_price->getInfo(['id' => $value['electric_price_id']]);
                } else {
                    $electric_price['unit_price'] = $value['unit_price'];
                    $electric_price['rate'] = $value['rate'];
                }
                if ($sys['meter_reading_type'] == 1) {
                    $begin_time = date('Y-m-01', strtotime('-1 month'));
                    $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                    $end_time = date('Y-m-t', strtotime('-1 month'));
                    $where1[] = ['reading_time', '<=', strtotime($end_time)];
                    $where1[] = ['electric_id', '=', $value['id']];
                    $reading_list = $electric_realtime->getList($where1);
                    $reading_list = $reading_list->toArray();
                    $len = count($reading_list);
                    if (!empty($reading_list) && $len > 0) {
                        $reading_num = $reading_list[0]['end_num'] - $reading_list[$len - 1]['begin_num'];
                        $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                        $order_arr = [
                            'uid' => $uid['uid'],
                            'phone' => $uid['phone'],
                            'province_id' => $value['province_id'],
                            'city_id' => $value['city_id'],
                            'area_id' => $value['area_id'],
                            'street_id' => $value['street_id'],
                            'community_id' => $value['community_id'],
                            'village_id' => $value['village_id'],
                            'single_id' => $value['single_id'],
                            'floor_id' => $value['floor_id'],
                            'layer_id' => $value['layer_id'],
                            'vacancy_id' => $value['vacancy_id'],
                            'meter_reading_type' => $sys['meter_reading_type'],
                            'payment_num' => 0,
                            'payment_type' => 1,
                            'begin_num' => $reading_list[$len - 1]['begin_num'],
                            'end_num' => $reading_list[0]['end_num'],
                            'unit_price' => $electric_price['unit_price'],
                            'rate' => $electric_price['rate'],
                            'add_time' => strtotime($readingtime),
                            'pay_time' => strtotime($readingtime),
                            'electric_id' => $value['id'],
                            'order_no' => build_real_orderid($uid['uid']),
                            'charge_num' => $reading_num,
                            'pay_type' => 'meterPay',
                            'charge_price' => $charge_price,
                            'status' => 2,
                        ];
                        $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                        $service_order->insertOne($order_arr);
                        $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => strtotime($readingtime)]);
                    }

                }
                if ($sys['meter_reading_type'] == 2 ) {
                    $begin_time = date('Y-m-d 00:00:00', strtotime($readingtime));
                    $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                    $end_time = date('Y-m-d 23:59:59', strtotime($readingtime));
                    $where1[] = ['reading_time', '<=', strtotime($end_time)];
                    $where1[] = ['electric_id', '=', $value['id']];
                    $reading_list = $electric_realtime->getList($where1);
                    $reading_list = $reading_list->toArray();
                    $len = count($reading_list);

                    if ($len == 1) {
                        $realtime_last_info = $reading_list[0];
                    } elseif ($len > 1) {
                        $realtime_last_info = ['end_num' => $reading_list[$len-1]['end_num'], 'begin_num' => $reading_list[0]['begin_num']];
                    } else {
                        $realtime_last_info = [];
                    }
                    if (empty($realtime_last_info)) {
                        continue;
                    }
                    if (!empty($realtime_last_info)) {
                        $reading_num = $realtime_last_info['end_num'] - $realtime_last_info['begin_num'];
                        $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                        $order_arr = [
                            'uid' => $uid['uid'],
                            'phone' => $uid['phone'],
                            'province_id' => $value['province_id'],
                            'city_id' => $value['city_id'],
                            'area_id' => $value['area_id'],
                            'street_id' => $value['street_id'],
                            'community_id' => $value['village_id'],
                            'single_id' => $value['single_id'],
                            'floor_id' => $value['floor_id'],
                            'layer_id' => $value['layer_id'],
                            'vacancy_id' => $value['vacancy_id'],
                            'meter_reading_type' => $sys['meter_reading_type'],
                            'payment_num' => 0,
                            'payment_type' => 1,
                            'begin_num' => $realtime_last_info['begin_num'],
                            'end_num' => $realtime_last_info['end_num'],
                            'unit_price' => $electric_price['unit_price'],
                            'rate' => $electric_price['rate'],
                            'add_time' => strtotime($readingtime),
                            'pay_time' => strtotime($readingtime),
                            'electric_id' => $value['id'],
                            'order_no' => rand(1000, 9999) . strtotime($readingtime),
                            'charge_num' => $reading_num,
                            'pay_type' => 'meterPay',
                            'charge_price' => $charge_price,
                            'status' => 2,
                        ];
                        $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                        $service_order->insertOne($order_arr);
                        $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => strtotime($readingtime)]);
                    }
                }

            }
        }

        return true;

    }


    /**
     * 获取抄表数据
     * @author:zhubaodi
     * @date_time: 2021/4/21 13:50
     */
    public function meter_reading11()
    {
        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_sys = new HouseMeterReadingSys();
        $electric_realtime = new HouseMeterElectricRealtime();
        $service_vacancy = new HouseVillageUserVacancy();
        $service_price = new HouseMeterElectricPrice();
        $service_order = new HouseMeterUserPayorder();
        $templateNewsService = new TemplateNewsService();

        $time = strtotime(date('Y-m-d 00:00:00'));
        $where = [];
        $where[] = ['status', '=', '0'];
        $where[] = ['reading_time', '<', $time];

        $electric_list = $house_meter_electric->getListAll($where, true, 1, 100);
        if (!empty($electric_list)) {
            $electric_list = $electric_list->toArray();
            $sys = $electric_sys->getInfo(['id' => 1]);
            foreach ($electric_list as $value) {
                $uid = $service_vacancy->getOne(['pigcms_id' => $value['vacancy_id']]);
                if ($sys) {
                    if (empty($value['electric_price_id']) && empty($value['unit_price'])) {
                        $electric_price = $service_price->getInfo(['city_id' => $value['city_id'], 'house_type' => $uid['house_type']]);
                    } elseif (!empty($value['electric_price_id'])) {
                        $electric_price = $service_price->getInfo(['id' => $value['electric_price_id']]);
                    } else {
                        $electric_price['unit_price'] = $value['unit_price'];
                        $electric_price['rate'] = $value['rate'];
                    }
                    $date1 = date('Y-m', $sys['meter_reading_date']);
                    $time1 = date('Y-m-d H:i', time());
                    $time2 = substr_replace($time1, $date1, 0, 7);
                    $dateMonth = strtotime($time2);
                    if ($sys['meter_reading_type'] == 1 && $dateMonth >= $sys['meter_reading_date']) {
                        $where1=[];
                        $begin_time = date('Y-m-01', strtotime('-1 month'));
                        $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                        $end_time = date('Y-m-t', strtotime('-1 month'));
                        $where1[] = ['reading_time', '<=', strtotime($end_time)];
                        $where1[] = ['electric_id', '=', $value['id']];
                        $reading_list = $electric_realtime->getList($where1);
                        $reading_list = $reading_list->toArray();
                        $len = count($reading_list);
                        if (!empty($reading_list) && $len > 0) {
                            $reading_num = $reading_list[0]['end_num'] - $reading_list[$len - 1]['begin_num'];
                            $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                            $order_arr = [
                                'uid' => $uid['uid'],
                                'phone' => $uid['phone'],
                                'province_id' => $value['province_id'],
                                'city_id' => $value['city_id'],
                                'area_id' => $value['area_id'],
                                'street_id' => $value['street_id'],
                                'community_id' => $value['community_id'],
                                'village_id' => $value['village_id'],
                                'single_id' => $value['single_id'],
                                'floor_id' => $value['floor_id'],
                                'layer_id' => $value['layer_id'],
                                'vacancy_id' => $value['vacancy_id'],
                                'meter_reading_type' => $sys['meter_reading_type'],
                                'payment_num' => 0,
                                'payment_type' => 1,
                                'begin_num' => $reading_list[0]['begin_num'],
                                'end_num' => $reading_list[$len - 1]['end_num'],
                                'unit_price' => $electric_price['unit_price'],
                                'rate' => $electric_price['rate'],
                                'add_time' => time(),
                                'pay_time' => time(),
                                'electric_id' => $value['id'],
                                'order_no' => build_real_orderid($uid['uid']),
                                'charge_num' => $reading_num,
                                'pay_type' => 'meterPay',
                                'charge_price' => $charge_price,
                                'status' => 2,
                            ];
                            $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                            $service_order->insertOne($order_arr);
                            $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => time()]);
                            $user = new User();
                            $openid = $user->getOne(['uid' => $uid['uid']]);
                            if (!empty($openid)) {
                                $href1 = get_base_url('pages/houseMeter/index/billList?electric_id=' . $value['id']);
                                $datamsg1 = [
                                    'tempKey' => 'TM01008',
                                    'dataArr' => [
                                        'href' => $href1,
                                        'wecha_id' => $openid['openid'],
                                        'first' => '电表抄表扣费成功',
                                        'keyword1' => '电费',
                                        'keyword2' => $value['village_address'],
                                        'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $charge_price,

                                    ]
                                ];
                                //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                $msg = $templateNewsService->sendTempMsg($datamsg1['tempKey'], $datamsg1['dataArr']);

                            }
                            if ($remaining_capacity < $sys['electric_set'] || $remaining_capacity < $sys['price_electric_set']) {
                                if ($value['disabled'] != true) {
                                    $switch = $this->download_switch($value['id'], 'close');

                                }
                            }
                            //剩余电量小于断闸可手动开闸且大于断闸需交费
                            if ($remaining_capacity < $sys['electric_set'] && $remaining_capacity > $sys['price_electric_set']) {
                                if (!empty($openid)) {
                                    $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                    $templateNewsService = new TemplateNewsService();
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['electric_set'] . '，电表已断闸，可重新开闸，请及时缴费！',
                                            'keyword1' => '电表状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                }
                                $sms_data = array('type' => 'meter');
                                $sms_data['uid'] = $uid['uid'];
                                $sms_data['mobile'] = $openid['phone'];
                                $sms_data['sendto'] = 'user';
                                $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，可重新开闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['electric_set']));
                                $sms = (new SmsService())->sendSms($sms_data);

                            } //剩余电量小于断闸需交费
                            if ($remaining_capacity <= $sys['price_electric_set']) {
                                if (!empty($openid)) {
                                    $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                    $templateNewsService = new TemplateNewsService();
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['price_electric_set'] . '，电表已断闸，请及时缴费！',
                                            'keyword1' => '电表状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $tempMsg = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);

                                    $sms_data = array('type' => 'meter');
                                    $sms_data['uid'] = $uid['uid'];
                                    $sms_data['mobile'] = $openid['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['price_electric_set']));
                                    $sendSms = (new SmsService())->sendSms($sms_data);

                                }

                            }
                        }


                    }
                    /* $time2 = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                     $dateDay = time() - $time2;*/
                    $date1 = date('Y-m-d', $sys['meter_reading_date']);
                    $time1 = date('Y-m-d H:i', time());
                    $time2 = substr_replace($time1, $date1, 0, 10);
                    $dateDay = strtotime($time2);
                    $resDay = $dateDay >= $sys['meter_reading_date'] ? 'true' : 'false';
                    if ($sys['meter_reading_type'] == 2 && $dateDay >= $sys['meter_reading_date']) {
                        $where1=[];
                        $begin_time = date('Y-m-d 00:00:00', time());
                        $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                        $end_time = date('Y-m-d 23:59:59', time());
                        $where1[] = ['reading_time', '<=', strtotime($end_time)];
                        $where1[] = ['electric_id', '=', $value['id']];
                        $reading_list = $electric_realtime->getList($where1);
                        $reading_list = $reading_list->toArray();
                        $len = count($reading_list);
                        if ($len == 1) {
                            $realtime_last_info = $reading_list[0];
                        } elseif ($len > 1) {
                            $realtime_last_info = ['end_num' => $reading_list[$len - 1]['end_num'], 'begin_num' => $reading_list[0]['begin_num']];
                        } else {
                            $realtime_last_info = [];
                        }

                        // $realtime_last_info = $electric_realtime->where(['electric_id' => $value['id']])->order('id DESC')->find();

                        if (!empty($realtime_last_info)) {
                            $reading_num = $realtime_last_info['end_num'] - $realtime_last_info['begin_num'];
                            $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                            $order_arr = [
                                'uid' => $uid['uid'],
                                'phone' => $uid['phone'],
                                'province_id' => $value['province_id'],
                                'city_id' => $value['city_id'],
                                'area_id' => $value['area_id'],
                                'street_id' => $value['street_id'],
                                'community_id' => $value['village_id'],
                                'single_id' => $value['single_id'],
                                'floor_id' => $value['floor_id'],
                                'layer_id' => $value['layer_id'],
                                'vacancy_id' => $value['vacancy_id'],
                                'meter_reading_type' => $sys['meter_reading_type'],
                                'payment_num' => 0,
                                'payment_type' => 1,
                                'begin_num' => $realtime_last_info['begin_num'],
                                'end_num' => $realtime_last_info['end_num'],
                                'unit_price' => $electric_price['unit_price'],
                                'rate' => $electric_price['rate'],
                                'add_time' => time(),
                                'pay_time' => time(),
                                'electric_id' => $value['id'],
                                'order_no' => rand(1000, 9999) . time(),
                                'charge_num' => $reading_num,
                                'pay_type' => 'meterPay',
                                'charge_price' => $charge_price,
                                'status' => 2,
                            ];
                            $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                            $service_order->insertOne($order_arr);
                            $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => time()]);
                            $user = new User();
                            $openid = $user->getOne(['uid' => $uid['uid']]);

                            if (!empty($openid)) {
                                $href1 = get_base_url('pages/houseMeter/index/billList?electric_id=' . $value['id']);
                                $datamsg1 = [
                                    'tempKey' => 'TM01008',
                                    'dataArr' => [
                                        'href' => $href1,
                                        'wecha_id' => $openid['openid'],
                                        'first' => '电表抄表扣费成功',
                                        'keyword1' => '电费',
                                        'keyword2' => $value['village_address'],
                                        'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $charge_price,

                                    ]
                                ];
                                //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                $resqq = $templateNewsService->sendTempMsg($datamsg1['tempKey'], $datamsg1['dataArr']);
                            }
                            if (($remaining_capacity < $sys['electric_set']) || ($remaining_capacity < $sys['price_electric_set'])) {
                                if ($value['disabled'] != true) {
                                    $dayswitch = $this->download_switch($value['id'], 'close');
                                }

                            }

                            if ($remaining_capacity < $sys['electric_set'] && $remaining_capacity > $sys['price_electric_set']) {
                                if (!empty($openid)) {
                                    $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                    $templateNewsService = new TemplateNewsService();
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['electric_set'] . '，电表已断闸，可重新开闸，请及时缴费！',
                                            'keyword1' => '电表状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                    $sms_data = array('type' => 'meter');
                                    $sms_data['uid'] = $uid['uid'];
                                    $sms_data['mobile'] = $openid['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，可重新开闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['electric_set']));
                                    $sendSms = (new SmsService())->sendSms($sms_data);

                                }

                            }
                            if ($remaining_capacity <= $sys['price_electric_set']) {
                                if (!empty($openid)) {
                                    $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                    $templateNewsService = new TemplateNewsService();
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['price_electric_set'] . '，电表已断闸，请及时缴费！',
                                            'keyword1' => '电表状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                    $sms_data = array('type' => 'meter');
                                    $sms_data['uid'] = $uid['uid'];
                                    $sms_data['mobile'] = $openid['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['price_electric_set']));
                                    $send_Sms = (new SmsService())->sendSms($sms_data);
                                }


                            }

                        }
                    }
                }
            }
        }

        return true;

    }

    /**
     * 获取抄表数据
     * @author:zhubaodi
     * @date_time: 2021/4/21 13:50
     */
    public function meter_reading1()
    {
        $house_meter_electric = new HouseMeterAdminElectric();
        $electric_sys = new HouseMeterReadingSys();
        $electric_realtime = new HouseMeterElectricRealtime();
        $service_vacancy = new HouseVillageUserVacancy();
        $service_price = new HouseMeterElectricPrice();
        $service_order = new HouseMeterUserPayorder();
        $templateNewsService = new TemplateNewsService();

        $time = strtotime(date('Y-m-d 00:00:00'));
        $where = [];
        $where[] = ['status', '=', '0'];
        $where[] = ['reading_time', '<', $time];
        $where[] = ['nowtime', '<', time()-3000];

        $electric_list = $house_meter_electric->getListAll($where, true, 1, 50);

        if (!empty($electric_list)) {
            $electric_list = $electric_list->toArray();
            $sys = $electric_sys->getInfo(['id' => 1]);
            foreach ($electric_list as $value) {
                $uid = $service_vacancy->getOne(['pigcms_id' => $value['vacancy_id']]);
                if ($sys) {
                    if (empty($value['electric_price_id']) && empty($value['unit_price'])) {
                        $electric_price = $service_price->getInfo(['city_id' => $value['city_id'], 'house_type' => $uid['house_type']]);
                    } elseif (!empty($value['electric_price_id'])) {
                        $electric_price = $service_price->getInfo(['id' => $value['electric_price_id']]);
                    } else {
                        $electric_price['unit_price'] = $value['unit_price'];
                        $electric_price['rate'] = $value['rate'];
                    }
                    $date1 = date('Y-m', $sys['meter_reading_date']);
                    $time1 = date('Y-m-d H:i', time());
                    $time2 = substr_replace($time1, $date1, 0, 7);
                    $dateMonth = strtotime($time2);

                    if ($sys['meter_reading_type'] == 1 && $dateMonth >= $sys['meter_reading_date']) {
                        $house_meter_electric->saveOne(['id' => $value['id']], ['nowtime' => time()]);
                        $where1=[];
                        $begin_time = date('Y-m-01', strtotime('-1 month'));
                        $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                        $end_time = date('Y-m-t', strtotime('-1 month'));
                        $where1[] = ['reading_time', '<=', strtotime($end_time)];
                        $where1[] = ['electric_id', '=', $value['id']];
                        $reading_list = $electric_realtime->getList($where1);
                        $reading_list = $reading_list->toArray();
                        $len = count($reading_list);
                        if (!empty($reading_list) && $len > 0) {
                            $reading_num = $reading_list[0]['end_num'] - $reading_list[$len - 1]['begin_num'];
                            $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                            $order_arr = [
                                'uid' => $uid['uid'],
                                'phone' => $uid['phone'],
                                'province_id' => $value['province_id'],
                                'city_id' => $value['city_id'],
                                'area_id' => $value['area_id'],
                                'street_id' => $value['street_id'],
                                'community_id' => $value['community_id'],
                                'village_id' => $value['village_id'],
                                'single_id' => $value['single_id'],
                                'floor_id' => $value['floor_id'],
                                'layer_id' => $value['layer_id'],
                                'vacancy_id' => $value['vacancy_id'],
                                'meter_reading_type' => $sys['meter_reading_type'],
                                'payment_num' => 0,
                                'payment_type' => 1,
                                'begin_num' => $reading_list[0]['begin_num'],
                                'end_num' => $reading_list[$len - 1]['end_num'],
                                'unit_price' => $electric_price['unit_price'],
                                'rate' => $electric_price['rate'],
                                'add_time' => time(),
                                'pay_time' => time(),
                                'electric_id' => $value['id'],
                                'order_no' => build_real_orderid($uid['uid']),
                                'charge_num' => $reading_num,
                                'pay_type' => 'meterPay',
                                'charge_price' => $charge_price,
                                'status' => 2,
                            ];
                            $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                            $service_order->insertOne($order_arr);
                            $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => time()]);
                            $user = new User();
                            $openid = $user->getOne(['uid' => $uid['uid']]);
                            if (!empty($openid)) {
                                $href1 = get_base_url('pages/houseMeter/index/billList?electric_id=' . $value['id']);
                                $datamsg1 = [
                                    'tempKey' => 'TM01008',
                                    'dataArr' => [
                                        'href' => $href1,
                                        'wecha_id' => $openid['openid'],
                                        'first' => '电表抄表扣费成功',
                                        'keyword1' => '电费',
                                        'keyword2' => $value['village_address'],
                                        'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $charge_price,

                                    ]
                                ];
                                //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                $msg = $templateNewsService->sendTempMsg($datamsg1['tempKey'], $datamsg1['dataArr']);

                            }
                            if ($remaining_capacity < $sys['electric_set'] || $remaining_capacity < $sys['price_electric_set']) {
                                if ($value['disabled'] != true) {
                                    $switch = $this->download_switch($value['id'], 'close');

                                }
                            }
                            //剩余电量小于断闸可手动开闸且大于断闸需交费
                            if ($remaining_capacity < $sys['electric_set'] && $remaining_capacity > $sys['price_electric_set']) {
                                if (!empty($openid)) {
                                    $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                    $templateNewsService = new TemplateNewsService();
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['electric_set'] . '，电表已断闸，可重新开闸，请及时缴费！',
                                            'keyword1' => '电表状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                }
                                $sms_data = array('type' => 'meter');
                                $sms_data['uid'] = $uid['uid'];
                                $sms_data['mobile'] = $openid['phone'];
                                $sms_data['sendto'] = 'user';
                                $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，可重新开闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['electric_set']));
                                $sms = (new SmsService())->sendSms($sms_data);

                            } //剩余电量小于断闸需交费
                            if ($remaining_capacity <= $sys['price_electric_set']) {
                                if (!empty($openid)) {
                                    $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                    $templateNewsService = new TemplateNewsService();
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['price_electric_set'] . '，电表已断闸，请及时缴费！',
                                            'keyword1' => '电表状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $tempMsg = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);

                                    $sms_data = array('type' => 'meter');
                                    $sms_data['uid'] = $uid['uid'];
                                    $sms_data['mobile'] = $openid['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['price_electric_set']));
                                    $sendSms = (new SmsService())->sendSms($sms_data);

                                }

                            }
                        }


                    }
                    /* $time2 = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                     $dateDay = time() - $time2;*/
                    $date1 = date('Y-m-d', $sys['meter_reading_date']);
                    $time1 = date('Y-m-d H:i', time());
                    $time2 = substr_replace($time1, $date1, 0, 10);
                    $dateDay = strtotime($time2);
                    $resDay = $dateDay >= $sys['meter_reading_date'] ? 'true' : 'false';
                    $dateDay=1623857446;
                    if ($sys['meter_reading_type'] == 2 && $dateDay >= $sys['meter_reading_date']) {
                        $house_meter_electric->saveOne(['id' => $value['id']], ['nowtime' => time()]);
                        print_r($value);exit;
                        $where1=[];
                        $begin_time = date('Y-m-d 00:00:00', time());
                        $where1[] = ['reading_time', '>=', strtotime($begin_time)];
                        $end_time = date('Y-m-d 23:59:59', time());
                        $where1[] = ['reading_time', '<=', strtotime($end_time)];
                        $where1[] = ['electric_id', '=', $value['id']];
                        $reading_list = $electric_realtime->getList($where1);
                        $reading_list = $reading_list->toArray();
                        $len = count($reading_list);
                        if ($len == 1) {
                            $realtime_last_info = $reading_list[0];
                        } elseif ($len > 1) {
                            $realtime_last_info = ['end_num' => $reading_list[$len - 1]['end_num'], 'begin_num' => $reading_list[0]['begin_num']];
                        } else {
                            $realtime_last_info = [];
                        }

                        // $realtime_last_info = $electric_realtime->where(['electric_id' => $value['id']])->order('id DESC')->find();

                        if (!empty($realtime_last_info)) {
                            $reading_num = $realtime_last_info['end_num'] - $realtime_last_info['begin_num'];
                            $charge_price = $reading_num * $electric_price['unit_price'] * $electric_price['rate'];
                            $order_arr = [
                                'uid' => $uid['uid'],
                                'phone' => $uid['phone'],
                                'province_id' => $value['province_id'],
                                'city_id' => $value['city_id'],
                                'area_id' => $value['area_id'],
                                'street_id' => $value['street_id'],
                                'community_id' => $value['village_id'],
                                'single_id' => $value['single_id'],
                                'floor_id' => $value['floor_id'],
                                'layer_id' => $value['layer_id'],
                                'vacancy_id' => $value['vacancy_id'],
                                'meter_reading_type' => $sys['meter_reading_type'],
                                'payment_num' => 0,
                                'payment_type' => 1,
                                'begin_num' => $realtime_last_info['begin_num'],
                                'end_num' => $realtime_last_info['end_num'],
                                'unit_price' => $electric_price['unit_price'],
                                'rate' => $electric_price['rate'],
                                'add_time' => time(),
                                'pay_time' => time(),
                                'electric_id' => $value['id'],
                                'order_no' => rand(1000, 9999) . time(),
                                'charge_num' => $reading_num,
                                'pay_type' => 'meterPay',
                                'charge_price' => $charge_price,
                                'status' => 2,
                            ];
                            $remaining_capacity = $value['remaining_capacity'] - $reading_num;
                            $service_order->insertOne($order_arr);
                            $house_meter_electric->saveOne(['id' => $value['id']], ['remaining_capacity' => $remaining_capacity, 'reading_time' => time()]);
                            $user = new User();
                            $openid = $user->getOne(['uid' => $uid['uid']]);

                            if (!empty($openid)) {
                                $href1 = get_base_url('pages/houseMeter/index/billList?electric_id=' . $value['id']);
                                $datamsg1 = [
                                    'tempKey' => 'TM01008',
                                    'dataArr' => [
                                        'href' => $href1,
                                        'wecha_id' => $openid['openid'],
                                        'first' => '电表抄表扣费成功',
                                        'keyword1' => '电费',
                                        'keyword2' => $value['village_address'],
                                        'remark' => '缴费时间:' . date('Y-m-d H:i') . '\n' . '缴费金额:￥' . $charge_price,

                                    ]
                                ];
                                //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                $resqq = $templateNewsService->sendTempMsg($datamsg1['tempKey'], $datamsg1['dataArr']);
                            }
                            if (($remaining_capacity < $sys['electric_set']) || ($remaining_capacity < $sys['price_electric_set'])) {
                                if ($value['disabled'] != true) {
                                    $dayswitch = $this->download_switch($value['id'], 'close');
                                }

                            }

                            if ($remaining_capacity < $sys['electric_set'] && $remaining_capacity > $sys['price_electric_set']) {
                                if (!empty($openid)) {
                                    $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                    $templateNewsService = new TemplateNewsService();
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['electric_set'] . '，电表已断闸，可重新开闸，请及时缴费！',
                                            'keyword1' => '电表状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                    $sms_data = array('type' => 'meter');
                                    $sms_data['uid'] = $uid['uid'];
                                    $sms_data['mobile'] = $openid['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，可重新开闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['electric_set']));
                                    $sendSms = (new SmsService())->sendSms($sms_data);

                                }

                            }
                            if ($remaining_capacity <= $sys['price_electric_set']) {
                                if (!empty($openid)) {
                                    $href = get_base_url('pages/houseMeter/index/meterDetails?electric_id=' . $value['id']);
                                    $templateNewsService = new TemplateNewsService();
                                    $datamsg = [
                                        'tempKey' => 'OPENTM400166399',
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $openid['openid'],
                                            'first' => '您好，您所在的' . $value['village_address'] . '电表剩余度数低于' . $sys['price_electric_set'] . '，电表已断闸，请及时缴费！',
                                            'keyword1' => '电表状态提醒',
                                            'keyword2' => '已发送',
                                            'keyword3' => date('H:i'),
                                            'remark' => '请点击查看详细信息！',

                                        ]
                                    ];
                                    //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                                    $restem = $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                    $sms_data = array('type' => 'meter');
                                    $sms_data['uid'] = $uid['uid'];
                                    $sms_data['mobile'] = $openid['phone'];
                                    $sms_data['sendto'] = 'user';
                                    $sms_data['content'] = L_('您好，您所在的x1电表剩余度数低于x2，电表已断闸，请及时缴费！！', array('x1' => $value['village_address'], 'x2' => $sys['price_electric_set']));
                                    $send_Sms = (new SmsService())->sendSms($sms_data);
                                }


                            }

                        }
                    }
                }
            }
        }

        return true;

    }

    //获取小区公共区域
    public function getHouseVillagePublicAreaList($whereArr=array(),$field='*'){
        if(empty($whereArr)){
            return array();
        }
        $houseVillagePublicArea=new HouseVillagePublicArea();
        $publicAreaList=$houseVillagePublicArea->getList($whereArr,$field);
        if($publicAreaList && !$publicAreaList->isEmpty()){
            $publicAreaList=$publicAreaList->toArray();
        }else{
            $publicAreaList=array();
        }
        return $publicAreaList;
    }

}
