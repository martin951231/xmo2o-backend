<?php
/*
 * 首页5图广告
 *
 */
class Plat_five_adverAction extends BaseAction{
	public function index(){
        $_GET['city_id'] = $_GET['city_id'] ?? -1;
        $_GET['status'] = $_GET['status'] ?? -1;

		$database_five_adver_group = D('Plat_five_adver_group');

		$where = [];
		if($_GET['city_id'] >= 0){
            $where['city_id'] = $_GET['city_id'];
        }
        if($_GET['status'] >= 0){
            $where['status'] = $_GET['status'];
        }
		
        $count_group = $database_five_adver_group->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count_group, 30);
		$group_list = $database_five_adver_group->where($where)->order('sort DESC,`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        //增加城市筛选
        $citys = [['area_id' => -1, 'area_name' => '全部'], ['area_id' => 0, 'area_name' => '通用']];
        $groupCityIds = $database_five_adver_group->getField('city_id', true);
        $groupCityIds = array_filter(array_unique($groupCityIds));
        if ($groupCityIds) {
            $area = D('Area')->where(['area_id' => ['IN', $groupCityIds]])->field('area_id,area_name')->select();
            $area && $citys = array_merge($citys, $area);
        }
        $this->assign('citys', $citys);
		
		$this->assign('group_list',$group_list);
		$pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
		
		$this->display();
	}
	public function add(){

        //是否为区域管理员并且开启可视化多模块设置
        $is_area_many_city = 0;
        if($this->system_session['open_admin_area']==1&&C('customization.open_view_multi_city')==1){
            $is_area_many_city = 1;
        }
        $this->assign('is_area_many_city',$is_area_many_city);
		$this->display();
	}
	public function edit(){
		$pigcms_id = intval($_GET['id']);

		$database_five_adver_group = D('Plat_five_adver_group');
		$now_group = $database_five_adver_group->where(array('pigcms_id'=>$pigcms_id))->find();
		$this->assign('now_group',$now_group);

        //是否为区域管理员并且开启可视化多模块设置
        $is_area_many_city = 0;
        if($this->system_session['open_admin_area']==1&&C('customization.open_view_multi_city')==1){
            $is_area_many_city = 1;
        }
        $this->assign('is_area_many_city',$is_area_many_city);
		$this->display();
	}

	public function modify(){
		$image = D('Image')->handle($this->system_session['id'], 'system', 0, array('size' => 10), false);
		if (!$image['error']) {
			$_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
		} else {
			$this->frame_submit_tips(0, $image['message']);
		}
		

        if($_POST['currency'] == 1 || (empty($_POST['province_idss'])&&C('customization.open_view_multi_city')!=1)){
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

		$database_five_adver_group = D('Plat_five_adver_group');
		if($id = $activity_id = $database_five_adver_group->data($_POST)->add()){
			D('Image')->update_table_id('/upload/system/' . $_POST['pic'], $id, 'system');
			D('Config')->clearDataCacheDir('plat_five_adver');
			$this->frame_submit_tips(1,'添加成功！');
		}else{
			$this->frame_submit_tips(0,'添加失败！请重试~');
		}
	}

	public function amend(){

		$id = $_POST['pigcms_id']??'';
		if(!$id){
            $this->frame_submit_tips(0,'参数错误');
        }
        $database_five_adver_group = D('Plat_five_adver_group');

		$info = $database_five_adver_group->where('pigcms_id = '.$id)->find();
        if(!$info){
            $this->frame_submit_tips(0, '信息不存在！');
        }

        if($_POST['currency'] == 1 || (empty($_POST['province_idss'])&&C('customization.open_view_multi_city')!=1)){
            $_POST['province_id']   =   0;
            $_POST['city_id']   =   0;
            $_POST['city_ids']   =   '';
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
        
		if($_FILES['pic'] && $_FILES['pic']['error'] != 4){
			$image = D('Image')->handle($this->system_session['id'], 'system', 0, array('size' => 10), false);
			if (!$image['error']) {
				$_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
			} else {
				$this->frame_submit_tips(0, $image['message']);
			}
		}
		if(isset($_POST["dosubmit"])){
		    unset($_POST["dosubmit"]);
        }
        $save = M('Plat_five_adver_group')->save($_POST);
		if($save||($save == 0)){
			D('Image')->update_table_id('/upload/system/' . $_POST['pic'], $id, 'system');
			D('Config')->clearDataCacheDir('plat_five_adver');
			$this->frame_submit_tips(1,'修改成功！');
		}else{
			$this->frame_submit_tips(0,'修改失败！请重试~');
		}
	}

	public function del(){
		$pigcms_id = intval($_POST['pigcms_id']);
		$database_five_adver_group = D('Plat_five_adver_group');

		$now_group = $database_five_adver_group->where(array('pigcms_id'=>$pigcms_id))->find();
		if (!$now_group) {
			$this->error('广告组不存在');
		}

		if($database_five_adver_group->where(array('pigcms_id'=>$pigcms_id))->delete()){
			// 删除活动
			$database_plat_five_adver = D('Plat_five_adver');
			$database_plat_five_adver->where(array('group_id'=>$pigcms_id))->delete();
			D('Config')->clearDataCacheDir('plat_five_adver');
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！请重试~');
		}
	}

	public function activity_list(){
		$database_plat_buy_active_group = D('Plat_five_adver_group');
		$now_group = $database_plat_buy_active_group->where(array('pigcms_id'=>$_GET['group_id']))->find();
		$this->assign('now_group',$now_group);
		
		$group_id = $now_group['pigcms_id'];
		
		if($now_group['from'] == 0){
			$database_plat_five_adver = D('Plat_five_adver');
			$active_list = $database_plat_five_adver
							->alias('a')
							->Distinct(true)
							->field('a.*,s.`name` AS `store_name`')
							->join('left join pigcms_merchant_store as s on a.store_id=s.store_id')
							->where("`group_id`='$group_id'")
							->order('`a`.`sort` DESC,`a`.`id` DESC')
							->select();
		}else{
			$database_plat_five_adver = D('Plat_five_adver');
			$active_list = $database_plat_five_adver
							->where("`group_id`='$group_id'")
							->order('`sort` DESC,`id` DESC')
							->select();
		}
		$this->assign('active_list',$active_list);
		
		if($now_group['from'] == 0){
			$this->display();
		}else{
			$this->display('activity_list_adver');
		}
	}
	public function activity_add(){
		if($_GET['id']){
			$database_plat_five_adver = D('Plat_five_adver');
			$now_active = $database_plat_five_adver->where(array('id'=>$_GET['id']))->find();
			$this->assign('now_active',$now_active);
			
			$group_id = $now_active['group_id'];
		}else{
			$group_id = $_GET['group_id'];
		}
		$database_plat_buy_active_group = D('Plat_five_adver_group');
		$now_group = $database_plat_buy_active_group->where(array('pigcms_id'=>$group_id))->find();
		$this->assign('now_group',$now_group);
		
		if($now_group['from'] == 0){
			$this->display();
		}else{
			$this->display('activity_add_adver');
		}
	}
	
	public function activity_modify(){
		$database_plat_five_adver = D('Plat_five_adver');
		$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
		if(empty($_POST['id'])){
			if($database_plat_five_adver->data($_POST)->add()){
				D('Config')->clearDataCacheDir('plat_five_adver');
				$this->success('添加成功！');
			}else{
				$this->error('添加失败！');
			}
		}else{
			if($database_plat_five_adver->where(array('id'=>$_POST['id']))->data($_POST)->save()){
				D('Config')->clearDataCacheDir('plat_five_adver');
				$this->success('保存成功！');
			}else{
				$this->error('保存失败！');
			}
		}
	}

	public function activity_del(){
		$id = intval($_POST['id']);
		$database_plat_five_adver = D('Plat_five_adver');

		$now_active = $database_plat_five_adver->where(array('id'=>$id))->find();
		if (!$now_active) {
			$this->error('内容不存在');
		}

		if($database_plat_five_adver->where(array('id'=>$id))->delete()){
			D('Config')->clearDataCacheDir('plat_five_adver');
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！请重试~');
		}
	}
	
	
	public function activity_modify_adver(){
		if($_FILES['active_img'] && $_FILES['active_img']['error'] != 4){
			$image = D('Image')->handle($this->system_session['id'], 'system', 0, array('size' => 10), false);
			if (!$image['error']) {
				$_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
			} else {
				$this->frame_submit_tips(0, $image['message']);
			}
		}
		
		$database_plat_five_adver = D('Plat_five_adver');
		$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
		if(empty($_POST['id'])){
			if($database_plat_five_adver->data($_POST)->add()){
				D('Config')->clearDataCacheDir('plat_five_adver');
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！');
			}
		}else{
			if($database_plat_five_adver->where(array('id'=>$_POST['id']))->data($_POST)->save()){
				D('Config')->clearDataCacheDir('plat_five_adver');
				$this->frame_submit_tips(1,'保存成功！');
			}else{
				$this->frame_submit_tips(0,'保存失败！');
			}
		}
	}
	
	
	public function choose_product(){
		$active = $_GET['active'] ? $_GET['active'] : 'group';
		
		if($_GET['keyword']){
			$_POST['keyword'] = $_GET['keyword'];
		}
		
		if($_GET['page']){
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
		
		if($active == 'shop'){
			$_POST['keyword'] && $where['name'] = $_POST['keyword'];
            // 排除已关闭的分类
            $showSortIds = D('Shop_goods_sort')->getAllShowChildSortIds();
            $where['showSortIds'] = $showSortIds;
            
			$return = D('Shop_goods')->get_list_by_storeid_diypage('-1',1,$where);

            $storeName = $storeNameFunc(array_unique(array_column($return['good_list'],'store_id')));
            
			foreach($return['good_list'] as $value){
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
					'product_link' => $this->config['site_url'].'/wap.php?c=Shop&a=index#good-'.$value['store_id'].'-'.$value['goods_id'],
				);
			}
			$return_page['page_bar'] = $return['page_bar'];
		}else if($active == 'mall'){
			$_POST['keyword'] && $where['name'] = $_POST['keyword'];
			$return = D('Shop_goods')->get_list_by_storeid_diypage('-1',2,$where);

            $storeName = $storeNameFunc(array_unique(array_column($return['good_list'],'store_id')));
            
			foreach($return['good_list'] as $value){
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
					'product_link' => $this->config['site_url'].'/wap.php?c=Mall&a=detail&goods_id='.$value['goods_id'],
				);
			}
			$return_page['page_bar'] = $return['page_bar'];
		} else if ($active == 'new_mall') {//新版商城
            $_POST['keyword'] && $where['name'] = $_POST['keyword'];
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
        }else{
		
			$now_time = time();
			$_POST['keyword'] && $where['name'] = $_POST['keyword'];
			$condition_where = "`gs`.`group_id`=`g`.`group_id` AND `gs`.`store_id`=`ms`.`store_id` AND `ms`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1'AND `ms`.`status`='1' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
			
			if ($_GET['city_id']) {
				$condition_where .= " AND `gs`.`city_id`='{$_GET['city_id']}' ";
			}
			if ($_POST['keyword']) {
				$condition_where .= " AND `g`.`name` like '%{$_POST['keyword']}%' ";
			}
			$condition_table  = array(C('DB_PREFIX').'group'=>'g', C('DB_PREFIX').'group_store'=>'gs', C('DB_PREFIX').'merchant_store'=>'ms', C('DB_PREFIX').'merchant'=>'m');
			$condition_field  = '`g`.*,`gs`.*';
			
			import('@.ORG.diypage');
			$count_group = D('')->table($condition_table)->where($condition_where)->count('DISTINCT `g`.`group_id`');
			$p = new Page($count_group, 8);
			$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order('`g`.`group_id` DESC')->group('`g`.`group_id`')->limit($p->firstRow.','.$p->listRows)->select();
			$group_image_class = new group_image();
			
			$return_page['page_bar'] = $p->show();
			
            $storeName = $storeNameFunc(array_unique(array_column($group_list,'store_id')));
            
			foreach($group_list as $key=>$value){				
				$tmp_pic_arr = explode(';',$value['pic']);
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
					'product_link' => $this->config['site_url'].'/wap.php?c=Group&a=detail&group_id='.$value['group_id'],
				);
			}
		}
		$this->assign($return_page);
		$this->display();
	}
	
	public function store_info(){
		$store = D('Merchant_store')->get_store_by_storeId($_POST['store_id']);
		$this->success($store);
	}
}
