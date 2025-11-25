<?php
/**
 * Created by PhpStorm.
 * User: zhubaodi
 * Date Time: 2021/4/12 17:23
 */

namespace app\community\model\service;

use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\Express;
use app\community\model\db\HouseMeterAdminElectric;
use app\community\model\db\HouseMeterUserPayorder;
use app\community\model\db\HouseMeterElectricPrice;
use app\community\model\db\HouseMeterReadingSys;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;
use app\community\model\service\HousePaidOrderRecordService;
use app\pay\model\db\PayOrderInfo;
class HouseMeterPayService
{
    public $base_url = '';
    public $street_url = '';


    //支付类型 alipay:支付宝 wechat:微信 unionpay:银联
    public $pay_type_arr1 = [
        'wechat' => '1', 'alipay' => '2', 'unionpay' => '3'
    ];


    /**
     * 生成订单
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @param string $charge_price 充值金额
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/4/12 16:07
     */
    public function addOrderInfo($uid, $electric_id, $charge_price)
    {
        $service_user = new User();
        $user_info = $service_user->getOne(['uid' => $uid]);
        if (!$user_info) {
            throw new \think\Exception("该用户不存在！");
        }

        $service_electric = new HouseMeterAdminElectric();
        $electric_info = $service_electric->getOne($electric_id);
        if (!$electric_info) {
            throw new \think\Exception("电表信息不存在！");
        }

        if (empty($electric_info['electric_price_id'])&&empty($electric_info['unit_price'])) {
            $service_vacancy = new HouseVillageUserVacancy();
            $house_type = $service_vacancy->getOne([['pigcms_id' ,'=', $electric_info['vacancy_id']], ['status', 'in','1,2,3']]);
            $service_price = new HouseMeterElectricPrice();
            $electric_price = $service_price->getInfo(['city_id' => $electric_info['city_id'], 'house_type' => $house_type['house_type']]);
        } elseif(!empty($electric_info['electric_price_id'])) {
            $service_price = new HouseMeterElectricPrice();
            $electric_price = $service_price->getInfo(['id' => $electric_info['electric_price_id']]);
        }else{
            $electric_price['unit_price']=$electric_info['unit_price'];
            $electric_price['rate']=$electric_info['rate'];
        }

        if ($charge_price>0&&$electric_price['unit_price']>0&&$electric_price['rate']>0){
            $unit_price=$electric_price['unit_price']*$electric_price['rate'];
            $charge_num=$charge_price/$unit_price;
        }else{
            $charge_num=0;
        }
        $service_sys = new HouseMeterReadingSys();
        $sys_info = $service_sys->getInfo(['id' => 1]);
        $order_data = [
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
            'electric_id' => $electric_info['id'],
            'uid' => $uid,
            'phone' => $user_info['phone'],
            'meter_reading_type' => $sys_info['meter_reading_type'],
            'unit_price' => $electric_price['unit_price'],
            'rate' => $electric_price['rate'],
            'payment_num' => 0,
            'payment_type' => 0,
            'status' => 1,
            'update_time' => time(),
            'add_time' => time(),
            'charge_price' => $charge_price,
            'charge_num' => $charge_num,
            'order_no' => rand(1000, 9999) . time(),

        ];
        $service_order = new HouseMeterUserPayorder();
        $order_id = $service_order->insertOne($order_data);
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
        $service_electric = new HouseMeterUserPayorder();
        $order_info = $service_electric->getOne($order_id);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }
        //  'order_id' => $order_info['order_id'],
        $data = [
            'order_money' => $order_info['charge_price'],
            'order_no' => $order_info['order_no'],
            'uid' => $order_info['uid'],
            'title' => '智能电表预交费',
            'time_remaining' => 900,
            'paid' => 0,
            'is_cancel' => 0,
            'city_id'=>0,
            'store_id'=>0,
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
        $service_electric = new HouseMeterUserPayorder();
        $order_info = $service_electric->getOne($order_id);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }
        $url= get_base_url('pages/houseMeter/index/successful?electric_id='.$order_info['electric_id']);
        $res['redirect_url']=$url;
        $res['direct']=1;

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
    public function afterPay($order_id,$extra){
        $service_order = new HouseMeterUserPayorder();
        $service_paid_order = new PayOrderInfo();
        $order_info = $service_order->getOne($order_id);
        if (!$order_info) {
            throw new \think\Exception("订单信息不存在！");
        }

       //  fdump_api(['支付回调', $order_id ,$extra],'afterpay/meter',1);
        if ($extra['paid'] != 1) {
            $order_id=$service_order->deleteInfo(['id' => $order_id]);
        }else{
            if($extra['current_system_balance']>=($extra['current_system_balance']+$extra['paid_money'])){
                $pay_type='balance';
            }else{
                $pay_type=$extra['paid_type'];
            }
            //todo 环球支付
            if(isset($extra['is_hqpay']) && intval($extra['is_hqpay']) == 1){
                $pay_type=$extra['hqpay_source'];
            }
            $order_paid_no=$service_paid_order->getByOrderNo($extra['paid_orderid']);
            $paid_orderNo='';
            if (!empty($order_paid_no)){
                $paid_orderNo=$order_paid_no['paid_extra'];
            }
            $order_data = [
                'pay_time' => time(),
                'pay_type' => $pay_type,
                'update_time' => time(),
                'paid_orderNo'=>$paid_orderNo,
                'current_system_balance'=>$extra['current_system_balance'],
                'status' => 2,
            ];

            $rets = $service_order->saveOne(['id' => $order_id], $order_data);
            $housePaidOrderRecordService=new HousePaidOrderRecordService();
            $housePaidOrderRecordService->addHouseMeterUserPayorderRecord($order_info,$extra);
            $service_order = new HouseMeterAdminElectric();
            $electric_info = $service_order->getOne($order_info['electric_id']);
            if (!$electric_info) {
                throw new \think\Exception("电表信息不存在！");
            }
            if (!empty($electric_info)){
                $remaining_capacity=$electric_info['remaining_capacity']+$order_info['charge_num'];
                $service_order->saveOne(['id' => $order_info['electric_id']], ['remaining_capacity'=>$remaining_capacity]);
            }
            //获取购买用户信息
            $userService = new UserService();
            $buyer = $userService->getUser($order_info['uid'], 'uid');
            if (empty($buyer)) {
                throw new Exception(L_("购买用户不存在"), 1003);
            }
            //判断帐户余额,扣除余额
            $balancePay = floatval($extra['current_system_balance']);
            if ($balancePay && $buyer['now_money'] < $balancePay) {
                throw new Exception(L_("您的帐户余额不够此次支付"), 1003);
            }
            if ($balancePay > 0) {
                $useResult = (new UserService())->userMoney($order_info['uid'], $balancePay, L_("电表充值，扣除余额，订单编号X1", array("X1" => $order_info['order_no'])));
                if ($useResult['error_code']) {
                    throw new Exception($useResult['msg'], 1003);
                }
            }

            //积分抵扣，扣除积分
            $scoreUsedCount = $order_info['score_used_count'];
            if ($scoreUsedCount && $buyer['score_count'] < $scoreUsedCount) {
                throw new Exception(L_("您的积分余额不够此次支付"), 1003);
            }
            if ($scoreUsedCount > 0) {
                $use_result = (new UserService())->userScore($order_info['uid'], $scoreUsedCount, L_("电表充值 ，扣除X1 ，订单编号X2", array( "X1" => cfg('score_name'), "X2" => $order_info['order_no'])));
                if ($use_result['error_code']) {
                    throw new Exception($use_result['msg'], 1003);
                }
            }

        }
        $user =new User();
        $openid=$user->getOne(['uid'=>$order_info['uid']]);
        if (!empty($openid)) {
            $href = get_base_url('pages/houseMeter/index/payRecord?electric_id=' . $order_info['electric_id']);
            $templateNewsService = new TemplateNewsService();
            $data = [
                'tempKey' => 'TM01008',
                'dataArr' => [
                    'href' => $href,
                    'wecha_id' => $openid['openid'],
                    'first' => '缴费成功提醒',
                    'keyword1' => '电费',
                    'keyword2' => $electric_info['village_address'],
                    'remark' => '缴费时间:'.date('Y-m-d H:i').'\n'.'缴费金额:￥'.$order_info['charge_price'],

                ]
            ];
            //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
            $templateNewsService->sendTempMsg($data['tempKey'], $data['dataArr']);
        }
        return $order_id;

    }
}
