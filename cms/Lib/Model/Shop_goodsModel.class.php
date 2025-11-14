<?php
class Shop_goodsModel extends Model
{
    public $defaultSendTime=0; // getSelectTime方法里的 默认的期望送达时间。用于传递值使用
    protected $send_time_type = [];

	public function _initialize(){
		parent::_initialize();
		$this->send_time_type = [
			L_('分钟'),
			L_('小时'),
			L_('天'),
			L_('周'),
			L_('月'),
		];
	}

    public function saveSpec($goods, $store_id)
    {
        $delete_spec_ids = array();
        $delete_spec_val_ids = array();
        $delete_properties_ids = array();

        $spec_obj = M('Shop_goods_spec'); //规格表
        $spec_value_obj = M('Shop_goods_spec_value');//规格对应的属性值
        $properties_obj = M('Shop_goods_properties');//属性表
        $goods_id = $goods['goods_id'];
        if ($check_data = $this->field(true)->where(array('store_id' => $store_id, 'goods_id' => $goods_id))->find()) {
            //查找已有的属性和规格
            $old_spec = $spec_obj->field(true)->where(array('goods_id' => $goods_id, 'store_id' => $store_id))->select();
            foreach ($old_spec as $os) {
                $delete_spec_ids[] = $os['id'];
            }
            if ($delete_spec_ids) {
                $old_spec_val = $spec_value_obj->field(true)->where(array('sid' => array('in', $delete_spec_ids)))->select();
                foreach ($old_spec_val as $osv) {
                    $delete_spec_val_ids[] = $osv['id'];
                }
            }
            $old_properties = $properties_obj->field(true)->where(array('goods_id' => $goods_id))->select();
            foreach ($old_properties as $op) {
                $delete_properties_ids[] = $op['id'];
            }
            unset($old_spec, $old_spec_val, $old_properties);
        } else {
            return false;
        }

        //配置属性
        $properties = array();
        $spec = array();
        $list = array();

        $data_spec = array('store_id' => $store_id, 'goods_id' => $goods_id);
        foreach ($goods['spec_id'] as $k => $id) {//规格id集合
            $name = $data_spec['name'] = isset($goods['specs'][$k]) ? $goods['specs'][$k] : '';//规格名称
            $spec_val_id = isset($goods['spec_val_id'][$k]) ? $goods['spec_val_id'][$k] : array();//规格属性值的ID集合
            $spec_val = isset($goods['spec_val'][$k]) ? $goods['spec_val'][$k] : array();//规格属性值的名称集合

            $list[$k] = array();
            if ($spec_obj->field(true)->where(array('id' => $id, 'goods_id' => $goods_id))->find()) {
                $spec_obj->where(array('id' => $id))->save($data_spec);
                //多语言
                $lang_model = D('Lang');
                $data_spec['id'] = $id;
                $lang_model->edit_lang_data('Shop_goods_spec',$data_spec);
                unset($data_spec['id']);
            } else {
                $id = $spec_obj->add($data_spec);
                //多语言
                $lang_model = D('Lang');
                $data_spec['id'] = $id;
                $lang_model->add_lang_data('Shop_goods_spec',$data_spec);
                unset($data_spec['id']);
            }
            if ($id) {//规格id
                $delete_spec_ids = array_diff($delete_spec_ids, array($id));

                $data_spec_val = array('sid' => $id);
                foreach ($spec_val_id as $i => $vid) {
                    $data_spec_val['name'] = $spec_val[$i];
                    if ($spec_value_obj->where(array('id' => $vid, 'sid' => $id))->find()) {
                        $spec_value_obj->where(array('id' => $vid))->save($data_spec_val);
                        //多语言
                        $lang_model = D('Lang');
                        $data_spec_val['id'] = $vid;
                        $lang_model->edit_lang_data('Shop_goods_spec_value',$data_spec_val);
                        unset($data_spec_val['id']);
                    } else {
                        $vid = $spec_value_obj->add($data_spec_val);
                        //多语言
                        $lang_model = D('Lang');
                        $data_spec_val['id'] = $vid;
                        $lang_model->add_lang_data('Shop_goods_spec_value',$data_spec_val);
                        unset($data_spec_val['id']);
                    }
                    if ($vid) {
                        $delete_spec_val_ids = array_diff($delete_spec_val_ids, array($vid));
                        // 						$list[$k][$i] = array('spec_id' => $id, 'spec_name' => $name, 'spec_val_id' => $vid, 'spec_val_name' => $spec_val[$i]);
                        $list[$k][$i] = $vid;
                    }
                }
            }
        }
        $spec_value = array();
        $this->format_str($list, 0, array(), $spec_value);
        $properties = array();
        $is_properties = 0;
        foreach ($goods['properties_id'] as $pi => $pid) {//属性id集合
            $is_properties = 1;
            $name = isset($goods['properties'][$pi]) ? $goods['properties'][$pi] : '';//属性名称
            $num = isset($goods['properties_num'][$pi]) ? intval($goods['properties_num'][$pi]) : 1;//属性值可选的数量

            foreach ($goods['properties_val'][$pi] as $key => &$gpv) {
                if (isset($goods['properties_val_status_' . $pi . '_' . $key])) {
                    $gpv .= ':1';
                } else {
                    $gpv .= ':0';
                }
            }
            $val = isset($goods['properties_val'][$pi]) ? implode(',', $goods['properties_val'][$pi]) : '';//属性的属性值

            if ($properties_obj->field(true)->where(array('goods_id' => $goods_id, 'id' => $pid))->find()) {
                $properties_obj->where(array('goods_id' => $goods_id, 'id' => $pid))->save(array('name' => $name, 'val' => $val, 'num' => $num));
            } else {
                $pid = $properties_obj->add(array('goods_id' => $goods_id, 'name' => $name, 'val' => $val, 'num' => $num));
            }
            if ($pid) {
                $delete_properties_ids = array_diff($delete_properties_ids, array($pid));

                $properties[] = array('id' => $pid, 'name' => $name, 'val' => $val);
            }
        }

        $specs = '';
        $pre = '';
        foreach ($spec_value as $k => $v) {
            $old_price = isset($goods['old_prices'][$k]) ? $goods['old_prices'][$k] : 0;
            $cost_price = isset($goods['cost_prices'][$k]) ? $goods['cost_prices'][$k] : 0;
            $number = isset($goods['numbers'][$k]) ? $goods['numbers'][$k] : '';
            $price = isset($goods['prices'][$k]) ? $goods['prices'][$k] : 0;
            $seckill_price = isset($goods['seckill_prices'][$k]) ? floatval($goods['seckill_prices'][$k]) : 0;
            $stock_num = isset($goods['stock_nums'][$k]) ? intval($goods['stock_nums'][$k]) : 0;
            $max_num = isset($goods['max_nums'][$k]) ? intval($goods['max_nums'][$k]) : 0;
            $old_price = $old_price ? $old_price : $price;
            $specs .= $pre . $v . '|' . $old_price . ':' . $price . ':' . $seckill_price . ':' . $stock_num . ':' . $cost_price  .  ':' . $max_num  . '|';
            if ($properties) {
                // 				$specs .= '|';
                $ppre = '';
                foreach ($properties as $ti => $ps) {
                    $num = isset($goods['num' . $ti][$k]) && $goods['num' . $ti][$k] ? intval($goods['num' . $ti][$k]) : 1;
                    $specs .= $ppre . $ps['id'] . '=' . $num;
                    $ppre = ':';
                }
            }
            $number && $specs .= '|' . $number;
            $pre = '#';
        }

        //2016-11-08新增系统的商品分类属性值与商品直接的关系▽
        D('Goods_properties_relation')->where(array('gid' => $goods_id))->delete();
        if (isset($goods['goodsproperties'])) {
            foreach ($goods['goodsproperties'] as $pid) {
                D('Goods_properties_relation')->add(array('gid' => $goods_id, 'pid' => $pid));
            }
        }
        //2016-11-08新增系统的商品分类属性值与商品直接的关系△

        //74:77|1:1:1:1:0:1|6=1#74:78|1:1:1:1:0:1|6=1#74:79|1:1:1:1:0:1|6=1#75:77|1:1:1:1:0:1|6=1#75:78|1:1:1:1:0:1|6=1#75:79|1:1:1:1:0:1|6=1#76:77|1:1:1:1:0:1|6=1#76:78|1:1:1:1:0:1|6=1#76:79|1:1:1:1:0:1|6=1
        //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price:max_num|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号 # 规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#...#规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号
        if ($this->where(array('goods_id' => $goods_id))->save(array('spec_value' => $specs, 'is_properties' => $is_properties, 'last_time' => time()))) {
            $delete_spec_ids && $spec_obj->where(array('id' => array('in', $delete_spec_ids)))->delete();
            $delete_spec_val_ids && $spec_value_obj->where(array('id' => array('in', $delete_spec_val_ids)))->delete();
            $delete_properties_ids && $properties_obj->where(array('id' => array('in', $delete_properties_ids)))->delete();
            //配置属性
            return $goods_id;
        } else {
            return false;
        }
    }

	/**
	 * @根据店铺分类属性设置批量修改该分类下的商品属性
	 * @param $store_id
	 * @param $sort_id
	 * @param $config
	 * @return bool
	 */
	public function  save_sort_config($store_id,$sort_id,$config)
	{
		if(!$store_id||!$sort_id) return false;

		$where = array();
		$where['sort_id']  = $sort_id;
		$where['store_id'] = $store_id;
		$save_data = array();
		$save_data['freight_template'] = intval($config['freight_template']);
		$save_data['freight_type'] = intval($config['freight_type']);
		$save_data['freight_value'] = floatval($config['freight_value']);
		$save_data['cat_fid'] = intval($config['cat_fid']);
		$save_data['cat_id'] = intval($config['cat_id']);
		if(isset($config['goods_type'])){
			$save_data['goods_type']=$config['goods_type'];
		}
		$this->where($where)->save($save_data);

		$good_array = $this->where($where)->select();
		if($good_array&&isset($config['goodsproperties'])){
			$goods_ids = array();
			foreach($good_array as $v){
				$goods_ids[] = $v['goods_id'];
			}
			D('Goods_properties_relation')->where('gid','in', $goods_ids)->delete();

			foreach($goods_ids as $good_id_val){
				foreach ($config['goodsproperties'] as $pid) {
					D('Goods_properties_relation')->add(array('gid' => $good_id_val, 'pid' => $pid));
				}
			}

		}
		return true;
	}

	public function save_post_form($goods, $store_id)
	{
		import('@.ORG.fc.Pinyin');
		$goods_id = isset($goods['goods_id']) ? intval($goods['goods_id']) : 0;

		$data = array('name' => $goods['name']);
		if (isset($goods['open_employee_card'])) {
			$data['open_employee_card'] = $goods['open_employee_card'];
		}
		if (isset($goods['employee_mer_id'])) {
			$data['employee_mer_id'] = $goods['employee_mer_id'];
		}
		if (isset($goods['employee_lables'])) {
			$data['employee_lables'] = $goods['employee_lables'];
		}
		//商品描述
		if(isset($goods['describe'])){
			$data['describe']=$goods['describe'];
		}
		//商品视频
		if(isset($goods['video_url'])){
			$data['video_url']=$goods['video_url'];
        }
        //商品外卖视频
		if(isset($goods['shop_video_url'])){
			$data['shop_video_url']=$goods['shop_video_url'];
		}
        //商品外卖视频封面图
		if(isset($goods['shop_video_preview'])){
			$data['shop_video_preview']=$goods['shop_video_preview'];
		}
		//首页排序
		if(isset($goods['index_sort'])){
			$data['index_sort']=$goods['index_sort'];
		}
		//商品归属
		if(isset($goods['goods_type'])){
			$data['goods_type']=$goods['goods_type'];
		}
		//商品标签
		if(isset($goods['label'])){
			$data['label_str']=trim(implode(',',$goods['label']),',');
		}
		//辣度
		if(isset($goods['spicy'])){
			$data['spicy']=$goods['spicy'];
		}
		// 口味
		if($goods['flavor']){
			$data['flavor'] = implode('&^&',array_filter($goods['flavor']));
		}
		// 原料
		if($goods['material']){
			$data['material'] = implode('&^&',array_filter($goods['material']));
		}
		// 特殊食材
		if($goods['special_food_materials']){
			$data['special_food_materials'] = implode('&^&',array_filter($goods['special_food_materials']));
		}
		// 做法
		if($goods['cooking_methods']){
			$data['cooking_methods'] = implode('&^&',array_filter($goods['cooking_methods']));
		}

		//商品现价类型
		if(isset($goods['price_type'])){
			$data['price_type']=intval($goods['price_type']);
		}

		//商品现金
		if(isset($goods['account_money'])){
			$data['account_money'] = $goods['account_money'];
		}

		//商品积分（米粒）
		if(isset($goods['account_score'])){
			$data['account_score'] = $goods['account_score'];
		}

		if(isset($goods['shop_virtual_sales'])){
			$data['shop_virtual_sales'] = $goods['shop_virtual_sales'];
		}

		if(isset($goods['audit_status'])){
			$data['audit_status'] = $goods['audit_status'];
		}

		if(isset($goods['audit_content'])){
			$data['audit_content'] = $goods['audit_content'];
		}
		
		if(isset($goods['mer_score_rate'])){
			$data['mer_score_rate'] = intval($goods['mer_score_rate']);
		}

        $data['use_area'] = $goods['use_area']*1? $goods['use_area']*1 : 0;
		$data['spicy'] = $data['spicy'] ? $data['spicy'] : 0;
		$data['flavor'] = $data['flavor'] ? $data['flavor'] : '';
		$data['material'] = $data['material'] ? $data['material'] : '';
		$data['special_food_materials'] = $data['special_food_materials'] ? $data['special_food_materials'] : '';
		$data['cooking_methods'] = $data['cooking_methods'] ? $data['cooking_methods'] : '';
		$data['unit'] = $goods['unit'];
		$data['old_price'] = empty($goods['old_price']) ? $goods['price'] : $goods['old_price'];
		$data['cost_price'] = empty($goods['cost_price']) ? 0 : $goods['cost_price'];
		$data['price'] = $goods['price'];
		// $data['price_range_low'] = $goods['price_range_low'];
		// $data['price_range_height'] = $goods['price_range_height'];
		$data['extra_pay_price'] = floatval($goods['extra_pay_price']);
		$data['seckill_price'] = $goods['seckill_price']?$goods['seckill_price']:'';
		$data['seckill_stock'] = $goods['seckill_stock']?$goods['seckill_stock']:'';
		$data['seckill_type'] = $goods['seckill_type']?$goods['seckill_type']:'';
		$data['seckill_open_time'] = $goods['seckill_open_time']?$goods['seckill_open_time']:'';
		$data['seckill_close_time'] = $goods['seckill_close_time']?$goods['seckill_close_time']:'';
		$data['show_start_time'] = isset($goods['show_start_time']) ? $goods['show_start_time'] : '00:00:00';
		$data['show_end_time'] = isset($goods['show_end_time']) ? $goods['show_end_time'] : '00:00:00';
		$data['packing_charge'] = $goods['packing_charge']?$goods['packing_charge']:'';
		$data['stock_num'] = $goods['stock_num'];
		$data['original_stock'] = $goods['original_stock']?$goods['original_stock']:'';
		$data['sort'] = $goods['sort']?$goods['sort']:0;
		if(isset($goods['status'])){
			$data['status'] = $goods['status']?$goods['status']:0;
		}
		$data['print_id'] = $goods['print_id']?$goods['print_id']:'';
		$data['sort_id'] = $goods['sort_id']?$goods['sort_id']:'';
		$data['des'] = $goods['des'];
		$data['image'] = strval($goods['pic']);
		$data['number'] = $goods['number']?$goods['number']:'';
		$data['store_id'] = $store_id;
		$data['score_max'] = isset($goods['score_max']) ? $goods['score_max'] : 0;
		$data['score_percent'] = isset($goods['score_percent']) ? $goods['score_percent'] : 0;
        $data['score_percent_type'] = isset($goods['score_percent_type']) ? intval($goods['score_percent_type']) : 0; //是否跳过

		$data['freight_template'] = intval($goods['freight_template']);
		$data['freight_type'] = intval($goods['freight_type']);
		$data['freight_value'] = floatval($goods['freight_value']);
		$data['max_num'] = intval($goods['max_num']);
		$data['min_num'] = intval($goods['min_num']);
		$data['limit_type'] = isset($goods['limit_type']) ? intval($goods['limit_type']) : 0;
		$data['is_discount'] = isset($goods['is_discount']) ? intval($goods['is_discount']) : 1;
		$data['is_use_coupon'] = isset($goods['is_use_coupon']) ? intval($goods['is_use_coupon']) : 1;
		$data['take_a_percentage'] = isset($goods['take_a_percentage']) ? intval($goods['take_a_percentage']) : 0;

        // 名称首字母
        $data['name_first_word'] = get_first_charter($data['name']);

        //首拼
        
        $data['goods_name_first_spell'] = '';
        $goodsStrArr = preg_split("//u", $data['name'], -1,PREG_SPLIT_NO_EMPTY);
        foreach($goodsStrArr as $key => $val){
            $data['goods_name_first_spell'] .= Pinyin::to_first($val) ?: $val;
        }
        
		if(isset($goods['spread_rate']) ){
			$data['spread_rate'] = $goods['spread_rate'];
		}
		if(isset($goods['sub_spread_rate']) ){
			$data['sub_spread_rate'] = $goods['sub_spread_rate'];
		}
		if(isset($goods['third_spread_rate']) ){
			$data['third_spread_rate'] = $goods['third_spread_rate'];
		}

		if(isset($goods['sell_type'])){
            $data['sell_type'] = isset($goods['sell_type']) ? $goods['sell_type'] : 1;
        }
		$data['unit_price'] = isset($goods['unit_price']) ? $goods['unit_price'] : 0;
		$data['weight'] = isset($goods['goods_weight']) ? $goods['goods_weight'] : 0;
		$data['weight_unit'] = isset($goods['weight_unit']) ? $goods['weight_unit'] : 1;
		$data['tare_weight'] = isset($goods['tare_weight']) ? $goods['tare_weight'] : 1;
		$data['stock_weight'] = isset($goods['stock_weight']) ? $goods['stock_weight'] : 0;
		$data['original_weight'] = isset($goods['original_weight']) ? $goods['original_weight'] : 0;
		$data['distributor_percent'] = isset($goods['distributor_percent']) ? $goods['distributor_percent'] : 0;
		// var_dump($data['distributor_percent']);die;;

		$data['last_time'] = time();
		//$data['miaomaiyun_g_m_id'] = $goods['miaomaiyun_g_m_id'] ?? 0;

		//2016-11-08新增系统的商品分类▽
		$data['cat_fid'] = intval($goods['cat_fid']);
		$data['cat_id'] = intval($goods['cat_id']);
		//2016-11-08新增系统的商品分类△

		//2019-08-13新增商品券商品的属性▽
		$data['tickets_bind_goods'] = isset($goods['tickets_bind_goods']) ? $goods['tickets_bind_goods'] : '';
		$data['ticket_deliver_type'] = isset($goods['ticket_deliver_type']) ? $goods['ticket_deliver_type'] : 0;
		$data['ticket_expire_date'] = isset($goods['ticket_expire_date']) ? $goods['ticket_expire_date'] : 0;
		$data['ticket_expire_type'] = isset($goods['ticket_expire_type']) ? $goods['ticket_expire_type'] : 0;
		$data['ticket_times'] = isset($goods['ticket_times']) ? $goods['ticket_times'] : 0;
		$data['ticket_space'] = isset($goods['ticket_space']) ? $goods['ticket_space'] : 0;
		$data['ticket_num'] = isset($goods['ticket_num']) ? $goods['ticket_num'] : 0;
		$data['ticket_start_time'] = isset($goods['ticket_start_time']) ? strtotime($goods['ticket_start_time']) : 0;
		$data['ticket_end_time'] = isset($goods['ticket_end_time']) ? strtotime($goods['ticket_end_time']) : 0;
		//2019-08-13新增商品券商品的属性△

		//2019-11-08新增商城商品虚拟销量字段
		$data['mall_virtual_sales'] = isset($goods['mall_virtual_sales']) ? $goods['mall_virtual_sales'] : 0;
		$data['supply_price'] = $goods['supply_price'] ?: 0;
		$data['goods_code'] = $goods['goods_code'] ?: '';
        $data['single_no_delivery'] = $goods['single_no_delivery'] ?? 0;
		$delete_spec_ids = array();
		$delete_spec_val_ids = array();
		$delete_properties_ids = array();

		$spec_obj = M('Shop_goods_spec'); //规格表
		$spec_value_obj = M('Shop_goods_spec_value');//规格对应的属性值
		$properties_obj = M('Shop_goods_properties');//属性表
		if ($check_data = $this->field(true)->where(array('store_id' => $store_id, 'goods_id' => $goods_id))->find()) {
			if ($this->where(array('store_id' => $store_id, 'goods_id' => $goods_id))->save($data)) {
				//查找已有的属性和规格
				$old_spec = $spec_obj->field(true)->where(array('goods_id' => $goods_id, 'store_id' => $store_id))->select();
				foreach ($old_spec as $os) {
					$delete_spec_ids[] = $os['id'];
				}
				if ($delete_spec_ids) {
					$old_spec_val = $spec_value_obj->field(true)->where(array('sid' => array('in', $delete_spec_ids)))->select();
					foreach ($old_spec_val as $osv) {
						$delete_spec_val_ids[] = $osv['id'];
					}
				}
				$old_properties = $properties_obj->field(true)->where(array('goods_id' => $goods_id))->select();
				foreach ($old_properties as $op) {
					$delete_properties_ids[] = $op['id'];
				}
				unset($old_spec, $old_spec_val, $old_properties);
			} else {
				return false;
			}
		} else {
		    //记录商品添加时间
            $data['use_area'] = $goods['use_area']*1? $goods['use_area']*1 : 0;
            $data['add_time'] = time();
			$data['supply_price'] = $goods['supply_price'] ?: 0;
			$data['goods_code'] = $goods['goods_code'] ?: '';
			$goods_id = $this->add($data);
			if (empty($goods_id)) return false;
		}
		// 同步修改餐饮状态
		if ($goods['is_sync']==1) {
			M('Foodshop_goods_new')->where(array('goods_id' => $goods_id))->save(array('status' => $data['status'],  'last_time' => $data['last_time']));
		}

		//配置属性
		$properties = array();
		$spec = array();
		$list = array();

		$data_spec = array('store_id' => $store_id, 'goods_id' => $goods_id);

		foreach ($goods['spec_id'] as $k => $id) {//规格id集合
			$name = $data_spec['name'] = isset($goods['specs'][$k]) ? $goods['specs'][$k] : '';//规格名称
			$spec_val_id = isset($goods['spec_val_id'][$k]) ? $goods['spec_val_id'][$k] : array();//规格属性值的ID集合
			$spec_val = isset($goods['spec_val'][$k]) ? $goods['spec_val'][$k] : array();//规格属性值的名称集合

			$list[$k] = array();
			if ($spec_obj->field(true)->where(array('id' => $id, 'goods_id' => $goods_id))->find()) {
				$spec_obj->where(array('id' => $id))->save($data_spec);
				//多语言
                $lang_model = D('Lang');
                $data_spec['id'] = $id;
                $lang_model->edit_lang_data('Shop_goods_spec',$data_spec);
                unset($data_spec['id']);
			} else {
				$id = $spec_obj->add($data_spec);
				//多语言
                $lang_model = D('Lang');
                $data_spec['id'] = $id;
                $lang_model->add_lang_data('Shop_goods_spec',$data_spec);
                unset($data_spec['id']);
			}
			if ($id) {//规格id
				$delete_spec_ids = array_diff($delete_spec_ids, array($id));

				$data_spec_val = array('sid' => $id);
				foreach ($spec_val_id as $i => $vid) {
					$data_spec_val['name'] = $spec_val[$i];
					if ($spec_value_obj->where(array('id' => $vid, 'sid' => $id))->find()) {
						$spec_value_obj->where(array('id' => $vid))->save($data_spec_val);
						//多语言
                        $lang_model = D('Lang');
                        $data_spec_val['id'] = $vid;
                        $lang_model->edit_lang_data('Shop_goods_spec_value',$data_spec_val);
                        unset($data_spec_val['id']);
					} else {
						$vid = $spec_value_obj->add($data_spec_val);
						//多语言
                        $lang_model = D('Lang');
                        $data_spec_val['id'] = $vid;
                        $lang_model->add_lang_data('Shop_goods_spec_value',$data_spec_val);
                        unset($data_spec_val['id']);
					}
					if ($vid) {
						$delete_spec_val_ids = array_diff($delete_spec_val_ids, array($vid));
// 						$list[$k][$i] = array('spec_id' => $id, 'spec_name' => $name, 'spec_val_id' => $vid, 'spec_val_name' => $spec_val[$i]);
						$list[$k][$i] = $vid;
					}
				}
			}
		}
		$spec_value = array();
		$this->format_str($list, 0, array(), $spec_value);

		$properties = array();
		$is_properties = 0;
		foreach ($goods['properties_id'] as $pi => $pid) {//属性id集合
			$is_properties = 1;
			$name = isset($goods['properties'][$pi]) ? $goods['properties'][$pi] : '';//属性名称
			$num = isset($goods['properties_num'][$pi]) ? intval($goods['properties_num'][$pi]) : 1;//属性值可选的数量

			foreach ($goods['properties_val'][$pi] as $key => &$gpv) {
			    if (isset($goods['properties_val_status_' . $pi . '_' . $key])) {
			        $gpv .= ':1';
			    } else {
			        $gpv .= ':0';
			    }
			}
			$val = isset($goods['properties_val'][$pi]) ? implode(',', $goods['properties_val'][$pi]) : '';//属性的属性值

			if ($properties_obj->field(true)->where(array('goods_id' => $goods_id, 'id' => $pid))->find()) {
				$properties_obj->where(array('goods_id' => $goods_id, 'id' => $pid))->save(array('name' => $name, 'val' => $val, 'num' => $num));
				$lang_model = D('Lang');
				$_edit = [
					'name' => $name,
					'val' => $val,
					'id' => $pid
				];
                $lang_model->edit_lang_data('Shop_goods_properties',$_edit);
			} else {
				$_insert = array('goods_id' => $goods_id, 'name' => $name, 'val' => $val, 'num' => $num);
				$pid = $properties_obj->add($_insert);
				//多语言
                $lang_model = D('Lang');
                $_insert['id'] = $pid;
                $lang_model->add_lang_data('Shop_goods_properties',$_insert);
			}
			if ($pid) {
				$delete_properties_ids = array_diff($delete_properties_ids, array($pid));

				$properties[] = array('id' => $pid, 'name' => $name, 'val' => $val);
			}
		}
		$specs = '';
		$pre = '';
		foreach ($spec_value as $k => $v) {
		    $weight = isset($goods['weight'][$k]) ? $goods['weight'][$k] : 0;
			$old_price = isset($goods['old_prices'][$k]) ? $goods['old_prices'][$k] : 0;
			$cost_price = isset($goods['cost_prices'][$k]) ? $goods['cost_prices'][$k] : 0;
			$number = isset($goods['numbers'][$k]) ? $goods['numbers'][$k] : '';
			$reg_img = isset($goods['reg_imgs'][$k]) ? $goods['reg_imgs'][$k] : '';
			$price = isset($goods['prices'][$k]) ? $goods['prices'][$k] : 0;

			$account_money = isset($goods['account_moneys'][$k]) ? $goods['account_moneys'][$k] : 0;
			$account_score = isset($goods['account_scores'][$k]) ? $goods['account_scores'][$k] : 0;

			$seckill_price = isset($goods['seckill_prices'][$k]) ? floatval($goods['seckill_prices'][$k]) : 0;
			$stock_num = isset($goods['stock_nums'][$k]) ? intval($goods['stock_nums'][$k]) : 0;
			$max_num = isset($goods['max_nums'][$k]) ? intval($goods['max_nums'][$k]) : 0;
			$old_price = $old_price ? $old_price : $price;
			$specs .= $pre . $v . '|' . $old_price . ':' . $price . ':' . $seckill_price . ':' . $stock_num . ':' . $cost_price  .  ':' . $max_num  . ':' . $weight. ':'.$account_money.  ':'.$account_score.'|';
			if ($properties) {
// 				$specs .= '|';
				$ppre = '';
				foreach ($properties as $ti => $ps) {
					$num = isset($goods['num' . $ti][$k]) && $goods['num' . $ti][$k] ? intval($goods['num' . $ti][$k]) : 1;
					$specs .= $ppre . $ps['id'] . '=' . $num;
					$ppre = ':';
				}
			}
			$specs .= '|' . $number;
			$specs .= '||' . urlencode($reg_img);
			$pre = '#';
		}

		//2016-11-08新增系统的商品分类属性值与商品直接的关系▽
		D('Goods_properties_relation')->where(array('gid' => $goods_id))->delete();
		if (isset($goods['goodsproperties'])) {
			foreach ($goods['goodsproperties'] as $pid) {
				D('Goods_properties_relation')->add(array('gid' => $goods_id, 'pid' => $pid));
			}
		}
		//2016-11-08新增系统的商品分类属性值与商品直接的关系△
		//规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price:max_num|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号 # 规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#...#规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号
		if ($this->where(array('goods_id' => $goods_id))->save(array('spec_value' => $specs, 'is_properties' => $is_properties, 'last_time' => $data['last_time'] + 1))) {
			$delete_spec_ids && $spec_obj->where(array('id' => array('in', $delete_spec_ids)))->delete();
			$delete_spec_val_ids && $spec_value_obj->where(array('id' => array('in', $delete_spec_val_ids)))->delete();
			$delete_properties_ids && $properties_obj->where(array('id' => array('in', $delete_properties_ids)))->delete();
			//更新继承商品库的新版商城商品规格库存数据
			$mallGoods = M('Mall_goods')->where(array('store_id' => $store_id, 'stock_type' => 2,'common_goods_id' => $goods_id,'is_del' => 0))->select();
			if (!empty($mallGoods)) {
				$goodsDetail = invoke_v20_service('\app\shop\model\service\goods\ShopGoodsService/getGoodsDetail', [
					'param' => [
						'goods_id' => $goods_id
					]
				]);
				$goodsImage = explode(';', $goodsDetail['data']['retval']['image']);
				foreach ($mallGoods as $mv) {
					$ret=invoke_v20_service('\app\mall\model\service\MallGoodsService/addOrEditGoods', [
						'param' => [
							'from' => 1,
							'goods_id' => $mv['goods_id'],
							'store_id' => $mv['store_id'],
							'name' => $goodsDetail['data']['retval']['name'],
							'images' => $goodsImage,
							'image' => explode('/upload/', $goodsImage[0]) ? '/upload/' . explode('/upload/', $goodsImage[0])[1] : $goodsImage[0],
							'goods_desc' => $mv['goods_desc'],
							'spec_desc' => $mv['spec_desc'],
							'pack_desc' => $mv['pack_desc'],
							'stock_num' => $data['stock_num'],
							'fright_id' => 0,
							'price' => $data['price'],
							'list' => $goodsDetail['data']['retval']['list'],
							'spec_list' => $goodsDetail['data']['retval']['spec_list'],
							'notes' => $goodsDetail['data']['retval']['properties_list']
						]
					]);
				}
			}

			//配置属性
			return $goods_id;
		} else {
			return false;
		}
	}

    //执行sql语句
	public function getArraid($p_sql){
	    return $this->query($p_sql);
    }
	public function save_post_form_foodshop($goods, $store_id)
	{
		$goods_id = isset($goods['goods_id']) ? intval($goods['goods_id']) : 0;


		$data['old_price'] = empty($goods['old_price']) ? $goods['price'] : $goods['old_price'];
		$data['price'] = $goods['price'];
		$data['spec_stock'] = $goods['spec_stock'];
		$data['spec_type'] = $goods['spec_type'];
		$data['spec_open_time'] = $goods['spec_open_time'] ? strtotime($goods['spec_open_time']) : '';
		$data['spec_close_time'] = $goods['spec_close_time'] ? strtotime($goods['spec_close_time']) : '';
		$data['spec_open_datetime'] =$goods['spec_open_datetime'] ? strtotime($goods['spec_open_datetime']) : '';
		$data['spec_close_datetime'] = $goods['spec_close_datetime'] ? strtotime($goods['spec_close_datetime']) : '';

		$data['status'] = $goods['status'];
		$data['store_id'] = $store_id;
		$data['goods_id'] = $goods['goods_id'];

		if(isset($goods['sell_type'])){
            $data['sell_type'] = isset($goods['sell_type']) ? $goods['sell_type'] : 1;
        }

		$data['last_time'] = time();
		$delete_spec_ids = array();
		$delete_spec_val_ids = array();
		$delete_properties_ids = array();

		$spec_obj = M('Shop_goods_spec'); //规格表
		$spec_value_obj = M('Shop_goods_spec_value');//规格对应的属性值
		$properties_obj = M('Shop_goods_properties');//属性表

		if ($check_data = M('Foodshop_goods_new')->field(true)->where(array('store_id' => $store_id, 'goods_id' => $goods_id))->find()) {
			$update = M('Foodshop_goods_new')->save($data);
		} else {
			$goods_id = M('Foodshop_goods_new')->add($data);
			if (empty($goods_id)) return false;
		}

		$spec_value = array();
		foreach ($goods['indexs'] as $key => $value) {
			$spec_value[$value]['price'] = $goods['prices'][$key];
			$spec_value[$value]['stock_num'] = $goods['stock_nums'][$key];
		}
		//配置属性
		$shop_goods = $this->field(true)->where(array('store_id' => $store_id, 'goods_id' => $goods_id))->find();

		if ($shop_goods['spec_value']) {
			$old_spec_value = $this->format_spec_value($shop_goods['spec_value'],$goods_id);
			$specs = '';
			$pre = '';
			foreach ($old_spec_value['list'] as $k => $v) {
				$specs .= $pre . $k . '|' . $spec_value[$k]['price']. ':' . $spec_value[$k]['stock_num']. '|#';
			}

			if ($this->where(array('goods_id' => $goods_id))->save(array('foodshop_spec_value' => $specs, 'last_time' => time()))) {
				//配置属性
				return $goods_id;
			} else {
				return false;
			}
		}else{
			return $goods_id;
		}

	}
	public function format_str($a, $i, $str, &$return)
	{
		if ($i == 0) {
			$ii = $i + 1;
			foreach ($a[$i] as $val) {
				$t = $str ? $str . ':' : '';
				if ($ii == count($a)) {
					$return[] = $t . $val;
				} else {
					$this->format_str($a, $ii, $t . $val, $return);
				}
			}
		} else if ($i == count($a) - 1) {
			foreach ($a[$i] as $val) {
				$t = $str ? $str . ':' : '';
				$return[] = $t . $val;
			}
		} else {
			$ii = $i + 1;
			foreach ($a[$i] as $val) {
				$t = $str ? $str . ':' : '';
				$this->format_str($a, $ii, $t . $val, $return);
			}
		}
	}

	private function format_html($a, $i, $str, &$return)
	{
		if ($i == 0) {
			$ii = $i + 1;
			foreach ($a[$i] as $val) {
				$ta = $str;
				array_push($ta, $val);
				if ($ii == count($a)) {
					$return[] = $ta;
				} else {
					$this->format_html($a, $ii, $ta, $return);
				}
			}
		} else if ($i == count($a) - 1) {
			foreach ($a[$i] as $val) {
				$ta = $str;
				array_push($ta, $val);
				$return[] = $ta;
			}
		} else {
			$ii = $i + 1;
			foreach ($a[$i] as $val) {
				$ta = $str;
				array_push($ta, $val);
				$this->format_html($a, $ii, $ta, $return);
			}
		}
	}

	//@param int $is_group 拼团活动（1-团长；1-参团,3-获取规格的团长价和参团价）
	public function format_spec_value($str, $goods_id, $is_prorerties = 1, $minNum = 0,$group_info=null,$foodshop_str = '',$is_check_limited = true)
	{
		$goods_image_class = new goods_image();
		if ($is_prorerties || $str) {
			$properties_obj = M(_view('Shop_goods_properties'));
			$goods_properties_temp = $properties_obj->field(true)->where(array('goods_id' => $goods_id))->order('id asc')->select();
			$goods_properties_list = array();
			$goods_properties_status_list = array();
			foreach ($goods_properties_temp as $goods_p) {
				$vals = explode(',', $goods_p['val']);
				$value = array();
				$valueStatus = array();
				foreach ($vals as $val) {
				    $valArr = explode(':', $val);
				    $decodeVal = html_entity_decode($valArr[0],ENT_QUOTES);
				    if (!(isset($valArr[1]) && $valArr[1] == 0)) {
				        $value[] = $decodeVal;
				        $valueStatus[] = array($decodeVal, 1);
				    } else {
				        $valueStatus[] = array($decodeVal, 0);
				    }
				}
				$goods_p['val'] = $value;
				$goods_p['val_status'] = $valueStatus;
				$goods_properties_status_list[$goods_p['id']] = $goods_p;
				if ($value) {
				    $goods_properties_list[$goods_p['id']] = $goods_p;
				}
			}
			unset($goods_properties_temp);
		}
		if ($str) {
			$spec_obj = M(_view('Shop_goods_spec')); //规格表
			$spec_value_obj = M(_view('Shop_goods_spec_value'));//规格对应的属性值
			$goods_spec_temp = $spec_obj->field(true)->where(array('goods_id' => $goods_id))->order('id ASC')->select();
			$goods_spec_list = array();
			$specids = array();
			foreach ($goods_spec_temp as $goods_t) {
				$specids[] = $goods_t['id'];
				$goods_spec_list[$goods_t['id']] = $goods_t;
			}
			unset($goods_spec_temp);
			$spec_valuse_list = array();
			if ($specids) {
				$spec_valuse_temp = $spec_value_obj->field(true)->where(array('sid' => array('in', $specids)))->order('id ASC')->select();
				foreach ($spec_valuse_temp as $v_temp) {
					$spec_valuse_list[$v_temp['id']] = $v_temp;
					$goods_spec_list[$v_temp['sid']]['list'][$v_temp['id']] = $v_temp;
				}
				unset($spec_valuse_temp, $specids);
			}

			$return = array();
			$json = array();
			$spec_array = explode('#', $str);
			// 餐饮商品规格
			$foodshop_str && $foodshop_spec_array = explode('#', $foodshop_str);
			$p_ids = array();
			$is_sort = true;
			$new_goods_spec_list = array();

            if ($is_check_limited) {
                $seckill_record = D('Time_limited_discount_goods')->get_in_progress_record($goods_id);
            } else {
                $seckill_record = [];
            }
            $spec_list = isset($seckill_record['spec_list']) ? $seckill_record['spec_list'] : [];
            $limit_id = $seckill_record ? $seckill_record['id'] : 0;
            $isOpenMiaomaiyunErp = C('config.miaomaiyun_erp_open');
            foreach ($spec_array as $key=>$row) {
				$row_array = explode('|', $row);
				$spec_ids = explode(':', $row_array[0]);
				// 餐饮商品规格
				$foodshop_str && $foodshop_row_array = explode('|', $foodshop_spec_array[$key]);
				$t_index = '';
				$t_pre = '';
				$spec_data = array();
				foreach ($spec_ids as $id) {
					$t_index .= $t_pre . 'id_' . $id;
					$t_pre = '_';
					$spec_data[] = array(
						'spec_val_id' => $id,
						'spec_val_name' => isset($spec_valuse_list[$id]['name']) ? html_entity_decode($spec_valuse_list[$id]['name'], ENT_QUOTES) : '',
						'spec_val_sid' => isset($spec_valuse_list[$id]['sid']) ? $spec_valuse_list[$id]['sid'] : '',
						'miaomaiyun_spec_attr_id' => $isOpenMiaomaiyunErp ? $goods_spec_list[$spec_valuse_list[$id]['sid']]['miaomaiyun_spec_attr_id']: 0,
						'miaomaiyun_spec_attr_val_id' => $isOpenMiaomaiyunErp ?$spec_valuse_list[$id]['miaomaiyun_spec_attr_val_id']: 0
					);
					if ($is_sort && isset($spec_valuse_list[$id]['sid']) && isset($goods_spec_list[$spec_valuse_list[$id]['sid']])) {
						$new_goods_spec_list[] = $goods_spec_list[$spec_valuse_list[$id]['sid']];
					}
				}
				$is_sort = false;
				$index = implode('_', $spec_ids);

				$return[$index]['index'] = $t_index;
				$return[$index]['spec'] = $spec_data;


                $prices = explode(':', $row_array[1]);
                //重置限时优惠价格和库存
                if (isset($spec_list[$index])) {
                    $prices[2] = $spec_list[$index]['limit_price'];
                    $prices[3] = $spec_list[$index]['stock'];
                    $limit_id = $spec_list[$index]['id'];
                }else{
                    $limit_id = 0;
                }
                $return[$index]['limit_id'] = $limit_id;

				// 餐饮商品规格
				$foodshop_str && $foodshop_prices = explode(':', $foodshop_row_array[1]);
				$return[$index]['old_price'] = floatval($prices[0]);
				$return[$index]['price'] = isset($foodshop_prices[0]) ? floatval($foodshop_prices[0]) : floatval($prices[1]);
				$return[$index]['seckill_price'] = floatval($prices[2]);
				$return[$index]['seckill_discount'] = $return[$index]['price'] ? round($return[$index]['seckill_price']/$return[$index]['price']*10,1) : 10;
				$return[$index]['stock_num'] = isset($foodshop_prices[1]) ? $foodshop_prices[1] : $prices[3];
                $return[$index]['cost_price'] = isset($prices[4]) ? $prices[4] : 0;
                /*-------预售修改规格价格信息----------*/
                $time = time();

                $where['a.is_del'] = 0;
                $where['a.end_time'] = ['EGT',$time];
                $where['a.goods_id'] = $goods_id;
                $book_len=count($spec_ids);
                if($book_len>1){
                    $books=implode('_',$spec_ids);
                }else{
                    $books=$spec_ids[0];
                }
                $where['b.spec_index'] = $books;
                $field='b.bargin_price,b.discount_price,c.price';
                $join=' inner join pigcms_shop_goods as c ON a.goods_id = c.goods_id  inner join  pigcms_mall_book_goods_spec as b ON a.id = b.book_id';
                $arr=M('Mall_book_goods')->alias('a')->field($field)->join($join)->where($where)->select();
                if(count($arr)>0){
                    $return[$index]['bargin_price'] = $arr[0]['bargin_price'];
                    $return[$index]['discount_price'] = $arr[0]['discount_price'];
                }
                /*------------------------------------*/

                if ($minNum > 1) {
				    $return[$index]['max_num'] = 0;
				} else {
				    $return[$index]['max_num'] = isset($prices[5]) ? $prices[5] : 0;
				}
				if($seckill_record){
                    $return[$index]['limit_num'] = $seckill_record['limit_num'];
                    $return[$index]['max_num'] = $seckill_record['limit_num'];
                }
                $return[$index]['weight'] = isset($prices[6]) ? $prices[6] : 0;
                $return[$index]['account_money'] = isset($prices[7]) ? $prices[7] : 0;
                $return[$index]['account_score'] = isset($prices[8]) ? $prices[8] : 0;

                $return[$index]['vip_wholesale_price'] = isset($prices[7]) ? $prices[7] : 0;
                $return[$index]['goods_weight'] = isset($prices[8]) ? $prices[8] : 0;
                $return[$index]['goods_volume'] = isset($prices[9]) ? $prices[9] : 0;

				$return[$index]['number'] = isset($row_array[3]) ? $row_array[3] : '';
				$reg_title=isset($row_array[5]) ? urldecode($row_array[5]) : '';
				$return[$index]['reg_title'] =$reg_title;
				$return[$index]['reg_img'] = $goods_image_class->get_image_by_path($reg_title,0);

				if (isset($row_array[2]) && $row_array[2] && strstr($row_array[2], '=')) {
					$p_data = array();
					$tdata = array();
					$properties = explode(':', $row_array[2]);
					foreach ($properties as $k => $pro) {
						$pro_array = explode('=', $pro);
						$decodePropertiesVal = html_entity_decode(isset($goods_properties_status_list[$pro_array[0]]['name']) ? $goods_properties_status_list[$pro_array[0]]['name'] : '', ENT_QUOTES);
						$p_data[] = array('id' => intval($pro_array[0]), 'num' => intval($pro_array[1]), 'name' => $decodePropertiesVal);
						$tdata['num' . $k . '[]'] = $pro_array[1];
					}
					$return[$index]['properties'] = $p_data;
					$json[$t_index] = $tdata;
				}
				if (empty($return[$index]['number']) && isset($row_array[2]) && $row_array[2] && !strstr($row_array[2], '=')) {
					$return[$index]['number'] = $row_array[2];
				}
				$json[$t_index]['old_prices[]'] = $prices[0];
				$json[$t_index]['prices[]'] = isset($foodshop_prices[0]) ? floatval($foodshop_prices[0]) : floatval($prices[1]);
				$json[$t_index]['seckill_prices[]'] = $prices[2];
				$json[$t_index]['account_moneys[]'] = $prices[7];
				$json[$t_index]['account_scores[]'] = $prices[8];
				$json[$t_index]['stock_nums[]'] = isset($foodshop_prices[1]) ? $foodshop_prices[1] : $prices[3];
				$json[$t_index]['cost_prices[]'] = isset($prices[4]) ? floatval($prices[4]) : 0;
				$json[$t_index]['max_nums[]'] = isset($prices[5]) ? floatval($prices[5]) : 0;
				$json[$t_index]['numbers[]'] = isset($row_array[3]) ? $row_array[3] : '';
				$json[$t_index]['reg_imgs[]'] =array('title'=>$reg_title,'url'=>$return[$index]['reg_img']['image']) ;
				/*拼团活动的商品*/
				if(isset($group_info)){
					$group_type=$group_info['group_type'];
					if($group_type==1 || $group_type==2){
						if($group_type==1){
							$tprice=$group_info['team_price'][$key];
							$return[$index]['cost_price'] =$group_info['join_price'][$key];
						}else{
							$tprice=$group_info['join_price'][$key];
						}
						$return[$index]['price'] =$tprice;
						$return[$index]['seckill_price'] =0;
						$return[$index]['seckill_discount'] =0;
						$return[$index]['max_num'] =0;

						$json[$t_index]['prices[]'] =$tprice;
						$json[$t_index]['seckill_prices[]'] =0;
						$json[$t_index]['max_nums[]'] =0;
					}elseif($group_type==3){
						$team_price=$group_info['team_price'][$key];
						$join_price=$group_info['join_price'][$key];
						$return[$index]['team_price']=$team_price;
						$return[$index]['join_price']=$join_price;
						$json[$t_index]['team_price[]'] =$team_price;
						$json[$t_index]['join_price[]'] =$join_price;
					}
				}
			}
		}
		$data = array();
		$data['limit_id'] = $limit_id;
		$new_goods_spec_list && $data['spec_list'] = $new_goods_spec_list;
		$goods_properties_list && $data['properties_list'] = $goods_properties_list;
		$goods_properties_status_list && $data['properties_status_list'] = $goods_properties_status_list;
		$return && $data['list'] = $return;
		$json && $data['json'] = $json;

        return $data ?: [];
	}

	//获取不同类型的商品
    public function get_list_by_storeid_diypage($store_id,$shop_type=1, $where_info = array(),$page_size=8)
    {
        $where = array('gd.status' => 1);

		if($store_id>=0){
			$where['gd.store_id'] = $store_id;
		}
		if($where_info['sort_id']>0){
			$where['gd.sort_id'] = intval($where_info['sort_id']);
		}

        //获取商城商品
        if($shop_type==2){
			$cat_arr = D('Goods_category')->get_category_array();
        	$where['gd.cat_fid']=array('in',$cat_arr);
        }
		//获取快店商品
		if($shop_type==1){
			$where['gd.goods_type']=array('neq',1);
		}
        if ($where_info['name']) {
            $where['gd.name'] = array('like', '%' . $where_info['name'] . '%');
        }
        if ($where_info['seckill_close_time']) {
            $where['gd.seckill_close_time'] = $where_info['seckill_close_time'];
        }
        if ($where_info['seckill_price']) {
            $where['gd.seckill_price'] = $where_info['seckill_price'];
        }
        if ($where_info['_string']) {
            $where['_string'] = $where_info['_string'];
        }
        
        if (isset($where_info['adjunct_status']) && $where_info['adjunct_status']) {
			$where['sg.status'] = 1;
		}

        // 排除已关闭分类
		if(isset($where_info['showSortIds'])){
			if (empty($where_info['showSortIds'])) {
				$where['gd.sort_id'] = null;
			}else{
				if ($where['_string']) {
					$where['_string'] .= ' AND (gd.sort_id in('.implode(',', $where_info['showSortIds']).'))';
				}else{
					$where['_string'] = '(gd.sort_id in('.implode(',', $where_info['showSortIds']).'))';
				}
			}
		}
		$where['st.status'] = 1;

        $count = $this->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id inner join '.C('DB_PREFIX').'shop_goods_sort sg on gd.sort_id = sg.sort_id')
			->where($where)->field('gd.*')->count();
        import('@.ORG.diypage');
        $Page = new Page($count, $page_size);
        $good_list = $this->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id inner join '.C('DB_PREFIX').'shop_goods_sort sg on gd.sort_id = sg.sort_id')
            ->where($where)
			->field('gd.*')
            ->order('`last_time` DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();

        $goods_image_class = new goods_image();
        $timeNow = time();
        $goodsList = array();
        $site_url = C('config.site_url');
        foreach ($good_list as $value) {
//            // 新增限时显示
//            if (! ($value['show_start_time'] == $value['show_end_time'] || ($value['show_start_time'] == '00:00:00' && $value['show_end_time'] == '23:59:00'))) {
//                $st = strtotime(date('Y-m-d') . ' ' . $value['show_start_time']);
//                $et = strtotime(date('Y-m-d') . ' ' . $value['show_end_time']);
//                if (! ($st <= $timeNow && $timeNow <= $et)) {
//                    continue;
//                }
//            }
            $tmp_pic_arr = explode(';', $value['image']);
            foreach ($tmp_pic_arr as $k => $v) {
                $value['pic_arr'][$k]['title'] = $v;
                $value['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
                $value['image_url'] = $value['pic_arr'][$k]['url']['image'];
            }


            if ($shop_type == 2) {
                // 商城
                $url = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $value["goods_id"];
            } else {
                //快店
                $url = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$value['store_id'].'&good_id=' . $value["goods_id"];
            }
            $value['link_url'] = $url;

            $goodsList[] = $value;
        }
        return array(
            'good_list' => $goodsList,
            'page_bar' => $Page->show()
        );
    }

	/**
	 * @param $store_id
	 * @param int $shop_type
	 * @param array $where_info
	 * @param int $page_size
	 * @return array
	 * 新版商城商品
	 */
	public function get_list_by_new_mall($where_info = array(),$page_size=8)
	{
		$where['gd.status'] = 1;
		$where['gd.is_del'] = 0;
		if ($where_info['name']) {
			$where['gd.name'] = array('like', '%' . $where_info['name'] . '%');
		}
		$where['st.status'] = 1;
		$where['mt.status'] = 1;
		$count = M('Mall_goods')->alias('gd')
			->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
			->join('inner join '.C('DB_PREFIX').'merchant mt on mt.mer_id=st.mer_id')
			->where($where)
			->count();
		import('@.ORG.diypage');
		$Page = new Page($count, $page_size);
		$good_list = M('Mall_goods')->alias('gd')
			->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
			->join('inner join '.C('DB_PREFIX').'merchant mt on mt.mer_id=st.mer_id')
			->where($where)
			->field('gd.*')
			->order('gd.create_time DESC')
			->limit($Page->firstRow . ',' . $Page->listRows)
			->select();
		$goodsList = array();
		$site_url = C('config.site_url');

		foreach ($good_list as $value) {
			$value['image']= replace_file_domain($value['image']);
			$url = $site_url . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $value["goods_id"];
			$value['link_url'] = $url;
			$goodsList[] = $value;
		}
		return array(
			'good_list' => $goodsList,
			'page_bar' => $Page->show()
		);
	}

    public function get_list_by_storeid_diypage_skill($store_id,$shop_type=1, $where_info = array(),$page_size=8)
    {
        $where=array();
        if ($where_info['name']) {
            $where['gd.name'] = array('like', '%' . $where_info['name'] . '%');
        }
        if($store_id>=0){
            $where['gd.store_id']=$store_id;
        }
        $where['gd.cat_fid']=array('gt',0);

        $where['gd.status']=1;
        $where['st.status']=1;
        $where['tlg.end_date']=array('egt',date('Y-m-d'));
        $where['tlg.stock']=array('neq',0);
        $where['tlg.is_del']=array('eq',0);
        $count=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
            ->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')
            ->join('inner join '.C('DB_PREFIX').'time_limited_discount_goods tlg on tlg.goods_id=gd.goods_id')
            ->where($where)->count(1);


        import('@.ORG.diypage');
        $Page = new Page($count, $page_size);
        if($page_size>0){
            $pageShow = $Page->show();
            $good_list=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
                ->join('inner join '.C('DB_PREFIX').'time_limited_discount_goods tlg on tlg.goods_id=gd.goods_id')
                ->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')
                ->where($where)->field('gd.*,tlg.start_date,tlg.start_time,tlg.end_date,tlg.end_time')->limit($Page->firstRow . ',' . $Page->listRows)
                ->order('gd.goods_id desc')->select();
        }else{
            $pageShow = '';
            $good_list=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
                ->join('inner join '.C('DB_PREFIX').'time_limited_discount_goods tlg on tlg.goods_id=gd.goods_id')
                ->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')
                ->where($where)->field('gd.*,tlg.start_date,tlg.start_time,tlg.end_date,tlg.end_time')
                ->order('gd.goods_id desc')->select();
        }

        $goods_image_class = new goods_image();
        $goodsList = array();
        $site_url = C('config.site_url');
        foreach ($good_list as $value) {
            $tmp_pic_arr = explode(';', $value['image']);
            foreach ($tmp_pic_arr as $k => $v) {
                $value['pic_arr'][$k]['title'] = $v;
                $value['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
                $value['image_url'] = $value['pic_arr'][$k]['url']['image'];
            }
            $value['seckill_open_time'] = $value['start_date'].' '.$value['start_time'];
            $value['seckill_close_time'] = $value['end_date'].' '.$value['end_time'];
            $act_info = D('Time_limited_discount_goods')->get_in_progress_record($value['goods_id']);
            $value['seckill_price'] =$act_info['new_limit_price'];
            if ($shop_type == 2) {
                // 商城
                $url = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $value["goods_id"];
            } else {
                //快店
                $url = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$value['store_id'].'&good_id=' . $value["goods_id"];
            }
            $value['link_url'] = $url;

            $goodsList[] = $value;
        }
        return array(
            'good_list' => $goodsList,
            'page_bar' => $pageShow
        );
    }
	public function get_list_by_storeid_diypage_skill_new($shop_type=1, $where,$page_size=10)
	{
		$where['gd.cat_fid']=array('gt',0);
		$where['gd.status']=1;
		$where['st.status']=1;
		$where['tlg.end_date']=array('egt',date('Y-m-d'));
		$where['tlg.stock']=array('neq',0);
		$where['tlg.is_del']=array('eq',0);
		$count=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
			->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')
			->join('inner join '.C('DB_PREFIX').'time_limited_discount_goods tlg on tlg.goods_id=gd.goods_id')
			->where($where)->count(1);


		import('@.ORG.diypage');
		$Page = new Page($count, $page_size);
		if($page_size>0){
			$good_list=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
				->join('inner join '.C('DB_PREFIX').'time_limited_discount_goods tlg on tlg.goods_id=gd.goods_id')
				->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')
				->where($where)->field('gd.goods_id,gd.name,gd.image,gd.price,tlg.start_date,tlg.start_time,tlg.end_date,tlg.end_time,gd.store_id')->limit($Page->firstRow . ',' . $Page->listRows)
				->order('gd.goods_id desc')->select();
		}
		$goods_image_class = new goods_image();
		$goodsList = array();
		$site_url = C('config.site_url');
		foreach ($good_list as $value) {
			$tmp_pic_arr = explode(';', $value['image']);
			foreach ($tmp_pic_arr as $k => $v) {
				$value['pic_arr'][$k]['title'] = $v;
				$value['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
				$value['image_url'] = replace_file_domain($value['pic_arr'][$k]['url']['image']);
			}
			$value['seckill_open_time'] = $value['start_date'].' '.$value['start_time'];
			$value['seckill_close_time'] = $value['end_date'].' '.$value['end_time'];
			$act_info = D('Time_limited_discount_goods')->get_in_progress_record($value['goods_id']);
			$value['seckill_price'] =$act_info['new_limit_price'];
			if ($shop_type == 2) {
				// 商城
				$url = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $value["goods_id"];
			} else {
				//快店
				$url = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$value['store_id'].'&good_id=' . $value["goods_id"];
			}
			$value['image'] = replace_file_domain($value['image']);
			$value['link_url'] = $url;

			$goodsList[] = $value;
		}
		return array(
			'good_list' => $goodsList,
			'total' => $count
		);
	}
	public function getGoodsBySortIds($sortIds, $store_id, $isShow = false, $order = 'sort DESC, goods_id ASC',$is_shop=0,$is_back=0, $uid = 0)
    {
        if (count($sortIds) < 1) {
            return null;
        } elseif (count($sortIds) == 1) {
            if($sortIds[0]){//加个判断 tapdbug_ID1009392
				$where = array('sort_id' => $sortIds[0], 'store_id' => $store_id);
			}else{
                return [];
            }
        } else {
            $where = array('sort_id' => array('in', $sortIds), 'store_id' => $store_id);
        }

		if(!$is_back){//如果不是商家后台，只能获取未下架的商品
			$where['status'] = 1;
		}

        $sort_list = D(_view('Shop_goods_sort'))->field(true)->where($where)->order('`sort` DESC,`sort_id` ASC,`status` DESC')->select();

        if(C('config.is_show_jd_goods')==0){
            $where['jd_sku_id'] = array('eq',0);
        }elseif(C('config.is_show_shop_jd')==0 && $is_shop==1){
            $where['jd_sku_id'] = array('eq',0);
        }

		if($is_shop == 1){
			$where['goods_type']=array('neq',1);
		}

        //只属于商城的商品不展示
        //$where['goods_type']=array('neq',1);
        $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order($order)->select();

        $goods_image_class = new goods_image();
        $timeNow = time();
        $today = date('Ymd');
        $goodsList = array();

        $storeShop = D('Merchant_store_shop')->where(array('store_id' => $store_id))->find();
        $stock_type = $storeShop['stock_type'];
        $time_limited_discount_mod = D('Time_limited_discount_goods');
        foreach ($g_list as $row) {
            //新增限时显示
            if (empty($isShow) && !($row['show_start_time'] == $row['show_end_time'] || ($row['show_start_time'] == '00:00:00' && $row['show_end_time'] == '23:59:00'))) {
                $st = strtotime(date('Y-m-d') . ' ' . $row['show_start_time']);
                $et = strtotime(date('Y-m-d') . ' ' . $row['show_end_time']);
                if (!($st <= $timeNow && $timeNow <= $et)) {
                    continue;
                }
            }

			//只显示未开启员工专卖商品
			if (C('customization.life_tools')) {
				$mer_id = D('Merchant_store')->where(['store_id' => $store_id])->getField('mer_id');
				$setEmployeeCardMerId = C('config.set_employee_card_mer_id') ? explode(',',C('config.set_employee_card_mer_id')) : [];
				if (in_array($mer_id,$setEmployeeCardMerId) && $row['open_employee_card'] == 1) { //已开启开启员工专卖商品，且用户标签不对应的不能购买
					$can_buy = 0;
					if ($uid && $row['employee_lables']) {
						$employee_card_user = D('Employee_card_user')->where(['uid' => $uid, 'mer_id' => $row['employee_mer_id'], 'status' => 1])->field('lable_ids')->find();
						if (!empty($employee_card_user['lable_ids'])) {
							$lable_ids = explode(',', $employee_card_user['lable_ids']);
							$goods_lable_ids = explode(',', $row['employee_lables']);
							if (array_intersect($lable_ids, $goods_lable_ids)) { //存在交集可以购买
								$can_buy = 1;
							}
						}
					}
					if ($can_buy == 0) {
						continue;
					}
				}
			}

            $seckill_record = $time_limited_discount_mod->get_in_progress_record($row['goods_id']);


            $row['o_price'] = getFormatNumber($row['price']);
            if ($seckill_record &&($seckill_record['stock']>0 || $seckill_record['stock'] == -1)) {
                //存在限时优惠
                $row['is_seckill_price'] = true;
                if ($seckill_record['is_spec'] == 1) {
                    $limit_prices = array_column($seckill_record['spec_list'], 'limit_price');
                    $total_stock = array_sum(array_column($seckill_record['spec_list'], 'stock'));
                    sort($limit_prices);
                    $row['price'] = isset($limit_prices[0]) ? getFormatNumber($limit_prices[0]) : getFormatNumber($row['o_price']);
                    $seckill_stock_num = intval($total_stock);
                } else {
                    $row['price'] = getFormatNumber($seckill_record['limit_price']);
                    $seckill_stock_num = $seckill_record['stock'];
                }

                $row['old_price'] = floatval($row['old_price']);
                $row['seckill_price'] = getFormatNumber($row['price']);
                $row['max_num'] = $seckill_record['limit_num'];
            } else {
                $row['is_seckill_price'] = false;
                $row['price'] = getFormatNumber($row['price']);
                $seckill_stock_num = 0;
                $row['old_price'] = floatval($row['old_price']);
            }

            /*$row['sell_day'] = $stock_type ? $today : $row['sell_day'];
            if ($row['seckill_type'] == 1) {
                $now_time = date('H:i');
                $open_time = date('H:i', $row['seckill_open_time']);
                $close_time = date('H:i', $row['seckill_close_time']);

                // 秒杀库存的计算
                if ($today == $row['sell_day']) {
                    $seckill_stock_num = $row['seckill_stock'] == - 1 ? - 1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
                } else {
                    $seckill_stock_num = $row['seckill_stock'];
                }
            } else {
                $now_time = time();
                $open_time = $row['seckill_open_time'];
                $close_time = $row['seckill_close_time'];
                $seckill_stock_num = $row['seckill_stock'] == - 1 ? - 1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
            }

            $row['is_seckill_price'] = false;
            $row['o_price'] = floatval($row['price']);
            if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0 && $seckill_stock_num != 0) {
                $row['price'] = floatval($row['seckill_price']);
                $row['is_seckill_price'] = true;
            } else {
                $row['price'] = floatval($row['price']);
            }

            $row['old_price'] = floatval($row['old_price']);
            $row['seckill_price'] = floatval($row['seckill_price']);*/

            //重置库存
            $twStock = $this->queryTwGoodsStock($row['goods_id']);
            if (!is_null($twStock)) {
                $row['stock_num'] = getFormatNumber($twStock);
            }

			//商品图片
			$tmp_pic_arr = $goods_image_class->get_allImage_by_path($row['image'], 's');
			$row['image'] = thumb_img($tmp_pic_arr[0],240,240,'fill');

            $return = $this->format_spec_value($row['spec_value'], $row['goods_id'], $row['is_properties']);
            $row['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
            $row['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
            $row['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $row['list'] = isset($return['list']) ? $return['list'] : '';
			$row['is_new'] = (($_SERVER['REQUEST_TIME'] - $row['add_time']) > 864000) ? 0 : 1;
			$row['seckill_stock'] = $seckill_stock_num;
			if($row['seckill_stock'] > 0 && $row['is_seckill_price']){
				$row['stock_num'] = $row['seckill_stock'];
			}
			if($row['seckill_stock'] == 0){
				$row['is_seckill_price'] = false;
				$row['price'] = $row['o_price'];
			}

			if(!$row['spec_list']){
				if($row['min_num'] > 0 && $row['stock_num'] >= 0 && $row['min_num'] > $row['stock_num']){
					$row['stock_num'] = 0;
				}
				if($row['min_num'] > 0 && $row['seckill_stock'] >= 0 && $row['min_num'] > $row['seckill_stock']){
					$row['seckill_stock'] = 0;
				}
			}
            $goodsList[$row['sort_id']][] = $row;
        }

        $s_list = array();
        $today = date('w');
        foreach ($sort_list as $value) {
            if (strlen($value['week']) && !$isShow) {
                $week_arr = explode(',', $value['week']);
                if (!in_array($today, $week_arr)) {
                    continue;
                }
            }
            if (isset($goodsList[$value['sort_id']])) {
                $value['goods_list'] = $goodsList[$value['sort_id']];
                $s_list[] = $value;
            }
        }

        return $s_list;
    }

    //获取商品列表展示图片
    public function reg_good_img($img_str=null,$goods_image='',$type=0){
    	if(empty($img_str)){
    		return '';
    	}
    	$img_arr=explode(';',$img_str);
    	$data=array();
    	foreach ($img_arr as $k => $v) {
    		$arr=array();
    		$arr['title']=$v;
    		if('http' === substr($v,0,4)){
				$arr['url'] = $v;
			}else{
    			$arr['url']=$goods_image->get_image_by_path($v,$type);
    		}
    		$data[]=$arr;
    	}
    	return $data;
    }

    public function get_list_by_storeid($store_id, $sort = 0, $keyword = '', $is_shop = 0, $is_wap = 1)
	{
		$database_goods_sort = D('Shop_goods_sort');
		$condition_goods_sort['store_id'] = $store_id;
		$condition_goods_sort['status'] = '1';

		// 状态
		if ($is_wap) {
            // 获得正常显示的分类
            $showSortIds = D('Shop_goods_sort')->getAllShowChildSortIds($store_id, [], true, true, true, true);
            if ($showSortIds) {
            	$condition_goods_sort['sort_id'] = ['in',$showSortIds];
            }else{
            	$condition_goods_sort['sort_id'] = null;
            }
		}

		$sort_list = D(_view('Shop_goods_sort'))->field(true)->where($condition_goods_sort)->order('`sort` DESC,`sort_id` ASC')->select();
		$sort_image_class = new goods_sort_image();
		$s_list = array();
		$today = date('w');
		foreach ($sort_list as $value) {
			if ($value['week']) {
				$week_arr = explode(',',$value['week']);
				if (!in_array($today, $week_arr)) {
					continue;
				}
				$week_str = '';
				foreach ($week_arr as $k=>$v){
					$week_str .= $this->get_week($v).' ';
				}
				$value['week_str'] = $week_str;
			}

			$value['cat_name'] = html_entity_decode($value['cat_name']);
			$value['see_image'] = $sort_image_class->get_image_by_path($value['image'],C('config.site_url'),'s');
			$s_list[$value['sort_id']] = $value;
		}

		$goods_image_class = new goods_image();
        if(C('config.is_show_jd_goods')==0){
            $condition =  array('store_id' => $store_id, 'status' => 1,'jd_sku_id'=>0);
        }else{
            $condition =  array('store_id' => $store_id, 'status' => 1);
            if(C('config.is_show_shop_jd')==0 && $is_shop==1){
                $condition =  array('store_id' => $store_id, 'status' => 1,'jd_sku_id'=>0);
            }
        }

        if ($is_shop == 1) {
            //去除只属于商城的商品
            $condition['goods_type'] = array('neq', 1);
        }

        //wap端不屏蔽
        if ($is_wap == 1) {
            // 过滤附属商品 附属商品不展示 zhoukang
            $ids_list = M('Shop_subsidiary_piece_goods')->where(['store_id' => $store_id, 'is_delete' => 0])->distinct(true)->field('goods_id')->getField('goods_id', true);
            if ($ids_list) {
                $where['goods_id'] = ['not in', $ids_list];
                $condition['goods_id'] = ['not in', $ids_list];
            }
        }

        if ($sort == 1) {
			$g_list = M(_view('Shop_goods'))->field(true)->where($condition)->order('sell_count DESC, goods_id ASC')->select();
		} elseif ($sort == 2) {
			$g_list = M(_view('Shop_goods'))->field(true)->where($condition)->order('price DESC, goods_id ASC')->select();
		} elseif ($keyword != '') {
            if(C('config.is_show_jd_goods')==0){
            	$where=array('store_id' => $store_id, 'name' => array('like', '%' . $keyword . '%'), 'status' => 1,'jd_sku_id'=>0);
            	if($is_shop==1){
            		$where['goods_type']=array('neq',1);
            	}

                $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order('sort DESC, goods_id ASC')->select();
            }elseif(C('config.is_show_shop_jd')==0 && $is_shop==1){
            	$where=array('store_id' => $store_id, 'name' => array('like', '%' . $keyword . '%'), 'status' => 1,'jd_sku_id'=>0);
            	if($is_shop==1){
	            	$where['goods_type']=array('neq',1);
            	}

                $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order('sort DESC, goods_id ASC')->select();
            }else{
            	$where=array('store_id' => $store_id, 'name' => array('like', '%' . $keyword . '%'), 'status' => 1);
            	if($is_shop==1){
	            	$where['goods_type']=array('neq',1);
            	}

                $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order('sort DESC, goods_id ASC')->select();
            }
		} else {
			$g_list = M(_view('Shop_goods'))->field(true)->where($condition)->order('sort DESC, goods_id ASC')->select();
		}

		$today = date('Ymd');
		$storeShop = D('Merchant_store_shop')->where(array('store_id' => $store_id))->find();
		$stock_type = $storeShop['stock_type'];
		$sort_result = array();

		$timeNow = time();
		foreach ($g_list as $row) {
		    //新增限时显示
		    if (!($row['show_start_time'] == $row['show_end_time'] || ($row['show_start_time'] == '00:00:00' && $row['show_end_time'] == '23:59:00'))) {
		        $st = strtotime(date('Y-m-d') . ' ' . $row['show_start_time']);
		        $et = strtotime(date('Y-m-d') . ' ' . $row['show_end_time']);
		        if (!($st <= $timeNow && $timeNow <= $et)) {
		            continue;
		        }
		    }
            $row['name'] = html_entity_decode($row['name'], ENT_QUOTES);
		    $row['sell_day'] = $stock_type ? $today : $row['sell_day'];
			if ($row['seckill_type'] == 1) {
				$now_time = date('H:i');
				$open_time = date('H:i', $row['seckill_open_time']);
				$close_time = date('H:i', $row['seckill_close_time']);
				//秒杀库存的计算
				if ($today == $row['sell_day']) {
				    $seckill_stock_num = $row['seckill_stock'] == -1 ? -1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
				} else {
				    $seckill_stock_num = $row['seckill_stock'];
				}
			} else {
				$now_time = time();
				$open_time = $row['seckill_open_time'];
				$close_time = $row['seckill_close_time'];
				$seckill_stock_num = $row['seckill_stock'] == -1 ? -1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
			}
			$row['is_seckill_price'] = false;
			$row['o_price'] = floatval($row['price']);

			if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0 && $seckill_stock_num != 0) {
				$row['price'] = floatval($row['seckill_price']);
				$row['is_seckill_price'] = true;
			} else {
				$row['price'] = floatval($row['price']);
			}

// 			$row['price'] = floatval($row['price']);
			$row['old_price'] = floatval($row['old_price']);
			$row['seckill_price'] = floatval($row['seckill_price']);

			if($row['image']){
                $tmp_pic_arr = explode(';', $row['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $row['pic_arr'][$key]['title'] = $value;
                    if('http' === substr($value,0,4)){
						$row['pic_arr'][$key]['url'] = $value;
					}else{
                        $row['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value);
                    }
                }
            } else if (C('config.goods_default_image')) {
                $row['pic_arr'][0]['url'] = C('config.goods_default_image');
            }

			$return = $this->format_spec_value($row['spec_value'], $row['goods_id'], $row['is_properties']);
// 			$row['json'] = isset($return['json']) ? json_encode($return['json']) : '';
			$row['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
			$row['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
			$row['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
			$row['list'] = isset($return['list']) ? $return['list'] : '';

			if ($sort == 1 || $sort == 2 || $keyword != '') {//pc端的排序与搜索
				$sort_result[] = $row;
			} else {
				if (isset($s_list[$row['sort_id']])) {
					if (isset($s_list[$row['sort_id']]['goods_list'])) {
						$s_list[$row['sort_id']]['goods_list'][] = $row;
					} else {
						$s_list[$row['sort_id']]['goods_list'] = array($row);
					}
				}
			}
		}
		if ($sort == 1 || $sort == 2 || $keyword != '') {//pc端的排序与搜索
			$s_list = array(array('goods_list' => $sort_result, 'sort_id' => false));
		} else {
			foreach ($s_list as $k => $r) {
				if (!isset($r['goods_list'])) {
					unset($s_list[$k]);
				}
			}
		}
		return $s_list;
	}

	protected function get_week($num){
		switch($num){
			case 1:
				return L_('星期一');
			case 2:
				return L_('星期二');
			case 3:
				return L_('星期三');
			case 4:
				return L_('星期四');
			case 5:
				return L_('星期五');
			case 6:
				return L_('星期六');
			case 0:
				return L_('星期日');
			default:
				return '';
		}
	}

	public function get_goods_by_id($goods_id,$group_id=null)
	{
		$now_goods = M(_view('Shop_goods'))->field(true)->where(array('goods_id' => $goods_id))->find();
		if(empty($now_goods)){
			return false;
		}

        //外卖视频播放地址
        $now_goods['shop_video_url'] = $now_goods['shop_video_url'] ? file_domain() . '/' . $now_goods['shop_video_url'] : '';
        $now_goods['shop_video_preview'] = $now_goods['shop_video_preview'] ? file_domain() . $now_goods['shop_video_preview'] : '';

		/*新增商品销量方法 2020-09-24*/
		//$now_goods['sell_count']=$this->get_sell_count($goods_id);

		//如果设置了最小起购，限购就无效
		if ($now_goods['min_num'] > 1) {
		    $now_goods['max_num'] = 0;
		} else {
		    $now_goods['max_num'] = $now_goods['max_num'];
		}
		//拼团商品
		if($group_id>0){
			$group_info=D('Mall_group_act')->get_group_by_id($group_id,$goods_id,$now_goods);
			if(!empty($group_info)){
				$now_goods['group_info']=$group_info;
				$group_info['group_type']=3;
				$now_goods['group_id']=$group_id;
				$now_goods['group_limit_num']=$group_info['limit_num'];
				$now_goods['group_ftime']=$group_info['ftime'];
				//获取用户当前参团购买商品次数
				D('Mall_group_act')->get_user_buy_count($group_id,$_SESSION['user']['uid']);
			}
		}
		$shop = D('Merchant_store_shop')->where(array('store_id' => $now_goods['store_id']))->find();
		if (empty($shop)) return false;
		$now_goods['mer_id'] = $shop['mer_id'];

		$stock_type = $shop['stock_type'];
		if(!empty($now_goods['image'])){
			$goods_image_class = new goods_image();
			$tmp_pic_arr = explode(';', $now_goods['image']);
			foreach ($tmp_pic_arr as $key => $value) {
				$now_goods['pic_arr'][$key]['title'] = $value;
                if('http' === substr($value,0,4)){
					$now_goods['pic_arr'][$key]['url'] = replace_file_domain($value);
				}else{
                    $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 'm');
                }
			}
			$now_goods['image'] = $now_goods['pic_arr'][0]['url'];
		}else if(C('config.goods_default_image')){
			$now_goods['image'] = C('config.goods_default_image');
		}

        $now_goods['name'] = html_entity_decode($now_goods['name'], ENT_QUOTES);
        $now_goods['is_seckill_price'] = false;
        $now_goods['o_price'] = getFormatNumber($now_goods['price']);
        $now_goods['old_price'] = getFormatNumber($now_goods['price']);
        $now_goods['packing_charge'] = getFormatNumber($now_goods['packing_charge']);

        $time_limited_discount_mod = D('Time_limited_discount_goods');
        $seckill_record = $time_limited_discount_mod->get_in_progress_record($goods_id);
        if ($seckill_record&&($seckill_record['stock']>0 || $seckill_record['stock'] == -1)) {
            //存在限时优惠
            $now_goods['is_seckill_price'] = true;
            $now_goods['price'] = $seckill_record['is_spec'] == 1 ? $now_goods['o_price'] : getFormatNumber($seckill_record['limit_price']);
            $seckill_stock_num = $seckill_record['stock'];
            $now_goods['seckill_price'] = getFormatNumber($seckill_record['limit_price']);
            $now_goods['seckill_stock'] = $seckill_stock_num;
            $now_goods['limit_num'] = $seckill_record['limit_num'];
            $now_goods['max_num'] = $seckill_record['limit_num'];
			$now_goods['sell_count'] = M('Time_limited_discount_sale_record')->where(['limit_id'=>$seckill_record['id']])->sum('num');
        } else {
            $now_goods['price'] = getFormatNumber($now_goods['price']);
            $seckill_stock_num = 0;
        }
        $now_goods['sell_count'] = $now_goods['sell_count'];
        $now_goods['seckill_price'] = getFormatNumber($now_goods['seckill_price']);

		/*if ($now_goods['seckill_type'] == 1) {
			$now_time = date('H:i');
			$open_time = date('H:i', $now_goods['seckill_open_time']);
			$close_time = date('H:i', $now_goods['seckill_close_time']);

			//秒杀库存的计算
			$today = date('Ymd',time());
			if ($today == $now_goods['sell_day']) {
				$seckill_stock_num = $now_goods['seckill_stock'] == -1 ? -1 : (intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) > 0 ? intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) : 0);
			} else {
				$seckill_stock_num = $now_goods['seckill_stock'];
			}
		} else {
			$now_time = time();
			$open_time = $now_goods['seckill_open_time'];
			$close_time = $now_goods['seckill_close_time'];
			$seckill_stock_num = $now_goods['seckill_stock'] == -1 ? -1 : (intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) > 0 ? intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) : 0);
		}
		$now_goods['name'] = html_entity_decode($now_goods['name'], ENT_QUOTES);
		$now_goods['is_seckill_price'] = false;
		$now_goods['o_price'] = floatval($now_goods['price']);
		$now_goods['old_price'] = floatval($now_goods['price']);
		if ($open_time < $now_time && $now_time < $close_time && floatval($now_goods['seckill_price']) > 0 && $seckill_stock_num != 0) {
			$now_goods['price'] = floatval($now_goods['seckill_price']);
			$now_goods['is_seckill_price'] = true;
		} else {
			$now_goods['price'] = floatval($now_goods['price']);
		}
		$now_goods['seckill_price'] = floatval($now_goods['seckill_price']);
		$now_goods['packing_charge'] = floatval($now_goods['packing_charge']);*/
        //fdump("**************************************","dengyuanhui888",1);
        //fdump($now_goods['spec_value'],"dengyuanhui888",1);
        //fdump($now_goods['goods_id'],"dengyuanhui888",1);
        //fdump($now_goods['is_properties'],"dengyuanhui888",1);
        //fdump($now_goods['min_num'],"dengyuanhui888",1);
        //fdump($group_info,"dengyuanhui888",1);
        //fdump("**************************************","dengyuanhui888",1);
		$return = $this->format_spec_value($now_goods['spec_value'], $now_goods['goods_id'], $now_goods['is_properties'], $now_goods['min_num'],$group_info);
		//fdump($return['spec_list'],"dengyuanhui888",1);
		$now_goods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
		$now_goods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
		$now_goods['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
		$now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
		$now_goods['list'] = isset($return['list']) ? $return['list'] : '';

		$today = date('Ymd');
		$now_goods['sell_day'] = $stock_type ? $today : $now_goods['sell_day'];
		$now_goods['today_sell_spec'] = json_decode($now_goods['today_sell_spec'], true);
//		if($now_goods['spec_value']!=''){
//			$now_goods['extra_pay_price']=0;
//		}
		if($now_goods['extra_pay_price']>0){
			$now_goods['extra_pay_price_name']=C('config.Currency_txt').L_('宝');
		}
        if ($now_goods['is_seckill_price']) {
			$now_goods['stock_num'] = $seckill_stock_num;
		} else {
// 			if ($now_goods['sell_day'] == $today) {
// 				$now_goods['stock_num'] = $now_goods['stock_num'] == -1 ? -1 : (intval($now_goods['stock_num'] - $now_goods['today_sell_count']) > 0 ? intval($now_goods['stock_num'] - $now_goods['today_sell_count']) : 0);
// 			}
			if ($now_goods['sell_day'] != $today) {
				if($now_goods['sell_type']==2){
					$now_goods['stock_num'] = $now_goods['original_stock'];
	                if($now_goods['original_weight'] == -1){
	                    $now_goods['stock_num'] = -1;
	                }else{
	                    $goods_weight =  $now_goods['weight_unit'] == 2 ? $now_goods['weight'] * 1000 : $now_goods['weight'];
	                    $now_goods['stock_num'] = $goods_weight > 0 ? floor($now_goods['original_weight'] / $goods_weight) : 0;
	                }
				}
			}else{
                if($now_goods['sell_type'] == 2 && $now_goods['stock_weight'] == -1){
                    $now_goods['stock_num'] = -1;
                }elseif($now_goods['sell_type'] == 2){
                    $goods_weight =  $now_goods['weight_unit'] == 2 ? $now_goods['weight'] * 1000 : $now_goods['weight'];
                    $now_goods['stock_num'] = $goods_weight > 0 ? floor($now_goods['stock_weight'] / $goods_weight) : 0;
                }
            }
		}
		foreach ($now_goods['list'] as $key => &$row) {
			if ($now_goods['is_seckill_price']) {
				//$row['stock_num'] = $seckill_stock_num;
			} else {
				$t_count = isset($now_goods['today_sell_spec'][$key]) ? intval($now_goods['today_sell_spec'][$key]) : 0;
				if ($now_goods['sell_day'] == $today) {
					$row['stock_num'] = $row['stock_num'] == -1 ? -1 : (intval($row['stock_num'] - $t_count) > 0 ? intval($row['stock_num'] - $t_count) : 0);
				}
			}
		}
		$template_id = intval($now_goods['freight_template']);
		if ($template_id) {
			if ($min = D('Express_template_value')->field(true)->where(array('tid' => $template_id, 'full_money' => array('gt', 0)))->find('first_freight')) {
				$min = 0;
			} else {
				$min = D('Express_template_value')->where(array('tid' => $template_id))->min('first_freight');
				$min = min($min, $now_goods['freight_value']);
			}
			$max = D('Express_template_value')->where(array('tid' => $template_id))->max('first_freight');
			$max = max($max, $now_goods['freight_value']);
			if ($min < $max) {
				$now_goods['deliver_fee'] = floatval($min) . '~' . floatval($max);
			} else {
				$now_goods['deliver_fee'] = floatval($min);
			}
		} else {
			$now_goods['deliver_fee'] = floatval($now_goods['freight_value']);
		}
		$nowtime = time();
		$now_goods['is_new'] = (($nowtime - $now_goods['add_time']) > 864000) ? 0 : 1;

		if(!$now_goods['spec_list']){
			if($now_goods['min_num'] > 0 && $now_goods['stock_num'] >= 0 && $now_goods['min_num'] > $now_goods['stock_num']){
				$now_goods['stock_num'] = 0;
			}
			if($now_goods['min_num'] > 0 && $now_goods['seckill_stock'] >= 0 && $now_goods['min_num'] > $now_goods['seckill_stock']){
				$now_goods['seckill_stock'] = 0;
			}
		}
		/*判断商品是否是自营商品*/
		if(C('config.open_kefu_url') && C('config.kefu_mer_id')>0){
			$store_arr=M('Merchant_store')->where(array('mer_id'=>C('config.kefu_mer_id')))->getField('store_id',true);
			if(is_array($store_arr) && in_array($now_goods['store_id'], $store_arr)){
				$now_goods['is_self']=1;
			}else{
				$now_goods['is_self']=0;
			}
		}
		$now_goods['label_arr']=$now_goods['label_str']?explode(',', $now_goods['label_str']):array();

        $spicy_arr = D('Shop_goods')->spicy_arr();
        $now_goods['spicy'] = $spicy_arr[$now_goods['spicy']] ? $spicy_arr[$now_goods['spicy']] : '';//辣度
        $now_goods['flavor'] = $now_goods['flavor'] ? json_decode($now_goods['flavor']) : array();//口味
        $now_goods['material'] = $now_goods['material'] ? json_decode($now_goods['material']) : array();//原料
        $now_goods['special_food_materials'] = $now_goods['special_food_materials'] ? json_decode($now_goods['special_food_materials']) : array();//特殊食材
        $now_goods['cooking_methods'] = $now_goods['cooking_methods'] ? json_decode($now_goods['cooking_methods']) : array();//做法

        //增加积分抵扣
        $score = $this->getScoreMaxByGoodsId($goods_id);
        if ($score['is_show']) {
			$score['reduce_money'] = $score['is_percent'] ? getFormatNumber($now_goods['price'] * $score['score_max_percent']) : getFormatNumber($score['score_max'] / C('config.user_score_use_percent'));
            $now_goods['price_type'] = 2;
            $now_goods['account_money'] = $now_goods['price'] - $score['reduce_money'] > 0 ? getFormatNumber($now_goods['price'] - $score['reduce_money']) : 0;
            $now_goods['account_score'] = intval($score['reduce_money'] * C('config.user_score_use_percent'));;
        }
		return $now_goods;
	}

	//获取商品销量
	public function get_sell_count($goods_id){
            $where=array();
            $where['de.goods_id']=$goods_id;
            $where['ord.paid']=1;
             $data=M('Shop_order')->alias('ord')->join('inner join '.C("DB_PREFIX").'shop_order_detail de on ord.order_id=de.order_id')
                 ->join('inner join '.C('DB_PREFIX').'user us on ord.uid=us.uid')
                 ->where($where)->count();
            return $data;
    }
    /**
     * 获取每个用户对某个商品的购买量
     */
    public function getBuyGoodsNum($uid, $goods_id,$spec_ids='')
    {
		$status = ' (4, 5) ';
		if($_POST['page_from'] == 'pay' && $_POST['pay_from'] == 'pay'){//个人中心订单列表付款
			$status = ' (0, 4, 5) ';
		}
        $sql = "SELECT sum(d.num) as num FROM " . C('DB_PREFIX') . "shop_order as o INNER JOIN " . C('DB_PREFIX') . "shop_order_detail as d ON o.order_id=d.order_id AND o.uid={$uid} WHERE  o.status NOT IN $status AND d.goods_id={$goods_id}";
		$spec_ids && $sql .= ' AND d.spec_id="' . $spec_ids . '"';
        $res = $this->query($sql);
        $num = isset($res[0]['num']) ? intval($res[0]['num']) : 0;
        return $num;
    }

    /**
     * 检查库存
     * @param int $store_id
     * @param int $goods_id
     * @param int $num
     * @param string $spec_ids = 'id_id'
     * @return array:number string |multitype:number unknown |multitype:number string Ambigous <number, mixed>
     */
    public function check_stock($goods_id, $num, $spec_ids = '', $stock_type = 0, $store_id = 0, $is_market = false, $uid = 0, $group_id = 0, $bar_id = 0, $team_id = 0, $tWeight = 0, $host_goods_id = 0, $mall_periodic_purchase_specs=array(),$original_price_type=false)
    {
        if ($store_id) {
            $now_goods = $this->field(true)->where(array('goods_id' => $goods_id, 'store_id' => $store_id))->find();
        } else {
            $now_goods = $this->field(true)->where(array('goods_id' => $goods_id))->find();
        }
        if (empty($now_goods)) return array('status' => 0, 'msg' => L_('商品不存在'));
        //新增拼团商品逻辑 （team_id>0 参团）
        if (!$mall_periodic_purchase_specs && $group_id > 0) {
            $group_data = D('Mall_group_act')->check_group_info($goods_id, $group_id, $team_id);
            $result_arr = array();
            if ($group_data['error'] == 0) {
                $group_info = $group_data['data'];
                //校验团信息和用户是否已参与该团
                if ($team_id > 0) {
                    $result = D('Mall_group_team')->get_team_info($group_info['id'], $team_id, $uid, $group_info);
                    if ($result['error'] == 1) {
                        $result_arr['status'] = 0;
                        $result_arr['msg'] = $result['msg'];
                        return $result_arr;
                    }
                    //"老带新团"类别团的校验
                    if ($group_info['join_type'] == 2) {
                        $buy_num = M('shop_order')->where(array('uid' => $uid, 'store_id' => $store_id))->count(1);
                        if ($buy_num > 0) {
                            $result_arr['status'] = 0;
                            $result_arr['msg'] = L_('抱歉，该团为老带新团，仅限新用户参与');
                            return $result_arr;
                        }
                    }
                    $group_info['group_type'] = 2;
                } else {
                    $group_info['group_type'] = 1;
                }
                $now_goods['min_num'] = 0;    //起购失效
                $now_goods['max_num'] = 0;    //无规格限购失效
                /*-----------------------------*/
                $group_num = D('Mall_group_act')->get_group_goods_num($group_info['id'], $uid, $goods_id);
                //限购数量
                $limit_num = $group_info['limit_num'];
                if ($limit_num > 0) {
                    if ($group_num >= $limit_num) {
                        $result_arr['status'] = 0;
                        $result_arr['msg'] = L_("拼团商品【X1】每个用户限购X2，您已经购买了X3", array("X1" => $now_goods['name'], "X2" => $limit_num . $now_goods['unit'], "X3" => $limit_num . $now_goods['unit']));
                        return $result_arr;
                    } elseif ($group_num + $num > $limit_num) {
                        $result_arr['status'] = 0;
                        if ($group_num > 0) {
                            $result_arr['msg'] = L_("拼团商品【X1】您最多可购X2，您已经购买了X3", array("X1" => $now_goods['name'], "X2" => intval($limit_num - $group_num) . $now_goods['unit'], "X3" => $group_num . $now_goods['unit']));
                        } else {
                            $result_arr['msg'] = L_("拼团商品【X1】您最多可购X2", array("X1" => $now_goods['name'], "X2" => intval($limit_num))) . $now_goods['unit'];
                        }
                        return $result_arr;
                    }
                }
            } else {
                $result_arr['status'] = 0;
                $result_arr['msg'] = $group_data['msg'];
                return $result_arr;
            }
        } elseif (!$mall_periodic_purchase_specs && $bar_id > 0) {
            $result = D('Mall_bargain_team')->get_bar_good_price($bar_id, $team_id);
            if ($result['error'] == 1) {
                return array('status' => 0, 'msg' => $result['msg']);
            } else {
                $bar_price = $result['price'];
            }
        }
        if (!$mall_periodic_purchase_specs && $now_goods['min_num'] > 0 && $num < $now_goods['min_num']) {
            return array('status' => 0, 'msg' => L_("商品【X1】每单X2起购", array("X1" => $now_goods['name'], "X2" => $now_goods['min_num'] . $now_goods['unit'])));
        }

		//商品缩略图
		$goods_image_class = new goods_image();
        $tmp_pic_arr = $goods_image_class->get_allImage_by_path($now_goods['image'], 's');
		$image = thumb_img($tmp_pic_arr[0],240,240,'fill');

        $stock_num = 0;
        if ($now_goods['status'] != 1 && $is_market === false) return array('status' => 0, 'msg' => $now_goods['name'] . L_('商品已下架'));
        $today = date('Ymd');
        //商品的库存类型（0：每日更新相同的库存，1:商品的总库存不会自动更新）
        $now_goods['sell_day'] = $stock_type ? $today : $now_goods['sell_day'];
        $maxNum = $now_goods['max_num'];
        $seckill_record = D('Time_limited_discount_goods')->get_in_progress_record($goods_id);
        $limit_id = 0;
        if ($seckill_record) {
            //存在限时优惠
            $limit_id = $seckill_record['id'];
            $is_seckill_price = true;
            $maxNum = $seckill_record['limit_num'] > 0 ? $seckill_record['limit_num'] : 0;
            if($seckill_record['is_spec'] == 1){
                $limit_prices = array_column($seckill_record['spec_list'], 'limit_price');
                sort($limit_prices);
                $total_stock = $seckill_record['spec_list'][$spec_ids]['stock'];
                $price = getFormatNumber($limit_prices[0]);
                $seckill_stock_num = intval($total_stock);
            }else{
                $price = getFormatNumber($seckill_record['limit_price']);
                $seckill_stock_num = $seckill_record['stock'];
            }
            $maxNum = $seckill_record['limit_num'];
        } else {
            $is_seckill_price = false;
            $price = getFormatNumber($now_goods['price']);
            $seckill_stock_num = 0;
        }
        if($seckill_stock_num == 0){
            $is_seckill_price = false;
            $price = getFormatNumber($now_goods['price']);
            $seckill_stock_num = 0;
        }

        /*if ($now_goods['seckill_type'] == 1) {//秒杀类型（0：固定时间段，1：每天的时间段）
            $now_time = date('H:i');
            $open_time = date('H:i', $now_goods['seckill_open_time']);
            $close_time = date('H:i', $now_goods['seckill_close_time']);
            //秒杀库存的计算
            if ($today == $now_goods['sell_day']) {
                $seckill_stock_num = $now_goods['seckill_stock'] == -1 ? -1 : (intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) > 0 ? intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) : 0);
            } else {
                $seckill_stock_num = $now_goods['seckill_stock'];
            }
        } else {
            $now_time = time();
            $open_time = $now_goods['seckill_open_time'];
            $close_time = $now_goods['seckill_close_time'];
            //秒杀库存的计算
            $seckill_stock_num = $now_goods['seckill_stock'] == -1 ? -1 : (intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) > 0 ? intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) : 0);
        }
        $maxNum = $now_goods['max_num'];
        $is_seckill_price = false;
        if ($open_time < $now_time && $now_time < $close_time && floatval($now_goods['seckill_price']) > 0 && $seckill_stock_num != 0) {
            $price = floatval($now_goods['seckill_price']);
            $is_seckill_price = true;
        } else {
            $price = floatval($now_goods['price']);
        }*/
        /*商品总量*/
        $weight = $now_goods['weight'];

        /*新增拼团商品逻辑*/
        if (isset($group_info)) {
            if ($group_info['group_type'] == 1) {        //团长
                $price = floatval($group_info['team_price']['0']);
                $now_goods['cost_price'] = floatval($group_info['join_price']['0']);
            } elseif ($group_info['group_type'] == 2) {    //参团
                $price = floatval($group_info['join_price']['0']);
            }
        }

        // 如果周期购规格存在取 规格相关数据
        if ($mall_periodic_purchase_specs) {
            $price = floatval($mall_periodic_purchase_specs['specs_price']);
        }

        $old_price = floatval($now_goods['price']);
        $cost_price = floatval($now_goods['cost_price']);
		if($original_price_type){//强制原价购买【米粒商城的功能】
			$price_type = 1;
		}else{
			$price_type = $now_goods['price_type'];
		}
        if ($price_type == 1) {
            $account_money = $now_goods['account_money'] > 0 ? $now_goods['account_money'] : $now_goods['price'];
        } else {
            $account_money = $now_goods['account_money'];
        }
		$account_score = $now_goods['account_score'];
        if ($spec_ids && $now_goods['spec_value']) {
            $return = $this->format_spec_value($now_goods['spec_value'], $now_goods['goods_id'], $now_goods['is_properties'], 0, $group_info);
            $list = isset($return['list']) ? $return['list'] : '';
            if (isset($list[$spec_ids])) {
                $today_sell_spec = json_decode($now_goods['today_sell_spec'], true);
                if($is_seckill_price){
                    $price = $list[$spec_ids]['seckill_price']?$list[$spec_ids]['seckill_price']:$price;
                }else{
                    $price = $list[$spec_ids]['price']?$list[$spec_ids]['price']:$price;
                }
                /*if ($now_goods['seckill_type'] == 1) {
                    $now_time = date('H:i');
                    $open_time = date('H:i', $now_goods['seckill_open_time']);
                    $close_time = date('H:i', $now_goods['seckill_close_time']);
                } else {
                    $now_time = time();
                    $open_time = $now_goods['seckill_open_time'];
                    $close_time = $now_goods['seckill_close_time'];
                }
                if ($open_time < $now_time && $now_time < $close_time && $list[$spec_ids]['seckill_price'] > 0 && $seckill_stock_num != 0) {
                    $price = $list[$spec_ids]['seckill_price'];
                    $is_seckill_price = true;
                } else {
                    $price = $list[$spec_ids]['price'];
                }*/

                $old_price = floatval($list[$spec_ids]['price']);
                $cost_price = floatval($list[$spec_ids]['cost_price']);
				$account_money = floatval($list[$spec_ids]['account_money']);
				$account_score = floatval($list[$spec_ids]['account_score']);
                $number = $list[$spec_ids]['number'];
                $maxNum = $list[$spec_ids]['max_num'];
                $weight = $list[$spec_ids]['weight'];
                if ($is_seckill_price) {
                    $stock_num = $seckill_stock_num;
                } else {
                    if ($today == $now_goods['sell_day']) {
                        $sell_count = isset($today_sell_spec[$spec_ids]) ? intval($today_sell_spec[$spec_ids]) : 0;
                        $stock_num = $list[$spec_ids]['stock_num'] == -1 ? -1 : (intval($list[$spec_ids]['stock_num'] - $sell_count) > 0 ? intval($list[$spec_ids]['stock_num'] - $sell_count) : 0);
                    } else {
                        $stock_num = $list[$spec_ids]['stock_num'];
                    }
                }
            } else {
                return array('status' => 0, 'msg' => L_('您选择的规格可能被商家修改了'));
            }
        } else {
            if ($is_seckill_price) {
                $stock_num = $seckill_stock_num;
            } elseif ($now_goods['sell_type'] == 2) {
                $stock_num = -1;    //按照重量的库存校验
                if ($today == $now_goods['sell_day']) {
// 					$stock_num = $now_goods['stock_num'] == -1 ? -1 : (intval($now_goods['stock_num'] - $now_goods['today_sell_count']) > 0 ? intval($now_goods['stock_num'] - $now_goods['today_sell_count']) : 0);
                    $stock_weight = $now_goods['stock_weight'];
                } else {
// 					$stock_num = $now_goods['stock_num'];
                    $stock_weight = $now_goods['original_weight'];
                }
            } else {
                if ($today == $now_goods['sell_day']) {
                    $stock_num = $now_goods['stock_num'];
                    /*$stock_num = $now_goods['stock_num'] == -1 ? -1 : (intval($now_goods['stock_num'] - $now_goods['today_sell_count']) > 0 ? intval($now_goods['stock_num'] - $now_goods['today_sell_count']) : 0);*/
                } else {
                    $stock_num = $now_goods['stock_num'];
                }
            }
            $number = $now_goods['number'];
        }
        if ($mall_periodic_purchase_specs){
            // 如果有周期购，库存以周期购规格为准
            $stock_num = $mall_periodic_purchase_specs['specs_stock'];
        }

        $maxNum = intval($maxNum);
        if ($now_goods['min_num'] > 1 && !$is_seckill_price) {
            $maxNum = 0;
        }
        if ($now_goods['limit_type'] == 1 && $maxNum > 0) {
            $buyNum = $this->getBuyGoodsNum($uid, $goods_id,$spec_ids);
            if ($maxNum - $buyNum < 1) {
                return array('status' => 0, 'msg' => L_("商品【X1】每个用户限购X2，您已经购买X3", array("X1" => $now_goods['name'], "X2" => $maxNum . $now_goods['unit'], "X3" => $maxNum . $now_goods['unit'])));
            } elseif ($maxNum - $buyNum < $num) {
                if ($buyNum > 0) {
                    return array('status' => 0, 'msg' => L_("商品【X1】您最多可购X2，您已经购买X3", array("X1" => $now_goods['name'], "X2" => intval($maxNum - $buyNum) . $now_goods['unit'], "X3" => $buyNum . $now_goods['unit'])));
                } else {
                    return array('status' => 0, 'msg' => L_("商品【X1】您最多可购X2", array("X1" => $now_goods['name'], "X2" => intval($maxNum - $buyNum))) . $now_goods['unit']);
                }
            }
        }

        //如果为附属商品才限购
        if ($host_goods_id == 0) {
            if (empty($is_seckill_price) && $maxNum > 0 && $num > $maxNum) {
                return array('status' => 0, 'msg' => L_("商品【X1】每单限购X2", array("X1" => $now_goods['name'], "X2" => $maxNum . $now_goods['unit'])));
            }
        }

        //砍价活动商品价格
        if ($bar_id > 0 && isset($bar_price) && $bar_price >= 0) {
            $price = $bar_price;
        }
        if ($now_goods['sell_type'] == 2 && $now_goods['weight_unit'] == 2) { //称重商品计算重量
            if ($tWeight) {
                $total_weight = $tWeight;
            } else {
                $total_weight = $now_goods['weight'] * 1000 * $num;
            }
        } elseif ($now_goods['sell_type'] == 2) {
            if ($tWeight) {
                $total_weight = $tWeight;
            } else {
                $total_weight = $now_goods['weight'] * $num;
            }
        }
        if ($now_goods['weight_unit'] == 2) { //转换成g
            $now_goods['goods_weight'] = $now_goods['weight'] * 1000;
        } else {
            $now_goods['goods_weight'] = $now_goods['weight'];
        }

        // 如果是附属商品和非多规格商品取出对应的商品价格
        if ($host_goods_id AND empty($now_goods['spec_value'])) {
            $price = M('shop_subsidiary_piece_goods')->where(array('goods_id' => $goods_id, 'host_goods_id' => $host_goods_id, 'is_delete' => 0))->getField('price');
        }
		$goods_stock_num_key = 'goods_stock_'.$now_goods['goods_id'];
		$s_stock_num = S($goods_stock_num_key);
		if($s_stock_num<>false&&$s_stock_num==0){
			return array('status' => 0, 'msg' => '商品【'.$now_goods['name'].'】库存不足');
		}

		if($price_type==3){
			$price = 0;
			$old_price = 0;
		}elseif($price_type == 2){
			$price = $account_money;
			$old_price = $account_money;
		} else if ($price_type == 1) {
            $price = $price;
            $old_price = $old_price;
        }elseif($price_type>0){
            $price = $account_money > 0 ? $account_money : $price;
            $old_price = $account_money > 0 ? $account_money : $old_price;
		}else{
			$price = floatval($price);
			$old_price = floatval($old_price);
		}
		if($price_type<2){
			$account_score = 0;
		}

        if ($stock_num == -1) {
            if ($now_goods['sell_type'] == 2) {  //称重商品
                if ($stock_weight >= $total_weight || $now_goods['stock_weight'] == -1) {
                    $return = array('status' => 1, 'num' => $num, 'goods_id' => $now_goods['goods_id'], 'is_use_coupon' => $now_goods['is_use_coupon'], 'is_discount' => $now_goods['is_discount'], 'maxNum' => $maxNum, 'is_seckill_price' => $is_seckill_price, 'old_price' => $old_price, 'cost_price' => $cost_price, 'price' => $price, 'image' => $image, 'limit_type' => $now_goods['limit_type'], 'packing_charge' => $now_goods['packing_charge'], 'freight_type' => $now_goods['freight_type'], 'freight_value' => $now_goods['freight_value'], 'freight_template' => $now_goods['freight_template'], 'unit' => $now_goods['unit'], 'number' => $number, 'sort_id' => $now_goods['sort_id'], 'name' => $now_goods['name'], 'jd_sku_id' => $now_goods['jd_sku_id'], 'sell_type' => $now_goods['sell_type'], 'goods_weight' => $now_goods['goods_weight']);
                    $return['weight'] = $weight;
                    $return['weight_unit'] = $now_goods['weight_unit'];
                    $return['account_money'] = $account_money;
                    $return['account_score'] = $account_score;
                    $return['price_type'] = $price_type;
                    return $return;
                } else {
                    return array('status' => 0, 'msg' => L_('商品【X1】库存不足',array('X1'=>$now_goods['name'])));
                }
            } else {
                $return = array('status' => 1, 'num' => $num, 'goods_id' => $now_goods['goods_id'], 'is_use_coupon' => $now_goods['is_use_coupon'], 'is_discount' => $now_goods['is_discount'], 'maxNum' => $maxNum, 'is_seckill_price' => $is_seckill_price, 'old_price' => $old_price, 'cost_price' => $cost_price, 'price' => $price, 'image' => $image, 'limit_type' => $now_goods['limit_type'], 'packing_charge' => $now_goods['packing_charge'], 'freight_type' => $now_goods['freight_type'], 'freight_value' => $now_goods['freight_value'], 'freight_template' => $now_goods['freight_template'], 'unit' => $now_goods['unit'], 'number' => $number, 'sort_id' => $now_goods['sort_id'], 'name' => $now_goods['name'], 'jd_sku_id' => $now_goods['jd_sku_id'], 'sell_type' => $now_goods['sell_type'], 'goods_weight' => $now_goods['goods_weight']);
                $return['weight'] = $weight;
                $return['weight_unit'] = $now_goods['weight_unit'];
				$return['account_money'] = $account_money;
                $return['account_score'] = $account_score;
				$return['price_type'] = $price_type;
                return $return;
            }
        } elseif ($stock_num == 0) {
            return array('status' => 0, 'msg' => L_('商品【X1】库存不足',array('X1'=>$now_goods['name'])));
        } elseif ($stock_num - $num >= 0) {
            if ($now_goods['sell_type'] == 2) {  //称重商品
                if ($stock_weight >= $total_weight || $now_goods['stock_weight'] == -1) {
                    $return = array('status' => 1, 'num' => $num, 'goods_id' => $now_goods['goods_id'], 'is_use_coupon' => $now_goods['is_use_coupon'], 'is_discount' => $now_goods['is_discount'], 'maxNum' => $maxNum, 'is_seckill_price' => $is_seckill_price, 'old_price' => $old_price, 'cost_price' => $cost_price, 'price' => $price, 'image' => $image, 'limit_type' => $now_goods['limit_type'], 'packing_charge' => $now_goods['packing_charge'], 'freight_type' => $now_goods['freight_type'], 'freight_value' => $now_goods['freight_value'], 'freight_template' => $now_goods['freight_template'], 'unit' => $now_goods['unit'], 'number' => $number, 'sort_id' => $now_goods['sort_id'], 'name' => $now_goods['name'], 'jd_sku_id' => $now_goods['jd_sku_id'], 'sell_type' => $now_goods['sell_type'], 'goods_weight' => $now_goods['goods_weight']);
                    $return['weight'] = $weight;
                    $return['weight_unit'] = $now_goods['weight_unit'];
					$return['account_money'] = $account_money;
                    $return['account_score'] = $account_score;
					$return['price_type'] = $price_type;
                    return $return;
                } else {
                    return array('status' => 0, 'msg' => L_('商品【X1】库存不足',array('X1'=>$now_goods['name'])));
                }
            } else {
                $return = array('status' => 1, 'num' => $num, 'goods_id' => $now_goods['goods_id'], 'maxNum' => $maxNum, 'is_use_coupon' => $now_goods['is_use_coupon'], 'is_discount' => $now_goods['is_discount'], 'is_seckill_price' => $is_seckill_price, 'old_price' => $old_price, 'cost_price' => $cost_price, 'price' => $price, 'image' => $image, 'limit_type' => $now_goods['limit_type'], 'packing_charge' => $now_goods['packing_charge'], 'freight_type' => $now_goods['freight_type'], 'freight_value' => $now_goods['freight_value'], 'freight_template' => $now_goods['freight_template'], 'unit' => $now_goods['unit'], 'number' => $number, 'sort_id' => $now_goods['sort_id'], 'name' => $now_goods['name'], 'jd_sku_id' => $now_goods['jd_sku_id'], 'sell_type' => $now_goods['sell_type'], 'goods_weight' => $now_goods['goods_weight']);
                $return['weight'] = $weight;
                $return['weight_unit'] = $now_goods['weight_unit'];
				$return['account_money'] = $account_money;
                $return['account_score'] = $account_score;
				$return['price_type'] = $price_type;
                return $return;
            }
        } else {
            if ($now_goods['sell_type'] == 2) {  //称重商品
                if ($stock_weight >= $total_weight || $now_goods['stock_weight'] == -1) {
                } else {
                    return array('status' => 0, 'msg' => L_('商品【X1】库存不足',array('X1'=>$now_goods['name'])));
                }
            } else {
                $return = array('status' => 2, 'num' => $stock_num, 'goods_id' => $now_goods['goods_id'], 'maxNum' => $maxNum, 'is_use_coupon' => $now_goods['is_use_coupon'], 'is_discount' => $now_goods['is_discount'], 'is_seckill_price' => $is_seckill_price, 'old_price' => $old_price, 'cost_price' => $cost_price, 'msg' => $now_goods['name'] . ' ' . L_('库存不足,最多能购买') . $stock_num . $now_goods['unit'], 'limit_type' => $now_goods['limit_type'], 'number' => $number, 'price' => $price, 'image' => $image, 'packing_charge' => $now_goods['packing_charge'], 'freight_type' => $now_goods['freight_type'], 'freight_value' => $now_goods['freight_value'], 'freight_template' => $now_goods['freight_template'], 'unit' => $now_goods['unit'], 'sort_id' => $now_goods['sort_id'], 'name' => $now_goods['name'], 'jd_sku_id' => $now_goods['jd_sku_id'], 'sell_type' => $now_goods['sell_type'], 'goods_weight' => $now_goods['goods_weight']);
                $return['weight'] = $weight;
                $return['weight_unit'] = $now_goods['weight_unit'];
				$return['account_money'] = $account_money;
                $return['account_score'] = $account_score;
				$return['price_type'] = $price_type;
                return $return;
            }
        }
    }

	/**
	 * 更新库存
	 * $id_index = 1_1
	 * $goods 是shop_goods_order_detail 的一条记录
	 * $type 操作类型 0：加销量，减库存，1：加库存，减销量
	 */
// 	public function update_stock($goods_id, $num, $id_index = '', $is_seckill = 0)
	public function update_stock($goods, $type = 0)
	{
		static $shops;
		$today = date('Ymd');
		$now_goods = $this->field(true)->where(array('goods_id' => $goods['goods_id']))->find();
		if (empty($now_goods)) return array('status' => 0, 'msg' => L_('商品不存在'));

		if (isset($shops[$now_goods['store_id']])) {
			$shop = $shops[$now_goods['store_id']];
		} else {
			$shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $now_goods['store_id']))->find();
			$shops[$shop['store_id']] = $shop;
		}
		//$shop['stock_type']库存变更类型，0:每天更新成固定库存，1：不会自动更新库存
		$now_goods['sell_day'] = $shop['stock_type'] ? $today : $now_goods['sell_day'];

		if ($type == 0) {//加销量
			$num = $goods['num'];
            $total_num = $goods['num'];
            $seckill_num = $goods['num'];
            if($goods['sell_type']==2){ //计重商品
                $weight = $goods['goods_weight'] * $goods['num'];
                if($goods['code_weight']) $weight += $goods['code_weight'];
            }
		} else {//减销量
			$total_num = $goods['num'] * -1;
			if ($today == date('Ymd', $goods['create_time'])) {//下单是就是今天时候要实时回滚销量
				$num = $goods['num'] * -1;
				$seckill_num = $goods['num'] * -1;
			} else {//下单不是今天的情况
				if ($shop['stock_type'] == 0) {//每天固定库存的情况下，就无需回滚今天的销量
					$num = 0;
				} else {
					$num = $goods['num'] * -1;
				}
				if ($now_goods['seckill_type'] == 1) {//秒杀库存类型，1：每天固定库存，就无需回滚今天的销量
					$seckill_num = 0;
				} else {//0：固定库存
					$seckill_num = $goods['num'] * -1;
				}
			}
            if($goods['sell_type']==2){ //计重商品
                if ($today == date('Ymd', $goods['create_time'])) {//下单是就是今天时候要实时回滚销量
                    $weight = ($goods['goods_weight'] * $goods['num']) * -1;
                    if ($goods['code_weight']) $weight -= $goods['code_weight'];
                }else{//下单不是今天的情况
                    if ($shop['stock_type'] == 0) {//每天固定库存的情况下，就无需回滚今天的销量
                        $weight = 0;
                    } else {
                        $weight = ($goods['goods_weight'] * $goods['num']) * -1;
                        if ($goods['code_weight']) $weight -= $goods['code_weight'];
                    }
                }
            }
		}

		$today_sell_count = $now_goods['today_sell_count'];//今日销量
        $today_sell_weight = $now_goods['today_sell_weight'];//今日销售重量
		$sell_count = $now_goods['sell_count'];//总销量
		$sell_weight = $now_goods['sell_weight'];//总销售重量
		$today_sell_spec = $now_goods['today_sell_spec'] ? json_decode($now_goods['today_sell_spec'], true) : '';//今日每种规格下的销量
		if(empty($today_sell_spec) || !is_array($today_sell_spec)){
			$today_sell_spec = array();
		}

		$today_seckill_count = $now_goods['today_seckill_count'];//今日秒杀的销量
		if ($today == $now_goods['sell_day']) {
		    $stock_num = $now_goods['stock_num'];//库存
		    $stock_weight = $now_goods['stock_weight'];//库存重量
		} else {
		    $stock_num = $now_goods['original_stock'];//库存
            $stock_weight = $now_goods['original_weight'];//库存重量
		}

		if ($goods['spec_id']) {//某种规格
			$id_index = $goods['spec_id'];
			isset($today_sell_spec[$id_index]) || $today_sell_spec[$id_index] = 0;
			if ($today == $now_goods['sell_day']) {
                $today_sell_spec[$id_index] = $today_sell_spec[$id_index] + $num;
				$today_sell_count += $num;
			} else {
				$today_sell_spec[$id_index] = $num;
				$today_sell_count = $num;
			}
			$sell_count += $total_num;
			$today_sell_spec[$id_index] = max(0, $today_sell_spec[$id_index]);

			if ($goods['is_seckill']) {
				if ($now_goods['seckill_type'] == 1) {
					if ($today == $now_goods['sell_day']) {
						$today_seckill_count += $seckill_num;
					} else {
						$today_seckill_count = $seckill_num;
					}
				} else {
					$today_seckill_count += $seckill_num;
				}
			} elseif ($today != $now_goods['sell_day']) {
				$today_seckill_count = 0;
			}
		} else {
			if ($today == $now_goods['sell_day']) {
				$today_sell_count += $num;
				$today_sell_weight += $weight;
			} else {
				$today_sell_count = $num;
                $today_sell_weight = $weight;
			}
			$sell_count += $total_num;
			$sell_weight += $weight;
			$today_sell_count = max(0, $today_sell_count);
			$today_sell_weight = max(0, $today_sell_weight);

			if ($goods['is_seckill']) {
				if ($now_goods['seckill_type'] == 1) {
					if ($today == $now_goods['sell_day']) {
						$today_seckill_count += $seckill_num;
					} else {
						$today_seckill_count = $seckill_num;
					}
				} else {
					$today_seckill_count += $seckill_num;
				}
			} elseif ($today != $now_goods['sell_day']) {
				$today_seckill_count = 0;
			}
		}
		if ($stock_num >= 0) {
		    if ($stock_num - $num >= 0) {
		        $stock_num -= $num;
		    } else {
		        $stock_num = 0;
		    }
		}
		$goods_stock_num_key = 'goods_stock_'.$goods['goods_id'];
		S($goods_stock_num_key,$stock_num);//加缓存

		if( $stock_weight>0){
            if ($stock_weight - $weight >= 0) {
                $stock_weight -= $weight;
            } else {
                $stock_weight = 0;
            }
        }
		$sell_count = max(0, $sell_count);
		$today_sell_count = max(0, $today_sell_count);
		$today_seckill_count = max(0, $today_seckill_count);

        if (!empty($goods['specs_id'])>0) {
            $mall_periodic_purchase_specs = M('Mall_periodic_purchase_specs')->where(array('specs_id' => $goods['specs_id']))->find();
            if ($mall_periodic_purchase_specs && $total_num>0) {
                M('Mall_periodic_purchase_specs')->where(array('specs_id' => $goods['specs_id']))->setInc('specs_sales', $total_num);
            } elseif ($mall_periodic_purchase_specs && $total_num<0) {
                $specs_sales_num = abs($total_num);
                M('Mall_periodic_purchase_specs')->where(array('specs_id' => $goods['specs_id']))->setInc('specs_sales', $specs_sales_num);
            }
        }

        // 超值换购库存销量处理
        if ($goods['rep_id']>0) {
        	$repurchase_where = array('rep_id' => $goods['rep_id'],'goods_id' => $goods['goods_id']);
            $shop_repurchase_goods = M('Shop_repurchase_goods')->where($repurchase_where)->find();
            if ($shop_repurchase_goods) {
            	if ($shop_repurchase_goods['stock_num'] != -1) {
        			$repurchase_data['stock_num'] = max(0,$shop_repurchase_goods['stock_num']-$total_num);
        			$repurchase_data['sell_count'] = $shop_repurchase_goods['sell_count']+$total_num;
            	}else{
            		$repurchase_data['sell_count'] = $shop_repurchase_goods['sell_count']+$total_num;
            	}

                M('Shop_repurchase_goods')->where($repurchase_where)->save($repurchase_data);
        		fdump(M('Shop_repurchase_goods')->_sql(),'shop_repurchase_goods');
            }
        }

        //限时优惠不更新商品库库存，更新限时优惠活动库存
        if ($goods['is_seckill'] && $goods['sell_type'] == 1){
            //$type 操作类型 0：加销量，减库存，1：加库存，减销量
            if ($goods['spec_id']) {
                //规格
                if ($type == 0) {
                    M('Time_limited_discount_goods_spec')->where(['id' => $goods['limit_id'], 'stock' => ['gt', 0]])->setDec('stock', $goods['num']);
                } else {
                    M('Time_limited_discount_goods_spec')->where(['id' => $goods['limit_id'], 'stock' => ['egt', 0]])->setInc('stock', $goods['num']);
                }
            } else {
                //无规格
                if ($type == 0) {
                    M('Time_limited_discount_goods')->where(['id' => $goods['limit_id'], 'stock' => ['gt', 0]])->setDec('stock', $goods['num']);
                } else {
                    M('Time_limited_discount_goods')->where(['id' => $goods['limit_id'], 'stock' => ['egt', 0]])->setInc('stock', $goods['num']);
                }
            }
        }else{
        	$saveData = [
        		'stock_num' => $stock_num,
        		'stock_weight'=>$stock_weight,
        		'sell_day' => $today,
        		'today_seckill_count' => $today_seckill_count,
        		'sell_count' => $sell_count,
        		'sell_weight' => $sell_weight,
        		'today_sell_count' => $today_sell_count,
        		'today_sell_weight' => $today_sell_weight,
        		'today_sell_spec' => $today_sell_spec ? json_encode($today_sell_spec) : ''
        	];
            fdump_api($saveData,'update_stock',true);
            $this->where(array('goods_id' => $goods['goods_id']))->save($saveData);

			//有赞云商品售出和回滚库存同步
			if ($now_goods['youzanyun_goods_id']) {
				// 规格id
				$now_goods['spec_id'] = $goods['spec_id'];
				$now_goods['today_sell_spec'] = $saveData['today_sell_spec'];

				// if ($type == 0) {//减库存
				// 	$stockNum = $goods['num'] * -1;
				// } else {// 加库存
				// 	$stockNum = $goods['num'];
				// }
				$this->updateYouzanyunStock($now_goods, $shop, $stock_num);
			}
        }

		//淼迈云ERP商品售出和回滚库存同步
//		if ($now_goods['miaomaiyun_g_m_id']) {
//			$syncNum = $type == 0 ? $goods['num'] * -1 : $goods['num'];
//			import("@.ORG.Erp.ErpService");
//			ErpService::updateGoodsStock($now_goods['goods_id'], $syncNum);
//		}
	}

	/*
     * 有赞云商品售出和回滚库存同步
     * @param array $goods 商品信息
     * @param array $shop 店铺信息
     * @param int $stockNum 库存增量
     * $return array
    */
	public function updateYouzanyunStock($goods, $shop, $stockNum){
        $return = [
        	'error'=>1
        ];

        // 没有配置有赞云
		if (!$shop['youzanyun_client_id'] || !$shop['youzanyun_client_secret'] || !$shop['youzanyun_grant_id'] || !$shop['youzanyun_update_stock']) {
			return $return;
		}

        // 非有赞云商品
		if (!$goods['youzanyun_goods_id']) {
			return $return;
		}

		import('@.ORG.youzanyun.SynchronousGoods');
		$synchronousGoods = new SynchronousGoods($shop['youzanyun_client_id'], $shop['youzanyun_client_secret'], $shop['youzanyun_grant_id'], $shop['youzanyun_store_id']);

		if($goods['youzanyun_sku_ids'] && $goods['spec_id']){// 多规格
			// 有赞云规格id
			$youzanyun_sku_ids = json_decode($goods['youzanyun_sku_ids'], true);

			// 获得规格信息
			$list = $this->format_spec_value($goods['spec_value'], $goods['goods_id']);

			// 获取规格当前库存
			$today_sell_spec = json_decode($goods['today_sell_spec'],true);
			if($list['list']){
				$spec = $list['list'][$goods['spec_id']];

				$stockNum = $spec['stock_num'] - $today_sell_spec[$goods['spec_id']];

			}

			// 调用接口更新
			$return = $synchronousGoods->updateStock($goods['youzanyun_goods_id'], $stockNum, $youzanyun_sku_ids[$goods['spec_id']], $spec);
		}else{
			// 调用接口更新
			$return = $synchronousGoods->updateStock($goods['youzanyun_goods_id'], $stockNum);
		}
		return $return;
	}


	public function get_list_by_option($where, $sort, $sort_type = 1, $is_mall = true,$shop_type=1, $in_week = false)
	{
		$order = '';
		if ($sort == 1) {
			$order .= '`g`.`sell_count` DESC, `g`.`price` ASC';
		} elseif ($sort == 2) {
			$order .= '`g`.`sell_count`';
		} elseif ($sort == 3) {
			$order .= '`g`.`price`';
		}elseif($sort==4){	//商品优选排序规则
			$order.='g.index_sort desc,g.sell_count desc,g.goods_id';
		}
		if ($sort != 1) {
			if ($sort_type == 1) {
				$order .= ' DESC';
			} else {
				$order .= ' ASC';
			}
		}
		$condition = '`g`.`status`=1';

		if($is_mall){
			$condition.= ' AND s.mall_on_sale=1';
		}else{
			$condition.= ' AND s.shop_on_sale=1';
		}

		if($sort==4){//优选商品
			$condition .= ' AND `g`.`is_preference`=1';
		}
		$cat_arr = D('Goods_category')->get_category_array();
		$cat_str = implode(',',$cat_arr);

		if (C('customization.life_tools')) {
			$condition .= ' AND (s.mer_id NOT IN (' . C('config.set_employee_card_mer_id') . ') OR g.open_employee_card = 0'; //只显示未开启员工专卖商品
//			$condition .= ' AND (s.mer_id <> ' . C('config.set_employee_card_mer_id') . ' OR g.open_employee_card = 0'; //只显示未开启员工专卖商品
			if (!empty($where['uid'])) {
				$sql1 = "(select ecu.lable_ids from pigcms_employee_card_user as ecu where ecu.uid = " . $where['uid'] . " AND ecu.mer_id = g.employee_mer_id AND ecu.status = 1)";
				$condition .= " OR (concat(',',g.employee_lables,',') regexp concat(',(',replace(" . $sql1 . ",',','|'),'),'))";
		//			$employee_card_user = D('Employee_card_user')->where(['uid' => $where['uid'], 'mer_id' => $now_goods['employee_mer_id'], 'status' => 1])->field('lable_ids')->find();
		//			if (!empty($employee_card_user['lable_ids'])) {
		//				$lable_ids = explode(',', $employee_card_user['lable_ids']);
		//				$condition .= ' OR (';
		//				foreach ($lable_ids as $lk => $lv) {
		//					if ($lk == 0) {
		//						$condition .= 'FIND_IN_SET("' . $lv . '", g.employee_lables)';
		//					} else {
		//						$condition .= ' OR FIND_IN_SET("' . $lv . '", g.employee_lables)';
		//					}
		//				}
		//				$condition .= ')';
		//			}
			}
			$condition .= ')';
		}

		if ($where['cat_fid']) {
			if($where['cat_fid']=='mall_all'){
				$condition .= " AND `g`.`cat_fid` in (".$cat_str.")";
			}else{
				$condition .= " AND `g`.`cat_fid`='{$where['cat_fid']}'";
			}
		}
		if ($where['cat_id']) {
			if($where['cat_fid'] == 0){
				$condition .= " AND `g`.`cat_fid`='{$where['cat_id']}'";
			}else{
				$condition .= " AND `g`.`cat_id`='{$where['cat_id']}'";
			}
		}
		if($shop_type==2){
			if(empty($where['cat_fid']) && empty($where['cat_id'])){
				$condition .= " AND `g`.`cat_fid` in (".$cat_str.")";
			}
		}else{
			//去除只属于商城的商品
        	$condition.=' and g.goods_type!=1';
		}
		if ($where['store_id']) {
			$condition .= " AND `g`.`store_id`='{$where['store_id']}'";
		}

		if ($where['key']) {
			$condition .= " AND `g`.`name` LIKE '%{$where['key']}%'";
		}

		if ($where['goods_ids']) {
			$condition .= ' AND `g`.`goods_id` IN (' . implode(',', $where['goods_ids']) . ')';
		}

		if ($where['store_theme']) {
			$condition .= " AND `sh`.`store_theme` = ' {$where['store_theme']}'";
		}
		if($where['store_status']){
			$condition .= " AND `sh`.`store_status` = ' {$where['store_status']}'";
		}
		if($where['sort_id']){
			$condition .= ' AND `g`.`sort_id` IN (' . implode(',', $where['sort_id'][1]) . ')';
		}
		//所属商户必须是营业的
		$normal_merchant = D('Merchant')->get_all_normal_merchant();
		$condition .= ' AND `s`.`mer_id` IN (' . implode(',', $normal_merchant) . ')';
		if ($where['pids']) {
			$where['pids'] = array_map('intval', $where['pids']);
			$pids = implode(',', $where['pids']);
			$p_sql = "SELECT gid, pid FROM  ". C('DB_PREFIX') . "goods_properties_relation WHERE `pid` IN ({$pids})";
			$list = $this->query($p_sql);
			$pids_arr1 = $pids_arr2 = $pids_arr3 = $pids_arr4 = '';
			$goods_ids = array();
			switch (count($where['pids'])) {
				case 1:
					$pids_arr1 = explode(',', $where['pids'][0]);
					foreach ($list as $vo) {
						if (!in_array($vo['gid'], $goods_ids)) $goods_ids[] = $vo['gid'];
					}
					break;
				case 2:
					$pids_arr1 = explode(',', $where['pids'][0]);
					$pids_arr2 = explode(',', $where['pids'][1]);
					$goods_ids1 = $goods_ids2 = array();
					foreach ($list as $vo) {
						if (in_array($vo['pid'], $pids_arr1)) {
							$goods_ids1[] = $vo['gid'];
						} elseif (in_array($vo['pid'], $pids_arr2)) {
							$goods_ids2[] = $vo['gid'];
						}
					}
					$goods_ids = array_intersect($goods_ids1, $goods_ids2);
					break;
				case 3:
					$pids_arr1 = explode(',', $where['pids'][0]);
					$pids_arr2 = explode(',', $where['pids'][1]);
					$pids_arr3 = explode(',', $where['pids'][2]);
					$goods_ids1 = $goods_ids2 = $goods_ids3 = array();
					foreach ($list as $vo) {
						if (in_array($vo['pid'], $pids_arr1)) {
							$goods_ids1[] = $vo['gid'];
						} elseif (in_array($vo['pid'], $pids_arr2)) {
							$goods_ids2[] = $vo['gid'];
						} elseif (in_array($vo['pid'], $pids_arr3)) {
							$goods_ids3[] = $vo['gid'];
						}
					}
					$goods_ids = array_intersect($goods_ids1, $goods_ids2, $goods_ids3);
					break;
				case 4:
					$pids_arr1 = explode(',', $where['pids'][0]);
					$pids_arr2 = explode(',', $where['pids'][1]);
					$pids_arr3 = explode(',', $where['pids'][2]);
					$pids_arr4 = explode(',', $where['pids'][3]);
					$goods_ids1 = $goods_ids2 = $goods_ids3 = $goods_ids4 = array();
					foreach ($list as $vo) {
						if (in_array($vo['pid'], $pids_arr1)) {
							$goods_ids1[] = $vo['gid'];
						} elseif (in_array($vo['pid'], $pids_arr2)) {
							$goods_ids2[] = $vo['gid'];
						} elseif (in_array($vo['pid'], $pids_arr3)) {
							$goods_ids3[] = $vo['gid'];
						} elseif (in_array($vo['pid'], $pids_arr4)) {
							$goods_ids4[] = $vo['gid'];
						}
					}
					$goods_ids = array_intersect($goods_ids1, $goods_ids2, $goods_ids3, $goods_ids4);
					break;
			}
			if (empty($goods_ids)) {
				return array('goods_list' => null, 'total' => 0, 'next_page' => 0, 'total_page' => 0);
			} else {
				$condition .= ' AND `g`.`goods_id` IN (' . implode(',', $goods_ids) . ')';
			}
		}
		if(C('config.is_show_jd_goods')==0){
		    $condition .= ' AND `g`.`jd_sku_id` = 0 ';
        }
        /*修复指定时间显示商品列表查询数量不一致的bug 2018/12/14*/
        $today=date('Y/m/d');
		$now_time=time();
		$string="(g.show_start_time=g.show_end_time or (g.show_start_time='00:00:00' and g.show_end_time='23:59:00') or (unix_timestamp(concat('".$today."',' ',g.show_start_time))<=".$now_time." and unix_timestamp(concat('".$today."',' ',g.show_end_time))>=".$now_time."))";
		$condition.=' and '.$string;

        if ($in_week) {
            $week = date('w');
            $condition .= " and (sgs.week = '' OR FIND_IN_SET($week,sgs.week))";
        }

/*		$sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `s`.`status`=1 AND `s`.`have_shop`=1 AND `s`.`store_id`=`sh`.`store_id` INNER JOIN " . C('DB_PREFIX') . "shop_goods AS g ON `sh`.`store_id`=`g`.`store_id` AND `s`.`status`=1 inner join ". C('DB_PREFIX') ."shop_goods_sort as sgs on `sgs`.`status`=1 AND `sgs`.`sort_id`=`g`.`sort_id` WHERE {$condition}";*/
        $sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `s`.`status`=1 AND `s`.`store_id`=`sh`.`store_id` INNER JOIN " . C('DB_PREFIX') . "shop_goods AS g ON `sh`.`store_id`=`g`.`store_id` AND `s`.`status`=1 left join ". C('DB_PREFIX') ."shop_goods_sort as sgs on `sgs`.`status`=1 AND `sgs`.`sort_id`=`g`.`sort_id` WHERE {$condition}";
        $count = $this->query($sql_count);

		$total = isset($count[0]['count']) ? intval($count[0]['count']) : 0;

		$page = isset($where['page']) ? intval($where['page']) : 1;
		$pagesize = isset($where['page_size']) ? intval($where['page_size']) : 10;
		$totalPage = ceil($total / $pagesize);
		$star = $pagesize * ($page - 1);
		$return['next_page'] = $totalPage > $page ? intval($page + 1) : 0;
		$return['total_page'] = $totalPage;
		$return['total'] = $total;
		// $field="`g`.`goods_id`,g.store_id,`g`.`sort_id`,`g`.`packing_charge`,`g`.`packing_charge`, `g`.`reply_count`,`g`.`max_num`, `g`.`min_num`, `g`.`name`, `g`.`describe`, `g`.`show_start_time`, `g`.`show_end_time`, `g`.`seckill_type`, `g`.`seckill_open_time`, `g`.`last_time`, `g`.`seckill_close_time`, `g`.`price`, `g`.`old_price`, `g`.`seckill_price`, `g`.`unit`, `g`.`image`, `g`.`sell_count` ,`g`.`extra_pay_price`,`g`.`spec_value`,`g`.`jd_sku_id`,`g`.`stock_num`,`g`.`seckill_stock`,`g`.`is_properties`,`g`.`sell_day`,`g`.`today_seckill_count`,`g`.`original_stock`";

		$field="`g`.*,s.name as store_name,s.mer_id,sh.score_mean as store_score_mean,sh.mall_deliver_type,s.logo as store_logo,sm.s_basic_price as mall_s_basic_price";
		//$field="`g`.*";

		//$sql = "SELECT ".$field." FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `s`.`status`=1 AND `s`.`have_shop`=1 AND `s`.`store_id`=`sh`.`store_id` INNER JOIN " . C('DB_PREFIX') . "shop_goods AS g ON `sh`.`store_id`=`g`.`store_id` WHERE {$condition} {$order} LIMIT {$star}, {$pagesize}";

		//$temp_list = $this->query($sql);

		/*$temp_list = $this->field($field)->table(C('DB_PREFIX') ._view('shop_goods'). " as g")
				->join("inner join " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`store_id`=`g`.`store_id`")
				->join("inner join " . C('DB_PREFIX') . "merchant_store_shop as sh on `s`.`status`=1 AND `s`.`have_shop`=1 AND `s`.`store_id`=`sh`.`store_id`")
				->join("left join " . C('DB_PREFIX') . "merchant_store_mall as sm on `s`.`store_id`=`sm`.`store_id`")
				->join("inner join " . C('DB_PREFIX') . "shop_goods_sort as sgs on `sgs`.`sort_id`=`g`.`sort_id`")
				->where($condition)
				->order($order)->limit($star,$pagesize)->select();*/
		if($shop_type==2){//商城商品
			$temp_list = $this->field($field)->table(C('DB_PREFIX') ._view('shop_goods'). " as g")
				->join("inner join " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`store_id`=`g`.`store_id`")
				->join("inner join " . C('DB_PREFIX') . "merchant_store_shop as sh on `s`.`status`=1 AND `s`.`have_mall`=1 AND `s`.`store_id`=`sh`.`store_id`")
				->join("left join " . C('DB_PREFIX') . "merchant_store_mall as sm on `s`.`store_id`=`sm`.`store_id`")
				->join("left join " . C('DB_PREFIX') . "shop_goods_sort as sgs on `sgs`.`sort_id`=`g`.`sort_id` AND `sgs`.`status`=1")
				->where($condition)
				->order($order)->limit($star,$pagesize)->select();
		}else{
			$temp_list = $this->field($field)->table(C('DB_PREFIX') ._view('shop_goods'). " as g")
				->join("inner join " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`store_id`=`g`.`store_id`")
				->join("inner join " . C('DB_PREFIX') . "merchant_store_shop as sh on `s`.`status`=1 AND `s`.`have_shop`=1 AND `s`.`store_id`=`sh`.`store_id`")
				->join("left join " . C('DB_PREFIX') . "merchant_store_mall as sm on `s`.`store_id`=`sm`.`store_id`")
				->join("left join " . C('DB_PREFIX') . "shop_goods_sort as sgs on `sgs`.`sort_id`=`g`.`sort_id` AND `sgs`.`status`=1")
				->where($condition)
				->order($order)->limit($star,$pagesize)->select();
		}
		$goods_image_class = new goods_image();
		$nowtime = time();
		$today = date('Ymd');
        $goods_list = array();
		foreach ($temp_list as $row) {
		    //新增限时显示
		    if (!($row['show_start_time'] == $row['show_end_time'] || ($row['show_start_time'] == '00:00:00' && $row['show_end_time'] == '23:59:00'))) {
		        $st = strtotime(date('Y-m-d') . ' ' . $row['show_start_time']);
		        $et = strtotime(date('Y-m-d') . ' ' . $row['show_end_time']);
		        if (!($st <= $nowtime && $nowtime<= $et)) {
		            continue;
		        }
		    }
			$row['price'] = floatval($row['price']);
            $row['name'] = html_entity_decode($row['name'], ENT_QUOTES);
            $row['product_reply'] = $row['reply_count'];
            $row['stock'] = $row['stock_num'];
            $row['o_price'] = getFormatNumber($row['price']);
            $row['old_price'] = getFormatNumber($row['price']);
            $row['account_money'] = getFormatNumber($row['account_money']);
            $row['account_score'] = getFormatNumber($row['account_score']);
			//积分展示
			$row['score_text'] = '';
			if(C('customization.goods_show_score') && $scorePrice = floatval($row['price'])){
				$scorePercent = D('Percent_rate')->scorePercent('shop', $row['goods_id'], $row['mer_id']);
				$score = bcmul($scorePrice, $scorePercent,2);
				if(C('config.score_round_two')){
					$score = sprintf('%.2f',floatval($score));
				}else{
					$score = round($score);
				}
				floatval($score) > 0 && $row['score_text'] = C('config.score_name').'+'.$score;
			}
            $seckill_record = D('Time_limited_discount_goods')->get_in_progress_record($row['goods_id']);
            if ($seckill_record) {
                //存在限时优惠
                $row['is_seckill_price'] = true;
                if($seckill_record['is_spec'] == 1){
                    $limit_prices = array_column($seckill_record['spec_list'], 'limit_price');
                    $total_stock = array_sum(array_column($seckill_record['spec_list'], 'stock'));
                    sort($limit_prices);
                    $row['price'] = getFormatNumber($limit_prices[0]);
                    $seckill_stock_num = intval($total_stock);
                }else{
                    $row['price'] = getFormatNumber($seckill_record['limit_price']);
                    $seckill_stock_num = $seckill_record['stock'];
                }
                $row['seckill_price'] = getFormatNumber($row['price']);
                $row['seckill_discount'] = round($row['price']/$row['o_price']*10,1);
				$skillSellCount = M('Time_limited_discount_sale_record')->where(['limit_id' => $seckill_record['id']])->sum('num');
				$row['sell_count'] += intval($skillSellCount);
            } else {
                $row['is_seckill_price'] = false;
                $row['price'] = getFormatNumber($row['price']);
                $seckill_stock_num = 0;
            }

            /*if ($row['seckill_type'] == 1) {
                $now_time = date('H:i');
                $open_time = date('H:i', $row['seckill_open_time']);
                $close_time = date('H:i', $row['seckill_close_time']);

                // 秒杀库存的计算
                if ($today == $row['sell_day']) {
                    $seckill_stock_num = $row['seckill_stock'] == - 1 ? - 1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
                } else {
                    $seckill_stock_num = $row['seckill_stock'];
                }
            } else {
                $now_time = time();
                $open_time = $row['seckill_open_time'];
                $close_time = $row['seckill_close_time'];
                $seckill_stock_num = $row['seckill_stock'] == - 1 ? - 1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
            }

			$row['name'] = html_entity_decode($row['name'], ENT_QUOTES);
			$row['product_reply'] = $row['reply_count'];
			$row['stock'] = $row['stock_num'];
			$row['is_seckill_price'] = false;
			$row['o_price'] = getFormatNumber($row['price']);
			$row['old_price'] = getFormatNumber($row['price']);
			if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0 && $seckill_stock_num != 0){
				$row['price'] = getFormatNumber($row['seckill_price']);
				$row['is_seckill_price'] = true;
				$row['seckill_discount'] = round($row['price']/$row['o_price']*10,1);
			} else {
				$row['price'] = getFormatNumber($row['price']);
			}*/
			$row['is_new'] = (($nowtime - $row['add_time']) > 864000) ? 0 : 1;

			if ($is_mall) {
                // $row['url'] = U('Mall/detail', array('goods_id' => $row['goods_id']), true, false, true);
            	$row['url'] = C('config.site_url') . '/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$row['goods_id'];
            } else {
                // $row['url'] = U('LocalShopMall/detail', array('goods_id' => $row['goods_id']), true, false, true);
            	$row['url'] = C('config.site_url') . '/wap.php?g=Wap&c=LocalShopMall&a=detail&goods_id='.$row['goods_id'];
            }
			$row['pcmallurl'] = '/mall/goods/' . $row['goods_id'];
			if ($row['min_num'] > 1) {
			    $row['max_num'] = 0;
			}
			$tmp_pic_arr = explode(';', $row['image']);
			foreach ($tmp_pic_arr as $key => $value) {
			    if('http' === substr($value,0,4)){
					$temp_image = $value;
				}else{
                    $temp_image = $goods_image_class->get_image_by_path($value,0);
                }
				if ($temp_image) {
			        if(is_array($temp_image)){
                        $row['image'] = $temp_image['s_image'];
                        break;
                    }else{
                        $row['image'] = $temp_image;
                        break;
                    }
				}
			}
			//if($row['image']){
				//$row['image'] = thumb_img($row['image'],240,240,'fill');
			//}
			$row['seckill_stock'] = $seckill_stock_num;
			if($row['seckill_stock'] > 0 && $row['is_seckill_price']){
				$row['stock_num'] = $row['seckill_stock'];
			}
			if($row['seckill_stock'] == 0){
				$row['is_seckill_price'] = false;
				$row['price'] = $row['old_price'];
                $row['seckill_discount'] = 10;
			}

			if(!$row['spec_list']){
				if($row['min_num'] > 0 && $row['stock_num'] >= 0 && $row['min_num'] > $row['stock_num']){
					$row['stock_num'] = 0;
					$row['stock'] = 0;
				}
				if($row['min_num'] > 0 && $row['seckill_stock'] >= 0 && $row['min_num'] > $row['seckill_stock']){
					$row['seckill_stock'] = 0;
				}
			}
			$row['store_logo'] = thumb($row['store_logo'], 90, 90, 'fill');

			$show_deliver = false;//商城：是否展示配送费
			if(C('config.open_mall_deliver') && ($row['mall_deliver_type']==0||$row['mall_deliver_type']>5) && $row['mall_s_basic_price']!=null){
				$show_deliver =true;
			}
			$row['show_deliver'] = $show_deliver;
			$row['store_score_mean'] = round($row['store_score_mean'],1);
			if(!$row['store_score_mean']){
				$row['store_score_mean'] = 5;
			}
            $row['mall_s_basic_price'] = intval($row['mall_s_basic_price']);

            $row['shop_video_url'] = $row['shop_video_url']?C('config.site_url').'/'.$row['shop_video_url']:'';

            //消费一元获得积分
			$buy_can_get_score = D('Goods_score')->getGoodsScore($row['price'],'shop',$row['has_spec']);
			$row['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
			$row['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            
			$goods_list[] = $row;
		}
		$return['goods_list'] = $goods_list;
		return $return;
	}

	//@param int $shop_type 店铺类型 1-快店；2-商城
	public function get_list_by_condition($where, $sort = 1, $sort_type = 1,$shop_type=1)
	{
		$condition = array('status' => 1, 'store_id' => $where['store_id']);
		$where['sort_id'] && $condition['sort_id'] = $where['sort_id'];
		$where['name'] && $condition['name'] = $where['name'];

		if($shop_type==2){
			$condition['cat_fid']=array('gt',0);
		}else{
			//去除只属于商城的商品
        	$condition['goods_type']=array('neq',1);
		}

		$order = 'sort DESC,';
		if ($sort == 1) {
			$order .= 'sell_count';
		} elseif ($sort == 2) {
			$order .= 'price';
		} elseif ($sort == 3) {
			$order .= 'reply_count';
		}
		if ($sort_type == 1) {
			$order .= ' DESC';
		} else {
			$order .= ' ASC';
		}
		if(C('config.is_show_jd_goods')==0){
            $condition['jd_sku_id'] = array('eq',0);
        }
		$count = $this->where($condition)->count();
		$page_size = 10;
		import('@.ORG.new_reply_ajax_page');
		$p = new Page($count, $page_size);


		$goods_image_class = new goods_image();
		$g_list = $this->field(true)->where($condition)->order($order . ',index_sort desc,sort desc, goods_id ASC')->limit($p->firstRow . ',' . $page_size)->select();
		/*elseif ($keyword != '') {
			$g_list = $this->field(true)->where(array('store_id' => $store_id, 'name' => array('like', '%' . $keyword . '%'), 'status' => 1))->order('sort DESC, goods_id ASC')->select();
		} else {
			$g_list = $this->field(true)->where(array('store_id' => $store_id, 'status' => 1))->order('sort DESC, goods_id ASC')->select();
		}*/

		$sort_result = array();
		$nowtime = time();
		$goods_list = array();
        $time_limited_discount_mod = D('Time_limited_discount_goods');
		foreach ($g_list as $row) {
			$row['sell_count'] += $row['mall_virtual_sales'];
		    //新增限时显示
		    if (!($row['show_start_time'] == $row['show_end_time'] || ($row['show_start_time'] == '00:00:00' && $row['show_end_time'] == '23:59:00'))) {
		        $st = strtotime(date('Y-m-d') . ' ' . $row['show_start_time']);
		        $et = strtotime(date('Y-m-d') . ' ' . $row['show_end_time']);
		        if (!($st <= $nowtime && $nowtime <= $et)) {
		            continue;
		        }
		    }

			if ($row['seckill_type'] == 1) {
				$now_time = date('H:i');
				$open_time = date('H:i', $row['seckill_open_time']);
				$close_time = date('H:i', $row['seckill_close_time']);
			} else {
				$now_time = time();
				$open_time = $row['seckill_open_time'];
				$close_time = $row['seckill_close_time'];
			}
			$row['is_seckill_price'] = false;
			$row['o_price'] = floatval($row['price']);
			$row['old_price'] = floatval($row['price']);

            $seckill_record = $time_limited_discount_mod->get_in_progress_record($row['goods_id']);
            if ($seckill_record) {
                //存在限时优惠
                $row['is_seckill_price'] = true;
                if ($seckill_record['is_spec'] == 1) {
                    $limit_prices = array_column($seckill_record['spec_list'], 'limit_price');
                    $total_stock = array_sum(array_column($seckill_record['spec_list'], 'stock'));
                    sort($limit_prices);
                    $row['price'] = isset($limit_prices[0]) ? getFormatNumber($limit_prices[0]) : getFormatNumber($row['o_price']);
                } else {
                    $row['price'] = getFormatNumber($seckill_record['limit_price']);
                }
                $row['seckill_price'] = $row['price'];
            } else {
                $row['is_seckill_price'] = false;
                $row['price'] = getFormatNumber($row['price']);
            }
             $row['sell_count']=$this->get_goods_sale_nums($row['goods_id']);
			/*if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0) {
				$row['price'] = floatval($row['seckill_price']);
				$row['is_seckill_price'] = true;
			} else {
				$row['price'] = floatval($row['price']);
			}*/
			$row['is_new'] = (($nowtime - $row['add_time']) > 864000) ? 0 : 1;
			$row['seckill_price'] = floatval($row['seckill_price']);
			$row['has_format'] = false;
			if ($row['spec_value'] || $row['is_properties']) {
				$row['has_format'] = true;
			}
			$row['has_spec']  = $row['spec_value'] ? true : false; //商品是否有规格

			$row['url'] = U('Mall/detail', array('goods_id' => $row['goods_id']), true, false, true);
			$tmp_pic_arr = explode(';', $row['image']);
			foreach ($tmp_pic_arr as $key => $value) {
			    if('http' === substr($value,0,4)){
					$temp_image = $value;
				}else{
                    $temp_image = $goods_image_class->get_image_by_path($value,0);
                }
				if ($temp_image) {
                    if(is_array($temp_image)){
                        $row['image'] = $temp_image['image'];
                        break;
                    }else{
                        $row['image'] = $temp_image;
                        break;
                    }
				}
			}

			/*新增预售状态*/
            $time = time();
            $where['is_del'] = 0;
            $where['goods_id'] = $row['goods_id'];
            $where['start_time'] = ['ELT',$time];
            $where['end_time'] = ['EGT',$time];
            $where['_string'] = 'stock>0 or stock=-1';
            $res = M('Mall_book_goods')->where($where)->find();
            if($res['id']){
                $row['is_book']=1;
            }else{
                $row['is_book']=0;
            }
			$goods_list[] = $row;
		}

		$return['count'] = $count;
		$return['goods_list']  = $goods_list;
		$return['now']  = $p->nowPage;
		$return['total']  = $p->totalPage;
		return $return;
	}


	/**
	 * 检查库存
	 * @param int $goods_id
	 * @param int $num
	 * @param string $spec_ids = 'id_id'
	 * @param string $stock_type //店铺更新库存的类型 0：每日更新相同的库存 1：库存不会每天自动更新
	 * @return multitype:number string |multitype:number unknown |multitype:number string Ambigous <number, mixed>
	 */
	public function check_stock_list($store_id = 2, $stock_type = 0, $num = 10,$shop_type=1)
	{
		$where=array('store_id' => $store_id, 'status' => 1);
		if($shop_type==2){
			$where['cat_fid']=array('gt',0);
		}else{
			$where['goods_type']=array('neq',1);
		}
		$goods_list = $this->field('`goods_id`,`name`,`price`,`sell_day`,`spec_value`,`is_properties`,`today_sell_spec`,`stock_num`,`number`')->where($where)->select();

		$today = date('Ymd');

		$waring_list = array();
		foreach ($goods_list as $now_goods) {
			$now_goods['sell_day'] = $stock_type ? $today : $now_goods['sell_day'];
			if ($now_goods['spec_value']) {
				$return = $this->format_spec_value($now_goods['spec_value'], $now_goods['goods_id'], $now_goods['is_properties']);
				$list = isset($return['list']) ? $return['list'] : '';
				$today_sell_spec = json_decode($now_goods['today_sell_spec'], true);

				foreach ($list as $key => $value) {

					if ($today == $now_goods['sell_day']) {
						$sell_count = isset($today_sell_spec[$key]) ? intval($today_sell_spec[$key]) : 0;
						$stock_num = $value['stock_num'] == -1 ? -1 : (intval($value['stock_num'] - $sell_count) > 0 ? intval($value['stock_num'] - $sell_count) : 0);
					} else {
						$stock_num = $value['stock_num'];
					}
					$name = $pre = '';
					foreach ($value['spec'] as $spec_val) {
						$name .= $pre . $spec_val['spec_val_name'];
						$pre = ',';
					}
					if ($name) $name = ' (' . $name . ')';
					if ($stock_num != -1 && $stock_num < $num) {
						$waring_list[] = array('goods_id' => $now_goods['goods_id'], 'number' => $value['number'], 'price' => $value['price'], 'stock_num' => $stock_num, 'name' => $now_goods['name'] . $name);
					}
				}


			} else {

				if ($today == $now_goods['sell_day']) {
 					$stock_num = $now_goods['stock_num'];
				} else {
					$stock_num = $now_goods['stock_num'];
				}

				if ($stock_num != -1 && $stock_num < $num) {
					$waring_list[] = array('goods_id' => $now_goods['goods_id'], 'number' => $now_goods['number'], 'price' => $now_goods['price'], 'stock_num' => $stock_num, 'name' => $now_goods['name']);
				}
			}
		}

		return $waring_list;
	}


	public function get_list($store_id, $is_cache = true, $filterGoodsWhere = [],$filterSortWhere=[])
	{
		if ($is_cache) {
			$s_list = S('shop_goods_by_storeid_' . $store_id);
		} else {
			$s_list = null;
		}
		if ($s_list) return $s_list;

		$database_goods_sort = D('Shop_goods_sort');
		$condition_goods_sort['store_id'] = $store_id;
		$condition_goods_sort['status'] = '1';
        if($filterSortWhere){
            $condition_goods_sort = array_merge($condition_goods_sort,$filterSortWhere);
        }
		$sort_list = D(_view('Shop_goods_sort'))->field(true)->where($condition_goods_sort)->order('`sort` DESC,`sort_id` ASC')->select();
		$s_list = array();
		$today = date('w');
		foreach ($sort_list as $value) {
			if ($value['week']) {
				$week_arr = explode(',', $value['week']);
				if (!in_array($today, $week_arr)) {
					continue;
				}
				$week_str = '';
				foreach ($week_arr as $k => $v){
					$week_str .= $this->get_week($v) . ' ';
				}
				$value['week_str'] = $week_str;
			}

			$s_list[$value['sort_id']] = $value;
		}

		$where = $filterGoodsWhere ?: array();
		$where['store_id']=$store_id;

		//去除只属于商城的商品
        $where['goods_type']=array('neq',1);

		$g_list = $this->field(true)->where($where)->order('`status` DESC,`sort` DESC, `goods_id` ASC')->select();

		$today = date('Ymd');
		$store_shop = D("Merchant_store_shop")->field('stock_type')->where(array('store_id' => $store_id))->find();
		$stock_type = intval($store_shop['stock_type']);
		$timeNow = time();
		$goods_image_class = new goods_image();
		foreach ($g_list as $row) {
		    //新增限时显示
		    if (!($row['show_start_time'] == $row['show_end_time'] || ($row['show_start_time'] == '00:00:00' && $row['show_end_time'] == '23:59:00'))) {
		        $st = strtotime(date('Y-m-d') . ' ' . $row['show_start_time']);
		        $et = strtotime(date('Y-m-d') . ' ' . $row['show_end_time']);
		        if (!($st <= $timeNow && $timeNow <= $et)) {
		            continue;
		        }
		    }
			$row['sell_day'] = $stock_type ? $today : $row['sell_day'];

			$temp_goods = array('name' => trim($row['name']), 'goods_id' => $row['goods_id'], 'goods_id_real' => $row['goods_id']);
			$temp_goods['seckill_type'] = $row['seckill_type'];

			if ($row['seckill_type'] == 1) {
				$now_time = date('H:i');
				$open_time = date('H:i', $row['seckill_open_time']);
				$close_time = date('H:i', $row['seckill_close_time']);
			} else {
				$now_time = time();
				$open_time = $row['seckill_open_time'];
				$close_time = $row['seckill_close_time'];
			}
			$temp_goods['open_time'] = $open_time;
			$temp_goods['close_time'] = $close_time;
			$temp_goods['price'] = floatval($row['price']);
			$temp_goods['seckill_price'] = floatval($row['seckill_price']);
			$temp_goods['number'] = trim($row['number']);
			$temp_goods['unit'] = $row['unit'];

			$temp_goods['sell_type'] = $row['sell_type'];
			$temp_goods['unit_price'] = $row['unit_price'];
			$temp_goods['goods_weight'] = $row['weight_unit']==2 ? $row['weight'] * 1000 : $row['weight'];//商品重量


			$temp_goods['sort_id'] = $row['sort_id'];
			if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0) {
				$temp_goods['is_seckill_price'] = true;
			} else {
				$temp_goods['is_seckill_price'] = false;
			}
			if ($today == $row['sell_day']) {
// 				$stock_num = $row['stock_num'] == -1 ? -1 : (intval($row['stock_num'] - $row['today_sell_count']) > 0 ? intval($row['stock_num'] - $row['today_sell_count']) : 0);
				$stock_num = $row['stock_num'];
                $stock_weight = $row['stock_weight'];
			} else {
				$stock_num = $row['original_stock'];
                $stock_weight = $row['original_weight'];
			}
			$temp_goods['stock_num'] = $stock_num;
            $temp_goods['stock_weight'] = $stock_weight;//库存重量
			//商品图片
			$tmp_pic_arr = $goods_image_class->get_allImage_by_path($row['image'], 's');
			$temp_goods['image'] = thumb_img($tmp_pic_arr[0],240,240,'fill');
			$temp_goods['status'] = $row['status'];
			$return = $this->format_spec_value($row['spec_value'], $row['goods_id'], $row['is_properties']);
			if (isset($return['list'])) {
				$today_sell_spec = json_decode($row['today_sell_spec'], true);
				foreach ($return['list'] as $id_key => $spec_goods) {
					$temp_goods['goods_id'] = $row['goods_id'] . '_' . $id_key;
					$temp_goods['price'] = floatval($spec_goods['price']);
					$temp_goods['seckill_price'] = floatval($spec_goods['seckill_price']);
					$temp_goods['number'] = trim($spec_goods['number']);

					if ($today == $row['sell_day']) {
						$sell_count = isset($today_sell_spec[$id_key]) ? intval($today_sell_spec[$id_key]) : 0;
						$stock_num = $spec_goods['stock_num'] == -1 ? -1 : (intval($spec_goods['stock_num'] - $sell_count) > 0 ? intval($spec_goods['stock_num'] - $sell_count) : 0);
					} else {
						$stock_num = $spec_goods['stock_num'];
					}

					$temp_goods['stock_num'] = $stock_num;
					$t_names = array();
					$temp_goods['spec_arr'] = $spec_goods['spec'];
					foreach ($spec_goods['spec'] as $spec_row) {
						$t_names[] = $spec_row['spec_val_name'];
					}
					$temp_goods['name'] = trim($row['name']) . '(' . implode(',', $t_names) . ')';

					if (isset($s_list[$row['sort_id']])) {
						if (isset($s_list[$row['sort_id']]['goods_list'])) {
							$s_list[$row['sort_id']]['goods_list'][] = $temp_goods;
						} else {
							$s_list[$row['sort_id']]['goods_list'] = array($temp_goods);
						}
					}
				}
			} else {
				if (isset($s_list[$row['sort_id']])) {
					if (isset($s_list[$row['sort_id']]['goods_list'])) {
						$s_list[$row['sort_id']]['goods_list'][] = $temp_goods;
					} else {
						$s_list[$row['sort_id']]['goods_list'] = array($temp_goods);
					}
				}
			}
		}

		foreach ($s_list as $k => $r) {
			if (!isset($r['goods_list'])) {
				unset($s_list[$k]);
			}
		}
		$s_list = sortArray($s_list,'sort',SORT_DESC);
		S('shop_goods_by_storeid_' . $store_id, $s_list);
		return $s_list;
    }

    public function checkCartOld($store_id, $uid, $goodsData, $isCookie = 1, $address_id = 0, $is_market = false)
    {
        $store = D("Merchant_store")->field(true)->where(array('store_id' => $store_id))->find();
        if ($store['have_shop'] == 0 || $store['status'] != 1) {
            return array('error_code' => true, 'msg' => '商家已经关闭了该业务,不能下单了!');
        }
        if (C('config.store_shop_auth') == 1 && $store['auth'] < 3) {
            return array('error_code' => true, 'msg' => L_("您查看的X1没有通过资质审核！",array("X1" => C('config.shop_alias_name'))));
        }

        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($store['pic_info']);
        $store['images'] = $images ? array_shift($images) : '';

        $store_shop = D("Merchant_store_shop")->field(true)->where(array('store_id' => $store_id))->find();
        if (empty($store) || empty($store_shop)) {
            return array('error_code' => true, 'msg' => L_('店铺信息有误'));
        }

		//检测营业时间 开始
		$is_open = 0;
		 if (D('Merchant_store_shop')->checkTime($store) || $store_shop['advance_day'] > 0) {
            $is_open = 1;
        }
        if ($is_open == 0) {
            return array('error_code' => true, 'msg' => L_('欢迎预定,此刻店内休息（营业时间: X1）',D('Merchant_store_shop')->getBuniessName($store)));
        }
        if ($store_shop['close_reason'] == '') {
            $close_reason = L_('店铺临时关闭');
        } else {
            $close_reason = L_('店铺临时关闭： ') . $store_shop['close_reason'];
        }
        if ($store_shop['is_close'] == 1) {
            return array('error_code' => true, 'msg' => $close_reason);
        }
		//检测营业时间 结束

        $store = array_merge($store, $store_shop);
        $mer_id = $store['mer_id'];

        if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
            $store['extra_price'] = floatval($store['s_extra_price']) ? floatval($store['s_extra_price']) : floatval(C('config.extra_price'));
        } else {
            $store['extra_price'] = floatval($store['extra_price']);//配送附加费
        }
        $store['delivery_range_polygon'] = substr($store['delivery_range_polygon'], 9, strlen($store['delivery_range_polygon']) - 11);
        $lngLatData = explode(',', $store['delivery_range_polygon']);
        array_pop($lngLatData);
        $lngLats = array();
        foreach ($lngLatData as $lnglat) {
            $lng_lat = explode(' ', $lnglat);
            $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
        }
        $store['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
        //用户的VIP折扣率
        $vip_discount = 100;
        $is_discount = 0;
        //店铺设置的vip等级折扣率
        $storeShopLevel = !empty($store_shop['leveloff']) ? unserialize($store_shop['leveloff']) : '';
        $user = M('User')->field(true)->where(array('uid' => $uid))->find();
        if ($storeShopLevel && $user) {
            if ($user['level']) {
                //系统设置的用户等级
                $tmpArr = M(_view('User_level'))->field(true)->order('`id` ASC')->select();
                $levelArray = array();
                foreach ($tmpArr as $vv) {
                    $levelArray[$vv['level']] = $vv;
                }
                if (isset($storeShopLevel[$user['level']]) && isset($levelArray[$user['level']])) {
                    $levelOff = $storeShopLevel[$user['level']];
                    if ($levelOff['type'] == 1) {
                        $vip_discount = $levelOff['vv'];
                    }
                }
            }
        }

        $goods = array();
        $price = 0;//原始总价
        $total = 0;//商品总数
        $extra_price = 0;//额外价格的总价
        $packing_charge = 0;//打包费
        //店铺优惠条件
        $sorts_discout = D('Shop_goods_sort')->get_sorts_discount($store_id);
        $store_discount_money = 0;//店铺折扣后的总价
        $noDiscountGoods = '';
        $useGoods = array();
        $useCouponMeny = 0;
        if ($isCookie == 0) {
            foreach ($goodsData as $row) {
                $goods_id = $row['goods_id'];


                $num = $row['num'];
                if ($num < 1) continue;
                $t_return = $this->check_stock($goods_id, $num, $row['spec_id'], $store_shop['stock_type'], $store_id, false, $uid);
                if ($t_return['status'] == 0) {
                    continue;
                } elseif ($t_return['status'] == 2) {
                    continue;
                }
                $total += $num;

                if ($t_return['is_seckill_price']) {
                    $is_discount = 1;
                }

                $oldNum = 0;
                $discountNum = 0;
                if ($t_return['limit_type'] == 0 && $t_return['maxNum'] > 0) {
                    if (isset($useGoods[$t_return['goods_id']])) {
                        $useNum = $useGoods[$t_return['goods_id']];
                        $discountNum = max(0, $t_return['maxNum'] - $useNum);
                        $useGoods[$t_return['goods_id']] += $discountNum;
                    } else {
                        $discountNum = $t_return['maxNum'];
                        $useGoods[$t_return['goods_id']] = $t_return['maxNum'];
                    }
                    if ($num > $discountNum) {
                        $oldNum = $num - $discountNum;
                    }
                    if ($oldNum > 0) {
                        $price += floatval($t_return['old_price'] * $oldNum);
                    }
                    if ($discountNum > 0) {
                        $price += floatval($t_return['price'] * $discountNum);
                    }
                } else {
                    $price += floatval($t_return['price'] * $num);
                }

                $extra_price += $row['extra_price'] * $num;
                $packing_charge += $t_return['packing_charge'] * $num;

                //折扣($sorts_discout[$t_return['sort_id']]['discount_type'] == 1 ? '分类折扣' : '店铺折扣')
                $t_discount = isset($sorts_discout[$t_return['sort_id']]['discount']) && $sorts_discout[$t_return['sort_id']]['discount'] ? $sorts_discout[$t_return['sort_id']]['discount'] : 100;

                //该商品的折扣类型 0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                $discount_type = 0;
                //折扣率 0：无折扣
                $discount_rate = 0;
                if ($t_return['is_discount'] == 0) {
                    if ($t_discount < 100) {
                        $noDiscountGoods .= L_("【X1】",array("X1" => $t_return['name']));
                    }
                    $t_discount = 100;
                }
                if ($t_discount < 100) {
                    if ($sorts_discout[$t_return['sort_id']]['discount_type']) {//分类折扣
                        $discount_type = 2;
                    } else {
                        $discount_type = 1;
                    }
                    $discount_rate = $t_discount;
                    $is_discount = 1;
                }

                if ($oldNum > 0) {
                    $num = $oldNum;
                    $tempPrice = $t_return['old_price'];
                    $this_goods_total_price = $num * round($tempPrice * $t_discount * 0.01, 2);//本商品的折扣总价
                    $only_discount_price = round($tempPrice * $t_discount * 0.01, 2);
                    if ($sorts_discout['discount_type'] == 0) {//折上折
                        if ($vip_discount < 100) {
                            $is_discount = 1;
                            $discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
                            $discount_rate = $discount_rate ? $discount_rate . ',' . $vip_discount : $vip_discount;
                        }
                        $this_goods_total_price = round($this_goods_total_price * $vip_discount * 0.01, 2);
                        $only_discount_price = round($only_discount_price * $vip_discount * 0.01, 2);
                    } else {//折扣最优
                        $t_vip_price = $num * round($tempPrice * $vip_discount * 0.01, 2);
                        if ($t_vip_price < $this_goods_total_price) {
                            $this_goods_total_price = $t_vip_price;
                            if ($vip_discount < 100) {
                                $is_discount = 1;
                                $discount_type = 3;
                                $discount_rate = $vip_discount;
                            }
                            $only_discount_price = round($tempPrice * $vip_discount * 0.01, 2);
                        }
                    }
                    $store_discount_money += $this_goods_total_price;

                    $str = '';
                    $str_s && $str = implode(',', $str_s);
                    $str_p && $str = $str ? $str . ';' . implode(',', $str_p) : implode(',', $str_p);
                    $goods[] = array(
                        'name' => $row['productName'],
                        'packname' => $row['packname'],
                        'is_seckill_price' => false,//是否是秒杀价(0:否，1：是)
                        'discount_type' => $discount_type,//0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                        'discount_rate' => $discount_rate,//折扣率
                        'num' => $num,
                        'goods_id' => $goods_id,
                        'old_price' => floatval($t_return['old_price']),//商品原始价
                        'price' => floatval($tempPrice),//是秒杀的时候是秒杀价，不是的时候是原始价
                        'discount_price' => floatval($only_discount_price),//折扣价
                        'cost_price' => floatval($t_return['cost_price']),
                        'number' => $t_return['number'],
                        'image' => $t_return['image'],
                        'sort_id' => $t_return['sort_id'],
                        'packing_charge' => $t_return['packing_charge'],
                        'unit' => $t_return['unit'],
                        'str' => $str,
                        'spec_id' => $spec_str,
                        'extra_price' => $row['productExtraPrice'],

                    );
                    $num = $discountNum;
                }
                if ($num > 0) {
                    $this_goods_total_price = $num * round($t_return['price'] * $t_discount * 0.01, 2);//本商品的店铺折扣后的总价
                    $only_discount_price = round($t_return['price'] * $t_discount * 0.01, 2);//本商品的店铺折扣的单价
                    if ($sorts_discout['discount_type'] == 0) {//折上折
                        if ($vip_discount < 100) {
                            $is_discount = 1;
                            $discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
                            $discount_rate = $discount_rate ? $discount_rate . ',' . $vip_discount : $vip_discount;
                        }
                        $this_goods_total_price = round($this_goods_total_price * $vip_discount * 0.01, 2);//本商品的VIP折扣后的总价
                        $only_discount_price = round($only_discount_price * $vip_discount * 0.01, 2);//本商品的VIP折扣的单价
                    } else {//折扣最优
                        $t_vip_price = $num * round($t_return['price'] * $vip_discount * 0.01, 2);
                        if ($t_vip_price < $this_goods_total_price) {
                            $this_goods_total_price = $t_vip_price;

                            if ($vip_discount < 100) {
                                $is_discount = 1;
                                $discount_type = 3;
                                $discount_rate = $vip_discount;
                            }
                            $only_discount_price = round($t_return['price'] * $vip_discount * 0.01, 2);
                        }
                    }

                    $store_discount_money += $this_goods_total_price;//折扣后的商品总价（店铺，分类，VIP折扣都计算在内）

                    $goods[] = array(
                        'name' => $row['name'],
                        'is_seckill_price' => $t_return['is_seckill_price'],//是否是秒杀价(0:否，1：是)
                        'discount_type' => $discount_type,//0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                        'discount_rate' => $discount_rate,//折扣率
                        'num' => $num,
                        'goods_id' => $goods_id,
                        'old_price' => floatval($t_return['old_price']),//商品原始价
                        'price' => floatval($t_return['price']),//
                        'discount_price' => floatval($only_discount_price),//折扣价
                        'cost_price' => floatval($t_return['cost_price']),
                        'number' => $t_return['number'],
                        'image' => $t_return['image'],
                        'sort_id' => $t_return['sort_id'],
                        'packing_charge' => $t_return['packing_charge'],
                        'unit' => $t_return['unit'],
                        'str' => $row['spec'],
                        'spec_id' => $row['spec_id'],
                        'extra_price' => $row['extra_price']
                    );
                }
            }
        } elseif ($isCookie == 1) {

            if ($address_id) {
                $user_adress = D('User_adress')->get_one_adress($uid, $address_id);
                $express_freight = array();
                $delivery_list = D('Express_template')->get_deliver_list($store['mer_id'], $store['store_id']);
                $goods_id_array = array();
                $delivery_money_total = 0;
                $max_freight = 0;
                $template_total_price = 0;
            }

            //处理拼单可能出现每个袋中有相同的商品导致数量超出
            $totalData = array();
            foreach ($goodsData as $row) {
                $goods_id = $row['productId'];
                $num = $row['count'];
                if ($num < 1) continue;
                $spec_ids = array();
                foreach ($row['productParam'] as $r) {
                    if ($r['type'] == 'spec') {
                        $spec_ids[] = $r['id'];
                    }
                }
                $spec_str = $spec_ids ? implode('_', $spec_ids) : '';
                if (isset($totalData[$goods_id . '_' . $spec_str])) {
                    $totalData[$goods_id . '_' . $spec_str] += $num;
                } else {
                    $totalData[$goods_id . '_' . $spec_str] = $num;
                }
            }
            foreach ($goodsData as $row) {
                $goods_id = $row['productId'];
                $num = $row['count'];
                if ($num < 1) continue;
                $spec_ids = array();
                $str_s = array(); $str_p = array();
                foreach ($row['productParam'] as $r) {
                    if ($r['type'] == 'spec') {
                        $spec_ids[] = $r['id'];
                        $str_s[] = $r['name'];
                    } else {
                        foreach ($r['data'] as $d) {
                            $str_p[] = $d['name'];
                        }
                    }
                }
                $spec_str = $spec_ids ? implode('_', $spec_ids) : '';

                $tNum = isset($totalData[$goods_id . '_' . $spec_str]) ? intval($totalData[$goods_id . '_' . $spec_str]) : $num;
                $t_return = $this->check_stock($goods_id, $tNum, $spec_str, $store_shop['stock_type'], $store_id, false, $uid,$row['group_id'],$row['team_id']);

                if ($t_return['status'] == 0) {
                    return array('error_code' => true, 'msg' => $t_return['msg']);
                } elseif ($t_return['status'] == 2) {
                    return array('error_code' => true, 'msg' => $t_return['msg']);
                }
                $total += $num;
                if ($t_return['is_seckill_price']) {
                    $is_discount = 1;
                }

                $oldNum = 0;
                $discountNum = 0;
                if ($t_return['limit_type'] == 0 && $t_return['maxNum'] > 0) {

                    if (isset($useGoods[$t_return['goods_id']])) {
                        $useNum = $useGoods[$t_return['goods_id']];
                        $discountNum = max(0, $t_return['maxNum'] - $useNum);
                        $useGoods[$t_return['goods_id']] += $discountNum;
                    } else {
                        $discountNum = $t_return['maxNum'];
                        $useGoods[$t_return['goods_id']] = $t_return['maxNum'];
                    }

                    if ($num > $discountNum) {
                        $oldNum = $num - $discountNum;
                    }

                    if ($oldNum > 0) {
                        $price += floatval($t_return['old_price'] * $oldNum);
                    }
                    if ($discountNum > 0) {
                        $price += floatval($t_return['price'] * $discountNum);
                    }
                } else {
                    $price += floatval($t_return['price'] * $num);
                }
                $extra_price += $row['productExtraPrice'] * $num;
                $packing_charge += $t_return['packing_charge'] * $num;

                if ($address_id) {
                    //-----计算运费--------  freight_type ==> 0:最大，1：单独
                    if ($t_return['freight_type'] == 0) {//计件：取最大值
                        $template_id = intval($t_return['freight_template']);
                        if ($user_adress) {
                            if (isset($delivery_list[$template_id][$user_adress['city']])) {
                                $express_freight_tmp = $delivery_list[$template_id][$user_adress['city']];
                            } elseif (isset($delivery_list[$template_id][$user_adress['province']])) {
                                $express_freight_tmp = $delivery_list[$template_id][$user_adress['province']];
                            } else {
                                $template_id = 0;
                                $express_freight_tmp = array('freight' => $t_return['freight_value'], 'full_money' => 0, 'tid' => 0);
                            }
                        } else {
                            $template_id = 0;
                            $express_freight_tmp = array('freight' => $t_return['freight_value'], 'full_money' => 0, 'tid' => 0);
                        }
                        if ($max_freight < $express_freight_tmp['freight']) {
                            $express_freight = $express_freight_tmp;
                            $max_freight = $express_freight_tmp['freight'];
                        }
                        $template_total_price += $t_return['price'] * $num;
                    } else {//计件：各个商品运费的叠加总和
                        if (!in_array($goods_id, $goods_id_array)) {
                            $template_id = intval($t_return['freight_template']);
                            if ($user_adress) {
                                if (isset($delivery_list[$template_id][$user_adress['city']])) {
                                    $delivery_money_total += $delivery_list[$template_id][$user_adress['city']]['freight'];
                                } elseif (isset($delivery_list[$template_id][$user_adress['province']])) {
                                    $delivery_money_total += $delivery_list[$template_id][$user_adress['province']]['freight'];
                                } else {
                                    $delivery_money_total += $t_return['freight_value'];
                                }
                            }
                            $goods_id_array[] = $goods_id;
                        }
                    }
                    //-----计算运费--------
                }

                $t_discount = isset($sorts_discout[$t_return['sort_id']]['discount']) && $sorts_discout[$t_return['sort_id']]['discount'] ? $sorts_discout[$t_return['sort_id']]['discount'] : 100;

                $discount_type = 0;
                $discount_rate = 0;
                if ($t_return['is_discount'] == 0) {
                    if ($t_discount < 100) {
                        $noDiscountGoods .= L_("【X1】",array("X1" => $t_return['name']));
                    }
                    $t_discount = 100;
                }
                if ($t_discount < 100) {
                    $is_discount = 1;
                    if ($sorts_discout[$t_return['sort_id']]['discount_type']) {//分类折扣
                        $discount_type = 2;
                    } else {
                        $discount_type = 1;
                    }
                    $discount_rate = $t_discount;
                }

                if ($oldNum > 0) {
                    $num = $oldNum;
                    $tempPrice = $t_return['old_price'];
                    $this_goods_total_price = $num * round($tempPrice * $t_discount * 0.01, 2);//本商品的折扣总价
                    $only_discount_price = round($tempPrice * $t_discount * 0.01, 2);
                    if ($sorts_discout['discount_type'] == 0) {//折上折
                        if ($vip_discount < 100) {
                            $is_discount = 1;
                            $discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
                            $discount_rate = $discount_rate ? $discount_rate . ',' . $vip_discount : $vip_discount;
                        }
                        $this_goods_total_price = round($this_goods_total_price * $vip_discount * 0.01, 2);
                        $only_discount_price = round($only_discount_price * $vip_discount * 0.01, 2);
                    } else {//折扣最优
                        $t_vip_price = $num * round($tempPrice * $vip_discount * 0.01, 2);
                        if ($t_vip_price < $this_goods_total_price) {
                            $this_goods_total_price = $t_vip_price;
                            if ($vip_discount < 100) {
                                $is_discount = 1;
                                $discount_type = 3;
                                $discount_rate = $vip_discount;
                            }
                            $only_discount_price = round($tempPrice * $vip_discount * 0.01, 2);
                        }
                    }
                    $store_discount_money += $this_goods_total_price;

                    $str = '';
                    $str_s && $str = implode(',', $str_s);
                    $str_p && $str = $str ? $str . ';' . implode(',', $str_p) : implode(',', $str_p);
                    $goods[] = array(
                        'name' => $row['productName'],
                        'packname' => $row['packname'],
                        'is_seckill_price' => $t_return['is_seckill_price'],//是否是秒杀价(0:否，1：是)
                        'discount_type' => $discount_type,//0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                        'discount_rate' => $discount_rate,//折扣率
                        'num' => $num,
                        'goods_id' => $goods_id,
                        'old_price' => floatval($t_return['old_price']),//商品原始价
                        'price' => floatval($tempPrice),//是秒杀的时候是秒杀价，不是的时候是原始价
                        'discount_price' => floatval($only_discount_price),//折扣价
                        'cost_price' => floatval($t_return['cost_price']),
                        'number' => $t_return['number'],
                        'image' => $t_return['image'],
                        'sort_id' => $t_return['sort_id'],
                        'packing_charge' => $t_return['packing_charge'],
                        'unit' => $t_return['unit'],
                        'str' => $str,
                        'spec_id' => $spec_str,
                        'extra_price' => $row['productExtraPrice']
                    );
                    $num = $discountNum;
                }
                if ($num > 0) {
                    $this_goods_total_price = $num * round($t_return['price'] * $t_discount * 0.01, 2);//本商品的折扣总价
                    $only_discount_price = round($t_return['price'] * $t_discount * 0.01, 2);
                    if ($sorts_discout['discount_type'] == 0) {//折上折
                        if ($vip_discount < 100) {
                            $is_discount = 1;
                            $discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
                            $discount_rate = $discount_rate ? $discount_rate . ',' . $vip_discount : $vip_discount;
                        }
                        $this_goods_total_price = round($this_goods_total_price * $vip_discount * 0.01, 2);
                        $only_discount_price = round($only_discount_price * $vip_discount * 0.01, 2);
                    } else {//折扣最优
                        $t_vip_price = $num * round($t_return['price'] * $vip_discount * 0.01, 2);
                        if ($t_vip_price < $this_goods_total_price) {
                            $this_goods_total_price = $t_vip_price;
                            if ($vip_discount < 100) {
                                $is_discount = 1;
                                $discount_type = 3;
                                $discount_rate = $vip_discount;
                            }
                            $only_discount_price = round($t_return['price'] * $vip_discount * 0.01, 2);
                        }
                    }
                    $store_discount_money += $this_goods_total_price;

                    $str = '';
                    $str_s && $str = implode(',', $str_s);
                    $str_p && $str = $str ? $str . ';' . implode(',', $str_p) : implode(',', $str_p);
                    $goods[] = array(
                        'name' => $row['productName'],
                        'packname' => $row['packname'],
                        'is_seckill_price' => $t_return['is_seckill_price'],//是否是秒杀价(0:否，1：是)
                        'discount_type' => $discount_type,//0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                        'discount_rate' => $discount_rate,//折扣率
                        'num' => $num,
                        'goods_id' => $goods_id,
                        'old_price' => floatval($t_return['old_price']),//商品原始价
                        'price' => floatval($t_return['price']),//是秒杀的时候是秒杀价，不是的时候是原始价
                        'discount_price' => floatval($only_discount_price),//折扣价
                        'cost_price' => floatval($t_return['cost_price']),
                        'number' => $t_return['number'],
                        'image' => $t_return['image'],
                        'sort_id' => $t_return['sort_id'],
                        'packing_charge' => $t_return['packing_charge'],
                        'unit' => $t_return['unit'],
                        'str' => $str,
                        'spec_id' => $spec_str,
                        'extra_price' => $row['productExtraPrice']
                    );
                }
            }
        } elseif ($isCookie == 2) {
            foreach ($goodsData as $row) {
                $num = $row['num'];
                if ($num < 1) continue;
                $ids = explode('_', $row['goods_id']);
                $goods_id = array_shift($ids);
                $spec_str = $ids ? implode('_', $ids) : '';
                $t_return = $this->check_stock($goods_id, $num, $spec_str, $store_shop['stock_type'], $store_id, $is_market, $uid,$row['group_id'],$row['team_id']);
                //店铺关闭线下零售收取打包费时 将商品的打包费置 0
                if ($is_market && $store['is_open_retail_pack'] == 0) {
                    $t_return['packing_charge'] = 0;
                }
                if (!$is_market) {
                    if ($t_return['status'] == 0) {
                        return array('error_code' => true, 'msg' => $t_return['msg']);
                    } elseif ($t_return['status'] == 2) {
                        return array('error_code' => true, 'msg' => $t_return['msg']);
                    }
                }
                $total += $num;
                if ($t_return['is_seckill_price']) {
                    $is_discount = 1;
                }

                $oldNum = 0;
                $discountNum = 0;
                if ($t_return['limit_type'] == 0 && $t_return['maxNum'] > 0) {
                    if (isset($useGoods[$t_return['goods_id']])) {
                        $useNum = $useGoods[$t_return['goods_id']];
                        $discountNum = max(0, $t_return['maxNum'] - $useNum);
                        $useGoods[$t_return['goods_id']] += $discountNum;
                    } else {
                        $discountNum = $t_return['maxNum'];
                        $useGoods[$t_return['goods_id']] = $t_return['maxNum'];
                    }
                    if ($num > $discountNum) {
                        $oldNum = $num - $discountNum;
                    }
                    if ($oldNum > 0) {
                        $price += floatval($t_return['old_price'] * $oldNum);
                    }
                    if ($discountNum > 0) {
                        $price += floatval($t_return['price'] * $discountNum);
                    }
                } else {
                    $price += floatval($t_return['price'] * $num);
                }

                $extra_price += $row['productExtraPrice'] * $num;
                $packing_charge += $t_return['packing_charge'] * $num;

                $t_discount = isset($sorts_discout[$t_return['sort_id']]['discount']) && $sorts_discout[$t_return['sort_id']]['discount'] ? $sorts_discout[$t_return['sort_id']]['discount'] : 100;

                $discount_type = 0;
                $discount_rate = 0;
                if ($t_return['is_discount'] == 0) {
                    if ($t_discount < 100) {
                        $noDiscountGoods .= L_("【X1】",array("X1" => $t_return['name']));
                    }
                    $t_discount = 100;
                }
                if ($t_discount < 100) {
                    $is_discount = 1;
                    if ($sorts_discout[$t_return['sort_id']]['discount_type']) {//分类折扣
                        $discount_type = 2;
                    } else {
                        $discount_type = 1;
                    }
                    $discount_rate = $t_discount;
                }

                if ($oldNum > 0) {
                    $num = $oldNum;
                    $tempPrice = $t_return['old_price'];
                    $this_goods_total_price = $num * round($tempPrice * $t_discount * 0.01, 2);//本商品的折扣总价
                    $only_discount_price = round($tempPrice * $t_discount * 0.01, 2);
                    if ($sorts_discout['discount_type'] == 0) {//折上折
                        if ($vip_discount < 100) {
                            $is_discount = 1;
                            $discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
                            $discount_rate = $discount_rate ? $discount_rate . ',' . $vip_discount : $vip_discount;
                        }
                        $this_goods_total_price = round($this_goods_total_price * $vip_discount * 0.01, 2);
                        $only_discount_price = round($only_discount_price * $vip_discount * 0.01, 2);
                    } else {//折扣最优
                        $t_vip_price = $num * round($tempPrice * $vip_discount * 0.01, 2);
                        if ($t_vip_price < $this_goods_total_price) {
                            $this_goods_total_price = $t_vip_price;
                            if ($vip_discount < 100) {
                                $is_discount = 1;
                                $discount_type = 3;
                                $discount_rate = $vip_discount;
                            }
                            $only_discount_price = round($tempPrice * $vip_discount * 0.01, 2);
                        }
                    }

                    $store_discount_money += $this_goods_total_price;

                    $str = '';
                    $str_s && $str = implode(',', $str_s);
                    $str_p && $str = $str ? $str . ';' . implode(',', $str_p) : implode(',', $str_p);
                    $goods[] = array(
                        'name' => $row['productName'],
                        'packname' => $row['packname'],
                        'is_seckill_price' => $t_return['is_seckill_price'],//是否是秒杀价(0:否，1：是)
                        'discount_type' => $discount_type,//0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                        'discount_rate' => $discount_rate,//折扣率
                        'num' => $num,
                        'goods_id' => $goods_id,
                        'old_price' => floatval($t_return['old_price']),//商品原始价
                        'price' => floatval($tempPrice),//是秒杀的时候是秒杀价，不是的时候是原始价
                        'discount_price' => floatval($only_discount_price),//折扣价
                        'cost_price' => floatval($t_return['cost_price']),
                        'number' => $t_return['number'],
                        'image' => $t_return['image'],
                        'sort_id' => $t_return['sort_id'],
                        'packing_charge' => $t_return['packing_charge'],
                        'unit' => $t_return['unit'],
                        'str' => $str,
                        'spec_id' => $spec_str,
                        'extra_price' => $row['productExtraPrice']
                    );
                    $num = $discountNum;
                }
                if ($num > 0) {
                    $this_goods_total_price = $num * round($t_return['price'] * $t_discount * 0.01, 2);//本商品的折扣总价
                    $only_discount_price = round($t_return['price'] * $t_discount * 0.01, 2);
                    if ($sorts_discout['discount_type'] == 0) {//折上折
                        if ($vip_discount < 100) {
                            $is_discount = 1;
                            $discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
                            $discount_rate = $discount_rate ? $discount_rate . ',' . $vip_discount : $vip_discount;
                        }
                        $this_goods_total_price = round($this_goods_total_price * $vip_discount * 0.01, 2);
                        $only_discount_price = round($only_discount_price * $vip_discount * 0.01, 2);
                    } else {//折扣最优
                        $t_vip_price = $num * round($t_return['price'] * $vip_discount * 0.01, 2);
                        if ($t_vip_price < $this_goods_total_price) {
                            $this_goods_total_price = $t_vip_price;
                            if ($vip_discount < 100) {
                                $is_discount = 1;
                                $discount_type = 3;
                                $discount_rate = $vip_discount;
                            }
                            $only_discount_price = round($t_return['price'] * $vip_discount * 0.01, 2);
                        }
                    }
                    $store_discount_money += $this_goods_total_price;

                    $str = str_replace(array($t_return['name'], '(', ')'), '', $row['name']);
                    $goods[] = array(
                        'name' => $t_return['name'],
                        'is_seckill_price' => $t_return['is_seckill_price'],
                        'discount_type' => $discount_type,//0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                        'discount_rate' => $discount_rate,//折扣率
                        'num' => $num,
                        'goods_id' => $goods_id,
                        'old_price' => floatval($t_return['old_price']),//商品原始价
                        'price' => floatval($t_return['price']),
                        'discount_price' => floatval($only_discount_price),//折扣价
                        'cost_price' => floatval($t_return['cost_price']),
                        'number' => $t_return['number'],
                        'image' => $t_return['image'],
                        'sort_id' => $t_return['sort_id'],
                        'packing_charge' => $t_return['packing_charge'],
                        'unit' => $t_return['unit'],
                        'str' => $str,
                        'spec_id' => $spec_str,
                        'extra_price'=> 0
                    );
                }
            }
        }

        $minus_price = 0;
        //会员等级优惠  外卖费不参加优惠
        $vip_discount_money = round($store_discount_money, 2);

//         $discounts = D('Shop_discount')->get_discount_byids(array($store_id));
        $discounts = D('Shop_discount')->getDiscounts($store['mer_id'], $store_id);
        $discount_list = null;

        //优惠
        $sys_first_reduce = 0;//平台首单优惠
        $sto_first_reduce = 0;//店铺首单优惠
        $sys_full_reduce = 0;//平台满减
        $sto_full_reduce = 0;//店铺满减

        $platform_merchant = 0;//平台优惠中商家补贴的总和统计
        $platform_plat = 0;//平台优惠中平台补贴的总和统计

        $shopOrderDB = D("Shop_order");
        $noDiscountList = array();
        $sys_count = $shopOrderDB->where(array('uid' => $uid))->count();
        if (empty($sys_count) && $uid) {//平台首单优惠
            if ($d_tmp = $this->getReduce($discounts, 0, $vip_discount_money, 0, $is_discount)) {
                $dd_tmp['discount_type'] = 1;//平台首单
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['id'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $discount_list['system_newuser'] = $dd_tmp;
                if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                    $sys_first_reduce += $d_tmp['plat_money'];
                    $platform_plat += $d_tmp['plat_money'];
                    $sto_first_reduce += $d_tmp['merchant_money'];
                    $platform_merchant += $d_tmp['merchant_money'];
                } else {
                    $sys_first_reduce += $d_tmp['reduce_money'];
                    $platform_plat += $d_tmp['reduce_money'];
                }
            }

            if ($d_tmp = $this->getNoShareReduce($discounts, 0, $vip_discount_money, 0, $is_discount)) {
                foreach ($d_tmp as $dt) {
                    if ($dt['is_share'] == 0) {
                        $noDiscountList[] = array('type' => 1, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                    }
                }
            }
        }

        if ($uid && ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, 0, $is_discount))) {
            $dd_tmp['discount_type'] = 2;//平台满减
            $dd_tmp['money'] = $d_tmp['full_money'];
            $dd_tmp['minus'] = $d_tmp['reduce_money'];
            $dd_tmp['did'] = $d_tmp['id'];
            $dd_tmp['plat_money'] = $d_tmp['plat_money'];
            $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
            $discount_list['system_minus'] = $dd_tmp;
            if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                $sys_full_reduce += $d_tmp['plat_money'];
                $platform_plat += $d_tmp['plat_money'];
                $sto_full_reduce += $d_tmp['merchant_money'];
                $platform_merchant += $d_tmp['merchant_money'];
            } else {
                $sys_full_reduce += $d_tmp['reduce_money'];
                $platform_plat += $d_tmp['reduce_money'];
            }
        }
        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, 0, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 2, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                }
            }
        }

        $sto_count = $shopOrderDB->where(array('uid' => $uid, 'store_id' => $store_id))->count();

        if (empty($sto_count)) {
            if ($d_tmp = $this->getReduce($discounts, 0, $vip_discount_money, $store_id, $is_discount)) {
                $dd_tmp['discount_type'] = 3;//店铺首单
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['id'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $discount_list['newuser'] = $dd_tmp;
                $sto_first_reduce += $d_tmp['reduce_money'];
            }
            if ($d_tmp = $this->getNoShareReduce($discounts, 0, $vip_discount_money, $store_id, $is_discount)) {
                foreach ($d_tmp as $dt) {
                    if ($dt['is_share'] == 0) {
                        $noDiscountList[] = array('type' => 3, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                    }
                }
            }
        }
        if ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, $store_id, $is_discount)) {
            $dd_tmp['discount_type'] = 4;//店铺满减
            $dd_tmp['money'] = $d_tmp['full_money'];
            $dd_tmp['minus'] = $d_tmp['reduce_money'];
            $dd_tmp['did'] = $d_tmp['id'];
            $dd_tmp['plat_money'] = $d_tmp['plat_money'];
            $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
            $discount_list['minus'] = $dd_tmp;
            $sto_full_reduce += $d_tmp['reduce_money'];
        }
        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, $store_id, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 4, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                }
            }
        }

        //起步运费
        $delivery_fee = 0;
        //超出距离部分的单价
        $per_km_price = 0;
        //起步距离
        $basic_distance = 0;
        //减免配送费的金额
        $delivery_fee_reduce = 0;

        $plat_reduce_deliver_money = 0;
        $merchant_reduce_deliver_money = 0;

        //起步运费
        $delivery_fee2 = 0;
        //超出距离部分的单价
        $per_km_price2 = 0;
        //起步距离
        $basic_distance2 = 0;

        $deliverReturn = D('Deliver_set')->getDeliverInfo($store, $price);
        $delivery_fee = $deliverReturn['delivery_fee'];
        $per_km_price = $deliverReturn['per_km_price'];
        $basic_distance = $deliverReturn['basic_distance'];
        $delivery_fee2 = $deliverReturn['delivery_fee2'];
        $per_km_price2 = $deliverReturn['per_km_price2'];
        $basic_distance2 = $deliverReturn['basic_distance2'];
        $store['delivertime_start'] = $deliverReturn['delivertime_start'];
        $store['delivertime_stop'] = $deliverReturn['delivertime_stop'];
        $store['delivertime_start2'] = $deliverReturn['delivertime_start2'];
        $store['delivertime_stop2'] = $deliverReturn['delivertime_stop2'];

        if ($store_shop['deliver_type'] == 0 || $store_shop['deliver_type'] == 3) {//平台配送|平台或自提
            $store['basic_price'] = floatval($store_shop['s_basic_price']) ? floatval($store_shop['s_basic_price']) : (floatval(C('config.basic_price')) ? floatval(C('config.basic_price')) : floatval($store_shop['basic_price']));
            $store['extra_price'] = floatval($store_shop['s_extra_price']) ? floatval($store_shop['s_extra_price']) : floatval(C('config.extra_price'));
            //使用平台的优惠（配送费的减免）
            if ($d_tmp = $this->getReduce($discounts, 2, $price)) {
                $dd_tmp['discount_type'] = 5;//平台配送费满减
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['id'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $discount_list['delivery'] = $dd_tmp;
                $delivery_fee_reduce = $d_tmp['reduce_money'];
                if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                    $plat_reduce_deliver_money += $d_tmp['plat_money'];
                    $platform_plat += $d_tmp['plat_money'];
                    $merchant_reduce_deliver_money += $d_tmp['merchant_money'];
                    $platform_merchant += $d_tmp['merchant_money'];
                } else {
                    $plat_reduce_deliver_money += $d_tmp['reduce_money'];
                    $platform_plat += $d_tmp['reduce_money'];
                }
            }
        }

        if (empty($goods)) {
            return array('error_code' => true, 'msg' => L_('购物车是空的'));
        } else {
            $data = array('error_code' => false);
            $data['total'] = $total;
            $data['price'] = $price;//商品实际总价
            $data['extra_price'] = $extra_price;//商品实际总价
            $data['discount_price'] = $vip_discount_money;//折扣后的总价
            $data['goods'] = $goods;
            $data['store_id'] = $store_id;
            $data['mer_id'] = $mer_id;
            $data['store'] = $store;
            $data['discount_list'] = $discount_list;

            $data['delivery_type'] = $store_shop['deliver_type'];

            $data['sys_first_reduce'] = $sys_first_reduce;//平台新单优惠的金额
            $data['sys_full_reduce'] = $sys_full_reduce;//平台满减优惠的金额
            $data['sto_first_reduce'] = $sto_first_reduce;//店铺新单优惠的金额
            $data['sto_full_reduce'] = $sto_full_reduce<$price?$sto_full_reduce:$price;//店铺满减优惠的金额

            $data['platform_merchant'] = $platform_merchant;//平台优惠中商家补贴的总和统计
            $data['platform_plat'] = $platform_plat;//平台优惠中平台补贴的总和统计

            $data['store_discount_money'] = $store_discount_money;//店铺折扣后的总价
            $data['vip_discount_money'] = $vip_discount_money;//VIP折扣后的总价
            $data['packing_charge'] = $packing_charge;//总的打包费

            $data['delivery_fee'] = $delivery_fee;//起步配送费
            if ($address_id) {
                $full_money = floatval($express_freight['full_money']);
                if (!($full_money != 0 && $template_total_price >= $full_money)) {
                    $delivery_money_total += $express_freight['freight'];
                }
                $data['delivery_fee'] = $delivery_money_total;//起步配送费
            }

            $data['basic_distance'] = $basic_distance;//起步距离
            $data['per_km_price'] = $per_km_price;//超出起步距离部分的距离每公里的单价
            $data['delivery_fee_reduce'] = $delivery_fee_reduce;//配送费减免的金额

            //平台配送时配送费满减平台和商家各自承担的金额
            $data['plat_reduce_deliver_money'] = $plat_reduce_deliver_money; //平台减免的配送费
            $data['merchant_reduce_deliver_money'] = $merchant_reduce_deliver_money;//商家减免的配送金额

            $data['delivery_fee2'] = $delivery_fee2;//起步配送费
            $data['basic_distance2'] = $basic_distance2;//起步距离
            $data['per_km_price2'] = $per_km_price2;//超出起步距离部分的距离每公里的单价
            $data['userphone'] = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';

            $data['noDiscountList'] = $noDiscountList;
            $data['noDiscountGoods'] = $noDiscountGoods;
            return $data;
        }

    }

    /*计重模式邮费的计算*/
    public function get_goods_weight($info=null,$delivery_list=null,$address,$data=null){
		$goods_id=$info['goods_id'];
    	if(empty($goods_id) || empty($address)){
    		return $data;
    	}
    	//商品信息
    	$goods_weight_info=$data[$goods_id];
    	//当前规格商品的总重量和价格
    	$total_weight=$info['num']*$info['weight'];
    	$total_price=$info['num']*$info['price'];

    	if(isset($goods_weight_info)){
    		$total_weight+=$goods_weight_info['total_weight'];
    		$total_price+=$goods_weight_info['total_price'];
    		$delivery_info=$goods_weight_info['delivery_info'];

    	}
    	else{
    		$template_id=intval($info['freight_template']);	//商品运费模板id
	    	$delivery_arr=$delivery_list[$template_id];
	    	if($delivery_arr['freight_type']==1){
	    		return $data;
	    	}
	    	if(isset($delivery_arr[$address['city']])){
	    		$delivery_info=$delivery_arr[$address['city']];

	    	}elseif(isset($delivery_arr[$address['province']])){
	    		$delivery_info=$delivery_arr[$address['province']];

	    	}else{
	    		$delivery_info=array('first_freight'=>$info['freight_value'],'add_weight'=>0);
	    	}
	    	$goods_weight_info=array();
    		$goods_weight_info['freight_type']=$info['freight_type'];
    		$goods_weight_info['weight_unit']=$info['weight_unit'];
	    	$goods_weight_info['delivery_info']=$delivery_info;
    	}

    	//更新该商品的总重量
    	$goods_weight_info['total_weight']=$total_weight;
    	$goods_weight_info['total_price']=$total_price;
    	$data[$goods_id]=$goods_weight_info;
    	return $data;
    }

    public function getReduce($discounts, $type, $price, $store_id = 0, $is_discount = 1, $order_count = 0, $percentage_status = 0)
    {
        $reduce_money = 0;
        if($percentage_status == 1){
            $reduce_money = 1;
        }

        $return = null;
        if (isset($discounts[$store_id])) {

//            //确保是按照reduce_money正序排列
//            $reduce_money = array_column($discounts[$store_id], 'reduce_money');
//            array_multisort($reduce_money, SORT_ASC, $discounts[$store_id]);

            foreach ($discounts[$store_id] as $row) {
                if ($type == 0) {
					if ($row['order_count'] >= $order_count && $row['percentage_status'] == $percentage_status && ($row['is_share'] || $is_discount == 0)&& ($row['type']==0 || ($row['store_id'] == 0 && $row['order_count'] == 1))) {
                        if ($price >= $row['full_money']) {
                            if ($reduce_money < $row['reduce_money']) {
                                $reduce_money = $row['reduce_money'];
                                $return = $row;
                            }
                        }
                    }
                } else {

	        			fdump($row,'discount',1);
                    if($percentage_status == 1){

                        //折扣
                        if ($row['type'] == $type && $row['order_count'] == 0 && $row['percentage_status'] == $percentage_status && ($row['is_share'] || $is_discount == 0)) {
                            if ($price >= $row['full_money']) {
                                if ($reduce_money > $row['reduce_money']) {
                                    $reduce_money = $row['reduce_money'];
                                    $return = $row;
                                }
                            }
                        }

                    }else{

                        if ($row['type'] == $type && $row['order_count'] == 0 && $row['percentage_status'] == $percentage_status && ($row['is_share'] || $is_discount == 0)) {
                            if ($price >= $row['full_money']) {
                                if ($reduce_money < $row['reduce_money']) {
                                    $reduce_money = $row['reduce_money'];
                                    $return = $row;
                                }
                            }
                        }
                    }

                }
            }
        }
        return $return;
    }

    private function getNoShareReduce($discounts, $type, $price, $store_id = 0, $is_discount = 1, $order_count = 0)
    {
        $return = null;
        if (isset($discounts[$store_id])) {
            foreach ($discounts[$store_id] as $row) {
                if ($type == 0) {
                    if ($row['order_count'] > $order_count && $is_discount) {
                        if ($price >= $row['full_money']) {
                            $return[] = $row;
                        }
                    }
                } else {
                    if ($row['type'] == $type && $is_discount) {
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
     * 经销市场的保存
     * @param array $goods
     * @param int $store_id
     * @return boolean|mixed|boolean|string|unknown
     */
    public function savePostForm($goods, $store_id)
    {
        $original_goods_id = isset($goods['goods_id']) ? intval($goods['goods_id']) : 0;

        $data = array('name' => $goods['name']);
        $data['unit'] = $goods['unit'];
        $data['old_price'] = empty($goods['old_price']) ? $goods['price'] : $goods['old_price'];
        $data['cost_price'] = empty($goods['cost_price']) ? 0 : $goods['cost_price'];
        $data['price'] = $goods['price'];
        $data['extra_pay_price'] = floatval($goods['extra_pay_price']);
        $data['seckill_price'] = $goods['seckill_price'];
        $data['seckill_stock'] = $goods['seckill_stock'];
        $data['seckill_type'] = $goods['seckill_type'];
        $data['seckill_open_time'] = $goods['seckill_open_time'];
        $data['seckill_close_time'] = $goods['seckill_close_time'];
        $data['packing_charge'] = $goods['packing_charge'];
        $data['stock_num'] = $goods['stock_num'];
        $data['sort'] = $goods['sort'];
        $data['status'] = $goods['status'];
        $data['print_id'] = $goods['print_id'];
        $data['sort_id'] = $goods['sort_id'];
        $data['des'] = $goods['des'];
        $data['image'] = $goods['pic'];
        $data['number'] = $goods['number'];
        $data['store_id'] = $store_id;
        $data['original_goods_id'] = $original_goods_id;

        $data['freight_template'] = intval($goods['freight_template']);
        $data['freight_type'] = intval($goods['freight_type']);
        $data['freight_value'] = floatval($goods['freight_value']);

        $data['last_time'] = time();

        //2016-11-08新增系统的商品分类▽
        $data['cat_fid'] = intval($goods['cat_fid']);
        $data['cat_id'] = intval($goods['cat_id']);
        //2016-11-08新增系统的商品分类△

        $delete_spec_ids = array();
        $delete_spec_val_ids = array();
        $delete_properties_ids = array();

        $spec_obj = M('Shop_goods_spec'); //规格表
        $spec_value_obj = M('Shop_goods_spec_value');//规格对应的属性值
        $properties_obj = M('Shop_goods_properties');//属性表

        $relation_map = '';
        if ($check_data = $this->field(true)->where(array('store_id' => $store_id, 'original_goods_id' => $original_goods_id))->find()) {
            $goods_id = $check_data['goods_id'];
            $relation_map = $check_data['relation_map'] ? json_decode($check_data['relation_map'], true) : '';
            if ($this->where(array('store_id' => $store_id, 'goods_id' => $goods_id))->save($data)) {
                //查找已有的属性和规格
//                 $old_spec = $spec_obj->field(true)->where(array('goods_id' => $goods_id, 'store_id' => $store_id))->select();
//                 foreach ($old_spec as $os) {
//                     $delete_spec_ids[] = $os['id'];
//                 }
//                 if ($delete_spec_ids) {
//                     $old_spec_val = $spec_value_obj->field(true)->where(array('sid' => array('in', $delete_spec_ids)))->select();
//                     foreach ($old_spec_val as $osv) {
//                         $delete_spec_val_ids[] = $osv['id'];
//                     }
//                 }
//                 $old_properties = $properties_obj->field(true)->where(array('goods_id' => $goods_id))->select();
//                 foreach ($old_properties as $op) {
//                     $delete_properties_ids[] = $op['id'];
//                 }
//                 unset($old_spec, $old_spec_val, $old_properties);
            } else {
                return false;
            }
        } else {
            $goods_id = $this->add($data);
            if (empty($goods_id)) return false;
        }

        //配置属性
        $properties = array();
        $spec = array();
        $list = array();

        $data_spec = array('store_id' => $store_id, 'goods_id' => $goods_id);
        foreach ($goods['spec_id'] as $k => $id) {//规格id集合
            $id = intval($id);
            $name = $data_spec['name'] = isset($goods['specs'][$k]) ? $goods['specs'][$k] : '';//规格名称
            $spec_val_id = isset($goods['spec_val_id'][$k]) ? $goods['spec_val_id'][$k] : array();//规格属性值的ID集合
            $spec_val = isset($goods['spec_val'][$k]) ? $goods['spec_val'][$k] : array();//规格属性值的名称集合

            $list[$k] = array();

            if (isset($relation_map['spec_id'][$id]) && $relation_map['spec_id'][$id]) {
                $newId = intval($relation_map['spec_id'][$id]);
                if ($spec_obj->field(true)->where(array('id' => $newId, 'goods_id' => $goods_id))->find()) {
                    $spec_obj->where(array('id' => $newId))->save($data_spec);
                } else {
                    $newId = $spec_obj->add($data_spec);
                    $relation_map['spec_id'][$id] = $newId;
                }
            } elseif ($id) {
                $newId = $spec_obj->add($data_spec);
                $relation_map['spec_id'][$id] = $newId;
            } else {
                $newId = $spec_obj->add($data_spec);
            }

            if ($newId) {//规格id
//                 $delete_spec_ids = array_diff($delete_spec_ids, array($id));
                $data_spec_val = array('sid' => $newId);
                foreach ($spec_val_id as $i => $vid) {
                    $vid = intval($vid);
                    $data_spec_val['name'] = $spec_val[$i];
                    if (isset($relation_map['spec_val_id'][$vid]) && $relation_map['spec_val_id'][$vid]) {
                        $newVID = intval($relation_map['spec_val_id'][$vid]);
                        if ($spec_value_obj->where(array('id' => $newVID, 'sid' => $newId))->find()) {
                            $spec_value_obj->where(array('id' => $newVID))->save($data_spec_val);
                        } else {
                            $newVID = $spec_value_obj->add($data_spec_val);
                            $relation_map['spec_val_id'][$vid] = $newVID;
                        }
                    } elseif ($vid) {
                        $newVID = $spec_value_obj->add($data_spec_val);
                        $relation_map['spec_val_id'][$vid] = $newVID;
                    } else {
                        $newVID = $spec_value_obj->add($data_spec_val);
                    }

                    if ($newVID) {
//                      $delete_spec_val_ids = array_diff($delete_spec_val_ids, array($vid));
//                      $list[$k][$i] = array('spec_id' => $id, 'spec_name' => $name, 'spec_val_id' => $vid, 'spec_val_name' => $spec_val[$i]);
                        $list[$k][$i] = $newVID;
                    }
                }
            }
        }
        $spec_value = array();
        $this->format_str($list, 0, array(), $spec_value);

        $properties = array();
        $is_properties = 0;
        foreach ($goods['properties_id'] as $pi => $pid) {//属性id集合
            $pid = intval($pid);
            $is_properties = 1;
            $name = isset($goods['properties'][$pi]) ? $goods['properties'][$pi] : '';//属性名称
            $num = isset($goods['properties_num'][$pi]) ? intval($goods['properties_num'][$pi]) : 1;//属性值可选的数量
            $val = isset($goods['properties_val'][$pi]) ? implode(',', $goods['properties_val'][$pi]) : '';//属性的属性值

            if (isset($relation_map['properties_id'][$pid]) && $relation_map['properties_id'][$pid]) {
                $newPid = intval($relation_map['properties_id'][$pid]);
                if ($properties_obj->field(true)->where(array('goods_id' => $goods_id, 'id' => $newPid))->find()) {
                    $properties_obj->where(array('goods_id' => $goods_id, 'id' => $newPid))->save(array('name' => $name, 'val' => $val, 'num' => $num));
                } else {
                    $newPid = $properties_obj->add(array('goods_id' => $goods_id, 'name' => $name, 'val' => $val, 'num' => $num));
                    $relation_map['properties_id'][$pid] = $newPid;
                }
            } elseif ($pid) {
                $newPid = $properties_obj->add(array('goods_id' => $goods_id, 'name' => $name, 'val' => $val, 'num' => $num));
                $relation_map['properties_id'][$pid] = $newPid;
            } else {
                $newPid = $properties_obj->add(array('goods_id' => $goods_id, 'name' => $name, 'val' => $val, 'num' => $num));
            }

            if ($newPid) {
//                 $delete_properties_ids = array_diff($delete_properties_ids, array($newPid));
                $properties[] = array('id' => $newPid, 'name' => $name, 'val' => $val);
            }
        }

        $specs = '';
        $pre = '';
        foreach ($spec_value as $k => $v) {
            $old_price = isset($goods['old_prices'][$k]) ? $goods['old_prices'][$k] : 0;
            $cost_price = isset($goods['cost_prices'][$k]) ? $goods['cost_prices'][$k] : 0;
            $number = isset($goods['numbers'][$k]) ? $goods['numbers'][$k] : '';
            $price = isset($goods['prices'][$k]) ? $goods['prices'][$k] : 0;
            $seckill_price = isset($goods['seckill_prices'][$k]) ? floatval($goods['seckill_prices'][$k]) : 0;
            $stock_num = isset($goods['stock_nums'][$k]) ? intval($goods['stock_nums'][$k]) : 0;
            $old_price = $old_price ? $old_price : $price;
            $specs .= $pre . $v . '|' . $old_price . ':' . $price . ':' . $seckill_price . ':' . $stock_num . ':' . $cost_price  . '|';
            if ($properties) {
                // 				$specs .= '|';
                $ppre = '';
                foreach ($properties as $ti => $ps) {
                    $num = isset($goods['num' . $ti][$k]) && $goods['num' . $ti][$k] ? intval($goods['num' . $ti][$k]) : 1;
                    $specs .= $ppre . $ps['id'] . '=' . $num;
                    $ppre = ':';
                }
            }
            $number && $specs .= '|' . $number;
            $pre = '#';
        }

        //2016-11-08新增系统的商品分类属性值与商品直接的关系▽
        D('Goods_properties_relation')->where(array('gid' => $goods_id))->delete();
        if (isset($goods['goodsproperties'])) {
            foreach ($goods['goodsproperties'] as $pid) {
                D('Goods_properties_relation')->add(array('gid' => $goods_id, 'pid' => $pid));
            }
        }
        //2016-11-08新增系统的商品分类属性值与商品直接的关系△

        //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号 # 规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#...#规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号
        if ($this->where(array('goods_id' => $goods_id))->save(array('spec_value' => $specs, 'relation_map' => $relation_map ? json_encode($relation_map) : '', 'is_properties' => $is_properties, 'last_time' => $data['last_time'] + 1))) {
//             $delete_spec_ids && $spec_obj->where(array('id' => array('in', $delete_spec_ids)))->delete();
//             $delete_spec_val_ids && $spec_value_obj->where(array('id' => array('in', $delete_spec_val_ids)))->delete();
//             $delete_properties_ids && $properties_obj->where(array('id' => array('in', $delete_properties_ids)))->delete();
            //配置属性
            return $goods_id;
        } else {
            return false;
        }
    }

    public function checkBusinessTime($businessTime, $time, $startTime)
    {
        if (empty($businessTime)) return true;
        if ($time < $startTime) return false;
        foreach ($businessTime as $row) {
            if ($row['s'] <= $time && $time <= $row['e']) {
                return true;
            }
        }
        return false;
    }

	public function checkBusinessTime_new($weekTime, $time, $startTime,$addTime,$last_time_max=0, $selfPick=0, $advance_day){

		if (empty($weekTime)) return false;
		if ($time < $startTime) return false;

		$last_end = strtotime(date("Y-m-d 23:59:59",$last_time_max));
		if($time>$last_end) return false;

		$w = date("w",$time);
		$last_w = date("w",$last_time_max);
		$checkTime = $weekTime[$w];
		if(empty($checkTime)) return false;
		if($selfPick) {//自提需在店铺营业时间段内
			$timeCheck = false;
			foreach($checkTime as $v){
				if ($v['open_time'] <> '00:00:00' && $v['close_time'] <> '00:00:00') {
					$timeStart = strtotime(date("Y-m-d", $time) . ' ' . $v['open_time']);
					$timeEnd = strtotime(date("Y-m-d", $time) . ' ' . $v['close_time']);
					if ($timeStart <= $time && $time < $timeEnd) {
						$timeCheck = true;
					}
				} elseif ($v['open_time'] == '00:00:00' && $v['close_time'] == '00:00:00') {
					$timeCheck = true;
				}
			}
			if(!$timeCheck) {
				return false;
			}
		}
		if($time > strtotime(date('Y-m-d 23:59:59')) + $advance_day*24*3600) {
			return false;
		}
		//如果是最后一天只能取00点开始的时间段(前面的逻辑已经在预约时间的基础上多加了1天，这里是补全跨天的营业时间)
		if(date('Ymd',$time) == date('Ymd',$last_time_max) && $advance_day){
			foreach($checkTime as $k=> $v){
				if ($v['open_time'] <> '00:00:00' || $v['close_time'] == '00:00:00' || date('H', $time) > 0) {
					unset($checkTime[$k]);
				}
			}
		}
		$last_end = strtotime(date('Y-m-d',$last_time_max).' 23:59:59');

		//这里需要判断一下昨天晚上的营业时间（包括配送时间和出单时间）是否跨越了零点的情况【营业时间设置本身没有跨越0点的情况下】
		$zero = strtotime(date('Y-m-d',$time).' '.'00:00:00');
		$today_zero_add = $this->getYerterdayNight($weekTime,$time,$addTime);

		if($time<$zero+$today_zero_add && $today_zero_add){
			return true;
		}
		foreach($checkTime as $key => $val){
			if($val['open_time']==$val['close_time']&&$val['time_sort']==1){//24小时营业
				return true;
			}elseif($val['open_time']=='00:00:00'&&($val['close_time']=='23:59:00'||$val['close_time']=='23:59:59')){//24小时营业
				return true;
			}else{
				$start_time = strtotime(date('Y-m-d',$time).' '.$val['open_time']);
				$end_time = strtotime(date('Y-m-d',$time).' '.$val['close_time']);
				
				if($start_time+$addTime<=$time&&$end_time+$addTime>=$time){
					return true;
				}elseif($zero>time()&&$time<$last_end){
					$today_zero_add = $this->getYerterdayNight($weekTime,$time,$addTime);
					if($time<$zero+$today_zero_add){
						return true;
					}
				}
			}
		}
		return false;
	}


	//获取指定time前一天的的最晚关店时间差
	public function getYerterdayNight($weekTime,$time,$addTime){
		$w = date("w",$time);
		if($w==0){
			$last = 6;
		}else{
			$last = $w-1;
		}
		$select_time = $weekTime[$last];
		$time_arr = array();
		if(empty($select_time)) return 0;//前一天不营业
		foreach($select_time as $key => $val){
			if($val['close_time']<>'00:00:00'&&$val['close_time']<>'23:59:59'&&$val['close_time']<>'23:59:00'){//不是营业到24点
				$today_time = strtotime(date('Y-m-d',$time).' '.$val['close_time']);
				$yesterday_time = $today_time-60*60*24;
				$time_arr[] = $yesterday_time;
			}else{//营业到24点
				return $addTime;
			}
		}
		if(!empty($time_arr)){
			$yesterda_night = max($time_arr);
		}else{//理论上不存在这种情况，加个打印，方便出现bug调试
			fdump($select_time,'select_time_error');
		}

		if($yesterda_night){
			$today_zero = strtotime(date('Y-m-d',$time).' '.'00:00:00');
			$ext = $today_zero-$yesterda_night;
			if($ext>$addTime){
				return 0;
			}else{
				return $addTime-$ext;
			}
		}else{
			return 0;
		}
	}

    public function getSelectTime_new($return, $paramTime = null, $n = 0, $second = 900)
    {
    	return $this->getSelectTime($return,$paramTime, $n,$second);
        // 获取每周的营业时间段
        $time_list = D('Merchant_store_open_time')->get_store_open_time($return['store']['mer_id'], $return['store']['store_id'], 0, false);

        // 获取本周剩余的时间
        $time_list_thisweek = array();
        foreach ($time_list as $key => $value) {
            if (date('w') <= $value['week']) {
                $time_list_thisweek[] = $value;
            }
        }
		$return['store']['advance_day'] = $return['store']['advance_day']?$return['store']['advance_day']:1;
        //如果可以提前天数大于一周营业天数 则增加
        if ($return['store']['advance_day'] > get_week_count($time_list_thisweek)) {
            $s = ceil($return['store']['advance_day'] / get_week_count($time_list));
            for ($i = 1; $i <= $s; $i++) {
                $time_list_thisweek = array_merge($time_list_thisweek, $time_list);
            }

            $num = 0;
            foreach ($time_list_thisweek as $key => $value) {
                if ($value['week_status'] == 1) {
                    $num++;
                }
                if ($num == $return['store']['advance_day']) {
                    $b = $key;
                    continue;
                }
            }

            //去除多余天数
            $time_list_thisweek = array_slice($time_list_thisweek, 0, $b + 1);
        }else{
			$time_list_thisweek = array_slice($time_list_thisweek, 0, $return['store']['advance_day'] + 1);
		}

        //获取本周每天的营业时间 (补全时间使用)
        $week_open_time = D('Merchant_store_open_time')->get_one_time_by_store($return['store']['store_id']);

        $datetime_list = array();
        $time = strtotime(date('Y-m-d'));
        foreach ($time_list_thisweek as $key => $value) {
            $datetime = date('Y-m-d', $time);
            if ($value['week_status'] == 1) {
                $return['store']['open_1'] = $value['time_list'][0]['open_time'];
                $return['store']['close_1'] = $value['time_list'][0]['close_time'];
                $return['store']['open_2'] = $value['time_list'][1]['open_time'];
                $return['store']['close_2'] = $value['time_list'][1]['close_time'];
                $return['store']['open_3'] = $value['time_list'][2]['open_time'];
                $return['store']['close_3'] = $value['time_list'][2]['close_time'];

                //可提前多少天预订
                $return['store']['advance_day'] = 0;

                if ($key > 0) {
                    $paramTime = strtotime(date('Y-m-d 00:00:00'));
                }
				$reduce = 0;
				if($time==strtotime(date('Y-m-d'))+86400){//明天
					$reduce = strtotime('23:59:59')-time();//今天剩余时间
				}elseif($time>time()){
					$reduce = -1;
				}
                $datetime_list[$datetime] = $this->getSelectTime($return, $paramTime, $n, $second,$reduce)[date('Y-m-d')];

                //补全时间--------------------------begin------------------------------
                $yesterda_week = $value['week'] - 1;
                if ($value['week'] == 0) {
                    $yesterda_week = 6;
                }

                // 获取昨天的营业时间
                $yesterda = $week_open_time[$yesterda_week];
                // 获取今天的营业时间
                $today = $week_open_time[$value['week']];
                if ($yesterda['week_status'] == 1 AND $today['week_status'] == 1) {
                    $yesterda_time_show = explode('~', end(explode(',', $yesterda['time_show'])));
                    $today_time_show = explode('~', explode(',', $today['time_show'])[0]);

                    if (($yesterda_time_show[1] == '次日00:00' OR $yesterda_time_show[1] == '00:00') AND ($today_time_show[0] == '00:00' OR $today_time_show[0] == '次日00:00')) {
                        if ($second == 900) {
                            $datetime_yesterda = date('Y-m-d', strtotime($datetime) - 86400);//获取前一天

                            //获取增加的秒数
                            $second_num = $this->getSelectTime($return, $paramTime, $n, $second, 1);
                            $stt1 = date('H:i', strtotime(reset($datetime_list[$datetime])['hour_minute']) - $second_num);
                            $stt2 = date('H:i', strtotime(end($datetime_list[$datetime_yesterda])['hour_minute']));
                            if($stt1 <= '00:20' AND $stt2 >= '23:30'){
                                //补当天开始时间
                                $time_sr = '00:00';
                                while ($time_sr < reset($datetime_list[$datetime])['hour_minute']) {
                                    $datetime_list[$datetime][$time_sr]['hour_minute'] = $time_sr;
                                    $datetime_list[$datetime][$time_sr]['show_hour_minute'] = $time_sr . '-' . date('H:i', strtotime($time_sr) + 900);
                                    $datetime_list[$datetime][$time_sr]['time_select'] = 1;
                                    $datetime_list[$datetime][$time_sr]['delivery_fee'] = reset($datetime_list[$datetime])['delivery_fee'];
                                    $datetime_list[$datetime][$time_sr]['delivery_fee_old'] = reset($datetime_list[$datetime])['delivery_fee_old'];
                                    $time_sr = date('H:i', strtotime($time_sr) + 900);
                                }

                                //补前一天结束时间
                                $datetime_list[$datetime_yesterda]['23:45']['hour_minute'] = '23:45';
                                $datetime_list[$datetime_yesterda]['23:45']['show_hour_minute'] = '23:45-00:00';
                                $datetime_list[$datetime_yesterda]['23:45']['time_select'] = 1;
                                $datetime_list[$datetime_yesterda]['23:45']['delivery_fee'] = $datetime_list[$datetime_yesterda]['23:30']['delivery_fee'];
                                $datetime_list[$datetime_yesterda]['23:45']['delivery_fee_old'] = $datetime_list[$datetime_yesterda]['23:30']['delivery_fee_old'];
                            }
                        } else {
                            //补当天开始时间
                            $time_sr = '00:00';
                            while ($time_sr < reset($datetime_list[$datetime])['hour_minute']) {
                                $datetime_list[$datetime][$time_sr]['hour_minute'] = $time_sr;
                                $datetime_list[$datetime][$time_sr]['show_hour_minute'] = $time_sr . '-' . date('H:i', strtotime($time_sr) + 1200);
                                $datetime_list[$datetime][$time_sr]['time_select'] = 1;
                                $time_sr = date('H:i', strtotime($time_sr) + 1200);
                            }

                            //补前一天结束时间
                            $datetime = date('Y-m-d', strtotime($datetime) - 86400);//获取前一天
                            $datetime_list[$datetime]['23:45']['hour_minute'] = '23:40';
                            $datetime_list[$datetime]['23:45']['show_hour_minute'] = '23:40-00:00';
                            $datetime_list[$datetime]['23:45']['time_select'] = 1;
                        }
                    }
                }
                //补全时间--------------------------end------------------------------

            } else {
                $datetime_list[$datetime] = array();
            }
            $time += 86400;
        }
        return $datetime_list;
    }



    public function getSelectTime($return, $paramTime = null, $n = 0, $second = 900, $return_s = 0,$reduce=0)
    {
        if ($paramTime === null) {
            $paramTime = time();
        }
        //预订的天数
        $advance_day = $return['store']['advance_day'];

        //配送时间段
        $start_time = $return['store']['delivertime_start'];
        $stop_time = $return['store']['delivertime_stop'];
        $start_time2 = $return['store']['delivertime_start2'];
        $stop_time2 = $return['store']['delivertime_stop2'];
        $start_time3 = $return['store']['delivertime_start3'];
        $stop_time3 = $return['store']['delivertime_stop3'];

        if ($start_time == $stop_time && $start_time == '00:00:00') {//时间段一，24小时
            $start_time = '00:00:00';
            $stop_time = '23:59:59';
        } elseif ($start_time2 == $stop_time2 && $start_time2 == '00:00:00') {//没有时间段二
            $stop_time2 = $start_time2 = 0;
        }
        if ($start_time3 == $stop_time3 && $start_time3 == '00:00:00') {//没有时间段三
            $stop_time3 = $start_time3 = 0;
        }

        //不受配送时间限制
        if($n){
            $start_time = '00:00:00';
            $stop_time = '23:59:59';
            $stop_time2 = $start_time2 = 0;
            $stop_time3 = $start_time3 = 0;
        }

        //出单时长的单位
        $diffTime = 60;
        if($return['order_type']==2){
        	$type_key = 'mall_send_time_type';
        	$return['store']['work_time'] = $return['store']['mall_work_time'];
        }else{
        	$type_key = 'send_time_type';
        }
        if ($return['store'][$type_key] == 1) {
            $diffTime = 3600;
        } elseif ($return['store'][$type_key] == 2) {
            $diffTime = 86400;
        } elseif ($return['store'][$type_key] == 3) {
            $diffTime = 86400 * 7;
        } elseif ($return['store'][$type_key] == 4) {
            $diffTime = 86400 * 30;
        }
        if ($n == 1) {
            $dateList = [];
            $workSecond = $return['store']['work_time'] * $diffTime;
            $tm = time();
            $storeOpenTime = D('Merchant_store_open_time')->get_time_by_week($return['store']['store_id']);
            $firstTime = $tm + $workSecond;
            for ($day = 0; $day <= $advance_day; $day++) {
                $thisUnix = $tm + 86400 * $day + $workSecond;
                $thisWeek = date('w', $thisUnix);
                $thisWeekOpenTime = $storeOpenTime[$thisWeek] ?? [];
                if (empty($thisWeekOpenTime)) {
                    continue;
                }
                foreach ($thisWeekOpenTime as $v) {
                    if ($v['open_time'] == '00:00:00' && $v['close_time'] == '00:00:00') {
                        $v['close_time'] = '23:59:59';
                    }
                    $nowOpenTime = strtotime($v['open_time']) + 86400 * $day;
                    $nowCloseTime = strtotime($v['close_time']) + 86400 * $day;
                    while ($nowOpenTime <= $nowCloseTime) {

                        if( $nowOpenTime > $firstTime){
                            $dateList[date('Y-m-d', $thisUnix)][date('H:i', $nowOpenTime)] = array(
                                'hour_minute' => date('H:i', $nowOpenTime),
                                'time_select' => 1,
                                'show_hour_minute' => date('H:i', $nowOpenTime) . '-' . date('H:i', $nowOpenTime + $second),
                                'delivery_fee_old' => 0,
                                'delivery_fee' => 0,
                                'first_asap' => false,
                                'unix_time' => $nowOpenTime
                            );
                        }
                        $nowOpenTime += $second;
                    }
                }
            }
            return $dateList;
        }
        //营业时间
        $open_1 = $return['store']['open_1'];
        $close_1 = $return['store']['close_1'];
        $open_2 = $return['store']['open_2'];
        $close_2 = $return['store']['close_2'];
        $open_3 = $return['store']['open_3'];
        $close_3 = $return['store']['close_3'];

        if (getFormatNumber($return['deliver_speed_hour']) && $return['store']['send_time_type'] == 0 && isset($return['distance'])) {    //按时速计算
            $send_time =  $return['distance'] / $return['deliver_speed_hour'] * 3600 + ($return['deliver_delay_time'] * 60);
        } else {
            $send_time = $return['store']['send_time'] * 60;
        }
		if($reduce>$return['store']['work_time'] * $diffTime + $send_time||$reduce=='-1'){
			$nowTime = $paramTime;//默认的期望送达时间
		}else{
			$nowTime = $paramTime + $return['store']['work_time'] * $diffTime + $send_time;//默认的期望送达时间
		}

        if ($second == 1200) {
			if($reduce>$return['store']['work_time'] * $diffTime||$reduce=='-1'){
				$nowTime = $paramTime;
			}else{
				$nowTime = $paramTime + $return['store']['work_time'] * $diffTime;
			}
		}
        $nowTime = strtotime(date('Y-m-d H:i', $nowTime));
		if($second == 900 && !$this->defaultSendTime){
			$this->defaultSendTime = $nowTime;
		}

        $addTime = $return['store']['work_time'] * $diffTime + $send_time;
        if ($second == 1200) {
            $addTime = $return['store']['work_time'] * $diffTime;
        }

        // 立即送达时间
        $right_row_time = time() + $return['store']['work_time'] * $diffTime + $send_time;

        //直接返回增加的秒数
        if ($return_s == 1) {
            return $addTime;
        }

        //营业时间第一时间段的  开始时间等于结束时间 证明就是全天营业
        if ($open_1 == $close_1) {
            $open_1 = '00:00:00';
            $close_1 = '23:59:59';
            $open_2 = $close_2 = $open_3 = $close_3 = '00:00:00';
        }

        if ($nowTime >= strtotime(date('Y-m-d 23:59:59'))) {
            $open_2 = '00:00:00';
            $close_2 = '23:59:59';
        }

        $ot1 = strtotime(date('Y-m-d ' . $open_1));
        $ct1 = strtotime(date('Y-m-d ' . $close_1));
        $ot2 = strtotime(date('Y-m-d ' . $open_2));
        $ct2 = strtotime(date('Y-m-d ' . $close_2));
        $ot3 = strtotime(date('Y-m-d ' . $open_3));
        $ct3 = strtotime(date('Y-m-d ' . $close_3));

        $s1 = $s2 = $s3 = $e1 = $e2 = $e3 = 0;

        $businessTime = array();
        if ($ot1 != $ct1) {
            $s1 = $ot1;
            $e1 = $ct1;
            if ($ot1 > $ct1) {
                $ct1 += 86400;
            }
            $businessTime[] = array(
                's' => $ot1 + $addTime,
                'e' => $ct1 + $addTime
            );
        }
        if ($ot2 != $ct2) {
            $s2 = $ot2;
            $e2 = $ct2;
            if ($ot2 > $ct2) {
                $ct2 += 86400;
            }
            $businessTime[] = array(
                's' => $ot2 + $addTime,
                'e' => $ct2 + $addTime
            );
        }
        if ($ot3 != $ct3) {
            $s3 = $ot3;
            $e3 = $ct3;
            if ($ot3 > $ct3) {
                $ct3 += 86400;
            }
            $businessTime[] = array(
                's' => $ot3 + $addTime,
                'e' => $ct3 + $addTime
            );
        }

        if ($e1 - $s2 > 86280 && $e1 - $s2 < 86400) {//时间段1和时间段2隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_2)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_2)) + 86400 + $addTime
            );
        }
        if ($e2 - $s1 > 86280 && $e2 - $s1 < 86400) {//时间段2和时间段1隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_1)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_1)) + 86400 + $addTime
            );
        }
        if ($e1 - $s3 > 86280 && $e1 - $s3 < 86400) {//时间段1和时间段3隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_3)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_3)) + 86400 + $addTime
            );
        }
        if ($e3 - $s1 > 86280 && $e3 - $s1 < 86400) {//时间段3和时间段1隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_1)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_1)) + 86400 + $addTime
            );
        }
        if ($e2 - $s3 > 86280 && $e2 - $s3 < 86400) {//时间段2和时间段3隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_3)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_3)) + 86400 + $addTime
            );
        }
        if ($e3 - $s2 > 86280 && $e3 - $s2 < 86400) {//时间段3和时间段2隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_2)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_2)) + 86400 + $addTime
            );
        }

        $startTime1 = strtotime(date('Y-m-d ' . $start_time));
        $stopTime1 = strtotime(date('Y-m-d ' . $stop_time));

        $deliverTimeList = array();
        if ($startTime1 > $stopTime1) {
            $deliverTimeList[] = array(
                's' => strtotime(date('Y-m-d 00:00:00')),
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price1'], 2))
            );
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1 + 86400,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price1'], 2))
            );
        } else {
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price1'], 2)),
            );
        }
        if ($start_time2 != '0' && $stop_time2 != '0') {
            $startTime2 = strtotime(date('Y-m-d ' . $start_time2));
            $stopTime2 = strtotime(date('Y-m-d ' . $stop_time2));
            if ($startTime2 > $stopTime2) {
                $deliverTimeList[] = array(
                    's' => strtotime(date('Y-m-d 00:00:00')),
                    'e' => $stopTime2,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2))
                );
                $deliverTimeList[] = array(
                    's' => $startTime2,
                    'e' => $stopTime2 + 86400,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2))
                );
            } else {
                $deliverTimeList[] = array(
                    's' => $startTime2,
                    'e' => $stopTime2,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2)),
                );
            }
        }

        if ($start_time3 != '0' && $stop_time3 != '0') {
            $startTime3 = strtotime(date('Y-m-d ' . $start_time3));
            $stopTime3 = strtotime(date('Y-m-d ' . $stop_time3));
            if ($startTime3 > $stopTime3) {
                $deliverTimeList[] = array(
                    's' => strtotime(date('Y-m-d 00:00:00')),
                    'e' => $stopTime3,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
                );
                $deliverTimeList[] = array(
                    's' => $startTime3,
                    'e' => $stopTime3 + 86400,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
                );
            } else {
                $deliverTimeList[] = array(
                    's' => $startTime3,
                    'e' => $stopTime3,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
                );
            }
        }

        if ($stopTime1 - $startTime2 > 86330 && $stopTime1 - $startTime2 < 86400) {//时间段1和时间段2隔天相连
            $startTime2 += 86400;
            $stopTime2 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime2,
                'e' => $stopTime2,
                'select' => 2,
                'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2))
            );
        }
        if ($stopTime2 - $startTime1 > 86330 && $stopTime2 - $startTime1 < 86400) {//时间段2和时间段1隔天相连
            $startTime1 += 86400;
            $stopTime1 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price1'], 2))
            );
        }

        if ($stopTime1 - $startTime3 > 86330 && $stopTime1 - $startTime3 < 86400) {//时间段1和时间段3隔天相连
            $startTime3 += 86400;
            $stopTime3 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime3,
                'e' => $stopTime3,
                'select' => 3,
                'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
            );
        }
        if ($stopTime3 - $startTime1 > 86330 && $stopTime3 - $startTime1 < 86400) {//时间段3和时间段1隔天相连
            $startTime1 += 86400;
            $stopTime1 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price1'], 2))
            );
        }

        if ($stopTime2 - $startTime3 > 86330 && $stopTime2 - $startTime3 < 86400) {//时间段2和时间段3隔天相连
            $startTime3 += 86400;
            $stopTime3 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime3,
                'e' => $stopTime3,
                'select' => 3,
                'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
            );
        }

        if ($stopTime3 - $startTime2 > 86330 && $stopTime3 - $startTime2 < 86400) {//时间段3和时间段2隔天相连
            $startTime2 += 86400;
            $stopTime2 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime2,
                'e' => $stopTime2,
                'select' => 2,
                'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2))
            );
        }

        $newB = array(); // 营业时间
        $newD = array(); // 配送时间
        for ($day = 0; $day <= $advance_day; $day++) {
            foreach ($businessTime as $btime) {
                $btime['s'] += $day * 86400;
                $btime['e'] += $day * 86400;
                $newB[] = $btime;
            }
            foreach ($deliverTimeList as $dtime) {
                $dtime['s'] += $day * 86400 + ($second == 900 ? $addTime : 0);//这里的addTime以前是sendtime,注意下逻辑
                $dtime['e'] += $day * 86400 + $addTime;
                $newD[] = $dtime;
            }
        }

        foreach ($deliverTimeList as $dtime) {
            $dtime['s'] += ($advance_day + 1) * 86400 + ($second == 900 ? $addTime : 0);//这里的addTime以前是sendtime,注意下逻辑
            $dtime['e'] += ($advance_day + 1) * 86400 + $addTime;
            $newD[] = $dtime;
        }
		$newB_new = D('Merchant_store_open_time')->get_time_by_week($return['store']['store_id']);
		$last_time_max = time()+($advance_day+1)*60*60*24;
        //$last_time_max = strtotime(date('Y-m-d 23:59:59')) + $advance_day*24*3600;
        $dateList = array();
        foreach ($newD as $row_key => $row) {
            $stime = $row['s'];
            $etime = $row['e'];
            $_m_00 = strtotime(date('Y-m-d H:00', $stime));
            $_m_15 = strtotime(date('Y-m-d H:15', $stime));
            $_m_30 = strtotime(date('Y-m-d H:30', $stime));
            $_m_45 = strtotime(date('Y-m-d H:45', $stime));

            if ($_m_00 >= $stime) {
                $stime = $_m_00;
            } elseif ($_m_15 >= $stime) {
                $stime = $_m_15;
            } elseif ($_m_30 >= $stime) {
                $stime = $_m_30;
            } elseif ($_m_45 >= $stime) {
                $stime = $_m_45;
            } else {
                $stime = strtotime(date('Y-m-d H:00', $stime + 3600));
            }

            if ($row['s'] <= $nowTime && $nowTime < $row['e']) {
                if ($this->checkBusinessTime_new($newB_new, $nowTime, $nowTime,$addTime,$last_time_max, $n, $advance_day)) {
                    $dateList[date('Y-m-d', $nowTime)][date('H:i', $nowTime)] = array(
                        'hour_minute' => date('H:i', $nowTime),
                        'time_select' => $row['select'],
                        'delivery_fee_old' => $row['delivery_fee_old'],
                        'delivery_fee' => $row['delivery_fee'],
						'first_asap' => $row_key == 0 ? true : false,
						'unix_time' => $nowTime,
						's_basic_price' => $row['s_basic_price'],
                    );

                    // 判断是否是立即送达时间
                    if (strtotime(date('H:i', $right_row_time)) == strtotime(date('H:i', $nowTime))) {
                    	$dateList[date('Y-m-d', $nowTime)][date('H:i', $nowTime)]['is_right_now'] = 1;
                    }
                }
            }
            for ($nowDate = $stime; $nowDate <= $etime;) {
                if ($this->checkBusinessTime_new($newB_new, $nowDate, $nowTime,$addTime,$last_time_max, $n, $advance_day)) {
                    $dateList[date('Y-m-d', $nowDate)][date('H:i', $nowDate)] = array(
                        'hour_minute' => date('H:i', $nowDate),
                        'time_select' => $row['select'],
                        'delivery_fee_old' => $row['delivery_fee_old'],
                        'delivery_fee' => $row['delivery_fee'],
						'unix_time' => $nowDate,
						's_basic_price' => $row['s_basic_price'],
                    );
                }
                $nowDate += $second;
            }
        }

        //组合时间段
        $dateList_new = array();
        foreach ($dateList as $key => $value) {
            foreach ($value as $k => $v) {
                $btime = $key . ' ' . $dateList[$key][$k]['hour_minute'] . ':00';
                $etime = date('H:i', strtotime($btime) + $second);
				$dateList_new[$key][$k]['unix_time'] = $dateList[$key][$k]['unix_time'];
				$dateList_new[$key][$k]['first_asap'] = $dateList[$key][$k]['first_asap'] ? true : false;
				$dateList_new[$key][$k]['hour_minute'] = $dateList[$key][$k]['hour_minute'];
				$dateList_new[$key][$k]['show_hour_minute'] = $dateList[$key][$k]['hour_minute'] . '-' . $etime;
				$dateList_new[$key][$k]['time_select'] = $dateList[$key][$k]['time_select'];

				$dateList_new[$key][$k]['s_basic_price'] = $dateList[$key][$k]['s_basic_price'];
				$dateList_new[$key][$k]['is_right_now'] = intval($dateList[$key][$k]['is_right_now']);

				if (!$n) {
					$dateList_new[$key][$k]['delivery_fee'] = (empty($_POST['Device-Id']) || $_POST['Device-Id'] == 'wxapp') ? $dateList[$key][$k]['delivery_fee'] : getFormatNumber($dateList[$key][$k]['delivery_fee']);
					$dateList_new[$key][$k]['delivery_fee_old'] = (empty($_POST['Device-Id']) || $_POST['Device-Id'] == 'wxapp') ? $dateList[$key][$k]['delivery_fee_old'] : getFormatNumber($dateList[$key][$k]['delivery_fee_old']);
				}
            }
        }
        return $dateList_new;
    }

    public function get_plat_select_time($plat_deliver, $paramTime = null, $n = 0, $second = 900){
    	if ($paramTime === null) {
            $paramTime = time();
        }
        $return = $plat_deliver;
        //预订的天数
        $advance_day = 1;
        $plat_config = D('Deliver_set')->get_plat_deliver_set();
        //配送时间段
        $start_time = $plat_config['delivertime_start'];
        $stop_time = $plat_config['delivertime_stop'];
        $start_time2 = $plat_config['delivertime_start2'];
        $stop_time2 = $plat_config['delivertime_stop2'];
        $start_time3 = $plat_config['delivertime_start3'];
        $stop_time3 = $plat_config['delivertime_stop3'];

        if ($start_time == $stop_time && $start_time == '00:00:00') {//时间段一，24小时
            $start_time = '00:00:00';
            $stop_time = '23:59:59';
        } elseif ($start_time2 == $stop_time2 && $start_time2 == '00:00:00') {//没有时间段二
            $stop_time2 = $start_time2 = 0;
        }
        if ($start_time3 == $stop_time3 && $start_time3 == '00:00:00') {//没有时间段三
            $stop_time3 = $start_time3 = 0;
        }

        //不受配送时间限制
        if($n){
            $start_time = '00:00:00';
            $stop_time = '23:59:59';
            $stop_time2 = $start_time2 = 0;
            $stop_time3 = $start_time3 = 0;
        }

        //出单时长
        $diffTime = 0;

        //营业时间
        $open_1 = $return['store']['open_1'];
        $close_1 = $return['store']['close_1'];
        $open_2 = $return['store']['open_2'];
        $close_2 = $return['store']['close_2'];
        $open_3 = $return['store']['open_3'];
        $close_3 = $return['store']['close_3'];


        $send_time = $plat_deliver['s_send_time']*60;


		$nowTime = $paramTime + $send_time;//默认的期望送达时间



        $nowTime = strtotime(date('Y-m-d H:i', $nowTime));
		if($second == 900 && !$this->defaultSendTime){
			$this->defaultSendTime = $nowTime;
		}

        $addTime =  $send_time;

        $startTime1 = strtotime(date('Y-m-d ' . $start_time));
        $stopTime1 = strtotime(date('Y-m-d ' . $stop_time));

        $deliverTimeList = array();
        if ($startTime1 > $stopTime1) {
            $deliverTimeList[] = array(
                's' => strtotime(date('Y-m-d 00:00:00')),
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1 + 86400,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
        } else {
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
        }
        if ($start_time2 != '0' && $stop_time2 != '0') {
            $startTime2 = strtotime(date('Y-m-d ' . $start_time2));
            $stopTime2 = strtotime(date('Y-m-d ' . $stop_time2));
            if ($startTime2 > $stopTime2) {
                $deliverTimeList[] = array(
                    's' => strtotime(date('Y-m-d 00:00:00')),
                    'e' => $stopTime2,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
                );
                $deliverTimeList[] = array(
                    's' => $startTime2,
                    'e' => $stopTime2 + 86400,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
                );
            } else {
                $deliverTimeList[] = array(
                    's' => $startTime2,
                    'e' => $stopTime2,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
                );
            }
        }

        if ($start_time3 != '0' && $stop_time3 != '0') {
            $startTime3 = strtotime(date('Y-m-d ' . $start_time3));
            $stopTime3 = strtotime(date('Y-m-d ' . $stop_time3));
            if ($startTime3 > $stopTime3) {
                $deliverTimeList[] = array(
                    's' => strtotime(date('Y-m-d 00:00:00')),
                    'e' => $stopTime3,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
                );
                $deliverTimeList[] = array(
                    's' => $startTime3,
                    'e' => $stopTime3 + 86400,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
                );
            } else {
                $deliverTimeList[] = array(
                    's' => $startTime3,
                    'e' => $stopTime3,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
                );
            }
        }

        if ($stopTime1 - $startTime2 > 86330 && $stopTime1 - $startTime2 < 86400) {//时间段1和时间段2隔天相连
            $startTime2 += 86400;
            $stopTime2 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime2,
                'e' => $stopTime2,
                'select' => 2,
                'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
            );
        }
        if ($stopTime2 - $startTime1 > 86330 && $stopTime2 - $startTime1 < 86400) {//时间段2和时间段1隔天相连
            $startTime1 += 86400;
            $stopTime1 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
        }

        if ($stopTime1 - $startTime3 > 86330 && $stopTime1 - $startTime3 < 86400) {//时间段1和时间段3隔天相连
            $startTime3 += 86400;
            $stopTime3 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime3,
                'e' => $stopTime3,
                'select' => 3,
                'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
            );
        }
        if ($stopTime3 - $startTime1 > 86330 && $stopTime3 - $startTime1 < 86400) {//时间段3和时间段1隔天相连
            $startTime1 += 86400;
            $stopTime1 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($return['delivery_fee_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee'], 2))
            );
        }

        if ($stopTime2 - $startTime3 > 86330 && $stopTime2 - $startTime3 < 86400) {//时间段2和时间段3隔天相连
            $startTime3 += 86400;
            $stopTime3 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime3,
                'e' => $stopTime3,
                'select' => 3,
                'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee3'], 2))
            );
        }

        if ($stopTime3 - $startTime2 > 86330 && $stopTime3 - $startTime2 < 86400) {//时间段3和时间段2隔天相连
            $startTime2 += 86400;
            $stopTime2 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime2,
                'e' => $stopTime2,
                'select' => 2,
                'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee2'], 2))
            );
        }


        $newD = array(); // 配送时间
        for ($day = 0; $day <= $advance_day; $day++) {
            foreach ($deliverTimeList as $dtime) {
                $dtime['s'] += $day * 86400 + ($second == 900 ? $addTime : 0);//这里的addTime以前是sendtime,注意下逻辑
                $dtime['e'] += $day * 86400 + $addTime;
                $newD[] = $dtime;
            }
        }

        foreach ($deliverTimeList as $dtime) {
            $dtime['s'] += ($advance_day + 1) * 86400 + ($second == 900 ? $addTime : 0);//这里的addTime以前是sendtime,注意下逻辑
            $dtime['e'] += ($advance_day + 1) * 86400 + $addTime;
            $newD[] = $dtime;
        }

        $dateList = array();
        foreach ($newD as $row_key => $row) {
            $stime = $row['s'];
            $etime = $row['e'];
            $_m_00 = strtotime(date('Y-m-d H:00', $stime));
            $_m_15 = strtotime(date('Y-m-d H:15', $stime));
            $_m_30 = strtotime(date('Y-m-d H:30', $stime));
            $_m_45 = strtotime(date('Y-m-d H:45', $stime));

            if ($_m_00 >= $stime) {
                $stime = $_m_00;
            } elseif ($_m_15 >= $stime) {
                $stime = $_m_15;
            } elseif ($_m_30 >= $stime) {
                $stime = $_m_30;
            } elseif ($_m_45 >= $stime) {
                $stime = $_m_45;
            } else {
                $stime = strtotime(date('Y-m-d H:00', $stime + 3600));
            }

            if ($row['s'] <= $nowTime && $nowTime < $row['e']) {

                    $dateList[date('Y-m-d', $nowTime)][date('H:i', $nowTime)] = array(
                        'hour_minute' => date('H:i', $nowTime),
                        'time_select' => $row['select'],
                        'delivery_fee_old' => $row['delivery_fee_old'],
                        'delivery_fee' => $row['delivery_fee'],
						'first_asap' => $row_key == 0 ? true : false,
						'unix_time' => $nowTime
                    );

            }
            for ($nowDate = $stime; $nowDate <= $etime;) {

                    $dateList[date('Y-m-d', $nowDate)][date('H:i', $nowDate)] = array(
                        'hour_minute' => date('H:i', $nowDate),
                        'time_select' => $row['select'],
                        'delivery_fee_old' => $row['delivery_fee_old'],
                        'delivery_fee' => $row['delivery_fee'],
						'unix_time' => $nowDate
                    );

                $nowDate += $second;
            }
        }
		// dump($dateList);exit;
        //组合时间段
        $dateList_new = array();
        foreach ($dateList as $key => $value) {
            foreach ($value as $k => $v) {
                $btime = $key . ' ' . $dateList[$key][$k]['hour_minute'] . ':00';
                $etime = date('H:i', strtotime($btime) + $second);
                if (isset($dateList[$key][$etime])&&$v['unix_time']>$nowTime) {
					$dateList_new[$key][$k]['unix_time'] = $dateList[$key][$k]['unix_time'];
					$dateList_new[$key][$k]['first_asap'] = $dateList[$key][$k]['first_asap'] ? true : false;
                    $dateList_new[$key][$k]['hour_minute'] = $dateList[$key][$k]['hour_minute'];
                    $dateList_new[$key][$k]['show_hour_minute'] = $dateList[$key][$k]['hour_minute'] . '-' . $etime;
                    $dateList_new[$key][$k]['time_select'] = $dateList[$key][$k]['time_select'];
                    if (!$n) {
                        $dateList_new[$key][$k]['delivery_fee'] = (empty($_POST['Device-Id']) || $_POST['Device-Id'] == 'wxapp') ? $dateList[$key][$k]['delivery_fee'] : getFormatNumber($dateList[$key][$k]['delivery_fee']);
                        $dateList_new[$key][$k]['delivery_fee_old'] = (empty($_POST['Device-Id']) || $_POST['Device-Id'] == 'wxapp') ? $dateList[$key][$k]['delivery_fee_old'] : getFormatNumber($dateList[$key][$k]['delivery_fee_old']);
                    }
                }
            }
        }
        return $dateList_new;
    }

    public function getListByStoreId($store_id, $sort = 0, $keyword = '', $is_app=false,$inWeek = false, $isOpen=false, $uid = 0)

    {
        $sort_list = D('Shop_goods_sort')->lists($store_id,1,'',$inWeek,$isOpen);
        $now_shop = D('Merchant_store_shop')->field('stock_type')->where(array('store_id' => $store_id))->find();
        $merId = D('Merchant_store')->where(array('store_id' => $store_id))->getField('mer_id');
        if ($sort == 1) {
        	$where=array('store_id' => $store_id, 'status' => 1);
        	$where['goods_type']=array('neq',1);
            $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order('sell_count DESC, goods_id ASC')->select();
        } elseif ($sort == 2) {
        	$where=array('store_id' => $store_id, 'status' => 1);
        	$where['goods_type']=array('neq',1);
            $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order('price DESC, goods_id ASC')->select();
        } elseif ($keyword != '') {
        	$where=array('store_id' => $store_id, 'name' => array('like', '%' . $keyword . '%'), 'status' => 1);
        	$where['goods_type']=array('neq',1);
            $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order('sort DESC, goods_id ASC')->select();
        } else {
        	$where=array('store_id' => $store_id, 'status' => 1);
        	$where['goods_type']=array('neq',1);
            $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order('sort DESC, goods_id ASC')->select();
        }
        $newList = array();
        $today = date('Ymd');
        $goods_image_class = new goods_image();
        $timeNow = time();
        $time_limited_discount_mod = D('Time_limited_discount_goods');
        foreach ($g_list as $r) {
            //新增限时显示
            if (!($r['show_start_time'] == $r['show_end_time'] || ($r['show_start_time'] == '00:00:00' && $r['show_end_time'] == '23:59:00'))) {
                $st = strtotime(date('Y-m-d') . ' ' . $r['show_start_time']);
                $et = strtotime(date('Y-m-d') . ' ' . $r['show_end_time']);
                if (!($st <= $timeNow && $timeNow <= $et)) {
                    continue;
                }
            }

			//只显示未开启员工专卖商品
			if (C('customization.life_tools')) {
				$mer_id = D('Merchant_store')->where(['store_id' => $store_id])->getField('mer_id');
				$setEmployeeCardMerId = C('config.set_employee_card_mer_id') ? explode(',',C('config.set_employee_card_mer_id')) : [];
				if (in_array($mer_id,$setEmployeeCardMerId) && $r['open_employee_card'] == 1) { //已开启开启员工专卖商品，且用户标签不对应的不能购买
					$can_buy = 0;
					if ($uid && $r['employee_lables']) {
						$employee_card_user = D('Employee_card_user')->where(['uid' => $uid, 'mer_id' => $r['employee_mer_id'], 'status' => 1])->field('lable_ids')->find();
						if (!empty($employee_card_user['lable_ids'])) {
							$lable_ids = explode(',', $employee_card_user['lable_ids']);
							$goods_lable_ids = explode(',', $r['employee_lables']);
							if (array_intersect($lable_ids, $goods_lable_ids)) { //存在交集可以购买
								$can_buy = 1;
							}
						}
					}
					if ($can_buy == 0) {
						continue;
					}
				}
			}

            //重置库存
            $twStock = $this->queryTwGoodsStock($r['goods_id']);
            if (!is_null($twStock)) {
                $r['stock_num'] = getFormatNumber($twStock);
            }

            $glist = array();
			$glist['score_percent'] = $r['score_percent'];
            $glist['product_id'] = $r['goods_id'];
			$glist['store_id'] = $r['store_id'];
            $glist['product_name'] = html_entity_decode($r['name'], ENT_QUOTES);
            $glist['product_describe'] = $r['describe'];
            $glist['describe'] = $r['describe'];
            $glist['product_price'] = strval(floatval($r['price']));
            $glist['is_seckill_price'] = strval(floatval($r['is_seckill_price']));
            $glist['o_price'] = strval(floatval($r['old_price']));
            $glist['number'] = $r['number'];
            $glist['packing_charge'] = strval(floatval($r['packing_charge']));
            $glist['unit'] = $r['unit'];
            $glist['is_subsidiary_goods'] = $r['is_subsidiary_goods'];//1有附属商品 0没有附属商品
            $glist['shop_video_url'] = $r['shop_video_url'] ?C('config.site_url').'/'.$r['shop_video_url']:'';//外卖商品视频地址
			//积分展示
			$glist['score_text'] = '';
			if(C('customization.goods_show_score') && $scorePrice = floatval($r['price'])){
				$scorePercent = D('Percent_rate')->scorePercent('shop', $r['goods_id'], $merId);
				$score = bcmul($scorePrice, $scorePercent,2);
				if(C('config.score_round_two')){
					$score = sprintf('%.2f',floatval($score));
				}else{
					$score = round($score);
				}
				floatval($score) > 0 && $glist['score_text'] = C('config.score_name').'+'.$score;
			}
            //如果设置了最小起购，限购就无效
            if ($r['min_num'] > 1) {
                $glist['max_num'] = 0;
            } else {
                $glist['max_num'] = $r['max_num'];
            }
            $glist['min_num'] = $r['min_num'];
            $glist['limit_type'] = $r['limit_type'];
            $glist['single_no_delivery'] = $r['single_no_delivery']??0;

            //商品缩略图
			$tmp_pic_arr = $goods_image_class->get_allImage_by_path($r['image'], 's');
			$glist['product_image'] = thumb_img($tmp_pic_arr[0],240,240,'fill');

            $glist['product_sale'] = $r['sell_count'] + $r['shop_virtual_sales'];
            $glist['product_reply'] = $r['reply_count'];
            $glist['has_format'] = false;
            if ($r['spec_value'] || $r['is_properties']) {
                $glist['has_format'] = true;
            }
			$glist['has_spec']  = $r['spec_value'] ? true : false; //商品是否有规格
            $r['sell_day'] = $now_shop['stock_type'] ? $today : $r['sell_day'];

            //限时优惠价格、库存、限购数量重置
            $result = $time_limited_discount_mod->get_discount_price_by_goodsid($r['goods_id']);
            if($result['is_discount']){
                $r['is_seckill_price'] = true;
                $r['max_num'] = $result['max_num'];
                $glist['max_num'] = $result['max_num'];
                $glist['product_price'] = getFormatNumber($result['discount_price']);
                $glist['o_price'] = getFormatNumber($r['price']);
                $seckill_stock_num = $result['stock'];
                $glist['seckill_discount'] = $glist['o_price'] ? round($glist['product_price']/$glist['o_price']*10, 1) : 0;
				$glist['product_sale'] = M('Time_limited_discount_sale_record')->where(['limit_id'=>$result['id']])->sum('num');
            }else{
                $r['is_seckill_price'] = false;
                $glist['product_price'] = getFormatNumber($r['price']);
                $seckill_stock_num = 0;
            }

            /*if ($r['seckill_type'] == 1) {
                $now_time = date('H:i');
                $open_time = date('H:i', $r['seckill_open_time']);
                $close_time = date('H:i', $r['seckill_close_time']);

                // 秒杀库存的计算
                if ($today == $r['sell_day']) {
                    $seckill_stock_num = $r['seckill_stock'] == - 1 ? - 1 : (intval($r['seckill_stock'] - $r['today_seckill_count']) > 0 ? intval($r['seckill_stock'] - $r['today_seckill_count']) : 0);
                } else {
                    $seckill_stock_num = $r['seckill_stock'];
                }
            } else {
                $now_time = time();
                $open_time = $r['seckill_open_time'];
                $close_time = $r['seckill_close_time'];
                $seckill_stock_num = $r['seckill_stock'] == - 1 ? - 1 : (intval($r['seckill_stock'] - $r['today_seckill_count']) > 0 ? intval($r['seckill_stock'] - $r['today_seckill_count']) : 0);
            }

            $r['is_seckill_price'] = false;
            if ($open_time < $now_time && $now_time < $close_time && floatval($r['seckill_price']) > 0 && $seckill_stock_num != 0) {
                $glist['product_price'] = getFormatNumber($r['seckill_price']);
                $r['is_seckill_price'] = true;
                $glist['o_price'] = getFormatNumber($r['price']);
				$glist['seckill_discount'] = $glist['o_price'] ? round($glist['product_price']/$glist['o_price']*10, 1) : 0;
            }*/

            if ($r['is_seckill_price']) {
                $glist['stock'] = $seckill_stock_num;
                $glist['seckill_stock'] = $seckill_stock_num;
            } else {
                if ($today == $r['sell_day']) {
//                     $glist['stock'] = $r['stock_num'] == - 1 ? $r['stock_num'] : (intval($r['stock_num'] - $r['today_sell_count']) > 0 ? intval($r['stock_num'] - $r['today_sell_count']) : 0);
                    $glist['stock'] = $r['stock_num'];
                    if($r['sell_type'] == 2 && $r['stock_weight'] == -1){
                        $glist['stock'] = -1;
                    }elseif($r['sell_type'] == 2){
                        $goods_weight =  $r['weight_unit'] == 2 ? $r['weight'] * 1000 : $r['weight'];
                        $glist['stock'] = $goods_weight > 0 ? floor($r['stock_weight'] / $goods_weight) : 0;
                    }
                } else {
//                     $glist['stock'] = $r['stock_num'];
                    $glist['stock'] = $r['original_stock'];
                    if($r['sell_type'] == 2 && $r['original_weight'] == -1){
                        $glist['stock'] = -1;
                    }elseif($r['sell_type'] == 2){
                        $goods_weight =  $r['weight_unit'] == 2 ? $r['weight'] * 1000 : $r['weight'];
                        $glist['stock'] = $goods_weight > 0 ? floor($r['original_weight'] / $goods_weight) : 0;
                    }
                }
            }
            $glist['is_seckill_price'] = $r['is_seckill_price'];
            $glist['is_new'] = (($timeNow - $glist['add_time']) > 864000) ? 0 : 1;

			if(!$glist['spec_list']){
				if($glist['min_num'] > 0 && $glist['stock_num'] >= 0 && $glist['min_num'] > $glist['stock_num']){
					$glist['stock_num'] = 0;
				}
				if($glist['min_num'] > 0 && $glist['seckill_stock'] >= 0 && $glist['min_num'] > $glist['seckill_stock']){
					$glist['seckill_stock'] = 0;
				}
			}

            $glist['have_combined_package'] = $this->haveCombinedPackage($r['goods_id']);

            if (isset($newList[$r['sort_id']])) {
                $newList[$r['sort_id']][] = $glist;
            } else {
                $newList[$r['sort_id']] = array($glist);
            }
        }
        $result = array();
        $index = 0;
        foreach ($sort_list as $l) {
            $temp = array();
            $temp['sort_id'] = $l['sort_id'];
            $temp['cat_id'] = $l['sort_id'];
            $temp['cat_name'] = htmlspecialchars_decode($l['sort_name']);
            $temp['level'] = $l['level'];
            $temp['index'] = $index;
            $temp['desc'] = $l['desc'];
            $temp['findex'] = 0;
            $temp['fid'] = 0;
            $temp['category_limit_num'] = $l['category_limit_num'];
            if (!$is_app || intval($l['sort_discount'])>10) {
                $temp['sort_discount'] = strval(round(intval($l['sort_discount']) * 0.1, 2));
            } else {
                $temp['sort_discount'] = strval(round($l['sort_discount']*0.1, 2));
            }
            if (isset($newList[$l['sort_id']])) {
                $temp['product_list'] = $newList[$l['sort_id']];
                $temp['son_list'] = array();
            } elseif ($l['son_list']) {
                $temp['product_list'] = array();
                $son_list = $this->formatList($l['son_list'], $newList, $index, $l['sort_id'], $is_app);
                if ($son_list) {
                    $temp['son_list'] = $son_list;
                } else {
                    $temp = null;
                }
            } else {
                $temp = null;
            }
            if (!empty($temp)) {
                $index ++;
                $result[] = $temp;
            }
        }
        return $result;
    }

    private function formatList($list, $newList, $findex, $fid, $is_app=false)
    {
        if (empty($list)) {
            return $list;
        }
        $result = array();
        $index = 0;
        foreach ($list as $l) {
            $temp = array();
            $temp['cat_id'] = $l['sort_id'];
            $temp['level'] = $l['level'];
            $temp['index'] = $index;
            $temp['findex'] = $findex;
            $temp['fid'] = $fid;
            $temp['cat_name'] = $l['sort_name'];
            if (!$is_app || intval($l['sort_discount'])>10) {
                $temp['sort_discount'] = strval(round(intval($l['sort_discount']) * 0.1, 2));
            } else {
                $temp['sort_discount'] = strval(round(intval($l['sort_discount']), 2));
            }
            if (isset($newList[$l['sort_id']])) {
                $temp['product_list'] = $newList[$l['sort_id']];
                $temp['son_list'] = array();
            } elseif ($l['son_list']) {
                $temp['product_list'] = array();
                $son_list = $this->formatList($l['son_list'], $newList, $index, $l['sort_id'], $is_app);
                if ($son_list) {
                    $temp['son_list'] = $son_list;
                } else {
                    $temp = null;
                }
            } else {
                $temp = null;
            }
            if (!empty($temp)) {
                $index ++;
                $result[] = $temp;
            }
        }
        return $result;
    }

    public function get_qrcode($id)
    {
        $condition_good['goods_id'] = $id;
        $now_good = $this->field('`goods_id`')->where($condition_good)->find();
        if (empty($now_good)) {
            return false;
        }
        return $now_good;
    }

    /**
     * 判断店铺是否有秒杀商品
     * @param int $store_id
     * @return boolean
     */
    public function getDiscountGoods($store_id)
    {
        return D('Time_limited_discount_goods')->check_store_has_time_limit($store_id);

        $nowTime = time();
        $where = 'store_id=' . $store_id . ' AND seckill_price>0 AND ((seckill_type=0 AND seckill_open_time<' . $nowTime . ' AND seckill_close_time>' . $nowTime . ') OR seckill_type=1)';
        $where.=' and goods_type!=1';
        $goodsList = $this->field(true)->where($where)->select();
        foreach ($goodsList as $goods) {
            if ($goods['seckill_type'] == 0) {
                return true;
            } else {
                $seckill_open_time = strtotime(date('Y-m-d')  . ' ' . date('H:i:s', $goods['seckill_open_time']));
                $seckill_close_time = strtotime(date('Y-m-d')  . ' ' . date('H:i:s', $goods['seckill_close_time']));
                if ($seckill_open_time < $nowTime && $nowTime < $seckill_close_time) {
                    return true;
                }
            }
        }
        return false;
    }

    public function findListByStoreid($store_id, $sort = 0, $keyword = '')
    {
        $database_goods_sort = D('Shop_goods_sort');
        $condition_goods_sort['store_id'] = $store_id;
        $sort_list = D(_view('Shop_goods_sort'))->field(true)->where($condition_goods_sort)->order('`sort` DESC,`sort_id` ASC')->select();
        $sort_image_class = new goods_sort_image();
        $goods_image_class = new goods_image();
        $s_list = array();
        $today = date('w');

        $today = date('Ymd');
        $storeShop = D('Merchant_store_shop')->where(array('store_id' => $store_id,'status' => '1'))->find();
        $stock_type = $storeShop['stock_type'];
        $sort_result = array();

        $timeNow = time();

        foreach ($sort_list as $value) {
            if ($value['week']) {
                $week_arr = explode(',',$value['week']);
                if (!in_array($today, $week_arr)) {
                    continue;
                }
                $week_str = '';
                foreach ($week_arr as $k=>$v){
                    $week_str .= $this->get_week($v).' ';
                }
                $value['week_str'] = $week_str;
            }

            $value['see_image'] = $sort_image_class->get_image_by_path($value['image'],C('config.site_url'),'s');
            $s_list[$value['sort_id']] = $value;



            if ($sort == 1) {
            	$where=array('store_id' => $store_id, 'status' => 1, 'sort_id' => $value['sort_id']);
            	$where['goods_type']=array('neq',1);
                $g_list = $this->field(true)->where($where)->order('sell_count DESC, goods_id ASC')->select();
            } elseif ($sort == 2) {
            	$where=array('store_id' => $store_id, 'status' => 1, 'sort_id' => $value['sort_id']);
            	$where['goods_type']=array('neq',1);
                $g_list = $this->field(true)->where($where)->order('price DESC, goods_id ASC')->select();
            } elseif ($keyword != '') {
            	$where=array('store_id' => $store_id, 'sort_id' => $value['sort_id'], 'name' => array('like', '%' . $keyword . '%'), 'status' => 1);
            	$where['goods_type']=array('neq',1);
                $g_list = $this->field(true)->where($where)->order('sort DESC, goods_id ASC')->select();
            } else {
            	$where=array('store_id' => $store_id, 'sort_id' => $value['sort_id'], 'status' => 1);
            	$where['goods_type']=array('neq',1);
                $g_list = $this->field(true)->where($where)->order('sort DESC, goods_id ASC')->select();
            }


            foreach ($g_list as $row) {
                //新增限时显示
                if (!($row['show_start_time'] == $row['show_end_time'] || ($row['show_start_time'] == '00:00:00' && $row['show_end_time'] == '23:59:00'))) {
                    $st = strtotime(date('Y-m-d') . ' ' . $row['show_start_time']);
                    $et = strtotime(date('Y-m-d') . ' ' . $row['show_end_time']);
                    if (!($st <= $timeNow && $timeNow <= $et)) {
                        continue;
                    }
                }

                $row['sell_day'] = $stock_type ? $today : $row['sell_day'];
                if ($row['seckill_type'] == 1) {
                    $now_time = date('H:i');
                    $open_time = date('H:i', $row['seckill_open_time']);
                    $close_time = date('H:i', $row['seckill_close_time']);
                    //秒杀库存的计算
                    if ($today == $row['sell_day']) {
                        $seckill_stock_num = $row['seckill_stock'] == -1 ? -1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
                    } else {
                        $seckill_stock_num = $row['seckill_stock'];
                    }
                } else {
                    $now_time = time();
                    $open_time = $row['seckill_open_time'];
                    $close_time = $row['seckill_close_time'];
                    $seckill_stock_num = $row['seckill_stock'] == -1 ? -1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
                }
                $row['is_seckill_price'] = false;
                $row['o_price'] = floatval($row['price']);

                if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0 && $seckill_stock_num != 0) {
                    $row['price'] = floatval($row['seckill_price']);
                    $row['is_seckill_price'] = true;
                } else {
                    $row['price'] = floatval($row['price']);
                }

                $row['old_price'] = floatval($row['old_price']);
                $row['seckill_price'] = floatval($row['seckill_price']);
                $tmp_pic_arr = explode(';', $row['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $row['pic_arr'][$key]['title'] = $value;
                    $row['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value);
                }

                $return = $this->format_spec_value($row['spec_value'], $row['goods_id'], $row['is_properties']);
                $row['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
                $row['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
                $row['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                $row['list'] = isset($return['list']) ? $return['list'] : '';

                if ($sort == 1 || $sort == 2 || $keyword != '') {//pc端的排序与搜索
                    $sort_result[] = $row;
                } else {
                    if (isset($s_list[$row['sort_id']])) {
                        if (isset($s_list[$row['sort_id']]['goods_list'])) {
                            $s_list[$row['sort_id']]['goods_list'][] = $row;
                        } else {
                            $s_list[$row['sort_id']]['goods_list'] = array($row);
                        }
                    }
                }
            }

        }
        if ($sort == 1 || $sort == 2 || $keyword != '') {//pc端的排序与搜索
            $s_list = array(array('goods_list' => $sort_result, 'sort_id' => false));
        } else {
            foreach ($s_list as $k => $r) {
                if (!isset($r['goods_list'])) {
                    unset($s_list[$k]);
                }
            }
        }
        return $s_list;
    }

	/**
	 * @desc 这里新增一个方法专门计算计件模式下的运费。jjc-2019-05-09
	 * @param $template：运费模板
	 * @param $num：商品数量
	 * @return int：运费
	 */
	protected function express_fee_by_piece($template,$num){
		if(empty($template)||empty($num)) return 0;
		if(empty($template['first_freight'])||$template['first_freight']=='0.00'){//这个是老数据，不支持首件+续件的计费方式。
			return $template['freight'];
		}
		if($num<$template['first_weight']){
			return $template['first_freight'];
		}
		//运费计算公式：首件运费+续件运费（向上取整）
		if($num-$template['first_weight']==0||$template['add_weight']==0){//防止计算除数或者被除数为0导致的计算问题
			$fee = $template['first_freight'];
		}else{
		    /*if($template['total_weight']>$template['first_weight']){*/
                $fee = $template['first_freight']+ceil(($num-$template['first_weight'])/$template['add_weight'])*$template['add_freight'];
            /*}else{
                $fee = $template['first_freight'];
            }*/
		}
		return $fee;
	}

	//商城店铺设置
	private function get_mall_store($shop){
		$db = M('Merchant_store_mall');
		$where = ['store_id'=>$shop['store_id']];
		$data = $db->where($where)->find();
		//需要替换的字段
		$fidld_config = array(
				'delivery_range_type',
				'custom_id',
				'delivery_radius',
				'delivery_area',
				'delivery_range_polygon',
				's_send_time',
				's_basic_price',
				's_extra_price',
				's_is_open_own',
				's_basic_price1',
				's_free_type',
				's_delivery_fee',
				's_basic_distance',
				's_per_km_price',
				's_full_money',
				's_basic_price2',
				's_free_type2',
				's_delivery_fee2',
				's_basic_distance2',
				's_per_km_price2',
				's_full_money2',
				's_basic_price3',
				's_free_type3',
				's_delivery_fee3',
				's_basic_distance3',
				's_per_km_price3',
				's_full_money3',
				's_is_open_virtual',
				'virtual_delivery_fee',
		);
		if($data){//如果设置了商城店铺信息字段需要替换成商城的
			foreach($fidld_config as $val){
				$shop[$val] = $data[$val];
			}
		}
		return $shop;
	}

    //校验用户下单信息
    public function checkCart($store_id, $uid, $goodsData, $isCookie = 1, $address_id = 0, $is_market = false, $shop_type = 1, $group_id = null, $bar_id = null, $team_id = null, $upgradeVipLevel = 0)
    {
        //计算附属商品数量 当主商品数量增加 附属商品数量相应增加
        $goodsData = $this->combination_data_num($goodsData);

        fdump_sql(['goods'=>$goodsData,'store_id'=>$store_id,'uid'=>$uid,'isCookie'=>$isCookie],'shop_checkCart');

        $store = D(_view("Merchant_store"))->field(true)->where(array('store_id' => $store_id))->find();
        if ($store['have_shop'] == 0 || $store['status'] != 1) {
            return array('error_code' => true, 'msg' => '商家已经关闭了该业务,不能下单了!');
        }
        if (C('config.store_shop_auth') == 1 && $store['auth'] < 3) {
            //dump(2);
            if ($shop_type == 2) {
                $alias_name = C('config.mall_alias_name');
                $alias_name = $alias_name ? $alias_name : L_('商城');
            } else {
                $alias_name = C('config.shop_alias_name');
            }
            return array('error_code' => true, 'msg' => L_("您查看的X1没有通过资质审核！", array("X1" => $alias_name)));
        }

        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($store['pic_info']);
        $store['images'] = $images ? array_shift($images) : '';

        $store_shop = D(_view("Merchant_store_shop"))->field(true)->where(array('store_id' => $store_id))->find();
		if($shop_type==2){//商城订单，商城的店铺信息兼容商城店铺的属性设置
			$store_shop = $this->get_mall_store($store_shop);
		}

        if (empty($store) || empty($store_shop)) {
            return array('error_code' => true, 'msg' => L_('店铺信息有误'));
        }
		if($shop_type==1){
            //dump(5);
			//检测营业时间 开始
			$is_open = 0;
			if (D('Merchant_store_shop')->checkTime($store) || $store_shop['advance_day'] > 0) {
				$is_open = 1;
			}
			if ($is_open == 0 && $shop_type==1) {
				return array('error_code' => true, 'msg' => L_('欢迎预定,此刻店内休息（营业时间: X1）', D('Merchant_store_shop')->getBuniessName($store)));
			}
			if ($store_shop['close_reason'] == '') {
				$close_reason = L_('店铺临时关闭');
			} else {
				$close_reason = L_('店铺临时关闭： ') . $store_shop['close_reason'];
			}
			if ($store_shop['is_close'] == 1) {
				return array('error_code' => true, 'msg' => $close_reason);
			}
            //dump($is_open);
            //dump("is_close".$store_shop['is_close']);
			//检测营业时间 结束
		}
        $store = array_merge($store, $store_shop);
        $mer_id = $store['mer_id'];

        //商城读取mall_deliver_type
        if ($shop_type == 2) {
            $store['deliver_type'] = in_array($store['mall_deliver_type'], [0, 6, 8, 7]) ? 0 : $store['mall_deliver_type'];
        }

        //不满足配送要求的加价送增加的价格
        if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
            $store['extra_price'] = floatval($store['s_extra_price']) ? floatval($store['s_extra_price']) : floatval(C('config.extra_price'));
        } else {
            $store['extra_price'] = floatval($store['extra_price']);//配送附加费
        }
        $store['delivery_range_polygon'] = substr($store['delivery_range_polygon'], 9, strlen($store['delivery_range_polygon']) - 11);
        $lngLatData = explode(',', $store['delivery_range_polygon']);
        array_pop($lngLatData);
        $lngLats = array();
        foreach ($lngLatData as $lnglat) {
            $lng_lat = explode(' ', $lnglat);
            $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
        }
        $store['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
        //用户的VIP折扣率
        $vip_discount = 100;
        $is_discount = 0;
        $vipMinusPrice = 0;//立减金额

        //店铺设置的vip等级折扣率
        if ($shop_type == 2) {
            $storeShopLevel = !empty($store_shop['mall_leveloff']) ? unserialize($store_shop['mall_leveloff']) : '';
        } else {
            $storeShopLevel = !empty($store_shop['leveloff']) ? unserialize($store_shop['leveloff']) : '';
        }

        $user = M('User')->field(true)->where(array('uid' => $uid))->find();
        //dump("user:".$user);
        //("storeShopLevel:".$storeShopLevel);
        if ($storeShopLevel && $user) {
            //确认页选中升级vip，强制更新用户vip等级从而享受相关优惠福利
            if($upgradeVipLevel){
                $user['level'] = $upgradeVipLevel;
            }
            if ($user['level']) {
                //系统设置的用户等级
                $tmpArr = M(_view('User_level'))->field(true)->order('`id` ASC')->select();
                $levelArray = array();
                foreach ($tmpArr as $vv) {
                    $levelArray[$vv['level']] = $vv;
                }
                if (isset($storeShopLevel[$user['level']]) && isset($levelArray[$user['level']])) {
                    $levelOff = $storeShopLevel[$user['level']];
                    if ($levelOff['type'] == 1) {
                        $vip_discount = $levelOff['vv'];
                    }else{
						$vipMinusPrice = $levelOff['vv'];
					}
                }
            }
        }
        if ($group_id > 0 || $bar_id > 0) {    //拼团砍价不享受会员等级优惠
            $vip_discount = 100;
            $is_discount = 0;
        }

        $goods = array();
        $price = 0;//原始总价
        $total = 0;//商品总数
        $extra_price = 0;//额外价格的总价
        $packing_charge = 0;//打包费
        if ($group_id > 0 || $bar_id > 0) {    //拼团砍价不享受店铺折扣
            $sorts_discout = null;
        } else {
            //店铺优惠条件
            $sorts_discout = D('Shop_goods_sort')->get_sorts_discount($store_id, $shop_type);
        }

        $store_discount_money = 0;//店铺折扣后的总价
        $noDiscountGoods = '';
        $useGoods = array();
        $useCouponMeny = 0;//统计可用商家优惠券的商品总价

        if ($address_id) {
            $user_adress = D('User_adress')->get_one_adress($uid, $address_id);
            $express_freight = array();
            $delivery_list = D('Express_template')->get_deliver_list($store['mer_id'], $store['store_id'], 2);
            $goods_id_array = array();
            $delivery_money_total = 0;
            $max_freight = 0;
            $template_total_price = 0;
        }

        //处理拼单可能出现每个袋中有相同的商品导致数量超出
        $totalData = array();
        $uniqueMd5 = [];
        foreach ($goodsData as $key=>$row) {
            $removeIdRow = $row;
            unset($removeIdRow['id'], $removeIdRow['count'], $removeIdRow['num']);
            $md5 = md5(var_export($removeIdRow, true));
            if (in_array($md5, $uniqueMd5)) {
                unset($goodsData[$key]);

                continue;
            }
            $uniqueMd5[] = $md5;

            if ($isCookie == 0) {
                $goods_id = $row['goods_id'];
                $num = $row['num'];
                $spec_str = $row['spec_id'];
                $str = $row['spec'];
            } elseif ($isCookie == 1) {
                $goods_id = $row['productId'];
                $num = $row['count'];
                $spec_ids = array();
                foreach ($row['productParam'] as $r) {
                    if ($r['type'] == 'spec') {
                        $spec_ids[] = $r['id'];
                    }
                }
                $spec_str = $spec_ids ? implode('_', $spec_ids) : '';
            } elseif ($isCookie == 2) {
                $ids = explode('_', $row['goods_id']);
                $goods_id = array_shift($ids);
                $spec_str = $ids ? implode('_', $ids) : '';
                $num = $row['num'];
                $weight = ($row['goods_weight'] * $row['num']) + $row['code_weight'];
            }

            if ($num < 1 && !$row['code_weight']) continue;
            if (isset($totalData[$goods_id . '_' . $spec_str])) {
                $totalData[$goods_id . '_' . $spec_str] += $num;
                if ($isCookie == 2) {
                    $totalData[$goods_id . '_' . $spec_str . 'w'] += $weight;
                }
            } else {
                $totalData[$goods_id . '_' . $spec_str] = $num;
                if ($isCookie == 2) {
                    $totalData[$goods_id . '_' . $spec_str . 'w'] = $weight;
                }
            }
        }
        $goodsData = array_values($goodsData);
		//这里商城订单加个处理，如果一笔订单中的商城运费计算方式既有“按最大值计算”又有“单独计算”，统一修改成单独计算
		$mixed_freight = false;
		if($shop_type==2){
			$mixed_freight = $this->check_mall_mixed_freight($goodsData);
		}
        //检测商品是否展示
        $invalid_goods = [];
        foreach ($goodsData as $key => $g) {
            $goodsDBName = $g['productName'] ?? $g['name'];
            $goodsDBId = $g['productId'] ?? $g['goods_id'];
            $goodsName = M('Shop_goods')->where(['goods_id' => $goodsDBId])->getField('name');
            $goodsData[$key]['spec_name'] = $goodsData[$key]['name'];
            if ($goodsName) {
                $goodsData[$key]['productName'] = $goodsData[$key]['name'] = $goodsName;
            } else {
                $goodsData[$key]['productName'] = $goodsData[$key]['name'] = $goodsDBName;
            }
            if ($shop_type == 1 && !$this->isShopOrMallGoods($goodsDBId, true)) {
                //外卖
                return array('error_code' => true, 'msg' => $goodsData[$key]['productName'] . L_('只能在') . C('config.mall_alias_name') . L_('购买'));
            } else if ($shop_type == 2 && !$this->isShopOrMallGoods($goodsDBId, false)) {
                //商城
                return array('error_code' => true, 'msg' => $goodsData[$key]['productName'] . L_('只能在') . C('config.shop_alias_name') . L_('购买'));
            }

            if (!D('Shop_goods')->isShow($goodsDBId, 1 | 4, $shop_type)) {
                $invalid_goods[] = $goodsData[$key]['productName'];
            }
        }
        if ($invalid_goods) {
            return array('error_code' => true, 'msg' => implode('、', $invalid_goods) . L_('已下架'));
        }
        /*按照重量计算的商品信息*/
        $goods_weight_list = array();
		$total_score = 0;//订单所需总积分
        foreach ($goodsData as $row) {
			$checkRes = $this->checkGoodsSpec($row);
			if(!empty($checkRes['error_code'])){
				return $checkRes;
			}
            if ($isCookie == 0) {
                $goods_id = $row['goods_id'];
				if(!empty($goods_id)){
					$full_list = D('Mall_full_act')->get_full_list($goods_id,$store_id);
					if(!empty($full_list['layer_num_id'])){
						$where['id'] = array('in',$full_list['layer_num_id']);
						$where['_string'] = "goods_price <= ".$row['productPrice']*$row['count'];
						$layer_list = M('Mall_full_layer')->where($where)->order('goods_price desc')->limit(1)->find();

						if($layer_list){
							$coupon = M('Card_new_coupon')->where(array('coupon_id'=>$layer_list['coupon_id']))->find();
							$shop_good=D('Shop_goods')->where(array('goods_id'=>$layer_list['give_goods_id']))->find();
							$mall_full_layer = array(
								'coupon_name'=>$coupon['name'],
								'discount'=>$coupon['discount'],//优惠券抵扣钱数
								'coupon_id'=>$layer_list['coupon_id'],
								'shop_good_name'=>$shop_good['name'],
								'price'=>$shop_good['price'],
								'goods_id'=>$layer_list['give_goods_id'],
							);
						}
					}
				}
                $num = $row['num'];
                $spec_str = $row['spec_id'];
                $str = $row['spec'];
            }
            elseif ($isCookie == 1) {
                $goods_id = $row['productId'];
				if(!empty($goods_id)&&!$row['rep_id']){
					$full_list = D('Mall_full_act')->get_full_list($goods_id,$store_id);

					if(!empty($full_list['layer_num_id'])){
						$where['id'] = array('in',$full_list['layer_num_id']);
						$where['_string'] = "goods_price <= ".$row['productPrice']*$row['count'];
						$layer_list = M('Mall_full_layer')->where($where)->order('goods_price desc')->limit(1)->find();

						if($layer_list){
							$coupon = M('Card_new_coupon')->where(array('coupon_id'=>$layer_list['coupon_id']))->find();
							$shop_good=D('Shop_goods')->where(array('goods_id'=>$layer_list['give_goods_id']))->find();
							$mall_full_layer = array(
								'coupon_name'=>$layer_list['coupon_id']==0?"":$coupon['name'],
								'discount'=>$coupon['discount'],//优惠券抵扣钱数
								'coupon_id'=>$layer_list['coupon_id'],
								'shop_good_name'=>$shop_good['name'],
								'price'=>$shop_good['price'],
								'goods_id'=>$layer_list['give_goods_id'],
							);
						}
					}
					if(empty($row['count'])){
                        $row['count']=1;
                    }
                 /*--------------------------活动---------------------*/
				}
				if (!empty($row['specs_id'])>0) {
                    $mall_periodic_purchase_specs = M('Mall_periodic_purchase_specs')->where(array('specs_id' => $row['specs_id']))->find();
                }

                $num = $row['count'];
                $spec_ids = array();
                $str_s = array();
                $str_p = array();
                foreach ($row['productParam'] as $r) {
                    if ($r['type'] == 'spec') {
                        $spec_ids[] = $r['id'];
                        $str_s[] = $r['name'];
                    } else {
                        foreach ($r['data'] as $d) {
                            $str_p[] = $d['name'];
                        }
                    }
                }
                $spec_str = $spec_ids ? implode('_', $spec_ids) : '';

                $str = '';
                $str_s && $str = implode(',', $str_s);
                $str_p && $str = $str ? $str . '、' . implode(',', $str_p) : implode(',', $str_p);
            }
            elseif ($isCookie == 2) {
                $ids = explode('_', $row['goods_id']);
                $goods_id = array_shift($ids);
                $spec_str = $ids ? implode('_', $ids) : '';
                $num = $totalData[$goods_id . '_' . $spec_str];
                $weight = $totalData[$goods_id . '_' . $spec_str . 'w'];
            }
            if ($num*1 < 1 && !$weight) continue;
            $tNum = isset($totalData[$goods_id . '_' . $spec_str]) ? intval($totalData[$goods_id . '_' . $spec_str]) : $num;
            $tWeight = isset($totalData[$goods_id . '_' . $spec_str . 'w']) ? intval($totalData[$goods_id . '_' . $spec_str . 'w']) : $weight;
            //校验商品信息和库存，获取商品实际的单价
            /*重点*/
            if (!$mall_periodic_purchase_specs) {
                $mall_periodic_purchase_specs = array();
            }
			$original_price_type = false;//强制原价购买【定制米粒商城的功能】
			if($row['price_type']===1){
				$original_price_type = true;
			}
            $t_return = $this->check_stock($goods_id, $tNum, $spec_str, $store_shop['stock_type'], $store_id, $is_market, $uid, $group_id, $bar_id, $team_id, $tWeight, $row['host_goods_id'],$mall_periodic_purchase_specs,$original_price_type);
			
            if($mixed_freight){//商城模块运费既有“按最大值计算”又有“单独计算”，强制修改成单独计算
				$t_return['freight_type'] = 1;
			}
            if ($mall_periodic_purchase_specs) {
                $t_return['price'] = $mall_periodic_purchase_specs['specs_price'];
            }

            // 超值换购
            if ($row['rep_id']) {
                // $t_return['price'] = $row['productPrice'];
            }
			if ($isCookie == 2) {
                $str = str_replace(array($t_return['name'], '(', ')'), '', $row['spec_name']);
            }
            if ($is_market && $store['is_open_retail_pack'] == 0) {
                $t_return['packing_charge'] = 0;
            }

            if ($t_return['status'] == 0) {
                return array('error_code' => true, 'msg' => $t_return['msg']);
            } elseif ($t_return['status'] == 2) {
                return array('error_code' => true, 'msg' => $t_return['msg']);
            }

            $total += $num;

            if ($t_return['is_seckill_price']) {
                $is_discount = 1;
            }

            //处理每单中商品按优惠价计算的个数
            $oldNum = 0;
            $discountNum = 0;
            if ($t_return['limit_type'] == 0 && $t_return['maxNum'] > 0) {
                $tmpKey = $t_return['goods_id'] . ($spec_str ? '_' . $spec_str : '');
                if (isset($useGoods[$tmpKey])) {
                    $useNum = $useGoods[$tmpKey];
                    $discountNum = max(0, $t_return['maxNum'] - $useNum);
                    if ($num < $discountNum) {
                        $discountNum = $num;
                    }
                    $useGoods[$tmpKey] += $discountNum;
                } else {
                    if ($num > $t_return['maxNum']) {
                        $discountNum = $t_return['maxNum'];
                    } else {
                        $discountNum = $num;
                    }
                    $useGoods[$tmpKey] = $discountNum;
                }

                if ($row['host_goods_id'] == 0) {
                    if ($num > $discountNum) {
                        $oldNum = $num - $discountNum;
                    }
                }

                if ($oldNum > 0) {
                    $price += floatval($t_return['old_price'] * $oldNum);
                }
                if ($discountNum > 0) {
                    $price += floatval($t_return['price'] * $discountNum);
                }
            } else {
                if($row['sell_type'] == 2){
					$price += floatval($row['code_price']);
				}else{
					$price += floatval($t_return['price'] * $num);
                }
            }
            $extra_price += $t_return['extra_price'] * $num;
            $packing_charge += $t_return['packing_charge'] * $num;

            if ($address_id) {
                //-----计算运费--------  freight_type ==> 0:最大，1：单独
                $goods_weight_list = $this->get_goods_weight($t_return, $delivery_list, $user_adress, $goods_weight_list);
                /*不是按照重量计算的商品*/
                if (!isset($goods_weight_list[$goods_id])) {
                    if ($t_return['freight_type'] == 0) {//计件，按照最大配送费计算
                        $template_id = intval($t_return['freight_template']);
                        if ($user_adress) {
                            if (isset($delivery_list[$template_id][$user_adress['city']])) {
                                $express_freight_tmp = $delivery_list[$template_id][$user_adress['city']];
                            } elseif (isset($delivery_list[$template_id][$user_adress['province']])) {
                                $express_freight_tmp = $delivery_list[$template_id][$user_adress['province']];
                            } else {
                                $template_id = 0;
                                $express_freight_tmp = array('freight' => $t_return['freight_value'], 'full_money' => 0, 'tid' => 0);
                            }
                        } else {
                            $template_id = 0;
                            $express_freight_tmp = array('freight' => $t_return['freight_value'], 'full_money' => 0, 'tid' => 0);
                        }
						if($template_id===0){//其他区域的运费
							if ($max_freight < $t_return['freight_value']) {
								$express_freight = $express_freight_tmp;
								$max_freight = $t_return['freight_value'];
							}
						}else{
							$fee = $this->express_fee_by_piece($express_freight_tmp,$num);
							if ($max_freight < $fee) {
								$express_freight = $express_freight_tmp;
								$max_freight = $fee;
							}

						}

//						if ($max_freight < $express_freight_tmp['freight']) {
//							$express_freight = $express_freight_tmp;
//							$max_freight = $express_freight_tmp['freight'];
//						}

                        $template_total_price += $t_return['price'] * $num;
						$delivery_money_total = $max_freight;
                    } else {//计件，按照各件叠加算总和
                        if (!in_array($goods_id, $goods_id_array)) {
                            $template_id = intval($t_return['freight_template']);
                            if ($user_adress) {
                                if (isset($delivery_list[$template_id][$user_adress['city']])) {
                                    //$delivery_money_total += $delivery_list[$template_id][$user_adress['city']]['freight'];
                                    $delivery_money_total += $this->express_fee_by_piece($delivery_list[$template_id][$user_adress['city']],$num);
                                } elseif (isset($delivery_list[$template_id][$user_adress['province']])) {
                                    //$delivery_money_total += $delivery_list[$template_id][$user_adress['province']]['freight'];
                                    $delivery_money_total += $this->express_fee_by_piece($delivery_list[$template_id][$user_adress['province']],$num);
                                } else {
                                    $delivery_money_total += $t_return['freight_value'];
                                }
                            }
                            $goods_id_array[] = $goods_id;
                        }
                    }
                }
            }
            //折扣($sorts_discout[$t_return['sort_id']]['discount_type'] == 1 ? '分类折扣' : '店铺折扣')
            $t_discount = isset($sorts_discout[$t_return['sort_id']]['discount']) && $sorts_discout[$t_return['sort_id']]['discount'] ? $sorts_discout[$t_return['sort_id']]['discount'] : 100;

            //该商品的折扣类型 0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
            $discount_type = 0;
            //折扣率 0：无折扣
            $discount_rate = 0;
            if ($t_return['is_discount'] == 0) {
                if ($t_discount < 100) {
                    $noDiscountGoods .= L_("【X1】", array("X1" => $t_return['name']));
                }
                $t_discount = 100;
            }
            if ($t_discount < 100) {
                if ($sorts_discout[$t_return['sort_id']]['discount_type']) {//分类折扣
                    $discount_type = 2;
                } else {
                    $discount_type = 1;
                }
                $discount_rate = $t_discount;
                $is_discount = 1;
            }
            if ($oldNum > 0) {
                $num = $oldNum;
                $tempPrice = $t_return['old_price'];
                $this_goods_total_price = $num * round($tempPrice * $t_discount * 0.01, 2);//本商品的折扣总价
                $only_discount_price = round($tempPrice * $t_discount * 0.01, 2);
                if ($sorts_discout['discount_type'] == 0) {//折上折
                    if ($vip_discount < 100) {
                        $is_discount = 1;
                        $discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
                        $discount_rate = $discount_rate ? $discount_rate . ',' . $vip_discount : $vip_discount;
                    }
                    if (C('config.vip_level_discount_sequence') == 0) { //正常计算
                        $thisGoodsTotalPrice = $this_goods_total_price;
                        $this_goods_total_price = round($this_goods_total_price * $vip_discount * 0.01, 2);//本商品的VIP折扣后的总价
                        $thisGoodsVipReduce = round($thisGoodsTotalPrice - $this_goods_total_price, 2);
                        $only_discount_price = round($only_discount_price * $vip_discount * 0.01, 2);//本商品的VIP折扣的单价
                    }
                } else {//折扣最优
                    $t_vip_price = $num * round($tempPrice * $vip_discount * 0.01, 2);
                    if ($t_vip_price < $this_goods_total_price) {
                        if (C('config.vip_level_discount_sequence') == 0) { //正常计算
                            $this_goods_total_price = $t_vip_price;
                            $thisGoodsTotalPrice = round($num * $t_return['price'], 2);
                            $thisGoodsVipReduce = round($thisGoodsTotalPrice - $this_goods_total_price, 2);
                        } elseif (C('config.vip_level_discount_sequence') == 1) { //满减后计算
                            $optimalMoney = round($num * $tempPrice, 2);
                        }
                        if ($vip_discount < 100) {
                            $is_discount = 1;
                            $discount_type = 3;
                            $discount_rate = $vip_discount;
                        }
                        if (C('config.vip_level_discount_sequence') == 0) { //正常计算
                            $only_discount_price = round($tempPrice * $vip_discount * 0.01, 2);
                        }
                    }
                }
                if(!empty($vipMinusPrice)){
					$is_discount = 1;
					$discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
					$discount_rate = $vip_discount;
					$thisGoodsVipReduce = ($tempPrice > $vipMinusPrice?$vipMinusPrice : $tempPrice)*$num;
				}
				$only_discount_price = getFormatNumber($only_discount_price - $vipMinusPrice, 2, false);
				$this_goods_total_price = getFormatNumber($this_goods_total_price - $vipMinusPrice*$num, 2, false);
				
                $optimalDiscountMoney += $optimalMoney;
                $vip_level_reduce_money += $thisGoodsVipReduce;

                //附属商品不优惠
                if($row['host_goods_id'] > 0){
                    $this_goods_total_price = $t_return['price'] * $row['count'];
                }

                $store_discount_money += $this_goods_total_price;
                
                if ($t_return['is_use_coupon']) {
                    $useCouponMeny += $this_goods_total_price;
                }
                $str = '';
                $str_s && $str = implode(',', $str_s);
                $str_p && $str = $str ? $str . ';' . implode(',', $str_p) : implode(',', $str_p);

                $goods[] = array(
                    'name' => $row['productName'] ? $row['productName'] : $row['name'],
                    'packname' => $row['packname'] ? $row['packname'] : '',
                    'is_seckill_price' => false,//是否是秒杀价(0:否，1：是),超出限购数量使用原价
                    'discount_type' => $discount_type,//0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                    'discount_rate' => $discount_rate,//折扣率
                    'num' => $num,
                    'goods_id' => $goods_id,
                    'old_price' => floatval($t_return['old_price']),//商品原始价
                    'price' => floatval($tempPrice),//是秒杀的时候是秒杀价，不是的时候是原始价
                    'discount_price' => floatval($only_discount_price),//折扣价
                    'cost_price' => floatval($t_return['cost_price']),
                    'number' => $t_return['number'],
                    'image' => $t_return['image'],
                    'sort_id' => $t_return['sort_id'],
                    'packing_charge' => $t_return['packing_charge'],
                    'unit' => $t_return['unit'],
                    'str' => $str,
                    'spec_id' => $spec_str,
                    'extra_price' => $t_return['extra_price'],
                    'is_use_coupon' => $t_return['is_use_coupon'],
                    'jd_sku_id' => $t_return['jd_sku_id'],
                    'this_goods_vip_reduce' => $thisGoodsVipReduce,
                    'sell_type' => $t_return['sell_type'],
                    'goods_weight' => $t_return['goods_weight'],
                    'code_weight' => $row['code_weight'],
                    'code_price' => $row['code_price'],
                    'host_goods_id' => isset($row['host_goods_id']) ? $row['host_goods_id'] : 0, //附属商品关联的主商品ID
                    'uniqueness_number' => isset($row['uniqueness_number']) ? $row['uniqueness_number'] : 0 ,//附属商品与主商品的相同唯一ID
                    'periodic_appoint_day' => $row['periodic_appoint_day'],//周期购商品指定日期

					'mall_full_layer'=>empty($mall_full_layer)?[]:$mall_full_layer,
                    'specs_id' => $row['specs_id']>0 ? $row['specs_id'] : 0,
                    'specs_name' => $mall_periodic_purchase_specs?$mall_periodic_purchase_specs['specs_name']:'',
                    'specs_price' => $mall_periodic_purchase_specs?$mall_periodic_purchase_specs['specs_price']:0,
                    'periodic_num' => $mall_periodic_purchase_specs?$mall_periodic_purchase_specs['periodic_num']:0,
                    'specs_stock' => $mall_periodic_purchase_specs?$mall_periodic_purchase_specs['specs_stock']:0,
                    'rep_id' => $row['rep_id']>0 ? $row['rep_id'] : 0,//超值换购活动id
                    'in_the_preferential' => 1  //是否参与优惠，仅作为标记使用
                );
                $num = $discountNum;
            }
            if ($num > 0 || $row['code_price']) {
				if($row['sell_type'] == 2){
					$this_goods_total_price = round($row['code_price'] * $t_discount * 0.01, 2);
				}else{
					$this_goods_total_price = $num * round($t_return['price'] * $t_discount * 0.01, 2);
				}

				// 超值换购不参与折扣
				if ($row['rep_id']) {
					$this_goods_total_price = 0;
				}
                //$this_goods_total_price = $num * round($t_return['price'] * $t_discount * 0.01, 2) + round($row['code_price'] * $t_discount * 0.01, 2);//本商品的店铺折扣后的总价
                $only_discount_price = round($t_return['price'] * $t_discount * 0.01, 2);//本商品的店铺折扣的单价
                if ($sorts_discout['discount_type'] == 0) {//折上折
                    if ($vip_discount < 100) {
                        $is_discount = 1;
                        $discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
                        $discount_rate = $discount_rate ? $discount_rate . ',' . $vip_discount : $vip_discount;
                    }
                    if (C('config.vip_level_discount_sequence') == 0) { //正常计算
                        $thisGoodsTotalPrice = $this_goods_total_price;
                        $this_goods_total_price = round($this_goods_total_price * $vip_discount * 0.01, 2);//本商品的VIP折扣后的总价
                        $thisGoodsVipReduce = round($thisGoodsTotalPrice - $this_goods_total_price, 2);
                        $only_discount_price = round($only_discount_price * $vip_discount * 0.01, 2);//本商品的VIP折扣的单价
                    }
                } else {//折扣最优
					if($row['sell_type'] == 2){
						$t_vip_price = round($row['code_price'] * $vip_discount * 0.01, 2);
					}else{
						$t_vip_price = $num * round($t_return['price'] * $vip_discount * 0.01, 2);
					}
                    $t_vip_price = $num * round($t_return['price'] * $vip_discount * 0.01, 2) + round($row['code_price'] * $vip_discount * 0.01, 2);
                    if ($t_vip_price < $this_goods_total_price) {
                        if (C('config.vip_level_discount_sequence') == 0) { //正常计算
                            $this_goods_total_price = $t_vip_price;
                            $thisGoodsTotalPrice = round($num * $t_return['price'], 2) + $row['code_price'];
                            $thisGoodsVipReduce = round($thisGoodsTotalPrice - $this_goods_total_price, 2);
                        } elseif (C('config.vip_level_discount_sequence') == 1) { //满减后计算
                            $optimalMoney = round($num * $t_return['price'], 2) + $row['code_price'];
                        }
                        if ($vip_discount < 100) {
                            $is_discount = 1;
                            $discount_type = 3;
                            $discount_rate = $vip_discount;
                        }
                        if (C('config.vip_level_discount_sequence') == 0) { //正常计算
                            $only_discount_price = round($t_return['price'] * $vip_discount * 0.01, 2);
                        }
                    }
                }
				if(!empty($vipMinusPrice)){
					$is_discount = 1;
					$discount_type = $discount_type == 2 ? 5 : ($discount_type == 1 ? 4 : 3);
					$discount_rate = $vip_discount;
					$thisGoodsVipReduce = ($t_return['price'] > $vipMinusPrice?$vipMinusPrice : $t_return['price'])*$num;
				}
                $optimalDiscountMoney += $optimalMoney;
                $vip_level_reduce_money += $thisGoodsVipReduce;
				
				$only_discount_price = getFormatNumber($only_discount_price - $vipMinusPrice, 2, false);
				$this_goods_total_price = getFormatNumber($this_goods_total_price - $vipMinusPrice*$num, 2, false);

                //附属商品不优惠
                if($row['host_goods_id'] > 0){
                    $this_goods_total_price = $t_return['price'] * $row['count'];
                }
                $store_discount_money += $this_goods_total_price;//折扣后的商品总价（店铺，分类，VIP折扣都计算在内）

                if ($t_return['is_use_coupon']) {
                    $useCouponMeny += $this_goods_total_price;
                }

                // 超值换购商品不参与其他任何折扣
                if($row['rep_id'] > 0){
                	$only_discount_price = round($row['productPrice'], 2);//本商品的店铺折扣的单价
                    $this_goods_total_price = $t_return['price'] * $row['count'];
                    $discount_type = 0;
                    $discount_rate = 0;
                }
				$total_score += $t_return['account_score'];
                $goods[] = array(
                    'name' => $row['productName'] ? $row['productName'] : $row['name'],
                    'packname' => $row['packname'] ? $row['packname'] : '',
                    'is_seckill_price' => $t_return['is_seckill_price'],//是否是秒杀价(0:否，1：是)
                    'discount_type' => $discount_type,//0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
                    'discount_rate' => $discount_rate,//折扣率
                    'num' => $num,
                    'goods_id' => $goods_id,
                    'old_price' => getFormatNumber($t_return['old_price']),//商品原始价
                    'price' => getFormatNumber($t_return['price']),
                    'price_type' => floatval($t_return['price_type']),
                    'account_money' => floatval($t_return['account_money']),
                    'account_score' => floatval($t_return['account_score']),
                    'discount_price' => floatval($only_discount_price),//折扣价
                    'cost_price' => floatval($t_return['cost_price']),
                    'number' => $t_return['number'],
                    'weight' => $t_return['weight'],
                    'weight_unit' => $t_return['weight_unit'],
                    'image' => $t_return['image'],
                    'sort_id' => $t_return['sort_id'],
                    'packing_charge' => $t_return['packing_charge'],
                    'unit' => $t_return['unit'],
                    'str' => $str,
                    'spec_id' => $spec_str,
                    'extra_price' => $t_return['extra_price'],
                    'is_use_coupon' => $t_return['is_use_coupon'],
                    'jd_sku_id' => $t_return['jd_sku_id'],
                    'this_goods_vip_reduce' => $thisGoodsVipReduce,
                    'sell_type' => $t_return['sell_type'],
                    'goods_weight' => $t_return['goods_weight'],
                    'code_weight' => $row['code_weight'],
                    'code_price' => $row['code_price'],
                    'host_goods_id' => isset($row['host_goods_id']) ? $row['host_goods_id'] : 0, //附属商品关联的主商品ID
                    'uniqueness_number' => isset($row['uniqueness_number']) ? $row['uniqueness_number'] : 0 ,//附属商品与主商品的相同唯一ID
                    'periodic_appoint_day' => $row['periodic_appoint_day'],//周期购商品指定日期

					'mall_full_layer'=>empty($mall_full_layer)?[]:$mall_full_layer,
                    'specs_id' => $row['specs_id']>0 ? $row['specs_id'] : 0,
                    'specs_name' => $mall_periodic_purchase_specs?$mall_periodic_purchase_specs['specs_name']:'',
                    'specs_price' => $mall_periodic_purchase_specs?$mall_periodic_purchase_specs['specs_price']:0,
                    'periodic_num' => $mall_periodic_purchase_specs?$mall_periodic_purchase_specs['periodic_num']:0,
                    'specs_stock' => $mall_periodic_purchase_specs?$mall_periodic_purchase_specs['specs_stock']:0,
                    'rep_id' => $row['rep_id']>0 ? $row['rep_id'] : 0,//超值换购活动id
                    'in_the_preferential' => 0  //是否参与优惠，仅作为标记使用
                );
            }
        }
        //无规格的附属商品价格重新计算
        foreach ($goods as $key => $value){
            if($value['host_goods_id'] > 0 AND empty($value['str'])){
                $where['goods_id'] = $value['goods_id'];
                $where['host_goods_id'] = $value['host_goods_id'];
                $where['is_delete'] = 0;
                $subsidiary_goods_price = M('Shop_subsidiary_piece_goods')->where($where)->getField('price');
                if(!$subsidiary_goods_price){
                    $subsidiary_goods_price = $value['price'];
                }

                $goods[$key]['is_seckill_price'] = false;
                $goods[$key]['discount_type'] = 0;
                $goods[$key]['discount_rate'] = 0;
                $goods[$key]['old_price'] = $subsidiary_goods_price;//原价
                $goods[$key]['price'] = $subsidiary_goods_price;
                $goods[$key]['discount_price'] = $subsidiary_goods_price;//折扣价
                //$goods[$key]['cost_price'] = $subsidiary_goods_price;
                $goods[$key]['extra_price'] = 0;
                $goods[$key]['code_price'] = NULL;
            }
			$goods_mer_score_rate = M('Shop_goods')->where(['goods_id'=>$value['goods_id']])->getField('mer_score_rate');
			$goods[$key]['mer_score_rate'] = !empty($goods_mer_score_rate)?$goods_mer_score_rate:0;
        }
        /*比较按照重量计算运费的按照计件运算运费*/
        foreach ($goods_weight_list as $k => $v) {
            $delivery_info = $v['delivery_info'];
            $total_weight = $v['total_weight'];
            if ($v['weight_unit'] == 1) {
                $total_weight = bcdiv($total_weight, 1000, 5);
            }
            $postage = 0;
            if ($delivery_info['first_freight'] <= 0 && $delivery_info['add_freight'] <= 0) {
                $postage = 0;
            } elseif ($delivery_info['add_weight'] <= 0) {
                $postage = $delivery_info['first_freight'];
            } else {
                $postage = $delivery_info['first_freight'];
                if ($total_weight > $delivery_info['first_weight']) {    //超出首重
                    $scale = floor(($total_weight - $delivery_info['first_weight']) / $delivery_info['add_weight']);
                    $postage += $scale * $delivery_info['add_freight'];
                }
            }
            if ($v['freight_type'] == 0) {

                if ($max_freight < $postage) {
                    $express_freight = $delivery_info;
                    $express_freight['freight'] = $postage;
                    $max_freight = $postage;
                }
                $template_total_price += $v['total_price'];
                $delivery_money_total = $max_freight;
            } else {
                $delivery_money_total += $postage;
            }
        }
        $minus_price = 0;
        //会员等级优惠  外卖费不参加优惠
        $vip_discount_money = round($store_discount_money, 2);
        //$discounts = D('Shop_discount')->get_discount_byids(array($store_id));
        if ($group_id > 0 || $bar_id > 0) {    //拼团砍价不享受平台/店铺（满减、折扣）优惠
            $discounts = array();
        }
        else {
			$usage_scenario = -1;
            if (empty($_POST['deliver_type'])) {
                if ($store['deliver_type'] == 2) {//自提
                    $usage_scenario = 2;
                } else if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3){//平台配送
                    $usage_scenario = 1;
                }else{
                    $usage_scenario = 1;
                }
            } else {
                if ($_POST['deliver_type'] == 1) {//自提
                    $usage_scenario = 2;
                } else {
                    $usage_scenario = 1;
                }
            }
			if($usage_scenario != -1){
				$discounts = D('Shop_discount')->getDiscounts($store['mer_id'], $store_id, $shop_type, $usage_scenario);
			}else{
				$discounts = array();
			}
        }

        $discount_list = null;
        //优惠
        $sys_first_reduce = 0;//平台首单优惠
        $sto_first_reduce = 0;//店铺首单优惠
        $sys_full_reduce = 0;//平台满减
        $sto_full_reduce = 0;//店铺满减
        $platform_merchant = 0;//平台优惠中商家补贴的总和统计
        $platform_plat = 0;//平台优惠中平台补贴的总和统计

        if ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, 0, $is_discount, '', 1)) {
            $dd_tmp = array();
            $dd_tmp['discount_type'] = 6;//平台满额打折
            $dd_tmp['name'] = $d_tmp['name'];
            $dd_tmp['money'] = $d_tmp['full_money'];
            $dd_tmp['minus'] = $d_tmp['reduce_money'];
            $dd_tmp['minus2'] = round($vip_discount_money * (1 - $d_tmp['reduce_money']),2);//减了多少
            $dd_tmp['did'] = $d_tmp['id'];
            $dd_tmp['plat_money'] = $d_tmp['plat_money'];
            $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
            $dd_tmp['order_count'] = $d_tmp['order_count'];
            $dd_tmp['percentage_status'] = $d_tmp['percentage_status'];
			$dd_tmp['type'] = $d_tmp['type'];
            $discount_list['plat_discount'] = $dd_tmp;

            //打折 优惠的价格=价格*(1-折扣率)
            $platform_merchant =  round($dd_tmp['minus2'] * $d_tmp['merchant_money'],2);//平台优惠中商家补贴的
            $platform_plat =  round($dd_tmp['minus2'] * $d_tmp['plat_money'],2);//平台优惠中平台补贴的

            $sys_full_reduce += $platform_plat;
	        $sto_full_reduce += $platform_merchant;
        }

        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, 0, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 6, 'money' => $dt['full_money'], 'minus' => $vip_discount_money * (1 - $d_tmp['reduce_money']));
                }
            }
        }

        $shopOrderDB = D("Shop_order");
        $sys_count = $shopOrderDB->where(array('uid' => $uid, 'paid' => 1, 'status' => array('not in', '4,5')))->count();
        if ($discounts) {
            $ok = 0;
            foreach ($discounts as $key => $value) {
                foreach ($value as $k => $v) {
                    if ($v['order_count'] > $sys_count) {
                        $ok = 1;
                    }
                }
            }
        }
		$sys_first_reduce_type = 0;//平台首单优惠的类型，如果是减免运费的话，后面需要和运费做个比较，优惠的最大金额不能超过总运费。
        if ($ok && $uid) {//平台首单优惠
            if ($d_tmp = $this->getReduce($discounts, 0, $vip_discount_money, 0, $is_discount, $sys_count)) {
            	$dd_tmp = array();
                $dd_tmp['discount_type'] = 1;//平台首单
                $dd_tmp['name'] = $d_tmp['name'];
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['id'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $dd_tmp['order_count'] = $d_tmp['order_count'];
				$dd_tmp['type'] = $d_tmp['type'];
				if($d_tmp['type']==2){
					$sys_first_reduce_type = 1;//如果是1的话，需要和配送费比较。
				}
                $discount_list['system_newuser'] = $dd_tmp;
                if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                    $sys_first_reduce += $d_tmp['plat_money'];
                    $platform_plat += $d_tmp['plat_money'];
                    $sto_first_reduce += $d_tmp['merchant_money'];
                    $platform_merchant += $d_tmp['merchant_money'];
                } else {
                    $sys_first_reduce += $d_tmp['reduce_money'];
                    $platform_plat += $d_tmp['reduce_money'];
                }
            }
            if ($d_tmp = $this->getNoShareReduce($discounts, 0, $vip_discount_money, 0, $is_discount, $sys_count)) {
                foreach ($d_tmp as $dt) {
                    if ($dt['is_share'] == 0) {
                        $noDiscountList[] = array('type' => 1, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                    }
                }
            }
        }

        if ($uid && ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, 0, $is_discount))) {
        	// 折扣与满减不同享
        	if (!$discount_list['plat_discount'] || $discount_list['plat_discount']['minus2'] < $d_tmp['reduce_money']) {
        		if ($discount_list['plat_discount']) {
        			$sto_full_reduce -= floatval($discount_list['plat_discount']['minus2']);
	        		$platform_merchant -= round($discount_list['plat_discount']['minus2'] * $discount_list['plat_discount']['plat_money'],2);
	            	$platform_plat -=  round($discount_list['plat_discount']['minus2'] * $discount_list['plat_discount']['merchant_money'],2);
        		}
        		//平台优惠中平台补贴的

            	$dd_tmp = array();
	        	$dd_tmp['discount_type'] = 2;//平台满减
	            $dd_tmp['name'] = $d_tmp['name'];
	            $dd_tmp['money'] = $d_tmp['full_money'];
	            $dd_tmp['minus'] = $d_tmp['reduce_money'];
	            $dd_tmp['did'] = $d_tmp['id'];
	            $dd_tmp['plat_money'] = $d_tmp['plat_money'];
	            $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
	            $dd_tmp['order_count'] = $d_tmp['order_count'];
				$dd_tmp['type'] = $d_tmp['type'];
	            $discount_list['system_minus'] = $dd_tmp;
	            if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
	                $sys_full_reduce += $d_tmp['plat_money'];
	                $platform_plat += $d_tmp['plat_money'];
	                $sto_full_reduce += $d_tmp['merchant_money'];
	                $platform_merchant += $d_tmp['merchant_money'];
	            } else {
	                $sys_full_reduce += $d_tmp['reduce_money'];
	                $platform_plat += $d_tmp['reduce_money'];
	            }
	            unset($discount_list['plat_discount']);
        	}

        }
        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, 0, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 2, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                }
            }
        }

        $sto_count = $shopOrderDB->where(array('uid' => $uid, 'store_id' => $store_id, 'paid' => 1, 'status' => array('not in', '4,5')))->count();
        if (empty($sto_count)) {
            if ($d_tmp = $this->getReduce($discounts, 0, $vip_discount_money, $store_id, $is_discount)) {
            	$dd_tmp = array();
                $dd_tmp['discount_type'] = 3;//店铺首单
                $dd_tmp['name'] = $d_tmp['name'];
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['checkCartid'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $dd_tmp['order_count'] = $d_tmp['order_count'];
				$dd_tmp['type'] = $d_tmp['type'];
                $discount_list['newuser'] = $dd_tmp;
                $sto_first_reduce += $d_tmp['reduce_money'];
            }
            if ($d_tmp = $this->getNoShareReduce($discounts, 0, $vip_discount_money, $store_id, $is_discount)) {
                foreach ($d_tmp as $dt) {
                    if ($dt['is_share'] == 0) {
                        $noDiscountList[] = array('type' => 3, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                    }
                }
            }
        }

        if ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, $store_id, $is_discount)) {
            $dd_tmp = array();
            $dd_tmp['discount_type'] = 4;//店铺满减
            $dd_tmp['name'] = $d_tmp['name'];
            $dd_tmp['money'] = $d_tmp['full_money'];
            $dd_tmp['minus'] = $d_tmp['reduce_money'];
            $dd_tmp['did'] = $d_tmp['id'];
            $dd_tmp['plat_money'] = $d_tmp['plat_money'];
            $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
            $dd_tmp['order_count'] = $d_tmp['order_count'];
			$dd_tmp['type'] = $d_tmp['type'];
            $discount_list['minus'] = $dd_tmp;
            $sto_full_reduce += $d_tmp['reduce_money'];
        }
        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, $store_id, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 4, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                }
            }
        }

        //起步运费
        $delivery_fee = 0;
        //超出距离部分的单价
        $per_km_price = 0;
        //起步距离
        $basic_distance = 0;
        //减免配送费的金额
        $delivery_fee_reduce = 0;
        $plat_reduce_deliver_money = 0;
        $merchant_reduce_deliver_money = 0;

        //起步运费
        $delivery_fee2 = 0;
        //超出距离部分的单价
        $per_km_price2 = 0;
        //起步距离
        $basic_distance2 = 0;

        $deliverReturn = D('Deliver_set')->getDeliverInfo($store, $price);
        $delivery_fee = $deliverReturn['delivery_fee'];
        $per_km_price = $deliverReturn['per_km_price'];
        $basic_distance = $deliverReturn['basic_distance'];
        $delivery_fee2 = $deliverReturn['delivery_fee2'];
        $per_km_price2 = $deliverReturn['per_km_price2'];
        $basic_distance2 = $deliverReturn['basic_distance2'];
        $delivery_fee3 = $deliverReturn['delivery_fee3'];
        $per_km_price3 = $deliverReturn['per_km_price3'];
        $basic_distance3 = $deliverReturn['basic_distance3'];
        $store['delivertime_start'] = $deliverReturn['delivertime_start'];
        $store['delivertime_stop'] = $deliverReturn['delivertime_stop'];
        $store['delivertime_start2'] = $deliverReturn['delivertime_start2'];
        $store['delivertime_stop2'] = $deliverReturn['delivertime_stop2'];
        $store['delivertime_start3'] = $deliverReturn['delivertime_start3'];
        $store['delivertime_stop3'] = $deliverReturn['delivertime_stop3'];

        if ($store_shop['deliver_type'] == 0 || $store_shop['deliver_type'] == 3) {//平台配送|平台或自提
            //时段起送价
            $start_time = $deliverReturn['delivertime_start'];
            $stop_time = $deliverReturn['delivertime_stop'];

            $start_time2 = $deliverReturn['delivertime_start2'];
            $stop_time2 = $deliverReturn['delivertime_stop2'];

            $start_time3 = $deliverReturn['delivertime_start3'];
            $stop_time3 = $deliverReturn['delivertime_stop3'];
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
                        $store['basic_price'] = floatval($store['s_basic_price1']) > -1 ? floatval($store['s_basic_price1']) : (floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval(C('config.basic_price1')) ? floatval(C('config.basic_price1')) : floatval(C('config.basic_price')))));//起送价
                    } else {
                        $store['basic_price'] = floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval(C('config.basic_price1')) ? floatval(C('config.basic_price1')) : floatval(C('config.basic_price'))));//起送价
                    }
                } else {
                    $store['basic_price'] = floatval($store['basic_price1']) ? floatval($store['basic_price1']) : floatval($store['basic_price']);//起送价
                }
            } elseif ($selectDeliverTime == 2) {
                if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
                    if ($store['s_is_open_own'] == 1) {
                        $store['basic_price'] = floatval($store['s_basic_price2']) > -1 ? floatval($store['s_basic_price2']) : (floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval(C('config.basic_price2')) ? floatval(C('config.basic_price2')) : floatval(C('config.basic_price')))));//起送价
                    } else {
                        $store['basic_price'] = floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval(C('config.basic_price2')) ? floatval(C('config.basic_price2')) : floatval(C('config.basic_price'))));//起送价
                    }
                } else {
                    $store['basic_price'] = floatval($store['basic_price2']) ? floatval($store['basic_price2']) : floatval($store['basic_price']);//起送价
                }

            } elseif ($selectDeliverTime == 3) {
                if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
                    if ($store['s_is_open_own'] == 1) {
                        $store['basic_price'] = floatval($store['s_basic_price3']) > -1 ? floatval($store['s_basic_price3']) : (floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval(C('config.basic_price3')) ? floatval(C('config.basic_price3')) : floatval(C('config.basic_price')))));//起送价
                    } else {
                        $store['basic_price'] = floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval(C('config.basic_price3')) ? floatval(C('config.basic_price3')) : floatval(C('config.basic_price'))));//起送价
                    }
                } else {
                    $store['basic_price'] = floatval($store['basic_price3']) ? floatval($store['basic_price3']) : floatval($store['basic_price']);//起送价
                }
            } else {
                if ($store['deliver_type'] == 0 || $store['deliver_type'] == 3) {
                    $store['basic_price'] = floatval($store['s_basic_price']) > -1 ? floatval($store['s_basic_price']) : floatval(C('config.basic_price'));//起送价
                } else {
                    $store['basic_price'] = floatval($store['basic_price']);//起送价
                }
            }

            $store['extra_price'] = floatval($store_shop['s_extra_price']) ? floatval($store_shop['s_extra_price']) : floatval(C('config.extra_price'));
            //使用平台的优惠（配送费的减免）
            if ($d_tmp = $this->getReduce($discounts, 2, $price,0,0)) {
            	$dd_tmp = array();
                $dd_tmp['discount_type'] = 5;//平台配送费满减
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['id'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
				$dd_tmp['type'] = $d_tmp['type'];
                $discount_list['delivery'] = $dd_tmp;
                $delivery_fee_reduce = $d_tmp['reduce_money'];
                if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                    $plat_reduce_deliver_money += $d_tmp['plat_money'];
                    $platform_plat += $d_tmp['plat_money'];
                    $merchant_reduce_deliver_money += $d_tmp['merchant_money'];
                    $platform_merchant += $d_tmp['merchant_money'];
                } else {
                    $plat_reduce_deliver_money += $d_tmp['reduce_money'];
                    $platform_plat += $d_tmp['reduce_money'];
                }
            }
            //使用平台的优惠（配送费的折扣）
           if ($d_tmp = $this->getReduce($discounts, 2, $price,0,1,0,1)) {
                $dd_tmp['discount_type'] = 5;//平台配送费折扣
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
	            $dd_tmp['did'] = $d_tmp['id'];
	            $dd_tmp['plat_money'] = $d_tmp['plat_money'];
	            $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
	            $dd_tmp['order_count'] = $d_tmp['order_count'];
	            $dd_tmp['percentage_status'] = $d_tmp['percentage_status'];
                $discount_list['delivery_zhekou'] = $dd_tmp;
            }
        }
		$pack_info = D('Mall_goods_pack')->get_pack_price($goods,$store_id);//一口价详情
		if($pack_info){
			$sys_first_reduce = 0;//平台首单优惠
			$sto_first_reduce = 0;//店铺首单优惠
			$sys_full_reduce = 0;//平台满减
			$sto_full_reduce = 0;//店铺满减
			$discount_list = array();
		}
		//dump($pack_info['pack_act_list']);exit;
		//$vip_discount_money =$pack_info['pack_total_price']?$pack_info['pack_total_price']:$vip_discount_money;
        //判断店铺是根据订单还是根据商品收取打包费
        if ($store_shop['pack_type'] == 2) {
            $packing_charge = $store_shop['pack_fee'];
        }
		if ($is_market && $store['is_open_retail_pack'] == 0) {
			$packing_charge = 0;
		}
		$vip_discount_money =($pack_info['pack_total_price']&&$pack_info['pack_total_price']<=$vip_discount_money)?$pack_info['pack_total_price']:$vip_discount_money;


        if (empty($goods)) {
            return array('error_code' => true, 'msg' => L_('购物车是空的'));
        } else {
            $data = array('error_code' => false);
            $data['total'] = $total;
            $data['price'] = $price;//商品实际总价
            $data['extra_price'] = $extra_price;//商品实际总价
            $data['discount_price'] = $vip_discount_money;//折扣后的总价
            $data['goods'] = $goods;
            $data['store_id'] = $store_id;
            $data['mer_id'] = $mer_id;
            $data['store'] = $store;
            $data['discount_list'] = $discount_list;
            $data['delivery_type'] = $store_shop['deliver_type'];
            $data['sys_first_reduce'] = $sys_first_reduce;//平台新单优惠的金额
			$data['sys_first_reduce_type'] = $sys_first_reduce_type;//平台新单优惠的类型：1配送费
            $data['sys_full_reduce'] = $sys_full_reduce;//平台满减优惠的金额
            $data['sto_first_reduce'] = $sto_first_reduce;//店铺新单优惠的金额
            $data['sto_full_reduce'] = $sto_full_reduce;//店铺满减优惠的金额
            $data['can_discount_money'] = $useCouponMeny;//可用商家优惠券的总价
            $data['platform_merchant'] = $platform_merchant;//平台优惠中商家补贴的总和统计
            $data['platform_plat'] = $platform_plat;//平台优惠中平台补贴的总和统计
            $data['store_discount_money'] = $store_discount_money;//店铺折扣后的总价
            $data['vip_discount_money'] = $vip_discount_money;//VIP折扣后的总价
            $data['packing_charge'] = $packing_charge;//总的打包费
            $data['delivery_fee'] = $delivery_fee;//起步配送费
            $data['sorts_discount_type'] = $sorts_discout['discount_type'];
            $data['discount_type'] = $discount_type;//该商品的折扣类型 0:无折扣1：店铺折扣，2：分类折扣，3：VIP折扣，4:店铺+VIP折扣，5:分类+VIP折扣
            $data['vip_discount'] = $vip_discount;//会员等级折扣
            $data['store_discount'] = $t_discount;    //店铺/分类折扣
            $data['optimalDiscountMoney'] = $optimalDiscountMoney;//折扣最优的计算金额
            $data['vip_level_reduce_money'] = $vip_level_reduce_money;//正常计算的会员等级优惠金额
            if ($address_id) {
                $full_money = floatval($express_freight['full_money']);
                if (!($full_money != 0 && $template_total_price >= $full_money)) {//不满足免邮活动
					if ($store_shop['deliver_type'] != 0 && $store_shop['deliver_type'] != 3) {
						$data['delivery_fee'] = $delivery_money_total;//起步配送费
					}
					if($shop_type==2 && ($store_shop['mall_deliver_type'] == 3 || $store_shop['mall_deliver_type'] == 5 || $store_shop['mall_deliver_type'] == 8)){//商城的快递或者自提方式下用给赋值,快递配送给赋值
                       $data['delivery_fee'] =$delivery_money_total;
                     }
                }elseif($shop_type==2){//商城订单，满足包邮活动，配送费为0
					$data['delivery_fee'] = 0;
				}
            }elseif($shop_type==2){//商城订单未选择配送地址，配送费为0
				$data['delivery_fee'] = 0;
			}

            $data['basic_distance'] = $basic_distance;//起步距离
            $data['per_km_price'] = $per_km_price;//超出起步距离部分的距离每公里的单价
            $data['delivery_fee_reduce'] = $delivery_fee_reduce;//配送费减免的金额
            //平台配送时配送费满减平台和商家各自承担的金额
            $data['plat_reduce_deliver_money'] = $plat_reduce_deliver_money; //平台减免的配送费
            $data['merchant_reduce_deliver_money'] = $merchant_reduce_deliver_money;//商家减免的配送金额
            $data['delivery_fee2'] = $delivery_fee2;//起步配送费
            $data['basic_distance2'] = $basic_distance2;//起步距离
            $data['per_km_price2'] = $per_km_price2;//超出起步距离部分的距离每公里的单价
            $data['delivery_fee3'] = $delivery_fee3;//起步配送费
            $data['basic_distance3'] = $basic_distance3;//起步距离
            $data['per_km_price3'] = $per_km_price3;//超出起步距离部分的距离每公里的单价
            $data['deliver_speed_hour'] = getFormatNumber($deliverReturn['deliver_speed_hour']);	//店铺的所属时速
            $data['deliver_delay_time'] = getFormatNumber($deliverReturn['deliver_delay_time']);	//店铺的所属配送延迟时间
            $data['userphone'] = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';
            $data['noDiscountList'] = $noDiscountList;
            $data['noDiscountGoods'] = $noDiscountGoods;
			$data['pack_act_list'] = $pack_info['pack_act_list'];//当前订单所享受的一口价活动列表

            $data['delivery_free'] = $deliverReturn['delivery_free'];//免配送费1
            $data['delivery_free2'] = $deliverReturn['delivery_free2'];//免配送费2
            $data['delivery_free3'] = $deliverReturn['delivery_free3'];//免配送费3
            $data['delivery_free_money'] = $deliverReturn['delivery_free_money'];//满￥免配送费1
            $data['delivery_free_money2'] = $deliverReturn['delivery_free_money2'];//满￥免配送费2
            $data['delivery_free_money3'] = $deliverReturn['delivery_free_money3'];//免配送费3


			$data['total_score'] = $total_score;//当前订单所需总积分

			if($shop_type==2){//商城才有周期购
				foreach($data['goods'] as $v){
					// 周期购信息
					$periodic_info = D('Mall_periodic_purchase')->get_goods_one($v['goods_id']);
					if($periodic_info){
						if (!$periodic_info['freight_type'] || $periodic_info['freight_type']!=2) {
							// 周期购 周期购运费计算方式  0 （每期快递发货运费）×（期数）
							$data['delivery_fee'] = $data['delivery_fee']*$periodic_info['periodic_count'];
						} elseif ($v['specs_id']>0 && $periodic_info['freight_type']==2 && intval($v['periodic_num'])<intval($periodic_info['full_periodic_count'])) {
							// 周期购 周期购运费计算方式  1： 满多少期数包邮，默认满一期包邮 但是不满足期数
							$data['delivery_fee'] = $data['delivery_fee']*$periodic_info['periodic_count'];
						} elseif ($v['specs_id']>0 && $periodic_info['freight_type']==2 && intval($v['periodic_num'])>=intval($periodic_info['full_periodic_count'])) {
							// 周期购 周期购运费计算方式  1： 满多少期数包邮，默认满一期包邮 满足期数包邮
							$data['delivery_fee'] = 0;
						}
					}
				}
			}

			/*----------------------新增的---------------------*/
            //预售商品
            $time=time();
            $where3 = array();
            $where3['start_time']=array('lt',$time);
            $where3['end_time']=array('gt',$time);
            $where3['stock']=array('neq',0);
            $where3['is_del'] = 0;
            $where3['store_id'] =$goodsData[0]['store_id'];
            $where3['goods_id'] =$goodsData[0]['productId'];
            if(empty($goodsData[0]['productId'])){
                /*当取消订单再来一单的时候，预售需要这个判断给goods_id赋值*/
               $where3['goods_id'] =$goodsData[0]['goods_id'];
            }
            $book_arr = M('Mall_book_goods')->where($where3)->find();
            if(!empty($goodsData[0]['specid']) && $book_arr['is_spec']==1){
                 $where4['book_id']=$book_arr['id'];
                 $where4['spec_index']=$goodsData[0]['specid'];
                 $where4['stock']=array('neq',0);
                 $book_arr1 = M('Mall_book_goods_spec')->where($where4)->find();
                 if(!empty($book_arr1)){
                     $book_arr['discount_price']=$book_arr1['discount_price'];
                     $book_arr['bargin_price']=$book_arr1['bargin_price'];
                 }
            }

            if($book_arr['send_goods_type']==1){
                $book_arr['send_goods_date']=date('Y-m-d',$book_arr['send_goods_date']);
            }

            if($book_arr['rest_pay_type']==1){
                $book_arr['rest_start_time']=date('Y-m-d',$book_arr['rest_start_time']);
                $book_arr['rest_end_time']=date('Y-m-d',$book_arr['rest_end_time']);
            }
            $data['book_record']=$book_arr;
            //fdump($store_shop,"deng0126");
			$data['mall_deliver_type'] = $store_shop['mall_deliver_type'];
			if($store_shop['mall_deliver_type']==8 || $store_shop['mall_deliver_type']==0 || $store_shop['mall_deliver_type']==7 || $store_shop['mall_deliver_type']==6){
				if($store_shop['s_is_open_own']){
					$data['s_free_type'] = $store_shop['s_free_type'];//免配送费类型(0:免配送费，1：不免，2：达到一定金额免配送费)
					$data['s_delivery_fee'] = $store_shop['s_delivery_fee'];//基本的配送费
					$data['s_basic_distance'] = $store_shop['s_basic_distance'];//基本的距离（km)
					$data['s_per_km_price'] = $store_shop['s_per_km_price'];//超出起步距离部分的距离每公里的单价
					$data['s_full_money'] = $store_shop['s_full_money'];//	满足金额免配送费

					$data['s_free_type2'] = $store_shop['s_free_type2'];//免配送费类型(0:免配送费，1：不免，2：达到一定金额免配送费)
					$data['s_delivery_fee2'] = $store_shop['s_delivery_fee2'];//基本的配送费
					$data['s_basic_distance2'] = $store_shop['s_basic_distance2'];//基本的距离（km)
					$data['s_per_km_price2'] = $store_shop['s_per_km_price2'];//超出起步距离部分的距离每公里的单价
					$data['s_full_money2'] = $store_shop['s_full_money2'];//	满足金额免配送费

					$data['s_free_type3'] = $store_shop['s_free_type3'];//免配送费类型(0:免配送费，1：不免，2：达到一定金额免配送费)
					$data['s_delivery_fee3'] = $store_shop['s_delivery_fee3'];//基本的配送费
					$data['s_basic_distance3'] = $store_shop['s_basic_distance3'];//基本的距离（km)
					$data['s_per_km_price3'] = $store_shop['s_per_km_price3'];//超出起步距离部分的距离每公里的单价
					$data['s_full_money3'] = $store_shop['s_full_money3'];//	满足金额免配送费
				} else {
                    $data['s_free_type'] = '1';//免配送费类型(0:免配送费，1：不免，2：达到一定金额免配送费)
                    $data['s_delivery_fee'] = $data['delivery_fee'];//基本的配送费
                    $data['s_basic_distance'] = $data['basic_distance'];//基本的距离（km)
                    $data['s_per_km_price'] = $data['per_km_price'];//超出起步距离部分的距离每公里的单价
                    $data['s_full_money'] = $data['full_money'];//	满足金额免配送费

                    $data['s_free_type2'] = '1';//免配送费类型(0:免配送费，1：不免，2：达到一定金额免配送费)
                    $data['s_delivery_fee2'] = $data['delivery_fee2'];//基本的配送费
                    $data['s_basic_distance2'] = $data['basic_distance2'];//基本的距离（km)
                    $data['s_per_km_price2'] = $data['per_km_price2'];//超出起步距离部分的距离每公里的单价
                    $data['s_full_money2'] = $data['full_money2'];//	满足金额免配送费

                    $data['s_free_type3'] = '1';//免配送费类型(0:免配送费，1：不免，2：达到一定金额免配送费)
                    $data['s_delivery_fee3'] = $data['delivery_fee3'];//基本的配送费
                    $data['s_basic_distance3'] = $data['basic_distance3'];//基本的距离（km)
                    $data['s_per_km_price3'] = $data['per_km_price3'];//超出起步距离部分的距离每公里的单价
                    $data['s_full_money3'] = $data['full_money3'];//	满足金额免配送费
				}
			}
			fdump_api(['req_goods' => $goodsData, 'resp_goods' => $data], 'shop_order/check_cart_goods', 1);
            return $data;
        }
    }

	/**
	 * 校验商品规格和属性是否被修改过
	 * @param array $goodsSpec
	 * @return array|int[]
	 */
    public function checkGoodsSpec(array $goodsSpec)
	{
		if(empty($goodsSpec['productParam'])){
			return ['error_code' => 0];
		}
		foreach ($goodsSpec['productParam'] as $v){
			if($v['type'] == 'spec'){
				$spec = M('Shop_goods_spec_value')->where(['id' => $v['id']])->find();
				if(empty($spec)){
					return ['error_code' => 1001, 'msg' => "【{$v['name']}】规格不存在，请重新购买！"];
				}
				if($spec['name'] !== $v['name']){
					return ['error_code' => 1001, 'msg' => "【{$v['name']}】规格已修改，请重新购买！"];
				}
			}
			if($v['type'] == 'properties'){
				foreach ($v['data'] as $p){
					$properties = M('Shop_goods_properties')->where(['id' => $p['list_id']])->find();
					if(empty($properties)){
						return ['error_code' => 1001, 'msg' => "【{$p['name']}】属性不存在，请重新购买！"];
					}
					$propertiesArr = explode(',', $properties['val']);
					$propVal = [];
					foreach ($propertiesArr as $pv){
						$pvArr = explode(':', $pv);
						if($pvArr[1]){
							$propVal[] = $pvArr[0];
						}
					}
					if(!in_array($p['name'],$propVal)){
						return ['error_code' => 1001, 'msg' => "【{$p['name']}】属性已修改，请重新购买！"];
					}
				}
			}
		}
		return ['error_code' => 0];
	}
	
	//判断商城商品的运费计算方式是不是两种都有
	public function check_mall_mixed_freight($goods_data){
		if(empty($goods_data)) return false;
		$ids = array();
		foreach($goods_data as $v){
			$ids[] = $v['productId'];
		}
		$list = M('Shop_goods')->where(array('goods_id'=>array('in',$ids)))->field('freight_type')->select();
		$freight_value = array();
		foreach($list as $val){
			$freight_value[] = $val['freight_type'];
		}
		if(in_array('0',$freight_value) && in_array('1',$freight_value)){
			return true;
		}else{
			return false;
		}
	}

    // 配送费满减折扣计算
    public function delivery_discount(&$return=array(),$delivery_fee=0,$is_unset=0,$app_version=1300)
    {
    	if ($app_version<=1300) {
    		return false;
    	}
    	$return_data = array();
    	// 存在折扣优惠
        if ($return['discount_list']['delivery_zhekou']) {
            $delivery_zhekou = $return['discount_list']['delivery_zhekou'];
            // 折扣金额
            $delivery_zhekou_reduce = round($delivery_fee * (1-$delivery_zhekou['minus']),2);
	    	if ($delivery_zhekou_reduce>$return['delivery_fee_reduce']) {
	    		$return_data['discount_type'] = 'zhekou';
		    	$return['delivery_fee_reduce'] = $delivery_zhekou_reduce;
		        $return['discount_list']['delivery_zhekou']['minus2'] = $delivery_zhekou_reduce;
		        // 满减优惠去掉
		        if ($return['discount_list']['delivery']) {
		            $delivery_manjian = $return['discount_list']['delivery'];
		        	if ($is_unset) {
		            	unset($return['discount_list']['delivery']);
		        	}
		            if ($delivery_manjian['plat_money'] > 0 || $delivery_manjian['merchant_money']) {
		                $return['merchant_reduce_deliver_money'] -= $delivery_manjian['merchant_money'];
		                $return['plat_reduce_deliver_money'] -= $delivery_manjian['plat_money'];
		                $return['platform_plat'] -= $delivery_manjian['plat_money'];
		                $return['platform_merchan']-= $delivery_manjian['merchant_money'];
		            }else{
		                $return['plat_reduce_deliver_money'] += $delivery_manjian['minus'];
		                $return['platform_plat'] -= $delivery_manjian['minus'];
		            }
		        }
		        if ($delivery_zhekou['plat_money'] > 0 || $delivery_zhekou['merchant_money']) {
		            $return['merchant_reduce_deliver_money'] += round($delivery_zhekou['merchant_money']*$return['delivery_fee_reduce'],2);
		            $return['plat_reduce_deliver_money'] += $delivery_manjian['plat_money'];
		            $return['platform_plat'] += $delivery_manjian['plat_money'];
		            $return['platform_merchan']+= $delivery_manjian['merchant_money'];
		        }
		    }else{
	    		$return_data['discount_type'] = 'manjian';
				if ($is_unset) {
					unset($return['discount_list']['delivery_zhekou']);
				}
		    }
		    return $return_data;
		}
    }

    //获取砍价商品列表
    public function get_skill_goods_list($store_id,$page=1,$limit=3,$user=[]){
    	$where=array();
    	//店铺状态

    	//$where['gd.status']=1;
		$time=time();
        $week = date('w');
	    $tm = time();
	    $today = date('Y-m-d');
    	//考不考虑时间
        if($store_id>0){
            $where = [
                'gd.store_id'=>$store_id,
                'gd.cat_fid'=>array('gt',0),
                'pg.type'=>1,
                'pg.is_promote'=>1,
                'ltg.is_del'=>0,
                'ltg.end_date' => ['egt', $today],
                'ltg.start_date' => ['elt', $today],
                'pg.promote_end_time'=>array('egt',time()),
                '_string' => "UNIX_TIMESTAMP(CONCAT('$today', ' ', ltg.end_time)) > $tm AND UNIX_TIMESTAMP(CONCAT('$today', ' ', ltg.start_time)) < $tm AND FIND_IN_SET($week,ltg.week) AND gd.status=1 and st.status and ltg.stock <>0  AND  FIND_IN_SET($week,week)"
            ];
        }else{
			$where = [
				'gd.cat_fid'=>array('gt',0),
				'pg.type'=>1,
				'pg.is_promote'=>1,
				'ltg.is_del'=>0,
				'ltg.end_date' => ['egt', $today],
				'ltg.start_date' => ['elt', $today],
				'pg.promote_end_time'=>array('egt',time()),
				'_string' => "UNIX_TIMESTAMP(CONCAT('$today', ' ', ltg.end_time)) > $tm AND UNIX_TIMESTAMP(CONCAT('$today', ' ', ltg.start_time)) < $tm AND FIND_IN_SET($week,ltg.week) AND gd.status=1 and st.status and ltg.stock <>0  AND  FIND_IN_SET($week,week)"
			];
		}
        //$where['_string'] ="gd.status=1 and st.status and ltg.stock <>0  AND  FIND_IN_SET($week,week)";
        //$where['pg.promote_end_time']=array('egt',time());
		$first=($page-1)*$limit;
    	$field='gd.goods_id,gd.store_id,gd.name,gd.image,gd.max_num,gd.unit,gd.price,gd.old_price,
    	gd.seckill_type,ltg.limit_type,ltg.limit_num,ltg.limit_price as seckill_price,gd.seckill_open_time,
    	gd.seckill_close_time,gd.sell_day,gd.seckill_stock,gd.today_seckill_count,ltg.start_date,ltg.end_date,
    	ltg.start_time,ltg.end_time,ltg.stock,ltg.is_spec,ltg.id as limit_id,gd.use_area';

    	$list=$this->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')
            ->join('inner join '.C('DB_PREFIX').'time_limited_discount_goods ltg on ltg.goods_id=gd.goods_id')
            ->join('inner join '.C('DB_PREFIX').'mall_promote_goods pg on pg.goods_id = ltg.goods_id')
            ->where($where)->field($field)->limit($first,$limit)->order('pg.sort desc')->select();
        $area_id=$this->getAreaId($user);
        /*if(!empty($area_id)){*/
            foreach ($list as $k => $v){
                if($v['use_area']==1){
                    $goods_list = D('Shop_goods_area')->where(array('did'=>$v['goods_id'],'store_id'=>array('exp','IS NOT NULL')))->getField('aid',true);
                    if(empty($goods_list)){
                        continue;
                    }
                    //取交集,有则过滤商品
                    $arrs=array_intersect($area_id, $goods_list);
                    if(empty($arrs)){
                        unset($list[$k]);
                        continue;
                    }
                }
            }
            $list=array_values($list);
        /*}*/

    	$goods_image=new goods_image();
    	//最后结束的时间
    	$max_close_time=0;
    	foreach ($list as $k => $v) {
            $ret=$this->check_bet_time($v['goods_id'],$v['start_date'],$v['start_time'],$v['end_date'],$v['end_time']);

            $startTime = strtotime($v['start_date'].' '.$v['start_time']);
            $endTime = strtotime($v['end_date'].' '.$v['end_time']);
	    $v['seckill_open_time']=$startTime;
            $v['seckill_close_time']=$endTime;
    		if($ret==1){
                $v['seckill_status']=1;
                $v['time_clock']=$v['seckill_close_time']-$time;
                $today_seckill_count=$this->get_nums($v['goods_id']);
                if($v['stock']==-1){
                    $v['percent']=0;
                }elseif ($v['stock']==0){
                    $v['percent']=100;
                }else{
                    $v['percent']= ($v['stock']+$today_seckill_count) > 0 ? round(($today_seckill_count/($v['stock']+$today_seckill_count))*100,2) : 100;
                }
    		}elseif ($ret==2){
    		    //未开始或者未在显示时间段内
                $v['time_clock']=$startTime-time();
                $v['seckill_status']=0;
            }elseif ($ret==4){
    		    //还未开始
		        $v['time_clock']=$startTime-time();
                $v['seckill_status']=0;
            }
    		else{
    		    //已经结束
                $today_seckill_count=$this->get_nums($v['goods_id']);
                $v['percent']= ($v['stock']+$today_seckill_count) > 0 ? round(($today_seckill_count/($v['stock']+$today_seckill_count))*100,2) : 100;
                $v['seckill_status']=2;
                unset($list[$k]);
                continue;
            }
    		if($v['seckill_close_time']>$max_close_time){
    			$max_close_time=$v['seckill_close_time'];
    		}
    		$img_arr=D('shop_goods')->reg_good_img($v['image'],$goods_image);
			$v['image']=$img_arr['0']['url'];
    		$list[$k]=$v;
            /*获取限时价格*/
            if($list[$k]['is_spec']==1){
                $mytest = D('Time_limited_discount_goods_spec')->where(['limit_id' => $list[$k]['limit_id']])->min('limit_price');
                $list[$k]['seckill_price'] = $mytest;
            }else{
                $arr=D('Time_limited_discount_goods')->get_in_progress_record($v['goods_id']);
                $list[$k]['seckill_price']=$arr['limit_price'];
				if(empty($arr)){
					$list[$k]['seckill_price']=$v['price'];
				}
            }
    	}
		$list=array_values($list);
    	$result=array();
    	$result['max_close_time']=$max_close_time;
    	$result['max_clock_time']=$max_close_time-$time;
    	if($result['max_clock_time'] < 0){
			$result['list'] = [];
		}else{
			$result['list']=(array)$list;
		}

    	return $result;
    }


    /**
     * @param $user
     * @return array|bool|mixed
     * 返回区id
     */
    public function getAreaId($user){
        //fdump($user,"dengyyyyy",1);
	    $area_id=array();
	    if(empty($user['area_id']) || $user['area_id']==0){
           if(empty($user['city_id'])){
                if(empty($user['province_id'])){
                    return false;
                }else{
                 $arr=$this->getChildArea($user['province_id']);
                 for($i=0;$i<count($arr);$i++){
                     $area_id=$this->getChildArea($arr[$i]);
                 }
                 return $area_id;
                }
           }else{
               $area_id = D('Area')->where(array('area_pid|area_id'=>$user['city_id'],'is_open'=>1))->getField('area_id',true);
               //fdump($area_id,"dengyyyyy",1);
               return $area_id;
           }
        }else{
            $area_id[0] = $user['area_id'];
			!empty($user['city_id']) && $area_id[1] = $user['city_id'];
			!empty($user['province_id']) && $area_id[2] = $user['province_id'];
	        return $area_id;
        }
    }

    /**
     * @param $area_id
     * @return mixed
     * 取得子区域
     */
    public function getChildArea($area_id){
        $area_list = D('Area')->where(array('area_pid'=>$area_id))->getField('area_id',true);
        return $area_list;
    }

//查询限时优惠销量

    public function get_nums($goods){
        $act_info = D('Time_limited_discount_goods')->get_in_progress_record($goods);
        $where1['s.limit_id']=$act_info['id'];
        $where1['t.paid']=1;
        $where1['s.goods_id']=$goods;
        $num=M('Shop_order_detail')
            ->alias('s')->join('INNER JOIN '. C('DB_PREFIX') .'shop_order AS t ON s.order_id = t.order_id')
            ->where($where1)
            ->sum('s.num');
        return $num;
    }

    //查询商品销量

    public function get_goods_sale_nums($goods){
        $where1['t.paid']=1;
        $where1['s.goods_id']=$goods;
        $num=M('Shop_order_detail')
            ->alias('s')->join('INNER JOIN '. C('DB_PREFIX') .'shop_order AS t ON s.order_id = t.order_id')
            ->where($where1)
            ->sum('s.num');
        return $num;
    }
    //获取商品图片信息
    public function get_goods_image($goods_id){
    	$info=$this->where(array('goods_id'=>$goods_id))->field('goods_id,image,spec_value')->find();
    	$img=new goods_image();
    	$img_arr=$this->reg_good_img($info['image'],$img);
    	$pic=$img_arr['0']['url']['s_image'];
    	$data=array();
    	if(!empty($info['spec_value'])){
    		$spec_arr=explode('#', $info['spec_value']);
    		foreach ($spec_arr as $key => $value) {
    			$arr=explode('|',$value);
    			$key=str_replace(':','_',$arr['0']);
    			if(!empty($arr['5'])){
    				$img_arr2=$img->get_image_by_path(urldecode($arr['5']));
    				$data[$key]=$img_arr2['s_image'];
    			}else{
    				$data[$key]=$pic;
    			}

    		}
    	}else{
    		$data[]=$pic;
    	}
    	return $data;
    }

    public function check_bet_time($goods_id,$day1,$time1,$day2,$time2){
        //$Day = date('Y-m-d ',time());
        $timeBegin = strtotime($day1." ".$time1);
        $timeEnd = strtotime($day2." ".$time2);
        $curr_time = time();
        if($curr_time >= $timeBegin && $curr_time <= $timeEnd){
            $checkDayStr = date('Y-m-d ',time());
            $startTime = strtotime($checkDayStr.$time1);
            $endTime = strtotime($checkDayStr.$time2);
            if(time()>$startTime && time()<$endTime ){
                //满足条件
                return 1;
            }else{
                //满足条件,但是不在活动时间内
                return 4;
            }
        }elseif($curr_time*1<$timeEnd*1){
            //活动未开始
            return 2;
        }elseif($curr_time*1<$timeBegin*1){
            //活动结束
            return 3;
        }
    }
    //判断商品类型
    public function get_good_act_info($goods_id=null){
    	$time=time();
    	//拼团商品
    	$where=array();
    	//$where['stime']=array('lt',$time);
    	$where['ftime']=array('gt',$time);
    	$where['good_id']=$goods_id;
    	$where['status']=1;
    	$where['is_del']=0;
    	$group_info=M('Mall_group_act')->where($where)->field('*')->find();

    	$act_info=array();
    	if($group_info){	//拼团商品
    		$act_info['group_id']=$_GET['group_id']=$_POST['group_id']=$group_info['id'];
    		$act_info['is_group']=1;
    		$act_info['num']=$group_info['complete_num'];
    		$arr=explode('/',$group_info['join_price']);
    		asort($arr);
    		$act_info['price']=array_shift($arr);
    		return $act_info;
    	}

    	//砍价商品
		$where2=array();
    	//$where2['stime']=array('lt',$time);
    	$where2['ftime']=array('gt',$time);
    	$where2['goods_id']=$goods_id;
    	$where2['status']=1;
    	$where2['is_del']=0;
    	$cut_info=M('Mall_bargain_act')->where($where2)->field('*')->find();
    	$act_info=array();
    	if($cut_info){
    		$act_info['bar_id']=$_GET['bar_id']=$_POST['bar_id']=$cut_info['id'];
    		$act_info['is_cut']=1;
    		$act_info['price']=$cut_info['floor_price'];
    		if($cut_info['spec_id']){
    			$where2=array();
				$where2['id']=array('in',explode('_',$cut_info['spec_id']));
				$spec_arr=M('Shop_goods_spec_value')->where($where2)->order('id asc')->getfield('name',true);
				$spec_str=implode(',',$spec_arr);
				$act_info['spec_str']=$spec_str;
    		}
    		return $act_info;
    	}
        //预售商品
        $time=time();
        $where5=array();
        $where5['start_time']=array('lt',$time);
        $where5['end_time']=array('gt',$time);
        $where5['stock']=array('neq',0);
        $where5['goods_id']=$goods_id;
        //$where['status']=1;array(array('neq',-1),array('neq',0),'and');
        $where5['is_del']=0;
        $book_info=M('Mall_book_goods')->where($where5)->field('*')->find();
        $act_info=array();
        if(!empty($book_info)){	//预售商品
            $act_info['book_id']=$book_info['id'];
            $act_info['is_booking']=1;
            return $act_info;
        }
    	return $act_info;

    }

    //获取店铺的的推荐商品
    public function get_recommend_goods($store_id,$goods_id,$limit=6,$arr=[]){
    	$where=array();
		/*修复指定时间显示商品列表查询数量不一致的bug 2018/12/14*/
        $today=date('Y/m/d');
		$now_time=time();
		$where=array();
		$where['gd.store_id']=$store_id;
		$where['gd.cat_fid']=array('gt',0);
		$where['gd.status']=1;
		$where['sh.store_theme']=1;
		$where['st.have_shop']=1;
		$string="(gd.show_start_time=gd.show_end_time or (gd.show_start_time='00:00:00' and gd.show_end_time='23:59:00') or (unix_timestamp(concat('".$today."',' ',gd.show_start_time))<=".$now_time." and unix_timestamp(concat('".$today."',' ',gd.show_end_time))>=".$now_time."))";
		$where['_string']=$string;
		//是否推荐
		$where['is_recommend']=1;
		//去除当前商品
		$where['gd.goods_id']=array('neq',$goods_id);

		$cat_id=M('shop_goods')->where(array('goods_id'=>$goods_id))->getField('cat_id');

		$list=M('Shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->order('tsort desc')->limit($limit)->getfield('gd.goods_id,gd.use_area,gd.store_id,gd.name,gd.image,gd.describe,gd.price,gd.old_price,case gd.cat_id when "'.$cat_id.'" then gd.last_time+10000000000 else gd.last_time end as tsort',true);
		$list=(array)$list;

		$area_id = [];
		if (!empty($arr)) {
			//设置商品区域展示
			$area_id = $this->getAreaId($arr);
		}

		if (!empty($list)) {
			foreach ($list as $k1 => $v1) {
				if ($v1['use_area'] == 1) {
					$goods_list = D('Shop_goods_area')->where(array('did' => $v1['goods_id'],'store_id' => array('exp','IS NOT NULL')))->getField('aid',true);
					if (empty($goods_list)) {
						continue;
					}
					//取交集,有则过滤商品
					$arrs = array_intersect($area_id, $goods_list);
					if (empty($arrs)) {
						unset($list[$k1]);
						continue;
					}
				}
			}
			$list = array_values($list);
		}

		$count=count($list);
		$data=array();
		if($count<6){
			$limit=$limit-$count;
			$goods_arr=(array)array_keys($list);
			$goods_arr[]=$goods_id;
			if(!empty($goods_arr)){
				$where['gd.goods_id']=array('not in',$goods_arr);
			}
			unset($where['is_recommend']);

			if (!empty($area_id)) {
				$hot_list = M('Shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->field('gd.goods_id,gd.use_area,gd.store_id,gd.name,gd.image,gd.describe,gd.price,gd.old_price,case gd.cat_id when "'.$cat_id.'" then sell_count+10000 else sell_count end as sell_sort')->order('sell_sort desc')->select();
				if (!empty($hot_list)) {
					foreach ($hot_list as $k2 => $v2) {
						if ($v2['use_area'] == 1) {
							$goods_list = D('Shop_goods_area')->where(array('did' => $v2['goods_id'],'store_id' => array('exp','IS NOT NULL')))->getField('aid',true);
							if (empty($goods_list)) {
								continue;
							}
							//取交集,有则过滤商品
							$arrs = array_intersect($area_id, $goods_list);
							if (empty($arrs)) {
								unset($hot_list[$k2]);
								continue;
							}
						}
					}
					$hot_list = array_values($hot_list);
				}
				$hot_list = array_slice($hot_list, 0,$limit);
			} else {
				$hot_list = M('Shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on gd.store_id=st.store_id')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on gd.store_id=sh.store_id')->where($where)->field('gd.goods_id,gd.use_area,gd.store_id,gd.name,gd.image,gd.describe,gd.price,gd.old_price,case gd.cat_id when "'.$cat_id.'" then sell_count+10000 else sell_count end as sell_sort')->order('sell_sort desc')->limit($limit)->select();
			}

			$data=array_merge($list,(array)$hot_list);
		}elseif($count>6){
			$data=array_slice($list, 0,$limit);
			$arr=array();
			foreach ($data as $k => $v) {
				$arr[]=$v['last_time'];
			}
			array_multisort($arr,SORT_DESC,$data);
		}else{
			$data=$list;
		}
		$goods_image_class = new goods_image();
		foreach ($data as $k => $v) {
		    /*判断是否存在预售*/
            $book_act = D('Mall_book_goods')->get_book_record($v['goods_id']);
            if($book_act){
             $v['book_record']=1;
            }else{
             $v['book_record']=0;
            }
            /*----------------*/
			$tmp_pic_arr = explode(';', $v['image']);
			foreach ($tmp_pic_arr as $key => $value) {
			    if('http' === substr($value,0,4)){
					$temp_image = $value;
				}else{
                    $temp_image = $goods_image_class->get_image_by_path($value);
                }
				if ($temp_image) {
			        if(is_array($temp_image)){
                        $v['image'] = $temp_image['s_image'];
                        break;
                    }else{
                        $v['image'] = $temp_image;
                        break;
                    }
                    $v['image'] = thumb_img($v['image'],352);
				}
			}
			$data[$k]=$v;
		}
		return (array)$data;
    }

    //定制价格加成遍历
    public function jdPriceUpdate(){
        set_time_limit(0);
        $where['jd_sku_id'] = array('gt',0);
        $goodsArr = M('Shop_goods')->field('goods_id,cost_price,price,jd_sku_id')->where($where)->select();
        foreach($goodsArr as $goods) {
            $percent = floatval(C('config.jd_price_add_percent'));
            if ($goods['cost_price'] >= 1000 && $goods['cost_price'] < 2000) {
                $percent = floatval(C('config.jd_price_add_percent_gt1000'));
            } elseif ($goods['cost_price'] >= 2000 && $goods['cost_price'] < 3000) {
                $percent = floatval(C('config.jd_price_add_percent_gt2000'));
            } elseif ($goods['cost_price'] >= 3000) {
                $percent = floatval(C('config.jd_price_add_percent_gt3000'));
            }
            if ($percent <= 0) {
                $percent = 1;
            } else {
                $percent = 1 + ($percent / 100);
            }
            $new_price = round($goods['cost_price'] * $percent, 1);
            if($new_price!=$goods['price']){
                M('Shop_goods')->where(array('goods_id'=>$goods['goods_id']))->save(array('price'=>$new_price));
            }
        }
    }

    /*
     * 获取商品的区块及附属商品列表
     */
    public function get_Subsidiary_Piece($goods_id = 0)
    {
        $Shop_subsidiary_piece_where = array('sp.goods_id' => $goods_id, 'sp.is_delete' => 0, 'sug.is_delete' => 0, 'g.status' => 1);
        $subsidiary_piece_goodslist = M('Shop_subsidiary_piece')->alias('sp')
            ->where($Shop_subsidiary_piece_where)
            ->join('RIGHT JOIN pigcms_shop_subsidiary_piece_goods as sug ON sp.id = sug.piere_id')
            ->join('RIGHT JOIN pigcms_shop_goods as g ON sug.goods_id = g.goods_id')
            ->field('
                sp.id as id_sp,sp.name as name_sp,sp.maxnum as maxnum_sp,sp.mininum as mininum_sp,
                sug.maxnum as maxnum_sug,sug.mininum as mininum_sug,sug.price as price_sug,sug.sort as sort_sug,
                g.*
            ')
            ->order('id_sp ASC, sug.sort DESC, g.goods_id DESC')
            ->select();
        $subsidiary_piece_goodslist_goodids = array_column($subsidiary_piece_goodslist, 'goods_id');
        $subsidiary_piece_goodslist_names = array_column(M(_view('Shop_goods'))->where(['goods_id'=>['in', $subsidiary_piece_goodslist_goodids]])->select(), 'name', 'goods_id');
        $Shop_subsidiary_piece_ids = array_column($subsidiary_piece_goodslist, 'id_sp');
        $Shop_subsidiary_piece_names = array_column(M(_view('Shop_subsidiary_piece'))->where(['id'=>['in', $Shop_subsidiary_piece_ids]])->select(), 'name', 'id');

        $list = array();
        if ($subsidiary_piece_goodslist) {
            foreach ($subsidiary_piece_goodslist as $key => $value) {
            	$value['name_sp'] = isset($Shop_subsidiary_piece_names[$value['id_sp']]) ? $Shop_subsidiary_piece_names[$value['id_sp']] : $value['name_sp'];
            	$value['name'] = isset($subsidiary_piece_goodslist_names[$value['goods_id']]) ? $subsidiary_piece_goodslist_names[$value['goods_id']] : $value['name'];
                $value['name'] = htmlspecialchars_decode($value['name']);
                $value['name_sp'] = htmlspecialchars_decode($value['name_sp']);
                if($value['stock_num'] < 0 or $value['mininum_sug'] <= $value['stock_num']){
                    $list[$value['id_sp']]['id_sp'] = $value['id_sp'];
                    $list[$value['id_sp']]['name'] = $value['name_sp'];
                    $list[$value['id_sp']]['maxnum'] = $value['maxnum_sp'];
                    $list[$value['id_sp']]['mininum'] = $value['mininum_sp'];
                    unset($value['name_sp']);
                    unset($value['maxnum_sp']);
                    unset($value['mininum_sp']);
                    $list[$value['id_sp']]['goods'][] = $value;
                }
            }
        }

        return array_values($list);
    }

    // 组合附属商品
    public function combination_data($data_list = array(), $wstr = 'str', $x = 0)
    {
    	$wstr_second = $wstr.'_second';
        $goodsList = $data_list;
        foreach ($data_list as $key => $value) {
            if ($value['host_goods_id'] == 0) {
                if($value['sell_type'] == 2){
                    $value['num'] = $value['num'] ?: $value['code_weight'] / $value['goods_weight'];
                    $data_list[$key]['num'] = $value['num'];
                }
                $newstr = '';
                $newstr2 = '';

                // 第二种语言
                $newstr_second = '';
                $newstr_second2 = '';
                $price = 0;
                $pay_price = 0;
                $total = 0;
                $discount_total = 0;

                $old_price_all = 0;
                $discount_price_all = 0;
                $discount_price = 0;
                foreach ($goodsList as $k => $v) {
                    if ($v['host_goods_id'] == $value['goods_id'] AND $v['uniqueness_number'] == $value['uniqueness_number']) {
                        $price += $v['price'] * $v['num'];//附属菜总价
                        $pay_price += $v['pay_price'] * $v['num'];//附属菜总价
                        $total += $v['total'];//附属菜总价
                        $discount_total += $v['discount_price']* $v['num'];//附属菜总价

                        $old_price_all += $v['old_price_all'];// * $v['num'];//附属菜总价
                        $discount_price_all += $v['discount_price_all'];// * $v['num'];//附属菜总价
                        $discount_price += $v['discount_price'];// * $v['num'];//附属菜总价

                        // '米饭×3，水煮鱼(大份、大辣)×3，水煮鱼(中份、大辣)×3，三鲜汤×3';
                        $str = '';
                        $str_second = '';
                        if (!empty($v[$wstr])) {
                            $str = '(' . $v[$wstr] . ')';
                        }
                        if (!empty($v[$wstr_second])) {
                            $str_second = '(' . $v[$wstr_second] . ')';
                        }
                        if ($x) {
                            $newstr .= $v['name'] . $str . '×' . $v['num'] . '，';
                            // 第二种语言名称
                            if($value[$wstr_second]){
                            	$newstr_second .= $v['name_second'] . $str . '×' . $v['num'] . '，';
                            }
                        } else {
                            $newstr .= $v['name'] . $str . '×' . $v['num'] / $value['num'] . '，';
                            // 第二种语言名称
                            if($value[$wstr_second]){
                            	$newstr_second .= $v['name_second'] . $str . '×' . $v['num'] / $value['num'] . '，';
                            }
                        }
                    }
                }

                $newstr2 = substr($newstr, 0, strlen($newstr) - 3);
                // 第二种语言
                $newstr_second && $newstr_second2 = substr($newstr_second, 0, strlen($newstr_second) - 3);
				if(empty($data_list[$key][$wstr])){
					$data_list[$key][$wstr.'_old'] = $data_list[$key][$wstr];
					$data_list[$key][$wstr] = $newstr2 ? $newstr2 : '';
					if ($newstr_second) {
						$data_list[$key][$wstr_second] = $newstr_second2 ? $newstr_second2 : '';
					}
				}else{
					$data_list[$key][$wstr.'_old'] = $data_list[$key][$wstr];
					$data_list[$key][$wstr] .= ' ' . $newstr2;
					if ($newstr_second) {
						$data_list[$key][$wstr_second] .= ' ' . $newstr_second2;
					}
				}

                if($value['price'] >= 0){
					$data_list[$key]['price'] = getFormatNumber($data_list[$key]['price'] + $price / $value['num']);
				}

                if($value['pay_price'] >= 0){
					$data_list[$key]['pay_price'] = getFormatNumber($data_list[$key]['pay_price'] + $pay_price / $value['num']);
				}

                if($value['total'] >= 0){
					$data_list[$key]['total'] = getFormatNumber($data_list[$key]['total'] + $total / $value['num']);
				}

                if($value['discount_total'] >= 0){
					$data_list[$key]['discount_total'] = getFormatNumber($data_list[$key]['discount_total'] + $discount_total / $value['num']);
				}

                // ajax_cart
                if($value['old_price_all'] >= 0){
					$data_list[$key]['old_price_all'] = getFormatNumber( $data_list[$key]['old_price_all'] + $old_price_all);
				}
                if($value['discount_price_all'] >= 0){
					$data_list[$key]['discount_price_all'] = getFormatNumber($data_list[$key]['discount_price_all'] + $discount_price_all);
				}

                if($value['discount_price'] >= 0){
					$data_list[$key]['discount_price'] = getFormatNumber($data_list[$key]['discount_price'] + $discount_total / $value['num']);
				}
            } else {
                unset($data_list[$key]);
            }
        }

        return array_values($data_list);
    }

    // 组合附属商品2
    public function combination_data2($data_list = array())
    {
        $goodsList = $data_list;
        foreach ($data_list as $key => $value) {
            if ($value['host_goods_id'] == 0) {
                foreach ($goodsList as $k => $v) {
                    if ($v['host_goods_id'] == $value['goods_id'] AND $v['uniqueness_number'] == $value['uniqueness_number']) {
                        $data_list[$key]['info'][] = $v;
                    }
                }
                $data_list[$key]['info_count'] = count($data_list[$key]['info']) ? : 0;
            } else {
                unset($data_list[$key]);
            }
        }
        return array_values($data_list);
    }

    // 获取某个商品的附属商品ID列表
    public function getGoodsSubsidiary($goods_id = 0){
        $ids = M('Shop_subsidiary_piece_goods')->where(['host_goods_id'=>$goods_id, 'is_delete'=>0])->order('sort DESC')->getField('id', true);
        return $ids;
    }

    //计算附属商品数量 当主商品数量增加 附属商品数量相应增加
    public function combination_data_num($data_list = array()){
        $goodsList = $data_list;
        foreach ($data_list as $key => $value) {
            if ($value['host_goods_id'] == 0 AND $value['count'] > 1) {
                foreach ($goodsList as $k => $v) {
                    if ($v['host_goods_id'] == $value['productId'] AND $v['host_goods_id'] > 0 AND $v['uniqueness_number'] == $value['uniqueness_number']) {
                        $data_list[$k]['count'] = $value['count'] * $v['count'];
                    }
                }
            }
        }
        return $data_list;
    }

    //替换商品名称 (多语言开启时)
    /*
	*second_lang 第二种语言
	*/
    public function replace_goods_name($data = array(),$second_lang='')
    {

    	$default_language = C('config.default_language');//默认语言
        //获取商品id数组 并去重
        $goods_id_list = array_unique(array_column($data, 'goods_id'));
        //取出商品id数组中的商品名称 用商品作为键值
        $goods_name_list = array_column(M(_view('Shop_goods'))->where(['goods_id' => ['in', $goods_id_list]])->select(),'name','goods_id');

        if ($second_lang) {
        	$goods_name_list_second = array_column(M(_view('Shop_goods',$second_lang))->where(['goods_id' => ['in', $goods_id_list]])->select(array(),$second_lang),'name','goods_id');
        }
        foreach ($data as $k => $v) {
            //替换商品名称
            $data[$k]['name'] = isset($goods_name_list[$v['goods_id']]) ? $goods_name_list[$v['goods_id']] : $v['name'];
            $data[$k]['spec_name']=$v['spec'];
            // 双语
            $second_lang && $data[$k]['name_second'] = isset($goods_name_list_second[$v['goods_id']]) ? $goods_name_list_second[$v['goods_id']] : $v['name'];

            //替换商品属性
            if($v['spec']){
            	$spec_arr = [];
            	$tmp_spec_arr = explode('、', $v['spec']);
				foreach($tmp_spec_arr as $value){
					$tmp_t_spec_arr = explode(',', $value);
					$spec_arr = array_merge($spec_arr, $tmp_t_spec_arr);
				}
            	$spec_lang = [];
            	foreach ($spec_arr as $spec) {
            		$property = M("Shop_goods_properties")->where(['goods_id'=>$v['goods_id'], 'val'=>['like', '%'.$spec.':%']])->find();
            		if($property){
		        		$_val = explode(',', $property['val']);
		        		foreach ($_val as $p => $_v) {
		        			$temp = explode(':', $_v);
		        			if($temp[0] == $spec){
		        				$lang = M(_view("Shop_goods_properties"))->where(['id'=>$property['id']])->find();
		        				if($lang){
		        					$_val_lang = explode(',', $lang['val']);
		        					if(isset($_val_lang[$p])){
		        						$_temp = explode(':', $_val_lang[$p]);
		        						$spec_lang[] = $_temp[0];
		        					}
		        				}
		        				if ($second_lang) {
		        					$second_lang_p = M(_view("Shop_goods_properties",$second_lang))->where(['id'=>$property['id']])->find(array(),$second_lang);

		        					if($second_lang_p){
			        					$_val_lang = explode(',', $second_lang_p['val']);
			        					if(isset($_val_lang[$p])){
			        						$_temp = explode(':', $_val_lang[$p]);
			        						$spec_lang_second[] = $_temp[0];
			        					}
			        				}
						        }
		        			}
		        		}
		        	}else{
						$temp_now = D('')->field('*,gcv.id AS gcv_id')->table(C('DB_PREFIX') .'shop_goods_spec_value'. " as gcv")->join("inner join " . C('DB_PREFIX') . "shop_goods_spec AS gs ON `gcv`.`sid`=`gs`.`id`")->where(['gs.goods_id'=>$v['goods_id'],'gcv.name'=>$spec])->find();
						$temp_list = D('')->field('gcv.*,gcv.id AS gcv_id')->table(C('DB_PREFIX') ._view('shop_goods_spec_value'). " as gcv")->join("inner join " . C('DB_PREFIX') . "shop_goods_spec AS gs ON `gcv`.`sid`=`gs`.`id`")->where(['gs.goods_id'=>$v['goods_id']])->select();
						foreach($temp_list as $vv){
							if($vv['gcv_id'] == $temp_now['gcv_id']){
								$spec_lang[] = $vv['name'];
							}
						}
					}
            	}
            	if(!empty($spec_lang)){
            		$data[$k]['spec'] = implode('、', $spec_lang);
            		unset($spec_lang);
            	}
            	if(!empty($spec_lang_second)){
            		$data[$k]['spec_second'] = implode(',', $spec_lang_second);
            		unset($spec_lang_second);
            	}
            }
        }

        return $data;
    }


    /**
     * 通过店铺id获取客服对应商品列表
     * @param $storeId
     * @param $page
     * @param $limit
     * @param $type
     */
    public function getRecommendGoodsForKefu($storeId, $page = 1, $limit = 20, $type = 'mall')
    {
        if (!isset($storeId) || $storeId < 1) {
            return array();
        }

        if ($type == 'mall') {
            //商城
            $goods = $this->where(array('store_id' => $storeId, 'status' => 1, 'goods_type' => array('IN', array(0, 1))))
                ->field('goods_id AS id,store_id,`name`,price,image,sell_count AS sale_count')
                ->order('sell_count DESC')
                ->limit(($page - 1) * $limit, $limit)
                ->select();
        } else if ($type == 'meal') {
            //外卖
            $goods = $this->where(array('store_id' => $storeId, 'status' => 1, 'goods_type' => 0))
                ->field('goods_id AS id,store_id,`name`,price,image,sell_count AS sale_count')
                ->order('sell_count DESC')
                ->limit(($page - 1) * $limit, $limit)
                ->select();
        } else if ($type == 'group') {
            //团购
            $tm = time();
            $goods = M('Group_store')->alias('s')
                ->field('g.group_id AS id,g.`name`,g.sale_count,s.store_id,g.price,g.pic AS image')
                ->join(C('DB_PREFIX') . 'group AS g ON g.group_id = s.group_id')
                ->where(array('s.store_id' => $storeId, 'g.begin_time' => ['gt', $tm], 'g.end_time' => ['lt', $tm]))
                ->order('g.sale_count DESC')
                ->limit(($page - 1) * $limit, $limit)
                ->select();
        }
        //商品封面图取第一张
        $goodsImage = new goods_image();
        foreach ($goods as &$good) {
            $images = explode(';', $good['image']);
            if ($images[0]) {
                $good['image'] = $goodsImage->get_image_by_path($images[0], 0);
            }
            $good['type']=$type;
        }
        return $goods;
    }

    //辣度
    public function spicy_arr($data = array())
    {
        return array(1=>L_('不辣'),2=>L_('微辣'),3=>L_('中辣'),4=>L_('重辣'));
    }

    //获取商品的组合套餐
    public function get_combination_list($goods_id,$uid=0,$is_check=false)
    {
    	// 当前商品
    	$now_goods = $this->where(['goods_id'=>$goods_id])->find();
    	// 店铺信息
        $storeShop = D('Merchant_store_shop')->where(array('store_id' => $now_goods['store_id']))->find();
        $stock_type = $storeShop['stock_type'];

        $today = date('Ymd');

        // 组合套餐
    	$combination_list = M(_view('Shop_combination'))->field('c.*')->join(' as c left join '.C('DB_PREFIX').'shop_combination_goods as g on c.com_id=g.com_id')->where("c.is_delete = 0 AND g.goods_id = " . $goods_id)->order('c.com_id ASC')->select();

        $goods_image_class = new goods_image();
        foreach ($combination_list as $key => $value) {
        	$where = array(
        		'cg.com_id' => $value['com_id']
        	);
        	$where['cg.goods_id'] = array('neq',$goods_id);

            $goods = M('Shop_combination_goods')->field('cg.id,cg.com_id,g.*')->join(' as cg left join '.C('DB_PREFIX').'shop_goods as g on cg.goods_id = g.goods_id')->where($where)->order('cg.id ASC')->select();
            $goods = array_values($goods);
            $new_goods = [];
        	if ($is_check) {
        		$where['cg.goods_id'] = $goods_id;
            	$new_goods = M('Shop_combination_goods')->field('cg.id,cg.com_id,g.*')->join(' as cg left join '.C('DB_PREFIX').'shop_goods as g on cg.goods_id = g.goods_id')->where($where)->find();
            	$new_goods = $new_goods ? $new_goods : [];
        		$new_goods && $goods = array_merge([$new_goods],$goods);
        	}

            $goodsData = array();

            foreach ($goods as $k => &$row) {
            	$goodsData[$k] = array(
					'host_goods_id' =>0,
					'count' =>1,
					'productId'=>$row['goods_id'],
					'productName'=>$row['name'],
					'uniqueness_number'=>$row['goods_id'],
				);

	        	$row['sell_day'] = $stock_type ? $today : $row['sell_day'];
	            if ($row['seckill_type'] == 1) {
	                $now_time = date('H:i');
	                $open_time = date('H:i', $row['seckill_open_time']);
	                $close_time = date('H:i', $row['seckill_close_time']);

	                // 秒杀库存的计算
	                if ($today == $row['sell_day']) {
	                    $seckill_stock_num = $row['seckill_stock'] == - 1 ? - 1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
	                } else {
	                    $seckill_stock_num = $row['seckill_stock'];
	                }
	            } else {
	                $now_time = time();
	                $open_time = $row['seckill_open_time'];
	                $close_time = $row['seckill_close_time'];
	                $seckill_stock_num = $row['seckill_stock'] == - 1 ? - 1 : (intval($row['seckill_stock'] - $row['today_seckill_count']) > 0 ? intval($row['seckill_stock'] - $row['today_seckill_count']) : 0);
	            }

	            $row['is_seckill_price'] = false;
	            $row['o_price'] = getFormatNumber($row['price']);
	            if ($open_time < $now_time && $now_time < $close_time && getFormatNumber($row['seckill_price']) > 0 && $seckill_stock_num != 0) {
	                $row['price'] = getFormatNumber($row['seckill_price']);
	                $row['is_seckill_price'] = true;
	            } else {
	                $row['price'] = getFormatNumber($row['price']);
	            }

	            $row['old_price'] = getFormatNumber($row['old_price']);
	            $row['seckill_price'] = getFormatNumber($row['seckill_price']);

	            $return = $this->format_spec_value($row['spec_value'], $row['goods_id'], $row['is_properties']);

	            $row['list'] = isset($return['list']) ? $return['list'] : '';

				$row['seckill_stock'] = $seckill_stock_num;
				if($row['seckill_stock'] > 0 && $row['is_seckill_price']){
					$row['stock_num'] = $row['seckill_stock'];
				}
				if($row['seckill_stock'] == 0){
					$row['is_seckill_price'] = false;
				}

				$goodsData[$k]['productPrice'] = $row['price'];
				if($row['list']){
					$spec_list = array_values($row['list']);
					$spec_list = $spec_list[0];
					foreach ($spec_list['spec'] as $_spec) {
						$goodsData[$k]['productParam'][] = array(
							'id' => $_spec['spec_val_id'],
							'name' => $_spec['spec_val_name'],
							'spec_id' => $_spec['spec_val_sid'],
							'type' => "spec",
						);
						$goodsData[$k]['productParam']['uniqueness_number'] .= '_'.$_spec['spec_val_id'].'_'.$_spec['spec_val_sid'];
					}
					foreach ($spec_list['properties'] as $_prop) {
						$goodsData[$k]['productParam'][] = array(
							'data'=>[
								'id' => $_prop['num'],
								'list_id' => $_prop['id'],
								'name' => $_prop['name'],
							],
							'type' => 'properties',
						);
						$goodsData[$k]['productParam']['uniqueness_number'] .= '_'.$_prop['id'].'_'.$_prop['num'];
					}

					$row['price'] = $spec_list['price'];
					$row['stock_num'] = $spec_list['stock_num'];
					if ($row['is_seckill_price']) {
						$row['price'] = $spec_list['seckill_price'];
					}
				}
	            //商品图片
	            $tmp_pic_arr = $goods_image_class->get_allImage_by_path($row['image'], 's');
	            $tmp_pic_arr[0] = replace_file_domain($tmp_pic_arr[0]);
	            $row['image'] = thumb_img($tmp_pic_arr[0], 240, 240, 'fill');
            }
            $combination_list[$key]['goods'] = $goods;
			// 验证是否存在库存 获得优惠信息
			if ($is_check) {
				$return = $this->checkCart($now_goods['store_id'], $uid, $goodsData);
				if ($return['error_code']) {
					unset($combination_list[$key]);
				}else{
					$combination_list[$key]['old_price'] = getFormatNumber($return['price']);
					// $combination_list[$key]['price'] = getFormatNumber($return['discount_price']);

					$pay_price = strval(round($return['vip_discount_money'] - $return['sto_first_reduce'] - $return['sto_full_reduce'] - $return['sys_first_reduce'] - $return['sys_full_reduce'], 2));

					$hasReduceMoney = 0;
			        if(C('config.vip_level_discount_sequence')==1) { //满减后计算
			            if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
			                if ($return['sorts_discount_type'] == 0) { //折上折
			                    $hasReduceMoney = round($pay_price - round($pay_price * ($return['vip_discount'] * 0.01), 2), 2);
			                } else {  //折扣最优
			                    if ($return['optimalDiscountMoney'] > 0) {
			                        $totalOptimalMoney = $return['optimalDiscountMoney'];
			                        $result['vip_discount_money'] = $totalOptimalMoney;
			                        $pay_price = round($totalOptimalMoney - round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2), 2);//实际要支付商品的总价格
			                        $hasReduceMoney = round($pay_price - round($pay_price * ($return['vip_discount'] * 0.01), 2), 2);
			                    }
			                }
			            }
			            $pay_price  = round($pay_price - $hasReduceMoney,2);
			        }
			        $pay_price = $pay_price > 0 ? $pay_price : 0;
					$combination_list[$key]['price'] = getFormatNumber($pay_price);
					$total_price = 0;
					foreach ($return['goods'] as $good) {
						$total_price += $good['price'];
					}
					$combination_list[$key]['discount_price'] = getFormatNumber($total_price - $pay_price);
				}
			}
        }
        if(!$combination_list){
            $combination_list = array();
        }

        return $combination_list;
    }

    //验证是否是限时折扣商品
    public function check_seckill($now_shop_store,$now_goods,$today='')
    {
    	// 返回数组
    	$returnArr = array('status'=>0);
    	$today = $today ? $today : date('Ymd');
    	// 库存类型
    	$stock_type = $now_shop_store['stock_type'];
    	// 售卖日期
    	$now_goods['sell_day'] = $stock_type ? $today : $now_goods['sell_day'];

        if ($now_goods['seckill_type'] == 1) {
            $now_time = date('H:i');
            $open_time = date('H:i', $now_goods['seckill_open_time']);
            $close_time = date('H:i', $now_goods['seckill_close_time']);

            // 秒杀库存的计算
            if ($today == $now_goods['sell_day']) {
                $returnArr['stock_num'] = $now_goods['seckill_stock'] == - 1 ? - 1 : (intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) > 0 ? intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) : 0);
            } else {
                $returnArr['stock_num'] = $now_goods['seckill_stock'];
            }
        } else {
            $now_time = time();
            $open_time = $now_goods['seckill_open_time'];
            $close_time = $now_goods['seckill_close_time'];
            $returnArr['stock_num'] = $now_goods['seckill_stock'] == - 1 ? - 1 : (intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) > 0 ? intval($now_goods['seckill_stock'] - $now_goods['today_seckill_count']) : 0);
        }
        if ($open_time < $now_time && $now_time < $close_time && floatval($now_goods['seckill_price']) > 0 && $returnArr['stock_num'] != 0) {
            $returnArr['price'] = floatval($now_goods['seckill_price']);
            $returnArr['status'] = 1;
        }
        return $returnArr;
    }

    // 添加超值换购商品
    public function addRepurchase($store_id,$rep_id,$good_ids)
    {
        //fdump($store_id,'confirm',1);
        //fdump($rep_id,'confirm',1);
        //fdump($good_ids,'confirm',1);
    	// 返回数组
    	$returnArr = array('error'=>1);

       	// 店铺信息
        $now_shop = D(_view('Merchant_store_shop'))->field(true)->where(array('store_id'=>$store_id))->find();

        $where = array();
        // 店铺ID
        $where['store_id'] = $store_id;
        $where['rep_id'] = $rep_id;
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();

        // 返回数组
        $returnArr['is_show'] = 0;

        if (!$detail) {
        	$returnArr['msg'] = L_('活动不存在');
        	return $returnArr;
        }

        if ($detail['status'] != 1) {
        	$returnArr['msg'] = L_('活动未进行');
        	return $returnArr;
        }

        if ($detail['start_time'] > time()) {
        	$returnArr['msg'] = L_('活动未开始');
        	return $returnArr;
        }

        if ($detail['end_time'] < time()) {
        	$returnArr['msg'] = L_('活动已结束');
        	return $returnArr;
        }

        // if ($detail['is_limit'] == 1 && $detail['is_limit'] < $money) {
        // 	$returnArr['msg'] = L_('未达到换购金额');
        // 	return $returnArr;
        // }

        $returnArr['name'] = $detail['name'];
        $returnArr['rep_id'] = $detail['rep_id'];
        $returnArr['discount_money'] = 0; // 优惠金额
        $returnArr['packing_charge'] = 0; // 餐盒费
        $returnArr['price'] = 0; // 实际金额合计
        $returnArr['total_money'] = 0; // 原价合计

        $where_goods['r.store_id'] = $store_id;
        $where_goods['r.rep_id'] = $detail['rep_id'];
        $where_goods['r.goods_id'] = array('in',(string)$good_ids);
        $where_goods['g.spec_value'] = '';
        $goods = M('Shop_repurchase_goods')->field('r.rep_id,r.price as r_price,r.stock_num as r_stock_num,r.sell_count as r_sell_count,g.*')->join('as r left join '.C('DB_PREFIX').'shop_goods as g on g.goods_id=r.goods_id')->where($where_goods)->select();

        if (!$goods) {
        	$returnArr['error'] = 2;
        	$returnArr['msg'] = L_('商品未参与换购');
        	return $returnArr;
        }

        $goods_arr = array();
        foreach ($goods as $key => &$g) {
            // 验证库存
            if ($g['r_stock_num'] != -1 && $g['r_stock_num']<0) {
	        	$returnArr['error'] = 2;
	        	$returnArr['msg'] = L_('库存不足');
	        	return $returnArr;
            }

            $return = $this->check_stock($g['goods_id'], 1);
            if ($return['status'] != 1) {
	        	$returnArr['error'] = 2;
	        	$returnArr['msg'] = L_('库存不足');
	        	return $returnArr;
            }

            // 验证是否是限时折扣 是则不参与换购
            $return = $this->check_seckill($now_shop,$g);
            if ($return['status'] == 1) {
            	$returnArr['error'] = 2;
	        	$returnArr['msg'] = L_('参与限时优惠');
	        	return $returnArr;
            }

            // 验证是否有附属菜 是则不参与换购
            $piece_goods = M('Shop_subsidiary_piece_goods')->where(array('host_goods_id'=>$g['goods_id'],'is_delete'=>0))->find();
            if ($piece_goods) {
            	$returnArr['error'] = 2;
	        	$returnArr['msg'] = L_('包含附属菜');
	        	return $returnArr;
            }

            $goodInfo = D('Shop_goods')->get_goods_by_id($g['goods_id']);
            if (empty($goodInfo) || $goodInfo['status'] != 1) {
            	$returnArr['error'] = 2;
	        	$returnArr['msg'] = L_('商品不存在');
	        	return $returnArr;
            }

            $uniqueNumber = $goodInfo['goods_id'];
            $productParam = [];

            $data = [
                'rep_id' => $rep_id,
                'host_goods_id' => 0,
                'productId' => $goodInfo['goods_id'],
                'productName' => $goodInfo['name'],
                'productPrice' => $g['r_price'],
                'count' => 1,
                'uniqueness_number' => $uniqueNumber,
                'productParam' => $productParam
            ];

            if ($now_shop['pack_type']!=2) {
                // 单个商品收取
            	$returnArr['packing_charge'] += $goodInfo['packing_charge'];// 打包费
            }

            $returnArr['total_money'] += $goodInfo['price'] + $goodInfo['packing_charge'];
            $returnArr['price'] += $g['r_price'] + $goodInfo['packing_charge'];
            $returnArr['discount_money'] += $goodInfo['price'] - $g['r_price'];

            $goods_arr[] = $data;
        }
		$returnArr['total_money'] = getFormatNumber($returnArr['total_money']);
		$returnArr['discount_money'] = getFormatNumber($returnArr['discount_money']);
		$returnArr['packing_charge'] = getFormatNumber($returnArr['packing_charge']);
        $returnArr['error'] = 0;
        $returnArr['info'] = $goods_arr;
        return $returnArr;

    }

    // 验证商品是否参与超值换购
    public function checkRepurchase($good_id)
    {
        $where = array();
        // 店铺ID
        $where['r.store_id'] = $store_id;
        $where['rg.goods_id'] = $good_id;
        $where['r.is_delete'] = 0;
        $where['r.status'] = 1;
        $where['r.start_time'] = ['lt',time()];
        $where['r.end_time'] = ['gt',time()];

        $list = M('Shop_repurchase_goods')->alias('rg')->field('rg.goods_id')->join(' inner join '.C('DB_PREFIX').'shop_repurchase as r on r.rep_id=rg.rep_id')->where($where)->select();

        if(empty($list)){
        	return false;
        }

        foreach ($list as $key => $value) {

            // 验证是否是限时折扣 是则不参与换购
            $return =  D('Time_limited_discount_goods')->get_in_progress_record($value['goods_id']);

            // 验证是否有附属菜 是则不参与换购
            $piece_goods = M('Shop_subsidiary_piece_goods')->where(array('host_goods_id'=>$value['goods_id'],'is_delete'=>0))->find();
            if (!$piece_goods && !$return) {
	        	return true;
            }
        }
    	return false;
    }

    /**
     * 获取凑单商品
     */
    public function getCollectGoodsByPrice($price, $store_id, $order = 'sort DESC, price ASC', $is_shop = 1, $limit = 30)
    {
        $week = date('w');
        $sortWhere = [
            'store_id' => $store_id,
            'status' => 1,
            '_string' => "(`week` = '' OR FIND_IN_SET(" . $week . ",`week`))"
        ];
        $sortIds = M('Shop_goods_sort')->where($sortWhere)->getField('sort_id', true);
        $where = [
            'store_id' => $store_id,
            'status' => 1,
            'price' => ['egt', $price],
            'sort_id' => ['IN', $sortIds],
            'spec_value' => '',
            'is_properties' => 0,
            'is_subsidiary_goods' => 0
        ];
        if ($is_shop == 1) {
            $where['goods_type'] = ['neq', 1];
        }
        $g_list = M(_view('Shop_goods'))->field(true)->where($where)->order($order)->limit($limit)->select();

        $goods_image_class = new goods_image();
        $timeNow = time();
        $today = date('Ymd');
        $goodsList = array();

        $storeShop = D('Merchant_store_shop')->where(array('store_id' => $store_id))->find();
        $stock_type = $storeShop['stock_type'];
        $discountMod = D('Time_limited_discount_goods');
        foreach ($g_list as $row) {
            //拼单去掉限时优惠商品
            if ($discountMod->get_in_progress_record($row['goods_id'])) {
                continue;
            }
            if (!$this->isShow($row['goods_id'], 15)) {
                continue;
            }

            $row['sell_day'] = $stock_type ? $today : $row['sell_day'];
            $row['is_seckill_price'] = false;
            $row['price'] = floatval($row['price']);
            $row['old_price'] = floatval($row['old_price']);
            $row['seckill_price'] = floatval($row['seckill_price']);

            //商品图片
            $tmp_pic_arr = $goods_image_class->get_allImage_by_path($row['image'], 's');
            $row['image'] = thumb_img($tmp_pic_arr[0], 240, 240, 'fill');

            $return = $this->format_spec_value($row['spec_value'], $row['goods_id'], $row['is_properties']);
            $row['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
            $row['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
            $row['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $row['list'] = isset($return['list']) ? $return['list'] : '';
            $row['is_new'] = (($_SERVER['REQUEST_TIME'] - $row['last_time']) > 864000) ? 0 : 1;

            if (!$row['spec_list']) {
                if ($row['min_num'] > 0 && $row['stock_num'] >= 0 && $row['min_num'] > $row['stock_num']) {
                    $row['stock_num'] = 0;
                }
            }
            $goodsList[] = $row;
        }
        foreach($goodsList as $key=>$value){
            $value['sell_day'] = $stock_type ? $today : $value['sell_day'];
            if ($today == $value['sell_day']) {
                $value['stock_num'] = $value['stock_num'];
            } else {
                if($value['sell_type']==2){
                    $value['stock_num'] = $value['original_stock'];
                }
            }
            if (!$value['is_seckill_price'] && $value['stock_num'] != -1 && $value['stock_num'] < 0) {
                continue;
            }
            $value['name'] = html_entity_decode($value['name'], ENT_QUOTES);
            $productList[$key] = array(
                'product_id' 		=> $value['goods_id'],			//商品ID
                'goods_id' 			=> $value['goods_id'],			//商品ID
                'store_id' 			=> $value['store_id'],			//店铺ID
                'name' 				=> $value['name'],				//商品名称
                'product_name' 		=> $value['name'],  			//商品名称
                'product_describe' 	=> $value['describe'],  			//商品描述
                'describe' 	        => $value['describe'],  			//商品描述
                'unit' 				=> $value['unit'],				//商品单位
                'o_price' 			=> $value['o_price'],			//商品价格
                'price' 			=> $value['price'],				//商品价格
                'product_price' 	=> $value['price'],				//商品价格
                'is_seckill_price' 	=> $value['is_seckill_price'],	//是否在秒杀
                'seckill_discount' 	=> $value['o_price'] ? round($value['price']/$value['o_price']*10,1) : 10,//秒杀折扣
                'seckill_stock' 	=> $value['seckill_stock'],		//秒杀库存，-1代表无限
                'image' 			=> $value['image'],				//商品图片
                'product_image' 	=> $value['image'],				//商品图片
                'limit_type' 		=> $value['limit_type'],		//限购类别
                'sell_count' 		=> $value['sell_count'],		//商品已售数量
                'stock_num' 		=> $value['stock_num'],			//商品库存
                'stock' 		    => $value['stock_num'],	//商品库存
                'today_sell_count' 	=> $value['today_sell_count'],	//商品今日销量
                'reply_count' 		=> $value['reply_count'],		//商品评论数量
                'packing_charge' 	=> $value['packing_charge'],	//商品打包费
                'number' 			=> $value['number'],			//商品条码数值
                'min_num' 			=> $value['min_num'],			//商品最少购买数量
                'max_num' 			=> $value['max_num'],				//商品最多购买数量
                'has_format' 		=> ($value['spec_value'] || $value['is_properties']) ? true : false,//商品是否有规格属性
                'has_spec'   		=> $value['spec_value'] ? true : false, //商品是否有规格
                'is_new' 			=> $value['is_new'],			//是否新品
                'product_sale' 		=> $value['sell_count'],		//商品销量
                'product_reply' 	=> $value['reply_count'],		//好评数
                'is_subsidiary_goods' 	=> $value['is_subsidiary_goods'], //1有附属商品 0没有附属商品
            );
        }
        return $productList;
    }

    // 获得超值换购列表
    public function get_repurchase($store_id,$money){

        // 店铺信息
        $now_store = D(_view('Merchant_store'))->where(array('store_id'=>$store_id))->find();
        $now_shop = D(_view('Merchant_store_shop'))->field(true)->where(array('store_id'=>$store_id))->find();

        $where = array();
        // 店铺ID
        $where['store_id'] = $store_id;
        $where['is_delete'] = 0;
        $where['status'] = 1;
        $where['start_time'] = array('lt',time());
        $where['end_time'] = array('gt',time());
        $where['_string'] = 'is_limit=0 OR (is_limit=1 AND limit_money<='.$money.')';

        $detail = M('Shop_repurchase')->where($where)->find();

        // 返回数组
        $returnArr['is_show'] = 0;

        if (!$detail) {
            return $returnArr;
        }

        // 当前显示星期数
        $detail['week_arr'] = explode(',', $detail['week']);
        $now_week = date('w',time());
        // 不在显示时间范围内
        if (!in_array($now_week, $detail['week_arr'])) {
            return $returnArr;
        }

        // 不在显示时间范围内
        if (strtotime($detail['show_time_start'])>time() || strtotime($detail['show_time_end'])<time()) {
            return $returnArr;
        }

        $returnArr['name'] = $detail['name'];
        $returnArr['rep_id'] = $detail['rep_id'];
        $returnArr['discount_money'] = 0; // 优惠金额

        $where_goods['r.store_id'] = $store_id;
        $where_goods['r.rep_id'] = $detail['rep_id'];
        $where_goods['g.spec_value'] = '';
        $goods = M('Shop_repurchase_goods')->field('r.rep_id,r.price as r_price,r.stock_num as r_stock_num,r.sell_count as r_sell_count,g.*')->join('as r left join '.C('DB_PREFIX').'shop_goods as g on g.goods_id=r.goods_id')->where($where_goods)->select();

        if (!$goods) {
            return $returnArr;
        }

        $goods_arr = array();
        foreach ($goods as $key => &$value) {
            // 验证库存
            if ($value['r_stock_num'] != -1 && $value['r_stock_num']<=0) {
                continue;
            }
            // 验证库存
            $return = D('Shop_goods')->check_stock($value['goods_id'], 1);
            if ($return['status'] != 1) {
                continue;
            }

            // 验证是否是限时折扣 是则不参与换购
            $return = D('Shop_goods')->check_seckill($now_shop,$value);
            if ($return['status'] == 1) {
                continue;
            }

            // 验证是否有附属菜 是则不参与换购
            $piece_goods = M('Shop_subsidiary_piece_goods')->where(array('host_goods_id'=>$value['goods_id'],'is_delete'=>0))->find();
            if ($piece_goods) {
                continue;
            }

            // 总计优惠的金额
            $returnArr['discount_money'] += $value['price'] - $value['r_price'];

            $goodInfo = D('Shop_goods')->get_goods_by_id($value['goods_id']);

            $goods_arr[$key]['goods_id'] = $value['goods_id'];
            $goods_arr[$key]['name'] = $goodInfo['name'];
            $goods_arr[$key]['image'] = $goodInfo['image'];
            $goods_arr[$key]['price'] = $value['r_price'];
            $goods_arr[$key]['old_price'] = $value['price'];
            if ($now_shop['pack_type']==2) {
                //每单收取
                $goods_arr[$key]['packing_charge'] = 0;
            }else{
                // 单个商品收取
                $goods_arr[$key]['packing_charge'] = $value['packing_charge']; // 打包费
            }
            $goods_arr[$key]['discount'] = number_format(getFormatNumber(($value['r_price'])/$value['price'])*10,1);
        }
        if ($goods_arr) {
            $returnArr['is_show'] = 1;
            $returnArr['goods'] = array_values($goods_arr);
            $returnArr['discount_money'] = getFormatNumber($returnArr['discount_money']);
        }
        return $returnArr;
    }

    /**
     * 判断商品是否展示，包括以下几种情况
     * 二进制运算  1、商品下架和不在显示时间段  2、没库存  4、分类不存在或者不显示  8、附属菜分类
	 * shopType  1:外卖  2：商城
     */
    public function isShow($goodsId, $filter = 15, $shopType = 1)
    {
        $test_status = $filter & 1;
        $test_stock = $filter & 2;
        $test_sort = $filter & 4;
        $test_subsidiary = $filter & 8;

        $tm = time();
        $good = $this->where(['goods_id' => $goodsId, 'status' => 1])->find();
        if (!$good) {
            return false;
        }
        //商品下架
        if ($test_status && $good['status'] != 1) {
            return false;
        }

        //商品库存
		if($good['sell_type'] == 2){
			//计重
			if ($test_stock && $good['stock_weight'] != -1 && $good['stock_weight'] <= 0) {
				return false;
			}
		}else{
			if ($test_stock && $good['stock_num'] != -1 && $good['stock_num'] <= 0) {
				return false;
			}
		}


        //商品不在显示时间段内
        empty($good['show_start_time']) && $good['show_start_time'] = '00:00:00';
        empty($good['show_end_time']) && $good['show_end_time'] = '00:00:00';
        if ($test_status && !($good['show_start_time'] == '00:00:00' && $good['show_end_time'] == '00:00:00') && ($tm < strtotime($good['show_start_time']) || $tm > strtotime($good['show_end_time']))) {
            return false;
        }

        //分类不显示或者商品所属附属菜分类
        if ($test_sort || $test_subsidiary) {
            $week = date('w');
            $where = "`sort_id`=%d ";
            if ($test_sort) {
            	if($shopType == 2){
					$where .= " AND (`week` = '' OR FIND_IN_SET(" . $week . ",`week`)) ";
				}else{
					$where .= " AND `sort_status` = 1 AND (`week` = '' OR FIND_IN_SET(" . $week . ",`week`)) ";
				}
            }
            if ($test_subsidiary) {
            	if($shopType == 2){
					$where .= " AND `status`=1 ";
				}else{
					$where .= " AND `sort_status` = 1 AND `status`=1 ";
				}
            }
            $sort = M('Shop_goods_sort')->where(sprintf($where, $good['sort_id']))->find();
            if (!$sort) {
                return false;
            }
            if ($sort['fid'] > 0) {
                for ($i = 0; $i < 3; $i++) {
                    if ($sort['fid'] > 0) {
                        $sort = M('Shop_goods_sort')->where(sprintf($where, $sort['fid']))->find();
                        if (!$sort) {
                            break;
                        }
                    } else {
                        break;
                    }
                }
            }
        }
        return true;
    }

    //设置商品库存缓存
    public function set_goods_stock_cache($goods_id, $is_remove=false){
        $now_goods = $this->where(array('goods_id'=>$goods_id))->find();
        $info = $this->format_spec_value($now_goods['spec_value'], $now_goods['goods_id']);
		fdump_sql($info, 'set_goods_stock_cache' . $goods_id);
        if($info['list']){//有规格属性的
            foreach($info['list'] as $key => $val){
                $cache_key = 'goods_stock_'.$now_goods['goods_id'].'_'.$key;
                if(!$is_remove && !cache_sql($cache_key)){ //没有设置缓存再设置，有缓存就不设置了
					cache_sql($cache_key, $val['stock_num'], 10*60);    //由于商品最低可设置10分钟回滚库存，设置为10分钟缓存。
				}else if($is_remove){
					cache_sql($cache_key, null);
				}
            }
        }else{//没有规格属性的
            $cache_key = 'goods_stock_'.$now_goods['goods_id'];
			if(!$is_remove && !cache_sql($cache_key)){
				cache_sql($cache_key, $now_goods['stock_num'], 10*60);    //由于商品最低可设置10分钟回滚库存，设置为10分钟缓存。
			}else if($is_remove){
				cache_sql($cache_key, null);
			}
        }
    }

    //判断商品下单库存是否充足【计划任务调用此方法】
    public function check_shop_goods_stock($id){

        $db = M('shop_goods_stock_check');
        $where = ['id'=>$id];

        $info = $db->where($where)->find();
        $goods = json_decode($info['goods'],true);

        $not_enough = [];//库存不足的商品

        foreach($goods as $key => $val){
            if($val['spec_id']){//商品有规格属性
                $cache_key = 'goods_stock_'.$val['goods_id'].'_'.$val['spec_id'];
            }else{
                $cache_key = 'goods_stock_'.$val['goods_id'];
            }
            $cache_stock = cache_sql($cache_key);
            if($cache_stock===false){//没有设置缓存
                D('Shop_goods')->set_goods_stock_cache($val['goods_id']);
                $cache_stock = cache_sql($cache_key);
            }
            if($cache_stock=='-1'){
                continue;
            }
            $new_stock = $cache_stock-$val['num'];//缓存库存-购买量

            if($new_stock<0){
                $not_enough[] = $val['name'];//提示库存不足
            }else{
                cache_sql($cache_key, $new_stock, 10*60);//更新缓存
            }
        }
        $save = [];
        if(empty($not_enough)){//所有商品库存都充足
            $save['status'] = 1;
        }else{//库存不充足
            $save['status'] = 2;
            $save['msg'] = json_encode($not_enough);
        }
        $db->where($where)->data($save)->save();
    }

	/*
		* 获得猜你喜欢发现好菜商品分类
		* author htm
		* date 2020-03-25
	*/
	public function get_sort_by_guess_like($where)
	{
		// 查询商品分类id

		$condition = array('s.status' => 1);

		$condition['ms.status'] = 1;
		$condition['ms.have_shop'] = 1;

		// 所在城市
		$condition['ms.city_id'] = $where['city_id'];

		// 类型
		$condition['fg.business_type'] = $where['business_type'];

		// 商品分类
		$sort_ids = D('Shop_goods_sort')->getAllShowChildSortIds(0,[],true,true,true);
		if ($sort_ids) {
			$condition['s.sort_id'] = ['in',$sort_ids];
		}else{
			$condition['s.sort_id'] = null;
		}

		$g_list = $this->field('s.store_id')->join(' as s left join '.C('DB_PREFIX').'merchant_store as ms on s.store_id=ms.store_id  INNER JOIN ' . C('DB_PREFIX') .'system_find_goods AS fg ON fg.goods_id=s.goods_id')->where($condition)->select();

		// 商品分类id数组
		$store_id_arr = array_column($g_list, 'store_id');

		// 分类详情
		$sort_condition['store_id'] = ['in',$store_id_arr];

		$category_relation_list = M('Shop_category_relation')->field(true)->where($sort_condition)->select();



		$cat_id_arr = array_column($category_relation_list, 'cat_id');

		// 分类详情
		$cat_condition['cat_id'] = ['in',$cat_id_arr];
		$cat_condition['cat_status'] = 1;
		$category_list = M(_view('Shop_category'))->field(true)->where($cat_condition)->order('`cat_sort` DESC,`cat_id` ASC')->select();

		$category_result = array();
		foreach ($category_list as $key => $row) {
		    $category_result[] = [
		    	'sort_id' => $row['cat_id'],
		    	'sort_name' => $row['cat_name'],
		    ];
		}
		return $category_result;
	}

	/*
		* 猜你喜欢发现好菜商品列表
		* author htm
		* date 2020-03-25
	*/
	public function get_goods_by_guess_like($where)
	{
		// 商品状态
		$condition = array('s.status' => 1);

		$condition['ms.status'] = 1;
		$condition['ms.have_shop'] = 1;
        $condition['ms.is_business_open'] = 1;
		$condition['s.stock_num'] = ['neq',0];

		// 店铺分类
		/**
		if ($where['sort_id']) {
			$store_condition['r.cat_id'] = $where['sort_id'];
			$store_condition['ss.status'] = 1;
			$store_id_list = array_column( M('Merchant_store_shop')->field('s.store_id')->join(' as s left join '.C('DB_PREFIX').'shop_category_relation as r on s.store_id=r.store_id left join '.C('DB_PREFIX').'merchant_store as ss on s.store_id=ss.store_id')->where($store_condition)->select(), 'store_id');
			// var_dump(M('Merchant_store_shop')->_sql());
			if ($store_id_list) {
				$condition['ms.store_id'] = ['in',$store_id_list];
			}else{
				$condition['ms.store_id'] = null;
			}
		}**/

		//----这里的分类不再是店铺分类，而是用户的自定义分类
		$find_type = 1;//平台的自定义分类
		if($where['business_type']=='shop'){
			$find_type = 2;
		}

		$goods_arr  = D('Diy_category')->get_normal_goods_id($find_type,$where['sort_id']);

		$condition['s.goods_id'] = ['in',$goods_arr];
		//----这里的分类不再是店铺分类，而是用户的自定义分类

		// 所在城市
		$condition['ms.city_id'] = $where['city_id'];

		// 类型
		//$condition['fg.business_type'] = $where['business_type'];

		$condition['_string'] = '( (s.show_start_time = s.show_end_time) OR (s.show_start_time="00:00:00" AND s.show_end_time="23:59:00") OR (s.show_start_time<="'.date('H:i:s').'" AND s.show_end_time>="'.date('H:i:s').'"))';

		// 排序
		$lat = $where['lat'];
		$lng = $where['lng'];
		$condition_field = 'ms.*,s.*,ms.name as store_name,m.*';
		if ($lat && $lng) {
			$condition_field .= ", ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`ms`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`ms`.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`ms`.`long`*PI()/180)/2),2)))*1000) AS juli";

            //距离优先排序
            $order_sort = 'juli ASC,s.goods_id ASC';
		}else{
            $order_sort = 's.goods_id ASC';
		}

		// 页数
		$page = intval($_POST['page']) ? intval($_POST['page']) : 1;

		// 每页显示数量
		$page_size = 10;

		// 商品分类
		$sort_ids = D('Shop_goods_sort')->getAllShowChildSortIds(0,[],true,true,true);
		if ($sort_ids) {
			$condition['s.sort_id'] = ['in',$sort_ids];
		}else{
			$condition['s.sort_id'] = null;
		}

		// 总数
		$count = $this->join(' as s left join '.C('DB_PREFIX').'merchant_store as ms on s.store_id=ms.store_id  INNER JOIN ' . C('DB_PREFIX') ._view("merchant_store_shop"). ' AS m ON m.store_id=ms.store_id')->where($condition)->count();

		$g_list = M(_view('Shop_goods'))->field($condition_field)->join(' as s left join '.C('DB_PREFIX').'merchant_store as ms on s.store_id=ms.store_id  INNER JOIN ' . C('DB_PREFIX') ._view("merchant_store_shop"). ' AS m ON m.store_id=ms.store_id')->order($order_sort)->where($condition)->limit(($page-1)*$page_size.','.$page_size)->select();
		//echo M()->getLastSql();exit;

		$goods_image_class = new goods_image();
        $store_image_class = new store_image();
		$sort_result = array();
		$nowtime = time();
		$goods_list = array();
		// var_dump($g_list);

		// 外卖店铺详细信息
		$store_ids = array_column($g_list, 'store_id');
		$shop_store_where = [];
		$shop_store_where['store_ids'] = $store_ids;
		$shop_store_where['lat'] = $where['lat'];
		$shop_store_where['long'] = $where['lng'];
		$shop_store_where['deliver_type'] = 'skip';
		$shop_store_where['limit'] = 20;
		$shop_store_list = D('Merchant_store_shop')->get_list_by_option($shop_store_where);
		$shop_store_list = $shop_store_list['shop_list'];

		$shop_store_arr = [];
		foreach ($shop_store_list as $key => $value) {
			$shop_store_arr[$value['store_id']] = $value;
		}

		foreach ($g_list as $row) {
			$shop_store = $shop_store_arr[$row['store_id']];
			if(empty($shop_store)){
				$count--;
				continue;
			}
			$_tmp_goods = [];

			$_tmp_goods['name'] = $row['name'];//商品名称
			$_tmp_goods['sell_count'] = getFormatNumber($row['sell_count'] + $row['shop_virtual_sales']);

		    //新增限时显示
		    if (!($row['show_start_time'] == $row['show_end_time'] || ($row['show_start_time'] == '00:00:00' && $row['show_end_time'] == '23:59:00'))) {
		        $st = strtotime(date('Y-m-d') . ' ' . $row['show_start_time']);
		        $et = strtotime(date('Y-m-d') . ' ' . $row['show_end_time']);
		        if (!($st <= $nowtime && $nowtime <= $et)) {
		            // continue;
		        }
		    }

			$_tmp_goods['o_price'] = getFormatNumber($row['price']);//商品原价
			$_tmp_goods['old_price'] = getFormatNumber($row['price']);//商品原价
		    $seckill_record = D('Time_limited_discount_goods')->get_in_progress_record($row['goods_id']);
            if ($seckill_record) {
                //存在限时优惠
                $_tmp_goods['is_seckill_price'] = true;
                $_tmp_goods['price'] = getFormatNumber($seckill_record['limit_price']);
                $seckill_stock_num = $seckill_record['stock'];
                $_tmp_goods['seckill_price'] = getFormatNumber($seckill_record['limit_price']);
                $_tmp_goods['seckill_discount'] = round($row['price']/$row['o_price']*10,1);
            } else {
                $_tmp_goods['is_seckill_price'] = false;
                $_tmp_goods['price'] = getFormatNumber($row['price']);
                $seckill_stock_num = 0;
            }

			$_tmp_goods['seckill_discount']	= $_tmp_goods['o_price'] ? round($_tmp_goods['price']/$_tmp_goods['o_price']*10,1) : 10;//秒杀折扣

			// 商品图片
			$tmp_pic_arr = explode(';', $row['image']);
			foreach ($tmp_pic_arr as $key => $value) {
			    if('http' === substr($value,0,4)){
					$temp_image = $value;
				}else{
                    $temp_image = $goods_image_class->get_image_by_path($value,0);
                }
				if ($temp_image) {
                    if(is_array($temp_image)){
                        $_tmp_goods['image'] = thumb($temp_image['image'],400,400,'fill');
                        break;
                    }else{
                        $_tmp_goods['image'] = thumb($temp_image,400,400,'fill');
                        break;
                    }
				}
			}


			// 店铺信息
			$_tmp_goods['store_name'] = $row['store_name'];//店铺名称
			$_tmp_goods['store_id'] = $row['store_id'];//店铺id
			$_tmp_goods['sort_id'] = $row['sort_id'];//分类id
			$_tmp_goods['goods_id'] = $row['goods_id'];//商品id

			$_tmp_goods['delivery'] = $shop_store['deliver'] ? true : false;
			$_tmp_goods['delivery_time'] = $shop_store['send_time'];//配送时长
			$_tmp_goods['send_time_type'] = $shop_store['send_time_type'];
			$_tmp_goods['delivery_time_type'] = $this->send_time_type[$shop_store['send_time_type']];
			$_tmp_goods['delivery_price'] = getFormatNumber($shop_store['basic_price']);//起送价
			$_tmp_goods['delivery_money'] = getFormatNumber($shop_store['delivery_fee']);//配送费
			$_tmp_goods['delivery_system'] = $shop_store['deliver_type'] == 0 || $shop_store['deliver_type'] == 3 ? true : false;//是否是平台配送
			$_tmp_goods['deliver_type'] = $shop_store['deliver_type'];
			$_tmp_goods['lat'] = $shop_store['lat'];
			$_tmp_goods['lng'] = $shop_store['long'];
			$_tmp_goods['range'] = $shop_store['range'];

			// 店铺评分
			$_tmp_goods['star'] = number_format($shop_store['score_mean'],1);
			if($_tmp_goods['star'] == '0.0'){
				$_tmp_goods['star'] = '5.0';
			}

			$images = $store_image_class->get_allImage_by_path($row['pic_info']);
            $_tmp_goods['store_image'] = $images ? array_shift($images) : '';

            if($_tmp_goods['logo']){
				$_tmp_goods['store_image'] = replace_file_domain($row['logo']);
			}
			$_tmp_goods['store_image'] = thumb($_tmp_goods['store_image'],96,96,'fill');

			$_tmp_goods['url'] = C('config.site_url'). D('House_village')->base_url . 'pages/shop_new/shopDetail/shopDetail?store_id='.$row['store_id'].'&sort_id='.$row['sort_id'].'&goods_id='.$row['goods_id'];

			$goods_list[] = $_tmp_goods;
		}

		$return['count'] = $count;
		$return['goods_list']  = $goods_list;
		return $return;
	}


	/**
	 * [get_goods_info_lang 获取商品的多语言信息]
	 * @Author   JJC
	 * @DateTime 2020-05-14T15:04:35+0800
	 * @param    [type]                   $ids  [商品id集合]
	 * @param    string                   $lang [语言环境]
	 * @return   [type]                         [description]
	 */
	public function get_goods_info_lang($ids,$lang='chinese'){
		if(empty($ids)){
			return [];
		}
		$db_name = 'Shop_goods_'.$lang;
		$key_name = 'goods_id_'.$lang;

		$store_list = M($db_name)->where([$key_name=>['in',$ids]])->select();
		$store_arr = [];

		$store_name_key = 'name_'.$lang;
		foreach ($store_list as $key => $value) {
			$store_arr[$value[$key_name]] = $value;
		}
		return $store_arr;
	}

    /**
     * 判断是否是商城或者快店商品
     * @param string $type
     * @author 张涛
     * @date 2020/06/10
     */
    public function isShopOrMallGoods($goodsId, $isShop = true)
    {
        $goodsType = M('Shop_goods')->where(['goods_id' => $goodsId])->getField('goods_type');
        if ($isShop) {
            return $goodsType == 0;
        } else {
            return true;
        }
    }

    /**
     * 判断是否含有组合商品
     * @author 张涛
     * @date 2020/06/18
     */
    public function haveCombinedPackage($goodsId)
    {
        $nowGoods = $this->where(['goods_id' => $goodsId])->find();
        // 组合套餐
        $combinationList = M(_view('Shop_combination'))->alias('c')
            ->field('c.*')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'shop_combination_goods AS g ON c.com_id=g.com_id')
            ->where(['c.is_delete' => 0, 'g.goods_id' => $goodsId])
            ->select();
        $isHave = false;
        foreach ($combinationList as $key => $value) {
            $where = ['cg.com_id' => $value['com_id'], 'cg.goods_id' => ['neq', $goodsId]];

            $goods = M('Shop_combination_goods')->alias('cg')
                ->field('cg.id,cg.com_id,g.*')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'shop_goods AS g ON cg.goods_id = g.goods_id')
                ->where($where)
                ->select();
            if ($goods) {
                $isHave = true;
                break;
            }
        }
        return $isHave;
    }

    /**
     * 校验购物车商品限购数量
     * @author 张涛
     * @date 2020/07/16
     */
    public function checkGoodsMaxCountInCart($uid, $storeId,$goodsId = 0)
    {
		$where = ['uid' => $uid, 'store_id' => $storeId, 'is_choose' => 1];
		if ($goodsId > 0) {
			$where['goods_id'] = is_array($goodsId) ? ['IN', $goodsId] : $goodsId;
		}
		$cart = M('Cart')->where($where)->select();
        $products = [];
        foreach ($cart as $v) {
            $info = json_decode($v['info'], true);
            foreach ($info as &$i) {
                $i['count'] = $i['count'] * $v['num'];
            }
            $products = array_merge($products, $info);
        }
        $shopGoodsMod = D('Shop_goods');
        $timeLimitedMod = D('Time_limited_discount_goods');
        $goodsIndexInfo = [];
        $timeLimitedGoodsIndexInfo = [];

        //先获取每个主菜数量
        $hostGoodsCount = [];
        foreach ($products as $p) {
            if ($p['host_goods_id'] == 0) {
                if (isset($hostGoodsCount[$p['productId']])) {
                    $hostGoodsCount[$p['productId']] += $p['count'];
                } else {
                    $hostGoodsCount[$p['productId']] = $p['count'];
                }
            }
        }

        foreach ($products as $p) {
            if ($p['host_goods_id'] == 0) {
                //主菜
                if (isset($goodsIndexInfo[$p['productId']])) {
                    $goodsInfo = $goodsIndexInfo[$p['productId']];
                } else {
                    $goodsInfo = $shopGoodsMod->get_goods_by_id($p['productId']);
                    $goodsIndexInfo[$p['productId']] = $goodsInfo;
                }
                //限时优惠
                if (isset($timeLimitedGoodsIndexInfo[$p['productId']])) {
                    $timeGoodsInfo = $timeLimitedGoodsIndexInfo[$p['productId']];
                } else {
                    $timeGoodsInfo = $timeLimitedMod->get_discount_price_by_goodsid($p['productId']);
                    $timeGoodsInfo = !$timeGoodsInfo['is_discount'] ? [] : $timeGoodsInfo;
                    $timeLimitedGoodsIndexInfo[$p['productId']] = $timeGoodsInfo;
                }
                //如果存在限时优惠，则判断是否超出限时优惠价格
                /*if($timeGoodsInfo && $goodsInfo['min_num'] == 0){
                    if (isset($hostGoodsCount[$p['productId']]) && $timeGoodsInfo['max_num'] > 0 && $hostGoodsCount[$p['productId']] > $timeGoodsInfo['max_num']) {
                        throw new Exception(L_("商品【X1】您最多可购X2", array("X1" => $goodsInfo['name'], "X2" => intval($timeGoodsInfo['max_num']) . $goodsInfo['unit'])));
                    }
                }*/

                //不存在限时优惠，则判断主菜限购数量或者规格限购数量
                if ($goodsInfo['min_num'] == 0) {
                    if ($goodsInfo['list']) {
                        //多规格
                        $specIndexArr = [];
                        $specName = [];
                        foreach ($p['productParam'] as $param) {
                            $param['type'] == 'spec' && $specIndexArr[] = $param['id'];
                            $param['type'] == 'spec' && $specName[] = $param['name'];
                        }
                        $specIndex = implode('_', $specIndexArr);
                        $specMaxNum = $goodsInfo['list'][$specIndex]['max_num'] ?? 0;
                        $specStockNum = $goodsInfo['list'][$specIndex]['stock_num'] ?? 0;
                        if (! $timeGoodsInfo && $specMaxNum > 0 && $p['count'] > $specMaxNum) {
                            throw new Exception(L_("商品【X1】您最多可购X2", array("X1" => $goodsInfo['name'], "X2" => intval($specMaxNum) . $goodsInfo['unit'])));
                        }

                        if ($specStockNum != -1 && $p['count'] > $specStockNum) {
                            throw new Exception(L_("商品【X1】库存不足", array("X1" => $goodsInfo['name'])));
                        }

                        //多规格商品限购开关
                        if(C('config.shop_limit_sku_intersection') == 1){
                            if(! $timeGoodsInfo && $goodsInfo['max_num'] > 0 && isset($hostGoodsCount[$p['productId']]) && $hostGoodsCount[$p['productId']] > $goodsInfo['max_num']){
                                throw new Exception(L_("商品【X1】您最多可购X2", array("X1" => $goodsInfo['name'], 'X2'=>intval($goodsInfo['max_num']) . $goodsInfo['unit'])));
                            }
                        }
                    } else {
                        //单一规格
                        if (! $timeGoodsInfo && $goodsInfo['max_num'] > 0 && isset($hostGoodsCount[$p['productId']]) && $hostGoodsCount[$p['productId']] > $goodsInfo['max_num']) {
                            throw new Exception(L_("商品【X1】您最多可购X2", array("X1" => $goodsInfo['name'], "X2" => intval($goodsInfo['max_num']) . $goodsInfo['unit'])));
                        }
                        if ($goodsInfo['stock_num'] != -1 && $hostGoodsCount[$p['productId']] > $goodsInfo['stock_num']) {
                            throw new Exception(L_("商品【X1】库存不足", array("X1" => $goodsInfo['name'])));
                        }
                    }
                }
            }
        }
        return true;
    }


    /**
     * 根据商品id获取商品最大抵扣积分数量
     * @param $goodsId
     * @author: 张涛
     * @date: 2020/11/5
     */
    public function getScoreMaxByGoodsId($goodsId)
    {
        $scoreOpen = C('config.shop_goods_score_edit');
        $scoreCountPerYuan = C('config.user_score_use_percent');
        $showMallGoodsScore = C('config.show_mall_goods_score_discount');

        $rs = ['score_max' => 0, 'reduce_money' => 0];
        if ($scoreOpen == 1 && $showMallGoodsScore == 1) {
            $goods = $this->where(['goods_id' => $goodsId])->find();
            if ($goods['score_max'] > 0) {
                $rs['score_max'] = $goods['score_max'];
            } else {
                $merchanRate = M('Merchant_store')->alias('m')
                    ->join(C('DB_PREFIX') . 'merchant_percent_rate AS r ON m.mer_id = r.mer_id')
                    ->where(['m.store_id' => $goods['store_id']])
                    ->field('r.*')
                    ->find();
                if ($merchanRate['merchant_shop_score_max'] > 0) {
                    $rs['score_max'] = $merchanRate['merchant_shop_score_max'];
                } else if ($merchanRate['merchant_score_max'] > 0) {
                    $rs['score_max'] = $merchanRate['merchant_score_max'];
                } else if (C('config.shop_score_max') > 0) {
                    $rs['score_max'] = C('config.shop_score_max');
                } else if (C('config.user_score_max_use') > 0) {
                    $rs['score_max'] = C('config.user_score_max_use');
                }
            }
        }

        if ($rs['score_max'] > 0) {
            //$rs['reduce_money'] = round($rs['score_max'] / $scoreCountPerYuan, 2);
        }
        //最大积分抵扣可以设置百分比 例如5%
		$rs['is_percent'] = is_numeric($rs['score_max']) ? false : true;
		$rs['score_max_percent'] = $rs['is_percent']?round(floatval($rs['score_max']) / 100, 2):0;
		if (floatval($rs['score_max']) > 0) {
			$rs['is_show'] = true;
		} else {
			$rs['is_show'] = false;
		}
        return $rs;
    }


    /**
     * 支付前检查商品
     *
     * @param array $goodsIds
     * @author: zt
     * @date: 2023/06/19
     */
    public function checkBeforePay($goodsIds = [])
    {
        $rs = ['error_no' => 0, 'error_msg' => ''];
        $goods = $this->where(['goods_id' => ['IN', $goodsIds], 'status' => 1])->select();

        try {
            if (empty($goods)) {
                throw new Exception(L_('商品已下架,无法下单'));
            }

            $singleNoDelivery = array_column($goods, 'single_no_delivery');
            if (!in_array(0, $singleNoDelivery)) {
                throw new Exception(L_('仅含单点不送的商品，无法下单'));
            }
        } catch (Exception $e) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = $e->getMessage();
        }
        return $rs;
    }

	/*
     * 获取商品积分比例
     */
	public function getGoodsScore($goodsId)
	{
		if(empty(C('config.shop_goods_score_edit'))){
			return -1;
		}
		$scorePercent = self::where(['goods_id' => $goodsId])->getField('score_percent');
		if(strpos($scorePercent,'%')){
			$scorePercent = bcdiv($scorePercent,100,2);
		}

		return $scorePercent;
	}

    
	public function goodsList(int $distance = 0, int $pageSize=10, $lng = '', $lat = '')
	{
		$page = $_GET['p'] ?: 1;
		$where = [
			'g.status' => 1,
			's.status' => 1,
			's.is_business_open' => 1,
			's.have_shop' => 1
		];
		
		$fields = 'g.goods_id, g.image, g.name, g.describe, g.price, g.unit, g.sell_count sale_num, g.shop_virtual_sales, g.store_id';
		$shopGoods = $this
			->alias('g')
			->join('INNER JOIN '.C('DB_PREFIX').'merchant_store s ON g.store_id = s.store_id')
			->where($where);
		
		$sort = '';
		if(!empty($lng) && !empty($lat) && $distance){
			$fields .= ", (st_distance(point(`long`, `lat`), point({$lng}, {$lat}) ) * 111195 / 1000) AS distance";
			$sort = 'distance ASC,';
			$shopGoods = $shopGoods->having("distance <= {$distance}");
		}
		$sort .= "sort DESC, goods_id DESC";
		$count = clone $shopGoods;
		
		$count = $count->count();
		$list = $shopGoods
			->field($fields)
			->order($sort)
			->page("{$page},{$pageSize}")
			->select();

		$Page = new Page($count, $pageSize);
		$show = $Page->show();
		
		if($list){
			$deliverType = [
				0 => '平台配送',
				1 => '商家配送',
				2 => '客户自提',
				3 => '平台配送|自提',
				4 => '商家配送|自提',
				5 => '快递配送',
				6 => '快递配送|平台配送|用户自提'
			];
			foreach ($list as &$v) {
				$v['image'] = replace_file_domain($v['image']);
				$v['sale_num'] = $v['sale_num'] + $v['shop_virtual_sales'];
				$v['jump_link'] = C('config.site_url')."/packapp/plat/pages/shop_new/goodsDetail/goodsDetail?store_id={$v['store_id']}&&product_id=".$v['goods_id'];
				unset($v['shop_virtual_sales']);
				$merchant = D('Merchant')
					->join(' m INNER JOIN '.C('DB_PREFIX').'merchant_store ms ON m.mer_id = ms.mer_id')
					->join(' INNER JOIN '.C('DB_PREFIX').'merchant_store_shop sp ON ms.store_id = sp.store_id')
					->where([
						'ms.store_id' => $v['store_id']
					])
					->field('m.name, sp.deliver_type')
					->find();
				$v['merchant_name'] = $merchant['name'];
				$v['deliver_type'] = $merchant['deliver_type'];
				$v['deliver_type_text'] = $deliverType[$merchant['deliver_type']];
			}
		}
		return [
			'count' => $count,
			'list' => $list,
			'pagebar'=>$show
		];
	}

    public function queryTwGoodsStock($goodsId)
	{
        return null;
        if (!C('customization.open_tuwei_sync')) {
            return null;
        }
		$goods = D('Shop_goods')->where(['goods_id' => $goodsId])->find();
		if (empty($goods['tw_item_no'])) {
			return null;
		}
		$store = D('Merchant_store')->where(['store_id' => $goods['store_id']])->find();
		if (empty($store['tw_shop_id'])) {
			return null;
		}
		$params = [
			'shop_id' => $store['tw_shop_id'],
			'item_no' => $goods['tw_item_no']
		];
		import('@.ORG.Tw');
		$result = (new Tw())->queryStock($params);
		$stockQty = $result['stockinfo'][0]['stock_qty'] ?? null;
		$updateStock = $stockQty > 0 ? $stockQty : 0;
		D('Shop_goods')->where(['goods_id' => $goodsId])->save(['stock_num' => $updateStock]);
		return $updateStock;
	}


    public function syncProductImageToTw($goodsId, $images)
    {
        if (!C('customization.open_tuwei_sync')) {
            return false;
        }
        if (empty($goodsId) || empty($images)) {
            return false;
        }
        $goods = D('Shop_goods')->where(['goods_id' => $goodsId])->find();
        if (empty($goods['tw_item_no'])) {
            return false;
        }
        $store = D('Merchant_store')->where(['store_id' => $goods['store_id']])->find();
        if (empty($store['tw_shop_id'])) {
            return false;
        }

        //更新商品
        $this->where(['goods_id' => $goodsId])->save(['image' => implode(';', $images)]);
        //推送图维
        import('@.ORG.Tw');
        $imgCount = count($images);
        //先同步封面图
        $params = [
            'shop_id' => $store['tw_shop_id'],
            'item_no' => $goods['tw_item_no'],
            'flag' => 0,
            'item_detail_image' => [
                ['url' => replace_file_domain($images[0])]
            ]
        ];
        (new Tw())->syncProductImage($params);
        
        if ($imgCount > 1) {
            //详情图
            $params = [
                'shop_id' => $store['tw_shop_id'],
                'item_no' => $goods['tw_item_no'],
                'flag' => 1,
                'item_detail_image'=>[]
            ];
            foreach($images as $k=>$v){
                if($k == 0){
                    continue;
                }
                $params['item_detail_image'][]=['url'=>replace_file_domain($v)];
            }
            //(new Tw())->syncProductImage($params);
        }
    }

    public function picSync()
    {
        $lockKey = "picSyncKey";
        $lockRs = setLock($lockKey, 55);
        if ($lockRs['error_no']) {
            return true;
        }
        $tm = time();
        $fileHandle = new file_handle();
        $imgMod = D('Image');
        $syncMod = M('Shop_goods_batch_sync');
        while (time() - $tm < 50) {
            $record = $syncMod->where(['is_sync' => 0])->order('id ASC')->find();
            if (empty($record)) {
                break;
            }
            $data = json_decode($record['sync_data'], true);
            $goodsId = $data['goods_id'] ?? 0;
            $picPath = $data['pic_path'] ?? '';

            try {
                if (empty($goodsId) || empty($picPath)) {
                    throw new Exception('参数有误');
                }

                $good = $this->where(['goods_id' => $goodsId])->find();
                if (empty($good)) {
                    throw new Exception('商品不存在');
                }

                $images = explode(';', $good['image']);
                $images[0] = substr($picPath, 1);
                $imgMod->imageAddWatermark($picPath);
                $fileHandle->upload($picPath);

                $images = array_filter($images);
                $this->where(['goods_id' => $good['goods_id']])->save(['image' => implode(';', $images)]);
                $syncMod->where(['id' => $record['id']])->save(['is_sync' => 1]);
            } catch (Exception $e) {
                $syncMod->where(['id' => $record['id']])->save(['is_sync' => 2, 'fail_reason' => $e->getMessage()]);
            }
        }
    }
	public function miaomaiSyncPercent($storeId)
	{
		$syncCacheTotals = (int) S('miaomai_goods_sync_goods_total_rows_'.$storeId);
		$syncCacheAlreadyTotals = (int) S('miaomai_goods_already_sync_goods_total_rows_'.$storeId);
		if($syncCacheTotals && empty($syncCacheAlreadyTotals)){
			return ['status' => 1, 'msg' => '正在同步中,请稍等','percent' => 0];
		}
		if($syncCacheTotals && $syncCacheTotals <= $syncCacheAlreadyTotals){
			S('miaomai_goods_sync_goods_total_rows_'.$storeId, 0);
			S('miaomai_goods_already_sync_goods_total_rows_'.$storeId, 0);
			return ['status' => 2, 'msg' => '同步完成，共同步'.$syncCacheAlreadyTotals.'个商品','percent' => 100];
		}
		if($syncCacheTotals && $syncCacheAlreadyTotals){
			$percent = bcmul($syncCacheAlreadyTotals/$syncCacheTotals, '100', 2);
			return ['status' => 1, 'msg' => '正在同步，同步完成'.$percent.'%', 'percent' => $percent];
		}
		return ['status' => 0, 'msg' => '正在同步中,请稍等'];
	}
	public function miaoMaiGoodsSync($merId, $storeId, $getPercent = 0) 
	{
		$percent = $this->miaomaiSyncPercent($storeId);
		if($percent['status'] || $getPercent){
			return $percent;
		}
		import("@.ORG.Erp.MiaomaiErpService");
		try{
			$store = D('Merchant_store')->where(['store_id' => $storeId])->find();
			$page = 1;
			$config = MiaomaiErpService::getErpConfigByMerchantId($merId);

			$erpGoodsCategory = (MiaomaiErpService::getInstance())
				->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'], $config['miaomaiyun_url'])
				->run(Miaomaiyun::GET_GOODS_CATEGORY);

			fdump_api($erpGoodsCategory, 'job/miaomai_erp_category', 1);
			if($erpGoodsCategory['status']=='n'){
				return ['status' => 0, 'msg' => '没有获取到分类,无法同步'];
			}

			foreach ($erpGoodsCategory['data'] as $category) {//同步分类
				D('Shop_goods_sort')->syncMiaomaiCategory($storeId, $category);
			}

			$erpGoods = (MiaomaiErpService::getInstance())
				->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'], $config['miaomai_url'])
				->setParam('stores_id', $store['miaomaiyun_erp_storeid'])
				->setParam('page', $page - 1)
				->setParam('spec_id',1)
				->setParam('limit', 50)
				->run(Miaomaiyun::GET_GOODS_ALL);
			S('miaomai_goods_sync_goods_total_rows_'.$storeId, $erpGoods['total_rows']);
//			for ($page = 1;$page <= $erpGoods['max_page'];$page++) {
//				D('Merchant_store')->miaomaiGoodsSync($merId, $storeId, $page);
//			}
			//队列
			invoke_v20_service('\app\internal_card\model\service\MiaomaiGoodsSyncService/queueSyncGoods', ['mer_id' => $merId, 'store_id' => $storeId, $erpGoods['max_page']]);
			return ['status' => 1, 'msg' => '同步中'];
		}catch (\Exception $e){
			return ['status' => 0, 'msg' => $e->getMessage()];
		}
	}
}
?>

