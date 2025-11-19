<?php

// 快速买单管理

class QuickPayAction extends BaseAction {
   
    private $uid = 0;
    public function __construct() {
        parent::__construct();
        $areaid = C('config.now_city');
        $Nowarea = $_SESSION['myArea' . $areaid];
        if (empty($Nowarea)) {
            $Nowarea = D('Area')->get_area_by_areaId($areaid);
            $_SESSION['myArea' . $areaid] = serialize($Nowarea);
        } else {
            $Nowarea = unserialize($Nowarea);
        }
        $uid = !empty($this->user_session) ? $this->user_session['uid'] : 0;
        $this->uid = $uid > 0 ? $uid : 0;
        $this->assign('uid', $uid);
    }
    /**
     * 店铺列表
     */
    public function index()
    {
        if (!($this->uid > 0)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
            exit();
        }

        //导航条
        $adver = D('Adver')->get_adver_by_key('wap_quickpay_top', 5);
        $this->assign('wap_quickpay_top', $adver);

        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_quickpay_slider', 0);

        $wap_index_slider = array();
        if(count($tmp_wap_index_slider) >= 10 && $this->config['wap_slider_number'] == 10){
            $wap_slider_number = $this->config['wap_slider_number'];
        }else{
            $wap_slider_number = 8;
        }

        foreach($tmp_wap_index_slider as $key=>$value){
            $tmp_i = floor($key/$wap_slider_number);
            $wap_index_slider[$tmp_i][] = $value;
        }
        $this->assign('wap_quickpay_slider',$wap_index_slider);
        $this->assign('wap_quickpay_slider_number',$wap_slider_number);

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';
        $this->assign('now_area_url', $area_url);
        $circle_id = 0;
        $area_id = 0;
        if (!empty($area_url)) {
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
        }

        //判断排序信息

        $sort = !empty($_GET['sort']) ? htmlspecialchars(trim($_GET['sort'])) : 'juli';
        $queue = isset($_GET['queue']) ? intval($_GET['queue']) : -1;

        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 10);
        if (empty($long_lat)) {
            $sort = $sort == 'juli' ? 'defaults' : $sort;
            $sort_array = array(
                // array('sort_id'=>'defaults','sort_value'=>'智能排序'),
                // array('sort_id'=>'rating','sort_value'=>'评价最高'),
                // array('sort_id'=>'new','sort_value'=>'新店'),
                // array('sort_id'=>'discount','sort_value'=>'折扣最大'),
            );
        } else {
            $sort_array = array(
                array('sort_id'=>'juli', 'sort_value'=>'离我最近'),
            );
            $this->assign('long_lat', $long_lat);
        }
        if (0 == $this->config['is_open_merchant_foodshop_discount']) {
            foreach($sort_array as $k => $v){
                if($v['sort_id']=='new' || $v['sort_id']=='discount'){
                    unset($sort_array[$k]);
                }
            }
        }

        foreach ($sort_array as $key => $value) {
            if ($sort == $value['sort_id']) {
                $now_sort_array = $value;
                break;
            }
        }
        if( $this->user_session['uid'] && $this->config['open_rand_send']){
            $coupon_html = D('System_coupon')->rand_send_coupon_get(array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$this->user_session['uid']));
            $coupon_html && $this->assign('coupon_html',$coupon_html);
        }
        $queue_array = array('-1' => '不限', '0' => '无排号', '1' => '可排号');
        $other = $now_sort_array['sort_value'] . '/' . $queue_array[$queue];
        $this->assign('other', $other);
        $this->assign('sort_array', $sort_array);
        $this->assign('now_sort_array', $now_sort_array);
        $all_area_list = D('Area')->get_all_area_list();
        $this->assign('all_area_list', $all_area_list);
        $cat_url = isset($_GET['cat_url']) ? htmlspecialchars(trim(($_GET['cat_url']))) : 'all';
        $this->assign('now_cat_url', $cat_url);
        $this->assign('now_queue', $queue);
        $cat_id = 0;
        if($cat_url != 'all'){
            $now_category = D('Meal_store_category')->get_category_by_catUrl($cat_url);

            if (empty($now_category)) {
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category',$now_category);

            if (!empty($now_category['cat_fid'])) {
                $f_category = D('Meal_store_category')->get_category_by_id($now_category['cat_fid']);
                $all_category_url = $f_category['cat_url'];

                $this->assign('top_category',$f_category);

                $cat_fid = $now_category['cat_fid'];
                $cat_id = $now_category['cat_id'];
            } else {
                $this->assign('top_category',$now_category);

                $cat_id = 0;
                $cat_fid = $now_category['cat_id'];
            }
        }
        $all_category_list = D('Meal_store_category')->get_all_category();
        $this->assign('all_category_list', $all_category_list);
        $this->display();
    }

    public function ajaxList()
    {
        $this->header_json();

        //判断地区信息
        $area_url = !empty($_GET['area_url']) ? $_GET['area_url'] : '';

        $circle_id = 0;
        $area_id = 0;
        $cat_url = isset($_GET['cat_url']) ? htmlspecialchars(trim(($_GET['cat_url']))) : 'all';
        if (!empty($area_url)) {
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->error('当前区域不存在！');
            }

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
        }



        $cat_id = 0;
        if($cat_url != 'all'){
            $now_category = D('Meal_store_category')->get_category_by_catUrl($cat_url);
            if (empty($now_category)) {
                $this->error_tips('此分类不存在！');
            }
            $this->assign('now_category',$now_category);

            if (!empty($now_category['cat_fid'])) {
                $f_category = D('Meal_store_category')->get_category_by_id($now_category['cat_fid']);
                $all_category_url = $f_category['cat_url'];

                $this->assign('top_category',$f_category);

                $cat_fid = $now_category['cat_fid'];
                $cat_id = $now_category['cat_id'];
            } else {
                $this->assign('top_category',$now_category);
                $cat_id = 0;
                $cat_fid = $now_category['cat_id'];
            }
        }
        
        if($_GET['user_long'] && $_GET['user_lat']){
          $long_lat = array(
            'long'=>$_GET['user_long'],
            'lat'=>$_GET['user_lat'],
          );
        }else if($_SESSION['openid']){
          $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],10);
        }

        //判断排序信息
        $sort = isset($_GET['sort']) ? htmlspecialchars(trim($_GET['sort'])) : '';
        $queue = isset($_GET['queue']) ? intval($_GET['queue']) : -1;
        $keyword = isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '';

        $where = array('area_id' => $area_id, 'circle_id' => $circle_id, 'cat_fid' => $cat_fid, 'cat_id' => $cat_id, 'lat' => $long_lat['lat'], 'long' => $long_lat['long'], 'sort' => 'juli', 'queue' => $queue, 'keyword' => $keyword);

        $return = D('Merchant_store_foodshop')->wap_get_storeList_by_catid_quickpay($where);
        echo json_encode($return);
    }
    public function search(){
        $this->display();
    }
}