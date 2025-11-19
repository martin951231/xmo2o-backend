<?php
class Merchant_store_shopModel extends Model
{
    protected $send_time_type = [];

    public function _initialize(){
        parent::_initialize();
        $this->send_time_type = [
            L_('分钟'),
            L_('小时'),
            L_('天'),
            L_('周'),
            L_('月'),
        ];
    }
    /**
     * 根据条件获取商家列表
     *
     * @param boolean $checkTime  是否检查店铺分类是否显示
     * @param array $where            
     * @param number $limit            
     */
    public function get_list_by_option($where = array(), $is_wap = 1, $is_search = 0, $pageSize=0, $checkTime = false)
    {
        $deliver_type = isset($where['deliver_type']) ? $where['deliver_type'] : 'all';
        $order_str = isset($where['order']) ? $where['order'] : '';
        $lat = isset($where['lat']) ? floatval($where['lat']) : 0;
        $long = isset($where['long']) ? floatval($where['long']) : 0;
        $cat_id = isset($where['cat_id']) ? intval($where['cat_id']) : 0;
        $cat_fid = isset($where['cat_fid']) ? intval($where['cat_fid']) : 0;
        $limit	 = isset($where['limit']) ? intval($where['limit']) : 0;
        $special = isset($where['special']) ? $where['special'] : '';
        $catIds = isset($where['cat_ids']) ? $where['cat_ids'] : [];

        isset($where['city_id']) &&  $where['city_id'] = intval($where['city_id']);
        if (count(explode('-', $deliver_type)) == 2) {
            $deliver_type = 'all';
        }
        $condition_where = "s.status=1 AND s.store_id=m.store_id AND s.have_shop=1 AND ((m.is_close_shop=0 AND m.store_theme=1) OR m.store_theme=0) AND s.shop_on_sale=1";

        if ($where['city_id']) {
            $condition_where .= " AND s.city_id='".$where['city_id']."'";
        }else{
            $condition_where .= " AND s.city_id='".(C('config.now_shop_city') ? C('config.now_shop_city') : C('config.now_city'))."'";
        }

        if (is_null(C('config.store_city_match')) || C('config.store_city_match') == 1) {
            $condition_where = "s.city_id='" . (C('config.now_shop_city') ? C('config.now_shop_city') : C('config.now_city')) . "' AND s.status=1 AND s.store_id=m.store_id AND s.have_shop=1 AND s.shop_on_sale=1 AND ((m.is_close_shop=0 AND m.store_theme=1) OR m.store_theme=0)";
        } else {
            $condition_where = "s.status=1 AND s.store_id=m.store_id AND s.have_shop=1 AND s.shop_on_sale=1 AND ((m.is_close_shop=0 AND m.store_theme=1) OR m.store_theme=0)";
        }

        if (C('config.store_shop_auth') == 1) {
            $condition_where .= " AND s.auth>2";
        }
        
        if (isset($where['is_business_open'])) {
            $condition_where .= " AND s.is_business_open = 1";
        }

        //为您优选和爆款推荐获取店铺列表增加有效时间判断
        $today = date('Y-m-d');
        $time = date('H:i:s');
        $week = date('w');
		if ($where['preference_status']) {
            $condition_where .= " AND m.preference_status=1";
            $checkTime && $condition_where .=sprintf(" AND ((m.preference_effect_date_type = 1 OR (m.preference_start_date <= '%s' AND m.preference_end_date >= '%s' AND FIND_IN_SET(%d,m.preference_week))) AND (m.preference_effect_time_type = 1 OR (m.preference_start_time <= '%s' AND m.preference_end_time >= '%s'))) ", $today, $today, $week, $time, $time);
        }
        if ($where['recommend_status']) {
            $condition_where .= " AND m.recommend_status=1 AND m.recommend_good_ids <> ''";
            $checkTime && $condition_where .=sprintf(" AND ((m.recommend_effect_date_type = 1 OR (m.recommend_start_date <= '%s' AND m.recommend_end_date >= '%s' AND FIND_IN_SET(%d,m.recommend_week))) AND (m.recommend_effect_time_type = 1 OR (m.recommend_start_time <= '%s' AND m.recommend_end_time >= '%s'))) ", $today, $today, $week, $time, $time);
        }
        if (isset($where['deliver_type_pc'])) {
            if ($where['deliver_type_pc'] == 0) {
                $condition_where .= " AND ((`m`.`delivery_range_type`=0 AND `m`.`deliver_type` IN (0, 1, 3, 4)  AND ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < `m`.`delivery_radius`*1000)";
                $condition_where .= " OR (`m`.`delivery_range_type`=1 AND ST_Contains(ST_PolygonFromText(`m`.`delivery_range_polygon`),ST_PolygonFromText('Point({$long} {$lat})'))>0))";
            } elseif ($where['deliver_type_pc'] == 1) {
                $condition_where .= " AND ((`m`.`delivery_range_type`=0 AND `m`.`deliver_type` IN (0, 3)  AND ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < `m`.`delivery_radius`*1000)";
                $condition_where .= " OR (`m`.`delivery_range_type`=1 AND ST_Contains(ST_PolygonFromText(`m`.`delivery_range_polygon`),ST_PolygonFromText('Point({$long} {$lat})'))>0))";
            } elseif ($where['deliver_type_pc'] == 2) {
                $condition_where .= " AND `m`.`deliver_type` IN (2, 3, 4)";
            } elseif ($where['deliver_type_pc'] == 5) {
                $condition_where .= " AND `m`.`deliver_type`=5";
            } else {
                $condition_where .= " AND (`m`.`deliver_type` IN (2, 3, 4, 5) OR (`m`.`delivery_range_type`=0 AND `m`.`deliver_type` IN (0, 1) AND ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < `m`.`delivery_radius`*1000)";
                $condition_where .= " OR (`m`.`delivery_range_type`=1 AND ST_Contains(ST_PolygonFromText(`m`.`delivery_range_polygon`),ST_PolygonFromText('Point({$long} {$lat})'))>0))";
            }
        } else {
            //自提店铺搜索最大范围
            $length = is_null(C('config.pick__max_search_kilometer')) ? 20 : C('config.pick__max_search_kilometer');
            $maxLong = $length * 1000;

            if ($deliver_type == 'delivery') {
                // $condition_where .= " AND `m`.`deliver_type`<>2";
                $condition_where .= " AND ((`m`.`delivery_range_type`=0 AND `m`.`deliver_type`<>2 AND ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < `m`.`delivery_radius`*1000)";
                $condition_where .= " OR (`m`.`delivery_range_type`=1 AND `m`.`deliver_type`<>2 AND ST_Contains(ST_PolygonFromText(`m`.`delivery_range_polygon`),ST_PolygonFromText('Point({$long} {$lat})'))>0))";
            } elseif ($deliver_type == 'pick') {
                $condition_where .= " AND `m`.`deliver_type` IN (2, 3, 4)";
                $condition_where .= " AND ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < $maxLong ";
            } elseif ($deliver_type == 'offline') {
                $condition_where .= " AND `m`.`open_offline`='1'";
            }  elseif ($deliver_type == 'skip') { 
                //跳过配送限制
            } else {
                //自提店铺搜索最大范围
                $length = is_null(C('config.pick__max_search_kilometer')) ? 20 : C('config.pick__max_search_kilometer');
                $maxLong = $length * 1000;
                $condition_where .= " AND (";
                $condition_where .= "(`m`.`deliver_type` IN (2, 3, 4, 5)";
                if ($maxLong > 0) {
                    $condition_where .= " AND ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < $maxLong)";
                } else {
                    $condition_where .= ')';
                }

                $condition_where .= " OR (`m`.`delivery_range_type`=0 AND `m`.`deliver_type` IN (0, 1,3,4) AND ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) < `m`.`delivery_radius`*1000)";
                $condition_where .= " OR (`m`.`delivery_range_type`=1 AND ST_Contains(ST_PolygonFromText(`m`.`delivery_range_polygon`),ST_PolygonFromText('Point({$long} {$lat})'))>0))";
            }
        }
        
        if (isset($where['key']) && $where['key']) {
			if ($is_search) {
				if($where['search_type'] == 'barcode'){
					$condition_where .= " AND `s`.`status` = 1 AND (`g`.`number`='{$where['key']}')";
				}else{
					$condition_where .= " AND `s`.`status` = 1 AND (`s`.`"._field("name")."` LIKE '%{$where['key']}%' OR `m`.`search_keywords` LIKE '%{$where['key']}%' OR `g`.`name` LIKE '%{$where['key']}%')";
				}
			} else {
				$condition_where .= " AND `s`.`"._field("name")."` LIKE '%{$where['key']}%'";
			}
        }
        
        if ($cat_id) {
            $category = M((_view('Shop_category')))->where(array('cat_id' => $cat_id))->find();
        } elseif ($cat_fid) {
            $category = M((_view('Shop_category')))->where(array('cat_id' => $cat_fid))->find();
        }
        $show_method = isset($category) && $category ? $category['show_method'] : (isset($where['show_method']) ? $where['show_method'] : 2);

        //商家入驻缴费开关
		$merchant_settle_in = C('customization.merchant_settle_in');
		$mer_field = '';
		if($merchant_settle_in == 1){
			$mer_field = 'mm.settle_in_money, mm.settle_in_sign,';
		}
		
        $condition_field = 's.*, m.*, mm.isverify,'.$mer_field.'mm.logo as `merchant_logo`';
        $order = '';
        if(C('config.shop_sort_top')){//手动排序置顶优先
            $order = '`m`.`sort` DESC, '.$order;
        }
        $time = date('H:i:s');
        if ($show_method == 2) { // 靠后显示
            $condition_field .= ",(CASE
	        WHEN (`s`.`is_business_open`='1') then 2
	        WHEN (`m`.`is_reserve` = 1) then 1
	        ELSE 0
	        END) as `t_sort`";
            $order .= '`t_sort` DESC, ';
            $order .= '`m`.`is_close` ASC, ';
        } elseif ($show_method == 0) { // 不显示
            $condition_where .= " AND `s`.`is_business_open`= 1";
            $condition_where .= " AND `m`.`is_close`= 0";
        }
        if ($order_str == 'basic_price') {
            if (C('config.basic_price')) {
                $condition_field .= ",(CASE
			WHEN ((`m`.`deliver_type`=0 OR `m`.`deliver_type`=3) AND `m`.`s_basic_price` > 0) then `m`.`s_basic_price`
			WHEN ((`m`.`deliver_type`=0 OR `m`.`deliver_type`=3) AND `m`.`s_basic_price`=0) then " . C('config.basic_price') . "
			ELSE `m`.`basic_price`
			END) as `t_basic_price`";
            } else {
                $condition_field .= ",(CASE
			WHEN ((`m`.`deliver_type`=0 OR `m`.`deliver_type`=3) AND `m`.`s_basic_price` > 0) then `m`.`s_basic_price`
			WHEN ((`m`.`deliver_type`=0 OR `m`.`deliver_type`=3) AND `m`.`s_basic_price`=0) then `m`.`basic_price`
			ELSE `m`.`basic_price`
			END) as `t_basic_price`";
            }
			//此处商家配送，多时间段不同起送价时会有排序问题。筛选是按照 basic_price标准起送价，但是后续在展示时使用了时间段的起送价。
        }

        //特色筛选
        $tm = time();
        $dbPrefix = C('DB_PREFIX');
        $specialArray = explode('-',$special);
        $specConditionArr = [];
        if (in_array('ppsj', $specialArray)) {
            //品牌商家
            $specConditionArr[]='`mm`.`isverify`= 1';
        }
        if (in_array('jdlq', $specialArray)) {
            //进店领券
            $specConditionArr[]='`m`.`jdlq`= 1';
        }
        if (in_array('mjyh', $specialArray)) {
            //满减优惠
            $specConditionArr[]=sprintf("EXISTS (SELECT d.id FROM ".$dbPrefix."shop_discount AS d LEFT JOIN ".$dbPrefix."shop_discount_area AS da ON d.id = da.did LEFT JOIN ".$dbPrefix."shop_discount_merchant AS dm ON d.id = dm.did WHERE d.`status` = 1 AND ((d.store_id = s.store_id) OR ( d.start_time <= %d AND d.end_time >= %d AND (( d.use_type = 0 OR FIND_IN_SET(s.store_id, dm.store_ids)) OR ( d.use_area = 0 OR s.city_id = da.aid )))) LIMIT 1)", $tm, $tm);
        }
        if (in_array('sdlj', $specialArray)) {
            //首单立减
            $specConditionArr[]= sprintf("EXISTS (SELECT d.id FROM ".$dbPrefix."shop_discount AS d LEFT JOIN ".$dbPrefix."shop_discount_area AS da ON d.id = da.did LEFT JOIN ".$dbPrefix."shop_discount_merchant AS dm ON d.id = dm.did WHERE d.`type` = 0 AND d.`status` = 1 AND ((d.store_id = s.store_id) OR ( d.start_time >= %d AND d.end_time <= %d AND (( d.use_type = 0 OR FIND_IN_SET(s.store_id, dm.store_ids)) OR ( d.use_area = 0 OR s.city_id = da.aid )))) LIMIT 1)", $tm, $tm);
        }
        if (in_array('zksp', $specialArray)) {
            //折扣商品
            $w = date('w');
            $today = date('Y-m-d');
            $subSql = 'SELECT g.goods_id FROM ' . $dbPrefix . 'shop_goods AS g INNER JOIN ' . $dbPrefix . 'time_limited_discount_goods AS t ON t.goods_id = g.goods_id INNER JOIN ' . $dbPrefix . 'shop_goods_sort AS sort ON sort.sort_id = g.sort_id 
                WHERE g.status=1 AND sort.status=1 AND t.is_del=0 AND g.store_id = s.store_id AND FIND_IN_SET(%d, t.week) AND UNIX_TIMESTAMP( CONCAT(\'%s\'," ", end_time )) > %d AND UNIX_TIMESTAMP( CONCAT( \'%s\', "", start_time )) < %d LIMIT 1';
            $subSql = sprintf($subSql, $w, $today, $tm, $today, $tm);

            $specConditionArr[] = "EXISTS ($subSql)";
        }
        if ($deliver_type != 'pick' && in_array('lyqs', $specialArray)) {
            //0元起送
            $specConditionArr[] =  "`m`.`zero_up_to_send`= 1";
        }
        if ($deliver_type != 'pick' && in_array('mpsf', $specialArray)) {
            //免配送费
            $specConditionArr[] = "`m`.`free_delivery`= 1";
        }
        
        if($specConditionArr){
            $condition_where .= ' AND ( ';
            if(C('config.shop_index_search_rule') == 0){
                $condition_where .= implode(" OR ",$specConditionArr);
            }else{
                $condition_where .= implode(" AND ",$specConditionArr);
            }
            $condition_where .= ') ';
        }

        //判断是否是员工专卖店铺
        $setEmployeeCardMerId = C('config.set_employee_card_mer_id') ?: '';
        if($setEmployeeCardMerId){
            $condition_where .= " AND `s`.`mer_id` NOT IN (".$setEmployeeCardMerId.")";
        }
        // 排序
        switch ($order_str) {
            case 'score_mean':
                $order .= '`m`.`score_mean` DESC,`m`.`sort` DESC';
                break;
            case 'create_time':
                $order .= '`m`.`create_time` DESC,`m`.`sort` DESC';
                break;
            case 'sale_count':
                $order .= '`m`.`sale_count`+`m`.`virtual_sale_count` DESC,`m`.`sort` DESC';
                break;
            case 'basic_price':
                $order .= 't_basic_price ASC,`m`.`sort` DESC';
                break;
            case 'delivery_fee':
                $order .= '`m`.`delivery_fee` ASC,`m`.`sort` DESC';
                break;
            case 'store_id':
                $order .= '`s`.`store_id` ASC, `m`.`sort` DESC';
                break;
			case 'send_time':
            case 'juli': // 智能排序
                $condition_field .= ", ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                $order .= '`m`.`sort` DESC, juli ASC,`s`.`store_id` DESC';
                break;
			case 'only_juli': // 距离排序
                $condition_field .= ", ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                //$order .= 'juli ASC,`s`.`store_id` DESC';
                //距离优先排序
                $order = 'juli ASC,' . $order . '`s`.`store_id` DESC';
                break;
			case 'preference_sort':
                $order .= '`m`.`preference_sort` DESC,`m`.`sort` DESC';
                break;
            case 'recommend_sort':
                $order .= '`m`.`recommend_sort` DESC,`m`.`sort` DESC';
                break;
            default:
                $condition_field .= ", ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";
                $order .= '`m`.`sort` DESC, juli ASC,`s`.`store_id` DESC';
                break;
        }
        
        //根据分类查找对应的店铺ID
        if ($cat_fid && $cat_id) {
            $relation = D('Shop_category_relation')->where(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id))->select();
        } elseif ($cat_fid) {
            if($checkTime){
                $cateWhere = sprintf("cat_fid=" . $cat_fid . " AND (effect_date_type = 1 OR (start_date <= '%s' AND end_date >= '%s' AND FIND_IN_SET(%d,week))) AND (effect_time_type = 1 OR (start_time <= '%s' AND end_time >= '%s'))", $today, $today, $week, $time, $time);
                $showCateIds = D('Shop_category')->where($cateWhere)->getField('cat_id', true);
                if ($showCateIds) {
                    $relation = D('Shop_category_relation')->where(array('cat_id' => ['IN', $showCateIds]))->select();
                } else {
                    $relation = [];
                }
            }else{
                $relation = D('Shop_category_relation')->where(array('cat_fid' => $cat_fid))->select();
            }
        } elseif($cat_id) {
            $relation = D('Shop_category_relation')->where(array('cat_id' => $cat_id))->select();
        } else {
            // 全部分类下没有分类的店铺不显示
            if ($checkTime) {
                $cateWhere = sprintf("(effect_date_type = 1 OR (start_date <= '%s' AND end_date >= '%s' AND FIND_IN_SET(%d,week))) AND (effect_time_type = 1 OR (start_time <= '%s' AND end_time >= '%s'))", $today, $today, $week, $time, $time);
                $showCateIds = D('Shop_category')->where($cateWhere)->getField('cat_id', true);
                if ($showCateIds) {
                    $relation = D('Shop_category_relation')->where(array('cat_id' => ['IN', $showCateIds]))->select();
                } else {
                    $relation = [];
                }
            } else {
                $relation = D('Shop_category_relation')->select();
            }
        }
        $store_ids = array();
        foreach ($relation as $r) {
            if (! in_array($r['store_id'], $store_ids)) {
                $store_ids[] = $r['store_id'];
            }
        }
        if ($store_ids) {
            if ($where['store_ids']) {//店铺id的条件
                if(!is_array($where['store_ids'])){
                    $where['store_ids'] = explode(',',$where['store_ids']);
                }
                $store_ids = array_intersect($store_ids, $where['store_ids']);
            }
            if($store_ids){
                $condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';

            }else{
                return array(
                    'shop_list' => null,
                    'pagebar' => null,
                    'total' => 0,
                    'next_page' => 0
                );
            }
        } else {
            return array(
                'shop_list' => null,
                'pagebar' => null,
                'total' => 0,
                'next_page' => 0
            );
        }

		//没有告知取多少条，则统计。否则不统计，影响效率
		if(empty($limit)){
			$sql_count = "SELECT count(distinct(`s`.store_id)) as count FROM " . C('DB_PREFIX') . _view("merchant_store")." AS s";
			$sql_count .= " INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop as m ON m.store_id=s.store_id";
			$sql_count .= " LEFT JOIN " . C('DB_PREFIX') . "merchant AS mm ON s.mer_id=mm.mer_id";
			if ($is_search) {
				$sql_count .= " LEFT JOIN " . C('DB_PREFIX') . "shop_goods AS g ON g.store_id=m.store_id";
				$sql_count .= " WHERE {$condition_where} AND mm.status = 1";
			} else {
				$sql_count .= " WHERE {$condition_where} AND mm.status = 1";
			}
			$count = $this->query($sql_count);
			$total = isset($count[0]['count']) ? $count[0]['count'] : 0;
			if ($is_wap == 1) {
				$page = isset($where['page']) ? intval($where['page']) : 1;
				$pagesize = $pageSize ? $pageSize : 20;
				$totalPage = ceil($total / $pagesize);
				$pagetotal = $totalPage;
				$star = $pagesize * ($page - 1);
				$return['has_more'] = $totalPage > $page ? true : false;
			} elseif ($is_wap == 2) {
				$page = isset($where['page']) ? intval($where['page']) : 1;
				$pagesize = 10;
				$totalPage = ceil($total / $pagesize);
				
				$star = $pagesize * ($page - 1);
				$return['next_page'] = $totalPage > $page ? intval($page + 1) : 0;
			} elseif ($is_wap == 3) {
				$page = isset($where['page']) ? intval($where['page']) : 1;
				$pagesize = 10;
				$pagetotal = ceil($total / $pagesize);
				$star = $pagesize * ($page - 1);
				$return['has_more'] = $pagetotal > $page ? true : false;
			}  elseif($is_wap == 4){
				$page = isset($where['page']) ? intval($where['page']) : 1;
				$pagesize = 10;
				$pagetotal = ceil($total / $pagesize);
				$star =$page;
				$return['has_more'] = $pagetotal > $page ? true : false;
			} else if ($is_wap == 5) {
				$page = isset($where['page']) ? intval($where['page']) : 1;
				$pagesize = 10;
				$totalPage = ceil($total / $pagesize);
				$pagetotal = $totalPage;
				$star = $pagesize * ($page - 1);
				$return['has_more'] = $totalPage > $page ? true : false;
			}else {
				import('@.ORG.group_page');
				$p = new Page($total, C('config.meal_page_row'), C('config.meal_page_val'));
				$star = $p->firstRow;
				$pagesize = $p->listRows;
			}
        }else{
			$star = 0;
			$pagesize = $limit;
		}
        if ($is_search) {
            $sql = "SELECT {$condition_field} FROM " . C('DB_PREFIX') ._view("merchant_store"). " AS s";
            $sql .= " INNER JOIN " . C('DB_PREFIX') ._view("merchant_store_shop"). " AS m ON m.store_id=s.store_id";
            $sql .= " LEFT JOIN " . C('DB_PREFIX') . "merchant AS mm ON mm.mer_id = s.mer_id";
            $sql .= " LEFT JOIN " . C('DB_PREFIX') . "shop_goods AS g ON g.store_id=m.store_id";
            $sql .= " WHERE {$condition_where} AND mm.status = 1 GROUP BY `m`.store_id";
            $sql .= " ORDER BY {$order}";
            $sql .= " LIMIT {$star}, {$pagesize}";
            
            $result = $this->query($sql);
            $res = array();
            $store_ids = array();
            foreach ($result as $r) {
                if (! in_array($r['store_id'], $store_ids)) {
                    $store_ids[] = $r['store_id'];
                }
                $res[$r['store_id']] = $r;
            }
            if (empty($store_ids)) {
                return array(
                    'shop_list' => null,
                    'pagebar' => null,
                    'total' => 0,
                    'next_page' => 0
                );
            }
            
			$goods_image_class = new goods_image();
            
            $good_where = array('status' => 1, 'store_id' => array('in', $store_ids), 'name' => array('like', '%' . $where['key'] . '%'));
			if($where['search_type'] == 'barcode'){
				unset($good_where['name']);
				$good_where['number'] = $where['key'];
			}
            $goods_list = D(_view('Shop_goods'))->field(true)->where($good_where)->select();
            		
            $sort_goods_list = array();
            $sort_ids = array();
            foreach ($goods_list as $goods) {
                //重置库存
                $twStock = D('Shop_goods')->queryTwGoodsStock($goods['goods_id']);
                if (!is_null($twStock)) {
                    $goods['stock_num'] = getFormatNumber($twStock);
                }

                if (isset($sort_goods_list[$goods['sort_id']])) {
                    $sort_goods_list[$goods['sort_id']][] = $goods;
                } else {
                    $sort_goods_list[$goods['sort_id']] = array($goods);
                }
                if (!in_array($goods['sort_id'], $sort_ids)) {
                    $sort_ids[] = $goods['sort_id'];
                }
            }
            $sort_list = D(_view('Shop_goods_sort'))->field(true)->where(array('sort_id' => array('in', $sort_ids),'status' => '1','sort_status' => '1'))->select();

            // 获得正常显示的分类
            //$showSortIds = D('Shop_goods_sort')->getAllShowChildSortIds(0, $sort_ids, true, true, false, true);
            $sortShowArr = [];
            $sortMod = D('Shop_goods_sort');

            $today = date('w');
            $goods_list = array();
            foreach ($sort_list as $st) {
                if($st['week']){
                    $week_arr = explode(',', $st['week']);
                    if (!in_array($today, $week_arr)) {
                        continue;
                    }
                }

                //判断分类是否展示
                if (isset($sortShowArr[$st['sort_id']]) && $sortShowArr[$st['sort_id']] == false) {
                    continue;
                }
                $isSortShow = $sortMod->testFidIsShowBySortId($st['sort_id']);
                $sortShowArr[$st['sort_id']] = $isSortShow;
                if ($isSortShow == false) {
                    continue;
                }

                $goods_list = array_merge($goods_list, $sort_goods_list[$st['sort_id']]);
            }
            foreach ($goods_list as $goods) {
                $goods['price'] = getFormatNumber($goods['price']);
                $goods['search_name'] = str_replace($where['key'], '<font color="#06c1ae">' . $where['key'] . '</font>', $goods['name']);
				
				$images = $goods_image_class->get_allImage_by_path($goods['image']);
				$goods['image'] = $images ? array_shift($images) : '';
				$goods['image'] = thumb_img($goods['image']['s_image'],240,240,'fill');
				
				$tmpGoodNameStr = str_replace($where['key'], '&^&' . $where['key'] . '&^&', $goods['name']);
				$tmpGoodNameArr = explode('&^&',$tmpGoodNameStr);
				$goods['search_name_arr'] = array();
				foreach($tmpGoodNameArr as $tmpValue){
					if(!empty($tmpValue)){
						$goods['search_name_arr'][] = array(
							'value'   => $tmpValue,
							'is_high' => $tmpValue == $where['key'] ? true : false
						);
					}
				}

				//限时优惠价格库存展示
                $time_limited_goods = D('Time_limited_discount_goods')->get_discount_price_by_goodsid($goods['goods_id']);
                if ($time_limited_goods['is_discount'] == 1 && ($time_limited_goods['stock'] > 0 || $time_limited_goods['stock'] == -1)) {
                    $goods['is_seckill'] = 1;
                    $goods['seckill_price'] = getFormatNumber($time_limited_goods['discount_price']);
                    $goods['seckill_stock'] = $time_limited_goods['stock'];
                    $goods['seckill_discount'] = round($goods['seckill_price'] * 10 / $goods['price'],1);
                } else {
                    $goods['is_seckill'] = 0;
                    $goods['seckill_discount'] = 10;
                }
                if(!empty($where['goods_num_limit']) && isset($res[$goods['store_id']]['goods_list']) && count($res[$goods['store_id']]['goods_list']) == $where['goods_num_limit']){
                    continue;
                }
                if (isset($res[$goods['store_id']]['goods_list'])) {
                    $res[$goods['store_id']]['goods_list'][] = $goods;
                } else {
                    $res[$goods['store_id']]['goods_list'] = array($goods);
                }
            }
			
			//兼容商品有情况搜索不出来时，店铺展示出来的问题
			foreach($res as $key => $value){
				if(empty($value['goods_list']) && stripos($value['name'],$where['key']) === false && stripos($value['search_keywords'],$where['key']) === false){
					unset($res[$key]);
				}
			}
			$res = array_values($res);
        } else {
            //个人中心获取收藏店铺列表特殊处理
            $isEmpty = false;
            if (GROUP_NAME == 'Appapi' && ((MODULE_NAME == 'My' && ACTION_NAME == 'my_collect') || (MODULE_NAME == 'HouseApi' && ACTION_NAME == 'getVillageGuessLikeStoreList'))) {
                if (empty($where['store_ids'])) {
                    $isEmpty = true;
                } else {
                    $condition_where = 's.store_id IN (' . implode(',', $where['store_ids']) . ')';
                }
            }
            if ($isEmpty) {
                $res = [];
            } else {
                $sql = "SELECT {$condition_field} FROM " . C('DB_PREFIX') . _view("merchant_store") . " AS s";
                $sql .= " INNER JOIN " . C('DB_PREFIX') . _view("merchant_store_shop") . " AS m ON m.store_id=s.store_id";
                $sql .= " LEFT JOIN " . C('DB_PREFIX') . "merchant AS mm ON mm.mer_id = s.mer_id";
                $sql .= " WHERE {$condition_where} AND mm.status = 1";
                $sql .= " ORDER BY {$order}";
                $sql .= " LIMIT {$star}, {$pagesize}";
                $res = $this->query($sql);
            }
        }
        $ids = array();
        $store_ids = array();
        $latLng = array();
        $default_language = C('config.default_language');//默认语言
        foreach ($res as $kr => $r) {
            if (! in_array($r['circle_id'], $ids)) {
                $ids[] = $r['circle_id'];
            }
            //判断语言环境
            $now_lang = C("config.system_lang");
            if($now_lang && isset($r['name_'.$now_lang]) && isset($r['name']) && $now_lang != $default_language){
                $res[$kr]['name'] = $r['name_'.$now_lang];
            }
            
            if (! in_array($r['store_id'], $store_ids)) {
                $store_ids[] = $r['store_id'];
            }
            $latLng[] = $r['lat'] . ',' . $r['long'];
        }
        
//         $discounts = D('Shop_discount')->get_discount_byids($store_ids);
        $temp = array();
        if ($ids) {
            $areas = M("Area")->where(array('area_id' => array('in', $ids)))->select();
            foreach ($areas as $a) {
                $temp[$a['area_id']] = $a;
            }
        }
        
        $newLatLong = array();
        if (C('config.is_riding_distance') && $latLng && C('config.riding_only_settlement') != 1) {
            $url = 'http://api.map.baidu.com/routematrix/v2/riding?destinations=' . implode('|', $latLng) . '&origins=' . $lat . ',' . $long . '&ak=' . C('config.baidu_map_ak') . '&output=json';
            import('ORG.Net.Http');
            $http = new Http();
            $result = $http->curlGet($url);
            if ($result) {
                $result = json_decode($result, true);
                if ($result['status'] == 0) {
                    $newLatLong = $result['result'];
                }
            }
        }
        
        import('@.ORG.longlat');
        $longlat_class = new longlat();
        $store_image_class = new store_image();
        
        $shopDiscountDataBase = D('Shop_discount');
        $cardNewCouponDataBase = D('Card_new_coupon');
        $deliverSetDB = D('Deliver_set');
        $shopGoodsDB = D('Shop_goods');
        $shopGoodsSortDB = D('Shop_goods_sort');
        foreach ($res as &$v) {

            // 验证是否有三级分类
            $v['is_mult_class'] = $this->checkMultClass($v['store_id']);
            $v['isDiscountGoods'] = $shopGoodsDB->getDiscountGoods($v['store_id']);
            $v['isdiscountsort'] = $shopGoodsSortDB->checkShopDiscount($v['store_id']);
            $v['url'] = C('config.site_url') . '/shop/' . $v['store_id'] . '.html';
            $v['area_name'] = isset($temp[$v['circle_id']]) ? $temp[$v['circle_id']]['area_name'] : '';
            $images = $store_image_class->get_allImage_by_path($v['pic_info']);
            $v['image'] = $images ? array_shift($images) : '';

            if($merchant_settle_in == 1){
                //商家入驻缴费标志
                $v['settle_in_sign'] = $v['settle_in_money'] > 0 ? $v['settle_in_sign'] : '';
            } 
			
			if($where['key']){
                $v['name'] = html_entity_decode($v['name'], ENT_QUOTES);
				$tmpStoreNameStr = str_replace($where['key'], '&^&' . $where['key'] . '&^&', $v['name']);
				$tmpStoreNameArr = explode('&^&',$tmpStoreNameStr);
				$v['name_arr'] = array();
				foreach($tmpStoreNameArr as $tmpValue){
					if(!empty($tmpValue)){
						$v['name_arr'][] = array(
							'value'   => $tmpValue,
							'is_high' => $tmpValue == $where['key'] ? true : false
						);
					}
				}
			}
			
			if($v['merchant_logo']){
				$v['merchant_logo'] = replace_file_domain($v['merchant_logo']);
			}

            if($v['recommend_pic']){
                $v['recommend_pic'] = replace_file_domain($v['recommend_pic']);
            }
			
            $v['mean_money'] = floatval($v['mean_money']);
            $v['wap_url'] = U('Shop/shop', array('mer_id' => $v['mer_id'], 'store_id' => $v['store_id']));
            $v['deliver'] = $v['deliver_type'] == 2 ? false : true;
            
            if ($v['send_time_type'] == 0) {
                $v['send_time'] = $v['sort_time'];
            } elseif ($v['send_time_type'] == 1) {
                $v['send_time'] = floatval(round($v['sort_time'] / 60, 2));
            } elseif ($v['send_time_type'] == 2) {
                $v['send_time'] = floatval(round($v['sort_time'] / 1440, 2));
            } elseif ($v['send_time_type'] == 3) {
                $v['send_time'] = floatval(round($v['sort_time'] / 10080, 2));
            } elseif ($v['send_time_type'] == 4) {
                $v['send_time'] = floatval(round($v['sort_time'] / 43200, 2));
            }
            
            
            if (empty($newLatLong) || empty($newLatLong[0]['distance']['value'])) {
                if ($v['juli']) {
                    $v['range'] = getRange($v['juli']);
                    $jl = $v['juli'];
                } else {
                    if(C("config.map_config") != 'google'){
                        $location2 = $longlat_class->gpsToBaidu($v['lat'], $v['long']); // 转换腾讯坐标到百度坐标
                    }
                    else{
                        $location2 = ['lng'=>$v['long'],'lat'=>$v['lat']];
                    }
                    $jl = getDistance($location2['lat'], $location2['lng'], $lat, $long);
                    $v['range'] = getRange($jl);
                }
            } else {
                $tLatLng = array_shift($newLatLong);
                //$v['range'] = $tLatLng['distance']['text'];
                $v['range'] = getRange($tLatLng['distance']['value'], false, true);
                $jl = $tLatLng['distance']['value'];
            }

            if(!$lat || !$long){
                $v['range'] = 0;
            }

			if($v['delivery_range_type']==1){
				if($v['delivery_range_polygon']!=''){
					$v_delivery_range_polygon = str_replace('POLYGON((','',$v['delivery_range_polygon']);
					$v_delivery_range_polygon = str_replace('))','',$v_delivery_range_polygon);
					$v_polygon = explode(',',$v_delivery_range_polygon);
					foreach($v_polygon as $v_k => $v_po){
						$v_arr = explode(' ',$v_po);
						$v_polygon_arr[$v_k]['lng'] = $v_arr[0];
						$v_polygon_arr[$v_k]['lat'] = $v_arr[1];
					}
					$v_polygon_latlng[] = $v_polygon_arr;
					$r = isPtInPoly($long,$lat,$v_polygon_latlng);
					if(in_array($v['deliver_type'], array(3, 4)) && !$r){
						$v['deliver'] = 0;
					}
				}
			}else{
				if (in_array($v['deliver_type'], array(3, 4)) && $jl > $v['delivery_radius'] * 1000) {
					$v['deliver'] = 0;
				}
			}
            
            $v['state'] = 0; // 根据营业时间判断
            if ($this->checkTime($v)) {
                $v['state'] = 1;
            }
            $time = time();
            
            $discounts = $shopDiscountDataBase->getDiscounts($v['mer_id'], $v['store_id']);
            $v['merchant_coupon'] = $cardNewCouponDataBase->is_shop_coupon($v['store_id'], $v['mer_id']);
            $v['system_discount'] = isset($discounts[0]) ? $discounts[0] : null;
            $v['merchant_discount'] = isset($discounts[$v['store_id']]) ? $discounts[$v['store_id']] : null;
            
            
            $is_have_two_time = 0; // 是否是第二时段的配送显示
            $deliverReturn = $deliverSetDB->getDeliverInfo($v);
            
			if(getFormatNumber($deliverReturn['deliver_speed_hour'])){
				if ($v['send_time_type'] == 0) {
					//做单时长 + 配送时速 + 配送延迟时间
					$v['send_time'] = $v['work_time'] + round($jl / ($deliverReturn['deliver_speed_hour'] * 1000 / 60),0) + $deliverReturn['deliver_delay_time'];
				}
			}

			if(!$lat || !$long){
                $v['send_time'] = 0;
            }
			
            $delivery_fee = $deliverReturn['delivery_fee'];
            $per_km_price = $deliverReturn['per_km_price'];
            $basic_distance = $deliverReturn['basic_distance'];
            
            $delivery_fee2 = $deliverReturn['delivery_fee2'];
            $per_km_price2 = $deliverReturn['per_km_price2'];
            $basic_distance2 = $deliverReturn['basic_distance2'];
            
            $delivery_fee3 = $deliverReturn['delivery_fee3'];
            $per_km_price3 = $deliverReturn['per_km_price3'];
            $basic_distance3 = $deliverReturn['basic_distance3'];
            
            $start_time = $deliverReturn['delivertime_start'];
            $stop_time = $deliverReturn['delivertime_stop'];
            
            $start_time2 = $deliverReturn['delivertime_start2'];
            $stop_time2 = $deliverReturn['delivertime_stop2'];
            
            $start_time3 = $deliverReturn['delivertime_start3'];
            $stop_time3 = $deliverReturn['delivertime_stop3'];

            $delivery_free = $deliverReturn['delivery_free'];//免配送费1
            $delivery_free2 = $deliverReturn['delivery_free2'];//免配送费2
            $delivery_free3 = $deliverReturn['delivery_free3'];//免配送费3
            $delivery_free_money = $deliverReturn['delivery_free_money'];//满￥免配送费1
            $delivery_free_money2 = $deliverReturn['delivery_free_money2'];//满￥免配送费2
            $delivery_free_money3 = $deliverReturn['delivery_free_money3'];//满￥免配送费3
            
            $v['delivery_fee'] = $delivery_fee;

            $selectDeliverTime = 0;
            if (! ($start_time == $stop_time && $start_time == '00:00:00')) {
                $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
                $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
                if ($start_time > $stop_time) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time) {
                        $selectDeliverTime = 1;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time <= $time && $time <= $stop_time) {
                            $selectDeliverTime = 1;
                        }
                    }
                } elseif ($start_time <= $time && $time <= $stop_time) {
                    $selectDeliverTime = 1;
                }
                if (! ($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                    $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                    $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);
                    if ($start_time2 > $stop_time2) {
                        if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                            $v['delivery_fee'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                            $is_have_two_time = 1;
                        } else {
                            $stop_time2 += 86400;
                            if ($start_time2 <= $time && $time <= $stop_time2) {
                                $v['delivery_fee'] = $delivery_fee2;
                                $selectDeliverTime = 2;
                                $is_have_two_time = 1;
                            }
                        }
                    } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                        $v['delivery_fee'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                        $is_have_two_time = 1;
                    }
                }
                
                if (! ($start_time3 == $stop_time3 && $start_time2 == '00:00:00')) {
                    $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                    $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);
                    if ($start_time3 > $stop_time3) {
                        if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                            $v['delivery_fee'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                            $is_have_two_time = 1;
                        } else {
                            $stop_time3 += 86400;
                            if ($start_time3 <= $time && $time <= $stop_time3) {
                                $v['delivery_fee'] = $delivery_fee3;
                                $selectDeliverTime = 3;
                                $is_have_two_time = 1;
                            }
                        }
                    } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                        $v['delivery_fee'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                        $is_have_two_time = 1;
                    }
                }
            }else{
                $selectDeliverTime = 1;
            }
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $v['area_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $v['city_id'], 'status' => 1))->find();
                if (empty($set)) {
                    $set = D('Deliver_set')->field(true)->where(array('area_id' => $v['province_id'], 'status' => 1))->find();
                }
            }
            if($selectDeliverTime==1){
                if ($v['deliver_type'] == 0 || $v['deliver_type'] == 3) {
                    if($v['s_is_open_own']==1){
                        $v['basic_price'] = floatval($v['s_basic_price1']) > -1 ? floatval($v['s_basic_price1']) : (floatval($v['s_basic_price']) > -1 ? floatval($v['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval(C('config.basic_price1')) ? floatval(C('config.basic_price1')) : floatval(C('config.basic_price')))));//起送价
                    }else{
                        $v['basic_price'] =floatval($v['s_basic_price']) > -1 ? floatval($v['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval(C('config.basic_price1')) ? floatval(C('config.basic_price1')) : floatval(C('config.basic_price'))));//起送价
                    }
                    $v['extra_price'] = floatval($v['s_extra_price']) ? floatval($v['s_extra_price']) : floatval(C('config.extra_price'));

                    // 平台设置的配送优惠
                    if ($delivery_free==1) {
                        $v['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                    }elseif ($delivery_free==2) {
                        $v['deliver_discount'] = array('money' => $delivery_free_money, 'type'=>'deliver_free');
                    }
                } else {
                    $v['basic_price'] = floatval($v['basic_price1'])?floatval($v['basic_price1']):floatval($v['basic_price']);//起送价
                    $v['extra_price'] = floatval($v['extra_price']);//不满起送价另付金额支持配送
                }
            }elseif($selectDeliverTime==2){
                if ($v['deliver_type'] == 0 || $v['deliver_type'] == 3) {
                    if($v['s_is_open_own']==1) {
                        $v['basic_price'] = floatval($v['s_basic_price2']) > -1 ? floatval($v['s_basic_price2']) : (floatval($v['s_basic_price']) > -1 ? floatval($v['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval(C('config.basic_price2')) ? floatval(C('config.basic_price2')) : floatval(C('config.basic_price')))));//起送价
                    }else{
                        $v['basic_price'] = floatval($v['s_basic_price']) > -1 ? floatval($v['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval(C('config.basic_price2')) ? floatval(C('config.basic_price2')) : floatval(C('config.basic_price'))));//起送价
                    }
                    $v['extra_price'] = floatval($v['s_extra_price']) ? floatval($v['s_extra_price']) : floatval(C('config.extra_price'));

                    // 平台设置的配送优惠
                    if ($delivery_free2==1) {
                        $v['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                    }elseif ($delivery_free2==2) {
                        $v['deliver_discount'] = array('money' => $delivery_free_money2, 'type'=>'deliver_free');
                    }
                } else {
                    $v['basic_price'] = floatval($v['basic_price2'])?floatval($v['basic_price2']):floatval($v['basic_price']);//起送价
                    $v['extra_price'] = floatval($v['extra_price']);//不满起送价另付金额支持配送
                }

            }elseif($selectDeliverTime==3){
                if ($v['deliver_type'] == 0 || $v['deliver_type'] == 3) {
                    if($v['s_is_open_own']==1) {
                        $v['basic_price'] = floatval($v['s_basic_price3']) > -1 ? floatval($v['s_basic_price3']) : (floatval($v['s_basic_price']) > -1 ? floatval($v['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval(C('config.basic_price3')) ? floatval(C('config.basic_price3')) : floatval(C('config.basic_price')))));//起送价
                    }else{
                        $v['basic_price'] = floatval($v['s_basic_price']) > -1 ? floatval($v['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval(C('config.basic_price3')) ? floatval(C('config.basic_price3')) : floatval(C('config.basic_price'))));//起送价
                    }
                    $v['extra_price'] = floatval($v['s_extra_price']) ? floatval($v['s_extra_price']) : floatval(C('config.extra_price'));

                    // 平台设置的配送优惠
                    if ($delivery_free3==1) {
                        $v['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                    }elseif ($delivery_free3==2) {
                        $v['deliver_discount'] = array('money' => $delivery_free_money3, 'type'=>'deliver_free');
                    }
                } else {
                    $v['basic_price'] = floatval($v['basic_price3'])?floatval($v['basic_price3']):floatval($v['basic_price']);//起送价
                    $v['extra_price'] = floatval($v['extra_price']);//不满起送价另付金额支持配送
                }
            }else{
                if ($v['deliver_type'] == 0 || $v['deliver_type'] == 3) {
                    $v['basic_price'] = floatval($v['s_basic_price']) > -1 ? floatval($v['s_basic_price']) : floatval(C('config.basic_price'));
                    $v['extra_price'] = floatval($v['s_extra_price']) ? floatval($v['s_extra_price']) : floatval(C('config.extra_price'));
                } else {
                    $v['basic_price'] = floatval($v['basic_price']);//起送价
                    $v['extra_price'] = floatval($v['extra_price']);//不满起送价另付金额支持配送
                }
            }

            $v['is_have_two_time'] = $is_have_two_time;
            if ($v['s_is_open_virtual'] == 1 && in_array($v['deliver_type'], [0, 3])) {
                $v['delivery_fee'] = $v['virtual_delivery_fee'];
            }
        }
        $return['shop_list'] = $res;
        $return['total'] = $total;
        $return['pagetotal'] = $pagetotal;
        if (! $is_wap) {
            $return['totalPage'] = $p->totalPage;
            $return['pagebar'] = $p->show();
        }
        if (empty($res) && isset($return['has_more'])) {
            $return['has_more'] = false;
        }
        return $return;
    }
    
	/**
	 * 根据条件获取商家列表
	 * @param array $where
	 * @param number $limit
	 */
	public function get_list_by_ids($ids, $params = array(),$limit)
	{
		$lat = isset($params['lat']) ? $params['lat'] : 0;
		$long = isset($params['long']) ? $params['long'] : 0;

		if (empty($ids)) return null;

        $time = date("H:i:s");
        $condition_field = 's.*,m.*';
        $order = '';
        $time = date("H:i:s");
        $condition_field .= ",(CASE
	        WHEN (`s`.`open_1`='00:00:00' and `s`.`open_2`='00:00:00' and `s`.`open_3`='00:00:00' and `s`.`close_1`='00:00:00' and `s`.`close_2`='00:00:00' and `s`.`close_3`='00:00:00') then 2
	        WHEN (`m`.`is_reserve` = 1) then 1
	        WHEN ((`s`.`open_1`<'$time' and `s`.`close_1`>'$time') OR (`s`.`open_2`<'$time' and `s`.`close_2`>'$time') OR (`s`.`open_3`<'$time' and `s`.`close_3`>'$time')) then 2
	        ELSE 0
	        END) as `t_sort`";
        $order .= '`t_sort` DESC, ';
        $order .= '`m`.`is_close` ASC ';

		$sql = "SELECT {$condition_field} FROM "  . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop as m ON m.store_id=s.store_id WHERE m.store_id IN ({$ids})"."ORDER BY".$order."limit {$limit}";
		$res = $this->query($sql);
		$ids = array();
		$store_ids = array();
		foreach ($res as $r) {
			if (!in_array($r['circle_id'], $ids)) {
				$ids[] = $r['circle_id'];
			}
			if (!in_array($r['store_id'], $store_ids)) {
				$store_ids[] = $r['store_id'];
			}
		}
		
		$temp = array();
		if ($ids) {
			$areas = M("Area")->where(array('area_id' => array('in', $ids)))->select();
			foreach ($areas as $a) {
				$temp[$a['area_id']] = $a;
			}
		}

		import('@.ORG.longlat');
		$longlat_class = new longlat();
		$store_image_class = new store_image();
		$list = array();
		
		$shopDiscountDataBase = D('Shop_discount');
		$deliverSetDB = D('Deliver_set');
		foreach ($res as $v) {
			$v['url'] = C('config.site_url') . '/shop/' . $v['store_id'] . '.html';
			$v['area_name'] = isset($temp[$v['circle_id']]) ? $temp[$v['circle_id']]['area_name'] : '';
			$images = $store_image_class->get_allImage_by_path($v['pic_info']);
			$v['image'] = $images ? array_shift($images) : array();
			$v['mean_money'] = floatval($v['mean_money']);
			$v['wap_url'] = U('Shop/shop', array('mer_id' => $v['mer_id'], 'store_id' => $v['store_id']));
			$v['deliver'] = $v['deliver_type'] == 2 ? false : true;
			if ($v['send_time_type'] == 0) {
			    $v['send_time'] = $v['sort_time'];
			} elseif ($v['send_time_type'] == 1) {
			    $v['send_time'] = floatval(round($v['sort_time'] / 60, 2));
			} elseif ($v['send_time_type'] == 2) {
			    $v['send_time'] = floatval(round($v['sort_time'] / 1440, 2));
			} elseif ($v['send_time_type'] == 3) {
			    $v['send_time'] = floatval(round($v['sort_time'] / 10080, 2));
			} elseif ($v['send_time_type'] == 4) {
			    $v['send_time'] = floatval(round($v['sort_time'] / 43200, 2));
			}
			$location2 = $longlat_class->gpsToBaidu($v['lat'], $v['long']);//转换腾讯坐标到百度坐标
			$jl = getDistance($location2['lat'], $location2['lng'], $lat, $long);
			$v['range'] = getRange($jl);

			if (in_array($v['deliver_type'], array(3, 4)) && $jl > $v['delivery_radius'] * 1000) {
				$v['deliver'] = 0;
			}

			$v['state'] = 0;//根据营业时间判断
			$time = time();
			if ($this->checkTime($v)) {
			    $v['state'] = 1;
			}

			$discounts = $shopDiscountDataBase->getDiscounts($v['mer_id'], $v['store_id']);
			
			$v['system_discount'] = isset($discounts[0]) ? $discounts[0] : null;
			$v['merchant_discount'] = isset($discounts[$v['store_id']]) ? $discounts[$v['store_id']] : null;

			$is_have_two_time = 0; // 是否是第二时段的配送显示
			$deliverReturn = $deliverSetDB->getDeliverInfo($v);
			
			$delivery_fee = $deliverReturn['delivery_fee'];
			$per_km_price = $deliverReturn['per_km_price'];
			$basic_distance = $deliverReturn['basic_distance'];
			
			$delivery_fee2 = $deliverReturn['delivery_fee2'];
			$per_km_price2 = $deliverReturn['per_km_price2'];
			$basic_distance2 = $deliverReturn['basic_distance2'];
			
			$delivery_fee3 = $deliverReturn['delivery_fee3'];
			$per_km_price3 = $deliverReturn['per_km_price3'];
			$basic_distance3 = $deliverReturn['basic_distance3'];
			
			$start_time = $deliverReturn['delivertime_start'];
			$stop_time = $deliverReturn['delivertime_stop'];
			
			$start_time2 = $deliverReturn['delivertime_start2'];
			$stop_time2 = $deliverReturn['delivertime_stop2'];
			
			$start_time3 = $deliverReturn['delivertime_start3'];
			$stop_time3 = $deliverReturn['delivertime_stop3'];
			
			$v['delivery_fee'] = $delivery_fee;
			
			if (! ($start_time == $stop_time && $start_time == '00:00:00')) {
			    $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
			    $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
			    if ($start_time > $stop_time) {
			        $stop_time += 86400;
			    }
			    if (! ($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
			        $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
			        $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);
			        if ($start_time2 > $stop_time2) {
			            if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
			                $v['delivery_fee'] = $delivery_fee2;
			                $is_have_two_time = 1;
			            } else {
			                $stop_time2 += 86400;
			                if ($start_time2 <= $time && $time <= $stop_time2) {
			                    $v['delivery_fee'] = $delivery_fee2;
			                    $is_have_two_time = 1;
			                }
			            }
			        } elseif ($start_time2 <= $time && $time <= $stop_time2) {
			            $v['delivery_fee'] = $delivery_fee2;
			            $is_have_two_time = 1;
			        }
			    }
			    
			    if (! ($start_time3 == $stop_time3 && $start_time2 == '00:00:00')) {
			        $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
			        $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);
			        if ($start_time3 > $stop_time3) {
			            if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
			                $v['delivery_fee'] = $delivery_fee3;
			                $is_have_two_time = 1;
			            } else {
			                $stop_time3 += 86400;
			                if ($start_time3 <= $time && $time <= $stop_time3) {
			                    $v['delivery_fee'] = $delivery_fee3;
			                    $is_have_two_time = 1;
			                }
			            }
			        } elseif ($start_time3 <= $time && $time <= $stop_time3) {
			            $v['delivery_fee'] = $delivery_fee3;
			            $is_have_two_time = 1;
			        }
			    }
			}
			
			if ($v['deliver_type'] == 0 || $v['deliver_type'] == 3) {
			    $v['basic_price'] = floatval($v['s_basic_price']) > -1? floatval($v['s_basic_price']) : (C('config.basic_price') ? floatval(C('config.basic_price')) : floatval($v['basic_price']));
			}

			$v['is_have_two_time'] = $is_have_two_time;
            if($v['s_is_open_virtual']==1){
                $v['delivery_fee'] = $v['virtual_delivery_fee'];
            }
			$list[$v['store_id']] = $v;
		}
		$return['shop_list'] = $list;
		return $return;
	}



	public function get_qrcode($id,$type='shop'){
		$condition_store['store_id'] = $id;
		$now_store = $this->field('`store_id`,`qrcode_id`')->where($condition_store)->find();
		if(empty($now_store)){
			return false;
		}
        if($type=='mall'){
            $now_store['qrcode_id']=$now_store['mall_qrcode_id'];
        }
		return $now_store;
	}
	public function save_qrcode($id,$qrcode_id,$type='shop'){
		$condition_store['store_id'] = $id;
        if($type=='mall'){
            $data_store['mall_qrcode_id'] = $qrcode_id;
        }else{
            $data_store['qrcode_id'] = $qrcode_id;
        }
		if($this->where($condition_store)->data($data_store)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_("保存二维码至X1失败！请重试。",array("X1" => C('config.group_alias_name'))))); 
		}
	}
	public function del_qrcode($id){
		$condition_store['store_id'] = $id;
		$data_store['qrcode_id'] = '';
		if($this->where($condition_store)->data($data_store)->save()){
			return(array('error_code'=>false));
		}else{
			return(array('error_code'=>true,'msg'=>L_("保存二维码至X1失败！请重试。",array("X1" => C('config.group_alias_name'))))); 
		}
	}

	public function get_store_by_search($where,$shop_type=1,$pagesize=10)
	{
		$cat_id = isset($where['cat_id']) ? $where['cat_id'] : 0;
		$cat_fid = isset($where['cat_fid']) ? $where['cat_fid'] : 0;


// 		$condition_where = "s.city_id='" . C('config.now_city') . "' AND s.status=1 AND s.store_id=m.store_id AND s.have_shop=1";
        if($shop_type==2){
            $condition_where = "s.status=1 AND s.store_id=m.store_id AND s.have_shop=1 AND m.store_theme=1 AND s.shop_on_sale=1";
        }else{
            $condition_where = "s.status=1 AND s.store_id=m.store_id AND s.have_shop=1 AND m.store_theme=1 AND ((m.is_close_shop=0 AND m.store_theme=1) OR m.store_theme=0) AND s.mall_on_sale=1";
        }
		if (C('config.store_shop_auth') == 1) {
			$condition_where .= " AND s.auth>2";
		}
		if (isset($where['key']) && $where['key']) {
			$condition_where .= " AND `s`.`name` LIKE '%{$where['key']}%'";
		}

		if ($cat_fid || $cat_id) {
			if ($cat_fid && $cat_id) {
				$relation = D('Shop_category_relation')->where(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id))->select();
			} elseif ($cat_fid) {
				$relation = D('Shop_category_relation')->where(array('cat_fid' => $cat_fid))->select();
			} else {
				$relation = D('Shop_category_relation')->where(array('cat_id' => $cat_id))->select();
			}
			$store_ids = array();
			foreach ($relation as $r) {
				if (!in_array($r['store_id'], $store_ids)) {
					$store_ids[] = $r['store_id'];
				}
			}

            if ($store_ids) {
                // $condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';
            } else {
                return array('shop_list' => null, 'pagebar' => null, 'total' => 0, 'next_page' => 0);
            }
			
		}

        if ($where['store_id']) {
            // 取交集
            $store_ids && $where['store_id'] = array_intersect($store_ids, $where['store_id']);
            if ($where['store_id']) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $where['store_id']) . ')';
            } else {
                return array('shop_list' => null, 'pagebar' => null, 'total' => 0, 'next_page' => 0);
            }
        }else{
            if ($store_ids) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';
            }
        }


		$sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop as m ON m.store_id=s.store_id WHERE {$condition_where}";
		$count = $this->query($sql_count);
		$total = isset($count[0]['count']) ? $count[0]['count'] : 0;

		$page = isset($where['page']) ? intval($where['page']) : 1;

		$totalPage = ceil($total / $pagesize);
		$star = $pagesize * ($page - 1);
		$return['next_page'] = $totalPage > $page ? intval($page + 1) : 0;
		$return['total_page'] = $totalPage;
		$return['total'] = $total;


		$sql = "SELECT `s`.`name`, `s`.`pic_info`, `s`.`store_id`, `m`.`score_mean` FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop as m ON m.store_id=s.store_id WHERE {$condition_where} LIMIT {$star}, {$pagesize}";
		$res = $this->query($sql);

		$store_ids = array();
		$store_list = array();
		$store_image_class = new store_image();
		foreach ($res as $v) {
            $v['url'] = D('House_village')->base_url . 'pages/mall/store_home/index?store_id='.$v['store_id'];
			$images = $store_image_class->get_allImage_by_path($v['pic_info']);
			$v['image'] = $images ? array_shift($images) : array();
			$v['goods_count'] = 0;
			$store_ids[] = $v['store_id'];
			$store_list[$v['store_id']] = $v;
		}

		$goods_list = D('Shop_goods')->field('store_id, count(1) as cnt')->where(array('store_id' => array('in', $store_ids), 'status' => 1,'cat_fid'=>array('gt',0)))->group('store_id')->select();
		foreach ($goods_list as $g) {
			if (isset($store_list[$g['store_id']])) {
				$store_list[$g['store_id']]['goods_count'] = $g['cnt'];
			}
		}
		$return['store_list'] = array_values($store_list);
		return $return;
	}
	
	
	
    /**
     * 根据条件获取快店店铺列表
     * @param array $where array(
     *  'order' => 排序，可选值（只能填写一个）['store_id'(默认)，'score_mean'(好评)，'permoney'(人均消费)]
     *  'cat_id' => 分类ID
     *  'cat_fid' => 父分类ID
     *  'area_id' => 区域ID
     *  'circle_id' => 商圈ID
     *  'key' => 搜索的关键词
     * );
     * @param number $isverify (-1 ：不筛选，0：非签约商家，1：签约商家)
     */
    public function getStores($where = array(), $isverify = -1)
    {
        $order_str = isset($where['order']) ? $where['order'] : 'juli';
        $cat_id = isset($where['cat_id']) ? $where['cat_id'] : 0;
        $cat_fid = isset($where['cat_fid']) ? $where['cat_fid'] : 0;
        $area_id = isset($where['area_id']) ? $where['area_id'] : 0;
        $circle_id = isset($where['circle_id']) ? $where['circle_id'] : 0;
    
        
        $condition_where = "s.city_id='" . C('config.now_city') . "' AND s.status=1 AND s.store_id=m.store_id AND s.have_shop=1";
        if (C('config.store_shop_auth') == 1) {
            $condition_where .= " AND s.auth>2";
        }
    
        if (isset($where['key']) && $where['key']) {
            $condition_where .= " AND `s`.`name` LIKE '%{$where['key']}%'";
        }
    
        $area_id && $condition_where .= " AND `s`.`area_id`={$area_id}";
        $circle_id && $condition_where .= " AND `s`.`circle_id`={$circle_id}";
        
        if ($cat_id) {
            $category = M((_view('Shop_category')))->where(array('cat_id' => $cat_id))->find();
        } elseif ($cat_fid) {
            $category = M((_view('Shop_category')))->where(array('cat_id' => $cat_fid))->find();
        }
        $show_method = isset($category) && $category ? $category['show_method'] : 2;
    
    
        $condition_field = 's.*, m.*,mm.isverify';
        
        //排序
        $order = '';
        switch($order_str){
            case 'score_mean':
                $order .= '`m`.`score_mean` DESC,`s`.`store_id` DESC';
                break;
            case 'permoney':
                $order .= '`m`.`mean_money` ASC,`s`.`store_id` DESC';
                break;
            case 'store_id':
                $order .= '`s`.`store_id` ASC';
                break;
            default:
                $order .= '`s`.`store_id` ASC';
                break;
        }

        $mod = new Model();
        if ($cat_fid || $cat_id) {
            if ($cat_fid && $cat_id) {
                $relation = D('Shop_category_relation')->where(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id))->select();
            } elseif ($cat_fid) {
                $relation = D('Shop_category_relation')->where(array('cat_fid' => $cat_fid))->select();
            } else {
                $relation = D('Shop_category_relation')->where(array('cat_id' => $cat_id))->select();
            }
            $store_ids = array();
            foreach ($relation as $r) {
                if (!in_array($r['store_id'], $store_ids)) {
                    $store_ids[] = $r['store_id'];
                }
            }
            if ($store_ids) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';
            } else {
                return array('shop_list' => null, 'pagebar' => null, 'total' => 0, 'next_page' => 0);
            }
        }

        if ($isverify != -1) {
            $condition_where .= ' AND mm.isverify=' . $isverify;
            $condition_where .= ' AND mm.status=1';
            $sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop as m ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "merchant as mm ON mm.mer_id=s.mer_id WHERE {$condition_where}";
        } else {
            $condition_where .= ' AND mm.status=1';
            // $sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop as m ON m.store_id=s.store_id WHERE {$condition_where}";
            $sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop as m ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "merchant as mm ON mm.mer_id=s.mer_id WHERE {$condition_where}";
        }
        $count = $this->query($sql_count);
        
        $total = isset($count[0]['count']) ? $count[0]['count'] : 0;
        $nowPage = isset($where['p']) ? max($where['p'], 1) : 1;
        $pagesize = isset($where['pagesize']) ? intval($where['pagesize']) : 10;
        $pagesize = $pagesize > 0 && $pagesize < 100 ? $pagesize : 10;
        $star = ($nowPage - 1) * $pagesize;
        
        $sql = "SELECT {$condition_field} FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop as m ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "merchant as mm ON mm.mer_id=s.mer_id WHERE {$condition_where} ORDER BY {$order} LIMIT {$star}, {$pagesize}";
        $res = $this->query($sql);
        $store_ids = array();
        foreach ($res as $r) {
            if (!in_array($r['store_id'], $store_ids)) {
                $store_ids[] = $r['store_id'];
            }
        }

        $items = D((_view('Shop_category')))->field(true)->order('cat_id DESC')->select();
        $tmpMap = array();
        foreach ($items as $item) {
            $tmpMap[$item['cat_id']] = $item;
        }

        $store_image_class = new store_image();
        foreach ($res as &$v) {
            $v['url'] = C('config.site_url') . '/shop/' . $v['store_id'] . '.html';
            $images = $store_image_class->get_allImage_by_path($v['pic_info']);
            $v['image'] = $images ? array_shift($images) : array();
            $v['mean_money'] = floatval($v['mean_money']);
            $catNamef = isset($tmpMap[$v['cat_fid']]) ? $tmpMap[$v['cat_fid']]['cat_name'] : '';
            $catName = isset($tmpMap[$v['cat_id']]) ? $tmpMap[$v['cat_id']]['cat_name'] : '';
            $v['cat_name'] = $catNamef ? $catNamef . ($catName ? '-' . $catName : '') : $catName;
        }
        
        $return['shop_list'] = $res;
        $return['total'] = $total;
        $return['total_page'] = ceil($total / $pagesize);
        return $return;
    }

    /**
     * @desc获取店铺营业时间-jjc
     * @param $v
     * @return string
     */
//    public function getBuniessName($v){
//        if ($v['open_1'] == '00:00:00' && ($v['close_1'] == '00:00:00' || $v['close_1'] == '23:59:00'|| $v['close_1'] == '23:59:59')){
//            return '24小时营业';
//        }
//        dump($v);
//        $time_list = array();
//        $today = strtotime(date('Y-m-d'));//当天零点时间戳
//        for($i=1;$i<=3;$i++){
//            $openKey  = 'open_'.$i;
//            $closekey = 'close_'.$i;
//            $startTime = strtotime($v[$openKey]);
//            $endTime   = strtotime($v[$closekey]);
//            $temp = array();
//            if($startTime<>$today||$endTime<>$today){
//                $temp['open_time']  = $startTime;
//                $temp['close_time'] = $endTime<$startTime?$endTime+86400:$endTime;
//                $temp['join'] = 0;
//            }
//            if(!empty($temp)){
//                $time_list[] =  $temp;
//            }
//        }
//        return $this->deal_business_time_list($time_list);
//    }
//
//    protected function deal_business_time_list($time_list){
//        if(empty($time_list)) return '不营业';
//        $return = array();
//        foreach($time_list as $key=>$val){
//            //
//            break;
//        }
//    }

    public function getBuniessName($v)
    {
//		if(isset($v['is_business_open']) && !$v['is_business_open']){
//			return '不营业';
//		}
        if ($v['open_1'] == '00:00:00' && ($v['close_1'] == '00:00:00' || $v['close_1'] == '23:59:00')) {
            //echo date('Y-m-d ' . $v['open_1']);die;
            return $buniessName = '24小时营业';
        } else {
            $ot1 = strtotime(date('Y-m-d ' . $v['open_1']));
            $ct1 = strtotime(date('Y-m-d ' . $v['close_1']));
            $ot2 = strtotime(date('Y-m-d ' . $v['open_2']));
            $ct2 = strtotime(date('Y-m-d ' . $v['close_2']));
            $ot3 = strtotime(date('Y-m-d ' . $v['open_3']));
            $ct3 = strtotime(date('Y-m-d ' . $v['close_3']));
            $s1 = 0;
            $s2 = 0;
            $s3 = 0;
            $e1 = 0;
            $e2 = 0;
            $e3 = 0;
            if ($ot1 != $ct1) {
                $s1 = $ot1;
                $e1 = $ct1;
            } else {
                return $buniessName = '24小时营业';
            }
            if ($ot2 != $ct2) {
                $s2 = $ot2;
                $e2 = $ct2;
            }
            if ($ot3 != $ct3) {
                $s3 = $ot3;
                $e3 = $ct3;
            }
            $buniessName = array();
            if ($s1 > 0 && $e1 > 0) {
                if ($s1 > $e1) {
                    $e1 += 86400;
                }
                if ($s2 > 0 && $e2 > 0) {
                    if ($s2 > $e2) {
                        $e2 += 86400;
                    }
                    
                    if ($s2 < $s1 && $s1 <= $e2 && $e2 < $e1) {//时间段2在时间段1的左侧（数轴）且有重合部分
                        $s1 = $s2;
                    } elseif ($s1 < $s2 && $e2 < $e1) {//时间段2在时间段1的内部
                        
                    } elseif ($s1 < $s2 && $s2 <= $e1 && $e1 < $e2) {//时间段2在时间段的右侧（数轴）且有重合部分
                        $e1 = $e2;
                    } elseif ($s2 < $s1 && $e1 < $e2) {//时间段1在时间段2的内部
                        $s1 = $s2;
                        $e1 = $e2;
                    } elseif ($s2 + 86400 < $e1 && $e1 < $e2 + 86400) {//时间段1到次日的情况（时间段2的时间包含时间段1的时间）
                        $e1 = $e2 + 86400;
                    } elseif ($e2 - 86400 > 0 && $e2 - 86400 >= $s1 && ($s2 - 86400 > 0 && $s2 - 86400 < $s1 || $s2 - 86400 < 0 && $s2 < $s1)) {//时间段2到次日的情况（）
                        if ($s2 - 86400 > 0) {
                            $s1 = $s2 - 86400;
                        } else {
                            $s1 = $s2;
                        }
                    } else {
                        if ($s3 > 0 && $e3 > 0) {
                            if ($s3 > $e3) {
                                $e3 += 86400;
                            }
                            if ($s2 < $s3 && $s3 <= $e2 && $e2 < $e3) {//时间段2的开始时间
                                $s3 = $s2;
                            } elseif ($s3 < $s2 && $e2 < $e3) {
                                
                            } elseif ($s3 < $s2 && $s2 <= $e3 && $e3 < $e2) {
                                $e3 = $e2;
                            } elseif ($s2 < $s3 && $e3 < $e2) {
                                $s3 = $s2;
                                $e3 = $e2;
                            } elseif ($s2 + 86400 <= $s3 && $e3 < $e2 + 86400) {
                                $e3 = $e2 + 86400;
                            } elseif ($e2 - 86400 > 0 && $e2 - 86400 >= $s3 && ($s2 - 86400 > 0 && $s2 - 86400 < $s3 || $s2 - 86400 < 0 && $s2 < $s3)) {
                                if ($s2 - 86400 > 0) {
                                    $s3 = $s2 - 86400;
                                } else {
                                    $s3 = $s2;
                                }
                            } else {
                                if ($e2 - $s2 >= 86400) {
                                    return $buniessName = '24小时营业';
                                }
                                $sh = strtotime(date('Y-m-d ') . date('H:i:00', $s2));
                                $eh = strtotime(date('Y-m-d ') . date('H:i:00', $e2));
                                if ($sh > $eh) {
                                    $buniessName[] = date('H:i', $s2) . '~次日' . date('H:i', $e2);
                                } else {
                                    $buniessName[] = date('H:i', $s2) . '~' . date('H:i', $e2);
                                }
                            }
                        } else {
                            if ($e2 - $s2 >= 86400) {
                                return $buniessName = '24小时营业';
                            }
                            $sh = strtotime(date('Y-m-d ') . date('H:i:00', $s2));
                            $eh = strtotime(date('Y-m-d ') . date('H:i:00', $e2));
                            if ($sh > $eh) {
                                $buniessName[] = date('H:i', $s2) . '~次日' . date('H:i', $e2);
                            } else {
                                $buniessName[] = date('H:i', $s2) . '~' . date('H:i', $e2);
                            }
                        }
                    }
                    
                }
                
                if ($s3 > 0 && $e3 > 0) {
                    $e3 = $e3;
                    if ($s3 > $e3) {
                        $e3 = $e3 + 86400;
                    }
                    
                    if ($s3 < $s1 && $s1 <= $e3 && $e3 < $e1) {//时间段2的开始时间
                        $s1 = $s3;
                    } elseif ($s1 < $s3 && $e3 < $e1) {
                        
                    } elseif ($s1 < $s3 && $s3 <= $e1 && $e1 < $e3) {
                        $e1 = $e3;
                        $e1 = $e3;
                    } elseif ($s3 < $s1 && $e1 < $e3) {
                        $s1 = $s3;
                        $e1 = $e3;
                    }  elseif ($s3 + 86400 < $s1 && $e1 < $e3 + 86400) {
                        $s1 = $s3;
                        $e1 = $e3 + 86400;
                    } elseif ($e3 - 86400 > 0 && $e3 - 86400 >= $s1 && ($s3 - 86400 > 0 && $s3 - 86400 < $s1 || $s3 - 86400 < 0 && $s3 < $s1)) {
                        if ($s2 - 86400 > 0) {
                            $s1 = $s3 - 86400;
                        } else {
                            $s1 = $s3;
                        }
                    } else {
                        if ($e3 - $s3 >= 86400) {
                            return $buniessName = '24小时营业';
                        }
                        $sh = strtotime(date('Y-m-d ') . date('H:i:00', $s3));
                        $eh = strtotime(date('Y-m-d ') . date('H:i:00', $e3));
                        if ($sh > $eh) {
                            $buniessName[] = date('H:i', $s3) . '~次日' . date('H:i', $e3);
                        } else {
                            $buniessName[] = date('H:i', $s3) . '~' . date('H:i', $e3);
                        }
                    }
                }
                
                if ($e1 - $s1 >= 86400) {
                    return $buniessName = '24小时营业';
                }
                $sh = strtotime(date('Y-m-d ') . date('H:i:00', $s1));
                $eh = strtotime(date('Y-m-d ') . date('H:i:00', $e1));
                if ($sh > $eh) {
                    $buniessName[] = date('H:i', $s1) . '~次日' . date('H:i', $e1);
                } else {
                    $buniessName[] = date('H:i', $s1) . '~' . date('H:i', $e1);
                }
            }
            sort($buniessName);
            return implode(',', $buniessName);
        }
    }
    public function checkTime($v)
    {
		if(isset($v['is_business_open']) && !$v['is_business_open']){
			return false;
		}
		if(isset($v['is_business_open']) && $v['is_business_open']){
			return true;
		}
        $nowTime = time();
        
        if ($v['open_1'] == '00:00:00' && ($v['close_1'] == '00:00:00' || $v['close_1'] == '23:59:00')) {
            return true;
        } else {
            $ot1 = strtotime(date('Y-m-d ' . $v['open_1']));
            $ct1 = strtotime(date('Y-m-d ' . $v['close_1']));
            $ot2 = strtotime(date('Y-m-d ' . $v['open_2']));
            $ct2 = strtotime(date('Y-m-d ' . $v['close_2']));
            $ot3 = strtotime(date('Y-m-d ' . $v['open_3']));
            $ct3 = strtotime(date('Y-m-d ' . $v['close_3']));
            $s1 = 0;
            $s2 = 0;
            $s3 = 0;
            $e1 = 0;
            $e2 = 0;
            $e3 = 0;
            if ($ot1 != $ct1) {
                $s1 = $ot1;
                $e1 = $ct1;
                if ($ot1 > $ct1) {
                    $ct1 += 86400;
                }
                if ($ot1 <= $nowTime && $nowTime <= $ct1) {
                    return true;
                }
            } else {
                return true;
            }
            if ($ot2 != $ct2) {
                $s2 = $ot2;
                $e2 = $ct2;
                if ($ot2 > $ct2) {
                    $ct2 += 86400;
                }
                if ($ot2 <= $nowTime && $nowTime <= $ct2) {
                    return true;
                }
            }
            if ($ot3 != $ct3) {
                $s3 = $ot3;
                $e3 = $ct3;
                if ($ot3 > $ct3) {
                    $ct3 += 86400;
                }
                if ($ot3 <= $nowTime && $nowTime <= $ct3) {
                    return true;
                }
            }
            
            if ($e1 - $s2 > 86330 && $e1 - $s2 < 86400) {//时间段1和时间段2隔天相连
                $s = $s1;
                $e = $e2 + 86400;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            if ($s2 > 0 && ($s2 - $e1 < 70) && ($s2 - $e1 >= 0)) {//时间段1和时间段2相连
                $s = $s1;
                $e = $e2;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            
            if ($e2 - $s1 > 86330 && $e2 - $s1 < 86400) {//时间段2和时间段1隔天相连
                $s = $s2;
                $e = $e1 + 86400;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            if ($e2 > 0 && ($s1 - $e2 < 70) && ($s1 - $e2 >= 0)) {//时间段2和时间段1相连
                $s = $s2;
                $e = $e1;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            
            if ($e1 - $s3 > 86330 && $e1 - $s3 < 86400) {//时间段1和时间段3隔天相连
                $s = $s1;
                $e = $e3 + 86400;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            
            if ($s3 > 0 && ($s3 - $e1 < 70) && ($s3 - $e1 >= 0)) {//时间段1和时间段3相连
                $s = $s1;
                $e = $e3;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            if ($e3 - $s1 > 86330 && $e3 - $s1 < 86400) {//时间段3和时间段1隔天相连
                $s = $s3;
                $e = $e1 + 86400;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            
            if ($e3 > 0 && ($s1 - $e3 < 70) && ($s1 - $e3 >= 0)) {//时间段3和时间段1相连
                $s = $s3;
                $e = $e1;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            
            if ($e2 - $s3 > 86330 && $e2 - $s3 < 86400) {//时间段2和时间段3隔天相连
                $s = $s2;
                $e = $e3 + 86400;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            if ($e2 > 0 && $s3 > 0 && ($s3 - $e2 < 70) && ($s3 - $e2 >= 0)) {//时间段2和时间段3相连
                $s = $s2;
                $e = $e3;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            
            if ($e3 - $s2 > 86330 && $e3 - $s2 < 86400) {//时间段3和时间段2隔天相连
                $s = $s3;
                $e = $e2 + 86400;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            if ($e3 > 0 && $s2 > 0 && ($s2 - $e3 < 70) && ($s2 - $e3 >= 0)) {//时间段3和时间段2相连
                $s = $s3;
                $e = $e2;
                if ($s <= $nowTime && $nowTime <= $e) {
                    return true;
                }
            }
            return false;
        }
    }

    //获取最新的商家的配送方式
    public function get_mall_deliver_type($deliver_type=0,$mall_deliver_type=0){
        /**说明：
         * 看了以下代码，这个方法应该是获取【商城】店铺的配送方式且兼容了【快店】店铺的配送方式；
         * 即如果商城店铺没有设置配送方式，会根据快店的配送方式转化为商城的配送方式；
         * 由于商城新增【平台配送】功能且商城的配送方式组合已经多余快店，这段代码已不满足业务需求
         * 现在需要直接返回商城店铺配送方式_JJC_2019-12-25
        if($mall_deliver_type>0){
            return $mall_deliver_type;
        }else{
            switch ($deliver_type) {
                case '0':
                case '1':
                case '5':
                    $mall_deliver_type=5;
                    break;
                case '2':
                    $mall_deliver_type=2;
                    break;
                case '3':
                case '4':
                    $mall_deliver_type=3;
                    break;
                default:
                    $mall_deliver_type=5;
                    break;
            }
            return $mall_deliver_type;
        }
         **/
        return $mall_deliver_type;
    }


    /**
     * 根据店铺id获取起送价
     */
    public function getBaseDeliveryPriceByStoreid($storeid)
    {
        $store = D('Merchant_store')->where(['store_id' => $storeid])->find();
        $shop = $this->where(['store_id' => $storeid])->find();
        $row = array_merge($store, $shop);
        $deliveryPrice = $row['basic_price'];

        $store = [];
        $deliverReturn = D('Deliver_set')->getDeliverInfo($row);
        $delivery_fee = $deliverReturn['delivery_fee'];
        $per_km_price = $deliverReturn['per_km_price'];
        $basic_distance = $deliverReturn['basic_distance'];
        $delivery_fee2 = $deliverReturn['delivery_fee2'];
        $per_km_price2 = $deliverReturn['per_km_price2'];
        $basic_distance2 = $deliverReturn['basic_distance2'];
        $delivery_fee3 = $deliverReturn['delivery_fee3'];
        $per_km_price3 = $deliverReturn['per_km_price3'];
        $basic_distance3 = $deliverReturn['basic_distance3'];
        $start_time = $deliverReturn['delivertime_start'];
        $stop_time = $deliverReturn['delivertime_stop'];
        $start_time2 = $deliverReturn['delivertime_start2'];
        $stop_time2 = $deliverReturn['delivertime_stop2'];
        $start_time3 = $deliverReturn['delivertime_start3'];
        $stop_time3 = $deliverReturn['delivertime_stop3'];

        $time = time();
        $selectDeliverTime = 0;
        if (!($start_time == $stop_time && $start_time == '00:00:00')) {
            $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
            $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
            if ($start_time > $stop_time) {
                if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time) {
                    $selectDeliverTime = 1;
                } else {
                    $stop_time2 += 86400;
                    if ($start_time <= $time && $time <= $stop_time) {
                        $selectDeliverTime = 1;
                    }
                }
            } elseif ($start_time <= $time && $time <= $stop_time) {
                $selectDeliverTime = 1;
            }
            if (!($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);
                if ($start_time2 > $stop_time2) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                        $store['delivery_money'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time2 <= $time && $time <= $stop_time2) {
                            $store['delivery_money'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                        }
                    }
                } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                    $store['delivery_money'] = $delivery_fee2;
                    $selectDeliverTime = 2;
                }
            }
            if (!($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);

                if ($start_time3 > $stop_time3) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                        $store['delivery_money'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                    } else {
                        $stop_time3 += 86400;
                        if ($start_time3 <= $time && $time <= $stop_time3) {
                            $store['delivery_money'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                        }
                    }
                } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                    $store['delivery_money'] = $delivery_fee3;
                    $selectDeliverTime = 3;
                }
            }
        } else {
            $selectDeliverTime = 1;
        }

        //判断是否开启了城市区域配送设置
        $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['area_id'], 'status' => 1))->find();
        if (empty($set)) {
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['city_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['province_id'], 'status' => 1))->find();
            }
        }

        if ($selectDeliverTime == 1) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price1']) ? floatval($row['s_basic_price1']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price']))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) ? floatval($row['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price1']) ? floatval($row['basic_price1']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        } elseif ($selectDeliverTime == 2) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price2']) ? floatval($row['s_basic_price2']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price']))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price2']) ? floatval($row['basic_price2']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        } elseif ($selectDeliverTime == 3) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price3']) ? floatval($row['s_basic_price3']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price']))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price3']) ? floatval($row['basic_price3']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        } else {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                $store['delivery_price'] = floatval($row['s_basic_price']) ? floatval($row['s_basic_price']) : floatval($this->config['basic_price']);//起送价
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }
        return $store;
    }

    // 获得店铺优惠信息
    public function get_store_coupon_list($store){
        $coupon_list = array();
        if ($store['is_invoice']) {
            $coupon_list['invoice'] = floatval($store['invoice_price']);
        }
        
        if ($store['isDiscountGoods']) {
            $coupon_list['isdiscountgoods'] = 1;
        }
        if ($store['isdiscountsort']) {//店铺的商品分类折扣
            $coupon_list['isdiscountsort'] = 1;
        }
        if ($store['store_discount'] != 0 && $store['store_discount'] != 100) {
            $coupon_list['discount'] = $store['store_discount']/10;
        }

        foreach ($store['system_discount'] as $row_d) {
            if ($row_d['type'] == 0) {//新单
                $coupon_list['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'usage_scenario'=>$row_d['usage_scenario']);
            } elseif ($row_d['type'] == 1) {//满减
                $coupon_list['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'usage_scenario'=>$row_d['usage_scenario']);
            } elseif ($row_d['type'] == 2) {//配送
                if ($row_d['full_money'] >= 0 && $row_d['reduce_money'] > 0) {
                    $coupon_list['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'type'=>$row_d['type'],'usage_scenario'=>$row_d['usage_scenario']);
                }
            }
        }
        
        foreach ($store['merchant_discount'] as $row_m) {
            if ($row_m['type'] == 0) {
                $coupon_list['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
            } elseif ($row_m['type'] == 1) {
                $coupon_list['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
            }
        }

        if ($store['deliver']) {
            if (in_array($store['deliver_type'], [1, 4])) {
                //商家配送才判断店铺配置的配送时间段
                if ($store['is_have_two_time']) {
                    if ($store['reach_delivery_fee_type2'] == 0) {
                        if ($store['basic_price'] > 0 && $store['delivery_fee2'] > 0) {
                            $coupon_list['delivery'][] = array('money' => floatval($store['basic_price']), 'minus' => floatval($store['delivery_fee2']));
                        }
                    } elseif ($store['reach_delivery_fee_type2'] == 2) {
                        $store['delivery_fee2'] && $coupon_list['delivery'][] = array('money' => floatval($store['no_delivery_fee_value2']), 'minus' => floatval($store['delivery_fee2']));
                    }
                } else {
                    if ($store['reach_delivery_fee_type'] == 0) {
                        if ($store['basic_price'] > 0 && $store['delivery_fee'] > 0) {
                            $coupon_list['delivery'][] = array('money' => floatval($store['basic_price']), 'minus' => floatval($store['delivery_fee']));
                        }
                    } elseif ($store['reach_delivery_fee_type'] == 2) {
                        $store['delivery_fee'] && $coupon_list['delivery'][] = array('money' => floatval($store['no_delivery_fee_value']), 'minus' => floatval($store['delivery_fee']));
                    }
                }
            }

            // 平台免配送费设置
            if ($store['deliver_discount']) {
                $coupon_list['delivery_free'] = $store['deliver_discount'];
            }
        }
        $coupon_list = $this->simpleParseCoupon($coupon_list,'array');
        return $coupon_list;
    }

    public function simpleParseCoupon($obj){
        
        $returnObj = array();
        foreach ($obj as $key => $value) {
            if ($key == 'invoice') {
                $returnObj[] = array(
                    'type' => 'invoice',
                    'text' => L_('票'),
                );
            } elseif ($key == 'discount') {
                $returnObj[] = array(
                    'type' => 'discount',
                    'text' => L_('全场X1折', $obj[$key]),
                );
            } elseif ($key == 'isdiscountgoods') {
                $returnObj[] = array(
                    'type' => 'isdiscountgoods',
                    'text' => L_('限时优惠'),
                );
            } elseif ($key == 'isdiscountsort') {
                $returnObj[] = array(
                    'type' => 'isdiscountsort',
                    'text' => L_('折扣优惠'),
                );
            } elseif ($key == 'delivery_free') {
                $Currency_symbol = C('config.Currency_symbol');
                if ($value['money']>0) {
                    $d_text = L_('满'.$Currency_symbol.'X1免配送费',$value['money']);
                }else{
                    $d_text = L_('免配送费');
                }
                $returnObj[] = array(
                    'type' => 'delivery_free',
                    'text' => $d_text,
                );
            } else {
                foreach ($obj[$key] as $k => $v) {
                    if ($v['percentage_status']==1) {
                        if($obj[$key][$k]['money']>0){
                            $dis_text = '享X2折';
                        }else{
                            $dis_text = 'X2折';
                        }
                        $dis_num = round($v['minus']*10,2);
                    }elseif($v['type']==2){
                        $dis_text = '减'.C('config.Currency_symbol').'X2运费';
                        $dis_num = round($v['minus'],2);
                    }else{
                        $dis_text = '减'.C('config.Currency_symbol').'X2';
                        $dis_num = round($v['minus'],2);
                    }

                    if($key == 'delivery'){
                        if($obj[$key][$k]['order_count'] >= 1){
                            $returnObj[] = array(
                                'type' => 'delivery',
                                'text' => L_('平台首X0单满'.C('config.Currency_symbol').'X1'.$dis_text.'运费',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }elseif($obj[$key][$k]['order_count'] == 1){
                            $returnObj[] = array(
                                'type' => 'delivery',
                                'text' => L_('平台首单满'.C('config.Currency_symbol').'X1'.$dis_text.'运费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }else{
                            if($obj[$key][$k]['order_count'] >0){
                                $returnObj[] = array(
                                    'type' => 'delivery',
                                    'text' => L_('满'.C('config.Currency_symbol').'X1'.$dis_text.'运费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                );
                            }else{
                                $returnObj[] = array(
                                    'type' => 'delivery',
                                    'text' => L_($dis_text.'运费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                );
                            }
                        }
                    } else if($key == 'system_newuser'){
                        if($obj[$key][$k]['order_count'] == 1){
                            $returnObj[] = array(
                                'type' => 'system_newuser',
                                'text' => L_('平台首单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }else{
                            $returnObj[] = array(
                                'type' => 'system_newuser',
                                'text' => L_('平台首X0单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }
                    } else if($key == 'newuser'){
                        if($obj[$key][$k]['order_count'] == 1){
                            if ($obj[$key][$k]['money'] > 0) {
                                $returnObj[] = array(
                                    'type' => 'newuser',
                                    'text' => L_('店铺首单满' . C('config.Currency_symbol') . 'X1' . $dis_text, array('X1' => $obj[$key][$k]['money'], 'X2' => $dis_num)),
                                );
                            } else {
                                $returnObj[] = array(
                                    'type' => 'newuser',
                                    'text' => L_('店铺首单' . $dis_text, array('X2' => $dis_num)),
                                );
                            }
                        }else{
                            if($obj[$key][$k]['order_count']){
                                $returnObj[] = array(
                                    'type' => 'newuser',
                                    'text' => L_('店铺首X0单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                );
                            }
                            else{
                                if($obj[$key][$k]['money'] > 0){
                                    $returnObj[] = array(
                                        'type' => 'newuser',
                                        'text' => L_('店铺首单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                    );
                                }else{
                                    $returnObj[] = array(
                                        'type' => 'newuser',
                                        'text' => L_('店铺首单'.$dis_text,array('X2'=>$dis_num)),
                                    );
                                }
                            }
                        }
                    } else {
                        
                        if($v['order_count'] > 1){
                            $returnObj[] = array(
                                'type' => 'minus',
                                'text' => L_('首X0单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }elseif($v['order_count'] == 1){
                            $returnObj[] = array(
                                'type' => 'minus',
                                'text' => L_('首单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }else{
                            if($obj[$key][$k]['money']>0){
                                if($v['usage_scenario'] == 2){
                                    $returnObj[] = array(
                                        'type' => 'minus',
                                        'text' => L_('自取满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                    );
                                }else{
                                    $returnObj[] = array(
                                        'type' => 'minus',
                                        'text' => L_('满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                    );
                                }
                            }else{
                                
                                if($v['usage_scenario'] == 2){
                                    $returnObj[] = array(
                                        'type' => 'minus',
                                        'text' => L_('自取'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                    );
                                }else{
                                    $returnObj[] = array(
                                        'type' => 'minus',
                                        'text' => L_($dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                    );
                                }   
                            }   
                        }
                    }
                }
            }
        }
        return $returnObj;
    }

    // 获得前台店铺列表处理数据
    public function getShopList($param){
        $key = isset($param['key']) ? htmlspecialchars($param['key']) : '';
        $cat_url = isset($param['cat_url']) ? htmlspecialchars($param['cat_url']) : 'all';
        $order = isset($param['sort_url']) ? htmlspecialchars($param['sort_url']) : 'score_mean';
        $deliver_type = isset($param['type_url']) ? htmlspecialchars($param['type_url']) : 'all';
        $lat = isset($param['lat']) ? htmlspecialchars($param['lat']) : 0;
        $long = isset($param['lng']) ? htmlspecialchars($param['lng']) : (isset($param['long']) ? htmlspecialchars($param['long']) : 0);
        $is_search = $param['is_search'] ? 1 : 0;
        $pageSize = intval($param['pageSize']);
        $search_type = isset($param['search_type']) ? htmlspecialchars($param['search_type']) : '';
        $store_ids = isset($param['store_ids']) ? $param['store_ids'] : array();
        $specialUrl = isset($param['special_url']) ? htmlspecialchars($param['special_url']) : '';
        $limit = isset($param['limit']) ? $param['limit'] : 0;
        foreach($store_ids as $id_k=>$id_v){
            $store_ids[$id_k] = intval($id_v);
        }
        cookie('userLocationLong', $long);
        cookie('userLocationLat', $lat);
        $page = isset($param['page']) ? intval($param['page']) : 1;
        $page = max(1, $page);
        $cat_id = 0;
        $cat_fid = 0;
        $village_id = $param['village_id'] + 0;
        if($param['village_id']){
            $now_village =  D('House_village')->get_one($param['village_id']);
            $lat = $now_village['lat'];
            $long = $now_village['long'];
        }
        if ($cat_url != 'all') {
            $now_category = D('Shop_category')->get_category_by_catUrl($cat_url);
            if ($now_category) {
                if ($now_category['cat_fid']) {
                    $cat_id = $now_category['cat_id'];
                    $cat_fid = $now_category['cat_fid'];
                } else {
                    $cat_id = 0;
                    $cat_fid = $now_category['cat_id'];
                }
            }
        }

        $where = array('deliver_type' => $deliver_type, 'order' => $order, 'lat' => $lat, 'long' => $long, 'cat_id' => $cat_id, 'cat_fid' => $cat_fid, 'page' => $page, 'search_type'=>$search_type,'special' => $specialUrl);
        $key && $where['key'] = $key;
        $store_ids && $where['store_ids'] = $store_ids;
        $limit && $where['limit'] = $limit;

        // 跳过配送距离限制
        $where['deliver_type'] = 'skip';
        $where['city_id'] = $param['city_id'];

        // 如果存在社区id ,获取社区关联商户id, 加入查询条件中
        if ($village_id) {
            $where['village_id'] = $village_id;
            $lists = D('House_village_store')->get_list_by_option($where);
        } else {
            $lists = $this->get_list_by_option($where,1,$is_search,$pageSize);
        }
        $return = array();
        $now_time = date('H:i:s');
        if($param['hot_keywords'] && is_string($param['hot_keywords'])){
            $param['hot_keywords'] = explode(' ',$param['hot_keywords']);
        }
        $hot_keywords = is_array($param['hot_keywords']) ? $param['hot_keywords'] : array();
        $hot_keywords_time_unix = $param['hot_keywords_time'] ? $param['hot_keywords_time'] : time();
        $hot_keywords_time_hour = date('G',$hot_keywords_time_unix);
        $now_time_hour = date('G');
        if(($hot_keywords_time_hour >= 4 && $hot_keywords_time_hour < 10) && ($now_time_hour >= 10 || $now_time_hour < 4)){ //早饭
            $hot_keywords = array();
        }else if(($hot_keywords_time_hour >= 10 && $hot_keywords_time_hour < 14) && ($now_time_hour >= 14 || $now_time_hour < 10)){ //午饭
            $hot_keywords = array();
        }else if(($hot_keywords_time_hour >= 14 && $hot_keywords_time_hour < 17) && ($now_time_hour >= 17 || $now_time_hour < 14)){ //下午茶
            $hot_keywords = array();
        }else if(($hot_keywords_time_hour >= 17 && $hot_keywords_time_hour < 4) && ($now_time_hour >= 4 && $now_time_hour < 17)){   //晚饭+夜宵
            $hot_keywords = array();
        }
        /*获取自营商家下的店铺*/
        if(C('config.open_kefu_url') && C('config.kefu_mer_id')>0){
            $store_arr=M('Merchant_store')->where(array('mer_id'=>C('config.kefu_mer_id')))->getField('store_id',true);
        }

        // 店铺分类
        $database_shop_category = D('Shop_category');
        $category_list = $database_shop_category->lists();
        foreach ($lists['shop_list'] as $row) {
            $temp = array();
            $temp['store_id'] = $row['store_id'];
            if(is_array($store_arr) && in_array($temp['store_id'], $store_arr)){
                $temp['is_self']=1;
            }else{
                $temp['is_self']=0;
            }
            $temp['name'] = html_entity_decode($row['name'], ENT_QUOTES);
            $temp['store_theme'] = $row['store_theme'];
            $temp['isverify'] = $row['isverify'];
            $temp['extra_price'] = $row['extra_price'];
            $temp['juli_wx'] = $row['juli'];
            $temp['range'] = $row['range'];
            $temp['image'] = $row['image'];
            if($row['logo']){
                $temp['image'] = replace_file_domain($row['logo']);
            }
            $temp['image'] = thumb_img($temp['image'],180,120,'fill');
            
            $temp['star'] = number_format($row['score_mean'],1);
            if($temp['star'] == '0.0'){
                $temp['star'] = '5.0';
            }
            $temp['month_sale_count'] = $row['sale_count'] + $row['virtual_sale_count'];
            $temp['shop_fitment_color'] = $row['shop_fitment_color'] ? $row['shop_fitment_color'] : '113533';           //背景色
            $temp['shop_fitment_front_color'] = 'ffffff';       //标题色
            $temp['is_new'] = ($row['create_time'] + 864000) < time() ? 0 : 1;
            $temp['delivery'] = $deliver_type == 'pick' ? 0 : $row['deliver'];//是否支持配送
            $temp['delivery'] = $temp['delivery'] ? true : false;
            $temp['delivery_time'] = $row['send_time'];//配送时长
            $temp['send_time_type'] = $row['send_time_type'];
            $temp['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];
            $temp['delivery_price'] = floatval($row['basic_price']);//起送价
            $temp['delivery_money'] = floatval($row['delivery_fee']);//配送费
            $temp['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送
            $temp['is_close'] = 1;
            $temp['deliver_type'] = $row['deliver_type'];
            if ($this->checkTime($row)) {
                $temp['is_close'] = 0;
            }
            $temp['is_close'] = $temp['is_close'] ? $temp['is_close'] : $row['is_close'];
            $temp['time'] = $this->getBuniessName($row);

            $temp['check_age_object'] = array();
            $temp['check_age_object']['age_tips_status0'] = 0;//0=正常 1=重新输入生日 2=未达到法定年龄
            $temp['check_age_object']['age_tips0'] = L_('澳洲法律规定X1岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满X1周岁。',$row['age_limit_buy']);
            $temp['check_age_object']['age_tips_title0'] = L_('您已达到购买烟酒产品法定年龄');
            $temp['check_age_object']['age_tips_status1'] = 1;//0=正常 1=重新输入生日 2=未达到法定年龄
            $temp['check_age_object']['age_tips1'] = L_('您购买的是烟酒类产品，所以我们需要确认您的年龄需满X1周岁或者以上。在递送时，您有可能被要求出示相关证件。',$row['age_limit_buy']);
            $temp['check_age_object']['age_tips_title1'] = L_('请输入您的生日信息');
            $temp['check_age_object']['age_tips_status2'] = 2;//0=正常 1=重新输入生日 2=未达到法定年龄
            $temp['check_age_object']['age_tips2'] = L_('澳洲法律规定X1岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满X1周岁。',$row['age_limit_buy']);
            $temp['check_age_object']['age_tips_title2'] = L_('您未达到购买烟酒产品法定年龄');
            $temp['check_age_object']['age_limit_buy'] = $row['age_limit_buy'];
            $temp['check_age_object']['check_age'] = false;//是否判断用户年龄
            if($row['age_limit_buy_switch'] == 1 && $row['age_limit_buy'] > 0){ // 1=开启验证
                $temp['check_age_object']['check_age'] = true;
            }
            
            if($is_search){
                $temp['goods_list'] = $row['goods_list'] ? $row['goods_list'] : array();
            }
            
            if($where['key']){
                $temp['name_arr'] = $row['name_arr'];
            }

            $temp['coupon_list'] = array();
            if ($row['is_invoice']) {
                $temp['coupon_list']['invoice'] = floatval($row['invoice_price']);
            }
            if ($row['isDiscountGoods']) {
                $temp['coupon_list']['isdiscountgoods'] = 1;
            }
            if ($row['isdiscountsort']) {//店铺的商品分类折扣
                $temp['coupon_list']['isdiscountsort'] = 1;
            }
            if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
                $temp['coupon_list']['discount'] = $row['store_discount']/10;
            }
            $system_delivery = array();
            foreach ($row['system_discount'] as $row_d) { 
                if ($row_d['type'] == 0) {//新单
                    $temp['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'usage_scenario'=>$row_d['usage_scenario']);
                } elseif ($row_d['type'] == 1) {//满减
                    $temp['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'usage_scenario'=>$row_d['usage_scenario']);
                } elseif ($row_d['type'] == 2) {//配送
                    if ($row_d['full_money'] >= 0 && $row_d['reduce_money'] > 0) {
                        $temp['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'type'=>$row_d['type'],'usage_scenario'=>$row_d['usage_scenario']);
                    }
                }
            }
            
            foreach ($row['merchant_discount'] as $row_m) {
                if ($row_m['type'] == 0) {
                    $temp['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                } elseif ($row_m['type'] == 1) {
                    $temp['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                }
            }

            if ($row['deliver']) {
                if ($temp['delivery_system']) {
                    $system_delivery && $temp['coupon_list']['delivery'] = $system_delivery;
                } else {
                    if ($row['is_have_two_time']) {
                        if ($row['reach_delivery_fee_type2'] == 0) {
                            if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
                                $temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee2']));
                            }
                        } elseif ($row['reach_delivery_fee_type2'] == 1) {
                            //$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                        } elseif ($row['reach_delivery_fee_type2'] == 2) {
                            $row['delivery_fee2'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value2']), 'minus' => floatval($row['delivery_fee2']));
                        }
                    } else {
                        if ($row['reach_delivery_fee_type'] == 0) {
                            if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
                                $temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
                            }
                        } elseif ($row['reach_delivery_fee_type'] == 1) {
                            //$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                        } elseif ($row['reach_delivery_fee_type'] == 2) {
                            $row['delivery_fee'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
                        }
                    }
                }

                // 平台免配送费设置
                if ($row['deliver_discount']) {
                    $temp['coupon_list']['delivery_free'] = $row['deliver_discount'];
                    $temp['deliver_discount'] = $row['deliver_discount'];
                }
            }
            $temp['coupon_count'] = count($temp['coupon_list']);
            //dump($temp['coupon_list']);exit;
            $temp['coupon_list'] = $this->simpleParseCoupon($temp['coupon_list'],'array');

            //增加满赠活动
            $activity = D('Store_full_gift_activity')->getCurrentActivityByStoreId($row['store_id']);
            if ($activity) {
                $setting = D('Store_full_gift_activity')->getSettingByActivityId($activity['id']);
                if ($setting) {
                    if($setting[0]['full_price'] > 0){
                        array_push($temp['coupon_list'], ['type' => 'full_gift', 'text' => L_('满') . C('config.Currency_symbol') . $setting[0]['full_price'] . L_('得赠品')]);
                    }else{
                        array_push($temp['coupon_list'], ['type' => 'full_gift', 'text' => L_('下单得赠品')]);
                    }
                }
            }

            $temp['deliver_name'] = !empty(C('config.deliver_name')) ? C('config.deliver_name') : L_('平台配送');

            // 显示描述 所属分类
            if ($row['preference_status']) {
                // 优选 主推描述
                $temp['preference_desc'] = $row['preference_desc'];
            }else{
                // 前三子分类
                $condition_shop_category['store_id'] = $row['store_id'];
                $relation_ids = array_column(D('Shop_category_relation')->field(true)->where($condition_shop_category)->select(), 'cat_id') ;
                $category_arr = array();
                $index = 0;
                foreach ($category_list as $_cate) {
                    if ($index>=3) {
                        break;
                    }
                    foreach ($_cate['son_list'] as $_sub_cate) {
                        if ($index>=3) {
                            break;
                        }
                        if (in_array($_sub_cate['cat_id'], $relation_ids)) {
                            $index ++;
                            $category_arr[] = $_sub_cate['cat_name'];
                        }
                    }
                }
                $temp['category_arr'] = $category_arr;
            }
            
            $temp['next_date']  = (object)array();//下一阶段营业时间
            $temp['is_advance'] = false;
            if($temp['is_close']){//只有休息中的店铺才显示下一阶段营业时间
                $next_date = D('Merchant_store_open_time')->get_next_time($temp['store_id']);
                $temp['next_date'] = $next_date;
                if($next_date['type']<>3){
                    $temp['is_advance'] = true;
                }
                
            }
            
            $return[] = $temp;
            
            //热门关键词
            $tmp_key = explode(' ',$row['search_keywords']);
            if(!empty($tmp_key)){
                foreach($tmp_key as $key=>$value){
                    if(empty($value)){
                        unset($tmp_key[$key]);
                    }
                }
            }
            if(!empty($tmp_key)){
                $hot_keywords = array_unique(array_merge($hot_keywords,$tmp_key));
            }
        }
        $array = [
            'store_list' => $return, 
            'hot_keywords' => $hot_keywords ? array_values($hot_keywords) : [], 
            'hot_keywords_time' => time() , 
            'has_more' => $lists['has_more'] ? true : false
        ];
        return $array;
    }

    // 根据店铺是否添加子分类判断店铺是否启用三级分类
    public function checkMultClass($store_id){
        $condition = [];
        $condition['store_id'] = $store_id;
        $condition['fid'] = ['gt',0];
        $res = M('Shop_goods_sort')->where($condition)->find();
        return $res ? '1' : '0';
    }

    /**
     * 猜你喜欢活动店铺
     * @param array $where
     * @param number $limit
     */
    public function get_store_by_guess_like($where,$lat=0, $lng=0,$limit=3)
    {
        if(isset($where['uid'])){
            $uid = $where['uid'];
            unset($where['uid']);
        }
        // 所在城市
        $condition['s.city_id'] = $where['city_id'];

        // 类型
        $condition['ls.business_type'] = $where['business_type'];


        $condition['s.status'] = 1;
        $condition['s.is_business_open'] = 1;
        $condition['s.have_shop'] = 1;
       

        $time = date("H:i:s");
        $condition_field = 's.*,m.*,ls.id activity_id, ls.image as activity_image,ls.title as activity_title,ls.price as activity_price,ls.label as activity_label,ls.description as activity_description,ls.show_type as activity_show_type';
        $order = '';
        $time = date("H:i:s");
      
        $order .= '`t_sort` DESC, ';
        $order .= '`m`.`is_close` ASC ';

        $res = M(_view('Merchant_store'))->field($condition_field)->join(' as s INNER JOIN ' . C('DB_PREFIX') ._view("merchant_store_shop"). ' AS m ON m.store_id=s.store_id INNER JOIN ' . C('DB_PREFIX') .'system_guess_like_activity AS ls ON ls.store_id=s.store_id')->order('ls.id ASC')->where($condition)->limit($limit)->select();


        $store_ids = array_column($res, 'store_id');
        $shop_store_where = [];
        $shop_store_where['store_ids'] = $store_ids;
        $shop_store_where['city_id'] = $where['city_id'];
        $shop_store_where['lat'] = $lat;
        $shop_store_where['long'] = $lng;
        $shop_store_where['limit'] = $limit;
        $shop_store_where['deliver_type'] = 'skip';
        $shop_store_where = array_merge($where, $shop_store_where);
        $shop_store_list = $this->get_list_by_option($shop_store_where);
        $shop_store_list = $shop_store_list['shop_list'];
        $shop_store_arr = [];
        foreach ($shop_store_list as $key => $value) {
            $shop_store_arr[$value['store_id']] = $value;
        }
      
        import('@.ORG.longlat');
        $longlat_class = new longlat();
        $store_image_class = new store_image();
        $list = array();
        
        $shopDiscountDataBase = D('Shop_discount');
        $deliverSetDB = D('Deliver_set');

        $result_store = [];
        foreach ($res as $v) {
            if(!isset($shop_store_arr[$v['store_id']])){
                continue;
            }
            if(!empty($uid)){
                $key = sprintf('uid:%s,store_id:%s,activity_id:%s',$uid, $v['store_id'], $v['activity_id']);
                if(cache($key)){//用户设置为不喜欢，暂不显示
                    continue;
                }
            }
            $_tmp_store = [];

            // 活动信息
            $_tmp_store['activity_id']    = $v['activity_id'];//活动ID
            $_tmp_store['activity_title'] = $v['activity_title'];//活动标题
            $_tmp_store['activity_price'] = $v['activity_price'];//活动价格
            $_tmp_store['activity_label'] = $v['activity_label'] ? explode("\r\n", $v['activity_label']) : [];//活动标签
            $_tmp_store['activity_description'] = $v['activity_description'];//活动描述
            $_tmp_store['activity_show_type'] = $v['activity_show_type'];//活动图片布局形式0平铺1一大两小

            $_tmp_store['activity_image'] = explode(';', $v['activity_image']);//活动图片
            foreach ($_tmp_store['activity_image'] as &$img) {
                $img = thumb($img, 240, 160, 'fill');
            }

            $_tmp_store['store_name'] = $v['name'];//店铺名称

            // 店铺图片
            $images = $store_image_class->get_allImage_by_path($v['pic_info']);
            $_tmp_store['image'] = $images ? array_shift($images) : array();

            $_tmp_store['state'] = 0;//根据营业时间判断
            $time = time();
            if ($this->checkTime($v)) {
                $_tmp_store['state'] = 1;
            }

            $shop_store = $shop_store_arr[$v['store_id']];
            $_tmp_store['delivery'] = $shop_store['deliver'] ? true : false;
            $_tmp_store['delivery_time'] = $shop_store['send_time'];//配送时长
            $_tmp_store['send_time_type'] = $shop_store['send_time_type'];
            $_tmp_store['delivery_time_type'] = $this->send_time_type[$shop_store['send_time_type']];
            $_tmp_store['delivery_price'] = floatval($shop_store['basic_price']);//起送价
            $_tmp_store['delivery_money'] = floatval($shop_store['delivery_fee']);//配送费
            $_tmp_store['delivery_system'] = $shop_store['deliver_type'] == 0 || $shop_store['deliver_type'] == 3 ? true : false;//是否是平台配送
             $_tmp_store['range'] = $shop_store['range'];

            // 配送方式
            $_tmp_store['deliver_type'] = $shop_store['deliver_type'];
            $_tmp_store['delivery_name'] = L_('客户自提');
            if (in_array($shop_store['deliver_type'], [1,4])) {
                $_tmp_store['delivery_name'] = L_('商家配送');
            }elseif(in_array($shop_store['deliver_type'], [0,3])){
                $_tmp_store['delivery_name'] = C('config.deliver_name')?:L_('平台配送');
            }elseif(in_array($shop_store['deliver_type'], [5])){
                $_tmp_store['delivery_name'] = L_('快递配送');
            }
            $_tmp_store['star'] = number_format($shop_store['score_mean'],1);
            if($_tmp_store['star'] == '0.0'){
                $_tmp_store['star'] = '5.0';
            }

            $_tmp_store['url'] = C('config.site_url').'/wap.php?g=Wap&c=Shop&a=index&shop-id='.$v['store_id'];

            $list[] = $_tmp_store;
        }
        $return['shop_list'] = $list;
        return $return;
    }


    /**
     * 写入快店新订单数量
     * @author: 张涛
     * @date: 2020/11/19
     */
    public function shopNewOrderCountInc($storeId, $num = 1)
    {
        $rs = M('Merchant_store_shop_ext')->where(['store_id' => $storeId])->setInc('shop_new_order_count', $num);
        if (!$rs) {
            M('Merchant_store_shop_ext')->add(['store_id' => $storeId, 'shop_new_order_count' => $num]);
        }
    }

    /**
     * 获取用户当前会员等级优惠
     * @param $uid
     * @param $storeId
     * @author: 张涛
     * @date: 2020/11/11
     */
    public function getVipLevelDiscount($uid, $storeId, $type = 'shop')
    {
        $rs = ['show_level_price' => false, 'level' => 0, 'discount_val' => 100, 'discount' => 1];
        if (C('config.show_vip_price') == 1) {
            $level = (new UserModel())->getLevelByUid($uid);
            $discountVal = 100;
            $discountPrice = 0;
            if ($level['is_level']) {
                //是会员
                $shop = $this->where(['store_id' => $storeId])->find();

                if ($type == 'shop') {
                    $levelOff = unserialize($shop['leveloff']);
                } else if ($type == 'mall') {
                    $levelOff = unserialize($shop['mall_leveloff']);
                }
                $levelOff = $levelOff ?: [];
                foreach ($levelOff as $l) {
                    if ($l['level'] == $level['level']) {
                        if($l['type'] == 1){//百分比
                            $discountVal = $l['vv'];
                        }else if($l['type'] == 2){//立减
                            $discountPrice = $l['vv'];
                        }
                        break;
                    }
                }
            }
            $rs = [
                'show_level_price' => $level['is_level'] && ($discountVal != 100 || $discountPrice > 0) ? true : false,
                'level' => $level['level'],
                'discount_val' => $discountVal,
                'discount' => round($discountVal / 100, 2),
                'discount_price' => $discountPrice
            ];
        }
        return $rs;
    }

    public function hasChangeAddress($merchantId, $storeId, $orderId)
    {
        $store = M('Merchant_store')
            ->where(['mer_id' => $merchantId, 'store_id' => $storeId])
            ->field('store_id')
            ->find();
        
        if(empty($store)){
            return [
                'has_change' => false
            ];
        }
        
        $addressSetting = M('Merchant_address_edit_setting')->where(['merchant_id' => $merchantId])->find();
        if(empty($addressSetting)){
            return [
                'has_change' => false
            ];
        }
        
        $systemOrder = M('System_order')->where(['type' => 'shop', 'order_id' => $orderId])->find();
        if(!empty($systemOrder['change_address_status'])){
            return [
                'has_change' => false
            ];
        }
       
        $orderStatus = [//配送状态（0：订单生产，1:店员接单，2：配送员接单，3：配送员取货，4：送达，5：确认收货，6，配送结束，7，配送失败）
            1 => [0],//店铺接单前
            2 => [0, 1],//骑手接单前
            3 => [0, 1, 2],//骑手取货前
        ];
        
        $shopOrder = M('Shop_order')
            ->where(['order_id' => $orderId])
            ->where(['status' => ['IN',[0,1]]])
            ->field(['status', 'order_status', 'lat', 'lng', 'is_pick_in_store'])
            ->find();
        
        if(empty($shopOrder)){
            return [
                'has_change' => false
            ];
        }
        
        if(
            empty($shopOrder['is_pick_in_store']) &&
            !in_array($shopOrder['order_status'],$orderStatus[C('config.address_edit_order_status')])
        ){
            return [
                'has_change' => false
            ];
        }elseif(
            $shopOrder['is_pick_in_store'] == 1 &&
            !in_array($shopOrder['order_status'],$orderStatus[$addressSetting['order_status']])
        ){
            return [
                'has_change' => false
            ];
        }
        
        //deliver_type '配送方式（0平台配送，1商家配送，2客户自提，3平台配送或自提，4商家配送或自提,5快递配送）'
        //is_pick_in_store '配送方式( 0：平台配送，1：商家配送，2：自提 3：快递配送（商城）)',
        $deliveryType = $this->where(['store_id' => $storeId])->field('deliver_type')->find();
        if(
            in_array($deliveryType['deliver_type'], [0, 3]) && 
            C('config.address_edit_allow') == 1 && 
            $addressSetting['platform_allow'] == 1 && empty($shopOrder['is_pick_in_store'])
        ){//平台
           $platformTypes = explode(',',$addressSetting['platform_type']);
           
           if(!in_array('shop',$platformTypes)){//外卖业务
               return [
                   'has_change' => false
               ];
           }
            return [
                'has_change' => true,
                'distance' => C('config.address_edit_distribution_distance')
            ];
           
        }else if(
            in_array($deliveryType['deliver_type'], [1, 4]) &&
            $addressSetting['merchant_allow'] == 1 &&
            $shopOrder['is_pick_in_store'] == 1
        ){//商家
            $merchantTypes = explode(',',$addressSetting['merchant_type']);

            if(!in_array('shop',$merchantTypes)){//外卖业务
                return [
                    'has_change' => false
                ];
            }
            return [
                'has_change' => true,
                'distance' => $addressSetting['distribution_distance']
            ];
        }

        return [
            'has_change' => false
        ];
    }

}
?>