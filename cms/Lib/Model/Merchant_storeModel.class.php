<?php
class Merchant_storeModel extends Model
{
    /**
     * 说明店铺的状态  status字段
     * 0:关闭,1：开启，2：审核中，4：删除
     */
    
	public function get_storelist_by_merId($mer_id,$business=''){
		$where = array('mer_id'=>$mer_id, 'status' => 1);
		if($business == 'group'){
			$where['have_group'] = 1;
			$where['group_on_sale'] = 1;
		}
		return M(_view('merchant_store'))->field(true)->where($where)->select();
	}


	/*通过店铺ID得到当前店铺*/
	public function get_store_by_storeId($store_id){
		$now_store = D('')->field('`m`.`name` AS `merchant_name`,`m`.*,`mc`.*,`a`.*')->table(array(C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX')._view('merchant_store')=>'mc',C('DB_PREFIX').'area'=>'a'))->where("`mc`.`store_id`='$store_id' AND `mc`.`area_id`=`a`.`area_id` AND `m`.`mer_id`=`mc`.mer_id")->find();
		if(!$now_store){
            $now_store = D('')->field('`m`.`name` AS `merchant_name`,`m`.*,`mc`.*')->table(array(C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX')._view('merchant_store')=>'mc'))->where("`mc`.`store_id`='$store_id' AND `m`.`mer_id`=`mc`.mer_id")->find();
        }
		if(!empty($now_store['logo'])){
			$now_store['logo'] = file_domain().$now_store['logo'];
		}
		if(!empty($now_store['pic_info'])){
			$store_image_class = new store_image();
			$now_store['all_pic'] = $store_image_class->get_allImage_by_path($now_store['pic_info']);
		}else{
            $now_store['all_pic'] = [];
        }
		return $now_store;
	}
	/*通过店铺ID得到当前店铺*/
	public function get_store_list_by_merId($mer_id,$store_type=''){
		$where = '';
		if($store_type == 'shop'){
			$where = ' AND `mc`.`have_shop`=1';
		}
		$store_list = D('')->table(array(C('DB_PREFIX').'merchant_store'=>'mc',C('DB_PREFIX').'area'=>'a'))->field('mc.*,a.area_name')->where("`mc`.`mer_id`='$mer_id' AND `mc`.`area_id`=`a`.`area_id` AND `mc`.`status`=1".$where)->order('`sort` DESC')->select();
		foreach($store_list as $key=>$value){
			if(!empty($value['pic_info'])){
				$store_image_class = new store_image();
				$store_list[$key]['all_pic'] = $store_image_class->get_allImage_by_path($value['pic_info']);
			}
		}
		return $store_list;
	}


	/**
	 * 根据条件获取商家列表
	 * @param array $where
	 * @param number $limit
	 */
	public function get_list_by_option($area_id = 0, $circle_id = 0, $order = 'store_id', $lat = 0, $long = 0, $cat_id = 0, $cat_fid = 0, $store_type = 2)
	{
		$condition_where = "s.city_id='".C('config.now_city')."' AND s.have_meal=1 AND s.status=1 AND s.store_id=m.store_id";
		if (is_array($store_type)) {
			$condition_where .= " AND s.store_type IN (" . implode(',', $store_type) . ")";
		} else if($store_type){
			$condition_where .= " AND s.store_type={$store_type}";
		}

		$area_id && $condition_where .= " AND s.area_id={$area_id}";
		$circle_id && $condition_where .= " AND s.circle_id={$circle_id}";

		$juli = '';
		//排序
		switch($order){
			case 'price-asc':
				$order = '`m`.`mean_money` ASC,`s`.`store_id` DESC';
				break;
			case 'price-desc':
				$order = '`m`.`mean_money` DESC,`s`.`store_id` DESC';
				break;
			case 'hot':
				$order = '`m`.`sale_count` DESC,`s`.`store_id` DESC';
				break;
			case 'time':
				$order = '`s`.`last_time` DESC,`s`.`store_id` DESC';
				break;
			case 'store_id':
				$order = '`s`.`store_id` ASC';
				break;
			case 'juli':
			default:
				$juli = ", ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
				$order = 'juli asc';
				break;
		}


		import('@.ORG.wap_group_page');
		$mod = new Model();
		if ($cat_fid || $cat_id) {
			if ($cat_fid && $cat_id) {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id)))->select();
			} elseif ($cat_fid) {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_fid' => $cat_fid)))->select();
			} else {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_id' => $cat_id)))->select();
			}
			$store_ids = array();
			foreach ($relation as $r) {
				if (!in_array($r['store_id'], $store_ids)) {
					$store_ids[] = $r['store_id'];
				}
			}
			if ($store_ids) {
				$condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';
			} else {
				return array('meal_list' => null, 'pagebar' => null);
			}
		}

		$labels = D('Store_label')->field(true)->select();
		$label_list = array();
		foreach ($labels as $label) {
			$label_list[$label['id']] = $label;
		}

		$sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_foodshop as m ON m.store_id=s.store_id WHERE {$condition_where}";
		$count = $mod->query($sql_count);

		$total = isset($count[0]['count']) ? $count[0]['count'] : 0;
		$p = new Page($total, C('config.meal_page_row'), C('config.meal_page_val'));

		$sql = "SELECT s.*, m.* {$juli} FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_foodshop as m ON m.store_id=s.store_id WHERE {$condition_where} ORDER BY {$order} LIMIT {$p->firstRow}, {$p->listRows}";
		$res = $mod->query($sql);
		$ids = array();
		foreach ($res as $r) {
			if (!in_array($r['circle_id'], $ids)) {
				$ids[] = $r['circle_id'];
			}
		}
		$temp = array();
		if ($ids) {
			$areas = M("Area")->where(array('area_id' => array('in', $ids)))->select();
			foreach ($areas as $a) {
				$temp[$a['area_id']] = $a;
			}
		}
		$now_time = time();
		$store_image_class = new store_image();
		foreach ($res as &$v) {
			$v['url'] = C('config.site_url').'/meal/'.$v['store_id'].'.html';
			$v['area_name'] = isset($temp[$v['circle_id']]) ? $temp[$v['circle_id']]['area_name'] : '';
			$images = $store_image_class->get_allImage_by_path($v['pic_info']);
			$v['image'] = $images ? array_shift($images) : array();
			$v['mean_money'] = floatval($v['mean_money']);
			$v['wap_url'] = U('Food/shop',array('mer_id'=>$v['mer_id'],'store_id'=>$v['store_id']));

			if($v['juli']){
				$v['range'] = getRange($v['juli']);
			}

			$v['state'] = 0;//根据营业时间判断
			$v['work_time'] = '';

			$temp_label = array();
			foreach (unserialize($v['store_labels']) as $labelid) {
				isset($label_list[$labelid]) && $temp_label[] = $label_list[$labelid];
			}
			$v['store_labels'] = $temp_label;
			if ($v['office_time']) {
				foreach (unserialize($v['office_time']) as $time) {
					$v['work_time'] .= '<span>' . $time['open'] . '-' . $time['close'] . '</span>';
					$open = strtotime(date("Y-m-d ") . $time['open'] . ':00');
					$close = strtotime(date("Y-m-d ") . $time['close'] . ':00');
					if ($open < $now_time && $now_time < $close) {
						$v['state'] = 1;//根据营业时间判断
					}
				}
			} else {
				if ($v['open_1'] == '00:00:00' && $v['close_1'] == '00:00:00') {
					$v['work_time'] .= '<span>24小时营业</span>';
					$v['state'] = 1;
				} else {
					$now_time = date('H:i:s');
					$v['work_time'] .= '<span>' . $v['open_1'] . '-' . $v['close_1'] . '</span>';
					if ($v['open_1'] < $now_time && $now_time < $v['close_1']) {
						$v['state'] = 1;
					}
					if ($v['open_2'] != '00:00:00' && $v['close_2'] != '00:00:00') {
						$v['work_time'] .= '<span>' . $v['open_2'] . '-' . $v['close_2'] . '</span>';
						if ($v['open_2'] < $now_time && $now_time < $v['close_2']) {
							$v['state'] = 1;
						}
					}
					if ($v['open_3'] != '00:00:00' && $v['close_3'] != '00:00:00') {
						$v['work_time'] .= '<span>' . $v['open_3'] . '-' . $v['close_3'] . '</span>';
						if ($v['open_3'] < $now_time && $now_time < $v['close_3']) {
							$v['state'] = 1;
						}
					}
				}
			}
		}
		$return['meal_list'] = $res;
		$return['totalPage'] = $p->totalPage;
		$return['pagebar'] = $p->show();
		$return['meal_count'] = $total;
		return $return;
	}


	public function get_list_by_option_pc($area_id = 0, $circle_id = 0, $order = 'store_id', $cat_id = 0, $cat_fid = 0, $store_type = 2)
	{
// 		$condition_table = array(C('DB_PREFIX') . 'merchant_store' => 's', C('DB_PREFIX') . 'merchant_store_meal' => 'm');

		$condition_where = "`me`.`city_id`='".C('config.now_city')."' AND s.have_meal=1 AND s.status=1 AND s.store_id=m.store_id AND me.status=1 AND s.foodshop_on_sale=1";
		if (is_array($store_type)) {
			$condition_where .= " AND s.store_type IN (" . implode(',', $store_type) . ")";
		} else {
			$condition_where .= "  AND s.store_type={$store_type}";
		}
// 		$condition_field = "s.*, m.*";
// // 		if ($cat_id) {
// 			$condition_table[C('DB_PREFIX') . 'meal_store_category_relation'] = 'r';
// 			$condition_where .= " AND s.store_id=r.store_id AND r.cat_id={$cat_id}";
// 			$condition_field .= ", r.*";
// 		}
		$area_id && $condition_where .= " AND s.area_id={$area_id}";
		$circle_id && $condition_where .= " AND s.circle_id={$circle_id}";
		//排序
		switch($order){
			case 'price-asc':
				$order = '`m`.`mean_money` ASC,`s`.`store_id` DESC';
				break;
			case 'price-desc':
				$order = '`m`.`mean_money` DESC,`s`.`store_id` DESC';
				break;
			case 'hot':
				$order = '`m`.`sale_count` DESC,`s`.`store_id` DESC';
				break;
			case 'time':
				$order = '`s`.`last_time` DESC,`s`.`store_id` DESC';
				break;
			case 'store_id':
			default:
				$order = '`s`.`store_id` ASC';
		}


		import('@.ORG.group_page');
		$mod = new Model();
		if ($cat_fid || $cat_id) {
			if ($cat_fid && $cat_id) {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id)))->select();
			} elseif ($cat_fid) {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_fid' => $cat_fid)))->select();
			} else {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_id' => $cat_id)))->select();
			}
			$store_ids = array();
			foreach ($relation as $r) {
				if (!in_array($r['store_id'], $store_ids)) {
					$store_ids[] = $r['store_id'];
				}
			}
			if ($store_ids) {
				$condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';
			} else {
				return array('group_list' => null, 'pagebar' => null);
			}
		}
		$sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant AS me INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON me.mer_id=s.mer_id INNER JOIN " . C('DB_PREFIX') . "merchant_store_meal as m ON m.store_id=s.store_id WHERE {$condition_where}";
		$count = $mod->query($sql_count);
		$total = isset($count[0]['count']) ? $count[0]['count'] : 0;
		$p = new Page($total, C('config.meal_page_row'), C('config.meal_page_val'));
		$sql = "SELECT me.fans_count, s.*, m.* FROM " . C('DB_PREFIX') . "merchant AS me INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON me.mer_id=s.mer_id INNER JOIN " . C('DB_PREFIX') . "merchant_store_meal as m ON m.store_id=s.store_id WHERE {$condition_where} ORDER BY {$order} LIMIT {$p->firstRow}, {$p->listRows}";
		$res = $mod->query($sql);
// 		$count = D('')->table($condition_table)->where($condition_where)->count();

// 		$res = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		$ids = array();
		foreach ($res as $r) {
			if (!in_array($r['circle_id'], $ids)) {
				$ids[] = $r['circle_id'];
			}
		}
		$temp = array();
		if ($ids) {
			$areas = M("Area")->where(array('area_id' => array('in', $ids)))->select();
			foreach ($areas as $a) {
				$temp[$a['area_id']] = $a;
			}
		}
		$now_time = time();
		$store_image_class = new store_image();
		$open_list = array();
		$close_list = array();
		foreach ($res as $v) {
			$v['url'] = C('config.site_url').'/meal/'.$v['store_id'].'.html';
			$v['area_name'] = isset($temp[$v['circle_id']]) ? $temp[$v['circle_id']]['area_name'] : '';
			$images = $store_image_class->get_allImage_by_path($v['pic_info']);
			$v['image'] = $images ? array_shift($images) : array();

			$v['state'] = 0;//根据营业时间判断
			$v['work_time'] = '';
			if ($v['office_time']) {
				$now_time = time();
				foreach (unserialize($v['office_time']) as $time) {
					$v['work_time'] .= '<span>' . $time['open'] . '-' . $time['close'] . '</span>';
					$open = strtotime(date("Y-m-d ") . $time['open'] . ':00');
					$close = strtotime(date("Y-m-d ") . $time['close'] . ':00');
					if ($open < $now_time && $now_time < $close) {
						$v['state'] = 1;//根据营业时间判断
					}
				}
			} else {
				if ($v['open_1'] == '00:00:00' && $v['close_1'] == '00:00:00') {
					$v['work_time'] .= '<span>24小时营业</span>';
					$v['state'] = 1;
				} else {
					$now_time = date('H:i:s');
					$v['work_time'] .= '<span>' . $v['open_1'] . '-' . $v['close_1'] . '</span>';
					if ($v['open_1'] < $now_time && $now_time < $v['close_1']) {
						$v['state'] = 1;
					}
					if ($v['open_2'] != '00:00:00' && $v['close_2'] != '00:00:00') {
						$v['work_time'] .= '<span>' . $v['open_2'] . '-' . $v['close_2'] . '</span>';
						if ($v['open_2'] < $now_time && $now_time < $v['close_2']) {
							$v['state'] = 1;
						}
					}
					if ($v['open_3'] != '00:00:00' && $v['close_3'] != '00:00:00') {
						$v['work_time'] .= '<span>' . $v['open_3'] . '-' . $v['close_3'] . '</span>';
						if ($v['open_3'] < $now_time && $now_time < $v['close_3']) {
							$v['state'] = 1;
						}
					}
				}
			}
			if ($v['state']) {
				$open_list[] = $v;
			} else {
				$close_list[] = $v;
			}
		}

		if ($close_list) {
			foreach ($close_list as $cl) {
				$open_list[] = $cl;
			}
		}
		return array('group_list' => $open_list, 'pagebar' => $p->show());
	}


	/**
	 * 根据关键字获取商家列表
	 * @param array $where
	 * @param number $limit
	 */
	public function get_list_by_search($w, $order = 'store_id', $is_wap = false)
	{
		$where['have_meal'] = 1;
		$where['status'] = 1;
		$condition_where = [
			's.have_meal' => 1,
			's.status' => 1,
			's.foodshop_on_sale' => 1
		];
		!empty(trim($w)) && $condition_where['s.name'] = ['LIKE', '%' . safeValue($w) . '%'];

		//排序
		switch($order){
			case 'price-asc':
				$order = '`m`.`mean_money` ASC,`s`.`store_id` DESC';
				break;
			case 'price-desc':
				$order = '`m`.`mean_money` DESC,`s`.`store_id` DESC';
				break;
			case 'hot':
				$order = '`m`.`sale_count` DESC,`s`.`store_id` DESC';
				break;
			case 'time':
				$order = '`s`.`last_time` DESC,`s`.`store_id` DESC';
				break;
			default:
				$order = '`m`.`store_id` ASC';
		}

		if(empty($is_wap)){
			import('@.ORG.group_page');
		}else{
			import('@.ORG.wap_group_search_page');
		}

		//商家入驻缴费开关
		$merchant_settle_in = C('customization.merchant_settle_in');
		$mer_field = '';
		if($merchant_settle_in == 1){
			$mer_field = 'me.settle_in_money, me.settle_in_sign,';
		}
		
		$Model = new Model();
		$count = D('Merchant')->alias('me')
		->join(C('DB_PREFIX') . 'merchant_store AS s ON me.mer_id=s.mer_id AND me.status=1')
		->join('INNER JOIN '.C('DB_PREFIX') . 'merchant_store_foodshop AS m ON s.store_id=m.store_id')
		->where($condition_where)
		->count();
		$p = new Page($count, C('config.group_page_row'), C('config.group_page_val'));
		$res = D('Merchant')->alias('me')
		->join(C('DB_PREFIX') . 'merchant_store AS s ON me.mer_id=s.mer_id AND me.status=1')
		->join('INNER JOIN '.C('DB_PREFIX') . 'merchant_store_foodshop AS m ON s.store_id=m.store_id')
		->where($condition_where)
		->field("me.fans_count,me.isverify, " . $mer_field . " s.*, m.*")
		->order($order)
		->limit($p->firstRow, $p->listRows)
		->select();

		$ids = array();
		foreach ($res as $r) {
			if (!in_array($r['circle_id'], $ids)) {
				$ids[] = $r['circle_id'];
			}
		}
		$temp = array();
		if ($ids) {
			$areas = M("Area")->where(array('area_id' => array('in', $ids)))->select();
			foreach ($areas as $a) {
				$temp[$a['area_id']] = $a;
			}
		}
		$now_time = time();
		$store_image_class = new store_image();
		foreach ($res as &$v) {
			$v['url'] = $is_wap ? U('Wap/Foodshop/shop', array('mer_id' => $v['mer_id'], 'store_id' => $v['store_id'], 'keywords' => urlencode($w))) : C('config.site_url').'/meal/'.$v['store_id'].'.html';
			$v['area_name'] = isset($temp[$v['circle_id']]) ? $temp[$v['circle_id']]['area_name'] : '';
			$images = $store_image_class->get_allImage_by_path($v['pic_info']);
			$v['image'] = $images ? array_shift($images) : array();
			$v['mean_money'] = floatval($v['mean_money']);
			$v['state'] = 0;//根据营业时间判断
			$v['work_time'] = '';
			$v['search_name'] = str_replace($w, '<font color="#06c1ae">' . $w. '</font>', $v['name']);

			if($merchant_settle_in == 1){
				//商家入驻缴费标志
				$v['settle_in_sign'] = $v['settle_in_money'] > 0 ? $v['settle_in_sign'] : '';
				unset($v['settle_in_money']);
			}
			
			if ($v['office_time']) {
				foreach (unserialize($v['office_time']) as $time) {
					$v['work_time'] .= '<span>' . $time['open'] . '-' . $time['close'] . '</span>';
					$open = strtotime(date("Y-m-d ") . $time['open'] . ':00');
					$close = strtotime(date("Y-m-d ") . $time['close'] . ':00');
					if ($open < $now_time && $now_time < $close) {
						$v['state'] = 1;//根据营业时间判断
					}
				}
			} else {
				if ($v['open_1'] == '00:00:00' && $v['close_1'] == '00:00:00') {
					$v['work_time'] .= '<span>24小时营业</span>';
					$v['state'] = 1;
				} else {
					$now_time = date('H:i:s');
					$v['work_time'] .= '<span>' . substr($v['open_1'], 0, -3) . '-' . substr($v['close_1'], 0, -3) . '</span>';
					if ($v['open_1'] < $now_time && $now_time < $v['close_1']) {
						$v['state'] = 1;
					}
					if ($v['open_2'] != '00:00:00' && $v['close_2'] != '00:00:00') {
						$v['work_time'] .= '<span>' . substr($v['open_2'], 0, -3) . '-' . substr($v['close_2'], 0, -3) . '</span>';
						if ($v['open_2'] < $now_time && $now_time < $v['close_2']) {
							$v['state'] = 1;
						}
					}
					if ($v['open_3'] != '00:00:00' && $v['close_3'] != '00:00:00') {
						$v['work_time'] .= '<span>' . substr($v['open_3'], 0, -3) . '-' . substr($v['close_3'], 0, -3) . '</span>';
						if ($v['open_3'] < $now_time && $now_time < $v['close_3']) {
							$v['state'] = 1;
						}
					}
				}
			}

		}
		return array('group_list' => $res, 'pagebar' => $p->show(), 'meal_count' => $count, 'totalPage' => ceil($count / C('config.group_page_row')));
	}

	public function get_hot_list($limit = 6, $lat = 0, $long = 0)
	{
		$condition_where = "`me`.`city_id`='".C('config.now_city')."' AND s.have_shop=1 AND s.status=1 AND me.status=1 AND m.is_close_shop=0";
		if (C('config.store_shop_auth') == 1) {
			$condition_where .= " AND s.auth>2";
		}
		$Model = new Model();
		if ($lat && $long) {
			$order = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(($lat * PI() / 180- `s`.`lat` * PI()/180)/2),2)+COS($lat *PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(($long *PI()/180- `s`.`long`*PI()/180)/2),2)))*1000) ASC, m.sort DESC";
		} else {
			$order = "m.sort DESC";
		}
		$sql = "SELECT s.*, m.*, mm.isverify,me.fans_count FROM ". C('DB_PREFIX') . "merchant as me INNER JOIN ". C('DB_PREFIX') . "merchant_store AS s ON me.mer_id=s.mer_id INNER JOIN ". C('DB_PREFIX') . "merchant_store_shop AS m ON s.store_id=m.store_id LEFT JOIN ".C('DB_PREFIX')."merchant as mm ON mm.mer_id = s.mer_id WHERE {$condition_where} ORDER BY {$order} LIMIT 0, {$limit}";
		$res = $Model->query($sql);

		$ids = array();
		foreach ($res as $r) {
			if (!in_array($r['circle_id'], $ids)) {
				$ids[] = $r['circle_id'];
			}
		}
		$temp = array();
		if ($ids) {
			$areas = M("Area")->where(array('area_id' => array('in', $ids)))->select();
			foreach ($areas as $a) {
				$temp[$a['area_id']] = $a;
			}
		}

		$now_time = time();
		$store_image_class = new store_image();
		foreach ($res as &$v) {
			$v['url'] = C('config.site_url').'/shop/'.$v['store_id'].'.html';
			$v['area_name'] = isset($temp[$v['circle_id']]) ? $temp[$v['circle_id']]['area_name'] : '';
			$images = $store_image_class->get_allImage_by_path($v['pic_info']);
			$v['image'] = $images ? array_shift($images) : array();
			$v['fans_count'] = M('Merchant_user_relation')->alias('m')->join('left join '.C('DB_PREFIX').'user u on u.openid=m.openid')->where(array('mer_id'=>$v['mer_id'],'u.openid'=>array('neq',''),'m.openid'=>array('neq','')))->count();
			$v['state'] = 0;//根据营业时间判断
			$v['work_time'] = '';
			if ($v['office_time']) {
				foreach (unserialize($v['office_time']) as $time) {
					$v['work_time'] .= '<span>' . $time['open'] . '-' . $time['close'] . '</span>';
					$open = strtotime(date("Y-m-d ") . $time['open'] . ':00');
					$close = strtotime(date("Y-m-d ") . $time['close'] . ':00');
					if ($open < $now_time && $now_time < $close) {
						$v['state'] = 1;//根据营业时间判断
					}
				}
			} else {
				if ($v['open_1'] == '00:00:00' && $v['close_1'] == '00:00:00') {
					$v['work_time'] .= '<span>24小时营业</span>';
					$v['state'] = 1;
				} else {
					$now_time = date('H:i:s');
					$v['work_time'] .= '<span>' . $v['open_1'] . '-' . $v['close_1'] . '</span>';
					if ($v['open_1'] < $now_time && $now_time < $v['close_1']) {
						$v['state'] = 1;
					}
					if ($v['open_2'] != '00:00:00' && $v['close_2'] != '00:00:00') {
						$v['work_time'] .= '<span>' . $v['open_2'] . '-' . $v['close_2'] . '</span>';
						if ($v['open_2'] < $now_time && $now_time < $v['close_2']) {
							$v['state'] = 1;
						}
					}
					if ($v['open_3'] != '00:00:00' && $v['close_3'] != '00:00:00') {
						$v['work_time'] .= '<span>' . $v['open_3'] . '-' . $v['close_3'] . '</span>';
						if ($v['open_3'] < $now_time && $now_time < $v['close_3']) {
							$v['state'] = 1;
						}
					}
				}
			}
		}
		if ($lat && $long) {
			foreach ($res as &$v){
				$v['range'] = getRange(getDistance($v['lat'],$v['long'],$lat,$long));
			}
		}

		return $res;
	}

	public function wap_get_store_collect_list($uid){
		$condition_where = "`s`.`circle_id`=`a`.`area_id` AND `s`.`store_id`=`c`.`id` AND `c`.`type`='group_shop' AND `c`.`uid`='$uid'";
		$condition_table = array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'area'=>'a',C('DB_PREFIX').'user_collect'=>'c');
		$condition_field  = '`s`.*,`a`.`area_name`';
		$order = '`c`.`collect_id` DESC';

		import('@.ORG.wap_collect_page');
		$count_store = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_store,10,'page');
		$store_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		if($store_list){
			$store_image_class = new store_image();
			foreach($store_list as &$v){
				$images = $store_image_class->get_allImage_by_path($v['pic_info']);
				$v['list_pic'] = $images ? array_shift($images) : array();
			}
		}

		$return['store_list'] = $store_list;

		$return['pagebar'] = $p->show();

		return $return;
	}

	/*增加一次订餐店铺评论数*/
	public function setInc_meal_reply($store_id, $score){
		$store_meal = D("Merchant_store_meal")->where(array('store_id' => $store_id))->find();
		if ($store_meal) {
			$data = array('reply_count' => $store_meal['reply_count'] + 1, 'score_all' => $store_meal['score_all'] + $score);
			$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			if (D("Merchant_store_meal")->where(array('store_id' => $store_id))->data($data)->save()) {
				return true;
			} else {
				return false;
			}
		} else {
			$data = array('reply_count' => 1, 'score_all' => $score);
			$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			$data['store_id'] = $store_id;
			if (D("Merchant_store_meal")->add($data)) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function setInc_shop_reply($store_id, $score=null, $dscore=null){
		$store_shop = D("Merchant_store_shop")->where(array('store_id' => $store_id))->find();
		if ($store_shop) {
			$data=array();
			if(is_numeric($score)){
				$data['reply_count']=$store_shop['reply_count']+1;
				$data['score_all']=$store_shop['score_all']+$score;
				$data['score_mean'] = min($data['score_all'] / $data['reply_count'],5);

			}
			if(is_numeric($dscore)){
				$data['reply_deliver_count']=$store_shop['reply_deliver_count']+1;
				$data['reply_deliver_score'] =min( ($store_shop['reply_deliver_count'] * $store_shop['reply_deliver_score'] + $dscore) / $data['reply_deliver_count'],5);
			}
			if (D("Merchant_store_shop")->where(array('store_id' => $store_id))->data($data)->save()) {
				return true;
			} else {
				return false;
			}
		} else {
			$data=array();
			if(is_numeric($score)){
				$data['reply_count']=1;
				$data['score_all']=$score;
				$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			}
			if(is_numeric($dscore)){
				$data['reply_deliver_count']=1;
				$data['reply_deliver_score']=$dscore;
			}
			$data['store_id'] = $store_id;
			if (D("Merchant_store_shop")->add($data)) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function setInc_foodshop_reply($store_id, $score){
		$store_shop = D("Merchant_store_foodshop")->where(array('store_id' => $store_id))->find();
		if ($store_shop) {
			$data = array('reply_count' => $store_shop['reply_count'] + 1, 'score_all' => $store_shop['score_all'] + $score);
			$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			if (D("Merchant_store_foodshop")->where(array('store_id' => $store_id))->data($data)->save()) {
				return true;
			} else {
				return false;
			}
		} else {
			$data = array('reply_count' => 1, 'score_all' => $score);
			$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			$data['store_id'] = $store_id;
			if (D("Merchant_store_foodshop")->add($data)) {
				return true;
			} else {
				return false;
			}
		}
	}
	public function setDec_meal_reply($reply)
	{
		$store_meal = D("Merchant_store_meal")->where(array('store_id' => $reply['parent_id']))->find();
		if ($store_meal) {
			$data['reply_count'] = max(0, $store_meal['reply_count'] - 1);
			$data['score_all'] = max(0, $store_meal['score_all'] - $reply['score']);
			if ($data['reply_count'] == 0 || $data['score_all'] == 0) {
				$data['score_mean'] = 0;
			} else {
				$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			}
			if (D("Merchant_store_meal")->where(array('store_id' => $reply['parent_id']))->data($data)->save()) return true;
		}
		return false;
	}

	public function setDec_shop_reply($reply)
	{
		$store_shop = D("Merchant_store_shop")->where(array('store_id' => $reply['parent_id']))->find();
		if ($store_shop) {
			$data['reply_count'] = max(0, $store_shop['reply_count'] - 1);
			$data['score_all'] = max(0, $store_shop['score_all'] - $reply['score']);
			if ($data['reply_count'] == 0 || $data['score_all'] == 0) {
				$data['score_mean'] = 0;
			} else {
				$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			}

            $data['reply_deliver_count'] = max(0, $store_shop['reply_deliver_count'] - 1);
            if ($data['reply_deliver_count'] > 0) {
                $data['reply_deliver_score'] = ($store_shop['reply_deliver_count'] * $store_shop['reply_deliver_score'] - $reply['deliver_score']) / $data['reply_deliver_count'];
                if($data['reply_deliver_score'] < 0){
                    $data['reply_deliver_score'] = 0;
                }
            } else {
                $data['reply_deliver_score'] = 0;
            }

			if (D("Merchant_store_shop")->where(array('store_id' => $reply['parent_id']))->data($data)->save()) return true;
		}
		return false;
	}

	public function setDec_foodshop_reply($reply)
	{
		$store_shop = D("Merchant_store_foodshop")->where(array('store_id' => $reply['parent_id']))->find();
		if ($store_shop) {
			$data['reply_count'] = max(0, $store_shop['reply_count'] - 1);
			$data['score_all'] = max(0, $store_shop['score_all'] - $reply['score']);
			if ($data['reply_count'] == 0 || $data['score_all'] == 0) {
				$data['score_mean'] = 0;
			} else {
				$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			}
			if (D("Merchant_store_foodshop")->where(array('store_id' => $reply['parent_id']))->data($data)->save()) return true;
		}
		return false;
	}



	/*收藏列表*/
	public function get_meal_collect_list($uid){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`m`.`store_id`=`c`.`id` AND `c`.`type`='meal_detail' AND `c`.`uid`='{$uid}'";
		$condition_table = array(C('DB_PREFIX').'merchant_store'=>'m',C('DB_PREFIX').'user_collect'=>'c');
		$condition_field  = '`m`.*';

		$order = '`c`.`collect_id` DESC';

		import('@.ORG.collect_page');
        $count_meal = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_meal,10,'page');
		$meal_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();

		if($meal_list){
			$store_image_class = new store_image();
			foreach($meal_list as &$v){
				$images = $store_image_class->get_allImage_by_path($v['pic_info']);
				$v['list_pic'] = $images ? array_shift($images) : array();
				$v['url'] = C('config.site_url').'/meal/'.$v['store_id'].'.html';
			}
		}
		$return['meal_list'] = $meal_list;

		return $return;
	}

	/*wap收藏列表*/
	public function wap_get_meal_collect_list($uid){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`m`.`store_id`=`c`.`id` AND `c`.`type`='meal_detail' AND `c`.`uid`='{$uid}'";
		$condition_table = array(C('DB_PREFIX').'merchant_store'=>'m',C('DB_PREFIX').'user_collect'=>'c');
		$condition_field  = '`m`.*';

		$order = '`c`.`collect_id` DESC';

		import('@.ORG.wap_collect_page');
        $count_meal = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_meal,10,'page');
        $meal_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();

		if($meal_list){
			$store_image_class = new store_image();
			foreach($meal_list as &$v){
				$images = $store_image_class->get_allImage_by_path($v['pic_info']);
				$v['list_pic'] = $images ? array_shift($images) : array();
			}
		}
		$return['meal_list'] = $meal_list;

		return $return;
	}



	public function get_qrcode($id){
		$condition_store['store_id'] = $id;
		$now_store = $this->field('`store_id`,`qrcode_id`')->where($condition_store)->find();
		if(empty($now_store)){
			return false;
		}
		return $now_store;
	}
	public function save_qrcode($id,$qrcode_id){
		$condition_store['store_id'] = $id;
		$data_store['qrcode_id'] = $qrcode_id;
		if($this->where($condition_store)->data($data_store)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_("保存二维码至X1失败！请重试。",array("X1" => C('config.group_alias_name'))))); 
		}
	}
	public function del_qrcode($id){
		$condition_store['store_id'] = $id;
		$data_store['qrcode_id'] = '';
		if($this->where($condition_store)->data($data_store)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_("保存二维码至X1失败！请重试。",array("X1" => C('config.group_alias_name'))))); 
		}
	}


	public function wap_get_store_list_by_catid($cat_id, $area_id, $order, $lat, $long, $cat_url){

		$stores = D('Store_category')->field('store_id')->where("cat_id='$cat_id'")->select();
		foreach ($stores as $s) $store_ids[] = $s['store_id'];
		$store_ids && $where['store_id'] = array('in', $store_ids);
		if ($cat_url == 'dianying' && empty($store_ids)) {
			return false;
		}
// 		$where['have_meal'] = 1;
		$where['status'] = 1;
		$area_id && $where['area_id'] = $area_id;

		$count = D('Merchant_store')->where($where)->count();

		//排序
		switch($order){
			case 'distance':
				$order = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(($lat * PI() / 180- `lat` * PI()/180)/2),2)+COS($lat *PI()/180)*COS(`lat`*PI()/180)*POW(SIN(($long *PI()/180- `long`*PI()/180)/2),2)))*1000) ASC";//'`g`.`price` ASC,`g`.`group_id` DESC';
				break;
// 			case 'priceDesc':
// 				$order = '`g`.`price` DESC,`g`.`group_id` DESC';
// 				break;
// 			case 'solds':
// 				$order = '`g`.`sale_count` DESC,`g`.`group_id` DESC';
// 				break;
// 			case 'rating':
// 				$order = '`g`.`score_mean` DESC,`g`.`group_id` DESC';
// 				break;
// 			case 'start':
// 				$order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
// 				break;
			default:
				$order = '`store_id` DESC';
		}

		import('@.ORG.wap_group_page');
		$p = new Page($count,C('config.group_page_row'),C('config.group_page_val'));
		$list = D('Merchant_store')->field(true)->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
// 		echo D('Merchant_store')->_sql();
		$return['pagebar'] = $p->show();

		if($list){
			$store_image_class = new store_image();
			foreach($list as &$v){
				$images = $store_image_class->get_allImage_by_path($v['pic_info']);
				$v['image'] = $images ? array_shift($images) : '';

				$v['juli'] = ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(($lat * PI() / 180- $v['lat'] * PI()/180)/2),2)+COS($lat *PI()/180)*COS($v['lat']*PI()/180)*POW(SIN(($long *PI()/180- $v['long']*PI()/180)/2),2)))*1000);
				$v['juli'] = $v['juli'] > 1000 ? number_format($v['juli']/1000, 1) . 'km' : ($v['juli'] < 100 ? '<100m' : $v['juli'] . 'm');
			}
		}
		$return['store_list'] = $list;
		return $return;
	}

	public function wap_get_store_list_waimai( $lat, $long){
		if (!$lat || !$long) {
			return false;
		}
		$where['status'] = 1;
		$where['have_waimai'] = 1;
		$count = D('Merchant_store')->where($where)->count();
		$order = " `sort` desc ,ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(($lat * PI() / 180- `lat` * PI()/180)/2),2)+COS($lat *PI()/180)*COS(`lat`*PI()/180)*POW(SIN(($long *PI()/180- `long`*PI()/180)/2),2)))*1000) ASC";
		$list = D('Merchant_store')->field('store_id,name')->where($where)->order($order)->page(1,5)->select();

		$return['store_list'] = $list;
		return $return;
	}

	// 获得用户收藏列表
	public function get_store_collect_list($where){
		$condition_where = "`s`.`store_id`=`c`.`store_id` AND `c`.`type`='store' AND `c`.`uid`=".$where['uid'];
		$condition_table = array(C('DB_PREFIX')._view('merchant_store')=>'s',C('DB_PREFIX').'merchant_user_relation'=>'c');
		$condition_field  = '`s`.*';
		$order = '`c`.`id` DESC';
		
		$store_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->select();

		$returnArr = [];
		if($store_list){
			$store_image_class = new store_image();
			foreach($store_list as $v){
				$_tmp_store = [];
				// 店铺名称
				$_tmp_store['name'] = $v['name'];
				$_tmp_store['store_id'] = $v['store_id'];

				// 图片
				$store_image_class = new store_image();
				$images = $store_image_class->get_allImage_by_path($v['pic_info']);
				$_tmp_store['pic'] = array_shift($images);

				// 店铺评分
				$now_store_score = M('Merchant_score')->where(array('type'=>2,'parent_id'=>$v['store_id']))->find();
				$_tmp_store['star']    = $now_store_score && $now_store_score['reply_count'] ? round($now_store_score['score_all'] / $now_store_score['reply_count'],1) : '5.0';
				
				//业务标签
				$business_label_arr = array();
				if($v['have_group']){
					$business_label_arr[] = msubstr(C('config.group_alias_name'),0,1,false);
				}
				if($v['have_shop']){
					$business_label_arr[] = msubstr(C('config.shop_alias_name'),0,1,false);
				}
				if($v['have_meal']){
					$business_label_arr[] = msubstr(C('config.meal_alias_name'),0,1,false);
				}
				$_tmp_store['business_label'] = implode(',',$business_label_arr);
				
				$plat_recommend_value = explode('_',$v['plat_recommend_info']);
				
				if($plat_recommend_value[0] == 'cash' && $v['discount_txt']){	//推荐优惠买单
					$discount_txt_arr = unserialize($v['discount_txt']);
					if($discount_txt_arr['discount_type'] == '1'){
						if($discount_txt_arr['discount_percent'] != 0 && $discount_txt_arr['discount_percent'] != 10){
							$_tmp_store['tip'] = '买单'.str_replace('.','',$discount_txt_arr['discount_percent']).'折';
						}
					}else if($discount_txt_arr['discount_type'] == '2'){
						if($discount_txt_arr['condition_price'] != 0 && $discount_txt_arr['condition_price'] != $discount_txt_arr['minus_price']){
							$_tmp_store['tip'] = '买单满'.$discount_txt_arr['condition_price'].'元减'.$discount_txt_arr['minus_price'].'元';
						}
					}
				}else if($plat_recommend_value[0] == 'group'){	//推荐团购
					if($plat_recommend_value[1]){
						$plat_recommend_group = D('Group')->field('`group_id`,`name` AS `group_name`,`price` AS `group_price`')->where(array('group_id'=>$plat_recommend_value[1]))->find();
					}else{
						$condition_where_plat_recommend_group = "`g`.`group_id`=`gs`.`group_id` AND `gs`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
						$plat_recommend_group = D('')->field('`g`.`group_id`,`g`.`name` AS `group_name`,`g`.`price` AS `group_price`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gs'))->where($condition_where_plat_recommend_group)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->find();
					}
					
					if($plat_recommend_group){
						$_tmp_store['tip'] = getFormatNumber($plat_recommend_group['group_price']) . '元 | '.html_entity_decode($plat_recommend_group['group_name']);
					}
				}else if($plat_recommend_value[0] == 'description'){	//一句话描述
					if($plat_recommend_value[1]){
						$plat_recommend_data['tip'] = html_entity_decode($plat_recommend_value[1]);
					}
				}
				$_tmp_store['url'] = C('config.site_url').'/wap.php?g=Wap&c=Merchant&a=shop&store_id='.$v['store_id'];

				$returnArr[] = $_tmp_store;
			}
		}
		$return['store_list'] = $returnArr;

		return $return;
	}

	// 获取餐饮店铺详情
	public function now_store($store_id, $is_return = true)
    {
        $store = $this->field(true)->where(array('store_id' => $store_id))->find();
        if (empty($store) && $is_return) {
            return array('err_code' => true, 'msg' => L_('不存在的店铺'));
        } elseif ($store['status'] != 1 && $is_return) {
            return array('err_code' => true, 'msg' => L_('店铺状态异常'));
        }  elseif ($store['have_meal'] != 1 && $is_return) {
            return array('err_code' => true, 'msg' => L_('店铺不支持餐饮'));
        } else {
            $store['business_time'] = '';
            if ($store['open_1'] == '00:00:00' && $store['close_1'] == '00:00:00') {
                $store['business_time'] = '24小时营业';
            } else {
                $now_time = time();
                $is_close = 1;
                $open_1 = strtotime(date('Y-m-d') . $store['open_1']);
                $close_1 = strtotime(date('Y-m-d') . $store['close_1']);
                if ($open_1 >= $close_1) {
                    $close_1 += 86400;
                }
                if ($open_1 < $now_time && $now_time < $close_1) {
                    $is_close = 0;
                }
                if ($store['open_2'] != '00:00:00' || $store['close_2'] != '00:00:00') {
                    $open_2 = strtotime(date('Y-m-d') . $store['open_2']);
                    $close_2 = strtotime(date('Y-m-d') . $store['close_2']);
                    if ($open_2 >= $close_2) {
                        $close_2 += 86400;
                    }
                    if ($open_2 < $now_time && $now_time < $close_2) {
                        $is_close = 0;
                    }
                }
                if ($store['open_3'] != '00:00:00' || $store['close_3'] != '00:00:00') {
                    $store['business_time'] .= ';' . substr($store['open_3'], 0, -3) . '~' . substr($store['close_3'], 0, -3);
                    $open_3 = strtotime(date('Y-m-d') . $store['open_3']);
                    $close_3 = strtotime(date('Y-m-d') . $store['close_3']);
                    if ($open_3 >= $close_3) {
                        $close_3 += 86400;
                    }
                    if ($open_3 < $now_time && $now_time < $close_3) {
                        $is_close = 0;
                    }
                }
            }
            if ($is_close && $is_return) {
                return array('err_code' => true, 'msg' => L_('店铺不在营业中'));
            }
        }
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($store['pic_info']);
        $store['image_list'] = $images;
        $store['image'] = $images ? array_shift($images) : array();


        $merchant = M('Merchant')->field(true)->where(array('mer_id' => $store['mer_id']))->find();
        if (empty($merchant) && $is_return) {
            return array('err_code' => true, 'msg' => L_('不存在的商家'));
        } elseif ($merchant['status'] != 1 && $is_return) {
            return array('err_code' => true, 'msg' => L_('商家状态异常'));
        }
        $foodshop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $store_id))->find();
        if (empty($foodshop) && $is_return) {
            return array('err_code' => true, 'msg' => L_('不存在的餐饮店铺'));
        }

        $background = '';
        if(!empty($foodshop['background'])){
            $goods_image_class = new foodshopstore_image();
            $tmp_pic_arr = explode(';', $foodshop['background']);
            $background = $goods_image_class->get_image_by_path($tmp_pic_arr[0]);
            $background = $background && $background['image'] ? $background['image'] : '';
        }
        $foodshop['background'] = $background ? $background : '';

        return array('err_code' => false, 'data' => array_merge($store, $foodshop));
    }

    // 餐饮满减折扣限显示
    public function simpleParseCouponFoodshop($obj){
    	fdump($obj,'obj');
		$returnObj = array();
        foreach ($obj as $key => $value) {
            foreach ($obj[$key] as $k => $v) {
            	if ($v['percentage_status']==1) {
            		$returnObj[$k]['reduction_type'] = 'zhekou';
					if($obj[$key][$k]['money']>0){
						$dis_text = '享X2折';
					}else{
						$dis_text = 'X2折';
					}
                    $dis_num = round($v['minus']*10,2);
                }else{
            		$returnObj[$k]['reduction_type'] = 'manjian';
                    $dis_text = '减'.C('config.Currency_symbol').'X2';
                    $dis_num = round($v['minus'],2);
                }
                $money_txt = C('config.Currency_symbol') . $obj[$key][$k]['money'];
				if($key == 'system_newuser'){
                    $returnObj[$k]['type'] = 'system_newuser';

                    if($obj[$key][$k]['order_count'] <= 1){
                        $returnObj[$k]['text'] = L_('平台首单X1'.$dis_text,array('X1'=>$money_txt,'X2'=>$dis_num));
                    }else{
                        $returnObj[$k]['text'] = L_('平台首X0单X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$money_txt,'X2'=>$dis_num));
                    }
                } else if($key == 'newuser'){
                    $returnObj[$k]['type'] = 'newuser';
                	if($obj[$key][$k]['order_count'] <= 1){
                		$returnObj[$k]['text'] = L_('店铺首单X1'.$dis_text,array('X1'=>$money_txt,'X2'=>$dis_num));
                    }else{
                		$returnObj[$k]['text'] = L_('店铺首X0单X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$money_txt,'X2'=>$dis_num));
                    }
                } else {
                    $returnObj[$k]['type'] = 'minus';
                	if($v['order_count'] > 1){
                		$returnObj[$k]['text'] = L_('首X0单X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$money_txt,'X2'=>$dis_num));
                	}elseif($v['order_count'] == 1){

                		$returnObj[$k]['text'] = L_('首单X1'.$dis_text,array('X1'=>$money_txt,'X2'=>$dis_num));
                	}else{
						if($obj[$key][$k]['money']>0){
							
                			$returnObj[$k]['text'] = L_('满X1'.$dis_text,array('X1'=>$money_txt,'X2'=>$dis_num));
						}else{
                			$returnObj[$k]['text'] = L_($dis_text,array('X1'=>$money_txt,'X2'=>$dis_num));
							
						}	
                	}
                }
            }
        }
		return $returnObj;
	}

	/* 解析优惠券 */
	public function parseCoupon($obj, $type)
    {
        $returnObj = array();
        foreach ($obj as $key => $value) {
            if ($key == 'invoice') {
                $returnObj[$key] = L_('满X1支持开发票，请在下单时填写发票抬头',C('config.Currency_symbol') . $obj[$key]);
            } elseif ($key == 'discount') {
                $returnObj[$key] = L_('店内全场X1折',$obj[$key]);
            } elseif ($key == 'isdiscountgoods') {
                $returnObj[$key] = L_('店内有部分商品限时优惠');
            } elseif ($key == 'isdiscountsort') {
                $returnObj[$key] = L_('部分商品分类参与折扣优惠');
            } else {
                $returnObj[$key] = [];
                foreach ($obj[$key] as $k => $v) {
                	if ($v['percentage_status']==1) {
                        $dis_text = '享X2折';
                        $dis_num = round($v['minus']*10,2);
                    }else{
                        $dis_text = '减'.C('Config.Currency_symbol').'X2';
                        $dis_num = round($v['minus'],2);
                    }

                    if($key == 'delivery'){
                    	if($obj[$key][$k]['order_count'] > 1){
	                        $returnObj[$key][] = L_('平台首X0单满'.C('Config.Currency_symbol').'X1配送'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                    	}elseif($obj[$key][$k]['order_count'] == 1){
	                        $returnObj[$key][] = L_('平台首单满'.C('Config.Currency_symbol').'X1配送'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                    	}else{
							if($obj[$key][$k]['order_count'] > 0){
								$returnObj[$key][] = L_('满'.C('Config.Currency_symbol').'X1'.$dis_text.'运费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
							}else{
								$returnObj[$key][] = L_($dis_text.'运费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
							} 
                    	}
                    } else if($key == 'system_newuser'){
                        if($obj[$key][$k]['order_count'] == 1){
                            $returnObj[$key][] = L_('平台首单X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                        }else{
                           	$returnObj[$key][] = L_('平台首X0单X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                        }
                    } else if($key == 'newuser'){
                    	if($obj[$key][$k]['order_count'] == 1){
	                        $returnObj[$key][] = L_('店铺首单X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                        }else{
	                        $returnObj[$key][] = L_('店铺首X0单X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                        }
                    } else {
                    	if($obj[$key][$k]['order_count'] > 1){
	                        $returnObj[$key][] = L_('首X0单满'.C('Config.Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                    	}elseif($obj[$key][$k]['order_count'] == 1){
	                        $returnObj[$key][] = L_('首单满'.C('Config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                    	}else{
							if($obj[$key][$k]['order_count'] > 0){
								$returnObj[$key][] = L_('满'.C('Config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
							}else{
								$returnObj[$key][] = L_($dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
							}	
                    	}
                    }
                }
            }
        }
        $textObj = array();
        foreach ($returnObj as $key => $value) {
            if ($key == 'invoice' || $key == 'discount' || $key == 'isdiscountgoods' || $key == 'isdiscountsort') {
                $textObj[$key] = $value;
            } else {
                switch ($key) {
                    case 'system_newuser':
                        //$textObj[$key] = L_('平台首单X1' ,implode(',', $value));
                        $textObj[$key] = L_(implode(',', $value));
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠X1' ,implode(',', $value));
                        break;
                    case 'newuser':
                        $textObj[$key] = L_('店铺首单X1' ,implode(',', $value));
                        break;
                    case 'minus':
                        $textObj[$key] = L_('店铺优惠X1' ,implode(',', $value));
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠X1' ,implode(',', $value));
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

    /**
     * 根据店铺ID获取店铺详情
     */
    public function getStoreInfoById($storeId)
    {
        if ($storeId < 1) {
            return [];
        } else {
            $info = M(_view('Merchant_store'))->alias('store')
                ->join(C('DB_PREFIX') . 'merchant_store_shop AS shop ON store.store_id = shop.store_id')
                ->where(['store.store_id' => $storeId])
                ->find();
            return $info;
        }
    }

    /**
     * 根据店铺ID获取店铺详情
     */
    public function getStoreById($storeId)
    {
        if ($storeId < 1) {
            return [];
        } else {
            $info = M(_view('Merchant_store'))
                ->where(['store_id' => $storeId])
                ->find();
            return $info;
        }
    }

    /**
     * 根据店铺id获取必选分类
     */
    public function getMustSortIdsByStoreId($storeId,$field='*')
    {
        $week = date('w');
        $where = [
            'store_id' => $storeId,
            'is_must' => 1,
            'status' => 1,
            'sort_status' => 1,
            '_string' => "(`week` = '' OR FIND_IN_SET(" . $week . ",`week`))"
        ];

        // 排除已关闭的分类
        $showSortIds = D('Shop_goods_sort')->getAllShowChildSortIds($storeId,[],true,true,true);
        if (!empty($showSortIds)) {
            $where['sort_id'] = ['in',$showSortIds];
        }else{
            $where['sort_id'] = null;
        }

        $mustSort = M('Shop_goods_sort')->where($where)->field($field)->find();
        return $mustSort ? $mustSort : [];
    }

    /**
     * 根据店铺ID获取必须分类商品信息
     */
    public function getMustGoodsIdById($storeId)
    {
        $mustSortId = $this->getMustSortIdsByStoreId($storeId);
        if ($mustSortId && is_array($mustSortId)) {
            $mustGoods = M('Shop_goods')->where(['sort_id' => ['IN', $mustSortId], 'status' => 1, 'store_id' => $storeId])->select();
        }
        return isset($mustGoods) && $mustGoods ? $mustGoods : [];
    }

    /**
     * 获取店铺起送价和加价送
     */
    public function getStoreBasePrice($storeId)
    {
        //deliver_type 0：平台配送   1：商家配送   2：客户自提   3：平台配送或自提  4：商家配送或自提   5：快递配送
        $now_store = D('Merchant_store')->where(['store_id' => $storeId])->find();
        $now_shop = D('Merchant_store_shop')->where(['store_id' => $storeId])->find();
        $row = array_merge($now_store, $now_shop);
        $store = [];
        $store['delivery_price'] = floatval($row['basic_price']);//起送价
        $deliverReturn = D('Deliver_set')->getDeliverInfo($row);
        $delivery_fee = $deliverReturn['delivery_fee'];
        $per_km_price = $deliverReturn['per_km_price'];
        $basic_distance = $deliverReturn['basic_distance'];

        $delivery_fee2 = $deliverReturn['delivery_fee2'];
        $per_km_price2 = $deliverReturn['per_km_price2'];
        $basic_distance2 = $deliverReturn['basic_distance2'];

        $delivery_fee3 = $deliverReturn['delivery_fee3'];
        $per_km_price3 = $deliverReturn['per_km_price3'];
        $basic_distance3 = $deliverReturn['basic_distance3'];

        $start_time = $deliverReturn['delivertime_start'];
        $stop_time = $deliverReturn['delivertime_stop'];

        $start_time2 = $deliverReturn['delivertime_start2'];
        $stop_time2 = $deliverReturn['delivertime_stop2'];

        $start_time3 = $deliverReturn['delivertime_start3'];
        $stop_time3 = $deliverReturn['delivertime_stop3'];

        $delivery_free = $deliverReturn['delivery_free'];//免配送费1
        $delivery_free2 = $deliverReturn['delivery_free2'];//免配送费2
        $delivery_free3 = $deliverReturn['delivery_free3'];//免配送费3
        $delivery_free_money = $deliverReturn['delivery_free_money'];//满￥免配送费1
        $delivery_free_money2 = $deliverReturn['delivery_free_money2'];//满￥免配送费2
        $delivery_free_money3 = $deliverReturn['delivery_free_money3'];//满￥免配送费3
        $store['delivery_money'] = $delivery_fee;

        if (getFormatNumber($deliverReturn['deliver_speed_hour']) && getFormatNumber($_POST['lat']) && getFormatNumber($_POST['lng'])) {
            if ($store['send_time_type'] == 0) {
                $jl = getDistance($_POST['lat'], $_POST['lng'], $row['lat'], $row['long']);
                //做单时长 + 配送时速 + 配送延迟时间
                $store['delivery_time'] = $row['work_time'] + round($jl / ($deliverReturn['deliver_speed_hour'] * 1000 / 60), 0) + $deliverReturn['deliver_delay_time'];
            }
        }

        $time = time();
        $selectDeliverTime = 0;
        if (!($start_time == $stop_time && $start_time == '00:00:00')) {
            $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
            $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
            if ($start_time > $stop_time) {
                if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time) {
                    $selectDeliverTime = 1;
                } else {
                    $stop_time2 += 86400;
                    if ($start_time <= $time && $time <= $stop_time) {
                        $selectDeliverTime = 1;
                    }
                }
            } elseif ($start_time <= $time && $time <= $stop_time) {
                $selectDeliverTime = 1;
            }
            if (!($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);

                if ($start_time2 > $stop_time2) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                        $store['delivery_money'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time2 <= $time && $time <= $stop_time2) {
                            $store['delivery_money'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                        }
                    }
                } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                    $store['delivery_money'] = $delivery_fee2;
                    $selectDeliverTime = 2;
                }
            }
            if (!($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);

                if ($start_time3 > $stop_time3) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                        $store['delivery_money'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                    } else {
                        $stop_time3 += 86400;
                        if ($start_time3 <= $time && $time <= $stop_time3) {
                            $store['delivery_money'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                        }
                    }
                } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                    $store['delivery_money'] = $delivery_fee3;
                    $selectDeliverTime = 3;
                }
            }
        } else {
            $selectDeliverTime = 1;
        }

        $this->config = C('config');
        $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['area_id'], 'status' => 1))->find();
        if (empty($set)) {
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['city_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['province_id'], 'status' => 1))->find();
            }
        }
        if ($selectDeliverTime == 1) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price1']) > -1 ? floatval($row['s_basic_price1']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);

                // 平台设置的配送优惠
                if ($delivery_free == 1) {
                    $store['deliver_discount'] = array('money' => 0, 'type' => 'deliver_free');
                } elseif ($delivery_free == 2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money, 'type' => 'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($row['basic_price1']) ? floatval($row['basic_price1']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        } elseif ($selectDeliverTime == 2) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price2']) > -1 ? floatval($row['s_basic_price2']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);

                // 平台设置的配送优惠
                if ($delivery_free2 == 1) {
                    $store['deliver_discount'] = array('money' => 0, 'type' => 'deliver_free');
                } elseif ($delivery_free2 == 2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money2, 'type' => 'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($row['basic_price2']) ? floatval($row['basic_price2']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }

        } elseif ($selectDeliverTime == 3) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price3']) > -1 ? floatval($row['s_basic_price3']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
                // 平台设置的配送优惠
                if ($delivery_free3 == 1) {
                    $store['deliver_discount'] = array('money' => 0, 'type' => 'deliver_free');
                } elseif ($delivery_free3 == 2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money3, 'type' => 'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($row['basic_price3']) ? floatval($row['basic_price3']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        } else {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price']));//起送价
                $store['extra_price'] = isset($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }
        if ($store['deliver_type'] == 2) { //只有自提的情况下，去掉加价送
            $store['extra_price'] = 0;
        }
        if ($row['s_is_open_virtual'] == 1) {
            $store['delivery_money'] = floatval($row['virtual_delivery_fee']);
        } else {
            $store['delivery_money'] = floatval($store['delivery_money']);
        }
        return $store;
    }

    /**
     * 判断是否在城市配送区域
     */
    public function checkInStoreDeliverRange($storeid, $userLat, $userLng)
    {
        $sql = "SELECT ROUND( 6378.137 * 2 * ASIN( SQRT( POW( SIN(( {$userLat} * PI() / 180 - `s`.`lat` * PI() / 180 ) / 2 ), 2 ) + COS({$userLat} * PI() / 180) * COS(`s`.`lat` * PI() / 180) * POW( SIN(( $userLng * PI() / 180 - `s`.`long` * PI() / 180 ) / 2 ), 2 ))) * 1000 ) AS juli, s.*, m.* FROM pigcms_merchant_store AS s INNER JOIN pigcms_merchant_store_shop AS m ON m.store_id = s.store_id WHERE s.store_id = {$storeid} LIMIT 1";

        $store = $this->query($sql);
        if (empty($store)) {
            return [];
        }
        $store = $store[0];
        $store['deliver'] = $store['deliver_type'] == 2 ? false : true;

        $newLatLong = array();
        if (C('config.is_riding_distance') && C('config.riding_only_settlement') != 1) {
            $storePos = $store['lat'] . ',' . $store['long'];
            $url = 'http://api.map.baidu.com/routematrix/v2/riding?destinations=' . $storePos . '&origins=' . $userLat . ',' . $userLng . '&ak=' . C('config.baidu_map_ak') . '&output=json';
            import('ORG.Net.Http');
            $http = new Http();
            $result = $http->curlGet($url);
            if ($result) {
                $result = json_decode($result, true);
                if ($result['status'] == 0) {
                    $newLatLong = $result['result'];
                }
            }
        }

        import('@.ORG.longlat');
        $longlatClass = new longlat();
        if (empty($newLatLong) || empty($newLatLong[0]['distance']['value'])) {
            if ($store['juli']) {
                $store['range'] = getRange($store['juli']);
                $jl = $store['juli'];
            } else {
                if (C("config.map_config") != 'google') {
                    $location2 = $longlatClass->gpsToBaidu($store['lat'], $store['long']); // 转换腾讯坐标到百度坐标
                } else {
                    $location2 = ['lng' => $store['long'], 'lat' => $store['lat']];
                }
                $jl = getDistance($location2['lat'], $location2['lng'], $userLat, $userLng);
                $store['range'] = getRange($jl);
            }
        } else {
            $tLatLng = array_shift($newLatLong);
            $store['range'] = $tLatLng['distance']['text'];
            $jl = $tLatLng['distance']['value'];
        }

        if ($store['delivery_range_type'] == 1) {
            if ($store['delivery_range_polygon'] != '') {
                $v_delivery_range_polygon = str_replace('POLYGON((', '', $store['delivery_range_polygon']);
                $v_delivery_range_polygon = str_replace('))', '', $v_delivery_range_polygon);
                $v_polygon = explode(',', $v_delivery_range_polygon);
                foreach ($v_polygon as $v_k => $v_po) {
                    $v_arr = explode(' ', $v_po);
                    $v_polygon_arr[$v_k]['lng'] = $v_arr[0];
                    $v_polygon_arr[$v_k]['lat'] = $v_arr[1];
                }
                $v_polygon_latlng[] = $v_polygon_arr;
                $r = isPtInPoly($userLng, $userLat, $v_polygon_latlng);
                if (in_array($store['deliver_type'], array(3, 4)) && !$r) {
                    $store['deliver'] = 0;
                }
            }
        } else {
            if (in_array($store['deliver_type'], array(3, 4)) && $jl > $store['delivery_radius'] * 1000) {
                $store['deliver'] = 0;
            }
        }
        return $store;
    }

	/**
	 * @desc获取店铺各个业务上下架情况
	 * @param $store_id
	 * @return array
	 */
	public function get_business_on_sale($store_id){
		if(!$store_id) return array();
		$where = array();
		$where['store_id'] = $store_id;
		$field = 'group_on_sale,foodshop_on_sale,shop_on_sale,mall_on_sale';
		$info = $this->where($where)->field($field)->find();
		return $info;
	}

	/**
	 * @desc设置各个业务上下架
	 * @param $store_id
	 * @param $data
	 * @return bool
	 */
	public function set_business_on_sale($store_id,$data){
		if(!$store_id) return false;
		$where = array();
		$where['store_id'] = $store_id;
		$save_data = array();
		$save_data['group_on_sale'] = $data['group_on_sale'];
		$save_data['foodshop_on_sale'] = $data['foodshop_on_sale'];
		$save_data['shop_on_sale'] = $data['shop_on_sale'];
		$save_data['mall_on_sale'] = $data['mall_on_sale'];
		$r = $this->where($where)->data($save_data)->save();
		if($r !== false){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @desc 获取各个业务上架店铺id集合
	 * @param $business：group=>团购,foodshop=>餐饮,shop=>快店,mall=>商城
	 * @return array
	 */
	public function get_single_on_sale($business){
		$business_config = array(
			'group'    =>  'group_on_sale',
			'foodshop' =>  'foodshop_on_sale',
			'shop'     =>  'shop_on_sale',
			'mall'     =>  'mall_on_sale',
		);
		if(empty($business_config[$business])) return array();
		$key = $business_config[$business];
		$where = array();
		$where[$key] = 1;
		$return = array();
		$list = $this->where($where)->field('store_id')->select();
		if($list){
			foreach($list as $val){
				$return[] = $val['store_id'];
			}
		}
		return $return;
	}

	/**
	 * [get_store_name_lang 获取多语言环境下店铺名称]
	 * @Author   JJC
	 * @DateTime 2020-05-14T13:15:16+0800
	 * @param    [type]                   $ids  [店铺id集合]
	 * @param    [type]                   $lang [语言环境]
	 * @return   [type]                         [description]
	 */
	public function get_store_name_lang($ids,$lang='chinese'){
		if(empty($ids)){
			return [];
		}
		$db_name = 'Merchant_store_'.$lang;
		$key_name = 'store_id_'.$lang;

		$store_list = M($db_name)->where([$key_name=>['in',$ids]])->select();
		$store_arr = [];

		$store_name_key = 'name_'.$lang;
		foreach ($store_list as $key => $value) {
			$store_arr[$value[$key_name]] = $value[$store_name_key];
		}
		return $store_arr;
	}

	/**
	 * [获得店铺配送信息]
	 * @Author   JJC
	 * @DateTime 2020-05-14T13:15:16+0800
	 * @param    [type]                   $ids  [店铺id集合]
	 * @param    [type]                   $lang [语言环境]
	 * @return   [type]                         [description]
	 */
	public function get_store_deliver_info($store, $lng='', $lat=''){

		$send_time_type = [
			L_('分钟'),
			L_('小时'),
			L_('天'),
			L_('周'),
			L_('月'),
		]; 
		
		//配送时长
		if ($store['send_time_type'] == 0) {
		    $store['delivery_time'] = $store['sort_time'];
		} elseif ($store['send_time_type'] == 1) {
		    $store['delivery_time'] = floatval(round($store['sort_time'] / 60, 2));
		} elseif ($store['send_time_type'] == 2) {
		    $store['delivery_time'] = floatval(round($store['sort_time'] / 1440, 2));
		} elseif ($store['send_time_type'] == 3) {
		    $store['delivery_time'] = floatval(round($store['sort_time'] / 10080, 2));
		} elseif ($store['send_time_type'] == 4) {
		    $store['delivery_time'] = floatval(round($store['sort_time'] / 43200, 2));
		}

 		$latLng = array();
        $latLng[] = $store['lat'] . ',' . $store['long'];   

		$newLatLong = array();
        if (C('config.is_riding_distance') && $latLng && C('config.riding_only_settlement') != 1) {
            $url = 'http://api.map.baidu.com/routematrix/v2/riding?destinations=' . implode('|', $latLng) . '&origins=' . $lat . ',' . $lng . '&ak=' . C('config.baidu_map_ak') . '&output=json';
            import('ORG.Net.Http');
            $http = new Http();
            $result = $http->curlGet($url);
            if ($result) {
                $result = json_decode($result, true);
                if ($result['status'] == 0) {
                    $newLatLong = $result['result'];
                }
            }
        }


        if (empty($newLatLong) || empty($newLatLong[0]['distance']['value'])) {
            if ($store['juli']) {
                $store['range'] = getRange($v['juli']);
                $jl = $store['juli'];
            } else {
                if(C("config.map_config") != 'google'){
			        import('@.ORG.longlat');
			        $longlat_class = new longlat();
                    $location2 = $longlat_class->gpsToBaidu($store['lat'], $store['long']); // 转换腾讯坐标到百度坐标
                }
                else{
                    $location2 = ['lng'=>$store['long'],'lat'=>$store['lat']];
                }
                $jl = getDistance($location2['lat'], $location2['lng'], $lat, $lng);
                $store['range'] = getRange($jl);
            }
        } else {
            $tLatLng = array_shift($newLatLong);
            //$v['range'] = $tLatLng['distance']['text'];
            $store['range'] = getRange($tLatLng['distance']['value'], false, true);
            $jl = $tLatLng['distance']['value'];
        }

            if( $store['store_id'] == 440){
        fdump($store['range'],'get_store_deliver_info',1);
            }

        if(!$lat || !$lng){
            $store['range'] = 0;
            $store['send_time'] = 0;
        }

		if($store['delivery_range_type']==1){
			if($store['delivery_range_polygon']!=''){
				$v_delivery_range_polygon = str_replace('POLYGON((','',$store['delivery_range_polygon']);
				$v_delivery_range_polygon = str_replace('))','',$v_delivery_range_polygon);
				$v_polygon = explode(',',$v_delivery_range_polygon);
				foreach($v_polygon as $v_k => $v_po){
					$v_arr = explode(' ',$v_po);
					$v_polygon_arr[$v_k]['lng'] = $v_arr[0];
					$v_polygon_arr[$v_k]['lat'] = $v_arr[1];
				}
				$v_polygon_latlng[] = $v_polygon_arr;
				$r = isPtInPoly($long,$lat,$v_polygon_latlng);
				if(in_array($store['deliver_type'], array(3, 4)) && !$r){
					$store['deliver'] = 0;
				}
			}
		}else{
			if (in_array($store['deliver_type'], array(3, 4)) && $jl > $store['delivery_radius'] * 1000) {
				$store['deliver'] = 0;
			}
		}

		
		$store['send_time_type'] = $store['send_time_type'];
		$store['delivery_time_type'] = $send_time_type[$store['send_time_type']];
		$store['delivery_price'] = getFormatNumber($store['basic_price']);//起送价

		//配送时间
		$store['delivery'] = $store['deliver_type'] == 2 ? false : true;//是否支持配送
		

		$deliverReturn = D('Deliver_set')->getDeliverInfo($store);
		$delivery_fee = $deliverReturn['delivery_fee'];
		$per_km_price = $deliverReturn['per_km_price'];
		$basic_distance = $deliverReturn['basic_distance'];
		
		$delivery_fee2 = $deliverReturn['delivery_fee2'];
		$per_km_price2 = $deliverReturn['per_km_price2'];
		$basic_distance2 = $deliverReturn['basic_distance2'];
		
		$delivery_fee3 = $deliverReturn['delivery_fee3'];
		$per_km_price3 = $deliverReturn['per_km_price3'];
		$basic_distance3 = $deliverReturn['basic_distance3'];
		
		$start_time = $deliverReturn['delivertime_start'];
		$stop_time = $deliverReturn['delivertime_stop'];
		
		$start_time2 = $deliverReturn['delivertime_start2'];
		$stop_time2 = $deliverReturn['delivertime_stop2'];
		
		$start_time3 = $deliverReturn['delivertime_start3'];
		$stop_time3 = $deliverReturn['delivertime_stop3'];

        $delivery_free = $deliverReturn['delivery_free'];//免配送费1
        $delivery_free2 = $deliverReturn['delivery_free2'];//免配送费2
        $delivery_free3 = $deliverReturn['delivery_free3'];//免配送费3
        $delivery_free_money = $deliverReturn['delivery_free_money'];//满￥免配送费1
        $delivery_free_money2 = $deliverReturn['delivery_free_money2'];//满￥免配送费2
        $delivery_free_money3 = $deliverReturn['delivery_free_money3'];//满￥免配送费3
		$store['delivery_money'] = $delivery_fee;
        $store['delivery_fee'] = $delivery_fee;
		if(getFormatNumber($deliverReturn['deliver_speed_hour']) && getFormatNumber($lat) && getFormatNumber($lng)){
			if ($store['send_time_type'] == 0) {
				//做单时长 + 配送时速 + 配送延迟时间
				$store['delivery_time'] = $store['work_time'] + round($jl / ($deliverReturn['deliver_speed_hour'] * 1000 / 60),0) + $deliverReturn['deliver_delay_time']; 
			}
		}

		$time = time();
        $selectDeliverTime = 0;
        if (! ($start_time == $stop_time && $start_time == '00:00:00')) {
            $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
            $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
            if ($start_time > $stop_time) {
                if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time) {
                    $selectDeliverTime = 1;
                } else {
                    $stop_time2 += 86400;
                    if ($start_time <= $time && $time <= $stop_time) {
                        $selectDeliverTime = 1;
                    }
                }
            } elseif ($start_time <= $time && $time <= $stop_time) {
                $selectDeliverTime = 1;
            }
            if (! ($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);

                if ($start_time2 > $stop_time2) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                        $store['delivery_money'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time2 <= $time && $time <= $stop_time2) {
                            $store['delivery_money'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                        }
                    }
                } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                    $store['delivery_money'] = $delivery_fee2;
                    $selectDeliverTime = 2;
                }
            }
            if (! ($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);

                if ($start_time3 > $stop_time3) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                        $store['delivery_money'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                    } else {
                        $stop_time3 += 86400;
                        if ($start_time3 <= $time && $time <= $stop_time3) {
                            $store['delivery_money'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                        }
                    }
                } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                    $store['delivery_money'] = $delivery_fee3;
                    $selectDeliverTime = 3;
                }
            }
        }else{
            $selectDeliverTime = 1;
        }

        $set = D('Deliver_set')->field(true)->where(array('area_id' => $store['area_id'], 'status' => 1))->find();
        if (empty($set)) {
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $store['city_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $store['province_id'], 'status' => 1))->find();
            }
        }
        if ($selectDeliverTime == 1) {
            if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
                if ($store['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($store['s_basic_price1']) > -1 ? floatval($store['s_basic_price1']) : (floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval(C('config.basic_price1')) ? floatval(C('config.basic_price1')) : (C('config.basic_price') ? floatval(C('config.basic_price')) : floatval($store['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval(C('config.basic_price1')) ? floatval(C('config.basic_price1')) : (C('config.basic_price') ? floatval(C('config.basic_price')) : floatval($store['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($store['s_extra_price']) ? floatval($store['s_extra_price']) : floatval(C('config.extra_price'));

                // 平台设置的配送优惠
                if ($delivery_free==1) {
                    $store['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                }elseif ($delivery_free==2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money, 'type'=>'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($store['basic_price1']) ? floatval($store['basic_price1']) : floatval($store['basic_price']);//起送价
                $store['extra_price'] = floatval($store['extra_price']);//不满起送价另付金额支持配送
            }
        } elseif ($selectDeliverTime == 2) {
            if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
                if ($store['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($store['s_basic_price2']) > -1 ? floatval($store['s_basic_price2']) : (floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval(C('config.basic_price2')) ? floatval(C('config.basic_price2')) : (C('config.basic_price') ? floatval(C('config.basic_price')) : floatval($store['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval(C('config.basic_price2')) ? floatval(C('config.basic_price2')) : (C('config.basic_price') ? floatval(C('config.basic_price')) : floatval($store['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($store['s_extra_price']) ? floatval($store['s_extra_price']) : floatval(C('config.extra_price'));

                // 平台设置的配送优惠
                if ($delivery_free2==1) {
                    $store['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                }elseif ($delivery_free2==2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money2, 'type'=>'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($store['basic_price2']) ? floatval($store['basic_price2']) : floatval($store['basic_price']);//起送价
                $store['extra_price'] = floatval($store['extra_price']);//不满起送价另付金额支持配送
            }

        } elseif ($selectDeliverTime == 3) {
            if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
                if ($store['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($store['s_basic_price3']) > -1 ? floatval($store['s_basic_price3']) : (floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval(C('config.basic_price3')) ? floatval(C('config.basic_price3')) : (C('config.basic_price') ? floatval(C('config.basic_price')) : floatval($store['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval(C('config.basic_price3')) ? floatval(C('config.basic_price3')) : (C('config.basic_price') ? floatval(C('config.basic_price')) : floatval($store['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($store['s_extra_price']) ? floatval($store['s_extra_price']) : floatval(C('config.extra_price'));
                // 平台设置的配送优惠
                if ($delivery_free3==1) {
                    $store['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                }elseif ($delivery_free3==2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money3, 'type'=>'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($store['basic_price3']) ? floatval($store['basic_price3']) : floatval($store['basic_price']);//起送价
                $store['extra_price'] = floatval($store['extra_price']);//不满起送价另付金额支持配送
            }
        } else {
            if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
                $store['delivery_price'] = floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (C('config.basic_price') ? floatval(C('config.basic_price')) : floatval($store['basic_price']));//起送价
                $store['extra_price'] = floatval($store['s_extra_price']) ? floatval($store['s_extra_price']) : floatval(C('config.extra_price'));
            } else {
                $store['delivery_price'] = floatval($store['basic_price']);//起送价
                $store['extra_price'] = floatval($store['extra_price']);//不满起送价另付金额支持配送
            }
        }
		
		if($store['delivery_price']){
			$store['delivery_price'] = getFormatNumber($store['delivery_price']);
		}
		
        if ($store['deliver_type'] == 2) { //只有自提的情况下，去掉加价送
            $store['extra_price'] = 0;
        }
        if ($store['s_is_open_virtual'] == 1) {
            $store['delivery_money'] = floatval($store['virtual_delivery_fee']);
        } else {
            $store['delivery_money'] = floatval($store['delivery_money']);
        }
// 		$store['delivery_money'] = $store['deliver_type'] == 0 ? C('config.delivery_fee') : $store['delivery_fee'];//配送费
// 		$store['delivery_money'] = floatval($store['delivery_money']);//配送费
        $store['delivery_system'] = $store['deliver_type'] == 0 || $store['deliver_type'] == 3 ? true : false;//是否是平台配送
        if (in_array($store['deliver_type'], array(2, 3, 4))) {
            $store['pick'] = 1;//是否支持自提
        } else {
            $store['pick'] = 0;//是否支持自提
        }
        $store['pack_alias'] = $store['pack_alias'];//打包费别名
        $store['freight_alias'] = $store['freight_alias'];//运费别名
		return $store;
	}
	
	public function storeIsBooking($storeId)
    {
        //copy from cms/Lib/Action/Appapi/Shop_newAction.class.php
        $now_store = D(_view('Merchant_store'))->where(['store_id' => $storeId])->find();
        $now_shop = D(_view('Merchant_store_shop'))->where(['store_id' => $storeId])->find();
        $row = array_merge($now_store, $now_shop);
        $next_time = D('Merchant_store_open_time')->get_next_time($row['store_id']);
        $store['next_time'] = ($now_store['is_business_open']&&!$now_store['is_close'])? ['1'=>'1'] : $next_time;
        
        if($row['is_close']){
            $store['is_close'] = 1;
            $store['time_arr'] = [];
            $store['time'] = L_('紧急关店');
        }else{
            $store['close_reason'] = '';
            $store['is_booking'] = false;
            if(D('Merchant_store_shop')->checkTime($row)){
                $store['is_close'] = 0;
            }else{
                $store['is_close'] = 1;
                $store['advance_day'] = $row['advance_day'];
                if($store['advance_day'] > 0){
                    $store['is_booking'] = true;
                    $store['is_close'] = 0;
                }
            }
        }
        if($store['next_time']['type']==3){//如果没有营业时间，则是关店状态且不可预定
            $store['is_close'] = 1;
            $store['is_booking'] = false;
        }
        
        return $store;
    }
    
    public function miaomaiGoodsSync($merId, $storeId, $page = 1)
    {
        import("@.ORG.Erp.MiaomaiErpService");
        
        return MiaomaiErpService::syncErpGoodsToSystem($merId, $storeId, $page);
    }
}
?>