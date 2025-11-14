<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/28 14:07
 */

namespace app\community\model\service;


use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseVillageHaokuaichongRate;
use app\community\model\db\HouseVillageHaokuaichongRegion;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePilePayOrder;
use app\community\model\db\HouseVillagePileUserBalance;
use app\community\model\db\HouseVillagePileUserBalanceLog;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\PlatOrder;
use app\community\model\db\User;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePileAttributeSet;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\HouseVillagePileEquipmentAttribute;
use app\community\model\db\HouseVillagePileMonthCard;
use app\community\model\db\HouseVillagePileMonthCardUser;
use app\community\model\db\HouseVillagePileAttribute;
use app\consts\OrderRecordConst;
use app\pay\model\db\PayOrderInfo;
use app\common\model\db\PaidOrderRecord;
use app\pay\model\service\channel\ChinaumsService;
use app\community\model\db\HouseVillagePayConfig;
use app\community\model\db\VillageChinaumsDivideRecord;
use app\common\model\service\config\ConfigCustomizationService;
class PileOrderPayService
{
    public $base_url = '';


    public $street_url = '';


    //支付类型 alipay:支付宝 wechat:微信 unionpay:银联
    public $pay_type_arr1 = [
        'wechat' => '1', 'alipay' => '2', 'unionpay' => '3'
    ];


    /**
     * 生成订单
     * @param 传参
     * array(
     * 'uid'=>用户uid
     * 'type'=>缴费类型 1充电支付 2购买月卡支付
     * 'id'=>设备id
     * 'portNum'=>插座编号
     * 'price'=>充值金额
     * 'card_id'=>月卡id
     * )
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/12 16:07
     */
    public function  addOrderInfo($data)
    {
        $service_user = new User();
        $user_info = $service_user->getOne(['uid' => $data['uid']]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $service_equipment = new HouseVillagePileEquipment();
        $equipment_info = $service_equipment->getInfo(['id' => $data['id']]);
        if (!$equipment_info) {
            throw new \think\Exception("设备不存在！");
        }
        if ($equipment_info['type'] == 1) {
            if ($data['type'] == 1) {
                $order_data = [
                    'order_name' => '充电桩充电支付',
                    'order_type' => 'pile',
                    'order_no' => build_real_orderid($data['uid']),
                    'uid' => $data['uid'],
                    'village_id' => $equipment_info['village_id'],
                    'bind_id' =>$data['pigcms_id'],
                    'money' => $data['price'],
                    'time' => time(),
                    'paid' => 0,
                    'pay_uid' => $data['uid'],
                ];
                $order_pile = [
                    'village_id' => $equipment_info['village_id'],
                    'order_type' => 1,
                    'order_no' => $order_data['order_no'],
                    'equipment_id' => $equipment_info['id'],
                    'socket_no' => $data['portNum'],
                    'use_money' => $data['price'],
                    'uid' => $data['uid'],
                    'add_time' => time(),
                ];
            } else {
                $service_card = new HouseVillagePileMonthCard();
                $card_info = $service_card->getOne(['id' => $data['card_id'], 'status' => 1, 'is_del' => 1,'village_id'=>$equipment_info['village_id']]);
                if (!$card_info) {
                    throw new \think\Exception("月卡信息不存在！");
                }

                $service_card_user = new HouseVillagePileMonthCardUser();
                $card_user_info = $service_card_user->getOne(['uid' => $data['uid'],'village_id'=>$equipment_info['village_id']]);
                if ($card_user_info && $card_user_info['expire_time'] > time() && $card_user_info['status'] == 1) {
                    throw new \think\Exception("您当前有未到期的月卡，需到期后才能继续购买月卡！");
                }
                $order_data = [
                    'order_name' => '充电桩购买月卡',
                    'order_type' => 'pile',
                    'order_no' => build_real_orderid($data['uid']),
                    'village_id' => $card_info['village_id'],
                    'bind_id' =>$data['pigcms_id'],
                    'money' => $data['price'],
                    'time' => time(),
                    'paid' => 0,
                    'pay_uid' => $data['uid'],

                ];
                $order_pile = [
                    'village_id' => $card_info['village_id'],
                    'order_type' => 2,
                    'equipment_id' => $equipment_info['id'],
                    'uid' => $data['uid'],
                    'order_no' => $order_data['order_no'],
                    'use_money' => $data['price'],
                    'add_time' => time(),
                ];

                $order_user = [
                    'uid' => $data['uid'],
                    'village_id' => $card_info['village_id'],
                    'card_id' => $data['card_id'],
                    'price' => $card_info['price'],
                    'all_num' => $card_info['all_num'],
                    'per_price' => $card_info['per_price'],
                    'surplus_num' => $card_info['all_num'],
                    'max_num' => $card_info['max_num'],
                    'use_month' => $card_info['use_month'],
                    'buy_time' => time(),
                    'expire_time' => strtotime('+' . $card_info['use_month'] . 'month'),
                    'add_time' => time(),
                    'status' => 0,
                ];

                $service_card_user->insertCard($order_user);

            }
        }
        elseif($equipment_info['type'] == 21){
            if ($data['type'] ==1) {
                $portNum=$data['portNum']-1;
                $portNum=$portNum>0 ? $portNum:0;
                if ($portNum<=9){
                    $portNum='0'.$portNum;
                }
                $order_data = [
                    'order_name' => '艾特充充电桩充电支付',
                    'order_type' => 'pile',
                    'order_no' =>build_real_orderid($data['uid']),
                    'uid' => $data['uid'],
                    'village_id' => $equipment_info['village_id'],
                    'bind_id' =>$data['pigcms_id'],
                    'money' => $data['price'],
                    'time' => time(),
                    'paid' => 0,
                    'pay_uid' => $data['uid'],
                ];
                if (strlen($equipment_info['equipment_serial'])==8){
                    $equipment_serial='00'.$equipment_info['equipment_serial'];
                }else{
                    $equipment_serial=$equipment_info['equipment_serial'];
                }
                $order_pile = [
                    'village_id' => $equipment_info['village_id'],
                    'order_type' => 1,
                    'order_no' => date('ymdhis').mt_rand(10000000,99999999).$equipment_serial.$portNum,
                    'equipment_id' => $equipment_info['id'],
                    'socket_no' => $data['portNum'],
                    'use_money' => $data['price'],
                    'uid' => $data['uid'],
                    'type' => 21,
                    'add_time' => time(),
                ];
            }
            else {
                $service_card = new HouseVillagePileMonthCard();
                $card_info = $service_card->getOne(['id' => $data['card_id']]);
                if (!$card_info) {
                    throw new \think\Exception("月卡信息不存在！");
                }

                $service_card_user = new HouseVillagePileMonthCardUser();
                $card_user_info = $service_card_user->getOne(['uid' => $data['uid']]);
                if ($card_user_info && $card_user_info['expire_time'] > time() && $card_user_info['status'] == 1) {
                    throw new \think\Exception("您当前有未到期的月卡，需到期后才能继续购买月卡！");
                }
                $order_data = [
                    'order_name' => 'tcp充电桩购买月卡',
                    'order_type' => 'pile',
                    'order_no' => build_real_orderid($data['uid']),
                    'village_id' => $card_info['village_id'],
                    'bind_id' =>$data['pigcms_id'],
                    'money' => $data['price'],
                    'time' => time(),
                    'paid' => 0,
                    'pay_uid' => $data['uid'],

                ];
                $order_pile = [
                    'village_id' => $card_info['village_id'],
                    'order_type' => 2,
                    'equipment_id' => $equipment_info['id'],
                    'uid' => $data['uid'],
                    'order_no' => mt_rand(1000000,9999999).$data['uid'].substr(time(), 2, 9 - strlen($data['uid'])),
                    'use_money' => $data['price'],
                    'add_time' => time(),
                    'type' => 2,
                ];

                $order_user = [
                    'uid' => $data['uid'],
                    'card_id' => $data['card_id'],
                    'price' => $card_info['price'],
                    'per_price' => $card_info['per_price'],
                    'all_num' => $card_info['all_num'],
                    'surplus_num' => $card_info['all_num'],
                    'max_num' => $card_info['max_num'],
                    'use_month' => $card_info['use_month'],
                    'buy_time' => time(),
                    'status' => 0,
                    'expire_time' => strtotime('+' . $card_info['use_month'] . 'month'),
                    'add_time' => time(),
                    'village_id' => $card_info['village_id'],
                ];

                $service_card_user->insertCard($order_user);

            }
        }else if($equipment_info['type'] == 3){
            if ($data['type'] ==1) {
                $portNum=$data['portNum']-1;
                $portNum=$portNum>0 ? $portNum:0;
                if ($portNum<=9){
                    $portNum='0'.$portNum;
                }
                $area_id=$equipment_info['area_id'];
                if(empty($area_id)){
                    throw new \think\Exception("设备所属充电区不存在，无法充电！");
                }
                $houseVillageHaokuaichongRegionDb = new HouseVillageHaokuaichongRegion();
                $whereRegion=array('area_id'=>$area_id,'village_id'=>$equipment_info['village_id']);
                $hkcRegionObj=$houseVillageHaokuaichongRegionDb->getOneData($whereRegion,'*');
                $hkcRegion=array();
                if($hkcRegionObj && !$hkcRegionObj->isEmpty()){
                    $hkcRegion=$hkcRegionObj->toArray();
                }
                if(empty($hkcRegion) || $hkcRegion['del_time']>0){
                    throw new \think\Exception("设备所属充电区不存在，无法充电！");
                }
                $common_id=$hkcRegion['common_id'];
                if(empty($common_id)){
                    throw new \think\Exception("设备所属充电区费率套餐未设置，无法充电！");
                }
                $houseVillageHaokuaichongRateDb = new HouseVillageHaokuaichongRate();
                $whereRate=array('rate_id'=>$common_id,'village_id'=>$equipment_info['village_id']);
                $hkcRateObj=$houseVillageHaokuaichongRateDb->getOneData($whereRate,'*');
                $hkcRate=array();
                if($hkcRateObj && !$hkcRateObj->isEmpty()){
                    $hkcRate=$hkcRateObj->toArray();
                }
                if(empty($hkcRate) || $hkcRate['del_time']>0){
                    throw new \think\Exception("设备所属充电区费率套餐不存在，无法充电！");
                }
                $extra_data=array('equipment_num'=>$equipment_info['equipment_num'],'equipment_port'=>$portNum,'area_id'=>$area_id);
                $extra_data['haokuaichong_rate']=$hkcRate;
                $extra_data_str=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                $order_no=date('YmdHis').'33'.mt_rand(100000,999999).$equipment_info['equipment_sn'].$portNum;
                $buy_ele=0;
                $buy_time=0;
                $rate_detail=json_decode($hkcRate['rate_detail'],true);

                if($hkcRate['rate_type']=='B2'){
                    //计量
                    $standard=$rate_detail && $rate_detail['0'] ? $rate_detail['0']['standard']:1;
                    $standard=$standard>0 ? $standard:1;
                    $buy_ele=$data['price']/$standard;  
                    $buy_ele=round($buy_ele,2);
                }elseif($hkcRate['rate_type']=='B1'){
                    //计时
                    $standard=$rate_detail && $rate_detail['0'] ? $rate_detail['0']['standard']:0.5;
                    $buy_time=($data['price']/$standard);
                    if($buy_time>18){
                        $buy_time=18;  //最大时长为18小时
                    }
                    $buy_time=$buy_time*3600;
                    $buy_time=floor ($buy_time);
                }
                $order_data = [
                    'order_name' => '好快充充电桩充电支付',
                    'order_type' => 'pile',
                    'order_no' =>build_real_orderid($data['uid']),
                    'uid' => $data['uid'],
                    'village_id' => $equipment_info['village_id'],
                    'bind_id' =>$data['pigcms_id'],
                    'money' => $data['price'],
                    'time' => time(),
                    'paid' => 0,
                    'remarks'=>'好快充充电桩充电支付',
                    'pay_uid' => $data['uid'],
                ];
                $order_pile = [
                    'village_id' => $equipment_info['village_id'],
                    'order_type' => 1,
                    'order_no' => $order_no,
                    'socket_no' => $data['portNum'],
                    'equipment_id' => $equipment_info['id'],
                    'use_money' => $data['price'],
                    'uid' => $data['uid'],
                    'type' => 3,
                    'add_time' => time(),
                    'buy_ele'=>$buy_ele,
                    'buy_time'=>$buy_time,
                    'extra_data'=>$extra_data_str,
                ];
            }else{
                $service_card = new HouseVillagePileMonthCard();
                $card_info = $service_card->getOne(['id' => $data['card_id'], 'status' => 1, 'is_del' => 1,'village_id'=>$equipment_info['village_id']]);
                if (!$card_info) {
                    throw new \think\Exception("月卡信息不存在！");
                }
                $service_card_user = new HouseVillagePileMonthCardUser();
                $card_user_info = $service_card_user->getOne(['uid' => $data['uid'],'village_id'=>$equipment_info['village_id']]);
                if ($card_user_info && $card_user_info['expire_time'] > time() && $card_user_info['status'] == 1) {
                    throw new \think\Exception("您当前有未到期的月卡，需到期后才能继续购买月卡！");
                }
                $order_data = [
                    'order_name' => '好快充充电桩购买月卡',
                    'order_type' => 'pile',
                    'order_no' => build_real_orderid($data['uid']),
                    'village_id' => $equipment_info['village_id'],
                    'bind_id' =>$data['pigcms_id'],
                    'money' => $data['price'],
                    'time' => time(),
                    'remarks'=>'好快充充电桩购买月卡',
                    'paid' => 0,
                    'pay_uid' => $data['uid'],

                ];
                $order_no=date('YmdHis').'33'.mt_rand(100000,999999).$equipment_info['equipment_sn'].$data['uid'];
                $order_pile = [
                    'village_id' => $equipment_info['village_id'],
                    'order_type' => 2,
                    'equipment_id' => $equipment_info['id'],
                    'uid' => $data['uid'],
                    'order_no' => $order_no,
                    'use_money' => $data['price'],
                    'add_time' => time(),
                    'type' => 3,
                ];

                $order_user = [
                    'uid' => $data['uid'],
                    'card_id' => $data['card_id'],
                    'price' => $card_info['price'],
                    'per_price' => $card_info['per_price'],
                    'all_num' => $card_info['all_num'],
                    'surplus_num' => $card_info['all_num'],
                    'max_num' => $card_info['max_num'],
                    'use_month' => $card_info['use_month'],
                    'buy_time' => time(),
                    'status' => 0,
                    'expire_time' => strtotime('+' . $card_info['use_month'] . 'month'),
                    'add_time' => time(),
                    'village_id' => $card_info['village_id'],
                ];

                $service_card_user->insertCard($order_user);
            }
        }
        else {
            if ($data['type'] ==1) {
                if ($equipment_info['status'] != 1) {
                    throw new \think\Exception("当前设备已离线，无法充电");
                }
                if (empty($equipment_info['fd'])) {
                    //throw new \think\Exception("当前设备异常，无法充电");
                }
                $time1 = time() - 300;
                if ($equipment_info['last_heart_time'] < $time1) {
                   // throw new \think\Exception("当前设备已离线，无法充电");
                }
                $socket_arr = json_decode($equipment_info['port_status']);
                if (!empty($socket_arr)) {
                    $status = 0;
                    foreach ($socket_arr as $k=>$value) {
                        if ($k == $data['portNum']-1) {
                            $status = $value;
                            break;
                        }
                    }
                    //判断插座状态
                    if ($status != 0) {
                        throw new \think\Exception("当前插座不能充电，请更换插座");
                    }
                }
                $order_data = [
                    'order_name' => 'tcp充电桩充电支付',
                    'order_type' => 'pile',
                    'order_no' => build_real_orderid($data['uid']),
                    'uid' => $data['uid'],
                    'village_id' => $equipment_info['village_id'],
                    'bind_id' =>$data['pigcms_id'],
                    'money' => $data['price'],
                    'time' => time(),
                    'paid' => 0,
                    'pay_uid' => $data['uid'],
                ];
                $order_pile = [
                    'village_id' => $equipment_info['village_id'],
                    'order_type' => 1,
                    'order_no' => mt_rand(1000000,9999999).$data['uid'].substr(time(), 2, 9 - strlen($data['uid'])),
                    'equipment_id' => $equipment_info['id'],
                    'socket_no' => $data['portNum'],
                    'use_money' => $data['price'],
                    'uid' => $data['uid'],
                    'type' => 2,
                    'add_time' => time(),
                ];
            } else {
                $service_card = new HouseVillagePileMonthCard();
                $card_info = $service_card->getOne(['id' => $data['card_id']]);
                if (!$card_info) {
                    throw new \think\Exception("月卡信息不存在！");
                }

                $service_card_user = new HouseVillagePileMonthCardUser();
                $card_user_info = $service_card_user->getOne(['uid' => $data['uid']]);
                if ($card_user_info && $card_user_info['expire_time'] > time() && $card_user_info['status'] == 1) {
                    throw new \think\Exception("您当前有未到期的月卡，需到期后才能继续购买月卡！");
                }
                $order_data = [
                    'order_name' => 'tcp充电桩购买月卡',
                    'order_type' => 'pile',
                    'order_no' => build_real_orderid($data['uid']),
                    'village_id' => $card_info['village_id'],
                    'bind_id' => '',
                    'money' => $data['price'],
                    'time' => time(),
                    'paid' => 0,
                    'pay_uid' => $data['uid'],

                ];
                $order_pile = [
                    'village_id' => $card_info['village_id'],
                    'order_type' => 2,
                    'equipment_id' => $equipment_info['id'],
                    'uid' => $data['uid'],
                    'order_no' => mt_rand(1000000,9999999).$data['uid'].substr(time(), 2, 9 - strlen($data['uid'])),
                    'use_money' => $data['price'],
                    'add_time' => time(),
                    'type' => 2,
                ];

                $order_user = [
                    'uid' => $data['uid'],
                    'card_id' => $data['card_id'],
                    'price' => $card_info['price'],
                    'per_price' => $card_info['per_price'],
                    'all_num' => $card_info['all_num'],
                    'surplus_num' => $card_info['all_num'],
                    'max_num' => $card_info['max_num'],
                    'use_month' => $card_info['use_month'],
                    'buy_time' => time(),
                    'status' => 0,
                    'expire_time' => strtotime('+' . $card_info['use_month'] . 'month'),
                    'add_time' => time(),
                    'village_id' => $card_info['village_id'],
                ];

                $service_card_user->insertCard($order_user);

            }
        }

        $service_order = new HouseVillagePayOrder();
        $order_id = $service_order->add_order($order_data);
        $order_info = $service_order->get_one(['order_id' => $order_id]);
        $pay_order_param = array(
            'business_type' => $order_info['order_type'],
            'business_id' => $order_info['order_id'],
            'order_name' => $order_info['order_name'],
            'uid' => $order_info['uid'],
            'total_money' => $order_info['money'],
            'wx_cheap' => 0,
            'is_own' => 4
        );
        $db_plat_order = new PlatOrder();
        $plat_order_result = $db_plat_order->add_order($pay_order_param);
        $order_pile['order_id'] = $order_id;
        $service_order_pile = new HouseVillagePilePayOrder();
        $order_pile_id = $service_order_pile->add_order($order_pile);

        return $order_id;
    }


    /**
     * 支付查询订单信息
     * @param integer $order_id 订单id
     * @date_time: 2021/4/12 16:54
     * @return array
     * @throws \think\Exception
     * @author:zhubaodi
     */
    public function getOrderPayInfo($order_id,$order_type='pile')
    {
        if (empty($order_id)) {
            throw new \think\Exception("请上传订单id！");
        }
        if($order_type=='pile_user_charge_balance'){
            $userBalanceLogDb = new HouseVillagePileUserBalanceLog();
            $whereArr=array(['id','=',$order_id]);
            $order_obj=$userBalanceLogDb->getOneData($whereArr);
            $order_info='';
            if($order_obj && !$order_obj->isEmpty()){
                $order_info= $order_obj->toArray();
            }
            if (!$order_info) {
                throw new \think\Exception("充值订单信息不存在！");
            }
            $data = [
                'village_id'=>$order_info['village_id'],
                'order_money' => $order_info['money'],
                'order_no' => $order_info['pay_order_no'],
                'uid' => $order_info['uid'],
                'title' => '充电桩用户余额充值',
                'time_remaining' => 900,
                'paid' => 0,
                'is_cancel' => 0,
                'city_id' => 0,
                'store_id' => 0,
                'is_recharge'=>1,
                'mer_id' => 0,
                'goods_desc'=>'充电桩用户余额充值',
            ];

            return $data;
        }
        $service_order = new HouseVillagePayOrder();
        $order_info = $service_order->get_one(['order_id' => $order_id]);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }
        $data = [
            'village_id'=>$order_info['village_id'],
            'order_money' => $order_info['money'],
            'order_no' => $order_info['order_no'],
            'uid' => $order_info['uid'],
            'title' => '充电桩充电支付',
            'time_remaining' => 900,
            'paid' => 0,
            'is_cancel' => 0,
            'city_id' => 0,
            'store_id' => 0,
            'mer_id' => 0,
            'goods_desc'=>'电瓶车充电费用', 
        ];

        return $data;


    }

    /**
     * 支付成功后跳转url
     * @param integer $order_id 订单id
     * @date_time: 2021/4/12 16:54
     * @return array
     * @throws \think\Exception
     * @author:zhubaodi
     */
    public function getPayResultUrl($order_id,$order_type='pile')
    {
        if (empty($order_id)) {
            throw new \think\Exception("请上传订单id！");
        }
        $service_equipment = new HouseVillagePileEquipment();
        if($order_type=='pile_user_charge_balance'){
            $houseVillagePileUserBalanceLog = new HouseVillagePileUserBalanceLog();
            $chargeOrderObj=$houseVillagePileUserBalanceLog->getOneData(array('id'=>$order_id,'opt_type_flag'=>'charge_order'));
            if($chargeOrderObj && !$chargeOrderObj->isEmpty()){
                $equipment_info = $service_equipment->getInfo(['id' => $chargeOrderObj['equipment_id']]);
                $url = get_base_url('pages/village/smartCharge/smartCharge?qcodeNum='.$equipment_info['equipment_serial'].'&type=2');
                if($equipment_info['type']==1){
                    $url = get_base_url('pages/village/smartCharge/smartCharge?qcodeType=0&qcodeNum='.$equipment_info['equipment_num']);
                }
                $res['redirect_url'] = $url;
                $res['direct'] = 1;
                return $res;
            }else{
                throw new \think\Exception("订单信息不存在！");
            }
        }
        $service_order = new HouseVillagePayOrder();
        $order_info = $service_order->get_one(['order_id' => $order_id]);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }
        $service_order_pile = new HouseVillagePilePayOrder();
        $order_pile_id = $service_order_pile->get_one(['order_id' => $order_id]);
        
        $equipment_info = $service_equipment->getInfo(['id' => $order_pile_id['equipment_id']]);
        if (!$equipment_info) {
            throw new \think\Exception("设备不存在！");
        }
        $village_id=$order_pile_id['village_id'];
        
        if ($order_pile_id['order_type'] == 2) {
            $service_Card_User = new HouseVillagePileMonthCardUser();
            $where=[];
            $where[] = ['uid', '=', $order_pile_id['uid']];
            $where[] = ['status', '=', 1];
            $where[] = ['expire_time', '>', time()];
            $where[] = ['surplus_num', '>', 0];
            $cardUserInfo = $service_Card_User->getOne($where);
            $url = get_base_url('pages/village/smartCharge/monthCardDetails?id=' . $equipment_info['id'] . '&mycardid=' . $cardUserInfo['id'] . '&cardId=' . $cardUserInfo['card_id']);
         //   $url = get_base_url('pages/village/smartCharge/paySuccess?payType=1&id=' . $equipment_info['id'] . '&mycardid=' . $cardUserInfo['id'] . '&cardId=' . $cardUserInfo['card_id']);

        }else if ($equipment_info['type']==21){
           //  $url = get_base_url('pages/village/smartCharge/paySuccess?type=21&qcodeNum=' .$equipment_info['equipment_num']);
            $url = get_base_url('pages/village/smartCharge/chargeDetails?status=0&order_id=' . $order_pile_id['id']);

        } else {
            $url = get_base_url('pages/village/smartCharge/chargeDetails?status=0&order_id=' . $order_pile_id['id']);
        }
        $configCustomizationService=new ConfigCustomizationService();
        $pile_charge_consume_point=$configCustomizationService->getPileChargeConsumePoint();
        if($pile_charge_consume_point){
            $url = get_base_url('pages/village/smartCharge/paySuccess?type='.$equipment_info['type'].'&qcodeNum=' .$equipment_info['equipment_num'].'&order_id=' . $order_pile_id['id'].'&village_id='.$village_id);
        }
        $res['redirect_url'] = $url;
        $res['direct'] = 1;
        return $res;
    }


    /**
     * 支付成功后回调
     * @param integer $order_id 订单id
     * @param array $extra 支付返回参数
     * @date_time: 2021/4/12 16:54
     * @return string
     * @throws \think\Exception
     * @author:zhubaodi
     */
    public function afterPay($order_id, $extra)
    {
        fdump_api(['支付回调' => __LINE__,'order_id' => $order_id,'extra' => $extra],'after_pay/PileOrderPayService',1);
        $service_order = new HouseVillagePayOrder();
        $db_plat_order = new PlatOrder();
        if ($extra['current_system_balance'] >= ($extra['paid_money']+$extra['current_system_balance'])) {

            $pay_type = 'balance';
        } else {
            $pay_type = $extra['paid_type'];
        }
        //fdump_api(['支付回调', $pay_type ,$extra],'afterpay/meter',1);
        if ($extra['paid_type'] == 'wechat') {
            $paid_type = 'weixin';
        } else {
            //todo 环球支付
            $paid_type = $extra['paid_type'];
        }
        $service_paid_order = new PayOrderInfo();
        $pay_order_info=$service_paid_order->getByOrderNo($extra['paid_orderid']);
        $pay_order_info_id=0;
        $paid_orderNo='';
        $order_type='';
        $now_time=time();
        if (!empty($pay_order_info) && !$pay_order_info->isEmpty()){
            $pay_order_info=$pay_order_info->toArray();
            $paid_orderNo=$pay_order_info['paid_extra'];
            $pay_order_info_id=$pay_order_info['id'];
            if($pay_order_info['business']=='pile_user_charge_balance'){
                $order_data = [
                    'pay_time' => $extra['paid_time'] ? $extra['paid_time']:$now_time,
                    'pay_money' => $extra['paid_money'],
                    'pay_type' => $paid_type,
                    'third_id' => $paid_orderNo,
                    'orderid'=>isset($extra['paid_orderid'])?$extra['paid_orderid']:'',
                    'is_mobile_pay' => 0,
                    'paid' => $extra['paid'],
                    'system_balance' => $extra['current_system_balance'],
                    'system_score' => $extra['current_score_use'],
                    'system_score_money' => $extra['current_score_deducte'],
                ];
                if (isset($extra['current_village_balance'])){
                    $order_data['village_balance']=$extra['current_village_balance'];
                }
                $db_plat_order->save_one(['business_id' => $order_id,'business_type'=>'pile_user_charge_balance'], $order_data);
                $this->pileUserBalanceAfterPay($order_id,$pay_order_info,$paid_type);
                return $order_id;
            }
            $order_type=$pay_order_info['business'];
        }
        
        $order_info = $service_order->get_one(['order_id' => $order_id]);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }
        $order_type=$order_info['order_type'];
        $pay_orderid=isset($extra['paid_orderid']) ? $extra['paid_orderid']:'';
        $order_data = [
            'pay_time' => $extra['paid_time'] ? $extra['paid_time']:$now_time,
            'pay_money' => $extra['paid_money'],
            'pay_type' => $paid_type,
            'third_id' => $paid_orderNo,
            'orderid'=>isset($extra['paid_orderid'])?$extra['paid_orderid']:'',
            'is_mobile_pay' => 0,
            'paid' => $extra['paid'],
            'system_balance' => $extra['current_system_balance'],
            'system_score' => $extra['current_score_use'],
            'system_score_money' => $extra['current_score_deducte'],
        ];
        if (isset($extra['current_village_balance'])){
            $order_data['village_balance']=$extra['current_village_balance'];
        }
        $db_plat_order->save_one(['business_id' => $order_id,'business_type'=>$order_type], $order_data);
        $plat_order_info = $db_plat_order->get_one(['business_id' => $order_id,'business_type'=>$order_type]);
        $extra['order_name'] = $order_info['order_name'];
        $extra['order_id'] = $plat_order_info['order_id'];
        $extra['pay_time'] = $extra['paid_time'];
        $param = [
            'order_id' => $order_id,
            'plat_order_info' => $extra,
        ];
        $service_order_pile = new HouseVillagePilePayOrder();
        $pile_order_info = $service_order_pile->get_one(['order_id' => $order_id],'*');
        $param['plat_order_info']['pile_pay_order_id']=$pile_order_info['id'];
        $order_record_id=0;
        if(isset($extra['order_record_id'])){
            $order_record_id=$extra['order_record_id'];
        }
        if(isset($extra['pay_order_info_id']) && $extra['pay_order_info_id']){
            $pay_order_info_id=$extra['pay_order_info_id'];
        }
        $record_extra_data=$extra;
        $record_extra_data['house_village_pay_order_id']=$order_id;
        $record_extra_data['after_pay_time']=time();
        $save_paid_order_record=array('source_from'=>1);
        $save_paid_order_record['business_type']='village_pile_pay';
        $save_paid_order_record['house_type']='village_pile_pay';
        $save_paid_order_record['business_name']='充电桩充电支付';
        $save_paid_order_record['uid']=$pile_order_info['uid'] ? $pile_order_info['uid']:$order_info['pay_uid'];
        $save_paid_order_record['order_id']=$pile_order_info['id'];
        $save_paid_order_record['order_no']=$pile_order_info['order_no'];
        $save_paid_order_record['order_type']='village_pile_pay';
        if($pile_order_info['type']!=1){
            $save_paid_order_record['order_type']='village_tcp_pile_pay';
        }
        $save_paid_order_record['order_type_v'] =$pile_order_info['type'];
        $service_user = new User();
        $user_info = $service_user->getOne(['uid' => $save_paid_order_record['uid']]);
        if($user_info && isset($user_info['uid'])){
            $save_paid_order_record['u_phone']=$user_info['phone'] ? $user_info['phone']:'';
            $save_paid_order_record['u_name']=$user_info['nickname'] ? $user_info['nickname']:'';
        }
        $save_paid_order_record['table_name']='house_village_pile_pay_order';
        $save_paid_order_record['business_order_id']=$order_id;
        if(isset($extra['paid_orderid']) && $extra['paid_orderid']){
            $save_paid_order_record['pay_order_no']=$extra['paid_orderid'];
        }
        if($paid_orderNo){
            $save_paid_order_record['third_transaction_no']=$paid_orderNo;
        }
        $save_paid_order_record['order_money']=$pile_order_info['use_money'];
        $save_paid_order_record['pay_money']=$extra['paid_money'];
        $save_paid_order_record['pay_type']=$pay_type;
        $balance_money= $extra['current_system_balance'];
        if(isset($extra['current_village_balance']) && $extra['current_village_balance']>0){
            $balance_money += $extra['current_village_balance'];
        }
        $save_paid_order_record['balance_money'] = $balance_money;
        $save_paid_order_record['is_online']=1;
        $save_paid_order_record['pay_time']=$extra['paid_time'];
        $save_paid_order_record['bind_user_id']=isset($order_info['bind_id']) && $order_info['bind_id'] ? $order_info['bind_id']:0;
        $save_paid_order_record['village_id']=$pile_order_info['village_id'];
        $record_extra_data['order_serial']=$pile_order_info['order_serial'];
        $record_extra_data['equipment_id']=$pile_order_info['equipment_id'];
        $record_extra_data['use_ele']=$pile_order_info['use_ele'];
        $record_extra_data['socket_no']=$pile_order_info['socket_no'];
        $save_paid_order_record['extra_data']=json_encode($record_extra_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $whereArr=array();
        if ($pay_order_info_id > 0) {
            $whereArr[] = array('pay_order_info_id', '=', $pay_order_info_id);
        }else if($order_record_id>0){
            $whereArr[] = array('id', '=', $order_record_id);
        }else{
            $whereArr[]=array('village_id','=',$save_paid_order_record['village_id']);
            $whereArr[]=array('source_from','=',1);
            $whereArr[]=array('order_id','=',$save_paid_order_record['order_id']);
            $whereArr[]=array('order_no','=',$save_paid_order_record['order_no']);
            $whereArr[]=array('table_name','=','house_village_pile_pay_order');
        }
        $paidOrderRecordDb = new PaidOrderRecord();
        $paidOrderRecordInfo = $paidOrderRecordDb->getOneData($whereArr, 'id,update_time');
        if ($paidOrderRecordInfo && !$paidOrderRecordInfo->isEmpty()) {
            $paidOrderRecordDb->saveOneData(array(['id', '=', $paidOrderRecordInfo['id']]), $save_paid_order_record);
        } else {
            $paidOrderRecordDb->addOneData($save_paid_order_record);
        }
        if ($pay_order_info && isset($pay_order_info['pay_config']) && $pay_order_info['pay_config'] && $pay_order_info['channel'] == 'chinaums' && $pay_order_info['env'] == 'wechat_mini') {
            $payOrderConfig = json_decode($pay_order_info['pay_config'], true);
            $chinaumsMoney = $pay_order_info['money']-$pay_order_info['refund_money'];
            if ($chinaumsMoney>0 && $payOrderConfig && isset($payOrderConfig['without_asset']) && $payOrderConfig['without_asset'] == 1) {
                $param['plat_order_info']['is_chinaums_divide']=1;
                $chinaums_merchant_already_get = !empty($pay_order_info['chinaums_merchant_already_get']) ? $pay_order_info['chinaums_merchant_already_get'] : 0;
                $chinaums_merchant_already_get=($chinaumsMoney/$pay_order_info['money'])*$chinaums_merchant_already_get;
                $chinaums_merchant_already_get=$chinaums_merchant_already_get>0 ? round($chinaums_merchant_already_get):0;
                $chinaums_merchant_already_get=intval($chinaums_merchant_already_get);
                
                $chinaums_platform_already_get = !empty($pay_order_info['chinaums_platform_already_get']) ? $pay_order_info['chinaums_platform_already_get'] : 0;
                $divide_money = $chinaums_merchant_already_get / 100;
                $divide_money = $divide_money > 0 ? round($divide_money, 2) : 0;
                $param['plat_order_info']['chinaums_divide_money']=$divide_money;

                $chinaums_platform_already_get=($chinaumsMoney/$pay_order_info['money'])*$chinaums_platform_already_get;
                $system_take=$chinaums_platform_already_get / 100;
                $param['plat_order_info']['chinaums_system_take']=$system_take >0 ? round($system_take,2):0;
            }
        }

        $serviceHouseVillage = new HouseVillageService();
        $lakala_pay_switch=cfg('lakala_pay_switch');
        $lakala_pay_switch = $lakala_pay_switch ? intval($lakala_pay_switch):0;
        $is_lakala_pay_divide=0;
        if($lakala_pay_switch>0 && $pay_order_info && isset($pay_order_info['channel'])&& $pay_order_info['channel'] == 'lakala' && $pay_order_info['pay_config'] && $pay_order_info['paid_extra'] && $pay_order_info['paid_money']>0) {
            try {
                $pay_env = $pay_order_info['env'];
                $pay_config = json_decode($pay_order_info['pay_config'], true);
                $paid_order_no = $pay_order_info['paid_extra'];
                $pay_money = $pay_order_info['paid_money'];
                $mer_get = $pay_money;
                $plat_get = 0;
                $now_village_info = $serviceHouseVillage->getHouseVillageInfo(array('village_id' => $order_info['village_id']), '*');
                if ($now_village_info['percent'] > 0) {
                    $percent = $now_village_info['percent'];
                } else {
                    $percent = cfg('platform_get_village_percent');
                }
                $system_take = 0;
                if ($percent > 0 && $percent < 100) {
                    $system_take = ($pay_money * $percent) / 100;
                    $system_take = round($system_take);
                    $plat_get = $system_take;
                    $mer_get = $pay_money - $system_take;
                }
                $is_lakala_pay_divide=1;

                $mer_get_tmp = $mer_get / 100;
                $mer_get_tmp = round($mer_get_tmp, 2);
                $plat_get_tmp = $plat_get / 100;
                $plat_get_tmp = $plat_get_tmp > 0 ? round($plat_get_tmp, 2) : 0;
                $param['plat_order_info']['is_pay_need_divide']=$is_lakala_pay_divide;
                $param['plat_order_info']['pay_divide_type']='lakala_pay_divide';
                $param['plat_order_info']['divide_village_money']=$mer_get_tmp;
                $param['plat_order_info']['divide_system_take']=0;
                //$param['plat_order_info']['divide_desc'] = "【拉卡拉分账】订单号：".$pay_order_info['orderid']." 小区：".$mer_get_tmp." 平台：".$plat_get_tmp;
                $param['plat_order_info']['divide_desc'] = "【拉卡拉分账】订单号：".$pay_order_info['orderid'];  //改成充电结束后分账 这里的分账数据不对了，不显示
            } catch (\Exception $ee) {
                fdump_api([$paid_order_no, $ee->getMessage(), 'pay_order_info' => $pay_order_info], 'pay/villageLakalaPileFenzhangErr', 1);
            }
        }
        
        try {
            fdump_api(['1支付回调老版' => __LINE__, 'param' => $param], 'after_pay/PileOrderPayService', 1);
            $res = invoke_cms_model('House_village_pay_order/after_pay', $param);
            fdump_api(['2支付回调老版' => __LINE__, 'res' => $res], 'after_pay/PileOrderPayService', 1);
        }catch (\Exception $e){
            fdump_api(['line' . __LINE__, 'err_msg'=>$e->getMessage()], 'after_pay/PileOrderPayService', 1);
        }

        //-----小区住户余额start-------//
        //获取小区用户信息
        $village_user = new StorageService();
        // print_r([$now_order['uid'],$villageId]);
        if(!empty($order_info['pay_uid'])&&!empty($order_info['village_id'])){
            $now_village_user = $village_user->getVillageUser($order_info['pay_uid'],$order_info['village_id']);
        }
        if (empty($now_village_user)) {
            throw new \Exception(L_("小区住户余额信息不存在"), 1003);
        }
        $villageBalancePay=0;
        if (isset($extra['current_village_balance'])){
            //判断小区住户帐户余额,扣除余额
            $villageBalancePay = floatval($extra['current_village_balance']);
            if ($villageBalancePay && $now_village_user['current_money'] < $villageBalancePay) {
                fdump_api(['villageBalancePay'=>$villageBalancePay,'now_village_user'=>$now_village_user,'您的帐户余额不够此次支付'],'haokuaichong/startType3Tocharge',1);
                //throw new \Exception(L_("您的帐户余额不够此次支付"), 1003);
            }
        }
        if ($villageBalancePay > 0) {
            $village_money_data=[
                'uid'=>$order_info['pay_uid'],
                'village_id'=>$order_info['village_id'],
                'type'=>2,
                'current_village_balance'=>$villageBalancePay,
                'role_id'=>0,
                'desc'=>L_("充电桩充电支付，扣除小区住户余额，订单编号X1", array("X1" => $pile_order_info['order_no'])),
                'order_id'=>$pile_order_info['order_id'],
                'order_type'=>1,
            ];
            $villageUseResult = $village_user->addVillageUserMoney($village_money_data);
            if ($villageUseResult['error_code']) {
                throw new \Exception($villageUseResult['msg'], 1003);
            }
        }
        //-------小区住户余额end--------//
        //获取购买用户信息
        $userService = new UserService();
        $buyer = $userService->getUser($order_info['pay_uid'], 'uid');
        if (empty($buyer)) {
            throw new \think\Exception(L_("购买用户不存在"), 1003);
        }
        //判断帐户余额,扣除余额
        $balancePay = floatval($extra['current_system_balance']);
        if ($balancePay && $buyer['now_money'] < $balancePay) {
            throw new \think\Exception(L_("您的帐户余额不够此次支付"), 1003);
        }
        if ($balancePay > 0) {
            $useResult = (new UserService())->userMoney($order_info['pay_uid'], $balancePay, L_("充电桩支付，扣除余额，订单编号X1", array("X1" => $order_info['order_no'])));
            if ($useResult['error_code']) {
                throw new \think\Exception($useResult['msg'], 1003);
            }
        }
        //积分抵扣，扣除积分
        $scoreUsedCount = $order_info['score_used_count'];
        if ($scoreUsedCount && $buyer['score_count'] < $scoreUsedCount) {
            throw new \think\Exception(L_("您的积分余额不够此次支付"), 1003);
        }
        if ($scoreUsedCount > 0) {
            $scoreParam=array('order_id'=>$order_info['id'],'score_type'=>21,'house_type'=>1,'order_type'=>'village_pile_pay','village_id'=>$order_info['village_id']);
            $use_result = (new UserService())->userScore($order_info['uid'], $scoreUsedCount, L_("充电桩支付 ，扣除X1 ，订单编号X2", array("X1" => cfg('score_name'), "X2" => $order_info['order_no'])),0,$scoreParam);
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['msg'], 1003);
            }
        }
        
        if ($pay_type == 'balance') {
            $pay_type_pile = 1;
        }elseif ($pay_type== 'wechat'||$pay_type== 'weixin') {
            $pay_type_pile = 2;
        } else {
            $pay_type_pile = 4;
        }
        //todo 环球支付
        if(isset($extra['is_hqpay']) && intval($extra['is_hqpay']) == 1){
            $pay_type_pile=($extra['hqpay_source'] == 'weixin') ? 2: 4;
        }
        if (isset($extra['paid_time']) && intval($extra['paid_time']) > 1) {
            $payTime = intval($extra['paid_time']);
        } else {
            $payTime = time();
        }
        $order_data_pile = [
            'pay_time' => $payTime,
            'pay_type' => $pay_type_pile,
        ];
        if (isset($extra['current_system_balance']) && $extra['current_system_balance']) {
            $order_data_pile['current_system_balance'] = $extra['current_system_balance'];
        }
        if($pile_order_info && isset($pile_order_info['third_paid_orderid'])){
            $order_data_pile['pay_orderid']=$pay_orderid;
            $order_data_pile['third_paid_orderid']=$paid_orderNo ? $paid_orderNo:'';
        }
        if($lakala_pay_switch>0 && $is_lakala_pay_divide==1) {
            //先标记上等 充电结束分账
            $order_data_pile['is_fenzhang']=1;
            $order_data_pile['fz_type']='lakala_divide';
        }
        fdump_api(['更改记录' => __LINE__,'order_data_pile' => $order_data_pile],'after_pay/PileOrderPayService',1);
        $orderId = $service_order_pile->saveOne(['id' => $pile_order_info['id']], $order_data_pile);
        fdump_api(['1更改记录' => __LINE__,'orderId' => $orderId, 'pile_order_info' => $pile_order_info],'after_pay/PileOrderPayService',1);
        if ($pile_order_info['order_type'] == 1) {
            //开启充电
            $service_pile = new PileUserService();
            if ($pile_order_info['type'] == 1) {
                $service_pile->newPriceStandardCharge($pile_order_info['id']);
            }elseif($pile_order_info['type'] == 21) {
                $service_pile->outCharge($pile_order_info['id'],2);
            }else if($pile_order_info['type'] == 3){
                $param=array('village_id'=>$pile_order_info['village_id'],'order_pile_id'=>$pile_order_info['id'],'equipment_num'=>'');
                $invokeres = invoke_cms_model('House_village_pile_equipment/startType3Tocharge', $param);
                fdump_api(['param'=>$param,'invokeres'=>$invokeres],'haokuaichong/startType3Tocharge',1);
                if($invokeres && isset($invokeres['retval']) && !empty($invokeres['retval']) &&  isset($invokeres['retval']['data'])){
                    $res=$invokeres['retval']['data'];
                }
            }else {
                $service_pile->newPriceStandardCharge_tcp($pile_order_info['id']);
            }
        } elseif ($pile_order_info['order_type'] == 2) {
            $service_card_user = new HouseVillagePileMonthCardUser();
            $card_where = [
                ['uid', '=', $pile_order_info['uid']],
                ['status', '=', 0],
                ['expire_time', '>', time()]
            ];

            $carg_list=$service_card_user->getList($card_where)->toArray();
            if (!empty($carg_list)){
                rsort($carg_list);
                $data_card = ['status' => 1];
                $service_card_user->saveOne(['id'=>$carg_list[0]['id']], $data_card);
            }
            
        }
        sleep(1);
        if($pay_order_info && isset($pay_order_info['channel']) && $pay_order_info['channel']=='chinaums'){
            $this->chinaums_order_divide($pile_order_info['id'],$pile_order_info,$pay_order_info['orderid']);
        }
        /***** 这里注释掉 等充电结束后这个流程走完后 在分账
        if($lakala_pay_switch>0 && $is_lakala_pay_divide==1) {
            try {
                $pay_env = $pay_order_info['env'];
                $pay_config = json_decode($pay_order_info['pay_config'], true);
                $lakalaService = new   \app\pay\model\service\channel\LakalaService($pay_env, $pay_config);
                $paid_order_no = $pay_order_info['paid_extra'];
                $pay_money = $pay_order_info['paid_money'];
                $mer_get = $pay_money;
                $plat_get = 0;
                $now_village_info = $serviceHouseVillage->getHouseVillageInfo(array('village_id' => $order_info['village_id']), '*');
                if ($now_village_info['percent'] > 0) {
                    $percent = $now_village_info['percent'];
                } else {
                    $percent = cfg('platform_get_village_percent');
                }
                $system_take = 0;
                if ($percent > 0 && $percent < 100) {
                    $system_take = ($pay_money * $percent) / 100;
                    $system_take = round($system_take);
                    $plat_get = $system_take;
                    $mer_get = $pay_money - $system_take;
                }

                $lakalafz_ret = $lakalaService->fenzhang($paid_order_no, $mer_get, $plat_get);
                $mer_get_tmp = $mer_get / 100;
                $mer_get_tmp = round($mer_get_tmp, 2);
                $plat_get_tmp = $plat_get / 100;
                $plat_get_tmp = $plat_get_tmp > 0 ? round($plat_get_tmp, 2) : 0;
                $xdesc ="【拉卡拉分账】订单号：".$pay_order_info['orderid']." 小区：".$mer_get_tmp." 平台：".$plat_get_tmp;
                fdump_api(['order_id'=>$order_id,'paid_order_no'=>$paid_order_no,'xdesc'=>$xdesc ,'lakalafz_ret'=>$lakalafz_ret], 'pay/villageLakalaPileFenzhang', 1);
            } catch (\Throwable $ee) {
                fdump_api([$paid_order_no, $ee->getMessage(), 'pay_order_info' => $pay_order_info], 'pay/villageLakalaPileFenzhangErr', 1);

            }
        }
        */

        return $order_id;

    }


    public function chinaums_order_divide($pile_order_id=0,$order_info=array(),$paid_orderid='')
    {
        $service_order_pile_db = new HouseVillagePilePayOrder();
        if($pile_order_id>0 && (empty($order_info) || !isset($order_info['pay_orderid']))){
            $whereOrder = [];
            $whereOrder[] = ['id', '=', $pile_order_id];
            $order_info = $service_order_pile_db->get_one($whereOrder,'*');
        }
        if(!empty($order_info) && !empty($order_info['pay_orderid']) && empty($paid_orderid)){
            $paid_orderid=$pile_order_id['pay_orderid'];
        }
        if(empty($paid_orderid)){
            return false;
        }
        $is_chinaums_divide=0;
        $db_pay_order_info = new PayOrderInfo();
        $pay_order_info = $db_pay_order_info->getByOrderNo($paid_orderid);
        if ($pay_order_info && isset($pay_order_info['pay_config']) && $pay_order_info['pay_config'] && $pay_order_info['channel'] == 'chinaums' && $pay_order_info['env'] == 'wechat_mini') {
            $payOrderConfig = json_decode($pay_order_info['pay_config'], true);
            $chinaumsMoney = $pay_order_info['money']-$pay_order_info['refund_money'];
            if ($chinaumsMoney>0 && $payOrderConfig && isset($payOrderConfig['without_asset']) && $payOrderConfig['without_asset'] == 1) {
                $is_chinaums_divide=1;
                $chinaumsOrderid = $pay_order_info['orderid'];
                $chinaums_merchant_already_get = !empty($pay_order_info['chinaums_merchant_already_get']) ? $pay_order_info['chinaums_merchant_already_get'] : 0;
                $chinaums_merchant_already_get=($chinaumsMoney/$pay_order_info['money'])*$chinaums_merchant_already_get;
                $chinaums_merchant_already_get=$chinaums_merchant_already_get>0 ? round($chinaums_merchant_already_get):0;
                $chinaums_merchant_already_get=intval($chinaums_merchant_already_get);
                $chinaums_platform_already_get = !empty($pay_order_info['chinaums_platform_already_get']) ? $pay_order_info['chinaums_platform_already_get'] : 0;
                $subOrders = [
                    ['merId' => $payOrderConfig['sub_mer_no'], 'satAmt' => $chinaums_merchant_already_get],
                ];
                $divide_money = $chinaums_merchant_already_get / 100;
                $divide_money = $divide_money > 0 ? round($divide_money, 2) : 0;
                try {
                    $chinaumsService = new ChinaumsService('wechat_mini', $payOrderConfig);
                    $result = $chinaumsService->fenzhangConfirm($chinaumsOrderid, $chinaumsMoney, $subOrders);
                    fdump_api(['payOrderConfig'=>$payOrderConfig, 'chinaumsOrderid'=>$chinaumsOrderid, 'chinaumsMoney'=>$chinaumsMoney, 'subOrders'=>$subOrders,'result'=>$result], 'after_pay/000chinaums_divide', 1);
                    if ($result) {
                        $db_pay_order_info->updateThis(['orderid' => $chinaumsOrderid, 'channel' => 'chinaums', 'paid' => 1], ['async_confirm_result' => json_encode($result, JSON_UNESCAPED_UNICODE)]);
                    }
                } catch (\Exception $e) {
                    $err_msg = $e->getMessage();
                    fdump_api([__LINE__,'payOrderConfig'=>$payOrderConfig, 'chinaumsOrderid'=>$chinaumsOrderid, 'chinaumsMoney'=>$chinaumsMoney, 'subOrders'=>$subOrders,'err_msg'=>$e->getMessage()], 'after_pay/000chinaums_divide', 1);
                }
            }
        }
        if($is_chinaums_divide<1 && $pay_order_info && isset($pay_order_info['channel'])&& $pay_order_info['channel'] == 'chinaums' && $pay_order_info['chinaums_merchant_already_get']>0) {
            $chinaums_divide_record=array('village_id'=>$order_info['village_id'],'chinaums_zg_mid'=>'');
            $db_house_village_pay_config  = new HouseVillagePayConfig();
            $whereConfig=array(['village_id','=',$order_info['village_id']]);
            $village_pay_config= $db_house_village_pay_config->getOne($whereConfig,"*");
            if($village_pay_config && !$village_pay_config->isEmpty() && isset($village_pay_config['chinaums_zg_mid']) && $village_pay_config['chinaums_zg_mid']){
                $villageChinaumsDivideRecordDb= new VillageChinaumsDivideRecord();
                $chinaums_divide_record['chinaums_zg_mid']=$village_pay_config['chinaums_zg_mid'];
                $chinaums_divide_record['village_money']=$pay_order_info['chinaums_merchant_already_get'];
                $chinaums_divide_record['platform_money']=$pay_order_info['chinaums_platform_already_get'];
                $chinaums_divide_record['dividend_status']=1;
                $chinaums_divide_record['pay_order_info_id']=$pay_order_info['id'];
                $chinaums_divide_record['b_order_id']=$order_info['id'];
                $chinaums_divide_record['order_table_type']='house_village_pile_pay_order';
                $chinaums_divide_record['add_time']=time();
                $whereDivideRecord=array(['pay_order_info_id','=',$pay_order_info['id']],['village_id','=',$order_info['village_id']],['b_order_id','=',$order_info['id']]);
                $haveDivideRecord=$villageChinaumsDivideRecordDb->getOneData($whereDivideRecord);
                if(empty($haveDivideRecord) || $haveDivideRecord->isEmpty()){
                    $villageChinaumsDivideRecordDb->addOneData($chinaums_divide_record);
                }
            }
        }
        return true;
    }
    /***
    ***充值支付回调
     ****/
    public function pileUserBalanceAfterPay($order_id=0,$pay_order_info=array(),$paid_type=''){
        $houseVillagePileUserBalanceLog = new HouseVillagePileUserBalanceLog();
        $houseVillagePileUserBalance = new HouseVillagePileUserBalance();
        $chargeOrderObj=$houseVillagePileUserBalanceLog->getOneData(array('id'=>$order_id,'opt_type_flag'=>'charge_order'));
        $nowtime=time();
        if($chargeOrderObj && !$chargeOrderObj->isEmpty()){
            $chargeOrder= $chargeOrderObj->toArray();
            $third_id='';
            $pay_order_no='';
            $pay_order_info_id=0;
            $pay_time=$nowtime;
            if($pay_order_info){
                $third_id=$pay_order_info['paid_extra'];
                $pay_order_no=$pay_order_info['orderid'];
                $pay_order_info_id=$pay_order_info['id'];
                $pay_time=$pay_order_info['paid_time'];
                if(empty($paid_type) && $pay_order_info['pay_type']){
                    $paid_type=$pay_order_info['pay_type'];
                }
            }
            $saveArr=array('pay_time'=>$pay_time,'pay_type'=>$paid_type,'third_id'=>$third_id,'pay_order_no'=>$pay_order_no,'pay_order_info_id'=>$pay_order_info_id);
            $houseVillagePileUserBalanceLog->saveOneData(array('id'=>$chargeOrder['id']),$saveArr);
            $userBalanceObj=$houseVillagePileUserBalance->getOneData(array('uid'=>$chargeOrder['uid']));
            $userBalance='';
            if($userBalanceObj && !$userBalanceObj->isEmpty()){
                $userBalance= $userBalanceObj->toArray();
            }
            $saveUserArr=array('update_time'=>$nowtime,'village_id'=>$chargeOrder['village_id']);
            $add_money= $chargeOrder['money']+$chargeOrder['give_money'];
            $give_money = $chargeOrder['give_money'];
            $recharge_money=$chargeOrder['money'];
            $before_money=0;
            $after_money=$add_money;
            if($userBalance && isset($userBalance['uid'])){
                $saveUserArr['current_money']=$userBalance['current_money']+$add_money;
                $after_money=$saveUserArr['current_money'];
                $before_money=$userBalance['current_money'];
                $saveUserArr['add_money']=$add_money+$userBalance['add_money'];
                $saveUserArr['give_money']=$give_money+$userBalance['give_money'];
                $saveUserArr['recharge_money']=$recharge_money+$userBalance['recharge_money'];
                $houseVillagePileUserBalance->saveOneData(array('uid'=>$chargeOrder['uid']),$saveUserArr);
            }else{
                $saveUserArr['add_money']=$add_money;
                $saveUserArr['uid']=$chargeOrder['uid'];
                $saveUserArr['current_money']=$add_money;
                $saveUserArr['give_money']=$give_money;
                $saveUserArr['recharge_money']=$recharge_money;
                $saveUserArr['add_time']=$nowtime;
                $houseVillagePileUserBalance->addOneData($saveUserArr);
            }
            
            //添加记录
            $balanceLogArr=array('uid'=>$chargeOrder['uid'],'village_id'=>$chargeOrder['village_id']);
            $balanceLogArr['before_money']=$before_money;
            $balanceLogArr['money']=$chargeOrder['money'];
            $balanceLogArr['give_money']=$chargeOrder['give_money'];
            $balanceLogArr['after_money']=$after_money;
            $balanceLogArr['opt_type']=1;
            $balanceLogArr['opt_type_flag']='user_charge_balance';
            $balanceLogArr['remarks']='用户余额充值';
            $balanceLogArr['add_time']=$nowtime;
            $balanceLogArr['pay_time']=$pay_time;
            $balanceLogArr['pay_type']=$paid_type;
            $balanceLogArr['third_id']=$third_id;
            $balanceLogArr['equipment_id']=$chargeOrder['equipment_id'];
            $balanceLogArr['pay_order_no']=$pay_order_no;
            $balanceLogArr['pay_order_info_id']=$pay_order_info_id;
            $balanceLogArr['extra_data']=$chargeOrder['extra_data'];
            $idd=$houseVillagePileUserBalanceLog->addOneData($balanceLogArr);
            return $idd;
        }
        return false;
    }
    public function saveUserChargeOrder($pdata=array()){
        if(!isset($pdata['uid']) || empty($pdata['uid'])){
            throw new \think\Exception("用户参数错误，请重试！");
        }
        if(!isset($pdata['charge_item']) || empty($pdata['charge_item'])){
            throw new \think\Exception("充值金额项目数据不存在！");
        }
        $houseVillagePileUserBalanceLog = new HouseVillagePileUserBalanceLog();
        $service_equipment = new HouseVillagePileEquipment();
        $saveArr=array('uid'=>$pdata['uid']);
        if(isset($pdata['village_id'])){
            $saveArr['village_id']=$pdata['village_id'];
        }
        $saveArr['money']=$pdata['charge_item']['recharge_money'];
        $saveArr['give_money']=$pdata['charge_item']['give_money'];
        $saveArr['opt_type']=0;
        $saveArr['equipment_id']=$pdata['e_id'];
        if($pdata['e_id']){
            $equipmentInfo = $service_equipment->getInfo(array('id'=>$pdata['e_id']), '*');
            if($equipmentInfo && !$equipmentInfo->isEmpty()){
                $saveArr['village_id']=$equipmentInfo['village_id'];
            }
        }
        $saveArr['opt_type_flag']='charge_order';
        $saveArr['add_time']=time();
        $saveArr['extra_data'] =json_encode($pdata['charge_item'],JSON_UNESCAPED_UNICODE);
        $order_id=$houseVillagePileUserBalanceLog->addOneData($saveArr);
        if($order_id>0){
            $pay_order_param = array(
                'business_type' => 'pile_user_charge_balance',
                'business_id' => $order_id,
                'order_name' => '充电桩用户余额充值',
                'uid' => $pdata['uid'],
                'total_money' =>  $saveArr['money'],
                'wx_cheap' => 0,
                'add_time'=>time(),
                'is_own' => 0
            );
            $db_plat_order = new PlatOrder();
            $plat_order_result = $db_plat_order->add_order($pay_order_param);
            return $order_id;
        }else{
            throw new \think\Exception("订单生成失败！");
        }
    }
    public function getPileUserBalanceList($whereArr, $field='b.*,u.nickname,u.openid',$orderBy='b.id desc', $page=1, $limit=10,$is_export=false){
        $houseVillagePileUserBalance = new HouseVillagePileUserBalance();
        $returnArr=array('list'=>array(),'total'=>0,'page_size'=>$limit);
        $total=$houseVillagePileUserBalance->getCountJoinUser($whereArr);
        if($total>0){
            $returnArr['total']= $total;
            $listObj=$houseVillagePileUserBalance->getListJoinUser($whereArr,$field,$orderBy,$page,$limit);
            if($listObj && !$listObj->isEmpty()){
                $export_Arr=array();
                $list=$listObj->toArray();
                foreach ($list as $kk=>$vv){
                    $list[$kk]['update_time_str']=$vv['update_time']>100 ? date('Y-m-d H:i:s',$vv['update_time']):'';
                    if($is_export){
                        $tmpArr=array('uid'=>$vv['uid'],'nickname'=>$vv['nickname'],'phone'=>$vv['phone'],'current_money'=>$vv['current_money'],'update_time'=>$list[$kk]['update_time_str']);
                        $export_Arr[]=$tmpArr;
                    }
                }
                if($is_export){
                    $returnArr['list']=$export_Arr;
                }else{
                    $returnArr['list']=$list;
                }
                
            }
        }
        return $returnArr;
        
    }
    public function getPileUserBalanceInfo($whereArr=array(),$field='*'){
        $houseVillagePileUserBalance=new HouseVillagePileUserBalance();
        $tmpObj=$houseVillagePileUserBalance->getOneData($whereArr);
        $userBalance=array();
        if($tmpObj && !$tmpObj->isEmpty()){
            $userBalance=$tmpObj->toArray();
        }
        return $userBalance;
    }
    public function getPileUserBalanceLogList($whereArr, $field='bl.*,u.nickname,u.openid',$orderBy='bl.id desc', $page=1, $limit=10,$is_export=false){
        $houseVillagePileUserBalanceLog = new HouseVillagePileUserBalanceLog();
        $returnArr=array('list'=>array(),'total'=>0,'page_size'=>$limit);
        $total=$houseVillagePileUserBalanceLog->getCountJoinUser($whereArr);
        $opt_type_flag_arr=array('charge_order'=>'充值订单','user_charge_balance'=>'余额充值','order_use_balance'=>'余额充电','order_refund_balance'=>'充电退款', 'user_withdraw' => '申请退款成功');
        if($total>0){
            $houseVillageDb=new HouseVillage();
            $villageArr=array();
            $returnArr['total']= $total;
            $pay_type_arr = ['wechat' => '微信支付', 'weixin' => '微信支付','aiHorsePay' => '通联支付','alipay' => '支付宝', 'unionpay' => '银联支付','hqpay_wx'=>'环球汇通微信支付','hqpay_al'=>'环球汇通支付宝支付','farmersbankpay'=>'仪征农商行支付','abchina'=>'农行缴费中心支付','constructionPay'=>'建行支付','constructionApp'=>'建行app支付'];
            $listObj=$houseVillagePileUserBalanceLog->getListJoinUser($whereArr,$field,$orderBy,$page,$limit);
            if($listObj && !$listObj->isEmpty()){
                $list=$listObj->toArray();
                $export_Arr=array();
                foreach ($list as $kk=>$vv){
                    $list[$kk]['add_time_str']=$vv['add_time']>100 ? date('Y-m-d H:i:s',$vv['add_time']):'';
                    $list[$kk]['opt_type_flag_str']=isset($opt_type_flag_arr[$vv['opt_type_flag']]) ? $opt_type_flag_arr[$vv['opt_type_flag']]:'';
                    $village_name='';
                    if(isset($villageArr[$vv['village_id']]) && $villageArr[$vv['village_id']]){
                        $village_name=$villageArr[$vv['village_id']];
                    }elseif($vv['village_id']>0){
                       $villageTmpObj= $houseVillageDb->getInfo(array('village_id'=>$vv['village_id']),'village_name');
                       if($villageTmpObj && !$villageTmpObj->isEmpty()){
                           $village_name=$villageTmpObj['village_name'];
                           $villageArr[$vv['village_id']]=$village_name;
                       }
                    }
                    $list[$kk]['village_name']=$village_name;
                    $list[$kk]['pay_time_str']=$vv['pay_time']>100 ? date('Y-m-d H:i:s',$vv['pay_time']):'';
                    $list[$kk]['pay_type_str']=$vv['pay_type'] && isset($pay_type_arr[$vv['pay_type']]) ? $pay_type_arr[$vv['pay_type']]:$vv['pay_type'];
                    $list[$kk]['withdraw_status_str']='';
                    if ($vv['opt_type_flag'] == 'user_withdraw' && $vv['opt_type'] == 3) {
                        $list[$kk]['withdraw_status_str'] = '处理中';
                        if ($vv['withdraw_status'] == 1) {
                            $list[$kk]['withdraw_status_str'] = '退款成功';
                            if(empty($vv['withdraw_bak'])){
                                $list[$kk]['withdraw_bak'] ='已退款充值金额'.$vv['withdraw_money'].'元，扣除赠送金额'.$vv['withdraw_give_money'].'元';
                            }
                        } elseif ($vv['withdraw_status'] == 2) {
                            $list[$kk]['withdraw_status_str'] = '退款失败了';
                        } elseif ($vv['withdraw_status'] == 3) {
                            $list[$kk]['withdraw_status_str'] = '退款驳回';
                        }
                    } else if ($vv['opt_type_flag'] == 'user_withdraw' && $vv['opt_type'] == 2) {
                        $list[$kk]['withdraw_status_str'] = '申请退款成功';
                        $list[$kk]['opt_type_flag_str'] = '申请退款成功';
                    }
                    $list[$kk]['withdraw_time_str']=$vv['withdraw_time'] > 100 ? date('Y-m-d H:i:s', $vv['withdraw_time']) : '';
                    
                    if($is_export){
                        $money=$vv['money'];
                        if($vv['opt_type']==1){
                            $money='+'.$vv['money'];
                        }elseif($vv['opt_type']==2){
                            $money='-'.$vv['money'];
                        }
                        $tmpArr=array('uid'=>$vv['uid'],'nickname'=>$vv['nickname'],'phone'=>$vv['phone'],'money'=>$money,'give_money'=>$vv['give_money']>0 ? '+'.$vv['give_money']:'');
                        $tmpArr['after_money']=$vv['after_money'];
                        $tmpArr['opt_type_flag']=$list[$kk]['opt_type_flag_str'];
                        $tmpArr['village_name']=$village_name;
                        $tmpArr['add_time_str']=$list[$kk]['add_time_str'];
                        $other_info="订单ID：".$vv['pile_pay_order_id']."\n订单编号：".$vv['pay_order_no'];
                        if($vv['opt_type_flag']=='user_charge_balance'){
                            $other_info="支付时间：".$list[$kk]['pay_time_str']."\n支付类型：".$list[$kk]['pay_type_str']."\n支付订单号：".$vv['pay_order_no']."\n第三方流水号：".$vv['third_id'];
                        }
                        $tmpArr['other_info']=$other_info;
                        $export_Arr[]=$tmpArr;
                    }
                }
                if($is_export){
                    $returnArr['list']=$export_Arr;
                }else{
                    $returnArr['list']=$list;
                }
            }
        }
        return $returnArr;

    }

    public function excelExportPileUserBalanceOpt($param = array())
    {
        $returnArr = array();
        $returnArr['lists'] = array();
        $returnArr['export_title'] = array();
        if ($param['xtype'] == 1) {
            //1用户余额
            try {
                $returnArr['export_title'] = array('用户UID', '用户昵称', '用户手机号', '用户充电桩余额', '余额最后变动时间');
                $field = isset($param['field']) && $param['field'] ? $param['field'] : 'b.*,u.nickname,u.phone';
                $order = isset($param['order']) && $param['order'] ? $param['order'] : 'b.id desc';
                $balanceList = $this->getPileUserBalanceList($param['where'], $field, $order, 0, 1000,true);
                $returnArr['lists'] = $balanceList['list'];
            } catch (\Exception $e) {

            }
        } elseif ($param['xtype'] == 2) {
            //2余额详细
            try {
                $returnArr['export_title'] = array('用户UID', '用户昵称', '用户手机号', '余额变动', '充值赠送', '余额变动后余额', '余额变动类型', '余额变动小区', '余额变动时间', '其他信息');
                $field = isset($param['field']) && $param['field'] ? $param['field'] : 'bl.*,u.nickname,u.phone';
                $order = isset($param['order']) && $param['order'] ? $param['order'] : 'bl.id desc';
                $balanceList = $this->getPileUserBalanceLogList($param['where'], $field, $order, 0, 1000,true);
                $returnArr['lists'] = $balanceList['list'];
            } catch (\Exception $e) {

            }
        }
        return $returnArr;
    }
}