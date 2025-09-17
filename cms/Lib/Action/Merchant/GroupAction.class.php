<?php

/*
 * 团购
 */

class GroupAction extends BaseAction {
    /* 团购列表 */ 

    public function index() {

        $database_group = D('Group');
        $condition_where='mer_id = '.$this->merchant_session['mer_id'].' AND status <> 4';
        if(!empty($_GET['keyword'])){
            switch($_GET['searchtype']){
                case 'group_id':
                    $condition_where .= " AND `group_id`=" . intval($_GET['keyword']);
                    break;
                case 's_name':
                    $condition_where .= " AND `s_name` LIKE '%" . $_GET['keyword'] . "%'";
                    break;
                case 'name':
                    $condition_where .= " AND `name` LIKE '%" . $_GET['keyword'] . "%'";
                    break;
            }
        }


        if(!empty($_GET['searchstatus'])){
            $now_time = $_SERVER['REQUEST_TIME'];
            switch($_GET['searchstatus']){
                case '1':
                    $condition_where .= " AND `status`='1'  AND `type`='1' AND `begin_time`<'$now_time' AND `end_time`>'$now_time'";
                    break;
                case '2':
                    $condition_where .= " AND (`status`<>'1' OR `type`<>'1' OR `begin_time`>'$now_time' OR `end_time`<'$now_time')";
                    break;
            }
        }

        $group_count = $database_group->where($condition_where)->count();

        import('@.ORG.merchant_page');
        $p = new Page($group_count, 20);
        $group_list = $database_group->field(true)->where($condition_where)->order('`group_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $group_image_class = new group_image();
        $d_group_order = D('Group_order');
        foreach ($group_list as $key => $value) {
            $tmp_pic_arr = explode(';', $value['pic']);
            $group_list[$key]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
            // 根据配置获取规格信息
            // 查询规格售出数量
            $get_spec_sale_count = $d_group_order->get_spec_sale_count($value['group_id']);
            if ($get_spec_sale_count > 0) {
                $group_list[$key]['sale_count'] = $get_spec_sale_count + intval($value['sale_count']);
                $group_list[$key]['sale_txt'] = L_('已售') . $group_list[$key]['sale_count'];
            }
            $arr = $d_group_order->get_spec_count_num($value['group_id']);
            //原始库存（开启了规格-原始库存为规格库存总数）
            if ($arr['spec_num'] > 0) {
                $group_list[$key]['count_num'] = $arr['count_num_total'];
            }

        }
        $this->assign('group_list', $group_list);

        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        $this->display();
    }

    public function add() {
        if (IS_POST) {
            if (empty($_POST['name'])) {
                $this->error(L_('请填写商品标题'));
            }
            if (empty($_POST['s_name'])) {
                $this->error(L_('请填写商品名称'));
            }
            if (empty($_POST['intro'])) {
                $this->error(L_('请填写商品简介'));
            }

            if($_POST['tuan_type']==2 && $_POST['open_express']==0 && $_POST['pick_in_store']==0){
                $this->error(L_('请选择一种配送方式'));
            }

            //判断关键词
            $keywords = trim($_POST['keywords']);
            if (!empty($keywords)) {
                $tmp_key_arr = explode(' ', $keywords);
                $key_arr = array();
                foreach ($tmp_key_arr as $value) {
                    if (!empty($value)) {
                        array_push($key_arr, $value);
                    }
                }
                if (count($key_arr) > 5) {
                    $this->error(L_('关键词最多5个。'));
                }
            }

            if ($_POST['old_price']<0 && $this->config['open_extra_price']==0) {
                $this->error(L_('请填写商品原价'));
            }
            if ($_POST['price'] <0 &&$this->config['open_extra_price']==0) {
                $this->error(L_('请填写商品X1价',$this->config['group_alias_name']));
            }
			if($_POST['price'] - $_POST['wx_cheap'] < 0){
				$this->error(L_('商品价格减去微信优惠不能小于0'));
			}
            if (empty($_POST['store'])) {
                $this->error(L_('请至少选择一家店铺'));
            }

            if (!$_POST['cat_id'] && $this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                if(empty($_POST['group_category'])){
                    $this->error(L_('请至少选一个分类！'));
                }
                $cat_ids = array();
                foreach ($_POST['group_category'] as $cat_a) {
                    $a = explode('-', $cat_a);
                    $cat_ids[] = array('cat_fid' => $a[0], 'cat_id' => $a[1]);
                }
            }
            $group_content = reset($_POST['group_content']);
            if (empty($_POST['content']) && empty($group_content)) {
                $this->error(L_('请填写本单详情'));
            }
            if ($_POST['group_content'] && count($_POST['group_content']) > 1) {
                foreach ($_POST['group_content'] as $val) {
                    if (!$val['name']) {
                        $this->error(L_('请填写详情名称'));
                    } elseif (!$val['content']) {
                        $this->error(L_('请填写【X1】详情内容',$val['name']));
                    }
                }
            }
            if (empty($_POST['content']) && $group_content) {
                $_POST['content'] = $group_content['content'];
            }
            if (empty($_POST['pic'])) {
                $this->error(L_('请至少上传一张照片'));
            }
            if (empty($_POST['success_num'])) {
                $this->error(L_('成功X1人数要求至少为1人',$this->config['group_alias_name']));
            }
            //商品总数量
            if ($_POST['count_num'] > 0 && $_POST['count_num'] < $_POST['once_min']) {
            	$this->error(L_('商品总数量不能小于一次最少购买数量'));
            }
            
            if ($_POST['once_max'] > 0 && $_POST['once_min'] > $_POST['once_max']) {
            	$this->error('ID最多购买数量不能小于一次最少购买数量');
            }
            // 上传了规格信息 就对应判断规格信息
            if ($_POST['specifications']) {
                if (count($_POST['specifications']) > 6) {
                    $this->error(L_('对应团购规格最多添加6个！'));
                }
                $specifications_name_arr = array();
                foreach ($_POST['specifications'] as $val) {
                    // 判断 规格名必须存在
                    if (!$val['specifications_name']) {
                        $this->error(L_('请填写对应规格名称！'));
                    } elseif (in_array($val['specifications_name'], $specifications_name_arr)) {
                        $this->error(L_('【X1】规格名称重复！',$val['specifications_name']));
                    } elseif (!$val['price']) {
                        // 判断价格必须存在
                        $this->error(L_('请填写【X1】规格价格！',$val['specifications_name']));
                    } elseif ($val['count_num'] > 0 && $val['count_num'] < $val['once_min']) {
                        // 判断商品总数量不能小于一次最少购买数量
                        $this->error(L_('商品总数量不能小于同一ID一次最少购买数量'));
                    } elseif ($val['once_max'] > 0 && $val['once_max'] < $val['once_min']) {
                        // 判断同一ID最多购买数量不能小于一次最少购买数量
                        $this->error(L_('同一ID最多购买数量不能小于一次最少购买数量'));
                    } elseif ($val['once_max_day'] > 0 && $val['once_max_day'] < $val['once_min']) {
                        // 判断同一ID每日最多购买数量不能小于一次最少购买数量
                        $this->error(L_('同一ID每日最多购买数量不能小于一次最少购买数量'));
                    } elseif ($val['once_min'] <= 0) {
                        // 一次最少购买数量不得小于0
                        $this->error(L_('一次最少购买数量不得小于0'));
                    }
                    $specifications_name_arr[] = $val['specifications_name'];
                }
            }
			
			if($_POST['bind_trade'] == 0){
				unset($_POST['appoint_id']);
			}else if($_POST['bind_trade'] == 1){
				if(empty($_POST['appoint_id'])){
                    $this->error(L_('绑定预约不能为空！'));
                }
				$_POST['is_appoint_bind'] = 1;
				$_POST['trade_type'] = 'appoint';
				$_POST['trade_info'] = $_POST['appoint_id'];
			}else if($_POST['bind_trade'] == 2){
				if(empty($_POST['trade_hotel_cat_id'])){
                    $this->error(L_('请选择酒店分类'));
                }
                if($_POST['stock_reduce_method']==1){
                    $this->error(L_('酒店库存仅支持支付完成后减库存'));
                }

                if($_POST['count_num']>0 ){
                    $_POST['count_num'] = 0;
                }
                $_POST['pin_num'] = 0;
                $_POST['pin_effective_time'] = 0;
				$_POST['trade_type'] = 'hotel';
				$_POST['trade_info'] = implode(',',$_POST['trade_hotel_cat_id']);
			}
			if (isset($_POST['pin_num']) && $_POST['pin_num']==1) {
                $this->error(L_('拼团数量最少设置为2'));
            }
            
            isset($_POST['tagname']) && $_POST['tagname'] = trim($_POST['tagname']);
            $packageid = isset($_POST['packageid']) ? intval($_POST['packageid']) : 0;
			 if(($packageid>0) && empty($_POST['tagname'])){
			     $this->error($this->config['group_alias_name'] .L_('套餐标签必须要写上！'));
			 }
            $leveloff = isset($_POST['leveloff']) ? $_POST['leveloff'] : false;
            unset($_POST['leveloff']);
			
			$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			
			/*foreach($_POST['pic'] as $kp => $vp){
				$tmp_vp = explode(',',$vp);
				$_POST['pic'][$kp]=$rand_num.','.$tmp_vp[1];
			}*/
            //处理选择图片链接存在逗号隔开的
            foreach ($_POST['pic'] as $k => $v) {
                if (false !== strpos($v, ',')) {
                    $splistArr = explode(',', $v);
                    $_POST['pic'][$k] = '/upload/group/' . $splistArr[0] . '/' . $splistArr['1'];
                }
            }
            $_POST['pic'] = implode(';', $_POST['pic']);
		
			
            $_POST['cue'] = '';
            if ($_POST['cue_field']) {
                $cue_field = array();
                foreach ($_POST['cue_field']['value'] as $key => $value) {
                    array_push($cue_field, array('key' => $_POST['cue_field']['key'][$key], 'value' => $value));
                }
                $_POST['cue'] = serialize($cue_field);
            }
            if (is_array($_POST['custom'])) {
                foreach ($_POST['custom'] as $key => $value) {
                    if (is_array($value)) {
                        $_POST[$key] = implode(',', $value);
                    } else {
                        $_POST[$key] = $value;
                    }
                }
            }

            $_POST['content'] = fulltext_filter($_POST['content']);
//            $_POST['discount'] = $_POST['price'] / $_POST['old_price'] * 10;

            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            if ($this->config['group_verify']) {
                $_POST['status'] = $this->merchant_session['issign'] ? 1 : 2;
            } else {
                $_POST['status'] = 1;
            }

            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['begin_time'] = strtotime($_POST['begin_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['deadline_time'] = strtotime($_POST['deadline_time']);
            // 处理时间 - 结束时间必须大于开始时间  团购券有效时间必须大于等于结束时间
            if (intval($_POST['begin_time']) > intval($_POST['end_time'])) {
                $this->error(L_('结束时间必须大于开始时间！'));
            }
            if (intval($_POST['end_time']) > intval($_POST['deadline_time'])) {
                $this->error($this->config['group_alias_name'] . L_('券有效期必须不小于结束时间！'));
            }

            //店铺信息
            $database_merchant_store = D('Merchant_store');
            foreach ($_POST['store'] as $key => $value) {
                $condition_merchant_store['store_id'] = $value;
                $tmp_group_store = $database_merchant_store->field('`store_id`,`province_id`,`city_id`,`area_id`,`circle_id`')->where($condition_merchant_store)->find();
                if (!empty($tmp_group_store)) {
                    $data_group_store_arr[] = $tmp_group_store;
                }
                //给店铺添加分类
                if (!($store_catgory = D('Store_category')->field(true)->where(array('cat_id' => intval($_POST['cat_fid']), 'store_id' => $value))->find())) {
                    D('Store_category')->add(array('cat_id' => intval($_POST['cat_fid']), 'store_id' => $value));
                }
            }

            if (empty($data_group_store_arr)) {
                $this->error(L_('您选择的店铺信息不正确！请重试。'));
            } else if ($_POST['tuan_type'] == 2) {
                $_POST['prefix_title'] = L_('购物');
            } else if (count($data_group_store_arr) == 1) {
                $circle_info = D('Area')->get_area_by_areaId($data_group_store_arr[0]['circle_id']);
                if (empty($circle_info)) {
                    $this->error(L_('您选择的店铺区域商圈信息不正确！请修改店铺资料后重试。可能店铺以前在添加时没有选择商圈，重新选择商圈点击保存即可。'));
                }
                $_POST['prefix_title'] = $circle_info['area_name'];
            } else {
                $_POST['prefix_title'] = count($data_group_store_arr) . L_('店通用');
            }

            $newleveloff = array();
            if (!empty($leveloff)) {
                foreach ($leveloff as $kk => $vv) {
                    $vv['type'] = intval($vv['type']);
                    $vv['vv'] = intval($vv['vv']);
                    if (($vv['type'] > 0) && ($vv['vv'] > 0)) {
                        $vv['level'] = $kk;
                        $newleveloff[$kk] = $vv;
                    }
                }
            }

            $_POST['leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';
            if ($leveloff === false){
                unset($_POST['leveloff']);
			}
			// 处理一下 在团购券有效期前时间可以取消订单
            if ($_POST['no_refund'] && $_POST['no_refund'] == 1) {
                $_POST['before_effective_time'] = 0;
            }
            $database_group = D('Group');
            // 如果有团购地址定位 记录改地址
            if ($_POST['long_lat']) {
                $long_lat = explode(',',$_POST['long_lat']);
                $_POST['long'] = $long_lat[0];
                $_POST['lat'] = $long_lat[1];
                $now_city = D('Area')->cityMatching($_POST['lat'],$_POST['long']);
                if ($_POST['province_id']<=0) {
                    $_POST['province_id'] = $now_city['area_info']['province_id'];
                }
                if ($_POST['city_id']<=0) {
                    $_POST['city_id'] = $now_city['area_info']['city_id'];
                }
                if ($_POST['area_id']<=0) {
                    $_POST['area_id'] = $now_city['area_info']['area_id'];
                }
            }
            $_POST['add_time'] = time();
            if ($group_id = $database_group->data($_POST)->add()) {
                // 上传了规格信息 就对应添加规格信息
                if ($_POST['specifications']) {
                    $time = time();
                    $m_group_specifications = M('Group_specifications');
                    foreach ($_POST['specifications'] as $val) {
                        $specifications_arr = array(
                            'group_id' => $group_id,
                            'specifications_name' => $val['specifications_name'],
                            'price' => $val['price'],
                            'old_price' => $val['old_price'] ? $val['old_price'] : 0,
                            'count_num' => $val['count_num'] ? $val['count_num'] : 0,
                            'once_max' => $val['once_max'] ? $val['once_max'] : 0,
                            'once_max_day' => $val['once_max_day'] ? $val['once_max_day'] : 0,
                            'once_min' => $val['once_min'] ? $val['once_min'] : 0,
                            'add_time' => $time,
                        );
                        $m_group_specifications->data($specifications_arr)->add();
                    }
                }
                // 团购二级分类多选关联
                if ($cat_ids && $this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                    $database_group_bind_category = M('Group_bind_category');
                    $time = time();
                    $data_group_bind_category = array();
                    foreach($cat_ids as $key => $value){
                        $data_group_bind_category[$key]['cid'] = $value['cat_id'];
                        $data_group_bind_category[$key]['cat_fid'] = $value['cat_fid'];
                        $data_group_bind_category[$key]['group_id'] = $group_id;
                        $data_group_bind_category[$key]['add_time'] = $time;
                    }
                    if ($data_group_bind_category) {
                        $database_group_bind_category->addAll($data_group_bind_category);
                    }
                }
                // 团购多详情显示
                if ($_POST['group_content']) {
                    $group_content_arr = $_POST['group_content'];
                    $num = count($group_content_arr);
                    $time_info = time();
                    $m_group_content = M('Group_content');
                    $data = array();
                    $data['add_time'] = $time_info;
                    $data['group_id'] = $group_id;
                    foreach ($group_content_arr as $val) {
                        if ($num == 1 && !$val['name']) {
                            $data['name'] = L_('商品详情');
                        } elseif ($val['name']) {
                            $data['name'] = $val['name'];
                        }
                        $data['content'] = fulltext_filter($val['content']);
                        $m_group_content->data($data)->add();
                    }
                }

                $database_group_store = D('Group_store');
                foreach ($data_group_store_arr as $key => $value) {
                    $data_group_store = $value;
                    $data_group_store['group_id'] = $group_id;
                    $data_group_store['package_id'] = !empty($_POST['package_id']) && isset($_POST['package_id'][$value['store_id']]) ? $_POST['package_id'][$value['store_id']] : 0;
                    $database_group_store->data($data_group_store)->add();
                }

                //判断关键词
                if (!empty($key_arr)) {
                    $database_keywords = D('Keywords');
                    $data_keywords['third_id'] = $group_id;
                    $data_keywords['third_type'] = 'group';
                    foreach ($key_arr as $value) {
                        $data_keywords['keyword'] = $value;
                        $database_keywords->data($data_keywords)->add();
                    }
                }

                //添加或删除到套餐
                if ($packageid > 0) {
                    $mpackageDb = M('Group_packages');
                    $mpackage = $mpackageDb->where(array('id' => $packageid, 'mer_id' => $this->merchant_session['mer_id']))->find();
                    if (!empty($mpackage)) {
                        $mpackage['groupidtext'] = !empty($mpackage['groupidtext']) ? unserialize($mpackage['groupidtext']) : array();
                        $mpackage['groupidtext'][$group_id] = $_POST['tagname'];
                        $mpackageDb->where(array('id' => $mpackage['id']))->save(array('groupidtext' => serialize($mpackage['groupidtext'])));
                    }
                }
                $this->success(L_('添加成功！'),U('Group/index'));
            } else {
                $this->error(L_('添加失败！请重试。'));
            }
        } else {
            $database_group_category = D('Group_category');
            $condition_f_group_category['cat_fid'] = 0;
            $condition_f_group_category['cat_status'] = 1;
            $f_category_list = $database_group_category->field('`cat_id`,`cat_name`,`cat_field`,`cue_field`,`editor_num`,`editor_title`')->where($condition_f_group_category)->order('`cat_sort` DESC,`cat_id` ASC')->select();
            if (empty($f_category_list)) {
                $this->error(L_('管理员没有添加X1分类！',$this->config['group_alias_name']));
            }
            $this->assign('f_category_list', $f_category_list);
            $condition_s_group_category['cat_status'] = 1;
            foreach ($f_category_list as $val) {
                if ($val['cat_id']) {
                    $condition_s_group_category['cat_fid'] = $val['cat_id'];
                    $s_category_list = $database_group_category->field('`cat_id`,`cat_name`')->where($condition_s_group_category)->order('`cat_sort` DESC,`cat_id` ASC')->select();
                    if ($s_category_list) {
                        $this->assign('s_category_list', $s_category_list);
                        break;
                    }
                }
             }
            if (empty($s_category_list)) {
                $this->error(L_('所有主分类下没有添加子分类！'));
            }

            if (!empty($f_category_list[0]['cat_field'])) {
                $cat_field = unserialize($f_category_list[0]['cat_field']);
                $custom_html = '';
                foreach ($cat_field as $key => $value) {
                    if (empty($value['use_field'])) {
                        $custom_html .= '<div class="form-group"><label class="col-sm-1">' . $value['name'] . '：</label>';
                        if ($value['type'] == 0) {
                            $custom_html .= '<select name="custom[custom_' . $key . ']" class="col-sm-1">';
                            foreach ($value['value'] as $k => $v) {
                                $custom_html .= '<option value="' . $k . '">' . $v . '</option>';
                            }
                            $custom_html .= '</select>';
                        } else {
                            foreach ($value['value'] as $k => $v) {
                                $custom_html .= '<label style="margin-right:30px;"><input class="ace" type="checkbox" name="custom[custom_' . $key . '][]" value="' . $k . '" id="custom_' . $key . '_' . $k . '"/><span class="lbl"><label for="custom_' . $key . '_' . $k . '">&nbsp;' . $v . '</label></span></label>';
                            }
                        }
                        $custom_html .= '</div>';
                    }
                }
            }
            $this->assign('custom_html', $custom_html);

            if (!empty($f_category_list[0]['cue_field'])) {
                $cue_field = unserialize($f_category_list[0]['cue_field']);
                $cue_html = '';
                foreach ($cue_field as $key => $value) {
                    $cue_html .= '<div class="form-group"><label class="col-sm-1">' . $value['name'] . '：</label>';
                    if ($value['type'] == 0) {
                        $cue_html .= '<input type="hidden" name="cue_field[key][]" value="' . $value['name'] . '"/><input type="text" class="col-sm-4" name="cue_field[value][]"/>';
                    } else {
                        $cue_html .= '<input type="hidden" name="cue_field[key][]" value="' . $value['name'] . '"/><textarea class="col-sm-4" rows="5" name="cue_field[value][]"></textarea>';
                    }
                    $cue_html .= '</div>';
                }
            }
            $this->assign('cue_html', $cue_html);

            $mer_id = $this->merchant_session['mer_id'];
            $db_arr = array(C('DB_PREFIX') . 'area' => 'a', C('DB_PREFIX') . 'merchant_store' => 's');
            $store_list = D()->table($db_arr)->field('a.`area_name`,s.`adress`,`s`.`name`,`s`.`store_id`')->where("`s`.`mer_id`='$mer_id' AND `s`.`status`='1' AND `s`.`have_group`='1' AND `s`.`area_id`=`a`.`area_id`")->order('`sort` DESC,`store_id` ASC')->select();
            if (empty($store_list)) {
				$this->assign('jumpUrl',U('Config/store'));
                $this->error(L_('您暂时还没有能添加X1信息的店铺！',$this->config['group_alias_name']));
            }
            $this->assign('store_list', $store_list);
            $db_arr = array(C('DB_PREFIX') . 'foodshop_goods_package' => 'p', C('DB_PREFIX') . 'foodshop_goods_package_detail' => 'd');
            $package_list = D()->table($db_arr)->field('p.`id`,p.store_id,p.`name`')->where("`p`.`id`= d.pid AND `p`.`status`='1' AND `p`.`is_order`='1' AND `d`.`goods_detail` <> ''")->order('p.`id` DESC')->select();
            $package_list = array_column($package_list, NUll, 'id');
            $this->assign('package_list', $package_list);
            $levelDb = M('User_level');
            $tmparr = M(_view('User_level'))->order('id ASC')->select();
            $levelarr = array();
            if ($tmparr && $this->config['level_onoff'] && $this->config['group_level_onoff']) {


                foreach ($tmparr as $vv) {

                    $vv['vv'] = $vv['boon'];
                    $vv['type'] = $vv['type'];
                    $vv['lid'] = $vv['id'];

                    $levelarr[$vv['level']] = $vv;
                }


            }

            unset($tmparr);
            $this->assign('levelarr', $levelarr);
            $mpackageDb = M('Group_packages');
            $mpackagelist = $mpackageDb->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->order('id DESC')->select();
            $this->assign('mpackagelist', $mpackagelist);
            
            if(isset($this->config['appoint_category_bgimg'])){
				$database_appoint = D('Appoint');
				$appoint_list = $database_appoint->get_appointmerchantlist_by_MerchantId($mer_id);
				$this->assign('appoint_list',$appoint_list);
			}
			
			$database_trade_hotel_category = D('Trade_hotel_category');
			$trade_hotel_category_list = $database_trade_hotel_category->where(array('mer_id'=>$mer_id,'is_remove'=>'0','cat_fid'=>'0'))->order('`cat_sort` DESC')->select();
			$this->assign('trade_hotel_category_list',$trade_hotel_category_list);


            $where_express = array('mer_id' => $this->merchant_session['mer_id']);
            $express_list = M('Express_template')->where($where_express)->select();

            $this->assign('express_list', $express_list);

            // 取默认数量-分类信息
            $info_category = reset($f_category_list);
            $this->assign('info_category', $info_category);
            $editor_title = array();
            if ($info_category['editor_title']) {
                $editor_title_info = $info_category['editor_title'] = unserialize($info_category['editor_title']);
            }
            if (empty($editor_title_info)) {
                $editor_title[] = L_('商品详情');
            } else {
                foreach ($editor_title_info as $val) {
                    if ($val) {
                        $editor_title[] = $val;
                    }
                }
            }
            $this->assign('editor_title', $editor_title);
            $this->assign('editor_title_count', count($editor_title));

            $this->display();
        }
    }
	
	public function edit() {
        if (empty($this->config['merchant_edit_group'])) {
            $this->error(L_('管理员没有给予编辑团购的权限'));
        }

        $database_group = D('Group');
        $condition_group['group_id'] = $_GET['group_id'];
        $now_group = $database_group->field(true)->where($condition_group)->find();
        if($now_group['trade_info']!=0){
            $now_group['trade_info'] = explode(',',$now_group['trade_info']);
        }
        if (empty($now_group)) {
            $this->error(L_('该X1不存在！',$this->config['group_alias_name']));
        }
        if (IS_POST) {
            if (empty($_POST['name'])) {
                $this->error(L_('请填写商品标题'));
            }
            if (empty($_POST['s_name'])) {
                $this->error(L_('请填写商品名称'));
            }
            if (empty($_POST['intro'])) {
                $this->error(L_('请填写商品简介'));
            }
            if($now_group['tuan_type']==2 && $_POST['open_express']==0 && $_POST['pick_in_store']==0){
                $this->error(L_('请选择一种配送方式'));
            }

            //判断关键词
            $keywords = trim($_POST['keywords']);
            if (!empty($keywords)) {
                $tmp_key_arr = explode(' ', $keywords);
                $key_arr = array();
                foreach ($tmp_key_arr as $value) {
                    if (!empty($value)) {
                        array_push($key_arr, $value);
                    }
                }
                if (count($key_arr) > 5) {
                    $this->error(L_('关键词最多5个。'));
                }
            }

            if ($_POST['old_price'] <0 &&$this->config['open_extra_price']==0) {
                $this->error(L_('请填写商品原价'));
            }
            if ($_POST['price']<0&&$this->config['open_extra_price']==0) {
                $this->error(L_('请填写商品X1价',$this->config['group_alias_name']));
            }
			if($_POST['price'] - $_POST['wx_cheap'] < 0){
				$this->error(L_('商品价格减去微信优惠不能小于0'));
			}
            if (empty($_POST['store'])) {
                $this->error(L_('请至少选择一家店铺'));
            }
            if (!$_POST['cat_id'] && $this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                if(empty($_POST['group_category'])){
                    $this->error(L_('请至少选一个分类！'));
                }
                $cat_ids = array();
                foreach ($_POST['group_category'] as $cat_a) {
                    $a = explode('-', $cat_a);
                    $cat_ids[] = array('cat_fid' => $a[0], 'cat_id' => $a[1]);
                }
                // 兼容app 和 pc 顾将多选的分类id第一个赋给原来的分类id
                if (reset($cat_ids)) {
                    $_POST['cat_id'] = reset($cat_ids)['cat_id'];
                }
            }
            $group_content = reset($_POST['group_content']);
            if (empty($_POST['content']) && empty($group_content)) {
                $this->error(L_('请填写本单详情'));
            }
            if ($_POST['group_content'] && count($_POST['group_content']) > 1) {
                foreach ($_POST['group_content'] as $val) {
                    if (!$val['name']) {
                        $this->error(L_('请填写详情名称'));
                    } elseif (!$val['content']) {
                        $this->error(L_('请填写【X1】详情内容',$val['name']));
                    }
                }
            }
            if (empty($_POST['content']) && $group_content) {
                $_POST['content'] = $group_content['content'];
            }
            if (empty($_POST['pic'])) {
                $this->error(L_('请至少上传一张照片'));
            }
            if (empty($_POST['success_num'])) {
                $this->error(L_('成功X1人数要求至少为1人',$this->config['group_alias_name']));
            }
            //商品总数量
            if ($_POST['count_num'] > 0 && $_POST['count_num'] < $_POST['once_min']) {
            	$this->error(L_('商品总数量不能小于一次最少购买数量'));
            }
            
            if ($_POST['once_max'] > 0 && $_POST['once_min'] > $_POST['once_max']) {
            	$this->error('ID最多购买数量不能小于一次最少购买数量');
            }
            
             if($_POST['is_appoint_bind'] && ($_POST['is_appoint_bind'] == 1)){
                if(!$_POST['appoint_id']){
                    $this->error(L_('绑定预约不能为空！'));
                }
            }
            // 上传了规格信息 就对应判断规格信息
            $specifications_id_arr = array();
            if ($_POST['specifications']) {
                if (count($_POST['specifications']) > 6) {
                    $this->error(L_('对应团购规格最多添加6个！'));
                }
                $specifications_name_arr = array();
                foreach ($_POST['specifications'] as $val) {
                    // 判断 规格名必须存在
                    if (!$val['specifications_name']) {
                        $this->error(L_('请填写对应规格名称！'));
                    } elseif (in_array($val['specifications_name'], $specifications_name_arr)) {
                        $this->error(L_('【').$val['specifications_name'].L_('】规格名称重复！'));
                    } elseif (!$val['price']) {
                        // 判断价格必须存在
                        $this->error(L_('请填写【X1】规格价格！',$val['specifications_name']));
                    } elseif ($val['count_num'] > 0 && $val['count_num'] < $val['once_min']) {
                        // 判断商品总数量不能小于一次最少购买数量
                        $this->error(L_('商品总数量不能小于同一ID一次最少购买数量'));
                    } elseif ($val['once_max'] > 0 && $val['once_max'] < $val['once_min']) {
                        // 判断同一ID最多购买数量不能小于一次最少购买数量
                        $this->error(L_('同一ID最多购买数量不能小于一次最少购买数量'));
                    } elseif ($val['once_max_day'] > 0 && $val['once_max_day'] < $val['once_min']) {
                        // 判断ID最多购买数量不能小于一次最少购买数量
                        $this->error(L_('同一ID每日最多购买数量不能小于一次最少购买数量'));
                    } elseif ($val['once_min'] <= 0) {
                        // 一次最少购买数量不得小于0
                        $this->error(L_('一次最少购买数量不得小于0'));
                    } elseif ($val['specifications_id']) {
                        $specifications_id_arr[] = $val['specifications_id'];
                    }
                    $specifications_name_arr[] = $val['specifications_name'];
                }
            }
            if($_POST['bind_trade']==0){
                $_POST['is_appoint_bind'] = 0;
                $_POST['appoint_id'] = 0;
                $_POST['trade_type'] = '';
                //$_POST['trade_info'] = '';
            }

            
            $leveloff = isset($_POST['leveloff']) ? $_POST['leveloff'] : false;
            isset($_POST['tagname']) && $_POST['tagname'] = trim($_POST['tagname']);
            $packageid = isset($_POST['packageid']) ? intval($_POST['packageid']) : 0;
             if(($packageid>0) && empty($_POST['tagname'])){
                 $this->error($this->config['group_alias_name'] .L_('套餐标签必须要写上！'));
             }
            unset($_POST['leveloff']);
			//处理图片 文件名统一
			$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			
			/*foreach($_POST['pic'] as $kp => $vp){
				$tmp_vp = explode(',',$vp);
				$_POST['pic'][$kp]=$rand_num.','.$tmp_vp[1];
			}*/
            foreach ($_POST['pic'] as $k=>$v){
                if(false !== strpos($v,',')){
                    $splistArr = explode(',',$v);
                    $_POST['pic'][$k] = '/upload/group/' . $splistArr[0] . '/' . $splistArr['1'];
                }
            }
            $_POST['pic'] = implode(';', $_POST['pic']);
			
            $_POST['content'] = fulltext_filter($_POST['content']);
//            $_POST['discount'] = $_POST['price'] / $_POST['old_price'] * 10;

            $_POST['sort'] = intval($_POST['sort']);
            $_POST['index_sort'] = intval($_POST['index_sort']);

            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['begin_time'] = strtotime($_POST['begin_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['deadline_time'] = strtotime($_POST['deadline_time']);
            // 处理时间 - 结束时间必须大于开始时间  团购券有效时间必须大于等于结束时间
            if (intval($_POST['begin_time']) > intval($_POST['end_time'])) {
                $this->error(L_('结束时间必须大于开始时间！'));
            }
            if (intval($_POST['end_time']) > intval($_POST['deadline_time'])) {
                $this->error($this->config['group_alias_name'] . L_('券有效期必须不小于结束时间！'));
            }

            if ($_POST['cue_field']) {
                $cue_field = array();
                foreach ($_POST['cue_field']['value'] as $key => $value) {
                    array_push($cue_field, array('key' => $_POST['cue_field']['key'][$key], 'value' => $value));
                }
                $_POST['cue'] = serialize($cue_field);
            } else {
                // 如果没有传值，默认对应清空
                $_POST['cue'] = '';
            }

            // 团购二级分类多选关联
            if ($cat_ids && $this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                $_POST['custom_0'] = $_POST['custom_1'] = $_POST['custom_2'] = $_POST['custom_3'] = $_POST['custom_4'] = '';
                if (isset($_POST['custom']) && !empty($_POST['custom'])) {
                    foreach ($_POST['custom'] as $key => $value) {
                        if (is_array($value)) {
                            $_POST[$key] = implode(',', $value);
                        } else {
                            $_POST[$key] = $value;
                        }
                    }
                }
            } elseif (!isset($_POST['noedittype']) && isset($_POST['cat_fid']) && isset($_POST['cat_id'])) {
                $_POST['custom_0'] = $_POST['custom_1'] = $_POST['custom_2'] = $_POST['custom_3'] = $_POST['custom_4'] = '';

                if (isset($_POST['custom']) && !empty($_POST['custom'])) {
                    foreach ($_POST['custom'] as $key => $value) {
                        if (is_array($value)) {
                            $_POST[$key] = implode(',', $value);
                        } else {
                            $_POST[$key] = $value;
                        }
                    }
                }
            }
            //店铺信息
            $database_merchant_store = D('Merchant_store');
            foreach ($_POST['store'] as $key => $value) {
                $condition_merchant_store['store_id'] = $value;
                $tmp_group_store = $database_merchant_store->field('`store_id`,`province_id`,`city_id`,`area_id`,`circle_id`')->where($condition_merchant_store)->find();
                if (!empty($tmp_group_store)) {
                    $data_group_store_arr[] = $tmp_group_store;
                }
            }
            if (empty($data_group_store_arr)) {
                $this->error(L_('您选择的店铺信息不正确！请重试。'));
            } else if ($_POST['tuan_type'] == 2) {
                $_POST['prefix_title'] = L_('购物');
            } else if (count($data_group_store_arr) == 1) {
                $circle_info = D('Area')->get_area_by_areaId($data_group_store_arr[0]['circle_id']);
                if (empty($circle_info)) {
                    $this->error(L_('您选择的店铺区域商圈信息不正确！请修改店铺资料后重试。'));
                }
                $_POST['prefix_title'] = $circle_info['area_name'];
            } else {
                $_POST['prefix_title'] = count($data_group_store_arr) . L_('店通用');
            }

            $group_id = $now_group['group_id'];
            $condition_save_group['group_id'] = $group_id;
            $newleveloff = array();
            if (!empty($leveloff)) {
                foreach ($leveloff as $kk => $vv) {
                    $vv['type'] = intval($vv['type']);
                    $vv['vv'] = intval($vv['vv']);
                    if (($vv['type'] > 0) && ($vv['vv'] > 0)) {
                        $vv['level'] = $kk;
                        $newleveloff[$kk] = $vv;
                    }
                }
            }

            $_POST['leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';
            if ($leveloff === false)
                unset($_POST['leveloff']);



            if($this->config['group_verify'] == 1 && $_POST['status'] == '1'){
				$_POST['status'] = $this->merchant_session['issign'] ? 1 : 2;
            }
            switch($_POST['bind_trade']){
                case 0:
                    unset($_POST['appoint_id']);
                    $_POST['trade_type']=0;
                    $_POST['trade_info']='';
                    break;
                case 1:
                    if(empty($_POST['appoint_id'])){
                        $this->error(L_('绑定预约不能为空！'));
                    }
                    $_POST['is_appoint_bind'] = 1;
                    $_POST['trade_type'] = 'appoint';
                    $_POST['trade_info'] = $_POST['appoint_id'];

                    break;
                case 2:
                    if(empty($_POST['trade_hotel_cat_id'])){
                        $this->error(L_('请选择酒店分类'));
                    }
                    if($_POST['stock_reduce_method']==1){
                        $this->error(L_('酒店库存仅支持支付完成后减库存'));
                    }
                    if($_POST['count_num']>0 ){
                        $_POST['count_num'] = 0;
                    }
                    $_POST['pin_num'] = 0;
                    $_POST['pin_effective_time'] = 0;
                    $_POST['trade_type'] = 'hotel';
                    $_POST['trade_info'] = implode(',',$_POST['trade_hotel_cat_id']);
                    $_POST['is_appoint_bind'] = 0;
                    $_POST['appoint_id'] = 0;
                    break;
            }
            if (isset($_POST['pin_num']) && $_POST['pin_num']==1) {
                $this->error(L_('拼团数量最少设置为2'));
            }
            // 处理一下 在团购券有效期前时间可以取消订单
            if ($_POST['no_refund'] && $_POST['no_refund'] == 1) {
                $_POST['before_effective_time'] = 0;
            }
            // 如果有团购地址定位 记录改地址
            if ($_POST['long_lat']) {
                $long_lat = explode(',',$_POST['long_lat']);
                $_POST['long'] = $long_lat[0];
                $_POST['lat'] = $long_lat[1];
                $now_city = D('Area')->cityMatching($_POST['lat'],$_POST['long']);
                if ($_POST['province_id']<=0) {
                    $_POST['province_id'] = $now_city['area_info']['province_id'];
                }
                if ($_POST['city_id']<=0) {
                    $_POST['city_id'] = $now_city['area_info']['city_id'];
                }
                if ($_POST['area_id']<=0) {
                    $_POST['area_id'] = $now_city['area_info']['area_id'];
                }
            }

            if ($database_group->where($condition_save_group)->data($_POST)->save()) {
				
				import('ORG.Util.Dir');
				Dir::delDirnotself(WEB_PATH.'/runtime/wxapp_group/');
				Dir::delDirnotself(WEB_PATH.'/upload/wxapp_group/');
				
                $database_group_store = D('Group_store');
                $condition_group_store['group_id'] = $group_id;
                $database_group_store->where($condition_group_store)->delete();
                foreach ($data_group_store_arr as $key => $value) {
                    $data_group_store = $value;
                    $data_group_store['group_id'] = $group_id;
                    $data_group_store['package_id'] = isset($_POST['package_id'][$value['store_id']]) ? $_POST['package_id'][$value['store_id']] : 0;
                    $database_group_store->data($data_group_store)->add();
                }

                //判断关键词
                $database_keywords = D('Keywords');
                $condition_keywords['third_id'] = $group_id;
                $condition_keywords['third_type'] = 'group';
                $database_keywords->where($condition_keywords)->delete();

                if (!empty($key_arr)) {
                    $data_keywords['third_id'] = $group_id;
                    $data_keywords['third_type'] = 'group';
                    foreach ($key_arr as $value) {
                        $data_keywords['keyword'] = $value;
                        $database_keywords->data($data_keywords)->add();
                    }
                }
                //添加或删除到套餐
                $mpackageDb = M('Group_packages');
                if ($now_group['packageid'] > 0) {
                    $mpackage = $mpackageDb->where(array('id' => $now_group['packageid'], 'mer_id' => $now_group['mer_id']))->find();
                    if (!empty($mpackage)) { /*                     * **删除原有的**** */
                        $mpackage['groupidtext'] = !empty($mpackage['groupidtext']) ? unserialize($mpackage['groupidtext']) : array();
                        unset($mpackage['groupidtext'][$group_id]);
                        $mpackage['groupidtext'] = !empty($mpackage['groupidtext']) ? serialize($mpackage['groupidtext']) : '';
                        $mpackageDb->where(array('id' => $mpackage['id']))->save(array('groupidtext' => $mpackage['groupidtext']));
                    }
                }
                if ($packageid > 0) { /*                 * ****现在编辑处理**** */
                    $mpackage2 = $mpackageDb->where(array('id' => $packageid, 'mer_id' => $now_group['mer_id']))->find();
                    if (!empty($mpackage2)) {
                        $mpackage2['groupidtext'] = !empty($mpackage2['groupidtext']) ? unserialize($mpackage2['groupidtext']) : array();
                        $mpackage2['groupidtext'][$group_id] = $_POST['tagname'];
                        $mpackageDb->where(array('id' => $mpackage2['id']))->save(array('groupidtext' => serialize($mpackage2['groupidtext'])));
                    }
                }
                // 上传了规格信息 就对应添加或修改规格信息
                if ($_POST['specifications']) {
                    $m_group_specifications = M('Group_specifications');
                    if ($specifications_id_arr) {
                        $where = array(
                            'specifications_id' => array('not in', $specifications_id_arr),
                            'group_id' => $group_id ,
                            'status' => 1
                        );
                        $m_group_specifications->where($where)->data(array('status' => 2))->save();
                    }
                    $time = time();
                    foreach ($_POST['specifications'] as $val) {
                        if ($val['specifications_id']) {
                            $specifications_save = array(
                                'specifications_name' => $val['specifications_name'],
                                'price' => $val['price'],
                                'old_price' => $val['old_price'] ? $val['old_price'] : 0,
                                'count_num' => $val['count_num'] ? $val['count_num'] : 0,
                                'once_max' => $val['once_max'] ? $val['once_max'] : 0,
                                'once_max_day' => $val['once_max_day'] ? $val['once_max_day'] : 0,
                                'once_min' => $val['once_min'] ? $val['once_min'] : 0,
                                'last_time' => $time,
                                'status' => 1,
                            );
                            $m_group_specifications->where(array('specifications_id' => $val['specifications_id']))->data($specifications_save)->save();
                        } else {
                            $specifications_arr = array(
                                'group_id' => $group_id,
                                'specifications_name' => $val['specifications_name'],
                                'price' => $val['price'],
                                'old_price' => $val['old_price'] ? $val['old_price'] : 0,
                                'count_num' => $val['count_num'] ? $val['count_num'] : 0,
                                'once_max' => $val['once_max'] ? $val['once_max'] : 0,
                                'once_max_day' => $val['once_max_day'] ? $val['once_max_day'] : 0,
                                'once_min' => $val['once_min'] ? $val['once_min'] : 0,
                                'add_time' => $time,
                                'status' => 1,
                            );
                            $m_group_specifications->data($specifications_arr)->add();
                        }
                    }
                } else {
                    // 没传 且开启多规格情况下 默认删除所有
                    $m_group_specifications = M('Group_specifications');
                    $where = array(
                        'group_id' => $group_id ,
                        'status' => 1
                    );
                    $m_group_specifications->where($where)->data(array('status' => 2))->save();
                }
                // 团购二级分类多选关联
                if ($cat_ids && $this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                    $database_group_bind_category = M('Group_bind_category');
                    $condition_group_bind_category['group_id'] = $group_id;
                    $database_group_bind_category->where($condition_group_bind_category)->delete();
                    $time = time();
                    $data_group_bind_category = array();
                    foreach($cat_ids as $key => $value){
                        $data_group_bind_category[$key]['cid'] = $value['cat_id'];
                        $data_group_bind_category[$key]['cat_fid'] = $value['cat_fid'];
                        $data_group_bind_category[$key]['group_id'] = $group_id;
                        $data_group_bind_category[$key]['add_time'] = $time;
                    }
                    if ($data_group_bind_category) {
                        $database_group_bind_category->addAll($data_group_bind_category);
                    }
                }
                // 团购多详情显示
                if ($_POST['group_content']) {
                    $m_group_content = M('Group_content');
                    $condition_group_content['group_id'] = $group_id;
                    $m_group_content->where($condition_group_content)->delete();
                    $group_content_arr = $_POST['group_content'];
                    $time_info = time();
                    $data = array();
                    $data['add_time'] = $time_info;
                    $data['group_id'] = $group_id;
                    $add_all = array();
                    foreach ($group_content_arr as $val) {
                        $data['name'] = $val['name'];
                        $data['content'] = fulltext_filter($val['content']);
                        $add_all[] = $data;
                    }
                    if ($add_all) {
                        $m_group_content->addAll($add_all);
                    }
                }
                if(!empty($_POST['cat_fid'])){
                    $get_grouplist_catfid = 0;
                    $get_grouplist_catid = $_POST['cat_id'];
                }else{
                    $get_grouplist_catfid = $_POST['cat_id'];
                    $get_grouplist_catid = 0;
                }
                $group_store_info = M('Group_store')->where(array('group_id' => $group_id))->select();
                if ($group_store_info) {
                    foreach ($group_store_info as $val) {
                        S('wap_store_group_'.($get_grouplist_catfid ? $get_grouplist_catfid : $get_grouplist_catid).'_'.$val['store_id'], array());
                        S('wap_store_group_0_'.$val['store_id'], array());
                    }
                    S('wap_group_store_'.$group_id,array());
                }
				
				//清空团购缓存
				$cache_key = 'group_stock_' . $group_id;
				cache_sql($cache_key, null);
				
                $this->success(L_('编辑成功！'),U('Group/index'));
            } else {
                $this->error(L_('编辑失败！请重试。'));
            }
        } else {
            //图片
            $group_image_class = new group_image();
            $tmp_pic_arr = explode(';', $now_group['pic']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_group['pic_arr'][$key]['title'] = $value;
                $now_group['pic_arr'][$key]['url'] = $group_image_class->get_image_by_path($value, 's');
            }
            if ($now_group['cue']) {
                $now_group['cue_arr'] = unserialize($now_group['cue']);
            }
            /*处理图片上传阿里云导致详情中的图片不展示*/
            $now_group['content'] = replace_file_domain_content($now_group['content']);
            $this->assign('now_group', $now_group);

            //关键词
            $database_keywords = D('Keywords');
            $conditon_keywords['third_id'] = $now_group['group_id'];
            $conditon_keywords['third_type'] = 'group';
            $keywords_list = $database_keywords->field('`keyword`')->where($conditon_keywords)->order('`id` ASC')->select();
            if (!empty($keywords_list)) {
                $keywords_arr = array();
                foreach ($keywords_list as $value) {
                    $keywords_arr[] = $value['keyword'];
                }
                $keywords = implode(' ', $keywords_arr);
                $this->assign('keywords', $keywords);
            }

            //所属店铺
            $database_group_store = D('Group_store');
            $condition_group_store['group_id'] = $now_group['group_id'];
            $store_list = $database_group_store->field(true)->where($condition_group_store)->select();
            $store_arr = array();
            $package_id_arr = array();
            foreach ($store_list as $value) {
                $store_arr[] = $value['store_id'];
                $package_id_arr[$value['store_id']] = $value['package_id'];
            }
            $this->assign('store_arr', $store_arr);


            //分类
            $database_group_category = D('Group_category');
            $condition_f_group_category['cat_fid'] = 0;
            $condition_f_group_category['cat_status'] = 1;
            $f_category_list = $database_group_category->field('`cat_id`,`cat_name`,`cat_field`,`cue_field`,`editor_num`,`editor_title`')->where($condition_f_group_category)->order('`cat_sort` DESC,`cat_id` ASC')->select();
            if (empty($f_category_list)) {
                $this->error(L_('管理员没有添加X1分类！',$this->config['group_alias_name']));
            }
            $this->assign('f_category_list', $f_category_list);

            $condition_s_group_category['cat_fid'] = $now_group['cat_fid'];
            $condition_s_group_category['cat_status'] = 1;
            $s_category_list = $database_group_category->field('`cat_id`,`cat_name`')->where($condition_s_group_category)->order('`cat_sort` DESC,`cat_id` ASC')->select();

            if (empty($s_category_list)) {
                $fid_cat_name = $database_group_category->where(array('cat_id' => $now_group['cat_fid']))->getField('cat_name');
                $this->error($fid_cat_name . ' 分类下没有添加子分类！');
            }
            $this->assign('s_category_list', $s_category_list);

            foreach ($f_category_list as $value) {
                if ($value['cat_id'] == $now_group['cat_fid']) {
                    $now_f_category = $value;
                    break;
                }
            }

            if (!empty($now_f_category['cat_field'])) {
                $cat_field = unserialize($now_f_category['cat_field']);
                $custom_html = '';
                foreach ($cat_field as $key => $value) {
                    if (empty($value['use_field'])) {
                        $custom_html .= '<div class="form-group"><label class="col-sm-1">' . $value['name'] . '：</label>';
                        if ($value['type'] == 0) {
                            $custom_html .= '<select name="custom[custom_' . $key . ']" class="col-sm-1">';
                            foreach ($value['value'] as $k => $v) {
                                $custom_html .= '<option value="' . $k . '"';
                                if ($now_group['custom_' . $key] == $k) {
                                    $custom_html .=' selected=selected';
                                }
                                $custom_html .= ' >' . $v . '</option>';
                            }
                            $custom_html .= '</select>';
                        } else {
                            $checkVarr = explode(',', $now_group['custom_' . $key]);
                            $checkVarr = !empty($checkVarr) ? $checkVarr : array();
                            foreach ($value['value'] as $k => $v) {
                                $custom_html .= '<label style="margin-right:30px;"><input class="ace" type="checkbox" name="custom[custom_' . $key . '][]" value="' . $k . '" id="custom_' . $key . '_' . $k . '"';
                                if (in_array($k, $checkVarr)) {
                                    $custom_html .=' checked=checked';
                                }
                                $custom_html .= ' /><span class="lbl"><label for="custom_' . $key . '_' . $k . '">&nbsp;' . $v . '</label></span></label>';
                            }
                        }
                        $custom_html .= '</div>';
                    }
                }
            }
            $this->assign('custom_html', $custom_html);

            if (!empty($now_f_category['cue_field'])) {
                $cue_field = unserialize($now_f_category['cue_field']);
                $cue_html = '';
                foreach ($cue_field as $key => $value) {
                    $cue_html .= '<div class="form-group"><label class="col-sm-1">' . $value['name'] . '：</label>';
                    if ($value['type'] == 0) {
                        $cue_html .= '<input type="hidden" name="cue_field[key][]" value="' . $value['name'] . '"/><input type="text" class="col-sm-4" name="cue_field[value][]" value="' . (!empty($now_group['cue_arr'][$key]['value']) ? $now_group['cue_arr'][$key]['value'] : '') . '"/>';
                    } else {
                        $cue_html .= '<input type="hidden" name="cue_field[key][]" value="' . $value['name'] . '"/><textarea class="col-sm-4" rows="5" name="cue_field[value][]">' . (!empty($now_group['cue_arr'][$key]['value']) ? $now_group['cue_arr'][$key]['value'] : '') . '</textarea>';
                    }
                    $cue_html .= '</div>';
                }
            }
            $this->assign('cue_html', $cue_html);

            $mer_id = $this->merchant_session['mer_id'];
            $db_arr = array(C('DB_PREFIX') . 'area' => 'a', C('DB_PREFIX') . 'merchant_store' => 's');
            $store_list = D()->table($db_arr)->field('a.`area_name`,s.`adress`,`s`.`name`,`s`.`store_id`')->where("`s`.`mer_id`='$mer_id' AND `s`.`status`='1' AND `s`.`have_group`='1' AND `s`.`area_id`=`a`.`area_id`")->order('`sort` DESC,`store_id` ASC')->select();
            if (empty($store_list)) {
                $this->error(L_('您暂时还没有能添加') . $this->config['group_alias_name'] . L_('信息的店铺！'));
            }
            $this->assign('store_list', $store_list);
            $leveloff = !empty($now_group['leveloff']) ? unserialize($now_group['leveloff']) : false;

            $levelDb = M('User_level');
            $tmparr = M(_view('User_level'))->order('id ASC')->select();
            $levelarr = array();
            if ($tmparr && $this->config['level_onoff']) {
                foreach ($tmparr as $vv) {
                    if (!empty($leveloff) && isset($leveloff[$vv['level']])) {
                        $vv['vv'] = $leveloff[$vv['level']]['vv'];
                        $vv['type'] = $leveloff[$vv['level']]['type'];
                    } else {
                        $vv['vv'] = '';
                        $vv['type'] = '';
                    }
                    $levelarr[$vv['level']] = $vv;
                }
            }
            unset($tmparr);
            $this->assign('levelarr', $levelarr);
            $mpackageDb = M('Group_packages');
            $mpackagelist = $mpackageDb->field(true)->where(array('mer_id' => $now_group['mer_id']))->order('id DESC')->select();
            $this->assign('mpackagelist', $mpackagelist);
            //餐饮套餐
            $db_arr = array(C('DB_PREFIX') . 'foodshop_goods_package' => 'p', C('DB_PREFIX') . 'foodshop_goods_package_detail' => 'd');
            $package_list = D()->table($db_arr)->field('p.`id`,p.store_id,p.`name`')->where("`p`.`id`= d.pid AND `p`.`status`='1' AND `p`.`is_order`='1' AND `d`.`goods_detail` <> ''")->order('p.`id` DESC')->select();
            $package_list = array_column($package_list, NUll, 'id');
            foreach ($package_list as &$package)
            {
                if (isset($package_id_arr[$package['store_id']]) && $package_id_arr[$package['store_id']] == $package['id']) {
                    $package['is_choose'] = 1;
                } else {
                    $package['is_choose'] = 2;
                }
            }
            $this->assign('package_list', $package_list);
            
            if(isset($this->config['appoint_category_bgimg'])){
				$database_appoint = D('Appoint');
				$appoint_list = $database_appoint->get_appointmerchantlist_by_MerchantId($mer_id);
				$this->assign('appoint_list',$appoint_list);
			}
            ///if($now_group['trade_type']=='hotel') {
                $database_trade_hotel_category = D('Trade_hotel_category');
                $trade_hotel_category_list = $database_trade_hotel_category->where(array('mer_id' => $mer_id, 'is_remove' => '0', 'cat_fid' => '0'))->order('`cat_sort` DESC')->select();
                $this->assign('trade_hotel_category_list', $trade_hotel_category_list);
           // }

            $where_express = array('mer_id' => $this->merchant_session['mer_id']);

            $express_list = M('Express_template')->where($where_express)->select();

            $this->assign('express_list', $express_list);

            // 查询规格参数
            $group_specifications_info = M('Group_specifications')->where(array('group_id' => $now_group['group_id'], 'status' => 1))->select();
            if (!$group_specifications_info) {
                $group_specifications_info = array();
            }
            $group_specifications_count = count($group_specifications_info);
            $this->assign('group_specifications_info', $group_specifications_info);
            $this->assign('group_specifications_count', $group_specifications_count);

            // 团购二级规格多选关联
            if ($this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                $database_group_bind_category = M('Group_bind_category');
                $condition_group_bind_category['group_id'] = $now_group['group_id'];
                $cid_info = $database_group_bind_category->where($condition_group_bind_category)->getField('bind_id, cid');
                $this->assign('cid_info', $cid_info);
            }
            // 获取商品 各个详情
            $m_group_content = M('Group_content');
            $condition_group_content['group_id'] = $now_group['group_id'];
            $group_content = $m_group_content->order('content_id ASC')->where($condition_group_content)->select();
            if (!$group_content) {
                $group_content = array();
                $arr = array(
                    'name' => L_('商品详情'),
                    'content' => $now_group['content']
                );
                $group_content[] = $arr;
            }
            $editor_title = array();
            if ($now_f_category['editor_title']) {
                $editor_title_info = $now_f_category['editor_title'] = unserialize($now_f_category['editor_title']);
            }
            if (empty($editor_title_info)) {
                $editor_title[] = L_('商品详情');
            } else {
                foreach ($editor_title_info as $val) {
                    if ($val) {
                        $editor_title[] = $val;
                    }
                }
            }
            foreach ($editor_title as $key=>&$val) {
                if (!$group_content[$key] || $group_content[$key] != $val) {
                    $group_content[$key]['name'] = $val;
                    if (!$group_content[$key]['content']) {
                        $group_content[$key]['content'] = '';
                    }
                }
            }

            if ($group_content) {
                foreach ($group_content as &$item) {
                    $item['content'] = replace_file_domain_content($item['content']);
                }
            }
            $this->assign('group_content', $group_content);
            $this->assign('group_content_count', count($group_content));

            $this->display();
        }
    }

    public function frame_edit() {
        if (empty($_SESSION['system'])) {
            $this->error(L_('非法修改'));
        }

        $database_group = D('Group');
        $condition_group['group_id'] = $_GET['group_id'];
        $now_group = $database_group->field(true)->where($condition_group)->find();
        if($now_group['trade_info']!=0){
            $now_group['trade_info'] = explode(',',$now_group['trade_info']);
        }

        if (empty($now_group)) {
            $this->error(L_('该X1不存在！',$this->config['group_alias_name']));
        }
        if (IS_POST) {
            if (empty($_POST['name'])) {
                $this->error(L_('请填写商品标题'));
            }
            if (empty($_POST['s_name'])) {
                $this->error(L_('请填写商品名称'));
            }
            if (empty($_POST['intro'])) {
                $this->error(L_('请填写商品简介'));
            }
            if($now_group['tuan_type']==2 && $_POST['open_express']==0 && $_POST['pick_in_store']==0){
                $this->error(L_('请选择一种配送方式'));
            }
            //判断关键词
            $keywords = trim($_POST['keywords']);
            if (!empty($keywords)) {
                $tmp_key_arr = explode(' ', $keywords);
                $key_arr = array();
                foreach ($tmp_key_arr as $value) {
                    if (!empty($value)) {
                        array_push($key_arr, $value);
                    }
                }
                if (count($key_arr) > 5) {
                    $this->error(L_('关键词最多5个。'));
                }
            }

            if ($_POST['old_price']<0 &&$this->config['open_extra_price']==0) {
                $this->error(L_('请填写商品原价'));
            }
            if ($_POST['price']<0&&$this->config['open_extra_price']==0) {
                $this->error(L_('请填写商品X1价',$this->config['group_alias_name']));
            }
			if($_POST['price'] - $_POST['wx_cheap'] < 0){
				$this->error(L_('商品价格减去微信优惠不能小于0'));
			}
            if (empty($_POST['store'])) {
                $this->error(L_('请至少选择一家店铺'));
            }
            if (!$_POST['cat_id'] && $this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                if(empty($_POST['group_category'])){
                    $this->error(L_('请至少选一个分类！'));
                }
                $cat_ids = array();
                foreach ($_POST['group_category'] as $cat_a) {
                    $a = explode('-', $cat_a);
                    $cat_ids[] = array('cat_fid' => $a[0], 'cat_id' => $a[1]);
                }
                // 兼容app 和 pc 顾将多选的分类id第一个赋给原来的分类id
                if (reset($cat_ids)) {
                    $_POST['cat_id'] = reset($cat_ids)['cat_id'];
                }
            }
            $group_content = reset($_POST['group_content']);
            if (empty($_POST['content']) && empty($group_content)) {
                $this->error(L_('请填写本单详情'));
            }
            if ($_POST['group_content'] && count($_POST['group_content']) > 1) {
                foreach ($_POST['group_content'] as $val) {
                    if (!$val['name']) {
                        $this->error(L_('请填写详情名称'));
                    } elseif (!$val['content']) {
                        $this->error(L_('请填写【X1】详情内容',$val['name']));
                    }
                }
            }
            if (empty($_POST['content']) && $group_content) {
                $_POST['content'] = $group_content['content'];
            }
            if (empty($_POST['pic'])) {
                $this->error(L_('请至少上传一张照片'));
            }
            if (empty($_POST['success_num'])) {
                $this->error(L_('成功X1人数要求至少为1人',$this->config['group_alias_name']));
            }
            //商品总数量
            if ($_POST['count_num'] > 0 && $_POST['count_num'] < $_POST['once_min']) {
            	$this->error(L_('商品总数量不能小于一次最少购买数量'));
            }
            
            if ($_POST['once_max'] > 0 && $_POST['once_min'] > $_POST['once_max']) {
            	$this->error('ID最多购买数量不能小于一次最少购买数量');
            }
			
			if($_POST['is_appoint_bind'] && ($_POST['is_appoint_bind'] == 1)){
                if(!$_POST['appoint_id']){
                    $this->error(L_('绑定预约不能为空！'));
                }
            }
            // 上传了规格信息 就对应判断规格信息
            $specifications_id_arr = array();
            if ($_POST['specifications']) {
                if (count($_POST['specifications']) > 6) {
                    $this->error(L_('对应团购规格最多添加6个！'));
                }
                $specifications_name_arr = array();
                foreach ($_POST['specifications'] as $val) {
                    // 判断 规格名必须存在
                    if (!$val['specifications_name']) {
                        $this->error(L_('请填写对应规格名称！'));
                    } elseif (in_array($val['specifications_name'], $specifications_name_arr)) {
                        $this->error(L_('【').$val['specifications_name'].L_('】规格名称重复！'));
                    } elseif (!$val['price']) {
                        // 判断价格必须存在
                        $this->error(L_('请填写【X1】规格价格！',$val['specifications_name']));
                    } elseif ($val['count_num'] > 0 && $val['count_num'] < $val['once_min']) {
                        // 判断商品总数量不能小于一次最少购买数量
                        $this->error(L_('商品总数量不能小于同一ID一次最少购买数量'));
                    } elseif ($val['once_max'] > 0 && $val['once_max'] < $val['once_min']) {
                        // 判断同一ID最多购买数量不能小于一次最少购买数量
                        $this->error(L_('同一ID最多购买数量不能小于一次最少购买数量'));
                    } elseif ($val['once_max_day'] > 0 && $val['once_max_day'] < $val['once_min']) {
                        // 判断ID最多购买数量不能小于一次最少购买数量
                        $this->error(L_('同一ID每日最多购买数量不能小于一次最少购买数量'));
                    } elseif ($val['once_min'] <= 0) {
                        // 一次最少购买数量不得小于0
                        $this->error(L_('一次最少购买数量不得小于0'));
                    } elseif ($val['specifications_id']) {
                        $specifications_id_arr[] = $val['specifications_id'];
                    }
                    $specifications_name_arr[] = $val['specifications_name'];
                }
            }
			
            $leveloff = isset($_POST['leveloff']) ? $_POST['leveloff'] : false;
            isset($_POST['tagname']) && $_POST['tagname'] = trim($_POST['tagname']);
            $packageid = isset($_POST['packageid']) ? intval($_POST['packageid']) : 0;
			 if(($packageid>0) && empty($_POST['tagname'])){
			     $this->error($this->config['group_alias_name'] .L_('套餐标签必须要写上！'));
			 }
            unset($_POST['leveloff']);
            $_POST['pic'] = implode(';', $_POST['pic']);

            $_POST['content'] = fulltext_filter($_POST['content']);
            if(!$_POST['old_price']){
                $_POST['old_price'] = $_POST['price'];
            }
            $_POST['discount'] = getFormatNumber($_POST['price'] / $_POST['old_price'] * 10) ;

            $_POST['sort'] = intval($_POST['sort']);
            $_POST['index_sort'] = intval($_POST['index_sort']);
            $_POST['group_max_score_use'] = floatval($_POST['group_max_score_use']);
            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['begin_time'] = strtotime($_POST['begin_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['deadline_time'] = strtotime($_POST['deadline_time']);
            // 处理时间 - 结束时间必须大于开始时间  团购券有效时间必须大于等于结束时间
            if (intval($_POST['begin_time']) > intval($_POST['end_time'])) {
                $this->error(L_('结束时间必须大于开始时间！'));
            }
            if (intval($_POST['end_time']) > intval($_POST['deadline_time']) && !empty($_POST['deadline_time'])) {
                $this->error($this->config['group_alias_name'] . L_('券有效期必须不小于结束时间！'));
            }

            if ($_POST['cue_field']) {
                $cue_field = array();
                foreach ($_POST['cue_field']['value'] as $key => $value) {
                    array_push($cue_field, array('key' => $_POST['cue_field']['key'][$key], 'value' => $value));
                }
                $_POST['cue'] = serialize($cue_field);
            } else {
                // 如果没有传值，默认对应清空
                $_POST['cue'] = '';
            }
            // 团购二级分类多选关联
            if ($cat_ids && $this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                $_POST['custom_0'] = $_POST['custom_1'] = $_POST['custom_2'] = $_POST['custom_3'] = $_POST['custom_4'] = '';
                if (isset($_POST['custom']) && !empty($_POST['custom'])) {
                    foreach ($_POST['custom'] as $key => $value) {
                        if (is_array($value)) {
                            $_POST[$key] = implode(',', $value);
                        } else {
                            $_POST[$key] = $value;
                        }
                    }
                }
            } elseif (!isset($_POST['noedittype']) && isset($_POST['cat_fid']) && isset($_POST['cat_id'])) {
                $_POST['custom_0'] = $_POST['custom_1'] = $_POST['custom_2'] = $_POST['custom_3'] = $_POST['custom_4'] = '';

                if (isset($_POST['custom']) && !empty($_POST['custom'])) {
                    foreach ($_POST['custom'] as $key => $value) {
                        if (is_array($value)) {
                            $_POST[$key] = implode(',', $value);
                        } else {
                            $_POST[$key] = $value;
                        }
                    }
                }
            }


            //店铺信息
            $database_merchant_store = D('Merchant_store');
            foreach ($_POST['store'] as $key => $value) {
                $condition_merchant_store['store_id'] = $value;
                $tmp_group_store = $database_merchant_store->field('`store_id`,`province_id`,`city_id`,`area_id`,`circle_id`')->where($condition_merchant_store)->find();
                if (!empty($tmp_group_store)) {
                    $data_group_store_arr[] = $tmp_group_store;
                }
            }
            if (empty($data_group_store_arr)) {
                $this->error(L_('您选择的店铺信息不正确！请重试。'));
            } else if ($_POST['tuan_type'] == 2) {
                $_POST['prefix_title'] = L_('购物');
            } else if (count($data_group_store_arr) == 1) {
                $circle_info = D('Area')->get_area_by_areaId($data_group_store_arr[0]['circle_id']);
                if (empty($circle_info)) {
                    $this->error(L_('您选择的店铺区域商圈信息不正确！请修改店铺资料后重试。'));
                }
                $_POST['prefix_title'] = $circle_info['area_name'];
            } else {
                $_POST['prefix_title'] = count($data_group_store_arr) . L_('店通用');
            }

            $group_id = $now_group['group_id'];
            $condition_save_group['group_id'] = $group_id;
            $newleveloff = array();
            if (!empty($leveloff)) {
                foreach ($leveloff as $kk => $vv) {
                    $vv['type'] = intval($vv['type']);
                    $vv['vv'] = intval($vv['vv']);
                    if (($vv['type'] > 0) && ($vv['vv'] > 0)) {
                        $vv['level'] = $kk;
                        $newleveloff[$kk] = $vv;
                    }
                }
            }

            $_POST['leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';
            if ($leveloff === false)
                unset($_POST['leveloff']);


            switch($_POST['bind_trade']){
                case 0:
                    $_POST['trade_type']=0;
                    $_POST['trade_info']='';
                    $_POST['appoint_id'] = 0;
                    $_POST['is_appoint_bind'] = 0;
                    break;
                case 1:
                    if(empty($_POST['appoint_id'])){
                        $this->error(L_('绑定预约不能为空！'));
                    }
                    $_POST['is_appoint_bind'] = 1;
                    $_POST['trade_type'] = 'appoint';
                    $_POST['trade_info'] = $_POST['appoint_id'];

                    break;
                case 2:
                    if(empty($_POST['trade_hotel_cat_id'])){
                        $this->error(L_('请选择酒店分类'));
                    }
                    $_POST['pin_num'] = 0;
                    $_POST['pin_effective_time'] = 0;
                    $_POST['trade_type'] = 'hotel';
                    $_POST['trade_info'] = implode(',',$_POST['trade_hotel_cat_id']);
                    $_POST['is_appoint_bind'] = 0;
                    $_POST['appoint_id'] = 0;
                    break;
            }
            if (isset($_POST['pin_num']) && $_POST['pin_num']==1) {
                $this->error(L_('拼团数量最少设置为2'));
            }
            // 如果有团购地址定位 记录改地址
            if ($_POST['long_lat']) {
                $long_lat = explode(',',$_POST['long_lat']);
                $_POST['long'] = $long_lat[0];
                $_POST['lat'] = $long_lat[1];
                $now_city = D('Area')->cityMatching($_POST['lat'],$_POST['long']);
                if ($_POST['province_id']<=0) {
                    $_POST['province_id'] = $now_city['area_info']['province_id'];
                }
                if ($_POST['city_id']<=0) {
                    $_POST['city_id'] = $now_city['area_info']['city_id'];
                }
                if ($_POST['area_id']<=0) {
                    $_POST['area_id'] = $now_city['area_info']['area_id'];
                }
            }

            //团购到期类型
            if($_POST['effective_type'] == 1){
                //到期天数
                $_POST['deadline_time'] = $_POST['deadline_days'];
                unset($_POST['deadline_days']);
            }


            if ($database_group->where($condition_save_group)->data($_POST)->save()) {
				
				import('ORG.Util.Dir');
				Dir::delDirnotself(WEB_PATH.'/runtime/wxapp_group/');
				Dir::delDirnotself(WEB_PATH.'/upload/wxapp_group/');
				
                $database_group_store = D('Group_store');
                $condition_group_store['group_id'] = $group_id;
                $database_group_store->where($condition_group_store)->delete();

                foreach ($data_group_store_arr as $key => $value) {
                    $data_group_store = $value;
                    $data_group_store['group_id'] = $group_id;
                    $database_group_store->data($data_group_store)->add();
                }

                //判断关键词
                $database_keywords = D('Keywords');
                $condition_keywords['third_id'] = $group_id;
                $condition_keywords['third_type'] = 'group';
                $database_keywords->where($condition_keywords)->delete();

                if (!empty($key_arr)) {
                    $data_keywords['third_id'] = $group_id;
                    $data_keywords['third_type'] = 'group';
                    foreach ($key_arr as $value) {
                        $data_keywords['keyword'] = $value;
                        $database_keywords->data($data_keywords)->add();
                    }
                }
                //添加或删除到套餐
                $mpackageDb = M('Group_packages');
                if ($now_group['packageid'] > 0) {
                    $mpackage = $mpackageDb->where(array('id' => $now_group['packageid'], 'mer_id' => $now_group['mer_id']))->find();
                    if (!empty($mpackage)) { /*                     * **删除原有的**** */
                        $mpackage['groupidtext'] = !empty($mpackage['groupidtext']) ? unserialize($mpackage['groupidtext']) : array();
                        unset($mpackage['groupidtext'][$group_id]);
                        $mpackage['groupidtext'] = !empty($mpackage['groupidtext']) ? serialize($mpackage['groupidtext']) : '';
                        $mpackageDb->where(array('id' => $mpackage['id']))->save(array('groupidtext' => $mpackage['groupidtext']));
                    }
                }
                if ($packageid > 0) { /*                 * ****现在编辑处理**** */
                    $mpackage2 = $mpackageDb->where(array('id' => $packageid, 'mer_id' => $now_group['mer_id']))->find();
                    if (!empty($mpackage2)) {
                        $mpackage2['groupidtext'] = !empty($mpackage2['groupidtext']) ? unserialize($mpackage2['groupidtext']) : array();
                        $mpackage2['groupidtext'][$group_id] = $_POST['tagname'];
                        $mpackageDb->where(array('id' => $mpackage2['id']))->save(array('groupidtext' => serialize($mpackage2['groupidtext'])));
                    }
                }
                // 上传了规格信息 就对应添加或修改规格信息
                if ($_POST['specifications']) {
                    $m_group_specifications = M('Group_specifications');
                    if ($specifications_id_arr) {
                        $where = array(
                            'specifications_id' => array('not in', $specifications_id_arr),
                            'group_id' => $group_id ,
                            'status' => 1
                        );
                        $m_group_specifications->where($where)->data(array('status' => 2))->save();
                    }
                    $time = time();
                    foreach ($_POST['specifications'] as $val) {
                        if ($val['specifications_id']) {
                            $specifications_save = array(
                                'specifications_name' => $val['specifications_name'],
                                'price' => $val['price'],
                                'old_price' => $val['old_price'] ? $val['old_price'] : 0,
                                'count_num' => $val['count_num'] ? $val['count_num'] : 0,
                                'once_max' => $val['once_max'] ? $val['once_max'] : 0,
                                'once_max_day' => $val['once_max_day'] ? $val['once_max_day'] : 0,
                                'once_min' => $val['once_min'] ? $val['once_min'] : 0,
                                'last_time' => $time,
                                'status' => 1,
                            );
                            $m_group_specifications->where(array('specifications_id' => $val['specifications_id']))->data($specifications_save)->save();
                        } else {
                            $specifications_arr = array(
                                'group_id' => $group_id,
                                'specifications_name' => $val['specifications_name'],
                                'price' => $val['price'],
                                'old_price' => $val['old_price'] ? $val['old_price'] : 0,
                                'count_num' => $val['count_num'] ? $val['count_num'] : 0,
                                'once_max' => $val['once_max'] ? $val['once_max'] : 0,
                                'once_max_day' => $val['once_max_day'] ? $val['once_max_day'] : 0,
                                'once_min' => $val['once_min'] ? $val['once_min'] : 0,
                                'add_time' => $time,
                                'status' => 1,
                            );
                            $m_group_specifications->data($specifications_arr)->add();
                        }
                    }
                } else {
                    // 没传 默认删除所有
                    $m_group_specifications = M('Group_specifications');
                    $where = array(
                        'group_id' => $group_id ,
                        'status' => 1
                    );
                    $m_group_specifications->where($where)->data(array('status' => 2))->save();
                }
                // 团购二级规格多选关联
                if ($cat_ids && $this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                    $database_group_bind_category = M('Group_bind_category');
                    $condition_group_bind_category['group_id'] = $group_id;
                    $database_group_bind_category->where($condition_group_bind_category)->delete();
                    $time = time();
                    $data_group_bind_category = array();
                    foreach($cat_ids as $key => $value){
                        $data_group_bind_category[$key]['cid'] = $value['cat_id'];
                        $data_group_bind_category[$key]['cat_fid'] = $value['cat_fid'];
                        $data_group_bind_category[$key]['group_id'] = $group_id;
                        $data_group_bind_category[$key]['add_time'] = $time;
                    }
                    if ($data_group_bind_category) {
                        $database_group_bind_category->addAll($data_group_bind_category);
                    }
                }
                // 团购多详情显示
                if ($_POST['group_content']) {
                    $m_group_content = M('Group_content');
                    $condition_group_content['group_id'] = $group_id;
                    $m_group_content->where($condition_group_content)->delete();
                    $group_content_arr = $_POST['group_content'];
                    $time_info = time();
                    $data = array();
                    $data['add_time'] = $time_info;
                    $data['group_id'] = $group_id;
                    $add_all = array();
                    foreach ($group_content_arr as $val) {
                        $data['name'] = $val['name'];
                        $data['content'] = fulltext_filter($val['content']);
                        $add_all[] = $data;
                    }
                    if ($add_all) {
                        $m_group_content->addAll($add_all);
                    }
                }
                if(!empty($_POST['cat_fid'])){
                    $get_grouplist_catfid = 0;
                    $get_grouplist_catid = $_POST['cat_id'];
                }else{
                    $get_grouplist_catfid = $_POST['cat_id'];
                    $get_grouplist_catid = 0;
                }
                $group_store_info = M('Group_store')->where(array('group_id' => $group_id))->select();
                if ($group_store_info) {
                    foreach ($group_store_info as $val) {
                        S('wap_store_group_'.($get_grouplist_catfid ? $get_grouplist_catfid : $get_grouplist_catid).'_'.$val['store_id'], array());
                        S('wap_store_group_0_'.$val['store_id'], array());
                    }
                    S('wap_group_store_'.$group_id,array());
                }
				
				//清空团购缓存
				$cache_key = 'group_stock_' . $group_id;
				cache_sql($cache_key, null);
				
                $this->success(L_('编辑成功！'));
            } else {
                $this->error(L_('编辑失败！请重试。'));
            }
        } else {
            //图片
            $group_image_class = new group_image();
            $tmp_pic_arr = explode(';', $now_group['pic']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_group['pic_arr'][$key]['title'] = $value;
                $now_group['pic_arr'][$key]['url'] = replace_file_domain($group_image_class->get_image_by_path($value, 's'));
            }
            if ($now_group['cue']) {
                $now_group['cue_arr'] = unserialize($now_group['cue']);
            }
            $now_group['content'] = replace_file_domain_content($now_group['content']);
            $this->assign('now_group', $now_group);

            //关键词
            $database_keywords = D('Keywords');
            $conditon_keywords['third_id'] = $now_group['group_id'];
            $conditon_keywords['third_type'] = 'group';
            $keywords_list = $database_keywords->field('`keyword`')->where($conditon_keywords)->order('`id` ASC')->select();
            if (!empty($keywords_list)) {
                $keywords_arr = array();
                foreach ($keywords_list as $value) {
                    $keywords_arr[] = $value['keyword'];
                }
                $keywords = implode(' ', $keywords_arr);
                $this->assign('keywords', $keywords);
            }

            //所属店铺
            $database_group_store = D('Group_store');
            $condition_group_store['group_id'] = $now_group['group_id'];
            $store_list = $database_group_store->field(true)->where($condition_group_store)->select();
            $store_arr = array();
            foreach ($store_list as $value) {
                $store_arr[] = $value['store_id'];
            }
            $this->assign('store_arr', $store_arr);



            //分类
            $database_group_category = D('Group_category');
            $condition_f_group_category['cat_fid'] = 0;
            $condition_f_group_category['cat_status'] = 1;
            $f_category_list = $database_group_category->field('`cat_id`,`cat_name`,`cat_field`,`cue_field`,`editor_title`')->where($condition_f_group_category)->order('`cat_sort` DESC,`cat_id` ASC')->select();
            if (empty($f_category_list)) {
                $this->error(L_('管理员没有添加X1分类！',$this->config['group_alias_name']));
            }
            $this->assign('f_category_list', $f_category_list);

            $condition_s_group_category['cat_fid'] = $now_group['cat_fid'];
            $condition_s_group_category['cat_status'] = 1;
            $s_category_list = $database_group_category->field('`cat_id`,`cat_name`')->where($condition_s_group_category)->order('`cat_sort` DESC,`cat_id` ASC')->select();
            $this->assign('s_category_list', $s_category_list);
            if (empty($s_category_list)) {
                $fid_cat_name = $database_group_category->where(array('cat_id' => $now_group['cat_fid']))->getField('cat_name');
                $this->error($fid_cat_name . ' 分类下没有添加子分类！');
            }

            foreach ($f_category_list as $value) {
                if ($value['cat_id'] == $now_group['cat_fid']) {
                    $now_f_category = $value;
                    break;
                }
            }

            if (!empty($now_f_category['cat_field'])) {
                $cat_field = unserialize($now_f_category['cat_field']);
                $custom_html = '';
                foreach ($cat_field as $key => $value) {
                    if (empty($value['use_field'])) {
                        $custom_html .= '<div class="form-group"><label class="col-sm-1">' . $value['name'] . '：</label>';
                        if ($value['type'] == 0) {
                            $custom_html .= '<select name="custom[custom_' . $key . ']" class="col-sm-1">';
                            foreach ($value['value'] as $k => $v) {
                                $custom_html .= '<option value="' . $k . '"';
                                if ($now_group['custom_' . $key] == $k) {
                                    $custom_html .=' selected=selected';
                                }
                                $custom_html .= ' >' . $v . '</option>';
                            }
                            $custom_html .= '</select>';
                        } else {
                            $checkVarr = explode(',', $now_group['custom_' . $key]);
                            $checkVarr = !empty($checkVarr) ? $checkVarr : array();
                            foreach ($value['value'] as $k => $v) {
                                $custom_html .= '<label style="margin-right:30px;"><input class="ace" type="checkbox" name="custom[custom_' . $key . '][]" value="' . $k . '" id="custom_' . $key . '_' . $k . '"';
                                if (in_array($k, $checkVarr)) {
                                    $custom_html .=' checked=checked';
                                }
                                $custom_html .= ' /><span class="lbl"><label for="custom_' . $key . '_' . $k . '">&nbsp;' . $v . '</label></span></label>';
                            }
                        }
                        $custom_html .= '</div>';
                    }
                }
            }
            $this->assign('custom_html', $custom_html);

            if (!empty($now_f_category['cue_field'])) {
                $cue_field = unserialize($now_f_category['cue_field']);
                $cue_html = '';
                foreach ($cue_field as $key => $value) {
                    $cue_html .= '<div class="form-group"><label class="col-sm-1">' . $value['name'] . '：</label>';
                    if ($value['type'] == 0) {
                        $cue_html .= '<input type="hidden" name="cue_field[key][]" value="' . $value['name'] . '"/><input type="text" class="col-sm-4" name="cue_field[value][]" value="' . (!empty($now_group['cue_arr'][$key]['value']) ? $now_group['cue_arr'][$key]['value'] : '') . '"/>';
                    } else {
                        $cue_html .= '<input type="hidden" name="cue_field[key][]" value="' . $value['name'] . '"/><textarea class="col-sm-4" rows="5" name="cue_field[value][]">' . (!empty($now_group['cue_arr'][$key]['value']) ? $now_group['cue_arr'][$key]['value'] : '') . '</textarea>';
                    }
                    $cue_html .= '</div>';
                }
            }
            $this->assign('cue_html', $cue_html);

            $mer_id = $now_group['mer_id'];
            $db_arr = array(C('DB_PREFIX') . 'area' => 'a', C('DB_PREFIX') . 'merchant_store' => 's');
            $store_list = D()->table($db_arr)->field('a.`area_name`,s.`adress`,`s`.`name`,`s`.`store_id`')->where("`s`.`mer_id`='$mer_id' AND `s`.`status`='1' AND `s`.`have_group`='1' AND `s`.`area_id`=`a`.`area_id`")->order('`sort` DESC,`store_id` ASC')->select();
            if (empty($store_list)) {
                $this->error(L_('您暂时还没有能添加X1信息的店铺！',$this->config['group_alias_name']));
            }
            $this->assign('store_list', $store_list);
            $leveloff = !empty($now_group['leveloff']) ? unserialize($now_group['leveloff']) : false;

            $levelDb = M('User_level');
            $tmparr = M(_view('User_level'))->where('22=22')->order('id ASC')->select();
            $levelarr = array();
            if ($tmparr && $this->config['level_onoff']) {
                foreach ($tmparr as $vv) {
                    if (!empty($leveloff) && isset($leveloff[$vv['level']])) {
                        $vv['vv'] = $leveloff[$vv['level']]['vv'];
                        $vv['type'] = $leveloff[$vv['level']]['type'];
                    } else {
                        $vv['vv'] = '';
                        $vv['type'] = '';
                    }
                    $levelarr[$vv['level']] = $vv;
                }
            }
            unset($tmparr);
            $this->assign('levelarr', $levelarr);
            $mpackageDb = M('Group_packages');
            $mpackagelist = $mpackageDb->field(true)->where(array('mer_id' => $now_group['mer_id']))->order('id DESC')->select();
            $this->assign('mpackagelist', $mpackagelist);

            if(isset($this->config['appoint_category_bgimg'])){
                $database_appoint = D('Appoint');
                $appoint_list = $database_appoint->get_appointmerchantlist_by_MerchantId($mer_id);
                $this->assign('appoint_list',$appoint_list);
            }
            ///if($now_group['trade_type']=='hotel') {
            $database_trade_hotel_category = D('Trade_hotel_category');
            $trade_hotel_category_list = $database_trade_hotel_category->where(array('mer_id' => $mer_id, 'is_remove' => '0', 'cat_fid' => '0'))->order('`cat_sort` DESC')->select();
            $this->assign('trade_hotel_category_list', $trade_hotel_category_list);
            // }


            $where_express = array('mer_id' => $this->merchant_session['mer_id']);
            $express_list = M('Express_template')->where($where_express)->select();
            $this->assign('express_list', $express_list);

            // 查询规格参数
            $group_specifications_info = M('Group_specifications')->where(array('group_id' => $now_group['group_id'], 'status' => 1))->select();
            if (!$group_specifications_info) {
                $group_specifications_info = array();
            }
            $group_specifications_count = count($group_specifications_info);
            $this->assign('group_specifications_info', $group_specifications_info);
            $this->assign('group_specifications_count', $group_specifications_count);

            // 团购二级规格多选关联
            if ($this->config['group_category_switch'] && $this->config['group_category_switch'] == 1) {
                $database_group_bind_category = M('Group_bind_category');
                $condition_group_bind_category['group_id'] = $now_group['group_id'];
                $cid_info = $database_group_bind_category->where($condition_group_bind_category)->getField('bind_id, cid');
                $this->assign('cid_info', $cid_info);
            }
            // 获取商品 各个详情
            $m_group_content = M('Group_content');
            $condition_group_content['group_id'] = $now_group['group_id'];
            $group_content = $m_group_content->order('content_id ASC')->where($condition_group_content)->select();
            if (!$group_content) {
                $group_content = array();
                $arr = array(
                    'name' => L_('商品详情'),
                    'content' => $now_group['content']
                );
                $group_content[] = $arr;
            }
            $editor_title = array();
            if ($now_f_category['editor_title']) {
                $editor_title_info = $now_f_category['editor_title'] = unserialize($now_f_category['editor_title']);
            }
            if (empty($editor_title_info)) {
                $editor_title[] = L_('商品详情');
            } else {
                foreach ($editor_title_info as $val) {
                    if ($val) {
                        $editor_title[] = $val;
                    }
                }
            }
            foreach ($editor_title as $key=>&$val) {
                if (!$group_content[$key] || $group_content[$key] != $val) {
                    $group_content[$key]['name'] = $val;
                    if (!$group_content[$key]['content']) {
                        $group_content[$key]['content'] = '';
                    }
                }
            }
            if ($group_content) {
                foreach ($group_content as &$item) {
                    $item['content'] = replace_file_domain_content($item['content']);
                }
            }
            $this->assign('group_content', $group_content);
            $this->assign('group_content_count', count($group_content));

            $this->display();
        }
    }

    public function ajax_get_category() {
        $database_group_category = D('Group_category');
        $condition_now_group_category['cat_id'] = $_GET['cat_fid'];
        $condition_now_group_category['cat_status'] = 1;
        $now_category = $database_group_category->field('`cat_field`,`cue_field`,`editor_num`,`editor_title`')->where($condition_now_group_category)->find();
        if (empty($now_category)) {
            $return['error'] = 1;
            $return['msg'] = L_('该分类不存在！');
        } else {
            $condition_s_group_category['cat_fid'] = $_GET['cat_fid'];
            $condition_s_group_category['cat_status'] = 1;
            $s_category_list = $database_group_category->field('`cat_id`,`cat_name`')->where($condition_s_group_category)->order('`cat_sort` DESC,`cat_id` ASC')->select();
            if (empty($s_category_list)) {
                $return['error'] = 1;
                $return['msg'] = L_('该分类下没有添加子分类！请勿选择。');
            } else {
                if (!empty($now_category['cat_field'])) {
                    $cat_field = unserialize($now_category['cat_field']);
                    $custom_html = '';
                    foreach ($cat_field as $key => $value) {
                        if (empty($value['use_field'])) {
                            $custom_html .= '<div class="form-group"><label class="col-sm-1">' . $value['name'] . '：</label>';
                            if ($value['type'] == 0) {
                                $custom_html .= '<select name="custom[custom_' . $key . ']" class="col-sm-1" style="margin-right:10px;">';
                                foreach ($value['value'] as $k => $v) {
                                    $custom_html .= '<option value="' . $k . '">' . $v . '</option>';
                                }
                                $custom_html .= '</select>';
                            } else {
                                foreach ($value['value'] as $k => $v) {
                                    $custom_html .= '<label style="margin-right:30px;"><input class="ace" type="checkbox" name="custom[custom_' . $key . '][]" value="' . $k . '" id="custom_' . $key . '_' . $k . '"/><span class="lbl"><label for="custom_' . $key . '_' . $k . '">&nbsp;' . $v . '</label></span></label>';
                                }
                            }
                            $custom_html .= '</div>';
                        }
                    }
                    $return['custom_html'] = $custom_html;
                } else {
                    $return['custom_html'] = '';
                }

                if (!empty($now_category['cue_field'])) {
                    $cue_field = unserialize($now_category['cue_field']);
                    $cue_html = '';
                    foreach ($cue_field as $key => $value) {
                        $cue_html .= '<div class="form-group"><label class="col-sm-1">' . $value['name'] . '：</label>';
                        if ($value['type'] == 0) {
                            $cue_html .= '<input type="hidden" name="cue_field[key][]" value="' . $value['name'] . '"/><input type="text" class="col-sm-4" name="cue_field[value][]"/>';
                        } else {
                            $cue_html .= '<input type="hidden" name="cue_field[key][]" value="' . $value['name'] . '"/><textarea class="col-sm-4" rows="5" name="cue_field[value][]"></textarea>';
                        }
                        $cue_html .= '</div>';
                    }
                    $return['cue_html'] = $cue_html;
                } else {
                    $return['cue_html'] = '';
                }

                $return['error'] = 0;
                $return['cat_list'] = $s_category_list;
            }
            $editor_title = array();
            if ($now_category['editor_title']) {
                $editor_title_info = $now_category['editor_title'] = unserialize($now_category['editor_title']);
            }
            if (empty($editor_title_info)) {
                $editor_title[] = L_('商品详情');
            } else {
                foreach ($editor_title_info as $val) {
                    if ($val) {
                        $editor_title[] = $val;
                    }
                }
            }
            $return['editor_title'] = $editor_title;
            $return['editor_title_count'] = count($editor_title);
        }
        exit(json_encode($return));
    }

    public function ajax_upload_pic() {
        if ($_FILES['imgFile']['error'] != 4) {
			$param = array('size' => $this->config['group_pic_size']);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
            $param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = $this->config['group_pic_width'];
            $param['thumbMaxHeight'] = $this->config['group_pic_height'];
            $param['thumbRemoveOrigin'] = false;
			$image = D('Image')->handle($this->merchant_session['mer_id'], 'group', 1, $param);
			if ($image['error']) {
				exit(json_encode(array('error' => 1,'message' =>$image['message'])));
			} else {
				$title = $image['title']['imgFile'];
				$group_image_class = new group_image();
				$url = $group_image_class->get_image_by_path($title, 's');
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			}
		}else{
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}
    public function ajax_del_pic() {
//        $group_image_class = new group_image();
//        $group_image_class->del_image_by_path($_POST['path']);
    }

    public function order_list() {

        $mer_id = $this->merchant_session['mer_id'];
        $group_id = intval($_GET['group_id']);
        //团购列表
        $now_group = D('Group')->get_group_by_groupId($group_id);
        if (empty($now_group)) {
            //$this->error('当前' . $this->config['group_alias_name'] . '不存在！');
        }
        $this->assign('now_group', $now_group);


        $condition_where = "`o`.`uid`=`u`.`uid` AND `o`.`group_id`=`g`.`group_id` AND `m`.`mer_id`=`o`.`mer_id` AND `o`.`mer_id` = ".$mer_id;
        if($group_id){
            $condition_where.=' AND o.group_id = '.$group_id;
        }
        if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'real_orderid') {
                $condition_where .= " AND `o`.`real_orderid`='" . htmlspecialchars($_GET['keyword'])."'";
            } elseif ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$_GET['keyword']))->find();
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($tmp_result['order_id'])."'";
            } elseif ($_GET['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= " AND `u`.`phone`='" . htmlspecialchars($_GET['keyword']) . "'";
            } elseif ($_GET['searchtype'] == 's_name') {
                $condition_where .= " AND `g`.`s_name` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            }elseif ($_GET['searchtype'] == 'third_id') {
                $condition_where .= " AND `o`.`third_id` ='".$_GET['keyword']."'";
            }
        }
        if ($this->system_session['area_id']) {
            $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
            $condition_where .= " AND `m`.`{$area_index}`={$this->system_session['area_id']}";
        }

        $status = isset($_GET['status']) ? intval($_GET['status']) : -1;
        $type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
        $sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
        $pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
        if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
        if ($type != 'price' && $type != 'pay_time') $type = '';
        $order_sort = '';
        if ($type && $sort) {
            $order_sort .= 'o.' . $type . ' ' . $sort . ',';
            $order_sort .= 'o.order_id DESC';
        } else {
            $order_sort .= 'o.order_id DESC';
        }

        if ($status === 0) {
            $condition_where .= " AND `o`.`paid`=1 AND `o`.`status`=0";
        }else if ($status != -1 && $status != 8) {
            $condition_where .= " AND `o`.`status`={$status}";
        } elseif ($status == 8) {
            $condition_where .= " AND `o`.`paid`=0";
        }
        if($pay_type){
            if($pay_type=='balance'){
                $condition_where .= " AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 OR `o`.`card_give_money` <> 0) AND `o`.`paid` = 1";
            }else{
                $condition_where .= " AND `o`.`pay_type`='{$pay_type}'";
            }
        }

        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips(L_("结束时间应大于开始时间"));
            }

            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));   

            $_GET['searchTimeType'] = $_GET['searchTimeType'] ? $_GET['searchTimeType'] : 'add_time';
            if ($_GET['searchTimeType']=='refund_time'){//退款时间
                if($status==3 || $status==6){
                    $condition_where .= " AND (o.last_time"." BETWEEN ".$period[0].' AND '.$period[1].")";
                }
            }else{
                 $condition_where .= " AND (o.".$_GET['searchTimeType']." BETWEEN ".$period[0].' AND '.$period[1].")";
            }
            //$condition_where['_string']=$time_condition;
        }

        $condition_table = array(C('DB_PREFIX').'group'=>'g', C('DB_PREFIX').'group_order'=>'o', C('DB_PREFIX').'user'=>'u', C('DB_PREFIX').'merchant'=>'m');
        $order_count = D('')->where($condition_where)->table($condition_table)->count();
        import('@.ORG.merchant_page');
        $p = new Page($order_count,20);

        $order_list = D('')->field('`o`.`phone` AS `group_phone`,`o`.*,`g`.`s_name`,`g`.`price` as g_price,`u`.`uid`,`u`.`nickname`,`u`.`phone`,`m`.`phone` as m_phone,`m`.`name` as m_name,`m`.`mer_id`,`g`.`group_id`')->where($condition_where)->table($condition_table)->order($order_sort)->limit($p->firstRow.','.$p->listRows)->select();
        if($order_list){

            // 团购优惠组合
            $combineIds = array_column($order_list, 'combine_id');
            $combineList = array_column(D('Group_combine_activity')->where(['combine_id'=>['in',$combineIds]])->select(),'title','combine_id');
            foreach ($order_list as &$value) {
                // 团购优惠组合
                if($combineList[$value['combine_id']]){
                    $value['s_name'] .= '【'.$combineList[$value['combine_id']].'】';
                }
            }
            //$this->error_tips('当前'.$this->config['group_alias_name'].'并未产生订单！');
        }
        $pay_method = D('Config')->get_pay_method('','',1);
        $this->assign('pay_method',$pay_method);
        $this->assign('order_list',$order_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status,'pay_type'=>$pay_type));
        $this->assign('status_list', D('Group_order')->status_list);

        $this->display();
    }

    //到店自提
    public function pick_in_store(){


        $pick_addr = D('Pick_address')->get_pick_addr_by_merid($this->merchant_session['mer_id'],true);
        $this->assign('pick_addr',$pick_addr);
        $this->display();
    }

    public function pick_address_add(){
        if(IS_POST){
            if(empty($_POST['pick_addr'])||empty($_POST['phone'])){
                $this->error(L_("信息不全，请检查！"));
            }
            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            if(M('Pick_address')->add($_POST)){
                $this->success(L_("保存成功！"));
            }else{
                $this->error(L_("保存失败！"));
            }

        }else{
            $this->display();
        }
    }

    public function pick_address_edit(){
        if(IS_POST){

            if(M('Pick_address')->where(array('id'=>$_POST['id']))->save($_POST)){
                $this->success(L_("保存成功！"));
            }else{
                $this->error(L_("保存失败！"));
            }

        }else{
            if(!empty($_GET['id'])){
                $n = preg_match('/\d+/',$_GET['id'],$id);
                $pick_addr = M('Pick_address')->where(array('id'=>$id[0]))->find();

                $this->assign('pick_addr',$pick_addr);
                $this->display();
            }else{
                $this->error(L_("访问失败！"));
            }
        }
    }

    public function pick_address_del(){
        if(IS_GET){
            $n = preg_match('/\d+/',$_GET['id'],$id);
            if(M('Pick_address')->where(array('id'=>$id[0]))->delete()){
                $this->success(L_("删除成功！"));
            }else{
                $this->error(L_("删除失败！"));
            }
        }
    }

    public function order_detail() {
        if(strlen($_GET['order_id'])>=20){
            $now_group_order = D('Group_order')->where(array('real_orderid'=>$_GET['order_id']))->find();
            $_GET['order_id'] = $now_group_order['order_id'];
        }
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->merchant_session['mer_id'], $_GET['order_id'], false);
        if (empty($now_order)) {
            exit(L_('此订单不存在！'));
        }
        if (!empty($now_order['pay_type'])) {
            if($now_order['is_pick_in_store']){
                $now_order['paytypestr']=L_("到店自提");
            }else{
                $now_order['paytypestr'] = D('Pay')->get_pay_name($now_order['pay_type'],$now_order['is_mobile_pay'],$now_order['paid']);
            }

            if (($now_order['pay_type'] == 'offline') && !empty($now_order['third_id']) && ($now_order['paid'] == 1)) {
                $now_order['paytypestr'] .='<span style="color:green">&nbsp; 已支付</span>';
            } else if (($now_order['pay_type'] != 'offline') && ($now_order['paid'] == 1)) {
                $now_order['paytypestr'] .='<span style="color:green">&nbsp; 已支付</span>';
            } else {
                $now_order['paytypestr'] .='<span style="color:red">&nbsp; 未支付</span>';
            }
        }else {
			if($now_order['paid']){
				$now_order['paytypestr'] = L_('余额支付');
			}else{
				$now_order['paytypestr'] = L_('未支付');
			}
        }
        $pin_info = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
        $this->assign('pin_info', $pin_info);

        if ($now_order['tuan_type'] == 2 && $now_order['paid'] == 1) {
            $express_list = D('Express')->get_express_list();
            $this->assign('express_list', $express_list);

            //得到该订单归属团购的店铺列表
            $group_store_list = D('Group_store')->get_storelist_by_groupId($now_order['group_id']);
            $this->assign('group_store_list', $group_store_list);
        }
        $pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
        $this->assign('pass_array',$pass_array);
//        if(!empty($now_order['coupon_id'])) {
//            $system_coupon = D('System_coupon')->get_coupon_info($now_order['coupon_id']);
//            $now_order['coupon_price'] = $system_coupon['price'];
//            $this->assign('system_coupon',$system_coupon);
//        }else if(!empty($now_order['card_id'])) {
//            $card = D('Member_card_coupon')->get_coupon_info($now_order['card_id']);
//            $now_order['coupon_price'] = $card['price'];
//            $this->assign('card', $card);
//        }
//
        if($now_order['refund_detail'] && unserialize($now_order['refund_detail'])){
            $now_order['refund_detail'] = unserialize($now_order['refund_detail']);
            if ($now_order['refund_detail']['msg'] && $now_order['refund_detail']['error']) {
                $now_order['refund_detail']['msg'] = L_("错误码：").$now_order['refund_detail']['error'] . ', '. $now_order['refund_detail']['msg'];
            }
        }elseif($now_order['refund_detail'] && !$now_order['refund_detail']['refund_id']){
            $now_order['refund_detail']['msg'] = str_replace('certificate not match',L_('微信支付证书不匹配'),$now_order['refund_detail']);
        }elseif($now_order['refund_detail'] && $now_order['refund_detail']['msg']){
            $now_order['refund_detail']['msg'] = str_replace('certificate not match',L_('微信支付证书不匹配'),$now_order['refund_detail']['msg']);
        }elseif($now_order['refund_detail'] && $now_order['refund_detail']['err_msg']){
            $now_order['refund_detail']['err_msg'] = str_replace('certificate not match',L_('微信支付证书不匹配'),$now_order['refund_detail']['err_msg']);
        }
        if($now_order['refund_detail'] && !is_array($now_order['refund_detail'])){
            $err_msg = array();
            $err_msg['err_msg'] = $now_order['refund_detail'];
            $now_order['refund_detail'] = $err_msg;
        }
        // 退款详情
        if ($now_order['refund_detail']) {
            $refund_detail = $now_order['refund_detail'];
        } else {
            $refund_detail = array();
        }
        if ($now_order['refund_detail']['refund_time'] && strtotime($now_order['refund_detail']['refund_time'])) {
            $now_order['refund_detail']['refund_time'] = strtotime($now_order['refund_detail']['refund_time']);
        }
        $this->assign('refund_detail',$refund_detail);

        $pass_num = $now_order['pass_num']>1?intval($now_order['pass_num']):1; // 一份团购 对应生成核销券 份数
        $total_pass_num = $now_order['num']*$pass_num; // 改订单产生的总 核销券 份数
        $unconsume_pass_num = 0;
        if($now_order['pass_array']){
            $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
            $unconsume_pass_num = $total_pass_num-$consume_num;
        }elseif($now_order['tuan_type']==2){
            $unconsume_pass_num = $total_pass_num;
        }elseif ($now_order['status']==0){
            $unconsume_pass_num = 1;
        }
        $pass_info = array(
            'total_pass_num' => $total_pass_num,
            'unconsume_pass_num' => $unconsume_pass_num,
        );
        $this->assign('pass_info',$pass_info);

		if($now_order['trade_info']){
			$trade_info_arr = unserialize($now_order['trade_info']);
			if($trade_info_arr['type'] == 'hotel'){
				$trade_hotel_info = D('Trade_hotel_category')->format_order_trade_info($now_order['trade_info']);
                $now_order['refund_detail'] = unserialize($now_order['refund_detail']);
				$this->assign('trade_hotel_info',$trade_hotel_info);
			}
		}

        $write_info = unserialize($now_order['write_field']);
        if ($write_info) {
            $write_list = array();
            foreach($write_info as $key=>$val){
                if(!empty($write_info[$key]['value'])){
                    $write_list[$key]['name'] = $val['name'];
                    $write_list[$key]['value'] = $val['value'];
                    $write_list[$key]['type'] = $val['type'];
                    if($write_info[$key]['type'] == 2){
                        $write_list[$key]['long'] = $val['long'];
                        $write_list[$key]['lat'] = $val['lat'];
                        $write_list[$key]['address'] = $val['address'];
                        $write_list[$key]['value'] = $val['value'].' '.$val['value-desc'];
                    }
                }
            }
            $now_order['write_list'] = $write_list;
        }

        // 如果存在 获取对应规格
        if ($now_order['specifications_id']) {
            $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->find();
            if ($specifications_info) {
                $now_order['specifications_info'] = $specifications_info;
            } else {
                $now_order['specifications_info'] = array();
            }
        }

        // 团购优惠组合
        $combineList = D('Group_combine_activity')->where(['combine_id'=> $now_order['combine_id'] ])->find();
        if($combineList){
            $now_order['s_name'] .= '【'.$combineList['title'].'】';
        }
            

        $this->assign('now_order', $now_order);
        $this->display();
    }

    public function order_store_id() {
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->merchant_session['mer_id'], $_GET['order_id'], true, false);
        if (empty($now_order)) {
            $this->error(L_('此订单不存在！'));
        }
        if (empty($now_order['paid'])) {
            $this->error(L_('此订单尚未支付！'));
        }
        $condition_group_order['order_id'] = $now_order['order_id'];
        $data_group_order['store_id'] = $_POST['store_id'];
        if (D('Group_order')->where($condition_group_order)->data($data_group_order)->save()) {
			$now_order['store_id'] = $_POST['store_id'];
			//店员APP推送
			D('Merchant_store_staff')->sendMsgGroupOrder($now_order);
			
            $this->success(L_('修改成功！'));
        } else {
            $this->error(L_('修改失败！请重试。'));
        }
    }

    public function group_remark() {
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->merchant_session['mer_id'], $_GET['order_id'], true, false);
        if (empty($now_order)) {
            $this->error(L_('此订单不存在！'));
        }
        if (empty($now_order['paid'])) {
            $this->error(L_('此订单尚未支付！'));
        }
        $condition_group_order['order_id'] = $now_order['order_id'];
        $data_group_order['merchant_remark'] = $_POST['merchant_remark'];
        if (D('Group_order')->where($condition_group_order)->data($data_group_order)->save()) {
            $this->success(L_('修改成功！'));
        } else {
            $this->error(L_('修改失败！请重试。'));
        }
    }

    public function group_pass_array(){
        $this->check_group();
        $database_group_order = D('Group_order');
        $now_order = $database_group_order->get_order_detail_by_id_and_merId($this->merchant_session['mer_id'],$_GET['order_id'],false);
        $pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
        $this->assign('pass_array',$pass_array);
        $this->assign('now_order',$now_order);
        $this->display();
    }

    /*     * ****套餐管理页**开始****** */

    public function mpackage() {
        $mpackageDb = M('Group_packages');
        $_count = $mpackageDb->where(array('mer_id' => $this->merchant_session['mer_id']))->count();
        import('@.ORG.merchant_page');
        $p = new Page($_count, 20);
        $mpackagelist = $mpackageDb->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('mpackagelist', $mpackagelist);
        $this->display();
    }

    public function mpackageadd() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $mpackageDb = M('Group_packages');
        if (IS_POST) {
            $_POST['title'] = trim($_POST['title']);
            if (empty($_POST['title']))
                $this->error(L_('套餐标示名称不能为空，必须填上'));
            $id = isset($_POST['idx']) ? intval($_POST['idx']) : $id;
            unset($_POST['idx']);
            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            if ($id > 0) {
                $tmpid = $mpackageDb->where(array('id' => $id))->save($_POST);
                $this->success(L_('修改成功！'), U('Group/mpackage'));
                exit();
            } else {
                $tmpid = $mpackageDb->add($_POST);
                $this->success(L_('保存成功！'), U('Group/mpackage'));
                exit();
            }
            $this->error(L_('保存失败'));
            exit();
        } else {
            $mpackage = $mpackageDb->where(array('id' => $id))->find();
            $this->assign('mpackage', !empty($mpackage) ? $mpackage : array('id' => 0, 'title' => '', 'description' => ''));
            $this->display();
        }
    }

    public function start_group_list(){
        $mer_id =  $this->merchant_session['mer_id'];
        $start_group_list = D('Group_start')->get_start_group_list_by_merid($mer_id);
        $this->assign('start_group_list',$start_group_list);
        $this->display();
    }

    public function start_group_info(){
        $mer_id =  $this->merchant_session['mer_id'];
        $buyer_list = D('Group_start')->get_buyer_list_by_order_id('',$_GET['id']);
        $robot_list = M('Robot_list')->where(array('mer_id'=>$mer_id))->getField('id,robot_name');
        foreach ($buyer_list as &$v) {
            if($v['type']==1){
                $v['nickname'] = $robot_list[$v['uid']];
                $v['phone'] = '';
            }
        }

        $this->assign('buyer_list',$buyer_list);
        $this->display();
    }

    public function update_start_status(){
        $status = $_POST['status'];
        $id = explode(',',$_POST['id']);

        if($_POST['status']==3){
            foreach ($id as $item) {
                $start_info = D('Group_start')->where(array('id'=>$item))->find();
                $need_num = $start_info['complete_num']-$start_info['num'];
                if($need_num>0){
                    D('Group_start')->create_robot($start_info);
                }
            }

        }
        $this->ajaxReturn(D('Group_start')->update_start_group($id,$status));
    }



    public function export()
    {
       $param = $_POST;
        $param['type'] = 'group';
        $param['rand_number'] = time();
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
        die;
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = L_('团购订单信息');
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        // 设置当前的sheet
        $condition_where = "WHERE o.mer_id = ".$this->merchant_session['mer_id'];
        if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'real_orderid') {
                $condition_where .= " AND `o`.`real_orderid`='" . htmlspecialchars($_GET['keyword'])."'";
            } elseif ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$_GET['keyword']))->find();
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($tmp_result['order_id'])."'";
            } elseif ($_GET['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= " AND `u`.`phone`='" . htmlspecialchars($_GET['keyword']) . "'";
            } elseif ($_GET['searchtype'] == 's_name') {
                $condition_where .= " AND `g`.`s_name` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            }elseif ($_GET['searchtype'] == 'third_id') {
                $condition_where .= " AND `o`.`third_id` ='".$_GET['keyword']."'";
            }
        }
        if ($this->system_session['area_id']) {
            $area_index = D('Area')->getIndexByAreaID($this->system_session['area_id']);
            $condition_where .= " AND `m`.`{$area_index}`={$this->system_session['area_id']}";
        }

        $status = isset($_GET['status']) ? intval($_GET['status']) : -1;
        $type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
        $sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
        $pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
        if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
        if ($type != 'price' && $type != 'pay_time') $type = '';
        $order_sort = '';
        if ($type && $sort) {
            $order_sort .= 'o.' . $type . ' ' . $sort . ',';
            $order_sort .= 'o.order_id DESC';
        } else {
            $order_sort .= 'o.order_id DESC';
        }

        if ($status != -1) {
            $condition_where .= " AND `o`.`status`={$status}";
        }
        if($pay_type){
            if($pay_type=='balance'){
                $condition_where .= " AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 )";
            }else{
                $condition_where .= " AND `o`.`pay_type`='{$pay_type}'";
            }
        }

        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $condition_where .= " AND (o.add_time BETWEEN ".$period[0].' AND '.$period[1].")";
            //$condition_where['_string']=$time_condition;
        }

        $sql = "SELECT count(order_id) as count FROM " . C('DB_PREFIX') . "group_order AS o  LEFT JOIN " . C('DB_PREFIX') . "group g ON g.group_id = o.group_id  LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON `o`.`mer_id`=`m`.`mer_id` LEFT JOIN " . C('DB_PREFIX') . "user u ON u.uid = o.uid ".$condition_where." ORDER BY o.order_id DESC ";
        $count = D()->query($sql);

        $length = ceil($count[0]['count'] / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息',($i+1)));
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', L_('订单编号'));
            $objActSheet->setCellValue('B1', L_('商家名称'));
            $objActSheet->setCellValue('C1', L_('客户姓名'));
            $objActSheet->setCellValue('D1', L_('客户电话'));
            $objActSheet->setCellValue('E1', L_('订单总价'));
            $objActSheet->setCellValue('F1', L_('平台余额'));
            $objActSheet->setCellValue('G1', L_('商家余额'));
            $objActSheet->setCellValue('H1', L_('在线支付金额'));
            $objActSheet->setCellValue('I1', L_('平台').$this->config['score_name'].'');
            $objActSheet->setCellValue('J1', L_('平台优惠券'));
            $objActSheet->setCellValue('K1', L_('商家优惠券'));
            $objActSheet->setCellValue('L1', L_('商家折扣'));
            $objActSheet->setCellValue('M1', L_('支付时间'));
            $objActSheet->setCellValue('N1', L_('订单状态'));
            $objActSheet->setCellValue('O1', L_('支付情况'));
            $sql = "SELECT o.*, m.name AS merchant_name,u.nickname as username FROM " . C('DB_PREFIX') . "group_order AS o  LEFT JOIN " . C('DB_PREFIX') . "group g ON g.group_id = o.group_id  LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON `o`.`mer_id`=`m`.`mer_id` LEFT JOIN " . C('DB_PREFIX') . "user u ON u.uid = o.uid ".$condition_where." ORDER BY o.order_id DESC LIMIT " . $i * 1000 . ",1000";
            $result_list = D()->query($sql);

            if (!empty($result_list)) {
                $index = 2;
                foreach ($result_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['merchant_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['username'] . ' ');
                    $objActSheet->setCellValueExplicit('D' . $index, $value['phone'] . ' ');
                    $objActSheet->setCellValueExplicit('E' . $index, floatval($value['total_money']));
                    $objActSheet->setCellValueExplicit('F' . $index, floatval($value['balance_pay']));
                    $objActSheet->setCellValueExplicit('G' . $index, floatval($value['merchant_balance']));
                    $objActSheet->setCellValueExplicit('H' . $index, floatval($value['payment_money']));
                    $objActSheet->setCellValueExplicit('I' . $index, floatval($value['score_reducte']));
                    $objActSheet->setCellValueExplicit('J' . $index, floatval($value['coupon_price']));
                    $objActSheet->setCellValueExplicit('K' . $index, floatval($value['card_price']));
                    $objActSheet->setCellValueExplicit('L' . $index, floatval($value['card_discount'])?floatval($value['card_discount']). L_('折'):'');
                    $objActSheet->setCellValueExplicit('M' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
                    $objActSheet->setCellValueExplicit('N' . $index, $this->get_order_status($value));
                    $objActSheet->setCellValueExplicit('O' . $index, D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']));


                    $index++;
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

    public function get_order_status($order){
        $status = '';
        if($order['paid']){
            if($order['pay_type']=='offline' && empty($order['third_id'])&& $order['status'] == 0){
                $status=L_('线下支付，未付款');
            }elseif($order['status']==0){
                $status=L_('已付款');
                if($order['tuan_type'] != 2){
                    $status.=L_('已付款');
                }else{
                    if($order['is_pick_in_store']){
                        $status.=L_('未取货');
                    }else{
                        $status.=L_('未发货');
                    }
                }
            }elseif($order['status']==1){
                if($order['tuan_type'] != 2){
                    $status=L_('已消费');
                }else{
                    if($order['is_pick_in_store']){
                        $status=L_('已取货');
                    }else{
                        $status=L_('已发货');
                    }
                }
                $status.=L_('待评价');
            }elseif($order['status']==2){
                $status=L_('已完成');
            }elseif($order['status']==3){
                $status=L_('已退款');
            }elseif($order['status']==4){
                $status=L_('已取消');
            }
        }else{
            if($status==4){
                $status=L_('已取消');
            }else{
                $status=L_('未付款');
            }
        }

        return $status;
    }

    public function del_package(){
        if(!empty($_GET['id'])){
            if(M('Group_packages')->where(array('id'=>$_GET['id']))->delete()){
                $date['tagname'] = '';
                $date['packageid'] = 0;
                $where['packageid'] = $_GET['id'];
                M('Group')->where($where)->save($date);
                $this->success(L_('删除成功'));
            }else{
                $this->error(L_('删除失败'));
            }
        }else{
            $this->error(L_('非法操作'));
        }
    }

    /*     * ****套餐管理页**结束**** */



    //通过坐标获取城市  通过城市名字查询数据
    public function cityMatching(){
        $url = 'http://api.map.baidu.com/geocoder/v2/?output=json&ak=' . $this->config['baidu_map_ak'] . '&location='.$_GET['lat'].','.$_GET['lng'];
        import('ORG.Net.Http');
        $http = new Http();
        $result = $http->curlGet($url);
        $result = json_decode($result,true);
        $city_name = $result['result']['addressComponent']['city'];
        $long   =   strlen($city_name);
        if($long >= 7){
            $city_name    =   str_replace(L_('市'),'',$city_name);
            $city_name    =   str_replace(L_('地区'),'',$city_name);
            $city_name    =   str_replace(L_('特别行政区'),'',$city_name);
            $city_name    =   str_replace(L_('特別行政區'),'',$city_name);
            $city_name    =   str_replace(L_('蒙古自治州'),'',$city_name);
            $city_name    =   str_replace(L_('回族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('柯尔克孜自治州'),'',$city_name);
            $city_name    =   str_replace(L_('哈萨克自治州'),'',$city_name);
            $city_name    =   str_replace(L_('土家族苗族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('藏族羌族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('傣族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('布依族苗族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('苗族侗族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('壮族苗族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('澳门'),L_('澳門'),$city_name);
            $city_name 	  =   str_replace(L_('哈尼族彝族自治州'),'',$city_name);
            $city_name 	  =   str_replace(L_('傣族景颇族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('藏族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('彝族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('白族自治州'),'',$city_name);
            $city_name    =   str_replace(L_('傈僳族自治州'),'',$city_name);
        }
        $database_area = D('Area');
        $database_field = '`area_id`,`area_name`,`area_url`';
        $condition_all_city['area_name'] = $city_name;
        $condition_all_city['area_type'] = 2;
        $condition_all_city['is_open'] = 1;
        $oCity = $database_area->field($database_field)->where($condition_all_city)->find();
        if($oCity){
            // 获取详细地址
            $address_detail = $result['result']['formatted_address']  . ' ' . $result['result']['sematic_description'];
            exit(json_encode(array('error'=>1,'msg'=>L_('获取成功'),'area_id'=>$oCity['area_id'],'area_name'=>$oCity['area_name'], 'detail' => $address_detail)));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>L_('当前城市未开启，请选择别的城市'))));
        }

    }
}
