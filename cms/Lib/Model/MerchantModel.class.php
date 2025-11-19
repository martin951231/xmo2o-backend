<?php
class MerchantModel extends Model{
	public function get_info($id){
		$now_merchant = $this->field(true)->where(array('mer_id'=>$id))->find();
		if(empty($now_merchant)){
			return false;
		}
		return $now_merchant;
	}
	public function get_qrcode($id){
		$now_merchant = $this->field('`mer_id`,`qrcode_id`')->where(array('mer_id'=>$id))->find();
		if(empty($now_merchant)){
			return false;
		}
		return $now_merchant;
	}
	public function save_qrcode($id,$qrcode_id){
		if($this->where(array('mer_id'=>$id))->data(array('qrcode_id'=>$qrcode_id))->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_('保存二维码至商家信息失败！请重试。')));
		}
	}
	public function del_qrcode($id){
		if($this->where(array('mer_id'=>$id))->data(array('qrcode_id'=>''))->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_('保存二维码至商家信息失败！请重试。')));
		}
	}
	/*
	 * 若用户扫描了商家二维码，则为商家储存首页排序值。 若商家设置了自动增长的团购ID，则自动增长某团购。
	 *
	 */
	public function update_group_indexsort($mer_id){
		if(empty($mer_id)) return false;
		$now_merchant = $this->field('`auto_indexsort_groupid`')->where(array('mer_id'=>$mer_id))->find();
		if(empty($now_merchant)){
			return false;
		}
		$merchant_qrcode_indexsort = C('config.merchant_qrcode_indexsort');
		if($now_merchant['auto_indexsort_groupid']){
			$database_group = D('Group');
			$condition_group['group_id'] = $now_merchant['auto_indexsort_groupid'];
			if($database_group->where($condition_group)->setInc('index_sort',$merchant_qrcode_indexsort)){
				return true;
			}
		}
		$this->where(array('mer_id'=>$mer_id))->setInc('storage_indexsort',$merchant_qrcode_indexsort);
	}

	public function get_merchants_by_long_lat($lat, $long, $around_range = 2000)
	{
        if(C('config.map_config')=='baidu') {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $location2 = $longlat_class->gpsToBaidu($lat, $long);//转换腾讯坐标到百度坐标
            $lat = $location2['lat'];
            $long = $location2['lng'];
        }
		$Model = new Model();
		$sql = "SELECT s.lat, s.long, s.mer_id, s.name as sname, s.store_id, s.phone as sphone, m.name, m.phone, s.adress, m.pic_info, ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) as juli FROM ". C('DB_PREFIX') . "merchant_store AS s INNER JOIN ". C('DB_PREFIX') . "merchant AS m ON s.mer_id=m.mer_id WHERE `s`.`status`=1 AND `m`.`status`=1 AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < '$around_range' ORDER BY juli ASC";
		$result = $Model->query($sql);
		$merchant_image_class = new merchant_image();
		foreach ($result as &$r) {
			$images = explode(";", $r['pic_info']);
			$images = explode(";", $images[0]);
			$r['img'] = $merchant_image_class->get_image_by_path($images[0]);
			$r['url'] = C('config.site_url').'/wap.php?c=Index&a=index&token=' . $r['mer_id'];
		}
		return $result;
	}

	/*收藏列表*/
	public function get_collect_list($uid){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`m`.`mer_id`=`c`.`id` AND `c`.`type`='merchant_id' AND `c`.`uid`='{$uid}'";
		$condition_table = array(C('DB_PREFIX').'merchant'=>'m',C('DB_PREFIX').'user_collect'=>'c');
		$condition_field  = '`m`.*,c.collect_id';

		$order = '`c`.`collect_id` DESC';

		import('@.ORG.collect_page');
		$count_meal = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_meal,10,'page');
		$merchant_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$return['pagebar'] = $p->show();

		if($merchant_list){
			$store_image_class = new merchant_image();
			foreach($merchant_list as &$v){
				$images = $store_image_class->get_allImage_by_path($v['pic_info']);
				$v['list_pic'] = $images ? array_shift($images) : array();
				$v['url'] = C('config.site_url').'/merindex/'.$v['mer_id'].'.html';
			}
		}
		$return['merchant_list'] = $merchant_list;
		return $return;
	}

         /*public function merchant_list($where,$field = true){
            if(!$where){
                return false;
            }
            $database_appoint_product = D('Appoint_product');
            $database_appoint_order = D('Appoint_order');

            $now_order = $database_appoint_order->field($field)->where($where)->find();

            //服务商家列表start
            $product_id = $now_order['product_id'];
            $_Map['id'] = $product_id;
            $cat_id = $database_appoint_product->where($_Map)->getField('cat_id');
            if(!$cat_id){
                return array('status'=>0,'msg'=>L_('分类为空！'));
            }

            $Map['a.cat_id'] = $cat_id;
            $Map['a.check_status'] = 1;
            $Map['a.appoint_status'] = 0;
            $Map['a.start_time'] = array('lt',$_SERVER['REQUEST_TIME']);
            $Map['a.end_time'] = array('gt',$_SERVER['REQUEST_TIME']);
            $Map['m.status'] = 1;

            $field = 'a.appoint_id,a.mer_id,m.name';
            $appoint_list = D('Appoint a')->join(C('DB_PREFIX').'merchant m on a.mer_id = m.mer_id')->where($Map)->getField('a.`mer_id`,m.`name`');
            $detail['appoint_list'] = $appoint_list;
            //服务商家列表end

            if($now_order){
                return array('status'=>1,'detail'=>$detail);
            }else{
                return array('status'=>0,'detail'=>$detail);
            }
        }*/



        public function merchant_list($where,$field = true){
            if(!$where){
                return false;
            }
            $database_appoint = D('Appoint');
            $database_merchant = D('Merchant');

            $where['check_status'] = 1;
            $where['appoint_status'] = 0;
            $where['start_time'] = array('lt',time());
            $where['end_time'] = array('gt',time());
            $where['appoint_type'] = 1;
            $appoint_list = $database_appoint->field($field)->where($where)->group('mer_id')->select();
            $mer_id_arr = array();
            foreach($appoint_list as $v){
                $mer_id_arr[] = $v['mer_id'];
            }
            $Map['mer_id'] = array('in',$mer_id_arr);
            $Map['status'] = 1;
            $mer_list = $database_merchant->where($Map)->getField('mer_id,name');
            if($mer_list){
                return array('status'=>1,'merchant_list'=>$mer_list);
            }else{
                return array('status'=>0,'merchant_list'=>$mer_list);
            }
        }
    /*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function wap_get_Merchant_list_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0,$keyword=''){
		$now_time = $_SERVER['REQUEST_TIME'];
//		$condition_where = "`m`.`city_id`='".C('config.now_city')."' AND `g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `g`.`type`='1' AND `m`.`status`='1' AND `g`.`end_time`>'$now_time'";
		$condition_where = "`city_id`='".C('config.now_city')."' AND `status`='1'";
		//分类相关
		if(!empty($get_grouplist_catfid)){
			$condition_where .= " AND `cat_fid`='$get_grouplist_catfid'";
		}else if(!empty($get_grouplist_catid)){
			$condition_where .= " AND `cat_id`='$get_grouplist_catid'";
		}
		//区域
		if($area_id || $circle_id){
			$condition_field  = '*';
			if ($circle_id) {
				$condition_where .= " AND circle_id`='$circle_id' ";
			} else {
				$condition_where .= " AND `area_id`='$area_id' ";
			}
			if ($order == 'juli') {
				//$condition_where .= " AND `m`.`mer_id`=`s`.`mer_id`";
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli";
			}
			$condition_table  = C('DB_PREFIX').'merchant_store';
		}else{
			$condition_field  = '*';
			if ($order == 'juli') {
				$condition_where .= " AND `m`.`mer_id`=`s`.`mer_id`";
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli";
			}
			$condition_table  = C('DB_PREFIX').'merchant_store';
		}
		if($keyword){
			$condition_where.=" AND name like '%{$keyword}%' ";
		}

		//排序
		//switch($order){
		//	case 'price':
		//		$order = '`g`.`price` ASC,`g`.`group_id` DESC';
		//		break;
		//	case 'priceDesc':
		//		$order = '`g`.`price` DESC,`g`.`group_id` DESC';
		//		break;
		//	case 'solds':
		//		$order = '`g`.`sale_count` DESC,`g`.`group_id` DESC';
		//		break;
		//	case 'rating':
		//		$order = '`g`.`score_mean` DESC,`g`.`group_id` DESC';
		//		break;
		//	case 'start':
		//		$order = '`g`.`last_time` DESC,`g`.`group_id` DESC';
		//		break;
		//	case 'juli':
		//		$order = 'juli asc,`g`.`group_id` DESC';
		//		break;
		//	default:
		//		$order = '`store_id` DESC';
		//}

		import('@.ORG.wap_group_page');
		$count_group = D('')->table($condition_table)->where($condition_where)->count();

		$p = new Page($count_group,C('config.group_page_row'),C('config.group_page_val'));
		$group_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		$return['pagebar'] = $p->show();
		if($group_list){
			foreach($group_list as $k=>$v){
				$tmp_pic_arr = explode(';',$v['pic_info']);
				$group_list[$k]['list_pic'] = $this->get_image_by_path($tmp_pic_arr[0],'');
				$group_list[$k]['url'] = $this->get_group_url($v['store_id'],true);
				$group_list[$k]['price'] = floatval($v['price']);
				$group_list[$k]['old_price'] = floatval($v['old_price']);
				$group_list[$k]['wx_cheap'] = floatval($v['wx_cheap']);
				$group_list[$k]['is_start'] = 1;
				if ($now_time < $v['begin_time']) {
					$group_list[$k]['is_start'] = 0;
				}
				$group_list[$k]['begin_time'] = date("Y-m-d H:i:s", $v['begin_time']);
			}
		}
		$return['group_list'] = $group_list;
		$return['group_count'] = $count_group;
		$return['totalPage'] = ceil($count_group/C('config.group_page_row'));
		return $return;
	}
	/*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
	public function wap_get_storeList_by_catid($get_grouplist_catid,$get_grouplist_catfid,$area_id,$order, $lat = 0, $long = 0, $circle_id = 0,$search_txt=''){
		$now_time = $_SERVER['REQUEST_TIME'];
//		$condition_where = "`gc`.`city_id`='".C('config.now_city')."' AND `g`.`status`='1' AND `g`.`type`='1' AND `s`.`status`='1' AND `g`.`end_time`>'$now_time' AND `gc`.`group_id`=`g`.`group_id` AND `gc`.`store_id`=`s`.`store_id`";
		$condition_where = "`m`.`city_id`='".C('config.now_city')."' AND `m`.`status`='1' AND `s`.`status`='1'";
		//分类相关
		if(!empty($get_grouplist_catfid)){
			$condition_where .= " AND `m`.`cat_fid`='$get_grouplist_catfid'";
		}else if(!empty($get_grouplist_catid)){
			$condition_where .= " AND `m`.`cat_id`='$get_grouplist_catid'";
		}
		$condition_field  = '`s`.`fans_count`,`m`.`name` AS `store_name`,`m`.*, FORMAT(`ms`.`score_all`/`ms`.`reply_count`,1) AS pingjun,`ms`.`type`,mm.isverify';
//		$condition_table  = array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'group_store'=>'gc', C('DB_PREFIX').'merchant_store'=>'s');
		$condition_table  = array(C('DB_PREFIX').'merchant_store'=>'m');
		//区域
		if($area_id || $circle_id){
			if ($circle_id) {
				$condition_where .= " AND `m`.`circle_id`='$circle_id'";
			} else {
				$condition_where .= " AND `m`.`area_id`='$area_id'";
			}
			if ($order == 'juli') {
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`m`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`m`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`m`.`long`*PI()/180)/2),2)))*1000) AS juli";
			}
		}else{
			if ($order == 'juli') {
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`m`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`m`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`m`.`long`*PI()/180)/2),2)))*1000) AS juli";
			}

		}
		if($search_txt){
			$condition_where.=" AND `m`.`name` like '%$search_txt%' ";
		}
		//排序
		switch($order){
			case 'solds':		//	人气最高
				$order = '`m`.`hits` DESC,`m`.`store_id` DESC';
				break;
			case 'rating':		//	评价最高
				$order = '`pingjun` DESC,`m`.`store_id` DESC';
				break;
			case 'start':		//	最新发布
				$order = '`m`.`last_time` DESC,`m`.`store_id` DESC';
				break;
			case 'juli':	//	距离
				$order = '`juli` asc,`m`.`store_id` DESC';
				break;
			default:		//	默认
				$order = '`m`.`sort` DESC,`m`.`store_id` DESC';
		}
		import('@.ORG.wap_group_page');
		$count_group = M('')->table($condition_table)->join(C('DB_PREFIX').'merchant as s ON s.mer_id = m.mer_id')->where($condition_where)->count();
		$p = new Page($count_group, 10,'page');
		$store_list = M('')->field($condition_field)->table($condition_table)->join(array('`pigcms_merchant_score` as `ms` ON m.`store_id` = ms.`parent_id` AND ms.type=2','`pigcms_merchant` as `s` ON m.`mer_id` = s.`mer_id`'))->join(C('DB_PREFIX').'merchant as mm ON mm.mer_id = m.mer_id')->where($condition_where)->group('m.`store_id`')->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		$return['totalPage'] = ceil($count_group/10);
        if ($store_list) {
			$site_url  = C('config.site_url');
			$store_image_class = new store_image();
            foreach ($store_list as $k => $v) {
                if (!empty($v['pingjun'])) {
                    $store_list[$k]['xing'] = $v['pingjun'] * 20;
                } else {
                    $v['pingjun'] = 0;
                }
                
                //$store_list[$k]['url'] = C('config.site_url') . D('House_village')->base_url . 'pages/store/homePage?store_id=' . $v['store_id']; //老版本店铺主页
                $store_list[$k]['url'] = get_base_url('pages/store/v1/home/index?store_id=' . $v['store_id'], true); //新版本店铺主页

                $images = $store_image_class->get_allImage_by_path($v['pic_info']);
                $store_list[$k]['list_pic'] = $images[0];
                if ($v['logo']) {
                	$store_list[$k]['list_pic'] = file_domain().$v['logo'];
                }
                // $tmp_pic_arr = explode(';', $v['pic_info']);
                // $store_list[$k]['list_pic'] = $this->get_image_by_path($tmp_pic_arr[0], '1');
				$store_list[$k]['discount_txt'] = unserialize($v['discount_txt']);
				$store_list[$k]['pay_in_store'] = C('config.pay_in_store');
				$store_list[$k]['store_pay'] = $site_url.str_replace('appapi.php','wap.php',U('My/pay', array('store_id' => $v['store_id'])));
            }
        }
		$return['store_list'] = $store_list;
		$return['store_count'] = count($store_list);
		return $return;
	}
	/*wap版得到指定商场搜索商铺，带有分页功能*/
	public function wap_get_storeList($market_id,$lat=0,$long=0){
		$now_time = $_SERVER['REQUEST_TIME'];
		$condition_where = "`status`='1'";
		$condition_field  = '*';
		$condition_table  = array(C('DB_PREFIX').'merchant_store'=>'m');
		//区域
		if($market_id){
			$condition_where .= " AND `market_id`='$market_id'";
		}else{
			$condition_where .= " AND `market_id`=0";
		}
		if($lat && $long){
			$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`long`*PI()/180)/2),2)))*1000) AS juli";
			$order	=	'juli';
		}
		import('@.ORG.wap_group_page');
		$count_group = M('Merchant_store')->where($condition_where)->count();
		$p = new Page($count_group,20,'page');
		$store_list	= M('Merchant_store')->field($condition_field)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		if($store_list){
			foreach($store_list as $k=>$v){
				$merchant_score	=	M('Merchant_score')->field('FORMAT(`score_all`/`reply_count`,1) AS pingjun')->where(array('parent_id'=>$v['store_id'],'type'=>2))->find();
				if($merchant_score){
					$store_list[$k]['pingjun']	=	$merchant_score['pingjun'];
					$store_list[$k]['xing']		=	$merchant_score['pingjun']*20;
				}
				$merchant	=	M('Merchant')->field('fans_count')->where(array('mer_id'=>$v['mer_id']))->find();
				$store_list[$k]['fans_count']	=	$merchant['fans_count'];
				$store_list[$k]['url'] = U('Merchant/shop',array('store_id'=>$v['store_id']));
				$tmp_pic_arr = explode(';',$v['pic_info']);
				$store_list[$k]['list_pic'] = $this->get_image_by_path($tmp_pic_arr[0],'1');
				$store_list[$k]['store_name'] = $store_list[$k]['name'];
				unset($store_list[$k]['name']);
			}
		}
		$return['totalPage'] = ceil($count_group/20);
		$return['store_list'] = $store_list;
		$return['store_count'] = count($store_list);
		return $return;
	}
	/*获取快店的url*/
	public function get_group_url($store_id,$is_wap){
		if($is_wap){
			return str_replace('appapi.php', 'wap.php',U('Wap/Merchant/shop',array('store_id'=>$store_id)));
		}else{
			echo 1;
			return C('config.site_url').'/shop/'.$store_id.'.html';
		}
	}
	/*根据商品数据表的图片字段的一段来得到图片*/
	public function get_image_by_path($path,$image_type='-1'){
        if(!empty($path)){
            if($image_type == '-1'){
                $return['image'] = file_domain().$path;
                $return['m_image'] = file_domain().$path;
                $return['s_image'] = file_domain().$path;
            }else{
                if(strpos($path,file_domain()) !== false){
                    $return=$path;
                }else{
                    $return = file_domain().$path;
                }
            }
            return $return;
        }else{
            return false;
        }
    }

	public function get_merchant_user($mer_id){
		return $this->join('as m left join '.C('DB_PREFIX').'user as u ON u.uid = m.uid' )
				->field('m.mer_id,m.name as mer_name ,m.phone as mer_phone,m.open_money_tempnews,u.uid,u.nickname,u.phone as user_phone,u.openid')->where(array('m.mer_id'=>$mer_id,'u.uid'=>array('gt',0)))->find();
	}
	
    	
    public function saverelation($openid, $mer_id, $from_merchant)
    {
        if (empty($openid)) return false;
        if ($relation = D('Merchant_user_relation')->field(true)->where(array('openid' => $openid, 'mer_id' => $mer_id))->find()) {
            return false;
        }
        
        $relation = array('openid' => $openid, 'mer_id' => $mer_id, 'dateline' => time(), 'from_merchant' => $from_merchant);
        D('Merchant_user_relation')->add($relation);
        D('Merchant')->where(array('mer_id' => $mer_id))->setInc('fans_count', 1);
        return true;
    }

    public function getDiscount($mer_id)
    {
        if (0 == C('config.is_open_merchant_discount')) {
            return 0;
        }
        $merchant = $this->field('mer_id, discount_order_num, discount_num, discount_percent, is_discount')->where(array('mer_id' => $mer_id))->find();
        if (empty($merchant)) {
            return 0;
        }
        if ($merchant['is_discount'] == 1 && ($merchant['discount_order_num'] > $merchant['discount_num'] || $merchant['discount_order_num'] == 0)) {
            return $merchant['discount_percent'];
        } elseif ($merchant['is_discount'] == 1) {
            $this->where(array('mer_id' => $mer_id))->save(array('is_discount' => 0));
        }
        return 0;
    }
    
    public function setDiscountNumInc($mer_id, $money)
    {
        $this->where(array('mer_id' => $mer_id))->setInc('discount_num');
        $this->where(array('mer_id' => $mer_id))->setInc('discount_price', $money);
        
        $merchant = $this->field('mer_id, discount_order_num, discount_num, discount_percent, is_discount')->where(array('mer_id' => $mer_id))->find();
        if ($merchant['discount_order_num'] != 0 && $merchant['discount_order_num'] <= $merchant['discount_num']) {
            $this->where(array('mer_id' => $mer_id))->save(array('is_discount' => 0));
        }
    }
    
    public function guess_you_like($long, $lat, $limit)
    {
        $now_time = $_SERVER['REQUEST_TIME'];
        
        $condition_where = "`m`.`city_id`='" . C('config.now_city') . "' AND `m`.`status`='1' AND `s`.`status`='1'";
        
        $condition_field = '`s`.`fans_count`,`m`.`name` AS `store_name`,`m`.*, FORMAT(`ms`.`score_all`/`ms`.`reply_count`,1) AS pingjun,`ms`.`type`,mm.isverify';
        $condition_table = array(
            C('DB_PREFIX') . 'merchant_store' => 'm'
        );
        // 区域
        
        $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`m`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`m`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`m`.`long`*PI()/180)/2),2)))*1000) AS juli";
        
        $order = '`juli` asc,`m`.`store_id` DESC';
        
        $store_list = M('')->field($condition_field)
            ->table($condition_table)
            ->join(array(
            '`pigcms_merchant_score` as `ms` ON m.`store_id` = ms.`parent_id` AND ms.type=2',
            '`pigcms_merchant` as `s` ON m.`mer_id` = s.`mer_id`'
        ))->join(C('DB_PREFIX') . 'merchant as mm ON mm.mer_id = m.mer_id')
            ->where($condition_where)
            ->group('m.`store_id`')
            ->order($order)
            ->limit($limit)
            ->select();

        if ($store_list) {
            foreach ($store_list as $k => $v) {
                if ($v['pingjun']) {
                    $store_list[$k]['xing'] = $v['pingjun'] * 20;
                }
                $store_list[$k]['url'] = U('Merchant/shop', array('store_id' => $v['store_id']));
                $tmp_pic_arr = explode(';', $v['pic_info']);
                $store_list[$k]['list_pic'] = $this->get_image_by_path($tmp_pic_arr[0], '1');
            }
        }
        $return['store_list'] = $store_list;
        return $return;
    }


	public function reg_notice($mer_id){

		$where['mer_id'] = $mer_id;
		$merchant = M('Merchant')->field(true)->where($where)->find();

		$where_admin['openid']=array('neq','');
		$where_admin['mer_reg_notice']=1;
		$admin_list = M('Admin')->where($where_admin)->select();
		$tmp_area = array($merchant['city_id'],$merchant['province_id'],$merchant['area_id']);
		foreach ($admin_list as $v) {
			if ($v['level'] == 2 || $v['level'] == 0) {
				$send_to[] = $v;
			} else if (in_array($v['area_id'], $tmp_area)) {
				$send_to[] = $v;
			}
		}

		$area_list  =  M('Area')->where(array('area_id'=>array('in',$tmp_area)))->getField('area_id,area_name');
		$address = '';
		foreach ( $tmp_area as $v) {
			$address .= $area_list[$v];
		}
		$href ='';

        //没查到商家信息就不要发模板消息啦！！！
        if (empty($merchant)) {
            return false;
        }

		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		foreach ($send_to as $s) {
			$model->sendTempMsg('OPENTM405733036', array('href' => $href,
				'wecha_id' => $s['openid'],
				'first' => L_('您有一个新的商家入驻申请'),
				'keyword1' => $merchant['name'],
				'keyword2' => $address,
				'keyword3' => date('Y-m-d H:i:s', $merchant['reg_time']),
				'keyword4' => "{$merchant['account']} {$merchant['phone']}",
				'remark' => L_('请登录PC端系统后台及时查看')),
				0);
		}
	}

	/*使用商家的积分*/
	public function use_score($mer_id,$score,$desc,$order_id='',$order_type='',$uid=0){
		if($score>0){

			$zbw_sync =false;

			$condition_user['mer_id'] = $mer_id;
//			if($this->where($condition_user)->setDec('platform_score',$score)){
			if($this->where($condition_user)->setDec('system_score',$score)){
				if(!$zbw_sync){
					$data['mer_id'] = $mer_id;
					$data['sore_count'] = $score;
					$data['business_id'] = $order_id;
					$data['business_type'] = $order_type;
					$data['set_type'] = 1;
					$data['operation'] = $uid;
					$data['operation_type'] = 0;
					$data['add_time'] = time();
					D('Merchant_score_order')->data($data)->add();
				}
				return array('error_code' =>false,'msg' =>C('config.score_name').L_('扣除成功！'));
			}else{

				return array('error_code' => true, 'msg' => L_("扣除X1失败！请联系管理员协助解决。",array("X1" => C('config.score_name')))); 
			}
		}else{
			return array('error_code' => true, 'msg' => C('config.score_name').L_('数据有误'));
		}
	}

	//增加积分
    public function add_system_score($order_info,$system_take,$score,$desc){
		$condition['mer_id'] = $order_info['mer_id'];
		if($this->where($condition)->setInc('system_score',$score)){ 
			$data['mer_id'] = $order_info['mer_id'];
	        $data['uid'] = $order_info['uid'];
	        $data['money'] = $system_take;
	        $data['order_id'] = $order_info['order_id'];
	        $data['order_type'] = $order_info['order_type'];
	        $data['score'] = $score;
	        $data['desc'] = $desc;
	        $data['type'] = 1;// 增加
	        $data['time'] = time();
	       	if (D('Merchant_score_list')->add($data)) {
	            return array('error_code'=>false,'msg'=>L_('保存商家积分成功！'));
	       	}else{
	            return array('error_code'=>true,'msg'=>L_('保存商家积分失败！'));
	       	}
		}else{
	        return array('error_code'=>true,'msg'=>L_('保存商家积分失败！'));
		}
    }

	//减少商家平台积分
    public function use_system_score($mer_id,$score,$desc){
		$condition['mer_id'] = $mer_id;
		if($this->where($condition)->setDec('system_score',$score)){ 
			$data['mer_id'] = $mer_id;
	        $data['score'] = $score;
	        $data['desc'] = $desc;
	        $data['type'] = 2;// 减少
	        $data['is_pay_back'] = 1;// 减少
	        $data['time'] = time();
	       	if (D('Merchant_score_list')->add($data)) {
	            return array('error_code'=>false,'msg'=>L_('保存商家积分成功！'));
	       	}else{
	            return array('error_code'=>true,'msg'=>L_('保存商家积分失败！'));
	       	}
		}else{
	        return array('error_code'=>true,'msg'=>L_('保存商家积分失败！'));
		}
    }

	//减少商家平台积分
    public function add_money_by_score($mer_id,$money,$desc){
		$condition['mer_id'] = $mer_id;
		$mer_info = $this->get_info($mer_id);
		if($this->where($condition)->setInc('money', $money)){
			$data['mer_id'] = $mer_id;
	        $data['money'] = $money;
	        $data['income'] = 1;
	        $data['desc'] = $desc;
	        $data['type'] = 'score_pay_back';
	        $data['use_time'] = time();
	        $data['now_mer_money'] = $mer_info['money']+$money;
	        if (D('Merchant_money_list')->add($data)){
				return array('error_code'=>false,'msg'=>L_('保存商家余额成功！'));
	        }else{
				return array('error_code'=>true,'msg'=>L_('保存商家余额失败！'));
	        }       
		}else{
	        return array('error_code'=>true,'msg'=>L_('保存商家余额失败！'));
		}
    }

    //增加平台采购备用金
    public function add_reserve_fund($order_info,$money,$desc){
		$condition['mer_id'] = $order_info['mer_id'];
		if($this->where($condition)->setInc('reserve_fund',$money)){ 
			$data['mer_id'] = $order_info['mer_id'];
	        $data['uid'] = $order_info['uid'];
	        $data['money'] = $money;
	        $data['order_id'] = $order_info['order_id'];
	        $data['order_type'] = $order_info['order_type'];
	        $data['desc'] = $desc;
	        $data['type'] = 1;// 增加
	        $data['time'] = time();
	       	if (D('Merchant_reserve_fund')->add($data)) {
	            return array('error_code'=>false,'msg'=>L_('保存平台采购备用金成功！'));
	       	}else{
	            return array('error_code'=>true,'msg'=>L_('保存平台采购备用金失败！'));
	       	}
		}else{
	        return array('error_code'=>true,'msg'=>L_('保存平台采购备用金失败！'));
		}
    }


    /*wap版查询店铺*/
    public function wap_search_storeList($search_txt='',$business=''){
        $condition_where = "(`m`.`city_id`='".C('config.now_city')."' or `m`.`area_id`='".C('config.now_city')."') AND `m`.`status`='1' AND `s`.`status`='1'";
		if($business=='group'){//团购业务
			$condition_where.= ' AND `m`.`have_meal`=1 AND `m`.`group_on_sale`=1';
		}
        $condition_field  = '`s`.`fans_count`,`m`.`name` AS `store_name`,`m`.*, FORMAT(`ms`.`score_all`/`ms`.`reply_count`,1) AS pingjun,`ms`.`type`,mm.isverify';
        $condition_table  = array(C('DB_PREFIX').'merchant_store'=>'m');
        if($search_txt){
            $condition_where.=" AND `m`.`name` like '%$search_txt%' ";
        }
        $order = '`m`.`sort` DESC,`m`.`store_id` DESC';
        $store_list = M('')->field($condition_field)->table($condition_table)->join(array('`pigcms_merchant_score` as `ms` ON m.`store_id` = ms.`parent_id` AND ms.type=2','`pigcms_merchant` as `s` ON m.`mer_id` = s.`mer_id`'))->join(C('DB_PREFIX').'merchant as mm ON mm.mer_id = m.mer_id')->where($condition_where)->group('m.`store_id`')->order($order)->limit(20)->select();
		//echo M()->getLastsql();exit;
        $d_group = D('Group');
        if ($store_list) {
            foreach ($store_list as $k => $v) {
                if (!empty($v['pingjun'])) {
                    $store_list[$k]['xing'] = $v['pingjun'] * 20;
                } else {
                    $v['pingjun'] = 0;
                }
                $store_list[$k]['url'] = U('Merchant/shop', array(
                    'store_id' => $v['store_id']
                ));
                $tmp_pic_arr = explode(';', $v['pic_info']);
                $store_list[$k]['list_pic'] = $this->get_image_by_path($tmp_pic_arr[0], '1');
                // 获取浏览数和头像
                $store_list[$k]['merchant_record_num'] = $d_group->merchant_record_num($v['mer_id'], 1);
            }
        }
        $return['store_list'] = $store_list;
        $return['store_count'] = count($store_list);
        return $return;
    }

    /**
     * 手机号唯一判断
     */
    public function phoneIsUnique($phone, $exceptMerId = [])
    {
        if (empty($phone)) {
            return false;
        }
        $where = ['phone' => $phone, 'status' => ['NEQ', 4]];
        if ($exceptMerId) {
            $where['mer_id'] = ['NOT IN', $exceptMerId];
        }
        $merchant = $this->where($where)->find();
        return $merchant ? false : true;
    }

    /**
     * 获取所有启用状态下商户的id集合
     */
    public function get_all_normal_merchant(){
    	$where = array();
    	$where['status'] = 1;
    	$list = $this->where($where)->field('mer_id')->select();
    	$return = array();
    	if($list){
    		foreach ($list as $v) {
    			$return[] = $v['mer_id'];
    		}
    	}
    	return $return;
    }

    public function v20Login($id,$extends=[])
	{
		$where['mer_id'] = $id;
		$now_admin = $this->where($where)->find();
		if(empty($now_admin)){
			return false;
		}
		
		if($now_admin['status'] != 1){
			return false;
		}
		
		if(!empty($now_admin['last_ip'])){
			import('ORG.Net.IpLocation');
			$IpLocation = new IpLocation();
			$last_location = $IpLocation->getlocation($now_admin['last_ip']);
			$now_admin['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
			$now_admin['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');
		}
		$now_admin['merchant_extends'] = $extends;
		session('merchant',$now_admin);
		session('staff',null);
		return true;
	}
    /**
     * [change_merchant_name_language 根据语言环境替换列表中商户名称]
     * @Author   JJC
     * @DateTime 2020-05-14T11:01:23+0800
     * @param    [type]                   $list     [需要处理的列表]
     * @param    [type]                   $language [语言环境]
     * @param    string                   $mer_name [列表中商户名称的key]
     * @param    string                   $mer_id   [列表中商户id的key]
     * @return   [type]                             [处理好的数组]
     */
    public function change_merchant_name_lang($list,$lang='',$mer_name='merchant_name',$mer_id='mer_id'){

    	$model_name = 'Merchant_'.$lang;
    	$model_key = 'mer_id_'.$lang;

    	$model = M($model_name);
    	$mer_ids = array_column($list, $mer_id);

    	$mer_list = $model->where([$model_key=>['in',$mer_ids]])->select();
    	$mer_arr = [];
    	foreach ($mer_list as $key => $value) {
    		$mer_arr[$value[$model_key]] = $value['name_'.$lang];
    	}
    	foreach ($list as $k => $v) {
    		$list[$k][$mer_name] = $mer_arr[$v[$mer_id]]?$mer_arr[$v[$mer_id]]:$v[$mer_name];
    	}
    	return $list;
    }

    public function replace_merchant_img($img, $type = 'merchant')
    {
        if (empty($img)) {
            return '';
        }

        $imageTmp = explode(',', $img);
        if (count($imageTmp) == 1) {
            //新格式
			if (strpos($img, '/upload') !== false) {
				return replace_file_domain($img);
			}else{
				return replace_file_domain('/upload/'.$type.'/'.trim($img,'/'));
			}
        } else {
            //旧格式 	000/000/000,628c7f2b96599836.png
            $img = '/upload/' . $type . '/' . $imageTmp[0] . '/' . $imageTmp[1];
            return replace_file_domain($img);
        }
    }
}
?>