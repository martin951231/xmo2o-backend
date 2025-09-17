<?php
/**
 * 商家收入service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/12 09:18
 */

namespace app\merchant\model\service;   
use app\common\model\db\LiveShow;
use app\common\model\db\LiveVideo;
use app\common\model\service\admin_user\AdminUserService;
use app\common\model\service\BdCommissionRecordService;
use app\common\model\service\percent_rate\PercentRateService;
use app\common\model\service\ScrollMsgService;
use app\common\model\service\SpreadJifenListService;
use app\common\model\service\UserService as UserService;
use app\common\model\service\UserSpreadListService;
use app\common\model\service\UserSpreadService;
use app\common\model\service\weixin\TemplateNewsService;
use app\internal_card\model\service\InternalCardMoneyBillService;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\service\MallOrderService;
use app\merchant\model\db\MerchantMoneyList as MerchantMoneyListModel;
use app\merchant\model\service\card\CardNewService as CardNewService;
use app\merchant\model\service\distributor\DistributorAgentService;
use app\merchant\model\service\spread\MerchantSpreadService;
use app\merchant\model\service\spread\UserSpreadMerchantService;
use app\pay\model\db\MallbookPayInfo;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\channel\ChannelService;
use app\pay\model\service\channel\LakalaService;
use app\pay\model\service\channel\MallbookService;
use app\pay\model\service\channel\TianqueService;
use app\pay\model\service\PayService;
use app\pay\model\service\record\WeixinProfitSharingRecordService;
use app\store_marketing\model\service\StoreMarketingRecordService;
use app\villageGroup\model\db\VillageGroupOrder;

class MerchantMoneyListService {
    public $merchantMoneyListModel = null;
    public function __construct()
    {
        $this->merchantMoneyListModel = new MerchantMoneyListModel();
    }

    /**
     * 增加商家余额
     * @param $orderInfo[
    *                          pay_order_id:[],//支付单号
     *                         total_money:1,//当前订单总金额
    *                          bill_money:12,//当前商家应该入账的金额(在线支付，余额，商家余额，积分抵扣，平台优惠券抵扣金额，满减优惠平台部分)
    *                          balance_pay:'23',//平台余额支付金额
    *                          merchant_balance:'23',//商家会员卡支付金额
    *                          card_give_money:'23',//商家会员卡赠送支付金额
    *                          payment_money:'23',//在线支付金额（不包含自有支付）
     *                         score_deducte:'23',//积分支付金额
    *                          order_from:1,//餐饮外卖订单类型
    *                          order_type:'shop',//业务代号 如：shop\mall
    *                          num:'2',//数量
    *                          store_id:2,//当前门店ID
    *                          mer_id:2,//当前商家ID
    *                          order_id:'2',//当前订单id
    *                          group_id:'2',//团购id
    *                          real_orderid:'2',//当前订单长id（选传）
    *                          union_mer_id:2,//联盟商家ID
    *                          uid:2,//用户ID
    *                          desc:'',//描述
    *                          is_own:'',//自由支付
    *                          own_pay_money:'',//自由支付在线支付金额
    *                          pay_for_system:'23'//外卖商城使用平台配送的配送费快递费
    *                          pay_for_store:'23'//商城使用快递配送的配送费快递费
    *                          score_discount_type:1
    *                          money_system_take:'12'//平台抽成基数(不包含自有支付金额)
    *                          discount_detail:''//满减优惠详情
    *                          system_coupon_plat_money:''//平台优惠券平台抵扣金额
    *                          system_coupon_merchant_money:''//平台优惠券商家抵扣金额
    *                           get_system_take_money:false//仅返回抽成金额 不做其他处理
	 * ]
     * @param $other array 其他
     * @return string
     */
    public function addMoney($orderInfo, $other=''){
        fdump_api($orderInfo ,'merchant_money_f',1);

        //增加缓存避免重复
        $key = 'MerchantSettlement' . md5(var_export(func_get_args(), true));
        if (cache($key)) {
            return false;
        }
        cache($key, 1, 10);

        // 商家service
        $merchantService = new MerchantService();
        // 店铺service
        $merchantStoreService = new MerchantStoreService();
        // 用户service
        $userService = new UserService();
        $unionMerId  = $orderInfo['union_mer_id'] ?? 0;
        $merId = $orderInfo['mer_id'];// 商家id
        $desc = $orderInfo['desc'];// 收入描述
        $payForSystem = $orderInfo['pay_for_system'] ?? ''; // 外卖商城使用平台配送的配送费快递费
        $num = $orderInfo['num'] ?? 1;//商品数量
        $money = $orderInfo['bill_money'];//可对账金额
        $orderInfo['total_money'] = round($orderInfo['total_money'],2); // 订单总金额
        $moneySystemTake =  $orderInfo['money_system_take'] ?? 0; // 平台抽成基数


        if(isset($orderInfo['real_orderid']) && $orderInfo['real_orderid']){
            $has = $this->merchantMoneyListModel->where('order_id',$orderInfo['real_orderid'])->find();
            if($has && $orderInfo['order_type'] != 'group'){
                return ;
            }
        }
        // 只获取平台抽成金额
        $getSystemTakeMoney = $orderInfo['get_system_take_money'] ?? false;
        
        $systemCouponPlatMoney = isset($orderInfo['system_coupon_plat_money']) ? $orderInfo['system_coupon_plat_money'] : '0';//平台优惠券平台抵扣部分
        $systemCouponMerchantMoney = isset($orderInfo['system_coupon_merchant_money']) ? $orderInfo['system_coupon_merchant_money'] : '0';//平台优惠券商家抵扣金额

        $data = [];// 保存数据
        $unionData = [];// 会员卡联盟保存数据

        // 订单类型
        $data['type']=$orderInfo['order_type'];
        $unionMerId && $unionData['type']=$orderInfo['order_type'];

        // 订单id
        if($data['type']=='group' || $data['type']=='shop' || $data['type']=='mall' || $data['type']=='dining') {
            $data['order_id'] = $orderInfo['real_orderid'];
            $unionMerId && $unionData['order_id']=$orderInfo['real_orderid'];
        }else{
            $data['order_id'] = $orderInfo['order_id'];
            $unionMerId && $unionData['order_id']=$orderInfo['order_id'];
        }

        // 保存数据
        $data['money'] = $money;
        $data['total_money'] = $orderInfo['total_money'];
        $data['num'] = $num>0 ? $num : 1;
        $data['mer_id'] = $merId>0 ? $merId : '1';

        // 商家联盟
        $unionData['money'] = 0;
        $unionData['total_money'] = 0;
        $unionData['num'] = $num>0 ? $num: 1;
        $unionData['mer_id'] = $unionMerId;

        $merchantBalance = 0;
        if ($unionMerId) { //商家会员卡联盟
            $merchantBalance = $orderInfo['merchant_balance'];
        }

        //商家绑定用户
        $merUser = $merchantService->getMerchantUserByMerId($merId);
        // 店铺信息
        if(isset($orderInfo['store_id'])){
            $nowStore = $merchantStoreService->getStoreByStoreId($orderInfo['store_id']);
            $data['store_id'] = $orderInfo['store_id'];//店铺id
            $unionData['store_id'] = $orderInfo['store_id'];
        }

        // 商家信息
        $nowMerchant = $merchantService->getMerchantByMerId($merId);
        $nowMerchantUnion = [];
        $unionMerId &&  $nowMerchantUnion = $merchantService->getMerchantByMerId($merId);

        $nowUser = [];
        if($orderInfo['uid']>0){
            $nowUser = $userService->getUser($orderInfo['uid']);

        }

        //日照银行担保支付确认
        $pay_where = [
            ['channel', '=', 'ebankpay'],
            ['pay_type', '=', 'ebankpay'],
            ['paid', '=', '1'],
        ];
        $pay_orders = (new PayService)->getPayOrders($orderInfo['pay_order_id'], $pay_where);
        if (!empty($pay_orders)) {
            foreach ($pay_orders as $ebankv) {
                $ebankv['uid'] = $orderInfo['uid'];
                (new PayService())->payEbankGuarantee($ebankv);
            }
        }

        //自有支付
        $ownPercentMoney = 0;
        if(isset($orderInfo['is_own'])&&$orderInfo['is_own']>0){
             $aliasName = $this->getAliasName()[$orderInfo['order_type']];
             $storeName = L_('无');
             if (!empty($nowStore)) {
                 $storeName = $nowStore['name'];
             }
             if($orderInfo['is_own']==2){
                 $remark = L_('子商家支付商家余额不增加余额，请到子商家平台中查看');
                 $title = L_("子商家");
             }else{
                 $remark = L_('请到微信商家平台中查看');

                 $title = L_("自有支付");
             }

            $msgData = [
                'href' => '',
                'wecha_id' => $merUser['openid'],
                'first' => $title.'收款成功,' ,
                'keyword1' => $aliasName,
                'keyword2' =>$orderInfo['payment_money'],
                'keyword3' =>$storeName,
                'keyword4' => date("Y年m月d日 H:i"),
                'keyword5' =>$orderInfo['order_id'],
                'remark' =>$remark
            ];
            !$getSystemTakeMoney && (new TemplateNewsService())->sendTempMsg('OPENTM402026291', $msgData, $merUser['mer_id']);
            if(cfg('open_mer_own_percent')==1){ //自由支付抽成
                $ownPercentMoney = $orderInfo['own_pay_money'];
            }
        }
        // 获取抽成比例
        $percentArr = $this->getPercentByOrder($merId, $orderInfo, $orderInfo['total_money']);
        
        fdump_api([
            'mer_id'     => $merId,
            'order_info' => $orderInfo,
            'money'      => $money,
            'percent'    => $percentArr,
            'msg'        => '获取抽成比例'
        ],'MerchantMoneyListService/merchant_money_list', 1);

        fdump_api($percentArr ,'merchant_money_f',1);

        // 抽成比例
        $percent = get_format_number($percentArr['percent']);

        // 商家联盟抽成比例
        $unionPercent = get_format_number($percentArr['union_percent']);

        // 外卖商城使用平台配送的配送费快递费
        if($money<=0){
            if($payForSystem!=0){
                if($orderInfo['is_own']==1) {
                    $payForSystemDesc=L_("自有支付使用平台X1，系统扣除X2费",array("X1" => cfg('deliver_name'),"X2" => cfg('deliver_name'))); 
                }else if($orderInfo['order_from']==1){
                    $payForSystemDesc=L_("商城订单快递配送转为X1，系统扣除X2费",array("X1" => cfg('deliver_name'),"X2" => cfg('deliver_name'))); 
                }else{
                    $payForSystemDesc= L_("转X1，平台从商家余额中扣除订单的配送费",array("X1" => cfg('deliver_name'))); 
                }
                !$getSystemTakeMoney && $this->useMoney($merId,$payForSystem,$orderInfo['order_type'],$payForSystemDesc,$orderInfo['real_orderid']);
            }
            $money  = 0;
        }

        // 商城订单支付应该入账的金额若为负数，从商家余额扣除
        if($money<0 && $orderInfo['type'] == 'mall'){
            $moneySystemDesc = L_('平台从商家余额中扣除费用');
            !$getSystemTakeMoney && $this->useMoney($merId,$money,$orderInfo['order_type'],$moneySystemDesc,$orderInfo['real_orderid']);
        }
        // 抽成service
        $percentRateService = new PercentRateService();

        if($orderInfo['order_type']!='withdraw' && $orderInfo['order_type']!='merrecharge'){
            if(cfg('open_extra_price')==1){
                $data['score'] = $percentRateService->getExtraMoney($orderInfo);
                $data['score_count']=$orderInfo['score_used_count'];
            }else {
                $score_get = 0;
                if(cfg('add_score_by_percent')==0  && cfg('add_score_by_system_commission') == 0 && (cfg('open_score_discount')==0 || $orderInfo['score_discount_type']!=2)) {
                    if (cfg('open_score_get_percent') == 1) {
                        $scoreGetPercent = cfg('score_get_percent') ?: 0;
                        $score_get = $scoreGetPercent / 100;
                    } else {
                        $score_get = cfg('user_score_get')?:0;
                    }
                    if ($orderInfo['mer_id'] > 0) {
                        if (isset($nowMerchant['score_get_percent']) && $nowMerchant['score_get_percent'] >= 0) {
                            $score_get = floatval($nowMerchant['score_get_percent']) / 100;
                        }
                    }
                    $data['score'] = round($money*$score_get);
                    $unionData['score'] = round($merchantBalance*$score_get);
                }
                $data['score_count']= isset($orderInfo['score_used_count']) && $orderInfo['score_used_count']>0?$orderInfo['score_used_count']:0;
                $unionData['score'] = round($merchantBalance*$score_get);
            }
        }
        //除了提现，分佣，批发分佣，其他的收入要抽成
        if($orderInfo['order_type']!='withdraw' && $orderInfo['order_type']!='spread' && $orderInfo['order_type']!='merrecharge' && $orderInfo['order_type']!='market_spread'){
            $data['total_money']= $orderInfo['total_money'];
            $unionData['total_money'] = $orderInfo['total_money'];

            if(cfg('open_meiyong_percent')==1 && ($orderInfo['order_type'] == 'shop' || $orderInfo['order_type'] == 'mall')){
                $data['system_take'] = round($moneySystemTake * $percent / 100, 2);
                if ($orderInfo['uid'] > 0 && (cfg('add_score_by_percent') > 0 || cfg('add_score_by_system_commission') == 1) && round($data['system_take']) > 0) {
                    $data['score'] = round($data['system_take']);
                }
        
            }else {
                if (cfg('open_store_percent_single') == 1 && ($orderInfo['order_type'] == 'store' || $orderInfo['order_type'] == 'cash')) {
                    $data['system_take'] = 0;
                } else {
                    $data['system_take'] = round(($moneySystemTake) * $percent / 100, 2);
                    $unionData['system_take'] = round($merchantBalance * $unionPercent / 100, 2);
                }
                if ($orderInfo['uid'] > 0 && (cfg('add_score_by_percent') > 0 || cfg('add_score_by_system_commission') == 1) && round($data['system_take']) > 0) {
                    $data['score'] = round($data['system_take']);
                    $unionData['score'] = round($unionData['system_take']);
                }
                $expressFee = $orderInfo['express_fee'] ?? 0;
                if (isset($nowMerchant['package_fee_percent']) && $nowMerchant['package_fee_percent'] == 0) {
                    $money = $money + ($orderInfo['packing_charge'] ?? 0);
                } else if ($expressFee > 0) {
                    $money = $money + $expressFee;
                }
            }

            if(cfg('open_store_percent_single')==1 && $orderInfo['is_own']==0 && ($orderInfo['order_type']=='store'||$orderInfo['order_type']=='cash')) {
                // 开启后快速买单按单独结算比例抽成
                $data['money'] = $percentRateService->getPercentRate($merId,$orderInfo['order_type'],$orderInfo['total_money'], '',$orderInfo['store_id'], true);
            }else{
                $data['money'] = $money - $data['system_take'];
                $unionData['money'] = $merchantBalance - $unionData['system_take'];
            }
            $data['percent'] = $percent;
            $unionData['percent'] = $unionPercent;
        }else{
            $data['percent'] = 0;
            $unionData['percent'] = 0;
            $data['system_take'] = 0;
            $unionData['system_take'] = 0;
        }
        //直播订单扣除佣金   先给主播加佣金，佣金从商家要增加的余额里面扣除      
        if(cfg("live_open") == '1' && ((isset($orderInfo['is_liveshow']) && $orderInfo['is_liveshow'] == '1') ||(isset($orderInfo['is_livevideo']) && $orderInfo['is_livevideo'] == '1'))){
            if($orderInfo['is_liveshow'] == '1'){
                $live_info = (new LiveShow)->getOne([['live_id', '=', $orderInfo['liveshow_id']]])->toArray();
            }
            elseif($orderInfo['is_livevideo'] == '1'){
                $live_info = (new LiveVideo)->getOne([['video_id', '=', $orderInfo['livevideo_id']]])->toArray();
            }
            //1、计算佣金
            $liveshow_money = 0;
            $live_desc = "";
            switch ($orderInfo['order_type']) {
                case 'mall':
                    $_where = [['order_id', '=', $orderInfo['order_id']]];
                    if($orderInfo['is_liveshow'] == '1'){
                        $_where[] = ['is_liveshow', '=', 1];
                    }
                    else{
                        $_where[] = ['is_livevideo', '=', 1];
                    }
                    $order_detail = (new MallOrderDetail)->getSome($_where, 'id,liveshow_percent,money_total');
                    if($order_detail){
                        $order_detail = $order_detail->toArray();
                        foreach ($order_detail as $od) {
                            $goods_all_money = $od['money_total'];
                            //减掉系统抽成
                            $goods_all_money = $goods_all_money - $goods_all_money*$data['percent']/100;
                            //再乘以设置的佣金比例就是主播抽成了
                            $goods_money = $goods_all_money * $od['liveshow_percent']/100;
                            !$getSystemTakeMoney && (new MallOrderDetail)->updateThis([['id', '=', $od['id']]], ['liveshow_money'=>$goods_money]);
                            $liveshow_money += $goods_money;
                        }
                        $data['money'] -= $liveshow_money;
                        !$getSystemTakeMoney && (new MallOrderService)->updateMallOrder(['order_id'=>$orderInfo['order_id']], ['live_merchant_money'=>$data['money'], 'live_author_money'=>$liveshow_money]);
                        if($orderInfo['is_liveshow'] == '1'){
                            $live_desc = "用户购买".cfg('mall_alias_name')."商品，增加佣金，直播ID".$live_info['live_no'];
                        }
                        else{
                            $live_desc = "用户购买".cfg('mall_alias_name')."商品，增加佣金，短视频ID".$live_info['video_id'];
                        }
                    }
                    break;
                case 'village_group'://佣金通过计划任务发放
                    $liveCommission = VillageGroupOrder::where([
                        'order_id' => $orderInfo['order_id'],
                        'live_commission_status' => 10
                    ])->value('live_commission');
                    $liveCommission && $data['money'] -= $liveCommission;
                    break;
            }
            if($liveshow_money > 0){
                !$getSystemTakeMoney && $userService->addMoney($live_info['uid'],  $liveshow_money, $live_desc);
            }
        }
        $data['money'] < 0 && $data['money'] = 0;
        $data['system_take'] < 0 && $data['system_take'] = 0;
        $data['income'] = 1;
        $data['use_time']= time();
        $data['desc']=  empty($desc)?'':$desc;

        $unionData['money'] < 0 && $unionData['money'] = 0;
        $unionData['system_take'] < 0 && $unionData['system_take'] = 0;
        $unionData['income'] = 1;
        $unionData['use_time'] = time();
        $unionData['desc'] =  empty($desc)?'':$desc;

        //商城使用快递配送的配送费快递费直接添加到商家余额
        if ($orderInfo['order_type'] == 'mall' && $orderInfo['pay_for_store'] > 0) {
            $data['money'] = $data['money'] + $orderInfo['pay_for_store'];
        }

        // 自有支付抽成金额
        $ownSystemTake = 0;
        if(cfg('open_mer_own_percent') && $ownSystemTake>0 ){
            $ownSystemTake = sprintf("%.2f",$ownPercentMoney*$percent/100);
        }
        
        // 抽成总额
        $systemTakeMoney = get_format_number($data['system_take'] + $unionData['system_take'] + $ownSystemTake);

        if($getSystemTakeMoney){// 仅返回抽成金额 不做其他处理
            return $systemTakeMoney;
        }
        
        //分账加钱
        $weixinLedgerRes = [];// 微信分账结果
        try{
            //拿到支付单信息
            $success = 0;// 随行付
            $success_money = 0; // 随行付
            $launch_ledger_money = 0;// 随行付 参与分账的金额

            if(!empty($orderInfo['pay_order_id'])){
                $pay_where = [
                    [
                        'channel', '=', 'tianque'
                    ],
                    [
                        'refund', '<>', '2'
                    ],
                    [
                        'paid', '=', '1'
                    ],
                ];
                $pay_orders = (new PayService)->getPayOrders($orderInfo['pay_order_id'], $pay_where);
                if($pay_orders){
                    $max_count = 0;//超出分账最大比例的次数
                    foreach ($pay_orders as $key => $value) {
                        $pay_config = $value['pay_config'] ? json_decode($value['pay_config'], true) : [];
                        if(($value['paid_money'] - $value['refund_money']) <= 0){
                            continue;
                        }
                        $tmp_money = round(($value['paid_money'] - $value['refund_money'])*($data['percent']/100))/100;//当前支付单扣除掉的平台抽成
                        //判断当前支付单的抽成比例是否占了总额的30%以上，如果超过了就要以30%为准，如果除不尽，就向下取整(因为随行付规定超过30%的抽成无效)
                        $_percent = ($tmp_money*100)/($value['paid_money'] - $value['refund_money']);
                        if($_percent > 0.3){
                            $max_count++;
                            $tmp_money = floor(($value['paid_money'] - $value['refund_money'])*0.3)/100;
                        }
                        $mno = isset($pay_config['mno']) ? $pay_config['mno'] : '';
                        if((new TianqueService())->launchLedger(0, $value['orderid'], $tmp_money, $mno, $value['paid_money'] - $value['refund_money'])){
                            $launch_ledger_money += $value['paid_money'] - $value['refund_money'];
                            $success++;
                            $success_money += $tmp_money;
                        } 
                        if(($value['paid_money'] - $value['refund_money']) > 0 && $tmp_money == 0){//防止那些被四舍五入的金额
                            $launch_ledger_money += $value['paid_money'] - $value['refund_money'];
                            $success++;
                        }
                        
                    }
                }
                if($success > 0){
                    $data['money'] += floor($launch_ledger_money*$data['percent']/100)/100 - $success_money;
                    $data['system_take'] -= $success_money;
                    $data['percent'] = $max_count == (is_array($orderInfo['pay_order_id']) ? count($orderInfo['pay_order_id']) : 1) ? 30.00 : $data['percent'];//如果支付单中每一笔都超出了最大分账比例，则抽成比例换成30%
                    if($success == (is_array($orderInfo['pay_order_id']) ? count($orderInfo['pay_order_id']) : 1)){
                        // $data['money'] = 0;//总额不能减
                        $data['desc'] = '【随行付--分账收入】'.$data['desc'];
                    }
                    elseif($success != '0'){
                        // $data['money'] -= $success_money;//总额不能减
                        $data['desc'] = '【随行付--分账收入】'.$data['desc'];   
                    }
                }

                // 微信分账
                $pay_where = [
                    [
                        'channel', '=', 'wechat'
                    ],
                    [
                        'refund', '<>', '2'
                    ],
                    [
                        'paid', '=', '1'
                    ],
                ];
                $pay_orders = (new PayService)->getPayOrders($orderInfo['pay_order_id'], $pay_where);
                if($pay_orders){
                    $orderInfoNew['uid'] = $orderInfo['uid'];
                    $orderInfoNew['system_take'] = $systemTakeMoney;
                    $orderInfoNew['percent'] = $percent;
                    $orderInfoNew['own_pay_money'] = $ownPercentMoney;
                    $orderInfoNew['order_type'] = $orderInfo['order_type'];
                    $orderInfoNew['order_id'] = $orderInfo['order_id'];
                    $orderInfoNew['mer_id'] = $orderInfo['mer_id'];
                    $weixinLedgerRes = $this->saveWeixinProfitSharing($orderInfoNew, $pay_orders);
                    
                    if(cfg('open_mer_own_percent') && $ownPercentMoney > 0){
                        $data['desc'] = $data['desc']." （微信分账".$weixinLedgerRes['merchant_get_money']."）"; 
                    } 
                }
            }
            
        }
        catch(\Exception $e){
            throw new \think\Exception($e->getMessage());
        }
        //银联分账
        $chinaumsFenzhang = false;
        try{
            if(!empty($orderInfo['pay_order_id'])){
                $pay_where = [
                    [
                        'channel', '=', 'chinaums'
                    ],
                    [
                        'refund', '<>', '2'
                    ],
                    [
                        'paid', '=', '1'
                    ],
                ];
                $pay_orders = (new PayService)->getPayOrders($orderInfo['pay_order_id'], $pay_where);

                $chinaums_merchant_already_get = $chinaums_platform_already_get= 0;
                $payConfig = [];
                $payEnv = '';
                if($pay_orders){
                    foreach ($pay_orders as $key => $value) {
                        empty($payConfig)&& $payConfig = json_decode($value['pay_config'],true);
                        empty($payEnv) && $payEnv = $value['env']; 
                        $chinaums_merchant_already_get += $value['chinaums_merchant_already_get'];
                        $chinaums_platform_already_get += $value['chinaums_platform_already_get'];
                    }

                    if (cfg('chinaums_platform_sub_mchid') && $chinaums_merchant_already_get > 0) {
                        //平台通过子商户角色分账
                        $chinaums_data = [
                            'mer_id' => $orderInfo['mer_id'],
                            'create_time' => time(),
                            'merchant_money' => $chinaums_merchant_already_get,
                            'platform_money' => $chinaums_platform_already_get,
                            'is_withdraw' => 0,
                            'plat_is_withdraw' => 0,
                            'pay_orderid' => is_array($orderInfo['pay_order_id']) ? implode(',', $orderInfo['pay_order_id']) : $orderInfo['pay_order_id'],
                            'type' => 'settlement'
                        ];

                        if (\think\facade\Db::name('chinaums_fenzhang_record')->insert($chinaums_data)) {
                            //餐饮会存在多笔银联+其他支付方式，所以需要把其他支付方式的结算金额给到商家
                            if ($orderInfo['order_type'] == 'dining' && is_array($orderInfo['pay_order_id']) && count($orderInfo['pay_order_id']) > count($pay_orders)) {
                                $data['money'] = $data['money'] ;
                            } else {
                                $data['money'] = 0;
                            }
                            $data['system_take'] = $chinaums_platform_already_get/100;
                            $data['desc'] = sprintf($data['desc'] . " （银联分账:%.2f元）",getFormatNumber($chinaums_merchant_already_get/100));
                        }
                        $chinaumsFenzhang = true;
                    }else if($payConfig && isset($payConfig['without_asset']) && $payConfig['without_asset'] == 1 && $payEnv == 'wechat_mini'){
                        //不接资管
                        $chinaumsOrderid = $pay_orders[0]['orderid'];
                        $chinaumsMoney = $pay_orders[0]['money'];
                        $subOrders = [
                            ['merId' => $payConfig['sub_mer_no'], 'satAmt' => $chinaums_merchant_already_get],
                        ];
                        $result = (new \app\pay\model\service\channel\ChinaumsService($payEnv, $payConfig))->fenzhangConfirm($chinaumsOrderid, $chinaumsMoney, $subOrders);
                        if ($result) {
                            (new \app\pay\model\db\PayOrderInfo())->updateThis(['orderid' => $chinaumsOrderid, 'channel' => 'chinaums', 'paid' => 1], ['async_confirm_result' => json_encode($result, JSON_UNESCAPED_UNICODE)]);
                        }
    
                        $data['money'] = 0;
                        $data['system_take'] = $chinaums_platform_already_get / 100;
                        $data['desc'] = sprintf($data['desc'] . " （银联分账:%.2f元）",getFormatNumber($chinaums_merchant_already_get/100));
                        $chinaumsFenzhang = true;
                    } else{
                        if($chinaums_merchant_already_get > 0){
                            $data['money'] = get_format_number($data['money'] - $chinaums_merchant_already_get/100);
                            $data['desc'] = $data['desc']." （银联分账）"; 
                        }
                        $chinaums_data = [
                            'mer_id' => $orderInfo['mer_id'],
                            'create_time' => time(),
                            'merchant_money' => $chinaums_merchant_already_get,
                            'platform_money' => $chinaums_platform_already_get,
                            'is_withdraw' => 0
                        ];
                        if (C('config.is_1238888_system') || \think\facade\Db::name('merchant_chinaums_record')->insert($chinaums_data)) {
                            $data['money'] = 0;
                            $data['system_take'] = $chinaums_platform_already_get / 100;
                            $data['desc'] = sprintf($data['desc'] . " （银联分账:%.2f元）",getFormatNumber($chinaums_merchant_already_get/100));
                        }
                        $chinaumsFenzhang = true;
                    }
                }
            }
        }
        catch(\Exception $e){
            throw new \think\Exception($e->getMessage());
        }
        

        // 自有支付抽成
        if(cfg('open_mer_own_percent') && $ownPercentMoney>0 && !$chinaumsFenzhang && (!$weixinLedgerRes || !$weixinLedgerRes['is_need'])){// 自由支付 不分账需要扣除商家余额抽成
            $payForSystemDesc = L_("对商家自有支付抽成，在线自有支付支付金额：X1，扣除商家余额：X2",array("X1" => $ownPercentMoney,"X2" => sprintf("%.2f",$ownPercentMoney*$percent/100))); 
            $result_pay = $this->useMoney($merId,sprintf("%.2f",$ownPercentMoney*$percent/100),$orderInfo['order_type'],$payForSystemDesc,$data['order_id'],$percent,sprintf("%.2f",$ownPercentMoney*$percent/100),array(),$orderInfo['store_id']);
        }
        
        // 保存商家余额
        if ($unionPercent != 100 && $unionData['money'] > 0 && ($merchantService->addMoney($unionMerId,$unionData['money'])) === false) {
            throw new \think\Exception(L_("增加联盟商家余额失败"));
        }
        $data_money = $launch_ledger_money > 0  ? $data['money']-$launch_ledger_money/100 + $success_money : $data['money'];
        $data_money < 0 && $data_money = 0;

        //$data['money']  商家收入，data['system_take']  平台抽成  卡拉卡（钱账通）分账
        if (!is_null(cfg('lakala_pay_switch')) && !empty($orderInfo['pay_order_id'])) {
            $pay_where = [['channel', '=', 'lakala'], ['refund', '<>', '2'], ['paid', '=', '1']];
            $pay_orders = (new PayService)->getPayOrders($orderInfo['pay_order_id'], $pay_where);
            if($pay_orders){
                $pay_env = '';
                $pay_config = [];
                $mer_get = 0;
                $plat_get = 0;
                $real_pay = 0;
                $order_no = '';
                foreach ($pay_orders as $pay) {
                    $real_pay += ($pay['paid_money'] - $pay['refund_money']);
                    empty($pay_env) && $pay_env = $pay['env'];
                    empty($pay_config) && $pay_config = json_decode($pay['pay_config'], true);
                    empty($order_no) && $order_no = $pay['paid_extra'];
                }
                if ($real_pay >= $data['money'] * 100) {
                    $mer_get = bcmul($data['money'] , 100);
                    $plat_get = bcsub($real_pay , $mer_get);
                    $data['money'] = 0;
                } else {
                    $mer_get = $real_pay;
                    $plat_get = 0;
                    $data['money'] -= $real_pay / 100;
                }
                $data['desc'] = sprintf($data['desc'] . " （【拉卡拉分账】商户：%s,平台：%s）", bcdiv($mer_get, 100, 2), bcdiv($plat_get, 100, 2));
                $data['platform_get'] = bcdiv($plat_get, 100, 2);
                $data['merchant_get'] = bcdiv($mer_get, 100, 2);
                try {
                    (new LakalaService($pay_env, $pay_config))->fenzhang($order_no, $mer_get, $plat_get);
                } catch (\Throwable $th) {
                    fdump_api([$order_no, $th->getMessage()], 'pay/LakalaFenzhangErr', 1);
                }
                $data_money = $data['money'];
            }
        }


        if(customization('open_yibao') ){//mallbook分账
            try{
                if(!empty($orderInfo['pay_order_id'])){
                    $pay_where = [
                        ['channel', '=', 'mallbook'],
                        ['refund', '<>', '2'],
                        ['paid', '=', '1'],
                    ];
                    $pay_orders = (new PayService)->getPayOrders($orderInfo['pay_order_id'], $pay_where);
                   
                    fdump_api([
                        'pay_orders' => $pay_orders,
                        'data_money' => $data_money,
                        'msg'        => 'mallbook分账'
                    ], 'mallbook/separateMoney',true);
                    
                    $merAddMoney = $addMerMoney = 0;
                    foreach ($pay_orders as $payNo){
                        $merOrderNo = $payNo['orderid'];
                        $payAmount = MallbookPayInfo::where('mallbook_order_no', $merOrderNo)->findOrEmpty()->toArray();
                        $leftAmount = bcdiv($payAmount['amount'] - $payAmount['refund_amount'], 100,2);
                        
                        if($data_money >= $leftAmount){
                            $addMerMoney = $leftAmount;
                            $data_money = $data_money - $leftAmount;
                        }else{
                            $addMerMoney = $data_money;
                            $data_money = 0;
                        }
                        
                        fdump_api([
                            'pay_amount' => $payAmount,
                            'add_money'   => $addMerMoney,
                            'data_money'  => $data_money,
                            'left_amount' => $leftAmount,
                            'msg'         => 'mallbook分账1'
                        ], 'mallbook/separateMoney',true);
                        
                        (new MallbookService())->separateMoney($merOrderNo, $merId, $addMerMoney);
                        $merAddMoney = bcadd($merAddMoney, $addMerMoney,2);
                    }
                    if($merAddMoney > 0){
                        $data['money'] = get_format_number($data['money'] - $merAddMoney);
                        $data['desc'] = $data['desc']." （汇付分账）";
                    }
                }
            }catch (\Exception $e){
                fdump_api([
                    'trace' => $e->getTraceAsString(),
                    'msg' => $e->getMessage()
                ], 'mallbook/merAddMoneyError', true);
                throw_exception($e->getMessage());
            }
        }
        
        if( $data_money > 0 && $merchantService->addMoney($merId,$data_money) === false){//参与分账的钱，不能再给商家加余额了
            throw new \think\Exception(L_("增加商家余额失败"));
        }
        (new InternalCardMoneyBillService())->setServiceFee($orderInfo, $percent);//计算平台服务费
        // 当前商家余额
        $nowMerMoney = get_format_number($nowMerchant['money'] + $data_money);

        // 联盟商家余额
        $nowUnionMerchant = isset($nowMerchantUnion) && $nowMerchantUnion ? get_format_number($nowMerchantUnion['money']+$unionData['money']) : '0';
        // 联盟商家余额
        $nowUnionMerMoney = 0;
        if($nowUnionMerchant){
            $nowUnionMerMoney = $nowUnionMerchant['money'];
        }

        $data['now_mer_money'] = $nowMerMoney;
        $unionData['now_mer_money'] = $nowUnionMerMoney;
        
        if($data['type'] == 'yydb' || $data['type'] == 'coupon'){
            $data['type'] = 'activity';
        }

        $unionData['union_mer_id'] =$merId;

        if ($unionMerId && !$this->add($unionData)) {
            throw new \think\Exception(L_("，保存联盟商家收入失败！"));
        }

        //区域管理员得到分佣（扣除平台抽成后的金额，计算分佣）
        if(isset($orderInfo['price']) && $orderInfo['price'] > 0){
            $commission_money = $orderInfo['price'];
        }
        else{
            $commission_money = $data['total_money'];
        }
        invoke_cms_model('Area_commission/addCommission',[$commission_money, $merId, $orderInfo['store_id'], $orderInfo['order_type'], $orderInfo['order_id']]);
        fdump_api([$commission_money, $systemTakeMoney, $data, $other], 'merchant_money_list/bd', 1);
        //平台业务员得到分佣（扣除平台抽成后的金额，计算分佣）
        if(empty(cfg('open_bd_spread_new_commission')) && empty($data['system_take'])){
            fdump_api([
                '当平台抽成为0的时候,业务经理和业务员不抽成',
                $commission_money, $systemTakeMoney, $data, $other, $orderInfo
            ], 'merchant_money_list/bd', 1);
        }else if($other != 'reject'){
            (new BdCommissionRecordService())->addCommission($commission_money, $merId, $orderInfo['store_id'], $orderInfo['order_type'], $orderInfo['real_orderid']?:$orderInfo['order_id']);
        }
        fdump_api($data ,'merchant_money_f',1);

        //当平台支付用户实际支付的钱不足平台抽成金额的时候扣除商家余额
        if (in_array($orderInfo['order_type'], ['mall']) && $orderInfo['is_own'] == 0 && $orderInfo['payment_money'] > 0 && $orderInfo['payment_money'] < $data['system_take']) {
            $diffMoney = $data['system_take'] - $orderInfo['payment_money'];
            if ($merchantService->useMoney($merId, $diffMoney)) {
                $data['now_mer_money'] -= $diffMoney;
            }
        }

        if(!$this->add($data)){
            throw new \think\Exception(L_("，保存商家收入失败！"));
        }

        //分销员分享抽成到账(新版商城分销到账走自动关闭接口)
        try{
            if ($data['type'] == 'group') {
                (new StoreMarketingRecordService())->doArrival([
                    'order_id' => $orderInfo['order_id'],
                    'goods_type' => 1
                ]);
            }
        } catch(\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        if($orderInfo['order_type'] != 'withdraw'&& !empty($merUser) && $merUser['open_money_tempnews']) {
            $aliasName = $this->getAliasName();
            $aliasName = $aliasName[$orderInfo['order_type']] ?? L_('无');
            $storeName = $nowStore['name'] ?? L_('无');


            if (isset($orderInfo['pay_type']) && $orderInfo['pay_type'] == 'offline') {
                //线下支付
                $notifyMoney = $data['total_money'];
                $aliasName .= "(线下支付)";
            } else {
                $notifyMoney = $money;
            }

            // 发送模板消息
            $msgData = [
                'href' => '',
                'wecha_id' => $merUser['openid'],
                'first' => L_("收款成功，当前商家余额：X1",array("X1" => $nowMerchant['money']))  ,
                'keyword1' => $aliasName,
                'keyword2' => $notifyMoney,
                'keyword3' => $storeName,
                'keyword4' => date("Y年m月d日 H:i"),
                'keyword5' => $data['order_id'],
                'remark' => L_('请到商家中心商家余额中查看')
            ];
            (new TemplateNewsService())->sendTempMsg('OPENTM402026291', $msgData);
        }

        //结算佣金
        (new UserSpreadListService)->spreadToComplete($orderInfo['order_type'], $orderInfo['order_id']);
        
        if($orderInfo['order_type']!='merrecharge'){
            if ($unionMerId) {
                // 商家联盟，使用联盟商家会员卡应当扣除联盟商家的会员卡余额
                (new CardNewService())->merchantCard($unionMerId,$orderInfo);
            }else{
                (new CardNewService())->merchantCard($merId,$orderInfo);
            }
        }

        //解冻用户奖励金额 定制
        if(cfg('free_recommend_awards_percent')>0 && $orderInfo['uid'] ){
            // 在线支付金额 平台余额 商家余额 商家会员卡赠送余额
            // 推广注册的用户 推广注册的商家
            $award_money  = $orderInfo['payment_money'] + $orderInfo['balance_pay']+$orderInfo['merchant_balance']+$orderInfo['card_give_money'];
            if($award_money > 0) {
                $spreadUsers[]  = $orderInfo['uid'];
                $FenrunFreeAwardMoneyListService      = new FenrunFreeAwardMoneyListService();
                $where = [];
                $where['uid'] = $nowUser['uid'];

                $nowUserSpread = (new UserSpreadService())->getOneRow($where);
                if (!empty($nowUserSpread)) {
                    $spreadUser = $userService->getUser($nowUserSpread['spread_uid'], 'uid');

                    if ($spreadUser && !in_array($spreadUser['uid'], $spreadUsers)) {
                        $free_money = round(($award_money) * cfg('free_recommend_awards_percent') / 100, 2);

                        if ($free_money > 0 && $spreadUser['frozen_award_money'] > 0) {
                            $FenrunFreeAwardMoneyListService->freeUserRecommendAwards($spreadUser['uid'], $free_money,1,$orderInfo['uid']);
                        }
                    }
                }

                //如果该商家是被推荐注册的
                if ($nowMerchant['uid']) {
                    $where['uid'] = $nowMerchant['uid'];
                    $nowUserSpread = (new UserSpreadService())->getOneRow($where);
                    if (!empty($nowUserSpread)) {
                        $spreadUser = $userService->getUser($nowUserSpread['spread_uid'], 'uid');
                        if ($spreadUser && !in_array($spreadUser['uid'], $spreadUsers)) {
                            $free_money = round(($award_money) * cfg('free_mer_award_percent') / 100, 2);
                            if ($free_money > 0 && $spreadUser['frozen_award_money'] > 0) {
                                $FenrunFreeAwardMoneyListService->freeUserRecommendAwards($spreadUser['uid'], $free_money, 2,$merId);
                            }
                        }
                    }
                }
            }
        }

        // 分销员
         if(cfg('open_distributor')>0 && $orderInfo['mer_id'] &&cfg('agent_percent')>0){
             (new DistributorAgentService())->addMoney($orderInfo['mer_id'],$money,$orderInfo['order_id']);
         }

         if((!$other || $other != 'reject') && $orderInfo['order_type']!='merrecharge' && cfg('open_c2b_spread')>0 && $orderInfo['mer_id'] &&cfg('c2b_first_rate')>0){
             (new UserSpreadMerchantService())->userAddMoney($orderInfo,$money);
         }

        if(cfg('share_coupon')==1 && isset($orderInfo['share_status'])&&$orderInfo['share_status']==1){
            $param['type'] = $orderInfo['order_type'];
            $param['order_id'] = $orderInfo['order_id'];
            $param['uid'] = $orderInfo['uid'];
        }
        //增加积分按平台抽成算
        $this->setScore($systemTakeMoney, $orderInfo, $nowStore);

        if($orderInfo['uid']){
            //推广分佣结算
            if($orderInfo['order_type']=='shop' && $orderInfo['order_from']==1){
                $spreadCondition['order_type'] ='mall';
            }else{
                $spreadCondition['order_type'] =$orderInfo['order_type'];
            }
            $spreadCondition['order_id'] = $orderInfo['order_id'] ;
            $spreadCondition['level'] = 0; // 有等级的在订单过了可申请售后时间再对账
            $nowSpread = (new UserSpreadListService())->getSome($spreadCondition);
        
            foreach($nowSpread as $v){
                if(cfg('open_distributor')>0){
                    $res= (new DistributorAgentService())->getEffective($v['uid'], 1);
                    if(!$res){
                        break;
                    }
                }
                (new UserSpreadListService())->spreadCheck($v['uid'], $v['pigcms_id']);
            }

            $count = 1;// 未定，可由业务传入
            if(cfg('open_user_award')==1){
                $award_percent = $percentRateService->getUserAward($orderInfo['order_type']);
                $award_percent = $award_percent['first_rate'];

                if($award_percent>0){
                    if($orderInfo['order_type']=='group' && !$orderInfo['verify_all']){
                        $award_money = round(($orderInfo['payment_money']+$orderInfo['balance_pay'])/$count*$award_percent/100,2);
                    }else{
                        $award_money = round(($orderInfo['payment_money']+$orderInfo['balance_pay'])*$award_percent/100,2);
                    }
                }
                if($award_money>0){
                    $userService->addMoney($orderInfo['uid'],  $award_money, L_('用户消费奖励金'));
                }
            }

            //校验该订单是否存在分佣的积分
             (new SpreadJifenListService())->spreadCheckJifen($orderInfo);
        }

        if(cfg('nmgzhcs_appid') && $nowMerchant['nmgzhcs_appid']){
             $this->nmgzhcsMoney($nowMerchant['nmgzhcs_appid'],$data['order_id'],$data['money'],$desc);
        }

        return true;
    }

    
	/**
     * 保存微信分账信息
     * @param $orderInfo array 订单信息
     * @param $date array 商家收入信息
     * @param $pay_config array 支付参数信息
     * @return array
     */
    public function saveWeixinProfitSharing($orderInfo, $payOrders){
    
        $takeFromSytemTake = false; // 用户推广金额由抽成分账
        $sendSpread = false; // 用户推广金额是否分账
        $sendSytemTake = true; // 是否给平台抽成分账（非自有支付不需要给平台抽成分账）

        $receiversAll = [];
        if($orderInfo['uid']){// 查看用户推广分佣信息
            $spread_condition['order_type'] = $orderInfo['order_type'];
            $spread_condition['order_id'] = $orderInfo['order_id'];
            $spread_condition['status'] = 0;

            // 推广分佣列表
            $nowSpread = (new UserSpreadListService)->getSome($spread_condition);

        }
        $merchantGetMoney = 0;
        $systemTake = $orderInfo['system_take']; // 平台抽成
        $level = 0;
        foreach($payOrders as $_payOrder){
            $receivers = [];
            $payConfig = $_payOrder['pay_config'] ? json_decode($_payOrder['pay_config'], true) : '';

            if($_payOrder['channel'] == 'wechat' && $payConfig['open_weixin_profit_sharing']){// 微信分账 (暂时不考虑同一笔订单不同渠道的支付)
                // 支付金额
                $payMoney = ($_payOrder['paid_money'] - $_payOrder['refund_money'])/100;

                // 可分账金额
                $money = get_format_number($payMoney * 0.3);

                if($_payOrder['is_own'] && !cfg('open_spread_by_merchant')){// 商家自有支付且用户推广佣金由平台出，推广金额不得大于平台抽成，多出的金额不得获得分账
                    $takeFromSytemTake = true;
                }elseif($_payOrder['is_own'] || (!$_payOrder['is_own'] && !cfg('open_spread_by_merchant'))){// 非自有支付且用户推广佣金由商家出 不需给用户分账
                    $sendSpread = true; // 用户推广金额是否分账
                }

                $paidMoney = 0;
                if($nowSpread){// 用户推广分佣分账
                    foreach($nowSpread as $v){
                        $level = $v['level'];

                        if($v['money'] > 0 && $v['spread_type'] == 0 && $sendSpread){// 保存分账信息 用户获得的分账
                            if($takeFromSytemTake && $systemTake < $v['money']){// 平台抽成的钱小于用户推广退佣，则用户不会获得推广分佣的金额
                                continue;
                            }                 
                            
                            if($money < $v['money']){// 不能大于微信最大分账金额
                                continue;
                            }

                            // 获得分佣的用户
                            $spread_user = (new UserService)->getUser($v['uid']);
                            if($_payOrder['env'] == 'wechat_mini'){// 小程序
                                $openid = $spread_user['wxapp_openid'];
                            }else{
                                $openid = $spread_user['openid'];
                            }
                            
                            if($spread_user && $openid && !isset($receiversAll[$openid])){// 存在微信openid
                                $systemTake -= $v['money']; // 平台抽成减去用户分佣金额
                                $money -= $v['money'];
                                $paidMoney += $v['money'];
                                $temp = [
                                    'type' => 'PERSONAL_OPENID',
                                    'account' => $openid,
                                    'amount' => intval($v['money'] * 100),
                                    'description' => '用户消费获得推广佣金',
                                ];
                                $receivers[] = $temp;

                                $receiversAll[$spread_user['openid']] = $temp;
                            } 
                        }

                    }
                }
                
                // 分账信息
                $addData[$_payOrder['orderid']] = [
                    'mer_id' => $orderInfo['mer_id'],
                    'percent' => $orderInfo['percent'],
                    'order_type' => $orderInfo['order_type'],
                    'order_id' => $orderInfo['order_id'],
                    'third_id' => $_payOrder['paid_extra'],
                    'orderid' => $_payOrder['orderid'],
                    'paid_money' => $paidMoney, // 分账金额
                    'is_withdraw' => $level == 0 ? 1 : 0,// $level=0直接结算 否则佣金需要不可售后才能结算也要结算后才能分账
                    'is_v20' => 1,
                    'receivers' => $receivers ? json_encode($receivers) : '', // 分账接收方列表
                    'create_time' => time(),
                ];
                
                if($_payOrder['is_own']){// 记录自有支付给商家的分账
                    $merchantGetMoney += $payMoney - $paidMoney;
                }

                // 订单id
                if(in_array($orderInfo['order_type'], ['group', 'group_combine', 'shop', 'mall', 'dining'])) {
                    $addData[$_payOrder['orderid']]['order_id'] = $orderInfo['real_orderid'];
                }else{
                    $addData[$_payOrder['orderid']]['order_id'] = $orderInfo['order_id'];
                }
            }
        }

        if($systemTake){// 平台抽成分账
            foreach($payOrders as $_payOrder){
                $receivers = [];
                $payConfig = $_payOrder['pay_config'] ? json_decode($_payOrder['pay_config'], true) : '';
    
                if($_payOrder['channel'] == 'wechat' && $payConfig['open_weixin_profit_sharing']){// 微信分账 (暂时不考虑同一笔订单不同渠道的支付)
                    // 支付金额
                    $payMoney = $_payOrder['paid_money'] - $_payOrder['refund_money'];
    
                    // 可分账金额
                    $money = $payMoney * 0.3;
                     
                    if($_payOrder['is_own'] == 0){// 非自有支付 只需给用户分账不给平台分账
                        $sendSytemTake = false; // 是否给平台抽成分账（非自有支付不需要给平台抽成分账）
                    }

                    if($sendSytemTake){// 平台获得的分账 自有支付
                        $money -= $addData[$_payOrder['orderid']]['paid_money'];// 减去该订单已分账的金额
                        
                        $receivers = json_decode($addData[$_payOrder['orderid']]['receivers'], true);

                        $nowSystemTake = min($money, $systemTake);
                        $systemTake -= $nowSystemTake;

                        $account = '';
                        switch ($_payOrder['env']) {
                            case 'wechat_h5'://微信端
                                $account = $payConfig['pay_weixin_mchid'];
                                break;
                            case 'wechat_mini':
                                $account = $payConfig['pay_wxapp_mchid'];
                                break;
                            case 'iosapp':
                            case 'androidapp':
                                $account = $payConfig['pay_weixinapp_mchid'];
                                break;
                            case 'h5':
                                $account = $payConfig['pay_weixinh5_mchid'];
                                break;
                        }

                        $receivers[] = [
                            'type' => 'MERCHANT_ID',
                            'account' => $account,
                            'amount' => intval($nowSystemTake * 100),
                            'description' => '用户消费获得商家抽成',
                        ];
                        $addData[$_payOrder['orderid']]['receivers'] = $receivers ? json_encode($receivers) : '';
                        $addData[$_payOrder['orderid']]['paid_money'] += $nowSystemTake;
                        
                        if($_payOrder['is_own']){// 记录自有支付给商家的分账
                            $merchantGetMoney -= $nowSystemTake;
                        }
                    }
                }
            }
        }

        // 记录分账记录
        if($addData){
            $res = (new WeixinProfitSharingRecordService)->addAll($addData);
            if($res == false){
                throw new \think\Exception('分账添加失败', 1005);
            }
        }

        $returnArr = [];
        $returnArr['send_spread'] = $sendSpread;
        $returnArr['is_need'] = $addData ? 1 : 0;
        $returnArr['merchant_get_money'] = $merchantGetMoney;
        return $returnArr;
    }

    /**
     * 执行微信分账
     * @param $info array 订单信息
     * @return array
     */
    public function weixinProfitSharing($info){
       
        // 分账列表
        $receivers = $info['receivers'] ? json_decode($info['receivers'], true) : [];

        // 支付订单信息
        $payOrder = (new PayService)->getByExtendsField(['orderid' => $info['orderid']]);

        $payConfig = $payOrder['pay_config'] ? json_decode($payOrder['pay_config'], true) : '';
        
        // 查询商家余额记录
        $money_where = [
            'order_id' => $info['order_id'],
            'type' => $info['order_type'],
            'income' => 1
        ];
        $merchant_money = $this->getOne($money_where);

        //new 支付通道 调用第三方支付
        $channel_obj = ChannelService::getChannelService($payOrder['channel'], $payOrder['env'], $payConfig);
         if($receivers){// 单次分账
            // 添加分账接收方关系
            $result = $channel_obj->profitsharingaddreceiver($info);
            
            // 调用单次分账接口
            try{
                $return = $channel_obj->profitSharing($info);

                if($return['order_id']){
                    foreach($receivers as $value){
                        if($value['type'] == 'PERSONAL_OPENID'){// 分账成功后扣除用户余额
                            $nowUser = (new UserService)->getUser($value['account'], 'openid');
                            if (empty($nowUser)) {
                                $nowUser = (new UserService)->getUser($value['account'], 'wxapp_openid');
                            }
                            (new UserService)->userMoney($nowUser['uid'],$value['amount']/100,L_("微信分账扣除余额")); 
                        }
                    }

                    // 更新描述
                    $desc = $merchant_money['desc'].'分账单号:'.$return['order_id'];
                    $this->merchantMoneyListModel->where($money_where)->update(['desc' => $desc]);
                }

            }catch(\Exception $e){
                if(stripos($e->getMessage(),'AMOUNT_OVERDUE')){// 分账金额超出最大分账比例
                    $amount = 0;
                    foreach($receivers as $value){
                        $amount += $value['amount'];
                    }

                    $desc_pay_for_system = L_("对商家自有支付抽成，在线自有支付支付金额：X1，扣除商家余额：X2",array("X1" => $payOrder['paid_money'],"X2" => $amount/100)); 

                    $this->useMoney($info['mer_id'],$amount/100, $info['order_type'], $desc_pay_for_system,$info['order_id'],$info['percent'],$amount/100,array(),$merchant_money['store_id']);
                    (new WeixinProfitSharingRecordService())->updateThis(['id'=>$info['id']], ['receivers' => '','paid_money'=>0]);
                }
                throw new \think\Exception($e->getMessage());
            }
            
        }else{// 完成分账
            $return = $channel_obj->profitsharingfinish($info);
            if($return['order_id']){
                // 更新描述
                $desc = $merchant_money['desc'].'分账单号:'.$return['order_id'];
                $this->merchantMoneyListModel->where($money_where)->update(['desc' => $desc]);
            }
        }
        return $return;
    }

	/**
     * 增加金额积分处理
     * @param $orderInfo array 订单信息
     * @param $nowStore array 店铺信息
     * @param $nowUser array 用户信息
     * @param $scoreName array 积分信息
     * @return array
     */
    public  function setScore($systemTake, $orderInfo, $nowStore){
        
        if(cfg('add_score_by_system_commission')==1){// 定制 积分按抽成算
            // 商家获得积分
            $merchantGetScore = getFormatNumber($systemTake*cfg('add_score_by_system_commission_merchant_percent')/100);
            invoke_cms_model('Merchant_get_score/addScoreLog',['mer_id'=>$orderInfo['mer_id'],'score_count'=>$merchantGetScore]);
        }

        if( $orderInfo['uid']>0 && (cfg('add_score_by_percent') ==1 || cfg('add_score_by_system_commission') == 1) && (cfg('open_score_discount')==0 || $orderInfo['score_discount_type']!=2)){
            // 积分名
            $scoreName = cfg('score_name');

            $nowMerchant = (new MerchantService())->getMerchantByMerId($orderInfo['mer_id']);

            // 用户信息
            $nowUser  = (new UserService())->getUser($orderInfo['uid']);

            // 获得积分推广推送消息内容
            $shopDescArr = $this->getshowDesc($orderInfo, $nowStore, $nowUser);
            $scoreDesc = $shopDescArr['scoreDesc'];//积分描述
            $spreadDesc = $shopDescArr['spreadDesc'];//推广描述
            $fundDesc = $shopDescArr['fundDesc'];//商家获得平台采购备用金描述
            $descInfo = [
                'mer_id' => $shopDescArr['mer_id'],//订单商家id
                'store_id' => $shopDescArr['store_id'],//订单店铺id
                'order_id' => $shopDescArr['order_id'],//订单id
                'order_type' => $shopDescArr['order_type']//业务类型
            ];
            
            if(cfg('add_score_by_system_commission')==1){// 定制 积分按抽成算
                // 用户获得积分
                $userGetScore = getFormatNumber($systemTake*cfg('add_score_by_system_commission_user_percent')/100);
                (new UserService())->addScore($orderInfo['uid'], $userGetScore,$scoreDesc,0,$descInfo);
            }elseif(cfg('open_score_pay_back')==1 && cfg('score_get_times')>0 && round($systemTake)>0){
                $scorePayBack = $systemTake*cfg('score_get_times');
                (new UserService())->addScore($orderInfo['uid'], $scorePayBack,$scoreDesc,0,$descInfo);
            } else if (cfg('add_score_by_percent') == 1 && $orderInfo['uid'] > 0 && $orderInfo['total_money'] > 0) {
                //根据订单金额获取积分
                if ($nowMerchant['score_get_percent'] > 0) {
                    $userGetScore = getFormatNumber($systemTake * $nowMerchant['score_get_percent'] / 100);
                } else if (cfg('open_score_get_percent') == 1) {
                    //百分比
                    $userGetScore = getFormatNumber($systemTake * cfg('score_get_percent') / 100);
                } else {
                    //积分
                    $userGetScore = getFormatNumber($systemTake * cfg('user_score_get'));
                }
                (new UserService())->addScore($orderInfo['uid'], $userGetScore, $scoreDesc);
            }

            // 商家获得积分按抽成算
            if(cfg('open_score_pay_back_mer')==1 && cfg('score_get_times_mer')>0 && round($systemTake)>0){
                $scorePayBackMer = $systemTake*cfg('score_get_times_mer');
                (new MerchantService())->addSystemScore($orderInfo, $systemTake, $scorePayBackMer,$scoreDesc);
            }

            // 商家推广佣金获得积分按抽成算
            if(cfg('system_take_spread_percent_mer')>0){
                 $res = (new MerchantSpreadService())->addSpreadList($orderInfo,$nowUser,$orderInfo['order_type'],$spreadDesc,$systemTake);
            }

            // 商家获得平台采购备用金
            if(cfg('reserve_fund_percent')>0){
                $reserveFund= $systemTake*cfg('reserve_fund_percent')/100;
                $res = (new MerchantService())->addReserveFund($orderInfo, $reserveFund,$fundDesc);
            }

            (new ScrollMsgService())->addMsg($orderInfo['order_type'],$orderInfo['uid'],L_('用户x1于x2购买 x3产品成功并消费获得x4',array('x1'=>str_replace_name($nowUser['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>cfg(''.$orderInfo['order_type'].'_alias_name'),'x4'=>$scoreName)));
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 获得抽成比例
     * @param $orderInfo array 订单信息
     * @param $nowStore array 店铺信息
     * @param $nowUser array 用户信息
     * @param $scoreName array 积分信息
     * @return array
     */
    public  function getPercentByOrder($merId, $orderInfo, $money){
        $percentRateService = new PercentRateService();
        $unionMerId = $orderInfo['union_mer_id'] ?? 0;
         // 联盟商家
        if ($unionMerId) {
            $merchantBalance = $orderInfo['merchant_balance'];
        }

        fdump($orderInfo, 'percentDetail');
        $unionPercent = 100;
        switch($orderInfo['order_type']){
            case 'group':
                $percent = $percentRateService->getPercentRate($merId,$orderInfo['order_type'],$money,$orderInfo['group_id'], $orderInfo['store_id'], true);
            break;
            case 'meal':
                if($orderInfo['order_from']==1){
                    // 是否开启商家餐饮折扣功能 开启后，平台可以为每个商家来设置餐饮折扣
                    if(cfg('is_open_merchant_foodshop_discount')==1){
                        // $where = array('real_orderid'=>$orderInfo['order_id']);
                        // $percent = D('Foodshop_order')->where($where)->getField('mer_table_scale');
                    }else{
                        $percent = 0;
                    }

                    if($percent==0 || $percent==100){
                        $percent = $percentRateService->getPercentRate($merId, 'meal_scan', $money, '', $orderInfo['store_id'], true);
                        if ($unionMerId) {
                            // 联盟商家抽成比例
                            $unionPercent = $percentRateService->getPercentRate($unionMerId,'meal_scan',$merchantBalance, '',$orderInfo['store_id'], true);
                        }
                    }
                }else{
                    $percent = $percentRateService->getPercentRate($merId,$orderInfo['order_type'],$money, '',$orderInfo['store_id'], true);
                    if ($unionMerId) {
                        // 联盟商家抽成比例
                        $unionPercent = $percentRateService->getPercentRate($unionMerId,$orderInfo['order_type'],$merchantBalance, '',$orderInfo['store_id'], true);
                    }
                }
            break;
            case 'dining'://餐饮2.0
                $percent = $percentRateService->getPercentRate($merId,$orderInfo['order_type'],$money, '',$orderInfo['store_id'], true);
                if ($unionMerId) {
                    // 联盟商家抽成比例
                    $unionPercent = $percentRateService->getPercentRate($unionMerId,$orderInfo['order_type'],$merchantBalance, '',$orderInfo['store_id'], true);
                }
                break;
            case 'market':
            case 'market_integral_mall':
                $percent = $percentRateService->getMarketPercent($merId);
            break;
            case 'marketcancel':
                $percent = 0;
            break;
            case 'sub_card':
                $percent = $orderInfo['percent'];
            break;
            case 'shop':
                if($orderInfo['order_from']==1){
                    $orderInfo['order_type']='mall';
                    $percent = $percentRateService->getPercentRate($merId,'mall',$money, '',$orderInfo['store_id'], true);
                    if ($unionMerId) {
                        // 联盟商家抽成比例
                        $unionPercent = $percentRateService->getPercentRate($unionMerId,'mall',$merchantBalance, '',$orderInfo['store_id'], true);
                    }
                }
            break;
            default:
                $percent = $percentRateService->getPercentRate($merId,$orderInfo['order_type'],$money, '',$orderInfo['store_id']??0, true);

                if ($unionMerId) {
                    // 联盟商家抽成比例
                    $unionPercent = $percentRateService->getPercentRate($unionMerId,$orderInfo['order_type'],$merchantBalance, '',$orderInfo['store_id'], true);
                }
        }

        // 商家信息
        $merchantService = new MerchantService();
        $nowMerchant = $merchantService->getMerchantByMerId($merId);

        if(cfg('open_fans_percent')==1 && $nowMerchant['fans_percent']>0){
            $percent = $nowMerchant['fans_percent'];
        }
        $returnArr['percent'] = $percent;
        $returnArr['union_percent'] = $unionPercent;
        return $returnArr;
    }

	/**
     * 获得积分推广推送消息内容
     * @param $orderInfo array 订单信息
     * @param $nowStore array 店铺信息
     * @param $nowUser array 用户信息
     * @param $scoreName array 积分信息
     * @return array
     */
    public  function getshowDesc($orderInfo,$nowStore,$nowUser){
        // 积分名
        $scoreName = cfg('score_name');
        // 返回数组
        $returnArr = [];
        switch($orderInfo['order_type']){
            case 'store':
            case 'cash':
                $scoreDesc = L_("在X1 中使用X2支付了X3 获得X4",array("X1" => $nowStore['name'],"X2" => cfg('cash_alias_name'),"X3" => floatval($orderInfo['total_money']) . cfg('Currency_txt'),"X4" => $scoreName)); 
                $spreadDesc = L_("X1用户X2获得佣金",array("X1" => $nowUser['nickname'],"X2" => cfg('cash_alias_name'))); 
                $fundDesc = L_("X1用户X2获得备用金",array("X1" => $nowUser['nickname'],"X2" => cfg('cash_alias_name'))); 
                break;
            case 'group':
                $scoreDesc = L_("购买 X1 消费X2 获得X3",array("X1" => $orderInfo['order_name'],"X2" => floatval($orderInfo['total_money']) . cfg('Currency_txt'),"X3" => $scoreName)); 
                $spreadDesc = L_("X1用户购买X2获得佣金",array("X1" => $nowUser['nickname'],"X2" => cfg('group_alias_name'))); 
                $fundDesc = L_("X1用户购买X2获得备用金",array("X1" => $nowUser['nickname'],"X2" => cfg('group_alias_name'))); 
                break;
            case 'shop':
                $scoreDesc = L_("在 X1 中消费X2 获得X3",array("X1" => $nowStore['name'],"X2" => floatval($orderInfo['total_money']) . cfg('Currency_txt'),"X3" => $scoreName)); 
                $spreadDesc = L_("X1用户购买X2获得佣金",array("X1" => $nowUser['nickname'],"X2" => cfg('shop_alias_name'))); 
                $fundDesc = L_("X1用户购买X2获得备用金",array("X1" => $nowUser['nickname'],"X2" => cfg('shop_alias_name'))); 
                break;
            case 'mall':
                $scoreDesc = L_("在 X1 中消费X2 获得X3",array("X1" => $nowStore['name'],"X2" => floatval($orderInfo['total_money']) . cfg('Currency_txt'),"X3" => $scoreName)); 
                $spreadDesc = L_("X1用户购买X2获得佣金",array("X1" => $nowUser['nickname'],"X2" => cfg('mall_alias_name'))); 
                $fundDesc = L_("X1用户购买X2获得备用金",array("X1" => $nowUser['nickname'],"X2" => cfg('mall_alias_name'))); 
                break;
            case 'meal':
            case 'dining':
                $scoreDesc =L_("购买X1商品获得X2",array("X1" => cfg('meal_alias_name'),"X2" => $scoreName)); 
                $spreadDesc = L_("X1用户购买X2获得佣金",array("X1" => $nowUser['nickname'],"X2" => cfg('meal_alias_name'))); 
                $fundDesc = L_("X1用户购买X2获得备用金",array("X1" => $nowUser['nickname'],"X2" => cfg('meal_alias_name'))); 
                break;
            case 'shop_offline':
                $scoreDesc = L_("在 X1 中消费X2 获得X3",array("X1" => $nowStore['name'],"X2" => floatval($orderInfo['total_money']) . cfg('Currency_txt'),"X3" => $scoreName)); 
                $spreadDesc = L_("X1用户购买X2线下零售获得佣金",array("X1" => $nowUser['nickname'],"X2" => cfg('shop_alias_name'))); 
                $fundDesc = L_("X1用户购买X2线下零售获得备用金",array("X1" => $nowUser['nickname'],"X2" => cfg('shop_alias_name'))); 
                break;
            case 'appoint':
                $scoreDesc = L_("购买X1商品获得X2",array("X1" => cfg('appoint_alias_name'),"X2" => $scoreName)); 
                $spreadDesc = L_("X1用户购买X2获得佣金",array("X1" => $nowUser['nickname'],"X2" => cfg('appoint_alias_name'))); 
                $fundDesc = L_("X1用户购买X2获得备用金",array("X1" => $nowUser['nickname'],"X2" => cfg('appoint_alias_name'))); 
                break;
            case 'wxapp':
                $scoreDesc = L_("购买 X1 消费X2 获得X3",array("X1" => $orderInfo['order_id'],"X2" => floatval($orderInfo['money']).cfg('Currency_txt'),"X3" => $scoreName)); 
                $spreadDesc = L_("X1用户购买营销获得佣金",array("X1" => $nowUser['nickname'])); 
                $fundDesc = L_("X1用户购买营销获得备用金",array("X1" => $nowUser['nickname'])); 
                break;
            case 'weidian':
                $scoreDesc = L_("购买 X1 消费X2 获得X3",array("X1" => $orderInfo['order_id'],"X2" => floatval($orderInfo['money']).cfg('Currency_txt'),"X3" => $scoreName)); 
                $spreadDesc = L_("X1用户购买微店获得佣金",array("X1" => $nowUser['nickname'])); 
                $fundDesc = L_("X1用户购买微店获得备用金",array("X1" => $nowUser['nickname'])); 
                break;
            case 'lifetools':
                $scoreDesc = L_("购买 X1 消费X2 获得X3",array("X1" => $orderInfo['order_id'],"X2" => floatval($orderInfo['total_money']).cfg('Currency_txt'),"X3" => $scoreName));
                $spreadDesc = L_("X1用户购买体育健身获得佣金",array("X1" => $nowUser['nickname']));
                $fundDesc = L_("X1用户购买体育健身获得备用金",array("X1" => $nowUser['nickname']));
                break;
            case 'lifetoolscard':
                $scoreDesc = L_("购买 X1 消费X2 获得X3",array("X1" => $orderInfo['order_id'],"X2" => floatval($orderInfo['total_money']).cfg('Currency_txt'),"X3" => $scoreName));
                $spreadDesc = L_("X1用户购买景区次卡获得佣金",array("X1" => $nowUser['nickname']));
                $fundDesc = L_("X1用户购买景区次卡获得备用金",array("X1" => $nowUser['nickname']));
                break;
        }
        $returnArr['scoreDesc'] = $scoreDesc;
        $returnArr['spreadDesc'] = $spreadDesc;
        $returnArr['fundDesc'] = $fundDesc;
        $returnArr['mer_id'] = $orderInfo['mer_id']??0;
        $returnArr['store_id'] = $orderInfo['store_id']??0;
        $returnArr['order_id'] = $orderInfo['order_id']??0;
        $returnArr['order_type'] = $orderInfo['order_type'];
        return $returnArr;
    }

    /**
     * 减少余额
     * @param $merId int 商家id
     * @param $orderId array 店铺信息
     * @param $nowUser array 用户信息
     * @param $scoreName array 积分信息
     * @param $extra array    //后来的同事请不要在额外增加其他参数了，全部在extra里进行拓展。extra.platform_get: 第三方分账系统平台抽成扣除分账金额。单位:默认为元，extra.merchant_get: 第三方分账系统商家扣除分账金额。单位:默认为元
     * @return array
     */
     public function useMoney($merId, $money, $type, $desc, $orderId, $percent=0, $systemTake = 0, $mer_user=array(), $storeId=0,$extra = []){
        $merchantService = new MerchantService();
        $data['mer_id'] = $merId;
        $data['income'] = 2;
        $data['order_id'] = $orderId;
        if($storeId>0){
            $data['store_id'] = $storeId;
        }

        if($percent){
            $data['percent'] = $percent;
            $data['system_take'] = $systemTake;
        } elseif ($systemTake && cfg('merchant_withdraw_fee_type')==1&& cfg('company_pay_mer_money')>0) {
            $data['system_take'] = $systemTake;
        }
        $data['use_time']= time();
        $data['type']= $type;
        $data['desc']=  $desc;
        $data['money']=  $money;

        
        $res = $merchantService->useMoney($merId,$data['money']);
        if(!$res){
            return false;
        }

        // 商家信息
        $nowMerchant = $merchantService->getMerchantByMerId($merId);
         fdump('$nowMerchant','getRefundMerchentMoney',1);
         fdump($merId,'getRefundMerchentMoney',1);
         fdump($nowMerchant,'getRefundMerchentMoney',1);

        $merStatus = 1;
        if(cfg('open_mer_owe_money')==0){
            $nowMerchant['mch_owe_money'] = 0;
        }

         // 模板消息内容
         $msgData = [
             'href' => '',
             'wecha_id' => $nowMerchant['openid'] ?? '',
             'keyword1' => L_('商家余额使用'),
             'keyword2' =>$money,
             'keyword3' =>'',
             'keyword4' => date("Y年m月d日 H:i"),
             'keyword5' =>$data['order_id'],
             'remark' => L_('请到商家中心商家余额中查看')
         ];
        if($nowMerchant['money']<$nowMerchant['mch_owe_money']*(-1)){
            $data = [
                'status' => 3
            ];
            $merchantService->updateByMerId($merId, $data);
            $merStatus = 3;

            // 发送欠款通知
            $this->merchantOweMoneyNotice($merId);
        }else  if($nowMerchant['money']<0){
            // 发送模板消息
            $msgData['first'] =  L_("X1，当前商家余额：X2，您的商家状态为欠费，您的商家业务状态为禁止状态，请及时充值",array("X1" => $desc,"X2" => $nowMerchant['money']));
            (new TemplateNewsService())->sendTempMsg('OPENTM402026291', $msgData, $merId);
        }

        if(!empty($mer_user['openid'])){
            if($merStatus==3){
                // 发送模板消息
                $msgData['first'] =  L_("X1，当前商家余额：X2，您的商家状态为欠费，您的商家业务状态为禁止状态，请及时充值",array("X1" => $desc,"X2" => $nowMerchant['money']));
                (new TemplateNewsService())->sendTempMsg('OPENTM402026291', $msgData, $merId);
            }else{
                // 发送模板消息
                $msgData['first'] =  L_("X1，当前商家余额：X2",array("X1" => $desc,"X2" => $nowMerchant['money']));
                (new TemplateNewsService())->sendTempMsg('OPENTM402026291', $msgData, $merId);
            }
        }

        $data['now_mer_money'] = $nowMerchant['money'];
        if (isset($extra['platform_get'])) {
            $data['platform_get'] = $extra['platform_get'];
        }
        if (isset($extra['merchant_get'])) {
            $data['merchant_get'] = $extra['merchant_get'];
        }
        if(!$this->add($data)){
            return false;
            // return array('error_code'=>true,'msg'=>$desc.'，'.L_('保存失败！'));
        }

        $nowMerchant = $merchantService->getMerchantByMerId($merId);
        if(cfg('nmgzhcs_appid') && $nowMerchant['nmgzhcs_appid']){
            $this->nmgzhcsMoney($nowMerchant['nmgzhcs_appid'],$data['order_id'],'-'.$money,$desc);
        }
        return true;
        
    }

    /**
     * 发送欠款通知
     * @param $merId int 商家id
     * @return array
     */
    public function merchantOweMoneyNotice($merId){
        $where['mer_id'] = $merId;
        
        // 商家信息
        $merchant = (new MerchantService())->getMerchantByMerId($merId);

        // 管理员列表
        $whereAdmin[] = ['openid' , '<>', ''];
        $whereAdmin[] = ['withdraw_notice' , '=', '1'];
        $adminList = (new AdminUserService())->getList($whereAdmin);

        $tmpArea = [
            $merchant['area_id'],
            $merchant['city_id'],
            $merchant['province_id']
        ];
        foreach ($adminList as $v) {
            if($v['level']==2 || $v['level']==0){
                $send_to[] = $v;
            }else if(in_array($v['area_id'],$tmpArea)){
                $send_to[] = $v;
            }
        }
        // $model = new templateNews(cfg('wechat_appid'), cfg('wechat_appsecret'));
        // foreach ($send_to as $s) {
        //     $href ='';
        //     $model->sendTempMsg('OPENTM401300510', array('href' => $href,
        //         'wecha_id' => $s['openid'],
        //         'first' => L_('欠款通知'),
        //         'keyword1' =>"商家【{$merchant['name']}】",
        //         'keyword2' => L_('欠款').$merchant['money'],
        //         'remark' =>L_('时间X1,您的帐户余额已欠费，请尽快充值',date('Y-m-d H:i',time()))),
        //     0);
        // }
    }

    public function nmgzhcsMoney($business_appid,$order_id,$money,$desc){
		$appid = cfg('nmgzhcs_appid') ;
		$appsecret = cfg('nmgzhcs_appsecret');

		$url = cfg('nmgzhcs_base_url').'/api/businessmoney/index';

		$params=array(
			'appid'=>$appid,
			'business_appid'=>$business_appid,
			'money'=>$money,
			'order_code'=>$order_id,
			'xnote'=>$desc,
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		$params['sign'] = $sign;

		$res = http_request($url,'POST',$params);
		$res = json_decode($res[1],true);
	}

    public  function getAliasName(){
        return array(
            'all'=>L_('选择分类'),
            'group'=>cfg('group_alias_name'),
            'shop'=>cfg('shop_alias_name'),
            'mall'=>cfg('mall_alias_name'),
            'meal'=>cfg('meal_alias_name'),
            'dining'=>cfg('meal_alias_name'),
            'appoint'=>cfg('appoint_alias_name'),
            'waimai'=>L_('外卖'),
            'store'=>cfg('cash_alias_name'),
            'cash'=>L_('到店支付'),
            'weidian'=>L_('微店'),
            'wxapp'=>L_('营销'),
            'withdraw'=>L_('提现'),
            'coupon'=>L_('优惠券'),
            'lifetools'=>L_('景区体育健身'),
            'lifetoolscard'=>L_('景区次卡'),
            'activity'=>L_('平台活动'),
            'spread'=>L_('商家推广'),
            'sub_card'=>L_('免单套餐'),
            'score_pay_back'=>cfg('score_name').L_('返现'),
            'gift'=>cfg('score_name').L_('商城'),
            'village_group'=>cfg('village_group_alias'),
            'award'=>L_('奖励金'),
            'integral_mall'=>L_('商家积分商城'),
        );
    }

    /**
     * 统计某个字段
     * @param  $where array 查询条件
     * @param  $field string 需要统计的字段
     * @author hengtingmei
     * @return string
     */
    public function getTotalByCondition($where, $field){
        $res = $this->merchantMoneyListModel->getTotalByCondition($where,$field);
        if(!$res){
            return 0;
        }

        return $res['total'];
    }

	/**
     * 根据条件获取其他模块店铺列表
     * @param $merId int 商家id
     * @return array
     */
    public function getMerchantByMerId($merId) {
        if(empty($merId)){
           return [];
        }

        $merchant = $this->merchantModel->getMerchantByMerId($merId);
        if(!$merchant) {
            return [];
        }
        
        return $merchant->toArray(); 
    }

    /**
     * 更新数据
     * @param $merId int 商家id
     * @return array
     */
    public function updateByMerId($merId, $data) {
        if(empty($merId) || empty($data)){
           return false;
        }

        $where = [
            'mer_id' => $merId
        ];
        $result = $this->merchantModel->where($where)->update($data);
        if(!$result) {
            return false;
        }
        
        return $result; 
    }
    
    
    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $order = $this->merchantMoneyListModel->getOne($where);
        if(!$order) {
            return [];
        }
        return $order->toArray();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $result = $this->merchantMoneyListModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->merchantMoneyListModel->id;
        
    }

    public function getAll($where)
    {
        return $this->merchantMoneyListModel->where($where)->select()->toArray();
    }
    
}