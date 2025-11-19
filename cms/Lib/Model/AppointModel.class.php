<?php
class AppointModel extends Model{
    public $appoint_base_url = '';

    public function __construct(){
        if(C('config.system_type') == 'village'){
            $this->appoint_base_url = '/packapp/village/';
        }else{
            $this->appoint_base_url = '/packapp/plat/';
        }
        parent::__construct();
    }

	/*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function wap_get_appoint_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0, $appoint_id=0,$limit=0){
		$now_time = $_SERVER['REQUEST_TIME'];
		//`gc`.`city_id`='".C('config.now_city')."'  AND 
		$condition_where = "`g`.`check_status`='1' AND `g`.`appoint_status`='0' AND `m`.`status`='1' AND `g`.`start_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
		//分类相关
		if(!empty($get_grouplist_catfid)){
			$condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
		}else if(!empty($get_grouplist_catid)){
			$condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
		}

		$join = 'AS g INNER JOIN '.C('DB_PREFIX').'appoint_store AS gc ON g.appoint_id = gc.appoint_id INNER JOIN '.C('DB_PREFIX').'merchant_store AS s ON gc.store_id = s.store_id INNER JOIN '.C('DB_PREFIX').'merchant AS m ON s.mer_id = m.mer_id ';

		// 区域相关
		if($area_id || $circle_id){
			$condition_field  = 'DISTINCT `g`.`appoint_id`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*,s.lat,s.long';
			if ($circle_id) {
				$condition_where .= " AND `gc`.`circle_id`='$circle_id' AND `gc`.`appoint_id`=`g`.`appoint_id`";
			} else {
				$condition_where .= " AND `gc`.`area_id`='$area_id' AND `gc`.`appoint_id`=`g`.`appoint_id`";
			}
			if ($order == 'juli') {
				$condition_field .= ", ROUND( 12756.276 * ASIN( SQRT( POW( SIN(( {$lat} * 0.017453294 - `s`.`lat` * 0.017453294 ) / 2 ), 2 ) + COS( {$lat} * 0.017453294 ) * COS(`s`.`lat` * 0.017453294) * POW( SIN(( {$long} * 0.017453294 - `s`.`long` * 0.017453294 ) / 2 ), 2 ))) * 1000 ) AS juli";
			}
		}else{
			$condition_field  = ' `m`.`name` AS `merchant_name`,`g`.*,`m`.*,s.lat,s.long';
			if ($order == 'juli') {
				$condition_field .= ", ROUND( 12756.276 * ASIN( SQRT( POW( SIN(( {$lat} * 0.017453294 - `s`.`lat` * 0.017453294 ) / 2 ), 2 ) + COS( {$lat} * 0.017453294 ) * COS(`s`.`lat` * 0.017453294) * POW( SIN(( {$long} * 0.017453294 - `s`.`long` * 0.017453294 ) / 2 ), 2 ))) * 1000 ) AS juli";
			}
		}

		if ($appoint_id) {
			$condition_where .= ' AND g.appoint_id in ('.implode(',',$appoint_id).')';
		}
		//排序相关
		switch($order){
			case 'price':
				$order = '`g`.`appoint_price` ASC,`g`.`appoint_id` DESC';
				break;
			case 'priceDesc':
				$order = '`g`.`appoint_price` DESC,`g`.`appoint_id` DESC';
				break;
			case 'start':
				$order = '`g`.`create_time` DESC,`g`.`appoint_id` DESC';
				break;
			case 'juli':
				$order = 'juli asc,`g`.`appoint_id` DESC';
				break;
            case 'appointNum':
                $order = '`g`.`appoint_sum` DESC,`g`.`appoint_id` DESC';
                break;
            case 'sale_max':
                $order = '`g`.`appoint_sum` DESC,`g`.`appoint_id` DESC';
                break;
            case 'evaluate_max':
                $order = '`g`.`score_mean` DESC,`g`.`appoint_id` DESC';
                break;
            case 'price_asc':
                $order = '`g`.`appoint_price` ASC,`g`.`appoint_id` DESC';
                break;
			default:
				$order = '`g`.`sort` DESC,`g`.`appoint_id` DESC';
		}

		import('@.ORG.wap_group_page');
		$count_group = M('Appoint')->join($join)->where($condition_where)->count('DISTINCT `g`.`appoint_id`');
		$pageSize = C('config.appoint_page_row');
		if ($limit) {
			$pageSize = $limit;
		}
		$p = new Page($count_group,$pageSize,C('config.appoint_page_val'));
		$group_list = M('Appoint')
            ->join($join)
            ->field($condition_field)
            ->where($condition_where)
            ->group('`g`.`appoint_id`')
            ->order($order)
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
		$tmp_key =array();
		$return['pagebar'] = $count_group>0?$p->show():'';
        $categoryAutotrophic = [];
		if($group_list){
			$group_image_class = new appoint_image();
			foreach($group_list as $k=>$v){
			    if(isset($categoryAutotrophic[$v['cat_id']])){
                    $group_list[$k]['is_autotrophic'] = $categoryAutotrophic[$v['cat_id']];
                } else {
			        $autotrophic = M('Appoint_category')->where(['cat_id' => $v['cat_id']])->field('is_autotrophic')->find();
			        if(!empty($autotrophic)){
                        $categoryAutotrophic[$v['cat_id']] = intval($autotrophic['is_autotrophic']);
                    }
                    $group_list[$k]['is_autotrophic'] = intval($autotrophic['is_autotrophic']);
                }
				if(in_array($v['appoint_id'],$tmp_key)){
					//unset($group_list[$k]);
				}else{
					$tmp_key[] = $v['appoint_id'];
					$tmp_pic_arr = explode(';',$v['pic']);
					$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
					$group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],true);
					$group_list[$k]['payment_money'] = floatval($v['payment_money']);
					$group_list[$k]['appoint_sum'] = intval($v['appoint_sum']);
					if(!$v['lat'] || !$lat){
						$v['juli'] = 0;
						$group_list[$k]['juli'] = 0;
					}else{
						!$v['juli'] && $v['juli'] = getDistance($v['lat'], $v['long'], $lat, $long);
						$group_list[$k]['range'] = getRange($v['juli'],false);
						$group_list[$k]['juli'] = getRange($v['juli'],false);
					}
					$group_list[$k]['lat'] =$v['lat'];
					$group_list[$k]['long'] =$v['long'];
					$tmp[] = $group_list[$k];
				}
			}
		}
		$return['group_list'] = $tmp;
		$return['totalPage'] = $count_group>0?$p->totalPage:'';
		$return['meal_count'] = $count_group;
		return  $return;
	}


    /*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
    public function get_appoint_list_new($where_data){
        $get_grouplist_catid = $where_data['get_grouplist_catid'];
        $get_grouplist_catfid = $where_data['get_grouplist_catfid'];
        $area_id = $where_data['area_id'];
        $order = $where_data['order'];
        $lat = $where_data['lat'] ? $where_data['lat'] : 0;
        $long = $where_data['long'] ? $where_data['long'] : 0;
		$circle_id = $where_data['circle_id'] ? $where_data['circle_id'] : 0;
		
		if (!$_GET['page'] && !$_POST['page']) {
			$_GET['page'] = $_POST['page'] = 1;
		} elseif(!$_GET['page'] && $_POST['page']) {
			$_GET['page'] = $_POST['page'];
		}

        $database_appoint_category = D('Appoint_category');
        $cat_id_arr = array();
        if (isset($where_data['is_autotrophic'])) {
            // 考虑到只有二级分类 才有 是否平台自营 0，商家入驻 1，平台自营 2，第三方入驻
            $appoint_category = $database_appoint_category->where(array('is_autotrophic' => $where_data['is_autotrophic'], 'fid' => array('gt', 0)))->field('cat_id')->select();
            if ($appoint_category) {
                foreach ($appoint_category as $val) {
                    $cat_id_arr[] = $val['cat_id'];
                }
            }
        }


        $now_time = $_SERVER['REQUEST_TIME'];
        $now_city = C('config.now_city');
        $condition_where = "(`gc`.`city_id`='".$now_city."' OR `m`.`city_id`='".$now_city."')  AND `g`.`check_status`='1' AND `g`.`appoint_status`='0' AND `m`.`status`='1' AND `g`.`start_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
        $condition_where .= ' AND s.status = 1 ';
        //分类相关
		if(!empty($get_grouplist_catid)){
			$condition_where .= " AND `g`.`cat_id`='$get_grouplist_catid'";
		}elseif(!empty($get_grouplist_catfid)){
            $condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
        }
        if (!empty($cat_id_arr)) {
            $cat_ids = implode(',',$cat_id_arr);
            $condition_where .= " AND `g`.`cat_id` in ({$cat_ids}) ";
        }
        if (isset($where_data['payment_status'])) {
            $payment_status_str = implode(',',$where_data['payment_status']);
            $condition_where .= " AND `g`.`payment_status` in ({$payment_status_str}) ";
        }
        if (isset($where_data['price_min'])) {
            $condition_where .= " AND `g`.`appoint_price` >= ({$where_data['price_min']}) ";
        }
        if (isset($where_data['price_max'])) {
            $condition_where .= " AND `g`.`appoint_price` <= ({$where_data['price_max']}) ";
        }

        if (isset($where_data['keywords'])) {
            $condition_where .= " AND `g`.`appoint_name` like '%{$where_data['keywords']}%' ";
        }


        $join = 'AS g LEFT JOIN '.C('DB_PREFIX').'appoint_store AS gc ON g.appoint_id = gc.appoint_id LEFT JOIN '.C('DB_PREFIX').'merchant_store AS s ON gc.store_id = s.store_id LEFT JOIN '.C('DB_PREFIX').'merchant AS m ON g.mer_id = m.mer_id ';

        // 区域相关
        if($area_id || $circle_id){
            $condition_field  = '`g`.`appoint_id`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*,s.lat,s.long';
            if ($circle_id) {
                $condition_where .= " AND `gc`.`circle_id`='$circle_id' AND `gc`.`appoint_id`=`g`.`appoint_id`";
            } else {
                $condition_where .= " AND (`gc`.`area_id`='$area_id' OR (`gc`.`area_id` IS NULL AND `m`.`area_id`='$area_id')) AND `gc`.`appoint_id`=`g`.`appoint_id`";
            }
            if ($order == 'juli') {
                $condition_field .= ", ROUND( 12756.276 * ASIN( SQRT( POW( SIN(( {$lat} * 0.017453294 - `s`.`lat` * 0.017453294 ) / 2 ), 2 ) + COS( {$lat} * 0.017453294 ) * COS(`s`.`lat` * 0.017453294) * POW( SIN(( {$long} * 0.017453294 - `s`.`long` * 0.017453294 ) / 2 ), 2 ))) * 1000 ) AS juli";
            }
        }else{
            $condition_field  = ' `m`.`name` AS `merchant_name`,`g`.*,`m`.*,s.lat,s.long';
            if ($order == 'juli') {
                $condition_field .= ", ROUND( 12756.276 * ASIN( SQRT( POW( SIN(( {$lat} * 0.017453294 - `s`.`lat` * 0.017453294 ) / 2 ), 2 ) + COS( {$lat} * 0.017453294 ) * COS(`s`.`lat` * 0.017453294) * POW( SIN(( {$long} * 0.017453294 - `s`.`long` * 0.017453294 ) / 2 ), 2 ))) * 1000 ) AS juli";
            }
        }
        //排序相关
        switch($order){
            case 'price':
                $order = '`g`.`appoint_price` ASC,`g`.`appoint_id` DESC';
                break;
            case 'priceDesc':
                $order = '`g`.`appoint_price` DESC,`g`.`appoint_id` DESC';
                break;
            case 'start':
                $order = '`g`.`create_time` DESC,`g`.`appoint_id` DESC';
                break;
            case 'juli':
                $order = 'juli asc,`g`.`appoint_id` DESC';
                break;
            case 'appointNum':
                $order = '`g`.`appoint_sum` DESC,`g`.`appoint_id` DESC';
                break;
            case 'sale_max':
                $order = '`g`.`appoint_sum` DESC,`g`.`appoint_id` DESC';
                break;
            case 'evaluate_max':
                $order = '`g`.`score_mean` DESC,`g`.`appoint_id` DESC';
                break;
            case 'price_asc':
                $order = '`g`.`appoint_price` ASC,`g`.`appoint_id` DESC';
                break;
            default:
                $order = '`g`.`sort` DESC,`g`.`appoint_id` DESC';
        }

        import('@.ORG.wap_group_page');
        $count_group_list = M('Appoint')->join($join)->where($condition_where)->group('`g`.`appoint_id`')->field('`g`.`appoint_id`')->select();
		$count_group = count($count_group_list);
        $p = new Page($count_group,C('config.appoint_page_row'),'page');
        $group_list = M('Appoint')->join($join)->field($condition_field)->group('`g`.`appoint_id`')->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();


		$tmp_key =array();
        $return['pagebar'] = $count_group>0?$p->show():'';
		$tmp = array();
        if($group_list){
            $group_image_class = new appoint_image();
            $database_appoint_comment = D('Appoint_comment');
            foreach($group_list as $k=>$v){
                if(in_array($v['appoint_id'],$tmp_key)){
                    //unset($group_list[$k]);
                }else{
                    $cat_info=$database_appoint_category->where(array('cat_id' => $v['cat_id']))->field('is_autotrophic,cue_field,wap_title,wap_content')->find();
                    if ($cat_info && $cat_info['is_autotrophic']) {
                        $cuefield = unserialize($cat_info['cue_field']);
                        foreach ($cuefield as $val) {
                            $sort[] = $val['sort'];
                        }
                        array_multisort($sort, SORT_DESC, $cuefield);
                        $group_list[$k]['wap_title'] = unserialize($cat_info['wap_title']);
                        $group_list[$k]['wap_content'] = unserialize($cat_info['wap_content']);
                        $group_list[$k]['formData'] = $cuefield;
                    }

                    $where_comment['appoint_id'] = $v['appoint_id'];
                    $where_comment['status'] = 1;
                    $comment = $database_appoint_comment->where($where_comment)->field('SUM(score) as total_score, COUNT(id) as total_num')->find();
                    if ($comment && floatval($comment['total_score'])>0 && floatval($comment['total_num'])>0) {
                        $group_list[$k]['score_mean'] = bcdiv(floatval($comment['total_score']),floatval($comment['total_num']),1);
                    } else {
                        $group_list[$k]['score_mean'] = '';
                    }


                    $group_list[$k]['is_autotrophic'] = $cat_info['is_autotrophic'];
                    $tmp_key[] = $v['appoint_id'];
                    $tmp_pic_arr = explode(';',$v['pic']);
                    $group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
                    $group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],true);
                    $group_list[$k]['payment_money'] = floatval($v['payment_money']);
                    $group_list[$k]['appoint_sum'] = intval($v['appoint_sum']);
                    if(!$v['lat'] || !$lat){
                        $v['juli'] = 0;
                        $group_list[$k]['juli'] = 0;
                    }else{
                        !$v['juli'] && $group_list[$k]['juli'] = getDistance($v['lat'], $v['long'], $lat, $long);
                        $group_list[$k]['range'] = getRange($v['juli'],false);
                        $group_list[$k]['juli'] = getRange($v['juli'],false);
                    }
                    $group_list[$k]['lat'] =$v['lat'];
                    $group_list[$k]['long'] =$v['long'];
                    $tmp[] = $group_list[$k];
                }
            }
        }
        $return['list'] = $tmp;
        $return['totalPage'] = $count_group>0?$p->totalPage:0;
        $return['count'] = $count_group?$count_group:0;
        return  $return;
    }



    // wap用
	public function get_list_by_search($w, $order = 'store_id',$is_wap = false){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_table = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'appoint_category'=>'c');
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `g`.`check_status`='1' AND `g`.`appoint_status`='0' AND `m`.`status`='1' AND `g`.`start_time`<'$now_time' AND `g`.`end_time`>'$now_time'";

		//排序相关
		switch($order){
			case 'price':
				$order = '`g`.`appoint_price` ASC,`g`.`appoint_id` DESC';
				break;
			case 'priceDesc':
				$order = '`g`.`appoint_price` DESC,`g`.`appoint_id` DESC';
				break;
			case 'start':
				$order = '`g`.`create_time` DESC,`g`.`appoint_id` DESC';
				break;
			case 'juli':
				$order = 'juli asc,`g`.`appoint_id` DESC';
				break;
			case 'appointNum':
				$order = '`g`.`appoint_sum` DESC,`g`.`appoint_id` DESC';
				break;
			default:
				$order = '`g`.`sort` DESC,`g`.`appoint_id` DESC';
		}
		if($w){
			$condition_where .= " AND `g`.`appoint_name` like '%$w%' ";
		}
		if(empty($is_wap)){
			import('@.ORG.group_page');
		}else{
			import('@.ORG.wap_group_search_page');
		}

       	$condition_where .= ' AND `c`.`is_autotrophic`=0 AND `g`.`cat_id` = `c`.`cat_id`';

		$count_group = D('')->table($condition_table)->where($condition_where)->count('DISTINCT `g`.`appoint_id`');
		$p = new Page($count_group,C('config.appoint_page_row'),C('config.appoint_page_val'));
		$group_list = D('')->table($condition_table)->where($condition_where)->order($order)->group('`g`.`appoint_id`, `m`.`mer_id`')->limit($p->firstRow.','.$p->listRows)->select();
		$return['pagebar'] = $count_group>0?$p->show():'';
		if(C('config.open_extra_price')==1){
			$open_extra_price = 1;
			$extra_price_name = C('config.extra_price_alias_name');
		}
		if(count($group_list)>0){
			$group_image_class = new appoint_image();
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],true);
				$group_list[$k]['payment_money'] = floatval($v['payment_money']);
				$group_list[$k]['appoint_sum'] = intval($v['appoint_sum']);
				$group_list[$k]['search_name'] = str_replace($w, '<font color="#06c1ae">' . $w. '</font>', $v['appoint_name']);
				if($open_extra_price==1&&$v['extra_pay_price']>0){
					$group_list[$k]['extra_pay_price'] = '+'.floatval($v['extra_pay_price']).$extra_price_name;
				}else{
					$group_list[$k]['extra_pay_price']='';
				}
			}
		}
		$return['group_list'] = $count_group>0?$group_list:'';
		$return['totalPage'] = $count_group>0?$p->totalPage:'';
		$return['meal_count'] = $count_group;
		$return['group_count'] = $count_group;
		$return['store_count'] = $count_group;

		return $return;
	}

	/*得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function get_appoint_list_by_catid($get_appointlist_catid,$get_grouplist_catfid,$cat_url,$area_id,$circle_id,$order,$attrs,$category_cat_field){

		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`m`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`check_status`='1' AND `g`.`appoint_status`='0' AND `m`.`status`='1' AND `g`.`start_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
		//分类相关
		if(!empty($get_grouplist_catfid)){
			$condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
		}else if(!empty($get_appointlist_catid)){
			$condition_where .= " AND `g`.`cat_id`='$get_appointlist_catid'";
		}

		//区域或商圈
		if($circle_id){
			$condition_where .= " AND `gc`.`circle_id`='$circle_id' AND `gc`.`appoint_id`=`g`.`appoint_id`";
			$condition_table  = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc');
			$condition_field  = 'DISTINCT `g`.`appoint_id`,`g`.`appoint_name` AS `appoint_name`,`g`.`hits` AS `appoint_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		}else if($area_id){
			$condition_where .= " AND `gc`.`area_id`='$area_id' AND `gc`.`group_id`=`g`.`group_id`";
			$condition_table  = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'group_store'=>'gc');
			$condition_field  = 'DISTINCT `g`.`group_id`,`g`.`appoint_name` AS `appoint_name`,`g`.`hits` AS `appoint_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		}else{
			$condition_table = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m');
			$condition_field  = '`g`.`appoint_name` AS `appoint_name`,`g`.`hits` AS `appoint_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
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

		//排序相关
		switch($order){
			case 'hit-asc':
				$order = '`appoint_hits` ASC,`g`.`appoint_id` DESC';
				break;
			case 'hit-desc':
				$order = '`appoint_hits` DESC,`g`.`appoint_id` DESC';
				break;
			case 'time':
				$order = '`g`.`create_time` DESC,`g`.`appoint_id` DESC';
				break;
			case 'hot':
				$order = '`g`.`appoint_sum` DESC,`g`.`appoint_id` DESC';
				break;
			default:
				$order = '`g`.`sort` DESC,`g`.`appoint_id` DESC';
		}

		import('@.ORG.group_page');
		$count_group = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_group,C('config.appoint_page_row'),C('config.appoint_page_val'));
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
//echo D('')->_sql();	exit;
		$return['pagebar'] = $p->show();

		if($group_list){
			$group_image_class = new appoint_image();
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id']);
				$group_list[$k]['payment_money'] = floatval($v['payment_money']);
				$group_list[$k]['appoint_sum'] = intval($v['appoint_sum']);
			}
		}
		$return['group_list'] = $group_list;

		return $return;
	}


	public function new_get_appoint_list_by_catid($get_appointlist_catid,$get_grouplist_catfid,$cat_url,$area_id,$circle_id,$order,$attrs,$category_cat_field){

		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`gc`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`check_status`='1' AND `g`.`appoint_status`='0' AND `m`.`status`='1' AND `g`.`start_time`<'$now_time' AND `g`.`end_time`>'$now_time'";
		//分类相关
		if(!empty($get_grouplist_catfid)){
			$condition_where .= " AND `g`.`cat_fid`='$get_grouplist_catfid'";
		}else if(!empty($get_appointlist_catid)){
			$condition_where .= " AND `g`.`cat_id`='$get_appointlist_catid'";
		}

		//区域或商圈
		if($circle_id){
			$condition_where .= " AND `gc`.`circle_id`='$circle_id' AND `gc`.`appoint_id`=`g`.`appoint_id`";
			$condition_table  = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'appoint_store'=>'gc');
			$condition_field  = 'DISTINCT `g`.`appoint_id`,`g`.`appoint_name` AS `appoint_name`,`g`.`hits` AS `appoint_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		}else if($area_id){
			$condition_where .= " AND `gc`.`area_id`='$area_id' AND `gc`.`appoint_id`=`g`.`appoint_id`";
			$condition_table  = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'appoint_store'=>'gc');
			$condition_field  = 'DISTINCT `g`.`appoint_name` AS `appoint_name`,`g`.`hits` AS `appoint_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		}else{
			$condition_where .= "  AND `gc`.`appoint_id`=`g`.`appoint_id`";
			$condition_table = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'appoint_store'=>'gc');
			$condition_field  = 'DISTINCT `g`.`appoint_name` AS `appoint_name`,`g`.`hits` AS `appoint_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
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

		//排序相关
		switch($order){
			case 'hit-asc':
				$order = '`appoint_hits` ASC,`g`.`appoint_id` DESC';
				break;
			case 'hit-desc':
				$order = '`appoint_hits` DESC,`g`.`appoint_id` DESC';
				break;
			case 'time':
				$order = '`g`.`create_time` DESC,`g`.`appoint_id` DESC';
				break;
			case 'hot':
				$order = '`g`.`appoint_sum` DESC,`g`.`appoint_id` DESC';
				break;
			default:
				$order = '`g`.`sort` DESC,`g`.`appoint_id` DESC';
		}

		import('@.ORG.group_page');
		$count_group = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_group,C('config.appoint_page_row'),C('config.appoint_page_val'));
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		$return['pagebar'] = $p->show();
		if($group_list){
			$group_image_class = new appoint_image();
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id']);
				$group_list[$k]['payment_money'] = floatval($v['payment_money']);
				$group_list[$k]['appoint_sum'] = intval($v['appoint_sum']);
			}
		}
		$return['group_list'] = $group_list;

		return $return;
	}

	public function get_order_list($uid, $status, $is_wap=false){
		$database_appoint = D('Appoint');
		$database_appoint_order = D('Appoint_order');
		$condition_where['uid'] = $uid;
		if($status == '1'){
			$condition_where['service_status'] = '0';
		}
		if($status == '2'){
			$condition_where['service_status'] = '1';
		}
		if($status == '3'){
			$condition_where['service_status'] = '2';
		}

		$count = $database_appoint_order->field(true)->where($condition_where)->count();
		
		import('@.ORG.user_page');
		$p = new Page($count, 10);

		$order_list = $database_appoint_order->field(true)->where($condition_where)->order('`order_time` DESC, `order_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

		if(!empty($order_list)){
			$appoint_image_class = new appoint_image();
			foreach($order_list as $k=>$v){
				$where['appoint_id'] = $v['appoint_id'];
				$appoint_info = $database_appoint->field('`pic`, `appoint_name`, `appoint_price`, `payment_status`')->where($where)->find();
				$tmp_pic_arr = explode(';', $appoint_info['pic']);
				$order_list[$k]['list_pic'] = $appoint_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$order_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],true);
				$order_list[$k]['payment_money'] = floatval($v['payment_money']);
				$order_list[$k]['order_url'] = $database_appoint_order->get_order_url($v['order_id'], true);
				$order_list[$k]['appoint_name'] = $appoint_info['appoint_name'];
				$order_list[$k]['payment_status'] = $appoint_info['payment_status'];
				$order_list[$k]['appoint_price'] = floatval($appoint_info['appoint_price']);
			}
		}
		return array('order_list' => $order_list, 'pagebar' => $p->show(), 'page' => ceil($count/10),'count' => $count);
		// return $order_list;
	}

	public function wap_get_order_list($uid, $status = '0',$where=[]){
		$database_appoint = D('Appoint');
		$database_appoint_order = D('Appoint_order');
		$condition_where['uid'] = $uid;
		$condition_where['paid'] = array('neq', 4);
		if($status == -1){
			$condition_where['service_status'] = 0;
		}else if($status == 1){
			$condition_where['service_status'] = 1;
		}else if($status == 2){
			$condition_where['service_status'] = 2;
		}
		if (isset($where['is_del'])) {
            $condition_where['is_del'] = $where['is_del'];
        }

		$order_list = $database_appoint_order->where($condition_where)->order('`order_time` DESC')->select();

		if(!empty($order_list)){
			$appoint_image_class = new appoint_image();
			foreach($order_list as $k=>$v){
				if($v['appoint_id'] == 0){
					if($v['cat_id']){
						$appoint_cate = D('Appoint_category')->where(array('cat_id'=>$v['cat_id']))->find();
						$order_list[$k]['list_pic'] = C('config.site_url').'/upload/system/'.$appoint_cate['cat_pic'];
						$order_list[$k]['url'] = C('config.site_url').'/wap.php?g=Wap&c=Appoint&a=two_category&cat_id='.$v['cat_id'];
						$order_list[$k]['payment_money'] = floatval($v['payment_money']);
						$order_list[$k]['order_url'] = $database_appoint_order->get_order_url($v['order_id'], true);
						$order_list[$k]['appoint_name'] = $appoint_cate['cat_name'];
						$order_list[$k]['payment_status'] = 0;

					}
				}else{
					$where['appoint_id'] = $v['appoint_id'];
					$appoint_info = $database_appoint->field('`pic`, `appoint_name`, `appoint_price`, `payment_status`, `is_appoint_price`')->where($where)->find();
					$tmp_pic_arr = explode(';', $appoint_info['pic']);
					$order_list[$k]['list_pic'] = $appoint_image_class->get_image_by_path($tmp_pic_arr[0],'s');
					$order_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],true);
					$order_list[$k]['payment_money'] = floatval($v['payment_money']);
					$order_list[$k]['order_url'] = $database_appoint_order->get_order_url($v['order_id'], true);
					$order_list[$k]['appoint_name'] = $appoint_info['appoint_name'];
                    $order_list[$k]['payment_status'] = $appoint_info['payment_status'];
                    $order_list[$k]['is_appoint_price'] = $appoint_info['is_appoint_price'];
				}
			}
		}

		return $order_list;
	}


	public function wap_order_list($where){
		$database_appoint = D('Appoint');
		$database_appoint_order = D('Appoint_order');
		$where['paid'] = array('neq', 4);
		$order_list = $database_appoint_order->field(true)->where($where)->order('`order_time` DESC')->select();
		if(!empty($order_list)){
			$appoint_image_class = new appoint_image();
			foreach($order_list as $k=>$v){
				if($v['appoint_id'] == 0){

					if($v['cat_id']){
						$appoint_cate = D('Appoint_category')->where(array('cat_id'=>$v['cat_id']))->find();
						$order_list[$k]['list_pic'] = C('config.site_url').'/upload/system/'.$appoint_cate['cat_pic'];
						$order_list[$k]['url'] = C('config.site_url').'/wap.php?g=Wap&c=Appoint&a=two_category&cat_id='.$v['cat_id'];
						$order_list[$k]['payment_money'] = floatval($v['payment_money']);
						$order_list[$k]['order_url'] = $database_appoint_order->get_order_url($v['order_id'], true);
						$order_list[$k]['appoint_name'] = $appoint_cate['cat_name'];
						$order_list[$k]['payment_status'] = $appoint_info['payment_status'];

					}
				}else{
					$where['appoint_id'] = $v['appoint_id'];
					$appoint_info = $database_appoint->field('`pic`, `appoint_name`, `appoint_price`, `payment_status`')->where($where)->find();
					$tmp_pic_arr = explode(';', $appoint_info['pic']);
					$order_list[$k]['list_pic'] = $appoint_image_class->get_image_by_path($tmp_pic_arr[0],'s');
					$order_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],true);
					$order_list[$k]['payment_money'] = floatval($v['payment_money']);
					$order_list[$k]['order_url'] = $database_appoint_order->get_order_url($v['order_id'], true);
					$order_list[$k]['appoint_name'] = $appoint_info['appoint_name'];
					$order_list[$k]['payment_status'] = $appoint_info['payment_status'];
				}
			}
		}

		return $order_list;
	}

    /**
     * 通过appoint_id获得该预约的相关信息
     * @param $appoint_id
     * @param string $other
     * @param bool $is_statistics // 是否统计商家总数-预约数  总计预约次数  总评价均分
     * @return mixed
     */
	public function get_appoint_by_appointId($appoint_id,$other='',$is_statistics=false,$store_id=0){
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`appoint_status`='0' AND `g`.`check_status`='1' AND `g`.`appoint_id`='$appoint_id'";
		$condition_table = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m');
		$condition_field  = '`g`.`appoint_name` AS `appoint_name`,`g`.`hits` AS `appoint_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
		$now_group = D('')->field($condition_field)->table($condition_table)->where($condition_where)->find();
		if(!empty($now_group)){
			$group_image_class = new appoint_image();
			$now_group['url'] = C('config.site_url').'/appoint/'.$now_group['appoint_id'].'.html';
			$now_group['payment_money'] = floatval($now_group['payment_money']);
			$now_group['appoint_sum'] = intval($now_group['appoint_sum']);
			$now_group['buy_url'] = C('config.site_url').'/appoint/order/'.$now_group['appoint_id'].'.html';
			$now_group['all_pic'] = $group_image_class->get_allImage_by_path($now_group['pic']);

			$now_group['store_list'] = D('Appoint_store')->get_storelist_by_appointId($now_group['appoint_id'], $store_id);
			if(count($now_group['store_list']) == 1){
				$now_group['store_list'][0]['area'] = D('Area')->get_area_by_areaId($now_group['store_list'][0]['area_id']);
				$now_group['store_list'][0]['circle'] = D('Area')->get_area_by_areaId($now_group['store_list'][0]['circle_id']);
			}
			if($other){
				$condition_group['appoint_id'] = $appoint_id;
				switch($other){
					case 'hits-setInc':
						$this->where($condition_group)->setInc('hits');
						break;
				}
			}
			if ($is_statistics) {
                $now_group['merchant_statistics'] = $this->merchant_appoint_statistics_new($now_group['mer_id']);
                $now_group['appoint_favorable_rate'] = $this->appoint_comment_statistic($now_group['mer_id'],$appoint_id);
            }
			$d_area = M('Area');
			$provice_name = $d_area->where(['area_id'=>$now_group['province_id']])->getField('area_name');
			$city_name = $d_area->where(['area_id'=>$now_group['city_id']])->getField('area_name');
			$area_name = $d_area->where(['area_id'=>$now_group['area_id']])->getField('area_name');
            $street_name = D('Area_street')->where(['area_id'=>$now_group['street_id']])->getField('area_name');
			$now_group['merchant_address'] = $provice_name .' '.$city_name.' '.$area_name.' '.$street_name.' '.$now_group['address'];
			$now_group['logo'] = replace_file_domain($now_group['logo']);
            $now_group['payment_status_txt'] = $now_group['payment_status']?'定金':'一口价';
            $now_group['appoint_type_txt'] = $now_group['appoint_type']?'上门服务':'到店服务';

            $AppointProduct = D('Appoint_product')->where(['appoint_id' => $appoint_id])->select();
            if($AppointProduct && min(array_column($AppointProduct,'price'))){
                $now_group['is_appoint_price'] = 1;
                $now_group['payment_status_txt'] = '';
            }
		}

		return $now_group;
	}

	public function merchant_appoint_statistics($mer_id) {
	    // 查询商家所拥有的预约数量
        $appoint_num = $this->where(array('mer_id'=>$mer_id,'appoint_status'=>0,'check_status'=>1))->count();
        if (!$appoint_num) {
            $appoint_num = 0;
        }
        $arr['appoint_num'] = $appoint_num;
        // 查询商家所拥有的总预约数
        $appoint_sum = $this->where(array('mer_id'=>$mer_id,'appoint_status'=>0,'check_status'=>1))->sum('appoint_sum');
        if (!$appoint_sum) {
            $appoint_sum = 0;
        }
        $arr['appoint_sum'] = $appoint_sum;
        // 查询商家评分计算平均分
        $comment = M('Appoint_comment')->where(array('mer_id'=>$mer_id,'status'=>1))->field('count(id) as comment_num, sum(score) as score_all')->find();
        if (empty($comment)) {
            $score_all = 5;
        } else {
            $score_all = $comment['score_all']?$comment['score_all']:0;
            $comment_num = $comment['comment_num']?$comment['comment_num']:0;
            if ($comment_num<=0) {
                $score_all = 5;
            } else {
                $score_all = bcdiv($score_all,$comment_num,1);
            }
        }
        $arr['score_all'] = $score_all;

        $favorable_rate = getFormatNumber($score_all/5 * 100);
        $arr['favorable_rate'] = $favorable_rate;

        return $arr;
    }

    /**
     * 新版好评率统计
     * @param $mer_id
     */
    public function merchant_appoint_statistics_new($mer_id){
        // 查询商家所拥有的预约数量
        $appoint_num = $this->where(array('mer_id'=>$mer_id,'appoint_status'=>0,'check_status'=>1))->count();
        if (!$appoint_num) {
            $appoint_num = 0;
        }
        $arr['appoint_num'] = $appoint_num;
        // 查询商家所拥有的总预约数
        $appoint_sum = $this->where(array('mer_id'=>$mer_id,'appoint_status'=>0,'check_status'=>1))->sum('appoint_sum');
        if (!$appoint_sum) {
            $appoint_sum = 0;
        }
        $arr['appoint_sum'] = $appoint_sum;
        
        $favorable_rate = $this->appoint_comment_statistic($mer_id);
        $arr['favorable_rate'] = $favorable_rate;

        return $arr;
    }

    /**
     * 统计预约服务好评率
     * @param $mer_id
     * @param int $appoint_id
     * @return float|int
     */
    public function appoint_comment_statistic($mer_id,$appoint_id=0){
        $where = array(
            'mer_id'=>$mer_id,
            'status'=>1,
            'average_score'=>array('lt',4),
        );
        if($appoint_id){
            $where['appoint_id'] = $appoint_id;
        }
        // 查询商家非好评数
        $comment_bad = M('Appoint_comment')->where($where)->count();

        $favorable_rate = 100;
        if($comment_bad){
            unset($where['average_score']);
            //查询商家总评论数
            $comment_total = M('Appoint_comment')->where($where)->count();
            $statistics = ($comment_total-$comment_bad)/$comment_total;
            $favorable_rate = getFormatNumber($statistics * 100);
        }
        return $favorable_rate;
    }


    /**
     * 通过appoint_id获得该预约的相关信息
     * @param int $appoint_id
     * @param string $other
     */
    public function get_appoint_info($appoint_id,$other=''){
        $condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `g`.`appoint_id`='$appoint_id'";
        $condition_table = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m');
        $condition_field  = '`g`.`appoint_name` AS `appoint_name`,`g`.`hits` AS `appoint_hits`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*';
        $now_group = D('')->field($condition_field)->table($condition_table)->where($condition_where)->find();
        if(!empty($now_group)){
            $group_image_class = new appoint_image();
            $now_group['payment_money'] = floatval($now_group['payment_money']);
            $now_group['appoint_sum'] = intval($now_group['appoint_sum']);
            $now_group['buy_url'] = C('config.site_url').'/appoint/order/'.$now_group['appoint_id'].'.html';
            $now_group['all_pic'] = $group_image_class->get_allImage_by_path($now_group['pic']);

            $now_group['store_list'] = D('Appoint_store')->get_storelist_by_appointId($now_group['appoint_id']);
            if(count($now_group['store_list']) == 1){
                $now_group['store_list'][0]['area'] = D('Area')->get_area_by_areaId($now_group['store_list'][0]['area_id']);
                $now_group['store_list'][0]['circle'] = D('Area')->get_area_by_areaId($now_group['store_list'][0]['circle_id']);
            }
            if($other){
                $condition_group['appoint_id'] = $appoint_id;
                switch($other){
                    case 'hits-setInc':
                        $this->where($condition_group)->setInc('hits');
                        break;
                }
            }
        }

        return $now_group;
    }


	public function get_appointlist_by_MerchantId($mer_id,$limit=0,$is_wap=false,$appoint_id=0){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`appoint_status`='0' AND `g`.`check_status`='1'  AND `g`.`start_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`mer_id`='$mer_id'";
		if(!empty($appoint_id)){
			$condition_where .= " AND `g`.`appoint_id`='$appoint_id' AND is_autotrophic=0";
		}
		$group_list = D('')->field('`g`.`appoint_name` AS `appoint_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*')->table(array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m'))->where($condition_where)->order('`g`.`sort` DESC,`g`.`appoint_id` DESC')->limit($limit)->select();
		if($group_list){
			$group_image_class = new appoint_image();
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],$is_wap);
				$group_list[$k]['payment_money'] = floatval($v['payment_money']);
				$group_list[$k]['appoint_sum'] = intval($v['appoint_sum']);
			}
		}
		return $group_list;
	}

	public function get_appointlist_by_StoreId($store_id,$is_wap=false,$socre=1,$order='`store_sort` desc`'){
		$now_time = $_SERVER['REQUEST_TIME'];
		$appoint_id	=	M('Appoint_store')->field(array('appoint_id'))->where(array('store_id'=>$store_id))->select();
		$appointArr	=	array();
		foreach($appoint_id as $k=>$v){
			//$appoint	=	M('Appoint')->field(array('appoint_id','appoint_name','payment_status','payment_money','appoint_price','appoint_sum','appoint_type','pic'))->where(array('appoint_id'=>$v['appoint_id'],'appoint_status'=>0,'check_status'=>1,'start_time'=>array('lt',$now_time),'end_time'=>array('gt',$now_time)))->find();


			$appoint_field = array('`a`.`appoint_id`','`a`.`appoint_name`','`a`.`payment_status`','`a`.`payment_money`','`a`.`appoint_price`','`a`.`appoint_sum`','`a`.`appoint_type`','`a`.`pic`','`a`.`is_appoint_price`','`a`.`extra_pay_price`');
			$appoint_category_field = array('`ac`.*');
			$condition_field = array_merge($appoint_field , $appoint_category_field);

			$condition_table = array(C('DB_PREFIX').'appoint'=>'`a`',C('DB_PREFIX').'appoint_category'=>'`ac`');
			$condition_where = '`a`.`cat_id` = `ac`.`cat_id` AND `a`.`appoint_id`='.$v['appoint_id'].' AND `a`.`appoint_status` = 0 AND `a`.`check_status`=1 AND `a`.`start_time` <' . $now_time . ' AND `a`.`end_time` > '. $now_time .' AND `ac`.`is_autotrophic` = 0';

			$appoint = M('')->table($condition_table)->field($condition_field)->where($condition_where)->find();


			if($appoint){
				$group_image_class = new appoint_image();
				$tmp_pic_arr = explode(';',$appoint['pic']);
				$appoint['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$appoint['url']	=	$this->get_appoint_url($appoint['appoint_id'],$is_wap);
				$appoint['payment_money'] = floatval($appoint['payment_money']);
				$appoint['appoint_sum'] = intval($appoint['appoint_sum']);
				$appointArr[]	=	$appoint;
				if($socre == 2){
					break;
				}
			}else{
				continue;
			}
		}
		if($appointArr){
			return $appointArr;
		}else{
			return false;
		}
	}

	/*得到指定分店的团购*/
	public function get_store_appoint_list($store_id , $limit = 6, $is_wap = false ,$order='`g`.`sort` DESC,`g`.`appoint_id` DESC'){
		$start_time = $_SERVER['REQUEST_TIME'];
		$end_time = strtotime(date('Y-m-d 00:00:00'));
		$group_list = D('')->field('`g`.`appoint_name` AS `appoint_name`,`m`.`name` AS `merchant_name`,`g`.*,`gc`.*,`m`.*')->table(array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'appoint_store'=>'gc',C('DB_PREFIX').'appoint_category'=>'ac'))->where("`g`.`appoint_id`=`gc`.`appoint_id` AND `gc`.`store_id`='$store_id' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`appoint_status`='0' AND `g`.`check_status`='1'  AND `g`.`start_time`<'$start_time' AND `g`.`end_time`>'$end_time' AND `m`.`status`='1' AND `ac`.`is_autotrophic`='0' AND `g`.`cat_id`=`ac`.`cat_id`")->limit($limit)->order($order)->select();
		if($group_list){
			$group_image_class = new appoint_image();

			foreach($group_list as $key=>$value){
				$tmp_pic_arr = explode(';',$value['pic']);
				$group_list[$key]['list_pic'] = thumb_img(str_replace('s_','',$group_image_class->get_image_by_path($tmp_pic_arr[0],'s')),240,240,'fill');
				$group_list[$key]['url'] = $this->get_appoint_url($value['appoint_id'],$is_wap);
				$group_list[$key]['payment_money'] = floatval($value['payment_money']);
				$group_list[$key]['appoint_sum'] = intval($value['appoint_sum']);
			}
			return $group_list;
		}else{
			return false;
		}
	}

	/*得到分类下的团购*/
	public function get_appointlist_by_catId($cat_id,$cat_fid,$limit=6,$is_wap=false,$sort=''){
		$start_time = $_SERVER['REQUEST_TIME'];
		$end_time = strtotime(date('Y-m-d 00:00:00'));
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`appoint_status`='0' AND `g`.`check_status`='1'  AND `g`.`start_time`<'$start_time' AND `g`.`end_time`>'$end_time'";
		if(empty($cat_fid) && !empty($cat_id)){
			$condition_where .= " AND `g`.`cat_fid`='$cat_id'";
		}else if(!empty($cat_id)){
			$condition_where .= " AND `g`.`cat_id`='$cat_id'";
		}
		if(empty($sort)){
			$condition_sort = "'`g`.`sort` DESC,`g`.`appoint_id` DESC'";
		}else{
			$condition_sort = $sort;
		}

		$group_list = D('')->field('`g`.`appoint_name` AS `appoint_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*')->table(array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m'))->where($condition_where)->order($condition_sort)->limit($limit)->select();

		if($group_list){
			$group_image_class = new appoint_image();
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],$is_wap);
				$group_list[$k]['payment_money'] = floatval($v['payment_money']);
				$group_list[$k]['appoint_sum'] = intval($v['appoint_sum']);
			}
		}
		return $group_list;
	}

	// 二维码
	public function get_qrcode($id){
		$condition_group['appoint_id'] = $id;
		$now_group = $this->field('`appoint_id`,`qrcode_id`')->where($condition_group)->find();
		if(empty($now_group)){
			return false;
		}
		return $now_group;
	}
	public function save_qrcode($id,$qrcode_id){
		$condition_group['appoint_id'] = $id;
		$data_group['qrcode_id'] = $qrcode_id;
		if($this->where($condition_group)->data($data_group)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_('保存二维码至预约失败！请重试。')));
		}
	}

	public function get_appoint_url($appoint_id,$is_wap=false){
		if($is_wap){
            $appoint = $this->field('`appoint_id`,`cat_id`, cat_fid')
                ->where(['appoint_id' => $appoint_id])
                ->find();
            $category = D('Appoint_category')
                ->field('is_autotrophic')
                ->where(['cat_id' => $appoint['cat_id']])
                ->find();
            if($category){
                $autotrophic = empty($category['is_autotrophic']) ? 'productDetail' : 'goodsDetail';

                return C('config.site_url').D('House_village')->base_url."pages/appoint/goods/{$autotrophic}?appoint_id={$appoint_id}&is_autotrophic={$category['is_autotrophic']}";
            }
            
			return C('config.site_url') . D('House_village')->base_url . 'pages/appoint/goods/productDetail?appoint_id=' . $appoint_id;
		}else{
			return C('config.site_url').'/appoint/'.$appoint_id.'.html';
		}
	}

	/*增加一次预约评论数*/
	public function setInc_appoint_reply($now_order, $score)
	{
        $appointScore = M('Appoint_comment')
            ->where(['appoint_id' => $now_order['appoint_id']])
            ->field('COUNT(*) comment_count, SUM(score) score_all')
            ->find();
        
		$condition_appoint['appoint_id'] = $now_order['appoint_id'];
		$data_appoint['score_all'] = $appointScore['score_all'];
		$data_appoint['reply_count'] = $appointScore['comment_count'];
		$data_appoint['score_mean'] = $appointScore['score_all'] / $appointScore['comment_count'];
        
		if($this->where($condition_appoint)->data($data_appoint)->save()){
			return true;
		}else{
			return false;
		}
	}
	
	
	public function setDec_appoint_reply($reply)
	{
		if ($appoint = $this->field(true)->where(array('appoint_id' => $reply['parent_id']))->find()) {
			$comment = D('Appoint_comment')->field(true)->where(array('order_id' => $reply['order_id'], 'appoint_id' => $reply['parent_id']))->find();
			$comment && D('Appoint_comment')->where(array('order_id' => $reply['order_id'], 'appoint_id' => $reply['parent_id']))->save(array('status' => 2));
			if ($order = D('Appoint_order')->field(true)->where(array('order_id' => $reply['order_id']))->find()) {
				if ($worker = D('Merchant_workers')->field(true)->where(array('merchant_worker_id' => $order['merchant_worker_id']))->find()) {
					$merchant_workers_data['profession_total_score'] = max(0, $worker['profession_total_score'] - $reply['score']);
					$merchant_workers_data['communicate_total_score'] = max(0, $worker['communicate_total_score'] - $reply['score']);
					$merchant_workers_data['speed_total_score'] = max(0, $worker['speed_total_score'] - $reply['score']);
					$merchant_workers_data['profession_num'] = max(0, $worker['speed_total_score'] - 1);
					$merchant_workers_data['communicate_num'] = max(0, $worker['speed_total_score'] - 1);
					$merchant_workers_data['speed_num'] = max(0, $worker['speed_total_score'] - 1);
					if ($merchant_workers_data['profession_total_score'] == 0 || $merchant_workers_data['profession_num'] == 0) {
						$merchant_workers_data['profession_avg_score'] = 0;
					} else {
						$merchant_workers_data['profession_avg_score'] = $merchant_workers_data['profession_total_score']/$merchant_workers_data['profession_num'];
					}
					if ($merchant_workers_data['communicate_total_score'] == 0 || $merchant_workers_data['communicate_num'] == 0) {
						$merchant_workers_data['profession_avg_score'] = 0;
					} else {
						$merchant_workers_data['profession_avg_score'] = $merchant_workers_data['communicate_total_score']/$merchant_workers_data['communicate_num'];
					}
					if ($merchant_workers_data['speed_total_score'] == 0 || $merchant_workers_data['speed_num'] == 0) {
						$merchant_workers_data['profession_avg_score'] = 0;
					} else {
						$merchant_workers_data['profession_avg_score'] = $merchant_workers_data['speed_total_score']/$merchant_workers_data['speed_num'];
					}
					$merchant_workers_data['all_avg_score'] = ($merchant_workers_data['profession_avg_score'] + $merchant_workers_data['communicate_avg_score'] + $merchant_workers_data['speed_avg_score']) / 3;
					$result = D('Merchant_workers')->where(array('merchant_worker_id' => $order['merchant_worker_id']))->save($merchant_workers_data);
				}
			}
			
			$data['reply_count'] = max(0, $appoint['reply_count'] - 1);
			$data['score_all'] = max(0, $appoint['score_all'] - $reply['score']);
			if ($data['reply_count'] == 0 || $data['score_all'] == 0) {
				$data['score_mean'] = 0;
			} else {
				$data['score_mean'] = $data['score_all'] / $data['reply_count'];
			}
			if ($this->where(array('appoint_id' => $reply['parent_id']))->data($data)->save()) return true;
		}
		return false;
	}



	/*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function wap_get_appoint_collect_list($uid){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`a`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `a`.`check_status`='1' AND `a`.`start_time`<'$now_time' AND `a`.`end_time`>'$now_time' AND `a`.`appoint_id`=`c`.`id` AND `c`.`uid`='$uid' AND `c`.`type`='appoint_detail'";

		$condition_table = array(C('DB_PREFIX').'appoint'=>'a',C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'user_collect'=>'c');
		$condition_field  = '`a`.`appoint_name`,`m`.`name` AS `merchant_name`,`a`.*,`m`.*';

		$order = '`c`.`collect_id` DESC';

		import('@.ORG.wap_collect_page');
		$count_group = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_group,10,'page');
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		$return['pagebar'] = $p->show();

		if($group_list){
			$group_image_class = new group_image();
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic']);
				$group_list[$k]['list_pic'] = $this->get_image_by_path($tmp_pic_arr[0],'s');
				$group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],true);
				$group_list[$k]['payment_money'] = floatval($v['payment_money']);
			}
		}
		$return['group_list'] = $group_list;
		return $return;
	}
        
        
        //商家自营列表
        public function get_appointmerchantlist_by_MerchantId($mer_id){
            $now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `m`.`status`='1' AND `g`.`appoint_status`='0' AND `g`.`check_status`='1'  AND `g`.`start_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`mer_id`='$mer_id'";
		if(!empty($appoint_id)){
			$condition_where .= " AND `g`.`appoint_id`<>'$appoint_id'";
		}
		$group_list = D('')->field('`g`.`appoint_name` AS `appoint_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*')->table(array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m'))->where($condition_where)->order('`g`.`sort` DESC,`g`.`appoint_id` DESC')->limit($limit)->select();
		if($group_list){
                    $database_appoint_category = D('Appoint_category');
			$group_image_class = new appoint_image();
			foreach($group_list as $k=>$v){
                                $cat_info = $database_appoint_category->get_category_by_id($v['cat_id']);
                                if($cat_info['is_autotrophic']==0){
                                    $tmp_pic_arr = explode(';',$v['pic']);
                                    $group_list[$k]['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
                                    $group_list[$k]['url'] = $this->get_appoint_url($v['appoint_id'],$is_wap);
                                    $group_list[$k]['payment_money'] = floatval($v['payment_money']);
                                    $group_list[$k]['appoint_sum'] = intval($v['appoint_sum']);
                                }else{
                                    unset($group_list[$k]);
                                }
			}
		}
                return $group_list;
		
        }

	private function get_image_by_path($path,$image_type='-1'){
	    if(!empty($path)){
			$image_tmp = explode(',',$path);
			if($image_type == '-1'){
				$return['image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/'.$image_tmp['1'];
				$return['m_image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/m_'.$image_tmp['1'];
				$return['s_image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/s_'.$image_tmp['1'];
			}else{
				$return = file_domain().'/upload/appoint/'.$image_tmp[0].'/'.$image_type.'_'.$image_tmp['1'];
			}
			return $return;
		}else{
			return false;
		}
	}


    /**
     * 获取预约店铺工作人员和店铺项目信息
     * @param $mobile
     * @return array
     */
	public function worker_appoint_merchant($mobile) {
	    $m_merchant_workers = M('Merchant_workers');
	    $merchant_worker_id = $m_merchant_workers->where(array('mobile' => $mobile, 'status' => 1))->getField('merchant_worker_id');
        $info = array();
	    if ($merchant_worker_id) {
	      $m_merchant_workers_appoint = M('Merchant_workers_appoint');

	      $where = array(
              'merchant_worker_id' => $merchant_worker_id,
          );
	      $field = 'a.merchant_worker_id, a.mer_id, b.appoint_name, b.appoint_content, b.payment_money, b.appoint_type, b.appoint_sum, b.pic, b.appoint_id';

          $info = $m_merchant_workers_appoint->alias('a')
              ->join('LEFT JOIN pigcms_appoint as b on a.appoint_id=b.appoint_id')
              ->field($field)
              ->where($where)
              ->select();


            $group_image_class = new appoint_image();
            foreach($info as &$val){
                $tmp_pic_arr = explode(';',$val['pic']);
                $val['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
                $val['url'] = $this->get_appoint_url($val['appoint_id'],true);
                $val['payment_money'] = floatval($val['payment_money']);
                $val['appoint_sum'] = intval($val['appoint_sum']);
            }

        }
        return $info;
    }
    
    public function getRangeStore($lng, $lat, $distance)
    {
        $store = M('Merchant_store')
            ->where([
                'auth' => 3,
                'is_business_open' => 1
            ])
            ->join('as s inner join '.C('DB_PREFIX').'appoint_store a ON s.store_id = a.store_id')
            ->field([
                's.store_id',
                "(6371 * acos(cos(radians({$lat})) * cos(radians(lat)) * cos(radians(`long`) - 
					radians({$lng})) + sin(radians({$lat})) * sin(radians(lat)))) AS distance"
            ])
            ->having("distance <= {$distance}")
            ->order('distance ASC')
            ->limit(50)
            ->select();

        if($store){
            return array_unique(array_column($store,'store_id'));
        }
        return [];
    }

    public function goodsList(int $distance = 0, $pageSize = 10, $lng = '', $lat = '')
    {
        $page = $_GET['p'] ?: 1;
        $where = [
            'a.check_status' => 1, 
            'a.appoint_status' => 0, 
            'a.start_time' => ['lt',time()],
            'a.end_time' => ['gt', time()],
            'ms.status' => 1,
            'ms.is_business_open' => 1
        ];
        
        $fields = 'a.mer_id, a.appoint_id goods_id, a.pic image, a.appoint_name name, a.appoint_price price, 
        a.appoint_sum sale_num, a.virtual_sales,a.appoint_type, a.score_all, a.is_appoint_price, ac.is_autotrophic';
        
        $appoint = D('Appoint')
            ->alias('a')
            ->join('INNER JOIN '.C('DB_PREFIX').'appoint_category ac ON a.cat_id = ac.cat_id')
            ->join('INNER JOIN '.C('DB_PREFIX').'appoint_store s ON s.appoint_id = a.appoint_id')
            ->join('INNER JOIN '.C('DB_PREFIX').'merchant_store ms ON s.store_id = ms.store_id')
            ->where($where);
        
        $sort = '';
        if(!empty($lng) && !empty($lat) && $distance){
            $fields .= " ,(st_distance(point(`long`, `lat`), point({$lng}, {$lat}) ) * 111195 / 1000) AS distance";
            $sort = 'distance ASC,';
            $appoint = $appoint->having("distance <= {$distance}");
        }
        $sort .= " a.sort DESC, a.appoint_id DESC";
        $countSql = clone $appoint;
        $count = $countSql->count();
        
        $list = $appoint
            ->field($fields)
            ->page("{$page},{$pageSize}")
            ->order($sort)
            ->select();
        
        $Page = new Page($count, $pageSize);
        $show = $Page->show();
        
        if ($list) {
            foreach ($list as &$v) {
                $appoint_image_class = new appoint_image();
                $image = explode(';', $v['image']);
                $v['unit']='';
                if(substr($image[0],1,6) == 'upload'){
                    $v['image'] = file_domain().$image[0];
                }else{
                    $v['image'] = $appoint_image_class->get_image_by_path($image[0], 's');
                }
                if (empty($v['is_appoint_price']) && floatval($v['price'])<=0) {
                    $v['price'] = '面议';
                }
                
                $specPrice = M('Appoint_product')->where(['appoint_id' => $v['goods_id']])->field('price')->select();
                if(!empty($specPrice)){
                    $specPrice = array_column($specPrice,'price');
                    $v['price'] = min($specPrice);
                }
                $baseUrl =  C('config.system_type') == 'village' ? '/packapp/village/' : '/packapp/plat/';
                $autotrophic = empty($v['is_autotrophic']) ? 'productDetail' : 'goodsDetail';

                $v['jump_link'] = C('config.site_url')."{$baseUrl}pages/appoint/goods/{$autotrophic}?appoint_id={$v['goods_id']}&is_autotrophic={$v['is_autotrophic']}";
                $v['sale_num'] = $v['sale_num'] + $v['virtual_sales'];
                unset($v['virtual_sales']);
                $merchant = D('Merchant')
                    ->where(['mer_id' => $v['mer_id']])
                    ->field('name')
                    ->find();
                $v['merchant_name'] = $merchant['name'] ?? '';
            }
        }
        return [
            'count' => $count,
            'list' => $list,
            'pagebar' => $show
        ];
    }
    public function appointCheckQrcode($order_id, $uid)
    {
        $now_order = D('Appoint_order')->get_order_detail_by_id($uid,$order_id,true);
        if(empty($now_order)){
            return '';
        }
        $dirName = '/runtime/qrcode/appoint_new/';
        if(!file_exists($_SERVER['DOCUMENT_ROOT'].$dirName)){
            mkdir($_SERVER['DOCUMENT_ROOT'].$dirName,0777,true);
        }
        $fileName = $uid.$order_id.date('Y-H-d').'.png';
        $filePath = $dirName.$fileName;
        if(file_exists($_SERVER['DOCUMENT_ROOT'].$filePath)){
            return C('config.site_url') .$filePath;
        }
        $path = C('config.site_url')."/wap.php?c=Storestaff&a=appoint_qrcode&order_id=$order_id";
        import('@.ORG.phpqrcode');
        QRcode::png($path,$_SERVER['DOCUMENT_ROOT'].$filePath,2,10,2);

        return C('config.site_url') .$filePath;

    }

    /**
     * 预约评论
     * copy from cms/Lib/Action/Wap/MyAction.class.php appoint_feedback
     */
    public function appointFeedback()
    {
        $appointOrder = D('AppointOrder')
            ->where([
                'service_status' => 1,
                'verify_time' => ['between',[time()-(C('config.appoint_feedback_exceed_time') + 24) * 3600, time()-(C('config.appoint_feedback_exceed_time')) * 3600]],
                'order_time' => ['lt', time()-(C('config.appoint_feedback_exceed_time')) * 3600],
            ])
            ->limit(50)
            ->select();
        fdump_api([
            'sql' => D('AppointOrder')->_sql()
        ],'appoint/appointFeedback', 1);
        foreach ($appointOrder as $now_order) {
            $database_merchant_workers         = D('Merchant_workers');
            $database_appoint_visit_order_info = D('Appoint_visit_order_info');
            $database_appoint_supply           = D('Appoint_supply');
            $Map['appoint_order_id']           = $now_order['order_id'];
            $appoint_visit_order_info          = $database_appoint_visit_order_info->where($Map)->find();

            if (!$appoint_visit_order_info) {
                $Map['order_id']          = $now_order['order_id'];
                $appoint_visit_order_info = $database_appoint_supply->where($Map)->find();
            }
            $merchant_workers_info = NULL;
            if ($appoint_visit_order_info) {
                $_Map['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'] > 0 ? $appoint_visit_order_info['merchant_worker_id'] : $appoint_visit_order_info['worker_id'];
                $merchant_workers_info      = $database_merchant_workers->appoint_worker_info($_Map);
            }

            $score                   = 5;
            $profession_total_score  = 5;
            $communicate_total_score = 5;
            $speed_total_score       = 5;

            $professionScore  = $profession_total_score;
            $communicateScore = $communicate_total_score;
            $speedScore       = $speed_total_score;

            $totalScore = $score + $profession_total_score + $communicate_total_score + $speed_total_score;
            if ($merchant_workers_info) {//有服务人员的取评价平均分
                $averageScore = round(bcdiv($totalScore, 4, 2));
                $score        = $averageScore;
            } else {
                $averageScore = $score;
            }
            M()->startTrans();
            try {
                $pic_ids                  = array();
                $database_reply           = D('Reply');
                $data_reply['parent_id']  = $now_order['appoint_id'];
                $data_reply['store_id']   = $now_order['store_id'];
                $data_reply['mer_id']     = $now_order['mer_id'];
                $data_reply['score']      = $averageScore;
                $data_reply['order_type'] = '2';
                $data_reply['order_id']   = intval($now_order['order_id']);
                $data_reply['anonymous']  = 1;
                $data_reply['status']     = 1;
                $data_reply['comment']    = '默认好评！';
                $data_reply['uid']        = $now_order['uid'];
                $data_reply['pic']        = !empty($pic_ids) ? implode(',', $pic_ids) : '';
                $data_reply['add_time']   = time();
                $data_reply['add_ip']     = get_client_ip(0);
                if ($database_reply->data($data_reply)->add() === false) {
                    throw_exception('评价失败！');
                }
                D('Appoint')->setInc_appoint_reply($now_order, $score);
                D('Appoint_order')->change_status($now_order['order_id'], 2);
                /*----系统订单信息-已评价 该系统订单状态为已评价---*/
                D('System_order')->save_order_status('appoint', $now_order['order_id'], 3);

                $database_merchant_score = D('Merchant_score');
                $now_merchant_score      = $database_merchant_score
                    ->field('`pigcms_id`,`score_all`,`reply_count`')
                    ->where(array('parent_id' => $now_order['mer_id'], 'type' => '1'))
                    ->find();
                if (empty($now_merchant_score)) {
                    $data_merchant_score['parent_id']   = $now_order['mer_id'];
                    $data_merchant_score['type']        = '1';
                    $data_merchant_score['score_all']   = $score;
                    $data_merchant_score['reply_count'] = 1;
                    $database_merchant_score->data($data_merchant_score)->add();
                } else {
                    $data_merchant_score['score_all']   = $now_merchant_score['score_all'] + $score;
                    $data_merchant_score['reply_count'] = $now_merchant_score['reply_count'] + 1;
                    $database_merchant_score
                        ->where(array('pigcms_id' => $now_merchant_score['pigcms_id']))
                        ->data($data_merchant_score)
                        ->save();
                }
                $now_store_score = $database_merchant_score
                    ->field('`pigcms_id`,`score_all`,`reply_count`')
                    ->where(array('parent_id' => $now_order['store_id'], 'type' => '2'))
                    ->find();
                if (empty($now_store_score)) {
                    $data_store_score['parent_id']   = $now_order['store_id'];
                    $data_store_score['type']        = '2';
                    $data_store_score['score_all']   = $score;
                    $data_store_score['reply_count'] = 1;
                    $database_merchant_score->data($data_store_score)->add();
                } else {
                    $data_store_score['score_all']   = $now_store_score['score_all'] + $score;
                    $data_store_score['reply_count'] = $now_store_score['reply_count'] + 1;
                    $database_merchant_score->where(array('pigcms_id' => $now_store_score['pigcms_id']))->data($data_store_score)->save();
                }

                //工作人员评分start
                if ($appoint_visit_order_info) {
                    if ($merchant_workers_info) {
                        $profession_total_score  = $merchant_workers_info['profession_total_score'];
                        $communicate_total_score = $merchant_workers_info['communicate_total_score'];
                        $speed_total_score       = $merchant_workers_info['speed_total_score'];
                        $profession_num          = $merchant_workers_info['profession_num'];
                        $communicate_num         = $merchant_workers_info['communicate_num'];
                        $speed_num               = $merchant_workers_info['speed_num'];

                        $profession_total_score  += $professionScore;
                        $communicate_total_score += $communicateScore;
                        $speed_total_score       += $speedScore;
                        $profession_num++;
                        $communicate_num++;
                        $speed_num++;

                        $merchant_workers_data['profession_total_score']  = $profession_total_score;
                        $merchant_workers_data['communicate_total_score'] = $communicate_total_score;
                        $merchant_workers_data['speed_total_score']       = $speed_total_score;
                        $merchant_workers_data['profession_num']          = $profession_num;
                        $merchant_workers_data['communicate_num']         = $communicate_num;
                        $merchant_workers_data['speed_num']               = $speed_num;
                        $merchant_workers_data['profession_avg_score']    = $profession_total_score / $profession_num;
                        $merchant_workers_data['communicate_avg_score']   = $communicate_total_score / $communicate_num;
                        $merchant_workers_data['speed_avg_score']         = $speed_total_score / $speed_num;
                        $merchant_workers_data['all_avg_score']           = ($merchant_workers_data['profession_avg_score'] + $merchant_workers_data['communicate_avg_score'] + $merchant_workers_data['speed_avg_score']) / 3;
                        $merchant_workers_data['mer_id']                  = $now_order['mer_id'];
                        $result                                           = $database_merchant_workers->where($_Map)->data($merchant_workers_data)->save();
                        if (!$result) {
                            throw_exception('工作人员评分失败！');
                        }
                        $database_appoint_comment    = D('Appoint_comment');
                        $_data['uid']                = $now_order['uid'];
                        $_data['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'] > 0 ? $appoint_visit_order_info['merchant_worker_id'] : $appoint_visit_order_info['worker_id'];
                        $_data['appoint_id']         = $now_order['appoint_id'];
                        $_data['profession_score']   = $professionScore;
                        $_data['communicate_score']  = $communicateScore;
                        $_data['average_score']      = $averageScore;
                        $_data['speed_score']        = $speedScore;
                        $_data['score']              = $score;
                        $_data['status']             = 1;
                        $_data['comment_img']        = '';
                        $_data['content']            = '默认好评！';
                        $_data['add_time']           = time();
                        $_data['order_id']           = $now_order['order_id'];
                        $_data['mer_id']             = $now_order['mer_id'];

                        if ($database_appoint_comment->data($_data)->add()) {
                            $worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'] > 0 ? $appoint_visit_order_info['merchant_worker_id'] : $appoint_visit_order_info['worker_id'];
                            $database_merchant_workers->where($worker_where)->setInc('comment_num');
                            $database_appoint = D('Appoint');
                            $database_appoint->where(array('appoint_id' => $now_order['appoint_id']))->setInc('comment_num');
                        }
                    } else {
                        $database_appoint_comment    = D('Appoint_comment');
                        $_data['score']              = $score;
                        $_data['average_score']      = $averageScore;
                        $_data['uid']                = $now_order['uid'];
                        $_data['merchant_worker_id'] = 0;
                        $_data['appoint_id']         = $now_order['appoint_id'];
                        $_data['comment_img']        = '';
                        $_data['status']             = 1;
                        $_data['content']            = '默认好评！';
                        $_data['add_time']           = time();
                        $_data['order_id']           = $now_order['order_id'];
                        $_data['mer_id']             = $now_order['mer_id'];
                        $database_appoint_comment->data($_data)->add();
                    }
                }
                fdump_api([
                    'order_id' => $now_order['order_id']
                ],'appoint/appointFeedback', 1);
                M()->commit();
            } catch (\Exception $e) {
                fdump_api([
                    $e->getTraceAsString()
                ], 'appoint/feedback');
                M()->rollback();
            }
        }
        return true;
    }

    /*
    * 获取商品积分比例
    */
    public function getGoodsScore($goodsId)
    {
        $scorePercent = self::where(['appoint_id' => $goodsId])->getField('score_percent');
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