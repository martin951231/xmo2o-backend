<?php
//商城控制器
/*@time 2018-10-22*/
class MallAction extends BaseAction
{
    
    const GOODS_SORT_LEVEL = 3;
	/* 店铺管理 */
	public function index()
	{
		$mer_id = $this->merchant_session['mer_id'];
		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['mer_id'] = $mer_id;
		$condition_merchant_store['have_mall'] = '1';
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
		$sql = "SELECT `s`.`store_id`, `s`.`mer_id`, `s`.`name`, `s`.`adress`, `s`.`phone`, `s`.`sort`, `s`.`meituan_token`, `s`.`eleme_shopId`, `ss`.`store_theme`, `ss`.`store_id` AS sid, `ss`.`zbw_sBranchNo` FROM ". C('DB_PREFIX') . "merchant_store AS s LEFT JOIN  ". C('DB_PREFIX') . "merchant_store_shop AS ss ON `s`.`store_id`=`ss`.`store_id`";
// 		$sql .= " LEFT JOIN ". C('DB_PREFIX') . "merchant_store_zbw AS zbw ON `ss`.`store_id`=`zbw`.storeId";
		$sql .= " WHERE `s`.`mer_id`={$mer_id} AND `s`.`status`='1' AND `s`.`have_mall`='1'";
		if ($this->merchant_session['store_id']) {
		    $sql .= " AND `s`.`store_id`={$this->merchant_session['store_id']}";
		}
		$sql .= " ORDER BY `s`.`sort` DESC,`s`.`store_id` ASC";
		$sql .= " LIMIT {$p->firstRow}, {$p->listRows}";
		$store_list = D()->query($sql);
		import('@.ORG.Meituan');
		$eleme = new Meituan();
		foreach ($store_list as &$store) {
		    $store['meituan_url'] = $eleme->getAuthUrl($store['store_id'], $store['name']);
		    $store['meituan_cancel_url'] = $eleme->cancelBind($store['meituan_token']);
		}
// 		echo D()->_sql();
// 		$store_list = D()->table($db_arr)->field(true)->where("`s`.`mer_id`='$mer_id' AND `s`.`status`='1' AND `s`.`have_shop`='1' AND `s`.`area_id`=`a`.`area_id`")->order('`sort` DESC,`store_id` ASC')->limit($p->firstRow.','.$p->listRows)->select();
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
			$_POST['store_id'] = $now_store['store_id'];

			$_POST['background']=$_POST['background']?$_POST['background']:'';
			//店铺商城折扣
			$_POST['mall_discount'] = intval(floatval($_POST['mall_discount']) * 10);
			$_POST['mall_discount'] = ($_POST['mall_discount'] > 100 || $_POST['mall_discount'] < 0) ? 0 : $_POST['mall_discount'];
			
			$_POST['mall_discount_type'] = isset($_POST['mall_discount_type']) ? intval($_POST['mall_discount_type']) : 0;

			$leveloff = isset($_POST['mall_leveloff']) ? $_POST['mall_leveloff'] :false;
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
			$_POST['mall_leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';
			if($leveloff === false) unset($_POST['mall_leveloff']);

			$database_merchant_store_shop = D('Merchant_store_shop');
			$store_shop = $database_merchant_store_shop->field(true)->where(array('store_id' => $store_id))->find();
			if ($store_shop) {
				$operat_shop = $database_merchant_store_shop->data($_POST)->save();
			} else {
				$operat_shop = $database_merchant_store_shop->add($_POST);
			}
			$this->success(L_('编辑成功！'));
		} else {
			$database_merchant_store_shop = D('Merchant_store_shop');
			$condition_merchant_store_shop['store_id'] = $now_store['store_id'];
			$store_shop = $database_merchant_store_shop->field(true)->where($condition_merchant_store_shop)->find();
			/**
			if(empty($store_shop['have_mall'])){
				$this->error(L_('请先开启商城店铺'));
				exit;
			}**/
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

			//所有分类
			$database_shop_category = D('Shop_category');
			$category_list = $database_shop_category->lists();
			$this->assign('category_list', $category_list);

			//此店铺有的分类
			$database_shop_category_relation = D('Shop_category_relation');
			$condition_shop_category_relation['store_id'] = $now_store['store_id'];
			$relation_list = $database_shop_category_relation->field(true)->where($condition_shop_category_relation)->select();
			$relation_array = array();
			foreach ($relation_list as $key => $value) {
				array_push($relation_array, $value['cat_id']);
			}
			 
			$store_shop['store_discount'] *= 0.1;
			$store_shop['mall_discount'] *= 0.1;
			$this->assign('relation_array', $relation_array);
			
			$leveloff = !empty($store_shop['mall_leveloff']) ? unserialize($store_shop['mall_leveloff']) :false;
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

			//0平台配送 2客户自提 3-快递或自提 5快递配送 6平台配送或自提 7平台配送或快递 8平台配送或自提或快递配送
			$deliver_types=array();
			if(C('config.open_mall_deliver') == 1){
				$deliver_types[]=array('id'=>8,'name'=>L_('平台配送或自提或快递配送'));
				$deliver_types[]=array('id'=>7,'name'=>L_('平台配送或快递'));
				$deliver_types[]=array('id'=>6,'name'=>L_('平台配送或自提'));
				$deliver_types[]=array('id'=>0,'name'=>L_('平台配送'));
			}

			$deliver_types[]=array('id'=>5,'name'=>L_('快递配送'));
			$deliver_types[]=array('id'=>3,'name'=>L_('快递或自提'));
			$deliver_types[]=array('id'=>2,'name'=>L_('客户自提'));
			$store_shop['mall_deliver_type']=$database_merchant_store_shop->get_mall_deliver_type($store_shop['deliver_type'],$store_shop['mall_deliver_type']);

            $this->assign('deliver_types', $deliver_types);
            $this->assign('store_shop', $store_shop);
            $this->assign('now_store', $now_store);
			
			$condition['store_id']	= $_GET['store_id'];
			$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
			$this->assign('subject_info',$subject_info);
			
			$this->display();
		}
	}

	/*添加推荐商品*/
	public function recommend_goods_list(){
		$where=array();
		$where['is_recommend'] = 1;
		$where['store_id'] = $_GET['store_id'];
		import('@.ORG.merchant_page');
		$model=M('Shop_goods');
		$count=$model->where($where)->count(1);
		$page= new Page($count,20);
		$list=$model->where($where)->limit($page->firstRow,$page->listRows)->order('last_time desc')->select();
		$selecteditems='';
		foreach ($list as $k => $v) {
			if($k==0){
				$selecteditems.=$v['goods_id'];
			}else{
				$selecteditems.=','.$v['goods_id'];	
			}
		}
		foreach ($list as $k=>$v){
            $list[$k]['today_sell_count']=$this->getSaleByDate($v['goods_id'],$v['store_id']);
        }
		$this->assign('goods_list',$list);
		$this->assign('selecteditems',$selecteditems);
		$this->assign('pagebar',$page->show());
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);
		$this->display();
	}

    /**
     * 获取今日销量
     * @author mrdeng
     * @date 2020/07/15
     */
    public function getSaleByDate($goods_id,$store_id)
    {
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        //$tm = time();
        $where[] = ['a.pay_time' => [['GT', $beginToday], ['LT',$endToday]]];
        $where[] =['b.goods_id'=>['eq',$goods_id]] ;
        $where[] =['a.store_id'=>['eq',$store_id]] ;

        $join = C('DB_PREFIX') . 'shop_order_detail AS b ON a.order_id = b.order_id';
        $count = M('Shop_order')->alias('a')->join($join)->where($where)->count();
        return intval($count);
    }

	//更新是否设置商品为推荐商品
	public function save_goods_recommed(){
		$goods_str=I('goods_str','','trim');
		$is_recommend=I('is_recommend',1,'intval');
		if(empty($goods_str)){
			$this->error(L_('缺少参数！'));
		}
		$model=M('Shop_goods');
		$goods_arr=explode(',',trim($goods_str,','));
		foreach ($goods_arr as $k => $v) {
			$data=array();
			$data['goods_id']=$v;
			$data['is_recommend']=$is_recommend;
			$model->data($data)->save();
		}
		if($is_recommend==1){
			$this->success(L_('设置成功！'));
		}else{
			$this->success(L_('取消成功！'));
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
        if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $now_store['store_id'], 'sort_id' => $fid))->find()) {
            $ids = $shopGoodsSortDB->getIds($fid, $now_store['store_id']);
            if (count($ids) > 1) {
                $sortList = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $now_store['store_id'], 'sort_id' => array('in', $ids)))->order('sort_id ASC')->select();
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
        
        $sort_list = M(_view('Shop_goods_sort'))->field(true)->where($where)->order('`sort` DESC,`sort_id` ASC')->select();
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
        
    	$where=array('store_id' => $store_id,'cat_fid'=>array('gt',0));
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
            $temp['index'] = $index;
            $temp['findex'] = 0;
            $temp['fid'] = 0;
			$temp['operation_type'] = $l['operation_type'];
            
            if (isset($newList[$l['sort_id']])) {
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
		$this->assign('now_store', $now_store);
        $this->display();
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
            $temp['status'] = $l['status'];
            $temp['is_must'] = $l['is_must'];
            
            if (isset($newList[$l['sort_id']])) {
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

	/**
	 * 根据商家店铺分类里的属性设置批量修改该分类下商品的属性
	 * @param $store_id:店铺id
	 * @param $sort_id：分类id
	 * @param $config：具体属性值
	 * @return bool
	 */
	private function change_goods_property_by_sort($store_id,$sort_id,$config)
	{
		return true;
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
	    
	    if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find()) {
	        if ($sort['level'] == self::GOODS_SORT_LEVEL) {
	            $this->error($sort['sort_name'] . L_('分类下不能再增加子分类'), U('Mall/goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
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
		$this->assign('sort_list_json', json_encode($sort_list_tmp, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS));

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
				$data_goods_sort['status'] = isset($_POST['status'])?intval($_POST['status']):1;
				$data_goods_sort['print_id'] = intval($_POST['print_id']);
				$data_goods_sort['fid'] = intval($_POST['sort_id_1']);
				$data_goods_sort['level'] = 1;
				if ($sort) {
				    $data_goods_sort['level'] = $sort['level'] + 1;
    				if (M('Shop_goods')->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find()) {
    				    $this->error($sort['sort_name'] . L_('分类下有归属商品了，不能给它增加子分类'), U('Mall/goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
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
				
				//商城产品配置属性
				$goods_config = array();
				$goods_config['freight_template'] =  intval($_POST['freight_template']);
				$goods_config['freight_value']    =  floatval($_POST['freight_value']);
				$goods_config['freight_type']     =  intval($_POST['freight_type']);
				$goods_config['cat_fid']          =  intval($_POST['cat_fid']);
				$goods_config['cat_id']           =  intval($_POST['cat_id']);
				$goods_config['goods_type']       =  intval($_POST['goods_type']);
				$goods_config['goodsproperties']  =  is_array($_POST['goodsproperties'])?$_POST['goodsproperties']:array();
				$data_goods_sort['mall_goods_config']=json_encode($goods_config);
				$data_goods_sort['is_mall_config'] = intval($_POST['is_mall_config']);
				if ($sort_id =$database_goods_sort->data($data_goods_sort)->add()) {
					//多语言表增加数据
					$lang_model = D('Lang');
					$_POST['sort_id'] = $sort_id;
					$lang_model->add_lang_data('shop_goods_sort',$_POST);

					if ($sort && $sort['operation_type'] == 2) {
				        $database_goods_sort->where(array('sort_id' => $sort['sort_id']))->save(array('operation_type' => 1));
				    }
					$this->success(L_('添加成功！！'), U('Mall/goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
					die;
				} else {
				    echo $database_goods_sort->_sql();
					$this->error(L_('添加失败！！请重试。'), U('Mall/goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
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

		$category_list = D('Goods_category')->get_list();
		$this->assign('category_list', json_encode($category_list));
		$this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());
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
	    $fid = isset($_REQUEST['fid']) ? intval($_REQUEST['fid']) : 0;
	    if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid))->find()) {
	        if ($sort['level'] == self::GOODS_SORT_LEVEL) {
	            $this->error($sort['sort_name'] . L_('分类下不能再增加子分类'), U('Mall/goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
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
		$sort_list_tmp = array_merge([0=>['sort_id'=>0,'sort_name'=>'请选择']], $sort_list_tmp);
		$this->assign('sort_list_json', json_encode($sort_list_tmp, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS));

		if (IS_POST) {
			if (empty($_POST['sort_name'])) {
				$error_tips = L_('分类名称必填！').'<br/>';
			} else {
				$database_goods_sort = D('Shop_goods_sort');
				$data_goods_sort['sort_id'] = $now_sort['sort_id'];
				$data_goods_sort['sort_name'] = htmlspecialchars(trim($_POST['sort_name']));
				$data_goods_sort['sort'] = intval($_POST['sort']);
				$data_goods_sort['sort_discount'] = intval(floatval($_POST['sort_discount']) * 10);
				$data_goods_sort['sort_discount'] = ($data_goods_sort['sort_discount'] > 100 || $data_goods_sort['sort_discount'] < 0) ? 0 : $data_goods_sort['sort_discount']; 
				$data_goods_sort['status'] = isset($_POST['status'])?intval($_POST['status']):1;
				$data_goods_sort['week'] = implode(',',$_POST['week']);
				$data_goods_sort['is_weekshow'] = strlen($data_goods_sort['week'])?1:0;
				$data_goods_sort['print_id'] = intval($_POST['print_id']);
				$data_goods_sort['fid'] = $fid;
				
				//$data_goods_sort['level'] = 1;
				if ($sort) {
// 				    $data_goods_sort['level'] = $sort['level'] + 1;
    				if (M('Shop_goods')->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find()) {
    				    $this->error($sort['sort_name'] . L_('分类下有归属商品了，不能给它增加子分类'), U('Mall/goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
    				    exit;
    				}
				}
				
// 				if ($data_goods_sort['level'] < self::GOODS_SORT_LEVEL) {
// 				    $data_goods_sort['operation_type'] = 2;
// 				} else {
// 				    $data_goods_sort['operation_type'] = 0;
// 				}
				
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

				//商城产品配置属性
				$goods_config = array();
				$goods_config['freight_template'] =  intval($_POST['freight_template']);
				$goods_config['freight_value']    =  floatval($_POST['freight_value']);
				$goods_config['freight_type']     =  intval($_POST['freight_type']);
				$goods_config['cat_fid']          =  intval($_POST['cat_fid']);
				$goods_config['cat_id']           =  intval($_POST['cat_id']);
				$goods_config['goods_type']       =  intval($_POST['goods_type']);
				$goods_config['goodsproperties']  =  is_array($_POST['goodsproperties'])?$_POST['goodsproperties']:array();
				if(intval($_POST['is_mall_config'])==1){
					D('Shop_goods')->save_sort_config($now_sort['store_id'],$now_sort['sort_id'],$goods_config);
				}
				$data_goods_sort['mall_goods_config']=json_encode($goods_config);
				$data_goods_sort['is_mall_config'] = intval($_POST['is_mall_config']);
				if ($database_goods_sort->data($data_goods_sort)->save()) {
					$this->update_son_show($now_sort['sort_id']);
					//多语言表增加数据
					$lang_model = D('Lang');
					//$_POST['store_id'] = $condition_merchant_store['store_id'];
					$lang_model->edit_lang_data('Shop_goods_sort',$data_goods_sort);

					if ($sort && $sort['operation_type'] == 2) {
				        $database_goods_sort->where(array('sort_id' => $sort['sort_id']))->save(array('operation_type' => 1));
				    }
					$this->success(L_('保存成功！！'), U('Mall/goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
					die;
				} else {
					$this->error(L_('保存失败！！您是不是没做过修改？请重试。'), U('Mall/goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
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
		//该分类下商城里的商品默认的属性值
		$mall_goods_config = json_decode($now_sort['mall_goods_config'],1);
		$this->assign('mall_goods_config',$mall_goods_config);
		$this->assign('sort', $sort);
		$this->assign('print_list', $print_list);
		$category_list = D('Goods_category')->get_list();
		$this->assign('category_list', json_encode($category_list));
		$this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());
		$this->display();
	}
	
	//如果父级分类展示信息修改了，则子级的展示信息相应的也应调整
	public function update_son_show($sort_id){
		$db = M('Shop_goods_sort');
		$where = ['sort_id'=>$sort_id];
		$field = 'sort_id,is_weekshow,week,level';
		$info = $db->where($where)->field($field)->find();
		if(empty($info)||empty($info['is_weekshow'])){
			return true;
		}
		if($info['level']==2){
			$all_week = explode(',',$info['week']);
			$list = $db->where(array('fid'=>$info['sort_id']))->field($field)->select();
			if($list){
				foreach($list as $key => $val){
					$this->set_sort_show($val,$all_week);
				}
			}
		}

		if($info['level']==1){
			$all_week = explode(',',$info['week']);
			$list = $db->where(array('fid'=>$info['sort_id']))->field($field)->select();
			if($list){
				foreach($list as $key => $val){
					$this->set_sort_show($val,$all_week);
					$son_list = $db->where(array('fid'=>$val['sort_id']))->field($field)->select();
					if($son_list){
						$all_w = explode(',',$val['week']);
						foreach($son_list as $sk => $sv){
							$this->set_sort_show($sv,$all_w);
						}
					}
				}
			}
		}
	}
	
	private function set_sort_show($info,$week){
		$db = M('Shop_goods_sort');
		$current_week = explode(',',$info['week']);
		$new_week = array_intersect($week,$current_week);//获取交集
		$data = array();
		$data['is_weekshow'] = 1;
		$data['week'] = $new_week?implode(',',$new_week):'';
		$db->where(array('sort_id'=>$info['sort_id']))->data($data)->save();
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
			$this->success(L_('删除成功！'));
		} else {
			$this->error(L_('删除失败！'));
		}
	}
	/* 分类页商品筛选 */
	public function sort_goods_search(){
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);

		$database_goods = D('Shop_goods');
		$condition_goods['store_id'] = $now_store['store_id'];
		/*增加搜索类型*/
		$search_type=I('get.search_type','','trim');
		if($_GET['keyword']){
			if($search_type=='number'){
				$condition_goods['_string']="number like '%".$_GET['keyword']."%' or locate('".$_GET['keyword']."',spec_value)>0";
			}else{
				$condition_goods['name'] = array('like','%'.$_GET['keyword'].'%');
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
		foreach ($goods_list as &$rl) {
			$rl['print_name'] = isset($plist[$rl['print_id']]['name']) ? $plist[$rl['print_id']]['name'] : '';
			if ($rl['sell_day'] != $today) {
			    $rl['today_sell_count'] = 0;
			}
			if ($rl['stock_num'] == -1) {
			    $rl['stock_num_t'] = L_('无限');
			} else {
			    if ($now_store['stock_type'] == 1) {
			        $rl['stock_num_t'] = max(0, $rl['stock_num'] - $rl['sell_count']);
			    } else {
			        $rl['stock_num_t'] = max(0, $rl['stock_num'] - $rl['today_sell_count']);
			    }
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

		// 条件查询去掉没有商品的分类
		$condition_goods_all['shop_type']=1;

		// 商城
		//$condition_goods_all['cat_fid']=array('gt',0);

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
        $this->assign('sort_list_json', json_encode($sort_list_tmp,JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE|JSON_HEX_APOS));

		if (!$_GET['sort_id'] && $first_sort_id) {
			$_GET['sort_id'] = $first_sort_id;
			$now_sort = $this->check_sort($_GET['sort_id']);
		}

		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);

		$database_goods = D(_view('Shop_goods'));
		$condition_goods['sort_id'] = $now_sort['sort_id'];
		//包含在所有店铺类型中的商品
		$condition_goods['shop_type']=1;
		//$condition_goods['cat_fid']=array('gt',0);
		$count_goods = $database_goods->where($condition_goods)->count();

		// 商品统计
		$where_count['shop_type']=1;
		//$where_count['cat_fid']=array('gt',0);
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

		$this->assign('count_goods', $count_goods);

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

		//包含在所有店铺类型中的商品
		//$condition_goods['shop_type']=1;
		//$condition_goods['cat_fid']=array('gt',0);

		$condition_goods['sort_id'] = $now_sort['sort_id'];
                $condition_goods['store_id'] = $_GET['store_id'];
		$database_goods = M('Shop_goods');
		$count_goods = $database_goods->where($condition_goods)->count();
		// import('@.ORG.merchant_page');
        // import('@.ORG.ajaxpage');
        import('@.ORG.diypage');
		$p = new Page($count_goods, 30);

		$goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
		
		$today = date('Ymd');
		$now_time = time();
        $goods_image_class = new goods_image();
		foreach ($goods_list as &$rl) {
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
		// 商品分类
		$sort_list = D('Shop_goods_sort')->lists($_GET['store_id'], false,true);
		$sort_list_tmp = array();
		foreach ($sort_list as $key => $value) {
			$tmp = array();
			$tmp['sort_id'] = $value['sort_id'];
			$tmp['sort_name'] = $value['sort_name'];
			$tmp['operation_type'] = $value['operation_type'];
			if ($value['son_list']) {
				foreach ($value['son_list'] as $son_k => $_son) {
					$_son_tmp = array();
					$_son_tmp['sort_id'] = $_son['sort_id'];
					$_son_tmp['sort_name'] = $_son['sort_name'];
					$_son_tmp['operation_type'] = $_son['operation_type'];
					if ($_son['son_list']) {
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
		$this->assign('sort_list_json', json_encode($sort_list_tmp, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS));

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

		//商城类型
		$condition_goods['shop_type']=1;
		$condition_goods['cat_fid']=array('gt',0);
		
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
        $goods_image_class = new goods_image();
		foreach ($goods_list as &$rl) {
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

		$this->assign('goods_list', $newList);
		$this->display();
	}

	public function goods_add()
	{
		$now_sort = $this->check_sort(intval($_GET['sort_id']));
		$now_store = $this->check_store($now_sort['store_id']);
		$templete_necessary = false;//运费模板是否必选
        if($now_store['mall_deliver_type']==3 || $now_store['mall_deliver_type']==5){//2-客户自提，3-配送或自提，5-快递配送
            $templete_necessary = true;
        }
        if (IS_POST) {
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
                $_POST['name'] = $_POST['name'] ? $_POST['name'] : $now_goods['name'];
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
            $_POST['pic'] = $_POST['pic'] ? $_POST['pic'] : $pics;
			if (empty($_POST['name'])) {
				$error_tips = L_('商品名称必填！').'<br/>';
			}
			if (empty($_POST['unit'])) {
				$error_tips .= L_('商品单位必填！').'<br/>';
			}
			if ($_POST['price'] === '' && !$this->config['open_extra_price']) {
				$error_tips .= L_('商品价格可以设置为0，但是必填！').'<br/>';
			}		
			if ($_POST['price'] < 0 && !$this->config['open_extra_price']) {
				$error_tips .= L_('商品价格必须大于或等于0！').'<br/>';
			}
			// if ($_POST['price'] < $_POST['price_range_low'] || $_POST['price'] > $_POST['price_range_height']) {
			// 	$error_tips .= L_('商品价格必须大于或等于商品最低价，小于或等于商品最高价').'<br/>';
			// }
            if (empty($_POST['pic'])) {
                $error_tips .= L_('请至少上传一张照片！') . '<br/>';
            }
			if($_POST['stock_num'] >= 0 && (($_POST['seckill_stock'] > 0 && $_POST['seckill_stock'] > $_POST['stock_num']) || $_POST['seckill_stock'] == -1)){
				$error_tips .= '限时价库存不能大于商品库存！如果不做限时活动，请将限时价库存设置为0'.'<br/>';
			}
			if($templete_necessary && empty($_POST['freight_template'])){
                $error_tips .= '运费模板必选！'.'<br/>';
            }
            if(!empty($_POST['video_url'])){
                $file_handle = new file_handle();
                $file_handle->upload($_POST['video_url']);
            }
			if($_POST['sell_type']==2){ //计重

				if (empty($_POST['goods_weight'])) {
				    $error_tips .= '商品重量不能为空'.'<br/>';
				}
                if (strlen($_POST['number'])>5) {
                    $error_tips .= '计重商品条码请不要大于5位'.'<br/>';
                }
                if (empty($_POST['unit_price'])) {
                    $error_tips .= '商品单价不能为空'.'<br/>';
                }
                
                if (empty($_POST['stock_weight'])) {
                    $error_tips .= '库存重量不能为空'.'<br/>';
                }
                if (empty($_POST['original_weight'])) {
                    $error_tips .= '原始库存重量不能为空'.'<br/>';
                }
                if($_POST['original_stock'] === ''){
					$error_tips .= '商品库存必填'.'<br/>';
				}
				if(($_POST['original_weight'] != -1 && $_POST['stock_weight'] > $_POST['original_weight']) || ($_POST['stock_weight'] == -1 && $_POST['original_weight'] != -1)){
					$error_tips .= '当前库存重量不能大于原始库存重量！'.'<br/>';
				}
            }else{
            	if($_POST['stock_num'] === ''){
					$error_tips .= '商品库存必填'.'<br/>';
				}
            	if(($_POST['original_stock'] != -1 && $_POST['stock_num'] > $_POST['original_stock']) || ($_POST['stock_num'] == -1 && $_POST['original_stock'] != -1)){
					$error_tips .= '当前库存不能大于原始库存！'.'<br/>';
				}
            }
			if (!$_POST['cat_fid'] || !$_POST['cat_id']) {
                $error_tips .= '请在商城属性设置里选择商城商品分类'.'<br/>';
            }
			
			$_POST['des'] = fulltext_filter($_POST['des']);
			
			$_POST['pic'] = implode(';', $_POST['pic']);
			$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			foreach($_POST['pic'] as $kp => $vp){
				$tmp_vp = explode(',', $vp);
				if (!strstr($tmp_vp[0], '/upload/')) $rand_num = $tmp_vp[0];
				$_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
			}
			
			$_POST['print_id'] = isset($_POST['print_id']) ? intval($_POST['print_id']) : 0;

			if ($_POST['specs']) {
				foreach ($_POST['specs'] as $val) {
					if (empty($val)) {
						$error_tips .= L_('请给规格取名，若不需要的请删除后重新生成').'<br/>';
					}
				}
			}

			if ($_POST['spec_val']) {
				foreach ($_POST['spec_val'] as &$rowset) {
					foreach ($rowset as $val) {
						if (empty($val)) {
							$error_tips .= L_('请给规格的属性值取名，若不需要的请删除后重新生成').'<br/>';
						}
                        $rowset[$k] = trim($val);
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
				foreach ($_POST['properties_val'] as &$rowset2) {
					foreach ($rowset2 as &$val) {
						if (empty($val)) {
							$error_tips .= L_('请给属性的属性值取名，若不需要的请删除后重新生成').'<br/>';
						}
                        $val = trim($val);
					}
				}
			}

			if (isset($_POST['prices']) && $_POST['prices']) {
				foreach ($_POST['prices'] as $rowset3) {
					foreach ($rowset3 as $val) {
						if (empty($val)) {
							$error_tips .= L_('所有的现价必须要填写').'<br/>';
						}
					}
				}
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

			if($_POST['price_type']==1){//全现金，积分置为0
				$_POST['account_score'] = 0;
				$_POST['account_money'] = 0;
			}else if($_POST['price_type']==3){//
				$_POST['account_money'] = 0;
				if(empty($_POST['account_score'])){
					$s = '所需'.$config['score_name'].'必填';
					$error_tips .= L_($s).'<br/>';
				}
			}else if($_POST['price_type']==2){//
				if(empty($_POST['account_score'])){
					$s = '所需'.$config['score_name'].'必填';
					$error_tips .= L_($s).'<br/>';
				}
				if(empty($_POST['account_money'])){
					$error_tips .= L_('所需现金必填').'<br/>';
				}
			}else{
				$_POST['price_type'] = 0;
			}

			if (empty($error_tips)) {
				$_POST['sort_id'] = $sort_id;
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];

				if ($goods_id = D('Shop_goods')->save_post_form($_POST, $now_store['store_id'])) {

                    $areaArray = $_POST['areaIds'];
                    $data = array();
                    if (!empty($areaArray) && $_POST['use_area']*1==1) {
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

                        // 查询区域等级
                        $area_list = D('Area')->where(array('area_id'=>array('in',array_keys($data))))->select();
                        $area_level_arr = array();
                        foreach ($area_list as $key => $value) {
                            $area_level_arr[$value['area_id']] = $value['area_type'];
                        }
                        $shopGoodsDB = D('Shop_goods_area');
                        /*$provinceIds = array();
                        $cityIds = array();
                        $areaIds = array();*/
                        foreach ($data as $aid => $level) {
                           /* if ($level == 1) {
                                $provinceIds[] = $aid;
                            } elseif ($level == 2) {
                                $cityIds[] = $aid;
                            } elseif ($level == 3) {
                                $areaIds[] = $aid;
                            }*/
                            $shopGoodsDB->add(array('did' => $goods_id, 'aid' => $aid, 'level' => $area_level_arr[$aid], 'store_id' => $now_store['store_id']));
                        }
                    }

					//商品绑定平台E卡
					$ecard_id=I('ecard_id','','intval');
					if($ecard_id>0){
						D('Ecard_coupon')->goods_bind_ecard($ecard_id,$goods_id);
					}
					D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');

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

					$this->success(L_('添加成功！'), U('Mall/goods_list', array('store_id' => $now_sort['store_id'],'sort_id' => $now_sort['sort_id'])));
					die;
					$ok_tips = L_('添加成功！');
				} else {
					$this->error(L_('添加失败！请重试！'), U('Mall/goods_list', array('store_id' => $now_sort['store_id'],'sort_id' => $now_sort['sort_id'])));
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
        $this->assign('sort_list', addslashes(json_encode($sort_list, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS)));
		$ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
		$this->assign('select_ids', json_encode($ids));

		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
		if($now_sort['is_mall_config']==1){
			$mall_goods_config = json_decode($now_sort['mall_goods_config'],true);
			$this->assign('mall_goods_config',$mall_goods_config );
		}

		$this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());

        $is_open_online_collection = $this->config['is_open_online_collection'];
        $this->assign('is_open_online_collection', $is_open_online_collection);
		
		// 商品分组
        $shop_goods_group_list = M('Shop_goods_group')->where(['store_id' => $now_store['store_id']])->select();
		$this->assign('shop_goods_group_list', $shop_goods_group_list);
		$this->assign('mer_id', $this->merchant_session['mer_id']);
		$this->assign('open_price_type', $this->merchant_session['mer_id']==$this->config['kefu_mer_id']?1:0);

		//$area_list=array();
        $where1 = array('area_type' => array('lt', 4), 'is_open' => 1);
        $areaList = D('Area')->field('area_id, area_pid, area_name')->where($where1)->select();
        /*$list = D('Shop_discount_area')->field('aid')->where(array('did' => $id))->select();
        $areaIds = array();
        foreach ($list as $row) {
            $areaIds[] = $row['aid'];
        }*/

        $tmpMap = array();
        foreach ($areaList as $item) {
            $item['select'] = 0;
            /*if (in_array($item['area_id'], $areaIds)) {
                $item['select'] = 1;
            }*/
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
		$this->display();
	}

	public function ajax_sort_goods_properties(){
		$cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
		$sort = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;//此方法根据ajax_goods_properties修改，这里的变量不统一是对的
		$properties = D('Goods_properties')->field(true)->where(array('status' => 1, 'cat_id' => $cat_id))->select();
		if ($properties) {
			$sort_array = D(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $sort))->find();
			$value_id_array = json_decode($sort_array['mall_goods_config'],true);
			$value_ids =$value_id_array['goodsproperties'];
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
			$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
			$shop = D('Merchant_store_shop')->field('store_theme')->where(array('store_id' => $store_id))->find();
			$store_theme = isset($shop['store_theme']) ? intval($shop['store_theme']) : 0;
			if ($store_theme) {
				$width = '900,450';
				$height = '900,450';
			} else {
				$width = '900,450';
				$height = '500,250';
			}
			$param = array('size' => $this->config['group_pic_size']);
			$param['thumb'] = true;
			$param['imageClassPath'] = 'ORG.Util.Image';
			$param['thumbPrefix'] = 'm_,s_';
			$param['thumbMaxWidth'] = $width;
			$param['thumbMaxHeight'] = $height;
			$param['thumbRemoveOrigin'] = false;
			$image = D('Image')->handle($this->merchant_session['mer_id'], 'goods', 1, $param);
			if ($image['error']) {
				exit(json_encode(array('error' => 1,'message' =>$image['message'])));
			} else {
				$title = $image['title']['imgFile']?$image['title']['imgFile']:$image['title']['file'];
				$goods_image_class = new goods_image();
				$url = $goods_image_class->get_image_by_path($title, 'm');
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			}
		} else {
			exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
		}
	}

	/* 编辑商品 */
	public function goods_edit()
	{

		$now_goods = $this->check_goods($_GET['goods_id']);
		//dump($now_goods['list']);exit;
		$now_sort = $this->check_sort($now_goods['sort_id']);
		$now_store = $this->check_store($now_sort['store_id']);
		$templete_necessary = false;//运费模板是否必选
        if($now_store['mall_deliver_type']==3 || $now_store['mall_deliver_type']==5){//2-客户自提，3-配送或自提，5-快递配送
            $templete_necessary = true;
        }
		if (IS_POST) {
            //fdump("55555555555555555555555555","dengyuanhui55663");
			if (empty($_POST['name'])) {
				$error_tips = L_('商品名称必填！').'<br/>';
			}
			if (empty($_POST['unit'])) {
				$error_tips .= L_('商品单位必填！').'<br/>';
			}
			if ($_POST['price'] === '' && !$this->config['open_extra_price']) {
				$error_tips .= L_('商品价格可以设置为0，但是必填！').'<br/>';
			}
			if ($_POST['price'] < 0 && !$this->config['open_extra_price']) {
				$error_tips .= L_('商品价格必须大于或等于0！').'<br/>';
			}
			// if ($_POST['price'] < $_POST['price_range_low'] || $_POST['price'] > $_POST['price_range_height']) {
			// 	$error_tips .= L_('商品价格必须大于或等于商品最低价，小于或等于商品最高价').'<br/>';
			// }
            if (empty($_POST['pic'])) {
                $error_tips .= L_('请至少上传一张照片！') . '<br/>';
            }
			if($_POST['sell_type']==2){
				if($_POST['original_stock'] === '' || $_POST['stock_num'] === ''){
					$error_tips .= '商品库存必填'.'<br/>';
				}
				if(($_POST['original_stock'] != -1 && $_POST['stock_num'] > $_POST['original_stock']) || ($_POST['stock_num'] == -1 && $_POST['original_stock'] != -1)){
					$error_tips .= '当前库存不能大于原始库存！'.'<br/>';
				}
			}else{
				if($_POST['stock_num'] === ''){
					$error_tips .= '商品库存必填'.'<br/>';
				}
				if($_POST['stock_num'] >= 0 && (($_POST['seckill_stock'] > 0 && $_POST['seckill_stock'] > $_POST['stock_num']) || $_POST['seckill_stock'] == -1)){
					$error_tips .= '限时价库存不能大于商品库存！如果不做限时活动，请将限时价库存设置为0'.'<br/>';
				}	
			}
			if($now_goods['stock_num'] != $_POST['stock_num']){
				$_POST['sell_day'] = date('Ymd');
			}
			if($templete_necessary && empty($_POST['freight_template'])){
                $error_tips .= '运费模板必选！'.'<br/>';
            }
			$_POST['des'] = fulltext_filter($_POST['des']);
			$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
			$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			foreach($_POST['pic'] as $kp => $vp){
			    if(false === strpos($vp,',')){
                    $_POST['pic'][$kp] = $vp;
                }else{
                    $tmp_vp = explode(',', $vp);
                    if (!strstr($tmp_vp[0], '/upload/')) $rand_num = $tmp_vp[0];
                    $_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
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
				foreach ($_POST['spec_val'] as &$rowset) {
					foreach ($rowset as $k=>$val) {
						if (empty($val)) {
							$error_tips .= L_('请给规格的属性值取名，若不需要的请删除后重新生成').'<br/>';
						}
                        $rowset[$k] = trim($val);
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
				foreach ($_POST['properties_val'] as &$rowset) {
					foreach ($rowset as &$val) {
						if (empty($val)) {
							$error_tips .= L_('请给属性的属性值取名，若不需要的请删除后重新生成').'<br/>';
						}
                        $val = trim($val);
					}
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

            $discountAreaDB = D('Shop_goods_area');
            $areaArray = $_POST['areaIds'];
            //fdump($_POST['areaIds'],"dengyuanhui55663ttttttttttttttt");
            if(!empty($areaArray)){
                //$discountAreaDB->where(array('did' => $now_goods['goods_id']))->delete();
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
                // 查询区域等级
                $area_list = D('Area')->where(array('area_id'=>array('in',array_keys($data))))->select();
                $area_level_arr = array();
                foreach ($area_list as $key => $value) {
                    $area_level_arr[$value['area_id']] = $value['area_type'];
                }

                $discountAreaDB = D('Shop_goods_area');
                //删除已经设置的区域
                $discountAreaDB->where(array('did' => $now_goods['goods_id']))->delete();
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
                    $discountAreaDB->add(array('did' => $now_goods['goods_id'], 'aid' => $aid, 'level' => $area_level_arr[$aid],'store_id'=>$now_sort['store_id']));
                }
            }

            if(empty($areaArray)){
                $discountAreaDB->where(array('did' => $now_goods['goods_id']))->delete();
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

			if($_POST['price_type']==1){//全现金，积分置为0
				$_POST['account_score'] = 0;
				$_POST['account_money'] = 0;
			}else if($_POST['price_type']==3){//
				$_POST['account_money'] = 0;
				if(empty($_POST['account_score'])){
					$s = '商品售卖形式下的所需'.$this->config['score_name'].'必填';
					$error_tips .= L_($s).'<br/>';
				}
			}else if($_POST['price_type']==2){//
				if(empty($_POST['account_score'])){
					$s = '商品售卖形式下的所需'.$this->config['score_name'].'必填';
					$error_tips .= L_($s).'<br/>';
				}
				if(empty($_POST['account_money'])){
					$error_tips .= L_('商品售卖形式下的所需现金必填').'<br/>';
				}
			}else{
				$_POST['price_type'] = 0;
			}

            if($_POST['price_type']>1){
                $book_act = D('Mall_book_goods')->get_book_record($_GET['goods_id']);
                if(!empty($book_act)){
                    $this->error(L_('保存失败！此商品已参与预售活动，不可再参与积分活动！'));
                    die;
                }
            }

			//删除原来保存的带价格推广图片
			import('ORG.Util.Dir');
			$img_rand_path=sprintf("%09d", $now_goods['goods_id']);
			$rand_num = substr($img_rand_path, 0, 3) . '/' . substr($img_rand_path, 3, 3) . '/' . substr($img_rand_path, 6, 3);
			$imgMallPathShare = './runtime/wxapp_mall/'.$rand_num;

			Dir::delDirnotself($imgMallPathShare);
			$upload_path = './upload/wxapp_mall/'.$rand_num.'/*.*';
			foreach(glob($upload_path) as $v){
				if(stripos($v,'friend_'.$now_goods['goods_id']) || stripos($v,'mall_'.$now_goods['goods_id']) ){
					unlink($v);
				}
			}

            if(!empty($_POST['video_url'])){
                $file_handle = new file_handle();
                $file_handle->upload($_POST['video_url']);
            }
			//删除缓存里的库存
			D('Shop_goods')->set_goods_stock_cache($now_goods['goods_id'], true);
			

			if (empty($error_tips)) {
				$_POST['goods_id'] = $now_goods['goods_id'];
				$_POST['sort_id'] = $sort_id;
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
				
				if ($goods_id = D('Shop_goods')->save_post_form($_POST, $now_store['store_id'])) {
					D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');

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

					$this->success(L_('保存成功！'), U('Mall/goods_list', array('store_id' => $now_sort['store_id'],'sort_id' => $now_sort['sort_id'],'page' => $_POST['page'])));
					die;
					$ok_tips = L_('保存成功！');
				} else {
					$this->error(L_('保存失败！请重试！'), U('Mall/goods_list', array('store_id' => $now_sort['store_id'],'sort_id' => $now_sort['sort_id'],'page' => $_POST['page'])));
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
		foreach($sort_list as $key =>$val){
			unset($sort_list[$key]['mall_goods_config']);
		}
        $this->assign('sort_list', addslashes(json_encode($sort_list, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS)));
		$ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
		$this->assign('select_ids', json_encode($ids));
		$this->assign('now_goods', array_merge($now_goods,$_POST));
		$this->assign('now_sort', $now_sort);
		$this->assign('now_store', $now_store);
		$this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());

		// 商品分组
        $shop_goods_group_list = M('Shop_goods_group')->where(['store_id' => $now_goods['store_id']])->select();
        $bind_group_list = M('Shop_goods_group_list')->field('l.id,l.group_id,g.name')->join(' as l left join '.C('DB_PREFIX').'shop_goods_group as g on l.group_id = g.id')->where(['l.goods_id' => $_GET['goods_id']])->select();
        foreach ($bind_group_list as $key => $value) {
        	$group_ids[] =  $value['group_id'];
        }

        $is_open_online_collection = $this->config['is_open_online_collection'];
        $this->assign('is_open_online_collection', $is_open_online_collection);

		$this->assign('group_ids',implode(',',$group_ids));
		$this->assign('shop_goods_group_list', $shop_goods_group_list);
		$this->assign('bind_group_list', $bind_group_list);
		$this->assign('mer_id', $this->merchant_session['mer_id']);
		$this->assign('open_price_type', $this->merchant_session['mer_id']==$this->config['kefu_mer_id']?1:0);

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
        $list = D('Shop_goods_area')->field('aid')->where(array('did' => $_GET['goods_id'],'store_id'=>$now_goods['store_id']))->select();
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

		$this->display();
	}
	
	//刷新运费模板
	public function refresh_express(){
		$templete_list = D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
		$inner_html = '<option value="0">请选择运费模板...</option>';
		if($templete_list){
			foreach($templete_list as $val){
				$inner_html .= '<option value="'.$val['id'].'">'.$val['name'].'</option>';
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
			M('Shop_goods_properties')->where(array('goods_id' => $now_goods['goods_id']))->delete();
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

		$database_goods = D('Shop_goods');
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
			if ($now_shop = D('Merchant_store_shop')->field(true)->where($condition_merchant_store)->find()) {
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

	/*校验商品类型*/
	public function check_goods_type(){
		$goods_id=I('goods_id','','intval');
		$act_info=D('Shop_goods')->get_good_act_info($goods_id);
		if($act_info){
			if($act_info['is_group']){
				$act_name=L_('拼团');
			}else{
				$act_name=L_('砍价');
			}
			$this->error(L_('抱歉！该商品已经被设置为X1活动，暂不可删除。请先到商城管理--商城店铺列表--X2管理中解禁该商品。',array('X1'=> $act_name,'X2'=>$act_name)),'',array('is_check'=>1));
		}else{
			$this->success('');
		}
	}

	/* 检测商品存在 */
	protected function check_goods($goods_id,$is_del=0)
	{
		$database_shop_goods = D('Shop_goods');
		$database_shop_goods_model = M(_view('Shop_goods'));
		$condition_goods['goods_id'] = $goods_id;
		$now_goods = $database_shop_goods_model->field(true)->where($condition_goods)->find();
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
				$this->error(L_('抱歉！该商品已经被设置为X1活动，暂不可删除。请先到商城管理--商城店铺列表--X2管理中解禁该商品。',array('X1'=> $act_name,'X2'=>$act_name)),'',5);
				exit;
			}			
			
		}
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

		$return = $database_shop_goods->format_spec_value($now_goods['spec_value'], $now_goods['goods_id']);
		$now_goods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
		$now_goods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
		$now_goods['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
		$now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
		$now_goods['list'] = isset($return['list']) ? $return['list'] : '';
		
		/*处理图片上传阿里云导致详情中的图片不展示*/
        if (!(C('config.is_open_online_collection') == 1 && (strpos($now_goods['des'], 'alicdn.com') !== false || strpos($now_goods['des'], 'pddpic.com') !== false))) {
            $src = '<img src="' . file_domain() . '/';
            $now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
            $now_goods['des'] = str_replace('<img alt="" src="/', $src, $now_goods['des']);
            $now_goods['des'] = replace_file_domain($now_goods['des']);
        }

		$now_goods['label_arr']=explode(',', $now_goods['label_str']);

		//判断该商品是否已经绑定e卡
		$where=array();
		$where['bind_goods_id']=$goods_id;
		$ecard_info=M('ecard')->where($where)->field('id,card_name')->find();
		if(!empty($ecard_info)){
			$now_goods['ecard_id']=$ecard_info['id'];
			$now_goods['card_name']=$ecard_info['card_name'];	
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

// 		echo "<Pre/>";
// 		print_r($formart_data);
// 		die;

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
// 		echo "<Pre/>";
// 		print_r(array('spec_list' => $spec_list, 'properties_list' => $properties_list, 'list' => $list, 'json' => $json));
// 		die;
		return array('spec_list' => $spec_list, 'properties_list' => $properties_list, 'list' => $list, 'json' => $json);

	}

	public function discount()
	{
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('store', $now_store);
		$where=array();
		$where['store_id']=$now_store['store_id'];
		$where['shop_type']=2;
		$where['source']=1;
		$discount = D('Shop_discount')->field(true)->where($where)->select();
		$this->assign('discount_list', $discount);
		$this->display();
	}

	public function discount_add()
	{
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store',$now_store);

		if (IS_POST) {
			$database_discount = D('Shop_discount');
			$data_discount['store_id'] = $now_store['store_id'];
			$data_discount['mer_id'] = $now_store['mer_id'];
			$data_discount['full_money'] = $_POST['full_money'];
			$data_discount['reduce_money'] = $_POST['reduce_money'];
			$data_discount['type'] = intval($_POST['type']);
			$data_discount['status'] = intval($_POST['status']);
			$data_discount['is_share'] = intval($_POST['is_share']);
			$data_discount['shop_type'] = 2;
			$data_discount['source'] = 1;
			if ($database_discount->data($data_discount)->add()) {
				$this->success(L_('添加成功！！'), U('Mall/discount',array('store_id' => $now_store['store_id'])));
				die;
			}else{
				$this->error(L_('添加失败！！请重试。'), U('Mall/discount',array('store_id' => $now_store['store_id'])));
				die;
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
		$now_store = $this->check_store($_GET['store_id']);
		if (!($discount = D('Shop_discount')->field(true)->where(array('id' => intval($_GET['id']), 'store_id' => $now_store['store_id']))->find())) {
			$this->error(L_('不存在的优惠，请查证后修改。'), U('Mall/discount',array('store_id' => $now_store['store_id'])));
		}
		$this->assign('now_store',$now_store);

		if (IS_POST) {
			$database_discount = D('Shop_discount');
			$data_discount['id'] = $discount['id'];
			$data_discount['store_id'] = $now_store['store_id'];
			$data_discount['mer_id'] = $now_store['mer_id'];
			$data_discount['full_money'] = $_POST['full_money'];
			$data_discount['reduce_money'] = $_POST['reduce_money'];
			$data_discount['type'] = intval($_POST['type']);
			$data_discount['status'] = intval($_POST['status']);
			$data_discount['is_share'] = intval($_POST['is_share']);
			$data_discount['shop_type'] = 2;
			$data_discount['source'] = 1;
			if ($database_discount->data($data_discount)->save()) {
				$this->success(L_('修改成功！！'), U('Mall/discount',array('store_id' => $now_store['store_id'])));
				die;
			}else{
				$this->error(L_('修改失败！！请重试。'), U('Mall/discount',array('store_id' => $now_store['store_id'])));
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

	/*拼团活动start*/
	//拼团活动
	public function group_list(){
		$store_id=I('store_id','','intval');
		$now_store = $this->check_store($store_id);
		$mer_id=$_SESSION['merchant']['mer_id'];
		$where=array();
		$where['act.mer_id']=$mer_id;
		$where['act.store_id']=$store_id;
		$where['act.is_del']=0;
		$searchtype=I('searchtype','','trim');
		$keyword=I('keyword','','trim');
		if(!empty($keyword)){
			switch ($searchtype) {
				case 'goods_id':
					$where['act.good_id']=$keyword;
					break;
				case 'goods_name':
					$where['gd.name']=array('like','%'.$keyword.'%');
					break;
			}	
		}
		//活动开始时间
		$stime1=I('sdate1','','strtotime');
		$stime2=I('sdate2','','strtotime');
		if($stime1 && $stime2){
			$where['act.stime']=array('between',array($stime1,$stime2));
		}elseif($stime1){
			$where['act.stime']=array('egt',$stime1);
		}elseif($stime2){			
			$where['act.stime']=array('elt',$stime1);
		}
		//活动结束时间
		$ftime1=I('fdate1','','strtotime');
		$ftime2=I('fdate2','','strtotime');
		if($ftime1 && $ftime2){
			$where['act.ftime']=array('between',array($ftime1,$ftime2));
		}elseif($ftime1){
			$where['act.ftime']=array('egt',$ftime1);
		}elseif($ftime2){			
			$where['act.ftime']=array('elt',$ftime2);
		}

		$count=M('mall_group_act')->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->where($where)->count();

		import('@.ORG.merchant_page');
		$page= new Page($count,30);
		$list=M('mall_group_act')->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->field('act.*,gd.name,gd.store_id store_id2,gd.sell_day,gd.original_stock,gd.price,gd.spec_value,gd.today_sell_spec,gd.stock_num,gd.sell_count')->where($where)->limit($page->firstRow,$page->listRows)->select();
		foreach ($list as $k => $v) {
			$affect_time=$v['affect_time'];
			$affect_time=$affect_time/60;
			$d=floor($affect_time/(24*60));
			$h=floor(($affect_time-$d*24*60)/60);
			$m=$affect_time%60;
			$list[$k]['affect_time']=$d.L_('天').$h.L_('时').$m.L_('分');
			$stock_arr=$this->get_stock_num($v['store_id2'],$v['sell_day'],$v['original_stock'],$v['spec_value'],$v['today_sell_spec'],$v['stock_num'],$v['sell_count']);
			$list[$k]['stock_str']=implode('/', $stock_arr);
		}
		$this->assign('now_store',$now_store);
		$this->assign('list',$list);
		$this->assign('time',time());
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//添加拼团活动
	public function save_group_act(){
		$id=I('id','','intval');
		$store_id=I('store_id','','intval');
		$info=$this->check_group_act($id,$store_id);
		if(IS_POST){
			$data=array();
			$data['good_id']=I('goods_id','','intval');
			$data['stime']=I('sdate','','strtotime');
			$data['ftime']=I('fdate','','strtotime');
			$day=I('day',0,'intval');
			$hour=I('hour',0,'intval');
			$minute=I('minute',0,'intval');
			$data['affect_time']=$day*24*3600+$hour*3600+$minute*60;
			$data['complete_num']=I('complete_num',0,'intval');
			$data['limit_num']=I('limit_num',1,'intval');
			$data['join_type']=I('join_type',1,'intval');
			$join_price=I('join_price','','trim');
			$data['join_price']=implode('/',$join_price);
			$team_price=I('team_price','','trim');
			$data['team_price']=implode('/',$team_price);
			if($id>0){				
				$data['id']=$id;
				$data['status']=1;
				//$info=M('mall_group_act')->where(array('id'=>$id))->find();
				//$data=array_merge($info,$data);
				$res=M('mall_group_act')->data($data)->save();
			}else{
				$data['mer_id']=$_SESSION['merchant']['mer_id'];
				$data['store_id']=$store_id;
				$data['status']=1;
				$data['addtime']=time();
				$res=M('mall_group_act')->data($data)->add();
			}
			if($res!==false){
				$this->success(L_('操作成功！'),U("group_list",array('store_id'=>$store_id)));
			}else{
				$this->error(L_('抱歉，操作失败，请重试！'));
			}
		}else{			
			$now_store = $this->check_store($store_id);			
			if($id>0){
				
				$affect_time=$info['affect_time'];
				$affect_time=$affect_time/60;
				$d=floor($affect_time/(24*60));
				$h=floor(($affect_time-$d*24*60)/60);
				$m=$affect_time%60;
				$info['day']=$d;
				$info['hour']=$h;
				$info['minute']=$m;

				$info['join_price']=explode('/',$info['join_price']);
				$info['team_price']=explode('/',$info['team_price']);
				$return = D('Shop_goods')->format_spec_value($info['spec_value'], $info['goods_id']);
				$info['spec_list']=$return['spec_list']?$return['spec_list']:'';
				$info['list']=$return['list']?$return['list']:'';
				
				/*获取商品规格剩余库存信息*/
				$stock_arr=$this->get_stock_num($info['store_id2'],$info['sell_day'],$info['original_stock'],$info['spec_value'],$info['today_sell_spec'],$info['stock_num'],$info['sell_count']);
				if($info['list']){
					foreach ($info['list'] as $k => $v) {
						$info['list'][$k]['t_stock_num']=$stock_arr[$k];
					}
				}else{
					$info['t_stock_num']=$stock_arr['0'];
				}

				$time=time();
				if($info['status']==0){
					$is_edit=1;
				}elseif($info['stime']>$time || $info['ftime']<$time){
					$is_edit=1;
				}else{
					$is_edit=0;
				}
				$info['is_edit']=$is_edit;
				$this->assign('info',$info);
			}
			$this->assign('now_store',$now_store);
			//echo $merchant_session['store_id'];exit;
			$this->display();
		}
	}

	//校验拼团活动
	private function check_group_act($id=0,$store_id=null){
		$info=array();
		if($id>0){
			$info=M('mall_group_act')->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->field('act.*,gd.store_id store_id2,gd.sell_day,gd.original_stock,spec_value,name,price,goods_id,gd.today_sell_spec,gd.stock_num,gd.sell_count')->where(array('id'=>$id))->find();
			$time=time();
			if($info['is_del']==1){
				$this->error(L_('该活动已删除'),U('Mall/group_list',array('store_id'=>$store_id)));				
			}elseif($info['status']==0){
				$this->error(L_('抱歉，该活动已失效，暂不支持编辑！'),U('Mall/group_list',array('store_id'=>$store_id)));
			}elseif($info['ftime']<$time){
				$this->error(L_('抱歉，该活动已结束，暂不支持编辑！'),U('Mall/group_list',array('store_id'=>$store_id)));
			}
		}
		return $info;
	}

	//设置拼团活动未失效拼团
	public function save_act_status(){
		$id=I('id','','intval');
		if($id<0){
			$this->error(L_('缺少参数！'));
		}
		$data=array();
		$data['id']=$id;
		$data['status']=0;
		$res=M('mall_group_act')->data($data)->save();
		if($res){
			$this->success(L_('操作成功！'));
		}else{
			$this->error(L_('抱歉，操作失败，请重试！'));
		}
	}

	//删除拼团活动拼团
	public function del_group_act(){
		$store_id=I('get.store_id','','intval');
		$id=I('get.id','','intval');
		if(empty($id)){
			$this->error(L_('缺少参数！'));
		}
		$where=array();
		$where['id']=$id;
		$where['is_del']=0;
		$act_info=M('mall_group_act')->where($where)->find();
		if(empty($act_info)){
			$this->error(L_('该拼团活动不存在！'));
		}
		$time=time();
		if($act_info['status']==1 && $act_info['stime']<$time && $act_info['ftime']>$time){
			$this->error(L_('进行中的拼团活动不能删除！'));
		}
		$data=array();
		$data['id']=$id;
		$data['is_del']=1;
		$res=M('mall_group_act')->data($data)->save();
		if($res){
			$this->success(L_('删除成功！'));
		}else{
			$this->error(L_('抱歉，删除失败，请重试！'));
		}
	}

	//拼团活动订单
	public function group_order_list(){
		$store_id=I('get.store_id');
		$now_store = $this->check_store($store_id);	
		$this->assign('now_store',$now_store);
		$where=array();
		$where['act.store_id']=$store_id;
		$where['team.status']=array('neq',4);
		$searchtype=I('searchtype','','trim');
		$keyword=I('keyword','','trim');
		if(!empty($keyword)){
			switch ($searchtype) {
				case 'goods_id':
					$where['act.good_id']=$keyword;
					break;
				case 'goods_name':
					$where['gd.name']=array('like','%'.$keyword.'%');
					break;
			}
		}		
		//活动开始时间
		$stime1=I('sdate1','','strtotime');
		$stime2=I('sdate2','','strtotime');
		if($stime1 && $stime2){
			$where['team.start_time']=array('between',array($stime1,$stime2));
		}elseif($stime1){
			$where['team.start_time']=array('egt',$stime1);
		}elseif($stime2){			
			$where['team.start_time']=array('elt',$stime1);
		}
		//活动结束时间
		$ftime1=I('fdate1','','strtotime');
		$ftime2=I('fdate2','','strtotime');
		if($ftime1 && $ftime2){
			$where['_string']='(team.start_time+affect_time)>='.$ftime1.' and (team.start_time+affect_time)<='.$ftime2;
		}elseif($ftime1){
			$where['_string']='(team.start_time+affect_time)>='.$ftime1;
		}elseif($ftime2){
			$where['_string']='(team.start_time+affect_time)<='.$ftime2;
		}

		$count=M('mall_group_act')->alias('act')->join(C('DB_PREFIX').'mall_group_team team on act.id=team.group_id')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->where($where)->count(1);
		import('@.ORG.merchant_page');
		$page= new Page($count,20);
		$list=M('mall_group_act')->alias('act')->join(C('DB_PREFIX').'mall_group_team team on act.id=team.group_id')->join(C('DB_PREFIX').'shop_goods gd on act.good_id=gd.goods_id')->where($where)->limit($page->firstRow,$page->listRows)->field('team.*,gd.name,(team.start_time+affect_time) as end_time')->limit($page->firstRow,$page->listRows)->order('team.id desc')->select();
		$time=time();
		foreach ($list as $k => $v) {
			if($v['end_time']<$time && $v['status']==0){
				M('mall_group_team')->where(array('id'=>$v['id']))->setField('status',2);
				$list[$k]['status']=2;

			}
			$nickname=M('user')->where(array('uid'=>$v['user_id']))->getfield('nickname');
			$list[$k]['nickname']=$nickname;
		}

		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//拼团团队用户列表
	public function group_team_user_list(){
		$store_id=I('get.store_id');
		$now_store = $this->check_store($store_id);	
		$this->assign('now_store',$now_store);
		$mer_id=$_SESSION['merchant']['mer_id'];
		$team_id=I('get.tid','','intval');
		$list=D('Mall_group_team')->get_team_user_list($team_id,$mer_id);
		$this->assign('list',$list);
		$this->display();
	}

	//批量成团
	public function save_group_status(){
		$status=I('status','','intval');
		$id=I('id','','trim');
		$id_arr=explode(',',$id);
		if($status==3){
			foreach ($id_arr as $k => $v){
				$team_info=M('Mall_group_team')->where(array('id'=>$v))->find();
				if(isset($team_info) && $team_info['complete_num']>$team_info['num']){
					D('Mall_group_team')->create_robot($team_info);
					$where['team_id']=$v;
                    $where['paid']=1;
                    $where['order_from']=1;
                    $order =  D("Shop_order")->where($where)->select();
                    if(!empty($order)){
                        foreach ($order as $key=>$val){
                            D('Shop_order')->auto_set($val['order_id']);
                        }
                    }
				}
			}
			$res=D('Mall_group_team')->update_group_status($id_arr,$status);
			$this->ajaxReturn($res);

		}
	}

	//满减送列表
	public function full_list()
	{

		$store_id=I('store_id','','intval');
		$now_store = $this->check_store($store_id);
		$mer_id=$_SESSION['merchant']['mer_id'];

		$where=array();
		if(!empty($_GET['keyword'])){
			$where['act.name'] = array('like','%'.$_GET['keyword'].'%');
		}
		//活动开始时间
		$stime1=I('sdate1','','strtotime');
		$stime2=I('sdate2','','strtotime');
		if($stime1 && $stime2){
			$where['act.stime']=array('between',array($stime1,$stime2));
		}elseif($stime1){
			$where['act.stime']=array('egt',$stime1);
		}elseif($stime2){
			$where['act.stime']=array('elt',$stime1);
		}
		//活动结束时间
		$ftime1=I('fdate1','','strtotime');
		$ftime2=I('fdate2','','strtotime');
		if($ftime1 && $ftime2){
			$where['act.ftime']=array('between',array($ftime1,$ftime2));
		}elseif($ftime1){
			$where['act.ftime']=array('egt',$ftime1);
		}elseif($ftime2){
			$where['act.ftime']=array('elt',$ftime2);
		}
		$where['act.mer_id']=$mer_id;
		$where['act.store_id']=$store_id;
		$where['act.is_del']=0;
		$count=M('Mall_full_act')->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->where($where)->count();
		import('@.ORG.merchant_page');
		$page= new Page($count,30);
		$list=M('Mall_full_act')->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->field('act.*')->where($where)->order('id desc')->limit($page->firstRow,$page->listRows)->select();

        foreach($list as $k=>&$v){
			$model_list = M('Mall_full_layer')->where(array('id'=>array('in',$v['layer_num_id'])))->select();
			foreach($model_list as $k1=>$item){
				$coupon = M('card_new_coupon')->where(array('coupon_id'=>$item['coupon_id']))->find();
				$v['mall_full_layer'][$k]['coupon_name'] = $coupon['name'];
				$shop_good=D('Shop_goods')->where(array('goods_id'=>$item['give_goods_id']))->find();
				$v['mall_full_layer'][$k]['goods_name'] =$shop_good['name'];
			}
		}

		$this->assign('list',$list);
		$this->assign('time',time());
		$this->assign('pagebar',$page->show());
		$this->assign('now_store',$now_store);
        $this->display();
	}
	//新建满减送活动
	public function save_full_info()
	{

		$store_id=I('store_id','','intval');
		if(IS_POST){

			//$insert = array();
			$arr = array();
			$data = array();
			if(empty($_POST['goods_price'])){

				$this->error('满减金额不能为空!');
			}
			if(empty($_POST['give_goods_id'][0])){
				if(empty($_POST['coupon_id'][0])){

					$this->error('优惠劵或者商品必须选择一个!');
				}
			}


			foreach($_POST['goods_price'] as $key => $value){
				//$insert[] = [$_POST['goods_price'][$key], $_POST['coupon_id'][$key], $_POST['give_goods_id'][$key]];
				if($_POST['goods_price']){
					$data['goods_price'] = $_POST['goods_price'][$key];
				}
				if($_POST['give_goods_id']){
					$data['give_goods_id'] = $_POST['give_goods_id'][$key];
				}
				if($_POST['coupon_id']){
					$data['coupon_id'] = $_POST['coupon_id'][$key];
				}
				$arr[] = M('Mall_full_layer')->add($data);
			}
			$modelStr = implode(',',$arr);
			$data['mer_id'] = $_SESSION['merchant']['mer_id'];
			$data['store_id'] = $store_id;
			$data['name'] = $_POST['name'];
			$data['stime']=I('sdate','','strtotime');
			$data['ftime']=I('fdate','','strtotime');
			$data['addtime'] = time();
			$data['is_del'] = 0;
			$data['status'] = 1;
			$data['type'] = $_POST['type'];
			$data['layer_num_id'] = $modelStr;
			$data['activity_goods_type'] = I('activity_goods_type','','int');
			if($_POST['activity_goods_type'] == 1){
				$where['_string'] = "cat_fid > 0";
				$where['store_id'] = $store_id;
				$where['status'] = 1;
				$shop_goods = M('shop_goods')->where($where)->select();
				$arr = [];
				foreach($shop_goods as $value){
					$arr[] = $value['goods_id'];
				}
				$goodsId = implode(',',$arr);
				$data['goods_id'] = $goodsId;
			}else{

				$data['goods_id'] = implode(',',$_POST['goods_str']);
			}
			if(M('mall_full_act')->add($data)){
				$this->success(L_('操作成功！'),U('full_list',array('store_id'=>$store_id)));
			}else{
				$this->error(L_('抱歉，活动删除失败，请重试！'));
			}
		}else{
			$where['_string'] = "cat_fid > 0";
			$where['status'] = 1;
			$data = D('Shop_goods')->where(array('store_id'=>$store_id))->where($where)->order('last_time desc')->select();
			$list=$data;

			$this->assign('list',$list);
			
			$wheres['cate_name'] = array('in','mall,all');
			$wheres['is_receive_full_reduction'] = 1;
			$wheres['mer_id'] = $this->merchant_session['mer_id'];
			$wheres['status'] = 1;
			$receive_coupon_list = D('Card_new_coupon')->where($wheres)->select();
			$this->assign('receive_coupon_list',$receive_coupon_list);
			
			$now_store = $this->check_store($store_id);
			$this->assign('now_store',$now_store);
			$this->display();
		}
	}



	public function edit_full()
	{
		$id=I('id','','intval');
		$store_id=I('store_id','','intval');
		if(IS_POST){
			$data['id'] = $id;
			//$data['stime']=I('sdate','','strtotime');
			$data['ftime']=I('fdate','','strtotime');
			$res=M('mall_full_act')->data($data)->save();

			if($res!==false){
				$this->success(L_('操作成功！'),U('full_list',array('store_id'=>$store_id)));
			}else{
				$this->error(L_('抱歉，操作失败，请重试！'));
			}
		}else{
			$where['_string'] = "cat_fid > 0";
			$data = D('Shop_goods')->where(array('store_id'=>$store_id))->where($where)->order('last_time desc')->select();
			$list=$data;

			$this->assign('list',$list);

			$receive_coupon_list = D('Card_new_coupon')->where('is_receive_full_reduction=1 AND (cate_name="mall" or cate_name="") AND mer_id='.$_SESSION['merchant']['mer_id'])->select();
			$this->assign('receive_coupon_list',$receive_coupon_list);

			$now_store = $this->check_store($store_id);
			$this->assign('now_store',$now_store);

			$info = M('mall_full_act')->where(array('id'=>$id))->find();
			$this->assign('info',$info);

			$shop_goods_list = D('Shop_goods')->where(array('goods_id'=>array('in',$info['goods_id'])))->select();
			$this->assign('shop_goods_list',$shop_goods_list);

			$full_laryer = M('Mall_full_layer')->where(array('id'=>array('in',$info['layer_num_id'])))->select();
			foreach($full_laryer as $k=>&$value){
				$value['coupon_layer_id'] =$value['coupon_id'];
			}
			$this->assign('full_laryer',$full_laryer);


			$this->display();
		}
	}

	public function full_delete()
	{
		$id=I('id','','intval');
		if(empty($id)){
			$this->error(L_('缺少参数！'));
		}
		$where=array();
		$where['id']=$id;
		$where['id_del']=0;
		$info=M('Mall_full_act')->where($where)->find();
		if(empty($info)){
			$this->error(L_('活动不存在！'));
		}
//		$time=time();
//		if($info['status']==1 && ($info['stime']<=$time && $info['ftime']>=$time)){
//			$this->error(L_('活动正在进行中，暂不支持删除！'));
//		}
		$data=array();
		$data['id']=$id;
		$data['is_del']=1;
		$res=M('Mall_full_act')->data($data)->save();
		if($res){
			$this->success(L_('活动删除成功！'));
		}else{
			$this->error(L_('抱歉，活动删除失败，请重试！'));
		}
	}

	public function full_invalid()
	{
		$id=I('id','','intval');
		if(empty($id)){
			$this->error(L_('缺少参数！'));
		}
		$where=array();
		$where['id']=$id;

		$info=M('Mall_full_act')->where($where)->find();
		if(empty($info)){
			$this->error(L_('活动不存在！'));
		}

		$data=array();
		$data['id']=$id;
		$data['status']=0;
		$res=M('Mall_full_act')->data($data)->save();
		if($res){
			$this->success(L_('失效成功！'));
		}else{
			$this->error(L_('抱歉，活动删除失败，请重试！'));
		}
	}
	/*砍价活动start*/
	//砍价活动列表	
	public function bargain_list(){
		$store_id=I('store_id','','intval');
		$now_store = $this->check_store($store_id);
		$mer_id=$_SESSION['merchant']['mer_id'];
		$where=array();
		$where['act.mer_id']=$mer_id;
		$where['act.store_id']=$store_id;
		$where['act.is_del']=0;
		$searchtype=I('searchtype','','trim');
		$keyword=I('keyword','','trim');
		if(!empty($keyword)){
			switch ($searchtype) {
				case 'goods_id':
					$where['act.goods_id']=$keyword;
					break;
				case 'goods_name':
					$where['gd.name']=array('like','%'.$keyword.'%');
					break;
			}
		}
		//活动开始时间
		$stime1=I('sdate1','','strtotime');
		$stime2=I('sdate2','','strtotime');
		if($stime1 && $stime2){
			$where['act.stime']=array('between',array($stime1,$stime2));
		}elseif($stime1){
			$where['act.stime']=array('egt',$stime1);
		}elseif($stime2){			
			$where['act.stime']=array('elt',$stime1);
		}
		//活动结束时间
		$ftime1=I('fdate1','','strtotime');
		$ftime2=I('fdate2','','strtotime');
		if($ftime1 && $ftime2){
			$where['act.ftime']=array('between',array($ftime1,$ftime2));
		}elseif($ftime1){
			$where['act.ftime']=array('egt',$ftime1);
		}elseif($ftime2){			
			$where['act.ftime']=array('elt',$ftime2);
		}
		$count=M('mall_bargain_act')->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->where($where)->count();
		import('@.ORG.merchant_page');
		$page= new Page($count,30);
		$list=M('mall_bargain_act')->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->field('act.*,gd.name,gd.store_id store_id2,gd.sell_day,gd.original_stock,gd.price,gd.spec_value,gd.today_sell_spec,gd.stock_num,gd.sell_count')->where($where)->limit($page->firstRow,$page->listRows)->select();
		foreach ($list as $k => $v) {
			$affect_time=$v['affect_time'];
			$affect_time=$affect_time/60;
			$d=floor($affect_time/(24*60));
			$h=floor(($affect_time-$d*24*60)/60);
			$m=$affect_time%60;
			$list[$k]['affect_time']=$d.L_('天').$h.L_('时').$m.L_('分');
			$stock_arr=$this->get_stock_num($v['store_id2'],$v['sell_day'],$v['original_stock'],$v['spec_value'],$v['today_sell_spec'],$v['stock_num'],$v['sell_count'],$v['spec_id']);
			$list[$k]['stock_str']=implode('/', $stock_arr);
		}
		$this->assign('now_store',$now_store);
		$this->assign('list',$list);
		$this->assign('time',time());
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//编辑砍价活动
	public function save_bargain_info(){
		$id=I('id','','intval');		
		$store_id=I('store_id','','intval');
		//校验砍价活动是否可编辑
		$info=$this->check_bargin_act($id,$store_id);
		if(IS_POST){
			$goods_id_arr=I('goods_id');
			$spec_id_arr=I('spec_id');
			$spec_price_arr=I('spec_price');
			if(empty($goods_id_arr)){
				$this->error(L_('最少选择一个商品！'));
			}
			$arr=array();
			$arr['spec_id']=I('spec_id','','trim');
			$arr['stime']=I('sdate','','strtotime');
			$arr['ftime']=I('fdate','','strtotime');
			$day=I('day',0,'intval');
			$hour=I('hour',0,'intval');
			$minute=I('minute',0,'intval');
			$arr['affect_time']=$day*24*3600+$hour*3600+$minute*60;
			$arr['floor_price']=I('floor_price',0,'floatval');
			$arr['low_price']=I('low_price','','floatval');
			$arr['high_price']=I('high_price','','floatval');
			$arr['min_num']=I('min_num','','intval');
			$arr['max_bar_num']=I('max_bar_num',0,'intval');
			if($id>0){
				$data=$arr;
				$data['id']=$id;
				foreach($goods_id_arr as $k=>$v){
					$data['goods_id']=$v;
					$data['spec_id']=$spec_id_arr[$k];
					$data['spec_price']=$spec_price_arr[$k];
				}
				$data['status']=1;
				$res=M('mall_bargain_act')->data($data)->save();
			}else{
				$arr['mer_id']=$_SESSION['merchant']['mer_id'];
				$arr['store_id']=$store_id;
				$arr['addtime']=time();
				$data=array();
				foreach($goods_id_arr as $k=>$v){
					$arr['goods_id']=$v;
					$arr['spec_id']=$spec_id_arr[$k];
					$arr['spec_price']=$spec_price_arr[$k];
					$data[]=$arr;
				}
				$res=M('mall_bargain_act')->addAll($data);
			}
			if($res!==false){
				$this->success(L_('操作成功！'),U('bargain_list',array('store_id'=>$store_id)));
			}else{
				$this->error(L_('抱歉，操作失败，请重试！'));
			}
		}else{
			$now_store = $this->check_store($store_id);	
			$this->assign('now_store',$now_store);
			if($id>0){
				$affect_time=$info['affect_time'];
				$affect_time=$affect_time/60;
				$d=floor($affect_time/(24*60));
				$h=floor(($affect_time-$d*24*60)/60);
				$m=$affect_time%60;
				$info['day']=$d;
				$info['hour']=$h;
				$info['minute']=$m;
				/*获取剩余库存*/
				$stock_arr=$this->get_stock_num($info['store_id2'],$info['sell_day'],$info['original_stock'],$info['spec_value'],$info['today_sell_spec'],$info['stock_num'],$info['sell_count'],$info['spec_id']);

				if($info['spec_id']>0){
					$return = D('Shop_goods')->format_spec_value($info['spec_value'], $info['goods_id']);
					$spec_list=$return['spec_list'];
					$list=$return['list'];					
					$reg_list=array();
					foreach ($list as $k => $v) {
						if($k==$info['spec_id']){
							$arr=array();
							$reg_name='';
							foreach ($v['spec'] as $k2 => $v2) {
								$reg_name.=$spec_list[$k2]['name'].':'.$v2['spec_val_name'].'&nbsp;&nbsp;&nbsp;&nbsp;';
							}
							$info['reg_name']=$reg_name;
							$info['price']=$v['price'];
						}
					}
					$info['stock_num']=$stock_arr[$info['spec_id']];
				}else{
					$info['stock_num']=$stock_arr['0'];
				}
				$time=time();
				if($info['status']==0){
					$is_edit=1;
				}elseif($info['stime']>$time || $info['ftime']<$time){
					$is_edit=1;
				}else{
					$is_edit=0;
				}
				$info['is_edit']=$is_edit;
				$this->assign('info',$info);
			}			
			$this->display();
		}
	}

	//校验砍价活动
	private function check_bargin_act($id=0,$store_id){
		$info=array();
		if($id>0){
			$info=M('mall_bargain_act')->alias('act')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->field('act.*,gd.store_id store_id2,sell_day,original_stock,spec_value,name,price,today_sell_spec,stock_num,sell_count')->where(array('id'=>$id))->find();
			$time=time();
			if($info['is_del']==1){
				$this->error(L_('该活动已删除！'),U('Mall/bargain_list',array('store_id'=>$store_id)));
				exit;
			}elseif($info['status']==0){
				$this->error(L_('抱歉，该活动已失效，暂不支持编辑！'),U('Mall/bargain_list',array('store_id'=>$store_id)));
				exit;
			}elseif($info['ftime']<$time){
				$this->error(L_('抱歉，该活动已结束，暂不支持编辑！'),U('Mall/bargain_list',array('store_id'=>$store_id)));
				exit;
			}
		}
		return $info;
	}

	//关闭砍价活动
	public function save_bargain_status(){
		$id=I('id','','intval');
		if(empty($id)){
			$this->error(L_('缺少参数！'));
		}
		$data=array();
		$data['id']=$id;
		$data['status']=0;
		$res=M('mall_bargain_act')->data($data)->save();
		if($res){
			$this->success(L_('活动关闭成功！'));
		}else{
			$this->error(L_('抱歉，活动关闭失败，请重试！'));
		}
	}

	//删除砍价活动
	public function del_bargin_act(){
		$id=I('id','','intval');
		if(empty($id)){
			$this->error(L_('缺少参数！'));
		}
		$where=array();
		$where['id']=$id;
		$where['id_del']=0;
		$info=M('mall_bargain_act')->where($where)->find();
		if(empty($info)){
			$this->error(L_('活动不存在！'));
		}
		$time=time();
		if($info['status']==1 && ($info['stime']<=$time && $info['ftime']>=$time)){
			$this->error(L_('活动正在进行中，暂不支持删除！'));
		}
		$data=array();
		$data['id']=$id;
		$data['is_del']=1;
		$res=M('mall_bargain_act')->data($data)->save();
		if($res){
			$this->success(L_('活动删除成功！'));
		}else{
			$this->error(L_('抱歉，活动删除失败，请重试！'));
		}
	}

	//用户砍价活动列表
	public function bargain_team_list(){
		$store_id=I('get.store_id');
		$now_store = $this->check_store($store_id);	
		$this->assign('now_store',$now_store);
		$where=array();
		$where['act.store_id']=$store_id;
		$searchtype=I('searchtype','','trim');
		$keyword=I('keyword','','trim');
		if(!empty($keyword)){
			switch ($searchtype) {
				case 'goods_id':
					$where['act.goods_id']=$keyword;
					break;
				case 'goods_name':
					$where['gd.name']=array('like','%'.$keyword.'%');
					break;
			}	
		}
		//活动开始时间
		$stime1=I('sdate1','','strtotime');
		$stime2=I('sdate2','','strtotime');
		if($stime1 && $stime2){
			$where['team.start_time']=array('between',array($stime1,$stime2));
		}elseif($stime1){
			$where['team.start_time']=array('egt',$stime1);
		}elseif($stime2){			
			$where['team.start_time']=array('elt',$stime1);
		}
		//活动结束时间
		$ftime1=I('fdate1','','strtotime');
		$ftime2=I('fdate2','','strtotime');
		if($ftime1 && $ftime2){
			$where['_string']='(team.start_time+affect_time)>='.$ftime1.' and (team.start_time+affect_time)<='.$ftime2;
		}elseif($ftime1){
			$where['_string']='(team.start_time+affect_time)>='.$ftime1;
		}elseif($ftime2){
			$where['_string']='(team.start_time+affect_time)<='.$ftime2;
		}

		$count=M('mall_bargain_act')->alias('act')->join(C('DB_PREFIX').'mall_bargain_team team on act.id=team.bar_id')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->where($where)->count(1);
		import('@.ORG.merchant_page');
		$page= new Page($count,20);
		$list=M('mall_bargain_act')->alias('act')->join('right join '.C('DB_PREFIX').'mall_bargain_team team on act.id=team.bar_id')->join(C('DB_PREFIX').'shop_goods gd on act.goods_id=gd.goods_id')->where($where)->limit($page->firstRow,$page->listRows)->field('team.*,gd.name,(team.start_time+affect_time) as end_time,act.goods_id,act.spec_id,gd.spec_value')->limit($page->firstRow,$page->listRows)->order('team.id desc')->select();
		$time=time();
		foreach ($list as $k => $v) {
			if($v['end_time']<$time && $v['status']==0){
				M('mall_bargain_team')->where(array('id'=>$v['id']))->setField('status',2);
				$list[$k]['status']=2;
			}
			if($v['spec_id']>0){
				$info=D('Shop_goods')->format_spec_value($v['spec_value'],$v['goods_id']);
				$spec_list=$info['spec_list'];
				$reg_list=$info['list'];
				foreach ($reg_list as $k2 => $v2) {
					if($k2==$v['spec_id']){
						$reg_name='';
						foreach ($v2['spec'] as $k3 => $v3) {
							$reg_name.=$spec_list[$k3]['name'].':'.$v3['spec_val_name'].'&nbsp;&nbsp; ';
						}
						$list[$k]['name']=$v['name'].'('.$reg_name.')';	
					}
					
				}	
			}
			$nickname=M('user')->where(array('uid'=>$v['user_id']))->getfield('nickname');
			$list[$k]['nickname']=$nickname;
		}

		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//砍价活动帮助砍价列表
	public function bargain_team_user_list(){
		$store_id=I('get.store_id');
		$now_store = $this->check_store($store_id);	
		$this->assign('now_store',$now_store);
		$mer_id=$_SESSION['merchant']['mer_id'];
		$team_id=I('get.tid','','intval');
		$list=D('Mall_bargain_team')->get_team_user_list($team_id,$mer_id);
		$this->assign('list',$list);
		$this->display();
	}

	//商城商品列表
	public function mall_goods_list(){
		$store_id=I('get.store_id','','intval');
		$now_store=$this->check_store($store_id);
		$data=D('Shop_goods')->get_list_by_storeid_diypage($store_id);
		$list=$data['good_list'];
		$where=array();
		$where['store_id']=$store_id;
		$arr=M('mall_group_act')->where($where)->getfield('good_id',true);
		$arr=(array)$arr;
		foreach ($list as $k => $v) {
			$is_use=0;
			if(in_array($v['goods_id'],$arr)){
				$is_use=1;
			}
			$list[$k]['is_use']=$is_use;
		} 
		$data['goods_list']=$list;
		$this->assign($data);
		$this->assign('now_store',$now_store);
		$this->display();
	}

	//获取商品信息
	public function get_good_info(){
		$goods_id=I('goods_id','','intval');
		if(empty($goods_id)){
			$this->error(L_('缺少参数！'));
		}
		$info=M('shop_goods')->where(array('goods_id'=>$goods_id))->field('goods_id,store_id,sell_day,original_stock,name,price,cat_fid,seckill_price,spec_value,today_sell_spec,stock_num,sell_count')->find();

		if(empty($info)){
			$this->error(L_('网络出错，请重试！'));
		}
		if($info['cat_fid']<=0){
			$this->error(L_('抱歉，该商品不是商城商品，不能设置为拼团商品'));
		}
		if($info['seckill_price']>0){
			$this->error(L_('抱歉，限时购商品不能设置为拼团商品'));
		}
		$return = D('Shop_goods')->format_spec_value($info['spec_value'], $goods_id);
		$info['spec_list']=$return['spec_list']?$return['spec_list']:'';
		$info['list']=$return['list']?$return['list']:'';
		/*获取商品规格剩余库存信息*/
		$stock_arr=$this->get_stock_num($info['store_id'],$info['sell_day'],$info['original_stock'],$info['spec_value'],$info['today_sell_spec'],$info['stock_num'],$info['sell_count']);
		if($info['list']){
			foreach ($info['list'] as $k => $v) {
				$info['list'][$k]['t_stock_num']=$stock_arr[$k];
			}
		}else{
			$info['t_stock_num']=$stock_arr['0'];
		}
		
		$this->success($info);
	}


	public function order()
	{
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
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
// 		if ($status != -1) {
// 		    if ($status == 2) {
// 		        $where['status'] = array('in', '2, 3');
// 		    } elseif ($status == 6) {
// 		        $where['paid'] = 0;
// 		    } else {
// 		        $where['status'] = $status;
// 		    }
// 		}

		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$where['real_orderid'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
				unset($where['orderid']);
				$where['order_id'] = $tmp_result['order_id'];
			} elseif ($_GET['searchtype'] == 'name') {
				$where['username'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['userphone'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'third_id') {
				$where['third_id'] =$_GET['keyword'];
			}
		}

		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';

		if($status == 100){
			$where['paid'] = 0;
		}elseif($status == 12){
			$where['paid'] = 1;
			$where['status'] = array('in',array(0,1,9));
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
			$where['pay_type'] = $pay_type;
		}else if($pay_type=='balance'){
			$where['_string'] = "(`balance_pay`<>0 OR `merchant_balance` <> 0 OR `card_give_money` <> 0)";
		}

		$period = array();
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}

			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));	

			$_GET['searchTimeType'] = $_GET['searchTimeType'] ? $_GET['searchTimeType'] : 'create_time';
			$where['_string'] .=( $where['_string']?' AND ':''). " (".$_GET['searchTimeType']." BETWEEN ".$period[0].' AND '.$period[1].")";
			//$condition_where['_string']=$time_condition;
		}
		//来源是商城的订单
		$where['order_from']=1;
		if($_GET['team_id']>0){
			$where['group_id']=array('gt',0);
			$where['team_id']=$_GET['team_id'];
			$this->assign('team_id',$_GET['team_id']);
		}else{
			if(isset($where['_string'])){
				$where['_string'].=' and ';
			}else{
				$where['_string']='';
			}
			$where['_string'].='(group_id=0 or (group_id>0 and buy_type=1))';
		}
		$return = D('Shop_order')->get_order_list($where, $order_sort, 2, false);
        $group_arr=D('Mall_group_team')->get_order_group_status();
		foreach ($return['order_list'] as $k => $v) {
			if($v['group_id']>0){
				$v['group_status']=$group_arr[$v['order_id']];
			}
			$return['order_list'][$k]=$v;
		}		
		$this->assign('status_list', D('Shop_order')->status_list);
		$this->assign($return);
		$this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status,'pay_type'=>$pay_type));

		$field = 'sum(price) AS total_price, sum(price - card_price - merchant_balance - balance_pay - payment_money - score_deducte - coupon_price) AS offline_price, sum(card_price + merchant_balance + balance_pay + payment_money + score_deducte + coupon_price) AS online_price';
		$count_where = "store_id='{$now_store['store_id']}' AND paid=1 AND is_del=0 AND status<>4 AND status<>5 AND (pay_type<>'offline' OR (pay_type='offline' AND third_id<>''))";
		if ($period) {
		    $count_where .= " AND (" . ($_GET['searchTimeType'] ?? 'create_time') . " BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
		}
  		$count_where.=' and order_from=1';
		$result_total = D('Shop_order')->field($field)->where($count_where)->select();
		$result_total = isset($result_total[0]) ? $result_total[0] : '';
		$this->assign($result_total);
		$pay_method_wap = D('Config')->get_pay_method('','',1);
		$pay_method_app = D('Config')->get_pay_method('','',0,1);
		$pay_method = array_merge($pay_method_wap,$pay_method_app);
		$this->assign('pay_method',$pay_method);
		$this->display();
	}

	public function order_detail()
	{
		if(strlen($_GET['order_id'])>=20){
			$now_shop_order = D('Shop_order')->where(array('real_orderid'=>$_GET['order_id']))->find();
			$_GET['order_id'] = $now_shop_order['order_id'];
		}
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		$order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'mer_id' => $this->merchant_session['mer_id']));
		$order['user_adress'] = D('User_adress')->get_one_adress($order['uid'],intval($order['address_id']));
		if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $order['store_id']) {
		    $this->error(L_('您没有这个权限'));
		}
		$tempList = array();
		/*预售商品最新价格*/
        if(!empty($order['book_id']) && $order['book_id']*1>0){
            $order['book_good_new_price']=$order['bargin_price']+$order['rest_price']-$order['freight_charge'];
            $order['book_order_pay']=$order['bargin_price']+$order['rest_price'];
        }
       // fdump($order,"y888888",1);
		foreach($order['info'] as $v) {
		    /*预售判断*/
            $v['book_style']=0;
		    if(!empty($order['book_id']) && $order['book_id']*1>0){
                $v['book_style']=1;
                $v['book_all_pay']=$order['bargin_price']+$order['rest_price']-$order['freight_charge']+$order['book_discount_price'];
                $v['book_discount_price']=$order['book_discount_price'];
            }

			if($v['give_goods_id']){
				$shop_goods_name = D('Shop_goods')->field('goods_id,name,price')->where(array('goods_id'=>$v['give_goods_id']))->find();
				$v['give_goods_name'] = $shop_goods_name['name'];
				$v['give_goods_price'] = $shop_goods_name['price'];
			}
			if($v['give_coupon_id']){
				$coupon = D('Card_new_coupon')->field('name')->where(array('coupon_id'=>$v['give_coupon_id']))->find();
				$v['give_coupon_name'] = $coupon['name'];
			}
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
		//获取取消订单时间
        if ($order['status'] == 5) {
            $order['cancel_time'] = M('Shop_order_log')->where(['status' => 10, 'order_id' => intval($_GET['order_id'])])->getField('dateline');
        }

        /*预售付款总额*/
        if(!empty($order['book_id']) && $order['book_id']*1>0){
            $order['book_all_pay']=$order['bargin_price']+$order['rest_price'];
        }

		$store = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
		$this->assign('store', $store);
		$this->assign('order', $order);
        //退款详情
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $refundList = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'uid' => $order['uid']))->order('id DESC')->select();

        $sql = "SELECT g.image, d.goods_id, d.name, d.price, d.num, d.unit, d.spec, d.id, d.refund_id FROM " . C('DB_PREFIX') . "shop_goods AS g INNER JOIN " . C('DB_PREFIX') . "shop_refund_detail AS d ON g.goods_id=d.goods_id WHERE d.order_id={$order_id}";
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
        $is_authentication = D('Merchant_store')->where(array('store_id' => $order['store_id']))->getField('is_authentication');
        //实名认证
        if($is_authentication){
            $authenticationInfo = D('Mall_authentication')->where(array('uid'=>$order['uid']))->find();
            $authenticationInfo['authentication_field'] = unserialize($authenticationInfo['authentication_field']);
            $this->assign('authentication_field',$authenticationInfo['authentication_field']);
        }
        $this->assign('refund_list', $refundList);
		$this->display();
	}

	public function change_mall_order_note()
	{
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
				if ($target_sort = M(_view('Shop_goods_sort'))->field(true)->where(array('store_id' => $store_id, 'sort_name' => $sort['sort_name'],'source_sort_id'=>$source_sort_id))->find()) {
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

									$spec_value_list = M('Shop_goods_spec_value')->field(true)->where(array('sid' => $source_spec_id))->select();
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

	    $sql = "SELECT s.store_id, s.name FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON sh.store_id=s.store_id WHERE s.have_shop=1 AND s.status=1 AND s.store_id<>{$store_id} AND s.mer_id={$this->merchant_session['mer_id']}";
	    $res = D()->query($sql);
	    $this->assign('stores', $res);
	    $this->assign('store_id', $store_id);
	    $this->display();
	}

	public function foodshop()
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
        $sortList = $shopGoodsSortDB->field(true)->where(array('store_id' => $store_id, 'fid' => 0))->select();

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
                    $sql = "SELECT count(1) as cnt, sum(d.num) as totalNum, sum(d.price) as totalPrice, sum(d.cost_price) as costPrice FROM " . C('DB_PREFIX') . 'shop_order AS o INNER JOIN ' . C('DB_PREFIX') . 'shop_order_detail AS d ON d.order_id=o.order_id WHERE o.paid=1 AND (o.pay_type<>"offline" OR (o.pay_type="offline" AND o.third_id>0)) AND o.status<>4 AND o.status<>5' . $where;
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
//         $now_goods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
//         $now_goods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
        $now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_goods['list'] = isset($return['list']) ? $return['list'] : '';
        $now_goods['discount_info'] = '';
        $now_goods['status'] = 1;
//         echo '<pre/>';
//         print_r($now_goods);die;
        if ($marketGoodsOld) {
            if ($marketGoodsOld['spec_value'] && $now_goods['list']) {
                $spec_array = explode('#', $marketGoodsOld['spec_value']);
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = str_replace(':', '_', $row_array[0]);
//                     echo $i . '<br/>';
                    if (isset($now_goods['list'][$i])) {
                        $tArray = explode(':', $row_array[1]);
                        $now_goods['list'][$i]['wholesale_prices'] = $tArray[1];
                        $now_goods['list'][$i]['min_nums'] = $tArray[2];
                        $now_goods['list'][$i]['stocks'] = $tArray[3];
                        $now_goods['list'][$i]['group_team_prices'] = $tArray[5];
                        $now_goods['list'][$i]['group_join_prices'] = $tArray[6];
                        $now_goods['list'][$i]['vip_wholesale_prices'] = $tArray[7];
                    }
                }
            }

            $now_goods['wholesale_price'] = $marketGoodsOld['price'];
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

        }


        if (IS_POST) {
//             echo '<pre/>';
//             print_r($_POST);die;

            if ($now_goods['spec_list']) {
                $wholesale_prices = $_POST['wholesale_prices'];
                $group_team_prices_arr = $_POST['group_team_prices_arr'];
                $group_join_prices_arr = $_POST['group_join_prices_arr'];

                if (1==$this->config['merchant_type_switch']) {
                    $vip_wholesale_prices = $_POST['vip_wholesale_prices'];
                }
                $min_nums = $_POST['min_nums'];
                $stocks = $_POST['stocks'];
                foreach ($wholesale_prices as $i => $val) {
                    if (!((empty($wholesale_prices[$i]) && empty($stocks[$i]) && empty($min_nums[$i])) || ($wholesale_prices[$i] && $stocks[$i] && $min_nums[$i]))) {
                        $error_tips .= '请正确设置每一种规格对应的批发参数<br/>';
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

                if (empty($marketGoods['cat_fid']) || empty($marketGoods['cat_id'])) {
                    $this->error(L_('请选择商品分类！'));
                }
                if (empty($now_goods['spec_list'])) {
                    $marketGoods['min_num'] = $min_num;
                    $marketGoods['stock_num'] = $stock;
                    $marketGoods['price'] = $wholesale_price;
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
                            // 如果存在vip价格加上vip价格 占据地8个位置
                            $row_array[1].=':'.$vip_wholesale_prices[$i];

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
                        $data['group_team_price'] = $group_team_price;
                        $data['vip_wholesale_price'] = $vip_wholesale_price;

                        M('Market_goods')->where(array('mid' => $mid))->save($data);
                    }
                    $this->success(L_('发布成功！'), U('Market/index'));
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
            $category_list = D('Goods_wholesale_category')->get_list();
            $this->assign('category_list', json_encode($category_list));
            $this->assign('now_goods', $now_goods);
            $this->assign('now_store', $now_store);
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

        foreach ($store_ids as $store_id) {
            if ($target_store = M('Merchant_store')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'store_id' => $store_id, 'have_shop' => 1))->find()) {
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
                    if(!empty($goods['image'])){
                        $tmp_pic_arr = explode(';', $goods['image']);
                        foreach ($tmp_pic_arr as $key => $value) {
                            $image_tmp = explode(',', $value);
                            if (strstr($image_tmp[0], 'sysgoods_')) {
                                continue;
                            }
                            $images = $goods_image_class->get_image_by_path($value);
                            foreach ($images as $img) {
                                $img = str_replace(C('config.site_url'), '', $img);
                                $newFile = './upload/foodshop_goods/' . str_replace('/upload/goods/', '', $img);
                                dmkdir($newFile, 0777, false);
                                $t = copy('.' . $img, $newFile);
                            }
                        }
                    }
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
        $pack_id = intval($_GET['pack_id']);
        $code = intval($_GET['code']);
        if($pack_id){
        	$qrCon = $this->config['site_url'] . '/wap.php?g=Wap&c=Mall&a=pack_act&id=' . $pack_id;
        }elseif($code){
        	$qrCon = $this->config['site_url'] . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $code;
        }
		
		import('@.ORG.phpqrcode');
		$size = $_GET['size'] ? $_GET['size']: 12;
		QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1);
    }

    public function cardqrcode()
    {
        import('@.ORG.phpqrcode');
        $code=I('get.code','','intval');
        $param='&goods_id='.$code;
        $type=I('get.type','','intval');
        $act_id=I('get.act_id','','intval');
        $pack_id=I('get.pack_id','','intval');
        if($type>0 && $act_id>0){
        	if($type==1){
        		$param.='&group_id='.$act_id;
        	}elseif($type==2){
        		$param.='&bar_id='.$act_id;
        	}
			$href = $this->config['site_url'] . '/wap.php?c=Mall&a=detail'.$param;
        }elseif($pack_id>0){
			
			$href = $this->config['site_url'] . '/wap.php?g=Wap&c=Mall&a=pack_act&id='.$pack_id;
		}

        
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
                        if ($thisSort = D(_view('Shop_goods_sort'))->field(true)->where(array('clsNo' => $item['clsNo'], 'store_id' => $storeId))->find()) {
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

    public function get_spec_stock(){
    	$goods_id=I('goods_id','','intval');
    	$spec_id=I('spec_id','','trim');
    	$info=M('Shop_goods')->where(array('goods_id'=>$goods_id))->field('goods_id,store_id,spec_value,today_sell_spec,stock_num,sell_count,sell_day,original_stock')->find();
    	$stock_arr=$this->get_stock_num($info['store_id'],$info['sell_day'],$info['original_stock'],$info['spec_value'],$info['today_sell_spec'],$info['stock_num'],$info['sell_count'],$spec_id);
    	$data=array();
    	if($spec_id!=''){
    		$data['stock_num']=$stock_arr[$spec_id];
    	}else{
    		$data['stock_num']=$stock_arr['0'];
    	}
    	$this->success(L_('获取成功'),'',$data);
    }

    //获取规格
    public function get_stock_num($store_id,$sell_day,$original_stock,$spec_value,$today_sell_spec,$stock_num,$sell_count=0,$spec_id=''){
    	/*判断店铺库存类型*/
    	$stock_type=M('Merchant_store_shop')->where(array('store_id'=>$store_id))->getfield('stock_type');
    	$today=date('Ymd');
    	$sell_day=$stock_type?$today:$sell_day;

    	$stock_arr=array();
    	if(!empty($spec_value)){
    		$arr=explode('#', $spec_value);
    		$sale_arr=json_decode($today_sell_spec,true);
    		foreach ($arr as $k => $v) {
    			$arr2=explode('|', $v);
    			$key=str_replace(':', '_', $arr2['0']);
    			if($spec_id!='' && $key!=$spec_id){
    				continue;
    			}
    			$reg_arr=explode(':', $arr2['1']);
    			$stock_num=$reg_arr['3'];
    			if($stock_num!=-1){
    				if($sell_day==$today){
    					$t_stock_num=$stock_num-$sale_arr[$key];
    					$t_stock_num=max($t_stock_num,0);
    				}else{
    					$t_stock_num=$stock_num;
    				}
    			}else{
    				$t_stock_num=L_('无限');
    			}
    			$stock_arr[$key]=$t_stock_num;
    		}
    	}else{
    		if($stock_num!=-1){
    			if($sell_day==$today){
    				$t_stock_num=$stock_num;
    			}else{	//每天更新的，初始化使用原始库存
    				$t_stock_num=$original_stock;
    			}
    		}else{
    			$t_stock_num=L_('无限');
    		}
    		$stock_arr[]=$t_stock_num;
    	}
    	return $stock_arr;
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
		$goodArr = M('Shop_goods')->field('`goods_id`,`name`')->where(array('goods_id'=>array('in',$goodIdArr)))->select();
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
			$diypage_list = M('Merchant_store_diypage')->field('`page_id`,`page_name`')->where(array('mer_id'=>$this->mer_id,'is_remove'=>'0'))->select();
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
			$data_data['subject_pic']	= $_POST['subject_pic'];
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
			$info   = getimagesize($goodImgSrc);
			$fun    = 'imagecreatefrom'.image_type_to_extension($info[2], false);
			$src_im =  call_user_func_array($fun,array($goodImgSrc));


			//创建新图像
				$newimg = imagecreatetruecolor($_POST['good_width'],$_POST['good_height']);
				// 调整默认颜色
				$color = imagecolorallocate($newimg, 255, 255, 255);
				imagefill($newimg, 0, 0, $color);
				//裁剪
				//等比例缩小高度居中
				$goodImageTop = intval($info[1] - ($info[0]*$_POST['good_height']/$_POST['good_width']));
				// echo $goodImageTop;die;
				imagecopyresampled($newimg, $src_im, 0, 0, 0, $goodImageTop/2, $_POST['good_width'],$_POST['good_height'],$info[0],$info[1]-$goodImageTop);
				imagedestroy($src_im); //销毁原图
				imagecopy($img,$newimg,$_POST['good_left'],$_POST['good_top'],0,0,$_POST['good_width'],$_POST['good_height']);

		// header('Content-type: image/png');
		// imagepng($img);
		// die;
		$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
		$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

		$fileName = 'upload/store/'.$rand_num;
		if(!file_exists($fileName)){
			mkdir($fileName,0777,true);
		}
		$fileName.= '/'.$_POST['store_id'].'_subject.png';
		if(imagepng($img,$fileName)){
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
	}

	//视频文件上传
	public function vupload(){
        set_time_limit (0);
        //关闭缓存
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
 		
 		$filepath='./upload/video/'.date('Y').'/'.date('m');
 		if(!file_exists($filepath)){
			mkdir($filepath,0777,true);
		}
        $ip_path = $filepath;
        $save_path = trim($filepath,'./');
        import('@.ORG.Vupload');
        $uploader =  new Vupload();
        $uploader->set('path',$ip_path);


        $ffmpeg = trim(shell_exec('which ffmpeg')); // or better yet:
        $ffmpeg2 = trim(shell_exec('type -P ffmpeg'));// 解决which ffmpeg返回为空实际安装了ffmpeg

        //用于断点续传，验证指定分块是否已经存在，避免重复上传
        if(isset($_POST['status'])){
            if($_POST['status'] == 'chunkCheck'){
                $target =  $ip_path.'/'.$_POST['name'].'/'.$_POST['chunkIndex'];
                if(file_exists($target) && filesize($target) == $_POST['size']){
                    die('{"ifExist":1}');
                }
                die('{"ifExist":0}');
 
            }elseif($_POST['status'] == 'md5Check'){
 
                //todo 模拟持久层查询
                $dataArr = array(
                    'b0201e4d41b2eeefc7d3d355a44c6f5a' => 'kazaff2.jpg'
                );
 
                if(isset($dataArr[$_POST['md5']])){
                    die('{"ifExist":1, "path":"'.$dataArr[$_POST['md5']].'"}');
                }
                die('{"ifExist":0}');
            }elseif($_POST['status'] == 'chunksMerge'){
 
                if($path = $uploader->chunksMerge($_POST['name'], $_POST['chunks'], $_POST['ext'])){
                    //todo 把md5签名存入持久层，供未来的秒传验证
                    session('video_path', $save_path.'/'.$path);
					if (!empty($ffmpeg) || !empty($ffmpeg2))
					{
		                 //生成缩略图
		                $str = "ffmpeg -i " . $ip_path .'/'.$path. " -y -f mjpeg -ss 1 -t 0.01 -s 740x500 " . $ip_path.'/'.$path.'.png';
		                exec($str, $out);
		            }

	                $file_handle = new file_handle();
	                $file_handle->upload($save_path.'/'.$path);
	                $file_handle->upload($save_path.'/'.$path.'.png');
                    die('{"status":1, "path": "'.$save_path.'/'.$path.'"}');
                }
                die('{"status":0}');
            }
        }
 
        if(($path = $uploader->upload('file',$_POST))!==false){
            if(!session('video_path')){
                session('video_path', $save_path.'/'.$path);
            }


            if (!empty($ffmpeg) || !empty($ffmpeg2))
            {
                 //生成缩略图
                $str = "ffmpeg -i " . $ip_path .'/'.$path. " -y -f mjpeg -ss 1 -t 0.01 -s 740x500 " . $ip_path.'/'.$path.'.png';
                exec($str, $out);
            }

            $file_handle = new file_handle();
            $file_handle->upload($save_path.'/'.$path);
            $file_handle->upload($save_path.'/'.$path.'.png');

            die('{"status":1, "path": "'.$save_path.'/'.$path.'"}');
        }
        die('{"status":0}');
    }

    //删除上传的视频文件
    public function del_video_cache(){
    	$uniqueFileName=I('uniqueFileName','','trim');
    	if(empty($uniqueFileName)){
    		$this->error(L_('网络出错，请重试！'));
    	}
    	$ip_path = './upload/video';
    	$filepath=$ip_path.'/'.$uniqueFileName;
		if(is_dir($filepath) || !is_file($filepath.'.tmp')){
			$this->deleteDir($filepath);
			unlink($filepath.'.tmp');
		}
		$this->success(L_('删除成功'));
    }

    //删除商品视频
    public function del_video(){
    	$goods_id=I('get.goods_id','','intval');
    	$video_url=I('video_url','','trim');
    	if($goods_id>0){
    		$data=array();
    		$data['goods_id']=$goods_id;
    		$data['video_url']='';
    		$res=M('shop_goods')->data($data)->save();
    	}else{
    		$res=1;
    	}
    	if($res!==false){
    		unlink($video_url);
    		$this->success(L_('删除成功！'));
    	}else{
    		$this->error(L_('删除失败，请重试！'));
    	}
    }

    //删除文件夹
    private function deleteDir($dir){
		if (!$handle = @opendir($dir)) {
			return false;
		}
		while (false !== ($file = readdir($handle))) {
			if ($file !== "." && $file !== "..") {       //排除当前目录与父级目录
				$file = $dir . '/' . $file;
				if (is_dir($file)) {
					deleteDir($file);
				} else {
					@unlink($file);
				}             
			}
		}
		@rmdir($dir);
    }

	//打包一口价管理group_list
	public function pack_index(){
		$store_id=I('store_id','','intval');
		$now_store = $this->check_store($store_id);
		$mer_id=$_SESSION['merchant']['mer_id'];
		$where=array();

		$where['store_id']=$store_id;
		$where['is_del']=0;

		$keyword=I('keyword','','trim');
		if(!empty($keyword)){
			
			$where['pack_name']=array('like','%'.$keyword.'%');
					
		}
		//活动开始时间
		$stime1=I('sdate1','','strtotime');
		$stime2=I('sdate2','','strtotime');
		if($stime1 && $stime2){
			$where['begin_time']=array('between',array($stime1,$stime2+60*60*24-1));
		}elseif($stime1){
			$where['begin_time']=array('egt',$stime1);
		}elseif($stime2){			
			$where['begin_time']=array('elt',$stime1+60*60*24-1);
		}
		//活动结束时间
		$ftime1=I('fdate1','','strtotime');
		$ftime2=I('fdate2','','strtotime');
		if($ftime1 && $ftime2){
			$where['end_time']=array('between',array($ftime1,$ftime2+60*60*24-1));
		}elseif($ftime1){
			$where['end_time']=array('egt',$ftime1);
		}elseif($ftime2){			
			$where['end_time']=array('elt',$ftime2+60*60*24-1);
		}

		$count=M('Mall_goods_pack')->where($where)->count();

		import('@.ORG.merchant_page');
		$page= new Page($count,30);
		$list=M('Mall_goods_pack')->field(true)->where($where)->limit($page->firstRow,$page->listRows)->select();
		//dump($list);exit;
		$this->assign('now_store',$now_store);
		$this->assign('list',$list);
		$this->assign('time',time());
		$this->assign('pagebar',$page->show());
		$this->display();

	}

	//新增打包一口价
	public function add_pack(){
		$model = D('Mall_goods_pack');

		if($_GET['id']){
			$info = $model->get_one($_GET['id']);
			$this->assign('info',$info);
		}
		if(IS_POST){
			$tm = time();
			$post = $_POST;
			$goods_ids = $post['goods_str'];
			$post['goods_str']  = implode(',', $post['goods_str']);
			$post['begin_time'] = strtotime($post['begin_time']);
			$post['end_time']   = strtotime($post['end_time'] );
			$post['pack_limit'] = intval($post['pack_limit']);
			$post['pack_price'] = floatval($post['pack_price']);
			$post['pack_num']   = intval($post['pack_num']);
			if(!$post['pack_price']){
				$this->error('请填写活动价格');exit;
			}
			if(!$post['pack_num']){
				$this->error('请填写打包件数');exit;
			}

			//检查商品是否参与多个打包一口价
			$goods_mod = D('Shop_goods');
			$names = [];
			foreach ($goods_ids as $gid) {
				$where = [
					'store_id' => $post['store_id'],
					'is_del' => 0,
					'begin_time' => ['elt', $tm],
					'end_time' => ['egt', $tm]
				];
				$post['id']  && $where['id'] = ['neq', $post['id']];
				if ($model->where($where)->find()) {
					$names[] = $goods_mod->where(['goods_id' => $gid])->getField('name');
				}
			}
			if ($names) {
				$error = L_('以下商品已参与打包一口价') . '<br/>' . implode('、', $names);
				$this->error($error);
				exit;
			}


			if($post['id']){//编辑
				$id = $post['id'];
				unset($post['id']);
				$r = $model->update_one($post,$id);
				if($r){
					$this->success('更新成功',U('pack_index',array('store_id'=>$post['store_id'])));exit;
				}else{
					$this->error('更新失败',U('pack_index',array('store_id'=>$post['store_id'])));exit;
				}
			}else{//新增
				unset($post['id']);
				$r = $model->add_one($post);
				if($r){
					$this->success('添加成功',U('pack_index',array('store_id'=>$post['store_id'])));exit;
				}else{
					$this->error('添加失败',U('pack_index',array('store_id'=>$post['store_id'])));exit;
				}
			}
		}
		$this->display();
	}

	public function del_pack(){
		$id=I('id','','intval');
		$r = M('Mall_goods_pack')->where(array('id'=>$id))->data(array('is_del'=>1))->save();
		if($r){
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！');
		}
	}
	
	// 周期购管理
	public function periodic_index(){

		$store_id=I('store_id','','intval');
		$store_id = $store_id?$store_id:$_POST['store_id'];
		$where = array();
		$keyword=I('keyword','','trim');
		if ($keyword) {
			$where['gd.name'] = array('like','%'.$keyword.'%');
		}
		$where['gd.store_id'] = $store_id;
		$where['p.status'] = 0;
		$count=M('mall_periodic_purchase')->alias('p')->join(C('DB_PREFIX').'shop_goods gd on p.goods_id=gd.goods_id')->where($where)->count();

		import('@.ORG.merchant_page');
		$page= new Page($count,30);
		$list=M('mall_periodic_purchase')->alias('p')->join(C('DB_PREFIX').'shop_goods gd on p.goods_id=gd.goods_id')->field('p.*,gd.sell_day,gd.original_stock,gd.name as goods_name,gd.price,gd.stock_num,gd.sell_count')->where($where)->limit($page->firstRow,$page->listRows)->select();
		$list = $this->periodic_deal_list($list);
		
		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//删除商品周期购
	public function periodic_del(){
		$id=I('id','','intval');
		$r = M('Mall_periodic_purchase')->where(array('id'=>$id))->data(array('status'=>1))->save();
		if($r){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	public function periodic_deal_list($data){
		if($data){
			foreach ($data as $key => $val) {
				switch ($val['periodic_type']) {
					case '1':
						$data[$key]['periodic_text'] = '每日一期，共'.$val['periodic_count'].'期';
						$data[$key]['periodic_data_text'] = '每天';
						break;
					case '2':
						$data[$key]['periodic_text'] = '每周一期，共'.$val['periodic_count'].'期';
						$week = array('周日','周一','周二','周三','周四','周五','周六');
						$periodic_date = explode(',', $val['periodic_date']);
						$show_data = array();
						foreach ($periodic_date as $v) {
							$show_data[] = $week[$v];
						}
						$data[$key]['periodic_data_text'] = implode(',', $show_data);
						break;
					case '3':
						$data[$key]['periodic_text'] = '每月一期，共'.$val['periodic_count'].'期';
						$periodic_date = explode(',', $val['periodic_date']);
						$show_data = array();
						foreach ($periodic_date as $v) {
							$show_data[] = $v.'号';
						}
						$data[$key]['periodic_data_text'] = implode(',', $show_data);
						break;
					default:
						$data[$key]['periodic_text'] = '未知';
						$data[$key]['periodic_data_text'] = '未知';
						break;
				}
				$data[$key]['deliver_text'] = '需提前'.$val['pre_delivery_day'].'天，并在'.$val['pre_delivery_time'].'时之前下单';
				$data[$key]['update_time'] = date('Y-m-d h:i:s',$val['update_time']);
			}
		}
		return $data;
	}

	// 新增周期购
	public function periodic_add(){

		if(IS_POST){
			$model = D('Mall_periodic_purchase');
			$set = $model->get_goods_one($_POST['goods_id']);
			if($set){
				$this->error('该商品已设置成周期购，无需再添加！');exit;
			}
			$_POST['periodic_date'] = implode(',', $_POST['periodic_date']);
			$r = $model->edit($_POST);
			if($r){
				$this->success('添加成功',U('periodic_index',array('store_id'=>$_POST['store_id'])));exit;
			}else{
				$this->error('添加失败');exit;
			}
		}
		$this->display();
	}    

	//编辑周期购
	public function periodic_edit(){
		$id=I('id','','intval');
		$model = D('Mall_periodic_purchase');
		$info = $model->get_one($id);
		if(IS_POST){
			$model = D('Mall_periodic_purchase');
			$_POST['periodic_date'] = implode(',', $_POST['periodic_date']);
			$r = $model->edit($_POST,$id);
			if($r){
				$this->success('编辑成功',U('periodic_index',array('store_id'=>$_POST['store_id'])));exit;
			}else{
				$this->error('编辑失败');exit;
			}
		}
		$info = $model->get_one($id);
		$info['periodic_date'] = json_encode(explode(',', $info['periodic_date']));
		$this->assign('info',$info);
		$this->display('periodic_add');
	}

	public function marketingList(){
		$this->display();
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
        $count = M('Shop_goods_batch_log')->where(['store_id' => $storeId,'type'=>1])->count();
        $p = new Page($count, 15);
        if ($count > 0) {
            $logs = M('Shop_goods_batch_log')->where(['store_id' => $storeId,'type' =>1])->order('pigcms_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
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
                'mer_id' => $this->merchant_session['mer_id'],
                'type'=>1
            ];
            $logId = M('Shop_goods_batch_log')->add($log);

            $export = [
                'type' => 'export_goods',
                'param' => serialize(['store_id' => $storeId, 'log_id' => $logId, 'sell_type' => $sellType, 'type' => 'export_goods', 'rand_number' => time(), 'is_shop' => 0]),
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
                    'is_shop' => 0
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
        $sellType = 1;
        $parseData = [];
        $header = [];
        $category_list = D('Goods_category')->get_all_category_by_name();
        $mer_id=M('Merchant_store')->where(['store_id'=>$storeId])->getField('mer_id');
        //dump($data);exit;
        foreach ($data as $k => $v) {
            if ($k == 0) {
                $header = $v;
                continue;
            }
            $properties = $this->parseProperties($v['H']);
            $specs = $this->parseSpec($v['G'], $v['I'],$v['T'], $v['V']);
            $end = end($parseData);
            if (empty($v['A']) || $parseData['name'] == $v['A']) {
                if (!empty($specs['propertie'])) {
                    $len = count($parseData);
                    $parseData[$len - 1]['spec'][] = $specs;
                    $parseData[$len - 1]['numbers'][] = $v['M']??'';
                }
            } else {
            	$cat = explode('#', $v['R']);
                $template = M('Express_template')->where(['name'=>$v['O'],'mer_id'=>$mer_id])->getField('id');
                $parseData[] = [
                    'store_id' => $storeId,
                    'sell_type' => $sellType,
                    'name' => $v['A'],
                    'describe' => $v['B'],
                    'number' => $v['M'],
                    'unit' => $v['D'],
                    'sort' => explode('#', trim($v['C'])),
                    'min_num' => $v['E'],
                    'stock_num' => $v['K'],
                    'original_stock' => $v['L'],
                    'packing_charge' => $v['J'],
                    'numbers' => [$v['M'] ?? ''],
                    'status' => $v['N'] > 0 ? 1 : 0,
                    'price' => $v['F'],
                    'cost_price' => $v['U'],
                    'propertie' => $properties,
                    'spec' => $specs['propertie'] ? [$specs] : [],
                    'origin_excel_data'=>$v,
                    'freight_template' => $template?$template:'',//运费模板
                    'freight_value' => $v['P'],//其他区域运费
                    'freight_type' => intval($v['Q']),//运费计算方式
                    'cat_fid' => intval($category_list[$cat[0]]),
                    'cat_id' => intval($category_list[$cat[1]]),
                    'goods_type' => $v['S'],//商品类型
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
            'header' => json_encode($header, JSON_UNESCAPED_UNICODE),
            'type' => 1,
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
                    throw new \Exception(L_('商品单位不能为空'));
                }
                if ($data['cat_fid'] < 1) {
                    throw new \Exception(L_('商城商品分类不存在'));
                }
                //商品基础信息
                $mainSort = end($data['sort']);
                $data['sort_id'] = 0;
                if ($mainSort) {
                    $data['sort_id'] = $goodsSortMod->where(['sort_name' => $mainSort, 'store_id' => $storeId])->getField('sort_id');
                    if ($data['sort_id'] < 1) {
                        throw new \Exception(L_('商品分类不存在'));
                    }
                } else {
                    throw new \Exception(L_('商品分类不能为空'));
                }
                $data['last_time'] = time();
                $data['number'] = $data['spec'] ? '' : $data['numbers'][0];
                if ($act == 'update') {
                    $oldGoods = $goodsMod->where(['name' => $data['name'], 'sell_type' => $sellType, 'store_id' => $storeId])->find();
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
                        $specString .= '#' . implode($specValIdArr, ':') . '|' . $sval['price'] . ':' . $sval['price'] . '::'. $sval['stock'] .':'.$sval['cost_price'].'::' . '|' . implode($specPropertieString, ':') . '|'.$data['numbers'][$skey].'||';
                    } else {
                        $specString .= implode($specValIdArr, ':') . '|' . $sval['price'] . ':' . $sval['price'] . '::'. $sval['stock'] .':'.$sval['cost_price'].'::' . '|' . implode($specPropertieString, ':') . '|'.$data['numbers'][$skey].'||';
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
                //echo M()->getLastSql();exit;
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

            $parseData[] = [
                'store_id' => $storeId,
                'sell_type' => $sellType,
                'name' => $v['A'],
                'describe' => $v['B'],
                'sort' => explode('#', $v['C']),
                'unit' => $v['D'],
                'price' => $v['E'],
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
                if (!is_numeric($data['price'])) {
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
                } else {
                    throw new \Exception(L_('商品分类不能为空'));
                }
                $data['last_time'] = time();
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
        exit;
        if (IS_POST) {
            $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
            $act = isset($_POST['act']) ? trim($_POST['act']) : '';
            if ($storeId < 1) {
                $this->error(L_('请选择店铺'));
            }
            if (!in_array($act, ['add', 'update'])) {
                $this->error(L_('操作有误'));
            }
            if ($_FILES['file']['error'] != 4) {
                $uploadDir = './upload/excel/merchant/goods/' . date('Ymd') . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                import('ORG.Net.UploadFile');
                $config = [
                    'maxSize' => 10 * 1024 * 1024,
                    'allowExts' => ['xls', 'xlsx'],
                    'savePath' => $uploadDir,
                    'thumb' => false,
                    'saveRule' => 'uniqid'
                ];
                $upload = new UploadFile($config);
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    $file = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $data = format_excel2array($file, 0, ["\r\n", "\r", "\n"], '');
                    $parseData = [];

                    $category_list = D('Goods_category')->get_all_category_by_name();
                    //dump($category_list);exit;
                    foreach ($data as $k => $v) {
                        if ($k == 0) {
                            continue;
                        }
                        $properties = $this->parseProperties($v['H']);
                        $specs = $this->parseSpec($v['G'], $v['I']);
                        $end = end($parseData);
                        if (empty($v['A']) || $parseData['name'] == $v['A']) {
                            if (!empty($specs['propertie'])) {
                                $len = count($parseData);
                                $parseData[$len - 1]['spec'][] = $specs;
                            }
                        } else {
                        	$cat = explode('#', $v['R']);
                        	//dump($category_list[$cat[0]]);
                        	$template = M('Express_template')->where(['name'=>$v['O']])->getField('id');
                            $parseData[] = [
                                'store_id' => $storeId,
                                'name' => $v['A'],
                                'describe' => $v['B'],
                                'number' => $v['M'],
                                'unit' => $v['D'],
                                'sort' => explode('#', $v['C']),
                                'min_num' => $v['E'],
                                'stock_num' => $v['K'],
                                'original_stock' => $v['L'],
                                'packing_charge' => $v['J'],
                                'status' => $v['N'] > 0 ? 1 : 0,
                                'price' => $v['F'],
                                'propertie' => $properties,
                                'spec' => $specs['propertie'] ? [$specs] : [],
                                'freight_template' => $template?$template:'',//运费模板
                                'freight_value' => $v['P'],//其他区域运费
                                'freight_type' => intval($v['Q']),//运费计算方式
                                'cat_fid' => intval($category_list[$cat[0]]),
                                'cat_id' => intval($category_list[$cat[1]]),
                                'goods_type' => $v['S'],//商品类型
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
                        'file' => $file,
                        'store_id' => $storeId,
                        'mer_id' => $this->merchant_session['mer_id'],
                        'type' => 1
                    ];
                    $logId = M('Shop_goods_batch_log')->add($log);
                    foreach ($parseData as $data) {
                        try {
                            //商品基础信息
                            $mainSort = end($data['sort']);
                            $data['sort_id'] = 0;
                            if ($mainSort) {
                                $data['sort_id'] = $goodsSortMod->where(['sort_name' => $mainSort, 'store_id' => $storeId])->getField('sort_id');
                                if($data['sort_id'] < 1){
                                    throw new \Exception(L_('商品分类不存在'));
                                }
                            }else{
                                throw new \Exception(L_('商品分类不能为空'));
                            }
                            $data['last_time'] = time();
                            if ($act == 'update') {
                                $oldGoods = $goodsMod->where(['name' => $data['name']])->find();
                                DUMP(M()->getLastSql());exit;
                                if (!$oldGoods) {
                                    throw new \Exception(L_('商品不存在'));
                                }
                                $goodsMod->where(['goods_id' => $oldGoods['goods_id']])->save($data);
                                $goodsId =$oldGoods['goods_id'];
                                fdump($act,'jjcs',1);
                            } else {
                            	$oldGoods = $goodsMod->where(['name' => $data['name']])->find();
                                if ($oldGoods) {
                                    throw new \Exception(L_('商品已经存在！'));
                                }
                                $goodsId = $goodsMod->add($data);
                                fdump(M()->getLastSql(),'jjcs',1);
                            }


                            //商品属性
                            $propertieNames = [];
                            $specPropertieString = [];
                            foreach ($data['propertie'] as $pval) {
                                $val = '';
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
                                    } else {
                                        $pid = $goodsPropertiesMod->add($properties);
                                    }
                                } else {
                                    $pid = $goodsPropertiesMod->add($properties);
                                }
                                $propertieNames[] = $pval['propertie'];
                                $specPropertieString[] = $pid . '=1';
                            }

                            if ($propertieNames) {
                                $goodsPropertiesMod->where(['goods_id' => $goodsId, 'name' => ['NOT IN', $propertieNames]])->delete();
                            } else {
                                $goodsPropertiesMod->where(['goods_id' => $goodsId])->delete();
                            }

                            //商品规格
                            $specString = '';
                            $specNames = [];
                            $sids = $goodsSpecMod->where(['goods_id' => $goodsId, 'store_id' => $storeId])->getField('id', true);
                            if ($sids) {
                                $goodsSpecValMod->where(['sid' => ['IN', $sids]])->delete();
                            }
                            foreach ($data['spec'] as $sval) {
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
                                    }
                                    $specValIdArr[] = $specValId;
                                }
                                if ($specString) {
                                    $specString .= '#' . implode($specValIdArr, ':') . '|' . $sval['price'] . ':' . $sval['price'] . ':::0::'. '|'.implode($specPropertieString,':').'|||';
                                } else {
                                    $specString .= implode($specValIdArr, ':') . '|' . $sval['price'] . ':' . $sval['price'] . ':::0::' . '|'.implode($specPropertieString,':').'|||';
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
                                'data' => json_encode($data)
                            ];
                            fdump($data,'jjcs',1);
                            fdump($e,'jjcs',1);
                            M('Shop_goods_batch_fail_log')->add($failLog);
                            $fail++;
                        }
                    }
                    M('Shop_goods_batch_log')->where(['pigcms_id' => $logId])->save(['status' => 2, 'success_num' => $success, 'error_num' => $fail]);
                    $this->success(L_('成功'));
                } else {
                    $this->error($upload->getErrorMsg());
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
            $this->assign('act',$act);
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
                $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
                if ($storeId < 1) {
                    $this->error(L_('请选择店铺'));
                }
                $zipFileInfo = $_FILES['file'];
                $maxSize = 50 * 1024 * 1024;
                $allowExts = ['zip', 'rar'];
                $goodsMod = M('Shop_goods');
                if ($zipFileInfo['size'] > $maxSize) {
                    $this->error(L_('最大压缩包50M'));
                }
                $ext = pathinfo($zipFileInfo['name'], PATHINFO_EXTENSION);
                if (empty($ext) || !in_array($ext, $allowExts)) {
                    $this->error(L_('上传文件必须是.zip或.rar格式'));
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
							$encode_filename = mb_convert_encoding($f, 'utf-8', 'GBK');
                        }else if($current_encode == 'EUC-CN'){
							$encode_filename = mb_convert_encoding($f, 'utf-8', 'GB2312');
						}else{
                            $encode_filename = mb_convert_encoding($f, 'utf-8', $current_encode);
                        }
                        $fpathinfo = $this->path_info($encode_filename);
                        $good = $goodsMod->where(['name' => $fpathinfo['filename'], 'store_id' => $storeId])->find();
                        if ($good) {
                            $images = explode(';', $good['image']);
                            $thisImage = $uploadGoodsPath . createRandomStr(10) . '.' . $fpathinfo['extension'];
                            if (copy($unzipPath . $f, $thisImage)) {
                                $images[0] = substr($thisImage, 1);
                                $file_handle->upload($thisImage);
                            }
                            $images = array_filter($images);
                            $goodsMod->where(['goods_id' => $good['goods_id']])->save(['image' => implode($images, ';')]);
                            $success++;
                        } else {
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
                    'type'=>1
                ];
                $logId = M('Shop_goods_batch_log')->add($log);

    			//1.  全部导入成功后的文案： 全部图片导入成功！
    			//2.  部分导入成功部分失败的文案： 导入完成！其中部分图片导入失败。
    			//3. 全部导入失败后的文案：全部图片导入失败！ 
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
	
	//中文名称过长导致文件名都去为空，重新写了个方法避免这种情况
    function path_info($filepath)
    {
        $path_parts = array();
        $path_parts ['dirname'] = rtrim(substr($filepath, 0, strrpos($filepath, '/')),"/")."/";
        $path_parts ['basename'] = ltrim(substr($filepath, strrpos($filepath, '/')),"/");
        $path_parts ['extension'] = substr(strrchr($filepath, '.'), 1);
        $path_parts ['filename'] = ltrim(substr($path_parts ['basename'], 0, strrpos($path_parts ['basename'], '.')),"/");
        return $path_parts;
    }
	
    public function updateGoodsPic()
    {
        $logMod = M('Shop_goods_batch_log');
        $logs = $logMod->where(['action' => 4, 'status' => 0])->select();
        $goodsMod = M('Shop_goods');
        foreach ($logs as $v) {
            $merId = M('Merchant_store')->where(['store_id' => $v['store_id']])->getField('mer_id');
            if ($merId < 1) {
                continue;
            }

            $file = $v['file'];
            $imgMerId = sprintf("%09d", $merId);
            $randNum = substr($imgMerId, 0, 3) . '/' . substr($imgMerId, 3, 3) . '/' . substr($imgMerId, 6, 3);
            $uploadGoodsPath = "./upload/goods/{$randNum}/";
            $unzipPath = './runtime/unzip/' . createRandomStr(10);
            $zip = new PclZip($file);
            if ($zip->extract($unzipPath) == 0) {
                $logMod->where(['pigcms_id' => $v['pigcms_id']])->save(['status' => 3, 'error_msg' => $zip->errorInfo()]);
            } else {
                $picPath = $unzipPath . '/pics/';
                $files = scandir($picPath);
                foreach ($files as $v) {
                    if ($v == '.' || $v == '..') {
                        continue;
                    }
                    $pathInfo = pathinfo($picPath . $v);
                    if (in_array($pathInfo['extension'], ['jpg', 'png'])) {
                        $good = $goodsMod->where(['name' => $pathInfo['filename']])->find();
                        if ($good) {
                            $images = explode(';', $good['image']);
                            $thisImage = $uploadGoodsPath . createRandomStr(10) . '.' . $pathInfo['extension'];
                            if (count($images) < 5 && copy($picPath . $v, $thisImage)) {
                                $images[] = substr($thisImage, 1);
                            }
                            $goodsMod->where(['goods_id' => $good['goods_id']])->save(['image' => implode($images, ';')]);
                        }
                    }
                    unlink($picPath . $v);
                }
                rmdir($picPath);
                $logMod->where(['pigcms_id' => $v['pigcms_id']])->save(['status' => 2]);
            }
        }
    }

    /**
     * 限时优惠
     */
    public function time_limited_discount()
    {
        $lists = D('Time_limited_discount_goods')->get_list($_GET,'mall');
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
        $book_goods = isset($_GET['book_goods']) ? intval($_GET['book_goods']) : 0;
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        if($book_goods){
            //预售
            $condition = [
                'keyword' => $keyword,
                'store_id' => $store_id
            ];
            /*-----判断活动-----*/
            //$where['goods_type'] = 2;
            //$data = D('Shop_goods')->get_list_by_storeid_diypage($store_id, 2,$where);
            /*$list = $data['good_list'];
            //$type = I('get.type', '', 'trim');
            $goods_id = $_GET['goods_id'];
            $goods_id_arr = explode(',',$goods_id);*/
            $now_time = time();
            //拼团商品,获取不可选的
            $where0 = array();
            $where0['ftime'] = array('gt', $now_time);
            $where0['status'] = 1;
            $where0['is_del'] = 0;
            $where0['store_id'] = $store_id;
            $group_arr = M('Mall_group_act')->where($where0)->getfield('good_id', true);
            $group_arr = (array)$group_arr;

            //周期购商品获取不可选的
            $wherea = array();
            $wherea['status'] = 0;
            $periodic_arr = M('Mall_periodic_purchase')->where($wherea)->getfield('goods_id', true);
            $periodic_arr = (array)$periodic_arr;

            //打包一口价
            $where1 = array();
            $where1['is_del'] = 0;
            $where1['store_id'] = $store_id;
            $where1['end_time'] = array('gt', $now_time);
            $goods_pack_arr = M('Mall_goods_pack')->where($where1)->getfield('goods_str', true);
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
            $where3['stock']=array('neq',0);
            $where3['is_del'] = 0;
            $where3['store_id'] = $store_id;
            $book_arr = M('Mall_book_goods')->where($where3)->getfield('goods_id', true);
            //fdump(M('Mall_book_goods')->getLastSql(),"dengyuanhui888",1);
            $time=time();
            $where4 = array();
            $where4['stime']=array('lt',$time);
            $where4['ftime']=array('gt',$time);
            $where4['store_id'] = $store_id;
            $where4['is_del'] = 0;
            $full_act=M('Mall_full_act')->where($where4)->getfield('goods_id',true);

            /*积分活动*/
            $where5['price_type']=array('gt',1);
            $where5['store_id'] = $store_id;
            $where5['is_del'] = 0;
            $score_act=M('Shop_goods')->where($where5)->getfield('goods_id',true);
            /*-----结束判断活动-----*/

            $data = D('Time_limited_discount_goods')->select_goods1($condition,'mall');
            $goods=$data['goods'];
            if($data){
                foreach ($goods as $key=>$val){
                    $data['goods'][$key]['tip']='';
                    $data['goods'][$key]['is_clik']=1;
                    /*已经参与预售*/
                    if (in_array($val['goods_id'], $book_arr)) {
                        $data['goods'][$key]['is_clik']=0;
                        $data['goods'][$key]['tip'] = '已参与活动';
                        continue;
                    }

                    /*限时购商品*/
                    if ($val['seckill_price'] > 0) {
                        $data['goods'][$key]['is_clik']=0;
                        $data['goods'][$key]['tip'] = L_('限时购商品不可选');
                       // $data['goods'][$key] = $v;
                        continue;
                    }

                    /*拼团商品*/
                    if (in_array($val['goods_id'], $group_arr)) {
                        $data['goods'][$key]['is_clik']=0;
                        $data['goods'][$key]['tip'] = L_('拼团商品不可选');
                       // $data['goods'][$key] = $v;
                        continue;
                    }

                    /*周期购商品*/
                    if (in_array($val['goods_id'], $periodic_arr)) {
                        $data['goods'][$key]['is_clik']=0;
                        $data['goods'][$key]['tip'] = L_('周期购商品不可选');
                        //$data['goods'][$key] = $v;
                        continue;
                    }
                    if (in_array($val['goods_id'], $goods_pack_arr) && !empty($goods_pack_arr)) {
                        $data['goods'][$key]['is_clik']=0;
                        $data['goods'][$key]['tip'] = '打包一口价商品不可选';
                       /* $list[$k] = $v;*/
                        continue;
                    }
                    /*打包一口价商品*/
                  /*  foreach ($goods_pack_arr as $key1=>$val1){
                        if(strpos($val1,$val['goods_id']) !== false){
                            $data['goods'][$key]['is_clik']=0;
                            $data['goods'][$key]['tip'] = L_('打包一口价商品不可选');
                            //$data['goods'][$key] = $v;
                            continue;
                        }
                    }*/

                    /*砍价活动商品*/
                    if (in_array($val['goods_id'], $bargain_arr)) {
                        $data['goods'][$key]['is_clik']=0;
                        $data['goods'][$key]['tip'] = L_('砍价活动不可选');
                        //$data['goods'][$key] = $v;
                        continue;
                    }

                    /*已经参与积分活动*/
                    if (in_array($val['goods_id'], $score_act)) {
                        $data['goods'][$key]['is_clik']=0;
                        $data['goods'][$key]['tip'] = L_('积分活动不可选');
                        //$data['goods'][$key] = $v;
                        continue;
                    }

                    /*满减活动商品*/
                    /*已经参与积分活动*/
                    if (in_array($val['goods_id'], $full_act)) {
                        $data['goods'][$key]['is_clik']=0;
                        $data['goods'][$key]['tip'] = L_('满减活动商品不可选');
                        //$data['goods'][$key] = $v;
                        continue;
                    }
                   /* foreach ($full_act as $key2=>$val2){
                        if(strpos($val2,$val['goods_id']) !== false){
                            $data['goods'][$key]['is_clik']=0;
                            $data['goods'][$key]['tip'] = L_('打包一口价商品不可选');
                           // $data['goods'][$key] = $v;
                            continue;
                        }
                    }*/
                }
            }
            list($goods_list, $page_bar) = [$data['goods'], $data['page_bar']];
            //fdump($goods_list,"dengyuanhui223",1);
            $this->assign('goods_list', $goods_list);
            $this->assign('page_bar', $page_bar);
            $this->display();
        }else{
            $condition = [
                'keyword' => $keyword,
                'store_id' => $store_id
            ];
            $data = D('Time_limited_discount_goods')->select_goods($condition,'mall');
            foreach ($data['goods'] as $key=>$val) {
                $data['goods'][$key]['tip'] = '';
                $data['goods'][$key]['is_clik'] = 1;
            }
            list($goods_list, $page_bar) = [$data['goods'], $data['page_bar']];
            $this->assign('goods_list', $goods_list);
            $this->assign('page_bar', $page_bar);
            $this->display();
        }
    }

    public function time_limited_discount_add()
    {
        if (IS_POST) {
        	//dump($_POST);exit;
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
        	//dump($_POST);exit;
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
                $lists = D('Time_limited_discount_goods')->get_list($where,'mall');
                $info = isset($lists['lists'][0]) ? $lists['lists'][0] : [];
                //进行中的活动只能改库存
                $status = D('Time_limited_discount_goods')->get_status($id);
                $this->assign('in_progress', $status['status'] == 2 ? 1 : 0);
                $this->assign('info', $info);

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

    public function book_goods_add(){

    	if (IS_POST) {
    		try {
                D('Mall_book_goods')->add_record($_POST);
                $this->success(L_('添加成功'), U('book_index', ['store_id' => $_POST['store_id']]));
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }

    	}else{
            $this->assign("start_time", date('Y-m-d H:i:s',time()));
            $this->assign("end_time", date('Y-m-d H:i:s',time()));
            $this->assign("send_goods_date", date('Y-m-d',time()));
    		$this->display();
    	}
    }

    public function book_goods_edit(){

    	if (IS_POST) {
    		try {
                D('Mall_book_goods')->edit_record($_POST);
                $this->success(L_('编辑成功'), U('book_index', ['store_id' => $_POST['store_id']]));
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }

    	}else{
    		$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    		$where = [
                'store_id' => $store_id,
                'id' => $id
            ];
            $lists = D('Mall_book_goods')->get_list($where,'mall');
            $info = isset($lists['lists'][0]) ? $lists['lists'][0] : [];
            //进行中的活动只能改库存
            $status = D('Mall_book_goods')->get_status($id);
            //dump($info);exit;
            $this->assign('info', $info);

            $this->display();
    	}
    }

    /**
     * 预售首页
     */
    public function book_index()
    {
        $lists = D('Mall_book_goods')->get_list($_GET);
       // fdump($lists,"dengyuanhui888",1);
        $this->assign('lists', $lists['lists']);
        $this->assign('page_bar', $lists['page_bar']);
        $this->display();
    }

    public function book_del(){
    	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $detail = D('Mall_book_goods')->where(['id' => $id, 'is_del' => 0])->find();
        if (!$detail) {
            exit(json_encode(array('error' => 1, 'msg' => L_('活动不存在或已撤销'))));
        }
        if (D('Mall_book_goods')->where(['id' => $id])->setField('is_del', 1)) {
            exit(json_encode(array('error' => 0, 'msg' => L_('撤销成功'))));
        } else {
            exit(json_encode(array('error' => 1, 'msg' => L_('撤销失败'))));
        }
    }


    public function book_delete(){
    	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $detail = D('Mall_book_goods')->where(['id' => $id, 'is_del' => 1])->find();
        if (!$detail) {
            exit(json_encode(array('error' => 1, 'msg' => L_('活动不存在或状态不正确'))));
        }
        $exist = M('Shop_order')->where(['book_id'=>$id])->find();
        if($exist){
        	exit(json_encode(array('error' => 1, 'msg' => L_('抱歉：当前预售活动，已有客户下单，无法删除！'))));
        }
        if (D('Mall_book_goods')->where(['id' => $id])->delete()) {
            exit(json_encode(array('error' => 0, 'msg' => L_('删除成功'))));
        } else {
            exit(json_encode(array('error' => 1, 'msg' => L_('删除失败'))));
        }
    }

    //预售订单列表页
    public function book_order_list()
	{
		$now_store = $this->check_store($_GET['store_id']);
		$this->assign('now_store', $now_store);
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$book_status = isset($_GET['book_status']) ? intval($_GET['book_status']) : '';
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
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
		$where['book_id'] = ['GT',0];
		if($book_status){
			$where['book_status'] = $book_status;
		}
// 		if ($status != -1) {
// 		    if ($status == 2) {
// 		        $where['status'] = array('in', '2, 3');
// 		    } elseif ($status == 6) {
// 		        $where['paid'] = 0;
// 		    } else {
// 		        $where['status'] = $status;
// 		    }
// 		}

		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'real_orderid') {
				$where['real_orderid'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
				$tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
				unset($where['orderid']);
				$where['order_id'] = $tmp_result['order_id'];
			} elseif ($_GET['searchtype'] == 'name') {
				$where['username'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['userphone'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'third_id') {
				$where['third_id'] =$_GET['keyword'];
			}
		}

		$pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';

		if($status == 100){
			$where['paid'] = 0;
		}elseif($status == 12){
			$where['paid'] = 1;
			$where['status'] = array('in',array(0,1,9));
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
			$where['pay_type'] = $pay_type;
		}else if($pay_type=='balance'){
			$where['_string'] = "(`balance_pay`<>0 OR `merchant_balance` <> 0 OR `card_give_money` <> 0)";
		}

		$period = array();
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips(L_("结束时间应大于开始时间"));
			}

			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));	

			$_GET['searchTimeType'] = $_GET['searchTimeType'] ? $_GET['searchTimeType'] : 'create_time';
			$where['_string'] .=( $where['_string']?' AND ':''). " (".$_GET['searchTimeType']." BETWEEN ".$period[0].' AND '.$period[1].")";
			//$condition_where['_string']=$time_condition;
		}
		//来源是商城的订单
		$where['order_from']=1;
		if($_GET['team_id']>0){
			$where['group_id']=array('gt',0);
			$where['team_id']=$_GET['team_id'];
			$this->assign('team_id',$_GET['team_id']);
		}else{
			if(isset($where['_string'])){
				$where['_string'].=' and ';
			}else{
				$where['_string']='';
			}
			$where['_string'].='(group_id=0 or (group_id>0 and buy_type=1))';
		}
		$return = D('Shop_order')->get_order_list($where, $order_sort, 2, false);		
		$group_arr=D('Mall_group_team')->get_order_group_status();
		foreach ($return['order_list'] as $k => $v) {
			if($v['group_id']>0){
				$v['group_status']=$group_arr[$v['order_id']];
			}
			$return['order_list'][$k]=$v;
		}		
		$this->assign('status_list', D('Shop_order')->status_list);
		$this->assign($return);
		$this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status,'pay_type'=>$pay_type));

		$field = 'sum(price) AS total_price, sum(price - card_price - merchant_balance - balance_pay - payment_money - score_deducte - coupon_price) AS offline_price, sum(card_price + merchant_balance + balance_pay + payment_money + score_deducte + coupon_price) AS online_price';
		$count_where = "store_id='{$now_store['store_id']}' AND paid=1 AND is_del=0 AND status<>4 AND status<>5 AND (pay_type<>'offline' OR (pay_type='offline' AND third_id<>''))";
		if ($period) {
		    $count_where .= " AND (use_time BETWEEN " . $period[0] .' AND ' . $period[1] . ")";
		}
  		$count_where.=' and order_from=1';
		$result_total = D('Shop_order')->field($field)->where($count_where)->select();
		$result_total = isset($result_total[0]) ? $result_total[0] : '';
		$this->assign($result_total);
		$pay_method_wap = D('Config')->get_pay_method('','',1);
		$pay_method_app = D('Config')->get_pay_method('','',0,1);
		$pay_method = array_merge($pay_method_wap,$pay_method_app);
		$this->assign('pay_method',$pay_method);
        $this->display();
	}
	
	public function see_qrcode(){
		$this->display();
	}
}
