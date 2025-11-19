<?php
/**
 * 收银台
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/26 9:43
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\HouseVillageOrderService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillagePayCashierOrderService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\ConfigService;
use app\community\model\service\HouseVillagePaymentService;
use app\community\model\service\HouseVillagePropertyFeeService;

class CashierController extends BaseController{

    /**
     * 收银台搜索结果
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'search_value'=> '查询字段 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/25 17:34
     * @return \json
     */
    public function userSearch() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        $search_value = $this->request->param('search_value','','trim');
        if (!$search_value) {
            return api_output(1001,[],'请输入查询信息！');
        }
        $service_user = new HouseVillageUserService();
        $where = [];
        $where[] = ['a.village_id','=',$village_id];
        $where[] = ['a.type','in','0,3'];
        $where[] = ['a.status','=',1];
        $where[] = ['a.vacancy_id','>',0];
        $where[] = ['a.uid|a.phone|a.name', 'not in', [0,'']];
        if ($search_value) {
            // 多个字段模糊查询
            $where[] = ['a.name|a.phone|a.usernum|a.bind_number','LIKE', '%'.$search_value.'%'];
        }

        $field = 'a.pigcms_id,a.village_id,a.uid,a.usernum,a.bind_number,a.name,a.phone,a.address,a.layer_num,a.room_addrss,hvf.floor_name,hvf.floor_layer,hvs.single_name,a.single_id,a.floor_id,a.layer_id,a.vacancy_id';
        $list = $service_user->getLimitRoomList($where,'',$field,'a.pigcms_id DESC');
        $data = [
            'list' => $list
        ];
        return api_output(0,$data);
    }

    /**
     * 业主欠费列表页
     * @param 传参
     * array (
     *  'village_id'=> '小区id,从搜索页面选择后必传',
     *  'pigcms_id'=> '业主绑定id,从搜索页面选择后必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/25 13:54
     * @return \json
     */
    public function personCashierOrder() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        if (!$this->config['site_url']) {
            // 初始化 配置 业务层
            $service_config = new ConfigService();
            $where_config = [];
            $where_config[] = ['name', '=', "site_url"];
            $config = $service_config->get_config_list($where_config);
            $this->config['site_url'] = $config['site_url'];
        }
        $site_url = $this->config['site_url'];
        $static_resources = static_resources(true);

        $where = [];
        $where['village_id'] = $village_id;
        $where['order_type'] = ['custom','custom_payment','property'];
        $where['paid'] = 0; //未付款
        $where['cashier_id'] = 0;
        $where['bind_id'] = $pigcms_id;
        $service_order = new HouseVillageOrderService();
        $list = $service_order->getOrderList($where,0);

        if ($list && $list['pay_list_order']) {
            foreach($list['pay_list_order'] as &$val) {
                $val['icon_img'] = $site_url . $static_resources . 'images/cashier/'.$val['type'].'.png';
            }
        }

        $totalmoney = $list['totalmoney'];

        //小区信息
        $service_house_village = new HouseVillageService();
        $now_village = $service_house_village->getHouseVillage($village_id);
        //当前业主信息
        $service_user = new HouseVillageUserService();
        $field_bind = 'pigcms_id,village_id,uid,usernum,bind_number,name,phone,water_price,electric_price,gas_price,park_price,address,single_id,layer_id,floor_id,vacancy_id,property_endtime';
        $now_user_info = $service_user->getHouseUserBind($pigcms_id,$field_bind);
        $now_user_info['address'] = $service_house_village->getSingleFloorRoom($now_user_info['single_id'],$now_user_info['floor_id'],$now_user_info['layer_id'],$now_user_info['vacancy_id'],$now_user_info['village_id']);
        $pay_list_type = $service_order->pay_list_type();

        //缴费
        $pay_list = array();
        $now_village['property_price'] = get_number_format($now_village['property_price'],2);
        $now_village['water_price'] = get_number_format($now_village['water_price'],2);
        $now_village['electric_price'] = get_number_format($now_village['electric_price'],2);
        $now_village['gas_price'] = get_number_format($now_village['gas_price'],2);
        $now_village['park_price'] = get_number_format($now_village['park_price'],2);

        // 水费
        if($now_village['water_price'] && isset($now_user_info['water_price']) && $now_user_info['water_price']>0){
            $totalmoney += floatval($now_user_info['water_price']);
            $pay_list[] = array(
                'type' => 'water',
                'name' => $pay_list_type['water'],
                'money'=>get_number_format($now_user_info['water_price'],2),
                'icon_img'=>$site_url . $static_resources . 'images/cashier/water.png',
            );
        }
        // 电费
        if($now_village['electric_price'] && isset($now_user_info['electric_price']) && $now_user_info['electric_price']>0){
            $totalmoney += floatval($now_user_info['electric_price']);
            $pay_list[] = array(
                'type' => 'electric',
                'name' => $pay_list_type['electric'],
                'money'=>get_number_format($now_user_info['electric_price'],2),
                'icon_img'=>$site_url . $static_resources . 'images/cashier/electric.png',
            );
        }
        // 燃气费
        if($now_village['gas_price'] && isset($now_user_info['gas_price']) && $now_user_info['gas_price']>0){
            $totalmoney += floatval($now_user_info['gas_price']);
            $pay_list[] = array(
                'type' => 'gas',
                'name' => $pay_list_type['gas'],
                'money'=>get_number_format($now_user_info['gas_price'],2),
                'icon_img'=>$site_url . $static_resources . 'images/cashier/gas.png',
            );
        }
        // 停车费
        if($now_village['park_price'] && isset($now_user_info['park_price']) && $now_user_info['park_price']>0){
            $totalmoney += floatval($now_user_info['park_price']);
            $pay_list[] = array(
                'type' => 'park',
                'name' => $pay_list_type['park'],
                'money'=>get_number_format($now_user_info['park_price'],2),
                'icon_img'=>$site_url . $static_resources . 'images/cashier/park.png',
            );
        }
        // 计算欠缴物业费
        $service_house_village_property_fee = new HouseVillagePropertyFeeService();
        $standard_info = $service_house_village_property_fee->getBindStandard(['pigcms_id'=>$now_user_info['vacancy_id'],'is_del'=>1],'standard_id');
        if(!$standard_info['standard_id']){
            $property_price = 0;
        }elseif ($now_user_info['property_endtime'] && $now_user_info['property_endtime'] < time()){
            $service_house_village = new HouseVillageService();
            $bind_info = $service_house_village->getRoomInfoWhere(['pigcms_id'=>$now_user_info['vacancy_id']],'housesize');
            $date = $service_house_village_property_fee->getStandard(['id'=>$standard_info['standard_id']],'date_month');
            $num = $this->getMonthNum($now_user_info['property_endtime'] + 1, time());
            if(date('d',time()) > date('d',$now_user_info['property_endtime'] + 1))
                $num+=1;
            $start_time = strtotime(date('Y-m',$now_user_info['property_endtime']));
            $dateMonth[] = $start_time;
            for ($i=1;$i<$num;$i++){
                $dateMonth[] = strtotime("+ $i month",$start_time);
            }
            $property_price = $this->property_price($dateMonth,unserialize($date['date_month']),$bind_info['housesize']);
        }else{
            $property_price = 0;
        }
        if ($standard_info['standard_id'] && $property_price>0) {
            $totalmoney += round(floatval($property_price), 2)?round(floatval($property_price), 2):0;
            $pay_list[] = array(
                'type' => 'property',
                'name' => $pay_list_type['property'],
                'money' => round(floatval($property_price), 2)?round(floatval($property_price), 2):0,
                'property_month_num'=> isset($num)?$num:0,
                'icon_img'=>$site_url . $static_resources . 'images/cashier/property.png',
            );
        }

        // 自定义缴费
        if($now_village['has_custom_pay']){
            $service_house_village_payment = new HouseVillagePaymentService();
            $payment_list = $service_house_village_payment->CashierPaymentList(['psb.pigcms_id'=>$now_user_info['pigcms_id'],'psb.village_id'=>$village_id],'psb.*,ps.*,p.*,psb.payment_id as psb_payment_id,ps.payment_id as ps_payment_id');

            // 车位缴费
            //$position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$now_user_info['pigcms_id']));

            //$payment_list = array_merge($payment_list, $position_payment_list);
            $cycle_type_china = array(
                'Y'=>'年',
                'M'=>'月',
                'D'=>'日',
            );
            $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
            foreach ($payment_list as $kk => $vv) {
                if(!$vv['payment_id']){
                    unset($payment_list[$kk]);
                    continue;
                }
                if ($vv['cycle_sum'] - $vv['paid_cycle'] <= 0) {
                    unset($payment_list[$kk]);
                    continue;
                }
                $payment_list[$kk]['start_time'] = date('Y-m-d',$vv['start_time']);
                // 计算结束时间
                $vv['end_time'] = strtotime(date('Y-m-d',$vv['start_time']).'+'.$vv['cycle_sum']*$vv['pay_cycle'].$cycle_type[$vv['cycle_type']]);

                $payment_list[$kk]['end_time'] = date('Y-m-d',$vv['end_time']);
                $payment_list[$kk]['cycle_type'] = $cycle_type_china[$vv['cycle_type']];
                if (isset($vv['garage_num']) && $vv['garage_num']) {
                    $payment_list[$kk]['payment_name'] = $vv['payment_name'].'('.$vv['garage_num'].'-'.$vv['position_num'].')';
                }
                switch ($vv['cycle_type']) {
                    case 'Y':
                        $end_time = $vv['start_time'] + $vv['pay_cycle']*$vv['paid_cycle']*86400*365;
                        break;
                    case 'M':
                        $end_time = $vv['start_time'] + $vv['pay_cycle']*$vv['paid_cycle']*86400*30;
                        break;
                    case 'D':
                        $end_time = $vv['start_time'] + $vv['pay_cycle']*$vv['paid_cycle']*86400;
                        break;
                }
                if($end_time < time()){
                    $num = $service_house_village_payment->getTimeNum($end_time, time(), $vv['cycle_type']);
                    if ($num>0 && $vv['pay_cycle']) {
                        $num = ceil($num / $vv['pay_cycle']);
                    } else {
                        $num = 0;
                    }
                    $num = min($num, $vv['cycle_sum']);
                    if($vv['pay_type'] == 1){
                        $money = round($num * $vv['pay_money'],2);
                    }else{
                        $money = round($vv['metering_mode_val'] * $vv['pay_money']*$num,2);
                    }
                }else{
                    continue;
                }
                $totalmoney += $money;
                $pay_list[] = array(
                    'type' => 'custom_payment',
                    'payment_bind_id'=>$vv['bind_id'],
                    'payment_paid_cycle'=>$vv['cycle_sum'],
                    'name'=>$vv['payment_name'],
                    //'url' => U('House/village_pay',array('village_id'=>$now_village['village_id'],'type'=>'custom_payment')),
                    'money'=> $money,
                    'icon_img'=>$site_url . $static_resources . 'images/cashier/custom_payment.png',
                );
            }
        }

        $list['pay_list'] = $pay_list;
        $list['user_info'] = $now_user_info;
        $list['totalmoney'] = get_number_format($totalmoney,2);
        return api_output(0,$list);
    }

    /**
     * 获取物业优惠列表
     * @author lijie
     * @date_time 2020/11/17
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function preferentialList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)) {
            return api_output(1001,[],'缺少查询对象！');
        }
        $service_user = new HouseVillageUserService();
        // 当前业主信息
        $bind_where['pigcms_id'] = $pigcms_id;
        $now_bind_info = $service_user->getHouseUserBindWhere($bind_where,'floor_id,housesize,vacancy_id');
        if (!$now_bind_info) {
            return api_output(1003,[],'当前小区不存在！');
        }
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];
        $service_order = new HouseVillageOrderService();
        $list = $service_order->get_house_village_property_list_where($where, true, 'property_month_num desc',$now_bind_info['vacancy_id']);
        if ($list['list']) {
            foreach($list['list'] as &$val) {
                if(isset($list['property_unit_fee'])){
                    $val['property_fee'] = number_format($now_bind_info['housesize'] * $val['property_month_num'] * $list['property_unit_fee'],2);
                }else{
                    $val['property_fee'] = 0;
                }
            }
        }
        return api_output(0,$list);
    }

    /**
     * 添加收费订单
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'type'=> '缴费类型 必传',// property 物业费 water 水费
     *  'pigcms_id'=> '用户小区绑定id 必传',
     *  'remarks'=> '小区id 必传',
     *  'property_id'=> '小区id 必传',
     *  'payment_price'=> '自定义缴费项金额 type为custom_payment时候必传',
     *  'payment_name'=> '自定义缴费项名称 type为custom_payment时候必传',
     *  'payment_paid_cycle'=> '自定义缴费项周期数 type为custom_payment时候必传',
     *  'payment_bind_id'=> '自定义缴费项id type为custom_payment时候必传',
     *  'custom_price'=> '临时自定义缴费金额 type为custom时候必传',
     *  'custom_remark'=> ''临时自定义缴费名称 type为custom时候必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/26 20:15
     * @return \json
     */
    public function ownerOrderAdd() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(67,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        // 缴费类型
        $type = $this->request->param('type','','trim');
        if (!$type) {
            return api_output(1003,[],'缺少缴费对象信息！');
        }
        // 业主
        $pigcms_id = $this->request->param('pigcms_id','','trim');
        if (!$pigcms_id) {
            return api_output(1003,[],'缺少业主id！');
        }
        // 备注
        $remarks = $this->request->param('remarks','','trim');

        $service_user = new HouseVillageUserService();
        // 当前业主信息
        $bind_where['pigcms_id'] = $pigcms_id;
        $now_bind_info = $service_user->getHouseUserBindWhere($bind_where);
        //小区信息
        $service_house_village = new HouseVillageService();
        $now_village = $service_house_village->getHouseVillage($village_id);
        if (!$now_bind_info) {
            return api_output(1003,[],'当前小区不存在！');
        }

        if (!$this->config['Currency_symbol']) {
            // 初始化 配置 业务层
            $service_config = new ConfigService();
            $where_config = [];
            $where_config[] = ['name', '=', "Currency_symbol"];
            $config = $service_config->get_config_list($where_config);
            $this->config['Currency_symbol'] = $config['Currency_symbol'];
        }

        if('property'==$type) {
            // 物业缴费周期
            $property_id = $this->request->param('property_id','','intval');
            if (!$property_id) {
                return api_output(1003,[],'缺少物业缴费周期！');
            }
            //单元信息
            $where_floor = [];
            $where_floor[] = ['floor_id', '=', $now_bind_info['floor_id']];
            $now_floor_info = $service_house_village->getHouseVillageFloorWhere($where_floor);

            $property_where['id'] = $property_id;
            $service_order = new HouseVillageOrderService();
            $now_property_info = $service_order->get_house_village_property_where($property_where);
            if (!$now_property_info) {//物业缴费周期不存在
                return api_output(1003,[],'物业缴费周期不存在！');
            }
            $data['order_name']         = '物业费';
            $data['order_type']         = 'property';
            $data['village_id']         = $village_id;
            $data['time']               = time();
            $data['property_month_num'] = $now_property_info['property_month_num'];
            $data['floor_type_name']    = $now_floor_info['name'] ? $now_floor_info['name'] : '';
            $data['house_size']         = $now_bind_info['housesize'];
            $data['bind_id']            = $now_bind_info['pigcms_id'];
            $data['uid']                = $now_bind_info['uid'];
            $data['diy_type']           = $now_property_info['diy_type'];
            if ($now_property_info['diy_type'] > 0) {
                $data['diy_content'] = $now_property_info['diy_content'];
            } else {
                $data['presented_property_month_num'] = $now_property_info['presented_property_month_num'] ? $now_property_info['presented_property_month_num'] : 0;
            }
            $service_house_village_property_fee_service = new HouseVillagePropertyFeeService();
            $standard_bind_info = $service_house_village_property_fee_service->getBindStandard(['pigcms_id'=>$now_bind_info['vacancy_id'],'is_del'=>1],'standard_id');
            $standard_info = $service_house_village_property_fee_service->getStandard(['id'=>$standard_bind_info['standard_id']],'date_month');
            $property_fee = $service_house_village_property_fee_service->getNowPropertyFee(unserialize($standard_info['date_month']),time());
            $data['money'] = round($property_fee*$now_bind_info['housesize'] * $now_property_info['property_month_num'],2);
            $data['property_fee'] = $property_fee;
            $data['remarks'] = $remarks;
            $order_id = $service_order->add_house_village_pay_order($data);
            if ($order_id) {
                $order['order_id'] = $order_id;
                return api_output(0,$order,'添加成功！');
            } else {
                return api_output(1003,[],'添加失败！');
            }

        }else{
            $service_order = new HouseVillageOrderService();
            switch($type){
                case 'water':
                    if(empty($now_village['water_price'])) {
                        return api_output(1003,[],'当前小区不支持缴纳水费！');
                    }
                    $pay_money = $now_bind_info['water_price'];
                    $where_type = [];
                    $where_type[] = ['usernum','=',$now_bind_info['usernum']];
                    $field = 'ydate,mdate,use_water AS use,water_price AS price';
                    $order_list = $service_order->get_house_village_user_paylist_where($where_type,$field,'`pigcms_id` DESC');
                    foreach($order_list as $key=>$value){
                        $order_list[$key]['desc']  = '用水 '.floatval($value['use']).' 立方米，总费用 '.$this->config['Currency_symbol'] . floatval($value['price']);
                    }
                    $data_order['order_name'] = '水费';
                    break;
                case 'electric':
                    if(empty($now_village['electric_price'])) {
                        return api_output(1003,[],'当前小区不支持缴纳电费！');
                    }
                    $pay_money = $now_bind_info['electric_price'];
                    $where_type = [];
                    $where_type[] = ['usernum','=',$now_bind_info['usernum']];
                    $field = 'ydate,mdate,use_electric AS use,electric_price AS price';
                    $order_list = $service_order->get_house_village_user_paylist_where($where_type,$field,'`pigcms_id` DESC');
                    foreach($order_list as $key=>$value){
                        $order_list[$key]['desc'] = '用电 '.floatval($value['use']).' 千瓦时(度)，总费用 '.$this->config['Currency_symbol'] . floatval($value['price']);
                    }
                    $data_order['order_name'] = '电费';
                    break;
                case 'gas':
                    if(empty($now_village['gas_price'])) {
                        return api_output(1003,[],'当前小区不支持缴纳燃气费！');
                    }
                    $pay_money = $now_bind_info['gas_price'];
                    $where_type[] = ['usernum','=',$now_bind_info['usernum']];
                    $field = 'ydate,mdate,use_gas AS use,gas_price AS price';

                    $order_list = $service_order->get_house_village_user_paylist_where($where_type,$field,'`pigcms_id` DESC');

                    foreach($order_list as $key=>$value){
                        $order_list[$key]['desc'] = '使用燃气 '.floatval($value['use']).' 立方米，总费用 '.$this->config['Currency_symbol'] . floatval($value['price']);
                    }
                    $data_order['order_name'] = '燃气费';
                    break;
                case 'park':
                    if(empty($now_village['park_price'])) {
                        return api_output(1003,[],'当前小区不支持缴纳停车费！');
                    }
                    $pay_money = $now_bind_info['park_price'];
                    $where_type[] = ['usernum','=',$now_bind_info['usernum']];
                    $field = 'ydate,mdate,park_price AS price';

                    $order_list = $service_order->get_house_village_user_paylist_where($where_type,$field,'`pigcms_id` DESC');

                    foreach($order_list as $key=>$value){
                        $order_list[$key]['desc'] = '停车费 '.$this->config['Currency_symbol'] . floatval($value['price']);
                    }
                    $data_order['order_name'] = '停车费';
                    break;
                case 'custom_payment':
                    // 自定义缴费项金额
                    $pay_money = $this->request->param('payment_price','','floatval');
                    // 自定义缴费项名称
                    $order_name = $this->request->param('payment_name','','trim');
                    // 自定义缴费项周期数
                    $payment_paid_cycle = $this->request->param('payment_paid_cycle','','trim');
                    // 自定义缴费项id
                    $payment_bind_id = $this->request->param('payment_bind_id','','intval');
                    if(!$pay_money || !$order_name || !$payment_paid_cycle || !$payment_bind_id) {
                        return api_output(1001,[],'请完善必填项！');
                    }

                    $data_order['order_name'] = $order_name;
                    $data_order['payment_paid_cycle'] = $payment_paid_cycle;
                    $data_order['payment_bind_id'] = $payment_bind_id;
                    break;
                case 'custom':
                    // 自定义缴费金额
                    $pay_money = $this->request->param('custom_price','','floatval');
                    // 自定义缴费名称
                    $custom_remark = $this->request->param('custom_remark','','trim');
                    if(!$custom_remark) {
                        return api_output(1001,[],'请完善必填项！');
                    }
                    $data_order['order_name'] = $custom_remark;
                    break;
                default:
                    return api_output(1003,[],'当前小区不支持当前缴费方式！');
                    break;
            }

            $data_order['money'] = $pay_money ;
            $data_order['uid'] = $now_bind_info['uid'];
            $data_order['bind_id'] = $now_bind_info['pigcms_id'];
            $data_order['village_id'] = $now_village['village_id'];
            $data_order['time'] = $_SERVER['REQUEST_TIME'];
            $data_order['paid'] = 0;
            $data_order['order_type'] = $type;
            $data_order['remarks'] = $remarks;

            $order_id = $service_order->add_house_village_pay_order($data_order);
            if ($order_id) {
                $order['order_id'] = $order_id;
                return api_output(0,$order,'添加成功！');
            } else {
                return api_output(1003,[],'添加失败！');
            }
        }
    }

    /**
     * 添加临时收费总订单
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'orderids'=> '收费订单id集合 必传 数组集合',//如果选中了水电燃停车费请使用 缴费类型|缴费金额 例如 water|10     'custom|101,custom|102' 或者 [101=>'custom|101',102=>'']  订单类型|订单id    person_cashier_order接口的订单类型(type)|对应的订单id(order_id)
     *  'pigcms_id'=> '小区业主绑定id 必传', // 业主绑定表格或者业主信息中的pigcms_id
     *  'village_id'=> '小区id 必传',
     *  'money'=> '需要支付总金额 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/26 20:15
     * @return \json
     */
    public function addTemporaryOrder() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        // 判断如果是数组 将其解析成 英文逗号分隔的字符串
        $orderids = $this->request->param('orderids','');
        if (is_array($orderids)) {
            $orderids = implode(",", $orderids);
        }
        // 小区业主绑定id
        $bind_id = $this->request->param('pigcms_id','','intval');
        // 小区id
        $village_id = $this->request->param('village_id','','intval');
        // 需要支付总金额
        $money = $this->request->param('money','','floatval');
        if (!$orderids || !$bind_id || !$village_id || !$money) {
            return api_output(1001,[],'缺少不要信息！');
        }
        $service_order = new HouseVillageOrderService();
        $data = [
            'orderids' => $orderids,
            'bind_id' => $bind_id,
            'village_id' => $village_id,
            'money' => $money,
            'add_time' => time()
        ];
        $temporary_order_id = $service_order->add_temporary_order($data);
        if ($temporary_order_id) {
            $temporary_order = [];
            $temporary_order['temporary_order_id'] = $temporary_order_id;
            return api_output(0,$temporary_order,'添加成功！');
        } else {
            return api_output(1003,[],'添加失败！');
        }
    }


    /**
     * 获得临时收费总订单
     * @param 传参
     * array (
     *  'temporary_order_id'=> '临时订单id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/27 14:42
     * @return \json
     */
    public function getTemporaryOrder() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        $temporary_order_id= $this->request->param('temporary_order_id','','intval');
        if (!$temporary_order_id) {
            return api_output(1001,[],'缺少不要信息！');
        }
        $service_order = new HouseVillageOrderService();

        $where = [];
        $where[] = ['a.temporary_order_id','=',$temporary_order_id];
        $where[] = ['a.village_id','=',$village_id];
        $temporary_order = $service_order->get_temporary_order($where,'a.*,b.name');
        if ($temporary_order) {
            return api_output(0,$temporary_order);
        } else {
            return api_output(1003,[],'该临时订单不存在！');
        }
    }

    /**
     * 线下支付类型
     * @param 传参
     * array (
     *  'village_id'=> '社区id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/27 15:44
     * @return \json
     */
    public function payTypeList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $service_order = new HouseVillageOrderService();

        $where = [];
        $where[] = ['village_id','=',$village_id];
        $pay_type = $service_order->get_house_village_pay_type_where($where);
        $pay_arr = [
            'list' => $pay_type,
            'list_arr' => array_values($pay_type)
        ];
        return api_output(0,$pay_arr);
    }

    /**
     * 支付
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '小区绑定id 必传',
     *  'real_money'=> '实际收的金额 必传',
     *  'temporary_order_id'=> '临时订单id 选传',
     *  'orderids'=> '订单id集合 选传 和temporary_order_id 必传一个 ' ,// 'custom|101,custom|102' 或者 [101=>'custom|101',102=>'']
     *  'pay_type'=> '支付类型id',
     *  'remarks'=> '备注',
     *  'is_online'=> '是否线上支付 0 线下支付  1 线上支付',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/27 15:44
     * @return \json
     */
    public function doCashier() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        // 业主绑定id 即临时总订单中 bind_id
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)) {
            return api_output(1001,[],'缺少对应业主！');
        }
        $real_money = $this->request->param('real_money','','floatval');
        if (empty($pigcms_id)) {
            return api_output(1001,[],'缺少实际收款金额！');
        }
        $temporary_order_id = $this->request->param('temporary_order_id','','intval');
        $orderids = $this->request->param('orderids');
        $service_order = new HouseVillageOrderService();
        if (empty($orderids) && $temporary_order_id) {
            $where = [];
            $where[] = ['a.temporary_order_id','=',$temporary_order_id];
            $temporary_order = $service_order->get_temporary_order($where,'a.*');
            $orderids = $temporary_order['orderids'];
        }
        if (empty($orderids)) {
            return api_output(1001,[],'缺少对应订单！');
        }
        $is_online = $this->request->param('is_online',0,'intval');
        $pay_type = $this->request->param('pay_type','0','intval');
        if ($is_online==0 && !$pay_type) {
            return api_output(1001,[],'缺少线下支付方式！');
        }
        $remarks = $this->request->param('remarks','','trim');


        $service_house_village = new HouseVillageService();
        $service_user = new HouseVillageUserService();
        // 小区信息
        $now_village = $service_house_village->getHouseVillage($village_id);
        // 当前业主信息
        $bind_where['pigcms_id'] = $pigcms_id;
        $now_bind_info = $service_user->getHouseUserBindWhere($bind_where);
        if (!$now_bind_info) {
            return api_output(1003,[],'缺少对应业主！');
        }
        //缴费项
        $order_arr = explode(',',trim($orderids,','));
        // 所有订单id
        $aOrderId = array();
        //需要添加的订单数据
        $aDataOrder = array();
        //总金额
        $totalmoney = 0;

        // 验证自定义缴费是否已缴完毕
        $custom_payment_order = array();
        $custom_payment_ids = array();

        // 循环处理下
        $service_order = new HouseVillageOrderService();
        $pay_list_type = $service_order->pay_list_type();
        foreach ($order_arr as $key => $order) {
            $aOrder = explode('|', $order);
            switch ($aOrder[0]) {
                case 'custom_payment':  // 自定义缴费标
                    if(isset($aOrder[2])){
                        $aDataOrder[] = array(
                            'money' => $aOrder[3],
                            'uid' => $now_bind_info['uid'],
                            'bind_id' => $now_bind_info['pigcms_id'],
                            'village_id' => $village_id,
                            'time' => $_SERVER['REQUEST_TIME'],
                            'paid' => 0,
                            'payment_bind_id'=>$aOrder[1],
                            'payment_paid_cycle'=>$aOrder[2],
                            'order_type' => $aOrder[0],
                            'order_name' => '自定义缴费',
                            'remarks' => $this->request->post('remarks',''),
                        );
                        $totalmoney += $aOrder[3];
                    }else{
                        // 已有订单
                        if(!$aOrder[1]){
                            return api_output(1001,[],'传递参数有误！');
                        }
                        $where_order = [];
                        $where_order[] = ['order_id', '=', $aOrder[1]];
                        $order_info = $service_order->get_house_village_pay_order_where($where_order);
                        if ($order_info) {
                            $totalmoney += $order_info['money'];
                            $aOrderId[] = $aOrder[1];
                        }

                        // 验证自定义缴费是否已缴完毕
                        $custom_payment_ids[] = $order_info['payment_bind_id'];
                        if ($custom_payment_order && $order_info['payment_bind_id'] && isset($custom_payment_order[$order_info['payment_bind_id']])) {
                            $custom_payment_order[$order_info['payment_bind_id']]['paid_cycle'] += $order_info['payment_paid_cycle'];
                        }else{
                            $custom_payment_order[$order_info['payment_bind_id']]['paid_cycle'] = $order_info['payment_paid_cycle'];
                            $custom_payment_order[$order_info['payment_bind_id']]['order_name'] = $order_info['order_name'];
                        }
                    }

                    break;
                case 'property': // 物业费
                    if(!isset($aOrder[1])){
                        // 计算欠缴物业费
                        $service_house_village_property_fee = new HouseVillagePropertyFeeService();
                        $standard_info = $service_house_village_property_fee->getBindStandard(['pigcms_id'=>$now_bind_info['vacancy_id'],'is_del'=>1],'standard_id');
                        if(!$standard_info['standard_id']){
                            $property_price = 0;
                        }elseif ($now_bind_info['property_endtime'] && $now_bind_info['property_endtime'] < time()){
                            $service_house_village = new HouseVillageService();
                            $bind_info = $service_house_village->getRoomInfoWhere(['pigcms_id'=>$now_bind_info['vacancy_id']],'housesize');
                            $date = $service_house_village_property_fee->getStandard(['id'=>$standard_info['standard_id']],'date_month');
                            $num = $this->getMonthNum($now_bind_info['property_endtime'] + 1, time());
                            if(date('d',time()) > date('d',$now_bind_info['property_endtime'] + 1))
                                $num+=1;
                            $start_time = strtotime(date('Y-m',$now_bind_info['property_endtime']));
                            $dateMonth[] = $start_time;
                            for ($i=1;$i<$num;$i++){
                                $dateMonth[] = strtotime("+ $i month",$start_time);
                            }
                            $property_price = $this->property_price($dateMonth,unserialize($date['date_month']),$bind_info['housesize']);
                        }else{
                            $property_price = 0;
                        }
                        $aDataOrder[] = array(
                            'money' => $property_price,
                            'uid' => $now_bind_info['uid'],
                            'bind_id' => $now_bind_info['pigcms_id'],
                            'village_id' => $village_id,
                            'time' => $_SERVER['REQUEST_TIME'],
                            'property_month_num'=>isset($num)?$num:0,
                            'paid' => 0,
                            'order_type' => $aOrder[0],
                            'order_name' => $pay_list_type['property'],
                            'remarks' => $remarks,
                        );
                    }else{
                        $aOrderId[] = $aOrder[1];
                    }
                    break;
                case 'custom': // 自定义缴费
                    // 已有订单
                    if(!isset($aOrder[1])){
                        return api_output(1001,[],'传递参数有误！');
                    }
                    $where_order = [];
                    $where_order[] = ['order_id', '=', $aOrder[1]];
                    $order_info = $service_order->get_house_village_pay_order_where($where_order);
                    if ($order_info) {
                        $totalmoney += $order_info['money'];
                        $aOrderId[] = $aOrder[1];
                    }
                    break;

                case 'water':
                    if(empty($now_village['water_price']))
                        return api_output(1003,[],'当前小区不支持缴纳水费！');

                    $totalmoney += $now_bind_info['water_price'];
                    $aDataOrder[] = array(
                        'money' => $now_bind_info['water_price'],
                        'uid' => $now_bind_info['uid'],
                        'bind_id' => $now_bind_info['pigcms_id'],
                        'village_id' => $village_id,
                        'time' => $_SERVER['REQUEST_TIME'],
                        'paid' => 0,
                        'order_type' => $aOrder[0],
                        'order_name' => $pay_list_type['water'],
                        'remarks' => $remarks,
                    );
                    break;
                case 'electric':
                    if(empty($now_village['electric_price'])){
                        return api_output(1003,[],'当前小区不支持缴纳电费！');
                    }

                    $totalmoney += $now_bind_info['electric_price'];
                    $aDataOrder[] = array(
                        'money' => $now_bind_info['electric_price'],
                        'uid' => $now_bind_info['uid'],
                        'bind_id' => $now_bind_info['pigcms_id'],
                        'village_id' => $village_id,
                        'time' => $_SERVER['REQUEST_TIME'],
                        'paid' => 0,
                        'order_type' => $aOrder[0],
                        'order_name' => $pay_list_type['electric'],
                        'remarks' => $remarks,
                    );
                    break;
                case 'gas':
                    if(empty($now_village['gas_price'])){
                        return api_output(1003,[],'当前小区不支持缴纳燃气费！');
                    }

                    $totalmoney += $now_bind_info['gas_price'];
                    $aDataOrder[] = array(
                        'money' => $now_bind_info['gas_price'],
                        'uid' => $now_bind_info['uid'],
                        'bind_id' => $now_bind_info['pigcms_id'],
                        'village_id' => $village_id,
                        'time' => $_SERVER['REQUEST_TIME'],
                        'paid' => 0,
                        'order_type' => $aOrder[0],
                        'order_name' => $pay_list_type['gas'],
                        'remarks' => $remarks,
                    );
                    break;
                case 'park':
                    if(empty($now_village['park_price'])){
                        return api_output(1003,[],'当前小区不支持缴停车费！');
                    }

                    $totalmoney += $now_bind_info['park_price'];
                    $aDataOrder[] = array(
                        'money' => $now_bind_info['park_price'],
                        'uid' => $now_bind_info['uid'],
                        'bind_id' => $now_bind_info['pigcms_id'],
                        'village_id' => $village_id,
                        'time' => $_SERVER['REQUEST_TIME'],
                        'paid' => 0,
                        'order_type' => $aOrder[0],
                        'order_name' => $pay_list_type['park'],
                        'remarks' => $remarks,
                    );
                    break;
            }
        }
        // 验证自定义缴费是否已缴完毕
        if ($custom_payment_ids) {
            $where_bind = [];
            $where_bind[] = ['bind_id', 'in', $custom_payment_ids];
            $custom_payment_order_list = $service_order->get_village_payment_standard_bind_list($where_bind);
            foreach ($custom_payment_order_list as $key => $value) {
                $unpay_cycle = max(0,$value['cycle_sum'] - $value['paid_cycle']);
                if ($unpay_cycle < $custom_payment_order[$value['bind_id']]['paid_cycle'] ) {
                    return api_output(1001,[],"缴费项{$custom_payment_order[$value['bind_id']]['order_name']}缴费周期不能大于未缴周期，未缴周期为{$unpay_cycle}周期！");
                }
            }
        }
        //生成总订单
        $data = array(
            'pay_type' => $pay_type,
            'money' => $real_money,
            'uid' => $now_bind_info['uid'],
            'pigcms_id' => $now_bind_info['pigcms_id'],
            'village_id' => $village_id,
            'time' => time(),
            'paid' => 0,
            'remarks' => $remarks,
            'role_id' => isset($this->_uid)?$this->_uid:'',
            'role_type' => isset($this->login_role)?$this->login_role:''
        );
        $service_pay_cashier_order = new HouseVillagePayCashierOrderService();
        $cashier_id = $service_pay_cashier_order->add_house_village_pay_cashier_order($data);
        if (!$cashier_id) {
            return api_output(1003,[],'订单生成失败！');
        }

        // 更新物业自定义缴费
        if ($aOrderId) {
            $where_pay_order = [];
            $where_pay_order[] = ['order_id', 'in', $aOrderId];
            $service_order->save_house_village_pay_order($where_pay_order,['cashier_id'=>$cashier_id]);
        }
        // 生成水电燃气停车订单
        if ($aDataOrder) {
            foreach ($aDataOrder as $data_order) {
                $data_order['cashier_id'] = $cashier_id;
                $order_id = $service_order->add_house_village_pay_order($data_order);
                $aOrderId[] = $order_id;
            }
        }

        //扫码支付
        if (!$this->config['site_url']) {
            // 初始化 配置 业务层
            $service_config = new ConfigService();
            $where_config = [];
            $where_config[] = ['name', '=', "site_url"];
            $config = $service_config->get_config_list($where_config);
            $this->config['site_url'] = $config['site_url'];
        }
        if ($is_online == 1 && $real_money > 0) {
            $qrcode_id = $cashier_id + 4200000000;
            $src = $this->config['site_url']."/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id=".$qrcode_id;
            $arr = [
                'cashier_id' => $cashier_id,
                'img_url' => $src,
            ];
            return api_output(0,$arr);
        }else{
            // 支付
            $result = $service_pay_cashier_order->cashier_pay($cashier_id);
            if($result['error_code']==1){
                return api_output(1003,[],$result['msg']);
            }else{
                return api_output(0,['cashier_id'=>$cashier_id]);
            }
        }
    }

    /**
     * 业主历史缴费列表
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '小区绑定id 必传',
     *  'page'=> '分页 必传',// 如果不传默认第一页数据 取第几页数据就传几
     *  'page_size'=> '，每页数量 选传 默认10',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/29 10:09
     * @return \json
     */
    public function userHistoryOrder() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        if (!$pigcms_id) {
            return api_output(1001,[],'缺少业主身份！');
        }
        //当前业主信息
        $service_user = new HouseVillageUserService();
        $field = 'pigcms_id,village_id,uid,usernum,bind_number,name,phone,single_id,floor_id,layer_num,room_addrss,address,layer_id,vacancy_id';
        $now_user_info = $service_user->getHouseUserBind($pigcms_id,$field);
        if (!$now_user_info) {
            return api_output(1001,[],'该业主不存在！');
        }
        $service_house_village = new HouseVillageService();
        $now_user_info['address'] = $service_house_village->getSingleFloorRoom($now_user_info['single_id'],$now_user_info['floor_id'],$now_user_info['layer_id'],$now_user_info['vacancy_id'],$now_user_info['village_id']);
        $page = $this->request->param('page','1','intval');
        $page_size = $this->request->param('page_size','10','intval');

        $service_order = new HouseVillageOrderService();
        $service_village_pay_cashier_order = new HouseVillagePayCashierOrderService();

        $where_type = [];
        $where_type[] = ['village_id','=',$village_id];
        $pay_type = $service_order->get_house_village_pay_type_where($where_type);

        $where = [];
        $where[] = ['a.pigcms_id', '=', $pigcms_id];
        $where[] = ['b.village_id', '=', $village_id];
        $where[] = ['a.paid', '=', 1];
//        $where[] = ['b.name|b.phone', 'like', "%".$search."%"];
        $order='a.cashier_id DESC';
        $field = 'b.name,b.phone,a.cashier_id,a.cashier_id,a.uid,a.pigcms_id,a.village_id,a.money,a.pay_time,a.remarks,a.pay_type';
        $paid_list = $service_village_pay_cashier_order->get_limit_list($where,$field,$page,$order,$page_size);
        $total = 0;

        $service_login = new ManageAppLoginService();
        $base_url = $service_login->get_app_base_url();
        if (!$this->config['site_url']) {
            // 初始化 配置 业务层
            $service_config = new ConfigService();
            $where_config = [];
            $where_config[] = ['name', '=', "site_url"];
            $config = $service_config->get_config_list($where_config);
            $this->config['site_url'] = $config['site_url'];
        }
        $site_url = $this->config['site_url'];

        if($paid_list){
            foreach ($paid_list as &$v){
                $total += $v['money'];                              //本页的总额
                if ($v['pay_type']==0) {
                    $v['pay_type_name'] = '扫码支付';
                } elseif ($pay_type) {
                    foreach ($pay_type as $key => $value) {
                        if ($v['pay_type'] == $value['id']) {
                            $v['pay_type_name'] = $value['name'];
                        }
                    }
                }
                $where_count = [];
                $where_count[] = ['cashier_id','=',$v['cashier_id']];
                $count_info = $service_village_pay_cashier_order->get_house_village_pay_order_count($where_count);
                if (1==$count_info['count']) {
                    // 直接跳转单个详情页面
                    $order_id = $count_info['order_id'];
                    $v['url'] = $site_url . $base_url . "pages/Community/CollectMoney/HistoryPayMoney?cashier_id=".$v['cashier_id'].'&order_id='.$order_id;
                    $v['only_one'] = true;
                    $v['order_id'] = $order_id;
                } else {
                    // 跳转多个信息
                    $v['url'] = $site_url . $base_url . "pages/Community/CollectMoney/allHistoryPayMoney?cashier_id=".$v['cashier_id'];
                    $v['only_one'] = false;
                }
            }
        }

        $list['user_info'] = $now_user_info;
        $list['paid_list'] = $paid_list;
        $list['totalmoney'] = $total;
        return api_output(0,$list);

    }

    /**
     * 业主历史缴费多个详情
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'cashier_id'=> '订单id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/29 14:41
     * @return \json
     */
    public function cashierOrderDetail() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth) && !in_array(83,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        $cashier_id = $this->request->param('cashier_id','','intval');
        if(empty($cashier_id)){
            return api_output(1001,[],'缺少订单ID！');
        }
        $service_village_pay_cashier_order = new HouseVillagePayCashierOrderService();
        $service_order = new HouseVillageOrderService();

        $where = [];
        $where[] = ['cashier_id', '=', $cashier_id];
        //查询总订单
        $field = 'cashier_id,pay_time,pay_type,paid,remarks,money,uid,pigcms_id,village_id';
        $cashier_order = $service_village_pay_cashier_order->get_house_village_pay_cashier_order($where,$field);
        if (!$cashier_order) {
            return api_output(1003,[],'订单不存在！');
        }
        if ($cashier_order['pay_time']) {
            $cashier_order['pay_time'] = date('Y-m-d H:i:s',$cashier_order['pay_time']);
        }
        $where_type = [];
        $where_type[] = ['village_id','=',$village_id];
        $pay_type_arr = $service_order->get_house_village_pay_type_where($where_type);
        if ($cashier_order['pay_type']==0) {
            $cashier_order['pay_type_name'] = '扫码支付';
        } elseif ($pay_type_arr && $pay_type_arr[$cashier_order['pay_type']]) {
            $cashier_order['pay_type_name'] = $pay_type_arr[$cashier_order['pay_type']]['name'];
        }

        $where_order = [];
        $where_order[] = ['cashier_id', '=', $cashier_id];
        $field_order = 'order_id,order_name,order_type,money,time';
        $order_list = $service_village_pay_cashier_order->get_house_village_pay_order_list($where_order,$field_order);
        $totalmoney = 0;
        $site_url = cfg('site_url');
        foreach ($order_list as $k=>&$value) {
            switch ($value['order_type']){
                case 'water':
                    $order_list[$k]['icon'] = $site_url.'/v20/public/static/community/images/cashier/water.png';
                    break;
                case 'electric':
                    $order_list[$k]['icon'] = $site_url.'/v20/public/static/community/images/cashier/electric.png';
                    break;
                case 'property':
                    $order_list[$k]['icon'] = $site_url.'/v20/public/static/community/images/cashier/property.png';
                    break;
                case 'gas':
                    $order_list[$k]['icon'] = $site_url.'/v20/public/static/community/images/cashier/gas.png';
                    break;
                case 'custom':
                    $order_list[$k]['icon'] = $site_url.'/v20/public/static/community/images/cashier/custom.png';
                    break;
                default:
                    $order_list[$k]['icon'] = $site_url.'/v20/public/static/community/images/cashier/custom.png';
            }
            $value['money'] = number_format($value['money'],2,".","");
            $value['time'] = date('Y-m-d H:i:s',$value['time']);
            $totalmoney += $value['money'];
        }

        //当前业主信息
        $service_user = new HouseVillageUserService();
        $field = 'pigcms_id,village_id,uid,usernum,bind_number,name,single_id,floor_id,layer_num,room_addrss,address';
        $now_user_info = $service_user->getHouseUserBind($cashier_order['pigcms_id'],$field);
        if (!$now_user_info) {
            $now_user_info = [];;
        }
        // 实际金额
        $cashier_order['real_money'] = number_format(get_format_number($totalmoney),2,".","");

        $cashier_order['order_list'] = $order_list;
        $cashier_order['user_info'] = $now_user_info;
        if(!$cashier_order){
            return api_output(1003,[],'订单信息不存在！');
        }else{
            return api_output(0,$cashier_order);
        }
    }

    /**
     * 业主历史缴费多个详情
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'cashier_id'=> '总订单id 必传',
     *  'order_id'=> '订单id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/29 14:41
     * @return \json
     */
    public function payOrderDetail() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(66,$this->auth) && !in_array(83,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        $cashier_id = $this->request->param('cashier_id','','intval');
        if(empty($cashier_id)){
            return api_output(1001,[],'缺少订单ID！');
        }
        $order_id = $this->request->param('order_id','','intval');
        if(empty($order_id)){
            return api_output(1001,[],'缺少订单ID！');
        }
        $service_village_pay_cashier_order = new HouseVillagePayCashierOrderService();
        $service_order = new HouseVillageOrderService();

        $where = [];
        $where[] = ['cashier_id', '=', $cashier_id];
        //查询总订单
        $field = 'cashier_id,pay_time,pay_type,paid,remarks,money,uid,pigcms_id,village_id';
        $cashier_order = $service_village_pay_cashier_order->get_house_village_pay_cashier_order($where,$field);
        if (!$cashier_order) {
            return api_output(1003,[],'订单不存在！');
        }
        if ($cashier_order['pay_time']) {
            $cashier_order['pay_time'] = date('Y-m-d H:i:s',$cashier_order['pay_time']);
        }
        $where_type = [];
        $where_type[] = ['village_id','=',$village_id];
        $pay_type_arr = $service_order->get_house_village_pay_type_where($where_type);
        if ($cashier_order['pay_type']==0) {
            $cashier_order['pay_type_name'] = '扫码支付';
        } elseif ($pay_type_arr && $pay_type_arr[$cashier_order['pay_type']]) {
            $cashier_order['pay_type_name'] = $pay_type_arr[$cashier_order['pay_type']]['name'];
        }

        $where_order = [];
        $where_order[] = ['cashier_id', '=', $cashier_id];
        $where_order[] = ['order_id', '=', $order_id];
        $field_order = 'order_id,order_name,order_type,money';
        $order_info = $service_order->get_house_village_pay_order_where($where_order,$field_order);


        //当前业主信息
        $service_user = new HouseVillageUserService();
        $field = 'pigcms_id,village_id,uid,usernum,bind_number,name,single_id,floor_id,layer_num,room_addrss,address';
        $now_user_info = $service_user->getHouseUserBind($cashier_order['pigcms_id'],$field);
        if (!$now_user_info) {
            $now_user_info = [];;
        }

        $order_msg = [];
        if ($order_info['money']) {
            $order_msg[] = [
                'title' => '应缴金额',
                'content' => '￥' . $order_info['money'],
            ];
        }
        if ($order_info['order_name']) {
            $order_msg[] = [
                'title' => '缴费项',
                'content' => $order_info['order_name'],
            ];
        }
        if ($cashier_order['pay_type_name']) {
            $order_msg[] = [
                'title' => '支付方式',
                'content' => $cashier_order['pay_type_name'],
            ];
        }
        if (isset($cashier_order['remarks'])) {
            $order_msg[] = [
                'title' => '备注',
                'content' => $cashier_order['remarks']?$cashier_order['remarks']:'无',
            ];
        }
        if ($cashier_order['pay_time']) {
            $order_msg[] = [
                'title' => '支付时间',
                'content' => $cashier_order['pay_time'],
            ];
        }
        if ($order_id) {
            $order_msg[] = [
                'title' => '订单编号',
                'content' => $order_id,
            ];
        }


        // 图标
        $static_resources = static_resources(true);
        if (!$this->config['site_url']) {
            // 初始化 配置 业务层
            $service_config = new ConfigService();
            $where_config = [];
            $where_config[] = ['name', '=', "site_url"];
            $config = $service_config->get_config_list($where_config);
            $this->config['site_url'] = $config['site_url'];
        }
        $site_url = $this->config['site_url'];
        if ($order_info['order_type']) {
            $cashier_order['title_img'] = $site_url . $static_resources . "images/single_detail_{$order_info['order_type']}.png";
        } else {
            $cashier_order['title_img'] = $site_url . $static_resources . "images/single_detail_custom.png";
        }

        $cashier_order['order_msg'] = $order_msg;
        // 实际金额
        $cashier_order['real_money'] = $order_info['money'];
        $cashier_order['order_id'] = $order_info['order_id'];
        $cashier_order['order_name'] = $order_info['order_name'];
        $cashier_order['order_type'] = $order_info['order_type'];
        $cashier_order['user_info'] = $now_user_info;


        if(!$cashier_order){
            return api_output(1003,[],'订单信息不存在！');
        }else{
            return api_output(0,$cashier_order);
        }
    }

    /**
     * 删除未交费订单
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'order_id'=> '订单id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/29 17:24
     * @return \json
     */
    public function payOrderDel() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(69,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少查询小区！');
            }
        }
        $order_id = $this->request->param('order_id','','intval');
        $service_order = new HouseVillageOrderService();
        $where_order = [];
        $where_order[] = ['order_id', '=', $order_id];
        $field_order = 'order_id,order_name,order_type,money,paid';
        $pay_order = $service_order->get_house_village_pay_order_where($where_order,$field_order);
        if (!$pay_order) {
            return api_output(1001,[],'订单信息不存在！');
        }
        if ($pay_order['paid'] > 0) {
            return api_output(1001,[],'该订单已付款，不能删除！');
        }
        $del = $service_order->house_village_pay_order_del($where_order);
        if($del){
            return api_output(0,['order_id'=>$order_id]);
        }else{
            return api_output(1001,[],'删除订单失败！请重新再试！');
        }
    }

    /**
     * 银台缴费类型
     * @author lijie
     * @date_time 2020/11/07
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function chargeType()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        $village_id = $this->request->post('village_id',0);
        if(!$pigcms_id || !$village_id)
            return api_output_error(1001,'缺少必传参数');
        $service_user = new HouseVillageUserService();
        // 当前业主信息
        $bind_where['pigcms_id'] = $pigcms_id;
        $now_bind_info = $service_user->getHouseUserBindWhere($bind_where,'vacancy_id');
        if (!$now_bind_info) {
            return api_output(1003,[],'当前小区不存在！');
        }
        $service_house_village_property_fee_service = new HouseVillagePropertyFeeService();
        $standard_bind_info = $service_house_village_property_fee_service->getBindStandard(['pigcms_id'=>$now_bind_info['vacancy_id']],'standard_id');
        $service_house_village_payment_service = new HouseVillagePaymentService();
        $data = $service_house_village_payment_service->getUserPaymentLists(['psb.pigcms_id'=>$pigcms_id,'psb.village_id'=>$village_id],'p.payment_name,ps.cycle_type,ps.pay_type,ps.pay_money,ps.metering_mode,ps.metering_mode_type,psb.cycle_sum,psb.start_time,psb.end_time,psb.paid_cycle,ps.pay_cycle,psb.metering_mode_val,psb.bind_id');
        if(empty($data)){
            $return = [
                [
                    'type'=>'property',
                    'name'=>'物业费'
                ],
                [
                    'type'=>'custom',
                    'name'=>'临时费用'
                ],
            ];
        }else{
            $return = [
                [
                    'type'=>'property',
                    'name'=>'物业费'
                ],
                [
                    'type'=>'custom',
                    'name'=>'临时费用'
                ],
                [
                    'type'=>'custom_payment',
                    'name'=>'自定义缴费',
                    'dataApp'=>$data
                ]
            ];
        }
        if(empty($standard_bind_info)){
            unset($return[0]);
            $return = array_values($return);
        }
        return api_output(0,$return);
    }

    /**
     * 获取两个日期相差几个月
     * @author lijie
     * @date_time 2020/07/27
     * @param $date1
     * @param $date2
     * @return float|int
     */
    public function getMonthNum($date1,$date2){
        list($date_1['y'],$date_1['m'])=explode("-",date('Y-m',$date1));
        list($date_2['y'],$date_2['m'])=explode("-",date('Y-m',$date2));
        return abs($date_1['y']-$date_2['y'])*12 +$date_2['m']-$date_1['m'];
    }

    /**
     * 计算物业费
     * @author lijie
     * @date_time 2020/07/20 15:48
     * @param $dateMonths
     * @param $chargingStandard
     * @param $housesize
     * @return float|int
     */
    public function property_price($dateMonths,$chargingStandard,$housesize)
    {
        $property_price = 0;
        foreach ($dateMonths as $value){
            foreach ($chargingStandard as $v){
                if($value >= strtotime($v['date_month'])){
                    $price = $v['price'];
                }
            }
            if(isset($price)){
                $property_price += $price*$housesize;
            }
            unset($price);
        }
        return $property_price;
    }
}