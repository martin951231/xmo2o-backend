<?php
/**
 * 订单和缴费相关业务
 * Created by PhpStorm.
 * Author: wanziyagn
 * Date Time: 2020/4/26 14:01
 */

namespace app\community\model\service;

use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePaymentPaylist;
use app\community\model\db\HouseVillagePropertyPaylist;
use app\community\model\db\PlatOrder;
use app\community\model\db\HouseVillageProperty;
use app\community\model\db\HouseVillageUserPaylist;
use app\community\model\db\HouseVillagePayTemporary;
use app\community\model\db\HouseVillagePayType;
use app\community\model\db\HouseVillagePaymentStandardBind;
use app\community\model\db\HouseVillagePaymentStandard;
use app\community\model\db\HouseVillagePaymentStandardUnpaid;
use app\community\model\db\HouseVillagePropertyStandardBind;
use app\community\model\db\HouseVillagePropertyStandard;

class HouseVillageOrderService
{

    /**
     * 小区缴费参数
     * @author:wanziyang
     * @date_time: 2020/4/26 14:53
     * @return array|null|\think\Model
     */
    public function pay_list_type() {
        $pay_list_type = array(
            'property'=>'物业费',
            'water'=>'水费',
            'electric'=>'电费',
            'gas'=>'燃气费',
            'park'=>'停车费',
            'custom'=>'临时缴费',
            'custom_payment'=>'自定义缴费',
        );
        return $pay_list_type;
    }


    /**
     * 获取小区缴费信息
     * @author:wanziyang
     * @date_time: 2020/4/26 11:02
     * @param array $where_arr 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function getOrderList($where_arr,$page=0,$field ='',$order='a.order_id DESC') {
        if(!$where_arr['village_id']){
            return [];
        }
        $where = [];
        if(isset($where_arr['paid'])){
            $where[] = ['a.paid', '=', intval($where_arr['paid'])];
        }
        if(isset($where_arr['bind_id'])){
            $where[] = ['a.bind_id', '=', intval($where_arr['bind_id'])];
        }
        if(isset($where_arr['phone'])){
            $where[] = ['hvu.phone', 'like', '%'.$where_arr['phone'].'%'];
        }
        if(isset($where_arr['name'])){
            $where[] = ['hvu.name', 'like', '%'.$where_arr['name'].'%'];
        }
        if(isset($where_arr['usernum'])){
            $where[] = ['hvu.usernum', 'like', '%'.$where_arr['usernum'].'%'];
        }
        if(isset($where_arr['order_name'])){
            $where[] = ['a.order_name', 'like', '%'.$where_arr['order_name'].'%'];
        }
        if(isset($where_arr['order_type'])){
            $where[] = ['a.order_type', 'in', $where_arr['order_type']];
        }
        if(isset($where_arr['pay_type'])){
            $where[] = ['a.pay_type', '=', intval($where_arr['pay_type'])];
        }
        if(isset($where_arr['cashier_id'])){
            $where[] = ['a.cashier_id', '=', $where_arr['cashier_id']];
        }


        if(isset($where_arr['is_pay_bill']) && $where_arr['is_pay_bill']==1){
            $where[] = ['a.is_pay_bill', 'in', '1,2'];
        }else if(isset($where_arr['is_pay_bill']) && $where_arr['is_pay_bill']==2){
            $where[] = ['a.is_pay_bill', '=', '1'];
        }

        if(isset($where['pay_time_start']) && isset($where['pay_time_end'])){
            $where[] = ['a.pay_time', 'between', $where['pay_time_start'].','.$where['pay_time_end']];
        }
        if (!$order) {
            $order = ' a.pay_time DESC,a.order_id DESC, a.paid ASC';
        }
        if (!$field) {
            $field = '`hvu`.`name` AS `username` ,hvu.*,a.order_id,a.order_type,a.order_name,a.time,a.money';
        }

        $db_house_village_pay_order = new HouseVillagePayOrder();
        $list = $db_house_village_pay_order->get_order_limit_list($where,$page,$field, $order);
//        dump($db_house_village_pay_order->getLastSql());die;

        $totalmoney = 0;
        $order_list = array();
        if (!($list->isEmpty())) {
            foreach($list as $key=>$value){
                $totalmoney += $value['money'];                              //本页的总额
                $order_list[$key]['order_id'] = $value['order_id'];
                $order_list[$key]['type'] = $value['order_type'];
                $order_list[$key]['name'] = $value['order_name'];
                $order_list[$key]['money'] = $value['money'];
                $order_list[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
            }
        }

        $ret = [];
        $ret['pay_list_order'] = $order_list;
        $ret['totalmoney'] = $totalmoney;

        return $ret;

    }

    /**
     * 获取小区缴费信息
     * @author:wanziyang
     * @date_time: 2020/4/26 11:02
     * @param array $where_arr 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function get_village_pay_order($where_arr,$page=0,$field ='',$order='a.order_id DESC') {
        if(!$where_arr['village_id']){
            return [];
        }
        $where = [];
        if(isset($where_arr['paid'])){
            $where[] = ['a.paid', '=', intval($where_arr['paid'])];
        }
        if(isset($where_arr['bind_id'])){
            $where[] = ['a.bind_id', '=', intval($where_arr['bind_id'])];
        }
        if(isset($where_arr['phone'])){
            $where[] = ['hvu.phone', 'like', '%'.$where_arr['phone'].'%'];
        }
        if(isset($where_arr['name'])){
            $where[] = ['hvu.name', 'like', '%'.$where_arr['name'].'%'];
        }
        if(isset($where_arr['usernum'])){
            $where[] = ['hvu.usernum', 'like', '%'.$where_arr['usernum'].'%'];
        }
        if(isset($where_arr['order_name'])){
            $where[] = ['a.order_name', 'like', '%'.$where_arr['order_name'].'%'];
        }
        if(isset($where_arr['order_type'])){
            $where[] = ['a.order_type', 'in', '('.$where_arr['order_type'].')'];
        }
        if(isset($where_arr['pay_type'])){
            $where[] = ['a.pay_type', '=', intval($where_arr['pay_type'])];
        }
        if(isset($where_arr['cashier_id'])){
            $where[] = ['a.cashier_id', 'in', '('.$where_arr['cashier_id'].')'];
        }


        if($where_arr['is_pay_bill']==1){
            $where[] = ['a.is_pay_bill', 'in', '1,2'];
        }else if($where_arr['is_pay_bill']==2){
            $where[] = ['a.is_pay_bill', '=', '1'];
        }

        if($where['pay_time_start'] && $where['pay_time_end']){
            $where[] = ['a.pay_time', '=', '1'];
        }
        if (!$order) {
            $order = ' `o`.`pay_time` DESC,`o`.`order_id` DESC, `o`.`paid` ASC';
        }
        if (!$field) {
            $field = '`hvu`.`name` AS `username` ,hvu.*,a.*';
        }

        $db_house_village_pay_order = new HouseVillagePayOrder();
        $list = $db_house_village_pay_order->get_order_limit_list($where,$page,$field, $order);

        // 物业缴费
        $db_house_village_property_paylist = new HouseVillagePropertyPaylist();
        $whereproperty = [
            'village_id' => $where_arr['village_id']
        ];
        $pay_list = $db_house_village_property_paylist->get_list($whereproperty);

        // 自定义缴费
        $db_house_village_payment_paylist = new HouseVillagePaymentPaylist();
        $wherepay = [
            'village_id' => $where_arr['village_id']
        ];
        $custom_paylist = $db_house_village_payment_paylist->get_list($wherepay);

        $db_plat_order = new PlatOrder();
        $totalmoney = 0;
        $order_list = array();
        if (!($list->isEmpty())) {
            foreach($list as $key=>$value){
                if ($custom_paylist) {
                    foreach($custom_paylist as $pay_info){
                        if($value['order_id'] ==  $pay_info['order_id']){
                            $list[$key]['custom_time_str'] = date('Y-m-d',$pay_info['start_time']) . '至' . date('Y-m-d',$pay_info['end_time']);
                        }
                    }
                }
                if(!empty($pay_list)){
                    foreach($pay_list as $pay_info){
                        if($value['order_id'] ==  $pay_info['order_id']){
                            $list[$key]['property_time_str'] = date('Y-m-d',$pay_info['start_time']) . '至' . date('Y-m-d',$pay_info['end_time']);
                        }
                    }
                }
                $plat_order_where = [];
                if ($value['cashier_id']) {
                    $plat_order_where[] = ['business_type','=','house_village_pay_cashier'];
                    $plat_order_where[] = ['business_id','=',$value['cashier_id']];
                    $plat_order_where[] = ['paid','=',1];
                }else{
                    $plat_order_where[] = ['business_type','=','house_village_pay'];
                    $plat_order_where[] = ['business_id','=',$value['order_id']];
                    $plat_order_where[] = ['paid','=',1];
                }
                $plat_order = $db_plat_order->get_one($plat_order_where,'is_own');
                $list[$key]['is_own'] = $plat_order ? $plat_order['is_own'] : 0;

                $totalmoney += $value['money'];                              //本页的总额
                $order_list[$key]['order_id'] = $value['order_id'];
                $order_list[$key]['type'] = $value['order_type'];
                $order_list[$key]['name'] = $value['order_name'];
                $order_list[$key]['money'] = $value['money'];
                $order_list[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
            }
        } else {
            $list = [];
        }

        $ret = [];
        $ret['pay_list_order'] = $order_list;

        return $list;

    }


    /**
     * 条件获取小区物业费缴费赠送活动
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $vacancy_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_house_village_property_list_where($where,$field = true,$order='id desc',$vacancy_id=0) {
        // 初始化 物业管理员 数据层
        $db_house_village_property = new HouseVillageProperty();
        $list = $db_house_village_property->get_village_property_list($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }else{
            $db_house_village_property_standard_bind = new HouseVillagePropertyStandardBind();
            $standard_bind_info = $db_house_village_property_standard_bind->getOne(['pigcms_id'=>$vacancy_id,'is_del'=>1],'standard_id');
            if(empty($standard_bind_info)){
                $data['property_unit_fee'] = 0;
            }else{
                $db_house_village_property_standard = new HouseVillagePropertyStandard();
                $standard_info = $db_house_village_property_standard->getOne(['id'=>$standard_bind_info['standard_id']],'date_month');
                if($standard_info){
                    $property_unit_fee = $this->getNowPropertyFee(unserialize($standard_info['date_month']),time());
                    $data['property_unit_fee'] = $property_unit_fee;
                }else{
                    $data['property_unit_fee'] = 0;
                }
            }
        }
        $data['list'] = $list;
        return $data;
    }

    /**
     * 条件获取小区缴费列表信息
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_house_village_property_where($where,$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_village_property = new HouseVillageProperty();
        $list = $db_house_village_property->get_one($where,$field);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }

    /**
     * 添加小区缴费列表信息
     * @author: wanziyang
     * @date_time: 2020/4/27 13:11
     * @param array $data 添加的数据
     * @return array|null|\think\Model
     */
    public function add_house_village_pay_order($data) {
        // 初始化 数据层
        $db_house_village_pay_order= new HouseVillagePayOrder();
        $order_id = $db_house_village_pay_order->add_order($data);
        return $order_id;
    }


    /**
     * 修改小区缴费列表信息
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param array $where 修改的条件
     * @param array $data 修复的数据
     * @return array|null|\think\Model
     */
    public function save_house_village_pay_order($where,$data) {
        // 初始化 数据层
        $db_house_village_pay_order= new HouseVillagePayOrder();
        $cashier_id = $db_house_village_pay_order->save_one($where,$data);
        return $cashier_id;
    }

    /**
     * 条件获取小区缴费单条信息
     * @author: wanziyang
     * @date_time: 2020/4/27 16:36
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_house_village_pay_order_where($where,$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_village_pay_order= new HouseVillagePayOrder();
        $list = $db_house_village_pay_order->get_one($where,$field);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }


    /**
     * 条件获取物业缴费列表信息
     * @author: wanziyang
     * @date_time: 2020/4/23 21:00
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function get_house_village_payment_paylist_where($where,$field = true,$order='') {
        // 初始化 数据层
        $db_house_village_payment_paylist = new HouseVillagePropertyPaylist();
        $list = $db_house_village_payment_paylist->get_list($where, $field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }


    /**
     * 条件获业主每月的欠费详细表
     * @author: wanziyang
     * @date_time: 2020/4/23 21:00
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function get_house_village_user_paylist_where($where,$field = true,$order='pigcms_id DESC') {
        // 初始化 数据层
        $db_house_village_user_paylist = new HouseVillageUserPaylist();
        $list = $db_house_village_user_paylist->get_list($where, $field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;
    }


    /**
     * 添加小区临时缴费信息
     * @author: wanziyang
     * @date_time: 2020/4/23 13:11
     * @param array $data 添加的数据
     * @return array|null|\think\Model
     */
    public function add_temporary_order($data) {
        // 初始化 物业管理员 数据层
        $db_house_village_pay_temporary= new HouseVillagePayTemporary();
        $temporary_order_id = $db_house_village_pay_temporary->add_order($data);
        return $temporary_order_id;
    }


    /**
     * 获取小区临时缴费信息
     * @author: wanziyang
     * @date_time: 2020/4/23 14:44
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_temporary_order($where,$field = true) {
        // 初始化 物业管理员 数据层
        $db_house_village_pay_temporary= new HouseVillagePayTemporary();
        $list = $db_house_village_pay_temporary->get_one($where,$field);
        return $list;
    }

    /**
     * 条件获取小区线下支付方式
     * @author: wanziyang
     * @date_time: 2020/4/27 15:42
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function get_house_village_pay_type_where($where,$field = true,$order='id DESC') {
        // 初始化 数据层
        $db_house_village_pay_type = new HouseVillagePayType();
        $list = $db_house_village_pay_type->get_list($where, $field,$order);
        $list_arr = [];
        if (!($list->isEmpty())) {
            foreach($list as &$val) {
                $list_arr[$val['id']] = $val;
            }
        }
        return $list_arr;
    }

    /**
     * 条件获取缴费相关绑定信息列表
     * @author: wanziyang
     * @date_time: 2020/4/27 16:47
     * @param array $where 条件
     * @param string|bool $field 需要获取的对应字段
     * @param string $order 排序
     * @return array|null|\think\Model
     */
    public function get_village_payment_standard_bind_list($where,$field = true,$order='bind_id DESC') {
        // 初始化 数据层
        $db_house_village_payment_standard_bind = new HouseVillagePaymentStandardBind();
        $list = $db_house_village_payment_standard_bind->get_list($where, $field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }
        return $list;

    }


    /**
     * @author: wanziyang
     * @date_time: 2020/4/29 17:18
     * @param array $where
     * @return array|null|\think\Model
     */
    public function house_village_pay_order_del($where) {
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $del = $db_house_village_pay_order->del_one($where);
        return $del;
    }

    /**
     * 获取缴费金额
     * @author lijie
     * @date_time 2020/08/04 13:11
     * @param $where
     * @param $whereAnd
     * @param string $field
     * @return float
     */
    public function getSumMoney($where,$whereAnd = array(),$field='money')
    {
        $house_village_pay_order = new HouseVillagePayOrder();
        $sum_money = $house_village_pay_order->sumMoney($where,$whereAnd,$field);
        return $sum_money;
    }

    /**
     * 获取账单列表
     * @author lijie
     * @date_time 2020/08/15 10:01
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function houseVillagePayOrderLists($where,$field=true)
    {
        $house_village_pay_order = new HouseVillagePayOrder();
        $data = $house_village_pay_order->get_list($where,$field);
        $arr = array();
        foreach ($data as $k=>$v){
            $arr[0]['title'] = '订单编号';
            $arr[1]['title'] = '缴费项';
            $arr[2]['title'] = '应缴金额';
            $arr[0]['name'] = $v['order_id'];
            $arr[1]['name'] = $v['order_name'];
            $arr[2]['name'] = $v['money'];
            $data[$k]['list'] = $arr;
        }
        return $data;
    }

    /**
     * 获取账单列表
     * @author lijie
     * @date_time 2020/08/17 10:02
     * @param $where
     * @param int $page
     * @param string $field
     * @param string $order
     * @return array|\think\Model|null
     */
    public function getHouseVillagePayOrderLists($where,$page=0,$field ='hvu.name,hvu.phone,hvu.address,a.money,hvu.type',$order='a.pay_time DESC,a.order_id DESC, a.paid ASC')
    {
        $house_village_pay_order = new HouseVillagePayOrder();
        $data = $house_village_pay_order->get_order_limit_list($where,$page,$field,$order);
        return $data;
    }

    /**
     * 获取自定义缴费费用
     * @author lijie
     * @date_time 2020/08/17 10:57
     * @param $where
     * @return mixed
     */
    public function getSumPayment($where)
    {
        $house_village_payment_standard_bind = new HouseVillagePaymentStandardBind();
        $info = $house_village_payment_standard_bind->getSum($where);
        return $info;
    }

    /**
     * 获取指定条件的订单数量
     * @author lijie
     * @date_time 2020/09/30
     * @param $where
     * @return \think\Collection
     */
    public function get_pay_order_count($where)
    {
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $count = $db_house_village_pay_order->get_count($where);
        return $count;
    }

    /**
     * 获取列表
     * @author lijie
     * @date_time 2020/09/30
     * @param $where
     * @return array|\think\Model|null
     */
    public function get_house_village_pay_order_lists($where)
    {
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $lists = $db_house_village_pay_order->get_list($where);
        return $lists;
    }

    /**
     * 添加物业缴费
     * @author lijie
     * @date_time 2020/09/30
     * @param $data
     * @return int|string
     */
    public function add_house_village_payment_paylist($data) {
        $db_house_village_payment_paylist = new HouseVillagePropertyPaylist();
        $list = $db_house_village_payment_paylist->add_one($data);
        return $list;
    }

    /**
     * 添加欠费
     * @author lijie
     * @date_time 2020/09/30
     * @param $where
     * @param $data
     * @return int|string
     */
    public function save_house_village_user_paylist($where,$data)
    {
        $db_house_village_user_paylist = new HouseVillageUserPaylist();
        $res = $db_house_village_user_paylist->saveOne($where,$data);
        return $res;
    }

    /**
     * 件获取缴费相关绑定信息
     * @author lijie
     * @date_time 2020/09/30
     * @param $where
     * @return array|\think\Model|null
     */
    public function getPayment($where)
    {
        $house_village_payment_standard_bind = new HouseVillagePaymentStandardBind();
        $info = $house_village_payment_standard_bind->get_one($where);
        return $info;
    }

    /**
     * @param $where
     * @return array|\think\Model|null
     */
    public function getPaymentStandard($where)
    {
        $house_village_payment_standard = new HouseVillagePaymentStandard();
        $info = $house_village_payment_standard->get_one($where);
        return $info;
    }

    public function save_house_village_payment_standard_unpaid($where,$data)
    {
        $db_house_village_payment_standard_unpaid = new HouseVillagePaymentStandardUnpaid();
        $res = $db_house_village_payment_standard_unpaid->saveOne($where,$data);
        return $res;
    }

    public function incPayment($where,$cycle)
    {
        $house_village_payment_standard_bind = new HouseVillagePaymentStandardBind();
        $info = $house_village_payment_standard_bind->incPaidCycle($where,$cycle);
        return $info;
    }

    /**
     * 获取当月物业费收费标准
     * @author lijie
     * @date_time 2020/07/30
     * @param $chargingStandard
     * @param $month
     * @return mixed
     */
    public function getNowPropertyFee($chargingStandard,$month)
    {
        foreach ($chargingStandard as $value) {
            if (strtotime($value['date_month']) <= $month && $value['date_month'] != '') {
                $price = $value['price'];
            }
        }
        return isset($price) ? $price : 0;
    }

    /**
     * 计算房间所欠物业费
     * @author lijie
     * @date_time 2020/12/01
     * @param $room_id
     * @param $property_end_time
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoomPropertyFee($room_id,$property_end_time,$housesize)
    {
        $db_house_village_property_standard_bind = new HouseVillagePropertyStandardBind();
        $standard_bind_info = $db_house_village_property_standard_bind->getOne(['pigcms_id'=>$room_id,'is_del'=>1],'standard_id');
        if(empty($standard_bind_info)){
            $price = 0;
        }else{
            $db_house_village_property_standard = new HouseVillagePropertyStandard();
            $standard_info = $db_house_village_property_standard->getOne(['id'=>$standard_bind_info['standard_id']],'date_month');
            if($standard_info){
                $num = $this->getMonthNum($property_end_time + 1, time());
                if(date('d',time()) > date('d',$property_end_time + 1))
                    $num+=1;
                $start_time = strtotime(date('Y-m',$property_end_time));
                $dateMonth[] = $start_time;
                for ($i=1;$i<$num;$i++){
                    $dateMonth[] = strtotime("+ $i month",$start_time);
                }
                $price = $this->property_price($dateMonth,unserialize($standard_info['date_month']),$housesize);
            }else{
                $price = 0;
            }
        }
        return $price;
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
}