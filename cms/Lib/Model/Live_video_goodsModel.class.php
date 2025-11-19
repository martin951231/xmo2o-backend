<?php
class Live_video_goodsModel extends Model
{
	//获取直播商品信息
	public function get_goods_by_liveid($live_id, $order="bg.no asc", $param = []){
		if(empty($live_id)) return [];
		$where = [
			'bg.video_id' => $live_id,
			'bg.is_del' => 0
		];

		if(isset($param['status'])){
			$where['bg.status'] = $param['status'];
		}

		if(isset($param['mer_id']) && $param['mer_id']){
			$where['g.mer_id'] = $param['mer_id'];
		}
		// $data = $this->where($where)->field("id,goods_id,no,status")->order($order)->select();

		$data = $this->alias("bg")->field('bg.id,bg.goods_id,bg.no,bg.status')->join(' LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($where)->order($order)->select();
		return $data;	

	}


	//获取直播商品信息
	public function formatGoods($video_id,$data,$uid=0){
		if(empty($video_id)) return [];

		$returnArr = [];

		$goods = D("Live_goods")->get_by_ids(array_column($data, 'goods_id'), ['img','name','price','stock'], false);
		foreach ($data as $key => $value) {
			if(empty($goods[$value['goods_id']])){
				continue;
			}
			$temp = [
				'no' => $value['no'],
				'id' => $value['goods_id'],
				'img' => $goods[$value['goods_id']]['img'],
				'name' => $goods[$value['goods_id']]['name'],
				'price' => $goods[$value['goods_id']]['price'],
				'old_price' => $goods[$value['goods_id']]['old_price'] ?: $goods[$value['goods_id']]['price'],
				'stock' => $goods[$value['goods_id']]['stock'],
				'type' => $goods[$value['goods_id']]['business'],
				'video_id' => $video_id,
				'status' => $value['status'],//状态1上架0下架
				'shop_redirect_info' => [],
                'offline' => $goods[$value['goods_id']]['offline'],
				'discount' => D("Card_new_coupon")->check_has_live_coupon($goods[$value['goods_id']]['mer_id'],$goods[$value['goods_id']]['store_id'],$goods[$value['goods_id']]['business']),
				'redirect_url' => '',
				'label_arr' => [],//参与活动标签
				'store_name' => $goods[$value['goods_id']]['store_name'] ?? '',//店铺名称
				'address' => $goods[$value['goods_id']]['address'] ?? '',//店铺地址
				'long' => $goods[$value['goods_id']]['long'] ?? '',//店铺经度
				'lat' => $goods[$value['goods_id']]['lat'] ?? '',//店铺维度
			];
			switch ($goods[$value['goods_id']]['business']) {
				case 'shop':
					$real_goodsinfo = D("Shop_goods")->where(['goods_id'=>$goods[$value['goods_id']]['real_goods_id']])->find();
					$temp['shop_redirect_info']['store_id'] = $real_goodsinfo['store_id'];
					$temp['shop_redirect_info']['cate_id'] = $real_goodsinfo['sort_id'];
					$temp['shop_redirect_info']['goods_id'] = $goods[$value['goods_id']]['real_goods_id'];

                    // 限时优惠 显示特价商品
                    $limit_time_discount = D('Time_limited_discount_goods')->get_in_progress_record($goods[$value['goods_id']]['real_goods_id']);
                    if($limit_time_discount){
                        $temp['label_arr'][] = [
                            'type' => 'discount',
                            'name' => '特价商品',
                        ];
                        $temp['price'] = $limit_time_discount['new_limit_price']?? $limit_time_discount['limit_price'];
                    }
					if($key == 0){

						// 其他优惠 显示活动中
						// 超值换购
						$res = D('Shop_goods')->checkRepurchase($goods[$value['goods_id']]['real_goods_id']);
						if($res){
							$temp['label_arr'][] = [
								'type' => 'activity',
								'name' => '活动中',
							];
						}else{
							// 满赠
							$res = D('Store_full_gift_activity')->checkFullActivity($goods[$value['goods_id']]['real_goods_id']);
							if($res){
								$temp['label_arr'][] = [
									'type' => 'activity',
									'name' => '活动中',
								];
							}else{
								// 店铺优惠
								$res = D('Shop_discount')->getDiscounts($goods[$value['goods_id']]['mer_id'], $goods[$value['goods_id']]['store_id'], 1);
								if($res){
									$temp['label_arr'][] = [
										'type' => 'activity',
										'name' => '活动中',
									];
								}
							}
						}

					}

					// 查找最优的优惠券
					$temp['coupon'] = D("Card_new_coupon")->get_live_best_coupon($goods[$value['goods_id']]['mer_id'], $goods[$value['goods_id']]['store_id'], $goods[$value['goods_id']]['business'],$goods[$value['goods_id']]['real_goods_id'],$uid);
					break;
				case 'mall':
					$temp['shop_redirect_info'] = (object) $temp['shop_redirect_info'];
					$temp['redirect_url'] = C("config.site_url")."/wap.php?g=Wap&c=Mall&a=detail&goods_id=".$goods[$value['goods_id']]['real_goods_id']."&current_livevideo_id=".$video_id;

                    $goods_id = $goods[$value['goods_id']]['real_goods_id'];
                    // 限时优惠 显示特价商品
                    $limit_time_discount = D('Time_limited_discount_goods')->get_in_progress_record($goods_id);
                    if($limit_time_discount){
                        $temp['label_arr'][] = [
                            'type' => 'discount',
                            'name' => '特价商品',
                        ];
                        $temp['price'] = $limit_time_discount['new_limit_price'] ?? $limit_time_discount['limit_price'];
                    }
					if($key == 0){

						// 其他优惠 显示活动中
						// 拼团商品 砍价 预售
						$res = D('Shop_goods')->get_good_act_info($goods_id);
						if($res){
							$temp['label_arr'][] = [
								'type' => 'activity',
								'name' => '活动中',
							];
						}else{
							// 打包一口价 周期购 满减送
							//周期购
							$res = D('Mall_periodic_purchase')->get_goods_one($goods_id,1);
							if($res){
								$temp['label_arr'][] = [
									'type' => 'activity',
									'name' => '活动中',
								];
							}else{
								//打包一口价
								$res = D('Mall_goods_pack')->get_one_by_goood($goods_id);
								if($res){
									$temp['label_arr'][] = [
										'type' => 'activity',
										'name' => '活动中',
									];
								}else{
									//满减
									$full_layer_list = D('Mall_full_act')->get_full_list($goods_id,$goods[$value['goods_id']]['store_id']);
									if(!empty($full_layer_list['layer_num_id'])){
										$temp['label_arr'][] = [
											'type' => 'activity',
											'name' => '活动中',
										];
									}else{
										// 店铺优惠
										$res = D('Shop_discount')->getDiscounts($goods[$value['goods_id']]['mer_id'], $goods[$value['goods_id']]['store_id'], 2);
										if($res){
											$temp['label_arr'][] = [
												'type' => 'activity',
												'name' => '活动中',
											];
										}
									}
								}
							}
						}
					}

					// 查找最优的优惠券
					$temp['coupon'] = D("Card_new_coupon")->get_live_best_coupon($goods[$value['goods_id']]['mer_id'], $goods[$value['goods_id']]['store_id'], $goods[$value['goods_id']]['business'],$goods[$value['goods_id']]['real_goods_id'],$uid);
					break;               
				case 'mall3':
                    $temp['shop_redirect_info'] = (object) $temp['shop_redirect_info'];
                    $temp['redirect_url'] = C('config.site_url'). D('House_village')->base_url ."pages/shopmall_third/commodity_details?goods_id=".$goods[$value['goods_id']]['real_goods_id']."&current_livevideo_id=".$video_id;
                    break;
				// case 'meal':
				// 	$redirect_url = C("config.site_url")."/wap.php?g=Wap&c=Mall&a=detail&goods_id=".$live_goods['business_id'];
				// 	break;
				case 'group':
					$temp['shop_redirect_info'] = (object) $temp['shop_redirect_info'];
					$temp['redirect_url'] = C("config.site_url")."/wap.php?g=Wap&c=Group&a=detail&group_id=".$goods[$value['goods_id']]['real_goods_id']."&current_livevideo_id=".$video_id;
					
					if($key == 0){
						// 团购价 < 原价 显示特价商品
						if($goods[$value['goods_id']]['old_price'] > $goods[$value['goods_id']]['price']){
							$temp['label_arr'][] = [
								'type' => 'discount',
								'name' => '特价商品',
							];
						}
						// 微信/app优惠价格 大于0 显示活动中
						if($goods[$value['goods_id']]['wx_cheap'] > 0){
							$temp['label_arr'][] = [
								'type' => 'activity',
								'name' => '活动中',
							];
						}
					}
					
					// 查找最优的优惠券
					$temp['coupon'] = D("Card_new_coupon")->get_live_best_coupon($goods[$value['goods_id']]['mer_id'], $goods[$value['goods_id']]['store_id'], $goods[$value['goods_id']]['business'],0,$uid);
					
					break;
			}
			if(empty($temp['shop_redirect_info'])){
				$temp['shop_redirect_info'] = (object) $temp['shop_redirect_info'];
			}
			$returnArr[] = $temp;
		}
		return $returnArr;	

	}

	/**
	 * 获取对应业务下的真实的goodsid
	 * @param  [type] $live_id  [description]
	 * @param  [type] $business shop=外卖 mall=商城...
	 * @return [type]           [description]
	 */
	public function get_goods_percent_by_live($live_id, $business){
		if(empty($live_id) || empty($business)) return [];
		$goods = array_column($this->get_goods_by_liveid($live_id),'goods_id');
		$where = [
			'business' => $business,
			'id' => ['in', $goods]
		];
		$data = D("Live_goods")->where($where)->field("business_id as goods_id,percent")->select();
		return ['goodids' => array_column($data, 'goods_id'), 'percent' => array_column($data, 'percent', 'goods_id')];
	}

	/**
	 * 添加销量
	 * @param goods_id 业务商品ID
	 * @param live_id  直播ID
	 * @param $nums 销售个数
	 * @param $type 'shop' => 外卖  'mall' => 商城  'group' => 团购
	 */
	public function add_sale_nums($goods_id, $live_id, $nums, $type){
		if(empty($goods_id) || empty($live_id) || empty($nums) || empty($type)) return ;
		$where = [
			'business' => $type,
			'business_id' => $goods_id,
			'status' => 1
		];
		$live_goods = M("Live_goods")->where($where)->find();
		if($live_goods){
			$where = [
				'live_id' => $live_id,
				'goods_id' => $live_goods['id'],
				'is_del' => 0
			];
			$live_show_goods = $this->where($where)->find();
			if($live_show_goods){
				$this->where($where)->data(['sale_nums'=>($live_show_goods['sale_nums']+$nums)])->save();
				M("Live_goods")->where(['id'=>$live_goods['id']])->data(['video_sales'=>($live_goods['video_sales']+$nums)])->save();
				return true;
			}
		}
		return false;
	}
}