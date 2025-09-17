<?php

namespace app\deliver\model\service;


use app\common\model\db\ShopOrder;
use app\common\model\service\user\UserNoticeService;
use app\common\model\service\UserMoneyListService;
use app\deliver\Code;
use app\deliver\model\db\DeliverSupply;
use app\deliver\model\db\DeliverUser;
use app\mall\model\service\MallOrderService;
use app\merchant\model\service\MerchantMoneyListService;
use app\paotui\model\service\ServiceUserPublishService;
use app\shop\model\db\ShopOrderDetail;
use app\shop\model\db\ShopOrderRefund;
use app\shop\model\service\order\ShopOrderService;
use app\shop\model\service\order\ShopOrderLogService;
use app\villageGroup\model\service\VillageGroupOrderService;
use map\longLat;
use think\Exception;
use think\facade\Db;

/**
 * 配送服务
 * @author: 张涛
 * @date: 2020/9/9
 */
class DeliverSupplyService
{
    public $deliverSupplyMod;

    public function __construct()
    {
        $this->deliverSupplyMod = new DeliverSupply();
    }

    /**
     * 获取一条配送记录
     * @author: 汪晨
     * @date: 2021/1/27
     */
    public function getOneOrder($where, $fields = '*')
    {
        return (new DeliverSupply())->where($where)->field($fields)->findOrEmpty()->toArray();
    }

    /**
     * 获取配送里程列表
     * @author: 汪晨
     * @date: 2021/1/27
     */
    public function getDistanceLists($where, $fields = '*')
    {
        return (new DeliverSupply())->where($where)->field($fields)->select()->toArray();
    }

    /**
     * 更新打赏小费数据
     * @param $orderId
     * @author: 汪晨
     * @date: 2021/1/29
     */
    public function tipPriceUpdae($orderId,$tipPrice)
    {
        $data = [
            'tip_price' => $tipPrice,
        ];
        return $this->deliverSupplyMod->where(['order_id' => $orderId])->inc('tip_price',$tipPrice)->update();
        // return $this->deliverSupplyMod->where(['order_id' => $orderId])->update($data);
    }

    /**
     * 获取配送列表
     * @author: 张涛
     * @date: 2020/9/9
     */
    public function getOrderLists($where, $uid = 0)
    {
        $items = $this->deliverSupplyMod->where($where)->order('supply_id DESC')->select()->toArray();
        return $this->buildDeliverData($items, $uid);
    }

    /**
     * 获取可抢订单列表
     * @author: 张涛
     * @date: 2020/9/9
     */
    public function getNewOrderLists(DeliverUser $nowUser, $orderType = '')
    {
        $myLng = $nowUser->lng;
        $myLat = $nowUser->lat;
        $myRange = $nowUser->range * 1000;
        $uid = $nowUser->uid;
        $tm = time(); 
        if ($nowUser->group == 1) {
            //平台配送员 状态必须为待结单和没有关联配送员
            $where = "`type`= 0 AND `status`=1 AND `uid`=0 AND NOT FIND_IN_SET($uid,back_log) AND NOT FIND_IN_SET($uid,refuse_log)";
            if ($nowUser->delivery_range_type == 0) {
                $where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$myLat}*PI()/180-`from_lat`*PI()/180)/2),2)+COS({$myLat}*PI()/180)*COS(`from_lat`*PI()/180)*POW(SIN(({$myLng}*PI()/180-`from_lnt`*PI()/180)/2),2)))*1000) < $myRange ";
            } else {
                $where .= " AND MBRContains(PolygonFromText('" . $nowUser->delivery_range_polygon . "'),PolygonFromText(CONCAT('Point(',from_lnt,' ',from_lat,')')))>0";
            }
            $where = '((' . $where . ') OR (get_type IN (1,2) AND status IN (2,3,4) AND uid=' . $uid . ' AND is_fetch_order = 0))';
        } else {
            //商家配送员
            $storeIds = $nowUser->store_ids ? $nowUser->store_ids : $nowUser->store_id;
            $where = "`type`= 1 AND `status`=1 AND `store_id` IN (" . $storeIds . ")";
        }

        $filter = '';
        if ($orderType == 'merchant_order') {
            $filter .= " AND `item` IN (2,4,5)";
        } else if ($orderType == 'service_buy') {
            $filter .= " AND `item`=3 AND `server_type` IN (2,3)";
        } else if ($orderType == 'service_send') {
            $filter .= " AND `item`=3 AND `server_type`=1";
        } else if ($orderType == 'village_group_order') {
            $filter .= " AND `item`=8";
        }
        $where .= $filter;

        //配送员可配送订单类型判断
        $whiteOrderType = explode(',', $nowUser->order_type);
        $whiteCondition = [];
        if (in_array(1, $whiteOrderType)) {
            //商家单
            $whiteCondition[] = " (`item` IN (2,4,5)) ";
        }
        if (in_array(2, $whiteOrderType)) {
            //帮买单
            $whiteCondition[] = " (`item`=3 AND `server_type` IN (2,3)) ";
        }
        if (in_array(3, $whiteOrderType)) {
            //帮送单
            $whiteCondition[] = " (`item`=3 AND `server_type`=1) ";
        }
        if (in_array(4, $whiteOrderType)) {
            //社区团购
            $whiteCondition[] = " (`item`= 8) ";
        }
        $where .= ' AND (' . implode('OR', $whiteCondition) . ')';

        //新订单读取最近7天的订单，数量限制300单，防止历史订单过多导致系统卡死
        $where .= ' AND create_time > ' . ($tm - 86400 * 7);
        $items = $this->deliverSupplyMod->where($where)->order('supply_id DESC')->limit(300)->select()->toArray();

        //屏蔽掉不允许抢单的订单
        $cityIds = array_unique(array_column($items, 'city_id'));
        $areaConfig = (new \app\common\model\db\Area())->whereIn('area_id', $cityIds)->column('no_grab_min', 'area_id');
//        $areaDeliverConfig = (new \app\common\model\db\Area())->whereIn('area_id', $cityIds)->column('deliver_model', 'area_id');

        foreach ($items as $k => $v) {
            if ($v['type'] == 0 && $v['status'] == 1 && isset($areaConfig[$v['city_id']]) && $v['create_time'] + $areaConfig[$v['city_id']] * 60 < $tm) {
                unset($items[$k]);
            }
//            elseif($v['type'] == 0 && isset($areaDeliverConfig[$v['city_id']]) && $areaDeliverConfig[$v['city_id']] > 0 && $v['get_type'] == 0){//禁止抢单的城市的订单不出现
//                unset($items[$k]);
//            }
        }
        $items = array_values($items);

        //获取到其他用户转给自己的
        $where = 'transfer_to_uid =' . $nowUser->uid . ' AND transfer_status=0 AND transfer_deliver_status = deliver_status' . $filter;
        $transferItems = $this->deliverSupplyMod->where($where)->select()->toArray();
        $items = array_merge($transferItems, $items);

        //超出最大接单量，则显示新任务为空
        $currentOrderCount = (new DeliverUserService)->getCurrentCount($nowUser->uid);
        if ($nowUser->max_num != 0 && $nowUser->max_num <= $currentOrderCount) {
            return ['max_num' => $nowUser->max_num, 'code' => Code::OVER_MAX_NUM, 'message' => L_('超出最大接单量')];
        }
        return $this->buildDeliverData($items, $nowUser->uid);
    }

    public function buildDeliverData($items, $uid = 0)
    {
        $shopOrderService = new ShopOrderService();
        $serviceUserPublishService = new ServiceUserPublishService();
        $mallOrderService = new MallOrderService();
        $villageGroupOrderService = new VillageGroupOrderService();
        $nowUser = (new DeliverUserService())->getOneUser('uid=' . $uid);
        $newOrders = [];
        $tm = time();
        foreach ($items as $key => $value) {
            $thisOrder['supply_id'] = $value['supply_id'];
            $thisOrder['deliver_cash'] = get_format_number($value['deliver_cash']);
            $thisOrder['deliver_user_fee'] = get_format_number($value['deliver_user_fee'] + $value['tip_price']);
            $thisOrder['deliver_status'] = $value['deliver_status'];
            $thisOrder['transfer_status'] = $value['transfer_status'];
            $thisOrder['transfer_to_uid'] = $value['transfer_to_uid'];
            $thisOrder['transfer_time'] = $value['transfer_time'];
            $thisOrder['is_fetch_order'] = $value['is_fetch_order'];
            $thisOrder['show_nav'] = $value['deliver_status'] > 1 ? true : false;
            //$thisOrder['show_miles'] = true;
            $thisOrder['is_voice'] = false;
            $thisOrder['voice_url'] = '';
            $thisOrder['voice_time'] = 0;

            $order = [];
            $prefectOrderInfo = false;
            if ($value['item'] == 2) {
                //外卖
                $order = $shopOrderService->getInfoForDeliverList($value['order_id']);
            } else if ($value['item'] == 3) {
                //帮我买帮我送
                $order = $serviceUserPublishService->getInfoForDeliverList($value['order_id']);
                $isVoice = $order['order']['is_voice'] ?? 0;
                $endAddressName = $order['order']['end_adress_name'] ?? '';
                $prefectOrderInfo = ($isVoice == 1 && empty($endAddressName));

                $thisOrder['is_voice'] = $isVoice ? true : false;
                $thisOrder['voice_url'] = replace_file_domain($order['order']['voice_url']);
                $thisOrder['voice_time'] = $order['order']['voice_time'];

                $fixExpectTime = strtotime($order['expect_use_time']);
                if ($value['appoint_time'] == 0) {
                    $value['appoint_time'] = $fixExpectTime;
                }
                if ($value['order_out_time'] == 0) {
                    $value['order_out_time'] = $fixExpectTime;
                }
            }else if($value['item'] == 4 ){
                //新版商城
                $order = $mallOrderService->getDeliverDetail($value['order_id']);
            } else if($value['item'] == 5 ){
                //新版商城周期购
                $order = $mallOrderService->getDeliverDetail($value['order_id'],1);
            } else if ($value['item'] == 8) {
                //社区团购
                $order = $villageGroupOrderService->getInfoForDeliverList($value['order_id']);
            }else {
                continue;
            }

            $thisOrder['need_prefect_order_info'] =  $prefectOrderInfo;
            if ($value['deliver_status'] == 1 && $nowUser) {
                //待接单的计算配送费
                if ($value['item'] == 2) {
                    $fc = $value['freight_charge'] ?? 0;
                } else if ($value['item'] == 3) {
                    //帮我买帮我送
                    $fc = $order['order']['basic_distance_price'] + $order['order']['weight_price'] + $order['order']['distance_price']; //基础配送费+超重费用+超距离配送
                } else if ($value['item'] == 4) {
                    //新版商城
                    $fc = $order['order']['money_freight'] ?? 0;
                } else if ($value['item'] == 8) {
                    //社区团购
                    $fc = $value['freight_charge'] ?? 0;
                }
                $thisOrder['deliver_user_fee'] = $fc - ($fc * ($nowUser['take_percent'] / 100));
                $thisOrder['deliver_user_fee'] = get_format_number($thisOrder['deliver_user_fee'] + $value['tip_price']);
            }

            if ($value['deliver_status'] < 4 && cfg('deliver_see_freight_charge') == 0) {
                $thisOrder['deliver_user_fee'] = '--';
            }

            if (empty($order)) {
                continue;
            }

            //展示x分钟送达等展示
            if ($value['deliver_status'] == 1) {
                $thisOrder['time_left_second'] = $value['appoint_time'] - $tm;
                $thisOrder['time_left_type'] = L_('送达');
            } else if ($value['deliver_status'] == 2 || $value['deliver_status'] == 3) {
                $value['order_out_time'] = $value['order_out_time'] > 0 ? $value['order_out_time'] : $value['appoint_time'];
                $thisOrder['time_left_second'] = $value['order_out_time'] - $tm;
                $thisOrder['time_left_type'] = L_('取货');
            } else if ($value['deliver_status'] == 4) {
                $thisOrder['time_left_second'] = $value['appoint_time'] - $tm;
                $thisOrder['time_left_type'] = L_('送货');
            }
            $thisOrder['show_status'] = '';
            $thisOrder['transfer_left_second'] = 0;
            $thisOrder['is_transfer_order'] = false;


            $isTransferToUser = 0;
            if ($uid > 0 && $value['transfer_status'] == 0 && $value['transfer_time'] > 0) {
                //转单,写死3分钟
                if ($uid == $value['transfer_from_uid']) {
                    $thisOrder['transfer_left_second'] = config('const.refuse_transfer_order_expire') - (time() - $value['transfer_time']);
                    $thisOrder['is_transfer_order'] = true;
                    $isTransferToUser = -1;
                } else {
                    $isTransferToUser = 1;
                }
            }

            //取件地址距离重置
            if ($nowUser && isset($order['pick_address']['miles']) && $order['pick_address']['miles'] != '') {
                $pickDistance = get_distance($nowUser['now_lat'], $nowUser['now_lng'], $value['from_lat'], $value['from_lnt']);
                $order['pick_address']['miles'] = $pickDistance >= 1000 ? round($pickDistance / 1000, 2) . 'km' : $pickDistance . 'm';
            }

            // 获取订单完成时间 判断是否显示收货地址
            $is_user_address = true;
            $businessOrderTime = $value['finish_time'] ?? $value['end_time'];
            if(!empty($businessOrderTime)){
                $businessOrderDateline = $businessOrderTime + (60*60*6);
                $is_user_address = $businessOrderDateline > time() ? true : false;
            }
            $thisOrder['is_user_address'] = $is_user_address;
            $thisOrder['fetch_number'] = $order['fetch_number'];
            $thisOrder['note'] = $order['desc'] ?: $order['note'];
            $thisOrder['pick_address'] = $order['pick_address'];
            $thisOrder['user_address'] = $order['user_address'];
            $thisOrder['labels'] = $order['labels'];
            $thisOrder['phone_lists'] = $order['phone_lists'];
            $thisOrder['username'] = $order['username'];
            $thisOrder['phone'] = $order['phone'];
            $thisOrder['btns'] = $this->getBtnsByDeliverStatus($value['deliver_status'], '', $isTransferToUser,$value['is_fetch_order'],0,$prefectOrderInfo);
            $newOrders[] = $thisOrder;
        }

        return $newOrders;
    }

    /**
     * 根据配送状态获取按钮
     * @author: 张涛
     * @date: 2020/9/9
     */
    public function getBtnsByDeliverStatus($status, $from = '', $isTransferToUser = 0, $isFetchOrder = 1, $uid =0, $prefectOrderInfo = false)
    {
        //配送状态 1：等待接待  2：接单  3：我已到店  4：我已取货 5：我已送达
        //1：文本 2、拒单  3、电话  4、转单  5、扔回
        $grabBtn = ["action" => "grab_order", "style" => 1, "txt" => L_("抢单"), "background" => "#27A3F7", "font_color" => "#FFFFFF","border_color"=>"#27A3F7"];
        $refuseBtn = ["action" => "refuse_order", "style" => 2, "txt" => L_("拒绝"), "background" => "#FFFFFF", "font_color" => "#333333","border_color"=>"#D5D5D5"];
        $reportArriveStoreBtn = ["action" => "report_arrive_store", "style" => 1, "txt" => L_("上报到店"), "background" => "#FD9827", "font_color" => "#FFFFFF","border_color"=>"#FD9827"];
        $pickBtn = ["action" => "pick_order", "style" => 1, "txt" => L_("我已取货"), "background" => "#27A3F7", "font_color" => "#FFFFFF","border_color"=>"#27A3F7"];
        $finishBtn = ["action" => "finish_order", "style" => 1, "txt" => L_("我已送达"), "background" => "#57D57A", "font_color" => "#FFFFFF","border_color"=>"#57D57A"];
        $acceptTransferBtn = ["action" => "accept_transfer_order", "style" => 1, "txt" => L_("接收转单"), "background" => "#2AC5B4", "font_color" => "#FFFFFF","border_color"=>"#2AC5B4"];
        $fetchBtn = ["action" => "fetch_order", "style" => 1, "txt" => L_("接收派单"), "background" => "#2AC5B4", "font_color" => "#FFFFFF","border_color"=>"#2AC5B4"];
        $phoneBtn = ["action" => "phone", "style" => 3, "txt" => "", "background" => "#ffffff", "font_color" => "#000000","border_color"=>"#ffffff"];
        $throwBtn = ["action" => "throw_order", "style" => 5, "txt" => L_("扔回"), "background" => "#ffffff", "font_color" => "#000000","border_color"=>"#ffffff"];
        $transferBtn = ["action" => "transfter_order", "style" => 4, "txt" => L_("转单"), "background" => "#ffffff", "font_color" => "#000000","border_color"=>"#ffffff"];
        $perfectBtn = ["action" => "fill_shipping_address", "style" => 1, "txt" => L_("完善订单信息"), "background" => "#27A3F7", "font_color" => "#FFFFFF","border_color"=>"#27A3F7"];

        $isCancelOrder = 1;
        if ($uid > 0) {
            $isCancelOrder = (new DeliverUser())->where('uid', '=', $uid)->value('is_cancel_order');
        }

        if ($status == 2 && $prefectOrderInfo) {
            return [$perfectBtn, $phoneBtn];
        }

        $btns = [];
        if ($from == 'detail') {
            //详情页按钮
            if ($status == 1) {
                $btns = [$grabBtn];
            } else if ($status == 2) {
                if($isFetchOrder == 0){
                    if(cfg('deliver_refuse_system_assign') == 1){
                        $btns = [$fetchBtn, $refuseBtn, $phoneBtn];
                    }else{
                        $btns = [$fetchBtn, $phoneBtn];
                    }
                } else {
                    if ($isCancelOrder) {
                        $btns = [$reportArriveStoreBtn, $throwBtn, $transferBtn, $phoneBtn];
                    } else {
                        $btns = [$reportArriveStoreBtn, $transferBtn, $phoneBtn];
					}
				}
            } else {
                if ($isFetchOrder == 0) {
                    if(cfg('deliver_refuse_system_assign') == 1){
                        $btns = [$fetchBtn, $refuseBtn, $phoneBtn];
                    }else{
                        $btns = [$fetchBtn, $phoneBtn];
                    }
                } else {
                    if ($status == 2) {
                        if ($isCancelOrder) {
                            $btns = [$reportArriveStoreBtn, $throwBtn, $transferBtn, $phoneBtn];
                        } else {
                            $btns = [$reportArriveStoreBtn, $transferBtn, $phoneBtn];
                        }
                    } else if ($status == 3) {
                        $btns = [$pickBtn, $transferBtn, $phoneBtn];
                    } else if ($status == 4) {
                        $btns = [$finishBtn, $transferBtn, $phoneBtn];
                    }
                }
            }
        } else {
            if ($status == 1) {
                $btns = [$grabBtn];
            } else {
                if ($isFetchOrder == 0) {
                    if(cfg('deliver_refuse_system_assign') == 1){
                        $btns = [$fetchBtn, $refuseBtn];
                    }else{
                        $btns = [$fetchBtn];
                    }
                } else {
                    if ($status == 2) {
                        $btns = [$reportArriveStoreBtn, $phoneBtn];
                    } else if ($status == 3) {
                        $btns = [$pickBtn, $phoneBtn];
                    } else if ($status == 4) {
                        $btns = [$finishBtn, $phoneBtn];
                    }
                    if ($isTransferToUser == 1) {
                        $btns = [$acceptTransferBtn, $refuseBtn];
                    } else if ($isTransferToUser == -1) {
                        $btns = [];
                    }
                }
            }
        }
        return $btns;
    }

    /**
     * 获取配送员评分统计、评论统计
     * @author: 张涛
     * @date: 2020/09/14
     */
    public function getScoreReportByUid($uid)
    {
        $rs = ['score' => 0, 'user_count' => 0, 'good' => 0, 'middle' => 0, 'bad' => 0, 'total' => 0];

        //获取好、中、差评数量
        $fields = "CASE WHEN score < 3 THEN 'bad' WHEN score = 3 THEN 'middle' ELSE 'good' END AS score_type,count(supply_id) AS count";
        $count = $this->deliverSupplyMod->field($fields)->where('uid', '=', $uid)->where('userId', '>', 0)->group('score_type')->select()->toArray();
        foreach ($count as $key => $value) {
            $rs[$value['score_type']] += $value['count'];
            $rs['total'] += $value['count'];
        }
        $rs['user_count'] = $rs['total'];

        //获取配送员评分
        $averageScore = (new DeliverUser())->where('uid', '=', $uid)->value('average_score');
        $rs['score'] = $averageScore ?: 0;
        return $rs;
    }

    /**
     * 获取评论列表
     * @author: 张涛
     * @date: 2020/09/14
     */
    public function replyList($uid, $page = 1, $pageSize = 20, $scoreType = 'all')
    {
        $prefix = config('database.connections.mysql.prefix');
        $fields = 's.name, u.avatar, s.score, s.comment, s.comment_time, s.from_site';
        $where = [['s.uid', '=', $uid]];
        if ($scoreType == 'good') {
            $where[] = ['s.score', '>', 3];
        } else if ($scoreType == 'middle') {
            $where[] = ['s.score', '=', 3];
        } else if ($scoreType == 'bad') {
            $where[] = ['s.score', '<', 3];
        }
        $lists = $this->deliverSupplyMod->alias('s')
            ->join([$prefix . 'user' => 'u'], 'u.uid=s.userId')
            ->field($fields)
            ->where($where)
            ->order('s.comment_time', 'DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select()
            ->toArray();
        $defaultAvatar = cfg('site_url') . '/static/images/user_avatar.jpg';
        foreach ($lists as $key => $row) {
            if (empty($row['avatar'])) {
                $lists[$key]['avatar'] = $defaultAvatar;
            }
            $lists[$key]['score'] = intval($row['score']);
            $lists[$key]['comment_time'] = date('Y-m-d H:i', $row['comment_time']);
        }
        return $lists;
    }

    /**
     * 获取配送订单详情
     * @author: 张涛
     * @date: 2020/9/16
     */
    public function getOrderDetail($supplyId, $uid = 0)
    {
        $supplyOrder = $this->deliverSupplyMod->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $nowUser = (new DeliverUserService())->getOneUser('uid=' . $uid);
        if (empty($supplyOrder)) {
            throw new Exception(L_('配送订单不存在'));
        }
        if ($uid > 0 && $supplyOrder['uid'] > 0 && !in_array($uid, [$supplyOrder['uid'], $supplyOrder['transfer_to_uid']])) {
            throw new Exception(L_('禁止访问其他配送员配送单'));
        }
        $tm = time();
        $rs = [
            'supply_id' => $supplyId,
            'deliver_cash' => get_format_number($supplyOrder['deliver_cash']),
            'uid' => $supplyOrder['uid'],
            'is_new_order' => false,
            'deliver_user_fee' => $supplyOrder['deliver_user_fee'],
            'is_voice' => false,
            'voice_url' => '',
            'voice_time' => 0
        ];

        $isTransferToUser = 0;
        if ($uid > 0) {
            if ($uid == $supplyOrder['transfer_from_uid']) {
                $isTransferToUser = -1;
            } else if ($uid == $supplyOrder['transfer_to_uid']) {
                $isTransferToUser = 1;
            }
        }

        if ($supplyOrder['item'] == 2) {
            //外卖订单
            $order = (new ShopOrderService())->getInfoForDeliverList($supplyOrder['order_id']);
            if (empty($order)) {
                throw new Exception(L_('业务订单不存在'));
            }
            $supplyOrder['deliver_user_fee'] = $rs['deliver_user_fee'] = $supplyOrder['deliver_status'] > 1 ? $supplyOrder['deliver_user_fee'] : get_format_number($supplyOrder['freight_charge'] - ($supplyOrder['freight_charge'] * ($nowUser['take_percent'] / 100)));
            $businessOrder = $order['order'];
            $rs['fetch_number'] = intval($businessOrder['fetch_number']);
            if ($supplyOrder['deliver_status'] == 1) {
                $rs['is_new_order'] = true;
            }
            //取件地址距离重置
            if ($nowUser) {
                $pickDistance = get_distance($nowUser['now_lat'], $nowUser['now_lng'], $supplyOrder['from_lat'], $supplyOrder['from_lnt']);
                $order['pick_address']['miles'] = $pickDistance >= 1000 ? round($pickDistance / 1000, 2) . 'km' : $pickDistance . 'm';
            }
            $rs['pick_address'] = $order['pick_address'];
            $rs['user_address'] = $order['user_address'];
            $rs['phone_lists'] = $order['phone_lists'];
            $rs['btns'] = $this->getBtnsByDeliverStatus($supplyOrder['deliver_status'], 'detail', $isTransferToUser, $supplyOrder['is_fetch_order'],$uid);

            $rs['goods'] = (new ShopOrderService())->getGoodsByMergeSubsidiaryGoods($supplyOrder['order_id']);

            $is_cancel = 0;
            if($businessOrder['status']==4){
                $goods_total_all = (new ShopOrderDetail())->where(['order_id'=>$supplyOrder['order_id']])->sum('num');
                $refund_goods_total_all = (new ShopOrderRefund())->alias('r')->join('shop_refund_detail rd', 'rd.refund_id = r.id')
                    ->where(['r.order_id'=>$supplyOrder['order_id'],'r.status'=>1])->sum('rd.num');
                if(!$refund_goods_total_all||($refund_goods_total_all>0&&$goods_total_all<=$refund_goods_total_all)){
                    $is_cancel = 1;
                }
            }
            
            $rs['satus_show'] = new \stdClass();
            if (in_array($businessOrder['status'], [ 5, 11])||($businessOrder['status']==4&&$is_cancel)) {
                $rs['satus_show'] = ['type' => 2, 'value' => ['status' => L_('订单已取消')]];
            } else if ($supplyOrder['deliver_status'] == 1) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['appoint_time'] - $tm), 'time_left_type' => L_('送达')]];
            } else if ($supplyOrder['deliver_status'] == 2 || $supplyOrder['deliver_status'] == 3) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['order_out_time'] - $tm), 'time_left_type' => L_('取货')]];
            } else if ($supplyOrder['deliver_status'] == 4) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['appoint_time'] - $tm), 'time_left_type' => L_('送货')]];
            } else if ($supplyOrder['deliver_status'] == 5) {
                //等待接单
                $rs['satus_show'] = ['type' => 3, 'value' => ['expect_arrive_second' => intval($supplyOrder['appoint_time'] - $supplyOrder['create_time']), 'real_arrive_second' => intval($supplyOrder['end_time'] - $supplyOrder['create_time'])]];
            }

            //特殊要求
            $rs['special'] = [];
            if ($businessOrder['invoice_head']) {
                $rs['special'][] = ['title' => L_('发票'), 'value' => $businessOrder['invoice_head']];
            }
            if ($businessOrder['desc']) {
                $rs['special'][] = ['title' => L_('备注'), 'value' => $businessOrder['desc']];
            }

            //时间点
            $rs['status_list'] = [
                ["txt" => L_("抢单"), "time" => $supplyOrder['grab_time'] > 0 ? date('H:i', $supplyOrder['grab_time']) : '--'],
                ["txt" => L_("到店"), "time" => $supplyOrder['report_arrive_store_time'] > 0 ? date('H:i', $supplyOrder['report_arrive_store_time']) : '--'],
                ["txt" => L_("取货"), "time" => $supplyOrder['pick_time'] > 0 ? date('H:i', $supplyOrder['pick_time']) : '--'],
                ["txt" => L_("送达"), "time" => $supplyOrder['finish_time'] > 0 ? date('H:i', $supplyOrder['finish_time']) : '--'],
            ];

            // 获取订单完成时间 判断是否显示收货地址
            $is_user_address = true;
            $businessOrderTime = $supplyOrder['finish_time'] ?? $supplyOrder['end_time'];
            if(!empty($businessOrderTime)){
                $businessOrderDateline = $businessOrderTime + (60*60*6);
                $is_user_address = $businessOrderDateline > time() ? true : false;
            }

            $rs['is_user_address'] = $is_user_address;

            $rs['order'] = [
                'order_type' => 'shop',
                'order_type_str' => cfg('shop_alias_name'),
                'order_id' => $businessOrder['order_id'],
                'real_orderid' => $businessOrder['real_orderid'],
                'expect_use_time' => date('Y-m-d H:i', $businessOrder['expect_use_time']) . '-' . date('H:i', $businessOrder['expect_use_time'] + 900),
                'short_expect_use_time' => L_('X1前', ['X1' => date('H:i', $businessOrder['expect_use_time'] + 900)]),
                'status' => $businessOrder['status'],
                'order_status' => $businessOrder['order_status'],
                'num' => $businessOrder['num'],
                'price' => $businessOrder['price'],
                'desc' => $businessOrder['desc'],
            ];
            $rs['income_details'] = [
                "freight_charge" => get_format_number($supplyOrder['freight_charge']),
                "plat_service_charge" => get_format_number($supplyOrder['freight_charge'] - $supplyOrder['deliver_user_fee']),
                "fee" => get_format_number($supplyOrder['tip_price']),
                "income" => get_format_number($supplyOrder['deliver_user_fee'] + $supplyOrder['tip_price'])
            ];
        } else if($supplyOrder['item'] == 3){
            $order = (new ServiceUserPublishService())->getInfoForDeliverList($supplyOrder['order_id']);
            if (empty($order)) {
                throw new Exception(L_('业务订单不存在'));
            }
            $businessOrder = $order['order'];
            $fc = $businessOrder['basic_distance_price'] + $businessOrder['weight_price'] + $businessOrder['distance_price'];//基础配送费+超重费用+超距离配送
            $supplyOrder['deliver_user_fee'] = $rs['deliver_user_fee'] = $supplyOrder['deliver_status'] > 1 ? $supplyOrder['deliver_user_fee'] : get_format_number($fc - ($fc * ($nowUser['take_percent'] / 100)));//配送员所得费用（扣除平台抽成）

            $fixExpectTime = strtotime($order['expect_use_time']);
            if ($supplyOrder['appoint_time'] == 0) {
                $supplyOrder['appoint_time'] = $fixExpectTime;
            }
            if ($supplyOrder['order_out_time'] == 0) {
                $supplyOrder['order_out_time'] = $fixExpectTime;
            }
            $rs['fetch_number'] = 0;
            if ($supplyOrder['deliver_status'] == 1) {
                $rs['is_new_order'] = true;
            }
            $rs['is_voice'] = $businessOrder['is_voice'] ? true : false;
            $rs['voice_url'] = replace_file_domain($businessOrder['voice_url']);
            $rs['voice_time'] = $businessOrder['voice_time'];


            //取件地址距离重置
            if ($nowUser && isset($order['pick_address']['miles']) && $order['pick_address']['miles'] != '') {
                $pickDistance = get_distance($nowUser['now_lat'], $nowUser['now_lng'], $supplyOrder['from_lat'], $supplyOrder['from_lnt']);
                $order['pick_address']['miles'] = $pickDistance >= 1000 ? round($pickDistance / 1000, 2) . 'km' : $pickDistance . 'm';
            }

            $rs['pick_address'] = $order['pick_address'];
            $rs['user_address'] = $order['user_address'];
            $rs['phone_lists'] = $order['phone_lists'];

            $isVoice = $businessOrder['is_voice'] ?? 0;
            $endAddressName = $businessOrder['end_adress_name'] ?? '';
            $prefectOrderInfo = ($isVoice == 1 && empty($endAddressName));
            $rs['btns'] = $this->getBtnsByDeliverStatus($supplyOrder['deliver_status'], 'detail',$isTransferToUser, $supplyOrder['is_fetch_order'],$uid,$prefectOrderInfo);

            $rs['satus_show'] = new \stdClass();

            //状态 1正常，2已支付待服务，3以服务待确认，4订单完成，5用户申请退款，6退款成功订单关闭，7服务已完成评价成功 8等待取货 9配送中，10用户已取消,11服务过期，12过期退款, 13 退款失败
            if (in_array($businessOrder['status'], [5, 6, 10, 11])) {
                $rs['satus_show'] = ['type' => 2, 'value' => ['status' => L_('平台取消订单')]];
            } else if ($supplyOrder['deliver_status'] == 1) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['appoint_time'] - $tm), 'time_left_type' => L_('送达')]];
            } else if ($supplyOrder['deliver_status'] == 2 || $supplyOrder['deliver_status'] == 3) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['order_out_time'] - $tm), 'time_left_type' => L_('取货')]];
            } else if ($supplyOrder['deliver_status'] == 4) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['appoint_time'] - $tm), 'time_left_type' => L_('送货')]];
            } else if ($supplyOrder['deliver_status'] == 5) {
                //等待接单
                $rs['satus_show'] = ['type' => 3, 'value' => ['expect_arrive_second' => intval($supplyOrder['appoint_time'] - $supplyOrder['create_time']), 'real_arrive_second' => intval($supplyOrder['end_time'] - $supplyOrder['create_time'])]];
            }

            //特殊要求
            $rs['special'] = [];

            $rs['service_goods'] = [];
            $rs['service_goods']['fee'] = get_format_number($businessOrder['tip_price']??0);
            $rs['service_goods']['goods_catgory'] = $businessOrder['goods_catgory'] ?? '';
            $rs['service_goods']['weight'] = get_format_number(floatval($businessOrder['weight']));
            $rs['service_goods']['weight_price'] = get_format_number($businessOrder['weight_price']??0);
            $rs['service_goods']['price'] = get_format_number(floatval($businessOrder['estimate_goods_price'] ?? $businessOrder['total_price']));
            $rs['service_goods']['pics'] = array_filter(explode(';', $businessOrder['img']));
            $rs['service_goods']['remarks'] = $businessOrder['remarks'] ?? '';

            //时间点
            $rs['status_list'] = [
                ["txt" => L_("接单时间"), "time" => $supplyOrder['grab_time'] > 0 ? date('H:i', $supplyOrder['grab_time']) : '--'],
                ["txt" => L_("实际取货"), "time" => $supplyOrder['pick_time'] > 0 ? date('H:i', $supplyOrder['pick_time']) : '--'],
                ["txt" => L_("实际送达"), "time" => $supplyOrder['finish_time'] > 0 ? date('H:i', $supplyOrder['finish_time']) : '--'],
            ];

            if($businessOrder['catgory_type'] == 2){
                //帮我买
                $orderType = 'service_buy';
                $orderTypeStr = L_('同城-帮买');
            }else{
                //帮我送
                $orderType = 'service_send';
                $orderTypeStr = L_('同城-帮送');
            }

            $rs['order'] = [
                'order_type' => $orderType,
                'order_type_str' => $orderTypeStr,
                'order_id' => $businessOrder['publish_id'],
                'real_orderid' => $businessOrder['order_sn'],
                'expect_use_time' => $businessOrder['fetch_time'],
                'short_expect_use_time' => L_('X1前', ['X1' => date('H:i', strtotime($businessOrder['fetch_time']))]),
                'status' => $businessOrder['status'],
                'num' => 1,
                'price' => $businessOrder['total_price'],

            ];
            $rs['income_details'] = [
                "freight_charge" => get_format_number($supplyOrder['freight_charge']),
                "plat_service_charge" => get_format_number($supplyOrder['freight_charge'] - $supplyOrder['deliver_user_fee']),
                "fee" => get_format_number($supplyOrder['tip_price']),
                "income" => get_format_number($supplyOrder['deliver_user_fee'] + $supplyOrder['tip_price'])
            ];
        }else if ($supplyOrder['item'] == 4 || $supplyOrder['item'] == 5) {
            //商城订单
            $isPeriodic = $supplyOrder['item'] == 4 ? 0 : 1;
            $order = (new MallOrderService())->getDeliverDetail($supplyOrder['order_id'], $isPeriodic);
            if (empty($order)) {
                throw new Exception(L_('业务订单不存在'));
            }
            $supplyOrder['deliver_user_fee'] = $rs['deliver_user_fee'] = $supplyOrder['deliver_status'] > 1 ? $supplyOrder['deliver_user_fee'] : get_format_number($supplyOrder['freight_charge'] - ($supplyOrder['freight_charge'] * ($nowUser['take_percent'] / 100)));
            $businessOrder = $order['order'];
            $rs['fetch_number'] = intval($businessOrder['fetch_number']??0);
            if ($supplyOrder['deliver_status'] == 1) {
                $rs['is_new_order'] = true;
            }
            //取件地址距离重置
            if ($nowUser) {
                $pickDistance = get_distance($nowUser['now_lat'], $nowUser['now_lng'], $supplyOrder['from_lat'], $supplyOrder['from_lnt']);
                $order['pick_address']['miles'] = $pickDistance >= 1000 ? round($pickDistance / 1000, 2) . 'km' : $pickDistance . 'm';
            }
            $rs['pick_address'] = $order['pick_address'];
            $rs['user_address'] = $order['user_address'];
            $rs['phone_lists'] = $order['phone_lists'];
            $rs['btns'] = $this->getBtnsByDeliverStatus($supplyOrder['deliver_status'], 'detail', $isTransferToUser, $supplyOrder['is_fetch_order'],$uid);

            $rs['goods'] = (new MallOrderService())->getGoodsByOrderId($supplyOrder['order_id'], 'goods_id,name,num,price,sku_info AS spec',$isPeriodic);
            $goodsCount = array_sum(array_column($rs['goods'],'num'));

            $rs['satus_show'] = new \stdClass();
            if (in_array($businessOrder['status'], [4, 5, 11])) {
                $rs['satus_show'] = ['type' => 2, 'value' => ['status' => L_('订单已取消')]];
            } else if ($supplyOrder['deliver_status'] == 1) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['appoint_time'] - $tm), 'time_left_type' => L_('送达')]];
            } else if ($supplyOrder['deliver_status'] == 2 || $supplyOrder['deliver_status'] == 3) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['order_out_time'] - $tm), 'time_left_type' => L_('取货')]];
            } else if ($supplyOrder['deliver_status'] == 4) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['appoint_time'] - $tm), 'time_left_type' => L_('送货')]];
            } else if ($supplyOrder['deliver_status'] == 5) {
                //等待接单
                $rs['satus_show'] = ['type' => 3, 'value' => ['expect_arrive_second' => intval($supplyOrder['appoint_time'] - $supplyOrder['create_time']), 'real_arrive_second' => intval($supplyOrder['end_time'] - $supplyOrder['create_time'])]];
            }

            //特殊要求
            $rs['special'] = [];
            if ($businessOrder['remark']) {
                $rs['special'][] = ['title' => L_('备注'), 'value' => $businessOrder['remark']];
            }

            //时间点
            $rs['status_list'] = [
                ["txt" => L_("抢单"), "time" => $supplyOrder['grab_time'] > 0 ? date('H:i', $supplyOrder['grab_time']) : '--'],
                ["txt" => L_("到店"), "time" => $supplyOrder['report_arrive_store_time'] > 0 ? date('H:i', $supplyOrder['report_arrive_store_time']) : '--'],
                ["txt" => L_("取货"), "time" => $supplyOrder['pick_time'] > 0 ? date('H:i', $supplyOrder['pick_time']) : '--'],
                ["txt" => L_("送达"), "time" => $supplyOrder['finish_time'] > 0 ? date('H:i', $supplyOrder['finish_time']) : '--'],
            ];
            $rs['order'] = [
                'order_type' => 'shop',
                'order_type_str' => cfg('mall_alias_name'),
                'order_id' => $businessOrder['order_id'],
                'real_orderid' => $businessOrder['order_no'],
                'expect_use_time' => date('Y-m-d H:i:s', $order['expect_use_time']),
                'short_expect_use_time' => L_('X1前', ['X1' => date('H:i', $order['expect_use_time'])]),
                'status' => $businessOrder['status'],
                'order_status' => $businessOrder['status'],
                'num' => $goodsCount,
                'price' => $supplyOrder['money'],
                'desc' => $businessOrder['remark'],
            ];
            $rs['income_details'] = [
                "freight_charge" => get_format_number($supplyOrder['freight_charge']),
                "plat_service_charge" => get_format_number($supplyOrder['freight_charge'] - $supplyOrder['deliver_user_fee']),
                "fee" => get_format_number($supplyOrder['tip_price']),
                "income" => get_format_number($supplyOrder['deliver_user_fee'] + $supplyOrder['tip_price'])
            ];
        }else if($supplyOrder['item'] == 8){
            //社区团购订单
            $order = (new VillageGroupOrderService())->getInfoForDeliverList($supplyOrder['order_id']);
            if (empty($order)) {
                throw new Exception(L_('业务订单不存在'));
            }
            $supplyOrder['deliver_user_fee'] = $rs['deliver_user_fee'] = $supplyOrder['deliver_status'] > 1 ? $supplyOrder['deliver_user_fee'] : get_format_number($supplyOrder['freight_charge'] - ($supplyOrder['freight_charge'] * ($nowUser['take_percent'] / 100)));
            $businessOrder = $order['order'];
            $rs['fetch_number'] = 0;
            if ($supplyOrder['deliver_status'] == 1) {
                $rs['is_new_order'] = true;
            }
            //取件地址距离重置
            if ($nowUser) {
                $pickDistance = get_distance($nowUser['now_lat'], $nowUser['now_lng'], $supplyOrder['from_lat'], $supplyOrder['from_lnt']);
                $order['pick_address']['miles'] = $pickDistance >= 1000 ? round($pickDistance / 1000, 2) . 'km' : $pickDistance . 'm';
            }
            $rs['pick_address'] = $order['pick_address'];
            $rs['user_address'] = $order['user_address'];
            $rs['phone_lists'] = $order['phone_lists'];
            $rs['btns'] = $this->getBtnsByDeliverStatus($supplyOrder['deliver_status'], 'detail', $isTransferToUser, $supplyOrder['is_fetch_order'],$uid);
            
            $rs['goods'] = (new VillageGroupOrderService())->getGoodsByOrderId($supplyOrder['order_id'],'goods_id,name,num,price,"" AS spec');
            $rs['satus_show'] = new \stdClass();
            if ($supplyOrder['deliver_status'] >= 1 && $supplyOrder['deliver_status'] < 6) {
                //等待接单
                $rs['satus_show'] = ['type' => 1, 'value' => ['time_left_second' => intval($supplyOrder['appoint_time'] - $tm), 'time_left_type' => L_('送达')]];
            } else {
                $rs['satus_show'] = ['type' => 2, 'value' => ['status' => $businessOrder['status_txt']]];
            }
            if ($businessOrder['note']) {
                $rs['special'][] = ['title' => L_('备注'), 'value' => $businessOrder['note']];
            }
          

            //时间点
            $rs['status_list'] = [
                ["txt" => L_("抢单"), "time" => $supplyOrder['grab_time'] > 0 ? date('H:i', $supplyOrder['grab_time']) : '--'],
                ["txt" => L_("到店"), "time" => $supplyOrder['report_arrive_store_time'] > 0 ? date('H:i', $supplyOrder['report_arrive_store_time']) : '--'],
                ["txt" => L_("取货"), "time" => $supplyOrder['pick_time'] > 0 ? date('H:i', $supplyOrder['pick_time']) : '--'],
                ["txt" => L_("送达"), "time" => $supplyOrder['finish_time'] > 0 ? date('H:i', $supplyOrder['finish_time']) : '--'],
            ];
         

            // 获取订单完成时间 判断是否显示收货地址
            $is_user_address = true;
            $businessOrderTime = $supplyOrder['finish_time'] ?? $supplyOrder['end_time'];
            if(!empty($businessOrderTime)){
                $businessOrderDateline = $businessOrderTime + (60*60*6);
                $is_user_address = $businessOrderDateline > time() ? true : false;
            }
          
            $rs['is_user_address'] = $is_user_address;

            $rs['order'] = [
                'order_type' => 'village_group',
                'order_type_str' => '社区团购',
                'order_id' => $businessOrder['order_id'],
                'real_orderid' => $businessOrder['real_orderid'],
                'expect_use_time' => date('Y-m-d H:i:s', $businessOrder['expect_send_time']),
                'short_expect_use_time' => L_('X1前', ['X1' => date('H:i', $businessOrder['expect_send_time'])]),
                'status' => $businessOrder['status'],
                'order_status' => $businessOrder['order_status'],
                'num' => array_sum(array_column($rs['goods'], 'num')),
                'price' => $businessOrder['total_money'],
                'desc' => $businessOrder['note'],
            ];
            $rs['income_details'] = [
                "freight_charge" => get_format_number($supplyOrder['freight_charge']),
                "plat_service_charge" => get_format_number($supplyOrder['freight_charge'] - $supplyOrder['deliver_user_fee']),
                "fee" => get_format_number($supplyOrder['tip_price']),
                "income" => get_format_number($supplyOrder['deliver_user_fee'] + $supplyOrder['tip_price'])
            ];
        }else {
            throw new Exception(L_('非外卖业务订单详情暂未适配'));
        }
        $rs['transfer_order_expire_time'] = 180;

        if ($supplyOrder['deliver_status'] < 4 && cfg('deliver_see_freight_charge') == 0) {
            $rs['deliver_user_fee'] = '--';
            $rs['income_details'] = [
                "freight_charge" => '--',
                "plat_service_charge" => '--',
                "fee" => '--',
                "income" => 0,
            ];
        }
        return $rs;
    }

    /**
     * 获取一条记录
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function getOne($where, $fields = '*')
    {
        return $this->deliverSupplyMod->where($where)->field($fields)->findOrEmpty()->toArray();
    }


    /**
     * 配送单转单超时更新
     * @param $supplyId
     * @author: 张涛
     * @date: 2020/9/27
     */
    public function transferExpiredUpdae($supplyId)
    {
        $data = [
            'transfer_from_uid' => 0,
            'transfer_deliver_status' => 0,
            'transfer_to_uid' => 0,
            'transfer_accept_time' => 0,
            'transfer_refuse_time' => 0,
            'transfer_status' => 0,
            'transfer_time' => 0
        ];
        $this->deliverSupplyMod->where(['supply_id' => $supplyId])->update($data);
    }


    /**
     * 写入配送表
     * @author: 张涛
     * @date: 2020/11/17
     */
    public function saveOrder($supply)
    {
        /*$supply = [
            'order_from' => 8,  //新版商城固定传8
            'order_id' => 1,  //商城订单主键id
            'paid' => 1,  //是否支付  1：已支付  0：未支付
            'pay_time' => time(),  //支付时间
            'real_orderid' => '202011170569566',  //商场订单长订单号
            'pay_type' => 'alipay',  //支付方式
            'money' => 100,//应收金额
            'deliver_cash' => 0, //配送员应收现金
            'store_id' => 429, //店铺ID
            'store_name' => '张涛专属测试店铺',//店铺名称
            'mer_id' => 805,//商家ID
            'from_site' => '亚夏大厦',//店铺地址
            'from_lnt' => '118.2212',//来源地经度
            'from_lat' => '125.2123',//来源地纬度
            'province_id' => 1,//店铺所在省份id
            'city_id' => 2,//店铺所在城市id
            'area_id' => 3,//店铺所在区域id
            'aim_site' => '这是收货地址字段',//收货地址
            'aim_lnt' => '118.2212',//收货地经度
            'aim_lat' => '125.2123',//收货地纬度
            'name' => '张涛',//收货人名称
            'phone' => '15521092484',//收货人手机号
            'fetch_number' => 0, //取单号
            'type' => 0,//配送方式   0 系统配送 1商家配送
            'item' => 4,
            'order_out_time' => time(), //预计出单时间
            'appoint_time' => time(), //期望送达时间
            'note' => '', //备注
            'is_right_now' => 1, //是否立即送达
            'order_time' => time(),  //订单下单时间
            'freight_charge' => 10.23, //配送费
            'distance' => 12, //距离，单位：km
            'virtual_phone' => '15521092484', //虚拟手机号
            'virtual_phone_overtime' => time(), //虚拟手机号过期时间
        ];*/

        $supply['status'] = 1;
        $supply['deliver_status'] = 1;
        $supply['create_time'] = time();
        $id = $this->deliverSupplyMod->insertGetId($supply);
        if ($id < 1) {
            return false;
        } else {
            invoke_cms_model('Deliver_supply/sendMsg', ['supply' => $supply], true);
            return true;
        }
    }


    /**
     * 获取配送时间段
     * @param $fromLocation  起点经纬度  lat lng
     * @param $toLocation  终点经纬度  lat lng
     * @param $horseman_type //1-平台配送员，2-商家配送员
     * @param $mer_id //商家id（$horseman_type为2时必传）
     * @return array
     * @author: 张涛
     * @date: 2020/12/1
     */
    public function getDeliverTimeSetting($fromLocation, $toLocation, $horseman_type=1, $mer_id = 0)
    {
        //判断是否可配送
        $isPositionInDeliverRange = (new DeliverUserService())->isPositionInDeliverRange($fromLocation['lat'], $fromLocation['lng'], $horseman_type, $mer_id);

        //计算距离
        $speed = cfg('deliver_speed_hour');
        $delay = cfg('deliver_delay_time');
        $defaultSendTime = cfg('deliver_send_time');
        if (cfg('is_riding_distance') || cfg('map_config') == 'google') {
            $longlatClass = new longLat();
            $distance = $longlatClass->getRidingDistance($fromLocation['lat'], $fromLocation['lng'], $toLocation['lat'], $toLocation['lng']);
            if ($distance == -1) {
                throw new Exception(L_('请求不到您的具体定位，请重试！'));
            }
        }
        isset($distance) || $distance = get_distance($fromLocation['lat'], $fromLocation['lng'], $toLocation['lat'], $toLocation['lng']);
        $distanceKm = $distance / 1000;

        $time1 = explode('-', cfg('delivery_time'));
        $time2 = explode('-', cfg('delivery_time2'));
        $time3 = explode('-', cfg('delivery_time3'));

        $timeLists = [];
        if ($time1 && cfg('delivery_time')!='-' && $time1[0] != $time1[1]) {
            $timeLists[] = [
                'start_time' => $time1[0] . ':00', //开始时间
                'end_time' => $time1[1] . ':59',  //结束时间
                'basic_price' => cfg('basic_price1'),  //起送价
                'basic_distance' => cfg('basic_distance'), //起步距离
                'delivery_fee' => cfg('delivery_fee'), //起步配送费
                'per_km_price' => cfg('per_km_price')  //每公里的配送费
            ];
        }
        if ($time2 && cfg('delivery_time2') != '-' && $time2[0] != $time2[1]) {
            $timeLists[] = [
                'start_time' => $time2[0] . ':00',
                'end_time' => $time2[1] . ':59',
                'basic_price' => cfg('basic_price2'),
                'basic_distance' => cfg('basic_distance2'),
                'delivery_fee' => cfg('delivery_fee2'),
                'per_km_price' => cfg('per_km_price2')
            ];
        }
        if ($time3 && cfg('delivery_time3') != '-' && $time3[0] != $time3[1]) {
            $timeLists[] = [
                'start_time' => $time3[0] . ':00',
                'end_time' => $time3[1] . ':59',
                'basic_price' => cfg('basic_price3'),
                'basic_distance' => cfg('basic_distance3'),
                'delivery_fee' => cfg('delivery_fee3'),
                'per_km_price' => cfg('per_km_price3')
            ];
        }
        
        $return = [
            'status' => $isPositionInDeliverRange,  //true=能够支持送达 false=不能够支持送达
            'time' => $speed > 0 ? round(($distanceKm / $speed) * 60 + $delay, 2) : $defaultSendTime, //预计配送时间 单位：分
            'business_time_list' => $timeLists
        ];
        return $return;
    }


    /**
     * 计算运费
     * @param $fromLocation  起点经纬度  lat lng
     * @param $toLocation  终点经纬度  lat lng
     * @param $expectUnixTime  送达时间  格式：Y-m-d H:i:s
     * @return int
     * @author: 张涛
     * @date: 2020/12/1
     */
    public function calculationFreight($fromLocation, $toLocation, $expectTime, $returnType = 0)
    {
        $freight = 0;
        $expectUnix = strtotime($expectTime);
        $day = date('Y-m-d',$expectUnix);

        //计算距离
        if (cfg('is_riding_distance') || cfg('map_config') == 'google') {
            $longlatClass = new longLat();
            $distance = $longlatClass->getRidingDistance($fromLocation['lat'], $fromLocation['lng'], $toLocation['lat'], $toLocation['lng']);
            if ($distance == -1) {
                throw new Exception(L_('请求不到您的具体定位，请重试！'));
            }

            if ($distance == 0 && 500 < get_distance($fromLocation['lat'], $fromLocation['lng'], $toLocation['lat'], $toLocation['lng'])) {
                throw new Exception(L_('当前地址不在配送范围内'));
            }
        }
        isset($distance) || $distance = get_distance($fromLocation['lat'], $fromLocation['lng'], $toLocation['lat'], $toLocation['lng']);
        $distanceKm = $distance / 1000;

        //计算运费
        $config = [
            '1' => ['delivery_time', 'basic_price1', 'basic_distance', 'delivery_fee', 'per_km_price'],
            '2' => ['delivery_time2', 'basic_price2', 'basic_distance2', 'delivery_fee2', 'per_km_price2'],
            '3' => ['delivery_time3', 'basic_price3', 'basic_distance3', 'delivery_fee3', 'per_km_price3'],
        ];
        foreach ($config as $k => $v) {
            $time = explode('-', cfg($v[0]));
            if (isset($time[1]) && $time[1] == '00:00') {
                $time[1] = '23:59';
            }
            $start = strtotime($day.' '.$time[0] . ':00');
            $end = strtotime($day.' '.$time[1] . ':59');
            if ($expectUnix >= $start && $expectUnix <= $end) {
                $basicDistance = cfg($v[2]);
                $basicDistance = $basicDistance ?: 0;
                $overDistance = max($distanceKm - $basicDistance, 0);
                $freight = (cfg($v[3]) ?: 0) + (cfg($v[4]) ?: 0) * $overDistance;
                break;
            }
        }
        $freight > 0 && $freight = format_number_by_config($freight);
        if($returnType === 0){
            return $freight;
        }
        else{
            return ['freight' => $freight, 'distance' => $distance];   
        }
    }


    /**
     * 抢单
     * @author: 张涛
     * @date: 2021/03/02
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

        $deliverUser = [
            'id' => $uid,
            'name' => $user['name'],
            'phone' => $user['phone'],
            'store_id'=>$supply['store_id']
        ];

        //业务后置操作
        if($supply['item'] == 4 || $supply['item'] == 5){
            //新版商城
            (new MallOrderService())->housemanOrderLog($supply['item'] == 4 ? 'order' : 'periodic', 1, $supply['order_id'], $user['now_lng'], $user['now_lat'], $deliverUser);
            //增加消息通知
            (new UserNoticeService())->addNotice([
                'type'=>0,
                'business'=>'mall',
                'order_id'=>$supply['order_id'],
                'title'=>'骑手正在配送',
                'content'=>'骑手正在配送，商品将尽快为你送达',
            ]);
        }else if($supply['item'] == 8){
            //社区团购
            //增加消息通知
            (new UserNoticeService())->addNotice([
                'type'=>0,
                'business'=>'village_group',
                'order_id'=>$supply['order_id'],
                'title'=>'骑手正在配送',
                'content'=>'骑手正在配送，商品将尽快为你送达',
            ]);
            (new VillageGroupOrderService())->afterGrap($supply['order_id'],$deliverUser);
        }
    }

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
        $deliverUser = [
            'id' => $uid,
            'name' => $user['name'],
            'phone' => $user['phone'],
        ];
        if($supply['item'] == 4 || $supply['item'] == 5){
            (new MallOrderService())->housemanOrderLog($supply['item'] == 4 ? 'order' : 'periodic', 2, $supply['order_id'], $user['now_lng'], $user['now_lat'], $deliverUser);
        }else if($supply['item'] == 8){
            //社区团购
            (new VillageGroupOrderService())->afterReportArriveStore($supply['order_id'],$deliverUser);
        }
        
    }

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
        $deliverUser = [
            'id' => $uid,
            'name' => $user['name'],
            'phone' => $user['phone'],
        ];
        if($supply['item'] == 4 || $supply['item'] == 5){
            (new MallOrderService())->housemanOrderLog($supply['item'] == 4 ? 'order' : 'periodic', 3, $supply['order_id'], $user['now_lng'], $user['now_lat'], $deliverUser);
        }else if($supply['item'] == 8){
            //社区团购
            (new VillageGroupOrderService())->afterPickOrder($supply['order_id'],$deliverUser);
        }
    }

    public function finishOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
        if ($supply['status'] != 4) {
            throw new Exception(L_('当前状态不能修改成完成状态！'));
        }
        $columns = [
            'uid' => $uid,
            'status' => 5,
            'deliver_status' => 5,
            'end_time' => time(),
            'finish_time' => time(),
            'is_settlement'=>1
        ];
        $result = (new DeliverSupply())->where(['uid' => $uid, 'supply_id' => $supplyId, 'status' => 4])->update($columns);
        if (!$result) {
            throw new Exception(L_('更新配送信息失败！'));
        }

        //统计每日配送量
        (new DeliverUserService())->updateDeliverUserTotalNum($uid);
        (new DeliverUserService())->updateDeliverUserNumByDate($uid, date('Ymd'));

        $deliverUser = [
            'id' => $uid,
            'name' => $user['name'],
            'phone' => $user['phone'],
        ];

        if($supply['item'] == 4 || $supply['item'] == 5){
            (new MallOrderService())->housemanOrderLog($supply['item'] == 4 ? 'order' : 'periodic', 4, $supply['order_id'], $user['now_lng'], $user['now_lat'], $deliverUser);
            if($supply['is_settlement'] == 0){
                (new DeliverUserService())->addMoney($supply['uid'], 'mall', $supply['order_id'], $supply['deliver_user_fee'], '配送收入', $supply['tip_price'], '小费收入'); 
            }
            
            //增加消息通知
            (new UserNoticeService())->addNotice([
                'type'=>0,
                'business'=>'mall',
                'order_id'=>$supply['order_id'],
                'title'=>'商品已经送达',
                'content'=>'您的商城商品已经送达，点击查看订单详情',
            ]);
        }else if($supply['item'] == 8){
            //社区团购
            $res = (new VillageGroupOrderService())->afterFinishOrder($supply['order_id'],$deliverUser);
            if($res && $supply['is_settlement'] == 0){
                //增加配送员配送费
                (new DeliverUserService())->addMoney($supply['uid'], 'village_group', $supply['order_id'], $supply['deliver_user_fee'], '配送收入', $supply['tip_price'], '小费收入');  
            }
            
            //增加消息通知
            (new UserNoticeService())->addNotice([
                'type'=>0,
                'business'=>'village_group',
                'order_id'=>$supply['order_id'],
                'title'=>'商品已经送达',
                'content'=>'您的社区团购商品已经送达，点击查看订单详情',
            ]);
        }

        /*if (cfg('open_deliver_level') && $user['group'] == 1) {
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
        }*/
    }

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

    public function acceptTransferOrder($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where(['supply_id' => $supplyId, 'transfer_to_uid' => $uid])->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception(L_('转单记录不存在'), Code::TRANSFER_ORDER_NOT_EXIST);
        }

        //超时了
        if (time() > $supply['transfer_time'] + config('const.refuse_transfer_order_expire')) {
            (new DeliverSupplyService())->transferExpiredUpdae($supplyId);
            throw new Exception(L_('转单超时'), Code::TRANSFER_ORDER_EXPIRED);
        } else {
            $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();
            //转单后重新计算佣金
            $deliver_user_fee = $supply['freight_charge'] - ($supply['freight_charge'] * ($user['take_percent'] / 100));
            $data = [
                'uid' => $supply['transfer_to_uid'],
                'transfer_accept_time' => time(),
                'transfer_status' => 1,
                'deliver_user_fee'  =>  $deliver_user_fee
            ];
            (new DeliverSupply())->where(['supply_id' => $supplyId])->update($data);
        }
    }

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
            'uid' => $supply['transfer_from_uid'] > 0 ? $supply['transfer_from_uid'] : 0
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
            'business'=>'mall',
            'order_id'=>$supply['order_id'],
            'title'=>'骑手正在配送',
            'content'=>'骑手正在配送，商品将尽快为你送达',
        ]);
        if($supply['item'] == 4 || $supply['item'] == 5){
            $deliverUser = [
                'id' => $uid,
                'name' => $nowUser['name'],
                'phone' => $nowUser['phone'],
            ];
            (new MallOrderService())->housemanOrderLog($supply['item'] == 4 ? 'order' : 'periodic', 1, $supply['order_id'], $nowUser['now_lng'], $nowUser['now_lat'], $deliverUser);
        }
    }


    /**
     * 获取未读消息数
     *
     * @param int $uid
     * @param int $supplyId
     * @date: 2021/09/01
     */
    public function getImUnReadMsg($uid, $supplyId)
    {
        $rs = ['im_unread_message_count' => 0];
        if ($uid) {
            $imUser = 'deliver_' . $uid;
            $where = [['m.uuid', '=', $imUser]];

            if ($supplyId > 0) {
                $supplyOrder = (new DeliverSupply())->where(['supply_id' => $supplyId, 'uid' => $uid])->find();
                if (empty($supplyOrder)) {
                    throw new Exception(L_('配送单不存在'));
                }
                switch ($supplyOrder->item) {
                    case 2:
                        $where[] = ['business_type', '=', 'shop'];
                        break;
                    case 3:
                        $where[] = ['business_type', '=', 'paotui'];
                        break;
                    default:
                        break;
                }
                $where[] = ['business_order_id', '=', $supplyOrder['order_id']];
            }
            $count = Db::name('im_group_chat_members')->alias('m')
                ->join('im_group_chat c', 'c.group_id=m.group_id')
                ->where($where)
                ->sum('m.unread_msg_count');
            $rs['im_unread_message_count'] = intval($count);
        }
        return $rs;
    }
}
