<?php
class Live_goodsModel extends Model
{
	//通过ID串获取商品信息
	public function get_by_ids($ids,$field = ['img', 'name'], $deal = true, $param = []){
		if(empty($ids)) return [];
		if(!is_array($ids)){
			$ids = trim($ids,',');
		}
		$where = [
			'status' => 1,
			'id' => ['in', $ids]
		];

		if($param['mer_id']){
			$where['mer_id'] = $param['mer_id'];
		}
		$data = $this->field("id,business,business_id")->where($where)->select();

		$deal_data = $this->get_fields($data, $field);
		return $deal ? array_values($deal_data) : $deal_data;
	}


	//通过ID串获取数据
	public function getList($condition,$field = true){
		if(empty($condition)) return [];
		
		$data = $this->field($field)->where($condition)->select();

		return $data;
	}

	public function get_info($id){
		return $this->where(['id'=>$id])->find();
	}

	//获取各业务信息
	public function get_fields($data, $field = ['img', 'name']){
		$return = [];
		$business = [];
		foreach ($data as $key => $value) {
			switch ($value['business']) {
				case 'shop':
					$business['shop'][] = $value['business_id'];
					break;
				case 'group':
					$business['group'][] = $value['business_id'];
					break;
				case 'village_group':
					$business['village_group'][] = $value['business_id'];
					break;
				case 'mall':
					$business['mall'][] = $value['business_id'];
					break;
				case 'meal':
					$business['meal'][] = $value['business_id'];
					break;
				case 'mall3':
					$business['mall3'][] = $value['business_id'];
					break;
			}
		}
		$shops = [];//外卖商品信息
		$groups = [];//团购商品信息
		$malls = [];//商城商品信息
		$mall3s = [];//新版商城商品信息
		$meals = [];//餐饮商品信息
		if($business['shop']){
			$shops = array_column(M("Shop_goods")->alias("a")->join("left join ".C("DB_PREFIX")."merchant_store as b on a.store_id=b.store_id")->where(['a.goods_id'=>['in', $business['shop']]])->field("a.goods_id,a.name,a.image,a.price,a.stock_num as stock,b.mer_id,b.store_id,a.status,b.name as store_name,b.long,b.lat,b.adress")->select(), NULL, 'goods_id');
		}
		if($business['group']){
			$groups = array_column(M("Group")->alias("a")->join("left join ".C("DB_PREFIX")."merchant as b on a.mer_id=b.mer_id")->where(['a.group_id'=>['in', $business['group']]])->field("a.group_id as goods_id,a.s_name as name,a.pic as image,a.price,a.count_num as stock,b.mer_id,sale_count,a.status,a.old_price,a.wx_cheap")->select(), NULL, 'goods_id');
		}
		if($business['village_group']){
			$villageGroupGoods = array_column(M("Village_group_goods")
                ->alias("a")
                ->join("left join ".C("DB_PREFIX")."merchant as b on a.mer_id=b.mer_id")
                ->where(['a.goods_id'=>['in', $business['village_group']]])
                ->field("a.goods_id,a.name,a.image,a.price,a.stock_num as stock,a.today_sell_count,b.mer_id, sell_count as sale_count,a.status,a.old_price")
                ->select(), NULL, 'goods_id');
		}
		if($business['mall']){
			$malls = array_column(M("Shop_goods")->alias("a")->join("left join ".C("DB_PREFIX")."merchant_store as b on a.store_id=b.store_id")->where(['a.goods_id'=>['in', $business['mall']]])->field("a.goods_id,a.name,a.image,a.price,a.stock_num as stock,b.mer_id,b.store_id,a.status,b.name as store_name,b.long,b.lat,b.adress")->select(), NULL, 'goods_id');
		}
		if($business['meal']){
			$meals = array_column(M("Foodshop_goods")->alias("a")->join("left join ".C("DB_PREFIX")."merchant_store as b on a.store_id=b.store_id")->where(['a.goods_id'=>['in', $business['meal']]])->field("a.goods_id,a.name,a.image,a.price,a.old_price,a.stock_num as stock,b.mer_id,b.store_id,b.name as store_name,b.long,b.lat,b.adress")->select(), NULL, 'goods_id');
		}
		if($business['mall3']){
			$mall3s = array_column(M("Mall_goods")->alias("a")->join("left join ".C("DB_PREFIX")."merchant_store as b on a.store_id=b.store_id")->where(['a.goods_id'=>['in', $business['mall3']]])->field("a.goods_id,a.name,a.image,a.price,a.stock_num as stock,b.mer_id,b.store_id,a.status,b.name as store_name,b.long,b.lat,b.adress")->select(), NULL, 'goods_id');
		}

		import('@.ORG.group_image');
		$group_image_class = new group_image();
		import('@.ORG.foodshop_goods_image');
		$foodshop_image_class = new foodshop_goods_image();
		foreach ($data as $key => &$value) {
			$return[$value['id']]['business'] = $value['business'];//业务代号
			$return[$value['id']]['real_goods_id'] = $value['business_id'];//业务商品ID
			$return[$value['id']]['id'] = $value['id'];
            $return[$value['id']]['offline'] = 0;
			switch ($value['business']) {
				case 'shop':
					$return[$value['id']]['mer_id'] = $shops[$value['business_id']]['mer_id'];
					$return[$value['id']]['store_id'] = $shops[$value['business_id']]['store_id'];
					if($shops[$value['business_id']]['status'] != 1){
                        $return[$value['id']]['offline'] = 1;
                    }

					$return[$value['id']]['store_name'] = $shops[$value['business_id']]['store_name'];//店铺名称
					$return[$value['id']]['address'] = $shops[$value['business_id']]['adress'];//店铺地址
					$return[$value['id']]['long'] = $shops[$value['business_id']]['long'];//店铺经度
					$return[$value['id']]['lat'] = $shops[$value['business_id']]['lat'];//店铺维度
					break;
				case 'mall':
					$return[$value['id']]['mer_id'] = $malls[$value['business_id']]['mer_id'];
					$return[$value['id']]['store_id'] = $malls[$value['business_id']]['store_id'];
					$return[$value['id']]['old_price'] = $malls[$value['business_id']]['old_price'];
                    if($malls[$value['business_id']]['status'] != 1){
                        $return[$value['id']]['offline'] = 1;
                    }
					$return[$value['id']]['store_name'] = $malls[$value['business_id']]['store_name'];//店铺名称
					$return[$value['id']]['address'] = $malls[$value['business_id']]['adress'];//店铺地址
					$return[$value['id']]['long'] = $malls[$value['business_id']]['long'];//店铺经度
					$return[$value['id']]['lat'] = $malls[$value['business_id']]['lat'];//店铺维度
					break;
				case 'mall3':
					$return[$value['id']]['mer_id'] = $mall3s[$value['business_id']]['mer_id'];
					$return[$value['id']]['store_id'] = $mall3s[$value['business_id']]['store_id'];
                    if($mall3s[$value['business_id']]['status'] != 1){
                        $return[$value['id']]['offline'] = 1;
                    }
					$return[$value['id']]['store_name'] = $mall3s[$value['business_id']]['store_name'];//店铺名称
					$return[$value['id']]['address'] = $mall3s[$value['business_id']]['adress'];//店铺地址
					$return[$value['id']]['long'] = $mall3s[$value['business_id']]['long'];//店铺经度
					$return[$value['id']]['lat'] = $mall3s[$value['business_id']]['lat'];//店铺维度
					break;
				case 'group':
					$return[$value['id']]['mer_id'] = $groups[$value['business_id']]['mer_id'];
					$return[$value['id']]['store_id'] = 0;//团购没有所属店铺一说
					$return[$value['id']]['old_price'] = $groups[$value['business_id']]['old_price'] ?: 0;
					$return[$value['id']]['wx_cheap'] = $groups[$value['business_id']]['wx_cheap'] ?: 0;
                    if($groups[$value['business_id']]['status'] != 1){
                        $return[$value['id']]['offline'] = 1;
                    }
					break;
                case 'village_group':
                    $value['mer_id'] = $villageGroupGoods[$value['business_id']]['mer_id'];
                    $value['store_id'] = 0;//团购没有所属店铺一说
                    $value['old_price'] = $villageGroupGoods[$value['business_id']]['old_price'] ?: 0;
                    $value['wx_cheap'] = 0;
                    if($groups[$value['business_id']]['status'] != 1){
                        $value['offline'] = 1;
                    }
                    break;
				case 'meal':
					$return[$value['id']]['mer_id'] = $meals[$value['business_id']]['mer_id'];
					$return[$value['id']]['store_id'] = $meals[$value['business_id']]['store_id'];
					$return[$value['id']]['store_name'] = $meals[$value['business_id']]['store_name'];//店铺名称
					$return[$value['id']]['address'] = $meals[$value['business_id']]['adress'];//店铺地址
					$return[$value['id']]['long'] = $meals[$value['business_id']]['long'];//店铺经度
					$return[$value['id']]['lat'] = $meals[$value['business_id']]['lat'];//店铺维度
					break;
			}
			if(in_array('price', $field)){//商品价格
				$return[$value['id']]['price'] = "";
				switch ($value['business']) {
					case 'shop':
						$return[$value['id']]['price'] = $shops[$value['business_id']]['price'];
						break;
					case 'mall':
						$return[$value['id']]['price'] = $malls[$value['business_id']]['price'];
						break;
					case 'group':
						$return[$value['id']]['price'] = $groups[$value['business_id']]['price'];
						break;
                    case 'village_group':
                        $return[$value['id']]['price'] = $villageGroupGoods[$value['business_id']]['price'];
                        break;	
					case 'meal':
						$return[$value['id']]['price'] = $meals[$value['business_id']]['price'];
						break;
					case 'mall3':
						$return[$value['id']]['price'] = $mall3s[$value['business_id']]['price'];
						break;
				}
			}
			if(in_array('stock', $field)){//商品库存
				$return[$value['id']]['stock'] = "";
				switch ($value['business']) {
					case 'shop':
						$return[$value['id']]['stock'] = $shops[$value['business_id']]['stock'];
						break;
					case 'mall':
						$return[$value['id']]['stock'] = $malls[$value['business_id']]['stock'];
						break;
					case 'mall3':
						$return[$value['id']]['stock'] = $mall3s[$value['business_id']]['stock'];
						break;
					case 'group':
						$return[$value['id']]['stock'] = $groups[$value['business_id']]['stock'] <= 0 ? -1 : ($groups[$value['business_id']]['stock']-$groups[$value['business_id']]['sale_count'] >= 0 ? $groups[$value['business_id']]['stock']-$groups[$value['business_id']]['sale_count'] : 0);
						break;
                    case 'village_group':
                        $value['stock'] = $villageGroupGoods[$value['business_id']]['stock']-$villageGroupGoods[$value['business_id']]['today_sell_count'];
                        break;
					case 'meal':
						$return[$value['id']]['stock'] = $meals[$value['business_id']]['stock'];
						break;
				}
			}
			if(in_array('img', $field)){//商品图片
				$return[$value['id']]['img'] = "";
				switch ($value['business']) {
					case 'shop':
						$imgs = $shops[$value['business_id']]['image'] ? explode(';', $shops[$value['business_id']]['image']) : [];
						$return[$value['id']]['img'] = !$imgs[0] ? "" : replace_file_domain($imgs[0]);
						break;
					case 'mall':
						$imgs = $malls[$value['business_id']]['image'] ? explode(';', $malls[$value['business_id']]['image']) : [];
						$return[$value['id']]['img'] = !$imgs[0] ? "" : replace_file_domain($imgs[0]);
						break;
					case 'mall3':
						$return[$value['id']]['img'] = replace_file_domain($mall3s[$value['business_id']]['image']);
						break;
					case 'group':
						if(strpos($groups[$value['business_id']]['image'],';') !== false){
							$groups[$value['business_id']]['image'] = explode(';', $groups[$value['business_id']]['image'])[0];
						}
						$return[$value['id']]['img'] = !$groups[$value['business_id']]['image'] ? "" : $group_image_class->get_image_by_path($groups[$value['business_id']]['image'])['image'];
						break;
                    case 'village_group':
                        $return[$value['id']]['img'] = replace_file_domain($villageGroupGoods[$value['business_id']]['image']);
                        break;
					case 'meal':
						$return[$value['id']]['img'] = !$meals[$value['business_id']]['image'] ? "" : $foodshop_image_class->get_image_by_path($meals[$value['business_id']]['image'],'s');
						break;
				}
				$return[$value['id']]['img'] = $return[$value['id']]['img'] ? thumb_img($return[$value['id']]['img'],'240','240','fill') : C('config.site_url').'/static/images/live_goods_default.png';
			}

			if(in_array('name', $field)){//商品名称
				$return[$value['id']]['name'] = "";
				switch ($value['business']) {
					case 'shop':
						$return[$value['id']]['name'] = $shops[$value['business_id']]['name'];
						break;
					case 'mall':
						$return[$value['id']]['name'] = $malls[$value['business_id']]['name'];
						break;
					case 'mall3':
						$return[$value['id']]['name'] = $mall3s[$value['business_id']]['name'];
						break;
					case 'group':
						$return[$value['id']]['name'] = $groups[$value['business_id']]['name'];
						break;
					case 'village_group':
						$return[$value['id']]['name'] = $villageGroupGoods[$value['business_id']]['name'];
						break;
					case 'meal':
						$return[$value['id']]['name'] = $meals[$value['business_id']]['name'];
						break;
				}
			}	
		}
		return $return;
	}

	//获取门店设置的直播短视频商品数量
	public function get_store_goods_nums($store_id){
		if(empty($store_id)) return 0;
		$where['status'] = 1;
		$where['store_id'] = $store_id;
		return $this->where($where)->count();
	}

	//修改商品的直播状态
	public function update_goods_live_status($ids, $live_id = 0){
		if(empty($ids)) return false;
		if(!is_array($ids)){
			$ids = trim($ids,',');
		}
		$where = [
			'id' => ['in', $ids]
		];
		$data = $this->field("id,business,business_id")->where($where)->select();

		foreach ($data as $key => $value) {
			switch ($value['business']) {
				case 'shop':
				case 'mall':
					M('Shop_goods')->where(['goods_id'=>$value['business_id']])->data(['current_liveshow_id'=>$live_id])->save();
					break;
				case 'mall3':
					M('Mall_goods')->where(['goods_id'=>$value['business_id']])->data(['current_liveshow_id'=>$live_id])->save();
					break;
				case 'group':
					M('Group')->where(['group_id'=>$value['business_id']])->data(['current_liveshow_id'=>$live_id])->save();
					break;
			}
		}
		return true;
	}
}