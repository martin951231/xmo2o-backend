<?php
class MerchantAction extends BaseAction{
	public function around(){
		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
		$this->assign('long_lat',$long_lat);
		$this->display();
	}
	public function ajaxAround(){
		$this->header_json();
		$list = D('Merchant')->get_merchants_by_long_lat($_POST['lat'], $_POST['lng'],2000);
		echo json_encode($list);
	}
	//	店铺列表
	public function store_list(){
		//顶部广告
		$wap_store_list_Adver = D('Adver')->get_adver_by_key('wap_store_list',5);
		$this->assign('wap_index_top_adver',$wap_store_list_Adver);

		//一图广告位
		$wap_store_list_1_img = D('Adver')->get_adver_by_key('wap_store_list_1_img',1);
		$this->assign('wap_store_list_1_img',$wap_store_list_1_img);
		//dump($wap_store_list_1_img);exit;

		//自定义页面
		$show_ad = $this->config['store_list_page_show_ad'];
		$center_type = $this->config['store_list_page_type'];
		if($show_ad){
			$where['type'] =$center_type;
			$slider_list = M('merchant_store_list_page')->where($where)->order('sort DESC')->select();
			//dump($slider_list);
			$this->assign('slider_list',$slider_list);
		}

		//导航条
		$tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_store_list',0);
		//dump($tmp_wap_index_slider);exit();
		$wap_index_slider = array();
		if(count($tmp_wap_index_slider) >= 10 &&$this->config['wap_slider_number'] == 10){
			$wap_slider_number = $this->config['wap_slider_number'];
		}else{
			$wap_slider_number = 8;
		}
		foreach($tmp_wap_index_slider as $key=>$value){
			$tmp_i = floor($key/$wap_slider_number);
			$wap_index_slider[$tmp_i][] = $value;
		}
		$this->assign('wap_index_slider',$wap_index_slider);
		$this->assign('wap_index_slider_number',$wap_slider_number);
		//判断分类信息
		$cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';
		$this->assign('now_cat_url', $cat_url);

		//判断地区信息
		$area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
		$this->assign('now_area_url',$area_url);

		$circle_id = 0;
		if(!empty($area_url)){
			$tmp_area = D('Area')->get_area_by_areaUrl($area_url);
			if(empty($tmp_area)){
				$this->error('当前区域不存在！');
			}
			$this->assign('now_area', $tmp_area);

			if ($tmp_area['area_type'] == 3) {
				$now_area = $tmp_area;
			} else {
				$now_circle = $tmp_area;
				$this->assign('now_circle', $now_circle);
				$now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
				if (empty($tmp_area)) {
					$this->error('当前区域不存在！');
				}
				$circle_url = $now_circle['area_url'];
				$circle_id = $now_circle['area_id'];
				$area_url = $now_area['area_url'];
			}
			$this->assign('top_area', $now_area);
			$area_id = $now_area['area_id'];
		}else{
			$area_id = 0;
		}

		//判断排序信息
		$sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';

		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 86400);	//获取用户的经度和纬度
        if(empty($long_lat) || empty($long_lat['long']) || empty($long_lat['lat'])){
			$sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
			$sort_array = array(
				array('sort_id'=>'defaults','sort_value'=>'默认排序'),
				array('sort_id'=>'rating','sort_value'=>'评价最高'),
				array('sort_id'=>'start','sort_value'=>'最新发布'),
				array('sort_id'=>'solds','sort_value'=>'人气最高'),
			);
		} else {
			$sort_array = array(
				array('sort_id'=>'juli','sort_value'=>'离我最近'),
				array('sort_id'=>'rating','sort_value'=>'评价最高'),
				array('sort_id'=>'start','sort_value'=>'最新发布'),
				array('sort_id'=>'solds','sort_value'=>'人气最高'),
			);
			$this->assign('long_lat',$long_lat);
		}
		foreach($sort_array as $key=>$value){
			if($sort_id == $value['sort_id']){
				$now_sort_array = $value;
				break;
			}
		}
		$this->assign('sort_array',$sort_array);
		$this->assign('now_sort_array',$now_sort_array);

		//所有分类 包含2级分类
		$all_category_list = D('Merchant_category')->get_all_category();
		$this->assign('all_category_list',$all_category_list);

		//根据分类信息获取分类
		if(!empty($cat_url)){
			$now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
			if(empty($now_category)){
				$this->error_tips('此分类不存在！');
			}
			$this->assign('now_category',$now_category);

			if(!empty($now_category['cat_fid'])){
				$f_category = D('Merchant_category')->get_category_by_id($now_category['cat_fid']);
				$all_category_url = $f_category['cat_url'];
				$category_cat_field = $f_category['cat_field'];

				$top_category = $f_category;
				$this->assign('top_category',$f_category);

				$get_grouplist_catfid = 0;
				$get_grouplist_catid = $now_category['cat_id'];
			}else{
				$all_category_url = $now_category['cat_url'];
				$category_cat_field = $now_category['cat_field'];
				$top_category = $now_category;
				$this->assign('top_category',$now_category);

				$get_grouplist_catfid = $now_category['cat_id'];
				$get_grouplist_catid = 0;
			}
		}
		$all_area_list = D('Area')->get_all_area_list();
		$this->assign('all_area_list',$all_area_list);
		//$this->assign(D('Merchant')->wap_get_Merchant_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id,$_GET['w']));

		/* 粉丝行为分析 */
		$this->behavior(array('model'=>'Merchant_store_list'));
		//指定商圈，去除掉右下角导航
		$default_circle_id=I('get.default_circle_id',0,'intval');
		if($default_circle_id>0){			
			$circle_name=M('Area')->where(array('area_id'=>$default_circle_id))->getField('area_name');
			$this->assign('circle_name',$circle_name);
			$this->assign('no_small_footer',1);
		}
		$this->display('store_list');
	}

	//商圈分类列表
	public function circle_list(){
		$circle_category_id=I('get.circle_category_id',0,'intval');
		if(IS_POST){
			$location = D('User_long_lat')->getLocation($_SESSION['openid'],0);
			$return=D('Area')->get_circle_data_by_menu($circle_category_id,$location['lat'],$location['long']);

			$where=array();
			$where['m.status']=1;
			$where['s.status']=1;
			$circle_arr=M('Merchant_store')->alias('m')->join('inner join '.C('DB_PREFIX').'merchant s on m.mer_id=s.mer_id')->where($where)->group('m.circle_id')->getField('circle_id,count(1)',true);

			foreach ($return['list'] as $key => $value) {
				if($location){
					$value['range_txt'] = $this->wapFriendRange($value['juli']);
				}
				$value['store_num']=intval($circle_arr[$value['area_id']]);
				$value['url']=U('Merchant/store_list',array('default_circle_id'=>$value['area_id']));

				$return['list'][$key]=$value;
			}
			echo json_encode($return);
		}else{
			$info=M('Circle_category')->where(array('id'=>$circle_category_id))->find();
			if(empty($info)){
				$this->error_tips('商圈分类不存在！');
			}
			$long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);	//获取用户的经度和纬度
			if(!empty($long_lat)){
				$this->assign('long_lat',$long_lat);
			}
			$this->assign('info',$info);
			$this->display();
		}
	}


	public function ajaxList(){
		$this->header_json();
		//判断分类信息
		$cat_url = !empty($_GET['cat_url']) ? $_GET['cat_url'] : '';

		//判断地区信息
		$area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
		$circle_id = 0;
		if(!empty($area_url)){
			$condition_area['area_pid'] = $this->config['now_city'];
			$condition_area['area_url'] = $area_url;
			$condition_area['is_open'] = '1';
			$tmp_area = D('Area')->where($condition_area)->find();
			if(empty($tmp_area)){
				$tmp_area = D('Area')->get_area_by_areaUrl($area_url);
			}
			if(empty($tmp_area)){
				$this->error('当前区域不存在！');
			}
			$tmp_area_id	=	$tmp_area['area_id'];

			if ($tmp_area['area_type'] == 3) {
				$now_area = $tmp_area;
			} else {
				$now_circle = $tmp_area;
				$now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
				if (empty($tmp_area)) {
					$this->error('当前区域不存在！');
				}
				$circle_url = $now_circle['area_url'];
				$circle_id = $now_circle['area_id'];
				$area_url = $now_area['area_url'];
			}
			$area_id = $now_area['area_id'];
		}else{
			$area_id = 0;
		}
		//判断商圈分类信息
		$default_circle_id=I('get.default_circle_id','','intval');
		if($default_circle_id>0){
			$circle_id=$default_circle_id;
		}
		//判断排序信息
		$sort_id = !empty($_GET['sort_id']) ? $_GET['sort_id'] : 'juli';
		if($_GET['user_long'] && $_GET['user_lat']){
			$long_lat = array(
				'long'=>$_GET['user_long'],
				'lat'=>$_GET['user_lat'],
			);
		}else if($_SESSION['openid']){
			$long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],10);
		}
		if(empty($long_lat) || empty($long_lat['lat']) || empty($long_lat['long'])){
			$sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
		}
		if($_GET['page'] == 1){
			if($long_lat && $long_lat['lat'] && $long_lat['long']){
				$condition_field = "`img`,`area_id`,`introduce`,`market_name`,`market_id`, ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$long_lat['lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$long_lat['lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long_lat['long']}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli";
			}else{
				$condition_field = "`img`,`area_id`,`introduce`,`market_name`,`market_id`";
			}
			if($tmp_area_id){
				$area_market	=	M('Area_market')->field($condition_field)->order('market_sort desc')->where(array('area_id'=>$tmp_area_id,'is_open'=>1))->limit(3)->select();
			}else{
//				$area_market	=	M('Area_market')->field($condition_field)->order('market_sort desc')->where(array('city_id'=>C('config.now_city'),'is_hot'=>1,'is_open'=>1))->limit(3)->select();
			}
			foreach($area_market as $k=>$v){
				$area_market[$k]['img']		=	$this->config['site_url'].$v['img'];
				$area_market[$k]['count']	=	M('merchant_store')->where(array('market_id'=>$v['market_id'],'status'=>1))->count();
				if($v['juli']){
					$area_market[$k]['range_txt']	=	$this->wapFriendRange($v['juli']);
				}else{
					$area_market[$k]['range_txt']	=	'';
				}
				$area_market[$k]['url']	=	U('store_market_list',array('market_id'=>$v['market_id']));
			}
		}
		//所有分类 包含2级分类
		// $all_category_list = D('Merchant_category')->get_all_category();

		//根据分类信息获取分类
		if(!empty($cat_url)){
			$now_category = D('Merchant_category')->get_category_by_catUrl($cat_url);
			if(empty($now_category)){
				$this->error_tips('此分类不存在！');
			}

			if(!empty($now_category['cat_fid'])){
				$f_category = D('Merchant_category')->get_category_by_id($now_category['cat_fid']);
				$all_category_url = $f_category['cat_url'];
				$category_cat_field = $f_category['cat_field'];

				$top_category = $f_category;

				$get_grouplist_catfid = 0;
				$get_grouplist_catid = $now_category['cat_id'];
			}else{
				$all_category_url = $now_category['cat_url'];
				$category_cat_field = $now_category['cat_field'];
				$top_category = $now_category;

				$get_grouplist_catfid = $now_category['cat_id'];
				$get_grouplist_catid = 0;
			}
		}

		$return = S('wap_store_'.($get_grouplist_catfid ? $get_grouplist_catfid : $get_grouplist_catid).'_'.$area_id.'_'.$sort_id.'_'.$_GET['page']);
		if(empty($return)){
			$return = D('Merchant')->wap_get_storeList_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$sort_id, $long_lat['lat'], $long_lat['long'], $circle_id,$_GET['w']);
			S('wap_store_group_'.($get_grouplist_catfid ? $get_grouplist_catfid : $get_grouplist_catid).'_'.$area_id.'_'.$sort_id.'_'.$_GET['page'],$return,360);
		}
        $m_merchant_category = M('Merchant_category');
        $m_area = M('Area');
		foreach($return['store_list'] as &$storeValue){
		    if ($storeValue['mer_id']) {
		        $menus = M('Merchant')->where(['mer_id' => $storeValue['mer_id']])->getField('menus');
                $menus = explode(",", $menus);
            }
			//得到友好的距离
			if($long_lat&&($sort_id=='defaults'||$sort_id=='juli')){
				$storeValue['range_txt'] = $this->wapFriendRange($storeValue['juli']);
			}else{
				$storeValue['range_txt'] =	'';
			}
			$storeValue['fans_count'] = M('Merchant_user_relation')->where(array('mer_id'=>$storeValue['mer_id']))->count();
			//	预约
			if($this->config['appoint_site_logo'] && (empty($menus) || in_array(60, $menus))){
				$storeValue['now_appoint']	=	D('Appoint')->get_appointlist_by_StoreId($storeValue['store_id'],true,2);
			}
			// 团购和店铺切换显示 获取分类和商圈
            if ($this->config['group_store_index'] == 1) {
                $storeValue['classification'] = M(_view('Merchant_category'))->where(array('cat_id' => $storeValue['cat_id']))->getField('cat_name');
                $storeValue['circle_info'] = $m_area->where(array('area_id' => $storeValue['circle_id']))->getField('area_name');
            }
			
			$storeValue['list_pic'] = thumb($storeValue['list_pic'], 276, 168);
		}
		$return['style'] = 'group';
		$return['market_list'] = isset($area_market)?$area_market:array();
		$return['img']	=	array(
			'group_alias_name'	=>	mb_substr($this->config['group_alias_name'],0,3),
			'waimai_alias_name'	=>	mb_substr($this->config['waimai_alias_name'],0,3),
			'meal_alias_name'	=>	mb_substr($this->config['meal_alias_name'],0,3),
		);
		echo json_encode($return);
		/* 粉丝行为分析 */
		$this->behavior(array('model'=>'Group_index'));
	}
	//	店铺详情
	public function shop(){
		
		redirect($this->config['site_url'] .'/packapp/platn/pages/store/v1/home/index?store_id=' . $_GET['store_id']);
		
		$now_store = D('Merchant_store')->get_store_by_storeId($_GET['store_id']);
		if(empty($now_store)){
			$this->error_tips('该店铺不存在！');
		}
		$now_store['supplier_info'] = htmlspecialchars_decode($now_store['supplier_info']);
		//导航
		$tmp_wap_index_slider = M('Merchant_store_slider')->field('url,pic,name')->where(array('status' => 1, 'store_id' => intval($_GET['store_id'])))->order('sort DESC')->select();
		$slider_num =0;
		$wap_slider_number = $this->config['wap_slider_number'];
		foreach($tmp_wap_index_slider as $key=>$value){
			$tmp_i = floor($key/$wap_slider_number);
			$wap_index_slider[$tmp_i][] = $value;
			$slider_num ++;
		}
		$this->assign('wap_index_slider',$wap_index_slider);
		$this->assign('wap_index_slider_number',$wap_slider_number);

		//收藏信息
		$collection_info = D('Merchant_user_relation')->get_foodshop_collect($this->user_session['uid'],intval($_GET['store_id']));
		if ($collection_info) {
			$this->assign('collection_info', $collection_info);
		}
		
		$this->assign('uid', $this->user_session['uid']);
		//店铺评论
		$reply_list = D('Reply')->get_store_reply_list($now_store['store_id'], 20);
		$reply_count = D('Reply')->where(array('status' => array('lt', 2), 'parent_id' => $now_store['store_id']))->count();
		$this->assign('reply_list', $reply_list);
		$this->assign('reply_count', $reply_count);

		//店铺优惠券
		$card_info   = D('Card_new')->get_card_by_mer_id($now_store['mer_id']);
		$coupon_list = D('Card_new_coupon')->get_coupon_list_by_type_merid('meal', $now_store['mer_id'], 0, 5, -1);
		$this->assign('card_info', $card_info);
		$this->assign('coupon_list', $coupon_list);

		$now_store['business_time'] = $now_store['is_business_open']?D('Merchant_store_shop')->getBuniessName($now_store):'休息中';
		//得到当前店铺的评分
		$store_score = D('Merchant_score')->field('`score_all`,`reply_count`')->where(array('parent_id'=>$now_store['store_id'],'type'=>'2'))->find();
		$this->assign('store_score',$store_score);

		if(!empty($this->user_session)){
			$database_user_collect = D('User_collect');
			$condition_user_collect['type'] = 'group_shop';
			$condition_user_collect['id'] = $now_store['store_id'];
			$condition_user_collect['uid'] = $this->user_session['uid'];
			if($database_user_collect->where($condition_user_collect)->find()){
				$now_store['is_collect'] = true;
			}
		}
		//	快店
		$now_store['wap_url'] = U('Food/shop',array('mer_id'=>$now_store['mer_id'],'store_id'=>$now_store['store_id']));
		$this->assign('now_store',$now_store);
		$store_group_list = D('Group')->get_store_group_list($now_store['store_id'],0,true);
		$this->assign('store_group_list',$store_group_list);

		//	预约
		if($this->config['appoint_page_row']){
			$now_appoint	=	D('Appoint')->get_appointlist_by_StoreId($now_store['store_id'],true);
			$this->assign('now_appoint',$now_appoint);
		}
		/* 粉丝行为分析 */
		D('Merchant_request')->add_request($now_store['mer_id'],array('group_hits'=>1));
		/*人气自增*/
		M('Merchant_store')->where(array('store_id'=>$now_store['store_id']))->setInc('hits');
		/* 粉丝行为分析 */
		$this->behavior(array('mer_id'=>$now_store['mer_id'],'store_id'=>$now_store['store_id']));

		if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_store['mer_id']))->select()) {
			$key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
			$kf_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key . '#serviceList_' . $now_store['mer_id'];
			$this->assign('kf_url', $kf_url);
		}
		$this->assign('is_wap', M('Classify')->field(true)->where(array('token' => $now_store['mer_id']))->find());
		$this->display();
	}
	//	商场店铺列表
	public function store_market_list(){
		$market_id	=	$_GET['market_id'];
		$this->assign('market_id', $market_id);
		/* 粉丝行为分析 */
		$this->behavior(array('model'=>'Merchant_store_market_list'));
		$this->display();
	}
	//	获取商场店铺列表接口
	public function store_market_list_ajax(){
		$this->header_json();
		$market_id	=	$_GET['market_id'];
		$long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
		$return = D('Merchant')->wap_get_storeList($market_id, $long_lat['lat'], $long_lat['long']);
		foreach($return['store_list'] as &$storeValue){
			//得到友好的距离
			if($long_lat){
				$storeValue['range_txt'] =	$this->wapFriendRange($storeValue['juli']);
			}else{
				$storeValue['range_txt'] =	'';
			}
		}
		$return['style'] = 'group';
		$return['img']	=	array(
			'group_alias_name'	=>	substr($this->config['group_alias_name'],0,3),
			'waimai_alias_name'	=>	substr($this->config['waimai_alias_name'],0,3),
			'meal_alias_name'	=>	substr($this->config['meal_alias_name'],0,3),
		);
		echo json_encode($return);
	}
}
?>