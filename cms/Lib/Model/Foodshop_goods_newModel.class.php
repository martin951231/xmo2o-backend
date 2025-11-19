<?php
class Foodshop_goods_newModel extends Model
{
    //获取不同类型的商品
    public function get_goods_list( $where_info = array(),$pagesize='20')
    {
        $condition_where = array();
		
		if($where_info['store_id']>0){
			$condition_where['fs.store_id'] = $where_info['store_id'];
		} 

        if($where_info['sort_id']>0){
            $condition_where['s.sort_id'] = $where_info['sort_id'];
        }

        if ($where_info['name']) {
            $condition_where[_field('s.name')] = array('like', '%' . $where_info['name'] . '%');
        }
  
        if ($where_info['_string']) {
            $condition_where['_string'] = $where_info['_string'];
            $condition_where['_string'] .= ' AND s.goods_id=fs.goods_id';
        }else{
            $condition_where['_string'] = 's.goods_id=fs.goods_id';
        }

        $condition_field = 's.*,fs.*';
        $condition_order = '`s`.`sort` DESC, `fs`.`goods_id` ASC';
        $condition_table = array(C('DB_PREFIX').'shop_goods'=>'s',C('DB_PREFIX').'foodshop_goods_new'=>'fs');
        $count = $this->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $Page = new Page($count, $pagesize);
        $good_list = D()
            ->field($condition_field)
            ->table($condition_table)
            ->where($condition_where)
            ->order($condition_order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // var_dump($good_list);
        $goods_image_class = new goods_image();
        $timeNow = time();
        $goodsList = array();
        $site_url = C('config.site_url');
        foreach ($good_list as $value) {
           
            $tmp_pic_arr = explode(';', $value['image']);
            foreach ($tmp_pic_arr as $k => $v) {
                $value['pic_arr'][$k]['title'] = $v;
                $value['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
                $value['image_url'] = $value['pic_arr'][$k]['url']['image'];
            }
            $value['name'] = html_entity_decode($value['name'],ENT_QUOTES);
            $value['describe'] = html_entity_decode($value['describe'],ENT_QUOTES);
            $goodsList[] = $value;
        }
        return array(
            'list' => $goodsList,
            'pagebar' => $Page->show()
        );
    }

    //获取不同类型的商品
    public function get_list_by_storeid_diypage($store_id, $where_info = array())
    {
        $condition_where = array('fs.status' => 1);
        
        if($store_id>=0){
            $condition_where['fs.store_id'] = $store_id;
        }
      
        if ($where_info['name']) {
            $condition_where['s.name'] = array('like', '%' . $where_info['name'] . '%');
        }

        if ($where_info['_string']) {
            $condition_where['_string'] = $where_info['_string'];
            $condition_where['_string'] .= ' AND s.goods_id=fs.goods_id';
        }else{
            $condition_where['_string'] = 's.goods_id=fs.goods_id';
        }

        $condition_field = 's.*,fs.*';
        $condition_order = '`s`.`sort` DESC, `fs`.`goods_id` ASC';
        $condition_table = array(C('DB_PREFIX').'shop_goods'=>'s',C('DB_PREFIX').'foodshop_goods_new'=>'fs');

        $count = D()->table($condition_table)->where($condition_where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 8);
        $good_list = D()
            ->field($condition_field)
            ->table($condition_table)
            ->where($condition_where)
            ->order($condition_order)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $goods_image_class = new goods_image();
        $timeNow = time();
        $goodsList = array();
        $site_url = C('config.site_url');
        foreach ($good_list as $value) {
            $tmp_pic_arr = explode(';', $value['image']);
            foreach ($tmp_pic_arr as $k => $v) {
                $value['pic_arr'][$k]['title'] = $v;
                $value['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
                $value['image_url'] = $value['pic_arr'][$k]['url']['image'];
            }
            $value['name'] = html_entity_decode($value['name'],ENT_QUOTES);
            $value['describe'] = html_entity_decode($value['describe'],ENT_QUOTES);
            $goodsList[] = $value;
        }
        return array(
            'good_list' => $goodsList,
            'page_bar' => $Page->show()
        );
    }

    public function get_goods_by_id($goods_id,$group_id=null)
    {
        $now_goods = M(_view('Shop_goods'))->field(true)->where(array('goods_id' => $goods_id))->find();
        $now_goods_foodshop = D('Foodshop_goods_new')->where(array('goods_id'=>$goods_id))->find();

        if(empty($now_goods) || !$now_goods_foodshop){
            return false;
        }

        $now_goods['name'] = html_entity_decode($now_goods['name'], ENT_QUOTES);
        $now_goods['price'] = floatval($now_goods_foodshop['price']);
        $now_goods['old_price'] = floatval($now_goods_foodshop['old_price']);
        $now_goods['o_price'] = floatval($now_goods_foodshop['old_price']);
        $now_goods['last_time'] = $now_goods_foodshop['last_time'];
        if ( $now_goods['image']==C('config.goods_default_image')) {
            $now_goods['image'] = '';
        }

        // 售卖时间
        if ($now_goods_foodshop['spec_type'] == 1) {
            $now_time = date('H:i');
            if (!$now_goods_foodshop['spec_open_time']&&!$now_goods_foodshop['spec_close_time']) {
                $open_time = '00:00';
                $close_time = '24:00';
            }else{
                $open_time = date('H:i', $now_goods_foodshop['spec_open_time']);
                $close_time = date('H:i', $now_goods_foodshop['spec_close_time']);
            }
        } else {
            $now_time = time();
            if (!$now_goods_foodshop['spec_open_datetime']&&!$now_goods_foodshop['spec_close_datetime']) {
                $open_time = strtotime(date('Y-m-d 00:00:00'));
                $close_time = strtotime(date('Y-m-d 23:59:59'));
            }else{
                $open_time = $now_goods_foodshop['spec_open_datetime'];
                $close_time = $now_goods_foodshop['spec_close_datetime'];
            }
        }
        $now_goods['is_time_out'] = false;
        if ( $open_time > $now_time || $close_time < $now_time){
            // 不在售卖时间内
            $now_goods['is_time_out'] = true;
        } 

        // 快店信息
        $shop = D('Merchant_store_shop')->where(array('store_id' => $now_goods['store_id']))->find();
        // 库存
        $stock_type = $shop['stock_type'];
        if (empty($shop)) return false;
        if ($now_goods['sell_day'] != $today) {
            if($now_goods['sell_type']==2){
                $now_goods['stock_num'] = $now_goods['original_stock'];
                if($now_goods['original_weight'] == -1){
                    $now_goods['stock_num'] = -1;
                }else{
                    $goods_weight =  $now_goods['weight_unit'] == 2 ? $now_goods['weight'] * 1000 : $now_goods['weight'];
                    $now_goods['stock_num'] = floor($now_goods['original_weight'] / $goods_weight);
                }
            }
        }else{
            if($now_goods['sell_type'] == 2 && $now_goods['stock_weight'] == -1){
                $now_goods['stock_num'] = -1;
            }elseif($now_goods['sell_type'] == 2){
                $goods_weight =  $now_goods['weight_unit'] == 2 ? $now_goods['weight'] * 1000 : $now_goods['weight'];
                $now_goods['stock_num'] = floor($now_goods['stock_weight'] / $goods_weight);
            }
        }

        // 餐饮库存
        if ($now_goods_foodshop['spec_stock'] == -1) {
            $now_goods['stock'] = $now_goods['stock_num'];
        }else{
            $now_goods['stock'] = $now_goods_foodshop['spec_stock'];
        }

        $now_goods['is_sell_out'] = false;
        if ($now_goods['stock'] != -1 && $now_goods['stock'] <=0 ) {
            // 已售完
            $now_goods['is_sell_out'] = true;
        }

        if(!$now_goods['is_sell_out']){
            /****** 限时优惠 start *******/
            if ($now_goods_foodshop['seckill_type'] == 1) {
                $now_time = date('H:i');
                $open_time = date('H:i', $now_goods_foodshop['seckill_open_time']);
                $close_time = date('H:i', $now_goods_foodshop['seckill_close_time']);
                
                //秒杀库存的计算
                $today = date('Ymd',time());
                if ($today == $now_goods_foodshop['sell_day']) {
                    $seckill_stock_num = $now_goods_foodshop['seckill_stock'] == -1 ? -1 : (intval($now_goods_foodshop['seckill_stock'] - $now_goods_foodshop['today_seckill_count']) > 0 ? intval($now_goods_foodshop['seckill_stock'] - $now_goods_foodshop['today_seckill_count']) : 0);
                } else {
                    $seckill_stock_num = $now_goods_foodshop['seckill_stock'];
                }
            } else {
                $now_time = time();
                $open_time = $now_goods_foodshop['seckill_open_time'];
                $close_time = $now_goods_foodshop['seckill_close_time'];
                $seckill_stock_num = $now_goods_foodshop['seckill_stock'] == -1 ? -1 : (intval($now_goods_foodshop['seckill_stock'] - $now_goods_foodshop['today_seckill_count']) > 0 ? intval($now_goods_foodshop['seckill_stock'] - $now_goods_foodshop['today_seckill_count']) : 0);
            }
            $now_goods['is_seckill_price'] = false;

            if ($open_time < $now_time && $now_time < $close_time && floatval($now_goods_foodshop['seckill_price']) > 0 && $seckill_stock_num != 0) {
                $now_goods['price'] = floatval($now_goods_foodshop['seckill_price']);
                $now_goods['is_seckill_price'] = true;
            }
            $now_goods['seckill_price'] = floatval($now_goods['seckill_price']);
            if ($now_goods['is_seckill_price']) {
                $now_goods['stock'] = $seckill_stock_num;
            }
            /****** 限时优惠 end *******/
        }

        if(!empty($now_goods['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_goods['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_goods['pic_arr'][$key]['title'] = $value;
                if('http' === substr($value,0,4)){
                    $now_goods['pic_arr'][$key]['url'] = $value;
                }else{
                    $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 'm');
                }
            }
            $now_goods['image'] = $now_goods['pic_arr'][0]['url'];
        }else if(C('config.goods_default_image')){
            $now_goods['image'] = C('config.goods_default_image');
        }

        $now_goods['name'] = html_entity_decode($now_goods['name'], ENT_QUOTES);
        // 原价
        $now_goods['o_price'] = floatval($now_goods['old_price']);

        $return = D('Shop_goods')->format_spec_value($now_goods['spec_value'], $now_goods['goods_id'], $now_goods['is_properties'], $now_goods['min_num'],$group_info,$now_goods['foodshop_spec_value']);
        $now_goods['json'] = isset($return['json']) ? json_encode($return['json']) : '';
        $now_goods['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
        $now_goods['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
        $now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_goods['list'] = isset($return['list']) ? $return['list'] : '';
        
        $today = date('Ymd');
        $now_goods['sell_day'] = $stock_type ? $today : $now_goods['sell_day'];
        $now_goods['today_sell_spec'] = json_decode($now_goods['today_sell_spec'], true);
   
        $nowtime = time();
        $now_goods['is_new'] = (($nowtime - $now_goods['last_time']) > 864000) ? 0 : 1;
        
        if(!$now_goods['spec_list']){
            if($now_goods['min_num'] > 0 && $now_goods['stock_num'] >= 0 && $now_goods['min_num'] > $now_goods['stock_num']){
                $now_goods['stock_num'] = 0;
            }
            if($now_goods['min_num'] > 0 && $now_goods['seckill_stock'] >= 0 && $now_goods['min_num'] > $now_goods['seckill_stock']){
                $now_goods['seckill_stock'] = 0;
            }
        }
       
        $now_goods['label_arr']=explode(',', $now_goods['label_str']);
        
        $spicy_arr = D('Shop_goods')->spicy_arr();
        $now_goods['spicy'] = $spicy_arr[$now_goods['spicy']] ? $spicy_arr[$now_goods['spicy']] : '';//辣度
        $now_goods['flavor'] = $now_goods['flavor'] ? explode('&^&',$now_goods['flavor']) : array();//口味
        $now_goods['material'] = $now_goods['material'] ? explode('&^&',$now_goods['material']) : array();//原料
        $now_goods['special_food_materials'] = $now_goods['special_food_materials'] ? explode('&^&',$now_goods['special_food_materials']) : array();//特殊食材
        $now_goods['cooking_methods'] = $now_goods['cooking_methods'] ? explode('&^&',$now_goods['cooking_methods']) : array();//做法
        $now_goods['describe'] = html_entity_decode($now_goods['describe'], ENT_QUOTES);
        return $now_goods;
    }

    public function getListByStoreId($store_id, $sort = 0, $keyword = '')
    {
        $where['store_id'] = $store_id;
        $where['fid'] = 0;
        $where['status'] = 1;
        $sort_list = D(_view('Shop_goods_sort'))->field(true)->where($where)->order('`sort` DESC,`sort_id` ASC')->select();
        foreach ($sort_list as &$_sort) {
            $_sort['child'] = D('Shop_goods_sort')->getAllSonIds($_sort['sort_id'],$_sort['store_id']);
        }

        $now_shop = D('Merchant_store_shop')->field('stock_type')->where(array('store_id' => $store_id))->find();
        $where=array('s.store_id' => $store_id, 'fs.status' => 1);
        if ($sort == 1) {
            $order = 's.sell_count DESC, s.goods_id ASC';
        } elseif ($sort == 2) {
            $order = 'fs.price DESC, s.goods_id ASC';
        } elseif ($keyword != '') {
            $where['s.name']= array('like', '%' . $keyword . '%');
            $order = 's.sort DESC, s.goods_id ASC';
        } else {
            $order = 's.sort DESC, s.goods_id ASC';
        }

        $g_list = M(_view('Shop_goods'))->field(true)->field('s.*,fs.*')->join(' as s inner join ' . C('DB_PREFIX').'foodshop_goods_new as fs on fs.goods_id=s.goods_id')->where($where)->order($order)->select();
        $newList = array();
        $today = date('Ymd');
        $goods_image_class = new goods_image();
        $timeNow = time();
		
		$spicy_arr = D('Shop_goods')->spicy_arr();
		
        foreach ($g_list as $r) {
            // 所属分类
            foreach ($sort_list as $_tmp_sort) {
                $glist = array();
                 // 售卖时间
                if ($r['spec_type'] == 1) {
                    $now_time = date('H:i');
                    if (!$r['spec_open_time']&&!$r['spec_close_time']) {
                        $open_time = '00:00';
                        $close_time = '24:00';
                    }else{
                        $open_time = date('H:i', $r['spec_open_time']);
                        $close_time = date('H:i', $r['spec_close_time']);
                    }
                } else {
                    $now_time = time();
                    if (!$r['spec_open_datetime']&&!$r['spec_close_datetime']) {
                        $open_time = strtotime(date('Y-m-d 00:00:00'));
                        $close_time = strtotime(date('Y-m-d 23:59:59'));
                    }else{
                        $open_time = $r['spec_open_datetime'];
                        $close_time = $r['spec_close_datetime'];
                    }
                }

                $glist['is_time_out'] = false;
                if ( $open_time > $now_time || $close_time < $now_time){
                    // 不在售卖时间内
                    $glist['is_time_out'] = true;
                } 
                
                if ($r['spec_stock'] == -1) {
                    $glist['stock'] = $r['stock_num'];
                }else{
                    $glist['stock'] = $r['spec_stock'];
                }

                $glist['is_sell_out'] = false;
                if ($glist['stock'] != -1 && $glist['stock'] <=0 ) {
                    // 已售完
                    $glist['is_sell_out'] = true;
                }

                if(!$glist['is_sell_out']){
                    /****** 限时优惠 start *******/
                    if ($r['seckill_type'] == 1) {
                        $now_time = date('H:i');
                        $open_time = date('H:i', $r['seckill_open_time']);
                        $close_time = date('H:i', $r['seckill_close_time']);
                        
                        //秒杀库存的计算
                        $today = date('Ymd',time());
                         $seckill_stock_num = $r['seckill_stock'];
                        
                    } else {
                        $now_time = time();
                        $open_time = $r['seckill_open_time'];
                        $close_time = $r['seckill_close_time'];
                        $seckill_stock_num = $r['seckill_stock'];
                    }
                    $glist['is_seckill_price'] = false;

                    if ($open_time < $now_time && $now_time < $close_time && floatval($r['seckill_price']) > 0 && $seckill_stock_num != 0) {
                        $r['price'] = floatval($r['seckill_price']);
                        $glist['is_seckill_price'] = true;
                    }
                    $glist['seckill_price'] = floatval($r['seckill_price']);
                    if ($glist['is_seckill_price']) {
                        $glist['stock'] = $seckill_stock_num;
                    }
                    /****** 限时优惠 end *******/
                }

                $glist['product_id'] = $r['goods_id'];
                $glist['store_id'] = $r['store_id'];
                $glist['product_name'] = html_entity_decode($r['name'], ENT_QUOTES);
                $glist['product_describe'] = html_entity_decode($r['describe'], ENT_QUOTES);
                $glist['describe'] = html_entity_decode($r['describe'], ENT_QUOTES);
                $glist['price'] = $r['price'];
                $glist['product_price'] = strval(floatval($r['price']));
                $glist['o_price'] = strval(floatval($r['old_price']));
                $glist['old_price'] = strval(floatval($r['old_price']));
                $glist['number'] = $r['number'];
                $glist['packing_charge'] = strval(floatval($r['packing_charge']));
                $glist['unit'] = $r['unit'];
                $glist['spicy'] = $spicy_arr[$r['spicy']] ? $spicy_arr[$r['spicy']] : '';//辣度
                $glist['flavor'] = $r['flavor'] ? explode('&^&',$r['flavor']) : array();//口味
                $glist['material'] = $r['material'] ? explode('&^&',$r['material']) : array();//原料
                $glist['special_food_materials'] = $r['special_food_materials'] ? explode('&^&',$r['special_food_materials']) : array();//特殊食材
                $glist['cooking_methods'] = $r['cooking_methods'] ? explode('&^&',$r['cooking_methods']) : array();//做法
               
                
                //商品缩略图
                if($r['image']){
                    $tmp_pic_arr = $goods_image_class->get_allImage_by_path($r['image'], 's');
                    $glist['product_image'] = thumb_img($tmp_pic_arr[0],180,180,'fill');
                }else{
                    $glist['product_image'] = '';
                }
                
                
                $glist['product_sale'] = $r['sell_count'];
                $glist['product_reply'] = $r['reply_count'];
                $glist['has_format'] = false;
                if ($r['spec_value'] || $r['is_properties']) {
                    $glist['has_format'] = true;
                }
                $glist['has_spec']  = $r['spec_value'] ? true : false; //商品是否有规格
                $glist['is_subsidiary_goods']  = $r['is_subsidiary_goods'] ? 1 : 0; //商品是否有附属菜
                
                $glist['is_new'] = (($timeNow - $glist['last_time']) > 864000) ? 0 : 1;
                if (in_array($r['sort_id'], $_tmp_sort['child'])) {
                    if (isset($newList[$_tmp_sort['sort_id']])) {
                        $newList[$_tmp_sort['sort_id']][] = $glist;
                    } else {
                        $newList[$_tmp_sort['sort_id']] = array($glist);
                    }
                }
            }
        }

        $result = array();
        $index = 0;
        foreach ($sort_list as $l) {
            $temp = array();
            $temp['cat_id'] = $l['sort_id'];
            $temp['cat_name'] = html_entity_decode($l['sort_name'],ENT_QUOTES);
            $temp['desc'] = $l['desc'];
            $temp['level'] = $l['level'];
            $temp['index'] = $index;
            $temp['findex'] = 0;
            $temp['fid'] = 0;
            $temp['sort_discount'] = strval(round(intval($l['sort_discount']) * 0.1, 2));
            if (isset($newList[$l['sort_id']])) {
                $temp['product_list'] = $newList[$l['sort_id']];
            }  else {
                $temp = null;
            }
            if (!empty($temp)) {
                $index ++;
                $result[] = $temp;
            }
        }
        return $result;
    }

    public function get_list_by_option($where, $sort, $sort_type = 1)
    {
        $order = '';
        if ($sort == 1) {
            $order .= '`g`.`sell_count` DESC, `g`.`price` ASC';
        } elseif ($sort == 2) {
            $order .= '`g`.`sell_count`';
        } elseif ($sort == 3) {
            $order .= '`fg`.`price`';
        }elseif($sort==4){  //商品优选排序规则
            $order.='g.index_sort desc,g.sell_count desc,g.goods_id';
        }
        if ($sort != 1) {
            if ($sort_type == 1) {
                $order .= ' DESC';
            } else {
                $order .= ' ASC';
            }
        }
        $condition = '`fg`.`status`=1 AND `s`.`status`=1 ';
        
        if ($where['cat_id']) {
            $condition .= " AND `g`.`cat_id`='{$where['cat_id']}'";
        }
        
        
        if ($where['store_id']) {
            $condition .= " AND `g`.`store_id`='{$where['store_id']}'";
        }
        
        if ($where['key']) {
            $condition .= " AND `g`.`name` LIKE '%{$where['key']}%'";
        }
        
        if ($where['goods_ids']) {
            $condition .= ' AND `g`.`goods_id` IN (' . implode(',', $where['goods_ids']) . ')';
        }

        if ($where['store_theme']) {
            $condition .= " AND `sh`.`store_theme` = ' {$where['store_theme']}'";
        }
        if($where['store_status']){
            $condition .= " AND `sh`.`store_status` = ' {$where['store_status']}'";
        }
       
        /*修复指定时间显示商品列表查询数量不一致的bug 2018/12/14*/
        $today=date('Y/m/d');
        $now_time=time();
        $string="(g.show_start_time=g.show_end_time or (g.show_start_time='00:00:00' and g.show_end_time='23:59:00') or (unix_timestamp(concat('".$today."',' ',g.show_start_time))<=".$now_time." and unix_timestamp(concat('".$today."',' ',g.show_end_time))>=".$now_time."))";
        $condition.=' and '.$string;

        $page = isset($where['page']) ? intval($where['page']) : 1;

        $field="`g`.*,fg.*";
        if ($where['page_size']) { 
            $sql_count = "SELECT count(1) as count FROM " . C('DB_PREFIX') . "merchant_store AS s INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS sh ON `s`.`status`=1 AND `sh`.`store_theme`=1 AND `s`.`store_id`=`sh`.`store_id` INNER JOIN " . C('DB_PREFIX') . "shop_goods AS g ON `sh`.`store_id`=`g`.`store_id`  INNER JOIN " . C('DB_PREFIX') . "shop_goods AS g ON `sh`.`store_id`=`g`.`store_id` WHERE {$condition}";
            $count = $this->query($sql_count);

            $total = isset($count[0]['count']) ? intval($count[0]['count']) : 0;
            $pagesize = isset($where['page_size']) ? intval($where['page_size']) : 10;
            $totalPage = ceil($total / $pagesize);
            $star = $pagesize * ($page - 1);
            $return['next_page'] = $totalPage > $page ? intval($page + 1) : 0;
            $return['total_page'] = $totalPage;
            $return['total'] = $total;

            
            //$temp_list = $this->query($sql);
            $temp_list = $this->field($field)->table(C('DB_PREFIX') .'foodshop_goods_new'. " as fg")->join("inner join " . C('DB_PREFIX') . _view('shop_goods')." AS g ON `g`.`goods_id`=`fg`.`goods_id`")->join("inner join " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`store_id`=`g`.`store_id`")->join("inner join " . C('DB_PREFIX') . "merchant_store_shop as sh on `s`.`status`=1 AND `s`.`store_id`=`sh`.`store_id`")->where($condition)->order($order)->limit($star,$pagesize)->select();

        }else{
             $temp_list = $this->field($field)->table(C('DB_PREFIX') .'foodshop_goods_new'. " as fg")->join("inner join " . C('DB_PREFIX') . _view('shop_goods')." AS g ON `g`.`goods_id`=`fg`.`goods_id`")->join("inner join " . C('DB_PREFIX') . "merchant_store AS s ON `s`.`store_id`=`g`.`store_id`")->join("inner join " . C('DB_PREFIX') . "merchant_store_shop as sh on `s`.`status`=1 AND `s`.`store_id`=`sh`.`store_id`")->where($condition)->order($order)->select();
        }
       
        $goods_image_class = new goods_image();
        $nowtime = time();
        $today = date('Ymd');
        $goods_list = array();
        foreach ($temp_list as $row) {
            $row['price'] = floatval($row['price']);
            // 售卖时间
            if ($row['spec_type'] == 1) {
                $now_time = date('H:i');
                if (!$row['spec_open_time']&&!$row['spec_close_time']) {
                    $open_time = '00:00';
                    $close_time = '24:00';
                }else{
                    $open_time = date('H:i', $row['spec_open_time']);
                    $close_time = date('H:i', $row['spec_close_time']);
                }
            } else {
                $now_time = time();
                if (!$row['spec_open_datetime']&&!$row['spec_close_datetime']) {
                    $open_time = strtotime(date('Y-m-d 00:00:00'));
                    $close_time = strtotime(date('Y-m-d 23:59:59'));
                }else{
                    $open_time = $row['spec_open_datetime'];
                    $close_time = $row['spec_close_datetime'];
                }
            }

            $row['is_time_out'] = false;
            if ( $open_time > $now_time || $close_time < $now_time){
                // 不在售卖时间内
                $row['is_time_out'] = true;
            } 
            
            $row['name'] = html_entity_decode($row['name'], ENT_QUOTES);
            if ($row['spec_stock'] == -1) {
                $row['stock'] = $row['stock_num'];
            }else{
                $row['stock'] = $row['spec_stock'];
            }

            $row['is_sell_out'] = false;
            if ($row['stock'] != '-1' && $row['stock'] <=0 ) {
                // 已售完
                $row['is_sell_out'] = true;
            }


            if(!$row['is_sell_out']){
                /****** 限时优惠 start *******/
                if ($row['seckill_type'] == 1) {
                    $now_time = date('H:i');
                    $open_time = date('H:i', $row['seckill_open_time']);
                    $close_time = date('H:i', $row['seckill_close_time']);
                    
                    //秒杀库存的计算
                    $today = date('Ymd',time());
                  
                    $seckill_stock_num = $row['seckill_stock'];
                    
                } else {
                    $now_time = time();
                    $open_time = $row['seckill_open_time'];
                    $close_time = $row['seckill_close_time'];
                    $seckill_stock_num = $row['seckill_stock'] == -1 ? -1 : intval($row['seckill_stock']);
                }
                $row['is_seckill_price'] = false;

                if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0 && $seckill_stock_num != 0) {
                    $row['price'] = floatval($row['seckill_price']);
                    $row['is_seckill_price'] = true;
                }
                $row['seckill_price'] = floatval($row['seckill_price']);
                if ($row['is_seckill_price']) {
                    $row['stock'] = $seckill_stock_num;
                }
                /****** 限时优惠 end *******/
            }
            $row['is_new'] = (($nowtime - $row['last_time']) > 864000) ? 0 : 1;

            if ($row['image']) {
                $tmp_pic_arr = explode(';', $row['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    if('http' === substr($value,0,4)){
                        $temp_image = $value;
                    }else{
                        $temp_image = $goods_image_class->get_image_by_path($value);
                    }
                    if ($temp_image) {
                        if(is_array($temp_image)){
                            $row['image'] = $temp_image['s_image'];
                            break;
                        }else{
                            $row['image'] = $temp_image; 
                            break;
                        }
                    }
                }
            }
            

            $spicy_arr = D('Shop_goods')->spicy_arr();
            $row['spicy'] = $spicy_arr[$row['spicy']] ? $spicy_arr[$row['spicy']] : '';//辣度
            $row['flavor'] = $row['flavor'] ? explode('&^&',$row['flavor']) : array();//口味
            $row['material'] = $row['material'] ? explode('&^&',$row['material']) : array();//原料
            $row['special_food_materials'] = $row['special_food_materials'] ? explode('&^&',$row['special_food_materials']) : array();//特殊食材
            $row['cooking_methods'] = $row['cooking_methods'] ? explode('&^&',$row['cooking_methods']) : array();//做法

            $row['name'] = html_entity_decode($row['name'], ENT_QUOTES);
            $row['describe'] = html_entity_decode($row['describe'], ENT_QUOTES);

            $goods_list[] = $row;
        }
        $return['goods_list'] = $goods_list;
        return $return;
    }

    //校验用户下单信息
    public function checkCart($store_id, $uid, $goodsData, $isCookie = 1)
    {
        //计算附属商品数量 当主商品数量增加 附属商品数量相应增加
        $goodsData = D('Shop_goods')->combination_data_num($goodsData);

        // 店铺信息
        $store = D(_view("Merchant_store"))->field(true)->where(array('store_id' => $store_id))->find();
        if ($store['have_meal'] == 0 || $store['status'] != 1) {
            return array('error_code' => true, 'msg' => '商家已经关闭了该业务,不能下单了!');
        }

        // 商家信息
        $merchant = D("Merchant")->field(true)->where(array('mer_id' => $store['mer_id']))->find();

        $error_return = array();
        $error_msg = '';

        foreach ($goodsData as $row) {
            $goods_id = $row['productId'];
            $num = $row['count'];
            $spec_ids = array();
            $str_s = array();
            $str_p = array();
            $str_s_second = array();
            $str_p_second = array();
            $properties = array();// 属性
            $error_msg = '';
            foreach ($row['productParam'] as $r) {
                if ($r['type'] == 'spec') {// 规格
                    $spec_ids[] = $r['id'];
                    $str_s[] = $r['name']; 

                    $spec_value_obj = M(_view('Shop_goods_spec_value',$merchant['merchant_default_lang']));
                    $goods_properties_temp = $spec_value_obj->field(true)->where(array('id' => $r['id']))->find(array(),$merchant['merchant_default_lang']);
                    // var_dump($goods_properties_temp['name']);
                    $str_s_second[] = $goods_properties_temp['name'] ? $goods_properties_temp['name'] : $r['name'];
                } else {// 属性
                    // 展示两种语言
                    $properties_obj = M(_view('Shop_goods_properties',$merchant['merchant_default_lang']));
                    $goods_properties_temp = array_column($properties_obj->field(true)->where(array('goods_id' => $goods_id))->order('id asc')->select(array(),$merchant['merchant_default_lang']),'val','id');
                    foreach ($r['data'] as $d) {
                        $str_p[] = $d['name'];
                        $properties[] = $d['list_id'].':'.$d['id'];
                        if ($goods_properties_temp[$d['list_id']]) {
                            $p_tmp = explode(',',$goods_properties_temp[$d['list_id']]);
                            $p_tmp = explode(':',$p_tmp[$d['id']]);
                            $str_p_second[] = $p_tmp[0] ? $p_tmp[0] : $d['name'];
                        }
                    }
                }
            }
            $spec_str = $spec_ids ? implode('_', $spec_ids) : '';

            $str = '';
            $str_s && $str = implode(',', $str_s);
            $str_p && $str = $str ? $str . '、' . implode(',', $str_p) : implode(',', $str_p);

            // 第二种语言（商家）
            $str_second = '';
            $str_s_second && $str_second = implode(',', $str_s_second);
            $str_p_second && $str_second = $str_second ? $str_second . '、' . implode(',', $str_p_second) : implode(',', $str_p_second);
            if ($num < 1 && !$weight) continue;

            $tNum = isset($totalData[$goods_id . '_' . $spec_str]) ? intval($totalData[$goods_id . '_' . $spec_str]) : $num;
            $tWeight = isset($totalData[$goods_id . '_' . $spec_str . 'w']) ? intval($totalData[$goods_id . '_' . $spec_str . 'w']) : $weight;

            //校验商品信息和库存，获取商品实际的单价
            /*重点*/
            $t_return = D('foodshop_goods_new')->check_stock($goods_id, $tNum, $spec_str, $store_shop['stock_type'], $store_id, $is_market, $uid, $group_id, $bar_id, $team_id, $tWeight, $row['host_goods_id']);

            // 价格变高
            if ($t_return['status'] == 0) {
                // 已下架
                $error_return['removed_goods'][] = array(
                    'goods_name' => $row['productName'] ? html_entity_decode($row['productName'],ENT_QUOTES) : html_entity_decode($row['name'],ENT_QUOTES),
                    'goods_id' => $goods_id,
                    'host_goods_id' => isset($row['host_goods_id']) ? $row['host_goods_id'] : 0, //附属商品关联的主商品ID
                    'uniqueness_number' => isset($row['uniqueness_number']) ? $row['uniqueness_number'] : 0 ,//附属商品与主商品的相同唯一ID
                );
                if (!$error_msg) {
                    $error_msg = $t_return['msg'];
                }
                // return array('error_code' => true, 'msg' => $t_return['msg']);
            }elseif ($t_return['status'] == 3) {
                // 不在售卖时间内
                $error_return['out_time_goods'][] = array(
                    'goods_name' => $row['productName'] ? html_entity_decode($row['productName'],ENT_QUOTES) : html_entity_decode($row['name'],ENT_QUOTES),
                    'goods_id' => $goods_id,
                    'host_goods_id' => isset($row['host_goods_id']) ? $row['host_goods_id'] : 0, //附属商品关联的主商品ID
                    'uniqueness_number' => isset($row['uniqueness_number']) ? $row['uniqueness_number'] : 0 ,//附属商品与主商品的相同唯一ID
                );
                if (!$error_msg) {
                    $error_msg = $t_return['msg'];
                }
                // return array('error_code' => true, 'msg' => $t_return['msg']);
            }elseif ($t_return['status'] == 4) {
                // 库存不足
                $error_return['no_stock_goods'][] = array(
                    'goods_name' => $row['productName'] ? html_entity_decode($row['productName'],ENT_QUOTES) : html_entity_decode($row['name'],ENT_QUOTES),
                    'goods_id' => $goods_id,
                    'host_goods_id' => isset($row['host_goods_id']) ? $row['host_goods_id'] : 0, //附属商品关联的主商品ID
                    'uniqueness_number' => isset($row['uniqueness_number']) ? $row['uniqueness_number'] : 0 ,//附属商品与主商品的相同唯一ID
                );
                if (!$error_msg) {
                    $error_msg = $t_return['msg'];
                }
                // return array('error_code' => true, 'msg' => $t_return['msg']);
            } elseif ($t_return['status'] == 2) {
                // 售罄
                $error_return['out_stock_goods'][] = array(
                    'goods_name' => $row['productName'] ? html_entity_decode($row['productName'],ENT_QUOTES) : html_entity_decode($row['name'],ENT_QUOTES),
                    'goods_id' => $goods_id,
                    'host_goods_id' => isset($row['host_goods_id']) ? $row['host_goods_id'] : 0, //附属商品关联的主商品ID
                    'uniqueness_number' => isset($row['uniqueness_number']) ? $row['uniqueness_number'] : 0 ,//附属商品与主商品的相同唯一ID
                );
                if (!$error_msg) {
                    $error_msg = $t_return['msg'];
                }
                // return array('error_code' => true, 'msg' => $t_return['msg']);
            }
            
            // 价格变高提示用户 提单提交成功
            if ($t_return['price']>$row['productPrice']) {
                $out_price_goods[] = array(
                    'goods_name' => $row['productName'] ? html_entity_decode($row['productName'],ENT_QUOTES) : html_entity_decode($row['name'],ENT_QUOTES),
                    'goods_id' => $goods_id,
                    'host_goods_id' => isset($row['host_goods_id']) ? $row['host_goods_id'] : 0, //附属商品关联的主商品ID
                    'uniqueness_number' => isset($row['uniqueness_number']) ? $row['uniqueness_number'] : 0 ,//附属商品与主商品的相同唯一ID
                );
            }

            $total += $num;

            //处理每单中商品按优惠价计算的个数
            $oldNum = 0;
            
            $price += floatval($t_return['price'] * $num);
            // $price += floatval($row['code_price']);

            // 限时优惠不参与满减
            if ($t_return['is_seckill_price']) {
                $is_discount = 1;
            }

            $goods[] = array(
                'name' => $row['productName'] ? html_entity_decode($row['productName'],ENT_QUOTES) : html_entity_decode($row['name'],ENT_QUOTES),
                'num' => $num,
                'goods_id' => $goods_id,
                'old_price' => floatval($t_return['old_price']),//商品原始价
                'price' => floatval($t_return['price']),
                'discount_price' => floatval($only_discount_price),//折扣价
                'cost_price' => floatval($t_return['cost_price']),
                'number' => $t_return['number'],
                'image' => $t_return['image'],
                'sort_id' => $t_return['sort_id'],
                'unit' => $t_return['unit'],
                'str' => $str,
                'str_second' => $str_second,
                'properties' => implode('_',$properties),
                'spec_id' => $spec_str,
                'sell_type' => $t_return['sell_type'],
                'goods_weight' => $t_return['goods_weight'],
                'code_weight' => $row['code_weight'],
                'code_price' => $row['code_price'],
                'host_goods_id' => isset($row['host_goods_id']) ? $row['host_goods_id'] : 0, //附属商品关联的主商品ID
                'uniqueness_number' => isset($row['uniqueness_number']) ? $row['uniqueness_number'] : 0 ,//附属商品与主商品的相同唯一ID
            );
        }

        if ($error_return) {
            return array('error_code' => true, 'data'=>$error_return,'msg' => $error_msg);
        }

        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($store['pic_info']);
        $store['images'] = $images ? array_shift($images) : '';

        $store_shop = D("Merchant_store_foodshop")->field(true)->where(array('store_id' => $store_id))->find();
        if (empty($store) || empty($store_shop)) {
            return array('error_code' => true, 'msg' => L_('店铺信息有误'));
        }
        

        $store = array_merge($store, $store_shop);
        $mer_id = $store['mer_id'];
        $minus_price = 0;
        //会员等级优惠  外卖费不参加优惠
        $vip_discount_money = round($price, 2);

   
        // 满减优惠
        $discounts = D('Shop_discount')->getDiscounts($store['mer_id'], $store_id, '',0,1);

        $discount_list = null;
        //优惠
        $sys_first_reduce = 0;//平台首单优惠
        $sto_first_reduce = 0;//店铺首单优惠
        $sys_full_reduce = 0;//平台满减
        $sto_full_reduce = 0;//店铺满减
        $platform_merchant = 0;//平台优惠中商家补贴的总和统计
        $platform_plat = 0;//平台优惠中平台补贴的总和统计

        // 店铺优惠在前，平台优惠在后
        $sto_count = M('Foodshop_order')->where(array('uid' => $uid, 'store_id' => $store_id))->count();
        if (empty($sto_count)) {
            if ($d_tmp = $this->getReduce($discounts, 0, $vip_discount_money, $store_id, $is_discount)) {
                $dd_tmp = array();
                $dd_tmp['discount_type'] = 3;//店铺首单
                $dd_tmp['name'] = $d_tmp['name'];
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['checkCartid'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $dd_tmp['order_count'] = $d_tmp['order_count'];
                $dd_tmp['type'] = $d_tmp['type'];
                $discount_list['newuser'] = $dd_tmp;
                $sto_first_reduce += $d_tmp['reduce_money'];
            }
            if ($d_tmp = $this->getNoShareReduce($discounts, 0, $vip_discount_money, $store_id, $is_discount)) {
                foreach ($d_tmp as $dt) {
                    if ($dt['is_share'] == 0) {
                        $noDiscountList[] = array('type' => 3, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                    }
                }
            }
        }

        // 店铺折扣
        if ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, $store_id, $is_discount,'',1)) {
           
            $dd_tmp = array();
            $dd_tmp['discount_type'] = 5;//店铺折扣
            $dd_tmp['name'] = $d_tmp['name'];
            $dd_tmp['money'] = $d_tmp['full_money'];
            $dd_tmp['minus'] = $d_tmp['reduce_money'];
            $dd_tmp['minus2'] = round($vip_discount_money * (1 - $d_tmp['reduce_money']),2);//减了多少
            $dd_tmp['did'] = $d_tmp['id'];
            $dd_tmp['plat_money'] = $d_tmp['plat_money'];
            $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
            $dd_tmp['order_count'] = $d_tmp['order_count'];
            $dd_tmp['type'] = $d_tmp['type'];
            $discount_list['sto_discount'] = $dd_tmp;

            //打折 优惠的价格=价格*(1-折扣率)
            $sto_full_reduce += $dd_tmp['minus2'];
        }

        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, $store_id, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 4, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                }
            }
        }


        // 店铺满减
        if ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, $store_id, $is_discount)) {
                // 折扣与满减不同享
            if (!$discount_list['sto_discount'] || $discount_list['sto_discount']['minus2'] < $d_tmp['reduce_money']) {
                if ($discount_list['sto_discount']) {
                    $sto_full_reduce -= floatval($discount_list['sto_discount']['minus2']);
                }
                $dd_tmp = array();
                $dd_tmp['discount_type'] = 4;//店铺满减
                $dd_tmp['name'] = $d_tmp['name'];
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['id'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $dd_tmp['order_count'] = $d_tmp['order_count'];
                $dd_tmp['type'] = $d_tmp['type'];
                $discount_list['minus'] = $dd_tmp;
                $sto_full_reduce += $d_tmp['reduce_money'];
            }
        }
        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, $store_id, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 4, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                }
            }
        }


        if ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, 0, $is_discount, '', 1)) {
            $dd_tmp = array();
            $dd_tmp['discount_type'] = 6;//平台满额打折
            $dd_tmp['name'] = $d_tmp['name'];
            $dd_tmp['money'] = $d_tmp['full_money'];
            $dd_tmp['minus'] = $d_tmp['reduce_money'];
            $dd_tmp['minus2'] = round(($vip_discount_money-$sto_full_reduce-$sto_first_reduce) * (1 - $d_tmp['reduce_money']),2);//减了多少
            $dd_tmp['did'] = $d_tmp['id'];
            $dd_tmp['plat_money'] = $d_tmp['plat_money'];
            $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
            $dd_tmp['order_count'] = $d_tmp['order_count'];
            $dd_tmp['percentage_status'] = $d_tmp['percentage_status'];
            $dd_tmp['type'] = $d_tmp['type'];
            $dd_tmp['minus2'] =  $dd_tmp['minus2']>0 ?  $dd_tmp['minus2'] : 0;
            $discount_list['plat_discount'] = $dd_tmp;

            //打折 优惠的价格=价格*(1-折扣率)
            $sys_full_reduce += $dd_tmp['minus2'];

            $platform_merchant =  round($dd_tmp['minus2'] * $d_tmp['plat_money'],2);//平台优惠中商家补贴的
            $platform_plat =  round($dd_tmp['minus2'] * $d_tmp['merchant_money'],2);//平台优惠中平台补贴的
        }
        
        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, 0, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 6, 'money' => $dt['full_money'], 'minus' => $vip_discount_money * (1 - $d_tmp['reduce_money']));
                }
            }
        }

        //已下单数量
        $sys_count = M('Foodshop_order')->where(array('uid' => $uid, 'paid' => 1, 'status' => array('not in', '0,5')))->count();
        if ($discounts) {
            $ok = 0;
            foreach ($discounts as $key => $value) {
                foreach ($value as $k => $v) {
                    if ($v['order_count'] > $sys_count) {
                        $ok = 1;
                    }
                }
            }
        }



        if ($ok && $uid) {//平台首单优惠折扣
            if ($d_tmp = $this->getReduce($discounts, 0, $vip_discount_money, 0, $is_discount, $sys_count,1)) {
                $can_discount_money = ($vip_discount_money-$sto_full_reduce-$sto_first_reduce-$sys_full_reduce);
                $dd_tmp = array();
                $dd_tmp['discount_type'] = 7;//店铺折扣
                $dd_tmp['name'] = $d_tmp['name'];
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['minus2'] = round($can_discount_money * (1 - $d_tmp['reduce_money']),2);//减了多少
                $dd_tmp['did'] = $d_tmp['id'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $dd_tmp['order_count'] = $d_tmp['order_count'];
                $dd_tmp['percentage_status'] = $d_tmp['percentage_status'];
                $dd_tmp['type'] = $d_tmp['type'];
                $dd_tmp['minus2'] =  $dd_tmp['minus2']>0 ?  $dd_tmp['minus2'] : 0;
                $discount_list['plat_newuser_discount'] = $dd_tmp;
                
                if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                    $sys_first_reduce += getFormatNumber($dd_tmp['minus2'] * $d_tmp['plat_money']);
                    $platform_plat += getFormatNumber($dd_tmp['minus2'] * $d_tmp['plat_money']);
                    $sto_first_reduce += getFormatNumber($dd_tmp['minus2'] * $d_tmp['merchant_money']);
                    $platform_merchant += getFormatNumber($dd_tmp['minus2'] * $d_tmp['merchant_money']);
                } else {
                    $sys_first_reduce += $dd_tmp['minus2'];
                    $platform_plat += $dd_tmp['minus2'];
                }
            }
            if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, 0, $is_discount, $sys_count)) {
                foreach ($d_tmp as $dt) {
                    if ($dt['is_share'] == 0) {
                        $noDiscountList[] = array('type' => 1, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                    }
                }
            }
        }

        if ($ok && $uid) {//平台首单优惠
            if ($d_tmp = $this->getReduce($discounts, 0, $vip_discount_money, 0, $is_discount, $sys_count)) {
                if (!$discount_list['plat_newuser_discount'] || $discount_list['plat_newuser_discount']['minus2'] < $d_tmp['reduce_money']) {
                    if ($discount_list['plat_newuser_discount']) {
                        $sys_first_reduce += $d_tmp['plat_money'];
                        $platform_plat += $d_tmp['plat_money'];
                        $sto_first_reduce += $d_tmp['merchant_money'];
                        $platform_merchant += $d_tmp['merchant_money'];

                        if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                            $minus2 = $discount_list['plat_newuser_discount']['minus2'];
                            $sys_first_reduce -= getFormatNumber($minus2 * $discount_list['plat_newuser_discount']['plat_money']);
                            $sto_first_reduce -= getFormatNumber($minus2 * $discount_list['plat_newuser_discount']['merchant_money']);
                            $platform_merchant -= getFormatNumber($minus2 * $discount_list['plat_newuser_discount']['merchant_money']);
                            $platform_plat -=  getFormatNumber($minus2 * $discount_list['plat_newuser_discount']['plat_money']);
                        }else{
                            $sys_first_reduce -= $discount_list['plat_newuser_discount']['minus2'];
                            $platform_plat -= $discount_list['plat_newuser_discount']['minus2'];
                        }
                    }
                    $dd_tmp = array();
                    $dd_tmp['discount_type'] = 1;//平台首单
                    $dd_tmp['name'] = $d_tmp['name'];
                    $dd_tmp['money'] = $d_tmp['full_money'];
                    $dd_tmp['minus'] = $d_tmp['reduce_money'];
                    $dd_tmp['did'] = $d_tmp['id'];
                    $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                    $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                    $dd_tmp['order_count'] = $d_tmp['order_count'];
                    $dd_tmp['type'] = $d_tmp['type'];
                    
                    $discount_list['system_newuser'] = $dd_tmp;
                    if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                        $sys_first_reduce += $d_tmp['plat_money'];
                        $platform_plat += $d_tmp['plat_money'];
                        $sto_first_reduce += $d_tmp['merchant_money'];
                        $platform_merchant += $d_tmp['merchant_money'];
                    } else {
                        $sys_first_reduce += $d_tmp['reduce_money'];
                        $platform_plat += $d_tmp['reduce_money'];
                    }
                }
            }
            if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, 0, $is_discount, $sys_count)) {
                foreach ($d_tmp as $dt) {
                    if ($dt['is_share'] == 0) {
                        $noDiscountList[] = array('type' => 1, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                    }
                }
            }
        }

        if ($uid && ($d_tmp = $this->getReduce($discounts, 1, $vip_discount_money, 0, $is_discount))) {
            // 折扣与满减不同享
            if (!$discount_list['plat_discount'] || $discount_list['plat_discount']['minus2'] < $d_tmp['reduce_money']) {
                if ($discount_list['plat_discount']) {
                    $sto_full_reduce -= floatval($discount_list['plat_discount']['minus2']);
                    $platform_merchant -= round($discount_list['plat_discount']['minus2'] * $discount_list['plat_discount']['plat_money'],2);
                    $platform_plat -=  round($discount_list['plat_discount']['minus2'] * $discount_list['plat_discount']['merchant_money'],2);
                }
                //平台优惠中平台补贴的

                $dd_tmp = array();
                $dd_tmp['discount_type'] = 2;//平台满减
                $dd_tmp['name'] = $d_tmp['name'];
                $dd_tmp['money'] = $d_tmp['full_money'];
                $dd_tmp['minus'] = $d_tmp['reduce_money'];
                $dd_tmp['did'] = $d_tmp['id'];
                $dd_tmp['plat_money'] = $d_tmp['plat_money'];
                $dd_tmp['merchant_money'] = $d_tmp['merchant_money'];
                $dd_tmp['order_count'] = $d_tmp['order_count'];
                $dd_tmp['type'] = $d_tmp['type'];
                $discount_list['system_minus'] = $dd_tmp;
                if ($d_tmp['plat_money'] > 0 || $d_tmp['merchant_money']) {
                    $sys_full_reduce += $d_tmp['plat_money'];
                    $platform_plat += $d_tmp['plat_money'];
                    $sto_full_reduce += $d_tmp['merchant_money'];
                    $platform_merchant += $d_tmp['merchant_money'];
                } else {
                    $sys_full_reduce += $d_tmp['reduce_money'];
                    $platform_plat += $d_tmp['reduce_money'];
                }
            }
            
        }
        if ($d_tmp = $this->getNoShareReduce($discounts, 1, $vip_discount_money, 0, $is_discount)) {
            foreach ($d_tmp as $dt) {
                if ($dt['is_share'] == 0) {
                    $noDiscountList[] = array('type' => 2, 'money' => $dt['full_money'], 'minus' => $dt['reduce_money']);
                }
            }
        }

        
        if (empty($goods)) {
            return array('error_code' => true, 'msg' => L_('购物车是空的'));
        } else {
            $data = array('error_code' => false);
            $data['total'] = $total;
            $data['price'] = $price;//商品实际总价
            $data['extra_price'] = $extra_price;//商品实际总价
            $data['discount_price'] = $vip_discount_money;//折扣后的总价
            $data['goods'] = $goods;
            $data['store_id'] = $store_id;
            $data['mer_id'] = $mer_id;
            $data['store'] = $store;
            $data['merchant'] = $merchant;
            $data['discount_list'] = $discount_list;
            $data['sys_first_reduce'] = $sys_first_reduce;//平台新单优惠的金额
            $data['sys_full_reduce'] = $sys_full_reduce;//平台满减优惠的金额
            $data['sto_first_reduce'] = $sto_first_reduce;//店铺新单优惠的金额
            $data['sto_full_reduce'] = $sto_full_reduce;//店铺满减优惠的金额
            $data['can_discount_money'] = $price;//可用商家优惠券的总价
            $data['platform_merchant'] = $platform_merchant;//平台优惠中商家补贴的总和统计
            $data['platform_plat'] = $platform_plat;//平台优惠中平台补贴的总和统计
            $data['store_discount_money'] = $store_discount_money;//店铺折扣后的总价
            $data['vip_discount_money'] = $vip_discount_money;//VIP折扣后的总价
            $data['sorts_discount_type'] = $sorts_discout['discount_type'];
            $data['vip_discount'] = $vip_discount;//会员等级折扣
            $data['vip_level_reduce_money'] = $vip_level_reduce_money;//正常计算的会员等级优惠金额
            $data['userphone'] = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';
            $data['noDiscountList'] = $noDiscountList;
            $data['noDiscountGoods'] = $noDiscountGoods;
            $data['out_price_goods'] = $out_price_goods;
            fdump($data,'data');

            foreach($data['goods'] as $v){
            }
            return $data;
        }
    }
    

     /**
     * 检查库存
     * @param int $store_id
     * @param int $goods_id
     * @param int $num
     * @param string $spec_ids = 'id_id'
     * @return multitype:number string |multitype:number unknown |multitype:number string Ambigous <number, mixed>
     */
    public function check_stock($goods_id, $num, $spec_ids = '', $stock_type = 0, $store_id = 0, $is_market = false, $uid = 0, $group_id = 0, $bar_id = 0, $team_id = 0, $tWeight = 0, $host_goods_id = 0)
    {
        if ($store_id) {
            $now_goods = D('Shop_goods')->field(true)->where(array('goods_id' => $goods_id, 'store_id' => $store_id))->find();
            $now_foodshop_goods = $this->field(true)->where(array('goods_id' => $goods_id, 'store_id' => $store_id))->find();
        } else {
            $now_goods = D('Shop_goods')->field(true)->where(array('goods_id' => $goods_id))->find();
            $now_foodshop_goods = $this->field(true)->where(array('goods_id' => $goods_id))->find();
        }
        if (empty($now_goods)) return array('status' => 0, 'msg' => L_('商品不存在'));

        // 商品库存
        $stock_num = 0;

        if ($host_goods_id==0) {
            // 非附属菜 餐饮菜品
            if (empty($now_foodshop_goods)) return array('status' => 0, 'msg' => L_('商品不存在'));

             // 售卖时间
            if ($now_foodshop_goods['spec_type'] == 1) {
                $now_time = date('H:i');
                if (!$now_foodshop_goods['spec_open_time']&&!$now_foodshop_goods['spec_close_time']) {
                    $open_time = '00:00';
                    $close_time = '24:00';
                }else{
                    $open_time = date('H:i', $now_foodshop_goods['spec_open_time']);
                    $close_time = date('H:i', $now_foodshop_goods['spec_close_time']);
                }
            } else {
                $now_time = time();
                if (!$now_foodshop_goods['spec_open_datetime']&&!$now_foodshop_goods['spec_close_datetime']) {
                    $open_time = strtotime(date('Y-m-d 00:00:00'));
                    $close_time = strtotime(date('Y-m-d 23:59:59'));
                }else{
                    $open_time = $now_foodshop_goods['spec_open_datetime'];
                    $close_time = $now_foodshop_goods['spec_close_datetime'];
                }
            }

            if ( $open_time > $now_time || $close_time < $now_time){
                // 不在售卖时间内
                return array('status' => 3, 'msg' => L_('不在售卖时间内'));
            } 

            $now_goods['seckill_type'] = $now_foodshop_goods['seckill_type'];
            $now_goods['seckill_price'] = $now_foodshop_goods['seckill_price'];
            $now_goods['seckill_stock'] = $now_foodshop_goods['seckill_stock'];
            $now_goods['seckill_open_time'] = $now_foodshop_goods['seckill_open_time'];
            $now_goods['seckill_close_time'] = $now_foodshop_goods['seckill_close_time'];
            $now_goods['today_seckill_count'] = $now_foodshop_goods['today_seckill_count'];
            $now_goods['price'] = $now_foodshop_goods['price'];
            $now_goods['old_price'] = $now_foodshop_goods['old_price'];
            // 餐饮库存
            if ($now_foodshop_goods['spec_stock'] == '-1') {
                $stock_num = $now_foodshop_goods['stock_num'] = $now_goods['stock_num'];
            }else{
                $stock_num = $now_foodshop_goods['stock_num'] = $now_foodshop_goods['spec_stock'];
            }

        }
        
        
        //商品缩略图
        $goods_image_class = new goods_image();
        $tmp_pic_arr = $goods_image_class->get_allImage_by_path($now_goods['image'], 's');
        $image = thumb_img($tmp_pic_arr[0],130,130,'fill');
        if ($host_goods_id==0) {
            // 餐饮商品下架了
            if ($now_foodshop_goods['status'] != 1) return array('status' => 0, 'msg' => $now_goods['name'] . L_('商品已下架'));
        }else{
            if ($now_goods['status'] != 1) return array('status' => 0, 'msg' => $now_goods['name'] . L_('商品已下架'));
        }
        $today = date('Ymd');

        //商品的库存类型（0：每日更新相同的库存，1:商品的总库存不会自动更新）
        $now_goods['sell_day'] = $stock_type ? $today : $now_goods['sell_day'];

        // 限时优惠
        if ($now_goods['seckill_type'] == 1) {//秒杀类型（0：固定时间段，1：每天的时间段）
            $now_time = date('H:i');
            $open_time = date('H:i', $now_goods['seckill_open_time']);
            $close_time = date('H:i', $now_goods['seckill_close_time']);
            //秒杀库存的计算
            $seckill_stock_num = $now_goods['seckill_stock'];
        } else {
            $now_time = time();
            $open_time = $now_goods['seckill_open_time'];
            $close_time = $now_goods['seckill_close_time'];
            //秒杀库存的计算
            $seckill_stock_num = $now_goods['seckill_stock'] == -1 ? -1 : intval($now_goods['seckill_stock']);
        }
       
        $is_seckill_price = false;
        if ($open_time < $now_time && $now_time < $close_time && floatval($now_goods['seckill_price']) > 0 && $seckill_stock_num != 0) {
            // 单价
            $price = floatval($now_goods['seckill_price']);
            $is_seckill_price = true;
        } else {
            // 单价
            $price = floatval($now_goods['price']);
        }

        $maxNum = $now_goods['max_num'];

        /*商品总量*/
        $weight = $now_goods['weight'];

        // 原价
        $old_price = floatval($now_goods['old_price']);

        // 进价
        $cost_price = floatval($now_goods['cost_price']);

        if ($spec_ids && $now_goods['spec_value']) {
            // 有规格
            if ($host_goods_id==0) {
                $return = D('Shop_goods')->format_spec_value($now_goods['spec_value'], $now_goods['goods_id'], $now_goods['is_properties'], 0,'',$now_goods['foodshop_spec_value']);
            }else{
                $return = D('Shop_goods')->format_spec_value($now_goods['spec_value'], $now_goods['goods_id'], $now_goods['is_properties'], 0);
            }

            $list = isset($return['list']) ? $return['list'] : '';
            if (isset($list[$spec_ids])) {
                $today_sell_spec = json_decode($now_goods['today_sell_spec'], true);

                if ($open_time < $now_time && $now_time < $close_time && $list[$spec_ids]['seckill_price'] > 0 && $seckill_stock_num != 0) {
                    // 单价
                    $price = $list[$spec_ids]['seckill_price'];
                    $is_seckill_price = true;
                } else {
                    // 单价
                    $price = $list[$spec_ids]['price'];
                }
               
                $old_price = floatval($list[$spec_ids]['price']);
                $cost_price = floatval($list[$spec_ids]['cost_price']);
                $number = $list[$spec_ids]['number'];
                $maxNum = $list[$spec_ids]['max_num'];
                $weight = $list[$spec_ids]['weight'];
                if ($is_seckill_price) {
                    $stock_num = $seckill_stock_num;
                } else {
                   if ($today == $now_goods['sell_day']) {
                        $sell_count = isset($today_sell_spec[$spec_ids]) ? intval($today_sell_spec[$spec_ids]) : 0;
                        $stock_num = $list[$spec_ids]['stock_num'] == -1 ? -1 : (intval($list[$spec_ids]['stock_num'] - $sell_count) > 0 ? intval($list[$spec_ids]['stock_num'] - $sell_count) : 0);
                    } else {
                        $stock_num = $list[$spec_ids]['stock_num'];
                    }
                }
                
            } else {
                return array('status' => 2, 'msg' => L_('您选择的规格可能被商家修改了'));
            }
        } else { 
            if ($is_seckill_price) {
                $stock_num = $seckill_stock_num; 
            } else {
               
                if ($host_goods_id==0) {
                    if ($now_foodshop_goods['stock_num'] != -1) {
                        if ($now_foodshop_goods['stock_num']<=0) {
                            return array('status' => 2, 'msg' => '商品已售罄');
                        }else{
                            $stock_num = $now_foodshop_goods['stock_num'];
                        }    
                    }
                }else{
                    if ($today == $now_goods['sell_day']) {
                        $stock_num = $now_goods['stock_num'];
                        /*$stock_num = $now_goods['stock_num'] == -1 ? -1 : (intval($now_goods['stock_num'] - $now_goods['today_sell_count']) > 0 ? intval($now_goods['stock_num'] - $now_goods['today_sell_count']) : 0);*/
                    } else {
                        $stock_num = $now_goods['stock_num'];
                    }
                    // if ($today == $now_goods['sell_day']) {
                    //     $sell_count = isset($today_sell_spec[$spec_ids]) ? intval($today_sell_spec[$spec_ids]) : 0;
                    //     $stock_num = $list[$spec_ids]['stock_num'] == -1 ? -1 : (intval($list[$spec_ids]['stock_num'] - $sell_count) > 0 ? intval($list[$spec_ids]['stock_num'] - $sell_count) : 0);
                    // } else {
                    //     $stock_num = $list[$spec_ids]['stock_num'];
                    // }
                }
            }
            $number = $now_goods['number'];
        }
        $maxNum = intval($maxNum);
        if ($now_goods['min_num'] > 1) {
            $maxNum = 0;
        }

        //如果为附属商品才限购
        if ($host_goods_id > 0) {
            if (empty($is_seckill_price) && $maxNum > 0 && $num > $maxNum) {
                return array('status' => 2, 'msg' => L_("商品【X1】每单限购X2", array("X1" => $now_goods['name'], "X2" => $maxNum . $now_goods['unit'])));
            }
        }

        // 如果是附属商品和非多规格商品取出对应的商品价格
        if ($host_goods_id AND empty($now_goods['spec_value'])) {
            $price = M('shop_subsidiary_piece_goods')->where(array('goods_id' => $goods_id, 'host_goods_id' => $host_goods_id, 'is_delete' => 0))->getField('price');
        }
        $goods_stock_num_key = 'goods_stock_'.$now_goods['goods_id'];
        $s_stock_num = S($goods_stock_num_key);
        if($s_stock_num<>false&&$s_stock_num==0){
            return array('status' => 2, 'msg' => '商品已售罄');
        }

        if ($stock_num == -1) {
            $return = array('status' => 1, 'num' => $num, 'goods_id' => $now_goods['goods_id'], 'maxNum' => $maxNum, 'old_price' => $old_price, 'cost_price' => $cost_price, 'price' => $price, 'image' => $image, 'limit_type' => $now_goods['limit_type'],  'freight_type' => $now_goods['freight_type'], 'freight_value' => $now_goods['freight_value'], 'freight_template' => $now_goods['freight_template'], 'unit' => $now_goods['unit'], 'number' => $number, 'sort_id' => $now_goods['sort_id'], 'name' => $now_goods['name'], 'sell_type' => $now_goods['sell_type'], 'goods_weight' => $now_goods['goods_weight']);
            $return['weight'] = $weight;
            $return['weight_unit'] = $now_goods['weight_unit'];

            return $return;
            
        } elseif ($stock_num == 0) {
            return array('status' => 2, 'msg' => L_('商品已售罄'));
        } elseif ($stock_num - $num >= 0) {
            $return = array('status' => 1, 'num' => $num, 'goods_id' => $now_goods['goods_id'], 'maxNum' => $maxNum,'old_price' => $old_price, 'cost_price' => $cost_price, 'price' => $price, 'image' => $image, 'limit_type' => $now_goods['limit_type'],  'freight_type' => $now_goods['freight_type'], 'freight_value' => $now_goods['freight_value'], 'freight_template' => $now_goods['freight_template'], 'unit' => $now_goods['unit'], 'number' => $number, 'sort_id' => $now_goods['sort_id'], 'name' => $now_goods['name'], 'sell_type' => $now_goods['sell_type'], 'goods_weight' => $now_goods['goods_weight']);
            $return['weight'] = $weight;
            $return['weight_unit'] = $now_goods['weight_unit'];
            return $return;
        } else {
            // 库存不足
            $return = array('status' => 4, 'num' => $stock_num, 'goods_id' => $now_goods['goods_id'], 'maxNum' => $maxNum, 'old_price' => $old_price, 'cost_price' => $cost_price, 'msg' => $now_goods['name'] . ' ' . L_('库存不足,最多能购买') . $stock_num . $now_goods['unit'], 'limit_type' => $now_goods['limit_type'], 'number' => $number, 'price' => $price, 'image' => $image, 'packing_charge' => $now_goods['packing_charge'], 'freight_type' => $now_goods['freight_type'], 'freight_value' => $now_goods['freight_value'], 'freight_template' => $now_goods['freight_template'], 'unit' => $now_goods['unit'], 'sort_id' => $now_goods['sort_id'], 'name' => $now_goods['name'],  'sell_type' => $now_goods['sell_type'], 'goods_weight' => $now_goods['goods_weight']);
            $return['weight'] = $weight;
            $return['weight_unit'] = $now_goods['weight_unit'];
            return $return;
            
        }
    } 

    /**
     * 更新库存
     *
     * $type 操作类型 0：加销量，减库存，1：加库存，减销量
     * 餐饮都是固定库存
     */
    public function update_stock($goods, $type = 0)
    {
        $now_goods = $this->field(true)->where(array('goods_id' => $goods['goods_id']))->find();
        $nowShopGoods = D('Shop_goods')->field(true)->where(array('goods_id' => $goods['goods_id']))->find();
        if (empty($nowGoods)) {
            return array('status' => 0, 'msg' => L_('商品不存在'));
        }
        
        if ($type == 0) {//加销量
            $num = $goods['num'];
            $total_num = $goods['num'];
        } else {//减销量
            $num = $goods['num'] * -1;
            $total_num = $goods['num'] * -1;
        }
        
        $sell_count = $now_goods['sell_count'];//总销量
        $seckill_count = $now_goods['seckill_count'];//总销量
        if ($goods['is_seckill']) {
            $seckill_count += $num;
        } 
        
        $sell_count += $total_num;
        if ($goods['spec_id']) {//某种规格
            //规格值ID:规格值ID:...:规格值ID|price(现价):seckill_price(秒杀价):stock_num(库存)#
            $spec_array = explode('#', $nowShopGoods['foodshop_spec_value']);
            $specValueArr = array();
            foreach ($spec_array as $spec) {
                $thisRow = explode('|', $spec);
                $ids = str_replace(':', '_', $thisRow[0]);
                if ($ids == $goods['spec_id']) {
                    $values = explode(':', $thisRow[1]);
                    
                    $tSN = isset($values[2]) ? $values[2] : -1;
                    if ($tSN != -1) {
                        $thisNum = $tSN - $num;
                        $tSN = max(0, $thisNum);
                    }
                    $values[2] = $tSN;
                    $thisRow[1] = implode(':', $values);
                }
                $specValueArr[] = implode('|', $thisRow);
            }
            $foodshop_spec_value = implode('#', $specValueArr);
           
        }

        //原库存
        $stock_num = $now_goods['spec_stock'];
        if ($stock_num >= 0) {
            if ($stock_num - $num >= 0) {
                $stock_num -= $num;
            } else {
                $stock_num = 0;
            }
        }

        //原限时优惠库存
        $seckill_stock = $now_goods['seckill_stock'];
        if ($seckill_stock >= 0) {
            if ($seckill_stock - $num >= 0) {
                $seckill_stock -= $num;
            } else {
                $seckill_stock = 0;
            }
        }

        $sell_count = max(0, $sell_count);
        $seckill_count = max(0, $seckill_count);

        $this->where(array('goods_id' => $goods['goods_id']))->save(array('stock_num' => $stock_num,'seckill_count' => $seckill_count, 'sell_count' => $sell_count, 'seckill_stock' => $seckill_stock));
        // 更新规格库存
        M('Shop_goods')->where(array('goods_id' => $goods['goods_id']))->save(array('foodshop_spec_value' => $foodshop_spec_value));
        // 更新快店库存
        D('Shop_goods')->update_stock($goods, $type, 1);
    }
    
    /*
        discounts        优惠列表
        type             优惠类型0商家1平台
        price            可优惠金额
        store_id         店铺id
        is_discount      是否已打过折
        order_count      优惠程度 限制几单享受优惠 0为不限制
        percentage_status     是否使用百分比折扣优惠 1是 0否
    */
    public function getReduce($discounts, $type, $price, $store_id = 0, $is_discount = 1, $order_count = 0, $percentage_status = 0)
    {
        $reduce_money = 0;
        if($percentage_status == 1){
            $reduce_money = 1;
        }

        $return = null;
        if (!isset($discounts[$store_id])) {
            return $return;
        }

        foreach ($discounts[$store_id] as $row) {
            if ($type == 0) {
                // 首单优惠
                if ($row['order_count'] > $order_count && $row['percentage_status'] == $percentage_status && ($row['is_share'] || $is_discount == 0)) {
                    if ($price >= $row['full_money']) {
                        // 平台优惠
                        if($percentage_status == 1){
                            //折扣
                            if ($reduce_money > $row['reduce_money']) {
                                $reduce_money = $row['reduce_money'];
                                $return = $row;
                            }
                        }else{
                            if ($reduce_money < $row['reduce_money']) {
                                $reduce_money = $row['reduce_money'];
                                $return = $row;
                            }
                        }
                    }
                }
            } else {
                // 平台优惠
                if($percentage_status == 1){
                    //折扣
                    if ($row['type'] == $type && $row['order_count'] == 0 && $row['percentage_status'] == $percentage_status && ($row['is_share'] || $is_discount == 0)) {
                        if ($price >= $row['full_money']) {
                            if ($reduce_money > $row['reduce_money']) {
                                $reduce_money = $row['reduce_money'];
                                $return = $row;
                            }
                        }
                    }

                }else{

                    if ($row['type'] == $type && $row['order_count'] == 0 && $row['percentage_status'] == $percentage_status && ($row['is_share'] || $is_discount == 0)) {
                        if ($price >= $row['full_money']) {
                            if ($reduce_money < $row['reduce_money']) {
                                $reduce_money = $row['reduce_money'];
                                $return = $row;
                            }
                        }
                    }
                }

            }
        }
        
        return $return;
    }

    private function getNoShareReduce($discounts, $type, $price, $store_id = 0, $is_discount = 1, $order_count = 0)
    {
        $return = null;
        if (isset($discounts[$store_id])) {
            foreach ($discounts[$store_id] as $row) {
                if ($type == 0) {
                    if ($row['order_count'] > $order_count && $is_discount) {
                        if ($price >= $row['full_money']) {
                            $return[] = $row;
                        }
                    }
                } else {
                    if ($row['type'] == $type && $is_discount) {
                        if ($price >= $row['full_money']) {
                            $return[] = $row;
                        }
                    }
                }
            }
        }
        return $return;
    }

    // 满减优惠格式化内容
    public function discount_formart($discount_list='')
    {
        $new_discount_list = array();
        foreach ($discount_list as $key => $dval) {
            if ($dval['percentage_status']==1) {
                $dis_text = '享X2折';
                $dis_num = floatval($dval['minus']*10);
            }else{
                $dis_text = '减X2';
                $dis_num = C('config.Currency_symbol') . floatval($dval['minus']);
            }
            switch ($key) {
                case 'plat_discount':
                    if (getFormatNumber($dval['money'])) {
                        $text = L_('商品满X1,享X2折', array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => getFormatNumber($dval['minus'] * 10)));
                    }else{
                        $text = L_('商品享X2折', array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => getFormatNumber($dval['minus'] * 10)));
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus2']));
                    break;
                case 'newuser':  
                    if ($dval['order_count'] == 1) {
                        if (getFormatNumber($dval['money'])) {
                            $pre_text = '店铺首单满X1';
                        }else{
                            $pre_text = '店铺首单';
                        }
                        $text = L_($pre_text.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } else { 
                        if ($dval['money']) {
                            $pre_text = '店铺首X0单满X1';
                        }else{
                            $pre_text = '店铺首X0单';
                        }
                        $text = L_($pre_text.$dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
                case 'minus':
                        if (getFormatNumber($dval['money'])) {
                            $pre_text = '店铺优惠满X1';
                        }else{
                            $pre_text = '店铺优惠';
                        }
                    $text = L_($pre_text.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
                case 'system_newuser':
                    if ($dval['order_count'] == 1) {
                        if (getFormatNumber($dval['money'])) {
                            $pre_text = '平台首单满X1';
                        }else{
                            $pre_text = '平台首单';
                        }
                        $text = L_($pre_text.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } else {
                        if (getFormatNumber($dval['money'])) {
                            $pre_text = '平台首X0单满X1';
                        }else{
                            $pre_text = '平台首X0单';
                        }
                        $text = L_($pre_text.$dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
                case 'plat_newuser_discount':
                    if ($dval['order_count'] == 1) {
                        if (getFormatNumber($dval['money'])) {
                            $pre_text = '平台首单满X1';
                        }else{
                            $pre_text = '平台首单';
                        }
                        $text = L_($pre_text.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    } else {
                        if (getFormatNumber($dval['money'])) {
                            $pre_text = '平台首X0单满X1';
                        }else{
                            $pre_text = '平台首X0单';
                        }
                        $text = L_($pre_text.$dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    }
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus2']));
                    break;
                case 'system_minus':
                        if (getFormatNumber($dval['money'])) {
                            $pre_text = '平台优惠满X1';
                        }else{
                            $pre_text = '平台优惠';
                        }
                    $text = L_($pre_text.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                    $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                    break;
            }
        }
        return $new_discount_list;
    }

    // 优惠券格式化内容
    public function formart_coupon($coupon_list){
        foreach($coupon_list as $k=>&$v){
            if($v['cate_id']!='0'){
                $v['cate_name'].=$v['cate_id']['cat_name'];
            }
            if($v['cate_name']=='all'){
                $v['cate_name']=L_('全通类优惠券');
            }
            if($v['is_discount']){
                $v['coupon_des'] = ($v['order_money']>0?L_('满'.C('config.Currency_symbol').'X1',getFormatNumber($v['order_money'])):'').L_('打X1折',floatval($v['discount_value']));
            }else{
                $v['coupon_des'] = ($v['order_money']>0?L_('满'.C('config.Currency_symbol').'X1',getFormatNumber($v['order_money'])):'').L_('减'.C('config.Currency_symbol').'X1',getFormatNumber($v['discount']));
            }
            $tmp_coupon_list['discount_value'] = floatval($v['discount_value']);
            $tmp_coupon_list['is_discount'] = $v['is_discount'];
            $tmp_coupon_list['coupon_des'] = $v['coupon_des'];
            $tmp_coupon_list['name'] = $v['name'];
            $tmp_coupon_list['discount'] = getFormatNumber($v['discount']);
            $tmp_coupon_list['order_money'] = getFormatNumber($v['order_money']);
            $tmp_coupon_list['had_id'] = $v['id'];
           // $tmp_coupon_list['coupon_id'] = $v['coupon_id'];
            if($_GET['coupon_type']=='mer'){
                $tmp_coupon_list['img'] = $this->config['site_url'].$v['img'];
            }
            $tmp_coupon_list['platform'] = $v['platform'];
            $tmp_coupon_list['des'] = $v['des'];
            $tmp_coupon_list['cate_name'] = $v['cate_name'];
            $tmp_coupon_list['time'] = L_('有效期至X1',date('y.m.d',$v['end_time']));
            $coupon_list_[] = $tmp_coupon_list;
            unset($coupon_list[$k]['cate_id']);
        }
        return $coupon_list_;
    }
}
