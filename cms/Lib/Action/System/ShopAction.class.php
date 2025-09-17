<?php
/*
 * 订餐管理
 *
 * @  BuildTime  2014/11/18 11:21
 */

class ShopAction extends BaseAction
{

	public function config(){
		redirect(U('Config/index',array('galias'=>'shop','header'=>'Shop/header')));
	}

    public function index()
    {
    	$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_shop_category = D(('shop_category'));
		$category = $database_shop_category->field(true)->where(array('cat_id' => $parentid))->find();
		$category_list = $database_shop_category->field(true)->where(array('cat_fid' => $parentid))->order('`cat_sort` DESC,`cat_id` ASC')->select();
		$this->assign('category', $category);
		$this->assign('category_list', $category_list);
		$this->assign('parentid', $parentid);
		$this->display();
    }

    // 页面装修
	public function cat_renovation()
	{
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_shop_category = D(('shop_category'));
		$category = $database_shop_category->field(true)->where(array('cat_id' => $parentid))->find();
		$shop_decoration_bgcolor = D(('shop_decoration_color'))->field(true)->where(array('cat_id' => $parentid,'type_id' => 1))->find();
		$shop_decoration_text_icon = D(('shop_decoration_color'))->field(true)->where(array('cat_id' => $parentid,'type_id' => 2))->find();
        empty($shop_decoration_bgcolor) ? $bgcolor = 'rgb(0,204,153)' : $bgcolor = $shop_decoration_bgcolor['value'];
        empty($shop_decoration_text_icon) ? $textcolor = 'rgb(255,255,255)' : $textcolor = $shop_decoration_text_icon['value'];
		$this->assign('shop_decoration_bgcolor', $bgcolor);
		$this->assign('shop_decoration_text_icon', $textcolor);
		$this->assign('category', $category);
		$this->assign('parentid', $parentid);
		$this->display();
	}


    // 更换背景颜色、文本与图标颜色
    public function change_footer_color()
    {
        $name = $_POST['name'];
        $cat_id = $_POST['parentid'];
        $condition = '';
        // 背景颜色
        if ($name == 'shop_decoration_bgcolor') {
            $condition = $name;
            $value = $_POST['color'];
            $cat_id = $cat_id;
            $type_id = 1;
        }
        // 文本与图标颜色
        if ($name == 'shop_decoration_text_icon') {
            $condition = $name;
            $value = $_POST['color'];
            $cat_id = $cat_id;
            $type_id = 2;
        }

        if (empty($condition)) {
            $this->error('该配置项不存在');
        }
        $count = D('Shop_decoration_color')->where(array('cat_id' => $cat_id, 'type_id' => $type_id))->count();
        if ($count != 0) {
            if (D('Shop_decoration_color')->where(array('cat_id' => $cat_id, 'type_id' => $type_id))->save(array('value' => $value, 'edit_time' => time()))) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        } else {
            if (D('Shop_decoration_color')->add(array('cat_id' => $cat_id, 'type_id' => $type_id, 'value' => $value, 'add_time' => time(), 'edit_time' => time(), 'status' => 1))) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
    }

    // 店铺列表展示热销商品按钮
    public function change_plat_news_show()
    {
        D('Config')->remove_cache();
        if (isset($this->config['shop_decoration_hot_goods'])) {
            if (D('Config')->where(array('name' => 'shop_decoration_hot_goods'))->save(array('value' => $_POST['value']))) {
				//删除缓存
				D('Config')->clearDataCacheDir('system_config');
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        } else {
            if (D('Config')->add(array('name' => 'shop_decoration_hot_goods', 'value' => $_POST['value'], 'info' => '店铺列表展示热销商品', 'gid' => 0, 'status' => 1))) {
				//删除缓存
				D('Config')->clearDataCacheDir('system_config');
                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        }
    }

    //装修热门关键词
    public function renovation_hot_mall(){
        $database_renovation_hot = D('Shop_decoration_hot_mall');
        $renovation_hot_list = $database_renovation_hot->field(true)->where(array('shop_id'=>$_GET['parentid']))->order('`sort` DESC,`hottest` DESC,`id` ASC')->select();
        $this->assign('renovation_hot_list',$renovation_hot_list);
        $this->display();
    }

    //装修热门关键词处理
    public function renovation_hot_mall_modify(){
        if(IS_POST){
            if($_GET['parentid']){
                $parentid = $_GET['parentid'];
                $namearray = array_filter($_POST);
                $database_renovation_hot = D('Shop_decoration_hot_mall');
                $count= $database_renovation_hot->where(array('shop_id'=>$parentid))->count();
                if($count){
                    if($database_renovation_hot->where(array('shop_id'=>$parentid))->delete()){}else{
                        $this->frame_submit_tips(0, '添加失败！请重试~');
                    }
                }
                $time = time();
                foreach ($namearray as $val){
                    $data['name'] = $val;
                    $data['shop_id'] = $parentid;
                    $data['add_time'] = $time;
                    $res=$database_renovation_hot->data($data)->add();
                }
                if($res){
                    $this->frame_submit_tips(1, '添加成功！');
                }else{
                    $this->frame_submit_tips(0, '添加失败！请重试~');
                }
            }else{
                $this->frame_submit_tips(0, '非法提交,请重新提交~');
            }
        }else{
            $this->frame_submit_tips(0, '非法提交,请重新提交~');
        }
    }

	public function cat_add()
	{
		$this->assign('bg_color','#F3F3F3');
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$this->assign('parentid', $parentid);
		$this->display();
	}

	public function cat_modify()
    {
        if (IS_POST) {
            if ($_FILES) {
                $image = D('Image')->handle($this->system_session['id'], 'system/shop', 0, array('size' => 10));
                if (!$image['error']) {
                    $_POST = array_merge($_POST, str_replace('/upload/system/shop/', '', $image['url']));
                }
            }
            $database_shop_category = D((_view('Shop_category')));
            if ($database_shop_category->field('cat_id')->where(array('cat_url' => $_POST['cat_url']))->find()) {
                $this->error('短标记(url):' . $_POST['cat_url'] . ',已存在');
            }
            if ($cat_id = $database_shop_category->data($_POST)->add()) {
                //多语言表增加数据
                $lang_model = D('Lang');
                $_POST['cat_id'] = $cat_id;
                $lang_model->add_lang_data('Shop_category', $_POST);
                if ($_POST['cat_pic']) {
                    D('Image')->update_table_id('/upload/system/shop/' . $_POST['cat_pic'], $_POST['cat_id'], 'shop_category');
                }
                $this->frame_submit_tips(1, '编辑成功！');

                //时间展示
                if (isset($_POST['effect_date_type'])) {
                    if ($_POST['effect_date_type'] == 1) {
                        $_POST['start_date'] = $_POST['end_date'] = null;
                        $_POST['week'] = [];
                    }
                    if ($_POST['effect_time_type'] == 1) {
                        $_POST['start_time'] = $_POST['end_time'] = null;
                    }
                    $_POST['week'] = implode(',', $_POST['week']);
                }

                if ($database_shop_category->data($_POST)->add()) {
                    $this->success('添加成功！');
                } else {
                    $this->frame_submit_tips(0, '编辑失败！请重试~');
                }
            } else {
                $this->frame_submit_tips(0, '非法提交,请重新提交~');
            }
        }
    }

	public function cat_edit()
	{
		$this->assign('bg_color','#F3F3F3');

		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_shop_category = M('Shop_category');
		$condition_now_shop_category['cat_id'] = intval($_GET['cat_id']);
		$now_category = $database_shop_category->field(true)->where($condition_now_shop_category)->find();
		if (empty($now_category)) {
			$this->frame_error_tips('没有找到该分类信息！');
		}
		$this->assign('parentid', $parentid);
		$this->assign('now_category', $now_category);
		$this->display();
	}

	public function cat_amend()
    {
        if (IS_POST) {
            if ($_FILES) {
                $image = D('Image')->handle($this->system_session['id'], 'system/shop', 0, array('size' => 10));
                if (!$image['error']) {
                    $_POST = array_merge($_POST, str_replace('/upload/system/shop/', '', $image['url']));
                }
            }
            $database_shop_category = M('Shop_category');
            $where = array('cat_id' => $_POST['cat_id']);
            unset($_POST['cat_id']);
            if ($database_shop_category->field('cat_id')->where(array('cat_url' => $_POST['cat_url'], 'cat_id' => array('neq', $where['cat_id'])))->find()) {
                $this->error('短标记(url):' . $_POST['cat_url'] . ',已存在');
            }
            if ($database_shop_category->where($where)->save($_POST)) {
                //多语言表修改数据
                $lang_model = D('Lang');
                //$_POST['cat_id'] = $_POST['cat_id'];
                $lang_model->edit_lang_data('Shop_category', $_POST);
                if ($_POST['cat_pic']) {
                    D('Image')->update_table_id('/upload/system/shop/' . $_POST['cat_pic'], $_POST['cat_id'], 'shop_category');
                }
                $this->frame_submit_tips(1, '编辑成功！');
                //时间展示
                if (isset($_POST['effect_date_type'])) {
                    if ($_POST['effect_date_type'] == 1) {
                        $_POST['start_date'] = $_POST['end_date'] = null;
                        $_POST['week'] = [];
                    }
                    if ($_POST['effect_time_type'] == 1) {
                        $_POST['start_time'] = $_POST['end_time'] = null;
                    }
                    $_POST['week'] = implode(',', $_POST['week']);
                }


                if ($database_shop_category->where($where)->save($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->frame_submit_tips(0, '编辑失败！请重试~');
                }
            } else {
                $this->frame_submit_tips(0, '非法提交,请重新提交~');
            }
        }
    }

	public function cat_del()
	{
		if (IS_POST) {
			$database_shop_category = M('Shop_category');
			$condition_now_shop_category['cat_id'] = intval($_POST['cat_id']);
			if ($obj = $database_shop_category->field(true)->where($condition_now_shop_category)->find()) {
				$t_list = $database_shop_category->field(true)->where(array('cat_fid' => $obj['cat_id']))->select();
				if ($t_list) {
					$this->error('该分类下有子分类，先清空子分类，再删除该分类');
				}
			}
			if ($database_shop_category->where($condition_now_shop_category)->delete()) {
				$database_shop_category_relation = M('Shop_category_relation');
				$condition_shop_category_relation['cat_id'] = intval($_POST['cat_id']);
				$database_shop_category_relation->where($condition_shop_category_relation)->delete();
				$this->success('删除成功！');
			} else {
				$this->error('删除失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}

	// 预约自定义表单所有字段展示
	public function cue_field(){
		$condition_now_appoint_category['cat_id'] = intval($_GET['cat_id']);
		$now_category = M((_view('Shop_category')))->field(true)->where($condition_now_appoint_category)->find();

		if(empty($now_category)){
			$this->frame_error_tips('没有找到该分类信息！');
		}
		if(!empty($now_category['cue_field'])){
			$now_category['cue_field'] = unserialize($now_category['cue_field']);
			foreach ($now_category['cue_field'] as $val){
				$sort[] = $val['sort'];
			}
			array_multisort($sort, SORT_DESC, $now_category['cue_field']);
		}
		$this->assign('now_category',$now_category);
		$this->display();
	}

	// 预约自定义表单添加字段
	public function cue_field_add(){
		$this->assign('bg_color','#F3F3F3');

		$this->display();
	}

	// 预约自定义表单添加字段 操作
	public function cue_field_modify(){
		if(IS_POST){
			$database_appoint_category = M((_view('Shop_category')));
			$condition_now_appoint_category['cat_id'] = intval($_POST['cat_id']);
			$now_category = $database_appoint_category->field(true)->where($condition_now_appoint_category)->find();

			if(!empty($now_category['cue_field'])){
				$cue_field = unserialize($now_category['cue_field']);
				foreach($cue_field as $key=>$value){
					if($value['name'] == $_POST['name']){
						$this->error('该填写项已经添加，请勿重复添加！');
					}
				}
			}else{
				$cue_field = array();
			}

			$post_data['name'] = $_POST['name'];
			$post_data['type'] = $_POST['type'];
			$post_data['sort'] = strval($_POST['sort']);
			$post_data['iswrite'] = $_POST['iswrite'];
			if(!empty($_POST['use_field'])){
				$post_data['use_field'] = explode(PHP_EOL, $_POST['use_field']);
				foreach($post_data['use_field'] as $ulkey=>$ulvalue){
					$post_data['use_field'][$ulkey] = trim($ulvalue);
				}
			}

			array_push($cue_field,$post_data);
			$data_group_category['cue_field'] = serialize($cue_field);
			$data_group_category['cat_id'] = $now_category['cat_id'];
			if($database_appoint_category->data($data_group_category)->save()){
				$this->success('添加成功！');
			}else{
				$this->error('添加失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	public function cue_field_del(){
		if(IS_POST){
			$database_group_category = M((_view('Shop_category')));
			$condition_now_group_category['cat_id'] = intval($_POST['cat_id']);
			$now_category = $database_group_category->field(true)->where($condition_now_group_category)->find();

			if(!empty($now_category['cue_field'])){
				$cue_field = unserialize($now_category['cue_field']);
				$new_cue_field = array();

				foreach($cue_field as $key=>$value){
					if($value['name'] != $_POST['name']){
						array_push($new_cue_field,$value);
					}
				}
			}else{
				$this->error('此填写项不存在！');
			}
			$data_group_category['cue_field'] = serialize($new_cue_field);
			$data_group_category['cat_id'] = $now_category['cat_id'];
			if($database_group_category->data($data_group_category)->save()){
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

    public function discount()
    {
        $type = isset($_GET['searchstatus']) ? intval($_GET['searchstatus']) : -1;
        $province_id = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
        $city_id = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $street_id = I('get.street_id',0,'intval');
        $sort = isset($_GET['sort']) ? intval($_GET['sort']) : 0;
        $_GET['searchstatus'] = $type;
        $begin_time = isset($_GET['begin_time']) ? strtotime($_GET['begin_time']) : 0;
        $end_time = isset($_GET['end_time']) ? strtotime($_GET['end_time'] . ' 23:59:59') : 0;
        $name = isset($_GET['name']) ? $_GET['name'] : '';
		$this->assign('level', $this->system_session['level']);
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
                $area_id = $this->system_session['area_id'];
                $city_id = $now_area['area_pid'];
                $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                $province_id = $temp_area['area_pid'];
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
                $city_id = $this->system_session['area_id'];
                $province_id = $now_area['area_pid'];
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
                $province_id = $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }

        if ($province_id && $city_id && $area_id) {
            if($street_id > 0){
                $sql = "SELECT d.* FROM " . C('DB_PREFIX') . "shop_discount AS d LEFT JOIN " . C('DB_PREFIX') . "shop_discount_area AS a ON a.did=d.id LEFT JOIN ".C('DB_PREFIX')."shop_discount_street AS s ON s.did=d.id WHERE `source`=0 AND s.street_id=".$street_id." AND (a.aid=" . $area_id . ' OR (d.is_area=0 AND d.use_area=0))';
            }else{
                $sql = "SELECT d.* FROM " . C('DB_PREFIX') . "shop_discount AS d LEFT JOIN " . C('DB_PREFIX') . "shop_discount_area AS a ON a.did=d.id WHERE `source`=0 AND (a.aid=" . $area_id . ' OR (d.is_area=0 AND d.use_area=0))';
            }

            if ($type != -1) {
                $sql .= ' AND type=' . $type;
            }
            $sql .= ' AND status != 4';

            if ($begin_time && $end_time) {
                $sql .= ' AND d.create_time BETWEEN ' . $begin_time . ' AND ' . $end_time;
            }
        } elseif ($province_id && $city_id) {
            $ids = array($city_id);
            $areas = D('Area')->field('area_id')->where(array('area_pid' => $city_id, 'is_open' => 1))->select();
            foreach ($areas as $area) {
                $ids[] = $area['area_id'];
            }
            $sql = "SELECT d.* FROM " . C('DB_PREFIX') . "shop_discount AS d LEFT JOIN " . C('DB_PREFIX') . "shop_discount_area AS a ON a.did=d.id WHERE `source`=0 AND (a.aid IN (" . implode(',', $ids) . ') OR (d.is_area=0 AND d.use_area=0))';
            if ($type != -1) {
                $sql .= ' AND type=' . $type;
            }
            $sql .= ' AND status != 4';

            if ($begin_time && $end_time) {
                $sql .= ' AND d.create_time BETWEEN ' . $begin_time . ' AND ' . $end_time . ' GROUP BY d.id';
            }
        } elseif ($province_id) {
            $ids = array($province_id);
            $areas = D('Area')->field('area_id')->where(array('area_pid' => $province_id, 'is_open' => 1))->select();
            $cityIds = array();
            foreach ($areas as $area) {
                $ids[] = $area['area_id'];
                $cityIds[] = $area['area_id'];
            }
            if ($cityIds) {
                $areas = D('Area')->field('area_id')->where(array('area_pid' => array('in', $cityIds), 'is_open' => 1))->select();
                foreach ($areas as $area) {
                    $ids[] = $area['area_id'];
                }
            }
            $sql = "SELECT d.* FROM " . C('DB_PREFIX') . "shop_discount AS d LEFT JOIN " . C('DB_PREFIX') . "shop_discount_area AS a ON a.did=d.id WHERE `source`=0 AND (a.aid IN (" . implode(',', $ids) . ') OR (d.is_area=0 AND d.use_area=0))';
            if ($type != -1) {
                $sql .= ' AND d.type=' . $type;
            }
            $sql .= ' AND status != 4';
            if ($begin_time && $end_time) {
                $sql .= ' AND d.create_time BETWEEN ' . $begin_time . ' AND ' . $end_time . '  GROUP BY d.id';
            }
        } else {
            $sql = "SELECT * FROM " . C('DB_PREFIX') . "shop_discount as d WHERE `source` = 0 AND status != 4";
            if ($type != -1) {
                $sql .= ' AND d.type=' . $type;
            }
            if ($begin_time && $end_time) {
                $sql .= ' AND create_time BETWEEN ' . $begin_time . ' AND ' . $end_time;
            }
        }
        $shop_type = I('shop_type', 1, 'intval');
        $sql .= ' and shop_type=' . $shop_type;
        if ($name) {
            $sql .= " AND `name` like '%$name%'";
        }
        if ($sort) {
            $sql .= ' GROUP BY `d`.`id` ORDER BY d.id ASC';
        } else {
            $sql .= ' GROUP BY `d`.`id` ORDER BY d.id DESC';
        }

        $discounts = D()->query($sql);
// 		$discounts = D('Shop_discount')->field(true)->where(array('source' => 0))->select();
        $this->assign('shop_type', $shop_type);
        foreach ($discounts as $key => $value) {
            if ($value['percentage_status'] == 1) {
                $discounts[$key]['reduce_money'] = $value['reduce_money'] * 100 . '%';
                $discounts[$key]['plat_money'] = $value['plat_money'] * 100 . '%';
                $discounts[$key]['merchant_money'] = $value['merchant_money'] * 100 . '%';
            }
            $ids[] = $value['id'];
        }

        if($ids){
        	$w = array();
        	$w['did'] = array('in',$ids);
        	$area = M('Shop_discount_area')->where($w)->select();
        	$use_city = array();
        	$area_tmp_city = [];
        	foreach ($area as $k => $v) {
				if($area_tmp_city[$v['aid']]){
					$tmp = $area_tmp_city[$v['aid']];
				}else{
					$tmp = $this->get_city_id($v);
					$area_tmp_city[$v['aid']] = $tmp;
				}
        		$use_city[$v['did']][] = $tmp;
        	}

        	foreach ($discounts as $k => $v) {
        		if($this->system_session['level']==2 ||$this->system_session['level']==0 ){
        			$discounts[$k]['edit'] = 1;
        		}elseif($v['use_area'] ==1 && in_array($this->system_session['area_id'], $use_city[$v['id']])){
					$discounts[$k]['edit'] = 1;
        		}else{
        			$discounts[$k]['edit'] = 0;
        		}
        	}
        }

        $this->assign('discount_list', $discounts);
        $this->display();
    }

    public function get_city_id($info){
    	if($info['level']==1||$info['level']==3){
			$data = S('area/area_id_all_'.$info['aid']);
			if(empty($data)){
				$data = M('Area')->field(true)->where(array('area_id'=>$info['aid']))->find();
				S('area/area_id_all_'.$info['aid'], $data);
			}
    		return $data['area_pid'];
    	}else{
    		return $info['aid'];
    	}
    }

    public function discount_add()
    {
        $shop_type = I('get.shop_type', 1, 'intval');
        $this->assign('shop_type', $shop_type);
        $this->assign('bg_color', '#F3F3F3');

        $this->assign('admin_level', $this->system_session['level']);
        $this->display();
    }

	public function discount_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		$database_shop_discount = D('Shop_discount');
		$condition_now_shop_discount['id'] = intval($_GET['id']);
		$now_discount = $database_shop_discount->field(true)->where($condition_now_shop_discount)->find();
		if (empty($now_discount)) {
			$this->frame_error_tips('没有找到该优惠信息！');
		}
		$this->assign('now_discount', $now_discount);
		$this->assign('admin_level', $this->system_session['level']);
		$this->display();
	}

	public function discount_modify()
	{
		if (IS_POST) {
			$_POST['source'] = 0;
            if ($_POST['percentage_status'] == 1) {
                if(floatval($_POST['reduce_money']) >= 1){
                    $this->error('优惠的金额必须填写百分比小数 必须小于1');
                }
                if (floatval($_POST['plat_money']) || floatval($_POST['merchant_money'])) {
                    if ((floatval($_POST['plat_money']) + floatval($_POST['merchant_money'])) != 1) {
                        $this->error('商家补贴的金额和平台补贴的金额的和必须等于1');
                    }
                } else {
                    $_POST['plat_money'] = 1;
                }
            } else {
                if (floatval($_POST['plat_money']) || floatval($_POST['merchant_money'])) {
                    if ((floatval($_POST['plat_money']) + floatval($_POST['merchant_money'])) != floatval($_POST['reduce_money'])) {
                        $this->error('商家补贴的金额和平台补贴的金额的和必须等于优惠的金额');
                    }
                } else {
                    $_POST['plat_money'] = $_POST['reduce_money'];
                }
            }
			if ($this->system_session['area_id']) {
			    $_POST['is_area'] = 1;
			}
			$_POST['create_time'] = time();
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['end_time'] = strtotime($_POST['end_time']);
            if($_POST['start_time'] >= $_POST['end_time']){
                $this->error('结束时间必须在开始时间之后');
            }
			if ($did = D('Shop_discount')->add($_POST)) {
			    if ($this->system_session['area_id']) {
			        $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			        if ($now_area) {
			            D('Shop_discount_area')->add(array('did' => $did, 'level' => $now_area['area_type'], 'aid' => $this->system_session['area_id']));
			        }
			    }
				D("System_log")->add_log("添加了".$this->config['mall_alias_name']."优惠券",$this->config['mall_alias_name'],$did);
				D('Config')->clearDataCacheDir('shop_discount');
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}

	public function discount_amend()
	{
		if (IS_POST) {
			$database_shop_discount = D('Shop_discount');
			$where = array('id' => $_POST['id']);
			unset($_POST['id']);

            if ($_POST['percentage_status'] == 1) {
                if(floatval($_POST['reduce_money']) >= 1){
                    $this->error('优惠的金额必须填写百分比小数 必须小于1');
                }
                if (floatval($_POST['plat_money']) || floatval($_POST['merchant_money'])) {
                    if ((floatval($_POST['plat_money']) + floatval($_POST['merchant_money'])) != 1) {
                        $this->error('商家补贴的金额和平台补贴的金额的和必须等于1');
                    }
                } else {
                    $_POST['plat_money'] = 1;
                }
            } else {
                if (floatval($_POST['plat_money']) || floatval($_POST['merchant_money'])) {
                    if ((floatval($_POST['plat_money']) + floatval($_POST['merchant_money'])) != floatval($_POST['reduce_money'])) {
                        $this->error('商家补贴的金额和平台补贴的金额的和必须等于优惠的金额');
                    }
                } else {
                    $_POST['plat_money'] = $_POST['reduce_money'];
                }
            }
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            if($_POST['start_time'] >= $_POST['end_time']){
                $this->error('结束时间必须在开始时间之后');
            }
			if ($database_shop_discount->where($where)->save($_POST)) {
				D("System_log")->add_log("编辑了".$this->config['mall_alias_name']."优惠券",$this->config['mall_alias_name'],$where['id']);
				D('Config')->clearDataCacheDir('shop_discount');
				D('Config')->clearCache();
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}

    public function order()
    {
        $province_id = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
        $city_id = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $street_id = I('get.street_id',0,'intval');

        $where_store = null;
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
                $area_id = $this->system_session['area_id'];
                $city_id = $now_area['area_pid'];
                $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                $province_id = $temp_area['area_pid'];
            } elseif($now_area['area_type'] == 2) {
                $area_index = 'city_id';
                $city_id = $this->system_session['area_id'];
                $province_id = $now_area['area_pid'];
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
                $province_id = $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
            $where_store[$area_index] = $this->system_session['area_id'];

            if($this->system_session['street_id']){
                $where_store['street_id'] = $this->system_session['street_id'];
            }
        }

        if (! empty($_GET['keyword']) && $_GET['searchtype'] == 's_name') {
            $where_store['name'] = array('like', '%' . $_GET['keyword'] . '%');
        }
        if ($province_id) {
            $where_store['province_id'] = $province_id;
        }
        if ($city_id) {
            $where_store['city_id'] = $city_id;
        }
        if ($area_id) {
            $where_store['area_id'] = $area_id;
        }
        if($street_id){
            $where_store['street_id'] = $street_id;
        }

        $store_ids = array();
        $where = array('platform' => 0);

        $count_where = "`o`.`platform`=0 AND `o`.`paid`=1 AND `o`.`status`<>4 AND `o`.`status`<>5 AND (`o`.`pay_type`<>'offline' OR (`o`.`pay_type`='offline' AND `o`.`third_id`<>''))";
        if ($where_store) {
            $stores = D('Merchant_store')->field('store_id')->where($where_store)->select();
            foreach ($stores as $row) {
                $store_ids[] = $row['store_id'];
            }

            if ($store_ids) {
                $where['store_id'] = array('in', $store_ids);
                $count_where .= ' AND `o`.`store_id` IN (' . implode(',', $store_ids) . ')';
            } else {
                import('@.ORG.system_page');
                $p = new Page(0, 20);
                $this->assign('order_list', null);
                $this->assign('pagebar', $p->show());
                $this->display();
                exit();
            }
        }

        if (! empty($_GET['keyword'])) {
            $_GET['keyword'] = trim($_GET['keyword']);
            if ($_GET['searchtype'] == 'real_orderid') {
				if(strlen($_GET['keyword']) >= 20){
					$where['real_orderid'] = htmlspecialchars($_GET['keyword']);
					$count_where .= " AND `o`.`real_orderid`='" . htmlspecialchars($_GET['keyword']) . "'";
				}else{
					$where['order_id'] = htmlspecialchars($_GET['keyword']);
					$count_where .= " AND `o`.`order_id`='" . htmlspecialchars($_GET['keyword']) . "'";
				}
            } elseif ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array(
                    'orderid' => $where['orderid']))->find();
                unset($where['orderid']);
                $where['order_id'] = $tmp_result['order_id'];
                $count_where .= " AND `o`.`order_id`=" . $tmp_result['order_id'];
            } elseif ($_GET['searchtype'] == 'name') {
                $where['username'] = htmlspecialchars($_GET['keyword']);
                $count_where .= " AND `o`.`username`='" . htmlspecialchars($_GET['keyword']) . "'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $where['userphone'] = htmlspecialchars($_GET['keyword']);
                $count_where .= " AND `o`.`userphone`='" . htmlspecialchars($_GET['keyword']) . "'";
            } elseif ($_GET['searchtype'] == 'third_id') {
                $where['third_id'] = $_GET['keyword'];
                $count_where .= " AND `o`.`third_id`='" . htmlspecialchars($_GET['keyword']) . "'";
            } elseif ($_GET['searchtype'] == 'register_phone' && $_GET['keyword']) {
                $uid = M('User')->where(['phone' => $_GET['keyword']])->getField('uid');
                if ($uid > 0) {
                    $where['uid'] = $uid;
                    $count_where .= " AND `o`.`uid`=" . $uid;
                }
            } elseif ($_GET['searchtype'] == 'member_card' && $_GET['keyword']) {
                //查询会员卡号的uid
                $uid = M('employee_card_user')->where(['card_number' => $_GET['keyword']])->getField('uid');
                if ($uid > 0) {
                    $where['uid'] = $uid;
                    $count_where .= " AND `o`.`uid`=" . $uid;
                }
            }
        }
        $status = isset($_GET['status']) ? intval($_GET['status']) : - 1;
        $type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
        $sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
        $pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
        if ($sort != 'DESC' && $sort != 'ASC')
            $sort = '';
        if ($type != 'price' && $type != 'pay_time')
            $type = '';
        $order_sort = '';
        if ($type && $sort) {
            $order_sort .= $type . ' ' . $sort . ',';
            $order_sort .= 'pay_time DESC,order_id DESC';
        } else {
            $order_sort .= 'pay_time DESC,order_id DESC';
        }
        if($status > 0){
            $count_where = str_replace('AND `o`.`status`<>4 AND `o`.`status`<>5','', $count_where);
        }
        if ($status == 11) {
            $where['status'] = array('in',array(2,3));
            $where['is_apply_refund'] = 1;
            $count_where .= ' AND o.status in(2,3) AND o.is_apply_refund=1';
        } else if($status == 0) {
            $where['paid'] = 1;
            $where['status'] = array('in',array(0,7));
            $where['order_status'] = 0;
            $count_where .= " AND `o`.`paid`=1 AND `o`.`status` IN (0,7) AND `o`.`order_status`=0";
        } else if($status == 100) {
            $where['paid'] = 0;
            $count_where .= " AND `o`.`paid`=0";
        } else if($status == 12){
			$where['paid'] = 1;
			$where['status'] = array('in',array(0,1,9));
			$count_where .= " AND `o`.`status` IN (0,1,9) AND paid=1";
		}else if ($status != - 1) {
            if ($status == 2) {
                $where['status'] = array('in', array(2, 3));
                $count_where .= " AND `o`.`status` IN (2, 3)";
            }elseif ($status === 0) {
                $where['paid'] = 1;
                $count_where .= ' AND o.paid = 1 ';
            } else {
                $where['status'] = $status;
                $count_where .= " AND `o`.`status`=" . $status;
            }
        }
        if ($pay_type && $pay_type != 'balance') {
            if ($pay_type == 'alipayh5') {
                $where['pay_type'] = ['IN', ['alipayh5', 'alipay']];
                $count_where .= " AND `o`.`pay_type` IN ('alipayh5','alipay')";
            }elseif ($pay_type == 'weixinapp') {
                $where['pay_type'] = 'weixin';
                $where['is_mobile_pay'] = 2;
                $count_where .= " AND `o`.`pay_type` = 'weixin' AND is_mobile_pay =2 ";
            }else if($pay_type == 'employee_balance_pay'){
                $where['employee_balance_pay'] = ['gt', 0];
                $count_where .= " AND `o`.`employee_balance_pay` > 0";
            }else if($pay_type == 'employee_score_pay'){
                $where['employee_score_pay'] = ['gt', 0];
                $count_where .= " AND `o`.`employee_score_pay` > 0";
            }else {
                $where['pay_type'] = $pay_type;
                $count_where .= " AND `o`.`pay_type`='" . $pay_type . "'";
            }
        } else if ($pay_type == 'balance') {
            $where['_string'] = "(`balance_pay`<>0 OR `merchant_balance` <> 0 OR pay_type='') ";

            $count_where .= " AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 OR o.pay_type='')";
        }


		if(empty($_GET['begin_time'])){
			$_GET['begin_time'] = date('Y-m-d',strtotime('-1 day'));
		}
        if(empty($_GET['end_time'])){
            $_GET['end_time'] = date('Y-m-d');
        }

        if (! empty($_GET['begin_time']) && ! empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(
                strtotime($_GET['begin_time'] . " 00:00:00"),
                strtotime($_GET['end_time'] . " 23:59:59")
            );

			$_GET['searchTimeType'] = $_GET['searchTimeType'] ? $_GET['searchTimeType'] : 'create_time';


            // 退款时间（包括退款，取消，申请退款）
            if ($_GET['searchTimeType']=='refund_time') {
            	$refundOrderIdArray = [];
            	// 查询申请退款订单
            	$refund_condition = [];
            	$refund_condition['status'] = ['in',[0,3]];
            	$refund_condition['applytime'] = ['between',$period];
            	$refund_apply_ids= array_column(M('Shop_order_refund')->field('order_id')->where($refund_condition)->select(), 'order_id');
            	$refund_apply_ids && $refundOrderIdArray = array_merge($refundOrderIdArray,$refund_apply_ids);

            	// 查询退款、取消订单
            	$refund_condition = [];
            	$refund_condition['status'] = ['in',[9,10]];
            	$refund_condition['dateline'] = ['between',$period];
            	$refund_ids = array_column(M('Shop_order_log')->field('order_id')->where($refund_condition)->select(), 'order_id');
            	$refund_ids && $refundOrderIdArray = array_merge($refundOrderIdArray,$refund_ids);
            	$refundOrderIdArray = array_unique($refundOrderIdArray);

            	$where['_string'] .= ($where['_string'] ? ' AND ' : '') . " `order_id` in (".implode(',', $refundOrderIdArray).")";

	            $count_where .= " AND (`o`.`order_id` in (".implode(',', $refundOrderIdArray).")";

            }else{
	            $where['_string'] .= ($where['_string'] ? ' AND ' : '') . " (".$_GET['searchTimeType']." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

	            $count_where .= " AND (`o`.`".$_GET['searchTimeType']."` BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }
        }
        //商城来源订单
        $from_type=I('get.from_type',1,'intval');
        if($from_type==2){
        	$where['order_from']=1;
        	$count_where.=' and o.order_from=1';
        }else{
        	$where['order_from']=array('neq',1);
        	$count_where.=' and o.order_from!=1';
        }
        //区分线下零售
        $order_from = I('get.order_from',0,'intval');
        if($order_from==1&&$from_type!=2){
            $where['order_from']=array(array('neq',1),array('neq',6));
            $count_where.=' and o.order_from!=6';
        }elseif($order_from==1){
            $where['order_from']=array('neq',6);
            $count_where.=' and o.order_from!=6';
        }elseif($order_from==6){
            $where['order_from']=6;
            $count_where.=' and o.order_from=6';
        }
        $this->assign('from_type',$from_type);
        $this->assign('order_from',$order_from);

		//配送方式
		if(isset($_GET['is_pick_in_store']) && $_GET['is_pick_in_store'] !== ''){
			$where['is_pick_in_store'] = $_GET['is_pick_in_store'];
            $count_where.=' and o.is_pick_in_store=' . $_GET['is_pick_in_store'];
		}
		//支付类型
		if(isset($_GET['from_pay_type']) && $_GET['from_pay_type'] !== ''){
			$where['from_pay_type'] = $_GET['from_pay_type'];
            $count_where.=' and o.from_pay_type=' . $_GET['from_pay_type'];
		}
        //$where['is_del'] = 0;
        //$count_where .= ' AND o.is_del = 0';

        $result = D("Shop_order")->get_order_list($where, $order_sort, 3, false);
		fdump($result,"ssssmmm7777777777777uuuuuuuuuu");
        $list = isset($result['order_list']) ? $result['order_list'] : '';
        $store_ids = array();
        foreach ($list as $l) {
            $store_ids[] = $l['store_id'];
        }
        $temp = array();
        if ($store_ids) {
            $store_ids = implode(',', $store_ids);
            $sql = "SELECT `m`.`name` AS merchant_name, `s`.`name` AS store_name, `s`.`phone` AS store_phone, `s`.`store_id` FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` WHERE `s`.`store_id` IN ($store_ids)";
            $mod = new Model();
            $res = $mod->query($sql);
            foreach ($res as $r) {
                $temp[$r['store_id']] = $r;
            }
        }

        //获取用户信息
        $uids = array_unique(array_column($list, 'uid'));
        if ($uids) {
            $users = D('User')->where(['uid' => ['IN', $uids]])->getField('uid,nickname,phone');
        } else {
            $users = [];
        }
        foreach ($result['order_list'] as &$li) {
            $li['merchant_name'] = isset($temp[$li['store_id']]['merchant_name']) ? $temp[$li['store_id']]['merchant_name'] : '';
            $li['store_name'] = isset($temp[$li['store_id']]['store_name']) ? $temp[$li['store_id']]['store_name'] : '';
            $li['store_phone'] = isset($temp[$li['store_id']]['store_phone']) ? $temp[$li['store_id']]['store_phone'] : '';
            $li['register_name'] = $users[$li['uid']]['nickname'] ?? '';;
            $li['register_phone'] = $users[$li['uid']]['phone'] ?? '';
        }
        $this->assign(array(
            'type' => $type,
            'sort' => $sort,
            'status' => $status,
            'pay_type' => $pay_type
        ));
        $status_list = D('Shop_order')->status_list;
        $status_list[11] = L_('申请退货');
        $this->assign('status_list', $status_list);
        $this->assign($result);

        // 下单总金额, 验证消费总金额
        $field = 'sum(price) AS total_price, sum(price - card_price - merchant_balance - balance_pay - payment_money - score_deducte - coupon_price - card_give_money) AS offline_price, sum(card_price + merchant_balance + balance_pay + payment_money + score_deducte + coupon_price + card_give_money) AS online_price';

        $table = array(
            C('DB_PREFIX') . 'shop_order' => 'o',
            C('DB_PREFIX') . 'merchant_store' => 's'
        );

        $count_where .= ' AND o.store_id = s.store_id';
        if ($this->system_session['area_id'] > 0) {
            $count_where .= ' AND s.' . $area_index . '=' . $this->system_session['area_id'];
        }
        if ($this->system_session['street_id'] > 0) {
            $count_where .= ' AND s.street_id=' . $this->system_session['street_id'];
        }
        $result_total_list = D('')->table($table)->field('total_price, price, qiye_pay,card_price, merchant_balance, balance_pay, payment_money, score_deducte, coupon_price, card_give_money')->where($count_where)->select();
        $result_total = array();
        foreach ($result_total_list as $key => $value) {
        	$total_online_temp = $value['card_price'] + $value['merchant_balance'] + $value['balance_pay'] + $value['payment_money'] + $value['score_deducte'] + $value['coupon_price'] + $value['card_give_money'] + $value['qiye_pay'];
        	$total_online_temp = $total_online_temp > $value['price'] ? $value['price'] : $total_online_temp;
        	$result_total[0]['total_price'] = getFormatNumber($result_total[0]['total_price']+$value['total_price']);
        	$result_total[0]['pay_price'] = getFormatNumber($result_total[0]['pay_price']+$value['price']);
        	$result_total[0]['offline_price'] = getFormatNumber($result_total[0]['offline_price']+$value['price']-$total_online_temp);
        	$result_total[0]['online_price'] = getFormatNumber($result_total[0]['online_price']+$total_online_temp);
            $result_total[0]['payment_money'] = getFormatNumber($result_total[0]['payment_money']+$value['payment_money']);
            $result_total[0]['balance_pay'] = getFormatNumber($result_total[0]['balance_pay']+$value['balance_pay']);
            $result_total[0]['merchant_balance'] = getFormatNumber($result_total[0]['merchant_balance']+$value['merchant_balance']);
            $result_total[0]['score_deducte'] = getFormatNumber($result_total[0]['score_deducte']+$value['score_deducte']);
            $result_total[0]['card_give_money'] = getFormatNumber($result_total[0]['card_give_money']+$value['card_give_money']);
        }
        $table[C('DB_PREFIX') . 'merchant_money_list'] = 'm';
        $count_where .= " AND o.real_orderid=m.order_id AND m.type='shop'";
        $money = D('')->table($table)->field($field)->where($count_where)->sum('m.money');

		$result_total = isset($result_total[0]) ? $result_total[0] : '';
        $this->assign($result_total);
        $pay_method = D('Config')->get_pay_method('', '', 1);
        $this->assign('pay_method', $pay_method);
        $this->assign('money', $money);
        $this->display();
    }

	public function change_shop_order_note(){
		$order_id = $_POST['order_id'];
		$note = $_POST['note'];
		$database_order = D('Shop_order');
		$where['order_id'] = $order_id;

		$update = $database_order->where($where)->setField('note',$note);

		if($update){
			echo json_encode(array('errorCode'=>0,'msg'=>L_('操作成功')));exit;
		}else{
			echo json_encode(array('errorCode'=>1,'msg'=>L_('操作失败')));exit;
		}
	}

    //订单详情
	public function order_detail()
	{
		$this->assign('bg_color', '#F3F3F3');
		if(strlen($_GET['order_id'])>10){
			$res = M('Shop_order')->field('order_id')->where(array('real_orderid'=>$_GET['order_id']))->find();
			$_GET['order_id']=$res['order_id'];
		}
        if ($this->system_session['level'] < 2) {
            if (!in_array(310, $this->system_session['menus'])) $this->assign('notShow', 1);
        }

		$order = D('Shop_order')->get_order_detail(array('order_id' => intval($_GET['order_id'])));
        //获取退款时间
        if ($order['status'] == 4) {
            $order['refund_time'] = M('Shop_order_log')->where(['status' => 9, 'order_id' => intval($_GET['order_id'])])->getField('dateline');
        }
		//获取取消订单时间
        if ($order['status'] == 5) {
            $order['cancel_time'] = M('Shop_order_log')->where(['status' => 10, 'order_id' => intval($_GET['order_id'])])->getField('dateline');
        }
		if (empty($order)) {
			$this->frame_error_tips('没有找到该订单的信息！');
		}
        $supplys = D('deliver_supply')->where(array('order_id' => intval($_GET['order_id']), 'item' => 2))->find();//这里修复一个bug
        $supplys['confirm_time'] = $supplys['create_time'] && $supplys['order_time'] ? intval(($supplys['create_time'] - $supplys['order_time']) / 60) . '分钟' : '-';
        $supplys['grab_time'] = $supplys['start_time'] ? intval(($supplys['start_time'] - $supplys['create_time']) / 60) . '分钟' : '-';
        $supplys['deliver_use_time'] = $supplys['end_time'] ? intval(($supplys['end_time'] - $supplys['start_time']) / 60) . '分钟' : '-';
        $tempList = array();
        $times = D('Shop_order_log')->where(array('order_id' => $_GET['order_id']))->select();
        $delivery_times = array();
        foreach ($times as $time) {
            if ($time['status'] > 3 && $time['status'] < 6) {
                $delivery_times[] = $time;
            }
        }
        if(count($delivery_times)>0){
		    $this->assign('delivery_times',$delivery_times);
        }
		foreach($order['info'] as $v) {
		    $index = isset($v['packname']) && $v['packname'] ? $v['packname'] : 0;
		    if (isset($tempList[$index])) {
		        $tempList[$index]['list'][] = $v;
		    } else {
		        $tempList[$index] = array('name' => $v['packname'], 'list' => array($v));
		    }
		}
		if (count($tempList) == 1) {
		    $tempList[$index]['name'] = '';
		}
		$order['info'] = $tempList;

		// 查询一下商圈信息
        $m_area = M('Area');
        $m_merchant_store= M("Merchant_store");

        $store_info = $m_merchant_store->where(array('store_id' => $order['store_id']))->Field('circle_id,is_authentication')->find();
        $circle_id  = $store_info['circle_id'];
        if ($circle_id) {
            // 获取对应商圈信息
            $condition_area = array(
                'area_id' => $circle_id
            );
            $circle_arr = $m_area->where($condition_area)->field('area_pid, area_name, area_ip_desc')->find();
            if (!$circle_arr['area_ip_desc']) {
                if ($circle_arr['area_pid']) {
                    $condition_pid_area = array(
                        'area_id' => $circle_arr['area_pid']
                    );
                    $circle_msg = $m_area->where($condition_pid_area)->getField('area_name');
                    $order['circle_info'] = $circle_msg . $circle_arr['area_name'];
                } else {
                    $order['circle_info'] = $circle_arr['area_name'];
                }
            } else {
                $order['circle_info'] = $circle_arr['area_ip_desc'];
            }
        } else {
            $order['circle_info'] = '';
        }
		$this->assign('store', D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find());
		        //实名认证
        if($store_info['is_authentication']&&$order['order_from']==1){
            $authenticationInfo = D('Mall_authentication')->where(array('uid'=>$order['uid']))->find();
            $authenticationInfo['authentication_field'] = unserialize($authenticationInfo['authentication_field']);
            $this->assign('authentication_field',$authenticationInfo['authentication_field']);
        }

        if(false!==strpos($order['last_time'],'-')){
            $order['last_time'] = strtotime($order['last_time']);
        }

		if($supplys['status'] == 0 && $cancle_order_log = M('Shop_order_log')->where(array('order_id'=>$order['order_id'],'status'=>34))->find()){
			$order['deliver_status_str'] = '<font color="red">系统后台调为配送失败'.($cancle_order_log['name'] ? '【取消人员：'.$cancle_order_log['name'].'】' : '').'</font>';
		}

        //组合附属商品2
		foreach ($order['info'] as $key => $value){
            $order['info'][$key]['list'] = D('Shop_goods')->combination_data2($value['list']);
        }

        //支付手续费
        $payment_money_order_db = M("payment_money_order");
        $payment_money_order = $payment_money_order_db->where("order_id=".$_GET['order_id'])->find();
        $order['service_charge'] = '';
        if($payment_money_order && $payment_money_order['service_charge'] != '0.00'){
        	$order['service_charge'] = $payment_money_order['service_charge'];
        }

        //优惠券情况
        if($order['coupon_id']){
            $system_coupon_hadpull = M('System_coupon_hadpull')->where(['id' => $order['coupon_id']])->find();
            if($system_coupon_hadpull){
                $order['system_coupon_info'] = M('System_coupon')->where(['coupon_id' => $system_coupon_hadpull['coupon_id']])->find();
            }
        }

        //定制--第三方实名认证 根据商品实名配置
        if($order['order_from']==1) {
            if($order['is_open_check'] == 1) { //订单商品需要实名认证
                $check_id_card_info = M('User_authentication')->field('user_truename,user_id_number,authentication_img,authentication_back_img,authentication_status')->where(['uid'=>$order['uid'],'authentication_status'=>1])->find();

                $this->assign('check_id_card_info', $check_id_card_info);
            }
        }

		$this->assign('order', $order);
		$this->assign('supplys',$supplys);

		//退款详情
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $refundList = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'uid' => $order['uid']))->order('id DESC')->select();

        $sql = "SELECT g.image, d.goods_id, d.host_goods_id, d.uniqueness_number, d.name, d.price, d.num, d.unit, d.spec, d.id, d.refund_id FROM " . C('DB_PREFIX') . "shop_goods AS g INNER JOIN " . C('DB_PREFIX') . "shop_refund_detail AS d ON g.goods_id=d.goods_id WHERE d.order_id={$order_id}";
        $list = D()->query($sql);
        $data = array();
        $goods_image_class = new goods_image();
        foreach ($list as $row) {
        	$row['price'] = $row['price'] < 0 ? 0 : $row['price'];
            $image = '';
            if(!empty($row['image'])){
                $tmp_pic_arr = explode(';', $row['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    if (empty($image)) {
                        $image = $goods_image_class->get_image_by_path($value, 's');
                        break;
                    }
                }
            }
            $row['image'] = $image;
            $data[$row['refund_id']][] = $row;
        }
        //状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请)
        $status = array('审核中', '已完成退货', '拒绝退货', '审核中', '取消退货申请');
        foreach ($refundList as &$refund) {
            $refund['image'] = $refund['image'] ? explode(',', $refund['image']) : '';
            $refund['goodsList'] = isset($data[$refund['id']]) ? $data[$refund['id']] : array();
            $refund['showStatus'] = $status[$refund['status']];
        }

        //组合附属商品2
        foreach ($refundList as $key => $value){
            $refundList[$key]['goodsList'] = D('Shop_goods')->combination_data2($value['goodsList']);
        }

        $this->assign('refund_list', $refundList);

        if($store_info['is_authentication']&&$order['order_from']==1){
            $authenticationInfo = D('Mall_authentication')->where(array('uid'=>$order['uid']))->find();
            $authenticationInfo['authentication_field'] = unserialize($authenticationInfo['authentication_field']);
            $this->assign('authentication_field',$authenticationInfo['authentication_field']);
        }

		$this->display();
	}

	//订单日志
    public function order_detail_log(){
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id))) {
            $status = D('Shop_order_log')->field(true)->where(array('order_id' => $order_id))->order('id DESC')->select();
            $this->assign('status', $status);
            $this->assign('order', $order);
            $this->display();
        } else {
            $this->error_tips('错误的订单信息！');
        }
    }

    //商城热门搜索词
    public function search_hot_mall(){
        $database_search_hot = D('Search_hot_mall');
        $page_size = 10;
        import('@.ORG.system_page');
        $count=$database_search_hot->count(1);
        $page=new page($count,$page_size);
        $search_hot_list = $database_search_hot->field(true)->order('`sort` DESC,`hottest` DESC,`id` ASC')->limit($page->firstRow,$page->listRows)->select();
        $this->assign('search_hot_list',$search_hot_list);
        $this->assign('pagebar',$page->show());
        $this->display();
    }

    //添加页面
    public function search_hot_mall_add(){
        $this->assign('bg_color','#F3F3F3');
        $this->display();
    }

    //添加处理
    public function search_hot_mall_modify(){
        if(IS_POST){
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            $database_search_hot = D('Search_hot_mall');
            if($res=$database_search_hot->data($_POST)->add()){
				D("System_log")->add_log("增加了".$this->config['mall_alias_name']."热门搜索关键词：".$_POST['name'],$this->config['mall_alias_name'],$res);
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    //编辑页面
    public function search_hot_mall_edit(){
        $this->assign('bg_color','#F3F3F3');
        $database_search_hot = D('Search_hot_mall');
        $condition_search_hot['id'] = intval($_GET['id']);
        $search_hot = $database_search_hot->field(true)->where($condition_search_hot)->find();
        $this->assign('search_hot',$search_hot);
        $this->display();
    }

    //编辑处理
    public function search_hot_mall_amend(){
        if(IS_POST){
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            $database_search_hot = D('Search_hot_mall');
            if($database_search_hot->data($_POST)->save()){
				D("System_log")->add_log("编辑了".$this->config['mall_alias_name']."热门搜索关键词：".$_POST['name'],$this->config['mall_alias_name'],$_POST['id']);
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    //删除热词
    public function search_hot_mall_del(){
        if(IS_POST){
            $database_search_hot = D('Search_hot_mall');
            $condition_search_hot['id'] = intval($_POST['id']);
            if($database_search_hot->where($condition_search_hot)->delete()){
				D("System_log")->add_log("删除了".$this->config['mall_alias_name']."热门搜索关键词",$this->config['mall_alias_name'],$_POST['id']);
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    //修改配送状态页
	public function edit_supply_status(){
        $supply_id = $_GET['supply_id'];
        $this->assign('supply_id',$supply_id);
        $this->display();
    }

	public function shop()
	{
        $where = [
            's.status'=>1,
            's.have_shop'=>1,
            'sh.deliver_type'=>['IN',[0,3]],
            'm.status'=>1,
        ];

        $searchType = I('get.searchtype', '', 'trim');
        $keyword = I('get.keyword', '', 'trim');
        $deliveryRange = I('get.delivery_range', 0, 'intval');
        $beginTime = I('get.begin_time', '', 'trim');
        $endTime = I('get.end_time', '', 'trim');
        $isExport = I('get.is_export', 0, 'intval');
        if ($searchType && $keyword) {
            switch ($searchType) {
                case 'store_name':
                    $where['s.name'] = ['LIKE', '%' . $keyword . '%'];
                    break;
                case 'mer_name':
                    $where['m.name'] = ['LIKE', '%' . $keyword . '%'];
                    break;
                default:
                    break;
            }
        }

        if ($deliveryRange == 1) {
            //已设置 包括半径和指定范围两种
            $where['_string'] = "sh.delivery_range_type=1 OR sh.delivery_radius > 0";
        } else if ($deliveryRange == 2) {
            //未设置
            $where['_string'] = "NOT (sh.delivery_range_type=1 OR sh.delivery_radius > 0)";
        }

        if ($beginTime) {
            $where['sh.create_time'][] = ['gt', strtotime($beginTime)];
        }
        if ($endTime) {
            $where['sh.create_time'][] = ['lt', strtotime($endTime) + 86400];
        }


        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $where['s.' . $area_index] = $this->system_session['area_id'];
            if ($this->system_session['street_id']) {
                $where['s.street_id'] = $this->system_session['street_id'];
            }
        }


        $join = 'INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = sh.store_id ';
        $join .= 'INNER JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id=s.mer_id ';

        if ($isExport) {
            $lists = D('Merchant_store_shop')->alias('sh')
                ->join($join)
                ->where($where)
                ->field('`m`.`name` AS merchant_name, `s`.`name` AS store_name, `m`.`phone` AS merchant_phone, `s`.`phone` AS store_phone, `s`.`store_id`, `sh`.*')
                ->select();
            $lists = array_map(function ($r) {
                $r['create_date'] = date('Y-m-d H:i:s', $r['create_time']);
                $r['is_open_own_desc'] = $r['s_is_open_own'] == 1 ? '开启' : '关闭';
                $r['delivery_desc'] = $r['delivery_range_type'] == 1 ? '自定义' : ($r['delivery_radius'] > 0 ? getFormatNumber($r['delivery_radius']) . '公里' : '未设置');
                return $r;
            }, $lists);
            $config = [
                'header' => [
                    ['title' => L_('店铺ID'), 'value_key' => 'store_id'],
                    ['title' => L_('商家名称'), 'value_key' => 'merchant_name'],
                    ['title' => L_('店铺名称'), 'value_key' => 'store_name'],
                    ['title' => L_('独立配送设置'), 'value_key' => 'is_open_own_desc'],
                    ['title' => L_('服务范围'), 'value_key' => 'delivery_desc'],
                    ['title' => L_('商家电话'), 'value_key' => 'merchant_phone'],
                    ['title' => L_('店铺电话'), 'value_key' => 'store_phone'],
                    ['title' => L_('创建时间'), 'value_key' => 'create_date'],
                ],
                'body' => $lists,
                'file_name' => time(),
                'sheet_title' => '平台配送店铺列表'
            ];
            $this->download_store_list($config);
        }

        $count = D('Merchant_store_shop')->alias('sh')
            ->join($join)
            ->where($where)
            ->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);

        $orderLists = D('Merchant_store_shop')->alias('sh')
            ->join($join)
            ->where($where)
            ->field('`m`.`name` AS merchant_name, `s`.`name` AS store_name, `m`.`phone` AS merchant_phone, `s`.`phone` AS store_phone, `s`.`store_id`, `sh`.*')
            ->limit($p->firstRow, $p->listRows)
            ->select();

		$this->assign('order_list', $orderLists);
		$this->assign('pagebar', $p->show());
		$this->display();
	}

	public function shop_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		$database = D('Merchant_store_shop');
		$where['store_id'] = intval($_GET['store_id']);
		$now_shop = $database->field(true)->where($where)->find();

		if (empty($now_shop)) {
			$this->frame_error_tips('没有找到该店铺信息！');
		}
		if ($now_shop['delivery_range_polygon']) {
            if (C('config.tianditu_web_key')) {
                $now_shop['delivery_range_polygon'] = polygon_2_type($now_shop['delivery_range_polygon'], 'json', 'bd09ToWgs84');
            } else {
                $now_shop['delivery_range_polygon'] = polygon_2_type($now_shop['delivery_range_polygon'], 'json');
            }
		}

		$is_have_two_time = 0;
		if ($this->config['delivery_time2']) {
		    $delivery_times2 = explode('-', $this->config['delivery_time2']);
		    $start_time2 = $delivery_times2[0];
		    $stop_time2 = $delivery_times2[1];
		    if ($start_time2 != $stop_time2) {
		        $is_have_two_time = 1;
		    }
		}

		$is_have_three_time = 0;
		if ($this->config['delivery_time2']) {
		    $delivery_times3 = explode('-', $this->config['delivery_time3']);
		    $start_time3 = $delivery_times3[0];
		    $stop_time3 = $delivery_times3[1];
		    if ($start_time2 != $stop_time2) {
		        $is_have_three_time = 1;
		    }
		}
        
        if($now_shop['s_is_open_own']){//是否开启平台对店铺的配送费单独设置
            $is_have_two_time = 1;
            $is_have_three_time = 1;
        }
		$customs = D('Deliver_custom')->field(true)->select();
		foreach ($customs as &$value) {
		    $value['delivery_range_polygon'] = substr($value['delivery_range_polygon'], 9, strlen($value['delivery_range_polygon']) - 11);
		    $lngLatData = explode(',', $value['delivery_range_polygon']);
		    array_pop($lngLatData);
		    $lngLats = array();
		    foreach ($lngLatData as $lnglat) {
		        $lng_lat = explode(' ', $lnglat);
// 		        $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
                if (C('config.tianditu_web_key')) {
                    list($lng_lat[0], $lng_lat[1]) = Coordinate::bd09ToWgs84($lng_lat[0], $lng_lat[1]);
                }
		        $lngLats[] = $lng_lat[1] . '-' . $lng_lat[0];
		    }
		    $value['delivery_range_polygon'] = implode('|', $lngLats);
		}
		$this->assign('customs', $customs);

		$this->assign('is_have_two_time', $is_have_two_time);
		$this->assign('is_have_three_time', $is_have_three_time);
		$now_store = D('Merchant_store')->field(true)->where($where)->find();
		$now_shop = array_merge($now_shop, $now_store);
		$this->assign('now_shop', $now_shop);
		$this->display();
	}

	public function shop_amend()
	{
		if (IS_POST) {
			$where = array('store_id' => $_POST['id']);
			if ($shop = D('Merchant_store_shop')->field(true)->where($where)->find()) {
    			unset($_POST['id']);
    			if ($_POST['delivery_range_type'] == 1) {
    			        if ($_POST['delivery_range_polygon'] && $_POST['delivery_range_polygon'] != $_POST['custom_id']) {
    			        $latLngArray = explode('|', $_POST['delivery_range_polygon']);
    			        if (count($latLngArray) < 3) {
    			            $this->error('请绘制一个合理的服务范围！');
    			        } else {
    		                $latLngData = array();
    		                foreach ($latLngArray as $row) {
    		                    $latLng = explode('-', $row);
								if(count($latLng) == 3){
									if(empty($latLng[0])){
										$latLng[0] = '-' . $latLng[1];
										$latLng[1] = $latLng[2];
									}
								}
                                if (C('config.tianditu_web_key')) {
                                    list($latLng[1], $latLng[0]) = Coordinate::wgs84ToBd09($latLng[1], $latLng[0]);
                                }
    // 		                    $latLngData[] = array('lat' => $latLng[0], 'lng' => $latLng[1]);
    		                    $latLngData[] = $latLng[1] . ' ' . $latLng[0];//array('lat' => $latLng[0], 'lng' => $latLng[1]);
    		                }
    		                $latLngData[] = $latLngData[0];
    		                $_POST['delivery_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
    			        }
                    } elseif ($_POST['custom_id']) {
                        $custom = D('Deliver_custom')->field(true)->where(array('id' => intval($_POST['custom_id'])))->find();
                        if (empty($custom)) {
                            $this->error('请选择正确的服务范围');
                        }
                        $_POST['delivery_range_polygon'] = $custom['delivery_range_polygon'];
                    } else {
    			        $this->error('请绘制您的服务范围！');
    			    }
    			    unset($_POST['delivery_radius']);
    			} else {
    			    unset($_POST['delivery_range_polygon']);
    			}
    			if ($_POST['s_send_time'] > 0) {
    			    $_POST['send_time'] = $_POST['s_send_time'];
    			} else {
    			    $_POST['send_time'] = $_POST['s_send_time'] = $this->config['deliver_send_time'];
    			}
    			$diffTime = 1;
    			if ($shop['send_time_type'] == 1) {
    			    $diffTime = 60;
    			} elseif ($shop['send_time_type'] == 2) {
    			    $diffTime = 1440;
    			} elseif ($shop['send_time_type'] == 3) {
    			    $diffTime = 1440 * 7;
    			} elseif ($shop['send_time_type'] == 4) {
    			    $diffTime = 1440 * 30;
    			}
    			$_POST['sort_time'] = $shop['work_time'] * $diffTime + $_POST['send_time'];

    			// 起送价设置
    			if ($_POST['open_s_basic_price'] == 0) {
    				$_POST['s_basic_price'] = -1;
    			}
    			// 起送价设置
    			if ($_POST['open_s_basic_price1'] == 0) {
    				$_POST['s_basic_price1'] = -1;
    			}
    			// 起送价设置
    			if ($_POST['open_s_basic_price2'] == 0) {
    				$_POST['s_basic_price2'] = -1;
    			}
    			// 起送价设置
    			if ($_POST['open_s_basic_price3'] == 0) {
    				$_POST['s_basic_price3'] = -1;
    			}

    			if (D('Merchant_store_shop')->where($where)->save($_POST)) {
    				$this->success('编辑成功！');
    			} else {
    				$this->error('编辑失败！请重试~');
    			}
			} else {
			    $this->error('数据不正确');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}

	public function refund_update(){
		$database_shop_order = D('Shop_order');
		$condition_shop_order['order_id'] = $_GET['order_id'];
		$order = $database_shop_order->field('`order_id`,`mer_id`,`paid`')->where($condition_shop_order)->find();
		if(empty($order)){
			$this->error('此订单不存在！');
		}
		$data['status'] = $order['paid'] == 1 ? 4 : 5;
		$data['last_time'] = time();
		if($database_shop_order->where($condition_shop_order)->save($data)){
		    D('Shop_order_log')->add_log(array('order_id' => $order['order_id'], 'status' => $order['paid'] == 1 ? 9 : 10, 'name' => $this->system_session['account'], 'note' => '系统管理员操作处理'));
			$this->success('订单状态已改为已退款！');
		}else{
			$this->error('订单状态改变失败！');
		}
	}

	public function export()
	{
		$param = $_POST;
		$param['type'] = 'shop';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		$param['system_session']['area_id'] = $this->system_session['area_id'] ;

        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
		die;
		set_time_limit(0);
		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = '订单信息';
		$cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_discISAM;
		$cacheSettings = array( 'dir' => './runtime' );
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);

		// 设置当前的sheet

		$where_store = null;
		if(!empty($_GET['keyword']) && $_GET['searchtype'] == 's_name'){
			$where_store['name'] = array('like', '%'.$_GET['keyword'].'%');
		}

		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$where_store[$area_index] = $this->system_session['area_id'];
		}

		$store_ids = array();
		$where = array('platform' => 0);
		$condition_where = 'WHERE `o`.`platform`=0';
		if ($where_store) {
			$stores = D('Merchant_store')->field('store_id')->where($where_store)->select();
			foreach ($stores as $row) {
				$store_ids[] = $row['store_id'];
			}
			if ($store_ids) {
				$where['store_id'] = array('in', $store_ids);
				$condition_where .= ' AND o.store_id IN ('.implode(',',$store_ids).')';
			}
		}
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$where['real_orderid'] = htmlspecialchars($_GET['keyword']);
				$condition_where .= ' AND o.real_orderid = "'. htmlspecialchars($_GET['keyword']).'"';
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
				unset($where['orderid']);
				$where['order_id'] = $tmp_result['order_id'];
				$condition_where .= ' AND o.order_id = '. $tmp_result['order_id'];
			} elseif ($_GET['searchtype'] == 'name') {
				$where['username'] = htmlspecialchars($_GET['keyword']);
				$condition_where .=  ' AND o.username = "'.  htmlspecialchars($_GET['keyword']).'"';
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['userphone'] = htmlspecialchars($_GET['keyword']);
				$condition_where .= ' AND o.userphone = "'.  htmlspecialchars($_GET['keyword']).'"';
			}elseif ($_GET['searchtype'] == 'third_id') {
				$where['third_id'] =$_GET['keyword'];
				$condition_where .= ' AND o.third_id = "'.  $_GET['keyword'].'"';
			}

		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';

		if($status == 100){
			$where['paid'] = 0;
			$condition_where .= ' AND o.paid=0';
		}else if ($status != -1) {
			$where['status'] = $status;
			$condition_where .= ' AND o.status='.$status;
		}

		if($pay_type&&$pay_type!='balance'){
			$where['pay_type'] = $pay_type;
			$condition_where .= ' AND o.pay_type="'.$pay_type.'"';
		}else if($pay_type=='balance'){
			$where['_string'] = "(`balance_pay`<>0 OR `merchant_balance` <> 0 )";
			$condition_where .= ' AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 )';
		}

		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips("结束时间应大于开始时间");
			}
			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$where['_string'] =( $where['_string']?' AND ':''). " (create_time BETWEEN ".$period[0].' AND '.$period[1].")";
			$condition_where .=  " AND (o.create_time BETWEEN ".$period[0].' AND '.$period[1].")";
		}

		$count = D('Shop_order')->where($where)->count();

		$length = ceil($count / 1000);
		for ($i = 0; $i < $length; $i++) {
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);
			$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千个订单信息');
			$objActSheet = $objExcel->getActiveSheet();
			$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(40);
			$objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
			$objExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);

			$objActSheet->setCellValue('A1', '订单编号');
			$objActSheet->setCellValue('B1', '商品名称');
			$objActSheet->setCellValue('C1', '商品进价');
			$objActSheet->setCellValue('D1', '单价');
			$objActSheet->setCellValue('E1', '单位');
			$objActSheet->setCellValue('F1', '规格/属性');
			$objActSheet->setCellValue('G1', '数量');
			$objActSheet->setCellValue('H1', '商家名称');
			$objActSheet->setCellValue('I1', '店铺名称');
			$objActSheet->setCellValue('J1', '客户姓名');
			$objActSheet->setCellValue('K1', '客户电话');
			$objActSheet->setCellValue('L1', '客户地址');
			$objActSheet->setCellValue('M1', '订单总价');
			$objActSheet->setCellValue('N1', '平台优惠');
			$objActSheet->setCellValue('O1', '商家优惠');
			$objActSheet->setCellValue('P1', '实付总价');
			$objActSheet->setCellValue('Q1', '在线支付金额');
			$objActSheet->setCellValue('R1', $this->config['deliver_name'] . '费');
			$objActSheet->setCellValue('S1', '商家配送费');
			$objActSheet->setCellValue('T1', '支付时间');
			$objActSheet->setCellValue('U1', '送达时间');
			$objActSheet->setCellValue('V1', '订单状态');
			$objActSheet->setCellValue('W1', '支付情况');

			$sql = "SELECT  o.*, m.name AS merchant_name,d.name as good_name,d.price as good_price ,d.spec,d.unit,d.cost_price, d.num as good_num, s.name AS store_name FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=o.store_id INNER JOIN " . C('DB_PREFIX') . "merchant AS m ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "shop_order_detail AS d ON `d`.`order_id`=`o`.`order_id` ".$condition_where." ORDER BY o.order_id DESC LIMIT " . $i * 1000 . ",1000";

			$result_list = D()->query($sql);

			$tmp_id = 0;
			if (!empty($result_list)) {
				$index = 1;
				foreach ($result_list as $value) {
					if($tmp_id == $value['real_orderid']){
						$objActSheet->setCellValueExplicit('A' . $index, '');
						$objActSheet->setCellValueExplicit('B' . $index, $value['good_name']);
						$objActSheet->setCellValueExplicit('C' . $index, $value['cost_price']);
						$objActSheet->setCellValueExplicit('D' . $index, $value['good_price']);
						$objActSheet->setCellValueExplicit('E' . $index, $value['unit']);
						$objActSheet->setCellValueExplicit('F' . $index, $value['spec']);
						$objActSheet->setCellValueExplicit('G' . $index, $value['good_num']);
						$objActSheet->setCellValueExplicit('H' . $index,'');
						$objActSheet->setCellValueExplicit('I' . $index,'');
						$objActSheet->setCellValueExplicit('J' . $index, '');
						$objActSheet->setCellValueExplicit('K' . $index, '');
						$objActSheet->setCellValueExplicit('L' . $index, '');
						$objActSheet->setCellValueExplicit('M' . $index, '');
						$objActSheet->setCellValueExplicit('N' . $index, '');
						$objActSheet->setCellValueExplicit('O' . $index, '');
						$objActSheet->setCellValueExplicit('P' . $index, '');
						$objActSheet->setCellValueExplicit('Q' . $index, '');
						$objActSheet->setCellValueExplicit('R' . $index, '');
						$objActSheet->setCellValueExplicit('S' . $index, '');
						$objActSheet->setCellValueExplicit('T' . $index, '');
						$objActSheet->setCellValueExplicit('U' . $index, '');
						$objActSheet->setCellValueExplicit('V' . $index, '');
						$objActSheet->setCellValueExplicit('W' . $index, '');
						$index++;
					}else{
						$index++;
						$objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
						$objActSheet->setCellValueExplicit('B' . $index, $value['good_name']);
						$objActSheet->setCellValueExplicit('C' . $index, $value['cost_price']);
						$objActSheet->setCellValueExplicit('D' . $index, $value['good_price']);
						$objActSheet->setCellValueExplicit('E' . $index, $value['unit']);
						$objActSheet->setCellValueExplicit('F' . $index, $value['spec']);
						$objActSheet->setCellValueExplicit('G' . $index, $value['good_num']);
						$objActSheet->setCellValueExplicit('H' . $index, $value['merchant_name']);
						$objActSheet->setCellValueExplicit('I' . $index, $value['store_name']);
						$objActSheet->setCellValueExplicit('J' . $index, $value['username']);
						$objActSheet->setCellValueExplicit('K' . $index, $value['userphone'] . ' ');
						$objActSheet->setCellValueExplicit('L' . $index, $value['address'] . ' ');
						$objActSheet->setCellValueExplicit('M' . $index, floatval($value['total_price']));
						$objActSheet->setCellValueExplicit('N' . $index, floatval($value['balance_reduce']));
						$objActSheet->setCellValueExplicit('O' . $index, floatval($value['merchant_reduce']));
						$objActSheet->setCellValueExplicit('P' . $index, floatval($value['price']));
						$objActSheet->setCellValueExplicit('Q' . $index, floatval($value['payment_money']));

						if($value['is_pick_in_store']  == 0){
							$objActSheet->setCellValueExplicit('R' . $index, floatval($value['freight_charge']));
						}else{
							$objActSheet->setCellValueExplicit('R' . $index, 0);
						}

						if($value['is_pick_in_store']  == 1){
							$objActSheet->setCellValueExplicit('S' . $index, floatval($value['freight_charge']));
						}else{
							$objActSheet->setCellValueExplicit('S' . $index, 0);
						}

						$objActSheet->setCellValueExplicit('T' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
						$objActSheet->setCellValueExplicit('U' . $index, $value['use_time'] ? date('Y-m-d H:i:s', $value['use_time']) : '');
						$objActSheet->setCellValueExplicit('V' . $index, D('Shop_order')->status_list[$value['status']]);
						$objActSheet->setCellValueExplicit('W' . $index, D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']));
						$index++;
					}
					$tmp_id = $value['real_orderid'];

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

	public function shop_list()
	{
        $parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_shop_category = D(('shop_category'));
		$category = $database_shop_category->field(true)->where(array('cat_id' => $parentid))->find();
        $this->assign('category', $category);

	    $download = isset($_GET['download'])?$_GET['download']:0;
		if($_GET['status'] == 0){
			$where = "s.status=1";
		}else{
			$where = "s.status=0";
		}
		$where .= " AND s.have_shop=1 AND m.status = 1";//array('status' => 1);

		if(!empty($_GET['keyword'])){
			$where .= " AND s.name LIKE '%{$_GET['keyword']}%'";
		}
		if(!empty($_GET['preference_status'])){
			$where .= " AND sh.preference_status='1'";
		}
        if($parentid){
            $where .= " AND s.cat_fid=$parentid";
        }

		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$where .= " AND s.{$area_index} = '{$this->system_session['area_id']}'";
            if($this->system_session['street_id']){
                $where .= " AND s.street_id = '{$this->system_session['street_id']}'";
            }
		}
		$sql_count = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE {$where}";
		$mode = new model();
		$count = $mode->query($sql_count);
		$count = isset($count[0]['cnt']) ? $count[0]['cnt'] : 0;
		import('@.ORG.system_page');
		$p = new Page($count, 20);

		if($download){
            $sql = "SELECT `m`.`name` AS merchant_name,s.adress store_address, `s`.`name` AS store_name, `m`.`phone` AS merchant_phone, `s`.`phone` AS store_phone, `s`.`store_id`,`s`.`status`,`s`.`open_1`,`s`.`close_1`,`s`.`open_2`,`s`.`close_2`,`s`.`open_3`,`s`.`close_3`, `sh`.* FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE {$where} ORDER BY `sh`.`sort` DESC, `sh`.`store_id` DESC";
        }else{
            $sql = "SELECT `m`.`name` AS merchant_name,s.adress store_address, `s`.`name` AS store_name, `m`.`phone` AS merchant_phone, `s`.`phone` AS store_phone, `s`.`store_id`,`s`.`status`,`s`.`open_1`,`s`.`close_1`,`s`.`open_2`,`s`.`close_2`,`s`.`open_3`,`s`.`close_3`, `sh`.* FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE {$where} ORDER BY `sh`.`sort` DESC, `sh`.`store_id` DESC LIMIT {$p->firstRow}, {$p->listRows}";
        }
		$order_list = $mode->query($sql);

		$store['is_close'] = 1;
		$now_time = date('H:i:s');
		foreach($order_list as &$row){
			if ($row['open_1'] == '00:00:00' && $row['close_1'] == '00:00:00') {
				$row['time'] = '24小时营业';
				//$row['is_close'] = 0;
			} else {
				$row['time'] = substr($row['open_1'], 0, -3) . '~' . substr($row['close_1'], 0, -3);
				if ($row['open_1'] < $now_time && $now_time < $row['close_1']) {
					//$row['is_close'] = 0;
				}

				if ($row['open_2'] != '00:00:00' || $row['close_2'] != '00:00:00') {
					$row['time'] .= ',' . substr($row['open_2'], 0, -3) . '~' . substr($row['close_2'], 0, -3);
					if ($row['open_2'] < $now_time && $now_time < $row['close_2']) {
						//$row['is_close'] = 0;
					}
				}
				if ($row['open_3'] != '00:00:00' || $row['close_3'] != '00:00:00') {
					$row['time'] .= ',' . substr($row['open_3'], 0, -3) . '~' . substr($row['close_3'], 0, -3);
					if ($row['open_3'] < $now_time && $now_time < $row['close_3']) {
						//$row['is_close'] = 0;
					}
				}
			}
		}
        if ($download) {
            $deliver_name = $this->config['deliver_name'] ? $this->config['deliver_name'] : L_('平台配送');
            foreach ($order_list as &$v) {
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $v['last_time'] = date('Y-m-d H:i:s', $v['last_time']);
                if ($v['deliver_type'] == 0) {
                    $v['deliver_type'] = $deliver_name;
                } else if ($v['deliver_type'] == 1) {
                    $v['deliver_type'] = L_('商家配送');
                } else if ($v['deliver_type'] == 2) {
                    $v['deliver_type'] = L_('客户自提');
                } else if ($v['deliver_type'] == 3) {
                    $v['deliver_type'] = $deliver_name . L_('或自提');
                } else if ($v['deliver_type'] == 4) {
                    $v['deliver_type'] = L_('商家配送或自提');
                } else if ($v['deliver_type'] == 5) {
                    $v['deliver_type'] = L_('快递配送');
                }
            }
            $config = [
                'header' => [
                    ['title' => '商家名称', 'value_key' => 'merchant_name'],
                    ['title' => '店铺名称', 'value_key' => 'store_name'],
                    ['title' => '配送方式', 'value_key' => 'deliver_type'],
                    ['title' => '商家电话', 'value_key' => 'merchant_phone'],
                    ['title' => '店铺电话', 'value_key' => 'store_phone'],
                    ['title' => '店铺地址', 'value_key' => 'store_address'],
                    ['title' => '营业时间', 'value_key' => 'time'],
                    ['title' => '创建时间', 'value_key' => 'create_time'],
                    ['title' => '最后更新时间', 'value_key' => 'last_time'],
                    ['title' => '店铺排序', 'value_key' => 'sort'],
                ],
                'body' => $order_list,
                'file_name' => L_('店铺导出')
            ];
            $this->download_store_list($config);
        } else {
            $this->assign('order_list', $order_list);
            $this->assign('pagebar', $p->show());
            $this->display();
        }
	}

    public function download_store_list($config)
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $objPHPExcel = new \PHPExcel();
        $header = $config['header'];
        $body = $config['body'];
        $fileName = $config['file_name'] . '.xlsx';
        //头部，显示导出日期
        $cellNum = count($header);
        $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $objPHPExcel->getActiveSheet()
            ->getRowDimension(1)
            ->setRowHeight(35);
        if($config['sheet_title']){
            $objPHPExcel->getActiveSheet()->setTitle($config['sheet_title']);
        }
        //设置字体靠左显示，垂直居中、自动换行
        $objPHPExcel->getDefaultStyle()
            ->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->getDefaultStyle()
            ->getAlignment()
            ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getDefaultStyle()
            ->getAlignment()
            ->setWrapText(true);
        foreach ($header as $k => $v) {
            //表头
            $objPHPExcel->getActiveSheet()->setCellValue($char[$k] . '1', $v['title']);
            $objPHPExcel->getActiveSheet()
                ->getStyle($char[$k] . '1')
                ->getFont()
                ->setBold(true);
            $objPHPExcel->getActiveSheet()
                ->getColumnDimension($char[$k])
                ->setWidth(isset($v['width']) ? $v['width'] : 30);
            $objPHPExcel->getActiveSheet()
                ->getRowDimension(1)
                ->setRowHeight(35);
        }

        foreach ($body as $key => $value) {
            foreach ($header as $k2 => $v2) {
                $valueFormate = $value[$v2['value_key']];
                $objPHPExcel->setActiveSheetIndex()->setCellValueExplicit($char[$k2] . ($key + 2), $valueFormate,
                    \PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }

        //设置字体
        $objPHPExcel->getActiveSheet()
            ->getStyle('A1')
            ->getFont()
            ->setName('微软雅黑')
            ->setSize(13)
            ->setBold(false);

        unset($header, $body);
        ob_end_clean();
        ob_start();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Cache-Control: max-age=0');
        $objExcelWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objExcelWriter->save('php://output');
        exit();
    }

	public function edit_sort()
	{
	    $this->assign('bg_color','#F3F3F3');
	    $database = D('Merchant_store_shop');
	    $where['store_id'] = intval($_GET['store_id']);
	    $now_shop = $database->field(true)->where($where)->find();

	    if (empty($now_shop)) {
	        $this->frame_error_tips('没有找到该店铺信息！');
	    }
	    $now_store = D('Merchant_store')->field(true)->where($where)->find();
	    unset($now_store['sort']);
	    $now_shop = array_merge($now_shop, $now_store);

	    // 推荐爆款商品
	    if ($now_shop['recommend_good_ids']) {
	    	$recommend_good_list = D(_view("Shop_goods"))->where(array('goods_id'=>array('in',$now_shop['recommend_good_ids'])))->select();
	    	$now_shop['recommend_good_list'] = $recommend_good_list;
	    }

	    $this->assign('now_shop', $now_shop);
	    $this->display();
	}

	public function edit_amend()
	{
	    if (IS_POST) {
	        $store_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	        $sort = isset($_POST['sort']) ? intval($_POST['sort']) : 0;
	        $eleme_shopId= isset($_POST['eleme_shopId']) ? intval($_POST['eleme_shopId']) : 0;
			$preference_status = isset($_POST['preference_status']) ? intval($_POST['preference_status']) : 0;
			$preference_sort = isset($_POST['preference_sort']) ? intval($_POST['preference_sort']) : 0;
			$preference_reason = isset($_POST['preference_reason']) ? $_POST['preference_reason'] : '';
			$preference_desc = isset($_POST['preference_desc']) ? $_POST['preference_desc'] : '';
			$search_keywords = isset($_POST['search_keywords']) ? $_POST['search_keywords'] : '';
            $virtual_sale_count = isset($_POST['virtual_sale_count']) ? intval($_POST['virtual_sale_count']) : 0;//虚拟销量
	        if ($eleme_shopId) {
	            $eleme_shop = M('Eleme_shop')->field(true)->where(array('shopId' => $eleme_shopId))->find();
	            if (empty($eleme_shop)) {
	                $this->error('饿了么店铺ID有误，或该店铺没有授权给平台的饿了么应用！');
	                exit;
	            }
	            if ($eleme_shop['store_id'] && $eleme_shop['store_id'] != $store_id) {
	                $this->error('该饿了么店铺已经授权给其他店铺管理了！');
	                exit;
	            }
	            $eleme_shop['store_id'] || M('Eleme_shop')->where(array('id' => $eleme_shop['id']))->save(array('store_id' => $store_id));
	            M('Merchant_store')->where(array('store_id' => $store_id))->save(array('eleme_shopId' => $eleme_shopId));
	        }
            if ($store_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find()) {
				//如果优选，必须商家上传正方形LOGO
				if($preference_status || $_POST['recommend_status']){
					$now_store = M('Merchant_store')->field('`mer_id`')->where(array('store_id' => $store_id))->find();
					$now_merchant = M('Merchant')->field(true)->where(array('mer_id' => $now_store['mer_id']))->find();
					if(empty($now_merchant['logo'])){
						$this->error('请先在商家后台上传正方形LOGO');
						exit;
					}
				}
            	//$sale_count = $store_shop['sale_count'] - $store_shop['virtual_sale_count'] + $virtual_sale_count;
                $sale_count = $store_shop['sale_count'];
				$save_data = array('sort' => $sort, 'sale_count' => $sale_count, 'virtual_sale_count' => $virtual_sale_count,'preference_status'=>$preference_status,'preference_sort'=>$preference_sort,'preference_reason'=>$preference_reason,'preference_desc'=>$preference_desc,'search_keywords'=>$search_keywords, 'last_time' => time());
				$save_data['recommend_status'] = intval($_POST['recommend_status']);
				$save_data['recommend_sort'] = intval($_POST['recommend_sort']);
				$save_data['recommend_desc'] = $_POST['recommend_desc'];
				$save_data['recommend_pic'] = $_POST['recommend_pic'];
				$save_data['recommend_good_ids'] = implode(',', $_POST['recommend_good_ids']);

                //处理优选、爆款推荐有效时间
                $prefix = ['preference_', 'recommend_'];
                $keys = ['effect_date_type', 'start_date', 'end_date', 'week', 'effect_time_type', 'start_time', 'end_time'];
                foreach ($prefix as $v) {
                    foreach ($keys as $k) {
                        $save_data[$v . $k] = ($k == 'week') ? implode(',', $_POST[$v . $k]) : $_POST[$v . $k];
                    }
                    if ($_POST[$v . 'effect_date_type'] == 1) {
                        $save_data[$v . 'start_date'] = $save_data[$v . 'end_date'] = null;
                        $save_data[$v . 'week'] = '';
                    }
                    if ($_POST[$v . 'effect_time_type'] == 1) {
                        $save_data[$v . 'start_time'] = $save_data[$v . 'end_time'] = null;
                    }
                }

				if ($save_data['recommend_status'] && empty($_POST['recommend_good_ids'])) {
                    $this->error('请选择爆款推荐商品~');
				}

                if (D('Merchant_store_shop')->where(array('store_id' => $store_id))->save($save_data)) {
                    fdump([$store_id, $save_data],__FUNCTION__);
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！请重试~');
                }
            } else {
                $this->error('店铺信息错误！');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function sys_sort()
    {
        $systemGoodsSort = D('System_goods_sort');
        $count= $systemGoodsSort->where(array('status' => 1))->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $category_list = $systemGoodsSort->field(true)->order('`sort` DESC, `sort_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('category_list', $category_list);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function sort_add()
    {
        $this->assign('bg_color','#F3F3F3');
        $this->display();
    }

    public function sort_modify()
    {
        if(IS_POST){
            $systemGoodsSort = D('System_goods_sort');
            if($systemGoodsSort->data($_POST)->add()){
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    public function sort_edit()
    {
        $this->assign('bg_color','#F3F3F3');

        $systemGoodsSort = D('System_goods_sort');
        $where['sort_id'] = intval($_GET['sort_id']);
        $now_sort = $systemGoodsSort->field(true)->where($where)->find();
        if (empty($now_sort)) {
            $this->frame_error_tips('没有找到该分类信息！');
        }
        $this->assign('now_sort', $now_sort);
        $this->display();
    }

    public function sort_amend()
    {
        if (IS_POST) {
            $systemGoodsSort= D('System_goods_sort');
            $where = array('sort_id' => intval($_POST['sort_id']));
            unset($_POST['sort_id']);
            if ($systemGoodsSort->where($where)->save($_POST)) {
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function sort_del()
    {
        if (IS_POST) {
            $systemGoodsSort= D('System_goods_sort');
            $where['sort_id'] = intval($_POST['sort_id']);
            if ($obj = $systemGoodsSort->field(true)->where($where)->find()) {
                $t_list = D('System_goods')->field(true)->where($where)->select();
                if ($t_list) {
                    $this->error('该分类下有商品，先清空商品后才可以删除！');
                }
            }
            if ($systemGoodsSort->where($where)->delete()) {
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function sys_goods()
    {
        $systemGoodsSort= D('System_goods_sort');
        $sort_list = $systemGoodsSort->field(true)->where(array('status' => 1))->order('`sort_id` DESC')->select();
        $list = array();
        foreach ($sort_list as $row) {
            $list[$row['sort_id']] = $row;
        }
        $this->assign('sort_list', $sort_list);
        //$where = array('status' => 1);
        $status = $_GET['status']??3;
        $where = [];
        if(!empty($_GET['keyword'])){
            $where['name'] = array('like', '%' . htmlspecialchars(trim($_GET['keyword'])) . '%');
        }

        if (intval($_GET['sort_id'])) {
            $where['sort_id'] = intval($_GET['sort_id']);
        }

        if(in_array($status,array(0,1,2))){
            $where['status'] = $status;
        }
        

        $systemGoods = D('System_goods');
        $count= $systemGoods->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $goods_list = $systemGoods->where($where)->order('`goods_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $goods_image_class = new goods_image();
        $status_name = array('未审核','已审核','审核失败');
        foreach ($goods_list as &$goods) {
            $goods['sort_name'] = isset($list[$goods['sort_id']]['name']) ? $list[$goods['sort_id']]['name'] : '';
            if(!empty($goods['image'])){
                $tmp_pic_arr = explode(';', $goods['image']);
                $image = '';
                foreach ($tmp_pic_arr as $key => $value) {
                    if (empty($image)) $image = $goods_image_class->get_image_by_path($value, 's');
                }
                $goods['image'] = $image;
            }
            //浏览量
            $browse_num = (int)$goods['browse_num'];
            $goods['browse_num'] = $browse_num >= 10000  ? round(($browse_num / 10000 ), 1) . '万' : $browse_num;

            $goods['status_name'] = $status_name[$goods['status']]??'';
            $goods['last_time'] = $goods['last_time']?date('Y-m-d H:i:s',$goods['last_time']):'';
        }
        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        $this->assign('open_status', $open_status);

        $this->assign('goods_list', $goods_list);
        $this->assign('pagebar', $p->show());
        $this->assign('status',$status);
        $this->display();
    }

    public function goods_add()
    {
        $this->assign('bg_color', '#F3F3F3');
        $systemGoodsSort= D('System_goods_sort');
        $sort_list = $systemGoodsSort->field(true)->where(array('status' => 1))->order('`sort_id` DESC')->select();
        $this->assign('sort_list', $sort_list);
        $this->display();
    }

    public function goods_modify()
    {
        $param = array('size' => $this->config['group_pic_size']);
        $param['thumb'] = true;
        $param['imageClassPath'] = 'ORG.Util.Image';
        $param['thumbPrefix'] = 'm_,s_';
        $param['thumbMaxWidth'] = '900,450';
        $param['thumbMaxHeight'] = '500,250';
        $param['thumbRemoveOrigin'] = false;

        $image = D('Image')->handle($this->system_session['id'], 'sysgoods', 0, $param);
        if (! $image['error']) {
            $_POST = array_merge($_POST, $image['title']);
        } else {
            $this->frame_submit_tips(0, $image['message']);
        }
        $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
        $systemGoods = D('System_goods');
        $temp = $systemGoods->field(true)->where(array('number' => $_POST['number']))->find();
        if ($temp) {
            $this->frame_submit_tips(0, '商品的条形码已存在，请不要重复');
        }
        if ($systemGoods->field(true)->where(array('name' => $_POST['name']))->find()) {
            $this->error('商品名称已存在，请修改后再提交');
        }
        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        if($open_status==1){
            $_POST['status'] = 0;
        }
        if ($id = $systemGoods->data($_POST)->add()) {
            D('Image')->update_table_id('/upload/sysgoods/' . $_POST['image'], $id, 'sysgoods');
            $this->frame_submit_tips(1, '添加成功！');
        } else {
            $this->frame_submit_tips(0, '添加失败！请重试~');
        }
    }

    public function goods_edit()
    {
        $is_status = $_GET['is_status']??0;
        $this->assign('is_status', $is_status);
        $this->assign('bg_color', '#F3F3F3');

        $systemGoods = D('System_goods');
        $condition['goods_id'] = intval($_GET['goods_id']);
        $now_goods = $systemGoods->field("*")->where($condition)->find();
        if (empty($now_goods)) {
            $this->frame_error_tips('该商品不存在！');
        }
        if(!empty($now_goods['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_goods['image']);
            $image = '';
            foreach ($tmp_pic_arr as $key => $value) {
                if('http' === substr($value,0,4)){
					$image = $value;
				}else{
                    if (empty($image)) $image = $goods_image_class->get_image_by_path($value, 's');
                }
            }
            $now_goods['image'] = $image;
        }
        $this->assign('now_goods', $now_goods);

        $systemGoodsSort= D('System_goods_sort');
        $sort_list = $systemGoodsSort->field(true)->where(array('status' => 1))->order('`sort_id` DESC')->select();
        $this->assign('sort_list', $sort_list);
        $this->display();
    }

    public function goods_amend()
    {
        $systemGoods = D('System_goods');
        $condition['goods_id'] = intval($_POST['goods_id']);
        $now_goods = $systemGoods->field(true)->where($condition)->find();
        if (empty($now_goods)) {
            $this->frame_submit_tips(0, '数据有误');
        }
        $temp = $systemGoods->field(true)->where(array('number' => $_POST['number'], 'goods_id' => array('neq', intval($_POST['goods_id']))))->find();
        if ($temp) {
            $this->frame_submit_tips(0, '商品的条形码已存在，请不要重复');
        }

        if ($systemGoods->field(true)->where(array('name' => $_POST['name'], 'goods_id' => array('neq', intval($_POST['goods_id']))))->find()) {
            $this->error('商品名称已存在，请修改后再提交');
        }

        if ($_FILES['image']['error'] != 4) {
            $param = array('size' => $this->config['group_pic_size']);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
            $param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = '900,450';
            $param['thumbMaxHeight'] = '500,250';
            $param['thumbRemoveOrigin'] = false;
            $image = D('Image')->handle($this->system_session['id'], 'sysgoods', 0, $param);
            if (! $image['error']) {
                $_POST = array_merge($_POST, $image['title']);
            } else {
                $this->frame_submit_tips(0, $image['message']);
            }
        }

        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        if($open_status==1){
            $_POST['status'] = 0;
        }else{
            $_POST['status'] = 1;
        }
        $_POST['status_content'] = "";
        $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
        if ($systemGoods->data($_POST)->save()) {
            D('Image')->update_table_id('/upload/sysgoods/' . $_POST['image'], $_POST['goods_id'], 'sysgoods');
            if ($_POST['image']) {
//                 unlink('./upload/sysgoods/' . $now_goods['image']);
            }
            $this->frame_submit_tips(1, '编辑成功！');
        } else {
            $this->frame_submit_tips(0, '编辑失败！请重试~');
        }
    }

    public function goods_del()
    {
        $systemGoods = D('System_goods');
        if(isset($_POST['goods_id'])){
            $condition['goods_id'] = intval($_POST['goods_id']);
        }else if(isset($_POST['goodsIds'])){
            $condition['goods_id'] = array('in',$_POST['goodsIds']);
        }
//         $now_goods = $systemGoods->field(true)->where($condition)->find();
        if ($systemGoods->where($condition)->delete()) {
//             unlink('./upload/sysgoods/' . $now_goods['image']);
            $this->success('删除成功');
        } else {
            $this->error('删除失败！请重试~');
        }
    }

    //抓取店铺最新商品数据
    public function capture_store_goods(){
    	$now_time=time();
    	$this->assign('now_time',$now_time);
    	$this->display();
    }

    /*选择商家店铺*/
    public function store_list(){
    	$where=array();
    	$where['s.status']=1;
    	$where['s.have_shop']=1;
    	$name=I('name','','trim');
    	if(!empty($name)){
    		$where['s.name']=array('like','%'.$name.'%');
    	}
    	$count=M('Merchant_store')->alias('s')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on s.store_id=sh.store_id')->where($where)->count(1);
    	import('@.ORG.system_page');
    	$page=new Page($count,8);

    	$list=M('Merchant_store')->alias('s')->join('inner join '.C('DB_PREFIX').'merchant_store_shop sh on s.store_id=sh.store_id')->where($where)->order('s.mer_id desc,s.sort desc,s.store_id asc')->field('s.store_id,s.mer_id,s.name')->limit($page->firstRow,$page->listRows)->select();
		$mer_arr=M('Merchant')->getField('mer_id,name',true);
    	foreach($list as $k=>$v){
    		$list[$k]['mer_name']=$mer_arr[$v['mer_id']];
    	}
    	$this->assign('store_list',$list);
    	$this->assign('page',$page->show());
    	$this->assign('first',$page->firstRow);
    	$this->display();
    }

    /*抓取展示页*/
    public function capture_page(){
    	$stime=I('get.sdate','','strtotime');
    	$ftime=I('get.fdate','','strtotime');
    	$store_str=I('get.store_str','','trim');
    	$this->assign('stime',$stime);
    	$this->assign('ftime',$ftime);
    	$this->assign('store_str',$store_str);
    	$this->display();
    }

    /*抓取时间段内的指定店铺商品放入商品库*/
    public function capture_store_goods_api(){
    	$store_id=I('store_id','','trim');
    	if(empty($store_id)){
    		$this->error('缺少参数');
    	}
    	//判断店铺创建的分类是否存在
    	$sort_model=M('system_goods_sort');
    	$menu_info=$sort_model->where(array('store_id'=>$store_id))->find();
    	if(empty($menu_info)){
    		$store_name=M('merchant_store')->where(array('store_id'=>$store_id))->getField('name');
    		$data=array();
    		$data['name']=$store_name;
    		$data['fid']=0;
    		$data['status']=1;
    		$data['sort']=0;
    		$data['store_id']=$store_id;
    		$sort_id=$sort_model->data($data)->add();
    		if(!$sort_id){
    			$this->error('创建分类失败！');
    		}
    	}else{
    		$sort_id=$menu_info['sort_id'];
    	}
    	//写入获取的店铺商品信息
    	$where=array();
    	$where['store_id']=$store_id;
    	$stime=I('stime','','trim');
    	$ftime=I('ftime','','trim');
    	if($stime && $ftime){
    		$where['last_time']=array('between',array($stime,$ftime));
    	}elseif($stime){
    		$where['last_time']=array('egt',$stime);
    	}elseif($ftime){
    		$where['last_time']=array('elt',$stime);
    	}
    	$list=M('Shop_goods')->where($where)->field('goods_id,name,price,cost_price,unit,number,image')->select();
    	$data_all=array();
    	$model=M('system_goods');
    	$num_arr=$model->getField('number');
    	$num_arr=(array)$num_arr;
    	foreach ($list as $k => $v) {
    		if($v['number'] && in_array($v['number'], $num_arr)){
    			continue;
    		}
    		$key=$store_id.'_'.$v['goods_id'];
    		$info=$model->where(array('key'=>$key))->find();
    		if(isset($info)){
    			continue;
    		}
    		$img_arr=explode(';',$v['image']);
    		$arr=array();
    		$arr['sort_id']=$sort_id;
    		$arr['name']=$v['name'];
    		$arr['price']=$v['price'];
    		$arr['cost_price']=$v['cost_price'];
    		$arr['unit']=$v['unit'];
    		$arr['number']=$v['number'];
    		$arr['image']=$img_arr['0'];
    		$arr['key']=$key;
    		$arr['last_time']=$_SERVER['REQUEST_TIME'];
    		$data_all[]=$arr;
    	}
    	$count=count($data_all);
    	if($count>0){
    		$res2=$model->addAll($data_all);
    	}else{
    		$res2=1;
    	}
    	if($res2>0){
    		$this->success('抓取成功','',array('count'=>$count));
    	}else{
    		$this->error('抓取失败！');
    	}
    }


    public function goods_import()
    {
        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);
                $upload_dir = './upload/excel/sysgoods/' . date('Ymd') . '/';
                if (! is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 10 * 1024 * 1024;
                $upload->allowExts = array('xls', 'xlsx');
                $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
                $upload->savePath = $upload_dir;
                $upload->thumb = false;
                $upload->thumbType = 0;
                $upload->imageClassPath = '';
                $upload->thumbPrefix = '';
                $upload->saveRule = 'uniqid';
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); // 文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);

                    $systemGoods = D('System_goods');
                    if (!empty($result) && is_array($result)) {
                        unset($result[1], $result[2]);
                        $last_user_id = 0;
                        $err_msg = '';
                        foreach ($result as $kk => $vv) {
                            if ($vv['A'] === null &&
                                $vv['B'] === null &&
                                $vv['C'] === null &&
                                $vv['D'] === null &&
                                $vv['E'] === null &&
                                $vv['F'] === null)
                                continue;

//                             if (empty($vv['A'])) {
//                                 $err_msg = '请填写商品条形码！';
//                                 continue;
//                             }
                            if (empty($vv['B'])) {
                                $err_msg .= '第' . $kk . '行未填写商品名称！<br/>';
                                continue;
                            }
                            if (empty($vv['C'])) {
                                $err_msg .= '第' . $kk . '行未填写商品单位！<br/>';
                                continue;
                            }
                            if ($vv['D'] < 0) {
                                $err_msg .= '第' . $kk . '行未填写零售价('.C('config.Currency_txt').')！<br/>';
                                continue;
                            }

                            if (empty($vv['F'])) {
                                $err_msg .= '第' . $kk . '行未填写所属分类ID！<br/>';
                                continue;
                            }

                            $number = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            $name = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            $sort_id = intval(trim($vv['F']));

                            if ($number && ($systemGoods->where(array('number' => $number))->find())) {
                                $err_msg .= '第' . $kk . '行的商品条形码【' . $number. '】已存在！<br/>';
                                continue;
                            }

                            if ($systemGoods->where(array('name' => $name))->find()) {
                                $err_msg .= '第' . $kk . '行的商品名【' . $name . '】已存在！<br/>';
                                continue;
                            }

                            $tmpdata = array();

                            $tmpdata['number'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            $tmpdata['name'] = $name;
                            $tmpdata['unit'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $tmpdata['price'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            $tmpdata['cost_price'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            $tmpdata['sort_id'] = intval($sort_id);
//                             $tmpdata['image'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);

                            $tmpdata['last_time'] = time();

                            $thisGoodsId = $systemGoods->data($tmpdata)->add();
                            if (!$thisGoodsId) {
                                $err_msg .= '第' . $kk . '行的数据导入失败！<br/>';
                            }
                        }
                        if (empty($err_msg)) {
                            $this->frame_submit_tips(1, '导入成功');
                            exit();
                        } else {
                            exit('导入失败！原因：<br/>' . $err_msg);
                        }
                    }
                } else {
                    exit($upload->getErrorMsg());
                }
            }
            exit('文件上传失败');
        } else {
            $this->display();
        }
    }

    public function unzip()
    {
        //限制上传大小，单位M
        $maxSize = 100;
        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                @set_time_limit(0);
                $upload_dir = './upload/sysgoods_temp/';
                if(!is_dir($upload_dir)){
                    mkdir($upload_dir, 0777, true);
                }

                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = $maxSize*1024*1024;
                $upload->allowExts = array('zip');
                $upload->savePath = $upload_dir;
                $upload->saveRule = 'uniqid';
                if(!$upload->upload()){
                    $this->frame_submit_tips(0, '文件上传失败！错误提示：' . $upload->getErrorMsg());
                    exit;
                }
                $uploadList = $upload->getUploadFileInfo();
                $localZipName = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                $zipFileNameArr = explode('.',$_FILES['file']['name']);
                $zipFileName = $zipFileNameArr[0];
//                 $zipFileName = iconv('GB2312', 'UTF-8', $zipFileName);
                import('@.ORG.PclZip');
                $archive = new PclZip($localZipName);
                if($archive->extract(PCLZIP_OPT_PATH, $upload_dir, PCLZIP_OPT_REPLACE_NEWER) == 0){
                    $this->frame_submit_tips(0, '文件解压失败！错误提示：'.$archive->errorInfo(true));
                }
                unlink($localZipName);
                //$upload_dir .= $zipFileName;
//                 $upload_dir = iconv("UTF-8", "gb2312", $upload_dir);
                // if(!is_dir($upload_dir)){
                //     $this->frame_submit_tips(0, '文件上传失败');
                //     exit();
                // }
                $handler = opendir($upload_dir);
                $systemGoodsDB = M('System_goods');
                import('ORG.Util.Image');
                //水印
                $watermarkfile =  C('config.site_water_mark');//'./upload/watermark/home.png';

                //将oss上的图片下载下来
                $file_handle = new file_handle();
                $file_handle->download($watermarkfile);
				$watermarkfile = $file_handle->get_path($watermarkfile);

                if ($watermarkfile) {
                    $pt = pathinfo($watermarkfile);
                    $pu = parse_url($watermarkfile);
                    if (isset($pu['path']) && isset($pt['extension'])) {
                        $watermarkfile = '.' . $pu['path'];
                        $watermarksize = @getimagesize($watermarkfile);
                        $watermark = array();
                        $watermark['watermarkstatus'] = C('config.site_water_mark_pos');
                        $watermark['watermarktype'] = isset($pt['extension']) ? $pt['extension'] : 'png';//'png';
                        $watermark['watermarkfile'] = $watermarkfile;
                        $watermark['watermarkminwidth'] = $watermarksize[0];
                        $watermark['watermarkminheight'] = $watermarksize[1];
                        $watermark['watermarkquality'] = 90;
                        $watermark['watermarktrans'] = 100;
                        $image_water_mark = new image_water_mark();
                    }
                }

                import('ORG.Util.Dir');
                $file_handle = new file_handle();
                fdump_api(['上传图片4' => $upload_dir],'image/upload',1);
                $result = [];
                while (($filename = readdir($handler)) !== false) {
                    fdump_api(['上传图片5' => $filename],'image/upload',1);
//                     $encode = mb_detect_encoding($filename);
//                     if ($encode != 'UTF-8') $filename = iconv($encode, "UTF-8", $filename);
                    if ($filename != '.' && $filename != '..') {//文件夹文件名字为'.'和‘..’，不要对他们进行操作
                        $original_filename = $upload_dir. '/' . $filename;

                        if (!is_dir($original_filename)) {
                            $file = pathinfo($original_filename);
                            if (!in_array(strtolower($file['extension']),array('gif','jpg','jpeg','bmp','png'))) {
                                unlink($original_filename);
                                $result[] = $file['basename'] . '图片格式不正确';
                                continue;
                            }

                            $number = trim($file['filename']);
							$number = str_replace(' ','',$number);
							$number = str_replace($zipFileName.'/','',$number);
                            fdump_api(['上传图片2' => $number],'image/upload',1);
                            if ($goods = $systemGoodsDB->field(true)->where(array('number|goods_id' => $number))->find()) {
                                fdump_api(['上传图片3' => [$goods, $systemGoodsDB->getLastSql()]],'image/upload',1);
                                $img_mer_id = sprintf("%09d", $goods['goods_id']);
                                $rand_num = 'sysgoods_' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
                                $toPath = "./upload/sysgoods/{$rand_num}/";
                                if(!is_dir($toPath)){
                                    mkdir($toPath, 0777, true);
                                }

                                $newName = substr(md5($goods['name']), 8, 16) . '.' . $file['extension'];
//                                 if ($encode != 'UTF-8') {
//                                     rename(iconv("UTF-8", $encode, $original_filename), $toPath . $newName);
//                                 } else {
                                    rename($original_filename, $toPath . $newName);
//                                 }

                                $file_handle->upload($toPath . $newName);
                                //$systemGoodsDB->where(array('goods_id' => $goods['goods_id']))->save(array('image' => $rand_num . ',' . $newName));
                                $systemGoodsDB->where(array('goods_id' => $goods['goods_id']))->save(array('image' => ltrim($toPath . $newName,'.')));

                                $image = getimagesize($toPath . $newName);
                                if (false !== $image) {
                                    $thumbWidth = explode(',', '900,450');
                                    $thumbHeight = explode(',', '900,450');
                                    $thumbPrefix = explode(',', 'm_,s_');
                                    $thumbSuffix = explode(',', '');
                                    $thumbFile = explode(',', '');
                                    $thumbPath = $toPath;
                                    $thumbExt = $file['extension']; // 自定义缩略图扩展名
                                    for ($i = 0, $len = count($thumbWidth); $i < $len; $i ++) {
                                        if (!empty($thumbFile[$i])) {
                                            $thumbname = $thumbFile[$i];
                                        } else {
                                            $prefix = isset($thumbPrefix[$i]) ? $thumbPrefix[$i] : $thumbPrefix[0];
                                            $suffix = isset($thumbSuffix[$i]) ? $thumbSuffix[$i] : $thumbSuffix[0];
                                            $thumbname = $prefix . basename($toPath . $newName, '.' . $file['extension']) . $suffix;
                                        }
                                        Image::thumb($toPath . $newName, $thumbPath . $thumbname . '.' . $thumbExt, '', $thumbWidth[$i], $thumbHeight[$i], true);
										if ($watermarkfile) {
											$image_water_mark->Watermark($thumbPath . $thumbname . '.' . $thumbExt, $watermark);
										}
                                    }
                                }
                            }else{
                                $result[] = $file['basename'] . '商品不存在';
                            }
//                             if ($encode != 'UTF-8') {
//                                 unlink(iconv("UTF-8", $encode, $original_filename));
//                             } else {
                                unlink($original_filename);
//                             }
                        } else {
//                             if ($encode != 'UTF-8') {
//                                 rmdir(iconv("UTF-8", $encode, $original_filename));
//                             } else {
                                Dir::delDir($original_filename, false);
//                             }
                        }
                    }
					if ($watermarkfile) {
						$image_water_mark->Watermark($toPath . $newName, $watermark);
					}
                }
//                 rmdir(iconv("UTF-8", "gb2312", $upload_dir));
                @closedir($upload_dir);
               
            }
            if ($result) {
                $failLog = RUNTIME_PATH . 'fail.txt';
                file_put_contents($failLog, "失败清单" . PHP_EOL . implode(PHP_EOL, $result));
                $this->error('文件上传失败', C('config.site_url') . '/runtime/fail.txt', 1);
            } else {
                $this->frame_submit_tips(1, '导入成功');
            }
            exit();
        } else {
            $this->assign('maxSize',$maxSize);
            $this->display();
        }
    }

    public function merchant_goods_unzip()
    {
        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                @set_time_limit(0);
                $upload_dir = './upload/mergoods_temp/';
                if(!is_dir($upload_dir)){
                    mkdir($upload_dir, 0777, true);
                }

                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 100*1024*1024;
                $upload->allowExts = array('zip');
                $upload->savePath = $upload_dir;
                $upload->saveRule = 'uniqid';
                if(!$upload->upload()){
                    $this->frame_submit_tips(0, '文件上传失败！错误提示：' . $upload->getErrorMsg());
                    exit;
                }
                $uploadList = $upload->getUploadFileInfo();
                $localZipName = $uploadList['0']['savepath'] . $uploadList['0']['savename'];

                import('@.ORG.PclZip');
                $archive = new PclZip($localZipName);
                if($archive->extract(PCLZIP_OPT_PATH, $upload_dir, PCLZIP_OPT_REPLACE_NEWER) == 0){
                    $this->frame_submit_tips(0, '文件解压失败！错误提示：'.$archive->errorInfo(true));
                }
                unlink($localZipName);

                $handler = opendir($upload_dir);
                $shopGoodsDB = M('Shop_goods');
                import('ORG.Util.Image');
                //水印
                $watermarkfile =  C('config.site_water_mark');

                if ($watermarkfile) {
                    //将oss上的图片下载下来
					$file_handle = new file_handle();
					$file_handle->download($watermarkfile);
					$watermarkfile = $file_handle->get_path($watermarkfile);

                    $pt = pathinfo($watermarkfile);
                    $pu = parse_url($watermarkfile);
                    if (isset($pu['path']) && isset($pt['extension'])) {
                        $watermarkfile = '.' . $pu['path'];
                        $watermarksize = @getimagesize($watermarkfile);
                        $watermark = array();
                        $watermark['watermarkstatus'] = C('config.site_water_mark_pos');
                        $watermark['watermarktype'] = isset($pt['extension']) ? $pt['extension'] : 'png';//'png';
                        $watermark['watermarkfile'] = $watermarkfile;
                        $watermark['watermarkminwidth'] = $watermarksize[0];
                        $watermark['watermarkminheight'] = $watermarksize[1];
                        $watermark['watermarkquality'] = 90;
                        $watermark['watermarktrans'] = 100;
                        $image_water_mark = new image_water_mark();
                    }
                }

                while (($filename = readdir($handler)) !== false) {
                    if ($filename != '.' && $filename != '..') {//文件夹文件名字为'.'和‘..’，不要对他们进行操作
                        $original_filename = $upload_dir. '/' . $filename;

                        if (!is_dir($original_filename)) {
                            $file = pathinfo($original_filename);
                            if (!in_array(strtolower($file['extension']), array('gif','jpg','jpeg','bmp','png'))) {
                                unlink($original_filename);
                                continue;
                            }

                            $number = trim($file['filename']);
							$number = str_replace(' ','',$number);

                            if ($goods = $shopGoodsDB->field(true)->where(array('goods_id' => $number))->find()) {
                                if($_POST['save_type'] && $goods['image']){
                                    continue;
                                }
                                $img_mer_id = sprintf("%09d", $goods['goods_id']);
                                $rand_num = 'mergoods_' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
                                $toPath = "./upload/goods/{$rand_num}/";
                                if(!is_dir($toPath)){
                                    mkdir($toPath, 0777, true);
                                }

                                $newName = substr(md5($goods['goods_id']), 8, 16) . '.' . $file['extension'];
                                rename($original_filename, $toPath . $newName);

                                $shopGoodsDB->where(array('goods_id' => $goods['goods_id']))->save(array('image' => ltrim($toPath . $newName,'.')));

                            }

                            unlink($original_filename);
                        } else {
                            rmdir($original_filename);
                        }
                    }
					if ($watermarkfile) {
						$image_water_mark->Watermark($toPath . $newName, $watermark);
					}
                }

                @closedir($upload_dir);
                $this->frame_submit_tips(1, '导入成功');
                exit();
            }
            $this->frame_submit_tips(0, '文件上传失败');
            exit();
        } else {
            $this->display();
        }
    }


    public function area()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($discount = D('Shop_discount')->field(true)->where(array('id' => $id))->find()) {
            if ($discount['mer_id'] || $discount['store_id']) {
                $this->error_tips('此优惠不属于平台优惠');
            }

            if ($discount['use_area'] == 0) {
                $this->error_tips('此优惠无需指定区域');
            }
            if (IS_POST) {
                $areaArray = $_POST['areaIds'];
                $data = array();
                $street_ids = [];
                foreach ($areaArray as $row) {
                    $arr = explode('-', $row);
                    if (count($arr) == 1) {
                        $data[$arr[0]] = 1;
                    } elseif (count($arr) == 2) {
                        unset($data[$arr[0]]);
                        $data[$arr[1]] = 2;
                    } elseif (count($arr) == 3) {
                        unset($data[$arr[0]], $data[$arr[1]]);
                        $data[$arr[2]] = 3;
                    } elseif (count($arr) == 4) {
                        unset($data[$arr[0]], $data[$arr[1]]);
                        $data[$arr[2]] = 3;
                        $street_ids[] = $arr[3];
                    }
                }
                if (empty($data)) {
                    $this->error_tips('请选择区域');
                }

                // 查询区域等级
                $area_list = D('Area')->where(array('area_id'=>array('in',array_keys($data))))->select();
                $area_level_arr = array();
                foreach ($area_list as $key => $value) {
                	$area_level_arr[$value['area_id']] = $value['area_type'];
                }

                $discountAreaDB = D('Shop_discount_area');
                $discountStreetDB = D('Shop_discount_street');
                //删除已经设置的区域
                $discountAreaDB->where(array('did' => $id))->delete();
                $provinceIds = array();
                $cityIds = array();
                $areaIds = array();
                foreach ($data as $aid => $level) {
                    if ($level == 1) {
                        $provinceIds[] = $aid;
                    } elseif ($level == 2) {
                        $cityIds[] = $aid;
                    } elseif ($level == 3) {
                        $areaIds[] = $aid;
                    }
                    $discountAreaDB->add(array('did' => $id, 'aid' => $aid, 'level' => $area_level_arr[$aid]));
                }

                $discountStreetDB->where(['did' => $id])->delete();
                if ($street_ids) {
                    $discount_street = [];
                    foreach ($street_ids as $sid) {
                        $discount_street[] = ['street_id' => $sid, 'did' => $id];
                    }
                    $discountStreetDB->addAll($discount_street);
                }

                //删除已经设置的商家，但是不在新设置的区域内
                $merchantWhere = 'd.did=' . $id . ' AND m.status=1';
                $tempWhere = '';
                if ($provinceIds) {
                    $tempWhere = 'province_id NOT IN (' . implode(',', $provinceIds) . ')';
                }
                if ($cityIds) {
                    $tempWhere = empty($tempWhere) ? 'city_id NOT IN (' . implode(',', $cityIds) . ')': $tempWhere . ' AND city_id IN (' . implode(',', $cityIds) . ')';
                }
                if ($areaIds) {
                    $tempWhere = empty($tempWhere) ? 'area_id NOT IN (' . implode(',', $areaIds) . ')' : $tempWhere . ' AND area_id IN (' . implode(',', $areaIds) . ')';
                }
                if (!empty($tempWhere)) {
                    $merchantWhere .= ' AND (' . $tempWhere . ')';
                }
                $sql = "SELECT d.mer_id, d.id FROM" . C('DB_PREFIX') . 'shop_discount_merchant AS d INNER JOIN ' . C('DB_PREFIX') . 'merchant AS m ON d.mer_id=m.mer_id WHERE ' . $merchantWhere;
                $deleteList = D()->query($sql);
                $discountMerchantDB = D('Shop_discount_merchant');
                foreach ($deleteList as $del) {
                    $discountMerchantDB->where(array('id' => $del['id'], 'mer_id' => $del['mer_id']))->delete();
                }
                $this->success('设置成功',U("discount"));
            } else {
                $where = array('area_type' => array('lt', 4), 'is_open' => 1);
                if ($this->system_session['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $where['area_id'] = $this->system_session['area_id'];
                    }  else {
                        $where['area_pid'] = $this->system_session['area_id'];
                    }
                }
                $areaList = D('Area')->field('area_id, area_pid, area_name')->where($where)->select();
                $list = D('Shop_discount_area')->field('aid')->where(array('did' => $id))->select();
                $areaIds = array();
                foreach ($list as $row) {
                    $areaIds[] = $row['aid'];
                }

                $tmpMap = array();
                foreach ($areaList as $item) {
                    $item['select'] = 0;
                    if (in_array($item['area_id'], $areaIds)) {
                        $item['select'] = 1;
                    }
                    $tmpMap[$item['area_id']] = $item;
                }

                $list = array();
                foreach ($areaList as $item) {
                    if (isset($tmpMap[$item['area_pid']])) {
                        $tmpMap[$item['area_pid']]['son_list'][$item['area_id']] = &$tmpMap[$item['area_id']];
                    } else {
                        $list[$item['area_id']] = &$tmpMap[$item['area_id']];
                    }
                }

                foreach ($list as $key => $value) {
            		foreach ($value['son_list'] as $k => $val) {
            			foreach($val['son_list'] as $son){
            				if($son['select'] == 1){
            					$list[$key]['son_list'][$k]['select'] = 1;
            					$list[$key]['select'] = 1;
            				}
            			}
            			if($val['select'] == 1){
        					$list[$key]['select'] = 1;
        				}
            		}
                }

                //区域增加乡镇
                $street_mod = M('Area_street');
                $street_ids = M('Shop_discount_street')->where(array('did' => $id))->getField('street_id', true);
                foreach ($list as $k1 => $v1) {
                    foreach ($v1['son_list'] as $k2 => $v2) {
                        foreach ($v2['son_list'] as $k3 => $v3) {
                            $street = $street_mod->where(['area_pid' => $v3['area_id']])->field('area_id, `area_pid`, `area_name`')->where(['is_open' => 1])->select();
                            $street = array_map(function ($item) use ($street_ids) {
                                $item['select'] = in_array($item['area_id'], $street_ids) ? 1 : 0;
                                return $item;
                            }, $street);
                            $list[$k1]['son_list'][$k2]['son_list'][$k3]['son_list'] = $street;
                        }
                    }
                }
                $this->assign('area_list', $list);
                $this->display();
            }
        } else {
            $this->error_tips('此优惠不存在');
        }
    }

    public function merchant()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($discount = D('Shop_discount')->field(true)->where(array('id' => $id))->find()) {
            if ($discount['mer_id'] || $discount['store_id']) {
                $this->error_tips('此优惠不属于平台优惠');
            }
            if ($discount['use_type'] == 0) {
                $this->error_tips('此优惠无需指定商家');
            }
            $status = isset($_GET['status']) ? intval($_GET['status']) : 0;

            $merchantWhere = 'm.status=1';

            if (! empty($_GET['keyword'])) {
                if ($_GET['searchtype'] == 'mer_id') {
                    $merchantWhere .= ' AND m.mer_id=' . $_GET['keyword'];
                } elseif ($_GET['searchtype'] == 'account') {
                    $merchantWhere .= ' AND m.account LIKE \'%' . $_GET['keyword'] . '%\'';
                } elseif ($_GET['searchtype'] == 'name') {
                    $merchantWhere .= ' AND m.name LIKE \'%' . $_GET['keyword'] . '%\'';
                } elseif ($_GET['searchtype'] == 'phone') {
                    $merchantWhere .= ' AND m.phone LIKE \'%' . $_GET['keyword'] . '%\'';
                }elseif ($_GET['searchtype'] == 'store_name') {
                    $merchantWhere .= ' AND s.name LIKE \'%' . $_GET['keyword'] . '%\'';
                }elseif ($_GET['searchtype'] == 'store_id') {
                    $merchantWhere .= ' AND s.store_id=' . $_GET['keyword'];
                }
            }

            if ($discount['use_area']) {
            	$areaList = D('Shop_discount_area')->join(' as sd left join '.C('DB_PREFIX').'area as a on sd.aid=a.area_id')->field('sd.id,sd.did,sd.aid,a.area_type as level')->where(array('sd.did' => $id))->select();
                if (empty($areaList)) {
                    $this->error_tips('请先指定使用的区域');
                }
                $provinceIds = array();
                $cityIds = array();
                $areaIds = array();
                foreach ($areaList as $area) {
                    if ($area['level'] == 1) {
                        $provinceIds[] = $area['aid'];
                    } elseif ($area['level'] == 2) {
                        $cityIds[] = $area['aid'];
                    } elseif ($area['level'] == 3) {
                        $areaIds[] = $area['aid'];
                    }
                }
                $tempWhere = '';
                if ($provinceIds) {
                    $tempWhere = 's.province_id IN (' . implode(',', $provinceIds) . ')';
                }
                if ($cityIds) {
                    $tempWhere = empty($tempWhere) ? 's.city_id IN (' . implode(',', $cityIds) . ')': $tempWhere . ' OR s.city_id IN (' . implode(',', $cityIds) . ')';
                }
                if ($areaIds) {
                    $tempWhere = empty($tempWhere) ? 's.area_id IN (' . implode(',', $areaIds) . ')' : $tempWhere . ' OR s.area_id IN (' . implode(',', $areaIds) . ')';
                }
                if (!empty($tempWhere)) {
                    $merchantWhere .= ' AND (' . $tempWhere . ')';
                }
            } else {
                if ($this->system_session['area_id']) {
                    $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
                    $merchantWhere .= ' AND s.' . $area_index . '=' . $this->system_session['area_id'];
                }
            }

            if ($status != 0) {
                $list = D('Shop_discount_merchant')->field('store_ids')->where(array('did' => $id))->select();
                $storeIdArr = array();
                foreach ($list as $l) {
                    $store_ids = explode(',',$l['store_ids']);
                    foreach ($store_ids as $_k => $_id) {
                    	if (!$_id) {
                    		unset($store_ids[$_k]);
                    	}
                    }
                    !empty($store_ids) && $storeIdArr = array_merge($storeIdArr,$store_ids);
                }
                if ($storeIdArr) {
                    if ($status == 1) {
                        $merchantWhere .= ' AND s.store_id IN (' . implode(',', $storeIdArr) . ')';
                    } else {
                        $merchantWhere .= ' AND s.store_id NOT IN (' . implode(',', $storeIdArr) . ')';
                    }
                }
            }

            $databaseMerchant = D('Merchant');
            $sqlCount = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . "merchant_store AS s LEFT JOIN  " . C('DB_PREFIX') . 'merchant AS m on s.mer_id=m.mer_id LEFT JOIN ' . C('DB_PREFIX') . 'shop_discount_merchant AS sm ON sm.did=' . $id . ' AND m.mer_id=sm.mer_id WHERE ' . $merchantWhere;
            // var_dump($sqlCount);
            $countList = $databaseMerchant->query($sqlCount);
            $count = isset($countList[0]['cnt']) ? intval($countList[0]['cnt']) : 0;

            import('@.ORG.system_page');
            $p = new Page($count, 15);
            $sql = "SELECT s.*,m.name as merchant_name, sm.id AS did,sm.store_ids FROM " . C('DB_PREFIX') . "merchant_store AS s LEFT JOIN  " . C('DB_PREFIX') . 'merchant AS m on s.mer_id=m.mer_id LEFT JOIN ' . C('DB_PREFIX') . 'shop_discount_merchant AS sm ON sm.did=' . $id . ' AND m.mer_id=sm.mer_id WHERE s.status <>4 AND ' . $merchantWhere . ' ORDER BY sm.did desc,m.mer_id DESC LIMIT ' . $p->firstRow . ',' . $p->listRows;

            $storeList = $databaseMerchant->query($sql);

			//查找有没有指定店铺
			if($storeList){
				$store_ids_key = [];
				$store_ids = [];
				$store_names = [];
				foreach($storeList as $key=>$value){
					$store_ids_arr = explode(',',$value['store_ids']);
					if($store_ids_arr){
						if (in_array($value['store_id'], $store_ids_arr)) {
							$storeList[$key]['is_bind'] = 1;
						}
						// foreach($store_ids_arr as $v){
						// 	if($v){
						// 		$store_ids_key[$v] = $key;
						// 		$store_ids[] = $v;
						// 	}
						// }
					}
				}
				// if($store_ids){
				// 	$store_list = array_column(M('Merchant_store')->field('`store_id`,`name` as `store_name`')->where(['store_id'=>['in',$store_ids]])->select(),'store_name','store_id');
				// 	foreach($store_list as $key => $value){
				// 		$store_names[$store_ids_key[$key]][] = $value;
				// 	}
				// 	foreach($store_names as $key => $value){
				// 		$storeList[$key]['store_names'] = implode('，',$value);
				// 	}
				// }
			}

            $this->assign('storeList', $storeList);
            $this->assign('pagebar', $p->show());
            $this->assign('did', $id);
            $this->assign('status', $status);
            $this->display();
        } else {
            $this->error_tips('此优惠不存在');
        }
    }

	public function discount_choose_store(){
		//获取该商家存在快店的门店
		$where = array(
			's.mer_id' => $_GET['mer_id'],
			's.status' => 1,
			's.have_shop' => 1
		);
		$order = "`s`.`sort` DESC,`s`.`store_id` ASC";
		$sql .= " LIMIT {$p->firstRow}, {$p->listRows}";
		$store_list = M()->table(C('DB_PREFIX').'merchant_store'.' as s')->join("inner join ".C('DB_PREFIX')."merchant_store_shop as ss on ss.store_id=s.store_id")->field("s.store_id,s.name as store_name")->where($where)->order($order)->select();

		$store_ids = explode(',',$_GET['store_ids']);

		$this->assign('store_ids', $store_ids);
		$this->assign('store_list', $store_list);
		$this->assign('bg_color', '#F3F3F3');
		$this->display();
	}

    public function change()
    {
        $did = isset($_POST['did']) ? intval($_POST['did']) : 0;
        $merIds = isset($_POST['merIds']) ? array_unique($_POST['merIds']) : '';
        $storeIds = isset($_POST['storeIds']) ? $_POST['storeIds'] : '';
        $type = isset($_POST['type']) ? intval($_POST['type']) : 0;
        if (empty($merIds)||empty($storeIds)) {
            $this->error('请选择店铺');
        }

        if ($discount = D('Shop_discount')->field(true)->where(array('id' => $did))->find()) {
            if ($discount['mer_id'] || $discount['store_id']) {
                $this->error('此优惠不属于平台优惠');
            }
            if ($discount['use_type'] == 0) {
                $this->error('此优惠无需指定商家');
            }
        }

        $discountMerchantDB = D('Shop_discount_merchant');
        // $discountMerchantDB->where(array('did' => $did, 'mer_id' => array('in', $merIds)))->delete();
        $discount_merchant_list = $discountMerchantDB->field(true)->where(array('did' => $did, 'mer_id' => array('in', $merIds)))->select();
        if ($type == 0) {
        	// 删除店铺
        	$delete_id_arr = array();
        	$save_id_arr = array();
        	foreach ($discount_merchant_list as $key => $value) {
        		// 原有的店铺id
        		$_store_ids = explode(',', $value['store_ids']);

        		// 要删除的店铺id
        		$_store_ids_del = array();
        		$storeIds[$value['mer_id']] && $storeIds_mer = explode(',',trim($storeIds[$value['mer_id']],','));
        		if ($storeIds_mer && $_store_ids_del = array_intersect($_store_ids,$storeIds_mer)) {
        			$_store_ids = array_diff($_store_ids,$_store_ids_del);
        		}

        		if ($_store_ids) {
        			$discountMerchantDB->where(array('did' => $did, 'mer_id' => $value['mer_id']))->setField('store_ids',implode(',',$_store_ids));
        		}else{
        			$discountMerchantDB->where(array('did' => $did, 'mer_id' => $value['mer_id']))->delete();
        		}
        	}
			D('Config')->clearDataCacheDir('shop_discount');
            $this->success('解除指定成功');
        } else {
        	// 添加店铺

        	// 修改记录
        	$save_id_arr = array();
        	foreach ($discount_merchant_list as $key => $value) {
        		// 原有的店铺id
        		$_store_ids = explode(',', $value['store_ids']);
        		foreach ($_store_ids as $_k => $id) {
        			if (!$id) {
        				unset($_store_ids[$_k]);
        			}
        		}

        		// 要添加的店铺id
        		$_store_ids_add = array();

        		$storeIds[$value['mer_id']] && $_store_ids_add = explode(',',trim($storeIds[$value['mer_id']],','));
        		if ($_store_ids_add) {
        			$_store_ids = array_unique(array_merge($_store_ids , $_store_ids_add));
        			$discountMerchantDB->where(array('did' => $did, 'mer_id' => $value['mer_id']))->setField('store_ids',implode(',',$_store_ids));
        			$save_id_arr[] = $value['mer_id'];
        		}
        	}

        	//添加记录
        	$add_id_arr = array_diff($merIds, $save_id_arr);
        	foreach ($add_id_arr as $key => $mer_id) {
				$data_arr = ['did' => $did, 'mer_id' => $mer_id];
				$data_arr['store_ids'] = $storeIds[$mer_id];
                $discountMerchantDB->data($data_arr)->add();
        	}

    //         foreach ($merIds as $mer_id) {
				// $data_arr = ['did' => $did, 'mer_id' => $mer_id];

				// if($_POST['storeIds'][$mer_id]){
				// 	$data_arr['store_ids'] = $_POST['storeIds'][$mer_id];
				// }else{
				// 	$data_arr['store_ids'] = '';
				// }

    //             $discountMerchantDB->data($data_arr)->add();
    //         }
			D('Config')->clearDataCacheDir('shop_discount');
            $this->success('指定成功');
        }

    }

    public function merchant_reduce()
    {
        $keyword = isset($_GET['keyword']) ? htmlspecialchars(trim($_GET['keyword'])) : '';
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $area = D('Area')->field(true)->where(array('area_id' => $area_id))->find();
        if ((empty($area) || $area['area_type'] > 3) && empty($_GET['street_id'])) {
            $this->error('选择城市区域');
        }
        $condition = array('status' => 1);
        $where = 'm.status=1';
        if ($area['area_type'] == 1) {
            $where .= ' AND m.province_id=' . $area_id;
            $condition['province_id'] = $area_id;
        } elseif ($area['area_type'] == 2) {
            $where .= ' AND m.city_id=' . $area_id;
            $condition['city_id'] = $area_id;
        } elseif ($area['area_type'] == 3) {
            $where .= ' AND m.area_id=' . $area_id;
            $condition['area_id'] = $area_id;
        }

        if (!empty($keyword)) {
            if ($_GET['searchtype'] == 'mer_id') {
                $where .= ' AND m.mer_id=' . $keyword;
                $condition['mer_id'] = $keyword;
            } else if ($_GET['searchtype'] == 'account') {
                $where .= " AND m.account LIKE '%" . $keyword . "%'";
                $condition['account'] = array('like', $keyword);
            } elseif ($_GET['searchtype'] == 'name') {
                $where .= " AND m.name LIKE '%" . $keyword . "%'";
                $condition['name'] = array('like', $keyword);
            } elseif ($_GET['searchtype'] == 'phone') {
                $where .= " AND m.phone LIKE '%" . $keyword . "%'";
                $condition['phone'] = array('like', $keyword);
            }
        }

        //乡镇管理员增加筛选
        if ($_GET['street_id']) {
            $condition['street_id'] = $_GET['street_id'];
            $where .= " AND m.street_id =" . $_GET['street_id'];
        }

        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $where .= " AND (o.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }

        $sql = "SELECT count(DISTINCT(m.mer_id)) AS cnt, sum(platform_merchant) as merchant_reduce, sum(platform_plat) as balance_reduce FROM " . C('DB_PREFIX') . "merchant AS m LEFT JOIN " . C('DB_PREFIX') . "shop_order AS o ON o.mer_id=m.mer_id";
        $sql .= " AND o.paid=1 AND o.status<4 WHERE " . $where;
//         $sql .= ' GROUP BY m.mer_id';
        $countResult = D()->query($sql);

        $count = isset($countResult[0]['cnt']) ? $countResult[0]['cnt'] : 0;
        $merchant_reduce = isset($countResult[0]['merchant_reduce']) ? floatval($countResult[0]['merchant_reduce']) : 0;
        $balance_reduce = isset($countResult[0]['balance_reduce']) ? floatval($countResult[0]['balance_reduce']) : 0;
        import('@.ORG.system_page');
        $p = new Page($count, 20);

        $sql = "SELECT m.mer_id, m.name, m.phone, sum(platform_merchant) as merchant_reduce, sum(platform_plat) as balance_reduce FROM " . C('DB_PREFIX') . "merchant AS m LEFT JOIN " . C('DB_PREFIX') . "shop_order AS o ON o.mer_id=m.mer_id";
        $sql .= " AND o.paid=1 AND o.status<4 WHERE " . $where;
        $sql .= ' GROUP BY m.mer_id';
        $sql .= " LIMIT {$p->firstRow}, {$p->listRows}";
        $result = D()->query($sql);
        $this->assign('list', $result);
        $this->assign(array('merchant_reduce' => $merchant_reduce, 'balance_reduce' => $balance_reduce));
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function reduce_list()
    {
        $area_id = isset($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;
        $mer_id = I('mer_id');
        $merchant = D('Merchant')->field(true)->where(array('mer_id'=>$mer_id))->find();
        if (empty($merchant)) {
            $this->error_tips('请选择商家');
        }
        $this->assign('now_merchant', $merchant);

        $where = array('mer_id' => $mer_id, 'paid' => 1, 'status' => array('lt', 4));
        $real_orderid = isset($_REQUEST['order_id']) ? htmlspecialchars(trim($_REQUEST['order_id'])) : '';
        $condition = ' AND o.mer_id=' . $mer_id;
        if (!empty($real_orderid)) {
            $where['real_orderid'] = $real_orderid;
            $condition .= " AND o.real_orderid='" . $real_orderid . "'";
        }
        $this->assign('order_id', $real_orderid);

        if(isset($_REQUEST['begin_time'])&&isset($_REQUEST['end_time'])&&!empty($_REQUEST['begin_time'])&&!empty($_REQUEST['end_time'])){
            if ($_REQUEST['begin_time']>$_REQUEST['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_REQUEST['begin_time']." 00:00:00"),strtotime($_REQUEST['end_time']." 23:59:59"));
            $time_condition = " (pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $where['_string'] = $time_condition;
            $condition .= ' AND' . $time_condition;
            $this->assign('begin_time', $_REQUEST['begin_time']);
            $this->assign('end_time', $_REQUEST['end_time']);
        }
        $count = D('Shop_order')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);

        $sql = "SELECT s.name, o.order_id, o.num, o.real_orderid, o.platform_merchant as merchant_reduce, o.platform_plat as balance_reduce, o.price, o.pay_time, o.discount_detail FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "shop_order AS o ON o.store_id=s.store_id";
        $sql .= " WHERE o.paid=1 AND o.status<4" . $condition;
        $sql .= " ORDER BY o.order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
        $list = D()->query($sql);
        foreach ($list as &$order) {
            if ($order['discount_detail'] && @unserialize($order['discount_detail'])) {
                $details = unserialize($order['discount_detail']);
                $discount_detail = '';
                $pre = '';
                foreach ($details as $dt) {
                    if ($dt['discount_type'] == 1) {
                        $discount_detail .= $pre . '平台新单';
                    } elseif ($dt['discount_type'] == 2) {
                        $discount_detail .= $pre . '平台满减';
                    } elseif ($dt['discount_type'] == 3) {
//                         $discount_detail .= $pre . '商家新单';
                    } elseif ($dt['discount_type'] == 4) {
//                         $discount_detail .= $pre . '商家满减';
                    }
                    $pre = ',';
                }
                $order['discount_detail'] = $discount_detail;
            } else {
                $order['discount_detail'] = '';
            }
        }
        $sql = "SELECT sum(o.platform_merchant) AS merchant_reduce, sum(o.platform_plat) AS balance_reduce FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "shop_order AS o ON o.store_id=s.store_id";
        $sql .= " WHERE o.paid=1 AND o.status<4" . $condition;
        $countResult = D()->query($sql);

        $merchant_reduce = isset($countResult[0]['merchant_reduce']) ? floatval($countResult[0]['merchant_reduce']) : 0;
        $balance_reduce = isset($countResult[0]['balance_reduce']) ? floatval($countResult[0]['balance_reduce']) : 0;


        $this->assign('mer_id', $mer_id);
        $this->assign('area_id', $area_id);
        $this->assign('list', $list);
        $this->assign(array('merchant_reduce' => $merchant_reduce, 'balance_reduce' => $balance_reduce));
        $this->assign('pagebar',$p->show());
        $this->display();
    }

    public function delete_discount(){
		$id = $_POST['id'];
		$info = M('Shop_discount')->where(['id'=>$id])->find();
		if($info['end_time'] > time() && $info['start_time'] < time() && $info['status'] == '1'){
			$this->error('进行中的活动不能够被删除，请先停止该活动之后再进行删除！1');
		}
		M('Shop_discount')->where(array('id'=>$id))->setField('status',4);
		D("System_log")->add_log("删除了".$this->config['mall_alias_name']."优惠券",$this->config['mall_alias_name'],$id);
		D('Config')->clearDataCacheDir('shop_discount');
		$this->success('删除成功');
	}


    public function reduce_export()
    {
        set_time_limit(0);
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $mer_id = I('mer_id');
        $merchant = D('Merchant')->field(true)->where(array('mer_id'=>$mer_id))->find();
        if (empty($merchant)) {
            $this->error_tips('请选择商家');
        }

        $where = array('mer_id' => $mer_id, 'paid' => 1, 'status' => array('lt', 4));
        $real_orderid = isset($_GET['order_id']) ? htmlspecialchars(trim($_GET['order_id'])) : '';
        $condition = ' AND o.mer_id=' . $mer_id;
        if (!empty($real_orderid)) {
            $where['real_orderid'] = $real_orderid;
            $condition .= " AND o.real_orderid='" . $real_orderid . "'";
        }
        $this->assign('order_id', $real_orderid);

        if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time_condition = " (pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $where['_string'] = $time_condition;
            $condition .= ' AND' . $time_condition;
        }

        $sql = "SELECT s.name, o.order_id, o.num, o.real_orderid, o.platform_merchant as merchant_reduce, o.platform_plat as balance_reduce, o.price, o.pay_time, o.discount_detail FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "shop_order AS o ON o.store_id=s.store_id";
        $sql .= " WHERE o.paid=1 AND o.status<4" . $condition;
        $sql .= " ORDER BY o.order_id DESC";
        $list = D()->query($sql);


        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = $merchant['name'] . '补贴统计详情';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setTitle($merchant['name'] . '补贴统计详情');
        $objActSheet = $objExcel->getActiveSheet();
        $objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);

        $objActSheet->setCellValue('A1', '店铺名称');
        $objActSheet->setCellValue('B1', '订单号');
        $objActSheet->setCellValue('C1', '数量');
        $objActSheet->setCellValue('D1', '优惠类型');
        $objActSheet->setCellValue('E1', '订单总额');
        $objActSheet->setCellValue('F1', '平台补贴金额');
        $objActSheet->setCellValue('G1', '商家补贴金额');
        $objActSheet->setCellValue('H1', '支付时间');
        $index = 1;
        foreach ($list as $order) {
            $index++;
            if ($order['discount_detail'] && @unserialize($order['discount_detail'])) {
                $details = unserialize($order['discount_detail']);
                $discount_detail = '';
                $pre = '';
                foreach ($details as $dt) {
                    if ($dt['discount_type'] == 1) {
                        $discount_detail .= $pre . '平台新单';
                    } elseif ($dt['discount_type'] == 2) {
                        $discount_detail .= $pre . '平台满减';
                    } elseif ($dt['discount_type'] == 3) {
//                         $discount_detail .= $pre . '商家新单';
                    } elseif ($dt['discount_type'] == 4) {
//                         $discount_detail .= $pre . '商家满减';
                    }
                    $pre = ',';
                }
                $order['discount_detail'] = $discount_detail;
            } else {
                $order['discount_detail'] = '';
            }

            $objActSheet->setCellValueExplicit('A' . $index, $order['name']);
            $objActSheet->setCellValueExplicit('B' . $index, $order['real_orderid']);
            $objActSheet->setCellValueExplicit('C' . $index, $order['num']);
            $objActSheet->setCellValueExplicit('D' . $index, $order['discount_detail']);
            $objActSheet->setCellValueExplicit('E' . $index, strval(($order['price'])));
            $objActSheet->setCellValueExplicit('F' . $index, strval(($order['balance_reduce'])));
            $objActSheet->setCellValueExplicit('G' . $index, strval(($order['merchant_reduce'])));
            $objActSheet->setCellValueExplicit('H' . $index, date('Y-m-d H:i:s', $order['pay_time']));
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

    //快店首页配置
    public function main_page()
    {
    	// 区域管理员
        if ($this->system_session['level'] == 1) {
            if ($this->system_session['area_type'] == 3) {
            	$this->error('您没有权限');
            }

           	$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $this->assign('now_area',$now_area);

            if ($now_area['area_type'] == 2) {
            	//城市管理员
                $area_index = 'city_id';
            	$where['s.city_id'] = $this->system_session['area_id'];
                $_GET['city_idss']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
            	//省份管理员
                $area_index = 'province_id';
            	$where['s.province_id'] = $this->system_session['area_id'];
                $_GET['province_idss'] =$now_area['area_id'];
            }
        }

        if ($_GET['city_idss']) {
         	$where['_string'] = '(s.city_id='.$_GET['city_idss'].' OR s.city_id=0)';
        }else{

        }

        $where['s.type'] = $_GET['type'] ? $_GET['type'] : $this->config['shop_main_page_center_type'];
        $where['s.is_del'] = 0;
        $slider_list = M('Shop_main_page_pic_slider')->join('as s left join ' . C('DB_PREFIX') . 'area a ON s.city_id = a.area_id')->where($where)->order('s.sort DESC')->field('s.*, a.area_name')->select();
        foreach ($slider_list as $key => $value) {
			$slider_list[$key]['area_name'] = $value['area_name'] ? : '通用';
		}

        $this->assign('slider_list', $slider_list);
        $this->assign('user_level', $this->system_session['level']);
        $this->display();
    }

    public function save_main_page()
    {
    	$city_id = $_POST['city_idss'];
    	$province_id = $_POST['province_idss'];
    	$data['area_id'] = $city_id;
    	$data['shop_main_page_show_ad'] = $_POST['shop_main_page_show_ad'];
    	$data['shop_main_page_center_type'] = $_POST['shop_main_page_center_type'];
    	$res = M('Area')->save($data);
    	if ($res!==false) {
    		$this->success('保存成功');
    	}else{
    		$this->error('保存失败');
    	}

    }

    public function get_shop_main_page_ad()
    {
		if ($_GET['city_idss']) {
         	$where['_string'] = '(s.city_id='.$_GET['city_idss'].' OR s.city_id=0)';
        }
    	$where['s.type'] = $_GET['type'] ? $_GET['type'] : $this->config['shop_main_page_center_type'];
        $where['s.is_del'] = 0;
        $slider_list = M('Shop_main_page_pic_slider')->join('as s left join ' . C('DB_PREFIX') . 'area a ON s.city_id = a.area_id')->where($where)->order('s.sort DESC')->field('s.*, a.area_name')->select();
        foreach ($slider_list as $key => $value) {
			$slider_list[$key]['area_name'] = $value['area_name'] ? : '通用';
			$slider_list[$key]['last_time'] = date('Y-m-d H:i:s',$value['last_time']);
		}
		$slider_list = $slider_list ? $slider_list : array();
    	exit(json_encode($slider_list));

    }
    //添加团购首页中部导航图文
    public function add_shop_center_type_img()
    {
        if (IS_POST) {
            $where['type'] = $_POST['type'];
            $where['is_del'] = 0;
            $count = M('Shop_main_page_pic_slider')->where($where)->count();
            if ($_FILES['pic']['error'] != 4) {
                $image = D('Image')->handle($this->system_session['id'], 'slider');
                if (!$image['error']) {
                    $_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
                } else {
                    $this->frame_submit_tips(0, $image['message']);
                }
            }
            $data['name'] = $_POST['name'];
            $data['sub_name'] = $_POST['sub_name'];
            $data['url'] = $_POST['url'];
            $data['last_time'] = $_SERVER['REQUEST_TIME'];
            $_FILES['pic']['error'] != 4 && $data['pic'] = $_POST['pic'];
            $data['sort'] = $_POST['sort'];
            $data['type'] = $_POST['type'];
            $data['wxapp_id'] = $_POST['wxapp_id'];
            $data['wxapp_page'] = $_POST['wxapp_page'];
            $data['ios_app_name'] = $_POST['ios_app_name'] ? $_POST['ios_app_name'] : '';
            $data['ios_app_url'] = $_POST['ios_app_url'] ? $_POST['ios_app_url'] : '';
            $data['android_app_name'] = $_POST['android_app_name'] ? $_POST['android_app_name'] : '';
            $data['android_app_url'] = $_POST['android_app_url'] ? $_POST['android_app_url'] : '';
            $data['wxapp_open_type'] = $_POST['wxapp_open_type'] ? $_POST['wxapp_open_type'] : '1';
            $data['app_open_type'] = $_POST['app_open_type'] ? $_POST['app_open_type'] : '2';
            $data['app_wxapp_page'] = $_POST['app_wxapp_page'] ? $_POST['app_wxapp_page'] : '';
            $data['app_wxapp_id'] = $_POST['app_wxapp_id'] ? $_POST['app_wxapp_id'] : '';

			// app打开其他小程序需要获取原始id
			if($_POST['app_wxapp_id']){
	        	$wxapp = M('Wxapp_other')->field(true)->where(['appid'=>$_POST['app_wxapp_id']])->find();
	        	$data['app_wxapp_username'] = $wxapp['username'];
			}

			if($_POST['currency'] == 1 || empty($_POST['province_idss'])){
				$data['province_id']	=	0;
				$data['city_id']	=	0;
			}else{
				$data['province_id'] = intval($_POST['province_idss']);
				$data['city_id'] = intval($_POST['city_idss']);
			}

            //时间展示
            $data['effect_date_type'] = $_POST['effect_date_type'];
            $data['effect_time_type'] = $_POST['effect_time_type'];
            $data['start_date'] = $_POST['start_date'];
            $data['end_date'] = $_POST['end_date'];
            $data['start_time'] = $_POST['start_time'];
            $data['end_time'] = $_POST['end_time'];
            if ($_POST['effect_date_type'] == 1) {
                $data['start_date'] = $data['end_date'] = null;
                $_POST['week'] = [];
            }
            if ($_POST['effect_time_type'] == 1) {
                $data['start_time'] = $data['end_time'] = null;
            }
            $data['week'] = implode(',', $_POST['week']);


            if ($_POST['id']) {
                $result = M('Shop_main_page_pic_slider')->where(array('id' => $_POST['id']))->save($data);
            } else {
//                if ($count == abs($_POST['type'])) {
//                    $this->error_tips(abs($_POST['type']) . '图模式只能添加' . abs($_POST['type']) . '个');
//                }
                $result = M('Shop_main_page_pic_slider')->add($data);
            }
            if ($result) {
                $this->frame_submit_tips(1, '保存成功！');
            } else {
                $this->frame_submit_tips(0, '保存失败！请重试~');
            }
        } else {
            if ($_GET['id']) {
                $where['id'] = $_GET['id'];
                $where['is_del'] = 0;
                $slider = M('Shop_main_page_pic_slider')->where($where)->find();
                $this->assign('slider', $slider);
            }
            //小程序列表
            $wxapp_list = M('Wxapp_other')->field(true)->select();
            $this->assign('wxapp_list', $wxapp_list);
	        //ios列表
	        $app_list = M('App_other')->field(true)->select();
	        $this->assign('app_list', $app_list);
            $this->assign('user_level', $this->system_session['level']);
            $this->assign('user_area_type', $this->system_session['area_type']);
            $this->display();
        }
    }

    public function del_shop_center_type_img(){
        $result = M('Shop_main_page_pic_slider')->where(array('id' => $_GET['id']))->setField('is_del', 1);
        if ($result) {
            $this->frame_submit_tips(1, '删除成功！');
        } else {
            $this->frame_submit_tips(0, '删除失败！请重试~');
        }
    }

	public function image_show(){
		$this->display();
	}

	//开店中店铺
    public function open_shop(){
        $where = "`sh`.`is_close`= 0";
        $where .= " AND `s`.`status` = 1";
        if(!empty($_GET['keyword'])){
            $where .= " AND s.name LIKE '%{$_GET['keyword']}%'";
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $where .= " AND s.{$area_index} = '{$this->system_session['area_id']}'";
            if($this->system_session['street_id']){
                $where .= " AND s.street_id = '{$this->system_session['street_id']}'";
            }
        }
        if($_GET['province_idss'] && $this->config['many_city']){
            $where .= " AND s.province_id = {$_GET['province_idss']}";
        }
        if($_GET['city_idss']){
            $where .= " AND s.city_id = {$_GET['city_idss']}";
        }
        if($_GET['area_id']){
            $where .= " AND s.area_id = {$_GET['area_id']}";
        }
        if($_GET['circle_id'] && $_GET['circle_id'] != 0){
            $where .= " AND s.circle_id = {$_GET['circle_id']}";
        }
        $sql_count = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE {$where}";
        $mode = new model();
        $count = $mode->query($sql_count);
        $count = isset($count[0]['cnt']) ? $count[0]['cnt'] : 0;
        import('@.ORG.system_page');
        $p = new Page($count, 20);

        $sql = "SELECT `m`.`name` AS merchant_name,  `s`.`mer_id` AS mer_id,`s`.`name` AS store_name, `m`.`phone` AS merchant_phone, `s`.`phone` AS store_phone, `s`.`store_id`,`s`.`status`,`s`.`open_1`,`s`.`close_1`,`s`.`open_2`,`s`.`close_2`,`s`.`open_3`,`s`.`close_3`, `sh`.* FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE {$where} ORDER BY `sh`.`sort` DESC, `sh`.`store_id` DESC ";
        if(empty($_GET['export'])){
            $sql .= " LIMIT {$p->firstRow}, {$p->listRows}";
        }
        $order_list = $mode->query($sql);
		$order_list = $this->deal_open_shop_list($order_list);
        
        if($_GET['export']){
            foreach ($order_list as &$v){
                foreach ($v['open_time_list'] as $vv){
                    if($vv['current'] == 1){
                        $v['time_show_text'] = $vv['week_show'].' '.$vv['time_show'];
                    }
                }
                $v['open_time_date'] = $v['open_time'] ? date('Y-m-d H:i:s', $v['open_time']) : '---';
            }
            $config = [
                'header' => [
                    ['title' => L_('编号'), 'value_key' => 'store_id'],
                    ['title' => L_('店铺名称'), 'value_key' => 'store_name'],
                    ['title' => L_('联系电话'), 'value_key' => 'store_phone'],
                    ['title' => L_('营业时间'), 'value_key' => 'time_show_text'],
                    ['title' => L_('开店时间'), 'value_key' => 'open_time_date']
                ],
                'body' => $order_list,
                'file_name' => time(),
                'sheet_title' => "营业中店铺"
            ];
            R('System/Shop/download_store_list', ['config' => $config]);
        }
                
        $this->assign('order_list', $order_list);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

	private function deal_open_shop_list($data){
		if($data){
			$store_ids = array();
			foreach($data as &$row){
				$store_ids[] = $row['store_id'];
			}
			if(!empty($store_ids)){
				$time_list = D('Merchant_store_open_time')->get_all_time_by_store($store_ids);
			}
			if(!empty($time_list)){
				foreach($data as $key =>$val){
					$data[$key]['open_time_list'] = $time_list[$val['store_id']];
				}
			}
		}
		return $data;
	}

    //关店中店铺
    public function close_shop(){
        $where = "`sh`.`is_close`= 1";
        $where .= " AND `s`.`status` = 1";
        if(!empty($_GET['keyword'])){
            $where .= " AND s.name LIKE '%{$_GET['keyword']}%'";
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $where .= " AND s.{$area_index} = '{$this->system_session['area_id']}'";
            if($this->system_session['street_id']){
                $where .= " AND s.street_id = '{$this->system_session['street_id']}'";
            }
        }
        if($_GET['province_idss'] && $this->config['many_city']){
            $where .= " AND s.province_id = {$_GET['province_idss']}";
        }
        if($_GET['city_idss']){
            $where .= " AND s.city_id = {$_GET['city_idss']}";
        }
        if($_GET['area_id']){
            $where .= " AND s.area_id = {$_GET['area_id']}";
        }
        if($_GET['circle_id'] && $_GET['circle_id'] != 0){
            $where .= " AND s.circle_id = {$_GET['circle_id']}";
        }
        $sql_count = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE {$where}";
        $mode = new model();
        $count = $mode->query($sql_count);
        $count = isset($count[0]['cnt']) ? $count[0]['cnt'] : 0;
        import('@.ORG.system_page');
        $p = new Page($count, 20);

        $sql = "SELECT `m`.`name` AS merchant_name, `s`.`name` AS store_name, `m`.`phone` AS merchant_phone, `s`.`phone` AS store_phone, `s`.`store_id`,`s`.`status`,`s`.`open_1`,`s`.`close_1`,`s`.`open_2`,`s`.`close_2`,`s`.`open_3`,`s`.`close_3`, `sh`.* FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE {$where} ORDER BY `sh`.`sort` DESC, `sh`.`store_id` DESC ";
        if(empty($_GET['export'])){
            $sql .= " LIMIT {$p->firstRow}, {$p->listRows}";
        }
        $order_list = $mode->query($sql);
		$order_list = $this->deal_open_shop_list($order_list);
       
        if($_GET['export']){
            foreach ($order_list as &$v){
                foreach ($v['open_time_list'] as $vv){
                    if($vv['current'] == 1){
                        $v['time_show_text'] = $vv['week_show'].' '.$vv['time_show'];
                    }
                }
                $v['close_time_date'] = $v['close_time'] ? date('Y-m-d H:i:s', $v['close_time']) : '---';
                if($v['close_by']==1){
                    $v['close_by_text'] = '平台';
                }else if($v['close_by']=='-'){
                    $v['close_by_text'] = '-';
                }else{
                    $v['close_by_text'] = '商家';
                }
            }
            $config = [
                'header' => [
                    ['title' => L_('编号'), 'value_key' => 'store_id'],
                    ['title' => L_('店铺名称'), 'value_key' => 'store_name'],
                    ['title' => L_('联系电话'), 'value_key' => 'store_phone'],
                    ['title' => L_('营业时间'), 'value_key' => 'time_show_text'],
                    ['title' => L_('关店原因'), 'value_key' => 'close_reason'],
                    ['title' => L_('关店时间'), 'value_key' => 'close_time_date'],
                    ['title' => L_('由谁关闭'), 'value_key' => 'close_by_text'],
                ],
                'body' => $order_list,
                'file_name' => time(),
                'sheet_title' => "未营业店铺"
            ];
            R('System/Shop/download_store_list', ['config' => $config]);
        }
        $this->assign('order_list', $order_list);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    //批量开关店
    public function changeOnOffBusiness(){
        $storeIds = $_POST['storeIds'];
        if(empty($storeIds)){
            exit(json_encode(array('error'=>1,'info'=>'请先选择店铺！')));
        }
        $type = $_POST['type'];
        if($type == 1){
            $reason = $_POST['reason'];
            if(M('Merchant_store_shop')->where(array('store_id'=>array('in',$storeIds)))->save(array('is_close'=>$type,'close_reason'=>$reason,'close_time'=>time(),'close_by'=>1))){
                exit(json_encode(array('errorno'=>1,'info'=>'关闭成功！')));
            }else{
                exit(json_encode(array('errorno'=>0,'info'=>'关闭失败！')));
            }
        }elseif($type == 0){
            if(M('Merchant_store_shop')->where(array('store_id'=>array('in',$storeIds)))->save(array('is_close'=>$type,'close_reason'=>"",'open_time'=>time()))){
                exit(json_encode(array('errorno'=>1,'info'=>'开启成功！')));
            }else{
                exit(json_encode(array('errorno'=>0,'info'=>'开启失败！')));
            }
        }
    }

    //平台快店退款操作
    public function replyRefund()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
        $type = isset($_POST['type']) ? htmlspecialchars(trim($_POST['type'])) : 'agree';
        $reply_content = isset($_POST['reply_content']) ? htmlspecialchars(trim($_POST['reply_content'])) : '';

        $order = D('Shop_order')->field(true)->where(array('order_id' => $order_id))->find();
        if (empty($order)) {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单信息错误')));
        }
        if (!in_array($type, array('agree', 'disagree'))) {
            exit(json_encode(array('errcode' => 1, 'msg' => '请选择正确的回复形式')));
        }

        if($order['status']==4){
            $goods_total_all = D('Shop_order_detail')->where(['order_id'=>$order_id])->sum('num');
            $refund_goods_total_all = D('Shop_order_refund')->join(' as r inner join '.C("DB_PREFIX").'shop_refund_detail as rd on rd.refund_id = r.id')
                ->where(['r.order_id'=>$order_id,'r.status'=>1])->sum('rd.num');
            if(!$refund_goods_total_all||$refund_goods_total_all >= $goods_total_all){
                exit(json_encode(array('errcode' => 1, 'msg' => '该订单已退款，请不要重复操作')));
            }
        }

        $refund = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'id' => $refund_id,'status' => array('neq',4)))->find();
        if (empty($refund)) {
            exit(json_encode(array('errcode' => 1, 'msg' => '订单的退货信息错误')));
        }
        if ($refund['status'] != 0) {
            exit(json_encode(array('errcode' => 1, 'msg' => '当前的退货信息无需修改')));
        }
        if($order['order_from']==1){
			$order_type='mall';
		}else{
			$order_type='shop';
		}
        //状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请)
        if ($type == 'agree') {
            //先减余额支付，在进行线上支付的退款
            //已退款的金额的查询
            // $refundMoney = D('Shop_order_refund')->field('sum(balance_pay) as balance_pay, sum(merchant_balance) as merchant_balance, sum(card_give_money) as card_give_money, sum(payment_money) as payment_money, sum(score_used_count) as score_used_count, sum(score_deducte) as score_deducte')->where(array('order_id' => $order_id,'status' => array('neq',4)))->find();
            $refundMoney = $refund;
            //积分->商家余额->平台余额->在线支付的金额
            $nowPayMoney = floatval($refund['price']);//当前要退款的金额

            // 查看要退换的积分 积分不够则从退款金额抵扣 20191219
            $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,$nowPayMoney);

            if($this->config['open_allinyun']==1){
                $nowPayMoney = $returnScoreBack['refundMoney'];

                import('@.ORG.AccountDeposit.AccountDeposit');
                $deposit = new AccountDeposit('Allinyun');
                $allyun = $deposit->getDeposit();

                $allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid']);

                if($allinyun_user['bizUserId']!=''){
                    $allyun->setUser($allinyun_user);
                    $params['bizOrderNo'] = 'shop_'.$order['orderid'];
                    $params['oriBizOrderNo'] = $order['third_id'];
                    $params['amount'] = intval($nowPayMoney*100);
                    $res = $allyun->refundApply($params);

                    if($res['status']=='OK'){
                        $data_shop_order['order_id'] = $order['order_id'];
                        $data_shop_order['status'] = 4;
                        $data_shop_order['last_time'] = time();
                        $data_shop_order['refund_detail'] = serialize($res);
                        $return = $this->shop_refund_detail($order, $order['store_id']);
                        if ($return['error_code']) {
                            exit(json_encode(array('errcode' => 1, 'msg' =>$return['msg'])));
//							$this->error_tips($return['msg']);
                        }
                        D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
                        D('Shop_order')->data($data_shop_order)->save();

			            // 退还获得积分 20191219
			            $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,floatval($refund['price']),1);
                        exit(json_encode(array('errcode' => 0, 'msg' =>'退款成功')));
                    }else{
                        exit(json_encode(array('errcode' => 1, 'msg' =>$res['message'])));
                    }
                }
            }

             // 计算退款时每种支付方式需要退款的金额
            $returnRefundMoney = D('Shop_order')->getRrfundDetail($refundMoney,$returnScoreBack);
            // 积分
            $score_used_count=$returnRefundMoney['score_used_count'];
            // 积分抵扣金额
            $score_deducte=$returnRefundMoney['score_deducte'];
            // 商家余额
            $merchant_balance=$returnRefundMoney['merchant_balance'];
            // 商家赠送余额
            $card_give_money=$returnRefundMoney['card_give_money'];
            // 平台余额
            $balance_pay=$returnRefundMoney['balance_pay'];
            // ecard
            $ecard_pay=$returnRefundMoney['ecard_pay'];
            // 剩余退还金额
            $backMoney=$returnRefundMoney['backMoney'];

            $payment_money = 0;
            $third_refund_id = 0;
            if ($refundMoney['payment_money'] > 0) {
                $payment_money = $refundMoney['payment_money'];
                $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['order_id'],'order_type'=>'shop','pay_type'=>$order['pay_type'],'paid_money'=>array('gt',0)))->find();
                if($payment_money == $payment_money_order['payment_money']){
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $order['payment_money'] = $payment_money_order['paid_money'];
                    $payment_money = $payment_money_order['paid_money'];
                }
                /*if($payment_money_order['pay_rate'] > 0 && $order['pay_type'] != 'weixin'){
                	$payment_money = getFormatNumber($payment_money*$payment_money_order['pay_rate']);
                }*/

                // 退回积分不够，需要扣除相应金额
                if ($backMoney) {
                    $payment_money = getFormatNumber($payment_money - $backMoney);
                    if ($payment_money<0) {
                        // 不够抵扣
                        $backMoney -= $payment_money;
                        $payment_money = 0;
                    }else{
                        // 足够抵扣
                        $backMoney=0;
                    }
                }
            }


            if ($payment_money > 0) {
                if ($order['is_own']) {
                    $pay_method = array();
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
                    foreach ($merchant_ownpay as $ownKey => $ownValue) {
                        $ownValueArr = unserialize($ownValue);
                        if ($ownValueArr['open']) {
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
                        }
                    }
                } else {
                    $is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
                    $pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0);
                }

                if (empty($pay_method)) {
                    exit(json_encode(array('errcode' => 1, 'msg' => '支付方式不存在或已关闭')));
                    return false;
                }
                if (in_array($order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                    $pay_class_name = 'Hqpay';
                    empty($pay_method[$order['pay_type']]) && $pay_method[$order['pay_type']] = 1;
                }
                if (empty($pay_method[$order['pay_type']])) {
                    exit(json_encode(array('errcode' => 1, 'msg' => '支付方式不存在或已关闭')));
                }
                if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'icbcpay')){
					if($payment_money_order['pay_method'] == 'wft'){
						$pay_method = 'wftpay';
					}
					if($payment_money_order['pay_method'] == 'paylinx'){
						$pay_method = 'paylinx';
					}
					if($payment_money_order['pay_method'] == 'weikebaba'){
						$pay_method = 'weikebaba';
					}
					if($payment_money_order['pay_method'] == 'wepayez'){
						$pay_method = 'wepayez';
					}
					if($payment_money_order['pay_method'] == 'icbcpay'){
                        $pay_method = 'icbcpay';
                    }
                	import('@.ORG.LowFeePay');
                	$lowfeepay = new LowFeePay($pay_method);
                    $order['payment_refund_money'] = $payment_money;
                	$order['order_type'] = 'shop';
                	$order['orderNo'] = $payment_money_order['orderNo'];
                	if($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $order['is_own'] =1 ;
                            $order['orderNo'] =$order['orderNo'];
                        }

                    }
                	$go_refund_param= $lowfeepay->refund($order);
                	fdump($go_refund_param,'lumin_refund',true);
                } else if ($payment_money_order && $payment_money_order['pay_method'] == 'chinaums') {
                    import("@.ORG.pay.Chinaums");
                    $pay = new Chinaums();
                    if ($payment_money_order['is_mobile_pay'] == 3) {
                        $mid = C("config.chinaums_mid_wxapp");
                    }
                    if ($payment_money_order['is_mobile_pay'] == 2) {
                        $mid = C("config.chinaums_mid_app");
                    }
                    if ($payment_money_order['is_mobile_pay'] == 1) {
                        $mid = C("config.chinaums_mid_wx");
                    }
                    $chinaums_merno = '';
                    if ($order['mer_id']) {
                        $now_merchant = M("Merchant")->where(['mer_id' => $order['mer_id']])->find();
                        if (!empty($now_merchant['chinaums_merno'])) {
                            $chinaums_merno = $now_merchant['chinaums_merno'];
                        }
                    }

                    $go_refund_param = $pay->refund($payment_money_order['order_type'] . '_' . $payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money, $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
                }else if($payment_money_order && $payment_money_order['pay_method'] == 'scrcu'){
                    import("@.ORG.pay.Scrcu");
                    $pay = new scrcu();

                    $scrcu_mer_no = '';
                    if($order['mer_id']){
                        $now_merchant = M("Merchant")->where(['mer_id'=>$order['mer_id']])->find();
                        if(!empty($now_merchant['scrcu_mer_no'])){
                            $scrcu_mer_no = $now_merchant['scrcu_mer_no'];
                        }
                    }

                    $go_refund_param= $pay->refund($order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
                }else if($payment_money_order && strpos($payment_money_order['pay_type'],'xiantao') !== false){// 智慧仙桃支付退款
                    if($order['payment_money'] == $payment_money){// 不支持部分退款
                        import('@.ORG.pay.XianTaoCard');
                        $pay = new XianTaoCard();

                        $now_user = D('User')->get_user($order['uid']);
                        try{
                            // 退款
                            $go_refund_param = $pay->refund($payment_money_order['orderNo'], $now_user['phone']);
                        } catch(Exception $e){
                            exit(json_encode(array('errcode' => 1, 'msg' => $e->getMessage())));
                        }
                    }else{
                        exit(json_encode(array('errcode' => 1, 'msg' => '仙桃支付方式不支持部分退款')));
                    }
                } else {
	                $pay_class_name != 'Hqpay' && $pay_class_name = ucfirst($order['pay_type']);
	                $import_result = import('@.ORG.pay.' . $pay_class_name);
	                if (empty($import_result)) {
	                    exit(json_encode(array('errcode' => 1, 'msg' => '支付方式不存在或已关闭')));
	                }

                    $order['payment_refund_money'] = $payment_money;
	                $order['order_type'] = 'shop';
	                $order['order_id'] = $order['orderid'];
	                if ($order['order_from'] == 4) {
	                    $pay_method['weixin']['config'] = array(
	                        'pay_weixin_appid' => C('config.pay_wxapp_appid'),
	                        'pay_weixin_key' => C('config.pay_wxapp_key'),
	                        'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
	                        'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
	                    );
	                    if ($order['is_own'] == 1) {
	                        $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
	                        $pay_method['weixin']['config'] = array(
	                            'pay_weixin_appid' => $wxapp_own_pay['appid'],
	                            'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
	                            'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
	                            'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
	                            'is_own' => 1
	                        );
	                    }
	                }
	                $now_user = D('User')->get_user($order['uid']);
	                if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
	                    $pay_class_name = 'AlipayApp';
	                    $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
	                    $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
	                    $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
	                }
	                $import_result = import('@.ORG.pay.' . $pay_class_name);
	                $pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
	                $go_refund_param = $pay_class->refund();
	            }

                if (!(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
                    exit(json_encode(array('errcode' => 1, 'msg' => $go_refund_param['msg'])));
                } else {
                    $third_refund_id = $go_refund_param['refund_param']['refund_id'];
                }
            }

            if($order['order_from']==1){
            	$alias_name=$this->config['mall_alias_name'];
            	$alias_name=$alias_name?$alias_name:'商城';
            	$order_type='mall';
            }else{
            	$alias_name=$this->config['shop_alias_name'];
            	$order_type='shop';
            }
            $mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();
            if ($score_used_count != 0) {
                $result = D('User')->add_score($order['uid'], $score_used_count, $alias_name . '部分商品退货, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
                $param = array('refund_time' => time());
                if ($result['error_code']) {
                    $score_used_count = 0;
                }
            }

            //平台余额退款
            if ($balance_pay > 0) {
                $result = D('User')->add_money($order['uid'], $balance_pay, $alias_name . '部分商品退货,增加余额,订单编号' . $order['real_orderid']);
                if($result['error_code']){
                    $balance_pay = 0;
                }
            }
            //商家会员卡余额退款
            if ($merchant_balance > 0 || $card_give_money > 0) {
				$card_mer_id = $order['mer_id'];

	            // 商家联盟会员卡
	            if ($order['union_mer_id']) {
	                $card_mer_id = $order['union_mer_id'];
	            }

                $result = D('Card_new')->add_user_money($card_mer_id, $order['uid'],  $merchant_balance, $card_give_money, 0, $alias_name . '部分商品退货,增加余额,订单编号' . $order['real_orderid'], $alias_name . '部分商品退货,增加赠送余额,订单编号' . $order['real_orderid']);
                if ($result['error_code']) {
                    $merchant_balance = 0;
                    $card_give_money = 0;
                }
            }
            $data = array();
            $data['reply_content'] = $reply_content;
            $data['reply_time'] = time();
            $data['status'] = 1;
            $data['score_deducte'] = $score_deducte;
            $data['score_used_count'] = $score_used_count;
            $data['balance_pay'] = $balance_pay;
            $data['merchant_balance'] = $merchant_balance;
            $data['card_give_money'] = $card_give_money;
            $data['payment_money'] = $payment_money;
            $data['third_refund_id'] = $third_refund_id;
            $data['refund_price'] = getFormatNumber($score_deducte+$balance_pay+$ecard_pay+$merchant_balance+$card_give_money+$payment_money);
            //Shop_refund_log status 状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请) 新增 5:平台同意退货 6:平台拒绝退货
            D('Shop_refund_log')->add(array('refund_id' => $refund_id, 'status' => 5, 'dateline' => time(), 'note' => $reply_content));
            D('Shop_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save($data);
            D('System_order')->save_order_status($order_type, $order_id, 4);

            $count = D('Shop_order_detail')->where(array('order_id' => $order_id, '_string' => 'refundNum < num'))->count();
            $rcount = D('Shop_order_refund')->where(array('order_id' => $order_id, 'status' => 0))->count();
            $saveData = array('is_apply_refund' => 0);
            if (empty($count) && empty($rcount)) {
                $saveData['status'] = 4;
                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
            }

            // 退还获得积分 20191219
            $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,floatval($refund['price']),$refund_id);

            /******************** 商家余额减少 start *******************/
            if (!$refund['merchant_money'] && $refund['price']>0) {
                $refund['merchant_money'] = $refund['price'];
            }
            $totalMoney = floatval(round($refund['merchant_money'], 2));
            if ($totalMoney<0) {
                $totalMoney=0;
            }
            fdump([__FUNCTION__ => [$order,$data]],'refund',true);
            // 商家会员卡联盟 商家余额退款减少联盟商家的余额
            if ($data['merchant_balance']&&$order['union_mer_id']) {
            	//使用了联盟商家会员卡
            	// 联盟商家
				if ($mdata = D('Merchant_money_list')->field('percent,type,money,system_take')->where(array('type' => $order_type,'income'=>1, 'order_id' => $order['real_orderid'],'union_mer_id'=>$order['union_mer_id']))->find()) {
					// 根据商家会员卡余额比例计算应退金额
	            	$union_mer_money = $totalMoney * ($data['merchant_balance']/$refundMoney['price']);
	            	$totalMoney = $totalMoney - $union_mer_money;

                    $returnSystemTake = $returnTakePercent = 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($union_mer_money,$mdata['money'],2), $mdata['system_take'],2);
                        $returnTakePercent = $mdata['percent'];
                    }
					D('Merchant_money_list')->use_money($order['union_mer_id'], $union_mer_money, $mdata['type'], $alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], $order['real_orderid'], $returnTakePercent, $returnSystemTake);
				}

				// 此商家
                $mdata = D('Merchant_money_list')
                    ->field('percent,type,money,system_take')
                    ->where(array('type' => $order_type, 'income'=>1,'order_id' => $order['real_orderid']))
                    ->find();
                //订单来源（0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团，9.扫码购）
                if (in_array($order['order_from'],[6,9])  && empty($mdata)) {
                    $mdata = D('Merchant_money_list')
                        ->field('percent,type,money,system_take')
                        ->where(['type' => 'shop_offline', 'income'=>1,'order_id' => $order['order_id']])
                        ->find();
                }
				if ($mdata) {
                    $returnSystemTake = $returnTakePercent = 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                        $returnTakePercent = $mdata['percent'];
                    }
                    
					D('Merchant_money_list')->use_money(
                        $order['mer_id'], 
                        $totalMoney, 
                        $mdata['type'], 
                        $alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], 
                        $order['real_orderid'], 
                        $returnTakePercent, 
                        $returnSystemTake,
                        [],
                        0,
                        $go_refund_param['refund_param']??[]
                    );
				}
            }else{
                $mdata = D('Merchant_money_list')
                    ->field('percent,type,money,system_take')
                    ->where(array('type' => $order_type, 'income'=>1, 'order_id' => $order['real_orderid']))
                    ->find();
                //订单来源（0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团，9.扫码购）
                if (in_array($order['order_from'],[6,9]) && empty($mdata)) {
                    $mdata = D('Merchant_money_list')
                        ->field('percent,type,money,system_take')
                        ->where(['type' => 'shop_offline','income'=>1, 'order_id' => $order['order_id']])
                        ->find();
                }
                
            	if ($mdata) {
                    if($order['packing_charge']>0){//存在打包费，则扣除打包费
                        $totalMoney=$totalMoney-$order['packing_charge'];
                        if ($totalMoney<0) {
                            $totalMoney=0;
                        }
                    }
                    $returnSystemTake = $returnTakePercent = 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                        $returnSystemTake = min($returnSystemTake, $mdata['system_take']);
                        $returnTakePercent = $mdata['percent'];
                    }
                    fdump_api([$mdata,$totalMoney,$returnSystemTake,$returnTakePercent],'pay/shop_refund_money_log',true);
					D('Merchant_money_list')->use_money(
                        $order['mer_id'], 
                        $totalMoney, 
                        $order_type, 
                        $alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], 
                        $order['real_orderid'], 
                        $returnTakePercent, 
                        $returnSystemTake,
                        [],
                        0,
                        $go_refund_param['refund_param']??[]
                    );
				}
            }
			/******************** 商家余额减少 end *******************/

            D('Shop_order')->where(array('order_id' => $order_id))->save($saveData);

            //如果该订单有免单行为需要扣除用户所得面单金额
            $refund_ready_num = D('Shop_order_refund')->join(' as r inner join pigcms_shop_refund_detail as rd on r.id = rd.refund_id')
                ->where(['r.order_id' => $order_id, 'r.status' => 1])
                ->sum('rd.num');
            if ($order['num'] <= $refund_ready_num) {
                $flipping_event_awards_log = D('Flipping_event_awards_log')->where(['order_id'=>$order_id,'uid'=>$order['uid'],'status'=>['neq',2]])->select();
                if($flipping_event_awards_log){
                    foreach ($flipping_event_awards_log as $log_item){
                        if($log_item['awards_type']==1) {
                            D('User')->user_money($order['uid'],$log_item['money'],'抽奖免单申请退款扣除余额',24,$order_id);
                        }
                        $flip_res = D('Flipping_event_awards_log')->where(['id'=>$log_item['id']])->delete();
                        $stock_inc_res = D('Flipping_event_awards')->where(['id'=>$log_item['awards_id']])->setInc('stocks_num',1);
                        fdump_api([$order_id,$order['uid'],$flip_res,D('Flipping_event_awards_log')->getLastSql(),$stock_inc_res,D('Flipping_event_awards')->getLastSql(),$log_item],'flipping_event',1);
                    }
                }
            }
            exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));
        } else {
            //Shop_refund_log status 状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请) 新增 5:平台同意退货 6:平台拒绝退货
            D('Shop_refund_log')->add(array('refund_id' => $refund_id, 'status' => 6, 'dateline' => time(), 'note' => $reply_content));
            $refundDetailList = D('Shop_refund_detail')->field(true)->where(array('refund_id' => $refund_id))->select();
            $orderDetailDB = D('Shop_order_detail');
            foreach ($refundDetailList as $row) {
                $orderDetailDB->where(array('id' => $row['detail_id']))->setDec('refundNum',$row['num']);
            }
            D('Shop_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save(array('reply_time' => time(), 'reply_content' => $reply_content, 'status' => 2));
            D('Shop_order')->where(array('order_id' => $order_id))->save(array('is_apply_refund' => 0));
            exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));
        }
    }

    // 平台手动退款
    public function system_refund(){
		$order_id = intval($_GET['order_id']);
		$now_order = D("Shop_order")->get_order_detail(array('order_id' => $order_id));

    	if (IS_POST) {
    		if (!C('config.open_system_refund') || (!in_array('374', $this->system_session['menus'])&&$this->system_session['account'] != 'admin')) {
    			$this->error('平台未开启手动退款');
    		}
    		$data['refund_type'] = $_POST['refund_type'];
    		$data['reason'] = $_POST['reason'];
    		$data['price'] = floatval($_POST['price']);
    		$data['remark'] = $_POST['remark'];

			$order_id = intval($_POST['order_id']);
			$now_order = D("Shop_order")->get_order_detail(array('order_id' => $order_id));

			if ($data['refund_type']<0) {
				$this->error('请选择退款账户');
			}

			if (empty($now_order['paid'])) {
				$this->error('当前订单还未付款！');
			}

			// 查询订单是否已经取消
			$is_cancel = D("Shop_order_log")->where(array('order_id' => $order_id,'status'=>10))->find();
			if ($is_cancel || $now_order['status'] ==5) {
				$this->error('订单已取消，不能退款！');
			}

			// 已退总金额
			// 用户申请退款
			$refund_money = M('Shop_order_refund')->field('sum(price) as r_price,sum(balance_pay) as r_balance_pay,sum(merchant_balance) as r_merchant_balance,sum(card_give_money) as r_card_give_money,sum(payment_money) as r_payment_money,sum(score_deducte) as r_score_deducte,sum(score_used_count) as r_score_used_count')->where(array('order_id'=>$order_id,'status'=>array('in','0,1,3')))->find();

			// 平台手动退款
			$refund_money_system = M('Shop_order_system_refund')->field('sum(price) as r_price,sum(balance_pay) as r_balance_pay,sum(merchant_balance) as r_merchant_balance,sum(card_give_money) as r_card_give_money,sum(payment_money) as r_payment_money,sum(score_deducte) as r_score_deducte,sum(score_used_count) as r_score_used_count')->where(array('order_id'=>$order_id,'status'=>1))->find();

			$refund_money['r_price'] = round(floatval($refund_money['r_price']+$refund_money_system['r_price']),2);
			$refund_money['r_balance_pay'] = round(floatval($refund_money['r_balance_pay']+$refund_money_system['r_balance_pay']),2);
			$refund_money['r_merchant_balance'] = round(floatval($refund_money['r_merchant_balance']+$refund_money_system['r_merchant_balance']),2);
			$refund_money['r_card_give_money'] = round(floatval($refund_money['r_card_give_money']+$refund_money_system['r_card_give_money']),2);
			$refund_money['r_payment_money'] = round(floatval($refund_money['r_payment_money']+$refund_money_system['r_payment_money']),2);
			$refund_money['r_score_deducte'] = round(floatval($refund_money['r_score_deducte']+$refund_money_system['r_score_deducte']),2);
			$refund_money['r_score_used_count'] = round(floatval($refund_money['r_score_used_count']+$refund_money_system['r_score_used_count']),2);

			// 在线支付详情
			$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>'shop','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
			if($now_order['payment_money'] == $payment_money_order['payment_money']){
				//可退金额包括用户实际支付的支付手续费
				$now_order['payment_money'] = $payment_money_order['paid_money'];
			}

			// 用户实际支付金额
			$total_money =  $now_order['payment_money']+$now_order['balance_pay']+$now_order['merchant_balance']+$now_order['card_give_money']+$now_order['score_deducte'];
			// 可退金额
			$can_refund = round($total_money - $refund_money['r_price'],2);

    		// 判断可退金额
			if ($data['price']<= 0) {
    			$this->error('退款金额不能小于等于0');
			}

			if ($data['price']>$can_refund) {
    			$this->error('退款金额不能大于可退款金额');
			}

			// 实际要退金额
			$can_refund_money = $data['price'];


            // 查看要退换的积分 积分不够则从退款金额抵扣 20191219
            $returnScoreBack = D('User')->refundScoreBack($now_order['uid'],$order_id,'shop',$can_refund_money);
            $can_refund_money = $returnScoreBack['refundMoney'];

			// 可退金额
			$balance_pay_can = 0;
			$card_give_money_can = 0;
			$merchant_balance_can = 0;
			$payment_money_can = 0;
			$score_used_count_can = 0;
			$score_deducte_can = 0;
			// 已退金额
			$balance_pay = 0;
			$card_give_money = 0;
			$merchant_balance = 0;
			$payment_money = 0;
			$score_used_count = 0;
			$score_deducte = 0;
			if ($data['refund_type']==0) {
				// 平台余额退款 优先级 ：平台余额>商家余额>积分抵扣>在线支付

				// 退平台余额
				if ($now_order['balance_pay'] - $refund_money['r_balance_pay'] > 0) {
					$balance_pay_can = $now_order['balance_pay'] - $refund_money['r_balance_pay'];

					if ($can_refund_money<$balance_pay_can) {
						// 退款金额小于平台余额可退金额 只退平台余额 结束
						$balance_pay_can = $can_refund_money;
					}

					$desc=$alias_name. '平台手动退款，增加余额，订单编号' . $now_order['real_orderid'];
				    $result = D('User')->add_money($now_order['uid'],$balance_pay_can,$desc);

					$param = array('refund_time' => time());
					if($result['error_code']){
						$param['err_msg'] = $result['msg'];
					} else {
						$balance_pay = $balance_pay_can;
						$param['refund_id'] = $now_order['order_id'];
					}

					$data_shop_order['order_id'] = $now_order['order_id'];
					$data_shop_order['refund_detail'] = serialize($param);
				}

				// 剩余需要退款的金额
				$can_refund_money = $data['price']-$balance_pay_can;

				// 会员卡赠送余额
				if ($now_order['card_give_money']>$refund_money['r_card_give_money']) {
					// 会员卡赠送金额可退金额
					$card_give_money_can = $now_order['card_give_money'] - $refund_money['r_card_give_money'];

					if ($can_refund_money<$card_give_money_can) {
						// 退款金额小于平台余额可退金额 只退平台余额 结束
						$card_give_money_can = $can_refund_money;
					}
				}

				// 剩余需要退款的金额
				$can_refund_money = $can_refund_money-$card_give_money_can;

				// 会员卡余额
				if ($now_order['merchant_balance']>$refund_money['r_merchant_balance'] && $can_refund_money >0) {
					// 会员卡金额可退金额
					$merchant_balance_can = $now_order['merchant_balance'] - $refund_money['r_merchant_balance'];

					if ($can_refund_money<$merchant_balance_can) {
						// 退款金额小于平台余额可退金额 只退平台余额 结束
						$merchant_balance_can = $can_refund_money;
					}
				}

				if ($card_give_money_can||$merchant_balance_can) {
					$result = D('Card_new')->add_user_money($now_order['mer_id'], $now_order['uid'], $merchant_balance_can,$card_give_money_can, 0, "平台手动退款，增加余额，订单编号".$now_order['real_orderid'],"商品退款，增加赠送余额，订单编号".$now_order['real_orderid']);
					$param = array('refund_time' => time());
					if ($result['error_code']) {
						$param['err_msg'] = $result['msg'];
					} else {
						$card_give_money = $merchant_balance_can;
						$merchant_balance = $merchant_balance_can;
					}
				}

				// 剩余需要退款的金额
				$can_refund_money = $can_refund_money-$merchant_balance_can;

				// 积分抵扣
				if ($now_order['score_deducte']>$refund_money['r_score_deducte'] && $can_refund_money >0) {
					// 会员卡金额可退金额
					$score_deducte_can = $now_order['score_deducte'] - $refund_money['r_score_deducte'];

					if ($can_refund_money<$score_deducte_can) {
						// 退款金额小于平台余额可退金额 只退平台余额 结束
						$score_deducte_can = $can_refund_money;
					}

					$score_used_count_can = round($score_deducte_can/$now_order['score_deducte']*$now_order['score_used_count'],2);

					$result = D('User')->add_score($now_order['uid'],$score_used_count_can, '平台手动退款，' . C('config.score_name') . '回滚，订单编号' . $now_order['real_orderid']);
					$param = array('refund_time' => time());
					if ($result['error_code']) {
						$param['err_msg'] = $result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
						$score_deducte = $score_deducte_can;
						$score_used_count = $score_used_count_can;
					}
				}

				// 剩余需要退款的金额
				$can_refund_money = $can_refund_money-$score_deducte_can;

				// 在线支付
				if ($now_order['payment_money']>$refund_money['r_payment_money'] && $can_refund_money >0) {
	                $payment_money_can = $now_order['payment_money'] - $refund_money['r_payment_money'];
					if ($can_refund_money<$payment_money_can) {
						// 退款金额小于平台余额可退金额 只退平台余额 结束
						$payment_money_can = $can_refund_money;
					}

					$result = D('User')->add_money($now_order['uid'],$payment_money_can, "平台手动退款，增加余额，订单编号".$now_order['real_orderid']);
					$go_refund_param = array('refund_time' => time());
					if($result['error_code']){
						$go_refund_param['err_msg'] = $result['msg'];
					} else {
						$payment_money = $payment_money_can;
					}
				}
			}else{
				// 原路退回 优先级：在线支付>平台余额>商家余额>积分抵扣
				// 退在线支付金额
				if ($now_order['payment_money'] - $refund_money['r_payment_money'] > 0) {
	                $payment_money_can = $now_order['payment_money'] - $refund_money['r_payment_money'];
					if($can_refund_money< $payment_money_can){
						$payment_money_can = $can_refund_money;
					}
					$now_order['payment_refund_money'] = $payment_money_can;
					if ($now_order['is_own']) {
	                    $pay_method = array();
	                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
	                    foreach ($merchant_ownpay as $ownKey => $ownValue) {
	                        $ownValueArr = unserialize($ownValue);
	                        if ($ownValueArr['open']) {
	                            $ownValueArr['is_own'] = true;
	                            $pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
	                        }
	                    }
	                } else {
	                    $is_wap = $now_order['pay_type'] == 'alipayh5' ? 1 : 0;
	                    $pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0);
	                }

	                if (empty($pay_method)) {
	                    $this->error('支付方式不存在或已关闭');
	                }

                    if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                        $pay_class_name = 'Hqpay';
                        empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                    }
	                if (empty($pay_method[$now_order['pay_type']])) {
	                    $this->error('支付方式不存在或已关闭');
	                }
                    if($payment_money_order['pay_method'] == 'mallbook'){//mallbook通道支付退款
                        import('@.ORG.pay.Mallbookpay.MallbookPayService');
                        $payService = new MallbookPayService();
                        $go_refund_param = $payService->refund(
                            $payment_money_order['orderid'],
                            $payment_money_order['paid_money'],
                            $payment_money_order['mallbook_order_no']
                        );
                    }else
	                if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'wepayez')){
						if($payment_money_order['pay_method'] == 'wft'){
							$pay_method = 'wftpay';
						}
						if($payment_money_order['pay_method'] == 'paylinx'){
							$pay_method = 'paylinx';
						}
						if($payment_money_order['pay_method'] == 'weikebaba'){
							$pay_method = 'weikebaba';
						}
						if($payment_money_order['pay_method'] == 'wepayez'){
							$pay_method = 'wepayez';
						}
	                	import('@.ORG.LowFeePay');
	                	$lowfeepay = new LowFeePay($pay_method);
	                	$now_order['order_type'] = 'shop';
	                	$now_order['orderNo'] = $payment_money_order['orderNo'];
	                	if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
	                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$now_order['mer_id']))->find();
	                        if(!empty($mer_juhe)){
	                            $lowfeepay->user_id = $mer_juhe['user_id'];
	                            $lowfeepay->key = $mer_juhe['key'];
	                            $now_order['is_own'] =1 ;
	                            $now_order['orderNo'] =$now_order['orderNo'];
	                        }

	                    }
	                	$go_refund_param= $lowfeepay->refund($now_order);
	                } else {
		                $pay_class_name != 'Hqpay' && $pay_class_name = ucfirst($now_order['pay_type']);
		            	fdump_sql($pay_class_name,'system_refund');
		                $import_result = import('@.ORG.pay.' . $pay_class_name);
		                if (empty($import_result)) {
	                    	$this->error('支付方式不存在或已关闭');
		                }

		                $now_order['order_type'] = 'shop';
		                $now_order['order_id'] = $now_order['orderid'];
		                if ($order['order_from'] == 4) {
		                    $pay_method['weixin']['config'] = array(
		                        'pay_weixin_appid' => C('config.pay_wxapp_appid'),
		                        'pay_weixin_key' => C('config.pay_wxapp_key'),
		                        'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
		                        'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
		                    );
		                    if ($now_order['is_own'] == 1) {
		                        $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
		                        $pay_method['weixin']['config'] = array(
		                            'pay_weixin_appid' => $wxapp_own_pay['appid'],
		                            'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
		                            'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
		                            'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
		                            'is_own' => 1
		                        );
		                    }
		                }
		                $now_user = D('User')->get_user($now_order['uid']);
		                if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
		                    $pay_class_name = 'AlipayApp';
		                    $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
		                    $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
		                    $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
		                }
		                $import_result = import('@.ORG.pay.' . $pay_class_name);
		                $pay_class = new $pay_class_name($now_order, $now_order['payment_refund_money'], $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $now_user, 1);
		                $go_refund_param = $pay_class->refund();
		            }
		            fdump_sql($go_refund_param,'system_refund');
	                if ($go_refund_param['error']==0 && $go_refund_param['type'] == 'ok') {
						$payment_money = $payment_money_can;
	                } else {
	                   	$this->error($go_refund_param['msg']);
	                }
				}

				// 剩余需要退款的金额
				$can_refund_money = $can_refund_money-$payment_money_can;

                //平台余额退款
				if ($now_order['balance_pay'] - $refund_money['r_balance_pay'] > 0 && $can_refund_money > 0) {
					$balance_pay_can = $now_order['balance_pay'] - $refund_money['r_balance_pay'];
					if ($can_refund_money<$balance_pay_can) {
						$balance_pay_can = $can_refund_money;
					}

	                $result = D('User')->add_money($now_order['uid'], $balance_pay_can, '平台手动退款,增加余额,订单编号' . $now_order['real_orderid']);
	                if(!$result['error_code']){
	                    $balance_pay = $balance_pay_can;
	                }
	            }

				// 剩余需要退款的金额
				$can_refund_money = $can_refund_money-$balance_pay_can;

	          	// 会员卡赠送余额
				if ($now_order['card_give_money']>$refund_money['r_card_give_money']) {
					// 会员卡赠送金额可退金额
					$card_give_money_can = $now_order['card_give_money'] - $refund_money['r_card_give_money'];

					if ($can_refund_money<$card_give_money_can) {
						// 退款金额小于平台余额可退金额 只退平台余额 结束
						$card_give_money_can = $can_refund_money;
					}
				}

				// 剩余需要退款的金额
				$can_refund_money = $can_refund_money-$card_give_money_can;

				// 会员卡余额
				if ($now_order['merchant_balance']>$refund_money['r_merchant_balance'] && $can_refund_money >0) {
					// 会员卡金额可退金额
					$merchant_balance_can = $now_order['merchant_balance'] - $refund_money['r_merchant_balance'];

					if ($can_refund_money<$merchant_balance_can) {
						// 退款金额小于平台余额可退金额 只退平台余额 结束
						$merchant_balance_can = $can_refund_money;
					}
				}

				if ($card_give_money_can||$merchant_balance_can) {
					$card_mer_id = $now_order['mer_id'];

		            // 商家联盟会员卡
		            if ($now_order['union_mer_id']) {
		                $card_mer_id = $now_order['union_mer_id'];
		            }

					$result = D('Card_new')->add_user_money($card_mer_id, $now_order['uid'], $merchant_balance_can,$card_give_money_can, 0, "平台手动退款，增加余额，订单编号".$now_order['real_orderid'],"商品退款，增加赠送余额，订单编号".$now_order['real_orderid']);
					$param = array('refund_time' => time());
					if ($result['error_code']) {
						$param['err_msg'] = $result['msg'];
					} else {
						$card_give_money = $merchant_balance_can;
						$merchant_balance = $merchant_balance_can;
					}
				}

				// 剩余需要退款的金额
				$can_refund_money = $can_refund_money-$merchant_balance_can;

				// 积分抵扣
				if ($now_order['score_deducte']>$refund_money['r_score_deducte'] && $can_refund_money >0) {
					// 会员卡金额可退金额
					$score_deducte_can = $now_order['score_deducte'] - $refund_money['r_score_deducte'];
					if ($can_refund_money<$score_deducte_can) {
						// 退款金额小于平台余额可退金额 只退平台余额 结束
						$score_deducte_can = $can_refund_money;
					}

					$score_used_count_can = round($score_deducte_can/$now_order['score_deducte']*$now_order['score_used_count'],2);

					$result = D('User')->add_score($now_order['uid'],$score_used_count_can, '平台手动退款，' . C('config.score_name') . '回滚，订单编号' . $now_order['real_orderid']);
					$param = array('refund_time' => time());

					if (!$result['error_code']) {
						$score_deducte = $score_deducte_can;
						$score_used_count = $score_used_count_can;
					}
				}
			}

            $data['refund_time'] = time();
            $data['status'] = 1;
            $data['order_id'] = $order_id;
            $data['score_deducte'] = $score_deducte;
            $data['score_used_count'] = $score_used_count;
            $data['balance_pay'] = $balance_pay;
            $data['merchant_balance'] = $merchant_balance;
            $data['card_give_money'] = $card_give_money;
            $data['payment_money'] = $payment_money;
            $data['uid'] = $now_order['uid'];
            $data['refund_price'] = $score_deducte+$balance_pay+$payment_money+$card_give_money+$merchant_balance;
            $data['system_id'] = $this->system_session['id'];

            // $data['third_refund_id'] = $third_refund_id;

            //Shop_refund_log status 状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请) 新增 5:平台同意退货 6:平台拒绝退货 8平台手动退款
            if($refund_id = D('Shop_order_system_refund')->add($data)){
            	$log_data = array();
            	$refund_reason = $this->refund_reason();
            	$log_data['note'] = $refund_reason[$date['data']];
            	$data['remark'] && $log_data['note'].'：'.$data['remark'];
            	$log_data['refund_id'] = $refund_id;
            	$log_data['status'] = 6;
            	$log_data['dateline'] = time();
            	D('Shop_refund_log')->add($log_data);

	            // 退还获得积分 20191219
	            $returnScoreBack = D('User')->refundScoreBack($now_order['uid'],$order_id,'shop',$data['price'],$refund_id,'shop_order_system_refund');

            	$saveData = array();
            	if (round($data['price']+$refund_money['r_price'],2)>=$total_money) {
            		// 完全退款，修改订单状态
            		$saveData['status'] = 4;
					$saveData['last_time'] = time();
               		D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
            	}
            	D('Shop_order')->where(array('order_id' => $order_id))->save($saveData);
            }else{
           		$this->error('退款记录添加失败');
            }
           	$this->success('退款成功');

    	}
    	$this->assign('refund_reason',$this->refund_reason());
    	$this->display();
    }

    public function system_refund_list(){
    	$order_id = intval($_GET['order_id']);
		// 已退总金额
		$refund_list = M('Shop_order_system_refund')->field('r.*,a.account')->where(array('r.order_id'=>$order_id))->join(' as r left join '.C('DB_PREFIX').'admin as a on a.id=r.system_id')->select();
    	$this->assign('refund_reason',$this->refund_reason());
		$this->assign('refund_list',$refund_list);
		$this->display();

    }

    // 平台手动退款原因
    public function refund_reason(){
    	return array(
    		1 => '平台原因',
			2 => '用户原因',
			3 => '递送员原因',
			4 => '餐厅原因',
			5 => '其他原因',
    	);
    }

     public function select_goods(){
        $selected = trim(I("selected"),',');
        $this->assign("selected", $selected);

        $type_arr = array();
        if($selected){
            $selected_arr = explode(',', $selected);
        }

        import('@.ORG.diypage');
        $condition = array();
        $database = D(_view('Shop_goods'));
        $condition['g.goods_type']=array('neq',1);
        if(C('config.is_show_jd_goods')==0){
            $condition['jd_sku_id'] = array('eq',0);
        }
        if($_GET['keyword']){
            $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
        }
        if($_GET['store_id']){
            $condition['g.store_id'] = $_GET['store_id'];
        }

        // 获得状态正常的分类
        $sortIds = D('Shop_goods_sort')->getAllShowChildSortIds();

        // 不能选择附属分类商品、
        // $shop_goods_sort = M('Shop_goods_sort')->where(['status'=>1])->select();
        // $sort_id = array_column($shop_goods_sort, 'sort_id');
        // $subsidiarySortIds = D('Shop_goods_sort')->getAllSubsidiarySortIds();

        // 排除已关闭的分类
        // $colseSortIds = D('Shop_goods_sort')->getAllCloseSortIds();

        // $sort_id = array_merge($subsidiarySortIds, $colseSortIds);

        if ($sortIds) {
        	$condition['g.sort_id'] = ['in',$sortIds];
        }else{
        	$condition['g.sort_id'] = null;
        }

        $field = 'g.goods_id, g.name, g.last_time, g.price';
        $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
        $p = new Page($count, 5);
        $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['select'] = 0;
            if(in_array($value['goods_id'], $selected_arr)){
                $goods_list[$key]['select'] = 1;
            }
        }

        $this->assign("keyword", $_GET['keyword']);
        $this->assign("good_list", $goods_list);
        $this->assign("pagebar", $p->show());
        $this->display();
    }

    public function ajaxGetGoods(){
        $selected = trim(I("selected"),',');
        $selected_arr = explode(',', $selected);
        $condition = array();
        $result = array();

        $database = D(_view('Shop_goods'));
        $condition['goods_id']=array('in',$selected_arr);
        $field = 'goods_id, name, last_time, price';
        $shopgoods_list = $database->field($field)->where($condition)->select();
        foreach ($shopgoods_list as $key => $value) {
            $result[] = $shopgoods_list[$key];
        }
        echo json_encode($result);
        exit;
    }

    // 快店店铺分类修改审核列表
    public function shop_category_apply(){
       	$where = array();
        $name = I('name', '', 'trim');
        if (!empty($name)) {
            $where['s.name'] = array('like', '%' . $name . '%');
        }
        $where['s.status'] = 1;
        $apply_store = array_column(M('Shop_category_relation_apply')->group('store_id')->select(), 'store_id');
        $where['sh.store_id'] = array('in',$apply_store);
        $count = M('Merchant_store_shop')
            ->join(' as sh inner join '.C('DB_PREFIX').'merchant_store as s on s.store_id=sh.store_id')
            ->where($where)
            ->count();
        import('@.ORG.system_page');
        $page = new Page($count, 30);
        $list = M('Merchant_store_shop')
            ->join(' as sh inner join '.C('DB_PREFIX').'merchant_store as s on s.store_id=sh.store_id')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->field('sh.store_id, s.name, s.phone')
            ->select();
        foreach ($list as &$value) {
           $last_cat = M('Shop_category_relation_apply')->where(['store_id'=>$value['store_id']])->order('addtime desc')->find();
           $value['cate_apply_time'] = $last_cat['addtime'];
        }
        $this->assign('list', $list);
        $this->assign('pagebar', $page->show());
        $this->display();
    }

    // 快店店铺分类修改审核
    public function do_apply(){
       	$store_id = $_GET['store_id'];
       	$agree = $_GET['agree'];
       	$now_store_shop = M('Merchant_store_shop')->where(['store_id'=>$store_id])->find();
       	if (!$now_store_shop) {
       		$this->error('店铺不存在');
       	}
       	if ($agree == 1) {
       		// 同意
       		$apply_category = M('Shop_category_relation_apply')->where(['store_id'=>$store_id])->select();
       		// var_dump($apply_category);
       		foreach($apply_category as $key => $value){
				$data_shop_category_relation[$key]['cat_id'] = $value['cat_id'];
				$data_shop_category_relation[$key]['cat_fid'] = $value['cat_fid'];
				$data_shop_category_relation[$key]['store_id'] = $store_id;

			}
			// 添加已审核的数据
			if(M('Shop_category_relation')->addAll($data_shop_category_relation)){
				// 删除待审核的数据
				M('Shop_category_relation_apply')->where(['store_id'=>$store_id])->delete();
				$this->success('操作成功');
       		}else{
       			$this->error('操作失败');
       		}
       	}else{
       		// 拒绝
       		if(M('Shop_category_relation_apply')->where(['store_id'=>$store_id])->delete()){
       			$this->success('操作成功');
       		}else{
       			$this->error('操作失败');
       		}
       	}
    }

    // 快店店铺分类修改查看
    public function show_category(){
       	$store_id = $_GET['store_id'];
       	$apply = $_GET['apply'];
       	if ($apply == 1) {
       		$relation_category_list = M('Shop_category_relation_apply')->where(['store_id'=>$store_id])->select();
       	}else{
       		$relation_category_list = M('Shop_category_relation')->where(['store_id'=>$store_id])->select();
       	}
		$category_ids = array_column($relation_category_list,'cat_id');

       	$database_shop_category = D('Shop_category');
		// $category_list = array_column($database_shop_category->select(),'cat_name','cat_id');
		$category_list = $database_shop_category->lists();
		$category_list_tmp = array();
		foreach ($category_list as $key => $_cat) {
			$temp_arr = array();
			$temp_arr['cat_name'] = $_cat['cat_name'];
			$has_sub = 0;
			foreach ($_cat['son_list'] as $_subcat) {
				if (in_array($_subcat['cat_id'], $category_ids)) {
					$has_sub = 1;
					$temp_arr['son_list'][]=array(
						'cat_name' => $_subcat['cat_name']
					);
				}
			}
			if ($has_sub) {
				$category_list_tmp[] = $temp_arr;
			}
		}
		$this->assign('category_list', $category_list_tmp);
		$this->display();
    }

    public function goods_price_modify_log()
    {
        $_GET['mer_status'] = 1;
        $rs = D('Shop_goods_price_modify_log')->get_lists($_GET);
        $this->assign($rs);
        $this->display();
    }

    public function show_spec()
    {
        $id = intval($_GET['id']);
        $log = D('Shop_goods_price_modify_log')->find($id);
        $specs = [];
        if ($log) {
            $specs = $log['spec_value'] ? json_decode($log['spec_value'], true) : [];
        }
        $this->assign('specs', $specs);
        $this->display();
    }

    /**
     * 外卖商品审核
     */
    public function goods_status(){
        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        if($open_status!=1){
            $this->frame_submit_tips(0, '未开启商品审核功能！');
        }
        $id = $_POST['goods_id']??'';
        $status = $_POST['status']??1;
        $status_content = $_POST['status_content']??'';
        $status_content = trim($status_content);
        $status_content = strip_tags($status_content);
        if(!$id){
            $this->frame_submit_tips(0, '商品不存在！');
        }
        if(!in_array($status,array(1,2))){
            $this->frame_submit_tips(0, '审核状态不存在！');
        }
        $condition['goods_id'] = intval($id);
        $systemGoods = D('System_goods');
        $now_goods = $systemGoods->field("*")->where($condition)->find();
        if (empty($now_goods)) {
            $this->frame_submit_tips(0,'该商品不存在！');
        }
        $save_data = [
            'status' => intval($status),
            'status_content' => strval($status_content)
        ];
        $where['goods_id'] = intval($id);
        $res = $systemGoods->where($where)->save($save_data);
        if($res){
            $this->frame_submit_tips(1, '审核成功！');
        }else{
            $this->frame_submit_tips(0, '审核失败！');
        }

    }

    /**
     * 商品列表-快速审核
     */
    public function goods_fast_status(){
        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        if($open_status!=1){
            $this->frame_submit_tips(0, '未开启商品审核功能！');
        }
        $ids = $_GET['id']?:$_POST['id'];
        if(!$ids){
            $this->frame_submit_tips(0, '请选择要审核的商品！');
        }
        if($this->isPost()){
            $status = $_POST['status']??1;
            $status_content = $_POST['status_content']??'';
            $status_content = trim($status_content);
            $status_content = strip_tags($status_content);
            $goods_model = D('System_goods');
            //获取所有未审核商品
            $ids = explode(',',$ids);
            $goods_list = $goods_model->field('goods_id')->where(['goods_id'=>array('in',$ids)])->select();
            foreach ($goods_list as $item){
                $goods_model->where(['goods_id'=>$item['goods_id']])->save(['status'=>$status,'status_content'=>$status_content]);
            }
            $this->frame_submit_tips(1, '快速审核成功！');
        }
        $this->assign('ids',$ids);
        $this->display();
    }

    /**
     * 商品审核-平台商品列表
     */
    public function shop_goods()
    {
        $systemGoodsSort= D('System_goods_sort');
        $sort_list = $systemGoodsSort->field(true)->where(array('status' => 1))->order('`sort_id` DESC')->select();
        $list = array();
        foreach ($sort_list as $row) {
            $list[$row['sort_id']] = $row;
        }
        $this->assign('sort_list', $sort_list);
        //$where = array('status' => 1);
        $status = $_GET['status']??3;
        $where = [];
        if(!empty($_GET['keyword'])){
            $where['name'] = array('like', '%' . htmlspecialchars(trim($_GET['keyword'])) . '%');
        }

        if (intval($_GET['sort_id'])) {
            $where['sort_id'] = intval($_GET['sort_id']);
        }
        if(in_array($status,array(0,1,2))){
            $where['status'] = $status;
        }

        $systemGoods = D('System_goods');
        $count= $systemGoods->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $goods_list = $systemGoods->where($where)->order('`goods_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $goods_image_class = new goods_image();
        $status_name = array('未审核','已审核','审核失败');
        foreach ($goods_list as &$goods) {
            $goods['sort_name'] = isset($list[$goods['sort_id']]['name']) ? $list[$goods['sort_id']]['name'] : '';
            if(!empty($goods['image'])){
                $tmp_pic_arr = explode(';', $goods['image']);
                $image = '';
                foreach ($tmp_pic_arr as $key => $value) {
                    if (empty($image)) $image = $goods_image_class->get_image_by_path($value, 's');
                }
                $goods['image'] = $image;
            }
            //浏览量
            $browse_num = (int)$goods['browse_num'];
            $goods['browse_num'] = $browse_num >= 10000  ? round(($browse_num / 10000 ), 1) . '万' : $browse_num;
            $goods['status_name'] = $status_name[$goods['status']]??'';
            $goods['last_time'] = $goods['last_time']?date('Y-m-d H:i:s',$goods['last_time']):'';
        }
        $not_audit_num_system = $systemGoods->where(['status'=>0])->count();
        $not_audit_num_system = $not_audit_num_system > 99 ? '99+' : $not_audit_num_system;
//        $not_audit_num_shop = D('Shop_goods')->where(['audit_status'=>0])->count();
        $not_audit_num_shop = D('Shop_goods')->join('as a inner join '.C('DB_PREFIX').'shop_goods_sort as b on a.sort_id = b.sort_id')->field('c.mer_id')->where(['a.audit_status'=>0])->join('inner join '.C('DB_PREFIX').'merchant_store c on a.store_id = c.store_id')->count();
        $not_audit_num_shop = $not_audit_num_shop > 99 ? '99+' : $not_audit_num_shop;
        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        $this->assign('open_status', $open_status);
        $this->assign('goods_list', $goods_list);
        $this->assign('pagebar', $p->show());
        $this->assign('status',$status);
        $this->assign('not_audit_num_system',$not_audit_num_system);
        $this->assign('not_audit_num_shop',$not_audit_num_shop);
        $this->display();
    }

    /**
     * 商品审核-店铺商品列表
     * @throws Exception
     */
    public function store_goods(){
       if ($_GET['sort_id']) {
           $condition_goods['a.sort_id'] = $_GET['sort_id'];
        }
        if (isset($_GET['status']) || isset($_GET['stock_num'])) {
            if (isset($_GET['status'])) {
                $condition_goods['a.status'] = $_GET['status'];
            }

            if (isset($_GET['stock_num'])) {
                $condition_goods['a.stock_num'] = $_GET['stock_num'];
            }
        }
        $audit_status = $_GET['audit_status']??3;
        if(!empty($_GET['keyword'])){
            $condition_goods['a.name'] = array('like', '%' . htmlspecialchars(trim($_GET['keyword'])) . '%');
        }

        if (intval($_GET['sort_id'])) {
            $condition_goods['a.sort_id'] = intval($_GET['sort_id']);
        }
        if(in_array($audit_status,array(0,1,2))){
            $condition_goods['a.audit_status'] = $audit_status;
        }
        //dump($where);exit();
        $database_goods = M('Shop_goods');
        $count_goods = $database_goods->join('as a inner join '.C('DB_PREFIX').'shop_goods_sort as b on a.sort_id = b.sort_id')->field('a.*,b.sort_name,c.name as store_name,c.store_id,c.mer_id')->where($condition_goods)->join('inner join '.C('DB_PREFIX').'merchant_store c on a.store_id = c.store_id')->count();
       /* import('@.ORG.diypage');
        $p = new Page($count_goods, 30);*/
        import('@.ORG.system_page');
        $p = new Page($count_goods, 20);

        $goods_list = $database_goods->join('as a inner join '.C('DB_PREFIX').'shop_goods_sort as b on a.sort_id = b.sort_id')->field('a.*,b.sort_name,c.name as store_name,c.store_id,c.mer_id')->where($condition_goods)->join('inner join '.C('DB_PREFIX').'merchant_store c on a.store_id = c.store_id')->order('a.sort DESC, a.goods_id ASC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $today = date('Ymd');
        $now_time = time();

        $goods_image_class = new goods_image();
        $audit_name = array('未审核','已审核','审核失败');
        foreach ($goods_list as &$rl) {
            $rl['audit_name'] = $audit_name[$rl['audit_status']]??'';
            $rl['last_time'] = $rl['last_time']?date('Y-m-d H:i:s',$rl['last_time']):'';

            if ($rl['sell_day'] != $today) {
                $rl['today_sell_count'] = 0;
            }
            $rl['seckill_status'] = false;
            if($rl['seckill_open_time'] < $now_time && $rl['seckill_close_time'] > $now_time){
                $rl['seckill_status'] = true;
                $rl['stock_num_t'] = $rl['seckill_stock'].L_('（限时优惠）');
            }
            elseif ($rl['stock_num'] == -1) {
                $rl['stock_num_t'] = L_('无限');
            } else {
                $rl['stock_num_t'] = $rl['stock_num'];
            }
            if($rl['cat_fid']>0){
                $rl['shop_type']=1;
            }
            $rl['add_time'] = date('Y-m-d H:i:s',$rl['add_time']);

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

            //浏览量
            $browse_num = (int)D('Shop_goods_views')->where('goods_id = ' . $rl['goods_id'])->getField('count');
            $rl['browse_num'] = $browse_num >= 10000 ? round(($browse_num / 10000), 1) . '万' : $browse_num;

        }
        $not_audit_num_system = D('System_goods')->where(['status'=>0])->count();
        $not_audit_num_system = $not_audit_num_system > 99 ? '99+' : $not_audit_num_system;
//        $not_audit_num_shop = $database_goods->where(['audit_status'=>0])->count();
        $not_audit_num_shop = D('Shop_goods')->join('as a inner join '.C('DB_PREFIX').'shop_goods_sort as b on a.sort_id = b.sort_id')->field('c.mer_id')->where(['a.audit_status'=>0])->join('inner join '.C('DB_PREFIX').'merchant_store c on a.store_id = c.store_id')->count();
        $not_audit_num_shop = $not_audit_num_shop > 99 ? '99+' : $not_audit_num_shop;
        $this->assign('goods_list', $goods_list);
        $this->assign('pagebar', $p->show());
        $this->assign('audit_status',$audit_status);
        $this->assign('not_audit_num_system',$not_audit_num_system);
        $this->assign('not_audit_num_shop',$not_audit_num_shop);
        $this->display();
    }

    /**
     * 店铺商品快速审核
     */
    public function goods_fast_audit(){
        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        if($open_status!=1){
            $this->frame_submit_tips(0, '未开启商品审核功能！');
        }
        $ids = $_GET['id']?:$_POST['id'];
        if(!$ids){
            $this->frame_submit_tips(0, '请选择要审核的商品！');
        }
        if($this->isPost()){
            $audit_status = $_POST['audit_status']??1;
            $status_content = $_POST['audit_content']??'';
            $status_content = trim($status_content);
            $status_content = strip_tags($status_content);
            $goods_model = D('Shop_goods');
            //获取所有未审核商品
            $ids = explode(',',$ids);
            $goods_list = $goods_model->field('goods_id')->where(['goods_id'=>array('in',$ids)])->select();
            $where = [
                'audit_status' => $audit_status,
                'audit_content' => $status_content,
                'status' => $audit_status==1?1:0,
            ];
            foreach ($goods_list as $item){
                $goods_model->where(['goods_id'=>$item['goods_id']])->save($where);
            }
            $this->frame_submit_tips(1, '快速审核成功！');
        }
        $this->assign('ids',$ids);
        $this->display();
    }

    /**
     * 店铺商品单个审核
     */
    public function store_goods_audit(){
        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        if($open_status!=1){
            $this->frame_submit_tips(0, '未开启商品审核功能！');
        }
        $id = $_POST['goods_id']??'';
        $audit_status = $_POST['audit_status']??1;
        $audit_status_content = $_POST['audit_status_content']??'';
        $audit_status_content = trim($audit_status_content);
        $audit_status_content = strip_tags($audit_status_content);
        if(!$id){
            $this->frame_submit_tips(0, '商品不存在！');
        }
        if(!in_array($audit_status,array(1,2))){
            $this->frame_submit_tips(0, '审核状态不存在！');
        }
        $condition['goods_id'] = intval($id);
        $systemGoods = D('Shop_goods');
        $now_goods = $systemGoods->field("*")->where($condition)->find();
        if (empty($now_goods)) {
            $this->frame_submit_tips(0,'该商品不存在！');
        }
        if($audit_status==1){
            $status = 1;
        }else{
            $status = 0;
        }
        $save_data = [
            'status' => $status,
            'audit_status' => intval($audit_status),
            'audit_content' => strval($audit_status_content)
        ];
        $where['goods_id'] = intval($id);
        $res = $systemGoods->where($where)->save($save_data);
        if($res){
            $this->frame_submit_tips(1, '审核成功！');
        }else{
            $this->frame_submit_tips(0, '审核失败！');
        }
    }
}
