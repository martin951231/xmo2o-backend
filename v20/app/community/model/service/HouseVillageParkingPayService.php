<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/28 14:07
 */

namespace app\community\model\service;


use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePilePayOrder;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePileAttributeSet;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\HouseVillagePileEquipmentAttribute;
use app\community\model\db\HouseVillagePileMonthCard;
use app\community\model\db\HouseVillagePileMonthCardUser;
use app\community\model\db\HouseVillagePileAttribute;


class HouseVillageParkingPayService
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
    public function addOrderInfo($data)
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
                    'bind_id' => '',
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
                $card_info = $service_card->getOne(['id' => $data['card_id'],'status'=>1,'is_del'=>1]);
                if (!$card_info) {
                    throw new \think\Exception("月卡信息不存在！");
                }

                $service_card_user = new HouseVillagePileMonthCardUser();
                $card_user_info = $service_card_user->getOne(['uid' => $data['uid']]);
                if ($card_user_info && $card_user_info['expire_time'] > time() && $card_user_info['status'] == 1) {
                    throw new \think\Exception("您当前有未到期的月卡，需到期后才能继续购买月卡！");
                }
                $order_data = [
                    'order_name' => '充电桩购买月卡',
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
                    'order_no' => $order_data['order_no'],
                    'use_money' => $data['price'],
                    'add_time' => time(),
                ];

                $order_user = [
                    'uid' => $data['uid'],
                    'card_id' => $data['card_id'],
                    'price' => $card_info['price'],
                    'all_num' => $card_info['all_num'],
                    'per_price' => $card_info['per_price'],
                    'surplus_num' => $card_info['all_num'],
                    'max_num' => $card_info['max_num'],
                    'use_month' => $card_info['use_month'],
                    'buy_time' => time(),
                    'expire_time' => strtotime('+'.$card_info['use_month'].'month'),
                    'add_time' => time(),
                    'status' => 0,
                    'village_id' => $card_info['village_id'],
                ];

                $service_card_user->insertCard($order_user);

            }
        }
        elseif($equipment_info['type'] == 21){
            if ($data['type'] ==1) {
                $portNum=$data['portNum']-1;
                if ($portNum<=9){
                    $portNum='0'.$portNum;
                }
                $order_data = [
                    'order_name' => '艾特充充电桩充电支付',
                    'order_type' => 'pile',
                    'order_no' =>build_real_orderid($data['uid']),
                    'uid' => $data['uid'],
                    'village_id' => $equipment_info['village_id'],
                    'bind_id' => '',
                    'money' => $data['price'],
                    'time' => time(),
                    'paid' => 0,
                    'pay_uid' => $data['uid'],
                ];
                $order_pile = [
                    'village_id' => $equipment_info['village_id'],
                    'order_type' => 1,
                    'order_no' => date('ymdhis').mt_rand(10000000,99999999).'00'.$equipment_info['equipment_serial'].$portNum,
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
        else {
            if ($data['type'] ==1) {
                $order_data = [
                    'order_name' => 'tcp充电桩充电支付',
                    'order_type' => 'pile',
                    'order_no' => build_real_orderid($data['uid']),
                    'uid' => $data['uid'],
                    'village_id' => $equipment_info['village_id'],
                    'bind_id' => '',
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
    public function getOrderPayInfo($order_id)
    {
        if (empty($order_id)) {
            throw new \think\Exception("请上传订单id！");
        }
        $service_order = new HouseVillagePayOrder();
        $order_info = $service_order->get_one(['order_id' => $order_id]);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }
        $data = [
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
    public function getPayResultUrl($order_id)
    {
        if (empty($order_id)) {
            throw new \think\Exception("请上传订单id！");
        }
        $service_order = new HouseVillagePayOrder();
        $order_info = $service_order->get_one(['order_id' => $order_id]);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }
        $service_order_pile = new HouseVillagePilePayOrder();
        $order_pile_id = $service_order_pile->get_one(['order_id' => $order_id]);

        $service_equipment = new HouseVillagePileEquipment();
        $equipment_info = $service_equipment->getInfo(['id' => $order_pile_id['equipment_id']]);
        if (!$equipment_info) {
            throw new \think\Exception("设备不存在！");
        }
        if ($equipment_info['type']==21){
            $url = get_base_url('pages/village/smartCharge/paySuccess?type=21&qcodeNum=' .$equipment_info['equipment_num']);
        }else{
            if($equipment_info['device_family']==1){
                $url = get_base_url('pages/village/smartCharge/paySuccess?deviceId=' .$equipment_info['equipment_num']);
            }else{
                $url = get_base_url('pages/village/smartCharge/paySuccess?qcodeNum=' .$equipment_info['equipment_sn']);
            }
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
        $service_order = new HouseVillagePayOrder();
        $order_info = $service_order->get_one(['order_id' => $order_id]);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }
        if ($extra['current_system_balance'] >= ($extra['paid_money']+$extra['current_system_balance'])) {
            $pay_type = 'balance';
        } else {
            $pay_type = $extra['paid_type'];
        }
        //fdump_api(['支付回调', $pay_type ,$extra],'afterpay/meter',1);
     /*   $order_data = [
            'pay_time' => time(),
            'order_pay_type' => $pay_type,
            'order_status' => 1,
        ];

        $service_order->save_one(['order_id' => $order_id], $order_data);*/
        $extra['order_name']=$order_info['order_name'];
        $param=[
            'order_id'=>$order_id,
            'plat_order_info'=>$extra,
        ];

        $res = invoke_cms_model('House_village_pay_order/after_pay', $param);

        $service_order_pile = new HouseVillagePilePayOrder();

        $pile_order_info = $service_order_pile->get_one(['order_id' => $order_id]);
        if ($pay_type != 'balance') {
            $pay_type_pile = 2;
        } else {
            $pay_type_pile = 1;
        }
        $order_data_pile = [
            'pay_time' => time(),
            'pay_type' => $pay_type_pile,
            'current_system_balance' => $extra['current_system_balance'],
        ];
        $orderId = $service_order_pile->saveOne(['id' => $pile_order_info['id']], $order_data_pile);
        if ($pile_order_info['order_type'] == 1) {
            //开启充电
            $service_pile = new PileUserService();
            if ($pile_order_info['type'] == 1) {
                $service_pile->newPriceStandardCharge($pile_order_info['id']);
            }elseif($pile_order_info['type'] == 21) {
                $service_pile->outCharge($pile_order_info['id'],2);
            }else {
                $service_pile->newPriceStandardCharge_tcp($pile_order_info['id']);
            }
        }elseif ($pile_order_info['order_type'] == 2){
            $service_card_user = new HouseVillagePileMonthCardUser();
            $card_where=[
                ['uid','=',$pile_order_info['uid']],
                ['status','=',0],
                ['expire_time','>',time()]
            ];
            $card_user=$service_card_user->get_one($card_where);
            $data_card=['status'=>1];
            $service_card_user->saveOne(['id'=>$card_user['id']],$data_card);
        }


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
            $useResult = (new UserService())->userMoney($order_info['pay_uid'], $balancePay, L_("电表充值，扣除余额，订单编号X1", array("X1" => $order_info['order_no'])));
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
            $use_result = (new UserService())->userScore($order_info['uid'], $scoreUsedCount, L_("电表充值 ，扣除X1 ，订单编号X2", array("X1" => cfg('score_name'), "X2" => $order_info['order_no'])));
            if ($use_result['error_code']) {
                throw new \think\Exception($use_result['msg'], 1003);
            }
        }
        return $order_id;

    }
}