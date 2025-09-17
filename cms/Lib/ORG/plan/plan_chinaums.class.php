<?php
/**
 * 银联分账执行
 * 给入驻商户钱，且给平台分账金额（手续费一笔0.8元由平台出）
 * 需求如下(客户原文)：
 * 		我们这边讨论了下，觉得应该是每天凌晨都跑一遍账单，然后根据商户的金额生成txt文件上传到分账的地方，第二天早上九点半银联那边就会自动进行分账了。跑账单的逻辑是：根据后台设置商户账期，假如商户为3天账期，那就每天凌晨跑商户三天前的账单，假如商户为7天账期，那就每天凌晨跑商户七天前的账单，跑完就把商户的这笔账单修改为已分账。在后台可以添加个字段，用来编辑商户的账期。您这边参考下看这样做可以吗？
 *
 * 把批量生成文件改成报文接口形式调用(调整方案)
 * 每天早上5点钟执行，因为银联那边接口12点到4点是不能调用的
 * author：lumin
 * date：2021-03-01
 */
class plan_chinaums extends plan_base{
	public function runTask(){
		//9:30-19:50  银联这个时间段执行划付操作
		if (time() < strtotime('9:30') || time() > strtotime('19:50')) {
			return true;
		}
		if(C('config.open_chinaums') == '1'){
			if (!is_null(C('config.chinaums_platform_sub_mchid'))) {
                $this->subMchIdFenzhang();
                $this->villageChinaumsDivideHandle();
                return true;
			}

			//1、每次获取2条已经设置银联分账信息的商家
			$current_time = time();
			$service_fee = is_null(C('config.chinaums_fenzhang_fee')) ? 80 : intval(C('config.chinaums_fenzhang_fee'));//每次交易手续费8毛钱 单位：分
			$time = $current_time - 24*3600-1000;//今天执行过的商家就不再检查了
			$merchant_where = [
				'chinaums_merno' => ['neq', ''],
				'chinaums_zg_merno' => ['neq', ''],
				'chinaums_accounting_period' => ['gt', '0'],
				'chinaums_plantime' => ['lt', $time]
			];
			$merchant = M("Merchant")->where($merchant_where)->field("mer_id, chinaums_merno, chinaums_zg_merno, chinaums_accounting_period, chinaums_last_accounting")->limit(20)->order('mer_id asc')->select();
			if($merchant){
				import("@.ORG.pay.Chinaums");
				$pay = new Chinaums();
				$zeroUnix = strtotime(date('Y-m-d'));
				foreach ($merchant as $minfo) {
					//判断当前商家的账期是否到了
					$jiange = ($current_time - $minfo['chinaums_last_accounting'])/86400;
					if($jiange >= $minfo['chinaums_accounting_period']){
						try{
							$merchant_money = M("Merchant_chinaums_record")->where(['mer_id'=>$minfo['mer_id'], 'is_withdraw'=>0,'create_time'=>['LT',$zeroUnix]])->sum("merchant_money");
							$plat_money = M("Merchant_chinaums_record")->where(['mer_id'=>$minfo['mer_id'], 'plat_is_withdraw'=>0,'create_time'=>['LT',$zeroUnix]])->sum("platform_money");
							if($merchant_money > 0 && $plat_money >= $service_fee){
								$merchant_money = $merchant_money + $service_fee;
								$plat_money = $plat_money - $service_fee;
								$merres = $pay->launchLedgerMoney($merchant_money, $plat_money, $minfo['chinaums_zg_merno']);
								if($merres == 1){
									M("Merchant_chinaums_record")->where(['mer_id'=>$minfo['mer_id'], 'is_withdraw'=>0,'create_time'=>['LT',$zeroUnix]])->data(['is_withdraw' => 1])->save();
									M("Merchant_chinaums_record")->data(['mer_id'=>$minfo['mer_id'], 'create_time'=>time(), 'merchant_money'=>0, 'platform_money'=> (-1 * $service_fee), 'is_withdraw'=>1, 'plat_is_withdraw'=>0])->add();
								}
								elseif($merres == 2){
									M("Merchant_chinaums_record")->where(['mer_id'=>$minfo['mer_id'], 'is_withdraw'=>0,'create_time'=>['LT',$zeroUnix]])->data(['is_withdraw' => 1])->save();
									M("Merchant_chinaums_record")->where(['mer_id'=>$minfo['mer_id'], 'plat_is_withdraw'=>0])->data(['plat_is_withdraw'=>1])->save();
								}
								M("Merchant")->data(['chinaums_last_accounting' => $current_time])->where(['mer_id'=>$minfo['mer_id']])->save();
							}
						}
						catch(Exception $e){
							fdump_sql(['mer_id'=>$minfo['mer_id'], 'error'=>$e->getMessage(), 'file'=>$e->getFile()], 'chinaums_fenzhang_error');
						}
					}
					M("Merchant")->data(['chinaums_plantime' => $current_time])->where(['mer_id'=>$minfo['mer_id']])->save();
				}
			}
            $this->villageChinaumsDivideHandle();
		}
		return true;
	}
    
    public function villageChinaumsDivideHandle()
    {
        $village_chinaums_divide_recordDb = M('Village_chinaums_divide_record');
        
        $today=date('Y-m-d');
        $add_time_tmp=strtotime($today);
        $whereStr = 'dividend_status >0 AND dividend_status<3 AND chinaums_zg_mid!="" AND village_money>0 AND add_time<'.$add_time_tmp;
        $divide_village = $village_chinaums_divide_recordDb->where($whereStr)->field('village_id,chinaums_zg_mid')->limit(20)->group('village_id')->select();
        if($divide_village){
            import("@.ORG.pay.Chinaums");
            $pay = new Chinaums();
            foreach ($divide_village as $mvv) {
                $nowtime = time();
                $divide_time_flag=date('YmdHi');
                $whereMoneyStr='village_id='.$mvv['village_id'].' AND dividend_status >0 AND dividend_status<3 AND chinaums_zg_mid!="" AND add_time<'.$add_time_tmp;
                $divide_money = $village_chinaums_divide_recordDb->where($whereMoneyStr)->field('sum(village_money) as d_village_money,sum(platform_money) as d_platform_money,sum(refund_money) as d_refund_money')->find();
                $d_village_money=$divide_money['d_village_money']-$divide_money['d_refund_money'];
                if (!empty($divide_money) &&  $d_village_money>0) {
                    $saveArr = array('divide_time' => $nowtime, 'dividend_status' => 0);
                    $extra_data=array();
                    try {
                        $merres = $pay->launchLedgerMoney($d_village_money, $divide_money['d_platform_money'], $mvv['chinaums_zg_mid']);
                    } catch (\Exception $e) {
                        $merres = $e->getMessage();
                    }
                    if (is_numeric($merres) && strlen($merres) < 3) {
                        if ($merres == 2 || ($merres == 1 && $divide_money['d_platform_money'] <= 0)) {
                            $saveArr['dividend_status'] = 4;
                        } else if ($merres == 1 && $divide_money['d_platform_money'] > 0) {
                            $saveArr['dividend_status'] = 3;  //商家成功了平台的未分
                        }
                    } else {
                        $saveArr['dividend_status'] = 2; //失败了
                    }
                    $saveArr['divide_time_flag']=$divide_time_flag;
                    $saveArr['refund_divide_time']=$nowtime;
                    $extra_data['ret']=$merres;
                    $extra_data['divide_time']= date('Y-m-d H:i:s');
                    $extra_data['divide_money']=$divide_money['d_village_money'];
                    $extra_data['fact_divide_money']=$d_village_money;
                    $extra_data['refund_money']=$divide_money['d_refund_money'];
                    $extra_data['platform_money']=$divide_money['d_platform_money'];
                    $extra_data['chinaums_zg_mid']=$mvv['chinaums_zg_mid'];
                    $saveArr['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                    $village_chinaums_divide_recordDb->where($whereMoneyStr)->data($saveArr)->save();
                }
            }
        }
        return true;
    }
    
    public function villageChinaumsDivideHandleBak()
    {
        return true; 
        /****此种分账作废*****/
        $village_money_listDb = M('Village_money_list');
        $nowtime = time();
        $divide_time_tmp = $nowtime - 200 * 24 * 3600;
        $whereStr = 'dividend_status >0 AND dividend_status<3 AND chinaums_zg_mid!="" AND (divide_time="0" OR divide_time>' . $divide_time_tmp . ')';
        $orderBy = 'divide_time ASC';
        import("@.ORG.pay.Chinaums");
        $pay = new Chinaums();
        $money_list = $village_money_listDb->where($whereStr)->field('*')->limit(20)->order($orderBy)->select();
        if (!empty($money_list)) {
            foreach ($money_list as $mvv) {
                $extra_data = json_decode($mvv['extra_data'], true);
                $chinaums_divide = isset($extra_data['chinaums_divide']) ? $extra_data['chinaums_divide'] : array();
                $saveArr = array('divide_time' => $nowtime, 'dividend_status' => 0);
                //dividend_status 0不在分账  divide_num 已经跑的次数
                $divide_num = !empty($chinaums_divide) && isset($chinaums_divide['divide_num']) ? $chinaums_divide['divide_num'] : 0;

                if (!empty($chinaums_divide) && $divide_num < 500 && isset($chinaums_divide['chinaums_merchant_already_get']) && $chinaums_divide['chinaums_merchant_already_get']) {
                    try {
                        $chinaums_platform_already_get = $chinaums_divide['chinaums_platform_already_get'] > 0 ? $chinaums_divide['chinaums_platform_already_get'] : 0;
                        $merres = $pay->launchLedgerMoney($chinaums_divide['chinaums_merchant_already_get'], $chinaums_platform_already_get, $mvv['chinaums_zg_mid']);
                    } catch (\Exception $e) {
                        $merres = $e->getMessage();
                    }
                    $desc = isset($chinaums_divide['desc']) ? $chinaums_divide['desc'] : '';
                    $chinaums_divide['divide_num'] = $divide_num + 1;
                    if (is_numeric($merres) && strlen($merres) < 3) {
                        if ($merres == 2 || ($merres == 1 && $chinaums_platform_already_get <= 0)) {
                            $saveArr['dividend_status'] = 4;
                            $desc = $desc . '（银联分账成功）';
                        } else if ($merres == 1 && $chinaums_platform_already_get > 0) {
                            $saveArr['dividend_status'] = 3;  //商家成功了平台的未分
                            $chinaums_divide['platform_no_divide'] = 1;
                            $desc = $desc . '（银联分账成功，平台抽成划拨失败）';
                        }
                    } else {
                        $saveArr['dividend_status'] = 2; //失败了
                        $desc = $desc . '（银联分账失败）';
                    }
                    $chinaums_divide['divide_time'] = date('Y-m-d H:i:s');
                    $chinaums_divide['ret'] = $merres;
                    $extra_data['chinaums_divide'] = $chinaums_divide;
                    $saveArr['desc'] = $desc;
                    $saveArr['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                }
                $village_money_listDb->where(array('id' => $mvv['id']))->data($saveArr)->save();
            }
        }
        return true;
    }
	/**
	 * 平台子商户分账
	 *
	 * @return void
	 * @date: 2023/07/18
	 */
	public function subMchIdFenzhang()
	{
		$current_time = time();
		$time = strtotime(date('Y-m-d'));
		$merchant_where = [
			'chinaums_merno' => ['neq', ''],
			'chinaums_zg_merno' => ['neq', ''],
			'chinaums_accounting_period' => ['gt', '0'],
			'chinaums_plantime' => ['lt', $time]
		];
		$merchant = M("Merchant")->where($merchant_where)->field("mer_id, chinaums_merno, chinaums_zg_merno, chinaums_accounting_period, chinaums_last_accounting")->limit(10)->order('mer_id asc')->select();
		if ($merchant) {
			import("@.ORG.pay.Chinaums");
			$pay = new Chinaums();
			foreach ($merchant as $minfo) {
				//判断当前商家的账期是否到了,结算不包括今天的记录
				$jiange = ($time - strtotime(date('Y-m-d', $minfo['chinaums_last_accounting']))) / 86400;
				if ($jiange >= $minfo['chinaums_accounting_period']) {
					try {
						$merchant_unsettle_records = M("Chinaums_fenzhang_record")->where(['mer_id' => $minfo['mer_id'], 'is_withdraw' => 0, 'create_time' => ['LT', $time]])->field("id,merchant_money")->select();
						$plat_unsettle_records = M("Chinaums_fenzhang_record")->where(['mer_id' => $minfo['mer_id'], 'plat_is_withdraw' => 0, 'create_time' => ['LT', $time]])->field("id,platform_money")->select();
						$merchant_money = array_sum(array_column($merchant_unsettle_records, 'merchant_money'));
						$merchant_rids = array_column($merchant_unsettle_records, 'id');
						$plat_money =  array_sum(array_column($plat_unsettle_records, 'platform_money'));
						$plat_rids = array_column($plat_unsettle_records, 'id');

						//划付商家
						if ($merchant_money > 0 && $pay->do202002($merchant_money, $minfo['chinaums_zg_merno'])) {
							M("Chinaums_fenzhang_record")->where(['id' => ['IN', $merchant_rids]])->save(['is_withdraw' => 1]);
						}
						//划付平台
						if ($plat_money > 0 && $pay->do202002($plat_money, C('config.chinaums_platform_sub_uid'))) {
							M("Chinaums_fenzhang_record")->where(['id' => ['IN', $plat_rids]])->save(['plat_is_withdraw' => 1]);
						}
						M("Merchant")->where(['mer_id' => $minfo['mer_id']])->save(['chinaums_last_accounting' => $current_time]);
					} catch (Exception $e) {
					}
					M("Merchant")->where(['mer_id' => $minfo['mer_id']])->save(['chinaums_plantime' => $current_time]);
				}
			}
		}
		return true;
	}

}