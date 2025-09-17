<?php
/**
 * 店铺service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/27 11:58
 */

namespace app\merchant\model\service;
use app\merchant\model\db\ShopDiscount as ShopDiscountModel;
class ShopDiscountService {
    public $merchantStoreModel = null;
    public function __construct()
    {
        $this->shopDiscountModel = new ShopDiscountModel();
    }

	/**
     * 根据条件获取店铺优惠
     * @param $merId int 商家id
     * @param $store_id array 店铺ID
     * @param $shopType int 店铺类别 1-快店；2-商城
     * @param $usageScenario string 1配送 2自提 0不限 限制用户配送还是自提时才可使用此优惠
     * @param $isFoodshop int 使适用的业务 0-快店；1-餐饮
     * @param $ext_where int 
     * @return array
     */
    public function getDiscounts($merId, $storeId = 0, $shopType = 1, $usageScenario = 0, $isFoodshop = 0, $ext_where = [])
    {
        // 读取缓存
	    $cache = cache();
        $cacheName = 'shop_discount/model_getDiscounts_' . implode('_',func_get_args());
        $returnCache = $cache->get($cacheName);
        if($returnCache){
           return $returnCache;
        }

        //use_type 0:全部商家，1：指定商家
        //use_area 0:全区域，1：指定区域
        //status:0关闭，1：正常
        //source:0平台，1：店铺，2：商家

        $time = time();
        $data = [];
        
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        //1:全区域，全商家
        $allSql = "SELECT * FROM " . $prefix . "shop_discount WHERE (`source` = 0 AND `use_area` = 0 AND `use_type` = 0 AND `is_area` = 0";

        //店铺优惠券没有时间，其他优惠要时间判断
    
        $allSql .= " and start_time <= $time and end_time >= $time";
        if ($storeId) {
            $allSql .= " OR (`source`=1 AND `store_id`={$storeId})) AND `status` = 1";
        } else {
            $allSql .= " ) AND `status` = 1";
        }

        // 适用的业务
        if ($isFoodshop==0) {
            $allSql .= " AND `use_limit` <> 1";
        }else{
            $allSql .= " AND `use_limit` <> 0";
        }

        // 店铺种类，1-快店；2-商城
        $shopType = $shopType ? $shopType : 1;
        if ($shopType == 1) {
            $allSql .= ' and shop_type=1';
        } elseif ($shopType == 2) {
            $allSql .= ' and shop_type=2';
        }

        // 1配送 2自提 0不限 限制用户配送还是自提时才可使用此优惠
        if($usageScenario > 0){
            $allSql .= " and ( usage_scenario = 0 or usage_scenario = $usageScenario )";
        }

        // 优惠类型(0:新单，1：满减，2：配送)
        if (isset($ext_where['type'])) {
            $allSql .= ' and type=' . intval($ext_where['type']);
        }

        // 是否开启使用百分比折扣优惠 1是 0否
        if (isset($ext_where['percentage_status'])) {
            $allSql .= ' and percentage_status=' . intval($ext_where['percentage_status']);
        }

        //1:全区域，全商家
        $result = $this->shopDiscountModel->query($allSql);
        foreach ($result as $row) {
            $data[$row['store_id']][] = $row;
        }

        //2.全区域，指定商家
        //4.指定区域，指定商家
        $sql = "SELECT d.*,m.store_ids FROM " . $prefix. "shop_discount AS d INNER JOIN " . $prefix . "shop_discount_merchant AS m ON d.id=m.did WHERE `status`=1 AND use_type=1 AND m.mer_id=" . $merId;
        if ($shopType == 1) {
            $sql .= ' and shop_type=1';
        } elseif ($shopType == 2) {
            $sql .= ' and shop_type=2';
        }

        if($usageScenario > 0){
            $sql .= " and ( usage_scenario = 0 or usage_scenario = $usageScenario )";
        }

        $sql .= " and start_time <= $time and end_time >= $time";

        if (isset($ext_where['type'])) {
            $sql .= ' and d.type=' . intval($ext_where['type']);
        }
        if (isset($ext_where['percentage_status'])) {
            $sql .= ' and d.percentage_status=' . intval($ext_where['percentage_status']);
        }
        // 适用的业务
        if ($isFoodshop==0) {
            $sql .= " AND `use_limit` <> 1";
        }else{
            $sql .= " AND `use_limit` <> 0";
        }

        $result = $this->shopDiscountModel->query($sql);
        foreach ($result as $row) {
            $store_ids = array();
            if($row['store_ids']){
                $store_ids = explode(',',$row['store_ids']);
            }
            if(empty($store_ids) || in_array($storeId,$store_ids)){
                $data[$row['store_id']][] = $row;
            }
        }
        
        //3.指定区域，全部商家
        if(isset($merchant['province_id'])){

            $merchant = (new MerchantService())->getMerchantByMerId($merId);
            $sql = "SELECT * FROM " . $prefix. "shop_discount AS d INNER JOIN " . $prefix. "shop_discount_area AS a ON a.did=d.id WHERE `status`=1 AND use_type=0 AND a.aid IN (" . $merchant['province_id'] . ',' . $merchant['city_id'] . ',' . $merchant['area_id'] . ')';
            if ($shopType == 1) {
                $sql .= ' and shop_type=1';
            } elseif ($shopType == 2) {
                $sql .= ' and shop_type=2';
            }

            // 适用的业务
            if ($isFoodshop==0) {
                $sql .= " AND `use_limit` <> 1";
            }else{
                $sql .= " AND `use_limit` <> 0";
            }

            if($usageScenario > 0){
                $sql .= " and ( usage_scenario = 0 or usage_scenario = $usageScenario )";
            }

            $sql .= " and start_time <= $time and end_time >= $time";
            if (isset($ext_where['type'])) {
                $sql .= ' and d.type=' . intval($ext_where['type']);
            }
            if (isset($ext_where['percentage_status'])) {
                $sql .= ' and d.percentage_status=' . intval($ext_where['percentage_status']);
            }
            $result = $this->shopDiscountModel->query($sql);
            foreach ($result as $row) {
                $data[$row['store_id']][] = $row;
            }

        }
        $cache->set($cacheName, $data, 30);
        return $data;
    }

    /**
     * 获取优惠买单优惠信息
     * @param $discount array 其它店铺表名
     * @return array
     */
    public function getStoreQuickDiscount($discount) {
        if(empty($discount)){
           return [];
        }
        // discount_type 1折扣2满减
        // discount_percent 折扣率
        // condition_price 满减条件
        // minus_price 满减金额
        $returnArr =  [];
        if($discount['discount_type'] == 1){
            $returnArr['type'] = $discount['discount_type'];
            $returnArr['name'] = '买单立享'.$discount['discount_percent'].'折';
        }elseif($discount['discount_type'] == 2){
            $returnArr['type'] = $discount['discount_type'];
            if($discount['condition_price'] > 0){
                $returnArr['name'] = '买单每满￥'.$discount['condition_price'].'减￥'.$discount['minus_price'];
            }else{
                $returnArr['name'] = '买单立减￥'.$discount['minus_price'];
            }
        }
        return $returnArr; 
    }

    
    /**
     * 格式化店铺优惠信息
     * @param $discount array 店铺优惠
     * @return array
     */
    public function formartDiscount($discounts, $storeId) {
        if(empty($discounts)){
           return [];
        }

        $returnArr = [];
        if(isset($discounts[0])){
            // 平台优惠
            foreach ($discounts[0] as $row_d) {

                if ($row_d['type'] == 0) {//新单
                    $returnArr['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                } elseif ($row_d['type'] == 1 ) {//满减
                    $returnArr['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                } elseif ($row_d['type'] == 2 ) {//配送
                    $returnArr['coupon_list']['delivery'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                }
            }
        }
       

        // 店铺优惠
        if($storeId){
            if (isset($discounts[$storeId]) && $discounts[$storeId]) {
                foreach ($discounts[$storeId] as $row_m) {
                    if ($row_m['type'] == 0) {
                        $returnArr['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']), 'order_count' => $row_m['order_count'], 'usage_scenario' => $row_m['usage_scenario']);
                    } elseif ($row_m['type'] == 1) {
                        $returnArr['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']), 'order_count' => $row_m['order_count'], 'usage_scenario' => $row_m['usage_scenario']);
                    }
                }
            }
        }
        return $returnArr; 
    }

    /**
     * 解析优惠信息
     * @param $discount array 店铺优惠
     * @return array
     */
    public function simpleParseCoupon($obj){
		$returnArr = array();
        if(empty($obj)){
            return $returnArr;
        }

        foreach ($obj as $key => $value) {
            if ($key == 'invoice') {
				$returnArr[] = array(
					'type' => 'invoice',
					'text' => L_('票'),
				);
            } elseif ($key == 'discount') {
				$returnArr[] = array(
					'type' => 'discount',
					'text' => L_('全场X1折', $obj[$key]),
				);
            } elseif ($key == 'isdiscountgoods') {
				$returnArr[] = array(
					'type' => 'isdiscountgoods',
					'text' => L_('限时优惠'),
				);
            } elseif ($key == 'isdiscountsort') {
				$returnArr[] = array(
					'type' => 'isdiscountsort',
					'text' => L_('折扣优惠'),
				);
            } elseif ($key == 'delivery_free') {
                $Currency_symbol = cfg('Currency_symbol');
                if ($value['money']>0) {
                    $d_text = L_('满'.$Currency_symbol.'X1免配送费',$value['money']);
                }else{
                    $d_text = L_('免配送费');
                }
                $returnArr[] = array(
                    'type' => 'delivery_free',
                    'text' => $d_text,
                );
            } else {
                foreach ($obj[$key] as $k => $v) {
                	if (isset($v['percentage_status'])&&$v['percentage_status']==1) {
						if($obj[$key][$k]['money']>0){
							$dis_text = '享X2折';
						}else{
							$dis_text = 'X2折';
						}
                        if(isset($v['type']) && $v['type'] == 2){
                            $dis_text = L_('配送费').$dis_text;
                        }
                        $dis_num = round($v['minus']*10,2);
                    }elseif(isset($v['type'])&&$v['type']==2){
                        $dis_text = '减'.cfg('Currency_symbol').'X2运费';
                        $dis_num = round($v['minus'],2);
                    }else{
                        $dis_text = '减'.cfg('Currency_symbol').'X2';
                        $dis_num = isset($v['minus']) ? round($v['minus'],2) : 0;
                    }

                    if($key == 'delivery'){
                    	if(isset($obj[$key][$k]['order_count'])&&$obj[$key][$k]['order_count'] >= 1){
	                        $returnArr[] = array(
								'type' => 'delivery',
								'text' => L_('平台首X0单满'.cfg('Currency_symbol').'X1'.$dis_text.'运费',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
							);
                    	}elseif(isset($obj[$key][$k]['order_count'])&&$obj[$key][$k]['order_count'] == 1){
	                        $returnArr[] = array(
								'type' => 'delivery',
								'text' => L_('平台首单满'.cfg('Currency_symbol').'X1'.$dis_text.'运费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
							);
                    	}else{
							if($obj[$key][$k]['order_count'] >0){
								$returnArr[] = array(
									'type' => 'delivery',
									'text' => L_('满'.cfg('Currency_symbol').'X1'.$dis_text.'运费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
								);
							}else{
								$returnArr[] = array(
									'type' => 'delivery',
                                    'text' => L_('满'.cfg('Currency_symbol').'X1'.$dis_text.'运费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
								);
							}
                    	}
                    } else if($key == 'system_newuser'){
                        if($obj[$key][$k]['order_count'] == 1){
                            $returnArr[] = array(
                                'type' => 'system_newuser',
                                'text' => L_('平台首单满'.cfg('Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }else{
                            $returnArr[] = array(
                                'type' => 'system_newuser',
                                'text' => L_('平台首X0单满'.cfg('Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }
                    } else if($key == 'newuser'){
                    	if($obj[$key][$k]['order_count'] == 1){
                            if ($obj[$key][$k]['money'] > 0) {
                                $returnArr[] = array(
                                    'type' => 'newuser',
                                    'text' => L_('店铺首单满' . cfg('Currency_symbol') . 'X1' . $dis_text, array('X1' => $obj[$key][$k]['money'], 'X2' => $dis_num)),
                                );
                            } else {
                                $returnArr[] = array(
                                    'type' => 'newuser',
                                    'text' => L_('店铺首单' . $dis_text, array('X2' => $dis_num)),
                                );
                            }
                        }else{
                        	if($obj[$key][$k]['order_count']){
                                if ($obj[$key][$k]['money'] > 0) {
                                    $returnArr[] = array(
                                        'type' => 'newuser',
                                        'text' => L_('店铺首X0单满'.cfg('Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                    );
                                }else{
                                    $returnArr[] = array(
                                        'type' => 'newuser',
                                        'text' => L_('店铺首X0单'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X2'=>$dis_num)),
                                    );
                                }
		                    }
		                    else{
		                        if($obj[$key][$k]['money'] > 0){
                                    $returnArr[] = array(
                                        'type' => 'newuser',
                                        'text' => L_('店铺首单满'.cfg('Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                    );
                                }else{
                                    $returnArr[] = array(
                                        'type' => 'newuser',
                                        'text' => L_('店铺首单'.$dis_text,array('X2'=>$dis_num)),
                                    );
                                }
		                    }
                        }
                    } else {
						
                    	if(isset($v['order_count'])&&$v['order_count'] > 1){
                            if ($obj[$key][$k]['money'] > 0) {
                                $returnArr[] = array(
                                    'type' => 'minus',
                                    'text' => L_('首X0单满' . cfg('Currency_symbol') . 'X1' . $dis_text, array('X0' => $obj[$key][$k]['order_count'], 'X1' => $obj[$key][$k]['money'], 'X2' => $dis_num)),
                                );
                            }else{
                                $returnArr[] = array(
                                    'type' => 'minus',
                                    'text' => L_('首X0单'. $dis_text, array('X0' => $obj[$key][$k]['order_count'], 'X2' => $dis_num)),
                                );
                            }
                    	}elseif(isset($v['order_count'])&&$v['order_count'] == 1){
                            if ($obj[$key][$k]['money'] > 0) {
                                $returnArr[] = array(
                                    'type' => 'minus',
                                    'text' => L_('首单满'.cfg('Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                );
                            }else{
                                $returnArr[] = array(
                                    'type' => 'minus',
                                    'text' => L_('首单'.$dis_text,array('X2'=>$dis_num)),
                                );
                            }
                    	}else{
							if(isset($obj[$key][$k]['money'])&&$obj[$key][$k]['money']>0){
								if(isset($v['usage_scenario'])&&$v['usage_scenario'] == 2){
									$returnArr[] = array(
										'type' => 'minus',
										'text' => L_('自取满'.cfg('Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
									);
								}else{
									$returnArr[] = array(
										'type' => 'minus',
										'text' => L_('满'.cfg('Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
									);
								}
							}else{
								if(isset($v['usage_scenario'])&&$v['usage_scenario'] == 2){
									$returnArr[] = array(
										'type' => 'minus',
										'text' => L_('自取'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
									);
								}else{
									$returnArr[] = array(
										'type' => 'minus',
										'text' => L_('无门槛'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
									);
								}	
							}	
                    	}
                    }
                }
            }
        }
		return $returnArr;
    }

    
    /**
     * 获得一条符合条件的满减优惠
     * @param   $discounts        优惠列表
     * @param   $type             优惠类型0:新单，1：满减，2：配送
     * @param   $price            可优惠金额
     * @param   $storeId         店铺id
     * @param   $isDiscount      是否已打过折
     * @param   $orderCount      优惠程度 限制几单享受优惠 0为不限制
     * @param   $percentageStatus     是否使用百分比折扣优惠 1是 0否
     * @return array
    */
    public function getReduce($discounts, $type, $price, $storeId = 0, $isDiscount = 1, $orderCount = 0, $percentageStatus = 0)
    {
        $reduceMoney = 0;

        if($percentageStatus == 1){// 百分比默认1
            $reduceMoney = 1;
        }

        $returnArr = null;
        if (!isset($discounts[$storeId])) {//没有可用满减
            return $returnArr;
        }

        foreach ($discounts[$storeId] as $row) {
            if ($type == 0) {
                // 首单优惠首一单首二单
                if ($row['order_count'] > $orderCount && $row['percentage_status'] == $percentageStatus && ($row['is_share'] || $isDiscount == 0)) {
                    if ($price >= $row['full_money']) {
                        // 平台优惠
                        if($percentageStatus == 1){
                            //折扣
                            if ($reduceMoney > $row['reduce_money']) {//获得最优折扣
                                $reduceMoney = $row['reduce_money'];
                                $returnArr = $row;
                            }
                        }else{
                            if ($reduceMoney < $row['reduce_money']) {//获得最大满减优惠
                                $reduceMoney = $row['reduce_money'];
                                $returnArr = $row;
                            }
                        }
                    }
                }
            } else {
                if($percentageStatus == 1){
                    //折扣
                    if ($row['type'] == $type && $row['order_count'] == 0 && $row['percentage_status'] == $percentageStatus && ($row['is_share'] || $isDiscount == 0)) {
                        if ($price >= $row['full_money']) {
                            if ($reduceMoney > $row['reduce_money']) {
                                $reduceMoney = $row['reduce_money'];
                                $returnArr = $row;
                            }
                        }
                    }

                }else{

                    if ($row['type'] == $type && $row['order_count'] == 0 && $row['percentage_status'] == $percentageStatus && ($row['is_share'] || $isDiscount == 0)) {
                        if ($price >= $row['full_money']) {
                            if ($reduceMoney < $row['reduce_money']) {
                                $reduceMoney = $row['reduce_money'];
                                $returnArr = $row;
                            }
                        }
                    }
                }
            }
        }
        
        return $returnArr;
    }

    /**
     * 获得符合条件的满减优惠
     * @param   $discounts        优惠列表
     * @param   $type             优惠类型0:新单，1：满减，2：配送
     * @param   $price            可优惠金额
     * @param   $storeId         店铺id
     * @param   $isDiscount      是否已打过折
     * @param   $orderCount      优惠程度 限制几单享受优惠 0为不限制
     * @return array
    */
    public function getNoShareReduce($discounts, $type, $price, $storeId = 0, $isDiscount = 1, $orderCount = 0)
    {
        $return = null;
        if (isset($discounts[$storeId])) {
            foreach ($discounts[$storeId] as $row) {
                if ($type == 0) {
                    if ($row['order_count'] > $orderCount && $isDiscount) {
                        if ($price >= $row['full_money']) {
                            $return[] = $row;
                        }
                    }
                } else {
                    if ($row['type'] == $type && $isDiscount) {
                        if ($price >= $row['full_money']) {
                            $return[] = $row;
                        }
                    }
                }
            }
        }
        return $return;
    }

    /**
     * 获得符合条件的满减优惠
     * @param   $discounts        优惠列表
     * @param   $canDiscountMoney        可参与折扣金额
     * @param   $storeId         店铺id
     * @param   $user             用户信息
     * @param   $storeOrderCount      用户店铺已下单数
     * @param   $systemOrderCount      用户平台已下单数
     * @param   $isDiscount
     * @return array
    */
    public function getDiscountList($discounts, $canDiscountMoney, $storeId, $user, $storeOrderCount = 0, $systemOrderCount = 0, $isDiscount = 0)
    {
        $returnArr = [];

        // 优惠列表
        $discountList = [];

        //优惠
        $systemFirstReduce = 0;//平台首单优惠
        $storeFirstReduce = 0;//店铺首单优惠
        $systemFullReduce = 0;//平台满减
        $storeFullReduce = 0;//店铺满减
        $platformMerchant = 0;//平台优惠中商家补贴的总和统计
        $platformPlat = 0;//平台优惠中平台补贴的总和统计

        $minusTotal = $canDiscountMoney;

        // 店铺优惠在前，平台优惠在后
        if (empty($storeOrderCount)) {//店铺首单
            if ($storeTmp = $this->getReduce($discounts, 0, $canDiscountMoney, $storeId, $isDiscount)) {
                    $discountTemp = [];
                    $minus = get_format_number(min($storeTmp['reduce_money'],$minusTotal));
                    $discountTemp['discount_type'] = 3;//店铺首单
                    $discountTemp['name'] = $storeTmp['name'];
                    $discountTemp['money'] = $storeTmp['full_money'];
                    $discountTemp['minus'] = $minus;
                    $discountTemp['old_minus'] = $storeTmp['reduce_money'];
                    $discountTemp['did'] = $storeTmp['checkCartid'];
                    $discountTemp['plat_money'] = $storeTmp['plat_money'];
                    $discountTemp['merchant_money'] = $storeTmp['merchant_money'];
                    $discountTemp['order_count'] = $storeTmp['order_count'];
                    $discountTemp['type'] = $storeTmp['type'];
                    $discountList['newuser'] = $discountTemp;
                    $storeFirstReduce += $minus;
                    $minusTotal -= $minus;

            }
        }

        // 店铺折扣
        if ($minusTotal > 0 && $storeTmp = $this->getReduce($discounts, 1, $canDiscountMoney, $storeId, $isDiscount,'',1)) {

            $minus2 = round($canDiscountMoney * (1 - $storeTmp['reduce_money']),2);
            $minus = get_format_number(min($minus2,$minusTotal));
            $discountTemp = array();
            $discountTemp['discount_type'] = 5;//店铺折扣
            $discountTemp['name'] = $storeTmp['name'];
            $discountTemp['money'] = $storeTmp['full_money'];
            $discountTemp['minus'] = $storeTmp['reduce_money'];
            $discountTemp['minus2'] = $minus;//减了多少
            $discountTemp['did'] = $storeTmp['id'];
            $discountTemp['plat_money'] = $storeTmp['plat_money'];
            $discountTemp['merchant_money'] = $storeTmp['merchant_money'];
            $discountTemp['order_count'] = $storeTmp['order_count'];
            $discountTemp['type'] = $storeTmp['type'];
            $discountList['sto_discount'] = $discountTemp;

            //打折 优惠的价格=价格*(1-折扣率)
            $storeFullReduce += $discountTemp['minus2'];
            $minusTotal -= $minus;
        }

        // 店铺满减
        if ($minusTotal > 0 && $storeTmp = $this->getReduce($discounts, 1, $canDiscountMoney, $storeId, $isDiscount)) {
            // 折扣与满减不同享
            if (!isset($discountList['sto_discount']) || $discountList['sto_discount']['minus2'] < $storeTmp['reduce_money']) {
                if (isset($discountList['sto_discount'])&&$discountList['sto_discount']) {
                    $storeFullReduce -= floatval($discountList['sto_discount']['minus2']);
                }
                $minus = get_format_number(min($storeTmp['reduce_money'],$minusTotal));
                $discountTemp = array();
                $discountTemp['discount_type'] = 4;//店铺满减
                $discountTemp['name'] = $storeTmp['name'];
                $discountTemp['money'] = $storeTmp['full_money'];
                $discountTemp['minus'] = $minus;
                $discountTemp['old_minus'] = $storeTmp['reduce_money'];
                $discountTemp['did'] = $storeTmp['id'];
                $discountTemp['plat_money'] = $storeTmp['plat_money'];
                $discountTemp['merchant_money'] = $storeTmp['merchant_money'];
                $discountTemp['order_count'] = $storeTmp['order_count'];
                $discountTemp['type'] = $storeTmp['type'];
                $discountList['minus'] = $discountTemp;
                $storeFullReduce += $minus;
                $minusTotal -= $minus;
            }
        }

        // 平台折扣
        if ($minusTotal > 0 && $storeTmp = $this->getReduce($discounts, 1, $canDiscountMoney, 0, $isDiscount, '', 1)) {
            $minus2 = round(($canDiscountMoney-$storeFullReduce-$storeFirstReduce) * (1 - $storeTmp['reduce_money']),2);
            $minus = get_format_number(min($minus2,$minusTotal));
            $discountTemp = array();
            $discountTemp['discount_type'] = 6;//平台满额打折
            $discountTemp['name'] = $storeTmp['name'];
            $discountTemp['money'] = $storeTmp['full_money'];
            $discountTemp['minus'] = $storeTmp['reduce_money'];
            $discountTemp['minus2'] = $minus;//减了多少
            $discountTemp['did'] = $storeTmp['id'];
            $discountTemp['plat_money'] = $storeTmp['plat_money'];
            $discountTemp['merchant_money'] = $storeTmp['merchant_money'];
            $discountTemp['order_count'] = $storeTmp['order_count'];
            $discountTemp['percentage_status'] = $storeTmp['percentage_status'];
            $discountTemp['type'] = $storeTmp['type'];
            $discountTemp['minus2'] =  $discountTemp['minus2']>0 ?  $discountTemp['minus2'] : 0;
            $discountList['plat_discount'] = $discountTemp;

            //打折 优惠的价格=价格*(1-折扣率)
            $systemFullReduce += $minus;
            $minusTotal -= $minus;
            $platformMerchant =  round($minus * $storeTmp['merchant_money'],2);//平台优惠中商家补贴的
            $platformPlat = round($minus * $storeTmp['plat_money'],2);//平台优惠中平台补贴的
        }

        $ok = 0;
        if ($discounts) {
            foreach ($discounts as $key => $value) {
                foreach ($value as $k => $v) {
                    if ($v['order_count'] > $systemOrderCount) {
                        $ok = 1;
                    }
                }
            }
        }
        if ($ok && $user) {//平台首单优惠折扣
            if ($minusTotal > 0 && $storeTmp = $this->getReduce($discounts, 0, $canDiscountMoney, 0, $isDiscount, $systemOrderCount,1)) {
                $can_discount_money = ($canDiscountMoney-$storeFullReduce-$storeFirstReduce-$systemFullReduce);

                $minus2 = round($can_discount_money * (1 - $storeTmp['reduce_money']),2);
                $minus = get_format_number(min($minus2,$minusTotal));
                $discountTemp = array();
                $discountTemp['discount_type'] = 7;//店铺折扣
                $discountTemp['name'] = $storeTmp['name'];
                $discountTemp['money'] = $storeTmp['full_money'];
                $discountTemp['minus'] = $storeTmp['reduce_money'];
                $discountTemp['minus2'] = $minus; //减了多少
                $discountTemp['did'] = $storeTmp['id'];
                $discountTemp['plat_money'] = $storeTmp['plat_money'];
                $discountTemp['merchant_money'] = $storeTmp['merchant_money'];
                $discountTemp['order_count'] = $storeTmp['order_count'];
                $discountTemp['percentage_status'] = $storeTmp['percentage_status'];
                $discountTemp['type'] = $storeTmp['type'];
                $discountTemp['minus2'] =  $discountTemp['minus2']>0 ?  $discountTemp['minus2'] : 0;
                $discountList['plat_newuser_discount'] = $discountTemp;
                
                if ($storeTmp['plat_money'] > 0 || $storeTmp['merchant_money']) {
                    $systemFirstReduce += get_format_number($discountTemp['minus2'] * $storeTmp['plat_money']);
                    $platformPlat += get_format_number($discountTemp['minus2'] * $storeTmp['plat_money']);
                    $storeFirstReduce += get_format_number($discountTemp['minus2'] * $storeTmp['merchant_money']);
                    $platformMerchant += get_format_number($discountTemp['minus2'] * $storeTmp['merchant_money']);
                } else {
                    $systemFirstReduce += $minus;
                    $platformPlat += $minus;
                }
                $minusTotal -= $minus;
            }
            if ($storeTmp = $this->getNoShareReduce($discounts, 1, $canDiscountMoney, 0, $isDiscount, $systemOrderCount)) {
                foreach ($storeTmp as $dt) {
                    if ($dt['is_share'] == 0) {
                        $noDiscountList[] = array('type' => 1, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                    }
                }
            }
        }

        if ($ok && $user) {//平台首单优惠
            if ($minusTotal > 0 && $storeTmp = $this->getReduce($discounts, 0, $canDiscountMoney, 0, $isDiscount, $systemOrderCount)) {
                if (!$discountList['plat_newuser_discount'] || $discountList['plat_newuser_discount']['minus2'] < $storeTmp['reduce_money']) {
                    if ($discountList['plat_newuser_discount']) {
                        $systemFirstReduce += $storeTmp['plat_money'];
                        $platformPlat += $storeTmp['plat_money'];
                        $storeFirstReduce += $storeTmp['merchant_money'];
                        $platformMerchant += $storeTmp['merchant_money'];

                        if ($storeTmp['plat_money'] > 0 || $storeTmp['merchant_money']) {
                            $minus2 = $discountList['plat_newuser_discount']['minus2'];
                            $systemFirstReduce -= get_format_number($minus2 * $discountList['plat_newuser_discount']['plat_money']);
                            $storeFirstReduce -= get_format_number($minus2 * $discountList['plat_newuser_discount']['merchant_money']);
                            $platformMerchant -= get_format_number($minus2 * $discountList['plat_newuser_discount']['merchant_money']);
                            $platformPlat -=  get_format_number($minus2 * $discountList['plat_newuser_discount']['plat_money']);
                        }else{
                            $systemFirstReduce -= $discountList['plat_newuser_discount']['minus2'];
                            $platformPlat -= $discountList['plat_newuser_discount']['minus2'];
                        }
                    }
                    $minus = get_format_number(min($storeTmp['reduce_money'],$minusTotal));
                    $discountTemp = array();
                    $discountTemp['discount_type'] = 1;//平台首单
                    $discountTemp['name'] = $storeTmp['name'];
                    $discountTemp['money'] = $storeTmp['full_money'];
                    $discountTemp['minus'] = $minus;
                    $discountTemp['old_minus'] = $storeTmp['reduce_money'];
                    $discountTemp['did'] = $storeTmp['id'];
                    $discountTemp['plat_money'] = $storeTmp['plat_money'];
                    $discountTemp['merchant_money'] = $storeTmp['merchant_money'];
                    $discountTemp['order_count'] = $storeTmp['order_count'];
                    $discountTemp['type'] = $storeTmp['type'];
                    $minusTotal -= $minus;
                    $discountList['system_newuser'] = $discountTemp;
                    if ($storeTmp['plat_money'] > 0 || $storeTmp['merchant_money']) {
                        $platMoney = get_format_number($minus*$storeTmp['plat_money']/($storeTmp['plat_money']+$storeTmp['merchant_money']));
                        $merchaMoney = get_format_number($minus*$storeTmp['merchant_money']/($storeTmp['plat_money']+$storeTmp['merchant_money']));
                        $systemFirstReduce += $platMoney;
                        $platformPlat += $platMoney;
                        $storeFirstReduce += $merchaMoney;
                        $platformMerchant += $merchaMoney;
                    } else {
                        $systemFirstReduce += $minus;
                        $platformPlat += $minus;
                    }
                }
            }
        }

        if ($minusTotal > 0 && $storeTmp = $this->getReduce($discounts, 1, $canDiscountMoney, 0, $isDiscount)) {
            // 折扣与满减不同享
            if (!isset($discountList['plat_discount']) || $discountList['plat_discount']['minus2'] < $storeTmp['reduce_money']) {
                if (isset($discountList['plat_discount']) && $discountList['plat_discount']) {
                    $storeFullReduce -= floatval($discountList['plat_discount']['minus2']);
                    $platformMerchant -= round($discountList['plat_discount']['minus2'] * $discountList['plat_discount']['plat_money'],2);
                    $platformPlat -=  round($discountList['plat_discount']['minus2'] * $discountList['plat_discount']['merchant_money'],2);
                }
                //平台优惠中平台补贴的

                $minus = get_format_number(min($storeTmp['reduce_money'],$minusTotal));
                $discountTemp = array();
                $discountTemp['discount_type'] = 2;//平台满减
                $discountTemp['name'] = $storeTmp['name'];
                $discountTemp['money'] = $storeTmp['full_money'];
                $discountTemp['minus'] = $minus;
                $discountTemp['old_minus'] = $storeTmp['reduce_money'];
                $discountTemp['did'] = $storeTmp['id'];
                $discountTemp['plat_money'] = $storeTmp['plat_money'];
                $discountTemp['merchant_money'] = $storeTmp['merchant_money'];
                $discountTemp['order_count'] = $storeTmp['order_count'];
                $discountTemp['type'] = $storeTmp['type'];
                $discountList['system_minus'] = $discountTemp;
                if ($storeTmp['plat_money'] > 0 || $storeTmp['merchant_money']) {
                    $platMoney = get_format_number($minus*$storeTmp['plat_money']/($storeTmp['plat_money']+$storeTmp['merchant_money']));
                    $merchaMoney = get_format_number($minus*$storeTmp['merchant_money']/($storeTmp['plat_money']+$storeTmp['merchant_money']));
                    $systemFullReduce += $platMoney;
                    $platformPlat += $platMoney;
                    $storeFullReduce += $merchaMoney;
                    $platformMerchant += $merchaMoney;
                } else {
                    $systemFullReduce += $minus;
                    $platformPlat += $minus;
                }
            }
            
        }
        
        $returnArr['discountList'] =  $discountList;
        $returnArr['systemFirstReduce'] =  get_format_number($systemFirstReduce);
        $returnArr['storeFirstReduce'] =  get_format_number($storeFirstReduce);
        $returnArr['systemFullReduce'] =  get_format_number($systemFullReduce);
        $returnArr['storeFullReduce'] =  get_format_number($storeFullReduce);
        $returnArr['platformMerchant'] =  get_format_number($platformMerchant);
        $returnArr['platformPlat'] =  get_format_number($platformPlat);
        return $returnArr;
    }
    
    /**
     * 满减优惠格式化内容
     * @param   $discountList        优惠列表
     * @return array
    */
    public function discountFormart($discountList='')
    {
        if(!$discountList){
            return [];
        }

        $newDiscountList = array();
        foreach ($discountList as $key => $dval) {
            if (isset($dval['percentage_status'])&&$dval['percentage_status']==1) {
                $dis_text = '享X2折';
                $dis_num = floatval($dval['minus']*10);
            }else{
                $dis_text = '减X2';
                $dis_num = cfg('Currency_symbol') . floatval(isset($dval['old_minus'] ) && $dval['old_minus'] ? $dval['old_minus'] : $dval['minus']);
            }
            switch ($key) {
                case 'sto_discount'://店铺折扣
                    if (get_format_number($dval['money'])) {
                        $text = L_('商品满X1,享X2折', array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => get_format_number($dval['minus'] * 10)));
                    }else{
                        $text = L_('商品享X2折', array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => get_format_number($dval['minus'] * 10)));
                    }
                    $newDiscountList[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => get_format_number($dval['minus2']));
                    break;
                case 'plat_discount':
                    if (get_format_number($dval['money'])) {
                        $text = L_('商品满X1,享X2折', array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => get_format_number($dval['minus'] * 10)));
                    }else{
                        $text = L_('商品享X2折', array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => get_format_number($dval['minus'] * 10)));
                    }
                    $newDiscountList[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => get_format_number($dval['minus2']));
                    break;
                case 'newuser':  
                    if ($dval['order_count'] == 1) {
                        if (get_format_number($dval['money'])>0) {
                            $pre_text = '店铺首单满X1';
                        }else{
                            $pre_text = '店铺首单';
                        }
                        $text = L_($pre_text.$dis_text, array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => $dis_num));
                    } else { 
                        if ($dval['money']) {
                            $pre_text = '店铺首X0单满X1';
                        }else{
                            $pre_text = '店铺首X0单';
                        }
                        $text = L_($pre_text.$dis_text, array('X0' => $dval['order_count'], 'X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => $dis_num));
                        
                    }
                    $newDiscountList[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => get_format_number($dval['minus']));
                    break;
                case 'minus':
                        if (get_format_number($dval['money'])>0) {
                            $pre_text = '店铺优惠满X1';
                        }else{
                            $pre_text = '店铺优惠';
                        }
                    $text = L_($pre_text.$dis_text, array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => $dis_num));
                    $newDiscountList[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => get_format_number($dval['minus']));
                    break;
                case 'system_newuser':
                    if ($dval['order_count'] == 1) {
                        if (get_format_number($dval['money'])>0) {
                            $pre_text = '平台首单满X1';
                        }else{
                            $pre_text = '平台首单';
                        }
                        $text = L_($pre_text.$dis_text, array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => $dis_num));
                    } else {
                        if (get_format_number($dval['money'])>0) {
                            $pre_text = '平台首X0单满X1';
                        }else{
                            $pre_text = '平台首X0单';
                        }
                        $text = L_($pre_text.$dis_text, array('X0' => $dval['order_count'], 'X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => $dis_num));
                    }
                    $newDiscountList[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => get_format_number($dval['minus']));
                    break;
                case 'plat_newuser_discount':
                    if ($dval['order_count'] == 1) {
                        if (get_format_number($dval['money'])>0) {
                            $pre_text = '平台首单满X1';
                        }else{
                            $pre_text = '平台首单';
                        }
                        $text = L_($pre_text.$dis_text, array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => $dis_num));
                    } else {
                        if (get_format_number($dval['money'])>0) {
                            $pre_text = '平台首X0单满X1';
                        }else{
                            $pre_text = '平台首X0单';
                        }
                        $text = L_($pre_text.$dis_text, array('X0' => $dval['order_count'], 'X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => $dis_num));
                    }
                    $newDiscountList[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => get_format_number($dval['minus2']));
                    break;
                case 'system_minus':
                        if (get_format_number($dval['money'])>0) {
                            $pre_text = '平台优惠满X1';
                        }else{
                            $pre_text = '平台优惠';
                        }
                    $text = L_($pre_text.$dis_text, array('X1' => cfg('Currency_symbol') . get_format_number($dval['money']), 'X2' => $dis_num));
                    $newDiscountList[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => get_format_number($dval['minus']));
                    break;
            }
        }
        return $newDiscountList;
    }
 

     /**
     * 解析优惠信息
     * @param $discount array 店铺优惠
     * @param $type string 
     * @return array
     */
	public function parseCoupon($obj, $type = '')
    {	
        $returnObj = array();
        foreach ($obj as $key => $value) {
            if ($key == 'invoice') {
                $returnObj[$key] = L_('满X1支持开发票，请在下单时填写发票抬头',cfg('Currency_symbol') . $obj[$key]);
            } elseif ($key == 'discount') {
                $returnObj[$key] = L_('店内全场X1折',$obj[$key]);
            } elseif ($key == 'isdiscountgoods') {
                $returnObj[$key] = L_('店内有部分商品限时优惠');
            } elseif ($key == 'isdiscountsort') {
                $returnObj[$key] = L_('部分商品分类参与折扣优惠');
            } elseif ($key == 'delivery_free') {
                $Currency_symbol = cfg('Currency_symbol');
                if ($value['money']) {
                    $d_text = L_('满'.$Currency_symbol.'X1免配送费',$value['money']);
                }else{
                    $d_text = L_('免配送费');
                }
                $returnObj[$key] =  $d_text;
            } else {
                $returnObj[$key] = [];
                foreach ($obj[$key] as $k => $v) {
                	if ($v['percentage_status']==1) {
                        $dis_num = round($v['minus']*10,2);
                    }else{
                        $dis_num = cfg('Currency_symbol').round($v['minus'],2);
                    }
                    if($key == 'delivery'){
                    	if($obj[$key][$k]['order_count'] > 1){
                    		if($v['percentage_status']==1){
		                        $returnObj[$key][] = L_('平台首X0单满X1配送享X2折',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
		                    else{
		                    	$returnObj[$key][] = L_('平台首X0单满X1配送减X2',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
                    	}elseif($obj[$key][$k]['order_count'] == 1){
                    		if($v['percentage_status']==1){
	                        	$returnObj[$key][] = L_('平台首单满X1配送享X2折',array('X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
	                        }else{
	                        	$returnObj[$key][] = L_('平台首单满X1配送减X2',array('X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
	                        }
                    	}else{
                            if ($obj[$key][$k]['order_count'] >= 0) {
                                if ($v['percentage_status'] == 1) {
                                    if ($obj[$key][$k]['money'] > 0) {
                                        $returnObj[$key][] = L_('满X1享X2折运费', array('X1' => cfg('Currency_symbol') . $obj[$key][$k]['money'], 'X2' => $dis_num));
                                    } else {
                                        $returnObj[$key][] = L_('享X2折运费', array('X2' => $dis_num));
                                    }
                                } else {
                                    if ($obj[$key][$k]['money'] > 0) {
                                        $returnObj[$key][] = L_('满X1减X2运费', array('X1' => cfg('Currency_symbol') . $obj[$key][$k]['money'], 'X2' => $dis_num));
                                    } else {
                                        $returnObj[$key][] = L_('减X2运费', array('X2' => $dis_num));
                                    }
                                }
                            }/*else{
								if($v['percentage_status']==1){
									$returnObj[$key][] = L_('享X1折运费',array('X1'=>$dis_num));
								}else{
									$returnObj[$key][] = L_('减X1运费',array('X1'=>$dis_num));
								}
							} */
                    	}
                    } else if($key == 'system_newuser'){
                        if($obj[$key][$k]['order_count'] == 1){
                        	if($v['percentage_status']==1){
                            	$returnObj[$key][] = L_('平台首单X1享X2折',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
							} else{
                            	$returnObj[$key][] = L_('平台首单X1减X2',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                            }
                        }else{
                        	if($v['percentage_status']==1){
                           		$returnObj[$key][] = L_('平台首X0单X1享X2折',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                           	}else{
                           		$returnObj[$key][] = L_('平台首X0单X1减X2',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                           	}
                        }
                    } else if($key == 'newuser'){
                    	if($obj[$key][$k]['order_count'] <= 1){
                    		if($v['percentage_status']==1){
		                        $returnObj[$key][] = L_('店铺首单满X1享X2折',array('X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
		                    else{
		                        if($obj[$key][$k]['money'] > 0){
                                    $returnObj[$key][] = L_('店铺首单满X1减X2',array('X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
                                }else{
                                    $returnObj[$key][] = L_('店铺首单减X2',array('X2'=>$dis_num));
                                }
		                    }
                        }else{
                        	if($v['percentage_status']==1){
		                        $returnObj[$key][] = L_('店铺首X0单满X1享X2折',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
		                    else{
		                    	$returnObj[$key][] = L_('店铺首X0单满X1减X2',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
                        }
                    } else {
                    	if($obj[$key][$k]['order_count'] > 1){
                    		if($v['percentage_status']==1){
	                        	$returnObj[$key][] = L_('首X0单满X1享X2折',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
	                        }
	                        else{
	                        	$returnObj[$key][] = L_('首X0单满X1减X2',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
	                        }
                    	}elseif($obj[$key][$k]['order_count'] == 1){
                    		if($v['percentage_status']==1){
		                        $returnObj[$key][] = L_('首单满X1享X2折',array('X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
		                    else{
		                    	$returnObj[$key][] = L_('首单满X1减X2',array('X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
                    	}else{
							if($obj[$key][$k]['money'] > 0){
								if($v['percentage_status']==1){
									$returnObj[$key][] = L_('满X1享X2折',array('X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
								}
								else{
									$returnObj[$key][] = L_('满X1减X2',array('X1'=>cfg('Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
								}
							}else{
								if($v['percentage_status']==1){
									$returnObj[$key][] = L_('享X1折',array('X1'=>$dis_num));
								}
								else{
									$returnObj[$key][] = L_('减X1',array('X1'=>$dis_num));
								}
							}	
                    	}
                    }
                }
            }
        }

        $textObj = array();
        foreach ($returnObj as $key => $value) {
            if ($key == 'invoice' || $key == 'discount' || $key == 'isdiscountgoods' || $key == 'isdiscountsort' || $key == 'delivery_free') {
                $textObj[$key] = $value;
            } else {
                switch ($key) {
                    case 'system_newuser':
                        $textObj[$key] = L_(implode(',', $value));
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠').implode(',', $value);
                        break;
                    case 'newuser':
                        $textObj[$key] = L_(implode(',', $value));
                        break;
                    case 'minus':
                        $textObj[$key] = L_('店铺优惠').implode(',', $value);
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠').implode(',', $value);
                        break;
                    case 'delivery':
                        $textObj[$key] = implode(',', $value);
                        break;
                }
            }
        }
        if ($type == 'text') {
            $tmpObj = array();
            foreach ($textObj as $key => $value) {
                $tmpObj[] = $value;
            }
            return implode(';', $tmpObj);
        } else {
            $returnObj = array();
            foreach ($textObj as $key => $value) {
                $returnObj[] = array(
                    'type' => $key,
                    'value' => $value
                );
            }
            return $returnObj;
        }
    }
}