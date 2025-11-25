<?php
class SpecialAction extends BaseAction{
    protected $send_time_type = array('分钟', '小时', '天', '周', '月');
	public function index(){
        $database_special = D('Special');
        $condition_special['pigcms_id'] = $_GET['id'];
        $now_special = $database_special->where($condition_special)->find();
        if ($now_special && 4==$now_special['type']) {
            // 如果是批发市场，默认不需要登录
        } else if(empty($this->user_session)){
            if($this->is_app_browser){
                $this->error_tips('请先进行登录！',U('Login/index',array('referer'=>urlencode(U('Special/index',$_GET)))));
            }else{
                redirect(U('Login/index',array('referer'=>urlencode(U('Special/index',$_GET)))));
            }
        }

		if($_GET['lat']&&$_GET['long']){
			$long_lat['lat'] = $_GET['lat'];
			$long_lat['long'] = $_GET['long'];
		}else{
			$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
		}
		if(!empty($long_lat)){
			import('@.ORG.longlat');
			$longlat_class = new longlat();
			$long_lat = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);
			$long_lat['long'] = $long_lat['lng'];
			$this->assign('long_lat',$long_lat);
		}
		
		$coupon_id_arr = array();
		if($now_special['coupon']){
			$now_special['coupon'] = unserialize($now_special['coupon']);
			foreach($now_special['coupon'] as $value){
				$coupon_id_arr[] = $value['id'];
			}
		}
		$now_special['coupon_id'] = implode(',',$coupon_id_arr);
		
		$now_special['product_list'] = unserialize($now_special['product_list']);

		$product_list = array();
		foreach($now_special['product_list'] as $key=>$value){
			$tmp_arr = array();
			foreach($value['product'] as $v){
				$tmp_arr[$key][] = $v['id'];
			}
			$product_list[$key] = implode(',',$tmp_arr[$key]);
		}

		$now_special['product_id_arr'] = $product_list;
		$now_special['image']=replace_file_domain($now_special['image']);



		$this->assign('now_special',$now_special);

		$database_special->where($condition_special)->setInc('hits');
        if($now_special['type']==3){
            $this->display('mall_index');
        }elseif($now_special['type']==4){
            $this->display('wholesale_index');
        }elseif($now_special['type']==5){
			$this->display('appoint_index');
		}else{
			$this->display();
		}
	}
	public function ajax_get_coupon_by_ids(){
		$ids = $_POST['ids'];
		$ids_arr = explode(',',$ids);
		if(empty($ids_arr)){
			$this->success([]);
		}
		$res = D('System_coupon')->get_coupon_list_by_ids($ids);
		$time = time();
		$user_id=$_SESSION['user']['uid'];
		foreach($ids_arr as $v){
			$can = true;
			if($res[$v]['end_time']<$time||$res[$v]['start_time']>$time||$res[$v]['status']!=1||$res[$v]['had_pull']==$res[$v]['num']){
				$can = false;
			}elseif($user_id>0 && isset($res[$v]['limit'])){
				$where=array();
				$where['coupon_id']=$v;
				$where['uid']=$user_id;
				$receive_num=M('System_coupon_hadpull')->where($where)->count(1);
				if($receive_num>=$res[$v]['limit']){
					$can=false;
				}

			}
			$arr[]=array(
				'id'=>$res[$v]['coupon_id'],
				'name'=>$res[$v]['name'],
				'order_money'=>floatval($res[$v]['order_money']),
				'discount'=>floatval($res[$v]['discount']),
				'can'=>$can,
			);
		}
		$this->success($arr);
	}

	//通过商品id获取商城商品
	public function ajax_get_mall_goods_by_ids(){
		$ids=I('ids','','trim');
		//var_dump($ids);
		$page=I('get.page',0,'intval');
		$page_count=10;

		// 按添加数据顺序显示
		// 当前页数显示的商品id
		$id_arr = explode(',', $ids);
		$id_arr = array_slice($id_arr,$page,$page_count);

		// 所有商品
		$where=array();
		$where['goods_id']=array('in', $ids);
		$list=M('shop_goods')->where($where)->field('goods_id,store_id,name,image,price,old_price,last_time,sell_count,cat_fid,seckill_price,seckill_type,seckill_open_time,seckill_close_time,sell_day,seckill_stock,today_seckill_count')->select();

		// $list = M('shop_goods')->where($where)->field('goods_id,store_id,name,image,price,old_price,last_time,sell_count,cat_fid,seckill_price,seckill_type,seckill_open_time,seckill_close_time,sell_day,seckill_stock,today_seckill_count')->limit($page,$page_count)->select();

		if(C('config.open_kefu_url') && C('config.kefu_mer_id')>0){
			$store_arr=M('Merchant_store')->where(array('mer_id'=>C('config.kefu_mer_id')))->getField('store_id',true);
		}

		$now_time=time();
		$goods_image=new goods_image();

		$data_list_tmp = array();
        $shopMod = new Merchant_store_shopModel();
		foreach ($list as $k => $v) {
			$img_arr = D('Shop_goods')->reg_good_img($v['image'],$goods_image);
			$v['image'] = thumb($img_arr['0']['title'], 320, 320);
			//自营商品
			if(is_array($store_arr) && in_array($v['store_id'], $store_arr)){
				$v['is_self']=1;
			}else{
				$v['is_self']=0;
			}
			if($v['sell_count']<=0){
				//十天之内未销售展示“新品上线”
				$time=$now_time-$v['last_time'];
				if($time>864000){
					$v['is_new']=0;
				}else{
					$v['is_new']=1;
				}
			}
			
			//校验商品是否是秒杀商品
			$v = $this->check_seckill_goods($v);
			
			if($v['seckill_price']<=0){
				$v['act_info']=D('Shop_goods')->get_good_act_info($v['goods_id']);
			}

            $time_limited_discount_mod = D('Time_limited_discount_goods');
            $seckill_record = $time_limited_discount_mod->get_in_progress_record($v['goods_id']);
            if(empty($v['act_info']) && empty($seckill_record)){
                $v['sell_count']=D('Shop_goods')->get_goods_sale_nums($v['goods_id']);
            }
            $levelDiscount = $shopMod->getVipLevelDiscount($_SESSION['user']['uid'], $v['store_id'], 'mall');
            $v['vip_show'] = $levelDiscount['show_level_price'] ? true : false;
            $v['vip_tag'] = M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
            $v['vip_price'] = getFormatNumber($v['price'] * $levelDiscount['discount']);

			$list[$k]=$v;
			$data_list_tmp[$v['goods_id']] = $v;
		}

		$data_list = array();
		foreach ($id_arr as $key => $_goods_id) {
			if ($data_list_tmp[$_goods_id]) {
				$data_list[] = $data_list_tmp[$_goods_id];
			}
		}
		$list=array_values($data_list);
		$this->success($list);
	}

	//校验商品是否是秒杀商品
	public function check_seckill_goods($info){
		
		$info['is_seckill_price']=0;
		
		//商城新版的限时优惠
		$time_limited_discount_mod = D('Time_limited_discount_goods');
        $seckill_record = $time_limited_discount_mod->get_in_progress_record($info['goods_id']);
        if ($seckill_record && ($seckill_record['stock']>0 || $seckill_record['stock'] == -1)) {
			// dump($seckill_record);
            //存在限时优惠
            $info['is_seckill_price'] = 1;
            // $info['price'] = $seckill_record['is_spec'] == 1 ? $info['price'] : getFormatNumber($seckill_record['limit_price']);
            $info['price'] = getFormatNumber($info['price']);
            $seckill_stock_num = $seckill_record['stock'];
			if($seckill_record['is_spec'] == 1){
				foreach($seckill_record['spec_list'] as $value){
					if($value['limit_price'] < $info['seckill_price'] || $info['seckill_price'] == 0){
						$info['seckill_price'] = getFormatNumber($value['limit_price']);
					}
				}
			}else{
				$info['seckill_price'] = getFormatNumber($seckill_record['limit_price']);
			}
            $info['seckill_stock'] = $seckill_stock_num;
            $info['limit_num'] = $seckill_record['limit_num'];
            $info['max_num'] = $seckill_record['limit_num'];
            $info['sell_count'] = D('Shop_goods')->get_nums($info['goods_id']);
        }
		
		
		return $info;
	}

	public function ajax_get_shop_by_ids(){
		//和快店列表返回一致$_POST['ids']
		$ids = $_POST['ids'];

		$where = array('lat' => $_POST['user_lat'], 'long' => $_POST['user_long']);
        $page = $_GET['page'] ? $_GET['page'] : 0;
        $page_count = 10;
		$lists = D('Merchant_store_shop')->get_list_by_ids($ids, $where,$page.','.$page_count);

		// dump(D('Merchant_store_shop'));
		$return = array();
		$now_time = date('H:i:s');
		$id_array = explode(',', $ids);
		foreach ($id_array as $store_id) {
			$row = isset($lists['shop_list'][$store_id]) ? $lists['shop_list'][$store_id] : '';
			if (empty($row)) continue;
			$temp = array();
			$temp['id'] = $row['store_id'];
			$temp['name'] = $row['name'];
			$temp['range'] = $row['range'];
			$temp['image'] = $row['image'];
			$temp['star'] = round($row['score_mean'],1);
			$temp['month_sale_count'] = $row['sale_count'] + $row['virtual_sale_count'];
			$temp['delivery'] = $deliver_type == 'pick' ? 0 : $row['deliver'];//是否支持配送
			$temp['delivery_time'] = $row['send_time'];//配送时长
			$temp['send_time_type'] = $row['send_time_type'];//配送时长类型
			$temp['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];//配送时长单位
			$temp['delivery_price'] = floatval($row['basic_price']);//起送价
			$temp['delivery_money'] = floatval($row['delivery_fee']);//配送费
			$temp['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送

			$temp['is_close'] = $row['state'] ? intval($row['is_close']) : 1;

			$temp['coupon_list'] = array();
			if ($row['is_invoice']) {
				$temp['coupon_list']['invoice'] = floatval($row['invoice_price']);
			}
			if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
				$temp['coupon_list']['discount'] = $row['store_discount']/10;
			}
			$system_delivery = array();
			
			foreach ($row['system_discount'] as $row_d) {
				if ($row_d['percentage_status']==1) {
					// 折扣
					$temp['coupon_list']['sys_discount'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money'])*10);

				}else{
					if ($row_d['type'] == 0) {//新单
						$temp['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
					} elseif ($row_d['type'] == 1) {//满减
						$temp['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
					} elseif ($row_d['type'] == 2) {//配送
						if ($row_d['full_money'] > 0 && $row_d['reduce_money'] > 0) {
							$system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
						}
					}
				}
				
			}
			foreach ($row['merchant_discount'] as $row_m) {
				if ($row_d['percentage_status']==1) {
					// 折扣
					$temp['coupon_list']['mer_discount'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money'])*10);
				}else{
					if ($row_m['type'] == 0) {
						$temp['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
					} elseif ($row_m['type'] == 1) {
						$temp['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
					}
				}
			}
			if ($row['deliver']) {
				if ($temp['delivery_system']) {
					$system_delivery && $temp['coupon_list']['delivery'] = $system_delivery;
				} else {
					if ($row['reach_delivery_fee_type'] == 0) {
						if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
							$temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
						}
					} elseif ($row['reach_delivery_fee_type'] == 1) {
						//$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
					} elseif ($row['reach_delivery_fee_type'] == 2) {
						$temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
					}
				}
			}

			$temp['coupon_count'] = count($temp['coupon_list']);
			if($temp['is_close']==1){
			    $append[] = $temp;
            }else{
                $return[] = $temp;
            }
		}
		if($append){
		   $return =  array_merge($return,$append);
		}
		if(empty($return)){
            $return = array();
        }

        $this->success($return);
	}


	/**批发*/

    //通过商品id获取批发商品
    public function ajax_get_wholesale_goods_by_ids(){
        $ids=I('ids','','trim');
        $page=I('get.page',0,'intval');
        $page_count=10;

        $sqlWhere=' AND g.goods_id in ('. $ids . ')';

        $sql = 'SELECT g.*, m.name AS merchant_name, m.phone AS merchant_phone, s.name AS store_name, s.phone AS store_phone';
        $sql .= ' FROM ' . C('DB_PREFIX') . 'market_goods AS g INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON g.store_id=s.store_id INNER JOIN ' . C('DB_PREFIX') . 'merchant AS m ON s.mer_id=m.mer_id AND m.status=1';
        $sql .= $sqlWhere;
        $sql .= ' ORDER BY g.last_time DESC';
        $sql .= ' LIMIT ' . $page . ',' . $page_count;
        $list = M('Market_goods')->query($sql);


        $goods_image_class = new goods_image();
        foreach ($list as &$v) {
            if(!empty($v['image'])){
                $tmp_pic_arr = explode(';', $v['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $v['pic'] || $v['pic'] = $goods_image_class->get_image_by_path($value, 's');
                }
            }
            $v['image'] = $v['pic'];
        }
        $list=(array)$list;
        $this->success($list);
    }

	//通过id获取预约商品
	public function ajax_get_appoint_goods_by_ids()
	{
		$ids=I('ids','','trim');

		$page=I('get.page',0,'intval');
		$page_count=10;
		$where['appoint_status'] = 0;
		$where['check_status'] = 1;
		$where['appoint_id'] = array('in',$ids);
		$list = M('Appoint')->where($where)->limit($page,$page_count)->order('appoint_id desc')->select();
		$group_image_class = new appoint_image();
		foreach ($list as $k => &$v) {
			$v['all_pic'] = $group_image_class->get_allImage_by_path($v['pic']);
			$v['sell_count'] = M('Appoint_order')->where(array('appoint_id'=>$v['appoint_id']))->count();
		}
		$list=(array)$list;
		$this->success($list);
	}
}
	
?>