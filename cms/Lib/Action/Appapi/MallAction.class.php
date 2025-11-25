<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/11/19.
 * Time: 10:12.
 */

/**
 * Class 商城模块
 */
class MallAction extends BaseAction{

    public function _initialize()
    {
        parent::_initialize();
    }

    //商城店铺主页
    public function store(){
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : intval($_GET['store_id']);
        if(!$store_id){
            $this->returnCode(1, null, '店铺不存在！');
        }
        $base_info = $this->get_store_base_info($store_id);
        $return = $base_info;//返回数据

        $now_store = D('Merchant_store')->get_store_by_storeId($store_id);

		if($this->_uid){
            $this->save_user_city($now_store['city_id'], $now_store['area_id']);
        }

        //店铺经纬度
        import('@.ORG.longlat');
        $longlat = new longlat();
        $tmpGcj02 = $longlat->baiduToGcj02($now_store['lat'],$now_store['long']);
        $now_store['gcj02_long'] = $tmpGcj02['lng'];
        $now_store['gcj02_lat'] = $tmpGcj02['lat'];
        $return['gcj02_long'] = $tmpGcj02['lng'];
        $return['gcj02_lat'] = $tmpGcj02['lat'];
        $return['logo'] = $now_store['logo'];
        if ($return['logo']) {
            $return['logo'] = thumb_img($return['logo'], 120, 120, 'fill');
        }

        //店铺自定义主页
        $return['set_diypage'] = false;
        $return['diypage_bgcolor'] = '';
        $return['diypage_content'] = array();
        $diy_where = array();
        $diy_where['store_id'] = $store_id;
        $diy_where['is_home'] = 1;
        $diy_where['is_remove'] = 0;
        $diy_info = M('Merchant_store_diypage')->where($diy_where)->find();
        if($diy_info){
            $return['set_diypage'] = true;
            $return['diypage_bgcolor'] = $diy_info['bgcolor'];
            $return['diypage_content'] = $this->get_store_diypage_page($diy_info['page_id'],$now_store,$diy_info);
        }
		
		
		$return['wxapp_share_title'] = $now_store['wxapp_share_title'] ? : '';
		$return['wxapp_share_pic'] = $now_store['wxapp_share_pic'] ? thumb($now_store['wxapp_share_pic'], 500, 400 , 'fill') : '';


        $this->returnCode(0,$return);
    }

    //店铺收藏
    public function store_collection(){
        $info = D('Merchant_user_relation')->where(array('store_id'=>intval($_POST['store_id']),'uid'=>$this->_uid,'type'=>'mall'))->find();
        if($info){
            if(D('Merchant_user_relation')->where(array('store_id'=>intval($_POST['store_id']),'uid'=>$this->_uid,'type'=>'mall'))->delete()){
                $this->returnCodeOk(L_('取消收藏成功'));
            }
        }
        $res = D('Merchant_user_relation')->data(array('store_id'=>$_POST['store_id'],'uid'=>$this->_uid,'type'=>'mall'))->add();
        if($res){
            $this->returnCodeOk(L_('收藏成功'));
        }else{
            $this->returnCodeError(L_('收藏失败请重试'));
        }
    }

    public function get_kefu($now_store){
        $uid = $this->_uid;
        if(!$uid){
            return -1;
        }
        //调整客服判断
        if (C('IM_JMESSAGE_OPEN')) {
            $whiteUid = C('IM_WHITE_UID');
            $kf = M('Merchant_store_kefu')->where(['store_id' => $now_store['store_id']])->find();
            
            if ($kf && $uid > 0 && (empty($whiteUid) || in_array($uid, $whiteUid))) {
                $kf_url = build_im_chat_url('user_' . $uid, $kf['username'], 'user2store');
                return $kf_url;
            } else {
                if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_store['mer_id']))->select()) {
                    $key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
                    $kf_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key . '#serviceList_' . $now_store['mer_id'];
                    return $kf_url;
                }
            }
        } else {
            if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_store['mer_id']))->select()) {
                $key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
                $kf_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key . '#serviceList_' . $now_store['mer_id'];
                return $kf_url;
            }
        }
    }

    /**
     * @desc获取店铺基础信息
     * @param $store_id
     * @return array
     */
    private function get_store_base_info($store_id){
        $where = array('store_id' => $store_id);
        $now_store = D('Merchant_store')->field(true)->where($where)->find();
        $now_shop  = D('Merchant_store_shop')->field(true)->where($where)->find();
        if (empty($now_shop) || empty($now_store)) {
            $this->returnCode(1, null, L_('该X1暂未完善信息，无法使用',$this->config['shop_alias_name']));
        }
        $store_collect = false;
        $_collect = D('Merchant_user_relation')->get_mall_collect($this->_uid, $store_id);
        if($_collect){
            $store_collect = true;
        }
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($now_store['pic_info']);
        $return = array();
        $return['store_id']   = $store_id;
        $return['name']       = $now_store['name'];
        $return['score_mean'] = (empty($now_shop['score_mean'])||$now_shop['score_mean']=='0.00')?5:$now_shop['score_mean'];//店铺星级
        $return['is_own']     = $now_store['mer_id']==$this->config['kefu_mer_id']?true:false;//是否自营
        $return['fans']       = $this->get_merchant_fans($now_store['mer_id']);//粉丝数
        $return['image']      = $images[0]?$images[0]:'';
		
		if (!empty($now_shop['background'])) {
			$image_tmp = explode(',', $now_shop['background']);
			$return['image'] = file_domain() . '/upload/background/' . $image_tmp[0] . '/' . $image_tmp['1'];
		}
		
        $return['phone']      = $now_store['phone'];
        $return['store_collect'] = $store_collect;
        $kefu = $this->get_kefu($now_store);
        $return['kefu']       = $kefu?$kefu:'';
        return $return;
    }

    /**
     * @desc获取店铺自定义页面的信息
     * @param $page_id
     * @return array
     */
    private function get_store_diypage_page($page_id,$now_store,$now_page){
        $db = M('Merchant_store_diypage_field');
        $where = array();
        $where['page_id'] = $page_id;
        $field_list = $db->where($where)->order('field_id asc')->select();
        $image_ad_index = 0;
        $show_sort = D('Shop_goods_sort')->get_show_sort($store_id);
        foreach($field_list as &$value){
            $value['content'] = unserialize($value['content']);
            if($value['field_type'] == 'tpl_shop'){
                $value['content']['shop_head_bg_img'] = $this->replaceImg($value['content']['shop_head_bg_img']);
                $value['content']['shop_head_logo_img'] = $this->replaceImg($now_store['all_pic'][0]);
                $value['content']['shop_url'] = $this->config['site_url'].'/wap.php?c=Mall&a=store&store_id='.$now_store['store_id'];
                $value['content']['card_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_card&mer_id='.$now_page['mer_id'];
                $value['content']['order_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_order&mer_id='.$now_page['mer_id'];
            }
            if($value['field_type'] == 'notice'){
                $value['content']['length'] = $this->dstrlen($value['content']['content'])/2;
            }
            if($value['field_type'] == 'rich_text'){
                $value['content']['content'] = str_replace('<img src="/upload/','<img src="'.file_domain().'/upload/',$value['content']['content']);
            }
            if($value['field_type'] == 'image_nav'){
                foreach($value['content'] as $k=>$v){
                    $value['content'][$k]['image'] = $this->replaceImg($v['image']);
                }
            }
            if($value['field_type'] == 'image_ad'){
                foreach($value['content']['nav_list'] as $k=>$v){
                    $value['content']['nav_list'][$k]['index'] = $image_ad_index;
                    $value['content']['nav_list'][$k]['image'] = $this->replaceImg($v['image']);
                    $image_ad_index++;
                }
                $value['content']['nav_list'] = array_values($value['content']['nav_list']);
            }
            if($value['field_type'] == 'coupons'){
                $tmpArr = [];
                foreach($value['content']['coupon_arr'] as $k=>$v){
                    $tmpArr[] = $v['id'];
                }
                $value['content']['coupon_arr'] = D('Card_new_coupon')->get_coupon_list_by_ids($tmpArr,true);
                $value['content']['coupon_arr'] = array_values($value['content']['coupon_arr']);
            }
            if($value['field_type'] == 'map'){
                $value['content']['lng'] = $now_store['gcj02_long'];
                $value['content']['lat'] = $now_store['gcj02_lat'];
                $value['content']['markers'] = array(
                    array(
                        'latitude' => $value['content']['lat'],
                        'longitude' => $value['content']['lng'],
                        'title' => $now_store['name'],
                        'iconPath' => '../../images/my_pos.png',
                        'callout' => array(
                            'content' => $now_store['name'],
                            'color' => '#000000',
                            'fontSize' => 14,
                            'borderRadius' => 8,
                            'bgColor' => '#FFFFFF',
                            'padding' => 14,
                            'display' => 'ALWAYS',
                        ),
                    ),
                );
            }
            if($value['field_type'] == 'goods'){
                if($value['content']['size'] == 1 && $value['content']['size_type'] == 2){
                    $value['content']['show_title'] = 0;
                }
                if(!empty($value['content']['goods'])){
                    foreach ($value['content']['goods'] as $k=>$v){
                        $product = D('Shop_goods')->get_goods_by_id($v['id']);
                        if(empty($product) || !in_array($product['sort_id'],$show_sort)){
                            unset($value['content']['goods'][$k]);
                        }else{
                            $value['content']['goods'][$k]['price'] = $product['price'];
                            $value['content']['goods'][$k]['title'] = $product['name'];
                            $value['content']['goods'][$k]['image'] = $this->replaceImg($value['content']['goods'][$k]['image']);
                            $value['content']['goods'][$k]['url'] = $this->config['site_url'] . '/wap.php?c=Mall&a=detail&goods_id='.$v['id'].'&otherLink=1';
                        }
                    }
                }
            }
        }
        return $field_list;
    }

    /**
     * @desc获取商户粉丝数
     * @param $mer_id
     * @return int
     */
    private function get_merchant_fans($mer_id){
        $where = " `m`.`mer_id`='$mer_id'";
        $sql = "SELECT COUNT(1) as count, from_merchant FROM " . C('DB_PREFIX') . "merchant_user_relation AS m LEFT JOIN " . C('DB_PREFIX') . "user AS u ON `m`.`openid`=`u`.`openid` WHERE `m`.`mer_id`='$mer_id'  AND `m`.`openid`!=''  AND `u`.`openid`!=''";
        $mode = new Model();
        $count_list = $mode->query($sql);
        return $count_list[0]['count']?$count_list[0]['count']:0;
    }

    private function replaceImg($url){
        $url = str_replace('http://','https://',$url);
        if(substr($url,0,8) == '/upload/'){
            $url = file_domain().$url;
        }else if(substr($url,0,9) == './static/'){
            $url = $this->config['site_url'].ltrim($url,'.');
        }else{
            $url = replace_file_domain($url);
        }

        return $url;
    }

    public static function dstrlen($string){
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }
        }
        return $noc;
    }
	
    //获取店铺分类，只需要前两级分类
	public function get_sorts_list(){
		$store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : intval($_GET['store_id']);
        $model = D('Shop_goods_sort');
        $field = 'sort_id,sort_name,fid,level';
        $list  = $model->get_level_sort($store_id,$field, ['is_mall_config' => 1]);
		
		foreach($list as $key=>$value){
			$list[$key]['sort_name'] = htmlspecialchars_decode($value['sort_name']);
		}
		
        $this->returnCode(0,$list);
	}

    //商城店铺商品列表页
    public function get_store_goods_list(){
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : intval($_GET['store_id']);//店铺id
        if(!$store_id){
            $this->returnCode(1, null, '店铺不存在！');
        }

        $model = M('Shop_goods');
        $where = array();
        $where['status'] = 1;
        $where['cat_fid'] = array('gt',0);
        $where['store_id'] = $store_id;

        $order = isset($_POST['order']) ? intval($_POST['order']) :0;//排序规则：0综合，1销量倒序，2销量正序，3价格倒序，4价格正序
        $goods_order = $this->get_order($order);

        $sort_id    = isset($_POST['sort_id']) ? intval($_POST['sort_id']) :'';//分类id
        $sort_level = isset($_POST['sort_level']) ? intval($_POST['sort_level']) :'';//分类等级
        if($sort_id&&$sort_level){
            $sort = $this->get_last_sort($sort_id,$sort_level);
            if(is_array($sort)){
                $where['sort_id'] = array('in',$sort);
            }else{
                $where['sort_id'] = $sort;
            }
        }else{
			$show_sort = D('Shop_goods_sort')->get_show_sort($store_id);
            //echo M()->getlastSql();exit;
			$where['sort_id'] = array('in',$show_sort);
		}

        $keyword = isset($_POST['keyword']) ? $_POST['keyword'] :'';
        if($keyword){
            $where['name'] = array('like','%'.$keyword.'%');
        }

        $total = $model->Where($where)->count();

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $pagesize = isset($_POST['page_size']) ? intval($_POST['page_size']) : 6;
        $totalPage = ceil($total / $pagesize);
        $start = $pagesize * ($page - 1);

        $goods_list = $model->Where($where)->field(true)->order($goods_order)->limit($start,$pagesize)->select();

        $goods_list = $this->deal_goods_list($goods_list,$store_id);
	    $addrs = $this->getUserMsg($this->_uid);
        if (empty($addrs)) {
            $arr['province_id'] = '';
            $arr['city_id']     = C('config.now_city');
            $arr['area_id']     = '';
        } else {
            $arr['province_id'] = $addrs['province_id'];
            $arr['city_id']     = $addrs['city_id'];
            $arr['area_id']     = $addrs['area_id'];
        }
        $area_id=D('Shop_goods')->getAreaId($arr);
        if(!empty($area_id)){
            if(!empty($goods_list)){
                foreach ($goods_list as $k => $v){
                    if($v['use_area']==1){
                        $area_good_list = D('Shop_goods_area')->where(array('did'=>$v['goods_id'],'store_id'=>array('exp','IS NOT NULL')))->getField('aid',true);
                        if(empty($area_good_list)){
                            continue;
                        }
                        fdump($area_id,"AreaId2021",1);
                        fdump($area_good_list,"AreaId2021",1);
                        //取交集,有则过滤商品
                        $arrs=array_intersect($area_id, $area_good_list);
                        if(empty($arrs)){
                            unset($goods_list[$k]);
                            continue;
                        }
                    }
                }
                $goods_list=array_values($goods_list);
            }
        }

        foreach ($goods_list as $k => $v) {
            $goods_list[$k]['seckill_discount'] = $v['seckill_discount'] > 0 ? $v['seckill_discount'] : 0;
        }
        $return['next_page'] = $totalPage > $page ? intval($page + 1) : 0;
        $return['total_page'] = $totalPage;
        $return['total'] = $total;
        $return['goods_list'] = $goods_list;

        $this->returnCode(0,$return);

    }
    public function getUserMsg($uid){
        $user = D('User')->field(true)->where(array('uid'=>$uid))->find();
        return $user;
    }
    /*
     * 获得商品及规格详情
     * author 衡婷妹
     * date 20210119
    */
    public function getGoodsDetail(){

        $goods_id = intval($_POST['goods_id']);//商品id

        if(empty($goods_id)){
            $this->returnCodeError(L_('缺少参数'));
        }
        

        $returnArr = [];


        $database_shop_goods = D('Shop_goods');
        $now_goods = $database_shop_goods->get_goods_by_id($goods_id);
        if(empty($now_goods)){
            $this->returnCodeError(L_('商品不存在！'));
        }

        if($now_goods['jd_sku_id'] != 0){
            import('ORG.Net.Http');
            $url_state = 'https://bizapi.jd.com/api/product/skuState';
            $request_state['token'] = $this->config['jd_access_token'];
            $request_state['sku'] = $now_goods['jd_sku_id'];
            $return1 = http::curlPost($url_state,$request_state);
            if($return1['result'][0]['state']==0){
                if($return1['resultCode']==2007){
                    $this->returnCodeError($return1['resultMessage'] . '！错误码：2007');
                    $this->error_tips($return1['resultMessage'] . '！错误码：2007');
                }
                M('Shop_goods')->where(array('goods_id'=>$now_goods['goods_id']))->delete();
                $this->returnCodeError(L_('当前商品已下架！'));
            }
            $url_check = 'https://bizapi.jd.com/api/product/check';
            $request_c['token'] = $this->config['jd_access_token'];
            $request_c['skuIds'] = $now_goods['jd_sku_id'];
            $return2 = http::curlPost($url_check,$request_c);
            $this->assign('is7ToReturn',$return2['result'][0]['is7ToReturn']);
            if($return2['result'][0]['saleState']==0){
                $this->returnCodeError(L_('当前商品不可售！'));
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
                M(_view('Shop_goods'))->where(array('goods_id'=>$now_goods['goods_id']))->save($goods);
                $now_goods = $database_shop_goods->get_goods_by_id($goods_id);
            }else{
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
                    M(_view('Shop_goods'))->where(array('goods_id'=>$now_goods['goods_id']))->save(array('price'=>$now_goods['cost_price']));
                    $now_goods = $database_shop_goods->get_goods_by_id($goods_id);
                }
            }
        }

        //如果设置了最小起购，限购就无效
        if ($now_goods['min_num'] > 1) {
            $now_goods['max_num'] = 0;
        }
        
        // 店铺id
        $store_id = $now_goods['store_id'];
        $where = array('store_id' => $store_id);
        $now_store = D(_view('Merchant_store'))->field(true)->where($where)->find();
      
        // 商城店铺
        $now_shop = D(_view('Merchant_store_shop'))->field(true)->where($where)->find();
        if (empty($now_shop) || empty($now_store)) {
            $this->returnCodeError(L_('抱歉，该商品所属店铺没有完善店铺信息'));
        }elseif($now_shop['store_theme']!=1){
            $this->returnCodeError(L_('抱歉，该商品所属店铺商城已关闭！'));
        }

        $goods_cart_status=1;
        if (!D('Shop_goods')->isShow($goods_id, 1 | 4)) { 
            $this->returnCodeError(L_('当前商品下架或不在显示时间段'));
        }

        // 商品规格列表
        if($now_goods['spec_list']){
            foreach ($now_goods['spec_list'] as &$_spec) {
                $_spec['list'] = array_values($_spec['list']);
            }

        }

        // 商品属性列表
        $now_goods['properties_list'] = array_values($now_goods['properties_list']);

        // 规格组合
        $list = $now_goods['list'];
        foreach ($list as $k => $v) {
            $v['reg_img'] = is_array($v['reg_img']) ? $v['reg_img']['s_image'] : ($v['reg_img'] ?  : $now_goods['image']);
            $list[$k]=$v;
        }

        $now_goods['list'] = array_values($list);

        //秒杀校验
        if($now_goods['is_seckill_price']){
            $act_info = D('Time_limited_discount_goods')->get_in_progress_record($now_goods['goods_id']);
            $ftime=strtotime($act_info['end_date'].' '.$act_info['end_time']);
            $now_goods['price']=$act_info['new_limit_price'];
            $now_goods['time_clock']=$ftime-time();
        }


        //商品是否参加团购接龙活动和当前活动价格
        $now_goods['is_solitaire'] = 0;
        $solitaire = (new Team_solitaire_activityModel())->getCurrentActivityByGoodsId($goods_id);
        $now_goods['origin_price'] = $now_goods['price'];
        if ($solitaire) {
            $now_goods['is_solitaire'] = 1;
            $now_goods['price'] = $solitaire['current_floor_price'];
        }
        
        $returnArr['goods_detail'] = $now_goods;
        $returnArr['operate_type'] = 'add_cart';
        $returnArr['operate_url'] = C('config.site_url').'/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$now_goods['goods_id'];

        // 查看商品是否参加拼团、砍价
        $act_info = D('Shop_goods')->get_good_act_info($goods_id);
        if($act_info){
            $returnArr['operate_type'] = 'detail';//跳转商品详情
        }

        $this->returnCode(0,$returnArr);
    }

    /*
     * 保存购物车
     * author 衡婷妹
     * date 20210119
    */
    public function saveCart(){
        if($_POST['store_id']){
            $condition_delete['shop_id'] = $_POST['store_id'];
            if ($this->_uid) {
                $where['uid'] = $this->_uid;
                $where['cart_cookie'] = $_POST['cart_cookie'];
                $where['_logic'] = 'or';
                $condition_delete['_complex'] = $where;
            }else{
                $condition_delete['cart_cookie'] = $_POST['cart_cookie'];
            }
            M('Mall_cart')->where($condition_delete)->delete();

            // 更新商城购物车数量缓存
            D('Mall_cart')->saveCookieData($this->_uid);
            $this->returnCode(0,L_('ok'));
        }

        if($_POST['cookieProductCart']){
            $cookieProductCart = $_POST['cookieProductCart'];
            // 删除原来的购物车
            foreach ($cookieProductCart as $value) {
                $condition_delete['shop_id'] = $value['store_id'];
                if ($this->_uid) {
                    $where['uid'] = $this->_uid;
                    $where['cart_cookie'] = $_POST['cart_cookie'];
                    $where['_logic'] = 'or';
                    $condition_delete['_complex'] = $where;
                } else {
                    $condition_delete['cart_cookie'] = $_POST['cart_cookie'];
                }
                M('Mall_cart')->where($condition_delete)->delete();
            }

            // 保存新的购物车
            foreach($cookieProductCart as $value){
                $data = $value;
                $data['productParam'] = $data['productParam'] ? serialize($data['productParam']) : array();
                $data['shop_id'] = $data['shop_id'] ? $data['shop_id'] :$data['store_id'];
                $data['cart_cookie'] = $_POST['cart_cookie'] ?? '';
                $data['uid'] = intval($this->_uid);
                M('Mall_cart')->data($data)->add();
            }
            // 更新商城购物车数量缓存
            D('Mall_cart')->saveCookieData($this->_uid);
            $this->returnCode(0,L_('ok'));
        }else{
            // 更新商城购物车数量缓存
            D('Mall_cart')->saveCookieData($this->_uid);
            $this->returnCode(0,L_('ok'));
        }
    }
    /*
     * 获得购物车详情
     * author 衡婷妹
     * date 20210119
    */
    public function getCartList(){
        if($this->_uid){
            $where['uid'] = $this->_uid;
            $where['cart_cookie'] = $_POST['cart_cookie'];
            $where['_logic'] = 'or';
            $condition['_complex'] = $where;
        }else{
            $condition['cart_cookie'] = $_POST['cart_cookie'];
        }

        $field = 'c.*,g.price_type,g.account_money,g.account_score';
        $cartList = M('Mall_cart')->alias('c')->join('Left join '.C('DB_PREFIX')._view('shop_goods').' as g on g.goods_id = c.productId')->field($field)->where($condition)->order('`id` ASC')->select();
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

        // 获得购物车数量 
        $store_id = $_POST['store_id'];
        if($store_id){
            $condition['shop_id'] = $store_id;
        }
        $count = M('Mall_cart')->field('sum(count) as count')->where($condition)->find();
        $returnArr = [];
        $returnArr['cart_list'] = $cartList;
        $returnArr['cart_num'] = $count['count'];
        $returnArr['cart_url'] = C('config.site_url').'/wap.php?g=Wap&c=Mall&a=cart&store_id='.$store_id;
        $this->returnCode(0,$returnArr);
    }
    private function deal_goods_list($list, $store_id = 0){
        $goods_list = array();
        $goods_image_class = new goods_image();

        $goodsMod = new Shop_goodsModel();
        //当前用户会员等级
        $levelDiscount = (new Merchant_store_shopModel())->getVipLevelDiscount($this->_uid, $store_id, 'mall');

        foreach ($list as $row) {
			$row['sell_count'] += $row['mall_virtual_sales'];
            $row['price'] = floatval($row['price']);
            $row['is_seckill_price'] = false;
            $time_limited_discount_mod = D('Time_limited_discount_goods');
            $seckill_record = $time_limited_discount_mod->get_in_progress_record($row['goods_id']);
            if ($seckill_record&&($seckill_record['stock']>0 || $seckill_record['stock'] == -1)) {
                //存在限时优惠
                $row['is_seckill_price'] = true;
                $row['price'] = $seckill_record['is_spec'] == 1 ? $row['o_price'] : getFormatNumber($seckill_record['limit_price']);
            }

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
            
            $row['name'] = html_entity_decode($row['name'], ENT_QUOTES);
            $row['product_reply'] = $row['reply_count'];
            $row['stock'] = $row['stock_num'];
            $row['o_price'] = floatval($row['price']);
            $row['old_price'] = floatval($row['price']);
            if ($seckill_record&&($seckill_record['stock']>0 || $seckill_record['stock'] == -1)) {
                //$row['price'] = floatval($row['seckill_price']);
                $row['is_seckill_price'] = true;
                $row['seckill_discount'] = round($row['price']/$row['o_price']*10,1);
            } else {
                $row['price'] = floatval($row['price']);
            }
            $row['is_new'] = ((time() - $row['last_time']) > 864000) ? 0 : 1; 
            $row['url'] = $this->config['site_url'] . '/wap.php?c=Mall&a=detail&goods_id=' . $row['goods_id'];
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
           /*判断是否存在预售商品*/
            $row['book_status']=0;//下默认没有
            $book_good=D('Mall_book_goods')->get_book_record($row['goods_id']);
            if(!empty($book_good)){
             $row['book_status']=1;
            }



            $score = $goodsMod->getScoreMaxByGoodsId($row['goods_id']);
            if ($score['is_show']) {
                $score['reduce_money'] = $score['is_percent'] ? getFormatNumber($row['price'] * $score['score_max_percent']) : getFormatNumber($score['score_max'] / C('config.user_score_use_percent'));
                $row['price_type'] = 2;
                $row['account_money'] = $row['price'] - $score['reduce_money'] > 0 ? getFormatNumber($row['price'] - $score['reduce_money']) : 0;
                $row['account_score'] = intval($score['reduce_money'] * C('config.user_score_use_percent'));
            }


            $row['vip_show'] = $levelDiscount['show_level_price'] ? true : false;
            $row['vip_tag'] = M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
            $row['vip_price'] = getFormatNumber($row['price'] * $levelDiscount['discount']);
            $row['vip_unit_price'] = getFormatNumber($row['unit_price'] * $levelDiscount['discount']);

            $goods_list[] = $row;
        }
        return $goods_list;
    }

    //商城店铺新品页
    public function get_store_new_goods_list(){

        $store_id   = isset($_POST['store_id']) ? intval($_POST['store_id']) : intval($_GET['store_id']);//店铺id
        $order      = isset($_POST['order']) ? intval($_POST['order']) :0;//排序规则：0综合，1销量倒序，2销量正序，3价格倒序，4价格正序
        if(!$store_id){
            $this->returnCode(1, null, '店铺不存在！');
        }
        $model = D('Shop_goods');
        $goods_order = $this->get_order($order);
        $where = array();
        $where['status'] = 1;
        $where['cat_fid'] = array('gt',0);
        $where['store_id'] = $store_id;
		
		$show_sort = D('Shop_goods_sort')->get_show_sort($store_id);
		$where['sort_id'] = array('in',$show_sort);

        //更新日期小于15天且销量小于3 的才是新品
        $time = time()-60*60*24*15;
        $where['last_time']  = array('gt',$time);
        $where['sell_count'] = array('lt',4);
	    $addrs = $this->getUserMsg($this->_uid);
        if (empty($addrs)) {
            $arr['province_id'] = '';
            $arr['city_id']     = C('config.now_city');
            $arr['area_id']     = '';
        } else {
            $arr['province_id'] = $addrs['province_id'];
            $arr['city_id']     = $addrs['city_id'];
            $arr['area_id']     = $addrs['area_id'];
        }
        $list = $model->Where($where)->order($goods_order)->limit(0,30)->select();

        $area_id=D('Shop_goods')->getAreaId($arr);
        if(!empty($area_id)){
            if(!empty($list)){
                foreach ($list as $k => $v){
                    if($v['use_area']==1){
                        $area_good_list = D('Shop_goods_area')->where(array('did'=>$v['goods_id'],'store_id'=>array('exp','IS NOT NULL')))->getField('aid',true);
                        if(empty($area_good_list)){
                            continue;
                        }
                        //取交集,有则过滤商品
                        $arrs=array_intersect($area_id, $area_good_list);
                        if(empty($arrs)){
                            unset($list[$k]);
                            continue;
                        }
                    }
                }
                $list=array_values($list);
            }
        }

        $list = $this->deal_goods_list($list);
        $list = $this->dealTimeData($list);
        $this->returnCode(0,$list);
    }

    private function dealTimeData($list){
        $return = array();
        if($list){
            $temp = array();
            foreach ($list as $key => $val) {
                $time = date("Y-m-d",$val['last_time']);
                $temp[$time]['time'] =  $time;
                $temp[$time]['goods_list'][] = $val;
            } 

            foreach ($temp as $key => $value) {
                $return[] = $value;
            } 
        }
        return $return;
    }

    private function get_order($order){
        switch ($order) {
            case '1'://1销量倒序
                $order = 'sell_count DESC,goods_id DESC';
                break;
            case '2'://2销量正序
                $order = 'sell_count ASC,goods_id DESC';
                break;
            case '3'://3价格倒序
                $order = 'price DESC,goods_id DESC';
                break;
            case '4'://4价格正序
                $order = 'price ASC,goods_id DESC';
                break;
            default:
                $order = 'sort DESC,sell_count DESC,goods_id DESC';
                break;
        }
        return $order;
    }

    //根据店铺分类等级获取最低层级的分类数组(只获取1级或者2级分类的最低级)
    private function get_last_sort($sort_id,$sort_level){
        if(!$sort_id || !$sort_level) return array();
        $model = M('Shop_goods_sort');
        if($sort_level==2){
            $next = $model->Where(array('fid'=>$sort_id,'_string'=>'(`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\')'))->select();
            //echo M()->getlastSql();exit;
            if($next){
                return $this->deal_array($next);
            }else{
                return $sort_id;
            }
        }
        if($sort_level==1){
            $second = $model->Where(array('fid'=>$sort_id,'_string'=>'(`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\')'))->select();
            //dump($second);exit;
            if(empty($second )) return $sort_id;
            $second_array = $this->deal_array($second);
            $where = array();
            $where['fid'] = array('in',$second_array);
            $where['_string'] = '(`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\')';
            $third = $model->Where($where)->select();
            if($third){
                return $this->deal_array($third);
            }else{
                return $second_array;
            }

        }
        return false;
    }

    private function deal_array($list,$field='sort_id'){
        $return = array();
        if($list){
            foreach ($list as $key => $val) {
                $return[] =$val[$field];
            }
        }
        return $return;
    }

	public function seckillGoodsList()
    {
//        $params = $_POST;
//        $type = I('type','shop');
////        $data = D('Admin')->getSeckillGoodsList($type, $params);
//        $data = D('Admin')->getSeckillGoods($params['goods_list']);
//        
//        $this->returnCode(0,$data);
    }
	
	
	
	
	
	
	
	
	
	
}

?>