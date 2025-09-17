<?php
/**
 * 店员扫码
 * Author: hengtingmei
 * Date Time: 2020/12/10 10:13
 */

namespace app\storestaff\model\service;

use app\common\model\service\coupon\MerchantCouponService;
use app\employee\model\service\EmployeePayCodeService;
use app\life_tools\model\db\LifeToolsCardOrder;
use app\life_tools\model\db\LifeToolsCardOrderRecord;
use app\life_tools\model\db\LifeToolsCardTools;
use app\life_tools\model\db\LifeToolsGroupOrder;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderBindSportsActivity;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\service\LifeToolsAppointService;
use app\life_tools\model\service\LifeToolsCardOrderService;
use app\life_tools\model\service\LifeToolsOrderService;
use app\life_tools\model\service\LifeToolsTicketService;
use app\life_tools\model\service\ScenicOpenPftService;
use app\mall\model\service\MallOrderService;
use app\merchant\model\db\CardNewDepositGoods;
use app\merchant\model\db\CardNewDepositGoodsBindUser;
use app\merchant\model\service\card\CardGoodsService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\order\TmpPayidService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;

use think\Exception;
use token\Token;
use think\facade\Db;
use app\life_tools\model\db\LifeToolsCard;

class ScanService {
    /**
     * 首页扫码
     * @param $param array
     * Author: hengtingmei
     * @return array
     */
    public function indexScan($param, $staffUser){
        $code = $param['code'] ?? '';//	扫码获得的数据

        $returnArr = [
            'operate_type' => 'url',
            'url' => '',
        ];

        if(empty($code)){
            throw new \think\Exception(L_('您扫描的内容暂时无法识别'),1003);
        }

        Db::startTrans();
        try {
            if (stripos($code,'shop') !== false && stripos($code,'&') === false){
                //店员扫用户支付成功页面进来
                $data = explode('_',$code);
                $orderId = $data[1];
                $returnArr['url'] = cfg('site_url').'/packapp/storestaff/shop_detail.html?order_id='.$orderId;
                // 跳转小程序
                $appVersion = request()->param('app_version') ?? '';
                if($appVersion >= '30200'){
                    $returnArr['url'] = 'pages/shop/order/orderSearch';
                    $returnArr['operate_type'] = 'miniapp';
                    // 设备id
                    $deviceId = request()->param('Device-Id') ?? '';
                    // 获得老店员app ticket
                    $ticket = Token::createToken($staffUser['id']);
                    $params = [
                        'device_id' => $deviceId,
                        'order_id' => $orderId,
                        'ticket' => $ticket,
                        'domain' => cfg('site_url'),
                    ];
                    $returnArr['app_data'] = [
                        'appid' => '__UNI__8799035',
                        'path' => 'pages/shop/order/orderSearch',
                        'arguments' => json_encode($params),
                    ];
                }
            }elseif(stripos($code,'scenic') !== false && stripos($code,'&') === false){
                // 店员扫景区订单
                $data = explode('_',$code);
                $scenic_order_id = $data[1];
                $returnArr['url'] = cfg('site_url').'/packapp/storestaff/ticket_order_details.html?order_id='.$scenic_order_id;
            }elseif(stripos($code,'cardgoods') !== false && stripos($code,'&') === false){
                // 店员扫积分兑换商品二维码
                $data = explode('_',$code);
                $sign = $data[1];
                //去核销
                (new CardGoodsService())->useOrder([
                    'staff_id'=>$staffUser['id'],
                    'sign'=>$sign
                ]);
                $returnArr['url'] = cfg('site_url').'/packapp/storestaff/pointExchangeList.html';
            } else if (stripos($code,'deposit') !== false) {//商品寄存核销业务
                $returnArr['operate_type'] = 'deposit';
                $data = (new CardNewDepositGoodsBindUser())->where(['number' => $code, 'is_del' => 0])->find();
                if (empty($data)) {
                    throw new \think\Exception(L_('该商品不存在'),1003);
                }
                if ($data['start_time'] > time()) {
                    throw new \think\Exception(L_('该商品未开始'),1003);
                }
                if ($data['end_time'] + 86400 <= time()) {
                    throw new \think\Exception(L_('该商品已结束'),1003);
                }
                $data['real_num'] = $data['num'] - $data['use_num'];//未使用数量
                if ($data['real_num'] <= 0) {
                    throw new \think\Exception(L_('该商品已全部核销'),1003);
                }
                $goodsData = (new CardNewDepositGoods())->where(['goods_id' => $data['goods_id']])->field('name as goods_name,image')->find();
                $returnArr['data'] = [
                    'id'         => $data['id'],
                    'start_time' => date('Y.m.d', $data['start_time']),
                    'end_time'   => date('Y.m.d', $data['end_time']),
                    'real_num'   => $data['real_num'],
                    'image'      => !empty($goodsData['image']) ? replace_file_domain($goodsData['image']) : '',
                    'goods_name' => $goodsData['goods_name'] ?? '',
                ];
            } elseif (stripos($code, 'mercoupon_') !== false) {
                //商家优惠券核销  格式 mercoupon_领取记录ID_用户ID
                $arr = explode('_',$code);
                if (count($arr) != 3) {
                    throw new \think\Exception(L_('优惠券核销码有误'), 1003);
                }
                $hadpullId = $arr[1];
                $uid = $arr[2];
                //优惠券领取记录
                $hadpullMod = new \app\common\model\db\CardNewCouponHadpull();
                $hadpull = $hadpullMod->where('uid', $uid)
                    ->where('id', $hadpullId)
                    ->findOrEmpty();
                if ($hadpull->isEmpty()) {
                    throw new  Exception('优惠券核销码不存在');
                }
                if ($hadpull->is_use != 0) {
                    throw new  Exception('优惠券核销码已使用或已过期');
                }

                $thisCoupon = (new \app\common\model\service\coupon\MerchantCouponService())->getCouponInfoByCondition(['coupon_id' => $hadpull->coupon_id]);
                if(empty($thisCoupon)){
                    throw new  Exception('优惠券不存在');
                }
                $thisCoupon = $thisCoupon[0];
                $res['discount_des'] = $thisCoupon['discount_des'];
                $res['discount_money'] = $thisCoupon['discount_title'];
                $res['limit_date'] = date('Y-m-d H:i', $thisCoupon['end_time']);
                $res['hadpull_id'] = $hadpullId;
                $res['uid'] = $uid;
                $res['operate_type'] = 'mercoupon_verify';
                return $res;

            }elseif(strlen($code) == 14) {
                // 用户支付码
                $payInfo = (new TmpPayidService())->scanPayidCheck($code);
                if($payInfo && $payInfo['uid'] > 0){
                    $returnArr['url'] = cfg('site_url').'/packapp/storestaff/tcashier_set.html?uid='.$payInfo['uid'].'&from_scan=1&payid='.$payInfo['payid'];
                }
            } elseif(strlen($code) == 16) {
                // 活动预约订单表核销
                $params = [];
                $params['code'] = $code;
                $params['mer_id'] = $staffUser['mer_id'];
                $params['staff_id'] = $staffUser['id'];

                try {
                    $res = (new LifeToolsAppointService())->getAppointDetailByCode($params['code']);
                } catch (\Exception $e) {
                    throw new \think\Exception($e->getMessage(), 1003);
                }
                $res['operate_type'] = 'lifetools_appoint_popup';
                $res['msg'] = '';
                return $res;
                
                // if($res){
                //     return [
                //         "operate_type"  => "success",
                //         "msg"           => "核销成功"
                //     ];
                // }
            } elseif (strlen($code) == 21 || strlen($code)  == 22 || strlen($code)  == 24) {
                //扫码核销商城自提订单
//                $typeKey = substr($code, 0, 1);
//                if ($typeKey == 1) {
//                    $orderId = substr($code, 1);
//                    $returnArr['url'] = cfg('site_url') . '/packapp/storestaff/mall_detail.html?order_id=' . $orderId;
//                }
                //查询订单
                $orderInfo = (new MallOrderService())->getOrders(['order_no'=>$code]);
                if(!$orderInfo){
                    throw new \think\Exception(L_('未查询到商城订单'),1003);
                }
                if($orderInfo[0]['store_id']!=$staffUser['store_id']){
                    throw new \think\Exception(L_('未查询到商城订单'),1003);
                }
                $mallOrder = $orderInfo[0];
                if($mallOrder['status'] < 10){
                    throw_exception(L_('订单未支付，无法核销'));
                }else if($mallOrder['status'] < 11){
                    throw_exception(L_('订单未接单，无法核销！'));
                }
                if(cfg('mall_verify_type')){//自动核销
                    if($orderInfo && $orderInfo[0]['status'] >= 30 && $orderInfo[0]['status'] <= 40){
                        throw new \think\Exception(L_('订单已完成，请勿重复操作'),1003);
                    }else if($orderInfo && $orderInfo[0]['status'] == 70){
                            throw new \think\Exception(L_('订单已退款，无法核销'),1003);
                    }else if($orderInfo && $orderInfo[0]['status'] < 10){
                            throw new \think\Exception(L_('订单未支付，无法核销'),1003);
                    }else if($orderInfo && $orderInfo[0]['status'] < 60 && $orderInfo[0]['status'] >= 50){
                            throw new \think\Exception(L_('订单已取消，无法核销'),1003);
                    }else if($orderInfo){
                        $staffUserInfo = [
                            'status'=>3,
                            'staff_id'=>$staffUser['id'],
                            'staff_name'=>$staffUser['username']
                        ];
                        $note = '扫码核销,该笔订单已完成';
                        $verify = (new MallOrderService())->changeOrderStatus($orderInfo[0]['order_id'], 32, $note, [], 0, '','normal',$staffUserInfo);
                        if($verify){
                            $returnArr['url'] = cfg('site_url') . '/packapp/staff/#/pages/mall/order/orderDetail?order_id=' . $orderInfo[0]['order_id'];
                            $returnArr['msg'] = $note;
                        }
                    }
                }else{//手动核销
                    $returnArr['url'] = cfg('site_url') . '/packapp/staff/#/pages/mall/order/orderDetail?order_id=' . $orderInfo[0]['order_id'];
                }

                
                // 跳转小程序
                $appVersion = request()->param('app_version') ?? '';
                if($appVersion >= '30200'){
                    $returnArr['url'] = 'pages/mall/order/orderDetail?order_id=' . $orderInfo[0]['order_id'];
                    $returnArr['operate_type'] = 'miniapp';
                    // 设备id
                    $deviceId = request()->param('Device-Id') ?? '';
                    // 获得老店员app ticket
                    $ticket = Token::createToken($staffUser['id']);
                    $params = [
                        'device_id' => $deviceId,
                        'order_id' => $orderInfo[0]['order_id'],
                        'ticket' => $ticket,
                        'domain' => cfg('site_url'),
                    ];
                    $returnArr['app_data'] = [
                        'appid' => '__UNI__8799035',
                        'path' => 'pages/mall/order/orderDetail?order_id=' . $orderInfo[0]['order_id'],
                        'arguments' => json_encode($params),
                    ];
                }
            } elseif (strlen($code) == 18){
                //员工卡核销

                $typeKey = substr($code, 0, 1);

                //消费券
                if ($typeKey == 1) {

                    $params = [];
                    $staff = (new MerchantStoreStaffService())->getOne(['id'=>$staffUser['id']]);

                    $mer = (new MerchantStoreService())->getStoreInfo($staff['store_id']);
                    if(!empty($mer)){
                        $params['mer_id'] = $mer['mer_id'];
                    }
                    $params['staff_id'] = $staff['id'];
                    $params['staff'] = $staff;
                    $params['code'] = $code;
                    $params['score'] = 0;
                    $params['operate_type'] = 'staff';
                    $params['card_type'] = 'coupon';

                    $res = (new EmployeePayCodeService)->deductions($params);
                    if($res){
                        $returnArr['operate_type'] = "success";
                        $returnArr['msg'] = $res['title'] ?? "消费券核销成功";
                    } 

                }else{ //积分
                    $returnArr['operate_type'] = 'url';
                    $returnArr['url'] = cfg('site_url') . '/packapp/storestaff/employee_card_score_verify.html?code=' . $code;;
                    $returnArr['msg'] = '';
                }

            } else if (strlen($code) == 13) { //城投-景区次卡核销
                $returnArr['operate_type'] = 'success';
                $returnArr['msg'] = '核销成功';
//                if (empty($param['tools_id'])) {
//                    throw new \think\Exception(L_('景区ID必传'),1003);
//                }
                $data = (new LifeToolsCardOrder())->getAllDetail(['o.code' => $code]);
                if (empty($data)) {
                    throw new \think\Exception(L_('该核销码有误，请检查'),1003);
                }
                if ($data['mer_id'] != $staffUser['mer_id']) {
                    throw new \think\Exception(L_('权限不足'),1003);
                }
                // if($data['type'] == 'scenic'){
                    //查询拥有核销当前次卡权限的店员信息
                    $staffInfo = (new LifeToolsCardTools())->getStaffByCardId(['a.card_id'=>$data['card_id']],'b.staff_ids,b.mer_id')->toArray();
                    if(!$staffInfo){
                        throw new \think\Exception(L_('您没有核销当前门票订单的权限'),1003);
                    }
                    $staffIdAry = [];
                    $merIdAry = [];
                    foreach ($staffInfo as $vStaff){
                        $staffIdAry = $vStaff['staff_ids'] ? array_merge($staffIdAry,explode(',',trim($vStaff['staff_ids'],','))) : $staffIdAry;
                        if(!$vStaff['staff_ids']){
                            //当前商家下的所有店员都可以核销
                            $merIdAry[] = $vStaff['mer_id'];
                        }
                    }
                    if($merIdAry){
                        //查询这些商家的所有店员id
                        $allStaffInfo = (new MerchantStoreStaffService())->getStaffListByCondition([['token','IN',$merIdAry]], 'id', true);
                        $allStaffIdAry = [];
                        foreach ($allStaffInfo as $vStaffInfo){
                            $allStaffIdAry[] = $vStaffInfo['id'];
                        }
                        $staffIdAry = array_merge($staffIdAry,$allStaffIdAry);
                    }
                    if(!in_array($staffUser['id'],$staffIdAry)){
                        throw new \think\Exception(L_('您没有核销当前门票订单的权限'),1003);
                    }
                // }
                if (!in_array($data['order_status'], [20, 30])) {
                    throw new \think\Exception(L_('该次卡状态不支持核销，请检查'),1003);
                }
                if ($data['day_num'] > 0 && $data['day_num'] <= (new LifeToolsCardOrderRecord())->getCount([['order_id', '=', $data['order_id']], ['add_time', '>=', strtotime(date('Y-m-d'))]]) ?? 0) {
                    throw new \think\Exception(L_('该次卡今日核销次数已达最大限制'),1003);
                }
                if ($data['out_time'] < time() && $data['out_time'] != 0) {
                    (new LifeToolsCardOrderService())->changeOrderStatus($data['order_id'], 70, '次卡已过期（核销页）');
                    throw new \think\Exception(L_('该次卡已过期'),1003);
                }
                if (isset($param['tools_id']) && !in_array($param['tools_id'], (new LifeToolsCardTools())->where(['card_id' => $data['card_id']])->column('tools_id') ?? [])) {
                    throw new \think\Exception(L_('该次卡不可以核销该景区'),1003);
                }
                $res = (new LifeToolsCardOrderRecord())->add([
                    'order_id'   => $data['order_id'],
                    'card_id'    => $data['card_id'],
                    'mer_id'     => $data['mer_id'],
                    'tools_id'   => $param['tools_id']??0,
                    'type'       => $data['type'],
                    'uid'        => $data['uid'],
                    'staff_id'   => $staffUser['id'],
                    'staff_name' => $staffUser['name'],
                    'store_id'   => $staffUser['store_id'],
                    'add_time'   => time()
                ]);
                if ($res === false) {
                    throw new \think\Exception(L_('核销失败，请重试'),1003);
                }
                //首次使用，计算过期时间
                if($data['out_time'] == 0){
                    $time = time();
                    (new LifeToolsCardOrder)->where('order_id', $data['order_id'])->update([
                        'first_time'    =>  $time,
                        'out_time'      =>  $time + (new LifeToolsCard())->getTermNum($data['term_type'], $data['term_num']),
                    ]);
                }
                if (empty((new LifeToolsOrderDetail())->getOne(['order_id' => $data['order_id'], 'status' => 1]))) { //全部核销，更新订单状态
                    try {
                        (new LifeToolsCardOrderService())->afterVerify($data['order_id']); //核销成功之后逻辑
                    } catch (\Exception $e) {
                        throw new \Exception($e->getMessage());
                    }
                }
                
                
            } else if (strlen($code) == 12) { //城投-体育健身-订单核销
                $returnArr['operate_type'] = 'success';
                $returnArr['msg'] = '核销成功';
                $data = (new LifeToolsOrderDetail())->getOne(['code' => $code]);
                if (empty($data)) {
                    throw new \think\Exception(L_('该核销码有误，请检查'),1003);
                }
                $data = $data->toArray();
                $orderInfo = (new LifeToolsOrder())->getDetail(['o.order_id' => $data['order_id']]);
                if ($orderInfo['mer_id'] != $staffUser['mer_id']) {
                    throw new \think\Exception(L_('权限不足'),1003);
                }
                if ($data['status'] == 2) {
                    throw new \think\Exception(L_('该核销码已核销，请勿重复核销'),1003);
                }
                if ($data['status'] == 3) {
                    throw new \think\Exception(L_('该核销码已退款，请检查'),1003);
                }
                if ($data['status'] != 1) {
                    throw new \think\Exception(L_('该订单状态不支持核销'),1003);
                }
                if ($orderInfo['order_status'] == 10) {
                    throw new \think\Exception(L_('该订单未支付，无法核销'),1003);
                }
                if ($orderInfo['type'] != 'course') {
                    //期票
                    if($orderInfo['type'] == 'scenic' && $orderInfo['scenic_ticket_type'] == 0){
                        if($orderInfo['date_ticket_start'] > date('Y-m-d')){
                            throw new \think\Exception(L_('未到核销日期'),1003);
                        }
                        if($orderInfo['date_ticket_end'] < date('Y-m-d')){
                            throw new \think\Exception(L_('该核销码已过期'),1003);
                        }
                    }else{
                        if (strtotime($orderInfo['ticket_time']) < strtotime(date('Y-m-d'))) {
                            throw new \think\Exception(L_('该核销码已过期'),1003);
                        }
                        if (strtotime($orderInfo['ticket_time']) > strtotime(date('Y-m-d'))) {
                            throw new \think\Exception(L_('未到核销日期'),1003);
                        }

                        // 验证核销时间
                        $ticket = (new LifeToolsTicketService())->getOne(['ticket_id' => $orderInfo['ticket_id']]);
                        if (($ticket['start_time'] != '00:00:00' || $ticket['end_time'] != '00:00:00') && strtotime(date('Y-m-d').$ticket['start_time']) >time() || strtotime(date('Y-m-d').$ticket['end_time']) < time()) {
                            throw new \think\Exception(L_('请在'.$ticket['start_time'].'至'.$ticket['end_time'].'之间核销'),1003);
                        }
                    }

                    // 验证核销时间
                    $ticket = (new LifeToolsTicketService())->getOne(['ticket_id' => $orderInfo['ticket_id']]);
                    if (($ticket['start_time'] != '00:00:00' || $ticket['end_time'] != '00:00:00') && strtotime(date('Y-m-d').$ticket['start_time']) >time() || strtotime(date('Y-m-d').$ticket['end_time']) < time()) {
                        throw new \think\Exception(L_('请在'.$ticket['start_time'].'至'.$ticket['end_time'].'之间核销'),1003);
                    }
                } else {
                    $course_end_time = (new LifeToolsTicket())->where(['ticket_id' => $orderInfo['ticket_id']])->value('course_end_time');
                    if ($course_end_time <= time()) {
                        throw new \think\Exception(L_('该核销码已过期'),1003);
                    }
                }

                if(!isset($param['verify_type']) || $param['verify_type'] != 2){//平台操作核销不需要验证店员
                    // 核销时验证门票绑定的店员
                    (new LifeToolsOrderService())->checkOrderStaff($orderInfo['ticket_id'], $staffUser['id']);
                }

                $condition = [];
                $condition[] = ['order_id', '=', $data['order_id']];
                $condition[] = ['status', '=', 2];
                $verifiedNum = (new LifeToolsOrderDetail())->where($condition)->count();

                $verifyType = isset($param['verify_type']) ? $param['verify_type'] : 4;
                $res = (new LifeToolsOrderDetail())->updateThis(['detail_id' => $data['detail_id']], [
                    'status'     => 2,
                    'last_time'  => time(),
                    'staff_id'   => $staffUser['id'],
                    'staff_name' => $staffUser['name'],
                    'store_id'   => $staffUser['store_id'],
                    'verify_type'   => $verifyType
                ]);
                if ($res === false) {
                    throw new \think\Exception(L_('核销失败，请重试'),1003);
                }
                //团体票
                if($orderInfo['is_group'] == 1){
                    $groupOrder = (new LifeToolsGroupOrder())->where('order_id', $orderInfo['order_id'])->find();
                    $groupOrder->verify_num = (new LifeToolsOrderDetail())->where('order_id', $orderInfo['order_id'])->where('status', 2)->count();
                    $groupOrder->save();
                }
                if (empty((new LifeToolsOrderDetail())->getOne(['order_id' => $data['order_id'], 'status' => 1]))) { //全部核销，更新订单状态
                    (new LifeToolsOrderService())->changeOrderStatus($data['order_id'], 30, '全部核销成功');
                    if (!empty((new LifeToolsOrderBindSportsActivity())->getOne(['order_id' => $data['order_id']]))) {
                        (new LifeToolsOrderBindSportsActivity())->updateThis(['order_id' => $data['order_id']], ['group_status' => 50]);
                    }
                }

                $returnArr["pop_data"]  =  [
                    ['title'=> '订单名称', 'value' => $data['ticket_title']??''],
                    ['title'=> '核销数量', 'value' => $data['num']??0],
                    ['title'=> '核销金额', 'value' => $data['total_price']??0],
                    ['title'=> '核销状态', 'value' => '核销成功'],
                    ['title'=> '核销成功时间', 'value' => date('Y-m-d H:i:s')]
                ];

                //票付通订单推送核销信息
                if($orderInfo['order_source'] == 'pft' && $orderInfo['third_order_no'] && $orderInfo['third_client_id']){
                    $scenicOpenPftService = new ScenicOpenPftService();
                    $params = [
                        'verifyNum'     =>  1,
                        'verifiedNum'   =>  $verifiedNum,
                        'verifyIDCard'  =>  '',
                        'verifyCode'    =>  $data['code']
                    ];
                    $scenicOpenPftService->pftOrderVerify($orderInfo, $params);
                }
            } else {
                if(stripos($code,'http://') !== 0 && stripos($code,'https://') !== 0){
                    $strArr = explode(',', $code);
                    if(count($strArr) == 2){
                        $barCode = $strArr[1];
                    }else{
                        $barCode = $code;
                    }

                    if(strlen($barCode) == 13){
                        $returnArr['url'] = cfg('site_url').'/packapp/storestaff/retail.html';
                    }else{
                        throw new \think\Exception(L_('您扫描的内容暂时无法识别'),1003);
                    }
                }else {
                    // 扫描网址二维码
                    $parseUrl = parse_url($code);

                    if(!isset($parseUrl['query'])){
                        // 不带参数返回错误
                        throw new \think\Exception(L_('您扫描的内容暂时无法识别'),1003);
                    }

                    // 处理参数
                    $parseUrl = convert_url_query($parseUrl['query']);

                    if ($parseUrl['a'] != 'group_qrcode' || $parseUrl['id']  == '' || $parseUrl['c']  != 'Storestaff') {
                        throw new \think\Exception(L_('您扫描的内容不是有效的X1验证二维码', cfg('group_alias_name')));
                    } else {
                        if(isset($parseUrl['is_group_combine']) && $parseUrl['is_group_combine'] == 1 && isset($parseUrl['order_id'])){
                            // 团购优惠组合 跳至团购列表进行处理
                            $returnArr['url'] = cfg('site_url').'/packapp/storestaff/group_list.html?group_pass=' . $parseUrl['id'] . '&from=scan&order_id=' . $parseUrl['order_id'];
                        }elseif ($parseUrl['a'] == 'group_qrcode' && isset($parseUrl['order_id'])) {
                            $returnArr['url'] = cfg('site_url').'/packapp/storestaff/group_detail.html?order_id=' . $parseUrl['order_id'] . '&from=scan';
                        }
                    }
                }
            }
            Db::commit();
        } catch (\think\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage(), $e->getCode());
        }
        return $returnArr;
    }

    public function verifyMerCoupon($hadpullId, $uid, $storestaff = [])
    {
        $hadpullMod = new \app\common\model\db\CardNewCouponHadpull();
        $hadpull = $hadpullMod->where('uid', $uid)
            ->where('id', $hadpullId)
            ->findOrEmpty();
        if ($hadpull->isEmpty()) {
            throw new  Exception('优惠券核销码不存在');
        }
        if ($hadpull->is_use != 0) {
            throw new  Exception('优惠券核销码已使用或已过期');
        }
        $thisCoupon = (new \app\common\model\service\coupon\MerchantCouponService())->getCouponInfoByCondition(['coupon_id' => $hadpull->coupon_id]);
        if (empty($thisCoupon)) {
            throw new  Exception('优惠券不存在');
        }
        $thisCoupon = $thisCoupon[0];
        $couponStore = explode(',', $thisCoupon['store_id']);
        if (!in_array($storestaff['store_id'], $couponStore)) {
            throw new  Exception('非本店铺优惠券，无法核销');
        }
        (new MerchantCouponService())->useCoupon($hadpullId, $storestaff['id'], 'storestaff', $thisCoupon['mer_id'], $uid);
        return true;
    }

}