<?php

/*
 * 可视化页面
 *
 * 
 */

class ViewpageAction extends BaseAction
{
    public function index()
    {
		
		if($this->config['single_system_type'] == 'shop'){
			redirect(U('shop_index'));
		}
		//查询是否隐藏老版商城首页
        $mall_show = M('Plugin')->where(['plugin_name'=>'餐饮管理','plugin_label'=>'foodshop'])->getField('show');
        $this->assign('mall_show', $mall_show);
        $home_search_ranking_show = D('System_menu')->where(['component'=>'platformSearchRankingConfig','status'=>1])->find();
        $this->assign('home_search_ranking_show', ($home_search_ranking_show?1:0));
        $this->display();
    }

    public function add()
    {
        $this->assign('bg_color', '#F3F3F3');
        $this->display();
    }

    public function change_index_tpl()
    {
        D('Config')->remove_cache();
        if ($this->config['mobile_index_tpl']) {
            if (D('Config')->where(array('name' => 'mobile_index_tpl'))->save(array('value' => $_POST['tpl']))) {
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        } else {
            if (D('Config')->add(array('name' => 'mobile_index_tpl', 'value' => $_POST['tpl'], 'info' => '移动端首页模板', 'gid' => 0, 'status' => 1))) {
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        }
    }
	
	public function change_slider_row()
    {
        D('Config')->remove_cache();
        if ($this->config['mobile_index_slider_row']) {
            if (D('Config')->where(array('name' => 'mobile_index_slider_row'))->save(array('value' => $_POST['row']))) {
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        } else {
            if (D('Config')->add(array('name' => 'mobile_index_slider_row', 'value' => $_POST['row'], 'info' => '移动端首页导航栏导航行数', 'gid' => 0, 'status' => 1))) {
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        }
    }

    public function change_plat_news_show()
    {
        D('Config')->remove_cache();
        if (isset($this->config['mobile_index_plat_news'])) {
            if (D('Config')->where(array('name' => 'mobile_index_plat_news'))->save(array('value' => $_POST['value']))) {
				
				//删除缓存
				D('Config')->clearDataCacheDir('system_config');
			
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        } else {
            if (D('Config')->add(array('name' => 'mobile_index_plat_news', 'value' => $_POST['value'], 'info' => '移动端首页是否展示平台快报', 'gid' => 0, 'status' => 1))) {
				
				//删除缓存
				D('Config')->clearDataCacheDir('system_config');
			
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        }
    }
	
	public function change_classify_show()
    {
        D('Config')->remove_cache();
        if (isset($this->config['wap_home_show_classify'])) {
            if (D('Config')->where(array('name' => 'wap_home_show_classify'))->save(array('value' => $_POST['value']))) {
				
				//删除缓存
				D('Config')->clearDataCacheDir('system_config');
				
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        } else {
            if (D('Config')->add(array('name' => 'wap_home_show_classify', 'value' => $_POST['value'], 'info' => '移动端首页是否展示平台快报', 'gid' => 0, 'status' => 1))) {
				
				//删除缓存
				D('Config')->clearDataCacheDir('system_config');
				
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        }
    }
    
    public function change_background_show()
    {
        D('Config')->remove_cache();
        
        if (D('Config')->where(array('name' => 'shop_index_page_background_show'))->save(array('value' => $_POST['value']))) {

            //删除缓存
            D('Config')->clearDataCacheDir('system_config');

            $this->success('切换成功');
        } else {
            $this->error('切换失败');
        }
    }

    public function banner_ticket_entrance(){
        $db = M('Entrance');
        $where = array();
        $where['key'] = 'banner_ticket_entrance';
        $data = $db->where($where)->find();
        if(IS_POST){
            if(strlen($_POST['name'])>12){
                $this->error('抱歉，按钮名称字符过长');
            }
            if(strlen($_POST['des'])>60){
                $this->error('抱歉，按钮描述字符过长');
            }
            $_POST['url'] = str_replace('&amp;','&',$_POST['url']);
			
			$r = false;
            if(empty($data)){
                $_POST['key'] = 'banner_ticket_entrance';
                $r = $db->add($_POST);
            }else{
                $r = $db->where($where)->save($_POST);
            }
            if($r !== false){
                $this->success('保存成功！');
            }else{
                $this->error('保存失败！');
            }
        }else{
            $this->assign('data',$data);
            $this->display();
        }

    }

    public function ajax_upload_pic()
    {
        if ($_FILES['file']['error'] != 4) {
            $param = array('size' => $this->config['group_pic_size']);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
// 			$param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = 640;
            $param['thumbMaxHeight'] = 420;
            $param['thumbRemoveOrigin'] = false;
            $image = D('Image')->handle($this->merchant_session['mer_id'], 'background', 1, $param,false);
            if ($image['error']) {
                exit(json_encode(array('error' => 1,'message' =>$image['msg'])));
            } else {
                $title = $image['title']['file'];
                $image_tmp = explode(',', $title);
                $url = file_domain() . '/upload/background/' . $image_tmp[0] . '/' . $image_tmp['1'];
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            }
        } else {
            exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
        }
    }



    public function footer()
    {
        $menu_category = M('Home_menu_category')->field('cat_id')->where(array('cat_key' => 'app_footer'))->find();
        if ($menu_category) {
            $footer_menu_list = M('Home_menu')->where(array('status' => '1', 'cat_id' => $menu_category['cat_id']))->order('`sort` DESC,`id` ASC')->limit(5)->select();
        }
        $this->assign('footer_menu_list', $footer_menu_list);
        $this->display();
    }

    //更换底部导航颜色
    public function change_footer_color()
    {
        D('Config')->remove_cache();
        $name = $_POST['name'];
        $condition = '';
        $info = '';
        if ($name == 'app_footer_bgcolor') {
            $condition = $name;
            $value = $_POST['color'];
            $info = '移动端底部导航背景颜色';
        }
        if ($name == 'app_footer_txtactivecolor') {
            $condition = $name;
            $value = $_POST['color'];
            $info = '移动端底部导航文字选中颜色';
        }

        if ($name == 'app_footer_txtcolor') {
            $condition = $name;
            $value = $_POST['color'];
            $info = '移动端底部导航文字默认颜色';
        }

        if ($name == 'coupon_main_color') {
            $condition = $name;
            $value = $_POST['color'];
            $info = '领券中心主题颜色';
        }

        if (empty($condition)) {
            $this->error('该配置项不存在');
        }

        if (isset($this->config[$condition])) {
            if (D('Config')->where(array('name' => $condition))->save(array('value' => $value))) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        } else {
            if (D('Config')->add(array('name' => $condition, 'value' => $value, 'info' => '移动端首页是否展示平台快报', 'gid' => 0, 'status' => 1))) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
    }

    public function guess_like()
    {
        $this->assign('bg_color', '#F3F3F3');
        $config_data = D('Config_data')->get_config_data();
        $this->assign('config_data', $config_data);

        // 活动
        $condition['a.business_type'] = 'plat';
        $activity_list = M('System_guess_like_activity')->field('a.*,s.name as store_name,m.name as mer_name')->join(' as a left join '.C('DB_PREFIX').'merchant_store as s on a.store_id=s.store_id  left join '.C('DB_PREFIX').'merchant as m on m.mer_id=s.mer_id')->where($condition)->select();
        foreach ($activity_list as &$value) {
            $value['image'] = explode(';', $value['image']);
        }
        $this->assign('activity_list', $activity_list);
        $condition = [];
        $condition['business_type'] = 'plat';
        $goods_id_arr = array_column(M('System_find_goods')->where($condition)->select(), 'goods_id');
        $goods_ids = implode(',', $goods_id_arr);
        $this->assign('goods_ids',$goods_ids);

        $this->display();
    }

    public function guess_like_post()
    {
        unset($_POST['dosubmit']);;

        // 发现好菜商品
        // 已添加商品id
        $condition = [];
        $condition['business_type'] = 'plat';
        $goods_id_arr = array_column(M('System_find_goods')->where($condition)->select(), 'goods_id');
        $goods_id_arr = $goods_id_arr ? $goods_id_arr : [];

        // 需要删除的商品
        if ($_POST['relation_goods_id']) {
            $delete_goods = array_diff($goods_id_arr, $_POST['relation_goods_id']);
        }else{
            // 全部删除
            $delete_goods = $goods_id_arr;
        }

        if ($delete_goods) {
            $condition['goods_id'] = ['in',$delete_goods];
            if(!M('System_find_goods')->where($condition)->delete()){
                $this->error('保存失败');
            }
        }

        // 新增的商品
        $add_goods = array_diff( $_POST['relation_goods_id'], $goods_id_arr);

        // 发现好菜商品
        if ($add_goods) {
            $add_data = [];
            foreach ($add_goods as $key => $value) {
                $add_data[] = [
                    'goods_id' => $value,
                    'business_type' => 'plat',
                    'create_time' => time(),
                ];
            }
            if(!M('System_find_goods')->addAll($add_data)){
                $this->error('保存失败');
            }
        }

        unset($_POST['relation_goods_id']);

        if(!D('Config_data')->save_config_data_all($_POST)){
			$this->error('保存失败');
		}
        $this->success('保存成功');
    }

    /**
     *外卖首页配置
     */
    public function shop_index()
    {
        $this->display();
    }

    // 外卖活动列表
    public function shop_activity()
    {
        // 活动
        $condition['a.business_type'] = 'shop';
        $activity_list = M('System_guess_like_activity')->field('a.*,s.name as store_name,m.name as mer_name')->join(' as a left join '.C('DB_PREFIX').'merchant_store as s on a.store_id=s.store_id  left join '.C('DB_PREFIX').'merchant as m on m.mer_id=s.mer_id')->where($condition)->select();
        foreach ($activity_list as &$value) {
            $value['image'] = explode(';', $value['image']);
        }
        $this->assign('activity_list', $activity_list);

        $this->display();
    }

    // 外卖发现好菜
    public function shop_guess_like()
    {
        $condition['business_type'] = 'shop';
        $goods_id_arr = array_column(M('System_find_goods')->where($condition)->select(), 'goods_id');
        $goods_ids = implode(',', $goods_id_arr);
        $this->assign('goods_ids',$goods_ids);
        $this->display();
    }

    public function shop_guess_like_post()
    {
        $database_config = D('Config');
        if (isset($_POST['shop_index_find_good_goods_title'])) {
            $data['name'] = 'shop_index_find_good_goods_title';
            $data['value'] = trim(stripslashes(htmlspecialchars_decode($_POST['shop_index_find_good_goods_title'])));
        }
        $database_config->data($data)->save();
       
        //删除缓存
        D('Config')->clearDataCacheDir('system_config');


        // 已添加商品id
        $condition = [];
        $condition['business_type'] = 'shop';
        $goods_id_arr = array_column(M('System_find_goods')->where($condition)->select(), 'goods_id');
        $goods_id_arr = $goods_id_arr ? $goods_id_arr : [];

        // 需要删除的商品
        if ($_POST['relation_goods_id']) {
            $delete_goods = array_diff($goods_id_arr, $_POST['relation_goods_id']);
        }else{
            // 全部删除
            $delete_goods = $goods_id_arr;
        }

        if ($delete_goods) {
            $condition['goods_id'] = ['in',$delete_goods];
            if(!M('System_find_goods')->where($condition)->delete()){
                $this->error('保存失败');
            }
        }

        // 新增的商品
        $add_goods = array_diff( $_POST['relation_goods_id'], $goods_id_arr);

        // 发现好菜商品
        if ($add_goods) {
            $add_data = [];
            foreach ($add_goods as $key => $value) {
                $add_data[] = [
                    'goods_id' => $value,
                    'business_type' => 'shop',
                    'create_time' => time(),
                ];
            }
            if(!M('System_find_goods')->addAll($add_data)){
                $this->error('保存失败');
            }
        }
        $this->success('保存成功');
    }

    // 添加编辑活动
    public function activity_edit()
    {
        if (IS_POST) {
            $id = $_POST['id'];

            if (!$_POST['store_id']) {
                $this->error('请选择店铺');
            }

            if(count($_POST['image'])!=3){
                $this->error('必须上传3张图片');
            }

            $_POST['image'] = implode(';', $_POST['image']);

            if ($id) {
                if(M('System_guess_like_activity')->where(['id'=>$_POST['id']])->save($_POST)!==false){
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }else{ 
                $_POST['create_time'] = time();
                if(M('System_guess_like_activity')->add($_POST)){
                    $this->success('添加成功');
                }else{
                    $this->error('添加失败');
                }
            }
        }else{
            if ($_GET['id']) {
                // 活动详情
                $now_activity = M('System_guess_like_activity')->where(['id'=>$_GET['id']])->find();
                $now_activity['image'] = explode(';', $now_activity['image'] );
                $this->assign('now_activity', $now_activity);

                // 店铺名称
                $now_store = M('merchant_store')->field('name')->where(['store_id'=>$now_activity['store_id']])->find();
                $this->assign('now_store', $now_store);
            }
        }
        $this->display();
    }

    // 获得活动列表
    public function activity_list()
    {
        $business_type = $_GET['business_type'];
        $condition['a.business_type'] = $business_type;
        $activity_list = M('System_guess_like_activity')->field('a.*,s.name as store_name,m.name as mer_name')->join(' as a left join '.C('DB_PREFIX').'merchant_store as s on a.store_id=s.store_id  left join '.C('DB_PREFIX').'merchant as m on m.mer_id=s.mer_id')->where($condition)->select();
        foreach ($activity_list as &$value) {
            $value['image'] = explode(';', $value['image']);
        }
        exit(json_encode(array('error' => 0,'list' =>$activity_list)));
        
    }

    // 删除活动
    public function activity_del()
    {   $id = $_POST['id'];
        $res = M('System_guess_like_activity')->where(['id'=>$id])->delete();
        if ($res) {
            exit(json_encode(array('error' => 0,'msg' =>'删除成功')));
        }else{
            exit(json_encode(array('error' => 1,'msg' =>'删除失败')));
        }
    }

    // 选择外卖店铺
    public function select_store(){
        if (!empty($_POST['search'])) {
            $condition['_string'] = "name like '%" . $_POST['search'] . "%'";
        }

        $order_string = 's.`store_id` DESC';
        $database_merchant_store_shop = D('Merchant_store_shop');

        $condition['s.status'] = 1;
        $condition['s.have_shop'] = 1;

        $count = $database_merchant_store_shop->join(' as ss join '.C('DB_PREFIX').'merchant_store as s on ss.store_id=s.store_id')->where($condition)->count();
        import('@.ORG.condition');
        $p = new Page($count, 5);
        $list = $database_merchant_store_shop->field('ss.*,s.name,s.mer_id')->join(' as ss join '.C('DB_PREFIX').'merchant_store as s on ss.store_id=s.store_id')->where($condition)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $p->show());
        $this->display();
    }
    
     
    public function mall_index()
    {
        $this->display();
    }
    
    public function ajax_upload_pic_k()
    { 
        if ($_FILES['file']['error'] != 4) {
            $width = '120,120';
            $height = '120,120';
            $param = array('size' => 5);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
            $param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = $width;
            $param['thumbMaxHeight'] = $height;
            $param['thumbRemoveOrigin'] = false;
            $image = D('Image')->handle($this->merchant_session['mer_id'], 'foodshopstore', 1, $param);
            if ($image['error']) {
                exit(json_encode(array(
                    'error' => 1,
                    'message' => $image['msg']
                )));
            } else {
                $title = $image['title']['imgFile'];
                $goods_image_class = new foodshopstore_image();
                $url = $goods_image_class->get_image_by_path($title, '-1');
                if ($_GET['size_type'] == 'big') {
                   $url = $url['image'];
                }else{
                   $url = $url['s_image'];
                }

                exit(json_encode(array(
                    'error' => 0,
                    'url' => $url,
                    'title' => $title
                )));
            }
        } else {
            exit(json_encode(array(
                'error' => 1,
                'message' => L_('没有选择图片')
            )));
        }
    }

    // 商品列表
    public function select_goods(){
        $selected = trim(I("selected"),',');
        $this->assign("selected", $selected);

        if($selected){
            $selected_arr = explode(',', $selected);
        }

        import('@.ORG.diypage');
        $condition = array();
        
        $database = D(_view('Shop_goods'));
        $condition['g.goods_type']=array('neq',1);
        $condition['g.status'] = 1;
        if($_GET['keyword']){
            $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
        }

         // 不能选择附属分类商品、
        // $shop_goods_sort = M('Shop_goods_sort')->where(['status'=>1])->select();
        // $sort_id = array_column($shop_goods_sort, 'sort_id');
        // $condition['g.sort_id'] = ['not in',$sort_id];


        // 排除已关闭的分类
        $showSortIds = D('Shop_goods_sort')->getAllShowChildSortIds(0, [], true, true, false);
        if (!empty($showSortIds)) {
            $condition['g.sort_id'] = ['in',$showSortIds];
        }else{
            $condition['g.sort_id'] = null;
        }

        $condition['s.status'] = 1;
        $condition['m.status'] = 1;
        $condition['s.have_shop'] = 1;
        $condition['g.is_subsidiary'] = 0;

        $store_id = I('store_id');
        $mer_id = I('mer_id');
        if(!empty($store_id)) {
            $condition['s.store_id'] = $store_id;

            $where['s.status'] = 1;
            $where['m.status'] = 1;
            $where['s.have_shop'] = 1;
            $where['s.store_id'] = $store_id;
            $mer_store_sel = M('merchant_store')->join("as s left join ".C("DB_PREFIX")."merchant as m on m.mer_id=s.mer_id")->field('s.store_id,s.name as store_name,m.mer_id,m.name as mer_name')->where($where)->select();
//            var_dump(M('merchant_store')->getLastSql());exit();
            $store_list = M('Merchant_store')->field('store_id,name')->where(['status'=>1,'have_shop'=>1,'mer_id'=>$mer_id])->order('store_id DESC')->select();

            $this->assign('mer_store_sel', $mer_store_sel[0]);
            $this->assign('store_list', $store_list);
        }

//        $condition['s.status'] = 1;
//        $condition['m.status'] = 1;
//        $condition['s.have_shop'] = 1;
        $field = 'g.goods_id, g.name, g.last_time, g.price,s.name as store_name';
        $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id"." left join ".C("DB_PREFIX")."merchant as m on m.mer_id=s.mer_id")->where($condition)->count();

        $p = new Page($count, 6);
        $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id"." left join ".C("DB_PREFIX")."merchant as m on m.mer_id=s.mer_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();

        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['select'] = 0;
            if(in_array($value['goods_id'], $selected_arr)){
                $goods_list[$key]['select'] = 1;
            }
        }

        if($store_id) {
            unset($condition['s.store_id']);
        }
        $mer_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id"." left join ".C("DB_PREFIX")."merchant as m on m.mer_id=s.mer_id")->field('s.store_id,s.name as store_name,m.mer_id,m.name as mer_name')->where($condition)->select();

        $mer_ids = [];
        foreach ($mer_list as $key => $value) {
            $mer_ids[$value['mer_id']] = $value['mer_name'];
        }

        $this->assign('mer_list',$mer_ids);
        $this->assign("keyword", $_GET['keyword']);
        $this->assign("good_list", $goods_list);
        $this->assign("pagebar", $p->show());

        $this->display();
    }

    public function getStoreList() {
        $mer_id = $_POST['mer_id'];

        $where['status'] = 1;
        $where['have_shop'] = 1;
        $where['mer_id'] = $mer_id;
        $store_list = M('Merchant_store')->field('store_id,name')->where($where)->order('store_id DESC')->select();

        if(!empty($store_list)){ //有值，组装数据
            $result['status'] = 200;
            $result['data'] = $store_list;
        }else{ //无值，返回状态码220
            $result['status'] = 220;
        }
        echo json_encode($result);
        exit();
    }

    public function ajaxGetGoods(){
        $selected = trim(I("selected"),',');
        $condition = array();
        $result = array();
        
        $database = D(_view('Shop_goods'));
        $condition['g.goods_id']=array('in',$selected);
        $field = 'g.goods_id, g.name, g.last_time, g.price, s.name as store_name, g.status,s.is_business_open';
        $shopgoods_list = $database->field($field)->join(' as g left join '.C('DB_PREFIX').'merchant_store as s on g.store_id=s.store_id')->where($condition)->select();
        foreach ($shopgoods_list as $key => $value) {
            $result[] = $shopgoods_list[$key];
        }
       
        echo json_encode($result);
        exit;
    }

    public function image_show(){
        $this->display();
    }

    public function foodshop_index() {
        $this->display();
    }

    public function baodi() {
        $this->display();
    }
	
	public function refresh_guess_like(){
		M('Process_plan')->where(['file' => 'plat_recommend'])->data(['plan_time' => time() - 1])->save();
        D('Config_data')->remove_cache();
	}


    public function change_mobile_index_scan()
    {
        D('Config')->remove_cache();
        if (isset($this->config['mobile_index_scan'])) {
            if (D('Config')->where(array('name' => 'mobile_index_scan'))->save(array('value' => $_POST['value']))) {

                //删除缓存
                D('Config')->clearDataCacheDir('system_config');

                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        } else {
            $this->error('切换失败');
        }
    }
}

?>