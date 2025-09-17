<?php

declare(strict_types=1);

namespace app\villageGroup\model\service;

use app\common\model\db\Config;
use app\common\model\db\LiveShow;
use app\common\model\db\User;
use app\common\model\service\UserService as UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\villageGroup\model\db\VillageGroupHeader;
use app\villageGroup\model\db\VillageGroupOrder;
use app\villageGroup\model\db\VillageGroupOrderDetail;
use app\villageGroup\model\db\VillageGroupPickAddress;

class VillageGroupOrderService
{
    public function groupOrderShareList(array $params)
    {
        $list = VillageGroupOrder::where('share_uid','>',0)
            ->withSearch(['user_name', 'share_user_name', 'goods_name','status', 'start_time', 'end_time'], $params)
            ->where('status','>',0)
            ->where('is_del',0);
        $valueSum = clone $list;
        $goodsNumSum = clone $list;
        
        $list = $list
            ->with(['user' => function($query){
                $query->withField(['uid', 'nickname']);
            },'share_user' => function($query){
                $query->withField(['uid', 'nickname']);
            }])->field(['order_id', 'uid', 'share_uid','status', 'discount_price', 'share_user_commission', 'add_time'])
            ->order('order_id DESC')
            ->append(['goods_count','goods_name'])
            ->paginate(min($params['page_size'] ?? 10, 100))
            ->each(function ($item){
                $item['user_name'] = $item->user->nickname;
                $item['share_user_name'] = $item->share_user->nickname;
                $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
                unset($item['uid'],$item['share_uid'], $item['user'], $item['share_user']);
            })
            ->toArray();

        $list['value_sum'] = $valueSum
        ->field('sum(discount_price) discount_price_sum, sum(share_user_commission) share_user_commission_sum')
        ->find();

        $goodsNumSumSql = $goodsNumSum->field('order_id')->buildSql();

        $list['value_sum']['goods_num_sum'] = VillageGroupOrderDetail::alias('v')
            ->join([$goodsNumSumSql => 's'],'v.order_id = s.order_id')
            ->sum('v.num');
        
        return $list;
    }

    /**
     * 社区团购订单获取配送信息
     *
     * @param int $orderId
     * @return void
     * @author: zt
     * @date: 2023/02/02
     */
    public function getInfoForDeliverList($orderId)
    {
        $order = VillageGroupOrder::where('order_id', $orderId)->find();
        $rs = [];
        if ($order) {
            $realStatus = $order->getData('status');
            $statusTxt = $order->status;
            $order = $order->toArray();
            $order['status'] = $realStatus;
            $order['status_txt'] = $statusTxt;
            $rs['order_id'] = $order['order_id'];
            $rs['real_orderid'] = $order['real_orderid'];
            $rs['fetch_number'] = 0;
            $rs['note'] = $order['note'] ?: '';
            $rs['desc'] = $order['note'] ?: '';
            $rs['expect_use_time'] = $order['expect_send_time'];

            //发货、收货地址
            $rs['user_address'] = [
                'title' => $order['address'],
                'sub_title' => $order['address'],
                'lng' => $order['address_long'],
                'lat' => $order['address_lat'],
                'tag' => L_('送货'),
                'miles' => $order['delivery_distance'] >= 1000 ? round($order['delivery_distance'] / 1000, 2) . 'km' : $order['delivery_distance'] . 'm'
            ];

            $rs['pick_address'] = [
                'title' => $order['pick_addr'],
                'sub_title' => $order['pick_addr'],
                'lng' => $order['pick_long'],
                'lat' => $order['pick_lat'],
                'tag' => L_('取货'),
                'miles' => '2.2km'
            ];

            $phoneLists = $labels = [];

            //手机号
            $buyer = (new User())->getUser('uid,nickname,phone', ['uid' => $order['uid']]);
            $phoneLists[] = [
                "name" => $order['username'],
                "type" => 1,
                "txt" => L_("收货人"),
                "show_phone" => $order['userphone'],
                "phone" => $order['userphone']
            ];
            if ($buyer['phone'] && $buyer['phone'] != $order['userphone']) {
                $phoneLists[] = [
                    "name" => $buyer['nickname'],
                    "type" => 3,
                    "txt" => L_("下单人"),
                    "show_phone" => $buyer['phone'],
                    "phone" => $buyer['phone']
                ];
            }
            $rs['phone_lists'] = $phoneLists;

            //标签
            $labels = [
                [
                    "txt" => L_("社区团购"),
                    "background" => "#A057F5",
                    "font_color" => "#FFFFFF",
                    "with_border" => false
                ]
            ];
            $rs['labels'] = $labels;
            $rs['order'] = $order;
            $rs['username'] = $order['username'];
            $rs['phone'] = $order['userphone'];
        }
        return $rs;
    }

    public function getGoodsByOrderId($orderId, $fields = '*')
    {
        return (new VillageGroupOrderDetail())->where(['order_id' => $orderId])->field($fields)->select()->toArray();
    }

    /**
     * 获取团长信息, 迁移来自 D('Village_group')->get_header_info();
     *
     * @param integer $uid
     * @param integer $headerId
     * @param boolean $isAll
     * @return void
     * @author: zt
     * @date: 2023/02/06
     */
    public function getHeaderInfo($uid = 0, $headerId = 0, $isAll = false)
    {
        $condition = [];
        if ($headerId > 0) {
            $condition[] = ['id', '=', $headerId];
        } else if ($uid > 0) {
            $condition[] = ['uid', '=', $uid];
        }
        if (!$isAll) {
            $condition[] = ['status', '<>', 4];
        }
        if ($condition) {
            $headerMod = new VillageGroupHeader();
            $pickAddressMod = new VillageGroupPickAddress();
            $header =  $headerMod->where($condition)->findOrEmpty()->toArray();
            if ($header) {
                $user = (new \app\common\model\db\User())->getOne(['uid' => $header['uid']], 'uid,avatar');
                if ($user && !$user->avatar) {
                    $header['avatar'] = cfg('site_url') . '/static/qrcode/wxapp/avatar.png';
                } else {
                    $header['avatar'] = replace_file_domain($user->avatar);
                }
                $header['header_uid'] = $header['uid'];
                $header['group_count'] = VillageGroupOrder::where(['start_uid' => $header['id']])->whereIn('status', '1,2,5,6,7,8')->count();


                $pickAddress = $pickAddressMod->where('header_uid', $header['id'])->findOrEmpty()->toArray();
                if ($pickAddress) {
                    $header['pick_address'] = $pickAddress;
                    if ($pickAddress['status'] == 1) {
                        $header['pick_address_status'] = 1;
                    } else {
                        $header['pick_address_status'] = 0;
                    }
                }
            }
            return ['error_code' => 0, 'msg' => $header];
        }
        return ['error_code' => 2, 'msg' => '没有查找到团长信息'];
    }


    /**
     * 记录订单流转日志, 迁移来自 D('Village_group')->add_log($param);
     *
     * @param array $param
     * @return void
     * @author: zt
     * @date: 2023/02/06
     */
    public function addLog($param)
    {
        if (empty($param['order_id'])) {
            return false;
        }

        $order = VillageGroupOrder::where('order_id', $param['order_id'])->find()->toArray();
        if ($order) {
            $logData = [
                'dateline' => time(),
                'order_id' => intval($param['order_id']),
                'goods_id' => intval($param['goods_id']),
                'status' => $param['status'] ?? 0,
                'deliver_status' => $param['deliver_status'] ?? 0,
                'phone' => $param['phone'] ?? '',
                'name' => $param['name'] ?? '',
                'note' => $param['note'] ?? '',
                'from_type' => $param['from_type'] ?? 0,
                'date_num' => $param['date_num'] ?? 1
            ];
            \think\facade\Db::name('village_group_order_log')->insert($logData);

            $nowUser = (new \app\common\model\db\User())->getOne(['uid' => $order['uid']]);
            if ($nowUser['openid'] && $nowUser['status'] != 0) {
                $href = cfg('site_url') . '/wap.php?c=Village_group&a=orderDetail&order_id=' . $order['order_id'];
                (new TemplateNewsService())->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $nowUser['openid'],
                    'first' =>  cfg('village_group_alias') . '订单',
                    'OrderSn' => $order['real_orderid'],
                    'OrderStatus' => $order['status'],
                    'remark' => date('Y-m-d H:i:s')
                ), $order['mer_id']??0);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 骑手接口单后处理逻辑
     *
     * @param int $businessOrderid 订单ID
     * @param array $deliverInfo 骑手信息
     * @return void
     * @author: zt
     * @date: 2023/02/06
     */
    public function afterGrap($businessOrderid, $deliverInfo)
    {
        $orderMod = new VillageGroupOrder();
        $orderDetail = new VillageGroupOrderDetail();
        $orderMod->where('order_id', $businessOrderid)->update(['order_status' => 1, 'deliver_info' => serialize($deliverInfo)]);

        $details = $orderDetail->where('order_id', $businessOrderid)->select();
        foreach ($details as $value) {
            $param['goods_id'] = $value['goods_id'];
            $param['from_type'] = 1;
            $param['deliver_status'] = 6;
            $param['note'] = L_('配送接单');
            $param['name'] = $deliverInfo['name'];
            $param['order_id'] = $value['order_id'];
            $this->addLog($param);
        }
    }


    /**
     * 上报到店后处理逻辑
     *
     * @param int $businessOrderid 订单ID
     * @param array $deliverInfo 骑手信息
     * @return void
     * @author: zt
     * @date: 2023/02/06
     */
    public function afterReportArriveStore($businessOrderid, $deliverInfo)
    {
        $orderMod = new VillageGroupOrder();
        $orderDetail = new VillageGroupOrderDetail();
        $orderMod->where('order_id', $businessOrderid)->update(['order_status' => 2]);

        $details = $orderDetail->where('order_id', $businessOrderid)->select();
        foreach ($details as $value) {
            $param['goods_id'] = $value['goods_id'];
            $param['from_type'] = 1;
            $param['deliver_status'] = 7;
            $param['note'] = '配送在团长处取货';
            $param['name'] = $deliverInfo['name'];
            $param['order_id'] = $value['order_id'];
            $this->addLog($param);
        }
    }

    /**
     * 取货后处理逻辑
     *
     * @param int $businessOrderid 订单ID
     * @param array $deliverInfo 骑手信息
     * @return void
     * @author: zt
     * @date: 2023/02/06
     */
    public function afterPickOrder($businessOrderid, $deliverInfo)
    {
        $orderMod = new VillageGroupOrder();
        $orderDetail = new VillageGroupOrderDetail();
        $orderMod->where('order_id', $businessOrderid)->update(['order_status' => 3]);

        $details = $orderDetail->where('order_id', $businessOrderid)->select();
        foreach ($details as $value) {
            $param['goods_id'] = $value['goods_id'];
            $param['from_type'] = 1;
            $param['deliver_status'] = 8;
            $param['note'] = '配送员配送中';
            $param['name'] = $deliverInfo['name'];
            $param['order_id'] = $value['order_id'];
            $this->addLog($param);
        }
    }

    /**
     * 我已送达后处理逻辑
     *
     * @param int $businessOrderid 订单ID
     * @param array $deliverInfo 骑手信息
     * @return void
     * @author: zt
     * @date: 2023/02/06
     */
    public function afterFinishOrder($businessOrderid, $deliverInfo)
    {
        $orderMod = new VillageGroupOrder();
        $orderDetail = new VillageGroupOrderDetail();
        $order = $orderMod->where('order_id', $businessOrderid)->findOrEmpty()->toArray();
        if (empty($order)) {
            return false;
        }

        $orderMod->where('order_id', $businessOrderid)->update(['order_status' => 4]);
        $details = $orderDetail->where('order_id', $businessOrderid)->select();
        foreach ($details as $value) {
            $param['goods_id'] = $value['goods_id'];
            $param['from_type'] = 1;
            $param['deliver_status'] = 9;
            $param['note'] = '配送员送达，完成核销';
            $param['name'] = $deliverInfo['name'];
            $param['order_id'] = $value['order_id'];
            $this->addLog($param);
        }
        $orderDetail->where('order_id', $businessOrderid)->update(['status' => 3]);
        if ($order) {
            $myHeader = $this->getHeaderInfo(0,$order['start_uid'])['msg'];
            $orderDetails = invoke_cms_model('Village_group/get_order_detail', [$businessOrderid, 1]);
            $res = invoke_cms_model('Village_group/verify_notice', [$orderDetails['retval'], $order, $myHeader, 2]);
            fdump_api([$businessOrderid,$res],'village_group_finish_deliver',1);
            if($res['retval']['error']!=0){
                throw new \think\Exception(L_($res['retval']['msg']));
            }
        }
        return true;
    }
    
    public function settlementLiveCommission()
    {
        $refundDays = Config::where(['name' => 'village_group_order_refund_days'])->value('value');
        VillageGroupOrder::where([
            ['bill_time', '>',0],
            ['status', 'IN',[2,13]],
            ['live_id', '>',0],
            ['live_commission_status', '=',10],
            ['bill_time', '<',time() - $refundDays * 86400],
        ])->limit(20)->select()->each(function ($item){
            $live_desc = "用户购买社区团购商品，增加佣金，直播ID".$item['live_id'];
            if($item['live_type'] == 1){
                $uid = LiveShow::where(['live_id' => $item['live_id']])->value('uid');
                $res = (new UserService())->addMoney($uid,  $item['live_commission'], $live_desc);
                if($res['error_code']){
                    fdump_api([
                        'village_group_order' => $item->toArray(),
                        'msg' => '社区团购商品，增加佣金',
                        'error' => $res
                    ], 'village_order/live_commission', true);
                }else{
                    $item->live_commission_status = 20;
                    $item->save();
                }
            }
        });
    }
    
}