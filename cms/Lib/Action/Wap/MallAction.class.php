<?php
class MallAction extends BaseAction
{
	protected $leveloff = '';

	public function init(){
		if(empty($this->user_session)){
			if($this->is_app_browser){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$this->error_tips('请先进行登录！',U('Login/index',$location_param));
			}else{
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				redirect(U('Login/index',$location_param));
			}
		}
		
		if(C('config.mdd_api_url')){
			import('@.ORG.mdd_user');
			$mdd_user = new mdd_user();
			$mdd_result = $mdd_user->select($this->user_session['uid']);
		}

		$now_user = D('User')->get_user($this->user_session['uid']);
		if(empty($now_user)){
			session('user',null);
			$this->error_tips('未获取到您的帐号信息，请重新登录！',U('Login/index'));
		}
		if(!$now_user['status']){
			session('user',null);
			$this->error_tips('您的账号被禁止登录',U('Login/index'));
		}
		$now_user['now_money'] = floatval($now_user['now_money']);
		$now_user['now_money_two'] = number_format(floatval($now_user['now_money']),2);
		$level = M(_view('User_level'))->getField('level,lname,icon');
		$now_user['lname'] = $level[$now_user['level']]['lname'];
		$now_user['icon'] = $level[$now_user['level']]['icon'];
		if( $this->config['zbw_key']){
			$zbw_user = D('ZbwErp')->sync_data($this->user_session['uid']);
		}
		if($this->config['open_allinyun']==1){
			$allinyun = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();
			if($allinyun['bizUserId']){
				import('@.ORG.AccountDeposit.AccountDeposit');
				$deposit = new AccountDeposit('Allinyun');
				$allyun = $deposit->getDeposit();
				// $allinyun = M('User_allinyun_info')->where(array('uid'=>$this->user_session['uid']))->find();
				$allyun->setUser($allinyun);
				$allinyun_user_info = $allyun->queryBalance();
				$now_user['now_money'] = $allinyun_user_info['signedResult']['allAmount']/100;

			}
		}
		$this->now_user = $now_user;
		$this->assign('now_user',$now_user);
		//$this->assign('level',$level);

		//二次验证时间
		if($times = D('Verify_limit')->field('end_time')->where(array('uid'=>$this->user_session['uid'],'times'=>array('lt',2)))->find()){
			$_SESSION['user']['verify_end_time']=$times['end_time'];
		}
	}

	public function index()
	{
		redirect(U('home',$_GET));
	    $_SESSION['session_store_id'] = 0;
		$category_list = D('Goods_category')->get_list();
		$this->assign('cat_fid', isset($category_list[0]['id']) ? $category_list[0]['id'] : 0);
		if( $this->user_session['uid'] && $this->config['open_rand_send']){
			$coupon_html = D('System_coupon')->rand_send_coupon_get(array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$this->user_session['uid']));
			$coupon_html && $this->assign('coupon_html',$coupon_html);
		}
		$this->display();
	}

	//新版首页
	public function home(){
		$show_ad = $this->config['mall_main_page_show_ad'];
		$center_type = $this->config['mall_main_page_center_type'];
		if($show_ad){
			$where['type'] =$center_type;
			$slider_list = M('Mall_main_page_pic_slider')->where($where)->order('sort DESC')->select();
			$this->assign('slider_list',$slider_list);
		}
		$_SESSION['session_store_id'] = 0;
		if( $this->user_session['uid'] && $this->config['open_rand_send']){
			$coupon_html = D('System_coupon')->rand_send_coupon_get(array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$this->user_session['uid']));
			$coupon_html && $this->assign('coupon_html',$coupon_html);
		}
		$this->check_footer_position();
		$this->display();
	}

	//获取首页信息
	public function ajax_home(){
		//轮播图
		$banner_list = D('Adver')->get_adver_by_key('wap_mall_index_top',5);
		foreach($banner_list as &$item){
			$item['pic'] = thumb_img($item['pic'], 750, 280);
		}
		
		//首页单图
		$adver_img=D("Adver")->get_adver_by_key("wap_mall_adver", 1);
		//中间单图==>郭先森
		$adverMiddleImg = D('Adver')->get_adver_by_key('index_middle_mall',1);

		//分类
		$category_list = D('Goods_category')->get_list(0,2);
		$list=array();
		foreach ($category_list as $key => $value) {
			$list[]=$value;
		}
        //设置商品区域展示
        $addrs=$this->getUserMsg($this->user_session['uid']);
        if(empty($addrs)){
            $arr['province_id']='';
            $arr['city_id']=C('config.now_city');
            $arr['area_id']='';
        }else{
            $arr['province_id']=$addrs['province_id'];
            $arr['city_id']=$addrs['city_id'];
            $arr['area_id']=$addrs['area_id'];
        }
		//导航
		$slider_list = D('Slider')->get_slider_by_key('wap_mall_slider', 0);
		foreach($slider_list as &$item){
			$item['pic'] = thumb_img($item['pic'], 72);
		}
		
		//限时购商品
		$kill_data=D('Shop_goods')->get_skill_goods_list('',1,4,$arr);
		//团购商品
		$group_data=D('Mall_group_act')->get_group_goods_list('',0,1,3,1,1,$arr);
		//砍价商品
		$cut_data=D('Mall_bargain_act')->get_cut_goods_list('',1,3,1,$arr);
		
		$data=array();
		$result_arr['banner_list']=(array)$banner_list;
		$result_arr['adver_img']=$adver_img;
		$result_arr['adverMiddleImg']=$adverMiddleImg;
		$result_arr['category_list']=(array)$list;
		$result_arr['slider_list']=(array)$slider_list;
		$result_arr['kill_data']=(array)$kill_data;
		$result_arr['group_data']=(array)$group_data;
		$result_arr['cut_data']=(array)$cut_data;
		echo json_encode($result_arr);
	}

	//获取分类信息
	public function get_menu_info(){
		$cat_fid=I('cat_fid','','intval');
		$result_arr=array();
		if($cat_fid>0){
			//轮播图
			$where=array();
			$where['cat_id']=$cat_fid;
			$where['type']=2;
			$banner_list=M('Goods_category_banner')->where($where)->select();
			foreach ($banner_list as $k => $v) {
				$v['image']=file_domain().'/upload/goodsbanner/'.$v['image'];
				$banner_list[$k]=$v;
			}
			//一级分类
			$slider_list=D('Goods_category')->get_menu_list($cat_fid);	
		}else{
			//轮播图
			$banner_list = D('Adver')->get_adver_by_key('wap_mall_index_top',5);
			//导航	
			$slider_list = D('Slider')->get_slider_by_key('wap_mall_slider', 0);
		}				
		$data=array();
		$data['banner_list']=(array)$banner_list;
		$data['slider_list']=(array)$slider_list;
		$result_arr['error']=0;
		$result_arr['msg']='';
		$result_arr['data']=$data;
		exit(json_encode($result_arr));
	}

	//获取优选商品接口
	public function get_goods_list(){
		$page=I('page',1,'intval');
		$page_size=I('page_size',6,'intval');
		$cat_fid=I('cat_fid','','intval');
        $addr=I('addr','');
		//获取商品列表
		$where=array();
		if($cat_fid>0){
			$where['cat_fid']=$cat_fid;
		}else{
			$where['cat_fid']='mall_all';
		}
		$where['page']=$page;

		$where['store_theme'] = 1;
		//$where['store_status'] = 1;
		$sort_id = $this->get_store_show_sort();
		$where['sort_id'] = array('in',$sort_id);
        $mall_recommend_num=C('config.mall_recommend_num');//后台配置为你推荐显示数量
		if(!empty($addr) && $addr=='home' && !empty($mall_recommend_num) && $mall_recommend_num>0){
            $where['page_size']=$page_size=$mall_recommend_num*1;
            if($page*$page_size>$mall_recommend_num*1){
                $goods_list=[];
            }else{
                $goods_list=D('Shop_goods')->get_list_by_option($where,4,1,1,2,1);
            }
        }else{
            $where['page_size']=$page_size;
            $goods_list=D('Shop_goods')->get_list_by_option($where,4,1,1,2,1);
        }
		$list=$goods_list['goods_list'];
		if(C('config.open_kefu_url') && C('config.kefu_mer_id')>0){
			$store_arr=M('Merchant_store')->where(array('mer_id'=>C('config.kefu_mer_id')))->getField('store_id',true);
		}

		$goodsMod = new Shop_goodsModel();


       //设置商品区域展示
        $addrs=$this->getUserMsg($this->user_session['uid']);
        if(empty($addrs)){
            $arr['province_id']='';
            $arr['city_id']=C('config.now_city');
            $arr['area_id']='';
        }else{
            $arr['province_id']=$addrs['province_id'];
            $arr['city_id']=$addrs['city_id'];
            $arr['area_id']=$addrs['area_id'];
        }
        $area_id=D('Shop_goods')->getAreaId($arr);
            foreach ($list as $k => $v){
                if($v['use_area']==1){
                    $goods_list = D('Shop_goods_area')->where(array('did'=>$v['goods_id'],'store_id'=>array('exp','IS NOT NULL')))->getField('aid',true);
                    if(empty($goods_list)){
                        continue;
                    }
                    if($v['goods_id']==7){
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

		foreach ($list as $k => $v) {
            $list[$k]['is_booking']=0;
            //$v['is_booking']==0;
			$v['image'] = thumb_img($v['image'],352);
			//自营商品
			if(is_array($store_arr) && in_array($v['store_id'], $store_arr)){
				$v['is_self']=1;
			}else{
				$v['is_self']=0;
			}
			if($v['is_seckill_price']){
				
			}else{
				$act_info=D('Shop_goods')->get_good_act_info($v['goods_id']);
				$v['url']=U('Mall/detail',array('goods_id'=>$v['goods_id'],'group_id'=>$act_info['group_id'],'bar_id'=>$act_info['bar_id']),true,false,true);
				$v['act_info']=$act_info;
                $list[$k]['is_booking']=$act_info['is_booking'];
                //$v['is_booking']=$act_info['is_booking'];
			}
			//商城商品新增虚拟销量
			$v['sell_count'] += $v['mall_virtual_sales'];

			//积分抵扣金额
            $rs = $goodsMod->getScoreMaxByGoodsId($v['goods_id']);

            //养老金
			$v['old_age_pension'] = D("Percent_rate")->getOldAgePension($v['mer_id'], 'mall', $v['price']);

            //增加积分抵扣
	        if ($rs['is_show']) {
                $rs['reduce_money'] = $rs['is_percent'] ? getFormatNumber($v['price'] * $rs['score_max_percent']) : getFormatNumber($rs['score_max'] / C('config.user_score_use_percent'));
	            $v['price_type'] = 2;
	            $v['account_money'] = $v['price'] - $rs['reduce_money'] > 0 ? getFormatNumber($v['price'] - $rs['reduce_money']) : 0;
	            $v['account_score'] = intval($rs['reduce_money'] * C('config.user_score_use_percent'));
                $v['score_use_max'] = $v['account_score'];
                $v['score_reduce_money'] = $rs['reduce_money'];
	        }

			$list[$k]=$v;
		}
		$set_num = isset($this->config['mall_recommend_num'])?$this->config['mall_recommend_num']:30;//设置的总数量
		if($set_num<$goods_list['total']){//只取$set_num条数据
			$goods_list['total'] = $set_num;
			$now_total_page = ceil($set_num/$page_size);
			$goods_list['total_page'] = $now_total_page;
			if($goods_list['next_page']>$now_total_page){
				$goods_list['next_page'] = 0;
			}
			if($page>$now_total_page){//当前页大于总页数
				$list = array();
			}elseif($page==$now_total_page){//如果当前页是最后页面需要截取数据
				$cut_num = $set_num-($page_size*($now_total_page-1));
				$list = array_slice($list,0,$cut_num);
			}
		}
		$goods_list['goods_list']=$list;
		$goods_list = unserialize(str_replace(array('NAN;','INF;'),'0;',serialize($goods_list)));
		echo json_encode((array)$goods_list);
	}

	public function ajax_index()
	{
		$return = array();
		$return['banner_list'] = D('Adver')->get_adver_by_key('wap_mall_index_top', 5);
		$return['slider_list'] = D('Slider')->get_slider_by_key('wap_mall_slider', 0);
		$return['category_list'] = D('Goods_category')->get_list();
		
		
		$where = array();
		$where['cat_fid'] = $return['category_list'][0]['id'];
		$where['page'] = 1;
		$where['store_theme'] = 1;

		
		
		$return['goods_list'] = D('Shop_goods')->get_list_by_option($where, 1,1,1,2);
		
		$_SESSION['session_store_id'] = 0;
		echo json_encode($return);
	}
	
	//分类页
	public function category()
	{
		$category_list = D('Goods_category')->get_list(1);
		$icon_arr=array();
		$i=1;
		foreach ($category_list as $k => $v) {
			$icon_arr[$i]=0;
			foreach ($v['son_list'] as $k2 => $v2) {
				if(!empty($v2['icon_path'])){
					$icon_arr[$i]=1;
					break;
				}
			}
			$i++;
		}
		$this->check_footer_position();
		$this->assign('category_list',$category_list);
		$this->assign('icon_arr',$icon_arr);
		$this->display('category');
	}
	
	public function goods_list()
	{
		$cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
		$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
		
		$title = '';
		if ($category = D('Goods_category')->field(true)->where(array('id' => $cat_fid))->find()) {
			$title = $category['name'];
			$category_list=D('Goods_category')->where(array('fid'=>$cat_fid,'status'=>1))->field('id,fid,name')->order('`sort` DESC, `id` ASC')->select();
			$this->assign('category_list',$category_list);
		}
		if ($category = D('Goods_category')->field(true)->where(array('id' => $cat_id))->find()) {
			$title = $title ? $title . '-' . $category['name'] : $category['name'];
		}
		$this->assign(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id));
		$this->assign('title', $title);
		$this->assign('names', $names);
		$this->display();
	}

	/*获取分类属性*/
	public function ajax_property(){
		$cat_fid=I('cat_fid','','intval');
		$cat_id=I('cat_id','','intval');
		$list=array();
		if($cat_id<=0){
			exit(json_encode($list));
		}
		$properties = D('Goods_properties')->field(true)->where(array('cat_id' => $cat_id, 'status' => 1))->select();
		$pids = array();
		$names = array();
		foreach ($properties as $pro) {
			$pids[] = $pro['id'];
			$names[] = $pro['name'];
		}
		$names = implode(',', $names);
		$value_list = array();
		if ($pids) {
			$temp_list = D('Goods_properties_value')->field(true)->where(array('pid' => array('in', $pids)))->select();
			foreach ($temp_list as $val) {
				$value_list[$val['pid']][] = $val;
			}
		}
		foreach ($properties as &$vo) {
			if (isset($value_list[$vo['id']])) {
				$vo['value_list'] = $value_list[$vo['id']];
			} else {
				$vo['value_list'] = null;
			}
		}
		$properties=(array)$properties;
		exit(json_encode($properties));
	}
	
	public function ajax_list()
	{
		$where = array();
		$where['cat_fid'] = isset($_POST['cat_fid']) ? intval($_POST['cat_fid']) : 0;
		$where['cat_id'] = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
		$where['store_id'] = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
		$where['page'] = isset($_POST['page']) ? intval($_POST['page']) : 0;
		
		$pids1 = isset($_POST['pids1']) ? trim(htmlspecialchars($_POST['pids1'])) : '';
		$pids2 = isset($_POST['pids2']) ? trim(htmlspecialchars($_POST['pids2'])) : '';
		$pids3 = isset($_POST['pids3']) ? trim(htmlspecialchars($_POST['pids3'])) : '';
		$pids4 = isset($_POST['pids4']) ? trim(htmlspecialchars($_POST['pids4'])) : '';
		$where['key'] = isset($_POST['key']) ? trim(htmlspecialchars($_POST['key'])) : '';
		$search_type = isset($_POST['search_type']) ? intval($_POST['search_type']) : 0;
		$sort = isset($_POST['sort']) ? intval($_POST['sort']) : 1;//排序字段(1:goods_id, 2:sell_count, 3:price)
		if (!in_array($sort, array(1, 2, 3))) $sort = 1;
		$sort_type = isset($_POST['sort_type']) ? intval($_POST['sort_type']) : 1;//排序方式(1:DESC, 2:ASC)
		if ($sort_type != 1 && $sort_type != 2) $sort_type = 1;
		
		$where['pids'] = null;
		if ($pids1) $where['pids'][] = $pids1;
		if ($pids2) $where['pids'][] = $pids2;
		if ($pids3) $where['pids'][] = $pids3;
		if ($pids4) $where['pids'][] = $pids4;
        $where['store_theme'] = 1;
        
		if ($search_type == 0) {
			$return = D('Shop_goods')->get_list_by_option($where, $sort, $sort_type,1,2);
		} else {
			$return = D('Merchant_store_shop')->get_store_by_search($where,2);
		}
		if(C('config.open_kefu_url') && C('config.kefu_mer_id')>0){
			$store_arr=M('Merchant_store')->where(array('mer_id'=>C('config.kefu_mer_id')))->getField('store_id',true);
		}
		$list=$return['goods_list'];

		//商品区域展示过滤
        $addrs=$this->getUserMsg($this->user_session['uid']);
        if(empty($addrs)){
            $arr['province_id']='';
            $arr['city_id']=C('config.now_city');
            $arr['area_id']='';
        }else{
            $arr['province_id']=$addrs['province_id'];
            $arr['city_id']=$addrs['city_id'];
            $arr['area_id']=$addrs['area_id'];
        }
        $area_id=D('Shop_goods')->getAreaId($arr);
            foreach ($list as $k => $v){
                if($v['use_area']==1){
                    $goods_list = D('Shop_goods_area')->where(array('did'=>$v['goods_id'],'store_id'=>array('exp','IS NOT NULL')))->getField('aid',true);
                    if(empty($goods_list)){
                        continue;
                    }                    //取交集,无则过滤商品
                    $arrs=array_intersect($area_id, $goods_list);
                    if(empty($arrs)){
                        unset($list[$k]);
                        continue;
                    }
                }
            }
            $list=array_values($list);

		foreach ($list as $k => $v) {
            $list[$k]['is_booking']=0;
			//自营商品
			if(is_array($store_arr) && in_array($v['store_id'], $store_arr)){
				$v['is_self']=1;
			}else{
				$v['is_self']=0;
			}
			if($v['is_seckill_price']){
					
			}else{
				$act_info=D('Shop_goods')->get_good_act_info($v['goods_id']);
				$v['act_info']=$act_info;
                $list[$k]['is_booking']=$act_info['is_booking'];
				$v['url'].='&group_id='.$act_info['group_id'].'&bar_id='.$act_info['bar_id'];
			}
			//2019-11-08新增商城虚拟销量
			$v['sell_count'] += $v['mall_virtual_sales'];
			$v['old_age_pension'] = D("Percent_rate")->getOldAgePension($v['mer_id'], 'mall', $v['price']);
			//增加积分抵扣
			$rs = D('Shop_goods')->getScoreMaxByGoodsId($v['goods_id']);
	        if ($rs['is_show']) {
                $rs['reduce_money'] = $rs['is_percent'] ? getFormatNumber($v['price'] * $rs['score_max_percent']) : getFormatNumber($rs['score_max'] / C('config.user_score_use_percent'));
	            $v['price_type'] = 2;
	            $v['account_money'] = $v['price'] - $rs['reduce_money'] > 0 ? getFormatNumber($v['price'] - $rs['reduce_money']) : 0;
	            $v['account_score'] = intval($rs['reduce_money'] * C('config.user_score_use_percent'));
	        }

	        //增加vip标识显示
            $levelDiscount = (new Merchant_store_shopModel())->getVipLevelDiscount($this->user_session['uid'], $v['store_id'], 'mall');
            $v['vip_show'] = $levelDiscount['show_level_price'] ? true : false;
            $v['vip_tag'] = M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
			if($v['vip_show']){
				$v['price'] = getFormatNumber($v['price'] * $levelDiscount['discount']);
			}
            
            $list[$k]=$v;
            $list[$k]['sell_count']=D('Shop_goods')->get_sell_count($v['goods_id']);
            $list[$k]['image'] = replace_file_domain($v['image']);
		}

		$return['goods_list']=$list;
		exit(json_encode($return));
	}
	
	public function detail()
	{
		D('Shop_goods')->set_goods_stock_cache($_GET['goods_id']);
		if($_GET['is_login']==1){
			$this->isLogin();
		}
		if($_GET['current_livevideo_id']){
			$_SESSION['current_livevideo_id'] = $_GET['current_livevideo_id'];
		}
		if($_GET['current_liveshow_id']){
			$_SESSION['current_liveshow_id'] = $_GET['current_liveshow_id'];
		}
		if($_GET['openid']){
			$_SESSION['share_openid'] = $_GET['openid'];	
		}
		else{
			$_SESSION['share_openid'] = '';		
		}
		/*处理小程序二维码超长不能生成二维码的问题*/
		$p0=I('p0',0,'intval');
		if($p0==1){
			$_GET['goods_id']=I('get.p1',0,'intval');
			$_GET['group_id']=I('get.p2',0,'intval');
			$_GET['bar_id']=I('get.p3',0,'intval');
			$_GET['team_id']=I('get.p4',0,'intval');
		}
		$goods_id = intval($_GET['goods_id']);
		if(empty($goods_id)){
			$this->error_tips('商品不存在！');
		}
		$group_id=I('group_id','','intval');
		$bar_id=I('bar_id','','intval');
		if($group_id<=0 && $bar_id<=0){
			D('Shop_goods')->get_good_act_info($goods_id);
			$group_id=I('group_id','','intval');
			$bar_id=I('bar_id','','intval');
		}
        //更新购物车总量，并返回购物车数据	
        $cartList = $this->save_cookie_data();
        if(!$cartList){
            $cartList = array();
        }else{
            foreach($cartList as &$cart_value){
                $cart_value['productParam'] = $cart_value['productParam'] ? unserialize($cart_value['productParam']) : array();
                $cart_value['count'] = intval($cart_value['count']);
                $cart_value['isSeckill'] = $cart_value['isSeckill'] == 'false' ? false : true;
                $cart_value['store_id'] = $cart_value['shop_id'];
            }
        }
        $this->assign('goods_cart',json_encode($cartList));
		$database_shop_goods = D('Shop_goods');
		$now_goods = $database_shop_goods->get_goods_by_id($goods_id,$group_id);

		if(empty($now_goods)){
			$this->error_tips('商品不存在！');
		}

        $levelDiscount = (new Merchant_store_shopModel())->getVipLevelDiscount($this->user_session['uid'], $now_goods['store_id'], 'mall');

        //每个商品要计算会员价
        $now_goods['vip_show'] = $levelDiscount['show_level_price'] ? true : false;
        $now_goods['vip_tag'] = M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
        $now_goods['vip_price'] = getFormatNumber($now_goods['price'] * $levelDiscount['discount']);
        if($now_goods['list'] && $now_goods['vip_show']){
            foreach ($now_goods['list'] as $k=>$v){
                $now_goods['list'][$k]['price'] = getFormatNumber($v['price'] * $levelDiscount['discount']);
            }
        }


		//门店
		if($_SESSION['session_store_id'] && $_SESSION['session_store_id'] != $now_goods['store_id']){
			$_SESSION['session_store_id'] = $now_goods['store_id'];
		}
		
		if($now_goods['jd_sku_id'] != 0){
            import('ORG.Net.Http');
            $url_state = 'https://bizapi.jd.com/api/product/skuState';
            $request_state['token'] = $this->config['jd_access_token'];
            $request_state['sku'] = $now_goods['jd_sku_id'];
            $return1 = http::curlPost($url_state,$request_state);
            if($return1['result'][0]['state']==0){
                if($return1['resultCode']==2007){
                    $this->error_tips($return1['resultMessage'] . '！错误码：2007');
                }
                M('Shop_goods')->where(array('goods_id'=>$now_goods['goods_id']))->delete();
                $this->error_tips('当前商品已下架！');
            }
            $url_check = 'https://bizapi.jd.com/api/product/check';
            $request_c['token'] = $this->config['jd_access_token'];
            $request_c['skuIds'] = $now_goods['jd_sku_id'];
            $return2 = http::curlPost($url_check,$request_c);
            $this->assign('is7ToReturn',$return2['result'][0]['is7ToReturn']);
            if($return2['result'][0]['saleState']==0){
                $this->error_tips('当前商品不可售！');
            }
            $url_price = "https://bizapi.jd.com/api/price/getSellPrice";
            $data_price['token'] = $this->config['jd_access_token'];
            $data_price['sku']=$now_goods['jd_sku_id'];
            $data_price['queryExts'] =  'containsTax';
            $return_price = Http::curlPost($url_price,$data_price);
            //价格变动
            if($return_price['result'][0]['price'] != $now_goods['cost_price']){
                //价格加成
                $percent = floatval(C('config.jd_price_add_percent'));
                if($return_price['result'][0]['price']>=1000 && $return_price['result'][0]['price']<2000){
                    $percent = floatval(C('config.jd_price_add_percent_gt1000'));
                }elseif($return_price['result'][0]['price']>=2000 && $return_price['result'][0]['price']<3000){
                    $percent = floatval(C('config.jd_price_add_percent_gt2000'));
                }elseif($return_price['result'][0]['price']>=3000){
                    $percent = floatval(C('config.jd_price_add_percent_gt3000'));
                }

                if($percent <= 0){
                    $percent = 1;
                }else{
                    $percent = 1 + ($percent / 100);
                }
                $goods['price'] = round($return_price['result'][0]['price'] * $percent,1);
                $goods['cost_price'] = $return_price['result'][0]['price'];
                M('Shop_goods')->where(array('goods_id'=>$now_goods['goods_id']))->save($goods);
                $now_goods = $database_shop_goods->get_goods_by_id($goods_id);
            }
            else{
                //价格加成随配置项
                $percent = floatval(C('config.jd_price_add_percent'));
                if($now_goods['cost_price']>=1000 && $now_goods['cost_price']<2000){
                    $percent = floatval(C('config.jd_price_add_percent_gt1000'));
                }elseif($now_goods['cost_price']>=2000 && $now_goods['cost_price']<3000){
                    $percent = floatval(C('config.jd_price_add_percent_gt2000'));
                }elseif($now_goods['cost_price']>=3000){
                    $percent = floatval(C('config.jd_price_add_percent_gt3000'));
                }
                if($percent <= 0){
                    $percent = 1;
                }else{
                    $percent = 1 + ($percent / 100);
                }
                $now_goods['cost_price'] = round($now_goods['cost_price'] * $percent,1);
                if($now_goods['price']!=$now_goods['cost_price']){
                    M('Shop_goods')->where(array('goods_id'=>$now_goods['goods_id']))->save(array('price'=>$now_goods['cost_price']));
                    $now_goods = $database_shop_goods->get_goods_by_id($goods_id);
                }
            }
        }
		//如果设置了最小起购，限购就无效
		if ($now_goods['min_num'] > 1) {
		    $now_goods['max_num'] = 0;
		}
		
		$store_id = $now_goods['store_id'];
		
		$where = array('store_id' => $store_id);
		$now_store = D('Merchant_store')->field(true)->where($where)->find();
		if($this->user_session['uid']){
			$this->save_user_city($now_store['city_id'], $now_store['area_id']);
		}
		$this->assign('now_store',$now_store);
		
		
		//商家
		$now_merchant = D('Merchant')->field(true)->where(['mer_id' => $now_store['mer_id']])->find();
		if($now_merchant['status'] != 1){
			$this->error_tips('抱歉，该商品所属商家已关闭！');
		}
		
		
		//资质认证
		// if ($this->config['store_shop_auth'] == 1 && $now_store['auth'] < 3) {
			// $this->error_tips('店铺没有通过资质认证');
		// }
		$now_shop = D('Merchant_store_shop')->field(true)->where($where)->find();
		if (empty($now_shop) || empty($now_store)) {
			$this->error_tips('该店铺没有完善店铺信息');
		}elseif($now_shop['store_theme']!=1){
			$this->error_tips('抱歉，该商品所属店铺商城已关闭！');
		}
		if($now_store['mall_on_sale']==2){
			$this->error_tips('抱歉，该商品所属店铺已被下架，如有疑问，请联系系统管理员！');
		}
		if(!getFormatNumber($now_shop['score_mean'])){
			$now_shop['score_mean'] = 5;
		}
		$score_mean_html = '';
        //处理成html
        for ($i=1; $i <=5; $i++) { 
            if($now_shop['score_mean']>=$i){
                if($now_shop['score_mean'] == $i+0.5){
                    $score_mean_html .= '<i class="full"></i>';
                    $score_mean_html .= '<i class="half"></i>';
                    $i ++;
                }else{
                    $score_mean_html .= '<i class="full"></i>';
                }
            }else{
                $score_mean_html .= '<i></i>';
            }
		}

		$city_name = $province_name = '';
		$areas = D('Area')->field(true)->where(array('area_id' => array('in', array($now_store['province_id'], $now_store['city_id']))))->select();
		foreach ($areas as $area) {
			if ($area['area_pid']) {
				$city_name = $area['area_name'];
			} else {
				$province_name = $area['area_name'];
			}
		}
// 		$discounts = D('Shop_discount')->get_discount_byids(array($store_id));
		$discounts = D('Shop_discount')->getDiscounts($now_store['mer_id'], $store_id,2);
		$row = array_merge($now_store, $now_shop);
		
		$store = array();
		$store['score_mean_html'] = $score_mean_html;
		$store_image_class = new store_image();
		$images = $store_image_class->get_allImage_by_path($row['pic_info']);

		$store['store_id'] = $row['store_id'];
		$store['phone'] = $row['phone'];
		$store['long'] = $row['long'];
		$store['lat'] = $row['lat'];
		$store['store_theme'] = $row['store_theme'];
		$store['adress'] = $row['adress'];
		$store['now_city_name'] = $province_name . ' ' . $city_name;
		$store['is_close'] = 1;
		$now_time = date('H:i:s');
		if ($row['open_1'] == '00:00:00' && $row['close_1'] == '00:00:00') {
			$store['time'] = '24小时营业';
			$store['is_close'] = 0;
		}
		else {
			$store['time'] = substr($row['open_1'], 0, -3) . '~' . substr($row['close_1'], 0, -3);
			if ($row['open_1'] < $now_time && $now_time < $row['close_1']) {
				$store['is_close'] = 0;
			}
			if ($row['open_2'] != '00:00:00' || $row['close_2'] != '00:00:00') {
				$store['time'] .= ',' . substr($row['open_2'], 0, -3) . '~' . substr($row['close_2'], 0, -3);
				if ($row['open_2'] < $now_time && $now_time < $row['close_2']) {
					$store['is_close'] = 0;
				}
			}
			if ($row['open_3'] != '00:00:00' || $row['close_3'] != '00:00:00') {
				$store['time'] .= ',' . substr($row['open_3'], 0, -3) . '~' . substr($row['close_3'], 0, -3);
				if ($row['open_3'] < $now_time && $now_time < $row['close_3']) {
					$store['is_close'] = 0;
				}
			}
		}
		
		$store['name'] = $row['name'];
		$store['store_notice'] = $row['store_notice'];
		$store['txt_info'] = $row['txt_info'];
		$store['image'] = isset($images[0]) ? $images[0] : '';
		if($row['logo']){
			$store['image'] = thumb_img($row['logo'], 90, 90, 'fill');
		}
		$store['star'] = round($row['score_mean'],1);
		$store['month_sale_count'] = $row['sale_count'];
		$store['delivery'] = $row['deliver_type'] == 2 ? false : true;//是否支持配送
		$store['delivery_time'] = $row['send_time'];//配送时长
		$store['delivery_price'] = floatval($row['basic_price']);//起送价
		

		$store['pack_alias'] = $row['pack_alias'];//打包费别名
		$store['freight_alias'] = $row['freight_alias'];//运费别名
		$store['coupon_list'] = array();

		/*获取该商品最低的免邮金额*/
		if($now_goods['freight_template']>0){
			$full_money_arr=M('Express_template_value')->where(array('tid'=>$now_goods['freight_template'],'full_money'=>array('gt',0)))->getField('full_money',true);
			//最低的免邮金额
			if(is_array($full_money_arr)){
				asort($full_money_arr);
				$min_full_money=array_shift($full_money_arr);
				$store['coupon_list']['free_postage']=$min_full_money;
			}
		}
// 		if ($row['is_invoice']) {
// 			$store['coupon_list']['invoice'] = floatval($row['invoice_price']);
// 		}
		if ($row['mall_discount'] != 0 && $row['mall_discount'] != 100) {
			$store['coupon_list']['discount'] = $row['mall_discount']/10;
		}
		$system_delivery = array();
		if (isset($discounts[0]) && $discounts[0]) {
			foreach ($discounts[0] as $row_d) {
				if ($row_d['type'] == 0) {//新单
					$store['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'percentage_status'=>$row_d['percentage_status'],'minus' => floatval($row_d['reduce_money']));
				} elseif ($row_d['type'] == 1) {//满减
					$store['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'percentage_status'=>$row_d['percentage_status'],'minus' => floatval($row_d['reduce_money']));
				} elseif ($row_d['type'] == 2) {//配送
					$system_delivery[] = array('money' => floatval($row_d['full_money']), 'percentage_status'=>$row_d['percentage_status'],'minus' => floatval($row_d['reduce_money']));
				}
			}
		}
		if (isset($discounts[$store_id]) && $discounts[$store_id]) {
			foreach ($discounts[$store_id] as $row_m) {
				if ($row_m['type'] == 0) {
					$store['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
				} elseif ($row_m['type'] == 1) {
					$store['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
				}
			}
		}
		if ($store['delivery'] && false) {
			if ($store['delivery_system']) {
				$system_delivery && $store['coupon_list']['delivery'] = $system_delivery;
			} else {
				if ($is_have_two_time) {
					if ($row['reach_delivery_fee_type2'] == 0) {
						if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
							$store['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee2']));
						}
					} elseif ($row['reach_delivery_fee_type'] == 1) {
						//$store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
					} else {
						$row['delivery_fee2'] && $store['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value2']), 'minus' => floatval($row['delivery_fee2']));
					}
				} else {
					if ($row['reach_delivery_fee_type'] == 0) {
						if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
							$store['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
						}
					} elseif ($row['reach_delivery_fee_type'] == 1) {
						//$store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
					} else {
						$row['delivery_fee'] && $store['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
					}
				}
			}
		}
		//获取店铺商城商品总数
		$goods_count=D('Shop_goods')->where(array('store_id'=>$store_id,'status'=>1,'cat_fid'=>array('gt',0)))->count(1);
		$this->assign('goods_count', $goods_count);
		$store['phone'] = array_filter(explode(' ', $store['phone']));

		// 查看用户是否收藏
		$store['is_collect'] = false;
		if ($this->user_session['uid']) {
			$store['collect_info'] = D('Merchant_user_relation')->get_mall_collect($this->user_session['uid'],$store_id);
			if ($store['collect_info']) {
				$store['is_collect'] = true;
			}
		}

        //用户是否登录
        $isLogin = $this->user_session['uid'] > 0 ? true : false;
        $this->assign('isLogin', $isLogin);

        //查看商品是否收藏
        $now_goods['is_collect'] = false;
        if ($this->user_session['uid'] > 0) {
            $goodsCollect = D('Merchant_user_relation')->get_mallgoods_collect($this->user_session['uid'], $now_goods['goods_id']);
            $goodsCollect && $now_goods['is_collect'] = true;
        }


		$this->assign('store', $store);
		$list=$now_goods['list'];
		
		foreach ($list as $k => $v) {
			$v['reg_img'] = is_array($v['reg_img']) ? $v['reg_img']['s_image'] : ($v['reg_img'] ?  : $now_goods['image']);
			$list[$k]=$v;
		}

        $this->assign('goods_detail', $list ? str_replace(['\t','\r','\r\n'],'',json_encode($list)) : '');

		//秒杀校验
		if($now_goods['is_seckill_price']){
			/**
			if($now_goods['seckill_type']==1){
				$ftime=strtotime(date('Ymd').' '.date('His',$now_goods['seckill_close_time']));
			}else{
				$ftime=$now_goods['seckill_close_time'];
			}**/
			$act_info = D('Time_limited_discount_goods')->get_in_progress_record($now_goods['goods_id']);
			if(!empty($act_info) && $act_info['stock_num']>0){
                $ftime=strtotime($act_info['end_date'].' '.$act_info['end_time']);
                $now_goods['time_clock']=$ftime-time();
                $where1['s.limit_id']=$act_info['id'];//一个商品可以在活动失效之后再次添加活动，所以不加这个判断
                $where1['t.paid']=1;
                $where1['s.goods_id']=$now_goods['goods_id'];
                $num=M('Shop_order_detail')
                    ->alias('s')->join('INNER JOIN '. C('DB_PREFIX') .'shop_order AS t ON s.order_id = t.order_id')
                    ->where($where1)
                    ->sum('s.num');
                if($num>0){
                    $now_goods['sell_count']=$num;
                }
                //活动修改一下展示价
                if($act_info && $now_goods['time_clock'] > 0){
                    $now_goods['price'] = getFormatNumber($act_info['limit_price']);
                }
            }else{
                $now_goods['is_seckill_price']=false;
            }
		}else{
			//多次一举 shop_goods表记录的有销量字段sell_count。这里如果读订单商品表会导致可能和其他地方展示不一致
            /*$where1['t.paid']=1;
            $where1['s.goods_id']=$now_goods['goods_id'];
            $num=M('Shop_order_detail')
                ->alias('s')->join('INNER JOIN '. C('DB_PREFIX') .'shop_order AS t ON s.order_id = t.order_id')
                ->where($where1)
                ->sum('s.num');
            if($num>0){
                $now_goods['sell_count']=$num;
            }*/
        }

        $now_goods['is_begin_seckill_price'] = false;//默认无即将开始限时优惠
        $begin_limit_info=D('Time_limited_discount_goods')->get_in_twelhour_progress_record($now_goods['goods_id']);//是否有即将开始的限时优惠活动
        if(!empty($begin_limit_info)){//仿限时接口
            $now_goods['is_begin_seckill_price'] = true;
            $now_goods['is_seckill_price'] = true;
            $now_goods['price'] = $begin_limit_info['is_spec'] == 1 ? $now_goods['o_price'] : getFormatNumber($begin_limit_info['limit_price']);
            $seckill_stock_num = $begin_limit_info['stock'];
            $now_goods['seckill_price'] = getFormatNumber($begin_limit_info['limit_price']);
            $now_goods['seckill_stock'] = $seckill_stock_num;
            $now_goods['limit_num'] = $begin_limit_info['limit_num'];
            $now_goods['max_num'] = $begin_limit_info['limit_num'];
            $start_arr=explode(":",$begin_limit_info['start_time']);
            $now_goods['seckill_start_time'] = $start_arr[0].":".$start_arr[1];
            $now_goods['time_clock']=$begin_limit_info['time_out'];
            $where1['s.limit_id']=$begin_limit_info['id'];//一个商品可以在活动失效之后再次添加活动，所以不加这个判断
            $where1['t.paid']=1;
            $where1['s.goods_id']=$now_goods['goods_id'];
            $num=M('Shop_order_detail')
                ->alias('s')->join('INNER JOIN '. C('DB_PREFIX') .'shop_order AS t ON s.order_id = t.order_id')
                ->where($where1)
                ->sum('s.num');
            if($num>0){
                $now_goods['sell_count']=$num;
            }
            //活动修改一下展示价
            if($begin_limit_info && $now_goods['time_clock'] > 0){
                $now_goods['price'] = getFormatNumber($begin_limit_info['limit_price']);
            }
        }

		/*替换资源地址*/
        $now_goods['des']= replace_file_domain_content($now_goods['des']);
		
		//新增商品虚拟销量2019-11-08
		$now_goods['sell_count'] += $now_goods['mall_virtual_sales'];

		$now_goods['old_age_pension'] = D("Percent_rate")->getOldAgePension($now_store['mer_id'], 'mall', $now_goods['price']);
		$this->assign('now_goods', $now_goods);

        //调整客服判断
        if (C('IM_JMESSAGE_OPEN')) {
            $whiteUid = C('IM_WHITE_UID');
            $kf = M('Merchant_store_kefu')->where(['store_id' => $store['store_id']])->find();
            $uid = $this->user_session['uid'];
            if ($kf && $uid > 0 && (empty($whiteUid) || in_array($uid, $whiteUid))) {
                $kf_url = build_im_chat_url('user_' . $uid, $kf['username'], 'user2store', ['from' => 'mall', 'goods_id' => $goods_id]);
                $this->assign('kf_url', $kf_url);
            }
        } else {
            if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_store['mer_id']))->select()) {
                $key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
                $kf_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key . '#serviceList_' . $now_store['mer_id'];
                $this->assign('kf_url', $kf_url);
            }
        }

		$user_id=$_SESSION['user']['uid'];

		//会员卡
		if($this->config['coupon_spread_open']){
			$merchant_card_spread_info = D('Card_new')->get_card_by_mer_id_and_uid($now_store['mer_id'],$this->user_session['uid']);
			if(!$merchant_card_spread_info['get_card']){
				$merchant_card_spread_info['discount_price']=round(($now_goods['price']*($merchant_card_spread_info['card_discount']*10))/100,2);
			}
			if($merchant_card_spread_info['card']['self_get']||$merchant_card_spread_info['card']['auto_get']){
				$this->assign('merchant_card_spread_info',$merchant_card_spread_info);
			}
		}

		//积分
		if($this->user_session['uid']) {
			$order_info['total_money'] = $now_goods['price'];
			$now_goods['discount_price']=$now_goods['price'];
			$order_info['order_content'][] = $now_goods;
			$user_score_use = D('Percent_rate')->get_max_core_use($now_store['mer_id'],'mall',$order_info['total_money'],$order_info);

			$user_score_use_money = bcdiv($user_score_use,  $this->config['user_score_use_percent'], 2);
			if(strpos($now_goods['score_percent'],'%')!==false){
				$now_goods['score_percent'] = floatval(str_replace("%",'',$now_goods['score_percent']))/100;
			}else{
				$now_goods['score_percent'] = floatval($now_goods['score_percent']);
			}

			$can_get_score = $now_goods['score_percent']*$now_goods['price'];
			$this->assign('user_score_use_money',$user_score_use_money);
			$this->assign('can_get_score',$can_get_score);
			$this->assign('user_score_use',$user_score_use);
		}

		// 获得分佣佣金
		$spread_rate = D('Percent_rate')->get_user_spread_rate_by_goods($goods_id,'shop');
		if ($spread_rate['first_rate']) {
            $spread_rate['first_money'] = round($now_goods['price']*($spread_rate['first_rate']/100),2);
            $spread_rate['second_money'] = $spread_rate['second_rate']>0?round($now_goods['price']*($spread_rate['second_rate']/100),2):0;
            $spread_rate['third_money'] = $spread_rate['third_rate']>0?round($now_goods['price']*($spread_rate['third_rate']/100),2):0;
		}
		$this->assign('spread_rate',$spread_rate);

		//优惠券
		//获取优惠券	
        $coupon_list=$this->get_store_coupon_list($now_store['store_id']);
		$this->assign('coupon_list',$coupon_list);

		//周期购商品属性
		$periodic_model = D('Mall_periodic_purchase');
		$periodic = $periodic_model->get_goods_one($goods_id,1);
		$this->assign('periodic',$periodic);

		//打包一口价
		$packModel = D('Mall_goods_pack');
		$pack = $packModel->get_one_by_goood($goods_id);
		$this->assign('pack',$pack);

		//预售活动
		$book_act = D('Mall_book_goods')->get_effective_active($goods_id);
		$this->assign('book_act',$book_act);

		//满减
		$model = D('Mall_full_act');
		$full_layer_list = $model->get_full_list($goods_id,$now_store['store_id']);
		if(!empty($full_layer_list['layer_num_id'])){
			$where['id'] = array('in',$full_layer_list['layer_num_id']);

			$layer_list = M('Mall_full_layer')->where($where)->select();
			$coupon_lists = array();
			$price = array();

			foreach($layer_list as $k=>$value){
				$coupon = M('Card_new_coupon')->where(array('coupon_id'=>$value['coupon_id']))->find();
				$shop_good=D('Shop_goods')->where(array('goods_id'=>$value['give_goods_id']))->find();
				$coupon_lists[] = array(
					'coupon_name'=>empty($coupon['name'])?'':$coupon['name'],
					'start_time'=>empty($coupon['start_time'])?'':date('Y-m-d',$coupon['start_time']),
					'end_time'=>empty($coupon['end_time'])?'':date('Y-m-d',$coupon['end_time']),
					'discount'=>empty($coupon['discount'])?'':$coupon['discount'],
					'coupon_id'=>empty($value['coupon_id'])?'':$value['coupon_id'],
					'goods_price'=>$value['goods_price'],
					'shop_good_name'=>$shop_good['name'],
					'goods_id'=>$value['give_goods_id'],

				);

				$price[] = array(
					'goods_price'=>$value['goods_price']
				);
			}

			$this->assign('price',$price);
			$this->assign('coupon_lists',$coupon_lists);
		}else{
			$this->assign('price',[]);
			$this->assign('coupon_lists',[]);
		}

		$this->assign('page_share_content', str_replace(array("\r", "\t", "\r\n", "\n", PHP_EOL), '', $now_goods['describe']));

		//dump($now_shop['mall_deliver_type']);
		$plat_deliver = false;//是否支持平台配送
		if($now_shop['mall_deliver_type']==0||$now_shop['mall_deliver_type']==6||$now_shop['mall_deliver_type']==7||$now_shop['mall_deliver_type']==8){
			$plat_deliver = true;
		}
		$this->assign('plat_deliver',$plat_deliver);

		//拼团商品
		if($group_id>0){
			$group_id=I('get.group_id','','intval');
			$team_id=I('get.team_id','','intval');
			$this->assign('group_id',$group_id);
			$this->assign('team_id',$team_id);
			$group_info=$now_goods['group_info'];
			//获取用户可参团的团队
			$team_data=D("Mall_group_team")->get_can_join_group($group_id,$user_id,$group_info['affect_time'],3,1);
			$group_buy_num=D('Mall_group_act')->get_group_goods_num($group_id,$_SESSION['user']['uid'],$goods_id);
			$group_info['group_buy_num']=(int)$group_buy_num;
			//商家拼团商品数量
			$store_group_num=D('Mall_group_act')->get_store_group_num($store_id);
			$this->assign('store_group_num',$store_group_num);
			$this->assign('group_info',$group_info);
			$this->assign('team_data',$team_data);
			// 获取该商品下单用户信息 19/1/25
			$buy_data=D('Mall_group_act')->get_act_user($now_goods['goods_id'],10,1);
			$this->assign('buy_data',$buy_data); 

			$this->display('group_good_details');
		}elseif($bar_id>0){
			$team_id=I('get.team_id','','intval');
			$bar_info=D('Mall_bargain_act')->get_cut_goods_info($goods_id,$bar_id,$team_id);
			if(empty($bar_info)){
				$this->error_tips('网络出错，请重试！');
			}
			//助力砍价获取砍价用户的信息			
			$team_info=D('Mall_bargain_team')->get_team_info($bar_info,$team_id,$user_id,$now_goods);
			if($team_info['status']==1 && $team_id>0){
				$this->redirect('Mall/detail',array('goods_id'=>$goods_id,'bar_id'=>$bar_id));
			}
			//砍价本人查看
			if($team_info['user_id']==$user_id){
				$team_id='';
			}
			$team_user_list=D('Mall_bargain_team')->get_team_user_list($team_info['id']);
			
			$this->assign('stock_num',$bar_info['stock_num']);
			$this->assign('bar_id',$bar_id);
			$this->assign('team_id',$team_id);
			$this->assign('bar_info',$bar_info);
			$this->assign('team_info',$team_info);
			$this->assign('team_user_list',$team_user_list);
			// 获取该商品下单用户信息 19/1/25
			$buy_data=D('Mall_bargain_act')->get_act_user($now_goods['goods_id'],10,1);
			$this->assign('buy_data',$buy_data);
			$this->display('cut_good_details');
		}
		else{
			//判断用户是否为会员
			$user_id=$_SESSION['user']['uid'];
			$member_info=array();
			$is_vip=0;
			if($user_id>0){
				$level=M('user')->where(array('uid'=>$user_id))->getField('level');
				if($level>0){
					$is_vip=1;
				}
			}
			$member_info['is_vip']=$is_vip;
			$mall_leveloff=M('Merchant_store_shop')->where(array('store_id'=>$store['store_id']))->getField('mall_leveloff');
			$max_level_discount=100;
			if(!empty($mall_leveloff)){
				$level_discount_arr=unserialize($mall_leveloff);
				foreach ($level_discount_arr as $k => $v) {
					$max_level_discount=min($max_level_discount,$v['vv']);
				}
			}
			$member_info['max_level_discount']=round($max_level_discount/10,2);
			
			$this->assign('member_info',$member_info);
			/*获取店铺的推荐商品*/
            //设置商品区域展示
            $arr   = [];
            $addrs = $this->getUserMsg($this->user_session['uid']);
            if (empty($addrs)) {
                $arr['province_id'] = '';
                $arr['city_id']     = C('config.now_city');
                $arr['area_id']     = '';
            } else {
                $arr['province_id'] = $addrs['province_id'];
                $arr['city_id']     = $addrs['city_id'];
                $arr['area_id']     = $addrs['area_id'];
            }
			$recommend_list=D('Shop_goods')->get_recommend_goods($store['store_id'],$now_goods['goods_id'],6,$arr);
			$this->assign('recommend_list',$recommend_list);
			
			/*获取该商品的下单用户信息*/
			$buy_data=D('Shop_order')->get_buy_user($now_goods['goods_id'],10,1);
			$this->assign('buy_data',$buy_data); 

			$this->display();
		}

	}

	//打包一口价活动页面
	public function pack_act(){
		$id = I('id','','intval');
		$model = D('Mall_goods_pack');
		$info = $model->get_one($id);
		if(empty($info)){
			$this->error_tips('抱歉：活动不存在！');
		}elseif(time()<$info['begin_time']){
			$this->error_tips('抱歉：活动尚未开始！');
		}elseif(time()>$info['end_time']){
			//$this->error_tips('抱歉：活动已经结束！');
		}
		$result = $this->deal_pack_cart($info['goods_list']);
		$info['goods_list'] = $result['act_list'];
		$have_num = $result['have_num'];
		$this->assign('have_num',$have_num);
		//更新购物车总量，并返回购物车数据	
        $cartList = $this->save_cookie_data();
        if(!$cartList){
            $cartList = array();
        }else{
            foreach($cartList as &$cart_value){
                $cart_value['productParam'] = $cart_value['productParam'] ? unserialize($cart_value['productParam']) : array();
                $cart_value['count'] = intval($cart_value['count']);
                $cart_value['isSeckill'] = $cart_value['isSeckill'] == 'false' ? false : true;
                $cart_value['store_id'] = $cart_value['shop_id'];
            }
        }
        $this->assign('goods_cart',json_encode($cartList));
		
		$this->assign('info',$info);
		$this->display();
	}
	
	public function deal_pack_cart($act_goods_list){
		$cookie = cookie('cart_cookie');
		$uid = $this->user_session['uid'];
		$return = array();
		$have_num = 0;
		if( $cookie || $uid ){
			$actModel = M('Mall_cart');
			$where = array();
			if($cookie && $uid){
				$w['uid'] = $this->user_session['uid'];
				$w['cart_cookie'] = $cookie;
				$w['_logic'] = 'or';
				$where['_complex'] = $w;
			}elseif($cookie){
				$where['cart_cookie'] = $cookie;
			}elseif($uid){
				$where['uid'] = $uid;
			}
			$cart_list = $actModel->field('productId as product_id,count')->where($where)->select();
			if(!empty($cart_list)){
				foreach($act_goods_list as $key => $val){
					$act_goods_list[$key]['is_added'] = 0;
					foreach($cart_list as $k => $v){
						if($val['goods_id']==$v['product_id']){
							$act_goods_list[$key]['is_added'] = 1;
							$have_num += $v['count'];
						}
					}
				}
			}
			
		}
		$return['act_list'] = $act_goods_list;
		$return['have_num'] = $have_num;
		return $return;
	}

	public function get_periodic_day(){
		$goods_id = $_POST['goods_id'];
		$appoint_day = $_POST['appoint_day'];
		$model = D('Mall_periodic_purchase');
		$date_list = $model->create_preriodic_date($model->get_goods_one($goods_id),time(),$appoint_day);
		echo '预计最早配送时间：'.date('Y-m-d',$date_list[0]);
	}

	//获取购买当前商品的用户
	//@param int $goods_type 1-拼团；2-砍价
	public function get_more_buy(){
		$goods_id=I('goods_id','','intval');
		$goods_type=I('goods_type',0,'intval');
		if($goods_type==1){
			$list=D('Mall_group_act')->get_act_user($goods_id,10,0);
		}elseif($goods_type==2){
			$list=D('Mall_bargain_act')->get_act_user($goods_id,10,0);
		}else{
			$list=D('Shop_order')->get_buy_user($goods_id,10,0);
		}
		foreach($list as $key=>$value){
			$list[$key]['avatar'] = replace_file_domain($value['avatar']);
		}
		echo json_encode($list);
	}

	//ajax判断是否可售
	public function store()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$sort_id = isset($_GET['sort_id']) ? intval($_GET['sort_id']) : 0;
		$where = array('store_id' => $store_id);
		$now_store = D('Merchant_store')->field(true)->where($where)->find();
		//资质认证
		if ($this->config['store_shop_auth'] == 1 && $now_store['auth'] < 3) {
			$this->error_tips('您查看的'.$this->config['mall_alias_name'].'没有通过资质审核！');
			exit;
		}
		$now_shop = D('Merchant_store_shop')->field(true)->where($where)->find();
		if (empty($now_shop) || empty($now_store)) {
			$this->error_tips('店铺信息错误');
		}
		if($now_store['mall_on_sale']==2){
			$this->error_tips('店铺已被下架，如有疑问，请联系系统管理员！');
		}
		
		//如果没有直接到分类页，则跳转到新版
		if (!$sort_id && C('config.show_mall_goods_score_discount') != 1) {
			redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/mall/store_home/index?store_id=' . $store_id);
		}
		
		$otherwc = isset($_GET['otherwc']) ? intval($_GET['otherwc']) : 0;
		if($otherwc){
		    $_SESSION['session_store_id'] = $store_id;
		}
		/* 自定义首页开始 */
		if(empty($_GET['show_own'])){
			$database_diypage = M('Merchant_store_diypage');
			$condition_diypage = array('store_id'=>$now_store['store_id'],'is_home'=>'1','is_remove'=>'0');
			$home_diypage = $database_diypage->field('page_id')->where($condition_diypage)->find();
			if($home_diypage){
				redirect(U('Diypage/page',array('page_id'=>$home_diypage['page_id'])));
			}
		}
		/* 自定义首页结束 */
		
		
		if (!empty($now_shop['background'])) {
			$image_tmp = explode(',', $now_shop['background']);
			$now_shop['background'] = file_domain() . '/upload/background/' . $image_tmp[0] . '/' . $image_tmp['1'];
		}
		
// 		$discounts = D('Shop_discount')->get_discount_byids(array($store_id));
		$discounts = D('Shop_discount')->getDiscounts($now_store['mer_id'], $store_id,2);
		$row = array_merge($now_store, $now_shop);
		
		$store = array();
		$store_image_class = new store_image();
		$images = $store_image_class->get_allImage_by_path($row['pic_info']);
	
		$store['id'] = $row['store_id'];
		
		$store['phone'] = $row['phone'];
		$store['background'] = $row['background'];
		$store['long'] = $row['long'];
		$store['lat'] = $row['lat'];
		$store['store_theme'] = $row['store_theme'];
		$store['adress'] = $row['adress'];
		$store['is_close'] = 1;
		$now_time = date('H:i:s');
		if ($row['open_1'] == '00:00:00' && $row['close_1'] == '00:00:00') {
			$store['time'] = '24小时营业';
			$store['is_close'] = 0;
		} else {
			$store['time'] = substr($row['open_1'], 0, -3) . '~' . substr($row['close_1'], 0, -3);
			if ($row['open_1'] < $now_time && $now_time < $row['close_1']) {
				$store['is_close'] = 0;
			}
			if ($row['open_2'] != '00:00:00' || $row['close_2'] != '00:00:00') {
				$store['time'] .= ',' . substr($row['open_2'], 0, -3) . '~' . substr($row['close_2'], 0, -3);
				if ($row['open_2'] < $now_time && $now_time < $row['close_2']) {
					$store['is_close'] = 0;
				}
			}
			if ($row['open_3'] != '00:00:00' || $row['close_3'] != '00:00:00') {
				$store['time'] .= ',' . substr($row['open_3'], 0, -3) . '~' . substr($row['close_3'], 0, -3);
				if ($row['open_3'] < $now_time && $now_time < $row['close_3']) {
					$store['is_close'] = 0;
				}
			}
		}
		
		$store['name'] = $row['name'];
		$store['store_notice'] = $row['mall_notice'];
		$store['txt_info'] = $row['txt_info'];
		$store['image'] = isset($images[0]) ? $images[0] : '';
		if($row['logo']){
			$store['image'] = replace_file_domain($row['logo']);
		}
		
		$store['star'] = round($row['score_mean'],1);
		$store['month_sale_count'] = $row['sale_count'];
		$store['delivery'] = $row['deliver_type'] == 2 ? false : true;//是否支持配送
		$store['delivery_time'] = $row['send_time'];//配送时长
		$store['delivery_price'] = floatval($row['basic_price']);//起送价
		
		$is_have_two_time = 0;//是否是第二时段的配送显示
		
		if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
			if ($this->config['delivery_time']) {
				$delivery_times = explode('-', $this->config['delivery_time']);
				$start_time = $delivery_times[0] . ':00';
				$stop_time = $delivery_times[1] . ':00';
				if (!($start_time == $stop_time && $start_time == '00:00:00')) {
					if ($this->config['delivery_time2']) {
						$delivery_times2 = explode('-', $this->config['delivery_time2']);
						$start_time2 = $delivery_times2[0] . ':00';
						$stop_time2 = $delivery_times2[1] . ':00';
						if (!($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
							$is_have_two_time = 1;
						}
					}
				}
			}
			
			if ($is_have_two_time) {
				if ($now_time <= $stop_time || $now_time > $stop_time2) {
					$is_have_two_time = 0;
				} 
			}
			
			if ($row['s_is_open_own']) {
				if ($is_have_two_time) {
					$store['delivery_money'] = $row['s_free_type2'] == 0 ? 0 : $row['s_delivery_fee2'];
				} else {
					$store['delivery_money'] = $row['s_free_type'] == 0 ? 0 : $row['s_delivery_fee'];
				}
			} else {
				$store['delivery_money'] = $is_have_two_time ? C('config.delivery_fee2') : C('config.delivery_fee');
			}
		} else {
			if (!($row['delivertime_start'] == $row['delivertime_stop'] && $row['delivertime_start'] == '00:00:00')) {
				if (!($row['delivertime_start2'] == $row['delivertime_stop2'] && $row['delivertime_start2'] == '00:00:00')) {
					$is_have_two_time = 1;
				}
			}
			
			if ($is_have_two_time) {
				if ($now_time <= $row['delivertime_stop'] || $now_time > $row['delivertime_stop2']) {
					$is_have_two_time = 0;
				} 
			}
			
			$store['delivery_money'] = $is_have_two_time ? $row['delivery_fee2'] : $row['delivery_fee'];
		}
		$store['delivery_money'] = floatval($store['delivery_money']);
		
		$store['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送
		if (in_array($row['deliver_type'], array(2, 3, 4))) {
			$store['pick'] = 1;//是否支持自提
		} else {
			$store['pick'] = 0;//是否支持自提
		}
		$store['pack_alias'] = $row['pack_alias'];//打包费别名
		$store['freight_alias'] = $row['freight_alias'];//运费别名
		$store['coupon_list'] = array();
		if ($row['is_invoice']) {
			$store['coupon_list']['invoice'] = floatval($row['invoice_price']);
		}
		if ($row['mall_discount'] != 0 && $row['mall_discount'] != 100) {
			$store['coupon_list']['discount'] = $row['mall_discount']/10;
		}
		$system_delivery = array();

		$sys_newuser = '';
		$sys_minus  = '';
		$sys_delivery = '';
		if (isset($discounts[0]) && $discounts[0]) {
			foreach ($discounts[0] as $row_d) {
				if ($row_d['type'] == 0) {//新单
					$store['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
					$sys_newuser .= '满' . floatval($row_d['full_money']) . C('config.Currency_txt').'减' . floatval($row_d['reduce_money']) . C('config.Currency_txt').',';
				} elseif ($row_d['type'] == 1) {//满减
					$store['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
					$sys_minus  .= '满' . floatval($row_d['full_money']) . C('config.Currency_txt').'减' . floatval($row_d['reduce_money']) . C('config.Currency_txt').',';
				} elseif ($row_d['type'] == 2) {//配送
					$system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
					$sys_delivery .= '满' . floatval($row_d['full_money']) . C('config.Currency_txt').'减' . floatval($row_d['reduce_money']) . C('config.Currency_txt').',';
				}
			}
		}
		
		$mer_newuser = '';
		$mer_minus  = '';
		$mer_delivery = '';
		if (isset($discounts[$store_id]) && $discounts[$store_id]) {
			foreach ($discounts[$store_id] as $row_m) {
				if ($row_m['type'] == 0) {
					$store['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
					$mer_newuser .= '满' . floatval($row_m['full_money']) . C('config.Currency_txt').'减' . floatval($row_m['reduce_money']) . C('config.Currency_txt').',';
				} elseif ($row_m['type'] == 1) {
					$store['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
					$mer_minus .= '满' . floatval($row_m['full_money']) . C('config.Currency_txt').'减' . floatval($row_m['reduce_money']) . C('config.Currency_txt').',';
				}
			}
		}
		if ($store['delivery']) {
			if ($store['delivery_system']) {
				$system_delivery && $store['coupon_list']['delivery'] = $system_delivery;
			} else {
				$sys_delivery = '';
				if ($is_have_two_time) {
					if ($row['reach_delivery_fee_type2'] == 0) {
						if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
							$store['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee2']));
							$mer_delivery .= '满' . floatval($row['basic_price']) . C('config.Currency_txt').'减' . floatval($row['delivery_fee2']) . C('config.Currency_txt').',';
						}
					} elseif ($row['reach_delivery_fee_type'] == 1) {
						//$store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
					} elseif ($row['delivery_fee2']) {
						 $store['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value2']), 'minus' => floatval($row['delivery_fee2']));
						 $mer_delivery .= '满' . floatval($row['no_delivery_fee_value2']) . C('config.Currency_txt').'减' . floatval($row['delivery_fee2']) . C('config.Currency_txt').',';
					}
				} else {
					if ($row['reach_delivery_fee_type'] == 0) {
						if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
							$store['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
							$mer_delivery .= '满' . floatval($row['basic_price']) . C('config.Currency_txt').'减' . floatval($row['delivery_fee']) . C('config.Currency_txt').',';
						}
					} elseif ($row['reach_delivery_fee_type'] == 1) {
						//$store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
					} elseif ($row['delivery_fee']) {
						 $store['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
						 $mer_delivery .= '满' . floatval($row['no_delivery_fee_value']) . C('config.Currency_txt').'减' . floatval($row['delivery_fee']) . C('config.Currency_txt').',';
					}
				}
			}
		}
		$store['txt_discount'] = array('sys_newuser' => rtrim($sys_newuser, ','), 'sys_minus' => rtrim($sys_minus, ','), 'sys_delivery' => rtrim($sys_delivery, ','), 'mer_newuser' => rtrim($mer_newuser, ','), 'mer_minus' => rtrim($mer_minus, ','), 'mer_delivery' => rtrim($mer_delivery, ','));
		
        $nowSort = array('sort_id' => 0, 'sort_name' => '分类');
        $s_list = D('Shop_goods_sort')->getAllChilds($store_id);
        foreach ($s_list as $value) {
            if ($value['sort_id'] == $sort_id) {
                $nowSort = array('sort_id' => $sort_id, 'sort_name' => $value['sort_name']);
            }
        }
		//获取优惠券	
        $coupon_list=$this->get_store_coupon_list($store['id']);
		$this->assign('coupon_list',$coupon_list);
		//商城商品数量
		$goods_count=D('Shop_goods')->where(array('store_id' => $store_id, 'status' => 1,'cat_fid'=>array('gt',0)))->count();
        $this->assign('now_sort', $nowSort);
        $this->assign('sort_list', $s_list);

		// 查看用户是否收藏
		$store['is_collect'] = false;
		if ($this->user_session['uid']) {
			$store['collect_info'] = D('Merchant_user_relation')->get_mall_collect($this->user_session['uid'],$store_id);
			if ($store['collect_info']) {
				$store['is_collect'] = true;
			}
		}

        $this->assign(array('store' => $store, 'goods_count' => $goods_count));
        $this->display();
	}

	//获取店铺优惠券
	protected function get_store_coupon_list($store_id){
		//优惠券
        $where_coupon=array();
		$where_coupon['is_shop'] = array('neq',2);//仅店内
		$where_coupon['is_receive_full_reduction'] = 0;//
		$where_coupon['_string'].=" (store_id ='' OR store_id LIKE '%".intval($store_id)."%')  ";
		
		$coupon_list = D('System_coupon')->get_sys_mer_coupon_by_type('mall',array('mer_condition'=>$where_coupon),'wap',$store_id);
		$user_id=$_SESSION['user']['uid'];
		if($user_id>0){
			$system_hadpull = M('System_coupon_hadpull');
			$merchant_hadpull = M('card_new_coupon_hadpull');
			foreach ($coupon_list as $k => $v) {
				if($v['status']==4){
					unset($coupon_list[$k]);
					continue;
				}
				$where=array();
				$where['coupon_id']=$v['coupon_id'];
				$where['uid']=$user_id;
				if($v['is_system']==1){	//平台券
					$receive_num=$system_hadpull->where($where)->count(1);
				}else{
					$receive_num=$merchant_hadpull->where($where)->count(1);
				}				
				if($receive_num==0){
					if($v['status']==3 || $v['had_pull']==$v['num']){
						$v['receive_status']=2;	//已抢完
					}else{
						$v['receive_status']=0;	//未领取
					}
				}else{
					if($receive_num>=$v['limit']){
						$v['receive_status']=1;	//已领取
					}elseif($v['status']==3 || $v['had_pull']==$v['num']){
						$v['receive_status']=1;	//已领取
					}	
				}
				$coupon_list[$k]=$v;
			}
		}else{
            foreach ($coupon_list as $k => $v) {
                if ($v['status'] == 4) {
                    unset($coupon_list[$k]);
                    continue;
                }
            }
        }
		return $coupon_list;
	}
	
	
	public function ajax_goods()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$sort_id = isset($_POST['sort_id']) ? intval($_POST['sort_id']) : 0;
		$sort = isset($_POST['sort']) ? intval($_POST['sort']) : 1;
		$sort_type = isset($_POST['sort_type']) ? intval($_POST['sort_type']) : 1;
		$store_sort = $this->get_store_show_sort($store_id);
		if(empty($sort_id)&&$store_id){
			$sort_id = array('in',$store_sort);
		}
		$return = D('Shop_goods')->get_list_by_condition(array('store_id' => $store_id, 'sort_id' => $sort_id), $sort, $sort_type,2);
		$goods_list=$return['goods_list'];

		if(C('config.open_kefu_url') && C('config.kefu_mer_id')>0){
			$store_arr=M('Merchant_store')->where(array('mer_id'=>C('config.kefu_mer_id')))->getField('store_id',true);
		}
        $goodsMod = new Shop_goodsModel();
		foreach ($goods_list as $k => $v) {
			//自营商品
			if(is_array($store_arr) && in_array($v['store_id'], $store_arr)){
				$v['is_self']=1;
			}else{
				$v['is_self']=0;
			}
			if($v['seckill_price']<=0){
				$v['act_info']=D('Shop_goods')->get_good_act_info($v['goods_id']);
				$v['url'].='&group_id='.$v['act_info']['group_id'].'&bar_id='.$v['act_info']['bar_id'];
			}


            $score = $goodsMod->getScoreMaxByGoodsId($v['goods_id']);
            if ($score['is_show']) {
                $score['reduce_money'] = $score['is_percent'] ? getFormatNumber($v['price'] * $score['score_max_percent']) : getFormatNumber($score['score_max'] / C('config.user_score_use_percent'));
                $v['price_type'] = 2;
                $v['account_money'] = $v['price'] - $score['reduce_money'] > 0 ? getFormatNumber($v['price'] - $score['reduce_money']) : 0;
                $v['account_score'] = intval($score['reduce_money'] * C('config.user_score_use_percent'));
            }

            $goods_list[$k]=$v;
		}
		$return['goods_list']=$goods_list;
		exit(json_encode($return));
		if (empty($goods)) {
			exit(json_encode(array('goods_list' => null, 'count' => 0)));
		} else {
			exit(json_encode(array('goods_list' => $goods, 'count' => count($goods))));
		}
	}
	
	public function get_store_show_sort($store_id=0){
		$model = M('Shop_goods_sort');
		if($store_id){
			$where_string = '`store_id`='.$store_id.' and ((`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\'))';
		}else{
			$where_string = '(`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\')';
		}
		$where['_string'] = $where_string;
		$data = $model->FIELD(`sort_id`)->where($where)->select();
		$sort = array();
		foreach($data as $v){
			$sort[] = $v['sort_id'];
		}
		return $sort;
	}

	
	public function cart()
	{
// 		$carts = $_COOKIE['mall_goods_cart'];
// 		$carts = json_decode($carts, true);
		
		$carts = array();
//		for ($i = 0; $i < 40; $i++) {
//		    $tmpCookie = cookie('mall_goods_cart_' . $i);
//		    if (!empty($tmpCookie)) {
//		        $tmpArr = json_decode($tmpCookie, true);
//		        if (empty($tmpArr)) {
//		            $tmpArr = array();
//		        }
//		        $carts = array_merge($carts, $tmpArr);
//		    } else {
//		        break;
//		    }
//		}
        if($this->user_session['uid']){
            $where['c.uid'] = $this->user_session['uid'];
            $where['c.cart_cookie'] =cookie('cart_cookie');
            $where['_logic'] = 'or';
            $condition['_complex'] = $where;
        }else{
            $condition['c.cart_cookie'] = cookie('cart_cookie');
        }
        $carts = M('Mall_cart')->alias('c')->join('Left join '.C('DB_PREFIX').'shop_goods as g on g.goods_id = c.productId')->field('c.*,g.status,g.price_type,g.account_score,g.account_money')->where($condition)->order('`id` ASC')->select();
		foreach($carts as $key => $cart){
            $carts[$key]['store_id'] = $cart['shop_id'];
		    if($carts[$key]['productParam']!="") $carts[$key]['productParam'] = unserialize($carts[$key]['productParam']);
        }
		$goods_store = array();
		$goods_ids = array();
		$cart_list = array();
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : $_SESSION['session_store_id'];
		foreach ($carts as $cart) {
			$goods_store[$cart['productId']] = $cart['store_id'];
			$goods_ids[] = $cart['productId'];
		}

		if ($goods_ids) {
			$store_list = array();
			$s_list = D('Merchant_store')->field('name, store_id')->where(array('store_id' => array('in', $goods_store)))->select();
			foreach ($s_list as $s) {
			    if ($store_id && $store_id != $s['store_id']) continue;
				$store_list[$s['store_id']] = $s;
			}
			$goods_list = array();
			$g_list = D('Shop_goods')->field(true)->where(array('goods_id' => array('in', $goods_ids), 'status' => 1))->select();
			$goods_image_class = new goods_image();
			$goodsMod = new Shop_goodsModel();
			foreach ($g_list as $row) {
			    if ($store_id && $store_id != $row['store_id']) continue;

                $row['is_begin_seckill_price'] = false;
                $begin_limit_info=D('Time_limited_discount_goods')->get_in_twelhour_progress_record($row['goods_id']);//是否有即将开始的限时优惠活动
                if(!empty($begin_limit_info)){
                    $row['is_begin_seckill_price'] = true;
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
				if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0) {
					$row['price'] = floatval($row['seckill_price']);
					$row['is_seckill_price'] = true;
				} else {
					$row['price'] = floatval($row['price']);
				}
			
				$row['old_price'] = floatval($row['old_price']);
				$row['seckill_price'] = floatval($row['seckill_price']);
			
				$row['url'] = U('Mall/detail', array('goods_id' => $row['goods_id']), true, false, true);
				$tmp_pic_arr = explode(';', $row['image']);
				foreach ($tmp_pic_arr as $key => $value) {
                    if('http' === substr($value,0,4)){
						$temp_image = $value;
					}else{
                        $temp_image = $goods_image_class->get_image_by_path($value);
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

                $score = $goodsMod->getScoreMaxByGoodsId($row['goods_id']);
                if ($score['is_show']) {
                    $score['reduce_money'] = $score['is_percent'] ? getFormatNumber($row['price'] * $score['score_max_percent']) : getFormatNumber($score['score_max'] / C('config.user_score_use_percent'));
                    $row['price_type'] = 2;
                    $row['account_money'] = $row['price'] - $score['reduce_money'] > 0 ? getFormatNumber($row['price'] - $score['reduce_money']) : 0;
                    $row['account_score'] = intval($score['reduce_money'] * C('config.user_score_use_percent'));
                }

				$goods_list[$row['goods_id']] = $row;
			}
		}

		$pack_list = D('Mall_goods_pack')->deal_cart_list($carts);//一口价活动列表
		foreach ($carts as $vo) {
		    if ($store_id && $store_id != $vo['store_id']) continue;
			$index_key = 's_' . $vo['store_id'] . '_g_' . $vo['productId'];
			$name = $pre = '';
			if ($vo['productParam']) {
				foreach ($vo['productParam'] as $param) {
					if ($param['type'] == 'spec') {
						$index_key .= '_s_' . $param['id'];
						$name .= $pre . $param['name'];
						$pre = ','; 
					} else {
						foreach ($param['data'] as $d) {
							$index_key .= '_v_' . $d['id'];
							$name .= $pre . $d['name'];
							$pre = ','; 
						}
					}
				}
			}
			
			$goods_list[$vo['productId']]['index_key'] = $index_key;
			$goods_list[$vo['productId']]['mall_cart_id'] = $vo['id'];
// 			$goods_list[$vo['productId']]['price'] = $vo['productPrice'];
			$goods_list[$vo['productId']]['num'] = $vo['count'];
			if ($name) {
				$goods_list[$vo['productId']]['name'] = $vo['productName'] . '(' . $name . ')';
			}
			$goods_list[$vo['productId']]['price'] = $vo['productPrice'];

            $score = $goodsMod->getScoreMaxByGoodsId($vo['productId']);
            if ($score['is_show']) {
                $score['reduce_money'] = $score['is_percent'] ? getFormatNumber($vo['productPrice'] * $score['score_max_percent']) : getFormatNumber($score['score_max'] / C('config.user_score_use_percent'));
                $goods_list[$vo['productId']]['price_type'] = 2;
                $goods_list[$vo['productId']]['account_money'] = $vo['productPrice'] - $score['reduce_money'] > 0 ? getFormatNumber($vo['productPrice'] - $score['reduce_money']) : 0;
                $goods_list[$vo['productId']]['account_score'] = intval($score['reduce_money'] * C('config.user_score_use_percent'));
            }
			$total_price = $vo['count'] * $goods_list[$vo['productId']]['price'];
			if (isset($store_list[$vo['store_id']]['goods_list'])) {
				$store_list[$vo['store_id']]['total_price'] += $total_price;
				$store_list[$vo['store_id']]['goods_list'][] = $goods_list[$vo['productId']];
			} else {
				$store_list[$vo['store_id']]['total_price'] = $total_price;
				$store_list[$vo['store_id']]['goods_list'] = array($goods_list[$vo['productId']]);
			}
		}
		$store_list = $this->deal_store_pack_list($store_list,$pack_list);
		//dump($store_list);exit;
		$this->assign('product_list', $store_list);
		$this->assign('store_id', $store_id);
		$this->check_footer_position();
		$this->display();
	}
	
	//【购物车页面】猜你喜欢商品列表:销量前100商品，随机20个
	public function guess_you_like(){

		$model = D('Shop_goods');
		
		$where = array('g.status' => 1);
		//获取商城商品
		$cat_arr = D('Goods_category')->get_category_array();
        $where['g.cat_fid']=array('in',$cat_arr);
		$where['s.status']=1;  //店铺状态正常
		$where['m.status'] = 1;
		
		if($_SESSION['session_store_id']){
            $where['s.store_id'] = $_SESSION['session_store_id'];
        }else{
			$where['s.mall_on_sale'] = 1;
		}
		
		$order = 'g.sell_count desc,g.goods_id desc';
        
		$select_list = M('Shop_goods')->alias('g')->JOIN('LEFT JOIN '.C('DB_PREFIX').'merchant_store as s on g.store_id=s.store_id LEFT JOIN '.C('DB_PREFIX').'merchant as m on m.mer_id=s.mer_id')->field('g.goods_id')->where($where)->order($order)->limit(100)->select();
		
		$select_list = array_column($select_list,'goods_id');
		$key_list = count($select_list) < 20 ? array_rand($select_list, count($select_list)) : array_rand($select_list, 20); 
		$select_id = array();
		foreach($key_list as $val){
			$select_id[] = $select_list[$val];
		}
		$w = array('goods_id'=>array('in',$select_id));
		$good_list = $model->where($w)->field(true)->select();
		//设置商品区域展示
        $addrs=$this->getUserMsg($this->user_session['uid']);
        if(empty($addrs)){
            $arr['province_id']='';
            $arr['city_id']=C('config.now_city');
            $arr['area_id']='';
        }else{
            $arr['province_id']=$addrs['province_id'];
            $arr['city_id']=$addrs['city_id'];
            $arr['area_id']=$addrs['area_id'];
        }
        $area_id=D('Shop_goods')->getAreaId($arr);
        if(!empty($area_id)){
            foreach ($good_list as $k => $v){
                if($v['use_area']==1){
                    $goods_list = D('Shop_goods_area')->where(array('did'=>$v['goods_id'],'store_id'=>array('exp','IS NOT NULL')))->getField('aid',true);
                    if(empty($goods_list)){
                        continue;
                    }                    //取交集,有则过滤商品
                    $arrs=array_intersect($area_id, $goods_list);
                    if(empty($arrs)){
                        unset($good_list[$k]);
                        continue;
                    }
                }
            }
            $good_list=array_values($good_list);
        }

		if($good_list){
			$goods_image_class = new goods_image();
			foreach ($good_list as $value) {
				// 新增限时显示
				if (! ($value['show_start_time'] == $value['show_end_time'] || ($value['show_start_time'] == '00:00:00' && $value['show_end_time'] == '23:59:00'))) {
					$st = strtotime(date('Y-m-d') . ' ' . $value['show_start_time']);
					$et = strtotime(date('Y-m-d') . ' ' . $value['show_end_time']);
					if (! ($st <= $timeNow && $timeNow <= $et)) {
						continue;
					}
				}
				$tmp_pic_arr = explode(';', $value['image']);
				foreach ($tmp_pic_arr as $k => $v) {
					$value['pic_arr'][$k]['title'] = $v;
					$value['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
				}
				$value['image_url'] = $value['pic_arr'][0]['url']['image'];

				$url = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $value["goods_id"];
			   
				$value['link_url'] = $url;

				/*新增预售显示*/
                $book_act = D('Mall_book_goods')->get_book_record($value['goods_id']);
                if($book_act){
                    $value['book_record']=1;
                }else{
                    $value['book_record']=0;
                }
				$goodsList[] = $value;
			}
		}
		header("content:application/json;");
		echo json_encode($goodsList);
	}

	public function getUserMsg($uid){
        $user = D('User')->field(true)->where(array('uid'=>$uid))->find();
        return $user;
    }
	protected function deal_store_pack_list($store_list,$pack_list){
		if($pack_list){
			foreach($store_list as $key => $val){
				$act_list = array();
				foreach($pack_list as $k => $v){
					if($val['store_id']==$v['store_id']){
						$act_list[] = $v;
					}
				}
				$store_list[$key]['pack_act_list'] = $act_list;
			}
		}
		//dump($store_list);
		return $store_list;
	}
	
	public function reply()
	{
		$goods_id = isset($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;
		$store_shop = array();
		if ($goods = D('Shop_goods')->field(true)->where(array('goods_id' => $goods_id))->find()) {
			$store = D('Merchant_store')->field(true)->where(array('store_id' => $goods['store_id']))->find();
			$store_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $goods['store_id']))->find();
			$store = array_merge($store, $store_shop);
			$this->assign('store', $store);
		} else {
			$this->error_tips('商品不存在！');
		}
		$new_comment=I('new_comment',0,'intval');
		if($new_comment==1){
			$this->display('comment_list');
		}else{
			$this->display();
		}
	}
	
	public function search()
	{
		$keyword = isset($_GET['key']) ? trim(htmlspecialchars($_GET['key'])) : '';
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$this->assign('store_id', $store_id);
		$this->assign('keyword', $keyword);
        $search_hot_list = M('Search_hot_mall')->field(true)->order('`hottest` DESC,`sort` DESC,`id` ASC')->select();
        $this->assign('search_hot_list',$search_hot_list);
		$this->display();
	}
	
	
	public function addressinfo()
	{
		$now_store = D('Merchant_store')->get_store_by_storeId($_GET['store_id']);
		if(empty($now_store)){
			$this->error_tips('该店铺不存在！');
		}
		$this->assign('now_store',$now_store);

		$this->display();
	}
	
	public function confirm_order()
	{
		//delivery_type 0:平台配送，1：商家配送，2：自提，3:平台配送或自提，4：商家配送或自提,5:邮递，6：邮递或自提
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$address_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']) : cookie('userLocationId');
		$show_user_adress = D('User_adress')->get_one_adress($this->user_session['uid'], intval($address_id));
        
		//dump($show_user_adress);exit;
		/*新增重新下单功能 2018/11/23 取消订单后，再来一单*/
		if($order_id>0){
			$where=array();
			$where['order_id']=$order_id;
			$uid=$this->user_session['uid'];
			$where['uid']=$uid;
			$order = D('Shop_order')->get_order_detail($where);
			$goodsData=$order['info'];
            $return=D('Shop_goods')->checkCart($store_id,$uid,$goodsData,0,$show_user_adress['adress_id'],false,2,$order['group_id'],$order['bar_id'],$order['team_id']);
//			var_dump($return);
			$this->assign('order_id', $order_id);
		}
		elseif(isset($_GET['params']) && $_GET['params']=='onlybuy'){
            $return = $this->check_cart($show_user_adress['adress_id'],'onlybuy');
            $this->assign('onlybuy', $_GET['params']);

        }else{
            $return = $this->check_cart($show_user_adress['adress_id']);
        }
		
		//必传手机号
		$now_user = D('User')->get_user($this->user_session['uid']);
		if(empty($now_user) || $now_user['status'] == '4'){
			redirect(U('Login/logout', ['referer' => urlencode($_SERVER['REQUEST_URI'])]));
        }
        if(empty($now_user['phone'])){
            redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/my/newnumber');
        }
	
		if ($return['error_code'])
		    $this->error_tips($return['msg']);
		//dump();exit;
		$score_less = 1;
		if($this->user_session['score_count']>=$return['total_score'] || $this->user_session['score_count'] < 0){
			$score_less = 0;
		}
		$this->assign('score_less',$score_less);
		
		/*增加数据展示优化逻辑start*/
		if(C('config.vip_level_discount_sequence')==1) { //满减后计算
            if ($return['sorts_discount_type'] == 0) {
                $return['this_discount_price'] = round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2) + ($return['price'] - $return['vip_discount_money']);
            } else {
                if ($return['optimalDiscountMoney'] > 0) {
                    $return['this_discount_price'] = round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2) + ($return['price'] - $return['optimalDiscountMoney']);
                }
            }
        }else{
            $return['this_discount_price'] = round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2) + ($return['price'] - $return['vip_discount_money']);
        }
		/*增加数据展示优化逻辑end*/
		
		//判断是否有限时价商品，并且比现价还便宜3折（含）以上，前端弹出验证码防止机器作弊
		$return['show_buy_captcha'] = false;
		if($this->config['dingxiang_captcha_appid']){
			foreach($return['goods'] as $value){
				if($value['is_seckill_price'] && $value['discount_price'] <= $value['price']*0.3){
					$return['show_buy_captcha'] = true;
					break;
				}
			}
		}

		//展示商品价格和积分最大抵扣
        $goodsMod = new Shop_goodsModel();
        foreach ($return['goods'] as $key => $value) {
            $score = $goodsMod->getScoreMaxByGoodsId($value['goods_id']);
            if ($score['is_show']) {
                $score['reduce_money'] = $score['is_percent'] ? getFormatNumber($value['price'] * $score['score_max_percent']) : getFormatNumber($score['score_max'] / C('config.user_score_use_percent'));
                $return['goods'][$key]['price_type'] = 2;
                $return['goods'][$key]['price'] = $value['price'] - $score['reduce_money'] > 0 ? getFormatNumber($value['price'] - $score['reduce_money']) : 0;
                $return['goods'][$key]['account_score'] = intval($score['reduce_money'] * C('config.user_score_use_percent'));
                $score['score_max'] = intval($score['reduce_money'] * C('config.user_score_use_percent'));
                if ($return['price'] > $score['reduce_money'] * $value['num']) {
                    $return['price'] -= $score['reduce_money'] * $value['num'];
                } else {
                    $return['price'] = 0;
                }
                if ($return['vip_discount_money'] > $score['reduce_money'] * $value['num']){
                    $return['vip_discount_money'] -= $score['reduce_money'] * $value['num'];
                }else {
                    $return['vip_discount_money'] = 0;
                }
                //if ($return['total_score'] <= 0) {
                    $return['total_score'] += $score['score_max'] * $value['num'];
                //}
            }
        }

        //dump($return);exit;

		$basic_price = $return['price'];//商品实际总价
        if ($this->config['open_extra_price'] > 0) {
            $basic_price += $return['extra_price'];
        }
        $return['basic_price']=$basic_price;
        $return['price'] = round($return['vip_discount_money'] - round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2), 2);//实际要支付商品的总价格
		$return['price'] = $return['price']>0?$return['price']:0;
        $hasReduceMoney = 0;
        if(C('config.vip_level_discount_sequence')==1) { //满减后计算
            if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
                if ($return['sorts_discount_type'] == 0) { //折上折
                    $hasReduceMoney = $return['price'] - round($return['price'] * ($return['vip_discount'] * 0.01), 2);
                    $return['discount_list']['vip_level'] = array('discount_type' => 6, 'vip_level_discount' => $return['vip_discount'] * 0.1, 'vip_level_reduce_money' => $hasReduceMoney);
                } else { //折扣最优
                    if ($return['optimalDiscountMoney'] > 0) {
                        $totalOptimalMoney = $return['optimalDiscountMoney'];
                        $return['vip_discount_money'] = $totalOptimalMoney;
                        $return['price'] = round($totalOptimalMoney - round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2), 2);//实际要支付商品的总价格
                        $hasReduceMoney = $return['price'] - round($return['price'] * ($return['vip_discount'] * 0.01), 2);
                        $return['discount_list']['vip_level'] = array('discount_type' => 6, 'vip_level_discount' => $return['vip_discount'] * 0.1, 'vip_level_reduce_money' => $hasReduceMoney);
                    }
                }
            }
            $return['price']  = $return['price'] - $hasReduceMoney;
        }else{  //正常计算
            $hasReduceMoney = $return['vip_level_reduce_money'];
            $return['this_discount_price'] = $return['this_discount_price'] - $hasReduceMoney;
            $return['discount_list']['vip_level'] = array('discount_type' => 6, 'vip_level_discount' => $return['vip_discount'] * 0.1, 'vip_level_reduce_money' => $return['vip_level_reduce_money']);
        }
        $return['vipLevelReduceMoney'] =  $hasReduceMoney;
        $return['vip_discount'] =  $return['vip_discount'] * 0.1;
        $return['price'] += $return['packing_charge']; //商品的实际支付总加打包费


		$address_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']) : cookie('userLocationId');
		$userLocationLong = cookie('userLocationLong');
		$userLocationLat = cookie('userLocationLat');
		if (empty($userLocationLong) || empty($userLocationLat)) {
			$userLocationLat = $return['store']['lat'];
			$userLocationLong = $return['store']['long'];
		}

		//平台配送错误信息放在这里
		$plat_deliver_err = array(
				'tips' => '',
				'url' => '',
		);

		$user_address_temp = D('User_adress')->field(true)->where(array('uid' => $this->user_session['uid']))->order('`default` DESC,`adress_id` DESC')->select();
		$return['address_count'] = count($user_address_temp);
		$user_adress = array();
		$distance_array = array();
		$user_address_list = array();
		$is_adress = false;
		if($_GET['adress_id']){
			$user_address_temp = [];
			$user_address_temp[] = D('User_adress')->field(true)->where(array('adress_id' => $_GET['adress_id']))->find();
		}
		//echo $return['store']['delivery_range_type'];exit;
		foreach ($user_address_temp as $address) {
			$address['distance'] = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
			if ($return['store']['delivery_range_type'] == 0) {
				$distance = getDistance($address['latitude'], $address['longitude'], $return['store']['lat'], $return['store']['long']);
				$distance = $distance / 1000;
				if ($return['store']['delivery_radius'] < $distance && $return['delivery_type'] != 5) {
					continue;
				}
			} else {
				if ($return['store']['delivery_range_polygon']) {
					if (!isPtInPoly($address['longitude'], $address['latitude'], $return['store']['delivery_range_polygon']) && $return['delivery_type'] != 5) {
						continue;
					}
				} else {
					continue;
				}
			}
			$is_adress = true;

			if ($address_id == $address['adress_id']) {
				$user_adress = $address;
				break;
			}

			if(empty($address_id) && $address['default']){
				$user_adress = $address;
				break;
			}
			$distance_array[] = $address['distance'];
			$user_address_list[] = $address;
		}

		if($_GET['adress_id']){
			$show_user_adress = D('User_adress')->get_one_adress($this->user_session['uid'], intval($_GET['adress_id']));
		}else{
			if($user_adress['adress_id']){
				$show_user_adress = D('User_adress')->get_one_adress($this->user_session['uid'], intval($user_adress['adress_id']));
			}else{
				$show_user_adress = D('User_adress')->get_one_adress($this->user_session['uid'], intval($user_address_list[0]['adress_id']));
			}
			
		}
		$this->assign('show_user_adress',$show_user_adress);
		if(!$is_adress){
			$plat_deliver_err['tips'] = '抱歉，您所选择的地址不在配送范围内';
			$plat_deliver_err['url'] = U('My/adress',array('buy_type' => 'mall', 'store_id'=>$return['store']['store_id'], 'village_id'=>$return['village_id'], 'mer_id' => $return['store']['mer_id'], 'current_id'=>$user_adress['adress_id'],'params'=>$_GET['params'],'order_id'=>$order_id,'group_id'=>$return['group_id'],'bar_id'=>$return['bar_id'],'team_id'=>$return['team_id']));
		}
		$this->assign('is_adress', $is_adress);
		//DUMP($plat_deliver_err);EXIT;
		if (empty($user_adress) && $distance_array && $user_address_list) {
			array_multisort($distance_array, SORT_ASC, $user_address_list);
			$user_adress = $user_address_list[0];
		}
		if ($user_adress) {
			$province = D('Area')->get_area_by_areaId($user_adress['province'],false);
			$user_adress['province_txt'] = $province['area_name'];

			$city = D('Area')->get_area_by_areaId($user_adress['city'],false);
			$user_adress['city_txt'] = $city['area_name'];

			$area = D('Area')->get_area_by_areaId($user_adress['area'],false);
			$user_adress['area_txt'] = $area['area_name'];
		}
		$user_adress = $show_user_adress;
         /*预售尾款*/
        $return['left_price']=$return['price']-$return['book_record']['bargin_price']-$return['book_record']['discount_price']+$return['delivery_fee'];
        /*预售小计*/
        $return['book_pay_price']=$return['price']-$return['book_record']['discount_price']+$return['delivery_fee'];

        $this->assign($return);
		//计算配送费
		if ($user_adress) {
			$distance = 0;
			if (C('config.is_riding_distance')) {
				import('@.ORG.longlat');
				$longlat_class = new longlat();
				$distance = $longlat_class->getRidingDistance($return['store']['lat'], $return['store']['long'], $user_adress['latitude'], $user_adress['longitude']);
				$distance = '';
				if($distance == -1) $this->error_tips('请求不到您的具体定位，请重试！');

			}
			$distance || $distance = getDistance($return['store']['lat'], $return['store']['long'], $user_adress['latitude'], $user_adress['longitude']);
			//dump($distance);exit;
            if($return['mall_deliver_type']==8 || $return['mall_deliver_type']==0 || $return['mall_deliver_type']==7 || $return['mall_deliver_type']==6){
                $distance = $distance / 1000;
                $pass_distance = $distance > $return['s_basic_distance'] ? floatval($distance - $return['s_basic_distance']) : 0;
                $return['delivery_fee'] = $return['s_delivery_fee']+round($pass_distance * $return['s_per_km_price'], 2);
                $return['delivery_fee_old'] = $return['delivery_fee'];
                if ($return['s_free_type']==0){
                    $return['delivery_fee'] =0;
                    $return['delivery_fee_old'] =0;
                }elseif($return['s_free_type']==2){
                    if($return['s_full_money']<=$return['price']){//达到满免金额，与实际支付价格比较，达到要求免运费
                        $return['delivery_fee'] =0;
                        $return['delivery_fee_old'] =0;
                    }
                }

                $pass_distance = $distance > $return['s_basic_distance2'] ? floatval($distance - $return['s_basic_distance2']) : 0;
                $return['delivery_fee2'] = $return['s_delivery_fee2']+round($pass_distance * $return['s_per_km_price2'], 2);
                $return['delivery_fee2_old'] = $return['delivery_fee2'];
                if ($return['s_free_type2']==0){
                    $return['delivery_fee2'] =0;
                    $return['delivery_fee2_old'] =0;
                }elseif($return['s_free_type2']==2){
                    if($return['s_full_money2']<=$return['price']){
                        $return['delivery_fee2'] =0;
                        $return['delivery_fee2_old'] =0;
                    }
                }


                $pass_distance = $distance > $return['s_basic_distance3'] ? floatval($distance - $return['s_basic_distance3']) : 0;
                $return['delivery_fee3'] = $return['s_delivery_fee3']+round($pass_distance * $return['s_per_km_price3'], 2);
                $return['delivery_fee3_old'] = $return['delivery_fee3'];
                if ($return['s_free_type3']==0){
                    $return['delivery_fee3'] =0;
                    $return['delivery_fee3_old'] =0;
                }elseif($return['s_free_type3']==2){
                    if($return['s_full_money3']<=$return['price']){
                        $return['delivery_fee3'] =0;
                        $return['delivery_fee3_old'] =0;
                    }
                }
            }
            else{
                $distance = $distance / 1000;
                $pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;

                // 配送配费折扣
                $return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);
                $return['delivery_fee_old'] = $return['delivery_fee'];
                $return['delivery_fee'] = $return['delivery_fee'] - $return['delivery_fee_reduce'];
                $return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;

                $pass_distance = $distance > $return['basic_distance2'] ? floatval($distance - $return['basic_distance2']) : 0;
                $return['delivery_fee2'] += round($pass_distance * $return['per_km_price2'], 2);
                $return['delivery_fee2_old'] = $return['delivery_fee2'];
                $return['delivery_fee2'] = $return['delivery_fee2'] - $return['delivery_fee_reduce'];
                $return['delivery_fee2'] = $return['delivery_fee2'] > 0 ? $return['delivery_fee2'] : 0;

                $pass_distance = $distance > $return['basic_distance3'] ? floatval($distance - $return['basic_distance3']) : 0;
                $return['delivery_fee3'] += round($pass_distance * $return['per_km_price3'], 2);
                $return['delivery_fee3_old'] = $return['delivery_fee3'];
                $return['delivery_fee3'] = $return['delivery_fee3'] - $return['delivery_fee_reduce'];
                $return['delivery_fee3'] = $return['delivery_fee3'] > 0 ? $return['delivery_fee3'] : 0;
            }
			switch (intval($this->config['count_freight_charge_method'])) {
				case 0:
					break;
				case 1:
					$return['delivery_fee'] = ceil($return['delivery_fee'] * 10) / 10;
					$return['delivery_fee2'] = ceil($return['delivery_fee2'] * 10) / 10;
					$return['delivery_fee3'] = ceil($return['delivery_fee3'] * 10) / 10;
					$return['delivery_fee_old'] = ceil($return['delivery_fee_old'] * 10) / 10;
					$return['delivery_fee2_old'] = ceil($return['delivery_fee2_old'] * 10) / 10;
					$return['delivery_fee3_old'] = ceil($return['delivery_fee3_old'] * 10) / 10;
					break;
				case 2:
					$return['delivery_fee'] = floor($return['delivery_fee'] * 10) / 10;
					$return['delivery_fee2'] = floor($return['delivery_fee2'] * 10) / 10;
					$return['delivery_fee3'] = floor($return['delivery_fee3'] * 10) / 10;
					$return['delivery_fee_old'] = floor($return['delivery_fee_old'] * 10) / 10;
					$return['delivery_fee2_old'] = floor($return['delivery_fee2_old'] * 10) / 10;
					$return['delivery_fee3_old'] = floor($return['delivery_fee3_old'] * 10) / 10;

					break;
				case 3:
					$return['delivery_fee'] = round($return['delivery_fee'] * 10) / 10;
					$return['delivery_fee2'] = round($return['delivery_fee2'] * 10) / 10;
					$return['delivery_fee3'] = round($return['delivery_fee3'] * 10) / 10;
					$return['delivery_fee_old'] = round($return['delivery_fee_old'] * 10) / 10;
					$return['delivery_fee2_old'] = round($return['delivery_fee2_old'] * 10) / 10;
					$return['delivery_fee3_old'] = round($return['delivery_fee3_old'] * 10) / 10;
					break;
				case 4:
					$return['delivery_fee'] = ceil($return['delivery_fee']);
					$return['delivery_fee2'] = ceil($return['delivery_fee2']);
					$return['delivery_fee3'] = ceil($return['delivery_fee3']);
					$return['delivery_fee_old'] = ceil($return['delivery_fee_old']);
					$return['delivery_fee2_old'] = ceil($return['delivery_fee2_old']);
					$return['delivery_fee3_old'] = ceil($return['delivery_fee3_old']);
					break;
				case 5:
					$return['delivery_fee'] = floor($return['delivery_fee']);
					$return['delivery_fee2'] = floor($return['delivery_fee2']);
					$return['delivery_fee3'] = floor($return['delivery_fee3']);
					$return['delivery_fee_old'] = floor($return['delivery_fee_old']);
					$return['delivery_fee2_old'] = floor($return['delivery_fee2_old']);
					$return['delivery_fee3_old'] = floor($return['delivery_fee3_old']);
					break;
				case 6:
					$return['delivery_fee'] = round($return['delivery_fee']);
					$return['delivery_fee2'] = round($return['delivery_fee2']);
					$return['delivery_fee3'] = round($return['delivery_fee3']);
					$return['delivery_fee_old'] = round($return['delivery_fee_old']);
					$return['delivery_fee2_old'] = round($return['delivery_fee2_old']);
					$return['delivery_fee3_old'] = round($return['delivery_fee3_old']);
					break;
			}
		}
		$return['order_type'] = 2;//商城订单取不同的配置项
		$date_list = D('Shop_goods')->getSelectTime($return);
		//dump($date_list);exit;
		foreach ($date_list as $key => $rowset) {
			$temp = array();
			asort($rowset);
			foreach ($rowset as $rv) {
				$temp[] = $rv;
			}
			if ($key == date('Y-m-d')) {
				$d_list[] = array('ymd' => $key, 'show_date' => '今天', 'date_list' => $temp);
			} else if($key == date('Y-m-d',time()+86400)){
				$d_list[] = array('ymd' => $key, 'show_date' => '明天', 'date_list' => $temp);
			} else if($key == date('Y-m-d',time()+86400*2)){
				$d_list[] = array('ymd' => $key, 'show_date' => '后天', 'date_list' => $temp);
			} else {
				$d_list[] = array('ymd' => $key, 'show_date' => date('m月d日', strtotime($key)), 'date_list' => $temp);
			}
		}
		//dump($d_list);exit;
		if (empty($d_list)) {
			$plat_deliver_err['tips'] = '抱歉，当前暂无可选择的配送时间';
			$plat_deliver_err['url'] ='';

		}

		if($return['vip_discount_money'] < $return['store']['s_basic_price']){
			$e = $return['store']['s_basic_price']-$return['vip_discount_money'];
			$plat_deliver_err['tips'] = '未满足起送价，还差<label style="color:#ff510c">'.$e.'元</label>即可满足起送价哦<label style="color:#ff510c">【去凑单】</label>';
			$plat_deliver_err['url'] =U('home');
		}
		
		//dump($d_list);exit;
		$this->assign('dates', $d_list);
		
		/**
		 * 京东校验
		 */
		if($this->config['jd_access_token']){
			import('ORG.Net.Http');
			$url_addr = 'https://bizapi.jd.com/api/area/getJDAddressFromAddress';
			$url_check = 'https://bizapi.jd.com/api/stock/getNewStockById';
			$redata['address'] = $user_adress['province_txt'].$user_adress['city_txt'].$user_adress['area_txt'].$user_adress["adress"].$user_adress["detail"];
			$redata['token'] = $this->config['jd_access_token'];
			$addr_return = http::curlPost($url_addr,$redata);
			$re_stock['token'] = $redata['token'];
			foreach($return['goods'] as $goods){
				if($goods['jd_sku_id']!=0){
					$re_st[] = array('skuId'=>$goods['jd_sku_id'],'num'=>$goods['num']);
				}
			}
        }
        if(!empty($re_st)){
            $re_stock['skuNums'] = json_encode($re_st);
            //请求四级地址列表
            if($addr_return['result']['countyId']>0){
                $url_town = "https://bizapi.jd.com/api/area/getTown";
                $town_data['token'] = $this->config['jd_access_token'];
                $town_data['id'] = $addr_return['result']['countyId'];
                $town_return = http::curlPost($url_town,$town_data);
                if($town_return['success']==true){
                    if(count($town_return['result'])>0){
                        foreach($town_return['result'] as $key =>$val){
                            $towns[] = array('townName'=>$key,'townId'=>$val);
                        }
                        $this->assign('towns',$towns);
                    }
                }
            }
            $addr_return['result']['townId'] = $addr_return['result']['townId'] ? $addr_return['result']['townId'] : 0;
            $re_stock['area'] = $addr_return['result']['provinceId'].'_'.$addr_return['result']['cityId'].'_'.$addr_return['result']['countyId'].'_'.$addr_return['result']['townId'];
            $this->assign('area_code',$re_stock['area']);
            $this->assign('area_address',$user_adress["adress"].$user_adress["detail"]);
            $check_return = http::curlPost($url_check,$re_stock);
            $stock = json_decode($check_return['result'],true);
            foreach($stock as $st){
                if($st['stockStateId']==36 || $st['stockStateId']==34 || $st['stockStateId']==99){
                    foreach($return['goods'] as $goods){
                        if($goods['jd_sku_id'] == $st['skuId']){
                            $name .= $goods['name'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                        }
                    }
                    $this->assign('error_tips',$name.'当前收货地区暂时无货，请重新选择收货地址！');
                }
            }
            //请求京东运费
            $url_freight = 'https://bizapi.jd.com/api/order/getFreight';
            $re_freight['token'] = $redata['token'];
            $re_freight['sku'] = $re_stock['skuNums'];
            $re_freight['province'] = $addr_return['result']['provinceId'];
            $re_freight['city'] = $addr_return['result']['cityId'];
            $re_freight['county'] = $addr_return['result']['countyId'];
            $re_freight['paymentType'] = 4;
            $return_freight = http::curlPost($url_freight,$re_freight);
            $this->assign('jd_freight',$return_freight['result']['freight']);
            $this->assign('jd_on',1);
            //end
            $return['price'] += $return_freight['result']['freight'];
        }

		$village_id = isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
		$this->assign('village_id', $village_id);
		$this->assign('user_adress', $user_adress);
		
		$pick_addr_id = isset($_GET['pick_addr_id']) ? $_GET['pick_addr_id'] : '';
		$pick_list = D('Pick_address')->get_pick_addr_by_merid($return['mer_id'], true, 0, false,[], 1);
		if (empty($pick_list)) {
		    $lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
		    $nowStore = $return['store'];
		    $distance = getDistance($lng_lat['lat'], $lng_lat['long'], $nowStore['lat'], $nowStore['long']);
		    $areaList = D('Area')->where(array('area_id' => array('in', array($nowStore['province_id'], $nowStore['city_id'], $nowStore['area_id']))))->getField('area_id,area_name');
		    $pick_list[] = array(
		        'name' => $nowStore['adress'] . ' ' . $nowStore['name'],
		        'area_info' => array(
		            'province' => $areaList[$nowStore['province_id']],
		            'city' => $areaList[$nowStore['city_id']],
		            'area' => $areaList[$nowStore['area_id']]
		        ),
		        'pick_addr_id' => 's' . $nowStore['store_id'],
		        'phone' => $nowStore['phone'],
		        'long' => $nowStore['long'],
		        'lat' => $nowStore['lat'],
		        'addr_type' => 1,
		        'distance' => $distance
		    );
		}
		if ($pick_addr_id) {
		    foreach ($pick_list as $k => $v) {
		        if ($v['pick_addr_id'] == $pick_addr_id) {
		            $pick_address = $v;
		            break;
		        }
		    }
		} else {
		    $pick_address = $pick_list[0];
		}

		$pick_address['distance'] = $this->wapFriendRange($pick_address['distance']);

		// 自提点数量
		$pick_address_count = count($pick_list);

		$goods=$return['goods'];

		//计算店铺折扣总额
		$store_discount_price=0;
		foreach($goods as $v){
			$v['discount_price']=$v['discount_price']+round($v['this_goods_vip_reduce']/$v['num'],2);
			if(C('config.vip_level_discount_sequence')==1){	//满减后计算
				if($return['vip_discount']>0 && $return['vip_discount']<100){
					if($return['sorts_discount_type']!=0){	//折扣最优
						if($return['optimalDiscountMoney']>0){
							$v['discount_price']=$v['price'];
						}
					}
				}
			}
			if($v['discount_price']<$v['price']){
				$store_discount_price+=($v['price']-$v['discount_price'])*$v['num'];
			}
		}
		$return['store_discount_price']=round($store_discount_price,2);

		//优化展示顺序
		$return['discount_list']=$this->discount_sort($return['discount_list']);
		//dump($return['price']);exit;
		

		$this->assign('pick_addr_id', $pick_addr_id);
		$this->assign('pick_address', $pick_address);
		$this->assign('pick_address_count', $pick_address_count);
		
		$now_store_category_relation = M('Shop_category_relation')->where(array('store_id'=>$return['store_id']))->find();
		$now_store_category = M((_view('Shop_category')))->where(array('cat_id'=>$now_store_category_relation['cat_id']))->find();
		if($now_store_category['cue_field']){
			$this->assign('cue_field',unserialize($now_store_category['cue_field']));
		}
        // 添加查询 商城店铺 配送类型
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        if ($store_id) {
            $merchant_store_shop_info = M('Merchant_store_shop')->field('deliver_type,mall_deliver_type')->where(array('store_id' => $store_id))->find();
            if ($merchant_store_shop_info) {
            	$merchant_store_shop_info['delivery_type']=D('Merchant_store_shop')->get_mall_deliver_type($merchant_store_shop_info['deliver_type'],$merchant_store_shop_info['mall_deliver_type']);
            	if($return['book_record']){
            	    //预售活动仅支持快递
            		$merchant_store_shop_info['delivery_type'] = 5;
            	}
               /*-----------------再次从预售活动表中确认有没有这个商品--------------*/
                $productCart = $this->getCookieData('onlybuy');
                //预售商品
                $time=time();
                //拼团商品
                $where=array();
                $where['start_time']=array('lt',$time);
                $where['end_time']=array('gt',$time);
                $where['stock']=array('neq',0);
                $where['store_id']=$productCart[0]['store_id'];
                $where['goods_id']=$productCart[0]['productId'];
                $where['is_del']=0;
                $book_info=M('Mall_book_goods')->where($where)->field('id')->find();
                if(isset($book_info)) {    //预售商品
                    //预售活动仅支持快递
                    $merchant_store_shop_info['delivery_type'] = 5;
                }
                /*-----------------结束确认快递--------------*/
                $this->assign('delivery_type', $merchant_store_shop_info['delivery_type']);
            }
        }
		//拼团订单
		$group_id=I('get.group_id','','intval');
		$bar_id=I('get.bar_id','','intval');
		$team_id=I('get.team_id','','intval');
		if($group_id>0){
			//获取该团的参团用户
			if($team_id>0){
				$join_list=D('Mall_group_team')->get_team_user_list($team_id,$return['mer_id']);
			}else{
				$join_list=array();
			}
		}
		//统计优惠总额
		$discount_price=$return['store_discount_price'];
		foreach ($return['discount_list'] as $k => $v) {
			if($k=='delivery'){
				continue;
			}
			if($k=='vip_level'){
				$discount_price+=$v['vip_level_reduce_money'];				
			}else{
				$discount_price+=($v['minus2']??$v['minus']);
			}
		}

		//dump($user_adress);
		$this->assign('plat_deliver_err',json_encode($plat_deliver_err));
//		满减处理//进行马上购买显示满减
		$this->assign('discount_price',$discount_price);
		$this->assign('group_id',$group_id);
		$this->assign('bar_id',$bar_id);
		$this->assign('team_id',$team_id);
		$this->assign('team_id',$team_id);
		$this->display();

	}

	//排序优惠信息
	public function discount_sort($discount_list=null){
		if(empty($discount_list)){
			return array();
		}
		$arr=array();
		$arr[]='newuser';
		$arr[]='minus';
		$arr[]='system_newuser';
		$arr[]='system_minus';
        $arr[] = 'plat_discount';
		if(C('config.vip_level_discount_sequence')==0){
			array_unshift($arr,'vip_level');
		}else{
			$arr[]='vip_level';
		}
		$arr[]='delivery';
		$list=array();
		foreach ($discount_list as $k => $v) {
			$i=array_search($k,$arr);
			if(is_numeric($i)){
				$list[$i]=$v;
			}
		}
		ksort($list);
		$list2=array();
		foreach ($list as $k => $v) {
			$key=$arr[$k];
			$list2[$key]=$v;
		}
		return $list2;
	}

	//检查用户是否实名认证
	public function check_user_authentication($uid, $data) {
	    if($uid && $data) {

            $is_open_check = 0; //是否开启实名认证  0关闭   1开启
            $img_check = 0; //实名认证方式 0 姓名+身份证号   1 姓名+身份证+身份证正反面照片

            foreach ($data['goods'] as $key => $value) {
                $check_type = M('Shop_goods')->field('is_open_check,img_check')->where(['goods_id' => $value['goods_id']])->find();

                if ($check_type['is_open_check'] == 1) {
                    $is_open_check = 1;
                    if ($check_type['img_check'] == 1) {
                        $img_check = 1;
                        break;
                    }
                }
            }

            if($is_open_check == 1) { //商品需要实名认证
                //查询该用户是否已实名
                $check_id_card_info = M('User_authentication')->field('user_truename,user_id_number,authentication_img,authentication_back_img,authentication_status')->where(['uid'=>$uid,'authentication_status'=>1])->find();

                if($check_id_card_info) { // 用户已实名  [实名类型不做区分]
                    $res = ['need_check_auth' => false, 'is_open_check'=>$is_open_check, 'img_check'=>$img_check];
                    return $res;
                } else { //用户未实名 跳转实名页
					$res = ['need_check_auth'=> true, 'url'=> $this->config['site_url'] . D('House_village')->base_url . 'pages/my/authentication' ];
                    return $res;
//                    header('Location:'.rtrim(C('site_url'),'/').U('Wap/Card/get_card',array('uid'=>$uid)));
                            header('Location:' . $this->config['site_url'] . '/packapp/plat/pages/my/authentication');
                }

            } else { //商品不需要实名认证
                $res = ['is_open_check'=>0, 'img_check'=>$img_check];
                return $res;
            }
        }
    }
	
	public function save_order()
	{

		set_time_limit(0);
	    $address_id = isset($_POST['adress_id']) ? intval($_POST['adress_id']) : 0;
	    $order_id=I('order_id','','intval');
        $uid=$this->user_session['uid'];

	    //【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
	    $live_id = D("Live_show")->preg_liveid();//正则表达式取出usergent中的live_id
	    $video_id = D("Live_show")->preg_videoid();//正则表达式取出usergent中的video_id

	    /*新增重新下单功能 2018/11/23 */
	    if ($order_id && ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $uid)))) {
	    	$store_id = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0;
	    	$group_id=I('get.group_id','','intval');
        	$bar_id=I('get.bar_id','','intval');
        	$team_id=I('get.team_id','','intval');
            $return = D('Shop_goods')->checkCart($store_id, $uid, $order['info'],0,$address_id,false,2,$group_id,$bar_id,$team_id);

		}
	    elseif(isset($_REQUEST['onlybuy']) && $_REQUEST['onlybuy']=='onlybuy'){
            $return = $this->check_cart($address_id,$_REQUEST['onlybuy']);
        }
	    else{
            $return = $this->check_cart($address_id);
        }
		if ($return['error_code']) $this->error_tips($return['msg']);

		//判断是否有限时价商品，并且比现价还便宜3折（含）以上，前端弹出验证码防止机器作弊
		$return['show_buy_captcha'] = false;
		if($this->config['dingxiang_captcha_appid']){
			foreach($return['goods'] as $value){
				if($value['is_seckill_price'] && $value['discount_price'] <= $value['price']*0.3){
					$return['show_buy_captcha'] = true;
					break;
				}
			}
		}
		if($return['show_buy_captcha']){
			if(!$_POST['CaptchaToken']){
				$this->error_tips('图形校验码未监控通过，请刷新页面后再试。');
			}
			include('./cms/Lib/ORG/dingxianginc/CaptchaClient.php');
			$appId = $this->config['dingxiang_captcha_appid'];
			$appSecret = $this->config['dingxiang_captcha_appsecret'];
			$client = new CaptchaClient($appId, $appSecret);
			$client->setTimeOut(5);      //设置超时时间，默认2秒
			$response = $client->verifyToken($_POST['CaptchaToken']);  //token指的是前端传递的值，即验证码验证成功颁发的token
			//确保验证状态是SERVER_SUCCESS，SDK中有容错机制，在网络出现异常的情况会返回通过
			if($response->serverStatus != 'SERVER_SUCCESS' || !$response->result){
				/**token验证失败**/
				$this->error_tips('图形校验码出现错误，请刷新页面后再试。');
			}
		}
/*---------------本地测试需要正常下单请禁用以下----------------------*/
		$check_db = M('shop_goods_stock_check');
        $check_data = [];
        $check_data['goods'] = json_encode($return['goods']);
        $check_id = $check_db->add($check_data);
        $arr= array();
        $arr['param'] = serialize(array());
        $arr['plan_time'] = -1 * time();
        $arr['space_time'] = 1;
        $arr['add_time'] = time();
        $arr['file'] = 'shop_goods_stock_check';
        $arr['time_type'] = 0;
        $arr['unique_id'] = 'shop_goods_stock_check';
		$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        M('process_sub_plan')->data($arr)->add();

        $process = true;
        $i=0;
        while($process){
            $check_info = $check_db->where(array('id'=>$check_id))->find();
            if($check_info['status']==1){//成功
                $process = false;
                $check_return = true;
            }elseif($check_info['status']==2){//失败
                $process = false;
                $check_goods = json_decode($check_info['msg']);
                $check_goods_str = implode(",", $check_goods);
                $check_tips = '抱歉，商品：'.$check_goods_str.'，库存不足！';
                $this->error_tips($check_tips);
            }
            if ($i >= 50) {
                D('Shop_goods')->check_shop_goods_stock($check_id); //计划任务未开启，手动执行
//                $process = false;
//                $this->error_tips('抱歉，系统繁忙，请稍后再试！');
            }
            $i++;
            usleep(300000);
        }
/*---------------本地测试需要正常下单请禁用以上截止----------------------*/
		// $check_db->where(array('id'=>$check_id))->delete();		//为了日志留存，禁止保存

		


		//再次校验库存 start
	    if ($order_id && ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $uid)))) {
	    	$store_id = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0;
	    	$group_id=I('get.group_id','','intval');
        	$bar_id=I('get.bar_id','','intval');
        	$team_id=I('get.team_id','','intval');
            $return = D('Shop_goods')->checkCart($store_id, $uid, $order['info'],0,$address_id,false,2,$group_id,$bar_id,$team_id);
		}elseif(isset($_REQUEST['onlybuy']) && $_REQUEST['onlybuy']=='onlybuy'){
            $return = $this->check_cart($address_id,$_REQUEST['onlybuy']);
        }else{
            $return = $this->check_cart($address_id);
        }
		if ($return['error_code']) $this->error_tips($return['msg']);
		//再次校验库存 end



        //商城实名认证开关
        if($this->config['open_mall_check_id_card'] == 1) {
            //定制-第三方实名认证  检测商品是否需要用户实名认证
            $res_check = $this->check_user_authentication($uid, $return);
			if($res_check['need_check_auth']){
				$this->error_tips('need_check_auth', $res_check['url']);
			}
        }



		if (IS_POST) {
			$village_id = isset($_REQUEST['village_id']) ? intval($_REQUEST['village_id']) : 0;
			$phone = isset($_POST['ouserTel']) ? htmlspecialchars($_POST['ouserTel']) : '';
			$name = isset($_POST['ouserName']) ? htmlspecialchars($_POST['ouserName']) : '';
			$address = isset($_POST['ouserAddres']) ? htmlspecialchars($_POST['ouserAddres']) : '';
			$pick_address = isset($_POST['pick_address']) ? htmlspecialchars($_POST['pick_address']) : '';
			$invoice_head = isset($_POST['invoice_head']) ? htmlspecialchars($_POST['invoice_head']) : '';
			$pick_id = isset($_POST['pick_id']) ? htmlspecialchars($_POST['pick_id']) : 0;
			$pickType = substr($pick_id, 0, 1);
			$pick_id = substr($pick_id, 1);
			$deliver_type = isset($_POST['deliver_type']) ? intval($_POST['deliver_type']) : 0;
			$arrive_time = isset($_POST['oarrivalTime']) ? htmlspecialchars($_POST['oarrivalTime']) : 0;
			$arrive_date = isset($_POST['oarrivalDate']) ? htmlspecialchars($_POST['oarrivalDate']) : 0;
			$note = isset($_POST['omark']) ? htmlspecialchars($_POST['omark']) : '';

			//京东运费,邮箱
            $jd_freight = isset($_POST['jd_freight']) ? $_POST['jd_freight'] : 0;
            $jd_email = isset($_POST['email']) ? $_POST['email'] : '1396442963@qq.com';


			if ($deliver_type != 1) {//配送方式是：非自提和非快递配送
				if (empty($name)) $this->error_tips('联系人不能为空');
				if (empty($phone)) $this->error_tips('联系电话不能为空');
				//开启对接京东后校验用户手机号
				if(C('config.is_open_jd_goods') && !preg_match("/^1[345789]\d{9}$/", $phone)){
	                //$this->error_tips('请输入正确的手机号！');
	            }
				if (!($user_address = D('User_adress')->field(true)->where(array('adress_id' => $address_id, 'uid' => $this->user_session['uid']))->find())) {
					$this->error_tips('地址信息不存在');
				}
			}
			$now_time = time();
			$order_data = array();
			$order_data['mer_id'] = $return['mer_id'];
			$order_data['store_id'] = $return['store_id'];
			$order_data['uid'] = $this->user_session['uid'];

			$order_data['desc'] = $note;
			$order_data['create_time'] = $now_time;
			$order_data['last_time'] = $now_time;
			$order_data['invoice_head'] = $invoice_head;
			$order_data['village_id'] = $village_id;

			$order_data['num'] = $return['total'];
			$order_data['packing_charge'] = $return['packing_charge'];//打包费
			$order_data['merchant_reduce'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//店铺优惠
			$order_data['merchant_reduce_total'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//所有店铺优惠
			$order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
			$orderid  = build_real_orderid($this->user_session['uid']);
			$order_data['real_orderid'] = $orderid;
			$order_data['no_bill_money'] = 0;//无需跟平台对账的金额

            $order_data['jd_email'] = $jd_email;

            //购买用户信息
            $user_info = D('User')->where(array('uid' => $this->user_session['uid']))->find();
            if (empty($user_info)) {
                $this->error_tips('用户不存在');
            }

            //实名认证
            $order_data['is_open_check'] = $res_check['is_open_check']; //是否开启实名认证  0关闭   1开启
            $order_data['img_check'] = $res_check['img_check']; //实名认证方式 0 姓名+身份证号   1 姓名+身份证+身份证正反面照片


			if($deliver_type == 3){//平台配送
				$diffTime = 60;
				if ($return['store']['send_time_type'] == 1) {
					$diffTime = 3600;
				} elseif ($return['store']['send_time_type'] == 2) {
					$diffTime = 86400;
				} elseif ($return['store']['send_time_type'] == 3) {
					$diffTime = 86400 * 7;
				} elseif ($return['store']['send_time_type'] == 4) {
					$diffTime = 86400 * 30;
				}

				$order_data['is_pick_in_store'] = 0;

				$arrive_time_unix = strtotime($arrive_date .'   '. $arrive_time);
				if ($arrive_time_unix == 0) {
					if($return['delivery_type'] != 5) $this->error_tips('请选择配送时间');
				}

				$order_data['expect_use_time'] = $arrive_time_unix ? $arrive_time_unix : time() + $return['store']['send_time'] + $return['store']['work_time'] * $diffTime;
				$order_data['freight_charge'] = floatval($_POST['plat_deliver_fee']);

                $order_data['freight_charge_original'] =$order_data['freight_charge'];//运费(优惠前的原始运费)
				$delivery_fee = $order_data['freight_charge'];

				$order_data['username'] = $name;
				$order_data['userphone'] = $phone;
				$order_data['address'] = $user_address['adress'] . $user_address['detail'];
				$order_data['address_id'] = $address_id;
				$order_data['lat'] = $user_address['latitude'];
				$order_data['lng'] = $user_address['longitude'];
				$distance = getDistance($user_address['latitude'], $user_address['longitude'],$return['store']['lat'], $return['store']['long']);
				$order_data['distance'] = $distance;
			}
			elseif ($deliver_type == 1) {//自提
				$order_data['is_pick_in_store'] = 2;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
				$delivery_fee = $order_data['freight_charge'] = 0;//运费
				$order_data['username'] = isset($this->user_session['nickname']) && $this->user_session['nickname'] ? $this->user_session['nickname'] : $user_info['nickname'];
				$order_data['userphone'] = isset($this->user_session['phone']) && $this->user_session['phone'] ? $this->user_session['phone'] :$user_info['phone'];
				$order_data['address'] = $pick_address;
				$order_data['address_id'] = 0;
				/*if ($pickType == 's') {*/
				   /* $order_data['pick_id'] = 0*/;
				   /* $order_data['order_status'] = 1;
				    $order_data['status'] = 9;*/
                    /*$order_data['status'] = 7;*/
				/*} else {*/
				    $order_data['pick_id'] = $pick_id;
				    $order_data['status'] = 7;
				/*}*/
// 				$order_data['pick_id'] = $pick_id;
// 				$order_data['status'] = 7;
// 				$order_data['expect_use_time'] = time() + $return['store']['send_time'] * 60;//客户期望使用时间
			}
			else {//配送
				$order_data['username'] = $name;
				$order_data['userphone'] = $phone;
				$order_data['address'] = $address;
				$order_data['address_id'] = $address_id;
				$order_data['lat'] = $user_address['latitude'];
				$order_data['lng'] = $user_address['longitude'];
// 				$order_data['expect_use_time'] = time() + 86400 * 7;//客户期望使用时间
				$delivery_fee = $order_data['freight_charge'] = getFormatNumber($return['delivery_fee']);//运费
				$order_data['is_pick_in_store'] = 3;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
			}
			$hasReduceMoney = 0;
            if(C('config.vip_level_discount_sequence')==1) { //满减后计算
                if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
                    if ($return['sorts_discount_type'] == 0) { //折上折
                        $tempMoney = round($return['vip_discount_money'] - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                        $hasReduceMoney = $tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2);
                        foreach ($return['goods'] as &$goods) {
                            $rate = sprintf('%.2f', ($goods['price'] * $goods['num']) / $return['price']);
                            $rate = $rate ? $rate : 1;
                            $goods['discount_price'] = floatval(round($goods['discount_price'] - (($hasReduceMoney * $rate) / $goods['num']), 2));
                        }
                    } else { //折扣最优
                        if ($return['optimalDiscountMoney'] > 0) {
                            $totalOptimalMoney = $return['optimalDiscountMoney'];
                            $return['vip_discount_money'] = $totalOptimalMoney;
                            $tempMoney = round($totalOptimalMoney - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                            $hasReduceMoney = $tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2);
                            foreach ($return['goods'] as &$goods) {
                                $rate = sprintf('%.2f', ($goods['price'] * $goods['num']) / $return['price']);
                                $rate = $rate ? $rate : 1;
                                $goods['discount_price'] = floatval(round($goods['price'] - (($hasReduceMoney * $rate) / $goods['num']), 2));
                            }
                        }
                    }
                }
                $return['vip_discount_money']  = round($return['vip_discount_money'] - $hasReduceMoney,2);
                $return['discount_price']  = $return['vip_discount_money'];
                $return['can_discount_money'] = round($return['can_discount_money'] - $hasReduceMoney,2);
                $order_data['vip_level_after'] = 1;//会员等级优惠满减后计算
            }else{ //正常计算
                $hasReduceMoney = $return['vip_level_reduce_money'];
            }
            $order_data['vip_level_reduce_money'] = $hasReduceMoney;//会员等级折扣优惠金额
            $order_data['vip_level_discount'] = $return['vip_discount'];//会员等级折扣

			//退货时用到的京东地址码和地址
            $area_code = explode('_',$_POST['area_code']);
            $town = $_POST['town'] ? $_POST['town'] : 0;
			$order_data['jd_area'] = $area_code[0].'_'.$area_code[1].'_'.$area_code[2].'_'.$town;

			$order_data['jd_freight'] = $jd_freight;
			$order_data['order_from'] = 1;//商品的价格
			$order_data['goods_price'] = $return['price'];//商品的价格
			$order_data['extra_price'] = $return['extra_price'];//另外要支付的金额
			$order_data['discount_price'] = $return['vip_discount_money'];//商品折扣后的总价
			$order_data['total_price'] = $return['price'] + $delivery_fee + $return['packing_charge'] + $jd_freight;//订单总价  商品价格+打包费+配送费 +京东运费
			$order_data['total_score'] = $return['total_score'];
			$order_data['price'] = $order_data['discount_price'] + $delivery_fee + $return['packing_charge'] + $jd_freight - $order_data['merchant_reduce'] - $order_data['balance_reduce'];//实际要支付的价格
			$order_data['discount_detail'] = '';//优惠详情
			$order_data['discount_detail'] = $return['discount_list'] ? serialize($return['discount_list']) : '';//优惠详情
			$order_data['can_discount_money'] = $return['can_discount_money'] ;//使用商家优惠券的金额

			$order_data['reduce_stock_type'] = $return['store']['reduce_stock_type'];//'减库存类型（0：支付后，1：下单后）'

			//自定义字段
			if($_POST['cue_field']){
				$order_data['cue_field'] = serialize($_POST['cue_field']);
			}
			//京东下单
            foreach($return['goods'] as $jd_goods){
                if($jd_goods['jd_sku_id']!=0){
                    $re_st[] = array('skuId'=>$jd_goods['jd_sku_id'],'num'=>$jd_goods['num'],'bNeedAnnex'=>true,'bNeedGift'=>true);
                    $jd_order_check[] = array('price'=>$jd_goods['cost_price'],'skuId'=>$jd_goods['jd_sku_id']);
                    $jd_kv[$jd_goods['jd_sku_id']] = $jd_goods['goods_id'];
                }
            }
            if(!empty($re_st)){
                $url_order = 'https://bizapi.jd.com/api/order/submitOrder';
                $data_order['token'] = $this->config['jd_access_token'];
                $data_order['thirdOrder'] = $order_data['real_orderid'];
                $data_order['sku'] = json_encode($re_st);
                $area_code = explode('_',$_POST['area_code']);
                $data_order['name']  = $name;
                $data_order['province']  = $area_code[0];
                $data_order['city']  = $area_code[1];
                $data_order['county']  = $area_code[2];
                $data_order['town']  = $_POST['town'] ? $_POST['town'] : 0;
                $data_order['address']  = $_POST['area_address'];
                $data_order['mobile']  = $phone;
                $data_order['email']  = $jd_email;
                $data_order['remark']  = $note;
                $data_order['invoiceState '] = 2; //集中开票
                $data_order['invoiceType'] = 3; //电子发票
                $data_order['selectedInvoiceTitle'] = 5;// 单位
                $data_order['companyName'] = '山东飞赴网络科技有限公司';//发票抬头
                $data_order['regCode'] = '91371700MA3MQGCR5U';//纳税人识别号
                $data_order['invoiceContent'] = 1;//明细
                $data_order['invoicePhone'] = '13573006288';//增票收票人电话
                $data_order['paymentType'] = 4;
                $data_order['isUseBalance'] = 1;
                $data_order['submitState'] = 0;
                $data_order['doOrderPriceMode'] = 1;
                $data_order['orderPriceSnap'] = json_encode($jd_order_check);
                import('ORG.Net.Http');
                $return_data = http::curlPost($url_order,$data_order);
                $order_data['jd_order_id'] = $return_data['result']['jdOrderId'];
                //下单失败后，检查进货价是否改动
                if($return_data['success']==false){
                   // fdump_sql(json_encode($return_data),'jd_order');
                    import('ORG.Net.Http');
                    $skuIds = '';
                    foreach($jd_order_check as $jt){
                        $skuIds .= $jt['skuId'].',';
                    }
                    $skuIds = substr($skuIds,0,-1);
                    $url_prices = "https://bizapi.jd.com/api/price/getSellPrice";
                    $prices_data['token'] = $this->config['jd_access_token'];
                    $prices_data['sku'] = $skuIds;
                    $prices_data['queryExts'] = 'containsTax';
                    $prices_return = http::curlPost($url_prices,$prices_data);
                   if($prices_return['success']==true){
                       foreach($prices_return['result'] as $price_g){
                            foreach($jd_order_check as $rg){
                                //判断价格
                                if($price_g['skuId']==$rg['skuId']){
                                    if($price_g['price']!=$rg['price']){

                                        //价格加成
                                        $percent = floatval($this->config['jd_price_add_percent']);
                                        if($percent <= 0){
                                            $percent = 1;
                                        }else{
                                            $percent = 1 + ($percent / 100);
                                        }
                                        $goods['price'] = round($price_g['price'] * $percent,2);
                                        $goods['cost_price'] = $price_g['price'];
                                        M('Shop_goods')->where(array('goods_id'=>$jd_kv[$rg['skuId']]))->save($goods);
                                    }
                                }
                            }
                       }
                   }
                $this->error_tips('京东售价已更新，请刷新页面！');
                }elseif($return_data['success']==true){
                }
            }

            //判断商品是否是拼团商品
            foreach ($return['goods'] as $k => $v) {
            	$goods_id=$v['goods_id'];
            }
            $group_id=I('get.group_id','','intval');
            $bar_id=I('get.bar_id','','intval');
            $team_id=I('get.team_id','','intval');
            //用户发起拼团
            if($group_id>0){
            	if($team_id>0){		//参团
            		$order_data['group_id']=$group_id;
	            	$order_data['team_id']=$team_id;
	            	$order_data['buy_type']=2;	//参团
            	}else{		//发起拼团
					$result_arr=D('Mall_group_team')->add_group_team($order_data['uid'],$goods_id);
	            	if($result_arr['error']==1){
	            		$this->error_tips($result_arr['msg']);
	            		exit;
	            	}
	            	$order_data['group_id']=$group_id;
	            	$order_data['team_id']=$result_arr['msg'];
	            	$order_data['buy_type']=1;	//是拼团团长订单
            	}
            }elseif($bar_id>0){
            	$order_data['bar_id']=$bar_id;
            	$order_data['team_id']=$team_id;
            	$order_data['buy_type']=3;
            }

			if (C('config.open_single_spread')==1) {
					// 处理分销人
				if ($_POST['openid'] || $_SESSION['share_openid']) {
					$_POST['openid'] = $_POST['openid'] ? $_POST['openid'] : $_SESSION['share_openid'];
					$distributor_user = D('User')->get_user($_POST['openid'],'openid');
					// 验证是否有分销权限
					$distributor = M('Distributor')->where(array('uid'=>$distributor_user['uid'],'status'=>1))->find();
					if ($distributor) {
						$order_data['distributor_uid'] = intval($distributor_user['uid']);
					}
				}elseif ($_POST['share_uid']) {
					// 验证是否有分销权限
					$distributor = M('Distributor')->where(array('uid'=>$_POST['share_uid'],'status'=>1))->find();
					if ($distributor) {
						$order_data['distributor_uid'] = intval($_POST['share_uid']);
					}
				}
            }
			//打包一口价活动
			$order_data['pack_act_list'] = $return['pack_act_list']?json_encode($return['pack_act_list']):'';

			//【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
	        if($live_id && C("config.live_open") == '1'){
	            $live_goods = D("Live_show_goods")->get_goods_percent_by_live($live_id,'mall');
	            $is_liveshow = 0;
	            if($live_goods['goodids']){
	                foreach ($return['goods'] as $_k => $_g) {
	                    if(in_array($_g['goods_id'], $live_goods['goodids'])){
	                        $is_liveshow = 1;
	                        $return['goods'][$_k]['is_liveshow'] = 1;
	                        $return['goods'][$_k]['liveshow_id'] = $live_id;
                        	$return['goods'][$_k]['liveshow_percent'] = $live_goods['percent'][$_g['goods_id']] == -1 ? C('config.live_show_commission_percent') : $live_goods['percent'][$_g['goods_id']];
	                    }
	                }
	            }
	            if($is_liveshow == 1){
	                $order_data['is_liveshow'] = 1;
	                $order_data['liveshow_id'] = $live_id;
	            }
	        }
	        elseif($video_id && C("config.live_open") == '1'){
	            $live_goods = D("Live_video_goods")->get_goods_percent_by_live($video_id,'mall');
	            $is_livevideo = 0;
	            if($live_goods['goodids']){
	                foreach ($return['goods'] as $_k => $_g) {
	                    if(in_array($_g['goods_id'], $live_goods['goodids'])){
	                        $is_livevideo = 1;
	                        $return['goods'][$_k]['is_livevideo'] = 1;
	                        $return['goods'][$_k]['livevideo_id'] = $video_id;
                        	$return['goods'][$_k]['liveshow_percent'] = $live_goods['percent'][$_g['goods_id']] === -1 ? C('config.live_video_commission_percent') : $live_goods['percent'][$_g['goods_id']];
	                    }
	                }
	            }
	            if($is_livevideo == 1){
	                $order_data['is_livevideo'] = 1;
	                $order_data['livevideo_id'] = $video_id;
	            }
	        }

            /*预售不需要任何外部优惠*/
            if(!empty($return['book_record'])){
                /*$order_data['card_discount']=0;
                $order_data['card_id']=0;
                $order_data['card_price']=0;

                $order_data['merchant_reduce']=0;
                $order_data['balance_reduce']=0;
                $order_data['balance_reduce_total']=0;

                $order_data['coupon_id']=0;
                $order_data['plat_discount']=0.00;

                $order_data['plat_discount_price']=0.00;
                $order_data['can_discount_money']=0.00;
                $order_data['vip_discount_money']=0.00;

                $order_data['merchant_reduce_total']=0.00;
                $order_data['vip_level_reduce_money']=0.00;
                $order_data['vip_level_discount']=0.00;

                $order_data['merchant_discount_price']=0.00;*/
                //预售订单
                $book_record = $return['book_record'];
                $order_data['book_id'] = $book_record['id'];//预售活动id
                /*计算尾款支付金额*/
                $order_data['rest_price'] = $return['price'] + $delivery_fee-$book_record['bargin_price']-$book_record['discount_price'];
                $order_data['expect_book_time_start'] = $book_record['start_time'];
                $order_data['expect_book_time_end']   = $book_record['end_time'];
                $order_data['bargin_price'] = $book_record['bargin_price'];
                if($book_record['rest_pay_type']==1){
                    $order_data['expect_rest_time_start'] = $book_record['rest_start_time'];
                    $order_data['expect_rest_time_end']   = $book_record['rest_end_time'];
                }
                /*优惠价格加入订单*/
                $order_data['book_discount_price'] = $book_record['discount_price']>$order_data['rest_price']?$order_data['rest_price']:$book_record['discount_price'];//活动优惠金额
                $order_data['total_price']=$order_data['bargin_price']+$order_data['rest_price'];
                $order_data['discount_price']=$order_data['bargin_price']+$order_data['rest_price'];
            }
            //end
            $order_id = D('Shop_order')->add($order_data);
			if ($order_id) {
				D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 0));
				if ($order_data['is_pick_in_store'] == 2 && $order_data['status'] == 7) {
					D('Pick_order')->add(array('store_id' => $order_data['store_id'], 'order_id' => $order_id, 'pick_id' => $pick_id, 'status' => 0, 'dateline' => time()));
					//这里新增一步操作，自提订单需要接单后才能分配自提点
					//D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 11, 'name' => $this->staff_session['name'], 'phone' => $this->store['phone']));//分配到自提点
				}
				$detail_obj = D('Shop_order_detail');
				$goods_obj = D("Shop_goods");
                $discountPrice = $order_data['merchant_reduce'] + $order_data['balance_reduce'];
                $totalDiscountPrice = $order_data['discount_price'];
				foreach ($return['goods'] as $grow) {
					$detail_data = array('store_id' => $return['store_id'], 'order_id' => $order_id, 'number' => isset($grow['number']) && $grow['number'] ? $grow['number'] : '', 'cost_price' => $grow['cost_price'], 'unit' => $grow['unit'], 'goods_id' => $grow['goods_id'], 'name' => $grow['name'], 'price' => $grow['price'], 'num' => $grow['num'], 'spec' => $grow['str'], 'spec_id' => $grow['spec_id'], 'create_time' => time());
					$detail_data['goods_image']=$grow['image'];
					$detail_data['is_seckill'] = intval($grow['is_seckill_price']);
					$detail_data['discount_type'] = intval($grow['discount_type']);
					$detail_data['discount_rate'] = $grow['discount_rate'];
					$detail_data['sort_id'] = $grow['sort_id'];
					$detail_data['old_price'] = floatval($grow['old_price']);
					$detail_data['discount_price'] = floatval($grow['discount_price']);
//					$detail_data['pay_price'] = floatval($grow['discount_price']);
                    $detail_data['pay_price'] = floatval(round($grow['discount_price'] * (1 - $discountPrice / $totalDiscountPrice), 2));
                    $detail_data['pay_price'] = is_nan($detail_data['pay_price']) ? 0 : $detail_data['pay_price'];
					$detail_data['jd_sku_id'] = $grow['jd_sku_id'];
					$detail_data['sell_type'] = empty($grow['sell_type']) ? 0:$grow['sell_type'];
					$detail_data['goods_weight']=$grow['goods_weight']?$grow['goods_weight']:0;
					$detail_data['give_goods_id']=$grow['mall_full_layer']['goods_id'] ? $grow['mall_full_layer']['goods_id'] : 0;//满减商品id
					$detail_data['give_coupon_id']=$grow['mall_full_layer']['coupon_id'] ? $grow['mall_full_layer']['coupon_id'] : 0;//满减优惠券id
					$detail_data['score']=$grow['account_score'] ? : 0;
					$detail_data['is_liveshow'] = $grow['is_liveshow'] ? : 0;
					$detail_data['liveshow_id'] = $grow['liveshow_id'] ? : 0;
					$detail_data['is_livevideo'] = $grow['is_livevideo'] ? : 0;
					$detail_data['livevideo_id'] = $grow['livevideo_id'] ? : 0;
					$detail_data['liveshow_percent'] = $grow['liveshow_percent'] ? : 0;

					//周期购信息
					$periodic_info = D('Mall_periodic_purchase')->get_goods_one($grow['goods_id']);
					if($periodic_info){
						$d = array();
						$d['periodic_type'] = $periodic_info['periodic_type'];
						$d['periodic_appoint_day'] = $grow['periodic_appoint_day'];
						$d['periodic_delay_limit'] = $periodic_info['delay_limit'];
						M('Shop_order')->where(array('order_id'=>$order_id))->data($d)->save();
					}

					 //记录限时优惠id
					if ($detail_data['is_seckill']) {
						$seckill_record = D('Time_limited_discount_goods')->get_in_progress_record($detail_data['goods_id']);
						if (empty($detail_data['spec_id'])) {
							$detail_data['limit_id'] = $seckill_record['id'];
						} else {
							$spec_lists = isset($seckill_record['spec_list']) ? $seckill_record['spec_list'] : [];
							$detail_data['limit_id'] = isset($spec_lists[$detail_data['spec_id']]) ? $spec_lists[$detail_data['spec_id']]['id'] : 0;
						}
					}else{
						$detail_data['limit_id'] = 0;
					}
					//dump($detail_data);
					$detail_data_save = D('Shop_order_detail')->add($detail_data);
					//异常时记录至数据库日志里，方便后续查看原因。
					if (!$detail_data_save) {
						$fdumpArr = array(
							'order_id' => $order_id,
							'detail_data' => $detail_data,
						);
						fdump_sql($fdumpArr, 'mall_shop_order_good_fail');
					}
					//echo M()->getLastSql();exit;

					$order_data['reduce_stock_type'] && $goods_obj->update_stock(array_merge($grow,$detail_data));//修改库存
				}
				if ($this->user_session['openid']) {
					$keyword2 = '';
					$pre = '';
					foreach ($return['goods'] as $menu) {
						$keyword2 .= $pre . $menu['name'] . ':' . $menu['price'] . '*' . $menu['num'];
						$pre = '\n\t\t\t';
					}
					$href = C('config.site_url').'/wap.php?c=Mall&a=status&order_id='. $order_id . '&mer_id=' . $order_data['mer_id'] . '&store_id=' . $order_data['store_id'];
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '您好，您的订单已生成', 'keyword3' => $orderid, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $keyword2, 'remark' => '您的该次'.$this->config['mall_alias_name'].'下单成功，感谢您的使用！'), $order_data['mer_id']);
				}

				$printHaddle = new PrintHaddle();
				$printHaddle->printit($order_id, 'shop_order', 0);

				//清除购物车信息,迁移到支付成功后删除
                /*if(cookie('mall_buy_type')=='add'){
                    $mall_cart_ids = json_decode(cookie('mall_cart_ids'),true);
                    M('Mall_cart')->where(array('id'=>array('in',$mall_cart_ids)))->delete();
                    cookie('mall_cart_ids',null);
                }*/
// 				$msg = ArrayToStr::array_to_str($order_id, 'shop_order');
// 				$op = new orderPrint($this->config['print_server_key'], $this->config['print_server_topdomain']);
// 				$op->printit($return['mer_id'], $return['store_id'], $msg, 0);

// 				$str_format = ArrayToStr::print_format($order_id, 'shop_order');
// 				foreach ($str_format as $print_id => $print_msg) {
// 					$print_id && $op->printit($return['mer_id'], $return['store_id'], $print_msg, 0, $print_id);
// 				}


				$sms_data = array('mer_id' => $return['mer_id'], 'store_id' => $return['store_id'], 'type' => 'shop');
				if ($this->config['sms_shop_place_order'] == 1 || $this->config['sms_shop_place_order'] == 3) {
					$sms_data['uid'] = $this->user_session['uid'];
					$sms_data['mobile'] = $order_data['userphone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = '您' . date("Y-m-d H:i:s") . '在【' . $return['store']['name'] . '】中下了一个订单，订单号：' . substr($orderid, 0, 15).'...';
					Sms::sendSms($sms_data);
				}
				if ($this->config['sms_shop_place_order'] == 2 || $this->config['sms_shop_place_order'] == 3) {
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $return['store']['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = '顾客【' . $order_data['username'] . '】在' . date("Y-m-d H:i:s") . '时下了一个订单，订单号：' . substr($orderid, 0, 15).'...' . '请您注意查看并处理!';
					Sms::sendSms($sms_data);
				}


				/*记录平台订单信息 2019/01/04 */
                //增加搜索关键词  店铺名称 和 商品名称
                $goods_name = [];

                foreach ($return['goods'] as $v) {
                    $goods_name[] = $v['name'];
                }

                $goods_name_str = rtrim(implode(',', $goods_name),',');
                $order_data['keywords'] = $return['store']['name'].','.$goods_name_str;

				D('System_order')->add_order('mall',$order_data,$order_id);

				if(IS_AJAX){
					$this->success(array('order_id' => $order_id));
				}else{
					redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'mall')));
				}
			} else {
				$this->error_tips('订单保存失败');
			}
		} else {
			$this->error_tips('不合法的提交');
		}

	}
	
	private function check_cart($address_id,$onlybuy='cart')
	{
		$this->isLogin();
		$store_id = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0;
		if($onlybuy=='onlybuy'){
		   // dump(123123);exit;
            $productCart = $this->getCookieData($onlybuy);
        }else{
            $productCart = $this->getCookieData('cart');//json_decode(cookie('buy_mall_goods_cart'), true);
        }
        $group_id=I('get.group_id','','intval');
        $bar_id=I('get.bar_id','','intval');
        $team_id=I('get.team_id','','intval');

        $begin_skill_status=false;
        //dump($productCart);exit;
         foreach ($productCart as $key=>$value){
             $begin_skill=D('Time_limited_discount_goods')->get_in_twelhour_progress_record($value['productId']);//是否有即将开始的限时优惠活动
             if(!empty($begin_skill)){
                 $begin_skill_status=true;
             }
         }

         if($begin_skill_status){
             return array('error_code' => true, 'msg' => '存在即将开始限时优惠活动的商品,请删掉该商品再结算！');
         }

        $this->user_authentication($store_id,$this->user_session['uid']);//判断所购买商品是否需要实名验证

		return D('Shop_goods')->checkCart($store_id, $this->user_session['uid'], $productCart, 1, $address_id,false,2,$group_id,$bar_id,$team_id);
	}
	
	//判断所购买商品是否需要实名验证
	private function user_authentication($store_id,$uid){
		$authentication_info = M('Mall_authentication')->where(array('uid'=>$uid))->find();
		if($authentication_info['authentication_status']==2) return true;
		$store = M('Merchant_store')->where(array('store_id'=>$store_id))->field('is_authentication')->find();
		if(!$store['is_authentication']) return true;
		$tips = '抱歉：该店铺需要实名认证才可下单';
		$this->error_tips($tips, U('Mall/authentication',array('referer'=>urlencode($_SERVER["REQUEST_URI"]))));
	}
	
	private function getCookieData($onlybuy)
	{
        $productCart = array();
        for ($i = 0; $i < 40; $i++) {
            if($onlybuy=='onlybuy'){
                $tmpCookie = cookie('only_buy_cart_' . $i);
            }else{
//                $tmpCookie = cookie('buy_mall_goods_cart_' . $i);
                $tmpCookie = json_decode(cookie('mall_cart_ids'),true);
                $productCart = M('Mall_cart')->where(array('id'=>array('in',$tmpCookie)))->select();
                foreach($productCart as $key => $cart){
                    if($cart['productParam']!=''){
                        $productCart[$key]['productParam'] = unserialize($productCart[$key]['productParam']);
                    }
                }
                return $productCart;
            }
            if (!empty($tmpCookie)) {
                $tmpArr = json_decode($tmpCookie, true);
                if (empty($tmpArr)) {
                    $tmpArr = array();
                }
                $productCart = array_merge($productCart, $tmpArr);
            } else {
                break;
            }
        }
	    return $productCart;
	}
	
	
	private function isLogin()
	{
		if (empty($this->user_session)) {
			if($this->is_app_browser){
				$this->error_tips('请先进行登录！',U('Login/index'));
			}else{
				redirect(U('Login/index',array('referer'=>urlencode($_SERVER["REQUEST_URI"]))));
			}
		}
	}
	
	private function get_reduce($discounts, $type, $price, $store_id = 0)
	{
		$reduce_money = 0;
		$return = null;
		if (isset($discounts[$store_id])) {
			foreach ($discounts[$store_id] as $row) {
				if ($row['type'] == $type) {
					if ($price >= $row['full_money']) {
						if ($reduce_money < $row['reduce_money']) {
							$reduce_money = $row['reduce_money'];
							$return = $row;
						}
					}
				}
			}
		}
		return $return;
	}

	public function status_old()
	{
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		if ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']))) {
			$storeName = D("Merchant_store")->field('`name`, `phone`')->where(array('store_id' => $order['store_id']))->find();
			$this->assign('storeName', $storeName);
			cookie('shop_cart_' . $order['store_id'], null);
			$this->clear_cookie();
				
			$status = D('Shop_order_log')->field(true)->where(array('order_id' => $order_id))->order('id DESC')->select();
			$statusCount = D("Shop_order_log")->where(array('order_id' => $order_id))->count();
			$this->assign('statusCount', $statusCount);
			$this->assign('status', $status);
			$this->assign('order_id', $order_id);
			$this->assign('order', $order);
			$this->display();
		} else {
			$this->error_tips('错误的订单信息！');
		}
	}
	
	public function order_detail_old()
	{
		$this->isLogin();
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']));
		$store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
		$shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
		$this->clear_cookie();
		$this->assign('store', array_merge($store, $shop));
		$this->assign('order', $order);
		$this->display();
	}
	
	private function clear_cookie()
	{
		$goodsCart = array();
		$type = cookie('mall_buy_type');
		if($type=='add'){
            $buy_mall_goods_cart = $this->getCookieData('cart');//json_decode(cookie('buy_mall_goods_cart'), true);
            foreach ($buy_mall_goods_cart as $row) {
                $goodsCartKey = 's_' . $row['store_id'] . '_g_' . $row['productId'];
                if ($row['productParam']) {
                    foreach ($row['productParam'] as $param) {
                        if ($param['type'] == 'spec') {
                            $goodsCartKey .= '_s_' . $param['id'];
                        } else {
                            if ($param['data']) {
                                foreach ($param['data'] as $da) {
                                    $goodsCartKey .= '_v_' . $da['id'];
                                }
                            }
                        }
                    }
                }
                $goodsCart[$goodsCartKey] = $row;
            }
            for ($i = 0; $i < 40; $i++) {
                $tmpCookie = cookie('buy_mall_goods_cart_' . $i);
                if (!empty($tmpCookie)) {
                    cookie('buy_mall_goods_cart_' . $i, null);
                } else {
                    break;
                }
            }
            cookie('buy_mall_goods_cart', null);
            if ($goodsCart) {
                $cart_goods = array();
                $productCart_t = array();
                for ($i = 0; $i < 40; $i++) {
                    $tmpCookie = cookie('mall_goods_cart_' . $i);
                    if (!empty($tmpCookie)) {
                        $tmpArr = json_decode($tmpCookie, true);
                        if (empty($tmpArr)) {
                            $tmpArr = array();
                        }
                        $productCart_t = array_merge($productCart_t, $tmpArr);
                    } else {
                        break;
                    }
                }
                //$productCart_t = json_decode(cookie('mall_goods_cart'), true);
                foreach ($productCart_t as $row) {
                    $this_index = 's_' . $row['store_id'] . '_g_' . $row['productId'];
                    if ($row['productParam']) {
                        foreach ($row['productParam'] as $param) {
                            if ($param['type'] == 'spec') {
                                $this_index .= '_s_' . $param['id'];
                            } else {
                                if ($param['data']) {
                                    foreach ($param['data'] as $da) {
                                        $this_index .= '_v_' . $da['id'];
                                    }
                                }
                            }
                        }
                    }
                    if (!isset($goodsCart[$this_index])) {
                        $cart_goods[] = $row;
                    }
                }
                if ($cart_goods) {
                    $cart_goods = array_chunk($cart_goods, 5);
                    $t = 0;
                    foreach ($cart_goods as $i => $cart) {
                        cookie('mall_goods_cart_' . $i, json_encode($cart));
                        $t = $i;
                    }
                    $t ++;
                    for ($i = $t; $i < 40; $i++) {
                        cookie('mall_goods_cart_' . $i, null);
                    }
                } else {
                    for ($i = 0; $i < 40; $i++) {
                        cookie('mall_goods_cart_' . $i, null);
                    }
                }
            }
            cookie('mall_buy_cart',null);
        }else{
            cookie('mall_buy_cart',null);
        }

	}
	
	public function order_detail()
	{
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		if ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']))) {
			$storeName = D("Merchant_store")->field('`name`, `phone`')->where(array('store_id' => $order['store_id']))->find();
			$this->assign('storeName', $storeName);
			cookie('shop_cart_' . $order['store_id'], null);
			$this->clear_cookie();
				
			$status = D('Shop_order_log')->field(true)->where(array('order_id' => $order_id))->order('id DESC')->select();
			$statusCount = D("Shop_order_log")->where(array('order_id' => $order_id))->count();
			$this->assign('statusCount', $statusCount);
			$this->assign('status', $status);
			$this->assign('order_id', $order_id);
			$this->assign('order', $order);
			$this->display('status_new');
		} else {
			$this->error_tips('错误的订单信息！');
		}
	}
    
	public function periodic_confirm(){
		$id = $_POST['id'];
		$model = D('Mall_periodic_deliver');
		$result = $model->confirm_arrive($id);
		echo json_encode($result);
	}

	/**
	 * 订单详情
	 */
    public function status()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order = D("Shop_order")->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']));

        if($order['is_pick_in_store']==0){
			redirect(packapp_url('my/mall_order.html?order_id='.$order_id.($_GET['is_first'] ? '&is_first='.$_GET['is_first'] : ''), D('User')->get_user($this->user_session['uid'])));
		}
        if ($order) {
			
            $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
            $store_image_class = new store_image();
            $images = $store_image_class->get_allImage_by_path($store['pic_info']);
            $store['image'] = isset($images[0]) ? $images[0] : '';
            
            //清除购物车信息
            if(cookie('mall_buy_type')=='add'){
                $mall_cart_ids = json_decode(cookie('mall_cart_ids'),true);
                M('Mall_cart')->where(array('id'=>array('in',$mall_cart_ids)))->delete();
                cookie('mall_cart_ids',null);
            }
            
            cookie('shop_cart_' . $order['store_id'], null);
            $this->clear_cookie();
            if($order['pay_type'] == 'offline' && empty($order['third_id'])){
                $payment = rtrim(rtrim(number_format($order['price']-$order['card_price']-$order['merchant_balance']-$order['card_give_money']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-floatval($order['coupon_price']),2,'.',''),'0'),'.');
            }
            $discount_price = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'], 2));
            //$discount_price = floatval(round($order['bargin_price'] +$order['rest_price']+ $order['freight_charge'] + $order['packing_charge'], 2));
            $order['card_discount'] = $order['card_discount'] == 0 ? 10 : $order['card_discount'];
			$cancle_apply_count = M('shop_order_log')->where(['order_id'=>$order['order_id'],'status'=>41])->count();
            $arr['order_details'] = array(
                'orderid' => $order['orderid'],
                'order_id' => $order['order_id'],
                'real_orderid' => $order['real_orderid'],
                'username' => $order['username'],
                'userphone' => $order['userphone'],
                'create_time' => date('Y-m-d H:i:s',$order['create_time']),
                'pay_time' => date('Y-m-d H:i:s',$order['pay_time']),
                'expect_use_time' => $order['expect_use_time'] != 0 ? date('Y-m-d H:i',$order['expect_use_time']) : '尽快',
                'is_pick_in_store' => $order['is_pick_in_store'],//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                'address' => $order['address'],
                'deliver_str' => $order['deliver_str'],
                'deliver_status_str' => $order['deliver_status_str'],
                'note' => isset($order['desc']) ? $order['desc'] : '',
                'invoice_head' => $order['invoice_head'],//发票抬头
                'pay_status' => $order['pay_status_print'],
                'pay_type_str' => $order['pay_type_str'],
                'pay_type' => $order['pay_type'],
                'status_str' => $order['status_str'],
                'score_used_count' => $order['score_used_count'],//抵用的积分
                'score_deducte' => strval(floatval($order['score_deducte'])),//积分兑现的金额
                'card_give_money' => strval(floatval($order['card_give_money'])),//会员卡赠送余额
                'merchant_balance' => strval(floatval($order['merchant_balance'])),//商家余额
                'balance_pay' => strval(floatval($order['balance_pay'])),//平台余额
                'payment_money' => strval(floatval($order['payment_money'])),//在线支付的金额
                'change_price' => strval(floatval($order['change_price'])),//店员修改前的原始价格（如果是0表示没有修改过，可不显示）
                'change_price_reason' => $order['change_price_reason'],//店员修改价格的理由
                'card_id' => $order['card_id'],
                'card_price' => strval(floatval($order['card_price'])),//商家优惠券的金额
                'coupon_price' => strval(floatval($order['coupon_price'])),//平台优惠券的金额
                'payment' => isset($payment) ? $payment : 0,
                'use_time' => $order['use_time'] != 0 ? date('Y-m-d H:i:s',$order['use_time']) : '0',
                'last_staff' => $order['last_staff'],
                'status' => $order['status'],
				'order_status' => $order['order_status'],
                'paid' => $order['paid'],
                'register_phone' => $order['register_phone'],//注册时的用户手机号
                'lat' => $order['lat'],
                'lng' => $order['lng'],
                'cue_field' => $order['cue_field'],//商家自定义字段值（如果没有的话是空 即：''）
                'card_discount' => $order['card_discount'],//会员卡折扣
                'goods_price' => strval(floatval($order['goods_price'])),//商品的总价
                'freight_charge' => strval(floatval($order['freight_charge'])),//配送费
                'packing_charge' => strval(floatval($order['packing_charge'])),//打包费
                'total_price' => strval(floatval($order['total_price'])),//订单总价
                'merchant_reduce' => strval(floatval($order['merchant_reduce'])),//商家优惠的金额
                'balance_reduce' => strval(floatval($order['balance_reduce'])),//平台优惠的金额
                'price' => strval(floatval($order['price'])),//实际支付金额
                'distance' => round(getDistance($order['lat'], $order['lng'], $this->store['lat'], $this->store['long'])/1000, 2),//距离
                'discount_price' => strval($discount_price),//折扣后的总价  = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'], 2));
                'minus_price' => strval(floatval(round($order['merchant_reduce'] + $order['balance_reduce'], 2))),//平台和商家的优惠金额
                'go_pay_price' => strval(floatval(round($discount_price - $order['merchant_reduce'] - $order['balance_reduce'], 2))),//应付的金额
                'minus_card_discount' => strval(floatval(round(($discount_price - $order['merchant_reduce'] - $order['balance_reduce'] - $order['freight_charge']) * (1 - $order['card_discount'] * 0.1), 2))),//折扣与优惠的优惠金额
                'order_from_txt' => $this->order_froms[$order['order_from']],
                'deliver_log_list' => D('Shop_order_log')->where(array('order_id' => $order['order_id']))->order('id DESC')->find(),
                'last_log' =>  D('Shop_order_log')->get_last_log($order['order_id'],'to_wap_usr'),
                'deliver_info' => unserialize($order['deliver_info']),
                'express_name' => isset($order['express_name']) ? $order['express_name'] : '',
                'express_code' => isset($order['express_code']) ? $order['express_code'] : '',
                'express_number' => isset($order['express_number']) ? $order['express_number'] : '',
                'vip_level_reduce_money'=>$order['vip_level_reduce_money'],
                'use_ecard_price'=>$order['use_ecard_price'],
				'total_score' => $order['total_score'],
				'cancle_apply_count'=>$cancle_apply_count,
				'book_id' => $order['book_id'],
				'book_status' => $order['book_status'],
				'expect_book_time_end' => $order['expect_book_time_end'],
				'expect_book_time_start' => $order['expect_book_time_start'],
				'book_time' => $order['book_time'],
				'bargin_price' => $order['bargin_price'],
				'rest_price' => $order['rest_price'],
				'rest_time' => $order['rest_time'],
                'book_discount_price'=>$order['book_discount_price'],
                'book_all_pay' => $order['bargin_price']+$order['rest_price'],
				'expect_rest_time_start' => $order['expect_rest_time_start'],
				'expect_rest_time_end' => $order['expect_rest_time_end'],
            );

			$goods_image_class=new goods_image();
			
			//商品图片列表
            foreach($order['info'] as $v) {
            	$goods_id=$v['goods_id'];
				if($v['give_goods_id']){
					$shop_goods_name = D('Shop_goods')->field('name,price')->where(array('goods_id'=>$v['give_goods_id']))->find();
				}
                if($v['give_coupon_id']){
					$coupon = D('Card_new_coupon')->field('name')->where(array('coupon_id'=>$v['give_coupon_id']))->find();
				}

                $discount_price = floatval($v['discount_price']) > 0 ? floatval($v['discount_price']) : floatval($v['price']);
                /*获取商品图片*/
                $goods_image=D("Shop_order")->get_order_goods_img($v,$goods_image_class);
                $tGoods = array(
					'name' => $v['name'],
					'discount_type' => $v['discount_type'],
					'price' => strval(floatval($v['price'])),
					'discount_price' => strval($discount_price),
					'spec' => empty($v['spec']) ? '' : $v['spec'],
					'num' => $v['num'],
					'total' => strval(floatval($v['price'] * $v['num'])),
					'discount_total' => strval(floatval($discount_price * $v['num'])),
					'is_seckill'=>$v['is_seckill'],
					'image'=>$goods_image,
					//'image'=>$v['goods_image'],
					'give_goods_id'=>empty($shop_goods_name)?'':$shop_goods_name['name'],
					'give_goods_price'=>empty($shop_goods_name)?'':$shop_goods_name['price'],
					'give_coupon_id'=>empty($coupon)?'':$coupon['name'],
					'score' => $v['score'],
				);
				//加上会员等级折扣金额
	            $d_rate = explode(',',$v['discount_rate']);
	            if($v['discount_type']!=3 && $d_rate[0]){
	                $tGoods['discount_total'] = ($tGoods['price'] * $tGoods['num']) *  ($d_rate[0] * 0.01);
	            }else{
	                $tGoods['discount_total'] = $tGoods['price'] * $tGoods['num'];
	            }
				$index = isset($v['packname']) && $v['packname'] ? $v['packname'] : 0;
				if (isset($tempList[$index])) {
					$tempList[$index]['list'][] = $tGoods;
				} else {
					$tempList[$index] = array('name' => $v['packname'], 'list' => array($tGoods));
				}
				$discount_arr=explode(',',$v['discount_rate']);
				$arr['store_discount']=$discount_arr['0'];
            }
//             echo '<pre/>';
//             print_r($arr);die;
            if (count($tempList) == 1) {
				$tempList[$index]['name'] = '';
			}
			
			$arr['info'] = $tempList;
			//修改展示顺序
	        if($order['vip_level_reduce_money']>0){
	            $order['discount_detail']['vip_level'] = array('discount_type'=>6,'vipLevelReduceMoney'=>$order['vip_level_reduce_money'],'vip_discount'=>$order['vip_level_discount'] * 0.1);
	        }
	        if($order['vip_level_after']==0) {
	            foreach($order['discount_list'] as $key => $value){
	                if($key == 'vip_level'){
	                    unset($order['discount_list']['vip_level']);
	                    $order['discount_list']['vip_level'] = $value;
	                }
	            }
	        }
	        foreach($order['discount_detail'] as $key => $value){
	            if($key == 'newuser'){
	                unset($order['discount_detail']['newuser']);
	                $order['discount_detail']['newuser'] = $value;
	            }elseif($key == 'minus'){
	                unset($order['discount_detail']['minus']);
	                $order['discount_detail']['minus'] = $value;
	            }
	        }
	        foreach($order['discount_detail'] as $key => $value){
	            if($key == 'system_newuser'){
	                unset($order['discount_detail']['system_newuser']);
	                $order['discount_detail']['system_newuser'] = $value;
	            }elseif($key == 'system_minus'){
	                unset($order['discount_detail']['system_minus']);
	                $order['discount_detail']['system_minus'] = $value;
	            }elseif($key == 'vip_level'){
	                if($order['vip_level_after']==0) continue;
	                unset($order['discount_detail']['vip_level']);
	                $order['discount_detail']['vip_level'] = $value;
	            }
	        }
	        foreach($order['discount_detail'] as $key => $value){
	            if($key == 'delivery'){
	                unset($order['discount_detail']['delivery']);
	                $order['discount_detail']['delivery'] = $value;
	            }
	        }	        
            $arr['discount_detail'] = $order['discount_detail'] ?: '';
            //获取店铺折扣价格
           	$arr['store_discount_price']=($order['goods_price']*100-$order['discount_price']*100-$order['vip_level_reduce_money']*100)/100;
            //是否周期购
			$arr['is_periodic'] = $order['periodic_type'];
			//判断拼团是否已成团
			if($order['group_id'] && $order['is_pick_in_store'] == 2){
				$mall_group_user = D('Mall_group_user')->where(['order_id' => $order['order_id']])->find();
				if($mall_group_user){
					$mall_group_team = D('Mall_group_team')->where(['id' => $mall_group_user['tid']])->find();
					if($mall_group_team != '1'){
						$arr['un_group_team_pick_qrcode'] = true;
					}
				}
			}
			/*微信支付不能输出自提二维码，修改添加的*/
			if($order['is_pick_in_store'] == 2 && $order['paid'] ==1){
                $arr['un_group_team_pick_qrcode'] = false;
            }
			//dump($arr);exit;
			$this->assign($arr);
			$book_act = D('Mall_book_goods')->get_info($order['book_id']);
			$this->assign('book_act',$book_act);
			$book_tips = '';
            $is_tips = '';
            $is_click = 0;//是否可以付尾款标志
			if($order['book_id']){//有预售活动
				if($order['book_status']==0){
					$book_tips = '定金支付时间：'.date('Y-m-d H:i:s',$book_act['start_time']).'至'.date('Y-m-d H:i:s',$book_act['end_time']);
				}elseif($order['book_status']==1){
					if($book_act['rest_pay_type']=='1'){
					    if(time()>$book_act['rest_end_time']){
					        /*0代表已经过期*/
                            $book_tips ="已过期";
                            $is_tips = "已过期";
                        }else{
                            $book_tips = '尾款支付时间：'.date('Y-m-d H:i:s',$book_act['rest_start_time']).'至'.date('Y-m-d H:i:s',$book_act['rest_end_time']);
                           if(time()>$book_act['rest_start_time'] && time()<=$book_act['rest_end_time']){
                               $is_click=1;
                           }
                        }
                    }else{

						$r_time = $order['book_time']+$book_act['rest_day']*60*60*24;
                        if(time()>=$r_time){
                            $is_click=1;
                        }
						$book_tips = '尾款支付时间：'.date('Y-m-d',$r_time);
					}
				}
			}
            $book_pay_url = $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $order['order_id'] . '&type=mall';
			$this->assign('book_pay_url',$book_pay_url);
			$this->assign('book_tips',$book_tips);
            $this->assign('is_tips',$is_tips);
            $this->assign('is_click',$is_click);
            $shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
            $store['image']=replace_file_domain($store['image']);
            $this->assign('store', array_merge($store, $shop));
			
			//打包一口价活动
			$pack_act_list = json_decode($order['pack_act_list'],1);
			$this->assign('pack_act_list',$pack_act_list);

            //周期购配送列表
            $periodic_deliver = D('Mall_periodic_deliver')->get_order_deliver($order['order_id']);

        	// 当前期数
            $now_periodic_deliver = D('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>array('in','2')))->order('deliver_num DESC')->find();
			
			//控制周期购订单是否可以取消订单（只要有配送任意一期都不可以取消）
			$cancel_order = D('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>array('in','1,2,3')))->count();
			$this->assign('cancel_order',$cancel_order);
			
            $periodic_deliver_count = D('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>array('in','0,1,2')))->count();
            $this->assign('now_deliver_num',$now_periodic_deliver['deliver_num'] ? $now_periodic_deliver['deliver_num']:0);
            $this->assign('periodic_deliver_count',$periodic_deliver_count);
            $this->assign('periodic_deliver',$periodic_deliver);
			
            $this->display('order_detail_new');
        } else {
            $this->error_tips('订单信息错误！');
        }
    }
	
	public function ajax_pack_cart_save(){
		
		if ($this->user_session['uid']) {
			$where['uid'] = $this->user_session['uid'];
			$where['cart_cookie'] = $_POST['cart_cookie'];
			$where['_logic'] = 'or';
			$condition['_complex'] = $where;
		} else {
			$condition['cart_cookie'] = $_POST['cart_cookie'];
		}
		$condition['productId'] = $_POST['goods_id'];
        $info = M('Mall_cart')->where($condition)->find();
		if($info){
			$result = M('Mall_cart')->where($condition)->setInc('count');
		}else{
			$goods_info = M('Shop_goods')->where(array('goods_id'=>$_POST['goods_id']))->find();
			
			$add_data = array();
			$add_data['productId'] = $goods_info['goods_id'];
			$add_data['cart_cookie'] = $_POST['cart_cookie'];
			if($this->user_session['uid']){
				$add_data['uid'] = $this->user_session['uid'];
			}
			$add_data['shop_id'] = $goods_info['store_id'];
			$add_data['productName'] = $goods_info['name'];
			$add_data['productPrice'] = $goods_info['price'];
			$add_data['productStock'] = $goods_info['stock_num'];
			$add_data['unit'] = $goods_info['unit'];
			$add_data['oldPrice'] = $goods_info['old_price'];
			$add_data['count'] = 1;
			$result = M('Mall_cart')->add($add_data);
		}
		$return = array();
		if($result){
			$return['status'] = 1;
			$return['msg'] = '成功！';
		}else{
			$return['status'] = 1;
			$return['msg'] = '失败！';
		}
		echo json_encode($return);
	}
	
    public function ajax_cart_save(){
        if(!$_POST['cart_cookie']){
            $this->error('error');
        }
        if($_POST['store_id']){
            $condition_delete['shop_id'] = $_POST['store_id'];
            if ($this->user_session['uid']) {
                $condition_delete['uid'] = $this->user_session['uid'];
            }else{
                $condition_delete['cart_cookie'] = $_POST['cart_cookie'];
            }
            M('Mall_cart')->where($condition_delete)->delete();
            $this->save_cookie_data();
            $this->success('ok');
        }
        if($_POST['cookieProductCart']) {
            foreach ($_POST['cookieProductCart'] as $value) {
                $condition_delete['shop_id'] = $value['store_id'];
                if ($this->user_session['uid']) {
                    $where['uid'] = $this->user_session['uid'];
                    $where['cart_cookie'] = $_POST['cart_cookie'];
                    $where['_logic'] = 'or';
                    $condition_delete['_complex'] = $where;
                } else {
                    $condition_delete['cart_cookie'] = $_POST['cart_cookie'];
                }
                M('Mall_cart')->where($condition_delete)->delete();
            }
        }
        if($_POST['cookieProductCart']){
            foreach($_POST['cookieProductCart'] as $value){
                $data = $value;
                $data['productParam'] = $data['productParam'] ? serialize($data['productParam']) : array();
                $data['shop_id'] = $data['shop_id'] ? $data['shop_id'] :$data['store_id'];
                $data['cart_cookie'] = $_POST['cart_cookie'];
                $data['uid'] = intval($this->user_session['uid']);
                M('Mall_cart')->data($data)->add();
//                echo M()->getLastSql();die;
            }
            $this->save_cookie_data();
            $this->success('ok');
        }else{
        	$this->save_cookie_data();
            $this->success('ok');
        }
    }

    //更新商城购物车数量缓存
    public function save_cookie_data(){
    	$condition=array();
    	if($this->user_session['uid']){
            $where['uid'] = $this->user_session['uid'];
            $where['cart_cookie'] = cookie('cart_cookie');
            $where['_logic'] = 'or';
            $condition['_complex'] = $where;
        }else{
            $condition['cart_cookie'] = cookie('cart_cookie');
        }
		
		if($_SESSION['session_store_id']){
			$condition['shop_id'] = $_SESSION['session_store_id'];
		}
		
        $cartList = M('Mall_cart')->where($condition)->order('`id` ASC')->select();
        $cartNum=0;
        foreach ($cartList as $k => $v) {
        	$cartNum+=intval($v['count']);
        }
        cookie('mallCartNum',$cartNum);
        return $cartList;
    }

    public function ajax_cart(){
        if(!$_POST['cart_cookie'] /*|| !$_POST['shop_id']*/){
            $this->error('error');
        }
        if($this->user_session['uid']){
            $where['uid'] = $this->user_session['uid'];
            $where['cart_cookie'] = $_POST['cart_cookie'];
            $where['_logic'] = 'or';
            $condition['_complex'] = $where;
        }else{
            $condition['cart_cookie'] = $_POST['cart_cookie'];
        }
//        $condition['shop_id'] = $_POST['shop_id'];
        $field = 'c.*,g.price_type,g.account_money,g.account_score';
        $cartList = M('Mall_cart')->alias('c')->join('Left join '.C('DB_PREFIX').'shop_goods as g on g.goods_id = c.productId')->field($field)->where($condition)->order('`id` ASC')->select();
        if(!$cartList){
            $cartList = array();
        }else{
            $goodsMod = new Shop_goodsModel();
            foreach($cartList as &$cart_value){
                $cart_value['productParam'] = $cart_value['productParam'] ? unserialize($cart_value['productParam']) : array();
                $cart_value['count'] = intval($cart_value['count']);
                $cart_value['isSeckill'] = $cart_value['isSeckill'] == 'false' ? false : true;
                $cart_value['store_id'] = $cart_value['shop_id'];

                $score = $goodsMod->getScoreMaxByGoodsId($cart_value['productId']);
                if ($score['is_show']) {
                    $score['reduce_money'] = $score['is_percent'] ? getFormatNumber($cart_value['productPrice'] * $score['score_max_percent']) : getFormatNumber($score['score_max'] / C('config.user_score_use_percent'));
                    $cart_value['price_type'] = 2;
                    $cart_value['account_money'] = $cart_value['productPrice'] - $score['reduce_money'] > 0 ? getFormatNumber($cart_value['productPrice'] - $score['reduce_money']) : 0;
                    $cart_value['account_score'] = intval($score['reduce_money'] * C('config.user_score_use_percent'));
                }
            }
        }
        $this->success($cartList);
    }

    //商城订单页
    public function mall_order_list(){
    	$this->display();
    }

    //退货售后页面
    public function refund_order_list(){
    	$this->display();
    }

    //我的个人中心
    public function myCenter(){
		$this->init();
		$uid=$this->user_session['uid'];
		$is_manager = M('Classify_manager')->field(true)->where(array('uid'=>$uid))->find();
		if($is_manager){
			$this->assign('is_manager',$is_manager);
		}else{
			$is_manager = false;
			$this->assign('is_manager',$is_manager);
		}
		//	商家优惠券
		$mer_list = D('Card_new_coupon')->get_user_all_coupon_list($uid,1,'mall');
		if($mer_list){
			$mer_number	=	count($mer_list);
		}else{
			$mer_number = 0;
		}
		$this->assign('mer_number',$mer_number);
		//	平台优惠券
		$coupon_list = D('System_coupon')->get_user_coupon_list($uid,$this->user_session['phone'],1,'mall');
		$coupon_number	=	count($coupon_list);
		$this->assign('coupon_number',$coupon_number);
		//	统计我参与的活动
		$sql	=	"SELECT COUNT(*) AS tp_count FROM `pigcms_extension_activity_record` `ear`,`pigcms_extension_activity_list` `eal`,`pigcms_merchant` `m` WHERE(`ear`.`activity_list_id` = `eal`.`pigcms_id` AND `eal`.`mer_id` = `m`.`mer_id`AND `ear`.`uid` = '$uid') GROUP BY `eal`.`pigcms_id` ";
		$mod = new Model();
		$result = $mod->query($sql);
		$activity_number	=	count($result);
		$this->assign('activity_number',$activity_number);
		//	统计我的会员卡
		//$card_number = D('Member_card_set')->get_all_card_count($uid);
		$sql = 'SELECT c.card_id,c.status,cl.status as clstatus,c.bg,c.diybg,m.name,c.discount,cl.id as cardid,cl.card_money,cl.card_money_give,m.pic_info,m.mer_id FROM '
				.C('DB_PREFIX').'card_userlist `cl`  left join '
				.C('DB_PREFIX').'card_new `c` on cl.mer_id = c.mer_id left join '
				.C('DB_PREFIX').'merchant m on c.mer_id  = m.mer_id WHERE ( cl.uid = '.$uid.' AND c.status=1 AND cl.status=1 AND m.status=1) ';
		$res =  M('')->query($sql);

		foreach ($res as $v) {
			$tmp[$v['card_id']]['id'] = $v['cardid'];
		}
		$card_number = count($tmp);
		$this->assign('card_number', $card_number);
		$share = new WechatShare($this->config,$_SESSION['openid']);
		$this->BioAuthticMethod = $share->getBioAuthticMethod($this->static_path);
		$this->assign('BioAuthticMethod', $this->BioAuthticMethod);


		$database_house_worker = D('House_worker');
		$house_worker_condition['status'] = 1;
		$house_worker_condition['openid'] = $this->user_session['openid'];
		if($now_house_worker = $database_house_worker->where($house_worker_condition)->find()){
			$this->assign('now_house_worker' , $now_house_worker);
		}
		if($this->config['show_scroll_msg']){
			$scroll_msg = D('Scroll_msg')->get_msg();
			$this->assign('scroll_msg',$scroll_msg);
		}

		$recently_sign = M('User_sign')->where(array('uid'=>$uid))->order('id DESC')->find();
		$today_sing = false;
		if($recently_sign && strtotime(date('Ymd',$_SERVER['REQUEST_TIME']))==strtotime(date('Ymd',$recently_sign['sign_time']))){
			$today_sing = true;
		}

		if($this->config['open_distributor']==1){
			$distributor['distributor'] = M('Distributor_agent')->where(array('uid'=>$uid,'type'=>1))->find();
			$distributor['agent'] = M('Distributor_agent')->where(array('uid'=>$uid,'type'=>2))->find();
			$this->assign('distributor',$distributor);
		}
		$this->assign('today_sing',$today_sing);

		$site_phone = empty($this->config['site_phone'])?array():explode(' ',$this->config['site_phone']);
		$kefu['phone'] =$site_phone;

        $kf = M('Merchant_store_kefu')->where(['store_id' => 0])->find();
        if (C('IM_JMESSAGE_OPEN') && $kf && $uid > 0) {
            $kefu['url'] = build_im_chat_url('user_' . $uid, $kf['username'], 'user2plat');
        }else if ($this->is_weixin_browser && $this->config['open_kefu_url'] &&  $this->now_user['openid'] && $services = D('Customer_service')->where(array('mer_id' => $this->config['kefu_mer_id']))->select()) {
			$key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $this->now_user['openid']), $this->config['im_appkey']);
			$kefu['url'] = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $this->now_user['openid'] . '&key=' . $key . '#serviceList_' . $this->config['kefu_mer_id'];
		}		
		$this->assign('kefu',$kefu);
		$this->display();
    }

   	/*活动方法*/
   	//限时购商品列表页
   	public function seckill_goods_list(){
   		if(IS_AJAX){
   			$store_id=I('get.store_id','','intval');
	   		$page=I('page',1,'intval');
			$limi['list']=array();
			/*if($page>1){
				echo json_encode($limi);die;
			}*/
            //设置商品区域展示
            $addrs=$this->getUserMsg($this->user_session['uid']);
            if(empty($addrs)){
                $arr['province_id']='';
                $arr['city_id']=C('config.now_city');
                $arr['area_id']='';
                //fdump(C('config.now_city'),"mydededededede",1);
            }else{
                $arr['province_id']=$addrs['province_id'];
                $arr['city_id']=$addrs['city_id'];
                $arr['area_id']=$addrs['area_id'];
            }
	   		$data=D('Shop_goods')->get_skill_goods_list($store_id,$page,10,$arr);
	   		$user_id=$_SESSION['user']['uid'];
	   	
	   		/*if($user_id>0){*/
	   			$list=$data['list'];
	   			foreach ($list as $k => $v) {
                    if($v['seckill_status']==2){
                       unset($list[$k]);
                       continue;
                    }
	   				if($v['seckill_status']==0){
	   					$token=md5('seckill_'.$user_id.$v['seckill_open_time'].$v['goods_id']);
	   					$is_bespoke=D('Plan_clock_msg')->check_bespoke_msg($token);
	   					$v['is_bespoke']=$is_bespoke;
	   				}
	   				$list[$k]=$v;
	   			}
                $limi['list']=array_values($list);
	   	/*	}*/
	   		echo json_encode($limi);
   		}else{
			//顶部导航
			$slider = D('Adver')->get_adver_by_key('wap_seckill_top',5);
			//dump($slider);
			$this->assign('wap_seckill_top',$slider);
   			$store_id=I('store_id','','intval');
	   		$this->assign('store_id',$store_id);
   			$this->display();
   		}
   	}

   	//预约提醒
   	public function add_bespoke_msg(){
   		if (empty($this->user_session)) {
			$this->error('请先进行登录！',U('Login/index'));
		}
   		$start_time=I('start_time',time(),'');
   		$goods_id=I('goods_id','','intval');
   		$user_id=$_SESSION['user']['uid'];
   		$is_app=$this->is_app_browser;
   		if($_SESSION['user']['is_follow']!=1 && !$is_app){
   			$return=array();
   			$return['is_follow']=0;
   			$return['wechat_qrcode']=C('config.wechat_qrcode');
   			$this->error('预约失败','',$return);
   		}
   		$token=md5('seckill_'.$user_id.$start_time.$goods_id);
   		$num=D('Plan_clock_msg')->check_bespoke_msg($token);
   		if($num>0){
   			$this->success('预约成功！');
   		}   		
   		$result=D('Plan_clock_msg')->add_bespoke_msg($user_id,$goods_id,$token,$start_time,$is_app);
   		if($result['error']==0){
   			$this->success('预约成功！');
   		}else{
   			$this->error($result['msg']);
   		}
   	}

   	//拼团活动列表页
   	public function group_goods_list(){
   		if(IS_AJAX){
            //设置商品区域展示
            $addrs=$this->getUserMsg($this->user_session['uid']);
            if(empty($addrs)){
                $arr['province_id']='';
                $arr['city_id']=C('config.now_city');
                $arr['area_id']='';
            }else{
                $arr['province_id']=$addrs['province_id'];
                $arr['city_id']=$addrs['city_id'];
                $arr['area_id']=$addrs['area_id'];
            }
	   		$store_id=I('get.store_id','','intval');
	   		$page=I('page',1,'intval');
	   		$limit=I('limit',10,'intval');
	   		$list=D('Mall_group_act')->get_group_goods_list($store_id,0,$page,$limit,0,0,$arr);
	   		echo json_encode($list);
   		}else{
   			//轮播图
			$banner_list = D('Adver')->get_adver_by_key('mall_group_act_adver',5);
   			$store_id=I('store_id','','intval');
	   		$this->assign('store_id',$store_id);
	   		$this->assign('banner_list',$banner_list);
   			$this->display();	
   		}
   	}

   	//砍价活动列表页
   	public function cut_goods_list(){
   		if(IS_AJAX){
            //设置商品区域展示
            $addrs=$this->getUserMsg($this->user_session['uid']);
            if(empty($addrs)){
                $arr['province_id']='';
                $arr['city_id']=C('config.now_city');
                $arr['area_id']='';
            }else{
                $arr['province_id']=$addrs['province_id'];
                $arr['city_id']=$addrs['city_id'];
                $arr['area_id']=$addrs['area_id'];
            }
	   		$store_id=I('get.store_id','','intval');
	   		$page=I('page',1,'intval');
	   		$limit=I('limit',10,'intval');
	   		$list=D('Mall_bargain_act')->get_cut_goods_list($store_id,$page,$limit,1,$arr);
	   		echo json_encode($list);
   		}else{
   			//轮播图
			$banner_list = D('Adver')->get_adver_by_key('mall_cut_act_adver',5);
   			$list=D('Mall_bargain_act')->get_cut_user_list();
   			$store_id=I('store_id','','intval');
	   		$this->assign('store_id',$store_id);
	   		$this->assign('banner_list',$banner_list);
	   		$this->assign('list',$list);
   			$this->display();
   		}
   	}

   	public function group_team_detail(){
   		$group_id=I('get.group_id','','intval');
   		$team_id=I('get.team_id','','intval');
   		$order_id=I('get.order_id','','intval');
   		$group_info=D('Mall_group_act')->get_group_by_id($group_id);
   		$user_id=$_SESSION['user']['uid'];
    	if($group_info && $group_info['join_type']==2 && $user_id>0){
    		$buy_num=M('shop_order')->where(array('uid'=>$user_id,'store_id'=>$group_info['store_id']))->count(1);
    		$group_info['buy_num']=$buy_num;
    	}
		//商品信息
    	$now_goods = D('Shop_goods')->get_goods_by_id($group_info['good_id'],$group_id);
    	//团队信息
    	$team_info=D('Mall_group_team')->get_team_info($group_id,$team_id,$user_id,$group_info,2);
    	$end_time=$team_info['start_time']+$group_info['affect_time'];
    	$team_info['time_clock']=$end_time-time();
    	if($team_info['complete_num']>$team_info['num']){
			$team_info['need_num']=$team_info['complete_num']-$team_info['num'];
		}else{
			$team_info['need_num']=0;
		}
    	//参团用户信息
    	$team_user_list=D('Mall_group_team')->get_team_user_list($team_id,$order_info['mer_id']);
    	//判断用户是否已参与拼团
    	$user_id=$_SESSION['user']['uid'];
    	$is_join=0;
    	if($user_id>0){
    		foreach ($team_user_list as $k => $v) {
    			if($v['user_id']==$user_id){
    				$is_join=1;
    				$order_id=$v['order_id'];
    				break;
    			}
    		}
    	}
    	$goods_list=$now_goods['list'];
		foreach ($goods_list as $k => $v) {
			$v['reg_img']=$v['reg_img']?$v['reg_img']['s_image']:$now_goods['image'];
			$goods_list[$k]=$v;
		}
		$from=I('get.from','','trim');
		//拼团活动列表
		$group_list=D('Mall_group_act')->get_group_goods_list('',1,1,6,0,0);
		$this->assign('list',$group_list);
		$this->assign('order_id',$order_id);
    	$this->assign('is_join',$is_join);
    	$this->assign('group_info',$group_info);
    	$this->assign('now_goods',$now_goods);
    	$this->assign('goods_detail', $goods_list ? json_encode($goods_list) : '');
    	$this->assign('team_info',$team_info);
    	$this->assign('team_user_list',$team_user_list);
    	$this->display();
   	}

   	//拼团订单详情页
   	public function group_order_detail(){
   		$order_id = I('get.order_id','','intval');
        $order_info=M('shop_order')->where(array('order_id'=>$order_id))->find();
        if ($order_info && $order_info['group_id']>0) {
        	$group_id=$order_info['group_id'];
        	$team_id=$order_info['team_id'];
        	$this->redirect('Mall/group_team_detail',array('group_id'=>$group_id,'team_id'=>$team_id,'order_id'=>$order_id,'from'=>ACTION_NAME));
        } else {
            $this->error_tips('订单信息错误！');
        }
   	}

   	//参团详情页
   	public function group_join_detail(){
   		$group_id=I('group_id','','intval');
   		$team_id=I('team_id','','intval');
   		if(empty($group_id) || empty($team_id)){
   			$this->error_tips('网络出错，请重试！');
   		}
   		$group_info=D('Mall_group_act')->get_group_by_id($group_id);
   		//商品信息
        $now_goods = D('Shop_goods')->get_goods_by_id($group_info['good_id']);
        //团队信息
        $team_info=D('Mall_group_team')->get_team_info($order_info['group_id'],$team_id,$order_info['uid'],null,2);
        $team_info['end_time']=$team_info['start_time']+$group_info['affect_time'];
   		//参团用户信息
    	$team_user_list=D('Mall_group_team')->get_team_user_list($team_id,$order_info['mer_id']);

    	$this->assign('group_info',$group_info);
    	$this->assign('now_goods',$now_goods);
    	$this->assign('team_info',$team_info);
    	$this->assign('team_user_list',$team_user_list);
    	$this->display();
   	}

    //发起砍价活动
    public function add_bargain_team(){
        $goods_id=I('goods_id','','intval');
    	if (empty($this->user_session)) {
			$this->error('请先进行登录！',U('Mall/detail',array('is_login'=>1,'goods_id'=>$goods_id)));
		}
    	$bar_id=I('bar_id','','intval');
    	$store_id=I('store_id','','intval');
    	if(empty($bar_id) || empty($store_id)){
    		$this->error('缺少参数！');
    	}
    	$result=D('Mall_bargain_act')->get_bar_act_info($bar_id);
    	if($result['error']==1){
    		$this->error($result['msg']);
    	}    	
    	$user_id=$_SESSION['user']['uid'];
    	$bar_info=$result['msg'];
    	$store_info = D("Merchant_store")->field(true)->where(array('store_id' => $store_id))->find();
    	//发起砍价
    	$result=D('Mall_bargain_team')->add_bargain_team($bar_info,$store_info['mer_id'],$user_id);
    	if($result['error']==1){
    		$this->error($result['msg'],'',$result);
    	}
    	$team_id=$result['msg'];
		$result2=D('Mall_bargain_team')->check_help_bar($user_id,$team_id,$bar_info,2);
		if($result2['error']==1){
			$this->error($result2['msg']);
		}
		$team_info=$result2['msg'];
		$bar_price=D('Mall_bargain_team')->get_bargain_price($bar_info,$team_info);
		$res=D('Mall_bargain_team')->add_help_bar_log($team_id,$user_id,$bar_price,$team_info,1);
    	if($res){	//发起成功，跳转
    		$this->success('发起砍价成功',U('Mall/index'),array('bar_price'=>$bar_price,'team_id'=>$team_id));
    	}else{
    		$this->error('发起砍价失败，请重试！');
    	}
    }

    //帮助砍价
    public function help_bargain(){
    	if (empty($this->user_session)) {
			$this->error('请先进行登录！',U('Login/index'));
		}
    	$bar_id=I('bar_id','','intval');
    	$team_id=I('team_id','','intval');
    	if(empty($bar_id) || empty($team_id)){
    		$this->error('缺少参数！');
    	}
    	$result=D('Mall_bargain_act')->get_bar_act_info($bar_id,$team_id);
    	if($result['error']==1){
    		$this->error($result['msg']);
    	}
    	$user_id=$_SESSION['user']['uid'];
    	$bar_info=$result['msg'];
    	//校验能不能帮助砍价
    	$result2=D('Mall_bargain_team')->check_help_bar($user_id,$team_id,$bar_info);
    	if($result2['error']==1){
    		$this->error($result2['msg']);
    	}
    	$team_info=$result2['msg'];
		$bar_price=D('Mall_bargain_team')->get_bargain_price($bar_info,$team_info);
		$res=D('Mall_bargain_team')->add_help_bar_log($team_id,$user_id,$bar_price,$team_info);
		if($res){	//发起成功，跳转
    		$this->success('砍价成功',U('Mall/index'),array('bar_price'=>$bar_price));
    	}else{
    		$this->error('砍价失败，请重试！');
    	}
    }

    //优惠券列表
    public function coupon_list(){

		if($_GET['type']=='mer') {
			$coupon_list = D('Card_new_coupon')->get_user_all_coupon_list($this->user_session['uid']);

			$this->assign('cate_platform', D('Card_new_coupon')->cate_platform());
		}else{
			$coupon_list=array();

			$coupon_list = D('System_coupon')->get_user_coupon_list($this->user_session['uid'], $this->user_session['phone']);

			$this->assign('cate_platform', D('System_coupon')->cate_platform());
		}
		$tmp = array();

		$card_info=array();
		foreach ($coupon_list as $key => $v) {
			if($v['type']!='mall'){
				continue;
			}
			$card_info[$v['is_use']]++;
			if (!empty($tmp[$v['is_use']][$v['coupon_id']])) {
				$tmp[$v['is_use']][$v['coupon_id']]['get_num']++;
			} else {
				$tmp[$v['is_use']][$v['coupon_id']] = $v;
				$mer = M('Merchant')->where(array('mer_id'=>$v['mer_id']))->find();
				$tmp[$v['is_use']][$v['coupon_id']]['merchant']=$mer['name'];
				$tmp[$v['is_use']][$v['coupon_id']]['get_num'] = 1;
				switch($v['type']){
					case 'all':
						$url = $this->config['site_url'].'/wap.php';
						break;
					case 'group':
						$url = $this->config['site_url'].'/wap.php?g=Wap&c=Group&a=index';
						break;
					case 'meal':
						$url = $this->config['site_url'].'/wap.php?g=Wap&c=Meal_list&a=index';
						break;
					case 'appoint':
						$url = $this->config['site_url'].'/wap.php?g=Wap&c=Appoint&a=index';
						break;
					case 'shop':
						$url = $this->config['site_url'].'/wap.php?g=Wap&c=Shop&a=index';
						break;
					case 'mall':
						$url = $this->config['site_url'].'/wap.php?g=Wap&c=Mall&a=index';
						break;
				}
				$tmp[$v['is_use']][$v['coupon_id']]['url'] = $url;
			}
		}
		$this->assign('coupon', $tmp);
		$this->assign('card_info', $card_info);
		$this->display();
    }

    //我的拼团活动
    public function my_group_act(){
    	if(IS_AJAX){
    		$user_id=$_SESSION['user']['uid'];
    		$page=I('page',1,'intval');
    		$status=I('status','','');
    		$where=array();
    		if($status>=0){
    			$where['team_status']=$status;
    		}
	    	$list=D('Mall_group_team')->get_user_join_team($user_id,$where,$page,10);
	    	echo json_encode($list);
    	}else{
			$this->isLogin();
			$this->display();
    	}
    }

    //我的砍价活动
    public function my_bargain_act(){
    	if(IS_AJAX){
			$user_id=$_SESSION['user']['uid'];
			$page=I('page',1,'intval');
			$list=D('Mall_bargain_team')->get_user_team_list($user_id,$page,10);
	    	echo json_encode($list);
    	}else{
    		$this->isLogin();    	
    		$this->display();
    	}
    }

    //小程序分享
	public function share(){
		$goods_id=I('get.goods_id','','intval');
		$goods_info=M('Shop_goods')->where(array('goods_id'=>$goods_id))->find();
		if(empty($goods_info)){
			$this->error('当前商品不存在！');
		}
		$goods_image=new goods_image();
		$goods_name=$goods_info['name'];
		
		$img_arr=D('Shop_goods')->reg_good_img($goods_info['image'],$goods_image,-1);
		$goods_info['goodMainPic']=$img_arr['0']['url']['s_image'];
		$goods_pic=$goods_info['goodMainPic'];

		/*总本地服务器获取数据*/
		$root_path=$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
		$goods_info['goodMainPic']=str_replace($root_path,'.',$goods_info['goodMainPic']);


		$img_rand_path=sprintf("%09d", $goods_id);
		$rand_num = substr($img_rand_path, 0, 3) . '/' . substr($img_rand_path, 3, 3) . '/' . substr($img_rand_path, 6, 3);
		
		$group_id=I('get.group_id','','intval');
		$bar_id=I('get.bar_id','','intval');
		$team_id=I('get.team_id','','intval');

		$imgMallPath = 'upload/wxapp_mall/'.$rand_num.'/mall_'.$goods_id;
		$imgMallPathShare = 'runtime/wxapp_mall/'.$rand_num.'/mall_'.$goods_id.'_'.$this->user_session['uid'];

		$imgFriendPath = 'upload/wxapp_mall/'.$rand_num.'/friend_'.$goods_id;
	
		$is_invite_act=I('is_invite_act',1,'intval');
		if($group_id>0 || $bar_id>0){
			if($group_id>0){	//邀请参团
				$imgMallPath.='_'.$group_id;
				$imgMallPathShare.='_'.$group_id;
				$imgFriendPath.='_'.$group_id;				
				$url='/wap.php?c=Mall&a=detail&goods_id='.$goods_id.'&group_id='.$group_id;
				//获取拼团信息
				$group_info=M('Mall_group_act')->where(array('id'=>$group_id))->find();
				$join_price_arr=explode('/',$group_info['join_price']);
				sort($join_price_arr);
				$group_info['group_price']=$join_price_arr['0'];
				$goods_info['price']=$group_info['group_price'];
				$is_invite_group=1;
				$goods_info['is_invite_group']=1;
			}elseif($bar_id>0){
				$imgMallPath.='_'.$bar_id;
				$imgMallPathShare.='_'.$bar_id;
				$imgFriendPath.='_'.$bar_id;
				$url='/wap.php?c=Mall&a=detail&p0=1&p1='.$goods_id.'&p3='.$bar_id;
				//获取砍价信息
				$cut_info=M('Mall_bargain_act')->where(array('id'=>$bar_id))->find();
				$goods_info['price']=$cut_info['floor_price'];
				$is_invite_cut=1;
				$goods_info['is_invite_cut']=1;
			}
			//邀请参团或砍价
			if($team_id>0 && $is_invite_act){
				$imgMallPath.='_'.$team_id;
				$imgMallPathShare.='_'.$team_id;
				$imgFriendPath.='_'.$team_id;
				if($is_invite_group==1){
					$url='/wap.php?c=Mall&a=group_team_detail&group_id='.$group_id;
				}
				if($is_invite_cut){
					$url.='&p4='.$team_id;					
				}else{
					$url.='&team_id='.$team_id;
				}
			}
		}
		else{

			$url='/wap.php?c=Mall&a=detail&s=1&goods_id='.$goods_id;
			//限时秒杀商品的推广图
			$is_seckill_price=I('get.is_seckill_price',0,'intval');
            $tm = time();
            $week = date('w');
            $today = date('Y-m-d');
            $where = [
                'is_del' => 0,
                'goods_id' => $goods_id,
                'end_date' => ['egt', $today],
                'start_date' => ['elt', $today],
                '_string' => "UNIX_TIMESTAMP(CONCAT('$today', ' ', `end_time`)) > $tm AND UNIX_TIMESTAMP(CONCAT('$today', ' ', `start_time`)) < $tm AND FIND_IN_SET($week,`week`)"
            ];
            $record = M('Time_limited_discount_goods')->where($where)->find();
            $mytest = D('Time_limited_discount_goods_spec')->where(['limit_id' => $record['id']])->select();
            if (empty($record)) {
                if($goods_info['seckill_price']>0){
                    $goods_info['price']=$goods_info['seckill_price'];
					$goods_info['is_seckill_price'] = true;
                }else{
                    $goods_info['price']=$goods_info['price'];
                }
            }
            else{
				if($record['stock'] != 0){
					if(empty($mytest)){
						$goods_info['price']=$record['limit_price'];
					}else{
						$goods_info['price']=$mytest[0]['limit_price'];
					}
                }
				$goods_info['is_seckill_price'] = true;
            }
		}
		$imgMallPath.='.png';
		$imgMallPathShare.='.png';
		$imgFriendPath.='.png';

		//换短链
		$link_id = M('Short_link')->data(['link_url'=>$url])->add();
		if(!$link_id){
			$this->error('小程序码生成失败');
		}

		//小程序路径
		$wxapp_path = D('House_village')->home_url . '?redirect=webview&webview_url='.urlencode('/short_'.$link_id);

		//商城分享朋友圈背景图
		$imgMallPath=$this->get_wxapp_share_bg($imgMallPath,$goods_info,$wxapp_path);

		//分享朋友圈图片 600*822图片
		if($this->user_session){
			if(!file_exists(dirname($imgMallPathShare))){
				mkdir(dirname($imgMallPathShare),0777,true);
			}
			$img = imagecreatetruecolor(600,822);

			$white  =  imagecolorallocate ($img,255,255,255);
			imagefill($img,0,0,$white);
			
			//背景图片绘制
			$src_im=imagecreatefrompng($imgMallPath);
			imagecopy($img,$src_im,0,0,0,0,600,822);
			
			$font = './static/fonts/apple_lihei.otf';

			import('ORG.Net.Http');
			$http = new Http();
			$avatar = $http->curlGet($this->user_session['avatar']);
			
			if(!file_exists('./runtime/tempQrcode')){
				mkdir('./runtime/tempQrcode',0777,true);
			}
			$filePath = './runtime/tempQrcode/'.uniqid().'.jpg';
			file_put_contents($filePath,$avatar);
			
			$src_im =  imagecreatefromjpeg($filePath);
			
			//创建新图像
			$newimg = imagecreatetruecolor(60,60);
			// 调整默认颜色
			$color = imagecolorallocate($newimg, 255, 255, 255);
			imagefill($newimg, 0, 0, $color);
			//裁剪
			imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 60,60,132,132);
			imagedestroy($src_im); //销毁原图
			imagecopy($img,$newimg,45,45,0,0,60,60);

			//用户昵称
			$fontSize = 14;//像素字体
			$fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
			imagettftext($img, $fontSize, 0, 120, 72, $fontColor, $font, '@ '.$this->user_session['nickname']);
            //imagettftext ($img, $fontSize, 0, 10, 366, $fontColor, $font, C('config.Currency_symbol').$goods_info['price']);
			imagepng($img,$imgMallPathShare);	
		}
		
		/*----朋友圈图片结束----*/

		/*分享好友 500*400 图片 */
		$imgFriendPath=$this->get_share_friend_pic($imgFriendPath,$goods_info);
		/*---分享好友图片结束--*/

		$share_data=array();
		if($is_invite_group==1){
			$share_data['share_title']='邀您参团'.$goods_info['name'];
			$share_data['title']='邀请好友一起拼团！';
		}elseif($is_invite_cut==1){
			$share_data['share_title']='邀您砍价'.$goods_info['name'];
			$share_data['title']='邀请好友一起砍价！';
		}else{
			$share_data['share_title']='我推荐'.$goods_info['name'];
			$share_data['title']='邀请好友一起买！';
		}
		$share_data['good_image'] = $goods_pic . "?" . time();
		$share_data['friend_img'] = file_domain().'/'.$imgFriendPath . "?" . time();
		$share_data['mall_img'] = C('config.site_url').'/'.$imgMallPathShare . "?" . time();
		$share_data['wxapp_path'] = $wxapp_path;
		$share_data['goods_info'] = $goods_info;
		if(empty($this->user_session)){
			$this->error('请先登录！');
		}else{
			$this->success($share_data);
		}		
	}

	protected function get_share_friend_pic($imgFriendPath,$goods_info){
		$file_handle = new file_handle();
		if(!$goods_info['is_seckill_price'] && !$file_handle->file_exists($imgFriendPath)){
			if(!file_exists(dirname($imgFriendPath))){
				mkdir(dirname($imgFriendPath));
			}
			$font = './static/fonts/apple_lihei.otf';
			$goodMainPic=$goods_info['goodMainPic'];

			$goods_price=$goods_info['price'];
			$goods_old_price=$goods_info['old_price'];

			$img = imagecreatetruecolor(500,400);

			$white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
			imagefill ( $img ,  0 ,   0 ,  $white );

			//背景图片绘制
			if($goods_info['is_invite_group']==1){
				$src_im = imagecreatefrompng('static/wxapp/wxapp_mall_group_bg.png');
			}elseif($goods_info['is_invite_cut']==1){
				$src_im = imagecreatefrompng('static/wxapp/wxapp_mall_cut_bg.png');
			}else{
				$src_im = imagecreatefrompng('static/wxapp/wxapp_friend_bg.png');
			}
			
			//裁剪
			imagecopy($img,$src_im,0,0,0,0,500,400);

			//商品图片绘制
			$info   = getimagesize($goodMainPic);
			$fun    = 'imagecreatefrom'.image_type_to_extension($info[2], false);
			$src_im =  call_user_func_array($fun,array($goodMainPic));


			//创建新图像
			$newimg = imagecreatetruecolor(308,308);
			// 调整默认颜色
			$color = imagecolorallocate($newimg, 255, 255, 255);
			imagefill($newimg, 0, 0, $color);
			//裁剪
			imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 308,308,$info[0],$info[1]);
			imagedestroy($src_im); //销毁原图
			imagecopy($img,$newimg,96,0,0,0,308,308);

			//实际价格
			$fontSize = 20;//像素字体
			$fontColor = imagecolorallocate($img, 238, 0, 0);//字的RGB颜色
			imagettftext ($img, $fontSize, 0, 10, 366, $fontColor, $font, C('config.Currency_symbol').$goods_price);
			if($goods_old_price>0 && $goods_old_price>$goods_price){
				//原价格
				$fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$goods_price);//文字水平居中实质
				$mallPriceWidth = $fontBox[2];
				$fontSize = 12;//像素字体
				$fontColor = imagecolorallocate($img, 153, 153, 153);//字的RGB颜色
				imagettftext ($img, $fontSize, 0, 30 + $mallPriceWidth, 363, $fontColor, $font, C('config.Currency_symbol').$goods_old_price);
				$fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$goods_old_price);//文字水平居中实质
				$mallOldPriceWidth = $fontBox[2];
				$mallOldPriceBegin = 26 + $mallPriceWidth;
				imageline($img,$mallOldPriceBegin,357,$mallOldPriceBegin + $mallOldPriceWidth + 10,357,$fontColor);	
			}
			//保存主图
			imagepng($img,$imgFriendPath);
		}
		else{
            if(!file_exists(dirname($imgFriendPath))){
                mkdir(dirname($imgFriendPath));
            }
            $font = './static/fonts/apple_lihei.otf';
            $goodMainPic=$goods_info['goodMainPic'];

            $goods_price=$goods_info['price'];
            $goods_old_price=$goods_info['old_price'];

            $img = imagecreatetruecolor(500,400);

            $white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
            imagefill ( $img ,  0 ,   0 ,  $white );

            //背景图片绘制
            if($goods_info['is_invite_group']==1){
                $src_im = imagecreatefrompng('static/wxapp/wxapp_mall_group_bg.png');
            }elseif($goods_info['is_invite_cut']==1){
                $src_im = imagecreatefrompng('static/wxapp/wxapp_mall_cut_bg.png');
            }else{
                $src_im = imagecreatefrompng('static/wxapp/wxapp_friend_bg.png');
            }

            //裁剪
            imagecopy($img,$src_im,0,0,0,0,500,400);

            //商品图片绘制
            $info   = getimagesize($goodMainPic);
            $fun    = 'imagecreatefrom'.image_type_to_extension($info[2], false);
            $src_im =  call_user_func_array($fun,array($goodMainPic));


            //创建新图像
            $newimg = imagecreatetruecolor(308,308);
            // 调整默认颜色
            $color = imagecolorallocate($newimg, 255, 255, 255);
            imagefill($newimg, 0, 0, $color);
            //裁剪
            imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 308,308,$info[0],$info[1]);
            imagedestroy($src_im); //销毁原图
            imagecopy($img,$newimg,96,0,0,0,308,308);

            //实际价格
            $fontSize = 20;//像素字体
            $fontColor = imagecolorallocate($img, 238, 0, 0);//字的RGB颜色

            imagettftext ($img, $fontSize, 0, 10, 366, $fontColor, $font, C('config.Currency_symbol').$goods_price);
            if($goods_old_price>0 && $goods_old_price>$goods_price){
                //原价格
                $fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$goods_price);//文字水平居中实质
                $mallPriceWidth = $fontBox[2];
                $fontSize = 12;//像素字体
                $fontColor = imagecolorallocate($img, 153, 153, 153);//字的RGB颜色
                imagettftext ($img, $fontSize, 0, 30 + $mallPriceWidth, 363, $fontColor, $font, C('config.Currency_symbol').$goods_old_price);
                $fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$goods_old_price);//文字水平居中实质
                $mallOldPriceWidth = $fontBox[2];
                $mallOldPriceBegin = 26 + $mallPriceWidth;
                imageline($img,$mallOldPriceBegin,357,$mallOldPriceBegin + $mallOldPriceWidth + 10,357,$fontColor);
            }
            //保存主图
            imagepng($img,$imgFriendPath);
        }
        $imgFriendPath=$imgFriendPath."?".time();
		return $imgFriendPath;
	}

	//获取商城商品小程序分享朋友圈背景图
	protected function get_wxapp_share_bg($imgMallPath,$goods_info=null,$wxapp_path){
        if($goods_info){
            $goods_id=$goods_info['goods_id'];
            /*限时价格显示*/
            $tm = time();
            $week = date('w');
            $today = date('Y-m-d');
            $where = [
                'is_del' => 0,
                'goods_id' => $goods_id,
                'end_date' => ['egt', $today],
                'start_date' => ['elt', $today],
                '_string' => "UNIX_TIMESTAMP(CONCAT('$today', ' ', `end_time`)) > $tm AND UNIX_TIMESTAMP(CONCAT('$today', ' ', `start_time`)) < $tm AND FIND_IN_SET($week,`week`)"
            ];
            $record = M('Time_limited_discount_goods')->where($where)->find();
            $mytest = D('Time_limited_discount_goods_spec')->where(['limit_id' => $record['id']])->select();
            if (empty($record)) {
                if($goods_info['seckill_price']>0){
                    $goods_info['price']=$goods_info['seckill_price'];
                }else{
                    $goods_info['price']=$goods_info['price'];
                }
            }
            else{
                if(empty($mytest)){
                    $goods_info['price']=$record['limit_price'];
                }else{
                    $goods_info['price']=$mytest[0]['limit_price'];
                }

            }
        }
        $goodMainPic=$goods_info['goodMainPic'];
		$goods_name=$goods_info['name'];
		$describe=$goods_info['describe'];
		$goods_price=$goods_info['price'];
		$goods_old_price=$goods_info['old_price'];
		$left=45;
		$top=125;
		//共用模板
		if(!file_exists($imgMallPath)){
			if(!file_exists(dirname($imgMallPath))){
				mkdir(dirname($imgMallPath),0777,true);
			}
			$img = imagecreatetruecolor(600,822);			
			$white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
			imagefill ( $img ,  0 ,   0 ,  $white );
			//背景图片绘制
			$src_im = imagecreatefrompng('static/wxapp/wxapp_mall_bg.png');
			//裁剪
			// imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 600,822,600,822);
			// imagedestroy($src_im); //销毁原图
			imagecopy($img,$src_im,0,0,0,0,600,822);

			//商品图片绘制
			$info   = getimagesize($goodMainPic);
			$fun    = 'imagecreatefrom'.image_type_to_extension($info[2], false);
			$src_im =  call_user_func_array($fun,array($goodMainPic));

			//创建新图像
			$newimg = imagecreatetruecolor(400,400);
			// 调整默认颜色
			$color = imagecolorallocate($newimg, 255, 255, 255);
			imagefill($newimg, 0, 0, $color);
			//裁剪
			imagecopyresampled($newimg, $src_im, 0, 0, 0,0,400,400,$info[0],$info[1]);
			imagedestroy($src_im); //销毁原图
			imagecopy($img,$newimg,$left+55,$top,0,0,400,400);

			$top=$top+400+51;
			$code_top=$top;
			$font = './static/fonts/apple_lihei.otf';
			$fontSize = 18;//像素字体
			//商品标题			
			$tmpGoodName = $goods_name;
			$fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
			$goodNameArr = array();

			if($goods_info['is_invite_group']==1 || $goods_info['is_invite_cut']==1){
				if($goods_info['is_invite_group']==1){
					$tag='【拼团】';
				}else{
					$tag='【砍价】';
				}
				$tagFontColor = imagecolorallocate ($img, 255, 0, 0 );//字的RGB颜色
				imagettftext ( $img, $fontSize, 0, $left-15, $top, $tagFontColor, $font, $tag);
				$goodNameArr[] = mb_strimwidth($tmpGoodName,0,21,'','utf-8');
				imagettftext ( $img, $fontSize, 0, $left+68, $top, $fontColor, $font, $goodNameArr['0']);

				if($goods_name != $goodNameArr[0]){
					$tmpGoodName = str_replace($goodNameArr[0],'',$tmpGoodName);
					$goodNameArr[] = mb_strimwidth($tmpGoodName,0,26,'...','utf-8');
				}
				$name = $goodNameArr['1'];
				imagettftext ( $img, $fontSize, 0, $left, $top+40, $fontColor, $font, $name);							
			}else{
				$goodNameArr[] = mb_strimwidth($tmpGoodName,0,26,'','utf-8');
				if($goods_name != $goodNameArr[0]){
					$tmpGoodName = str_replace($goodNameArr[0],'',$tmpGoodName);
					$goodNameArr[] = mb_strimwidth($tmpGoodName,0,26,'...','utf-8');
				}
				$name = implode($goodNameArr,"\n");
				imagettftext ( $img, $fontSize, 0, $left, $top, $fontColor, $font, $name);
			}
			

			$top=$top+80;
			//商品描述
			$goodDesArr=array();
			$describe = remove_nl($describe, '，');
			$goodDesArr[]=mb_strimwidth($describe,0,32,'','utf-8');
			if($describe!=$goodDesArr['0']){
				$describe=str_replace($goodDesArr['0'],'',$describe);
				$goodDesArr[]=mb_strimwidth($describe,0,32,'...','utf-8');
			}
			$tdescribe=implode($goodDesArr,"\n");
			$fontSize2 = 14;//像素字体
			$fontColor2 = imagecolorallocate ($img, 153,153,153 );//字的RGB颜色
			imagettftext ( $img, $fontSize2, 0, $left, $top, $fontColor2, $font, $tdescribe);

			$top=$top+100;
			//实际价格
			$fontSize = 24;//像素字体
			$fontColor = imagecolorallocate ($img, 238, 0, 0 );//字的RGB颜色
			imagettftext ($img, $fontSize, 0, 40, $top, $fontColor, $font, C('config.Currency_symbol').$goods_price);

			if($goods_old_price>0 && $goods_old_price>$goods_price){
				//原价格
				$fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$goods_old_price);
				$top=$top-3;
				//文字水平居中实质
				$goodsPriceWidth = $fontBox[2];
				$fontSize = 14;//像素字体
				$fontColor = imagecolorallocate ($img, 153, 153, 153 );//字的RGB颜色
				imagettftext ($img, $fontSize, 0, 70 + $goodsPriceWidth, $top, $fontColor, $font, C('config.Currency_symbol').$goods_old_price);
				$fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$goods_old_price);
				$top=$top-7;
				//文字水平居中实质
				$goodsOldPriceWidth = $fontBox[2];
				$goodsOldPriceBegin = 70 + $goodsPriceWidth;
				imageline($img,$goodsOldPriceBegin,$top,$goodsOldPriceBegin + $goodsOldPriceWidth + 5,$top,$fontColor);	
			}			

			//小程序二维码
			$qrcode = $this->get_wxapp_qrcode($wxapp_path,280);
			if(strpos($qrcode,'{"') === 0){
				$qrcodeArr = json_decode($qrcode,true);
				if($qrcodeArr && $qrcodeArr['errcode']){
					$this->error_tips('小程序二维码生成失败。'.$qrcodeArr['errmsg']);
				}
			}
			if(!file_exists('./runtime/tempQrcode')){
				mkdir('./runtime/tempQrcode',0777,true);
			}
			$filePath = './runtime/tempQrcode/'.uniqid().'.jpg';
			file_put_contents($filePath,$qrcode);
			$src_im =  imagecreatefromjpeg($filePath);
			//创建新图像
			$newimg = imagecreatetruecolor(120,120);
			// 调整默认颜色
			$color = imagecolorallocate($newimg, 255, 255, 255);
			imagefill($newimg, 0, 0, $color);
			//裁剪
			imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 120,120,280,280);
			imagedestroy($src_im); //销毁原图
			imagecopy($img,$newimg,422,$code_top,0,0,120,120);

			$tip_top=$code_top+160;
			//引导识别二维码
			$fontSize = 12;//像素字体
			$fontColor = imagecolorallocate($img, 153,153,153 );//字的RGB颜色
			imagettftext($img, $fontSize, 0, 432, $tip_top, $fontColor, $font, '长按立即购买');
		
			//分享语句
			$fontSize = 14;//像素字体
			$fontColor = imagecolorallocate ($img, 255,0,0 );//字的RGB颜色
			if($goods_info['is_invite_group']==1){
				$title='邀您参与'.$goods_price.C('config.Currency_txt').'拼团';
				imagettftext($img, $fontSize, 0, 120, 100, $fontColor, $font,$title);
			}elseif($goods_info['is_invite_cut']==1){
				$title='就差你一刀了！快来一起砍价';
				imagettftext($img, $fontSize, 0, 120, 100, $fontColor, $font, $title);
			}else{
				imagettftext($img, $fontSize, 0, 120, 100, $fontColor, $font, '为您推荐');
			}
			//保存主图
			imagepng($img,$imgMallPath);
		}else{
            if(!file_exists(dirname($imgMallPath))){
                mkdir(dirname($imgMallPath),0777,true);
            }
            $img = imagecreatetruecolor(600,822);
            $white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
            imagefill ( $img ,  0 ,   0 ,  $white );
            //背景图片绘制
            $src_im = imagecreatefrompng('static/wxapp/wxapp_mall_bg.png');
            //裁剪
            // imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 600,822,600,822);
            // imagedestroy($src_im); //销毁原图
            imagecopy($img,$src_im,0,0,0,0,600,822);

            //商品图片绘制
            $info   = getimagesize($goodMainPic);
            $fun    = 'imagecreatefrom'.image_type_to_extension($info[2], false);
            $src_im =  call_user_func_array($fun,array($goodMainPic));

            //创建新图像
            $newimg = imagecreatetruecolor(400,400);
            // 调整默认颜色
            $color = imagecolorallocate($newimg, 255, 255, 255);
            imagefill($newimg, 0, 0, $color);
            //裁剪
            imagecopyresampled($newimg, $src_im, 0, 0, 0,0,400,400,$info[0],$info[1]);
            imagedestroy($src_im); //销毁原图
            imagecopy($img,$newimg,$left+55,$top,0,0,400,400);

            $top=$top+400+51;
            $code_top=$top;
            $font = './static/fonts/apple_lihei.otf';
            $fontSize = 18;//像素字体
            //商品标题
            $tmpGoodName = $goods_name;
            $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
            $goodNameArr = array();

            if($goods_info['is_invite_group']==1 || $goods_info['is_invite_cut']==1){
                if($goods_info['is_invite_group']==1){
                    $tag='【拼团】';
                }else{
                    $tag='【砍价】';
                }
                $tagFontColor = imagecolorallocate ($img, 255, 0, 0 );//字的RGB颜色
                imagettftext ( $img, $fontSize, 0, $left-15, $top, $tagFontColor, $font, $tag);
                $goodNameArr[] = mb_strimwidth($tmpGoodName,0,21,'','utf-8');
                imagettftext ( $img, $fontSize, 0, $left+68, $top, $fontColor, $font, $goodNameArr['0']);

                if($goods_name != $goodNameArr[0]){
                    $tmpGoodName = str_replace($goodNameArr[0],'',$tmpGoodName);
                    $goodNameArr[] = mb_strimwidth($tmpGoodName,0,26,'...','utf-8');
                }
                $name = $goodNameArr['1'];
                imagettftext ( $img, $fontSize, 0, $left, $top+40, $fontColor, $font, $name);
            }else{
                $goodNameArr[] = mb_strimwidth($tmpGoodName,0,26,'','utf-8');
                if($goods_name != $goodNameArr[0]){
                    $tmpGoodName = str_replace($goodNameArr[0],'',$tmpGoodName);
                    $goodNameArr[] = mb_strimwidth($tmpGoodName,0,26,'...','utf-8');
                }
                $name = implode($goodNameArr,"\n");
                imagettftext ( $img, $fontSize, 0, $left, $top, $fontColor, $font, $name);
            }


            $top=$top+80;
            //商品描述
            $goodDesArr=array();
            $goodDesArr[]=mb_strimwidth($describe,0,32,'','utf-8');
            if($describe!=$goodDesArr['0']){
                $describe=str_replace($goodDesArr['0'],'',$describe);
                $goodDesArr[]=mb_strimwidth($describe,0,32,'...','utf-8');
            }
            $tdescribe=implode($goodDesArr,"\n");
            $fontSize2 = 14;//像素字体
            $fontColor2 = imagecolorallocate ($img, 153,153,153 );//字的RGB颜色
            imagettftext ( $img, $fontSize2, 0, $left, $top, $fontColor2, $font, $tdescribe);

            $top=$top+100;
            //实际价格
            $fontSize = 24;//像素字体
            $fontColor = imagecolorallocate ($img, 238, 0, 0 );//字的RGB颜色
            imagettftext ($img, $fontSize, 0, 40, $top, $fontColor, $font, C('config.Currency_symbol').$goods_price);

            if($goods_old_price>0 && $goods_old_price>$goods_price){
                //原价格
                $fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$goods_old_price);
                $top=$top-3;
                //文字水平居中实质
                $goodsPriceWidth = $fontBox[2];
                $fontSize = 14;//像素字体
                $fontColor = imagecolorallocate ($img, 153, 153, 153 );//字的RGB颜色
                imagettftext ($img, $fontSize, 0, 70 + $goodsPriceWidth, $top, $fontColor, $font, C('config.Currency_symbol').$goods_old_price);
                $fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$goods_old_price);
                $top=$top-7;
                //文字水平居中实质
                $goodsOldPriceWidth = $fontBox[2];
                $goodsOldPriceBegin = 70 + $goodsPriceWidth;
                imageline($img,$goodsOldPriceBegin,$top,$goodsOldPriceBegin + $goodsOldPriceWidth + 5,$top,$fontColor);
            }

            //小程序二维码
            $qrcode = $this->get_wxapp_qrcode($wxapp_path,280);
            if(strpos($qrcode,'{"') === 0){
                $qrcodeArr = json_decode($qrcode,true);
                if($qrcodeArr && $qrcodeArr['errcode']){
                    $this->error_tips('小程序二维码生成失败。'.$qrcodeArr['errmsg']);
                }
            }
            if(!file_exists('./runtime/tempQrcode')){
                mkdir('./runtime/tempQrcode',0777,true);
            }
            $filePath = './runtime/tempQrcode/'.uniqid().'.jpg';
            file_put_contents($filePath,$qrcode);
            $src_im =  imagecreatefromjpeg($filePath);
            //创建新图像
            $newimg = imagecreatetruecolor(120,120);
            // 调整默认颜色
            $color = imagecolorallocate($newimg, 255, 255, 255);
            imagefill($newimg, 0, 0, $color);
            //裁剪
            imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 120,120,280,280);
            imagedestroy($src_im); //销毁原图
            imagecopy($img,$newimg,422,$code_top,0,0,120,120);

            $tip_top=$code_top+160;
            //引导识别二维码
            $fontSize = 12;//像素字体
            $fontColor = imagecolorallocate($img, 153,153,153 );//字的RGB颜色
            imagettftext($img, $fontSize, 0, 432, $tip_top, $fontColor, $font, '长按立即购买');

            //分享语句
            $fontSize = 14;//像素字体
            $fontColor = imagecolorallocate ($img, 255,0,0 );//字的RGB颜色
            if($goods_info['is_invite_group']==1){
                $title='邀您参与'.$goods_price.C('config.Currency_txt').'拼团';
                imagettftext($img, $fontSize, 0, 120, 100, $fontColor, $font,$title);
            }elseif($goods_info['is_invite_cut']==1){
                $title='就差你一刀了！快来一起砍价';
                imagettftext($img, $fontSize, 0, 120, 100, $fontColor, $font, $title);
            }else{
                imagettftext($img, $fontSize, 0, 120, 100, $fontColor, $font, '为您推荐');
            }
            //保存主图
            imagepng($img,$imgMallPath);
        }

		return $imgMallPath;
	}

	//获取小程序二维码
	protected function get_wxapp_qrcode($path,$width){
		//兼容商家小程序
		if($_SESSION['wxapp_own_mer_id']){
			$wxapp_access_token = D('Access_token_wxapp_merchant_expires')->get_access_token($_SESSION['wxapp_own_mer_id']);
		}else{
			$wxapp_access_token = D('Access_token_wxapp_expires')->get_access_token();
		}
		
		if($wxapp_access_token['errcode']){
			$this->error_tips('小程序二维码生成失败。'.$wxapp_access_token['errmsg']);
		}else{
			$wxapp_access_token = $wxapp_access_token['access_token'];
		}
		
		import('ORG.Net.Http');
		$http = new Http();
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$wxapp_access_token;
		$postData = array(
			'path'=>$path,
			'width'=>$width
		);
		$qrcode = $http->curlPostOwn($url,json_encode($postData));

		if(is_null(json_decode($qrcode))){
			return $qrcode;
		}
		
		//若返回报错，则尝试生成无数量限制的
		$tmpUrlArr = parse_url($path);
		$urlParam = convertUrlQuery($tmpUrlArr['query']);
		
		if($urlParam['redirect'] != 'webview'){
			return [];
		}
		
		$page = 'pages/webview/webview';
		$scene = urldecode($urlParam['webview_url']);
		$url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$wxapp_access_token;
		$postData = array(
			'scene'=> $scene,
			'page'=> $page,
			'width'=> $width
		);
		$qrcode = $http->curlPostOwn($url, json_encode($postData));
		return $qrcode;
	}

	//获取时间差
	public function get_distance_time(){
		$end_time=I('end_time','','trim');
		$now_time=$_SERVER['REQUEST_TIME'];
		echo $end_time-$now_time;
	}

	//平台E卡列表页
	public function ecard_index(){
		//轮播图
		$banner_list = D('Adver')->get_adver_by_key('ecard_adver',5);
		$this->assign('banner_list',$banner_list);
		$where=array();
		$where['is_del']=0;
		$where['status']=1;
		$where['bind_goods_id']=array('gt',0);
		$list=M('ecard')->where($where)->select();
		foreach ($list as $k => $v) {
			$v['card_bg']=replace_file_domain($v['card_bg']);
			$list[$k]=$v;
		}
		$this->assign('list',$list);
		$this->display();
	}

	//周期购延期
	public function periodic_delay(){
		$model = D('Mall_periodic_deliver');
		$id = $_POST['id'];
		$type = $_POST['type'];
		$result = $model->delay($id,$type);
		echo json_encode($result);
	}
	
	// 上传图片
	public function ajax_upload_file(){
        if(!$_FILES){
            exit(json_encode(array('error'=>1,'msg'=>'没有选择图片')));
        }
        if($_FILES['imgFile']['error'] == 4){
            exit(json_encode(array('error'=>1,'msg'=>'没有选择图片')));
        }
        if($_FILES['imgFile']['error'] == 3){
            exit(json_encode(array('error'=>1,'msg'=>'文件只有部分被上传')));
        }
        $upload_file = D('Image')->handle($this->user_session['uid'], 'mall_provider', 0, array('size' => 20), false);
        if ($upload_file['error'] == 1){
            exit(json_encode(array('error'=>1,'msg'=>'上传失败，' .$upload_file['message']. '请重试！')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'上传成功','url'=>$upload_file['url']['imgFile'])));
        }

	}
	
	public function authentication(){
		if(empty($this->user_session)){
			$referer = urlencode(U('Mall/authentication'));//$_SERVER['REQUEST_URI']
			$this->success_tips('请先登录', U('Login/index',array('referer'=>$referer)));
		}
        // echo $this->config['authentication_price'];
		$where = array();
		$where['is_del'] = 0;
        $authentication_info = D('Mall_authentication')->where(array('uid'=>$this->user_session['uid']))->find();
        if($authentication_info){

            $authentication_info['authentication_field'] = unserialize($authentication_info['authentication_field']);
            $this->assign('authentication_info',$authentication_info);
            if($authentication_info['authentication_status'] == 3){
                $authentication_config_list = D('Mall_authentication_config')->where($where)->order('type asc')->select();
				if(empty($authentication_config_list)){
					$this->error_tips('认证字段为空，请联系系统管理员');
				}
                foreach ($authentication_config_list as $key => $value) {
                    if($value['type'] == 1){
                        $authentication_wenben[] = $value;
                    }else{
                        $authentication_tupian[] = $value;
                    }
                }
                $this->assign('authentication_wenben',$authentication_wenben);
                $this->assign('authentication_tupian',$authentication_tupian);

                $this->display('authentication_edit');
            }else{
                $this->display('authentication_index');
            }
        }else{
            $authentication_config_list = D('Mall_authentication_config')->where($where)->order('type asc')->select();
			if(empty($authentication_config_list)){
				$this->error_tips('认证字段为空，请联系系统管理员');
			}
            foreach ($authentication_config_list as $key => $value) {
                if($value['type'] == 1){
                    $authentication_wenben[] = $value;
                }else{
                    $authentication_tupian[] = $value;
                }
            }
            $this->assign('authentication_wenben',$authentication_wenben);
            $this->assign('authentication_tupian',$authentication_tupian);
            $this->display();
        }

    }

    public function authentication_data(){
		
        $data['uid'] = $this->user_session['uid'];
        $data['authentication_status'] = 1;
        $data['authentication_time'] = time();
        $authentication_id = $_POST['authentication_id'];
        unset($_POST['imgFile']);
        unset($_POST['authentication_id']);
        foreach ($_POST as $key => $value) {
            if(!$value['value']){
                $this->error_tips($value['title'].'不可以为空！');
            }
        }
        $data['authentication_field'] = serialize($_POST);

        if($authentication_id){
            $res = D('Mall_authentication')->where(array('authentication_id'=>$authentication_id))->data($data)->save();
            if($res){
                //扣款写记录
                $this->success_tips('提交成功！', U('Mall/authentication'));
            }else{
                $this->error_tips('提交失败请重试');
            }
        }else{
            $res = D('Mall_authentication')->data($data)->add();
            if($res){
                $this->success_tips('提交成功！', U('Mall/authentication'));
            }else{
                $this->error_tips('提交失败请重试');
            }
        }

    }

	//底部导航是否显示在侧边
	public function check_footer_position(){
		 // 平台app首页的自定义底部导航为商城首页链接时，则将商城首页底部导航改成右侧悬浮
        // 查询商城首页是否在平台首页导航显示
        $menu_category = M('Home_menu_category')->field('cat_id')->where(array('cat_key' => 'app_footer'))->find();
        if ($menu_category) {
            $footer_menu_list = M('Home_menu')->where(array('status' => '1', 'cat_id' => $menu_category['cat_id']))->order('`sort` DESC,`id` ASC')->limit(5)->select();
            foreach ($footer_menu_list as $key => $value) {
                if(stristr($value['url'],'c=Mall') && stristr($value['url'],'a=index') && count($footer_menu_list)>1 ){
                    $this->assign('is_right', 1);
                }
            }
        }
	}

	public function cancel_apply(){
		$order_id = intval($_GET['order_id']);
		$db = M('Shop_order');
		$where = ['order_id'=>$order_id];
		$order = $db->where($where)->find();
		if(empty($order)){
			exit(json_encode(array('errcode' => 1, 'msg' => '订单不存在！')));
		}
		if($order['status']<>11){
			exit(json_encode(array('errcode' => 1, 'msg' => '订单状态不正确！')));
		}
		$r = $db->where($where)->data(['status'=>0,'order_status'=>1])->save();
		if($r){
			$count = D('Shop_order_log')->where(['order_id' => $order_id, 'status' => 42])->count();
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 42,'note'=>$count+1));
			exit(json_encode(array('errcode' => 0, 'msg' => '撤回成功！', 'url' => U('Mall/status', array('order_id' => $order_id)))));
		}else{
			exit(json_encode(array('errcode' => 1, 'msg' => '撤回失败！')));
		}
	}

	public function express_new(){
		$express_code = D('Express')->where(['code' => $_GET['type']])->getField('kuaidiniao_code');
		
		$kdniao = new kdniao();
		
		$result = $kdniao->getOrderTracesByJson($express_code, $_GET['postid']);
		if(!$result['Success'] && $result['Reason']){
			$this->error_tips('快递鸟返回错误：' . $result['Reason']);
		}
		
		$this->assign('result', $result);
		$this->display();
	}
	

}
?>