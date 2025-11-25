<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/14 13:45
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;

use app\community\model\service\DoorService;
use app\community\model\service\HouseVillageService;

class BluetoothDoorController extends BaseController{

    /**
     * 获取门禁列表
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'page'=> '查询页数 必传',
     *  'floor_id'=> '单元id 选传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/14 14:52
     * @return \json
     */
    public function getDoorList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $page = $this->request->param('page','1','intval');
        $page_size = $this->request->param('page_size','10','intval');
        $floor_id = $this->request->param('floor_id','','intval');
        $door_name = $this->request->param('door_name','','trim');

        $where = [];
        $where[] = ['a.village_id','=',$village_id];
        if ($floor_id>0) {
            $where[] = ['a.floor_id','=',$floor_id];
        }
        if ($door_name) {
            $where[] = ['a.door_name','LIKE','%'.$door_name.'%'];
        }
        $service_door = new DoorService();
        $field ='a.*,hvf.floor_name,hvf.floor_layer,hvs.id as single_id,hvs.single_name,c.public_area_name';
        $order='a.door_id ASC';
        $list	=	$service_door->getHouseVillageDoorList($where,$page,$field,$order,$page_size);
        $out = [];
        $out['list'] = $list;
        return api_output(0,$out);
    }

    /**
     * 获取门禁可关联楼栋和公共区域
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/14 15:16
     * @return \json
     */
    public function getSingleList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $service_house_village = new HouseVillageService();
        $list = [];
        $where_public = [];
        $where_public[] = ['village_id', '=', $village_id];
        $where_public[] = ['status', '=', 1];
        $public_area = $service_house_village->getHouseVillagePublicAreaList($where_public,'public_area_id');
        if ($public_area) {
            $data['is_public'] = 1;
            $data['single_id'] = 'public_area';
            $data['single_name'] = '公共区域';
            $list[] = $data;
        }
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];
        $list_single = $service_house_village->getSingleList($where,'id as single_id,single_name','sort desc, id asc');
        foreach($list_single as $val) {
            $val['is_public'] = 0;
            $list[] = $val;
        }
        $out = [];
        $out['list'] = $list;
        return api_output(0,$out);
    }

    /**
     * 获取门禁可关联楼栋和公共区域
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'single_id'=> '楼栋id 或者public_area（公共区域标识） ',
     *  'is_public'=> '是否为公共区域数据 1 是',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/14 15:16
     * @return \json
     */
    public function getFloorList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $is_public = $this->request->param('is_public','','intval');
        $single_id = $this->request->param('single_id','','intval');
        $service_house_village = new HouseVillageService();
        $list = [];
        if ($is_public || $single_id=='public_area') {
            $where_public = [];
            $where_public[] = ['village_id', '=', $village_id];
            $where_public[] = ['status', '=', 1];
            $public_area = $service_house_village->getHouseVillagePublicAreaList($where_public,'public_area_id,public_area_name');
            if ($public_area) {
                foreach($public_area as $val) {
                    $val['is_public'] = 1;
                    $val['floor_id'] = $val['public_area_id'];
                    $val['floor_name'] = $val['public_area_name'];
                    $list[] = $val;
                }
            }
        } else {
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['single_id', '=', $single_id];
            $where[] = ['status', '=', 1];
            $list_single = $service_house_village->getFloorList($where,'floor_id,floor_name','sort desc, floor_id asc');
            foreach($list_single as $val) {
                $val['is_public'] = 0;
                $list[] = $val;
            }
        }
        $out = [];
        $out['list'] = $list;
        return api_output(0,$out);
    }


    /**
     * 添加
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'door_device_id'=> '设备ID 必传',
     *  'door_service_uuid' => '设备UUID  选传'.
     *  'door_characteristic_uuid' => '设备特征值 选传'.
     *  'is_public'=> '是否为公共区域数据 1 是 0 否 必传',
     *  'floor_id'=> '单元id 或者公共区域id 以is_public 区分 必传',
     *  'door_name'=> '门禁名称 必传',
     *  'door_status'=> '使用状态 1全部使用 2获取权限 必传',
     *  'door_psword'=> '门禁密码 选传',
     *  'all_status'=> '使用状态 1全部使用 2获取权限 选传',
     *  'open_time'=> '开门时长 选传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/14 15:16
     * @return \json
     */
    public function addDoor() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(259,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        // 设备id
        $door_device_id =  $this->request->param('door_device_id','','trim');
        if (empty($door_device_id)) {
            return api_output(1001,[],'缺少设备id！');
        }
        $is_public = $this->request->param('is_public','','intval');
        $floor_id = $this->request->param('floor_id','','intval');
        $door_name = $this->request->param('door_name','','trim');
        if (empty($door_name)) {
            return api_output(1001,[],'缺少门禁名称！');
        }
        $door_psword = $this->request->param('door_psword','','trim');
        // 状态 1启用 0未用
        $door_status = $this->request->param('door_status','1','intval');
        // 使用状态 1全部使用 2获取权限
        $all_status = $this->request->param('all_status','1','intval');
        // 开门时长
        $open_time = $this->request->param('open_time','','intval');
        // 设备UUID
        $door_service_uuid = $this->request->param('door_service_uuid','','trim');
        // 设备特征值
        $door_characteristic_uuid = $this->request->param('door_characteristic_uuid','','trim');

        $service_house_village = new HouseVillageService();
        $service_door = new DoorService();

        //小区信息
        $now_village = $service_house_village->getHouseVillage($village_id,'door_pwd');
        if ($now_village['door_pwd'] && !$door_psword) {
            $door_pwd = str_replace(',','',$now_village['door_pwd']);
            if ($door_pwd) {
                $door_psword = $door_pwd;
            } else {
                $door_psword = 123456;
            }
        }
        $data = [];
        $data['village_id'] = $village_id;
        $data['door_device_id'] = $door_device_id;
        if ($door_service_uuid) {
            $data['door_service_uuid'] = $door_service_uuid;
        }
        if ($door_characteristic_uuid) {
            $data['door_characteristic_uuid'] = $door_characteristic_uuid;
        }
        $data['door_name'] = $door_name;
        $data['door_psword'] = $door_psword;
        $data['door_status'] = $door_status;
        $data['all_status'] = $all_status;
        if ($is_public) {
            $data['floor_id'] = -1;
            $data['public_area_id'] = $floor_id;
            $where_public = [];
            $where_public[] = ['public_area_id', '=', $floor_id];
            $public_area = $service_house_village->getHouseVillagePublicAreaList($where_public,'public_area_id,lng,lat');
            if (empty($public_area)) {
                return api_output(1003,[],'对应公共区域不存在！');
            }
            $data['lng'] = $public_area['lng'] ? $public_area['lng'] : '';
            $data['lat'] = $public_area['lat'] ? $public_area['lat'] : '';
        } else {
            $where_floor = [];
            $where_floor[] = ['floor_id','=',$floor_id];
            $where_floor[] = ['status','=',1];
            $floor_info = $service_house_village->getHouseVillageFloorWhere($where_floor,'floor_id,long,lat');
            if (empty($floor_info)) {
                return api_output(1003,[],'对应单元不存在！');
            }
            $data['floor_id'] = $floor_id;
            $data['lng'] = $floor_info['long'] ? $floor_info['long'] : '';
            $data['lat'] = $floor_info['lat'] ? $floor_info['lat'] : '';
        }
        if ($open_time) {
            $data['open_time'] = $open_time;
        }
        $data['add_time'] = time();
        $door_id = $service_door->addHouseVillageDoorOne($data);
        if ($door_id) {
            return api_output(0,['door_id'=>$door_id],'添加成功！');
        } else {
            return api_output(1003,[],'添加失败，请稍后重试！');
        }
    }


    /**
     * 获取详情
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'door_id'=> '门禁id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/14 15:16
     * @return \json
     */
    public function doorDetail() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(227,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $door_id = $this->request->param('door_id','','intval');
        if(empty($door_id)){
            return api_output(1001,[],'缺少门禁id！');
        }
        $service_door = new DoorService();
        $where_door = [];
        $where_door[] = ['door_id','=',$door_id];
        $door_info = $service_door->getHouseVillageDoorOne($where_door);
        if(empty($door_info)){
            return api_output(1001,[],'门禁不存在！');
        }
        if ((isset($door_info['public_area_id']) && $door_info['public_area_id']>0) || $door_info['floor_id']<0) {
            $door_info['is_public'] = 1;
        } else {
            $door_info['is_public'] = 0;
        }
        // 去除密码返回
        unset($door_info['door_psword']);

        return api_output(0,$door_info);
    }


    /**
     * 编辑
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'door_id'=> '门禁id 必传',
     *  'door_device_id'=> '设备ID 必传',
     *  'door_service_uuid' => '设备UUID  选传'.
     *  'door_characteristic_uuid' => '设备特征值 选传'.
     *  'is_public'=> '是否为公共区域数据 1 是 0 否 必传',
     *  'floor_id'=> '单元id 或者公共区域id 以is_public 区分 必传',
     *  'door_name'=> '门禁名称 必传',
     *  'door_status'=> '使用状态 1全部使用 2获取权限 必传',
     *  'door_psword'=> '门禁密码 选传',
     *  'all_status'=> '使用状态 1全部使用 2获取权限 选传',
     *  'open_time'=> '开门时长 选传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/14 15:16
     * @return \json
     */
    public function saveDoor() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(227,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $door_id = $this->request->param('door_id','','intval');
        if(empty($door_id)){
            return api_output(1001,[],'缺少门禁id！');
        }
        $service_door = new DoorService();
        $where_door = [];
        $where_door[] = ['door_id','=',$door_id];
        $door_info = $service_door->getHouseVillageDoorOne($where_door);

        $is_public = $this->request->param('is_public','','intval');
        $floor_id = $this->request->param('floor_id','','intval');
        $door_name = $this->request->param('door_name','','trim');
        $door_psword = $this->request->param('door_psword','','trim');
        // 状态 1启用 0未用
        $door_status = $this->request->param('door_status');
        // 使用状态 1全部使用 2获取权限
        $all_status = $this->request->param('all_status','1','intval');
        // 开门时长
        $open_time = $this->request->param('open_time','','intval');
        // 设备id
        $door_device_id =  $this->request->param('door_device_id','','trim');
        // 设备UUID
        $door_service_uuid = $this->request->param('door_service_uuid','','trim');
        // 设备特征值
        $door_characteristic_uuid = $this->request->param('door_characteristic_uuid','','trim');

        $service_house_village = new HouseVillageService();

        //小区信息
        $now_village = $service_house_village->getHouseVillage($village_id,'door_pwd');
        if ($now_village['door_pwd'] && !$door_psword) {
            $door_pwd = str_replace(',','',$now_village['door_pwd']);
            if ($door_pwd) {
                $door_psword = $door_pwd;
            } else {
                $door_psword = 123456;
            }
        }
        $data = [];
        if ($door_device_id && $door_info['door_device_id'] != $door_device_id) {
            $data['door_device_id'] = $door_device_id;
        }
        if ($door_service_uuid && $door_info['door_service_uuid'] != $door_service_uuid) {
            $data['door_service_uuid'] = $door_service_uuid;
        }
        if ($door_characteristic_uuid && $door_info['door_characteristic_uuid'] != $door_characteristic_uuid) {
            $data['door_characteristic_uuid'] = $door_characteristic_uuid;
        }
        if ($door_name && $door_info['door_name'] != $door_name) {
            $data['door_name'] = $door_name;
        }
        if ($door_psword && $door_info['door_psword'] != $door_psword) {
            $data['door_psword'] = $door_psword;
        }
        if ($door_status && $door_info['door_status'] != $door_status) {
            $data['door_status'] = intval($door_status);
        }
        if ($all_status && $door_info['all_status'] != $all_status) {
            $data['all_status'] = $all_status;
        }
        if ($is_public) {
            $data['floor_id'] = -1;
            if ($floor_id && $door_info['public_area_id'] != $floor_id) {
                $data['public_area_id'] = $floor_id;
            }
            $where_public = [];
            $where_public[] = ['public_area_id', '=', $floor_id];
            $where_public[] = ['status', '=', 1];
            $public_area = $service_house_village->getHouseVillagePublicAreaList($where_public,'public_area_id,lng,lat');
            if (empty($public_area)) {
                return api_output(1003,[],'对应公共区域不存在！');
            }
            if ($public_area['lng'] && $door_info['lng'] != $public_area['lng']) {
                $data['lng'] = $public_area['lng'];
            }
            if ($public_area['lat'] && $door_info['lat'] != $public_area['lat']) {
                $data['lat'] = $public_area['lat'];
            }
        } else {
            $where_floor = [];
            $where_floor[] = ['floor_id','=',$floor_id];
            $where_floor[] = ['status', '=', 1];
            $floor_info = $service_house_village->getHouseVillageFloorWhere($where_floor,'floor_id,long,lat');
            if (empty($floor_info)) {
                return api_output(1003,[],'对应单元不存在！');
            }
            if ($floor_id && $door_info['floor_id'] != $floor_id) {
                $data['floor_id'] = $floor_id;
            }
            if ($floor_info['long'] && $door_info['lng'] != $floor_info['long']) {
                $data['lng'] = $floor_info['long'];
            }
            if ($floor_info['lat'] && $door_info['lat'] != $floor_info['lat']) {
                $data['lat'] = $floor_info['lat'];
            }
        }
        if ($open_time && $door_info['open_time'] != $open_time) {
            $data['open_time'] = $open_time;
        }
        if (empty($data)) {
            return api_output(1003,[],'当前没有任何改变，请进行修改后再次重试！');
        }
        $door_id = $service_door->saveHouseVillageDoorOne($where_door,$data);
        if ($door_id) {
            return api_output(0,['door_id'=>$door_id],'修改成功！');
        } else {
            return api_output(1003,[],'修改失败，请确认进行了修改后再次重试！');
        }
    }


    /**
     * 删除门禁
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'door_id'=> '门禁id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/14 15:16
     * @return \json
     */
    public function delDoor() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(260,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        // 蓝牙门禁id
        $door_id =  $this->request->param('door_id','','trim');
        if (empty($door_id)) {
            return api_output(1001,[],'缺少门禁id！');
        }
        $where = [];
        $where[] = ['door_id','=',$door_id];
        $service_door = new DoorService();
        $del = $service_door->delHouseVillageDoorOne($where);
        if ($del) {
            return api_output(0,['door_id' => $door_id],'删除成功');
        } else {
            return api_output(1003,[],'删除失败，请稍后重试！');
        }
    }
}