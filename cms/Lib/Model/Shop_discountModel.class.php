<?php

class Shop_discountModel extends Model
{

    public function get_discount_byids($store_ids = array(), $is_all = true)
    {
        if ($is_all) {
            $sql = "SELECT `id`, `full_money`, `reduce_money`, `type`, `source`, `status`, `create_time`, `store_id`, `mer_id` FROM " . C('DB_PREFIX') . "shop_discount WHERE (`source`=0";
            if ($store_ids) {
                $str = implode(',', $store_ids);
                $sql .= " OR (`source`=1 AND `store_id` IN ({$str}))) AND `status`=1";
            } else {
                $sql .= " ) AND `status`=1";
            }
            $result = $this->query($sql);
        } elseif ($store_ids) {
            $result = $this->field(true)->where(array('store_id' => array('in', $store_ids, 'status' => 1)))->select();
        } else {
            return null;
        }
        $list = array();
        foreach ($result as $row) {
            if ($row['source'] == 0) {
                $list[0][] = $row;
            } elseif ($row['store_id'] && $row['source'] == 1) {
                $list[$row['store_id']][] = $row;
            }
        }
        return $list;
    }

    //@param shop_type int 店铺类别 1-快店；2-商城
    //@param is_foodshop int 使适用的业务 0-快店；1-餐饮
    public function getDiscounts($merId, $store_id = 0, $shop_type = 1, $usage_scenario = 0, $is_foodshop = 0, $ext_where = [])
    {
		$cache_name = 'shop_discount/model_getDiscounts_' . implode('_',func_get_args());
		$return_cache = S($cache_name);
		if($return_cache){
			return $return_cache;
		}
        //use_type 0:全部商家，1：指定商家
        //use_area 0:全区域，1：指定区域
        //status:0关闭，1：正常
        //source:0平台，1：店铺，2：商家
        $time = time();
        $data = array();
        //1:全区域，全商家
        $sql = "SELECT * FROM " . C('DB_PREFIX') . "shop_discount WHERE (`source` = 0 AND `use_area` = 0 AND `use_type` = 0 AND `is_area` = 0";
        //店铺优惠券没有时间，其他优惠要时间判断
        $sql .= " and start_time <= $time and end_time >= $time";
        if ($store_id) {
            $sql .= " OR (`source`=1 AND `store_id`={$store_id})) AND `status` = 1";
        } else {
            $sql .= " ) AND `status` = 1";
        }

        // 适用的业务
        if ($is_foodshop==0) {
            $sql .= " AND `use_limit` <> 1";
        }else{
            $sql .= " AND `use_limit` <> 0";
        }

        $shop_type = $shop_type ? $shop_type : 1;
        if ($shop_type == 1) {
            $sql .= ' and shop_type=1';
        } elseif ($shop_type == 2) {
            $sql .= ' and shop_type=2';
        }

        if($usage_scenario > 0){
            $sql .= " and ( usage_scenario = 0 or usage_scenario = $usage_scenario )";
        }else if ($usage_scenario == 0) {
			//$sql .= " and usage_scenario = 0";//如果是0表示不不限制，所以这里可以给屏蔽掉。bug【ID1000650】--jjc
        }
        //$sql .= " and start_time <= $time and end_time >= $time";

        if (isset($ext_where['type'])) {
            $sql .= ' and type=' . intval($ext_where['type']);
        }
        if (isset($ext_where['is_share'])) {
            $sql .= ' and is_share=' . intval($ext_where['is_share']);
        }
        if (isset($ext_where['percentage_status'])) {
            $sql .= ' and percentage_status=' . intval($ext_where['percentage_status']);
        }

        $result = $this->query($sql);
        foreach ($result as $row) {
            $data[$row['store_id']][] = $row;
        }

        //2.全区域，指定商家
        //4.指定区域，指定商家
        $sql = "SELECT d.*,m.store_ids FROM " . C('DB_PREFIX') . "shop_discount AS d INNER JOIN " . C('DB_PREFIX') . "shop_discount_merchant AS m ON d.id=m.did WHERE `status`=1 AND use_type=1 AND m.mer_id=" . $merId;
        if ($shop_type == 1) {
            $sql .= ' and shop_type=1';
        } elseif ($shop_type == 2) {
            $sql .= ' and shop_type=2';
        }
        if ($store_id > 0) {
            $sql .= ' and FIND_IN_SET(' . $store_id . ',m.store_ids)';
        }

        if($usage_scenario > 0){
            $sql .= " and ( usage_scenario = 0 or usage_scenario = $usage_scenario )";
        }else if ($usage_scenario == 0) {
            //$sql .= " and usage_scenario = 0";
        }

        $sql .= " and start_time <= $time and end_time >= $time";

        if (isset($ext_where['type'])) {
            $sql .= ' and d.type=' . intval($ext_where['type']);
        }
        if (isset($ext_where['is_share'])) {
            $sql .= ' and is_share=' . intval($ext_where['is_share']);
        }
        if (isset($ext_where['percentage_status'])) {
            $sql .= ' and d.percentage_status=' . intval($ext_where['percentage_status']);
        }


        // 适用的业务
        if ($is_foodshop==0) {
            $sql .= " AND `use_limit` <> 1";
        }else{
            $sql .= " AND `use_limit` <> 0";
        }


        $result = $this->query($sql);
        foreach ($result as $row) {
			$store_ids = array();
			if($row['store_ids']){
				$store_ids = explode(',',$row['store_ids']);
			}
			if(empty($store_ids) || in_array($store_id,$store_ids)){
				$data[$row['store_id']][] = $row;
			}
        }
		
        //3.指定区域，全部商家
        $merchant = D('Merchant')->field('province_id, city_id, area_id,street_id')->where(array('mer_id' => $merId))->find();
        $sql = "SELECT d.* FROM " . C('DB_PREFIX') . "shop_discount AS d INNER JOIN " . C('DB_PREFIX') . "shop_discount_area AS a ON a.did=d.id WHERE `status`=1 AND use_type=0 AND a.aid IN (" . $merchant['province_id'] . ',' . $merchant['city_id'] . ',' . $merchant['area_id'] . ')';
        if ($shop_type == 1) {
            $sql .= ' and shop_type=1';
        } elseif ($shop_type == 2) {
            $sql .= ' and shop_type=2';
        }

        // 适用的业务
        if ($is_foodshop==0) {
            $sql .= " AND `use_limit` <> 1";
        }else{
            $sql .= " AND `use_limit` <> 0";
        }

        if($usage_scenario > 0){
            $sql .= " and ( usage_scenario = 0 or usage_scenario = $usage_scenario )";
        }else if ($usage_scenario == 0) {
            //$sql .= " and usage_scenario = 0";
        }

        $sql .= " and start_time <= $time and end_time >= $time";
        if (isset($ext_where['type'])) {
            $sql .= ' and d.type=' . intval($ext_where['type']);
        }
        if (isset($ext_where['is_share'])) {
            $sql .= ' and is_share=' . intval($ext_where['is_share']);
        }
        if (isset($ext_where['percentage_status'])) {
            $sql .= ' and d.percentage_status=' . intval($ext_where['percentage_status']);
        }
        $result = $this->query($sql);

        $discount_street = M('Shop_discount_street');
        foreach ($result as $row) {
            //判断是否存在乡镇
            $street_ids = $discount_street->where(['did' => $row['id']])->getField('street_id', true);
            if ($street_ids && !in_array($merchant['street_id'], $street_ids)) {
                continue;
            }
            $data[$row['store_id']][] = $row;
        }
        
        foreach ($data as &$v){
            $tempData = array_map('serialize', $v);
            $tempData = array_unique($tempData);
            $v = array_map('unserialize', $tempData);
        }
        
		S($cache_name,$data, 120);
        return $data;
    }
}

?>