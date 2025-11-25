<?php
//营销统计
class Revenue_statisticsModel extends Model{
	
    // 系统后台营业分析获取数据
    public function getRevenueStatistics($param, $is_system=1){
        // 开始时间 // 结束时间
        if ($param['start_time']&&$param['end_time']) {
            // 统计时间
            $condition['statistics_date'] = array('between',[$param['start_time'],$param['end_time']]);
        }

        if ($param['area_id'] && $param['area_id'] != 'undefined') {
            $where_mer['area_id'] = $param['area_id'];
        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
            $where_mer['city_id'] = $param['city_idss'];
        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
            $where_mer['province_id'] = $param['province_idss'];
        }

        if(C('customization.life_tools') && $param['system_session']['level'] != 2){// 城投生活通定制 商家绑定管理员
            $adminIdArr = D('Admin')->getAdminByFid($param['system_session']['id']);
            $where_mer['admin_id'] = ['in', $adminIdArr];               
        }
        
        if ($where_mer) {
            $mer_ids = M('Merchant')->where($where_mer)->select();

            foreach ($mer_ids as $st) {
                $mer_id_arr[] = $st['mer_id'];
            }
            if (!$mer_id_arr) {
                $condition['mer_id'] = null;
            }else{
                $condition['mer_id'] = array('in',$mer_id_arr);
            }
        }

        if ($param['mer_id']) {
            $condition['mer_id'] = $param['mer_id'];
        }

        // 查询统计类型
        $condition['business_type'] = $param['business_type'];

        if ($condition['business_type'] == 'market') {
            // 查询订单类型
            $condition['order_type'] = $param['order_type'];
        }

        // 查询字段
        $field = $this->getRevenueStatisticsField($param['business_type'], $is_system);

       	// 查询数据
        $returnArr = $this->field($field)->where($condition)->find();
       	// 处理数据
        $returnArr = $this->getFormatData($param['business_type'],$returnArr);

        return $returnArr;
    } 
    
    /* 快店统计平台盈利:当前时段下所有有效订单中平台佣金总额减去平台补贴总额加上平台配送费，可能会出现负数；平台补贴总额包含：1.平台满减优惠平台补贴金额部分，含满减优惠的所有类型2.平台优惠券平台补贴金额部分3.平台积分抵扣平台补贴部分4.平台会员等级优惠补贴部分5.平台送出积分部分6.平台送出平台佣金部分 */
    public function getPlatIncome($condition,$m_condition,$shop_type=0){
        /* 平台抽成 */
        // 获得的金额
        if($shop_type=1){
            $m_condition['m.type'] = 'mall';
        }else{
            $m_condition['m.type'] = 'shop';
        }
        
        $m_condition['m.income'] = 1;
        $income_money = D('merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        // 支出的金额
        $m_condition['m.income'] = 2;
        $pay_money = D('merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        $plat_get_commission = getFormatNumber($income_money['system_take']-$pay_money['system_take']);
        /* 平台抽成 */

        /* 平台补贴 */
        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款
        $discount_money = M('Shop_order')->field('sum(platform_plat) as platform_plat,sum(merchant_reduce) as merchant_reduce,sum(card_price) as card_price,sum(coupon_price) as coupon_price,sum(score_deducte) as score_deducte,sum(packing_charge) as packing_charge,sum(vip_level_reduce_money) as vip_level_reduce_money')->where($condition)->find();

        $discount_money = getFormatNumber($discount_money['platform_plat']+$discount_money['vip_level_reduce_money']+$discount_money['coupon_price']+$discount_money['score_deducte']);

        /* 平台送出积分*/
        // 有效订单列表
        $order_ids = array_column(M('Shop_order')->field('order_id')->where($condition)->select(), 'order_id') ;

        // 获得总积分
        $score_send_info = D('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>1])->find();
        $score_send = $score_send_info['score'];

        // 退回积分
        $score_send_info = D('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>2])->find();
        $score_back = $score_send_info['score'];

        $score_send_info = D('Shop_order_system_refund')->field('sum(score_back_count) as score')->where(['order_id'=>['in',$order_ids]])->find();
        $score_back += $score_send_info['score'];

        $score_send_info = D('Shop_order_refund')->field('sum(score_back_count) as score')->where(['order_id'=>['in',$order_ids,'status'=>1]])->find();
        $score_back += $score_send_info['score'];

        $score_send = getFormatNumber($score_send - $score_back);

        if (C('config.user_score_recharge_percent')) {
            $scoreSendMoney = getFormatNumber($score_send/C('config.user_score_recharge_percent'));
        }else{
            $scoreSendMoney = 0;
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

        // 已支付
        $d_condition['o.paid'] = 1;

        $d_condition['o.order_status'] = array('gt',0);  // 已接单
        $d_condition['o.status'] = array('in','0,1,2,3');  // 未取消 未退款
        $d_condition['o.is_pick_in_store'] = 0;  // 未取消 未退款
        $freight_charge = M('Shop_order')->field('sum(freight_charge) as freight_charge,sum(deliver_user_fee) as deliver_user_fee')->join(' as o left join '.C('DB_PREFIX').'deliver_supply as m on o.order_id=m.order_id and order_from=0')->where($d_condition)->find();
        $plat_freight_charge = getFormatNumber($freight_charge['freight_charge']-$freight_charge['deliver_user_fee']);

         // 平台盈利
        $plat_income = getFormatNumber($plat_get_commission-$discount_money+$plat_freight_charge);
        return $plat_income;
    }

    // 获得查询字段
    public function getRevenueStatisticsField($business_type, $is_system = 1){
        switch ($business_type) {
            case 'shop': // 外卖
            case 'dining': // 新版餐饮
                $field = 'sum(total_money) as total_money,sum(mer_income) as mer_income,sum(effective_count) as effective_count,sum(invalid_count) as invalid_count,sum(plat_total_money) as plat_total_money,sum(mer_total_money) as mer_total_money,sum(plat_income) as plat_income,sum(plat_get_commission) as plat_get_commission,sum(plat_freight_charge) as plat_freight_charge,sum(unit_price) as unit_price,sum(loss_money) as loss_money,sum(user_count) as user_count,sum(platform_plat) as platform_plat,sum(coupon_price) as coupon_price,sum(vip_level_reduce_money) as vip_level_reduce_money,sum(send_score) as send_score,sum(send_commission) as send_commission,sum(merchant_reduce) as merchant_reduce,sum(card_price) as card_price,sum(score_deducte) as score_deducte';
                if(!$is_system){
                    $field = 'sum(total_money) as total_money,sum(mer_income) as mer_income,sum(effective_count) as effective_count,sum(invalid_count) as invalid_count,sum(plat_total_money) as plat_total_money,sum(mer_total_money) as mer_total_money,sum(plat_freight_charge) as plat_freight_charge,sum(unit_price) as unit_price,sum(loss_money) as loss_money,sum(user_count) as user_count,sum(platform_plat) as platform_plat,sum(coupon_price) as coupon_price,sum(vip_level_reduce_money) as vip_level_reduce_money,sum(send_score) as send_score,sum(send_commission) as send_commission,sum(merchant_reduce) as merchant_reduce,sum(card_price) as card_price,sum(score_deducte) as score_deducte';
                }
                break;
            case 'mall': // 商城
                $field = 'sum(total_money) as total_money,sum(mer_income) as mer_income,sum(effective_count) as effective_count,sum(invalid_count) as invalid_count,sum(plat_total_money) as plat_total_money,sum(mer_total_money) as mer_total_money,sum(plat_income) as plat_income,sum(plat_get_commission) as plat_get_commission,sum(plat_freight_charge) as plat_freight_charge,sum(unit_price) as unit_price,sum(loss_money) as loss_money,sum(user_count) as user_count,sum(platform_plat) as platform_plat,sum(coupon_price) as coupon_price,sum(vip_level_reduce_money) as vip_level_reduce_money,sum(send_score) as send_score,sum(send_commission) as send_commission,sum(merchant_reduce) as merchant_reduce,sum(card_price) as card_price,sum(score_deducte) as score_deducte';
                break;
            case 'deliver': // 配送
                $field = 'sum(total_money) as total_money,sum(plat_freight_charge) as plat_freight_charge,sum(effective_count) as effective_count,sum(invalid_count) as invalid_count,sum(plat_total_money) as plat_total_money,sum(mer_total_money) as mer_total_money,sum(overtime_count) as overtime_count,sum(overtime_rate) as overtime_rate,sum(reminder_count) as reminder_count,sum(bad_count) as bad_count,sum(loss_money) as loss_money,sum(deliver_money_shop) as deliver_money_shop,sum(plat_freight_charge_shop) as plat_freight_charge_shop,sum(deliver_money_mall) as deliver_money_mall,sum(plat_freight_charge_mall) as plat_freight_charge_mall,sum(deliver_money_paotui) as deliver_money_paotui,sum(plat_freight_charge_paotui) as plat_freight_charge_paotui,sum(deliver_money_third) as deliver_money_third,sum(plat_freight_charge_third) as plat_freight_charge_third,sum(deliver_money_village_group) as deliver_money_village_group,sum(plat_freight_charge_village_group) as plat_freight_charge_village_group';
                break;
            case 'market': // 营销
                $field = 'sum(total_money) as total_money,sum(effective_count) as effective_count,sum(plat_income) as plat_income,sum(plat_total_money) as plat_total_money,sum(market_total_money) as market_total_money,sum(market_count) as market_count,sum(send_commission_mer) as send_commission_mer,sum(platform_plat_order_money) as platform_plat_order_money,sum(coupon_price_order_money) as coupon_price_order_money,sum(vip_level_reduce_money_order_money) as vip_level_reduce_money_order_money,sum(send_score_order_money) as send_score_order_money,sum(send_commission_order_money) as send_commission_order_money,sum(score_deducte_order_money) as score_deducte_order_money,sum(send_commission_mer_order_money) as send_commission_mer_order_money,sum(platform_plat_count) as platform_plat_count,sum(coupon_price_count) as coupon_price_count,sum(vip_level_reduce_money_count) as vip_level_reduce_money_count,sum(send_score_count) as send_score_count,sum(send_commission_count) as send_commission_count,sum(score_deducte_count) as score_deducte_count,sum(send_commission_mer_count) as send_commission_mer_count,sum(new_customer) as new_customer,sum(regular_customer) as regular_customer,system_coupon_hadpull,system_coupon_hadpull_used';
                break;
        } 

        return $field;
    }


    // 处理数据
    public function getFormatData($business_type,$returnArr){
        switch ($business_type) {
            case 'mall'://商城和外卖是一样的
            case 'shop': // 外卖
                $returnArr['unit_price'] = $returnArr['mer_income'] ? getFormatNumber($returnArr['mer_income']/$returnArr['effective_count']) : 0;
                if (C('config.user_score_recharge_percent')) {
                    $returnArr['scoreSendMoney'] = getFormatNumber($returnArr['send_score']/C('config.user_score_recharge_percent'));
                }else{
                    $returnArr['scoreSendMoney'] = 0;
                }
                break;
            case 'dining': // 新版餐饮
                $returnArr['unit_price'] = $returnArr['mer_income'] ? getFormatNumber($returnArr['mer_income']/$returnArr['effective_count']) : 0;
                if (C('config.user_score_recharge_percent')) {
                    $returnArr['scoreSendMoney'] = getFormatNumber($returnArr['send_score']/C('config.user_score_recharge_percent'));
                }else{
                    $returnArr['scoreSendMoney'] = 0;
                }
                break;
            case 'deliver': // 配送
                /* 配送超时超时率：统计时间内配送超时订单量除以有效配送单量。 */
                $returnArr['overtime_rate'] = $returnArr['effective_count']>0 ? getFormatNumber($returnArr['overtime_count']/$returnArr['effective_count']*100) : 0;

                /* 差评率：统计时间内配送员差评单量除以配送员评价总单量。 */
                $returnArr['bad_rate'] = $returnArr['effective_count']>0 ? getFormatNumber($returnArr['bad_count']/$returnArr['effective_count']*100) : 0;

                /* 催单率：统计时间内催单量除以有效配送单量。 */
                $returnArr['reminder_rate'] = $returnArr['effective_count']>0 ? getFormatNumber($returnArr['reminder_count']/$returnArr['effective_count']*100) : 0;

                /* 活动补贴 */
                $returnArr['discount_money'] =  getFormatNumber($returnArr['plat_total_money']+$returnArr['mer_total_money']);
                break;
            case 'market': // 营销                    
                /* 核销率 */
                $returnArr['coupon_use_rate'] = $returnArr['system_coupon_hadpull']>0 ? getFormatNumber($returnArr['system_coupon_hadpull_used']/$returnArr['system_coupon_hadpull']*100) : 0;
                /* 投入产出比 */
                $returnArr['input_output_rate'] = $returnArr['plat_total_money']>0 ? getFormatNumber($returnArr['plat_income']/$returnArr['plat_total_money']*100) : 0;
                
                /* 客单价：统计时间内所有有效订单中活动订单的平均单价，计算公式为：统计时间内所有有效订单中所有活动订单的商家净收入除以统计时间内所有有效订单中的所有活动订单总数 */
                if ($returnArr['market_count']) {
                    $returnArr['unit_price'] = getFormatNumber($returnArr['mer_income']/$returnArr['market_count']);
                }else{
                    $returnArr['unit_price'] = '0.00';
                }

                /* 占总营业额百分比，计算方式为：活动总营业额除以总营业额 */
                $returnArr['market_total_money_rate'] = $returnArr['total_money']>0 ? getFormatNumber($returnArr['market_total_money']/$returnArr['total_money']*100) : 0;

                /* 占总订单百分比，计算方式为：活动总订单数除以总订单数 */
                $returnArr['market_count_rate'] = $returnArr['effective_count']>0 ? getFormatNumber($returnArr['market_count']/$returnArr['effective_count']*100) : 0;
                break;
        }    

        return $returnArr;
    }

    // 对比数据
    public function getCompareData($business_type,$returnArr,$returnArrCompare,$is_percent=0,$compare_text=''){
        /*********************** 订单数据对比  ****************/
        $returnArrFormat = $returnArr;
        switch ($business_type) {
            case 'mall': 
        	case 'shop':
        	case 'deliver':
        		// 单位
	        	if ($is_percent) { 
	        		$unit = '%';
		        }else{
	        		$unit = C('config.Currency_txt');
		        }

		        // 营业额 比昨日同时段差异
	        	$returnArr1 = $this->getCompare($returnArr['total_money'],$returnArrCompare['total_money'],'total_money',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 商家收入 比昨日同时段差异
	        	$returnArr1 = $this->getCompare($returnArr['mer_income'],$returnArrCompare['mer_income'],'mer_income',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 平台配送费 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['plat_freight_charge'],$returnArrCompare['plat_freight_charge'],'plat_freight_charge',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 客单价 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['unit_price'],$returnArrCompare['unit_price'],'unit_price',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 平台活动补贴 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['plat_total_money'],$returnArrCompare['plat_total_money'],'plat_total_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 商家活动补贴 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['mer_total_money'],$returnArrCompare['mer_total_money'],'mer_total_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 平台盈利 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['plat_income'],$returnArrCompare['plat_income'],'plat_income',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 平台抽成 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['plat_get_commission'],$returnArrCompare['plat_get_commission'],'plat_get_commission',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 损失 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['loss_money'],$returnArrCompare['loss_money'],'loss_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 配送统计
                // 活动补贴
                $returnArr1 = $this->getCompare($returnArr['discount_money'],$returnArrCompare['discount_money'],'discount_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = '单';
                }
		        // 有效订单 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['effective_count'],$returnArrCompare['effective_count'],'effective_count',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 无效订单 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['invalid_count'],$returnArrCompare['invalid_count'],'invalid_count',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		       
                // 配送超时订单数
                $returnArr1 = $this->getCompare($returnArr['overtime_count'],$returnArrCompare['overtime_count'],'overtime_count',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
                // 催单数
                $returnArr1 = $this->getCompare($returnArr['reminder_count'],$returnArrCompare['reminder_count'],'reminder_count',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		        
                // 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = '人';
                }
		        // 下单用户数 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['user_count'],$returnArrCompare['user_count'],'user_count',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

        		// 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = '';
                }

		        // 配送超时率
                $returnArr1 = $this->getCompare($returnArr['overtime_rate'],$returnArrCompare['overtime_rate'],'overtime_rate',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 催单率
                $returnArr1 = $this->getCompare($returnArr['reminder_rate'],$returnArrCompare['reminder_rate'],'reminder_rate',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 差评数
                $returnArr1 = $this->getCompare($returnArr['bad_count'],$returnArrCompare['bad_count'],'bad_count',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		        
		        // 差评率
                $returnArr1 = $this->getCompare($returnArr['bad_rate'],$returnArrCompare['bad_rate'],'bad_rate',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
        		break;
            case 'dining':
                // 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = C('config.Currency_txt');
                }

                // 营业额 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['total_money'],$returnArrCompare['total_money'],'total_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 商家收入 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['mer_income'],$returnArrCompare['mer_income'],'mer_income',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
               
                // 客单价 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['unit_price'],$returnArrCompare['unit_price'],'unit_price',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 平台活动补贴 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['plat_total_money'],$returnArrCompare['plat_total_money'],'plat_total_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 商家活动补贴 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['mer_total_money'],$returnArrCompare['mer_total_money'],'mer_total_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 平台盈利 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['plat_income'],$returnArrCompare['plat_income'],'plat_income',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 平台抽成 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['plat_get_commission'],$returnArrCompare['plat_get_commission'],'plat_get_commission',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 损失 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['loss_money'],$returnArrCompare['loss_money'],'loss_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 活动补贴
                $returnArr1 = $this->getCompare($returnArr['discount_money'],$returnArrCompare['discount_money'],'discount_money',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = '单';
                }
                // 有效订单 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['effective_count'],$returnArrCompare['effective_count'],'effective_count',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 无效订单 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['invalid_count'],$returnArrCompare['invalid_count'],'invalid_count',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
                
                // 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = '人';
                }
                // 下单用户数 比昨日同时段差异
                $returnArr1 = $this->getCompare($returnArr['user_count'],$returnArrCompare['user_count'],'user_count',$is_percent,$unit,$compare_text);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
                break;
        	case 'market': //营销统计
        		// 单位
	        	if ($is_percent) { 
	        		$unit = '%';
		        }else{
	        		$unit = C('config.Currency_txt');
		        }
		        // 活动总营业额
	        	$returnArr1 = $this->getCompare($returnArr['market_total_money'],$returnArrCompare['market_total_money'],'market_total_money',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 活动总支出
	        	$returnArr1 = $this->getCompare($returnArr['plat_total_money'],$returnArrCompare['plat_total_money'],'plat_total_money',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 活动盈利
	        	$returnArr1 = $this->getCompare($returnArr['plat_income'],$returnArrCompare['plat_income'],'plat_income',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 活动商家收入
	        	$returnArr1 = $this->getCompare($returnArr['mer_income'],$returnArrCompare['mer_income'],'mer_income',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 活动客单价
	        	$returnArr1 = $this->getCompare($returnArr['unit_price'],$returnArrCompare['unit_price'],'unit_price',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

        		// 单位
				if ($is_percent) { 
	        		$unit = '%';
		        }else{
	        		$unit = '单';
		        }

		        // 带动订单
	        	$returnArr1 = $this->getCompare($returnArr['market_count'],$returnArrCompare['market_count'],'market_count',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

        		// 单位
				if ($is_percent) { 
	        		$unit = '%';
		        }else{
	        		$unit = '人';
		        }
		        // 活动老客数
	        	$returnArr1 = $this->getCompare($returnArr['regular_customer'],$returnArrCompare['regular_customer'],'regular_customer',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		        // 活动新客数
	        	$returnArr1 = $this->getCompare($returnArr['new_customer'],$returnArrCompare['new_customer'],'new_customer',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 单位
				if ($is_percent) { 
	        		$unit = '%';
		        }else{
	        		$unit = '';
		        }
		        // 投入产出比
	        	$returnArr1 = $this->getCompare($returnArr['input_output_rate'],$returnArrCompare['input_output_rate'],'input_output_rate',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		        // 领券数量
	        	$returnArr1 = $this->getCompare($returnArr['system_coupon_hadpull'],$returnArrCompare['system_coupon_hadpull'],'system_coupon_hadpull',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		        // 核销率
	        	$returnArr1 = $this->getCompare($returnArr['coupon_use_rate'],$returnArrCompare['coupon_use_rate'],'coupon_use_rate',$is_percent,$unit,$compare_text);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

        		break;
        }
        

        return $returnArrFormat;
    }

    public function getCompare($number,$numberCompare,$param_name,$is_percent=0,$unit='',$compare_text='',$is_wap=0){
    	if ($is_percent) {
            // 符号
            $diff = $number-$numberCompare;
            if ($diff>=0) {
                $fuhao = 1;
            }elseif ($diff<0) {
                $fuhao = -1;
            }

	    	if ($numberCompare!=0&&$number!=0) {
                $compare = $fuhao * abs(getFormatNumber(($number/$numberCompare)-1))*100; 
	        }elseif ($numberCompare!=0&&$number==0 || $numberCompare==0&&$number!=0) {
                $compare = $fuhao * 100;
            }else{
                $compare = '0';
	        }
    	}else{
    		$compare = getFormatNumber($number-$numberCompare);
    	}
      
        $returnArr = $this->statisticsFormat($number,$param_name,$compare,$unit,$compare_text,$is_wap,$is_percent);
        return $returnArr;
    }

    /**营业分析 对比数据*/
    public function statisticsFormat($data,$param='',$diff='',$unit='',$compare_text='',$is_wap=0,$is_percent=0){
        if(!$compare_text){
           $compare_text = L_('比昨日同时段');
        }
        $returnArr = [];
        $data = explode('.', $data);
        
        if(!empty($data[0])){
            $num = format_number_by_comma($data[0]);
            if(strpos($num,'-') === false && $data[0]<0){
                $num = '-'.$num;
            }
        }
        
        $returnArr[$param.'_0'] = $data[0] ? $num : '0';
        $returnArr[$param.'_1'] = $data[1] ? '.'.$data[1] : '.00';
         if ($is_wap) {
            $returnArr[$param.'_text'] = ['text'=>$compare_text,'diff'=>str_replace("-", "", $diff).($is_percent?'%':''),'is_percent'=>$is_percent,'is_up'=>($diff>0 ? 1 : ($diff<0 ? -1 : 0))];
        }else{
            if ($diff>0) { // 上升
                $returnArr[$param.'_text'] = $compare_text.'<embed src="./tpl/Merchant/default/static/images/up.svg" class="logo"><span style="color: red">'.$diff.$unit.'</span></embed>';
            }elseif ($diff==0) { // 持平
                $returnArr[$param.'_text'] = $compare_text.L_('持平');
            }else{ // 下降
                $returnArr[$param.'_text'] = $compare_text.'<embed src="./tpl/Merchant/default/static/images/down.svg" class="logo"><span style="color: #63D29D">'. str_replace("-", "", $diff).$unit.'</span></embed>';
            }
        }
        
        return $returnArr;
    }
}
?>