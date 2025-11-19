<?php
//商家后台营销统计
class Merchant_revenue_statisticsModel extends Model{
    // 处理数据
    public function getFormatData($business_type,$returnArr){
        switch ($business_type) {
            case 'mall'://商城和外卖格式完全一样
            case 'shop': // 外卖
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
    public function getCompareData($business_type,$returnArr,$returnArrCompare,$is_percent=0,$compare_text='',$is_wap=0){
        /*********************** 订单数据对比  ****************/
        $returnArrFormat = $returnArr;
        $revenue_statistics_database = D('Revenue_statistics');
        switch ($business_type) {
            case 'mall'://商城和外卖格式完全一样
        	case 'shop':
            case 'dining':
        		// 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = C('config.Currency_txt');
                }
                // 营业额
                $returnArr1 = $revenue_statistics_database->getCompare($returnArr['total_money'],$returnArrCompare['total_money'],'total_money',$is_percent,$unit,$compare_text,$is_wap);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 订单收入
                $returnArr1 = $revenue_statistics_database->getCompare($returnArr['mer_income'],$returnArrCompare['mer_income'],'mer_income',$is_percent,$unit,$compare_text,$is_wap);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 活动补贴
                $returnArr1 = $revenue_statistics_database->getCompare($returnArr['discount_money'],$returnArrCompare['discount_money'],'discount_money',$is_percent,$unit,$compare_text,$is_wap);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 损失
                $returnArr1 = $revenue_statistics_database->getCompare($returnArr['loss_money'],$returnArrCompare['loss_money'],'loss_money',$is_percent,$unit,$compare_text,$is_wap);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 客单价
                $returnArr1 = $revenue_statistics_database->getCompare($returnArr['unit_price'],$returnArrCompare['unit_price'],'unit_price',$is_percent,$unit,$compare_text,$is_wap);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = '单';
                }

                // 有效订单
                $returnArr1 = $revenue_statistics_database->getCompare($returnArr['effective_count'],$returnArrCompare['effective_count'],'effective_count',$is_percent,$unit,$compare_text,$is_wap);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 无效订单
                $returnArr1 = $revenue_statistics_database->getCompare($returnArr['invalid_count'],$returnArrCompare['invalid_count'],'invalid_count',$is_percent,$unit,$compare_text,$is_wap);
                $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

                // 单位
                if ($is_percent) { 
                    $unit = '%';
                }else{
                    $unit = '人';
                }
                // 下单用户数
                $returnArr1 = $revenue_statistics_database->getCompare($returnArr['user_count'],$returnArrCompare['user_count'],'user_count',$is_percent,$unit,$compare_text,$is_wap);
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
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['market_total_money'],$returnArrCompare['market_total_money'],'market_total_money',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 活动总支出
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['mer_total_money'],$returnArrCompare['mer_total_money'],'mer_total_money',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 活动商家收入
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['mer_income'],$returnArrCompare['mer_income'],'mer_income',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 活动客单价
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['unit_price'],$returnArrCompare['unit_price'],'unit_price',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

        		// 单位
				if ($is_percent) { 
	        		$unit = '%';
		        }else{
	        		$unit = L_('单');
		        }

		        // 带动订单
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['market_count'],$returnArrCompare['market_count'],'market_count',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

        		// 单位
				if ($is_percent) { 
	        		$unit = '%';
		        }else{
	        		$unit = L_('人');
		        }
		        // 活动老客数
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['regular_customer'],$returnArrCompare['regular_customer'],'regular_customer',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		        // 活动新客数
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['new_customer'],$returnArrCompare['new_customer'],'new_customer',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

		        // 单位
				if ($is_percent) { 
	        		$unit = '%';
		        }else{
	        		$unit = '';
		        }
		        // 投入产出比
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['input_output_rate'],$returnArrCompare['input_output_rate'],'input_output_rate',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		        // 领券数量
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['card_new_coupon_hadpull'],$returnArrCompare['card_new_coupon_hadpull'],'card_new_coupon_hadpull',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);
		        // 核销率
	        	$returnArr1 = $revenue_statistics_database->getCompare($returnArr['coupon_use_rate'],$returnArrCompare['coupon_use_rate'],'coupon_use_rate',$is_percent,$unit,$compare_text,$is_wap);
		        $returnArrFormat = array_merge($returnArrFormat,$returnArr1);

        		break;
        }
        

        return $returnArrFormat;
    }
}
?>