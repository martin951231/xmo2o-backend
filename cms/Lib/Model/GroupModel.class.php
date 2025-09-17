<?php
class GroupModel extends Model{
	/*得到指定类型，指定数量的团购*/
	public function get_group_list($type,$limit=12,$is_wap=false,$mer_id=0,$group_ids=''){
		switch($type){
			case 'new':
				$order = '`g`.`group_id` DESC';
				break;
			case 'index_sort':
				$order = '`g`.`index_sort` DESC,`g`.`group_id` DESC';
				break;
		}
		$now_time = $_SERVER['REQUEST_TIME'];
		if ($mer_id > 0) {
            $other_conditon = ' AND m.mer_id='.$mer_id;
        } else {
            $other_conditon = '';
        }

        if ($group_ids) {
        	$other_conditon .= ' AND group_id in ('.implode(',',$group_ids).')';
        }

		$group_list = D('Group')->join('AS g INNER JOIN '.C('DB_PREFIX').'merchant m ON g.mer_id = m.mer_id ')->field('`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*')
				//->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m'))
				->where("`m`.`city_id`='".C('config.now_city')."'  AND `g`.`status`='1' AND `m`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time'".$other_conditon)->order($order)->limit($limit)->select();

		if($group_list){
			$group_image_class = new group_image();

			$open_extra_price = 0;
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}
            $database_group_category = D('Group_category');
			foreach($group_list as $key=>$value){
				$tmp_pic_arr = explode(';',$value['pic']);
				$group_list[$key]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$key]['url'] = $this->get_group_url($value['group_id'],$is_wap);
				$group_list[$key]['price'] = floatval($value['price']);
				$group_list[$key]['old_price'] = floatval($value['old_price']);
				$group_list[$key]['wx_cheap'] = floatval($value['wx_cheap']);
				$group_list[$key]['pin_num'] = floatval($value['pin_num']);
				$group_list[$key]['sale_count'] =$value['sale_count']+$value['virtual_num'];
				if($value['begin_time']+864000>time()&&($value['sale_count']+$value['virtual_num'])==0){
					$group_list[$key]['sale_txt'] = L_('新品上架');
				}elseif($value['begin_time']+864000<time()&&($value['sale_count']+$value['virtual_num'])==0){
					$group_list[$key]['sale_txt'] = '';
				}else{
					$group_list[$key]['sale_txt'] = L_('已售').floatval($value['sale_count']+$value['virtual_num']);
				}
				if($open_extra_price==1&&$value['extra_pay_price']>0){
					$group_list[$key]['extra_pay_price'] = '+'.floatval($value['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$key]['extra_pay_price']='';
				}
                // 处理  管理商品属性字段 前端显示问题
                $group_list[$key]['field_info'] = $database_group_category->get_cat_fid_show($value['cat_fid'], $value);
                $group_list[$key]['image'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
			}
			return $group_list;
		}else{
			return false;
		}
	}

	public function get_hits_log($mer_id)
	{

		import('@.ORG.merchant_page');
		$count_group = D('')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'index_group_hits'=>'i'))->where("`g`.`group_id`=`i`.`group_id` AND `g`.`status`='1' AND `g`.`mer_id`='$mer_id'")->count();
		$p = new Page($count_group,C('config.group_page_row'),C('config.group_page_val'));
		$group_list = D('')->field('`g`.`s_name`,`i`.*')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'index_group_hits'=>'i'))->where("`g`.`group_id`=`i`.`group_id` AND `g`.`status`='1' AND `g`.`mer_id`='$mer_id'")->order('`i`.`time` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		if($group_list){
// 			$group_image_class = new group_image();
			foreach($group_list as $key=>$value){
// 				$tmp_pic_arr = explode(';',$value['pic']);
// 				$group_list[$key]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$key]['url'] = $this->get_group_url($value['group_id'],false);
// 				$group_list[$key]['price'] = floatval($value['price']);
// 				$group_list[$key]['old_price'] = floatval($value['old_price']);
// 				$group_list[$key]['wx_cheap'] = floatval($value['wx_cheap']);
			}
			$return['group_list'] = $group_list;
			$return['pagebar'] = $p->show();
			return $return;
		}else{
			return false;
		}
	}
	/* 商家自定义页面选择团购 带分页、搜索*/
	public function diypage_store_group_list($store_id){
		$now_time = $_SERVER['REQUEST_TIME'];
		
		$where = "`g`.`group_id`=`gc`.`group_id` AND `gc`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`end_time`>'$now_time'";
		
		if ($_POST['keyword']){
			$where.= "`g`.`s_name` LIKE %".$_POST['keyword']."%";
		}
		$count = D('')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gc'))->where($where)->count();
		import('@.ORG.diypage');
		$Page = new Page($count,8);
		
		$group_list = D('')->field('`g`.`group_id`,`g`.`s_name` AS `group_name`,`g`.`last_time`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gc'))->where($where)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		
		return array('group_list'=>$group_list,'page_bar'=>$Page->show());
	}
	/*得到指定分店的团购 不得到商家信息*/
	public function get_single_store_group_list($store_id,$limit=6,$is_wap=false,$source=false){
		$now_time = $_SERVER['REQUEST_TIME'];
		
		if($_POST['Device-Id'] == 'wxapp'){
			$condition_where = '';
		}else{
			$condition_where = "`gc`.`city_id`='".C('config.now_city')."' AND ";
		}
		$condition_where = "`g`.`group_id`=`gc`.`group_id` AND `gc`.`store_id`='$store_id' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`end_time`>'$now_time'";

		$group_list = D('')->field('`g`.`group_id`,`g`.`cat_id`,`g`.`cat_fid`,`g`.`name` AS `group_name`,`g`.`price`,`g`.`pic`,`g`.`old_price`,`g`.`wx_cheap`,`g`.`sale_count`,`g`.`count_num`,`g`.`virtual_num`,`g`.`end_time`,`g`.`begin_time`,`g`.`pin_num`,`g`.`extra_pay_price`,`g`.`trade_type`,`g`.`discount`,`g`.`vip_discount_type`,`g`.`custom_0`,`g`.`custom_1`,`g`.`custom_2`,`g`.`custom_3`,`g`.`custom_4`,`g`.`tuan_type`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gc'))->where($condition_where)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->limit($limit)->select();

		if($group_list){
			$group_image_class = new group_image();

			$open_extra_price = 0;
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}
			foreach($group_list as $key=>$value){
			    if($source && $value['tuan_type']==0){
			        unset($group_list[$key]);
			        continue;
                }
				$tmp_pic_arr = explode(';',$value['pic']);
				$group_list[$key]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$key]['url'] = $this->get_group_url($value['group_id'],$is_wap);
				$group_list[$key]['price'] = floatval($value['price']);
				$group_list[$key]['old_price'] = floatval($value['old_price']);
				$group_list[$key]['wx_cheap'] = floatval($value['wx_cheap']);
				$group_list[$key]['is_start'] = 1;
				$group_list[$key]['trade_type'] = $value['trade_type'];
				$group_list[$key]['pin_num'] = $value['pin_num'];
				if($value['begin_time']+864000>time()&&($value['sale_count']+$value['virtual_num'])==0){
					$group_list[$key]['sale_txt'] = L_('新品上架');
				}elseif($value['begin_time']+864000<time()&&($value['sale_count']+$value['virtual_num'])==0){
					$group_list[$key]['sale_txt'] = '';
				}else{
					$group_list[$key]['sale_txt'] = L_('已售').floatval($value['sale_count']+$value['virtual_num']);
				}
				if($open_extra_price==1&&$value['extra_pay_price']>0){
					$group_list[$key]['extra_pay_price'] =floatval($value['extra_pay_price']);
				}else{
					unset($group_list[$key]['extra_pay_price']);
				}
				if ($now_time < $value['begin_time']) {
					$group_list[$key]['is_start'] = 0;
				}
				$group_list[$key]['begin_time'] = date("Y-m-d H:i:s", $value['begin_time']);
				$group_list[$key]['sale_count'] = $value['sale_count']+$value['virtual_num'];
				if($value['count_num']>0){	//限制库存
					$group_list[$key]['surplus_num']=$value['count_num']-$group_list[$key]['sale_count'];
				}
			}
            $group_list=array_values($group_list);
			return $group_list;
		}else{
			return false;
		}
	}
	/*得到指定分店的团购 且得到商家信息*/
	public function get_store_group_list($store_id,$limit=6,$is_wap=false){
		$now_time = $_SERVER['REQUEST_TIME'];
		$group_list = D('')->field('`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`gc`.*,`m`.*')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc'))->where("`g`.`group_id`=`gc`.`group_id` AND `gc`.`store_id`='$store_id' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `m`.`status`='1'")->order('`g`.`sort` DESC,`g`.`group_id` DESC')->limit($limit)->select();
		if($group_list){
			$group_image_class = new group_image();
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}

            $database_group_category = D('Group_category');
			foreach($group_list as $key=>$value){
				$tmp_pic_arr = explode(';',$value['pic']);
				$group_list[$key]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$key]['url'] = $this->get_group_url($value['group_id'],$is_wap);
				$group_list[$key]['price'] = floatval($value['price']);
				$group_list[$key]['old_price'] = floatval($value['old_price']);
				$group_list[$key]['wx_cheap'] = floatval($value['wx_cheap']);
				$group_list[$key]['pin_num'] = floatval($value['pin_num']);
				if($value['begin_time']+864000>time()&&($value['sale_count']+$value['virtual_num'])==0){
					$group_list[$key]['sale_txt'] = L_('新品上架');
				}elseif($value['begin_time']+864000<time()&&($value['sale_count']+$value['virtual_num'])==0){
					$group_list[$key]['sale_txt'] = '';
				}else{
					$group_list[$key]['sale_txt'] = L_('已售').floatval($value['sale_count']+$value['virtual_num']);
				}
				if($open_extra_price==1&&$value['extra_pay_price']>0){
					$group_list[$key]['extra_pay_price'] = '+'.floatval($value['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$key]['extra_pay_price']='';
				}
                // 处理  管理商品属性字段 前端显示问题
				$group_list[$key]['field_info'] = $database_group_category->get_cat_fid_show($value['cat_fid'], $value);
			}
			return $group_list;
		}else{
			return false;
		}
	}
	/*得到批量分类下的团购*/
	public function get_category_arr_group_list($category_list,$limit=6,$is_wap=false){
		if(is_array($category_list)){
			$group_image_class = new group_image();
			$now_time = $_SERVER['REQUEST_TIME'];
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}
			foreach($category_list as $key=>$value){
				$cat_fid = $value['cat_id'];
				$group_list = D('')->field('`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m'))->where("`m`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`cat_fid`='$cat_fid' AND `m`.`status`='1'")->order('`g`.`index_sort` DESC,`g`.`group_id` DESC')->limit($limit)->select();
				if($group_list){
					foreach($group_list as $k=>$v){
					    if($v['group_cate']=='cashing'){
                            $group_list[$k]['list_pic'] = C('config.site_url').'/static/images/group/cashing.png';
                        }else{
                            $tmp_pic_arr = explode(';',$v['pic']);
                            $group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
                        }
						$group_list[$k]['url'] = $this->get_group_url($v['group_id'],$is_wap);
						$group_list[$k]['price'] = floatval($v['price']);
						$group_list[$k]['old_price'] = floatval($v['old_price']);
						$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
						if($v['begin_time']+864000>time() && ($v['sale_count']+$v['virtual_num']) ==0){
							$group_list[$k]['sale_txt'] = L_('新品上架');
						}elseif($v['begin_time']+864000<time() && ($v['sale_count']+$v['virtual_num'])==0){
							$group_list[$k]['sale_txt'] = '';
						}else{
							$group_list[$k]['sale_txt'] = '已售<strong class="num">'.floatval($v['sale_count']+$v['virtual_num']).'</strong>';
						}
						if($open_extra_price==1&&$v['extra_pay_price']>0){
							$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
						}else{
							$group_list[$k]['extra_pay_price']='';
						}
					}
					$category_list[$key]['group_list'] = $group_list;
				}
			}
			return $category_list;
		}else{
			return false;
		}
	}
	/*得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function get_group_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$cat_url,$area_id,$circle_id,$order,$attrs,$category_cat_field){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`m`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`end_time`>'$now_time'  AND `m`.`status`='1'";// AND `g`.`begin_time`<'$now_time'
//		//分类相关
//		if(!empty($get_grouplist_catfid)){
//			$condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
//		}else if(!empty($get_grouplist_catid)){
//			$condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
//		}
		//店铺必须是上架状态
		$on_sale_store_id = D('Merchant_store')->get_single_on_sale('group');
		$condition_where .= ' AND `gc`.`store_id` in ('.implode(',',$on_sale_store_id).')';
        // 处理 关于 团购二级分类多关联查询 C('config.group_category_switch')
        if (!empty($get_grouplist_catfid)){
            //分类相关
            $condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
        } else {
            if (C('config.group_category_switch') && C('config.group_category_switch') == 1) {
                $bind_where = array();
                if(!empty($get_grouplist_catfid)){
                    $bind_where['cat_fid'] = $get_grouplist_catfid;
                }else if(!empty($get_grouplist_catid)){
                    $bind_where['cid'] = $get_grouplist_catid;
                }
                if ($bind_where) {
                    $group_info = M('Group_bind_category')->where($bind_where)->group('group_id')->getField('bind_id, group_id');
                    if ($group_info && $get_grouplist_catid) {
                        $group_info = implode(',', $group_info);
                        $condition_where .= " AND (`g`.`cat_id`='$get_grouplist_catid' OR `g`.`group_id` IN ('" . $group_info . "'))";
                    } elseif(!empty($get_grouplist_catid)){
                        $condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
                    }
                }
            } elseif(!empty($get_grouplist_catid)){
                $condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
            }
        }

		//区域或商圈
		if($circle_id){
			$condition_where .= " AND `ms`.`circle_id`='$circle_id' AND `gc`.`group_id`=`g`.`group_id` AND `ms`.`store_id`=`gc`.`store_id`";
			$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc',C('DB_PREFIX').'merchant_store'=>'ms');
			$condition_field  = '`g`.`group_id`,`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		}else if($area_id){
			$condition_where .= " AND `ms`.`area_id`='$area_id' AND `gc`.`group_id`=`g`.`group_id`  AND `ms`.`store_id`=`gc`.`store_id`";
			$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc',C('DB_PREFIX').'merchant_store'=>'ms');
			$condition_field  = '`g`.`group_id`,`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		}else{
			$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc');
			$condition_field  = '`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		}

		//自定义字段
		if(!empty($attrs)){
			$attrs_tmp_arr_old = explode(';',$attrs);
			if(!empty($attrs_tmp_arr_old)){
				foreach($attrs_tmp_arr_old as $key=>$value){
					$attrs_tmp_str = explode(':',$value);
					$attrs_arr[$attrs_tmp_str[0]] = $attrs_tmp_str[1];
				}
			}
			$cat_field_arr = unserialize($category_cat_field);
			foreach($cat_field_arr as $key=>$value){
				if(empty($value['use_field']) && isset($attrs_arr[$value['url']])){
					if($value['type'] == 0){
						$tmp_custom_value = $attrs_arr[$value['url']];
						$condition_where .= ' AND `g`.`custom_'.$key."`='$tmp_custom_value'";
					}else if($value['type'] == 1){
						$tmp_custom_value = $attrs_arr[$value['url']];
						$tmp_custom_arr = explode(',',$tmp_custom_value);
						foreach($tmp_custom_arr as $k=>$v){
							$condition_where .= " AND FIND_IN_SET('$v',`g`.`custom_".$key."`)";
						}

					}
				}
			}
		}
		//echo $condition_where;

		//排序
		switch($order){
			case 'price-asc':
				$order = '`g`.`price` ASC,`g`.`group_id` DESC';
				break;
			case 'price-desc':
				$order = '`g`.`price` DESC,`g`.`group_id` DESC';
				break;
			case 'hot':
				$order = '`g`.`sale_count` DESC,`g`.`group_id` DESC';
				break;
			case 'rating':
				$order = '`g`.`score_mean` DESC,`g`.`group_id` DESC';
				break;
			case 'time':
				$order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
				break;
			default:
				$order = '`g`.`sort` DESC,`g`.`group_id` DESC';
		}

		import('@.ORG.group_page');
		$count_group_list = D('')->table($condition_table)->where($condition_where)->group('`g`.`group_id`, `m`.`mer_id`')->field('`g`.`group_id`');
		$count_group = $count_group_list?count($count_group_list):0;
		$p = new Page($count_group,C('config.group_page_row'),C('config.group_page_val'));
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->group('`g`.`group_id`, `m`.`mer_id`')->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();

		if($group_list){
			$group_image_class = new group_image();
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}

			foreach($group_list as $k=>$v){
                if($v['group_cate']=='cashing'){
                    $group_list[$k]['list_pic'] = C('config.site_url').'/static/images/group/cashing.png';
                }else{
                    $tmp_pic_arr = explode(';',$v['pic']);
                    $group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
                }
				$group_list[$k]['url'] = C('config.site_url').'/group/'.$v['group_id'].'.html';
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				$group_list[$k]['is_start'] = 1;

				if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = L_('新品上架');
				}elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = '';
				}else{
					$group_list[$k]['sale_txt'] = '已售<strong class="num">'.floatval($v['sale_count']+$v['virtual_num']).'</strong>';
				}

				if($open_extra_price==1&&$v['extra_pay_price']>0){
					$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$k]['extra_pay_price']='';
				}
				if ($now_time < $v['begin_time']) {
					$group_list[$k]['is_start'] = 0;
				}
				$group_list[$k]['begin_time'] = date("Y-m-d H:i:s", $v['begin_time']);
			}
		}
		$return['group_list'] = $group_list;
		return $return;
	}

	/*得到团购搜索列表，带有分页功能*/
	public function get_group_list_by_keywords($keywords,$order,$is_wap=false,$cat_id ='',$lat=0,$long=0,$is_search=0){
		//商家入驻缴费开关
		$merchant_settle_in = C('customization.merchant_settle_in');
		$mer_field = '';
		if($merchant_settle_in == 1){
			$mer_field = '`m`.settle_in_money, `m`.settle_in_sign,';
		}
		$now_time = $_SERVER['REQUEST_TIME'];
		if($lat){
			$condition_where = "(`m`.`city_id`='".C('config.now_city')."' or `m`.`area_id`='".C('config.now_city')."') AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `m`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `gs`.`group_id` = `g`.`group_id` AND `gs`.`store_id` =`s`.`store_id` AND `s`.`group_on_sale`=1";
			$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
			$condition_field  = "`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,".$mer_field."`g`.*, ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
		}else{
			$condition_where = "(`m`.`city_id`='".C('config.now_city')."' or `m`.`area_id`='".C('config.now_city')."') AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `m`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `gs`.`group_id` = `g`.`group_id` AND `gs`.`store_id` =`s`.`store_id` AND `s`.`group_on_sale`=1";
			$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
			$condition_field  = "`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,".$mer_field."`g`.*";

		}
		if($cat_id){
			if(is_array($cat_id)){
				$cat_id = implode(',',$cat_id);
				$condition_where.=" AND `g`.`cat_fid` in ({$cat_id}) ";
			}else{
				$condition_where.=" AND `g`.`cat_fid`={$cat_id} ";
			}
		}
		if($is_search){
            $condition_where.=" AND `g`.`group_cate` != 'cashing' "; 
        }
		//排序
		switch($order){
			case 'price-asc':
			case 'price':
				$order = '`g`.`price` ASC,`g`.`group_id` DESC';
				break;
			case 'price-desc':
				$order = '`g`.`price` DESC,`g`.`group_id` DESC';
				break;
			case 'hot':
				$order = '`g`.`sale_count` DESC,`g`.`group_id` DESC';
				break;
			case 'rating':
				$order = '`g`.`score_mean` DESC,`g`.`group_id` DESC';
				break;
			case 'time':
				$order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
				break;
			default:
				$order = '`g`.`sort` DESC,`g`.`group_id` DESC';
		}

		if(empty($is_wap)){
			import('@.ORG.group_page');
		}else{
			import('@.ORG.wap_group_search_page');
		}

		$condition_where = ['_string' => $condition_where];
		if ($keywords) {
			$condition_where['g.name|m.name|g.s_name'] = ['like', '%' . $keywords . '%'];
		}

		$count_group_list = D('')->table($condition_table)->where($condition_where)->group('`g`.`group_id`, `m`.`mer_id`')->field('`g`.`group_id`')->select();
		$count_group = $count_group_list ? count($count_group_list) : 0;
		$p = new Page($count_group,C('config.group_page_row'),C('config.group_page_val'));
		
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->group('`g`.`group_id`, `m`.`mer_id`')->order($order)->limit($p->firstRow,$p->listRows)->select();
		$return['totalPage'] = $p->totalPage;
		$return['pagebar'] = $p->show();
		$return['group_count'] = $count_group;
		if($group_list){
			$group_image_class = new group_image();
			$now_time = $_SERVER['REQUEST_TIME'];
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}
            $database_group_category = D('Group_category');
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['merchant_name'] = str_replace($keywords,'<em>'.$keywords.'</em>',$v['merchant_name']);
				$group_list[$k]['group_name'] = str_replace($keywords,'<em>'.$keywords.'</em>',$v['group_name']);
				$group_list[$k]['list_pic'] = replace_file_domain($group_image_class->get_image_by_path($tmp_pic_arr[0],'s'));
				$group_list[$k]['url'] = $this->search_get_group_url($v['group_id'],$is_wap,$keywords);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				$group_list[$k]['is_start'] = 1;

				if($merchant_settle_in == 1){
					//商家入驻缴费标志
					$group_list[$k]['settle_in_sign'] = $v['settle_in_money'] > 0 ? $v['settle_in_sign'] : '';
					unset($group_list[$k]['settle_in_money']);
				}

				$v['juli']&&$group_list[$k]['juli_txt'] = $this->wapFriendRange($v['juli']);
				if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = L_('新品上架');
				}elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = '';
				}else{
					$group_list[$k]['sale_txt'] = L_('已售').floatval($v['sale_count']+$v['virtual_num']);
				}
				if($open_extra_price==1&&$v['extra_pay_price']>0){
					$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$k]['extra_pay_price']='';
				}
				if ($now_time < $v['begin_time']) {
					$group_list[$k]['is_start'] = 0;
				}
                // 处理  管理商品属性字段 前端显示问题
                $group_list[$k]['field_info'] = $database_group_category->get_cat_fid_show($v['cat_fid'], $v);
                $group_list[$k]['detail_url'] = $this->group_page_num($_GET['page_num'], $v['group_id'], 'group_detail');
                //消费一元获得积分
                $buy_can_get_score = D('Goods_score')->getGoodsScore($v['price'],'group',false,$v['score_percent']);
                $group_list[$k]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                $group_list[$k]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
			}
		}

		$return['group_list'] = $group_list;

		return $return;
	}

	/*得到团购搜索列表，带有分页功能*/
	public function get_group_list_by_group_ids($groupids, $order, $is_wap=false){
		$groupids = "'" . implode("','", $groupids) . "'";
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`m`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `m`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`group_id` IN ($groupids)";
		$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m');
		$condition_field  = '`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';

		//排序
		switch($order){
			case 'price-asc':
				$order = '`g`.`price` ASC,`g`.`group_id` DESC';
				break;
			case 'price-desc':
				$order = '`g`.`price` DESC,`g`.`group_id` DESC';
				break;
			case 'hot':
				$order = '`g`.`sale_count` DESC,`g`.`group_id` DESC';
				break;
			case 'rating':
				$order = '`g`.`score_mean` DESC,`g`.`group_id` DESC';
				break;
			case 'time':
				$order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
				break;
			default:
				$order = '`g`.`sort` DESC,`g`.`group_id` DESC';
		}

		if(empty($is_wap)){
			import('@.ORG.group_search_page');
		}else{
			import('@.ORG.wap_group_search_page');
		}

		$count_group = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_group,C('config.group_page_row'),C('config.group_page_val'));
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();
		$return['group_count'] = $count_group;
		if($group_list){
			$group_image_class = new group_image();
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['merchant_name'] = str_replace($keywords,'<em>'.$keywords.'</em>',$v['merchant_name']);
				$group_list[$k]['group_name'] = str_replace($keywords,'<em>'.$keywords.'</em>',$v['group_name']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->search_get_group_url($v['group_id'],$is_wap,$keywords);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
			}
		}
		$return['group_list'] = $group_list;

		return $return;
	}

	/*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function wap_get_group_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0,$round = 0, $attrs = '', $category_cat_field = ''){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`gs`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1' AND `m`.`status`='1' AND `g`.`end_time`>'$now_time' AND `s`.`have_group`=1 AND `s`.`group_on_sale`=1";// AND `g`.`begin_time`<'$now_time'
//		//分类相关
//		if(!empty($get_grouplist_catfid)){
//			$condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
//		}else if(!empty($get_grouplist_catid)){
//			$condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
//		}

        // 处理 关于 团购二级分类多关联查询 C('config.group_category_switch')
        if (!empty($get_grouplist_catfid)){
            //分类相关
            $condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
        } else {
            if (C('config.group_category_switch') && C('config.group_category_switch') == 1) {
                $bind_where = array();
                if(!empty($get_grouplist_catfid)){
                    $bind_where['cat_fid'] = $get_grouplist_catfid;
                }else if(!empty($get_grouplist_catid)){
                    $bind_where['cid'] = $get_grouplist_catid;
                }
                if ($bind_where) {
                    $group_info = M('Group_bind_category')->where($bind_where)->group('group_id')->getField('bind_id, group_id');
                    if ($group_info && $get_grouplist_catid) {
                        $group_info = implode(',', $group_info);
                        $condition_where .= " AND (`g`.`cat_id`='$get_grouplist_catid' OR `g`.`group_id` IN ('" . $group_info . "'))";
                    } elseif(!empty($get_grouplist_catid)){
                        $condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
                    }
                }
            } elseif(!empty($get_grouplist_catid)){
                $condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
            }
        }

		//区域
		if($area_id || $circle_id){
			$condition_field  = 'DISTINCT `g`.`group_id`,`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*';
			if ($circle_id) {
				$condition_where .= " AND (`gc`.`circle_id`='$circle_id' OR `g`.`circle_id`='$circle_id') AND `gc`.`group_id`=`g`.`group_id`";
			} else {
				$condition_where .= " AND (`gc`.`area_id`='$area_id' OR `g`.`area_id`='$area_id') AND `gc`.`group_id`=`g`.`group_id`";
			}
			if ($order == 'juli') {
				$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
				$condition_where .= " AND `m`.`mer_id`=`s`.`mer_id`";

                $group_index_store_is_show = C('config.group_index_store_is_show');
                if ($group_index_store_is_show == 1) {
                    $condition_field .= ", CASE  WHEN (`g`.`long` = 0 AND `g`.`lat` = 0) then ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000) else ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) end as juli";
                } else {
                    $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                }
			} else {
				$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
			}


		}else{
			$condition_field  = '`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*';
			if ($order == 'juli') {
				$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
				$condition_where .= " 	AND `g`.`group_id` = `gs`.`group_id` AND `gs`.`store_id`=`s`.`store_id` AND `m`.`mer_id`=`s`.`mer_id`  ";


                $group_index_store_is_show = C('config.group_index_store_is_show');
                if ($group_index_store_is_show == 1) {
                    if($round){
                        $condition_where .= " AND (((`g`.`long` = 0 OR `g`.`lat` = 0) AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000)<{$round}) OR ((`g`.`long` <> 0 AND `g`.`lat` <> 0) AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000)<{$round}))";
                    }
                    $condition_field .= ", CASE  WHEN (`g`.`long` <> 0 AND `g`.`lat` <> 0) then ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000) else ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) end as juli";
                } else {
                    if($round){
                        $condition_where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000)<{$round} ";
                    }
                    $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                }
			} else {
				$condition_where .= " 	AND `g`.`group_id` = `gs`.`group_id` AND `gs`.`store_id`=`s`.`store_id`AND `m`.`mer_id`=`s`.`mer_id`  ";
				$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
			}

		}

        //自定义字段
        if(!empty($attrs) && !empty($category_cat_field)){
            $attrs_tmp_arr_old = explode(';',$attrs);
            if(!empty($attrs_tmp_arr_old)){
                foreach($attrs_tmp_arr_old as $key=>$value){
                    $attrs_tmp_str = explode(':',$value);
                    $attrs_arr[$attrs_tmp_str[0]] = $attrs_tmp_str[1];
                }
            }
            $cat_field_arr = unserialize($category_cat_field);
            $where = false;
            $tmp_where = '';
            foreach($cat_field_arr as $key=>$value){
                if(empty($value['use_field']) && isset($attrs_arr[$value['url']])){
                    $tmp_custom_value = $attrs_arr[$value['url']];
                    $tmp_custom_arr = explode(',',$tmp_custom_value);
                    foreach($tmp_custom_arr as $k=>$v){
                        if ($v === '') continue;
                        if ($where) {
                            $tmp_where .= " OR FIND_IN_SET('$v',`g`.`custom_".$key."`)";
                        } else {
                            $where = true;
                            $tmp_where .= "FIND_IN_SET('$v',`g`.`custom_".$key."`)";
                        }
                    }
                    if ($tmp_where) {
                        $condition_where .= ' AND (' . $tmp_where . ')';
                    }
                }
            }
        }

		//排序
		switch($order){
			case 'price':
				$order = '`g`.`price` ASC,`g`.`group_id` DESC';
				break;
			case 'priceDesc':
				$order = '`g`.`price` DESC,`g`.`group_id` DESC';
				break;
			case 'solds':
				$order = '(`g`.`sale_count` +`g`.`virtual_num`) DESC,`g`.`group_id` DESC';
				break;
			case 'rating':
				$order = '`g`.`score_mean` DESC,`g`.`group_id` DESC';
				break;
			case 'start':
				$order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
				break;
			case 'juli':
				$order = 'juli asc,`g`.`group_id` DESC';
				break;
// 			default:
// 				$order = 'juli asc,`g`.`group_id` DESC';
			default:
				$order = '`g`.`sort` DESC,`g`.`group_id` DESC';
		}

		import('@.ORG.wap_group_page');
		//$count_group = D('')->table($condition_table)->where($condition_where)->group('`g`.`group_id`, `m`.`mer_id`')->count();
		$subQuery =D('')->field('g.group_id')->table($condition_table)->where($condition_where)->group('`g`.`group_id`, `m`.`mer_id`')->buildSql();
		$count_group =  M()->table($subQuery.' a')->count();
		if(GROUP_NAME!='Appapi' && $count_group>C('config.group_list_default_num')){
			$count_group = C('config.group_list_default_num');
		}
		if(strpos($_SERVER['HTTP_REFERER'],'main_page') &&C('config.group_list_default_num')< C('config.group_page_row')){
			C('config.group_page_row',C('config.group_list_default_num'));
		}
		$p = new Page($count_group,C('config.group_page_row'),C('config.group_page_val'));
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->group('`g`.`group_id`, `m`.`mer_id`')->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();
		if($group_list){
			$group_image_class = new group_image();
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}
			foreach($group_list as $k=>$v){
			    //新加的start
                $group_list[$k]['area_name'] = '';
                if($v['area_id']){
                    $group_list[$k]['area_name'] = D("Area")->name($v['area_id']);
                }

                // $store_list[$k]['url'] = U('Group/shop',array('store_id'=>$v['store_id']));
                $group_list[$k]['url'] = C('config.site_url') . D('House_village')->base_url. 'pages/store/homePage?store_id='.$v['store_id'];
                $group_list[$k]['store_name']=$v['name'];
                $store_score = D('Merchant_score')->field('`score_all`,`reply_count`')->where(array('parent_id'=>$v['store_id'],'type'=>'2'))->find();
                $group_list[$k]['score'] = number_format($store_score['score_all']/$store_score['reply_count'],1);
                if($v['discount_txt']&&C('config.pay_in_store')) {
                    $tmp = unserialize($v['discount_txt']);
                    if ($tmp['discount_type'] == 1) {
                        $group_list[$k]['discount_str'] = $tmp['discount_percent'] . L_("折优惠");
                    } else if ($tmp['discount_type'] == 2) {
                        $group_list[$k]['discount_str'] = L_("满X1减X1",array("X1" => $tmp['minus_price'])) ;
                    }
                }else{
                    $group_list[$k]['discount_str'] = '';
                }
                $group_list[$k]['score'] = $v['score'] ? $v['score'] : '5';
                //新加的end

				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_group_url($v['group_id'],true);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				$group_list[$k]['is_start'] = 1;
				$group_list[$k]['trade_type'] = $v['trade_type'];
				$group_list[$k]['pin_num'] = $v['pin_num'];
				$group_list[$k]['juli_txt'] = $this->wapFriendRange($v['juli']);
				if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = L_('新品上架');
				}elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = '';
				}else{
					$group_list[$k]['sale_txt'] = L_('已售').floatval($v['sale_count']+$v['virtual_num']);
				}
				if($open_extra_price==1&&$v['extra_pay_price']>0){
					$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$k]['extra_pay_price']='';
				}
				if ($now_time < $v['begin_time']) {
					$group_list[$k]['is_start'] = 0;
				}
				$group_list[$k]['begin_time'] = date("Y-m-d H:i:s", $v['begin_time']);
				$group_list[$k]['sale_count'] = $group_list[$k]['sale_count']+$group_list[$k]['virtual_num'];
			}
		}

        if (!$group_list) $group_list = array();
        $return['store_list'] = empty($group_list)?array():$group_list;
		$return['group_list'] = $group_list;
		$return['group_count'] = $count_group;
		$return['totalPage'] = ceil($count_group/C('config.group_page_row'));
		return $return;
	}

	public function wap_get_hotel($keyword='',$get_grouplist_catid,$get_grouplist_catfid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0,$round = 0)
	{
		$now_time = $_SERVER['REQUEST_TIME'];

		$condition_where = "`m`.`city_id`='" . C('config.now_city') . "' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1' AND `m`.`status`='1' AND `g`.`end_time`>'$now_time' AND `s`.`have_group`=1 ";// AND `g`.`begin_time`<'$now_time'
		if($keyword){
			$condition_where .= " AND (`g`.`name` like '%{$keyword}%'OR `g`.`s_name` like '%{$keyword}%' OR `m`.`name` like '%{$keyword}%') ";
		}

		//分类相关
		if (is_array($get_grouplist_catfid)){
			$hotel_fid = implode(',',$get_grouplist_catfid);
			$condition_where.=" AND `g`.`cat_fid` in ({$hotel_fid}) ";
		}else if(!empty($get_grouplist_catfid)){
			$condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
		}else if(!empty($get_grouplist_catid)){
			$condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
		}else{
			$hotel_category = M('Group_category')->field('cat_id')->where(array('cat_url'=>'jiudian'))->find();

			$condition_where .= " AND `g`.`cat_id`='{$hotel_category['cat_id']}'";
		}

		//区域
		if($area_id || $circle_id){
			$condition_field  = 'DISTINCT `g`.`group_id`,`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*,`gc`.`store_id`,`gc`.`circle_id`';
			if(is_array($circle_id)){
				$circle_ids = implode(',',$circle_id);
				$condition_where .= " AND `gc`.`circle_id` in({$circle_ids}) AND `gc`.`group_id`=`g`.`group_id`";
			}else if ($circle_id) {
				$condition_where .= " AND `gc`.`circle_id`='$circle_id' AND `gc`.`group_id`=`g`.`group_id`";
			} else {
				$condition_where .= " AND( `gc`.`area_id`='$area_id' OR  `gc`.`city_id`='$area_id')AND `gc`.`group_id`=`g`.`group_id`";
			}
//			if ($order == 'juli') {
				$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
				$condition_where .= " AND `m`.`mer_id`=`s`.`mer_id`";

			if($lat && $long) {
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
			}
//			} else {
//				$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
//			}


		}else{

			$condition_field  = '`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*,`s`.*';
//			if ($order == 'juli') {
				$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
				$condition_where .= " 	AND `g`.`group_id` = `gs`.`group_id` AND `gs`.`store_id`=`s`.`store_id` AND `m`.`mer_id`=`s`.`mer_id`  ";
				if($round && $lat && $long){
					$condition_where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000)<{$round} ";
				}
			if($lat && $long) {
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
			}
//			} else {
//				$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'merchant_store'=>'s');
//			}

		}

		//排序
		switch($order){
			case 'price':
				$order = '`g`.`price` ASC,`g`.`group_id` DESC';
				break;
			case 'priceDesc':
				$order = '`g`.`price` DESC,`g`.`group_id` DESC';
				break;
			case 'solds':
				$order = '(`g`.`sale_count` +`g`.`virtual_num`) DESC,`g`.`group_id` DESC';
				break;
			case 'rating':
				$order = '`g`.`score_mean` DESC,`g`.`group_id` DESC';
				break;
			case 'start':
				$order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
				break;
			case 'juli':
				$order = 'juli asc,`g`.`group_id` DESC';
				break;
// 			default:
// 				$order = 'juli asc,`g`.`group_id` DESC';
			default:
				$order = '`g`.`sort` DESC,`g`.`group_id` DESC';
		}

		import('@.ORG.wap_group_page');
		$count_group = D('')->table($condition_table)->where($condition_where)->count("DISTINCT `g`.`group_id`");

		$p = new Page($count_group,C('config.group_page_row'),C('config.group_page_val'));
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->group('`g`.`group_id`, `m`.`mer_id`')->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();

		if($group_list){
			$group_image_class = new group_image();
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_group_url($v['group_id'],true);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				$group_list[$k]['is_start'] = 1;
				$group_list[$k]['pin_num'] = $v['pin_num'];
				if (isset($v['juli']) && floatval($v['juli'])>0) {
					$group_list[$k]['juli_txt'] = $this->wapFriendRange($v['juli']);
				} else {
					$group_list[$k]['juli_txt'] = '';
				}
				$circle_name = M('Area')->where(array('area_id'=>$v['circle_id']))->find();
				$group_list[$k]['circle_name'] = $circle_name['area_name']?$circle_name['area_name']:'';
				if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = L_('新品上架');
				}elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = '';
				}else{
					$group_list[$k]['sale_txt'] = L_('已售').floatval($v['sale_count']+$v['virtual_num']);
				}
				if($open_extra_price==1&&$v['extra_pay_price']>0){
					$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$k]['extra_pay_price']='';
				}
				if ($now_time < $v['begin_time']) {
					$group_list[$k]['is_start'] = 0;
				}
				$group_list[$k]['begin_time'] = date("Y-m-d H:i:s", $v['begin_time']);
				$group_list[$k]['sale_count'] = $group_list[$k]['sale_count']+$group_list[$k]['virtual_num'];
			}
		}

		$return['group_list'] = $group_list;
		$return['group_count'] = $count_group;
		$return['totalPage'] = ceil($count_group/C('config.group_page_row'));
		return $return;
	}

	/*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function wap_get_storeList_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0,$store_name=false, $attrs = '', $category_cat_field = ''){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`gc`.`city_id`='".C('config.now_city')."' AND `g`.`status`='1' AND `g`.`type`='1' AND `s`.`status`='1' AND `g`.`end_time`>'$now_time' AND `gc`.`group_id`=`g`.`group_id` AND `gc`.`store_id`=`s`.`store_id` AND `m`.`status`='1' AND `m`.`mer_id`=`s`.`mer_id` AND `s`.`have_group`=1 AND `s`.`group_on_sale`=1";// AND `g`.`begin_time`<'$now_time'

//        //分类相关
//        if(!empty($get_grouplist_catfid)){
//            $condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
//        }else if(!empty($get_grouplist_catid)){
//            $condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
//        }

		// 处理 关于 团购二级分类多关联查询 C('con ig.group_category_switch')
        if (!empty($get_grouplist_catfid)){
            //分类相关
            $condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
        } else {
            if (C('config.group_category_switch') && C('config.group_category_switch') == 1) {
                $bind_where = array();
                if(!empty($get_grouplist_catfid)){
                    $bind_where['cat_fid'] = $get_grouplist_catfid;
                }else if(!empty($get_grouplist_catid)){
                    $bind_where['cid'] = $get_grouplist_catid;
                }
                if ($bind_where) {
                    $group_info = M('Group_bind_category')->where($bind_where)->group('group_id')->getField('bind_id, group_id');
                    if ($group_info && $get_grouplist_catid) {
                        $group_info = implode(',', $group_info);
                        $condition_where .= " AND (`g`.`cat_id`='$get_grouplist_catid' OR `g`.`group_id` IN ('" . $group_info . "'))";
                    } elseif(!empty($get_grouplist_catid)){
                        $condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
                    }
                }
            } elseif(!empty($get_grouplist_catid)){
                $condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
            }
        }

		if($store_name){
			$condition_where.=" AND `s`.`name` like '%".addslashes($store_name)."%'";
		}
		$condition_field  = '`s`.`store_id`,`s`.`name` AS `store_name`,`s`.`pic_info`,`s`.`mer_id`,`s`.`have_meal`,`s`.`have_shop`,`s`.`have_group`,`s`.`discount_txt`,`s`.`area_id`';
		$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s', C('DB_PREFIX').'merchant'=>'m');
		// 区域
		if($area_id || $circle_id){
			if ($circle_id) {
				$condition_where .= " AND (`gc`.`circle_id`='$circle_id' OR `g`.`circle_id`='$circle_id') AND `gc`.`group_id`=`g`.`group_id`";
			} else {
				$condition_where .= " AND (`gc`.`area_id`='$area_id' OR `g`.`area_id`='$area_id') AND `gc`.`group_id`=`g`.`group_id` AND `s`.`area_id`='$area_id'";
			}
			if ($order == 'juli') {
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
			}
		}else{
			if ($order == 'juli') {
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
			}

		}

        //自定义字段
        if(!empty($attrs) && !empty($category_cat_field)){
            $attrs_tmp_arr_old = explode(';',$attrs);
            if(!empty($attrs_tmp_arr_old)){
                foreach($attrs_tmp_arr_old as $key=>$value){
                    $attrs_tmp_str = explode(':',$value);
                    $attrs_arr[$attrs_tmp_str[0]] = $attrs_tmp_str[1];
                }
            }
            $cat_field_arr = unserialize($category_cat_field);
            $where = false;
            $tmp_where = '';
            foreach($cat_field_arr as $key=>$value){
                if(empty($value['use_field']) && isset($attrs_arr[$value['url']])){
                    $tmp_custom_value = $attrs_arr[$value['url']];
                    $tmp_custom_arr = explode(',',$tmp_custom_value);
                    foreach($tmp_custom_arr as $k=>$v){
                        if ($v === '') continue;
                        if ($where) {
                            $tmp_where .= " OR FIND_IN_SET('$v',`g`.`custom_".$key."`)";
                        } else {
                            $where = true;
                            $tmp_where .= "FIND_IN_SET('$v',`g`.`custom_".$key."`)";
                        }
                    }
                    if ($tmp_where) {
                        $condition_where .= ' AND (' . $tmp_where . ')';
                    }
                }
            }
//            if ($tmp_where) {
//                $condition_where .= ' AND (' . $tmp_where . ')';
//            }
        }

		//排序
		switch($order){
			case 'price':
				$order = '`g`.`price` ASC,`g`.`group_id` DESC';
				break;
			case 'priceDesc':
				$order = '`g`.`price` DESC,`g`.`group_id` DESC';
				break;
			case 'solds':
				$order = '`g`.`sale_count` DESC,`g`.`group_id` DESC';
				break;
			case 'rating':
				$order = '`g`.`score_mean` DESC,`g`.`group_id` DESC';
				break;
			case 'start':
				$order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
				break;
			case 'juli':
				$order = '`juli` asc,`g`.`group_id` DESC';
				break;
			default:
				$order = '`g`.`sort` DESC,`g`.`group_id` DESC';
		}

		import('@.ORG.wap_group_page');
		$count_group = D('')->table($condition_table)->where($condition_where)->count('DISTINCT `s`.`store_id`');
//		if($count_group>C('config.group_list_default_num')){
//			$count_group = C('config.group_list_default_num');
//		}
		if($count_group>C('config.group_list_default_num')){
			$count_group = C('config.group_list_default_num');
		}
		if(strpos($_SERVER['HTTP_REFERER'],'main_page') &&C('config.group_list_default_num')< C('config.group_page_row')){
			C('config.group_page_row',C('config.group_list_default_num'));
		}

		$p = new Page($count_group,C('config.group_page_row'),'page');
		$store_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->group('`s`.`store_id`')->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		$return['totalPage'] = ceil($count_group/C('config.group_page_row'));
	// echo D('')->_sql();
		if($store_list){
			foreach($store_list as $k=>$v){
				$store_list[$k]['area_name'] = '';
				if($v['area_id']){
					$store_list[$k]['area_name'] = D("Area")->name($v['area_id']);
				}

				// $store_list[$k]['url'] = U('Group/shop',array('store_id'=>$v['store_id']));
                $store_list[$k]['url'] = C('config.site_url') . D('House_village')->base_url. 'pages/store/homePage?store_id='.$v['store_id'];

				$store_score = D('Merchant_score')->field('`score_all`,`reply_count`')->where(array('parent_id'=>$v['store_id'],'type'=>'2'))->find();
				$store_list[$k]['score'] = number_format($store_score['score_all']/$store_score['reply_count'],1);
				if($v['discount_txt']&&C('config.pay_in_store')) {
					$tmp = unserialize($v['discount_txt']);
					if ($tmp['discount_type'] == 1) {
						$store_list[$k]['discount_str'] = $tmp['discount_percent'] . L_("折优惠");
					} else if ($tmp['discount_type'] == 2) {
						$store_list[$k]['discount_str'] = L_("满X1减X2",array("X1" => getFormatNumber($tmp['condition_price']),"X2" => getFormatNumber($tmp['minus_price']))) ; 
					}
				}else{
					$store_list[$k]['discount_str'] = '';
				}
				$store_list[$k]['score'] = $v['score'] ? $v['score'] : '5';
			}
		}
		$return['store_list'] = empty($store_list)?array():$store_list;
		$return['store_count'] = count($store_list);

		return $return;
	}

	/*得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function get_group_collect_list($uid){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`group_id`=`c`.`group_id` AND `c`.`uid`='$uid' AND `c`.`type`='group'";

		$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'merchant_user_relation'=>'c');
		$condition_field  = '`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';

		$order = '`c`.`id` DESC';

		import('@.ORG.collect_page');
		$count_group = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_group,10,'page');
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();

		if($group_list){
			$group_image_class = new group_image();
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_group_url($v['group_id'],false);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = L_('新品上架');
				}elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = '';
				}else{
					$group_list[$k]['sale_txt'] = L_('已售').floatval($v['sale_count']+$v['virtual_num']);
				}
			}
		}
		$return['group_list'] = $group_list;

		return $return;
	}

	/*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function wap_get_group_collect_list($uid){
//		$now_time = $_SERVER['REQUEST_TIME'];
//        $condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`group_id`=`c`.`id` AND `c`.`uid`='$uid' AND `c`.`type`='group_detail'";
        $condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`group_id`=`c`.`group_id` AND `c`.`uid`='$uid' AND `c`.`type`='group'";

        $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'merchant_user_relation'=>'c');
		$condition_field  = '`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';

		$order = '`c`.`id` DESC';

		import('@.ORG.wap_collect_page');
		$count_group = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_group,10,'page');
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();

		if($group_list){
			$group_image_class = new group_image();
            $database_group_category = D('Group_category');
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_group_url($v['group_id'],true);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = L_('新品上架');
				}elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = '';
				}else{
					$group_list[$k]['sale_txt'] = L_('已售').floatval($v['sale_count']+$v['virtual_num']);
				}
                // 处理  管理商品属性字段 前端显示问题
                $group_list[$k]['field_info'] = $database_group_category->get_cat_fid_show($v['cat_fid'], $v);
			}
		}
		$return['group_list'] = $group_list;

		return $return;
	}

	public function get_group_by_groupId($group_id,$other=''){
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`status`='1' AND `g`.`group_id`='$group_id'";
		$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m');
		$condition_field  = '`g`.`name` AS `group_name`,`g`.`hits` AS `group_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		$database = D('');
		$now_group = D('')->field($condition_field)->table($condition_table)->where($condition_where)->find();
		if(!empty($now_group)){
			$now_group['price'] = floatval($now_group['price']);
			$now_group['old_price'] = floatval($now_group['old_price']);
			$now_group['wx_cheap'] = floatval($now_group['wx_cheap']);
			$now_group['url'] = C('config.site_url').'/group/'.$now_group['group_id'].'.html';
			$now_group['buy_url'] = C('config.site_url').'/group/buy/'.$now_group['group_id'].'.html';
			if($now_group['group_cate']=='cashing'){
                $now_group['all_pic'][] = ['m_image'=>C('config.site_url').'/static/images/group/cashing.png'];
            }else{
                $group_image_class = new group_image();
                $now_group['all_pic'] = $group_image_class->get_allImage_by_path($now_group['pic']);
            }
//			$now_group['store_list'] = D('Group_store')->get_storelist_by_groupId($now_group['group_id']);
			$now_group['store_list'] = D('Group_store')->get_store_list_by_groupId($now_group['group_id']);
			if(!empty($now_group['store_list'])) {
				if (count($now_group['store_list']) == 1) {
					$now_group['store_list'][0]['area'] = D('Area')->get_area_by_areaId($now_group['store_list'][0]['area_id']);
					$now_group['store_list'][0]['circle'] = D('Area')->get_area_by_areaId($now_group['store_list'][0]['circle_id']);
				} else if ($now_group['trade_type'] == 'hotel') {
					$now_group['store_list'][0]['area'] = D('Area')->get_area_by_areaId($now_group['store_list'][0]['area_id']);
					$now_group['store_list'][0]['circle'] = D('Area')->get_area_by_areaId($now_group['store_list'][0]['circle_id']);
				}
			}

			if($now_group['begin_time']+864000>time()&&($now_group['sale_count']+$now_group['virtual_num'])==0){
				$now_group['sale_txt'] = L_('新品上架');
			}elseif($now_group['begin_time']+864000<time()&&($now_group['sale_count']+$now_group['virtual_num'])==0){
				$now_group['sale_txt'] = '';
			}else{
				$now_group['sale_txt'] = L_('已售').floatval($now_group['sale_count']+$now_group['virtual_num']);
			}

			if($other){
				$condition_group['group_id'] = $group_id;
				switch($other){
					case 'hits-setInc':
						$this->where($condition_group)->setInc('hits');
						break;
				}
			}
		}
		return $now_group;
	}
	public function get_grouplist_by_MerchantId($mer_id,$limit=0,$is_wap=false,$group_id=0){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`mer_id`='$mer_id'";
		if(!empty($group_id)){
			$condition_where .= " AND `g`.`group_id`<>'$group_id'";
		}
		$group_list = D('')->field('`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m'))->where($condition_where)->order('`g`.`sort` DESC,`g`.`group_id` DESC')->limit($limit)->select();
		if($group_list){
			$group_image_class = new group_image();
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}
            $database_group_category = D('Group_category');
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_group_url($v['group_id'],$is_wap);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				$group_list[$k]['pin_num'] = $v['pin_num'];
				$group_list[$k]['trade_type'] = $v['trade_type'];
				if($open_extra_price==1&&$v['extra_pay_price']>0){
					$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$k]['extra_pay_price']='';
				}
				if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = L_('新品上架');
				}elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = '';
				}else{
					$group_list[$k]['sale_txt'] = L_('已售').floatval($v['sale_count']+$v['virtual_num']);
				}
                // 处理  管理商品属性字段 前端显示问题
                $group_list[$k]['field_info'] = $database_group_category->get_cat_fid_show($v['cat_fid'], $v);

			}
		}
		return $group_list;
	}
	/*得到分类下的团购*/
	public function get_grouplist_by_catId($cat_id,$cat_fid,$limit=6,$is_wap=false,$sort=''){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `m`.`city_id`='" . C('config.now_city') . "'";
		if(empty($cat_fid) && !empty($cat_id)){
			$condition_where .= " AND `g`.`cat_fid`='$cat_id'";
		}else if(!empty($cat_id)){
			$condition_where .= " AND `g`.`cat_id`='$cat_id'";
		}
		if(empty($sort)){
			$condition_sort = "'`g`.`sort` DESC,`g`.`group_id` DESC'";
		}else{
			$condition_sort = $sort;
		}

		$group_list = D('')->field('`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m'))->where($condition_where)->order($condition_sort)->limit($limit)->select();
		if($group_list){
			$group_image_class = new group_image();
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}
            $database_group_category = D('Group_category');
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_group_url($v['group_id'],$is_wap);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				$group_list[$k]['trade_type'] = $v['trade_type'];
				if($open_extra_price==1&&$v['extra_pay_price']>0){
					$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$k]['extra_pay_price']='';
				}
				if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = L_('新品上架');
				}elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
					$group_list[$k]['sale_txt'] = '';
				}else{
					$group_list[$k]['sale_txt'] = L_('已售').floatval($v['sale_count']+$v['virtual_num']);
				}
                // 处理  管理商品属性字段 前端显示问题
                $group_list[$k]['field_info'] = $database_group_category->get_cat_fid_show($v['cat_fid'], $v);
                $group_list[$k]['detail_url'] = $this->group_page_num($_GET['page_num'], $v['group_id'], 'group_detail_from');
			}
		}
		return $group_list;
	}
	/*得到订单列表*/
	public function get_order_list($uid,$status,$is_wap=false){
		$condition_where = "`o`.`uid`='$uid' AND `o`.`group_id`=`g`.`group_id`";

		if($status == '0'){
			$condition_where .= " AND `o`.`status`<=3";
		}else if($status == '-1'){
			$condition_where .= " AND `o`.`paid`='0' AND `o`.`status`='0'";
		}else if($status == '1'){
			$condition_where .= " AND `o`.`paid`='1'";
			$condition_where .= " AND `o`.`status`='0'";
		}else if($status == '2'){
			$condition_where .= " AND `o`.`paid`='1'";
			$condition_where .= " AND `o`.`status`='1'";
		}else if($status == '3'){
			$condition_where .= " AND `o`.`paid`='1'";
			$condition_where .= " AND `o`.`status`='2'";
		}
		$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o');

		import('@.ORG.user_page');
		$count = $this->where($condition_where)->table($condition_table)->count();
		$p = new Page($count,10);

		$order_list = $this->field('`o`.*,`g`.`s_name`,`g`.`pic`,`g`.`end_time`')->where($condition_where)->table($condition_table)->order('`o`.`add_time` DESC')->limit($p->firstRow.',10')->select();

		if(!empty($order_list)){
			$group_image_class = new group_image();
			foreach($order_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$order_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$order_list[$k]['url'] = $this->get_group_url($v['group_id'],$is_wap);
				$order_list[$k]['price'] = floatval($v['price']);
				$order_list[$k]['total_money'] = floatval($v['total_money']);
			}
		}

		$return['pagebar'] = $p->show();
		$return['order_list'] = $order_list;

		return $return;
	}
	/*手机版得到订单列表*/
	public function wap_get_order_list($uid,$status = '0',$last_order_id=false,$is_app=false,$mer_id = 0){
		$condition_where ='';
		if($status == '0'){
			$condition_where .= "  (`o`.`status`<=3 OR `o`.`status`=7) ";  // 获取全部团购订单  去除了 4 未付款取消订单，5为未完全消费  6，部分退款
		}else if($status == '-1'){
			$condition_where .= "  `o`.`paid`='0' AND `o`.`status`='0'";  // 待支付
		}else if($status == '1'){
			$condition_where .= "  `o`.`paid`='1' AND `o`.`status`='0'";  // 已支付未消费、待收货
		}else if($status == '2'){
			$condition_where .= " `o`.`paid`='1' AND `o`.`status`='1'";  // 待评价、已消费、已收货
		}else if($status == '3'){
            $condition_where .= "  `o`.`paid`='1' AND `o`.`status`='2'"; // 已完成
        }

		$condition_where .= ' AND `o`.`uid` = '.$uid.' AND `o`.`is_del` = 0';
		
		if($mer_id){
			$condition_where .= ' AND `o`.`mer_id`='.$mer_id;
		}
		
		if($is_app){
			if($last_order_id){
				$condition_where.=" AND `o`.`order_id` =".$last_order_id;
			}
			$order_list = $this
					->join('AS g left join '.C('DB_PREFIX').'group_order AS o ON `o`.`group_id`=`g`.`group_id` ')
					->join( C('DB_PREFIX').'group_buyer_list as l ON `o`.`order_id` = `l`.`order_id` ')
					->join(C('DB_PREFIX').'group_start as s ON `l`.`fid` = `s`.`id` ')
					->field('`o`.order_id,o.real_orderid,o.status,o.paid,o.single_buy,o.pay_time,o.num,o.total_money,o.add_time,o.is_head,o.is_pick_in_store,o.third_id,o.pay_type,`g`.`s_name`,`g`.`pic`,`g`.`end_time`,g.pin_num,g.no_refund,s.status as pin_status')
					->where($condition_where)->page(1,10)->order('`o`.`add_time` DESC')
					->select();
		}else{
			$order_list = $this
					->join('AS g left join '.C('DB_PREFIX').'group_order AS o ON `o`.`group_id`=`g`.`group_id` ')
					->join( C('DB_PREFIX').'group_buyer_list as l ON `o`.`order_id` = `l`.`order_id` ')
					->join(C('DB_PREFIX').'group_start as s ON `l`.`fid` = `s`.`id` ')
					->field('`o`.order_id,o.real_orderid,o.status,o.paid,o.pay_time,o.single_buy,o.num,o.total_money,o.add_time,o.is_head,o.is_pick_in_store,o.third_id,o.pay_type,`g`.`s_name`,`g`.`pic`,`g`.`end_time`,g.pin_num,g.no_refund,s.status as pin_status')
					->where($condition_where)->order('`o`.`add_time` DESC')
					->select();
		}

		if(!empty($order_list)){
			$group_image_class = new group_image();
			foreach($order_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$order_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				//$order_list[$k]['url'] = $this->get_group_url($v['group_id'],true);
				$order_list[$k]['url'] = str_replace('appapi.php', 'wap.php',U('Wap/Group/detail',array('pin_num'=>$v['pin_num'],'group_id'=>$v['group_id'])));
				$order_list[$k]['order_url'] =  U('My/group_order',array('order_id'=>$v['order_id']));
				$order_list[$k]['price'] = floatval($v['price']);
				$order_list[$k]['total_money'] = floatval($v['total_money']);
				$order_list[$k]['refund_status'] = !$v['status']&&($v['paid']==1?1:0);

				if(($v['status'] == '1') && $v['is_pick_in_store']== '1'){
					$order_list[$k]['status_txt']=L_('已自提');
				}elseif($v['status'] ==1){
					$order_list[$k]['status_txt']=L_('去评价');
				}elseif($v['status'] ==2){
					$order_list[$k]['status_txt']=L_('已完成');
				}elseif($v['status'] ==3){
					$order_list[$k]['status_txt']=L_('已取消');
				}elseif(empty($v['paid'])){
					$order_list[$k]['status_txt']=L_('未付款');
				}elseif(empty($v['status']) && ($v['is_head'] > 0)){
					$order_list[$k]['status_txt']=L_('未消费');
				}elseif(empty($v['status'])){
					$order_list[$k]['status_txt']=L_('未消费');
				}elseif($v['status'] == '1'){
					$order_list[$k]['status_txt']=L_('未评价');
				}elseif($v['status'] == '2'){
					$order_list[$k]['status_txt']=L_('已完成');
				}else if($v['paid']){
					$order_list[$k]['status_txt']=L_('已付款');
				}elseif(empty($v['third_id']) && $v['pay_type'] == 'offline'){
                    $order_list[$k]['status_txt']=L_('线下未付款');
                }
				if($v['pin_num']>0 && $v['single_buy']!=1){
					switch($v['pin_status']){
						case 0:
							$order_list[$k]['pin_status_str']=  '<font color="red">未成团</font>';
							$order_list[$k]['pin_status_txt']=  L_('未成团');
							break;
						case 1:
							$order_list[$k]['pin_status_str']=  '<font color="green">已成团</font>';
							$order_list[$k]['pin_status_txt']=  L_('已成团');
							break;
						case 2:
							$order_list[$k]['pin_status_str']=  '<font color="red">拼团超时</font>';
							$order_list[$k]['pin_status_txt']=  L_('拼团超时');
							break;
						case 3:
							$order_list[$k]['pin_status_str']=  '<font color="green">已成团</font>';
							$order_list[$k]['pin_status_txt']=  L_('已成团');
							break;
					}
				}else{
					$order_list[$k]['pin_status_txt']=  '';
					$order_list[$k]['pin_status']=  '';
				}

				if(!empty($order['pin_status_txt'])){
					$order_list[$k]['status_txt'].='('.$v['pin_status_txt'].')';
				}
			}
		}
		return $order_list;
	}




	/*得到待评价订单列表*/
	public function get_rate_order_list($uid,$is_rate=false,$is_wap=false){
		$condition_where = "`o`.`uid`='$uid' AND `o`.`group_id`=`g`.`group_id`";
		if($is_rate){
			$condition_where .= " AND `o`.`paid`='1'";
			$condition_where .= " AND `o`.`status`='2'";
			$condition_where .= " AND `r`.`order_type`='0' AND `r`.`order_id`=`o`.`order_id`";
			$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o',C('DB_PREFIX').'reply'=>'r');
			$condition_field = '`o`.*,`g`.`s_name`,`g`.`pic` `group_pic`,`g`.`end_time`,`r`.*';
			$condition_order = '`r`.`pigcms_id` DESC';
		}else{
			$condition_where .= " AND `o`.`paid`='1'";
			$condition_where .= " AND `o`.`status`='1'";
			$condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o');
			$condition_field = '`o`.*,`g`.`s_name`,`g`.`pic` `group_pic`,`g`.`end_time`';
			$condition_order = '`o`.`add_time` DESC';
		}

		$order_list = $this->field($condition_field)->where($condition_where)->table($condition_table)->order($condition_order)->select();
		if(!empty($order_list)){
			$group_image_class = new group_image();
			foreach($order_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['group_pic']);
				$order_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$order_list[$k]['url'] = $this->get_group_url($v['group_id'],$is_wap);
				$order_list[$k]['price'] = floatval($v['price']);
				$order_list[$k]['total_money'] = floatval($v['total_money']);
				if($is_rate){
					$order_list[$k]['comment'] = stripslashes($v['comment']);
					if($v['pic']){
						$tmp_array = explode(',',$v['pic']);
						$order_list[$k]['pic_count'] = count($tmp_array);
					}
				}
			}
		}

		return $order_list;
	}
	public function get_group_url($group_id,$is_wap){
		if($is_wap){
			$now_group = $this->where(array('group_id'=>$group_id))->find();
			return str_replace('appapi.php', 'wap.php',U('Wap/Groupnew/detail',array('pin_num'=>$now_group['pin_num'],'group_id'=>$group_id)));
		}else{
			return C('config.site_url').'/group/'.$group_id.'.html';
		}
	}
	public function search_get_group_url($group_id,$is_wap,$keywords){
		if($is_wap){
			return U('Wap/Groupnew/detail',array('group_id'=>$group_id,'keywords'=>urlencode($keywords)));
		}else{
			return C('config.site_url').'/group/'.$group_id.'.html';
		}
	}
	/*增加一次团购评论数*/
	public function setInc_group_reply($now_order, $score)
	{
		if ($group = $this->field(true)->where(array('group_id' => $now_order['group_id']))->find()) {
			$data_group['reply_count'] = $group['reply_count'] + 1;
			$data_group['score_all'] = $group['score_all'] + $score;
			$data_group['score_mean'] = $data_group['score_all'] / $data_group['reply_count'];
			if ($this->where(array('group_id' => $now_order['group_id']))->data($data_group)->save()) return true;
		}
		return false;
	}

	/*减少一次团购评论数*/
	public function setDec_group_reply($reply)
	{
		if ($group = $this->field(true)->where(array('group_id' => $reply['parent_id']))->find()) {
			$data_group['reply_count'] = max(0, $group['reply_count'] - 1);
			$data_group['score_all'] = max(0, $group['score_all'] - $reply['score']);
			if ($data_group['reply_count'] == 0 || $data_group['score_all'] == 0) {
				$data_group['score_mean'] = 0;
			} else {
				$data_group['score_mean'] = $data_group['score_all'] / $data_group['reply_count'];
			}
			if ($this->where(array('group_id' => $reply['parent_id']))->data($data_group)->save()) return true;
		}
		return false;
	}

	public function get_qrcode($id){
		$condition_group['group_id'] = $id;
		$now_group = $this->field('`group_id`,`qrcode_id`')->where($condition_group)->find();
		if(empty($now_group)){
			return false;
		}
		return $now_group;
	}
	public function save_qrcode($id,$qrcode_id){
		$condition_group['group_id'] = $id;
		$data_group['qrcode_id'] = $qrcode_id;
		if($this->where($condition_group)->data($data_group)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_("保存二维码至X1失败！请重试。",array("X1" => C('config.group_alias_name'))))); 
		}
	}
	public function del_qrcode($id){
		$condition_group['group_id'] = $id;
		$data_group['qrcode_id'] = '';
		if($this->where($condition_group)->data($data_group)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_("保存二维码至X1失败！请重试。",array("X1" => C('config.group_alias_name'))))); 
		}
	}
	/*得到团购的微信优惠*/
	public function get_group_cheap($id){
		$now_group = $this->field('`wx_cheap`')->where(array('group_id'=>$id))->find();
		return floatval($now_group['wx_cheap']);
	}

	public function scenic_get_group_list_by_cate($catfid, $area_id, $mer_id,$num,$lat = 0, $long = 0,$cat_name,$cat_url,$page){
		//$lat = 31.823263;
		//$long = 117.235268;
		$_POST['page'] = 'page';
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`m`.`city_id`='".$area_id."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1'AND `g`.`tuan_type` <> 2 AND `m`.`status`='1'";// AND `g`.`begin_time`<'$now_time'  AND `g`.`end_time`>'$now_time'
		//分类相关
		if(!empty($catfid)&&empty($mer_id)){
			$condition_where .= " AND `g`.`cat_fid`='$catfid'";
		}elseif(!empty($catfid)&&!empty($mer_id)){
			$condition_where .= " AND `g`.`cat_fid`='$catfid' AND `g`.`mer_id`='$mer_id'";
		}else{
			return array();
		}
		$condition_field  = ' `g`.`group_id`,`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		$condition_where .= "  AND `gc`.`group_id`=`g`.`group_id`";
		$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
		$condition_where .= " AND `m`.`mer_id`=`s`.`mer_id`";
		if(empty($mer_id)&&!empty($lat)&&!empty($long)){
			$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
			$order = 'juli asc,`g`.`group_id` DESC';
		}else{
			$order = '`g`.`group_id` DESC';
		}

		//排序
		import('@.ORG.group_page');
		$group_count =  D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($group_count,C('config.group_page_row'),'page');
		if(empty($num)){
			$group_list_arr = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->group('`g`.`group_id`, `m`.`mer_id`')->limit($p->firstRow.','.$p->listRows)->select();
		}else{
			$group_list_arr = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->group('`g`.`group_id`, `m`.`mer_id`')->limit($num)->select();
		}
		if($group_list_arr){
			$group_image_class = new group_image();
			$open_extra_price = 0;
			if(C('config.open_extra_price')==1){
				$open_extra_price = 1;
				$extra_price_name = C('config.extra_price_alias_name');
			}

			foreach($group_list_arr as $k=>$v) {
				$group_list[$k]['group_id'] = $v['group_id'];
				$mer_arr[] = $v['mer_id'];
				$group_list[$k]['mer_id'] = $v['mer_id'];
				$group_list[$k]['name'] = $v['group_name'];
				if (!empty($lat) && !empty($long)){
					if ($v['juli'] < 100) {
						$group_list[$k]['juli'] = '<100m';
					} else if ($v['juli'] < 1000) {
						$group_list[$k]['juli'] = $v['juli'] . 'm';
					} else {
						$group_list[$k]['juli'] = round($v['juli'] / 1000, 1) . 'km';
					}
				}
				$tmp_pic_arr = explode(';',$v['pic']);
				foreach ($tmp_pic_arr as $p){
					$group_list[$k]['list_pic'][] = $group_image_class->get_image_by_path($p,'s');
				}

				$group_list[$k]['url'] = $this->get_group_url($v['group_id'],true);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				$group_list[$k]['is_start'] = 1;
				$group_list[$k]['pin_num'] = $v['pin_num'];
				if($open_extra_price==1&&$v['extra_pay_price']>0){
					$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$k]['extra_pay_price']='';
				}
				if ($now_time < $v['begin_time']) {
					$group_list[$k]['is_start'] = 0;
				}
				$group_list[$k]['begin_time'] = date("Y-m-d H:i:s", $v['begin_time']);
				$group_list[$k]['cat_name'] = $cat_name;
				$group_list[$k]['url'] = U('Group/detail',array('group_id'=>$v['group_id']));
				$group_list[$k]['detail_url'] = U('Group/index',array('cat_url'=>$cat_url));
				$group_list[$k]['sale_count'] = $v['sale_count'];
				$group_list[$k]['trade_type'] = $v['trade_type'];
			}
		}


		$return['group_list'] = $group_list;

		return $return;
	}

	public function  group_notice($order,$verify_all)
	{

			//积分
			if($verify_all){

				$order['order_type']='group';
				$order['verify_all']=1;
				$order['desc']=L_("用户购买X1记入收入",array("X1" => $order['order_name'])); 
				//$order['store_id'] =$order['store_id'];
				//$order['mer_id'] =$order['mer_id'];
				//D('Merchant_money_list')->add_money($order['mer_id'],'用户购买'.$order['order_name'].'记入收入',$order);
				D('SystemBill')->bill_method($order['is_own'],$order);
				$now_user = M('User')->where(array('uid'=>$order['uid']))->find();
				if(C('config.open_extra_price')==1){
					$order['order_type'] = 'group';
					$score = D('Percent_rate')->get_extra_money($order);
					if($score>0){
						D('User')->add_score($order['uid'], floor($score),L_("购买 X1 消费X2 获得X3",array("X1" => $order['order_name'],"X2" => floatval($order['total_money']) . C('config.Currency_txt'),"X3" => C('config.extra_price_alias_name')))); 
					}

				}else {
					if(C('config.open_score_get_percent')==1){
						$score_get = C('config.score_get_percent')/100;
					}else{

						$score_get = C('config.user_score_get');
					}

					if($order['is_own'] && C('config.user_own_pay_get_score')!=1){
						$order['payment_money']= 0;
					}	


					D('User')->add_score($order['uid'], round(($order['payment_money'] + $order['balance_pay']) * $score_get),L_("购买 X1 消费X2 获得X3",array("X1" => $order['order_name'],"X2" => floatval($order['total_money']) . C('config.Currency_txt'),"X3" => C('config.score_name'))));
					D('Scroll_msg')->add_msg('group',$now_user['uid'],L_('用户x1于x2购买x3成功并消费获得x4',array('x1'=>str_replace_name($now_user['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>C('config.group_alias_name'),'x4'=>C('config.score_name'))));

					D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['total_money'], L_("购买 X1 消费X2 获得积分",array("X1" => $order['order_name'],"X2" => floatval($order['total_money']) . C('config.Currency_txt')))); 
				}
				//商家推广分佣

				D('Merchant_spread')->add_spread_list($order,$now_user,'group',L_("X1购买X2获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.group_alias_name')))); 

			}

			//短信
			$sms_data = array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id'], 'type' => 'group');
			if (C('config.sms_group_finish_order')==1|| C('config.sms_group_finish_order')== 3) {
				$sms_data['uid'] = $order['uid'];
				$sms_data['mobile'] = $order['phone'];
				$sms_data['sendto'] = 'user';
				if(empty($order['res'])){
					$sms_data['content'] = L_("您购买 X1的订单（订单号：X2）已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！",array("X1" => $order['order_name'],"X2" => $order['real_orderid'])); 
				}else{
					$sms_data['content'] = L_("您购买 X1的订单（消费码：X2）已经完成了消费，如有任何疑意，请您及时联系我们！",array("X1" => $order['order_name'],"X2" => $order['res']['group_pass'])); 
				}
				Sms::sendSms($sms_data);
			}
			if (C('config.sms_group_finish_order')==2 || C('config.sms_group_finish_order')==3) {
				$sms_data['uid'] = 0;
				$sms_data['mobile'] = $this->store['phone'];
				$sms_data['sendto'] = 'merchant';
				$sms_data['content'] = L_("顾客购买的X1的订单(订单号：X2)，已经完成了消费！",array("X1" => $order['order_name'],"X2" => $order['real_orderid'])); 
				Sms::sendSms($sms_data);
			}

			//打印
			$printHaddle = new PrintHaddle();
			$printHaddle->printit($order['order_id'], 'group_order', 2);
	}


	public function wait_for_confirm_express_list(){
		$where['o.status'] = 0;
		$where['o.paid'] = 1;
		$where['o.express_id'] =array('neq','') ;
		$where['o.is_pick_in_store'] =0;
		$where['m.group_express_outtime'] =array('neq',0);
		$where['_string'] ='o.last_time<('.time().'-m.group_express_outtime*86400)';
		$order_list =  M('Group_order')->field('o.*,u.openid')->join('as o  left join '.C('DB_PREFIX').'merchant m on o.mer_id = m.mer_id left join '.C('DB_PREFIX').'user u ON u.uid = o.uid')->where($where)->limit(5)->select();

		foreach ($order_list as $value) {
			$data_group_order['status'] = 1; //原来是1
			$data_group_order['use_time'] = time();
			$condition_group_order['order_id'] = $value['order_id'];
			if (M('Group_order')->where($condition_group_order)->data($data_group_order)->save()) {
				$express_nmae = D('Express')->get_express($value['express_type']);
				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
				$href = C('config.site_url').'/wap.php?c=My&a=group_order_list';
				$model->sendTempMsg('TM00017', array('href' => $href,
						'wecha_id' => $value['openid'],
						'first' => C('config.group_alias_name').L_('快递发货通知'),
						'OrderSn' => $value['real_orderid'],
						'OrderStatus' =>$this->staff_session['name'].L_('已为您发货'),
						'remark' =>L_("快递号：X1)，请尽快确认",array("X1" => $value['order_id'],"X2" => $express_nmae['name']))), $value['mer_id']); 

				$this->group_notice($value,1);
			}
		}
		return $order_list;
	}

	public function wapFriendRange($meter){
		if($meter < 100){
			return '<100m';
		}else if($meter <1000){
			return $meter.'m';
		}else{
			return round($meter/1000,1).'km';
		}
	}


	public function get_express_fee($group_id=0,$money=0,$address=array()){
		$now_group = $this->where(array('group_id'=>$group_id))->find();
		$express_fee = $now_group['express_fee'];

		if(!empty($now_group['express_template_id'])){
			$express_fee_list = M('Express_template')
					->join('as e left join '.C('DB_PREFIX').'express_template_area as a ON e.id = a.tid left join '.C('DB_PREFIX').'express_template_value as v ON a.vid = v.id')
					->where(array('e.id'=>$now_group['express_template_id']))->select();

			foreach ($express_fee_list as &$v) {
				if($v['freight_type'] == 1 && $v['freight'] == 0){
					$v['freight'] = $v['first_freight'];
				}
				
                // 添加同城
                if ($v['mer_id'] && $v['area_id'] == 0) {
                    $v['area_id'] = M('Merchant')->where(array('store_id' => $v['mer_id'], 'status' => 1))->getField('city_id');
                }

				if($v['area_id'] == $address['area']){

					if($money>=$v['full_money'] && $v['full_money']!=0){
						$v['freight'] = 0;
						return $v;
					}
					return $v;
				}
				if($v['area_id'] ==$address['city']){

					if($money>=$v['full_money'] && $v['full_money']!=0 ){
						$v['freight'] = 0;
						return $v;
					}
					return $v;
				}
				if($v['area_id'] == $address['province']){

					if($money>=$v['full_money'] && $v['full_money']!=0){
						$v['freight'] = 0;
						return $v;
					}
					return $v;
				}
			}
		}
		return array('freight'=>$express_fee,'full_money'=>'0');

	}



    /*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/

    /**
     * 团购优选首页
     * @param $area_id
     * @param $order
     * @param int $lat
     * @param int $long
     * @param int $circle_id
     * @param int $round
     * @param int $page_size
     * @param int $cat_fid
     * @param int $cat_id
     * @param bool $record_num
     * @param int $avatar
     * @return mixed
     */
    public function group_list_index($order, $page_size=0,$cat_fid=0,$cat_id=0, $record_num=false, $avatar=0, $lat = 0, $long = 0, $circle_id = 0,$round = 0, $area_id=0, $is_wap = 0){
        $now_time = $_SERVER['REQUEST_TIME'];
        $condition_where = "`m`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1' AND `m`.`status`='1' AND `g`.`end_time`>'$now_time' AND `s`.`have_group`=1 AND `s`.`group_on_sale`=1";// AND `g`.`begin_time`<'$now_time'
        if($is_wap){
            $condition_where .= " AND `g`.`group_cate` != 'cashing'";
        }
        
        // 暂时不查询 关联插件的团购
//        $condition_where .= " AND `g`.`trade_type`=''";

        // 处理 关于 团购二级分类多关联查询 C('config.group_category_switch')
        if (!empty($cat_fid)){
            //分类相关
            $condition_where .= " AND `g`.`cat_fid`='$cat_fid'";
        } else {
            if (C('config.group_category_switch') && C('config.group_category_switch') == 1) {
                $bind_where = array();
                if(!empty($cat_fid)){
                    $bind_where['cat_fid'] = $cat_fid;
                }else if(!empty($cat_id)){
                    $bind_where['cid'] = $cat_id;
                }
                if ($bind_where) {
                    $group_info = M('Group_bind_category')->where($bind_where)->getField('bind_id, group_id');
                    if ($group_info && $cat_id) {
                        $group_info = implode(',', $group_info);

                        $condition_where .= " AND (`g`.`cat_id`='$cat_id' OR `g`.`group_id` IN ('".$group_info. "'))";
                    } elseif(!empty($cat_id)){
                        $condition_where .= " AND `g`.`cat_id`='$cat_id'";
                    }
                }
            } elseif(!empty($cat_id)){
                $condition_where .= " AND `g`.`cat_id`='$cat_id'";
            }
        }

        //区域
        if($area_id || $circle_id){
            $condition_field  = 'DISTINCT `g`.`group_id`,`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*';
            if ($circle_id) {
                $condition_where .= " AND `gc`.`circle_id`='$circle_id' AND `gc`.`group_id`=`g`.`group_id`";
            } else {
                $condition_where .= " AND `gc`.`area_id`='$area_id' AND `gc`.`group_id`=`g`.`group_id`";
            }
            if ($order == 'juli') {
                $condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
                $condition_where .= " AND `m`.`mer_id`=`s`.`mer_id`";

                $group_index_store_is_show = C('config.group_index_store_is_show');
                if ($group_index_store_is_show == 1) {
                    $condition_field .= ", CASE  WHEN (`g`.`long` <> 0 AND `g`.`lat` <> 0) then ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000) else ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) end as juli";
                } else {
                    $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                }
            } else {
                $condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
            }


        }else{
            $condition_field  = '`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*';
            if ($order == 'juli') {
                $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
                $condition_where .= " 	AND `g`.`group_id` = `gs`.`group_id` AND `gs`.`store_id`=`s`.`store_id` AND `m`.`mer_id`=`s`.`mer_id`  ";


                $group_index_store_is_show = C('config.group_index_store_is_show');
                if ($group_index_store_is_show == 1) {
                    if($round){
                        $condition_where .= " AND (((`g`.`long` = 0 OR `g`.`lat` = 0) AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000)<{$round}) OR ((`g`.`long` <> 0 AND `g`.`lat` <> 0) AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000)<{$round}))";
                    }
                    $condition_field .= ", CASE  WHEN (`g`.`long` <> 0 AND `g`.`lat` <> 0) then ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000) else ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) end as juli";
                } else {
                    if($round){
                        $condition_where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000)<{$round} ";
                    }
                    $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                }
            } else {
                $condition_field .= ", CASE  WHEN (`g`.`long` <> 0 AND `g`.`lat` <> 0) then ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000) else ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) end as juli";
                $condition_where .= " AND `g`.`group_id` = `gs`.`group_id` AND `gs`.`store_id`=`s`.`store_id` AND `m`.`mer_id`=`s`.`mer_id`  ";
                $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
            }

        }

		if (isset($_POST['group_cate']) && $_POST['group_cate']) {
			$condition_where .= " AND g.group_cate='" . $_POST['group_cate'] . "'";
		}

        //排序
        switch($order){
            case 'pop':
                $order = '`g`.`pop_sort` DESC,`g`.`group_id` DESC';
                break;
            case 'hot':
                $order = '`g`.`hot_sort` DESC,`g`.`group_id` DESC';
                break;
            case 'sales':
                $order = '(`g`.`sale_count` +`g`.`virtual_num`) DESC,`g`.`group_id` DESC';
                break;
            case 'new_time':
                $order = '`g`.`add_time` DESC,`g`.`group_id` DESC';
                break;
            case 'start':
                $order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
                break;
            case 'juli':
                $order = 'juli asc,`g`.`group_id` DESC';
                break;
            default:
                $order = '`g`.`sort` DESC,`g`.`group_id` DESC';
        }

        import('@.ORG.wap_group_page');

        $subQuery =D('')->field('g.group_id')->table($condition_table)->where($condition_where)->group('`g`.`group_id`, `m`.`mer_id`')->buildSql();
        $count_group =  M()->table($subQuery.' a')->count();
//        if(GROUP_NAME!='Appapi' && $count_group>C('config.group_list_default_num')){
//            $count_group = C('config.group_list_default_num');
//        }
        if ($page_size > 0) {
            $page_row = intval($page_size);
        } else {
            $page_row = C('config.group_page_row');
        }
        $page_info = $_GET['page'];
        $p = new Page($count_group,$page_row, 'page');
        $group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->group('`g`.`group_id`, `m`.`mer_id`')->limit($p->firstRow.','.$p->listRows)->select();
        fdump(D('')->getLastSql(),"dffffffftttttttttttt555555556");
        if ($page_info>0 && $p->nowPage < $page_info) {
            $group_list = array();
        }
        $return['pagebar'] = $p->show();
        if($group_list){
            $group_image_class = new group_image();
            if(C('config.open_extra_price')==1){
                $open_extra_price = 1;
                $extra_price_name = C('config.extra_price_alias_name');
            }
            foreach($group_list as $k=>&$v){
                $tmp_pic_arr = explode(';',$v['pic']);
                $group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'');
                $group_list[$k]['price'] = floatval($v['price']);
                $group_list[$k]['old_price'] = floatval($v['old_price']);
                $group_list[$k]['is_start'] = 1;
                $group_list[$k]['juli_txt'] = $this->wapFriendRange($v['juli']);

                if($open_extra_price==1&&$v['extra_pay_price']>0){
                    $group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
                }else{
                    $group_list[$k]['extra_pay_price']='';
                }
                if ($now_time < $v['begin_time']) {
                    $group_list[$k]['is_start'] = 0;
                }
                $group_list[$k]['begin_time'] = date("Y-m-d H:i:s", $v['begin_time']);
                $group_list[$k]['sale_count'] = $group_list[$k]['sale_count']+$group_list[$k]['virtual_num'];
				if ($record_num){
					$msg = $this->group_record_num($group_list[$k]['group_id'], $avatar);
					$group_list[$k]['look_num'] = $msg['look_num'];
					$group_list[$k]['buy_times'] = $msg['buy_times'];
                    $group_list[$k]['avatar_info'] = $msg['avatar_info'];
				}
                $group_list[$k]['detail_url'] = $this->group_page_num($_GET['page_num'], $v['group_id'], 'group_detail');
                //积分展示
                $v['score_text'] = '';
                if(C('customization.goods_show_score') && $scorePrice = floatval($v['price'])){
                    $scorePercent = D('Percent_rate')->scorePercent('group', $v['group_id'], $v['mer_id']);
                    $score = bcmul($scorePrice, $scorePercent, 2);
                    if(C('config.score_round_two')){
                        $score = sprintf('%.2f',floatval($score));
                    }else{
                        $score = round($score);
                    }
                    floatval($score) > 0 && $v['score_text'] = C('config.score_name').'+'. $score;
                }
            }
        }

        if (!$group_list) $group_list = array();
        $return['group_list'] = $group_list;
        $return['group_count'] = $count_group;
        $return['totalPage'] = ceil($count_group/$page_row);
        $return['more'] = $return['totalPage']>1?true:false;
        return $return;
    }


    /**
     * 团购优选
     * @param $area_id
     * @param $order
     * @param int $lat
     * @param int $long
     * @param int $circle_id
     * @param int $round
     * @param int $page_size
     * @param int $cat_fid
     * @param int $cat_id
     * @return mixed
     */
    public function group_list_info($order, $page_size=0,$cat_fid=0,$cat_id=0, $lat = 0, $long = 0, $circle_id = 0,$round = 0, $area_id=0){
        $now_time = $_SERVER['REQUEST_TIME'];
        $condition_where = "`m`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1' AND `m`.`status`='1' AND `g`.`end_time`>'$now_time' AND `s`.`have_group`=1";// AND `g`.`begin_time`<'$now_time'

        // 暂时不查询 关联插件的团购
        $condition_where .= " AND `g`.`trade_type`=''";

        // 处理 关于 团购二级分类多关联查询 C('config.group_category_switch')
        if (!empty($cat_fid)){
            //分类相关
            $condition_where .= " AND `g`.`cat_fid`='$cat_fid'";
        } else {
            if (C('config.group_category_switch') && C('config.group_category_switch') == 1) {
                $bind_where = array();
                if(!empty($cat_fid)){
                    $bind_where['cat_fid'] = $cat_fid;
                }else if(!empty($cat_id)){
                    $bind_where['cid'] = $cat_id;
                }
                if ($bind_where) {
                    $group_info = M('Group_bind_category')->where($bind_where)->group('group_id')->getField('bind_id, group_id');
                    if ($group_info && $cat_id) {
                        $group_info = implode(',', $group_info);
                        $condition_where .= " AND (`g`.`cat_id`='$cat_id' OR `g`.`group_id` IN ('" . $group_info . "'))";
                    } elseif(!empty($cat_id)){
                        $condition_where .= " AND `g`.`cat_id`='$cat_id'";
                    }
                }
            } elseif(!empty($cat_id)){
                $condition_where .= " AND `g`.`cat_id`='$cat_id'";
            }
        }

        //区域
        if($area_id || $circle_id){
            $condition_field  = 'DISTINCT `g`.`group_id`,`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*';
            if ($circle_id) {
                $condition_where .= " AND `gc`.`circle_id`='$circle_id' AND `gc`.`group_id`=`g`.`group_id`";
            } else {
                $condition_where .= " AND `gc`.`area_id`='$area_id' AND `gc`.`group_id`=`g`.`group_id`";
            }
            if ($order == 'juli') {
                $condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
                $condition_where .= " AND `m`.`mer_id`=`s`.`mer_id`";

                $group_index_store_is_show = C('config.group_index_store_is_show');
                if ($group_index_store_is_show == 1) {
                    $condition_field .= ", CASE  WHEN (`g`.`long` <> 0 AND `g`.`lat` <> 0) then ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000) else ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) end as juli";
                } else {
                    $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                }
            } else {
                $condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
            }


        }else{
            $condition_field  = '`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*';
            if ($order == 'juli') {
                $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
                $condition_where .= " 	AND `g`.`group_id` = `gs`.`group_id` AND `gs`.`store_id`=`s`.`store_id` AND `m`.`mer_id`=`s`.`mer_id`  ";


                $group_index_store_is_show = C('config.group_index_store_is_show');
                if ($group_index_store_is_show == 1) {
                    if($round){
                        $condition_where .= " AND (((`g`.`long` = 0 OR `g`.`lat` = 0) AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000)<{$round}) OR ((`g`.`long` <> 0 AND `g`.`lat` <> 0) AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000)<{$round}))";
                    }
                    $condition_field .= ", CASE  WHEN (`g`.`long` <> 0 AND `g`.`lat` <> 0) then ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`g`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`g`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`g`.`long`*PI()/180)/2),2)))*1000) else ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) end as juli";
                } else {
                    if($round){
                        $condition_where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000)<{$round} ";
                    }
                    $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                }
            } else {
                $condition_where .= " 	AND `g`.`group_id` = `gs`.`group_id` AND `gs`.`store_id`=`s`.`store_id` AND `m`.`mer_id`=`s`.`mer_id`  ";
                $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gs',C('DB_PREFIX').'merchant_store'=>'s');
            }

        }

        //排序
        switch($order){
            case 'pop':
                $order = '`g`.`pop_sort` DESC,`g`.`group_id` DESC';
                break;
            case 'hot':
                $order = '`g`.`hot_sort` DESC,`g`.`group_id` DESC';
                break;
            case 'sales':
                $order = '`g`.`sale_count` DESC,`g`.`group_id` DESC';
                break;
            case 'new_time':
                $order = '`g`.`add_time` DESC,`g`.`group_id` DESC';
                break;
            case 'start':
                $order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
                break;
            case 'juli':
                $order = 'juli asc,`g`.`group_id` DESC';
                break;
            default:
                $order = '`g`.`sort` DESC,`g`.`group_id` DESC';
        }

        import('@.ORG.wap_group_page');

        $subQuery =D('')->field('g.group_id')->table($condition_table)->where($condition_where)->group('`g`.`group_id`, `m`.`mer_id`')->buildSql();
        $count_group =  M()->table($subQuery.' a')->count();
        if(GROUP_NAME!='Appapi' && $count_group>C('config.group_list_default_num')){
            $count_group = C('config.group_list_default_num');
        }
        if ($page_size > 0) {
            $page_row = intval($page_size);
        } else {
            $page_row = C('config.group_page_row');
        }
        $p = new Page($count_group,$page_row,C('config.group_page_val'));
        $group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->group('`g`.`group_id`, `m`.`mer_id`')->limit($p->firstRow.','.$p->listRows)->select();

        $return['pagebar'] = $p->show();
        if($group_list){
            $group_image_class = new group_image();
            if(C('config.open_extra_price')==1){
                $open_extra_price = 1;
                $extra_price_name = C('config.extra_price_alias_name');
            }
            foreach($group_list as $k=>$v){
                $tmp_pic_arr = explode(';',$v['pic']);
                $group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
                $group_list[$k]['url'] = $this->get_group_url($v['group_id'],true);
                $group_list[$k]['price'] = floatval($v['price']);
                $group_list[$k]['old_price'] = floatval($v['old_price']);
                $group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
                $group_list[$k]['is_start'] = 1;
                $group_list[$k]['trade_type'] = $v['trade_type'];
                $group_list[$k]['pin_num'] = $v['pin_num'];
                $group_list[$k]['juli_txt'] = $this->wapFriendRange($v['juli']);
                if($v['begin_time']+864000>time()&&($v['sale_count']+$v['virtual_num'])==0){
                    $group_list[$k]['sale_txt'] = L_('新品上架');
                }elseif($v['begin_time']+864000<time()&&($v['sale_count']+$v['virtual_num'])==0){
                    $group_list[$k]['sale_txt'] = '';
                }else{
                    $group_list[$k]['sale_txt'] = L_('已售').floatval($v['sale_count']+$v['virtual_num']);
                }
                if($open_extra_price==1&&$v['extra_pay_price']>0){
                    $group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
                }else{
                    $group_list[$k]['extra_pay_price']='';
                }
                if ($now_time < $v['begin_time']) {
                    $group_list[$k]['is_start'] = 0;
                }
                $group_list[$k]['begin_time'] = date("Y-m-d H:i:s", $v['begin_time']);
                $group_list[$k]['sale_count'] = $group_list[$k]['sale_count']+$group_list[$k]['virtual_num'];
                $msg = $this->group_record_num($group_list[$k]['group_id']);
                $group_list[$k]['look_num'] = $msg['look_num'];
                $group_list[$k]['buy_times'] = $msg['buy_times'];
            }
        }

        if (!$group_list) $group_list = array();
        $return['group_list'] = $group_list;
        $return['group_count'] = $count_group;
        $return['totalPage'] = ceil($count_group/$page_row);
        return $return;
    }

    /**
     * 团购优选浏览和购买数
     * @param $group_id
     * @param int $avatar
     * @return array
     */
    public function group_record_num($group_id, $avatar = 0) {
        $m_group_record = M('Group_record');
        $look_avatar = $m_group_record->alias('a')
            ->join('join pigcms_user as b on a.uid=b.uid')
            ->field('b.uid')
            ->group('a.uid')
            ->where(array('a.group_id' => $group_id, 'a.type' => 1))
            ->select();
        // 获取浏览数
        $look_num = count($look_avatar);

        $buy_avatar = $m_group_record->alias('a')
            ->join('join pigcms_user as b on a.uid=b.uid')
            ->field('b.uid')
            ->group('a.uid')
            ->where(array('a.group_id' => $group_id, 'a.type' => 2))
            ->order('a.add_time DESC')
            ->select();
        // 获取购买次数
        $buy_times = count($buy_avatar);
        // 判断是否获取购买者头像或者浏览者头像 购买者最少3个最多5个头像
        $avatar_info = array();
        $type = 0;
        if ($avatar > 0) {
            if($buy_times >= 3 && $avatar > 1) {
                $avatar_info = $m_group_record->alias('a')
                    ->join('join pigcms_user as b on a.uid=b.uid')
                    ->field('b.avatar')
                    ->group('a.uid')
                    ->where(array('a.group_id' => $group_id, 'a.type' => 2))
                    ->order('a.add_time DESC')
                    ->limit(5)
                    ->select();
                $type = 2;
            } elseif ($look_num >= 3 && $avatar > 0) {
                $avatar_info = $m_group_record->alias('a')
                    ->join('join pigcms_user as b on a.uid=b.uid')
                    ->field('b.avatar')
                    ->group('a.uid')
                    ->where(array('a.group_id' => $group_id, 'a.type' => 1))
                    ->order('a.add_time DESC')
                    ->limit(5)
                    ->select();
                $type = 1;
            }
        }
        // 处理下没有头像取默认头像
        if (!empty($avatar_info)) {
            foreach ($avatar_info as &$val) {
                if($val){
					if (!$val['avatar']) {
						$val['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/images/new_my/pic-default.png?t=001';
					}else{
						$val['avatar'] = replace_file_domain($val['avatar']);
					}
				}
            }
        }
        $avatar_info = array_reverse($avatar_info);
        return array(
            'type' => $type,
            'look_num' => $look_num,
            'buy_times' => $buy_times,
            'avatar_info' => $avatar_info
        );
    }



    /**
     * 团购优选商家浏览和购买数
     * @param $mer_id
     * @param int $avatar
     * @return array
     */
    public function merchant_record_num($mer_id, $avatar = 0) {
        $m_group_record = M('Group_record');
        $look_avatar = $m_group_record->alias('a')
            ->join('join pigcms_user as b on a.uid=b.uid')
            ->field('b.uid')
            ->group('a.uid')
            ->where(array('a.mer_id' => $mer_id, 'a.type' => 1))
            ->select();
        // 获取浏览数
        $look_num = count($look_avatar);
        $buy_avatar = $m_group_record->alias('a')
            ->join('join pigcms_user as b on a.uid=b.uid')
            ->field('b.uid')
            ->group('a.uid')
            ->where(array('a.mer_id' => $mer_id, 'a.type' => 2))
            ->order('a.add_time DESC')
            ->select();
        // 获取购买次数
        $buy_times = count($buy_avatar);
        // 判断是否获取购买者头像或者浏览者头像 购买者最少3个最多5个头像
        $avatar_info = array();
        $type = 0;
        if ($avatar > 0) {
            if($buy_times >= 3 && $avatar > 1) {
                $avatar_info = $m_group_record->alias('a')
                    ->join('join pigcms_user as b on a.uid=b.uid')
                    ->field('b.avatar')
                    ->group('a.uid')
                    ->where(array('a.mer_id' => $mer_id, 'a.type' => 2))
                    ->order('a.add_time DESC')
                    ->limit(5)
                    ->select();
                $type = 2;
            } elseif ($look_num >= 3 && $avatar > 0) {
                $avatar_info = $m_group_record->alias('a')
                    ->join('join pigcms_user as b on a.uid=b.uid')
                    ->field('b.avatar')
                    ->group('a.uid')
                    ->where(array('a.mer_id' => $mer_id, 'a.type' => 1))
                    ->order('a.add_time DESC')
                    ->limit(5)
                    ->select();
                $type = 1;
            }
        }
        // 处理下没有头像取默认头像
        if (!empty($avatar_info)) {
            foreach ($avatar_info as &$val) {
                if ($val && !$val['avatar']) {
                    $val['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/images/new_my/pic-default.png?t=001';
                }
            }
        }
        return array(
            'type' => $type,
            'look_num' => $look_num,
            'buy_times' => $buy_times,
            'avatar_info' => $avatar_info
        );
    }


    /**统一处理page_num 问题*/
    /**
     * @param int $page_num
     * @param int $type_id
     * @param string $type
     * @return string
     */
    public function group_page_num($page_num=0, $type_id=0, $type="category_all") {
        $url = '';
        if ($type=="category_all") {
            if ($page_num>0) {
                $url = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=category&page_num='.$page_num;
            } else {
                $url = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=category';
            }
        } elseif ($type=="two_category") {
            if ($page_num>0) {
                $page_num_tip = $page_num + 1;
                $url = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=two_category&cat_id='.$type_id.'&page_num='.$page_num_tip;
            } else {
                $url = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=two_category&cat_id='.$type_id;
            }
        } elseif ($type=="group_detail") {
            if ($page_num > 0) {
                $page_num_tip = $page_num + 1;
				$url = get_base_url('pages/group/v1/groupDetail/index', true) . '?group_id=' . $type_id . '&page_num=' . $page_num_tip;
            } else {
                $url = get_base_url('pages/group/v1/groupDetail/index?group_id=' . $type_id, true);
            }
        } elseif ($type=="group_detail_from") {
            if ($page_num > 0) {
                $page_num_tip = $page_num + 1;
                $url = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=detail&group_id='.$type_id.'&page_num='.$page_num_tip.'&from=group';
            } else {
                $url = C('config.site_url').'/wap.php?g=Wap&c=Groupnew&a=detail&group_id='.$type_id.'&from=group';
            }
        }
        return $url;
    }


    /**
     * 添加团购商品购买和浏览数及用户记录
     */
    /**
     * @param $group_id
     * @param int $uid
     * @param int $mer_id
     * @param int $type 1浏览 2购买 3分享
     * @return bool
     */
    public function group_record($group_id, $uid, $mer_id=0, $type=1) {
        if ($uid && $group_id) {
            // 同一用户不重复记入次数
            $info = M('Group_record')->where(array('group_id' => $group_id, 'mer_id' => $mer_id, 'uid' => $uid, 'type' => $type))->find();
            if (!$info) {
                $record = array(
                    'group_id' => $group_id,
                    'mer_id' => $mer_id,
                    'type' => $type,
                    'uid' => $uid,
                    'add_time' => time(),
                );
                M('Group_record')->data($record)->add();
            }
        }
        return true;
    }

    /**
     * 处理状态
     * @param $type
     * @return int|mixed
     */
    private function type_status($type) {
        $type_status = array(
            'topay' => -1,
            'topin' => 4,
            'touse' => 1,
            'isover' => 5,
            'isuse' => 2
        );
        $status = $type_status[$type] ? $type_status[$type] : 0;
        return $status;
    }


    /*得到团购优选订单列表*/
    public function get_new_order_list($uid,$status,$is_wap=false,$page_size=10){
        $condition_where = "`o`.`uid`='$uid' AND `o`.`group_id`=`g`.`group_id`";

        if($status == '0'){
			$condition_where .= " AND (`o`.`status`<=3  OR `o`.`status`=7)"; // 获取全部团购订单  去除了 4 未付款取消订单，5为未完全消费  6，部分退款
        }else if($status == '-1'){
            $condition_where .= " AND `o`.`paid`='0' AND `o`.`status`='0'"; // 待支付
        }else if($status == '1'){
            $condition_where .= " AND `o`.`paid`='1' AND `o`.`status`='0'";    // 已支付未消费、待收货
            $condition_where .= " AND ((`o`.`tuan_type`=0 AND `g`.`deadline_time`>".$_SERVER['REQUEST_TIME'].' AND `g`.`pin_num` <= 0) OR (`o`.`single_buy` = 1)'; // 团购未过期
            $condition_where .= " OR (`s`.`status`='1' AND `o`.`single_buy` != 1 AND `g`.`pin_num` > 0)";   // 拼团得成团且正常状态
            $condition_where .= " OR (`o`.`tuan_type`!=0 && `g`.`pin_num` <= 0))";   // 非拼团
        }else if($status == '2'){
            $condition_where .= " AND `o`.`paid`='1'";    // 待评价、已消费、已收货 已完成
            $condition_where .= " AND `o`.`status` IN (1,2)";
        }else if($status == '3'){
            $condition_where .= " AND `o`.`paid`='1'";    // 已完成
            $condition_where .= " AND `o`.`status`='2'";
        }else if($status == '4'){
            $condition_where .= " AND `o`.`paid`='1' AND `o`.`status`='0' AND `s`.`status`='0' AND `o`.`single_buy` != 1 AND `g`.`pin_num` > 0"; // 待成团
        }else if($status == '5'){
            $condition_where .= " AND  (( `o`.`tuan_type`=0 AND `o`.`paid`='1' AND `o`.`status`='0' AND `g`.`deadline_time`<".$_SERVER['REQUEST_TIME'].')';  // 已过期
            $condition_where .= " OR (`o`.`paid`='1' AND `o`.`status`='0' AND `s`.`status`='2' AND `o`.`single_buy` != 1 AND `g`.`pin_num` > 0))"; // 拼团超过有效期
        }
        $condition_table = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_order'=>'o');

        import('@.ORG.user_page');
        $count = $this->where($condition_where)->table($condition_table)
            ->join(' left join '.C('DB_PREFIX').'merchant as m ON `m`.`mer_id` = `o`.`mer_id` ')
            ->join(' left join '.C('DB_PREFIX').'group_buyer_list as l ON `o`.`order_id` = `l`.`order_id` ')
            ->join(' left join '.C('DB_PREFIX').'group_start as s ON `l`.`fid` = `s`.`id` ')
            ->count();
        $p = new Page($count,$page_size);
        $totalPage = ceil($count/$page_size);
        $page = $_GET['page'];
        if ($page > $totalPage) {
            $order_list = array();
            $return['pagebar'] = $p->show();
            $return['order_list'] = $order_list;
            $return['order_count'] = $count;
            $return['totalPage'] = $totalPage;
            return $return;
        }

        $order_list = $this->field('`o`.*,`g`.`name` as group_name,`g`.`s_name`,`g`.`old_price`,`g`.`pic`,`g`.`intro`,`g`.`end_time`,`g`.`pin_num`,`g`.`deadline_time`,`s`.`status` as pin_status,`m`.`name` as merchant_name, `m`.`pic_info` as merchant_pic_info, `m`.`logo` as merchant_logo')->where($condition_where)->table($condition_table)
            ->join(' left join '.C('DB_PREFIX').'merchant as m ON `m`.`mer_id` = `o`.`mer_id` ')
            ->join(' left join '.C('DB_PREFIX').'group_buyer_list as l ON `o`.`order_id` = `l`.`order_id` ')
            ->join(' left join '.C('DB_PREFIX').'group_start as s ON `l`.`fid` = `s`.`id` ')
            ->order('`o`.`add_time` DESC')->limit($p->firstRow.','.$page_size)->select();

        if(!empty($order_list)){
            $group_image_class = new group_image();
            $merchant_image_class = new merchant_image();
            foreach($order_list as $k=>$v){
                $tmp_pic = explode(';',$v['pic']);
                $order_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic[0],'s');
                $order_list[$k]['url'] = $this->get_group_url($v['group_id'],$is_wap);
                $order_list[$k]['price'] = floatval($v['price']);
                $order_list[$k]['old_price'] = floatval($v['old_price']);
                $order_list[$k]['total_money'] = floatval($v['total_money']);
                if (!$v['merchant_logo']) {
                    $tmp_pic_arr = explode(';', $v['merchant_pic_info']);
                    $list_pic = $merchant_image_class->get_image_by_path($tmp_pic_arr[0]);
                    $order_list[$k]['merchant_logo'] = $list_pic;
                }
                $order_list[$k]['detail_url'] = $this->group_page_num($_GET['page_num'], $v['group_id'], 'group_detail');
                // 处理状态
                if ($v['paid'] == 0 && $v['status'] == 0) {
                    $order_list[$k]['status_info'] = L_('待付款');
                    $order_list[$k]['status_key'] = '1';
                } elseif($v['tuan_type'] == 0 && $v['paid'] == 1 && $v['status'] == 0 && $v['deadline_time'] < $_SERVER['REQUEST_TIME']) {
                    $order_list[$k]['status_info'] = L_('已过期');
                    $order_list[$k]['status_key'] = '2';
                } elseif($v['paid'] == 1 && $v['status'] == 0 && $v['pin_status'] == 2 && $v['single_buy'] != 1 && $v['pin_num'] > 0) {
                    $order_list[$k]['status_info'] = L_('团购超时');
                    $order_list[$k]['status_key'] = '3';
                } elseif($v['paid'] == 1 && $v['status'] == 0 && $v['pin_status'] == 0 && $v['single_buy'] != 1 && $v['pin_num'] > 0) {
                    $order_list[$k]['status_info'] = L_('待成团');
                    $order_list[$k]['status_key'] = '4';
                } elseif($v['paid'] == 1 && $v['status'] == 0) {
                    $order_list[$k]['status_info'] = '待消费/收货';
                    $order_list[$k]['status_key'] = '5';
                } elseif($v['paid'] == 1 && $v['status'] == 1) {
                    $order_list[$k]['status_info'] = '已消费/收货';
                    $order_list[$k]['status_key'] = '6';
                } elseif($v['paid'] == 1 && $v['status'] == 2) {
                    $order_list[$k]['status_info'] = L_('已完成');
                    $order_list[$k]['status_key'] = '7';
                } else {
                    $order_list[$k]['status_info'] = L_('已取消');
                    $order_list[$k]['status_key'] = '7';
                }
            }
        } else {
            $order_list = array();
        }

        $return['pagebar'] = $p->show();
        $return['order_list'] = $order_list;
        $return['order_count'] = $count;
        $return['totalPage'] = $totalPage;

        return $return;
    }



    /**
     * 团购优选商家浏览、购买和分享信息
     * @param $group_id
     * @param $page_size
     * @return array
     */
    public function group_record_list($group_id,$page_size=10) {
        $m_group_record = M('Group_record');
        $record_list = $m_group_record->alias('a')
            ->join('join pigcms_user as b on a.uid=b.uid')
            ->field('a.type, b.uid, b.avatar, b.nickname')
            ->where(array('a.group_id' => $group_id))
            ->group('a.uid')
            ->limit($page_size)
            ->order('a.add_time DESC')
            ->select();
        foreach ($record_list as &$val){
            if (!$val['avatar']) {
                $val['avatar'] = C('config.site_url'). '/static/img/images/nohead.png';
            }else{
                $val['avatar'] = replace_file_domain($val['avatar']);
            }
        }
        return array(
            'record_list' => $record_list
        );
    }
	
	//获取团购店铺信息
	public function get_store_info($group_id){
        if(!$group_id) return array();
        $store_id = M('Group_store')->where(array('group_id'=>$group_id))->select();
		$store_ids = array();
		foreach($store_id as $val){
			$store_ids[] = $val['store_id'];
		}
        return M('Merchant_store')->where(array('store_id'=>array('in',$store_ids)))->field(true)->select();
    }
	
	//判断商品下单库存是否充足【计划任务调用此方法】
    public function check_group_stock($id){

        $db = M('Group_goods_stock_check');
        $where = ['id'=>$id];

        $info = $db->where($where)->find();
		if(!$info){
			return true;
		}
		$goods = json_decode($info['goods'],true);
		
		$cache_key = 'group_stock_' . $goods['group_id'];
		
		$cache_stock = cache_sql($cache_key);
		if($cache_stock===false){//没有设置缓存
			$this->set_group_stock_cache($cache_key, $goods['group_id']);
			$cache_stock = cache_sql($cache_key);
		}
		if($cache_stock=='-1'){
			$save['status'] = 1;
			$db->where($where)->data($save)->save();
			
			return true;
		}
		
		$new_stock = $cache_stock - $goods['num'];//缓存库存-购买量
		if($new_stock < 0){
			$save['status'] = 2;
			$db->where($where)->data($save)->save();
			
			return true;
		}
		
		cache_sql($cache_key, $new_stock, 20*60);//更新缓存
        $save['status'] = 1;
        $db->where($where)->data($save)->save();
    }
	
	function set_group_stock_cache($cache_key, $group_id){
		$group = D('Group')->get_group_by_groupId($group_id);
		
		if ($group['count_num'] > 0) {
			
			$where_sql = "group_id='{$group_id}' AND status<3";
            $group['sale_count'] = M('Group_order')->where($where_sql)->sum('num');
			
			$k_num = $group['count_num'] - $group['sale_count']; //实际库存
			if($k_num < 0){
				$k_num = 0;
			}
		}else{
			$k_num = -1;
		}
		cache_sql($cache_key, $k_num, 20*60);    //由于商品最低可设置10分钟回滚库存，设置为10分钟缓存。
	}

    /*
   * 获取商品积分比例
   */
    public function getGoodsScore($goodsId)
    {
        $scorePercent = self::where(['group_id' => $goodsId])->getField('score_percent');
        if(strpos($scorePercent,'%')){
            $scorePercent = bcdiv($scorePercent,100,2);
        }
        if(empty(floatval($scorePercent))){
            return -1;
        }
        return $scorePercent;
    }
}

?>