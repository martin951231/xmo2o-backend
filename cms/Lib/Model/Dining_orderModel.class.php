<?php
/**
 * 餐饮2.0订单
 */
class Dining_orderModel extends Model
{
    public $orderStatusArr = null;
    public function _initialize()
    {
        $this->orderStatusArr = [
            "0" => L_("订单生成"),
            "1" => L_("待支付"),
            "2" => L_("未接单"),
            "3" => L_("就餐中"),
            "4" => L_("已完成"),
            "5" => L_("已取消"),
            "6" => L_("待落座"),
            "7" => L_("点餐中"),
        ];
    }

    /**
     * 获得前端所需字段详情
     * @param $orderId int 订单id
     * @param $user int 用户
     * @return array
     */
    public function geOrderDetail($orderId){

        if(!$orderId){
            return  [];
        }

        // 订单详情
        $order = $this->where(['order_id' => $orderId])->find();
        if(!$order) {
            return  [];
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        $returnArr = $order;
        $returnArr['order_status'] = $this->getOrderStatus($returnArr);
        $returnArr['show_price'] = $order['total_price'];

        if($returnArr['order_status'] == 4){//已完成 显示支付金额
            $returnArr['show_price'] = $returnArr['price'];
        }

        $returnArr['order_status_txt'] = $this->orderStatusArr[$returnArr['order_status']];
        if($order['status'] == 2){// 定金待支付
            // 查询待支付订单
            $whereOrder = [
                'paid' => 0,
                'order_type' => 0,
                'order_id' => $order['order_id'],
            ];
            $payOrder = M('Dining_order_pay')->where($whereOrder)->find();
            if($payOrder) {
                $returnArr['pay_order_id'] = $payOrder['pay_order_id'];
                $returnArr['show_price'] = $payOrder['price'];
                $returnArr['pay_type'] = 'dining';

            }
        }elseif($order['status'] == 3){// 尾款待支付 自取尾款待支付
            // 查询待支付订单
            $whereOrder = [
                'paid' => 0,
                'order_type' => 1,
                'order_id' => $order['order_id'],
            ];
            $payOrder = M('Dining_order_pay')->where($whereOrder)->find();
            if($payOrder){
                $returnArr['pay_order_id'] = $payOrder['pay_order_id'];
                $returnArr['show_price'] = $payOrder['price'];
                $returnArr['pay_type'] = 'dining';
            }
        }


        $orderDetail = M('Dining_order_detail')->where(['order_id'=>$orderId])->select();

        // 组合附属商品
        $orderDetail = D('Shop_goods')->combination_data($orderDetail, 'spec', 0);

        // 获得商品信息
        $goodsId = array_column($orderDetail,'goods_id');

        $where = [
            'goods_ids' => implode(',',$goodsId),
            'store_id' => $order['store_id'],
        ];
        $goodsArr = M('Shop_goods')->field('goods_id,image')->where($where)->select();
        $image = array_column($goodsArr,'image', 'goods_id');
        $img = new goods_image();
        foreach ($orderDetail as $k => &$value){
            $value['num'] = $value['num'] - $value['refundNum'];
            if($value['num']<=0){
                unset($orderDetail[$k]);
                continue;
            }

            $tmp_pic_arr = explode(';', $image[$value['goods_id']]);
            foreach ($tmp_pic_arr as $key => $_img) {
                if('http' === substr($_img,0,4)){
                    $temp_image = $_img;
                }else{
                    $temp_image = $img->get_image_by_path($_img,0);
                }
                if ($temp_image) {
                    if(is_array($temp_image)){
                        $value['goods_image'] = thumb($temp_image['image'],180,180,'fill');
                        break;
                    }else{
                        $value['goods_image'] = thumb($temp_image,180,180,'fill');
                        break;
                    }
                }else{
                    if (C('config.is_open_goods_default_image')&&C('config.goods_default_image')) {
                        $value['goods_image'] = C('config.goods_default_image');
                    }else{
                        $value['goods_image'] = C('config.site_url').'/tpl/Merchant/default/static/images/default_img.png';
                    }
                }
            }


        }
        $returnArr['goods_list'] = $orderDetail;
        return $returnArr;
    }

    /**
     * 返回订单状态
     * @param $where array
     * @return array
     */
    public function getOrderStatus($order){
        if($order['status'] == 0){//订单生成
            return 0;
        }

        if(in_array($order['status'] ,[2,3])){//待支付
            return 1;
        }

        if(in_array($order['status'] ,[4])){//已接单
            return 6;
        }

        if(in_array($order['status'] ,[21])){//点餐中
            return 7;
        }

        if($order['status'] == 1 && in_array($order['order_from'] ,[0,3])){//预定单
            return 2;
        }

        if($order['status'] >= 20 && $order['status'] < 40){//就餐中
            return 3;
        }

        if($order['status'] >= 40 && $order['status'] < 50){//已完成
            return 4;
        }

        if($order['status'] == 51 ){//已退款
            return 5;
        }

        if($order['status'] == 60){//已取消
            return 5;
        }
    }

    // 系统后台营业分析获取数据
    public function getRevenueStatistics($mer_id,$date,$param=array()){
        // 开始时间
        $start_time = $date;
        // 结束时间
        $end_time = $start_time+86400-1;

        // 已支付
        $condition['pay_time'] =  ['gt', 0];
        $m_condition['o.pay_time'] = ['gt', 0];

        
        // 支付时间
        $condition['pay_time'] = array('between',[$start_time,$end_time]);
        $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]);

        if (!$mer_id) {
            if ($param['area_id'] && $param['area_id'] != 'undefined') {
                $where_mer['area_id'] = $param['area_id'];
            } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
                $where_mer['city_id'] = $param['city_idss'];
            } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
                $where_mer['province_id'] = $param['province_idss'];
            }
            if ($where_mer) {
                $mer_ids = M('Merchant')->where($where_mer)->select();

                foreach ($mer_ids as $st) {
                    $mer_id_arr[] = $st['mer_id'];
                }
                if (!$mer_id_arr) {
                    return array();
                }
                $condition['mer_id'] = array('in',$mer_id_arr);
                $m_condition['o.mer_id'] = array('in',$mer_id_arr);
            }
        }else{
            // 商家id
            $condition['mer_id'] = $mer_id;
            $m_condition['o.mer_id'] = $mer_id;
        }

        // 店铺id
        if ($param['store_list']&&$param['store_list']!=-1) {
            $condition['store_id'] = array('in',$param['store_list']);
            $m_condition['o.store_id'] = array('in',$param['store_list']);
        }

        // 有效订单
        $condition['status'] = array('between',[4,50]);;  // 未取消 未退款

        /* 营业额 0点到现在(已支付订单) */
        $total_price_today = $this->field('sum(total_price) as total_price')->where($condition)->find();
        $returnArr['total_money'] = getFormatNumber($total_price_today['total_price']);

       
        /* 订单收入 已对账金额包括自由支付 扣除：1. 平台抽成   2. 平台配送费   3. 平台满减补贴   4. 商家满减补贴   5. 申请售后退款金额 */
        // 获得的金额
        $m_condition['m.type'] = 'dining';
        $m_condition['m.income'] = 1;
        $income_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'dining_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        // 支出的金额
        $m_condition['m.income'] = 2;
        $pay_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'dining_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();
        $returnArr['mer_income'] = getFormatNumber($income_money['money']) - getFormatNumber($pay_money['money']);

        // 自有支付
        // 自有支付方式支付的金额
        $m_condition['pay.is_own'] = ['gt',0];
        $m_condition['o.status'] = array('between',[4,50]);  // 未取消 未退款
        $m_condition['pay.paid'] = 1;  // 已支付
        $own_money = M('Dining_order_pay')->join(' as pay join '.C('DB_PREFIX').'dining_order as o on o.order_id=m.order_id')->field('sum(pay.pay_money) as payment_money,sum(pay.refund_money) as refund_money')->where($condition)->find();
        $own_money = getFormatNumber($own_money['payment_money']);
      
        $returnArr['mer_income'] += getFormatNumber($own_money-$own_money['refund_money']);// 商家收入加上自有支付的金额

        /* 平台抽成 */
        $returnArr['plat_get_commission'] = getFormatNumber($income_money['system_take']-$pay_money['system_take']);


        /* 有效订单：已接单，且未被取消、未申请售后全部商品退款的订单数；取消包括平台、店员、用户操作的取消 */
        $condition['status'] = array('between',[4,50]);  // 未取消 未退款
        $returnArr['effective_count'] = $this->where($condition)->count();

        /* 活动补贴：：平台/商家满减优惠与平台/商家优惠券补贴总额，包含：1. 平台满减优惠平台补贴金额部分，含满减优惠的所有类型  2. 平台满减优惠商家补贴金额部分，含满减优惠的所有类型  3. 商家自有满减优惠商家补贴金额部分   4. 平台优惠券平台补贴金额部分   5. 商家优惠券商家补贴金额部分   6. 平台积分抵扣平台补贴部分 */
        $discount_money = M('Dining_order')->field('sum(platform_plat) as platform_plat,sum(platform_merchant) as merchant_reduce,sum(card_price) as card_price,sum(coupon_price) as coupon_price,sum(system_score_money) as score_deducte,sum(merchant_discount_money) as merchant_discount_money')->where($condition)->find();
        $returnArr['discount_money'] = getFormatNumber($discount_money['platform_plat']+$discount_money['merchant_reduce']+$discount_money['card_price']+$discount_money['coupon_price']+$discount_money['score_deducte']+$discount_money['merchant_discount_money']);

        // 平台满减补贴金额
        $returnArr['platform_plat'] = getFormatNumber($discount_money['platform_plat']);

        // 平台优惠券补贴金额
        $returnArr['coupon_price'] = getFormatNumber($discount_money['coupon_price']);

        // 平台积分补贴金额
        $returnArr['score_deducte'] = getFormatNumber($discount_money['score_deducte']);

        /* 平台送出积分*/
        // 有效订单列表
        $order_ids = array_column($this->field('order_id')->where($condition)->select(), 'order_id') ;

        // 获得总积分
        $score_send_info = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>1])->find();
        $score_send = $score_send_info['score'];

        // 退回积分
        $score_send_info = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>2])->find();
        $score_back = $score_send_info['score'];
        // 平台送出的积分
        $returnArr['score_send'] = getFormatNumber($score_send - $score_back);

        if (C('config.user_score_recharge_percent')) {
            $returnArr['scoreSendMoney'] = getFormatNumber($returnArr['score_send']/C('config.user_score_recharge_percent'));
        }else{
            $returnArr['scoreSendMoney'] = 0;
        }
        /* 平台送出积分*/

        // 平台送出佣金
        $spread_info = D('User_spread_list')->field('sum(money) as money')->where(['order_id'=>['in',$order_ids],'status'=>1])->find();
        $returnArr['send_commission'] = getFormatNumber($spread_info['money']);

        // 平台补贴总额
        $returnArr['plat_total_money'] = getFormatNumber($discount_money['platform_plat']+$discount_money['coupon_price']+$discount_money['score_deducte']+$scoreSendMoney+$returnArr['send_commission']);

        // 商家优惠券补贴金额
        $returnArr['card_price'] = getFormatNumber($discount_money['card_price']);

        // 商家满减补贴金额
        $returnArr['merchant_reduce'] = getFormatNumber($discount_money['merchant_reduce']);

        // 商家补贴总额
        $returnArr['mer_total_money'] = getFormatNumber($discount_money['merchant_reduce']+$discount_money['card_price']);
    
        // 获取平台配送费（计算配送员比例）
         // 支付时间
        $d_condition['o.pay_time'] = array('between',[$start_time,$end_time]);


        // 平台盈利
        $returnArr['plat_income'] = getFormatNumber($returnArr['plat_get_commission']-$returnArr['plat_total_money']);

        // 补贴总额
        $returnArr['discount_money'] = getFormatNumber($returnArr['mer_total_money']+$returnArr['plat_total_money']);

        /* 客单价：有效订单的平均单价。 */
        if ($returnArr['effective_count']) {
            $returnArr['unit_price'] = getFormatNumber($returnArr['mer_income']/$returnArr['effective_count']);
        }else{
            $returnArr['unit_price'] = '0.00';
        }

        /* 下单用户数 */
        unset($condition['status']);
        $user_count_today = $this->field('count(distinct(uid)) as user_count')->where($condition)->find();
        $returnArr['user_count'] = $user_count_today['user_count'];


        /* 无效订单：包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。 */
        unset($condition['order_status']);
        $condition['status'] = array('in','60,51'); // 取消 退款
        $returnArr['invalid_count'] = $this->where($condition)->count();

        /* 损失：包括用户主动及平台、店员主动取消与申请售后全部商品退款的退款金额。 */
        $condition['status'] = array('in','60,51'); // 取消 退款
        $price_today = $this->field('sum(price) as price')->where($condition)->find();
        $returnArr['loss_money'] = getFormatNumber($price_today['price']);

        return $returnArr;
    }


    /* 快店统计平台盈利:当前时段下所有有效订单中平台佣金总额减去平台补贴总额加上平台配送费，可能会出现负数；平台补贴总额包含：1.平台满减优惠平台补贴金额部分，含满减优惠的所有类型2.平台优惠券平台补贴金额部分3.平台积分抵扣平台补贴部分4.平台会员等级优惠补贴部分5.平台送出积分部分6.平台送出平台佣金部分 */
    public function getPlatIncome($condition,$m_condition){
        /* 平台抽成 */       
        
        $m_condition['m.income'] = 1;
        $income_money = D('merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'dining_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        // 支出的金额
        $m_condition['m.income'] = 2;
        $pay_money = D('merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'dining_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        $plat_get_commission = getFormatNumber($income_money['system_take']-$pay_money['system_take']);
        /* 平台抽成 */

        /* 平台补贴 */
        $condition['status'] = array('between',[4,50]);  // 未取消 未退款
        $discount_money = M('Dining_order')->field('sum(platform_plat) as platform_plat,sum(platform_merchant) as merchant_reduce,sum(card_price) as card_price,sum(coupon_price) as coupon_price,sum(system_score_money) as score_deducte,sum(merchant_discount_money) as merchant_discount_money')->where($condition)->find();

        $discount_money = getFormatNumber($discount_money['platform_plat']+$discount_money['coupon_price']+$discount_money['score_deducte']);

        /* 平台送出积分*/
         // 有效订单列表
        $order_ids = array_column($this->field('order_id')->where($condition)->select(), 'order_id') ;

        // 获得总积分
        $score_send_info = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>1])->find();
        $score_send = $score_send_info['score'];

        // 退回积分
        $score_send_info = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>2])->find();
        $score_back = $score_send_info['score'];
        // 平台送出的积分
        $returnArr['score_send'] = getFormatNumber($score_send - $score_back);

        if (C('config.user_score_recharge_percent')) {
            $returnArr['scoreSendMoney'] = getFormatNumber($returnArr['score_send']/C('config.user_score_recharge_percent'));
        }else{
            $returnArr['scoreSendMoney'] = 0;
        }

        /* 平台送出积分*/

        $discount_money += $scoreSendMoney;
        // 平台送出佣金
        $spread_info = D('User_spread_list')->field('sum(money) as money')->where(['order_id'=>['in',$order_ids],'status'=>1])->find();

        $discount_money += $spread_info['money'];

    
        // 获取平台配送费（计算配送员比例）
         // 支付时间
        $d_condition['o.pay_time'] = $m_condition['o.pay_time'];

        // 商家id
        $d_condition['o.mer_id'] = $m_condition['o.mer_id'];      

         // 平台盈利
        $plat_income = getFormatNumber($plat_get_commission-$discount_money);
        return $plat_income;
    }



    // 营业分析获取数据
    public function getHistoryStatistics($condition,$m_condition,$discount_info=0,$shop_type=0){

        // 有效订单
        $condition['status'] = array('between',[4,50]);;  // 未取消 未退款

        /* 营业额 0点到现在(已支付订单) */
        $total_price_today = $this->field('sum(total_price) as total_price')->where($condition)->find();
        $returnArr['total_money'] = getFormatNumber($total_price_today['total_price']);

        // var_dump($this->_sql(),$total_price_today);

           /* 订单收入 已对账金额包括自由支付 扣除：1. 平台抽成   2. 平台配送费   3. 平台满减补贴   4. 商家满减补贴   5. 申请售后退款金额 */
        // 获得的金额
        $m_condition['m.type'] = 'dining';
        $m_condition['m.income'] = 1;
        $income_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'dining_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        // 支出的金额
        $m_condition['m.income'] = 2;
        $pay_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'dining_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();
        $returnArr['mer_income'] = getFormatNumber($income_money['money']) - getFormatNumber($pay_money['money']);

        // 自有支付
        // 自有支付方式支付的金额
        $m_condition['pay.is_own'] = ['gt',0];
        $m_condition['o.status'] = array('between',[4,50]);  // 未取消 未退款
        $m_condition['pay.paid'] = 1;  // 已支付
        $own_money = M('Dining_order_pay')->join(' as pay join '.C('DB_PREFIX').'dining_order as o on o.order_id=m.order_id')->field('sum(pay.pay_money) as payment_money,sum(pay.refund_money) as refund_money')->where($condition)->find();
        $own_money = getFormatNumber($own_money['payment_money']);
      
        $returnArr['mer_income'] += getFormatNumber($own_money-$own_money['refund_money']);// 商家收入加上自有支付的金额

        /* 平台抽成 */
        $returnArr['plat_get_commission'] = getFormatNumber($income_money['system_take']-$pay_money['system_take']);


        /* 有效订单：已接单，且未被取消、未申请售后全部商品退款的订单数；取消包括平台、店员、用户操作的取消 */
        $condition['status'] = array('between',[4,50]);  // 未取消 未退款
        $returnArr['effective_count'] = $this->where($condition)->count();

        /* 活动补贴：：平台/商家满减优惠与平台/商家优惠券补贴总额，包含：1. 平台满减优惠平台补贴金额部分，含满减优惠的所有类型  2. 平台满减优惠商家补贴金额部分，含满减优惠的所有类型  3. 商家自有满减优惠商家补贴金额部分   4. 平台优惠券平台补贴金额部分   5. 商家优惠券商家补贴金额部分   6. 平台积分抵扣平台补贴部分 */
        $discount_money = M('Dining_order')->field('sum(platform_plat) as platform_plat,sum(platform_merchant) as merchant_reduce,sum(card_price) as card_price,sum(coupon_price) as coupon_price,sum(system_score_money) as score_deducte,sum(merchant_discount_money) as merchant_discount_money')->where($condition)->find();
        $returnArr['discount_money'] = getFormatNumber($discount_money['platform_plat']+$discount_money['merchant_reduce']+$discount_money['card_price']+$discount_money['coupon_price']+$discount_money['score_deducte']+$discount_money['merchant_discount_money']);

        // 平台满减补贴金额
        $returnArr['platform_plat'] = getFormatNumber($discount_money['platform_plat']);

        // 平台优惠券补贴金额
        $returnArr['coupon_price'] = getFormatNumber($discount_money['coupon_price']);

        // 平台积分补贴金额
        $returnArr['score_deducte'] = getFormatNumber($discount_money['score_deducte']);


        // 返回优惠详情
        if ($discount_info) {
            // 平台满减补贴金额
            $returnArr['discount_info']['platform_plat'] = getFormatNumber($discount_money['platform_plat']);
            // 商家满减补贴金额
            $returnArr['discount_info']['merchant_reduce'] = getFormatNumber($discount_money['merchant_reduce']);
            // 平台优惠券补贴金额
            $returnArr['discount_info']['coupon_price'] = getFormatNumber($discount_money['coupon_price']);
            // 商家优惠券补贴金额
            $returnArr['discount_info']['card_price'] = getFormatNumber($discount_money['card_price']);
            // 平台积分补贴金额
            $returnArr['discount_info']['score_deducte'] = getFormatNumber($discount_money['score_deducte']);


            // 平台补贴总额
            $returnArr['discount_info']['plat_total'] = getFormatNumber($discount_money['platform_plat']+$discount_money['coupon_price']+$discount_money['score_deducte']);
        }

        /* 平台送出积分*/
        // 有效订单列表
        $order_ids = array_column($this->field('order_id')->where($condition)->select(), 'order_id') ;

        // 获得总积分
        $score_send_info = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>1])->find();
        $score_send = $score_send_info['score'];

        // 退回积分
        $score_send_info = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>2])->find();
        $score_back = $score_send_info['score'];
        // 平台送出的积分
        $returnArr['score_send'] = getFormatNumber($score_send - $score_back);

        if (C('config.user_score_recharge_percent')) {
            $returnArr['scoreSendMoney'] = getFormatNumber($returnArr['score_send']/C('config.user_score_recharge_percent'));
        }else{
            $returnArr['scoreSendMoney'] = 0;
        }
        /* 平台送出积分*/

        // 平台送出佣金
        $spread_info = D('User_spread_list')->field('sum(money) as money')->where(['order_id'=>['in',$order_ids],'status'=>1])->find();
        $returnArr['send_commission'] = getFormatNumber($spread_info['money']);

        // 平台补贴总额
        $returnArr['plat_total_money'] = getFormatNumber($discount_money['platform_plat']+$discount_money['coupon_price']+$discount_money['score_deducte']+$scoreSendMoney+$returnArr['send_commission']);

        // 商家优惠券补贴金额
        $returnArr['card_price'] = getFormatNumber($discount_money['card_price']);

        // 商家满减补贴金额
        $returnArr['merchant_reduce'] = getFormatNumber($discount_money['merchant_reduce']);

        // 商家补贴总额
        $returnArr['mer_total_money'] = getFormatNumber($discount_money['merchant_reduce']+$discount_money['card_price']);
    
        // 获取平台配送费（计算配送员比例）
         // 支付时间
        $d_condition['o.pay_time'] = array('between',[$start_time,$end_time]);


        // 平台盈利
        $returnArr['plat_income'] = getFormatNumber($returnArr['plat_get_commission']-$returnArr['plat_total_money']);

        // 补贴总额
        $returnArr['discount_money'] = getFormatNumber($returnArr['mer_total_money']+$returnArr['plat_total_money']);

        /* 客单价：有效订单的平均单价。 */
        if ($returnArr['effective_count']) {
            $returnArr['unit_price'] = getFormatNumber($returnArr['mer_income']/$returnArr['effective_count']);
        }else{
            $returnArr['unit_price'] = '0.00';
        }

        /* 下单用户数 */
        unset($condition['status']);
        $user_count_today = $this->field('count(distinct(uid)) as user_count')->where($condition)->find();
        $returnArr['user_count'] = $user_count_today['user_count'];


        /* 无效订单：包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。 */
        unset($condition['order_status']);
        $condition['status'] = array('in','60,51'); // 取消 退款
        $returnArr['invalid_count'] = $this->where($condition)->count();

        /* 损失：包括用户主动及平台、店员主动取消与申请售后全部商品退款的退款金额。 */
        $condition['status'] = array('in','60,51'); // 取消 退款
        $price_today = $this->field('sum(price) as price')->where($condition)->find();
        $returnArr['loss_money'] = getFormatNumber($price_today['price']);




        return $returnArr;
    }


}

?>