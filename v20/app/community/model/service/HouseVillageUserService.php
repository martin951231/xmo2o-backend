<?php
/**
 * Created by PhpStorm.
 * User: wanziyang
 * Date Time: 2020/4/26 11:00
 */
namespace app\community\model\service;


use app\common\model\service\UserService as CommonUserService;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageUserAttribute;
use app\community\model\db\HouseVillageDataConfig;
use app\community\model\db\HouseVillageUserUnbind;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillage;
use app\community\model\db\ProcessSubPlan;
use app\community\model\db\User;
use app\community\model\service\UserService;

use think\facade\Log;
class HouseVillageUserService {

    public $relatives_type_info = array(
        0 => array(
            'relatives_type' => 1,
            'name' => '配偶'
        ),
        1 => array(
            'relatives_type' => 2,
            'name' => '父母'
        ),
        2 => array(
            'relatives_type' => 3,
            'name' => '子女'
        ),
        3 => array(
            'relatives_type' => 4,
            'name' => '亲朋好友'
        ),
    );

    public $relatives_type_info_other = array(
        0 => array(
            'relatives_type' => 1,
            'name' => '配偶'
        ),
        1 => array(
            'relatives_type' => 2,
            'name' => '父母'
        ),
        2 => array(
            'relatives_type' => 3,
            'name' => '子女'
        ),
        3 => array(
            'relatives_type' => 4,
            'name' => '亲朋好友'
        ),
        4 => array(
            'relatives_type' => 5,
            'name' => '老板'
        ),
        5 => array(
            'relatives_type' => 6,
            'name' => '人事'
        ),
        6 => array(
            'relatives_type' => 7,
            'name' => '财务'
        ),
    );

    public $relatives_type_arr=[
        1=>'配偶',2=>'父母',3=>'子女',4=>'亲朋好友',5=>'老板',6=>'人事',7=>'财务'
    ];

    /**
     * 获取小区绑定用户信息
     * @author:wanziyang
     * @date_time: 2020/4/30 10:04
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param bool $get_owner 是否获取父级业主信息
     * @param int $page_size 每页条数
     * @return array|null|\think\Model
     */
    public function getLimitRoomList($where,$page=0,$field =true,$order='a.pigcms_id DESC',$get_owner=false,$page_size=20) {
        $db_house_village_user_bind = new HouseVillageUserBind();
        $list = $db_house_village_user_bind->getLimitRoomList($where,$page,$field, $order,$page_size);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            $service_house_village = new HouseVillageService();
            foreach($list as &$val) {
                $val['address'] = $service_house_village->getSingleFloorRoom($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
                if ($get_owner && $val['parent_id']>0) {
                    $val['parent_info'] = $db_house_village_user_bind->getOne(['pigcms_id'=>$val['parent_id']],'name,phone');
                }
                if ($val['add_time']) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                if (isset($val['bind_number']) && $val['bind_number']) {
                    $val['usernum'] = $val['bind_number'];
                }
            }
        }
        return $list;
    }

    /**
     * 获取单个用户数据信息
     * @author:wanziyang
     * @date_time: 2020/4/26 15:33
     * @param int $id 对应用户绑定id
     * @param string|bool $field 需要获取的对应字段
     * @param string $key 查询条件字段
     * @return array|null|\think\Model
     */
    public function getHouseUserBind($id,$field = true,$key = 'pigcms_id') {
        // 初始化 物业管理员 数据层
        $where[$key] = $id;
        $info = $this->getHouseUserBindWhere($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        if($info){
            $user_service = new UserService();
            $user = $user_service->getUserOne(['uid'=>$info['uid']],'cardid');
            if($user) {
                $info['cardid'] = $user['cardid'];
            }
            if (isset($info['bind_number']) && $info['bind_number']) {
                $info['usernum'] = $info['bind_number'];
            }
        }
        if ($info && $info['single_id'] && $info['floor_id'] && $info['layer_num'] && $info['room_addrss']) {
            // 如果存在楼栋id 单元id 楼层和房间 取地址为自定义地址
            // 初始化 数据层
            $db_house_village_single = new HouseVillageSingle();
            $db_house_village_floor = new HouseVillageFloor();
            $service_house_village = new HouseVillageService();

            $where_single = [];
            $where_single[] = ['id','=',$info['single_id']];
            $single_info = $db_house_village_single->getOne($where_single,'single_name');


            $where_floor = [
                'floor_id' => $info['floor_id']
            ];
            $floor_info = $db_house_village_floor->getOne($where_floor,'floor_name, floor_layer');
            $word_data = [
                'single_name' => $single_info['single_name'],
                'floor_name' => $floor_info['floor_name'],
                'layer' => $info['layer_num'],
                'room' => $info['room_addrss']
            ];
            $info['address'] = $service_house_village->word_replce_msg($word_data, $id);
            $info['single_name'] = $single_info['single_name'];
            $info['floor_name'] = $floor_info['floor_name'];
            $info['floor_layer'] = $floor_info['floor_layer'];
        } elseif($info && $info['floor_id'] && $info['vacancy_id']) {
            // 如果存在 单元id 房间id 取地址为自定义地址
            // 初始化 数据层
            $db_house_village_single = new HouseVillageSingle();
            $db_house_village_floor = new HouseVillageFloor();
            $db_house_village_user_vacancy = new HouseVillageUserVacancy();
            $service_house_village = new HouseVillageService();;
            $where_floor = [
                'floor_id' => $info['floor_id']
            ];
            $floor_info = $db_house_village_floor->getOne($where_floor,'floor_name, floor_layer, single_id');
            if (empty($info['layer_num']) || empty($info['room_addrss'])) {
                $where_vacancy = [
                    'pigcms_id' => $info['vacancy_id']
                ];
                $vacancy_info = $db_house_village_user_vacancy->getOne($where_vacancy,'layer, room');
                $info['layer_num'] = $vacancy_info['layer'];
                $info['room_addrss'] = $vacancy_info['room'];
            }
            if (empty($info['single_id'])) {
                $info['single_id'] = $floor_info['single_id'];
                $db_house_village_user_bind = new HouseVillageUserBind();
                $db_house_village_user_bind->saveOne($where,['single_id' => $floor_info['single_id']]);
            }
            $where_single = [];
            $where_single[] = ['id','=',$info['single_id']];
            $single_info = $db_house_village_single->getOne($where_single,'single_name');
            $word_data = [
                'single_name' => $single_info['single_name'],
                'floor_name' => $floor_info['floor_name'],
                'layer' => $info['layer_num'],
                'room' => $info['room_addrss']
            ];
            $info['address'] = $service_house_village->word_replce_msg($word_data, $id);
            $info['single_name'] = $single_info['single_name'];
            $info['floor_name'] = $floor_info['floor_name'];
            $info['floor_layer'] = $floor_info['floor_layer'];
        }
        return $info;
    }

    /**
     * 条件获取单个用户数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getHouseUserBindWhere($where,$field = true, $is_car=false) {
        // 初始化 物业管理员 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $now_user = $db_house_village_user_bind->getOne($where,$field);
        if (!$now_user || $now_user->isEmpty()) {
            $now_user = [];
        } elseif ($is_car) {
            $field = 'b.car_id,b.province,b.car_number,c.position_num,c.position_id';
            $db_house_village_bind_car = new HouseVillageBindCar();
            $car_list = $db_house_village_bind_car->user_bind_car_list($now_user['pigcms_id'],[],$field);
            $car_arr = [];
            if ($car_list) {
                foreach ($car_list as $val) {
                    $car_arr[] = [
                        'car_id' => $val['car_id'],
                        'position_id' => $val['position_id'],
                        'position_num' => $val['position_num'],
                        'car_number' => $val['province'] . $val['car_number'],
                    ];
                }
            }
            $now_user['car_list'] = $car_arr;
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $fields = 'a.position_id,a.position_num,c.garage_num';
            $position_list = $db_house_village_parking_position->getUserPositionBindList($now_user['pigcms_id'],[],$fields);
            if (!$position_list) {
                $position_list = [];
            }
            $now_user['position_list'] = $position_list;
            if ($now_user) {
                $user = [];
                if ($now_user['name']) {
                    $user[] = [
                        'title' => '姓名',
                        'content' => $now_user['name'],
                        'is_phone' => 0
                    ];
                }
                if ($now_user['phone']) {
                    $user[] = [
                        'title' => '手机号',
                        'content' => $now_user['phone'],
                        'is_phone' => 1
                    ];
                }
                $user[] = [
                    'title' => '身份证号',
                    'content' => $now_user['id_card'] ? $now_user['id_card'] : '无',
                    'is_phone' => 0
                ];
                $user[] = [
                    'title' => 'IC卡号',
                    'content' => $now_user['ic_card'] ? $now_user['ic_card'] : '无',
                    'is_phone' => 0
                ];
                $user[] = [
                    'title' => '属性',
                    'content' => $now_user['attribute_name'] ? $now_user['attribute_name'] : '无',
                    'is_phone' => 0
                ];
                $user[] = [
                    'title' => '车位信息',
                    'content' => $position_list,
                    'is_phone' => 0
                ];
                $user[] = [
                    'title' => '车辆信息',
                    'content' => $car_arr,
                    'is_phone' => 0
                ];
                if ($now_user['address']) {
                    $user[] = [
                        'title' => '家庭住址',
                        'content' => $now_user['address'],
                        'is_phone' => 0
                    ];
                }
            }
            $now_user['user'] = $user;
        }
        if (isset($now_user['bind_number']) && $now_user['bind_number']) {
            $now_user['usernum'] = $now_user['bind_number'];
        }

        return $now_user;
    }

    /**
     * 获取单个工作人员信息
     * @author: wanziyang
     * @date_time: 2020/4/28 14:12
     * @param array $where
     * @param string|bool $field 需要获取的对应字段
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function getHouseWorker($where,$field =true) {
        $db_get_house_worker = new HouseWorker();
        $info = $db_get_house_worker->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }



    /**
     * 条件获取单个用户数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getHouseUserAttributeWhere($where,$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_village_user_attribute = new HouseVillageUserAttribute();
        $now_user = $db_house_village_user_attribute->getOne($where,$field);
        if (!$now_user || $now_user->isEmpty()) {
            $now_user = [];
        }
        return $now_user;
    }

    /**
     * Notes: 用户绑定对应的车辆信息
     * @param $bind_id
     * @param array $where
     * @return array
     * @author: wanzy
     * @date_time: 2020/11/4 14:55
     */
    public function getUserBindCarListWhere($bind_id, $where = [],$field = true) {
        $db_house_village_bind_car = new HouseVillageBindCar();
        $car_list = $db_house_village_bind_car->user_bind_car_list($bind_id,$where,$field);
        if (!$car_list || $car_list->isEmpty()) {
            $car_list = [];
        }
        return $car_list;
    }


    /**
     * 获取小区业主属性信息
     * @author: wanziyang
     * @date_time: 2020/5/8 16:44
     * @param array $where 查询条件
     * @param bool $format 取值后处理
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @param string $order 排序规则
     * @return array|null|Model
     */
    public function getHouseUserAttributeList($where,$format=false,$field=true,$order='pigcms_id ASC') {
        // 初始化 物业管理员 数据层
        $db_house_village_user_attribute = new HouseVillageUserAttribute();
        $list = $db_house_village_user_attribute->getList($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            $list=$list->toArray();
            if ($format) {
                $attribute_list = array();
                foreach ($list as $key => $value) {
                    $attribute_list[$value['pigcms_id']] = $value['name'];
                }
                $list = $attribute_list;
            }
        }
        return $list;
    }

    /**
     * 条件获取单个用户数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function getHouseUserDataList($where,$field =true,$order='sort DESC, acid ASC') {
        // 初始化 数据层
        $db_house_village_data_config = new HouseVillageDataConfig();
        $list = $db_house_village_data_config->getList($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * 添加小区绑定
     * @author: wanziyang
     * @date_time: 2020/5/9 11:45
     * @param array $data
     * @return array|null|\think\Model
     */
    public function addUserBindOne($data) {
        // 初始化 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $pigcms_id = $db_house_village_user_bind->addOne($data);
        return $pigcms_id;
    }

    /**
     * 编辑小区绑定
     * @author: wanziyang
     * @date_time: 2020/5/11 16:29
     * @param array $where
     * @param array $data
     * @return array|null|\think\Model
     */
    public function saveUserBindOne($where,$data) {
        // 初始化 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $pigcms_id = $db_house_village_user_bind->saveOne($where,$data);
        return $pigcms_id;
    }

    /**
     * 删除小区绑定用户
     * @author: wanziyang
     * @date_time: 2020/5/12 14:58
     * @param array $where
     * @return bool
     */
    public function delUserBindOne($where) {
        // 初始化 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $del = $db_house_village_user_bind->delOne($where);
        return $del;
    }

    /**
     * 修改房屋信息
     * @author: wanziyang
     * @date_time: 2020/5/9 12:00
     * @param array $where 改写条件
     * @param array $save 改写内容
     * @return array|null|\think\Model
     */
    public function saveHouseRoom($where, $save) {
        // 初始化 物业管理员 数据层
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $set = $db_house_village_user_vacancy->saveOne($where, $save);
        return $set;
    }


    /**
     * 获取解绑信息及相关信息
     * @author: wanziyang
     * @date_time: 2020/5/13 16:56
     * @param array $where
     * @param int $village_id 小区id
     * @param bool|string $field
     * @return bool
     */
    public function getUnbindUser($where,$village_id,$field=true) {
        // 初始化 数据层
        $db_house_village_user_unbind = new HouseVillageUserUnbind();
        $info = $db_house_village_user_unbind->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {
            $service_house_village = new HouseVillageService();
            $db_house_village_floor = new HouseVillageFloor();
            $db_house_village_single = new HouseVillageSingle();
            $db_house_village_user_bind = new HouseVillageUserBind();
            $service_area = new AreaService();
            $floor_info = $db_house_village_floor->getOne(['floor_id' => $info['floor_id']],'floor_id,floor_name,floor_layer,floor_type,single_id');
            $single_id = $info['single_id'] ? $info['single_id'] : $floor_info['single_id'];
            $single_info = $db_house_village_single->getOne(['id' => $single_id],'single_name');
            $info['floor_name'] = $floor_info['floor_name'];
            $info['floor_layer'] = $floor_info['floor_layer'];
            $info['single_name'] = $single_info['single_name'];

            $word_data = array();
            $sing_name = $single_info['single_name'] ? $single_info['single_name'] : $floor_info['floor_layer'];
            $word_data['floor_name'] = $floor_info['floor_name'];
            $word_data['layer'] = $info['layer'];
            $word_data['room'] = $info['room'];
            $word_data['single_name'] = $sing_name;
            $room_diy_name = $service_house_village->word_replce_msg($word_data,$village_id);
            $info['address'] = $room_diy_name;
            // 获取绑定用户业主资料
            //检测用户是否已存在
            $where = [];
            $where[] = ['usernum','=',$info['usernum']];
            $where[] = ['type','in','0,3'];
            $field = 'pigcms_id,village_id,uid,usernum,name,phone,type,id_card,ic_card,attribute,position_id,park_flag,authentication_field';
            $bind_info = $db_house_village_user_bind->getOne($where,$field);
            if ($bind_info) {
                $info['bind_id'] = $bind_info['pigcms_id'];
            } else {
                $info['bind_id'] = 0;
            }

            if ($bind_info && $bind_info['attribute']>0) {
                $where_attribute = [];
                $where_attribute[] = ['pigcms_id','=',$bind_info['attribute']];
                $attribute_info = $this->getHouseUserAttributeWhere($where_attribute,'name');
                $bind_info['attribute_name'] = $attribute_info['name'];
            }
            if($bind_info && $bind_info['authentication_field']){
                $bind_info['authentication_field'] = unserialize($bind_info['authentication_field']);
            }

            $user = [];
            if ($info['name'] || $bind_info['name']) {
                $user[] = [
                    'title' => '姓名',
                    'content' => $bind_info['name']?$bind_info['name']:$info['name'],
                    'is_phone' => 0
                ];
            }
            if ($info['phone'] || $bind_info['phone']) {
                $user[] = [
                    'title' => '手机号',
                    'content' => $bind_info['phone']?$bind_info['phone']:$info['phone'],
                    'is_phone' => 1
                ];
            }
            $user[] = [
                'title' => '身份证号',
                'content' => $bind_info['id_card'] ? $bind_info['id_card'] : '无',
                'is_phone' => 0
            ];
            $user[] = [
                'title' => 'IC卡号',
                'content' => $bind_info['ic_card'] ? $bind_info['ic_card'] : '无',
                'is_phone' => 0
            ];
            $user[] = [
                'title' => '属性',
                'content' => $bind_info['attribute_name'] ? $bind_info['attribute_name'] : '无',
                'is_phone' => 0
            ];
            $user[] = [
                'title' => '车位信息',
                'content' => '无',
                'is_phone' => 0
            ];
            $user[] = [
                'title' => '车辆信息',
                'content' => '无',
                'is_phone' => 0
            ];
            if ($info['address'] || $bind_info['address']) {
                $user[] = [
                    'title' => '家庭住址',
                    'content' => $info['address'] ? $info['address'] : $bind_info['address'],
                    'is_phone' => 0
                ];
            }

            $dbHouseVillage = new HouseVillage();
            $village_field = 'property_id';
            $village_info = $dbHouseVillage->getOne($village_id,$village_field);
            $where_data = [];
            if (isset($village_info['property_id']) && $village_info['property_id']) {
                $where_data[] = ['village_id','=',0];
                $where_data[] = ['property_id','=',$village_info['property_id']];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $this->getHouseUserDataList($where_data);
            } else {
                $where_data[] = ['village_id','=',$village_id];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $this->getHouseUserDataList($where_data);
            }

            if ($data) {
                foreach($data as &$v){
                    unset($v['system_value']);
                    unset($v['sort']);
                    unset($v['add_time']);
//                if($v['type'] == 2){
//                    if($v['is_system'] == 1){
//                        $v['use_field'] = $service_house_village->get_system_value($v['key']);
//                    }else{
//                        $v['use_field'] = explode(',',$v['use_field']);
//                    }
//                }elseif($v['type'] == 3){
//
//                    $v['use_field'] = [];
//                }else{
//                    $v['use_field'] = [];
//                }

                    if($v['type'] == 3){
                        if(isset($bind_info['authentication_field'][$v['key']]['value'])){
                            $v['value'] = $bind_info['authentication_field'][$v['key']]['value'];
                            $city = explode("#",$bind_info['authentication_field'][$v['key']]['value']);
                            $provice_info = [];
                            $city_info = [];
                            $value = '';
                            if ($city[0] && $city[0]>0) {
                                $provice_info = $service_area->getAreaOne(['area_id'=>$city[0],'is_open'=>1],'area_name');
                            }
                            if ($city[1] && $city[1]>0) {
                                $city_info = $service_area->getAreaOne(['area_id'=>$city[1],'is_open'=>1],'area_name');
                            }
                            if($provice_info) {
                                $value = $provice_info['area_name'];
                            }
                            if($city_info && $value) {
                                $value .= ' '.$city_info['area_name'];
                            } elseif($city_info) {
                                $value =$city_info['area_name'];
                            }
                            if ($value) {
                                $v['value'] = $value;
                            }
                            $v['province_idss'] = $city[0];
                            $v['city_idss'] = $city[1];
                        }else{
                            $v['value'] = '';
                            $v['province_idss'] = '';
                            $v['city_idss'] = '';
                        }
                    }else{
                        $v['value'] = isset($bind_info['authentication_field'][$v['key']]['value'])?$bind_info['authentication_field'][$v['key']]['value']:'';
                    }

                    unset($v['use_field']);
                    unset($v['key']);
                    unset($v['is_display']);
                    unset($v['is_must']);
                    unset($v['is_close']);
                    unset($v['is_system']);
                    unset($v['type']);
                }
            }
            unset($bind_info['authentication_field']);
            $info['user'] = $user;
            $info['dataList'] = $data;
        }
        return $info;
    }

    /**
     * 条件获取小区用户及相关信息
     * @author: wanziyang
     * @date_time: 2020/5/13 16:32
     * @param array $where 条件
     * @param int $village_id 小区id
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function getHouseUserWhere($where,$village_id,$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $info = $db_house_village_user_bind->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {
            $service_house_village = new HouseVillageService();
            $db_house_village_floor = new HouseVillageFloor();
            $db_house_village_single = new HouseVillageSingle();
            $service_area = new AreaService();
            $floor_info = $db_house_village_floor->getOne(['floor_id' => $info['floor_id']],'floor_id,floor_name,floor_layer,floor_type,single_id');
            $single_id = $info['single_id'] ? $info['single_id'] : $floor_info['single_id'];
            $single_info = $db_house_village_single->getOne(['id' => $single_id],'single_name');
            $info['floor_name'] = $floor_info['floor_name'];
            $info['floor_layer'] = $floor_info['floor_layer'];
            $info['single_name'] = $single_info['single_name'];
            $word_data = array();
            $sing_name = $single_info['single_name'] ? $single_info['single_name'] : $floor_info['floor_layer'];
            $word_data['floor_name'] = $floor_info['floor_name'];
            $word_data['layer'] = $info['layer'];
            $word_data['room'] = $info['room'];
            $word_data['single_name'] = $sing_name;
            $room_diy_name = $service_house_village->word_replce_msg($word_data,$village_id);
            $info['address'] = $room_diy_name;
            if (isset($info['bind_number']) && $info['bind_number']) {
                $info['usernum'] = $info['bind_number'];
            }

            if ($info && $info['attribute']>0) {
                $where_attribute = [];
                $where_attribute[] = ['pigcms_id','=',$info['attribute']];
                $attribute_info = $this->getHouseUserAttributeWhere($where_attribute,'name');
                $bind_info['attribute_name'] = $attribute_info['name'];
            }
            if($info && $info['authentication_field']){
                $info['authentication_field'] = unserialize($info['authentication_field']);
            }

            $user = [];
            if ($info['name']) {
                $user[] = [
                    'title' => '姓名',
                    'content' => $info['name'],
                    'is_phone' => 0
                ];
            }
            if ($info['phone']) {
                $user[] = [
                    'title' => '手机号',
                    'content' => $info['phone'],
                    'is_phone' => 1
                ];
            }
            $user[] = [
                'title' => '身份证号',
                'content' => $info['id_card'] ? $info['id_card'] : '无',
                'is_phone' => 0
            ];
            $user[] = [
                'title' => 'IC卡号',
                'content' => $info['ic_card'] ? $info['ic_card'] : '无',
                'is_phone' => 0
            ];
            $user[] = [
                'title' => '属性',
                'content' => $info['attribute_name'] ? $info['attribute_name'] : '无',
                'is_phone' => 0
            ];
            $user[] = [
                'title' => '车位信息',
                'content' => '无'
            ];
            $user[] = [
                'title' => '车辆信息',
                'content' => '无',
                'is_phone' => 0
            ];
            if ($info['address']) {
                $user[] = [
                    'title' => '家庭住址',
                    'content' => $info['address'],
                    'is_phone' => 0
                ];
            }

            $dbHouseVillage = new HouseVillage();
            $village_field = 'property_id';
            $village_info = $dbHouseVillage->getOne($village_id,$village_field);
            $where_data = [];
            if (isset($village_info['property_id']) && $village_info['property_id']) {
                $where_data[] = ['village_id','=',0];
                $where_data[] = ['property_id','=',$village_info['property_id']];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $this->getHouseUserDataList($where_data);
            } else {
                $where_data[] = ['village_id','=',$village_id];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $this->getHouseUserDataList($where_data);
            }
            if ($data) {
                foreach($data as &$v){
                    unset($v['system_value']);
                    unset($v['sort']);
                    unset($v['add_time']);
//                if($v['type'] == 2){
//                    if($v['is_system'] == 1){
//                        $v['use_field'] = $service_house_village->get_system_value($v['key']);
//                    }else{
//                        $v['use_field'] = explode(',',$v['use_field']);
//                    }
//                }elseif($v['type'] == 3){
//
//                    $v['use_field'] = [];
//                }else{
//                    $v['use_field'] = [];
//                }

                    if($v['type'] == 3){
                        if(isset($info['authentication_field'][$v['key']]['value'])){
                            $v['value'] = $info['authentication_field'][$v['key']]['value'];
                            $city = explode("#",$info['authentication_field'][$v['key']]['value']);
                            $provice_info = [];
                            $city_info = [];
                            $value = '';
                            if ($city[0] && $city[0]>0) {
                                $provice_info = $service_area->getAreaOne(['area_id'=>$city[0],'is_open'=>1],'area_name');
                            }
                            if (isset($city[1]) && $city[1]>0) {
                                $city_info = $service_area->getAreaOne(['area_id'=>$city[1],'is_open'=>1],'area_name');
                            }
                            if($provice_info) {
                                $value = $provice_info['area_name'];
                            }
                            if($city_info && $value) {
                                $value .= ' '.$city_info['area_name'];
                            } elseif($city_info) {
                                $value =$city_info['area_name'];
                            }
                            if ($value) {
                                $v['value'] = $value;
                            }
                            $v['province_idss'] = $city[0];
                            $v['city_idss'] = isset($city[1])?$city[1]:'';
                        }else{
                            $v['value'] = '';
                            $v['province_idss'] = '';
                            $v['city_idss'] = '';
                        }
                    }else{
                        $v['value'] = '';
                        if ($info['authentication_field'] && $v['key'] && isset($info['authentication_field'][$v['key']]) && isset($info['authentication_field'][$v['key']]['value'])) {
                            $v['value'] = $info['authentication_field'][$v['key']]['value'];
                        }
                    }

                    unset($v['use_field']);
                    unset($v['key']);
                    unset($v['is_display']);
                    unset($v['is_must']);
                    unset($v['is_close']);
                    unset($v['is_system']);
                    unset($v['type']);
                }
            }
            unset($info['authentication_field']);
            $info['user'] = $user;
            $info['dataList'] = $data;
        }
        return $info;
    }

    /**
     * 获取房间及相关信息
     * @author: wanziyang
     * @date_time: 2020/5/13 15:09
     * @param array $where 查询条件
     * @param int $village_id 小区id
     * @param bool $is_edit 是否为编辑信息
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getRoomUserWhere($where,$village_id,$is_edit=false,$field = true,$bind_id=0, $status=1) {
        // 初始化 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        if ($bind_id) {
            $where_bind = [];
            $where_bind[] = ['pigcms_id','=',$bind_id];
            $bind_field = 'pigcms_id,village_id,uid,usernum,bind_number,name,phone,type,id_card,ic_card,attribute,position_id,park_flag,vacancy_id,authentication_field';
            $bind_info = $db_house_village_user_bind->getOne($where_bind,$bind_field);
            $where_room = [];
            $where_room[] = ['pigcms_id','=',$bind_info['vacancy_id']];
            $info = $db_house_village_user_vacancy->getOne($where_room,$field);

            if (isset($bind_info['bind_number']) && $bind_info['bind_number'] && $info) {
                $info['usernum'] = $bind_info['bind_number'];
            }
        } else {
            $info = $db_house_village_user_vacancy->getOne($where,$field);
            if (isset($info['property_number']) && $info['property_number'] ) {
                $info['usernum'] = $info['property_number'];
            }
        }
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {
            $info['bind_id'] = $bind_info['pigcms_id'];
            $info['room_id'] = $info['pigcms_id'];
            $service_house_village = new HouseVillageService();
            $db_house_village_floor = new HouseVillageFloor();
            $db_house_village_single = new HouseVillageSingle();
            $service_area = new AreaService();
            if (isset($info['floor_id']) && $info['floor_id']) {
                $floor_info = $db_house_village_floor->getOne(['floor_id' => $info['floor_id']],'floor_id,floor_name,floor_layer,floor_type,single_id');
            }
            if (isset($info['single_id']) && $info['single_id']) {
                $single_id = $info['single_id'];
            } elseif (isset($floor_info['single_id']) && $floor_info['single_id']) {
                $single_id = $floor_info['single_id'];
            }
            if ($single_id) {
                $single_info = $db_house_village_single->getOne(['id' => $single_id],'single_name');
            }
            $info['floor_name'] = isset($floor_info['floor_name']) ? $floor_info['floor_name'] : '';
            $info['floor_layer'] = isset($floor_info['floor_layer']) ? $floor_info['floor_layer'] : '';
            $info['single_name'] = isset($single_info['single_name']) ? $single_info['single_name'] : '';

            $word_data = array();
            $sing_name = $single_info['single_name'] ? $single_info['single_name'] : $floor_info['floor_layer'];
            $word_data['floor_name'] = $floor_info['floor_name'];
            $word_data['layer'] = $info['layer'];
            $word_data['room'] = $info['room'];
            $word_data['single_name'] = $sing_name;
            $room_diy_name = $service_house_village->word_replce_msg($word_data,$village_id);
            $info['address'] = $room_diy_name;

            // 获取绑定用户业主资料
            //检测用户是否已存在
            if (!$bind_info) {
                $where = [];
                $where[] = ['vacancy_id','=',$info['vacancy_id']];
                $where[] = ['type','in','0,3'];
                $where[] = ['status','=',$status];
                $bind_field = 'pigcms_id,village_id,uid,usernum,name,phone,type,id_card,ic_card,attribute,position_id,park_flag,authentication_field';
                $bind_info = $db_house_village_user_bind->getOne($where,$bind_field);
                if ($bind_info) {
                    $info['bind_id'] = $bind_info['pigcms_id'];
                } else {
                    $info['bind_id'] = 0;
                }
            }

            if ($bind_info && $bind_info['attribute']>0) {
                $where_attribute = [];
                $where_attribute[] = ['pigcms_id','=',$bind_info['attribute']];
                $attribute_info = $this->getHouseUserAttributeWhere($where_attribute,'name');
                $bind_info['attribute_name'] = $attribute_info['name'];
            }
            if($bind_info && $bind_info['authentication_field']){
                $bind_info['authentication_field'] = unserialize($bind_info['authentication_field']);
                if (!$bind_info['authentication_field']) {
                    $bind_info['authentication_field'] = [];
                }
            }
            if ($bind_info['name']) {
                $info['name'] = $bind_info['name'];
            }
            if ($bind_info['phone']) {
                $info['phone'] = $bind_info['phone'];
            }
            if ($bind_info['uid']) {
                $info['uid'] = $bind_info['uid'];
            }

            $user = [];
            if ($info['name'] || $bind_info['name']) {
                $user[] = [
                    'title' => '姓名',
                    'content' => $bind_info['name']?$bind_info['name']:$info['name'],
                    'is_phone' =>0
                ];
            }
            if ($info['phone'] || $bind_info['phone']) {
                $user[] = [
                    'title' => '手机号',
                    'content' => $bind_info['phone']?$bind_info['phone']:$info['phone'],
                    'is_phone' =>1
                ];
            }
            $user[] = [
                'title' => '身份证号',
                'content' => $bind_info['id_card'] ? $bind_info['id_card'] : '无',
                'is_phone' =>0
            ];
            $user[] = [
                'title' => 'IC卡号',
                'content' => $bind_info['ic_card'] ? $bind_info['ic_card'] : '无',
                'is_phone' =>0
            ];
            $user[] = [
                'title' => '属性',
                'content' => $bind_info['attribute_name'] ? $bind_info['attribute_name'] : '无',
                'is_phone' =>0
            ];
            $user[] = [
                'title' => '车位信息',
                'content' => '无',
                'is_phone' =>0
            ];
            $user[] = [
                'title' => '车辆信息',
                'content' => '无',
                'is_phone' =>0
            ];
            if ($info['address'] || $bind_info['address']) {
                $user[] = [
                    'title' => '家庭住址',
                    'content' => $info['address'] ? $info['address'] : $bind_info['address'],
                    'is_phone' =>0
                ];
            }

            $dbHouseVillage = new HouseVillage();
            $village_field = 'property_id';
            $village_info = $dbHouseVillage->getOne($village_id,$village_field);
            $where_data = [];
            if (isset($village_info['property_id']) && $village_info['property_id']) {
                $where_data[] = ['village_id','=',0];
                $where_data[] = ['property_id','=',$village_info['property_id']];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $this->getHouseUserDataList($where_data);
            } else {
                $where_data[] = ['village_id','=',$village_id];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $this->getHouseUserDataList($where_data);
            }

            if ($data) {
                foreach($data as &$v){
                    unset($v['system_value']);
                    unset($v['sort']);
                    unset($v['add_time']);
                    if ($is_edit) {
                        if($v['type'] == 2){
                            if($v['is_system'] == 1){
                                $v['use_field'] = $service_house_village->get_system_value($v['key']);
                            }else{
                                $v['use_field'] = explode(',',$v['use_field']);
                            }
                        }elseif($v['type'] == 3){

                            $v['use_field'] = [];
                        }else{
                            $v['use_field'] = [];
                        }
                    }

                    if($v['type'] == 3){
                        if(isset($bind_info['authentication_field'][$v['key']]['value'])){
                            $v['value'] = $bind_info['authentication_field'][$v['key']]['value'];
                            $city = explode("#",$bind_info['authentication_field'][$v['key']]['value']);
                            $provice_info = [];
                            $city_info = [];
                            $value = '';
                            if ($city[0] && $city[0]>0) {
                                $provice_info = $service_area->getAreaOne(['area_id'=>$city[0],'is_open'=>1],'area_name');
                            }
                            if ($city[1] && $city[1]>0) {
                                $city_info = $service_area->getAreaOne(['area_id'=>$city[1],'is_open'=>1],'area_name');
                            }
                            if($provice_info) {
                                $value = $provice_info['area_name'];
                            }
                            if($city_info && $value) {
                                $value .= ' '.$city_info['area_name'];
                            } elseif($city_info) {
                                $value =$city_info['area_name'];
                            }
                            if ($value) {
                                $v['value'] = $value;
                            }
                            $v['province_idss'] = $city[0];
                            $v['city_idss'] = $city[1];
                        }else{
                            $v['value'] = '';
                            $v['province_idss'] = '';
                            $v['city_idss'] = '';
                        }
                    }else{
                        $v['value'] = isset($bind_info['authentication_field'][$v['key']]['value'])?$bind_info['authentication_field'][$v['key']]['value']:'';
                    }

                    if (!$is_edit) {
                        unset($v['use_field']);
                        unset($v['key']);
                        unset($v['is_display']);
                        unset($v['is_must']);
                        unset($v['is_close']);
                        unset($v['is_system']);
                        unset($v['type']);
                        unset($v['village_id']);
                    } else {
                        unset($v['is_close']);
                        unset($v['is_display']);
                        unset($v['village_id']);
                        unset($v['acid']);
                    }
                }
            }
            unset($bind_info['authentication_field']);
            $info['user'] = $user;
            $info['dataList'] = $data;
        }
        return $info;
    }

    /**
     * 获取小区绑定用户信息
     * @author:wanziyang
     * @date_time: 2020/5/28 11:41
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getLimitUserList($where,$page=0,$field =true,$order='pigcms_id ASC',$page_size=10) {
        // 初始化 数据层
        $db_house_village_user_bind = new HouseVillageUserBind();
        $list = $db_house_village_user_bind->getLimitUserList($where,$page,$field,$order,$page_size);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }

    /**
     * 搜获符合条件的社区用户
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @param $whereOr
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserListsByUserPhoneOrName($where,$whereOr,$field=true,$page=1,$limit=15)
    {
        $house_village_user_bind = new HouseVillageUserBind();
        $service_house_village = new HouseVillageService();
        $user_lists = $house_village_user_bind->getUserLists($where,$whereOr,$field,$page,$limit);
        if($user_lists){
            foreach ($user_lists as &$v){
                $v['address'] = $service_house_village->getSingleFloorRoom($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
            }
        }
        return $user_lists;
    }

    /**
     * Notes: 获取小区用户数据
     * @param $where
     * @param bool $field
     * @return array
     * @datetime: 2020/12/5 17:07
     */
    public function getUserInfos($where,$field=true){
        $house_village_user_bind = new HouseVillageUserBind();
        $user_info = $house_village_user_bind->getSumBills($where,$field);
        return $user_info;
    }

    /**
     * 编辑小区绑定
     * @author: wanziyang
     * @date_time: 2020/5/11 16:29
     * @param array $where
     * @param array $data
     * @return array|null|\think\Model
     */
    public function add_third_log($data) {
        // 初始化 数据层
        $db_house_village_user_bind = new ProcessSubPlan();
        $pigcms_id = $db_house_village_user_bind->add($data);
        return $pigcms_id;
    }


    public function visitor_bind_user($village_id,$phone,$name,$address_arr,$visitParams = [])
    {
        $db_house_village_user_bind = new HouseVillageUserBind();
        $server_common_user = new CommonUserService();
        $db_user = new User();
        $whereUser = [];
        $whereUser[] = ['phone', '=', $phone];
        $userInfo = $db_user->getOne($whereUser, 'uid,phone');
        $userInfo = $userInfo && !is_array($userInfo) ? $userInfo->toArray() : $userInfo;
        if(empty($userInfo))
        {
            $data_user = array(
                'nickname' 	=> $name,
                'phone' 	=> $phone,
                'source' 	=> 'houseautoreg',
            );
            try {
                $reg_result = $server_common_user->autoreg($data_user,false,true);
                if (isset($reg_result['uid']) && intval($reg_result['uid']) > 0) {
                    $uid = $reg_result['uid'];
                }
            } catch (\Exception $e) {
                fdump_api(['人员注册失败'.__LINE__,$userInfo,$data_user,$e->getMessage()],'autoreg/errLog',1);
            }
        }else{
            $uid = $userInfo['uid'];
        }
        $now_time = time();
        $arr = [
            'village_id' => $village_id,
            'uid' => $uid,
            'phone' => $phone,
            'name' => $name,
            'type' => 6,
            'usernum' => rand(1000, 9999) . '-' . $now_time,
            'single_id' => $address_arr['single_id'],
            'floor_id' => $address_arr['floor_id'],
            'layer_id' => $address_arr['layer_id'],
            'vacancy_id' => $address_arr['vacancy_id'],
            'add_time' => $now_time,
        ];
        $visitTime = isset($visitParams['visitTime']) && $visitParams['visitTime'] ? $visitParams['visitTime'] : '';
        if ($visitTime > 0) {
            $arr['property_starttime'] = strtotime(date('Y-m-d 00:00:00', $visitTime));
            $arr['property_endtime'] = strtotime(date('Y-m-d 23:59:59', $visitTime));
        }
        $pigcms_id = $db_house_village_user_bind->addOne($arr);
        
        $data['uid'] = $uid;
        $data['bind_id'] = $pigcms_id;
        return $data;
    }
}