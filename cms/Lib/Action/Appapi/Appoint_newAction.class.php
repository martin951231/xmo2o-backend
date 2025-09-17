<?php

use app\appoint\model\db\MerchantWorkers;
use app\appoint\model\db\RewardOrder;

/**
 * 
 * 预约服务
 */
class Appoint_newAction extends BaseAction{

    public function config() {
        $database_home_menu = D('Home_menu');
//        $footer_menu_list = $database_home_menu->getMenuList('appoint_footer');
//        $return = array('hidden' => false);
//        if ($footer_menu_list) {
//            foreach ($footer_menu_list as &$val) {
//                if ($val['pic_path']) {
//                    $val['pic_path'] = file_domain() . '/upload/slider/' . $val['pic_path'];
//                }
//                if ($val['hover_pic_path']) {
//                    $val['hover_pic_path'] = file_domain() . '/upload/slider/' . $val['hover_pic_path'];
//                }
//            }
//        }
        // 底部导航写死
        $footer_menu_list = [];
        $site_url = $this->config['site_url'];
        $base_url = D('Appoint')->appoint_base_url;
        $appoint_menus = [
            'name' => '首页',
            'url' => $site_url.$base_url.'pages/appoint/index',
            'pic_path' => $site_url.'/static/appoint_new/nav_img/index.png',
            'hover_pic_path' => $site_url.'/static/appoint_new/nav_img/index_hover.png',
            'wxapp_id' => '',
            'wxapp_page' => '',
            'android_app_name' => '',
            'android_app_url' => '',
            'ios_app_name' => '',
            'ios_app_url' => '',
            'type' => '0',
            'bgColor' => '#139CFF',
            'navType' => 'home',
            'suspensionPic' => $site_url.'/static/wxapp/appoint/icon-home.png',
        ];
        $footer_menu_list[] = $appoint_menus;
        $appoint_menus = [
            'name' => '分类',
            'url' => $site_url.$base_url.'pages/appoint/category',
            'pic_path' => $site_url.'/static/appoint_new/nav_img/cat.png',
            'hover_pic_path' => $site_url.'/static/appoint_new/nav_img/cat_hover.png',
            'wxapp_id' => '',
            'wxapp_page' => '',
            'android_app_name' => '',
            'android_app_url' => '',
            'ios_app_name' => '',
            'ios_app_url' => '',
            'type' => '0',
            'bgColor' => '#F57F59',
            'navType' => 'classification',
            'suspensionPic' => $site_url.'/static/wxapp/appoint/icon-classification.png',
        ];
        $footer_menu_list[] = $appoint_menus;


        $appoint_menus = [
            'name' => '收藏',
            'url' => $site_url.$base_url.'pages/my/collect?type=appoint',
            'pic_path' => $site_url.'/static/appoint_new/nav_img/collection.png',
            'hover_pic_path' => $site_url.'/static/appoint_new/nav_img/collection_hover.png',
            'wxapp_id' => '',
            'wxapp_page' => '',
            'android_app_name' => '',
            'android_app_url' => '',
            'ios_app_name' => '',
            'ios_app_url' => '',
            'type' => '0',
            'bgColor' => '#FE8F36',
            'navType' => 'collection',
            'suspensionPic' => $site_url.'/static/wxapp/appoint/icon-collection.png',
        ];
        $footer_menu_list[] = $appoint_menus;

        $appoint_menus = [
            'name' => '订单',
            'url' => $site_url.$base_url.'pages/my/my_order?state=0',
            'pic_path' => $site_url.'/static/appoint_new/nav_img/order.png',
            'hover_pic_path' => $site_url.'/static/appoint_new/nav_img/order_hover.png',
            'wxapp_id' => '',
            'wxapp_page' => '',
            'android_app_name' => '',
            'android_app_url' => '',
            'ios_app_name' => '',
            'ios_app_url' => '',
            'type' => '0',
            'bgColor' => '#06C1AE',
            'navType' => 'order',
            'suspensionPic' => $site_url.'/static/wxapp/appoint/icon-order.png',
        ];
        $footer_menu_list[] = $appoint_menus;

        fdump($footer_menu_list,'$footer_menu_list');


		$return['tabbar_type'] = 'business';
		
        $return['list'] = $footer_menu_list;
		
        $return['backgroundColor']  =  preg_match("/^rgb/", $this->config['app_footer_bgcolor']) ? RGBToHex($this->config['app_footer_bgcolor']) : $this->config['app_footer_bgcolor'];
		
        $return['selectedColor']  =  preg_match("/^rgb/", $this->config['app_footer_txtactivecolor']) ? RGBToHex($this->config['app_footer_txtactivecolor']) : $this->config['app_footer_txtactivecolor'];
		
        $return['color']  =  preg_match("/^rgb/", $this->config['app_footer_txtcolor']) ? RGBToHex($this->config['app_footer_txtcolor']) : $this->config['app_footer_txtcolor'];
		
		
		if(!$return['backgroundColor']){
			$return['backgroundColor'] = '#FAFAFC';
		}
		if(!$return['color']){
			$return['color'] = '#777777';
		}
		if(!$return['selectedColor']){
			$return['selectedColor'] = '#06c1ae';
		}
        $return['appoint_alias_name'] = $this->config['appoint_alias_name'] ? $this->config['appoint_alias_name'] : '预约';


        $this->returnCode(0,$return);
    }

    public function index_new() {
        $database_adver = D('Adver');
        $database_slider = D('Slider');
        $database_system_coupon = D("System_coupon");
        $database_appoint_category = D('Appoint_category');
        $arr = array();
        //顶部广告
        $wap_index_top_adver = $database_adver->get_adver_by_key('wap_yue_index_top', 5);
        $arr['index_top_adver'] = $wap_index_top_adver;

        //中间广告
//        $wap_index_center_adver = $database_adver->get_adver_by_key('wap_yue_index_center', 4);
//        $arr['wap_index_center_adver'] = $wap_index_center_adver;

        //导航条
        $tmp_wap_index_slider = $database_slider->get_slider_by_key('wap_yue_slider', 0);
        $wap_index_slider = array();
        foreach ($tmp_wap_index_slider as $key => $value) {
            $tmp_i = floor($key / 10);
            $wap_index_slider[$tmp_i][] = $value;
        }
        $arr['index_slider'] = $wap_index_slider;
        //所有分类 包含2级分类
        $all_category_list = $database_appoint_category->get_category_new();
        $arr['category_list'] = $all_category_list;

        $now_city = intval($_POST['now_city'])>0?intval($_POST['now_city']):$this->config['now_city'];
        $now_area = M('Area')->where(['area_id' => $now_city])->field('area_id,area_name')->find();
        $arr['now_city'] = $now_city;
        $arr['now_area_name'] = $now_area['area_name'];
        $arr['appoint_alias_name'] = $this->config['appoint_alias_name'] ? : '预约';
        
        $arr['appoint_style'] = $this->config['appoint_style'];
        $arr['appoint_bottom_style'] = $this->config['appoint_bottom_style'];
        
        $arr['appoint_show_tab'] = $this->config['appoint_show_tab'];//预约首页设置的tab是否显示
        $showTime = date('Y-m-d H:i:s');
        
        $appointActivity = M('Appoint_activity')
            ->where([
                'id' => $this->config['appoint_index_activity'],
                'delete_time' => 0,
                'start_show_time' => ['lt', $showTime],
                'end_show_time' => ['gt', $showTime]
            ])
            ->find();
        $arr['appoint_activity_page'] = '';
        if($appointActivity){
            $page = M('Micro_page_decorate')
                ->where([
                    'source' => 'appoint_Activity', 
                    'source_id' => $appointActivity['id'],
                    'type' => 1
                ])
                ->order('id DESC')
                ->find();
            if($page){
                $url = "/packapp/plat/pages/customPage/customPage?source=appoint_activity&source_id={$appointActivity['id']}&pageId={$page['id']}";
                $arr['appoint_activity_page'] = C('config.site_url'). $url;
            }
        }
		$arr['share_info'] = [
			'title'=> $arr['appoint_alias_name'] . ' - ' . $this->config['site_name'],
			'desc'=> '点击了解更多',
			'icon'=> $this->config['wechat_share_img'] ? : $this->config['site_logo'],
		];
		
        $this->returnCode(0,$arr);
    }


    public function rand_coupon(){
        if($this->_uid){
            $now_user = D('User')->get_user($this->_uid);
        }else{
            $this->returnCode('20044010');
        }
        $database_system_coupon = D('System_coupon');

        if($this->config['open_rand_send']){
            $where = array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$now_user['uid'],'from'=>'app','city_id'=>$now_user['city_id']);
            isset($_POST['cate_name']) && $where['cate_name'] = safeValue($_POST['cate_name']);
            if (!$where['cate_name']) {
                $where['cate_name'] = 'appoint';
            }
            $coupon_html = $database_system_coupon->rand_send_coupon_get($where);
            if(empty($coupon_html)){
                $this->returnCode(0,array(
                    'coupon'=>array(),
                    'coupon_count'=>0,
                ));

            }else{
                $this->returnCode(0,$coupon_html);
            }

        }else{
            $this->returnCode(0,array(
                'coupon'=>array(),
                'coupon_count'=>0,
            ));
        }
    }

    public function category(){
        $cat_id = I('cat_id');
        $database_appoint_category = D('Appoint_category');
        //所有分类 包含2级分类
        $all_category_list = $database_appoint_category->get_category_new(1,0,0,0);
        $arr['category_list'] = $all_category_list;
        $first = array_shift($all_category_list);
        $now_cat_id = !empty($cat_id) ? $cat_id : $first['cat_id'];
        $arr['now_cat_id'] = $now_cat_id;
        $this->returnCode(0,$arr);
    }

    // 列表筛选条件获取
    public function screening_conditions() {
        $type = I('type', 'category', 'trim');
        $arr = array();
        if ('category' == $type) {
            $database_appoint_category = D('Appoint_category');
            $database_area = D('Area');
            //判断分类信息
            $cat_url = !empty($_POST['cat_url']) ? $_POST['cat_url'] : '';
            $area_url = !empty($_POST['area_url']) ? $_POST['area_url'] : '';

            if (!empty($area_url)) {
                $tmp_area = $database_area->get_area_by_areaUrl($area_url);
                if (empty($tmp_area)) {
                    $this->returnCodeError('当前区域不存在！');
                }
                $arr['now_area'] = $tmp_area;

                if ($tmp_area['area_type'] == 3) {
                    $now_area = $tmp_area;
                } else {
                    $now_circle = $tmp_area;
                    $arr['now_circle'] = $now_circle;
                    $now_area = $database_area->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                    if (empty($tmp_area)) {
                        $this->returnCodeError('当前区域不存在！');
                    }
                }
                $arr['top_area'] = $now_area;
            }

            //判断排序信息   默认排序就是按照手动设置项排序
            $sort_id = !empty($_POST['sort_id']) ? $_POST['sort_id'] : 'defaults';

            $long_lat = array('lat' => 0, 'long' => 0);
            $_SESSION['openid'] && $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
            if (empty($long_lat['long']) || empty($long_lat['lat'])) {
                $sort_id = $sort_id == 'juli' ?  $sort_id:'defaults' ;
                $sort_array = array(
                    array('sort_id' => 'defaults', 'sort_value' => '综合排序'),
                    array('sort_id' => 'sale_max', 'sort_value' => '销量最高'),
                    array('sort_id' => 'evaluate_max', 'sort_value' => '评价最好'),
                    array('sort_id' => 'price_asc', 'sort_value' => '价格由低到高'),
                    array('sort_id' => 'priceDesc', 'sort_value' => '价格由高到低'),
                    array('sort_id' => 'juli', 'sort_value' => '离我最近'),
                );
            } else {
                $sort_array = array(
                    array('sort_id' => 'defaults', 'sort_value' => '综合排序'),
                    array('sort_id' => 'sale_max', 'sort_value' => '销量最高'),
                    array('sort_id' => 'evaluate_max', 'sort_value' => '评价最好'),
                    array('sort_id' => 'price_asc', 'sort_value' => '价格由低到高'),
                    array('sort_id' => 'priceDesc', 'sort_value' => '价格由高到低'),
                    array('sort_id' => 'juli', 'sort_value' => '离我最近'),
                );
            }
            foreach ($sort_array as $key => $value) {
                if ($sort_id == $value['sort_id']) {
                    $now_sort_array = $value;
                    break;
                }
            }
            $arr['sort_array'] = $sort_array;
            $arr['now_sort_array'] = $now_sort_array;

            //根据分类信息获取分类
            if (!empty($cat_url)) {
                $now_category = $database_appoint_category->get_category_by_catUrl($cat_url);
                if (empty($now_category)) {
                    $this->returnCodeError('此分类不存在！');
                }
                $arr['now_category'] = $now_category;
                if (!empty($now_category['cat_fid'])) {
                    $f_category = $database_appoint_category->get_category_by_id($now_category['cat_fid']);
                    $this->assign('top_category', $f_category);
                    // 存在上级即为 二级分类  查询上级分类列表
                    $two_category = $database_appoint_category->get_category_new(false, $now_category['cat_fid'],0,4,[0,1]);
                    $arr['two_category'] = $two_category;
                    $arr['top_category'] = $f_category;
                } else {
                    // 没有上级即为 一级分类  查询二级分类列表
                    $two_category = $database_appoint_category->get_category_new(false, $now_category['cat_id'],0,4,[0,1]);
                    $arr['two_category'] = $two_category;
                }
            }
            $all_area_list = $database_area->get_all_area_list('','','category_new','*,area_name as name',true);
            $arr['all_area_list'] = array_values($all_area_list);
            $arr['long_lat'] = $long_lat;

            // 处理返回最右边筛选
            $filter_item = array(
//                array(
//                    'name' => '服务商',
//                    'type' => '1',
//                    'key_word' => 'is_autotrophic',
//                    'choose' => array(
//                        0 => array('name' => '平台', 'value' => 1),
//                        1 => array('name' => '商家', 'value' => 0),
//                        2 => array('name' => '第三方入驻', 'value' => 0),
//                    )
//                ),
                array(
                    'name' => '交易方式',
                    'type' => '1',
                    'key_word' => 'payment_status',
                    'choose' => array(
                        0 => array('name' => '一口价', 'value' => 0),
                        1 => array('name' => '预付金', 'value' => 1),
                    )
                ),
                array(
                    'name' => '价格区间',
                    'type' => '2',
                    'choose' => array(
                        0 => array('name' => '最低价','key_word' => 'price_min'),
                        1 => array('name' => '最高价','key_word' => 'price_max'),
                    )
                ),
            );
            $arr['filter_item'] = $filter_item;


            $this->returnCode(0,$arr);
        } elseif ('search' == $type) {
            $sort_id = !empty($_POST['sort_id']) ? $_POST['sort_id'] : 'defaults';
            $sort_array = array(
                array('sort_id' => 'defaults', 'sort_value' => '综合排序'),
                array('sort_id' => 'evaluate_max', 'sort_value' => '好评多'),
                array('sort_id' => 'sale_max', 'sort_value' => '销量')
            );
            foreach ($sort_array as $key => $value) {
                if ($sort_id == $value['sort_id']) {
                    $now_sort_array = $value;
                    break;
                }
            }
            $arr['sort_array'] = $sort_array;
            $arr['now_sort_array'] = $now_sort_array;
            $this->returnCode(0,$arr);
        }
    }

    // 获取对应分类下预约信息
    public function ajaxList() {
        $area_url = isset($_POST['area_url']) && $_POST['area_url'] ? htmlspecialchars($_POST['area_url']) : 'all';
        $cat_url = isset($_POST['cat_url']) && $_POST['cat_url'] ? htmlspecialchars($_POST['cat_url']) : 'all';
        $circle_id = 0;
        $keywords = isset($_POST['keywords']) && $_POST['keywords'] ? htmlspecialchars($_POST['keywords']) : '';

        $database_area = D('Area');
        $database_appoint = D('Appoint');
        $database_user_long_lat = D('User_long_lat');
        $database_appoint_category = D('Appoint_category');
        $database_meal_store_category = D('Meal_store_category');
        if ($area_url != 'all') {
            $tmp_area = $database_area->get_area_by_areaUrl($area_url);
            if (empty($tmp_area)) {
                $this->returnCodeError('当前区域不存在！');
            }

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = $database_area->get_area_by_areaId($tmp_area['area_pid'], true, $area_url);
                if (empty($tmp_area)) {
                    $this->returnCodeError('当前区域不存在！');
                }
                $circle_id = $now_circle['area_id'];
            }
            $area_id = $now_area['area_id'];
        } else {
            $area_id = 0;
        }
        $sort_id = !empty($_POST['sort_id']) ? $_POST['sort_id'] : 'juli';

        $openid = D('User')->where(array('uid' => $this->_uid))->getField('openid');
        $long_lat = $database_user_long_lat->getLocation($openid);
        if (empty($long_lat) || empty($long_lat['lat']) || empty($long_lat['long'])) {
            $sort_id = $sort_id == 'juli' ? 'defaults' : $sort_id;
        }
        $where = array(
            'area_id' => $area_id,
            'order' => $sort_id,
            'lat' => $long_lat['lat'],
            'long' => $long_lat['long'],
            'circle_id' => $circle_id,
        );
        if ($cat_url != 'all') {
            $now_category = $database_appoint_category->get_category_by_catUrl($cat_url);
            if (empty($now_category)) {
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category', $now_category);

            if (!empty($now_category['cat_fid'])) {
                $f_category = $database_meal_store_category->get_category_by_id($now_category['cat_fid']);

                $this->assign('top_category', $f_category);

                $cat_fid = $now_category['cat_fid'];
                $cat_id = $now_category['cat_id'];
            } else {
                $this->assign('top_category', $now_category);

                $cat_id = 0;
                $cat_fid = $now_category['cat_id'];
            }
            $where['get_grouplist_catid'] = $cat_id;
            $where['get_grouplist_catfid'] = $cat_fid;
        }
        if (isset($_POST['is_autotrophic']) && is_array($_POST['is_autotrophic'])) {
            // 考虑到只有二级分类 才有 是否平台自营 0，商家入驻 1，平台自营 2，第三方入驻
            $where['is_autotrophic'] = array('in', $_POST['is_autotrophic']);
        }
        if (isset($_POST['payment_status']) && is_array($_POST['payment_status'])) {
            $where['payment_status'] =  $_POST['payment_status'];
        }
        if (isset($_POST['price_min'])) {
            $where['price_min'] = $_POST['price_min'];
        }
        if (isset($_POST['price_max'])) {
            $where['price_max'] = $_POST['price_max'];
        }
        if ($keywords) {
            $where['keywords'] = $keywords;
            unset($where['get_grouplist_catid']);
            unset($where['get_grouplist_catfid']);
        }
        $appointList = $database_appoint->get_appoint_list_new($where);
        if (!empty($appointList['list'])) {
            foreach ($appointList['list'] as &$item) {
                if (isset($item['is_appoint_price']) && !$item['is_appoint_price'] && floatval($item['appoint_price'])<=0) {
                    $item['appoint_price'] = '面议';
                }
                $item['appoint_sum'] += intval($item['virtual_sales']);
		$specPrice = M('AppointProduct')
                    ->where(['appoint_id' => $item['appoint_id']])
                    ->field('appoint_id, price')
                    ->select();
                if(!empty($specPrice)){
                    $appointProductPrice = array_column($specPrice,'price');
                    $item['appoint_price'] = min($appointProductPrice);
                }
                $item['appoint_product'] = empty($specPrice) ? NULL : $specPrice;

                //积分展示
                $item['score_text'] = '';
                if(C('customization.goods_show_score') && $scorePrice = floatval($item['appoint_price'])){
                    $scorePercent = D('Percent_rate')->scorePercent('appoint', $item['appoint_id'], $item['mer_id']);
                    $score = bcmul($scorePrice, $scorePercent,2);
                    if(C('config.score_round_two')){
                        $score = sprintf('%.2f',floatval($score));
                    }else{
                        $score = round($score);
                    }
                    floatval($score) > 0  && $item['score_text'] = C('config.score_name').'+'.$score;
                }
                //消费一元获得积分
                $buy_can_get_score = D('Goods_score')->getGoodsScore($item['appoint_price'],'appint',false,$item['score_percent']);
                $item['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                $item['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            }
        }
        $arr = array();
        $arr['appoint_list'] = $appointList;
        
        $now_city = intval($_POST['now_city'])>0?intval($_POST['now_city']):$this->config['now_city'];
        $now_area = M('Area')->where(['area_id' => $now_city])->field('area_id,area_name')->find();
        $arr['now_city'] = $now_city;
        $arr['now_area_name'] = $now_area['area_name'];
        $this->returnCode(0,$arr);

    }

    // 热门搜索词
    public function search_hot() {
        //预约热门搜索词
        $type = 3;
        $search_hot_list = D('Search_hot')->get_list(18,$type,true);
        $arr['search_hot_list'] = $search_hot_list;
        $this->returnCode(0,$arr);
    }

    // 第三方入驻
    public function category_detail() {
        $cat_id = I('cat_id');
        $database_appoint_category = D('Appoint_category');
        $category_info = $database_appoint_category->get_category_by_id($cat_id);
        if (2==$category_info['is_autotrophic']) {
            // 1，平台自营 2，第三方入驻 单独处理
            if ($category_info) {
                $cuefield = unserialize($category_info['cue_field']);
                foreach ($cuefield as $val) {
                    $sort[] = $val['sort'];
                }
                array_multisort($sort, SORT_DESC, $cuefield);
            }
            $category_info['wap_title'] = unserialize($category_info['wap_title']);
            $wap_content =  unserialize($category_info['wap_content']);
            $wap_content = replace_file_domain_content(htmlspecialchars_decode($wap_content,ENT_QUOTES));
            $wap_content = str_replace('src=', ' style="max-width:100%;" src=', $wap_content);
            $category_info['wap_content'] = $wap_content;
            $category_info['cat_big_pic'] = file_domain().'/upload/system/'.$category_info['cat_big_pic'];

            $arr['cat_info'] = $category_info;
            $arr['formData'] = $cuefield;

            $arr['text_box'] = $this->config['site_url'].'/static/appapi/img/fenleit.png';
            $this->returnCode(0,$arr);
        } else {
            $this->returnCodeError('当前分类信息有误！');
        }
    }

    // 详情页面接口
    public function detail() {
        $database_appoint_order = D('Appoint_order');
        $database_appoint = D('Appoint');
        $database_appoint_product = D('Appoint_product');
        $database_reply = D('Reply');
        $database_appoint_category = D('Appoint_category');
        $database_appoint_comment = D('Appoint_comment');

        $appoint_id = I('appoint_id');
        $cat_url = I('cat_url');
        if (empty($appoint_id) && empty($cat_url)) {
            $this->returnCodeError('当前预约项不存在！');
        }
        $arr = array();
        if ($appoint_id) {
            $now_appoint = $database_appoint->get_appoint_by_appointId($appoint_id, 'hits-setInc', true);
            if (empty($now_appoint)) {
                $this->returnCodeError('当前预约项不存在！');
            }else{
                $supply_where['service_status'] = array('gt', 0);
                $supply_where['appoint_id'] = $appoint_id;
                $appoint_sum = $database_appoint_order->where($supply_where)->count();
                if ($appoint_sum &&$appoint_sum!=$now_appoint['appoint_sum']) {
                    // 不同进行改写
                    $now_appoint['appoint_sum'] = $appoint_sum;
                    $database_appoint->where($supply_where)->setField('appoint_sum', $appoint_sum);
                }
            }
            $category_info = $database_appoint_category->get_category_by_id($now_appoint['cat_id']);
        } else {
            $category_info = $database_appoint_category->get_category_by_catUrl($cat_url);
            if (2!=$category_info['is_autotrophic'] && 1!=$category_info['is_autotrophic']) {
                $this->returnCodeError('非第三方入驻或平台自营！');
            }
        }
        if ($this->_uid) {
            $now_user = D('User')->get_user($this->_uid);
        }
        if ($category_info['is_autotrophic']) {
            // 1，平台自营 2，第三方入驻 单独处理
            if ($now_appoint['store_list']) {
                $store_msg = reset($now_appoint['store_list']);
                $kf = M('Merchant_store_kefu')->where(['store_id' => $store_msg['store_id']])->find();
                if (C('IM_JMESSAGE_OPEN') && $kf && $this->_uid > 0) {
                    $kf_url = build_im_chat_url('user_' . $this->_uid, $kf['username'], 'user2store', ['from' => 'appoint']);
                    $arr['kf_url'] = $kf_url;
                }
            }

            if ($category_info) {
                $cuefield = unserialize($category_info['cue_field']);
                foreach ($cuefield as $val) {
                    $sort[] = $val['sort'];
                }
                array_multisort($sort, SORT_DESC, $cuefield);
            }
            $category_info['wap_title'] = unserialize($category_info['wap_title']);
            $wap_content =  unserialize($category_info['wap_content']);
            if (is_array($wap_content)) {
                foreach($wap_content as &$val) {
                    $val = replace_file_domain_content_img($val);
                }
            } else {
                $wap_content = replace_file_domain_content_img($wap_content);
            }
            $category_info['wap_content'] = $wap_content;
            if(!strpos($category_info['cat_big_pic'],'/upload/system')){
                $category_info['cat_big_pic'] = '/upload/system/'.$category_info['cat_big_pic'];
            }
            $category_info['cat_big_pic'] = replace_file_domain($category_info['cat_big_pic']);
            $arr['cat_info'] = $category_info;
            $arr['formData'] = $cuefield;

            $arr['text_box'] = $this->config['site_url'].'/static/appapi/img/fenleit.png';
            $share_img = $category_info['cat_big_pic'];
            if (!$share_img) {
                $share_img = $now_appoint['logo'];
            }
            $share_info = [];
            $share_info['share_img'] = $share_img;
            $share_info['share_wx'] = $this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username'] ? 'wxapp' : 'h5';
            $share_info['userName'] = $this->config['pay_wxapp_username'];
            $arr['share_info'] = $share_info;
            $this->returnCode(0,$arr);
        }
        $share_img = '';
        if ($now_appoint['all_pic']) {
            foreach ($now_appoint['all_pic'] as  &$items) {
                if ($items['image']) {
                    $items['image'] = thumb($items['image'],400,400,'fill');
                    if (!$share_img) {
                        $share_img = $items['image'];
                    }
                }
            }
        }
        if (!$share_img) {
            $share_img = $now_appoint['logo'];
        }

        $arr['cat_info'] = $category_info;
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);

        if($long_lat && $long_lat['lat'] && $long_lat['long']){
            $rangeSort = array();
            foreach($now_appoint['store_list'] as &$storeValue){
                $storeValue['Srange'] = getDistance($long_lat['lat'],$long_lat['long'],$storeValue['lat'],$storeValue['long']);
                $storeValue['range'] = getRange($storeValue['Srange'],false);
                $rangeSort[] = $storeValue['Srange'];
            }
            array_multisort($rangeSort, SORT_ASC, $now_appoint['store_list']);
            $arr['long_lat'] = $long_lat;
        }
        if ($now_appoint['store_list'] && count($now_appoint['store_list'])==1) {
            $now_appoint['store_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id='.$now_appoint['store_list'][0]['store_id'];
        } elseif ($now_appoint['store_list'] && $now_appoint['store_list'][0]) {
            // 暂时也强制跳转第一个店铺
            $now_appoint['store_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id='.$now_appoint['store_list'][0]['store_id'];
        } else {
            $now_appoint['store_url'] = '';
        }
        $where_comment['appoint_id'] = $appoint_id;
        $where_comment['status'] = 1;
        $comment_list = $database_appoint_comment->where($where_comment)->limit(1)->select();
        $all_score=0;
        foreach ($comment_list as $key => $value) {
            $sum = $value['profession_score']+$value['communicate_score']+$value['speed_score'];
            $comment_list[$key]['sum'] = 5-intval($sum/3);
            $comment_img = unserialize(htmlspecialchars_decode($value['comment_img']));
            if ($comment_img) {
                foreach ($comment_img as &$val) {
                    $val = replace_file_domain($val);
                }
            }
            $comment_list[$key]['comment_img'] = $comment_img;
            if($value['score']=='0.00'){
                $comment_list[$key]['score'] = intval($sum/3);
                $value['score'] =  $comment_list[$key]['score'];
            }
            $all_score +=$value['score'];

            $where_reply['order_id'] = $value['order_id'];
            $where_reply['order_type'] = 2;
            $reply = M('Reply')->where($where_reply)->find();
            $user = D('User')->get_user($value['uid']);
            $comment_list[$key]['anonymous'] = $reply['anonymous'] ;
            $comment_list[$key]['nickname'] = $reply['anonymous'] ? '匿名网友' : $user['nickname'] ;
            $comment_list[$key]['avatar'] = $reply['anonymous']? $this->config['site_url'] . '/static/images/user_avatar.jpg' : $user['avatar'];
            $comment_list[$key]['add_time'] = date('Y-m-d H:i:s', $comment_list[$key]['add_time']);
        }
        //按照产品要求取所有评分的平均分
        $sumScore = $database_appoint_comment
            ->where($where_comment)
            ->field("
            COUNT(*) cnt, SUM(
                (
                IF(profession_score = 0, '5', profession_score) + 
                IF(communicate_score = 0, '5', communicate_score) + 
                IF(speed_score = 0, '5', speed_score) + 
                IF(score = 0, '5', score)
                ) / 4
            ) AS sum_score
            ")
            ->find();
        if (count($comment_list)>0) {
            $score_mean = round($sumScore['sum_score']/$sumScore['cnt']);
        } else {
            $score_mean = 5;//默认5星好评
        }
        $now_appoint['score_mean'] = $score_mean;
        D('Appoint')->where(['appoint_id'=>$appoint_id])->save(['score_mean' => $score_mean]);
        $comment_count = $database_appoint_comment->where($where_comment)->count();
        $arr['comment_count'] = $comment_count;
        $arr['comment_list'] = $comment_list;

        // 获取下联系电话
        if (count($now_appoint['store_list']) == 1) {
            $now_appoint['tel'] = $now_appoint['store_list'][0]['phone'];
        } else {
            $database_merchant = D('Merchant');
            $merchant_where['mer_id'] = $now_appoint['mer_id'];
            $now_appoint['tel'] = $database_merchant->where($merchant_where)->getField('phone');
        }

        // 评论数量
        $appoint_reply_list = $database_reply->get_appointReply_list($now_appoint['appoint_id']);
        $now_appoint['reply_num'] = count($appoint_reply_list);
        $product_condition['appoint_id'] = $appoint_id;
        $appoint_product_list = $database_appoint_product->where($product_condition)->select();

        if(count($appoint_product_list) == 1){
            $appoint_product_list[0]['is_active'] = 1;
            $now_appoint['appoint_price'] = $appoint_product_list[0]['price'];
            $now_appoint['payment_money'] = $appoint_product_list[0]['payment_price'];
        }else if($appoint_product_list){
            $tmp_appoint_product_list = array();
            foreach($appoint_product_list AS $uniqid => $row){
                foreach($row AS $key=>$value){
                    $tmp_appoint_product_list[$key][$uniqid] = $value;
                }
            }

            $sort = array(
                'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'payment_price',       //排序字段
            );
            array_multisort($tmp_appoint_product_list[$sort['field']], constant($sort['direction']) ,$appoint_product_list);
            $appoint_product_list[0]['is_active'] = 1;
            $now_appoint['appoint_price'] = $appoint_product_list[0]['price'];
            $now_appoint['payment_money'] = $appoint_product_list[0]['payment_price'];
        }
        $now_appoint['appoint_pic_content'] = replace_file_domain_content_img($now_appoint['appoint_pic_content']);
        $now_appoint['appoint_pic_content'] = str_replace(" font-size:16px;background-color:#ffffff;\" ", ' ', $now_appoint['appoint_pic_content']);
        $now_appoint['appoint_pic_content'] = str_replace("font-family:\" ", '', $now_appoint['appoint_pic_content']);
        $now_appoint['appoint_pic_content'] = str_replace(";font-family:\"", '"', $now_appoint['appoint_pic_content']);
        $now_appoint['appoint_pic_content'] = str_replace(" color:#9f9f9f;\"", ' ', $now_appoint['appoint_pic_content']);

        if (!empty($now_appoint)) {
            if (isset($now_appoint['is_appoint_price']) && !$now_appoint['is_appoint_price'] && floatval($now_appoint['appoint_price'])<=0) {
                $now_appoint['appoint_price'] = '面议';
            }
        }
        //积分展示
        $now_appoint['score_text'] = '';
        if(C('customization.goods_show_score') && $scorePrice = floatval($now_appoint['appoint_price'])){
            $scorePercent = D('Percent_rate')->scorePercent('appoint', $now_appoint['appoint_id'], $now_appoint['mer_id']);
            $score = bcmul($scorePrice, $scorePercent,2);
            if(C('config.score_round_two')){
                $score = sprintf('%.2f',floatval($score));
            }else{
                $score = round($score);
            }
            floatval($score) > 0  &&  $now_appoint['score_text'] = C('config.score_name').'+'.$score;
        }
        $now_appoint['appoint_sum'] += intval($now_appoint['virtual_sales']);
        $arr['now_appoint'] = $now_appoint;
        // 粉丝行为分析
        D('Merchant_request')->add_request($now_appoint['mer_id'], array('appoint_hits' => 1));
        $appoint_product_list = array_map(function ($r) use($now_appoint){
            $len = mb_strlen($r['name']);
            $contentLen = mb_strlen($r['content']);
            $r['short_content'] =  mb_strimwidth($r['content'], 0, $len > 15 ? $len * 2 : 30, '', "utf-8") . ($contentLen > 15 ? '...' : '');
            //消费一元获得积分
            $buy_can_get_score = D('Goods_score')->getGoodsScore($r['price'], 'appoint', false, $now_appoint['score_percent']);
            $r['buy_can_get_score'] = $buy_can_get_score ? $buy_can_get_score['score'] : 0;
            $r['buy_can_get_score_txt'] = $buy_can_get_score ? $buy_can_get_score['score_txt'] : '';
            return $r;
        }, $appoint_product_list);
        $arr['appoint_product_list'] = $appoint_product_list;

        if ($now_appoint['store_list']) {
            $store_msg = reset($now_appoint['store_list']);
            $kf = M('Merchant_store_kefu')->where(['store_id' => $store_msg['store_id']])->find();
            if (C('IM_JMESSAGE_OPEN') && $kf && $this->_uid > 0) {
                $kf_url = build_im_chat_url('user_' . $this->_uid, $kf['username'], 'user2store', ['from' => 'appoint']);
                $arr['kf_url'] = $kf_url;
            }
        }
        
        $collection_info = D('Appoint_collection')->where(array('appoint_id'=>intval($_POST['appoint_id']),'uid'=>$this->_uid))->find();
        if(!$collection_info){
            $collection_info = D('Merchant_user_relation')->where(array('appoint_id'=>intval($_POST['appoint_id']),'uid'=>$this->_uid,'type'=>'appoint'))->find();
        }
        if($collection_info){
            $arr['collection_info'] = $collection_info;
        }
        $share_info = [];
        $share_info['share_img'] = $share_img;
        $share_info['share_wx'] = $this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username'] ? 'wxapp' : 'h5';
        $share_info['userName'] = $this->config['pay_wxapp_username'];
        $arr['share_info'] = $share_info;
        $arr['now_appoint']['buy_can_get_score'] = 0;
        $arr['now_appoint']['buy_can_get_score_txt'] = '';
        if(!$arr['appoint_product_list']) {
            //消费一元获得积分
            $buy_can_get_score = D('Goods_score')->getGoodsScore($arr['now_appoint']['appoint_price'], 'appoint', false, $now_appoint['score_percent']);
            $arr['now_appoint']['buy_can_get_score'] = $buy_can_get_score ? $buy_can_get_score['score'] : 0;
            $arr['now_appoint']['buy_can_get_score_txt'] = $buy_can_get_score ? $buy_can_get_score['score_txt'] : '';
        }
        $this->returnCode(0,$arr);
    }


    // 收藏接口
    public function collection(){
        $appoint_id = I('appoint_id');
        if (empty($appoint_id)) {
            $this->returnCodeError('收藏失败,缺少收藏对象');
        }
        $info = D('Appoint_collection')->where(array('appoint_id'=>intval($appoint_id),'uid'=>$this->_uid))->find();
        if($info){
            if(D('Appoint_collection')->where(array('appoint_id'=>intval($appoint_id),'uid'=>$this->_uid))->delete()){
                $arr = array('appoint_id'=>$appoint_id,'msg'=>'取消收藏成功');
                $this->returnCode(0,$arr);
            }

        }
        $res = D('Appoint_collection')->data(array('appoint_id'=>$appoint_id,'uid'=>$this->_uid))->add();
        if($res){
            $arr = array('appoint_id'=>$_POST['appoint_id'],'msg'=>'收藏成功');
            $this->returnCode(0,$arr);
        }else{
            $this->returnCodeError('收藏失败请重试');
        }
    }

    // 评论接口
    public function comment_list(){
        $database_appoint_comment = D('Appoint_comment');
        $appoint_id = I('appoint_id');
        $comment_type = I('average_score');
        
        $where_comment['appoint_id'] = $appoint_id;
        $where_comment['status'] = 1;
        if($comment_type==1){ //1-差评
            $where_comment['average_score'] = array('lt',2);
        }elseif($comment_type==2){ //中评
            $where_comment['average_score'][] = array('lt',4);
            $where_comment['average_score'][] = array('gt',1);
        }elseif($comment_type==3){ //好评
            $where_comment['average_score'] = array('gt',3);
        }
        // 分页处理
        $page	=	I('page',1);
        $page_size	=	I('page_size',10);

        $count = $database_appoint_comment->where($where_comment)->count();
        $list = $database_appoint_comment->where($where_comment)->page($page,$page_size)->select();
        foreach ($list as $key => $value) {
            $sum = $value['profession_score']+$value['communicate_score']+$value['speed_score'];
            $list[$key]['sum'] = 5-intval($sum/3);
            $list[$key]['comment_img'] = unserialize(htmlspecialchars_decode($value['comment_img']));
            $comment_img = array();
            if(is_array($list[$key]['comment_img'])){
                foreach($list[$key]['comment_img'] as $k=>$v){
                    if($v){
                        $comment_img[] = replace_file_domain($v);
                    }
                }
            }elseif($list[$key]['comment_img']){
                $comment_img[] = replace_file_domain($list[$key]['comment_img']);
            }
            if (!empty($comment_img)) {
                $list[$key]['comment_img_num'] = count($comment_img);
            } else {
                $list[$key]['comment_img_num'] = 0;
            }
            $list[$key]['comment_img'] = $comment_img;
            if($value['score']=='0.00'){
                $list[$key]['score'] = intval($sum/3);
                $value['score'] =  $list[$key]['score'];
            }

            $where_reply['order_id'] = $value['order_id'];
            $where_reply['order_type'] = 2;
            $reply = M('Reply')->where($where_reply)->find();
            $user = D('User')->get_user($value['uid']);
            $list[$key]['anonymous'] = $reply['anonymous'];
            $list[$key]['nickname'] = $reply['anonymous'] ? '匿名网友' : $user['nickname'];
            $list[$key]['avatar'] = $reply['anonymous']? $this->config['site_url'] . '/static/images/user_avatar.jpg' : $user['avatar'];
            $list[$key]['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
        }
        $arr['list'] = $list;

        $arr['totalPage']	= ceil($count/$page_size);
        $arr['page']   = intval($page);


        $comment = M('Appoint_comment')->where(array('appoint_id'=>$appoint_id,'status'=>1))->field('count(id) as comment_num, sum(score) as score_all')->find();
        if (empty($comment)) {
            $score_all = 5;
        } else {
            $score_all = $comment['score_all']?$comment['score_all']:0;
            $comment_num = $comment['comment_num']?$comment['comment_num']:0;
            if ($comment_num<=0) {
                $score_all = 5;
            } else {
                $score_all = $score_all/$comment_num;
            }
        }
        $arr['score_all'] = $score_all;
        fdump($arr,'$arr');
        
        $arr['top_menu'] = [
            [
                'name' => '全部',
                'average_score' => 0,
            ],
            [
                'name' => '好评',
                'average_score' => 3,
            ],
            [
                'name' => '中评',
                'average_score' => 2,
            ],
            [
                'name' => '差评',
                'average_score' => 1,
            ],
        ];
        
        $this->returnCode(0,$arr);
    }

    // 分店信息
    public function branch(){
        $appoint_id = I('appoint_id');
        $now_appoint = D('Appoint')->get_appoint_by_appointId($appoint_id, 'hits-setInc');
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);

        if($long_lat && $long_lat['lat'] && $long_lat['long']){
            $rangeSort = array();
            foreach($now_appoint['store_list'] as &$storeValue){
                $storeValue['Srange'] = getDistance($long_lat['lat'],$long_lat['long'],$storeValue['lat'],$storeValue['long']);
                $storeValue['range'] = getRange($storeValue['Srange'],false);

                $storeValue['store_url'] = $this->config['site_url'].'/packapp/my/store/index.html?store_id='.$storeValue['store_id'];

                $rangeSort[] = $storeValue['Srange'];
            }
            array_multisort($rangeSort, SORT_ASC, $now_appoint['store_list']);
            $arr['long_lat'] = $long_lat;
        }
        if (empty($now_appoint)) {
            $this->returnCodeError('当前预约项不存在！');
        }
        $arr['now_appoint'] = $now_appoint;

        $this->returnCode(0,$arr);
    }

    // 订单提交页面信息
    public function order_detail() {
        $database_merchant_workers = D('Merchant_workers');
        $database_appoint_store = D('Appoint_store');
        $database_user_long_lat = D('User_long_lat');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_appoint_product =  D('Appoint_product');
        $database_area = D('Area');
        $database_appoint_order = D('Appoint_order');
        $database_appoint_category = D('Appoint_category');

        $merchant_workers_id = I('merchantWorkerId');
        $appoint_id = I('appoint_id');
        $order_from = I('order_from'); //fromWorkerOrder-技师代下单
        if (empty($appoint_id)) {
            $this->returnCodeError('当前服务不存在！');
        }
        $arr = array();

        $now_appoint = $database_appoint->get_appoint_by_appointId($appoint_id, 'hits-setInc');
        if (empty($now_appoint)) {
            $this->returnCodeError('当前预约项不存在！');
        }
        if ($now_appoint['start_time'] > $_SERVER['REQUEST_TIME']) {
            $this->returnCodeError('此单还未开始！');
        }

        if($order_from=='fromWorkerOrder'){
            $merchant_workers_id = $this->_uid;
        }

        // 自定义表单项
        $category = $database_appoint_category->get_category_by_id($now_appoint['cat_id']);
        if (empty($category['cue_field'])) {
            $category = $database_appoint_category->get_category_by_id($category['cat_fid']);
        }

        $cuefield_icon = [
            0 => 'txt_icon', // 单行文本
            1 => 'txt_icon',// 多行文本
            2 => 'map_icon',// 地图
            3 => 'select_icon',// 下拉选择框
            4 => 'number_icon',// 数字格式
            5 => 'mail_icon',// 邮件格式
            6 => 'datetime_icon',// 日期格式
            7 => 'datetime_icon',// 时间格式
            8 => 'phone_icon',// 手机格式
            9 => 'datetime_icon',// 日期时间格式
        ];
        if ($category['is_autotrophic']) {
            // 是否平台自营 0，商家入驻 1，平台自营 2，第三方入驻   非商家入驻单独处理
            $cuefield = [];
            if ($category) {
                $cuefield = unserialize($category['cue_field']);
                $site_url_appoint_icon = $this->config['site_url'] . '/static/appoint_new/icon_img/';
                foreach ($cuefield as &$val) {
                    $val['icon_img'] = $site_url_appoint_icon . $cuefield_icon[$val['type']] . '.png';
                    $sort[] = $val['sort'];
                    if (!isset($val['value'])) {
                        $val['value'] = '';
                    }
                }
                array_multisort($sort, SORT_DESC, $cuefield);
            }
            $now_city = D('Area')->get_area_by_areaId($this->config['now_city']);

            $arr['city_name'] = $now_city['area_name'];
            $arr['formData'] = $cuefield ? $cuefield : [];
            $this->returnCode(0, $arr);
        }

        $now_appoint_arr = [];
        $now_appoint_arr['appoint_type'] = $now_appoint['appoint_type'];
        $now_appoint_arr['is_store'] = $now_appoint['is_store'];
        // 产品列表
        $appointProduct = $database_appoint_product->get_productlist_by_appointId($appoint_id);
        $defaultAppointProduct = [];
        if ($appointProduct) {
            $arr['appoint_product'] = $appointProduct;
            if (empty($_POST['menuId'])) {
//                $defaultAppointProduct = $appointProduct[0];
                $this->returnCodeError('请选择对应预约规格！');
            } else {
                $defaultAppointProduct = $database_appoint_product->get_product_info($_POST['menuId']);
                $defaultAppointProduct = $defaultAppointProduct['detail'];
                if (empty($defaultAppointProduct)) {
                    $this->returnCodeError('当前所选预约规格不存在！');
                }
            }
            $arr['defaultAppointProduct'] = $defaultAppointProduct;
        }
        // 如果有默认选中的规格
        if ($defaultAppointProduct) {
            // 预约规格名称 存在赋值 不存在为空
            $now_appoint_arr['product_name'] = $defaultAppointProduct['name'] ? $defaultAppointProduct['name'] : '';
            // 预约规格定价 如果存在就有，不存在即-1
            $now_appoint_arr['product_payment_price'] = isset($defaultAppointProduct['payment_price']) ? $defaultAppointProduct['payment_price'] : -1;
            // 预约规格全价 如果存在就有，不存在即-1
            $now_appoint_arr['product_price'] = isset($defaultAppointProduct['price']) ? $defaultAppointProduct['price'] : -1;
        } else {
            $now_appoint_arr['product_name'] = '';
            $now_appoint_arr['product_payment_price'] = -1;
            $now_appoint_arr['product_price'] = -1;
            $now_appoint_arr['product_txt_tip'] = '';
        }
        // 显示全价
        $now_appoint_arr['total_price'] = floatval($now_appoint_arr['product_price'])>=0 ? $now_appoint_arr['product_price'] : $now_appoint['appoint_price'];
        if ($now_appoint['payment_status']==1) {
            // 显示应支付金额
            $now_appoint_arr['pay_price'] = floatval($now_appoint_arr['product_payment_price'])>=0 ? $now_appoint_arr['product_payment_price'] : $now_appoint['payment_money'];
            // 显示
            $now_appoint_arr['txt_tip'] = '预付金';
            $now_appoint_arr['deposit_txt_tip'] = '定金'.$this->config['Currency_symbol'].$now_appoint_arr['pay_price'];
            $now_appoint_arr['payment_money'] = $now_appoint['payment_money'];
            if (floatval($now_appoint_arr['product_price'])<0 && 0==$now_appoint['is_appoint_price'] && floatval($now_appoint_arr['total_price'])<=0) {
                $now_appoint_arr['txt_tip'] = '预付金';
                $now_appoint_arr['total_price'] = "面议({$now_appoint_arr['deposit_txt_tip']})";
                $now_appoint_arr['deposit_txt_tip'] = '';
            }
        } else {
            // 显示应支付金额
            $now_appoint_arr['pay_price'] = floatval($now_appoint_arr['product_price'])>=0 ? $now_appoint_arr['product_price'] : $now_appoint['appoint_price'];
            // 显示
            if (floatval($now_appoint_arr['product_price'])<0 && 0==$now_appoint['is_appoint_price']) {
                $now_appoint_arr['txt_tip'] = '一口价';
                if (floatval($now_appoint_arr['total_price'])<=0) {
                    $now_appoint_arr['total_price'] = '面议';
                }
            } else {
                $now_appoint_arr['txt_tip'] = '一口价';
            }
            $now_appoint_arr['deposit_txt_tip'] = '';
        }
        $now_appoint_arr['payment_status'] = $now_appoint['payment_status'];
        // 记录初始价格
        $now_appoint_arr['order_price'] = $now_appoint_arr['pay_price'];
        //商家优惠券
        if ($this->DEVICE_ID == 'wxapp') {
            $platform = 'wxapp';
        }
        elseif($this->is_weixin_browser){
            $platform = 'weixin';
        }
        elseif($this->is_app_browser){
            $platform = 'app';
        }
        else{
            $platform = 'wap';
        }
        $order_type = 'appoint';

//        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->_uid, $now_appoint['mer_id']);
        // 会员卡默认为空  暂时不进行处理
        $card_info = [];
        if (empty($card_info)||empty($card_info['discount']) || $card_info['discount'] == '0.00') {
            $card_info['discount'] = 10;
        }
        // 先折后减
        $now_appoint_arr['pay_price']  = ($now_appoint_arr['pay_price']) * $card_info['discount'] / 10;
        $now_appoint_arr['card_info'] = $card_info;

//        $tmp_order = $now_appoint;
//        if (floatval($now_appoint_arr['pay_price'])>0) {
//            $tmp_order['total_money'] = $now_appoint_arr['pay_price'];
//        } else {
//            $tmp_order['total_money'] = 0;
//        }
//        $tmp_order['uid'] = $this->_uid;

//        $merchant_coupon_id = $_POST['merchant_coupon_id']?$_POST['merchant_coupon_id']:0;
//        $system_coupon_id = $_POST['system_coupon_id']?$_POST['system_coupon_id']:0;
        // 此处暂时不支持优惠券
//        $merchant_coupon_id = 0;
//        $system_coupon_id = 0;

//        $tmp_order['freight_charge'] = 0;

        // 返回商家优惠券和系统优惠券跳转链接-改为不支持优惠券（去支付页面进行优惠券选择）
//        $appoint_base_url = D('Appoint')->appoint_base_url;
//        $arr['system_coupon_list'] = $this->config['site_url'].$appoint_base_url."pages/pay/coupon?appoint_id={$appoint_id}&type=appoint&coupon_type=system&coupon_id=".$system_coupon_id;
//        $arr['merchant_coupon_list'] =  $this->config['site_url'].$appoint_base_url."pages/pay/coupon?appoint_id={$appoint_id}&type=appoint&coupon_type=mer&coupon_id=".$merchant_coupon_id;
//
//        if (!empty($merchant_coupon_id)) {
//            $tmp_coupon = D('Card_new_coupon')->get_coupon_info($merchant_coupon_id);
//        } else {
//            $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, 'appoint');
//
//            $tmp_coupon = reset($card_coupon_list);
//        }
//        if (!empty($tmp_coupon)) {
//            $arr['has_merchant_coupon'] = '1';
//            $mer_coupon['had_id'] = $tmp_coupon['id'];
//            $mer_coupon['order_money'] = $tmp_coupon['order_money'];
//            $mer_coupon['discount'] = $tmp_coupon['discount'];
//        } else {
//            $arr['has_merchant_coupon'] = '0';
//            $mer_coupon = array();
//        }
//        $discount = empty($mer_coupon['discount']) ? 0 : $mer_coupon['discount'];
//        // 商家优惠券
//        $now_appoint_arr['pay_price']  = floatval($now_appoint_arr['pay_price']) - floatval($discount);
//        $arr['mer_coupon'] = $mer_coupon;

//        if(!empty($system_coupon_id)){
//            $tmp_coupon = D('System_coupon')->get_coupon_info($system_coupon_id);
//            if($tmp_coupon['is_discount']==1){
//                if($tmp_coupon['discount_type']==1){//减免运费
//                    $tmp_coupon['discount_money'] =round( $tmp_order['freight_charge'] * (100-$tmp_coupon['discount_value']*10)/100,2);
//                }else{
//                    $tmp_coupon['discount_money'] =round( $tmp_order['total_money'] * (100-$tmp_coupon['discount_value']*10)/100,2);
//                }
//            }else{
//                $tmp_coupon['discount_money'] = $tmp_coupon['discount'];
//            }
//        }else{
//            $phone = D('User')->where(array('uid' => $this->_uid))->getField('phone');
//            $now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type,$phone,$this->_uid, $platform, 'appoint');
//
//            $tmp_coupon = reset($now_coupon);
//        }
//
//        if($tmp_coupon){
//            $arr['has_system_coupon'] = '1';
//            $system_coupon['had_id'] = $tmp_coupon['id'];
//            //	$system_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
//            $system_coupon['order_money'] = $tmp_coupon['order_money'];
//
//            $system_coupon['discount'] = $tmp_coupon['discount_money'];
//            $system_coupon['is_discount'] = $tmp_coupon['is_discount'];
//            $system_coupon['discount_value'] = $tmp_coupon['discount_value'];
//
//            $system_coupon['discount_type'] = $tmp_coupon['discount_type'];
//            if($tmp_coupon['discount_type']=='1'){//如果是减免运费,减免的金额不能还大于运费金额
//                $system_coupon['discount'] = $tmp_order['freight_charge']>$system_coupon['discount']?$system_coupon['discount']:$tmp_order['freight_charge'];
//            }
//        }else{
//            $arr['has_system_coupon'] = '0';
//            $system_coupon =array();
//        }
//
//        $system_discount = empty($system_coupon['discount']) ? 0 : $system_coupon['discount'];
//        // 商家优惠券
//        $now_appoint_arr['pay_price']  = floatval($now_appoint_arr['pay_price']) - floatval($system_discount);
//
//        // 未使用优惠券钱金额
//        $now_appoint_arr['order_price'] = $tmp_order['total_money'];
//
//        $arr['system_coupon'] = $system_coupon;
        // 不支持优惠券后全部默认为空和不存在
        $arr['has_merchant_coupon'] = '0';
        $mer_coupon = array();
        $arr['mer_coupon'] = $mer_coupon;
        $arr['has_system_coupon'] = '0';
        $system_coupon =array();
        $arr['system_coupon'] = $system_coupon;


        $store_id = 0;
        if($order_from=='fromWorkerOrder'){
            $now_user = D('Merchant_workers')->field('merchant_worker_id as uid,name,merchant_store_id')->where(['merchant_worker_id'=>$this->_uid])->find();
            $store_id = $now_user['merchant_store_id'];
        }else {
            $now_user = $database_user->get_user($this->_uid);
            if (empty($this->user_session['phone']) && !empty($now_user['phone'])) {
                $_SESSION['user']['phone'] = $this->user_session['phone'] = $now_user['phone'];
            }
        }
        $arr['now_user'] = $now_user;

        $now_appoint_arr['store_list'] = $database_appoint_store->get_storelist_by_appointId($now_appoint['appoint_id'],$store_id);

        $long_lat = $database_user_long_lat->getLocation($_SESSION['openid']);
        if (!empty($long_lat) && $long_lat['lat'] && $long_lat['long']) {
            foreach ($now_appoint_arr['store_list'] as &$value) {
                $value['range'] = getDistance($value['lat'], $value['long'], $long_lat['lat'], $long_lat['long']);
                $value['range_txt'] = getRange($value['range']);
                $rangeSort[] = $value['range'];
                array_multisort($rangeSort, SORT_ASC, $now_appoint_arr['store_list']);
            }

            $arr['long_lat'] = $long_lat;
        }
        $now_city = $database_area->get_area_by_areaId($this->config['now_city']);

        $arr['city_name'] = $now_city['area_name'];

        $tmp_gap = $now_appoint['time_gap'];
        $beforeTime = $now_appoint['before_time'] > 0 ? ($now_appoint['before_time']) * 3600 : 0;
        $gap = $tmp_gap > 0 ? $tmp_gap * 60 : 1800;

        if (!empty($merchant_workers_id)) {
            // 预约开始时间 结束时间
            $merchant_workers_info = $database_merchant_workers->where(array('merchant_worker_id' => $merchant_workers_id))->find();
            $office_time = unserialize($merchant_workers_info['office_time']);
            if(isset($office_time['open']) && isset($office_time['close'])){
                $office_time = array($office_time);
            }

            $tmp_gap = $merchant_workers_info['time_gap'];
            // 发起预约时候的起始时间 还有提前多长时间可预约
            $beforeTime = $merchant_workers_info['before_time'] > 0 ? ($merchant_workers_info['before_time']) * 3600 : 0;
            $gap = $tmp_gap > 0 ? $tmp_gap * 60 : 1800;
        } else {
            $office_time = unserialize($now_appoint['office_time']);
            if(isset($office_time['open']) && isset($office_time['close'])){
                $office_time = array($office_time);
            }
        }

        $arr['time_gap'] = $tmp_gap;
        $tempTime = array();
        foreach ($office_time as $office_time_val) {
            // 如果设置的营业时间为0点到0点则默认是24小时营业
            $office_time_val['open'] = str_replace("：" ,':' ,$office_time_val['open']);
            $office_time_val['close'] = str_replace("：" ,':' ,$office_time_val['close']);
            if ((count($office_time_val) < 1)|| (($office_time_val['open'] == '00:00') && ($office_time_val['close']=='00:00'))) {
                $office_time_val['open'] = '00:00';
                $office_time_val['close'] = '24:00';
            } else {
                foreach ($office_time_val as $i => $time) {
                    $time['open'] = str_replace("：" ,':' ,$time['open']);
                    $time['close'] = str_replace("：" ,':' ,$time['close']);
                    if ($time['open'] == '00:00' && $time['close'] == '00:00') {
                        unset($office_time_val[$i]);
                    }
                }
            }
            $startTime = strtotime(date('Y-m-d') . ' ' . $office_time_val['open']);
            $endTime = strtotime(date('Y-m-d') . ' ' . $office_time_val['close']);
            for ($time = $startTime; $time < $endTime; $time = $time + $gap) {
                $tempKey = date('H:i', $time) . '-' . date('H:i', $time + $gap);
                $tempTime[$tempKey]['time'] = $tempKey;
                $tempTime[$tempKey]['start'] = date('H:i', $time);
                $tempTime[$tempKey]['end'] = date('H:i', $time + $gap);
                $tempTime[$tempKey]['order'] = 'no';
                if ((date('H:i') < date('H:i', $time - $beforeTime))) {
                    $tempTime[$tempKey]['order'] = 'yes';
                }
            }
        }

        $startTimeAppoint = $now_appoint['start_time'] > strtotime('now') ? $now_appoint['start_time'] : strtotime('now');

        if($tmp_gap > 0){
            $endTimeAppoint = $now_appoint['end_time'] > strtotime('+3 day') ? strtotime('+3 day') : $now_appoint['end_time'];
        }else{
            $endTimeAppoint = $now_appoint['end_time'] > strtotime('+30 day') ? strtotime('+30 day') : $now_appoint['end_time'];
        }


        $dateArray[date('Y-m-d', $startTimeAppoint)] = date('Y-m-d', $startTimeAppoint);
        $dateArray[date('Y-m-d', $endTimeAppoint)] = date('Y-m-d', $endTimeAppoint);
        for ($date = $startTimeAppoint; $date < $endTimeAppoint; $date = $date + 86400) {
            $dateArray[date('Y-m-d', $date)] = date('Y-m-d', $date);
        }
        ksort($dateArray);

        if($tmp_gap > 0){
            foreach ($dateArray as $i => $date) {
                $timeOrder[$date] = $tempTime;
            }
            ksort($timeOrder);


            foreach ($timeOrder as $i => $tem) {
                foreach ($tem as $key => $temval){
                    if ($temval['end'] == '00:00') {
                        $temval['end'] = '23:59';
                    }
                    if (strtotime($i . ' ' . $temval['end']) < strtotime('now') + $beforeTime && ($temval['order'] == 'yes')) {
                        $timeOrder[$i][$key]['order'] = 'no';
                    } elseif (strtotime($i . ' ' . $temval['end']) > strtotime('now') + $beforeTime + $gap && strtotime($i . ' ' . $temval['start']) > strtotime('now') + $beforeTime && ($temval['order'] == 'no')) {
                        $timeOrder[$i][$key]['order'] = 'yes';
                    }
                }
            }

            // 查询可预约时间点
            if ($now_appoint['is_store']) {
                $appoint_num = $database_appoint_order->get_worker_appoint_num($now_appoint['appoint_id'], $merchant_workers_id);
            } else {
                $appoint_num = $database_appoint_order->get_appoint_num($now_appoint['appoint_id']);
            }

            if (count($appoint_num) > 0) {
                foreach ($appoint_num as $val) {
                    $key = date('Y-m-d', strtotime($val['appoint_date']));
//                    if ($timeOrder[$key][$val['appoint_time']]['order'] != 'no') {
//                        if (isset($timeOrder[$key]) && (1 == $val['appointNum'])) {
//                            $timeOrder[$key][$val['appoint_time']]['order'] = 'all';
//                        }
//
//                    }
                    if (isset($timeOrder[$key][$val['appoint_time'].'-'.date('H:i',strtotime($val['appoint_time'])+$gap)]['order'])&&$timeOrder[$key][$val['appoint_time'].'-'.date('H:i',strtotime($val['appoint_time'])+$gap)]['order'] != 'no') {
                        $timeOrder[$key][$val['appoint_time'].'-'.date('H:i',strtotime($val['appoint_time'])+$gap)]['order'] ='all';
                    }
                }
            }

            $arr['timeOrder'] = $timeOrder;
        }else{

            $arr['timeOrder'] = $dateArray;
        }

        // 自定义表单项
        $cuefield = [];
        if ($category) {
            $cuefield = unserialize($category['cue_field']);
            $site_url_appoint_icon = $this->config['site_url'] . '/static/appoint_new/icon_img/';
            foreach ($cuefield as &$val) {
                $val['icon_img'] = $site_url_appoint_icon . $cuefield_icon[$val['type']] . '.png?t=01';
                $sort[] = $val['sort'];
            }
            array_multisort($sort, SORT_DESC, $cuefield);
        }

        $arr['formData'] = $cuefield ? $cuefield : [];
        if (isset($_POST['merchantWorkerId']) && !empty($merchant_workers_id)) {
            $_where['merchant_worker_id'] = $merchant_workers_id;
            $default_workers_info = $database_merchant_workers->where($_where)->find();
            $default_workers_info['avatar_path'] = str_replace(',','/',$default_workers_info['avatar_path']);
            $default_workers_info['desc'] = htmlspecialchars_decode($default_workers_info['desc']);
            $reward_order = M('Reward_order');
            $uid = $this->_uid;
            $order = $reward_order->field('order_id')->where(array('status' => 1, 'reward_id' => $merchant_workers_id, 'type' => 3, 'uid' => $uid))->find();
            if ($order) $default_workers_info['is_reward'] = 1;

            // 获取服务次数
            $supply_where['service_status'] = array('gt', 0);
            $supply_where['merchant_worker_id'] = $merchant_workers_id;
            $default_workers_info['finish_count'] = $database_appoint_order->where($supply_where)->count();
            if (!$default_workers_info['finish_count'] && $default_workers_info['appoint_num']>0) {
                $default_workers_info['finish_count'] = $default_workers_info['appoint_num'];
            }

            $arr['default_workers_info'] = $default_workers_info;
            $arr['default_store_id'] = $default_workers_info['merchant_store_id'];
        }

        $phone = D('User')->where(array('uid' => $this->_uid))->getField('phone');
        if ($this->user_session['phone'] || $phone) {
            if (!$this->user_session['phone']) {
                $this->user_session['phone'] = $phone;
            }
            $arr['pigcms_phone'] = substr($this->user_session['phone'], 0, 3) . '****' . substr($this->user_session['phone'], 7);
        } else {
            $arr['pigcms_phone'] = '您需要绑定手机号码';
        }
        $address_id = isset($_POST['adress_id']) ? intval($_POST['adress_id']) : cookie('userLocationId');
        $user_adress = D('User_adress')->get_one_adress($this->_uid, intval($address_id));

        $arr['user_adress'] = $user_adress;

        $now_appoint_arr['all_pic'] = $now_appoint['all_pic'];
        $now_appoint_arr['appoint_name'] = $now_appoint['appoint_name'];
        $now_appoint_arr['is_appoint_price'] = $now_appoint['is_appoint_price'];
        $now_appoint_arr['merchant_workers_id'] = $merchant_workers_id ? $merchant_workers_id : 0;
        $now_appoint_arr['appoint_worker_label'] = '选择预约的'.(C('config.appoint_worker_name')?:'技师');
        //积分展示
        $now_appoint_arr['score_text'] = '';
        if(C('customization.goods_show_score') && $scorePrice = floatval($now_appoint_arr['order_price'])){
            $scorePercent = D('Percent_rate')->scorePercent('appoint', $now_appoint['appoint_id'], $now_appoint['mer_id']);
            $score = bcmul($scorePrice, $scorePercent,2);
            if(C('config.score_round_two')){
                $score = sprintf('%.2f',floatval($score));
            }else{
                $score = round($score);
            }
            floatval($score) > 0 && $now_appoint_arr['score_text'] = C('config.score_name').'+'.$score;
        }
        $arr['now_appoint'] = $now_appoint_arr;
        $this->returnCode(0,$arr);
    }


    public function ajaxWorker(){
        $database_merchant_workers = D('Merchant_workers');
        $database_merchant_workers_appoint = D('Merchant_workers_appoint');
        $database_appoint_supply = D('Appoint_supply');

        $merchant_store_id = I('merchant_store_id');
        $appoint_id = I('appoint_id');
        $order_from = I('order_from'); //fromWorkerOrder-技师代下单

        $where['wa.merchant_store_id'] = $merchant_store_id;
        $where['wa.appoint_id'] = $appoint_id;
        $where['a.is_store'] = 1;
        if($order_from=='fromWorkerOrder'){
            $where['wa.merchant_worker_id'] = $this->_uid;
        }
        $merchant_workers_appoint_list = $database_merchant_workers_appoint
            ->join('as wa LEFT JOIN '.C('DB_PREFIX').'appoint as a ON a.appoint_id=wa.appoint_id')
            ->where($where)
            ->getField('wa.id,wa.merchant_worker_id');

        if ($merchant_workers_appoint_list) {
            $Map['merchant_worker_id'] = array('in', $merchant_workers_appoint_list);
            $Map['status'] = 1;
            $worker_list = $database_merchant_workers->where($Map)->select();
            $reward_order = M('Reward_order');
            $uid = $this->_uid;
            $supply_where['status'] = 3;
            $supply_where['store_id'] = $merchant_store_id;
            foreach($worker_list as &$worker){
                $worker['avatar_path'] = file_domain() . '/upload/appoint/' .str_replace(',','/',$worker['avatar_path']);
                //$worker['desc'] = htmlspecialchars_decode(strip_tags(html_entity_decode($worker['desc'])));
                $worker['desc'] = strip_tags(htmlspecialchars_decode(html_entity_decode($worker['desc'])));
                $worker['is_reward'] = 0;
                $worker['info'] = $worker['info'] ?: '';
                if($order_from!='fromWorkerOrder') {
                    $order = $reward_order->field('order_id')->where(array('status' => 1, 'reward_id' => $worker['merchant_worker_id'], 'type' => 3, 'uid' => $uid))->find();
                    if ($order) $worker['is_reward'] = 1;
                }
                // 获取服务次数
                $supply_where['worker_id'] = $worker['merchant_worker_id'];
                $worker['finish_count'] = $database_appoint_supply->where($supply_where)->count();
            }

            $arr['worker_list'] = $worker_list;
            $arr['appoint_worker_name'] = C('config.appoint_worker_name');
            $this->returnCode(0,$arr);
        } else {
            $arr['worker_list'] = array();
            $this->returnCode(0,$arr);
        }

    }

    public function ajaxWorkerTime(){
        $database_merchant_workers = D('Merchant_workers');
        $worker_id = I('worker_id');

        $appoint_id = $_POST['appoint_id'] + 0;
        $now_group = D('Appoint')->get_appoint_by_appointId($appoint_id, 'hits-setInc');
        // 预约开始时间 结束时间
       
        $office_time = unserialize($now_group['office_time']);
        if(!empty($worker_id)){
            $workersInfo = $database_merchant_workers
                ->where([
                    'merchant_worker_id' => $worker_id,
                    'status' => 1
                ])
                ->find();
            if(empty($workersInfo)){
                $this->returnCodeError(L_('当前工作人员不存在！'));
            }
            $workersTimes = unserialize($workersInfo['office_time']);
            if(!empty($workersTimes) ){
                $office_time = $workersTimes;
                $now_group['time_gap'] = $workersInfo['time_gap'];
            }
        }
        if(isset($office_time['open']) && isset($office_time['close'])){
            $office_time = array($office_time);
        }

        $tempTime = array();
        foreach ($office_time as $office_time_val) {
            // 如果设置的营业时间为0点到0点则默认是24小时营业
            $office_time_val['open'] = str_replace("：" ,':' ,$office_time_val['open']);
            $office_time_val['close'] = str_replace("：" ,':' ,$office_time_val['close']);
            if ((count($office_time_val) < 1)|| (($office_time_val['open'] == '00:00') && ($office_time_val['close']=='00:00'))) {
                $office_time_val['open'] = '00:00';
                $office_time_val['close'] = '24:00';
            } else {
                foreach ($office_time_val as $i => $time) {
                    $time['open'] = str_replace("：" ,':' ,$time['open']);
                    $time['close'] = str_replace("：" ,':' ,$time['close']);
                    if ($time['open'] == '00:00' && $time['close'] == '00:00') {
                        unset($office_time_val[$i]);
                    }
                }
            }
            // 发起预约时候的起始时间 还有提前多长时间可预约
            $tmp_gap = $now_group['time_gap'];
            $beforeTime = $now_group['before_time'] > 0 ? ($now_group['before_time']) * 3600 : 0;
            $gap = $tmp_gap > 0 ? $tmp_gap * 60 : 1800;

            $startTime = strtotime(date('Y-m-d') . ' ' . $office_time_val['open']);
            $endTime = strtotime(date('Y-m-d') . ' ' . $office_time_val['close']);
            
            if(empty($now_group['time_gap'])){
                $tempKey = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                $tempTime[$tempKey]['time'] = $tempKey;
                $tempTime[$tempKey]['start'] = date('H:i', $startTime);
                $tempTime[$tempKey]['end'] = date('H:i', $endTime);
                $tempTime[$tempKey]['order'] = 'yes';
            }else{
                for ($time = $startTime; $time < $endTime; $time = $time + $gap) {
                    $tempKey = date('H:i', $time) . '-' . date('H:i', $time + $gap);
                    $tempTime[$tempKey]['time'] = $tempKey;
                    $tempTime[$tempKey]['start'] = date('H:i', $time);
                    $tempTime[$tempKey]['end'] = date('H:i', $time + $gap);
                    $tempTime[$tempKey]['order'] = 'no';
                    if ((date('H:i') < date('H:i', $time - $beforeTime))) {
                        $tempTime[$tempKey]['order'] = 'yes';
                    }
                }  
            }
        }

        $startTimeAppoint = $now_group['start_time'] > strtotime('now') ? $now_group['start_time'] : strtotime('now');
        if($tmp_gap >= 0){
            $endTimeAppoint = $now_group['end_time'] > strtotime('+3 day') ? strtotime('+3 day') : $now_group['end_time'];
        }else{
            $endTimeAppoint = $now_group['end_time'] > strtotime('+30 day') ? strtotime('+30 day') : $now_group['end_time'];
        }

        $dateArray[date('Y-m-d', $startTimeAppoint)] = date('Y-m-d', $startTimeAppoint);
        $dateArray[date('Y-m-d', $endTimeAppoint)] = date('Y-m-d', $endTimeAppoint);
        for ($date = $startTimeAppoint; $date < $endTimeAppoint; $date = $date + 86400) {
            $dateArray[date('Y-m-d', $date)] = date('Y-m-d', $date);
        }
        ksort($dateArray);

        // if($tmp_gap >= 0){
        foreach ($dateArray as $i => $date) {
            $timeOrder[$date] = $tempTime;
        }
        ksort($timeOrder);
        $database_appoint_order = D('Appoint_order');
  
        foreach ($timeOrder as $i => $tem) {

            // 验证时间段是否已预约过
            $cindition_a['service_status'] =  0;
            $cindition_a['is_del'] =  0;
            $cindition_a['merchant_worker_id'] =  $worker_id;
            $cindition_a['appoint_id'] = $appoint_id;
            $cindition_a['appoint_date'] = $i;
            $cindition_a['paid'] =  array('not in',[2,3]);
            $appoint_times = $database_appoint_order->where($cindition_a)->getField('appoint_time', true);
            
            foreach ($tem as $key => $temval){
                if ($temval['end'] == '00:00') {
                    $temval['end'] = '23:59';
                }
                if (strtotime($i . ' ' . $temval['end']) < strtotime('now') + $beforeTime && ($temval['order'] == 'yes')) {
                    $timeOrder[$i][$key]['order'] = 'no';
                } elseif (strtotime($i . ' ' . $temval['end']) > strtotime('now') + $beforeTime + $gap && strtotime($i . ' ' . $temval['start']) > strtotime('now') + $beforeTime && ($temval['order'] == 'no')) {
                    $timeOrder[$i][$key]['order'] = 'yes';
                }
                if(in_array($temval['start'], $appoint_times)){
                    $timeOrder[$i][$key]['order'] = 'no';
                }
            }
        }

        // 查询可预约时间点
        $appoint_num = D('Appoint_order')->get_worker_appoint_num($now_group['appoint_id'], $worker_id);

        if (count($appoint_num) > 0) {
            foreach ($appoint_num as $val) {
                $key = date('Y-m-d', strtotime($val['appoint_date']));
                if(isset($timeOrder[$key])) {
                    foreach ($timeOrder[$key] as $tk => &$t) {
                        if ($t['order'] != 'no' && $t['start'] == $val['appoint_time']) {

                            $t['order'] = 'all';
                        }
                    }
                }
            }
        }
        $arr['tmp_gap'] = $tmp_gap;
        $arr['timeOrder'] = $timeOrder;
        $this->returnCode(0,$arr);
    }

    // 订单提交保存
    public function order_save() {
        $database_appoint_store = D('Appoint_store');
        $database_appoint = D('Appoint');
        $database_appoint_order = D('Appoint_order');
        $database_appoint_category = D('Appoint_category');

        $merchant_workers_id = I('merchantWorkerId') ?: I('worker_id');
        $appoint_id = I('appoint_id');
        $order_from = I('order_from'); //fromWorkerOrder-技师代下单
        $user_phone = I('user_phone'); //技师代下单用户手机号
        if (empty($appoint_id)) {
            $this->returnCodeError('当前服务不存在！');
        }
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $uid = $this->_uid;
        
        //技师代下单，查询用户是否存在，不存在注册新用户后下单
        if($order_from=='fromWorkerOrder'){
            if(!preg_match('/^1[3-9]{1}[0-9]{9}$/',$user_phone)) {
                $this->returnCodeError('客户手机号格式错误！');
            }
            if($merchant_workers_id!=$this->_uid){
                $this->returnCodeError('无法下其他技师的服务！');
            }
            $date_user['phone'] = $user_phone;
            $date_user['client'] = remove_xss($_POST['client']);
            $date_user['source'] ='sms';
            $date_user['spread_code'] = strval($_POST['spread_code']);
            $date_user['nickname'] = substr_replace($user_phone, '****', 3, 4);
            $reg_user  = D("User")->autoreg($date_user,false);
            if($reg_user['error_code']){
                $this->returnCodeError($reg_user['msg']);
            }
            $uid = $reg_user['uid'];
        }

        $now_appoint = $database_appoint->get_appoint_by_appointId($appoint_id, 'hits-setInc');
        if (empty($now_appoint)) {
            $this->returnCodeError('当前预约项不存在！');
        }
        if ($now_appoint['start_time'] > $_SERVER['REQUEST_TIME']) {
            $this->returnCodeError('此单还未开始！');
        }
        if (1==$now_appoint['appoint_type'] && intval($_POST['adress_id'])>0) {
            if (empty($_POST['custom_field'])) {
                $_POST['custom_field'] = [];
            }
            if($order_from=='fromWorkerOrder'){
                $address_info = M('Merchant_workers_address')->where(['adress_id' => $_POST['adress_id']])->find();
            }else{
                $address_info = M('User_adress')->where(['adress_id' => $_POST['adress_id']])->find();
            }
            
            if ($address_info) {
                $province_txt = D('Area')->get_area_by_areaId($address_info['province'],false);
                $province_txt = $province_txt ? $province_txt['area_name'] : '';
                $city_txt = D('Area')->get_area_by_areaId($address_info['city'],false);
                $city_txt = $city_txt ? $city_txt['area_name'] : '';
                $area_txt = D('Area')->get_area_by_areaId($address_info['area'],false);
                $area_txt = $area_txt ? $area_txt['area_name'] : '';
                $address_arr = [];
                $address_arr[] = $province_txt;
                $address_arr[] = $city_txt;
                $address_arr[] = $area_txt;
                $address_arr[] = $address_info['adress'];
                $address_arr[] = $address_info['detail'];
                $address = implode(' ', $address_arr);
                $custom_field = [
                    "name" => "服务位置",
                    "type" => "2",
                    "long" => $address_info['longitude'] ? $address_info['longitude'] : '',
                    "lat" => $address_info['latitude'] ? $address_info['latitude'] : '',
                    "value" => $address,
                    "address" => $address,
                    'phone' => $address_info['phone'],
                    'showName' => $address_info['name'],
                    'province' => $address_info['province'],
                    'city' => $address_info['city'],
                    'area' => $address_info['area'],
                ];
                array_unshift($_POST['custom_field'], $custom_field);
            }
        }
        // 自定义表单项
        $category = $database_appoint_category->get_category_by_id($now_appoint['cat_id']);
        $appoint_category = $category;
        if (empty($category['cue_field'])) {
            $appoint_category = $database_appoint_category->get_category_by_id($category['cat_fid']);
        }
        $now_time = time();
        $order_detail_qrcode = C('config.site_url').'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.C('config.site_url').'/packapp/plat/pages/appoint/goods/submitDetail?orderid=';
        if ($appoint_category['is_autotrophic']) {
                // 是否平台自营 0，商家入驻 1，平台自营 2，第三方入驻   非商家入驻单独处理
                $data['type'] = 1;
                $data['cat_id'] = $now_appoint['cat_id'];
                $data['cat_fid'] = $now_appoint['cat_fid'];
                $data['uid'] = $uid;
                $data['appoint_id'] = $now_appoint['appoint_id'];
                $data['appoint_name'] = $now_appoint['appoint_name'];
                $data['appoint_pic_content'] = $now_appoint['appoint_pic_content'];
                if (isset($_POST['custom_field']) && $_POST['custom_field']) {
                    $custom_field = $_POST['custom_field'];
                    foreach ($custom_field as $items) {
                        if ($items['iswrite']==1 && !$items['value']) {
                            $this->returnCodeError("{$items['name']}为必填项");
                            break;
                        }
                    }
                    $data['cue_field'] = serialize($_POST['custom_field']);
                }
                $appoint_where = array(
                    'cat_id' => $now_appoint['cat_id'],
                    'start_time' => array('elt', $now_time),
                    'end_time' => array('gt', $now_time),
                    'appoint_status' => 0,
                    'check_status' => 1,
                );

                $appoint_info = $database_appoint->field('appoint_id, mer_id, is_store')->where($appoint_where)->select();
                if ($appoint_info && 1==count($appoint_info)) {
                    $appoint_info = reset($appoint_info);
                    $data['mer_id'] = $appoint_info['mer_id'];
                    // 查询下关联店铺 取默认第一个
                    if ($appoint_info['is_store']!=1) {
                        $store_where = array(
                            'appoint_id' => $appoint_info['appoint_id']
                        );
                        $store = $database_appoint_store->where($store_where)->order('store_sort DESC,store_id DESC')->find();
                        if ($store>0) {
                            $data['store_id'] = $store['store_id'];
                            // 查询下店铺技师关联技师
                            $d_merchant_workers_appoint = M('Merchant_workers_appoint');
                            $work = $d_merchant_workers_appoint->where(array('merchant_store_id' => $store['store_id']))->order('id ASC')->find();
                            if ($work) {
                                $data['merchant_worker_id'] = $work['merchant_worker_id'];
                            }
                        }
                    }
                }
                if($order_from=='fromWorkerOrder'){
                    $data['place_order_worker'] = $this->_uid;
                }
                $result = $database_appoint_order->platform_save_post_form($data);
                if($result['status']){
                    if($this->is_weixin_browser){
                        $from_type = 0;
                    }
                    elseif($this->is_wxapp_browser){
                        $from_type = 3;
                    }
                    elseif($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
                        $from_type = 2;
                    }
                    elseif($this->is_app_browser){
                        $from_type = 1;
                    }
                    else{
                        $from_type = 0;
                    }
                    D('User_behavior_appoint')->add_record($data['cat_id'],0,$uid,$appoint_category['cat_name'],1,time(),$from_type,$result['order_id']);
                }

				$href = $this->config['site_url'] . D('House_village')->base_url . 'pages/appoint/goods/submitDetail?orderid=' . $result['order_id'].'&appoint_id=' . $appoint_info['appoint_id'];
                $order_detail_qrcode .= $result['order_id'];
                $hrefArr = ['href' => $href, 'order_id' => $result['order_id'], 'order_detail_qrcode' => $order_detail_qrcode];

                $this->returnCode(0,$hrefArr);
            } else {
	        if ($now_appoint['end_time'] < $_SERVER['REQUEST_TIME']) {
		        $this->returnCodeError('此单已经结束！');
	        }
	        $database_appoint_product = D('Appoint_product');
	        $paymentStatus = 0;
	        if ($now_appoint['payment_status'] && floatval($now_appoint['payment_money'])) {
		        $paymentStatus = 1;
	        }
	        $noServiceAppointOrder = D('Appoint_order')->where([
		        'appoint_id' => $appoint_id,
		        'uid' => $uid,
				'is_del' => 0,
		        'service_status' => 0,
		        'is_apply_refund' => 0,
		        'order_time' => ['between',[time()-24*3600, time()]]
	        ])->find();
			if($noServiceAppointOrder) {
				$this->returnCodeError('您尚有未服务订单，不可多次下单！');
			}
            // 产品列表
            if ($_POST['product_id']>0) {
                $paymentStatus = 0;
                $defaultAppointProduct = D('Appoint_product')->get_product_info($_POST['product_id']);
               
                if(empty($defaultAppointProduct['detail'])){
                    $this->returnCodeError('当前规格不存在！');
                }
                $defaultAppointProduct = $defaultAppointProduct['detail'];
                if(floatval($defaultAppointProduct['payment_price'])){
                    $paymentStatus = 1;
                }
            } else {
                $defaultAppointProduct = array();
                // 产品列表
                $appointProduct = $database_appoint_product->get_productlist_by_appointId($appoint_id);
                if ($appointProduct) {
                    $this->returnCodeError('请选择规格！');
                }
            }

            $now_appoint_arr = [];
            $now_appoint_arr['appoint_type'] = $now_appoint['appoint_type'];
            // 如果有默认选中的规格
            $now_appoint_arr = [];
            if ($defaultAppointProduct&&$defaultAppointProduct['status']==1) {
                // 预约规格名称 存在赋值 不存在为空
                $now_appoint_arr['product_name'] = $defaultAppointProduct['detail']['name']?? '';
                // 预约规格定价 如果存在就有，不存在即-1
                $now_appoint_arr['product_payment_price'] = $defaultAppointProduct['detail']['payment_price']?? -1;
                // 预约规格全价 如果存在就有，不存在即-1
                $now_appoint_arr['product_price'] = $defaultAppointProduct['detail']['price']?? -1;
            } else {
                $now_appoint_arr['product_name'] = '';
                $now_appoint_arr['product_payment_price'] = -1;
                $now_appoint_arr['product_price'] = -1;
            }
            // 显示全价
            $now_appoint_arr['total_price'] = floatval($now_appoint_arr['product_price'])> 0 ? $now_appoint_arr['product_price'] : $now_appoint['appoint_price'];
            if ($now_appoint['payment_status']==1) {
                // 显示应支付金额
                $now_appoint_arr['pay_price'] = floatval($now_appoint_arr['product_payment_price'])> 0 ? $now_appoint_arr['product_payment_price'] : $now_appoint['payment_money'];
            } else {
                // 显示应支付金额
                $now_appoint_arr['pay_price'] = floatval($now_appoint_arr['product_price'])> 0 ? $now_appoint_arr['product_price'] : $now_appoint['appoint_price'];
            }
            $now_appoint_arr['payment_status'] = $now_appoint['payment_status'];

            $now_appoint['order_price'] = $now_appoint_arr['pay_price'];

            //商家优惠券
            if ($this->DEVICE_ID == 'wxapp') {
                $platform = 'wxapp';
            }
            elseif($this->is_weixin_browser){
                $platform = 'weixin';
            }
            elseif($this->is_app_browser){
                $platform = 'app';
            }
            else{
                $platform = 'wap';
            }
            $order_type = 'appoint_new';

            //        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($uid, $now_appoint['mer_id']);
            // 会员卡默认为空  暂时不进行处理
            $card_info = [];
            if (empty($card_info)||empty($card_info['discount']) || $card_info['discount'] == '0.00') {
                $card_info['discount'] = 10;
            }
            // 先折后减
            $now_appoint_arr['pay_price']  = ($now_appoint_arr['pay_price']) * $card_info['discount'] / 10;
            $now_appoint_arr['card_info'] = $card_info;

            $service_date = trim($_POST['service_date']);
            $service_time = trim($_POST['service_time']);
            if ($service_date==-1 || !$service_date) {
                $this->returnCodeError('请选择预约时段！');
            }
            if ($service_time==-1 || !$service_time) {
                $this->returnCodeError('请选择预约时段！');
            }

            $custom_field = $_POST['custom_field'];
            if (!empty($custom_field)) {
                foreach ($custom_field as $items) {
                    if ($items['iswrite']==1 && !$items['value']) {
                        $this->returnCodeError("{$items['name']}为必填项");
                        break;
                    }
                }
            }

            $now_appoint['cue_field'] = serialize($_POST['custom_field']);
            $now_appoint['appoint_date'] = $service_date;
            $now_appoint['appoint_time'] = $service_time;
            $now_appoint['store_id'] = !empty($_POST['store_id']) ? $_POST['store_id'] + 0 : 0;
            $now_appoint['product_id'] = $_POST['product_id'] ? $_POST['product_id'] + 0 : 0;

            if (C('config.open_single_spread')==1) {
                // 处理分销人
                if (isset($_POST['openid']) && $_POST['openid']) {
                    $distributor_user = D('User')->get_user($_POST['openid'],'openid');
                    // 验证是否有分销权限
                    $distributor = M('Distributor')->where(array('uid'=>$distributor_user['uid'],'status'=>1))->find();
                    if ($distributor) {
                        $now_appoint['distributor_uid'] = intval($distributor_user['uid']);
                    }
                }elseif (isset($_POST['share_uid']) && $_POST['share_uid']) {
                    // 验证是否有分销权限
                    $distributor = M('Distributor')->where(array('uid'=>$_POST['share_uid'],'status'=>1))->find();
                    if ($distributor) {
                        $now_appoint['distributor_uid'] = intval($_POST['share_uid']);
                    }
                }
            }
            $phone = D('User')->where(array('uid' => $uid))->getField('phone');
            if (empty($this->user_session['phone']) && !$phone) {
                $arr = array('url' =>U('My/bind_user',array('referer'=>urlencode(U('Appoint/order',$_GET)))));
                $array = array(
                    'errorCode'=>'10070013',
                    'errorMsg'=>'您需要绑定手机号码！',
                    'result'=>$arr
                );
                echo json_encode($array);
                exit();
            }
            if(!empty($now_appoint['is_store']) && empty($now_appoint['store_id'])){
                $this->returnCodeError('店铺不能为空！');
            }
            if (1==$now_appoint['payment_status']) {
                $now_appoint['payment_money'] = $now_appoint_arr['pay_price'];
            } else {
                $now_appoint['payment_money'] = 0;
            }
            if(empty($now_appoint['payment_money']) && empty($now_appoint['product_payment_price'])){
                $now_appoint['paid'] = 1;
				if($defaultAppointProduct) {
					if($defaultAppointProduct['price']) {
						$now_appoint['paid'] = 0;//一口价要求必须付钱
					}
				}else if($now_appoint['appoint_price']){//一口价要求必须付钱
					$now_appoint['paid'] = 0;
				}
            }
            $now_appoint['content'] = trim($_POST['content']) ? trim($_POST['content']) : '';
            if($order_from=='fromWorkerOrder'){
                $now_appoint['place_order_worker'] = $this->_uid;
            }
            $result = $database_appoint_order->save_post_form($now_appoint, $uid, 0, $merchant_workers_id);
            if ($result['error'] == 1) {
                $this->returnCodeError($result['msg']);
            }
            $category = $database_appoint_category->get_category_by_id($now_appoint['cat_id']);
            if($this->is_weixin_browser){
                $from_type = 0;
            }
            elseif($this->is_wxapp_browser){
                $from_type = 3;
            }
            elseif($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
                $from_type = 2;
            }
            elseif($this->is_app_browser){
                $from_type = 1;
            }
            else{
                $from_type = 0;
            }
            D('User_behavior_appoint')->add_record($now_appoint['cat_id'],$appoint_id,$uid,$category['cat_name'],1,time(),$from_type,$result['order_id']);
            $now_order = $database_appoint_order->where(array('order_id' => $result['order_id']))->find();
	        $recordContent['content'] = [[
		        'title' => '下单时间',
		        'value' => date('Y-m-d H:i:s'),
		        'type' => 'text'
	        ]];
	        D('Appoint_order_records')->addOrderRecord($now_order['mer_id'], $now_order['order_id'], '用户下单', $recordContent);

            // 验证时间段是否已预约过
            $cindition_a['service_status'] =  0;
            $cindition_a['is_del'] =  0;
            $cindition_a['merchant_worker_id'] =  $now_order['merchant_worker_id'];
            $cindition_a['appoint_id'] =  $now_order['appoint_id'];
            $cindition_a['appoint_date'] =  $now_appoint['appoint_date'];
            $cindition_a['appoint_time'] =  $now_appoint['appoint_time'];
            $cindition_a['order_id'] =  array('neq',$result['order_id']);
            $cindition_a['paid'] =  array('not in',[2,3]);
            if(!empty($now_order['merchant_worker_id'])){
                $exit_appoint = $database_appoint_order->where($cindition_a)->find();
                if ($exit_appoint) {
                    $this->returnCodeError('该时间段已有预约，请选择其他时间段！');
                } 
            }
            $order_detail_qrcode .= $result['order_id'];
            // 如果需要定金
            if ($paymentStatus) {
                $href = $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $result['order_id'] . '&type=appoint';
                $hrefArr = ['href' => $href, 'order_id' => $result['order_id'], 'page_type' => 'pay', 'order_detail_qrcode' => $order_detail_qrcode];
            } else {
                $database_appoint_supply = D('Appoint_supply');
                $supply_data['appoint_id'] = $now_order['appoint_id'];
                $supply_data['mer_id'] = $now_order['mer_id'];
                $supply_data['store_id'] = $now_order['store_id'];
                $supply_data['create_time'] = time();
                $supply_data['worker_id'] = $now_order['merchant_worker_id'];
                $supply_data['start_time'] = $_SERVER['REQUEST_TIME'];
                $supply_data['paid'] = $now_order['paid'];
                if($now_order['merchant_worker_id']){//服务员工服务费
                    $supply_data['commission'] = D('Merchant_workers')->appointServiceFee($now_order['merchant_worker_id'], $now_appoint_arr['total_price']);
                    $supply_data['status'] =  2;
                }else{
                    $supply_data['status'] =  1;
                }
                $supply_data['uid'] =  $now_order['uid'];
                $supply_data['pay_type'] = $now_order['pay_type'];
                $supply_data['order_time'] = $now_order['order_time'];
                $supply_data['deliver_cash'] = floatval($now_order['product_price'] - $now_order['product_card_price'] - $now_order['product_merchant_balance'] - $now_order['product_balance_pay'] - $now_order['product_payment_money'] - $now_order['product_score_deducte'] - $now_order['product_coupon_price']);
                $supply_data['order_id'] = $now_order['order_id'];
                $database_appoint_supply->data($supply_data)->add();

                // 通知店员
                if($now_order['store_id']){
                    D('Merchant_store_staff')->sendMsgAppointOrder($now_order);
                }
				
				$href = $this->config['site_url'] . D('House_village')->base_url . 'pages/appoint/goods/submitDetail?orderid=' . $result['order_id'].'&appoint_id=' . $now_order['appoint_id'];
				
                $hrefArr = ['href' => $href, 'order_id' => $result['order_id'], 'order_detail_qrcode' => $order_detail_qrcode];
            }
            $this->returnCode(0,$hrefArr);
        }
    }
    // 生成技师打赏金钱信息
    public function worker_reward_pay_order() {
        $merchant_worker_id = (int)I('merchant_worker_id');
        $uid = (int)$this->_uid;
        if($uid <= 0){
            $this->returnCode('20044010');
        }
        // 查询一下用户是否支付过
        $info_pay = M('Reward_order')->where(array('uid'=>$uid,'reward_id'=>$merchant_worker_id, 'status' => 1, 'type' => 3))->find();
        // 如果已经打赏过了，提示打赏过了
        if (!empty($info_pay)) {
            $this->returnCodeError('已打赏');
        }
        // 获取技师信息
        $worker_info = M('Merchant_workers')->where(array('merchant_worker_id'=>$merchant_worker_id))->field('name, is_reward, reward_money')->find();
        // 该技师不存在
        if (empty($worker_info)) {
            exit(json_encode(array('error'=>2,'msg'=>'该'.($this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : '技师').'不存在')));
        }
        $userInfo = D('User')->where(array('uid'=>$uid))->field('uid, now_money')->find();
        // 如果余额不足提示去充值
        $reward_money = floatval($worker_info['reward_money']);
        $now_money = floatval($userInfo['now_money']);
        $data = array(
            'now_money' => $userInfo['now_money'],  // 当前余额
            'reward_money' => $worker_info['reward_money'],  // 打赏金额
        );
        if($reward_money < $now_money){
            $data['difference'] = $reward_money - $now_money; // 差额
            $arr = array('error'=>3,'msg'=>'当前余额不足请充值', 'info' => $data);
            $this->returnCode(0,$arr);
        }
        $data['difference'] = $now_money - $reward_money; // 差额

        $this->returnCode(0, $data);
    }


    // 技师打赏支付
    public function worker_reward_pay() {
        $merchant_worker_id = (int)I('merchant_worker_id');
        $uid = (int)$this->_uid;
        if($uid <= 0){
            $this->returnCode('20044010');
        }
        // 查询一下用户是否支付过
        $info_pay = M('Reward_order')->where(array('uid'=>$uid,'reward_id'=>$merchant_worker_id, 'status' => 1, 'type' => 3))->find();
        // 如果已经打赏过了，提示打赏过了
        if (!empty($info_pay)) {
            $this->returnCodeError('已打赏');
        }
        // 获取文章信息
        $worker_info = M('Merchant_workers')->where(array('merchant_worker_id'=>$merchant_worker_id))->field('name, is_reward, reward_money')->find();
        // 该资讯已被删除
        if (empty($worker_info)) {
            exit(json_encode(array('error'=>2,'msg'=>'该资讯已被删除')));
        }
        $userInfo = M('User')->where(array('uid'=>$uid))->field('uid, nickname, now_money')->find();
        // 如果余额不足提示去充值
        $reward_money = floatval($worker_info['reward_money']);
        $now_money = floatval($userInfo['now_money']);
        if($reward_money > $now_money){
            $data = array(
                'now_money' => $userInfo['now_money'],  // 当前余额
                'reward_money' => $worker_info['reward_money'],  // 打赏金额
                'difference' => $reward_money - $now_money,  // 差额
            );
            $arr = array('error'=>3,'msg'=>'当前余额不足请充值', 'info' => $data);
            $this->returnCode(0,$arr);
        }
        D()->startTrans();
        $dec_money = D('User')->user_money($this->_uid, $reward_money, "打赏技工【".$worker_info['name']."】扣除余额 ". $reward_money .C('config.Currency_txt')."");
        if(!$dec_money['error_code']){
            $order_info = array(
                'reward_id' => $merchant_worker_id,
                'uid' => $uid,
                'money' => $reward_money,
                'pay_type' => 3,
                'status' => 1,
                'type' => 3,
                'add_time' => time()
            );
            $reward_order = M('Reward_order')->data($order_info)->add();
            if (!$reward_order) {
                D()->rollback();
                $this->returnCodeError('打赏失败请重试');
            }
            // 对应添加技师记录
            // 处理一下操作用户的明后才能
            $user_name = $userInfo['nickname'];
            $msg = $this->add_row($merchant_worker_id, 1, $reward_money, $user_name . '打赏您' . $reward_money . C('config.Currency_txt'));
            if (!$msg) {
                D()->rollback();
                $this->returnCodeError('打赏失败请重试');
            }
            $platform_reward_technician_percentage = $this->config['platform_reward_technician_percentage'];
            if ($platform_reward_technician_percentage) {
                // 扣除平台抽成
                $platform_reward_money = ($reward_money * floatval($platform_reward_technician_percentage)) / 100;
                $platform_reward_money = round($platform_reward_money, 2);
                $msg = $this->add_row($merchant_worker_id, 2, $platform_reward_money, '平台抽成，扣除余额' . $platform_reward_money . C('config.Currency_txt'));
                if (!$msg) {
                    D()->rollback();
                    $this->returnCodeError('打赏失败请重试');
                }
            }
            D()->commit();
            $arr = array('error'=>1,'msg'=>'打赏成功');
            $this->returnCode(0, $arr);
        }else{
            D()->rollback();
            $arr = array('error'=>2,'msg'=> $dec_money['msg']);
            $this->returnCode(0, $arr);
        }
    }

    // 添加技师余额记录
    public function add_row($merchant_worker_id,$type,$money,$msg,$record_ip = true,$ask=0,$ask_id=0,$admin=false,$time =0 ){
        if(!$time){
            $time = $_SERVER['REQUEST_TIME'];
        }
        $data_merchant_workers_money['merchant_worker_id'] = $merchant_worker_id;
        $data_merchant_workers_money['type'] = $type;
        $data_merchant_workers_money['money'] = $money;
        $data_merchant_workers_money['desc'] = $msg;
        $data_merchant_workers_money['time'] = $time;
        if($_SESSION['system']['id'] && $admin){
            $data_merchant_workers_money['admin_id'] = $_SESSION['system']['id'];
        }
        if($record_ip){
            $data_merchant_workers_money['ip'] = get_client_ip(0);
        }

        if(M('Merchant_workers_money')->data($data_merchant_workers_money)->add()){
            return true;
        }else{
            return false;
        }
    }


    /*预约详情*/
    public function appoint_order(){
        if(empty($this->_uid)){
            $this->returnCode('20044010');
        }
        $order_id = I('order_id','','intval');
        if (!$order_id) {
            $this->returnCodeError('缺少订单id');
        }

        $now_order = D('Appoint_order')->get_order_detail_by_id($this->_uid,$order_id,true);

        $now_appoint = D('Appoint')->get_appoint_by_appointId($now_order['appoint_id'], 'hits-setInc', false, $now_order['store_id']);
        if(empty($now_order)){
            $this->returnCodeError('当前订单不存在');
        }
        $now_order['order_type'] = 'appoint';
        $laste_order_info = D('Tmp_orderid')->get_laste_order_info($now_order['order_type'],$now_order['order_id']);
        if(!$now_order['paid'] && !empty($laste_order_info)) {
            if ($laste_order_info['pay_type']=='weixin') {
                $redirctUrl = C('config.site_url') . '/wap.php?g=Wap&c=Pay&a=weixin_back&order_type='.$now_order['order_type'].'&order_id=' . $laste_order_info['orderid'];
                file_get_contents($redirctUrl);
                $now_order = D('Appoint_order')->get_order_detail_by_id($this->_uid,intval($order_id),true);
            }
        }
        if(empty($now_order)){
            $this->returnCodeError('当前订单不存在');
        }
        $now_supply = D('Appoint_supply')->where(array('order_id'=>intval($order_id),'status'=>3))->find();
        $appoint_cate = D('Appoint_category')->where(array('cat_id'=>$now_appoint['cat_id']))->find();
        $appoint_cate['list_pic'] = replace_file_domain(C('config.site_url').'/upload/system/'.$appoint_cate['cat_pic']);
        
        //获取自定义用户名称和手机号
        $cue_field = $now_order['cue_field']?unserialize($now_order['cue_field']):[];
        
        //获取店铺/商家地址
        $address = '';
        $addr_info = [];
        if($now_order['store_id']){
            $addr_info = D('Merchant_store')->field('province_id,city_id,area_id,street_id,adress as address,name')->where(['store_id'=>$now_order['store_id']])->find();
        }
        $store_name = '';
        if($addr_info){
            $store_name = $addr_info['name'];
        }
        if ((!$addr_info||empty($addr_info))&&$now_order['mer_id']){
            $addr_info = D('Merchant')->field('province_id,city_id,area_id,street_id,address')->where(['mer_id'=>$now_order['mer_id']])->find();
        }
        if($addr_info){
            $address = $addr_info['address'];
            $street_id = $addr_info['street_id'];
            unset($addr_info['address']);
            unset($addr_info['street_id']);
            $area_ids = array_values($addr_info);
            $area_name = D('Area')->field('area_id,area_name')->where(['area_id'=>['in',$area_ids]])->select();
            if($area_name){
                $area_arr = array_column($area_name,'area_name','area_id');
                $area_info = '';
                foreach ($addr_info as $item){
                    $area_info .= isset($area_arr[$item])?$area_arr[$item]:'';
                }
                $street_name = '';
                if($street_id){
                    $street_name = D('Area_street')->where(['area_id'=>$street_id])->getField('area_name');
                    $street_name = $street_name?$street_name:'';
                }
                $address = $area_info?($area_info.$street_name.$address):$address;
            }
        }
        
        // 0 待支付  1 已支付
        if (0==$now_order['paid'] && 1==$now_order['payment_status'] && 0==$now_order['is_del']) {
            $paid = 0;
        } else {
            $paid = 1;
        }

        $now_order_new = [
            'order_id' => $order_id, // 订单id
            'real_orderid' => $now_order['real_orderid'], // 订单长id
            'order_time_txt' => date('Y-m-d H:i:s', $now_order['order_time']), // 下单时间
            'pay_type_txt' => $paid!=0?$now_order['pay_type_txt']:'', // 定金或者全价支付方式
            'product_pay_type_txt' => $paid!=0?$now_order['product_pay_type_txt']:'',// 尾款支付方式
            'note' => $now_order['note']?$now_order['note']:'无',// 订单备注
            'balance_pay' => $now_order['balance_pay'],// 余额支付
            'pay_money' => $now_order['pay_money'],// 优惠后金额-实际支付金额
            'score_used_count' => $now_order['pay_money'],// 积分使用数量
            'score_deducte' => $now_order['pay_money'],// 积分抵扣金钱
            'payment_status' => $now_order['payment_status'],// 是否需要支付定金 1需要
            'paid' => $paid,// 0 待支付  1 已支付  2 定金已支付-尾款待支付 3 已服务-尾款待支付,
            'product_id' => intval($now_order['product_id']),
            'payment_money' => $now_order['payment_money'],
            'appoint_price' => $now_order['appoint_price'],
            'product_price' => $now_order['product_price'],
            'product_payment_price' => $now_order['product_payment_price'],
        ];
       
        if(empty($now_order_new['payment_status']) && empty($now_order_new['product_id']) && empty(floatval($now_order_new['appoint_price']))){
            $now_order_new['appoint_price'] = '面议';
        }
        // 覆盖当前订单价格显示
        $now_appoint['appoint_price'] = $now_order['appoint_price'];

        $order_msg = [];
        if ($now_order['real_orderid']) {
            $order_msg[] = [
                'title' => '订单编号',
                'content' => $now_order['real_orderid'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'order_basic',
            ];
        } elseif($order_id) {
            $order_msg[] = [
                'title' => '订单ID',
                'content' => $now_order['real_orderid'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'order_basic',
            ];
        }
        if ($now_order['order_time']) {
            $order_msg[] = [
                'title' => '下单时间',
                'content' => date('Y-m-d H:i:s', $now_order['order_time']),
                'title_color' => '',
                'content_color' => '',
                'tab'=>'order_basic',
            ];
        }
        if($store_name){
            $order_msg[] = [
                'title' => '预约店铺',
                'content' => $store_name,
                'title_color' => '',
                'content_color' => '',
                'tab'=>'service_info',
            ];
        }
        if($now_order['appoint_date']){
            $order_msg[] = [
                'title' => '预约时间',
                'content' => $now_order['appoint_date'].' '.$now_order['appoint_time'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'service_info',
            ];
        }
        foreach ($cue_field as $item){
            $order_msg[] = [
                'title' => $item['name'],
                'content' => $item['value'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'order_ext',
            ];
        }
        if ($now_order['product_name']) {
            $order_msg[] = [
                'title' => '服务名称',
                'content' => $now_order['product_name'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'service_info',
            ];
        }
        $Currency_symbol = $this->config['Currency_symbol'];
        $score_name = $this->config['score_name'];
        
        //增加技师姓名 + 联系方式
        if($now_order['merchant_worker_id'] > 0){
            $worker = D('Merchant_workers')->appoint_worker_info(['merchant_worker_id' => $now_order['merchant_worker_id']], ['merchant_worker_id', 'name', 'mobile']);
            if($worker['name']){
                $order_msg[] = [
                    'title' => '服务工作人员',
                    'content' => $worker['name'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'service_info',
                ];
            }
            if($worker['mobile']){
                $order_msg[] = [
                    'title' => '联系电话',
                    'content' => $worker['mobile'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'service_info',
                ];
            }
        }

        if (0<floatval($now_order['product_payment_price']) && $now_order["type"]<2 && $now_order["payment_status"] && $now_order['product_payment_price']>0) {
            $order_msg[] = [
                'title' => '所选服务定金',
                'content' => $Currency_symbol.$now_order['product_payment_price'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'service_info',
            ];
        }
        if (0<floatval($now_order['product_price'])) {
            $order_msg[] = [
                'title' => '所选服务全价',
                'content' => $Currency_symbol.$now_order['product_price'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'service_info',
            ];
        }
        if($now_order['product_id']>0){
            $now_appoint['appoint_price'] = $now_order['product_price'];
        }
        if (0<intval($now_order['product_use_time'])) {
            $order_msg[] = [
                'title' => '服务时间',
                'content' => $now_order['product_use_time'].'分钟',
                'title_color' => '',
                'content_color' => '',
                'tab'=>'service_info',
            ];
        }
        if (floatval($now_order['product_card_discount'])<10 && floatval($now_order['product_card_discount'])>0) {
            $order_msg[] = [
                'title' => '余额享受会员卡折扣',
                'content' => $now_order['product_card_discount'].'折',
                'title_color' => '',
                'content_color' => '',
                'tab'=>'pay_info',
            ];
        }
        if (intval($now_order['product_id'])>0 && intval($now_order['payment_status'])==1){
            if (0<floatval($now_order['product_payment_price']) && $now_order["type"]<=2 && $now_order["payment_status"]) {
                $msg_tip = ['title' => '定金支付金额'];
                if (0<floatval($now_order["balance_pay"])) {
                    $msg_tip['content'] = $Currency_symbol.$now_order["balance_pay"];
                } else {
                    $msg_tip['content'] = $Currency_symbol.$now_order["product_payment_price"];
                }
                $msg_tip['title_color'] = '';
                $msg_tip['content_color'] = '';
                $msg_tip['tab'] = 'pay_info';
                $order_msg[] = $msg_tip;
            }
            if (0<floatval($now_order['score_deducte'])) {
                $order_msg[] = [
                    'title' => "定金{$score_name}抵扣",
                    'content' => $Currency_symbol.$now_order['score_deducte'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            $order_msg[] = [
                'title' => "定金支付方式",
                'content' => $paid!=0?$now_order['pay_type_txt']:'暂未进行支付',
                'title_color' => '',
                'content_color' => '',
                'tab'=>'pay_info',
            ];
            if (0<floatval($now_order['pay_time'])) {
                $order_msg[] = [
                    'title' => "定金支付时间",
                    'content' => date('Y-m-d H:i:s', $now_order['pay_time']),
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
        } elseif(floatval($now_order['payment_money']) > 0 && intval($now_order['payment_status'])==1) {
            if (0<floatval($now_order['payment_money'])) {
                $order_msg[] = [
                    'title' => "定金支付金额",
                    'content' => $now_order['payment_money'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            if (0<floatval($now_order['score_deducte'])) {
                $order_msg[] = [
                    'title' => "定金{$score_name}抵扣",
                    'content' => $Currency_symbol.$now_order['score_deducte'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            $order_msg[] = [
                'title' => "定金支付方式",
                'content' => $paid!=0?$now_order['pay_type_txt']:'暂未进行支付',
                'title_color' => '',
                'content_color' => '',
                'tab'=>'pay_info',
            ];
            if (0<floatval($now_order['pay_time'])) {
                $order_msg[] = [
                    'title' => "定金支付时间",
                    'content' => date('Y-m-d H:i:s', $now_order['pay_time']),
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
        }
        if (1==$now_order['is_initiative']) {
            if (0<floatval($now_order['user_pay_time'])) {
                $order_msg[] = [
                    'title' => "尾款支付时间",
                    'content' => date('Y-m-d H:i:s', $now_order['user_pay_time']),
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            $order_msg[] = [
                'title' => "平台余额支付尾款金额",
                'content' => $Currency_symbol.$now_order['product_balance_pay'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'pay_info',
            ];
            if (0<floatval($now_order['product_card_give_money'])) {
                $order_msg[] = [
                    'title' => "商家会员卡赠送余额支付尾款",
                    'content' => $Currency_symbol.$now_order['product_card_give_money'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            if (0<floatval($now_order['product_merchant_balance'])) {
                $order_msg[] = [
                    'title' => "平台余额抵扣尾款",
                    'content' => $Currency_symbol.$now_order['product_merchant_balance'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            if (0<floatval($now_order['product_card_price'])) {
                $order_msg[] = [
                    'title' => "商家优惠券抵扣尾款",
                    'content' => $Currency_symbol.$now_order['product_card_price'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            if (0<floatval($now_order['product_coupon_price'])) {
                $order_msg[] = [
                    'title' => "平台优惠券抵扣尾款",
                    'content' => $Currency_symbol.$now_order['product_coupon_price'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            if (0<floatval($now_order['product_score_deducte'])) {
                $order_msg[] = [
                    'title' => "{$score_name}抵扣尾款",
                    'content' => $Currency_symbol.$now_order['product_score_deducte'],
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
            if($now_order['product_pay_type']) {
                $order_msg[] = [
                    'title'   => "在线支付方式",
                    'content' => $now_order['product_pay_type_txt'] ? $now_order['product_pay_type_txt'] : '余额支付',
                    'tab'=>'pay_info',
                ];
                $order_msg[] = [
                    'title'         => "在线支付尾款金额",
                    'content'       => $Currency_symbol . $now_order['user_pay_money'],
                    'title_color'   => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }

        }
        if($address){
            $order_msg[] = [
                'title' => "地址",
                'content' => $address,
                'title_color' => '',
                'content_color' => '',
                'tab'=>'service_info',
            ];
        }
        $appointCheckQrcodeParams = '';
        if (0==$now_order['service_status'] && $now_order['appoint_pass']) {
            $order_msg[] = [
                'title' => "消费码",
                'content' => $now_order['appoint_pass'],
                'title_color' => '',
                'content_color' => '',
                'tab'=>'pay_info',
            ];
            if(!empty($now_appoint['is_code_verify']) && $now_order['paid'] == 1 && $now_order['service_status'] == 0){//二维码核销
                $appointCheckQrcodeParams = $order_id;
            }
        }
        $title = '服务状态';
        $content = '';
        $title_color = '';
        $content_color = '';

        $appoint_worker_name = $this->config['appoint_worker_name'] ? $this->config['appoint_worker_name'] : '技师';

        if (0==$now_order['service_status']) {
            if($now_supply && $now_order['payment_status'] && empty($now_order['is_initiative'])){
                $title_color = 'red';
                $content = $appoint_worker_name . '已服务，用户未支付尾款';
            } elseif ($now_supply){
                $title_color = 'green';
                $content = '技师已服务';
            } else {
                $title_color = 'red';
                $content = '未服务';
            }
        } elseif(1==$now_order['service_status']) {
            $title_color = 'green';
            $content = '已服务';
        } elseif(2==$now_order['service_status']) {
            $title_color = 'green';
            $content = '已评价';
        }
        if ($now_order["is_del"]!=0 || $now_order["paid"]==3) {
            $title_color = 'red';
            $content .= ' 已取消';
        }
        $order_msg[] = [
            'title' => $title,
            'content' => $content,
            'title_color' => $title_color,
            'content_color' => $content_color,
            'tab'=>'service_status',
        ];
        if($now_order['paid']==1 && $now_order['service_status']==1){
            $reward_money = D('Reward_order')->where(['business_order_id'=>$order_id,'status'=>1,'is_del'=>0])->sum('money');
            if($reward_money>0){
                $order_msg[] = [
                    'title' => '打赏金额',
                    'content' => $reward_money,
                    'title_color' => '',
                    'content_color' => '',
                    'tab'=>'pay_info',
                ];
            }
        }
        foreach ($order_msg as $key=>$val){
            $order_msg[$key]['jump_name'] = '';
            $order_msg[$key]['jump_url'] = '';
            if($val['title']=='服务工作人员'){
                $order_msg[$key]['jump_name'] = '查看主页';
                $order_msg[$key]['jump_url'] = C('config.site_url').'/packapp/platn/pages/marriage/v1/technicianDetail/index?id='.$worker['merchant_worker_id'].'&businessType=appoint';
            }
        }
        $arr = array();
        //预约商品详情链接
        if ($appoint_cate['is_autotrophic'] == 0) {
            $arr['appoint_url'] = get_base_url('pages/appoint/goods/productDetail?appoint_id=' . $now_order['appoint_id'] . '&is_autotrophic=' . $appoint_cate['is_autotrophic']);
        } else {
            $arr['appoint_url'] = get_base_url('pages/appoint/goods/goodsDetail?appoint_id=' . $now_order['appoint_id'] . '&is_autotrophic=' . $appoint_cate['is_autotrophic']);
        }

        $arr['order_msg'] = $order_msg;
        //新增订单信息分区显示
        $arr['order_msg_tabs'] = [];
        $divTabs = [
            'service_status', //服务状态
            'order_basic',  //订单基础信息
            'service_info',  //服务信息
            'order_ext',  //订单额外信息
            'order_buyer_info', //购买人信息
            'pay_info',  //支付信息
        ];
        foreach ($divTabs as $tab) {
            $filter = array_filter($order_msg, function ($v) use ($tab) {
                return $v['tab'] == $tab;
            });
            $filter && $arr['order_msg_tabs'][] = array_values($filter);
        }
        // 处理
        $btn = [];
        $site_url = $this->config['site_url'];
	    $arr['refund_info'] = M('Appoint_order_refund')
		    ->where([
			    'appoint_id' => $now_order['appoint_id'],
			    'order_id'   => $now_order['order_id']
		    ])
		    ->order('id desc')
		    ->field('id refund_id, uid, image, reason, status, applytime')
		    ->find();
		
        if ($now_order['paid']==2 && $now_order['payment_status']==1) {
            $btn[] = [
                'is_btn' => -1, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                'btn_url' => '', // 对应跳转或者请求借口
                'btn_param' => [], // 传参值
                'btn_info' => '已退款', // 显示内容
                'btn_color' => '#F2F2F2', // 按钮颜色
                'font_color' => '#333333', // 文字颜色
                'btn_tip' => '', // 提交时候需要进行拦截的提示
            ];
        } elseif($now_order['paid']==1 && $now_order['service_status']==1) {
            $merchant_worker_id = $now_order['merchant_worker_id'];
            if(!$merchant_worker_id){
                $merchant_worker_id = D('Appoint_supply')->where(['order_id'=>$order_id])->getField('worker_id');
                if($merchant_worker_id){
                    D('Appoint_order')->where(['order_id'=>$order_id])->save(['merchant_worker_id'=>$merchant_worker_id]);
                }
            }
            if($merchant_worker_id&&C('config.appoint_open_worker_reward')){
                $btn[] = [
                    'is_btn' => 3, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口 3 打赏弹窗
                    'btn_url' => $site_url."/v20/public/index.php/appoint/api.AppointRewardOrder/getWorkerInfo", // 对应跳转或者请求借口
                    'btn_param' => ['order_id'=>$order_id], // 传参值
                    'btn_info' => '打赏', // 显示内容
                    'btn_color' => '#FF574A', // 按钮颜色
                    'font_color' => 'white', // 文字颜色
                    'btn_tip' => '', // 提交时候需要进行拦截的提示
                ];
            }
            $btn[] = [
                'is_btn' => 1, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                'btn_url' => $site_url."/wap.php?c=My&a=appoint_feedback&order_id={$order_id}", // 对应跳转或者请求借口
                'btn_param' => [], // 传参值
                'btn_info' => '去评价', // 显示内容
                'btn_color' => '#ffeeed', // 按钮颜色
                'font_color' => '#FF574A', // 文字颜色
                'btn_tip' => '', // 提交时候需要进行拦截的提示
                'btn_border_color' => '#FF574A', //按钮边框颜色
            ];
            $btn[] = [
                'is_btn' => 1, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                'btn_url' => $site_url."/wap.php?c=Appoint&a=refund&order_id={$order_id}", // 对应跳转或者请求借口
                'btn_param' => [], // 传参值
                'btn_info' => '去退款', // 显示内容
                'btn_color' => '#F2F2F2', // 按钮颜色
                'font_color' => '#333333', // 文字颜色
                'btn_tip' => '', // 提交时候需要进行拦截的提示
            ];
        }
        elseif ($now_order['paid']==3) {
            $btn[] = [
                'is_btn' => -1, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                'btn_url' => '', // 对应跳转或者请求借口
                'btn_param' => [], // 传参值
                'btn_info' => '已取消', // 显示内容
                'btn_color' => '#F2F2F2', // 按钮颜色
                'font_color' => '#333333', // 文字颜色
                'btn_tip' => '', // 提交时候需要进行拦截的提示
            ];
        } elseif($now_order['paid']==0 && $now_order['payment_status']==1 && $now_order['is_del']==0) {
            if (!$appoint_cate || 0==$appoint_cate['is_autotrophic']) {
                $url = $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $order_id . '&type=appoint';
                $btn[] = [
                    'is_btn' => 1, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                    'btn_url' => $url, // 对应跳转或者请求借口
                    'btn_param' => [], // 传参值
                    'btn_info' => '去付款', // 显示内容
                    'btn_color' => '#2681f3', // 按钮颜色
                    'font_color' => 'white', // 文字颜色
                    'btn_tip' => '', // 提交时候需要进行拦截的提示
                ];
            }
			if(!$now_order['service_status'] && !$now_order['is_initiative'] && empty($now_supply)){
				$btn[] = [
					'is_btn' => 2, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
					'btn_url' => "Appoint_new&a=ajax_wap_user_del",// 对应跳转或者请求借口
					'btn_param' => ['order_id'=>$order_id],// 传参值
					'btn_info' => '取消订单', // 显示内容
					'btn_color' => '#F2F2F2', // 按钮颜色
					'font_color' => '#333333', // 文字颜色
					'btn_tip' => '取消后，将无法恢复，是否确认取消？', // 提交时候需要进行拦截的提示
				];
			}
        } elseif(
            empty(floatval($now_order['payment_money'])) && 
            empty(floatval($now_order['product_payment_price'])) && 
            empty($now_order['service_status']) &&
            empty($now_order['is_del']) && 
            $now_order['is_initiative'] &&
            empty($arr['refund_info'])
        ){
            $btn[] = [
                'is_btn' => 1, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                'btn_url' => $site_url."/wap.php?c=Appoint&a=refund&order_id={$order_id}",// 对应跳转或者请求借口
                'btn_param' => [],// 传参值
                'btn_info' => '取消订单', // 显示内容
                'btn_color' => '#F2F2F2', // 按钮颜色
                'font_color' => '#333333', // 文字颜色
                'btn_tip' => '', // 提交时候需要进行拦截的提示
            ];
        }else {
            $btn = [];
            $appoint_rule_type =  $now_appoint['is_refund'] ?: $this->config['appoint_rule_type'];
            if ($now_order["is_del"]==0) {
                if ($this->config['appoint_rule']) {
                    if(!$now_order['is_initiative'] && (($now_order['product_price'] - $now_order['product_payment_price'] > 0) ||($now_order['appoint_price'] - $now_order['payment_money'] > 0)) && ($now_order['service_status'] == 0)){
                        $refund = D('Appoint_order_refund')->field('id')->where(array('order_id' => $order_id))->find();
                        if(empty($refund)) {
                            $btn_info = '支付余款';
                            if (!$now_order['payment_money'] && !$now_order['product_payment_price'] || ($now_order['payment_money'] == '0.00' && $now_order['product_payment_price'] == '0.00')) {
                                $btn_info = '支付';
                            }
                            $btn[] = [
                                'btn_type'   =>'go_pay',
                                'is_btn'     => 2, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                                'btn_url'    => "Appoint_new&a=ajax_wap_appoint_pay_balance",// 对应跳转或者请求借口
                                'btn_param'  => ['order_id' => $order_id],// 传参值
                                'btn_info'   => $btn_info, // 显示内容
                                'btn_color'  => '#2681f3', // 按钮颜色
                                'font_color' => 'white', // 文字颜色
                                'btn_tip'    => '', // 提交时候需要进行拦截的提示
                            ];
                        }
						if(!$now_order['service_status'] && empty($now_supply)){
							if($appoint_rule_type == 2){
                                if(floatval($now_order['product_payment_price']) > 0){
                                    $tmp_price = $now_order['product_payment_price'];
                                }else{
                                    $tmp_price = $now_order['payment_money'];
                                }
                                if(floatval($tmp_price) > 0) {
                                    $btn[] = [
                                        'is_btn'     => 1, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                                        'btn_url'    => $site_url . "/wap.php?c=Appoint&a=bond_refund&order_id={$order_id}",// 对应跳转或者请求借口
                                        'btn_param'  => [],// 传参值
                                        'btn_info'   => '取消订单', // 显示内容
                                        'btn_color'  => '#F2F2F2', // 按钮颜色
                                        'font_color' => '#333333', // 文字颜色
                                        'btn_tip'    => '', // 提交时候需要进行拦截的提示
                                    ];
                                }else{
                                    $btn[] = [
                                        'is_btn' => 2, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                                        'btn_url' => "Appoint_new&a=ajax_wap_user_del",// 对应跳转或者请求借口
                                        'btn_param' => ['order_id'=>$order_id],// 传参值
                                        'btn_info' => '取消订单', // 显示内容
                                        'btn_color' => '#F2F2F2', // 按钮颜色
                                        'font_color' => '#333333', // 文字颜色
                                        'btn_tip' => '取消后，将无法恢复，是否确认取消？', // 提交时候需要进行拦截的提示
                                    ];
                                }
							} elseif($appoint_rule_type == 1) {
								$btn_msg = [
									'is_btn' => 2, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
									'btn_url' => "Appoint_new&a=ajax_wap_appoint_del",// 对应跳转或者请求借口
									'btn_param' => ['order_id'=>$order_id],// 传参值
									'btn_info' => '取消订单', // 显示内容
									'btn_color' => '#F2F2F2', // 按钮颜色
									'font_color' => '#333333', // 文字颜色
									'btn_tip' => '取消后，将无法恢复，是否确认取消？', // 提交时候需要进行拦截的提示
								];
								if(floatval($now_order['product_payment_price']) > 0){
									$tmp_price = $now_order['product_payment_price'];
								}else{
									$tmp_price = $now_order['payment_money'];
								}
								if(floatval($tmp_price) > 0){
									$btn_msg['btn_tip'] = '该订单取消后，会扣除定金'.$Currency_symbol.$tmp_price.'，是否确认？';
								}else{
									$btn_msg['btn_tip'] = '取消后，将无法恢复，是否确认取消？';
								}
								$btn[] = $btn_msg;
							}
                        }
                    } else {
						if(!$now_order['service_status'] && $now_order['paid'] < 2 && empty($now_supply)){
                            if($appoint_rule_type == 2){
                                if(floatval($now_order['product_payment_price']) > 0){
                                    $tmp_price = $now_order['product_payment_price'];
                                }else{
                                    $tmp_price = $now_order['payment_money'];
                                }
                                if(floatval($tmp_price) > 0 || $now_order['is_initiative']){
                                    $btn[] = [
                                        'is_btn' => 1, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                                        'btn_url' => $site_url."/wap.php?c=Appoint&a=bond_refund&order_id={$order_id}",// 对应跳转或者请求借口
                                        'btn_param' => [],// 传参值
                                        'btn_info' => '取消订单', // 显示内容
                                        'btn_color' => '#F2F2F2', // 按钮颜色
                                        'font_color' => '#333333', // 文字颜色
                                        'btn_tip' => '', // 提交时候需要进行拦截的提示
                                    ];
                                }else{
                                    $btn[] = [
                                        'is_btn' => 2, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
                                        'btn_url' => "Appoint_new&a=ajax_wap_user_del",// 对应跳转或者请求借口
                                        'btn_param' => ['order_id'=>$order_id],// 传参值
                                        'btn_info' => '取消订单', // 显示内容
                                        'btn_color' => '#F2F2F2', // 按钮颜色
                                        'font_color' => '#333333', // 文字颜色
                                        'btn_tip' => '取消后，将无法恢复，是否确认取消？', // 提交时候需要进行拦截的提示
                                    ];
                                }
							} else {
								$btn_msg = [
									'is_btn' => 2, // 是否显示评论按钮 -1 显示-不跳转不请求借口  1 直接跳转  2 请求接口
									'btn_url' => "Appoint_new&a=ajax_wap_user_del",// 对应跳转或者请求借口
									'btn_param' => ['order_id'=>$order_id],// 传参值
									'btn_info' => '取消订单', // 显示内容
									'btn_color' => '#F2F2F2', // 按钮颜色
									'font_color' => '#333333', // 文字颜色
									'btn_tip' => '取消后，将无法恢复，是否确认取消？', // 提交时候需要进行拦截的提示
								];
                                if(floatval($now_order['product_payment_price']) > 0){
                                    $tmp_price = $now_order['product_payment_price'];
                                }else{
                                    $tmp_price = $now_order['payment_money'];
                                }
                                if(floatval($tmp_price) > 0 || $now_order['is_initiative']){
                                    $btn_msg['btn_url'] = "Appoint_new&a=ajax_wap_appoint_del";// 对应跳转或者请求借口
                                    $btn_msg['btn_tip'] = '该订单取消后，将无法恢复并且扣除定金'.$Currency_symbol.$tmp_price.'，是否确认？';
                                }else{
                                    $btn_msg['btn_tip'] = '取消后，将无法恢复，是否确认取消？';
                                }
								$btn[] = $btn_msg;
							}
                        }
                    }
                }else{
                    if ($now_order['paid']==1 && $now_order['user_pay_time']>0 && $now_order['service_status']==0) {
                        $btn_msg = [];
                        $btn_msg['is_btn'] = 1;
                        $btn_msg['btn_url'] = $site_url."/wap.php?c=Appoint&a=refund&order_id={$order_id}";
                        $btn_msg['btn_color'] = '#F2F2F2';
                        $btn_msg['font_color'] = '#333333';
                        $btn_msg['btn_info'] = '去退款';
                        $btn_msg['btn_tip'] = '';
                        $btn[] = $btn_msg;
                    }
                }
            }
        }
        foreach ($btn as $k=>$v){
            if(!isset($v['btn_border_color'])){
                $btn[$k]['btn_border_color'] = '';
            }
        }
        $arr['btn'] = $btn;

        if (0==$now_appoint['is_appoint_price'] && floatval($now_appoint['appoint_price']) <= 0) {
            $now_appoint['appoint_price'] = '面议';
        }
        $arr['appoint_rule_type'] = $appoint_rule_type ?: '';
        $arr['now_appoint'] = $now_appoint;
        $arr['now_supply'] = $now_supply ? $now_supply : [];
        $arr['now_order'] = $now_order_new;
        $arr['appoint_cate'] = $appoint_cate;
        $arr['appoint_check_qrcode'] = '';
        if(!empty($appointCheckQrcodeParams)){
            $arr['appoint_check_qrcode'] = D('Appoint')->appointCheckQrcode($appointCheckQrcodeParams, $this->_uid);
            $arr['qrcode_status'] = $now_order['service_status'];
            $arr['qrcode_status_txt'] = in_array($now_order['service_status'], [1, 2]) ? '已核销' : '';
        }
		
        if($arr['refund_info'] && $arr['refund_info']['image'] ){
            $images = explode(',', $arr['refund_info']['image']);
            foreach ($images as &$v){
                $v = file_domain().$v;
            }
            $arr['refund_info']['image'] = $images;
        }

        $this->returnCode(0, $arr);
    }


    /**
     * Notes: 支付预约尾款
     * @author: wanzy
     * @date_time: 2020/6/29 20:23
     */
    public function ajax_wap_appoint_pay_balance(){
        if(empty($this->_uid)){
            $this->returnCode('20044010');
        }
        $order_id = I('order_id', '', 'intval');
        if(empty($order_id)){
            $this->returnCodeError('传递参数有误!');
        }

        $database_appoint_order = D('Appoint_order');
        $now_order = $database_appoint_order->get_order_by_id($this->_uid,$order_id);

        if(empty($now_order)){
            $this->returnCodeError('该订单不存在!');
        }
        if($now_order['payment_status']){
            $now_pay_money = $now_order['product_price'] - $now_order['product_payment_price'];
            if(!$now_pay_money){
                $now_pay_money = $now_order['appoint_price'] - $now_order['payment_money'];
            }
        }else{
            $now_pay_money = $now_order['product_price'];
            if(empty((float)($now_pay_money))){
                $now_pay_money = $now_order['appoint_price'];
            }
        }
        if($now_pay_money <= 0){
            $this->returnCodeError('数据处理有误!');
        }
        $href = $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $order_id . '&type=balance-appoint';
        $hrefArr = ['href' => $href, 'order_id' => $order_id, 'is_pay' => 'pay'];
        $this->returnCode(0,$hrefArr);
    }


    /**
     * Notes: 订单取消
     * @author: wanzy
     * @date_time: 2020/6/29 20:23
     */
    public function ajax_wap_user_del(){
        if(empty($this->_uid)){
            $this->returnCode('20044010');
        }
        $order_id = I('order_id', '', 'intval');

        $database_appoint_order = D('Appoint_order');
        $where['order_id'] = $order_id;
        $data['is_del'] = 5;
        $data['del_time']= time();
        $now_order = $database_appoint_order->where($where)->find();
        if($now_order['service_status'] == 1){
            $this->returnCodeError('订单已服务!');
        }
        $result = $database_appoint_order->where($where)->data($data)->save();
        if(!empty($result)){

            $now_order['paid'] && $database_appoint_order->cancelPaidOrder($order_id);
            /*-----记录系统订单信息 订单取消成功 改写状态已取消 19/1/20----*/
            D("System_order")->save_order_status('appoint',$order_id,5);
            //增加消息通知
            $url = '\app\common\model\service\user\UserNoticeService/addNotice';
            $params['type'] = 0;
            $params['business'] = 'appoint';
            $params['order_id'] = $now_order['order_id'];
            $params['mer_id'] = $now_order['mer_id'];
            $params['store_id'] = $now_order['store_id'];
            $params['uid'] = $now_order['uid'];
            $params['title'] = '订单取消提醒';
            $params['content'] = '用户取消订单';
            invoke_v20_service($url, [$params]);
            $ret = array('is_del'=>1,'status'=>1,'msg'=>'取消成功！', 'url' => $this->config['site_url'].'/wap.php?g=Wap&c=My&a=appoint_order_list');
            
            $appointSupply = D('Appoint_supply')->where(['order_id' => $order_id])->find();
            if($appointSupply && $appointSupply['status'] == 2) {
                D('Appoint_supply')->where(['order_id' => $order_id])->save(['status' => 4]);// 未服务的退款标记一下 4 已取消
            }
            
            $this->returnCode(0,$ret);
        }else{
            $this->returnCodeError('取消失败!');
        }
    }


    /**
     * Notes: 取消订单
     * @author: wanzy
     * @date_time: 2020/6/29 20:54
     */
    public function ajax_wap_appoint_del(){
        $order_id = I('order_id', '', 'intval');
        if(empty($order_id)){
            $this->returnCodeError('传递参数有误!');
        }

        if($this->config['appoint_rule']){
            $database_appoint_order = D('Appoint_order');
            $now_order = $database_appoint_order->get_order_by_id($this->user_session['uid'] , $order_id);
            $appoint_service_time = strtotime($now_order['appoint_date']  . ' ' .  $now_order['appoint_time']);
            $appoint_before_cancel_time = $this->config['appoint_before_cancel_time'] * 60;
            if($now_order['paid']==3){
                $this->returnCodeError('已经退款了，不能再退了！');
            }
            if( (time() > ($appoint_service_time - $appoint_before_cancel_time)) && ($now_order['payment_status'] > 0)){
                if($now_order['payment_status'] > 0){
                    $where['order_id'] = $order_id;
                    $fields['paid'] = 3;
                    $fields['del_time'] = time();
                    if($database_appoint_order->where($where)->data($fields)->save()){
                        $now_order['order_type']='appoint';
                        $now_appoint = M('Appoint')->field('appoint_name')->where(array('appoint_id'=>$now_order['appoint_id']))->find();
                        if($now_order['product_payment_price']){
                            $tmp_price = $now_order['product_payment_price'];
                        }else{
                            $tmp_price = $now_order['payment_price'];
                        }
                        $order_info['order_id'] = $now_order['order_id'];
                        $order_info['store_id'] = $now_order['store_id'];
                        $order_info['mer_id'] = $now_order['mer_id'];
                        $order_info['order_type'] = 'appoint';
                        $order_info['balance_pay'] = $now_order['balance_pay'];
                        $order_info['score_deducte'] = $now_order['score_deducte'];
                        $order_info['payment_money'] = $now_order['pay_money'];
                        $order_info['is_own'] = $now_order['is_own'];
                        $order_info['merchant_balance'] = $now_order['merchant_balance'];
                        $order_info['score_used_count'] = $now_order['score_used_count'];
                        $order_info['money'] = $order_info['balance_pay'] + $order_info['score_deducte'] + ($order_info['is_own'] ? 0 : $order_info['payment_money']) + $order_info['merchant_balance'];

                        if($now_order['product_id'] > 0){
                            $order_info['total_money'] = $now_order['product_price'];
                        }else{
                            $order_info['total_money'] = $now_order['appoint_price'];
                        }

                        //$result = D('Merchant_money_list')->add_money($now_order['mer_id'] , '用户预约 '.$now_appoint['appoint_name'].'*1 取消预约，定金' . $order_info['money'] . '记入收入',$order_info);
                        $order_info['desc'] = '用户预约 '.$now_appoint['appoint_name'].'*1 取消预约，定金' . $order_info['money'] . '记入收入';
                        $order_info['score_discount_type']=$now_order['score_discount_type'];
                        $result =D('SystemBill')->bill_method($order_info['is_own'],$order_info);
                        $sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'appoint');
                        if ($this->config['user_cancel_appoint_order'] == 1 || $this->config['user_cancel_appoint_order'] == 3) {
                            $sms_data['uid'] = $now_order['uid'];
                            $sms_data['mobile'] = $now_order['phone'];
                            $sms_data['sendto'] = 'user';
                            $sms_data['content'] = '您购买 '.$now_appoint['appoint_name'].'的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
                            Sms::sendSms($sms_data);
                        }
                        if ($this->config['user_cancel_appoint_order'] == 2 || $this->config['user_cancel_appoint_order'] == 3) {
                            $merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
                            $sms_data['uid'] = 0;
                            $sms_data['mobile'] = $merchant['phone'];
                            $sms_data['sendto'] = 'merchant';
                            $sms_data['content'] = '顾客购买的' . $now_appoint['appoint_name'] . '的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
                            Sms::sendSms($sms_data);
                        }
                        if(!$result['error_code']){

                            // 取消成功推送消息给 商户
                            if ($now_order['mer_id']) {
                                // 获取对应商户user账号openid
                                $uid = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->getField('uid');
                                $merchant_user_openid = M('User')->where(array('uid' => $uid))->getField('openid');

                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                // 处理备注
                                $remark = '服务名称：'.$now_appoint['appoint_name'];
                                if ($now_order['product_id'] > 0 && $now_order['product_price']) {
                                    $remark .= ';服务全价：'.$now_order['product_price'];
                                } elseif ($now_order['appoint_price']) {
                                    $remark .= ';服务全价：'.$now_order['appoint_price'];
                                }
                                if ($now_order['appoint_time']) {
                                    $remark .= ';预约时间：'.$now_order['appoint_time'];
                                }
                                $model->sendTempMsg('TM00017',
                                    array(
                                        'wecha_id' => $merchant_user_openid,
                                        'first' => '用户在' . date('Y-m-d H:i',time()) . '时，取消了'.$now_appoint['appoint_name'].'预约订单，取消成功，订单号：'. $now_order['order_id'],
                                        'OrderSn' => $now_order['order_id'],
                                        'OrderStatus' => '取消成功',
                                        'remark' => $remark
                                    )
                                );
                            }
                            // 如果存在预约的工作者 直接推送消息 如果订单里不存在 查询下抢单的工作者 在推送消息
                            if ($now_order['merchant_worker_id']) {
                                // 存在工作人员id 直接推送
                                $database_merchant_workers = M('Merchant_workers');
                                $worker_where['merchant_worker_id'] = $now_order['merchant_worker_id'];
                                $now_worker = $database_merchant_workers->where($worker_where)->find();
                                if ($now_worker['openid']) {
                                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                    $first = '用户在' . date('Y-m-d H:i',time()) . '时，取消订单，请及时查看，订单号：'. $order_id;
                                    $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                                    $model->sendTempMsg('TM00017',
                                        array(
                                            'href' => $href,
                                            'wecha_id' => $now_worker['openid'],
                                            'first' => $first,
                                            'OrderSn' => $order_id,
                                            'OrderStatus' => '取消成功',
                                            'remark' => '\n请点击查看详细信息！'
                                        )
                                    );
                                }
                            } else {
                                // 查询下抢单的工作者 在推送消息
                                $database_appoint_supply = M('Appoint_supply');
                                $merchant_worker_id = $database_appoint_supply->where(array('appoint_id' => $now_order['appoint_id']))->getField('worker_id');
                                if ($merchant_worker_id > 0) {
                                    $database_merchant_workers = M('Merchant_workers');
                                    $worker_where['merchant_worker_id'] = $merchant_worker_id;
                                    $now_worker = $database_merchant_workers->where($worker_where)->find();
                                    if ($now_worker['openid']) {
                                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                        $first = '用户在' . date('Y-m-d H:i',time()) . '时，取消订单，请及时查看，订单号：'. $order_id;
                                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                                        $model->sendTempMsg('TM00017',
                                            array(
                                                'href' => $href,
                                                'wecha_id' => $now_worker['openid'],
                                                'first' => $first,
                                                'OrderSn' => $order_id,
                                                'OrderStatus' => '取消成功',
                                                'remark' => '\n请点击查看详细信息！'
                                            )
                                        );
                                    }
                                }
                            }
                            /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/20----*/
                            D("System_order")->save_order_status('appoint',$order_id,4);
                            
                            $appointSupply = D('Appoint_supply')->where(['order_id' => $order_id])->find();
                            if($appointSupply && $appointSupply['status'] == 2) {
                                D('Appoint_supply')->where(['order_id' => $order_id])->save(['status' => 4]);// 未服务的退款标记一下 4 已取消
                            }
                            $this->returnCode(0,['status'=>1,'is_cancel'=>1]);
                        }else{
                            $this->returnCodeError('取消失败！');
                        }
                    }
                }
            }else{
                $order_id = I('order_id', '', 'intval');
                if(empty($order_id)){
                    $this->returnCodeError('传递参数有误！');
                }
                $now_appoint = M('Appoint')->field('appoint_name')->where(array('appoint_id'=>$now_order['appoint_id']))->find();

                $database_appoint_order = D('Appoint_order');
                $where['order_id'] = $order_id;
                $data['is_del'] = 5;
                $data['del_time']= time();
                if($now_order['service_status'] == 1){
                    $this->returnCodeError('订单已服务!');
                }
                $result = $database_appoint_order->where($where)->data($data)->save();
                if(!empty($result)){
                    $now_order['paid'] && $database_appoint_order->cancelPaidOrder($order_id);
                    $sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'appoint');
                    if ($this->config['user_cancel_appoint_order'] == 1 || $this->config['user_cancel_appoint_order'] == 3) {
                        $sms_data['uid'] = $now_order['uid'];
                        $sms_data['mobile'] = $now_order['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['content'] = '您购买 '.$now_appoint['appoint_name'].'的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
                        Sms::sendSms($sms_data);
                    }
                    if ($this->config['user_cancel_appoint_order'] == 2 || $this->config['user_cancel_appoint_order'] == 3) {
                        $merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $merchant['phone'];
                        $sms_data['sendto'] = 'merchant';
                        $sms_data['content'] = '顾客购买的' . $now_appoint['appoint_name'] . '的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
                        Sms::sendSms($sms_data);
                    }

                    // 取消成功推送消息给 商户
                    if ($now_order['mer_id']) {
                        // 获取对应商户user账号openid
                        $uid = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->getField('uid');
                        $merchant_user_openid = M('User')->where(array('uid' => $uid))->getField('openid');

                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        // 处理备注
                        $remark = '服务名称：'.$now_appoint['appoint_name'];
                        if ($now_order['product_id'] > 0 && $now_order['product_price']) {
                            $remark .= ';服务全价：'.$now_order['product_price'];
                        } elseif ($now_order['appoint_price']) {
                            $remark .= ';服务全价：'.$now_order['appoint_price'];
                        }
                        if ($now_order['appoint_time']) {
                            $remark .= ';预约时间：'.$now_order['appoint_time'];
                        }
                        $model->sendTempMsg('TM00017',
                            array(
                                'wecha_id' => $merchant_user_openid,
                                'first' => '用户在' . date('Y-m-d H:i',time()) . '时，取消了'.$now_appoint['appoint_name'].'预约订单，取消成功，订单号：'. $now_order['order_id'],
                                'OrderSn' => $now_order['order_id'],
                                'OrderStatus' => '取消成功',
                                'remark' => $remark
                            )
                        );
                    }
                    // 如果存在预约的工作者 直接推送消息 如果订单里不存在 查询下抢单的工作者 在推送消息
                    if ($now_order['merchant_worker_id']) {
                        // 存在工作人员id 直接推送
                        $database_merchant_workers = M('Merchant_workers');
                        $worker_where['merchant_worker_id'] = $now_order['merchant_worker_id'];
                        $now_worker = $database_merchant_workers->where($worker_where)->find();
                        if ($now_worker['openid']) {
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $first = '用户在' . date('Y-m-d H:i',time()) . '时，取消订单，请及时查看，订单号：'. $order_id;
                            $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                            $model->sendTempMsg('TM00017',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $now_worker['openid'],
                                    'first' => $first,
                                    'OrderSn' => $order_id,
                                    'OrderStatus' => '取消成功',
                                    'remark' => '\n请点击查看详细信息！'
                                )
                            );
                        }
                    } else {
                        // 查询下抢单的工作者 在推送消息
                        $database_appoint_supply = M('Appoint_supply');
                        $merchant_worker_id = $database_appoint_supply->where(array('appoint_id' => $now_order['appoint_id']))->getField('worker_id');
                        if ($merchant_worker_id > 0) {
                            $database_merchant_workers = M('Merchant_workers');
                            $worker_where['merchant_worker_id'] = $merchant_worker_id;
                            $now_worker = $database_merchant_workers->where($worker_where)->find();
                            if ($now_worker['openid']) {
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $first = '用户在' . date('Y-m-d H:i',time()) . '时，取消订单，请及时查看，订单号：'. $order_id;
                                $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                                $model->sendTempMsg('TM00017',
                                    array(
                                        'href' => $href,
                                        'wecha_id' => $now_worker['openid'],
                                        'first' => $first,
                                        'OrderSn' => $order_id,
                                        'OrderStatus' => '取消成功',
                                        'remark' => '\n请点击查看详细信息！'
                                    )
                                );
                            }
                        }
                    }
                    /*-----记录系统订单信息 订单取消成功 改写状态已取消 19/1/20----*/
                    D("System_order")->save_order_status('appoint',$order_id,5);
                    
                    $appointSupply = D('Appoint_supply')->where(['order_id' => $order_id])->find();
                    if($appointSupply && $appointSupply['status'] == 2) {
                        D('Appoint_supply')->where(['order_id' => $order_id])->save(['status' => 4]);// 未服务的退款标记一下 4 已取消
                    }
                    $this->returnCode(0, array('is_cancel'=>1,'status'=>1,'msg'=>'取消成功！'));
                }else{
                    $this->returnCodeError('传递参数有误！');
                }
            }
        }
    }

}
?>