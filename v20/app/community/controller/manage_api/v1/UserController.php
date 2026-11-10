<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/26 9:41
 */

namespace app\community\controller\manage_api\v1;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\manage_api\BaseController;

use app\community\model\service\HouseContactWayUserService;
use app\community\model\service\HouseUserTrajectoryLogService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\AreaService;
use app\community\model\service\UserService;
use app\community\model\service\ConfigService;
use app\community\model\service\HouseVillageSingleService;
use think\facade\Env;
require_once '../extend/phpqrcode/phpqrcode.php';

use think\facade\Log;
class UserController extends BaseController{

    /**
     * 获取对应小区楼栋信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/6 14:31
     * @return \json
     */
    public function villageSingleList() {
        // 获取登录信息
        /*$arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }*/
        $village_id = $this->request->param('village_id','','intval');
        $is_public_rental = $this->request->param('is_public_rental',0,'int');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name',$village_id)?:'小区').'！');
            }
        }
        $site_url = cfg('site_url');
        //小区信息
        $service_house_village = new HouseVillageService();
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,village_logo');
        $arr = [];
        $arr['village_id'] = $now_village['village_id'];
        $arr['village_name'] = $now_village['village_name'];
        $arr['village_address'] = $now_village['village_address'];
        $arr['village_logo'] = $now_village['village_logo'] ? $now_village['village_logo'] : $site_url.'/static/images/wap_house/village_logo.png';


        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];
        if($is_public_rental){
            $where[] = ['is_public_rental', '=', 1];
        }

        $list = $service_house_village->getSingleList($where,true,'sort desc, id asc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['village_id'] = $v['village_id'];
            if(preg_match('/[\x7f-\xff]/', $v['single_name'])) {
                $item['single_name'] = $v['single_name'];
            }else{
                $item['single_name'] = $v['single_name'].'号'.((new HouseVillageService())->getHouseAlias('single_name',$village_id)?:'楼');
            }
//            $item['single_name'] = $v['single_name'];
            $item['single_id'] = $v['id'];
            $dataList[] = $item;
        }
        /**** 
         *   $configCustomizationService=new ConfigCustomizationService();
            $grapeFruitOrder=$configCustomizationService->getHzhouGrapefruitOrderJudge();
         */
        $arr['grapeFruitOrder']=true;
        $arr['list'] = $dataList;
        return api_output(0,$arr);
    }

    /**
     * 获取对应小区楼栋下单元信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'single_id'=> '楼栋id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/6 15:23
     * @return \json
     */
    public function villageFloorList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        /*if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }*/
        $village_id = $this->request->param('village_id','','intval');
        $is_public_rental = $this->request->param('is_public_rental',0,'int');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name',$village_id)?:'小区').'！');
            }
        }
        $single_id = $this->request->param('single_id','','intval');
        if (empty($single_id)) {
            return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('single_name',$village_id)?:'楼栋').'！');
        }
        $site_url = cfg('site_url');
        //小区信息
        $service_house_village = new HouseVillageService();
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,village_logo');
        $arr = [];
        $arr['village_id'] = $now_village['village_id'];
        $arr['village_name'] = $now_village['village_name'];
        $arr['village_address'] = $now_village['village_address'];
        $arr['village_logo'] = $now_village['village_logo'] ? $now_village['village_logo'] : $site_url.'/static/images/wap_house/village_logo.png';


        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['single_id', '=', $single_id];
        $where[] = ['status', '=', 1];
        if($is_public_rental){
            $where[] = ['is_public_rental', '=', 1];
        }
        $list = $service_house_village->getFloorList($where,true,'sort desc, floor_id asc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['village_id'] = $v['village_id'];
            $item['floor_id'] = $v['floor_id'];
            if(preg_match('/[\x7f-\xff]/', $v['floor_name'])) {
                $item['floor_name'] = $v['floor_name'];
            }else{
                $item['floor_name'] = $v['floor_name'].'('.((new HouseVillageService())->getHouseAlias('floor_name',$village_id)?:'单元').')';
            }
//            $item['floor_name'] = $v['floor_name'];
            $item['floor_layer'] = $v['floor_layer'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        $app_type = $this->request->param('app_type','','trim');
        if ($app_type=='android' && !$dataList) {
            return api_output(1001,[],'该'.((new HouseVillageService())->getHouseAlias('single_name',$village_id)?:'楼栋').'下没有'.((new HouseVillageService())->getHouseAlias('floor_name',$village_id)?:'单元').'，请联系物业添加或选择其他'.((new HouseVillageService())->getHouseAlias('single_name',$village_id)?:'楼栋').'！');
        }
        return api_output(0,$arr);
    }

    /**
     * 获取对应小区楼栋单元下信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'floor_id'=> '单元id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/6/12 17:38
     * @return \json
     */
    public function villageLayerList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        /*if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }*/
        $village_id = $this->request->param('village_id','','intval');
        $is_public_rental = $this->request->param('is_public_rental',0,'int');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $floor_id = $this->request->param('floor_id','','intval');
        if (empty($floor_id)) {
            return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('floor_name')?:'单元').'！');
        }
        $service_house_village = new HouseVillageService();
        //小区信息
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,village_logo');
        // 单元信息
        $where_floor = [];
        $where_floor[] = ['floor_id', '=', $floor_id];
        $now_floor_info = $service_house_village->getHouseVillageFloorWhere($where_floor,'floor_name,floor_layer,single_id');
        // 楼栋信息
        $where_single = [];
        $where_single[] = ['id', '=', $now_floor_info['single_id']];
        $single_info = $service_house_village->get_house_village_single_where($where_single,'single_name');

        $arr = [
            'village_name'    =>  $now_village['village_name'],
            'village_address' =>  $now_village['village_address'],
            'single_name'      =>  $single_info['single_name'],
            'floor_name'      =>  $now_floor_info['floor_name'],
            'floor_layer'     =>  $now_floor_info['floor_layer'],
            'village_id'     =>  $now_village['village_id']
        ];

        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['floor_id', '=', $floor_id];
        $where[] = ['status', '=', 1];
        if($is_public_rental){
            $where[] = ['is_public_rental', '=', 1];
        }

        $list = $service_house_village->getHouseVillageLayerList($where,true,'sort desc,id desc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['layer_id'] = $v['id'];
            $item['single_id'] = $v['single_id'];
            $item['floor_id'] = $v['floor_id'];
            $item['village_id'] = $v['village_id'];
            if(preg_match('/[\x7f-\xff]/', $v['layer_name'])) {
                $item['layer'] = $v['layer_name'];
            }else{
                $item['layer'] = $v['layer_name'].'层';
            }
//            $item['layer'] = $v['layer_name'];
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        $app_type = $this->request->param('app_type','','trim');
        if ($app_type=='android' && !$dataList) {
            return api_output(1001,[],'该'.((new HouseVillageService())->getHouseAlias('floor_name')?:'单元').'下没有楼层，请联系物业添加或选择其他'.((new HouseVillageService())->getHouseAlias('floor_name')?:'单元').'！');
        }
        return api_output(0,$arr);
    }

    /**
     * 获取对应小区楼栋单元下房间信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'floor_id'=> '单元id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/6 15:35
     * @return \json
     */
    public function villageRoomList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        /*if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }*/
        $village_id = $this->request->param('village_id','','intval');
        $is_public_rental = $this->request->param('is_public_rental',0,'int');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $floor_id = $this->request->param('floor_id','','intval');
        $layer_id= $this->request->param('layer_id','','intval');
        if (empty($floor_id) && empty($layer_id)) {
            return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('floor_name')?:'单元').'！');
        }
        $service_house_village = new HouseVillageService();
        //小区信息
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,village_logo');
        // 单元信息
        $where_floor = [];
        // 层级信息
        $where_layer = [];
        if ($layer_id) {
            $where_layer[] = ['id', '=', $layer_id];
            $where_layer[] = ['status', '=', 1];
            $now_layer_info = $service_house_village->getHouseVillageLayerWhere($where_layer,'layer_name,single_id,floor_id');
            if (!$floor_id && $now_layer_info['floor_id']) {
                $floor_id = $now_layer_info['floor_id'];
            }
        }
        if ($floor_id) {
            $where_floor[] = ['floor_id', '=', $floor_id];
            $now_floor_info = $service_house_village->getHouseVillageFloorWhere($where_floor,'floor_name,floor_layer,single_id');
        }

        // 楼栋信息
        if (isset($now_floor_info)) {
            $single_id = $now_floor_info['single_id'];
        }
        if (isset($now_layer_info)) {
            $single_id = $now_layer_info['single_id'];
        }
        $where_single = [];
        $where_single[] = ['id', '=', $single_id];
        $single_info = $service_house_village->get_house_village_single_where($where_single,'single_name');

        $arr = [
            'village_name'    =>  $now_village['village_name'],
            'village_address' =>  $now_village['village_address'],
            'single_name'      =>  $single_info['single_name'],
            'floor_name'      =>  $now_floor_info ? $now_floor_info['floor_name'] : '',
            'floor_layer'     => $now_layer_info ? $now_layer_info['layer_name'] : $now_floor_info['floor_layer'],
            'village_id'     =>  $now_village['village_id']
        ];

        $where = [];
        $where[] = ['village_id', '=', $village_id];
        if ($layer_id) {
            $where[] = ['layer_id', '=', $layer_id];
        }
        if ($floor_id) {
            $where[] = ['floor_id', '=', $floor_id];
        }
        $where[] = ['is_del', '=', 0];
        if($is_public_rental){
            $where[] = ['is_public_rental', '=', 1];
        }
        $list = $service_house_village->getUserVacancyList($where,true,'sort desc, floor_id asc,pigcms_id asc');
        $dataList = [];
        foreach($list as $k=>$v){
            $item = [];
            $item['pigcms_id'] = $v['pigcms_id'];
            $item['room_id'] = $v['pigcms_id'];
            $item['floor_id'] = $v['floor_id'];
            $item['village_id'] = $v['village_id'];
            $item['layer'] = $v['layer'];
            $item['room'] = $v['room'];
            $item['sort'] = $v['sort'];
            $data = [
                'single_name' => $single_info['single_name'],
                'floor_name' => $now_floor_info['floor_name'],
                'layer' => $v['layer'],
                'room' => $v['room'],
            ];
            $item['room_info'] = $service_house_village->word_replce_msg($data, $village_id) ;
            $dataList[] = $item;
        }
        $arr['list'] = $dataList;
        $app_type = $this->request->param('app_type','','trim');
        if ($app_type=='android' && !$dataList) {
            return api_output(1001,[],'该楼层下没有房屋，请联系物业添加或选择其他房屋！');
        }
        return api_output(0,$arr);
    }

    /**
     * 获取对应房间信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'room_id'=> '房间id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/6 15:35
     * @return \json
     */
    public function villageRoomInfo() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name',$village_id)?:'小区').'！');
            }
        }
        $room_id = $this->request->param('room_id','','intval');
        if (empty($room_id)) {
            return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('room_name',$village_id)?:'房间').'！');
        }
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(array('village_id'=>$village_id),'*');
        $where_room = [];
        $where_room[] = ['village_id', '=', $village_id];
        $where_room[] = ['pigcms_id', '=', $room_id];
        $info = $service_house_village->getRoomInfoWhere($where_room,'pigcms_id,usernum,property_number,layer,room,housesize,house_type,user_status,sell_status,name,phone,single_id,layer_id,floor_id');
        $service_house_village_single = new HouseVillageSingleService();
        $house_type_arr = $service_house_village->house_type_arr;
        if (isset($info['house_type']) && $info['house_type']) {
            $info['house_type_txt'] = $house_type_arr[$info['house_type']];
        } else {
            $info['house_type_txt'] = '暂未补充';
        }
        $user_status_arr = $service_house_village->user_status_arr;
        if (isset($info['user_status']) && $info['user_status']) {
            $info['user_status_txt'] = $user_status_arr[$info['user_status']];
        } else {
            $info['user_status_txt'] = '暂未补充';
        }
        $sell_status_arr = $service_house_village->sell_status_arr;
        if (isset($info['sell_status']) && $info['sell_status']) {
            $info['sell_status_txt'] = $sell_status_arr[$info['sell_status']];
        } else {
            $info['sell_status_txt'] = '暂未补充';
        }
        if(isset($info['phone']) && !empty($info['phone'])){
            $info['phone']=$service_house_village->phoneDesensitizationV($info['phone'],$village_id,$village_info);
        }
        $info['single_name']='';
        if($info['single_id']>0){
            $house_single=$service_house_village_single->getSingleInfo(['id'=>$info['single_id']],'single_name');
            if(!empty($house_single)){
                $info['single_name']=$house_single['single_name'];
                if(is_numeric($info['single_name'])){
                    $info['single_name']=$info['single_name'].'('.((new HouseVillageService())->getHouseAlias('single_name',$village_id)?:'栋').')';
                }
            }
        }
        $info['floor_name']='';
        if($info['floor_id']>0){
            $house_single=$service_house_village_single->getFloorInfo(['floor_id'=>$info['floor_id']],'floor_name');
            if(!empty($house_single)){
                $info['floor_name']=$house_single['floor_name'];
                if(is_numeric($info['floor_name'])){
                    $info['floor_name']=$info['floor_name'].'('.((new HouseVillageService())->getHouseAlias('floor_name',$village_id)?:'单元').')';
                }
            }
        }
        $info['layer_name']='';
        if($info['layer_id']>0){
            $house_single=$service_house_village_single->getLayerInfo(['id'=>$info['layer_id']],'layer_name');
            if(!empty($house_single)){
                $info['layer_name']=$house_single['layer_name'];
                if(is_numeric($info['layer_name'])){
                    $info['layer_name']=$info['layer_name'].'(层)';
                }
            }
        }
        $where = [];
        $where[] = ['a.village_id', '=', $village_id];
        $where[] = ['a.pigcms_id', '=', $room_id];
        $where[] = ['b.status', '=', '1'];
        $where[] = ['b.uid|b.phone|b.name', 'not in', [0,'']];
        $where[] = ['b.type','in',[0,1,2,3]];
        $user_count = $service_house_village->getRoomUserNumber($where);
        $arr_info = [];
        $arr_info['user_count'] = $user_count;
        $arr_info['room_info'] = $info;

        return api_output(0,$arr_info);
    }

    /**
     * 获取对应房间成员信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'room_id'=> '房间id 必传',
     *  'search_val'=> '查询内容 选传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/6 17:31
     * @return \json
     */
    public function villageRoomUserInfo() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $room_id = $this->request->param('room_id','','intval');
        if (empty($room_id)) {
            return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('room_name')?:'房间').'！');
        }
        $service_house_village = new HouseVillageService();
        $where_room = [];
        $where_room[] = ['village_id', '=', $village_id];
        $where_room[] = ['pigcms_id', '=', $room_id];
        $info = $service_house_village->getRoomInfoWhere($where_room,'pigcms_id');
        if (empty($info)) {
            return api_output(1001,[],'对应'.((new HouseVillageService())->getHouseAlias('room_name')?:'房间').'信息不存在！');
        }

        $search_val = $this->request->param('search_val','','trim');

        $where = [];
        $where[] = ['a.village_id', '=', $village_id];
        $where[] = ['a.pigcms_id', '=', $room_id];
        $where[] = ['b.status', '=', '1'];
        $where[] = ['b.type', 'in', '0,1,2,3'];
        if ($search_val) {
            $where[] = ['b.name|b.phone|c.nickname','LIKE', '%'.$search_val.'%'];
        }
        $where[] = ['b.uid|b.phone|b.name', 'not in', [0,'']];

        $field = 'a.pigcms_id as room_id,a.layer,a.room,b.pigcms_id,b.village_id,b.usernum,b.bind_number,b.name,b.phone,b.uid,b.parent_id,b.single_id,b.type,b.relatives_type,c.nickname,c.avatar,house_type';
        $village_info = $service_house_village->getHouseVillageInfo(array('village_id'=>$village_id),'*');
        $user_list = $service_house_village->getRoomUserList($where,$field);
        if($user_list) {
            $service_login = new ManageAppLoginService();
            $user_bind_type_color = $service_login->user_bind_type_color;
            $user_bind_type_arr = $service_login->user_bind_type_arr;
            $user_bind_relatives_type_arr = $service_login->user_bind_relatives_type_arr;
            $site_url = cfg('site_url');
            $static_resources = static_resources(true);
            foreach($user_list as &$val) {
                if (1==$val['type']) {
                    $val['identity_tag'] = $user_bind_type_arr[$val['type']] . ' | ' . $user_bind_relatives_type_arr[$val['relatives_type']];
                    $val['tag_color'] = $user_bind_type_color[$val['type']];
                } else {
                    if($val['type'] == 2 && $val['house_type'] != 1){
                        $val['identity_tag'] = '租客/员工';
                    }else{
                        $val['identity_tag'] = $user_bind_type_arr[$val['type']];
                    }
                    $val['tag_color'] = $user_bind_type_color[$val['type']];
                }
                if (!$val['avatar']) {
                    $val['avatar'] = $site_url . $static_resources . 'images/avatar.png';
                }
                if(isset($val['phone']) && !empty($val['phone'])){
                    $val['phone']=$service_house_village->phoneDesensitizationV($val['phone'],$village_id,$village_info);
                }else{
                    $val['phone']='';
                }
            }
        }
        $arr_info = [];
        $arr_info['user_list'] = $user_list;

        if (!$this->auth || ($this->auth && in_array(92,$this->auth))) {
            $arr_info['is_add'] = true;
        } else {
            $arr_info['is_add'] = false;
        }
        if (cfg('manage_resident_faces_switch')==1) {
            $arr_info['is_add'] = false;
        }

        return api_output(0,$arr_info);
    }

    /**
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '用户绑定小区id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/8 13:52
     * @return \json
     */
    public function villageUserInfo() {
        // 外部联系人ID 兼容企微聊天侧边栏调取用户详情
        $userId = $this->request->post('userId');
        $agentid = $this->request->post('agentid');
        $from_type = $this->request->post('from_type');
        $from_id = $this->request->post('from_id');
        $phone = $this->request->post('phone');
        $otherParam = [
            'agentid' => $agentid,
            'from_type' => $from_type,
            'from_id' => $from_id,
            'phone' => $phone,
        ];
        // 是否忽略登录
        $ignoreLogin = false;
        if(!empty($userId)){
            // 获取用户基本信息
            $db_house_contact_way_user_service = new HouseContactWayUserService();
            $user_info = $db_house_contact_way_user_service->gethouseContactWayUser($userId,'customer_id,uid,phone,property_id,village_id,bind_id', $otherParam);
            if(!empty($user_info['uid'])){
                $ignoreLogin = true;
            }
        }

        if(!$ignoreLogin){
            // 获取登录信息
            $arr = $this->getLoginInfo();
            if (isset($arr['status']) && $arr['status']!=1000) {
                return api_output($arr['status'],[],$arr['msg']);
            }
        }

        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)) {
            return api_output(1001,[],'缺少对应用户绑定'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'id！');
        }

        $service_user = new HouseVillageUserService();
        $service_area = new AreaService();
        // 当前业主信息
        $bind_where['pigcms_id'] = $pigcms_id;
        $field = 'pigcms_id,village_id,uid,vacancy_id as room_id, usernum,bind_number,name,phone,type,id_card,ic_card,attribute,position_id,park_flag,status,authentication_field';
        $now_bind_info = $service_user->getHouseUserBindWhere($bind_where,$field, true);
        if (empty($now_bind_info)) {
            return api_output(1001,[],'对应用户不存在！');
        }
        if ($now_bind_info && $now_bind_info['attribute']>0) {
            $where_attribute = [];
            $where_attribute[] = ['pigcms_id','=',$now_bind_info['attribute']];
            $attribute_info = $service_user->getHouseUserAttributeWhere($where_attribute,'name');
            $now_bind_info['attribute_name'] = $attribute_info['name'];
        } else {
            $now_bind_info['attribute_name'] = '';
        }
        if($now_bind_info && isset($now_bind_info['authentication_field'])){
            $now_bind_info['authentication_field'] = unserialize($now_bind_info['authentication_field']);
        }
        
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(array('village_id'=>$village_id),'*');
        if(isset($now_bind_info['phone']) && !empty($now_bind_info['phone'])){
            $now_bind_info['phone']=$service_house_village->phoneDesensitizationV($now_bind_info['phone'],$now_bind_info['village_id'],$village_info);
        }
        if(isset($now_bind_info['id_card']) && !empty($now_bind_info['id_card'])){
            $now_bind_info['id_card']=$service_house_village->idnumDesensitizationV($now_bind_info['id_card'],$now_bind_info['village_id'],$village_info);
        }
        $where_data = [];
        if (isset($village_info['property_id']) && $village_info['property_id']) {
            $where_data[] = ['village_id','=',0];
            $where_data[] = ['property_id','=',$village_info['property_id']];
            $where_data[] = ['is_display','=',1];
            $where_data[] = ['is_close','=',0];
            $data = $service_user->getHouseUserDataList($where_data);
        } else {
            $where_data[] = ['village_id','=',$village_id];
            $where_data[] = ['is_display','=',1];
            $where_data[] = ['is_close','=',0];
            $data = $service_user->getHouseUserDataList($where_data);
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
                    if(isset($now_bind_info['authentication_field']) && is_array($now_bind_info['authentication_field']) && isset($now_bind_info['authentication_field'][$v['key']]) && isset($now_bind_info['authentication_field'][$v['key']]['value'])){
                        $v['value'] = $now_bind_info['authentication_field'][$v['key']]['value'];
                        $city = explode("#",$now_bind_info['authentication_field'][$v['key']]['value']);
                        $provice_info = [];
                        $city_info = [];
                        $value = '';
                        if (isset($city[0]) && $city[0]>0) {
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
                        $v['province_idss'] = isset($city[0])? $city[0]:'';
                        $v['city_idss'] = isset($city[1])?$city[1]:'';
                    }else{
                        $v['value'] = '';
                        $v['province_idss'] = '';
                        $v['city_idss'] = '';
                    }
                }else{
                    if (isset($now_bind_info['authentication_field']) && is_array($now_bind_info['authentication_field']) && isset($now_bind_info['authentication_field'][$v['key']]) && isset($now_bind_info['authentication_field'][$v['key']]['value'])) {
                        $v['value'] = $now_bind_info['authentication_field'][$v['key']]['value'];
                    } else {
                        $v['value'] = '';
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
        unset($now_bind_info['authentication_field']);
        $out = [];
        $out['now_bind_info'] = $now_bind_info;
        $out['dataList'] = $data;

        if (!$this->auth || ($this->auth && !in_array(94,$this->auth) && in_array($now_bind_info['type'],[0,3]))) {
            $out['is_del'] = true;
        } elseif (!$this->auth || ($this->auth && !in_array(106,$this->auth) && 1==$now_bind_info['type'])) {
            $out['is_del'] = true;
        }  elseif (!$this->auth || ($this->auth && !in_array(113,$this->auth) && 2==$now_bind_info['type'])) {
            $out['is_del'] = true;
        } else {
            $out['is_del'] = false;
        }

        if (!$this->auth || ($this->auth && !in_array(93,$this->auth) && in_array($now_bind_info['type'],[0,3]))) {
            $out['is_edit'] = true;
        } elseif (!$this->auth || ($this->auth && !in_array(104,$this->auth) && 1==$now_bind_info['type'])) {
            $out['is_edit'] = true;
        }  elseif (!$this->auth || ($this->auth && !in_array(116,$this->auth) && 2==$now_bind_info['type'])) {
            $out['is_edit'] = true;
        } else {
            $out['is_edit'] = false;
        }
        $out['is_edit'] = true;
        $out['is_del'] = true;
        if ($now_bind_info && isset($now_bind_info['status']) && 2==$now_bind_info['status']) {
            $out['is_del'] = false;
        }

        if (cfg('manage_resident_faces_switch')==1) {
            $out['manage_resident_faces_switch'] = 1;
        } else {
            $out['manage_resident_faces_switch'] = 0;
        }
	    $out['use_new_page'] = cfg('faceImgUseNewPage') == 1 ? 1 : 0; //有这个值表示后端代码已更新，前端页面根据这个参数判断是否是用新版的人脸上传页面
        return api_output(0,$out);
    }

    /**
     * 添加业主、家属、租客时候获取的信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'room_id'=> '小区房间id 必传',
     *  'pigcms_id'=> '小区用户绑定id 选传',
     *  'edit_type'=> '编辑类型 默认edit 审核业主页面进入时候带参数check',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/8 16:54
     * @return \json
     */
    public function villageRoomAddInfo() {
        // 获取登录信息
        /*$arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }*/
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $room_id = $this->request->param('room_id','','intval');
        if (empty($room_id)) {
            return api_output(1001,[],'缺少对应房屋！');
        }
        $service_house_village = new HouseVillageService();
        // 房屋信息
        $where_room = [];
        $where_room[] = ['pigcms_id','=',$room_id];
        $village_room = $service_house_village->getRoomInfoWhere($where_room , "floor_id,village_id,layer,room,housesize,single_id,layer_id,house_type");
        if (empty($village_room)) {
            return api_output(1001,[],'房屋信息不存在！');
        }
        if ($village_room['single_id']>0) {
            $single_id = $village_room['single_id'];
        }
        if ($village_room['village_id']>0) {
            $village_id = $village_room['village_id'];
        }

        $now_village = $service_house_village->getHouseVillage($village_id, 'village_name,village_address,set_phone,set_id_code');
        // 单元信息
        $where_floor = [];
        $where_floor[] = ['floor_id','=',$village_room['floor_id']];
        $village_floor = $service_house_village->getHouseVillageFloorWhere($where_floor , 'village_id,floor_name,single_id,floor_layer');
        if ($village_floor['single_id']>0) {
            $single_id = $village_floor['single_id'];
        }
        // 楼栋信息
        if ($single_id>0) {
            $where_single = [];
            $where_single[] = ['id', '=', $single_id];
            $single_info = $service_house_village->get_house_village_single_where($where_single,'single_name');
            if($single_info['single_name']){
                $single_name  =  $single_info['single_name'];
            }
        }
        if (!$single_name && $village_floor['floor_layer']) {
            $single_name  =  $village_floor['floor_layer'];
        }
        $address_word = [
            'single_name' => $single_name,
            'floor_name' => $village_floor['floor_name'],
            'layer' => $village_room['layer'],
            'room' => $village_room['room'],
        ];
        $address = $service_house_village->word_replce_msg($address_word,$village_id);


        $info = array(
            'vacancy_layer'         =>  $village_room['layer'],
            'vacancy_floor_id'      =>  $village_room['floor_id'],
            'vacancy_room'          =>  $village_room['room'],
            'vacancy_housesize'     =>  $village_room['housesize'],
            'village_name'          =>  $now_village['village_name'],
            'village_address'       =>  $now_village['village_address'],
            'floor_name'            =>  $village_floor['floor_name'],
            'floor_layer'           =>  $village_floor['floor_layer'],
            'village_id'            =>  $village_floor['village_id'],
            'house_address'         =>  $address,
            'set_phone'             =>  $now_village['set_phone']==1 ? 1 :0,
            'set_id_code' =>  $now_village['set_id_code']==1 ? 1:0,
        );
        $out = [];
        $out['info'] = $info;


        $service_user = new HouseVillageUserService();
        $relatives_type_info = $service_user->relatives_type_info;
        $relatives_type_info_other = $service_user->relatives_type_info_other;


        $open_house_user_name = cfg('open_house_user_name');

        $pigcms_id = $this->request->param('pigcms_id','0','intval');
        $edit_type = $this->request->param('edit_type','edit','trim');
        if ($pigcms_id>0) {
            $service_area = new AreaService();
            // 当前用户
            $bind_where['pigcms_id'] = $pigcms_id;
            $field = 'pigcms_id,village_id,uid,usernum,bind_number,name,phone,type,relatives_type,id_card,ic_card,attribute,position_id,park_flag,authentication_field';
            $now_bind_info = $service_user->getHouseUserBindWhere($bind_where,$field);
            if($now_bind_info && $now_bind_info['authentication_field']){
                $now_bind_info['authentication_field'] = unserialize($now_bind_info['authentication_field']);
            }
            $now_bind_info['relatives_type_name'] = '';
            if (0==$now_bind_info['type']) {
                $now_bind_info['type_name'] = $open_house_user_name?'业主&社长':'业主';
                $role_list_data = [];
                $role_list_data[0] = [
                    'type'=>0,
                    'name'=>$open_house_user_name?'业主&社长':'业主',
                    'relatives_type_desc'=>[],
                ];
                $out['role_list'] = $role_list_data;
            } elseif(1==$now_bind_info['type']) {
                $now_bind_info['type_name'] = $open_house_user_name?'亲友':'家属';
                $relatives_type_info = [
                    1 => '配偶',
                    2 => '父母',
                    3 => '子女',
                    4 => '亲朋好友',
                    5=>'老板',
                    6=>'人事',
                    7=>'财务',
                ];
                $role_list_data = [];
                $relatives_type_arr = [];
                $relatives_type_arr[]['name'] = $relatives_type_info[$now_bind_info['relatives_type']];
                $role_list_data[0] = [
                    'type'=>1,
                    'name'=>$open_house_user_name?'亲友':'家属',
                    'relatives_type_arr'=>$relatives_type_arr
                ];

                $now_bind_info['relatives_type_name'] = $relatives_type_info[$now_bind_info['relatives_type']];
                $out['role_list'] = $role_list_data;

            }elseif (2==$now_bind_info['type']) {
                $now_bind_info['type_name'] = $open_house_user_name?'租客&社员':'租客';
                $role_list_data[0] = [
                    'type'=>2,
                    'name'=>$open_house_user_name?'租客&社员':'租客',
                    'relatives_type_arr'=>[],
                ];
                $out['role_list'] = $role_list_data;
            }elseif (3==$now_bind_info['type']) {
                $now_bind_info['type_name'] = $open_house_user_name?'替换业主&社长':'替换业主';
                $role_list_data[0] = [
                    'type'=>3,
                    'name'=>$open_house_user_name?'替换业主&社长':'替换业主',
                    'relatives_type_arr'=>[],
                ];
                $out['role_list'] = $role_list_data;
            }


            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillage($village_id,'property_id');
            $where_data = [];
            if (isset($village_info['property_id']) && $village_info['property_id']) {
                $where_data[] = ['village_id','=',0];
                $where_data[] = ['property_id','=',$village_info['property_id']];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $service_user->getHouseUserDataList($where_data);
            } else {
                $where_data[] = ['village_id','=',$village_id];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $service_user->getHouseUserDataList($where_data);
            }

            if ($data) {
                foreach($data as &$v){
                    unset($v['system_value']);
                    unset($v['sort']);
                    unset($v['add_time']);
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

                    if($v['type'] == 3){
                        if($now_bind_info && isset($now_bind_info['authentication_field']) && isset($now_bind_info['authentication_field'][$v['key']]) && isset($now_bind_info['authentication_field'][$v['key']]['value']) && $now_bind_info['authentication_field'][$v['key']]['value']){
                            $v['value'] = $now_bind_info['authentication_field'][$v['key']]['value'];
                            $city = explode("#",$now_bind_info['authentication_field'][$v['key']]['value']);
                            $provice_info = [];
                            $city_info = [];
                            $value = '';
                            if (is_array($city) && isset($city[0]) && $city[0]>0) {
                                $provice_info = $service_area->getAreaOne(['area_id'=>$city[0],'is_open'=>1],'area_name');
                            }
                            if (is_array($city) && isset($city[1]) && $city[1]>0) {
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
                            if (is_array($city) && isset($city[0]) && $city[0]>0) {
                                $v['province_idss'] = $city[0];
                            }
                            if (is_array($city) && isset($city[1]) && $city[1]>0) {
                                $v['city_idss'] = $city[1];
                            }
                        }else{
                            $v['value'] = '';
                            $v['province_idss'] = '';
                            $v['city_idss'] = '';
                        }
                    }else{
                        if (is_array($now_bind_info['authentication_field']) && !empty($now_bind_info['authentication_field']) && isset($now_bind_info['authentication_field'][$v['key']]) && $now_bind_info['authentication_field'][$v['key']] && $now_bind_info['authentication_field'][$v['key']]['value']) {
                            $v['value'] = $now_bind_info['authentication_field'][$v['key']]['value'];
                        } else {
                            $v['value'] = '';
                        }
                    }
                }
            }
            unset($now_bind_info['authentication_field']);
            $out['now_bind_info'] = $now_bind_info;
            $out['dataList'] = $data;
        } elseif('check'==$edit_type){
            $where = [];
            $where[] = ['pigcms_id','=',$room_id];
            $list = $service_user->getRoomUserWhere($where,$village_id,true);
            if (empty($list)) {
                return api_output(1001,[],'房屋信息不存在！');
            }
            if ($list['uid']==0 && $list['status']==1) {
                return api_output(1001,[],'该房屋不存在绑定业主！');
            }
            $out['is_edit_check'] = true;
            $now_bind_info = [];
            if ($list['bind_id']>0) {
                $now_bind_info['pigcms_id'] = $list['bind_id'];
            }
            $now_bind_info['village_id'] = $list['village_id'];
            if ($list['uid']>0) {
                $now_bind_info['uid'] = $list['uid'];
            }
            if ($list['usernum']) {
                $now_bind_info['usernum'] = $list['usernum'];
            }
            $now_bind_info['name'] = $list['name'];
            $now_bind_info['phone'] = $list['phone'];
            $now_bind_info['type'] = $list['type'];
            $now_bind_info['attribute'] = $list['attribute'];
            $now_bind_info['park_flag'] = $list['park_flag'];
            $now_bind_info['type_name'] = $list['park_flag'];
            if (0==$now_bind_info['type']) {
                $now_bind_info['type_name'] = $open_house_user_name?'业主&社长':'业主';
            }elseif (3==$now_bind_info['type']) {
                $now_bind_info['type_name'] = $open_house_user_name?'替换业主&社长':'替换业主';
            }
            $out['now_bind_info'] = $now_bind_info;
            $out['dataList'] = $list['dataList'];
        }  else {
            $out['is_edit_check'] = false;
            // 房屋信息
            $where_room = [];
            $where_room[] = ['pigcms_id','=',$room_id];
            $where_room[] = ['type','=',0];
            $where_room[] = ['status','=',3];
            $now_room_user = $service_house_village->getRoomInfoWhere($where_room , "phone,name,house_type");

            if($now_room_user){
                $now_room_user['phone_txt'] = substr($now_room_user['phone'],0,3).'****';
                $out['room_user'] = $now_room_user; //判断是否有业主===>郭杰
                $out['room_user_status'] = '1';//是有业主的

                $role_list_data = [];
                if($now_room_user['house_type'] != 1){
                    $relatives_type_info = $relatives_type_info_other;
                    $tenant = '租客/员工';
                }else{
                    $tenant = '租客';
                }
                $role_list_data[0] = [
                    'type'=>1,
                    'name'=>$open_house_user_name?'亲友':'家属',
                    'relatives_type_arr'=>$relatives_type_info
                ];
                $role_list_data[1] = [
                    'type'=>2,
                    'name'=>$open_house_user_name?'租客&社员':$tenant,
                    'relatives_type_arr'=>[],
                ];
//                $role_list_data[2] = [
//                    'type'=>3,
//                    'name'=>$open_house_user_name?'替换业主&社长':'替换业主',
//                    'relatives_type_arr'=>[],
//                ];

                $out['role_list'] = $role_list_data;
            }else{
                $out['room_user'] = ''; //判断是否有业主===>郭杰
                $out['room_user_status'] = '0';//无业主的
                $role_list_data = [];
                $role_list_data[0] = [
                    'type'=>0,
                    'name'=>$open_house_user_name?'业主&社长':'业主',
                    'relatives_type_desc'=>[],
                    'relatives_type_arr'=>[]
                ];
                if($village_room['house_type'] != 1){
                    $relatives_type_info = $relatives_type_info_other;
                    $tenant = '租客/员工';
                }else{
                    $tenant = '租客';
                }
                $role_list_data[1] = [
                    'type'=>1,
                    'name'=>$open_house_user_name?'亲友':'家属',
                    'relatives_type_desc'=>$relatives_type_info,
                    'relatives_type_arr'=>$relatives_type_info
                ];
                $role_list_data[2] = [
                    'type'=>2,
                    'name'=>$open_house_user_name?'租客&社员':$tenant,
                    'relatives_type_desc'=>[],
                    'relatives_type_arr'=>[]
                ];

                $out['role_list'] = $role_list_data;
            }

            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillage($village_id,'property_id');
            $where_data = [];
            $field = 'use_field,title, type, is_must,is_system,key';
            if (isset($village_info['property_id']) && $village_info['property_id']) {
                $where_data[] = ['village_id','=',0];
                $where_data[] = ['property_id','=',$village_info['property_id']];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $service_user->getHouseUserDataList($where_data,$field);
            } else {
                $where_data[] = ['village_id','=',$village_id];
                $where_data[] = ['is_display','=',1];
                $where_data[] = ['is_close','=',0];
                $data = $service_user->getHouseUserDataList($where_data,$field);
            }

            if($data){
                foreach($data as &$v){
                    if($v['type'] == 2){
                        if($v['is_system'] == 1){
                            $v['use_field'] = $service_house_village->get_system_value($v['key']);
                        }else{
                            $v['use_field'] = explode(',',$v['use_field']);
                        }
                    }elseif($v['type'] == 3){

                        $v['use_field'] = array();
                    }else{
                        $v['use_field'] = array();
                    }

                    $v['value'] = '';


                }

                $out['dataList'] = $data;
            }else{
                $out['dataList'] = [];
            }
        }

        // 业主属性
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $attribute_list = $service_user->getHouseUserAttributeList($where);
        if($attribute_list){
            $out['attribute_list'] = $attribute_list;
        }else{
            $out['attribute_list'] = [];
        }

        return api_output(0,$out);
    }

    /**
     * 添加业主、家属、租客
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'type'=> '对象身份  0业主 1，家人 2租客 3，更新业主 必传',
     *  'relatives_type'=> 'type为1 即家属的时候 必传',
     *  'attribute'=> '业主、家属、租客属性 必传',
     *  'name'=> '姓名 必传',
     *  'phone'=> '手机号 必传',
     *  'room_id'=> '绑定的房屋id 必传',
     *  'id_card'=> '身份证 选传',
     *  'ic_card'=> 'ic卡 选传',
     *  'memo'=> '备注 选传',
     *  'data_post' => [
     *      0=>[
     *          'use_field'=> ["男","女"],// 可选信息
     *          'title' => '性别',// 标题
     *          'type' => '2', // 类型 1文本 2下拉 3城市
     *          'is_must' => '1', // 是否必填 1 必填
     *          'is_system' => '1', // 是否系统参数 1 是
     *          'key' => 'sex',// 关键字
     *          'value' => '男' // 对应内容
     *      ]
     * ]
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/11 10:02
     * @return \json
     */
    public function villageRoomAdd() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $type = $this->request->param('type','','intval');
        if (empty($type)) {
            $param = $this->request->param();
            if (!isset($param['type'])) {
                return api_output(1001,[],'请选择添加对象身份！');
            }
            // 这个情况就是业主  所以直接赋值为0
            $type = 0;
        }
        $relatives_type = $this->request->param('relatives_type','','intval');
        if (1==$type && empty($relatives_type)) {
            return api_output(1001,[],'请选择添加家属和业主关系！');
        }
        if (3==$type) {
            return api_output(1001,[],'不能替换业主 请删除业主后再行添加！');
        }
        // 业主属性
        $service_user = new HouseVillageUserService();
        $attribute = $this->request->param('attribute',0,'intval');
        /*  此功能已作废
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $attribute_list = $service_user->getHouseUserAttributeList($where);
        if (!$attribute && $attribute_list) {
            return api_output(1001,[],'请选择添加对象属性！');
        }
        */
        $name = $this->request->param('name','','trim');
        if (empty($name)) {
            return api_output(1001,[],'请选择添加对象姓名！');
        }
        $phone = $this->request->param('phone','','trim');
        if(in_array($relatives_type,[2,3]) && !$phone){//仅仅对父母子女做手机号是否必填查询
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillage($village_id,'set_phone');
            $setPhone = $village_info['set_phone']??1;
            if($setPhone==1){
                return api_output(1001,[],'请填写手机号！');
            }
        }
        /*
        if(!in_array($relatives_type,[2,3]) && !$phone){//其他角色手机号必填
            return api_output(1001,[],'请填写手机号！');
        }
        */
        /*if (empty($phone)) {
            return api_output(1001,[],'请选择添加对象手机号！');
        }
        if(!cfg('international_phone') && !preg_match('/^[0-9]{11}$/',$phone)){
            return api_output(1003,[],'请输入有效的手机号！');
        }*/
        $room_id = $this->request->param('room_id','','intval');
        if (empty($room_id)) {
            return api_output(1001,[],'缺少对应房屋！');
        }
        $id_card = $this->request->param('id_card','','trim');
        $ic_card = $this->request->param('ic_card','','trim');

        $data_post = $this->request->param('data_post');

        if($data_post){
            if (is_string($data_post)) {
                $data_post = json_decode($data_post,true);
            }
            foreach($data_post as $k=>$v){
                if (is_string($v)) {
                    $v = json_decode($v,true);
                }
                if($v['is_must'] == 1 && empty($v['value'])){
                    return api_output(1001,[],$v['title'].'为必填项！');
                }
            }
            $arr_data = [];
            foreach($data_post as $kk=>$vv){
                if (is_string($vv)) {
                    $vv = json_decode($vv,true);
                }
                if($vv['value']){
                    $arr1[$vv['key']] = array(
                        'type'=>$vv['type'],
                        'is_must'=>$vv['is_must'],
                        'key'=>$vv['key'],
                        'title'=>$vv['title'],
                        'value'=>$vv['value'],

                    );
                }else{
                    $arr1[$vv['key']] = array(
                        'type'=>$vv['type'],
                        'is_must'=>$vv['is_must'],
                        'key'=>$vv['key'],
                        'title'=>$vv['title'],
                        'value'=>'',

                    );
                }

                $arr_data = $arr1;
            }
            $bind_data['authentication_field'] = serialize($arr_data);
        }else{
            $bind_data['authentication_field'] = '';
        }
        $service_house_village = new HouseVillageService();

        // 房屋信息
        $where_room = [];
        $where_room[] = ['pigcms_id','=',$room_id];
        $village_room = $service_house_village->getRoomInfoWhere($where_room , "pigcms_id,floor_id,village_id,usernum,property_number,layer,room,housesize,single_id,layer_id");
        if (empty($village_room)) {
            return api_output(1003,[],'房屋信息不存在！');
        }
        $uid = 0;
        if ($phone) {
            $service_user_info = new UserService();
            $where_user = [];
            $where_user[] = ['phone','=',$phone];
            $user = $service_user_info->getUserOne($where_user,'uid');
            if ($user && $user['uid']) {
                $uid = $user['uid'];
            }else{
                $uid = $service_user_info->addUser(['phone'=>$phone,'truename'=>$name]);
            }
            // 手机号不能与现有房间中用户手机号一样
            $bind_condition = [];
            $bind_condition[] = ['vacancy_id','=',$room_id];
            $bind_condition[] = ['phone','=',$phone];
            $bind_condition[] = ['status','in','1,2'];
            $family_bind_info = $service_user->getHouseUserBindWhere($bind_condition);
            if(!empty($family_bind_info)){
                return api_output(1003,[],'该手机号已绑定或已申请绑定此房间！');
            }
        }
        if($uid > 0){
            $bind_data['uid'] = $uid;
        }else{
            $bind_data['uid'] = 0;
        }
        $bind_data['housesize'] = $village_room['housesize'];
        // 手机号不能与现有房间中用户手机号一样
        $bind_condition = [];
        $bind_condition[] = ['vacancy_id','=',$room_id];
        $bind_condition[] = ['phone','=',$phone];
        $bind_condition[] = ['status','in','1,2'];
        $family_bind_info = $service_user->getHouseUserBindWhere($bind_condition);
        if(!empty($family_bind_info)){
            return api_output(1003,[],'该手机号已绑定或已申请绑定此'.((new HouseVillageService())->getHouseAlias('room_name')?:'房间').'！');
        }

        if (0==$type) {
            $where_bind = [];
            $where_bind[] = ['status','in',[1,2]];
            $where_bind[] = ['vacancy_id','=',$village_room['pigcms_id']];
            $where_bind[] = ['type','in',[0,3]];
            $bind_info = $service_user->getHouseUserBindWhere($where_bind);
            if ($bind_info && ((isset($bind_info['phone']) && !empty($bind_info['phone'])) || (isset($bind_info['name']) && !empty($bind_info['name'])) || (isset($bind_info['name']) && intval($bind_info['uid'])>0))) {
                if ($bind_info['status'] == 2) {
                    return api_output(1003,[],'已经有用户申请业主！');
                } else {
                    return api_output(1003,[],'已经存在业主！');
                }
            }
        } elseif (3!=$type) {
            // 不是业主  需要查询到业主关联业主
            $parent_condition = [];
            $parent_condition[] = ['vacancy_id','=',$room_id];
            $parent_condition[] = ['status','=','1'];
            $parent_condition[] = ['type','in','0,3'];
            $field_parent = 'pigcms_id';
            $parent_bind_info = $service_user->getHouseUserBindWhere($parent_condition,$field_parent);
            if ($parent_bind_info) {
                $bind_data['parent_id'] = $parent_bind_info['pigcms_id'];
            }
        }

        // 单元信息
        $where_floor = [];
        $where_floor[] = ['floor_id','=',$village_room['floor_id']];
        $village_floor = $service_house_village->getHouseVillageFloorWhere($where_floor , 'village_id,floor_name,single_id,floor_layer');
        if ($village_floor['single_id']>0) {
            $single_id = $village_floor['single_id'];
        } elseif($village_room['single_id']>0) {
            $single_id = $village_room['single_id'];
        }
        // 楼栋信息
        if ($single_id>0) {
            $where_single = [];
            $where_single[] = ['id', '=', $single_id];
            $single_info = $service_house_village->get_house_village_single_where($where_single,'single_name');
            if($single_info['single_name']){
                $single_name  =  $single_info['single_name'];
            }
        }
        if (!$single_name && $village_floor['floor_layer']) {
            $single_name  =  $village_floor['floor_layer'];
        }
        $address_word = [
            'single_name' => $single_name,
            'floor_name' => $village_floor['floor_name'],
            'layer' => $village_room['layer'],
            'room' => $village_room['room'],
        ];
        $address = $service_house_village->word_replce_msg($address_word,$village_id);

        $memo = $this->request->param('memo','','trim');
        $memo = htmlspecialchars($memo);
        $bind_data['name'] = $name;
        $bind_data['phone'] = $phone;
        $bind_data['floor_id'] = $village_room['floor_id'];
        $bind_data['layer_id'] = $village_room['layer_id'];
        $bind_data['village_id'] = $village_room['village_id'];
        $bind_data['room_addrss'] = $village_room['room'];
        $bind_data['layer_num'] = $village_room['layer'];
        if(0==$type)  {
            $bind_data['usernum'] = $village_room['usernum'];
        } else {
            $bind_data['usernum'] = rand(0,99999) . '-' . time();
        }
        $bind_data['attribute'] = $attribute;
        if ($memo) {
            $bind_data['memo'] = $memo;//备注
        }
        $bind_data['vacancy_id'] = $room_id;
        $bind_data['address'] = $address;
        $bind_data['single_id'] =$single_id;
        $bind_data['type'] = $type;
        if ($relatives_type) {
            $bind_data['relatives_type'] = $relatives_type;
        }
        if ($id_card) {
            $bind_data['id_card'] = $id_card;
        }
        if ($ic_card) {
            $bind_data['ic_card'] = $ic_card;
        }
        $bind_data['add_time'] = time();

        // 查询下是否存在虚拟业主  存在直接替换 然后删除现在的申请数据
        if (0==$type || 3==$type) {
            if ($bind_info) {
                fdump('虚拟业主>>>'.__LINE__,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                fdump($bind_info,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                // 存在虚拟业主
                // 记录原来用户id
                $pigcms_id = $bind_info['pigcms_id'];
                $where_other = [
                    'pigcms_id' => $pigcms_id
                ];
                $set = [
                    'usernum' => $village_room['usernum'].'-y-'.time(),
                    'status' => 0
                ];
                $service_user->saveUserBindOne($where_other,$set);
            }
        }
        $pigcms_id = $service_user->addUserBindOne($bind_data);
        if($pigcms_id){
            if (0==$type) {
                //更改房间业主信息  uid,name,phone,type=0,status=3,housesize,park_flag,add_time
                $data_info['uid'] = $bind_data['uid'];
                $data_info['name'] = $name;
                $data_info['phone'] = $phone;
                $data_info['type'] = $type;
                $data_info['status'] = 3;
                $data_info['housesize'] = $bind_data['housesize'];
                $data_info['add_time'] = time();
                $service_user->saveHouseRoom($where_room,$data_info);
                // 查询下是否存在先行添加的非业主绑定
                $where = [];
                $where[] = ['vacancy_id','=',$room_id];
                $where[] = ['type','not in', '0,3'];
                $where[] = ['parent_id','<>', $pigcms_id];
                $field = 'a.pigcms_id';
                $list = $service_user->getLimitRoomList($where,'',$field,'a.pigcms_id DESC');
                if ($list) {
                    foreach($list as $vl) {
                        $where_bind = [];
                        $where_bind[] = ['pigcms_id','=', $vl['pigcms_id']];
                        $save_data = [
                            'parent_id' => $pigcms_id
                        ];
                        $service_user->saveUserBindOne($where_bind,$save_data);
                    }
                }
            }
            $arr = array();
            $arr['param'] = serialize(array('bind_id' =>$pigcms_id,'type'=>'user'));
            $arr['plan_time'] = time()+60;
            $arr['space_time'] = 0;
            $arr['add_time'] = time();
            $arr['file'] = 'sub_house_village_third';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'sub_house_village_third_user_'.$pigcms_id;
            $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
            $service_user->add_third_log($arr);
            return api_output(0,['pigcms_id' => $pigcms_id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }
    }

    /**
     * 编辑业主、家属、租客
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '小区用户绑定id 必传',
     *  'edit_type'=> '编辑类型 默认edit 审核业主页面进入时候带参数(即获取接口中参数is_edit_check为true)check',
     *  'room_id'=> '房屋id 从审核业主详情编辑页面进入时候  即获取接口中参数is_edit_check为true  必传',
     *  'attribute'=> '业主、家属、租客属性 必传',
     *  'name'=> '姓名 必传',
     *  'phone'=> '手机号 必传',
     *  'room_id'=> '绑定的房屋id 必传',
     *  'id_card'=> '身份证 选传',
     *  'ic_card'=> 'ic卡 选传',
     *  'memo'=> '备注 选传',
     *  'data_post' => [
     *      0=>[
     *          'use_field'=> ["男","女"],// 可选信息
     *          'title' => '性别',// 标题
     *          'type' => '2', // 类型 1文本 2下拉 3城市
     *          'is_must' => '1', // 是否必填 1 必填
     *          'is_system' => '1', // 是否系统参数 1 是
     *          'key' => 'sex',// 关键字
     *          'value' => '男' // 对应内容
     *      ]
     * ]
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/11 10:02
     * @return \json
     */
    public function villageRoomEdit() {
        // 外部联系人ID 兼容企微聊天侧边栏调取用户详情
        $userId = $this->request->post('userId');
        // 是否忽略登录
        $ignoreLogin = false;
        if(!empty($userId)){
            // 获取用户基本信息
            $db_house_contact_way_user_service = new HouseContactWayUserService();
            $user_info = $db_house_contact_way_user_service->gethouseContactWayUser($userId,'customer_id,uid,phone,property_id,village_id,bind_id');
            if(!empty($user_info['uid'])){
                $ignoreLogin = true;
            }
        }
        // 忽略登录
        if(!$ignoreLogin){
            // 获取登录信息
            $arr = $this->getLoginInfo();
            if (isset($arr['status']) && $arr['status']!=1000) {
                return api_output($arr['status'],[],$arr['msg']);
            }
        }

        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $attribute = $this->request->param('attribute','','intval');
        $name = $this->request->param('name','','trim');
        $phone = $this->request->param('phone','','intval');
        $app_type = $this->request->post('app_type','');
        /*if($phone && !cfg('international_phone') && !preg_match('/^[0-9]{11}$/',$phone)){
            return api_output(1003,[],'请输入有效的手机号！');
        }*/
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)) {
            return api_output(1001,[],'缺少编辑对象！');
        }
        $id_card = $this->request->param('id_card','','trim');
        $ic_card = $this->request->param('ic_card','','trim');
        $data_post = $this->request->param('data_post');
        if($data_post){
            if (is_string($data_post)) {
                $data_post = json_decode($data_post,true);
            }
            foreach($data_post as $k=>$v){
                if (is_string($v)) {
                    $v = json_decode($v,true);
                }
                if($v['is_must'] == 1 && empty($v['value'])){
                    return api_output(1001,[],$v['title'].'为必填项！');
                }
            }
            $arr_data = [];
            foreach($data_post as $kk=>$vv){
                if (is_string($vv)) {
                    $vv = json_decode($vv,true);
                }
                if($vv['value']){
                    $arr1[$vv['key']] = array(
                        'type'=>$vv['type'],
                        'is_must'=>$vv['is_must'],
                        'key'=>$vv['key'],
                        'title'=>$vv['title'],
                        'value'=>$vv['value'],

                    );
                }else{
                    $arr1[$vv['key']] = array(
                        'type'=>$vv['type'],
                        'is_must'=>$vv['is_must'],
                        'key'=>$vv['key'],
                        'title'=>$vv['title'],
                        'value'=>'',
                    );
                }

                $arr_data = $arr1;
            }
            $bind_data['authentication_field'] = serialize($arr_data);
        }
        $service_user = new HouseVillageUserService();
        $service_house_village = new HouseVillageService();
        $service_user_info = new UserService();
        $memo = $this->request->param('memo','','trim');
        $memo = htmlspecialchars($memo);

        $edit_type = $this->request->param('edit_type','edit','intval');
        $room_id = $this->request->param('room_id','','intval');
        if ('check'==$edit_type && !$room_id) {
            return api_output(1001,[],'缺少'.((new HouseVillageService())->getHouseAlias('room_name')?:'房间').'id！');
        }
        if ('check'==$edit_type && $room_id) {
            // 房屋信息
            $where_room = [];
            $where_room[] = ['pigcms_id','=',$room_id];
            $village_room = $service_house_village->getRoomInfoWhere($where_room , "pigcms_id,floor_id,village_id,type,usernum,property_number,layer,room,housesize,single_id,layer_id");
            if (empty($village_room)) {
                return api_output(1003,[],'房屋信息不存在！');
            }
            // 获取是否已经绑定了用户信息
            $where_user = [];
            $where_user[] = ['vacancy_id','=',$village_room['pigcms_id']];
            $where_user[] = ['type','in','0,3'];
            $where_user[] = ['status','=',1];
            $user_info = $service_user->getHouseUserBindWhere($where_user);
            if (empty($user_info) || $user_info['usernum']!=$village_room['usernum']) {
                $bind_data['usernum'] = $village_room['usernum'];
            }
            if (empty($user_info) || $user_info['floor_id']!=$village_room['floor_id']) {
                $bind_data['floor_id'] = $village_room['floor_id'];
            }
            if (empty($user_info) || $user_info['layer_num']!=$village_room['layer']) {
                $bind_data['layer_num'] = $village_room['layer'];
            }
            if (empty($user_info) || $user_info['room_addrss']!=$village_room['room']) {
                $bind_data['room_addrss'] = $village_room['room'];
            }
            if (empty($user_info) ||($memo && $user_info['memo'] != $memo)) {
                //备注
                $bind_data['memo'] = $memo;
            }
            if (empty($user_info) || $user_info['name']!=$name) {
                $bind_data['name'] = $name;
            }
            if (empty($user_info) || $user_info['phone']!=$phone) {
                $bind_data['phone'] = $phone;
            }
            if (empty($user_info) || $user_info['id_card']!=$id_card) {
                $bind_data['id_card'] = $id_card;
            }
            if (empty($user_info) || $user_info['ic_card']!=$ic_card) {
                $bind_data['ic_card'] = $ic_card;
            }
            if (empty($user_info) || $user_info['attribute']!=$attribute) {
                $bind_data['attribute'] = $attribute;
            }
            $bind_data['village_id'] = $village_id;
            if (empty($user_info) || $user_info['housesize']!=$village_room['housesize']) {
                $bind_data['housesize'] = $village_room['housesize'];
            }
            $bind_data['add_time'] = time();
            $bind_data['vacancy_id'] = $room_id;
            $bind_data['type'] = $village_room['type'];
            $bind_data['single_id'] = $village_room['single_id'];
            $bind_data['layer_id'] = $village_room['layer_id'];
            $uid = 0;
            if ($phone && $user_info['phone'] != $phone) {
                $where_user = [];
                $where_user[] = ['phone','=',$phone];
                $user = $service_user_info->getUserOne($where_user,'uid');
                if ($user && $user['uid']) {
                    $uid = $user['uid'];
                }else{
                    $uid = $service_user_info->addUser(['phone'=>$phone,'truename'=>$name]);
                }
            }
            if($uid > 0  && $user_info['phone'] != $phone){
                $bind_data['uid'] = $uid;
            }elseif ($user_info['phone'] != $phone){
                $bind_data['uid'] = 0;
            }
            if($user_info){
                $save = $service_user->saveUserBindOne($where_user,$bind_data);
            }else{
                $save = $service_user->addUserBindOne($bind_data);
            }
            $houseUserTrajectoryLogService=new HouseUserTrajectoryLogService();
            $houseUserTrajectoryLogService->addLog(4,$village_id,0,$user_info['uid'],$user_info['pigcms_id'],2,'更新了人员数据','移动端更新了人员数据');
            if($save){
                if (0==$village_room['type']) {
                    //更改房间业主信息  uid,name,phone,type=0,status=3,housesize,park_flag,add_time
                    $where_room = [];
                    $where_room[] = ['pigcms_id','=',$room_id];
                    if (isset($bind_data['uid'])) {
                        $data_info['uid'] = $bind_data['uid'];
                    }
                    if ($name) {
                        $data_info['name'] = $name;
                    }
                    if ($phone) {
                        $data_info['phone'] = $phone;
                    }
                    $data_info['add_time'] = time();
                    $service_user->saveHouseRoom($where_room,$data_info);
                }
                $arr = array();
                $arr['param'] = serialize(array('bind_id' =>$pigcms_id,'type'=>'user'));
                $arr['plan_time'] = time()+60;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_user_'.$pigcms_id;
                $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
                $service_user->add_third_log($arr);
                return api_output(0,['pigcms_id' => $pigcms_id],'修改成功');
            }else{
                return api_output(1003,[],'修改失败！');
            }
        }
        // 绑定用户信息
        $where_user_bind = [];
        $where_user_bind[] = ['pigcms_id','=',$pigcms_id];
        $user_info = $service_user->getHouseUserBindWhere($where_user_bind);
        if (empty($user_info)) {
            return api_output(1003,[],'编辑对象不存在！');
        }
        $uid = 0;
        if ($phone && $user_info['phone'] != $phone) {
            $where_user = [];
            $where_user[] = ['phone','=',$phone];
            $user = $service_user_info->getUserOne($where_user,'uid');
            if ($user && $user['uid']) {
                $uid = $user['uid'];
            }
        }
        if($uid > 0  && $user_info['phone'] != $phone){
            $bind_data['uid'] = $uid;
        }elseif ($user_info['phone'] != $phone){
            $bind_data['uid'] = 0;
        }


        if ($name && $user_info['name'] != $name) {
            $bind_data['name'] = $name;
        }
        if ($phone && $user_info['phone'] != $phone) {
            $bind_data['phone'] = $phone;
        }
        if ($attribute && $user_info['attribute'] != $attribute) {
            $bind_data['attribute'] = $attribute;
        }
        if ($memo && $user_info['memo'] != $memo) {
            $bind_data['memo'] = $memo;//备注
        }
        if ($id_card && $user_info['id_card'] != $id_card) {
            $bind_data['id_card'] = $id_card;
        }
        if ($ic_card && $user_info['ic_card'] != $ic_card) {
            $bind_data['ic_card'] = $ic_card;
        }
        $bind_data['add_time'] = time();
        $save = $service_user->saveUserBindOne($where_user_bind,$bind_data);
        $houseUserTrajectoryLogService=new HouseUserTrajectoryLogService();
        $houseUserTrajectoryLogService->addLog(4,$village_id,0,$user_info['uid'],$user_info['pigcms_id'],2,'更新了人员数据','移动端更新了人员数据');
        if($save){
            if (0==$user_info['type']) {
                //更改房间业主信息  uid,name,phone,type=0,status=3,housesize,park_flag,add_time
                $where_room = [];
                $where_room[] = ['pigcms_id','=',$user_info['vacancy_id']];
                if (isset($bind_data['uid'])) {
                    $data_info['uid'] = $bind_data['uid'];
                }
                if ($name) {
                    $data_info['name'] = $name;
                }
                if ($phone) {
                    $data_info['phone'] = $phone;
                }
                $data_info['add_time'] = time();
                $service_user->saveHouseRoom($where_room,$data_info);
            }
            return api_output(0,['pigcms_id' => $pigcms_id],'修改成功');
        }else{
            return api_output(1003,[],'修改失败！');
        }
    }


    /**
     * 房屋绑定删除
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '小区用户绑定id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/29 14:41
     * @return \json
     */
    public function villageUserDelete() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(94,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)) {
            return api_output(1001,[],'缺少对应用户绑定'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'id！');
        }
        $service_user = new HouseVillageUserService();
        // 当前业主信息
        fdump('删除>>>'.__LINE__,'villageUserDelete',1);
        fdump($_POST,'villageUserDelete',1);
        $bind_where['pigcms_id'] = $pigcms_id;
        $field = 'pigcms_id,village_id,uid,vacancy_id,type';
        $now_bind_info = $service_user->getHouseUserBindWhere($bind_where,$field);
        fdump($now_bind_info,'villageUserDelete',1);
        if (empty($now_bind_info)) {
            return api_output(1001,[],'对应用户不存在或者已经被删除！');
        }
        $del = $service_user->delUserBindOne($bind_where);
        if($del){
            // 查询一下是否存在其他状态的业主信息，全部删除
            // todo 人脸门禁同步删除
            if (0==$now_bind_info['type'] || 3==$now_bind_info['type']) {
                $where = [];
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['vacancy_id', '=', $now_bind_info['vacancy_id']];
                $where[] = ['type', 'in', '0,3'];
                $other =$service_user->getLimitUserList($where);
                if (!empty($other)) {
                    foreach ($other as $val) {
                        $service_user->delUserBindOne(['pigcms_id' => $val['pigcms_id']]);
                        // todo 人脸门禁同步删除
                    }
                }
                // 删除下属关联家属租客
                $family_condition = [];
                $family_condition[] = ['village_id', '=', $now_bind_info['village_id']];
                $family_condition[] = ['parent_id', '=', $now_bind_info['pigcms_id']];

                $service_user->delUserBindOne($family_condition);

                //清空房间
                $data['uid'] = 0;
                $data['status'] = 1;
                $data['name'] = "";
                $data['phone'] = "";
                $data['type'] = 0;
                $data['park_flag'] = 0;

                $where_room = [];
                $where_room[] = ['pigcms_id', '=', $now_bind_info['vacancy_id']];
                $where_room[] = ['village_id', '=', $now_bind_info['village_id']];
                $service_user->saveHouseRoom($where_room,$data);
            }
            return api_output(0,['pigcms_id'=>$pigcms_id],'删除成功');
        }else{
            return api_output(1002,[],'删除失败');
        }
    }

    /**
     * 获取小区用户信息
     * @author lijie
     * @date_time 2020/09/01 14:35
     * @return \json
     */
    public function userBindInfo()
    {
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output_error(1001,'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)) {
            return api_output_error(1001,'缺少对应用户id！');
        }
        $service_user = new HouseVillageUserService();
        $info = $service_user->getHouseUserBind($pigcms_id,'name,phone,uid,single_id,floor_id,layer_num,room_addrss,vacancy_id');
        return api_output(0,$info,'获取成功');
    }

    /**
     * 生成二维码
     * @author lijie
     * @date_time 2020/09/01 17:15
     * @param $url
     * @return mixed
     */
    public function createQrcode()
    {
        $url = $this->request->post('url','');
        $village_id = $this->request->post('village_id',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$url && !$village_id && !$pigcms_id)
            return api_output_error(1001,'缺少必传参数');
        if($village_id && $pigcms_id){
            $service_config = new ConfigService();
            $site = $service_config->get_config('site_url','value');
            if(empty($site['value']))
                return api_output_error(1001,'配置错误');
            $service_house_village = new HouseVillageService();
            $url = $site['value'].$service_house_village->base_url.'pages/village/visitorPass/userinfo?village_id='.$village_id.'&pigcms_id='.$pigcms_id;
        }
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 6;//生成图片大小
        $dirName = dirname(__DIR__,6).'/';
        $filename = $dirName.'upload/qrcode/visitor';
        if(!file_exists($filename)){
            mkdir($filename,0777,true);
        }
        
        $order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $filename = $filename.'/'.$order_no.'.png';
        \QRcode::png($url,$filename, $errorCorrectionLevel, $matrixPointSize, 2);
        $QR =str_replace($dirName,'',$filename);       //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $data['qrcode'] =  $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;
        return api_output(0,$data);
    }

    /***
    **通过业主信息搜搜房间
     ***/
    public function searchVillageRoomByinfo() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应'.((new HouseVillageService())->getHouseAlias('house_name')?:'小区').'！');
            }
        }
        $search_val = $this->request->param('search_val','','trim');
        if(empty($search_val)){
            return api_output(1001,[],'请填写要查找的业主信息！');
        }
        $where = [];
        $where[] = ['a.village_id', '=', $village_id];
        $where[] = ['b.status', '=', '1'];
        $where[] = ['b.type', 'in', '0,1,2,3'];
        $search_val= htmlspecialchars($search_val,ENT_QUOTES);
        $where[] = ['b.name|b.phone','LIKE', '%'.$search_val.'%'];
        $field = 'a.pigcms_id as room_id,a.layer,a.room,a.single_id,a.layer_id,a.house_type,a.floor_id,a.village_id,b.phone,b.uid,b.name,b.address,c.nickname,c.avatar';
        $service_house_village = new HouseVillageService();
        $user_list = $service_house_village->getRoomUserList($where,$field);
        if($user_list) {
            $site_url = cfg('site_url');
            $service_house_village_single = new HouseVillageSingleService();
            $static_resources = static_resources(true);
            foreach($user_list as &$val) {
                if (!$val['avatar'] && (strpos($val['avatar'],'http:')===false && strpos($val['avatar'],'https:')===false)) {
                    $val['avatar'] = $site_url . $static_resources . 'images/avatar.png';
                }
                $val['single_name']='';
                if($val['single_id']>0){
                    $house_single=$service_house_village_single->getSingleInfo(['id'=>$val['single_id']],'single_name');
                    if(!empty($house_single)){
                        $val['single_name']=$house_single['single_name'];
                        if(is_numeric($val['single_name'])){
                            $val['single_name']=$val['single_name'].'('.((new HouseVillageService())->getHouseAlias('single_name')?:'栋').')';
                        }
                    }
                }
                $val['floor_name']='';
                if($val['floor_id']>0){
                    $house_single=$service_house_village_single->getFloorInfo(['floor_id'=>$val['floor_id']],'floor_name');
                    if(!empty($house_single)){
                        $val['floor_name']=$house_single['floor_name'];
                        if(is_numeric($val['floor_name'])){
                            $val['floor_name']=$val['floor_name'].'('.((new HouseVillageService())->getHouseAlias('floor_name')?:'单元').')';
                        }
                    }
                }
                $val['layer_name']='';
                if($val['layer_id']>0){
                    $house_single=$service_house_village_single->getLayerInfo(['id'=>$val['layer_id']],'layer_name');
                    if(!empty($house_single)){
                        $val['layer_name']=$house_single['layer_name'];
                        if(is_numeric($val['layer_name'])){
                            $val['layer_name']=$val['layer_name'].'(层)';
                        }
                    }
                }
            }
            return api_output(0,$user_list);
        }else{
            return api_output(1001,[],'未查找到业主绑定的房屋信息！');
        }
    }
}
