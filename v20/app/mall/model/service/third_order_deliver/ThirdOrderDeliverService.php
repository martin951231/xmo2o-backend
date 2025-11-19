<?php


namespace app\mall\model\service\third_order_deliver;


use app\common\model\db\ServiceOffer;
use app\common\model\db\ServiceUserPublishLog;
use app\common\model\service\user\UserNoticeService;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallRiderRecord;
use app\mall\model\db\ThirdParty;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\MallRiderService;
use app\paotui\model\db\ServiceUserPublish;
use map\longLat;
use think\facade\Db;

class ThirdOrderDeliverService
{
    /**
     * 更新订单状态接口
     */
    public function changeStatus($params)
    {
        fdump_api('开始:' . json_encode($params), 'mall_third_deliver/changeStatus', 1);
        $this->checkSign($params);
        $orderId = $params['orderId'];
        $status = $params['status'];
        $operatingTime = $params['operatingTime'];
        $operatorPhone = $params['operatorPhone'];
        $operator = $params['operator'];
        $businessType = $params['businessType'];
        if (!$orderId || !in_array($status, [11, 20, 22, 23, 24, 33]) || !$operatingTime || !$operator) {
            throw new \think\Exception(L_('参数错误！'));
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $operatorPhone)) {
            throw new \think\Exception(L_('手机号格式错误！'));
        }
        if ($businessType == 'service') {
            $this->serviceChangeStatus($params);
        } else {
            $this->mallChangeStatus($params);
        }
        fdump_api('结束：' . $orderId, 'mall_third_deliver/changeStatus', 1);
        return ['orderId' => $orderId];
    }

    /**
     * 商城订单状态修改
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function mallChangeStatus($params)
    {
        $orderId = $params['orderId'];
        $status = $params['status'];
        $operatingTime = $params['operatingTime'];
        $operator = $params['operator'];
        $operatorPhone = $params['operatorPhone'];
        $lng = $params['lng'];
        $lat = $params['lat'];
        $extends = $params['extends'];
        $order = (new MallOrder())->where(['order_no' => $orderId])->findOrEmpty()->toArray();
        if (!$order) {
            throw new \think\Exception(L_('订单不存在！'));
        }
        if ($order['pay_time'] <= 0) {
            fdump_api($order, 'mall_third_deliver/changeStatusOrder', 1);
            throw new \think\Exception(L_('订单未支付无法操作！'));
        }
        if (!$order['is_third_deliver']) {
            throw new \think\Exception(L_('当前订单无法操作！'));
        }
        if (($order['status'] >= 50 && $order['status'] < 60) || $order['status'] == 70) {
            throw new \think\Exception(L_('订单已取消无法操作！'));
        }
        $deliverUser = [
            'id' => 0,
            'name' => $operator,
            'phone' => $operatorPhone,
            'note' => $extends ?: '',
        ];
        Db::startTrans();
        try {
            switch ($status) {
                case 11:
                    if ($order['status'] >= 11) {
                        throw new \think\Exception(L_('当前状态无法修改！'));
                    }
                    //把状态置成11  记录日志
                    $note = '第三方平台接单，当前该笔订单正在备货中';
                    (new MallOrderService())->changeOrderStatus($order['order_id'], 11, $note);
                    //骑手纪录店员接单
                    (new MallRiderService())->addRecord('order', $order['order_id'], 0, '第三方平台接单', $lng, $lat, $deliverUser);
                    break;
                case 20:
                    if ($order['status'] >= 20 || $order['status'] < 11) {
                        throw new \think\Exception(L_('当前状态无法修改！'));
                    }
                    (new MallOrder())->where(['order_id' => $order['order_id']])->save(['send_time' => time()]);
                    $note = '第三方平台已发货，发货方式：骑手配送';
                    (new MallOrderService())->changeOrderStatus($order['order_id'], 20, $note);
                    //骑手纪录店员接单
                    (new MallRiderService())->addRecord('order', $order['order_id'], 0, '第三方平台备货完成', $lng, $lat, $deliverUser);
                    break;
                case 22:
                case 23:
                case 24:
                case 33:
                    if ($status == 22) {
                        //查询是否有接单记录，取货前可以更新更换配送员
                        $rider_log = (new MallRiderRecord())
                            ->where(['type' => 'order', 'send_id' => $order['order_id'], 'status' => 1, 'rider_id' => 0])
                            ->order('id desc')
                            ->findOrEmpty()->toArray();
                        if (($rider_log && $order['status'] == 22 && $rider_log['rider_phone'] != $deliverUser['phone']) || (!$rider_log && $order['status'] == 20)) {
                            if ($rider_log && $order['status'] == 22 && $rider_log['rider_phone'] != $deliverUser['phone']) {
                                $deliverUser['msg'] = '骑手已更换，正在赶往商家';
                            }
                        } else {
                            throw new \think\Exception(L_('当前状态无法修改！'));
                        }
                    }
                    if ($status == 23 && $order['status'] != 22) {
                        throw new \think\Exception(L_('当前状态无法修改！'));
                    }
                    if ($status == 24 && $order['status'] != 23) {
                        throw new \think\Exception(L_('当前状态无法修改！'));
                    }
                    if ($status == 33 && $order['status'] != 24) {
                        throw new \think\Exception(L_('当前状态无法修改！'));
                    }
                    if ($lng && $lat) {
                        $longlat = new longLat();
                        //WGS-84转百度地图坐标
                        $baiduLngLat = $longlat->toBaidu($lat, $lng, 1);
                        if ($baiduLngLat) {
                            $lng = $baiduLngLat['lng'];
                            $lat = $baiduLngLat['lat'];
                        }
                    }
                    $status_arr = [22 => 1, 23 => 2, 24 => 3, 33 => 4];
                    if ($status != 33) {
                        $update = [
                            'status' => $status,
                            'last_uptime' => time()
                        ];
                        (new MallOrder())->where(['order_id' => $order['order_id']])->save($update);
                    }
                    (new MallOrderService())->housemanOrderLog('order', $status_arr[$status], $order['order_id'], $lng, $lat, $deliverUser);
                    //增加消息通知
                    if ($status == 33) {
                        (new UserNoticeService())->addNotice([
                            'type' => 0,
                            'business' => 'mall',
                            'order_id' => $order['order_id'],
                            'title' => '商品已经送达',
                            'content' => '您的商城商品已经送达，点击查看订单详情',
                        ]);
                    }
                    break;
                default :
                    throw new \think\Exception(L_('状态有误！'));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            fdump_api([$e->getMessage(), $e->getLine(), $e->getFile()], 'mall_third_deliver/changeStatus', 1);
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 跑腿（服务快派）订单状态修改
     */
    public function serviceChangeStatus($params)
    {
        $orderId = $params['orderId'];
        $status = $params['status'];
        $operatingTime = $params['operatingTime'];
        $operator = $params['operator'];
        $operatorPhone = $params['operatorPhone'];
        $lng = $params['lng'];
        $lat = $params['lat'];
        $extends = $params['extends'];
        if (!in_array($status, [22, 24, 33])) {
            throw new \think\Exception(L_('状态参数错误！'));
        }
        $where = [
            'order_sn' => $orderId,
        ];
        $publish_order = (new ServiceUserPublish())->where($where)->findOrEmpty()->toArray();
        if (!$publish_order) {
            throw new \think\Exception(L_('订单不存在！'));
        }
        if ($publish_order['status'] < 1) {
            fdump_api($publish_order, 'mall_third_deliver/changeStatusOrder', 1);
            throw new \think\Exception(L_('订单未支付无法操作！'));
        }
        if (!$publish_order['is_third_deliver']) {
            throw new \think\Exception(L_('当前订单无法操作！'));
        }
        if ($publish_order['status'] == 6 || $publish_order['status'] >= 10) {
            throw new \think\Exception(L_('订单已取消无法操作！'));
        }
        $deliver_user = ['uid' => 0, 'name' => $operator, 'phone' => $operatorPhone, 'note' => $extends, 'lng' => $lng, 'lat' => $lat];
        if ($status == 22) {
            //查询接单记录,配送之前可以更换配送员
            $log = (new ServiceUserPublishLog())->where(['publish_id' => $publish_order['publish_id'], 'catgory_type' => 3, 'status' => 2])->findOrEmpty()->toArray();
            if (($log && $publish_order['status'] == 8 && $log['phone'] != $operatorPhone) || (!$log && $publish_order['status'] == 2)) {
                if ($log && $publish_order['status'] == 8 && $log['phone'] != $operatorPhone) {
                    $deliver_user['msg'] = '更换配送员，待取货';
                }
            } else {
                throw new \think\Exception(L_('当前状态无法修改！'));
            }
        }
        if ($status == 24 && $publish_order['status'] != 8) {
            throw new \think\Exception(L_('当前状态无法修改！'));
        }
        if ($status == 33 && $publish_order['status'] != 9) {
            throw new \think\Exception(L_('当前状态无法修改！'));
        }
        switch ($status) {
            case 22: //接单
                if(!empty($deliver_user['msg'])){
                    $offer_info = (new ServiceOffer())->where(['deliver_type'=>1,'publish_id'=>$publish_order['publish_id']])->findOrEmpty()->toArray();
                }
                fdump_api([$publish_order['publish_id'], $deliver_user, $offer_info], 'mall_third_deliver/changeStatusOrder', 1);
                if($offer_info){
                    $res = invoke_cms_model('Service_offer/offer_save_status', [$publish_order['publish_id'], 0, $offer_info['offer_id'], 8, 0, $deliver_user], true);
                }else{
                    $res = invoke_cms_model('Service_offer/add_offer', [$publish_order['publish_id'], 0, 0, $deliver_user], true);
                }
                break;
            case 24: //取餐-配送中
            case 33: //送达
                $status_service = [24 => 9, 33 => 4];
                $res = invoke_cms_model('Service_offer/offer_save_status', [$publish_order['publish_id'], 0, 0, $status_service[$status], 0, $deliver_user], true);
                break;
        }
        fdump_api([$publish_order['publish_id'], $deliver_user, $res], 'mall_third_deliver/changeStatusOrder', 1);
        if ($res['error_no'] > 0) {
            throw new \think\Exception(L_($res['error_msg']));
        }
        if (!$res['retval']) {
            throw new \think\Exception(L_('信息修改失败！'));
        }
        return true;
    }

    /**
     * 验证平台信息是否正确
     * @param $params
     * @throws \think\Exception
     */
    private function checkSign($params)
    {
        $key = $params['key'] ?? '';
        $thirdPlatform = $params['thirdPlatform'] ?? '';
        if (!$key || !$thirdPlatform) {
            throw new \think\Exception(L_('校验信息错误！'));
        }
        $third_party = (new ThirdParty())->where([
            ['key', '=', $key],
            ['alias', '=', $thirdPlatform],
            ['status', '=', 1],
        ])->findOrEmpty()->toArray();
        if (!$third_party) {
            throw new \think\Exception(L_('鉴权失败！'));
        }
    }
}