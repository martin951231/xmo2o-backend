<?php
/*
 * 地图处理
 *
 */
class SpecialAction extends BaseAction{
	public function index(){
		if(empty($_POST['id'])){
			$this->returnCode('20150001');
		}
	}

	// 系统后台外卖专题接口
	public function getSpecialDetail(){
		$returnArr = [];
		$database_special = D('Special');
        $condition_special['pigcms_id'] = $_POST['id'];
        $now_special = $database_special->where($condition_special)->find();
		
		if($_POST['long']){
			$_POST['lng'] = $_POST['long'];
		}
		
		if($_POST['lat']&&$_POST['long']){
			$long_lat['lat'] = $_POST['lat'];
			$long_lat['long'] = $_POST['long'];
		}else{
			$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
		}

		if(!empty($long_lat)){
			import('@.ORG.longlat');
			$longlat_class = new longlat();
			$long_lat = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);
			$long_lat['long'] = $long_lat['lng'];
		}
		$returnArr['long_lat'] = $long_lat;
		$returnArr['pigcms_id'] = $now_special['pigcms_id'];
		$returnArr['type'] = $now_special['type'];
		$returnArr['name'] = htmlspecialchars_decode($now_special['name']);
		$returnArr['bgcolor'] = $now_special['bgcolor'];
		$returnArr['desc'] = $now_special['desc'];
		$returnArr['hits'] = $now_special['hits'];
		$returnArr['province_id'] = $now_special['province_id'];
		$returnArr['city_id'] = $now_special['city_id'];
		$returnArr['image'] = replace_file_domain($now_special['image']);

		// 优惠券
		$coupon_id_arr = array();
		$couponArr = [];
		if($now_special['coupon']){
			$now_special['coupon'] = unserialize($now_special['coupon']);
			$coupon_id_arr = array_column($now_special['coupon'], 'id');

			$res = D('System_coupon')->get_coupon_list_by_ids($coupon_id_arr);
			$time = time(); 
			$cate_platform = D('System_coupon')->cate_platform();
			foreach($res as $v){
				// 是否否领用过 可以去使用
				$is_had = false;
				// 是否可以领取
				$can = true;

				if($v['end_time']<$time||$v['start_time']>$time||$v['status']!=1||$v['had_pull']==$v['num']){
					$can = false;
				}elseif($this->_uid>0){
					$where=array();
					$where['coupon_id']=$v['coupon_id'];
					$where['uid']=$this->_uid;
					$where['is_use']=0;
					$receive_num=M('System_coupon_hadpull')->where($where)->count(1);
					if($receive_num){
						$is_had=true;
					}

					if (isset($v['limit'])) {
						$where=array();
						$where['coupon_id']=$v['coupon_id'];
						$where['uid']=$this->_uid;
						$receive_num=M('System_coupon_hadpull')->where($where)->count(1);
						if($receive_num>=$v['limit']){
							$can=false;
						}
					}

				}
				$couponArr[]=array(
					'id'=>$v['coupon_id'],
					'name'=>$v['name'],
					'order_money'=>floatval($v['order_money']),
					'discount'=>floatval($v['discount']),
					'is_discount'=>$v['is_discount'],
					'discount_value'=>$v['discount_value'],
					'can'=>$can,
					'is_had'=>$is_had,
					'img'=>replace_file_domain($v['img']),
				);
			}
		}

		// 优惠券
		$returnArr['couponArr'] = $couponArr;

		// 店铺分类
		$returnArr['product_list'] = unserialize($now_special['product_list']);

		$_POST['pageSize'] = 5;
        $_POST['page'] = 1;
        $_POST['city_id'] = $_POST['city_id'] ? $_POST['city_id'] : $_POST['now_city'];
		$product_list = array();
		foreach($returnArr['product_list'] as $key=>$value){
			$tmp_arr = array();
			foreach($value['product'] as $v){
				$tmp_arr[$key][] = $v['id'];
			}
            $_POST['pageSize'] = count($tmp_arr[$key]);
			$returnArr['product_list'][$key]['product_ids'] = implode(',',$tmp_arr[$key]);
			unset($returnArr['product_list'][$key]['product']);
        	$_POST['store_ids'] = $returnArr['product_list'][$key]['product_ids'] ;
        	$returnArr['product_list'][$key]['store_list'] = D('Merchant_store_shop')->getShopList($_POST);

            //按照距离排序了
            $shopLists = $returnArr['product_list'][$key]['store_list']['store_list'];
            $returnArr['product_list'][$key]['store_list']['store_list'] = $shopLists;
		}

		$database_special->where($condition_special)->setInc('hits');
		$this->returnCodeOk($returnArr);
	}

	// 获得快店店铺列表
	public function getShopList(){
		$returnArr = [];

		$ids = $_POST['ids'];
		
		// $where = array('lat' => $_POST['user_lat'], 'long' => $_POST['user_long']);
        $page = $_POST['page'] ? $_POST['page'] : 1;
        // $page_count = 10;
        // $page_start = $page_count*($page-1);


        $_POST['pageSize'] = 5;
        $_POST['store_ids'] = $ids;
        $_POST['lat'] = $_POST['lat'];
		$_POST['lng'] = isset($_POST['lng']) ? htmlspecialchars($_POST['lng']) : (isset($_POST['long']) ? htmlspecialchars($_POST['long']) : 0);
        $_POST['page'] = $page;
        $_POST['data_is_return'] = true;
        $returnArr = D('Merchant_store_shop')->getShopList($_POST);
		$this->returnCodeOk($returnArr);
	}

	// 外卖专题自定义页面
	public function getDiypageDetail(){
		$returnArr = [];
		$condition_page['page_id'] = $_POST['page_id'];
		$condition_page['is_remove'] = 0;
		$now_page = D('Merchant_store_diypage')->where($condition_page)->find();
		if(empty($now_page)){
			$this->returnCodeError(L_('该页面不存在'));
		}

        // 经纬度
		if($_POST['lat']&&$_POST['long']){
            $long_lat['lat'] = $_POST['lat'];
            $long_lat['long'] = $_POST['long'];
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
		$returnArr['long_lat'] = $long_lat;
		
		// 点击数加1
		D('Merchant_store_diypage')->where($condition_page)->setInc('hits');

		// 店铺信息
		$now_store = D('Merchant_store')->get_store_by_storeId($now_page['store_id']);

		// 店铺链接
		if ($now_page['page_goods_source']==1) {
            $now_store['url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index#shop-'.$now_store['store_id'];
        } else {
            $now_store['url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Mall&a=store&store_id='.$now_store['store_id'];
        }

		// 会员卡链接
		$now_store['card_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id='.$now_store['mer_id'];
		
		// 自定义组件信息
		$field_list = D('Merchant_store_diypage_field')->get_store_diypage_page($_POST['page_id'],$now_store, $now_page,$this->_uid);

		$returnArr['now_store']['name'] = $now_store['name'];
		$returnArr['now_store']['mer_id'] = $now_store['mer_id'];
		$returnArr['now_store']['store_id'] = $now_store['store_id'];
		$returnArr['now_store']['logo'] = replace_file_domain($now_store['all_pic'][0]);
		$returnArr['now_store']['url'] = $now_store['url'];
		$returnArr['now_store']['card_url'] = $now_store['card_url'];
		if ($now_store['logo']) {
			$returnArr['now_store']['logo'] = replace_file_domain($now_store['logo']);
		}
		$returnArr['now_page'] = $now_page;
		$returnArr['field_list'] = $field_list;
		

        $merchant_store_info = D('Merchant_store')->where(array('store_id' => $now_page['store_id']))->find();
        $merchant_store_shop_info = D('Merchant_store_shop')->where(array('store_id' => $now_page['store_id']))->find();
        $row = array_merge($merchant_store_info, $merchant_store_shop_info);
        $is_close = 1;
        if (D('Merchant_store_shop')->checkTime($row)) {
            $is_close = 0;
        }

		$returnArr['is_close'] = $is_close;

		$this->returnCodeOk($returnArr);
	}
}