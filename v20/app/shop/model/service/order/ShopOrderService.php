<?php


namespace app\shop\model\service\order;

use app\common\model\db\Area;
use app\common\model\db\Merchant;
use app\common\model\db\MerchantStore;
use app\common\model\db\MiaomaiOrderUpload;
use app\common\model\db\ShopOrder;
use app\common\model\db\SystemCouponHadpull;
use app\common\model\db\SystemOrder;
use app\common\model\db\User;
use app\common\model\db\UserAdress;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\user\UserNoticeService;
use app\common\model\service\UserMoneyListService;
use app\common\model\service\UserService;
use app\deliver\Code;
use app\deliver\model\db\DeliverSupply;
use app\deliver\model\db\DeliverUser;
use app\deliver\model\service\DeliverSupplyService;
use app\deliver\model\service\DeliverUserService;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\service\InternalCardMoneyBillService;
use app\mall\model\db\Express;
use app\mall\model\db\MallOrderRefund;
use app\mall\model\service\MallOrderRemindService;
use app\mall\model\service\MallOrderService;
use app\merchant\model\db\MerchantAddressEditSetting;
use app\merchant\model\db\OrderAddressChangeRecord;
use app\merchant\model\service\MerchantMoneyListService;
use app\merchant\model\service\MerchantStoreService;
use app\shop\model\db\ShopGoods;
use app\shop\model\db\ShopOrderDetail;
use app\shop\model\db\ShopOrderRefund;
use app\shop\model\db\ShopRefundDetail;
use app\shop\model\service\store\MerchantStoreShopService;
use app\storestaff\model\service\StoreStaffService;
use app\thirdRobot\model\db\ThirdRobotDeliveryOrder;
use Carbon\Carbon;
use map\longLat;
use miaomai\Miaomaiyun;
use think\Exception;
use think\facade\Db;
use think\helper\Arr;

/**
 * 外卖订单服务类
 * @author: 张涛
 * @date: 2020/09/09
 */
class ShopOrderService
{
    public $shopOrderMod = null;

    public function __construct()
    {
        $this->shopOrderMod = new ShopOrder();
    }

    /**
     * 获取一条订单信息
     * @param $orderId
     * @author: 汪晨
     * @date: 2021/1/28
    */
    public function getOneOrder($orderId, $field = true){
        return $this->shopOrderMod->getOne(array('order_id'=>$orderId),$field);
    }


    /**
     * 更新订单催单信息
     * @param $orderId
     * @author: 汪晨
     * @date: 2021/2/8
    */
    public function saveOneOrder($orderId, $data){
        return $this->shopOrderMod->where('order_id', $orderId)->update($data);
    }


    /**
     * 根据订单ID获取地址（收货地址 + 取件地址）
     * @param $orderId
     * @author: 张涛
     * @date: 2020/9/18
     */
    public function getOrderInfoForRoutePoint($orderId)
    {
        $shopOrder = $this->shopOrderMod->where('order_id', $orderId)->findOrEmpty()->toArray();
        if (empty($shopOrder)) {
            throw new Exception(L_('订单不存在'));
        }

        $rs = [];
        $rs['user_address'] = [
            'type' => 2,
            'title' => $shopOrder['address'],
            'sub_title' => $shopOrder['address'],
            'lng' => $shopOrder['lng'],
            'lat' => $shopOrder['lat'],
        ];

        $storeInfo = (new MerchantStoreService)->getStoreByStoreId($shopOrder['store_id']);
        $rs['pick_address'] = [
            'type' => 1,
            'title' => $storeInfo['name'],
            'sub_title' => $storeInfo['adress'],
            'lng' => $storeInfo['long'],
            'lat' => $storeInfo['lat'],
        ];
        return $rs;
    }


    /**
     * 获取订单信息（针对配送app订单列表展示，其他地方勿用）
     * @author: 张涛
     * @date: 2020/09/09
     */
    public function getInfoForDeliverList($orderId)
    {
        $shopOrder = $this->shopOrderMod->where('order_id', $orderId)->findOrEmpty()->toArray();
        $rs = [];
        if ($shopOrder) {
            $rs['order_id'] = $shopOrder['order_id'];
            $rs['real_orderid'] = $shopOrder['real_orderid'];
            $rs['fetch_number'] = $shopOrder['fetch_number'] ?? 0;
            $rs['note'] = $shopOrder['note'] ?: '';
            $rs['desc'] = $shopOrder['desc'] ?: '';
            $rs['expect_use_time'] = $shopOrder['expect_use_time'];

            //发货、收货地址
            $rs['user_address'] = [
                'title' => $shopOrder['address'],
                'sub_title' => $shopOrder['address'],
                'lng' => $shopOrder['lng'],
                'lat' => $shopOrder['lat'],
                'tag' => L_('送货'),
                'miles' => $shopOrder['distance'] >= 1000 ? round($shopOrder['distance'] / 1000, 2) . 'km' : $shopOrder['distance'] . 'm'
            ];

            $storeInfo = (new MerchantStoreService)->getStoreByStoreId($shopOrder['store_id']);
            if(empty($storeInfo)){
                return [];
            }
            $rs['pick_address'] = [
                'title' => $storeInfo['name'],
                'sub_title' => $storeInfo['adress'],
                'lng' => $storeInfo['long'],
                'lat' => $storeInfo['lat'],
                'tag' => L_('取货'),
                'miles' => '2.2km'
            ];

            $phoneLists = $labels = [];

            $imUrl = '';
            if (cfg('deliver_app_im_open') && request()->log_uid) {
                //$imUrl = build_im_chat_url('deliver_' . (request()->log_uid), 'user_' . $shopOrder['uid'], 'deliver2user');
                $imUrl = build_im_chat_url('deliver_' . (request()->log_uid), 'user_' . $shopOrder['uid'], 'deliver2user');
                //$imUrl = cfg('site_url') . '/packapp/project_message/pages/shop/groupConversation/index?username=deliver_' . (request()->log_uid) . '&order_id=' . $orderId;
            }

            //手机号
            $buyer = (new User())->getUser('uid,nickname,phone', ['uid' => $shopOrder['uid']]);
            $virtualPhone = [];
            if ($shopOrder['virtual_phone']) {
                $splitArr = array_filter(explode(';', $shopOrder['virtual_phone']));
                foreach ($splitArr as $v) {
                    $split = explode(',', $v);
                    if (count($split) == 2) {
                        $virtualPhone[] = ['phone' => $split[0], 'transfer' => $split[1]];
                    }
                }
            }

            if ($shopOrder['userphone']) {
                $show=1;
                $deliver=(new DeliverSupply())->geOrder(['order_id'=>$orderId],'uid');
                if(!empty($deliver)){
                    $deliver=$deliver->toArray();
                    $d_status=(new DeliverUser())->getOne(['uid'=>$deliver['uid']],'show_phone_status');
                    if(!empty($d_status)){
                        $d_status=$d_status->toArray();
                        if(!$d_status['show_phone_status']){
                            $show=0;
                        }
                    }
                }
                if (isset($virtualPhone[0]) && $show) {//显示隐私号
                    $phoneLists[] = [
                        "name" => $shopOrder['username'],
                        "type" => 1,
                        "txt" => L_("收货人"),
                        "show_phone" => $virtualPhone[0]['phone'] . '转' . $virtualPhone[0]['transfer'],
                        "phone" => $virtualPhone[0]['phone'],
                        "im_url" => $imUrl
                    ];
                } else {
                    $phoneLists[] = [
                        "name" => $shopOrder['username'],
                        "type" => 1,
                        "txt" => L_("收货人"),
                        "show_phone" => $shopOrder['userphone'],
                        "phone" => $shopOrder['userphone'],
                        "im_url" => $imUrl
                    ];
                }
            }
            if ($storeInfo['phone']) {
                //多个商家联系电话只取第一个
                $phones = array_values(array_filter(explode(' ', $storeInfo['phone'])));
                $phoneLists[] = [
                    "name" => L_("商家"),
                    "type" => 2,
                    "txt" => L_("发货人"),
                    "show_phone"=>$phones[0] ?? '',
                    "phone" => $phones[0] ?? '',
                    "im_url" => ''
                ];
            }
            if ($buyer['phone'] && $buyer['phone'] != $shopOrder['userphone']) {
                if (isset($virtualPhone[1])) {
                    $phoneLists[] = [
                        "name" => $buyer['nickname'],
                        "type" => 3,
                        "txt" => L_("下单人"),
                        "show_phone" => $virtualPhone[1]['phone'] . '转' . $virtualPhone[1]['transfer'],
                        "phone" => $virtualPhone[1]['phone'],
                        "im_url" => $imUrl
                    ];
                } else {
                    $phoneLists[] = [
                        "name" => $buyer['nickname'],
                        "type" => 3,
                        "txt" => L_("下单人"),
                        "show_phone" => $buyer['phone'],
                        "phone" => $buyer['phone'],
                        "im_url" => $imUrl
                    ];
                }
            }
            $rs['phone_lists'] = $phoneLists;

            //标签
            $labels = [
                [
                    "txt" => $shopOrder['order_from'] == 1 ?  L_("商城") : (cfg('shop_alias_name') ? cfg('shop_alias_name') : L_("快店")),
                    "background" => "#A057F5",
                    "font_color" => "#FFFFFF",
                    "with_border" => false
                ]
            ];
            if ($shopOrder['invoice_head']) {
                $labels[] = [
                    "txt" => L_("需发票"),
                    "background" => "#FFFFFF",
                    "font_color" => "#27A3F7",
                    "with_border" => true
                ];
            }
            $changeAddressStatus = SystemOrder::where(['type' => 'shop', 'order_id' => $orderId])
                ->value('change_address_status');
            if($changeAddressStatus == 4){//同意修改
                $labels[] = [
                    "txt" => L_("地址已更改"),
                    "background" => "#1DC5A6",
                    "font_color" => "#FFFFFF",
                    "with_border" => false
                ];
            }
            $rs['labels'] = $labels;
            $rs['order'] = $shopOrder;
            $rs['username'] = $shopOrder['username'];
            $rs['phone'] = $shopOrder['userphone'];
        }
        return $rs;
    }

    /**
     * 快店、商城配送员接单操作
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function grabOrder($uid, $supplyId)
    {
        $supplyMod = new DeliverSupply();
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        $tm = time();

        if ($supply['uid'] != 0) {
            throw new Exception(L_('订单已被抢'), Code::ORDER_ACCEPTED);
        }

        if ($user['group'] == 2 && ($user['store_id'] || $user['store_ids'])) {
            $storeIds = !empty($user['store_ids']) ? explode(',', $user['store_ids']) : [$user['store_id']];
            if (!in_array($supply['store_id'], $storeIds)) {
                throw new Exception(L_('非该店铺配送员禁止接单！'));
            }
        }

        //检测最大接单量
        if ($user['max_num'] > 0) {
            $count = $supplyMod->where("uid={$uid} AND status >0 AND status < 5")->count();
            if ($count >= $user['max_num']) {
                throw new Exception(L_('您当前已有X1单没有完成配送，请配送完成再来抢单！', array('X1' => $count)));
            }
        }

        // 开启配送员等级
        if (cfg('open_deliver_level') && $user['group'] == 1) {
            $nowLevel = (new DeliverUserService())->getUserLevel($user['score']);
            if ($nowLevel['order_number'] >= 0) {
                // 今日接单量
                $todayNum = $supplyMod->where("uid={$uid} AND start_time >" . strtotime(date("Y-m-d")))->count();
                if ($todayNum >= $nowLevel['order_number']) {
                    throw new Exception(L_('今日接单量已达上限！'));
                }
            }
        }

        $columns = [
            'uid' => $uid,
            'status' => 2,
            'deliver_status' => 2,
            'is_fetch_order' => 1,
            'start_time' => $tm,
            'deliver_user_fee' => $supply['freight_charge'] - ($supply['freight_charge'] * ($user['take_percent'] / 100)),
            'grab_time' => $tm
        ];
        (new DeliverSupply())->where('supply_id', $supplyId)->update($columns);

        $order = (new ShopOrder())->where(['order_id' => $supply['order_id']])->findOrEmpty()->toArray();
        if ($order['order_status'] != 1) {
            throw new Exception(L_('订单信息错误！'));
        }
        //更新订单状态
        $deliverInfo = serialize(array('uid' => $user['uid'], 'name' => $user['name'], 'phone' => $user['phone'], 'store_id' => $user['store_id']));
        $result = (new ShopOrder())->updateOrder(['order_id' => $supply['order_id']], ['order_status' => 2, 'deliver_info' => $deliverInfo]);
        if (!$result) {
            throw new Exception(L_('更新订单信息失败！'));
        }

        // 周期购
        if ($order['periodic_type']) {
            $pData = array();
            $pData['status'] = 1;
            $pData['update_time'] = $tm;
            $pData['accept_time'] = $tm;
            $pData['deliver_uid'] = $user['uid'];
            $pData['deliver_type'] = 2;
            $pData['deliver_money'] = $supply['freight_charge'];

            $pWhere = array();
            //获得最近需要配送的周期
            $periodicDeliver = \think\facade\Db::name('mall_periodic_deliver')->where(['order_id' => $supply['order_id'], 'status' => 0])->order('deliver_num', 'asc')->findOrEmpty()->toArray();
            $pWhere['id'] = $periodicDeliver['id'];

            \think\facade\Db::name('mall_periodic_deliver')->where($pWhere)->update($pData);
        }

        //增加消息通知
        (new UserNoticeService())->addNotice([
            'type'=>0,
            'business'=>'shop',
            'order_id'=>$supply['order_id'],
            'title'=>'骑手正在配送',
            'content'=>'骑手正在配送，商品将尽快为你送达',
        ]);


        //修改地址自动撤销
        $this->cancelChangeAddress($supply['order_id'], $supply['mer_id'], $supply['type'], 2);
        

        $log = [
            'order_id' => $supply['order_id'],
            'status' => 3,
            'name' => $user['name'],
            'phone' => $user['phone'],
            'periodic_id' => $periodicDeliver['id'] ?? 0
        ];
        invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
    }

    /**
     * 上报到店
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function reportArriveStore($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        if($supply['status']==5){
            throw new Exception(L_('操作失败，订单已完成！'));
        }

        $columns = [
            'status' => 3,
            'deliver_status' => 3,
            'is_fetch_order' => 1,
            'real_fetch_good_time' => date('Y-m-d H:i:s'),
            'report_arrive_store_time' => time()
        ];

        (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->update($columns);

        $shopOrderMod = new ShopOrder();
        $order = $shopOrderMod->where(['order_id' => $supply['order_id']])->findOrEmpty()->toArray();
        if (empty($order)) {
            throw new Exception(L_('订单不存在！'));
        }
        $result = (new ShopOrder())->updateOrder(['order_id' => $supply['order_id']], ['order_status' => 3]);
        if (!$result) {
            throw new Exception(L_('更新订单信息失败！'));
        }
        
        $this->cancelChangeAddress($supply['order_id'], $supply['mer_id'], $supply['type'], 3);

        $log = [
            'order_id' => $supply['order_id'],
            'status' => 4,
            'name' => $user['name'],
            'phone' => $user['phone']
        ];
        invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
    }
  
    public function robotGrabOrder($orderId)
    {
        $order = ShopOrder::where(['order_id' => $orderId])->find()->toArray();
        if (empty($order)) {
            throw new Exception(L_('订单不存在！'));
        }
        //更新订单状态
        $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], ['order_status' => 2]);
        if (!$result) {
            throw new Exception(L_('更新订单信息失败！'));
        }

        // 周期购
        if ($order['periodic_type']) {
            throw new Exception(L_('当前为周期购订单！'));
        }

        //增加消息通知
        (new UserNoticeService())->addNotice([
            'type'     => 0,
            'business' => 'shop',
            'order_id' => $orderId,
            'uid'      => $order['uid'],
            'title'    => '机器人正在配送',
            'content'  => '机器人正在配送，商品将尽快为你送达',
        ]);

        $log = [
            'order_id' => $orderId,
            'status' => 3,
            'name' => '机器人',
            'phone' => '',
            'periodic_id' => 0
        ];
        invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
    }
    
    public function robotReportArriveStore($orderId)
    {
        $shopOrderMod = new ShopOrder();
        $order = $shopOrderMod->where(['order_id' => $orderId])->findOrEmpty()->toArray();
        if (empty($order)) {
            throw new Exception(L_('订单不存在！'));
        }
        $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], ['order_status' => 3]);
        if (!$result) {
            throw new Exception(L_('更新订单信息失败！'));
        }

        $log = [
            'order_id' => $orderId,
            'status' => 4,
            'name' => '机器人',
            'phone' => ''
        ];
        invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
    }
    
    public function cancelChangeAddress($orderId, $merId, $deliverType, $status)
    {
        //修改地址自动撤销
        $changeAddressStatus = SystemOrder::where(['type' => 'shop', 'order_id' => $orderId])
            ->field('change_address_status')
            ->find();

        $addressSetting = MerchantAddressEditSetting::where(['merchant_id' => $merId])->find();

        if(
            $changeAddressStatus['change_address_status'] == 1 && //1 等待商家审核
            $deliverType == 1 && //商家配送
            !empty($addressSetting) &&
            $addressSetting['order_status'] == $status //1:店铺接单前|2:骑手接单前|3:骑手到店前
        ){
            SystemOrder::where(['type' => 'shop', 'order_id' => $orderId])
                ->save(['change_address_status' => 3]);//3 修改地址自动撤销
        }
    }

    /**
     * 我已取货(迁移旧版取货 + 配送功能)
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function pickOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        $columns = [
            'uid' => $uid,
            'status' => 4,
            'deliver_status' => 4,
            'end_time' => time(),
            'pick_time' => time(),
            'real_deliver_start_time' => date('Y-m-d H:i:s')
        ];
        $result = (new DeliverSupply())->where(['uid' => $uid, 'supply_id' => $supplyId, 'deliver_status' => 3])->update($columns);
        if (!$result) {
            throw new Exception(L_('更新配送信息失败！'));
        }

        $result = (new ShopOrder())->updateOrder(['order_id' => $supply['order_id']], ['order_status' => 4]);
        if (!$result) {
            throw new Exception(L_('更新订单信息失败！'));
        }

        //日志
        $log = [
            'order_id' => $supply['order_id'],
            'status' => 5,
            'name' => $user['name'],
            'phone' => $user['phone']
        ];
        invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
    }
    /*
     * 机器人取货
     */
    public function robotPickOrder($orderId)
    {
        $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], ['order_status' => 4]);
        if (!$result) {
            throw new Exception(L_('更新订单信息失败！'));
        }
        //日志
        $log = [
            'order_id' => $orderId,
            'status' => 5,
            'name' => '机器人',
            'phone' => ''
        ];
        invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
    }
    
    /**
     * 我已送达
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function finishOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        if ($supply['status'] != 4) {
            throw new Exception(L_('当前状态不能修改成完成状态！'));
        }
        $shopOrderMod = new ShopOrder();
        $order = $shopOrderMod->where(['order_id' => $supply['order_id']])->findOrEmpty()->toArray();
        if (empty($order)) {
            throw new Exception(L_('订单不存在！'));
        }
        $goods_refund_num = (new ShopOrderRefund())->alias('r')->join('shop_refund_detail rd', 'r.id = rd.refund_id')
            ->where(['r.status'=>1,'r.order_id'=>$supply['order_id']])
            ->sum('rd.num');
        $goods_num = (new ShopOrderDetail())->where(['order_id'=>$supply['order_id']])->sum('num');
        if($order['status']==4&&$goods_refund_num>=$goods_num){ //全部退款无法操作
            throw new Exception(L_('该订单已退款无法修改成配送完成状态！'));
        }
        if (!in_array($order['status'],[1,4])){ //只有商家发货（状态1）和部分退款才可以操作（退款状态是4，上面已判断全退款了）
            throw new Exception(L_('该订单的当前状态无法修改成配送完成状态！'));
        }
        
        $columns = [
            'uid' => $uid,
            'status' => 5,
            'deliver_status' => 5,
            'end_time' => time(),
            'finish_time' => time()
        ];
        $result = (new DeliverSupply())->where(['uid' => $uid, 'supply_id' => $supplyId, 'status' => 4])->update($columns);
        if (!$result) {
            throw new Exception(L_('更新配送信息失败！'));
        }

        //统计每日配送量
        (new DeliverUserService())->updateDeliverUserTotalNum($uid);
        (new DeliverUserService())->updateDeliverUserNumByDate($uid, date('Ymd'));
        
        $data = ['order_status' => 5];
        if($order['status']!=4){
            $data['status'] = 2;
        }
        if ($order['is_pick_in_store'] == 0) {
            if ($order['paid'] == 0 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {
                $data['paid'] = $order['paid'] == 0 ? 1 : $order['paid'];
                $data['pay_type'] = '';
                $data['balance_pay'] = $order['balance_pay'] + $supply['deliver_cash'];
                $order['balance_pay'] = $order['balance_pay'] + $supply['deliver_cash'];
            }
        } else {
            if ($order['paid'] == 0) {
                $data['paid'] = 1;
                if (empty($order['pay_type']) && empty($order['pay_time'])) {
                    $data['pay_type'] = 'offline';
                }
            }
        }
        if (empty($order['pay_time'])) $data['pay_time'] = time();
        $data['use_time']  = time();//更新送达时间
        $data['last_time'] = time();
        if (empty($order['third_id'])) $data['third_id'] = $order['order_id'];

        if ($shopOrderMod->where('order_id', $supply['order_id'])->where('status', '<>', 2)->update($data)) {
            if ($order['is_pick_in_store'] == 0) { //平台配送
                if ($order['paid'] == 0 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {
                    (new UserMoneyListService())->addRow($order['uid'], 1, $supply['deliver_cash'], L_('配送员模拟手动充值'));
                    (new UserMoneyListService())->addRow($order['uid'], 2, $supply['deliver_cash'], L_('用户购买X1产品', ['X1' => cfg('shop_alias_name')]));
                }
            }

            \think\facade\Db::name('pick_order')->where(['store_id' => $order['store_id'], 'order_id' => $order['order_id']])->update(['status' => 4]);

            invoke_cms_model('Shop_order/shop_notice', [$order], true);

            //检查含有称重商品的订单，是否需要部分退款
            if (cfg('is_open_shop_weigh') == 1 && $order['is_weight'] == 1) {
                invoke_cms_model('Shop_order/checkWeightOrder', [$order]);
            }

            if ($order['is_pick_in_store'] == 5) {
                if ($order['platform'] == 1) {
                    (new MerchantMoneyListService())->useMoney($order['mer_id'], $order['freight_charge'], 'deliver', L_('平台配送商家由饿了么平台的订单的配送费'), $order['order_id']);
                } elseif ($order['platform'] == 2) {
                    (new MerchantMoneyListService())->useMoney($order['mer_id'], $order['freight_charge'], 'deliver', L_('平台配送商家由美团外卖平台的订单的配送费'), $order['order_id']);
                }
            }
            // 周期购
            if ($order['periodic_type']) { //周期购
                //获得最近需要配送的周期
                $periodicDeliver = \think\facade\Db::name('mall_periodic_deliver')->where(['order_id' => $order['order_id'], 'status' => 0])->order('deliver_num', 'asc')->findOrEmpty()->toArray();
                // 进入下一周期 配送状态更新
                if ($periodicDeliver) {
                    $oData = [];
                    $oData['is_pick_in_store'] = 3;
                    $oData['order_status'] = 0;
                    $oData['status'] = 0;
                    $oData['deliver_info'] = '';
                    (new ShopOrder())->updateOrder(['order_id' => $order['order_id']], $oData);

                    $log = [
                        'order_id' => $order['order_id'],
                        'status' => 35,
                        'name' => $user['name'],
                        'phone' => $user['phone'],
                        'note' => L_('配送员确认收货')
                    ];
                    invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
                }
            } else {
                $log = [
                    'order_id' => $supply['order_id'],
                    'status' => 6,
                    'name' => $user['name'],
                    'phone' => $user['phone']
                ];
                invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
            }
        } else {
            throw new Exception(L_('订单状态更新失败！'));
        }

        if (cfg('open_deliver_level') && $user['group'] == 1) {
            if ($order['expect_use_time'] >= time()) {
                // 准时达积分
                $score = cfg('deliver_level_ontime_get_score');
                $desc = '准时配送获得' . $score . '积分';
                (new DeliverUserService())->addScore($uid, $score, $desc, 4, $supply);
            } else {
                // 配送超时扣除积分
                $score = cfg('deliver_level_overtime_deduct_score');
                $desc = '配送超时扣除' . $score . '积分';
                (new DeliverUserService())->deductScore($uid, $score, $desc, 6, $supply);
            }
        }

        // 完成订单获取配送员积分
        if (cfg('open_deliver_level') && $user['group'] == 1) {
            $score = cfg('deliver_level_complate_get_score');
            $desc = '完成订单获得' . $score . '积分';
            (new DeliverUserService())->addScore($uid, $score, $desc, 2, $supply);
        }

        //增加消息通知
        (new UserNoticeService())->addNotice([
            'type'=>0,
            'business'=>'shop',
            'order_id'=>$supply['order_id'],
            'title'=>'商品已经送达',
            'content'=>'您的外卖商品已经送达，点击查看订单详情',
        ]);
        
    }

    /**
     * 扔回
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function throwOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();

        if (empty($supply)) {
            throw new Exception(L_('配送记录不存在'));
        }
        if ($user['is_cancel_order'] == 0) {
            throw new Exception(L_('您没有扔回的权限'));
        }
        if (!in_array($supply['deliver_status'], [2])) {
            throw new Exception(L_('该配送状态禁止扔回'));
        }

        $saveData = ['uid' => 0, 'status' => 1, 'start_time' => 0, 'offer_id' => 0, 'back_time' => time(), 'is_fetch_order' => 0, 'deliver_status' => 1];
        $saveData['back_log'] = $supply['back_log'] ? $supply['back_log'] . ',' . $uid : $uid;
        $result = (new DeliverSupply())->where(['uid' => $uid, 'supply_id' => $supplyId])->update($saveData);
        if (!$result) {
            throw new Exception(L_('更新配送信息失败！'));
        }

        $orderId = $supply['order_id'];
        $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], ['order_status' => 1, 'deliver_info' => '']);
        if (!$result) {
            throw new Exception(L_('更新订单信息失败！'));
        }

        //记录订单日志
        $log = [
            'order_id' => $orderId,
            'status' => 31,
            'name' => $user['name'],
            'phone' => $user['phone'],
            'note' => L_('配送员【X1】放弃配送，等待下个配送员接单配送', ['X1' => $user['name']])
        ];
        invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);

        //记录扔回日志
        $cancelData = [];
        $cancelData['uid'] = $user['uid'];
        $cancelData['username'] = $user['name'];
        $cancelData['userphone'] = $user['phone'];
        $cancelData['supply_id'] = $supply['supply_id'];
        $cancelData['store_id'] = $supply['store_id'];
        $cancelData['order_id'] = $supply['order_id'];
        $cancelData['item'] = $supply['item'];
        $cancelData['dateline'] = time();
        \think\facade\Db::name('deliver_cancel_log')->insert($cancelData);

        $supply['uid'] = 0;
        $supply['status'] = 1;
        $supply['start_time'] = 0;
        invoke_cms_model('Deliver_supply/sendMsg', ['supply' => $supply, 'excludeUid' => $uid], true);
    }

    /**
     * 转单
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function transfterOrder($uid, $supplyId, $transferToUid)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->findOrEmpty()->toArray();
        $transferUser = (new DeliverUser())->where('uid', $transferToUid)->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception(L_('配送记录不存在'));
        }
        if (!in_array($supply['deliver_status'], [2, 3, 4])) {
            throw new Exception(L_('该配送状态禁止转单'));
        }
        if ($transferUser['status'] != 1) {
            throw new Exception(L_('转单配送员不存在'));
        }
        if ($transferUser['is_notice'] != 0) {
            throw new Exception(L_('转单配送员暂不接单'));
        }
        $data = [
            'transfer_from_uid' => $uid,
            'transfer_deliver_status' => $supply['deliver_status'],
            'transfer_to_uid' => $transferToUid,
            'transfer_accept_time' => 0,
            'transfer_refuse_time' => 0,
            'transfer_status' => 0,
            'transfer_time' => time()
        ];
        $result = (new DeliverSupply())->where(['uid' => $uid, 'supply_id' => $supplyId])->update($data);
        if (!$result) {
            throw new Exception(L_('更新配送信息失败！'));
        }

        //增加推送新订单
        invoke_cms_model('Deliver_supply/newOrder', [$transferUser]);
    }

    /**
     * 接受转单
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function acceptTransferOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'transfer_to_uid' => $uid])->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception(L_('转单记录不存在'), Code::TRANSFER_ORDER_NOT_EXIST);
        }

        //超时了
        if (time() > $supply['transfer_time'] + config('const.refuse_transfer_order_expire')) {
            (new DeliverSupplyService())->transferExpiredUpdae($supplyId);
            throw new Exception(L_('转单超时'), Code::TRANSFER_ORDER_EXPIRED);
        } else {
            //转单后重新计算佣金
            $deliver_user_fee = $supply['freight_charge'] - ($supply['freight_charge'] * ($user['take_percent'] / 100));
            $data = [
                'uid' => $supply['transfer_to_uid'],
                'transfer_accept_time' => time(),
                'transfer_status' => 1,
                'deliver_user_fee' => $deliver_user_fee
            ];
            (new DeliverSupply())->where(['supply_id' => $supplyId])->update($data);

            //更新订单配送员信息
            $deliverInfo = serialize(array('uid' => $user['uid'], 'name' => $user['name'], 'phone' => $user['phone'], 'store_id' => $user['store_id']));
            (new ShopOrder())->updateOrder(['order_id' => $supply['order_id']], ['deliver_info' => $deliverInfo]);

            //记录订单日志
            $transferFromUser = (new DeliverUser())->where('uid', $supply['uid'])->findOrEmpty()->toArray();
            $log = [
                'order_id' => $supply['order_id'],
                'status' => 36,
                'name' => $user['name'],
                'phone' => $user['phone'],
                'note' => L_('接受来自配送员【X1】的转单', ['X1' => $transferFromUser['name']])
            ];
            invoke_cms_model('Shop_order_log/add_log', ['param' => $log]);
        }
    }


    /**
     * 拒绝转单和指派单
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function refuseOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId])->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception(L_('配送单记录不存在'));
        }
        if ($supply['transfer_to_uid'] > 0) {
            //拒绝转单
            if (time() > $supply['transfer_time'] + config('const.refuse_transfer_order_expire')) {
                (new DeliverSupplyService())->transferExpiredUpdae($supplyId);
                throw new Exception(L_('转单超时'), Code::TRANSFER_ORDER_EXPIRED);
            }
        }

        $refuseLog = explode(',', $supply['refuse_log']);
        $refuseLog[] = $uid;
        $newRefuseLog = implode(',', array_filter(array_unique($refuseLog)));
        $data = [
            'transfer_refuse_time' => time(),
            'refuse_log' => $newRefuseLog,
            'transfer_status' => 2,
            'uid' => $supply['transfer_from_uid'] > 0 ? $supply['transfer_from_uid'] : 0,
        ];
        if ($supply['transfer_from_uid'] == 0) {
            $data['status'] = 1;
            $data['deliver_status'] = 1;
        }
        //拒绝订单扔回智能调度
        if ($supply['transfer_to_uid'] == 0) {
            $data['is_smart'] = 0;
            $data['next_time'] = time() + 60;
        }
        (new DeliverSupply())->where(['supply_id' => $supplyId])->update($data);
    }

    /**
     * 接受派单
     * @param $uid
     * @param $supplyId
     * @author: 张涛
     * @date: 2020/10/24
     */
    public function fetchOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->findOrEmpty()->toArray();
        $nowUser = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception(L_('该订单已指派给其他配送员，请刷新订单'));
        }
        if ($supply['is_fetch_order'] != 0) {
            throw new Exception(L_('当前订单已接单'));
        }
        if (empty($nowUser)) {
            throw new Exception(L_('配送员不存在'));
        }


        $data = ['is_fetch_order' => 1];
        $result = (new DeliverSupply())->where(['uid' => $uid, 'supply_id' => $supplyId])->update($data);
        if (!$result) {
            throw new Exception(L_('更新配送信息失败！'));
        }
        //增加消息通知
        (new UserNoticeService())->addNotice([
            'type'=>0,
            'business'=>'shop',
            'order_id'=>$supply['order_id'],
            'title'=>'骑手正在配送',
            'content'=>'骑手正在配送，商品将尽快为你送达',
        ]);

        //记录订单日志
        $log = [
            'order_id' => $supply['order_id'],
            'status' => 35,
            'name' => $nowUser['name'],
            'phone' => $nowUser['phone']
        ];
        invoke_cms_model('Shop_order_log/add_log', ['param' => $log], true);
    }

    /**
     * 根据订单id获取商品信息
     * @author: 张涛
     * @date: 2020/9/15
     */
    public function getGoodsByOrderId($orderId, $fields = '*', $order = ['host_goods_id' => 'asc'])
    {
        return (new ShopOrderDetail())->where(['order_id' => $orderId])->field($fields)->order($order)->select()->toArray();
    }

    /**
     * 合并附属菜商品接口
     * @author: 张涛
     * @date: 2021/04/16
     */
    public function getGoodsByMergeSubsidiaryGoods($orderId)
    {
        $goods = $this->getGoodsByOrderId($orderId, 'goods_id,host_goods_id,name,num,price,spec,uniqueness_number');
        $subGoods = [];
        foreach ($goods as $k => $v) {
            if ($v['host_goods_id']) {
                $price = $v['price'] * $v['num'];
                $str = ($v['spec'] ? ($v['name'] . '(' . $v['spec'] . ')') : $v['name']) . '×' . $v['num'];
                if (isset($subGoods[$v['uniqueness_number']])) {
                    $subGoods[$v['uniqueness_number']]['price'] += $price;
                    $subGoods[$v['uniqueness_number']]['str'] .= '，' . $str;
                } else {
                    $subGoods[$v['uniqueness_number']] = ['price' => $price, 'str' => $str];
                }
                unset($goods[$k]);
            }
        }

        foreach ($goods as $gk => $gv) {
            if (isset($subGoods[$gv['uniqueness_number']])) {
                $goods[$gk]['price'] = get_format_number($gv['price'] * $gv['num'] + $subGoods[$gv['uniqueness_number']]['price']);
                $goods[$gk]['spec'] = $gv['spec'] ? $gv['spec'] . '，' . $subGoods[$gv['uniqueness_number']]['str'] : $subGoods[$gv['uniqueness_number']]['str'];
            } else {
                $goods[$gk]['price'] = get_format_number($gv['price'] * $gv['num']);
            }
            $refund_goods_num = (new ShopOrderRefund())->alias('r')->join('shop_refund_detail rd', 'r.id = rd.refund_id')
                ->where(['r.status'=>1,'r.order_id'=>$orderId,'rd.goods_id'=>$gv['goods_id']])
                ->sum('rd.num');
            $goods[$gk]['refund_nums'] = (int)$refund_goods_num?:0;
        }
        return array_values($goods);
    }

    /**
     * 获取订单信息
     * @author: 张涛
     * @date: 2020/9/15
     */
    public function getOrderInfo($where = [], $fields = '*')
    {
        if (empty($where)) {
            throw new Exception(L_('请传入查询条件！'));
        }
        return $this->shopOrderMod->where($where)->field($fields)->findOrEmpty()->toArray();
    }

    /**
     * 判断称重订单是否都已录入重量
     * @author: 张涛
     * @date: 2020/11/11
     */
    public function isAllWriteRealWeight($orderId)
    {
        $rs = (new ShopOrderDetail())->where(['order_id' => $orderId, 'sell_type' => 2, 'real_weight' => 0])->find();
        return $rs ? false : true;
    }

    /**
     * 订单商品组合
     * @author: 张涛
     * @date: 2020/11/9
     */
    public function combineGoods($orderId)
    {
        $goods = [];
        $orderGoods = $this->getGoodsByOrderId($orderId, 'id,goods_id,host_goods_id,uniqueness_number,name,spec,num,price,is_give,rep_id,discount_type,refundNum,discount_price,is_seckill,sell_type,goods_weight,real_weight,pay_price,old_price,discount_rate,packname');
        foreach ($orderGoods as $g) {
            $price = $g['discount_price'];
            $num = $g['num'];
            if($g['sell_type'] == 2){
                $num = $g['num'] ?: $g['real_weight'] / $g['goods_weight'];
                $g['num'] = $g['num'] ?: 1;
            }
            if ($g['discount_type'] == 3) {
                //VIP折扣
                $price = $g['price'];
            } else if ($g['discount_type'] == 4 || $g['discount_type'] == 5) {
                //店铺+VIP折扣  || 分类+VIP折扣
                $arr = explode(',', $g['discount_rate']);
                if (isset($arr[0]) && $arr[0] > 0) {
                    $price = $g['price'] * $arr[0] / 100;
                }
            }


            if ($g['host_goods_id'] > 0) {
                //附属菜
                $subGoods = [
                    "detail_id" => $g['id'],
                    "goods_id" => $g['goods_id'],
                    "name" => $g['name'],
                    "spec" => $g['spec'],
                    "num" => $g['num'],
                    "pay_price" => $g['pay_price'],
                    "old_price" => $g['old_price'],
                    "price" => get_format_number($price * $num),
                    "real_weight" => $g['real_weight'],
                    "goods_weight" => $g['goods_weight'] * $num,
                    "packname" => $g['packname'],
                ];
				$parent_id = 0;
				foreach($goods as $tmp_g){
					if($g['host_goods_id'] == $tmp_g['goods_id'] && $g['uniqueness_number'] == $tmp_g['uniqueness_number']){
						$parent_id = $tmp_g['detail_id'];
					}
				}
				$goods[$parent_id]['subsidiary_goods'][] = $subGoods;
            } else {
                //主菜
                $goods[$g['id']] = [
                    "detail_id" => $g['id'],
                    "goods_id" => $g['goods_id'],
                    "name" => $g['name'],
                    "uniqueness_number" => $g['uniqueness_number'] ?? '',
                    "spec" => $g['spec'],
                    "num" => $g['num'],
                    "pay_price" => $g['pay_price'],
                    "old_price" => $g['old_price'],
                    "price" => get_format_number($price * $num),
                    "real_weight" => $g['real_weight'],
                    "goods_weight" => $g['goods_weight'] * $num,
                    "sell_type" => $g['sell_type'],
                    "is_give" => (bool)$g['is_give'],
                    "is_exchange" => $g['rep_id'] > 0 ? true : false,
                    "is_seckill" => (bool)$g['is_seckill'],
                    "is_discount" => $g['discount_type'] > 0 ? true : false,
                    "refund_num" => $g['refundNum'],
                    "subsidiary_goods" => [],
                    "packname" => $g['packname'],
                ];
            }
        }
        $goods = array_values($goods);
		
        return $goods;
    }

    public function getCount($params)
    {
        $where = [];
        $where[] = [['o.paid', '=', 1], ['o.order_from', '<>', 1]];
        if (isset($params['status'])) {
            if ($params['status'] == 'new') {
                $where[] = ['o.status', '=', 0];
                $where[] = ['o.order_status', '=', 0];
            } else if ($params['status'] == 'progress') {
                $where[] = ['o.status', '<', 2];
                $where[] = ['o.order_status', '>=', 1];
                $where[] = ['o.order_status', '<', 5];
                $where[] = ['o.express_id', '=', 0];
                $where[] = ['o.is_handle', '=', 1];
            } else if ($params['status'] == 'refund') {
                $where[] = ['o.is_apply_refund', '=', 1];
                $where[] = ['o.status', '<>', 4];
                $where[] = ['o.is_handle', '=', 1];
            }
        }
        if (isset($params['store_id'])) {
            $where[] = ['o.store_id', '=', $params['store_id']];
        }
        $where[] = ['o.mer_id', '=', $params['mer_id']];
        if(isset($params['store_id'])&&$params['store_id']>0&&isset($params['pick_addr_ids'])&&$params['pick_addr_ids']){
            $where[] =  ['', 'exp', Db::raw('(o.is_pick_in_store <> 2) or (o.is_pick_in_store = 2 AND FIND_IN_SET(o.pick_id,"'.$params['pick_addr_ids'].'"))')];
        }
        $count = $this->shopOrderMod->alias('o')->join('user u', 'u.uid = o.uid')->where($where)->count();
        return $count;
    }

    /**
     * 获取待处理订单
     * @author: 张涛
     * @date: 2020/11/09
     * @return array
     */
    public function getPendingLists($params)
    {
        $where = [];
        $sort = ['sort_time' => 'asc'];
        $where[] = [['o.paid', '=', 1], ['o.order_from', '<>', 1]];
        $isNewOrder = false;
        if (isset($params['status'])) {
            switch ($params['status']) {
                case 'new':
                    $where[] = ['o.status', '=', 0];
                    $where[] = ['o.order_status', '=', 0];
                    $isNewOrder = true;
                    break;
                case 'progress':
                    $where[] = ['o.status', '<', 2];
                    $where[] = ['o.order_status', '>=', 1];
                    $where[] = ['o.order_status', '<', 5];
                    $where[] = ['o.express_id', '=', 0];
                    $where[] = ['o.is_handle', '=', 1];
                    break;
                case 'refund':
                    $where[] = ['o.is_apply_refund', '=', 1];
                    $where[] = ['o.status', '<>', 4];
                    $where[] = ['o.is_handle', '=', 1];
                    break;
            }
        }
        if (isset($params['store_id']) && $params['store_id'] > 0) {
            $where[] = ['o.store_id', '=', $params['store_id']];
        }
        $where[] = ['o.mer_id', '=', $params['mer_id']];
        if(isset($params['store_id'])&&$params['store_id']>0&&isset($params['pick_addr_ids'])&&$params['pick_addr_ids']){
            $where[] =  ['', 'exp', Db::raw('(o.is_pick_in_store <> 2) or (o.is_pick_in_store = 2 AND FIND_IN_SET(o.pick_id,"'.$params['pick_addr_ids'].'"))')];
        }
        $fields = 'o.*,CASE o.is_pick_in_store WHEN 0 THEN o.expect_pick_time WHEN 1 THEN o.expect_pick_time WHEN 2 THEN o.expect_use_time WHEN 3 THEN o.create_time END AS sort_time';
        $orders = $this->shopOrderMod
            ->alias('o')
            ->join('user u', 'u.uid = o.uid')
            ->where($where)
            ->field($fields)
            ->order($sort)
            ->page($params['page'], $params['pageSize'])
            ->select()
            ->toArray();
        $return = [];

        foreach ($orders as $o) {
            $parseOrder = $this->parseOrderInfo($o, 'new_order');
            if (empty($parseOrder)) {
                continue;
            }
            if (in_array($params['status'], ['new', 'refund'])) {
                $parseOrder['order']['order_out_show'] = false;
            }
            $return[] = $parseOrder;
        }

        if ($isNewOrder && isset($params['store_id']) && $params['store_id'] > 0) {
            //获取新订单的时候清空一下新订单记录
            \think\facade\Db::name('merchant_store_shop_ext')->where(['store_id' => $params['store_id']])->update(['shop_new_order_count' => 0]);
        }
        return $return;
    }

    /**
     * 获取预订单列表
     * @author: 张涛
     * @date: 2020/11/17
     */
    public function getBookOrderLists($params)
    {
        $where = [];
        $sort = ['sort_time' => 'asc'];
        $where[] = [['o.paid', '=', 1], ['o.is_handle', '=', 0], ['o.order_from', '<>', 1], ['o.is_booking', '=', 1], ['o.order_status', '=', 1], ['o.status', '<', 2],['o.store_id','=', $params['store_id']]];

        if(isset($params['store_id'])&&$params['store_id']>0&&isset($params['pick_addr_ids'])&&$params['pick_addr_ids']){
            $where[] =  ['', 'exp', Db::raw('(o.is_pick_in_store <> 2) or (o.is_pick_in_store = 2 AND FIND_IN_SET(o.pick_id,"'.$params['pick_addr_ids'].'"))')];
        }
        if (isset($params['date']) && $params['date']) {
            $unix = strtotime($params['date']);
            $where[] = ['o.expect_use_time', 'between', [$unix, $unix + 86399]];
        }
        $fields = 'o.*,CASE o.is_pick_in_store WHEN 0 THEN o.expect_pick_time WHEN 1 THEN o.expect_pick_time WHEN 2 THEN o.expect_use_time WHEN 3 THEN o.create_time END AS sort_time';
        $orders = $this->shopOrderMod->alias('o')->join('user u', 'u.uid = o.uid')->where($where)->field($fields)->order($sort)->page($params['page'], $params['pageSize'])->select()->toArray();

        $return = [];
        foreach ($orders as $o) {
            $parseOrder = $this->parseOrderInfo($o, 'book');
            if (empty($parseOrder)) {
                continue;
            }
            $parseOrder['order']['order_out_show'] = false;
            $return[] = $parseOrder;
        }
        return $return;
    }

    /**
     * 订单管理获取订单列表
     * @param $params
     * @author: 张涛
     * @date: 2020/11/11
     */
    public function getLists($params)
    {
        $where = $whereOr = [];
        $tag = 'lists';
        $sort = ['sort_time' => 'asc'];
        $where[] = [['o.paid', '=', 1], ['o.order_from', '<>', 1]];
        $completeWhere = [
            ['o.status', 'in', '2,3'],
            ['o.is_handle', '=', 1]
        ];
        $cancelWhere = [
            ['o.status', 'in', '4,5'],
        ];
        $deliveryWhere = [
            ['o.is_pick_in_store', '=', 3],
            ['o.status', '=', 1],
            ['o.is_handle', '=', 1]
        ];
        if (isset($params['status'])) {
            switch ($params['status']) {
                case 'complete';
                    $where = array_merge($where,$completeWhere);
                    break;
                case 'cancal';
                    $where = array_merge($where,$cancelWhere);
                    break;
                case 'delivery';
                    $where = array_merge($where,$deliveryWhere);
                    break;
                default:
                    $whereOr[] = $completeWhere;
                    $whereOr[] = $cancelWhere;
                    $whereOr[] = $deliveryWhere;
                    break;
            }
        }
        if (isset($params['store_id']) && $params['store_id'] > 0) {
            $where[] = ['o.store_id', '=', $params['store_id']];
        }
        if(isset($params['store_id'])&&$params['store_id']>0&&isset($params['pick_addr_ids'])&&$params['pick_addr_ids']){
            $where[] =  ['', 'exp', Db::raw('(o.is_pick_in_store <> 2) or (o.is_pick_in_store = 2 AND FIND_IN_SET(o.pick_id,"'.$params['pick_addr_ids'].'"))')];
        }
        if (isset($params['keyword']) && $params['keyword']) {
            $whereOr = [
                ['o.real_orderid', 'like', $params['keyword'] . '%'],
                ['o.username', 'like', '%' . $params['keyword'] . '%'],
                ['o.userphone', 'like', $params['keyword'] . '%'],
                ['u.phone', 'like', $params['keyword'] . '%'],
                ['o.third_id', 'like', $params['keyword'] . '%']
            ];
            //搜索默认搜索一年
            $where[] = ['o.create_time', '>', strtotime('-1 year')];
            $tag = 'search_order';
        }
        if (isset($params['date']) && $params['date']) {
            $unix = strtotime($params['date']);
            $where[] = ['o.create_time', 'between', [$unix, $unix + 86399]];
        }
        $fields = 'o.*,CASE o.is_pick_in_store WHEN 0 THEN o.expect_pick_time WHEN 1 THEN o.expect_pick_time WHEN 2 THEN o.expect_use_time WHEN 3 THEN o.create_time END AS sort_time';
        if (isset($params['pageSize']) && $params['pageSize'] == 0) {
            $orders = $this->shopOrderMod->alias('o')->leftJoin('user u','u.uid = o.uid')->where($where)->where(function ($query) use ($whereOr) {
                $query->whereOr($whereOr);
            })->field($fields)->order($sort)->select()->toArray();
        } else {
            $orders = $this->shopOrderMod->alias('o')->leftJoin('user u','u.uid = o.uid')->where($where)->where(function ($query) use ($whereOr) {
                $query->whereOr($whereOr);
            })->field($fields)->order($sort)->page($params['page'], $params['pageSize'])->select()->toArray();
        }

        $return = [];
        foreach ($orders as $o) {
            $parseOrder = $this->parseOrderInfo($o, $tag);
            if (empty($parseOrder)) {
                continue;
            }
            $return[] = $parseOrder;
        }
        return $return;
    }

    public function parseOrderInfo($order, $tag = '')
    {
        $shopOrderLogService = new \app\shop\model\service\order\ShopOrderLogService();
        $userService = new UserService();
        $shopService = new MerchantStoreShopService();
        $refundMod = new ShopOrderRefund();
        $tm = time();
        $pickAddTime = 1200;
        $deliverAddTime = 900;

        $buyer = $userService->getUser($order['uid'], 'uid');
        $shop = $shopService->getStoreByStoreId($order['store_id']);
        if (empty($shop)) {
            return [];
        }
        $order['change_address_status'] = SystemOrder::where(['type' => 'shop', 'order_id' => $order['order_id']])->value('change_address_status');
        //获取物流信息
        $express = (new Express())->select()->toArray();
        $expressIndex = array_combine(array_column($express, 'id'), $express);

        //订单基本信息
        $gcjLongLat = (new longLat())->baiduToGcj02($order['lat'], $order['lng']);

        $info = [
            "order_id" => $order['order_id'],
            "real_orderid" => $order['real_orderid'],
            "goods_count" => $order['num'],
            "total_price" => $order['is_pick_in_store'] == 0 ? get_format_number($order['discount_price'] + $order['packing_charge']) : get_format_number($order['price']),
            "packing_charge" => $order['packing_charge'],
            "freight_charge" => $order['freight_charge'],
            "create_time" => date('Y-m-d H:i', $order['create_time']),
            "real_price" => $order['price'],
            "show_expect_income"=>true,
            "expect_income" => get_format_number($order['expect_settlement_money']),
            "refund_price" => 0,
            "username" => $order['username'] ?: ($buyer['nickname']??''),
            "userphone" => $order['userphone'],
            "address" => $order['address'],
            "lat" => $order['lat'],
            "lng" => $order['lng'],
            'gcj_lng' => $gcjLongLat['lng'] ?? $order['lng'],
            'gcj_lat' => $gcjLongLat['lat'] ?? $order['lat'],
            "is_pick_in_store" => $order['is_pick_in_store'],
            "order_from" => $order['order_from'],
            "is_booking" => (bool)$order['is_booking'],
            "expect_use_time" => $order['expect_use_time'],
            "expect_use_show" => true,
            "expect_use_show_time" => date('H:i', $order['expect_use_time']),
            "order_out_show" => true,
            "order_out_show_time" => date('H:i', $order['expect_take_time']),
            "order_out_expire" => $tm > $order['expect_take_time'] ? true : false,
            "fetch_number" => $order['fetch_number'],
            "paid" => $order['paid'],
            "note" => $order['note'] ?: $order['desc'],
            "status" => $order['status'],
            "order_status" => $order['order_status'],
            "is_weight"=>$order["is_weight"],
            "status_txt" => "已支付",
            "express_id" => $order['express_id'],
            "express_name" => isset($expressIndex[$order['express_id']]) ? $expressIndex[$order['express_id']]['name'] : '--',
            "express_code" => isset($expressIndex[$order['express_id']]) ? $expressIndex[$order['express_id']]['code'] : '--',
            "express_number" => $order['express_number'],
            "freight_alias" => $order['is_pick_in_store'] == 0 ? L_('配送费') : $shop['freight_alias'],
            "pack_alias" => $shop['pack_alias'],
            "refund_reason" => "",
            "is_write_real_weight" => false,
			"invoice_head" => $order['invoice_head'] ? : '',	//发票抬头
			"cue_field" => $order['cue_field'] ? unserialize($order['cue_field']) : [],	//自定义填写项
            "change_address_info" => NULL,
        ];
		
		//自定义字段空的去掉
        if ($info['cue_field']) {
			foreach($info['cue_field'] as $cue_k => $cue_v){
				if(empty(trim($cue_v['txt']))){
					unset($info['cue_field'][$cue_k]);
				}
			}
			$info['cue_field'] = array_values($info['cue_field']);
        }else{
			$info['cue_field'] = [];	//老BUG可能会导致 unserialize 返回 false，强制处理成数组
		}

        $isOldOrder = $order['expect_settlement_money'] + $order['expect_system_take'];
        if($order['order_from'] == 6 && $info['expect_income'] <= 0){
            $info['show_expect_income'] = false;
        }else if($isOldOrder <= 0){
            $info['show_expect_income'] = false;
        }
        if ($order['is_weight'] == 1 && $this->isAllWriteRealWeight($order['order_id'])) {
            $info['is_write_real_weight'] = true;
        }

        //$addTime = $order['is_pick_in_store'] == 2 ?$pickAddTime:$deliverAddTime;
        $addTime = 0;
        //如果是预订单送达时间带上日期
        if ($info['is_booking']) {
            $info['expect_use_show_time'] = date('m-d H:i', $order['expect_use_time']);
        } else {
            $info['expect_use_show_time'] = date('H:i', $order['expect_use_time']);
        }
        if (date('Ymd', $order['expect_take_time']) != date('Ymd', $tm)) {
            $info['order_out_show_time'] = date('m-d H:i', $order['expect_take_time']);
        }

        //接单之前不应该显示出单时间
        if ($info['order_status'] < 1 || $order['expect_take_time'] == 0) {
            $info['order_out_show'] = false;
        } 

        //订单商品信息
        $goods = $this->combineGoods($order['order_id']);

        //重算价格
        $goodsId = [];
        $goodsNum = 0;
        $totalPrcie = $info['packing_charge'];
        if ($info['is_pick_in_store'] != 0) {
            $totalPrcie += $info['freight_charge'];
        }
        foreach ($goods as $v) {
            $goodsId[] = $v['goods_id'];
            $goodsNum += $v['num'];
            $totalPrcie += $v['price'];
            foreach ($v['subsidiary_goods'] as $v2) {
                $goodsId[] = $v2['goods_id'];
                $goodsNum += $v2['num'];
                $totalPrcie += $v2['price'];
            }
        }
        $info['goods_type_count'] = count(array_unique($goodsId));
        $info['goods_count'] = $goodsNum;
        $info['total_price'] = get_format_number($totalPrcie);

        //获取申请售后记录
        $refundRecord = $refundMod->getOneRefund(['order_id' => $order['order_id'], 'status' => 0]);
        $refund = [
            'refund_id' => $refundRecord['id'] ?? 0,
            'images'=>[],
            'reason'=>''
        ];
        if ($refundRecord) {
            $info['refund_reason'] = $refundRecord['reason'];
            $refund['reason'] = $refundRecord['reason'];
            $images = array_filter(explode(',', $refundRecord['image']));
            $refund['images'] = array_map(function ($r) {
                return replace_file_domain($r);
            }, $images);
        }

        //订单状态展示
        if ($order['is_handle'] == 1) {
            if ($order['order_status'] == 1) {
                if ($order['is_weight'] == 1 && !$this->isAllWriteRealWeight($order['order_id'])) {
                    $info['status_txt'] = L_('称重商品待录入重量');
                } else if ($order['is_pick_in_store'] == 3 && empty($order['express_number'])) {
                    $info['status_txt'] = L_('待发货');
                } else if ($order['is_pick_in_store'] == 3 && $order['express_number']) {
                    $info['status_txt'] = L_('已发货');
                } else if ($order['is_pick_in_store'] == 2) {
                    $info['status_txt'] = L_('待自取');
                } else if (in_array($order['is_pick_in_store'], [0, 1])) {
                    $info['status_txt'] = L_('待骑手接单');
                } else {
                    $info['status_txt'] = L_('已接单');
                }
            }
            if ($order['order_status'] == 2) {
                $info['status_txt'] = L_('骑手已接单');
            }
            if ($order['order_status'] == 3) {
                $info['status_txt'] = L_('骑手已到店');
            }
            if ($order['order_status'] == 4) {
                $info['status_txt'] = L_('骑手配送中');
            }
            if(!empty($order['robot_delivery_order_id'])){
                $info['status_txt'] = str_replace( L_('骑手'), L_('机器人'),$info['status_txt']);
            }
        }
        if (in_array($order['status'], [2, 3])) {
            $info['status_txt'] = L_('已完成');
            if ($refundRecord) {
                $nums = array_sum(array_column($goods, 'num'));
                $refundNums = array_sum(array_column($goods, 'refund_num'));
                if ($nums == $refundNums) {
                    $info['status_txt'] = L_('全部退款');
                } else {
                    $info['status_txt'] = L_('部分退款');
                }
            }
        }
        if (in_array($order['status'], [4])) {
            $info['status_txt'] = L_('全部退款');
        }
        if (in_array($order['status'], [5])) {
            $info['status_txt'] = L_('已取消');
        }

        //获取配送员
        $deliverUser = unserialize($order['deliver_info']);
        $deliverUser = $deliverUser ?: [];
        if ($deliverUser) {
            $statusArr = $shopOrderLogService->getLogByOrderId($order['order_id'], $order['is_pick_in_store'], 'H:i');
            if (isset($statusArr[0])) {
                $deliverUser['current_status'] = $statusArr[0]['time'] . $statusArr[0]['status'];
            } else {
                $deliverUser['current_status'] = '';
            }
            $deliverUser['is_show'] = true;
            $deliverUser['note'] = ($order['is_right_now'] == 1 ? '立即送达' : '期望送达') . '(' . date('H:i', $order['expect_use_time'] + $addTime) . '前送达)';
            $deliverUser['status_arr'] = $statusArr;
        } else {
            $deliverUser = ["is_show" => false];
        }

        //收货人
        $virtualPhone = [];
        if ($order['virtual_phone']) {
            $splitArr = array_filter(explode(';', $order['virtual_phone']));
            foreach ($splitArr as $v) {
                $split = explode(',', $v);
                if (count($split) == 2) {
                    $virtualPhone[] = ['phone' => $split[0], 'transfer' => $split[1]];
                }
            }
        }
        $isOpenPrivacyNumber = $virtualPhone ? true : false;
        $consignee = [
            "username" => $info['username'],
            "last_four_number" => $isOpenPrivacyNumber ? substr($info['userphone'], -4) : '',
            'open_privacy_number' => $isOpenPrivacyNumber,
            'phone' => []
        ];

        if ($isOpenPrivacyNumber) {
            isset($virtualPhone[0]) && $consignee['phone'][] = ['title' => L_('收货人电话'), 'show_phone' => $virtualPhone[0]['phone'] . L_('转') . $virtualPhone[0]['transfer'], 'phone' => $virtualPhone[0]['phone']];
            isset($virtualPhone[1]) && $consignee['phone'][] = ['title' => L_('注册电话'), 'show_phone' => $virtualPhone[1]['phone'] . L_('转') . $virtualPhone[1]['transfer'], 'phone' => $virtualPhone[1]['phone']];
        } else {
            $consignee['phone'][] = ['title' => L_('收货人电话'), 'show_phone' => $info['userphone'], 'phone' => $info['userphone']];
            if ($buyer && $buyer['phone'] && $buyer['phone'] != $info['userphone']) {
                $consignee['phone'][] = ['title' => L_('注册电话'), 'show_phone' => $buyer['phone'], 'phone' => $buyer['phone']];
            }
        }

        //优惠信息
        $expendDetails = $this->computeMerchantExpendByOrderId($order['order_id']);
        $expendSum = $expendDetails ? array_sum(array_column($expendDetails, 'amount')) : 0;
        $merchantActivityExpend = [
            "is_show" => $expendDetails ? true : false,
            "title" => "商家活动支出",
            "amount" => get_format_number($expendSum),
            "symbol" => "-",
            "detail" => $expendDetails
        ];
        $platServiceFee = [
            "is_show" => $isOldOrder <= 0 ?false:true,
            "symbol" => "-",
            "title" => cfg('system_take_alias_name') ? cfg('system_take_alias_name') : "平台服务费",
            "amount" => get_format_number($order['expect_system_take'])
        ];
        $realPayMoney = get_format_number($order['price']-$order['card_price']-$order['coupon_price']);
        $payDetail = [
            "is_show" => true,
            "title" => "顾客实际支付",
            "amount" => $realPayMoney > 0 ? $realPayMoney : 0,
            "symbol" => "",
            "detail" => [
                [
                    'title' => '餐品原价(含打包费)',
                    "symbol" => "",
                    "amount" => get_format_number($order['goods_price'] + $order['packing_charge'])
                ],
                [
                    'title' => '配送费(优惠前)',
                    "symbol" => "",
                    "amount" => get_format_number($order['freight_charge_original']+$order['other_money'])
                ],
                [
                    'title' => L_('顾客享受优惠'),
                    "symbol" => "-",
                    "amount" => get_format_number($order['total_price'] - $order['price'] + $order['card_price'] + $order['coupon_price'] + ($order['freight_charge_original'] - $order['freight_charge']))
                ]
            ]
        ];

        $refundSuccessLists = $refundMod->getAllRefundingRecords($order['order_id']);
        $refundDetail = ["is_show" => $refundSuccessLists ? true : false,];
        if ($refundSuccessLists) {
            $totalPrice = get_format_number(array_sum(array_column($refundSuccessLists, 'price')));
            $totalPayPrice = $totalDiscount = 0;
            foreach ($goods as $g) {
                if ($g['refund_num'] > 0) {
                    $totalPayPrice += $g['refund_num'] * $g['old_price'];
                    $totalDiscount += $g['refund_num'] * ($g['old_price'] - $g['pay_price']);
                }
            }
            $refundDetail["title"] = "顾客收到退款金额";
            $refundDetail["amount"] = get_format_number($totalPrice);
            $refundDetail["symbol"] = "";
            $refundDetail["detail"] = [
                ['title' => '申请退款餐品原价（不含打包费）', "symbol" => "", "amount" => get_format_number($totalPayPrice)],
                ['title' => '申请退款餐品优惠金额（按照优惠活动等比计算得出）', "symbol" => "-", "amount" => get_format_number($totalPayPrice-$totalPrice)]
            ];
        }

        //按钮
        $btnBar = [
            "take_order" => false,
            "cancel_order" => false,
            "print_order" => false,
            "delivery_order" => false,
            "agree_refund" => false,
            "disagree_refund" => false,
            "handle_book_order" => false,
            "write_weight" => false,
            "confirm_consume" => false,
            "change_address_status" => false,
        ];
        if(
            customization('property_robot_jsdhwy') && 
            empty($info['order_status']) && 
            empty($info['is_pick_in_store']) && 
            empty($info['status']) &&
            empty($order['robot_delivery_order_id'])
        ){//机器人配送
            $btnBar['robot_delivery'] = true;
        }
    
        if(customization('property_robot_jsdhwy') && !empty($info['robot_delivery_order_id'])){
            $btnBar['robot_arrive_start_address'] = false;
            $btnBar['robot_start_delivery'] = false;
            $btnBar['robot_delivery'] = false;
            $btnBar['robot_arrive_end_address'] = false;
            
            $robotOrder = ThirdRobotDeliveryOrder::where('id', $order['robot_delivery_order_id'])->find();
            if(!empty($robotOrder)){
                if(in_array(
                    $robotOrder['status'],
                    [ThirdRobotDeliveryOrder::ORDER_STATUS_20, ThirdRobotDeliveryOrder::ORDER_STATUS_30]
                )){
                    $btnBar['robot_arrive_start_address'] = true;
                }

                if(in_array(
                    $robotOrder['status'],
                    [ThirdRobotDeliveryOrder::ORDER_STATUS_40]
                )){
                    $btnBar['robot_start_delivery'] = true;
                }

                if(in_array(
                    $robotOrder['status'],
                    [ThirdRobotDeliveryOrder::ORDER_STATUS_50]
                )){
                    $btnBar['robot_arrive_end_address'] = true;
                }
            }
        }
        if(!empty($order['change_address_status'])){
            $order['change_address_status'] == 1 && $btnBar['change_address_status'] = true;
            $addressRecord = OrderAddressChangeRecord::where(['type' => 'shop', 'order_id' => $order['order_id']])->find();
            $addressRecord['distance'] = (getDistance($addressRecord['lat'], $addressRecord['lng'],$addressRecord['change_lat'], $addressRecord['change_lng'])/1000).'km';
            $addressRecord['change_address_status'] = $order['change_address_status'];
            $info['change_address_info'] = $addressRecord;
        }
        if (in_array($order['status'], [4, 5])) {
            //订单取消
            $btnBar['print_order'] = true;
        } else if ($order['is_apply_refund'] == 1) {
            //申请退款
            $btnBar['agree_refund'] = true;
            $btnBar['disagree_refund'] = true;
        } else if(in_array($order['status'], [2, 3])){
            //订单已完成、已评价
            $btnBar['print_order'] = true;
        }else {
            if ($order['order_status'] == 0 && $order['status'] == 0) {
                $btnBar['take_order'] = true;
                $btnBar['cancel_order'] = true;
            }
            if ($order['is_apply_refund'] == 0) {
                $btnBar['cancel_order'] = true;
            }
            if ($order['order_status'] >= 1) {
                $btnBar['print_order'] = true;
            }
            if ($order['is_pick_in_store'] == 3 && $order['order_status'] < 5 && $order['order_status'] > 0) {
                $btnBar['delivery_order'] = true;
            }
            if ($tag != 'book' && $order['is_handle'] == 1  && $order['order_status'] >= 1 && $order['order_status'] < 4 && !in_array($order['is_pick_in_store'], [0, 1, 3])) {
                $btnBar['confirm_consume'] = true;
            }
            if ($order['is_handle'] == 1 && $order['is_weight'] == 1 && $order['order_status'] > 0 && $order['order_status'] < 5) {
                $btnBar['write_weight'] = true;
            }
            if ($order['is_handle'] == 0 && $order['order_status'] == 1 && !in_array($tag, ['new_order'])) {
                $btnBar['handle_book_order'] = true;
            }
        }
        if(customization('internal_membership_card')){
            $miaomaiyunErpStoreid = MerchantStore::where(['store_id' => $order['store_id']])->value('miaomaiyun_erp_storeid');
            if($miaomaiyunErpStoreid){
                $btnBar = Arr::only($btnBar, ['print_order']);
            }
        }
        $good_lists = [];
        foreach ($goods as $item){
            $index = $item['packname']?:0;
            $good_lists[$index]['name'] = $item['packname'];
            $good_lists[$index]['list'][] = $item;
        }

        $good_lists = array_values($good_lists);

        return [
            'order_id' => $order['order_id'],
            'order' => $info,
            'goods' => $good_lists,
            'deliver_user' => $deliverUser ?: new \stdClass(),
            'consignee' => $consignee,
            'refund_info'=>$refund,
            'merchant_expend_money' => $merchantActivityExpend,
            'plat_service_money' => $platServiceFee,
            'real_pay_money' => $payDetail,
            'refund_money' => $refundDetail,
            'btn_bar' => $btnBar
        ];
    }


    /**
     * 店员接单
     * @return void
     * @author: 张涛
     * @date: 2020/11/09
     */
    public function takeOrder($orderId, $staffId, $pick_addr_ids='')
    {
        $staff = (new StoreStaffService)->getStaffInfoById($staffId);
        $order = $this->getOrderInfo(['order_id' => $orderId,'store_id'=>$staff['store_id']]);

        if (empty($staff)) {
            throw new Exception(L_('店员不存在'));
        }
        if (empty($order)) {
            throw new Exception(L_('订单不存在'));
        }
        if ($order['status'] > 0) {
            throw new Exception('该单已接，不要重复接单');
        }
        if ($order['paid'] == 0) {
            throw new Exception(L_('未付款的订单只能进行取消操作'));
        }
        if($order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'],$pick_addr_ids)){
            throw new Exception('没有分配当前自提点权限！');
        }
        $data = [
            'status' => 1,
            'last_staff' => $staff['name'],
            'is_taking' => 1,
            'order_status' => 1,
            'last_time' => time()
        ];
        Db::startTrans();
        try {
            $store = (new MerchantStoreService)->getStoreByStoreId($order['store_id']);
            $phones = $store['phone'] ? explode(' ', $store['phone']) : [];
            if ($order['is_pick_in_store'] == 3) {
                //快递配送
                unset($data['status']);
                $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], $data);
                if (!$result) {
                    throw new Exception('更新订单信息失败！');
                }
                (new SystemOrderService)->sendOrder('shop', $orderId);
                $log = ['order_id' => $orderId, 'status' => 2, 'name' => $staff['name'], 'phone' => $phones[0] ?? '', 'note' => '订单已确认，商家备货中'];
                invoke_cms_model('Shop_order_log/add_log', [$log], true);
            } else {
                $this->cancelChangeAddress($orderId, $order['mer_id'], $order['is_pick_in_store'], 1);
                //其他配送
                $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], $data);

                if (!$result) {
                    throw new Exception('更新订单信息失败！');
                }
                if ($order['is_pick_in_store'] != 2) {
                    if ($order['is_booking'] == 1 && $order['is_handle'] == 0) {
                        //预订单待处理则不推送给配送员
                    } else if ($order['is_weight'] == 1 && !$this->isAllWriteRealWeight($order['order_id'])) {
                        //称重订单商品没有录入商品重量不推送给配送员
                    }else {
                        $result = invoke_cms_model('Deliver_supply/saveOrder', [$orderId, $store]);
                        if (isset($result['retval']['error_code']) && $result['retval']['error_code']) {
                            throw new Exception($result['retval']['msg']);
                        }
                    }
                }
                (new SystemOrderService)->sendOrder('shop', $orderId);
                $log = ['order_id' => $orderId, 'status' => 2, 'name' => $staff['name'], 'phone' => $phones[0] ?? ''];
                invoke_cms_model('Shop_order_log/add_log', [$log], true);
            }
            (new UserNoticeService())->addNotice([
                'type'=>0,
                'business'=>'shop',
                'order_id'=>$orderId,
                'mer_id'=>$order['mer_id'],
                'store_id'=>$order['store_id'],
                'uid'=>$order['uid'],
                'title'=>'外卖商家已接单',
                'content'=>'商家已接单，商品将尽快为你送达'
            ]);

            //标签打印
            invoke_cms_model('Shop_order/labelPrint', ['orderId' => $orderId], true);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 验证店员是否有操作自提订单权限
     * @param $pick_id
     * @param $pick_addr_ids
     * @return bool true-有权限，false-无权限
     */
    public function checkPickRule($pick_id,$pick_addr_ids){
        $data = true;
        //验证是否有提交权限
        if($pick_addr_ids){
            $pick_addr_ids = explode(',',$pick_addr_ids);
            if(!in_array($pick_id,$pick_addr_ids)){
                $data = false;
            }
        }
        return $data;
    }

    /**
     * 取消订单
     * @return void
     * @author: 张涛
     * @date: 2020/11/09
     */
    public function cancelOrder($orderId, $staffId, $pick_addr_ids='')
    {
        $staff = (new StoreStaffService)->getStaffInfoById($staffId);
        $order = $this->getOrderInfo(['order_id' => $orderId,'store_id'=>$staff['store_id']]);

        if (empty($staff)) {
            throw new Exception(L_('店员不存在'));
        }
        if (empty($order)) {
            throw new Exception(L_('订单不存在'));
        }
        if (cfg('open_staff_power') && $staff['type'] == 0) {
            throw new Exception('您当前身份为【店小二】，没有权限，请联系管理员！');
        }
        if ($order['status'] == 2) {
            throw new Exception('该单已确认消费，不能取消订单');
        }
        if ($order['status'] == 4 || $order['status'] == 5) {
            throw new Exception('订单已取消，不能再做其他操作。');
        }
        if ($order['is_refund']) {
            throw new Exception('用户正在退款中~！');
        }

        if($order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'],$pick_addr_ids)){
            throw new Exception('没有分配当前自提点权限！');
        }

        $data = [
            'status' => 5,
            'last_staff' => $staff['name'],
            'cancel_type' => 4,  //取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）
            'last_time' => time()
        ];

        $store = (new MerchantStoreService)->getStoreByStoreId($order['store_id']);
        $phones = $store['phone'] ? explode(' ', $store['phone']) : [];
        $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], $data);
        if (!$result) {
            throw new Exception('更新订单信息失败！');
        }

        try {
            $log = ['order_id' => $orderId, 'status' => 10, 'name' => $staff['name'], 'phone' => $phones[0] ?? '', 'note' => L_('店员把此单修改成已取消')];
            invoke_cms_model('Shop_order_log/add_log', [$log], true);

            (new SystemOrderService)->cancelOrder('shop', $orderId);
            invoke_cms_model('Shop_order/check_refund', [$order, $staff], true);

            if ($order['is_pick_in_store'] == 3) {
                //快递配送

            } else {
                //其他配送
                $supplyMod = new DeliverSupply();
                $supply = $supplyMod->where('order_id', $orderId)->where('item', 2)->findOrEmpty()->toArray();
                if ($supply) {
                    $supplyMod->where('supply_id', $supply['supply_id'])->update(['status' => 0, 'deliver_status' => 0]);
                }

                if ($store['dada_shop_id']) {
                    //达达配送
                    $dada = new \express\Dada();
                    $dadaData = array();
                    $dadaData['order_id'] = $order['real_orderid'];//
                    $dadaData['cancel_reason_id'] = 4;
                    $dadaData['cancel_reason'] = L_('顾客取消订单');
                    $dada->formalCancel($dadaData);
                }
                if ($order['keloop_trade_no'] != '') {
                    //快跑者
                    $keloop = new  \express\Keloop();
                    $Data = array();
                    $Data['trade_no'] = $order['keloop_trade_no'];
                    $Data['reason'] = '顾客取消订单';
                    $result = $keloop->cancelOrder($Data);
                    if ($result['code'] != 200) {
                        throw new Exception('快跑取消失败:' . $result['message']);
                    }
                }
                if ($order['sf_order_id'] != '') {
                    //顺丰配送
                    $merchantStore = (new \app\merchant\model\service\MerchantStoreService())->getOne(['store_id' => $order['store_id']]);
                    $sfData = [
                        'order_id' => $order['sf_order_id'],
                        'order_type' => 1,
                        'push_time' => time()
                    ];
                    $shunfeng = new \express\Shunfeng($merchantStore);
                    $result = $shunfeng->cancelorder($sfData);
                    if ($result['error_code'] != 0) {
                        throw new Exception('顺丰配送取消失败:' . $result['message']);
                    }
                }
                //饿了么蜂鸟配送取消订单
                if ($order['is_ele_delivery'] == 1) {
                    $merchantStore = (new \app\merchant\model\service\MerchantStoreService())->getOne(['store_id' => $order['store_id']]);
                    $ele = new \express\EleDeliveryNew();
                    $ele->setBusinessData('partner_order_code', $order['real_orderid']);
                    //获取可取消原因
                    $reasonList = $ele->getCancelReasonList();
                    //预取消订单
                    $ele->setBusinessData('order_cancel_code', $reasonList[0]['order_cancel_code'] ?? 0);
                    $ele->preCancelOrder();
                    //正式取消
                    $ele->setBusinessData('order_cancel_role', 1);
                    $ele->setBusinessData('order_cancel_other_reason', '店员取消订单');
                    $ele->cancelOrder();
                }
                // 配送员推送模板消息
                if ($supply && $supply['uid'] > 0) {
                    $deliverUser = (new DeliverUser())->where('uid', '=', $supply['uid'])->where('status', '<>', 4)->findOrEmpty()->toArray();
                    if ($deliverUser && $deliverUser['openid']) {
                        $data = [
                            'href' => '',
                            'wecha_id' => $deliverUser['openid'],
                            'first' => L_('用户取消X1订单！', cfg('shop_alias_name')),
                            'OrderSn' => $order['real_orderid'],
                            'OrderStatus' => L_('已取消'),
                            'remark' => date('Y-m-d H:i:s')
                        ];
                        invoke_cms_model('Shop_order/sendTempMsg', ['TM00017', $data]);
                    }
                }

                //销量回滚reduce_stock_type
                if (($order['paid'] == 1 || $order['reduce_stock_type'] == 1) && $order['is_rollback'] == 0) {
                    $details = $this->getGoodsByOrderId($orderId);
                    foreach ($details as $menu) {
                        //修改库存
                        invoke_cms_model('Shop_goods/update_stock', [$menu, 1]);
                    }
                    (new ShopOrder())->updateOrder(['order_id' => $orderId], ['is_rollback' => 1]);
                }
            }
            (new UserNoticeService())->addNotice([
                'type'=>0,
                'business'=>'shop',
                'order_id'=>$orderId,
                'mer_id'=>$order['mer_id'],
                'store_id'=>$order['store_id'],
                'uid'=>$order['uid'],
                'title'=>'商家手动取消订单',
                'content'=>'商家手动取消订单，钱已退回，查看订单'
            ]);
        } catch (\Exception $e) {
            (new ShopOrder())->updateOrder(['order_id' => $orderId], ['status' => $order['status'], 'last_staff' => $order['status'], 'cancel_type' => $order['cancel_type'], 'last_time' => $order['last_time']]);
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 快递配送发货
     * @param $orderId
     * @param $staffId
     * @author: 张涛
     * @date: 2020/11/12
     */
    public function deliveryOrder($orderId, $staffId, $expressInfo)
    {
        $staff = (new StoreStaffService)->getStaffInfoById($staffId);
        $order = $this->getOrderInfo(['order_id' => $orderId]);

        if (empty($staff)) {
            throw new Exception(L_('店员不存在'));
        }
        if (empty($order)) {
            throw new Exception(L_('订单不存在'));
        }
        if ($order['is_pick_in_store'] != 3) {
            throw new Exception(L_('非快递配送无需发货'));
        }
        if (!isset($expressInfo['express_id']) || $expressInfo['express_id'] < 1) {
            throw new Exception(L_('请选择物流公司'));
        }
        if (!isset($expressInfo['express_number']) || empty($expressInfo['express_number'])) {
            throw new Exception(L_('请填写物流单号'));
        }
        $data = [
            'status' => 1,
            'last_staff' => $staff['name'],
            'express_id' => $expressInfo['express_id'],
            'express_number' => $expressInfo['express_number'],
            'last_time' => time()
        ];

        Db::startTrans();
        try {
            $store = (new MerchantStoreService)->getStoreByStoreId($order['store_id']);
            $phones = $store['phone'] ? explode(' ', $store['phone']) : [];
            $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], $data);
            if (!$result) {
                throw new Exception(L_('更新订单信息失败！'));
            }

            (new SystemOrderService)->sendOrder('shop', $orderId);

            //记录日志
            $log = ['order_id' => $orderId, 'status' => 2, 'name' => $staff['name'], 'phone' => $phones[0] ?? '', 'note' => $data['express_id'] . '/' . $data['express_number']];
            if ($order['express_id']) {
                $log['note'] = 'update';
            }
            if($order['status'] == 0){
                (new UserNoticeService())->addNotice([
                    'type'=>0,
                    'business'=>'shop',
                    'order_id'=>$orderId,
                    'mer_id'=>$order['mer_id'],
                    'store_id'=>$order['store_id'],
                    'uid'=>$order['uid'],
                    'title'=>'商品已发货',
                    'content'=>'您的商品已经发货，商品将尽快为你送达'
                ]);
            }
            invoke_cms_model('Shop_order_log/add_log', [$log, $order['is_pick_in_store']], true);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        };
    }


    /**
     * 录入商品重量
     * @throws Exception
     * @author: 张涛
     * @date: 2020/11/12
     */
    public function writeWeight($orderId, $staffId, $weightInfo)
    {
        $staff = (new StoreStaffService)->getStaffInfoById($staffId);
        $order = $this->getOrderInfo(['order_id' => $orderId]);

        if (empty($staff)) {
            throw new Exception(L_('店员不存在'));
        }
        if (empty($order)) {
            throw new Exception(L_('订单不存在'));
        }
        if ($order['is_weight'] != 1) {
            throw new Exception(L_('非称重订单无需录入商品重量'));
        }
        if (!isset($weightInfo['detail_id']) || empty($weightInfo['detail_id'])) {
            throw new Exception(L_('请选择录入重量商品'));
        }
        if (!isset($weightInfo['real_weight']) || empty($weightInfo['real_weight'])) {
            throw new Exception(L_('请录入商品重量'));
        }

        $details = $weightInfo['detail_id'];
        $weights = $weightInfo['real_weight'];
        Db::startTrans();
        try {
            $shopOrderDetailMod = new ShopOrderDetail();
            $details = $shopOrderDetailMod->where(['order_id' => $orderId, 'sell_type' => 2])->select()->toArray();
            if (empty($details)) {
                throw new Exception(L_('该订单没有称重商品无需录入商品重量'));
            }
            foreach ($details as $k => $d) {
                if (isset($details[$k]) && isset($weights[$k])) {
                    $shopOrderDetailMod->where(['id' => $details[$k], 'order_id' => $orderId, 'sell_type' => 2])->update(['real_weight' => $weights[$k]]);
                }
            }

            if ($this->isAllWriteRealWeight($orderId)) {
                $store = (new MerchantStoreService)->getStoreByStoreId($order['store_id']);
                $result = invoke_cms_model('Deliver_supply/saveOrder', [$orderId, $store]);
                if (isset($result['retval']['error_code']) && $result['retval']['error_code']) {
                    throw new Exception($result['retval']['msg']);
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        };
    }


    /**
     * 退款
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function replyRefund($orderId, $staffId, $refundInfo, $pick_addr_ids='')
    {
        $staff = (new StoreStaffService)->getStaffInfoById($staffId);
        $order = $this->getOrderInfo(['order_id' => $orderId,'store_id'=>$staff['store_id']]);

        if (empty($staff)) {
            throw new Exception(L_('店员不存在'));
        }
        if (empty($order)) {
            throw new Exception(L_('订单不存在'));
        }
        if (!isset($refundInfo['type']) || !in_array($refundInfo['type'], ['agree', 'disagree'])) {
            throw new Exception(L_('同意/拒绝参数有误'));
        }
        if($order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'],$pick_addr_ids)){
            throw new Exception('没有分配当前自提点权限！');
        }
        $refundId = $refundInfo['refund_id'] ?? 0;
        $type = $refundInfo['type'];
        $replyContent = $refundInfo['reply_content'] ?? '';

        $refund = (new ShopOrderRefund())->getOneRefund([['status', '<>', 4], ['order_id', '=', $orderId], ['id', '=', $refundId]]);
        if (empty($refund)) {
            throw new Exception(L_('订单的退货信息错误'));
        }
        if ($refund['status'] != 0) {
            throw new Exception(L_('当前的退货信息无需修改'));
        }

        //TODO  退款暂时先走以前的逻辑，这里涉及各种支付渠道退款，迁移复杂,暂时通过curl请求旧版接口
        include_once app()->getRootPath() . '../cms/Lib/ORG/ticket.class.php';
        $param = [
            'order_id' => $orderId,
            'refund_id' => $refundInfo['refund_id'],
            'type' => $refundInfo['type'],
            'reply_content' => $refundInfo['reply_content'],
            'Device-Id' => $refundInfo['Device-Id'] ?? '',
        ];
        $oldTicekt = \Ticket::create($staffId, $param['Device-Id'], true);
        $param['ticket'] = $oldTicekt['ticket'] ?? '';
        $api = cfg('site_url') . '/appapi.php?c=Storestaff&a=replyRefund';
        $result = \net\Http::curlPostOwnWithHeader($api, $param, [], 30);
        $result = json_decode($result, true);
        if (isset($result['errorCode']) && $result['errorCode'] == 0) {
            return true;
        } else {
            throw new Exception($result['errorMsg'] ?? L_('退款失败'));
        }
    }


    /**
     * 退款
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function handleBookOrder($orderId, $staffId, $pick_addr_ids)
    {
        $staff = (new StoreStaffService)->getStaffInfoById($staffId);
        $order = $this->getOrderInfo(['order_id' => $orderId,'store_id'=>$staff['store_id']]);

        if (empty($staff)) {
            throw new Exception(L_('店员不存在'));
        }
        if (empty($order)) {
            throw new Exception(L_('订单不存在'));
        }
        if ($order['is_booking'] != 1) {
            throw new Exception(L_('非预订单无需处理'));
        }
        if ($order['is_handle'] != 0) {
            throw new Exception(L_('该订单已处理，无需处理'));
        }
        if($order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'],$pick_addr_ids)){
            throw new Exception('没有分配当前自提点权限！');
        }

        if (!($order['is_weight'] == 1 && !$this->isAllWriteRealWeight($order['order_id']))) {
            $store = (new MerchantStoreService)->getStoreByStoreId($order['store_id']);
            $result = invoke_cms_model('Deliver_supply/saveOrder', [$orderId, $store]);
            if (isset($result['retval']['error_code']) && $result['retval']['error_code']) {
                throw new Exception($result['retval']['msg']);
            }
        }

        (new ShopOrder())->updateOrder(['order_id' => $orderId], ['is_handle' => 1]);
        return true;
    }


    /**
     * 确认消费
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function confirmConsume($orderId, $staffId, $pick_addr_ids='')
    {
        $staff = (new StoreStaffService)->getStaffInfoById($staffId);
        $order = $this->getOrderInfo(['order_id' => $orderId,'store_id'=>$staff['store_id']]);
        $tm = time();

        if (empty($staff)) {
            throw new Exception(L_('店员不存在'));
        }
        if (empty($order)) {
            throw new Exception(L_('订单不存在'));
        }
        if (cfg('open_staff_power') && $staff['type'] == 0) {
            throw new Exception(L_('您当前身份为【店小二】，没有权限，请联系管理员！'));
        }
        if ($order['status'] == 2) {
            throw new Exception(L_('该单已确认消费'));
        }
        if (in_array($order['status'], [4, 5])) {
            throw new Exception('该单已退款');
        }

        if($order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'],$pick_addr_ids)){
            throw new Exception('没有分配当前自提点权限！');
        }

        $data = [
            'status' => 2,
            'last_staff' => $staff['name'],
            'last_time' => $tm,
            'use_time' => $tm
        ];
        try {
            //赠送满减送优惠卷
            $orderDetail = $this->getGoodsByOrderId($orderId);
            if ($orderDetail) {
                foreach ($orderDetail as $k => $v) {
                    if (isset($v['give_coupon_id']) && $v['give_coupon_id'] > 0) {
                        $couponData['coupon_id'] = $v['give_coupon_id'];
                        $couponData['uid'] = $order['uid'];
                        $couponData['num'] = 1;
                        $couponData['receive_time'] = time();
                        $couponData['is_use'] = 0;
                        \think\facade\Db::name('card_new_coupon_hadpull')->insert($couponData);
                    }
                }
            }

            $store = (new MerchantStoreService)->getStoreByStoreId($order['store_id']);
            $phones = $store['phone'] ? explode(' ', $store['phone']) : [];

            if ($order['is_pick_in_store'] == 3) {
                $data['order_status'] = 6;//配送完成
                $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], $data);
                if (!$result) {
                    throw new Exception(L_('更新订单信息失败！'));
                }

                if ($order['status'] != 2 && $order['status'] != 3) {
                    invoke_cms_model('Shop_order/shopNotice', [$order, false], true);
                    $log = ['log_from' => 'storestaff', 'order_id' => $orderId, 'status' => 7, 'name' => $staff['name'], 'phone' => $phones[0] ?? '', 'note' => ''];
                    invoke_cms_model('Shop_order_log/add_log', [$log], true);
                }
            } else {
                $supplyMod = new DeliverSupply();
                $supply = $supplyMod->where(['order_id' => $orderId, 'item' => 2])->findOrEmpty()->toArray();


                if ($supply) {
                    //平台配送，当配送员接单后店员就不能把订单修改成已消费状态
                    if ($order['is_pick_in_store'] == 0 && $supply && $supply['uid']) {
                        throw new Exception(L_('您不能将该订单改成已消费状态。'));
                    }

                    if ($supply['status'] < 2) {
                        $supplyMod->where('supply_id', $supply['supply_id'])->delete();
                    } else {
                        $supplyMod->where('supply_id', $supply['supply_id'])->update(['status' => 5]);
                    }
                }
                $data['order_status'] = 6;//配送完成
                $result = (new ShopOrder())->updateOrder(['order_id' => $orderId], $data);
                if (!$result) {
                    throw new Exception(L_('更新订单信息失败！'));
                }

                //检查含有称重商品的订单，是否需要部分退款
                if (cfg('is_open_shop_weigh') == 1 && $order['is_weight'] == 1) {
                    invoke_cms_model('Shop_order/checkWeightOrder', [$order], true);
                }

                //当订单由未消费修改成已消费时做的通知
                if ($order['status'] != 2 && $order['status'] != 3) {
                    invoke_cms_model('Shop_order/shopNotice', [$order, false], true);

                    \think\facade\Db::name('pick_order')->where(['store_id' => $order['store_id'], 'order_id' => $order['order_id']])->update(['status' => 4]);
                    $log = ['order_id' => $orderId, 'status' => 7, 'name' => $staff['name'], 'phone' => $phones[0] ?? '', 'note' => ''];
                    invoke_cms_model('Shop_order_log/add_log', [$log], true);
                }
            }
            if($order['is_pick_in_store'] == 2){
                (new UserNoticeService())->addNotice([
                    'type'=>0,
                    'business'=>'shop',
                    'order_id'=>$orderId,
                    'mer_id'=>$order['mer_id'],
                    'store_id'=>$order['store_id'],
                    'uid'=>$order['uid'],
                    'title'=>'核销成功，已经领取商品',
                    'content'=>'您已经领取店铺自提商品，请核对订单。'
                ]);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取外卖新订单数量
     * @param $staffId
     * @throws Exception
     * @author: 张涛
     * @date: 2020/11/19
     */
    public function getShopNewOrderCount($staffId)
    {
        $num = 0;
        $staff = (new StoreStaffService)->getStaffInfoById($staffId);
        if (empty($staff)) {
            throw new Exception(L_('店员不存在'));
        }
        $ext = \think\facade\Db::name('merchant_store_shop_ext')->where(['store_id' => $staff['store_id']])->find();
        if ($ext) {
            $num = $ext['shop_new_order_count'];
            \think\facade\Db::name('merchant_store_shop_ext')->where(['store_id' => $staff['store_id']])->update(['shop_new_order_count' => 0]);
        }
        return $num;
    }

    /**
     * 获取配送订单配送信息
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function expressInfo($expressCode, $expressNumber, $phone)
    {
        $rs = (new \app\common\model\service\SingleFaceService)->getSynQuery($expressNumber, $expressCode, $phone);
        $data = $rs['data']['data'] ?? [];
        $points = [];
        foreach ($data as $v) {
            $points[] = [
                'time' => $v['time'],
                'status' => $v['context']
            ];
        }
        return $points;
    }


    /**
     * 根据订单id计算商家活动支出
     * 注意：改动此方法记得同步调整D('Shop_order')->computeMerchantExpendByOrderId
     * @param $orderId
     * @author: 张涛
     * @date: 2020/11/26
     */
    public function computeMerchantExpendByOrderId($orderId, $withIndex = false)
    {
        $order = $this->shopOrderMod->where('order_id', $orderId)->findOrEmpty()->toArray();
        $discount = [];

        $discountDetail = unserialize($order['discount_detail']);
        if ($discountDetail && isset($discountDetail['minus']) && $discountDetail['minus']) {
            $merMinus = $discountDetail['minus'];
            if ($merMinus['money'] > 0) {
                $title = sprintf('店铺优惠满%s减%s', get_format_number($merMinus['money']), get_format_number($merMinus['minus']));
            } else {
                $title = sprintf('店铺优惠减%s', get_format_number($merMinus['minus']));
            }
            $discount['merchant_reduce'] = [
                'title' => $title,
                'symbol' => '-',
                'amount' => get_format_number($merMinus['minus'])
            ];
        }
        if ($discountDetail && isset($discountDetail['newuser']) && $discountDetail['newuser']) {
            $merNewuser = $discountDetail['newuser'];
            if ($merNewuser['money'] > 0) {
                $title = sprintf('店铺首单满%s减%s', get_format_number($merNewuser['money']), get_format_number($merNewuser['minus']));
            } else {
                $title = sprintf('店铺首单减%s', get_format_number($merNewuser['minus']));
            }
            $discount['merchant_first_order_reduce'] = [
                'title' => $title,
                'symbol' => '-',
                'amount' => get_format_number($merNewuser['minus'])
            ];
        }
        if ($order['card_discount'] > 0 && $order['card_discount'] < 10) {
            $cardDdiscountMoney = (($order['discount_price'] + $order['packing_charge'] + $order['other_money']) - ($order['merchant_reduce'] + $order['balance_reduce'])) * (1 - $order['card_discount'] * 0.1);
            $discount['merchant_card'] = [
                'title' => sprintf('商家会员卡%s折优惠', $order['card_discount']),
                'symbol' => '-',
                'amount' => $cardDdiscountMoney>0 ?get_format_number($cardDdiscountMoney):0
            ];
        }
        if ($order['card_id'] > 0 && $order['card_price'] > 0) {
            $discount['merchant_coupon'] = [
                'title' => L_('商家优惠券'),
                'symbol' => '-',
                'amount' => get_format_number($order['card_price'])
            ];
        }
        if ($discountDetail && isset($discountDetail['delivery']) && $discountDetail['delivery']) {
            $sysDeliver = $discountDetail['delivery'];
            $sysDeliver['merchant_money'] = $sysDeliver['merchant_money'];
            if ($sysDeliver['minus'] > $order['freight_charge_original']) {
                $sum = $sysDeliver['merchant_money'] + $sysDeliver['plat_money'];
                if ($sum > 0) {
                    $sysDeliver['merchant_money'] = $sysDeliver['merchant_money'] * $order['freight_charge_original'] / $sum;
                }
            }

            if ($sysDeliver['money'] > 0) {
                $title = sprintf('平台配送费优惠满%s减%s，商家补贴部分', get_format_number($sysDeliver['money']), get_format_number($sysDeliver['minus']));
            } else {
                $title = sprintf('平台配送费优惠减%s，商家补贴部分', get_format_number($sysDeliver['minus']));
            }
            $discount['system_deliver'] = [
                'title' => $title,
                'symbol' => '-',
                'amount' => get_format_number($sysDeliver['merchant_money'])
            ];
        }
        if ($discountDetail && isset($discountDetail['system_minus']) && $discountDetail['system_minus']) {
            $sysMinus = $discountDetail['system_minus'];
            $balanceReduce = $order['discount_price'] + $order['freight_charge'];
            $merchantSubsidy = round($sysMinus['merchant_money'], 2);
            if ($sysMinus['minus'] >= $balanceReduce) {
                $sum = $sysMinus['merchant_money'] + $sysMinus['plat_money'];
                if ($sum > 0) {
                    $merchantSubsidy = round($sysMinus['merchant_money'] * $balanceReduce / $sum, 2);
                }
            }

            if ($sysMinus['money'] > 0) {
                $title = sprintf('平台优惠满%s减%s，商家补贴部分', get_format_number($sysMinus['money']), get_format_number($sysMinus['minus']));
            } else {
                $title = sprintf('平台优惠减%s，商家补贴部分', get_format_number($sysMinus['minus']));
            }
            $discount['system_reduce'] = [
                'title' => $title,
                'symbol' => '-',
                'amount' => get_format_number($merchantSubsidy)
            ];
        }

        if ($discountDetail && isset($discountDetail['system_newuser']) && $discountDetail['system_newuser']) {
            $systemNewuser = $discountDetail['system_newuser'];
            $systemNewuserMerchantSubsidy = round($systemNewuser['merchant_money'], 2);
            if ($systemNewuser['money'] > 0) {
                $title = sprintf('平台首单优惠满%s减%s', get_format_number($systemNewuser['money']), get_format_number($systemNewuser['minus']));
            } else {
                $title = sprintf('平台首单优惠减%s', get_format_number($systemNewuser['minus']));
            }
            $discount['system_newuser_reduce'] = [
                'title' => $title,
                'symbol' => '-',
                'amount' => get_format_number($systemNewuserMerchantSubsidy)
            ];
        }

        if ($discountDetail && isset($discountDetail['plat_discount']) && $discountDetail['plat_discount']) {
            $platDiscount = $discountDetail['plat_discount'];
            if ($platDiscount['percentage_status'] == 1) {
                //折扣
                $platDiscountMerchantSubsidy = round($platDiscount['minus2'] * $platDiscount['merchant_money'], 2);
                if ($platDiscount['money'] > 0) {
                    $title = sprintf('平台优惠满%s享%s折', get_format_number($platDiscount['money']), get_format_number($platDiscount['minus'] * 10));
                } else {
                    $title = sprintf('平台优惠享%s折', get_format_number($platDiscount['minus'] * 10));
                }
            } else {
                //满减
                $platDiscountMerchantSubsidy = round($platDiscount['merchant_money'], 2);
                if ($platDiscount['money'] > 0) {
                    $title = sprintf('平台优惠满%s减%s', get_format_number($platDiscount['money']), get_format_number($platDiscount['minus']));
                } else {
                    $title = sprintf('平台优惠减%s', get_format_number($platDiscount['minus']));
                }
            }

            $discount['plat_discount_reduce'] = [
                'title' => $title,
                'symbol' => '-',
                'amount' => get_format_number($platDiscountMerchantSubsidy)
            ];
        }

        if ($order['vip_level_discount'] > 0 && $order['vip_level_discount'] < 100) {
            $discount['system_card'] = [
                'title' => sprintf('平台会员等级%s折优惠', get_format_number($order['vip_level_discount'] / 10)),
                'symbol' => '-',
                'amount' => get_format_number($order['vip_level_reduce_money'])
            ];
        }

        if ($order['coupon_id'] > 0 && $order['coupon_price'] > 0) {
            $sysCoupon = (new SystemCouponHadpull())->alias('p')
                ->join('system_coupon c', 'c.coupon_id=p.coupon_id')
                ->field('c.*')
                ->where(['p.id' => $order['coupon_id']])
                ->findOrEmpty()
                ->toArray();
            if ($sysCoupon) {
                if ($sysCoupon['is_discount'] == 0) {
                    if ($sysCoupon['discount'] > $order['coupon_price']) {
                        $sum = $sysCoupon['merchant_money'] + $sysCoupon['plat_money'];
                        if ($sum > 0) {
                            $sysCoupon['merchant_money'] = round($sysCoupon['merchant_money'] * $order['coupon_price'] / $sum, 2);
                            $sysCoupon['plat_money'] = $order['coupon_price'] - $sysCoupon['merchant_money'];
                        }
                    }

                    $discount['system_coupon'] = [
                        'title' => L_('平台优惠券，商家补贴部分'),
                        'symbol' => '-',
                        'amount' => get_format_number($sysCoupon['merchant_money'])
                    ];
                } else {
                    $discount['system_coupon'] = [
                        'title' => L_('平台优惠券，商家补贴部分'),
                        'symbol' => '-',
                        'amount' => get_format_number($sysCoupon['merchant_money'] * $order['coupon_price'])
                    ];
                }
            }
        }

        if ($order['card_give_money'] > 0) {
            $discount['card_give_money'] = [
                'title' => L_('商家会员卡余额支付的线下充值部分'),
                'symbol' => '-',
                'amount' => get_format_number($order['card_give_money'])
            ];
        }

        return $withIndex ? $discount : array_values($discount);
    }

    /**
     * StatisticOrderMoneyJob 队列调用
     * @param $storeId
     * @param $date
     * @return bool
     */
    public function merchantStoreStatistic($storeId, $date): bool
    {
        $time = Carbon::parse($date);
        $startTime = $time->startOfDay()->timestamp;
        $endTime = $time->endOfDay()->timestamp;
        //总支付金额，平台余额支付金额， 支付订单数量
        $payMoney = ShopOrder::where([
                ['store_id', '=', $storeId],
                ['paid', '=', 1],
                ['pay_time', '>=', $startTime],
                ['pay_time', '<=', $endTime],
            ])
            ->field(
                "SUM(price) pay_money,
                SUM(CASE WHEN pay_type = 'offline' THEN discount_price ELSE 0 END) offline_pay_money,
                SUM(balance_pay) balance_pay_money,
                SUM(refund_money) refund_money,
                count(*) cnt"
            )
            ->find();
        //支付宝支付金额
        $aliPayMoney = ShopOrder::alias('o')
            ->join('payment_money_order p', "o.order_id = p.order_id AND p.order_type = 'shop'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'alipayh5, alipay'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //微信支付金额
        $weixinPayMoney = ShopOrder::alias('o')
            ->join('payment_money_order p', "o.order_id = p.order_id AND p.order_type = 'shop'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'weixinh5, weixin'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //一卡通支付金额
        $cardMoney = InternalMembershipCardLog::where([
                ['store_id', '=', $storeId],
                ['operation_type', '=', 2],//支付
                ['business_type', 'IN', 'shop, shop_offline'],
                ['create_time', '>=', $startTime],
                ['create_time', '<=', $endTime],
            ])
            ->field('SUM(money) pay_money')
            ->find();

        $allPayMoney = 
            $cardMoney['pay_money'] + 
            $payMoney['balance_pay_money'] + 
            $payMoney['offline_pay_money'] + 
            $aliPayMoney['pay_money'] + 
            $weixinPayMoney['pay_money'];
        
        $data = [
            'mer_id' => MerchantStore::where(['store_id' => $storeId, 'status' => 1])->value('mer_id'),
            'store_id' => $storeId,
            'order_type' => 'shop',
            'bill_date' => $date,
            'pay_order_count' => $payMoney['cnt'],
            'pay_money' => bcsub($allPayMoney, $payMoney['refund_money'], 2),
            'internal_card_pay_money' => $cardMoney['pay_money'] ?: 0,
            'offline_pay_money' => $payMoney['offline_pay_money'] ?: 0,
            'balance_pay_money' => $payMoney['balance_pay_money'] ?: 0,
            'alipay_pay_money' => $aliPayMoney['pay_money'] ?: 0,
            'wechat_pay_money' => $weixinPayMoney['pay_money'] ?: 0,
            'refund_money' => $payMoney['refund_money'] ?: 0,
        ];
        
        return (new InternalCardMoneyBillService())->saveMerchantStoreDateBill($data);
    }
    public function uploadMiaomaiOrder($orderId)
    {
        $order = ShopOrder::where(['order_id' => $orderId])->find();
        if(empty($order)){
            fdump_api(['order_id' => $orderId, 'error' => '订单不存在'], 'miaomai/uploadMiaomaiOrder', 1);
            return false;
        }
        $merchant = Merchant::where(['mer_id' => $order['mer_id']])->find();
        if(empty($merchant['miaomaiyun_switch'])){
            fdump_api(['order_id' => $orderId, 'error' => '淼迈云开关未打开'], 'miaomai/uploadMiaomaiOrder', 1);
            return false;
        }
        $merchantStore = MerchantStore::where(['store_id' => $order['store_id']])->find();
        if(empty($merchantStore['miaomaiyun_erp_storeid'])){
            fdump_api(['order_id' => $orderId, 'error' => '店铺淼迈云店铺未设置'], 'miaomai/uploadMiaomaiOrder', 1);
            return false;
        }
        $address = UserAdress::where(['adress_id' => $order['address_id']])->find();
        if(empty($address)){
            $address = [
                'province' => $merchantStore['province_id'],
                'city' => $merchantStore['city_id'],
                'area' => $merchantStore['area_id'],
                'address' => $merchantStore['adress'],
            ];
        }
        $province = Area::where(['area_id' => $address['province']])->value('area_name');
        $city = Area::where(['area_id' => $address['city']])->value('area_name');
        $area = Area::where(['area_id' => $address['area']])->value('area_name');
        
        $expressTime = '';
        if(!empty($order['expect_use_time'])){
            $expressTime = date('Y-m-d H:i:s', $order['expect_use_time']);
        }
        $payMethod = [
            'wechat' => ['pay_method' => '2', 'name' => '微信支付'],
            'alipay' => ['pay_method' => '3', 'name' => '支付宝支付'],
            'card' => ['pay_method' => '5', 'name' => '会员卡支付'],
            ['pay_method' => '11', 'name' => '挂账'],
            'score' => ['pay_method' => '2050', 'name' => '积分兑换'],
        ];
        $origin = [
            2 => ['origin' => 1, 'origin_name' => '安卓app'],
            3 => ['origin' => 2, 'origin_name' => '苹果app'],
            4 => ['origin' => 3, 'origin_name' => '小程序'],
            5 => ['origin' => 4, 'origin_name' => '网页'],
            6 => ['origin' => 5, 'origin_name' => '收银端'],
            9 =>['origin' => 6, 'origin_name' => '扫码支付'],
            ['origin' => 99, 'origin_name' => '其他'],
        ];
        $mode = [
            0 => ['mode' => 1, 'mode_name' => '配送'],
            1 => ['mode' => 1, 'mode_name' => '配送'],
            2 => ['mode' => 2, 'mode_name' => '自提'],
            3 => ['mode' => 1, 'mode_name' => '配送'],
        ];
        $orderFrom = 99;
        if(isset($origin[$order['order_from']])){
            $orderFrom = $origin[$order['order_from']]['mode'];
        }
        $orderPayMethod = 5;
        if(isset($payMethod[$order['pay_type']])){
            $orderPayMethod = $payMethod[$order['pay_type']]['pay_method'];
        }
        $data = [
            'appid' => $merchant['miaomaiyun_erp_appid'],
            'stores_id' => $merchantStore['miaomaiyun_erp_storeid'],
            'member_openid' => $order['userphone'],
            'freight' => $order['freight_charge'],
            'pay_time' => date('Y-m-d H:i:s', $order['pay_time']),
            'pay_method' => $orderPayMethod,
            'origin' => $orderFrom,
            'mode' => $mode[$order['is_pick_in_store']]['mode'],
            'address' => $order['address'],
            'pcac' => "$province/$city/$area",
            'districts' => $order['address'],
            'otype' => 2,
            'member_ver' => 2,
            'start_time' => $expressTime,
            'end_time' => $expressTime,
            'name' => $order['username'],
            'tel' => $order['userphone'],
            'out_code' => $order['real_orderid'],
            'remark' => $order['remark'],
        ];
        $orderGoods = ShopOrderDetail::where(['order_id' => $order['order_id']])->select();
        foreach($orderGoods as $detail){
            $miaomaiBarcodeId = ShopGoods::where(['goods_id' => $detail['goods_id']])->value('miaomaiyun_barcode_id');
            $data['goods'][] = [
                'barcode_id' => $miaomaiBarcodeId,
                'num' => $detail['num']
            ];
        }
        $data['goods'] = json_encode($data['goods'], JSON_UNESCAPED_UNICODE);
        $miaomaiyun = new Miaomaiyun();
        $result = $miaomaiyun
            ->setConfig($merchant['miaomaiyun_erp_appid'], $merchant['miaomaiyun_erp_appsecret'], $merchant['miaomaiyun_url'])
            ->setParams($data)
            ->run(Miaomaiyun::ORDER_UPD);
        
        fdump_api(['data' => $data,'result' => $result], 'miaomai/uploadMiaomaiShopOrder', 1);
        if ($result['status'] != 'y') {
            $order->miaomai_order_code = $result['data']['order_code'];
            $order->save();
        }
        $upload = [
            'order_no' => $order['real_orderid'],
            'order_type' => 'shop',
            'data' => $data,
            'result' => $result,
            'status' => $result['status'] == 'y' ? 1 : 2,
        ];
        if($result['status'] == 'y'){
            $order->miaomai_order_code = $result['data']['order_code'];
            $order->save();
            $upload['miaomaiyun_order_code'] = $result['data']['order_code'];
        }
        MiaomaiOrderUpload::saveInfo($upload);

        return true;
    }

    public function miaomaiChangeOrderStatus($order, $status, $staff)
    {
        $shopOrder = ShopOrder::where(['order_id' => $order['order_id']])->find();
        $orderId = $order['order_id'];
        switch ($status){
            case 10://发货
                (new StoreStaffService())->take($orderId, 'shop', $staff['id'], $staff['pick_addr_ids']??'');
                break;
            case 20://完成
                (new StoreStaffService())->confirmConsume($orderId, 'shop', $staff['id'], $staff['pick_addr_ids']??'');
                break;
            case 30://取消
                (new StoreStaffService())->cancel($orderId, 'shop', $staff['id'], $staff['pick_addr_ids']??'');
                break;
            case 40://退货
                if(!empty($order['refund_code']) && !empty($order['refund_status'])){
                    $refund = ShopOrderRefund::where(['miaomai_refund_code' => $order['refund_code']])->find();
                    switch ($order['refund_status']){
                        case 1://退款
                            (new StoreStaffService())->replyRefund($orderId, 'shop', $staff['id'], [
                                'refund_id' => $refund['refund_id'],
                                'type' => 'agree',
                            ]);
                            break;
                        case 2://拒绝退款
                            (new StoreStaffService())->replyRefund($orderId, 'shop', $staff['id'], [
                                'refund_id' => $refund['refund_id'],
                                'type' => 'disagree',
                                'reply_content' => '淼迈云拒绝退款',
                            ]);
                            break;
                        default:
                            throw_exception('退款状态异常');
                    }
                }else{
                    throw_exception('退款失败');
                }
                break;
        }
        return true;
    }

    public function miaomaiShopOrderRefund($refundId)
    {
        $orderRefund = ShopOrderRefund::where('id',$refundId)->find();
        $refundGoods = ShopRefundDetail::where('refund_id',$refundId)->select();
        $shopOrder = ShopOrder::where('order_id',$orderRefund['order_id'])->find();
        $merchant = Merchant::where(['mer_id' => $shopOrder['mer_id']])->find();
        if(empty($merchant['miaomaiyun_switch'])){
            fdump_api(['refund_id' => $refundId, 'error' => '淼迈云开关未打开'], 'miaomai/miaomaiShopOrderRefund', 1);
            return false;
        }
        $merchantStore = MerchantStore::where(['store_id' => $shopOrder['store_id']])->find();
        if(empty($merchantStore['miaomaiyun_erp_storeid'])){
            fdump_api(['refund_id' => $refundId, 'error' => '店铺淼迈云店铺未设置'], 'miaomai/miaomaiShopOrderRefund', 1);
            return false;
        }
        $goods = [];
        foreach ($refundGoods as $value) {
            $shopGoods = ShopGoods::alias('s')
                ->join('shop_order_detail d','d.goods_id = s.goods_id')
                ->where('d.id',$value['detail_id'])
                ->field('s.name goods_name, s.goods_id, s.miaomaiyun_barcode_id')
                ->find();
            $goods[] = [
                'barcode_id' => $shopGoods['miaomaiyun_barcode_id'],
                'name' => $shopGoods['goods_name'],
                'num' => $value['num'],
                'reason' => $orderRefund['reason'],
            ];
        }
        $miaomaiOrderCode = $shopOrder['miaomai_order_code'];
        $userPhone = User::where(['uid' => $shopOrder['uid']])->value('phone');
        $refundInfo = [
            'order_code' => $miaomaiOrderCode,
            'member_openid' => $userPhone,
            'reason' => 7,//其他
            'goods' => json_encode($goods,JSON_UNESCAPED_UNICODE)
        ];
        $result = (new Miaomaiyun)
            ->setConfig($merchant['miaomaiyun_erp_appid'], $merchant['miaomaiyun_erp_appsecret'], $merchant['miaomaiyun_url'])
            ->setParams($refundInfo)
            ->run(Miaomaiyun::ORDER_REFUND_APPLY);
        fdump_api(['refund_info' => $refundInfo, 'result' => $result],'miaomai/uploadMiaomaiShopOrder',1);
        $upload = [
            'order_no' => $shopOrder['real_orderid'],
            'order_type' => 'mall',
            'data' => $refundInfo,
            'result' => $result,
            'status' => $result['status'] == 'y' ? 1 : 2,
        ];
        if($result['status'] == 'y'){
            $orderRefund->miaomai_order_code = $result['data']['refund_code'];
            $orderRefund->save();
            $upload['miaomaiyun_order_code'] = $result['data']['refund_code'];
        }
        MiaomaiOrderUpload::saveInfo($upload);

        return true;
    }
}
