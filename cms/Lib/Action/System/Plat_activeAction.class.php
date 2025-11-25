<?php

/*
 * 首页超值抢购
 */

class Plat_activeAction extends BaseAction
{
    public function index()
    {
        $database_plat_buy_active_group = D('Plat_buy_active_group');
        $group_list = $database_plat_buy_active_group->order('`pigcms_id` DESC')->select();
        $this->assign('group_list', $group_list);
        $this->display();
    }

    public function add()
    {
        //是否为区域管理员并且开启可视化多模块设置
        $is_area_many_city = 0;
        if($this->system_session['open_admin_area']==1&&C('customization.open_view_multi_city')==1){
            $is_area_many_city = 1;
        }
        $this->assign('is_area_many_city',$is_area_many_city);
        $this->display();
    }

    public function edit()
    {
        $pigcms_id = intval($_GET['id']);
        $database_plat_buy_active_group = D('Plat_buy_active_group');
        $now_group = $database_plat_buy_active_group->where(array('pigcms_id' => $pigcms_id))->find();
        if ($now_group['city_id']) {
            $now_city = D('Area')->where(array('area_id' => $now_group['city_id']))->find();
            $now_group['province_id'] = $now_city['area_pid'];
        }
        $this->assign('now_group', $now_group);

        //是否为区域管理员并且开启可视化多模块设置
        $is_area_many_city = 0;
        if($this->system_session['open_admin_area']==1&&C('customization.open_view_multi_city')==1){
            $is_area_many_city = 1;
        }
        $this->assign('is_area_many_city',$is_area_many_city);
        $this->display();
    }

    public function modify()
    {
        $_POST['begin_time'] = strtotime($_POST['begin_time']);
        $_POST['end_time'] = strtotime($_POST['end_time']);

        if ( $_POST['column_type']==0) {
            $_POST['row_num'] = 0;
        }

        if($_POST['currency'] == 1 || (empty($_POST['province_idss'])&&C('customization.open_view_multi_city')!=1)){
            $_POST['city_ids'] = '';
            $_POST['province_id']   =   0;
            $_POST['city_id']   =   0;
        }else{
            if(C('customization.open_view_multi_city')==1){
                $_POST['province_id']	=	0;
                $_POST['city_id']	=	-1;
                $_POST['city_ids'] = '';
            }else{
                $_POST['city_id'] = $_POST['city_idss'];
                $_POST['province_id'] = $_POST['province_idss'];
            }
            unset($_POST['province_idss'],$_POST['city_idss']);
        }

        $database_plat_buy_active_group = D('Plat_buy_active_group');
        if ($activity_id = $database_plat_buy_active_group->data($_POST)->add()) {
            D('Config')->clearDataCacheDir('plat_buy_active');
            $this->frame_submit_tips(1, '添加成功！');
        } else {
            $this->frame_submit_tips(0, '添加失败！请重试~');
        }
    }

    public function amend()
    {
        $_POST['begin_time'] = strtotime($_POST['begin_time']);
        $_POST['end_time'] = strtotime($_POST['end_time']);

        if ( $_POST['column_type']==0) {
            $_POST['row_num'] = 0;
        }

        if($_POST['currency'] == 1 || (empty($_POST['province_idss'])&&C('customization.open_view_multi_city')!=1)){
            $_POST['city_ids'] = '';
            $_POST['province_id']   =   0;
            $_POST['city_id']   =   0;
        }else{
            if(C('customization.open_view_multi_city')==1) {
                $_POST['province_id']	=	0;
                $_POST['city_id']	=	-1;
                $_POST['city_ids'] = '';
            }else{
                $_POST['city_id'] = $_POST['city_idss'];
                $_POST['province_id'] = $_POST['province_idss'];
            }
            unset($_POST['province_idss'],$_POST['city_idss']);
        }

        $database_plat_buy_active_group = D('Plat_buy_active_group');
        if($database_plat_buy_active_group->data($_POST)->save() !== false){
            D('Config')->clearDataCacheDir('plat_buy_active');
            $this->frame_submit_tips(1, '修改成功！');
        } else {
            $this->frame_submit_tips(0, '修改失败！请重试~');
        }
    }

    public function del()
    {
        $pigcms_id = intval($_POST['pigcms_id']);
        $database_plat_buy_active_group = D('Plat_buy_active_group');

        $now_group = $database_plat_buy_active_group->where(array('pigcms_id' => $pigcms_id))->find();
        if (!$now_group) {
            $this->error('活动组不存在');
        }

        if ($database_plat_buy_active_group->where(array('pigcms_id' => $pigcms_id))->delete()) {
            // 删除活动
            $database_plat_buy_active = D('Plat_buy_active');
            $database_plat_buy_active->where(array('group_id' => $pigcms_id))->delete();
            D('Config')->clearDataCacheDir('plat_buy_active');
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！请重试~');
        }
    }

    public function activity_list()
    {
        $database_plat_buy_active_group = D('Plat_buy_active_group');
        $now_group = $database_plat_buy_active_group->where(array('pigcms_id' => $_GET['group_id']))->find();
        $this->assign('now_group', $now_group);
        $group_id = $now_group['pigcms_id'];
        $database_plat_buy_active = D('Plat_buy_active');
        $active_list = $database_plat_buy_active
            ->alias('a')
            ->Distinct(true)
            ->field('a.*,s.`name` AS `store_name`')
            ->join('join pigcms_merchant_store as s on a.store_id=s.store_id')
            ->where("`group_id`='$group_id'")
            ->order('`a`.`sort` DESC,`a`.`id` DESC')
            ->select();

        foreach($active_list as &$active_value){
            //下架或者不存在的商品不展示
            $active_value['error_message'] = '';
            if (in_array($active_value['active_type'], ['shop', 'mall'])) {
                $shop_good = D('Shop_goods')->where(['goods_id' => $active_value['other_id'], 'status' => 1])->find();
                if (empty($shop_good)) {
                    $active_value['error_message'] = '商品已下架';
                    continue;
                }
                $active_value['active_img'] = $shop_good['image'] ?: $active_value['active_img'];
            }else if ($active_value['active_type'] == 'group') {
                $group_goods = D('Group')->where(['group_id' => $active_value['other_id'], 'status' => 1])->find();
                if (empty($group_goods)) {
                    $active_value['error_message'] = '商品已下架';
                    continue;
                }
            }
            if ($active_value['store_id']) {
                $store_data = D('Merchant_store')->where(['store_id' => $active_value['store_id']])->find();
                if (!$store_data || $store_data['status'] != 1) {
                    $active_value['error_message'] = '店铺状态异常';
                    continue;
                }
            }
        }


        $this->assign('active_list', $active_list);

        $this->display();
    }

    public function activity_add()
    {
        if ($_GET['id']) {
            $database_plat_buy_active = D('Plat_buy_active');
            $now_active = $database_plat_buy_active->where(array('id' => $_GET['id']))->find();
            $this->assign('now_active', $now_active);
            $group_id = $now_active['group_id'];
        } else {
            $group_id = $_GET['group_id'];
        }
        $database_plat_buy_active_group = D('Plat_buy_active_group');
        $now_group = $database_plat_buy_active_group->where(array('pigcms_id' => $group_id))->find();
        $this->assign('now_group', $now_group);
        $this->display();
    }

    public function activity_modify()
    {
        $database_plat_buy_active = D('Plat_buy_active');
        $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
        if (empty($_POST['id'])) {
            if ($database_plat_buy_active->data($_POST)->add()) {
                D('Config')->clearDataCacheDir('plat_buy_active');
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        } else {
            if ($database_plat_buy_active->where(array('id' => $_POST['id']))->data($_POST)->save()) {
                D('Config')->clearCache();
                $this->success('保存成功！');
            } else {
                $this->error('保存失败！');
            }
        }
    }

    public function activity_del()
    {
        $id = intval($_POST['id']);
        $database_plat_buy_active = D('Plat_buy_active');
        $now_active = $database_plat_buy_active->where(array('id' => $id))->find();
        if (!$now_active) {
            $this->error('活动不存在');
        }
        if ($database_plat_buy_active->where(array('id' => $id))->delete()) {
            D('Config')->clearDataCacheDir('plat_buy_active');
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！请重试~');
        }
    }

    public function choose_product()
    {
        $active = $_GET['active'] ? $_GET['active'] : 'group';
        if ($_GET['keyword']) {
            $_POST['keyword'] = $_GET['keyword'];
        }

        if ($_GET['page']) {
            $_POST['page'] = $_GET['page'];
        }

        $storeNameFunc = function ($storeIds){
            if(!is_array($storeIds)){
                return [];
            }
            $storeInfo = M('Merchant_store')
                ->where(['store_id' => ['IN',$storeIds]])
                ->field('store_id, name')
                ->select();

            return array_column($storeInfo,'name','store_id');
        };
        
        $_POST['keyword'] && $where['name'] = $_POST['keyword'];
        if ($active == 'shop') {
            // 排除已关闭的分类
            $showSortIds = D('Shop_goods_sort')->getAllShowChildSortIds();
            $where['showSortIds'] = $showSortIds;
            $return = D('Shop_goods')->get_list_by_storeid_diypage('-1',1, $where);
            
            $storeName = $storeNameFunc(array_unique(array_column($return['good_list'],'store_id')));
            
            foreach ($return['good_list'] as $value) {
                $return_page['product_list'][] = array(
                    'product_id' => $value['goods_id'],
                    'product_type' => 'shop',
                    'store_id' => $value['store_id'],
                    'store_name' => $storeName[$value['store_id']] ?? '',
                    'product_name' => $value['name'],
                    'product_price' => $value['price'],
                    'old_price' => $value['old_price'],
                    'product_desc' => $value['describe'],
                    'product_img' => $value['pic_arr'][0]['title'],
                    'add_time' => $value['last_time'],
                    'product_link' => $this->config['site_url'] . '/wap.php?c=Shop&a=index#good-' . $value['store_id'] . '-' . $value['goods_id'],
                );
            }
            $return_page['page_bar'] = $return['page_bar'];
        } else if ($active == 'mall') {
            $return = D('Shop_goods')->get_list_by_storeid_diypage('-1', 2, $where);
            
            $storeName = $storeNameFunc(array_unique(array_column($return['good_list'],'store_id')));
            
            foreach ($return['good_list'] as $value) {
                $return_page['product_list'][] = array(
                    'product_id' => $value['goods_id'],
                    'product_type' => 'mall',
                    'store_id' => $value['store_id'],
                    'store_name' => $storeName[$value['store_id']] ?? '',
                    'product_name' => $value['name'],
                    'product_price' => $value['price'],
                    'old_price' => $value['old_price'],
                    'product_desc' => $value['describe'],
                    'product_img' => $value['pic_arr'][0]['title'],
                    'add_time' => $value['last_time'],
                    'product_link' => $this->config['site_url'] . '/wap.php?c=Mall&a=detail&goods_id=' . $value['goods_id'],
                );
            }
            $return_page['page_bar'] = $return['page_bar'];
        } else if ($active == 'newmall') {
            $return = D('Shop_goods')->get_list_by_new_mall($where);
            foreach ($return['good_list'] as $value) {
                $return_page['product_list'][] = array(
                    'product_id' => $value['goods_id'],
                    'product_type' => 'newmall',
                    'store_id' => $value['store_id'],
                    'product_name' => $value['name'],
                    'product_price' => $value['price'],
                    'old_price' => $value['max_price'],
                    'product_desc' => $value['describe'],
                    'product_img' => $value['image'],
                    'add_time' => $value['create_time'],
                    'product_link' => $value['link_url'],
                );
            }
            $return_page['page_bar'] = $return['page_bar'];
        } else if ($active == 'new_mall') {//新版商城
            $return = D('Mall_goods')->get_list_diypage($where);
            
            $storeName = $storeNameFunc(array_unique(array_column($return['good_list'],'store_id')));
            
            foreach ($return['good_list'] as $value) {
                $return_page['product_list'][] = array(
                    'product_id' => $value['goods_id'],
                    'product_type' => 'new_mall',
                    'store_id' => $value['store_id'],
                    'store_name' => $storeName[$value['store_id']] ?? '',
                    'product_name' => $value['name'],
                    'product_price' => $value['price'],
                    'old_price' => $value['price'],
                    'product_desc' => $value['describe'],
                    'product_img' => $value['image'],
                    'add_time' => $value['update_time'],
//                    'product_link' => $this->config['site_url'] . '/wap.php?c=Mall&a=detail&goods_id=' . $value['goods_id'],
                    'product_link' => $this->config['site_url'] . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $value['goods_id'],
                );
            }
            $return_page['page_bar'] = $return['page_bar'];
        } else {

            $now_time = $_SERVER['REQUEST_TIME'];
            $condition_where = "`gs`.`group_id`=`g`.`group_id` AND gs.store_id = st.store_id AND st.status=1 AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";

            if ($_GET['city_id'] != '' && $_GET['city_id'] != -1 && $_GET['city_id'] != 0) {
                $condition_where .= " AND `gs`.`city_id`='{$_GET['city_id']}' ";
            }
            if ($_POST['keyword']) {
                $condition_where .= " AND `g`.`name` like '%{$_POST['keyword']}%' ";
            }
            $condition_table = array(C('DB_PREFIX') . 'group' => 'g', C('DB_PREFIX') . 'group_store' => 'gs', C('DB_PREFIX') . 'merchant_store' => 'st');
            $condition_field = '`g`.*,`gs`.*';

            import('@.ORG.diypage');
            $count_group = D('')->table($condition_table)->where($condition_where)->count('DISTINCT `g`.`group_id`');
            $p = new Page($count_group, 8);
            $group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order('`g`.`group_id` DESC')->group('`g`.`group_id`')->limit($p->firstRow . ',' . $p->listRows)->select();
            $group_image_class = new group_image();

            $return_page['page_bar'] = $p->show();
     
            $storeName = $storeNameFunc(array_unique(array_column($group_list,'store_id')));
            foreach ($group_list as $key => $value) {
                $tmp_pic_arr = explode(';', $value['pic']);
                $return_page['product_list'][] = array(
                    'product_id' => $value['group_id'],
                    'product_type' => 'group',
                    'store_id' => $value['store_id'],
                    'store_name' => $storeName[$value['store_id']] ?? '',
                    'product_name' => $value['s_name'],
                    'product_price' => $value['price'],
                    'old_price' => $value['old_price'],
                    'product_desc' => $value['intro'],
                    'product_img' => $tmp_pic_arr[0],
                    'add_time' => $value['begin_time'],
                    'product_link' => $this->config['site_url'] . '/wap.php?c=Group&a=detail&group_id=' . $value['group_id'],
                );
            }
        }
//dump($return_page);die;
        $this->assign($return_page);
        $this->display();
    }

    public function store_info()
    {
        $store = D('Merchant_store')->get_store_by_storeId($_POST['store_id']);
        $this->success($store);
    }
}
