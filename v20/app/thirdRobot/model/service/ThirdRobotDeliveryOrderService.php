<?php

declare(strict_types=1);

namespace app\thirdRobot\model\service;

use app\common\model\db\SystemOrder;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\User;
use app\pay\model\service\PayService;
use app\shop\model\service\order\ShopOrderService;
use app\thirdRobot\model\db\ThirdRobotCallRecord;
use app\thirdRobot\model\db\ThirdRobotDeliveryOrder;
use app\thirdRobot\model\db\ThirdRobotDeliveryOrderLog;
use app\thirdRobot\model\db\ThirdRobotDevice;
use app\thirdRobot\model\db\ThirdRobotHistoryTaskDetail;
use think\facade\Db;

class ThirdRobotDeliveryOrderService
{
    const ROBOT_ARRIVED_START_POINT = 1;//机器人到达召唤地
    const ROBOT_START_DELIVERY = 2;//机器人开始配送
    const ROBOT_ARRIVED_END_POINT = 3;//机器人到达收货地
    
    public static $pageSize = 15;

    public function __construct()
    {
        if (!empty(input('page_size'))) {
            self::$pageSize = min(input('page_size'), 100);
        }
    }

    public function createOrder(array $params)
    {
        try {
            $order = Db::transaction(function () use ($params) {
                $order = $this->saveOrder($params);
                if ($order['type'] == 'call') {
                    $saveData = [
                        'total_price'    => $order['call_price'],
                        'goods_price'    => $order['call_price'],
                        'discount_price' => $order['call_price'],
                        'price'          => $order['call_price'],
                        'status'         => ThirdRobotDeliveryOrder::ORDER_STATUS_10,
                        'num'            => 1,
                    ];
                } else {
                    $saveData = [
                        'total_price'    => $order['delivery_price'],
                        'goods_price'    => $order['delivery_price'],
                        'discount_price' => $order['delivery_price'],
                        'price'          => $order['delivery_price'],
                        'status'         => ThirdRobotDeliveryOrder::ORDER_STATUS_10,
                        'num'            => 1,
                    ];
                }
                if (empty($params['role']) || $params['role'] == 'user') {
                    app(SystemOrderService::class)->saveOrder('robot', $order['id'], $order['uid'], $saveData);
                }
                return $order;
            });
        } catch (\Exception | \Throwable $e) {
            fdump_api([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'robot/createOrder', true);

            throw_exception($e->getMessage());
        }
        
        return $order;
    }
    
    public function saveOrder(array $params)
    {
        if (!empty($params['order_id'])) {
            $order = ThirdRobotDeliveryOrder::where('id', $params['order_id'])->find();
            if (empty($order)) {
                throw_exception('当前订单不存在！');
            }
            if(!empty($params['call_record_id'])){//召唤记录id
                $params['call_last_record_id'] = $params['call_record_id'];
            }
        } else {
            $order = app(ThirdRobotDeliveryOrder::class);
            $params['order_no'] = app(PayService::class)->createOrderNo();
        }
        $order->setAttrs($params);
        $order->save();
        return $order;
    }

    /**
     * 机器人状态变化通知
     * @param $uid
     * @param $note
     * @return false
     */
    public function wechatTemplateMsg($uid, $note)
    {
        $user = app(User::class)->getOne(['uid' => $uid['uid']]);
        if (empty($user) || empty($user['openid'])) {
            return false;
        }
        $dataMsg = [
            'tempKey' => 'OPENTM400166399',
            'dataArr' => [
                'wecha_id' => $user['openid'],
                'first' => '您好，您召唤的机器人状态改变，请及时查看！',
                'keyword1' => '机器人状态提醒',
                'keyword2' => $note,
                'keyword3' => date('H:i'),
            ]
        ];
        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
        return app(TemplateNewsService::class)->sendTempMsg($dataMsg['tempKey'], $dataMsg['dataArr']);
    }
    
    public function setRobotDeliveryPrice(array $params)
    {
        $robot = ThirdRobotDevice::where('id', $params['robot_id'])->find();
        if (empty($robot)) {
            throw_exception('当前机器人不存在！');
        }
        $robot->setAttrs($params);
        $robot->save();
        return $robot->id;
    }

    public function getRobotDeliveryPrice($robotCode)
    {
        $robot = ThirdRobotDevice::where('robot_code', $robotCode)->field('delivery_price')->find();
        if (empty($robot)) {
            throw_exception('当前机器人不存在！');
        }
        return $robot->delivery_price;
    }

    public function orderList(array $params)
    {
        $data = ThirdRobotDeliveryOrder::withSearch([
                    'status', 'start_time', 'end_time', 'keywords'
                ], $params)
                ->append(['status_text'])
                ->paginate(self::$pageSize);
        
        return $data;
    }

    public function orderDetail(array $params)
    {
        $data = ThirdRobotDeliveryOrder::where(['id' => $params['order_id']])
            ->withSearch(['uid'], $params)
            ->with(['robot' => function($query){
                $query->field('id, name, robot_type, robot_code, online_flag, lng, lat');
            }])
            ->append(['status_text','robot.robot_img'])
            ->find();
        
        $data->robot->lng = $data->robot_end_lng;
        $data->robot->lat = $data->robot_end_lat;
        $expireTime = strtotime($data['create_time']) + 10*60;//10分钟未支付取消订单
            
        if(empty($data['is_paid']) && time() > $expireTime){
            $this->changeOrderStatus($data['id'], ThirdRobotDeliveryOrder::ORDER_STATUS_12);
        }
        
        return $data;
    }

    public function cancelOrder(array $params)
    {
        $orderPaid = ThirdRobotDeliveryOrder::where('id', $params['order_id'])->value('is_paid');
        if($orderPaid){
            $params['status'] = ThirdRobotDeliveryOrder::ORDER_STATUS_70;
        }else{
            $params['status'] = ThirdRobotDeliveryOrder::ORDER_STATUS_12;
        }
        return $this->changeOrderStatus($params['order_id'],  $params['status'], $params);
    }
    
    public function changeOrderStatus($orderId, int $status, array $params = [])
    {
        try{
            $data = Db::transaction(function ()use($orderId, $status, $params){
                $data = [
                    'order_id' => $orderId,
                    'status' => $status,
                ];
                $orderInfo = ThirdRobotDeliveryOrder::where('id', $orderId)->find();
                $systemStatus = 0;//系统状态，1-已发货，2-已完成，3-已评价，4-已退款，5-已取消,6-已完成（不用评价）,7-退款申请中
                switch ($status) {
                    case ThirdRobotDeliveryOrder::ORDER_STATUS_12:
                        $note = '订单已取消';
                        $systemStatus = 5;
                        break;
                    case ThirdRobotDeliveryOrder::ORDER_STATUS_20:
                        $note = '订单已支付';
                        break;
                    case ThirdRobotDeliveryOrder::ORDER_STATUS_30:
                        $note = '机器人正前往召唤地';
                        if(!empty($orderInfo['shop_order_id'])){
                            app(ShopOrderService::class)->robotGrabOrder($orderId);
                            $this->callRobot($orderId);
                        }
                        break;
                    case ThirdRobotDeliveryOrder::ORDER_STATUS_40:
                        $note = '机器人到达召唤地，准备送货';
                        $systemStatus = 1;
                        if(!empty($orderInfo['shop_order_id'])){
                            app(ShopOrderService::class)->robotReportArriveStore($orderId);
                        }
                        break;
                    case ThirdRobotDeliveryOrder::ORDER_STATUS_50:
                        $note = '机器人正前往目的地';
                        if(!empty($orderInfo['shop_order_id'])){
                            app(ShopOrderService::class)->robotPickOrder($orderId);
                        }
                        break;
                    case ThirdRobotDeliveryOrder::ORDER_STATUS_60:
                        $note = '订单已完成';
                        $systemStatus = 6;
                        if(!empty($orderInfo['shop_order_id'])){
                            (new ShopOrderService())->confirmConsume($orderInfo['shop_order_id'], $orderInfo['uid']);
                        }
                        $data['delivery_time'] = date('Y-m-d H:i:s');
                        break;
                    case ThirdRobotDeliveryOrder::ORDER_STATUS_70:
                        $note = '订单已取消并退款';
                        $data['refund_remark'] = $params['refund_remark'] ?? '';
                        $systemStatus = 4;
                        $this->refundOrderMoney($orderId);
                        if($orderInfo['task_instance_id']){
                            $info = (new ThirdRobotRecognitionService())->cancelTask([
                                'taskInstanceId' => $orderInfo['task_instance_id']
                            ]);
                            $cancelData = [
                                'order_id' => $orderId,
                                'cancel_task_result' => $info,
                            ];
                            fdump_api($cancelData, 'robot/cancelTask', true);
                            fdump_sql($cancelData, 'robotCancelTask');
                        }
                        break;
                    default:
                        $note = '';
                        break;
                }
                if($status > ThirdRobotDeliveryOrder::ORDER_STATUS_30 && $status <= ThirdRobotDeliveryOrder::ORDER_STATUS_60){
                    app(ThirdRobotDeliveryOrderService::class)->orderRobotEndLocation($orderInfo['task_instance_id']);
                }
                $this->saveOrder($data);
                ThirdRobotDeliveryOrderLog::create([
                    'order_id' => $orderId,
                    'status' => $status,
                    'note' => $note,
                    'from_type' => $params['from_type'] ?? 0,
                    'operate_uid' => $params['operate_uid'] ?? 0,
                ]);
                $systemStatus && SystemOrder::where([
                    'type' => 'robot', 'order_id' => $orderId
                ])
                ->update(['system_status' => $systemStatus]);
                return true;
            });
        }catch (\Exception | \Throwable $e){
            fdump_api([
                'order_id' => $orderId,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'robot/changeOrderStatus', true);
            
            throw_exception($e->getMessage());
        }
        
        return $data;
    }
    
    public function callRobot($orderId)
    {
        $orderInfo = ThirdRobotDeliveryOrder::where('id', $orderId)->find();
        /**@var ThirdRobotDeviceCmdService $thirdRobotDeviceCmdService */
        $thirdRobotDeviceCmdService = app(ThirdRobotDeviceCmdService::class);
       
        $params = [
            'uid'              => $orderInfo->uid,
            'robot_id'         => $orderInfo->robot_id,
            'robot_code'       => $orderInfo->robot_code,
            'start_point_id'   => $orderInfo->start_point_id,
            'end_point_id'     => $orderInfo->end_point_id,
            'start_user_phone' => $orderInfo->start_address_phone,
            'end_user_phone'   => $orderInfo->end_address_phone,
            'call_from'        => 'robot_delivery',
            'call_from_id'     => $orderInfo->id,
            'order_id'         => $orderId 
        ];
        $result = $thirdRobotDeviceCmdService->multipointCall($params);
        $taskInstanceId = $result['task_instance_id'] ?? '';
        app(ThirdRobotDeliveryOrderService::class)->orderRobotEndLocation($taskInstanceId);
        return $this->saveOrder([
            'call_record_id'   => $result['record_id'],
            'task_instance_id' => $taskInstanceId,
            'order_id'         => $orderInfo->id,
        ]);
    }

    /**
     * 配送订单机器人最后位置获取
     * @param $TaskInstanceId
     * @return array|false|object|\think\App|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderRobotEndLocation($TaskInstanceId, $params = [])
    {
        try{
            //status '节点状态 -1： 未执行 0：发起失败 1：抵达中 2:异常挂起 3：异常结束 4：成功完成 5：取消结束 6：暂停中 7： 已抵达'
            $order = ThirdRobotDeliveryOrder::where('task_instance_id', $TaskInstanceId)->find();
            if(empty($order)){
                return false;
            }
            $data = app(ThirdRobotDeviceService::class)->getThirdRobotInfo($order['robot_code']);
            fdump_api([
                'task_instance_id' => $TaskInstanceId,
                'robot_info' => $data,
                'params' => $params
            ], 'robot/orderRobotEndLocation',1);
            
            if(empty($data['data']['loc'])){
                return false;
            }
            $location = json_decode($data['data']['loc'], true);
            //记录订单机器人最终位置
            $this->saveOrder([
                'robot_end_lng' => $location['lng'],
                'robot_end_lat' => $location['lat'],
                'order_id'      => $order->id
            ]);
            //公众号消息通知
            if(
                $order['role'] == 'user' &&
                !empty($params['status']) &&
                $params['status'] == ThirdRobotHistoryTaskDetail::STATUS_7
            ){//已抵达
                $taskDetail = ThirdRobotHistoryTaskDetail::where('task_instance_id', $TaskInstanceId)->find();
                
                $note = '已到达'.$taskDetail['target_point_flag'] ? '收货地' : '召唤地';
                $this->wechatTemplateMsg($order['uid'], $note);
            }
        }catch (\Exception | \Throwable $e){
            fdump_api([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'robot/orderRobotEndLocationError', true);
        }
        return true;
    }
    //region 对接支付

    /**
     * 获取订单信息
     * @param $orderId
     */
    public function getOrderPayInfo($orderId) 
    {
        $orderInfo = ThirdRobotDeliveryOrder::where('id', $orderId)->find();
        empty($orderInfo) && throw_exception('没有找到订单');

        $expireTime = strtotime($orderInfo['create_time']) + 60*10000;
        $orderInfo['timeout']      = $expireTime > time() ? 0 : 1; //1=已过期,0=未过期
        $orderInfo['time_surplus'] = $orderInfo['timeout'] == 0 ? $expireTime - time() : 0; //有效时间
        if ($orderInfo['is_paid'] != ThirdRobotDeliveryOrder::IS_PAID_1 && $orderInfo['timeout'] == 1) { //超时未支付
            $this->changeOrderStatus($orderId, ThirdRobotDeliveryOrder::ORDER_STATUS_12, ['note' => '超时未支付（支付中心）']);
        }
        if (isset($orderInfo['type']) && $orderInfo['type'] == 'call') {
            $order_money = $orderInfo['call_price'];
            $title = '机器人配送订单';
        } else {
            $order_money = $orderInfo['delivery_price'];
            $title = '机器人召唤订单';
        }
        return [
            'paid'                 => $orderInfo['is_paid'],
            'is_cancel'            => $orderInfo['timeout'],
            'uid'                  => $orderInfo['uid'],
            'order_no'             => $orderInfo['order_no'],
            'title'                => $title,
            'order_money'          => $order_money,
            'mer_id'               => 0,
            'city_id'              => 0,
            'store_id'             => 0,
            'time_remaining'       => $orderInfo['time_surplus'],
            'use_platform_balance' => true,
            'business_order_sn'    => $orderInfo['order_no'],
        ];
    }

    /**
     * 获取支付结果页地址
     * @param $orderId
     * @param int $is_cancel  1=已取消  0=未取消
     */
    public function getPayResultUrl($orderId, $cancel = 0)
    {
        $type = ThirdRobotDeliveryOrder::where(['id' => $orderId])->value('type');
        if($type == 'delivery'){
            $redirect_url = get_base_url('pages/robot/call_robot_release_order?call_from=platCallRobot&order_id=' . $orderId . '&type=robot');
        }else{
            $redirect_url = get_base_url('pages/robot/call_robot_release_order_2?call_from=villageCallRobot&order_id=' . $orderId . '&type=robot');
        }
    
        return ['redirect_url' => $redirect_url, 'direct' => 0, 'redirect_home_url'=>get_base_url()]; //前端提供跳转地址
    }
    
    /**
     * 支付成功逻辑
     * $orderId  订单表的主键ID
     */
    public function afterPay($orderId, $payInfo = []) 
    {
        fdump_api([$orderId => $payInfo],'robot_order/afterPay',true);
        $orderInfo = ThirdRobotDeliveryOrder::where('id', $orderId)->find();
        
        empty($orderInfo) && throw_exception('没有找到订单');
        
        $UserService = new UserService();
        $nowUser = $UserService->getUser($orderInfo['uid']);
        try{
            Db::transaction(function ()use($nowUser, $payInfo, $orderInfo){
                //扣除使用平台余额
                if ($payInfo['current_system_balance'] > 0) {
                    if ($nowUser['now_money'] < $payInfo['current_system_balance']) {
                        throw_exception("用户余额不足！");
                    }
                    if (isset($orderInfo['type']) && $orderInfo['type'] == 'call') {
                        $desc = "机器人配送召唤 ，扣除余额 ，订单编号" . $orderInfo['order_no'];
                    } else {
                        $desc = "召唤机器人 ，扣除余额 ，订单编号" . $orderInfo['order_no'];
                    }
                    $UserService = new UserService();
                    $useResult = $UserService->userMoney($nowUser['uid'], $payInfo['current_system_balance'], $desc);
                    if ($useResult['error_code']) {
                        throw_exception($useResult['msg']);
                    }
                    $orderInfo->system_balance_pay = $payInfo['current_system_balance'];
                }
                //在线支付金额
               
                $orderInfo->out_trade_no = $payInfo['paid_orderid'];
                $orderInfo->online_pay_money = $payInfo['paid_money'];
                $orderInfo->pay_time = date('Y-m-d H:i:s');
                $orderInfo->is_paid = ThirdRobotDeliveryOrder::IS_PAID_1;
                $orderInfo->save();
                $this->changeOrderStatus($orderInfo['id'], ThirdRobotDeliveryOrder::ORDER_STATUS_20, ['note' => '支付成功！']);
            });
            $this->callRobot($orderInfo['id']);
        }catch (\Exception | \Throwable $e){
            fdump_api([$e->getMessage(),$e->getTraceAsString()], 'robot/after_pay',true);
            throw_exception($e->getMessage());
        }        
        return true;
    }


    /**
     * 退款
     * @param $orderId
     * @param int $refundMoney
     * @return bool
     * @throws \think\Exception
     */
    public function refundOrderMoney($orderId) {
        $order= ThirdRobotDeliveryOrder::where('id', $orderId)
            ->where('status', '<>', ThirdRobotDeliveryOrder::ORDER_STATUS_70)
            ->where('refund_time', NULL)
            ->field('order_no, uid, online_pay_money, out_trade_no, is_paid, delivery_price, system_balance_pay, type, call_price')
            ->find();
        if(empty($order) || $order['is_paid'] != ThirdRobotDeliveryOrder::IS_PAID_1) { //未支付
            return false;
        }

        try {
            Db::transaction(function ()use($order) {
                //平台余额
                $refundSystemBalance = $order['system_balance_pay'];
                if ($refundSystemBalance > 0) {
                    $result = (new UserService())->addMoney($order['uid'], $refundSystemBalance, '召唤机器人订单退款,增加余额,订单编号' . $order['order_no']);
                    if ($result['error_code']) {
                        throw_exception($result['msg']);
                    }
                }

                //在线支付退款
                $refundOnlinePay = $order['online_pay_money'];
                if ($refundOnlinePay > 0) {
                    $PayService = new PayService();

                    if ($order['out_trade_no']) {
                        $end_order = $PayService->getPayOrderInfo($order['out_trade_no']);
                        if ($end_order['paid'] == '1' && $end_order['paid_money'] > 0) {
                            $end_money = ($end_order['paid_money'] - $end_order['refund_money']) / 100;
                            if ($end_money > 0) {
                                $PayService->refund($order['out_trade_no'], $end_money);
                            }
                        }
                    }
                }
                $order->refund_time = date('Y-m-d H:i:s');
                $order->save();
            });
        } catch(\Exception $e) {
            fdump_api([
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'robot/refund',true);
            
            throw_exception($e->getMessage());
        }
        return true;
    }

    //endregion

}