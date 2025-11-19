<?php

class ShopAction extends BaseAction
{

    const GOODS_SORT_LEVEL = 3;
	/* 店铺管理 */
	public function index()
	{
		$mer_id = $this->merchant_session['mer_id'];
		$this->assign('mer_id',$mer_id);
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['mer_id'] = $mer_id;
		$condition_merchant_store['have_shop'] = '1';
		$condition_merchant_store['status'] = '1';
		if ($this->merchant_session['store_id']) {
            $count_store = 1;
		} else {
            $count_store = $database_merchant_store->where($condition_merchant_store)->count();
		}
		$db_arr = array(C('DB_PREFIX') . 'area' => 'a', C('DB_PREFIX') . 'merchant_store' => 's');
		import('@.ORG.merchant_page');
		$p = new Page($count_store, 30);

		//获取该商家存在快店的门店
		$where = array(
			's.mer_id' => $mer_id,
			's.status' => 1,
			's.have_shop' => 1
		);
		if ($this->merchant_session['store_id']) {
			$where['s.store_id'] = $this->merchant_session['store_id'];
		}
		$order = "`s`.`sort` DESC,`s`.`store_id` ASC";
		$store_list = M()->table(C('DB_PREFIX')._view('merchant_store').' as s')->join("left join ".C('DB_PREFIX')."merchant_store_shop as ss on ss.store_id=s.store_id")->field("s.*,ss.store_theme,ss.store_id as sid,ss.zbw_sBranchNo")->where($where)->order($order)->limit($p->firstRow,$p->listRows)->select();

		if($this->config['meituan_developer_id'] && $this->config['meituan_sign_key']){
			import('@.ORG.Meituan');
			$eleme = new Meituan();
		}

		foreach ($store_list as &$store) {
			if($this->config['meituan_developer_id'] && $this->config['meituan_sign_key']){
				$store['meituan_url'] = $eleme->getAuthUrl($store['store_id'], $store['name']);
				$store['meituan_cancel_url'] = $eleme->cancelBind($store['meituan_token']);
			}
			$store['has_apply_category'] = M('Shop_category_relation_apply')->where(array('store_id'=>$store['store_id']))->count();
			$store['has_category'] = M('Shop_category_relation')->where(array('store_id'=>$store['store_id']))->count();
		}

		$this->assign('store_list', $store_list);

		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);

		$this->display();
	}

	/* 店铺信息修改 */
	public function shop_edit()
	{
	    if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $_GET['store_id']) {
	        $this->error(L_('您没有这个权限'));
	    }
		if (!empty($_SESSION['system'])) {
			$this->assign('login_system',true);
		}

		$now_store = $this->check_store($_GET['store_id']);
		$store_id = $now_store['store_id'];
		if(IS_POST){
		    if ($_POST['delivery_range_type'] == 1 && ($_POST['deliver_type'] == 1 || $_POST['deliver_type'] == 4)) {
		        if ($_POST['delivery_range_polygon']) {
		            $latLngArray = explode('|', $_POST['delivery_range_polygon']);
		            if (count($latLngArray) < 3) {
		                $this->error(L_('请绘制一个合理的服务范围！'));
		            } else {
		                $latLngData = array();
		                foreach ($latLngArray as $row) {
		                    $latLng = explode(',', $row);
		                    // $latLngData[] = array('lat' => $latLng[0], 'lng' => $latLng[1]);
		                    $latLngData[] = $latLng[1] . ' ' . $latLng[0];//array('lat' => $latLng[0], 'lng' => $latLng[1]);
		                }
		                $latLngData[] = $latLngData[0];
		                $_POST['delivery_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
		                $_POST['custom_id'] = 0;
		            }
		        } else {
		            $this->error(L_('请绘制您的服务范围！'));
		        }
		        unset($_POST['delivery_radius']);
		    } else {
		        unset($_POST['delivery_range_polygon']);
		    }
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

			$leveloff = isset($_POST['leveloff']) ? $_POST['leveloff'] :false;
			unset($_POST['leveloff']);

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

			$_POST['voice_phone'] = trim($_POST['voice_phone']);
			$_POST['store_discount'] = intval(floatval($_POST['store_discount']) * 10);
			$_POST['store_discount'] = ($_POST['store_discount'] > 100 || $_POST['store_discount'] < 0) ? 0 : $_POST['store_discount'];
			$_POST['discount_type'] = isset($_POST['discount_type']) ? intval($_POST['discount_type']) : 0;
			$_POST['reduce_stock_type'] = isset($_POST['reduce_stock_type']) ? intval($_POST['reduce_stock_type']) : 0;
			$_POST['rollback_time'] = isset($_POST['rollback_time']) ? intval($_POST['rollback_time']) : 20;
			$_POST['rollback_time'] = max(10, $_POST['rollback_time']);
			$_POST['work_time'] = isset($_POST['work_time']) ? intval($_POST['work_time']) : 20;
// 			$_POST['virtual_sale_count'] = isset($_POST['virtual_sale_count']) ? intval($_POST['virtual_sale_count']) : 0;//虚拟销量
			$_POST['leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';
			if($leveloff === false) unset($_POST['leveloff']);
			$database_merchant_store_shop = D('Merchant_store_shop');
			$deliver_type = isset($_POST['deliver_type']) ? intval($_POST['deliver_type']) : 0;
			//unset($_POST['deliver_type']);

            if (!isset($_POST['pack_type']) || !in_array($_POST['pack_type'], [1, 2])) {
                $this->error(L_('请选择打包费收取类型！'));
            }
            if ($_POST['pack_type'] == 1) {
                $_POST['pack_fee'] = 0;
            }

            // 订餐时间
            if($_POST['is_remind'] == 1){
                if(!$_POST['remind_time']){
                    $this->error(L_('开启订餐提醒必须设定订餐提醒时间'));
                }else{
                    $_POST['remind_time'] = strtotime($_POST['remind_time'].':00');
                }
            }

            //多直播地址
            $store_live_urls = [];
            $live_cates = isset($_POST['live_cate']) ? $_POST['live_cate'] : [];
            $live_urls = isset($_POST['live_url']) ? $_POST['live_url'] : [];
            $live_pics = isset($_POST['live_pic']) ? $_POST['live_pic'] : [];
            foreach ($live_cates as $lk => $lv) {
                if ($lv && isset($live_urls[$lk]) && $live_urls[$lk]) {
                    $store_live_urls[] = [
                        'live_cate' => $lv,
                        'live_url' => htmlspecialchars_decode($live_urls[$lk]),
                        'live_pic' => $live_pics[$lk] ?? ''
                    ];
                }
            }
            $_POST['store_live_urls'] = $store_live_urls ? json_encode($store_live_urls, JSON_UNESCAPED_UNICODE) : '{}';

            $_POST['remind_weekend'] = $_POST['remind_weekend'] ? implode(',', $_POST['remind_weekend']) : '';

			$store_shop = $database_merchant_store_shop->field(true)->where(array('store_id' => $store_id))->find();
			if ($store_shop) {
				if (in_array($store_shop['deliver_type'], array(0, 3)) && in_array($deliver_type, array(0, 3))) {//平台=>平台 配送距离不修改
					unset($_POST['delivery_radius']);
					$_POST['s_send_time'] = $store_shop['s_send_time'] ? $store_shop['s_send_time'] : $this->config['deliver_send_time'];
					$_POST['send_time'] = $_POST['s_send_time'];
				}
				if (in_array($store_shop['deliver_type'], array(1, 2, 4)) && in_array($deliver_type, array(0, 3))) {//商家=>平台 配送距离设置为0
					$_POST['delivery_radius'] = 0;
				}
                if (empty($store_shop['create_time'])) {
                    $_POST['create_time'] = time();
                } else {
                    $_POST['last_time'] = time();
                }

                $diffTime = 1;
                if ($_POST['send_time_type'] == 1) {
                    $diffTime = 60;
                } elseif ($_POST['send_time_type'] == 2) {
                    $diffTime = 1440;
                } elseif ($_POST['send_time_type'] == 3) {
                    $diffTime = 1440 * 7;
                } elseif ($_POST['send_time_type'] == 4) {
                    $diffTime = 1440 * 30;
                }
                //sort_time显示给用户看的，以及用于排序
                $_POST['sort_time'] = $_POST['work_time'] * $diffTime + $_POST['send_time'];
                if($_POST['is_close'] == 1){
                    $_POST['close_time'] = time();
                    $_POST['close_by'] = 0;
                }
                if($_POST['is_close'] == 0 && $store_shop['is_close']==1){
                    $_POST['open_time'] = time();
                }
				//$_POST['sale_count'] = $store_shop['sale_count'] - $store_shop['virtual_sale_count'] + $_POST['virtual_sale_count'];
				$operat_shop = $database_merchant_store_shop->data($_POST)->save();
				//多语言表增加数据
				$lang_model = D('Lang');
				$_POST['store_id'] = $store_id;
				$lang_model->edit_lang_data('Merchant_store_shop',$_POST);
			} else {
				if ($deliver_type == 0 || $deliver_type == 3) {
					$_POST['delivery_radius'] = 0;
					$_POST['send_time'] = $_POST['s_send_time'] = $this->config['deliver_send_time'];
				} else {
				    $_POST['s_send_time'] = is_null($this->config['deliver_send_time'])?0:$this->config['deliver_send_time'];
				}
				$_POST['create_time'] = time();
// 				$_POST['sale_count'] = $_POST['virtual_sale_count'];

				$diffTime = 1;
				if ($_POST['send_time_type'] == 1) {
				    $diffTime = 60;
				} elseif ($_POST['send_time_type'] == 2) {
				    $diffTime = 1440;
				} elseif ($_POST['send_time_type'] == 3) {
				    $diffTime = 1440 * 7;
				} elseif ($_POST['send_time_type'] == 4) {
				    $diffTime = 1440 * 30;
				}
				$_POST['sort_time'] = $_POST['work_time'] * $diffTime + $_POST['send_time'];

				$operat_shop = $database_merchant_store_shop->add($_POST);
				//多语言表增加数据
				$lang_model = D('Lang');
				$_POST['store_id'] = $store_id;
				$lang_model->add_lang_data('Merchant_store_shop',$_POST);
			}

			//处理配送
			$deliver_store = D('Deliver_store')->field(true)->where(array('store_id' => $now_store['store_id']))->find();
			if ($deliver_type != 2) {
				$t_type = ($deliver_type == 0 || $deliver_type == 3) ? 0 : 1;
				$deliver['store_id'] = $now_store['store_id'];
				$deliver['mer_id'] = $now_store['mer_id'];
				$deliver['site'] = $now_store['adress'];
				$deliver['type'] = $t_type;
				$deliver['range'] = $_POST['delivery_radius'];
				if ($deliver_store) {
					D('Deliver_store')->where(array('pigcms_id' => $deliver_store['pigcms_id']))->save($deliver);
				} else {
					D('Deliver_store')->data($deliver)->add();
				}
			} elseif ($deliver_type == 2 && $deliver_store) {
				D('Deliver_store')->field(true)->where(array('store_id' => $now_store['store_id']))->save(array('type' => 2));
			}

			$database_shop_category_relation = D('Shop_category_relation');
			$condition_shop_category_relation['store_id'] = $now_store['store_id'];

			// $database_shop_category_relation->where($condition_shop_category_relation)->delete();
			/* 提交分类需要审核 2019-12-06*/

			// 查询已通过的分类
			$old_category = $database_shop_category_relation->where($condition_shop_category_relation)->select();
			$old_cat_id = array_column($old_category, 'cat_id');

			// 删除原来数据
			$database_shop_category_relation->where($condition_shop_category_relation)->delete();
			// 删除原来审核数据
			M('Shop_category_relation_apply')->where($condition_shop_category_relation)->delete();
			$data_category_apply = array();
			foreach($cat_ids as $key => $value){
				if ($_SESSION['system']) {
					// 总管理员登录 无需审核
					$data_shop_category_relation[$key]['cat_id'] = $value['cat_id'];
					$data_shop_category_relation[$key]['cat_fid'] = $value['cat_fid'];
					$data_shop_category_relation[$key]['store_id'] = $now_store['store_id'];
				}else{
					if (in_array($value['cat_id'], $old_cat_id)) {
						// 已审核过的
						$data_shop_category_relation[$key]['cat_id'] = $value['cat_id'];
						$data_shop_category_relation[$key]['cat_fid'] = $value['cat_fid'];
						$data_shop_category_relation[$key]['store_id'] = $now_store['store_id'];
					}else{
						$data_category_apply[$key]['cat_id'] = $value['cat_id'];
						$data_category_apply[$key]['cat_fid'] = $value['cat_fid'];
						$data_category_apply[$key]['store_id'] = $now_store['store_id'];
						$data_category_apply[$key]['addtime'] = time();
						// 添加待审核的数据
						M('Shop_category_relation_apply')->add($data_category_apply[$key]);
					}
				}
			}
			// 添加已审核的数据
			$database_shop_category_relation->addAll($data_shop_category_relation);
			$this->success(L_('编辑成功！'));
		} else {
			$database_merchant_store_shop = D(_view('Merchant_store_shop'));
			$condition_merchant_store_shop['store_id'] = $now_store['store_id'];
			$store_shop = $database_merchant_store_shop->field(true)->where($condition_merchant_store_shop)->find();
            // 开启订餐提醒
            if($store_shop['is_remind'] == 1){
                if($store_shop['remind_time']){
                    $store_shop['remind_time'] = date('H:i', $store_shop['remind_time']);
                }else{
                    $store_shop['remind_time'] = '';
                }
            }
            $store_shop['remind_weekend_arr'] = $store_shop['remind_weekend'] ? explode(',', $store_shop['remind_weekend']) : [];
			if ($store_shop && $store_shop['delivery_range_polygon']) {
			    $store_shop['delivery_range_polygon'] = substr($store_shop['delivery_range_polygon'], 9, strlen($store_shop['delivery_range_polygon']) - 11);
			    $lngLatData = explode(',', $store_shop['delivery_range_polygon']);
			    array_pop($lngLatData);
			    $lngLats = array();
			    foreach ($lngLatData as $lnglat) {
			        $lng_lat = explode(' ', $lnglat);
			        $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
			    }
			    $store_shop['delivery_range_polygon'] = json_encode(array($lngLats));
			}
			//默认自行车，摩托车，汽车
			if(empty($store_shop)){
				$store_shop['electric_bicycle_parking_duration'] = '1';
				$store_shop['motorcycle_parking_duration'] = '10';
				$store_shop['car_parking_duration'] = '12';
			}

            //直播地址
            $store_shop['store_live_urls'] = json_decode($store_shop['store_live_urls'], true);

			//所有分类
			$database_shop_category = D('Shop_category');
			$category_list = $database_shop_category->lists();
			$this->assign('category_list', $category_list);

			//此店铺有的分类
			$database_shop_category_relation = D('Shop_category_relation');
			$condition_shop_category_relation['store_id'] = $now_store['store_id'];
			$relation_list = $database_shop_category_relation->field(true)->where($condition_shop_category_relation)->select();
			$relation_list = $relation_list ?: [];
			$relation_apply_list = M('Shop_category_relation_apply')->where($condition_shop_category_relation)->select();
			$relation_apply_list = $relation_apply_list ? $relation_apply_list : array();
			$relation_apply_ids = array_column($relation_apply_list, 'cat_id');
			$relation_list = array_merge($relation_list,$relation_apply_list);
			$relation_array = array();
			foreach ($relation_list as $key => $value) {
				array_push($relation_array, $value['cat_id']);
			}

			$store_shop['store_discount'] *= 0.1;
			$this->assign('relation_array', $relation_array);
			$this->assign('relation_apply_ids', $relation_apply_ids);
			$leveloff = !empty($store_shop['leveloff']) ? unserialize($store_shop['leveloff']) :false;
			$tmparr = M(_view('User_level'))->order('id ASC')->select();
			$levelarr = array();
			if ($tmparr && $this->config['level_onoff']) {
				foreach ($tmparr as $vv) {
                    if($this->config['discount_sync'] == 1 && !isset($store_shop['store_id'])){//同步
                        $vv['vv'] = $vv['boon'];
                    }else{
                        if (!empty($leveloff) && isset($leveloff[$vv['level']])) {
                            $vv['vv'] = $leveloff[$vv['level']]['vv'];
                            $vv['type'] = $leveloff[$vv['level']]['type'];
                        } else {
                            $vv['vv'] = '';
                            $vv['type'] = '';
                        }
                    }
					$levelarr[$vv['level']] = $vv;
				}
			}
			unset($tmparr);
			$this->assign('levelarr', $levelarr);

			if ($this->config['is_open_system_deliver']) {
                $deliver_types = array(
                    array(
                        'id' => 0,
                        'name' => $this->config['deliver_name']
                    ),
                    array(
                        'id' => 1,
                        'name' => L_('商家配送')
                    ),
                    array(
                        'id' => 2,
                        'name' => L_('客户自提')
                    ),
                    array(
                        'id' => 3,
                        'name' => $this->config['deliver_name'] . L_('或自提')
                    ),
                    array(
                        'id' => 4,
                        'name' => L_('商家配送或自提')
                    ),
                    array(
                        'id' => 5,
                        'name' => L_('快递配送')
                    )
                );
            } else {
                $deliver_types = array(
                    array(
                        'id' => 1,
                        'name' => L_('商家配送')
                    ),
                    array(
                        'id' => 2,
                        'name' => L_('客户自提')
                    ),
                    array(
                        'id' => 4,
                        'name' => L_('商家配送或自提')
                    ),
                    array(
                        'id' => 5,
                        'name' => L_('快递配送')
                    )
                );
                $store_shop['deliver_type'] = ($store_shop['deliver_type'] == 0 || $store_shop['deliver_type'] == 3) ? 1 : $store_shop['deliver_type'];
            }
            $this->assign('deliver_types', $deliver_types);
            $this->assign('store_shop', $store_shop);
            $this->assign('now_store', $now_store);
			//定制称码
            if($this->config['is_open_shop_weigh']==1){
                $this->assign('weigh_codes',M('Weigh_code')->where(array('store_id'=>$store_shop['store_id']))->select());
            }
			$condition['store_id']	= $now_store['store_id'];
			$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
            $subject_info['subject_pic'] = file_domain() .'/'. $subject_info['subject_pic'];
			$this->assign('subject_info',$subject_info);

            //店铺主页
            $viewStoreUrl = $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/shopDetail/shopDetail?store_id='.$now_store['store_id'];
            $this->assign('viewStoreUrl',$viewStoreUrl);
			$this->display();
		}
	}

	/**
	 * 商品分类
	 */
    public function goods_sort()
    {
        $fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
        $now_store = $this->check_store(intval($_GET['store_id']));
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
            //这里特殊处理一下level=2 ，operation_type=1或者0的分类，虽然分类下有商品（分类）但不展示商品管理（子分类）
            if ($value['level'] == 2 && ($value['operation_type'] == 1 || $value['operation_type'] == 0)) {
                $have_goods = D('Shop_goods')->where(['sort_id' => $value['sort_id']])->find();
                $have_sort = $shopGoodsSortDB->where(['fid' => $value['sort_id']])->find();
                if ($have_goods && $have_sort) {
                    $update = ['operation_type' => 2];
                    $value['operation_type'] = 2;
                } else if ($have_sort && $value['operation_type'] != 1) {
                    $update = ['operation_type' => 1];
                    $value['operation_type'] = 1;
                }
                $update && $shopGoodsSortDB->where(['sort_id' => $value['sort_id']])->save($update);
            }
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

	/**
	 * 商品分类新
	 */
    public function goods_sort_list()
    {
    	$store_id = $_GET['store_id'];
        $now_store = $this->check_store(intval($_GET['store_id']));
    	// 商品分类
		$first_sort_id = 0;
		$sort_list = D('Shop_goods_sort')->lists($_GET['store_id'], false,true);

    	$where=array('store_id' => $store_id);
        $g_list = M('Shop_goods')->field('sort_id,goods_id')->where($where)->order('sort DESC, goods_id ASC')->select();

        $newList = array();

        foreach ($g_list as $r) {
            if (isset($newList[$r['sort_id']])) {
                $newList[$r['sort_id']][] = $r;
            } else {
                $newList[$r['sort_id']] = array($r);
            }
        }
        $result = array();
        $index = 0;
		foreach ($sort_list as $l) {
            $temp = array();
            $temp['sort_id'] = $l['sort_id'];
            $temp['cat_id'] = $l['sort_id'];
            $temp['store_id'] = $l['store_id'];
            $temp['cat_name'] = $l['sort_name'];
            $temp['sort_name'] = $l['sort_name'];
            $temp['level'] = $l['level'];
            $temp['week'] = $l['week'];
            $temp['status'] = $l['status'];
            $temp['is_must'] = $l['is_must'];
            $temp['sort_status'] = $l['sort_status'];
            $temp['index'] = $index;
            $temp['findex'] = 0;
            $temp['fid'] = 0;
			$temp['operation_type'] = $l['operation_type'];

            if (isset($newList[$l['sort_id']]) && !$l['son_list']) {
                $temp['goods_count'] = count($newList[$l['sort_id']]);
                $temp['son_list'] = array();
            } elseif ($l['son_list']) {
                $temp['product_list'] = array();
                $son_list = $this->formatList($l['son_list'], $newList, $index, $l['sort_id']);
                if ($son_list) {
                    $temp['son_list'] = $son_list;
                } else {
                    $temp = null;
                }
            } else {
                $temp['goods_count'] = 0;
                $temp['son_list'] = array();
            }
            if (!empty($temp)) {
                $index ++;
                $result[] = $temp;
            }
        }
       	$sort_list_tmp = array();
		foreach ($result as $key => $value) {
            if ($value['week'] != null) {
                $week_arr = explode(',', $value['week']);
                $week_str = '';
                foreach ($week_arr as $k => $v) {
                    $week_str .= $this->get_week($v) . ' ';
                }
                $value['week_str'] = $week_str;
            }


			if ($value['son_list']) {
				$goods_count=0;
				foreach ($value['son_list'] as $son_k => $_son) {
					if ($_son['week'] != null) {
		                $week_arr = explode(',', $_son['week']);
		                $week_str = '';
		                foreach ($week_arr as $k => $v) {
		                    $week_str .= $this->get_week($v) . ' ';
		                }
		                $_son['week_str'] = $week_str;
		            }

					if ($_son['son_list']) {
						$goods_count1=0;
						foreach ($_son['son_list'] as $son_k2 => $_son2) {

							$goods_count1 += $_son2['goods_count'];
							if ($_son2['week'] != null) {
				                $week_arr = explode(',', $_son2['week']);
				                $week_str = '';
				                foreach ($week_arr as $k => $v) {
				                    $week_str .= $this->get_week($v) . ' ';
				                }
				                $_son2['week_str'] = $week_str;
				            }
							$_son['son_list'][$son_k2] = $_son2;

						};
						$_son['goods_count'] = $goods_count1;
					}
					$goods_count += $_son['goods_count'];

					$value['son_list'][$son_k] = $_son;
				}
				$value['goods_count'] = $goods_count;
			}
			$sort_list_tmp[$key] = $value;
		}
		$this->assign('sort_list', $sort_list_tmp);

		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);

        $this->display();
	}

	//修改展示区域
    public function area()
    {
        $goods_ids = isset($_GET['goods_ids']) ? $_GET['goods_ids'] : $_POST['goods_ids'];
        $discount = D('Shop_goods')->field(true)->where(array('goods_id' => array('in',$goods_ids),'cat_fid'=>array('gt', 0)))->select();
        if ($discount) {
            if (IS_POST) {
                $areaArray = $_POST['areaIds'];
                //if (!empty($data)) {
                $data = array();
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
                    }
                }
                $goods_ids=$_POST['goods_ids'];
               // }
                // 查询区域等级
                $area_list = D('Area')->where(array('area_id'=>array('in',array_keys($data))))->select();
                $area_level_arr = array();
                foreach ($area_list as $key => $value) {
                    $area_level_arr[$value['area_id']] = $value['area_type'];
                }

                $discountAreaDB = D('Shop_goods_area');
                $goodsDB = D('Shop_goods');
                //删除已经设置的区域
                //fdump($goods_ids,"dengyuanui3333",1);
                $discountAreaDB->where(array('did' => array('in',$goods_ids),'store_id'=>$_POST['store_id']))->delete();
                if(!empty($areaArray)){
                    foreach ($discount as $gk=>$gv){
                        $data1['use_area']=1;
                        $goodsDB->where(array('goods_id' => $gv['goods_id']))->save($data1);
                        foreach ($data as $aid => $level) {
                            $discountAreaDB->add(array('did' => $gv['goods_id'], 'aid' => $aid, 'level' => $area_level_arr[$aid], 'store_id' => $_POST['store_id'], 'set_type' => 0));
                        }
                    }
                }
                //删除已经设置的商家，但是不在新设置的区域内
                $this->success('设置成功',U('Mall/goods_list',array('store_id'=>$_POST['store_id'])));
            }
            else {
                $where = array('area_type' => array('lt', 4), 'is_open' => 1);
                if ($this->system_session['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $where['area_id'] = $this->system_session['area_id'];
                    }  else {
                        $where['area_pid'] = $this->system_session['area_id'];
                    }
                }
                //$where1 = array('store_id' =>$id, 'set_type' => 1);
                $areaList = D('Area')->field('area_id, area_pid, area_name')->where($where)->select();
                fdump($areaList,"dengyuanhui2112123",1);
                //$list = D('Shop_goods_area')->field('aid')->where($where1)->select();
                $p_sql="select aid from ".C('DB_PREFIX') ."shop_goods_area where (did in (".$goods_ids."))"." and store_id =".$_GET['store_id']." group by aid";
                $list=D('Shop_goods')->getArraid($p_sql);
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
                $this->assign('area_list', $list);
                $this->assign('goods_ids', $goods_ids);
                $this->display();
            }
        } else {
            $this->error_tips('此店铺无商品');
        }
    }

  	private function formatList($list, $newList, $findex, $fid, $has_goods=false)
    {
        if (empty($list)) {
            return $list;
        }
        $result = array();
        $index = 0;
        foreach ($list as $l) {
            $temp = array();
            $temp['cat_id'] = $l['sort_id'];
            $temp['sort_id'] = $l['sort_id'];
            $temp['store_id'] = $l['store_id'];
            $temp['level'] = $l['level'];
            $temp['week'] = $l['week'];
            $temp['index'] = $index;
            $temp['findex'] = $findex;
            $temp['fid'] = $fid;
            $temp['sort_name'] = $l['sort_name'];
			$temp['operation_type'] = $l['operation_type'];
            $temp['sort_status'] = $l['sort_status'];
            $temp['status'] = $l['status'];
            $temp['is_must'] = $l['is_must'];

            if (isset($newList[$l['sort_id']]) && !$l['son_list']) {
                $temp['goods_count'] = count($newList[$l['sort_id']]);
                $temp['son_list'] = array();
            } elseif ($l['son_list']) {
            	$temp['goods_count'] = 0;
                $son_list = $this->formatList($l['son_list'], $newList, $index, $l['sort_id'],$has_goods);
                if ($son_list) {
                    $temp['son_list'] = $son_list;
                } else {
                    $temp = null;
                }
            } else {
            	if ($has_goods) {
                    $temp = null;
            	}else{
	                $temp['goods_count'] = 0;
	                $temp['son_list'] = array();
            	}
            }
            if (!empty($temp)) {
                $index ++;
                $result[] = $temp;
            }
        }
        return $result;
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
	    $fid = isset($_REQUEST['fid']) ? intval($_REQUEST['fid']) : 0;
	    $now_store = $this->check_store(intval($_GET['store_id']));
        if ($now_store['tw_shop_id']) {
            $this->error("当前店铺属于图维门店，请到图维终端同步分类");
        }

	    if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find()) {
	        if ($sort['level'] == self::GOODS_SORT_LEVEL) {
	            $this->error($sort['sort_name'] . L_('分类下不能再增加子分类'), U('Shop/goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
	            exit;
	        }


			$ids = D('Shop_goods_sort')->getIds($fid, $now_store['store_id']);
			$this->assign('select_ids', json_encode($ids));

	    } else {
	        $fid = 0;
	        $sort = null;
	    }


		// 商品分类
		$sort_list = D('Shop_goods_sort')->lists($_GET['store_id'], false,true);
		$sort_list_tmp = array();
		foreach ($sort_list as $key => $value) {
			if ($value['operation_type']!=0) {

				$tmp = array();
				$tmp['sort_id'] = $value['sort_id'];
				$tmp['sort_name'] = $value['sort_name'];
				$tmp['operation_type'] = $value['operation_type'];
				if ($value['son_list']) {
					$value['son_list'] = array_merge([['sort_id'=>0,'sort_name'=>'请选择','operation_type'=>1]],$value['son_list']);
					foreach ($value['son_list'] as $son_k => $_son) {
						if ($_son['operation_type']!=0) {
							$_son_tmp = array();
							$_son_tmp['sort_id'] = $_son['sort_id'];
							$_son_tmp['sort_name'] = $_son['sort_name'];
							$_son_tmp['operation_type'] = $_son['operation_type'];

							// 不需要展示三级分类
							$_son_tmp['son_list'] = null;

							$tmp['son_list'][$son_k] = $_son_tmp;
						}
					};
				}
				$sort_list_tmp[$key] = $tmp;
			}
		}
		$sort_list_tmp = array_merge([0=>['sort_id'=>0,'sort_name'=>'请选择']],$sort_list_tmp);
		$this->assign('sort_list_json', str_replace('\t',' ',json_encode($sort_list_tmp)));

		if (IS_POST) {
			if (empty($_POST['sort_name'])) {
				$error_tips = L_('分类名称必填！').'<br/>';
			} else {
				$database_goods_sort = D('Shop_goods_sort');
				$data_goods_sort['store_id'] = $now_store['store_id'];
				$data_goods_sort['sort_name'] = htmlspecialchars(trim($_POST['sort_name']));
				$data_goods_sort['sort'] = intval($_POST['sort']);
				$data_goods_sort['sort_discount'] = intval(floatval($_POST['sort_discount']) * 10);
				$data_goods_sort['sort_discount'] = ($data_goods_sort['sort_discount'] > 100 || $data_goods_sort['sort_discount'] < 0) ? 0 : $data_goods_sort['sort_discount'];
				$data_goods_sort['status'] = intval($_POST['status']);
				$data_goods_sort['print_id'] = intval($_POST['print_id']);
				$data_goods_sort['level'] = 1;
				$data_goods_sort['desc'] = $_POST['desc'];
				$data_goods_sort['sort_status'] = intval($_POST['sort_status']);
                $data_goods_sort['category_limit_num'] = intval($_POST['category_limit_num']);

				// 修改父级分类
			    $sort_id_1 = $_REQUEST['sort_id_1'];
			    $sort_id_2 = $_REQUEST['sort_id_2'];
			    if ($sort_id_1&&!$sort_id_2) {
			    	$fid = $sort_id_1;
			    }elseif($sort_id_2){
			    	$fid = $sort_id_2;
			    }
		    	$sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find();

				$data_goods_sort['fid'] = $fid;
				if ($sort) {
				    $data_goods_sort['level'] = $sort['level'] + 1;
    				if (M('Shop_goods')->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find()) {
    				    $this->error($sort['sort_name'] . L_('分类下有归属商品了，不能给它增加子分类'), U('Shop/goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
    				    exit;
    				}
				}

				if ($data_goods_sort['level'] < self::GOODS_SORT_LEVEL) {
				    $data_goods_sort['operation_type'] = 2;
				} else {
				    $data_goods_sort['operation_type'] = 0;
				}
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

					$image = D('Image')->handle($this->merchant_session['mer_id'], 'goods_sort', 1, $param);

					if ($image['error']) {
						$error_tips .= $image['msg'] . '<br/>';
					} else {
						$_POST = array_merge($_POST, $image['title']);
					}
				}
				if(!empty($_POST['image_select'])){
					$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
					$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

					$tmp_img = explode(',',$_POST['image_select']);
					$_POST['image'] = $rand_num.','.$tmp_img[1];
				}
				$data_goods_sort['image'] = $_POST['image'] ?: '';

				//必选分类
                $data_goods_sort['is_must'] = isset($_POST['is_must']) ? $_POST['is_must'] : 0;
				if ($r = $database_goods_sort->data($data_goods_sort)->add()) {
                    if ($data_goods_sort['is_must'] == 1) {
                        $database_goods_sort->where(['store_id' => $now_store['store_id'], 'sort_id' => ['NEQ', $r]])->save(['is_must' => 0]);
                    }

					//多语言表修改数据
					$_POST['sort_id'] = $now_sort['sort_id'];
                	D('Lang')->add_lang_data('Shop_goods_sort',$_POST);
				    if ($sort && $sort['operation_type'] == 2) {
				        $database_goods_sort->where(array('sort_id' => $sort['sort_id']))->save(array('operation_type' => 1));
				    }
					$this->success(L_('添加成功！！'), U('Shop/goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
					die;
				} else {
				    echo $database_goods_sort->_sql();
					$this->error(L_('添加失败！！请重试。'), U('Shop/goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
					die;
				}
			}
		}

		$print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($print_list as &$l) {
		    if ($l['is_main']) {
		        $l['name'] .= '(主打印机)';
		    } else {
		        $l['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
		    }
		}
		$this->assign('sort', $sort);
		$this->assign('print_list', $print_list);
		$this->assign('now_store', $now_store);
		$this->assign('fid', $fid);
		$this->display();
	}


	/**
	 * 修改商品分类
	 */
	public function sort_edit()
	{
		$now_sort = $this->check_sort(intval($_GET['sort_id']));
		$now_store = $this->check_store($now_sort['store_id']);
        if ($now_store['tw_shop_id']) {
            $this->error("当前店铺属于图维门店，请到图维终端同步分类");
        }

	    $fid = isset($_REQUEST['fid']) ? intval($_REQUEST['fid']) : 0;
	    if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid))->find()) {
	        if ($sort['level'] == self::GOODS_SORT_LEVEL) {
	            $this->error($sort['sort_name'] . L_('分类下不能再增加子分类'), U('Shop/goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
	            exit;
	        }
			$ids = D('Shop_goods_sort')->getIds($fid, $now_store['store_id']);
			$this->assign('select_ids', json_encode($ids));
	    } else {
	        $fid = 0;
	        $sort = null;
	    }

	    // 商品分类
		$sort_list = D('Shop_goods_sort')->lists($now_store['store_id'], false,true);
		$sort_list_tmp = array();
		foreach ($sort_list as $key => $value) {
			if ($value['operation_type']!=0) {

				$tmp = array();
				$tmp['sort_id'] = $value['sort_id'];
				$tmp['sort_name'] = $value['sort_name'];
				$tmp['operation_type'] = $value['operation_type'];
				if ($value['son_list']) {
					$value['son_list'] = array_merge([['sort_id'=>0,'sort_name'=>'请选择','operation_type'=>1]],$value['son_list']);
					foreach ($value['son_list'] as $son_k => $_son) {
						if ($_son['operation_type']!=0) {
							$_son_tmp = array();
							$_son_tmp['sort_id'] = $_son['sort_id'];
							$_son_tmp['sort_name'] = $_son['sort_name'];
							$_son_tmp['operation_type'] = $_son['operation_type'];

							// 不需要展示三级分类
							$_son_tmp['son_list'] = null;

							$tmp['son_list'][$son_k] = $_son_tmp;
						}
					};
				}
				$sort_list_tmp[$key] = $tmp;
			}
		}
		$sort_list_tmp = array_merge([0=>['sort_id'=>0,'sort_name'=>'请选择']],$sort_list_tmp);
		$this->assign('sort_list_json', str_replace('\t',' ',json_encode($sort_list_tmp)));

		if (IS_POST) {
			if (empty($_POST['sort_name'])) {
				$error_tips = L_('分类名称必填！').'<br/>';
			} else {
				$database_goods_sort = D('Shop_goods_sort');
				$data_goods_sort['sort_id'] = $now_sort['sort_id'];
				$data_goods_sort['sort_name'] = trim($_POST['sort_name']);
				$data_goods_sort['sort'] = intval($_POST['sort']);
				$data_goods_sort['sort_discount'] = intval(floatval($_POST['sort_discount']) * 10);
				$data_goods_sort['sort_discount'] = ($data_goods_sort['sort_discount'] > 100 || $data_goods_sort['sort_discount'] < 0) ? 0 : $data_goods_sort['sort_discount'];
				$data_goods_sort['status'] = intval($_POST['status']);
				$data_goods_sort['week'] = implode(',',$_POST['week']);

				$data_goods_sort['print_id'] = intval($_POST['print_id']);
				$data_goods_sort['sort_status'] = intval($_POST['sort_status']);
				$data_goods_sort['desc'] = $_POST['desc'];
				$data_goods_sort['category_limit_num'] = intval($_POST['category_limit_num']);

				// 修改父级分类
			    $sort_id_1 = $_POST['sort_id_1'];
			    $sort_id_2 = $_POST['sort_id_2'];
			    if ($sort_id_1&&!$sort_id_2) {
			    	$fid = $sort_id_1;
			    }elseif($sort_id_2){
			    	$fid = $sort_id_2;
			    }else{
					$fid = 0;
				}
		    	$sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find();

				$data_goods_sort['fid'] = $fid;

				$data_goods_sort['level'] = 1;
				if ($sort) {
				    $data_goods_sort['level'] = $sort['level'] + 1;
    				if (M('Shop_goods')->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find()) {
    				    $this->error($sort['sort_name'] . L_('分类下有归属商品了，不能给它增加子分类'), U('Shop/goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
    				    exit;
    				}
				}

				if ($data_goods_sort['level'] < self::GOODS_SORT_LEVEL) {
				    $data_goods_sort['operation_type'] = 2;
				} else {
				    $data_goods_sort['operation_type'] = 0;
				}

				if (M('Shop_goods')->field(true)->where(array('sort_id' => $now_sort['sort_id'], 'store_id' => $now_store['store_id']))->find()) {
				    $data_goods_sort['operation_type'] = 0;
				}

				if($_FILES['image']['error'] != 4){
					$param = array('size' => $this->config['meal_pic_size']);
					$param['thumb'] = true;
					$param['imageClassPath'] = 'ORG.Util.Image';
					$param['thumbPrefix'] = 'm_,s_';
					$param['thumbMaxWidth'] = $this->config['meal_pic_width'];
					$param['thumbMaxHeight'] = $this->config['meal_pic_height'];
					$param['thumbRemoveOrigin'] = false;

					$image = D('Image')->handle($this->merchant_session['mer_id'], 'goods_sort', 1, $param);

					if ($image['error']) {
						$error_tips .= $image['msg'] . '<br/>';
					} else {
						$_POST = array_merge($_POST, $image['title']);
					}
				}
				if(!empty($_POST['image_select'])){
					$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
					$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

					$tmp_img = explode(',',$_POST['image_select']);
					$_POST['image']=$rand_num.','.$tmp_img[1];
				}
				if ($_POST['image']) {
					$data_goods_sort['image'] = $_POST['image'];
				}
                //必选分类
                $data_goods_sort['is_must'] = isset($_POST['is_must']) ? $_POST['is_must'] : 0;
				if ($database_goods_sort->data($data_goods_sort)->save()) {
                    if ($data_goods_sort['is_must'] == 1) {
                        $database_goods_sort->where(['store_id' => $now_sort['store_id'], 'sort_id' => ['NEQ', $now_sort['sort_id']]])->save(['is_must' => 0]);
                    }
					//多语言表修改数据
					$_POST['sort_id'] = $now_sort['sort_id'];
                	D('Lang')->edit_lang_data('Shop_goods_sort',$_POST);
				    if ($sort && $sort['operation_type'] == 2) {
				        $database_goods_sort->where(array('sort_id' => $sort['sort_id']))->save(array('operation_type' => 1));
				    }
					$this->success(L_('保存成功！！'), U('Shop/goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
					die;
				} else {
					$this->error(L_('保存失败！！您是不是没做过修改？请重试。'), U('Shop/goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
					die;
				}
			}
			$_POST['sort_id'] = $now_sort['sort_id'];
			$this->assign('now_sort', $_POST);
			$this->assign('ok_tips', $ok_tips);
			$this->assign('error_tips', $error_tips);
		}
		$now_sort['sort_discount'] *= 0.1;
		$this->assign('fid', $fid);
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
		$print_list = D('Orderprinter')->where(array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']))->select();
		foreach ($print_list as &$l) {
		    if ($l['is_main']) {
		        $l['name'] .= '(主打印机)';
		    } else {
		        $l['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
		    }
		}
		$this->assign('sort', $sort);
		$this->assign('print_list', $print_list);
		$this->display();
	}

	/* 删除分类 */
	public function sort_del()
	{
		$now_sort = $this->check_sort($_GET['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);

		$count = D('Shop_goods')->where(array('sort_id' => $now_sort['sort_id'], 'store_id' => $now_sort['store_id']))->count();
		if ($count) $this->error(L_('该分类下有商品，先删除商品后再来删除该分类'));

		$sortCount = D('Shop_goods_sort')->where(array('fid' => $now_sort['sort_id'], 'store_id' => $now_sort['store_id']))->count();
		if ($sortCount) $this->error(L_('该分类下有子分类，先删除子分类后再来删除该分类'));

		$database_goods_sort = D('Shop_goods_sort');
		$condition_goods_sort['sort_id'] = $now_sort['sort_id'];
		if ($database_goods_sort->where($condition_goods_sort)->delete()) {
			// 查看上级 如若上级分类下没有子类分，则将上级分类改为可添加子分类和商品两种都支持的
			if ($now_sort['fid']) {//存在上级分类
				// 查找子分类
				$condition_sort = [];
				$condition_sort['fid'] = $now_sort['fid'];
				if (!$son_sort = $database_goods_sort->where($condition_sort)->find()) {
					//上级分类下没有子分类，修改状态
					$condition_sort = [];
					$condition_sort['sort_id'] = $now_sort['fid'];
					//修改状态
					$database_goods_sort->where($condition_sort)->setField('operation_type',2);
				}
			}

			$this->success(L_('删除成功！'));
		} else {
			$this->error(L_('删除失败！'));
		}
	}
	/* 分类页商品筛选 */
	public function sort_goods_search(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);

		$database_goods = D(_view('Shop_goods'));
		$condition_goods['store_id'] = $now_store['store_id'];
		/*增加搜索类型*/
		$search_type=I('get.search_type','','trim');
		if($_GET['keyword']){
			if($search_type=='number'){
				$condition_goods['_string']="number like '%".$_GET['keyword']."%' or locate('".$_GET['keyword']."',spec_value)>0";
			}else{
				$condition_goods[_field('name')] = array('like','%'.$_GET['keyword'].'%');
			}
		}else{
			$this->error(L_('请输入搜索关键词'));
		}

		$count_goods = $database_goods->where($condition_goods)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count_goods, 20);
		$goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();

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
		foreach ($goods_list as &$rl) {
			$rl['print_name'] = isset($plist[$rl['print_id']]['name']) ? $plist[$rl['print_id']]['name'] : '';
			if ($rl['sell_day'] != $today) {
			    $rl['today_sell_count'] = 0;
			    //$rl['stock_num'] = $rl['original_stock'];
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
			    /*if ($now_store['stock_type'] == 1) {
			        $rl['stock_num_t'] = max(0, $rl['stock_num'] - $rl['sell_count']);
			    } else {
			        $rl['stock_num_t'] = max(0, $rl['stock_num'] - $rl['today_sell_count']);
			    }*/
			}
		}

		$this->assign('goods_list', $goods_list);
		$this->assign('pagebar', $p->show());

		$this->display();
	}

	/* 菜品管理 */
	public function goods_list()
	{
		if ($_GET['sort_id']) {
			$now_sort = $this->check_sort($_GET['sort_id']);
		}
		
		$pifa_hidden = 1;
		//获取商家信息
		$merchant_info = D('Merchant')->where(['mer_id'=>$this->mer_id])->find();
		if($merchant_info['menus']){
			$menus = explode(',',$merchant_info['menus']);
			if(in_array(10008,$menus)){
				$pifa_hidden = 0;
			}
			$this->assign('mer_id', $this->mer_id);
		}
		$this->assign('pifa_hidden',$pifa_hidden);

		$f_sort = D(_view('Shop_goods_sort'))->field(true)->where(['sort_id'=>$now_sort['fid']])->find();
		if ($f_sort) {
			$ff_sort = D(_view('Shop_goods_sort'))->field(true)->where(['sort_id'=>$f_sort['fid']])->find();
			if (!$ff_sort) {
				$ff_sort['sort_id'] = $f_sort['sort_id'];
				$f_sort['sort_id'] = $_GET['sort_id'];
			}
		}else{
			$f_sort['sort_id'] = $_GET['sort_id'];
			$ff_sort['sort_id'] = $_GET['sort_id'];
		}

		// 商品分类
		$sort_list = D('Shop_goods_sort')->lists($_GET['store_id'], false,true);
        
		if (isset($_GET['status']) || isset($_GET['stock_num']) || isset($_GET['single_no_delivery'])) {
			if (isset($_GET['status'])) {
				$condition_goods['status'] = $_GET['status'];
				$condition_goods_all['status'] = $_GET['status'];
			}

			if (isset($_GET['stock_num'])) {
				$condition_goods['stock_num'] = $_GET['stock_num'];
				$condition_goods_all['stock_num'] = $_GET['stock_num'];
			}

            if (isset($_GET['single_no_delivery'])) {
                $condition_goods['single_no_delivery'] = $_GET['single_no_delivery'];
                $condition_goods_all['single_no_delivery'] = $_GET['single_no_delivery'];
            }

			// 条件查询去掉没有商品的分类
			$goods_list_all = M('Shop_goods')->field('goods_id,sort_id')->where($condition_goods_all)->group('sort_id')->select();
			$goods_list_all = array_column($goods_list_all, 'goods_id','sort_id');

			$result = array();
	        $index = 0;
			foreach ($sort_list as $l) {
	            $temp = array();
	            $temp['sort_id'] = $l['sort_id'];
	            $temp['cat_id'] = $l['sort_id'];
	            $temp['store_id'] = $l['store_id'];
	            $temp['cat_name'] = $l['sort_name'];
	            $temp['sort_name'] = $l['sort_name'];
	            $temp['level'] = $l['level'];
	            $temp['week'] = $l['week'];
	            $temp['index'] = $index;
	            $temp['findex'] = 0;
	            $temp['fid'] = 0;
				$temp['operation_type'] = $l['operation_type'];

	            if (isset($goods_list_all[$l['sort_id']]) && empty($l['son_list'])) {
	                $temp['goods_count'] = count($goods_list_all[$l['sort_id']]);
	                $temp['son_list'] = array();
	            } elseif ($l['son_list']) {
	                $temp['product_list'] = array();
	                $son_list = $this->formatList($l['son_list'], $goods_list_all, $index, $l['sort_id'],true);
	                if ($son_list) {
	                    $temp['son_list'] = $son_list;
	                } else {
	                    $temp = null;
	                }
	            } else {
	                $temp = null;
	            }
	            if (!empty($temp)) {
	                $index ++;
	                $result[] = $temp;
	            }
	        }
			$sort_list = $result;
		}


		$first_sort_id = 0;
		$sort_list_tmp = array();
		$i=0;
		foreach ($sort_list as $key => $value) {
			$tmp = array();
			$tmp['sort_id'] = $value['sort_id'];
			$tmp['sort_name'] = $value['sort_name'];
			$tmp['operation_type'] = $value['operation_type'];
			if ($value['sort_id']==$ff_sort['sort_id']) {
				$tmp['selected'] = 1;
			}else{
				$tmp['selected'] = 0;
			}
			if(!$_GET['sort_id']&&$i==0){
				$first_sort_id = $tmp['sort_id'];
				$tmp['selected'] = 1;
			}
			if ($value['son_list']) {
				$tmp['operation_type'] = 1;
				$j=0;
				foreach ($value['son_list'] as $son_k => $_son) {
					$_son_tmp = array();
					$_son_tmp['sort_id'] = $_son['sort_id'];
					$_son_tmp['sort_name'] = $_son['sort_name'];
					$_son_tmp['operation_type'] = $_son['operation_type'];

					if ($_son['sort_id']==$f_sort['sort_id']) {
						$_son_tmp['selected'] = 1;
						$tmp['selected'] = 1;
						$tmp['child_selected'] = 1;
					}else{
						$_son_tmp['selected'] = 0;
					}
					if(!$_GET['sort_id']&&$i==0&&$j==0&&$_son['operation_type']!=1){
						$first_sort_id = $_son['sort_id'];
						$_son_tmp['selected'] = 1;
						$tmp['selected'] = 1;
						$tmp['child_selected'] = 1;
					}

					if ($_son['son_list']) {
						$_son_tmp['operation_type'] = 1;
						$k=0;
						foreach ($_son['son_list'] as $son_k2 => $_son2) {
							$_son_tmp2 = array();
							$_son_tmp2['sort_id'] = $_son2['sort_id'];
							$_son_tmp2['sort_name'] = $_son2['sort_name'];
							$_son_tmp2['operation_type'] = $_son2['operation_type'];

							if ($_son2['sort_id']==$now_sort['sort_id']) {
								$_son_tmp2['selected'] = 1;
								$_son_tmp['selected'] = 1;
								$_son_tmp['child_selected'] = 1;
								$tmp['selected'] = 1;
								$tmp['child_selected'] = 1;
							}else{
								$_son_tmp2['selected'] = 0;
							}
							if(!$_GET['sort_id']&&$i==0&&$j==0&&$k==0&&$_son2['operation_type']==0){
								$first_sort_id = $_son2['sort_id'];
								$_son_tmp2['selected'] = 1;
								$_son_tmp['selected'] = 1;
								$_son_tmp['child_selected'] = 1;
								$tmp['selected'] = 1;
								$tmp['child_selected'] = 1;
							}
							$k++;
							$_son['son_list'][$son_k2] = $_son_tmp2;
						};
						$_son_tmp['son_list'] = $_son['son_list'];
					}
					$j++;
					$tmp['son_list'][$son_k] = $_son_tmp;
				};
			}
			$i++;
			$sort_list_tmp[$key] = $tmp;
		}
		$this->assign('sort_list', $sort_list_tmp);
		$sort_list_tmp_json =  json_encode($sort_list_tmp,JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE|JSON_HEX_APOS);
		$this->assign('sort_list_json',str_replace('\t',' ',$sort_list_tmp_json));

		if (!$_GET['sort_id'] && $first_sort_id) {
			$_GET['sort_id'] = $first_sort_id;
			$now_sort = $this->check_sort($_GET['sort_id']);
		}

		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
		$open_from_shop = D('Merchant_store_foodshop')->where(['store_id'=>$now_sort['store_id']])->getField('open_from_shop');
		$this->assign("open_from_shop",$open_from_shop);
		$database_goods = D(_view('Shop_goods'));
		$condition_goods['sort_id'] = $now_sort['sort_id'];
		$condition_foodshop_goods['s.sort_id'] = $now_sort['sort_id'];



		/*增加搜索类型*/
		// $search_type=I('get.search_type','','trim');
		// if($_GET['keyword']){
		// 	if($search_type=='number'){
		// 		$condition_goods['_string']="number like '%".$_GET['keyword']."%' or locate('".$_GET['keyword']."',spec_value)>0";
		// 	}else{
		// 		$condition_goods[_field('name')] = array('like','%'.$_GET['keyword'].'%');
		// 	}
		// }
    //     if($_GET['sell_type']){
    //         if($_GET['sell_type']==1){
    //             $condition_goods['sell_type'] = 1;
				// $condition_foodshop_goods['s.sell_type'] = 1;
    //         }elseif($_GET['sell_type']==2){
    //             $condition_goods['sell_type'] = 2;
				// $condition_foodshop_goods['s.sell_type'] = 2;
    //         }
    //     }
		/*筛选商品类型*/
		// $shop_type=I('shop_type',0,'intval');
		// if($shop_type==1){
		// 	$condition_goods['goods_type']=array('neq',1);
		// 	$condition_foodshop_goods['s.goods_type'] = array('neq',1);
		// }elseif($shop_type==2){
		// 	$condition_goods['cat_fid']=array('gt',0);
		// 	$condition_foodshop_goods['s.cat_fid'] = array('gt',0);
		// }
		$count_goods = $database_goods->where($condition_goods)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count_goods, 30);
		$goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
		if (isset($_GET['stock_num'])) {
			$condition_goods['stock_num'] = $_GET['stock_num'];
		}
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

		// 查询已同步餐饮商品
        $goods_arr = M('Foodshop_goods_new')->field('f.goods_id')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where($condition_foodshop_goods)->select();
        $goods_id_arr = array();
        foreach ($goods_arr as $key => $value) {
            $goods_id_arr[] = $value['goods_id'];
        }

		foreach ($goods_list as &$rl) {
			if (in_array($rl['goods_id'], $goods_id_arr)) {
                $rl['has_foodshop_goods'] = 1;
            }

			$rl['print_name'] = isset($plist[$rl['print_id']]['name']) ? $plist[$rl['print_id']]['name'] : '';
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
		}
        // 发布批发市场权限控制
        $this->assign('is_publish_market_goods', $this->merchant_session['is_publish_market_goods']?$this->merchant_session['is_publish_market_goods']:0);
		// 商品统计
		$where_count['store_id'] = $now_store['store_id'];
		// 全部商品
		$all_goods_total = M('Shop_goods')->where($where_count)->count();
		$this->assign('all_goods_total', $all_goods_total);

		// 售卖中
		$where_count['status'] = 1;
		$sell_goods_total = M('Shop_goods')->where($where_count)->count();
		$this->assign('sell_goods_total', $sell_goods_total);

		// 已下架
		$where_count['status'] = 0;
		$undercarriage_goods_total = M('Shop_goods')->where($where_count)->count();
		$this->assign('undercarriage_goods_total', $undercarriage_goods_total);

		// 已售完
		unset($where_count['status']);
		$where_count['stock_num'] = 0;
		$sellout_goods_total = M('Shop_goods')->where($where_count)->count();
		$this->assign('sellout_goods_total', $sellout_goods_total);

        //单点不送
        unset($where_count['status'],$where_count['stock_num']);
		$where_count['single_no_delivery'] = 1;
        $single_goods_total = M('Shop_goods')->where($where_count)->count();
		$this->assign('single_goods_total', $single_goods_total);

		$this->assign('merchant', $this->merchant_session);
		$this->assign('audit_status',3);
		$this->assign('goods_list', $goods_list);
		$this->assign('pagebar', $p->show());
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
        $audit_status = $_GET['audit_status']??3;
        if(in_array($audit_status,array(0,1,2))){
            $condition_goods['audit_status'] = $audit_status;
        }

        if (isset($_GET['single_no_delivery'])) {
            $condition_goods['single_no_delivery'] = $_GET['single_no_delivery'];
        }

		$condition_goods['sort_id'] = $now_sort['sort_id'];
		$condition_foodshop_goods['s.sort_id'] = $now_sort['sort_id'];
		$database_goods = M('Shop_goods');
		$count_goods = $database_goods->where($condition_goods)->count();
        import('@.ORG.diypage');
		$p = new Page($count_goods, 30);

		$goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();

		$today = date('Ymd');
		$now_time = time();

		// 查询已同步餐饮商品
        $goods_arr = M('Foodshop_goods_new')->field('f.goods_id')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where($condition_foodshop_goods)->select();
        $goods_id_arr = array();
        foreach ($goods_arr as $key => $value) {
            $goods_id_arr[] = $value['goods_id'];
        }

        $goods_image_class = new goods_image();
        $audit_name = array('未审核','已审核','审核失败');

		foreach ($goods_list as &$rl) {
            $rl['audit_content'] = $rl['audit_content']?:'';
            $rl['audit_name'] = $audit_name[$rl['audit_status']]??'';
			if (in_array($rl['goods_id'], $goods_id_arr)) {
                $rl['has_foodshop_goods'] = 1;
            }

			$rl['print_name'] = isset($plist[$rl['print_id']]['name']) ? $plist[$rl['print_id']]['name'] : '';
			if ($rl['sell_day'] != $today) {
			    $rl['today_sell_count'] = 0;
			}

            //重置库存
            $twStock = D('Shop_goods')->queryTwGoodsStock($rl['goods_id']);
            if (!is_null($twStock)) {
                $rl['stock_num'] = getFormatNumber($twStock);
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
			$rl['last_time'] = date('Y-m-d H:i:s',$rl['last_time']);

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

        // 发布批发市场权限控制
        $is_publish_market_goods = $this->merchant_session['is_publish_market_goods']?$this->merchant_session['is_publish_market_goods']:0;

        $merchant_menu = M('New_merchant_menu')->where(['id'=>'10008'])->find();
        $is_fabu = $merchant_menu ? 1 : 0;
		exit(json_encode(array('goods_list'=>$goods_list,'page'=>$p->show(),'is_publish_market_goods'=>$is_publish_market_goods,'is_fabu'=>$is_fabu)));
	}

	// 修改商品排序
	public function goods_sort_edit(){
		$goods_id = $_POST['id'];
		$sort = $_POST['sort'];
		$res = M('Shop_goods')->where(['goods_id'=>$goods_id])->save(['sort'=>$sort]);
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
	    $audit_status = $_GET['audit_status']??3;
		// 商品分类
		$sort_list = D('Shop_goods_sort')->lists($_GET['store_id'], false,true);
		$sort_list_tmp = array();
		foreach ($sort_list as $key => $value) {
			$tmp = array();
			$tmp['sort_id'] = $value['sort_id'];
			$tmp['sort_name'] = $value['sort_name'];
			$tmp['operation_type'] = $value['operation_type'];
			if ($value['son_list']) {
				$tmp['operation_type'] = 1;
				foreach ($value['son_list'] as $son_k => $_son) {
					$_son_tmp = array();
					$_son_tmp['sort_id'] = $_son['sort_id'];
					$_son_tmp['sort_name'] = $_son['sort_name'];
					$_son_tmp['operation_type'] = $_son['operation_type'];
					if ($_son['son_list']) {
						$_son_tmp['operation_type'] = 1;
						foreach ($_son['son_list'] as $son_k2 => $_son2) {
							$_son_tmp2 = array();
							$_son_tmp2['sort_id'] = $_son2['sort_id'];
							$_son_tmp2['sort_name'] = $_son2['sort_name'];
							$_son_tmp2['operation_type'] = $_son2['operation_type'];
							$_son['son_list'][$son_k2] = $_son_tmp2;
						};
						$_son_tmp['son_list'] = $_son['son_list'];
					}
					$tmp['son_list'][$son_k] = $_son_tmp;
				};
			}
			$sort_list_tmp[$key] = $tmp;
		}
		$this->assign('sort_list', $sort_list_tmp);
		$this->assign('sort_list_json', str_replace('\t',' ',json_encode($sort_list_tmp)));

		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);

		$open_from_shop = D('Merchant_store_foodshop')->where(['store_id'=>$_GET['store_id']])->getField('open_from_shop');
		$this->assign("open_from_shop",$open_from_shop);

		$database_goods = D(_view('Shop_goods'));
		$condition_goods['store_id'] = $_GET['store_id'];
		$condition_foodshop_goods['s.store_id'] = $_GET['store_id'];

		$search_type=I('get.search_type','','trim');
		if($_GET['keyword']){
			if($search_type=='number'){
				$condition_goods['_string']="number like '%".$_GET['keyword']."%' ";
			}else{
				$condition_goods[_field('name')] = array('like','%'.$_GET['keyword'].'%');
			}
		}

        if(in_array($audit_status,array(0,1,2))){
            $condition_goods['audit_status'] = $audit_status;
        }

		$count_goods = $database_goods->where($condition_goods)->count();
		$this->assign("count_goods",$count_goods);

		$goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->select();
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

		// 查询已同步餐饮商品
        $goods_arr = M('Foodshop_goods_new')->field('f.goods_id')->join('as f join '.C('DB_PREFIX').'shop_goods as s on f.goods_id = s.goods_id')->where($condition_foodshop_goods)->select();
        $goods_id_arr = array();
        foreach ($goods_arr as $key => $value) {
            $goods_id_arr[] = $value['goods_id'];
        }

        $audit_name = array('未审核','已审核','审核失败');
        $goods_image_class = new goods_image();
		foreach ($goods_list as &$rl) {
            $rl['audit_name'] = $audit_name[$rl['audit_status']]??'';
			if (in_array($rl['goods_id'], $goods_id_arr)) {
                $rl['has_foodshop_goods'] = 1;
            }

			$rl['print_name'] = isset($plist[$rl['print_id']]['name']) ? $plist[$rl['print_id']]['name'] : '';
			if ($rl['sell_day'] != $today) {
			    $rl['today_sell_count'] = 0;
			}
			$rl['seckill_status'] = false;
            //重置库存
            $twStock = D('Shop_goods')->queryTwGoodsStock($rl['goods_id']);
            if (!is_null($twStock)) {
                $rl['stock_num'] = getFormatNumber($twStock);
            }

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
                $newList[$r['sort_id']]['sort_name'] = D('Shop_goods_sort')->where(['sort_id'=>$r['sort_id']])->getField('sort_name');
                $newList[$r['sort_id']]['product_list'] = array($r);
            }
        }
		$this->assign('merchant', $this->merchant_session);
        $this->assign('audit_status',$audit_status);
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

		// 限时优惠
		if ($now_goods['seckill_type'] == 1) {
            $now_goods['open_time_formart'] = date('H:i', $now_goods['seckill_open_time']);
            $now_goods['close_time_formart'] = date('H:i', $now_goods['seckill_close_time']);
        } else {
            $now_goods['open_time_formart']  = date('Y-m-d H:i:s',$now_goods['seckill_open_time']);
            $now_goods['close_time_formart'] = date('Y-m-d H:i:s',$now_goods['seckill_close_time']);
        }

		// 附属菜
		$piece_list = M(_view('Shop_subsidiary_piece'))->where("is_delete = 0 AND goods_id = " . $goods_id)->order('id ASC')->select();
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
		$return = array('status' => 1, 'data' => $now_goods);
		$this->ajaxReturn($return,'JSON');
	}

	//更新商品信息
	public function reset_goods_info(){
		$goods_id = I('goods_id');
		if(empty($goods_id)){
			$return = array('status' => 2, 'msg' => L_('参数错误'), 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}
		$now_goods = $this->check_goods($goods_id);
		if(empty($now_goods)){
			$return = array('status' => 2, 'msg' => L_('没有找到该商品'), 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}
		$stock_num = I('stock_num');//等于full时表示要更改跟原始库存一样的值
		$original_stock = I('original_stock');
		if(isset($_POST['original_stock'])){
			if($stock_num == $now_goods['stock_num'] && $original_stock == $now_goods['original_stock']){
				$return = array('status' => 2, 'msg' => L_('无变动'), 'data' => array());
				$this->ajaxReturn($return,'JSON');
			}
		}
		else{
			if($stock_num == $now_goods['stock_num']){
				$return = array('status' => 2, 'msg' => L_('无变动'), 'data' => array());
				$this->ajaxReturn($return,'JSON');
			}
		}
		$database_goods = D('Shop_goods');
		if((isset($_POST['original_stock']) && $original_stock === '') || $stock_num === ''){
			$return = array('status' => 2, 'msg' => L_('商品库存必填'), 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}
		if($stock_num != 'full' && $stock_num >= 0 && (($now_goods['seckill_stock'] > 0 && $now_goods['seckill_stock'] > $stock_num) || $now_goods['seckill_stock'] == -1)){
			$return = array('status' => 2, 'msg' => L_('限时价库存不能大于商品库存！如果不做限时活动，请将限时价库存设置为0'), 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}
		if(isset($_POST['original_stock'])){
			if(($original_stock != -1 && $stock_num > $original_stock) || ($stock_num == -1 && $original_stock != -1)){
				$return = array('status' => 2, 'msg' => L_('当前库存不能大于原始库存！'), 'data' => array());
				$this->ajaxReturn($return,'JSON');
			}
		}
		else{
			if(($now_goods['original_stock'] != -1 && $stock_num != 'full' && $stock_num > $now_goods['original_stock']) || ($stock_num == -1 && $now_goods['original_stock'] != -1)){
				$return = array('status' => 2, 'msg' => L_('当前库存不能大于原始库存！'), 'data' => array());
				$this->ajaxReturn($return,'JSON');
			}
		}
		$update = array();
		$update['stock_num']= $update['stock_weight'] = $stock_num == 'full' ? $now_goods['original_stock'] : $stock_num;
		$update['sell_day'] = date('Ymd');
		if(isset($_POST['original_stock'])){
			$update['original_stock'] = $original_stock;
            $update['original_weight'] = $original_stock;
		}
		if($database_goods->where('goods_id = '.$goods_id)->data($update)->save()){
			$return = array('status' => 1, 'msg' => L_('库存更新成功'), 'data' => array('update' => $update['stock_num']));
			$this->ajaxReturn($return,'JSON');
		}
		else{
			$return = array('status' => 2, 'msg' => L_('库存更新失败'), 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}
	}

	//更新商品信息 批量
	// 1-沽清，2-置满，3-修改库存，4-上架，5-下架，6-删除，7-修改分类，8-修改售卖时间，9-修改商品描述，10-修改最小购买量和单位，11-设置打包费
	public function reset_goods_info_batch(){
		$goods_id = I('goods_id');

		$stock_num = I('stock_num');//等于full时表示要更改跟原始库存一样的值
		$original_stock = I('original_stock');
		$type = I('type'); //修改类型

		$database_goods = D('Shop_goods');

		if(empty($goods_id)||empty($type)){
			$return = array('status' => 2, 'msg' => L_('缺少参数'), 'data' => array());
			$this->ajaxReturn($return,'JSON');
		}

		// 商品列表
		$goods_list = M('Shop_goods')->where(['goods_id'=>['in',$goods_id]])->select();
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
                    $update['stock_weight'] = 0;
					$update['sell_day'] = date('Ymd');
					break;
				case '2'://置满
					$fill_up_batch = [
						'goods_id' => $now_goods['goods_id'],
						'stock_num' => $now_goods['original_stock'],
                        'stock_weight' => $now_goods['original_weight'],
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
                    $update['stock_weight'] = $stock_num;
					$update['original_stock'] = $original_stock;
                    $update['original_weight'] = $original_stock;
					$update['sell_day'] = date('Ymd');

					if(($original_stock != -1 && $stock_num > $original_stock) || ($stock_num == -1 && $original_stock != -1)){
						$return = array('status' => 2, 'msg' => L_('当前库存不能大于原始库存！'), 'data' => array());
						$this->ajaxReturn($return,'JSON');
					}

					if($stock_num >= 0 && (($now_goods['seckill_stock'] > 0 && $now_goods['seckill_stock'] > $stock_num) || $now_goods['seckill_stock'] == -1)){
						$return = array('status' => 2, 'msg' => L_('商品X1限时价库存不能大于商品库存！如果不做限时活动，请将限时价库存设置为0',array('X1'=>$now_goods['name'])), 'data' => array());
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
					// $sort_id = $now_goods['sort_id'];
					for ($i = 1; $i <= self::GOODS_SORT_LEVEL; $i++) {
					    if (isset($_POST['sort_id_' . $i]) && intval($_POST['sort_id_' . $i])) {
					        $sort_id = intval($_POST['sort_id_' . $i]);
					        unset($_POST['sort_id_' . $i]);
					    }
					}
					$shopGoodsSortDB = M('Shop_goods_sort');
					if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sort_id, 'store_id' => $store_id))->find()) {
					    if ($fsort = M(_view('Shop_goods_sort'))->field(true)->where(array('fid' => $sort_id, 'store_id' => $store_id))->find()) {
							$return = array('status' => 2, 'msg' => L_('该分类有子分类，不能直接添加商品'), 'data' => array());
							$this->ajaxReturn($return,'JSON');
					    }
					} else {
						$return = array('status' => 2, 'msg' => L_('商品分类不存在'), 'data' => array());
						$this->ajaxReturn($return,'JSON');
					}
					$update['sort_id'] = $sort_id;

					break;
				case '8':
					$update['show_start_time'] = isset($_POST['show_start_time']) ? $_POST['show_start_time'] : '00:00:00';
					$update['show_end_time'] = isset($_POST['show_end_time']) ? $_POST['show_end_time'] : '00:00:00';
					break;
				case '9':
					$update['describe'] = $_POST['describe'];
					break;
				case '10':
					$update['min_num'] = $_POST['min_num'];
					$update['unit'] = $_POST['unit'];
					if (!$_POST['unit']) {
						$return = array('status' => 2, 'msg' => L_('商品单位不能为空'), 'data' => array());
						$this->ajaxReturn($return,'JSON');
					}

					break;
				case '11':
					$update['packing_charge'] = $_POST['packing_charge'];
					break;
                case '12': //单点不送
                    $update['single_no_delivery'] = $_POST['single_no_delivery'];
                    break;
			}
		}
		if ($type==6) {
			// 删除
			if($database_goods->where(['goods_id'=>['in',$goods_id]])->delete()!==false){
				//规格表
				$old_spec = M('Shop_goods_spec')->field(true)->where(array('goods_id' => ['in',$goods_id], 'store_id' => $store_id))->select();
				foreach ($old_spec as $os) {
					$delete_spec_ids[] = $os['id'];
				}
				// 删除规格
				M('Shop_goods_spec')->where(array('goods_id' => ['in',$goods_id], 'store_id' => $store_id))->delete();
				if ($delete_spec_ids) {
					$old_spec_val = M('Shop_goods_spec_value')->where(array('sid' => array('in', $delete_spec_ids)))->delete();
				}

				// 删除属性
				M('Shop_goods_properties')->where(array('goods_id' => ['in',$goods_id]))->delete();

				// 删除缓存
				S('shop_goods_by_storeid_' . $store_id,null);

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
					// 同步修改餐饮状态
					if ($_POST['is_sync']==1) {
						M('Foodshop_goods_new')->where(['goods_id'=>['in',$goods_id]])->data($update)->save();
					}
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

	//添加商品
	public function goods_add()
	{
		if ($_GET['sort_id']) {
			$now_sort = $this->check_sort(intval($_GET['sort_id']));
		}
		$now_store = $this->check_store($_GET['store_id']);
        if ($now_store['tw_shop_id']) {
            $this->error("当前店铺属于图维门店，请到图维终端同步商品");
        }
        // 辣度
        $spicy_arr =  D('Shop_goods')->spicy_arr();
       	$this->assign('spicy_arr',$spicy_arr);
        if (IS_POST) {
            $error_tips = '';
            if (C('customization.life_tools')) {
                if ($_POST['open_employee_card'] == 1 && (empty($_POST['employee_mer_id']) || empty($_POST['employee_lables']))) {
                    $error_tips .= L_('至少选择一个员工标签（如果此商家没有标签，请去通知此商家给员工设置身份标签）！') . '<br/>';
                }
                $_POST['employee_lables'] = !empty($_POST['employee_lables']) ? implode(',', $_POST['employee_lables']) : '';
            }
            if (!empty($_POST['sysname']) && (empty($_POST['name']) || empty($_POST['unit']) || empty($_POST['price']) || $_POST['pic'])) {
                $number = htmlspecialchars(trim($_POST['sysname']));
                $systemGoods = D('System_goods');
                $condition = array();

                if ($number) {
                    $condition['number'] = $number;
                }
                if (empty($condition)) {
                    $this->error(L_('请输入查询条件'));
                }
                $now_goods = $systemGoods->field(true)->where($condition)->find();
                if (empty($now_goods)) {
                    $this->error(L_('商品不存在'));
                }
                $_POST['name'] = $_POST['name'] ? htmlspecialchars_decode($_POST['name']) : $now_goods['name'];
                $_POST['unit'] = $_POST['unit'] ? $_POST['unit'] : $now_goods['unit'];
                $_POST['price'] = isset($_POST['price']) ? $_POST['price'] : $now_goods['price'];
                $pics = array();
                if(!empty($now_goods['image'])){
                    $goods_image_class = new goods_image();
                    $tmp_pic_arr = explode(';', $now_goods['image']);
                    foreach ($tmp_pic_arr as $key => $value) {
                        $pics[] = $value;
                    }
                }
            }

            if(!empty($_POST['shop_video_url'])){
            	$_POST['shop_video_preview'] = '/'.$_POST['shop_video_url'].'.png';
            }

            //处理选择图片链接存在逗号隔开的
            foreach ($_POST['pic'] as $k => $v) {
                if (false !== strpos($v, ',')) {
                    $splistArr = explode(',', $v);
                    $_POST['pic'][$k] = (strpos($v,'upload')===false ? '/upload/goods/' : '') . $splistArr[0] . '/' . $splistArr['1'];
                }
            }
            $_POST['pic'] = $_POST['pic'] ? $_POST['pic'] : $pics;
            if (empty($_POST['name'])) {
                $error_tips .= L_('商品名称必填！') . '<br/>';
            }
            if (empty($_POST['unit'])) {
                $error_tips .= L_('商品单位必填！') . '<br/>';
            }
            if ($_POST['price'] === '' && !$this->config['open_extra_price']) {
                $error_tips .= L_('商品价格可以设置为0，但是必填！') . '<br/>';
            }
            if ($_POST['price'] < 0 && !$this->config['open_extra_price']) {
                $error_tips .= L_('商品价格必须大于或等于0！') . '<br/>';
            }
            if (empty($_POST['pic'])) {
                if (!C('config.is_open_goods_default_image')) {
                    $error_tips .= L_('请至少上传一张照片！') . '<br/>';
                }
            }
            if ($_POST['stock_num'] >= 0 && (($_POST['seckill_stock'] > 0 && $_POST['seckill_stock'] > $_POST['stock_num']) || $_POST['seckill_stock'] == -1)) {
                $error_tips .= L_('限时价库存不能大于商品库存！如果不做限时活动，请将限时价库存设置为0') . '<br/>';
            }
            if($_POST['sell_type']==2){ //计重
                if (strlen($_POST['number'])>5) {
                    $error_tips .= L_('计重商品条码请不要大于5位').'<br/>';
                }
                if (empty($_POST['unit_price'])) {
                    $error_tips .= L_('商品单价不能为空').'<br/>';
                }
                if (empty($_POST['goods_weight'])) {
                    $error_tips .= L_('商品重量不能为空').'<br/>';
                }
                if (empty($_POST['stock_weight'])) {
                    $error_tips .= L_('库存重量不能为空').'<br/>';
                }
                if (empty($_POST['original_weight'])) {
                    $error_tips .= L_('原始库存重量不能为空').'<br/>';
                }
                if($_POST['original_stock'] === ''){
                    $error_tips .= L_('商品库存必填').'<br/>';
                }
                if(($_POST['original_weight'] != -1 && $_POST['stock_weight'] > $_POST['original_weight']) || ($_POST['stock_weight'] == -1 && $_POST['original_weight'] != -1)){
                    $error_tips .= L_('当前库存重量不能大于原始库存重量！').'<br/>';
                }
            }else{
                if($_POST['stock_num'] === ''){
                    $error_tips .= L_('商品库存必填').'<br/>';
                }
                if(($_POST['original_stock'] != -1 && $_POST['stock_num'] > $_POST['original_stock']) || ($_POST['stock_num'] == -1 && $_POST['original_stock'] != -1)){
                    $error_tips .= L_('当前库存不能大于原始库存！').'<br/>';
                }
            }
			if($_POST['original_stock'] === '' || $_POST['stock_num'] === ''){
				$error_tips .= L_('商品库存必填').'<br/>';
			}

			if(($_POST['original_stock'] != -1 && $_POST['stock_num'] > $_POST['original_stock']) || ($_POST['stock_num'] == -1 && $_POST['original_stock'] != -1)){
				$error_tips .= L_('当前库存不能大于原始库存！').'<br/>';
			}

			if(($_POST['original_weight'] != -1 && $_POST['stock_weight'] > $_POST['original_weight']) || ($_POST['stock_weight'] == -1 && $_POST['original_weight'] != -1)){
				$error_tips .= L_('当前库存重量不能大于原始库存重量！').'<br/>';
			}

			$_POST['des'] = fulltext_filter($_POST['des']);

			//exit;
			$_POST['pic'] = implode(';', $_POST['pic']);
			$_POST['print_id'] = isset($_POST['print_id']) ? intval($_POST['print_id']) : 0;

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
			//去空格处理
            if ($_POST['spec_val']) {
                foreach ($_POST['spec_val'] as $k1 => $rowset) {
                    foreach ($rowset as $k2 =>$val) {
                        $rowset[$k2] = trim($val);
                    }
                    $_POST['spec_val'][$k1] = $rowset;
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
            //去空格处理
            if ($_POST['properties_val']) {
                foreach ($_POST['properties_val'] as $k1 => $rowset) {
                    foreach ($rowset as $k2 =>$val) {
                        $rowset[$k2] = trim($val);
                    }
                    $_POST['properties_val'][$k1] = $rowset;
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

			$sort_id = 0;
			for ($i = 1; $i <= self::GOODS_SORT_LEVEL; $i++) {
			    if (isset($_POST['sort_id_' . $i]) && intval($_POST['sort_id_' . $i])) {
			        $sort_id = intval($_POST['sort_id_' . $i]);
			        unset($_POST['sort_id_' . $i]);
			    }
			}
			$shopGoodsSortDB = M('Shop_goods_sort');
			if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sort_id, 'store_id' => $now_store['store_id']))->find()) {
			    if ($fsort = M(_view('Shop_goods_sort'))->field(true)->where(array('fid' => $sort_id, 'store_id' => $now_store['store_id']))->find()) {
			        $error_tips .= L_('该分类有子分类，不能直接添加商品').'<br/>';
			    } elseif ($sort['operation_type'] != 0) {
			        $shopGoodsSortDB->where(array('sort_id' => $sort_id, 'store_id' => $now_store['store_id']))->save(array('operation_type' => 0));
			    }
			} else {
			    $error_tips .= L_('商品分类不存在').'<br/>';
			}
            if ($_POST['seckill_type']) {
                $_POST['seckill_open_time'] = $_POST['seckill_open_time'] ? strtotime(date('Y-m-d ') . $_POST['seckill_open_time'] . ":00") : 0;
                $_POST['seckill_close_time'] = $_POST['seckill_close_time'] ? strtotime(date('Y-m-d ') . $_POST['seckill_close_time'] . ":00") : 0;
            } else {
                $_POST['seckill_open_time'] = $_POST['seckill_open_datetime'] ? strtotime($_POST['seckill_open_datetime'] . ":00") : 0;
                $_POST['seckill_close_time'] = $_POST['seckill_close_datetime'] ? strtotime($_POST['seckill_close_datetime'] . ":00") : 0;
            }

			//判断是否设置了商品只属于商城
			if($_POST['cat_fid']==0 && $_POST['goods_type']==1){
				$_POST['goods_type']=0;
			}

            //判断区块输入是否合法
            $goods = array('price'=>$_POST['pg_price'], 'name'=>$_POST['pg_goods_name'], 'goods_id'=>$_POST['pg_goods_id'], 'maxnum'=>$_POST['pg_maxnum'], 'mininum'=>$_POST['pg_mininum'], 'sort'=>$_POST['pg_sort']);

			//重新排序goods元素内的数组 否则下面保存出错
            foreach ($goods as $k => $v){
                $goods[$k] = array_values($v);
            }

            foreach ($_POST['maxnum_piece'] as $key => $value) {
                if ($_POST['maxnum_piece'][$key] < $_POST['mininum_piece'][$key]) {
                    $error_tips .= L_('区块最小购买量必须小于等于最大购买量,请重新填写合理数量') . '<br/>';
                }

                foreach ($goods['maxnum'][$key] as $k => $v) {
                    if ($goods['maxnum'][$key][$k] < $goods['mininum'][$key][$k]) {
                        $error_tips .= L_('附属商品最小购买量必须小于等于最大购买量,请重新填写合理数量') . '<br/>';
                    }
                }

                $s = count($goods['maxnum'][$key]) - 1;
                for($i = 0; $i <= $s; $i++){
                    if($goods['maxnum'][$key][$i] > $value or $goods['mininum'][$key][$i] > $value){
                        $error_tips .= L_('区块最小购买量,最大购买量与商品不匹配,请重新填写合理数量').'<br/>';
                    }
                }
            }

            // 附属菜品与组合套餐不能同时存在
            if ($_POST['name_piece'] && $_POST['name_com']) {
			    $error_tips .= L_('有附属菜的主菜商品不可添加为组合套餐，如要操作，请先删除附属菜且保存后再添加组合套餐。').'<br/>';
            }

			if (empty($error_tips)) {
				$_POST['sort_id'] = $sort_id;
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
                //查询是否开启审核功能
                $open_status = $this->config['open_goods_status'];
                if($open_status==1){
                    $_POST['status'] = 0;
                    $_POST['audit_status'] = 0;
                }

				if ($goods_id = D('Shop_goods')->save_post_form($_POST, $now_store['store_id'])) {
					D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');

					//保存商品区块和附属商品关系
                    $this->save_subsidiary_piece($now_store['store_id'], $goods_id, $_POST['name_piece'], $_POST['mininum_piece'], $_POST['maxnum_piece'], $goods);

					//保存组合套餐
                    $this->save_combination($now_store['store_id'], $goods_id, $_POST['name_com'], $_POST['num_com'], $_POST['com_goods_id'], $_POST['com_ids']);

					//多语言表增加数据
					$lang_model = D('Lang');
					$_POST['goods_id'] = $goods_id;
					$lang_model->add_lang_data('Shop_goods',$_POST);

					// 商品分组
					$group_ids = $_POST['group_ids'];
			        $group_ids_arr = array_unique(explode(',', $group_ids));
			        $add_data = array();
			        if ($group_ids_arr) {
				       	foreach ($group_ids_arr as $key => $value) {
				       		if ($value) {
								$add_data[] = array(
									'goods_id' => $goods_id,
									'group_id' => $value,
								);
				       		}
						}
			           	M('Shop_goods_group_list')->addAll($add_data);
			        }

					$this->success(L_('添加成功！'), U('Shop/goods_list', array('store_id' => $_GET['store_id'],'sort_id'=>$sort_id)));
					die;
					$ok_tips = L_('添加成功！');
				} else {
					$this->error(L_('添加失败！请重试！'), U('Shop/goods_list', array('store_id' => $_GET['store_id'],'sort_id'=>$sort_id)));
					die;
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
		} else {
			/*$this->assign('now_goods', array('seckill_open_time' => strtotime(date('Y-m-d') . ' 08:00:00'), 'seckill_close_time' => strtotime(date('Y-m-d') . ' 10:00:00')));*/
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
		$this->assign('category_list', json_encode($category_list,JSON_UNESCAPED_UNICODE));

		$sort_list = D('Shop_goods_sort')->lists($now_store['store_id'], false,true);
		$this->assign('sort_list', addslashes(json_encode($sort_list, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS)));
		$ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
		$this->assign('select_ids', json_encode($ids));
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
		$this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());
		// 商品分组
        $shop_goods_group_list = M('Shop_goods_group')->where(['store_id' => $now_store['store_id']])->select();
		$this->assign('shop_goods_group_list', $shop_goods_group_list);

        $is_open_online_collection = $this->config['is_open_online_collection'];
        $this->assign('is_open_online_collection', $is_open_online_collection);

        $mer_list = M('Merchant')->field('mer_id,name')->where(array('status' => 1))->select();
        $this->assign('mer_list', $mer_list);
        $lable_list = D('Employee_card_lable')->field('id,name')->where(array('is_del' => 0))->select();
        $this->assign('lable_list', $lable_list);

        $set_employee_card_mer_id = $this->config['set_employee_card_mer_id'] ? explode(',',$this->config['set_employee_card_mer_id']) : [];
        $is_set_employee_card_mer_id = $set_employee_card_mer_id ? in_array($now_store['mer_id'],$set_employee_card_mer_id) : false;
        $this->assign('is_set_employee_card_mer_id',$is_set_employee_card_mer_id);
        $this->display();
	}

	public function ajax_goods_properties()
	{
		$cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
		$goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
		$properties = D('Goods_properties')->field(true)->where(array('status' => 1, 'cat_id' => $cat_id))->select();
		if ($properties) {
			$value_ids = array();
			$relations = D('Goods_properties_relation')->field(true)->where(array('gid' => $goods_id))->select();
			foreach ($relations as $r) {
				$value_ids[] = $r['pid'];
			}
			$pids = array();
			$list = array();
			foreach ($properties as $row) {
				$pids[] = $row['id'];
				$row['value_list'] = null;
				$list[$row['id']] = $row;
			}
			$value_list = D('Goods_properties_value')->field(true)->where(array('pid' => array('in', $pids)))->select();
			foreach ($value_list as $v) {
				if (isset($list[$v['pid']])) {
					if (in_array($v['id'], $value_ids)) {
						$v['checked'] = 1;
					} else {
						$v['checked'] = 0;
					}
					$list[$v['pid']]['value_list'][] = $v;
				}
			}
			$data = array();
			foreach ($list as $row) {
				if (isset($row['value_list']) && $row['value_list']) {
					$data[] = $row;
				}
			}
			exit(json_encode(array('error_code' => false, 'data' => $data)));
		} else {
			exit(json_encode(array('error_code' => true, 'msg' => L_('没有数据'))));
		}
	}

	public function ajax_upload_pic()
	{
		if ($_FILES['file']['error'] != 4) {
			$param = array('size' => 10);
			$image = D('Image')->handle($this->merchant_session['mer_id'], 'goods', 1, $param);
			if ($image['error']) {
				exit(json_encode(array('error' => 1,'message' =>$image['message'])));
			} else {
				exit(json_encode(array('error' => 0, 'url' => file_domain().$image['url']['file'], 'title' => $image['title']['file'])));
			}
		} else {
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}

    /* 编辑商品 */
    public function goods_edit()
    {
        $is_audit = $_GET['is_audit']??0; //平台查看店铺商品详情
        $now_goods = $this->check_goods($_GET['goods_id']);
        $now_sort = $this->check_sort($now_goods['sort_id']);
        if($is_audit==1){
            $now_store = array(
                'store_id' => $_GET['store_id'],
                'mer_id' => $_GET['mer_id']
            );
        }else{
            $now_store = $this->check_store($now_sort['store_id']);
        }
        // 辣度
        $spicy_arr =  D('Shop_goods')->spicy_arr();
       	$this->assign('spicy_arr',$spicy_arr);
        if (IS_POST) {
            if ($now_goods['tw_item_no']) {
                //图维商品只支持修改图片
                $pics = [];
                foreach ($_POST['pic'] as $k => $v) {
                    if (false !== strpos($v, ',')) {
                        $splistArr = explode(',', $v);
                        $pics[] = $splistArr[0] . '/' . $splistArr['1'];
                    } else {
                        $pics[] = $v;
                    }
                }
                if (empty($pics)) {
                    $this->error(L_('请至少上传一张商品图'));
                }

                D('Shop_goods')->syncProductImageToTw($now_goods['goods_id'], $pics);
                $this->success(L_('保存成功！'), U('Shop/goods_list', array('sort_id' => $now_sort['sort_id'], 'store_id' => $now_goods['store_id'], 'page' => $_POST['page'])));
                exit;
            }


			$_POST['spec_val_id'] = array_values($_POST['spec_val_id']);
			$_POST['spec_val'] = array_values($_POST['spec_val']);

            $error_tips = '';
            if (C('customization.life_tools')) {
                if ($_POST['open_employee_card'] == 1 && (empty($_POST['employee_mer_id']) || empty($_POST['employee_lables']))) {
                    $error_tips .= L_('至少选择一个员工标签（如果此商家没有标签，请去通知此商家给员工设置身份标签）！') . '<br/>';
                }
                $_POST['employee_lables'] = !empty($_POST['employee_lables']) ? implode(',', $_POST['employee_lables']) : '';
            }
            if (empty($_POST['name'])) {
                $error_tips .= L_('商品名称必填！') . '<br/>';
            }
            $_POST['name'] = htmlspecialchars_decode($_POST['name']);
            if (empty($_POST['unit'])) {
                $error_tips .= L_('商品单位必填！') . '<br/>';
            }
            if ($_POST['price'] === '' && !$this->config['open_extra_price']) {
                $error_tips .= L_('商品价格可以设置为0，但是必填！') . '<br/>';
            }
            if ($_POST['price'] < 0 && !$this->config['open_extra_price']) {
                $error_tips .= L_('商品价格必须大于或等于0！') . '<br/>';
            }

            if (empty($_POST['pic'])) {
                if (!C('config.is_open_goods_default_image')) {
                    $error_tips .= L_('请至少上传一张照片！') . '<br/>';
                }
            }

            if(!empty($_POST['shop_video_url'])){
            	$_POST['shop_video_preview'] = '/'.$_POST['shop_video_url'].'.png';
            }

            !isset($_POST['sell_type']) && $_POST['sell_type'] = $now_goods['sell_type'];
            if ($_POST['stock_num'] >= 0 && (($_POST['seckill_stock'] > 0 && $_POST['seckill_stock'] > $_POST['stock_num']+$now_goods['today_sell_count']) || $_POST['seckill_stock'] == -1)) {
                $error_tips .= L_('限时价库存不能大于商品库存！如果不做限时活动，请将限时价库存设置为0') . '<br/>';
            }
            if($_POST['sell_type']==2){
                if($_POST['original_stock'] === '' || $_POST['stock_num'] === ''){
                    $error_tips .= L_('商品库存必填').'<br/>';
                }
                if(($_POST['original_weight'] != -1 && $_POST['stock_weight'] > $_POST['original_weight']) || ($_POST['stock_weight'] == -1 && $_POST['original_weight'] != -1)){
                    $error_tips .= L_('当前库存不能大于原始库存！').'<br/>';
                }
            }else{
                if($_POST['stock_num'] === ''){
                    $error_tips .= L_('商品库存必填').'<br/>';
                }
                if(($_POST['original_stock'] != -1 && $_POST['stock_num'] > $_POST['original_stock']) || ($_POST['stock_num'] == -1 && $_POST['original_stock'] != -1)){
                    $error_tips .= L_('当前库存不能大于原始库存！').'<br/>';
                }
            }
            if ($now_goods['stock_num'] != $_POST['stock_num']) {
                $_POST['sell_day'] = date('Ymd');
            }
			if($_POST['original_stock'] === '' || $_POST['stock_num'] === ''){
				$error_tips .= L_('商品库存必填').'<br/>';
			}


			if($now_goods['stock_num'] != $_POST['stock_num']){
				$_POST['sell_day'] = date('Ymd');
			}

			$_POST['des'] = fulltext_filter($_POST['des']);

			/*$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			foreach($_POST['pic'] as $kp => $vp){
			    if(false === strpos($vp,',')){
                    $_POST['pic'][$kp] = $vp;
                }else{
                    $tmp_vp = explode(',', $vp);
                    if (!strstr($tmp_vp[0], '/upload/')) $rand_num = $tmp_vp[0];
                    $_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
                }
			}*/
			//处理选择图片链接存在逗号隔开的
            foreach ($_POST['pic'] as $k=>$v){
                if(false !== strpos($v,',')){
                    $splistArr = explode(',',$v);
                    $_POST['pic'][$k] = $splistArr[0].'/'.$splistArr['1'];
                }
            }

			$_POST['pic'] = implode(';', $_POST['pic']);

			$_POST['print_id'] = isset($_POST['print_id']) ? intval($_POST['print_id']) : 0;

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
            //去空格处理
            if ($_POST['spec_val']) {
                foreach ($_POST['spec_val'] as $k1 => $rowset) {
                    foreach ($rowset as $k2 =>$val) {
                        $rowset[$k2] = trim($val);
                    }
                    $_POST['spec_val'][$k1] = $rowset;
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
            //去空格处理
            if ($_POST['properties_val']) {
                foreach ($_POST['properties_val'] as $k1 => $rowset) {
                    foreach ($rowset as $k2 =>$val) {
                        $rowset[$k2] = trim($val);
                    }
                    $_POST['properties_val'][$k1] = $rowset;
                }
            }

			if ($_POST['score_percent']) {
				if(strpos($_POST['score_percent'],'%')){
					$tmp_percent = str_replace('%','',$_POST['score_percent']);
					if(floatval($tmp_percent)>100 ||floatval($tmp_percent)<0 ){
						$error_tips .= L_('消费1X1获得积分数百分比应在0-100%之间',C('config.Currency_txt')).'<br/>';
					}
				}
			}
			if ($_POST['score_max']<0) {
				$error_tips .= L_('积分最大使用数应是大于等于0的整数').'<br/>';
			}
			$score_max_deducte = bcdiv($_POST['score_max'], $this->config['user_score_use_percent'], 2);
			if ($score_max_deducte>$_POST['price']) {
				$error_tips .= L_('积分最大使用数抵扣金额超过了商品价格').'<br/>';
			}

			$sort_id = $now_sort['sort_id'];
			for ($i = 1; $i <= self::GOODS_SORT_LEVEL; $i++) {
			    if (isset($_POST['sort_id_' . $i]) && intval($_POST['sort_id_' . $i])) {
			        $sort_id = intval($_POST['sort_id_' . $i]);
			        unset($_POST['sort_id_' . $i]);
			    }
			}
			$shopGoodsSortDB = M('Shop_goods_sort');
			if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sort_id, 'store_id' => $now_store['store_id']))->find()) {
			    if ($fsort = M(_view('Shop_goods_sort'))->field(true)->where(array('fid' => $sort_id, 'store_id' => $now_store['store_id']))->find()) {
			        $error_tips .= L_('该分类有子分类，不能直接添加商品').'<br/>';
			    } elseif ($sort['operation_type'] != 0) {
			        $shopGoodsSortDB->where(array('sort_id' => $sort_id, 'store_id' => $now_store['store_id']))->save(array('operation_type' => 0));
			    }
			} else {
			    $error_tips .= L_('商品分类不存在').'<br/>';
			}

			if ($_POST['seckill_type']) {
			    $_POST['seckill_open_time'] = strtotime(date('Y-m-d ') . $_POST['seckill_open_time'] . ":00");
			    $_POST['seckill_close_time'] = strtotime(date('Y-m-d ') . $_POST['seckill_close_time'] . ":00");
			} else {
			    $_POST['seckill_open_time'] = strtotime($_POST['seckill_open_datetime'] . ":00");
			    $_POST['seckill_close_time'] = strtotime($_POST['seckill_close_datetime'] . ":00");
			}

			//判断是否设置了商品只属于商城
			if($_POST['cat_fid']==0 && $_POST['goods_type']==1){
				$_POST['goods_type']=0;
			}

			//判断区块输入是否合法
            $goods = array('price'=>$_POST['pg_price'], 'name'=>$_POST['pg_goods_name'], 'goods_id'=>$_POST['pg_goods_id'], 'maxnum'=>$_POST['pg_maxnum'], 'mininum'=>$_POST['pg_mininum'], 'sort'=>$_POST['pg_sort']);

			//重新排序goods元素内的数组 否则下面保存出错
            foreach ($goods as $k => $v){
                $goods[$k] = array_values($v);
            }

			foreach ($_POST['maxnum_piece'] as $key => $value) {
                if ($_POST['maxnum_piece'][$key] < $_POST['mininum_piece'][$key]) {
                    $error_tips .= L_('区块最小购买量必须小于等于最大购买量,请重新填写合理数量') . '<br/>';
                }

                foreach ($goods['maxnum'][$key] as $k => $v) {
                    if ($goods['maxnum'][$key][$k] < $goods['mininum'][$key][$k]) {
                        $error_tips .= L_('附属商品最小购买量必须小于等于最大购买量,请重新填写合理数量') . '<br/>';
                    }
                }

                //$goodsmini_sum = $goodsmax_sum = 0;
                $s = count($goods['maxnum'][$key]) - 1;
                for($i = 0; $i <= $s; $i++){
                   // $goodsmax_sum += $goods['maxnum'][$key][$i];
                   // $goodsmini_sum += $goods['mininum'][$key][$i];

                    if($goods['maxnum'][$key][$i] > $value or $goods['mininum'][$key][$i] > $value){
                        $error_tips .= L_('区块最小购买量,最大购买量与商品不匹配,请重新填写合理数量').'<br/>';
                    }

                }
               // if($goodsmax_sum > $value or $goodsmini_sum > $value){
               //     $error_tips .= L_('区块最小购买量,最大购买量与商品不匹配,请重新填写合理数量').'<br/>';
               // }
            }

            // 附属菜品与组合套餐不能同时存在
            if ($_POST['name_piece'] && $_POST['name_com']) {
			    $error_tips .= L_('有附属菜的主菜商品不可添加为组合套餐，如要操作，请先删除附属菜且保存后再添加组合套餐。').'<br/>';
            }

            //判断商品价格和规格价最高涨幅
            $max_percent = $this->merchant_session['login_type'] == 'fake_login' ? 0 : C('config.shop_goods_price_increase_percent');
            if ($max_percent > 0 && $_POST['price'] > $now_goods['price'] * (1 + $max_percent / 100)) {
                $error_tips .= L_(sprintf('商品涨幅不可超过%s，如有特殊情况修改请联系平台管理员', $max_percent . '%')) . '<br/>';
            }

            $spec_modify = [];
            if (!empty($now_goods['list'])) {
                $price = isset($_POST['prices']) ? $_POST['prices'] : [];
                $spec_val_id = isset($_POST['spec_val_id']) ? $_POST['spec_val_id'] : [];
                $spec_value = [];
                D('Shop_goods')->format_str($spec_val_id, 0, [], $spec_value);
                $spec_value = array_filter($spec_value);
                if ($spec_value) {
                    $spec_value_index = [];
                    foreach ($_POST['spec_val_id'] as $k => $v) {
                        $spec_value_index = $spec_value_index + array_combine($v, $_POST['spec_val'][$k]);
                    }
                    foreach ($spec_value as $k => $v) {
                        $old_index = str_replace(':', '_', $v);
                        $ids = explode(':', $v);
                        $spec_str = array_map(function ($k) use ($spec_value_index) {
                            return $spec_value_index[$k];
                        }, $ids);
                        $spec_str = implode('、', $spec_str);

                        if (isset($now_goods['list'][$old_index])) {
                            $new_price = $price[$k];
                            $old_price = $now_goods['list'][$old_index]['price'];
                            if ($max_percent > 0 && $old_price > 0 && $new_price > $old_price * (1 + $max_percent / 100)) {
                                $error_tips .= L_(sprintf('规格：%s涨幅不可超过%s，如有特殊情况修改请联系平台管理员', $spec_str, $max_percent . '%')) . '<br/>';
                            }
                            if ($old_price != $new_price) {
                                $spec_modify[] = [
                                    'index' => $v,
                                    'str' => $spec_str,
                                    'old_price' => getFormatNumber($old_price),
                                    'new_price' => getFormatNumber($new_price)
                                ];
                            }
                        }
                    }
                }
            }

            if ($spec_modify || $_POST['price'] != $now_goods['price']) {
                $price_modify_log = [
                    'old_price' => getFormatNumber($now_goods['price']),
                    'new_price' => getFormatNumber($_POST['price']),
                    'store_id' => $now_store['store_id'],
                    'mer_id' => $now_store['mer_id'],
                    'goods_id' => $now_goods['goods_id'],
                    'goods_name' => $_POST['name'],
                    'spec_value' => json_encode($spec_modify, JSON_UNESCAPED_UNICODE),
                    'time' => time()
                ];
            }

			//删除缓存里的库存
			D('Shop_goods')->set_goods_stock_cache($now_goods['goods_id'], true);

			if (empty($error_tips)) {
				$_POST['goods_id'] = $now_goods['goods_id'];
				$_POST['sort_id'] = $sort_id;
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
                //查询是否开启审核功能
                $open_status = $this->config['open_goods_status'];
                if($open_status==1){
                    $_POST['status'] = 0;
                    $_POST['audit_status'] = 0;
                }else{
                    $_POST['audit_status'] = 1;
                }
                $_POST['audit_content'] = '';
				if ($goods_id = D('Shop_goods')->save_post_form($_POST, $now_store['store_id'])) {
                    if (!empty($price_modify_log)) {
                        M('Shop_goods_price_modify_log')->add($price_modify_log);
                    }

					D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');

					//保存商品区块和附属商品关系
                    $this->save_subsidiary_piece($now_store['store_id'], $goods_id, $_POST['name_piece'], $_POST['mininum_piece'], $_POST['maxnum_piece'], $goods);

					//保存组合套餐
                    $this->save_combination($now_store['store_id'], $goods_id, $_POST['name_com'], $_POST['num_com'], $_POST['com_goods_id'], $_POST['com_ids']);

					//多语言表增加数据
					$lang_model = D('Lang');
					$_POST['goods_id'] = $goods_id;
					$lang_model->edit_lang_data('Shop_goods',$_POST);

					// 商品分组
					$group_ids = $_POST['group_ids'];
			        $bind_group_list = M('Shop_goods_group_list')->where(['goods_id' => $_POST['goods_id']])->delete();
			        $group_ids_arr = array_unique(explode(',', $group_ids));
			        $add_data = array();
			        if ($group_ids_arr) {
				       	foreach ($group_ids_arr as $key => $value) {
				       		if ($value) {
								$add_data[] = array(
									'goods_id' => $_POST['goods_id'],
									'group_id' => $value,
								);
				       		}
						}
			           	M('Shop_goods_group_list')->addAll($add_data);
			        }

					$this->success(L_('保存成功！'), U('Shop/goods_list', array('sort_id' => $now_sort['sort_id'],'store_id'=>$now_goods['store_id'],'page' => $_POST['page'])));
					die;
					$ok_tips = L_('保存成功！');
				} else {
					$this->error(L_('保存失败！请重试！'), U('Shop/goods_list', array('sort_id' => $now_sort['sort_id'],'store_id'=>$now_goods['store_id'],'page' => $_POST['page'])));
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
		/*$category_list = D('Goods_category')->get_list();
        $category_list = new stdClass();
		$this->assign('category_list', json_encode($category_list,JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE|JSON_HEX_APOS));*/
		$category_list = D('Goods_category')->get_list();
		$this->assign('category_list', json_encode($category_list,JSON_UNESCAPED_UNICODE));

		$sort_list = D('Shop_goods_sort')->lists($now_store['store_id'], false,true);
        $this->assign('sort_list', addslashes(json_encode($sort_list, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS)));
		$ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
		$this->assign('select_ids', json_encode($ids));
        $now_goods = array_merge($now_goods,$_POST);
        $now_goods['employee_lables'] = !empty($now_goods['employee_lables']) ? explode(',', $now_goods['employee_lables']) : [];
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

		// 查询是否同步到餐饮
		$foodshop_goods_new = M('Foodshop_goods_new')->where(array('goods_id'=>$now_goods['goods_id']))->find();
		$this->assign('foodshop_goods_sync', $foodshop_goods_new ? 1 : 0);

        $is_open_online_collection = $this->config['is_open_online_collection'];
        $this->assign('is_open_online_collection', $is_open_online_collection);

        $mer_list = M('Merchant')->field('mer_id,name')->where(array('status' => 1))->select();
        $this->assign('mer_list', $mer_list);
        $lable_list = D('Employee_card_lable')->field('id,name')->where(array('mer_id' => $now_goods['employee_mer_id'], 'is_del' => 0))->select();
        $this->assign('lable_list', $lable_list);
        $this->assign('is_audit',$is_audit);
        $set_employee_card_mer_id = $this->config['set_employee_card_mer_id'] ? explode(',',$this->config['set_employee_card_mer_id']) : [];
        $is_set_employee_card_mer_id = $set_employee_card_mer_id ? in_array($now_store['mer_id'],$set_employee_card_mer_id) : false;
        $this->assign('is_set_employee_card_mer_id',$is_set_employee_card_mer_id);
		$this->assign('merchant', $this->merchant_session);
		$this->display();
	}

	public function get_lable_employee_list() {
        $mer_id = I('mer_id', 0, 'intval');
        $lable_list = D('Employee_card_lable')->field('id,name')->where(array('mer_id' => $mer_id, 'is_del' => 0))->select();
        $inner_html = '<label class="col-sm-2"><label>选择员工身份标签</label></label>';
        if (!empty($lable_list)) {
            foreach ($lable_list as $val) {
                $inner_html .= '<input type="checkbox" name="employee_lables[]" value="' . $val['id'] . '" data-k="' . $val['id'] . '">&nbsp;' . $val['name'] . '&nbsp;&nbsp;&nbsp;&nbsp;';
            }
        }
        echo $inner_html;
    }

	/* 商品删除 */
	public function goods_del()
	{
		$now_goods = $this->check_goods($_GET['goods_id'],1);
		$now_sort = $this->check_sort($now_goods['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);

        if (D('Time_limited_discount_goods')->in_progress_or_todo($_GET['goods_id'])) {
            $this->error(L_('该商品已经设置了限时优惠活动，暂不可删除。'));
        }
		$database_goods = D('Shop_goods');
		$condition_goods['goods_id'] = $now_goods['goods_id'];
		if ($database_goods->where($condition_goods)->delete()) {
			$spec_obj = M('Shop_goods_spec'); //规格表
			$old_spec = $spec_obj->field(true)->where(array('goods_id' => $now_goods['goods_id'], 'store_id' => $now_sort['store_id']))->select();
			foreach ($old_spec as $os) {
				$delete_spec_ids[] = $os['id'];
			}
			$spec_obj->where(array('goods_id' => $now_goods['goods_id'], 'store_id' => $now_sort['store_id']))->delete();
			if ($delete_spec_ids) {
				$old_spec_val = M('Shop_goods_spec_value')->where(array('sid' => array('in', $delete_spec_ids)))->delete();
			}

			// 如若该分类下没有商品，则将上级分类改为可添加子分类和商品两种都支持的
			if ($now_sort['level']<=2) {//三级分类不需修改
				// 查找商品
				$condition_goods = [];
				$condition_goods['sort_id'] = $now_sort['sort_id'];
				if (!$goods = $database_goods->where($condition_goods)->find()) {
					//上级分类下没有子分类，修改状态
					$condition_sort = [];
					$condition_sort['sort_id'] = $now_sort['sort_id'];
					//修改状态
					D('Shop_goods_sort')->where($condition_sort)->setField('operation_type',2);
				}
			}

			M('Shop_goods_properties')->where(array('goods_id' => $now_goods['goods_id']))->delete();

			S('shop_goods_by_storeid_' . $now_store['store_id'],null);

			$this->success(L_('删除成功！'));
		}else{
			$this->error(L_('删除失败！请检查后重试。'));
		}
	}

	/* 商品状态 */
	public function goods_status()
	{
		$now_goods = $this->check_goods($_POST['id']);
		$now_sort = $this->check_sort($now_goods['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);
        //查询是否开启审核功能
        $open_status = $this->config['open_goods_status'];
        if($open_status==1&&$now_goods['audit_status']!=1){
            exit('2');
        }

        $database_goods = D('Shop_goods');

		$condition_goods['goods_id'] = $now_goods['goods_id'];
		$data_goods['status'] = $_POST['type'] == 'open' ? '1' : '0';
		if($database_goods->where($condition_goods)->data($data_goods)->save()){
			S('shop_goods_by_storeid_' . $now_store['store_id'],null);
			// 同步修改餐饮状态
			if ($_POST['is_sync']==1) {
				M('Foodshop_goods_new')->where(array('goods_id' => $now_goods['goods_id']))->save(array('status' => $data_goods['status'],  'last_time' => time()));
			}
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

		$database_merchant_store = D(_view('Merchant_store'));
		$condition_merchant_store['store_id'] = $store_id;
		$condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
		$now_store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
		if (empty($now_store)) {
			$this->error(L_('店铺不存在！'));
		} else {
			//return $now_store;
			if ($now_shop = D(_view('Merchant_store_shop'))->field(true)->where($condition_merchant_store)->find()) {
				if (!empty($now_shop['background'])) {
					$image_tmp = explode(',', $now_shop['background']);
					$now_shop['background_image'] = file_domain() . '/upload/background/' . $image_tmp[0] . '/' . $image_tmp['1'];
				}
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
	protected function check_goods($goods_id,$is_del=0)
	{
		$database_shop_goods = D('Shop_goods');
		$dadabase_shop_goods_model = M(_view('Shop_goods'));
		$condition_goods['goods_id'] = $goods_id;
		$now_goods = $dadabase_shop_goods_model->field(true)->where($condition_goods)->find();
		if(empty($now_goods)){
			$this->error(L_('商品不存在！'));
		}
		$act_info=D('Shop_goods')->get_good_act_info($goods_id);
		if($act_info){
			$now_goods['is_act']=1;
			if($act_info['is_group']){
				$act_name=L_('拼团');
				$now_goods['is_group']=1;
			}else{
				$act_name=L_('砍价');
				$now_goods['is_cut']=1;
			}
			if($is_del){
				$this->error(L_('抱歉！该商品已经被设置为X1活动，暂不可删除。请先到商城管理--商城店铺列表--X2管理中解禁该商品。',array('X1'=>$act_name,'X2'=>$act_name)),'',5);
				exit;
			}
		}
		if(!empty($now_goods['image'])){
			$goods_image_class = new goods_image();
			$tmp_pic_arr = explode(';', $now_goods['image']);
			foreach ($tmp_pic_arr as $key => $value) {
			    if('http' === substr($value,0,4)){
					$now_goods['pic_arr'][$key]['title'] = $value;
					$now_goods['pic_arr'][$key]['url'] = replace_file_domain($value);
				}else{
                    $now_goods['pic_arr'][$key]['title'] = $value;
                    $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                }

			}
		}

        $return = $database_shop_goods->format_spec_value($now_goods['spec_value'], $now_goods['goods_id'], 1, 0, null, '', false);
		$now_goods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
		$now_goods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
		$now_goods['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
		$now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
		$now_goods['list'] = isset($return['list']) ? $return['list'] : '';
		$now_goods['flavor'] = $now_goods['flavor'] ? explode('&^&',$now_goods['flavor']) : '';
		$now_goods['material'] = $now_goods['material'] ? explode('&^&',$now_goods['material']) : '';
		$now_goods['special_food_materials'] = $now_goods['special_food_materials'] ? explode('&^&',$now_goods['special_food_materials']) : '';
		$now_goods['cooking_methods'] = $now_goods['cooking_methods'] ? explode('&^&',$now_goods['cooking_methods']) : '';

		/*处理图片上传阿里云导致详情中的图片不展示*/
        if (!(C('config.is_open_online_collection') == 1 && (strpos($now_goods['des'], 'alicdn.com') !== false || strpos($now_goods['des'], 'pddpic.com') !== false))) {
            $src = '<img src="' . file_domain() . '/';
            $now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
            $now_goods['des'] = str_replace('<img alt="" src="/', $src, $now_goods['des']);
        }
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

	public function format_data($data)
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
// 		foreach ($data['spec_val_id'] as $k => $rowset) {
			$index = $pre = '';
			$tdata = array();
			foreach ($array as $irow) {
				$k = $irow[0];
				$ki = $irow[1];
				$r = $data['spec_val_id'][$irow[0]][$irow[1]];
// 			foreach ($rowset as $ki => $r) {
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
			$list[$index]['max_num'] = $data['max_nums'][$fi];
			$list[$index]['cost_price'] = $data['cost_prices'][$fi];
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
			$json[$index]['max_nums[]'] = $data['max_nums'][$fi];
			$json[$index]['cost_prices[]'] = $data['cost_prices'][$fi];
			$json[$index]['numbers[]'] = $data['numbers'][$fi];
		}
		return array('spec_list' => $spec_list, 'properties_list' => $properties_list, 'list' => $list, 'json' => $json);
	}



	public function order()
	{
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		$orderFrom =  I('order_from');
        $pick_id = isset($_GET['pick_id']) && $_GET['pick_id'] ? $_GET['pick_id'] : '';
        $staff_id = isset($_GET['staff_id']) && $_GET['staff_id'] ? $_GET['staff_id'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';
		if ($type && $sort) {
			$order_sort .= $type . ' ' . $sort . ',';
			$order_sort .= 'pay_time DESC,create_time DESC';
		} else {
			$order_sort .= 'pay_time DESC,create_time DESC';
		}

		$where = array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id']);

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'real_orderid') {
                $where['real_orderid'] = htmlspecialchars($_GET['keyword']);
            } elseif ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid' => $where['orderid']))->find();
                unset($where['orderid']);
                $where['order_id'] = $tmp_result['order_id'];
            } elseif ($_GET['searchtype'] == 'name') {
                $where['username'] = htmlspecialchars($_GET['keyword']);
            } elseif ($_GET['searchtype'] == 'phone') {
                $where['userphone'] = htmlspecialchars($_GET['keyword']);
            } elseif ($_GET['searchtype'] == 'third_id') {
                $where['third_id'] = $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'register_phone') {
                $uid = M('User')->where(['phone' => $_GET['keyword']])->getField('uid');
                if ($uid > 0) {
                    $where['uid'] = $uid;
                }
            } elseif ($_GET['searchtype'] == 'member_card') {
                $uid = M('employee_card_user')->where(['card_number' => $_GET['keyword']])->getField('uid');
                if ($uid > 0) {
                    $where['uid'] = $uid;
                }
            }
        }
        $cardUserModel = M('Employee_card_user');
        $identity = $department = '';
        if(!empty($_GET['identity']) || !empty($_GET['department'])){
            $identity = htmlspecialchars($_GET['identity'] ?? '');
            $department = htmlspecialchars($_GET['department'] ?? '');

            $condition = [];
            $condition['identity'] = ['like', "%$identity%"];
            $condition['department'] = ['like', "%$department%"];
            $condition['mer_id'] = $now_store['mer_id'];
            $uids = $cardUserModel->where($condition)->getField('uid', true);
            $where['uid'] = ['in', $uids ?: []];
        }
        
		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';

		if($status == 100){
			$where['paid'] = 0;
		}else if ($status != -1) {
		    if ($status == 2) {
		        $where['status'] = array('in', '2, 3');
		    } elseif ($status == 6) {
		        $where['paid'] = 0;
		    } else {
		        $where['status'] = $status;
		    }
		}
		if($pay_type&&$pay_type!='balance'){
            if($pay_type == 'employee_balance_pay'){
                $where['employee_balance_pay'] = ['gt', 0];
            }else if($pay_type == 'employee_balance_pay'){
                $where['employee_score_pay'] = ['gt', 0];
            }else{
                $where['pay_type'] = $pay_type;
            }
		}else if($pay_type=='balance'){
			$where['_string'] = "(`balance_pay`<>0 OR `merchant_balance` <> 0 OR `card_give_money` <> 0)";
		}

		$count_where = "store_id='{$now_store['store_id']}' AND paid=1 AND is_del=0 AND status<>4 AND status<>5 AND (pay_type<>'offline' OR (pay_type='offline' AND third_id<>''))";

		if (! empty($_GET['begin_time']) && ! empty($_GET['end_time'])) {
            if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}

			$period = array(
				strtotime($_GET['begin_time']." 00:00:00"),
				strtotime($_GET['end_time']." 23:59:59")
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

	            $count_where .= " AND (`order_id` in (".implode(',', $refundOrderIdArray)."))";

            }else{
	            $where['_string'] .= ($where['_string'] ? ' AND ' : '') . " (".$_GET['searchTimeType']." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

		    	$count_where .= " AND (".$_GET['searchTimeType']." BETWEEN " . $period[0] .' AND ' . $period[1] . ")";
            }
        }

        if($pick_id){
            $where['pick_id'] = $pick_id;
            $count_where .= " AND pick_id = ".$pick_id;
        }
        if($staff_id){
        	$staffName = M('Merchant_store_staff')->where(['id' => $staff_id])->field('name')->find();
			if(!empty($staffName['name'])){
				$where['staff_id|last_staff'] =array($staff_id,$staffName['name'],'_multi'=>true);
				$count_where .= " AND (staff_id = {$staff_id} OR last_staff={$staffName['name']})";
			}else{
				$where['staff_id'] = $staff_id;
				$count_where .= " AND staff_id = ".$staff_id;
            }
        }
        $this->assign('pick_id',$pick_id);
        $this->assign('staff_id',$staff_id);

        //去除来源是商城的
        $where['order_from'] = array('neq', 1);

        if($orderFrom == 1){
			$where['order_from'] = array('eq', 6);
		}else if($orderFrom == 2){
			$where['order_from'] = ['NOT IN', [1, 6]];
		}
		$order_list = D('Shop_order')->get_order_list($where, $order_sort, 2, false);

        //获取用户信息
        $uids = array_unique(array_column($order_list['order_list'], 'uid'));
        if ($uids) {
            $users = D('User')->where(['uid' => ['IN', $uids]])->getField('uid,nickname,phone');
        } else {
            $users = [];
        }
        $this->assign('users',$users);

        $status_list = D('Shop_order')->status_list;
		// 开启多语言
		if (C('config.open_multilingual')) {
			unset($status_list[7],$status_list[8],$status_list[9],$status_list[10]);
		}
        $this->assign('status_list', $status_list);
        $this->assign($order_list);
        $this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status, 'pay_type' => $pay_type));

		$field = 'sum(price) AS total_price, sum(price - card_price - merchant_balance - balance_pay - payment_money - score_deducte - coupon_price) AS offline_price, sum(card_price + merchant_balance + balance_pay + payment_money + score_deducte + coupon_price - membership_card_price) AS online_price';

		$count_where.=' and order_from!=1';
		$result_total = D('Shop_order')->field($field)->where($count_where)->select();
		$result_total = isset($result_total[0]) ? $result_total[0] : '';
		$result_total['offline_price'] = $result_total['offline_price'] < 0 ? 0 : $result_total['offline_price'];
		$this->assign($result_total);
		$pay_method_wap = D('Config')->get_pay_method('','',1);
		$pay_method_app = D('Config')->get_pay_method('','',0,1);
		$pay_method = array_merge($pay_method_wap,$pay_method_app);
		$this->assign('pay_method',$pay_method);

        //查询员工信息
        $condition = [];
        $condition['mer_id'] = $now_store['mer_id'];
        $userList = $cardUserModel->where($condition)->select();
        $identityList = array_column($userList, 'identity', 'uid');
        $departmentList = array_column($userList, 'department', 'uid');
        $this->assign('identityList', $identityList);
        $this->assign('departmentList', $departmentList);
        $this->assign('identity', $identity);
        $this->assign('department', $department);
		//获取自提点列表
        $self_pick = D('Pick_address')->field('id,pick_addr')->where('mer_id = '.$now_store['mer_id'])->select();
	    $this->assign('self_pick',$self_pick);
        //获取店员列表
        $staff_type = array('店员','核销员','店长');
        $staff_arr = D('Merchant_store_staff')->field('id,name,type')->where('store_id = '.$now_store['store_id'])->select();
        foreach ($staff_arr as $k=>$v){
            $staff_arr[$k]['name'] = isset($staff_type[$v['type']])?$staff_type[$v['type']].'-'.$v['name']:$v['name'];
        }
        $this->assign('staff_arr',$staff_arr);
		$this->display();
	}

	//订单详情
	public function order_detail()
	{
		if(strlen($_GET['order_id'])>=20){
			$now_shop_order = D('Shop_order')->where(array('real_orderid'=>$_GET['order_id']))->find();
			$_GET['order_id'] = $now_shop_order['order_id'];
		}
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id']));
		$order['internal_card_pay_money'] = 0;
		$internalCardPay = @json_decode($order['internal_card_pay'], true);
		if(is_array($internalCardPay)){
			$order['internal_card_pay_money'] = array_sum($internalCardPay);
		}
		$order['user_adress'] = D('User_adress')->get_one_adress($order['uid'],intval($order['address_id']));
		if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $order['store_id']) {
		    $this->error(L_('您没有这个权限'));
		}
		//获取退款时间
        if ($order['status'] == 4) {
            $order['refund_time'] = M('Shop_order_log')->where(['status' => 9, 'order_id' => intval($_GET['order_id'])])->getField('dateline');
        }
		//获取取消订单时间
        if ($order['status'] == 5) {
            $order['cancel_time'] = M('Shop_order_log')->where(['status' => 10, 'order_id' => intval($_GET['order_id'])])->getField('dateline');
        }

		$tempList = array();
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

        //组合附属商品2
        foreach ($order['info'] as $key => $value){
            $order['info'][$key]['list'] = D('Shop_goods')->combination_data2($value['list']);
        }

        if ($order['dingding_trade_no'] != '') {
            import('@.ORG.Dingding');
            $dingDing = new Dingding(C('config.dingding_fromplatform'), C('config.dingding_form_app_key'), C('config.dingding_appid'), C('config.dingding_code'), C('config.dingding_per_userid'), C('config.dingding_user_name'), C('config.dingding_password'), C('config.dingding_mobile'));
            $postData = [
                'origin_id' => $order['dingding_trade_no']
            ];
            $orderRecord = $dingDing->orderRecord($postData);
            if ($orderRecord) {
                $this->assign('orderRecord', $orderRecord);
            } else {
                $this->assign('orderRecord', []);
            }
        }

        //支付手续费
        $payment_money_order_db = M("payment_money_order");
        $payment_money_order = $payment_money_order_db->where("order_id=".$_GET['order_id'])->find();
        $order['service_charge'] = '';
        if($payment_money_order && $payment_money_order['service_charge'] != '0.00'){
        	$order['service_charge'] = $payment_money_order['service_charge'];
        }

		$store = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
		$this->assign('store', $store);
		$this->assign('order', $order);
		$this->assign('orderRecord', $orderRecord);
        //退款详情
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $refundList = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'uid' => $order['uid']))->order('id DESC')->select();

        $sql = "SELECT g.image, d.goods_id, d.host_goods_id, d.uniqueness_number, d.name, d.price, d.num, d.unit, d.spec, d.id, d.refund_id FROM " . C('DB_PREFIX') . "shop_goods AS g INNER JOIN " . C('DB_PREFIX') . "shop_refund_detail AS d ON g.goods_id=d.goods_id WHERE d.order_id={$order_id}";
        $list = D()->query($sql);
        $data = array();
        $goods_image_class = new goods_image();
        foreach ($list as $row) {
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
        $status = array(L_('商家审核中'), L_('已完成退货'), L_('商家拒绝退货'), L_('商家审核中'), L_('取消退货申请'));
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
		$this->display();
	}

	public function ajax_del_pic() {
// 		$group_image_class = new goods_image();
// 		$group_image_class->del_image_by_path($_POST['path']);
	}

	public function clone_goods()
	{
	    if ($this->merchant_session['store_id']) {
	        $this->error(L_('您没有这个权限'));
	    }
		$source_store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
		$store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : 0;
		if (empty($store_ids)) {
		    $this->error(L_('请选择要同步的店铺'));
		}
		if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $source_store_id, 'have_shop' => 1))->find()) {
			if ($shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $source_store_id))->find()) {

			} else {
				$this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['shop_alias_name']));
			}
		} else {
			$this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['shop_alias_name']));
		}
		foreach ($store_ids as $store_id) {
			if ($target_store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
				if (!$target_shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find()) {
					continue;
				}
			} else {
				continue;
			}

			$goods_sorts = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $source_store_id))->order('level ASC')->select();
			foreach ($goods_sorts as $sv) {
			    $oldIds[$sv['sort_id']] = $sv['fid'];//id ==> fid
			}
			$listOldFidToNewFid = array();
			foreach ($goods_sorts as $sort) {
				$source_sort_id = $sort['sort_id'];
				//if ($target_sort = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id, 'sort_name' => $sort['sort_name'],'source_sort_id'=>$source_sort_id))->find()) {
				if ($target_sort = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id, 'sort_name' => $sort['sort_name']))->find()) {
					$target_sort_id = $target_sort['sort_id'];
				} else {
                    $sort['store_id'] = $store_id;
                    $sort['source_sort_id'] = $sort['sort_id'];
					unset($sort['sort_id']);
					$sort['fid'] = 0;
					if (isset($oldIds[$source_sort_id]) && $oldIds[$source_sort_id]) {// oldFId = $oldIds[$source_sort_id];
					    $sort['fid'] = isset($listOldFidToNewFid[$oldIds[$source_sort_id]]) ? $listOldFidToNewFid[$oldIds[$source_sort_id]] : 0;
					}
					$target_sort_id = M('Shop_goods_sort')->add($sort);
				}
				$listOldFidToNewFid[$source_sort_id] = $target_sort_id;//oldID ==> newID
				$goods_list = M('Shop_goods')->field(true)->where(array('store_id' => $source_store_id, 'sort_id' => $source_sort_id))->select();
				foreach ($goods_list as $goods) {
					if ($tmp_goods = M('Shop_goods')->field(true)->where(array('name' => $goods['name'], 'store_id' => $store_id))->find()) {
						continue;
					} else {
						$source_goods_id = $goods['goods_id'];
						unset($goods['goods_id']);
						$goods['store_id'] = $store_id;
						$goods['sort_id'] = $target_sort_id;
						$goods['print_id'] = 0;
						$target_goods_id = M('Shop_goods')->add($goods);
						$pro_map = $spec_map = $spec_value_map = array();
						if ($goods['is_properties']) {
							$properties = M('Shop_goods_properties')->field(true)->where(array('goods_id' => $source_goods_id))->select();
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
							$spec_list = M('Shop_goods_spec')->field(true)->where(array('goods_id' => $source_goods_id, 'store_id' => $source_store_id))->select();
							foreach ($spec_list as $spec) {
								$source_spec_id = $spec['id'];
								unset($spec['id']);

								$spec['store_id'] = $store_id;
								$spec['goods_id'] = $target_goods_id;

								if ($new_spec_id = M('Shop_goods_spec')->add($spec)) {
									//多语言
                                    $lang_model = D('Lang');
                                    $spec['id'] = $new_spec_id;
                                    $lang_model->add_lang_data('Shop_goods_spec',$spec);
									$spec_value_list = M('Shop_goods_spec_value')->field(true)->where(array('sid' => $source_spec_id))->select();
									foreach ($spec_value_list as $spec_value) {
										$source_spec_value_id = $spec_value['id'];
										unset($spec_value['id']);
										$spec_value['sid'] = $new_spec_id;
										$spec_value_map[$source_spec_value_id] = M('Shop_goods_spec_value')->add($spec_value);
										//多语言
                                        $lang_model = D('Lang');
                                        $spec_value['id'] = $spec_value_map[$source_spec_value_id];
                                        $lang_model->add_lang_data('Shop_goods_spec_value',$spec_value);
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

	public function store()
	{
	    if ($this->merchant_session['store_id']) {
	        $this->error(L_('您没有这个权限'));
	    }
	    $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
	    if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
	        if ($shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find()) {

	        } else {
	            $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['shop_alias_name']));
	        }
	    } else {
	        $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['shop_alias_name']));
	    }

	    // $sql = "SELECT s.store_id, s.name FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON sh.store_id=s.store_id WHERE s.have_shop=1 AND s.status=1 AND s.store_id<>{$store_id} AND s.mer_id={$this->merchant_session['mer_id']}";
	    // $res = D()->query($sql);
	    $res = M()->table(C('DB_PREFIX')._view('merchant_store')." AS s")->join("INNER JOIN ".C('DB_PREFIX')."merchant_store_shop AS sh ON sh.store_id=s.store_id")->field('s.*')->where("s.have_shop=1 AND s.status=1 AND s.store_id<>{$store_id} AND s.mer_id={$this->merchant_session['mer_id']}")->select();
	    $this->assign('stores', $res);
	    $this->assign('store_id', $store_id);
	    $this->display();
	}

	public function foodshop()
	{
		set_time_limit(0);
	    if ($this->merchant_session['store_id']) {
	        $this->error(L_('您没有这个权限'));
	    }
	    $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
	    if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
	        if ($shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find()) {

	        } else {
	            $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['shop_alias_name']));
	        }
	    } else {
	        $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['shop_alias_name']));
	    }

	    $sql = "SELECT s.store_id, s.name FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_foodshop AS sh ON sh.store_id=s.store_id WHERE s.have_meal=1 AND s.status=1 AND s.mer_id={$this->merchant_session['mer_id']}";
	    $res = D()->query($sql);
	    $this->assign('stores', $res);
	    $this->assign('store_id', $store_id);
	    $this->display();
	}

	public function export()
	{
		$param = $_POST;
		$param['type'] = 'shop';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		$param['store_session']['store_id'] = $_POST['store_id'];
		$param['lang'] = C('config.system_lang');
		C('config.tmp_system_lang',$param['lang']);
		 //$param['store_session']['store_id'] = $store_session['store_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
		die;
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

		// 设置当前的sheet
		$where = array();
		$condition_where = 'WHERE o.store_id = '.$_GET['store_id'];
		$where['store_id'] =$_GET['store_id'];

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
			} elseif ($_GET['searchtype'] == 'third_id') {
				$where['third_id'] =$_GET['keyword'];
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
				$this->error_tips(L_("结束时间应大于开始时间"));
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
			$objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息',($i+1)));
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

			$objActSheet->setCellValue('A1', L_('订单编号'));
			$objActSheet->setCellValue('B1', L_('商品名称'));
			$objActSheet->setCellValue('C1', L_('商品进价'));
			$objActSheet->setCellValue('D1', L_('单价'));
			$objActSheet->setCellValue('E1', L_('单位'));
			$objActSheet->setCellValue('F1', '规格/属性');
			$objActSheet->setCellValue('G1', L_('数量'));
			$objActSheet->setCellValue('H1', L_('商家名称'));
			$objActSheet->setCellValue('I1', L_('店铺名称'));
			$objActSheet->setCellValue('J1', L_('客户姓名'));
			$objActSheet->setCellValue('K1', L_('客户电话'));
			$objActSheet->setCellValue('L1', L_('客户地址'));
			$objActSheet->setCellValue('M1', L_('订单总价'));
			$objActSheet->setCellValue('N1', L_('平台优惠'));
			$objActSheet->setCellValue('O1', L_('商家优惠'));
			$objActSheet->setCellValue('P1', L_('实付总价'));
			$objActSheet->setCellValue('Q1', L_('在线支付金额'));
			$objActSheet->setCellValue('R1', L_('支付时间'));
			$objActSheet->setCellValue('S1', L_('送达时间'));
			$objActSheet->setCellValue('T1', L_('订单状态'));
			$objActSheet->setCellValue('U1', L_('支付情况'));
			//$objActSheet->setCellValue('R1', 支付情况'支付情况');

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
						$objActSheet->setCellValueExplicit('R' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
						$objActSheet->setCellValueExplicit('S' . $index, $value['use_time'] ? date('Y-m-d H:i:s', $value['use_time']) : '');
						$objActSheet->setCellValueExplicit('T' . $index, D('Shop_order')->status_list[$value['status']]);
						$objActSheet->setCellValueExplicit('U' . $index, D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']));
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

	public function ajax_upload_shoppic()
	{
		if ($_FILES['file']['error'] != 4) {
			$param = array('size' => $this->config['group_pic_size']);
			$param['thumb'] = true;
			$param['imageClassPath'] = 'ORG.Util.Image';
// 			$param['thumbPrefix'] = 'm_,s_';
			$param['thumbMaxWidth'] = 640;
			$param['thumbMaxHeight'] = 420;
			$param['thumbRemoveOrigin'] = false;
			$image = D('Image')->handle($this->merchant_session['mer_id'], 'background', 1, $param);
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

	public function ajax_del_shoppic()
	{
		if (!empty($_POST['path'])) {
			$image_tmp = explode(',', $_POST['path']);
			unlink('./upload/background/' . $image_tmp[0] . '/' . $image_tmp['1']);
			unlink('./upload/background/' . $image_tmp[0] . '/m_' . $image_tmp['1']);
			unlink('./upload/background/' . $image_tmp[0] . '/s_' . $image_tmp['1']);
			return true;
		} else {
			return false;
		}
	}

	//720全景压缩包
    public function ajax_upload_zip(){
	    set_time_limit(0);
	    $store_id = $_GET['store_id'];
        if($_FILES['imgFile']['error'] != 4){
            $img_store_id = sprintf("%09d", $store_id);
            $rand_num = substr($img_store_id, 0, 3) . '/' . substr($img_store_id, 3, 3) . '/' . substr($img_store_id, 6, 3);
            $upload_dir = "./upload/720vr/{$rand_num}/";
            if(!is_dir($upload_dir)){
                mkdir($upload_dir,0777,true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = 50*1024*1024;
            $upload->allowExts = array('zip');
            $upload->savePath = $upload_dir;
            $upload->uploadReplace = 1;
            $upload->saveRule = '';
            if($upload->upload()){
                $uploadList = $upload->getUploadFileInfo();
                $zip = new PclZip($upload_dir.$uploadList[0]['savename']);
                if ($zip->extract(
                        PCLZIP_OPT_PATH, $upload_dir
                    ) == 0) {
                    fdump_sql($zip->errorInfo(true),'zip_error');
                }
                $title = $uploadList[0]['savename'];
                $file_title = str_replace('.zip','',$uploadList[0]['savename']);
                $file = $upload_dir.$uploadList[0]['savename'];
                unlink($file);
                if(file_exists($upload_dir.$file_title.'/html/index.html')){
                    exit(json_encode(array('error' => 0,'url' =>$upload_dir.$file_title.'/html/index.html','title'=>$title)));
                }else{
                    exit(json_encode(array('error' => 1,'message' =>L_('未找到index.html，请重新上传有效的压缩包'))));
                }
            }else{
                exit(json_encode(array('error' => 1,'message' =>$upload->getErrorMsg())));
            }
        }else{
            exit(json_encode(array('error' => 1,'message' =>L_('没有选择文件'))));
        }
    }

	public function change_mall()
	{
		$now_store = $this->check_store(intval($_POST['id']));
		$store_theme = $_POST['type'] == 'open' ? '1' : '0';
		if (D('Merchant_store_shop')->where(array('store_id' => $now_store['store_id'], 'mer_id' => $this->merchant_session['mer_id']))->save(array('store_theme' => $store_theme))) {
			exit('1');
		} else {
			exit;
		}
	}

    public function sort_order()
    {
        $sortId = isset($_GET['sort_id']) ? intval($_GET['sort_id']) : 0;
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $now_store = $this->check_store($store_id);

        $stime = isset($_GET['stime']) && $_GET['stime'] ? htmlspecialchars($_GET['stime']) : '';
        $etime = isset($_GET['etime']) && $_GET['etime'] ? htmlspecialchars($_GET['etime']) : '';
        $shopGoodsSortDB = D('Shop_goods_sort');
        $sortList = D(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id, 'fid' => 0))->select();

        if ($sort = D(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sortId, 'store_id' => $store_id))->find()) {
            if ($sort['fid']) {
                $this->error(L_('暂时不支持子分类的查询'));
            } else {
                $ids = $shopGoodsSortDB->getAllSonIds($sortId, $store_id);
                if ($ids) {
                    $where = ' AND d.sort_id IN (' . implode(',', $ids) . ')';
                    if ($stime && $etime) {
                        $where .= ' AND d.create_time>' . strtotime($stime) . ' AND d.create_time<' . strtotime($etime);
                    }
                    $sql = "SELECT count(1) as cnt, sum(d.num) as totalNum, sum(IF(d.discount_price>0, d.discount_price, d.price)) as totalPrice, sum(d.cost_price) as costPrice FROM " . C('DB_PREFIX') . 'shop_order AS o INNER JOIN ' . C('DB_PREFIX') . 'shop_order_detail AS d ON d.order_id=o.order_id WHERE o.paid=1 AND (o.pay_type<>"offline" OR (o.pay_type="offline" AND o.third_id>0)) AND o.status<>4 AND o.status<>5' . $where;
                    $totalList = M()->query($sql);

                    $count = isset($totalList[0]['cnt']) ? $totalList[0]['cnt'] : 0;
                    $totalPrice = isset($totalList[0]['totalPrice']) ? $totalList[0]['totalPrice'] : 0;
                    $totalNum = isset($totalList[0]['totalNum']) ? $totalList[0]['totalNum'] : 0;
                    $costPrice = isset($totalList[0]['costPrice']) ? $totalList[0]['costPrice'] : 0;
                    import('@.ORG.merchant_page');
                    $p = new Page($count, 20);
                    $sql = "SELECT `d`.* FROM " . C('DB_PREFIX') . 'shop_order AS o INNER JOIN ' . C('DB_PREFIX') . 'shop_order_detail AS d ON d.order_id=o.order_id WHERE o.paid=1 AND (o.pay_type<>"offline" OR (o.pay_type="offline" AND o.third_id>0)) AND o.status<>4 AND o.status<>5' . $where . ' LIMIT ' . $p->firstRow . ',' . $p->listRows;
                    $orders = M()->query($sql);
//                     $orders = D('Shop_order_detail')->field(true)->where($where)->select();
                }
                $this->assign(array('total_num' => $totalNum, 'total_price' => $totalPrice, 'cost_price' => $costPrice));
                $this->assign('order_list', $orders);
                $this->assign('sortId', $sortId);
                $this->assign('sort_list', $sortList);
                $this->assign('pagebar', $p->show());
                $this->display();
            }
        } else {
            $this->error(L_('分类信息有误'));
        }
    }

    public function sort_export()
    {
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = L_('商品分类销量统计');
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $sortId = isset($_GET['sort_id']) ? intval($_GET['sort_id']) : 0;
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $now_store = $this->check_store($store_id);

        $stime = isset($_GET['stime']) && $_GET['stime'] ? htmlspecialchars($_GET['stime']) : '';
        $etime = isset($_GET['etime']) && $_GET['etime'] ? htmlspecialchars($_GET['etime']) : '';
        $shopGoodsSortDB = D('Shop_goods_sort');
        $sortList = D(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id, 'fid' => 0))->select();

        if ($sort = D(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sortId, 'store_id' => $store_id))->find()) {
            if ($sort['fid']) {
                return ;
            } else {
                $ids = $shopGoodsSortDB->getAllSonIds($sortId, $store_id);
                if ($ids) {
                    $where = ' AND d.sort_id IN (' . implode(',', $ids) . ')';
                    if ($stime && $etime) {
                        $where .= ' AND d.create_time>' . strtotime($stime) . ' AND d.create_time<' . strtotime($etime);
                    }

                    $sql = "SELECT `d`.* FROM " . C('DB_PREFIX') . 'shop_order AS o INNER JOIN ' . C('DB_PREFIX') . 'shop_order_detail AS d ON d.order_id=o.order_id WHERE o.paid=1 AND (o.pay_type<>"offline" OR (o.pay_type="offline" AND o.third_id>0)) AND o.status<>4 AND o.status<>5' . $where;
                    $orders = M()->query($sql);

                    $objExcel->setActiveSheetIndex(0);
                    $objExcel->getActiveSheet()->setTitle(L_('商品销量详情'));
                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

                    $objActSheet->setCellValue('A1', L_('商品名称'));
                    $objActSheet->setCellValue('B1', L_('商品属性'));
                    $objActSheet->setCellValue('C1', L_('数量'));
                    $objActSheet->setCellValue('D1', L_('单价'));
                    $objActSheet->setCellValue('E1', L_('销售时间'));
                    $index = 1;
                    foreach ($orders as $order) {
                        $index++;
                        $objActSheet->setCellValueExplicit('A' . $index, $order['name']);
                        $objActSheet->setCellValueExplicit('B' . $index, $order['spec']);
                        $objActSheet->setCellValueExplicit('C' . $index, $order['num']);
                        $objActSheet->setCellValueExplicit('D' . $index, $order['discount_price'] > 0 ? $order['discount_price'] : $order['price']);
                        $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $order['create_time']));
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
            }
        } else {
            return false;
        }
    }

    /**
     * 将商品发布到批发市场
     */
    public function goods_push()
    {
        $now_merchant = M('Merchant')->where(array('mer_id' => $this->merchant_session['mer_id']))->field('is_publish_market_goods')->find();
        $is_publish_market_goods = $now_merchant['is_publish_market_goods']?$now_merchant['is_publish_market_goods']:0;
        if ($is_publish_market_goods == 1) {
            $this->error(L_('当前您不能发布商品，如需发布请联系管理员！'));
        }
        $goodsId = intval($_GET['goods_id']);
        $database_shop_goods = D('Shop_goods');
        $where = array('goods_id' => $goodsId);
        $now_goods = $database_shop_goods->field(true)->where($where)->find();
        if(empty($now_goods)){
            $this->error(L_('商品不存在！'));
        }
        $marketGoodsOld = M('Market_goods')->where($where)->find();
        if (!$marketGoodsOld && $now_goods['original_goods_id']>0) {
            $this->error(L_('该商品不能发布！'));
        }
        $now_store = $this->check_store($now_goods['store_id']);

        if(!empty($now_goods['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_goods['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_goods['pic_arr'][$key]['title'] = $value;
                $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
            }
        }

        $return = $database_shop_goods->format_spec_value($now_goods['spec_value'], $now_goods['goods_id']);

        $now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_goods['list'] = isset($return['list']) ? $return['list'] : '';
        $now_goods['discount_info'] = '';
        $now_goods['status'] = 1;
        if ($marketGoodsOld) {
            if ($marketGoodsOld['spec_value'] && $now_goods['list']) {
                $spec_array = explode('#', $marketGoodsOld['spec_value']);
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = str_replace(':', '_', $row_array[0]);
                    if (isset($now_goods['list'][$i])) {
                        $tArray = explode(':', $row_array[1]);
                        $now_goods['list'][$i]['wholesale_prices'] = $tArray[1];
                        $now_goods['list'][$i]['min_nums'] = $tArray[2];
                        $now_goods['list'][$i]['stocks'] = $tArray[3];
                        $now_goods['list'][$i]['group_team_prices'] = $tArray[5];
                        $now_goods['list'][$i]['group_join_prices'] = $tArray[6];
                        $now_goods['list'][$i]['vip_wholesale_prices'] = $tArray[7];
                        $now_goods['list'][$i]['goods_weights'] = $tArray[8];
                        $now_goods['list'][$i]['goods_volumes'] = $tArray[9];
                    }
                }
            }

            $now_goods['wholesale_price'] = $marketGoodsOld['price'];
            $now_goods['vip_wholesale_price'] = $marketGoodsOld['vip_wholesale_price'];
            $now_goods['min_num'] = $marketGoodsOld['min_num'];
            $now_goods['stock'] = $marketGoodsOld['stock_num'];
            $now_goods['discount_info'] = json_decode($marketGoodsOld['discount_info'], true);
            $now_goods['status'] = $marketGoodsOld['status'];
            $now_goods['cat_fid'] = $marketGoodsOld['cat_fid'];
            $now_goods['cat_id'] = $marketGoodsOld['cat_id'];
            // 是否加入白名单
            $now_goods['is_add_white_list'] = $marketGoodsOld['is_add_white_list'];
            /*拼团活动的配置信息*/
            $now_goods['is_group'] = $marketGoodsOld['is_group'];
            $now_goods['group_team_price'] = $marketGoodsOld['group_team_price'];
            $now_goods['group_join_price'] = $marketGoodsOld['group_join_price'];
            $now_goods['group_stime'] = $marketGoodsOld['group_stime'];
            $now_goods['group_ftime'] = $marketGoodsOld['group_ftime'];

            $day=floor($marketGoodsOld['group_affect_time']/(24*3600));
            $hour=floor(($marketGoodsOld['group_affect_time']-$day*24*3600)/3600);
            $minute=floor(($marketGoodsOld['group_affect_time']%3600)/60);

            $now_goods['day'] = $day;
            $now_goods['hour'] = $hour;
            $now_goods['minute'] = $minute;
            $now_goods['group_complete_num'] = $marketGoodsOld['group_complete_num'];
            $now_goods['min_unit'] = $marketGoodsOld['min_unit'];
            // 配送费计算方式
            $now_goods['freight_type'] = $marketGoodsOld['freight_type'];
            $now_goods['goods_weight'] = $marketGoodsOld['goods_weight'];
            $now_goods['goods_volume'] = $marketGoodsOld['goods_volume'];
            $now_goods['express_template_id'] = $marketGoodsOld['express_template_id'];
            $now_goods['express_fee'] = $marketGoodsOld['express_fee'];
            // 配送方式
            $now_goods['is_pick'] = $marketGoodsOld['is_pick'];
            $now_goods['pick_info'] = explode(',', $marketGoodsOld['pick_info']);
        }


        if (IS_POST) {
        	$shop_jump_type = $_POST['shop_jump_type']?:2;
            $error_tips ='';
            if (!$_POST['min_unit'] || $_POST['min_unit']<=0) {
                $this->error(L_('请填写大于0的最小批发单位！'));
            }

            if ($now_goods['spec_list']) {
                $wholesale_prices = $_POST['wholesale_prices'];
                $group_team_prices_arr = $_POST['group_team_prices_arr'];
                $group_join_prices_arr = $_POST['group_join_prices_arr'];

                if (1==$this->config['merchant_type_switch']) {
                    $vip_wholesale_prices = $_POST['vip_wholesale_prices'];
                }
                $freight_type = 1;
                $is_freight = 0; // 不需要衡量运费参数
                if (1==$this->config['merchant_integral_mall_control']) {
                    if (!$_POST['express_template_id'] || !$_POST['express_fee']) {
                        $this->error(L_('请选择运费模板及填写其他区域运费！'));
                    }
                    $freight_type = $_POST['freight_type']>0 ? $_POST['freight_type'] : 1; // 默认按件数计算配送费
                    $goods_weights = $_POST['goods_weights'];
                    $goods_volumes = $_POST['goods_volumes'];

                    $where_express = array('mer_id' => $this->merchant_session['mer_id'], 'id' => $_POST['express_template_id']);
                    $express_list = M('Market_express_template')->where($where_express)->find();
                    if (empty($express_list)) {
                        $this->error(L_('选择的模板信息不存在或者已经被删除！'));
                    }
                    $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $_POST['express_template_id']))->select();
                    if ($template_msgs) {
                        $freight_type_arr = array();
                        foreach ($template_msgs as $val) {
                            if (!$freight_type_arr[$val['tid']]) {
                                $freight_type_arr[$val['tid']] = array();
                            }
                            $freight_type_arr[$val['tid']][] = $val['freight_type'];
                        }
                        foreach ($freight_type_arr as $key=>$item) {
                            if (in_array(2, $item)) {
                                $is_freight = 1; // 重量-衡量运费参数
                            }
                            if (in_array(3, $item) && $is_freight==1) {
                                $is_freight = 3; // (体积 + 重量)-衡量运费参数
                            } elseif (in_array(3, $item)) {
                                $is_freight = 2; // 体积-衡量运费参数
                            }
                            $express_list['is_freight'] = $is_freight;
                        }

                    }
                }

                $min_nums = $_POST['min_nums'];
                $stocks = $_POST['stocks'];
                foreach ($wholesale_prices as $i => $val) {
                    if (empty($wholesale_prices[$i]) || empty($stocks[$i])) {
                        $error_tips .= '请正确设置每一种规格对应的批发参数<br/>';
                    }
                    if (empty($min_nums[$i]) || $min_nums[$i]<0) {
                        $min_nums[$i] = 0;
                    }
                    if (!empty($vip_wholesale_prices) && (!$vip_wholesale_prices[$i] && $vip_wholesale_prices[$i]<=0)) {
                        $error_tips .= '请正确设置每一种规格对应的VIP批发参数<br/>';
                    }
                    if ((1==$is_freight || 3==$is_freight) && (($goods_weights[$i] && $goods_weights[$i]<=0) || !$goods_weights[$i])) {
                        $error_tips .= '请正确设置每一种规格对应的大于0的重量<br/>';
                    }
                    if ((2==$is_freight || 3==$is_freight) && (($goods_volumes[$i] && $goods_volumes[$i]<=0) || !$goods_volumes[$i])) {
                        $error_tips .= '请正确设置每一种规格对应的大于0的体积<br/>';
                    }
                }
            } else {
                $wholesale_price = isset($_POST['wholesale_price']) ? floatval($_POST['wholesale_price']) : 0;
                if ($wholesale_price <= 0) {
                    $error_tips .= '请设置批发价且大于0<br/>';
                }
                if (1==$this->config['merchant_type_switch']) {
                    $vip_wholesale_price = isset($_POST['vip_wholesale_price']) ? floatval($_POST['vip_wholesale_price']) : 0;
                    if ($vip_wholesale_price <= 0) {
                        $error_tips .= '请设置VIP批发价且大于0<br/>';
                    }
                }

                $is_freight = 0; // 不需要衡量运费参数
                if (1==$this->config['merchant_integral_mall_control']) {
                    if (!$_POST['express_template_id'] || !$_POST['express_fee']) {
                        $this->error(L_('请选择运费模板及填写其他区域运费！'));
                    }
                    $freight_type = $_POST['freight_type']>0 ? $_POST['freight_type'] : 1; // 默认按件数计算配送费
                    $goods_weight = $_POST['goods_weight']>0 ? $_POST['goods_weight'] : 0;
                    $goods_volume = $_POST['goods_volume']>0 ? $_POST['goods_volume'] : 0;


                    $where_express = array('mer_id' => $this->merchant_session['mer_id'], 'id' => $_POST['express_template_id']);
                    $express_list = M('Market_express_template')->where($where_express)->find();
                    if (empty($express_list)) {
                        $this->error(L_('选择的模板信息不存在或者已经被删除！'));
                    }
                    $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $_POST['express_template_id']))->select();
                    if ($template_msgs) {
                        $freight_type_arr = array();
                        foreach ($template_msgs as $val) {
                            if (!$freight_type_arr[$val['tid']]) {
                                $freight_type_arr[$val['tid']] = array();
                            }
                            $freight_type_arr[$val['tid']][] = $val['freight_type'];
                        }
                        foreach ($freight_type_arr as $key=>$item) {
                            if (in_array(2, $item)) {
                                $is_freight = 1; // 重量-衡量运费参数
                            }
                            if (in_array(3, $item) && $is_freight==1) {
                                $is_freight = 3; // (体积 + 重量)-衡量运费参数
                            } elseif (in_array(3, $item)) {
                                $is_freight = 2; // 体积-衡量运费参数
                            }
                            $express_list['is_freight'] = $is_freight;
                        }

                    }

                    if ((1==$is_freight || 3==$is_freight) && $goods_weight<=0) {
                        $error_tips .= '请设置重量且大于0<br/>';
                    }
                    if ((2==$is_freight || 3==$is_freight) && $goods_volume<=0) {
                        $error_tips .= '请设置体积且大于0<br/>';
                    }
                }
                $min_num = isset($_POST['min_num']) ? intval($_POST['min_num']) : 0;
                if ($min_num <= 0) {
                    $error_tips .= '请设置最低批发数且大于0<br/>';
                }
                $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
                if ($stock <= 0) {
                    $error_tips .= '请设置库存且大于0<br/>';
                }
            }
            $is_group=I('is_group',0,'intval');
        	$group_team_price=I('group_team_price',0,'floatval');
        	$group_join_price=I('group_join_price',0,'floatval');

        	$group_stime=I('group_sdate','','strtotime');
        	$group_ftime=I('group_fdate','','strtotime');

        	$group_day=I('group_day',0,'intval');
        	$group_hour=I('group_hour',0,'intval');
        	$group_minute=I('group_minute',0,'intval');
        	$group_affect_time=$group_day*24*3600+$group_hour*3600+$group_minute*60;
        	$group_complete_num=I('group_complete_num',0,'intval');

        	if($is_group==1){
        		if(empty($group_stime) || empty($group_ftime) || $group_stime>=$group_ftime){
        			$error_tips.='请设置有效的拼团活动时间<br/>';
        		}
        		if($group_affect_time<=0){
        			$error_tips.='请设置有效的拼团活动时长<br/>';
        		}
        		if($group_complete_num<=1){
        			$error_tips.='请设置正确的拼团人数<br/>';
        		}
        	}

            $nums = $_POST['nums'];
            $discounts = $_POST['discounts'];
            $status = $this->config['market_goods_is_examine'] ? 0 : 1;

            if (empty($error_tips)) {
                $discount_info = array();
                for ($i = 0; $i < count($nums); $i ++) {
                    if ($nums[$i] && $discounts[$i]) {
                        $discount_info[] = array('num' => $nums[$i], 'discount' => $discounts[$i]);
                    }
                }
                $marketGoods = array('mer_id' => $this->merchant_session['mer_id'], 'last_time' => time());
                $marketGoods['store_id'] = $now_goods['store_id'];
                $marketGoods['goods_id'] = $now_goods['goods_id'];
                $marketGoods['name'] = $now_goods['name'];
                $marketGoods['number'] = $now_goods['number'];
                $marketGoods['unit'] = $now_goods['unit'];
                $marketGoods['image'] = $now_goods['image'];
                $marketGoods['des'] = $now_goods['des'];
                $marketGoods['is_properties'] = $now_goods['is_properties'];
                $marketGoods['status'] = $status;
                $marketGoods['discount_info'] = $discount_info ? json_encode($discount_info) : '';
                $marketGoods['cat_fid'] = isset($_POST['cat_fid']) ? intval($_POST['cat_fid']) : 0;
                $marketGoods['cat_id'] = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
                $marketGoods['province_id'] = isset($now_store['province_id']) ? intval($now_store['province_id']) : 0;
                $marketGoods['city_id'] = isset($now_store['city_id']) ? intval($now_store['city_id']) : 0;
                $marketGoods['area_id'] = isset($now_store['area_id']) ? intval($now_store['area_id']) : 0;
                // 白名单信息
                $marketGoods['white_list'] = $now_goods['white_list'];
                // 选择是后必须是加入白名单的商户才能在批发市场中查看和批发 1 选择 0不选择（所有商户可看）
                $marketGoods['is_add_white_list'] = $_POST['is_add_white_list']>0?1:0;
                /*新增拼团活动配置*/
                $marketGoods['is_group']=$is_group;
                $marketGoods['group_team_price']=$group_team_price;
                $marketGoods['group_join_price']=$group_join_price;
                $marketGoods['group_stime']=$group_stime;
                $marketGoods['group_ftime']=$group_ftime;
                $marketGoods['group_affect_time']=$group_affect_time;
                $marketGoods['group_complete_num']=$group_complete_num;
                // 配送计费方式
                $marketGoods['freight_type']= $freight_type>0 ? $freight_type : 0;
                $marketGoods['express_template_id']= $_POST['express_template_id'] > 0 ? $_POST['express_template_id'] : 0;
                $marketGoods['express_fee'] = $_POST['express_fee'] > 0 ? $_POST['express_fee'] : 0;
                $marketGoods['min_unit'] = $_POST['min_unit'] > 0 ? $_POST['min_unit'] : 0;
                if (1==$this->config['merchant_integral_mall_control'] && (!$_POST['express_template_id'] || !$_POST['express_fee'])) {
                    $this->error(L_('该商品不能发布！'));
                }

                if (empty($marketGoods['cat_fid']) || empty($marketGoods['cat_id'])) {
                    $this->error(L_('请选择商品分类！'));
                }
                if (empty($now_goods['spec_list'])) {
                    $marketGoods['min_num'] = $min_num;
                    $marketGoods['stock_num'] = $stock;
                    $marketGoods['price'] = $wholesale_price;
                    if ($vip_wholesale_price) {
                        $marketGoods['vip_wholesale_price']=$vip_wholesale_price;
                    }
                    if ($goods_weight) {
                        $marketGoods['goods_weight']=$goods_weight;
                    }
                    if ($goods_volume) {
                        $marketGoods['goods_volume']=$goods_volume;
                    }
                }
                // 自提相关信息
                if (isset($_POST['is_pick'])) {
                    $marketGoods['is_pick'] = intval($_POST['is_pick']);
                    if ($_POST['is_pick'] == 1 && empty($_POST['pick_info'])) {
                        $this->error(L_('请选择自提地点！'));
                    } elseif ($_POST['pick_info']) {
                        $marketGoods['pick_info'] = implode(',', $_POST['pick_info']);
                    }
                }
                if ($marketGoodsOld) {
                    if ($mid = M('Market_goods')->where(array('mid' => $marketGoodsOld['mid']))->save($marketGoods)) {
                        $mid = $marketGoodsOld['mid'];
                        //删除已有的属性与规格的数据
                        M('Market_goods_properties')->where($where)->delete();
                        $spec_list = M('Market_goods_spec')->field(true)->where($where)->select();
                        foreach ($spec_list as $spec) {
                            M('Market_goods_spec_value')->where(array('sid' => $spec['id']))->delete();
                        }
                        M('Market_goods_spec')->where($where)->delete();
                    }
                } else {
                    $mid = M('Market_goods')->add($marketGoods);
                }

                if ($mid) {
                    if ($now_goods['is_properties']) {
                        $properties = M('Shop_goods_properties')->field(true)->where(array('goods_id' => $now_goods['goods_id']))->select();
                        foreach ($properties as $pro_data) {
                            M('Market_goods_properties')->add($pro_data);
                        }
                    }

                    if ($now_goods['spec_value']) {
                        $spec_list = M('Shop_goods_spec')->field(true)->where(array('goods_id' => $now_goods['goods_id']))->select();
                        foreach ($spec_list as $spec) {
                            if (M('Market_goods_spec')->add($spec)) {
                                $spec_value_list = M('Shop_goods_spec_value')->field(true)->where(array('sid' => $spec['id']))->select();
                                foreach ($spec_value_list as $spec_value) {
                                    M('Market_goods_spec_value')->add($spec_value);
                                }
                            }
                        }
                        //[old] => 规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号 # 规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#...#规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号
                        //[new] => 规格值ID:规格值ID:...:规格值ID|old_price:price:min_num:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                        $spec_array = explode('#', $now_goods['spec_value']);
                        $target_spec_value_array = array();
                        $stock_num = 0;
                        $wholesale_price = 0;
                        $vip_wholesale_price = 0;
                        $min_num = 0;

                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                            $row_array[1] = $wholesale_prices[$i] . ':' . $wholesale_prices[$i] . ':' . $min_nums[$i] . ':' . $stocks[$i] . ':' . $wholesale_prices[$i];
                            //新增拼团活动信息
                            $row_array[1].=':'.$group_team_prices_arr[$i];
                            $row_array[1].=':'.$group_join_prices_arr[$i];
                            // 如果存在vip价格加上vip价格 占据第8个位置
                            $row_array[1].=':'.$vip_wholesale_prices[$i];
                            // 如果存在配送费计费方式【重量】加上配送费计费方式【重量】 占据第9个位置
                            $row_array[1].=':'.$goods_weights[$i];
                            // 如果存在配送费计费方式【体积】加上配送费计费方式【体积】 占据第10个位置
                            $row_array[1].=':'.$goods_volumes[$i];

                            $marketGoods['min_num'] = $min_num;
                            if ($wholesale_price > 0) {
                                $wholesale_price = min($wholesale_price, $wholesale_prices[$i]);
                            } else {
                                $wholesale_price = $wholesale_prices[$i];
                            }
                            if ($vip_wholesale_price > 0) {
                                $vip_wholesale_price = min($vip_wholesale_price, $vip_wholesale_prices[$i]);
                            } else {
                                $vip_wholesale_price = $vip_wholesale_prices[$i];
                            }
                            if ($group_team_price > 0) {
                                $group_team_price = min($group_team_price, $group_team_prices_arr[$i]);
                            } else {
                                $group_team_price = $group_team_prices_arr[$i];
                            }
                            if ($min_num > 0) {
                                $min_num = min($min_num, $min_nums[$i]);
                            } else {
                                $min_num = $min_nums[$i];
                            }
                            $stock_num += $stocks[$i];
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $data = array('spec_value' => implode('#', $target_spec_value_array));
                        $data['min_num'] = $min_num;
                        $data['stock_num'] = $stock_num;
                        $data['price'] = $wholesale_price;
                        $data['vip_wholesale_price'] = $vip_wholesale_price;
                        $data['group_team_price'] = $group_team_price;
                        M('Market_goods')->where(array('mid' => $mid))->save($data);
                    }
                    if($shop_jump_type==1){
						$this->success(L_('发布成功！'), U('Shop/goods_list',['store_id'=>$now_goods['store_id'],'sort_id'=>$now_goods['sort_id']]));
					}else{
						$this->success(L_('发布成功！'), U('Market/index'));
					}
                } else {
                    $this->error(L_('发布失败！请重试！'));
                }
            } else {
                $this->error($error_tips);
            }
        } else {
            if(!empty($marketGoodsOld)){
                $where=array();
                $where['group_id']=$marketGoodsOld['mid'];
                $where['status']=0;
                $count=M('Market_group_team')->where($where)->count(1);
                if($count>0){
                    $now_goods['no_edit']=1;
                }
            }
            $pick_addr = D('Pick_address')->get_pick_addr_by_merid($this->merchant_session['mer_id'],true);
            $this->assign('pick_addr', $pick_addr);
            $category_list = D('Goods_wholesale_category')->get_list();
            $this->assign('category_list', json_encode($category_list));
            $this->assign('now_goods', $now_goods);
            $this->assign('now_store', $now_store);
            // 运费模板信息
            if (1==$this->config['merchant_integral_mall_control']) {
                $where_express = array('mer_id' => $this->merchant_session['mer_id']);
                $express_list = M('Market_express_template')->where($where_express)->select();
                if (empty($express_list)) {
                    $this->error(L_('请先添加运费模板！'));
                }
                $list = array();
                $tids = array();
                foreach ($express_list as $row) {
                    $tids[] = $row['id'];
                    $list[$row['id']] = $row;
                }
                $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => array('in', $tids)))->select();
                if ($template_msgs) {
                    $freight_type_arr = array();
                    foreach ($template_msgs as $val) {
                        if (!$freight_type_arr[$val['tid']]) {
                            $freight_type_arr[$val['tid']] = array();
                        }
                        $freight_type_arr[$val['tid']][] = $val['freight_type'];
                    }
                    foreach ($freight_type_arr as $key=>$item) {
                        $is_freight = 0; // 不需要衡量运费参数
                        fdump($item, '$item', true);
                        if (in_array(2, $item)) {
                            $is_freight = 1; // 重量-衡量运费参数
                        }
                        if (in_array(3, $item) && $is_freight==1) {
                            $is_freight = 3; // (体积 + 重量)-衡量运费参数
                        } elseif (in_array(3, $item)) {
                            $is_freight = 2; // 体积-衡量运费参数
                        }
                        $list[$key]['is_freight'] = $is_freight;
                    }

                }
                $list = array_values($list);
                $this->assign('express_list', $list);
            }
            $this->display();
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
        $where = array('status' => 1);
        if(!empty($_GET['keyword'])){
            $where['name'] = array('like', '%' . htmlspecialchars(trim($_GET['keyword'])) . '%');
        }

        if (intval($_GET['sort_id'])) {
            $where['sort_id'] = intval($_GET['sort_id']);
        }

        $systemGoods = D('System_goods');
        $count= $systemGoods->where($where)->count();
        $p = new Page($count, 5);
        $goods_list = $systemGoods->field(true)->where($where)->order('`goods_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $goods_image_class = new goods_image();
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
        }
        $this->assign('goods_list', $goods_list);
        $this->assign('page', $p->show());
        $this->display();
    }

    public function ajax_goods()
    {
        $goods_id = isset($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;
        $number = isset($_GET['number']) ? htmlspecialchars(trim($_GET['number'])) : '';
        $systemGoods = D('System_goods');
        $condition = array();
        if ($goods_id) {
            $condition['goods_id'] = $goods_id;
        }
        if ($number) {
            $condition['number'] = $number;
        }
        if (empty($condition)) {
            exit(json_encode(array('error' => true, 'msg' => L_('请输入查询条件'))));
        }
        $now_goods = $systemGoods->field(true)->where($condition)->find();
        if (empty($now_goods)) {
            exit(json_encode(array('error' => true, 'msg' => L_('商品不存在'))));
        }
        if(!empty($now_goods['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_goods['image']);
            $image = '';
            $title = '';
            foreach ($tmp_pic_arr as $key => $value) {
                if (empty($image)) {
                    $title = $value;
                    $image = $goods_image_class->get_image_by_path($value, 's');
                }
            }
            $now_goods['image'] = $image;
            $now_goods['imageTitle'] = $title;
        }
        exit(json_encode(array('error' => false, 'data' => $now_goods)));
    }


    public function goodsToFood()
    {
        if ($this->merchant_session['store_id']) {
            $this->error(L_('您没有这个权限'));
        }
        $source_store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : 0;

        if (empty($store_ids)) {
            $this->error(L_('请选择要同步的店铺'));
        }
        if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $source_store_id, 'have_shop' => 1))->find()) {
            if (!($shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $source_store_id))->find())) {
                $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['shop_alias_name']));
            }
        } else {
            $this->error(L_('店铺不存在，或是店铺没有开启X1,所以不能被克隆商品',$this->config['shop_alias_name']));
        }

        $goods_image_class = new goods_image();
		$file_handle = new file_handle();

        foreach ($store_ids as $store_id) {
            if ($target_store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_meal' => 1))->find()) {
                if (!($target_shop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $store_id))->find())) {
                    continue;
                }
            } else {
                continue;
            }

            $goods_sorts = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $source_store_id))->select();
            foreach ($goods_sorts as $sv) {
                $oldIds[$sv['sort_id']] = $sv['fid'];//id ==> fid
            }
            $listOldFidToNewFid = array();
            foreach ($goods_sorts as $sort) {
                $source_sort_id = $sort['sort_id'];
                if (!($goods_list = M('Shop_goods')->field(true)->where(array('store_id' => $source_store_id, 'sort_id' => $source_sort_id))->select())) {
                    continue;
                }
                //目标分类
                if ($target_sort = M('Foodshop_goods_sort')->field(true)->where(array('store_id' => $store_id, 'sort_name' => $sort['sort_name']))->find()) {
                    $target_sort_id = $target_sort['sort_id'];
                } else {
                    $sort['store_id'] = $store_id;
                    unset($sort['sort_id']);

                    $target_sort_id = M('Foodshop_goods_sort')->add($sort);
                }
                $listOldFidToNewFid[$source_sort_id] = $target_sort_id;//oldID ==> newID

                foreach ($goods_list as $goods) {
                    /*if(!empty($goods['image'])){
                        $tmp_pic_arr = explode(';', $goods['image']);
                        foreach ($tmp_pic_arr as $key => $value) {
                            $image_tmp = explode(',', $value);
                            if (strstr($image_tmp[0], 'sysgoods_')) {
                                continue;
                            }
                            $images = $goods_image_class->get_image_by_path($value);
                            foreach ($images as $img) {
								$img = $file_handle->get_path($img);
                                $newFile = './upload/foodshop_goods/' . str_replace('/upload/goods/', '', $img);
                                dmkdir($newFile, 0777, false);
                                $file_handle->download($img);
                                $t = copy('.' . $img, $newFile);
								$file_handle->upload($newFile);
                            }
                        }
                    }*/
                    if ($tmp_goods = M('Foodshop_goods')->field(true)->where(array('name' => $goods['name'], 'store_id' => $store_id))->find()) {
                        continue;
                    } else {
                        $source_goods_id = $goods['goods_id'];
                        unset($goods['goods_id']);
                        unset($goods['seckill_price']);
                        unset($goods['seckill_open_time']);
                        unset($goods['seckill_close_time']);
                        unset($goods['seckill_type']);
                        unset($goods['seckill_stock']);
                        $goods['store_id'] = $store_id;
                        $goods['sort_id'] = $target_sort_id;
                        $goods['print_id'] = 0;
                        $target_goods_id = M('Foodshop_goods')->add($goods);
                        $pro_map = $spec_map = $spec_value_map = array();
                        if ($goods['is_properties']) {
                            $properties = M('Shop_goods_properties')->field(true)->where(array('goods_id' => $source_goods_id))->select();
                            foreach ($properties as $pro_data) {
                                $source_pro_id = $pro_data['id'];
                                unset($pro_data['id']);
                                $pro_data['goods_id'] = $target_goods_id;
                                $pro_map[$source_pro_id] = M('Foodshop_goods_properties')->add($pro_data);
                            }
                        }
                        if ($goods['spec_value']) {
                            $spec_list = M('Shop_goods_spec')->field(true)->where(array('goods_id' => $source_goods_id, 'store_id' => $source_store_id))->select();
                            foreach ($spec_list as $spec) {
                                $source_spec_id = $spec['id'];
                                unset($spec['id']);

                                $spec['store_id'] = $store_id;
                                $spec['goods_id'] = $target_goods_id;
                                if ($new_spec_id = M('Foodshop_goods_spec')->add($spec)) {

                                    $spec_value_list = M('Shop_goods_spec_value')->field(true)->where(array('sid' => $source_spec_id))->select();
                                    foreach ($spec_value_list as $spec_value) {
                                        $source_spec_value_id = $spec_value['id'];
                                        unset($spec_value['id']);
                                        $spec_value['sid'] = $new_spec_id;
                                        $spec_value_map[$source_spec_value_id] = M('Foodshop_goods_spec_value')->add($spec_value);
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
                            M('Foodshop_goods')->where(array('goods_id' => $target_goods_id))->save(array('spec_value' => implode('#', $target_spec_value_array)));
                        }
                    }
                }
            }
            $this->success(L_('克隆完成'));
        }
    }

    public function see_goods_qrcode()
    {
        $this->display();
    }

    public function cardqrcode()
    {
        import('@.ORG.phpqrcode');
        $goodsId = $_GET['code'];
        $storeId = M('Shop_goods')->where(['goods_id' => $goodsId])->getField('store_id');
        $href = $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/goodsDetail/goodsDetail?store_id=' . $storeId . '&product_id=' . $goodsId;
        //$href = $this->config['site_url'] . '/wap.php?c=Shop&a=detail&goods_id=' . $_GET['code'];
        QRcode::png($href, false, 2, 10, 2);
    }

    public function zbw()
    {
        $store_id = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0;

        if ($store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
            if ($shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find()) {
            } else {
                $this->error(L_('店铺不存在，或是店铺没有开启') . $this->config['shop_alias_name']);
            }
        } else {
            $this->error(L_('店铺不存在，或是店铺没有开启') . $this->config['shop_alias_name']);
        }
        if (IS_POST) {
            $zbw_sBranchNo = isset($_POST['zbw_sBranchNo']) ? htmlspecialchars(trim($_POST['zbw_sBranchNo'])) : '';
            if (empty($zbw_sBranchNo)) {
                $this->error(L_('机构编码不能为空'));
            }
            if (D('Merchant_store_shop')->where(array('store_id' => $store_id))->save(array('zbw_sBranchNo' => $zbw_sBranchNo))) {
                $this->success(L_('操作成功'));
            } else {
                $this->error(L_('操作失败，稍后重试'));
            }
        } else {
            $this->assign('store_id', $store_id);
            $this->display();
        }
    }

    public function syncZbw()
    {
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $zbw = $this->getZbwObj();
        if (is_array($zbw)) {
            exit(json_encode($zbw));
        }
        $zbwInfo = D('Merchant_store_shop')->field('zbw_sBranchNo')->where(array('store_id' => $storeId))->find();
        if (empty($zbwInfo) || empty($zbwInfo['zbw_sBranchNo'])) {
            exit(json_encode(array('sCode' => 1, 'sError' => L_('请先配置对接的信息'))));
        }
//         if ($zbwInfo) {
//             $zbw = new ZhiBaiWei();
//             if ($zbwInfo['expiresTime'] < time()) {
//                 $tokenResult = $zbw->getToken($zbwInfo['sAppKey'], $zbwInfo['sAppCode']);
//                 if ($tokenResult['sCode'] == 0) {
//                     D('Merchant_store_zbw')->where(array('storeId' => $storeId))->save(array('sToken' => $tokenResult['sToken'], 'expiresTime' => time() + 600));
//                     $zbw->setToken($tokenResult['sToken']);
//                 } else {
//                     exit(json_encode($tokenResult));
//                     exit(json_encode(array('error' => true, 'msg' => '获取token失败')));
//                 }
//             } else {
//                 $zbw->setToken($zbwInfo['sToken']);
//             }

            //分类同步处理
            $shopGoodsSortDB = D('Shop_goods_sort');
            $page = 1;
            while ($page) {
                $clsList = $zbw->getItemCls($page);
                if ($clsList['sCode'] == 0) {
                    if (!empty($clsList['ItemClss'])) {
                        foreach ($clsList['ItemClss'] as $item) {
                            if ($tempSort = $shopGoodsSortDB->field(true)->where(array('clsNo' => $item['clsNo'], 'store_id' => $storeId))->find()) {
                                continue;
                            }
                            $fclsNo = substr($item['clsNo'], 0, -2);
                            $fid = 0;
                            if (!empty($fclsNo)) {
                                if ($thisSort = $shopGoodsSortDB->field(true)->where(array('clsNo' => $fclsNo, 'store_id' => $storeId))->find()) {
                                    $level = $thisSort['level'] + 1;
                                    $fid = $thisSort['sort_id'];
                                    if ($thisSort['operation_type'] != 1) {
                                        $shopGoodsSortDB->where(array('clsNo' => $fclsNo, 'store_id' => $storeId))->save(array('operation_type' => 1));
                                    }
                                } else {
                                    $level = 1;
                                }
                            } else {
                                $level = 1;
                            }

                            $data = array('store_id' => $storeId, 'status' => 1);
                            $data['sort_name'] = $item['clsName'];
                            $data['clsNo'] = $item['clsNo'];
                            $data['level'] = $level;
                            $data['fid'] = $fid;
                            if ($level == 3) {
                                $data['operation_type'] = 0;
                            } else {
                                $data['operation_type'] = 2;
                            }
                            $shopGoodsSortDB->add($data);
                        }
                        $page ++;
                    } else {
                        $page = 0;
                    }
                } else {
                    $page = 0;
                    exit(json_encode($clsList));
                }
            }
            exit(json_encode(array('sCode' => 0, 'sError' => 'ok')));
//         } else {
//             exit(json_encode(array('sCode' => 1, 'sError' => '请先配置对接的信息')));
//         }
    }

    private function getZbwObj()
    {
        $list = D('Config')->field('name, value')->where(array('name' => array('in', array('zbw_sAppKey', 'zbw_sAppCode', 'zbw_sToken', 'zbw_expiresTime'))))->select();
        $config = array();
        foreach ($list as $row) {
            $config[$row['name']] = $row['value'];
        }

        $zbw = new ZhiBaiWei();
        if ($config['zbw_expiresTime'] < time()) {
            $tokenResult = $zbw->getToken($config['zbw_sAppKey'], $config['zbw_sAppCode']);
            if ($tokenResult['sCode'] == 0) {
                D('Config')->where(array('name' => 'zbw_sToken'))->save(array('value' => $tokenResult['sToken']));
                D('Config')->where(array('name' => 'zbw_expiresTime'))->save(array('value' => time() + 600));

                $zbw->setToken($tokenResult['sToken']);
            } else {
                return $tokenResult;
            }
        } else {
            $zbw->setToken($config['zbw_sToken']);
        }
        return $zbw;
    }

    public function syncZbwGoods()
    {
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;

        $zbw = $this->getZbwObj();
        if (is_array($zbw)) {
            exit(json_encode($zbw));
        }
        $zbwInfo = D('Merchant_store_shop')->field('zbw_sBranchNo')->where(array('store_id' => $storeId))->find();
        if (empty($zbwInfo) || empty($zbwInfo['zbw_sBranchNo'])) {
            exit(json_encode(array('sCode' => 1, 'sError' => L_('请先配置对接的信息'))));
        }
//         if ($zbwInfo) {
//             $zbw = new ZhiBaiWei();
//             if ($zbwInfo['expiresTime'] < time()) {
//                 $tokenResult = $zbw->getToken($zbwInfo['sAppKey'], $zbwInfo['sAppCode']);
//                 if ($tokenResult['sCode'] == 0) {
//                     D('Merchant_store_zbw')->where(array('storeId' => $storeId))->save(array('sToken' => $tokenResult['sToken'], 'expiresTime' => time() + 600));
//                     $zbw->setToken($tokenResult['sToken']);
//                 } else {
//                     exit(json_encode($tokenResult));
//                     exit(json_encode(array('error' => true, 'msg' => '获取token失败')));
//                 }
//             } else {
//                 $zbw->setToken($zbwInfo['sToken']);
//             }

            //商品同步处理
            $shopGoodsSortDB = D('Shop_goods_sort');
            $shopGoodsDB = D('Shop_goods');
            $page = isset($_POST['page']) ? intval($_POST['page']) : 0;
            $last_time = time();
            $goodsList = $zbw->getGoods($page);
            if ($goodsList['sCode'] == 0) {
                if (!empty($goodsList['goodsIds'])) {
                    $goodsIdLIst = array();
                    foreach ($goodsList['goodsIds'] as $item) {
                        $goodsIdLIst[] = $item['goodsId'];
                        if ($tempGoods = $shopGoodsDB->field(true)->where(array('zbwId' => $item['goodsId'], 'store_id' => $storeId))->find()) {
                            continue;
                        }
                        $sort_id = 0;
                        if ($thisSort = $shopGoodsSortDB->field(true)->where(array('clsNo' => $item['clsNo'], 'store_id' => $storeId))->find()) {
                            $sort_id = $thisSort['sort_id'];
                            if ($thisSort['operation_type'] != 0) {
                                $shopGoodsSortDB->where(array('clsNo' => $item['clsNo'], 'store_id' => $storeId))->save(array('operation_type' => 0));
                            }
                        }

                        $data = array('store_id' => $storeId);
                        $data['name'] = $item['name'];
                        $data['number'] = $item['barcode'];
                        $data['zbwId'] = $item['goodsId'];
                        $data['unit'] = $item['unit'];
                        $data['price'] = $item['salePrice'];
                        $data['sort_id'] = $sort_id;
                        $data['last_time'] = $last_time;
                        $shopGoodsDB->add($data);
                    }

                    $stockList = $zbw->goodsStock($goodsIdLIst, $zbwInfo['zbw_sBranchNo']);
                    if ($stockList['sCode'] == 0 && !empty($stockList['stocks'])) {
                        foreach ($stockList['stocks'] as $stock) {
                            $shopGoodsDB->where(array('zbwId' => $stock['goodsId'], 'store_id' => $storeId))->save(array('stock_num' => $stock['stockCnt']));
                        }
                    }
                    $page ++;
                    exit(json_encode(array('sCode' => 0, 'sError' => 'ok', 'page' => $page)));
                }
            }
            exit(json_encode(array('sCode' => 0, 'sError' => 'ok', 'page' => 0)));
//         } else {
//             exit(json_encode(array('sCode' => 1, 'sError' => '请先配置对接的信息')));
//         }
    }

    public function test()
    {
        $zbw = $this->getZbwObj();
        $stockList = $zbw->getItemImage('PK02534');
        echo '<pre/>';
        print_r($stockList);die;
    }

	public function shop_fitment_color(){
		if(IS_POST){
			$now_store = $this->check_store($_POST['store_id']);

			$condition['store_id']		= $_POST['store_id'];
			$data['shop_fitment_color'] = $_POST['shop_fitment_color'];
			$data['category_limit_num_bg'] = $_POST['category_limit_num_bg'];
			$data['last_time']			= time();
			if(M('Merchant_store_shop')->where($condition)->data($data)->save()){
				$this->success(L_('保存成功'));
			}else{
				$this->error(L_('保存失败'));
			}
		}else{
			$now_store = $this->check_store($_GET['store_id']);
			$this->assign('now_store',$now_store);
		}

		$this->display();
	}

	public function shop_fitment_showcase(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['showcase_good_id']);
        $goodArr = M('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id' => array('in', $goodIdArr)))->order('FIND_IN_SET(goods_id,"' . $subject_info['showcase_good_id'] . '")')->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
	}

	public function shop_fitment_showcase_save(){
		$now_store = $this->check_store($_POST['store_id']);

		$condition['store_id']		= $_POST['store_id'];
		$data['shop_showcase_show'] = $_POST['showcase_show'];
		$data['last_time']			= time();
		if(M('Merchant_store_shop')->where($condition)->data($data)->save()){
			$condition_data['store_id'] = $_POST['store_id'];
			$data_data['showcase_name']	= $_POST['showcase_name'];
			$data_data['showcase_good_id']	= implode(',',$_POST['good_id']);
			if(!$data_data['showcase_good_id']){
				$data_data['showcase_good_id'] = array();
			}
			$data_data['last_time']			= time();
			if(M('Merchant_store_shop_data')->where($condition)->find()){
				if(M('Merchant_store_shop_data')->where($condition_data)->data($data_data)->save()){
					$this->success(L_('保存成功！'));
				}else{
					$this->error(L_('保存失败！'));
				}
			}else{
				$data_data['store_id'] = $_POST['store_id'];
				if(M('Merchant_store_shop_data')->data($data_data)->add()){
					$this->success(L_('保存成功。'));
				}else{
					$this->error(L_('保存失败。'));
				}
			}
		}else{
			$this->error(L_('保存失败!.'));
		}
	}

	public function shop_fitment_subject(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['subject_good_id']);
		$goodArr = M('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
	}

	public function shop_fitment_history(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		if(IS_POST){
			$condition['store_id'] = $now_store['store_id'];
			$data['shop_brand_weipage'] = $_POST['shop_brand_weipage'];
			$data['last_time'] = time();
			if(M('Merchant_store_shop')->where($condition)->data($data)->save()){
				$this->success(L_('保存成功！'));
			}else{
				$this->error(L_('保存失败！'));
			}
		}else{
			//$diypage_list = M('Merchant_store_diypage')->field('`page_id`,`page_name`')->where(array('mer_id'=>$this->mer_id,'is_remove'=>'0'))->select();
			$diypage_list = M('Micro_page_decorate')->where(['source'=>'store','source_id'=>$now_store['store_id'],'type'=>1])->field('id AS page_id,page_title AS page_name')->select();
            $this->assign('diypage_list',$diypage_list);

			$this->display();
		}
	}

	public function shop_fitment_subject_save(){
		$now_store = $this->check_store($_POST['store_id']);

		$condition['store_id']		= $_POST['store_id'];
		$data['shop_subject_show'] = $_POST['subject_show'];
		$data['last_time']			= time();
		if(M('Merchant_store_shop')->where($condition)->data($data)->save()){
			$condition_data['store_id'] = $_POST['store_id'];
			$data_data['subject_name']	= $_POST['subject_name'];
            if ($_POST['subject_pic']) {
                $info = pathinfo($_POST['subject_pic']);
                $_POST['subject_pic'] = $info['dirname'] . '/' . $info['filename'] . '.' . ($info['extension'] ?: 'png');
            }
            $data_data['subject_pic'] = $_POST['subject_pic'] . '?t=' . time();
			$data_data['subject_good_id']	= implode(',',$_POST['good_id']);
			if(!$data_data['subject_good_id']){
				$data_data['subject_good_id'] = array();
			}
			$data_data['last_time']			= time();
			if(M('Merchant_store_shop_data')->where($condition)->find()){
				if(M('Merchant_store_shop_data')->where($condition_data)->data($data_data)->save()){
					$this->success(L_('保存成功'));
				}else{
					$this->error(L_('保存失败！'));
				}
			}else{
				$data_data['store_id'] = $_POST['store_id'];
				if(M('Merchant_store_shop_data')->data($data_data)->add()){
					$this->success(L_('保存成功'));
				}else{
					$this->error(L_('保存失败。'));
				}
			}
		}else{
			$this->error(L_('保存失败！.'));
		}
	}

	public function shop_fitment_build_subject_pic(){
		$this->display();
	}

	public function shop_fitment_build_subject_pic_func(){
		if(empty($_POST)){
			$_POST = array (
			  'img' => './static/images/shop_fitment/showcase_img1.png',
			  'big_left' => '50',
			  'big_top' => '35',
			  'big_font' => '40',
			  'small_left' => '50',
			  'small_top' => '116',
			  'small_font' => '24',
			  'good_left' => '426',
			  'good_top' => '22',
			  'good_width' => '232',
			  'good_height' => '132',
			  'big_color' => '#432D0A',
			  'small_color' => '#68502E',
			  'type' => '1',
			  'good_img_width' => '510',
			  'good_img_height' => '383',
			  'good_img_src' => 'http://www.group.com/upload/goods/000/000/001/m_5b9ca7bb56170374.jpg',
			  'big_name' => L_('优选食材精品推荐'),
			  'small_name' => L_('满60减20 满20减10'),
			  'big_color_arr' =>
			  array (
				0 => '67',
				1 => '45',
				2 => '10',
			  ),
			  'small_color_arr' =>
			  array (
				0 => '104',
				1 => '80',
				2 => '46',
			  ),
			  'store_id' => '356',
			);
		}
		$now_store = $this->check_store($_POST['store_id']);
		$bgImgSrc = $_POST['img'];
		$bgImgSrc = str_replace($this->config['site_url'],'',$bgImgSrc);
		$bgImgSrc = ltrim($bgImgSrc,'/');
		$bgImgSrc = ltrim($bgImgSrc,'./');

		$goodImgSrc = $_POST['good_img_src'];
		$goodImgSrc = str_replace($this->config['site_url'],'',$goodImgSrc);
		$goodImgSrc = ltrim($goodImgSrc,'/');
		$goodImgSrc = ltrim($goodImgSrc,'./');

		//画布
		$font = './static/fonts/apple_lihei.otf';
		$fontBold = './static/fonts/apple_lihei_bold.otf';

		$img = imagecreatetruecolor(686,176);
		$white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
		imagefill ( $img ,  0 ,   0 ,  $white );

		//背景图片绘制
        $src_im = imagecreatefrompng($bgImgSrc);
        imagecopy($img,$src_im,0,0,0,0,686,176);

		//写主标题
		$fontColor = imagecolorallocate ($img, $_POST['big_color_arr'][0], $_POST['big_color_arr'][1], $_POST['big_color_arr'][2]);
		$fontSize = intval($_POST['big_font']*0.7);
		imagettftext($img, $fontSize, 0, $_POST['big_left'], $_POST['big_top']+35, $fontColor, $fontBold, $_POST['big_name']);
		// imagettftext($img, $fontSize, 0, $_POST['big_left']+1, $_POST['big_top']+36, $fontColor, $font, $_POST['big_name']);

		//写副标题
		$fontColor = imagecolorallocate ($img, $_POST['small_color_arr'][0], $_POST['small_color_arr'][1], $_POST['small_color_arr'][2]);
		$fontSize = intval($_POST['small_font']*0.7);
		$fontTop = $_POST['small_top']+22;
		if($_POST['type'] == '2'){
			$fontTop+= 2;
		}
		imagettftext($img, $fontSize, 0, $_POST['small_left'], $fontTop , $fontColor, $font, $_POST['small_name']);

        //商品图片绘制
        $info = getimagesize($goodImgSrc);
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $src_im = call_user_func_array($fun, array($goodImgSrc));

        //创建新图像
        $newimg = imagecreatetruecolor($_POST['good_width'], $_POST['good_height']);
        // 调整默认颜色
        $color = imagecolorallocate($newimg, 255, 255, 255);
        imagefill($newimg, 0, 0, $color);
        //裁剪
        //等比例缩小高度居中
        $goodImageTop = intval($info[1] - ($info[0] * $_POST['good_height'] / $_POST['good_width']));
        // echo $goodImageTop;die;
        imagecopyresampled($newimg, $src_im, 0, 0, 0, $goodImageTop / 2, $_POST['good_width'], $_POST['good_height'], $info[0], $info[1] - $goodImageTop);
        imagedestroy($src_im); //销毁原图
        imagecopy($img, $newimg, $_POST['good_left'], $_POST['good_top'], 0, 0, $_POST['good_width'], $_POST['good_height']);

		// header('Content-type: image/png');
		// imagepng($img);
		// die;
		$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
		$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

		$fileName = 'upload/store/'.$rand_num;
		if(!file_exists($fileName)){
			mkdir($fileName,0777,true);
		}
		$fileName.= '/'.$_POST['store_id'].'_subject_'.time().'.png';
		if(imagepng($img,$fileName)){
            (new file_handle())->upload($fileName);
			$this->success($fileName);
		}else{
			$this->error(L_('生成失败，请重试'));
		}
	}

	public function shop_fitment_category_hot(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['cat_hot_good_id']);
		$goodArr = M('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
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
		if(M('Merchant_store_shop_data')->where($condition_data)->find()){
			if(M('Merchant_store_shop_data')->where($condition_data)->data($data_data)->save()){
				$this->success(L_('保存成功！'));
			}else{
				$this->error(L_('保存失败！'));
			}
		}else{
			$data_data['store_id'] = $_POST['store_id'];
			if(M('Merchant_store_shop_data')->data($data_data)->add()){
				$this->success(L_('保存成功。'));
			}else{
				$this->error(L_('保存失败。'));
			}
		}
	}

	public function shop_fitment_category_discount(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
		$this->assign('subject_info',$subject_info);

		$goodIdArr = explode(',',$subject_info['cat_discount_good_id']);
		$goodArr = M('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id'=>array('in',$goodIdArr)))->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
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
		if(M('Merchant_store_shop_data')->where($condition_data)->find()){
			if(M('Merchant_store_shop_data')->where($condition_data)->data($data_data)->save()){
				$this->success(L_('保存成功！'));
			}else{
				$this->error(L_('保存失败！'));
			}
		}else{
			$data_data['store_id'] = $_POST['store_id'];
			if(M('Merchant_store_shop_data')->data($data_data)->add()){
				$this->success(L_('保存成功。'));
			}else{
				$this->error(L_('保存失败。'));
			}
		}
	}

    // 商家批发市场相关权限
    public function capture_merchant_info() {
        $goods_id = I('goods_id','','trim');
        // 查询一下相关物品信息
        $db_shop_goods = M('Shop_goods');
        $goods_info = $db_shop_goods->where(array('goods_id' => $goods_id))->field('white_list')->find();
        $white_msg = array();
        if ($goods_info && $goods_info['white_list']) {
            $white_list = explode(',', $goods_info['white_list']);
            $merchant_info=M('Merchant')->where(array('mer_id' => array('in',  $goods_info['white_list'])))->getField('mer_id, name, phone');
            if ($white_list) {
                foreach ($white_list as $val) {
                    $white_msg[]=$merchant_info[$val];
                }
            }
        }
        $this->assign('white_msg',$white_msg);
        $this->assign('goods_id',$goods_id);
        $this->display();
    }

	// 商家批发市场相关权限
    public function capture_merchant(){
        $where=array();
        $where['status']=1;
        $name=I('name','','trim');
        if(!empty($name)){
            $where['name']=array('like','%'.$name.'%');
        }
        $goods_id = I('goods_id','','trim');

        $count=M('Merchant')->where($where)->count(1);
        import('@.ORG.system_page');
        $page=new Page($count,15);

        $list=M('Merchant')->where($where)->order('mer_id desc')->field('name, mer_id, phone')->limit($page->firstRow,$page->listRows)->select();

        $this->assign('merchant_list',$list);
        $this->assign('page_bar',$page->show());
        $this->assign('first',$page->firstRow);
        $this->assign('keyword',$name);
        $this->assign('goods_id',$goods_id);
        $this->display();
    }

    // 添加白名单信息
    public function merchant_white_list() {
        $goods_id = I('goods_id','','trim');
        $white_list=I('white_list','','trim');
        $db_shop_goods = M('Shop_goods');
        $goods_info = $db_shop_goods->where(array('goods_id' => $goods_id))->field('white_list')->find();
        if ($goods_info) {
            $save = array(
                'white_list' => $white_list
            );
            $goods_save = $db_shop_goods->where(array('goods_id' => $goods_id))->data($save)->save();
            if ($goods_save) {
                $this->success(L_('修改成功！'));
            } else {
                $this->success(L_('请检查是否未修改内容，请修改后重新提交！'));
            }
        } else {
            $this->error(L_('修改对象不存在！'));
        }
    }

    //添加称码
    public function add_weigh_code(){
        if($_POST['weigh_id']){
            if(strlen($_POST['weigh_code'])!=2 ) exit(json_encode(array('err_code'=>1,'msg'=>L_('称码请输入两位数！'))));
            if(M('Weigh_code')->where(array('store_id'=>$_POST['store_id'],'weigh_code'=>$_POST['weigh_code']))->find()){
                exit(json_encode(array('err_code'=>1,'msg'=>L_('该称码已经添加！'))));
            }
            if(M('Weigh_code')->where(array('id'=>$_POST['weigh_id']))->save($_POST)){
                exit(json_encode(array('err_code'=>0,'msg'=>L_('编辑成功！'))));
            }else{
                exit(json_encode(array('err_code'=>1,'msg'=>L_('编辑失败！'))));
            }
        }
        if($_POST['weigh_code']){
            if(strlen($_POST['weigh_code'])!=2 ) exit(json_encode(array('err_code'=>1,'msg'=>L_('称码请输入两位数！'))));
            if(M('Weigh_code')->where(array('store_id'=>$_POST['store_id'],'weigh_code'=>$_POST['weigh_code']))->find()){
                exit(json_encode(array('err_code'=>1,'msg'=>L_('该称码已经添加！'))));
            }
            if(M('Weigh_code')->add($_POST)){
                exit(json_encode(array('err_code'=>0,'msg'=>L_('添加成功！'))));
            }else{
                exit(json_encode(array('err_code'=>1,'msg'=>L_('添加失败！'))));
            }
        }else{
            exit(json_encode(array('err_code'=>1,'msg'=>L_('请正确输入！'))));
        }
    }

    //删除称码
    public function del_weigh_code(){
        if(M('Weigh_code')->where(array('id'=>$_POST['weigh_id']))->delete()){
            exit(json_encode(array('err_code'=>0,'msg'=>L_('删除成功！'))));
        }else{
            exit(json_encode(array('err_code'=>1,'msg'=>L_('删除失败！'))));
        }
    }

    //导出计重商品
    public function weigh_goods_export()
    {
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = L_('称重商品');
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        // 设置当前的sheet

        $database_goods = D('Shop_goods');
        $condition_goods['sell_type'] = 2;
        $condition_goods['sort_id'] = $_GET['sort_id'];
        /*增加搜索类型*/
        $search_type=$_GET['search_type'];
        if($_GET['keyword']){
            if($search_type=='number'){
                $condition_goods['number'] = array('like','%'.$_GET['keyword'].'%');
            }else{
                $condition_goods['name'] = array('like','%'.$_GET['keyword'].'%');
            }
        }
        /*筛选商品类型*/
        $shop_type=I('shop_type',0,'intval');
        if($shop_type==1){
            $condition_goods['goods_type']=array('neq',1);
        }elseif($shop_type==2){
            $condition_goods['cat_fid']=array('gt',0);
        }
        $count = $database_goods->where($condition_goods)->count();

        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个商品信息',($i+1)));
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);

            $objActSheet->setCellValue('A1', 'PLU');
            $objActSheet->setCellValue('B1', L_('品名'));
            $objActSheet->setCellValue('C1', L_('编码'));
            $objActSheet->setCellValue('D1', L_('单价'));
            $objActSheet->setCellValue('E1', L_('皮重'));
            $result_list = $database_goods->where($condition_goods)->select();
            if (!empty($result_list)) {
                $index = 2;
                foreach ($result_list as $value) {
                        $objActSheet->setCellValueExplicit('A' . $index, $index-1);
                        $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                        $objActSheet->setCellValueExplicit('C' . $index, $value['number']);
                        $objActSheet->setCellValueExplicit('D' . $index, $value['unit_price']);
                        $objActSheet->setCellValueExplicit('E' . $index, $value['tare_weight']);
                        $index++;
                    }

                }
            }
            sleep(2);
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

    /*
     * 快店附属商品分组列表
     * 必传 store_id 店铺ID
     * date 2019/03/27 author 周康
     */
    public function goods_subsidiary_group()
    {
        $store_id = $_GET['store_id'] ? $_GET['store_id'] : 0;
        $subsidiary_group_list = M(_view('Shop_goods_subsidiary_group'))->where("store_id = $store_id")->order('id asc')->select();

        if ($_GET['rejson']) {
            exit(json_encode(['code' => '0', 'msg' => 'success', 'data' => $subsidiary_group_list]));
        }

        $this->assign('subsidiary_group_list', $subsidiary_group_list);
        $this->display();
    }

    /*
     * 添加快店附属商品分组
     * 必传 name , t_name , store_id
     * date 2019/03/27 author 周康
     */
    public function goods_subsidiary_group_add()
    {
        if (IS_POST) {
            $store = D('Merchant_store')->where("store_id = " . $_POST['store_id'])->find();
            if ($store) {
                if (empty($_POST['name']) or empty($_POST['t_name'])) {
                    $this->error(L_('名称必填！'));
                }
                $res = M('Shop_goods_subsidiary_group')->data($_POST)->add();
                if ($res) {
                    //多语言表增加数据
                    $_POST['id'] = M('Shop_goods_subsidiary_group')->getLastInsID();
                    D('Lang')->add_lang_data('Shop_goods_subsidiary_group',$_POST);

                    $this->success(L_('添加成功！'), U('Shop/goods_subsidiary_group', array('store_id' => $_POST['store_id'])));
                    die;
                } else {
                    $this->error(L_('添加失败！'));
                }
            } else {
                $this->error(L_('店铺不存在！'));
            }
        }
        $this->display();
    }

    /*
     * 修改快店附属商品分组
     * 必传 name , t_name , store_id, group_id
     * date 2019/03/27 author 周康
     */
    public function goods_subsidiary_group_edit()
    {
        if (IS_POST) {
            if (empty($_POST['name']) or empty($_POST['t_name'])) {
                $this->error(L_('名称必填！'));
            }
            $res = M('Shop_goods_subsidiary_group')->save($_POST);
            if ($res) {
                //多语言表修改数据
                D('Lang')->edit_lang_data('Shop_goods_subsidiary_group',$_POST);

                $this->success(L_('修改成功！'), U('Shop/goods_subsidiary_group', array('store_id' => $_POST['store_id'])));
                die;
            } else {
                $this->error(L_('修改失败！'));
            }
        }
        $subsidiary_group = M(_view('Shop_goods_subsidiary_group'))->where(['id' => $_GET['group_id'], 'store_id' => $_GET['store_id']])->find();
        if (!$subsidiary_group) {
            $this->error(L_('该分组不存在！'));
        }
        $this->assign('subsidiary_group', $subsidiary_group);
        $this->display();
    }

    /*
     * 删除快店附属商品分组
     * 必传 store_id, group_id
     * date 2019/03/27 author 周康
     */
    public function goods_subsidiary_group_del()
    {
        $subsidiary_group = M('Shop_goods_subsidiary_group');
        $res = $subsidiary_group->where(['id' => $_GET['group_id'], 'store_id' => $_GET['store_id']])->delete();
        if ($res) {
            $this->success(L_('删除成功！'));
        } else {
            $this->error(L_('删除失败！'));
        }
    }

    /*
     * 列出某个组的附属商品
     * 必传 group_id
     * date 2019/03/27 author 周康
     */
    public function subsidiary_goods_list()
    {
        $ids = M('Shop_goods_subsidiary_group')->where(['id' => $_GET['group_id']])->getField('goods_ids');
        $goods_list = null;
        if ($ids) {
            $goods_list = D('Shop_goods')->where("goods_id in ($ids)")->field('goods_id,name,stock_num,spec_value,price')->select();
        }

        if ($_GET['rejson']) {
            exit(json_encode(['code' => '0', 'msg' => 'success', 'data' => $goods_list]));
        }

        $this->assign('goods_list', $goods_list);
        $this->display();
    }

    /*
     * 绑定分组时列出可选附属商品
     * 必传 store_id
     * 选传 group_id, keyword 商品名关键字模糊搜索
     * date 2019/03/27 author 周康
     */
    public function subsidiary_goods_op_list()
    {
		$where = array();

        // 关键字
        $keyword = trim($_GET['keyword']);
        if (!empty($keyword)) {
            $where['name'] = array('like', '%' . $keyword . '%');
        }

		if($_GET['type']) {
            $where['goods_type'] = 1;
        }
		if(isset($_GET['status']) && $_GET['status'] >= 0) {
            $where['status'] = $_GET['status'];
        }
		if($_GET['sort_id']){
			$where['sort_id'] = $_GET['sort_id'];
		}

        // 店铺ID
        $where['store_id'] = trim($_GET['store_id']);

		// 过滤主商品
        $ids_list = M('Shop_subsidiary_piece_goods')->distinct(true)->field('host_goods_id')->select();
        if($ids_list){
            $ids2 = array();
            foreach ($ids_list as $key => $value){
                $ids2[] = $value['host_goods_id'];
            }
            if($ids){
                $ids = $ids.','.implode($ids2, ',');
            }else{
                $ids = implode($ids2, ',');
            }
        }
        if($ids){
            $where['goods_id'] = ['not in', $ids];
        }

		if(empty($_GET['select_group_id'])){
			$count = D('Shop_goods')->where($where)->count();
			import('@.ORG.merchant_page');
			$page = new Page($count, 20);
			$goods_list = D('Shop_goods')->where($where)->field('goods_id,sort_id,name,stock_num,goods_type,status')->limit($page->firstRow . ',' . $page->listRows)->select();
		}else{
			foreach($where as $key => $value){
				$where['sg.' . $key] = $value;
				unset($where[$key]);
			}
			$where['sggl.id'] = $_GET['select_group_id'];
			$where['_string'] = 'FIND_IN_SET(`sg`.`goods_id`,`sggl`.`goods_ids`)';

			$count = D()->table([C('DB_PREFIX') . 'shop_goods' => 'sg', C('DB_PREFIX') . 'shop_goods_subsidiary_group' => 'sggl'])->where($where)->count();
			import('@.ORG.merchant_page');
			$page = new Page($count, 20);
			$goods_list = D()->table([C('DB_PREFIX') . 'shop_goods' => 'sg', C('DB_PREFIX') . 'shop_goods_subsidiary_group' => 'sggl'])->where($where)->field('`sg`.goods_id,`sg`.sort_id,`sg`.name,`sg`.stock_num,`sg`.goods_type,`sg`.status')->limit($page->firstRow . ',' . $page->listRows)->select();
		}

		//商品分组
		$goods_id_ids = M('Shop_goods_subsidiary_group')->where(['id' => $_GET['group_id'], 'store_id' => $_GET['store_id']])->getField('goods_ids');
		$group_list = explode(',', $goods_id_ids);
		foreach ($goods_list as &$value) {
			$value['is_group'] = in_array($value['goods_id'], $group_list) ? true : false;
		}

		// 商品分类
		$sort_ids = array_unique(array_column($goods_list,'sort_id'));
		$sort_list = array_column(D('Shop_goods_sort')->where(['sort_id' => ['in', $sort_ids]])->field('`sort_id`, `sort_name`')->select(), 'sort_name', 'sort_id');
		foreach ($goods_list as &$value) {
			$value['sort_name'] = $sort_list[$value['sort_id']];
		}


		$where_sort = array('store_id' => $_GET['store_id']);
        $where_sort['fid'] = 0;
        $sort_list = D(_view('Shop_goods_sort'))->field(true)->where($where_sort)->order('`sort` DESC,`sort_id` ASC')->select();
		$this->assign('sort_list', $sort_list);


		//商品分组
		$where_group = array('store_id' => $_GET['store_id']);
        $goods_group_list = M('Shop_goods_subsidiary_group')->where($where_group)->order('id asc')->select();
		$this->assign('goods_group_list', $goods_group_list);


        $this->assign('goods_list', $goods_list);
        $this->assign('page_bar', $page->show());
        $this->display();
    }

    /*
     * 绑定区块时列出可选附属商品
     * 必传 store_id
     * 选传 keyword 商品名关键字模糊搜索
     * date 2019/04/01 author 周康
     */
    public function subsidiary_goods_op_piece_list()
    {
        $goodsId = isset($_GET['goods_id']) && $_GET['goods_id'] > 0 ? intval($_GET['goods_id']) : 0;
        $where = array();

        // 店铺ID
        $where['store_id'] = trim($_GET['store_id']);

        // 关键字
        $keyword = trim(htmlspecialchars_decode($_GET['keyword']));
        if (!empty($keyword) AND $keyword != 'undefined') {
            $where['name'] = array('like', '%' . $keyword . '%');
        }

        // 过滤主商品
        $ids_list = M('Shop_subsidiary_piece_goods')->distinct(true)->field('host_goods_id')->select();
        if($ids_list){
            $ids = array();
            foreach ($ids_list as $key => $value){
                $ids[] = $value['host_goods_id'];
            }
            $goodsId > 0 && $ids[] = $goodsId;
            $where['goods_id'] = ['not in', implode($ids, ',')];
        }else{
            $where['goods_id'] = ['NEQ',$goodsId];
        }

        // 超值换购
        if ($_GET['type'] == 'rep') {
        	// 多规格属性与限时优惠的商品不支持参与超值抢活动购
        	$where['spec_value'] = '';

            // 排除已关闭的分类
            $showSortIds = D('Shop_goods_sort')->getAllShowChildSortIds($where['store_id'],[],true,true,false);
            if (!empty($showSortIds)) {
                $where['sort_id'] = ['in',$showSortIds];
            }else{
                $where['sort_id'] = null;
            }
        }
        $count = D('Shop_goods')->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 10);
        $goods_list = D('Shop_goods')->where($where)->field(true)->limit($page->firstRow . ',' . $page->listRows)->select();

        $today = date('Ymd');

        $storeShop = D('Merchant_store_shop')->where(array('store_id' => $where['store_id']))->find();
        $stock_type = $storeShop['stock_type'];

        foreach ($goods_list as $key => &$row) {
        	$row['sell_day'] = $stock_type ? $today : $row['sell_day'];
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

            $row['is_seckill_price'] = false;
            $row['o_price'] = floatval($row['price']);
            if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0 && $seckill_stock_num != 0) {
                $row['price'] = floatval($row['seckill_price']);
                $row['is_seckill_price'] = true;
            } else {
                $row['price'] = floatval($row['price']);
            }

        	// 超值换购
            if ($_GET['type']=='rep'&&$row['is_seckill_price']) {
            	unset($goods_list[$key]);
            }

            $row['old_price'] = floatval($row['old_price']);
            $row['seckill_price'] = floatval($row['seckill_price']);

            $return = D('Shop_goods')->format_spec_value($row['spec_value'], $row['goods_id'], $row['is_properties']);

            $row['list'] = isset($return['list']) ? $return['list'] : '';

			$row['seckill_stock'] = $seckill_stock_num;
			if($row['seckill_stock'] > 0 && $row['is_seckill_price']){
				$row['stock_num'] = $row['seckill_stock'];
			}
			if($row['seckill_stock'] == 0){
				$row['is_seckill_price'] = false;
			}

			if($row['list']){
				$spec_list = array_values($row['list']);
				$row['price'] = $spec_list[0]['price'];
				$row['stock_num'] = $spec_list[0]['stock_num'];
				if ($row['is_seckill_price']) {
					$row['price'] = $spec_list[0]['seckill_price'];
				}
			}
        }
        $this->assign('goods_list', $goods_list);
        $this->assign('page_bar', $page->show());
        $this->display();
    }

    /*
     * 分组选中绑定附属商品
     * 必传 goods_id, store_id, group_id
     * date 2019/03/27 author 周康
     */
    public function subsidiary_goods_checked()
    {
        $ids = M('Shop_goods_subsidiary_group')->where(['id' => $_POST['group_id'], 'store_id' => $_POST['store_id']])->field('goods_ids')->find();
        if ($ids) {
            if ($ids['goods_ids']) {
                $ids = $ids['goods_ids'] . ',' . $_POST['goods_ids'];
            } else {
                $ids = $_POST['goods_ids'];
            }
			$ids = str_replace(',,', ',', rtrim($ids, ','));
            $res = M('Shop_goods_subsidiary_group')->where(['id' => $_POST['group_id'], 'store_id' => $_POST['store_id']])->setField('goods_ids', $ids);
            if ($res) {
                exit(json_encode(['code' => 0, 'msg' => 'success']));
            } else {
                exit(json_encode(['code' => 400, 'msg' => L_('添加绑定失败！')]));
            }
        } else {
            exit(json_encode(['code' => 400, 'msg' => L_('没有这个关联关系！')]));
        }
    }

    /*
     * 删除某个组的附属商品关联关系
     * 必传 group_id, store_id, goods_id
     * date 2019/03/27 author 周康
     */
    public function subsidiary_goods_del()
    {
        $ids = M('Shop_goods_subsidiary_group')->where(['id' => $_GET['group_id'], 'store_id' => $_GET['store_id']])->getField('goods_ids');
        if ($ids) {
            $ids = explode(',', $ids);
            $key = array_search(trim($_GET['goods_id']), $ids);
            unset($ids[$key]);
            $res = M('Shop_goods_subsidiary_group')->where(['id' => $_GET['group_id'], 'store_id' => $_GET['store_id']])->setField('goods_ids', implode(',', $ids));
            if ($res) {
                $this->success(L_('删除成功！'));
            } else {
                $this->error(L_('删除失败！'));
            }
        } else {
            $this->error(L_('没有这个关联关系！'));
        }
    }

    /*
     * 保存附属商品区块信息
     * 必传 goods_id 主商品ID,name 区块名, maxnum, mininum, goods
     * date 2019/03/28 author 周康
     */
    public function save_subsidiary_piece($store_id = 0, $goods_id = 0, $name_piece = array(), $mininum_piece = array(), $maxnum_piece = array(), $goods = array())
    {
        if ($goods_id == 0) {
            return false;
        }

        $id_list = M('Shop_subsidiary_piece')->where(array('goods_id'=>$goods_id,'is_delete'=>0))->getField('id', true);
        if ($id_list) {
            $id_list = implode(',', $id_list);
            M('Shop_subsidiary_piece_goods')->where(array('piere_id'=>array('in',$id_list)))->setField('is_delete', 1);
			M('Shop_subsidiary_piece')->where(array('goods_id'=>$goods_id))->setField('is_delete', 1);

			M('Shop_goods')->where(array('goods_id'=>$goods_id))->setField('is_subsidiary_goods', 0);//设置该商品下面没有附属商品
        }

        try {
            foreach ($name_piece as $key => $value) {
                $data['store_id'] = $store_id;
                $data['goods_id'] = $goods_id;
                $data['name'] = htmlspecialchars_decode($name_piece[$key]);
                $data['maxnum'] = $maxnum_piece[$key];
                $data['mininum'] = $mininum_piece[$key];
                $data['addtime'] = date('Y-m-d H:i:s');
                $piece_id = M('Shop_subsidiary_piece')->data($data)->add();
                if ($piece_id) {

                    //多语言表增加数据
                    $data['id'] = $piece_id;
                    D('Lang')->add_lang_data('Shop_subsidiary_piece', $data);
                    unset($data['id']);
                    foreach ($goods['goods_id'][$key] as $k => $v) {
                        $info['piere_id'] = $piece_id;
                        $info['store_id'] = $store_id;
                        $info['goods_id'] = $goods['goods_id'][$key][$k];
                        $info['name'] = htmlspecialchars_decode($goods['name'][$key][$k]);
                        $info['price'] = is_numeric($goods['price'][$key][$k]) ? $goods['price'][$key][$k] : 0;
                        $info['maxnum'] = $goods['maxnum'][$key][$k];
                        $info['mininum'] = $goods['mininum'][$key][$k];
                        $info['sort'] = $goods['sort'][$key][$k];
                        $info['addtime'] = date('Y-m-d H:i:s');
                        $info['host_goods_id'] = $goods_id;
                        M('Shop_subsidiary_piece_goods')->data($info)->add();
                    }
                }
			}
			$count = M('Shop_subsidiary_piece_goods')->where(['host_goods_id'=>$goods_id,'is_delete'=>0])->count();
			if($count){
				M('Shop_goods')->where(array('goods_id'=>$goods_id))->setField('is_subsidiary_goods', 1);//设置该商品下面有附属商品
			}else{
                M('Shop_goods')->where(array('goods_id'=>$goods_id))->setField('is_subsidiary_goods', 0);
            }

            return true;
        } catch (Exception $e) {
            return false;
            //echo 'Message: ' .$e->getMessage();exit;
        }
    }

    /*
     * 获取某个主商品的区块列表
     * 必传 goods_id 主商品ID
     * date 2019/03/29 author 周康
     */
    public function select_subsidiary_piece()
    {
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

        exit(json_encode(['code' => '0', 'msg' => 'success', 'data' => $piece_list]));
    }


    /*
     * 快店商品分组列表
     * 必传 store_id 店铺ID
     * date 2019/06/20
     */
    public function shop_goods_group()
    {
        $store_id = $_GET['store_id'] ? $_GET['store_id'] : 0;
        // $where['mer_id'] = $this->merchant['mer_id'];
        $where['store_id'] = $store_id;
        $goods_group_list = M('Shop_goods_group')->where($where)->order('id asc')->select();

        if ($_GET['rejson']) {
            exit(json_encode(['code' => '0', 'msg' => 'success', 'data' => $goods_group_list]));
        }

        $this->assign('goods_group_list', $goods_group_list);
        $this->display();
    }

    /*
     * 添加快店商品分组
     * 必传 name , store_id
     * date 2019/06/20
     */
    public function shop_goods_group_add()
    {
        if (IS_POST) {
            $store = D('Merchant_store')->where("store_id = " . $_POST['store_id'])->find();
            if ($store) {
                if (empty($_POST['name'])) {
                    $this->error(L_('名称必填！'));
                }
                $res = M('Shop_goods_group')->data($_POST)->add();
                if ($res) {
                    $_POST['id'] = M('Shop_goods_group')->getLastInsID();

                    $this->success(L_('添加成功！'), U('Shop/shop_goods_group', array('store_id' => $_POST['store_id'])));
                    die;
                } else {
                    $this->error(L_('添加失败！'));
                }
            } else {
                $this->error(L_('店铺不存在！'));
            }
        }
        $this->display();
    }

    /*
     * 修改快店商品分组
     * 必传 name , store_id, group_id
     * date 2019/06/20
     */
    public function shop_goods_group_edit()
    {
        if (IS_POST) {
            if (empty($_POST['name'])) {
                $this->error(L_('名称必填！'));
            }
            $res = M('Shop_goods_group')->save($_POST);

            if ($res !==false) {
                $this->success(L_('修改成功！'), U('Shop/shop_goods_group', array('store_id' => $_POST['store_id'])));
                die;
            } else {
                $this->error(L_('修改失败！'));
            }
        }
        $shop_goods_group = M('Shop_goods_group')->where(['id' => $_GET['group_id'], 'store_id' => $_GET['store_id']])->find();
        if (!$shop_goods_group) {
            $this->error(L_('该分组不存在！'));
        }
        $this->assign('shop_goods_group', $shop_goods_group);
        $this->display();
    }

    /*
     * 删除快店商品分组
     * 必传 store_id, group_id
     * date 2019/06/20
     */
    public function shop_goods_group_del()
    {
        $shop_goods_group = M('Shop_goods_group');
        $res = $shop_goods_group->where(['id' => $_GET['group_id'], 'store_id' => $_GET['store_id']])->delete();
        if ($res) {
		    M('Shop_goods_group_list')->where(['group_id' => $_GET['group_id']])->delete();
            $this->success(L_('删除成功！'));
        } else {
            $this->error(L_('删除失败！'));
        }
    }



    /*
     * 列出某个组的商品
     * 必传 group_id
     * date 2019/06/20
     */
    public function shop_goods_group_list()
    {
        // $ids = M('Shop_goods_group')->where(['id' => $_GET['group_id']])->getField('goods_ids');
        $goods_list = M('Shop_goods_group_list')->field('l.id,l.goods_id,g.name,g.stock_num,g.spec_value,g.price')->join(' as l left join '.C('DB_PREFIX').'shop_goods as g on l.goods_id = g.goods_id')->where(['l.group_id' => $_GET['group_id']])->select();

        if ($_GET['rejson']) {
            exit(json_encode(['code' => '0', 'msg' => 'success', 'data' => $goods_list]));
        }

        $this->assign('goods_list', $goods_list);
        $this->display();
    }



    /*
     * 分组选中绑定商品
     * 必传 goods_id, store_id, group_id
     * date 2019/06/20
     */
    public function shop_goods_group_checked()
    {
        $now_shop_goods_group = M('Shop_goods_group')->where(['id' => $_POST['group_id'], 'store_id' => $_POST['store_id']])->find();
        if ($now_shop_goods_group) {
	    	// 已绑定商品
	        $goods_list = M('Shop_goods_group_list')->where(['group_id' => $_POST['group_id']])->select();
            $add_ids = array_unique(explode(',', $_POST['goods_ids']));
            if ($goods_list) {
            	// 过滤已经添加过得商品
            	$old_goods = array();
            	foreach ($goods_list as $key => $value) {
            		$old_goods[] = $value['goods_id'];
            	}
            	$add_ids = array_diff($add_ids, $old_goods);
            }

		    $add_data = array();
			foreach ($add_ids as $key => $value) {
				if($value){
					$add_data[] = array(
						'goods_id' => $value,
						'group_id' => $_POST['group_id'],
					);
				}
			}
            $res = M('Shop_goods_group_list')->addAll($add_data);
            if ($res) {
                exit(json_encode(['code' => 0, 'msg' => 'success']));
            } else {
                exit(json_encode(['code' => 400, 'msg' => L_('添加绑定失败！')]));
            }
        } else {
            exit(json_encode(['code' => 400, 'msg' => L_('没有这个关联关系！')]));
        }
    }

	/*
     * 绑定分组时列出可选商品
     * 必传 store_id
     * 选传 group_id, keyword 商品名关键字模糊搜索
     * date 2019/06/20
     */
    public function shop_goods_group_op_list()
    {
        $where = array();

        // 关键字
        $keyword = trim($_GET['keyword']);
        if (!empty($keyword)) {
            $where['name'] = array('like', '%' . $keyword . '%');
        }

		if($_GET['type']) {
            $where['goods_type'] = 1;
        }
		if(isset($_GET['status']) && $_GET['status'] >= 0) {
            $where['status'] = $_GET['status'];
        }
		if($_GET['sort_id']){
			$where['sort_id'] = $_GET['sort_id'];
		}

        // 店铺ID
        $where['store_id'] = trim($_GET['store_id']);

		if(empty($_GET['select_group_id'])){
			$count = D('Shop_goods')->where($where)->count();
			import('@.ORG.merchant_page');
			$page = new Page($count, 20);
			$goods_list = D('Shop_goods')->where($where)->field('goods_id,sort_id,name,stock_num,goods_type,status')->limit($page->firstRow . ',' . $page->listRows)->select();
		}else{
			foreach($where as $key => $value){
				$where['sg.' . $key] = $value;
				unset($where[$key]);
			}
			$where['sggl.group_id'] = $_GET['select_group_id'];
			$where['_string'] = '`sggl`.`goods_id`=`sg`.`goods_id`';

			$count = D()->table([C('DB_PREFIX') . 'shop_goods' => 'sg', C('DB_PREFIX') . 'shop_goods_group_list' => 'sggl'])->where($where)->count();
			import('@.ORG.merchant_page');
			$page = new Page($count, 20);
			$goods_list = D()->table([C('DB_PREFIX') . 'shop_goods' => 'sg', C('DB_PREFIX') . 'shop_goods_group_list' => 'sggl'])->where($where)->field('`sg`.goods_id,`sg`.sort_id,`sg`.name,`sg`.stock_num,`sg`.goods_type,`sg`.status')->limit($page->firstRow . ',' . $page->listRows)->select();
		}

		//商品分组
		$goods_id_ids = array_unique(array_column($goods_list,'goods_id'));
		$group_list = array_column(D('Shop_goods_group_list')->where(['group_id' => ['in', $_GET['group_id']]])->field('`group_id`, `goods_id`')->select(), 'group_id', 'goods_id');
		foreach ($goods_list as &$value) {
			$value['is_group'] = $group_list[$value['goods_id']] ? true : false;
		}

		// 商品分类
		$sort_ids = array_unique(array_column($goods_list,'sort_id'));
		$sort_list = array_column(D('Shop_goods_sort')->where(['sort_id' => ['in', $sort_ids]])->field('`sort_id`, `sort_name`')->select(), 'sort_name', 'sort_id');
		foreach ($goods_list as &$value) {
			$value['sort_name'] = $sort_list[$value['sort_id']];
		}


		$where_sort = array('store_id' => $_GET['store_id']);
        $where_sort['fid'] = 0;
        $sort_list = D(_view('Shop_goods_sort'))->field(true)->where($where_sort)->order('`sort` DESC,`sort_id` ASC')->select();
		$this->assign('sort_list', $sort_list);


		//商品分组
		$where_group = array('store_id' => $_GET['store_id']);
        $goods_group_list = M('Shop_goods_group')->where($where_group)->order('id asc')->select();
		$this->assign('goods_group_list', $goods_group_list);


        $this->assign('goods_list', $goods_list);
        $this->assign('page_bar', $page->show());
        $this->display();
    }

    /*
     * 删除某个组的商品关联关系
     * 必传 group_id, store_id, goods_id
     * date 2019/06/20
     */
    public function shop_goods_group_goods_del()
    {
    	$now_shop_goods_group = M('Shop_goods_group')->where(['id' => $_GET['group_id'], 'store_id' => $_GET['store_id']])->find();
        if ($now_shop_goods_group) {
	        $res = M('Shop_goods_group_list')->where(['group_id' => $_GET['group_id'],'id'=>$_GET['id']])->delete();
            if ($res) {
                $this->success(L_('删除成功！'));
            } else {
                $this->error(L_('删除失败！'));
            }
        } else {
            $this->error(L_('没有这个关联关系！'));
        }
    }

	public function shop_goods_group_sort(){
		$where_sort = array('store_id' => $_POST['store_id']);
        $where_sort['fid'] = $_POST['sort_id'];
        $sort_list = D(_view('Shop_goods_sort'))->field(true)->where($where_sort)->order('`sort` DESC,`sort_id` ASC')->select();
		$this->success($sort_list);
	}

    public function see_qrcode(){
		$this->display();
	}

    /*
     * 定点配送区域列表
     * date 2019/10/18
     */
    function fixed_point_index(){
    	$store_id = $_GET['store_id'];
    	$where['store_id'] = $store_id;
    	$where['is_del'] = 0;
    	$count = D('Shop_fixed_point_area')->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 20);
        $list = D('Shop_fixed_point_area')->where($where)->field(true)->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($list as &$l) {
            $areas = M('Area')->where(['area_id' => ['IN', [$l['province_id'], $l['city_id'], $l['area_id']]]])->order('area_id ASC')->getField('area_name', true);
            $l['area_name'] = $areas ? implode('-', $areas) : '';
        }
        $now_store = $this->check_store($store_id);
        $this->assign('now_store', $now_store);
        $this->assign('list', $list);
        $this->assign('pagebar', $page->show());
    	$this->display();
    }

    /*
     * 定点配送区域列表
     * date 2019/10/18
     */
    function fixed_point_edit(){
    	 if (IS_POST) {
            if (empty($_POST['long_lat'])) {
                $this->error(L_('请选择经纬度！'));
            }
            $long_lat = explode(',', $_POST['long_lat']);
            $_POST['lng'] = $long_lat[0];
            $_POST['lat'] = $long_lat[1];
            if ( !$_POST['lng'] || !$_POST['lat']) {
                $this->error(L_('经纬度格式不正确！'));
            }
            unset($long_lat);
            if (empty($_POST['address'])) {
                $this->error(L_('位置名称必填！'));
            }
            $_POST['last_time'] = time();
            if ($_POST['id']) {
            	$res = M('Shop_fixed_point_area')->save($_POST);
	            if ($res) {
	                $this->success(L_('修改成功！'), U('Shop/fixed_point_index', array('store_id' => $_POST['store_id'])));
	                die;
	            } else {
	                $this->error(L_('修改失败！'));
	            }
            }else{
            	$_POST['store_id'] = $_POST['store_id'];
            	$_POST['mer_id'] = $this->merchant_session['mer_id'];
            	$_POST['add_time'] = time();
            	$res = M('Shop_fixed_point_area')->add($_POST);
	            if ($res) {
	                $this->success(L_('添加成功！'), U('Shop/fixed_point_index', array('store_id' => $_POST['store_id'])));
	                die;
	            } else {
	                $this->error(L_('添加失败！'));
	            }
            }
        }

        if ($_GET['id']) {
        	$now_area = M('Shop_fixed_point_area')->where(['id' => $_GET['id'], 'store_id' => $_GET['store_id']])->find();
        }else{
            $default_city = M('Area')->where(['area_id'=>C('config.now_city')])->field('area_name,area_lng,area_lat')->find();
            $this->assign('default_city', $default_city);
        }

        $this->assign('now_area', $now_area);
        $this->display();
    }


    /*
     * 删除某个组的商品关联关系
     * 必传 group_id, store_id, goods_id
     * date 2019/06/20
     */
    public function fixed_point_del()
    {
    	$now_area = M('Shop_fixed_point_area')->where(['id' => $_GET['id'], 'store_id' => $_GET['store_id']])->find();
        if ($now_area) {
	        $res = M('Shop_fixed_point_area')->where(['id' => $_GET['id'], 'store_id' => $_GET['store_id']])->setField('is_del',1);
            if ($res) {
                $this->success(L_('删除成功！'));
            } else {
                $this->error(L_('删除失败！'));
            }
        } else {
            $this->error(L_('没有这个区域位置！'));
        }
    }


    /*
     * 定点配送楼号列表
     * date 2019/10/21
     */
    function fixed_point_floor(){
    	$store_id = $_GET['store_id'];
    	$where['store_id'] = $store_id;
    	$where['area_id'] = $_GET['area_id'];
    	$where['is_del'] = 0;
    	$count = D('Shop_fixed_point_floor')->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 20);
        $list = D('Shop_fixed_point_floor')->where($where)->field(true)->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('pagebar', $page->show());
    	$this->display();
    }

    /*
     * 定点配送楼号新增编辑
     */
    function fixed_point_floor_edit(){
    	 if (IS_POST) {
            $_POST['last_time'] = time();

            if (empty($_POST['floor_name'])) {
                $this->error(L_('楼号名称必填！'));
            }

            if (empty($_POST['area_id'])) {
                $this->error(L_('区域不存在！'));
            }

            if ($_POST['floor_id']) {
            	$res = M('Shop_fixed_point_floor')->save($_POST);
	            if ($res) {
	                $this->success(L_('修改成功！'), U('Shop/fixed_point_floor', array('store_id' => $_POST['store_id'],'area_id'=>$_POST['area_id'])));
	                die;
	            } else {
	                $this->error(L_('修改失败！'));
	            }
            }else{
            	$_POST['store_id'] = $_POST['store_id'];
            	$_POST['mer_id'] = $this->merchant_session['mer_id'];
            	$_POST['add_time'] = time();
            	$res = M('Shop_fixed_point_floor')->add($_POST);
	            if ($res) {
	                $this->success(L_('添加成功！'), U('Shop/fixed_point_floor', array('store_id' => $_POST['store_id'],'area_id'=>$_POST['area_id'])));
	                die;
	            } else {
	                $this->error(L_('添加失败！'));
	            }
            }
        }

        if ($_GET['floor_id']) {
        	$now_floor = M('Shop_fixed_point_floor')->where(['floor_id' => $_GET['floor_id'], 'store_id' => $_GET['store_id']])->find();
        }
        $this->assign('now_floor', $now_floor);
        $this->display();
    }


    /*
     * 删除楼号
     */
    public function fixed_point_floor_del()
    {
    	$now_area = M('Shop_fixed_point_floor')->where(['floor_id' => $_GET['floor_id'], 'store_id' => $_GET['store_id']])->find();
        if ($now_area) {
	        $res = M('Shop_fixed_point_floor')->where(['floor_id' => $_GET['floor_id'], 'store_id' => $_GET['store_id']])->delete();
            if ($res) {
                $this->success(L_('删除成功！'));
            } else {
                $this->error(L_('删除失败！'));
            }
        } else {
            $this->error(L_('没有这个区域位置！'));
        }
    }

    /*
     * 定点配送层号列表
     * date 2019/10/21
     */
    function fixed_point_layer(){
    	$store_id = $_GET['store_id'];
    	$where['store_id'] = $store_id;
    	$where['floor_id'] = $_GET['floor_id'];
    	$where['is_del'] = 0;
    	$count = D('Shop_fixed_point_layer')->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 20);
        $list = D('Shop_fixed_point_layer')->where($where)->field(true)->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('pagebar', $page->show());
    	$this->display();
    }

    /*
     * 定点配送楼号新增编辑
     */
    function fixed_point_layer_edit(){
    	 if (IS_POST) {
            $_POST['last_time'] = time();

            if (empty($_POST['layer_num'])) {
                $this->error(L_('层号名称必填！'));
            }

            if (empty($_POST['floor_id'])) {
                $this->error(L_('楼号id不存在！'));
            }

            if ($_POST['layer_id']) {
            	$res = M('Shop_fixed_point_layer')->save($_POST);
	            if ($res) {
	                $this->success(L_('修改成功！'), U('Shop/fixed_point_layer', array('store_id' => $_POST['store_id'],'floor_id'=>$_POST['floor_id'],'area_id'=>$_POST['area_id'])));
	                die;
	            } else {
	                $this->error(L_('修改失败！'));
	            }
            }else{
            	$_POST['store_id'] = $_POST['store_id'];
            	$_POST['mer_id'] = $this->merchant_session['mer_id'];
            	$_POST['add_time'] = time();
            	$res = M('Shop_fixed_point_layer')->add($_POST);
	            if ($res) {
	                $this->success(L_('添加成功！'), U('Shop/fixed_point_layer', array('store_id' => $_POST['store_id'],'floor_id'=>$_POST['floor_id'],'area_id'=>$_POST['area_id'])));
	                die;
	            } else {
	                $this->error(L_('添加失败！'));
	            }
            }
        }

        if ($_GET['layer_id']) {
        	$now_layer = M('Shop_fixed_point_layer')->where(['layer_id' => $_GET['layer_id'], 'store_id' => $_GET['store_id']])->find();
        }
        $this->assign('now_layer', $now_layer);
        $this->display();
    }


    /*
     * 删除楼号
     */
    public function fixed_point_layer_del()
    {
    	$now_area = M('Shop_fixed_point_layer')->where(['layer_id' => $_GET['layer_id'], 'store_id' => $_GET['store_id']])->find();
        if ($now_area) {
	        $res = M('Shop_fixed_point_layer')->where(['layer_id' => $_GET['layer_id'], 'store_id' => $_GET['store_id']])->delete();
            if ($res) {
                $this->success(L_('删除成功！'));
            } else {
                $this->error(L_('删除失败！'));
            }
        } else {
            $this->error(L_('没有这个楼层信息！'));
        }
    }

    /*
     * 定点配送房号列表
     * date 2019/10/21
     */
    function fixed_point_room(){
    	$store_id = $_GET['store_id'];
    	$where['store_id'] = $store_id;
    	$where['layer_id'] = $_GET['layer_id'];
    	$count = D('Shop_fixed_point_room')->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 20);
        $list = D('Shop_fixed_point_room')->where($where)->field(true)->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('pagebar', $page->show());
    	$this->display();
    }

    /*
     * 定点配送楼号新增编辑
     */
    function fixed_point_room_edit(){
    	 if (IS_POST) {
            $_POST['last_time'] = time();

            if (empty($_POST['room_num'])) {
                $this->error(L_('房号名称必填！'));
            }

            if (empty($_POST['layer_id'])) {
                $this->error(L_('层号id不存在！'));
            }

            if ($_POST['room_id']) {
            	$res = M('Shop_fixed_point_room')->save($_POST);
	            if ($res) {
	                $this->success(L_('修改成功！'), U('Shop/fixed_point_room', array('store_id' => $_POST['store_id'],'floor_id'=>$_POST['floor_id'],'layer_id'=>$_POST['layer_id'],'area_id'=>$_POST['area_id'])));
	                die;
	            } else {
	                $this->error(L_('修改失败！'));
	            }
            }else{
            	$_POST['store_id'] = $_POST['store_id'];
            	$_POST['mer_id'] = $this->merchant_session['mer_id'];
            	$_POST['add_time'] = time();
            	$res = M('Shop_fixed_point_room')->add($_POST);
	            if ($res) {
	                $this->success(L_('添加成功！'), U('Shop/fixed_point_room', array('store_id' => $_POST['store_id'],'floor_id'=>$_POST['floor_id'],'layer_id'=>$_POST['layer_id'],'area_id'=>$_POST['area_id'])));
	                die;
	            } else {
	                $this->error(L_('添加失败！'));
	            }
            }
        }

        if ($_GET['room_id']) {
        	$now_room = M('Shop_fixed_point_room')->where(['room_id' => $_GET['room_id'], 'store_id' => $_GET['store_id']])->find();
        }
        $this->assign('now_room', $now_room);
        $this->display();
    }


    /*
     * 删除楼号
     */
    public function fixed_point_room_del()
    {
    	$now_area = M('Shop_fixed_point_room')->where(['room_id' => $_GET['room_id'], 'store_id' => $_GET['store_id']])->find();
        if ($now_area) {
	        $res = M('Shop_fixed_point_room')->where(['room_id' => $_GET['room_id'], 'store_id' => $_GET['store_id']])->delete();
            if ($res) {
                $this->success(L_('删除成功！'));
            } else {
                $this->error(L_('删除失败！'));
            }
        } else {
            $this->error(L_('没有这个号信息！'));
        }
    }

    //导出二维码链接
    public function code_export()
    {
        set_time_limit(0);
       	$bak_name = 'code_' . date('His') . '.zip';
		$zip_name_path = './runtime/code'.date('Ymd').'/'.$_GET['id'].'/';
		$img_name_path = './runtime/code'.date('Ymd').'/'.$_GET['id'].'/image/';

		if (!is_dir($zip_name_path)){
	        mkdir($zip_name_path,0777,true);
	    }

	    if (!is_dir($img_name_path)){
	        mkdir($img_name_path,0777,true);
	    }
		$zip_name_path .= $bak_name;
		$zip = new ZipArchive();

		if($zip->open($zip_name_path, ZipArchive::CREATE)=== TRUE){
			$result_list = M('Shop_fixed_point_room')->field('r.room_id,r.room_num,l.layer_num,f.floor_name,a.*')->join(' as r join '.C('DB_PREFIX').'shop_fixed_point_layer as l on l.layer_id = r.layer_id join '.C('DB_PREFIX').'shop_fixed_point_floor as f on f.floor_id = l.floor_id join '.C('DB_PREFIX').'shop_fixed_point_area as a on a.id = f.area_id')->where(array('a.id'=>$_GET['id']))->select();
			$area_ids = array();
			$area_list = array_column(M('Area')->field('area_id,area_name')->select(),'area_name','area_id');
	       	$base_path = $this->config['site_url'].'/index.php?g=Index&c=Recognition&a=see_qrcode&type=fixed_point_delive&id=';
	        if (!empty($result_list)) {
	            foreach ($result_list as $value) {
					$image_path = $base_path.$value['room_id'];
					$img = file_get_contents($image_path);
					file_put_contents($img_name_path.$value['room_id'].'.png',$img);
					$image_name = $area_list[$value['province_id']].$area_list[$value['city_id']].$area_list[$value['area_id']].$value['address'].$value['floor_name'].$value['layer_num'].$value['room_num'].'.png';
					$zip->addFile($img_name_path.$value['room_id'].'.png', $image_name);
				}
	        }
			$zip->close();
		}

		header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename='.basename($bak_name)); //文件名
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: '. filesize($zip_name_path)); //告诉浏览器，文件大小
        @readfile($zip_name_path);
        exit();
    }

    /**
     * 满赠活动
     */
    public function fullGiftActivity()
    {
        $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $where = [
            'store_id' => $storeId,
            'is_del' => 0
        ];
        if (!empty($_GET['keyword'])) {
            $where['name'] = ['LIKE', '%' . trim($_GET['keyword']) . '%'];
        }
        if ($_GET['sdate1']) {
            $where['begin_time'][] = ['egt', strtotime($_GET['sdate1'])];
        }
        if ($_GET['sdate2']) {
            $where['begin_time'][] = ['elt', strtotime($_GET['sdate2'])];
        }
        if ($_GET['fdate1']) {
            $where['end_time'][] = ['egt', strtotime($_GET['fdate1'])];
        }
        if ($_GET['fdate2']) {
            $where['end_time'][] = ['elt', strtotime($_GET['fdate2'])];
        }
        import('@.ORG.merchant_page');
        $count = M('Store_full_gift_activity')->where($where)->count();
        $page = new Page($count, 30);
        $lists = M('Store_full_gift_activity')->where($where)->limit($page->firstRow, $page->listRows)->select();
        $tm = time();
        $shopOrderMod = D('Shop_order');
        foreach ($lists as &$l) {
            $l['setting'] = M('Store_full_gift_activity_setting')->alias('s')
                ->join(C('DB_PREFIX') . 'shop_goods AS g ON s.goods_id = g.goods_id')
                ->field('s.*,g.stock_num,g.name AS goods_name')
                ->where(['s.aid' => $l['id']])
                ->select();
            if($l['end_time'] <= $tm){
                $l['status_str'] = '<font style="color:red">已过期</font>';
            }else{
                $l['status_str'] = $l['status'] == 1? '正常':'失效';
            }

            $data = $shopOrderMod->where(['is_give'=>1,'full_gift_aid'=>$l['id'],'paid'=>1])->field('count(order_id) AS order_count,count(distinct uid) AS user_count,sum(price) AS total_price')->find();
            $l['order_count'] = isset($data['order_count'])?$data['order_count']:0;
            $l['user_count'] = isset($data['user_count'])?$data['user_count']:0;
            $l['total_price'] = isset($data['total_price'])?$data['total_price']:0;
        }
        $this->assign('lists', $lists);
        $this->assign('pagebar', $page->show());
        $this->assign('storeid',$storeId);
        $this->display();
    }

    /**
     * 新建满赠活动
     */
    public function fullGiftAdd()
    {
        if (IS_POST) {
            try{
                D('Store_full_gift_activity')->full_gift_add($_POST);
                $this->success(L_('新增成功'), U('Shop/fullGiftActivity', array('store_id' => $_POST['store_id'])));
            }catch (\Exception $e){
                $this->error(L_($e->getMessage()));
            }
        } else {
            $store_id = I('store_id', 0, 'intval');
            //获取附属菜
            $sortIds = M('Shop_goods_sort')->where(['status' => 0, 'store_id' => $store_id])->getField('sort_id', true);
            if($sortIds){
                $where = [
                    'status' => 1,
                    'spec_value' => '',
                    'is_properties' => 0,
                    'is_subsidiary_goods' => 0,
                    'store_id' => $store_id
                ];
                if ($sortIds) {
                    $where['sort_id'] = ['IN', $sortIds];
                }
                $data = D('Shop_goods')->where(array('store_id' => $store_id))->where($where)->order('last_time desc')->select();
            }else{
                $data = [];
            }
            $this->assign('list', $data);
            $now_store = $this->check_store($store_id);
            $this->assign('now_store', $now_store);
            $this->display();
        }
    }

    /**
     * 编辑满赠活动
     */
    public function fullGiftEdit()
    {
        if(IS_POST){
            try {
                D('Store_full_gift_activity')->full_gift_edit($_POST);
                $this->success(L_('编辑成功'), U('Shop/fullGiftActivity', array('store_id' => $_POST['store_id'])));
            } catch (\Exception $e) {
                $this->error(L_($e->getMessage()));
            }
        }else{
            $aid = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
            $actMod = M('Store_full_gift_activity');
            $settingMod = M('Store_full_gift_activity_setting');

            $activity = $actMod->where(['id' => $aid, 'store_id' => $storeId])->find();
            if (!$activity) {
                $this->error(L_('活动不存在'));
            }
            //获取附属菜
            $sortIds = M('Shop_goods_sort')->where(['status' => 0, 'store_id' => $storeId])->getField('sort_id', true);
            $where = [
                'status' => 1,
                'spec_value' => '',
                'is_properties' => 0,
                'is_subsidiary_goods' => 0,
                'store_id' => $storeId
            ];
            if ($sortIds) {
                $where['sort_id'] = ['IN', $sortIds];
            }
            $data = D('Shop_goods')->where(array('store_id' => $storeId))->where($where)->order('last_time desc')->select();
            $this->assign('list', $data);

            $activity['setting'] = $settingMod->where(['aid' => $aid])->select();
            $this->assign('activity', $activity);
            $this->display();
        }
    }

    public function fullGiftInvalid()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $activity = M('Store_full_gift_activity')->find($id);
        if (!$activity) {
            $this->error(L_('活动不存在'));
        }

        if (false !== M('Store_full_gift_activity')->where(['id' => $id])->save(['status' => 0])) {
            $this->success(L_('成功'), U('Shop/fullGiftActivity', array('store_id' => $activity['store_id'])));
        } else {
            $this->error(L_('失败'));
        }
    }

    public function fullGiftDel(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $activity = M('Store_full_gift_activity')->find($id);
        if (!$activity) {
            $this->error(L_('活动不存在'));
        }

        if (false !== M('Store_full_gift_activity')->where(['id' => $id])->save(['is_del' => 1])) {
            $this->success(L_('成功'), U('Shop/fullGiftActivity', array('store_id' => $activity['store_id'])));
        } else {
            $this->error(L_('失败'));
        }
    }

    // 组合套餐保存
    public function save_combination($store_id = 0, $goods_id = 0, $name_com = array(), $num_com = array(), $com_goods_id = array(),$com_ids = array())
    {
        if ($goods_id == 0) {
            return false;
        }

        // 原来的组合套餐
		$combination_list = D('Shop_goods')->get_combination_list($goods_id);
		$old_com_ids = array_column($combination_list, 'com_id');

		// 删除的组合套餐
		$com_ids = $com_ids ?: [];
		$del_com_ids = array_diff($old_com_ids, $com_ids);
		if ($del_com_ids) {
			if(M('Shop_combination')->where(array('com_id'=>array('in',$del_com_ids)))->setField('is_delete', 1)){
            	M('Shop_combination_goods')->where(array('com_id'=>array('in',$del_com_ids)))->delete();
				M('Shop_goods')->where(array('goods_id'=>$goods_id))->setField('is_have_combination', 0);//设置该商品下面没有组合套餐
			}else{
            	return false;
			}
		}


        foreach ($name_com as $key => $value) {
            $data['mer_id'] = $this->merchant_session['mer_id'];
            $data['store_id'] = $store_id;
            $data['name'] = htmlspecialchars_decode($name_com[$key]);
            $data['num'] = $num_com[$key];
            if ($com_ids[$key]) { //修改
            	$com_id = $com_ids[$key];
            	if ($com_goods_id[$key]) {
            		M('Shop_combination')->where(array('com_id'=>$com_id))->data($data)->save();
            	}else{
            		// 没有添加商品，删除套餐
            		M('Shop_combination')->where(array('com_id'=>$com_id))->setField('is_delete', 1);
            	}
            }else{ //新增
            	$data['addtime'] = time();
            	if ($com_goods_id[$key]) {
	            	$com_id = M('Shop_combination')->data($data)->add();
	            }
            }

            if ($com_id) {

                //多语言表增加数据
                $data['com_id'] = $com_id;
                // D('Lang')->add_lang_data('Shop_combination', $data);
                unset($data['com_id']);

                // 删除原来的
				M('Shop_combination_goods')->where(array('com_id'=>$com_id))->delete();

				// 添加本商品
				$info = array();
				$info['mer_id'] = $this->merchant_session['mer_id'];
                $info['com_id'] = $com_id;
                $info['store_id'] = $store_id;
                $info['goods_id'] = $goods_id;
                $info['addtime'] = time();
                M('Shop_combination_goods')->data($info)->add();

				// 添加新增的数据
                foreach ($com_goods_id[$key] as $k => $v) {
					$info = array();
            		$info['mer_id'] = $this->merchant_session['mer_id'];
                    $info['com_id'] = $com_id;
                    $info['store_id'] = $store_id;
                    $info['goods_id'] = $v;
                    $info['addtime'] = time();
                    M('Shop_combination_goods')->data($info)->add();
                }
            }
		}
		$combination_list = D('Shop_goods')->get_combination_list($goods_id);
		if($combination_list){
			M('Shop_goods')->where(array('goods_id'=>$goods_id))->setField('is_have_combination', 1);//设置该商品下面没有组合套餐
		}
        return true;
    }

    /*
     * 获取某个主商品的组合套餐列表
     * 必传 goods_id 主商品ID
     */
    public function combination_list()
    {
        $combination_list = D('Shop_goods')->get_combination_list($_GET['goods_id']);
        exit(json_encode(['code' => '0', 'msg' => 'success', 'data' => $combination_list]));
    }

    /*
     * 超值换购
     * 必传 store_id 店铺ID
     */
    public function shop_repurchase()
    {
        $where = array();
        // 店铺ID
        $where['store_id'] = trim($_GET['store_id']);
        $where['is_delete'] = 0;

        $count = D('Shop_repurchase')->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($count, 20);
        $list = D('Shop_repurchase')->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($list as $key => &$value) {
        	$week_arr = explode(',', $value['week']);
        	$week_str = '';
        	foreach ($week_arr as $_w) {
        		$week_str .= $this->get_week($_w).',';
        	}
        	$value['week_str'] = trim($week_str,',');

	        $where_goods['r.rep_id'] = $value['rep_id'];
	        $goods = M('Shop_repurchase_goods')->field('r.rep_id,r.price as r_price,r.stock_num as r_stock_num,r.sell_count as r_sell_count,g.*')->join('as r left join '.C('DB_PREFIX')._view('shop_goods').' as g on g.goods_id=r.goods_id')->where($where_goods)->select();
	        $goodsName = array_column($goods, 'name');
	        $value['goodsName'] = implode('，', $goodsName);

        }
        $this->assign('list', $list);
        $this->assign('page_bar', $page->show());
        $this->display();
    }

    /*
     * 超值换购 添加
     * 必传 store_id 店铺ID
     */
    public function shop_repurchase_add()
    {
        if (IS_POST) {
        	$data = $_POST;
			$data['start_time']=strtotime($data['start_time']);
			$data['end_time']=strtotime($data['end_time']);
			if(!$data['name']){
				$this->error('请输入活动名称');
			}

			if($data['end_time']<=$data['start_time']){
				$this->error('活动起始时间设置有误');
			}
        	if ($data['week'] === '') {
        		$this->error(L_('请选择星期几显示'));
        	}

        	if ($data['is_limit'] == 1 && $data['limit_money'] <= 0) {
        		$this->error(L_('请填写门槛金额'));
        	}
        	if (!$data['goods_id']) {
        		$this->error(L_('请添加活动商品'));
        	}
			$data['mer_id'] = $this->merchant_session['mer_id'];
			$data['addtime'] = time();
        	if($rep_id = M('Shop_repurchase')->data($data)->add()){
        		// 插入商品
        		$goodsSource = M('Shop_goods')->where(['goods_id'=>['in',$_POST['goods_id']]])->select();
        		$goodsSourcePrice = array_column($goodsSource, 'price','goods_id');
        		$goodsSourceStock = array_column($goodsSource, 'stock_num','goods_id');
        		$goods_data = array();
        		foreach ($_POST['goods_id'] as $key => $value) {
        			if ($_POST['price'][$key]>$goodsSourcePrice[$value]) {
        				$this->error(L_('活动价格不能大于商品原价'));
        			}

        			if ($goodsSourceStock[$value]!=-1) {
        				if ($_POST['stock_num'][$key]==-1) {
        					$this->error(L_('活动库存不能大于商品原库存'));
        				}
        				if ($_POST['stock_num'][$key]>$goodsSourceStock[$value]) {
        					$this->error(L_('活动库存不能大于商品原库存'));
        				}
        			}

        			$goods_data[] = array(
        				'rep_id' => $rep_id,
        				'store_id' => $data['store_id'],
        				'goods_id' => $value,
        				'price' => $_POST['price'][$key],
        				'stock_num' => $_POST['stock_num'][$key],
        				'addtime' => time(),
        				'mer_id' => $this->merchant_session['mer_id'],
        			);
        		}
        		M('Shop_repurchase_goods')->addAll($goods_data);
        		$this->success(L_('添加成功'),U('shop_repurchase',['store_id'=>$data['store_id']]));
        	}else{
        		$this->success(L_('添加失败'));
        	}
        }else{
	        $week_arr = ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'];
	        $this->assign('week_arr', $week_arr);
	        $this->display();
        }

    }

    /*
     * 超值换购 添加
     * 必传 store_id 店铺ID
     */
    public function shop_repurchase_edit()
    {
        $where = array();
        // 店铺ID
        $where['store_id'] = intval($_GET['store_id']);
        $where['rep_id'] = intval($_GET['rep_id']);
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();
        if (!$detail) {
        	$this->error(L_('活动不存在'));
        }
        $detail['week_arr'] = explode(',', $detail['week']);

        unset($where['is_delete']);
        $where_goods['r.store_id'] = trim($_GET['store_id']);
        $where_goods['r.rep_id'] = trim($_GET['rep_id']);
        $goods_list = M('Shop_repurchase_goods')->field('r.rep_id,r.price as r_price,r.stock_num as r_stock_num,r.sell_count as r_sell_count,g.*')->join('as r left join '.C('DB_PREFIX').'shop_goods as g on g.goods_id=r.goods_id')->where($where_goods)->select();
        $this->assign('detail', $detail);
        $this->assign('goods_list', $goods_list);

        if (IS_POST) {
        	$rep_id = intval($_POST['rep_id']);
        	$where['store_id'] = intval($_POST['store_id']);
	        $where['rep_id'] = intval($_POST['rep_id']);
	        $where['is_delete'] = 0;
	        $detail = M('Shop_repurchase')->where($where)->find();
	        if (!$detail) {
	        	$this->error(L_('活动不存在'));
	        }
        	$data = $_POST;
			$data['end_time']=strtotime($data['end_time']);
			if($data['end_time']<=$detail['start_time']){
				$this->error('活动结束时间设置有误');
			}
        	if (!$data['goods_id']) {
        		$this->error(L_('请添加活动商品'));
        	}

        	if(false !== M('Shop_repurchase')->where($where)->save($data)){
        		// 插入商品
        		unset($where['is_delete']);
        		$goods_list = array_column(M('Shop_repurchase_goods')->data($where)->select(), 'goods_id');
        		// 删除
        		$delete_goods_ids = array_diff($goods_list, $_POST['goods_id']);
        		if ($delete_goods_ids) {
        			$where['goods_id'] = array('in',$delete_goods_ids);
        			M('Shop_repurchase_goods')->where($where)->delete();
        		}

        		$goodsSource = M('Shop_goods')->where(['goods_id'=>['in',$_POST['goods_id']]])->select();
        		$goodsSourcePrice = array_column($goodsSource, 'price','goods_id');
        		$goodsSourceStock = array_column($goodsSource, 'stock_num','goods_id');
        		foreach ($_POST['goods_id'] as $key => $value) {
        			$goods_data = array();
        			$where['goods_id'] = $value;
        			if ($_POST['price'][$key]>$goodsSourcePrice[$value]) {
        				$this->error(L_('活动价格不能大于商品原价'));
        			}

        			if ($goodsSourceStock[$value]!=-1) {
        				if ($_POST['stock_num'][$key]==-1) {
        					$this->error(L_('活动库存不能大于商品原库存'));
        				}
        				if ($_POST['stock_num'][$key]>$goodsSourceStock[$value]) {
        					$this->error(L_('活动库存不能大于商品原库存'));
        				}
        			}
        			if (M('Shop_repurchase_goods')->where($where)->find()) {
        				$goods_data['price'] = $_POST['price'][$key];
        				$goods_data['stock_num'] = $_POST['stock_num'][$key];
        				M('Shop_repurchase_goods')->where($where)->save($goods_data);
        			}else{
	        			$goods_data = array(
	        				'rep_id' => $rep_id,
	        				'store_id' => $data['store_id'],
        					'goods_id' => $value,
	        				'price' => $_POST['price'][$key],
	        				'stock_num' => $_POST['stock_num'][$key],
	        				'addtime' => time(),
	        				'mer_id' => $this->merchant_session['mer_id'],
	        			);
        				M('Shop_repurchase_goods')->add($goods_data);
        			}
        		}
        		$this->success(L_('修改成功'),U('shop_repurchase',['store_id'=>$_POST['store_id']]));
        	}else{
        		$this->error(L_('修改失败'));
        	}
        }else{
	        $week_arr = ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'];
	        $this->assign('week_arr', $week_arr);
	        $this->display();
        }
    }

    // 删除超值换购
    public function shop_repurchase_del(){
    	$where = array();
        // 店铺ID
        $where['store_id'] = trim($_GET['store_id']);
        $where['rep_id'] = trim($_GET['rep_id']);
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();
        if (!$detail) {
        	$this->error(L_('活动不存在或已删除'));
        }
        if(M('Shop_repurchase')->where($where)->setField('is_delete',1)){
        	$this->success(L_('删除成功'));
        }else{
        	$this->error(L_('删除失败'));
        }
    }

    // 超值换购设置使用
    public function shop_repurchase_set_use(){
    	$where = array();
        $where['rep_id'] = intval($_GET['rep_id']);
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();
        if (!$detail) {
        	exit(json_encode(array('error'=>1,'msg'=>L_('活动不存在或已删除'))));
        }

        // 使用中的设为未使用
        $where_nouse['store_id'] = $detail['store_id'];
        $where_nouse['status'] = 1;
        M('Shop_repurchase')->where($where_nouse)->setField('status',0);

        // 将此活动设为已使用
        if(M('Shop_repurchase')->where($where)->setField('status',1)){
        	exit(json_encode(array('error'=>0,'msg'=>L_('设置成功'))));
        }else{
        	exit(json_encode(array('error'=>1,'msg'=>L_('设置失败'))));
        }
    }

    // 超值换购设置失效
    public function shop_repurchase_set_lose(){
    	$where = array();
        // 店铺ID
        $where['rep_id'] = intval($_GET['rep_id']);
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();
        if (!$detail) {
        	exit(json_encode(array('error'=>1,'msg'=>L_('活动不存在或已删除'))));
        }

        // 将此活动设为已使用
        if(M('Shop_repurchase')->where($where)->setField('status',2)){
        	exit(json_encode(array('error'=>0,'msg'=>L_('设置成功'))));
        }else{
        	exit(json_encode(array('error'=>1,'msg'=>L_('设置失败'))));
        }
    }

	public function marketingList(){
    	$store_id = $_GET['store_id'];
    	$this->assign('store_id',$store_id);
		$this->display();
	}

    /**
     * 限时优惠
     */
    public function time_limited_discount()
    {
        $lists = D('Time_limited_discount_goods')->get_list($_GET);
        $limit_total_config = D('Config_data')->where(['name'=>'limit_total_num'])->find();
        $this->assign('limit_total_config',$limit_total_config);
        $this->assign('lists', $lists['lists']);
        $this->assign('page_bar', $lists['page_bar']);
        $this->display();
    }

    /**
     * 限时优惠商品
     */
    public function get_time_limited_discount_goods()
    {
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $condition = [
            'keyword' => $keyword,
            'store_id' => $store_id
        ];
        $data = D('Time_limited_discount_goods')->select_goods($condition);
        list($goods_list, $page_bar) = [$data['goods'], $data['page_bar']];
        $this->assign('goods_list', $goods_list);
        $this->assign('page_bar', $page_bar);
        $this->display();
    }

    public function time_limited_discount_add()
    {
        if (IS_POST) {
            try {
                D('Time_limited_discount_goods')->add_record($_POST);
                $this->success(L_('添加成功'), U('time_limited_discount', ['store_id' => $_POST['store_id']]));
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        } else {
            $this->assign('week_arr', $this->get_week_arr());
            $this->display();
        }
    }

    public function time_limited_discount_edit()
    {
        if (IS_POST) {
            try {
                D('Time_limited_discount_goods')->edit_record($_POST);
                $this->success(L_('编辑成功'), U('time_limited_discount', ['store_id' => $_POST['store_id']]));
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        } else {
            $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($store_id < 1 || $id < 1) {
                $this->error(L_('参数有误'));
            } else {
                $where = [
                    'store_id' => $store_id,
                    'id' => $id
                ];
                $lists = D('Time_limited_discount_goods')->get_list($where);
                $info = isset($lists['lists'][0]) ? $lists['lists'][0] : [];
                //进行中的活动只能改库存
                $status = D('Time_limited_discount_goods')->get_status($id);
                $this->assign('in_progress', $status['status'] == 2 ? 1 : 0);
                $this->assign('info', $info);
                $this->assign('week_arr', $this->get_week_arr());
                $this->display();
            }
        }
    }

    public function time_limited_discount_set_del()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $detail = D('Time_limited_discount_goods')->where(['id' => $id, 'is_del' => 0])->find();
        if (!$detail) {
            exit(json_encode(array('error' => 1, 'msg' => L_('活动不存在或已撤销'))));
        }
        if (D('Time_limited_discount_goods')->where(['id' => $id])->setField('is_del', 1)) {
            D('Time_limited_discount_goods')->wirte_action_log(2, [], [], $id);
            exit(json_encode(array('error' => 0, 'msg' => L_('撤销成功'))));
        } else {
            exit(json_encode(array('error' => 1, 'msg' => L_('撤销失败'))));
        }
    }

    public function time_limited_report()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $limit_spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : 0;
        if ($id > 0) {
            if ($limit_spec_id > 0) {
                $limit_id = 0;
            } else {
                $limit_id = $id;
                $limit_spec_id = 0;
            }
            $detail = D('Time_limited_discount_goods')->get_detail($id, $limit_spec_id);
            $this->assign('days_end', $detail['days_end']);
            $this->assign('data_id', ['limit_spec_id' => $limit_spec_id, 'limit_id' => $limit_id]);
            $this->assign('detail', $detail);
        } else {
            $this->error(L_('参数有误'));
        }
        $this->display();
    }

    public function time_limited_logs()
    {
        $limit_id = I('get.limit_id', 0, 'intval');
        $logs = M('Time_limited_discount_log')->where(['limit_id' => $limit_id])->order('id DESC')->select();
        $parse_logs = [];
        foreach ($logs as $l) {
            $data = [
                'time' => date('Y-m-d H:i:s', $l['log_time'])
            ];
            if ($l['action'] == 0) {
                $data['action'] = L_('创建活动');
                $data['remark'] = L_('创建了限时优惠');
            } else if ($l['action'] == 1) {
                $data['action'] = L_('修改活动规则');
                $data['remark'] = L_('编辑了限时优惠');
            } else if ($l['action'] == 2) {
                $data['action'] = L_('修改活动规则');
                $data['remark'] = L_('撤销了限时优惠');
            }
            $parse_logs[] = $data;
        }
        $this->assign('parse_logs', $parse_logs);
        $this->display();
    }

    public function get_sale_statistics()
    {
        $limit_id = isset($_GET['limit_id']) ? intval($_GET['limit_id']) : 0;
        $limit_spec_id = isset($_GET['limit_spec_id']) ? intval($_GET['limit_spec_id']) : 0;
        $days = isset($_GET['days']) ? intval($_GET['days']) : 1;
        $rs = D('Time_limited_discount_goods')->get_sale_statistics($limit_id, $limit_spec_id, $days);
        $this->ajaxReturn($rs);
    }

    public function get_sale_record()
    {
        $limit_id = isset($_GET['limit_id']) ? intval($_GET['limit_id']) : 0;
        $limit_spec_id = isset($_GET['limit_spec_id']) ? intval($_GET['limit_spec_id']) : 0;
        $days = isset($_GET['days']) ? intval($_GET['days']) : 1;
        $mod = M('Time_limited_discount_sale_record');
        $where = [];
        $limit_id && $where['limit_id'] = $limit_id;
        $limit_spec_id && $where['limit_spec_id'] = $limit_spec_id;
        $begin_time = strtotime(date('Y-m-d', strtotime("-$days days")));
        $end_time = time();
        $where['create_time'] = [
            ['EGT', $begin_time],
            ['ELT', $end_time]
        ];
        $records = $mod->where($where)->field("SUM(num) AS sale_num, SUM(price) AS sale_price, FROM_UNIXTIME(create_time, '%Y-%m-%d') AS datetime")->group('datetime')->order('datetime DESC')->select();
        if ($records) {
            $date_index_records = array_combine(array_column($records, 'datetime'), $records);
        } else {
            $date_index_records = [];
        }
        $date_lists = [];
        while ($end_time >= $begin_time) {
            $date = date('Y-m-d', $end_time);
            if (isset($date_index_records[$date])) {
                $date_lists[] = $date_index_records[$date];
            } else {
                $date_lists[] = [
                    'sale_num' => 0,
                    'sale_price' => 0,
                    'datetime' => $date
                ];
            }
            $end_time -= 86400;
        }
        $this->assign('date_lists', $date_lists);
        $this->display();
    }


    protected function get_week_arr()
    {
        $week_arr = [L_('星期日'), L_('星期一'), L_('星期二'), L_('星期三'), L_('星期四'), L_('星期五'), L_('星期六')];
        return $week_arr;
    }

    protected function get_week_str($week)
    {
        $week_arr = $this->get_week_arr();
        if (!is_array($week)) {
            $week = explode(',', $week);
        }
        $week_str = array_map(function ($v) use ($week_arr) {
            return isset($week_arr[$v]) ? $week_arr[$v] : $v;
        }, $week);
        return implode(',', $week_str);
    }

    public function update_limit_total_num()
    {
        if (!isset($_POST['number'])) {
            $this->error(L_('参数有误'));
        }
        $number = intval($_POST['number']);
        if ($number < 0) {
            $this->error(L_('商品总数必须是大于或等于0的正整数'));
        }
        if (D('Config_data')->save_config_data('limit_total_num', $number)) {
            $this->success(L_('保存成功'));
        } else {
            $this->error(L_('保存失败'));
        }
    }

    public function parseProperties($str)
    {
        $splitArr = array_unique(explode('+', $str));
        $rs = [];
        foreach ($splitArr as $v) {
            $splitVal = explode('#', $v);
            if(count($splitVal) != 2){
                continue;
            }
            $rs[] = [
                'propertie' => $splitVal['0'],
                'values' => explode('&', $splitVal[1])
            ];
        }
        return $rs;
    }

    public function parseSpec($str, $price,$stock, $costPrice = 0)
    {
        $splitArr = array_unique(explode('+', $str));
        $rs = [];
        foreach ($splitArr as $v) {
            $splitVal = explode('#', $v);
            if (count($splitVal) != 2 || empty($splitVal['0']) || empty($splitVal[1])) {
                continue;
            }
            $rs[] = [
                'propertie' => $splitVal['0'],
                'values' => $splitVal[1],
            ];
        }
        return ['propertie' => $rs, 'price' => $price, 'stock' => $stock, 'cost_price' => $costPrice];
    }


    /**
     * 批量操作
     */
    public function batchOperation()
    {
        $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        if ($storeId < 1) {
            $this->error(L_('请选择店铺'));
        }
        import('@.ORG.merchant_page');
        $count = M('Shop_goods_batch_log')->where(['store_id' => $storeId, 'type' => 0])->count();
        $p = new Page($count, 15);
        if ($count > 0) {
            $logs = M('Shop_goods_batch_log')->where(['store_id' => $storeId, 'type' => 0])->order('pigcms_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $syncMod = M('Shop_goods_batch_sync');
        foreach ($logs as &$l) {
            if ($l['action'] == 4) {
                $total = $syncMod->where(['log_id' => $l['pigcms_id']])->count();
                $sync = $syncMod->where(['log_id' => $l['pigcms_id'], 'is_sync' => ['NEQ', 0]])->count();
                $l['ratio'] = $total > 0 ? round($sync * 100 / $total, 2) : 100;
            } else {
                $l['ratio'] = 100;
            }
        }
        $this->assign('pagebar', $p->show());
        $this->assign('logs', $logs);
        $this->assign('storeId', $storeId);
        $this->display();
    }

    /**
     * 导出商品
     */
    public function exportGoods()
    {
        $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $sellType = isset($_GET['sell_type']) ? intval($_GET['sell_type']) : 1;
        $rs = ['error_no' => 0, 'error_msg' => ''];
        if ($storeId < 1) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = '店铺参数有误';
        } else {
            $log = [
                'action' => $sellType == 1 ? 3 : 5,
                'log_date' => date('Y-m-d H:i:s'),
                'file' => '',
                'status' => 0,
                'store_id' => $storeId,
                'mer_id' => $this->merchant_session['mer_id']
            ];
            $logId = M('Shop_goods_batch_log')->add($log);

            $export = [
                'type' => 'export_goods',
                'param' => serialize(['store_id' => $storeId, 'log_id' => $logId, 'sell_type' => $sellType, 'type' => 'export_goods', 'rand_number' => time(), 'is_shop' => 1]),
                'dateline' => time(),
                'file_name' => date('Y-m-d H:i:s'),
                'title' => $sellType == 1 ? '导出全部计件商品' : '导出全部计重商品'
            ];
            if($exportId = M('Export_log')->add($export)){
                import('@.ORG.plan');
                $planClass = new plan();

				$param = [
					'export_id' => $exportId,
					'store_id' => $storeId,
					'log_id' => $logId,
					'sell_type' => $sellType,
					'type' => 'export_goods',
					'rand_number' => time(),
					'is_shop' => 1
				];

                $params = array(
                    'file' => 'order_export',
                    'plan_time' => time(),
                    'param' => $param,
                );
                $planClass->addTask($params);
            }
        }
        $this->ajaxReturn($rs);
    }

    protected function importPieceGoods()
    {
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $act = isset($_POST['act']) ? trim($_POST['act']) : '';
        if (empty($_FILES['file']['tmp_name'])) {
            $this->error(L_('没有上传文件'));
        }
        $data = format_excel2array($_FILES['file']['tmp_name'], 0, ["\r\n", "\r", "\n"], '');
		if(count($data) > 500){
			$this->error(L_('一次只能上传500件商品'));
		}

        $sellType = 1;
        $parseData = [];
        $header = [];
		foreach ($data as $k => $v) {
			if ($k == 0) {
				$header = $v;
				continue;
			}
			$properties = $this->parseProperties($v['H']);
			$specs      = $this->parseSpec($v['G'], $v['I'], $v['O'], $v['Q']);
			$end        = end($parseData);
			if (empty($v['A']) || $parseData['name'] == $v['A']) {
				if (!empty($specs['propertie'])) {
					$len                              = count($parseData);
					$parseData[$len - 1]['spec'][]    = $specs;
					$parseData[$len - 1]['numbers'][] = $v['M'] ?? '';
				}
			} else {
				$parseData[] = [
					'store_id'          => $storeId,
					'sell_type'         => $sellType,
					'name'              => $v['A'],
					'describe'          => $v['B'],
					'number'            => $v['M'],
					'numbers'           => [$v['M'] ?? ''],
					'unit'              => $v['D'],
					'sort'              => explode('#', $v['C']),
					'min_num'           => $v['E'],
					'stock_num'         => $v['K'],
					'original_stock'    => $v['L'],
					'packing_charge'    => $v['J'],
					'status'            => $v['N'] > 0 ? 1 : 0,
					'price'             => $v['F'],
					'old_price'         => $v['F'],
					'cost_price'        => getFormatNumber($v['P']),
					'propertie'         => $properties,
					'spec'              => $specs['propertie'] ? [$specs] : [],
					'origin_excel_data' => $v
				];
			}
		}
        if (empty($parseData)) {
            $this->error(L_('表格没数据'));
        }
        $success = 0;
        $fail = 0;
        $goodsMod = M('Shop_goods');
        $goodsSortMod = M('Shop_goods_sort');
        $goodsSpecMod = M('Shop_goods_spec');
        $goodsSpecValMod = M('Shop_goods_spec_value');
        $goodsPropertiesMod = M('Shop_goods_properties');

        $log = [
            'action' => $act == 'add' ? 1 : 2,
            'log_date' => date('Y-m-d H:i:s'),
            'file' => '',
            'store_id' => $storeId,
            'mer_id' => $this->merchant_session['mer_id'],
            'header' => json_encode($header, JSON_UNESCAPED_UNICODE)
        ];
        $logId = M('Shop_goods_batch_log')->add($log);

        foreach ($parseData as $data) {
            try {
                //判断必填项
                if (empty($data['name'])) {
                    throw new \Exception(L_('商品名称不能为空'));
                }
                if (empty($data['sort'])) {
                    throw new \Exception(L_('商品分类名称不能为空'));
                }
                if (empty($data['unit'])) {
                    throw new \Exception(L_('商品单位不能为空'));
                }
                if (!is_numeric($data['price'])) {
                    throw new \Exception(L_('商品价格不能为空'));
                }
                //商品基础信息
                $sortCount = count($data['sort']);
                $data['sort_id'] = 0;
                if ($sortCount) {
                    if($sortCount == 1){
                        $data['sort_id'] = $goodsSortMod->where(['sort_name' => end($data['sort']), 'store_id' => $storeId])->getField('sort_id');
                    }else{
                        $findSort = function ($sortName, $fid)use($storeId){
                            return M('Shop_goods_sort')
                                ->where(['sort_name' => $sortName, 'fid'=> $fid,'store_id' => $storeId])
                                ->getField('sort_id');
                        };
                        $fid = 0;
                        foreach ($data['sort'] as $key => $v){
                            $fid = $findSort($v, $fid);
                            if($key == $sortCount - 1){
                                $data['sort_id'] = $fid; 
                            }
                        } 
                    }
                    if ($data['sort_id'] < 1) {
                        throw new \Exception(L_('商品分类不存在'));
                    }
                } else {
                    throw new \Exception(L_('商品分类不能为空'));
                }
                
                $data['last_time'] = time();
                $data['number'] = $data['spec'] ? '' : $data['numbers'][0];
                if ($act == 'update') {
                    $oldGoods = $goodsMod->where(['name' => $data['name'], 'sell_type' => $sellType,'goods_type'=>0, 'store_id' => $storeId])->find();
                    if (!$oldGoods) {
                        throw new \Exception(L_('商品不存在'));
                    }
                    $goodsMod->where(['goods_id' => $oldGoods['goods_id']])->save($data);
                    $goodsId = $oldGoods['goods_id'];
					$data['goods_id'] = $goodsId;
					D('Lang')->edit_lang_data('Shop_goods',$data);
                } else {
                    if ($goodsMod->where(['name' => $data['name'], 'sell_type' => $sellType, 'store_id' => $storeId])->find()) {
                        throw new \Exception(L_('与已有商品重复'));
                    }
                    $goodsId = $goodsMod->add($data);
                    $data['goods_id'] = $goodsId;
					D('Lang')->add_lang_data('Shop_goods',$data);
                }

                //商品属性
                $propertieNames = [];
                $specPropertieString = [];
                foreach ($data['propertie'] as $pval) {
                    $valus = array_map(function ($r) {
                        return $r . ':1';
                    }, $pval['values']);
                    $properties = [
                        'goods_id' => $goodsId,
                        'name' => $pval['propertie'],
                        'val' => implode($valus, ','),
                        'num' => 1
                    ];
                    if ($act == 'update') {
                        if ($oldPropertie = $goodsPropertiesMod->where(['goods_id' => $goodsId, 'name' => $properties['name']])->find()) {
                            $goodsPropertiesMod->where(['id' => $oldPropertie['id']])->save($properties);
                            $pid = $oldPropertie['id'];
                            $properties['id'] = $pid;
                            D('Lang')->edit_lang_data('Shop_goods_properties',$properties);
                        } else {
                            $pid = $goodsPropertiesMod->add($properties);
                            $properties['id'] = $pid;
                            D('Lang')->add_lang_data('Shop_goods_properties',$properties);
                        }
                    } else {
                        $pid = $goodsPropertiesMod->add($properties);
                        $properties['id'] = $pid;
                        D('Lang')->add_lang_data('Shop_goods_properties',$properties);
                    }
                    $propertieNames[] = $pval['propertie'];
                    $specPropertieString[] = $pid . '=1';
                }

                if ($propertieNames) {
                    $goodsPropertiesMod->where(['goods_id' => $goodsId, 'name' => ['NOT IN', $propertieNames]])->delete();
                    $goodsMod->where(['goods_id' => $goodsId])->save(['is_properties'=>1]);
                } else {
                    $goodsPropertiesMod->where(['goods_id' => $goodsId])->delete();
                    $goodsMod->where(['goods_id' => $goodsId])->save(['is_properties'=>0]);
                }

                //商品规格
                $specString = '';
                $specNames = [];
                $sids = $goodsSpecMod->where(['goods_id' => $goodsId, 'store_id' => $storeId])->getField('id', true);
                if ($sids) {
                    $goodsSpecValMod->where(['sid' => ['IN', $sids]])->delete();
                }
                //dump($data['spec']);exit;
                foreach ($data['spec'] as $skey=>$sval) {
                    $specValIdArr = [];
                    foreach ($sval['propertie'] as $psval) {
                        $specData = [
                            'goods_id' => $goodsId,
                            'store_id' => $storeId,
                            'name' => $psval['propertie']
                        ];
                        if ($specItem = $goodsSpecMod->where($specData)->find()) {
                            $specId = $specItem['id'];
                        } else {
                            $specId = $goodsSpecMod->add($specData);
                            $specData['id'] = $specId;
                            D('Lang')->add_lang_data('Shop_goods_spec',$specData);
                        }
                        $specNames[] = $specData['name'];

                        $specValData = [
                            'sid' => $specId,
                            'name' => $psval['values']
                        ];
                        if ($specValItem = $goodsSpecValMod->where($specValData)->find()) {
                            $specValId = $specValItem['id'];
                        } else {
                            $specValId = $goodsSpecValMod->add($specValData);
                            $specValData['id'] = $specValId;
                            D('Lang')->add_lang_data('Shop_goods_spec_value',$specValData);
                        }
                        $specValIdArr[] = $specValId;
                    }
                    if ($specString) {
                        $specString .= '#' . implode($specValIdArr, ':') . '|' . $sval['price'] . ':' . $sval['price'] . '::'. $sval['stock'] .':'.$sval['cost_price'].'::' . '|' . implode($specPropertieString, ':') . '|' . $data['numbers'][$skey] . '||';
                    } else {
                        $specString .= implode($specValIdArr, ':') . '|' . $sval['price'] . ':' . $sval['price'] . '::'. $sval['stock'] .':'.$sval['cost_price'].'::' . '|' . implode($specPropertieString, ':') . '|' . $data['numbers'][$skey] . '||';
                    }
                }
                if ($specNames) {
                    $goodsSpecMod->where(['goods_id' => $goodsId, 'store_id' => $storeId, 'name' => ['NOT IN', $specNames]])->delete();
                } else {
                    $goodsSpecMod->where(['goods_id' => $goodsId, 'store_id' => $storeId])->delete();
                }

                if ($specString) {
                    $goodsMod->where(['goods_id' => $goodsId])->save(['spec_value' => $specString]);
                }
                $success++;
            } catch (\Exception $e) {
                $failLog = [
                    'log_id' => $logId,
                    'goods_name' => $data['name'],
                    'data' => $data['origin_excel_data'] ? json_encode($data['origin_excel_data'], JSON_UNESCAPED_UNICODE) : '{}',
                    'error_msg' => $e->getMessage()
                ];
                M('Shop_goods_batch_fail_log')->add($failLog);
                $fail++;
            }
        }
        M('Shop_goods_batch_log')->where(['pigcms_id' => $logId])->save(['status' => 2, 'success_num' => $success, 'error_num' => $fail]);
        if ($fail == 0) {
            $this->success(L_('全部导入成功'));
        } else if ($success > 0) {
            $this->success(L_('导入完成！其中部分商品导入失败，您可在操作记录列表中下载导入失败记录，做相应修改后继续导入'));
        } else {
            $this->success(L_('全部导入失败！您可在操作记录列表中下载导入失败记录，做相应修改后继续导入'));
        }
    }

    protected function importWeightGoods()
    {
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $act = isset($_POST['act']) ? trim($_POST['act']) : '';
        if (empty($_FILES['file']['tmp_name'])) {
            $this->error(L_('没有上传文件'));
        }
        $data = format_excel2array($_FILES['file']['tmp_name'], 0, ["\r\n", "\r", "\n"], '');
		if(count($data) > 500){
			$this->error(L_('一次只能上传500件商品'));
		}

        $parseData = [];
        $sellType = 2;
        $header = [];
        foreach ($data as $k => $v) {
            if ($k == 0) {
                $header = $v;
                continue;
            }
            if (empty($v['A'])) {
                continue;
            }
            if (strtolower(substr($v['F'], -2)) == 'kg') {
                $weight = substr($v['F'], 0, -2);
                $weightUnit = 2;
            } else {
                $weight = substr($v['F'], 0, -1);
                $weightUnit = 1;
            }

            $rowData = [
                'store_id' => $storeId,
                'sell_type' => $sellType,
                'name' => $v['A'],
                'describe' => $v['B'],
                'sort' => explode('#', $v['C']),
                'unit' => $v['D'],
                'unit_price' => $v['E'],
                'weight' => $weight,
                'weight_unit' => $weightUnit,
                'tare_weight' => $v['G'],
                'packing_charge' => $v['H'],
                'stock_weight' => $v['I'],
                'original_weight' => $v['J'],
                'number' => $v['K'],
                'status' => $v['L'] > 0 ? 1 : 0,
                'origin_excel_data' => $v
            ];
            //计算现价
            $totalWeight = ($weightUnit == 2) ? $weight * 1000 : $weight;
            $rowData['price'] = getFormatNumber(($rowData['unit_price'] / 500) * $totalWeight);
			$rowData['old_price'] = $rowData['price'];
            $parseData[] = $rowData;
        }
        if (empty($parseData)) {
            $this->error(L_('表格没数据'));
        }
        $success = 0;
        $fail = 0;
        $goodsMod = M('Shop_goods');
        $goodsSortMod = M('Shop_goods_sort');
        $log = [
            'action' => $act == 'add' ? 1 : 2,
            'log_date' => date('Y-m-d H:i:s'),
            'file' => '',
            'store_id' => $storeId,
            'mer_id' => $this->merchant_session['mer_id'],
            'header' => json_encode($header, JSON_UNESCAPED_UNICODE)
        ];
        $logId = M('Shop_goods_batch_log')->add($log);
        foreach ($parseData as $data) {
            try {
                //判断必填项
                if (empty($data['name'])) {
                    throw new \Exception(L_('商品名称不能为空'));
                }
                if (empty($data['sort'])) {
                    throw new \Exception(L_('商品分类名称不能为空'));
                }
                if (empty($data['unit'])) {
                    throw new \Exception(L_('商品单位不能为空'));
                }
                if (!is_numeric($data['unit_price'])) {
                    throw new \Exception(L_('商品单价不能为空'));
                }
                if (empty($data['weight'])) {
                    throw new \Exception(L_('商品重量不能为空'));
                }
                if (!is_numeric($data['stock_weight'])) {
                    throw new \Exception(L_('当前库存必须为数字'));
                }
                if (!is_numeric($data['original_weight'])) {
                    throw new \Exception(L_('原始库存必须为数字'));
                }
                if ($data['number'] === '') {
                    throw new \Exception(L_('条形码不能为空'));
                }

                //商品基础信息
                $mainSort = end($data['sort']);
                $data['sort_id'] = 0;
                if ($mainSort) {
                    $data['sort_id'] = $goodsSortMod->where(['sort_name' => $mainSort, 'store_id' => $storeId])->getField('sort_id');
                    if ($data['sort_id'] < 1) {
                        throw new \Exception(L_('商品分类不存在'));
                    }

				    if ($goodsSortMod->field(true)->where(array('fid' => $data['sort_id'], 'store_id' => $storeId))->find()) {
                        throw new \Exception(L_('该分类有子分类，不能直接添加商品'));
				    }
                } else {
                    throw new \Exception(L_('商品分类不能为空'));
                }
                $data['last_time'] = time();
                if ($act == 'update') {
                    $oldGoods = $goodsMod->where(['name' => $data['name'], 'sell_type' => $sellType, 'goods_type' => 0, 'store_id' => $storeId])->find();
                    if (!$oldGoods) {
                        throw new \Exception(L_('商品不存在'));
                    }
                    $goodsMod->where(['goods_id' => $oldGoods['goods_id']])->save($data);
                    $goodsId = $oldGoods['goods_id'];
                    $data['goods_id'] = $goodsId;
					D('Lang')->edit_lang_data('Shop_goods',$data);
                } else {
                    if ($goodsMod->where(['name' => $data['name'], 'sell_type' => $sellType, 'store_id' => $storeId])->find()) {
                        throw new \Exception(L_('与已有商品重复'));
                    }
                    $goodsId = $goodsMod->add($data);
                    $data['goods_id'] = $goodsId;
					D('Lang')->add_lang_data('Shop_goods',$data);
                }
                $success++;
            } catch (\Exception $e) {
                $failLog = [
                    'log_id' => $logId,
                    'goods_name' => $data['name'],
                    'data' => $data['origin_excel_data'] ? json_encode($data['origin_excel_data'], JSON_UNESCAPED_UNICODE) : '{}',
                    'error_msg' => $e->getMessage()
                ];
                M('Shop_goods_batch_fail_log')->add($failLog);
                $fail++;
            }
        }
        M('Shop_goods_batch_log')->where(['pigcms_id' => $logId])->save(['status' => 2, 'success_num' => $success, 'error_num' => $fail]);
        if ($fail == 0) {
            $this->success(L_('全部导入成功'));
        } else if ($success > 0) {
            $this->success(L_('导入完成！其中部分商品导入失败，您可在操作记录列表中下载导入失败记录，做相应修改后继续导入'));
        } else {
            $this->success(L_('全部导入失败！您可在操作记录列表中下载导入失败记录，做相应修改后继续导入'));
        }
    }


    /**
     * 批量新增商品
     */
    public function importGoods()
    {
        if (IS_POST) {
            $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
            $act = isset($_POST['act']) ? trim($_POST['act']) : '';
            $goodsType = isset($_POST['goods_type']) ? intval($_POST['goods_type']) : 1;
            if ($storeId < 1) {
                $this->error(L_('请选择店铺'));
            }
            if (!in_array($act, ['add', 'update'])) {
                $this->error(L_('操作有误'));
            }
            if ($_FILES['file']['error'] != 4) {
                if ($goodsType == 1) {
                    //计件类商品上传
                    $this->importPieceGoods();
                } else {
                    //计重类商品上传
                    $this->importWeightGoods();
                }
            } else {
                $this->error(L_('没有上传文件'));
            }
        } else {
            $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
            $act = isset($_GET['act']) ? trim($_GET['act']) : '';
            if ($storeId < 1) {
                $this->error(L_('请选择店铺'));
            }
            $this->assign('storeId', $storeId);
            $this->assign('act', $act);
            $this->display();
        }
    }

    /**
     * 上传图片
     */
    public function uploadGoodsPicZip()
    {
        if ($_POST) {
            if ($_FILES['file']['error'] != 4) {
                $success = 0;
                $fail = 0;
                $failLogs = [];
                $successLogs = [];
                $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
                if ($storeId < 1) {
                    $this->error(L_('请选择店铺'));
                }
                $zipFileInfo = $_FILES['file'];
                $maxSize = 50 * 1024 * 1024;
                $allowExts = ['zip'];
                $goodsMod = M('Shop_goods');
                if ($zipFileInfo['size'] > $maxSize) {
                    $this->error(L_('最大压缩包50M'));
                }
                $ext = pathinfo($zipFileInfo['name'], PATHINFO_EXTENSION);
                if (empty($ext) || !in_array($ext, $allowExts)) {
                    $this->error(L_('上传文件必须是.zip格式'));
                }
                //解压
                import('ORG.Util.Dir');
                $file = $zipFileInfo['tmp_name'];
                $unzipPath = RUNTIME_PATH.'unzip/' . createRandomStr(10) . '/';

                try {
                    $zip = new PclZip($file);
                    if ($zip->extract($unzipPath) == 0) {
                        fdump_sql($zip->errorInfo(true), 'zip_error');
                        throw  new Exception($zip->errorInfo());
                    }
                    $fileLists = scandir($unzipPath);
                    $isEmpty = true;
                    foreach ($fileLists as $f) {
                        if ($f == '.' || $f == '..') {
                            continue;
                        }
                        $fext = pathinfo($f, PATHINFO_EXTENSION);
                        if (empty($fext) || !in_array(strtolower($fext), ['jpg', 'png'])) {
                            throw  new Exception(L_('图片格式只能是格式为.jpg/.png，请检查'));
                        }
                        $isEmpty = false;
                    }
                    if ($isEmpty) {
                        throw  new Exception(L_('压缩文件解压为空'));
                    }

                    //复制图片到指定商品上传图片目录并手动同步OSS
                    $imgMerId = sprintf("%09d", $this->merchant_session['mer_id']);
                    $randNum = substr($imgMerId, 0, 3) . '/' . substr($imgMerId, 3, 3) . '/' . substr($imgMerId, 6, 3);
                    $uploadGoodsPath = "./upload/goods/{$randNum}/";
                    if (!is_dir($uploadGoodsPath)) {
                        mkdir($uploadGoodsPath, 755, true);
                    }
                    $file_handle = new file_handle();
                    foreach ($fileLists as $f) {
                        if ($f == '.' || $f == '..') {
                            continue;
                        }
                        $current_encode = mb_detect_encoding($f, array("ASCII", "GB2312", "GBK", 'BIG5', 'UTF-8'));
                        if($current_encode == 'CP936'){
							$encode_filename = $f;
                        }else if($current_encode == 'EUC-CN'){
							$encode_filename = mb_convert_encoding($f, 'utf-8', 'GB2312');
						}else{
                            $encode_filename = mb_convert_encoding($f, 'utf-8', $current_encode);
                        }
                        $origin_filename = $encode_filename;
                        $encode_filename = 'goods'.$encode_filename;
                        $fpathinfo = pathinfo($encode_filename);
                        $good = $goodsMod->where(['name' => ltrim($fpathinfo['filename'],'goods'), 'store_id' => $storeId, 'goods_type' => 0])->find();
                        if ($good) {
                            $images = explode(';', $good['image']);
                            $thisImage = $uploadGoodsPath . createRandomStr(10) . '.' . ltrim($fpathinfo['extension'],'goods');
                            if (copy($unzipPath . $f, $thisImage)) {
                                $images[0] = substr($thisImage, 1);
								//(new ImageModel)->imageAddWatermark($thisImage);
                                //$file_handle->upload($thisImage);
                            }

                            //$images = array_filter($images);
                            //$goodsMod->where(['goods_id' => $good['goods_id']])->save(['image' => implode(';',$images,)]);
                            $successLogs[] = [
                                'sync_data' => json_encode(['goods_id' => $good['goods_id'], 'pic_name' => $origin_filename, 'pic_path' => $thisImage],JSON_UNESCAPED_UNICODE),
                                'sync_type' => 'shopGoodsPic',
                                'is_sync' => 0
                            ];
                            $success++;
                        } else {
                            $failLogs[] = [
                                'goods_name' => $origin_filename,
                                'data' => json_encode(['A' => $origin_filename], JSON_UNESCAPED_UNICODE),
                                'error_msg' => '商品不存在'
                            ];
                            $fail++;
                        }
                        unlink($unzipPath . $f);
                    }
                    Dir::delDir($unzipPath, false);
                } catch (\Exception $e) {
                    Dir::delDir($unzipPath, false);
                    $this->error($e->getMessage());
                }

                //记录操作,并保存上传文件
                $uploadDir = './upload/excel/merchant/goods/' . date('Ymd') . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $log = [
                    'action' => 4,
                    'log_date' => date('Y-m-d H:i:s'),
                    'file' => '',
                    'status' => 2,
                    'success_num' => $success,
                    'error_num' => $fail,
                    'store_id' => $storeId,
                    'mer_id' => $this->merchant_session['mer_id'],
                    'header' => json_encode(['A' => '商品名称'], JSON_UNESCAPED_UNICODE)
                ];
                $logId = M('Shop_goods_batch_log')->add($log);
                if ($fail > 0 && $failLogs) {
                    $failLogs = array_map(function ($r) use ($logId) {
                        $r['log_id'] = $logId;
                        return $r;
                    }, $failLogs);
                    M('Shop_goods_batch_fail_log')->addAll($failLogs);
                }

                //图片全部挪到异步去上传
                if ($success > 0 && $successLogs) {
                    $successLogs =  array_map(function ($r) use ($logId) {
                        $r['log_id'] = $logId;
                        return $r;
                    }, $successLogs);
                    M('Shop_goods_batch_sync')->addAll($successLogs);
                }

                if($success>0&&$fail>0){
    				$this->success(L_('导入完成！其中部分图片导入失败。'));
    			}elseif ($success>0) {
    				$this->success(L_('全部图片导入成功！'));
    			}else {
    				$this->success(L_('全部图片导入失败！'));
    			}
            } else {
                $this->error(L_('没有上传文件'));
            }
        } else {
            $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
            if ($storeId < 1) {
                $this->error(L_('请选择店铺'));
            }
            $this->assign('storeId', $storeId);
            $this->display();
        }
    }

    public function DeleteHtml($str)
	{
		$str = trim($str); //清除字符串两边的空格
		$str = preg_replace("/\t/","",$str); //使用正则表达式替换内容，如：空格，换行，并将替换为空。
		$str = preg_replace("/\r\n/","",$str);
		$str = preg_replace("/\r/","",$str);
		$str = preg_replace("/\n/","",$str);
		$str = preg_replace("/ /","",$str);
		$str = preg_replace("/  /","",$str);  //匹配html中的空
		return trim($str); //返回字符串
	}

    public function downFailRecord()
    {
        $pigcmsId = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
        $log = M('Shop_goods_batch_log')->where(['pigcms_id' => $pigcmsId])->find();
        $failRecords = M('Shop_goods_batch_fail_log')->where(['log_id' => $pigcmsId])->select();
        $header = $log['header'];
		$header = $this->DeleteHtml($header);
        $header = $log['header'] ? json_decode($header, true) : [];

        $tabHeader = [];
        $tabData = [];
        foreach ($header as $k => $v) {
            if ($v === '') {
                continue;
            }
            $tabHeader[] = ['title' => $v, 'value_key' => $k];
        }
        //$tabHeader[] = ;
        array_unshift($tabHeader,['title' => '失败描述', 'value_key' => 'error_msg']);
        if (empty($failRecords)) {
            $this->error(L_('没有失败记录'));
        }
        foreach ($failRecords as $r) {
            $data = $r['data'] ? json_decode($r['data'], true) : [];
            if ($data) {
                $data['error_msg'] = $r['error_msg'];
            }
            $tabData[] = $data;
        }
        //dump($tabHeader);dump($tabData);exit;
        $config = [
            'header' => $tabHeader,
            'body' => $tabData,
            'file_name' => time()];
        $this->exportExcel($config);
    }


    public function exportExcel($config = [])
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
        $objPHPExcel->getActiveSheet()
            ->getRowDimension(2)
            ->setRowHeight(35);
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
                ->setRowHeight(105);
        }

        foreach ($body as $key => $value) {
            foreach ($header as $k2 => $v2) {
                $valueFormate = $value[$v2['value_key']];
                if (isset($v2['formate']) && $v2['formate']) {
                    switch ($v2['formate'][0]) {
                        case 'date';
                            $valueFormate = date($v2['formate'][1], $valueFormate);
                            break;
                    }
                }
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
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Cache-Control: max-age=0');
        $objExcelWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objExcelWriter->save('php://output');
        exit();
    }

    public function miaomaiyunErpGoodsLists()
    {
        $storeId = I('get.store_id', 0, 'intval');
        $page = I('get.p', 1, 'intval');
        $keyword = I('get.keyword', '', 'trim');
        $searchType = I('get.search_type', '', 'trim');
        import("@.ORG.Erp.ErpService");
        $config = ErpService::getErpConfigByStoreid($storeId);
        $instance = (ErpService::getInstance())->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'])
            ->setParam('pos_num', createRandomStr(8))
            ->setParam('stores_id', $config['miaomaiyun_erp_storeid'])
            ->setParam('spec_id', 1)
            ->setParam('page', $page - 1)
            ->setParam('limit', 10);
        if ($keyword && $searchType) {
            $instance->setParam('fields', $searchType)->setParam('keyword', $keyword);
        }
        $erpGoods = $instance->run(Miaomaiyun::GET_GOODS_ALL);
        $p = new Page($erpGoods['total_rows'], 10);
        $this->assign('erpGoods', $erpGoods['data']);
        $this->assign('page', $p->show());
        $this->display();
    }

    public function searchErpGoods()
    {
        $storeId = I('get.store_id', 0, 'intval');
        $gmId = I('get.g_m_id', 0, 'intval');
        import("@.ORG.Erp.ErpService");
        $config = ErpService::getErpConfigByStoreid($storeId);
        $instance = ErpService::getInstance();
        $erpGoodsRs = $instance->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'])
            ->setParam('pos_num', createRandomStr(8))
            ->setParam('stores_id', $config['miaomaiyun_erp_storeid'])
            ->setParam('fields', 'g_m_id')
            ->setParam('keyword', $gmId)
            ->run(Miaomaiyun::GET_GOODS_ALL);

        $mainGoods = [];
        $specGoods = [];
        foreach ($erpGoodsRs['data'] as $v) {
            if ($v['g_m_id'] == $gmId) {
                !$mainGoods && $mainGoods = $v;
                $specGoods[] = $v;
            }
        }
        $rs = ['error' => 0, 'error_msg' => '', 'retval' => []];
        if (empty($mainGoods)) {
            $rs['error'] = 1;
            $rs['error_msg'] = L_('商品不存在');
        } else {
            //获取规格
            $specRetailPrice = array_column($specGoods, 'retail_price');
            $specTradePrice = array_column($specGoods, 'trade_price');
            $specfi = array_unique(array_filter(array_column($specGoods, 'specifi')));
            $goodsSpec = [];
            foreach ($specfi as $item) {
                $split = array_map(function ($i) {
                    $i = trim($i, "[,]");
                    $i = explode(':', $i);
                    return count($i) == 2 ? $i : [];
                }, array_filter(explode(' ', $item)));
                foreach ($split as $s) {
                    $goodsSpec[$s[0]][] = $s[1];
                    $goodsSpec[$s[0]] = array_unique($goodsSpec[$s[0]]);
                }
            }

            //获取库存
            $stockRs = $instance->clearParam()->setParam('stores_id', $config['miaomaiyun_erp_storeid'])
                ->setParam('only_coding', $mainGoods['only_coding'])
                ->run('/api_v2/goods_set/stock_query');

            $rs['retval'] = [
                'name' => $mainGoods['goods_title'],
                'number' => $mainGoods['only_coding'],
                'unit' => $mainGoods['unit_name'],
                'price' => getFormatNumber($mainGoods['retail_price']),
                'cost_price' => getFormatNumber($mainGoods['buying_price']),
                'stock_num' => $stockRs['data'][0]['stock_num'] ?? 0
            ];
        }
        $this->ajaxReturn($rs);
    }

    public function insertErpGoods()
    {
        $storeId = I('post.store_id', 0, 'intval');
        $ids = I('post.g_m_ids', []);
        $ids = array_unique(array_filter($ids));

        $rs = ['error_no' => 0, 'error_msg' => '', 'retval' => []];
        if ($storeId < 1 || empty($ids)) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = '参数有误';
        } else {
            import("@.ORG.Erp.ErpService");
            foreach ($ids as $id) {
                ErpService::syncErpGoodsToSystem($id, $storeId);
            }
        }
        $this->ajaxReturn($rs);
    }

    /*
	 * 对接有赞云的店铺商品
    */
    public function synchronousGoodsByYouzanyun()
    {
        $storeId = I('get.store_id', 0, 'intval');
		$now_store = $this->check_store($_GET['store_id']);
		import('@.ORG.youzanyun.SynchronousGoods');

		if(!$now_store['youzanyun_client_id'] || !$now_store['youzanyun_client_secret'] || !$now_store['youzanyun_grant_id']){
        	$this->ajaxReturn(['error'=>1,'msg'=>'请完善有赞云配置']);
		}

		$synchronousGoods = new SynchronousGoods($now_store['youzanyun_client_id'], $now_store['youzanyun_client_secret'], $now_store['youzanyun_grant_id'], $now_store['youzanyun_store_id']);

		$return = $synchronousGoods->synchronousGoodsSort($storeId);
        $this->ajaxReturn($return);
    }


    /**
     * 查看批量更新商品图片同步进度
     */
    public function showPicSyncProcess()
    {
        $id = I('get.id', 0, 'intval');
        $items = M('Shop_goods_batch_sync')->where(['log_id' => $id])->order('id ASC')->select();
        foreach($items as &$i){
            $i['sync_data'] = json_decode($i['sync_data'],true);
        }
        $this->assign('items', $items);
        $this->display();
    }

	/**
	 * 店铺列表推荐商品装饰
	 */
	public function shop_good_goods(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		$condition['store_id']	= $_GET['store_id'];
		$subject_info = M('Merchant_store_shop_data')->where($condition)->find();

		$goodIdArr = explode(',',$subject_info['good_goods_ids']);
		$goodArr = M('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id' => array('in', $goodIdArr)))->order('FIND_IN_SET(goods_id,"' . $subject_info['showcase_good_id'] . '")')->select();
		$this->assign('goodArr',$goodArr);

		$this->display();
	}

	/**
	 * 店铺列表推荐商品装饰-保存
	 */
	public function shop_good_goods_save(){
		$now_store = $this->check_store($_POST['store_id']);

		$condition['store_id']		= $_POST['store_id'];
		$data['good_goods_show'] = $_POST['good_goods_show'];
		$data['last_time']			= time();
		if(M('Merchant_store_shop')->where($condition)->data($data)->save()){
			$data_data['good_goods_ids']	= $_POST['good_id']?implode(',',$_POST['good_id']):'';
			$data_data['last_time']			= time();
			if(M('Merchant_store_shop_data')->where($condition)->find()){
				if(M('Merchant_store_shop_data')->where($condition)->data($data_data)->save()){
					$this->success(L_('保存成功！'));
				}else{
					$this->error(L_('保存失败！'));
				}
			}else{
				$data_data['store_id'] = $_POST['store_id'];
				if(M('Merchant_store_shop_data')->data($data_data)->add()){
					$this->success(L_('保存成功。'));
				}else{
					$this->error(L_('保存失败。'));
				}
			}
		}else{
			$this->error(L_('保存失败!.'));
		}
	}
}
?>
