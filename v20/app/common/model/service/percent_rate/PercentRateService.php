<?php
/**
 * 抽成比例
 * User: wanziyang
 * Date: 2020/06/15
 */

namespace app\common\model\service\percent_rate;

use app\appoint\model\service\AppointService;
use app\common\model\db\Merchant;
use app\foodshop\model\service\order\DiningOrderService;
use app\group\model\service\MarketGoodsService;
use app\mall\model\db\MallGoods;
use app\mall\model\service\MallGoodsService;
use app\merchant\model\service\MerchantService;

use app\common\model\db\StorePercentRate;
use app\common\model\db\MerchantPercentRate;
use app\common\model\db\PercentDetail;
use app\common\model\db\PercentDetailByType;
use app\common\model\db\PercentDetailByStoreType;
use app\common\model\service\user\SpecialDayScoreTimesService;
use app\common\model\service\UserService;
use app\group\model\service\GroupService;
use app\market\model\service\MarketGoodsService as ServiceMarketGoodsService;
use app\merchant\model\service\spread\MerchantPercentRateService;
use app\shop\model\service\goods\ShopGoodsService;

class PercentRateService
{
    /**
     * 获取抽成比例
     * @author: wanziyang
     * @date_time: 2020/6/15 9:50
     * @param integer|string $merId 商家id
     * @param string $type 业务类型
     * @param float $money 金额
     * @param string|integer $groupId 业务相关id
     * @param string|integer $store_id 店铺id
     * @param bool $is_relation 判断依据
     * @return int|string
     */
    public function getPercentRate($merId, $type, $money, $groupId='', $store_id='', $is_relation=false) {
        if(cfg('is_open_merchant_foodshop_discount')==1 && ($type=='foodshop'||$type=='meal')){
            $where = array('real_orderid'=>$groupId);
            $service_foodshop_order = new DiningOrderService();
            $foodshop_order = $service_foodshop_order->getOrderByCondition($where);
            return $foodshop_order['mer_table_scale'];
        }
        if($type=='village_group'){
            return 0;
        }
        if($type=='award'){
            return 0;
        }
        if($type == 'yydb' || $type == 'coupon'){
            $type = 'activity';
        }

        if($type == 'dining'){//餐饮2.0
            $type = 'meal';
        }

        if(cfg('open_meal_scan_percent')==0 && $type=='meal_scan'){
            $type='meal';
        }
        if($groupId>0 && cfg('open_group_percent')==1){
//            $service_group = new MarketGoodsService();
//            $nowGroup = $service_group->getGroupOne(['group_id'=>$groupId]);
            $nowGroup = (new GroupService)->getOne(array('group_id' => $groupId));
            if($nowGroup['percent']>=0){
                return $nowGroup['percent'];
            }
        }
        if(cfg('open_store_percent_single')==1 && ($type=='store'||$type=='cash')){
            $service_merchant = new MerchantService();
            $now_mer = $service_merchant->getMerchantByMerId($merId);
            if($now_mer['store_percent_single']>0){
                if( $now_mer['store_percent_single_type']==1){
                    return $now_mer['store_percent_single'];
                }else{
                    return $now_mer['store_percent_single']*$money/100;
                }
            }else if( cfg('store_percent_single_value')>0){
                if( cfg('store_percent_single_type')==1){
                    return cfg('store_percent_single_value');
                }else{
                    return cfg('store_percent_single_value')*$money/100;
                }
            }

        }

        if (!$is_relation || !$store_id) {
            if($store_id){
                $where_rate['store_id'] = $store_id;
                $db_store_percent_rate = new StorePercentRate();
                $now_mer_pr = $db_store_percent_rate->getOne($where_rate);
                if (!$now_mer_pr || $now_mer_pr->isEmpty()) {
                    $now_mer_pr = [];
                } else {
                    $now_mer_pr = $now_mer_pr->toArray();
                }
            }else{
                $where_rate['mer_id'] = $merId;
                $db_merchant_percent_rate = new MerchantPercentRate();
                $now_mer_pr = $db_merchant_percent_rate->getRateData($where_rate);
                if (!$now_mer_pr || $now_mer_pr->isEmpty()) {
                    $now_mer_pr = [];
                } else {
                    $now_mer_pr = $now_mer_pr->toArray();
                }
            }

            if ($now_mer_pr) {
                if (isset($now_mer_pr[$type . '_percent']) && $now_mer_pr[$type . '_percent'] >= 0 && $now_mer_pr[$type .'_percent']!='') {
                    $percent = $this->percentDetail($merId,$type,'mer_type',$money,$store_id);
                    if(empty($percent)){
                        return $now_mer_pr[$type . '_percent'];
                    }else{
                        return $percent;
                    }
                } elseif (isset($now_mer_pr['merchant_percent']) && $now_mer_pr['merchant_percent'] >= 0 &&$now_mer_pr['merchant_percent']!='') {
                    $percent = $this->percentDetail($merId,$type,'merchant',$money,$store_id);

                    if(empty($percent)){
                        return $now_mer_pr['merchant_percent'];
                    }else{
                        return $percent;
                    }
                } elseif (isset($now_mer_pr['merchant_store_percent']) && $now_mer_pr['merchant_store_percent'] >= 0 &&$now_mer_pr['merchant_store_percent']!='') {
                    $percent = $this->percentDetail($merId,$type,'merchant_store',$money,$store_id);

                    if(empty($percent)){
                        return $now_mer_pr['merchant_store_percent'];
                    }else{
                        return $percent;
                    }
                }elseif ( cfg('open_meal_scan_percent')==1 && $type=='meal_scan' && cfg('meal_scan_percent') >= 0 ) {

                    return cfg('' . $type . '_percent');

                }elseif ( cfg('' . $type . '_percent') >= 0 || ($type=='meal_scan' && cfg('meal_percent') >= 0 ) ) {
                    if($type=='meal_scan'){
                        $type='meal';
                    }
                    $percent = $this->percentDetail($merId,$type,'sys_type',$money,$store_id);
                    if(empty($percent)){
                        return cfg('' . $type . '_percent');

                    }else{

                        return $percent;
                    }
                } elseif ( cfg('platform_get_merchant_percent') >= 0) {
                    $percent = $this->percentDetail($merId,$type,'system',$money,$store_id);
                    if(empty($percent)){
                        return cfg('platform_get_merchant_percent');
                    }else{
                        return $percent;
                    }
                } else {
                    return 0;
                }
            } else {
                $merchant_percent = (new MerchantPercentRate())->where(['mer_id'=>$merId])->findOrEmpty()->toArray();
                if ($merchant_percent&&isset($merchant_percent[$type . '_percent'])&&$merchant_percent[$type . '_percent']>0){
                    return $merchant_percent[$type . '_percent'];
                } elseif ($merchant_percent && $merchant_percent['merchant_percent'] >= 0 && $merchant_percent['merchant_percent'] != '') {
                    return $merchant_percent['merchant_percent'];
                } elseif (cfg('' . $type . '_percent') >= 0) {
                    return cfg('' . $type . '_percent');
                } elseif (cfg('platform_get_merchant_percent') >= 0) {
                    return cfg('platform_get_merchant_percent');
                } else {
                    return 0;
                }
            }
        } elseif($is_relation && $store_id>0){
            $where_rate['store_id'] = $store_id;
            $db_store_percent_rate = new StorePercentRate();
            $now_store_pr = $db_store_percent_rate->getOne($where_rate);
            if (!$now_store_pr || $now_store_pr->isEmpty()) {
                $now_store_pr = [];
            } else {
                $now_store_pr = $now_store_pr->toArray();
            }

            $where_mer_rate['mer_id'] = $merId;
            $db_merchant_percent_rate = new MerchantPercentRate();
            $now_mer_pr = $db_merchant_percent_rate->getRateData($where_mer_rate);
            if (!$now_mer_pr || $now_mer_pr->isEmpty()) {
                $now_mer_pr = [];
            } else {
                $now_mer_pr = $now_mer_pr->toArray();
            }
            if ($now_mer_pr || $now_store_pr) {
                if ($now_store_pr && $now_store_pr[$type . '_percent'] >= 0 && $now_store_pr[$type .'_percent']!='') {
                    $percent = $this->percentDetail($merId,$type,'mer_type',$money,$store_id);
                    if(empty($percent)){
                        return $now_store_pr[$type . '_percent'];
                    }else{
                        return $percent;
                    }
                } elseif ($now_store_pr && $now_store_pr['merchant_store_percent'] >= 0 &&$now_store_pr['merchant_store_percent']!='') {
                    $percent = $this->percentDetail($merId,$type,'merchant_store',$money,$store_id);
                    if(empty($percent)){
                        return $now_store_pr['merchant_store_percent'];
                    }else{
                        return $percent;
                    }
                } elseif ($now_mer_pr && $now_mer_pr[$type . '_percent'] >= 0 && $now_mer_pr[$type .'_percent']!='') {
                    $percent = $this->percentDetail($merId,$type,'mer_type',$money,0);
                    if(empty($percent)){
                        return $now_mer_pr[$type . '_percent'];
                    }else{
                        return $percent;
                    }
                } elseif ($now_mer_pr['merchant_percent'] >= 0 &&$now_mer_pr['merchant_percent']!='') {
                    $percent = $this->percentDetail($merId,$type,'merchant',$money,$store_id);

                    if(empty($percent)){
                        return $now_mer_pr['merchant_percent'];
                    }else{
                        return $percent;
                    }
                } elseif ( cfg('open_meal_scan_percent')==1 && $type=='meal_scan' && cfg('meal_scan_percent') >= 0 ) {

                    return cfg('' . $type . '_percent');

                }elseif ( cfg('' . $type . '_percent') >= 0 || ($type=='meal_scan' && cfg('meal_percent') >= 0 ) ) {
                    if($type=='meal_scan'){
                        $type='meal';
                    }
                    fdump('1111111111','percent_rate',1);
                    fdump(cfg('' . $type . '_percent') ,'percent_rate',1);
                    $percent = $this->percentDetail($merId,$type,'sys_type',$money,$store_id);
                    if(empty($percent)){
                        return cfg('' . $type . '_percent');

                    }else{

                        return $percent;
                    }
                } elseif ( cfg('platform_get_merchant_percent') >= 0) {
                    fdump('222222','percent_rate',1);
                    fdump(cfg('platform_get_merchant_percent') ,'percent_rate',1);
                    $percent = $this->percentDetail($merId,$type,'system',$money,$store_id);
                    if(empty($percent)){
                        return cfg('platform_get_merchant_percent');
                    }else{
                        return $percent;
                    }
                } else {
                    return 0;
                }
            } else {

                if (cfg('' . $type . '_percent') >= 0) {
                    return cfg('' . $type . '_percent');
                } elseif (cfg('platform_get_merchant_percent') >= 0) {
                    return cfg('platform_get_merchant_percent');
                } else {
                    return 0;
                }
            }
        }
    }

    /**
     * 抽成细则的筛选
     * @author: wanziyang
     * @date_time: 2020/6/15 13:36
     * @param integer|string $merId 商家id
     * @param string $type 类型
     * @param string $level 区分
     * @param float $money 金额
     * @param string|integer $store_id 店铺id
     * @return int
     */
    public function percentDetail($merId,$type,$level,$money,$store_id=''){
        if($type=='meal_scan') {
            return 0;
        }
        $db_percent_detail = new PercentDetail();
        $percent_detail = $db_percent_detail->getList([]);
        if(!empty($percent_detail)){
            $db_percent_detail_by_type = new PercentDetailByType();
            $db_percent_detail_by_store_type = new PercentDetailByStoreType();
            if($store_id){
                $system_percent = $db_percent_detail_by_store_type->getOne(array('fid'=>0));
                $mer_percent = $db_percent_detail_by_store_type->getOne(array('fid'=>$store_id));
                if (!$system_percent || $system_percent->isEmpty()) {
                    $system_percent = $db_percent_detail_by_type->getOne(array('fid'=>0));
                }
                if (!$mer_percent || $mer_percent->isEmpty()) {
                    $mer_percent = $db_percent_detail_by_type->getOne(array('fid'=>$merId));
                }
            }else{
                $system_percent = $db_percent_detail_by_type->getOne(array('fid'=>0));
                $mer_percent = $db_percent_detail_by_type->getOne(array('fid'=>$merId));
            }
            if (!$system_percent || $system_percent->isEmpty()) {
                $system_percent = [];
            } else {
                $system_percent = $system_percent->toArray();
            }
            if (!$mer_percent || $mer_percent->isEmpty()) {
                $mer_percent = [];
            } else {
                $mer_percent = $mer_percent->toArray();
            }

            $percent  = 0;
            $i = 0;
            $in_detail = false;
            $percent_arr = [];
            switch($level){
                case 'mer_type':
                    $percentDetail = $mer_percent[$type.'_percent_detail'] ?? '';
                    $percent_arr = explode(',',$percentDetail);
                    break;
                case 'merchant';
                    $percentDetail = $mer_percent['merchant_percent_detail'] ?? '';
                    $percent_arr = explode(',',$percentDetail);
                    break;
                case 'merchant_store';
                    $percentDetail = $mer_percent['merchant_store_percent_detail'] ?? '';
                    $percent_arr = explode(',',$percentDetail);
                    break;
                case 'sys_type':
                    $percentDetail = $system_percent[$type.'_percent_detail'] ?? '';
                    $percent_arr = explode(',',$percentDetail);
                    break;
            }

            foreach ($percent_detail as $pv) {
                if (bccomp($money, $pv['money_start'], 2) >= 0 && bccomp($pv['money_end'], $money, 2) >= 0) {
                    if(isset($percent_arr[$i]) && $percent_arr && $percent_arr[$i] >= 0 && $percent_arr[$i]!=''){
                        $percent = $percent_arr[$i];
                    }else{
                        $percent = $pv['percent'];
                    }
                    $in_detail = true;
                }
                $i++;
            }
            if($in_detail){
                return $percent;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

    /**
     * 元宝定制
     * @author: wanziyang
     * @date_time: 2020/6/15 13:43
     * @param array $order
     * @return int|string
     */
    public function getExtraMoney($order){
        $total_money = 0;
        switch($order['order_type']){
            case 'group':
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'meal':
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'dining'://餐饮2.0
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'shop':
            case 'mall':
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'appoint':
                if($order['paid'] == 3){
                    $total_money = $order['product_price'];
                }else{
                    if($order['is_initiative']==1){
                        //剩余钱的逻辑
                        if($order['product_id']){
                            //剩余钱的逻辑
                            $total_money = $order['product_balance_pay']+$order['user_pay_money']+$order['product_score_deducte']+$order['product_coupon_price'] + $order['product_payment_price'];

                        }else{
                            $total_money =$order['balance_pay'] + $order['pay_money'] + $order['product_balance_pay']+$order['user_pay_money']+$order['product_score_deducte']+$order['product_coupon_price'] + $order['product_payment_price'];
                        }
                    }else{
                        if($order['product_id']){
                            $money = $order['product_payment_price'];
                        }else{
                            $money = $order['payment_money'];
                        }
                        $total_money  = $money;
                    }
                }
                break;
            case 'store':
                $total_money = $order['balance_pay']+$order['merchant_balance']+$order['payment_money']+$order['score_deducte'];
                break;
            case 'cash':
                $total_money = $order['total_price'];
                break;
        }

        $service_merchant = new MerchantService();
        $now_merchant = $service_merchant->getMerchantByMerId($order['mer_id']);
        if($now_merchant['score_get']>=0){
            $score_percent = $now_merchant['score_get'];
        }else{
            $score_percent = cfg('user_score_get');
        }

        if(isset($order['extra_price']) && $order['extra_price']>0){
            if($order['score_used_count']>0){
                if($order['score_used_count']<$order['extra_price']){
                    $give_money =$order['extra_price']-$order['score_used_count'];
                }else{
                    $give_money =0 ;
                }
            }else{
                $give_money =bcmul($total_money,$score_percent,2) ;
            }
        }else{
            $give_money =bcmul($total_money,$score_percent,2) ;
        }

        return $give_money;
    }

    /**
     * 用户奖励比例 依据分佣第一级比例
     * @author: wanziyang
     * @date_time: 2020/6/15 13:45
     * @param string $type 业务类别
     * @return array
     */
    public function getUserAward($type){
        // 先行默认值
        $first_rate = $second_rate = $third_rate = 0;
        $rate_type = '';
        if(cfg(''.$type.'_first_rate')>=0){
            $first_rate=cfg(''.$type.'_first_rate');
            $second_rate=cfg(''.$type.'_second_rate')>0?cfg(''.$type.'_second_rate'):0;
            $third_rate=cfg(''.$type.'_third_rate')>0?cfg(''.$type.'_third_rate'):0;
            $rate_type='system_'.$type;
        }elseif(cfg('user_spread_rate')>=0){
            $first_rate=cfg('user_spread_rate');
            $second_rate=cfg('user_first_spread_rate')>0?cfg('user_first_spread_rate'):0;
            $third_rate=cfg('user_second_spread_rate')>0?cfg('user_second_spread_rate'):0;
            $rate_type = 'system';
        }

        return array(
            'first_rate'=>$first_rate,
            'second_rate'=>$second_rate,
            'third_rate'=>$third_rate,
            'type'=>$rate_type,
        );
    }

    /**
     * 获取批发抽成
     * @author: wanziyang
     * @date_time: 2020/6/15 13:47
     * @param integer|string $merId 商家id
     * @return bool|mixed
     */
    public function getMarketPercent($merId){
        $service_merchant = new MerchantService();
        $now_merchant = $service_merchant->getMerchantByMerId($merId);
        if($now_merchant['market_percent']>0){
            return  $now_merchant['market_percent'];
        }else{
            return   cfg('platform_get_merchant_percent');
        }
    }

    /**
     * 获取用户分佣比例
     * 对应旧代码方法：D('Percent_rate')->get_user_spread_rate()
     * @param $merId 商家ID
     * @param $type 类型
     * @param string $groupId 团购ID
     * @author: 张涛
     * @date: 2020/8/25
     */
    public function getUserSpreadRate($merId, $type, $groupId = '')
    {

        if($type == 'dining'){//餐饮2.0
            $type = 'meal';
        }
        
        $merchantPercentRateMod = new MerchantPercentRate();
        $now_mer_pr = $merchantPercentRateMod->getRateData(['mer_id' => $merId]);
        $first_rate = 0;
        $second_rate = 0;
        $third_rate = 0;
        $goods_percent = 0;
        $rate_type = '';

        if (cfg('open_score_pay_back') && cfg('system_take_spread_percent') > 0) {
            $spread_money_take = cfg('system_take_spread_percent');
            return array(
                'first_rate' => $spread_money_take * cfg('system_take_first_percent') / 100,
                'second_rate' => $spread_money_take * cfg('system_take_second_percent') / 100,
                'third_rate' => $spread_money_take * cfg('system_take_third_percent') / 100,
                'type' => 'system_take',
            );
        }
        switch ($type) {
            case 'recruit':
                // 招聘
                if (cfg('recruit_spread_rate') >= 0) {
                    $first_rate = cfg('recruit_spread_rate');
                    $second_rate = cfg('recruit_second_spread_rate') > 0 ? cfg('recruit_second_spread_rate') : 0;
                    $third_rate = cfg('recruit_third_spread_rate') > 0 ? cfg('recruit_third_spread_rate') : 0;
                    $rate_type = 'recruit';
                }
                break;
            case 'recharge':
                if (cfg('recharge_spread_rate') >= 0) {
                    $first_rate = cfg('recharge_spread_rate');
                    $second_rate = cfg('recharge_first_spread_rate') > 0 ? cfg('recharge_first_spread_rate') : 0;
                    $third_rate = cfg('recharge_second_spread_rate') > 0 ? cfg('recharge_second_spread_rate') : 0;
                    $rate_type = 'system';
                } elseif (cfg('user_spread_rate') >= 0) {
                    $first_rate = cfg('user_spread_rate');
                    $second_rate = cfg('user_first_spread_rate') > 0 ? cfg('user_first_spread_rate') : 0;
                    $third_rate = cfg('user_second_spread_rate') > 0 ? cfg('user_second_spread_rate') : 0;
                    $rate_type = 'system';
                }
                break;
            case 'classifynew':
                if (cfg('classifynew_first_rate') >= 0) {
                    $first_rate = cfg('classifynew_first_rate');
                    $second_rate = cfg('classifynew_second_rate') > 0 ? cfg('classifynew_second_rate') : 0;
                    $third_rate = cfg('classifynew_third_rate') > 0 ? cfg('classifynew_third_rate') : 0;
                    $rate_type = 'classifynew';
                } elseif (cfg('user_spread_rate') >= 0) {
                    $first_rate = cfg('user_spread_rate');
                    $second_rate = cfg('user_first_spread_rate') > 0 ? cfg('user_first_spread_rate') : 0;
                    $third_rate = cfg('user_second_spread_rate') > 0 ? cfg('user_second_spread_rate') : 0;
                    $rate_type = 'system';
                }
                break;
            case 'group':
                $nowGroup = (new GroupService())->getOne(['group_id' => $groupId]);
                $nowGroup = $nowGroup ? $nowGroup : [];
                if ($nowGroup['spread_rate'] >= 0) {
                    $first_rate = $nowGroup['spread_rate'];
                    $second_rate = $nowGroup['sub_spread_rate'] > 0 ? $nowGroup['sub_spread_rate'] : 0;
                    $third_rate = $nowGroup['third_spread_rate'] > 0 ? $nowGroup['third_spread_rate'] : 0;
                    $rate_type = 'group';
                } elseif ($now_mer_pr['group_first_rate'] >= 0 && $now_mer_pr['group_first_rate'] != '') {
                    $first_rate = $now_mer_pr['group_first_rate'];
                    $second_rate = $now_mer_pr['group_second_rate'] > 0 ? $now_mer_pr['group_second_rate'] : 0;
                    $third_rate = $now_mer_pr['group_third_rate'] > 0 ? $now_mer_pr['group_third_rate'] : 0;
                    $rate_type = 'merchant';
                } elseif (cfg('group_first_rate') >= 0) {
                    $first_rate = cfg('group_first_rate');
                    $second_rate = cfg('group_second_rate') > 0 ? cfg('group_second_rate') : 0;
                    $third_rate = cfg('group_third_rate') > 0 ? cfg('group_third_rate') : 0;
                    $rate_type = 'system_group';
                } elseif (cfg('user_spread_rate') >= 0) {
                    $first_rate = cfg('user_spread_rate');
                    $second_rate = cfg('user_first_spread_rate') > 0 ? cfg('user_first_spread_rate') : 0;
                    $third_rate = cfg('user_second_spread_rate') > 0 ? cfg('user_second_spread_rate') : 0;
                    $rate_type = 'system';
                }
                break;
            case 'shop':
            case 'mall':
                //升级商城用户分佣后去除
                $shopOrderMod = new \app\common\model\db\ShopOrder();
                $prefix = config('database.connections.mysql.prefix');
                $goods_list = $shopOrderMod->alias('o')
                    ->join([$prefix . 'shop_order_detail' => 'd'], 'o.order_id = d.order_id')
                    ->join([$prefix . 'shop_goods' => 'g'], 'g.goods_id = d.goods_id')
                    ->field('d.id,d.goods_id,d.price,d.discount_price,d.num,g.third_spread_rate,g.sub_spread_rate,g.spread_rate,o.payment_money,o.balance_pay,o.freight_charge')
                    ->where(['o.order_id' => $groupId])
                    ->select()
                    ->toArray();
                if (cfg('shop_goods_spread_edit') == 1 && $groupId) {//快店商品推广佣金获得单独设置
                    $first_rate = 0;
                    $second_rate = 0;
                    $third_rate = 0;
                    //商品分佣
                    $goods_percent = 1;
                    foreach ($goods_list as $_good) {
                        // 已退款商品不分佣
                        if ($_good['refundNum']) {
                            $_good['num'] = $_good['num'] - $_good['refundNum'];
                            if ($_good['num'] <= 0) {
                                continue;
                            }
                        }
                        $total_money = $_good['balance_pay'] + $_good['payment_money'];
                        if ($_good['spread_rate'] >= 0) {
                            $goods_toal_price = $_good['price'] * $_good['num'];
                            $first_money = round($goods_toal_price * ($_good['spread_rate'] / 100), 2);
                            $second_money = $_good['sub_spread_rate'] > 0 ? round($goods_toal_price * ($_good['sub_spread_rate'] / 100), 2) : 0;
                            $third_money = $_good['third_spread_rate'] > 0 ? round($goods_toal_price * ($_good['third_spread_rate'] / 100), 2) : 0;
                        } else {  //存在未设置商品分佣的跳出商品分佣
                            $goods_percent = 0;
                            break;
                        }
                        $first_rate += $first_money;
                        $second_rate += $second_money;
                        $third_rate += $third_money;
                    }
                    $rate_type = $type;
                }
                /*不走商品分佣使用系统的*/
                if ($goods_percent != 1) {
                    if ($now_mer_pr[$type . '_first_rate'] >= 0 && $now_mer_pr[$type . '_first_rate'] != '') {
                        $first_rate = $now_mer_pr[$type . '_first_rate'];
                        $second_rate = $now_mer_pr[$type . '_second_rate'] > 0 ? $now_mer_pr[$type . '_second_rate'] : 0;
                        $third_rate = $now_mer_pr[$type . '_third_rate'] > 0 ? $now_mer_pr[$type . '_third_rate'] : 0;
                        $rate_type = 'merchant';
                    } elseif (cfg('' . $type . '_first_rate') >= 0) {
                        $first_rate = cfg('' . $type . '_first_rate');
                        $second_rate = cfg('' . $type . '_second_rate') > 0 ? cfg('' . $type . '_second_rate') : 0;
                        $third_rate = cfg('' . $type . '_third_rate') > 0 ? cfg('' . $type . '_third_rate') : 0;
                        $rate_type = 'system_' . $type;
                    } elseif (cfg('user_spread_rate') >= 0) {
                        $first_rate = cfg('user_spread_rate');
                        $second_rate = cfg('user_first_spread_rate') > 0 ? cfg('user_first_spread_rate') : 0;
                        $third_rate = cfg('user_second_spread_rate') > 0 ? cfg('user_second_spread_rate') : 0;
                        $rate_type = 'system';
                    }
                }
                break;
            default:
                if ($now_mer_pr && $now_mer_pr[$type . '_first_rate'] >= 0 && $now_mer_pr[$type . '_first_rate'] !== "") {
                    $first_rate = $now_mer_pr[$type . '_first_rate'];
                    $second_rate = $now_mer_pr[$type . '_second_rate'] > 0 ? $now_mer_pr[$type . '_second_rate'] : 0;
                    $third_rate = $now_mer_pr[$type . '_third_rate'] > 0 ? $now_mer_pr[$type . '_third_rate'] : 0;
                    $rate_type = 'merchant';
                } elseif (cfg('' . $type . '_first_rate') >= 0 && cfg('' . $type . '_first_rate') !== "") {
                    $first_rate = cfg('' . $type . '_first_rate');
                    $second_rate = cfg('' . $type . '_second_rate') > 0 ? cfg('' . $type . '_second_rate') : 0;
                    $third_rate = cfg('' . $type . '_third_rate') > 0 ? cfg('' . $type . '_third_rate') : 0;
                    $rate_type = 'system_' . $type;
                } elseif (cfg('user_spread_rate') >= 0 && cfg('user_spread_rate') !== "") {
                    $first_rate = cfg('user_spread_rate');
                    $second_rate = cfg('user_first_spread_rate') > 0 ? cfg('user_first_spread_rate') : 0;
                    $third_rate = cfg('user_second_spread_rate') > 0 ? cfg('user_second_spread_rate') : 0;
                    $rate_type = 'system';
                }
                break;

        }
        return array(
            'first_rate' => $first_rate,
            'second_rate' => $second_rate,
            'third_rate' => $third_rate,
            'goods_percent' => $goods_percent,
            'type' => $rate_type,
        );
    }

    /**
     * 记录积分分佣日志
     * @param null $param
     * @param string $order_type
     * @param int $third_id
     * @return bool
     * @author: 张涛
     * @date: 2020/8/25
     */
    public function addSpreadJifenLog($param = null, $order_type = '', $third_id = 0)
    {
        if (empty($param)) {
            return false;
        }

        $spread_total_money = $param['spread_total_money'];
        if ($spread_total_money <= 0) {
            return false;
        }

        $now_time = time();
        $data = array();
        $arr = array();
        $arr['order_id'] = $param['order_id'];
        $arr['get_uid'] = $param['get_uid'];
        $arr['order_type'] = $order_type;
        $arr['spread_total_money'] = $spread_total_money;

        $spread_users = array();
        if (isset($param['get_user'])) {
            $spread_users[] = $param['get_user'];
        }
        //上一级分佣
        $first_jifen_percent = cfg('first_jifen_percent');
        if (!empty($param['first_uid']) && $first_jifen_percent > 0 && !in_array($param['first_uid'], $spread_users)) {
            $first_jifen = floor(($spread_total_money * $first_jifen_percent) / 100);
            if ($first_jifen > 0) {
                $arr['jifen'] = $first_jifen;
                $arr['uid'] = $param['first_uid'];
                $arr['spread_uid'] = 0;
                $arr['third_id'] = $third_id;
                $arr['status'] = 0;
                $arr['add_time'] = $now_time;
                $arr['update_time'] = $now_time;
                $data[] = $arr;
                $spread_users[] = $param['first_uid'];
            }
        } elseif (empty($param['first_uid'])) {
            if (!empty($data)) {
                \think\facade\Db::name('spread_jifen_list')->insertAll($data);
            }
            return false;
        }
        //上二级分佣
        $second_jifen_percent = cfg('second_jifen_percent');
        if (!empty($param['second_uid']) && $second_jifen_percent > 0 && !in_array($param['second_uid'], $spread_users)) {
            $second_jifen = floor(($spread_total_money * $second_jifen_percent) / 100);
            if ($second_jifen > 0) {
                $arr['jifen'] = $second_jifen;
                $arr['uid'] = $param['second_uid'];
                $arr['spread_uid'] = $param['first_uid'];
                $arr['third_id'] = $third_id;
                $arr['status'] = 0;
                $arr['add_time'] = $now_time;
                $arr['update_time'] = $now_time;
                $data[] = $arr;
                $spread_users[] = $param['second_uid'];
            }
        } elseif (empty($param['second_uid'])) {
            if (!empty($data)) {
                \think\facade\Db::name('spread_jifen_list')->insertAll($data);
            }
            return false;
        }
        //上三级分佣
        $third_jifen_percent = cfg('third_jifen_percent');
        if (!empty($param['third_uid']) && $third_jifen_percent > 0 && !in_array($param['third_uid'], $spread_users)) {
            $third_jifen = floor(($spread_total_money * $third_jifen_percent) / 100);
            if ($third_jifen > 0) {
                $arr['jifen'] = $third_jifen;
                $arr['uid'] = $param['third_uid'];
                $arr['spread_uid'] = $param['second_uid'];
                $arr['third_id'] = $third_id;
                $arr['status'] = 0;
                $arr['add_time'] = $now_time;
                $arr['update_time'] = $now_time;
                $data[] = $arr;
                $spread_users[] = $param['third_uid'];
            }
        } elseif (empty($param['third_uid'])) {
            if (!empty($data)) {
                \think\facade\Db::name('spread_jifen_list')->insertAll($data);
            }
            return false;
        }
    }


    //get_old_age_pension  获取养老金比例
    public function getOldAgePension($mer_id, $type, $money = 0, $retPer = false){
        if(empty($mer_id) || empty($type)) return 0;
        $db_merchant_percent_rate = new MerchantPercentRate();
        $merchant_config = $db_merchant_percent_rate->getRateData(['mer_id' => $mer_id]);
        if($merchant_config){
            $merchant_config = $merchant_config->toArray();
        }
        else{
            $merchant_config = [];   
        }
        $percent = 0;
        if($merchant_config && isset($merchant_config['oap_'.$type.'_percent']) && $merchant_config['oap_'.$type.'_percent'] > 0){
            $percent = $merchant_config['oap_'.$type.'_percent'];
        }
        elseif ($merchant_config && isset($merchant_config['oap_merchant_percent']) && $merchant_config['oap_merchant_percent'] > 0) {
            $percent = $merchant_config['oap_merchant_percent'];
        }
        elseif(cfg("oap_".$type."_percent") > 0){
            $percent = cfg("oap_".$type."_percent");
        }
        elseif(cfg("oap__percent") > 0){
            $percent = cfg("oap__percent");
        }
        return !$retPer ? get_format_number($money * $percent/100) : $percent;
    }

    /**
     * 获取用户分佣积分比例
     * @param int $merId
     * @param string $type
     * @param int $groupId
     * @return array
     * @author: 衡婷妹
     * @date: 2021/5/25
     */
    public function getUserScoreSpreadRate($type,$groupId=''){
        $first_rate='';
        $second_rate='';
        $third_rate='';
        $rate_type='';

        switch($type) {
            case 'group':

                $nowGroup = (new GroupService())->getOne(array('group_id'=>$groupId));

                // 开启团购单个商品积分分佣
                if($nowGroup['score_spread_rate']>=0 && cfg('open_score_spread_rate')==1){
                    $first_rate = $nowGroup['score_spread_rate'];
                    $second_rate = $nowGroup['sub_score_spread_rate']>0?$nowGroup['sub_score_spread_rate']:0;
                    $third_rate = $nowGroup['third_score_spread_rate']>0?$nowGroup['third_score_spread_rate']:0;
                    $rate_type = 'group';
                }else{
                    $first_rate=0;
                    $second_rate=0;
                    $third_rate=0;
                    $rate_type='';
                }
                break;
        }

        return array(
            'first_rate'=>$first_rate,
            'second_rate'=>$second_rate,
            'third_rate'=>$third_rate,
            'type'=>$rate_type,
        );
    }

    /**
     * 获取用户特殊日子积分比例
     * @param int $score
     * @param int $uid
     * @return bool
     * @author: 衡婷妹
     * @date: 2021/5/26
     */
    public function getUserAddScoreTimes($score,$uid){
        $now_user =(new UserService())->getUser($uid);
        $today_md = date('m-d');
        $now_year = date('Y').'-';

        $today_sec = strtotime($now_year.$today_md);
        if( cfg('vip_day')>=0 && cfg('vipday_score_times')>0){
            if (cfg('vip_day_type') == 1) { // 每周几积分倍数获取形式
                $today_week = date('w');
                if ($today_week == cfg('vip_day')) {
                    $score = $score*cfg('vipday_score_times');
                }
            }elseif (cfg('vip_day_type') == 2) { // 每月几号积分倍数获取形式
                $today_day = date('d');
                if ($today_day>9) {
                    $today_day = substr($today_day, -1);
                }
                if ($today_day == cfg('vip_day')) {
                    $score = $score*cfg('vipday_score_times');
                }
            }
        }

        if(date('m-d',strtotime($now_user['birthday']))==$today_md && cfg('birthday_score_times')>0){
            $score = $score*cfg('birthday_score_times');
        }

        $spec = (new SpecialDayScoreTimesService())->getSome([], true, 'value DESC');
        foreach ($spec as $v) {
            if($today_sec >= strtotime($now_year.$v['start']) &&  $today_sec<=strtotime($now_year.$v['end'])){
                $score = $score*$v['value'];
                break;
            }
        }
        return $score;
    }
    
    public static function scorePercent(string $goodsType, int $goodsId, int $merId)
    {
        $type = [
            'group'   => GroupService::class,
            'mall'    => MallGoodsService::class,
            'appoint' => AppointService::class,
            'shop'    => ShopGoodsService::class
        ];
        
        if(!isset($type[$goodsType])){
            throw_exception(L_('商品类型错误'));
        }
        $scorePercent = (new $type[$goodsType]())->getGoodsScore($goodsId);
        if($scorePercent == -1){//-1 不使用单品设置积分比例
            $merchantPercent = Merchant::where('mer_id', $merId)->value('score_get_percent');
            $merchantPercent = floatval($merchantPercent);
            if($merchantPercent >= 0){//使用商家设置积分比例
                $scorePercent = $merchantPercent/100;
            }else{
                if(cfg('open_score_get_percent') == 1){//平台开启积分比例
                    $scorePercent = cfg('score_get_percent')/100;
                }else{
                    $scorePercent = cfg('user_score_get');
                }
            }
        }

        return $scorePercent;
    }
}