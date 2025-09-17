<?php
/**
 * 团购订单
 * Created by PhpStorm.
 * Author: 衡婷妹
 * Date Time: 2020/11/18 17:10
 */

namespace app\group\model\service\order;

use app\common\model\db\MerchantStore;
use app\common\model\db\SystemOrder;
use app\common\model\service\ConfigService;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\user\DistributorService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\percent_rate\PercentRateService;
use app\common\model\service\PlatRecommendService;
use app\common\model\service\ScrollMsgService;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\user\DistributorSpreadListService;
use app\common\model\service\user\UserNoticeService;
use app\common\model\service\UserAdressService;
use app\common\model\service\UserLevelService;
use app\common\model\service\UserService;
use app\common\model\service\UserSpreadListService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\service\HouseVillageGroupService;
use app\community\model\service\HouseVillageSelectLogService;
use app\group\model\db\Group;
use app\group\model\db\GroupBuyerList;
use app\group\model\db\GroupCategory;
use app\group\model\db\GroupFoodshopPackage;
use app\group\model\db\GroupOrder;
use app\group\model\db\GroupPassRelation;
use app\group\model\db\GroupRedemptionCode;
use app\group\model\db\GroupStart;
use app\group\model\db\TempOrderData;
use app\group\model\db\TmpOrderid;
use app\group\model\service\order\GroupBookingAppointOrderService;
use app\group\model\service\group_combine\GroupCombineActivityGoodsService;
use app\group\model\service\group_combine\GroupCombineActivityService;
use app\group\model\service\GroupBookingAppointService;
use app\group\model\service\GroupRecordService;
use app\group\model\service\GroupService;
use app\group\model\service\GroupSpecificationsService;
use app\group\model\service\message\AppPushService;
use app\group\model\service\order_print\PrintHaddleService;
use app\group\model\service\StoreGroupService;
use app\hotel\model\service\TradeHotelCategoryService;
use app\hotel\model\service\TradeHotelStockService;
use app\internal_card\model\service\InternalCardService;
use app\merchant\model\service\card\CardNewService;
use app\merchant\model\service\MerchantMoneyListService;
use app\merchant\model\service\MerchantRequestService;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\pick\PickAddressService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\print_order\OrderprintService;
use app\merchant\model\service\spread\MerchantSpreadService;
use app\pay\model\db\PayOrderInfo;
use app\store_marketing\model\db\StoreMarketingPersonSetprice;
use app\store_marketing\model\db\StoreMarketingShareLog;
use app\store_marketing\model\service\StoreMarketingRecordService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use redis\Redis;
use Apppush;
use think\facade\Db;
use function GuzzleHttp\Psr7\str;

class GroupOrderService
{
    public $nowGroupOrderModel = null;
    public $weekArr = [];
    public function __construct()
    {
        $this->groupOrderModel = new GroupOrder();
        $this->weekArr = [
            0 => L_('周日'),
            1 => L_('周一'),
            2 => L_('周二'),
            3 => L_('周三'),
            4 => L_('周四'),
            5 => L_('周五'),
            6 => L_('周六'),
        ];
    }

    /**
     * 获取提交页详情
     * @param $param array 数据
     * @return array
     */
    public function getSaveOrderDetail($param)
    {
        //团购商品id
        $nowGroupId = $param['group_id'] ?? 0;
        $specificationsId = $param['specifications_id'] ?? 0;// 规格id
        $share_id = $param['share_id'] ?? 0;//分享ID
        $gid = $param['gid'] ?? 0;// 拼团id
        $num = $param['num'] ?? 0;// 购买商品数量
        $num = intval($num);
        $storeId = $param['store_id'] ?? 0;// 从哪个店铺主页点击过来的店铺id
        $addressId = $param['address_id'] ?? 0;// 用户地址id
        $pickAddressId = $param['pick_address_id'] ?? 0;// 自提地址id
        $ruleId = $param['rule_id'] ?? 0;// 场次id（场次预约商品）
        $bookDate = $param['book_date'] ?? 0;// 场次预约预定日期
        $bookTime = $param['book_time'] ?? 0;// 场次预约预定时间（任选几小时的开始时间）
        $combineId = $param['combine_id'] ?? 0;// 套餐id
        $type = $param['type'] ?? 0;// 购买类型 1 为单独购买2 为参团 3 为发起团购 
        $isPickInStore = $param['is_pick_in_store'] ?? 0;// (实物商品)是否到点自取，0 为配送 1 到店自取
        // 商家优惠券id
        $merchantCouponId = isset($param['merchant_coupon_id']) ? $param['merchant_coupon_id'] : 0;
        // 平台优惠券id
        $systemCouponId = isset($param['system_coupon_id']) ? $param['system_coupon_id'] : 0;
        // 是否使用平台优惠券
        $useSysCoupon = isset($param['use_sys_coupon']) ? $param['use_sys_coupon'] : 0;
        // 是否使用商家优惠券
        $useMerCoupon = isset($param['use_mer_coupon']) ? $param['use_mer_coupon'] : 0;

        // 酒店分类id
        $hotelCatId = isset($param['cat_id']) ? $param['cat_id'] : 0;
        // 预定开始时间
        $hotelDepTime = isset($param['dep_time']) ? $param['dep_time'] : 0;
        // 预定结束时间
        $hotelEndTime = isset($param['end_time']) ? $param['end_time'] : 0;

        $returnArr = [];
        $returnArr['is_pick_in_store'] = $isPickInStore;
        $nowTime = time();

        // 当前用户
        $nowUser = request()->user;
        if (empty($nowUser)) {
            throw new \think\Exception(L_('未登录'), 1002);
        }

        if (empty($nowUser['phone'])) {
            $url = customization('error_return_url') ? '_returnUrl_'.cfg('site_url') . '/packapp/plat/pages/my/newnumber' : '';
            throw new \think\Exception(L_('您需要绑定手机号码').$url, 1003);
        }


        //获取下单商品的信息
        $where = [
            'group_id' => $nowGroupId,
            'status' => 1
        ];
        $nowGroup = (new GroupService())->getOne($where);
        if (empty($nowGroup)) {
            throw new \think\Exception(L_('当前X1不存在！', cfg('group_alias_name')), 1003);
        }

        $returnArr['is_baodi'] = cfg('baodi_ticket_url') && $nowGroup['custom_product_id'];
        if($returnArr['is_baodi']){
            $lastGroupOrder = (new \app\group\model\db\GroupOrder())->where([['uid', '=', $nowUser['uid']], ['userinput', '<>', '']])->order('order_id', 'DESC')->find();
            $lastGroupOrder = [];
            if ($lastGroupOrder) {
                $returnArr['userinput'] = unserialize($lastGroupOrder['userinput']);
            } else {
                $returnArr['userinput'] = ['name' => '', 'idcard' => ''];
            }
        }

        if ($nowGroup['group_cate'] != 'booking_appoint') {// 场次预约没有开始结束时间
            if ($nowGroup['begin_time'] > $nowTime) {
                throw new \think\Exception(L_('此单X1还未开始！', cfg('group_alias_name')), 1003);
            }

            if ($nowGroup['end_time'] < $nowTime) {
                throw new \think\Exception(L_('此单X1已结束！', cfg('group_alias_name')), 1003);
            }
        } else {
            if (empty($ruleId)) {
                throw new \think\Exception(L_('请选择预定场次'), 1003);
            }
        }

        if ($nowGroup['type'] == 3 || $nowGroup['type'] == 4) {
            throw new \think\Exception(L_('此单X1已被抢完！', cfg('group_alias_name')), 1008);
        }

        if ($ruleId) {// 场次预约
            $num = 1;// 购买数量默认为1
            $ruleInfo = (new GroupBookingAppointService())->getRuleByRuleId($ruleId);
            if (empty($ruleInfo) || $ruleInfo['group_id'] != $nowGroupId) {
                throw new \think\Exception(L_('当前场次不存在或已删除'), 1008);
            }

            if (empty($bookDate)) {
                throw new \think\Exception(L_('请选择场次日期'), 1003);
            }

            if ($ruleInfo['use_hours'] && empty($bookDate)) {
                throw new \think\Exception(L_('请选择场次开始时间'), 1003);
            }
            $nowGroup['price'] = $ruleInfo['default_price'];// 修改商品单价为场次单价

            // 获得场次价格信息
            $priceInfo = (new GroupBookingAppointService())->getRulePrice($ruleId, $bookDate);
            if ($priceInfo && $priceInfo['is_sale'] == 0) {// 停售
                throw new \think\Exception(L_('该场次X1已停售，请重新选择预定日期', $bookDate), 1008);
            }

            if ($priceInfo) {// 优先使用价格日历的值
                $nowGroup['price'] = $priceInfo['price'];// 修改商品单价为场次单价
            }

            // 获得场次的套餐列表
            $combineList = (new GroupBookingAppointService())->getCombineByRuleId($ruleId);
            $all = [
                'combine_id' => 0,
                'name' => $ruleInfo['use_hours'] ? L_('任选X1小时', $ruleInfo['use_hours']) : '全部时间',
                'price' => '0',
                'intro' => '',
            ];
            $combineList = array_merge([$all], $combineList);
            foreach ($combineList as &$_combine) {
                $_combine['price'] = get_format_number($nowGroup['price'] + $_combine['price']);
            }

            if ($combineId) {// 套餐
                $combineInfo = (new GroupBookingAppointService())->getRuleCombineDetail($ruleId, $combineId);
                if (empty($combineInfo)) {
                    throw new \think\Exception(L_('当前套餐不存在或已删除'), 1003);
                }
                $nowGroup['price'] = $nowGroup['price'] + $combineInfo['price']; // 加上套餐价格
                $nowGroup['once_max_day'] = $combineInfo['once_max_day'];
                $nowGroup['once_max'] = $combineInfo['once_max'];
                $nowGroup['once_min'] = $combineInfo['once_min'];
                $nowGroup['count_num'] = $combineInfo['stock_num'];

                // 没有存过Redis的需要先存入redis
                try {
                    $redis = new Redis();
                    $key = 'group_'.$nowGroupId.'-r_'.$combineInfo['rule_id'].'-c_'.$combineInfo['id'];
                    if(!$redis->get($key)){
                        (new GroupService)->addRedis($nowGroup['group_id']);
                    }
                } catch (\Exception $e) {
                }
            }

            // 当天0点的时间
            $todayTime = strtotime(date('Y-m-d'));
            $ruleEndTime = $todayTime + $ruleInfo['end_time'];
            $bookEndTime = strtotime(date('Y-m-d') . ' ' . $bookTime) + $ruleInfo['use_hours'] * 3600;

            if (strtotime(date('Y-m-d') . ' ' . $bookTime) > $ruleEndTime) {// 预约开始时间大于场次结束时间
                throw new \think\Exception(L_('预约开始时间不能大于场次结束时间'), 1003);
            }
            $isEnough = true;
            if (empty($ruleInfo['use_hours'])) {
                $bookEndTime = $ruleEndTime;
            } elseif ($bookEndTime > $ruleEndTime) {// 时间不够
                $isEnough = false;
                $bookEndTime = $ruleEndTime;
            }

            if (date('Y-m-d', $bookEndTime) > date('Y-m-d')) {
                $endTime = L_('次日X1', date('H:i', $bookEndTime));
            } else {
                $endTime = date('H:i', $bookEndTime);
            }

            $cancelTxt = '';
            if ($nowGroup['cancel_type'] == 0) {
                $cancelTxt = L_('不可取消');
            } else if ($nowGroup['cancel_type'] == 1) {
                //到期前取消
                $cancelTxt = L_('X1前可随时退款，逾期不可退', date('m-d H:i', $bookEndTime-$nowGroup['cancel_hours']*3600));
            } else if ($nowGroup['cancel_type'] == 2) {
                $gv['cancel_txt'] = L_('随时可取消');
            } else if ($nowGroup['cancel_type'] == 3) {
                //开场前取消
                $cancelTxt = L_('X1前可随时退款，逾期不可退', date('m-d H:i', $todayTime+$ruleInfo['start_time']-$nowGroup['cancel_hours']*3600));
            }

            $returnArr['booking_appoint'] = [
                'rule_id' => intval($ruleId),
                'combine_id' => intval($combineId),
                'book_date' => $bookDate,
                'book_week' => $this->weekArr[date('w', strtotime($bookDate))],
                'book_time' => $bookTime,
                'book_end_time' => $endTime,
                'use_hours' => $ruleInfo['use_hours'],
                'show_time' => $bookTime.'-'.$endTime.' '.($ruleInfo['use_hours'] > 0 ? L_('任选X1小时',$ruleInfo['use_hours']): L_('全部时间')),
                'is_enough' => $isEnough,
                'enough_hour_txt' => $isEnough ? '' : L_('不足X1小时按照X2小时计费', ['X1' => $ruleInfo['use_hours'], 'X2' => $ruleInfo['use_hours']]),
                'cancel_txt' => $cancelTxt
            ];
            $returnArr['booking_appoint']['package_list'] = $combineList;

        } elseif ($specificationsId > 0) { // 规格信息
            // 查询规格参数
            $whereSpecifications = [
                'group_id' => $nowGroupId,
                'specifications_id' => $specificationsId,
                'status' => 1
            ];
            $nowGroupSpecificationsInfo = (new GroupSpecificationsService())->getOne($whereSpecifications);
            if (!$nowGroupSpecificationsInfo) {
                $nowGroupSpecificationsInfo = array();
            } else {
                $nowGroup['price'] = $nowGroupSpecificationsInfo['price'];
                $nowGroup['old_price'] = $nowGroupSpecificationsInfo['old_price'];
                $nowGroup['once_max_day'] = $nowGroupSpecificationsInfo['once_max_day'];
                $nowGroup['once_max'] = $nowGroupSpecificationsInfo['once_max'];
                $nowGroup['once_min'] = $nowGroupSpecificationsInfo['once_min'];
                $nowGroup['specifications_id'] = $specificationsId;
                $nowGroup['specifications_name'] = $nowGroupSpecificationsInfo['specifications_name'];
            }
            $returnArr['specifications_info'] = $nowGroupSpecificationsInfo;

            // 没有存过Redis的需要先存入redis
            try {
                $redis = new Redis();
                $key = 'group_'.$nowGroupId.'-s_'.$specificationsId;
                if(!$redis->get($key)){
                    (new GroupService)->addRedis($nowGroupId);
                }
            } catch (\Exception $e) {
            }
        }elseif($hotelCatId){// 绑定酒店模块
            $tradeHotel = (new TradeHotelCategoryService())->getCatPrice($nowGroup['mer_id'], $hotelCatId, $hotelDepTime, $hotelEndTime);

            $nowGroup['price'] = 0;
            $nowGroup['old_price'] = 0;
            if($tradeHotel){
                $nowGroup['price'] = $tradeHotel['discount_price'];
                $nowGroup['old_price'] = $tradeHotel['price'];
                $tradeHotel['days'] = 0;
                $tradeHotel['dep_time'] = $tradeHotel['end_time'] = '';
                $nowGroup['per_price'] = $tradeHotel['price'];
                if($hotelDepTime&&$hotelEndTime){
                    $weekarray=array("日","一","二","三","四","五","六"); //先定义一个数组
                    $tradeHotel['days'] = ceil(abs((strtotime($hotelEndTime)-strtotime($hotelDepTime))/3600/24));
                    $tradeHotel['dep_time'] = date('m.d',strtotime($hotelDepTime)).'(周'.$weekarray[date("w",strtotime($hotelDepTime))].')';
                    $tradeHotel['end_time'] = date('m.d',strtotime($hotelEndTime)).'(周'.$weekarray[date("w",strtotime($hotelEndTime))].')';
                    $tradeHotel['per_price'] = round($tradeHotel['price']/5).'/晚';
                }

            }
            $returnArr['trade_hotel'] = $tradeHotel;

        }else {// 有规格的商品必须选择规格,开启了拼团则规格不生效无需选择规格
            if($nowGroup['pin_num'] == 0){
                $whereSpecifications = [
                    'group_id' => $nowGroupId,
                    'status' => 1
                ];
                $nowGroupSpecificationsInfo = (new GroupSpecificationsService())->getOne($whereSpecifications);
                $returnArr['specifications_info'] = $nowGroupSpecificationsInfo ?: (object)[];
                if ($nowGroupSpecificationsInfo && $specificationsId < 0) {
                    // $url = $this->config['site_url'].'/wap.php?c=Groupnew&a=detail&group_id='.$_GET['group_id'];
                    throw new \think\Exception(L_('请选择规格'), 1003);
                }
            }else{
                $returnArr['specifications_info'] = (object)[];
            }

            // 没有存过Redis的需要先存入redis
            try {
                $redis = new Redis();
                $key = 'group_'.$nowGroupId;
                if(!$redis->get($key)){
                    (new GroupService)->addRedis($nowGroupId);
                }
            } catch (\Exception $e) {
            }
        }

        //店铺分销员分销商品
        $returnArr['share_id'] = 0;
        $returnArr['is_marketing_goods'] = 0;
        $setpriceData = [];
        if ($share_id > 0) {
                $shareData = (new StoreMarketingShareLog())->where([['id', '=', $share_id]])->find();
                if (empty($shareData)) {
                    throw new \think\Exception("分享码有误",1003);
                }
                if ($shareData['create_time'] > time() - 86400) {//分享链接24小时之内有效
                    $setpriceData = (new StoreMarketingPersonSetprice())->where([
                        ['share_id', '=', $shareData['id']],
                        ['person_id', '=', $shareData['person_id']],
                        ['goods_type', '=', 1],
                        ['goods_id', '=', $nowGroup['group_id']],
                        ['specs_id', '=', $specificationsId]
                    ])->find();
                    if (empty($setpriceData)) {
                        $setpriceData = [
                            'share_id' => $shareData['id'],
                            'person_id' => $shareData['person_id'],
                            'goods_id' => $nowGroup['group_id'],
                            'goods_type' => 1,
                            'specs_id' => $specificationsId,
                            'price' => $nowGroup['price'],
                            'create_time' => time()
                        ];
                        $setpriceData['id'] = (new StoreMarketingPersonSetprice())->insertGetId($setpriceData);
                    }
                    $nowGroup['price'] = $setpriceData['price'];
                    $returnArr['is_marketing_goods'] = 1;
                    $returnArr['share_id'] = $shareData['id'];
                }
        }

        // 验证购买数量是否符合
        $this->checkBuyNum($nowUser['uid'], $nowGroup['group_id'], $num, $specificationsId,0,$ruleId,$combineId);

        // 处理不同情况下的价格
        if ($type == 1) { // 原价购买
            $nowGroup['price'] = get_format_number($nowGroup['old_price']);
            $nowGroup['extra_pay_price'] = get_format_number($nowGroup['extra_pay_old_price']);
        } elseif ($type == 3) {
            $nowGroup['price'] = get_format_number(intval(round($nowGroup['price'] * $nowGroup['start_discount'])) / 100); //团长按团长折扣计算
            $startMaxNum = intval($nowGroup['start_max_num']) > 1 ? intval($nowGroup['start_max_num']) : 1; // 团长每次最多购买次数 默认取1 最小也取1
            if ($num && $num > $startMaxNum) {
                throw new \think\Exception(L_('此单团长每次最多购买X1份！' , $startMaxNum), 1003);
            }
            $nowGroup['start_max_num'] = $startMaxNum;
        } else {
            $nowGroup['price'] = get_format_number($nowGroup['price']);
        }

        //用户等级 优惠
        $userLevel = (new UserLevelService)->getSome([], true, ['id' => 'ASC']);
        $userLevel = array_column($userLevel, 'lname', 'level');
        $returnArr['level_off'] = [];
        $levelDiscount = 0;
        if ($nowGroup['trade_type'] != 'hotel' && !empty($nowUser['level'])) {
            // 会员优惠
            $leveloff = !empty($nowGroup['leveloff']) ? unserialize($nowGroup['leveloff']) : '';


            /****type:0无优惠 1百分比 2立减*******/
            if (!empty($leveloff) && isset($leveloff[$nowUser['level']]) && isset($userLevel[$nowUser['level']])) {
                $levelOff = $leveloff[$nowUser['level']];
                if ($levelOff['type'] == 1) {// 百分比
                    $levelDiscount = get_format_number($nowGroup['price'] * (1 - $levelOff['vv'] / 100));
                } elseif ($levelOff['type'] == 2) {// 立减
                    $levelDiscount = $levelOff['vv'];
                }
                $returnArr['level_off']['name'] = $userLevel[$nowUser['level']];
                $returnArr['level_off']['type'] = $levelOff['type'];
                $returnArr['level_off']['vv'] = $levelOff['vv'];
                $returnArr['level_off']['money'] = get_format_number($levelDiscount);
            }
        }

        $afterDiscountFinalprice = 0;// 折扣后商品单价
        $nowGroupDiscount = 0;// 折扣金额
        $tmpFinalprice = 0;// 临时的折扣后商品单价
        $finalprice = 0;
        // 折扣优惠方式  0 折上折 1 折扣最优
        if ($nowGroup['discount'] > 0) {
            $tmpFinalprice = round($nowGroup['price'] * $nowGroup['discount'] / 10, 2);
            $nowGroupDiscount = $nowGroup['price'] - $tmpFinalprice;
        }

        if ($nowGroup['vip_discount_type'] == 1) {// 折扣最优
            $finalprice = $nowGroup['price'] * $nowGroup['discount'] / 10;
            if ($nowGroupDiscount > $levelDiscount) {
                $afterDiscountFinalprice = $tmpFinalprice;
                $finalprice = 0;
                $levelDiscount = 0;

            } else {
                $afterDiscountFinalprice = $nowGroup['price'] - $levelDiscount;
                $nowGroupDiscount = 0;
            }
        } else if ($nowGroup['vip_discount_type'] == 2) {// 折上折
            // 重新计算会员等级优惠
            if ($returnArr['level_off']['type'] == 1) {
                $levelDiscount = round($tmpFinalprice * (1 - $returnArr['level_off']['vv'] / 100), 2);
            } elseif ($returnArr['level_off']['type'] == 2) {
                $levelDiscount = $returnArr['level_off']['vv'];
            }
            $afterDiscountFinalprice = $tmpFinalprice - $levelDiscount;
        } else {
            $afterDiscountFinalprice = $nowGroup['price'] - $levelDiscount;
        }
        $returnArr['level_off'] && $returnArr['level_off']['money'] = get_format_number($levelDiscount);

        // 加入拼团
        if ($gid) {
            $nowStart = (new GroupStartService())->getOne(['id' => $gid]);
            if (!$nowStart['status'] && $nowGroup['group_id'] == $nowStart['group_id']) {// 可以拼团 TODO
                // $_SESSION['gid']=$_GET['gid'];
            }

        }

        if ($nowGroup['tuan_type'] == 2) { // 实物
            $returnArr['user_adress'] = (new UserAdressService())->getOneAdress($nowUser['uid'], $addressId);
            $returnArr['express_fee'] = 0;
            /*运费计算*/
            if ($returnArr['user_adress']) {
                $expressFee = (new GroupService())->getExpressFee($nowGroup['group_id'], $nowGroup['price'], $returnArr['user_adress']);
                $returnArr['express_fee'] = $expressFee['freight'];
                $returnArr['express_template'] = $expressFee;
            }

            // 用户自提地址
            $pickLists = (new PickAddressService())->getPickAddressByMerId($nowGroup['mer_id'], false, 0, false, [], 1);
            $condition_group_store['group_id'] = $nowGroup['group_id'];
            $storeList = (new StoreGroupService())->getSome($condition_group_store);
            $store_arr = array_column($storeList, 'store_id');
            foreach ($pickLists as $key => $vv) {
                if (strpos($vv['pick_addr_id'], 's') !== false) {
                    $tmp_store_id = preg_match('/\d+/', $vv['pick_addr_id'], $r);
                    if (!in_array($r[0], $store_arr)) {

                    } else {
                        $pickList[] = $vv;
                    }
                } else {
                    $pickList[] = $vv;
                }
            }
            if (!empty($pickAddressId)) {
                foreach ($pickList as $v) {
                    if ($v['pick_addr_id'] == $pickAddressId) {
                        $pickAddress = $v;
                        break;
                    }
                }
            } else {
                $pickAddress = $pickList[0];
            }
            $returnArr['pick_address'] = $pickAddress;

            if ($nowGroup['open_express']) { // 快递配送
                $returnArr['delivery_list'][] = [
                    'name' => L_('快递配送'),
                    'value' => 0,
                ];
            }
            if ($nowGroup['pick_in_store']) { // 到店自提
                $returnArr['delivery_list'][] = [
                    'name' => L_('到店自提'),
                    'value' => 1,
                ];
            }
        } else {// 非实物没有运费
            $returnArr['express_fee'] = '0';
        }

        //每ID每天限购
        if ($nowGroup['once_max_day']) {
            $nowUser_today_count = (new GroupOrderService())->getOnceMaxDay($nowGroup['group_id'], $nowUser['uid'], $specificationsId);
            $today_can_buy = $nowGroup['once_max_day'] - $nowUser_today_count;

            if ($today_can_buy <= 0) {
                throw new \think\Exception(L_('该商品限制单人每天只能购买X1份，您当天购买的数量已达上限，不能再购买!', $nowGroup['once_max_day']), 1003);
            }

            if (!$nowGroup['once_max'] && $today_can_buy || $nowGroup['once_max'] > $today_can_buy) {
                $nowGroup['once_max'] = $today_can_buy;
            }
        }


        $returnArr['group_id'] = $nowGroup['group_id'];
        $returnArr['mer_id'] = $nowGroup['mer_id'];
        $returnArr['store_id'] = $storeId;
        $returnArr['group_cate'] = $nowGroup['group_cate']; // 商品类型  normal=团购商品  booking_appoint=场次预约 cashing=代金券 course_appoint=课程预约
        $returnArr['tuan_type'] = $nowGroup['tuan_type']; // 团购类型，0为团购券，1为代金券，2为实物
        $returnArr['s_name'] = $nowGroup['s_name']; // 商品名
        $returnArr['price'] = $nowGroup['price']; // 商品单价
        $returnArr['cancel_type'] = $nowGroup['cancel_type']; // 0=不可取消 1=到期前几小时取消 2=随时可取消
        $returnArr['phone'] = substr($nowUser['phone'], 0, 3) . '****' . substr($nowUser['phone'], 7);
        $returnArr['subtotal_price'] = get_format_number($nowGroup['price'] * $num); // 小计
        $returnArr['total_price'] = get_format_number($afterDiscountFinalprice * $num); // 总计
        $returnArr['store_phone'] = []; // 店铺手机号
        $returnArr['store_name'] = ''; // 店铺名
        $returnArr['face_value'] = get_format_number($nowGroup['face_value']); // 代金券面值
        $returnArr['num'] = $num;
        $returnArr['total_price'] += $returnArr['express_fee'];
        $returnArr['level_off'] && $returnArr['level_off']['money'] = get_format_number($returnArr['subtotal_price'] - $returnArr['total_price']);
        $returnArr['level_off'] = $returnArr['level_off'] ? $returnArr['level_off'] : (object)[];


        //店铺信息
        if ($storeId) {
            $store = (new MerchantStoreService())->getStoreByStoreId($storeId);
            if ($store) {
                $returnArr['store_phone'] = explode(',', $store['phone']); // 店铺手机号
                $returnArr['store_name'] = $store['name']; // 店铺名
            }
        }

        $GroupStoreList = (new StoreGroupService())->getSome(['group_id' => $nowGroup['group_id']]);
        if (!empty($GroupStoreList) && (cfg('group_to_store') == 1 || $nowGroup['tuan_type'] == 2)) {
            // 当此团购为实物且只指定一个店铺时，将店铺id直接带入保存到订单里  开启虚拟券单店铺绑定
            if (count($GroupStoreList) == 1 || $nowGroup['tuan_type'] == 2) {
                $returnArr['store_id'] = $GroupStoreList['0']['store_id'];
            }
        }

        // 临时订单id
        $tempOrderId = isset($param['temp_order_id']) ? intval($param['temp_order_id']) : 0;
        // 保存临时支付订单
        $tempOrderData = [
            'uid' => $nowUser['uid'],
            'mer_id' => $returnArr['mer_id'],
            'store_id' => $returnArr['store_id'],
            'temp_id' => $tempOrderId,
            'total_money' => $returnArr['total_price'],
            'group_id' => $nowGroupId??0
        ];
        $tempOrderDataId = (new GroupOrderTempService())->add($tempOrderData);
        $returnArr['temp_order_id'] = intval($tempOrderDataId);

//        $tmp_order = (new TempOrderData())->getOneData($tempOrderDataId);
        $tmpOrder = [];
        $tmpOrder['can_coupon_money'] = $returnArr['total_price'];
        $tmpOrder['total_money'] = $returnArr['total_price'];
        $tmpOrder['mer_id'] = $nowGroup['mer_id'];
        $tmpOrder['store_id'] = $storeId;
        $tmpOrder['business'] = 'group';
        $tmpOrder['temp_order_id'] =  $returnArr['temp_order_id'];
        // 使用平台
        $agent = request()->agent;
        switch ($agent) {
            case 'h5' :
            case 'alipay' :
                $tmpOrder['platform'] = 'wap';
                break;
            case 'iosapp' :
            case 'androidapp' :
                $tmpOrder['platform'] = 'app';
                break;
            case 'wechat_h5' :
                $tmpOrder['platform'] = 'weixin';
                break;
            case 'wechat_mini' :
                $tmpOrder['platform'] = 'wxapp';
                break;
        }

        //商家优惠券
        $tmpCoupon = [];
        $merchantCouponService = new \app\common\model\service\coupon\MerchantCouponService();

        if (!empty($merchantCouponId) && isset($useMerCoupon) && $useMerCoupon) {
            $tmpCoupon = $merchantCouponService->getCouponByHadpullId($merchantCouponId);
            $tmpCoupon['is_show_coupon'] = 1;
        } else {
            $tmpOrder['point_goods'] = [$tempOrderData['group_id']=>$tempOrderData['total_money']];
            $cardCouponList = $merchantCouponService->getAvailableCoupon($nowUser['uid'], $tmpOrder['mer_id'], $tmpOrder);
            // 处理不能使用优惠券不能使用原因
            $coupon_list_ = invoke_cms_model('System_coupon/getCanUseCoupon', ['now_order' => $tmpOrder, 'coupon_list' => $cardCouponList, 'business_type' => 'group', 'platform' => $tmpOrder['platform']??'', 'coupon_type' => 'mer', 'order_detail_goods' => [$tempOrderData['group_id']=>$tempOrderData['total_money']]]);
           
            if($coupon_list_){
                $cardCouponList = $coupon_list_['retval']['can_use_coupon_list'];
                if ($useMerCoupon && $cardCouponList) {
                    // 初次默认使用优惠券
                    $tmpCoupon = $cardCouponList[0];
                    $tmpCoupon['is_show_coupon'] = 1;
                }elseif(!$useSysCoupon&&$cardCouponList){
                    $tmpCoupon = $cardCouponList ? $cardCouponList[0] : [];
                    $tmpCoupon['is_show_coupon'] = 0;
                }
            }
        }
        if (!empty($tmpCoupon)) {
            $merCoupon['had_id'] = $tmpCoupon['had_id']??$tmpCoupon['id'];
            $merCoupon['is_show_coupon'] = $tmpCoupon['is_show_coupon'];
            $merCoupon['order_money'] = get_format_number($tmpCoupon['order_money']);//优惠条件
            $merCoupon['discount'] = get_format_number($tmpCoupon['discount']);//优惠金额
            $merCoupon['discount_desc'] = $merchantCouponService->formatDiscount([$tmpCoupon], true)[0]['discount_des'];//描述

            if($merCoupon['is_show_coupon']){
                // 优惠后金额
                $tmpOrder['can_coupon_money'] -= empty($merCoupon['discount']) ? 0 : $merCoupon['discount'];
            }
            
        } else {
            $merCoupon = [];
        }

        //平台优惠券
        $tmpCoupon = array();
        $systemCouponService = new \app\common\model\service\coupon\SystemCouponService();
        $tmpOrder['category'] = [$nowGroup['cat_fid']];
        if (!empty($systemCouponId) && isset($useSysCoupon) && $useSysCoupon) {//选择了优惠券
            $tmpCoupon = $systemCouponService->getCouponByHadpullId($systemCouponId);

            // 获得优惠金额
            if ($tmpCoupon) {
                $discountMoney = $systemCouponService->computeDiscount($tmpCoupon['coupon_id'], $tmpOrder['can_coupon_money']);
                $tmpCoupon['discount_money'] = $discountMoney['order_money_discount'];
                $tmpCoupon['is_show_coupon'] = 1;
            }
        } else {
            $can_coupon_money = $tmpOrder['can_coupon_money'];
            $tmpOrder['is_discount'] = 1;
            $tmpOrder['can_coupon_money'] = $returnArr['total_price']; //按照总商品价格算
            $systemCouponList = $systemCouponService->getAvailableCoupon($nowUser['uid'], $tmpOrder);
            if ($useSysCoupon && $systemCouponList) {
                // 初次默认使用优惠券
                $tmpCoupon = $systemCouponList ? $systemCouponList[0] : [];
                $tmpCoupon['is_show_coupon'] = 1;
            }elseif(!$useSysCoupon&&$systemCouponList){
                $tmpCoupon = $systemCouponList ? $systemCouponList[0] : [];
                $tmpCoupon['is_show_coupon'] = 0;
            }
            $tmpOrder['can_coupon_money'] = $can_coupon_money;
        }

        if ($tmpCoupon) {
            $systemCoupon['had_id'] = $tmpCoupon['had_id']??$tmpCoupon['id'];
            $systemCoupon['is_show_coupon'] = $tmpCoupon['is_show_coupon'];
            $systemCoupon['order_money'] = get_format_number($tmpCoupon['order_money']);//优惠条件
            $systemCoupon['discount'] = get_format_number($tmpCoupon['discount_money']);//优惠金额
            $systemCoupon['is_discount'] = $tmpCoupon['is_discount'];//是否折扣
            $systemCoupon['discount_value'] = $tmpCoupon['discount_value'];//折扣值
            $systemCoupon['discount_type'] = $tmpCoupon['discount_type'];//减免类型（目前仅支持外卖业务）：0不限，1减免运费，2减免餐费
            $systemCoupon['discount_desc'] = $systemCouponService->formatDiscount([$tmpCoupon], true)[0]['discount_des'];//描述

            // 优惠后金额
            if($systemCoupon['is_show_coupon']){
                $tmpOrder['can_coupon_money'] -= $systemCoupon['discount'];
            }
        } else {
            $systemCoupon = [];
        }

        $returnArr['total_price'] = max(0,get_format_number($tmpOrder['can_coupon_money'])); // 使用优惠券后的价格
        $returnArr['system_coupon'] = $systemCoupon ? $systemCoupon : (object)[];
        $returnArr['mer_coupon'] = $merCoupon ? $merCoupon : (object)[];

        $returnArr['formData'] = [];
        if($nowGroup['cat_fid']){
            $category = (new GroupCategory())->where(['cat_id'=>$nowGroup['cat_fid']])->value('write_field');
            if($category){
                $write_field = unserialize($category);
                foreach ($write_field as $val) {
                    $sort[] = $val['sort'];
                }
                array_multisort($sort, SORT_DESC, $write_field);
                $returnArr['formData'] = $write_field;
            }
        }

        return $returnArr;
    }

    /**
     * 保存订单
     * @param $param array 数据
     * @return array
     */
    public function saveOrder($param)
    {
        //团购商品id
        $nowGroupId = $param['group_id'] ?? 0;
        $specificationsId = $param['specifications_id'] ?? 0;// 规格id
        $share_id = $param['share_id'] ?? 0;//分享ID
        $gid = $param['gid'] ?? 0;// 拼团id
        $num = $param['num'] ?? 0;// 购买商品数量
        $storeId = $param['store_id'] ?? 0;// 从哪个店铺主页点击过来的店铺id
        $addressId = $param['address_id'] ?? 0;// 用户地址id
        $pickAddressId = $param['pick_address_id'] ?? 0;// 自提地址id
        $ruleId = $param['rule_id'] ?? 0;// 场次id（场次预约商品）
        $bookDate = $param['book_date'] ?? 0;// 场次预约预定日期
        $bookTime = $param['book_time'] ?? 0;// 场次预约预定时间（任选几小时的开始时间）
        $combineId = $param['combine_id'] ?? 0;// 套餐id
        $type = $param['type'] ?? 0;// 购买类型 1-原价购买
        $isPickInStore = $param['is_pick_in_store'] ?? 0;// (实物商品)是否到点自取，0 为配送 1 到店自取
        // 商家优惠券id
        $merchantCouponId = isset($param['merchant_coupon_id']) ? $param['merchant_coupon_id'] : 0;
        // 平台优惠券id
        $systemCouponId = isset($param['system_coupon_id']) ? $param['system_coupon_id'] : 0;
        // 是否使用平台优惠券
        $useSysCoupon = isset($param['use_sys_coupon']) ? $param['use_sys_coupon'] : 0;
        // 是否使用商家优惠券
        $useMerCoupon = isset($param['use_mer_coupon']) ? $param['use_mer_coupon'] : 0;
        
        // 酒店分类id
        $hotelCatId = isset($param['cat_id']) ? $param['cat_id'] : 0;
        // 预定开始时间
        $hotelDepTime = isset($param['dep_time']) ? $param['dep_time'] : 0;
        // 预定结束时间
        $hotelEndTime = isset($param['end_time']) ? $param['end_time'] : 0;
        //备注信息
        $note = $param['note']??'';
        $formData = $param['formData']??[];

        $returnArr = [];
        $nowTime = time();

        // 当前用户
        $nowUser = request()->user;
        if (empty($nowUser)) {
            throw new \think\Exception(L_('未登录'), 1002);
        }

        if (empty($nowUser['phone'])) {
            $url = customization('error_return_url') ? '_returnUrl_'.cfg('site_url') . '/packapp/plat/pages/my/newnumber' : '';
            throw new \think\Exception(L_('您需要绑定手机号码'.$url), 1003);
        }

        if (empty($num)) {
            throw new \think\Exception(L_('请选择购买数量'), 1003);
        }

        //获取下单商品的信息
        $where = [
            'group_id' => $nowGroupId,
            'status' => 1
        ];
        $nowGroup = (new GroupService())->getOne($where);
        if (empty($nowGroup)) {
            throw new \think\Exception(L_('当前X1不存在！', cfg('group_alias_name')), 1003);
        }

        if ($nowGroup['group_cate'] != 'booking_appoint') {// 场次预约没有开始结束时间
            if ($nowGroup['begin_time'] > $nowTime) {
                throw new \think\Exception(L_('此单X1还未开始！', cfg('group_alias_name')), 1003);
            }

            if ($nowGroup['end_time'] < $nowTime) {
                throw new \think\Exception(L_('此单X1已结束！', cfg('group_alias_name')), 1003);
            }
        } else {
            if (empty($ruleId)) {
                throw new \think\Exception(L_('请选择预定场次'), 1003);
            }
        }

        if ($nowGroup['type'] == 3 || $nowGroup['type'] == 4) {
            throw new \think\Exception(L_('此单X1已被抢完！', cfg('group_alias_name')), 1003);
        }

        foreach ($formData as $items) {
            if ($items['iswrite']==1 && !$items['value']) {
                throw new \think\Exception(L_($items['name']."为必填项"));
            }
        }
        // 分类自定义表单
        if($formData){
            $dataGroupOrder['write_field'] = serialize($formData);
        }

        $GroupStoreList = (new StoreGroupService())->getSome(['group_id' => $nowGroup['group_id']]);
        $nowGroup['specifications_id'] = 0;
        $dataGroupOrder['store_id'] = 0;
        if ($storeId) {
            $dataGroupOrder['store_id'] = $storeId;
        }
        if ($ruleId) {// 场次预约
            $num = 1;// 购买数量默认为1
            $ruleInfo = (new GroupBookingAppointService())->getRuleByRuleId($ruleId);
            if (empty($ruleInfo) || $ruleInfo['group_id'] != $nowGroupId) {
                throw new \think\Exception(L_('当前场次不存在或已删除'), 1003);
            }

            if (empty($bookDate)) {
                throw new \think\Exception(L_('请选择场次日期'), 1003);
            }

            if ($ruleInfo['use_hours'] && empty($bookDate)) {
                throw new \think\Exception(L_('请选择场次开始时间'), 1003);
            }
            $nowGroup['price'] = $ruleInfo['default_price'];// 修改商品单价为场次单价
            $nowGroup['count_num'] = $ruleInfo['count'];
            $nowGroup['sale_count'] = $ruleInfo['sale_count'];

            // 获得场次价格信息
            $priceInfo = (new GroupBookingAppointService())->getRulePrice($ruleId, $bookDate);
            if ($priceInfo && $priceInfo['is_sale'] == 0) {// 停售
                throw new \think\Exception(L_('该场次X1已停售，请重新选择预定日期', $bookDate), 1003);
            }

            if ($priceInfo) {// 优先使用价格日历的值
                $nowGroup['price'] = $priceInfo['price'];// 修改商品单价为场次单价
            }

            // 获得场次的套餐列表
            $combineList = (new GroupBookingAppointService())->getCombineByRuleId($ruleId);
            $all = [
                'rule_id' => $ruleId,
                'combine_id' => 0,
                'name' => $ruleInfo['use_hours'] ? L_('任选X1小时', $ruleInfo['use_hours']) : '全部时间',
                'price' => '0',
                'intro' => '',
            ];
            $combineList = array_merge([$all], $combineList);
            foreach ($combineList as $_combine) {
                $_combine['price'] = get_format_number($nowGroup['price'] + $_combine['price']);
            }

            if ($combineId) {// 套餐
                $combineInfo = (new GroupBookingAppointService())->getRuleCombineDetail($ruleId, $combineId);
                if (empty($combineInfo)) {
                    throw new \think\Exception(L_('当前套餐不存在或已删除'), 1003);
                }
                $nowGroup['price'] = $nowGroup['price'] + $combineInfo['price']; // 加上套餐价格
                $nowGroup['once_max_day'] = $combineInfo['once_max_day'];
                $nowGroup['once_max'] = $combineInfo['once_max'];
                $nowGroup['once_min'] = $combineInfo['once_min'];
                $nowGroup['count_num'] = $combineInfo['stock_num'];
                $nowGroup['sale_count'] = $combineInfo['sale_count'];
            }

            // 当天0点的时间
            $todayTime = strtotime(date('Y-m-d'));
            $ruleEndTime = strtotime($bookDate) + $ruleInfo['end_time'];
            $bookEndTime = strtotime($bookDate . ' ' . $bookTime) + $ruleInfo['use_hours'] * 3600;

            if (strtotime($bookDate . ' ' . $bookTime) > $ruleEndTime) {// 预约开始时间大于场次结束时间
                throw new \think\Exception(L_('预约开始时间不能大于场次结束时间'), 1003);
            }
            if (empty($ruleInfo['use_hours'])) {
                $bookEndTime = $ruleEndTime;
            } elseif ($bookEndTime > $ruleEndTime) {// 时间不够
                $bookEndTime = $ruleEndTime;
            }

        } elseif ($specificationsId > 0) { // 规格信息
            // 查询规格参数
            $whereSpecifications = [
                'group_id' => $nowGroupId,
                'specifications_id' => $specificationsId,
                'status' => 1
            ];
            $nowGroupSpecificationsInfo = (new GroupSpecificationsService())->getOne($whereSpecifications);
            if (empty($nowGroupSpecificationsInfo)) {
                throw new \think\Exception(L_('所选规格不存在'), 1003);
            }
            if($nowGroupSpecificationsInfo['count_num']>0 && ($nowGroupSpecificationsInfo['count_num']-$nowGroup['sale_count'])==0){
                throw new \think\Exception(L_('该商品库存为零，不能再购买!', '库存:'.$nowGroupSpecificationsInfo['count_num'].",销量：".$nowGroup['sale_count']), 1003);
            }
            $nowGroup['price'] = $nowGroupSpecificationsInfo['price'];
            $nowGroup['old_price'] = $nowGroupSpecificationsInfo['old_price'];
            $nowGroup['once_max_day'] = $nowGroupSpecificationsInfo['once_max_day'];
            $nowGroup['once_max'] = $nowGroupSpecificationsInfo['once_max'];
            $nowGroup['once_min'] = $nowGroupSpecificationsInfo['once_min'];
            $nowGroup['specifications_id'] = $specificationsId;
            $nowGroup['specifications_name'] = $nowGroupSpecificationsInfo['specifications_name'];

        }elseif($hotelCatId){// 绑定酒店

            $tradeHotel = (new TradeHotelCategoryService())->getCatPrice($nowGroup['mer_id'], $hotelCatId, $hotelDepTime, $hotelEndTime);
            if($tradeHotel&&$tradeHotel['stock']<$num){
                throw new \think\Exception(L_('库存不足，剩余'.$tradeHotel['stock']), 1003);
            }
            $nowGroup['price'] = $tradeHotel['discount_price'];
            $nowGroup['old_price'] = $tradeHotel['price'];

            $trade_info_arr = array(
                'type'=>'hotel',
                'dep_time'=>$hotelDepTime,
                'end_time'=>$hotelEndTime,
                'cat_id'=>$hotelCatId,
                'num'=>$num,
                'note'=>$note?:''
            );

            // 计算是否使用折扣价
            $dataGroupOrder['price'] = 0;
            foreach($tradeHotel['stock_list'] as $value){
                if($num>=$tradeHotel['discount_room']&&$tradeHotel['discount_room']>0){
                    $trade_info_arr['price_list'][$value['day']] = $value['discount_price'];
                }else{
                    $trade_info_arr['price_list'][$value['day']] = $value['price'];
                }
                $dataGroupOrder['price'] += $trade_info_arr['price_list'][$value['day']];
            }
            
            // 保存酒店信息
            $dataGroupOrder['trade_info'] = serialize($trade_info_arr);
            $dataGroupOrder['delivery_comment'] = $note??'';
            if(empty($dataGroupOrder['store_id'])){
                $dataGroupOrder['store_id'] = $GroupStoreList[0]['store_id'];
            }                
        } else {// 有规格的商品必须选择规格
            if($nowGroup['pin_num'] == 0){
                $whereSpecifications = [
                    'group_id' => $nowGroupId,
                    'status' => 1
                ];
                $nowGroupSpecificationsInfo = (new GroupSpecificationsService())->getOne($whereSpecifications);
                if ($nowGroupSpecificationsInfo && $nowGroupSpecificationsInfo > 0) {
                    throw new \think\Exception(L_('请选择规格'), 1003);
                }
            }
            
        }

        //店铺分销员分销商品
        $dataGroupOrder['is_marketing_goods'] = 0;
        $setpriceData = [];
        if ($share_id > 0) {
            $shareData = (new StoreMarketingShareLog())->where([['id', '=', $share_id]])->find();
            if (empty($shareData)) {
                throw new \think\Exception("分享码有误",1003);
            }
            if ($shareData['create_time'] > time() - 86400) {//分享链接24小时之内有效
                $setpriceData = (new StoreMarketingPersonSetprice())->where([
                    ['share_id', '=', $shareData['id']],
                    ['person_id', '=', $shareData['person_id']],
                    ['goods_type', '=', 1],
                    ['goods_id', '=', $nowGroup['group_id']],
                    ['specs_id', '=', $specificationsId]
                ])->find();
                if (empty($setpriceData)) {
                    $setpriceData = [
                        'share_id' => $shareData['id'],
                        'person_id' => $shareData['person_id'],
                        'goods_id' => $nowGroup['group_id'],
                        'goods_type' => 1,
                        'specs_id' => $specificationsId,
                        'price' => $nowGroup['price'],
                        'create_time' => time()
                    ];
                    $setpriceData['id'] = (new StoreMarketingPersonSetprice())->insertGetId($setpriceData);
                }
                $nowGroup['price'] = $setpriceData['price'];
                $dataGroupOrder['is_marketing_goods'] = 1;
            }
        }

        //如果下过“下单成功后减库存”的单，则让去个人中心付款
        if ($nowGroup['stock_reduce_method'] && $this->getOne(['group_id' => $nowGroup['group_id'], 'uid' => $nowUser['uid'], 'specifications_id' => intval($nowGroup['specifications_id']), 'paid' => '0', 'status' => '0'])) {
            // throw new \think\Exception(L_("该商品您有未支付的订单，请在个人中心订单列表支付！"),1003);
        }

        // 验证购买数量是否符合
        $this->checkBuyNum($nowUser['uid'], $nowGroup['group_id'], $num, $nowGroup['specifications_id'],0,$ruleId,$combineId);

        // 处理不同情况下的价格
        if ($type == 1) { // 原价购买
            $nowGroup['price'] = get_format_number($nowGroup['old_price']);
            $nowGroup['extra_pay_price'] = get_format_number($nowGroup['extra_pay_old_price']);
        } elseif ($type == 3) {// 发起拼团
            $nowGroup['price'] = get_format_number(intval(round($nowGroup['price'] * $nowGroup['start_discount'])) / 100); //团长按团长折扣计算
            $startMaxNum = intval($nowGroup['start_max_num']) > 1 ? intval($nowGroup['start_max_num']) : 1; // 团长每次最多购买次数 默认取1 最小也取1
            if (intval($num) && intval($num) > $startMaxNum) {
                throw new \think\Exception(L_('此单团长每次最多购买X1份！' . $startMaxNum), 1003);
            }
            $nowGroup['start_max_num'] = $startMaxNum;
        } else {
            $nowGroup['price'] = get_format_number($nowGroup['price']);
        }

        //用户等级 优惠
        $userLevel = (new UserLevelService)->getSome([], true, ['id' => 'ASC']);
        $userLevel = array_column($userLevel, 'lname', 'level');
        $levelOff = [];
        $levelDiscount = 0;
        if ($nowGroup['trade_type'] != 'hotel' && !empty($nowUser['level'])) {
            // 会员优惠
            $leveloff = !empty($nowGroup['leveloff']) ? unserialize($nowGroup['leveloff']) : '';


            /****type:0无优惠 1百分比 2立减*******/
            if (!empty($leveloff) && isset($leveloff[$nowUser['level']]) && isset($userLevel[$nowUser['level']])) {
                $levelOff = $leveloff[$nowUser['level']];
                if ($levelOff['type'] == 1) {// 百分比
                    $levelDiscount = get_format_number($nowGroup['price'] * (1 - $levelOff['vv'] / 100));
                } elseif ($levelOff['type'] == 2) {// 立减
                    $levelDiscount = $levelOff['vv'];
                }
            }
        }

        $afterDiscountFinalprice = 0;// 折扣后商品单价
        $nowGroupDiscount = 0;// 折扣金额
        $tmpFinalprice = 0;// 临时的折扣后商品单价
        $finalprice = 0;
        // 折扣优惠方式  0 折上折 1 折扣最优
        if ($nowGroup['discount'] > 0) {
            $tmpFinalprice = round($nowGroup['price'] * $nowGroup['discount'] / 10, 2);
            $nowGroupDiscount = $nowGroup['price'] - $tmpFinalprice;
        }

        if ($nowGroup['vip_discount_type'] == 1) {// 折扣最优
            $finalprice = $nowGroup['price'] * $nowGroup['discount'] / 10;
            if ($nowGroupDiscount > $levelDiscount) {
                $afterDiscountFinalprice = $tmpFinalprice;
                $finalprice = 0;
                $levelDiscount = 0;

            } else {
                $afterDiscountFinalprice = $nowGroup['price'] - $levelDiscount;
                $nowGroupDiscount = 0;
            }
        } else if ($nowGroup['vip_discount_type'] == 2) {// 折上折
            // 重新计算会员等级优惠
            if ($levelOff['type'] == 1) {
                $levelDiscount = round($tmpFinalprice * (1 - $levelOff['vv'] / 100), 2);
            } elseif ($levelOff['type'] == 2) {
                $levelDiscount = $levelOff['vv'];
            }
            $afterDiscountFinalprice = $tmpFinalprice - $levelDiscount;
        } else {
            $afterDiscountFinalprice = $nowGroup['price'] - $levelDiscount;
        }

        $afterDiscountFinalprice >= 0 && $finalprice = $afterDiscountFinalprice;
        $levelDiscount > 0 && $nowGroup['vip_discount_money'] = $levelDiscount;
        $finalprice >= 0 && $nowGroup['price'] = round($finalprice, 2);// 折扣后单价
        $levelDiscount > 0 && $nowGroup['level_price'] = $afterDiscountFinalprice;

        // 处理记录下购买
        (new GroupRecordService())->groupRecord($nowGroupId, $nowUser['uid'], $nowGroup['mer_id'], 2);

        $nowGroup['distributor_uid'] = 0;
        if (cfg('open_single_spread') == 1) {
            // 处理分销人
            if (isset($param['openid']) && $param['openid']) {
                $distributorUser = (new UserService())->getUser($param['openid'], 'openid');
                // 验证是否有分销权限
                $where = [
                    'uid' => $distributorUser['uid'],
                    'status' => 1
                ];
                $distributor = (new DistributorService())->getOne($where);

                if ($distributor) {
                    $nowGroup['distributor_uid'] = intval($distributorUser['uid']);
                }
            } elseif (isset($param['share_uid']) && $param['share_uid']) {
                // 验证是否有分销权限
                $where = [
                    'uid' => $param['share_uid'],
                    'status' => 1
                ];
                $distributor = (new DistributorService())->getOne($where);
                if ($distributor) {
                    $nowGroup['distributor_uid'] = intval($param['share_uid']);
                }
            }
        }

        if (isset($param['share_openid']) && trim($param['share_openid']) && 1 == cfg('open_single_weixin_spread_user')) {
            // 处理单个核销问题
            $nowGroup['share_openid'] = trim($param['share_openid']);
        }

        $dataGroupOrder['group_id'] = $nowGroup['group_id'];
        $dataGroupOrder['mer_id'] = $nowGroup['mer_id'];
        $dataGroupOrder['num'] = intval($num);
        $dataGroupOrder['pass_num'] = intval($nowGroup['pass_num']);
        $dataGroupOrder['uid'] = $nowUser['uid'];
        $dataGroupOrder['stock_reduce_method'] = $nowGroup['stock_reduce_method'];
        if($nowGroup['tuan_type']==3){
            $dataGroupOrder['exchange_num'] = $nowGroup['exchange_num'];
        }
        // $dataGroupOrder['is_liveshow'] = $nowGroup['is_liveshow'] ?? 0;
        // $dataGroupOrder['liveshow_id'] = $nowGroup['liveshow_id'] ?? 0;
        // $dataGroupOrder['is_livevideo'] = $nowGroup['is_livevideo'] ?? 0;
        // $dataGroupOrder['livevideo_id'] = $nowGroup['livevideo_id'] ?? 0;
        // $dataGroupOrder['liveshow_percent'] = $nowGroup['liveshow_percent'] ?? 0;
        $nowGroup['distributor_uid'] && $dataGroupOrder['distributor_uid'] = intval($nowGroup['distributor_uid']);// 分销人uid

        if (!cfg('house_buy_rebate_open')) {

            if (cookie('visit_village_id') != '' && (new HouseVillageGroupService())->getOne(['village_id' => cookie('visit_village_id'), 'group_id' => $nowGroup['group_id']])) {
                if ($_SESSION['now_village_bind']['village_id'] && $_SESSION['now_village_bind']['village_id'] > 0) {
                    $dataGroupOrder['village_id'] = $_SESSION['now_village_bind']['village_id'];
                } elseif ($_SESSION['house']['village_id'] && $_SESSION['house']['village_id'] > 0) {
                    $dataGroupOrder['village_id'] = $_SESSION['house']['village_id'];
                } else {
                    $storeArr = array_column((new StoreGroupService())->getSome(['group_id' => $nowGroup['group_id']]), 'store_id');
                    if ($storeArr) {
                        $countStore = (new PlatRecommendService())->getCount([['store_id', 'in', implode(',', $storeArr)]]);
                        if ($countStore > 0) {
                            $dataGroupOrder['village_id'] = cookie('visit_village_id');
                        }
                    }
                }
                if (!$dataGroupOrder['village_id']) {
                    $dataGroupOrder['village_id'] = cookie('visit_village_id');
                }
            } elseif (cookie('visit_village_id') != '' && cookie('visit_village_id') > 0) {
                $storeArr = array_column((new StoreGroupService())->getSome(['group_id' => $nowGroup['group_id']]), 'store_id');
                if ($storeArr) {
                    $countStore = (new PlatRecommendService())->getCount([['store_id', 'in', implode(',', $storeArr)]]);
                    if ($countStore > 0) {
                        $dataGroupOrder['village_id'] = cookie('visit_village_id');
                    }
                }
            }
        } else {
            //开关开启情况下-获取用户最新选择的小区
            $selectLog = (new HouseVillageSelectLogService())->getOne(['uid' => $nowUser['uid']], 'village_id', 'id DESC');
            $dataGroupOrder['village_id'] = isset($selectLog['village_id']) ? $selectLog['village_id'] : 0;
        }

        if (empty($dataGroupOrder['num'])) {
            throw new \think\Exception(L_('请输入正确的购买数量！'), 1003);
        } else if ($dataGroupOrder['num'] < $nowGroup['once_min']) {
            throw new \think\Exception(L_("您最少需要购买X1单！", array("X1" => $nowGroup['once_min'])), 1003);
        } else if ($nowGroup['once_max'] != 0 && $dataGroupOrder['num'] > $nowGroup['once_max']) {
            throw new \think\Exception(L_("您最多只能购买X1单！", array("X1" => $nowGroup['once_max'])), 1003);
        }

        if ($nowGroup['specifications_id']) {
            $dataGroupOrder['order_name'] = $nowGroup['s_name'] . L_('【') . $nowGroup['specifications_name'] . L_('】') . 'x' . $dataGroupOrder['num'].'份';
            $dataGroupOrder['specifications_id'] = $nowGroup['specifications_id'];
        } else {
            $dataGroupOrder['order_name'] = $nowGroup['s_name'] . 'x' . $dataGroupOrder['num'].($hotelCatId?'间':'份');
        }
        //1 为单独购买 3 为发起团购 2 为参团
        if ($type == 1) {
            $dataGroupOrder['price'] = isset($nowGroup['level_price']) ? $nowGroup['level_price'] : $nowGroup['old_price'];
            $dataGroupOrder['single_buy'] = 1;

        } elseif ($type == 3) {
            $dataGroupOrder['price'] = $nowGroup['price']; //团长按团长折扣计算
            if ($nowGroup['pin_num'] > 0) {
                $startRes = (new GroupStartService())->startGroup($nowUser['uid'], $nowGroup);
                $dataGroupOrder['is_head'] = $startRes['msg'];//是否是团长
                $dataGroupOrder['new_group'] = 1;
                if ($startRes['error_code']) {
                    throw new \think\Exception($startRes['msg'], 1003);
                }
            }

        } elseif ($type == 2) {
            // 加入拼团  
            if ($nowGroup['pin_num'] <= 0) {
                throw new \think\Exception(L_('非拼团商品，不能加入'), 1003);
            }

            if ($gid) {
                $nowStart = (new GroupStartService())->getOne(['id' => $gid]);
                if ($nowStart['uid'] == $nowUser['uid']) {
                    throw new \think\Exception(L_('您已加入该团，不能重复加入'), 1003);
                }
                if ($nowStart && $nowStart['complete_num'] <= $nowStart['num']) {
                    throw new \think\Exception(L_('当前X1已成团，请参加其他拼团或开团！', cfg('group_alias_name')), 1003);
                }
            }
            if (!empty($gid)) {
                $dataGroupOrder['pin_fid'] = $gid;
            }
            $dataGroupOrder['price'] = $nowGroup['price'];
        } else {
            $dataGroupOrder['price'] = $nowGroup['price'];
        }

        if ($nowGroup['extra_pay_price'] > 0) {
            $dataGroupOrder['extra_price'] = $nowGroup['type'] == 1 ? $nowGroup['extra_pay_old_price'] * $dataGroupOrder['num'] : $nowGroup['extra_pay_price'] * $dataGroupOrder['num'];
        }

        isset($nowGroup['vip_discount_money']) && $nowGroup['vip_discount_money'] && $dataGroupOrder['vip_discount_money'] = $nowGroup['vip_discount_money'] * $dataGroupOrder['num'];
        $dataGroupOrder['total_money'] = get_format_number($dataGroupOrder['price'] * $dataGroupOrder['num']);
        $dataGroupOrder['tuan_type'] = $nowGroup['tuan_type'];
        $dataGroupOrder['add_time'] = time();
        $orderid = build_real_orderid($nowUser['uid']);//real_orderid
        $dataGroupOrder['real_orderid'] = $orderid;

        //实物
        if ($nowGroup['tuan_type'] == 2 && $nowGroup['trade_type'] != 'hotel') {
            if (!empty($isPickInStore)) {
                if ($pickAddressId) {
                    $pickLists = (new PickAddressService())->getPickAddressByMerId($nowGroup['mer_id'], false, 0, false, [], 1);
                    foreach ($pickLists as $v) {
                        if ($v['pick_addr_id'] == $pickAddressId) {
                            $pick_address = $v;
                            break;
                        }
                    }

                    $dataGroupOrder['is_pick_in_store'] = 1;
                    $dataGroupOrder['phone'] = $pick_address['phone'];
                    $dataGroupOrder['adress'] = $pick_address['area_info']['province'] . $pick_address['area_info']['city'] . $pick_address['area_info']['area'] . $pick_address['name'];
                    $dataGroupOrder['pick_lng'] = $pick_address['long'] ? $pick_address['long'] : 0;
                    $dataGroupOrder['pick_lat'] = $pick_address['lat'] ? $pick_address['lat'] : 0;
                } else {
                    throw new \think\Exception(L_('自取地址为空不能使用，请选择自取地址！'), 1003);
                }
            } else {
                $nowAdress = (new UserAdressService())->getOneAdress($nowUser['uid'], $addressId);
                if (empty($nowAdress)) {
                    throw new \think\Exception(L_('请先添加收货地址！'), 1003);
                }
                $dataGroupOrder['contact_name'] = $nowAdress['name'];
                $dataGroupOrder['phone'] = $nowAdress['phone'];
                $dataGroupOrder['zipcode'] = $nowAdress['zipcode'];
                $dataGroupOrder['adress'] = $nowAdress['show_address'];
                (isset($param['delivery_type']) && $param['delivery_type']) && $dataGroupOrder['delivery_type'] = $param['delivery_type'];
                (isset($param['delivery_comment']) && $param['delivery_comment']) && $dataGroupOrder['delivery_comment'] = $_POST['delivery_comment'];

                /*运费计算*/
                $expressFee = (new GroupService())->getExpressFee($nowGroup['group_id'], $nowGroup['price'], $nowAdress);
                $dataGroupOrder['express_fee'] = $expressFee['freight'];
                $dataGroupOrder['total_money'] += $dataGroupOrder['express_fee'];
            }
        } else {
            $dataGroupOrder['phone'] = $nowUser['phone'];
            if (isset($param['delivery_comment']) && $param['delivery_comment']) {
                $dataGroupOrder['delivery_comment'] = $param['delivery_comment'];
            }
        }

        if (!empty($GroupStoreList) && (cfg('group_to_store') == 1 || $nowGroup['tuan_type'] == 2)) {
            // 当此团购为实物且只指定一个店铺时，将店铺id直接带入保存到订单里  开启虚拟券单店铺绑定
            if (count($GroupStoreList) == 1 || $nowGroup['tuan_type'] == 2) {
                $dataGroupOrder['store_id'] = $GroupStoreList['0']['store_id'];
            }
        }
        if (isset($nowGroup['share_openid'])) {
            // 处理单个分享问题
            $dataGroupOrder['share_openid'] = $nowGroup['share_openid'];
        }

        // 处理优惠券
        $tmpOrder = [];
        $tmpOrder['can_coupon_money'] = $dataGroupOrder['total_money'];
        $tmpOrder['mer_id'] = $dataGroupOrder['mer_id'];
        $tmpOrder['business'] = 'group';
        // 使用平台
        $agent = request()->agent;
        switch ($agent) {
            case 'h5' :
            case 'alipay' :
                $tmpOrder['platform'] = 'wap';
                break;
            case 'iosapp' :
            case 'androidapp' :
                $tmpOrder['platform'] = 'app';
                break;
            case 'wechat_h5' :
                $tmpOrder['platform'] = 'weixin';
                break;
            case 'wechat_mini' :
                $tmpOrder['platform'] = 'wxapp';
                break;
        }

        //商家优惠券
        $tmpCoupon = [];
        $merchantCouponService = new \app\common\model\service\coupon\MerchantCouponService();

        if (!empty($merchantCouponId) && isset($useMerCoupon) && $useMerCoupon) {
            $tmpCoupon = $merchantCouponService->getCouponByHadpullId($merchantCouponId);
        }
        if (!empty($tmpCoupon)) {
            $dataGroupOrder['card_id'] = $tmpCoupon['id'];
            $dataGroupOrder['card_price'] = $tmpCoupon['discount'] > $tmpOrder['can_coupon_money'] ? $tmpOrder['can_coupon_money'] : $tmpCoupon['discount'];

            // 优惠后金额
            $tmpOrder['can_coupon_money'] -= empty($tmpCoupon['discount']) ? 0 : $tmpCoupon['discount'];
        }

        //平台优惠券
        $tmpCoupon = array();
        $systemCouponService = new \app\common\model\service\coupon\SystemCouponService();
        if (!empty($systemCouponId) && isset($useSysCoupon) && $useSysCoupon) {//选择了优惠券
            $tmpCoupon = $systemCouponService->getCouponByHadpullId($systemCouponId);
            // 获得优惠金额
            if ($tmpCoupon) {
                $dataGroupOrder['coupon_id'] = $tmpCoupon['id'];
                $discountMoney = $systemCouponService->computeDiscount($tmpCoupon['coupon_id'], $tmpOrder['can_coupon_money']);
                $dataGroupOrder['coupon_price'] = $discountMoney['order_money_discount'] > $tmpOrder['can_coupon_money'] ? $tmpOrder['can_coupon_money'] : $discountMoney['order_money_discount'];
            }
        }

        //用户自定义输入信息
        if (isset($param['userinput'])) {
            $dataGroupOrder['userinput'] = serialize($param['userinput']);
        }
        //若有第三方团购ID，则存入
        if (isset($nowGroup['custom_product_id']) && $nowGroup['custom_product_id']) {
            $dataGroupOrder['custom_product_id'] = $nowGroup['custom_product_id'];
        }

        // 插入数据库
        $orderId = $this->add($dataGroupOrder);

        if (!$orderId) {
            throw new \think\Exception(L_('订单产生失败！请重试'), 1003);
        }

        // 验证库存
        if (isset($nowGroup['specifications_id']) && $nowGroup['specifications_id']) {
            (new GroupService())->checkRedisStock($nowGroupId, $num, 2, $nowGroup, $nowGroupSpecificationsInfo);
        } elseif ($ruleId || $combineId) {// 场次预约
            if(!$combineId){
                $combineInfo = [
                    'stock_num' => $ruleInfo['count'],
                    'sale_count' => $ruleInfo['sale_count'],
                    'rule_id' => $ruleId,
                    'count_num' => $ruleInfo['count'],
                    'id' => 0
                ];
            }
            (new GroupService())->checkRedisStock($nowGroupId, $num, 3, $nowGroup, [], $combineInfo);
           
        } else {
            (new GroupService())->checkRedisStock($nowGroupId, $num, 1, $nowGroup);
        }

        //添加分销记录
        if ($dataGroupOrder['is_marketing_goods'] == 1) {
            (new StoreMarketingRecordService())->addRecord([
                'share_id' => $shareData['id'],
                'store_id' => $dataGroupOrder['store_id'],
                'order_id' => $orderId,
                'uid' => $dataGroupOrder['uid'],
                'goods_id' => $dataGroupOrder['group_id'],
                'goods_type' => 1,
                'goods_name' => $nowGroup['s_name'],
                'goods_num' => $dataGroupOrder['num'],
                'pay_money' => $dataGroupOrder['price'],
                'create_time' => time()
            ]);
        }

        //下单成功减库存
        if ($nowGroup['stock_reduce_method']) {
            // 如果存在规格 改写规格销量
            $this->updateStock($nowGroup['group_id'], $nowGroup['specifications_id'], $ruleId, $combineId, $num, 1);
        }

        // 保存场次预约信息
        if ($ruleId) {
            $ruleData = [
                'order_id' => $orderId,
                'rule_id' => $ruleId,
                'combine_id' => $combineId,
                'book_start_time' => strtotime($bookDate . ' ' . $bookTime),
                'book_end_time' => $bookEndTime,
                'use_hours' => $ruleInfo['use_hours'],
            ];
            (new GroupBookingAppointOrderService())->add($ruleData);
        }

        if ($nowUser['openid']) {// 发送消息
            $href = cfg('site_url') . '/wap.php?c=My&a=group_order&order_id=' . $orderId;// TODO
            (new TemplateNewsService())->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $nowUser['openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $orderid, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $dataGroupOrder['order_name'], 'remark' => L_('您的该次X1已生成，点击查看订单详情！', cfg('group_alias_name'))), $dataGroupOrder['mer_id']);
        }

        // 发送短信给用户
        $smsData = array('mer_id' => $nowGroup['mer_id'], 'store_id' => 0, 'type' => 'group');
        if (cfg('sms_group_place_order') == 1 || cfg('sms_group_place_order') == 3) {
            $smsData['uid'] = $nowUser['uid'];
            $smsData['mobile'] = $dataGroupOrder['phone'];
            $smsData['sendto'] = 'user';
            $smsData['content'] = L_('您在x1时，购买了x2，已成功生产订单，订单号：x3', array('x1' => date("Y-m-d H:i:s"), 'x2' => $nowGroup['s_name'], 'x3' => $orderid));
            (new SmsService())->sendSms($smsData);
        }

        // 发送短信给商家
        if (cfg('sms_group_place_order') == 2 || cfg('sms_group_place_order') == 3) {
            $merchant = (new MerchantService())->getMerchantByMerId($nowGroup['mer_id']);
            if($merchant['phone']){
                $smsData['uid'] = 0;
                $smsData['mobile'] = $merchant['phone'];
                $smsData['sendto'] = 'merchant';
                $smsData['content'] = L_("有份新的X1被购买，订单号：X2请您注意查看并处理!", array("X1" => $nowGroup['s_name'], "X2" => $orderid));
                (new SmsService())->sendSms($smsData);
            }
        }

        if ($type == 3) { // 发起团购
            $noticeStatus = 8;
        } else if ($type == 2) { // 参加拼团
            $noticeStatus = 9;
        } else {
            $noticeStatus = 0;
        }

        // 消息推送
        $this->groupAppNotice($orderId, $noticeStatus);


        //增加搜索关键词  店铺名称 和 商品名称
        $storeInfo = (new MerchantStoreService())->getStoreByStoreId($dataGroupOrder['store_id']);
        $dataGroupOrder['keywords'] = trim(($storeInfo ? ($storeInfo['name'] . ',') : '') . $nowGroup['name'], ',');

        $returnArr['type'] = 'group';
        $returnArr['order_id'] = $orderId;
        return $returnArr;
    }

    /**
     * @param int $uid
     * @param int $nowGroupId
     * @param int $num
     * @param int $specificationsId
     * @param int $orderId
     * @return array
     */
    public function checkBuyNum($uid, $nowGroupId, $num, $specificationsId = 0, $orderId = 0, $ruleId = 0, $combineId = 0)
    {
        if ($specificationsId && $specificationsId > 0) {
            $where = [
                ['group_id', '=', $nowGroupId],
                ['uid', '=', $uid],
                ['specifications_id', '=', $specificationsId],
                ['status', '<', 3],
                ['paid', '=', 1],
            ];
            if ($orderId > 0) {
                $where[] = ['order_id', '<>', $orderId];
            }
            $count = $this->getSum($where);
            $nowGroup = (new GroupSpecificationsService())->getOne(array('specifications_id' => $specificationsId));
        } else if ($ruleId) {// 套餐
            $where = [
                ['o.group_id', '=', $nowGroupId],
                ['o.uid', '=', $uid],
                ['o.status', '<', 3],
                ['o.paid', '=', 1],
                ['b.rule_id', '=', $ruleId],
                ['b.combine_id', '=', $combineId],
            ];
            if ($orderId > 0) {
                $where[] = ['order_id', '<>', $orderId];
            }
            $count = $this->getBookingAppointSum($where);

            // 当前团购商品
            $nowGroup = (new GroupService())->getOne(['group_id' => $nowGroupId]);
            if($combineId){
                $combineInfo = (new GroupBookingAppointService())->getRuleCombineDetail($ruleId, $combineId);
                $nowGroup['once_max'] = $combineInfo['once_max'];
                $nowGroup['once_min'] = $combineInfo['once_min'];
                $nowGroup['once_max_day'] = $combineInfo['once_max_day'];
                $nowGroup['count_num'] = $combineInfo['stock_num'];
                $nowGroup['sale_count'] = $combineInfo['sale_count'];
            }else{                
                $ruleInfo = (new GroupBookingAppointService())->getRuleByRuleId($ruleId);
                $nowGroup['count_num'] = $ruleInfo['count'];
                $nowGroup['sale_count'] = $ruleInfo['sale_count'];
            }
        } else {
            $where = [
                ['group_id', '=', $nowGroupId],
                ['uid', '=', $uid],
                ['status', '<', 3],
            ];
            if ($orderId > 0) {
                $where[] = ['order_id', '<>', $orderId];
            }
            $count = $this->groupOrderModel->where($where)->sum('num');
            $nowGroup = (new GroupService())->getOne(['group_id' => $nowGroupId]);
        }

        $max_num = $nowGroup['once_max'];//一次购买最多的数量

        $min_num = $nowGroup['once_min'];//一次最少数量

        // 当前团购商品
        $nowGroupGoods = (new GroupService())->getOne(['group_id' => $nowGroupId]);

        //商品有库存限制的时候
        if ($nowGroup['count_num'] > 0) {
            $k_num = $nowGroup['count_num'] - $nowGroup['sale_count']; //实际库存
            
            if ($k_num < $min_num && $nowGroupGoods['stock_reduce_method'] == 1) {//库存等于销售量的时候不能买,下单后减库存模式不验证
                throw new \think\Exception(L_("该商品已售空"), 1003);
            } else {
                if ($max_num > 0) {
                    $my_num = $max_num - $count;//我的剩余得到份数
                    $my_num = $my_num > $k_num ? $k_num : $my_num;
                    $my_num = $my_num > $max_num ? $max_num : $my_num;

                    if ($my_num < $num) {
                        if ($my_num > 0) {
                            throw new \think\Exception(L_("您最多能购买X1份", $my_num), 1003);
                        } else {
                            throw new \think\Exception(L_("该商品限制单人只能购买X1份，您购买的数量已达上限，不能再购买", $max_num), 1003);
                        }
                    } elseif ($num < $min_num) {
                        throw new \think\Exception(L_("您一次最少要购买X1份买", $min_num), 1003);
                    }
                } else {
                    if ($num > $k_num) {
                        throw new \think\Exception(L_("您最多能购买X1份", $k_num), 1003);
                    }
                }
            }
        } else {
            if ($max_num > 0) {
                $my_num = $max_num - $count;//我的剩余得到份数
                $my_num = $my_num > $max_num ? $max_num : $my_num;
                if ($my_num < $num) {
                    if ($my_num > 0) {
                        throw new \think\Exception(L_("您最多能购买X1份", $my_num), 1003);
                    } else {
                        throw new \think\Exception(L_("此商品每个用户只能购买X1份", $max_num), 1003);
                    }
                } elseif ($num < $min_num) {
                    throw new \think\Exception(L_("您一次最少要购买X1份", $min_num), 1003);
                }
            }
        }

        if ($num < $min_num) {
            throw new \think\Exception(L_("您一次最少要购买X1份", $min_num), 1003);
        }

        //每ID每天限购
        if ($nowGroup['once_max_day']) {
            $nowUser_today_count = $this->getOnceMaxDay($nowGroupId, $uid, $specificationsId);
            $today_can_buy = $nowGroup['once_max_day'] - $nowUser_today_count;
            if ($today_can_buy <= 0 || $today_can_buy < $num) {
                throw new \think\Exception(L_("该商品限制单人每天只能购买X1份，您当天购买的数量已达上限，不能再购买", $nowGroup['once_max_day']), 1003);
            }
        }

        return true;
    }

    /**
     * 获得支付订单信息（供支付调用）
     * @param int $orderId 订单id
     * @return array
     */
    public function getOrderPayInfo($orderId)
    {
        if (!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $order = $this->getOne(['order_id' => $orderId]);
        if (!$order) {
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        if($order['trade_info']){
            $trade_info_arr = unserialize($order['trade_info']);
            if($trade_info_arr['type'] == 'hotel'){
                $tradeHotel = (new TradeHotelCategoryService())->getCatPrice($order['mer_id'], $trade_info_arr['cat_id'], $trade_info_arr['dep_time'], $trade_info_arr['end_time']);
                if($tradeHotel&&$tradeHotel['stock']<$trade_info_arr['num']){
                    throw new \think\Exception(L_('库存不足，剩余'.$tradeHotel['stock']), 1003);
                }
            }
        }

        // 店铺信息
        $store = (new \app\merchant\model\service\MerchantStoreService())->getStoreByStoreId($order['store_id']);

        // 商品信息
        $goodsDetail = [];


        $param['goodsDetail'] = $goodsDetail;

        $returnArr['order_money'] = get_format_number($order['total_money']-$order['card_price']-$order['coupon_price']);
        $returnArr['paid'] = $order['paid'];
        $returnArr['order_no'] = $order['real_orderid'];
        $returnArr['store_id'] = $order['store_id'];
        $returnArr['city_id'] = $store['city_id'] ?? 0;
        $returnArr['mer_id'] = $store['mer_id'] ?? 0;
        $returnArr['is_cancel'] = ($order['status'] == 3 || $order['status'] == 4) ? 1 : 0;
        $returnArr['time_remaining'] = 1200 - (time() - $order['add_time']);//秒 20分钟自动取消
        $returnArr['uid'] = $order['uid'];
        $returnArr['title'] = $order['order_name'];
        $returnArr['group_id'] = $order['group_id'];
        $returnArr['business_order_sn'] = $order['real_orderid'];

        if ($returnArr['time_remaining'] <= 0) {
            //超时取消订单
            $data['status'] = 4;
            $where = [
                'order_id' => $orderId
            ];
            $this->updateThis($where, $data);

            // 回滚库存
            $this->rollBackStock($orderId, $order);
            
        }
        return $returnArr;
        
    }

    /**
     * 回滚库存
     * @param int $orderId 订单id
     * @return array 
     */
    public function rollBackStock($orderId, $order=[])
    {
        if(empty($order)){
            $order = $this->getOne(['order_id'=>$orderId]);
            if(!$order) {
                throw new \think\Exception(L_("订单不存在"), 1003);
            }

        }
        
        // 当前团购信息
        $nowGroup = (new GroupService())->getOne(['group_id'=>$order['group_id']]);
        
        // 当前团购场次预约信息
        $ruleInfo = [];
        if($nowGroup['group_cate'] == 'booking_appoint') {// 场次预约
            $ruleInfo = (new GroupBookingAppointOrderService())->getOne(['order_id'=>$order['order_id']]);
        }

        $ruleId = $ruleInfo['rule_id'] ?? 0;
        $combineId = $ruleInfo['combine_id'] ?? 0;

        // 取消订单，回滚redis库存
        $this->addRedisStock($order['group_id'], $order['specifications_id'], $ruleId, $combineId, $order['num']);
        
        // 回滚商品库存
        if ($order['stock_reduce_method']) {// 下单成功后减库存回滚库存
            $this->updateStock($order['group_id'], $order['specifications_id'], $ruleId, $combineId, $order['num'],  2);
        }
        
        return true;
    }
    
    
    /**
     * 操作商品库存
     * @param int $order 订单信息
     * @param $type 操作类型 1-减少库存 2 增加库存
     * @return array
     */
    public function updateStock($groupId, $specificationsId = 0, $rule = 0, $combineId = 0, $num=0, $type = 1){
        if($num == 0 || empty($groupId)){
            return false;
        }

        if ($specificationsId) {
            (new GroupSpecificationsService())->updateStock($specificationsId, $num,$type);

            $is_surplus = (new GroupSpecificationsService())->getSpecSurplusCount($groupId);
            if (!$is_surplus) {
                // 如果团购存在规格且规格全部售空 团购结束
                (new GroupService())->updateThis(['group_id'=>$groupId], ['type' => 3]);
            }

        } elseif($rule) {// 场次预约
            (new GroupBookingAppointService())->updateStock($rule, $combineId,$num,$type);
        } else {
            (new GroupService())->updateStock($groupId, $num,$type);
        }

        return true;
    }
    
    /**
     * 取消订单，回滚redis库存
     * @param int $order 订单信息
     * @param $type 操作类型 1-减少库存 2 增加库存
     * @return array
     */
    public function addRedisStock($groupId, $specificationsId = 0, $ruleId = 0, $combineId = 0, $num=0){
        if($num == 0 || empty($groupId)){
            return false;
        }
        if ($specificationsId) {
            (new GroupSpecificationsService())->addRedisStock($groupId, $specificationsId, $num);
        } elseif($ruleId) {// 场次预约
            (new GroupBookingAppointService())->addRedisStock($groupId, $ruleId, $combineId,$num);
        } else {
            (new GroupService())->addRedisStock($groupId, $num);
        }
        return true;
    }
    /**
     * 支付成功后调用（供支付调用）
     * @param int $orderId 订单id
     * @param array $payParam 支付后的支付数据
     * @return array
     */
    public function afterPay($orderId, $payParam)
    {
        fdump_sql([$orderId, $payParam], 'order', 1);
        if (!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }

        $order = $this->getOne(['order_id' => $orderId]);
        if (!$order) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }
        if(!empty($payParam['current_internal_card_balance']) && !empty($payParam['current_internal_card_ids'])){
            $ecardInfo = (new InternalCardService)->beforePay($order['store_id'], $payParam['current_internal_card_ids'], $payParam['current_internal_card_balance']);
            $payParam['internal_card_pay'] = $ecardInfo['internal_card_pay'];
        }
        $paidTime = isset($payParam['pay_time']) ? $payParam['pay_time'] : '';
        $paidMoney = isset($payParam['paid_money']) ? $payParam['paid_money'] : '';
        $paidType = isset($payParam['paid_type']) ? $payParam['paid_type'] : '';
        $paidOrderid = isset($payParam['paid_orderid']) ? $payParam['paid_orderid'] : '';
        $currentScoreUse = isset($payParam['current_score_use']) ? $payParam['current_score_use'] : '';
        $currentScoreDeducte = isset($payParam['current_score_deducte']) ? $payParam['current_score_deducte'] : '';
        $currentSystemBalance = isset($payParam['current_system_balance']) ? $payParam['current_system_balance'] : '';
        $currentMerchantBalance = isset($payParam['current_merchant_balance']) ? $payParam['current_merchant_balance'] : '';
        $currentMerchantGiveBalance = isset($payParam['current_merchant_give_balance']) ? $payParam['current_merchant_give_balance'] : '';
        $currentQiyeBalance = isset($payParam['current_qiye_balance']) ? $payParam['current_qiye_balance'] : '';
        $offlineMoney = isset($payParam['offline_money']) ? $payParam['offline_money'] : '';
        $isOwn = isset($payParam['is_own']) ? $payParam['is_own'] : '0';
        $uid = isset($payParam['uid']) ? $payParam['uid'] : '0';
        $is_mobile_pay = $payParam['is_mobile_pay']??1;
        //一卡通
        $internalCardPay = (isset($payParam['internal_card_pay']) && $payParam['internal_card_pay']) ? json_decode($payParam['internal_card_pay'], true) : [];
        $order['internal_card_pay_money'] = $internalCardPay ? array_sum($internalCardPay) : 0;//一卡通使用金额

        $order['order_name'] = str_replace('*', '×', $order['order_name']);

        if ($order['paid'] == 1) {
            // 该订单已付款
            return false;
        }

        // 下单用户信息
        $nowUser = (new UserService())->getUser($order['uid']);
        if (empty($nowUser)) { //没有查找到此订单归属的用户，请联系管理员！
            return false;
            throw new \think\Exception(L_("没有查找到此订单归属的用户，请联系管理员！"), 1003);
        }

        if ($order['status'] == 3) {
            return false;
        }

        if (empty($order['stock_reduce_method'])) {//支付成功后减库存支付的时候验证库存量
            try {
                $this->checkBuyNum($order['uid'], $order['group_id'], $order['num'], $order['specifications_id'], $order['order_id']);
            } catch (\Exception $e) {
                return false;
            }
        }

        //判断会员卡余额
        if ($currentMerchantBalance > 0) {
            $user_merchant_balance = (new CardNewService())->getCardByUidAndMerId($order['mer_id'], $order['uid']);
            // 您的会员卡余额不够此次支付
            if ($user_merchant_balance['card_money'] < $currentMerchantBalance) {
                return false;
            }
        }

        if ($currentMerchantGiveBalance > 0) {
            $user_merchant_balance = (new CardNewService())->getCardByUidAndMerId($order['mer_id'], $order['uid']);

            //您的会员卡余额不够此次支付
            if ($user_merchant_balance['card_money_give'] < $currentMerchantGiveBalance) {
                return false;
            }
        }

        $nowUser = [];
        if ($uid) {
            $nowUser = (new UserService())->getUser($uid);
        } elseif ($order['uid']) {
            $nowUser = (new UserService())->getUser($order['uid']);
        }

        //判断帐户余额
        if ($currentSystemBalance > 0) {
            // 您的帐户余额不够此次支付
            if ($nowUser['now_money'] < $currentSystemBalance) {
                return false;
            }
        }

        // 平台积分
        if ($currentScoreUse > 0) {
            //判断积分数量是否正确
            if ($nowUser['score_count'] < $currentScoreUse) {
                return false;
            }
        }

        //如果使用了平台E卡
        if (!empty($order['ecard_password'])) {
            // D('Ecard_coupon')->add_ecard_log($order,'group');
        }

        if ($order['card_id']) {
            try {
                $result = (new MerchantCouponService())->useCoupon($order['card_id'], $order['order_id'], 'group', $order['mer_id'], $nowUser['uid']);
            } catch (\Exception $e) {
                return false;
            }
        }

        //如果使用了平台优惠券
        if ($order['coupon_id']) {
            try {
                $result = (new SystemCouponService())->useCoupon($order['coupon_id'], $order['order_id'], 'group', $order['mer_id'], $nowUser['uid']);
            } catch (\Exception $e) {
                return false;
            }
        }


        //如果用户使用了积分抵扣，则扣除相应的积分
        if ($currentScoreUse > 0) {
            $desc = L_("购买 X1商品 扣除X2", array("X1" => $order['order_name'], "X2" => cfg('score_name')));
            $desc .= L_('，订单编号') . $order['real_orderid'];
            $use_result = (new UserService())->userScore($nowUser['uid'], $currentScoreUse, $desc);
            if ($use_result['error_code']) {
                return false;
            }
        }

        //如果使用会员卡余额
        if ($currentMerchantBalance > 0) {
            $desc = L_("购买 X1商品 扣除会员卡余额", array("X1" => $order['order_name']));
            $desc .= L_('，订单编号') . $order['real_orderid'];
            try {
                $use_result = (new CardNewService())->useMoney($order['mer_id'], $nowUser['uid'], $currentMerchantBalance, $desc);
            } catch (\Exception $e) {
                return false;
            }
        }

        if ($currentMerchantGiveBalance > 0) {
            $desc = L_("购买 X1商品 扣除会员卡赠送余额，订单编号X2", array("X1" => $order['order_name'], 'X2' => $order['real_orderid']));
            $use_result = (new CardNewService())->useGiveMoney($order['mer_id'], $nowUser['uid'], $currentMerchantGiveBalance, $desc);
        }

        //如果用户使用了余额支付，则扣除相应的金额。
        if ($currentSystemBalance > 0) {
            $desc = L_("购买 X1商品 扣除余额，订单编号X2", array("X1" => $order['order_name'], 'X2' => $order['real_orderid']));

            $use_result = (new UserService())->userMoney($nowUser['uid'], $currentSystemBalance, $desc);
            if ($use_result['error_code']) {
                return false;
            }
        }
        //一卡通付款
        if($order['internal_card_pay_money'] > 0){
            try{
                (new InternalCardService)->cardPay([
                    'internal_card_pay' => $payParam['internal_card_pay'],
                    'order_no' => $order['real_orderid'],
                    'business_type' => 'group',
                    'mer_id' => $order['mer_id'],
                    'store_id' => $order['store_id'],
                ]);
            } catch (\Exception $e) {
                fdump_api([$e->getMessage(),$e->getTraceAsString()], 'pay/internal_card_pay',1);
                return false;
            }
        }
        if ($order['tuan_type'] < 2) {// 生成消费码
            $groupPass = $this->createGroupPass($order);
            $order['group_pass'] = $groupPass['group_pass'];
            $order['pass_array'] = $groupPass['pass_array'] ?? 0;
        }
        
        // 商家id
        $payParam['mer_id'] = $diningOrder['mer_id'] ?? 0;

        // 保存支付订单信息
        $saveData = [];
        $saveData['pay_time'] = $paidTime ? $paidTime : time();
        $saveData['payment_money'] = $paidMoney;//在线支付的钱
        $saveData['pay_type'] = $paidType;
        $saveData['third_id'] = $paidOrderid;
        $saveData['paid'] = 1;
        $saveData['is_mobile_pay'] = $is_mobile_pay;
        $saveData['status'] = 0;
        $saveData['score_used_count'] = $currentScoreUse;//积分使用数量
        $saveData['score_deducte'] = $currentScoreDeducte;//积分抵扣金额
        $saveData['balance_pay'] = $currentSystemBalance;//平台余额使用金额
        $saveData['merchant_balance'] = $currentMerchantBalance;//商家会员卡余额使用金额
        $saveData['card_give_money'] = $currentMerchantGiveBalance;//商家会员卡赠送余额使用金额
        $saveData['internal_card_pay'] = $payParam['internal_card_pay']??'';//一卡通支付信息
        $saveData['group_pass'] = $order['group_pass'] ?? '';
        $saveData['pass_array'] = $order['pass_array'] ?? 0;
        // $saveData['qiye_pay'] = $currentQiyeBalance;//企业预存款使用金额
        // $saveData['offline_money'] = $offlineMoney;//线下支付金额
        $saveData['is_own'] = $isOwn;//是否自有支付
        if ($uid) {
            $saveData['uid'] = $uid;
        }

        // 保存订单信息
        $where = [
            'order_id' => $orderId,
        ];
        if (!$this->updateThis($where, $saveData)) {
            return false;
        }

        if($order['tuan_type']==3){ //发兑换码
            $exchange_code_ids = [];
            $exchange_num = $order['num']*$order['exchange_num'];
            if($exchange_num>0){
                $exchange_code_ids = (new GroupRedemptionCode())->where(['group_id'=>$order['group_id'],'order_id'=>0,'status'=>0])->limit($exchange_num)->column('id');
            }
            if($exchange_code_ids){
                (new GroupRedemptionCode())->where([['id','in',$exchange_code_ids],['group_id','=',$order['group_id']],['order_id','=',0],['status','=',0]])->save(['order_id'=>$order['order_id'],'status'=>1,'store_id'=>$order['store_id']]);
                (new GroupOrder())->where(['order_id'=>$orderId])->save(['status'=>1]);
                fdump_api((new GroupRedemptionCode())->getLastSql(),'group/exchange_code',1);
            }else{
                fdump_api([$order['order_id'],$exchange_code_ids],'group/exchange_code',1);
            }
        }

        //增加消息通知
        (new UserNoticeService())->addNotice([
            'type'=>0,
            'business'=>'group',
            'order_id'=>$orderId,
            'mer_id'=>$order['mer_id'],
            'store_id'=>$order['store_id'],
            'uid'=>$order['uid'],
            'title'=>'团购商品下单成功',
            'content'=>'下单成功，商品将尽快为你送达'
        ]);
        $order = array_merge($order, $saveData);

        // 更新系统订单
        $this->addSystemOrder($where);
        /*----保存团购商品订单餐饮套餐信息 20/12/21----*/
        $condition_group_store = [
            'group_id' => $order['group_id'],
            'package_id' => ['gt', 0],
        ];
        $group_store = (new StoreGroupService())->getSome($condition_group_store);
        if (!empty($group_store)) {
            $group_foodshop_package = [];
            foreach ($group_store as $value) {
                $group_foodshop['store_id'] = $value['store_id'];
                $group_foodshop['group_id'] = $order['group_id'];
                $group_foodshop['order_id'] = $order['order_id'];
                $group_foodshop['uid'] = $order['uid'];
                $group_foodshop['package_id'] = $value['package_id'];
                $group_foodshop['num'] = $order['num'];
                $group_foodshop['create_time'] = time();
                $group_foodshop_package[] = $group_foodshop;
            }
            (new GroupFoodshopPackage())->addAll($group_foodshop_package);
        }

        // 添加滚动信息
        (new ScrollMsgService())->addMsg('group', $nowUser['uid'], L_('用户x1于x2购买x3成功', array('x1' => $nowUser['nickname'], 'x2' => date('Y-m-d H:i'), 'x3' => $order['order_name'])));

        // 当前团购信息
        $nowGroup = (new GroupService())->getOne(['group_id' => $order['group_id']]);

        // 当前团购场次预约信息
        $ruleInfo = [];
        if ($nowGroup['group_cate'] == 'booking_appoint') {// 场次预约
            $ruleInfo = (new GroupBookingAppointOrderService())->getOne(['order_id' => $order['order_id']])->toArray();
        }
        $ruleId = $ruleInfo['rule_id'] ?? 0;
        $combineId = $ruleInfo['combine_id'] ?? 0;
        fdump($order,'group_after_pay',1);
        fdump($ruleInfo,'group_after_pay',1);
        if (empty($order['stock_reduce_method'])) {// 支付成功后减库存支付的时候验证库存量
            $this->updateStock($order['group_id'], $order['specifications_id'], $ruleId, $combineId, $order['num'], 1);
        }


        /* 粉丝行为分析 */
        (new MerchantRequestService())->addRequest($order['mer_id'], array('group_buy_count' => $order['num'], 'group_buy_money' => $order['total_money']));

        // App推送
        $this->groupAppNotice($order['order_id'], 1);

        // 用户三级分佣
        (new UserSpreadListService())->setSpreadList($order, 'group');


        /* 计算用户分销获得佣金 */
        if (cfg('open_single_spread') == 1 && $order['distributor_uid'] && $nowGroup['distributor_percent']) {
            $distributor_spread_data = array();
            $spread_total_money = $order['balance_pay'] + $order['payment_money'];
            $distributor_spread_money = round($spread_total_money * $nowGroup['distributor_percent'] / 100, 2);
            if ($distributor_spread_money) {
                $distributor_spread_data = array(
                    'spread_uid' => $order['distributor_uid'],
                    'order_uid' => $order['uid'],
                    'money' => $distributor_spread_money,
                    'order_type' => 'group',
                    'order_id' => $order['order_id'],
                    'third_id' => $order['group_id'],
                    'real_orderid' => $order['real_orderid'],
                    'add_time' => time()
                );
                (new DistributorSpreadListService())->add($distributor_spread_data);
            }
        }

        //参团 单独购买不产生团购小组
        if ($nowGroup['pin_num'] > 0 && !$order['single_buy']) {
            if ($order['is_head']) {
                // 添加拼团购买记录
                (new GroupBuyerListService())->addBuyerList($order['is_head'], $order['uid'], $order['order_id']);

                // 更新拼团信息
                $date_start['num'] = 1;
                $date_start['status'] = 0;
                if ($nowGroup['pin_num'] == $date_start['num']) {
                    $date_start['status'] = 1;
                }
                (new GroupStartService())->updateThis(array('id' => $order['is_head']), $date_start);
            } elseif (!empty($order['pin_fid'])) {// 参加拼团

                (new GroupStartService())->addGroup($order['group_id'], $order['uid'], $nowGroup, $order['order_id'], $order['pin_fid']);
            } else {
                (new GroupStartService())->addGroup($order['group_id'], $order['uid'], $nowGroup, $order['order_id']);
            }
            $this->groupAppNotice($order['order_id'], 3);
        }

        //微信派发优惠券 支付到平台 微信支付
        if ($order['is_own'] == 0 && $order['pay_type'] == 'weixin' && $order['payment_money'] >= cfg('weixin_send_money')) {

            // D('System_coupon')->weixin_send( $order['payment_money'],$order['uid']);
        }

        if (cfg('open_extra_price') == 1 && $order['extra_price'] > 0) {
            $order['total_money'] = ($order['balance_pay'] + $order['merchant_balance'] + $payParam['paid_money']) . '+' . $order['score_used_count'] . cfg('score_name');
        }

        $_total = ($order['balance_pay'] + $order['merchant_balance'] + $payParam['paid_money']);
        if ($order['score_used_count'] && $order['score_used_count'] > 0) {
            $_total .= '+' . $order['score_used_count'] . cfg('score_name');
        }

        //增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store
        (new MerchantService())->saverelation($nowUser['openid'], $order['mer_id'], 5);

        $model = new TemplateNewsService();
        fdump_api(['团购回调' => [$nowUser,$order]],'sendTempMsg',1);
        if ($nowUser['openid']) {
            $href = cfg('site_url') . '/wap.php?c=My&a=group_order&order_id=' . $order['order_id'];
            if ($order['tuan_type'] < 2 && $nowGroup['pin_num'] == 0) {
                if ($nowGroup['open_num'] == 0 && $nowGroup['open_now_num'] == 0 && $nowGroup['group_share_num'] == 0) {
                    $remark = L_("X1成功，您的消费码：X2", array("X1" => cfg('group_alias_name'), "X2" => $order['group_pass']));
                } else {
                    if ($nowGroup['open_now_num'] > $nowGroup['sale_count'] && $nowGroup['open_now_num'] != 0 && $nowGroup['group_share_num'] == 0) {
                        $remark = L_("X1成功，还差X2份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示", array("X1" => cfg('group_alias_name'), "X2" => ($nowGroup['open_now_num'] - $nowGroup['sale_count'])));
                    } else if ($nowGroup['open_num'] > $nowGroup['sale_count'] && $nowGroup['open_num'] != 0 && $nowGroup['open_now_num'] == 0 && $nowGroup['group_share_num'] == 0) {
                        $remark = L_("X1成功，还差X2份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示", array("X1" => cfg('group_alias_name'), "X2" => ($nowGroup['open_num'] - $nowGroup['sale_count'])));
                    } else {
                        $remark = L_("X1成功，您的消费码：X2", array("X1" => cfg('group_alias_name'), "X2" => $order['group_pass']));
                    }
                }
                $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $nowUser['openid'], 'first' => cfg('group_alias_name') . L_('提醒'), 'keyword1' => $order['order_name'], 'keyword2' => $order['real_orderid'], 'keyword3' => $_total, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => $remark), $order['mer_id']);
            } else {
                $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $nowUser['openid'], 'first' => cfg('group_alias_name') . L_('提醒'), 'keyword1' => $order['order_name'], 'keyword2' => $order['real_orderid'], 'keyword3' => $_total, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => cfg('group_alias_name') . L_('成功，感谢您的使用')), $order['mer_id']);
            }
        }

        $sms_data = array('mer_id' => $order['mer_id'], 'store_id' => 0, 'type' => 'group');
        if (!($nowUser['openid'] && $nowUser['is_follow']) && $nowUser['phone'] && (cfg('sms_group_success_order') == 1 || cfg('sms_group_success_order') == 3)) {
            $sms_data['uid'] = $order['uid'];
            $sms_data['mobile'] = $nowUser['phone'];
            $sms_data['sendto'] = 'user';

            if ($order['group_pass'] && $nowGroup['pin_num'] == 0) {
                if ($nowGroup['open_num'] == 0 && $nowGroup['open_now_num'] == 0 && $nowGroup['group_share_num'] == 0 && $nowGroup['pin_num'] == 0) {
                    $remark = L_("您的消费码：X1", array("X1" => $order['group_pass']));
                } else {
                    if ($nowGroup['open_now_num'] > $nowGroup['sale_count'] && $nowGroup['open_now_num'] != 0 && $nowGroup['group_share_num'] == 0) {
                        $remark = L_("还差X1份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示", array("X1" => ($nowGroup['open_now_num'] - $nowGroup['sale_count'])));
                    } else if ($nowGroup['open_num'] > $nowGroup['sale_count'] && $nowGroup['open_num'] != 0 && $nowGroup['open_now_num'] == 0 && $nowGroup['group_share_num'] == 0) {
                        $remark = L_("还差X1份才能成团，快分享给好友吧，成团后消费码将在订单详情中显示", array("X1" => ($nowGroup['open_num'] - $nowGroup['get_order_detail_by_id_and_merId'])));
                    } else {
                        $remark = L_("您的消费码：X1", array("X1" => $order['group_pass']));
                    }
                }
                $sms_data['content'] = L_('您购买 x1的订单(订单号：x2)已经完成支付,x3', array('x1' => $order['order_name'], 'x2' => $order['real_orderid'], 'x3' => $remark));
            } else {
                $sms_data['content'] = L_('您购买 x1的订单(订单号：x2)已经完成支付!', array('x1' => $order['order_name'], 'x2' => $order['real_orderid']));
            }

            (new SmsService())->sendSms($sms_data);
        }
        if (cfg('sms_group_success_order') == 2 || cfg('sms_group_success_order') == 3) {
            $merchant = (new MerchantService())->getMerchantByMerId($order['mer_id']);
            if($merchant['phone']){
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $merchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['content'] = L_('顾客购买的x1的订单(订单号：x2),在x3时已经完成了支付！', array('x1' => $order['order_name'], 'x2' => $order['real_orderid'], 'x3' => date('Y-m-d H:i:s')));
                (new SmsService())->sendSms($sms_data);
            }
        }
        if ($order['trade_info']) {
            $trade_info_arr = unserialize($order['trade_info']);
            if ($order['stock_reduce_method'] == 0) {
                (new TradeHotelStockService())->changeCatStock($order['mer_id'], $trade_info_arr['cat_id'], $trade_info_arr['dep_time'], $trade_info_arr['end_time'], $trade_info_arr['num']);
            }
        }

        //店员APP推送
        if ($order['store_id']) {
            (new AppPushService())->sendMsgGroupOrder($order);
        }

        // 打印订单
        $orderprint = (new OrderprintService())->getOne(['store_id' => $order['store_id']], true);
        if ($orderprint && false !== strpos($orderprint['paid'], '1')) {
            $printHaddle = new PrintHaddleService();
            fdump_api([$orderprint,$order['order_id']],'group_order/afterPay',1);
            $printHaddle->groupOrderPrint($order['order_id'], 1);
        }
        /*----自动核销功能 19/10/21----*/
        $this->autoCheck($order);

        // 优惠组合订单添加一条支付记录
        if ($order['is_group_combine']) {
            $order['nickname'] = $nowUser['nickname'];
            $order['avatar'] = $nowUser['avatar'];
            $order['price'] = $order['price'];
            $order['pay_time'] = $order['pay_time'];
            (new GroupCombineActivityBuyLogService())->addBuyLog($order);

            // 获得佣金
            if ($order['share_uid'] && $order['spread_money'] > 0) {
                $combineDetail = (new GroupCombineActivityService())->sendSpreadMoney($order);
            }
        }
        return true;
    }

    /**
     * 获得支付后跳转链接供支付调用）
     * @param int $orderId 订单id
     * @return array
     */
    public function getPayResultUrl($orderId)
    {
        if (!$orderId) {
            throw new \think\Exception(L_("参数错误"), 1001);
        }
        $order = $this->getOne(['order_id' => $orderId]);
        if (!$order) {
            throw new \think\Exception(L_("当前订单不存在！"), 1003);
        }
        // 支付成功页
        if(!empty($order['is_head'])){
            $url = cfg('site_url') . '/packapp/plat/pages/shopmall_third/inviteFriends?orderid='.$order['order_id'].'&groupPurchase=1';
            return ['redirect_url' => $url, 'direct' => 1];
        }
        $url = cfg('site_url') . '/wap.php?c=Groupnew&a=order_detail&order_id=' . $order['order_id'];
        return ['redirect_url' => $url];

    }

    /**
     * 自动核销
     * @param array $nowOrder 订单信息
     * @return array
     */
    protected function autoCheck($nowOrder)
    {
        $groupInfo = (new GroupService)->getOne(array('group_id' => $nowOrder['group_id']));
        if (empty($groupInfo['auto_check'])||$groupInfo['tuan_type']==2) {//不支持自动核销
            return true;
        } else {
            if ($nowOrder['is_head'] && $nowOrder['pin_fid'] > 0) {//拼团订单
                $group_start = (new GroupStartService())->getGroupStartByOrderId($nowOrder['order_id']);
                $group_start_status = $group_start['status'];
                if ($group_start_status == 0) {// 此订单尚未成团！
                    return false;
                } elseif ($group_start_status == 2) { // 此订单超过有效期，团购作废！
                    return false;
                }

                $order_list = $this->getSome(array('pin_fid' => $nowOrder['pin_fid']));
                foreach ($order_list as $key => $order) {
                    $this->autoCheckInner($order);
                }
            } else {
                $this->autoCheckInner($nowOrder);
            }
            return true;
        }
    }

    /**
     * 订单核销
     * @param array $nowOrder 订单信息
     * @return array
     */
    public function groupVerify($nowOrder)
    {


        return true;
    }

    /**
     * 单个订单自动核销
     * @param array $nowOrder 订单信息
     * @return array
     */
    private function autoCheckInner($order)
    {
        if ($order['status'] != 0 && $order['status'] != 7) {
            return false;
        }

        if ($order['paid'] && ($order['status'] == 0 || $order['status'] == 7)) {
            $where['order_id'] = $order['order_id'];
            if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
                $saveData['third_id'] = $order['order_id'];
            }
            $saveData['status'] = '1';
            $saveData['store_id'] = $order['store_id'];
            $saveData['use_time'] = time();
            $saveData['last_staff'] = '系统自动核销';
            $saveData['staff_name'] = '系统自动核销';
            $saveData['verify_time'] = time();
            $saveData['last_random'] = mt_rand(100000000, 999999999);
            if (!$this->updateThis($where, $saveData)) {
                return false;
            }
            if (!(new SystemOrder())->where(['order_id' => $order['order_id']])->update(['system_status' => 2])) {
                return false;
            }
            // 计算未验证的验证码数量
            $passNum = $order['pass_num'] > 1 ? intval($order['pass_num']) : 1; // 一份团购 对应生成核销券 份数
            $consume_num = (new GroupPassRelationService())->getPassNum($order['order_id'], 1);
            $unConsumePassNum = $order['num'] * $passNum - $consume_num;

            $order['unconsume_pass_num'] = $unConsumePassNum; // 未验证核销码数量

            // 修改核销码状态
            $staff_info = array(
                'staff_name' => '系统自动核销',
                'verify_time' => time(),
            );
            $consume_num = (new GroupPassRelationService())->changeRefundStatus($order['order_id'], 1, $staff_info);
            //验证增加商家余额
            $order['order_type'] = 'group';
            $order['verify_all'] = 1;
            // try {
                $this->billOrder($order);
            // } catch (\Exception $e) {
            //     throw new \think\Exception($e->getMessage(), $e->getCode());
            // }
            $this->groupNotice($order, 1);
        } else {
            return false;
        }
    }

    /**
     * 商家对账
     * @param array $nowOrder 订单信息
     * @param bool $getSystemTakeMoney 是否仅返回抽成金额 不结算 默认：false
     * @return bool
     */
    public function billOrder($nowOrder, $getSystemTakeMoney = false)
    {
        $orderInfo = [];
        $orderInfo['get_system_take_money'] = $getSystemTakeMoney;
        $orderInfo['order_id'] = $nowOrder['order_id'];
        $orderInfo['real_orderid'] = $nowOrder['real_orderid'];
        $orderInfo['mer_id'] = $nowOrder['mer_id'];
        $orderInfo['store_id'] = $nowOrder['store_id'];//当前门店ID
        $orderInfo['order_name'] = $nowOrder['order_name'];
        $orderInfo['uid'] = $nowOrder['uid'];//用户id
        $orderInfo['order_type'] = $nowOrder['order_type'];//业务代号 group
        $orderInfo['desc'] = $nowOrder['desc'] ?? L_('用户购买X1记入收入', $nowOrder['order_name']);
        $orderInfo['score_discount_type'] = $nowOrder['score_discount_type'];//优惠通道 0 都行 1 优惠 2 积分 3 2选一

        $orderInfo['payment_money'] = !$nowOrder['is_own'] ? $nowOrder['payment_money'] : 0;//在线支付金额（不包含自有支付）
        $orderInfo['balance_pay'] = $nowOrder['balance_pay'];//平台余额支付金额
        $orderInfo['score_deducte'] = $nowOrder['score_deducte'];//平台积分抵扣金额
        $orderInfo['merchant_balance'] = $nowOrder['merchant_balance'];//商家会员卡支付金额
        $orderInfo['card_give_money'] = $nowOrder['card_give_money'];//商家会员卡赠送支付金额
        $orderInfo['score_used_count'] = $nowOrder['score_used_count'];//积分使用数量

        $orderInfo['is_own'] = $nowOrder['is_own'];//自有支付类型
        $orderInfo['own_pay_money'] = $nowOrder['is_own'] ? $nowOrder['payment_money'] : 0;//自有支付在线支付金额
        $orderInfo['pay_order_id'] = $nowOrder['third_id'];//支付单号id
        $orderInfo['pay_type'] = $nowOrder['pay_type'];//支付方式

        if (!empty($nowOrder['refund'])) {
            $num = 1;
            $money = $nowOrder['refund_money'];
            $orderInfo['bill_money'] = $money;// 对账金额
            $orderInfo['total_money'] = $money;// 总金额
        } elseif (!$nowOrder['verify_all']) {
            $num = 1;
            if ($nowOrder['pay_type'] == 'offline') {
                $count = (new GroupPassRelationService())->getPassNum($nowOrder['order_id'], 1);// 核销码数
                if ($nowOrder['score_deducte'] > $nowOrder['price']) {
                    if ($nowOrder['score_deducte'] - $count * $nowOrder['price'] > 0) {
                        $money = $nowOrder['price'];
                    } else {
                        $money = $nowOrder['score_deducte'] - ($count - 1) * $nowOrder['price'];
                    }
                } else {
                    if ($count == 1) {
                        $money = $nowOrder['score_deducte'];
                    } else {
                        throw new \think\Exception(L_("无收入记录"), 1003);
                    }
                }
            } else {
                $passNum = $nowOrder['pass_num'] > 1 ? intval($nowOrder['pass_num']) : 1; // 一份团购 对应生成核销券 份数
                $passTotalNum = $nowOrder['num'] * $passNum;
                $money = ($nowOrder['balance_pay'] + $orderInfo['payment_money'] + $nowOrder['score_deducte'] + $nowOrder['coupon_price'] + $nowOrder['merchant_balance']) / $passTotalNum * 100 / 100;
            }
            $orderInfo['bill_money'] = $money;
            $orderInfo['total_money'] = $money + $orderInfo['own_pay_money'] / $passTotalNum * 100 / 100;
        } else {
            $passNum = $nowOrder['pass_num'] > 1 ? intval($nowOrder['pass_num']) : 1; // 一份团购 对应生成核销券 份数
            $passTotalNum = $nowOrder['num'] * $passNum; // 总的核销码数量
            $unConsumePassNum = (isset($nowOrder['unconsume_pass_num']) && $nowOrder['unconsume_pass_num']) ? $nowOrder['unconsume_pass_num'] : $passTotalNum; // 未验证核销码数量
            $num = $unConsumePassNum;
            $moneyPrice = ($nowOrder['balance_pay'] + $orderInfo['payment_money'] + $nowOrder['score_deducte'] + $nowOrder['coupon_price'] + $nowOrder['merchant_balance']) / $passTotalNum * 100 / 100;

            /*//拉取商品，算单个商品的抽成
            $goods_detail = (new GroupService())->getOne(['group_id' => $nowOrder['group_id']]);
            if ($goods_detail['score_percent'] > 0) {
                (new UserService())->addScore($nowOrder['uid'], round($goods_detail['score_percent'] * $moneyPrice), '购买团购验证消费获得' . cfg('score_name'),0,[
                    'mer_id' => $nowOrder['mer_id'],
                    'store_id' => $nowOrder['store_id'],
                    'order_id' => $nowOrder['order_id'],
                    'order_type' => 'group',
                ]);
            }*/

            $money = $moneyPrice * $unConsumePassNum;
            $orderInfo['bill_money'] = $money;
            $orderInfo['total_money'] = $money + ($orderInfo['own_pay_money'] / $passTotalNum * 100 / 100)*$unConsumePassNum;
            if ($nowOrder['real_orderid'] && $passTotalNum != 1) {// 只有一个核销码的不验证
                $has = (new MerchantMoneyListService())->getOne(array('order_id' => $nowOrder['real_orderid']));
                if ($has && $money && floatval($money) == floatval($has['total_money'])) {
                    return false;
                }
            }
        }
        
        $orderInfo['express_fee'] = $orderInfo['total_money'] * $money > 0 ?  $nowOrder['express_fee'] / ($orderInfo['total_money'] * $money * 100 / 100) : 0;
        $orderInfo['bill_money'] -= $orderInfo['express_fee'];
        $orderInfo['money_system_take'] = $orderInfo['bill_money'];//平台抽成基数
        $orderInfo['num'] = $num;
        $orderInfo['group_id'] = !empty($nowOrder['group_id']) ? $nowOrder['group_id'] : 0;
        $orderInfo['union_mer_id'] = 0;//缺少这个字段，会导致报错

        // 结算
        $res = (new MerchantMoneyListService())->addMoney($orderInfo);
        return $res;
        
    }

    /**
     * 核销后的处理
     * @param array $nowOrder 订单信息
     * @return boll
     */
    public function groupNotice($order, $verify_all)
    {
        //积分
        $nowUser = (new UserService())->getUser($order['uid']);
        if ($verify_all) {
            if (cfg('open_extra_price') == 1) {
                $order['order_type'] = 'group';
                $score = (new PercentRateService())->getExtraMoney($order);
                if ($score > 0) {
                    (new UserService())->addScore($order['uid'], floor($score), L_('购买 X1 消费X2 获得X3', array('X1' => $order['order_name'], 'X2' => floatval($order['total_money']) . cfg('Currency_txt'), 'X3' => cfg('extra_price_alias_name'))),0,[
                        'mer_id' => $order['mer_id'],
                        'store_id' => $order['store_id'],
                        'order_id' => $order['order_id'],
                        'order_type' => 'group',
                    ]);
                }
            } else {
                if (cfg('add_score_by_percent') == 0 && (cfg('open_score_discount') == 0 || $order['score_discount_type'] != 2) && (cfg('score_get_times') == 0 || !cfg('score_get_times'))) {
                    if ($order['is_own'] && cfg('user_own_pay_get_score') != 1) {
                        $order['payment_money'] = 0;
                    }
                    $score_get = (new PercentRateService())->scorePercent('group', $order['group_id'], $order['mer_id']);
                    if($score_get>0){
                        $score_get = (new PercentRateService())->getUserAddScoreTimes($score_get, $order['uid']);
                        (new UserService())->addScore($order['uid'], ($order['payment_money'] + $order['balance_pay']) * $score_get, L_('购买 X1 消费X2 获得X3', array('X1' => $order['order_name'], 'X2' => floatval($order['total_money']) . cfg('Currency_txt'), 'X3' => cfg('score_name'))),0,[
                            'mer_id' => $order['mer_id'],
                            'store_id' => $order['store_id'],
                            'order_id' => $order['order_id'],
                            'order_type' => 'group',
                        ]);
                    }
                    
                    (new ScrollMsgService())->addMsg('group', $nowUser['uid'], L_('用户X1于X2购买 X3成功并消费获得X4', array('X1' => str_replace_name($nowUser['nickname']), 'X2' => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']), 'X3' => $order['order_name'], 'X4' => cfg('score_name'))));
                }
            }
            //商家推广分佣
            (new MerchantSpreadService())->addSpreadList($order, $nowUser, 'group', L_('X1购买X2获得佣金', array('X1' => $nowUser['nickname'], 'X2' => cfg('group_alias_name'))));
        }
        $this->groupAppNotice($order['order_id'], 7);

        //短信
        $sms_data = array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id'], 'type' => 'group');
        if ($nowUser['openid']) {

            $model = new TemplateNewsService();
            $href = cfg('site_url') . '/wap.php?c=My&a=group_order&order_id=' . $order['order_id'];
            $model->sendTempMsg('TM00017', array('href' => $href,
                'wecha_id' => $nowUser['openid'],
                'first' => cfg('group_alias_name') . L_('团购提醒'),
                'OrderSn' => $order['real_orderid'],
                'OrderStatus' => L_("您购买的团购已于X1核销成功", date('Y-m-d H:i:s', time())),
                'remark' => L_('点击查看详情')),
                $order['mer_id']);
        }
        if (cfg('sms_group_finish_order') == 1 || cfg('sms_group_finish_order') == 3) {
            $sms_data['uid'] = $order['uid'];
            $sms_data['mobile'] = $order['phone'];
            $sms_data['sendto'] = 'user';
            if (empty($order['res'])) {
                $sms_data['content'] = L_('您购买 X1的订单(订单号：X2)已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！', array('X1' => $order['order_name'], 'X2' => $order['real_orderid']));
            } else {
                $sms_data['content'] = L_('您购买 X1的订单(消费码：X2)已经完成了消费，如有任何疑意，请您及时联系我们！', array('X1' => $order['order_name'], 'X2' => $order['res']['group_pass']));
            }
            (new SmsService)->sendSms($sms_data);
        }
        if (cfg('sms_group_finish_order') == 2 || cfg('sms_group_finish_order') == 3) {
            $phone = (new MerchantStore())->where(['store_id' => $order['store_id']])->value('phone');
            if ($phone) {
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $phone;
                $sms_data['sendto'] = 'merchant';
                $sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！', array('X1' => $order['order_name'], 'X2' => $order['real_orderid']));
                (new SmsService)->sendSms($sms_data);
            }
        }
        //打印
        $printHaddle = new PrintHaddleService();
        $printHaddle->groupOrderPrint($order['order_id'], 'group_order', 2);
    }

    /**
     * app 极光推送
     * @param int $orderId
     * @param int $status
     * @return array
     */
    public function groupAppNotice($orderId, $status = 0)
    {
        $nowOrder = $this->getOne(['order_id' => $orderId]);
        $nowOrder['status'] = $status;
        (new AppPushService())->send($nowOrder, 'group');
    }

    /**
     * 获得订单状态
     * @param $data array 数据
     * @return array
     */
    public function getOrderStatus($order)
    {
        if ($order['status'] == 3) {
            return L_('已取消');//已退款
        } elseif ($order['paid']) {
            // 支付过
            if ($order['pay_type'] == 'offline' && empty($order['third_id'])) {
                return L_('线下支付 未付款');
            } elseif ($order['status'] == 0) {
                if ($order['tuan_type'] != 2 && $order['is_pay_bill'] == 0) {

                    if ($order['is_group_combine'] == 1 && ($order['pay_time'] + $order['can_use_day'] * 86400) < time()) {
                        // 优惠组合订单 查看过期时间
                        return L_('已过期');
                    } else {
                        return L_('待核销');
                    }
                } else if ($order['tuan_type'] != 2 && $order['is_pay_bill'] == 1) {
                    return L_('部分消费');
                } else {
                    if ($order['is_pick_in_store']) {
                        return L_('未取货');
                    } else {
                        if ($order['express_id']) {
                            return L_('未确认收货');
                        } else {
                            return L_('未发货');
                        }
                    }
                }
            } elseif ($order['status'] == 1) {
                if ($order['tuan_type'] != 2) {
                    return L_('已消费');
                } else {
                    if ($order['is_pick_in_store']) {
                        return L_('已取货');
                    } else {
                        return L_('已收货');
                    }
                }
            } elseif ($order['status'] == 5) {
                return L_('未完全消费');
            } elseif ($order['status'] == 6) {
                return L_('已取消');
            } elseif ($order['status'] == 7) {
                return L_('退款失败');
            } else {
                return L_('已完成');
            }
        } elseif ($order['status'] == 4) {
            return L_('已取消');
        } else {
            return L_('未付款');
        }
    }


    /**
     * 获得订单类型
     * @param $data array 数据
     * @return array
     */
    public function getOrderType($order)
    {
        if ($order['is_group_combine'] == 1) {
            $orderType = L_('优惠组合');
        } elseif ($order['tuan_type'] == 0) {
            $orderType = L_('X1券', cfg('group_alias_name'));
        } elseif ($order['tuan_type'] == 1) {
            $orderType = L_('代金券');
        } else {
            $orderType = L_('实物');
        }
        return $orderType;
    }

    /**
     * 添加保存订单
     * @param $data array 数据
     * @return array
     */
    public function saveCombineOrder($param)
    {
        $saveData = [];
        $returnArr = [];
        //优惠组合id
        $combineId = $param['combine_id'] ?? 0;
        //用户信息
        $userInfo = $param['user'] ?? [];
        // 分享人uid
        $shareUid = $param['share_uid'] ?? [];

        if (empty($combineId)) {
            throw new \think\Exception(L_("缺少参数"), 1003);
        }

        $uid = $userInfo['uid'] ?? 0;

        $where = [
            'combine_id' => $combineId,
            'status' => 1
        ];

        // 活动详情
        $nowGroup = (new GroupCombineActivityService())->getOne($where);
        if (empty($nowGroup)) {
            throw new \think\Exception(L_("活动不存在"), 1003);
        }

        // 验证库存
        if ($nowGroup['stock_num'] != -1 && $nowGroup['stock_num'] <= $nowGroup['sell_count']) {
            throw new \think\Exception(L_("商品已售完"), 1003);
        }

        // 验证有效期
        if ($nowGroup['start_time'] > time()) {
            throw new \think\Exception(L_("活动未开始"), 1003);
        }
        if ($nowGroup['end_time'] < time()) {
            throw new \think\Exception(L_("活动已结束"), 1003);
        }

        // 限购
        if ($nowGroup['limit_number'] > 0) {
            // 用户已购买数量
            $detail['has_buy_count'] = 0;
            $where = [
                ['uid', '=', $userInfo['uid']],
                ['status', 'not in', '3,4'],
                ['combine_id', '=', $nowGroup['combine_id']],
            ];
            $hasBuyCount = $this->getCount($where);
            if ($hasBuyCount >= $nowGroup['limit_number']) {
                throw new \think\Exception(L_("该优惠组合您购买已达上限啦  快去看看其他优惠组合吧~"), 1003);
            }
        }

        $saveData['is_group_combine'] = 1;
        $saveData['combine_id'] = $nowGroup['combine_id'];
        $saveData['num'] = 1;
        $saveData['pass_num'] = 0;
        $saveData['share_uid'] = $shareUid;
        $saveData['stock_reduce_method'] = 1; //库存减少方式（0：支付后减库存，1：下单即减库存）
        $saveData['order_name'] = L_($nowGroup['title']) . '*' . $saveData['num'];
        if ($saveData['share_uid']) {//分佣佣金
            $saveData['spread_money'] = $nowGroup['spread_money'];
        }
        //1 为单独购买 3 为发起团购 2 为参团
        $saveData['price'] = $nowGroup['price'];
        $saveData['single_buy'] = 1;
        $saveData['is_mobile_pay'] = 1;

        $saveData['total_money'] = $saveData['price'] * $saveData['num'];
        $saveData['add_time'] = time();

        $orderid = build_real_orderid($userInfo['uid']);//real_orderid
        $saveData['real_orderid'] = $orderid;
        $saveData['uid'] = $uid;
        $saveData['phone'] = $userInfo['phone'];
        $saveData['can_use_day'] = $nowGroup['can_use_day'];//优惠组合自购买起可使用时间
        $saveData['can_cancel_combine'] = $nowGroup['can_cancel']; // 优惠组合订单是否可以取消0-否1-是
        $saveData['can_use_count'] = $nowGroup['can_use_count']; // 优惠组合可使用次数

        $orderId = $this->add($saveData);

        if (!$orderId) {
            throw new \think\Exception(L_("订单产生失败！请重试"), 1003);
        }

        // 获得绑定的商品
        $where = [
            'combine_id' => $nowGroup['combine_id']
        ];
        $goods = (new GroupCombineActivityGoodsService())->getBindList($where);

        // 生成子订单
        $saveArr = [];
        foreach ($goods as $_goods) {
            $temp = [
                'order_id' => $orderId,
                'combine_id' => $nowGroup['combine_id'],
                'mer_id' => $_goods['mer_id'],
                'group_id' => $_goods['group_id'],
                'cost_price' => $_goods['cost_price'],
                'use_count' => $_goods['use_count'],
                'price' => $_goods['price'],
                'old_price' => $_goods['old_price'],
                'name' => $_goods['name'],
                'create_time' => time(),
            ];
            $saveArr[] = $temp;
        }
        (new GroupCombineActivityOrderGoodsService())->addAll($saveArr);

        // 处理库存 减少库存
        $saveData['sell_count'] = $nowGroup['sell_count'];
        (new GroupCombineActivityService())->updateStock($saveData, 1);

        $returnArr['msg'] = L_('保存成功');
        $returnArr['url'] = cfg('site_url') . '/packapp/plat/pages/shop_new/confirmOrder/pay?type=group&order_id=' . $orderId;
        return $returnArr;
    }

    /**
     * 获得优惠组合订单详情
     * @param $data array 数据
     * @return array
     */
    public function getGroupCombineOrderList($param)
    {
        $page = isset($param['page']) ? $param['page'] : 0;
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 0;//每页限制
        $keyword = isset($param['keyword']) ? $param['keyword'] : '';//搜索关键词
        $searchtype = isset($param['searchtype']) ? $param['searchtype'] : '';//搜索关键词类型
        $timetype = isset($param['time_type']) ? $param['time_type'] : '';//搜索时间类型
        $startTime = isset($param['start_time']) ? $param['start_time'] : '';//开始时间
        $endTime = isset($param['end_time']) ? $param['end_time'] : '';//结束时间
        $payType = isset($param['pay_type']) ? $param['pay_type'] : '';//订单类型
        $orderBy = isset($param['order_by']) ? $param['order_by'] : [];//排序

        // 搜索条件
        $where = [];
        // 排序
        $order = $orderBy;

        // 关键词搜索
        if (!empty($keyword)) {
            switch ($searchtype) {
                case 'real_orderid':
                    $where[] = ['o.real_orderid', 'like', '%' . $keyword . '%'];
                    break;
                case 'title':
                    $where[] = ['g.title', 'like', '%' . $keyword . '%'];
                    break;
            }
        }


        // 时间
        if (!empty($startTime) && !empty($endTime)) {
            if ($startTime > $endTime) {
                throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
            }

            $endTime = $endTime . ' 23:59:59';
            $period = array(
                strtotime($startTime),
                strtotime($endTime)
            );
            $where[] = ['o.' . $timetype, 'BETWEEN', $period];
        }

        // 支付方法方式
        if (isset($param['pay_type']) && $payType != '-1') {
            $where[] = ['o.paid', '=', 1];
            $where[] = ['o.pay_type', '=', $payType];
        }

        $field = 'o.*,g.title,u.nickname';

        // 查询列表
        $list = $this->getGroupCombineOrderListByJoin($where, $field, $order, $page, $pageSize);
        // 查询总数
        $count = $this->getGroupCombineOrderCountByJoin($where);

        foreach ($list as &$_order) {
            $_order['pay_time_str'] = $_order['pay_time'] ? date('Y-m-d H:i:s', $_order['pay_time']) : '';
            $_order['add_time_str'] = $_order['add_time'] ? date('Y-m-d H:i:s', $_order['add_time']) : '';

            //订单有效期
            if ($_order['pay_time']) {
                $_order['can_use_end_time'] = date('Y-m-d H:i:s', $_order['pay_time'] + $_order['can_use_day'] * 86400);
            } else {
                $_order['can_use_end_time'] = '--';
            }

            $_order['status_str'] = $this->getOrderStatus($_order);
        }

        $returnArr['list'] = $list;
        $returnArr['count'] = $count;
        return $returnArr;
    }

    /**
     * 获取订单详情
     * @param $data array 数据
     * @return array
     */
    public function getOrderDetail($param)
    {
        //订单id
        $orderId = $param['order_id'] ?? 0;

        $where = [
            'order_id' => $orderId,
        ];
        $returnArr = $this->getOne($where);
        // 订单状态
        $returnArr['order_status'] = $this->getOrderStatus($returnArr);

        $returnArr['pay_time_old'] = $returnArr['pay_time'];
        $returnArr['price'] = get_format_number($returnArr['price']);
        $returnArr['pay_time'] = $returnArr['pay_time'] ? date('Y-m-d H:i:s', $returnArr['pay_time']) : '';
        $returnArr['add_time_old'] = $returnArr['add_time'];
        $returnArr['add_time'] = $returnArr['add_time'] ? date('Y-m-d H:i:s', $returnArr['add_time']) : '';

        // 支付方式
        $returnArr['paid'] && $returnArr['pay_type_txt'] = (new ConfigService())->getPayName($returnArr['pay_type'], $returnArr['is_mobile_pay']);

        // 待支付 链接
        $returnArr['go_pay_url'] = cfg('site_url') . '/packapp/plat/pages/shop_new/confirmOrder/pay?type=group&order_id=' . $orderId;

        // 用户信息
        if ($returnArr['uid']) {
            $nowUser = (new UserService())->getUser($returnArr['uid']);
            $returnArr['nickname'] = $nowUser['nickname'] ?? '';
        }

        // 订单类型
        $returnArr['order_type'] = $this->getOrderType($returnArr);

        // 支付方式
        $returnArr['pay_type_str'] = (new ConfigService())->getPayName($returnArr['pay_type'], $returnArr['is_mobile_pay'], $returnArr['paid']);

        // 优惠组合订单详情
        if ($returnArr['is_group_combine']) {
            // 优惠组合详情
            $combine = (new GroupCombineActivityService())->getOne(['combine_id' => $returnArr['combine_id']]);
            $returnArr['title'] = $combine['title'];
            //可使用优惠组合券总次数
            $returnArr['old_price'] = get_format_number($combine['old_price']);

            // 商品详情
            $goodsList = (new GroupCombineActivityOrderGoodsService())->getGoodsList($param);
            $returnArr['group_list'] = $goodsList['list'];
            //剩余可使用优惠组合券次数
            $returnArr['now_use_count'] = max(0, $returnArr['can_use_count'] - $returnArr['combine_used_count']);

            $returnArr['can_use_end_time'] = '';
            if ($returnArr['pay_time_old']) {
                $canUseEndTime = $returnArr['pay_time_old'] + $returnArr['can_use_day'] * 86400;
                $returnArr['can_use_end_time'] = date('Y-m-d H:i:s', $canUseEndTime);
            }

            // 消费记录 已核销记录
            $where = [
                ['o.is_group_combine', '=', '1'],
                ['o.combine_order_id', '=', $returnArr['order_id']],
            ];
            $field = 'o.order_id,g.name,s.name as store_name,o.price,o.group_pass,o.staff_name';
            $goodsOrderList = $this->getGroupOrderListByJoin($where, $field);
            $returnArr['has_used_goods'] = $goodsOrderList;
        }
        return $returnArr;
    }

    /**
     * 写入平台总订单
     * @param $tableId int 桌台id
     * @return array
     */
    public function addSystemOrder($where = [], $step = 1)
    {
        if (!$where) return false;

        $nowOrder = $this->getOne($where);

        $systemOrderService = new SystemOrderService();
        $business = 'group';
        $businessOrderId = $nowOrder['order_id'];
        if ($step == 0) {
            // system_status  0-待支付 2-完成订单 待评价 3-完成订单 5-取消订单
            $saveData['store_id'] = $nowOrder['store_id'] ?? 0;
            $saveData['real_orderid'] = $nowOrder['real_orderid'];

            $saveData['price'] = $nowOrder['price'];
            $saveData['goods_price'] = $nowOrder['total_money'];
            $saveData['total_price'] = $nowOrder['total_money'];
            $saveData['mer_id'] = $nowOrder['mer_id'];
            $saveData['store_id'] = $nowOrder['store_id'];
            $saveData['num'] = $nowOrder['num'];
            $saveData['system_status'] = 0;
            // 添加系统总订单
            // 待支付
            $saveData['system_status'] = 0;

            $systemOrderService->saveOrder($business, $businessOrderId, $nowOrder['uid'], $saveData);
        } else {
            if ($nowOrder['paid'] == 1 && $nowOrder['status'] == 0) {
                $systemOrderService->paidOrder($business, $businessOrderId);
            } elseif ($nowOrder['status'] == 2) {
                // 已完成
                $saveData['system_status'] = 20;
                $systemOrderService->editOrder($business, $businessOrderId, $saveData);
            } elseif ($nowOrder['status'] == 3 || $nowOrder['status'] == 4) {
                //退款
                $systemOrderService->cancelOrder($business, $businessOrderId);
            } elseif ($nowOrder['status'] == 1 && $nowOrder['tuan_type'] == 3) { //兑换码支付成功后直接已完成，无法退款
                $systemOrderService->paidOrder($business, $businessOrderId);
                // 已完成，待评价
                $saveData['system_status'] = 2;
                $systemOrderService->editOrder($business, $businessOrderId, $saveData);
            }
        }
        return true;
    }

    /**
     * 获得优惠组合订单列表
     * @param $data array 数据
     * @return array
     */
    public function getGroupCombineOrderListByJoin($where, $field = true, $order = [], $page = 0, $pageSize = 0)
    {
        if (empty($order)) {
            $order = ['o.order_id' => 'DESC'];
        }
        $orderList = $this->groupOrderModel->getGroupCombineOrderListByJoin($where, $field, $order, $page, $pageSize);
        if (!$orderList) {
            return [];
        }
        return $orderList->toArray();
    }

    /**
     * 获得订单列表
     * @param $data array 数据
     * @return array
     */
    public function getGroupOrderListByJoin($where, $field = true, $order = [], $page = 0, $pageSize = 0)
    {
        if (empty($order)) {
            $order = ['o.order_id' => 'DESC'];
        }
        $orderList = $this->groupOrderModel->getGroupOrderListByJoin($where, $field, $order, $page, $pageSize);
        if (!$orderList) {
            return [];
        }
        return $orderList->toArray();
    }

    /**
     * 获得订单支付方式列表
     * @param $data array 数据
     * @return array
     */
    public function getPayMethodList()
    {
        // 获得列表
        $list = invoke_cms_model('Config/get_pay_method', ['notOnline' => 0, 'notOffline' => 1, 'is_wap' => true, 'is_app' => false, 'is_all' => true]);

        if ($list['error_no']) {
            $returnArr = [];
        } else {
            foreach ($list['retval'] as $key => $value) {
                $returnArr[] = [
                    'key' => $key,
                    'name' => $value['name'],
                ];
            }
        }
        return $returnArr;
    }

    /**
     * 修改订单备注
     * @param $param array 数据
     * @return array
     */
    public function editOrderNote($param)
    {
        $orderId = $param['order_id'] ?? 0;
        if (empty($orderId)) {
            throw new \think\Exception(L_("缺少参数"), 1003);
        }

        // 备注
        $noteInfo = $param['note_info'] ?? '';

        // 条件
        $where = [
            ['order_id', '=', $orderId]
        ];

        // 保存数据
        $saveData = [
            'note_info' => $noteInfo
        ];
        $res = $this->updateThis($where, $saveData);
        if ($res === false) {
            throw new \think\Exception(L_("修改失败"), 1003);
        }

        $returnArr['msg'] = L_('修改成功');
        return $returnArr;
    }

    /**
     * 团购商品生成消费码
     * author 衡婷妹
     * date 20201120
     */
    public function createGroupPass($nowOrder)
    {

        if ($nowOrder['tuan_type'] < 2) {
            // 团购组合优惠消费码长度16位 其他14位
            if ($nowOrder['is_group_combine']) {
                $min = 1000;
                $max = 9999;
            } else {
                $min = 10;
                $max = 99;
            }

            $nowGroup_pass_array = array(
                date('y', time()),
                date('m', time()),
                date('d', time()),
                date('H', time()),
                date('i', time()),
                date('s', time()),
                mt_rand($min, $max),
            );
            shuffle($nowGroup_pass_array);

            $returnArr['group_pass'] = implode('', $nowGroup_pass_array);
            $nowGroup_pass_arr[$returnArr['group_pass']] = 0;
            $date_group_pass_array[] = array('order_id' => $nowOrder['order_id'], 'group_pass' => $returnArr['group_pass'], 'status' => 0);
            $passNum = $nowOrder['pass_num'] > 0 ? intval($nowOrder['pass_num']) : 1;
            if ($nowOrder['num'] > 1 || $passNum > 1) {
                $passNum = intval($nowOrder['num']) * $passNum;
                for ($i = 0; $i < $passNum - 1; $i++) {
                    $nowGroup_pass_array = array(
                        date('y', time()),
                        date('m', time()),
                        date('d', time()),
                        date('H', time()),
                        date('i', time()),
                        date('s', time()),
                        mt_rand($min, $max),
                    );
                    shuffle($nowGroup_pass_array);
                    $tmp = implode('', $nowGroup_pass_array);
                    $date_group_pass_array[] = array('order_id' => $nowOrder['order_id'], 'group_pass' => $tmp, 'status' => 0);
                }
                $returnArr['pass_array'] = 1;
            }
            (new GroupPassRelationService())->addAll($date_group_pass_array);
        }
        return $returnArr;
    }

    /**
     * 验证商品库存是否足够
     * @param int $uid 用户id
     * @param int $nowGroupId 团购id
     * @param int $num 购买数量
     * @param int $specificationsId 规格id
     * @param int $orderId 订单id
     * @return array
     */
    public function checkBuyStockNum($uid, $nowGroupId, $num, $specificationsId = 0, $orderId = 0)
    {
        // 获得商品已售总数
        $where = [
            ['group_id', '=', $nowGroupId],
            ['uid', '=', $uid],
            ['paid', '=', 1],
            ['status', '<', 3],
        ];

        // 带规格的规格id
        if ($specificationsId && $specificationsId > 0) {
            $where[] = ['specifications_id', '=', $specificationsId];
        }
        // 订单id
        if ($orderId > 0) {
            $where[] = ['order_id', '<>', $orderId];
        }

        // 用户已购买此商品的总数
        $count = (new GroupOrderService())->getOne($where, 'sum(num) as num')['num'];

        // 获得商品详情
        if ($specificationsId && $specificationsId > 0) {
            // 规格详情
            $where = [
                ['specifications_id', '=', $specificationsId],
            ];
            $nowGroup = (new GroupSpecificationsService())->getOne($where);
        } else {
            $nowGroup = (new GroupService())->getGroupDetail($nowGroupId);
        }

        // 一个ID最多购买数量
        $onceMax = $nowGroup['once_max'];

        // 一个ID最少购买数量
        $onceMin = $nowGroup['once_min'];

        //商品有库存限制的时候
        if ($nowGroup['count_num'] > 0) {
            // 实际库存
            $nowGroupNum = $nowGroup['count_num'] - $nowGroup['sale_count'];

            //库存小于最少购买量的时候不能买
            if ($nowGroupNum < $onceMin) {
                throw new \think\Exception(L_("该商品已售空"), 1003);
            }

            if ($onceMax > 0) {
                // 我的剩余得到份数
                $myNum = $onceMax - $count;
                // 与库存取较小值
                $myNum = min($nowGroupNum, $myNum);
                // 与最大购买量取较小值
                $myNum = min($onceMax, $myNum);

                if ($myNum < $num) {// 购买量大于剩余珂购买数量
                    if ($myNum > 0) {
                        throw new \think\Exception(L_("您最多能购买X1份", array("X1" => $myNum)), 1003);
                    } else {
                        throw new \think\Exception(L_("该商品限制单人只能购买X1份，您购买的数量已达上限，不能再购买!", array("X1" => $onceMax)), 1003);
                    }
                } elseif ($num < $onceMin) {
                    throw new \think\Exception(L_("您一次最少要购买X1份", array("X1" => $onceMin)), 1003);
                }
            } else {
                if ($num > $nowGroupNum) {
                    throw new \think\Exception(L_("您最多能购买X1份", array("X1" => $nowGroupNum)), 1003);
                }
            }
        } else {
            if ($onceMax > 0) {
                $myNum = $onceMax - $count;//我的剩余得到份数
                $myNum = $myNum > $onceMax ? $onceMax : $myNum;
                if ($myNum < $num) {
                    if ($myNum > 0) {
                        throw new \think\Exception(L_("您最多能购买X1份", array("X1" => $myNum)), 1003);
                    } else {
                        throw new \think\Exception(L_("此商品每个用户只能购买X1份", array("X1" => $onceMax)), 1003);
                    }
                } elseif ($num < $onceMin) {
                    throw new \think\Exception(L_("您一次最少要购买X1份", array("X1" => $onceMin)), 1003);
                }
            }
        }

        if ($num < $onceMin) {
            throw new \think\Exception(L_("您一次最少要购买X1份", array("X1" => $onceMin)), 1003);
        }

        //每ID每天限购
        if ($nowGroup['once_max_day']) {
            $nowUser_today_count = $this->getOnceMaxDayBuyNumber($nowGroupId, $uid, $specificationsId);

            $today_can_buy = $nowGroup['once_max_day'] - $nowUser_today_count;
            if ($today_can_buy <= 0 || $today_can_buy < $num) {
                throw new \think\Exception(L_("该商品限制单人每天只能购买X1份，您当天购买的数量已达上限，不能再购买!", array("X1" => $nowGroup['once_max_day'])), 1003);
            }
        }

        return true;
    }

    /**
     * 得到一个用户今天买了多少份同一个团购
     * @param $data array 数据
     * @return string
     */
    public function getOnceMaxDayBuyNumber($nowGroupId, $uid, $specificationsId = 0, $time = true)
    {

        $where = [
            ['group_id', '=', $nowGroupId],
            ['uid', '=', $uid],
            ['paid', '=', 1],
            ['status', '<', 3],
        ];

        // 带规格的规格id
        if ($specificationsId && $specificationsId > 0) {
            $where[] = ['specifications_id', '=', $specificationsId];
        }

        if ($time) {
            $todayZero = strtotime(date('Y-m-d', time()));
            $where[] = ['pay_time', 'between', [$todayZero, $todayZero + 86400]];
        }

        // 用户已购买此商品的总数
        $count = (new GroupOrderService())->getOne($where, 'sum(num) as num')['num'] ?: 0;
        return $count;
    }

    /**
     * 根据商品id得到拼团商品的正在拼团列表-固定条数不带分页
     * @param array $nowGroupGoods 团购商品详情
     * @param int $limit 获取几条数据
     * @return array
     */
    public function getPinLimitListByGroupGoods($nowGroupGoods, $limit = 2)
    {
        // 用户信息
        $user = request()->user ?? [];

        // 团购id
        $nowGroupId = $nowGroupGoods['group_id'] ?? 0;

        // 排序
        $order = [
            'need_num' => 'ASC',
            'g.start_time' => 'ASC',
        ];

        // 查询自己正在参与的拼团
        $myJoinList = [];
        if ($user) {
            $where = [
                ['o.group_id', '=', $nowGroupId],
                ['o.paid', '=', 1],
                ['buy.uid', '=', $user['uid']],
                ['g.start_time', '>', time() - $nowGroupGoods['pin_effective_time'] * 3600],
            ];
            $myJoinList = $this->getPinOrderList($where, '', $order, 1, $limit);
            if ($myJoinList) {
                foreach ($myJoinList as &$self_pin) {
                    $self_pin['share_url'] = '' . '&gid=' . $self_pin['id'];// TODO
                    $self_pin['is_self'] = 1;
                    $self_pin['price'] = $nowGroupGoods['price'];
                    $self_pin['end_time'] = date('Y-m-d H:i:s', $self_pin['start_time'] + $nowGroupGoods['pin_effective_time'] * 3600);//拼团结束时间
                    $self_pin['surplus_time'] = $nowGroupGoods['pin_effective_time'] * 3600 - time() + $self_pin['start_time'];//拼团剩余时间

                }
            }
            if (count($myJoinList) >= $limit) {//已查询足够的条数直接返回
                $returnArr['list'] = $myJoinList;
                return $returnArr;
            }
            $limit = $limit - count($myJoinList);

        }

        // 查询可参加的拼团
        $where = [
            ['o.group_id', '=', $nowGroupId],
            ['o.paid', '=', 1],
            ['g.start_time', '>', time() - $nowGroupGoods['pin_effective_time'] * 3600],
        ];
        if ($myJoinList) {
            $joinOrderIds = array_column($myJoinList, 'order_id');
            $where[] = ['o.order_id', 'not in', implode(',', $joinOrderIds)];

        }
        $list = $this->getPinOrderList($where, '', $order, 1, $limit);
        if ($list) {
            foreach ($list as &$pin) {
                $pin['is_self'] = 0;
                $pin['price'] = $nowGroupGoods['price'];
                $pin['share_url'] = '';
                $pin['end_time'] = date('Y-m-d H:i:s', $pin['start_time'] + $nowGroupGoods['pin_effective_time'] * 3600);//拼团结束时间
                $pin['surplus_time'] = $nowGroupGoods['pin_effective_time'] * 3600 - time() + $pin['start_time'];//拼团剩余时间
            }
        }


        $returnArr['list'] = array_merge($myJoinList, $list);
        return $returnArr;
    }

    /**
     * 根据商品id得到拼团商品的正在拼团列表
     * @param array $nowGroupId 团购id
     * @param int $limit 获取几条数据
     * @param int $page 当前页数默认1
     * @return array
     */
    public function getPinOrderListByGroupId($nowGroupId, $page = 1, $limit = 0)
    {
        // 用户信息
        $user = request()->user ?? [];

        // 团购详情
        $nowGroupGoods = (new GroupService())->getOne(['group_id' => $nowGroupId], 'pin_effective_time,price');

        // 排序
        $order = [
            'need_num' => 'ASC',
            'g.start_time' => 'ASC',
        ];

        // 查询自己正在参与的拼团
        $myJoinList = [];
        if ($user && $page == 1) {
            $where = [
                ['o.group_id', '=', $nowGroupId],
                ['o.paid', '=', 1],
                ['buy.uid', '=', $user['uid']],
                ['g.start_time', '>', time() - $nowGroupGoods['pin_effective_time'] * 3600],
            ];
            $myJoinList = $this->getPinOrderList($where, '', $order);
            if ($myJoinList) {
                foreach ($myJoinList as &$self_pin) {// TODO
                    $self_pin['share_url'] = '' . '&gid=' . $self_pin['id'];
                    $self_pin['price'] = $nowGroupGoods['price'];
                    $self_pin['is_self'] = 1;
                    $self_pin['end_time'] = date('Y-m-d H:i:s', $self_pin['start_time'] + $nowGroupGoods['pin_effective_time'] * 3600);//拼团结束时间
                    $self_pin['surplus_time'] = $nowGroupGoods['pin_effective_time'] * 3600 - time() + $self_pin['start_time'];//拼团剩余时间
                }
            }

        }

        // 查询可参加的拼团
        $where = [
            ['o.group_id', '=', $nowGroupId],
            ['o.paid', '=', 1],
            ['g.start_time', '>', time() - $nowGroupGoods['pin_effective_time'] * 3600],
        ];
        if ($myJoinList) {
            $joinOrderIds = array_column($myJoinList, 'order_id');
            $where[] = ['o.order_id', 'not in', implode(',', $joinOrderIds)];

        }
        $list = $this->getPinOrderList($where, '', $order, $page, $limit);
        if ($list) {
            foreach ($list as &$pin) {
                $pin['is_self'] = 0;
                $pin['price'] = $nowGroupGoods['price'];
                $pin['share_url'] = '';
                $pin['end_time'] = date('Y-m-d H:i:s', $pin['start_time'] + $nowGroupGoods['pin_effective_time'] * 3600);//拼团结束时间
                $pin['surplus_time'] = $nowGroupGoods['pin_effective_time'] * 3600 - time() + $pin['start_time'];//拼团剩余时间
            }
        }

        $returnArr['list'] = array_merge($myJoinList, $list);
        $returnArr['page_size'] = $limit;
        return $returnArr;
    }
    
    public function getPinOrderByOrderId($orderId, $uid)
    {
        $order = GroupOrder::where('order_id', $orderId)
            ->where('paid',1)
            ->field('group_id, mer_id, is_head')
            ->find();
        
        empty($order) && throw_exception('当前拼团订单不存在！');
        
        $group = Group::where('group_id', $order->group_id)
            ->field('group_id, name, price, old_price, pic, pin_effective_time, begin_time, sale_count')
            ->find();
        
        $buyInfo = GroupBuyerList::where('order_id', $orderId)
            ->where('uid', $uid)
            ->find();
        
        $groupStart = GroupStart::where('id',$buyInfo->fid)
            ->field('uid, num, complete_num, status, start_time')
            ->find();
        
        $buyerList = app(GroupBuyerList::class)->getBuyerListByJoin([
            'fid' => $buyInfo->fid
        ],['u.uid','u.avatar']);
        foreach ($buyerList as $key=>$item){
            $buyerList[$key]['avatar'] = $item['avatar']?replace_file_domain($item['avatar']):'';
        }
        
        $group['detail_url']  = cfg('site_url')."/wap.php?c=Groupnew&a=order_detail&order_id={$orderId}";

        $group['end_time'] = date('Y-m-d H:i:s', $groupStart['start_time'] + $group['pin_effective_time'] * 3600);//拼团结束时间
        $group['surplus_time'] = $groupStart['start_time'] + $group['pin_effective_time'] * 3600 - time();//拼团剩余时间
        $group['surplus_time'] = $group['surplus_time']>0?$group['surplus_time']:0;
         
        return compact('group', 'groupStart', 'buyerList');
    }

    /*
     * 计算出用户团购规格下当前可购买数量
     * @param int $nowGroupId 团购id
     * @param array $specificationsInfo 规格详情
     * @param int $uid 用户id
     * @return array
     */
    public function getSpecBuyNum($nowGroupId, $specificationsInfo, $uid)
    {

        $returnArr = ['num' => 0, 'desc' => ''];
        //当前剩余库存
        if ($specificationsInfo['count_num'] >= 0) {
            $surplus = $specificationsInfo['count_num'];
        } else {
            $surplus = -1;
            $returnArr['num'] = 0;
        }

        //每ID每天限购
        if ($specificationsInfo['once_max_day']) {
            // 获取今日 用户已经购买的数量
            $nowUserTodayCount = $this->getOnceMaxDay($nowGroupId, $uid, $specificationsInfo['specifications_id'], true);
            $todayCanBuy = intval($specificationsInfo['once_max_day']) - intval($nowUserTodayCount);
            if ($todayCanBuy <= 0) {
                // 将今日可够该规格数量固定为0
                $returnArr['num'] = 0;
                $returnArr['desc'] = L_('每个用户每天最多购买X1份', $specificationsInfo['once_max_day']);
                // 每次最少购买数量
                if ($specificationsInfo['once_min'] > 1) {
                    $returnArr['desc'] .= '，' . L_('每个用户每次最少购买X1份', $specificationsInfo['once_min']);
                }
                return $returnArr;

            } else {
                $returnArr['desc'] = L_('每个用户每天最多购买X1份', $specificationsInfo['once_max_day']);
            }
        }

        //每ID限购
        if ($specificationsInfo['once_max'] && $specificationsInfo['once_max'] > 0) {
            // 获取用户已经购买的总数量
            $nowUser_count = $this->getOnceMaxDay($nowGroupId, $uid, $specificationsInfo['specifications_id'], false);
            $can_buy_total = $specificationsInfo['once_max'] - $nowUser_count;
            if ($can_buy_total <= 0) {
                // 将今日可够该规格数量固定为0
                $returnArr['num'] = 0;
                $returnArr['desc'] = L_('每个用户最多购买X1份', $specificationsInfo['once_max']);
                return $returnArr;
            } else {
                $returnArr['desc'] = ($returnArr['desc'] ? $returnArr['desc'] . '，' : '') . L_('每个用户最多购买X1份', $specificationsInfo['once_max']);
            }
            $todayCanBuy = min($todayCanBuy, $can_buy_total);
        }

        if (isset($todayCanBuy)) {
            // 今日可购买数量
            if ($surplus >= 0) {
                $todayCanBuy = min($todayCanBuy, $surplus);
            }
        } else {
            if ($specificationsInfo['count_num'] > 0 && $surplus <= 0) {
                // 将今日可够该规格数量固定为0
                $returnArr['num'] = 0;
                return $returnArr;
            } elseif ($specificationsInfo['count_num'] >= 0) {
                $todayCanBuy = $surplus;
            } else {
                $todayCanBuy = -1;
            }
        }
        if ($specificationsInfo['once_min'] > 1) {
            $returnArr['desc'] = ($returnArr['desc'] ? $returnArr['desc'] . '，' : '') . L_('每个用户每次最少购买X1份', $specificationsInfo['once_min']);
        }
        $returnArr['num'] = $todayCanBuy;
        return $returnArr;

    }

    /*
     * 得到一个用户今天买了多少份同一个团购
     * @param int $nowGroupId 团购id
     * @param array $specificationsInfo 规格详情
     * @param int $uid 用户id
     * @return array
     */
    public function getOnceMaxDay($nowGroupId, $uid, $specificationsId = 0, $time = true)
    {
        $where = [];
        $where[] = ['group_id', '=', $nowGroupId];
        if ($specificationsId && $specificationsId > 0) {
            $where[] = ['specifications_id', '=', $specificationsId];
        }
        $where[] = ['uid', '=', $uid];
        $where[] = ['paid', '=', 1];
        $where[] = ['status', '<', 3];
        if ($time) {
            $todayZero = strtotime(date('Y-m-d', time()));
            $where[] = ['pay_time', 'between', [$todayZero, $todayZero + 86400]];
        }
        $onceMaxDay = $this->groupOrderModel->where($where)->sum('num');
        return $onceMaxDay;
    }


    /**
     *得到拼团商品的正在拼团列表
     * @param array $where 查询条件
     * @param array $field 查询字段
     * @param array $order 排序
     * @param array $page 页码
     * @param array $limit 每页显示数量 0为查询所有
     * @return array
     */
    public function getPinOrderList($where = [], $field = true, $order = [], $page = 1, $limit = 0)
    {
        $result = $this->groupOrderModel->getPinOrderList($where, $field, $order, $page, $limit);

        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function getGroupCombineOrderCountByJoin($where)
    {
        $count = $this->groupOrderModel->getGroupCombineOrderCountByJoin($where);
        if (!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->groupOrderModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        $this->addSystemOrder(['order_id' => $id], 0);
        return $id;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($data) || empty($where)) {
            return false;
        }
        $result = $this->groupOrderModel->updateThis($where, $data);


        $this->addSystemOrder($where, 1);
        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return [];
        }

        $result = $this->groupOrderModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        try {
            $result = $this->groupOrderModel->getSome($where, $field, $order, $page, $limit);
//            var_dump($this->groupOrderModel->getLastSql());
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取数据数量
     * @param $where array
     * @return array
     */
    public function getCount($where)
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->groupOrderModel->getCount($where);
        if (empty($result)) {
            return 0;
        }

        return $result;
    }


    /**
     *获取订单商品数量
     * @param $where array
     * @return array
     */
    public function getGoodsNum($where){
        if(empty($where)){
            return false;
        }

        $result = $this->groupOrderModel->field('sum(num) as count')->where($where)->find();
        if(empty($result)){
            return 0;
        }

        return $result['count'];
    }

    /**
     * 获得订单列表
     * @param $data array 数据
     * @return array
     */
    public function getGroupOrderList($param,$is_export=0)
    {
        // 排序   
        if (empty($order)) {
            $order = ['o.order_id' => 'DESC'];
        }
        // 筛选条件
        $where[] = ['o.mer_id', '=', $param['mer_id']];
        // 时间筛选
        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $begin_time = strtotime($param['begin_time'] . "00:00:00");
            $end_time = strtotime($param['end_time'] . "23:59:59");
            if ($param['is_time']) {
                $where[] = ['o.pay_time', '>=', $begin_time];
                $where[] = ['o.pay_time', '<=', $end_time];
            } else {
                $where[] = ['o.add_time', '>=', $begin_time];
                $where[] = ['o.add_time', '<=', $end_time];
            }
        }
        // 类型筛选
        if (intval($param['is_type']) > -1) {
            $where[] = ['o.tuan_type', '=', intval($param['is_type'])];
        }
        // 消费状态
        if (intval($param['status']) > -1) {
            if($param['status']==6){
                $where[] = ['o.paid', '=', 0];
            }elseif($param['status']==0){
                $where[] = ['o.status', '=', intval($param['status'])];
                $where[] = ['o.paid', '=', 1];
            }else{
                $where[] = ['o.status', '=', intval($param['status'])];
            }
        }
        // 支付类型
        if (!empty($param['is_pay'])) {
            if($param['is_pay']=='balance'){
                $where[] = ['o.balance_pay', '>', 0];
            }else{
                $where[] = ['o.pay_type', '=', $param['is_pay']];
            }
        }
        // 综合查询
        if (!empty($param['text'])) {
            if ($param['is_compre'] == 0) {
                // 订单编号
                $where[] = ['o.real_orderid', 'like', '%' . $param['text'] . '%'];
            } elseif ($param['is_compre'] == 1) {
                // 支付流水号
                $orderId = (new PayOrderInfo())->where(array('orderid'=>$param['text'],'business'=>'group'))->value('business_order_id');
                $where[] = ['o.order_id', '=', ($orderId ?: '')];
            } elseif ($param['is_compre'] == 2) {
                // 第三方支付流水号
                $where[] = ['o.third_id', 'like', '%' . $param['text'] . '%'];
            } elseif ($param['is_compre'] == 3) {
                // 团购名称
                $where[] = ['g.s_name|o.order_name', 'like', '%' . $param['text'] . '%'];
            } elseif ($param['is_compre'] == 4) {
                // 客户名称
                $where[] = ['u.nickname', 'like', '%' . $param['text'] . '%'];
            } elseif ($param['is_compre'] == 5) {
                // 客户电话
                $where[] = ['u.phone', 'like', '%' . $param['text'] . '%'];
            }
        }
        if(isset($param['group_id'])&&$param['group_id']){
            $where[] = ['o.group_id', '=', $param['group_id']];
        }

        // 分页
        $page = 0;
        $pageSize = 0;
        if(!$is_export){
            // 分页
            $page = empty($param['page']) ? 1 : $param['page'];
            $pageSize = empty($param['pageSize']) ? 10 : $param['pageSize'];
        }

        // 列表
        $orderList = $this->groupOrderModel->getGroupOrderList($where, $order, $page, $pageSize);
        if (!$orderList) {
            return [];
        }else{
            foreach ($orderList['list'] as $key=>$item){
                $orderList['list'][$key]['status'] = $item['paid']!=1?7:$item['status'];
            }
        }
        return $orderList;
    }

    /**
     * 获得订单详情
     * @param $data array 数据
     * @return array
     */
    public function getGoodsOrderDetail($where)
    {
        $result = $this->groupOrderModel->getGoodsOrderDetail($where);
        if (!empty($result)) {
            $pass_num = $result['pass_num'] > 1 ? intval($result['pass_num']) : 1; // 一份团购 对应生成核销券 份数
            $result['total_pass_num'] = $result['num'] * $pass_num; // 改订单产生的总 核销券 份数
            $where1 = [['order_id', '=', $result['order_id']], ['status', '=', 1]];
            $count = (new GroupPassRelation())->getCount($where1);//未核销
            if ($result['pass_array']) {
                $result['unconsume_pass_num'] = $result['total_pass_num'] - $count;
            } elseif ($result['tuan_type'] == 2) {
                $result['unconsume_pass_num'] = 0;
                $result['total_pass_num'] = 0;
            } elseif ($result['status'] == 0) {
                $result['unconsume_pass_num'] = 1;
            }
            if($result['group_pass']){
                $result['group_pass_txt'] = preg_replace('#(\d{4})#','$1 ',$result['group_pass']);
            }
            if($result['add_time']){
                $result['add_time']=date("Y-m-d H:i:s",$result['add_time']);
            }
            if($result['refund_detail'] && unserialize($result['refund_detail'])){
                $result['refund_detail'] = unserialize($result['refund_detail']);
                if (isset($result['refund_detail']['msg']) && isset($result['refund_detail']['error'])) {
                    $result['refund_detail']['msg'] = "错误码：".$result['refund_detail']['error'] . ', '. isset($result['refund_detail']['msg'])?$result['refund_detail']['msg']:"";
                }
            }elseif($result['refund_detail'] && !$result['refund_detail']['refund_id']){
                $result['refund_detail']['msg'] = str_replace('certificate not match','微信支付证书不匹配',$result['refund_detail']);
            }elseif($result['refund_detail'] && $result['refund_detail']['msg']){
                $result['refund_detail']['msg'] = str_replace('certificate not match','微信支付证书不匹配',isset($result['refund_detail']['msg'])?$result['refund_detail']['msg']:"");
            }elseif($result['refund_detail'] && $result['refund_detail']['err_msg']){
                $result['refund_detail']['err_msg'] = str_replace('certificate not match','微信支付证书不匹配',isset($result['refund_detail']['err_msg'])?$result['refund_detail']['err_msg']:"");
            }
            if($result['paid']){
                if($result['pay_type']=="offline" && empty($result['third_id'])){
                    $result['pay_msg']="线下支付;未付款";
                }elseif ($result['status'] == 7){
                    $result['pay_msg']="退款失败，原因：".(isset($result['refund_detail']['err_msg'])?$result['refund_detail']['err_msg']:"");
                }elseif ($result['status'] == 0){
                    $result['pay_msg']="已付款";
                    if($result['tuan_type'] != 2 && $result['is_pay_bill'] == 0){
                        $result['pay_msg'].="(未消费)";
                    }elseif($result['tuan_type'] != 2 && $result['is_pay_bill'] == 1){
                        $result['pay_msg'].="(部分消费)";
                    }else{
                        if($result['is_pick_in_store']){
                            $result['pay_msg'].="未取货";
                        }else{
                          if($result['express_id'] != ''){
                              $result['pay_msg'].="已发货";
                          }else{
                              $result['pay_msg'].="未发货";
                          }
                        }
                    }
                }elseif ($result['status'] == 1){
                    if($result['tuan_type'] != 2){
                        $result['pay_msg']="已消费";
                    }else{
                        if($result['is_pick_in_store']){
                            $result['pay_msg']="已取货";
                        }else{
                            $result['pay_msg']="已收货";
                        }
                    }
                    $result['pay_msg'].="(待评价)";
                }elseif ($result['status'] == 2){
                    $result['pay_msg']="已完成";
                }elseif($result['status'] == 3){
                    $result['pay_msg']="已退款";
                    if(!empty($result['refund_detail']['refund_time'])){
                        $result['pay_msg'].=",退款时间：".date("Y-m-d H:i:s",$result['refund_detail']['refund_time']);
                    }
                }elseif($result['status'] == 4){
                    $result['pay_msg']="已取消";
                }elseif($result['status'] == 5){
                    $result['pay_msg']="未完全消费";
                }elseif($result['status'] == 5){
                    $result['pay_msg']="已取消-部分退款";
                }elseif($result['status'] == 9){
                    $result['pay_msg']="已过期平台冻结";
                }
            }else{
                if($result['status'] == 4){
                    $result['pay_msg']="未付款且已取消";
                }else{
                    $result['pay_msg']="未付款";
                }
            }
            $result['internal_card_pay_money'] = isset($result['internal_card_pay'])?array_sum($result['internal_card_pay']):0;
            $result['is_hotel'] = 0;
            if($result['trade_info']){
                $trade_info_arr = unserialize($result['trade_info']);
                if($trade_info_arr['type'] == 'hotel'){
                    $trade_hotel_info = invoke_cms_model('Trade_hotel_category/format_order_trade_info', ['trade_info' => $result['trade_info']]);
                    if($trade_hotel_info['error_no']==0){
                        $result['is_hotel'] = 1;
                        $result['trade_hotel'] = $trade_hotel_info;
                    }
                }
            }
            $result['paymoney']= $result['total_money']-$result['card_price']-$result['coupon_price']-$result['wx_cheap']-$result['score_deducte'];
            //获取对应的券码列表
            $result['group_pass_list'] = (new GroupPassRelation())->where('order_id',$result['order_id'])->column('group_pass,status,staff_name,verify_time');
            foreach($result['group_pass_list'] as $key => $val){
                $result['group_pass_list'][$key]['verify_time_txt'] = $val['verify_time'] ? date('Y-m-d H:i:s', $val['verify_time']) : 0;
                if($val['status']>0&&!$val['staff_name']){
                    $result['group_pass_list'][$key]['staff_name'] = $result['staff_name']?:'';
                    $result['group_pass_list'][$key]['verify_time_txt'] = $result['verify_time']?date('Y-m-d H:i:s',$result['verify_time']):'';
                }
            }

            if((!$result['group_pass_list']||empty($result['group_pass_list']))&&$result['group_pass']){
                $pass_status = 0;
                if($result['verify_time']>0){
                    $pass_status = 1;
                }elseif($result['status']==3){
                    $pass_status = 2;
                }
                $result['group_pass_list'][] = [
                    'group_pass' => $result['group_pass'],
                    'status' => $pass_status,
                    'verify_time_txt' => $result['verify_time'] ? date('Y-m-d H:i:s', $result['verify_time']) : 0,
                    'staff_name' => $result['staff_name']?:'',
                ];
            }
            $result['phone'] = $result['is_pick_in_store']==1?$result['user_phone']:$result['phone'];
            $write_info = $result['write_field']?unserialize($result['write_field']):[];
            $result['write_list'] = $write_info;
            if(cfg('baodi_ticket_url')&&$result['userinput']){
                $result['userinput'] = $result['userinput']?unserialize($result['userinput']):[];
            }
            $result['userinput'] = $result['userinput']?:null;
        }

        if ($result['paid'] == 1) {
            //增加支付流水号
            $payinfo = (new \app\pay\model\db\PayOrderInfo())->where(array('business_order_id' => $result['order_id'], 'business' => 'group', 'paid' => 1))->find();
            $result['orderid'] = $payinfo->orderid ?? '';
            $result['third_id'] = $payinfo->paid_extra ?? '';
        }

        $assign['list']=$result;
        return $assign;
    }

    /**
     * 修改备注
     */
    public function noteInfo($note_info, $id)
    {
        if (empty($note_info)) {
            return false;
        }
        $result = $this->groupOrderModel->editNoteInfo($note_info, $id);
        if (empty($result)) {
            return 0;
        }
        return true;
    }

    /**
     * 获取店铺场次预约总人数
     * @param $storeId
     * @date: 2021/06/16
     */
    public function getBookingAppointCount($storeId)
    {
        $where = [
            ['o.store_id', '=', $storeId],
            ['o.paid', '=', 1],
            ['g.group_cate', '=', 'booking_appoint']
        ];
        return $this->groupOrderModel->alias('o')
            ->join('group g', 'g.group_id=o.group_id')
            ->where($where)
            ->count();
    }

    /**
     * 获取店铺场次预约总商品数
     * @param $where
     * @date: 2021/10/25
     */
    public function getBookingAppointSum($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->groupOrderModel
                    ->alias('o')
                    ->leftJoin($prefix.'group_booking_appoint_order b ','b.order_id = o.order_id')->where($where)->sum('o.num');

        return $count;
    }
    

    /**
     * 获取总数
     * @param $where
     * @date: 2021/10/25
     */
    public function getSum($where, $field="num")
    {
        $count = $this->groupOrderModel->where($where)->sum($field);
        return $count;
    }

    /**
     * 获得团购券列表
     * @param $data array 数据
     * @return array
     */
    public function getGroupCouponList($param,$is_export=0)
    {
        // 排序   
        if (empty($order)) {
            $order = ['gp.id' => 'DESC'];
        }
        // 筛选条件
        $where[] = ['o.mer_id', '=', $param['mer_id']];
        $where[] = ['o.tuan_type', '=', 0];
        //核销人
        if(!empty($param['staff_name'])){
            $where[] = ['gp.staff_name', 'like', '%' . $param['staff_name'] . '%'];
        }
        //店铺
        if(!empty($param['store_name'])){
            $merchantStoreIdAry = (new MerchantStore())->where([['name', 'like', '%' . $param['store_name'] . '%']])->column('store_id');
            if($merchantStoreIdAry){
                $where[] = ['o.store_id', 'IN', $merchantStoreIdAry];
            }else{
                $where[] = ['o.store_id', '=', -1];
            }
        }
        // 核销时间筛选
        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $begin_time = strtotime($param['begin_time'] . "00:00:00");
            $end_time = strtotime($param['end_time'] . "23:59:59");
            $where[] = ['gp.verify_time', '>=', $begin_time];
            $where[] = ['gp.verify_time', '<=', $end_time];
        }
        //核销方式
        if(!empty($param['verify_type'])){
            if($param['verify_type']==99){
                $where[] = ['o.tuan_type', '=', 0];
                $where[] = ['o.status', 'in', [1,2]];
                $where[] = ['', 'exp', Db::raw('gp.verify_type = 0 or o.staff_name = "系统自动核销"')];
            }else{
                $where[] = ['gp.verify_type', '=', $param['verify_type']];
            }
        }
        //状态
        $whereRaw = '';
        if(!empty($param['status'])){//状态
            if($param['status']==1){//待核销
                $whereRaw = "gp.status = 0 and ((g.effective_type=0 and g.deadline_time>=".time().")||(g.effective_type=1 and g.deadline_time*24*60*60+o.pay_time>=".time()."))";
            }elseif($param['status']==2){//已核销
                $where[] = ['gp.status', '=', 1];
            }elseif($param['status']==3){//已过期
                $whereRaw = "gp.status = 0 and ((g.effective_type=0 and g.deadline_time<".time().")||(g.effective_type=1 and g.deadline_time*24*60*60+o.pay_time<".time()."))";
            }elseif($param['status']==4){//已退款
                $where[] = ['gp.status', '=', 2];
            }
        }

        if(!empty($param['keyword'])){
            if ($param['select_type'] == 'group_pass') {
                $where[] = ['gp.group_pass', '=', htmlspecialchars($param['keyword'])];
            } elseif ($param['select_type'] == 'real_orderid') {
                $where[] = ['o.real_orderid', '=', htmlspecialchars($param['keyword'])];
            } elseif ($param['select_type'] == 'nickname') {
                $where[] = ['u.nickname', 'like', '%' .htmlspecialchars($param['keyword']). '%'];
            } elseif ($param['select_type'] == 'phone') {
                $where[] = ['u.phone', '=', htmlspecialchars($param['keyword'])];
            }
        }

        // 分页
        $page = 0;
        $pageSize = 0;
        if(!$is_export){
            // 分页
            $page = empty($param['page']) ? 1 : $param['page'];
            $pageSize = empty($param['pageSize']) ? 10 : $param['pageSize'];
        }

        // 列表
        $list = $this->groupOrderModel->getGroupCouponList($where, $order, $page, $pageSize,$whereRaw);
        if (!$list) {
            return [];
        }else{
            foreach ($list['list'] as $key=>$item){
                $status = $item['status'];
                $list['list'][$key]['verify_time'] = $item['verify_time'] ? date('Y-m-d H:i:s',$item['verify_time']) : '';
                $list['list'][$key]['status_color'] = '#444444';
                if($item['status'] == 1){
                    $list['list'][$key]['status_color'] = '#080';
                }elseif($item['status'] == 2){
                    $list['list'][$key]['status_color'] = '#F00';
                }
                $list['list'][$key]['can_verify'] = 0;
                if($item['status'] == 0){
                    $list['list'][$key]['can_verify'] = 1;
                    if(($item['effective_type'] == 0 && $item['deadline_time'] < time()) || ($item['effective_type'] == 1 && $item['deadline_time']*24*60*60+$item['pay_time'] < time())){
                        $list['list'][$key]['can_verify'] = 0;
                    }
                }
                $list['list'][$key]['status'] = (new GroupPassRelation())->getStatusMsg($item['status'],$item['deadline_time'],$item['effective_type'],$item['pay_time']);
                $list['list'][$key]['verify_type'] = (new GroupPassRelation())->getVerifyTypeMsg($item['verify_type']);
                //店鋪名称
                $list['list'][$key]['store_name'] = (new MerchantStore())->where('store_id',$item['store_id'])->value('name');
                $list['list'][$key]['coupon_type'] = cfg('group_alias_name').'券';
                if($item['num']==1&&$item['tuan_type']==0&&in_array($status,[1,2])){
                    if(!$list['list'][$key]['verify_time']){
                        $list['list'][$key]['verify_time'] = $item['o_verify_time'] ? date('Y-m-d H:i:s',$item['o_verify_time']) : '';
                    }
                    if(!$item['staff_name']){
                        $list['list'][$key]['staff_name'] = $item['o_staff_name'] ?: '';
                    }
                }
                if($item['tuan_type']==0&&in_array($status,[1,2])&&!$list['list'][$key]['verify_type']){
                    $list['list'][$key]['verify_type'] = '未知的核销方式';
                }
            }
        }
        return $list;
    }

    /**
     * 获得团购券详情
     * @param $data array 数据
     * @return array
     */
    public function getGoodsCouponDetail($order_id,$groupPassId)
    {
        $where=[['o.order_id','=',$order_id]];
        $result = $this->groupOrderModel->getGoodsOrderDetail($where);
        if (!empty($result)) {
            $pass_num = $result['pass_num'] > 1 ? intval($result['pass_num']) : 1; // 一份团购 对应生成核销券 份数
            $result['total_pass_num'] = $result['num'] * $pass_num; // 改订单产生的总 核销券 份数
            $where1 = [['order_id', '=', $result['order_id']], ['status', '=', 1]];
            $count = (new GroupPassRelation())->getCount($where1);//未核销
            if ($result['pass_array']) {
                $result['unconsume_pass_num'] = $result['total_pass_num'] - $count;
            } elseif ($result['tuan_type'] == 2) {
                $result['unconsume_pass_num'] = $result['total_pass_num'];
            } elseif ($result['status'] == 0) {
                $result['unconsume_pass_num'] = 1;
            }
            if($result['add_time']){
                $result['add_time']=date("Y-m-d H:i:s",$result['add_time']);
            }
            if($result['paid']){
                if($result['pay_type']=="offline" && empty($result['third_id'])){
                    $result['pay_msg']="线下支付;未付款";
                }elseif ($result['status'] == 7){
                    $result['pay_msg']="退款失败，原因：".(isset($result['refund_detail']['err_msg'])?$result['refund_detail']['err_msg']:"");
                }elseif ($result['status'] == 0){
                    $result['pay_msg']="已付款";
                    if($result['tuan_type'] != 2 && $result['is_pay_bill'] == 0){
                        $result['pay_msg'].="(未消费)";
                    }elseif($result['tuan_type'] != 2 && $result['is_pay_bill'] == 1){
                        $result['pay_msg'].="(部分消费)";
                    }else{
                        if($result['is_pick_in_store']){
                            $result['pay_msg'].="未取货";
                        }else{
                            if($result['express_id'] != ''){
                                $result['pay_msg'].="已发货";
                            }else{
                                $result['pay_msg'].="未发货";
                            }
                        }
                    }
                }elseif ($result['status'] == 1){
                    if($result['tuan_type'] != 2){
                        $result['pay_msg']="已消费";
                    }else{
                        if($result['is_pick_in_store']){
                            $result['pay_msg']="已取货";
                        }else{
                            $result['pay_msg']="已收货";
                        }
                    }
                    $result['pay_msg'].="(待评价)";
                }elseif ($result['status'] == 2){
                    $result['pay_msg']="已完成";
                }elseif($result['status'] == 3){
                    $result['pay_msg']="已退款";
                    if(!empty($result['refund_detail']['refund_time'])){
                        $result['pay_msg'].=",退款时间：".date("Y-m-d H:i:s",$result['refund_detail']['refund_time']);
                    }
                }elseif($result['status'] == 4){
                    $result['pay_msg']="已取消";
                }elseif($result['status'] == 5){
                    $result['pay_msg']="未完全消费";
                }elseif($result['status'] == 5){
                    $result['pay_msg']="已取消-部分退款";
                }elseif($result['status'] == 9){
                    $result['pay_msg']="已过期平台冻结";
                }
            }else{
                if($result['status'] == 4){
                    $result['pay_msg']="未付款且已取消";
                }else{
                    $result['pay_msg']="未付款";
                }
            }
            
            //获取对应的券码信息
            $groupPassInfo = (new GroupPassRelation())->where('id',$groupPassId)->field('staff_name,verify_time,group_pass,id,status')->find();
            $result['staff_name'] = $groupPassInfo['staff_name']??'';
            $result['verify_time'] = $groupPassInfo['verify_time']??'';
            $result['group_pass'] = $groupPassInfo['group_pass']??'';
            $result['status_msg'] = (new GroupPassRelation())->getStatusMsg($groupPassInfo['status'],$result['deadline_time'],$result['effective_type'],$result['pay_time']);
            $result['deadline'] = $result['effective_type'] ? date('Y-m-d H:i:s',$result['deadline_time']*24*60*60+$result['pay_time']) : date('Y-m-d H:i:s',$result['deadline_time']);
            $result['can_verify'] = 0;
            if($groupPassInfo['status'] == 0){
                $result['can_verify'] = 1;
                if(($result['effective_type'] == 0 && $result['deadline_time'] < time()) || ($result['effective_type'] == 1 && $result['deadline_time']*24*60*60+$result['pay_time'] < time())){
                    $result['can_verify'] = 0;
                }
            }
        }

        $assign['list']=$result;
        return $assign;
    }

    /**
     * 团购券验证
     */
    public function couponVerify($order_id,$groupPassId)
    {
        $now_order = $this->groupOrderModel->getGoodsOrderDetail([['o.order_id','=',$order_id]]);
        $verify_all = false;
        if(empty($now_order)){
            throw_exception(L_('当前订单不存在！'));
        }else{
            if(empty($now_order['paid'])){
                throw_exception(L_('此订单尚未支付！'));
            }
            if($now_order['status']!=0 && $now_order['status']!=7){
                throw_exception(L_('此订单尚不是未消费！'));
            }
            if($now_order['is_head'] || $now_order['pin_fid']>0){
                $group_start = (new GroupStartService())->getGroupStartByOrderId($now_order['order_id']);
                $group_start_status  = $group_start['status'];
                if ($group_start_status == 0) {
                    throw_exception(L_('此订单尚未成团！'));
                } elseif ($group_start_status == 2) {
                    throw_exception(L_('此订单超过有效期，团购作废！'));
                }
            }

            $where = ['order_id'=>$now_order['order_id'],'id'=>$groupPassId];
            $res = (new GroupPassRelation())->where($where)->find();
            if($res){
                $date['status']=1;
                $date['staff_name']='商家自主核销';
                $date['verify_time'] = time();
                $date['verify_type'] = 2;
                if((new GroupPassRelation())->where($where)->update($date)){
                    $count = (new GroupPassRelation())->getPassNum($where['order_id']);
                    $count += (new GroupPassRelation())->getPassNum($where['order_id'],3);

                    if($count==0){
                        if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
                            $data_group_order['third_id'] = $now_order['order_id'];
                        }
                        $data_group_order['status'] = '1';
                        $verify_all = true;
                    }else{
                        $now_order['res'] = $res;
                    }
                    $condition_group_order['order_id'] = $where['order_id'];
                    $data_group_order['use_time'] = time();
                    $data_group_order['last_staff'] = '商家自主核销';
                    $data_group_order['last_random'] = mt_rand(100000000,999999999);
                    if ($now_order['status'] == 7 && !$verify_all) {
                        $data_group_order['status'] = 0;
                    }
                    $save_msg = (new GroupOrder())->where($condition_group_order)->update($data_group_order);
                    if($save_msg){
                        //验证增加商家余额
                        $now_order['order_type'] = 'group';
                        $now_order['verify_all'] = 0;
                        $now_order['desc']=L_('验证团购订单X1的消费码',$now_order['real_orderid']).'</br>'.$res['group_pass'].L_('记入收入');
                        $this->billOrder($now_order);
                        $this->groupNotice($now_order, $verify_all);
                    }else{
                        $date['status'] = 0;
                        (new GroupPassRelation())->where($where)->update($date);
                        throw_exception(L_('验证失败！请重试。'));
                    }
                }else{
                    throw_exception(L_('验证消费失败！'));
                }
            }else{
                throw_exception(L_('此消费码不存在！'));
            }
        }
        return true;
    }
    
    /**
     * 导出团购列表
     */
    public function exportGoodsCouponList($param)
    {
        $returnArr = $this->getGroupCouponList($param,1);
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '核销时间');
        $worksheet->setCellValueByColumnAndRow(2, 1, '券名称');
        $worksheet->setCellValueByColumnAndRow(3, 1, '券序列码');
        $worksheet->setCellValueByColumnAndRow(4, 1, '状态');
        $worksheet->setCellValueByColumnAndRow(5, 1, '店铺名称');
        $worksheet->setCellValueByColumnAndRow(6, 1, '卡券订单号');
        $worksheet->setCellValueByColumnAndRow(7, 1, '券类型');
        $worksheet->setCellValueByColumnAndRow(8, 1, '用户昵称');
        $worksheet->setCellValueByColumnAndRow(9, 1, '手机号');
        $worksheet->setCellValueByColumnAndRow(10, 1, '核销方式');
        $worksheet->setCellValueByColumnAndRow(11, 1, '核销人');


        //设置单元格样式
        $worksheet->getStyle('A1:K1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:K')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');

        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(42);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(35);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);

        $len = $returnArr['total'];
        $j = 0;
        $row = 0;
        $i = 0;
        foreach ($returnArr['list'] as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $val['verify_time']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $val['name']);
                $worksheet->setCellValueByColumnAndRow(3, $j, "\t".$val['group_pass']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $val['status']);
                $worksheet->setCellValueByColumnAndRow(5, $j, $val['store_name']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $val['real_orderid']);
                $worksheet->setCellValueByColumnAndRow(7, $j, cfg('group_alias_name').'券');
                $worksheet->setCellValueByColumnAndRow(8, $j, removeEmojiChar($val['nickname']));
                $worksheet->setCellValueByColumnAndRow(9, $j, $val['phone']);
                $worksheet->setCellValueByColumnAndRow(10, $j, $val['verify_type']);
                $worksheet->setCellValueByColumnAndRow(11, $j, $val['staff_name']);
                $i++;
            }

            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:K' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-券码管理.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        $downFileUrl = cfg('site_url') . '/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl, 'file_name' => $downFileUrl];
    }
}