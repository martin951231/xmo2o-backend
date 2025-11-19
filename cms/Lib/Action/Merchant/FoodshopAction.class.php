<?php
/**
 * 餐饮管理
 * @author pigcms_03
 *
 */

class FoodshopAction extends BaseAction
{
	public function index()
	{
		$mer_id = $this->merchant_session['mer_id'];
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['mer_id'] = $mer_id;
		$condition_merchant_store['have_meal'] = '1';
		$condition_merchant_store['status'] = '1';
		if ($this->merchant_session['store_id']) {
		    $count_store = 1;
		} else {
		    $count_store = $database_merchant_store->where($condition_merchant_store)->count();
		}
		
		import('@.ORG.merchant_page');
		$p = new Page($count_store, 30);
		
		$sql = "SELECT `s`.`store_id`, `s`.`mer_id`, `s`.`name`, `s`.`adress`, `s`.`phone`, `s`.`sort`, `fs`.`store_id` AS `sid`, `fs`.`open_from_shop` FROM ". C('DB_PREFIX') . "merchant_store AS s LEFT JOIN  ". C('DB_PREFIX') . "merchant_store_foodshop AS fs ON `s`.`store_id`=`fs`.`store_id`";
		$sql .= " WHERE `s`.`mer_id`={$mer_id} AND `s`.`status`='1' AND `s`.`have_meal`='1'";
		if ($this->merchant_session['store_id']) {
		    $sql .= " AND `s`.`store_id`={$this->merchant_session['store_id']}";
		}
		$sql .= " ORDER BY `s`.`sort` DESC,`s`.`store_id` ASC";
		$sql .= " LIMIT {$p->firstRow}, {$p->listRows}";
		$store_list = D()->query($sql);
		$this->assign('store_list', $store_list);
		$this->assign('pagebar', $p->show());
		$this->display();
	}
	
	/* 店铺信息修改 */
	public function shop_edit()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$store_id = $now_store['store_id'];
		if(IS_POST){

			$_POST['store_id'] = $now_store['store_id'];
			if(substr($_POST['store_notice'], -1) == ' '){
				$_POST['store_notice'] = trim($_POST['store_notice']);
			}else{
				$_POST['store_notice'] = trim($_POST['store_notice']);
			}
			if(empty($_POST['store_category'])){
				$this->error(L_('请至少选一个分类！'));
			}
			$cat_ids = array();
			foreach ($_POST['store_category'] as $cat_a) {
				$a = explode('-', $cat_a);
				$cat_ids[] = array('cat_fid' => $a[0], 'cat_id' => $a[1]);
			}
			
			if(empty($_POST['pic'])){
				$this->error(L_('请至少上传一张图片'));
			}

			$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			foreach($_POST['pic'] as $kp => $vp){
			    $tmp_vp = explode(',', $vp);
			    $_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
			}
			$_POST['pic'] = implode(';', $_POST['pic']);
			
			if(empty($_POST['background'])){
				$this->error(L_('请上传一张点菜背景图片'));
			}
			
			foreach($_POST['background'] as $kp => $vp){
			    $tmp_vp = explode(',', $vp);
			    $_POST['background'][$kp] = $rand_num . ',' . $tmp_vp[1];
			}

			$_POST['background'] = implode(';', $_POST['background']);
		
			$_POST['store_discount'] = isset($_POST['store_discount']) ? intval($_POST['store_discount']) : 0;
			$_POST['discount_type'] = isset($_POST['discount_type']) ? intval($_POST['discount_type']) : 0;
			$_POST['book_day'] = isset($_POST['book_day']) ? intval($_POST['book_day']) : 1;
			$_POST['is_auto_order'] = isset($_POST['is_auto_order']) ? intval($_POST['is_auto_order']) : 1;
            $_POST['supplier_nav_first_name'] = isset($_POST['supplier_nav_first_name'])? $_POST['supplier_nav_first_name'] : '';
            $_POST['supplier_nav_first_link'] = isset($_POST['supplier_nav_first_link'])? $_POST['supplier_nav_first_link'] : '';
            $_POST['supplier_nav_second_name'] = isset($_POST['supplier_nav_second_name'])? $_POST['supplier_nav_second_name'] : '';
            $_POST['supplier_nav_second_link'] = isset($_POST['supplier_nav_second_link'])? $_POST['supplier_nav_second_link'] : '';
			$database_merchant_store_foodshop = D('Merchant_store_foodshop');
			
			$store_shop = $database_merchant_store_foodshop->field(true)->where(array('store_id' => $store_id))->find();

			$sysnc = isset($_POST['sysnc']) ? intval($_POST['sysnc']) : 0;
			unset($_POST['sysnc']);
			
			if ($store_shop) {
				if (empty($store_shop['create_time'])) $_POST['create_time'] = time();
				$_POST['last_time'] = time();
				$operat_shop = $database_merchant_store_foodshop->data($_POST)->save();
			} else {
				$_POST['create_time'] = time();
				$_POST['last_time'] = time();
				$operat_shop = $database_merchant_store_foodshop->add($_POST);
				if ($sysnc) {
					/*****************************数据同步导入****************************************/
					$where = array('store_id' => $store_id);
					$sorts = D('Meal_sort')->field(true)->where($where)->select();
					$root_path = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
					foreach ($sorts as $sort) {
						$old_sort_id = $sort['sort_id'];
						unset($sort['sort_id']);
						if ($sort_id = D('Foodshop_goods_sort')->add($sort)) {
							$meals = D('Meal')->field(true)->where(array('store_id' => $store_id, 'sort_id' => $old_sort_id))->select();
							foreach ($meals as $meal) {
								//移动图片
								if ($meal['image']) {
									$image_tmp = explode(',', $meal['image']);
									$dest_dir = $root_path.'/upload/foodshop_goods/'.$image_tmp[0];
									if (file_exists($root_path . '/upload/meal/' . $image_tmp[0] . '/' . $image_tmp[1])) {
										dmkdir($dest_dir . '/' . $image_tmp[1]);
										copy($root_path . '/upload/meal/' . $image_tmp[0] . '/' . $image_tmp[1], $dest_dir . '/' . $image_tmp[1]);
									}
									if (file_exists($root_path . '/upload/meal/' . $image_tmp[0] . '/m_' . $image_tmp[1])) {
										dmkdir($dest_dir . '/m_' . $image_tmp[1]);
										copy($root_path . '/upload/meal/' . $image_tmp[0] . '/m_' . $image_tmp[1], $dest_dir . '/m_' . $image_tmp[1]);
									}
									if (file_exists($root_path . '/upload/meal/' . $image_tmp[0] . '/s_' . $image_tmp[1])) {
										dmkdir($dest_dir . '/s_' . $image_tmp[1]);
										copy($root_path . '/upload/meal/' . $image_tmp[0] . '/s_' . $image_tmp[1], $dest_dir . '/s_' . $image_tmp[1]);
									}
			
								}
								unset($meal['meal_id'], $meal['label'], $meal['vip_price']);
								$meal['stock_num'] = $meal['stock_num'] == 0 ? -1 : $meal['stock_num'];
								$meal['sort_id'] = $sort_id;
								$meal['sell_day'] = 0;
								$meal['today_sell_count'] = 0;
								$meal['sell_mouth'] = 0;
								D('Foodshop_goods')->add($meal);
							}
						}
					}
					/*****************************数据同步导入****************************************/
				}
			}
			
			// 活动
			if (C('config.open_meal_activity')) {
				$activity_data['activity_name'] = $_POST['activity_name'] ? $_POST['activity_name'] : ''; 
				$activity_data['activity_price'] = $_POST['activity_price'] ? $_POST['activity_price'] : ''; 
				$activity_data['activity_description'] = $_POST['activity_description'] ? $_POST['activity_description'] : ''; 
				$activity_data['activity_label'] = serialize(array_filter($_POST['activity_label']));

				foreach($_POST['activity_image'] as $kp => $vp){
				    $tmp_vp = explode(',', $vp);
				    $_POST['activity_image'][$kp] = $rand_num . ',' . $tmp_vp[1];
				} 
				$activity_data['activity_image'] =$_POST['activity_image']? implode(';', $_POST['activity_image']) : ''; 

				$activity = M('Foodshop_activity')->where(['store_id'=>$_POST['store_id']])->find();
				if ($activity) {
					M('Foodshop_activity')->where(['store_id'=>$_POST['store_id']])->save($activity_data);
				}else{
					$activity_data['create_time'] = time();
					$activity_data['store_id'] = $now_store['store_id'];
					$activity_data['mer_id'] = $now_store['mer_id'];
					M('Foodshop_activity')->add($activity_data);
				}
			}

			$database_shop_category_relation = D('Meal_store_category_relation');
			$condition_shop_category_relation['store_id'] = $now_store['store_id'];
			$database_shop_category_relation->where($condition_shop_category_relation)->delete();
			foreach($cat_ids as $key => $value){
				$data_shop_category_relation[$key]['cat_id'] = $value['cat_id'];
				$data_shop_category_relation[$key]['cat_fid'] = $value['cat_fid'];
				$data_shop_category_relation[$key]['store_id'] = $now_store['store_id'];
			}
			$database_shop_category_relation->addAll($data_shop_category_relation);
			
			$this->success(L_('编辑成功！'));
		} else {
			$database_merchant_store_foodshop = D('Merchant_store_foodshop');
			$condition_merchant_store_shop['store_id'] = $now_store['store_id'];
			$store_shop = $database_merchant_store_foodshop->field(true)->where($condition_merchant_store_shop)->find();

			if(!empty($store_shop['pic'])){
			    $goods_image_class = new foodshopstore_image();
			    $tmp_pic_arr = explode(';', $store_shop['pic']);
			    foreach ($tmp_pic_arr as $key => $value) {
			        $store_shop['pic_arr'][$key]['title'] = $value;
			        $store_shop['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
			    }
			}
			
			if(!empty($store_shop['background'])){
			    $goods_image_class = new foodshopstore_image();
			    $tmp_pic_arr = explode(';', $store_shop['background']);
			    foreach ($tmp_pic_arr as $key => $value) {
			        $store_shop['background_arr'][$key]['title'] = $value;
			        $store_shop['background_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
			    }
			}
			//所有分类
			$database_shop_category = D('Meal_store_category');
			$category_list = $database_shop_category->lists();//field(true)->where(array('cat_status' => 1))->order('`cat_sort` DESC,`cat_id` ASC')->select();
			$this->assign('category_list', $category_list);
			
			//此店铺有的分类
			$database_shop_category_relation = D('Meal_store_category_relation');
			$condition_shop_category_relation['store_id'] = $now_store['store_id'];
			$relation_list = $database_shop_category_relation->field(true)->where($condition_shop_category_relation)->select();
			$relation_array = array();
			foreach ($relation_list as $key => $value) {
				array_push($relation_array, $value['cat_id']);
			}

			// 店铺活动
			$activity = M('Foodshop_activity')->where(['store_id'=>$now_store['store_id']])->find();
			$activity['label_list'] = unserialize($activity['activity_label']);
			if(!empty($activity['activity_image'])){
			    $goods_image_class = new foodshopstore_image();
			    $tmp_pic_arr = explode(';', $activity['activity_image']);
			    foreach ($tmp_pic_arr as $key => $value) {
			        $activity['pic_arr'][$key]['title'] = $value;
			        $activity['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
			    }
			}
			$this->assign('activity', $activity);

            $condition['store_id'] = $now_store['store_id'];
			$subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
			$subject_info['banner'] = unserialize($subject_info['banner']);
			$this->assign('subject_info',$subject_info);
			$this->assign('relation_array', $relation_array);
			$this->assign('store_shop', $store_shop);
			$this->assign('now_store', $now_store);
			$this->display();
		}
	}
	
	/**
	 * 商品分类
	 */
	public function goods_sort()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		
		$database_goods_sort = D('Foodshop_goods_sort');
		$condition_goods_sort['store_id'] = $now_store['store_id'];
		$count_sort = $database_goods_sort->where($condition_goods_sort)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count_sort, 20);
		$sort_list = $database_goods_sort->field(true)->where($condition_goods_sort)->order('`sort` DESC,`sort_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();

		$where=array('store_id' => $store_id);
        $g_list = M('Foodshop_goods')->field('count(*) as total,sort_id')->where($where)->group('sort_id')->select();
        $goods_count_arr = array_column($g_list, 'total' , 'sort_id');

		$sort_image_class = new foodshop_goods_sort_image();
		foreach ($sort_list as $key => $value) {
			if (!empty($value['week'])) {
				$week_arr = explode(',', $value['week']);
				$week_str = '';
				foreach ($week_arr as $k => $v) {
					$week_str .= $this->get_week($v) . ' ';
				}
				$sort_list[$key]['week_str'] = $week_str;
			}
			$sort_list[$key]['goods_count'] = intval($goods_count_arr[$value['sort_id']]);
			$sort_list[$key]['see_image'] = $sort_image_class->get_image_by_path($value['image'], $this->config['site_url'], 's');
		}
		$this->assign('sort_list', $sort_list);
		$this->assign('pagebar', $p->show());
		$this->display();
	}
	
	
	protected function get_week($num)
	{
		switch($num){
			case 1:
				return L_('星期一');
			case 2:
				return L_('星期二');
			case 3:
				return L_('星期三');
			case 4:
				return L_('星期四');
			case 5:
				return L_('星期五');
			case 6:
				return L_('星期六');
			case 0:
				return L_('星期日');
			default:
				return '';
		}
	}
	
	/**
	 * 添加商品分类
	 */
	public function sort_add()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		
		// 打印机
		$print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($print_list as $key=>$l) {
			if ($l['is_main']) {
				if (C('config.open_foodshop_print_type')&&$l['is_print_menu']==0) { // 不打印后厨
					unset($print_list[$key]);
					continue;
				}
				$print_list[$key]['name'] .= '(主打印机)'; 
			} else {
				$print_list[$key]['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
			}
		}
		$this->assign('print_list', $print_list);
		
		if (IS_POST) {
			if (empty($_POST['sort_name'])) {
				$error_tips = L_('分类名称必填！').'<br/>';
			} else {
				$database_goods_sort = D('Foodshop_goods_sort');
				$data_goods_sort['store_id'] = $now_store['store_id'];
				$data_goods_sort['sort_name'] = $_POST['sort_name'];
				$data_goods_sort['show_start_time'] = $_POST['show_start_time'];
				$data_goods_sort['show_end_time'] = $_POST['show_end_time'];
				$data_goods_sort['sort'] = intval($_POST['sort']);
				$data_goods_sort['sort_discount'] = intval($_POST['sort_discount']);
				// $data_goods_sort['status'] = intval($_POST['status']);
				$data_goods_sort['status'] = 1;
				$data_goods_sort['is_open_print'] = intval($_POST['is_open_print']);
				// $data_goods_sort['print_id'] = intval($_POST['print_id']);
				$data_goods_sort['print_id'] = isset($_POST['print_id'])&&is_array($_POST['print_id']) ? implode(',', $_POST['print_id']) : intval($_POST['print_id']);

				if ($_POST['week']) {
					$data_goods_sort['week'] = strval(implode(',', $_POST['week']));
				}
				if ($_FILES['image']['error'] != 4) {
					$param = array('size' => $this->config['meal_pic_size']);
					$param['thumb'] = true;
					$param['imageClassPath'] = 'ORG.Util.Image';
					$param['thumbPrefix'] = 'm_,s_';
					$param['thumbMaxWidth'] = $this->config['meal_pic_width'];
					$param['thumbMaxHeight'] = $this->config['meal_pic_height'];
					$param['thumbRemoveOrigin'] = false;
					
					$image = D('Image')->handle($this->merchant_session['mer_id'], 'foodshop_goods_sort', 1, $param);
					              
					if ($image['error']) {
						$error_tips .= $image['msg'] . '<br/>';
					} else {
						$_POST = array_merge($_POST, $image['title']);
					}
				}
				if (!empty($_POST['image_select'])) {
					$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
					$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
								
					$tmp_img = explode(',',$_POST['image_select']);
					$_POST['image'] = $rand_num.','.$tmp_img[1];
				}
// 				$data_goods_sort['image'] = $_POST['image'];
				if ($database_goods_sort->data($data_goods_sort)->add()) {
					$this->success(L_('添加成功！！'), U('Foodshop/goods_sort',array('store_id' => $now_store['store_id'])));
					die;
					$ok_tips = L_('添加成功！！');
				} else {
					$this->error(L_('添加失败！！请重试。'), U('Foodshop/goods_sort',array('store_id' => $now_store['store_id'])));
					die;
					$error_tips = L_('添加失败！！请重试。');
				}
			}
			if (!empty($error_tips)) {
				$this->assign('now_sort', $_POST);
			}
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		$this->display();
	}
	
	
	/**
	 * 修改商品分类
	 */
	public function sort_edit()
	{
		$now_sort = $this->check_sort(intval($_GET['sort_id']));
		$now_store = $this->check_store($now_sort['store_id']);
		 // 是否启用餐饮打印模式 新
		if (C('config.open_foodshop_print_type')) {
			$now_sort['print_id'] = explode(',', $now_sort['print_id']);
		}
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);

		// 打印机
		$print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($print_list as $key=>$l) {
			if ($l['is_main']) {
				if (C('config.open_foodshop_print_type')&&$l['is_print_menu']==0) { // 不打印后厨
					unset($print_list[$key]);
					continue;
				}
				$print_list[$key]['name'] .= '(主打印机)'; 
			} else {
				$print_list[$key]['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
			}
		}
		$this->assign('print_list', $print_list);

		if (IS_POST) {
			if (empty($_POST['sort_name'])) {
				$error_tips = L_('分类名称必填！').'<br/>';
			} else {
				$database_goods_sort = D('Foodshop_goods_sort');
				$data_goods_sort['sort_id'] = $now_sort['sort_id'];
				$data_goods_sort['sort_name'] = $_POST['sort_name'];
				$data_goods_sort['show_start_time'] = $_POST['show_start_time'];
				$data_goods_sort['show_end_time'] = $_POST['show_end_time'];
				$data_goods_sort['sort'] = intval($_POST['sort']);
				$data_goods_sort['sort_discount'] = intval($_POST['sort_discount']);
				// $data_goods_sort['status'] = intval($_POST['status']);
				$data_goods_sort['status'] = 1;
				$data_goods_sort['week'] = implode(',', $_POST['week']);
				$data_goods_sort['is_open_print'] = intval($_POST['is_open_print']);
				// $data_goods_sort['print_id'] = intval($_POST['print_id']);
				$data_goods_sort['print_id'] = isset($_POST['print_id'])&&is_array($_POST['print_id']) ? implode(',', $_POST['print_id']) : intval($_POST['print_id']);
				if($_FILES['image']['error'] != 4){
					$param = array('size' => $this->config['meal_pic_size']);
					$param['thumb'] = true;
					$param['imageClassPath'] = 'ORG.Util.Image';
					$param['thumbPrefix'] = 'm_,s_';
					$param['thumbMaxWidth'] = $this->config['meal_pic_width'];
					$param['thumbMaxHeight'] = $this->config['meal_pic_height'];
					$param['thumbRemoveOrigin'] = false;
					
					$image = D('Image')->handle($this->merchant_session['mer_id'], 'foodshop_goods_sort', 1, $param);
					              
					if ($image['error']) {
						$error_tips .= $image['msg'] . '<br/>';
					} else {
						$_POST = array_merge($_POST, $image['title']);
					}
				}
				if(!empty($_POST['image_select'])){
					$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
					$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
								
					$tmp_img = explode(',', $_POST['image_select']);
					$_POST['image'] = $rand_num . ',' . $tmp_img[1];
				}
// 				if ($_POST['image']) {
// 					$data_goods_sort['image'] = $_POST['image'];
// 				}
				if ($database_goods_sort->data($data_goods_sort)->save()) {
					$this->success(L_('保存成功！！'), U('Foodshop/goods_sort',array('store_id' => $now_store['store_id'])));
					die;
					$ok_tips = L_('保存成功！！');
				} else {
					$this->error(L_('保存失败！！您是不是没做过修改？请重试。'), U('Foodshop/goods_sort',array('store_id' => $now_store['store_id'])));
					die;
					$error_tips = L_('保存失败！！您是不是没做过修改？请重试。');
				}
			}
			$_POST['sort_id'] = $now_sort['sort_id'];
			$this->assign('now_sort', $_POST);
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		$this->display();
	}
	
	/* 删除分类 */
	public function sort_del()
	{
		$now_sort = $this->check_sort(intval($_GET['sort_id']));
		$now_store = $this->check_store($now_sort['store_id']);

		$count = D('Foodshop_goods')->where(array('sort_id' => $now_sort['sort_id'], 'store_id' => $now_sort['store_id']))->count();
		if ($count) $this->error(L_('该分类下有商品，先删除商品后再来删除该分类'));
		$database_goods_sort = D('Foodshop_goods_sort');
		$condition_goods_sort['sort_id'] = $now_sort['sort_id'];
		if ($database_goods_sort->where($condition_goods_sort)->delete()) {
			$this->success(L_('删除成功！'));
		} else {
			$this->error(L_('删除失败！'));
		}
	}
	
	/* 菜品管理 */
	public function goods_list()
	{
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);


		// 商品分类
		$sort_list = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $now_store['store_id']))->select();

		$condition_goods['store_id'] = $now_store['store_id'];
		$condition_goods_all['store_id'] = $now_store['store_id'];
		if (isset($_GET['status']) || isset($_GET['stock_num'])) {
			if (isset($_GET['status'])) {
				$condition_goods['status'] = $_GET['status'];
				$condition_goods_all['status'] = $_GET['status'];
			}

			if (isset($_GET['stock_num'])) {
				$condition_goods['stock_num'] = $_GET['stock_num'];
				$condition_goods_all['stock_num'] = $_GET['stock_num'];
			}

			// 条件查询去掉没有商品的分类
			$goods_list_all = M('Foodshop_goods')->field('goods_id,sort_id')->where($condition_goods_all)->group('sort_id')->select();
			$goods_list_all = array_column($goods_list_all, 'goods_id','sort_id');
			$result = array();
			foreach ($sort_list as $l) {
	            if (!isset($goods_list_all[$l['sort_id']])) {
	                $l = null;
	            }
	            if (!empty($l)) {
	                $result[] = $l;
	            }
	        }
			$sort_list = $result;
		}

		$this->assign('sort_list', $sort_list);

		if (!$_GET['sort_id']) {
			$_GET['sort_id'] = $sort_list[0]['sort_id'];
		}

		if ($_GET['sort_id']) {
			$now_sort = $this->check_sort($_GET['sort_id']);
		}
		$this->assign('now_sort', $now_sort);
		
		$database_goods = D('Foodshop_goods');
		$condition_goods['sort_id'] = $now_sort['sort_id'];
		$count_goods = $database_goods->where($condition_goods)->count();
		$this->assign('count_goods', $count_goods);


		// 商品统计
		$where_count['store_id'] = $now_store['store_id'];
		// 全部商品
		$all_goods_total = M('Foodshop_goods')->where($where_count)->count();
		$this->assign('all_goods_total', $all_goods_total);

		// 售卖中
		$where_count['status'] = 1;
		$sell_goods_total = M('Foodshop_goods')->where($where_count)->count();
		$this->assign('sell_goods_total', $sell_goods_total);

		// 已下架
		$where_count['status'] = 0;
		$undercarriage_goods_total = M('Foodshop_goods')->where($where_count)->count();
		$this->assign('undercarriage_goods_total', $undercarriage_goods_total);

		// 已售完
		unset($where_count['status']);
		$where_count['stock_num'] = 0;
		$sellout_goods_total = M('Foodshop_goods')->where($where_count)->count();
		$this->assign('sellout_goods_total', $sellout_goods_total);

		$this->display();
	}

	/* 菜品管理 */
	public function ajax_goods_list()
	{
		if ($_GET['sort_id']) {
			$now_sort = $this->check_sort($_GET['sort_id']);
		}else{
			exit(json_encode(array('goods_list'=>array())));
		}

		if (isset($_GET['status']) || isset($_GET['stock_num'])) {
			if (isset($_GET['status'])) {
				$condition_goods['status'] = $_GET['status'];
			}

			if (isset($_GET['stock_num'])) {
				$condition_goods['stock_num'] = $_GET['stock_num'];
			}
		}

		$condition_goods['sort_id'] = $now_sort['sort_id'];
		$condition_foodshop_goods['s.sort_id'] = $now_sort['sort_id'];
		$database_goods = D('Foodshop_goods');
		$count_goods = $database_goods->where($condition_goods)->count();
        import('@.ORG.diypage');
		$p = new Page($count_goods, 30);

		$goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
		
		$today = date('Ymd');
		$now_time = time();
	
        $goods_image_class = new foodshop_goods_image();
		foreach ($goods_list as &$rl) {
			// 销量
			$sell_count = D('Foodshop_order_detail')->field('sum(num) AS num')->where(array('goods_id'=>$rl['goods_id']))->find();
			$rl['sell_count'] = intval($sell_count['num']);

			// 图片
			if ($rl['image']) {
				$tmp_pic_arr = explode(';', $rl['image']);
	            foreach ($tmp_pic_arr as $k => $v) {
	                $rl['pic_arr'][$k]['title'] = $v;
	                $rl['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
	                $k==0 && $rl['image_url'] = $rl['pic_arr'][$k]['url']['s_image'];
	            } 
			}else{
				if (C('config.is_open_goods_default_image')&&C('config.goods_default_image')) {
					$rl['image_url'] = C('config.goods_default_image');
				}else{
					$rl['image_url'] = C('config.site_url').'/tpl/Merchant/default/static/images/default_img.png';
				}
			}

			$rl['last_time'] = date('Y-m-d H:i:s',$rl['last_time']);
		}
		
		exit(json_encode(array('goods_list'=>$goods_list,'page'=>$p->show(),'is_publish_market_goods'=>$is_publish_market_goods,'is_fabu'=>$is_fabu)));
	}

		// 修改商品排序
	public function goods_sort_edit(){
		$goods_id = $_POST['id'];
		$sort = $_POST['sort'];
		$res = M('Foodshop_goods')->where(['goods_id'=>$goods_id])->save(['sort'=>$sort]);
		if ($res!==false) {
			$return = array('err_code' => 0, 'msg' => L_('已成功修改排序值'));
			$this->ajaxReturn($return,'JSON');
		}else{
			$return = array('err_code' => 1, 'msg' => L_('排序修改失败，请重试'));
			$this->ajaxReturn($return,'JSON');
		}
	}

	// 商品搜索
	public function goods_search(){
		$now_store = $this->check_store($_GET['store_id']);

		// 商品分类
		$goods_sorts = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('sort_list', $goods_sorts);
		$this->assign('now_store', $now_store);

		$database_goods = D('Foodshop_goods');
		$condition_goods['store_id'] = $_GET['store_id'];

		$search_type=I('get.search_type','','trim');
		if($_GET['keyword']){
			if($search_type=='number'){
				$condition_goods['_string']="number like '%".$_GET['keyword']."%' ";
			}else{
				$condition_goods[_field('name')] = array('like','%'.$_GET['keyword'].'%');
			}
		}
		
		$count_goods = $database_goods->where($condition_goods)->count();
		$this->assign("count_goods",$count_goods);
		
		$goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->select();
		
		$today = date('Ymd');
		$now_time = time();
		
        $goods_image_class = new foodshop_goods_image();
		foreach ($goods_list as &$rl) {
			// 销量
			$sell_count = D('Foodshop_order_detail')->field('sum(num) AS num')->where(array('goods_id'=>$rl['goods_id']))->find();
			$rl['sell_count'] = intval($sell_count['num']);

			// 图片
			if ($rl['image']) {
				$tmp_pic_arr = explode(';', $rl['image']);
	            foreach ($tmp_pic_arr as $k => $v) {
	                $rl['pic_arr'][$k]['title'] = $v;
	                $rl['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
	                $k==0 && $rl['image_url'] = $rl['pic_arr'][$k]['url']['s_image'];
	            } 
			}else{
				if (C('config.is_open_goods_default_image')&&C('config.goods_default_image')) {
					$rl['image_url'] = C('config.goods_default_image');
				}else{
					$rl['image_url'] = C('config.site_url').'/tpl/Merchant/default/static/images/default_img.png';
				}
			}
		}

		$newList = array();
        foreach ($goods_list as $r) {
            if (isset($newList[$r['sort_id']])) {
                $newList[$r['sort_id']]['product_list'][] = $r;
            } else {
                $newList[$r['sort_id']]['sort_name'] = D('Foodshop_goods_sort')->where(['sort_id'=>$r['sort_id']])->getField('sort_name');
                $newList[$r['sort_id']]['product_list'] = array($r);
            }
        }

		$this->assign('goods_list', $newList);
		$this->display();
	}

	//获取商品信息
	public function get_goods_info(){
		$goods_id = I('goods_id');
		if(empty($goods_id)){
			$return = array('status' => 2, 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}
		$now_goods = $this->check_goods($goods_id);
		if(empty($now_goods)){
			$return = array('status' => 2, 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}
		$return = array('status' => 1, 'data' => $now_goods);
		$this->ajaxReturn($return,'JSON');
	}

    //更新商品信息 批量
	// 1-沽清，2-置满，3-修改库存，4-上架，5-下架，6-删除，7-修改分类，8-修改售卖时间，9-修改商品描述，10-修改最小购买量和单位，11-设置打包费
	public function reset_goods_info_batch(){
		$goods_id = I('goods_id');

		$stock_num = I('stock_num');//等于full时表示要更改跟原始库存一样的值
		$original_stock = I('original_stock');
		$type = I('type'); //修改类型

		$database_goods = D('Foodshop_goods');

		if(empty($goods_id)||empty($type)){
			$return = array('status' => 2, 'msg' => L_('缺少参数'), 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}

		// 商品列表
		$goods_list = M('Foodshop_goods')->where(['goods_id'=>['in',$goods_id]])->select();
		if(empty($goods_list)){
			$return = array('status' => 2, 'msg' => L_('商品不存在'), 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}
		$store_id = $goods_list[0]['store_id'];
		$update = array();
		$fill_up_batch = array();
		$return_status = true;
		foreach ($goods_list as $key => $now_goods) {
			switch ($type) {
				case '1'://沽清
					$update['stock_num'] = 0;
					$update['sell_day'] = date('Ymd');
					break;
				case '2'://置满
					$fill_up_batch = [
						'goods_id' => $now_goods['goods_id'],
						'stock_num' => $now_goods['original_stock'],
						'sell_day' => date('Ymd'),
					];
					
					// 批量置满
					if($database_goods->save($fill_up_batch)===false){
						$return_status = false;
					}
					
					break;
				case '3'://修改库存
					if($original_stock === '' || $stock_num === ''){
						$return = array('status' => 2, 'msg' => L_('商品库存必填'), 'data' => array());
						$this->ajaxReturn($return,'JSON');
					}
					$update['stock_num'] = $stock_num;
					$update['original_stock'] = $original_stock;
					$update['sell_day'] = date('Ymd');

					if(($original_stock != -1 && $stock_num > $original_stock) || ($stock_num == -1 && $original_stock != -1)){
						$return = array('status' => 2, 'msg' => L_('当前库存不能大于原始库存！'), 'data' => array());
						$this->ajaxReturn($return,'JSON');
					}
					break;
				case '4':
					$update['status'] = 1;
					break;
				case '5':
					$update['status'] = 0;
					break;
				case '6':
					break;
				case '7':
					$sort_id = $_POST['sort_id'];
					$foodshopGoodsSortDB = M('Foodshop_goods_sort');
					if (!$sort = $foodshopGoodsSortDB->field(true)->where(array('sort_id' => $sort_id, 'store_id' => $store_id))->find()) {
						$return = array('status' => 2, 'msg' => L_('商品分类不存在'), 'data' => array());
						$this->ajaxReturn($return,'JSON');
					}
					$update['sort_id'] = $sort_id;

					break;
				case '8':
					$update['unit'] = $_POST['unit'];
					if (!$_POST['unit']) {
						$return = array('status' => 2, 'msg' => L_('商品单位不能为空'), 'data' => array());
						$this->ajaxReturn($return,'JSON');
					}
					break;
			}
		}
		if ($type==6) {
			// 删除
			if($database_goods->where(['goods_id'=>['in',$goods_id]])->delete()!==false){
				//规格表
				$old_spec = M('Foodshop_goods_spec')->field(true)->where(array('goods_id' => ['in',$goods_id], 'store_id' => $store_id))->select();
				foreach ($old_spec as $os) {
					$delete_spec_ids[] = $os['id'];
				}
				// 删除规格
				M('Foodshop_goods_spec')->where(array('goods_id' => ['in',$goods_id], 'store_id' => $store_id))->delete();
				if ($delete_spec_ids) {
					$old_spec_val = M('Foodshop_goods_spec_value')->where(array('sid' => array('in', $delete_spec_ids)))->delete();
				}

				// 删除属性
				M('Foodshop_goods_properties')->where(array('goods_id' => ['in',$goods_id]))->delete();

				$return = array('status' => 1, 'msg' => L_('删除成功'), 'data' => array('update' => $update['stock_num']));
				$this->ajaxReturn($return,'JSON');
			}
			else{
				$return = array('status' => 2, 'msg' => L_('删除失败'), 'data' => array());
				$this->ajaxReturn($return,'JSON');
			}
		}else{
			if($update){
				if($database_goods->where(['goods_id'=>['in',$goods_id]])->data($update)->save()!==false){
				
					$return = array('status' => 1, 'msg' => L_('更新成功'), 'data' => array('update' => $update['stock_num']));
					$this->ajaxReturn($return,'JSON');
				}
				else{
					$return = array('status' => 2, 'msg' => L_('更新失败'), 'data' => array());
					$this->ajaxReturn($return,'JSON');
				}
			}elseif ($return_status) {
				if (count($goods_list)==1) {
					$return_data = array('update' => $goods_list[0]['original_stock']);
				}
				$return = array('status' => 1, 'msg' => L_('更新成功'), 'data' => $return_data);
				$this->ajaxReturn($return,'JSON');
			}else{
				$return = array('status' => 2, 'msg' => L_('更新失败'), 'data' => array());
				$this->ajaxReturn($return,'JSON');
			}
		}
	}

	/* 添加店铺 */
	public function goods_add()
	{
		$now_store = $this->check_store($_GET['store_id']);
		if ($_GET['sort_id']) {
			$now_sort = $this->check_sort($_GET['sort_id']);
		}

		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
			
		$merchant = D('Merchant')->field('is_discount, discount_percent, discount_order_num')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
		$this->assign('merchant', $merchant);
		if (IS_POST) {
			if (empty($_POST['name'])) {
				$error_tips .= L_('商品名称必填！').'<br/>';
			}
			if (empty($_POST['unit'])) {
				$error_tips .= L_('商品单位必填！').'<br/>';
			}
			if (empty($_POST['price'])) {
				$error_tips .= L_('商品价格必填！').'<br/>';
			}
            if (empty($_POST['pic'])) {
            	$error_tips .= L_('请至少上传一张照片！').'<br/>';
            }
			
            $_POST['des'] = fulltext_filter($_POST['des']);
            
			$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			foreach ($_POST['pic'] as $kp => $vp) {
				$tmp_vp = explode(',', $vp);
				if (!strstr($tmp_vp[0], '/upload/')) $rand_num = $tmp_vp[0];
				$_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
				if (!$tmp_vp[1]) {
					$_POST['pic'][$kp] = $vp;
				}
			}
            $_POST['pic'] = implode(';', $_POST['pic']);
			$_POST['print_id'] = isset($_POST['print_id'])&&is_array($_POST['print_id']) ? implode(',', $_POST['print_id']) : intval($_POST['print_id']);
			
			
			if ($_POST['specs']) {
				foreach ($_POST['specs'] as $val) {
					if (empty($val)) {
						$error_tips .= L_('请给规格取名，若不需要的请删除后重新生成').'<br/>';
					}
				}
			}
			
			if ($_POST['spec_val']) {
				foreach ($_POST['spec_val'] as $rowset) {
					foreach ($rowset as $val) {
						if (empty($val)) {
							$error_tips .= L_('请给规格的属性值取名，若不需要的请删除后重新生成').'<br/>';
						}
					}
				}
			}
			
			
			if ($_POST['properties']) {
				foreach ($_POST['properties'] as $val) {
					if (empty($val)) {
						$error_tips .= L_('请给属性取名，若不需要的请删除后重新生成').'<br/>';
					}
				}
			}
			
			if ($_POST['properties_val']) {
				foreach ($_POST['properties_val'] as $rowset) {
					foreach ($rowset as $val) {
						if (empty($val)) {
							$error_tips .= L_('请给属性的属性值取名，若不需要的请删除后重新生成').'<br/>';
						}
					}
				}
			}
			
			if (isset($_POST['prices']) && $_POST['prices']) {
				foreach ($_POST['prices'] as $rowset) {
					foreach ($rowset as $val) {
						if (empty($val)) {
							$error_tips .= L_('所有的现价必须要填写').'<br/>';
						}
					}
				}
			}

			if (!M('Foodshop_goods_sort')->where(['sort_id'=>intval($_POST['sort_id'])])->find()) {
            	$error_tips .= L_('分类不存在！').'<br/>';
			}

			$_POST['seckill_open_time'] = strtotime($_POST['seckill_open_time'] . ":00");
			$_POST['seckill_close_time'] = strtotime($_POST['seckill_close_time'] . ":00");
			$_POST['label'] = isset($_POST['label']) && $_POST['label'] ? htmlspecialchars($_POST['label']) : '';
			$_POST['show_type'] = isset($_POST['show_type']) ? intval($_POST['show_type']) : 0;
			$_POST['is_discount'] = isset($_POST['is_discount']) ? intval($_POST['is_discount']) : 0;
			$_POST['is_table_discount'] = isset($_POST['is_table_discount']) ? intval($_POST['is_table_discount']) : 0;
			if (empty($error_tips)) {
				$_POST['sort_id'] = intval($_POST['sort_id']);
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
				
				if ($goods_id = D('Foodshop_goods')->save_post_form($_POST, $now_store['store_id'])) {
					D('Image')->update_table_id($_POST['image'], $goods_id, 'foodshop_goods');
					$this->success(L_('添加成功！'), U('Foodshop/goods_list', array('sort_id' => $now_sort['sort_id'],'store_id'=>$_GET['store_id'])));
					die;
					$ok_tips = L_('添加成功！');
				} else {
					$this->error(L_('添加失败！请重试！'), U('Foodshop/goods_list', array('sort_id' => $now_sort['sort_id'],'store_id'=>$_GET['store_id'])));
					die;
					$error_tips = L_('添加失败！请重试。');
				}
			} else {
				$return = $this->format_data($_POST);
				$_POST['json'] = isset($return['json']) ? json_encode($return['json']) : '';
				$_POST['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
				$_POST['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
				$_POST['list'] = isset($return['list']) ? $return['list'] : '';
				
				$this->assign('now_goods', $_POST);
			}
			
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		} else {

			$this->assign('now_goods', array('seckill_open_time' => strtotime(date('Y-m-d') . ' 08:00:00'), 'seckill_close_time' => strtotime(date('Y-m-d') . ' 10:00:00')));
		}
		$print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($print_list as $key=>$l) {
			if ($l['is_main']) {
				if (C('config.open_foodshop_print_type')&&$l['is_print_menu']==0) { // 不打印后厨
					unset($print_list[$key]);
					continue;
				}
				$print_list[$key]['name'] .= '(主打印机)'; 
			} else {
				$print_list[$key]['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
			}
		}
		// 商品分类
		$sort_list = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('sort_list', $sort_list);
		$this->assign('print_list', $print_list);
		$labels = D('Store_label')->field(true)->select();
		$this->assign('labels', $labels);
		$this->display();
	}


	
    public function ajax_upload_pic()
    {
        if ($_FILES['file']['error'] != 4) {
        	$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
//         	$shop = D('Merchant_store_foodshop')->field('store_theme')->where(array('store_id' => $store_id))->find();
//         	$store_theme = isset($shop['store_theme']) ? intval($shop['store_theme']) : 0;
//         	if ($store_theme) {
//         		$width = '900,450';
//         		$height = '900,450';
//         	} else {
        		$width = '900,450';
        		$height = '500,250';
//         	}
			$param = array('size' => $this->config['group_pic_size']);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
            $param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = $width;
            $param['thumbMaxHeight'] = $height;
            $param['thumbRemoveOrigin'] = false;
			$image = D('Image')->handle($this->merchant_session['mer_id'], 'foodshop_goods', 1, $param);
			if ($image['error']) {
				exit(json_encode(array('error' => 1,'info' =>$image['msg'] ? $image['msg'] : $image['message'])));
			} else {
				$title = $image['title']['file'];
				$goods_image_class = new foodshop_goods_image();
				$url = $goods_image_class->get_image_by_path($title, 's');
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			}
		}else{
			exit(json_encode(array('error' => 1,'info' =>L_('没有选择图片'))));
		}
	}
	
	/* 编辑商品 */
	public function goods_edit()
	{
		$now_goods = $this->check_goods(intval($_GET['goods_id']));
		$now_sort = $this->check_sort($now_goods['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);

		 // 是否启用餐饮打印模式 新
		if (C('config.open_foodshop_print_type')) {
			$now_goods['print_id'] = explode(',', $now_goods['print_id']);
		}
		$this->assign('now_goods', $now_goods);
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
		
		$merchant = D('Merchant')->field('is_discount, discount_percent, discount_order_num')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
		$this->assign('merchant', $merchant);
		if(IS_POST){
			if (empty($_POST['name'])) {
				$error_tips .= L_('商品名称必填！').'<br/>';
			}
			if (empty($_POST['unit'])) {
				$error_tips .= L_('商品单位必填！').'<br/>';
			}
			if (empty($_POST['price'])) {
				$error_tips .= L_('商品价格必填！').'<br/>';
			}
            if (empty($_POST['pic'])) {
            	$error_tips .= L_('请至少上传一张照片！').'<br/>';
            }
			if (!M('Foodshop_goods_sort')->where(['sort_id'=>intval($_POST['sort_id'])])->find()) {
            	$error_tips .= L_('分类不存在！').'<br/>';
			}
			
            $_POST['des'] = fulltext_filter($_POST['des']);
            
			$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			foreach($_POST['pic'] as $kp => $vp){
				$tmp_vp = explode(',', $vp);
				if (!strstr($tmp_vp[0], '/upload/')) $rand_num = $tmp_vp[0];
				$_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
				if (!$tmp_vp[1]) {
					$_POST['pic'][$kp] = $vp;
				}
			}
            $_POST['pic'] = implode(';', $_POST['pic']);
			$_POST['print_id'] = isset($_POST['print_id'])&&is_array($_POST['print_id']) ? implode(',', $_POST['print_id']) : intval($_POST['print_id']);
			
			
			if ($_POST['specs']) {
				foreach ($_POST['specs'] as $val) {
					if (empty($val)) {
						$error_tips .= L_('请给规格取名，若不需要的请删除后重新生成').'<br/>';
					}
				}
			}
			
			if ($_POST['spec_val']) {
				foreach ($_POST['spec_val'] as $rowset) {
					foreach ($rowset as $val) {
						if (empty($val)) {
							$error_tips .= L_('请给规格的属性值取名，若不需要的请删除后重新生成').'<br/>';
						}
					}
				}
			}
			
			
			if ($_POST['properties']) {
				foreach ($_POST['properties'] as $val) {
					if (empty($val)) {
						$error_tips .= L_('请给属性取名，若不需要的请删除后重新生成').'<br/>';
					}
				}
			}
			
			if ($_POST['properties_val']) {
				foreach ($_POST['properties_val'] as $rowset) {
					foreach ($rowset as $val) {
						if (empty($val)) {
							$error_tips .= L_('请给属性的属性值取名，若不需要的请删除后重新生成').'<br/>';
						}
					}
				}
			}
			$_POST['seckill_open_time'] = strtotime($_POST['seckill_open_time'] . ":00");
			$_POST['seckill_close_time'] = strtotime($_POST['seckill_close_time'] . ":00");
			$_POST['label'] = isset($_POST['label']) && $_POST['label'] ? htmlspecialchars($_POST['label']) : '';
			$_POST['show_type'] = isset($_POST['show_type']) ? intval($_POST['show_type']) : 0;
			$_POST['is_discount'] = isset($_POST['is_discount']) ? intval($_POST['is_discount']) : 0;
            $_POST['is_table_discount'] = isset($_POST['is_table_discount']) ? intval($_POST['is_table_discount']) : 0;
			if (empty($error_tips)) {
				$_POST['goods_id'] = $now_goods['goods_id'];
				$_POST['sort_id'] = $_POST['sort_id'] ? $_POST['sort_id']:$now_sort['sort_id'];
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
				
				if ($goods_id = D('Foodshop_goods')->save_post_form($_POST, $now_store['store_id'])) {
					D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');
					$this->success(L_('保存成功！'), U('Foodshop/goods_list', array('sort_id' => $now_sort['sort_id'],'store_id'=>$now_sort['store_id'])));
					die;
					$ok_tips = L_('保存成功！');
				} else {
					$this->error(L_('保存失败！请重试！'), U('Foodshop/goods_list', array('sort_id' => $now_sort['sort_id'],'store_id'=>$now_sort['store_id'])));
					die;
					$error_tips = L_('保存失败！请重试。');
				}
			} else {
				$return = $this->format_data($_POST);
				$_POST['json'] = isset($return['json']) ? json_encode($return['json']) : '';
				$_POST['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
				$_POST['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
				$_POST['list'] = isset($return['list']) ? $return['list'] : '';
				$this->assign('now_goods', $_POST);
			}
			
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		
		// 商品分类
		$sort_list = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('sort_list', $sort_list);
		
		$print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($print_list as $key=>$l) {
			if ($l['is_main']) {
				if (C('config.open_foodshop_print_type')&&$l['is_print_menu']==0) { // 不打印后厨
					unset($print_list[$key]);
					continue;
				}
				$print_list[$key]['name'] .= '(主打印机)'; 
			} else {
				$print_list[$key]['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
			}
		}
		$this->assign('print_list', $print_list);
		
		$labels = D('Store_label')->field(true)->select();
		$this->assign('labels', $labels);

		// 商品分类
		$goods_sorts = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('goods_sorts', $goods_sorts);
		
		// 商品分类
		$goods_sorts = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('goods_sorts', $goods_sorts);
		
		$this->display();
	}
	
	
	/* 商品删除 */
	public function goods_del()
	{
		$now_goods = $this->check_goods(intval($_GET['goods_id']));
		$now_sort = $this->check_sort($now_goods['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);
		
		$database_goods = D('Foodshop_goods');
		$condition_goods['goods_id'] = $now_goods['goods_id'];
		if ($database_goods->where($condition_goods)->delete()) {
			$spec_obj = M('Foodshop_goods_spec'); //规格表
			$old_spec = $spec_obj->field(true)->where(array('goods_id' => $now_goods['goods_id'], 'store_id' => $now_sort['store_id']))->select();
			foreach ($old_spec as $os) {
				$delete_spec_ids[] = $os['id'];
			}
			$spec_obj->where(array('goods_id' => $now_goods['goods_id'], 'store_id' => $now_sort['store_id']))->delete();
			if ($delete_spec_ids) {
				$old_spec_val = M('Foodshop_goods_spec_value')->where(array('sid' => array('in', $delete_spec_ids)))->delete();
			}
			M('Foodshop_goods_properties')->where(array('goods_id' => $now_goods['goods_id']))->delete();
			$this->success(L_('删除成功！'));
		}else{
			$this->error(L_('删除失败！请检查后重试。'));
		}
	}
	/* 商品状态 */
	public function goods_status()
	{
		$now_goods = $this->check_goods(intval($_POST['id']));
		$now_sort = $this->check_sort($now_goods['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);
		
		$database_goods = D('Foodshop_goods');
		$condition_goods['goods_id'] = $now_goods['goods_id'];
		$data_goods['status'] = $_POST['type'] == 'open' ? '1' : '0';
		if($database_goods->where($condition_goods)->data($data_goods)->save()){
			exit('1');
		}else{
			exit;
		}
	}
	
	/* 检测店铺存在，并检测是不是归属于商家 */
	protected function check_store($store_id)
	{
	    if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $store_id) {
	        $this->error(L_('您没有这个权限'));
	    }
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['store_id'] = $store_id;
		$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
		$now_store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
		if (empty($now_store)) {
			$this->error(L_('店铺不存在！'));
		} else {
			//return $now_store;
			if ($now_shop = D('Merchant_store_foodshop')->field(true)->where($condition_merchant_store)->find()) {
				return array_merge($now_store, $now_shop);
			}
			return $now_store;
			$now_shop = D('Merchant_store_shop')->field(true)->where($condition_merchant_store)->find();
			return array_merge($now_store, $now_shop);
		}
	}
	/* 检测分类存在 */
	protected function check_sort($sort_id)
	{
		$database_goods_sort = D('Foodshop_goods_sort');
		$condition_goods_sort['sort_id'] = $sort_id;
		$now_sort = $database_goods_sort->field(true)->where($condition_goods_sort)->find();
		if (empty($now_sort)) {
			$this->error(L_('分类不存在！'));
		}
		if(!empty($now_sort['image'])){
			$sort_image_class = new goods_sort_image();
			$now_sort['see_image'] = $sort_image_class->get_image_by_path($now_sort['image'],$this->config['site_url'],'s');
		}
		if (!empty($now_sort['week'])) {
			$now_sort['week'] = explode(',', $now_sort['week']);
		}
		return $now_sort;
	}
	/* 检测商品存在 */
	protected function check_goods($goods_id)
	{
		$database_shop_goods = D('Foodshop_goods');
		$condition_goods['goods_id'] = $goods_id;
		$now_goods = $database_shop_goods->field(true)->where($condition_goods)->find();
		if(empty($now_goods)){
			$this->error(L_('商品不存在！'));
		}
		if(!empty($now_goods['image'])){
            $goods_image_class = new foodshop_goods_image();
            $tmp_pic_arr = explode(';', $now_goods['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_goods['pic_arr'][$key]['title'] = $value;
                $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
            }
		}
		$return = $database_shop_goods->format_spec_value($now_goods);
		$now_goods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
		$now_goods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
		$now_goods['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
		$now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
		$now_goods['list'] = isset($return['list']) ? $return['list'] : '';

		return $now_goods;
	}
	
	private function format_spec($a, $i, $str, &$return)
	{
		if ($i == 0) {
			$ii = $i + 1;
			foreach ($a[$i] as $val) {
				$t = $str ? $str . '_' : '';
				if ($ii == count($a)) {
					$return[] = $t . $val;
				} else {
					$this->format_spec($a, $ii, $t . $val, $return);
				}
			}
		} else if ($i == count($a) - 1) {
			foreach ($a[$i] as $val) {
				$t = $str ? $str . '_' : '';
				$return[] = $t . $val;
			}
		} else {
			$ii = $i + 1;
			foreach ($a[$i] as $val) {
				$t = $str ? $str . '_' : '';
				$this->format_spec($a, $ii, $t . $val, $return);
			}
		}
	}
	
	private function format_data($data)
	{
		$spec_list = array();
		foreach ($data['spec_id'] as $i => $id) {
			$id = intval($id);
			$t_i = $id ? $id : 'i_' . $i;
			$spec_list[$t_i] = array('id' => $id, 'name' => $data['specs'][$i]);
			
			foreach ($data['spec_val_id'][$i] as $ii => $vid) {
				$vid = intval($vid);
				$v_i = $vid ? $vid : 'v_' . $ii;
				$spec_list[$t_i]['list'][$v_i] = array('id' => $vid,  'name' => $data['spec_val'][$i][$ii]);
			}
		}
		
		$properties_list = array();
		foreach ($data['properties_id'] as $pi => $pid) {
			$pid = intval($pid);
			$p_i = $pid ? $pid : 'p_' . $pi;
			$properties_list[$p_i] = array('id' => $pid, 'name' => $data['properties'][$pi], 'val' => $data['properties_val'][$pi]);
		}
		
		
		$for_data = array();
		foreach ($data['spec_val_id'] as $di => $dr) {
			foreach ($dr as $d => $id_t) {
				$for_data[$di][$d] = $di . '_' . $d;
			}
		}
		
		$formart_data = array();
		$this->format_spec($for_data, 0, '', $formart_data);
		$list = array();
		foreach ($formart_data as $fi => $string) {
			$array = explode('_', $string);
			$array = array_chunk($array, 2);
			$index = $pre = '';
			$tdata = array();
			foreach ($array as $irow) {
				$k = $irow[0];
				$ki = $irow[1];
				$r = $data['spec_val_id'][$irow[0]][$irow[1]];
				if ($r) {
					$index .= $pre . 'id_' . $r;
				} else {
					$index .= $pre . 'index_' . $ki;
				}
				$pre = '_';
				$tdata[] = array('spec_val_id' => $r, 'spec_val_name' => $data['spec_val'][$k][$ki]);
			}
			$list[$index]['index'] = $index;
			$list[$index]['spec'] = $tdata;
			$list[$index]['old_price'] = $data['old_prices'][$fi];
			$list[$index]['price'] = $data['prices'][$fi];
			$list[$index]['seckill_price'] = $data['seckill_prices'][$fi];
			$list[$index]['stock_num'] = $data['stock_nums'][$fi];
			$list[$index]['number'] = $data['numbers'][$fi];
			$pt_data = array();
			foreach ($data['properties'] as $pin => $pr) {
				$pt_data[] = array('id' => $data['properties_id'][$pin], 'num' => $data['num' . $fi][$pin], 'name' => $pr);
				$ptdata['num' . $pin . '[]'] = $data['num' . $fi][$pin];
			}
			$list[$index]['properties'] = $pt_data;
			
			$json[$index] = $ptdata;
			$json[$index]['old_prices[]'] = $data['old_prices'][$fi];
			$json[$index]['prices[]'] = $data['prices'][$fi];
			$json[$index]['seckill_prices[]'] = $data['seckill_prices'][$fi];
			$json[$index]['stock_nums[]'] = $data['stock_nums'][$fi];
			$json[$index]['numbers[]'] = $data['numbers'][$fi];
		}
		
		return array('spec_list' => $spec_list, 'properties_list' => $properties_list, 'list' => $list, 'json' => $json);
		
	}
	
	public function discount()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('store', $now_store);
		$discount = D('Foodshop_discount')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('discount_list', $discount);
		$this->display();
	}
	
	public function discount_add()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store',$now_store);
		
		if (IS_POST) {
			$database_discount = D('Foodshop_discount');
			$data_discount['store_id'] = $now_store['store_id'];
			$data_discount['mer_id'] = $now_store['mer_id'];
			$data_discount['full_money'] = $_POST['full_money'];
			$data_discount['reduce_money'] = $_POST['reduce_money'];
			$data_discount['type'] = intval($_POST['type']);
			$data_discount['status'] = intval($_POST['status']);
			$data_discount['source'] = 1;
			if ($database_discount->data($data_discount)->add()) {
				$this->success(L_('添加成功！！'), U('Foodshop/discount',array('store_id' => $now_store['store_id'])));
				die;
				$ok_tips = L_('添加成功！！');
			}else{
				$this->error(L_('添加失败！！请重试。'), U('Foodshop/discount',array('store_id' => $now_store['store_id'])));
				die;
				$error_tips = L_('添加失败！！请重试。');
			}
			if(!empty($error_tips)){
				$this->assign('now_discount', $_POST);
			}
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		$this->display();
	}
	
	public function discount_edit()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		if (!($discount = D('Foodshop_discount')->field(true)->where(array('id' => intval($_GET['id']), 'store_id' => $now_store['store_id']))->find())) {
			$this->error(L_('不存在的优惠，请查证后修改。'), U('Foodshop/discount',array('store_id' => $now_store['store_id'])));
		}
		$this->assign('now_store',$now_store);
		
		if (IS_POST) {
			$database_discount = D('Foodshop_discount');
			$data_discount['id'] = $discount['id'];
			$data_discount['store_id'] = $now_store['store_id'];
			$data_discount['mer_id'] = $now_store['mer_id'];
			$data_discount['full_money'] = $_POST['full_money'];
			$data_discount['reduce_money'] = $_POST['reduce_money'];
			$data_discount['type'] = intval($_POST['type']);
			$data_discount['status'] = intval($_POST['status']);
			$data_discount['source'] = 1;
			if ($database_discount->data($data_discount)->save()) {
				$this->success(L_('修改成功！！'), U('Foodshop/discount',array('store_id' => $now_store['store_id'])));
				die;
			}else{
				$this->error(L_('修改失败！请勿重复提交！'), U('Foodshop/discount',array('store_id' => $now_store['store_id'])));
				die;
			}
			if(!empty($error_tips)){
				$this->assign('now_discount', $_POST);
			}
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		} else {
			$this->assign('now_discount', $discount);
		}
		$this->display();
	}
	
	public function order()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		
		$where = array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']);
		$condition_where = 'Where o.mer_id = '.$now_store['mer_id'].' AND o.store_id = '.$now_store['store_id'];
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$where['real_orderid'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=" AND o.real_orderid ='".$where['real_orderid'] . "'";
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=" AND o.orderid ='".$where['orderid'] . "'";
			} elseif ($_GET['searchtype'] == 'name') {
				$where['name'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=" AND o.name ='".$where['name'] . "'";
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['phone'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=" AND o.phone ='".$where['phone'] . "'";
			}
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		$operate_status = isset($_GET['operate_status']) ? intval($_GET['operate_status']) : -1;
		$this->assign('operate_status', $operate_status);
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';

		// 新餐饮订单
		if (C('config.open_meal_electronic_menu') && $now_store['open_from_shop']) {
            $condition_where .= ' AND o.is_foodshop_new =1';
            $condition_where .= ' AND o.status >0';
			$order_sort .= 'operate_status ASC';
		}else{
            $condition_where .= ' AND o.is_foodshop_new =0';
		}
		// 新餐饮订单状态
		if ($operate_status>=0) {
            $condition_where .= ' AND o.operate_status =' . $operate_status;
		}

		if ($type && $sort) {
			$order_sort .= $type . ' ' . $sort . ',';
			$order_sort .= 'order_id DESC';
		} else {
			$order_sort .= 'order_id DESC';
		}
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if($pay_type=='store_online'){
			$condition_where .=' AND `p`.`offline_pay` =0 ';
		}else if($pay_type=='store_offline'){
			$condition_where .=' AND `p`.`offline_pay` >0 ';
		}else if($pay_type&&$pay_type!='balance'){
			$condition_where .=' AND p.pay_type ="'.$pay_type.'"';
		}else if($pay_type=='balance'){
			$condition_where .= " AND (`p`.`system_balance`<>0 OR `p`.`merchant_balance_pay` <> 0  OR `p`.`merchant_balance_give` <> 0 )";
		}


		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$where['_string'] .=( $where['_string']?' AND ':''). " (pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
			$condition_where .=" AND  (o.pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
		}

		if ($status != -1) {
			$where['status'] = $status;
			$condition_where .= ' AND o.status ='.$status;
		}

		// 必须绑定桌台或者不绑定桌台已支付才显示
		$condition_where .= ' AND (o.unbind_table=0 OR o.status>=3)';
		
// 		echo '<pre/>';
// 		print_r($where);die;
		//$count = D("Foodshop_order")->where($where)->count();
		if($pay_type=='store_online' || $pay_type=='store_offline'){
			$sql_cont = 'SELECT count(o.order_id) as count from pigcms_foodshop_order o LEFT JOIN (select a.* from pigcms_store_order a,(select max(order_id) as order_id,business_id from pigcms_store_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id) p on o.order_id  = p.business_id '.$condition_where.' ORDER BY o.order_id DESC';
		}else{
			$sql_cont = 'SELECT count(o.order_id) as count from pigcms_foodshop_order o LEFT JOIN (select a.* from pigcms_plat_order a,(select max(order_id) as order_id,business_id from pigcms_plat_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id) p on o.order_id  = p.business_id '.$condition_where.' ORDER BY o.order_id DESC';
		}
		$count  = M()->query($sql_cont);

		$count = $count[0]['count'];

		import('@.ORG.merchant_page');
		$p = new Page($count, 20);

		if($pay_type=='store_online' || $pay_type=='store_offline'){
			$sql = 'SELECT o.*,p.pay_type as pay_method from pigcms_foodshop_order o LEFT JOIN (select a.* from pigcms_store_order a,(select max(order_id) as order_id,business_id from pigcms_store_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id) p on o.order_id  = p.business_id '.$condition_where.' ORDER BY o.order_id DESC'.' limit '.$p->firstRow.','.$p->listRows;

		}else{

			$sql = 'SELECT o.*,p.pay_type as pay_method from pigcms_foodshop_order o LEFT JOIN (select a.* from pigcms_plat_order a,(select max(order_id) as order_id,business_id from pigcms_plat_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id) p on o.order_id  = p.business_id '.$condition_where.' ORDER BY o.order_id DESC'.' limit '.$p->firstRow.','.$p->listRows;
		}
		$list = M('')->query($sql);
		$mer_ids = $store_ids = array();
		foreach ($list as $l) {
			$mer_ids[] = $l['mer_id'];
			$store_ids[] = $l['store_id'];
			$table_types[] = $l['table_type'];
			$tids[] = $l['table_id'];

		}


		$type_list = array();
		if ($table_types) {
			$temp_type_list = M('Foodshop_table_type')->field(true)->where(array('id' => array('in', $table_types)))->select();
			foreach ($temp_type_list as $tmp) {
				$type_list[$tmp['id']] = $tmp;
			}
		}
		$table_list = array();
		if ($tids) {
			$temp_table_list = M('Foodshop_table')->field(true)->where(array('id' => array('in', $tids)))->select();
			foreach ($temp_table_list as $temp) {
				$table_list[$temp['id']] = $temp;
			}
		}


		$store_temp = $mer_temp = array();
		if ($mer_ids) {
			$merchants = D("Merchant")->where(array('mer_id' => array('in', $mer_ids)))->select();
			foreach ($merchants as $m) {
				$mer_temp[$m['mer_id']] = $m;
			}
		}
		if ($store_ids) {
			$merchant_stores = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_stores as $ms) {
				$store_temp[$ms['store_id']] = $ms;
			}
		}
		foreach ($list as &$li) {
			$li['merchant_name'] = isset($mer_temp[$li['mer_id']]['name']) ? $mer_temp[$li['mer_id']]['name'] : '';
			$li['store_name'] = isset($store_temp[$li['store_id']]['name']) ? $store_temp[$li['store_id']]['name'] : '';

			$li['table_type_name'] = isset($type_list[$li['table_type']]) ? $type_list[$table_list[$li['table_id']]['tid']]['name'] . '(' . $type_list[$table_list[$li['table_id']]['tid']]['min_people'] . '-' . $type_list[$table_list[$li['table_id']]['tid']]['max_people'] . '人)' : '';
			$li['table_name'] = isset($table_list[$li['table_id']]) ? $table_list[$li['table_id']]['name'] : '';
			if ($li['is_foodshop_new']) {
				$li['show_status'] = D('Foodshop_order')->operate_status_list[$li['operate_status']];
			}else{
				$li['show_status'] = D('Foodshop_order')->status_list[$li['status']];
			}

			// 堂扫实付金额
			if ($li['tax_rate_price']) {
				$li['price'] = $li['price'] + $li['tax_rate_price'];
			}
			// 在线支付详情
            $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$li['order_id'],'order_type'=>'plat','pay_type'=>$li['pay_type'],'paid_money'=>array('gt',0)))->find();
            if($li['price'] == $payment_money_order['payment_money']){
                //可退金额包括用户实际支付的支付手续费
                $li['price'] = $payment_money_order['paid_money'];
                $li['service_charge'] = $payment_money_order['service_charge'];
            }
		}
		$this->assign('order_list', $list);

		$pagebar = $p->show();
		$this->assign('status_list', D('Foodshop_order')->status_list);
		$this->assign('operate_status_list', D('Foodshop_order')->operate_status_list);
		$this->assign($list);

		// 堂扫
		$pay_method_new['alipay']['name'] = L_('支付宝');
		$this->assign('pay_method_new_js',json_encode($pay_method_new));

		$pay_method = D('Config')->get_pay_method('','',1);
		$pay_method['balance']['name'] = L_('余额支付');
		$pay_method['store_online']['name'] = L_('店员点餐线上支付');
		$pay_method['store_offline']['name'] = L_('店员点餐线下支付');
		$this->assign('pay_method_js',json_encode($pay_method));

		// 堂扫
		if (C('config.open_meal_electronic_menu') && $now_store['open_from_shop']) {
			$pay_method = $pay_method_new;
		}

		unset($pay_method['offline']);
		$this->assign('pay_method',$pay_method);
		$this->assign('pagebar',$pagebar);
		$this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status,'pay_type'=>$pay_type));

		$where = array('p.store_id' => $store_id, 'p.paid' => 1);
		if ($period) {
			$where['o.pay_time'] = array(array('gt', $period[0]), array('lt', $period[1]));
		}
		if ($status != -1) {
			$where['o.status'] = $status;

		}
		$where['business_type'] = 'foodshop';
		$where['refund_detail'] = array('eq','');
		$data = array('total_price' => 0, 'online_price' => 0, 'offline_price' => 0);
		// $field = 'sum(total_money - wx_cheap) AS total_price, sum(total_money - system_balance - system_coupon_price - system_score_money - merchant_balance_pay - merchant_balance_give - merchant_discount_money - merchant_coupon_price - pay_money) AS offline_price, sum(pay_money+system_balance + system_coupon_price + system_score_money + merchant_balance_pay + merchant_balance_give + merchant_discount_money + merchant_coupon_price) as online_price';
		// $order = D('Plat_order')->join('as p RIGHT JOIN '.C('DB_PREFIX').'foodshop_order as o ON p.business_id=o.order_id')->field($field)->where($where)->find();

		$field = 'sum(p.total_money - p.wx_cheap + p.service_charge) AS total_price, sum(p.total_money + p.service_charge - p.system_balance - p.system_coupon_price - p.system_score_money - p.merchant_balance_pay - p.merchant_balance_give - p.merchant_discount_money - p.merchant_coupon_price - p.pay_money) AS offline_price, sum(p.pay_money+p.system_balance + p.system_coupon_price + p.system_score_money + p.merchant_balance_pay + p.merchant_balance_give + p.merchant_discount_money + p.merchant_coupon_price) as online_price';

		$order = D('Plat_order')->field($field)->join(' as p left join '.C('DB_PREFIX').'foodshop_order as f on f.order_id = p.order_id')->where($where)->find();

		
		$data['total_price'] += floatval($order['total_price']);
		$data['online_price'] += floatval($order['online_price']);
		$data['offline_price'] += floatval($order['offline_price']);
		$field = 'sum(price) AS total_price, sum(price - balance_pay - card_price - payment_money - merchant_balance - card_give_money - score_deducte) AS offline_price, sum(payment_money+balance_pay + card_price + merchant_balance + card_give_money + score_deducte) as online_price';

		$where = array('store_id' => $store_id, 'paid' => 1);
		if ($period) {
			$where['pay_time'] = array(array('gt', $period[0]), array('lt', $period[1]));
		}
		if ($status != -1) {
			$where['status'] = $status;
		}
		$where['business_type'] = 'foodshop';

		$order = D('Store_order')->field($field)->where($where)->find();
		$data['total_price'] += floatval($order['total_price']);
		$data['online_price'] += floatval($order['online_price']);
		$data['offline_price'] += floatval($order['offline_price']);
		$this->assign($data); 
		$this->display();
	}

	public function order_detail()
	{
		$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : 0;
		if(strlen($order_id)>=15){
			$res = M('Foodshop_order')->where(array('real_orderid'=>$order_id))->find();
			$order_id = $res['order_id'];
		}
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $store_id), 3);
		//备注
		$store_order = D('Store_order')->where(array('business_id'=>$order_id,'business_type'=>'foodshop','paid'=>1))->find();
		$plat_order = D('Plat_order')->where(array('business_id'=>$order_id,'business_type'=>'foodshop','paid'=>1))->find();
		$store_order['desc'] && $order['note'] = $store_order['desc'];
				
        // 实付金额
        $discount_money = 0;
        if ($store_order) {  
            $discount_money += $store_order['card_price'] + $store_order['coupon_price']+ $store_order['score_deducte'];
        }
        if ($plat_order) {
            $discount_money +=  $plat_order['system_coupon_price'] + $plat_order['system_score_money'] + $plat_order['merchant_coupon_price'];
        }

        if ($store_order['card_discount']) {  
            $discount_money = round($store_order['price']-($store_order['price']-$store_order['no_discount_money'])*$store_order['card_discount']/10-$store_order['no_discount_money'],2);
        }
        $plat_order['merchant_discount_money'] && $discount_money += $plat_order['merchant_discount_money'];
        $order['price'] = $order['price'] - $discount_money;

        // 堂扫实付金额
		if ($order['tax_rate_price']) {
			$order['price'] = $order['price'] + $order['tax_rate_price'];
		}
		// 在线支付详情
        $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$plat_order['order_id'],'order_type'=>'plat','pay_type'=>$order['online_pay'],'paid_money'=>array('gt',0)))->find();
        if($payment_money_order['payment_money']){
            //可退金额包括用户实际支付的支付手续费
            $order['price'] = $payment_money_order['paid_money'];
            $order['service_charge'] = $payment_money_order['service_charge'];
        }

		$this->assign('order', $order);
		$this->display();
	}

	public function ajax_del_pic() {
		$group_image_class = new foodshop_goods_image();
		$group_image_class->del_image_by_path($_POST['path']);
	}
	
	public function clone_goods()
	{
		$source_store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
		$store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : 0;
		
		if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $source_store_id, 'have_shop' => 1))->find()) {
			if ($shop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $source_store_id))->find()) {
				
			} else {
				$this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',array('X1'=>$this->config['shop_alias_name'])));
			}
		} else {
			$this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',array('X1'=>$this->config['shop_alias_name'])));
		}
		foreach ($store_ids as $store_id) {
			if ($target_store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
				if (!$target_shop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $store_id))->find()) {
					continue;
				}
			} else {
				continue;
			}
			
			
			$goods_sorts = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $source_store_id))->select();
			foreach ($goods_sorts as $sort) {
				
				$source_sort_id = $sort['sort_id'];
				
				if ($target_sort = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $store_id, 'sort_name' => $sort['sort_name']))->find()) {
					$target_sort_id = $target_sort['sort_id'];
				} else {
					$sort['store_id'] = $store_id;
					unset($sort['sort_id']);
					$target_sort_id = M('Foodshop_goods_sort')->add($sort);
				}
				
				$goods_list = M('Foodshop_goods')->field(true)->where(array('store_id' => $source_store_id, 'sort_id' => $source_sort_id))->select();
				foreach ($goods_list as $goods) {
					if ($tmp_goods = M('Foodshop_goods')->field(true)->where(array('name' => $goods['name'], 'store_id' => $store_id))->find()) {
						continue;
					} else {
						$source_goods_id = $goods['goods_id'];
						unset($goods['goods_id']);
						$goods['store_id'] = $store_id;
						$goods['sort_id'] = $target_sort_id;
						$goods['print_id'] = 0;
						$target_goods_id = M('Foodshop_goods')->add($goods);
						$pro_map = $spec_map = $spec_value_map = array();
						if ($goods['is_properties']) {
							$properties = M('Foodshop_goods_properties')->field(true)->where(array('goods_id' => $source_goods_id))->select();
							foreach ($properties as $pro_data) {
								$source_pro_id = $pro_data['id'];
								unset($pro_data['id']);
								$pro_data['goods_id'] = $target_goods_id;
								$pro_map[$source_pro_id] = M('Foodshop_goods_properties')->add($pro_data);
							}
						}
						if ($goods['spec_value']) {
							$spec_list = M('Foodshop_goods_spec')->field(true)->where(array('goods_id' => $source_goods_id, 'store_id' => $source_store_id))->select();
							foreach ($spec_list as $spec) {
								$source_spec_id = $spec['id'];
								unset($spec['id']);
								
								$spec['store_id'] = $store_id;
								$spec['goods_id'] = $target_goods_id;
								$spec_map[$source_spec_id] = M('Foodshop_goods_spec')->add($spec);
								
								$spec_value_list = M('Foodshop_goods_spec_value')->field(true)->where(array('sid' => $source_spec_id))->select();
								foreach ($spec_value_list as $spec_value) {
									$source_spec_value_id = $spec_value['id'];
									unset($spec_value['id']);
									$spec_value['sid'] = $spec_map[$source_spec_id];
									$spec_value_map[$source_spec_value_id] = M('Foodshop_goods_spec_value')->add($spec_value);
								}
							}
							
							$spec_array = explode('#', $goods['spec_value']);
							$target_spec_value_array = array();
							foreach ($spec_array as $str) {
								$row_array = explode('|', $str);
								$spec_val_ids = explode(':', $row_array[0]);
								$new_ids = array();
								foreach ($spec_val_ids as $tid) {
									$new_ids[] = $spec_value_map[$tid];
								}
								$row_array[0] = implode(':', $new_ids);
								if (count($row_array) > 3) {
									$pro_str_ids = explode(':', $row_array[2]);
									$new_pro_ids = array();
									foreach ($pro_str_ids as $pstr) {
										$v_k_a = explode('=', $pstr);
										$new_pro_ids[] = $pro_map[$v_k_a[0]] . '=' . $v_k_a[1];
									}
									$row_array[2] = implode(':', $new_pro_ids);
								}
								$target_spec_value_array[] = implode('|', $row_array);
							}
							M('Foodshop_goods')->where(array('goods_id' => $target_goods_id))->save(array('spec_value' => implode('#', $target_spec_value_array)));
						}
					}
				}
			}
			$this->success(L_('克隆完成'));
		}
	}
	
	public function store()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
			if ($shop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $store_id))->find()) {
		
			} else {
				$this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',array('X1'=>$this->config['shop_alias_name'])));
			}
		} else {
			$this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',array('X1'=>$this->config['shop_alias_name'])));
		}
		
		$sql = "SELECT s.store_id, s.name FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_foodshop AS sh ON sh.store_id=s.store_id WHERE s.have_meal=1 AND s.status=1 AND s.store_id<>{$store_id} AND s.mer_id={$this->merchant_session['mer_id']}";
		$res = D()->query($sql);
		$this->assign('stores', $res);
		$this->assign('store_id', $store_id);
		$this->display();
	}

	//桌台二维码下载页面
	public function table_qrcode_download_page() {
	    if(IS_POST) {
	        $is_common = $_POST['is_common'];
	        $store_id = $_POST['store_id'];
	        if(empty($is_common)) {
                $this->error(L_('请选择下载的桌台码类型'));
            }

	        //桌台信息
            $now_store = $this->check_store($store_id);

            $tables = M('Foodshop_table')->field(true)->where(array('store_id' => $now_store['store_id']))->order('tid ASC,id ASC')->select();
            $types = M('Foodshop_table_type')->field(true)->where(array('store_id' => $now_store['store_id']))->select();

            $format_types = array();
            foreach ($types as $type) {
                $format_types[$type['id']] = $type;
            }
            foreach ($tables as &$table) {
                $table['type_name'] = isset($format_types[$table['tid']]['name']) ? $format_types[$table['tid']]['name'] : '';
                $table['store_logo'] = !empty($now_store['logo']) ? $now_store['logo'] : '';
            }

//            var_dump($tables);exit();

            //循环生成桌台的二维码
            foreach($tables as $key => $value) {
                if($is_common == 1) { //生成已装修的二维码

                } elseif($is_common == 2) {//生成普通二维码

                    //生成桌台二维码
                    $qrCon = $this->config['site_url'] . '/wap.php?g=Wap&c=Foodshop&a=scan_qcode&store_id=' . $store_id . '&table_id=' . $value['id'].'&order_from=1';
                    import('@.ORG.phpqrcode');
                    $size = $_GET['size'] ? $_GET['size']: 10;
                    QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1);
                    exit();
                }
            }


        }

        $store_id = I('store_id');
        $this->assign('store_id', $store_id);
	    $this->display();
    }


	
	public function table()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$showTab = isset($_GET['showTab']) ? intval($_GET['showTab']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		$tables = M('Foodshop_table')->field(true)->where(array('store_id' => $now_store['store_id']))->order('tid ASC,id ASC')->select();
		$types = M('Foodshop_table_type')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		$staffs = D('Merchant_store_staff')->field('id, name')->where(array('store_id' => $now_store['store_id']))->select();
		$sData = array();
		foreach ($staffs as $staff) {
		    $sData[$staff['id']] = $staff['name'];
		}
		$format_types = array();
		foreach ($types as $type) {
			$format_types[$type['id']] = $type;
		}
		foreach ($tables as &$table) {
			$table['type_name'] = isset($format_types[$table['tid']]['name']) ? $format_types[$table['tid']]['name'] : '';
		}

		$this->assign('showTab', $showTab);
		$this->assign('tables', $tables);
		$this->assign('table_types', $types);
		$this->display();
	}
	
	
	public function table_add()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		$types = M('Foodshop_table_type')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		if (empty($types)) {
			$this->error(L_('桌台还没有分类，请先增加桌台分类。'), U('Foodshop/type_add',array('store_id' => $now_store['store_id'])));
		}
		$this->assign('types', $types);
		$staffs = D('Merchant_store_staff')->field('id, name')->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('staffs', $staffs);
		if (IS_POST) {
			$data = array('store_id' => $now_store['store_id']);
			$error_tips = '';
			if (empty($_POST['name'])) {
				$error_tips .= L_('桌台名称必填！').'<br/>';
			} else {
				$data['name'] = htmlspecialchars(trim($_POST['name']));
			}
			$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
			$type_data = M('Foodshop_table_type')->field(true)->where(array('id' => $tid))->find();
			if (empty($type_data)) {
				$error_tips .= L_('桌台分类不存在！').'<br/>';
			} else {
				$data['tid'] = $tid;
			}
			$staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
			if($staff_id != 0){
                $staff = D('Merchant_store_staff')->field(true)->where(array('id' => $staff_id, 'store_id' => $now_store['store_id']))->find();
                if (empty($staff)) {
                    $error_tips .= L_('店员不存在！').'<br/>';
                }else {
                    $data['staff_id'] = $staff_id;
                }
            }else{
                $data['staff_id'] = $staff_id;
            }
			$service_fee = floatval($_POST['service_fee']);
			if($service_fee>100){
				$error_tips .= L_('桌台服务费最多只能设置100%').'<br/>';
			}else{
				$data['service_fee'] = $service_fee;
			}


			
// 			$min_num = isset($_POST['min_num']) ? intval($_POST['min_num']) : 0;
// 			$max_num = isset($_POST['max_num']) ? intval($_POST['max_num']) : 0;
// 			if ($max_num < 1 || $min_num < 1) {
// 				$error_tips .= '容纳人数填写不正确！'.'<br/>';
// 			} elseif ($max_num < $min_num) {
// 				$error_tips .= '最大容纳人数不能小于最少人数！'.'<br/>';
// 			} else {
// 				$data['min_num'] = $min_num;
// 				$data['max_num'] = $max_num;
// 			}
// 			if (!($min_num <= $type_data['people'] && $type_data['people'] <= $max_num)) {
// 				$error_tips .= '您的餐桌容纳人数不适合在该分类下！'.'<br/>';
// 			}
			if (empty($error_tips)) {
				if (D('Foodshop_table')->data($data)->add()) {
					M('Foodshop_table_type')->where(array('store_id' => $now_store['store_id'], 'id' => $tid))->setInc('num');
					$this->success(L_('添加成功！！'), U('Foodshop/table',array('store_id' => $now_store['store_id'], 'showTab' => 1)));
					die;
				} else {
					$error_tips .= L_('添加失败！！请重试。');
				}
			}
			if (!empty($error_tips)) {
				$this->assign('now_table', $_POST);
			}
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		$this->display();
	}
	
	
	public function table_edit()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		$types = M('Foodshop_table_type')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		if (empty($types)) {
			$this->error(L_('桌台还没有分类，请先增加桌台分类。'), U('Foodshop/type_add',array('store_id' => $now_store['store_id'])));
		}
		$table_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$table = M('Foodshop_table')->field(true)->where(array('id' => $table_id, 'store_id' => $now_store['store_id']))->find();
		if (IS_POST) {
			$data = array('store_id' => $now_store['store_id']);
			$error_tips = '';
			if (empty($_POST['name'])) {
				$error_tips .= L_('桌台名称必填！').'<br/>';
			} else {
				$data['name'] = htmlspecialchars(trim($_POST['name']));
			}
			$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
			$type_data = M('Foodshop_table_type')->field(true)->where(array('id' => $tid))->find();
			if (empty($type_data)) {
				$error_tips .= L_('桌台分类不存在！').'<br/>';
			} else {
				$data['tid'] = $tid;
			}
			$staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            if($staff_id != 0){
                $staff = D('Merchant_store_staff')->field(true)->where(array('id' => $staff_id, 'store_id' => $now_store['store_id']))->find();
                if (empty($staff)) {
                    $error_tips .= L_('店员不存在！').'<br/>';
                }else {
                    $data['staff_id'] = $staff_id;
                }
            }else{
                $data['staff_id'] = $staff_id;
            }

			$service_fee = floatval($_POST['service_fee']);
			if($service_fee>100){
				$error_tips .= L_('桌台服务费最多只能设置100%').'<br/>';
			}else{
				$data['service_fee'] = $service_fee;
			}
// 			$min_num = isset($_POST['min_num']) ? intval($_POST['min_num']) : 0;
// 			$max_num = isset($_POST['max_num']) ? intval($_POST['max_num']) : 0;
// 			if ($max_num < 1 || $min_num < 1) {
// 				$error_tips .= '容纳人数填写不正确！'.'<br/>';
// 			} elseif ($max_num < $min_num) {
// 				$error_tips .= '最大容纳人数不能小于最少人数！'.'<br/>';
// 			} else {
// 				$data['min_num'] = $min_num;
// 				$data['max_num'] = $max_num;
// 			}
			if (empty($error_tips)) {
				if (D('Foodshop_table')->where(array('id' => $table_id, 'store_id' => $now_store['store_id']))->save($data)) {
				    if ($table['tid'] != $tid) {
				        M('Foodshop_table_type')->where(array('store_id' => $now_store['store_id'], 'id' => $tid))->setInc('num');
				        M('Foodshop_table_type')->where(array('store_id' => $now_store['store_id'], 'id' => $table['tid']))->setDec('num');
				    }
				    $this->success(L_('保存成功！！'), U('Foodshop/table',array('store_id' => $now_store['store_id'], 'showTab' => 1)));
					die;
				} else {
					$error_tips = L_('保存失败！！您是不是没做过修改？请重试。');
				}
			}
			$this->assign('now_table', $_POST);
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		} else {
			$this->assign('now_table', $table);
		}
		$staffs = D('Merchant_store_staff')->field('id, name')->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('staffs', $staffs);
		$this->assign('types', $types);
		$this->display();
	}

	//批量设置批量设置桌台服务费
	public function batch_service_edit(){
		if (IS_POST) {
			$id = trim($_POST['id'],',');
			$id_arr = explode(',',$id);
			if(empty($id_arr)){
				$this->error(L_('请选择桌台'));
			}
			$service_fee = floatval($_POST['service_fee']);
			if($service_fee>100){
				$this->error(L_('最高可设置100%'));
			}
			$model = D('Foodshop_table');
			$where = array();
			$where['id'] = array('in',$id_arr);
			$r = $model->where($where)->save(array('service_fee'=>$service_fee));
			if($r){
				$this->success(L_('设置成功'));
			}else{
				$this->success(L_('设置失败'));
			}
		}else{
			$this->display();
		}
	}
	
	public function table_del()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		
		if ($table = M('Foodshop_table')->field(true)->where(array('store_id' => $now_store['store_id'], 'id' => $id))->find()) {
			if (M('Foodshop_table')->where(array('store_id' => $now_store['store_id'], 'id' => $id))->delete()) {
				M('Foodshop_table_type')->where(array('store_id' => $now_store['store_id'], 'id' => $table['tid']))->setDec('num');
				$this->success(L_('删除成功'));
			} else {
				$this->error(L_('删除失败，稍后重试'));
			}
		} else {
			$this->error(L_('非法的数据'));
		}
	}
	public function type_add()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		if (IS_POST) {
			$data = array('store_id' => $now_store['store_id']);
			$error_tips = '';
			if (empty($_POST['name'])) {
				$error_tips .= L_('桌台分类名称必填！').'<br/>';
			} else {
				$data['name'] = htmlspecialchars(trim($_POST['name']));
			}
			$min_people = isset($_POST['min_people']) ? intval($_POST['min_people']) : 0;
			$max_people = isset($_POST['max_people']) ? intval($_POST['max_people']) : 0;
			if ($max_people < 1 || $min_people < 1) {
				$error_tips .= L_('容纳人数填写不正确！').'<br/>';
			} elseif ($max_people < $min_people) {
				$error_tips .= L_('最大容纳人数不能小于最少人数！').'<br/>';
			} else {
				$data['min_people'] = $min_people;
				$data['max_people'] = $max_people;
			}
			if($_POST['service_fee']<0||$_POST['service_fee']>100){
				$error_tips .= L_('服务费只能设置0-100之间').'<br/>';
			}else{
				$data['service_fee'] = isset($_POST['service_fee']) ? floatval($_POST['service_fee']) : 0;
			}
			$data['deposit'] = isset($_POST['deposit']) ? floatval($_POST['deposit']) : 0;
			$data['use_time'] = isset($_POST['use_time']) ? intval($_POST['use_time']) : 60;
			$data['number_prefix'] = isset($_POST['number_prefix']) ? htmlspecialchars($_POST['number_prefix']) : '';
			if (empty($data['number_prefix'])) {
				$error_tips .= L_('排号前缀不能为空！').'<br/>';
			}
			if (D('Foodshop_table_type')->field('number_prefix')->where(array('store_id' => $now_store['store_id'], 'number_prefix' => $data['number_prefix']))->find()) {
				$error_tips .= L_('排号前缀已存在！').'<br/>';
			}
			
			if (empty($error_tips)) {
				if (D('Foodshop_table_type')->data($data)->add()) {
					$this->success(L_('添加成功！！'), U('Foodshop/table',array('store_id' => $now_store['store_id'])));
					die;
				} else {
					$error_tips .= L_('添加失败！！请重试。');
				}
			}
			if (!empty($error_tips)) {
				$this->assign('now_type', $_POST);
			}
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		$this->display();
	}
	
	
	public function type_edit()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		
		$table_type = M('Foodshop_table_type')->field(true)->where(array('id' => $_GET['id'], 'store_id' => $now_store['store_id']))->find();
		if (IS_POST) {
			$data = array('store_id' => $now_store['store_id']);
			$error_tips = '';
			if (empty($_POST['name'])) {
				$error_tips .= L_('桌台名称必填！').'<br/>';
			} else {
				$data['name'] = htmlspecialchars(trim($_POST['name']));
			}
			$min_people = isset($_POST['min_people']) ? intval($_POST['min_people']) : 0;
			$max_people = isset($_POST['max_people']) ? intval($_POST['max_people']) : 0;
			if ($max_people < 1 || $min_people < 1) {
				$error_tips .= L_('容纳人数填写不正确！').'<br/>';
			} elseif ($max_people < $min_people) {
				$error_tips .= L_('最大容纳人数不能小于最少人数！').'<br/>';
			} else {
				$data['min_people'] = $min_people;
				$data['max_people'] = $max_people;
			}
			
			if($_POST['service_fee']<0||$_POST['service_fee']>100){
				$error_tips .= L_('服务费只能设置0-100之间').'<br/>';
			}else{
				$data['service_fee'] = isset($_POST['service_fee']) ? floatval($_POST['service_fee']) : 0;
			}
			
			$data['deposit'] = isset($_POST['deposit']) ? floatval($_POST['deposit']) : 0;
			
			$data['use_time'] = isset($_POST['use_time']) ? intval($_POST['use_time']) : 60;
			$data['number_prefix'] = isset($_POST['number_prefix']) ? htmlspecialchars($_POST['number_prefix']) : '';
			if (empty($data['number_prefix'])) {
				$error_tips .= L_('排号前缀不能为空！').'<br/>';
			}
			$tobj = D('Foodshop_table_type')->field(true)->where(array('store_id' => $now_store['store_id'], 'number_prefix' => $data['number_prefix']))->find();
			if ($tobj && $tobj['id'] != intval($_GET['id'])) {
				$error_tips .= L_('排号前缀已存在！').'<br/>';
			}
			if (empty($error_tips)) {
				if (D('Foodshop_table_type')->where(array('store_id' => $now_store['store_id'], 'id' => intval($_GET['id'])))->save($data)) {
					$this->success(L_('保存成功！！'), U('Foodshop/table',array('store_id' => $now_store['store_id'])));
					die;
				} else {
					$error_tips = L_('保存失败！！您是不是没做过修改？请重试。');
				}
			}
			$this->assign('now_type', $_POST);
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		} else {
			$this->assign('now_type', $table_type);
		}
		$this->display();
	}
	
	public function type_del()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if ($table_type = M('Foodshop_table_type')->field(true)->where(array('store_id' => $now_store['store_id'], 'id' => $id))->find()) {
			$tables = M('Foodshop_table')->field(true)->where(array('store_id' => $now_store['store_id'], 'tid' => $id))->select();
			if ($tables) {
				$this->error(L_('该分类下还有桌台，先清空桌台后才能删除'));
			} else {
				if (M('Foodshop_table_type')->where(array('store_id' => $now_store['store_id'], 'id' => $id))->delete()) {
					$this->success(L_('删除成功'));
				} else {
					$this->error(L_('删除失败，稍后重试'));
				}
			}
		} else {
			$this->error(L_('非法的数据'));
		}
	}
	
	public function package()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		$packages = M('Foodshop_goods_package')->field(true)->where(array('store_id' => $now_store['store_id']))->select();
		$this->assign('packages', $packages);
		$this->display();
	}
	private function dstrlen($str)
	{
		$count = 0;
		for ($i = 0; $i < strlen($str); $i++) {
			$value = ord($str[$i]);
			if ($value > 127) {
				if ($value >= 192 && $value <= 223) {
					$i++;
				} elseif ($value >= 224 && $value <= 239) {
					$i = $i + 2;
				} elseif ($value >= 240 && $value <= 247) {
					$i = $i + 3;
				}
			}
			$count++;
		}
		return $count;
	}
	public function package_add()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		if (IS_POST) {
			if (empty($_POST['name'])) {
				$error_tips .= L_('套餐名称必填！').'<br/>';
			}
			if ($this->dstrlen($_POST['name']) > 20) {
				$error_tips .= L_('套餐名称不超过20个字').'<br/>';
			}
			if ($this->dstrlen($_POST['note']) > 200) {
				$error_tips .= L_('使用说明不超过200个字').'<br/>';
			}
			if (empty($error_tips)) {
				if ($goods_id = D('Foodshop_goods_package')->save_post_form($_POST, $now_store['store_id'])) {
					$this->success(L_('添加成功！'), U('Foodshop/package', array('store_id' => $now_store['store_id'])));
					die;
				} else {
					$this->error(L_('添加失败！请重试！'), U('Foodshop/package', array('store_id' => $now_store['store_id'])));
					die;
				}
			} else {
				$return = $this->format_package_data($_POST, $now_store['store_id']);
				$this->assign('package', $return);
			}
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}

		$this->assign('packages', $packages);
		$this->display();
	}

	
	private function format_package_data($data, $store_id)
	{
		$return = array('name' => $data['name'], 'price' => $data['price'], 'note' => $data['note']);
		$details = array();
		foreach ($data['nums'] as $i => $num) {
			if (isset($data['goods_ids'][$i]) && $data['goods_ids'][$i]) {
				$goods_ids = $data['goods_ids'][$i];
				$goods_list = D('Foodshop_goods')->field(true)->where(array('status' => 1, 'store_id' => $store_id, 'goods_id' => array('in', $goods_ids)))->select();
				$temp = array('num' => max(1, $num));
				foreach ($goods_list as $goods) {
					$temp['goods_list'][] = $goods;
				}
				$details[] = $temp;
			}
		}
		$return['goods_detail'] = $details;
		return $return;
	}
	public function package_edit()
	{
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $now_store = $this->check_store($store_id);
        
        $package = D('Foodshop_goods_package')->get_detail_by_id(array('id' => $id, 'store_id' => $now_store['store_id']), true);
        
        if(!empty($package['image'])){
            $image = $package['image'];
            $goods_image_class = new foodshop_goods_image();
            $package['pic']['title'] = $image;
            $package['pic']['url'] = $goods_image_class->get_image_by_path($image, 's');
        }
		$this->assign('now_store', $now_store);
		if (IS_POST) {
// 			echo '<Pre/>';
// 			print_r($_POST);
// 			die;
			if (empty($_POST['name'])) {
				$error_tips .= L_('套餐名称必填！').'<br/>';
			}
			if ($this->dstrlen($_POST['name']) > 20) {
				$error_tips .= L_('套餐名称不超过20个字').'<br/>';
			}
			if ($this->dstrlen($_POST['note']) > 200) {
				$error_tips .= L_('使用说明不超过200个字').'<br/>';
			}
				
			if (empty($error_tips)) {
				$_POST['id'] = $id;
				if ($goods_id = D('Foodshop_goods_package')->save_post_form($_POST, $now_store['store_id'])) {
					$this->success(L_('编辑成功！'), U('Foodshop/package', array('store_id' => $now_store['store_id'])));
					die;
				} else {
					$this->error(L_('编辑失败！请重试！'), U('Foodshop/package', array('store_id' => $now_store['store_id'])));
					die;
				}
			} else {
				$return = $this->format_package_data($_POST, $now_store['store_id']);
				$this->assign('package', $return);
			}
				
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		$this->assign('package', $package);
		$this->display();
	}
	
	public function package_detail()
	{
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$package = D('Foodshop_goods_package')->get_detail_by_id(array('id' => $id, 'store_id' => $now_store['store_id']), true);
		$this->assign('now_store', $now_store);
		$this->assign('package', $package);
		$this->display();
	}

	public  function store_slider(){
		$count  = M('Store_slider')->count();

		import('@.ORG.merchant_page');
		$p = new Page($count,15);
		$slider_list = M('Store_slider')->where(array('store_id'=>$_GET['store_id']))->order('sort DESC')->limit($p->firstRow,$p->listRows)->select();

		$this->assign('slider_list',$slider_list);
		$this->display();
	}
	
	public function slider_del(){
		$id = $_GET['id'];
		M('Store_slider')->where(array('id'=>$id))->delete();
		$this->success(L_('删除成功！'));
	}


	public  function store_slider_add(){
		if(IS_POST){
			
			if($_FILES['pic']['error'] != 4) {
				$image = D('Image')->handle($this->system_session['id'], 'slider');
				if (!$image['error']) {

					$_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
				} else {
					$this->error( $image['msg']);
				}
			}

			if(empty($_POST['name']) || empty($_POST['url']) ){
				$this->error(L_('数据未填写完整'));
			}
			$_POST['url'] = htmlspecialchars_decode($_POST['url']);
			$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
			if($res = M('Store_slider')->where(array('id'=>$_POST['id']))->find()){
				$id = $res['id'];
				$result = M('Store_slider')->where(array('id'=>$_POST['id']))->save($_POST);
			}else{
				if( empty($_POST['pic'])){
					$this->error(L_('数据未填写完整'));
				}
				$result = M('Store_slider')->add($_POST);
				$id = $result;
			}
			if($result){
				D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');
				S('slider_list_'.$_POST['cat_id'],NULL);
				$this->success(L_('保存成功'));
			}else{
				$this->error(L_('保存失败'));
			}
		}else{
			$silder = M('Store_slider')->where(array('id'=>$_GET['id']))->find();
			$this->assign('slider',$silder);
			$this->display();
		}
	}
	
	public function menu()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		
		$database_goods = D('Foodshop_goods');
		$condition_goods['status'] = 1;
		$condition_goods['is_must'] = 0;
		$condition_goods['store_id'] = $now_store['store_id'];
		$condition_goods['spec_value'] = '';
		$condition_goods['is_properties'] = 0;
		$count_goods = $database_goods->where($condition_goods)->count();
		$p = new Page($count_goods, 20);
		$goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
		
		$this->assign('goods_list', $goods_list);
		$this->assign('pagebar', $p->show());
		$this->display();
	}
	
	/* 分类状态 */
	public function package_status()
	{
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$package_db = D('Foodshop_goods_package');
		$where = array('store_id' => $now_store['store_id']);
		$where['id'] = $id;
		$data['status'] = $_POST['type'] == 'open' ? '1' : '0';
		if ($package_db->where($where)->save($data)) {
			exit('1');
		} else {
			exit;
		}
	}
	
	public function total()
	{
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		$sort_id = isset($_POST['sort_id']) ? intval($_POST['sort_id']) : 0;
		$begin_time = isset($_POST['begin_time']) && $_POST['begin_time'] ? strtotime(htmlspecialchars($_POST['begin_time'])) : 0;
		$end_time = isset($_POST['end_time']) && $_POST['end_time'] ? strtotime(htmlspecialchars($_POST['end_time'])) : 0;
		$goods_sorts = D('Foodshop_goods_sort')->field(true)->where(array('store_id' => $store_id))->select();
		$sort_list = array();
		foreach ($goods_sorts as $s) {
			$sort_list[$s['sort_id']] = $s;
		}
		
		$where = array('store_id' => $store_id);
		$now_time = time();
		if ($begin_time > $now_time) {
			$begin_time = $end_time = 0;
		}
		if (empty($begin_time) || empty($end_time)) {
			$begin_time = strtotime(date('Ymd', strtotime("-1 day")) . ' 00:00:00');
			$end_time = strtotime(date('Y-m-d', strtotime("-1 day")) . ' 23:59:59');
		} elseif ($begin_time > $end_time) {
			$end_time = $now_time;
		}
		$where['create_time'] = array(array('gt', $begin_time), array('lt', $end_time));
		$sort_id && $where['sort_id'] = $sort_id;
		$details = D('Foodshop_order_detail')->field('sum(num) AS num, name, goods_id, sort_id')->where($where)->group('goods_id')->select();
		
		foreach ($details as &$detail) {
			$detail['sort_name'] = isset($sort_list[$detail['sort_id']]['sort_name']) ? $sort_list[$detail['sort_id']]['sort_name'] : '';
		}
		$this->assign(array('begin_time' => date('Y-m-d H:i:s', $begin_time), 'end_time' => date('Y-m-d H:i:d', $end_time)));
		$this->assign('sort_list', $sort_list);
		$this->assign('sort_id', $sort_id);
		$this->assign('goods_list', $details);
		$this->display();
	}
	
	public function url_qrcode()
	{
		$store_id = intval($_GET['store_id']);
		$now_store = $this->check_store($store_id);
		$tableid = intval($_GET['pigcms_id']);
		$now_table = D('Foodshop_table')->field(true)->where(array('id' => $tableid, 'store_id' => $store_id))->find();
		if(empty($now_table)) exit(L_('桌台不存在！'));
		$qrCon = $this->config['site_url'] . '/wap.php?g=Wap&c=Foodshop&a=scan_qcode&store_id=' . $store_id . '&table_id=' . $tableid;
		import('@.ORG.phpqrcode');
		$size = $_GET['size'] ? $_GET['size']: 10;
		QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1);
	}

	public function export(){
		$store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$param = $_POST;
		$param['type'] = 'meal';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		//$param['store_session'] = $now_store;
		 $param['store_session']['store_id'] = $store_id;
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
		die;


		$condition_where = 'Where o.mer_id = '.$now_store['mer_id'].' AND o.store_id = '.$now_store['store_id'];

		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$where['real_orderid'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=' AND o.real_orderid ='.$where['real_orderid'];
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=' AND o.orderid ='.$where['orderid'];
			} elseif ($_GET['searchtype'] == 'name') {
				$where['name'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=' AND o.name ='.$where['name'];
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['phone'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=' AND o.phone ='.$where['phone'];
			}
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';
		if ($type && $sort) {
			$order_sort .= $type . ' ' . $sort . ',';
			$order_sort .= 'order_id DESC';
		} else {
			$order_sort .= 'order_id DESC';
		}
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if($pay_type&&$pay_type!='balance'){
			$condition_where .=' AND p.pay_type ="'.$pay_type.'"';
		}else if($pay_type=='balance'){
			$condition_where .= "  AND  (`p`.`system_balance`<>0 OR `p`.`merchant_balance_pay` <> 0 )";
		}


		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$where['_string'] .=( $where['_string']?' AND ':''). " (create_time BETWEEN ".$period[0].' AND '.$period[1].")";
			$condition_where .=" AND  (o.create_time BETWEEN ".$period[0].' AND '.$period[1].")";
		}

		if ($status != -1) {
			$where['status'] = $status;
			$condition_where .= ' AND o.status ='.$status;
		}
// 		echo '<pre/>';
// 		print_r($where);die;
		//$count = D("Foodshop_order")->where($where)->count();
		$sql_cont = 'SELECT count(o.order_id) as count from pigcms_foodshop_order o LEFT JOIN (select a.* from pigcms_plat_order a,(select max(order_id) as order_id,business_id from pigcms_plat_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id) p on o.order_id  = p.business_id '.$condition_where;
		$count  = M()->query($sql_cont);
		$count = $count[0]['count'];
		set_time_limit(0);
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = L_('订单信息');
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);
		$length = ceil($count / 1000);


		for ($i = 0; $i < $length; $i++) {
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);

			$objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息',array('X1'=>($i+1))));
			$objActSheet = $objExcel->getActiveSheet();
			$objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('R')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('S')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('T')->setAutoSize(true);
			$objExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('W')->setWidth(20);

			$objActSheet->setCellValue('A1', L_('订单编号'));
			$objActSheet->setCellValue('B1', L_('商家名称'));
			$objActSheet->setCellValue('C1', L_('店铺名称'));
			$objActSheet->setCellValue('D1', L_('客户名称'));
			$objActSheet->setCellValue('E1', L_('客户电话'));
			$objActSheet->setCellValue('F1', L_('预定金'));
			$objActSheet->setCellValue('G1', L_('预定时间'));
			$objActSheet->setCellValue('H1', L_('桌台类型'));
			$objActSheet->setCellValue('I1', L_('桌台名称'));
			$objActSheet->setCellValue('J1', L_('商品名称'));
			$objActSheet->setCellValue('K1', '规格/属性');
			$objActSheet->setCellValue('L1', L_('单价'));
			$objActSheet->setCellValue('M1', L_('数量'));
			$objActSheet->setCellValue('N1', L_('单位'));
			$objActSheet->setCellValue('O1', L_('订单状态'));
			$objActSheet->setCellValue('P1', L_('订单总价'));
			$objActSheet->setCellValue('Q1', L_('余额支付'));
			$objActSheet->setCellValue('R1', L_('平台在线支付'));
			$objActSheet->setCellValue('S1', L_('商家余额支付'));
			$objActSheet->setCellValue('T1', $this->config['score_name']);
			$objActSheet->setCellValue('U1', L_('支付时间'));
			$objActSheet->setCellValue('V1', L_('支付方式'));
			$objActSheet->setCellValue('W1', L_('支付类型'));


			//$objActSheet->setCellValue('R1', '支付情况');

			$sql = 'SELECT o.*,fd.name as goods_name,fd.num as goods_num ,fd.spec as goods_spec,fd.unit as goods_unit,fd.price as goods_price,p.pay_type as pay_method,p.system_balance,p.merchant_balance_pay,p.pay_money,p.system_score_money,p.pay_time ,p.paid from '.C('DB_PREFIX').'foodshop_order o LEFT JOIN  '.C('DB_PREFIX').'plat_order  p on o.order_id  = p.business_id AND p.paid =1 LEFT JOIN '.C('DB_PREFIX').'foodshop_order_detail as fd ON fd.order_id = o.order_id '.$condition_where.' ORDER BY o.order_id DESC ' .'limit '.($i*1000).',1000';

			$list = M('')->query($sql);
			$mer_ids = $store_ids = $table_types= $tids =array();
			foreach ($list as $l) {
				!in_array($l['mer_id'],$mer_ids) && $mer_ids[] = $l['mer_id'];
				!in_array($l['store_id'], $store_ids) && $store_ids[] = $l['store_id'];
				!in_array($l['table_type'], $table_types)&& $table_types[] = $l['table_type'];
				!in_array($l['table_id'], $tids) && $tids[] = $l['table_id'];

			}


			$type_list = array();
			if ($table_types) {
				$temp_type_list = M('Foodshop_table_type')->field(true)->where(array('id' => array('in', $table_types)))->select();
				foreach ($temp_type_list as $tmp) {
					$type_list[$tmp['id']] = $tmp;
				}
			}
			$table_list = array();
			if ($tids) {
				$temp_table_list = M('Foodshop_table')->field(true)->where(array('id' => array('in', $tids)))->select();
				foreach ($temp_table_list as $temp) {
					$table_list[$temp['id']] = $temp;
				}
			}


			$store_temp = $mer_temp = array();
			if ($mer_ids) {
				$merchants = D("Merchant")->where(array('mer_id' => array('in', $mer_ids)))->select();
				foreach ($merchants as $m) {
					$mer_temp[$m['mer_id']] = $m;
				}
			}
			if ($store_ids) {
				$merchant_stores = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
				foreach ($merchant_stores as $ms) {
					$store_temp[$ms['store_id']] = $ms;
				}
			}
			foreach ($list as &$li) {
				$li['merchant_name'] = isset($mer_temp[$li['mer_id']]['name']) ? $mer_temp[$li['mer_id']]['name'] : '';
				$li['store_name'] = isset($store_temp[$li['store_id']]['name']) ? $store_temp[$li['store_id']]['name'] : '';

				$li['table_type_name'] = isset($type_list[$li['table_type']]) ? $type_list[$li['table_type']]['name'] . '(' . $type_list[$li['table_type']]['min_people'] . '-' . $type_list[$li['table_type']]['max_people'] . '人)' : '';
				$li['table_name'] = isset($table_list[$li['table_id']]) ? $table_list[$li['table_id']]['name'] : '';
				$li['show_status'] = D('Foodshop_order')->status_list[$li['status']];
			}

			$tmp_id = 0;

			$index_good =0;
			if (!empty($list)) {
				$index = 1;
				foreach ($list as $value) {
					if($tmp_id == $value['order_id']){
						if($index_good==1){
							$objActSheet->setCellValueExplicit('A' . $index, '');
							$objActSheet->setCellValueExplicit('B' . $index, '');
							$objActSheet->setCellValueExplicit('C' . $index, '');
							$objActSheet->setCellValueExplicit('D' . $index, '');
							$objActSheet->setCellValueExplicit('E' . $index, '');
							$objActSheet->setCellValueExplicit('F' . $index, '');
							$objActSheet->setCellValueExplicit('G' . $index, '');
							$objActSheet->setCellValueExplicit('H' . $index, '');
							$objActSheet->setCellValueExplicit('I' . $index, '');
							$objActSheet->setCellValueExplicit('J' . $index, $value['goods_name']);
							$objActSheet->setCellValueExplicit('K' . $index, $value['goods_spec']);
							$objActSheet->setCellValueExplicit('L' . $index, $value['goods_price']);
							$objActSheet->setCellValueExplicit('M' . $index, $value['goods_num']);
							$objActSheet->setCellValueExplicit('N' . $index, $value['goods_unit']);
							$objActSheet->setCellValueExplicit('O' . $index, '');
							$objActSheet->setCellValueExplicit('P' . $index, '');
							$objActSheet->setCellValueExplicit('Q' . $index, '');
							$objActSheet->setCellValueExplicit('R' . $index, '');
							$objActSheet->setCellValueExplicit('S' . $index, '');
							$objActSheet->setCellValueExplicit('T' . $index, '');
							$objActSheet->setCellValueExplicit('U' . $index, '');
							$objActSheet->setCellValueExplicit('V' . $index, '');
							$objActSheet->setCellValueExplicit('W' . $index, '');
						}
						if($index_good==0) {
							$objActSheet->setCellValueExplicit('A' . $index, '');
							$objActSheet->setCellValueExplicit('B' . $index, '');
							$objActSheet->setCellValueExplicit('C' . $index, '');
							$objActSheet->setCellValueExplicit('D' . $index, '');
							$objActSheet->setCellValueExplicit('E' . $index, '');
							$objActSheet->setCellValueExplicit('F' . $index, '');
							$objActSheet->setCellValueExplicit('G' . $index, '');
							$objActSheet->setCellValueExplicit('H' . $index, '');
							$objActSheet->setCellValueExplicit('I' . $index, '');
							$objActSheet->setCellValueExplicit('J' . $index, $value['goods_name']);
							$objActSheet->setCellValueExplicit('K' . $index, $value['goods_spec']);
							$objActSheet->setCellValueExplicit('L' . $index, $value['goods_price']);
							$objActSheet->setCellValueExplicit('M' . $index, $value['goods_num']);
							$objActSheet->setCellValueExplicit('N' . $index, $value['goods_unit']);
							$objActSheet->setCellValueExplicit('O' . $index, $value['show_status']);
							$objActSheet->setCellValueExplicit('P' . $index, floatval($value['total_price']));
							$objActSheet->setCellValueExplicit('Q' . $index, floatval($value['system_balance']));
							$objActSheet->setCellValueExplicit('R' . $index, floatval($value['pay_money']));
							$objActSheet->setCellValueExplicit('S' . $index, floatval($value['merchant_balance_pay']));
							$objActSheet->setCellValueExplicit('T' . $index, floatval($value['system_score_money']));
							$objActSheet->setCellValueExplicit('U' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
							$objActSheet->setCellValueExplicit('V' . $index, D('Pay')->get_pay_name($value['pay_method'], $value['is_mobile_pay'], $value['paid']));
							$objActSheet->setCellValueExplicit('W' . $index, L_('支付余额'));
							$index_good = 1;
						}

						$index++;
					}else{
						$index++;
						$objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
						$objActSheet->setCellValueExplicit('B' . $index, $value['merchant_name']);
						$objActSheet->setCellValueExplicit('C' . $index, $value['store_name']);
						$objActSheet->setCellValueExplicit('D' . $index, $value['name']);
						$objActSheet->setCellValueExplicit('E' . $index,$value['phone']);
						$objActSheet->setCellValueExplicit('F' . $index, $value['book_price']);
						$objActSheet->setCellValueExplicit('G' . $index, $value['book_time'] ? date('Y-m-d H:i:s', $value['book_time']) : '');
						$objActSheet->setCellValueExplicit('H' . $index,$value['table_type_name']);
						$objActSheet->setCellValueExplicit('I' . $index,$value['table_name']);
						$objActSheet->setCellValueExplicit('J' . $index,'');
						$objActSheet->setCellValueExplicit('K' . $index,'');
						$objActSheet->setCellValueExplicit('L' . $index,'');
						$objActSheet->setCellValueExplicit('M' . $index,'');
						$objActSheet->setCellValueExplicit('N' . $index,'');
						$objActSheet->setCellValueExplicit('O' . $index, $value['show_status']);
						$objActSheet->setCellValueExplicit('P' . $index, floatval($value['total_money']));
						$objActSheet->setCellValueExplicit('Q' . $index, floatval($value['system_balance']));
						$objActSheet->setCellValueExplicit('R' . $index, floatval($value['pay_money']));
						$objActSheet->setCellValueExplicit('S' . $index, floatval($value['merchant_balance_pay']));
						$objActSheet->setCellValueExplicit('T' . $index, floatval($value['system_score_money']));
						$objActSheet->setCellValueExplicit('U' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
						$objActSheet->setCellValueExplicit('V' . $index,D('Pay')->get_pay_name($value['pay_method'], $value['is_mobile_pay'], $value['paid']));
						$objActSheet->setCellValueExplicit('W' . $index,L_('支付定金'));
						$index++;
						$index_good = 0;
						
						//商品
						if($value['goods_name']){
							$objActSheet->setCellValueExplicit('A' . $index, '');
							$objActSheet->setCellValueExplicit('B' . $index, '');
							$objActSheet->setCellValueExplicit('C' . $index, '');
							$objActSheet->setCellValueExplicit('D' . $index, '');
							$objActSheet->setCellValueExplicit('E' . $index, '');
							$objActSheet->setCellValueExplicit('F' . $index, '');
							$objActSheet->setCellValueExplicit('G' . $index, '');
							$objActSheet->setCellValueExplicit('H' . $index, '');
							$objActSheet->setCellValueExplicit('I' . $index, '');
							$objActSheet->setCellValueExplicit('J' . $index, $value['goods_name']);
							$objActSheet->setCellValueExplicit('K' . $index, $value['goods_spec']);
							$objActSheet->setCellValueExplicit('L' . $index, $value['goods_price']);
							$objActSheet->setCellValueExplicit('M' . $index, $value['goods_num']);
							$objActSheet->setCellValueExplicit('N' . $index, $value['goods_unit']);
							$objActSheet->setCellValueExplicit('O' . $index, $value['show_status']);
							$objActSheet->setCellValueExplicit('P' . $index, floatval($value['total_price']));
							$objActSheet->setCellValueExplicit('Q' . $index, floatval($value['system_balance']));
							$objActSheet->setCellValueExplicit('R' . $index, floatval($value['pay_money']));
							$objActSheet->setCellValueExplicit('S' . $index, floatval($value['merchant_balance_pay']));
							$objActSheet->setCellValueExplicit('T' . $index, floatval($value['system_score_money']));
							$objActSheet->setCellValueExplicit('U' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
							$objActSheet->setCellValueExplicit('V' . $index, D('Pay')->get_pay_name($value['pay_method'], $value['is_mobile_pay'], $value['paid']));
							$objActSheet->setCellValueExplicit('W' . $index, L_('支付余额'));
							$index_good = 1;
							$index++;
						}
					}
					$tmp_id = $value['order_id'];

				}
			}
			sleep(2);
		}
		//输出
		$objWriter = new PHPExcel_Writer_Excel5($objExcel);
		ob_end_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
	}
	
    public function ajax_store_pic()
    {
        if ($_FILES['file']['error'] != 4) {
            $width = '900,450';
            $height = '500,250';
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
                $title = $image['title']['file'];
                $goods_image_class = new foodshopstore_image();
                $url = $goods_image_class->get_image_by_path($title, 's');
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

    public function ajax_store_activity_pic()
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
                $url = $goods_image_class->get_image_by_path($title, 's');
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

    public function ajax_delstore_pic()
    {
        $group_image_class = new foodshopstore_image();
        $group_image_class->del_image_by_path($_POST['path']);
    }
    
    
    public function foodToGoods()
    {
        
        if ($this->merchant_session['store_id']) {
            $this->error(L_('您没有这个权限'));
        }
        $source_store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : 0;
        if (empty($store_ids)) {
            $this->error(L_('请选择要同步的店铺'));
        }
        if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $source_store_id, 'have_meal' => 1))->find()) {
            if (!($shop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $source_store_id))->find())) {
                $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['meal_alias_name']));
            }
        } else {
            $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['meal_alias_name']));
        }
        
        $goods_image_class = new foodshop_goods_image();
        
        foreach ($store_ids as $store_id) {
            if ($target_store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
                if (!($target_shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find())) {
                    continue;
                }
            } else {
                continue;
            }
            
            $goods_sorts = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $source_store_id))->select();
            foreach ($goods_sorts as $sv) {
                $oldIds[$sv['sort_id']] = $sv['fid'];//id ==> fid
            }
            $listOldFidToNewFid = array();
            foreach ($goods_sorts as $sort) {
                $source_sort_id = $sort['sort_id'];
                if (!($goods_list = M('Foodshop_goods')->field(true)->where(array('store_id' => $source_store_id, 'sort_id' => $source_sort_id))->select())) {
                    continue;
                }
                //目标分类
                if ($target_sort = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id, 'sort_name' => $sort['sort_name']))->find()) {
                    $target_sort_id = $target_sort['sort_id'];
                } else {
                    $sort['store_id'] = $store_id;
                    unset($sort['sort_id']);
                    
                    $target_sort_id = M('Shop_goods_sort')->add($sort);
                }
                $listOldFidToNewFid[$source_sort_id] = $target_sort_id;//oldID ==> newID
                
                foreach ($goods_list as $goods) {
                    if(!empty($goods['image'])){
                        $tmp_pic_arr = explode(';', $goods['image']);
                        foreach ($tmp_pic_arr as $key => $value) {
                            $image_tmp = explode(',', $value);
                            if (strstr($image_tmp[0], 'sysgoods_')) {
                                continue;
                            }
                            $images = $goods_image_class->get_image_by_path($value);
                            foreach ($images as $img) {
                            	$file_domain = file_domain();
								//将oss上的图片先下来
								$file_handle = new file_handle();
								if($file_handle->check_open_oss()) {
									$file_handle->download($img);
								}
								$img = str_replace($file_domain, '', $img);
								
                                $newFile = './upload/goods/' . str_replace('/upload/foodshop_goods/', '', $img);
                                dmkdir($newFile, 0777, false);
                                $t = copy('.' . $img, $newFile);
								
								//上传至云存储
								$file_handle = new file_handle();
								$file_handle->upload($newFile);
                            }
                        }
                    }
                    if ($tmp_goods = M('Shop_goods')->field(true)->where(array('name' => $goods['name'], 'store_id' => $store_id))->find()) {
                        continue;
                    } else {
                        $source_goods_id = $goods['goods_id'];
                        unset($goods['goods_id']);
                        $goods['store_id'] = $store_id;
                        $goods['sort_id'] = $target_sort_id;
                        $goods['print_id'] = 0;
                        $goods['desc'] = htmlspecialchars($goods['desc']);
                        $target_goods_id = M('Shop_goods')->add($goods);
                        $pro_map = $spec_map = $spec_value_map = array();
                        if ($goods['is_properties']) {
                            $properties = M('Foodshop_goods_properties')->field(true)->where(array('goods_id' => $source_goods_id))->select();
                            foreach ($properties as $pro_data) {
                                $source_pro_id = $pro_data['id'];
                                unset($pro_data['id']);
                                $pro_data['goods_id'] = $target_goods_id;
                                $pro_map[$source_pro_id] = M('Shop_goods_properties')->add($pro_data);
                                $lang_model = D('Lang');
                                $pro_data['id'] = $pro_map[$source_pro_id];
                                $lang_model->add_lang_data('Shop_goods_properties',$pro_data);
                            }
                        }
                        if ($goods['spec_value']) {
                            $spec_list = M('Foodshop_goods_spec')->field(true)->where(array('goods_id' => $source_goods_id, 'store_id' => $source_store_id))->select();
                            foreach ($spec_list as $spec) {
                                $source_spec_id = $spec['id'];
                                unset($spec['id']);
                                
                                $spec['store_id'] = $store_id;
                                $spec['goods_id'] = $target_goods_id;
                                
                                if ($new_spec_id = M('Shop_goods_spec')->add($spec)) {
                                    
                                    $spec_value_list = M('Foodshop_goods_spec_value')->field(true)->where(array('sid' => $source_spec_id))->select();
                                    foreach ($spec_value_list as $spec_value) {
                                        $source_spec_value_id = $spec_value['id'];
                                        unset($spec_value['id']);
                                        $spec_value['sid'] = $new_spec_id;
                                        $spec_value_map[$source_spec_value_id] = M('Shop_goods_spec_value')->add($spec_value);
                                    }
                                }
                            }
                            
                            //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                            $spec_array = explode('#', $goods['spec_value']);
                            $target_spec_value_array = array();
                            foreach ($spec_array as $str) {
                                $row_array = explode('|', $str);
                                $spec_val_ids = explode(':', $row_array[0]);
                                $new_ids = array();
                                foreach ($spec_val_ids as $tid) {
                                    $new_ids[] = $spec_value_map[$tid];
                                }
                                $row_array[0] = implode(':', $new_ids);
                                if (count($row_array) > 2 && $row_array[2] && strstr($row_array[2], '=')) {
                                    $pro_str_ids = explode(':', $row_array[2]);
                                    $new_pro_ids = array();
                                    foreach ($pro_str_ids as $pstr) {
                                        $v_k_a = explode('=', $pstr);
                                        $new_pro_ids[] = $pro_map[$v_k_a[0]] . '=' . $v_k_a[1];
                                    }
                                    $row_array[2] = implode(':', $new_pro_ids);
                                }
                                $target_spec_value_array[] = implode('|', $row_array);
                            }
                            M('Shop_goods')->where(array('goods_id' => $target_goods_id))->save(array('spec_value' => implode('#', $target_spec_value_array)));
                        }
                    }
                }
            }
            $this->success(L_('克隆完成'));
        }
    }
    
    public function shop()
    {
        if ($this->merchant_session['store_id']) {
            $this->error(L_('您没有这个权限'));
        }
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
            if ($shop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $store_id))->find()) {
                
            } else {
                $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['meal_alias_name']));
            }
        } else {
            $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['meal_alias_name']));
        }
        
        $sql = "SELECT s.store_id, s.name FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON sh.store_id=s.store_id WHERE s.have_shop=1 AND s.status=1 AND s.mer_id={$this->merchant_session['mer_id']}";
        $res = D()->query($sql);
        $this->assign('stores', $res);
        $this->assign('store_id', $store_id);
        $this->display();
    }

    /**
	 * 新分类 使用快店分类
	 */
	public function goods_sort_new()
	{
        $fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
		$now_store = $this->check_store($store_id);
		$this->assign('now_store', $now_store);
		
		$shopGoodsSortDB = D('Shop_goods_sort');
        $sortList = array();
        if ($sort = D(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $now_store['store_id'], 'sort_id' => $fid))->find()) {
            $ids = $shopGoodsSortDB->getIds($fid, $now_store['store_id']);
            if (count($ids) > 1) {
                $sortList = D(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $now_store['store_id'], 'sort_id' => array('in', $ids)))->order('sort_id ASC')->select();
            } else {
                $sortList = array($sort);
            }
        } else {
            $fid = 0;
        }
        $this->assign('now_store', $now_store);
        $this->assign('fid', $fid);
        $this->assign('sortList', $sortList);

        $where = array('store_id' => $now_store['store_id']);
        $where['fid'] = $fid;

        $sort_list = D(_view('Shop_goods_sort'))->field(true)->where($where)->order('`sort` DESC,`sort_id` ASC')->select();
        foreach ($sort_list as &$value) {
            if ($value['week'] != null) {
                $week_arr = explode(',', $value['week']);
                $week_str = '';
                foreach ($week_arr as $k => $v) {
                    $week_str .= $this->get_week($v) . ' ';
                }
                $value['week_str'] = $week_str;
            }
        }
        $this->assign('sort_list', $sort_list);
        $this->display();
	}


	/* 分类页商品筛选 */
	public function sort_goods_search(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);

		$database_goods = D(_view('Shop_goods'));
		$database_foodshop_goods_new = D('Foodshop_goods_new');
		$condition_goods['store_id'] = $now_store['store_id'];

		/*增加搜索类型*/
		$search_type=I('get.search_type','','trim');
		if($_GET['keyword']){
			if($search_type=='number'){
				$condition_goods['_string']="s.number like '%".$_GET['keyword']."%' or locate('".$_GET['keyword']."',spec_value)>0";
			}else{
				$condition_goods['name'] = $_GET['keyword'];
			}
		}
		$goods_list = $database_foodshop_goods_new->get_goods_list($condition_goods);
	
		$this->assign('goods_list', $goods_list['list']);
		$this->assign('pagebar', $goods_list['pagebar']);

		$this->display();
	}

    /* 新菜品管理 */
	public function goods_list_new()
	{
		$now_sort = $this->check_sort_new($_GET['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);

		$database_goods = D(_view('Shop_goods'));
		$database_foodshop_goods_new = D('Foodshop_goods_new');
		$condition_goods['sort_id'] = $now_sort['sort_id'];

		/*增加搜索类型*/
		$search_type=I('get.search_type','','trim');
		if($_GET['keyword']){
			if($search_type=='number'){
				$condition_goods['_string']="s.number like '%".$_GET['keyword']."%' or locate('".$_GET['keyword']."',spec_value)>0";
			}else{
				$condition_goods['name'] = $_GET['keyword'];
			}
		}
		$goods_list = $database_foodshop_goods_new->get_goods_list($condition_goods);

		$plist = array();
		$prints = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($prints as $l) {
			if ($l['is_main']) {
				$l['name'] .= '(主打印机)';
			} else {
				$l['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
			}
			$plist[$l['pigcms_id']] = $l;
		}
		$today = date('Ymd');
		$now_time = time();
		foreach ($goods_list['list'] as &$rl) {
			$rl['print_name'] = isset($plist[$rl['print_id']]['name']) ? $plist[$rl['print_id']]['name'] : '';
		}
		$this->assign('goods_list', $goods_list['list']);
		$this->assign('pagebar', $goods_list['pagebar']);
		$this->display();
	}

	/* 编辑商品 */
    public function goods_edit_new()
    {
    	if ($_GET['goods_id']) {
        	$now_goods = $this->check_goods_new($_GET['goods_id']);
    	}
        $now_sort = $this->check_sort_new($_GET['sort_id']);
        $now_store = $this->check_store($now_sort['store_id']);
        if (IS_POST) {
            $error_tips = '';
            if ($_POST['price'] === '' ) {
                $error_tips .= L_('商品价格可以设置为0，但是必填！') . '<br/>';
            }
            if ($_POST['price'] < 0 ) {
                $error_tips .= L_('商品价格必须大于或等于0！') . '<br/>';
            }
            if ($_POST['old_price']>0 && $_POST['old_price'] < $_POST['price'] ) {
                $error_tips .= L_('商品原价不能小于现价！') . '<br/>';
            }

			if (empty($error_tips)) {
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
				if ($_POST['seckill_type']) {
				    $_POST['seckill_open_time'] = strtotime(date('Y-m-d ') . $_POST['seckill_open_time'] . ":00");
				    $_POST['seckill_close_time'] = strtotime(date('Y-m-d ') . $_POST['seckill_close_time'] . ":00");
				} else {
				    $_POST['seckill_open_time'] = strtotime($_POST['seckill_open_datetime'] . ":00");
				    $_POST['seckill_close_time'] = strtotime($_POST['seckill_close_datetime'] . ":00");
				}
				if ($goods_id = D('Shop_goods')->save_post_form_foodshop($_POST, $now_store['store_id'])) {

					$this->success(L_('保存成功！'), U('Foodshop/goods_list_new', array('sort_id' => $now_sort['sort_id'],'page' => $_POST['page'])));
					die;
					$ok_tips = L_('保存成功！');
				} else {
					$this->error(L_('保存失败！请重试！'), U('Foodshop/goods_list_new', array('sort_id' => $now_sort['sort_id'],'page' => $_POST['page'])));
					die;
					$error_tips = L_('保存失败！请重试。');
				}
			} else {
				$return = $this->format_data($_POST);
				$_POST['json'] = isset($return['json']) ? json_encode($return['json']) : '';
				$_POST['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
				$_POST['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
				$_POST['list'] = isset($return['list']) ? $return['list'] : '';
				$this->assign('now_goods', $_POST);
			}

			S('shop_goods_by_storeid_' . $now_store['store_id'],null);

			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}

		$print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($print_list as &$l) {
			if ($l['is_main']) {
				$l['name'] .= '(主打印机)';
			} else {
				$l['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
			}
		}
		$this->assign('print_list', $print_list);
		$category_list = D('Goods_category')->get_list();
		$this->assign('category_list', json_encode($category_list));

		$sort_list = D('Shop_goods_sort')->lists($now_store['store_id'], false);
		$this->assign('sort_list', json_encode($sort_list));
		$ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
		$this->assign('select_ids', json_encode($ids));
		$this->assign('now_goods', $now_goods);
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
		$this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());

		// 商品分组
        $shop_goods_group_list = M('Shop_goods_group')->where(['store_id' => $now_goods['store_id']])->select();
        $bind_group_list = M('Shop_goods_group_list')->field('l.id,l.group_id,g.name')->join(' as l left join '.C('DB_PREFIX').'shop_goods_group as g on l.group_id = g.id')->where(['l.goods_id' => $_GET['goods_id']])->select();
        foreach ($bind_group_list as $key => $value) {
        	$group_ids[] =  $value['group_id'];
        }
		$this->assign('group_ids',implode(',',$group_ids));
		$this->assign('shop_goods_group_list', $shop_goods_group_list);
		$this->assign('bind_group_list', $bind_group_list);

		// 附属菜
		$piece_list = M(_view('Shop_subsidiary_piece'))->where("is_delete = 0 AND goods_id = " . $_GET['goods_id'])->order('id ASC')->select();
        foreach ($piece_list as $key => $value) {
            $goods = M('Shop_subsidiary_piece_goods')->where("is_delete = 0 AND piere_id = " . $value['id'])->order('sort DESC, id ASC')->select();
            foreach ($goods as &$v) {
                $v['spec_value'] = M('Shop_goods')->where("goods_id = " . $v['goods_id'])->getField('spec_value');
                $parent = M(_view('Shop_goods'))->where("goods_id = " . $v['goods_id'])->find();
                $v['name'] = $parent['name'];
            }
            $piece_list[$key]['goods'] = $goods;
        }
        if(!$piece_list){
            $piece_list = array();
        }
        $now_goods['piece_list'] = $piece_list;

		$this->assign('now_goods', $now_goods);
		$this->display();
	}

	/* 检测分类存在 */
	protected function check_sort_new($sort_id)
	{
		$database_goods_sort = D('Shop_goods_sort');
		$condition_goods_sort['sort_id'] = $sort_id;
		$now_sort = D(_view('Shop_goods_sort'))->field(true)->where($condition_goods_sort)->find();
		if (empty($now_sort)) {
			$this->error(L_('分类不存在！'));
		}
		if(!empty($now_sort['image'])){
			$sort_image_class = new goods_sort_image();
			$now_sort['see_image'] = $sort_image_class->get_image_by_path($now_sort['image'],$this->config['site_url'],'s');
		}
		if ($now_sort['week'] != null) {
			$now_sort['week'] = explode(',', $now_sort['week']);
		}
		return $now_sort;
	}

	/* 检测商品存在 */
	protected function check_goods_new($goods_id,$is_del=0)
	{
		$database_foodshop_goods_new = D('Foodshop_goods_new');
		$database_shop_goods = D(_view('Shop_goods'));
		$condition_goods['goods_id'] = $goods_id;
		$now_goods = $database_foodshop_goods_new->field(true)->where($condition_goods)->find();
		if(empty($now_goods)){
			$this->error(L_('商品不存在！'));
		}
		$act_info=D('Shop_goods')->get_good_act_info($now_goods['goods_id']);
		
		if(!empty($now_goods['image'])){
			$goods_image_class = new goods_image();
			$tmp_pic_arr = explode(';', $now_goods['image']);
			foreach ($tmp_pic_arr as $key => $value) {
			    if('http' === substr($value,0,4)){
					$now_goods['pic_arr'][$key]['title'] = $value;
					$now_goods['pic_arr'][$key]['url'] = $value;
				}else{
                    $now_goods['pic_arr'][$key]['title'] = $value;
                    $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                }
			}
		}

		$condition_goods['goods_id'] = $now_goods['goods_id'];
		$now_shop_goods = $database_shop_goods->field(true)->where($condition_goods)->find();

		$return = $database_shop_goods->format_spec_value($now_shop_goods['spec_value'], $now_goods['goods_id'],1,0,null,$now_shop_goods['foodshop_spec_value']);
		$now_goods['name'] = $now_shop_goods['name'];
		$now_goods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
		$now_goods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
		$now_goods['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
		$now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
		$now_goods['list'] = isset($return['list']) ? $return['list'] : '';
		foreach ($now_goods['list'] as $key => &$value) {
			$value['key'] = $key;
		}

		/*处理图片上传阿里云导致详情中的图片不展示*/
		$src = '<img src="'.file_domain().'/';
		$now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
		$now_goods['des'] = str_replace('<img alt="" src="/', $src, $now_goods['des']);
		$now_goods['sort_id'] = $now_shop_goods['sort_id'];

		return $now_goods;
	}

	public function shop_fitment_banner_edit(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
		$subject_info['banner'] = unserialize($subject_info['banner']);
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['subject_good_id']);
		$goodArr = M('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
	}

	public function shop_fitment_banner(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
		$subject_info['banner'] = unserialize($subject_info['banner']);
		// var_dump($subject_info);
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['subject_good_id']);
		$goodArr = M('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
	}

	public function shop_fitment_banner_save(){ 
		$now_store = $this->check_store($_POST['store_id']);

		$condition['store_id']		= $_POST['store_id'];
		$id = $_POST['id'];

		if (mb_strlen($_POST['name'])>20) {
			$this->error(L_('名称不能超过20个字'));
		}
		$data['last_time']			= time();
		if(M('Merchant_store_foodshop')->where($condition)->data($data)->save()){
			$condition_data['store_id'] = $_POST['store_id'];
			$data_data['last_time']			= time();
			if($subject_info = M('Merchant_store_foodshop_data')->where($condition)->find()){
				$banner = unserialize($subject_info['banner']);
				if ($banner) {
					if ($banner[$id]) {
						// 修改
						$banner[$id]['name'] = $_POST['name'];
						$banner[$id]['pic'] = $_POST['pic'];
						$banner[$id]['url'] = $_POST['url'];
						$banner[$id]['status'] = $_POST['status'];
					}else{
						// 新增
						$count = count($banner);
						$banner[$count]['name'] = $_POST['name'];
						$banner[$count]['pic'] = $_POST['pic'];
						$banner[$count]['url'] = $_POST['url'];
						$banner[$count]['status'] = $_POST['status'];
					}
				}else{
					// 新增
					$banner = array();
					$banner[0]['name'] = $_POST['name'];
					$banner[0]['pic'] = $_POST['pic'];
					$banner[0]['url'] = $_POST['url'];
					$banner[0]['status'] = $_POST['status'];
				}

				$data_data['banner']	= serialize($banner);
				$data_data['last_time']			= time();
				if(M('Merchant_store_foodshop_data')->where($condition_data)->data($data_data)->save()){
					$this->success(L_('保存成功'),U('shop_fitment_banner',array('store_id'=>$_POST['store_id'])));
				}else{
					$this->error(L_('保存失败！'));
				}
			}else{
				// 新增
				$banner = array();
				$banner[0]['name'] = $_POST['name'];
				$banner[0]['pic'] = $_POST['pic'];
				$banner[0]['url'] = $_POST['url'];
				$banner[0]['status'] = $_POST['status'];

				$data_data['banner']	= serialize($banner);
				$data_data['store_id'] = $_POST['store_id'];
				$data_data['last_time']			= time();
				if(M('Merchant_store_foodshop_data')->data($data_data)->add()){
					$this->success(L_('保存成功'));
				}else{
					$this->error(L_('保存失败！'));
				}
			}
		}else{
			$this->error(L_('保存失败！'));
		}
	}

	public function shop_fitment_banner_del(){
		$now_store = $this->check_store($_GET['store_id']);
		$condition['store_id']	= $_GET['store_id'];
		$id	= $_GET['id'];

		$subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
		$subject_info['banner'] = unserialize($subject_info['banner']);
		unset($subject_info['banner'][$id]);
		$data_data['banner']  = serialize(array_values($subject_info['banner']));
		$data_data['last_time']			= time();
		if(M('Merchant_store_foodshop_data')->where($condition)->data($data_data)->save()){
			$this->success(L_('删除成功'),U('shop_fitment_banner',array('store_id'=>$_GET['store_id'])));
		}else{
			fdump(M('Merchant_store_foodshop_data')->_sql(),'11');
			$this->error(L_('删除失败！'));
		}
	}

	// 今日特惠
	public function shop_fitment_preferential(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['preferential_good_id']);
        $goodArr = M('Foodshop_goods_new')->field('f.goods_id,s.name')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where(array('f.goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
	}

	
	/**
     * v20 餐饮2.0 页面装修页
     * User: chenxiang
     * Date: 2020/9/4 11:31
     */
	public function foodshop_fitment(){
		$condition_data = ['store_id' => $_GET['store_id']];
		$subject_info = M('Merchant_store_foodshop_data')->where($condition_data)->find();
		$this->assign('subject_info', $subject_info);
		
	    $this->display();
	}


    /**
     * v20 餐饮2.0 今日特惠
     * User: chenxiang
     * Date: 2020/9/4 11:31
     */
    public function foodshop_fitment_preferential(){
        $now_store = $this->check_store($_GET['store_id']);
        $this->assign('now_store',$now_store);

        $condition['store_id']	= $_GET['store_id'];
        $subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
        $this->assign('subject_info',$subject_info);

        $goodIdArr = explode(',',$subject_info['preferential_good_id']);
        $goodArr = M('Foodshop_goods_library')->field('f.goods_id,s.name')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where(array('f.goods_id'=>array('in',$goodIdArr)))->select();
        $this->assign('goodArr',$goodArr);

        $this->display();
    }

    /**
     * v20 餐饮2.0 今日特惠 save方法
     * User: chenxiang
     * Date: 2020/9/4 11:32
     */
    public function foodshop_fitment_preferential_save(){
        $condition['store_id']		= $_POST['store_id'];
        $data['last_time']			= time();
        if(M('Merchant_store_foodshop')->where($condition)->data($data)->save()!==false){
            $condition_data['store_id'] = $_POST['store_id'];
            $data_data['preferential_name']	= $_POST['preferential_name'];
            $data_data['preferential_good_id']	= implode(',',$_POST['good_id']);
            $data_data['preferential_status'] = $_POST['preferential_status'];
            if(!$data_data['preferential_good_id']){
                $data_data['preferential_good_id'] = array();
            }
            $data_data['last_time']			= time();
            if(M('Merchant_store_foodshop_data')->where($condition)->find()){
                if(M('Merchant_store_foodshop_data')->where($condition_data)->data($data_data)->save()){
                    $this->success(L_('保存成功！'));
                }else{
                    $this->error(L_('保存失败！'));
                }
            }else{
                $data_data['store_id'] = $_POST['store_id'];
                if(M('Merchant_store_foodshop_data')->data($data_data)->add()){
                    $this->success(L_('保存成功。'));
                }else{
                    $this->error(L_('保存失败。'));
                }
            }
        }else{
            $this->error(L_('保存失败!.'));
        }
    }

	public function shop_fitment_preferential_save(){
		$condition['store_id']		= $_POST['store_id'];
		$data['last_time']			= time();
		if(M('Merchant_store_foodshop')->where($condition)->data($data)->save()!==false){
			$condition_data['store_id'] = $_POST['store_id'];
			$data_data['preferential_name']	= $_POST['preferential_name'];
			$data_data['preferential_good_id']	= implode(',',$_POST['good_id']);
			$data_data['preferential_status'] = $_POST['preferential_status'];
			if(!$data_data['preferential_good_id']){
				$data_data['preferential_good_id'] = array();
			}
			$data_data['last_time']			= time();
			if(M('Merchant_store_foodshop_data')->where($condition)->find()){
				if(M('Merchant_store_foodshop_data')->where($condition_data)->data($data_data)->save()){
					$this->success(L_('保存成功！'));
				}else{
					$this->error(L_('保存失败！'));
				}
			}else{
				$data_data['store_id'] = $_POST['store_id'];
				if(M('Merchant_store_foodshop_data')->data($data_data)->add()){
					$this->success(L_('保存成功。'));
				}else{
					$this->error(L_('保存失败。'));
				}
			}
		}else{
			$this->error(L_('保存失败!.'));
		}
	}

	// 特色分类热销
	public function shop_fitment_category_hot(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['cat_hot_good_id']);
        $goodArr = M('Foodshop_goods_new')->field('f.goods_id,s.name')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where(array('f.goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
	}

    /**
     *  特色分类热销 v20 餐饮2.0
     * User: chenxiang
     * Date: 2020/9/5 17:24
     */
    public function foodshop_fitment_category_hot(){
        $now_store = $this->check_store($_GET['store_id']);
        $this->assign('now_store',$now_store);

        $condition['store_id']	= $_GET['store_id'];
        $subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
        $this->assign('subject_info',$subject_info);

        $goodIdArr = explode(',',$subject_info['cat_hot_good_id']);
        $goodArr = M('Foodshop_goods_library')->field('f.goods_id,s.name')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where(array('f.goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

        $this->display();
    }

    /**
     * 热销 save v20 餐饮2.0
     * User: chenxiang
     * Date: 2020/9/5 17:25
     */
    public function foodshop_fitment_category_hot_save(){
        $now_store = $this->check_store($_POST['store_id']);

        $condition_data['store_id'] = $_POST['store_id'];
        $data_data['cat_hot_name']	= $_POST['cat_hot_name'];
        $data_data['cat_hot_desc']	= $_POST['cat_hot_desc'];
        $data_data['cat_hot_sort']	= $_POST['cat_hot_sort'];
        $data_data['cat_hot_status']	= $_POST['cat_hot_status'];
        $data_data['cat_hot_good_id']	= implode(',',$_POST['good_id']);
        if(!$data_data['cat_hot_good_id']){
            $data_data['cat_hot_good_id'] = '';
        }
        $data_data['last_time']			= time();
        if(M('Merchant_store_foodshop_data')->where($condition_data)->find()){
            if(M('Merchant_store_foodshop_data')->where($condition_data)->data($data_data)->save()){
                $this->success(L_('保存成功！'));
            }else{
                $this->error(L_('保存失败！'));
            }
        }else{
            $data_data['store_id'] = $_POST['store_id'];
            if(M('Merchant_store_foodshop_data')->data($data_data)->add()){
                $this->success(L_('保存成功。'));
            }else{
                $this->error(L_('保存失败。'));
            }
        }
    }

	public function shop_fitment_category_hot_save(){
		$now_store = $this->check_store($_POST['store_id']);

		$condition_data['store_id'] = $_POST['store_id'];
		$data_data['cat_hot_name']	= $_POST['cat_hot_name'];
		$data_data['cat_hot_desc']	= $_POST['cat_hot_desc'];
		$data_data['cat_hot_sort']	= $_POST['cat_hot_sort'];
		$data_data['cat_hot_status']	= $_POST['cat_hot_status'];
		$data_data['cat_hot_good_id']	= implode(',',$_POST['good_id']);
		if(!$data_data['cat_hot_good_id']){
			$data_data['cat_hot_good_id'] = '';
		}
		$data_data['last_time']			= time();
		if(M('Merchant_store_foodshop_data')->where($condition_data)->find()){
			if(M('Merchant_store_foodshop_data')->where($condition_data)->data($data_data)->save()){
				$this->success(L_('保存成功！'));
			}else{
				$this->error(L_('保存失败！'));
			}
		}else{
			$data_data['store_id'] = $_POST['store_id'];
			if(M('Merchant_store_foodshop_data')->data($data_data)->add()){
				$this->success(L_('保存成功。'));
			}else{
				$this->error(L_('保存失败。'));
			}
		}
	}

	// 特色分类优惠
	public function shop_fitment_category_discount(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['cat_discount_good_id']);
        $goodArr = M('Foodshop_goods_new')->field('f.goods_id,s.name')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where(array('f.goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
	}

    /**
     *  特色分类优惠 v20 餐饮2.0
     * User: chenxiang
     * Date: 2020/9/5 17:40
     */
    public function foodshop_fitment_category_discount(){
        $now_store = $this->check_store($_GET['store_id']);
        $this->assign('now_store',$now_store);

        $condition['store_id']	= $_GET['store_id'];
        $subject_info = M('Merchant_store_foodshop_data')->where($condition)->find();
        $this->assign('subject_info',$subject_info);

        $goodIdArr = explode(',',$subject_info['cat_discount_good_id']);
        $goodArr = M('Foodshop_goods_library')->field('f.goods_id,s.name')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where(array('f.goods_id'=>array('in',$goodIdArr)))->select();
        $this->assign('goodArr',$goodArr);

        $this->display();
    }

    /**
     * 特色分类优惠 save  v20 餐饮2.0
     * User: chenxiang
     * Date: 2020/9/5 17:41
     */
    public function foodshop_fitment_category_discount_save(){
        $now_store = $this->check_store($_POST['store_id']);

        $condition_data['store_id'] = $_POST['store_id'];
        $data_data['cat_discount_name']	= $_POST['cat_discount_name'];
        $data_data['cat_discount_desc']	= $_POST['cat_discount_desc'];
        $data_data['cat_discount_sort']	= $_POST['cat_discount_sort'];
        $data_data['cat_discount_status']	= $_POST['cat_discount_status'];
        $data_data['cat_discount_good_id']	= implode(',',$_POST['good_id']);
        if(!$data_data['cat_discount_good_id']){
            $data_data['cat_discount_good_id'] = '';
        }
        $data_data['last_time']			= time();
        if(M('Merchant_store_foodshop_data')->where($condition_data)->find()){
            if(M('Merchant_store_foodshop_data')->where($condition_data)->data($data_data)->save()){
                $this->success(L_('保存成功！'));
            }else{
                $this->error(L_('保存失败！'));
            }
        }else{
            $data_data['store_id'] = $_POST['store_id'];
            if(M('Merchant_store_foodshop_data')->data($data_data)->add()){
                $this->success(L_('保存成功。'));
            }else{
                $this->error(L_('保存失败。'));
            }
        }
    }

	public function shop_fitment_category_discount_save(){
		$now_store = $this->check_store($_POST['store_id']);

		$condition_data['store_id'] = $_POST['store_id'];
		$data_data['cat_discount_name']	= $_POST['cat_discount_name'];
		$data_data['cat_discount_desc']	= $_POST['cat_discount_desc'];
		$data_data['cat_discount_sort']	= $_POST['cat_discount_sort'];
		$data_data['cat_discount_status']	= $_POST['cat_discount_status'];
		$data_data['cat_discount_good_id']	= implode(',',$_POST['good_id']);
		if(!$data_data['cat_discount_good_id']){
			$data_data['cat_discount_good_id'] = '';
		}
		$data_data['last_time']			= time();
		if(M('Merchant_store_foodshop_data')->where($condition_data)->find()){
			if(M('Merchant_store_foodshop_data')->where($condition_data)->data($data_data)->save()){
				$this->success(L_('保存成功！'));
			}else{
				$this->error(L_('保存失败！'));
			}
		}else{
			$data_data['store_id'] = $_POST['store_id'];
			if(M('Merchant_store_foodshop_data')->data($data_data)->add()){
				$this->success(L_('保存成功。'));
			}else{
				$this->error(L_('保存失败。'));
			}
		}
	}

	//餐饮商品状态修改
    public function goods_status_new(){
        $now_goods = $this->check_goods_new(intval($_POST['id']));
		$now_sort = $this->check_sort_new($now_goods['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);
		
		$database_goods = D('Foodshop_goods_new');
		$condition_goods['goods_id'] = $now_goods['goods_id'];
		$data_goods['status'] = $_POST['type'] == 'open' ? '1' : '0';
		if($database_goods->where($condition_goods)->data($data_goods)->save()){
			exit('1');
		}else{
			exit;
		}

    }

	//删除餐饮商品
    public function goods_del_new(){
        $now_goods = $this->check_goods_new(intval($_GET['goods_id']));

		$database_goods_new = D('Foodshop_goods_new');
		$database_shop_goods = M('Shop_goods');
		$condition_goods['goods_id'] = $now_goods['goods_id'];
		if ($database_goods_new->where($condition_goods)->delete()) {
			$data['foodshop_spec_value'] = ''; 
			$database_shop_goods->where($condition_goods)->save($data);
			$this->success(L_('删除成功！'));
		}else{
			$this->error(L_('删除失败！请检查后重试。'));
		}

    }
}