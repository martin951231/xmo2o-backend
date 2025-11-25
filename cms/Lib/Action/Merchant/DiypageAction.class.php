<?php

class DiypageAction extends BaseAction
{
    public $now_store = array();

    public function _initialize()
    {
        parent::_initialize();
        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['store_id'] = $_GET['store_id'] ? $_GET['store_id'] : $_POST['store_id'];
        $condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
        if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $condition_merchant_store['store_id']) {
            $this->error(L_('您没有这个权限'));
        }
        $this->now_store = $database_merchant_store->where($condition_merchant_store)->find();
        if (IS_GET) {
            if (!empty($this->now_store['pic_info'])) {
                $store_image_class = new store_image();
                $tmp_pic_arr = explode(';', $this->now_store['pic_info']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $this->now_store['pic'][$key]['title'] = $value;
                    $this->now_store['pic'][$key]['url'] = $store_image_class->get_image_by_path($value);
                }
            }
            $this->assign('now_store', $this->now_store);
        }
    }

    public function index()
    {
        $database_diypage = D('Merchant_store_diypage');
        $condition_diypage = array('store_id' => $_GET['store_id'], 'is_remove' => '0');
        $count_diypage = $database_diypage->where($condition_diypage)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_diypage, 20);

        $page_list = $database_diypage->where($condition_diypage)->order('`page_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $system_own = 0;
        if (C('config.open_kefu_url') && C('config.kefu_mer_id') == $this->merchant_session['mer_id']) {
            $system_own = 1;
        }

        foreach ($page_list as &$value) {
            $value['system_own'] = $system_own;
        }
        $this->assign('page_list', $page_list);
        $this->assign('pagebar', $p->show());

        $this->display();
    }

    public function create()
    {
        $page_goods_source = 1;
        $page_id = 0;
        if ($_GET['page_id']) {
            $condition_page['page_id'] = $page_id = $_GET['page_id'];
            $condition_page['store_id'] = $this->now_store['store_id'];
            $now_page = D('Merchant_store_diypage')->where($condition_page)->find();
            $now_page_custom = D('Merchant_store_diypage_field')->where($condition_page)->order('`field_id` ASC')->select();
            fdump($now_page_custom,'$now_page_custom');
            foreach ($now_page_custom as $key => $value) {
                $content_arr = unserialize($value['content']);
                $new_content_arr = $this->reg_image($content_arr);
                $now_page_custom[$key]['content'] = $new_content_arr;
                if ($value['field_type'] == 'rich_text') {
                    $now_page_custom[$key]['content'] = str_replace("font-family:'Microsoft YaHei';", '', $now_page_custom[$key]['content']);
                    $now_page_custom[$key]['content'] = str_replace("'allowfullscreen'", '"allowfullscreen"', $now_page_custom[$key]['content']);
                    $now_page_custom[$key]['content'] = str_replace("font-family:&quot;",'',$now_page_custom[$key]['content']);
                    $now_page_custom[$key]['content'] = str_replace("&quot;",'',$now_page_custom[$key]['content']);
                }
            }
            $page_goods_source = $now_page['page_goods_source'];

            $this->assign('now_page', $now_page);
            $this->assign('page_id', $page_id);
            $this->assign('now_page_custom', $now_page_custom);
        }
        $now_store = D('Merchant_store')->get_store_by_storeId($this->now_store['store_id']);
        $this->assign('now_store', $now_store);
        if (!$page_goods_source) {
            $page_goods_source = 1;
        }
        $this->assign('is_hark',C('config.is_hark_system'));
        $this->assign('page_goods_source', $page_goods_source);
        $this->display();
    }

    //格式化图片格式
    public function reg_image($content_arr)
    {
        if (is_array($content_arr)) {
            foreach ($content_arr as $k => $v) {
                if (is_array($v)) {
                    $content_arr[$k] = $this->reg_image($v);
                } elseif ($k == 'image' || $k == 'img') {
                    $content_arr[$k] = replace_file_domain($v);
                }elseif ($k == 'content') {
                    $content_arr[$k] = replace_file_domain_content($v);
                }
            }
        }
        return $content_arr;
    }

    public function see_qrcode(){
		
		$wxapp_page_url = '/wap.php?c=Diypage&a=page&page_id=' . $_GET['page_id'] . '&system_own='.$_GET['system_own'].'&page_from=store_qr_code';
		//换短链
		$link_id = M('Short_link')->data(['link_url'=>$wxapp_page_url])->add();
		if(!$link_id){
			$this->error('小程序码生成失败');
		}
		$wxapp_page_url = '/short_'.$link_id;
		$this->assign('wxapp_page_url', $wxapp_page_url);
		
		$this->display();
	}

    public function set_home()
    {
        $condition_page['store_id'] = $this->now_store['store_id'];
        D('Merchant_store_diypage')->where($condition_page)->data(array('is_home' => '0'))->save();
        if (empty($_GET['close'])) {
            $condition_page['page_id'] = $_GET['page_id'];
            D('Merchant_store_diypage')->where($condition_page)->data(array('is_home' => '1'))->save();
        }
        $this->success(L_('设置成功'));
    }

    public function delete()
    {
        $condition_page['store_id'] = $this->now_store['store_id'];
        $condition_page['page_id'] = $_GET['page_id'];
        if (D('Merchant_store_diypage')->where($condition_page)->data(array('is_remove' => '1'))->save()) {
            $this->success(L_('删除成功'));
        } else {
            $this->error(L_('删除失败'));
        }
    }

    public function upload()
    {
        if ($_FILES['file']['error'] != 4) {
            $image = D('Image')->handle($this->merchant_session['mer_id'], 'diypage', 1);

            if ($image['error']) {
                exit(json_encode($image));
            } else {
                if ($image['url']['imgFile'])
                    $url = $image['url']['imgFile'];
                else
                    $url = $image['url']['file'];
                $image = D('Image')->field(true)->where(array('pic_md5' => md5($url)))->find();

                exit(json_encode(array('error' => 0, 'pigcms_id' => $image['pigcms_id'], 'url' => $url, 'id' => $_POST['id'])));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => L_('没有选择图片'))));
        }
    }

    public function page_add()
    {
        // var_dump($_POST);die;s
        $page_id = $_POST['page_id'];
        $database_diypage = D('Merchant_store_diypage');
        $database_diypage_field = D('Merchant_store_diypage_field');
        $data_diypage_field['mer_id'] = $this->now_store['mer_id'];
        $data_diypage_field['store_id'] = $this->now_store['store_id'];
        $data_diypage_field['page_id'] = $page_id;

        $data_page['page_name'] = $_POST['page_name'];
        $data_page['page_desc'] = $_POST['page_desc'];
        $data_page['bgcolor'] = $_POST['bgcolor'];
        $data_page['menu_bgcolor'] = $_POST['menu_bgcolor'];
        $data_page['page_goods_source'] = $_POST['page_goods_source'];
        $data_page['store_cate'] = $_POST['store_cate'];// 店铺分类
        $data_page['add_time'] = time();
        if (empty($_POST['page_id'])) {
            $data_page['mer_id'] = $this->now_store['mer_id'];
            $data_page['store_id'] = $this->now_store['store_id'];
            $data_diypage_field['page_id'] = $page_id = $database_diypage->data($data_page)->add();
        } else {
            $data_diypage_field['page_id'] = $page_id = $_POST['page_id'];
            $database_diypage->where($data_diypage_field)->data($data_page)->save();

            $database_diypage_field->where($data_diypage_field)->delete();
        }
        if (empty($page_id)) {
            $this->error(L_('保存失败'));
        }
        $custom = $_POST['custom'];
        foreach ($_POST['stock'] as $v) {
            $data_diypage_field['field_type'] = $custom[$v]['type'];
            if ($custom[$v]['fontColor']) {
                $data_diypage_field['fontColor'] = $custom[$v]['fontColor'];
            }
            unset($custom[$v]['type']);
            unset($custom[$v]['fontColor']);
            array_walk_recursive($custom[$v], 'arr_htmlspecialchars_decode');
            $data_diypage_field['content'] = serialize($custom[$v]);
            fdump($data_diypage_field,'$data_diypage_field');
            $database_diypage_field->data($data_diypage_field)->add();
        }

        //foreach($_POST['custom'] as $value){
        //	$data_diypage_field['field_type'] = $value['type'];
        //	unset($value['type']);
        //	array_walk_recursive($value,'arr_htmlspecialchars_decode');
        //	$data_diypage_field['content'] = serialize($value);
        //	$database_diypage_field->data($data_diypage_field)->add();
        //}
        $this->success(L_('保存成功'));
    }

    public function group()
    {
        $group_return = D('Group')->diypage_store_group_list($this->now_store['store_id']);
        $this->assign($group_return);
        $this->display();
    }

    public function activity_module()
    {
        $database_wxapp_list = D('Wxapp_list');
        $condition_wxapp_list = array('mer_id' => $this->now_store['mer_id']);
        $condition_wxapp_list['status'] = 1;
        if ($_POST['keyword']) {
            $condition_wxapp_list['title'] = array('like', $_POST['keyword']);
        }
        $count_wxapp = $database_wxapp_list->where($condition_wxapp_list)->count();
        import('@.ORG.diypage');
        $p = new Page($count_wxapp, 8);

        $wxapp_list = $database_wxapp_list->where($condition_wxapp_list)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('page_bar', $p->show());
        $this->assign('wxapp_list', $wxapp_list);
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $this->display();
    }

    public function new_activity_module()
    {
        $huodong_type = $_GET['huodong_type'] ? $_GET['huodong_type'] : 'seckill';
        $hide_others = $_GET['hide_others'] == '1' ? true : false;
        $shop_type = $_GET['shop_type'] ? $_GET['shop_type'] : 1;
        $type = $_GET['type'] ? $_GET['type'] : '';
        $this->assign('hide_others',$hide_others);
        $this->assign('huodong_type',$huodong_type);
        $this->assign('page_target', $_GET['page_target']);
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $store_id = $this->now_store['store_id'];

//        new_activity_module&page_goods_source

        $url = $this->config['site_url'] . "/merchant.php?c=Diypage&a=new_activity_module";
        if ($shop_type>0) {
            $this->assign('page_goods_source', intval($shop_type));
            if ($shop_type==1) {
                // 如果是快店，那么只存在秒杀
                $huodong_type = 'seckill';
            }
            $url .= '&shop_type=' . intval($shop_type);
        }
        if ($type) {
            $url .= '&type=' . $type;
        }
        if ($store_id) {
            $url .= '&store_id=' . $store_id;
        }
        if ($_GET['number']) {
            $url .= '&number=' . $_GET['number'];
        }
        if ($_GET['selecteditems']) {
            $url .= '&selecteditems=' . $_GET['selecteditems'];
        }
        $url_arr = array(
            'tuan' => $url . '&huodong_type=tuan',
            'bargain' => $url . '&huodong_type=bargain',
            'seckill' => $url . '&huodong_type=seckill',
        );
        $this->assign('url_arr', $url_arr);


        switch($huodong_type) {
            //团购
            case 'tuan':
                $this->assign('title_msg', L_('拼团商品名称'));
                $group = M('mall_group_act');
                $keyword = $_POST['keyword'];
                $where=array();
                $where['act.mer_id']= $this->merchant_session['mer_id'];
                $where['act.store_id']= $store_id;
                $where['act.status']= 1;
                $where['act.is_del']= 0;
                // $where['act.ftime'] = array('gt', time());
                if ($keyword) {
                    $where['gd.name']=array('like','%'.$keyword.'%');
                }

                $count= $group->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->where($where)->count();

                import('@.ORG.diypage');
                $page = new Page($count, 6);

                $group_list = array();
                if ($count > 0) {
                    $group_list = $group->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->field('act.*,gd.name,gd.image,gd.store_id store_id2,gd.sell_day,gd.original_stock,gd.price,gd.spec_value,gd.today_sell_spec,gd.stock_num,gd.sell_count')->where($where)->limit($page->firstRow,$page->listRows)->select();
                    $this->assign('activity_page', $page->show());

                    $goods_image_class = new goods_image();
                    $site_url = C('config.site_url');
                    foreach ($group_list as &$value) {
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
                        $value['activity_id'] = $value['id'];
                        $value['activity_choose_id'] = 'tuan_' . $value['id'];
                        $value['activity_stime'] = $value['stime'];
                    }
                }

                // 标识已选择过的活动

                $this->assign('activity_list', $group_list);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $keyword);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('new_activity_module_ajax');
                } else {
                    $this->display('new_activity_module');
                }
                break;
            //砍价
            case 'bargain':
                $this->assign('title_msg', L_('砍价商品名称'));
                $bargain = M('Mall_bargain_act');
                $keyword = $_POST['keyword'];
                $where=array();
                $where['act.mer_id']= $this->merchant_session['mer_id'];
                $where['act.store_id']= $store_id;
                $where['act.status']= 1;
                $where['act.is_del']= 0;
                $where['act.ftime'] = array('gt', time());
                if ($keyword) {
                    $where['gd.name']=array('like','%'.$keyword.'%');
                }

                $count= $bargain->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->where($where)->count();

                import('@.ORG.diypage');
                $page = new Page($count, 6);

                $bargain_list = array();
                if ($count > 0) {
                    $bargain_list = $bargain->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->field('act.*,gd.name,gd.image,gd.store_id store_id2,gd.sell_day,gd.original_stock,gd.price,gd.spec_value,gd.today_sell_spec,gd.stock_num,gd.sell_count')->where($where)->limit($page->firstRow,$page->listRows)->select();
                    $this->assign('activity_page', $page->show());

                    $goods_image_class = new goods_image();
                    $site_url = C('config.site_url');
                    foreach ($bargain_list as &$value) {
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
                    }
                }

                // 标识已选择过的活动
                // 统一处理下
                if ($bargain_list) {
                    foreach ($bargain_list as &$val) {
                        $val['activity_id'] = $val['id'];
                        $val['activity_choose_id'] = 'bargain_' . $val['id'];
                        $val['activity_stime'] = $val['stime'];
                    }
                }

                $this->assign('activity_list', $bargain_list);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $keyword);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('new_activity_module_ajax');
                } else {
                    $this->display('new_activity_module');
                }
                break;
            //秒杀
            case 'seckill':
                $this->assign('title_msg', L_('秒杀商品名称'));
                $keyword = $_POST['keyword'];
                $seckill = D('Shop_goods');
                $seckill_where = array();
                $seckill_where['status'] = 1;
                $seckill_where['store_id'] = $store_id;
                $seckill_where['seckill_price'] = array('gt', 0);

                if($shop_type==1){
                    $where['goods_type']=array('neq',1);
                }

                //获取商城商品
                if($shop_type==2){
                    $where['cat_fid']=array('gt',0);
                }

//                $seckill_where['seckill_close_time'] = array('gt', time());

                $now_time = time();

                $seckill_where['_string'] = '(seckill_type=0 AND seckill_open_time <= '.$now_time.' AND seckill_close_time > '.$now_time.') OR (seckill_type=1)';

                if ($keyword) {
                    $seckill_where['name'] = $keyword;
                }

                $seckill_list = $seckill->get_list_by_storeid_diypage_skill($store_id, $shop_type, $seckill_where);
                $this->assign('activity_page', $seckill_list['page_bar']);

                // 统一处理下
                if ($seckill_list['good_list']) {
                    foreach ($seckill_list['good_list'] as &$val) {
                        $val['activity_id'] = $val['goods_id'];
                        $val['activity_choose_id'] = 'seckill_' . $val['goods_id'];
                        $val['activity_stime'] = strtotime($val['start_date']." ".$val['start_time']);
                    }
                }

                foreach ($seckill_list['good_list'] as $k => $v) {
                    $seckill_list['good_list'][$k]['seckill_open_time'] = $v['seckill_open_time'] ? strtotime($v['seckill_open_time']) : 0;
                    $seckill_list['good_list'][$k]['seckill_close_time'] = $v['seckill_close_time'] ? strtotime($v['seckill_close_time']) : 0;
                }

                $this->assign('activity_list', $seckill_list['good_list']);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $keyword);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('new_activity_module_ajax');
                } else {
                    $this->display('new_activity_module');
                }

                break;
        }
    }

    //选择页面
    public function page()
    {
        //fdump("46456546465","dengyuanhui223",1);
        $database_diypage = D('Merchant_store_diypage');
        $condition_diypage = array('store_id' => $this->now_store['store_id']);
        if ($_POST['keyword']) {
            $condition_diypage['page_name'] = array('like', $_POST['keyword']);
        }
        $count_diypage = $database_diypage->where($condition_diypage)->count();
        import('@.ORG.diypage');
        $p = new Page($count_diypage, 8);

        $page_list = $database_diypage->where($condition_diypage)->order('`page_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('page_bar', $p->show());
        $this->assign('page_list', $page_list);
        $this->display();
    }

    public function shop_url(){
        ///packapp/plat/pages/shop_new/shopDetail/shopDetail?store_id=342&backgroundColor=113533&frontColor=ffffff
    }

    //选择商品
    public function good()
    {
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $this->assign('keyword', $_POST['keyword']);
        $shop_type = I('get.shop_type', 1, 'intval');
        $where = array();
        if($_POST['keyword']){
            $where['name'] = $_POST['keyword'];
        }
        $where['adjunct_status'] = 1; //1-不展示附属菜
        $this->assign(D('Shop_goods')->get_list_by_storeid_diypage($this->now_store['store_id'], $shop_type,$where));
        $this->display();
    }

    //选择餐饮商品
    public function foodshop_goods_new()
    {   
        $where = array();
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $keyword = $_GET['keyword'];
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $this->assign('keyword', $keyword);
        $keyword && $where['name'] = $keyword;
        $this->assign(D('Foodshop_goods_new')->get_list_by_storeid_diypage($this->now_store['store_id'],$where));
        $this->display();
    }

    /**
     * v20 选餐2.0 选择餐饮商品
     * User: chenxiang
     * Date: 2020/9/4 11:45
     */
    public function foodshop_goods_library()
    {
        $where = array();
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $keyword = $_GET['keyword'];
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $this->assign('keyword', $keyword);
        $keyword && $where['name'] = $keyword;
        $this->assign(D('Foodshop_goods_library')->get_list_by_storeid_diypage($this->now_store['store_id'],$where));
        $this->display();
    }

    public function good_group_detail() {
        $store_id = $_POST['store_id'] ? $_POST['store_id'] : 0;
        $where['store_id'] = $store_id;

        $selecteditemsArr = $_POST['selecteditems'] ? explode(',', $_POST['selecteditems']) : [];

        $shop_type = I('get.shop_type', 0, 'intval');
        if ($_POST['keyword']) {
            $where['name'] = array('like', '%' . $_POST['keyword'] . '%');
        }

        $count = M('Shop_goods_group')->where($where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 6);
        $goods_group_list = M('Shop_goods_group')
            ->where($where)
            ->order('`id` DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $goods_group_goods_list = array();

        $where = array();
        //获取商城商品
        if($shop_type==2){
            $where['g.cat_fid']=array('gt',0);
        }
        //获取快店商品
        if($shop_type==1){
            $where['g.goods_type']=array('neq',1);
        }
        //获取快店商品
        if($selecteditemsArr){
            $where['l.goods_id']=array('not in',$selecteditemsArr);
        }
        foreach ($goods_group_list as $key => $value) {
            $where['l.group_id'] = $value['id'];
            $goods_list = M('Shop_goods_group_list')->field('l.id,l.goods_id,g.name,g.image,g.price')->join(' as l left join '.C('DB_PREFIX').'shop_goods as g on l.goods_id = g.goods_id')->where($where)->select();

            $goods_ids = array();
            $title = array();
            $image = array();
            $price = array();
            $goods_image_class = new goods_image();
            foreach ($goods_list as $_goods) {
                $goods_ids[] = $_goods['goods_id'];
                $title[] = $_goods['name'];

                $pic_arr = explode(';', $_goods['image']);
                $tmp_img = $goods_image_class->get_image_by_path($pic_arr[0]);
                $image[] = $tmp_img['m_image'];
                $price[] = $_goods['price'];
            }
            $goods_group_goods_list[$value['id']] = array(
                'id' => $value['id'],
                'goods_ids' => $goods_ids,
                'title' => $title,
                'image' => $image,
                'price' => $price,
                'group_info' => $value,
            );
        }
        fdump($goods_group_goods_list,'$goods_group_goods_list');
        echo json_encode($goods_group_goods_list);
        exit();
    }

    //选择商品分组
    public function good_group()
    {
        $store_id = $_GET['store_id'] ? $_GET['store_id'] : 0;
        $only = $_GET['only'] ? $_GET['only'] : 0;
        $where['store_id'] = $store_id;
    
        $selecteditemsArr = $_GET['selecteditems'] ? explode(',', $_GET['selecteditems']) : [];
        $this->assign('selecteditemsArr', $selecteditemsArr);
        if (isset($_GET['keyword']) && !$_POST['keyword']) {
            $_POST['keyword'] = $_GET['keyword'];
        }
        $this->assign('keyword', $_POST['keyword']);
        $shop_type = I('get.shop_type', 0, 'intval');
        if ($_POST['keyword']) {
            $where['name'] = array('like', '%' . $_POST['keyword'] . '%');
        }

        $count = M('Shop_goods_group')->where($where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 6);
        $goods_group_list = M('Shop_goods_group')
            ->where($where)
            ->order('`id` DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $goods_group_goods_list = array();

        $where = array();
        //获取商城商品
        if($shop_type==2){
            $where['g.cat_fid']=array('gt',0);
        }
        //获取快店商品
        if($shop_type==1){
            $where['g.goods_type']=array('neq',1);
        }
        //获取快店商品
        if($selecteditemsArr){
            $where['l.goods_id']=array('not in',$selecteditemsArr);
        }
        $is_choose = false;
        foreach ($goods_group_list as $key => $value) {
            $where['l.group_id'] = $value['id'];
            $goods_list = M('Shop_goods_group_list')->field('l.id,l.goods_id,g.name,g.image,g.price')->join(' as l left join '.C('DB_PREFIX').'shop_goods as g on l.goods_id = g.goods_id')->where($where)->select();

            $goods_ids = array();
            $title = array();
            $image = array();
            $price = array();
            $goods_image_class = new goods_image();
            foreach ($goods_list as $_goods) {
                $goods_ids[] = $_goods['goods_id'];
                $title[] = $_goods['name'];

                $pic_arr = explode(';', $_goods['image']);
                $tmp_img = $goods_image_class->get_image_by_path($pic_arr[0]);
                $image[] = $tmp_img['m_image'];
                $price[] = $_goods['price'];
            }
            if ($selecteditemsArr && in_array($value['id'], $selecteditemsArr)) {
                $goods_group_list[$key]['is_choose'] = true;
                $is_choose = true;
            }
            $goods_group_goods_list[$value['id']] = array(
                'id' => $value['id'],
                'goods_ids' => $goods_ids,
                'title' => $title,
                'image' => $image,
                'price' => $price,
                'group_info' => $value,
            );
        }
        $this->assign('goods_list', json_encode($goods_group_goods_list));
        $this->assign('goods_group_list', $goods_group_list);
        $this->assign('only', $only);
        $this->assign('is_choose', $is_choose);
        $this->assign('page_bar', $Page->show());
        $this->display();
    }

    //选择商家商品
    public function mall_good()
    {
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $type = I('get.type', '', 'trim');
        $where = array();
        if($_POST['keyword']){
            $where['name'] = trim($_POST['keyword']);
        }
		$where['goods_type'] = 2;
        $data = D('Shop_goods')->get_list_by_storeid_diypage($this->now_store['store_id'], 2,$where);
        $list = $data['good_list'];
        $type = I('get.type', '', 'trim');
        $goods_id = $_GET['goods_id'];
        $goods_id_arr = explode(',',$goods_id);
        $now_time = time();
        //拼团商品,获取不可选的
        $where = array();
        $where['ftime'] = array('gt', $now_time);
        $where['status'] = 1;
        $where['is_del'] = 0;
        $where['store_id'] = $this->now_store['store_id'];
        $group_arr = M('Mall_group_act')->where($where)->getfield('good_id', true);
        $group_arr = (array)$group_arr;
		
		//周期购商品获取不可选的
        $where = array();
        $where['status'] = 0;
        $periodic_arr = M('Mall_periodic_purchase')->where($where)->getfield('goods_id', true);
        $periodic_arr = (array)$periodic_arr;

        //打包一口价
        $where2 = array();
        $where2['is_del'] = 0;
        $where['store_id'] = $this->now_store['store_id'];
        $where['end_time'] = array('gt', $now_time);
        $goods_pack_arr = M('Mall_goods_pack')->where($where2)->getfield('id,goods_str', true);

        $pack_goods_ids = [];
        foreach ($goods_pack_arr as $pv) {
            $pack_goods_ids = array_merge($pack_goods_ids,explode(',',$pv));
        }
        $goods_pack_arr = array_filter($pack_goods_ids);
		
        //砍价活动
        $where2 = array();
        $where2['is_del'] = 0;
        $where['store_id'] = $this->now_store['store_id'];
        $bargain_arr = M('Mall_bargain_act')->where($where2)->getfield('id,goods_id,spec_id', true);
        $default_spec_id = I('get.spec_id', '', 'trim');
        //是否获取商品的规格
        $get_reg = I('get.get_reg', '', 'intval');
        foreach ($list as $k => $v) {
            if ($v['seckill_price'] > 0) {
                $v['choice_status'] = 0;
                $v['tip'] = L_('限时购商品不可选');
                $list[$k] = $v;
                continue;
            }

            if (in_array($v['goods_id'], $goods_id_arr)) {              
                $v['choice_status'] = 0;
                $v['tip'] = '已选择';
                $list[$k] = $v;
                continue;
            }

            if (in_array($v['goods_id'], $goods_pack_arr)) {              
                $v['choice_status'] = 0;
                $v['tip'] = '一口价已选择';
                $list[$k] = $v;
                continue;
            }
            
            $is_group = 0;
            if (in_array($v['goods_id'], $group_arr)) {
                $is_group = 1;
            }
			$is_periodic = 0;
			if (in_array($v['goods_id'], $periodic_arr)) {
                $is_periodic = 1;
            }
			
            if ($is_group == 1) {
                $v['choice_status'] = 0;
                if ($type == 'mall_group') {
                    $v['tip'] = L_('已选取');
                } else {
                    $v['tip'] = L_('拼团商品不可选');
                }
                $list[$k] = $v;
                continue;
            }
			if($is_periodic){
				$v['choice_status'] = 0;
				$v['tip'] = '周期购已选';
				$list[$k] = $v;
                continue;
			}
            //砍价
            $is_bargain = 0;
            foreach ($bargain_arr as $key => $value) {
                if ($value['goods_id'] == $v['goods_id']) {
                    $is_bargain = 1;
                    $spec_id[] = $value['spec_id'];
                    continue;
                }
            }
            if ($is_bargain == 1) {
                if ($type == 'mall_group' || $goods_id != $v['goods_id']) {
                    $v['choice_status'] = 0;
                    if ($type == 'mall_group') {
                        $v['tip'] = L_('砍价商品不可选');
                    } else {
                        $v['tip'] = L_('已选取');
                    }
                    $list[$k] = $v;
                    continue;
                }
            }
            if ($get_reg == 1) {
                $info = D('Shop_goods')->format_spec_value($v['spec_value'], $v['goods_id']);
                $spec_list = $info['spec_list'];
                $list2 = $info['list'];
                $reg_list = array();
                foreach ($list2 as $k2 => $v2) {
                    $arr = array();
                    $reg_name = '';
                    foreach ($v2['spec'] as $k3 => $v3) {
                        $reg_name .= $spec_list[$k3]['name'] . ':' . $v3['spec_val_name'] . '&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    $arr['spec_id'] = $k2;
                    $arr['reg_name'] = $reg_name;
                    $arr['price'] = $v2['price'];
                    $arr['stock_num'] = $v2['stock_num'];
                    if ($is_bargain == 1 && in_array($k2, $spec_id)) {
                        $arr['is_bargain'] = $is_bargain;
                    }
                    $reg_list[] = $arr;
                }
                $v['reg_list'] = $reg_list;
                $list[$k] = $v;
            }
        }
        $data['good_list'] = $list;
        $this->assign('type', $type);
        $this->assign('get_reg', $get_reg);
        $this->assign($data);
        $this->display();
    }

    //选择商家商品
    public function mall_good1()
    {
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $where = array();
        if($_POST['keyword']){
            $where['name'] = trim($_POST['keyword']);
        }
        $where['goods_type'] = 2;
        $data = D('Shop_goods')->get_list_by_storeid_diypage($this->now_store['store_id'], 2,$where);
        $list = $data['good_list'];
        //$type = I('get.type', '', 'trim');
        $goods_id = $_GET['goods_id'];
        $goods_id_arr = explode(',',$goods_id);
        $now_time = time();
        //拼团商品,获取不可选的
        $where = array();
        $where['ftime'] = array('gt', $now_time);
        $where['status'] = 1;
        $where['is_del'] = 0;
        $where['store_id'] = $this->now_store['store_id'];
        $group_arr = M('Mall_group_act')->where($where)->getfield('good_id', true);
        $group_arr = (array)$group_arr;

        //周期购商品获取不可选的
        $where = array();
        $where['status'] = 0;
        $periodic_arr = M('Mall_periodic_purchase')->where($where)->getfield('goods_id', true);
        $periodic_arr = (array)$periodic_arr;

        //打包一口价
        $where1 = array();
        $where1['is_del'] = 0;
        $where1['store_id'] = $this->now_store['store_id'];
        $where1['end_time'] = array('gt', $now_time);
        $goods_pack_arr = M('Mall_goods_pack')->where($where1)->getfield('id,goods_str', true);
        $goods_pack_arr = (array)$goods_pack_arr;

        //砍价活动
        $where2 = array();
        $where2['is_del'] = 0;
        $where2['store_id'] = $this->now_store['store_id'];
        $bargain_arr = M('Mall_bargain_act')->where($where2)->getfield('id,goods_id,spec_id', true);


        //预售商品
        $time=time();
        $where3 = array();
        $where3['start_time']=array('lt',$time);
        $where3['end_time']=array('gt',$time);
        $where3['stock']=array('gt',0);
        $where3['is_del'] = 0;
        $where3['store_id'] = $this->now_store['store_id'];
        $book_arr = M('Mall_book_goods')->where($where3)->getfield('goods_id', true);

        $time=time();
        $where4 = array();
        $where4['stime']=array('lt',$time);
        $where4['ftime']=array('gt',$time);
        $where4['store_id'] = $this->now_store['store_id'];
        $where4['is_del'] = 0;
        $list1=M('Mall_full_act')->field('goods_id')->where($where4)->select();
        //满减活动
        $full_act=[];
        if($list1){
             foreach ($list1 as $key=>$val){
                 $full_act[$key]=$val['goods_id'];
             }
        }

        //$default_spec_id = I('get.spec_id', '', 'trim');
        //是否获取商品的规格
        $get_reg = I('get.get_reg', '', 'intval');
        //fdump($list,"dengyuanhui223",1);
       //fdump("------------------------------list-----------------------------------","dengyuanhui223",1);
        //fdump($list,"dengyuanhui223",1);
        foreach ($list as $k => $v) {
            if ($v['seckill_price'] > 0) {
                $v['choice_status'] = 0;
                $v['tip'] = L_('限时购商品不可选');
                $list[$k] = $v;
                continue;
            }

            if (in_array($v['goods_id'], $goods_id_arr)) {
                $v['choice_status'] = 0;
                $v['tip'] = '已参与活动';
                $list[$k] = $v;
                continue;
            }

            //已参与预售活动
            if (in_array($v['goods_id'], $book_arr)) {
                $v['choice_status'] = 0;
                $v['tip'] = '已参与活动';
                $list[$k] = $v;
                continue;
            }

            if (in_array($v['goods_id'], $goods_pack_arr)) {
                $v['choice_status'] = 0;
                $v['tip'] = '已参与活动';
                $list[$k] = $v;
                continue;
            }

            $is_group = 0;
            if (in_array($v['goods_id'], $group_arr)) {
                $is_group = 1;
            }
            $is_periodic = 0;
            if (in_array($v['goods_id'], $periodic_arr)) {
                $is_periodic = 1;
            }

            if ($is_group == 1) {
                $v['choice_status'] = 0;
               /* if ($type == 'mall_group') {
                    $v['tip'] = L_('已选取');
                } else {
                    $v['tip'] = L_('拼团商品不可选');
                }*/
                $v['tip'] = '已参与活动';
                $list[$k] = $v;
                continue;
            }
            if($is_periodic){
                $v['choice_status'] = 0;
                $v['tip'] = '已参与活动';
                $list[$k] = $v;
                continue;
            }
            //砍价
            $is_bargain = 0;
            foreach ($bargain_arr as $key => $value) {
                if ($value['goods_id'] == $v['goods_id']) {
                    $is_bargain = 1;
                    $spec_id[] = $value['spec_id'];
                    continue;
                }
            }
            if ($is_bargain == 1) {
                /*if ($type == 'mall_group' || $goods_id != $v['goods_id']) {*/
                    $v['choice_status'] = 0;
                    /*if ($type == 'mall_group') {
                        $v['tip'] = L_('砍价商品不可选');
                    } else {
                        $v['tip'] = L_('已选取');
                    }*/
                    $v['tip'] = '已参与活动';
                    $list[$k] = $v;
                    continue;
               /* }*/
            }
            /*满减*/
            if($full_act){
                foreach ($full_act as $k=>$val){
                    if(strpos($val,$v['goods_id']) !== false){
                        $v['tip'] = '已参与活动';
                        $list[$k] = $v;
                        continue;
                    }
                }
            }

            if ($get_reg == 1) {
                //fdump("------------------------------spec_value-----------------------------------","dengyuanhui223",1);
                //fdump($v['spec_value'],"dengyuanhui223",1);
                //fdump($v['goods_id'],"dengyuanhui223",1);
                $info = D('Shop_goods')->format_spec_value($v['spec_value'], $v['goods_id']);
                $spec_list = $info['spec_list'];
                $list2 = $info['list'];
                //fdump("------------------------------遍历中的info-----------------------------------","dengyuanhui223",1);
                //fdump($list2,"dengyuanhui223",1);
                $reg_list = array();
                foreach ($list2 as $k2 => $v2) {
                    $arr = array();
                    $reg_name = '';
                    foreach ($v2['spec'] as $k3 => $v3) {
                        $reg_name .= $spec_list[$k3]['name'] . ':' . $v3['spec_val_name'] . '&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    $arr['spec_id'] = $k2;
                    $arr['reg_name'] = $reg_name;
                    $arr['price'] = $v2['price'];
                    $arr['image'] = $v2['reg_img']['s_image'];
                    $arr['stock_num'] = $v2['stock_num'];
                    if ($is_bargain == 1 && in_array($k2, $spec_id)) {
                        $arr['is_bargain'] = $is_bargain;
                    }
                    $reg_list[] = $arr;
                }
                $v['reg_list'] = $reg_list;
                $list[$k] = $v;
            }
        }
        $data['good_list'] = $list;
        //fdump("------------------------------遍历后的list-----------------------------------","dengyuanhui223",1);
        //fdump($data,"dengyuanhui223",1);
       // $this->assign('type', $type);
        $this->assign('get_reg', $get_reg);
        $this->assign($data);
        $this->display();
    }

    //选择商品
    public function goods()
    {
        $this->assign(D('Shop_goods')->get_list_by_storeid_diypage($this->now_store['store_id']), $_GET['shop_type']);
        $this->display();
    }

    //选择图片
    public function imageList()
    {
        $search = isset($_POST['keyword']) ? $_POST['keyword'] : '';
        $condition = "`otype`=1 AND `oid`='{$this->merchant_session['mer_id']}' AND `status`=1";
        if ($search) $condition .= " AND `img_remark` LIKE '%{$search}%'";
        $count = D('Image')->where($condition)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 27);
        $image_list = D('Image')->where($condition)->order('`pigcms_id` DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($image_list as $k => $v) {
            $v['pic'] = replace_file_domain($v['pic']);
            $image_list[$k] = $v;
        }
        $return['count'] = $count;
        $return['image_list'] = $image_list;
        $return['page_bar'] = $Page->show();
        $this->success($return);
    }

    //选择优惠券
    public function coupon()
    {
        if ($_GET['type']=='system') {
            $couponList = D('System_coupon')->get_coupon_list_by_merid_diypage();
        }else{
            $couponList = D('Card_new_coupon')->get_coupon_list_by_merid_diypage($this->merchant_session['mer_id']);
        }
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign($couponList);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $this->display();
    }

    //选择平台E卡
    public function ecard_list()
    {
        $where = array();
        $where['is_del'] = 0;
        $where['status'] = 1;
        $keyword = I('keyword', '', 'trim');
        if (!empty($keyword)) {
            $where['card_name'] = array('like', '%' . $keyword . '%');
        }
        $arr = M('ecard')->where($where)->count(1);
        $count = count($arr);

        import('@.ORG.diypage');
        $page = new Page($count, 20);
        $list = M('ecard')->where($where)->limit($page->firstRow, $page->listRows)->select();
        $model = M('ecard_coupon');
        $where2 = array();
        $where2['make_status'] = 1;
        foreach ($list as $k => $v) {
            $where2['ecard_id'] = $v['id'];
            $make_count = $model->where($where2)->count(1);
            $v['make_count'] = $make_count;
            $list[$k] = $v;
        }
        $this->assign('list', $list);
        $this->assign('pagebar', $page->show());
        $this->display();
    }


    /**
     * 活动调取：1:秒杀	 2：团购	 3：砍价
     */
    public function activity_info() {

        $huodong_type = $_GET['huodong_type'] ? $_GET['huodong_type'] : 'seckill';
        $hide_others = $_GET['hide_others'] == '1' ? true : false;
        $shop_type = $_GET['shop_type'] ? $_GET['shop_type'] : 1;
        $this->assign('hide_others',$hide_others);
        $this->assign('huodong_type',$huodong_type);
        $this->assign('page_target', $_GET['page_target']);
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);

        switch($huodong_type) {
            //团购
            case 'tuan':
                $group = M('mall_group_act');
                $keyword = $_POST['keyword'];
                $store_id = $this->now_store['store_id'];
                $where=array();
                $where['act.mer_id']= $this->merchant_session['mer_id'];
                $where['act.store_id']= $store_id;
                $where['act.status']= 1;
                $where['act.is_del']= 0;
               // $where['act.ftime'] = array('gt', time());
                if ($keyword) {
                    $where['gd.name']=array('like','%'.$keyword.'%');
                }

                $count= $group->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->where($where)->count();

                import('@.ORG.diypage');
                $page = new Page($count, 6);

                $group_list = array();
                if ($count > 0) {
                    $group_list = $group->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->field('act.*,gd.name,gd.image,gd.store_id store_id2,gd.sell_day,gd.original_stock,gd.price,gd.spec_value,gd.today_sell_spec,gd.stock_num,gd.sell_count')->where($where)->limit($page->firstRow,$page->listRows)->select();
                    $this->assign('group_page', $page->show());

                    $goods_image_class = new goods_image();
                    $site_url = C('config.site_url');
                    foreach ($group_list as &$value) {
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
                    }
                }

                // 标识已选择过的活动

                $this->assign('group_list', $group_list);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $keyword);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('activity_group_ajax');
                } else {
                    $this->display('activity_group');
                }
                break;
            //砍价
            case 'bargain':
                $bargain = M('Mall_bargain_act');
                $keyword = $_POST['keyword'];
                $store_id = $this->now_store['store_id'];
                $where=array();
                $where['act.mer_id']= $this->merchant_session['mer_id'];
                $where['act.store_id']= $store_id;
                $where['act.status']= 1;
                $where['act.is_del']= 0;
                $where['act.ftime'] = array('gt', time());
                if ($keyword) {
                    $where['gd.name']=array('like','%'.$keyword.'%');
                }

                $count= $bargain->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->where($where)->count();

                import('@.ORG.diypage');
                $page = new Page($count, 6);

                $bargain_list = array();
                if ($count > 0) {
                    $bargain_list = $bargain->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->field('act.*,gd.name,gd.image,gd.store_id store_id2,gd.sell_day,gd.original_stock,gd.price,gd.spec_value,gd.today_sell_spec,gd.stock_num,gd.sell_count')->where($where)->limit($page->firstRow,$page->listRows)->select();
                    $this->assign('bargain_page', $page->show());

                    $goods_image_class = new goods_image();
                    $site_url = C('config.site_url');
                    foreach ($bargain_list as &$value) {
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
                    }
                }

                // 标识已选择过的活动

                $this->assign('bargain_list', $bargain_list);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $keyword);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('activity_bargain_ajax');
                } else {
                    $this->display('activity_bargain');
                }
                break;
            //秒杀
            case 'seckill':
                $keyword = $_POST['keyword'];
                $seckill = D('Shop_goods');
                $seckill_where = array();
                $seckill_where['status'] = 1;
                $seckill_where['store_id'] = $this->now_store['store_id'];
                //$seckill_where['seckill_price'] = array('gt', 0);

                if($shop_type==1){
                    $where['goods_type']=array('neq',1);
                }

                //获取商城商品
                if($shop_type==2){
                    $where['cat_fid']=array('gt',0);
                }

//                $seckill_where['seckill_close_time'] = array('gt', time());

                //$now_time = time();

                //$seckill_where['_string'] = '(seckill_type=0 AND seckill_open_time <= '.$now_time.' AND seckill_close_time > '.$now_time.') OR (seckill_type=1)';

                if ($keyword) {
                    $seckill_where['name'] = $keyword;
                }

                $seckill_list = $seckill->get_list_by_storeid_diypage_skill($this->now_store['store_id'], $shop_type, $seckill_where,8);
                $this->assign('seckill_page', $seckill_list['page_bar']);

                // 标识已选择过的活动
                $this->assign('seckills', $seckill_list['good_list']);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $keyword);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('activity_seckill_ajax');
                } else {
                    $this->display('activity_seckill');
                }

                break;
        }
    }

     //选择餐饮选择快店商品
    public function select_from_shop()
    { 
//        $list = $data['good_list'];
        $goods_id = $_GET['goods_id'];
        $sort_id = $_GET['sort_id'];
        $keyword = $_GET['keyword'];
        $where['sort_id'] = $sort_id;
        if ($keyword) {
            $where['name'] = $keyword;
        }
        $data = D('Shop_goods')->get_list_by_storeid_diypage($this->now_store['store_id'],1,$where);
        $list = $data['good_list'];

        //已选择的商品不能重复选择
        $where = array();
        $where['f.store_id'] = $this->now_store['store_id'];
        $where['s.sort_id'] = $sort_id;
        $goods_arr = M('Foodshop_goods_new')->field('f.goods_id')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where($where)->select();
        $goods_id_arr = array();
        foreach ($goods_arr as $key => $value) {
            $goods_id_arr[] = $value['goods_id'];
        }
        foreach ($list as $k => &$v) {         
            if (in_array($v['goods_id'], $goods_id_arr)) {
                $v['choice_status'] = 0;
                $v['tip'] = '已选择';
            }
        }

        $data['good_list'] = $list;
        $this->assign('data',$data);
        $this->assign('keyword',$keyword);
        $this->display();
    }


    public function select_store(){
        $province_id = I("province_id");
        $city_id = I("city_id");

        $selecteditemsArr = $_GET['selecteditems'] ? explode(',', $_GET['selecteditems']) : [];
        $this->assign('selecteditemsArr', $selecteditemsArr);

        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['have_shop'] = '1';
        $condition_merchant_store['status'] = '1';
        $where="`s`.`store_id`=`ss`.`store_id` AND `s`.`status`='1' AND `s`.`have_shop`='1'";
        if($province_id){
            $where .= " and `s`.province_id=".$province_id;
        }
        if($city_id){
            $where .= " and `s`.city_id=".$city_id;
        }
        if($_GET['search']){
            $where.=" AND s.name like '%{$_GET['search']}%'";
        }
        $count_store = M()->table(array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant_store_shop'=>'ss'))->where($where)->count();

        import('@.ORG.diypage');
        $p = new Page($count_store, 8);

        $store_list = M()->field("`s`.`store_id`, `s`.`pic_info`, `s`.`name`, `s`.`adress`, `s`.`phone`, `s`.`sort`,`ss`.`sale_count`,`ss`.`deliver_type`")->table(array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant_store_shop'=>'ss'))->where($where)->order("`s`.`sort` DESC,`s`.`store_id` ASC")->limit($p->firstRow . ',' . $p->listRows)->select();

        $store_image_class = new store_image();
        foreach($store_list as &$value){
            $tmp_pic_arr = explode(';',$value['pic_info']);
            $value['image'] = $store_image_class->get_image_by_path($tmp_pic_arr[0]);

            if ($selecteditemsArr && in_array($value['store_id'], $selecteditemsArr)) {
                $value['is_choose'] = true;
                $is_choose = true;
            }
            $value['url'] = C('config.site_url').'/wap.php?g=Wap&c=Shop&a=index&shop-id='.$value['store_id'];
        }

        $this->assign('store_list', $store_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }
}

?>