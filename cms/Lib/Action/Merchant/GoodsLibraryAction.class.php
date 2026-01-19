<?php

class GoodsLibraryAction extends BaseAction
{

    const GOODS_SORT_LEVEL = 3;

	/**
	 * 商品分类列表
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

    /**
     * 添加商品分类
     */
    public function sort_add()
    {
        $fid = isset($_REQUEST['fid']) ? intval($_REQUEST['fid']) : 0;
        $now_store = $this->check_store(intval($_GET['store_id']));

        if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find()) {
            if ($sort['level'] == self::GOODS_SORT_LEVEL) {
                $this->error($sort['sort_name'] . L_('分类下不能再增加子分类'), U('goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
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
        $this->assign('sort_list_json', json_encode($sort_list_tmp));

        if (IS_POST) {
            if (empty($_POST['sort_name'])) {
                $error_tips = L_('分类名称必填！').'<br/>';
            } else {
                $database_goods_sort = D('Shop_goods_sort');
                $data_goods_sort['store_id'] = $now_store['store_id'];
                $data_goods_sort['sort_name'] = htmlspecialchars(trim($_POST['sort_name']));
                $data_goods_sort['sort'] = intval($_POST['sort']);
                $data_goods_sort['status'] = isset($_POST['status'])?intval($_POST['status']):1;
                $data_goods_sort['print_id'] = intval($_POST['print_id']);
                $data_goods_sort['level'] = 1;

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

                //必选分类
                $data_goods_sort['is_must'] = 0;
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
                    $this->success(L_('添加成功！！'), U('goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
                    die;
                } else {
                    $this->error(L_('添加失败！！请重试。'), U('goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
                    die;
                }
            }
        }
        $this->assign('sort', $sort);
        $this->assign('now_store', $now_store);
        $this->assign('fid', $fid);
        $this->display();
    }

    /**
     * 修改商品分类
     */
    public function sort_edit()
    {
        $sortId = isset($_GET['sort_id'])?intval($_GET['sort_id']):0;
        $now_sort = $this->check_sort($sortId);
        $now_store = $this->check_store($now_sort['store_id']);
        $fid = isset($_REQUEST['fid']) ? intval($_REQUEST['fid']) : 0;
        if ($sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid))->find()) {
            if ($sort['level'] == self::GOODS_SORT_LEVEL) {
                $this->error($sort['sort_name'] . L_('分类下不能再增加子分类'), U('goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
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
            if($value['sort_id'] == $sortId){
                continue;
            }

            if ($value['operation_type']!=0) {

                $tmp = array();
                $tmp['sort_id'] = $value['sort_id'];
                $tmp['sort_name'] = $value['sort_name'];
                $tmp['operation_type'] = $value['operation_type'];
                if ($value['son_list']) {
                    $value['son_list'] = array_merge([['sort_id'=>0,'sort_name'=>'请选择','operation_type'=>1]],$value['son_list']);
                    foreach ($value['son_list'] as $son_k => $_son) {
                        if($_son['sort_id'] == $sortId){
                            unset($_son_tmp['son_list']);
                            continue;
                        }

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
        $this->assign('sort_list_json', json_encode($sort_list_tmp));

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
                isset($_POST['status']) && $data_goods_sort['status'] = intval($_POST['status']);
                $data_goods_sort['week'] = implode(',',$_POST['week']);

                $data_goods_sort['print_id'] = intval($_POST['print_id']);
                $data_goods_sort['sort_status'] = intval($_POST['sort_status']);

                // 修改父级分类
                $sort_id_1 = $_POST['sort_id_1'];
                $sort_id_2 = $_POST['sort_id_2'];
                if ($sort_id_1&&!$sort_id_2) {
                    $fid = $sort_id_1;
                }elseif($sort_id_2){
                    $fid = $sort_id_2;
                }
                $sort = M(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find();

                $data_goods_sort['fid'] = $fid;

                $data_goods_sort['level'] = 1;
                if ($sort) {
                    $data_goods_sort['level'] = $sort['level'] + 1;
                    if (M('Shop_goods')->field(true)->where(array('sort_id' => $fid, 'store_id' => $now_store['store_id']))->find()) {
                        $this->error($sort['sort_name'] . L_('分类下有归属商品了，不能给它增加子分类'), U('goods_sort_list', array('store_id' => $now_store['store_id'], 'fid' => $fid)));
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

                //必选分类
                $data_goods_sort['is_must'] = 0;
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
                    $this->success(L_('保存成功！！'), U('goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
                    die;
                } else {
                    $this->error(L_('保存失败！！您是不是没做过修改？请重试。'), U('goods_sort_list',array('store_id' => $now_store['store_id'], 'fid' => $fid)));
                    die;
                }
            }
            $_POST['sort_id'] = $now_sort['sort_id'];
            $this->assign('now_sort', $_POST);
            $this->assign('error_tips', $error_tips);
        }
        $now_sort['sort_discount'] *= 0.1;
        $this->assign('fid', $fid);
        $this->assign('now_sort', $now_sort);
        $this->assign('now_store', $now_store);

        $this->assign('sort', $sort);
        $this->display();
    }

    /**
     * 删除分类
     */
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
     * 商品管理
     */
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
		$open_from_shop = D('Merchant_store_foodshop')->where(['store_id'=>$now_sort['store_id']])->getField('open_from_shop');
		$this->assign("open_from_shop",$open_from_shop);
		$database_goods = D(_view('Shop_goods'));
		$condition_goods['sort_id'] = $now_sort['sort_id'];
		$condition_foodshop_goods['s.sort_id'] = $now_sort['sort_id'];

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
		$this->assign('merchant', $this->merchant_session);

		$this->assign('goods_list', $goods_list);
		$this->assign('pagebar', $p->show());
		$this->display();
	}

    /**
     * 商品管理列表
     */
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
		$database_goods = M('Shop_goods');
		$count_goods = $database_goods->where($condition_goods)->count();
		// import('@.ORG.merchant_page');
        // import('@.ORG.ajaxpage');
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
		foreach ($goods_list as &$rl) {
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

	/**
     * 商品搜索
     */
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
		$this->assign('sort_list_json', json_encode($sort_list_tmp));

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

        $goods_image_class = new goods_image();
		foreach ($goods_list as &$rl) {
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
		$this->assign('goods_list', $newList);
		$this->display();
	}

    /**
     * 获取商品信息
     */
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
		$update['stock_num'] = $stock_num == 'full' ? $now_goods['original_stock'] : $stock_num;
		$update['sell_day'] = date('Ymd');
		if(isset($_POST['original_stock'])){
			$update['original_stock'] = $original_stock;
		}
		if($database_goods->where('goods_id = '.$goods_id)->data($update)->save()){
            //库存修改同步到新版商城商品
            $mallGoods = M('Mall_goods')->where(array('store_id' => $now_goods['store_id'], 'stock_type' => 2,'common_goods_id' => $goods_id,'is_del' => 0))->field('goods_id')->find();
            if($mallGoods){
                M('Mall_goods')->where(array('store_id' => $now_goods['store_id'], 'stock_type' => 2,'common_goods_id' => $goods_id,'is_del' => 0))->save(['stock_num'=>$stock_num]);
                M('Mall_goods_sku')->where(['goods_id'=>$mallGoods['goods_id'],'store_id' => $now_goods['store_id']])->save(['stock_num'=>$stock_num]);
            }
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
        if( $_FILES['file']['size'] > 256 * 1024){
            exit(json_encode(array('error' => 1,'message' =>L_('上传图片大小不能超过256KB'))));
        }
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

    //添加商品
    public function goods_add()
    {
        if ($_GET['sort_id']) {
            $now_sort = $this->check_sort(intval($_GET['sort_id']));
        }
        $now_store = $this->check_store($_GET['store_id']);

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
            //处理选择图片链接存在逗号隔开的
            foreach ($_POST['pic'] as $k => $v) {
                if (false !== strpos($v, ',')) {
                    $splistArr = explode(',', $v);
                    $_POST['pic'][$k] = (strpos($v,'upload')===false ? '/upload/goods/' : '') . $splistArr[0] . '/' . $splistArr['1'];
                }
            }
            $_POST['pic'] = $_POST['pic'] ? $_POST['pic'] : $pics;
            if (empty($_POST['name'])) {
                $error_tips = L_('商品名称必填！') . '<br/>';
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
			// if ($_POST['price'] < $_POST['price_range_low'] || $_POST['price'] > $_POST['price_range_height']) {
			// 	$error_tips .= L_('商品价格必须大于或等于商品最低价，小于或等于商品最高价').'<br/>';
			// }
            if (empty($_POST['pic'])) {
                if (!C('config.is_open_goods_default_image')) {
                    $error_tips .= L_('请至少上传一张照片！') . '<br/>';
                }
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
                //查询是否开启审核功能
                $open_status = $this->config['open_goods_status'];
                if($open_status==1){
                    $_POST['status'] = 0;
                    $_POST['audit_status'] = 0;
                }
                $_POST['sort_id'] = $sort_id;
                $_POST['store_id'] = $now_store['store_id'];
                $_POST['last_time'] = $_SERVER['REQUEST_TIME'];

                if ($goods_id = D('Shop_goods')->save_post_form($_POST, $now_store['store_id'])) {
                    D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');

                    //保存商品区块和附属商品关系
                    $this->save_subsidiary_piece($now_store['store_id'], $goods_id, $_POST['name_piece'], $_POST['mininum_piece'], $_POST['maxnum_piece'], $goods);

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

                    $this->success(L_('添加成功！'), U('goods_list', array('store_id' => $_GET['store_id'],'sort_id'=>$sort_id)));
                    die;
                    $ok_tips = L_('添加成功！');
                } else {
                    $this->error(L_('添加失败！请重试！'), U('goods_list', array('store_id' => $_GET['store_id'],'sort_id'=>$sort_id)));
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

            S('shop_goods_by_storeid_' . $now_store['store_id'],null);

            $this->assign('ok_tips', $ok_tips);
            $this->assign('error_tips', $error_tips);
        } else {
        }

        $category_list = D('Goods_category')->get_list();
        $this->assign('category_list', json_encode($category_list,JSON_UNESCAPED_UNICODE));

        $sort_list = D('Shop_goods_sort')->lists($now_store['store_id'], false,true);
        $this->assign('sort_list', addslashes(json_encode($sort_list,JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE|JSON_HEX_APOS)));
        $ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
        $this->assign('select_ids', json_encode($ids));
        $this->assign('now_sort', $now_sort);
        $this->assign('now_store', $now_store);
        $this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());
        $this->display();
    }
    /* 编辑商品 */
    public function goods_edit()
    {
        $now_goods = $this->check_goods($_GET['goods_id']);
        $now_sort = $this->check_sort($now_goods['sort_id']);
        $now_store = $this->check_store($now_sort['store_id']);
        // 辣度
        $spicy_arr =  D('Shop_goods')->spicy_arr();
       	$this->assign('spicy_arr',$spicy_arr);
        if (IS_POST) {
            $_POST['sell_type'] = $now_goods['sell_type'];
            $error_tips = '';
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
			// if ($_POST['price'] < $_POST['price_range_low'] || $_POST['price'] > $_POST['price_range_height']) {
			// 	$error_tips .= L_('商品价格必须大于或等于商品最低价，小于或等于商品最高价').'<br/>';
			// }
            if (empty($_POST['pic'])) {
                if (!C('config.is_open_goods_default_image')) {
                    $error_tips .= L_('请至少上传一张照片！') . '<br/>';
                }
            }

            !isset($_POST['sell_type']) && $_POST['sell_type'] = $now_goods['sell_type'];

            if($_POST['sell_type']==2){
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
                //查询是否开启审核功能
                $open_status = $this->config['open_goods_status'];
                if($open_status==1){
                    $_POST['status'] = 0;
                    $_POST['audit_status'] = 0;
                }else{
                    $_POST['status'] = 1;
                    $_POST['audit_status'] = 1;
                }
				$_POST['goods_id'] = $now_goods['goods_id'];
				$_POST['sort_id'] = $sort_id;
				$_POST['store_id'] = $now_store['store_id'];
				$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
				if ($goods_id = D('Shop_goods')->save_post_form($_POST, $now_store['store_id'])) {
                    if (!empty($price_modify_log)) {
                        M('Shop_goods_price_modify_log')->add($price_modify_log);
                    }

					D('Image')->update_table_id($_POST['image'], $goods_id, 'goods');

					//保存商品区块和附属商品关系
                    $this->save_subsidiary_piece($now_store['store_id'], $goods_id, $_POST['name_piece'], $_POST['mininum_piece'], $_POST['maxnum_piece'], $goods);

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

					$this->success(L_('保存成功！'), U('goods_list', array('sort_id' => $now_sort['sort_id'],'store_id'=>$now_goods['store_id'],'page' => $_POST['page'])));
					die;
					$ok_tips = L_('保存成功！');
				} else {
					$this->error(L_('保存失败！请重试！'), U('goods_list', array('sort_id' => $now_sort['sort_id'],'store_id'=>$now_goods['store_id'],'page' => $_POST['page'])));
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

		$category_list = D('Goods_category')->get_list();
		$this->assign('category_list', json_encode($category_list,JSON_UNESCAPED_UNICODE));

		$sort_list = D('Shop_goods_sort')->lists($now_store['store_id'], false,true);
        $this->assign('sort_list', addslashes(json_encode($sort_list,JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE|JSON_HEX_APOS)));
		$ids = D('Shop_goods_sort')->getIds($now_sort['sort_id'], $now_store['store_id']);
		$this->assign('select_ids', json_encode($ids));
		$this->assign('now_goods', array_merge($now_goods,$_POST));
		$this->assign('now_sort', $now_sort);
        $this->assign('merchant', $this->merchant_session);
		$this->assign('now_store', $now_store);
		$this->assign('express_template', D('Express_template')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id']))->select());
		$this->display();
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
			return $now_store;
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
					$now_goods['pic_arr'][$key]['url'] = $value;
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
		$src = '<img src="'.file_domain().'/';
		$now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
		$now_goods['des'] = str_replace('<img alt="" src="/', $src, $now_goods['des']);

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

	public function ajax_del_pic() {
// 		$group_image_class = new goods_image();
// 		$group_image_class->del_image_by_path($_POST['path']);
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
     * 批量新增/传图/更新/导出 - 批量操作
     */
    public function batchOperation()
    {
        $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        if ($storeId < 1) {
            $this->error(L_('请选择店铺'));
        }
        import('@.ORG.merchant_page');
        $count = M('Shop_goods_batch_log')->where(['store_id' => $storeId, 'type' => 2])->count();
        $p = new Page($count, 15);
        if ($count > 0) {
            $logs = M('Shop_goods_batch_log')->where(['store_id' => $storeId, 'type' => 2])->order('pigcms_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $this->assign('pagebar', $p->show());
        $this->assign('logs', $logs);
        $this->assign('storeId', $storeId);
        $this->display();
    }

    /**
     * 批量新增/传图/更新/导出 - 导出商品
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
                'type' => 2,
                'mer_id' => $this->merchant_session['mer_id']
            ];
            $logId = M('Shop_goods_batch_log')->add($log);

            $export = [
                'type' => 'export_goods_library',
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
					'type' => 'export_goods_library',
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

    /**
     * 批量新增/传图/更新/导出 - 导入商品
     */
    protected function importPieceGoods()
    {
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $act = isset($_POST['act']) ? trim($_POST['act']) : '';
        if (empty($_FILES['file']['tmp_name'])) {
            $this->error(L_('没有上传文件'));
        }
        $data = format_excel2array($_FILES['file']['tmp_name']);
		if(count($data) > 100){
			$this->error(L_('一次只能上传100件商品'));
		}
		
        $sellType = 1;
        $parseData = [];
        $header = [];
        foreach ($data as $k => $v) {
            if ($k == 0) {
                $header = $v;
                continue;
            }
            $properties = $this->parseProperties($v['G']);
            $specs = $this->parseSpec($v['F'], $v['H'],$v['L'],$v['N']);
            $end = end($parseData);
            if (empty($v['A']) || $parseData['name'] == $v['A']) {
                if (!empty($specs['propertie'])) {
                    $len = count($parseData);
                    $parseData[$len - 1]['spec'][] = $specs;
                }
            } else {
                $parseData[] = [
                    'store_id' => $storeId,
                    'sell_type' => $sellType,
                    'name' => $v['A'],
                    'describe' => $v['B'],
                    'sort' => explode('#', $v['C']),
                    'unit' => $v['D'],
                    'price' => $v['E'],
                    'number' => $v['K'],
                    'stock_num' => $v['I'],
                    'original_stock' => $v['J'],
                    'cost_price' => getFormatNumber($v['M']),
                    'propertie' => $properties,
                    'spec' => $specs['propertie'] ? [$specs] : [],
                    'origin_excel_data'=>$v
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
            'type' => 2,
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
                    throw new \Exception(L_('商品现价不能为空'));
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
                        $specString .= '#' . implode($specValIdArr, ':') . '|' . $sval['price'] . ':' . $sval['price'] . '::'. $sval['stock'] .':'.$sval['cost_price'].'::' . '|' . implode($specPropertieString, ':') . '|||';
                    } else {
                        $specString .= implode($specValIdArr, ':') . '|' . $sval['price'] . ':' . $sval['price'] . '::'. $sval['stock'] .':'.$sval['cost_price'].'::' . '|' . implode($specPropertieString, ':') . '|||';
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
                    'error_msg' => $e->getMessage(),
                    'type' => 2
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
     * 批量新增/传图/更新/导出 - 导入商品
     */
    protected function importWeightGoods()
    {
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $act = isset($_POST['act']) ? trim($_POST['act']) : '';
        if (empty($_FILES['file']['tmp_name'])) {
            $this->error(L_('没有上传文件'));
        }
        $data = format_excel2array($_FILES['file']['tmp_name']);
		if(count($data) > 100){
			$this->error(L_('一次只能上传100件商品'));
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
                'stock_weight' => $v['H'],
                'original_weight' => $v['I'],
                'number' => $v['J'],
                'origin_excel_data' => $v
            ];
            //计算现价
            $totalWeight = ($weightUnit == 2) ? $weight * 1000 : $weight;
            $rowData['price'] = getFormatNumber(($rowData['unit_price'] / 500) * $totalWeight);
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
            'type' => 2,
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
                } else {
                    throw new \Exception(L_('商品分类不能为空'));
                }
                $data['last_time'] = time();
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
     * 批量新增/传图/更新/导出 - 批量新增商品
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
     * 批量新增/传图/更新/导出 - 上传图片
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
                $file = $zipFileInfo['tmp_name'];
                $unzipPath = RUNTIME_PATH.'unzip/' . createRandomStr(10) . '/';
                $zip = new PclZip($file);
                if ($zip->extract($unzipPath) == 0) {
                    fdump_sql($zip->errorInfo(true), 'zip_error');
                    $this->error($zip->errorInfo());
                }
                $fileLists = scandir($unzipPath);
                $isEmpty = true;
                foreach ($fileLists as $f) {
                    if ($f == '.' || $f == '..') {
                        continue;
                    }
                    $fext = pathinfo($f, PATHINFO_EXTENSION);
                    if (empty($fext) || !in_array(strtolower($fext), ['jpg', 'png'])) {
                        $this->error(L_('图片格式只能是格式为.jpg/.png，请检查'));
                    }
                    $isEmpty = false;
                }
                if ($isEmpty) {
                    $this->error(L_('压缩文件解压为空'));
                }

                //复制图片到指定商品上传图片目录并手动同步OSS
                $imgMerId = sprintf("%09d", $this->merchant_session['mer_id']);
                $randNum = substr($imgMerId, 0, 3) . '/' . substr($imgMerId, 3, 3) . '/' . substr($imgMerId, 6, 3);
                $uploadGoodsPath = "./upload/goods/{$randNum}/";
                if(!is_dir($uploadGoodsPath)){
                    mkdir($uploadGoodsPath,755,true);
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
                    $fpathinfo = pathinfo('goods_'.$encode_filename);
                    $fpathinfo['filename'] = ltrim($fpathinfo['filename'],'goods_');

                    $good = $goodsMod->where(['name' => $fpathinfo['filename'], 'store_id' => $storeId, 'goods_type' => 0])->find();
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
                    }else{
                        $fail++;
                    }
                    unlink($unzipPath . $f);
                }

                //清理解压文件
                rmdir($unzipPath);

                //记录操作,并保存上传文件
                $uploadDir = './upload/excel/merchant/goods/' . date('Ymd') . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                /*import('ORG.Net.UploadFile');
                $config = [
                    'maxSize' => $maxSize,
                    'allowExts' => $allowExts,
                    'savePath' => $uploadDir,
                    'thumb' => false,
                    'saveRule' => 'uniqid'
                ];
                $upload = new UploadFile($config);
                $upload->upload();
                $uploadList = $upload->getUploadFileInfo();
                $file = $uploadList['0']['savepath'] . $uploadList['0']['savename'];*/
                $log = [
                    'action' => 4,
                    'log_date' => date('Y-m-d H:i:s'),
                    'file' => '',
                    'status' => 2,
                    'success_num' => $success,
                    'error_num' => $fail,
                    'store_id' => $storeId,
                    'mer_id' => $this->merchant_session['mer_id']
                ];
                $logId = M('Shop_goods_batch_log')->add($log);
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

    /**
     * 批量新增/传图/更新/导出 - 下载失败列表
     */
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


    protected function exportExcel($config = [])
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

    //同步商品首拼
    public function syncShopGoodsFirstSpellData()
    {
        //首拼
        import('@.ORG.fc.PinYin');
        $data['goods_name_first_spell'] = '';
        $Shop_goods = M('Shop_goods');
        $goods_list = $Shop_goods->field('goods_id,name,goods_name_first_spell')->select();
        foreach($goods_list as $val){

            $first_spell = '';
            $goodsStrArr = preg_split("//u", $val['name'], -1,PREG_SPLIT_NO_EMPTY);
            foreach($goodsStrArr as $v){
                $first_spell .= Pinyin::to_first($v) ?: $v;
            }
            $Shop_goods->where(['goods_id'=>$val['goods_id']])->save(['goods_name_first_spell' => $first_spell]);
        }
        echo 'success';
    }
}
?>