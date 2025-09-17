<?php
/**
 * Created by PhpStorm.
 * User: wanziyang
 * Date Time: 2020/4/23 11:07
 * 社区管理App登录业务层
 */
namespace app\community\model\service;

use app\community\model\db\HouseWorker;
use app\community\model\db\PropertyAdmin;
use app\community\model\db\HouseAdminGroup;
use app\community\model\db\HouseAdmin;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseProperty;
use app\community\model\db\HouseMenuNew;
use app\community\model\db\AppapiAppConfig;
use app\community\model\db\AreaStreetWorkers;
use app\consts\HouseWorkConst;

class ManageAppLoginService {

    public  $base_url = '/packapp/plat/';
    public function __construct(){
        if(cfg('system_type') == 'village'){
            $this->base_url = '/packapp/village/';
        }else{
            $this->base_url = '/packapp/plat/';
        }
    }

    // 绑定小区用户身份-区块色-背景色和文字颜色  0房主 1，家人 2租客 3，更新房主
    public $user_bind_type_color = [
        0=>['backgroundColor'=>'#2681f3','color'=>'white'],
        1=>['backgroundColor'=>'#ffa200','color'=>'white'],
        2=>['backgroundColor'=>'#2fd07b','color'=>'white'],
        3=>['backgroundColor'=>'#2681f3','color'=>'white'],
    ];
    // 绑定小区用户身份 0房主 1，家人 2租客 3，更新房主
    public $user_bind_type_arr = [
        0=>'业主',
        1=>'家属',
        2=>'租客',
        3=>'替换业主',
    ];
    public $user_bind_type_arr_other = [
        0=>'业主',
        1=>'家属',
        2=>'租客/员工',
        3=>'替换业主',
    ];
    // 绑定小区家属身份 1配偶 2 父母 3子女 4亲朋好友
    public $user_bind_relatives_type_arr = [
        1=>'配偶',
        2=>'父母',
        3=>'子女',
        4=>'亲朋好友',
        5=>'老板',
        6=>'人事',
        7=>'财务',
    ];

    //**************  移动管理端角色↓//
    /**
     * @var integer 移动管理端 街道工作人员
     */
    const MANAGE_APP_STREET_WORK = 1;
    /**
     * @var integer 移动管理端 街道管理员-暂未使用
     */
    const MANAGE_APP_STREET_ADMIN = 101;
    /**
     * @var integer 移动管理端 社区管理员-暂未使用
     */
    const MANAGE_APP_COMMUNITY_ADMIN = 201;
    /**
     * @var integer 移动管理端 社区工作人员-暂未使用
     */
    const MANAGE_APP_COMMUNITY_WORK = 202;
    /**
     * @var integer 移动管理端 物业总管理员
     */
    const MANAGE_APP_PROPERTY_ADMIN = 3;
    /**
     * @var integer 移动管理端 物业普通管理员
     */
    const MANAGE_APP_PROPERTY_USER = 4;
    /**
     * @var integer 移动管理端 小区物业管理人员
     */
    const MANAGE_APP_VILLAGE_ADMIN = 5;
    /**
     * @var integer 移动管理端 小区物业工作人员
     */
    const MANAGE_APP_VILLAGE_WORK = 6;
    //**************  注意↑ 移动管理端角色 //

    /**
     * 获取管理H5小程序登录角色  注意 只可增加或者删除 不可更改
     * @author: wanziyang
     * @date_time: 2020/4/23 11:35
     * @return array
     */
    public function login_role() {
        $role = [
//            HouseWorkConst::MANAGE_APP_STREET_WORK => '街道工作人员',
//            2 => '社区',
            HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN => '物业总管理员',
            HouseWorkConst::MANAGE_APP_PROPERTY_USER => '物业普通管理员',
            HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN =>'小区物业管理人员',
            HouseWorkConst::MANAGE_APP_VILLAGE_WORK => '小区物业工作人员',
        ];
        return $role;
    }
    /**
     * 获取管理App登录角色  注意 只可增加或者删除 不可更改
     * @author: wanziyang
     * @date_time: 2020/4/23 11:35
     * @return array
     */
    public function login_app_role() {
        $role = [
            ['type' => HouseWorkConst::MANAGE_APP_STREET_WORK, 'name' => '街道工作人员'],
//            ['type' => 2, 'name' => '社区'],
            ['type' => HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN, 'name' => '物业总管理员'],
            ['type' => HouseWorkConst::MANAGE_APP_PROPERTY_USER, 'name' => '物业普通管理员'],
            ['type' => HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN, 'name' => '小区物业管理人员'],
            ['type' => HouseWorkConst::MANAGE_APP_VILLAGE_WORK, 'name' => '小区物业工作人员']
        ];
        return $role;
    }

    /**
     * 小区移动管理端判断登录角色是否是物业或者
     * @param $login_role
     * @return bool
     */
    public function judgeIsPropertyOrVillage($login_role, $village_id = 0, $property_id = 0) {
        $propertyOrVillageArr  = [
            HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN,
            HouseWorkConst::MANAGE_APP_PROPERTY_USER,
            HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN,
            HouseWorkConst::MANAGE_APP_VILLAGE_WORK,
        ];
        $service_house_face_device = new HouseFaceDeviceService();
        $where = [];
        $where[] = ['camera_status', 'in', [0,1]];
        $where[] = ['look_url', '<>', ''];
        if (!$village_id || $village_id <= 0) {
            $whereVillageColumn = [];
            $whereVillageColumn[] = ['property_id', '=', $property_id];
            $whereVillageColumn[] = ['status', '=', 1];
            $village_id_arr = (new HouseVillageService())->getVillageColumn($whereVillageColumn, 'village_id');
        }
        if(isset($village_id_arr)) {
            $where[] = ['village_id', 'in', $village_id_arr];
        } elseif ($village_id > 0) {
            $where[] = ['village_id', '=', $village_id];
        }
        $count = $service_house_face_device->getCameraCount($where);
        if ($count <= 0) {
            return false;
        }
        
        if (in_array($login_role, $propertyOrVillageArr)) {
            return true;
        }
        return false;
    }


    public function get_app_base_url() {
        if(cfg('system_type') == 'village'){
            $base_url = '/packapp/village/';
        }else{
            $base_url = '/packapp/plat/';
        }
        return $base_url;
    }

    /**
     * @author: wanziyang
     * @date_time: 2020/4/25 13:24
     * @param int $time 时间戳
     * @param string $msg 信息
     * @param string $village_name 小区名称
     * @return string
     */
    public function time_tip($time=0,$msg='',$village_name='') {
        if (!$time) {
            $time = time();
        }
        $tip = '您好';
        $date_day = date('Y-m-d');
        $time_h = $date_day .' '.date('H:i', $time);
        $time = strtotime($time_h);
        $day_time = [
            ['start' => '5:00', 'end' => '9:00', 'tip' => '早上好'],
            ['start' => '9:00', 'end' => '12:00', 'tip' => '上午好'],
            ['start' => '12:00', 'end' => '18:00', 'tip' => '下午好'],
            ['start' => '18:00', 'end' => '22:00', 'tip' => '晚上好'],
            ['start' => '22:00', 'end' => '5:00', 'tip' => '夜深了'],
        ];
        foreach ($day_time  as $val) {
            $start = strtotime($date_day.' '.$val['start']);
            $end = strtotime($date_day.' '.$val['end']);
            if ($time>$start && $time<=$end) {
                $tip = $val['tip'];
            }
        }
        if ($msg) {
            $tip .= $msg;
        }
        $tip_arr = [
            'title_tip' => $tip,
            'welcome' => '欢迎使用'.$village_name.'管理平台',
        ];
        return $tip_arr;
    }

    /**
     * 获取单个物业管理数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param int $id 对应物业管理id
     * @param string|bool $field 需要获取的对应字段
     * @param string $key 查询条件字段
     * @return array|null|\think\Model
     */
    public function get_property_admin($id,$field = true,$key = 'id') {
        // 初始化 物业管理员 数据层
        $where[$key] = $id;
        $info = $this->get_property_admin_where($where,$field);
//        // 初始化 物业管理员权限分组 数据层  物业没有权限分组暂时隐藏
//        $db_House_admin_group = new HouseAdminGroup();
//        if ($info['group_id']) { // 绑定了权限分组
//            $where = ['group_id'=>$info['group_id'],'status'=>1];
//            $group = $db_House_admin_group->get_one($where);
//            $info['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
//        }
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 获取单个物业管理数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_property_admin_where($where,$field = true) {
        // 初始化 物业管理员 数据层
        $db_property_admin = new PropertyAdmin();
        $info = $db_property_admin->get_one($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }
    public function  save_property_admin($whereArr,$saveArr=array()){

        $db_property_admin = new PropertyAdmin();
        $ret = $db_property_admin->save_one($whereArr,$saveArr);
        return $ret;
    }
    /**
     * 获取单个社区管理员
     * @author: wanziyang
     * @date_time: 2020/4/24 11:31
     * @param int $id 对应物业管理id
     * @param string|bool $field 需要获取的对应字段
     * @param string $key 查询条件字段
     * @return array|null|\think\Model
     */
    public function get_house_admin($id,$field = true,$key = 'id') {
        // 初始化 小区管理员 数据层
        $where[$key] = $id;
        $info = $this->get_house_admin_where($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 条件获取社区管理员
     * @author: wanziyang
     * @date_time: 2020/4/24 11:31
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_house_admin_where($where,$field = true) {
        // 初始化 小区管理员 数据层
        $db_house_admin = new HouseAdmin();
        $info = $db_house_admin->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {

            if($info && !empty($info['wid'])){
                $info['menus']= (new HouseProgrammeService())->getProgrammeGroupMenus($info['village_id'],$info['wid'],$info['group_id'],$info['menus']);
            }else if($info && $info['group_id']){
                $db_house_admin_group = new HouseAdminGroup();
                $group = $db_house_admin_group->get_one(['group_id'=>$info['group_id'],'status'=>1]);
                $info['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
            }
//            if ($info['group_id']) { // 绑定了权限分组
//                $db_house_admin_group = new HouseAdminGroup();
//                $group = $db_house_admin_group->get_one(['group_id'=>$info['group_id'],'status'=>1]);
//                $info['menus'] = $group['group_menus'] ? $group['group_menus'] : '';
//            }
            if ($info['menus']) {
                $menus = $info['menus'];
                if (!empty($menus)) {
                    $db_house_menu_new = new HouseMenuNew();
                    $where = array(
                        ['id','in',$menus],
                        ['status','=','1']
                    );
                    $menus_info = $db_house_menu_new->getList($where);
                    $exist_menus = array();
                    if ($menus_info) {
                        foreach ($menus_info as $key => $value) {
                            $exist_menus[] = $value['id'];
                        }
                    }
                    $info['menus'] = implode(',',$exist_menus);
                }
            }
        }
        return $info;
    }


    /**
     * 条件获取社区管理员小区
     * @author: wanziyang
     * @date_time: 2020/5/7 11:43
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_admin_village_list($where,$field = 'a.*') {
        // 初始化 小区管理员 数据层
        $db_house_admin = new HouseAdmin();
        $list = $db_house_admin->get_admin_village($where,$field);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }


    /**
     * 更新设备信息
     * @author: wanziyang
     * @date_time: 2020/4/24 14:44
     * @param int $type 处理类型 1 街道 2 社区 3 物业总管理员  4 物业普通管理员 5 小区工作人员
     * @param int $id 对应id
     * @param string $device_id 设备id
     * @return bool
     */
    public function up_device($type,$id,$device_id){
        if (!$device_id) {
            return true;
        }
        $userUpdata =   array(
            'device_id' =>  $device_id
        );
        if ($type==1) {
            // 街道工作人员
            $dbAreaStreetWorkers = new AreaStreetWorkers();
            $dbAreaStreetWorkers->saveOne(['worker_id' => $id],$userUpdata);
        }elseif ($type==2){
            // 社区
        }elseif ($type==3){
            // 物业总管理员
            // 初始化 社区物业管理 数据层
            $db_property_admin = new PropertyAdmin();
            $db_property_admin->save_one(['id' => $id],$userUpdata);
        }elseif ($type==4){
            // 物业普通管理员
            // 初始化 社区物业 数据层
            $db_house_property = new HouseProperty();
            $db_house_property->save_one(['id' => $id],$userUpdata);
        }else{
            // 小区工作人员
            // 初始化 社区 数据层
            $db_house_village = new HouseVillage();
            $db_house_village->saveOne(['village_id' => $id],$userUpdata);
        }
    }

    /**
     * 编辑社区管理员信息
     * @author: wanziyang
     * @date_time: 2020/4/24 17:58
     * @param array $where 查询条件
     * @param array $data 对应街道社区id
     * @return array|null|\think\Model
     */
    public function edit_house_admin($where,$data) {
        // 初始化 街道社区管理员 数据层
        $db_house_admin = new HouseAdmin();
        $edit = $db_house_admin->save_one($where,$data);
        return $edit;
    }

    /**
     * 查询配置
     * @author: wanziyang
     * @date_time: 2020/4/29 20:58
     * @param string $field // 过滤字段
     * @return array
     */
    public function get_appapi_app_config($field='') {
        $db_appapi_app_config = new AppapiAppConfig();
        $appapi_app_config = $db_appapi_app_config->get_list();
        if (!$appapi_app_config || $appapi_app_config->isEmpty()) {
            return [];
        }
        $app_config = [];
        foreach($appapi_app_config as $value){
            $app_config[$value['var']] = replace_file_domain($value['value']);
        }
        if($field == ''){
            return $app_config;
        }else{
            return $app_config[$field];
        }
    }

    /**
     * Notes: 获取小区工作人员资料
     * @param $wid
     * @return array|\think\Model|null
     * @author: weili
     * @datetime: 2020/10/22 16:22
     */
    public function getHouseWorkerInfo($wid)
    {
        $dbHouseWorker = new HouseWorker();
        $where[] = ['wid','=',$wid];
        $info = $dbHouseWorker->getOne($where);
        return $info;
    }
}